import { selectors } from 'AssetsJsPath/backend/builder/selectors';
import { Canvas, InteractiveFabricObject, FabricImage } from 'fabric';
import { AligningGuidelines } from 'fabric/extensions';
import { Point } from 'fabric';
import { CANVAS_DEFAULTS, ALIGNMENT, TIMING } from 'AssetsJsPath/backend/builder/config';
import { lpOnElementReady } from 'AssetsJsPath/utils';
import {
	updateTextboxWidthInput,
	updatePositionPanelValues,
	handleSelectionChange,
	handleSelectionCleared
} from 'AssetsJsPath/backend/builder/toolbar';
import { highlightSelectedLayer, clearHighlight, scrollToSelectedLayer } from 'AssetsJsPath/backend/builder/layers/panel';
import { isLayersMenuActive } from 'AssetsJsPath/backend/builder/menu';
import { autoResizeCanvasToFit, checkLocalImageMissing } from 'AssetsJsPath/backend/builder/utils';
import { initPlaceholderAutocomplete } from 'AssetsJsPath/backend/builder/core/placeholder-autocomplete';

export function setControls( obj ) {
	if ( ! obj ) {
		return;
	}

	const objType = obj.type?.toLowerCase();
	const isQr = obj.get( 'type_layer' ) === 'qr_code';

	if ( isQr ) {
		obj.setControlsVisibility( {
			tl: true,
			tr: true,
			bl: true,
			br: true,
			mt: false,
			mb: false,
			ml: false,
			mr: false,
			mtr: true,
		} );
		obj.set( 'lockUniScaling', true );
		return;
	}

	const textTypes = [ 'i-text', 'textbox' ];

	if ( ! textTypes.includes( objType ) ) {
		return;
	}

	const isTextbox = objType === 'textbox';

	obj.setControlsVisibility( {
		tl: false,
		tr: false,
		bl: false,
		br: false,
		mt: false,
		mb: false,
		ml: isTextbox,
		mr: isTextbox,
		mtr: true,
	} );
}

export class CanvasManager {
	constructor( certificateId ) {
		this.certificateId = certificateId;
		this.canvas = null;
		this.isUpdatingSelection = false;
		this.textChangeTimeout = null;
	}

	async init( layerManager, contextMenu ) {
		const canvasEl = document.querySelector( selectors.elCanvas );
		if ( ! canvasEl ) {
			return;
		}

		try {

			const canvasData = window.lpCertCanvasData || {
				width: CANVAS_DEFAULTS.WIDTH,
				height: CANVAS_DEFAULTS.HEIGHT,
				background: CANVAS_DEFAULTS.BACKGROUND,
			};

			const isImageUrl = ( value ) => {
				if ( ! value || typeof value !== 'string' ) {
					return false;
				}
				return /^(https?:\/\/|\/|data:image)/i.test( value.trim() );
			};

			const backgroundValue = canvasData.background || CANVAS_DEFAULTS.BACKGROUND;
			const isBackgroundImage = isImageUrl( backgroundValue );

			this.canvas = new Canvas( canvasEl, {
				width: parseInt( canvasData.width ) || CANVAS_DEFAULTS.WIDTH,
				height: parseInt( canvasData.height ) || CANVAS_DEFAULTS.HEIGHT,
				backgroundColor: isBackgroundImage ? '' : backgroundValue,
				fireRightClick: true,
				stopContextMenu: true,
			} );

			let bgMissing = false;
			if ( isBackgroundImage ) {
				const bgLoaded = await this.loadBackgroundImage( backgroundValue );
				if ( ! bgLoaded ) {
					const missing = await checkLocalImageMissing( backgroundValue );
					if ( missing ) {
						canvasData.background = canvasData.no_image_url || '';
						window.lpCertCanvasData = canvasData;
						if ( canvasData.no_image_url ) {
							await this.loadBackgroundImage( canvasData.no_image_url );
						}
						bgMissing = true;
					}
				}
			}

			InteractiveFabricObject.ownDefaults = {
				...InteractiveFabricObject.ownDefaults,
				cornerStyle: 'circle',
				cornerStrokeColor: 'blue',
				cornerColor: 'lightblue',
				padding: 10,
				transparentCorners: false,
				cornerDashArray: [2, 2],
				borderColor: 'orange',
				borderDashArray: [3, 1, 3],
				borderScaleFactor: 2,
			}

			this.canvas.renderAll();

			const brokenImageCount = await layerManager.loadLayers();

			if ( bgMissing || brokenImageCount > 0 ) {
				layerManager.saveCanvasLayers( false, 0, true );
			}

			this.setupCanvasEvents( layerManager, contextMenu );

			this.initCanvasCenterGuidelines();

			lpOnElementReady( selectors.elCanvas, () => {
				this.autoResizeCanvas();
			} );

			this.setupResizeObserver();

			window.lpCertCanvas = this.canvas;

			this.setupCanvasAreaClickHandler();
		} catch ( error ) {
			console.error( 'Error initializing canvas:', error );
		}
	}

	setupCanvasEvents( layerManager, contextMenu ) {
		this.canvas.on( 'object:moving', ( e ) => {
			const obj = e.target;
			if ( ! obj ) {
				return;
			}

			updatePositionPanelValues( obj );
		} );

		this.canvas.on( 'object:resizing', ( e ) => {
			const obj = e.target;
			if ( ! obj ) {
				return;
			}

			updateTextboxWidthInput( obj );
		} );

		this.canvas.on('object:modified', (e) => {
			if (this.isUpdatingSelection) {
				return;
			}

			const obj = e.target;
			if (!obj) {
				return;
			}

			obj.setCoords();

			const isMultipleSelection = obj.type === 'activeselection' && obj._objects;

			if (isMultipleSelection) {
				const selectedObjects = [...obj._objects];

				this.isUpdatingSelection = true;
				this.canvas.discardActiveObject();

				selectedObjects.forEach((child) => {
					child.setCoords();
					const layerId = child.get('id');
					if (layerId) {
						layerManager.updateLayerDataFromObject(layerId, child);
					}
				});

				if (selectedObjects.length > 1) {
					import('fabric').then(({ ActiveSelection }) => {
						const selection = new ActiveSelection(selectedObjects, {
							canvas: this.canvas,
						});
						this.canvas.setActiveObject(selection);
						this.canvas.requestRenderAll();
						this.isUpdatingSelection = false;
					});
				} else if (selectedObjects.length === 1) {
					this.canvas.setActiveObject(selectedObjects[0]);
					this.canvas.requestRenderAll();
					this.isUpdatingSelection = false;
				}
			} else {
				const layerId = obj.get('id');
				if (layerId) {
					layerManager.updateLayerDataFromObject(layerId, obj);
				}

				updateTextboxWidthInput( obj );
				updatePositionPanelValues( obj );
			}

			layerManager.saveCanvasLayers(false);
		});

		this.canvas.on('text:changed', (e) => {
			const obj = e.target;
			if (!obj) {
				return;
			}

			this.handleTextAutoExpand(obj);

			if (this.textChangeTimeout) {
				clearTimeout(this.textChangeTimeout);
			}

			this.textChangeTimeout = setTimeout(() => {
				const layerId = obj.get('id');
				if (layerId) {
					layerManager.updateLayerDataFromObject(layerId, obj);
					layerManager.saveCanvasLayers();
				}
				this.textChangeTimeout = null;
			}, TIMING.DEBOUNCE_SAVE);
		});

		this.canvas.on('editing:exited', (e) => {
			const obj = e.target;
			if (!obj) {
				return;
			}

			if (this.textChangeTimeout) {
				clearTimeout(this.textChangeTimeout);
				this.textChangeTimeout = null;
			}

			const layerId = obj.get('id');
			if (layerId) {
				layerManager.updateLayerDataFromObject(layerId, obj);
				layerManager.saveCanvasLayers();
			}
		});

		initPlaceholderAutocomplete( this.canvas );

		this.canvas.on( 'selection:created', () => {
			highlightSelectedLayer();
			handleSelectionChange( this.canvas );
			this.canvas.requestRenderAll();
			if ( isLayersMenuActive() ) {
				setTimeout( () => scrollToSelectedLayer(), 50 );
			}
		} );

		this.canvas.on( 'selection:updated', () => {
			highlightSelectedLayer();
			handleSelectionChange( this.canvas );
			this.canvas.requestRenderAll();
			if ( isLayersMenuActive() ) {
				setTimeout( () => scrollToSelectedLayer(), 50 );
			}
		} );

		this.canvas.on( 'selection:cleared', () => {
			clearHighlight();
			handleSelectionCleared();
		} );

		if ( contextMenu ) {
			contextMenu.init();

			this.canvas.on('mouse:down', (opt) => {
				if (opt.e.button === 2) {
					const target = opt.target;

					if (target && target !== this.canvas.backgroundImage) {
						if (!opt.alreadySelected) {
							this.canvas.setActiveObject(target);
							this.canvas.requestRenderAll();
						}

						contextMenu.show(opt.e.clientX, opt.e.clientY, false);
					} else {
						this.canvas.discardActiveObject();
						this.canvas.requestRenderAll();
						contextMenu.show(opt.e.clientX, opt.e.clientY, true);
					}
				}
			});
		}
	}

	async loadBackgroundImage( imageUrl ) {
		if ( ! this.canvas || ! imageUrl ) {
			return false;
		}

		const applyBg = ( img ) => {
			const w = this.canvas.getWidth();
			const h = this.canvas.getHeight();
			const scale = Math.max( w / img.width, h / img.height );
			img.set( {
				scaleX: scale,
				scaleY: scale,
				originX: 'center',
				originY: 'center',
				left: w / 2,
				top: h / 2,
				selectable: false,
				evented: false,
			} );
			this.canvas.backgroundImage = img;
			this.canvas.renderAll();
		};

		try {
			const img = await FabricImage.fromURL( imageUrl, { crossOrigin: 'anonymous' } );
			if ( img && img.width && img.height ) {
				applyBg( img );
				return true;
			}
			throw new Error( 'empty dimensions' );
		} catch ( error ) {
			console.warn( 'Background image failed to load:', imageUrl );
			return false;
		}
	}

	autoResizeCanvas() {
		const retry = autoResizeCanvasToFit( this.canvas );
		if ( retry ) {
			requestAnimationFrame( () => this.autoResizeCanvas() );
		}
	}

	setupResizeObserver() {
		if ( ! this.canvas ) {
			return;
		}

		const canvasWrapper = this.canvas.wrapperEl;
		const container = canvasWrapper?.parentElement;

		if ( ! container ) {
			return;
		}

		this.resizeObserver = new ResizeObserver( () => {
			this.autoResizeCanvas();
		} );

		this.resizeObserver.observe( container );
	}

	handleTextAutoExpand( obj ) {
		if ( ! obj || ! this.canvas ) {
			return;
		}

		const textAlign = obj.textAlign || 'left';
		const originX = obj.originX || 'center';

		const expectedOriginX = this.getExpectedOriginX( textAlign );
		if ( originX === expectedOriginX ) {
			return;
		}

		const boundingRect = obj.getBoundingRect();
		const visualLeft = boundingRect.left;
		const visualRight = boundingRect.left + boundingRect.width;
		const visualCenter = boundingRect.left + boundingRect.width / 2;

		let newLeft = obj.left;

		switch ( textAlign ) {
			case 'left':
				obj.set( 'originX', 'left' );
				newLeft = visualLeft;
				break;

			case 'right':
				obj.set( 'originX', 'right' );
				newLeft = visualRight;
				break;

			case 'center':
			case 'justify':
				obj.set( 'originX', 'center' );
				newLeft = visualCenter;
				break;
		}

		obj.set( 'left', newLeft );
		obj.setCoords();
	}

	getExpectedOriginX( textAlign ) {
		switch ( textAlign ) {
			case 'left':
				return 'left';
			case 'right':
				return 'right';
			case 'center':
			case 'justify':
			default:
				return 'center';
		}
	}

	initCanvasCenterGuidelines() {

		new AligningGuidelines( this.canvas );

		const canvas = this.canvas;
		if ( ! canvas ) {
			return;
		}

		const config = {
			color: ALIGNMENT.COLOR,
			width: ALIGNMENT.LINE_WIDTH,
			margin: ALIGNMENT.MARGIN,
		};

		let centerVerticalLine = null;
		let centerHorizontalLine = null;

		const drawLine = ( ctx, x1, y1, x2, y2 ) => {
			const vpt = canvas.viewportTransform || [ 1, 0, 0, 1, 0, 0 ];
			const zoom = canvas.getZoom();

			ctx.save();
			ctx.lineWidth = config.width;
			ctx.strokeStyle = config.color;
			ctx.setLineDash( [] );
			ctx.beginPath();
			ctx.moveTo( x1 * zoom + vpt[ 4 ], y1 * zoom + vpt[ 5 ] );
			ctx.lineTo( x2 * zoom + vpt[ 4 ], y2 * zoom + vpt[ 5 ] );
			ctx.stroke();
			ctx.restore();
		};

		const isInRange = ( value1, value2 ) => {
			return Math.abs( Math.round( value1 ) - Math.round( value2 ) ) <= config.margin;
		};

		canvas.on( 'object:moving', ( e ) => {
			const obj = e.target;
			if ( ! obj ) {
				return;
			}

			const canvasCenterX = canvas.width / 2;
			const canvasCenterY = canvas.height / 2;
			const objCenter = obj.getCenterPoint();

			centerVerticalLine = null;
			centerHorizontalLine = null;

			if ( isInRange( objCenter.x, canvasCenterX ) ) {
				centerVerticalLine = { x: canvasCenterX, y1: 0, y2: canvas.height };
				obj.setPositionByOrigin(
					new Point( canvasCenterX, objCenter.y ),
					'center',
					'center'
				);
			}

			if ( isInRange( objCenter.y, canvasCenterY ) ) {
				centerHorizontalLine = { y: canvasCenterY, x1: 0, x2: canvas.width };
				obj.setPositionByOrigin(
					new Point( obj.getCenterPoint().x, canvasCenterY ),
					'center',
					'center'
				);
			}
		} );

		canvas.on( 'after:render', () => {
			const ctx = canvas.getSelectionContext();
			if ( ! ctx ) {
				return;
			}

			if ( centerVerticalLine ) {
				drawLine( ctx, centerVerticalLine.x, centerVerticalLine.y1, centerVerticalLine.x, centerVerticalLine.y2 );
			}
			if ( centerHorizontalLine ) {
				drawLine( ctx, centerHorizontalLine.x1, centerHorizontalLine.y, centerHorizontalLine.x2, centerHorizontalLine.y );
			}
		} );

		canvas.on( 'mouse:up', () => {
			centerVerticalLine = null;
			centerHorizontalLine = null;
			canvas.requestRenderAll();
		} );
	}

	setupCanvasAreaClickHandler() {
		if ( ! this.canvas ) {
			return;
		}

		const canvasArea = document.querySelector( '.lp-cert-builder-canvas-area' );
		if ( ! canvasArea ) {
			return;
		}

		const canvasWrapper = this.canvas.wrapperEl;
		if ( ! canvasWrapper ) {
			return;
		}

		const toolbarSelectors = [
			'.lp-cert-text-toolbar',
			'.lp-cert-image-toolbar',
			'.lp-cert-svg-toolbar',
			'.lp-cert-position-panel',
		];

		this._canvasAreaClickHandler = ( e ) => {
			if ( ! this.canvas ) {
				return;
			}

			if ( canvasWrapper.contains( e.target ) ) {
				return;
			}

			const isToolbarClick = toolbarSelectors.some( ( selector ) => {
				const toolbar = document.querySelector( selector );
				return toolbar && toolbar.contains( e.target );
			} );

			if ( isToolbarClick ) {
				return;
			}

			this.canvas.discardActiveObject();
			this.canvas.requestRenderAll();
		};

		canvasArea.addEventListener( 'click', this._canvasAreaClickHandler );
	}


	cleanup() {
		if ( this.resizeObserver ) {
			this.resizeObserver.disconnect();
			this.resizeObserver = null;
		}

		if ( this.textChangeTimeout ) {
			clearTimeout( this.textChangeTimeout );
			this.textChangeTimeout = null;
		}

		if ( this._canvasAreaClickHandler ) {
			const canvasArea = document.querySelector( '.lp-cert-builder-canvas-area' );
			if ( canvasArea ) {
				canvasArea.removeEventListener( 'click', this._canvasAreaClickHandler );
			}
			this._canvasAreaClickHandler = null;
		}

		if ( this.canvas ) {
			this.canvas.dispose();
			this.canvas = null;
		}
	}
}
