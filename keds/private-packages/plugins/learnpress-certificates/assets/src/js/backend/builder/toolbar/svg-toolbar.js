import { TIMING } from 'AssetsJsPath/backend/builder/config';
import { preparePositionPanel } from './position-panel';
import { initSliderProgress, updateSliderProgress } from 'AssetsJsPath/backend/utils/slider-progress';
import { togglePopupInContainer, hideActivePopup } from './popup-manager';
import { initColorPicker as createColorPicker } from 'AssetsJsPath/backend/builder/utils/color-picker';

const SELECTORS = {
	toolbar: '.lp-cert-svg-toolbar',
	trigger: '.lp-cert-svg-toolbar__trigger',
	popup: '.lp-cert-svg-toolbar__popup',
	fillTrigger: '.lp-cert-svg-toolbar__fill-trigger',
	fillPreview: '.lp-cert-svg-toolbar__fill-preview',
	fillColor: '.lp-cert-svg-toolbar__fill-color',
	fillColorPreset: '.lp-cert-svg-toolbar__color-preset',
	strokeColor: '.lp-cert-svg-toolbar__stroke-color',
	strokeWidth: '.lp-cert-svg-toolbar__stroke-width',
	opacity: '.lp-cert-svg-toolbar__opacity',
	opacityValue: '.lp-cert-svg-toolbar__opacity-value',
	positionToggle: '.lp-cert-svg-toolbar__position-toggle',
};

let canvas = null;
let layerManager = null;
let activeObject = null;
let isInitialized = false;
let fillColorPickerInitialized = false;
let strokeColorPickerInitialized = false;
let fillIroPicker = null;
let strokeIroPicker = null;
let isPopulating = false;

export function initSvgToolbar( canvasInstance, layerManagerInstance ) {
	canvas = canvasInstance;
	layerManager = layerManagerInstance;

	if ( isInitialized ) {
		return;
	}

	setupEventListeners();
	isInitialized = true;
}

export function showSvgToolbar( fabricObject ) {
	const toolbar = document.querySelector( SELECTORS.toolbar );
	if ( ! toolbar ) {
		return;
	}

	activeObject = fabricObject;
	toolbar.classList.add( 'is-visible' );

	populateToolbarValues( fabricObject );
	initColorPickers();
}

export function hideSvgToolbar() {
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

	// Fill Color (for Line, use stroke as the main color)
	const isLine = obj.type === 'line';
	const fillPreview = document.querySelector( SELECTORS.fillPreview );
	const fillColorInput = document.querySelector( SELECTORS.fillColor );
	const fillColor = isLine ? ( obj.stroke || '' ) : ( obj.fill || 'transparent' );
	if ( fillPreview ) {
		updateFillPreview( fillColor );
	}
	if ( fillColorInput ) {
		fillColorInput.value = fillColor === 'transparent' ? '' : fillColor;
		updateIroColorPicker( 'fill', fillColor );
	}

	// Stroke Color
	const strokeColorInput = document.querySelector( SELECTORS.strokeColor );
	const strokeColor = obj.stroke || '';
	if ( strokeColorInput ) {
		strokeColorInput.value = strokeColor || '';
		updateIroColorPicker( 'stroke', strokeColor );
	}

	// Stroke Width
	const strokeWidthInput = document.querySelector( SELECTORS.strokeWidth );
	if ( strokeWidthInput ) {
		strokeWidthInput.value = obj.strokeWidth || 0;
	}

	// Opacity
	const opacitySlider = document.querySelector( SELECTORS.opacity );
	const opacityValue = document.querySelector( SELECTORS.opacityValue );
	const opacity = Math.round( ( obj.opacity || 1 ) * 100 );
	if ( opacitySlider ) {
		opacitySlider.value = opacity;
		updateSliderProgress( opacitySlider );
	}
	if ( opacityValue ) {
		opacityValue.value = opacity;
	}

	isPopulating = false;
}

function updateIroColorPicker( type, color ) {
	if ( type === 'fill' && fillIroPicker ) {
		fillIroPicker.setValue( color || '#000000' );
	} else if ( type === 'stroke' && strokeIroPicker ) {
		strokeIroPicker.setValue( color || '#000000' );
	}
}

function initColorPickers() {
	initFillColorPicker();
	initStrokeColorPicker();
}

function initFillColorPicker() {
	if ( fillColorPickerInitialized ) {
		return;
	}

	const colorInput = document.querySelector( SELECTORS.fillColor );
	if ( ! colorInput ) {
		return;
	}

	colorInput.style.display = 'none';

	const container = document.createElement( 'div' );
	container.className = 'iro-color-picker-container';
	colorInput.parentNode.insertBefore( container, colorInput.nextSibling );

	fillIroPicker = createColorPicker( container, {
		color: colorInput.value || '#000000',
		mode: 'inline',
		width: 200,
		onChange: function( color ) {
			applyFillColor( color );
			colorInput.value = color;
		},
	} );

	fillColorPickerInitialized = true;
}

function initStrokeColorPicker() {
	if ( strokeColorPickerInitialized ) {
		return;
	}

	const colorInput = document.querySelector( SELECTORS.strokeColor );
	if ( ! colorInput ) {
		return;
	}

	colorInput.style.display = 'none';

	const container = document.createElement( 'div' );
	container.className = 'iro-color-picker-container';
	colorInput.parentNode.insertBefore( container, colorInput.nextSibling );

	strokeIroPicker = createColorPicker( container, {
		color: colorInput.value || '#000000',
		mode: 'inline',
		width: 160,
		onChange: function( color ) {
			applyStrokeColor( color );
			colorInput.value = color;
		},
	} );

	strokeColorPickerInitialized = true;
}

function setupEventListeners() {
	const toolbar = document.querySelector( SELECTORS.toolbar );
	if ( ! toolbar ) {
		return;
	}

	const triggers = toolbar.querySelectorAll( SELECTORS.trigger );
	triggers.forEach( ( trigger ) => {
		trigger.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			const popupId = trigger.getAttribute( 'data-popup' );
			const popup = toolbar.querySelector( `[data-popup-id="${ popupId }"]` );
			if ( popup ) {
				togglePopupInContainer( popup, trigger );
			}
		} );
	} );

	const fillTrigger = document.querySelector( SELECTORS.fillTrigger );
	if ( fillTrigger ) {
		fillTrigger.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			const popupId = fillTrigger.getAttribute( 'data-popup' );
			const popup = toolbar.querySelector( `[data-popup-id="${ popupId }"]` );
			if ( popup ) {
				togglePopupInContainer( popup, fillTrigger );
			}
		} );
	}

	const fillPresets = document.querySelectorAll( SELECTORS.fillColorPreset );
	fillPresets.forEach( ( preset ) => {
		preset.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			const color = preset.getAttribute( 'data-color' );
			applyFillColor( color );
			updateFillColorInput( color );
		} );
	} );

	// Stroke Width
	const strokeWidthInput = document.querySelector( SELECTORS.strokeWidth );
	if ( strokeWidthInput ) {
		strokeWidthInput.addEventListener( 'change', ( e ) => {
			const width = parseInt( e.target.value, 10 ) || 0;
			applySvgProperty( 'strokeWidth', width );
		} );
	}

	// Opacity Slider
	const opacitySlider = document.querySelector( SELECTORS.opacity );
	const opacityValue = document.querySelector( SELECTORS.opacityValue );
	if ( opacitySlider ) {
		initSliderProgress( opacitySlider );
		opacitySlider.addEventListener( 'input', ( e ) => {
			const value = parseInt( e.target.value, 10 );
			if ( opacityValue ) {
				opacityValue.value = value;
			}
			applySvgProperty( 'opacity', value / 100 );
		} );
	}

	// Opacity Value Input
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
			applySvgProperty( 'opacity', value / 100 );
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
}

function applyFillColor( color ) {
	if ( ! activeObject || ! canvas ) {
		return;
	}

	if ( activeObject.type === 'line' ) {
		activeObject.set( 'stroke', color || null );
	} else {
		activeObject.set( 'fill', color );
	}
	activeObject.set( 'dirty', true );
	canvas.requestRenderAll();
	saveLayerData();
	updateFillPreview( color );
}

function updateFillPreview( color ) {
	const fillPreview = document.querySelector( SELECTORS.fillPreview );
	if ( ! fillPreview ) {
		return;
	}

	if ( color === 'transparent' || ! color ) {
		fillPreview.style.background = 'linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%)';
		fillPreview.style.backgroundSize = '8px 8px';
		fillPreview.style.backgroundPosition = '0 0, 0 4px, 4px -4px, -4px 0px';
	} else {
		fillPreview.style.background = color;
		fillPreview.style.backgroundSize = '';
		fillPreview.style.backgroundPosition = '';
	}
}

function applyStrokeColor( color ) {
	if ( ! activeObject || ! canvas ) {
		return;
	}

	activeObject.set( 'stroke', color || null );
	activeObject.set( 'dirty', true );
	canvas.requestRenderAll();
	saveLayerData();
}

function updateFillColorInput( color ) {
	const fillColorInput = document.querySelector( SELECTORS.fillColor );
	if ( fillColorInput ) {
		fillColorInput.value = color === 'transparent' ? '' : color;
	}
	if ( fillIroPicker && color && color !== 'transparent' ) {
		fillIroPicker.setValue( color );
	}
}

function applySvgProperty( property, value ) {
	if ( ! activeObject || ! canvas ) {
		return;
	}

	activeObject.set( property, value );
	activeObject.setCoords();
	canvas.requestRenderAll();
	saveLayerData();
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
