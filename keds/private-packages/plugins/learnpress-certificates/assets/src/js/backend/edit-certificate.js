/**
 * Edit Certificate JS
 *
 * @since 4.2.0
 */
import * as lpUtils from 'AssetsJsPath/utils';
import { EditBuilder } from './builder';
import { showToastify } from './utils/toastify';
import { applyTemplateToCanvas } from './builder/events';
import Swal from 'sweetalert2';

if ( typeof window.Swal === 'undefined' ) {
	window.Swal = Swal;
}

let certificateId;

export class EditCertificate {
	constructor() {
		this.postId = null;
		this.wpMediaFrame = null;
	}

	static selectors = {
		elEditWrapper: '.lp-certificate-edit-wrapper',
		elBtnEditBuilder: '.lp-btn-certificate-edit-builder',
		elBtnChooseTemplate: '.lp-btn-cert-choose-template',
		elEditBuilder : '.lp-certificate-edit-builder',
		elEditGeneral : '.lp-certificate-edit-general',
		elBtnChooseThumbnail: '.lp-btn-cert-choose-thumbnail',
		elBtnRemoveThumbnail: '.lp-btn-cert-remove-thumbnail',
		elThumbnailInput: 'input[name=_lp_cert_thumbnail]',
	}

	init( certificateId ) {
		this.events();
	}

	events() {
		if ( EditCertificate._loadedEvents ) {
			return;
		}
		EditCertificate._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: EditCertificate.selectors.elBtnEditBuilder,
				callBack: this.openCertificateBuilder.name,
				class: this,
			},
			{
				selector: EditCertificate.selectors.elBtnChooseTemplate,
				callBack: this.chooseTemplateAndOpenBuilder.name,
				class: this,
			},
			{
				selector: EditCertificate.selectors.elBtnChooseThumbnail,
				callBack: this.openMediaLibrary.name,
				class: this,
			},
			{
				selector: EditCertificate.selectors.elBtnRemoveThumbnail,
				callBack: this.removeThumbnail.name,
				class: this,
			}
		] );

		const uploadBtn = document.querySelector( '#lp-cert-upload-thumbnail' );
		const removeBtn = document.querySelector( '#lp-cert-remove-thumbnail' );
		const builderBtn = document.querySelector( '#lp-cert-edit-builder' );

		if ( uploadBtn ) {
			uploadBtn.addEventListener( 'click', () => this.openMediaLibrary() );
		}

		if ( removeBtn ) {
			removeBtn.addEventListener( 'click', () => this.removeThumbnail() );
		}

		if ( builderBtn ) {
			builderBtn.addEventListener( 'click', ( e ) => {
				this.openCertificateBuilder(e);
			} );
		}
	}

	openMediaLibrary() {
		if ( typeof wp === 'undefined' || typeof wp.media === 'undefined' ) {
			console.error( 'wp.media is not available. Make sure wp_enqueue_media() is called.' );
			return;
		}

		if ( this.wpMediaFrame ) {
			this.wpMediaFrame.open();
			return;
		}

		this.wpMediaFrame = wp.media( {
			multiple: false,
			library: {
				type: 'image',
			},
		} );

		this.wpMediaFrame.on( 'select', () => {
			const attachment = this.wpMediaFrame.state().get( 'selection' ).first().toJSON();
			this.setThumbnail( attachment.url );
		} );

		this.wpMediaFrame.open();
	}

	setThumbnail( imageUrl ) {
		const thumbnailInput = document.querySelector( EditCertificate.selectors.elThumbnailInput );
		const thumbnailPreview = document.querySelector( '.lp-certificate-thumbnail-preview' );
		const elBtnRemoveThumbnail = document.querySelector( EditCertificate.selectors.elBtnRemoveThumbnail );

		if ( thumbnailInput ) {
			thumbnailInput.value = imageUrl;
		}

		if ( thumbnailPreview ) {
			thumbnailPreview.innerHTML = `<img src="${ imageUrl }" alt="">`;
			lpUtils.lpShowHideEl( elBtnRemoveThumbnail, 1 )
		}
	}

	removeThumbnail( args ) {
		const { e, target } = args;
		const thumbnailInput = document.querySelector( EditCertificate.selectors.elThumbnailInput );
		const thumbnailPreview = document.querySelector( '.lp-certificate-thumbnail-preview' );
		const elBtnRemoveThumbnail = document.querySelector( EditCertificate.selectors.elBtnRemoveThumbnail );

		if ( thumbnailInput ) {
			thumbnailInput.value = '';
		}

		if ( thumbnailPreview ) {
			thumbnailPreview.innerHTML = `
				<div class="lp-certificate-thumbnail-placeholder">
					<span class="dashicons dashicons-format-image"></span>
				</div>
			`;
		}

		lpUtils.lpShowHideEl( elBtnRemoveThumbnail, 0 );
	}

	async chooseTemplateAndOpenBuilder( args ) {
		const { e, target } = args;

		const status = target.dataset.status;
		const selectedTemplate = document.querySelector( 'input[name="lp_cert_template_choice"]:checked' );

		if ( ! selectedTemplate ) {
			showToastify( 'Please select a template', 'warning' );
			return;
		}

		const templateType = selectedTemplate.value;

		target.classList.add( 'lp-loading' );
		target.disabled = true;

		try {
			if ( status === 'auto-draft' ) {
				const certId = document.querySelector( '.lp-certificate-edit-wrapper' )?.dataset?.certId;
				const title = document.querySelector( '#title' )?.value || 'Certificate';

				if ( certId ) {
					const response = await fetch( window.lpCertSettings?.ajax_url || window.ajaxurl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
						},
						body: new URLSearchParams( {
							action: 'lp_cert_save_draft',
							cert_id: certId,
							title: title,
							template_type: templateType,
							nonce: window.lpCertSettings?.nonce || '',
						} ),
					} );

					const result = await response.json();

					if ( ! result.success ) {
						showToastify( result.data?.message || 'Error saving certificate', 'error' );
						return;
					}

					target.dataset.status = 'draft';
				}
			}

			const elEditBuilder = document.querySelector( EditCertificate.selectors.elEditBuilder );
			const elEditGeneral = document.querySelector( EditCertificate.selectors.elEditGeneral );
			lpUtils.lpShowHideEl( elEditBuilder, 1 );
			lpUtils.lpShowHideEl( elEditGeneral, 0 );

			if ( typeof window.history.pushState === 'function' ) {
				const newUrl = lpUtils.lpAddQueryArgs(
					window.location.href,
					{ 'is_builder_layout': 1 }
				);
				window.history.pushState( {}, '', newUrl );
			}

			await applyTemplateToCanvas( templateType );

		} catch ( error ) {
			console.error( 'Error:', error );
			showToastify( 'An error occurred', 'error' );
		} finally {
			target.classList.remove( 'lp-loading' );
			target.disabled = false;
		}
	}

	/**
	 * Open Certificate Builder screen
	 *
	 * @param args
	 */
	openCertificateBuilder( args ) {
		const { e, target } = args;

		const message = target.dataset.message;
		const status = target.dataset.status;

		if ( status === 'auto-draft' ) {
			showToastify( message, 'warning' );
			return;
		}

		const elEditBuilder = document.querySelector( EditCertificate.selectors.elEditBuilder );
		const elEditGeneral = document.querySelector( EditCertificate.selectors.elEditGeneral );
		lpUtils.lpShowHideEl( elEditBuilder, 1 );
		lpUtils.lpShowHideEl( elEditGeneral, 0 );

		// code update url address without reloading the page

		// Update param is_builder_layout=1 to url
		if ( typeof window.history.pushState === 'function' ) {
			const newUrl = lpUtils.lpAddQueryArgs(
				window.location.href,
				{ 'is_builder_layout': 1 }
			);
			window.history.pushState( {}, '', newUrl );
		}
	}
}

lpUtils.lpOnElementReady( EditCertificate.selectors.elEditWrapper, ( el ) => {
	certificateId = el.dataset.certId;

	const editCertificate = new EditCertificate();
	editCertificate.init( certificateId );

	const editBuilder = new EditBuilder();
	editBuilder.init( certificateId ).then( () => {});
} );
