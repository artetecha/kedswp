import { initResize } from './canvas-toolbar/resize';
import { initBackground } from './canvas-toolbar/background';
import { initOpacity } from './canvas-toolbar/opacity';
import { initLayers } from './canvas-toolbar/layers';
import { initPriorityNav, refreshPriorityNav } from './priority-nav';

const SELECTOR = '.lp-cert-canvas-toolbar';

let isInitialized = false;

export function initCanvasToolbar( canvasManagerInstance, layerManagerInstance ) {
	if ( isInitialized ) {
		return;
	}

	initResize( canvasManagerInstance, layerManagerInstance );
	initBackground();
	initOpacity( canvasManagerInstance, layerManagerInstance );
	initLayers();
	initPriorityNav();
	isInitialized = true;
}

export function showCanvasToolbar() {
	const toolbar = document.querySelector( SELECTOR );
	if ( toolbar ) {
		toolbar.classList.add( 'is-visible' );
		refreshPriorityNav();
	}
}

export function hideCanvasToolbar() {
	const toolbar = document.querySelector( SELECTOR );
	if ( toolbar ) {
		toolbar.classList.remove( 'is-visible' );
	}
}
