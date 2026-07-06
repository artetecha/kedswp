import { selectors } from './selectors';
import { blockDefaultContextMenu, convertTitlesToTooltips } from './utils';
import { CANVAS_DEFAULTS } from './config';
import { FullscreenMode, CanvasManager, ContextMenu, HotkeyManager } from './core';
import { LayerManager, setupLayersPanel } from './layers';
import { setupEvents } from './events';
import { initBackgrounds } from './backgrounds';
import { initToolbarManager } from './toolbar';
import { initShapesLibrary, initLibraryImages } from './library';
import { initPreview } from './preview';

export class EditBuilder {
	constructor() {
		this.certificateId = 0;
		this.canvasManager = null;
		this.layerManager = null;
		this.fullscreen = null;
		this.contextMenu = null;
		this.hotkeyManager = null;
		this.wpMediaFrame = null;
	}

	static selectors = selectors;

	async init( certificateId ) {
		this.certificateId = certificateId;
		setupEvents( this );
		blockDefaultContextMenu();
		convertTitlesToTooltips();

		this.fullscreen = new FullscreenMode();
		this.fullscreen.init();

		if ( ! window.lpCertCanvasData ) {
			window.lpCertCanvasData = {
				certificate_id: certificateId,
				width: CANVAS_DEFAULTS.WIDTH,
				height: CANVAS_DEFAULTS.HEIGHT,
				background: CANVAS_DEFAULTS.BACKGROUND,
				layers: [],
			};
		}

		await this.initCanvas();
	}

	async initCanvas() {
		this.canvasManager = new CanvasManager( this.certificateId );
		this.layerManager = new LayerManager( this.canvasManager );

		this.contextMenu = new ContextMenu( this.layerManager );

		await this.canvasManager.init( this.layerManager, this.contextMenu );

		if ( this.canvasManager.canvas ) {
			this.hotkeyManager = new HotkeyManager( this.canvasManager.canvas, this.layerManager );
			initBackgrounds( this.canvasManager.canvas, this.layerManager );
			initToolbarManager( this.canvasManager, this.layerManager );
			initShapesLibrary( this.canvasManager.canvas, this.layerManager );
			initLibraryImages( this.canvasManager.canvas, this.layerManager );
			setupLayersPanel( this );
			initPreview( this.canvasManager.canvas );
		}
	}
}
