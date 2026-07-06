import { TIMING } from 'AssetsJsPath/backend/builder/config';
import { getActivePopup } from './popup-manager';

const SELECTORS = {
	panel: '.lp-cert-position-panel',
	posX: '.lp-cert-position-panel__pos-x',
	posY: '.lp-cert-position-panel__pos-y',
};

let canvas = null;
let layerManager = null;
let activeObject = null;
let isInitialized = false;
let isPopulating = false;

export function initPositionPanel( canvasInstance, layerManagerInstance ) {
	canvas = canvasInstance;
	layerManager = layerManagerInstance;

	if ( isInitialized ) {
		return;
	}

	setupEventListeners();
	isInitialized = true;
}

export function preparePositionPanel( fabricObject ) {
	activeObject = fabricObject;
	populatePanelValues( fabricObject );
}

export function updatePositionPanelValues( obj ) {
	if ( ! obj || obj !== activeObject ) {
		return;
	}

	const posXInput = document.querySelector( SELECTORS.posX );
	const posYInput = document.querySelector( SELECTORS.posY );

	if ( posXInput ) {
		posXInput.value = Math.round( obj.left || 0 );
	}
	if ( posYInput ) {
		posYInput.value = Math.round( obj.top || 0 );
	}
}

export function updatePositionPanelForObject( fabricObject ) {
	const panel = document.querySelector( SELECTORS.panel );
	if ( ! panel || getActivePopup() !== panel ) {
		return;
	}

	activeObject = fabricObject;
	populatePanelValues( fabricObject );
}

function populatePanelValues( obj ) {
	if ( ! obj ) {
		return;
	}

	isPopulating = true;

	const posXInput = document.querySelector( SELECTORS.posX );
	const posYInput = document.querySelector( SELECTORS.posY );

	if ( posXInput ) {
		posXInput.value = Math.round( obj.left || 0 );
	}
	if ( posYInput ) {
		posYInput.value = Math.round( obj.top || 0 );
	}

	isPopulating = false;
}

function setupEventListeners() {
	const posXInput = document.querySelector( SELECTORS.posX );
	if ( posXInput ) {
		posXInput.addEventListener( 'change', ( e ) => {
			if ( isPopulating || ! activeObject || ! canvas ) {
				return;
			}
			const x = parseInt( e.target.value, 10 ) || 0;
			activeObject.set( 'left', x );
			activeObject.setCoords();
			canvas.requestRenderAll();
			saveLayerData();
		} );
	}

	const posYInput = document.querySelector( SELECTORS.posY );
	if ( posYInput ) {
		posYInput.addEventListener( 'change', ( e ) => {
			if ( isPopulating || ! activeObject || ! canvas ) {
				return;
			}
			const y = parseInt( e.target.value, 10 ) || 0;
			activeObject.set( 'top', y );
			activeObject.setCoords();
			canvas.requestRenderAll();
			saveLayerData();
		} );
	}

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
