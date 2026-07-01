import { Rect } from 'fabric';
import { TIMING } from 'AssetsJsPath/backend/builder/config';
import { preparePositionPanel } from './position-panel';
import { initSliderProgress, updateSliderProgress } from 'AssetsJsPath/backend/utils/slider-progress';
import { togglePopupInContainer, hideActivePopup } from './popup-manager';
import { setCanvasBackgroundImage } from 'AssetsJsPath/backend/builder/backgrounds';

const SELECTORS = {
	toolbar: '.lp-cert-image-toolbar',
	trigger: '.lp-cert-image-toolbar__trigger',
	popup: '.lp-cert-image-toolbar__popup',
	flipX: '.lp-cert-image-toolbar__flip-x',
	flipY: '.lp-cert-image-toolbar__flip-y',
	cornerRadiusSlider: '.lp-cert-image-toolbar__corner-radius-slider',
	cornerRadius: '.lp-cert-image-toolbar__corner-radius',
	opacity: '.lp-cert-image-toolbar__opacity',
	opacityValue: '.lp-cert-image-toolbar__opacity-value',
	positionToggle: '.lp-cert-image-toolbar__position-toggle',
	resizeFit: '.lp-cert-image-toolbar__resize-fit',
	resizeMini: '.lp-cert-image-toolbar__resize-mini',
	resizeOriginal: '.lp-cert-image-toolbar__resize-original',
	setAsBackground: '.lp-cert-image-toolbar__set-as-bg',
};

let canvas = null;
let layerManager = null;
let activeObject = null;
let isInitialized = false;
let isPopulating = false;

export function initImageToolbar( canvasInstance, layerManagerInstance ) {
	canvas = canvasInstance;
	layerManager = layerManagerInstance;

	if ( isInitialized ) {
		return;
	}

	setupEventListeners();
	isInitialized = true;
}

export function showImageToolbar( fabricObject ) {
	const toolbar = document.querySelector( SELECTORS.toolbar );
	if ( ! toolbar ) {
		return;
	}

	activeObject = fabricObject;
	toolbar.classList.add( 'is-visible' );

	populateToolbarValues( fabricObject );
}

export function hideImageToolbar() {
	const toolbar = document.querySelector( SELECTORS.toolbar );
	if ( toolbar ) {
		toolbar.classList.remove( 'is-visible' );
	}
	hideActivePopup();
	activeObject = null;
}

function populateToolbarValues( obj ) {
	if ( ! obj ) {
		return;
	}

	isPopulating = true;

	const cornerRadiusSlider = document.querySelector( SELECTORS.cornerRadiusSlider );
	const cornerRadiusInput = document.querySelector( SELECTORS.cornerRadius );
	const radius = obj.get( 'cornerRadius' ) || ( obj.clipPath ? obj.clipPath.get( 'rx' ) : 0 ) || 0;
	const radiusValue = Math.min( 100, Math.round( radius ) );
	if ( cornerRadiusSlider ) {
		cornerRadiusSlider.value = radiusValue;
		updateSliderProgress( cornerRadiusSlider );
	}
	if ( cornerRadiusInput ) {
		cornerRadiusInput.value = radiusValue;
	}

	const opacitySlider = document.querySelector( SELECTORS.opacity );
	const opacityValue = document.querySelector( SELECTORS.opacityValue );
	if ( opacitySlider ) {
		const opacity = Math.round( ( obj.opacity || 1 ) * 100 );
		opacitySlider.value = opacity;
		updateSliderProgress( opacitySlider );
		if ( opacityValue ) {
			opacityValue.value = opacity;
		}
	}

	isPopulating = false;
}

function setupEventListeners() {
	const triggers = document.querySelectorAll( SELECTORS.trigger );
	triggers.forEach( ( trigger ) => {
		trigger.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			const popupId = trigger.getAttribute( 'data-popup' );
			const toolbar = document.querySelector( SELECTORS.toolbar );
			if ( toolbar ) {
				const popup = toolbar.querySelector( `[data-popup-id="${ popupId }"]` );
				if ( popup ) {
					togglePopupInContainer( popup, trigger );
				}
			}
		} );
	} );

	const flipXBtn = document.querySelector( SELECTORS.flipX );
	if ( flipXBtn ) {
		flipXBtn.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			if ( ! activeObject ) {
				return;
			}
			applyImageProperty( 'flipX', ! activeObject.flipX );
		} );
	}

	const flipYBtn = document.querySelector( SELECTORS.flipY );
	if ( flipYBtn ) {
		flipYBtn.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			if ( ! activeObject ) {
				return;
			}
			applyImageProperty( 'flipY', ! activeObject.flipY );
		} );
	}

	const cornerRadiusSlider = document.querySelector( SELECTORS.cornerRadiusSlider );
	const cornerRadiusInput = document.querySelector( SELECTORS.cornerRadius );
	if ( cornerRadiusSlider ) {
		initSliderProgress( cornerRadiusSlider );
		cornerRadiusSlider.addEventListener( 'input', ( e ) => {
			const value = parseInt( e.target.value, 10 );
			if ( cornerRadiusInput ) {
				cornerRadiusInput.value = value;
			}
			applyCornerRadius( value );
		} );
	}

	if ( cornerRadiusInput ) {
		cornerRadiusInput.addEventListener( 'change', ( e ) => {
			let value = parseInt( e.target.value, 10 ) || 0;
			if ( value < 0 ) value = 0;
			if ( value > 100 ) value = 100;
			e.target.value = value;

			if ( cornerRadiusSlider ) {
				cornerRadiusSlider.value = value;
				updateSliderProgress( cornerRadiusSlider );
			}
			applyCornerRadius( value );
		} );
	}

	const opacitySlider = document.querySelector( SELECTORS.opacity );
	const opacityValue = document.querySelector( SELECTORS.opacityValue );
	if ( opacitySlider ) {
		initSliderProgress( opacitySlider );
		opacitySlider.addEventListener( 'input', ( e ) => {
			const value = parseInt( e.target.value, 10 );
			if ( opacityValue ) {
				opacityValue.value = value;
			}
			applyImageProperty( 'opacity', value / 100 );
		} );
	}

	if ( opacityValue ) {
		opacityValue.addEventListener( 'change', ( e ) => {
			let value = parseInt( e.target.value, 10 );
			if ( isNaN( value ) ) value = 100;
			if ( value < 0 ) value = 0;
			if ( value > 100 ) value = 100;
			e.target.value = value;

			if ( opacitySlider ) {
				opacitySlider.value = value;
				updateSliderProgress( opacitySlider );
			}
			applyImageProperty( 'opacity', value / 100 );
		} );
	}

	const positionToggle = document.querySelector( SELECTORS.positionToggle );
	if ( positionToggle ) {
		positionToggle.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			if ( activeObject ) {
				const panel = document.querySelector( '.lp-cert-position-panel' );
				preparePositionPanel( activeObject );
				togglePopupInContainer( panel, positionToggle );
			}
		} );
	}

	const resizeFitBtn = document.querySelector( SELECTORS.resizeFit );
	if ( resizeFitBtn ) {
		resizeFitBtn.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			applyResizeFit();
		} );
	}

	const resizeMiniBtn = document.querySelector( SELECTORS.resizeMini );
	if ( resizeMiniBtn ) {
		resizeMiniBtn.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			applyResizeMini();
		} );
	}

	const resizeOriginalBtn = document.querySelector( SELECTORS.resizeOriginal );
	if ( resizeOriginalBtn ) {
		resizeOriginalBtn.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			applyResizeOriginal();
		} );
	}

	const setAsBackgroundBtn = document.querySelector( SELECTORS.setAsBackground );
	if ( setAsBackgroundBtn ) {
		setAsBackgroundBtn.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			applySetAsBackground();
		} );
	}
}

function applyCornerRadius( radius ) {
	if ( ! activeObject || ! canvas ) {
		return;
	}

	activeObject.set( 'cornerRadius', radius );

	if ( radius <= 0 ) {
		activeObject.set( 'clipPath', null );
	} else {
		const clipRect = new Rect( {
			width: activeObject.width,
			height: activeObject.height,
			rx: radius,
			ry: radius,
			left: -activeObject.width / 2,
			top: -activeObject.height / 2,
			originX: 'left',
			originY: 'top',
			absolutePositioned: false,
		} );

		activeObject.set( 'clipPath', clipRect );
	}

	activeObject.set( 'dirty', true );
	activeObject.setCoords();
	canvas.requestRenderAll();
	saveLayerData();
}

function applyImageProperty( property, value ) {
	if ( ! activeObject || ! canvas ) {
		return;
	}

	activeObject.set( property, value );
	activeObject.setCoords();
	canvas.requestRenderAll();
	saveLayerData();
}

function applyResizeFit() {
	if ( ! activeObject || ! canvas ) {
		return;
	}

	const canvasWidth = canvas.getWidth();
	const canvasHeight = canvas.getHeight();
	const scale = Math.min( canvasWidth / activeObject.width, canvasHeight / activeObject.height );

	activeObject.set( {
		scaleX: scale,
		scaleY: scale,
		left: canvasWidth / 2,
		top: canvasHeight / 2,
		originX: 'center',
		originY: 'center',
	} );
	activeObject.setCoords();
	canvas.requestRenderAll();
	saveLayerData();
}

function applyResizeMini() {
	if ( ! activeObject || ! canvas ) {
		return;
	}

	const canvasWidth = canvas.getWidth();
	const canvasHeight = canvas.getHeight();
	const scale = Math.min( ( canvasWidth * 0.2 ) / activeObject.width, ( canvasHeight * 0.2 ) / activeObject.height );

	activeObject.set( {
		scaleX: scale,
		scaleY: scale,
		left: canvasWidth / 2,
		top: canvasHeight / 2,
		originX: 'center',
		originY: 'center',
	} );
	activeObject.setCoords();
	canvas.requestRenderAll();
	saveLayerData();
}

function applyResizeOriginal() {
	if ( ! activeObject || ! canvas ) {
		return;
	}

	const canvasWidth = canvas.getWidth();
	const canvasHeight = canvas.getHeight();

	activeObject.set( {
		scaleX: 1,
		scaleY: 1,
		left: canvasWidth / 2,
		top: canvasHeight / 2,
		originX: 'center',
		originY: 'center',
	} );
	activeObject.setCoords();
	canvas.requestRenderAll();
	saveLayerData();
}

async function applySetAsBackground() {
	if ( ! activeObject || ! canvas || ! layerManager ) {
		return;
	}

	const src = activeObject.getSrc?.() || activeObject.get( 'src' ) || '';
	if ( ! src ) {
		return;
	}

	const layerId = activeObject.get( 'id' );

	await setCanvasBackgroundImage( src, true );

	if ( layerId ) {
		layerManager.deleteLayer( layerId, activeObject, true );
	}

	layerManager.saveCanvasLayers( false );
}

function saveLayerData() {
	if ( isPopulating || ! activeObject || ! layerManager ) {
		return;
	}

	const layerId = activeObject.get( 'id' );
	if ( ! layerId ) {
		return;
	}
	layerManager.updateLayerDataFromObject( layerId, activeObject );
	layerManager.saveCanvasLayers( false, TIMING.DEBOUNCE_SAVE, false, TIMING.DEBOUNCE_HISTORY );
}
