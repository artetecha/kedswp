import { selectors } from 'AssetsJsPath/backend/builder/selectors';
import { applyBackgroundOpacity } from 'AssetsJsPath/backend/builder/toolbar/canvas-toolbar/opacity';
import { initColorPicker } from 'AssetsJsPath/backend/builder/utils/color-picker';
import { TIMING } from 'AssetsJsPath/backend/builder/config';

let canvas = null;
let layerManager = null;
let bgColorPicker = null;

export function initColorBackground( canvasInstance, layerManagerInstance ) {
	canvas = canvasInstance;
	layerManager = layerManagerInstance;

	setupColorPicker();
	updateBackgroundColorActiveState();
}

export function setupColorPicker() {
	const colorInput = document.querySelector( selectors.elBackgroundColorInput );
	if ( ! colorInput ) {
		return;
	}

	let initialColor = colorInput.value || '#ffffff';

	if ( canvas ) {
		const canvasData = window.lpCertCanvasData || {};
		const backgroundColor = canvasData.background || canvas.get( 'backgroundColor' );
		if ( backgroundColor && ! /^(https?:\/\/|\/|data:image)/i.test( backgroundColor.trim() ) ) {
			initialColor = backgroundColor.toLowerCase();
		}
	}

	colorInput.style.display = 'none';

	const container = document.createElement( 'div' );
	container.className = 'iro-color-picker-container';
	colorInput.parentNode.insertBefore( container, colorInput.nextSibling );

	bgColorPicker = initColorPicker( container, {
		color: initialColor,
		mode: 'inline',
		width: 238,
		onChange: function( color ) {
			colorInput.value = color;
			setCanvasBackgroundColor( { e: null, target: colorInput } );
		},
	} );
}

export function updateBackgroundColorActiveState() {
	if ( ! canvas ) {
		return;
	}

	const canvasData = window.lpCertCanvasData || {};
	const backgroundColor = canvasData.background || canvas.get( 'backgroundColor' );

	if ( ! backgroundColor ) {
		return;
	}

	const isImageUrl = /^(https?:\/\/|\/|data:image)/i.test( backgroundColor.trim() );
	if ( isImageUrl ) {
		return;
	}

	const normalizedColor = backgroundColor.toLowerCase();

	const swatches = document.querySelectorAll( selectors.elBackgroundColorSwatch );
	swatches.forEach( swatch => {
		const swatchColor = ( swatch.dataset.color || '' ).toLowerCase();
		if ( swatchColor === normalizedColor ) {
			swatch.classList.add( 'active' );
		} else {
			swatch.classList.remove( 'active' );
		}
	} );

	const colorInput = document.querySelector( selectors.elBackgroundColorInput );
	if ( colorInput ) {
		colorInput.value = normalizedColor;
	}
	if ( bgColorPicker ) {
		bgColorPicker.setValue( normalizedColor );
	}
}

export function setCanvasBackgroundColor( args ) {
	const { e, target } = args;
	e?.preventDefault();
	e?.stopPropagation();

	if ( ! canvas ) {
		return;
	}

	let color = '';
	if ( target.matches( selectors.elBackgroundColorSwatch ) ) {
		color = target.dataset.color || '';
	} else if ( target.matches( selectors.elBackgroundColorInput ) || target.classList.contains( 'cert-color-option' ) ) {
		color = target.value || '';
	}

	if ( ! color ) {
		return;
	}

	if ( canvas.backgroundImage ) {
		canvas.backgroundImage = null;
	}

	canvas.set( 'backgroundColor', color );

	const canvasData = window.lpCertCanvasData || {};
	canvasData.background = color;
	canvasData.baseBackgroundColor = null;
	window.lpCertCanvasData = canvasData;

	applyBackgroundOpacity();

	const colorInput = document.querySelector( selectors.elBackgroundColorInput );
	if ( colorInput ) {
		colorInput.value = color;
	}
	if ( bgColorPicker ) {
		bgColorPicker.setValue( color );
	}

	if ( layerManager ) {
		layerManager.saveCanvasLayers( false, TIMING.DEBOUNCE_SAVE, false, TIMING.DEBOUNCE_HISTORY );
	}

	updateBackgroundColorActiveState();
}
