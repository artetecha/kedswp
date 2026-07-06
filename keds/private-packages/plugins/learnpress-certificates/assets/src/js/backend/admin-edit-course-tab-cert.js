/**
 * Handle certificate tab in course edit screen
 *
 * @since 4.2.3
 * @version 1.0.0
 */
import * as lpToastify from 'AssetsJsPath/lpToastify';
import * as lpUtils from 'AssetsJsPath/utils';

( function () {
	let lpCertInfoFrame;

	function $$( sel, root ) {
		return ( root || document ).querySelectorAll( sel );
	}

	function $1( sel, root ) {
		return ( root || document ).querySelector( sel );
	}

	function clearChildren( el ) {
		while ( el && el.firstChild ) {
			el.removeChild( el.firstChild );
		}
	}

	function uploadWrap() {
		return $1( '#lp-cert-info-upload-wrap' );
	}

	function uploadDataset() {
		const w = uploadWrap();
		return w ? w.dataset : {};
	}

	function popupOptionsFromEl( el ) {
		const ds = el ? el.dataset : {};
		return {
			title: ds.popupTitle || '',
			text: ds.popupText || '',
			confirmButtonText: ds.popupConfirm || '',
			cancelButtonText: ds.popupCancel || '',
		};
	}

	function buildUploadPlaceholder() {
		const ds = uploadDataset();
		const wrap = document.createElement( 'div' );
		wrap.className = 'lp-cert-info-upload-area__placeholder';

		const icon = document.createElement( 'img' );
		icon.className = 'lp-cert-info-upload-area__icon';
		icon.src = ds.uploadIconUrl || '';
		icon.alt = '';
		wrap.appendChild( icon );

		const textWrap = document.createElement( 'div' );
		textWrap.className = 'lp-cert-info-upload-area__text';
		const linkSpan = document.createElement( 'span' );
		linkSpan.className = 'lp-cert-info-upload-area__link';
		linkSpan.textContent = ds.textClickToUpload || '';
		textWrap.appendChild( linkSpan );
		const dragSpan = document.createElement( 'span' );
		dragSpan.className = 'lp-cert-info-upload-area__drag';
		dragSpan.textContent = ds.textOrDragDrop || '';
		textWrap.appendChild( dragSpan );
		wrap.appendChild( textWrap );

		const hint = document.createElement( 'div' );
		hint.className = 'lp-cert-info-upload-area__hint';
		hint.textContent = ds.textJpgPng || '';
		wrap.appendChild( hint );

		return wrap;
	}

	const FIELD_ROW_SELECTORS = [ '#lp-cert-info-row-image', '#lp-cert-info-row-title', '#lp-cert-info-row-description' ];

	function setFieldRowsDisabled( disabled ) {
		FIELD_ROW_SELECTORS.forEach( function ( sel ) {
			const el = $1( sel );
			if ( ! el ) {
				return;
			}
			el.classList.toggle( 'lp-option-disabled', !! disabled );
		} );
	}

	function setEnableCheckbox( hasCert ) {
		const cb = $1( '#lp_cert_info_enable' );
		if ( ! cb ) {
			return;
		}
		cb.disabled = ! hasCert;
		if ( ! hasCert ) {
			cb.checked = false;
			setFieldRowsDisabled( true );
		}
	}

	function getInputValue( id ) {
		const el = document.getElementById( id );
		return el ? el.value : '';
	}

	function setInputValue( id, val ) {
		const el = document.getElementById( id );
		if ( el ) {
			el.value = val;
		}
	}

	function persistEnableDisabled( cId ) {
		if ( ! cId || ! window.lpAJAXG ) {
			return;
		}
		window.lpAJAXG.fetchAJAX( {
			action: 'save_course_cert_info',
			course_id: cId,
			enable: 0,
			image: getInputValue( 'lp_cert_info_image' ),
			title: getInputValue( 'lp_cert_info_title' ).trim(),
			description: getInputValue( 'lp_cert_info_description' ),
		}, {
			error: function () {
				console.error( 'Failed to persist enable=0 after cert removal.' );
			},
		} );
	}

	function showCertConfirm( options ) {
		if ( typeof window.lpCertConfirm === 'function' ) {
			return window.lpCertConfirm(
				Object.assign( { icon: 'warning' }, options )
			);
		}
		const ok = confirm( options.title + ( options.text ? '\n' + options.text : '' ) );
		return Promise.resolve( { isConfirmed: !! ok } );
	}

	function renderUploadPlaceholder() {
		const target = document.getElementById( 'lp-cert-info-upload-area' );
		if ( ! target ) {
			return;
		}
		clearChildren( target );
		target.appendChild( buildUploadPlaceholder() );
	}

	function openMediaPicker() {
		const ds = uploadDataset();
		if ( lpCertInfoFrame ) {
			lpCertInfoFrame.open();
			return;
		}
		lpCertInfoFrame = wp.media( {
			title: ds.textSelectImage || '',
			button: { text: ds.textUseThisImage || '' },
			multiple: false,
		} );
		lpCertInfoFrame.on( 'select', function () {
			const attachment = lpCertInfoFrame.state().get( 'selection' ).first().toJSON();
			const url = attachment.url;
			setInputValue( 'lp_cert_info_image', url );

			const target = document.getElementById( 'lp-cert-info-upload-area' );
			if ( ! target ) {
				return;
			}
			clearChildren( target );

			const img = document.createElement( 'img' );
			img.src = url;
			img.alt = '';
			target.appendChild( img );

			const removeBtn = document.createElement( 'button' );
			removeBtn.type = 'button';
			removeBtn.className = 'lp-cert-info-remove-image';
			removeBtn.setAttribute( 'aria-label', ds.textRemoveImageAria || '' );
			removeBtn.textContent = '×';
			target.appendChild( removeBtn );
		} );
		lpCertInfoFrame.open();
	}

	function getActiveCertificateId() {
		const active = $1( '#certificate-browser .lp-certificates-new .theme.active' );
		if ( ! active ) {
			return 0;
		}
		return parseInt( active.dataset.id, 10 ) || 0;
	}

	function syncActiveCertificate( certId ) {
		$$( '#certificate-browser .lp-certificates-new .theme' ).forEach( function ( theme ) {
			theme.classList.remove( 'active' );
		} );
		if ( certId ) {
			const target = $1( '#certificate-browser .lp-certificates-new .theme[data-id="' + certId + '"]' );
			if ( target ) {
				target.classList.add( 'active' );
			}
		}
		setEnableCheckbox( !! certId );
	}

	function clearUpdatingCertificate() {
		$$( '#certificate-browser .theme.updating' ).forEach( function ( el ) {
			el.classList.remove( 'updating' );
		} );
	}

	function updateCerOfCourse( cId, certId, callBack ) {
		const currentCertId = getActiveCertificateId();

		if ( ! cId || ! window.lpAJAXG ) {
			clearUpdatingCertificate();
			return;
		}

		window.lpAJAXG.fetchAJAX(
			{ action: 'cert_assign_to_course', course_id: cId, cert_id: certId },
			{
				success: function ( response ) {
					const { status, message, data  } = response;
					lpToastify.show( message, status );

					if ( callBack ) {
						callBack( response );
					}

					if ( response && response.status === 'success' ) {
						syncActiveCertificate( certId );
						if ( ! certId ) {
							persistEnableDisabled( cId );
						}
					}
				},
				error: function ( error ) {
					syncActiveCertificate( currentCertId );
					console.error( error );
				},
				completed: function () {
					clearUpdatingCertificate();
				},
			}
		);
	}

	function getCourseID() {
		const postIdInput = document.getElementById( 'post_ID' );
		if ( postIdInput && postIdInput.value ) {
			return postIdInput.value;
		}
		const browser = $1( '.lp-course-cert-browser-new' );
		return browser ? browser.dataset.courseId : '';
	}

	document.addEventListener( 'change', function ( e ) {
		if ( e.target && e.target.id === 'lp_cert_info_enable' ) {
			setFieldRowsDisabled( ! e.target.checked );
		}
	} );

	document.addEventListener( 'click', function ( e ) {
		const removeImg = e.target.closest( '.lp-cert-info-remove-image' );
		const uploadArea = e.target.closest( '#lp-cert-info-upload-area' );

		if ( uploadArea && ! removeImg ) {
			e.preventDefault();
			openMediaPicker();
			return;
		}

		if ( removeImg ) {
			e.preventDefault();
			e.stopPropagation();
			const wrap = removeImg.closest( '.lp-cert-info-upload-wrap' );
			showCertConfirm( popupOptionsFromEl( wrap ) ).then( function ( result ) {
				if ( result && result.isConfirmed ) {
					setInputValue( 'lp_cert_info_image', '' );
					renderUploadPlaceholder();
				}
			} );
			return;
		}

		const assignBtn = e.target.closest( '.button-assign-certificate' );
		if ( assignBtn ) {
			e.preventDefault();
			const cId = getCourseID();
			const theme = assignBtn.closest( '.theme' );
			if ( ! theme ) {
				return;
			}
			const certID = parseInt( theme.dataset.id, 10 );
			theme.classList.add( 'updating' );
			lpUtils.lpSetLoadingEl( assignBtn, 1 );
			updateCerOfCourse( cId, certID, ( response ) => {
				lpUtils.lpSetLoadingEl( assignBtn, 0 );
			});
			return;
		}

		const removeCertBtn = e.target.closest( '.button-remove-certificate' );
		if ( removeCertBtn ) {
			e.preventDefault();
			e.stopPropagation();
			const cId = getCourseID();
			const theme = removeCertBtn.closest( '.theme' );
			if ( ! theme ) {
				return;
			}
			showCertConfirm( popupOptionsFromEl( removeCertBtn ) ).then( function ( result ) {
				if ( ! result || ! result.isConfirmed ) {
					return;
				}

				theme.classList.add( 'updating' );
				lpUtils.lpSetLoadingEl( removeCertBtn, 1 );
				updateCerOfCourse( cId, 0, ( response ) => {
					lpUtils.lpSetLoadingEl( removeCertBtn, 0 );
				} );
			} );
			return;
		}

		const saveBtn = e.target.closest( '#lp-cert-info-save-btn' );
		if ( saveBtn ) {
			e.preventDefault();
			handleSaveInfo( saveBtn );
			return;
		}

		const loadMoreBtn = e.target.closest( '.lp-cer-btn-load-more-new' );
		if ( loadMoreBtn ) {
			e.preventDefault();
			handleLoadMore( loadMoreBtn );
		}
	} );

	function handleSaveInfo( btn ) {
		if ( btn.disabled ) {
			return;
		}

		const ds = btn.dataset;
		const originalText = btn.textContent.trim();

		const enableCheckbox = $1( '#lp_cert_info_enable' );
		const isEnabled = !! ( enableCheckbox && enableCheckbox.checked );
		const title = getInputValue( 'lp_cert_info_title' ).trim();

		if ( isEnabled && ! title ) {
			lpToastify.show( ds.textTitleRequired || '', 'error' );
			return;
		}

		btn.disabled = true;
		btn.textContent = ds.textSaving || '';

		const dataSend = {
			action: 'save_course_cert_info',
			course_id: parseInt( getCourseID(), 10 ) || 0,
			enable: isEnabled ? 1 : 0,
			image: getInputValue( 'lp_cert_info_image' ),
			title: title,
			description: getInputValue( 'lp_cert_info_description' ),
		};

		window.lpAJAXG.fetchAJAX( dataSend, {
			success: function ( response ) {
				if ( response.status === 'success' ) {
					lpToastify.show( ds.textSaved || '', 'success' );
				} else {
					lpToastify.show( response.message || ds.textErrorSave || '', 'error' );
				}
			},
			error: function () {
				lpToastify.show( ds.textErrorSave || '', 'error' );
			},
			completed: function () {
				btn.disabled = false;
				btn.textContent = originalText;
			},
		} );
	}

	function handleLoadMore( btn ) {
		if ( btn.disabled ) {
			return;
		}

		const offset = parseInt( btn.dataset.offset, 10 ) || 0;
		const cId = btn.dataset.courseId;
		const certActive = btn.dataset.certActive;
		const textOriginal = btn.textContent;

		btn.disabled = true;
		btn.textContent = textOriginal.trim() + '...';

		const browser = $1( '.lp-course-cert-browser-new' );
		const isCourseBuilder = browser && parseInt( browser.dataset.isCourseBuilder, 10 ) === 1;

		const dataSend = {
			action: 'certificate_load_more_course_certs',
			offset: offset,
			course_id: cId,
			cert_active: certActive,
			is_course_builder: isCourseBuilder ? 1 : 0,
		};

		window.lpAJAXG.fetchAJAX( dataSend, {
			success: function ( response ) {
				if ( response.status === 'success' && response.data && response.data.html ) {
					const addNew = $1( '.lp-certificates-new .add-new-theme' );
					if ( addNew ) {
						addNew.insertAdjacentHTML( 'beforebegin', response.data.html );
					}
					btn.dataset.offset = offset + 6;
					if ( ! response.data.has_more ) {
						btn.remove();
					}
				}
			},
			error: function () {
				console.error( 'Error loading more certificates' );
			},
			completed: function () {
				btn.disabled = false;
				btn.textContent = textOriginal;
			},
		} );
	}
}() );
