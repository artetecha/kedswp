import * as lpUtils from 'AssetsJsPath/utils';
import { EditCertificate } from 'AssetsJsPath/backend/edit-certificate';
import { selectors } from 'AssetsJsPath/backend/builder/selectors';
import { replaceNewlinesForLoad } from 'AssetsJsPath/backend/builder/utils';
import { toggleMenu, toggleGroup } from 'AssetsJsPath/backend/builder/menu';
import { refreshLayersPanel } from 'AssetsJsPath/backend/builder/layers';
import { setResizeManagers, populateCurrentDimensions } from 'AssetsJsPath/backend/builder/toolbar/canvas-toolbar/resize';
import {
	setCanvasBackgroundColor,
	setCanvasBackgroundImage,
	loadMoreBackgroundImages
} from 'AssetsJsPath/backend/builder/backgrounds';
import {
	showLibraryFullView,
	showLibraryMainView,
	insertLibraryImage
} from 'AssetsJsPath/backend/builder/library/images';
import { showToastify } from 'AssetsJsPath/backend/utils/toastify';
import { lpCertConfirm } from 'AssetsJsPath/backend/utils/confirm';

let thisEditBuilder;
let uploadListenerInitialized = false;

export function setupEvents( editBuilder ) {
	if ( editBuilder.constructor._loadedEvents ) {
		return;
	}
	editBuilder.constructor._loadedEvents = true;

	thisEditBuilder = editBuilder;

	lpUtils.eventHandlers( 'click', [
		{
			selector: selectors.elBtnBackEditGeneral,
			callBack: backToEditGeneral,
		},
		{
			selector: selectors.elTemplateItem,
			callBack: selectTemplate,
		},
		{
			selector: selectors.elBtnChooseTemplateFirst,
			callBack: submitSelectedTemplate,
		},
		{
			selector: selectors.elMenuItem,
			callBack: toggleMenu,
		},
		{
			selector: selectors.elGroupHeader,
			callBack: toggleGroup,
		},
		{
			selector: selectors.elInserterElement,
			callBack: insertElement,
		},
		{
			selector: selectors.elInserterUploadBtn,
			callBack: insertImage,
		},
		{
			selector: selectors.elInserterUploadItem,
			callBack: insertImageFromGrid,
		},
		{
			selector: selectors.elInserterUploadLoadMore,
			callBack: loadMoreUploadImages,
		},
		{
			selector: selectors.elBackgroundColorSwatch,
			callBack: setCanvasBackgroundColor,
		},
		{
			selector: selectors.elBackgroundBgItem,
			callBack: insertBackgroundImage,
		},
		{
			selector: selectors.elBackgroundBgLoadMore,
			callBack: handleLoadMoreBackgroundImages,
		},
		{
			selector: selectors.elBtnUndo,
			callBack: handleUndo,
		},
		{
			selector: selectors.elBtnRedo,
			callBack: handleRedo,
		},
		{
			selector: selectors.elTemplateApplyItem,
			callBack: applyTemplateFromSidebar,
		},
		{
			selector: selectors.elLibraryImagesViewAll,
			callBack: viewAllLibraryImages,
		},
		{
			selector: selectors.elLibraryImagesBack,
			callBack: backToLibraryMainView,
		},
		{
			selector: selectors.elLibraryImageItem,
			callBack: insertLibraryImageFromGrid,
		}
	] );
}

export async function applyTemplateToCanvas( template ) {
	if ( template && thisEditBuilder && thisEditBuilder.layerManager ) {
		await thisEditBuilder.layerManager.applyTemplate( template );
		refreshLayersPanel();
		populateCurrentDimensions();
		showToastify( 'Template applied successfully', 'success' );
	}
}

function handleUndo( args ) {
	if ( thisEditBuilder && thisEditBuilder.layerManager ) {
		thisEditBuilder.layerManager.handleUndo();
	}
}

function handleRedo( args ) {
	if ( thisEditBuilder && thisEditBuilder.layerManager ) {
		thisEditBuilder.layerManager.handleRedo();
	}
}

async function applyTemplateFromSidebar( args ) {
	const { e, target } = args;
	e.preventDefault();

	const templateItem = target.closest( selectors.elTemplateApplyItem );
	if ( ! templateItem ) {
		return;
	}

	const templateId = templateItem.dataset.template;
	if ( ! templateId ) {
		return;
	}

	const popupHost = templateItem.closest( '.lp-inserter-templates-area' ) || templateItem;
	const ds = popupHost.dataset;
	const result = await lpCertConfirm( {
		title: ds.popupTitle || '',
		text: ds.popupText || '',
		icon: 'warning',
		confirmButtonText: ds.popupConfirm || '',
		cancelButtonText: ds.popupCancel || '',
	} );

	if ( ! result.isConfirmed ) {
		return;
	}

	if ( thisEditBuilder && thisEditBuilder.layerManager ) {
		await thisEditBuilder.layerManager.applyTemplate( templateId );
		refreshLayersPanel();
		populateCurrentDimensions();
		showToastify( 'Template applied successfully', 'success' );
	}
}

function backToEditGeneral( args ) {
	const { e, target } = args;
	const elEditBuilder = document.querySelector( EditCertificate.selectors.elEditBuilder );
	const elEditGeneral = document.querySelector( EditCertificate.selectors.elEditGeneral );
	lpUtils.lpShowHideEl( elEditBuilder, 0 );
	lpUtils.lpShowHideEl( elEditGeneral, 1 );

	// Remove is_builder_layout=1 from URL
	if ( typeof window.history.pushState === 'function' ) {
		const newUrl = window.location.href.replace( /[?&]is_builder_layout=1/, '' );
		window.history.pushState( {}, '', newUrl );
	}
}

function selectTemplate( args ) {
	const { e, target } = args;

	if ( target.closest( selectors.elBtnChooseTemplateFirst ) ) {
		return;
	}

	const templateItem = target.closest( selectors.elTemplateItem );
	if ( ! templateItem ) {
		return;
	}

	const allItems = document.querySelectorAll( selectors.elTemplateItem );
	allItems.forEach( ( item ) => item.classList.remove( 'selected' ) );

	templateItem.classList.add( 'selected' );
}

async function submitSelectedTemplate( args ) {
	const { e, target } = args;

	const selectedItem = document.querySelector( `${ selectors.elTemplateItem }.selected` );
	if ( ! selectedItem ) {
		showToastify( 'Please select a template first', 'warning' );
		return;
	}

	const type = selectedItem.dataset.template;
	if ( ! type ) {
		return;
	}

	if ( ! thisEditBuilder.certificateId ) {
		showToastify( 'Certificate ID not found', 'error' );
		return;
	}

	const chooseBtn = target.closest( selectors.elBtnChooseTemplateFirst ) || target;
	lpUtils.lpSetLoadingEl( chooseBtn, 1 );

	const elEditBuilder = document.querySelector( '.lp-certificate-edit-builder' );
	const currentStatus = elEditBuilder?.dataset?.status || '';

	if ( currentStatus === 'auto-draft' ) {
		const titleInput = document.querySelector( '#title' );
		const title = titleInput?.value || 'Certificate';

		const isCourseBuilder = parseInt( window.lpCertSettings?.is_course_builder || 0, 10 ) === 1;

		const saveDraftData = {
			action: 'lp_cert_save_draft',
			cert_id: thisEditBuilder.certificateId,
			title: title,
			template_type: type,
			is_course_builder: isCourseBuilder ? 1 : 0,
		};

		try {
			await new Promise( ( resolve, reject ) => {
				window.lpAJAXG.fetchAJAX( saveDraftData, {
					success: ( response ) => {
						if ( response.status === 'success' ) {
							if ( elEditBuilder ) {
								elEditBuilder.dataset.status = 'draft';
							}

							const badge = document.querySelector( '.cb-cert-status-badge' );
							if ( badge ) {
								badge.className = 'cb-cert-status-badge cb-cert-status-badge--draft';
								badge.textContent = 'Draft';
							}

							const statusSelect = document.getElementById( 'cb-cert-status' );
							if ( statusSelect ) {
								statusSelect.value = 'draft';
							}

							const newUrl = response.data?.edit_link;
							if ( newUrl ) {
								window.history.replaceState( {}, '', newUrl );
							}

							const pageTitle = document.querySelector( '.wp-heading-inline' );
							if ( pageTitle && pageTitle.textContent.includes( 'Add New' ) ) {
								pageTitle.textContent = 'Edit Certificate';

								let addNewBtn = document.querySelector( '.page-title-action' );
								if ( ! addNewBtn ) {
									addNewBtn = document.createElement( 'a' );
									addNewBtn.className = 'page-title-action';
									addNewBtn.textContent = 'Add New Certificate';
									pageTitle.parentNode.insertBefore( addNewBtn, pageTitle.nextSibling );
								}

								const addNewLink = response.data?.add_new_link;
								if ( addNewLink ) {
									addNewBtn.href = addNewLink;
								}
							}

							resolve( response );
						} else {
							reject( new Error( response.message || 'Failed to save draft' ) );
						}
					},
					error: ( error ) => {
						reject( error );
					},
				} );
			} );
		} catch ( error ) {
			showToastify( error.message || 'Error saving draft', 'error' );
			return;
		}
	}

	const dataSend = {
		action: 'lp_cert_choose_template_type',
		certificate_id: thisEditBuilder.certificateId,
		template_type: type,
	};

	const callBack = {
		success: ( response ) => {
			const { status, message, data } = response;

			if ( 'success' === status ) {
				showToastify( message, 'success' );

				if ( data && data.layers ) {
					const processedLayers = replaceNewlinesForLoad( data.layers );
					window.lpCertCanvasData = {
						certificate_id: thisEditBuilder.certificateId,
						...processedLayers,
					};
				}

				if ( thisEditBuilder.canvasManager && thisEditBuilder.canvasManager.canvas ) {
					thisEditBuilder.canvasManager.canvas.dispose();
					thisEditBuilder.canvasManager.canvas = null;
				}

				const elLayoutBuilder = document.querySelector( selectors.elLayoutBuilder );
				const elLayoutSelection = document.querySelector( selectors.elLayoutSelection );
				lpUtils.lpShowHideEl( elLayoutSelection, 0 );
				lpUtils.lpShowHideEl( elLayoutBuilder, 1 );
				document.querySelectorAll( selectors.elBtnOpenFullscreen ).forEach( ( el ) => lpUtils.lpShowHideEl( el, 1 ) );
				document.querySelectorAll( selectors.elBtnUndo ).forEach( ( el ) => lpUtils.lpShowHideEl( el, 1 ) );
				document.querySelectorAll( selectors.elBtnRedo ).forEach( ( el ) => lpUtils.lpShowHideEl( el, 1 ) );

				thisEditBuilder.initCanvas().then( () => {
					refreshLayersPanel();
					setResizeManagers( thisEditBuilder.canvasManager, thisEditBuilder.layerManager );
					populateCurrentDimensions();
				} );
			} else {
				showToastify( message, 'error' );
			}
		},
		error: ( error ) => {
			showToastify( 'Error updating template type', 'error' );
		},
		completed: () => {
			if ( chooseBtn ) {
				lpUtils.lpSetLoadingEl( chooseBtn, 0 );
			}
		}
	}

	window.lpAJAXG.fetchAJAX( dataSend, callBack );
}

export async function insertElement( args ) {
	const { e, target } = args;
	e.preventDefault();

	const inserterItem = target.closest( selectors.elInserterElement );
	if ( ! inserterItem ) {
		return;
	}

	const type = inserterItem.dataset.typeLayer || 'text-static';
	const elementName = inserterItem.dataset.insert || '';
	const label = inserterItem.dataset.label || 'element';
	let result;

	switch ( type ) {
		case 'text-edit': {
			const textDefault = 'hello world';
			result = await thisEditBuilder.layerManager.insertFabricElement( 'text-edit', textDefault, {
				name: label || 'Text',
			} );
			break;
		}
		case 'qr_code': {
			const qrDefaultImage = window.lpCertSettings?.qr_default_image || '';
			if ( ! qrDefaultImage ) {
				return;
			}
			result = await thisEditBuilder.layerManager.insertFabricElement( 'qr_code', qrDefaultImage, {
				name: label || 'QR Code',
			} );
			break;
		}
		case 'text-static': {
			const textContent = '[' + elementName.toUpperCase().replace( /\s+/g, '_' ) + ']';
			result = await thisEditBuilder.layerManager.insertFabricElement( 'text-static', textContent, {
				name: label || textContent,
			} );
			break;
		}
		default: {
			showToastify( `Type "${ type }" is not supported yet.`, 'warning' );
			return;
		}
	}

	if ( result && result.fabricObj ) {
		refreshLayersPanel();
	}
}

export function insertImage( args ) {
	const { e, target } = args;
	e.preventDefault();

	if ( typeof wp === 'undefined' || typeof wp.media === 'undefined' ) {
		showToastify( 'Media library is not available', 'error' );
		return;
	}

	if ( ! thisEditBuilder.wpMediaFrame ) {
		thisEditBuilder.wpMediaFrame = wp.media( {
			title: 'Select Images',
			button: {
				text: 'Insert layers',
			},
			multiple: true,
			library: {
				type: 'image',
			},
			state: 'library',
		} );

		thisEditBuilder.wpMediaFrame.on( 'open', () => {
			const libraryState = thisEditBuilder.wpMediaFrame.state( 'library' );
			if ( libraryState ) {
				libraryState.set( 'content', 'upload' );
				libraryState.set( 'contentUserSetting', false );
			}
			setupMediaUploadListener();
		} );

		thisEditBuilder.wpMediaFrame.on( 'select', async () => {
			const selection = thisEditBuilder.wpMediaFrame.state().get( 'selection' );
			const attachments = selection.models || selection.toArray();

			if ( ! attachments || attachments.length === 0 ) {
				return;
			}

			let successCount = 0;
			let errorCount = 0;

			for ( const attachment of attachments ) {
				const attachmentData = attachment.toJSON ? attachment.toJSON() : attachment;
				const imageUrl = attachmentData.url;

				if ( ! imageUrl ) {
					errorCount++;
					continue;
				}

				try {
					const result = await thisEditBuilder.layerManager.insertFabricElement( 'image', imageUrl, { name: 'Image' } );

					if ( result && result.fabricObj ) {
						successCount++;
					} else {
						errorCount++;
					}
				} catch ( error ) {
					console.error( 'Error inserting image:', error );
					errorCount++;
				}
			}

			if ( successCount > 0 ) {
				refreshLayersPanel();
			}

			if ( successCount > 0 ) {
				if ( successCount === attachments.length ) {
					showToastify( `Successfully inserted ${ successCount } image(s)`, 'success' );
				} else {
					showToastify( `Inserted ${ successCount } image(s), ${ errorCount } failed`, 'warning' );
				}
			} else if ( errorCount > 0 ) {
				showToastify( `Failed to insert ${ errorCount } image(s)`, 'error' );
			}
		} );
	}

	thisEditBuilder.wpMediaFrame.open();
}

export async function insertImageFromGrid( args ) {
	const { e, target } = args;
	e.preventDefault();

	const uploadItem = target.closest( selectors.elInserterUploadItem );
	if ( ! uploadItem ) {
		return;
	}

	const imageUrl = uploadItem.dataset.imageUrl;
	if ( ! imageUrl ) {
		return;
	}

	const result = await thisEditBuilder.layerManager.insertFabricElement( 'image', imageUrl, { name: 'Image' } );

	if ( result && result.fabricObj ) {
		refreshLayersPanel();
	}
}

export function loadMoreUploadImages( args ) {
	const { e, target } = args;
	e.preventDefault();

	const button = target.closest( selectors.elInserterUploadLoadMore );
	const grid = document.querySelector( selectors.elInserterUploadGrid );

	if ( ! button || ! grid ) {
		return;
	}

	if ( button.disabled ) {
		return;
	}

	const offset = parseInt( button.dataset.offset || '0', 10 );
	const perPage = parseInt( button.dataset.perPage || '20', 10 );

	button.disabled = true;
	button.classList.add( 'is-loading' );

	const dataSend = {
		action: 'certificate_builder_load_images',
		offset: offset,
		per_page: perPage,
	};

	window.lpAJAXG.fetchAJAX( dataSend, {
		success: ( response ) => {
			const { status, data, message } = response;

			if ( status === 'success' && data ) {
				if ( data.html ) {
					grid.insertAdjacentHTML( 'beforeend', data.html );
				}

				if ( ! data.has_more || ! data.html ) {
					button.remove();
				} else {
					button.dataset.offset = data.next_offset || ( offset + perPage );
				}
			} else if ( message ) {
				showToastify( message, 'error' );
			}
		},
		error: () => {
			showToastify( 'Error loading images', 'error' );
		},
		completed: () => {
			if ( document.contains( button ) ) {
				button.disabled = false;
				button.classList.remove( 'is-loading' );
			}
		},
	} );
}

export function insertBackgroundImage( args ) {
	const { e, target } = args;
	e.preventDefault();

	const bgItem = target.closest( selectors.elBackgroundBgItem );
	if ( ! bgItem ) {
		return;
	}

	const imageUrl = bgItem.dataset.imageUrl;
	if ( imageUrl ) {
		setCanvasBackgroundImage( imageUrl );
	}
}

export function handleLoadMoreBackgroundImages( args ) {
	const { e, target } = args;
	e.preventDefault();

	const button = target.closest( selectors.elBackgroundBgLoadMore );
	if ( button ) {
		loadMoreBackgroundImages( button );
	}
}

export function viewAllLibraryImages( args ) {
	const { e, target } = args;
	e.preventDefault();
	showLibraryFullView();
}

export function backToLibraryMainView( args ) {
	const { e, target } = args;
	e.preventDefault();
	showLibraryMainView();
}

export async function insertLibraryImageFromGrid( args ) {
	const { e, target } = args;
	e.preventDefault();

	const imageItem = target.closest( selectors.elLibraryImageItem );
	if ( ! imageItem ) {
		return;
	}

	const imageUrl = imageItem.dataset.imageUrl;
	if ( ! imageUrl ) {
		return;
	}

	const result = await insertLibraryImage( imageUrl );

	if ( result && result.fabricObj ) {
		refreshLayersPanel();
	}
}

function refreshUploadImages() {
	const grid = document.querySelector( selectors.elInserterUploadGrid );
	if ( ! grid ) {
		return;
	}

	const loadMoreBtn = document.querySelector( selectors.elInserterUploadLoadMore );

	window.lpAJAXG.fetchAJAX( {
		action: 'certificate_builder_load_images',
		offset: 0,
		per_page: 20,
	}, {
		success: ( response ) => {
			if ( response.status === 'success' && response.data?.html ) {
				grid.innerHTML = response.data.html;
				if ( loadMoreBtn ) {
					loadMoreBtn.dataset.offset = response.data.next_offset || 20;
				}
			}
		},
	} );
}

function refreshBackgroundImages() {
	const mainGrid = document.querySelector( selectors.elBackgroundBgMainGrid );
	const loadMoreBtn = document.querySelector( selectors.elBackgroundBgLoadMore );

	if ( mainGrid ) {
		window.lpAJAXG.fetchAJAX( {
			action: 'certificate_builder_load_background_images',
			offset: 0,
			per_page: 20,
		}, {
			success: ( response ) => {
				if ( response.status === 'success' && response.data?.html ) {
					mainGrid.innerHTML = response.data.html;
					if ( loadMoreBtn ) {
						if ( ! response.data.has_more ) {
							loadMoreBtn.style.display = 'none';
						} else {
							loadMoreBtn.dataset.offset = response.data.next_offset || 20;
							loadMoreBtn.style.display = 'block';
						}
					}
				}
			},
		} );
	}
}

function setupMediaUploadListener() {
	if ( uploadListenerInitialized ) {
		return;
	}

	if ( typeof wp !== 'undefined' && wp.Uploader && wp.Uploader.queue ) {
		wp.Uploader.queue.on( 'reset', () => {
			refreshUploadImages();
			refreshBackgroundImages();
		} );
		uploadListenerInitialized = true;
	}
}
