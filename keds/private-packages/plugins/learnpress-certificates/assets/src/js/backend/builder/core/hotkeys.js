import { selectors } from 'AssetsJsPath/backend/builder/selectors';

export class HotkeyManager {
	constructor( canvas, layerManager ) {
		this.canvas = canvas;
		this.layerManager = layerManager;
		this.init();
	}

	init() {
		document.addEventListener( 'keydown', ( e ) => {
			if ( ! this.canvas || ! this.layerManager ) {
				return;
			}

			const previewModal = document.querySelector( selectors.elPreviewModal );
			if ( previewModal && previewModal.style.display !== 'none' ) {
				return;
			}

			const isInputFocused = document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA' || document.activeElement.isContentEditable;

			if ( isInputFocused && e.key !== 'Delete' && e.key !== 'Backspace' ) {
				return;
			}

			if ( e.ctrlKey || e.metaKey ) {
				switch ( e.key.toLowerCase() ) {
					case 'a':
						e.preventDefault();
						this.layerManager.handleSelectAll();
						break;
					case 'c':
						e.preventDefault();
						this.layerManager.handleCopy();
						break;
					case 'v':
						e.preventDefault();
						this.layerManager.handlePaste();
						break;
					case 'd':
						e.preventDefault();
						this.layerManager.handleDuplicate();
						break;
					case 'z':	
						e.preventDefault();
						if ( e.shiftKey ) {
							this.layerManager.handleRedo();
						} else {
							this.layerManager.handleUndo();
						}
						break;
					case 'y':
						e.preventDefault();
						this.layerManager.handleRedo();
						break;
				}
			} else if ( e.key === 'Delete' || e.key === 'Backspace' ) {
				if ( ! isInputFocused ) {
					e.preventDefault();
					this.layerManager.handleDeleteLayer();
				}
			} else if ( e.key === 'ArrowUp' || e.key === 'ArrowDown' || e.key === 'ArrowLeft' || e.key === 'ArrowRight' ) {
				if ( ! isInputFocused ) {
					e.preventDefault();
					this.layerManager.handleMoveObject( e.key, e.shiftKey );
				}
			}
		} );
	}
}
