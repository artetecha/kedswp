import { TIMING, CANVAS_DEFAULTS } from 'AssetsJsPath/backend/builder/config';
import { initSliderProgress, updateSliderProgress } from 'AssetsJsPath/backend/utils/slider-progress';
import { togglePopupInContainer } from '../popup-manager';

const SELECTORS = {
	btn: '.lp-cert-canvas-toolbar__bg-opacity',
	popup: '.lp-cert-opacity-popup',
	slider: '.lp-cert-opacity-popup__slider',
	input: '.lp-cert-opacity-popup__input',
};

let canvasManager = null;
let layerManager = null;
let popup = null;

function getCanvas() {
	return canvasManager?.canvas;
}

export function setOpacityManagers( canvasManagerInstance, layerManagerInstance ) {
	canvasManager = canvasManagerInstance;
	layerManager = layerManagerInstance;
}

export function initOpacity( canvasManagerInstance, layerManagerInstance ) {
	setOpacityManagers( canvasManagerInstance, layerManagerInstance );

	popup = document.querySelector( SELECTORS.popup );
	setupOpacityEvents();
	applyBackgroundOpacity();
}

function setupOpacityEvents() {
	const opacityBtn = document.querySelector( SELECTORS.btn );
	if ( opacityBtn ) {
		opacityBtn.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			if ( popup ) {
				populateCurrentOpacity();
				togglePopupInContainer( popup, opacityBtn );
			}
		} );
	}

	if ( ! popup ) return;

	const slider = popup.querySelector( SELECTORS.slider );
	const input = popup.querySelector( SELECTORS.input );

	if ( slider ) {
		initSliderProgress( slider );
		slider.addEventListener( 'input', ( e ) => {
			const value = e.target.value;
			if ( input ) input.value = value;
			updateBackgroundOpacity( value );
		} );
	}

	if ( input ) {
		input.addEventListener( 'input', ( e ) => {
			let value = parseInt( e.target.value, 10 );
			if ( isNaN( value ) ) value = 100;
			if ( value < 0 ) value = 0;
			if ( value > 100 ) value = 100;

			if ( slider ) {
				slider.value = value;
				updateSliderProgress( slider );
			}
			updateBackgroundOpacity( value );
		} );

		input.addEventListener('blur', (e) => {
			let value = parseInt( e.target.value, 10 );
			if ( isNaN( value ) ) value = 100;
			if ( value < 0 ) value = 0;
			if ( value > 100 ) value = 100;
			e.target.value = value;
		});
	}
}

function populateCurrentOpacity() {
	const canvasData = window.lpCertCanvasData || {};
	const opacity = canvasData.backgroundOpacity ?? 100;

	const slider = popup.querySelector( SELECTORS.slider );
	const input = popup.querySelector( SELECTORS.input );

	if ( slider ) {
		slider.value = opacity;
		updateSliderProgress( slider );
	}
	if ( input ) input.value = opacity;
}

function hexToRgba( hex, alpha ) {
	if ( ! hex ) return null;

	let normalizedHex = hex.replace( /^#/, '' );

	if ( normalizedHex.length === 3 ) {
		normalizedHex = normalizedHex.split( '' ).map( c => c + c ).join( '' );
	}

	const result = /^([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec( normalizedHex );
	if ( ! result ) return null;

	const r = parseInt( result[1], 16 );
	const g = parseInt( result[2], 16 );
	const b = parseInt( result[3], 16 );

	return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function getBaseColor( color ) {
	if ( ! color ) return null;
	if ( color.startsWith( '#' ) ) return color;
	return color;
}

function updateBackgroundOpacity( value ) {
	const canvas = getCanvas();
	if ( ! canvas ) return;

	const opacity = parseInt( value, 10 ) / 100;

	const canvasData = window.lpCertCanvasData || {};
	canvasData.backgroundOpacity = parseInt( value, 10 );
	window.lpCertCanvasData = canvasData;

	const bgImage = canvas.backgroundImage;
	if ( bgImage ) {
		bgImage.set( 'opacity', opacity );
	}
	
	let originalHexColor = canvasData.background || CANVAS_DEFAULTS.BACKGROUND;
	if ( originalHexColor && ! bgImage ) {
		const rgbaColor = hexToRgba( originalHexColor, opacity );
		if ( rgbaColor ) {
			canvas.set( 'backgroundColor', rgbaColor );
		}
	}

	canvas.requestRenderAll();

	if ( layerManager ) {
		layerManager.saveCanvasLayers( false, TIMING.DEBOUNCE_SAVE, false, TIMING.DEBOUNCE_HISTORY );
	}
}

export function applyBackgroundOpacity() {
	const canvas = getCanvas();
	if ( ! canvas ) return;

	const canvasData = window.lpCertCanvasData || {};
	const opacity = ( canvasData.backgroundOpacity ?? 100 ) / 100;

	const bgImage = canvas.backgroundImage;
	if ( bgImage ) {
		bgImage.set( 'opacity', opacity );
	}
	let originalHexColor = canvasData.background || CANVAS_DEFAULTS.BACKGROUND;

	if ( originalHexColor && ! bgImage ) {
		const rgbaColor = hexToRgba( originalHexColor, opacity );
		if ( rgbaColor ) {
			canvas.set( 'backgroundColor', rgbaColor );
		}
	}

	canvas.requestRenderAll();
}