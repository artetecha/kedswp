import { Canvas, FabricImage } from 'fabric';
import { selectors } from 'AssetsJsPath/backend/builder/selectors';
import { autoResizeCanvasToFit } from 'AssetsJsPath/backend/builder/utils';
import {
	downloadCertificate,
	downloadCertificateAsPDF,
	handleTextAutoExpand,
	isTextObject
} from 'AssetsJsPath/backend/builder/utils/certificate-download';
import {
	isQrObject,
	collectQrObject,
	applyQrReplacements,
	resetQrField
} from './qrcode';

let mainCanvas = null;
let previewCanvas = null;
let originalTexts = new Map();
let qrObjects = new Map();
let eventsBound = false;
let previewResizeObserver = null;
const STORAGE_KEY = 'lp_cert_preview_data';
const PREVIEW_CANVAS_INSET = {
	x: 48,
	y: 48,
};

export function initPreview( canvasInstance ) {
	mainCanvas = canvasInstance;
	if ( ! eventsBound ) {
		setupEventListeners();
		eventsBound = true;
	}
}

function setupEventListeners() {
	document.querySelectorAll( selectors.elBtnPreview ).forEach( ( btn ) => {
		btn.addEventListener( 'click', openPreviewModal );
	} );

	const closeBtn = document.querySelector( selectors.elPreviewModalClose );
	if ( closeBtn ) {
		closeBtn.addEventListener( 'click', closePreviewModal );
	}

	const applyBtn = document.querySelector( selectors.elPreviewApply );
	if ( applyBtn ) {
		applyBtn.addEventListener( 'click', applyPreviewData );
	}

	const clearBtn = document.querySelector( selectors.elPreviewClear );
	if ( clearBtn ) {
		clearBtn.addEventListener( 'click', clearAllFields );
	}

	const downloadBtn = document.querySelector( selectors.elPreviewDownload );
	if ( downloadBtn ) {
		downloadBtn.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			const dropdown = downloadBtn.closest( '.lp-cert-download-dropdown' );
			dropdown?.classList.toggle( 'open' );
		} );
	}

	document.querySelectorAll( '.lp-cert-download-option' ).forEach( ( option ) => {
		option.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			const format = option.dataset.format;
			const dropdown = option.closest( '.lp-cert-download-dropdown' );
			dropdown?.classList.remove( 'open' );
			downloadPreview( format );
		} );
	} );

	document.addEventListener( 'click', () => {
		document.querySelector( '.lp-cert-download-dropdown' )?.classList.remove( 'open' );
	} );

	document.addEventListener( 'keydown', ( e ) => {
		if ( e.key === 'Escape' ) {
			const modal = document.querySelector( selectors.elPreviewModal );
			if ( modal && modal.style.display !== 'none' ) {
				closePreviewModal();
			}
		}
	} );
}

async function openPreviewModal() {
	const modal = document.querySelector( selectors.elPreviewModal );
	if ( ! modal || ! mainCanvas ) {
		return;
	}

	modal.style.display = 'flex';
	document.body.classList.add( 'lp-cert-preview-open' );

	restoreFormData();
	await createPreviewCanvas();
}

function closePreviewModal() {
	const modal = document.querySelector( selectors.elPreviewModal );
	if ( modal ) {
		modal.style.display = 'none';
	}
	document.body.classList.remove( 'lp-cert-preview-open' );
	teardownPreviewResizeObserver();

	if ( previewCanvas ) {
		previewCanvas.dispose();
		previewCanvas = null;
	}
	originalTexts.clear();
	qrObjects.clear();
}

async function createPreviewCanvas() {
	const canvasEl = document.querySelector( selectors.elPreviewCanvas );
	if ( ! canvasEl || ! mainCanvas ) {
		return;
	}

	if ( previewCanvas ) {
		previewCanvas.dispose();
	}

	const width = mainCanvas.getWidth();
	const height = mainCanvas.getHeight();

	previewCanvas = new Canvas( canvasEl, {
		width: width,
		height: height,
		backgroundColor: mainCanvas.backgroundColor || '#ffffff',
		selection: false,
	} );

	if ( mainCanvas.backgroundImage ) {
		try {
			const bgUrl = mainCanvas.backgroundImage.getSrc();
			if ( bgUrl ) {
				const img = await FabricImage.fromURL( bgUrl, { crossOrigin: 'anonymous' } );
				if ( img ) {
					img.set( {
						scaleX: mainCanvas.backgroundImage.scaleX,
						scaleY: mainCanvas.backgroundImage.scaleY,
						originX: mainCanvas.backgroundImage.originX,
						originY: mainCanvas.backgroundImage.originY,
						left: mainCanvas.backgroundImage.left,
						top: mainCanvas.backgroundImage.top,
						opacity: mainCanvas.backgroundImage.opacity,
					} );
					previewCanvas.backgroundImage = img;
				}
			}
		} catch ( error ) {
			console.error( 'Error copying background:', error );
		}
	}

	const objects = mainCanvas.getObjects();
	for ( const obj of objects ) {
		try {
			const cloned = await obj.clone();
			cloned.set( {
				selectable: false,
				evented: false,
			} );

			if ( isQrObject( obj ) ) {
				collectQrObject( obj, cloned, qrObjects );
				previewCanvas.add( cloned );
			} else if ( isTextObject( cloned ) ) {
				const text = cloned.get( 'text' ) || '';
				if ( text.includes( '[' ) && text.includes( ']' ) ) {
					originalTexts.set( cloned, text );
				}
				handleTextAutoExpand( cloned );
				previewCanvas.add( cloned );
			} else {
				previewCanvas.add( cloned );
			}
		} catch ( error ) {
			console.error( 'Error cloning object:', error );
		}
	}

	previewCanvas.renderAll();

	autoFitPreviewCanvas();
	setupPreviewResizeObserver();

	await applyPreviewData();
}

function autoFitPreviewCanvas() {
	const retry = autoResizeCanvasToFit( previewCanvas, {
		insetX: PREVIEW_CANVAS_INSET.x,
		insetY: PREVIEW_CANVAS_INSET.y,
	} );
	if ( retry ) {
		requestAnimationFrame( () => autoFitPreviewCanvas() );
	}
}

function setupPreviewResizeObserver() {
	if ( typeof ResizeObserver === 'undefined' || ! previewCanvas ) {
		return;
	}

	const canvasWrapper = previewCanvas.wrapperEl;
	const container = canvasWrapper?.parentElement;

	if ( ! container ) {
		return;
	}

	teardownPreviewResizeObserver();

	previewResizeObserver = new ResizeObserver( () => {
		autoFitPreviewCanvas();
	} );

	previewResizeObserver.observe( container );
}

function teardownPreviewResizeObserver() {
	if ( previewResizeObserver ) {
		previewResizeObserver.disconnect();
		previewResizeObserver = null;
	}
}

function restoreFormData() {
	const saved = sessionStorage.getItem( STORAGE_KEY );
	if ( ! saved ) {
		return;
	}
	try {
		const data = JSON.parse( saved );
		const fields = document.querySelectorAll( '.lp-cert-preview-field input' );
		fields.forEach( ( field ) => {
			if ( data[ field.id ] !== undefined ) {
				field.value = data[ field.id ];
			}
		} );
	} catch ( e ) {}
}

function saveFormData() {
	const fields = document.querySelectorAll( '.lp-cert-preview-field input' );
	const data = {};
	fields.forEach( ( field ) => {
		data[ field.id ] = field.value;
	} );
	sessionStorage.setItem( STORAGE_KEY, JSON.stringify( data ) );
}

function getFormReplacements() {
	const fields = document.querySelectorAll( '.lp-cert-preview-field input' );
	const values = {};

	fields.forEach( ( field ) => {
		const placeholder = field.dataset.placeholder;
		if ( placeholder ) {
			values[ placeholder ] = field.value.trim();
		}
	} );

	return values;
}

async function applyPreviewData() {
	if ( ! previewCanvas ) {
		return;
	}

	const values = getFormReplacements();
	saveFormData();

	await applyQrReplacements( qrObjects, values.QR_CODE, previewCanvas );

	const objects = previewCanvas.getObjects();
	objects.forEach( ( obj ) => {
		if ( isTextObject( obj ) && ! qrObjects.has( obj ) ) {
			const originalText = originalTexts.get( obj );
			if ( originalText ) {
				let newText = originalText;

				Object.keys( values ).forEach( ( placeholder ) => {
					if ( placeholder === 'QR_CODE' ) {
						return;
					}
					const value = values[ placeholder ];
					if ( value ) {
						const regex = new RegExp( `\\[${ placeholder }\\]`, 'g' );
						newText = newText.replace( regex, value );
					}
				} );

				obj.set( 'text', newText );
				handleTextAutoExpand( obj );
			}
		}
	} );

	previewCanvas.renderAll();
}

async function clearAllFields() {
	const fields = document.querySelectorAll( '.lp-cert-preview-field input' );
	fields.forEach( ( field ) => {
		if ( field.dataset.placeholder === 'QR_CODE' ) {
			field.value = resetQrField();
		} else {
			field.value = '';
		}
	} );

	if ( previewCanvas ) {
		const objects = previewCanvas.getObjects();
		objects.forEach( ( obj ) => {
			if ( isTextObject( obj ) && ! qrObjects.has( obj ) ) {
				const originalText = originalTexts.get( obj );
				if ( originalText ) {
					obj.set( 'text', originalText );
					handleTextAutoExpand( obj );
				}
			}
		} );
		previewCanvas.renderAll();
	}

	sessionStorage.removeItem( STORAGE_KEY );
	await applyPreviewData();
}

async function downloadPreview( format = 'png' ) {
	if ( ! previewCanvas ) {
		return;
	}

	try {
		if ( format === 'pdf' ) {
			await downloadCertificateAsPDF( previewCanvas, {
				filename: `certificate-preview-${ Date.now() }.pdf`,
				multiplier: 2,
			} );
		} else {
			await downloadCertificate( previewCanvas, {
				filename: `certificate-preview-${ Date.now() }.png`,
				format: 'png',
				quality: 1,
				multiplier: 2,
			} );
		}
	} catch ( error ) {
		console.error( 'Error downloading certificate:', error );
	}
}

window.addEventListener( 'resize', () => {
	if ( previewCanvas ) {
		autoFitPreviewCanvas();
	}
} );
