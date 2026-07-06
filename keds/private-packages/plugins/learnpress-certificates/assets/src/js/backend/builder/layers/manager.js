import { showToastify } from 'AssetsJsPath/backend/utils/toastify';
import { lpCertConfirm } from 'AssetsJsPath/backend/utils/confirm';
import { replaceNewlinesForLoad, generateLayerId, resolveFont, checkLocalImageMissing } from 'AssetsJsPath/backend/builder/utils';
import { createFabricElement, isTextType, isSvgType } from 'AssetsJsPath/backend/builder/elements';
import { Rect, ActiveSelection, Textbox } from 'fabric';
import { selectors } from 'AssetsJsPath/backend/builder/selectors';
import { setControls } from 'AssetsJsPath/backend/builder/core';
import { updatePositionPanelValues } from 'AssetsJsPath/backend/builder/toolbar';
import { refreshLayersPanel } from './panel';
import { setCanvasBackgroundImage, updateBackgroundColorActiveState } from 'AssetsJsPath/backend/builder/backgrounds';
import { TEXT_DEFAULTS, TIMING, LAYER_DEFAULTS, ELEMENT_SCALING, CANVAS_DEFAULTS } from 'AssetsJsPath/backend/builder/config';

function getCopyName( name ) {
	const baseName = name || 'Layer';
	if ( baseName.endsWith( ' (copy)' ) ) {
		return baseName;
	}
	return baseName + ' (copy)';
}

export class LayerManager {
	constructor( canvasManager ) {
		this.canvasManager = canvasManager;
		this.saveTimeout = null;
		this.historyTimeout = null;
		this._clipboard = null;
		this._undoStack = [];
		this._redoStack = [];
		this._maxHistorySteps = LAYER_DEFAULTS.MAX_HISTORY_STEPS;
		this._isRestoring = false;
		this._lastSavedState = null;
		this._keyboardMoveTimeout = null;
	}

	get canvas() {
		return this.canvasManager?.canvas || null;
	}

	get certificateId() {
		return this.canvasManager?.certificateId || 0;
	}

	async insertFabricElement( type, data, options = {}, autoSave = true ) {

		const commonConfig = {
			left: null,
			top: null,
			originX: 'center',
			originY: 'center',
			width: null,
			height: null,
			scaleX: 1,
			scaleY: 1,
			centerOnCanvas: true,
		};

		const typeSpecificConfig = {};

		if ( type === 'text-edit' || type === 'text-static' ) {
			typeSpecificConfig.enterEditing = false;
			typeSpecificConfig.fontFamily = TEXT_DEFAULTS.FONT_FAMILY;
			typeSpecificConfig.fill = TEXT_DEFAULTS.FILL_COLOR;
			typeSpecificConfig.fontSize = TEXT_DEFAULTS.FONT_SIZE;
		}

		const defaultConfig = { ...commonConfig, ...typeSpecificConfig };
		const mergedOptions = { ...defaultConfig, ...options };

		if ( mergedOptions.left !== null || mergedOptions.top !== null ) {
			mergedOptions.centerOnCanvas = false;
		}

		if ( mergedOptions.left === null && this.canvas ) {
			mergedOptions.left = this.canvas.width / 2;
		}
		if ( mergedOptions.top === null && this.canvas ) {
			mergedOptions.top = this.canvas.height / 2;
		}

		const enterEditing = mergedOptions.enterEditing;

		if ( ! this.canvas ) {
			console.error( 'Canvas not initialized' );
			return null;
		}

		try {
			let fabricObj;

			fabricObj = await createFabricElement( type, data, mergedOptions );

			if ( ! fabricObj ) {
				console.error( 'Failed to create fabric object' );
				return null;
			}

			if ( this.canvas && ! isTextType( type ) ) {
				const canvasWidth = this.canvas.width || 0;
				const canvasHeight = this.canvas.height || 0;

				const objWidth = fabricObj.width || 0;
				const objHeight = fabricObj.height || 0;

				if ( canvasWidth > 0 && canvasHeight > 0 && objWidth > 0 && objHeight > 0 ) {
					const scaleX = (canvasWidth * ELEMENT_SCALING.TARGET_PERCENT) / objWidth;
					const scaleY = (canvasHeight * ELEMENT_SCALING.TARGET_PERCENT) / objHeight;

					const finalScale = Math.min( scaleX, scaleY );

					fabricObj.scaleX = finalScale;
					fabricObj.scaleY = finalScale;
				}
			}

			const layerId = generateLayerId();
			fabricObj.set('id', layerId);
			fabricObj.set('type_layer', type);

			fabricObj.set('name', mergedOptions.name || 'Layer');

			if ( isTextType( type ) || type === 'qr_code' ) {
				setControls( fabricObj );
			}

			if ( this.canvas ) {
				this.canvas.add( fabricObj );

				if ( mergedOptions.centerOnCanvas ) {
					this.canvas.centerObject( fabricObj );
				}

				fabricObj.setCoords();
				this.canvas.setActiveObject( fabricObj );
			}

			if ( enterEditing && fabricObj.enterEditing ) {
				fabricObj.enterEditing();
				if ( fabricObj.selectAll ) {
					fabricObj.selectAll();
				}
			}

			this.canvas.requestRenderAll();

			if ( autoSave ) {
				this.saveFabricElementToLayer( fabricObj, layerId );
				this.saveCanvasLayers( false, TIMING.DEBOUNCE_SAVE );
			}

			return { fabricObj, layerId };

		} catch ( error ) {
			console.error( 'Error inserting fabric element:', error );
			return null;
		}
	}

	saveFabricElementToLayer( fabricObj, layerId ) {
		const canvasData = window.lpCertCanvasData || {};
		const certificateId = canvasData.certificate_id;

		if ( ! certificateId ) {
			console.error( 'Certificate ID not found' );
			return null;
		}

		const customType = fabricObj.get( 'type_layer' );

		if ( ! customType ) {
			console.error( 'type_layer not set on fabric object' );
			return null;
		}

		const finalLayerData = fabricObj.toObject( [ 'id', 'type_layer', 'name', 'cornerRadius' ] );

		this.updateLayerDataFromObject( layerId, fabricObj );

		return finalLayerData;
	}

	updateLayerDataFromObject( layerId, fabricObj ) {
		const canvasData = window.lpCertCanvasData || {};
		const layers = canvasData.layers || [];

		if ( ! Array.isArray( layers ) ) {
			return;
		}

		const layerIndex = layers.findIndex( ( layer ) => {
			if ( ! layer ) {
				return false;
			}
			return layer.id === layerId;
		} );

		const fabricData = fabricObj.toObject( [ 'id', 'type_layer', 'name', 'cornerRadius' ] );
		const customType = fabricData.type_layer;

		if ( ! customType ) {
			console.error( 'type_layer not set on fabric object' );
			return;
		}

		const isTextElement = isTextType( customType );
		const text = isTextElement && fabricObj.text !== undefined
			? fabricObj.text.replace( /\n/g, '{n}' ).replace( /"/g, '&quot;' )
			: undefined;

		if ( layerIndex === -1 ) {
			const newLayer = { ...fabricData };
			if ( text !== undefined ) {
				newLayer.text = text;
			}
			layers.push( newLayer );
		} else {
			const nextLayer = {
				...layers[layerIndex],
				...fabricData,
			};
			if ( text !== undefined ) {
				nextLayer.text = text;
			} else if ( ! isTextElement ) {
				delete nextLayer.text;
			}
			layers[layerIndex] = nextLayer;
		}

		canvasData.layers = layers;
		window.lpCertCanvasData = canvasData;
	}

	saveCanvasLayers( showToast = false, debounce = 0, skipHistory = false, historyDebounce = 0 ) {
		if ( this.saveTimeout ) {
			clearTimeout( this.saveTimeout );
			this.saveTimeout = null;
		}

		if ( ! skipHistory && ! this._isRestoring ) {
			if ( historyDebounce > 0 ) {
				if ( this.historyTimeout ) {
					clearTimeout( this.historyTimeout );
				}
				this.historyTimeout = setTimeout( () => {
					this._updateHistoryStack();
					this.historyTimeout = null;
				}, historyDebounce );
			} else {
				this._updateHistoryStack();
			}
		}

		// if ( debounce === 0 || ! debounce ) {
		// 	this._performServerSave( showToast );
		// 	return;
		// }

		this.saveTimeout = setTimeout( () => {
			this._performServerSave( showToast );
			this.saveTimeout = null;
		}, TIMING.DEBOUNCE_SAVE );
	}

	_updateHistoryStack() {
		if ( ! this.canvas ) {
			return;
		}

		const canvasData = window.lpCertCanvasData || {};
		const newState = JSON.stringify( {
			canvas: this.canvas.toJSON( [ 'id', 'name', 'type_layer', 'cornerRadius' ] ),
			data: JSON.parse( JSON.stringify( canvasData ) )
		} );

		if ( this._lastSavedState === newState ) {
			return;
		}

		if ( this._lastSavedState ) {
			this._undoStack.push( this._lastSavedState );

			if ( this._undoStack.length > this._maxHistorySteps ) {
				this._undoStack.shift();
			}

			this._redoStack = [];
			this.updateHistoryButtons();
		}

		this._lastSavedState = newState;
	}

	_performServerSave( showToast = false ) {
		const canvasData = window.lpCertCanvasData;
		if ( ! canvasData || ! this.canvas ) {
			return;
		}

		const certificateId = this.certificateId || canvasData.certificate_id;
		if ( ! certificateId ) {
			console.warn( 'Certificate ID not found while saving canvas layers' );
			return;
		}

		const dataSend = {
			action: 'certificate_builder_save',
			certificate_id: certificateId,
			layers: canvasData,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message } = response;
				if ( showToast && message ) {
					showToastify( message, status === 'success' ? 'success' : 'error' );
				}
			},
			error: () => {
				if ( showToast ) {
					showToastify( 'Error saving layer position', 'error' );
				}
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	async loadLayers() {
		if ( ! this.canvas ) return 0;

		const canvasData = window.lpCertCanvasData || {};
		const processedCanvasData = replaceNewlinesForLoad( canvasData );
		const layers = processedCanvasData.layers || [];

		if ( ! Array.isArray( layers ) || layers.length === 0 ) {
			this._lastSavedState = JSON.stringify( {
				canvas: this.canvas.toJSON( [ 'id', 'name', 'type_layer', 'cornerRadius' ] ),
				data: JSON.parse( JSON.stringify( window.lpCertCanvasData || {} ) )
			} );
			return 0;
		}

		let brokenImageCount = 0;

		try {
			for ( let i = 0; i < layers.length; i++ ) {
				const layer = layers[i];

				try {
					const customType = layer.type_layer;
					if ( ! layer || ! customType ) {
						continue;
					}

				const { id, name, type_layer, type, text, src, canvas, group, stateProperties, cacheProperties, ...layerProps } = layer;

				let fabricObj = null;
				const isTextElement = isTextType( customType );

				if ( isTextElement ) {
					const textLayer = ( text || 'Text' ).replace( /\{n\}/g, '\n' ).replace( /\/n/g, '\n' ).replace( /&quot;/g, '"' );

					const targetFontFamily = layerProps.fontFamily || TEXT_DEFAULTS.FONT_FAMILY;
					const fontSize = layerProps.fontSize || TEXT_DEFAULTS.FONT_SIZE;
					const isWrap = type === 'Textbox';
					const isStatic = customType === 'text-static';

					const tempProps = { ...layerProps, fontFamily: TEXT_DEFAULTS.FONT_FAMILY };

					if ( isWrap ) {
						fabricObj = new Textbox( textLayer, {
							...tempProps,
							editable: ! isStatic,
							splitByGrapheme: false,
						} );
					} else {
						fabricObj = await createFabricElement( customType, textLayer, tempProps );
					}

					if ( fabricObj ) {
						fabricObj.set( 'text', textLayer );

						fabricObj.set( 'fontFamily', await resolveFont( targetFontFamily ) );

						if ( typeof fabricObj.initDimensions === 'function' ) {
							fabricObj.initDimensions();
						}
						fabricObj.set( 'dirty', true );
					}
				}
				else if ( customType === 'image' || customType === 'qr_code' ) {
					if ( ! src ) {
						console.warn( `Layer ${ name || i } has no src, skipping` );
						continue;
					}
					try {
						fabricObj = await createFabricElement( customType, src, layerProps );
						if ( ! fabricObj || ! fabricObj.width || ! fabricObj.height ) {
							throw new Error( 'empty dimensions' );
						}
						fabricObj.set( 'canvas', this.canvas );
					} catch ( imgErr ) {
						console.warn( `Image layer "${ name || i }" failed to load:`, src );
						const noImageUrl = window.lpCertCanvasData?.no_image_url;
						if ( noImageUrl ) {
							const missing = await checkLocalImageMissing( src );
							if ( ! missing ) {
								continue;
							}
							try {
								fabricObj = await createFabricElement( customType, noImageUrl, layerProps );
								if ( fabricObj ) {
									fabricObj.set( 'canvas', this.canvas );
									const liveLayers = window.lpCertCanvasData?.layers;
									if ( Array.isArray( liveLayers ) && liveLayers[ i ] ) {
										liveLayers[ i ].src = noImageUrl;
									}
									brokenImageCount++;
								}
							} catch ( placeholderErr ) {
								fabricObj = null;
							}
						} else {
							fabricObj = null;
						}
					}
				}
				else if ( isSvgType( customType ) ) {
					fabricObj = await createFabricElement( customType, layer );
				}

				if ( fabricObj ) {
					const cornerRadius = layerProps?.cornerRadius;

					if ( ! isSvgType( customType ) ) {
						const { clipPath, cornerRadius: _, ...propsToSet } = layerProps;
						fabricObj.set( propsToSet );
					}

					fabricObj.set( 'id', id || '' );
					fabricObj.set( 'type_layer', customType );
					fabricObj.set( 'name', name || 'Layer' );

					if ( isTextElement && layerProps.originX ) {
						fabricObj.set( 'originX', layerProps.originX );
					}

					if ( isTextElement ) {
						setControls( fabricObj );
					}

					if ( customType === 'qr_code' ) {
						setControls( fabricObj );
					}

					fabricObj.setCoords();
					this.canvas.add( fabricObj );
					this.canvas.bringObjectToFront( fabricObj );

					if ( customType === 'image' && cornerRadius && cornerRadius > 0 ) {
						await this.restoreClipPath( fabricObj, cornerRadius );
						this.canvas.requestRenderAll();
					}
				}
				} catch ( layerError ) {
					console.error( `Error loading layer ${ i } (${ layer?.name || 'unknown' }):`, layerError );
				}
			}

			this.canvas.renderAll();

			this._lastSavedState = JSON.stringify( {
				canvas: this.canvas.toJSON( [ 'id', 'name', 'type_layer', 'cornerRadius' ] ),
				data: JSON.parse( JSON.stringify( window.lpCertCanvasData || {} ) )
			} );

			return brokenImageCount;

		} catch ( error ) {
			console.error( 'Error loading layers:', error );
			return 0;
		}
	}

	async applyTemplate( templateId ) {
		if ( ! this.canvas ) {
			return;
		}

		this._isRestoring = true;

		const currentState = JSON.stringify( {
			canvas: this.canvas.toJSON( [ 'id', 'name', 'type_layer', 'cornerRadius' ] ),
			data: JSON.parse( JSON.stringify( window.lpCertCanvasData || {} ) )
		} );

		this._undoStack.push( currentState );
		if ( this._undoStack.length > this._maxHistorySteps ) {
			this._undoStack.shift();
		}
		this._redoStack = [];
		this.updateHistoryButtons();

		const objects = this.canvas.getObjects().filter( ( obj ) => {
			return obj !== this.canvas.backgroundImage && obj.get( 'id' );
		} );

		objects.forEach( ( obj ) => {
			this.canvas.remove( obj );
		} );

		const canvasData = window.lpCertCanvasData || {};
		canvasData.layers = [];
		window.lpCertCanvasData = canvasData;

		this.canvas.discardActiveObject();
		this.canvas.requestRenderAll();

		try {
			const response = await new Promise( ( resolve, reject ) => {
				window.lpAJAXG.fetchAJAX(
					{
						action: 'lp_cert_choose_template_type',
						certificate_id: this.certificateId,
						template_type: templateId,
					},
					{
						success: ( res ) => resolve( res ),
						error: ( err ) => reject( err ),
					}
				);
			} );

			if ( response.status === 'success' && response.data && response.data.layers ) {
				const processedData = response.data.layers;

				window.lpCertCanvasData = {
					certificate_id: this.certificateId,
					...processedData,
				};

				if ( processedData.width && processedData.height ) {
					const width = parseInt( processedData.width );
					const height = parseInt( processedData.height );

					if ( ! isNaN( width ) && ! isNaN( height ) ) {
						this.canvas.setDimensions( { width, height } );

						if ( this.canvasManager && typeof this.canvasManager.autoResizeCanvas === 'function' ) {
							this.canvasManager.autoResizeCanvas();
						}
					}
				}

				const background = processedData.background;
				if ( background ) {
					const isImageUrl = /^(https?:\/\/|\/|data:image)/i.test( background.trim() );

					if ( isImageUrl ) {
						await setCanvasBackgroundImage( background );
					} else {
						if ( this.canvas.backgroundImage ) {
							this.canvas.backgroundImage = null;
						}
						this.canvas.set( 'backgroundColor', background );
						this.canvas.requestRenderAll();
						updateBackgroundColorActiveState();
					}
				}

				await this.loadLayers();

				this.saveCanvasLayers( false, 0, true );
			}
		} catch ( error ) {
			console.error( 'Error applying template:', error );
		} finally {
			this._isRestoring = false;
		}
	}

	async handleDeleteLayer() {
		if (!this.canvas) {
			return;
		}

		const activeObject = this.canvas.getActiveObject();
		if (!activeObject) {
			return;
		}

		const activeObjects = this.canvas.getActiveObjects();

		// const result = await lpCertConfirm( {
		// 	title: 'Are you sure?',
		// 	text: 'Are you sure you want to delete this layer?',
		// 	icon: 'warning',
		// 	confirmButtonText: 'Yes, delete it',
		// 	cancelButtonText: 'Cancel',
		// } );

		// if ( ! result.isConfirmed ) {
		// 	return;
		// }

		if (activeObjects.length > 1) {
			const layerIdsToDelete = [];

			activeObjects.forEach((obj) => {
				const layerId = obj.get('id');
				if (layerId) {
					layerIdsToDelete.push(layerId);
					this.canvas.remove(obj);
				}
			});

			this.canvas.discardActiveObject();
			this.canvas.requestRenderAll();

			const canvasData = window.lpCertCanvasData || {};
			const layers = canvasData.layers || [];

			if (Array.isArray(layers)) {
				const filteredLayers = layers.filter((layer) => {
					return !layerIdsToDelete.includes(layer.id);
				});

				canvasData.layers = filteredLayers;
				window.lpCertCanvasData = canvasData;
			}

			this.saveCanvasLayers(true);

		} else {
			const layerId = activeObject.get('id');
			if (layerId) {
				this.deleteLayer(layerId, activeObject);
			}
		}
	}

	deleteLayer(layerId, fabricObj, skipSave = false) {
		if (!layerId || !fabricObj) {
			return;
		}

		this.canvas.remove(fabricObj);
		this.canvas.discardActiveObject();
		this.canvas.requestRenderAll();

		const canvasData = window.lpCertCanvasData || {};
		const layers = canvasData.layers || [];

		if (Array.isArray(layers)) {
			const filteredLayers = layers.filter((layer) => {
				return layer.id !== layerId;
			});

			canvasData.layers = filteredLayers;
			window.lpCertCanvasData = canvasData;
		}

		if ( ! skipSave ) {
			this.saveCanvasLayers(true);
		}
	}

	async restoreClipPath( fabricObj, cornerRadius ) {
		if ( ! fabricObj || ! cornerRadius || cornerRadius <= 0 ) {
			return;
		}

		try {
			const width = fabricObj.width || 100;
			const height = fabricObj.height || 100;

			const clipRect = new Rect( {
				width: width,
				height: height,
				rx: cornerRadius,
				ry: cornerRadius,
				left: -width / 2,
				top: -height / 2,
				originX: 'left',
				originY: 'top',
				absolutePositioned: false,
			} );

			fabricObj.set( 'clipPath', clipRect );
			fabricObj.set( 'cornerRadius', cornerRadius );
			fabricObj.set( 'dirty', true );
			fabricObj.setCoords();
		} catch ( error ) {
			console.warn( 'Error restoring clipPath:', error );
		}
	}

	handleMoveObject( direction, fastMove = false ) {
		if ( ! this.canvas ) {
			return;
		}

		const activeObject = this.canvas.getActiveObject();
		if ( ! activeObject ) {
			return;
		}

		const step = fastMove ? LAYER_DEFAULTS.MOVE_STEP_FAST : LAYER_DEFAULTS.MOVE_STEP;
		let deltaX = 0;
		let deltaY = 0;

		switch ( direction ) {
			case 'ArrowUp':
				deltaY = -step;
				break;
			case 'ArrowDown':
				deltaY = step;
				break;
			case 'ArrowLeft':
				deltaX = -step;
				break;
			case 'ArrowRight':
				deltaX = step;
				break;
		}

		if ( deltaX === 0 && deltaY === 0 ) {
			return;
		}

		const isMultipleSelection = activeObject.type === 'activeselection' && activeObject._objects;

		activeObject.set( {
			left: activeObject.left + deltaX,
			top: activeObject.top + deltaY,
		} );
		activeObject.setCoords();

		this.canvas.fire( 'object:moving', { target: activeObject } );
		this.canvas.requestRenderAll();

		if ( this._keyboardMoveTimeout ) {
			clearTimeout( this._keyboardMoveTimeout );
		}
		this._keyboardMoveTimeout = setTimeout( () => {
			this.canvas.fire( 'mouse:up', {} );
			this._keyboardMoveTimeout = null;
		}, TIMING.KEYBOARD_MOVE_DELAY );

		updatePositionPanelValues( activeObject );

		if ( isMultipleSelection ) {
			const selectedObjects = [ ...activeObject._objects ];

			this.canvas.discardActiveObject();

			selectedObjects.forEach( ( obj ) => {
				obj.setCoords();
				const layerId = obj.get( 'id' );
				if ( layerId ) {
					this.updateLayerDataFromObject( layerId, obj );
				}
			} );

			if ( selectedObjects.length > 1 ) {
				const selection = new ActiveSelection( selectedObjects, {
					canvas: this.canvas,
				} );
				this.canvas.setActiveObject( selection );
				this.canvas.requestRenderAll();
			} else if ( selectedObjects.length === 1 ) {
				this.canvas.setActiveObject( selectedObjects[ 0 ] );
				this.canvas.requestRenderAll();
			}
		} else {
			const layerId = activeObject.get( 'id' );
			if ( layerId ) {
				this.updateLayerDataFromObject( layerId, activeObject );
			}
		}

		this.saveCanvasLayers( false, TIMING.DEBOUNCE_SAVE );
	}

	handleSelectAll() {
		if ( ! this.canvas ) {
			return;
		}

		const objects = this.canvas.getObjects().filter( ( obj ) => {
			return obj !== this.canvas.backgroundImage && obj.get( 'id' );
		} );

		if ( objects.length === 0 ) {
			return;
		}

		if ( objects.length === 1 ) {
			this.canvas.setActiveObject( objects[ 0 ] );
		} else {
			const selection = new ActiveSelection( objects, {
				canvas: this.canvas,
			} );
			this.canvas.setActiveObject( selection );
		}

		this.canvas.requestRenderAll();
	}

	async handleCopy() {
		if ( ! this.canvas ) {
			return;
		}

		const activeObject = this.canvas.getActiveObject();
		if ( ! activeObject ) {
			return;
		}

		this._clipboard = await activeObject.clone( [ 'name', 'type_layer' ] );
	}

	async handlePaste() {
		if ( ! this.canvas || ! this._clipboard ) {
			return;
		}

		try {
			const clonedObj = await this._clipboard.clone( [ 'name', 'type_layer' ] );
			const offset = LAYER_DEFAULTS.DUPLICATE_OFFSET;

			if ( clonedObj.type === 'activeselection' ) {
				const objects = clonedObj._objects || [];
				const pastedObjects = [];

				objects.forEach( ( obj, index ) => {
					obj.set( 'id', generateLayerId( index ) );
					obj.set( 'name', getCopyName( obj.get( 'name' ) ) );
					setControls( obj );
					this.canvas.add( obj );
					pastedObjects.push( obj );
				} );

				const selection = new ActiveSelection( pastedObjects, {
					canvas: this.canvas,
				} );
				selection.set( {
					left: selection.left + offset,
					top: selection.top + offset,
				} );
				selection.setCoords();
				this.canvas.setActiveObject( selection );
				this.canvas.requestRenderAll();

				this.canvas.discardActiveObject();
				pastedObjects.forEach( ( obj ) => {
					obj.setCoords();
					const layerId = obj.get( 'id' );
					if ( layerId ) {
						this.saveFabricElementToLayer( obj, layerId );
					}
				} );

				if ( pastedObjects.length > 1 ) {
					const newSelection = new ActiveSelection( pastedObjects, {
						canvas: this.canvas,
					} );
					this.canvas.setActiveObject( newSelection );
				} else if ( pastedObjects.length === 1 ) {
					this.canvas.setActiveObject( pastedObjects[ 0 ] );
				}
			} else {
				const newLayerId = generateLayerId();
				clonedObj.set( {
					id: newLayerId,
					name: getCopyName( clonedObj.get( 'name' ) ),
					left: clonedObj.left + offset,
					top: clonedObj.top + offset,
					evented: true,
				} );

				setControls( clonedObj );

				this.canvas.add( clonedObj );
				clonedObj.setCoords();

				this.saveFabricElementToLayer( clonedObj, newLayerId );

				this.canvas.setActiveObject( clonedObj );
			}

			this._clipboard.set( {
				left: this._clipboard.left + offset,
				top: this._clipboard.top + offset,
			} );

			this.canvas.requestRenderAll();
			this.saveCanvasLayers( false );

			const activeObject = this.canvas.getActiveObject();
			if ( activeObject ) {
				updatePositionPanelValues( activeObject );
			}
		} catch ( error ) {
			console.error( 'Error pasting objects:', error );
		}
	}

	async handleDuplicate() {
		if ( ! this.canvas ) {
			return;
		}

		const activeObject = this.canvas.getActiveObject();
		if ( ! activeObject ) {
			return;
		}

		try {
			const clonedObj = await activeObject.clone( [ 'name', 'type_layer' ] );
			const offset = LAYER_DEFAULTS.DUPLICATE_OFFSET;

			if ( clonedObj.type === 'activeselection' ) {
				const objects = clonedObj._objects || [];
				const duplicatedObjects = [];

				objects.forEach( ( obj, index ) => {
					obj.set( 'id', generateLayerId( index ) );
					obj.set( 'name', getCopyName( obj.get( 'name' ) ) );
					setControls( obj );
					this.canvas.add( obj );
					duplicatedObjects.push( obj );
				} );

				const selection = new ActiveSelection( duplicatedObjects, {
					canvas: this.canvas,
				} );
				selection.set( {
					left: selection.left + offset,
					top: selection.top + offset,
				} );
				selection.setCoords();
				this.canvas.setActiveObject( selection );
				this.canvas.requestRenderAll();

				this.canvas.discardActiveObject();
				duplicatedObjects.forEach( ( obj ) => {
					obj.setCoords();
					const layerId = obj.get( 'id' );
					if ( layerId ) {
						this.saveFabricElementToLayer( obj, layerId );
					}
				} );

				if ( duplicatedObjects.length > 1 ) {
					const newSelection = new ActiveSelection( duplicatedObjects, {
						canvas: this.canvas,
					} );
					this.canvas.setActiveObject( newSelection );
				} else if ( duplicatedObjects.length === 1 ) {
					this.canvas.setActiveObject( duplicatedObjects[ 0 ] );
				}
			} else {
				const newLayerId = generateLayerId();
				clonedObj.set( {
					id: newLayerId,
					name: getCopyName( clonedObj.get( 'name' ) ),
					left: clonedObj.left + offset,
					top: clonedObj.top + offset,
					evented: true,
				} );

				setControls( clonedObj );

				this.canvas.add( clonedObj );
				clonedObj.setCoords();

				this.saveFabricElementToLayer( clonedObj, newLayerId );

				this.canvas.setActiveObject( clonedObj );
			}

			this.canvas.requestRenderAll();
			this.saveCanvasLayers( false );

			const finalActiveObject = this.canvas.getActiveObject();
			if ( finalActiveObject ) {
				updatePositionPanelValues( finalActiveObject );
			}
		} catch ( error ) {
			console.error( 'Error duplicating objects:', error );
		}
	}

	async handleUndo() {
		if ( ! this.canvas || this._undoStack.length === 0 || this._isRestoring ) {
			return;
		}

		this._isRestoring = true;

		const canvasState = this.canvas.toJSON( [ 'id', 'name', 'type_layer', 'cornerRadius' ] );
		const canvasData = window.lpCertCanvasData || {};
		const currentState = JSON.stringify( {
			canvas: canvasState,
			data: JSON.parse( JSON.stringify( canvasData ) )
		} );
		this._redoStack.push( currentState );

		if ( this._redoStack.length > this._maxHistorySteps ) {
			this._redoStack.shift();
		}

		const previousState = JSON.parse( this._undoStack.pop() );

		try {
			await this.canvas.loadFromJSON( previousState.canvas );

			if ( previousState.data ) {
				window.lpCertCanvasData = JSON.parse( JSON.stringify( previousState.data ) );

				const newWidth = previousState.data.width;
				const newHeight = previousState.data.height;
				if ( newWidth && newHeight ) {
					this.canvas.setDimensions( { width: newWidth, height: newHeight } );
				}
			}

			const layersData = window.lpCertCanvasData?.layers || [];
			this.canvas.getObjects().forEach( ( obj, index ) => {
				const layerData = layersData[ index ];
				if ( layerData ) {
					obj.set( 'id', layerData.id || '' );
					obj.set( 'name', layerData.name || '' );
					obj.set( 'type_layer', layerData.type_layer || '' );
					if ( layerData.cornerRadius ) {
						obj.set( 'cornerRadius', layerData.cornerRadius );
					}
				}
				if ( isTextType( obj.get( 'type_layer' ) ) ) {
					setControls( obj );
				}
			} );

			this.canvas.renderAll();
			refreshLayersPanel();

			if ( this.canvasManager?.autoResizeCanvas ) {
				this.canvasManager.autoResizeCanvas();
			}

			this._lastSavedState = JSON.stringify( {
				canvas: this.canvas.toJSON( [ 'id', 'name', 'type_layer', 'cornerRadius' ] ),
				data: window.lpCertCanvasData
			} );

			this.saveCanvasLayers( false, TIMING.DEBOUNCE_SAVE, true );
		} catch ( error ) {
			console.error( 'Error during undo:', error );
		}

		this._isRestoring = false;
		this.updateHistoryButtons();
	}

	async handleRedo() {
		if ( ! this.canvas || this._redoStack.length === 0 || this._isRestoring ) {
			return;
		}

		this._isRestoring = true;

		const canvasState = this.canvas.toJSON( [ 'id', 'name', 'type_layer', 'cornerRadius' ] );
		const canvasData = window.lpCertCanvasData || {};
		const currentState = JSON.stringify( {
			canvas: canvasState,
			data: JSON.parse( JSON.stringify( canvasData ) )
		} );
		this._undoStack.push( currentState );

		if ( this._undoStack.length > this._maxHistorySteps ) {
			this._undoStack.shift();
		}

		const nextState = JSON.parse( this._redoStack.pop() );

		try {
			await this.canvas.loadFromJSON( nextState.canvas );

			if ( nextState.data ) {
				window.lpCertCanvasData = JSON.parse( JSON.stringify( nextState.data ) );

				const newWidth = nextState.data.width;
				const newHeight = nextState.data.height;
				if ( newWidth && newHeight ) {
					this.canvas.setDimensions( { width: newWidth, height: newHeight } );
				}
			}

			const layersData = window.lpCertCanvasData?.layers || [];
			this.canvas.getObjects().forEach( ( obj, index ) => {
				const layerData = layersData[ index ];
				if ( layerData ) {
					obj.set( 'id', layerData.id || '' );
					obj.set( 'name', layerData.name || '' );
					obj.set( 'type_layer', layerData.type_layer || '' );
					if ( layerData.cornerRadius ) {
						obj.set( 'cornerRadius', layerData.cornerRadius );
					}
				}
				if ( isTextType( obj.get( 'type_layer' ) ) ) {
					setControls( obj );
				}
			} );

			this.canvas.renderAll();
			refreshLayersPanel();

			if ( this.canvasManager?.autoResizeCanvas ) {
				this.canvasManager.autoResizeCanvas();
			}

			this._lastSavedState = JSON.stringify( {
				canvas: this.canvas.toJSON( [ 'id', 'name', 'type_layer', 'cornerRadius' ] ),
				data: window.lpCertCanvasData
			} );

			this.saveCanvasLayers( false, TIMING.DEBOUNCE_SAVE, true );
		} catch ( error ) {
			console.error( 'Error during redo:', error );
		}

		this._isRestoring = false;
		this.updateHistoryButtons();
	}


	updateHistoryButtons() {
		const undoBtns = document.querySelectorAll( '.lp-btn-cert-undo' );
		const redoBtns = document.querySelectorAll( '.lp-btn-cert-redo' );

		undoBtns.forEach( ( btn ) => {
			btn.disabled = this._undoStack.length === 0;
		} );
		redoBtns.forEach( ( btn ) => {
			btn.disabled = this._redoStack.length === 0;
		} );
	}

	canUndo() {
		return this._undoStack.length > 0;
	}

	canRedo() {
		return this._redoStack.length > 0;
	}

	cleanup() {
		if ( this.saveTimeout ) {
			clearTimeout( this.saveTimeout );
			this.saveTimeout = null;
		}

		if ( this.historyTimeout ) {
			clearTimeout( this.historyTimeout );
			this.historyTimeout = null;
		}

		if ( this._keyboardMoveTimeout ) {
			clearTimeout( this._keyboardMoveTimeout );
			this._keyboardMoveTimeout = null;
		}

		this._clipboard = null;
		this._undoStack = [];
		this._redoStack = [];
		this._lastSavedState = null;
	}
}

