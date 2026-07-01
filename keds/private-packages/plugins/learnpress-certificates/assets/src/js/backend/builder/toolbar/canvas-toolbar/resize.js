import { setCanvasBackgroundImage } from 'AssetsJsPath/backend/builder/backgrounds';
import { TIMING } from 'AssetsJsPath/backend/builder/config';
import { applyBackgroundOpacity } from './opacity';
import { togglePopupInContainer, hideActivePopup } from '../popup-manager';

const SELECTORS = {
	popup: '.lp-cert-resize-popup',
	btn: '.lp-cert-canvas-toolbar__resize',
	apply: '.lp-cert-resize-popup__apply',
	preset: '.lp-cert-resize-popup__preset',
	widthInput: '.lp-cert-canvas-toolbar__resize-width',
	heightInput: '.lp-cert-canvas-toolbar__resize-height',
	presetInput: 'input[name="resize-preset"]',
};

let canvasManager = null;
let layerManager = null;
let popup = null;

function getCanvas() {
	return canvasManager?.canvas;
}

export function setResizeManagers( canvasManagerInstance, layerManagerInstance ) {
	canvasManager = canvasManagerInstance;
	layerManager = layerManagerInstance;
}

export function initResize( canvasManagerInstance, layerManagerInstance ) {
	setResizeManagers( canvasManagerInstance, layerManagerInstance );

	popup = document.querySelector( SELECTORS.popup );
	setupResizeEvents();
}

function setupResizeEvents() {
	const resizeBtn = document.querySelector( SELECTORS.btn );
	if ( resizeBtn ) {
		resizeBtn.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			if ( popup ) {
				populateCurrentDimensions();
				togglePopupInContainer( popup, resizeBtn );
			}
		} );
	}

	if ( ! popup ) return;

	const presets = popup.querySelectorAll( SELECTORS.presetInput );
	presets.forEach( ( radio ) => {
		radio.addEventListener( 'click', handlePresetClick );
		radio.addEventListener( 'change', handlePresetChange );
	} );

	const applyBtn = popup.querySelector( SELECTORS.apply );
	if ( applyBtn ) {
		applyBtn.addEventListener( 'click', applyResize );
	}

	const widthInput = popup.querySelector( SELECTORS.widthInput );
	const heightInput = popup.querySelector( SELECTORS.heightInput );
	[ widthInput, heightInput ].forEach( ( input ) => {
		if ( input ) {
			input.addEventListener( 'keydown', ( e ) => {
				if ( e.key === 'Enter' ) {
					e.preventDefault();
				}
			} );
		}
	} );

	const steppers = popup.querySelectorAll( '.lp-cert-resize-popup__stepper' );
	steppers.forEach( ( btn ) => {
		btn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			const target = btn.dataset.target;
			const input = target === 'width' ? widthInput : heightInput;
			if ( ! input || input.disabled ) return;

			const step = 1;
			let val = parseInt( input.value, 10 ) || 0;

			if ( btn.classList.contains( 'lp-cert-resize-popup__stepper--plus' ) ) {
				val = Math.min( val + step, 5000 );
			} else {
				val = Math.max( val - step, 100 );
			}

			input.value = val;
		} );
	} );
}

function resetPresets() {
	const presets = popup ? popup.querySelectorAll( SELECTORS.presetInput ) : [];
	presets.forEach( r => {
		r.checked = false;
		r.dataset.wasChecked = 'false';
	} );
}

function handlePresetClick( e ) {
	const radio = e.target;
	const presets = popup.querySelectorAll( SELECTORS.presetInput );

	if ( radio.dataset.wasChecked === 'true' ) {
		radio.checked = false;
		radio.dataset.wasChecked = 'false';
		handlePresetChange();
	} else {
		presets.forEach( r => r.dataset.wasChecked = 'false' );
		radio.dataset.wasChecked = 'true';
	}
}

function handlePresetChange() {
	const widthInput = popup.querySelector( SELECTORS.widthInput );
	const heightInput = popup.querySelector( SELECTORS.heightInput );

	if ( ! widthInput || ! heightInput ) return;

	const checked = popup.querySelector( SELECTORS.presetInput + ':checked' );

	if ( checked ) {
		const preset = checked.closest( SELECTORS.preset );
		if ( preset ) {
			const w = preset.dataset.width;
			const h = preset.dataset.height;
			if ( w && h ) {
				widthInput.value = w;
				widthInput.disabled = true;
				heightInput.value = h;
				heightInput.disabled = true;
			}
		}
	} else {
		widthInput.disabled = false;
		heightInput.disabled = false;
	}
}

function applyResize() {
	const widthInput = popup.querySelector( SELECTORS.widthInput );
	const heightInput = popup.querySelector( SELECTORS.heightInput );

	if ( ! widthInput || ! heightInput ) return;

	const width = parseInt( widthInput.value, 10 );
	const height = parseInt( heightInput.value, 10 );

	if ( ! width || width < 100 || width > 5000 ) return;
	if ( ! height || height < 100 || height > 5000 ) return;

	updateCanvasSize( width, height );
	hideActivePopup();
}

async function updateCanvasSize( width, height ) {
	const canvas = getCanvas();
	if ( ! canvas ) return;

	const canvasData = window.lpCertCanvasData || {};
	canvasData.width = width;
	canvasData.height = height;
	window.lpCertCanvasData = canvasData;

	canvas.setDimensions( { width, height } );

	const background = canvasData.background;
	if ( background ) {
		const isImageUrl = /^(https?:\/\/|\/|data:image)/i.test( background.trim() );
		if ( isImageUrl ) {
			await setCanvasBackgroundImage( background );
		} else {
			canvas.set( 'backgroundColor', background );
			applyBackgroundOpacity();
		}
	}

	canvas.renderAll();

	if ( canvasManager ) {
		canvasManager.autoResizeCanvas();
	}

	if ( layerManager ) {
		layerManager.saveCanvasLayers( false, TIMING.DEBOUNCE_SAVE );
	}
}

export function populateCurrentDimensions() {
	const canvas = getCanvas();
	if ( ! canvas ) return;

	if ( ! popup ) {
		popup = document.querySelector( SELECTORS.popup );
	}
	if ( ! popup ) return;

	const width = canvas.getWidth();
	const height = canvas.getHeight();

	const widthInput = popup.querySelector( SELECTORS.widthInput );
	const heightInput = popup.querySelector( SELECTORS.heightInput );

	if ( widthInput ) widthInput.value = width;
	if ( heightInput ) heightInput.value = height;

	const presets = popup.querySelectorAll( SELECTORS.preset );
	let matched = false;

	presets.forEach( preset => {
		const w = parseInt( preset.dataset.width );
		const h = parseInt( preset.dataset.height );
		const radio = preset.querySelector( 'input[type="radio"]' );

		if ( w === width && h === height ) {
			if ( radio ) {
				radio.checked = true;
				radio.dataset.wasChecked = 'true';
			}
			matched = true;
		} else if ( radio ) {
			radio.checked = false;
			radio.dataset.wasChecked = 'false';
		}
	} );

	if ( matched ) {
		if ( widthInput ) widthInput.disabled = true;
		if ( heightInput ) heightInput.disabled = true;
	} else {
		if ( widthInput ) widthInput.disabled = false;
		if ( heightInput ) heightInput.disabled = false;
	}
}
