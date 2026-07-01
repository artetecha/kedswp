import { isTextType, isImageType, isSvgType } from 'AssetsJsPath/backend/builder/elements';
import {
	initTextToolbar,
	showTextToolbar,
	hideTextToolbar
} from './text-toolbar';
import {
	initImageToolbar,
	showImageToolbar,
	hideImageToolbar
} from './image-toolbar';
import {
	initSvgToolbar,
	showSvgToolbar,
	hideSvgToolbar
} from './svg-toolbar';
import {
	initCanvasToolbar,
	showCanvasToolbar,
	hideCanvasToolbar
} from './canvas-toolbar';
import {
	initPositionPanel,
	updatePositionPanelValues,
	updatePositionPanelForObject,
} from './position-panel';
import { updateTextboxWidthInput } from './text-toolbar';
import { initPopupManager } from './popup-manager';
import { initTooltip } from './tooltip';

export const TOOLBAR_SELECTORS = {
	canvas: '.lp-cert-canvas-toolbar',
	text: '.lp-cert-text-toolbar',
	image: '.lp-cert-image-toolbar',
	svg: '.lp-cert-svg-toolbar',
};

let canvas = null;
let layerManager = null;
let isInitialized = false;

export function initToolbarManager( canvasManager, layerManagerInstance ) {
	if ( ! canvasManager ) {
		console.error( 'Canvas is required to initialize toolbar manager' );
		return;
	}

	canvas = canvasManager.canvas;
	layerManager = layerManagerInstance;

	initPopupManager();
	initTooltip();
	initPositionPanel( canvas, layerManager );
	initTextToolbar( canvas, layerManager );
	initImageToolbar( canvas, layerManager );
	initSvgToolbar( canvas, layerManager );
	initCanvasToolbar( canvasManager, layerManager );
	preventFormSubmitOnEnter();

	isInitialized = true;

	if (!canvas.getActiveObject()) {
		showCanvasToolbar();
	}
}

function preventFormSubmitOnEnter() {
	Object.values( TOOLBAR_SELECTORS ).forEach( ( selector ) => {
		const toolbar = document.querySelector( selector );
		if ( toolbar ) {
			toolbar.addEventListener( 'keydown', ( e ) => {
				if ( e.key === 'Enter' && e.target.tagName === 'INPUT' ) {
					e.preventDefault();
					e.target.blur();
				}
			} );
		}
	} );
}

export function handleSelectionChange( canvasInstance ) {
	const activeObject = canvasInstance.getActiveObject();

	if ( ! activeObject ) {
		hideAllToolbars();
		showCanvasToolbar();
		return;
	}

	const customType = activeObject.get( 'type_layer' );

	if ( ! customType ) {
		hideAllToolbars();
		showCanvasToolbar();
		return;
	}

	dispatchToolbar( customType, activeObject );
}

export function handleSelectionCleared() {
	hideAllToolbars();
	showCanvasToolbar();
}

function dispatchToolbar( customType, activeObject ) {
	hideAllToolbars();

	if ( isTextType( customType ) ) {
		showTextToolbar( activeObject );
	} else if ( isImageType( customType ) ) {
		showImageToolbar( activeObject );
	} else if ( isSvgType( customType ) ) {
		showSvgToolbar( activeObject );
	}

	updatePositionPanelForObject( activeObject );
}

export function hideAllToolbars() {
	hideTextToolbar();
	hideImageToolbar();
	hideSvgToolbar();
	hideCanvasToolbar();
}

export { updateTextboxWidthInput, updatePositionPanelValues };

export function refreshToolbar() {
	if ( ! canvas ) {
		return;
	}

	const activeObject = canvas.getActiveObject();
	if ( ! activeObject ) {
		hideAllToolbars();
		return;
	}

	const customType = activeObject.get( 'type_layer' );

	if ( customType ) {
		dispatchToolbar( customType, activeObject );
	}
}

export function isToolbarManagerInitialized() {
	return isInitialized;
}
