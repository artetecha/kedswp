import * as lpToastify from 'AssetsJsPath/lpToastify';
import * as lpUtils from 'AssetsJsPath/utils';

( function () {
	const editorHeader = document.querySelector( '.cb-cert-editor-header' );
	const headerData = editorHeader ? editorHeader.dataset : {};
	const certId = parseInt( headerData.certId, 10 ) || 0;
	const originalPostStatus = headerData.originalPostStatus || 'draft';

	const publishBtn = document.querySelector( '.cb-cert-btn-publish' );
	const publishBtnData = publishBtn ? publishBtn.dataset : {};

	const statusBadge = document.querySelector( '.cb-cert-status-badge' );
	const badgeData = statusBadge ? statusBadge.dataset : {};

	const thumbContainer = document.querySelector( '.lp-cert-metabox-thumbnail' );
	const thumbContainerData = thumbContainer ? thumbContainer.dataset : {};

	const titleInputEl = document.querySelector( '.cb-cert-title-input' );
	const titleInputData = titleInputEl ? titleInputEl.dataset : {};

	function getThumbUrl() {
		const input = document.getElementById( '_lp_cert_thumbnail' );
		return input ? input.value : '';
	}

	function getStatus() {
		const visSelect = document.getElementById( 'cb-cert-visibility' );
		if ( visSelect && visSelect.value === 'private' ) {
			return 'private';
		}
		const statusSelect = document.getElementById( 'cb-cert-status' );
		return statusSelect ? statusSelect.value : 'draft';
	}

	function fetchCertAJAX( action, data, onSuccess ) {
		if ( ! window.lpAJAXG ) {
			console.error( 'lpAJAXG not available' );
			return;
		}
		window.lpAJAXG.fetchAJAX(
			Object.assign( { action: action }, data ),
			{
				success: function ( res ) {
					const { status, message, data } = res;

					lpToastify.show( message, status );

					if ( status === 'success' ) {
						if ( onSuccess ) {
							onSuccess( res );
						}
					}
				},
				error: function ( err ) {
					console.error( 'AJAX error:', err );
					lpToastify.show( ( err && err.message ) || ( headerData.textRequestFailed || '' ), 'error' );
				},
			}
		);
	}

	const cbCertStatus = document.getElementById( 'cb-cert-status' );
	const cbCertVisibility = document.getElementById( 'cb-cert-visibility' );
	const passwordField = document.getElementById( 'password-visibility-field' );
	const publishDateInput = document.getElementById( 'cb-cert-publish-date' );

	function isDateInFuture() {
		if ( ! publishDateInput || ! publishDateInput.value ) {
			return false;
		}
		return new Date( publishDateInput.value ) > new Date();
	}

	function syncDateToHiddenFields() {
		if ( ! publishDateInput || ! publishDateInput.value ) {
			return;
		}
		const d = new Date( publishDateInput.value );
		const elAa = document.getElementById( 'aa' );
		const elMm = document.getElementById( 'mm' );
		const elJj = document.getElementById( 'jj' );
		const elHh = document.getElementById( 'hh' );
		const elMn = document.getElementById( 'mn' );
		if ( elAa ) {
			elAa.value = d.getFullYear();
		}
		if ( elMm ) {
			elMm.value = String( d.getMonth() + 1 ).padStart( 2, '0' );
		}
		if ( elJj ) {
			elJj.value = String( d.getDate() ).padStart( 2, '0' );
		}
		if ( elHh ) {
			elHh.value = String( d.getHours() ).padStart( 2, '0' );
		}
		if ( elMn ) {
			elMn.value = String( d.getMinutes() ).padStart( 2, '0' );
		}
	}

	const normalOptions = [
		{
			value: isDateInFuture() ? 'future' : 'publish',
			label: isDateInFuture() ? ( badgeData.labelFuture || '' ) : ( badgeData.labelPublish || '' ),
		},
		{ value: 'draft', label: badgeData.labelDraft || '' },
		{ value: 'pending', label: badgeData.labelPending || '' },
	];

	function updatePublishOption() {
		if ( ! cbCertStatus ) {
			return;
		}
		const vis = cbCertVisibility ? cbCertVisibility.value : 'public';
		if ( vis === 'private' ) {
			return;
		}
		const firstOpt = cbCertStatus.options[ 0 ];
		if ( ! firstOpt ) {
			return;
		}
		const future = isDateInFuture();
		firstOpt.value = future ? 'future' : 'publish';
		firstOpt.textContent = future ? ( badgeData.labelFuture || '' ) : ( badgeData.labelPublish || '' );
		normalOptions[ 0 ].value = firstOpt.value;
		normalOptions[ 0 ].label = firstOpt.textContent;
		updateBadge();
	}

	if ( publishDateInput ) {
		publishDateInput.addEventListener( 'change', function () {
			syncDateToHiddenFields();
			updatePublishOption();
		} );
	}

	function rebuildStatusSelect( vis ) {
		if ( ! cbCertStatus ) {
			return;
		}
		while ( cbCertStatus.firstChild ) {
			cbCertStatus.removeChild( cbCertStatus.firstChild );
		}

		if ( vis === 'private' ) {
			const opt = document.createElement( 'option' );
			opt.value = 'private';
			opt.textContent = badgeData.labelPrivate || '';
			opt.selected = true;
			cbCertStatus.appendChild( opt );
			cbCertStatus.disabled = true;
			return;
		}

		cbCertStatus.disabled = false;
		const selectVal = originalPostStatus === 'private' ? 'draft' : originalPostStatus;

		normalOptions.forEach( function ( item ) {
			const opt = document.createElement( 'option' );
			opt.value = item.value;
			opt.textContent = item.label;
			if (
				item.value === selectVal ||
				( item.value === 'publish' && selectVal === 'future' ) ||
				( item.value === 'future' && selectVal === 'publish' )
			) {
				opt.selected = true;
			}
			cbCertStatus.appendChild( opt );
		} );
	}

	if ( cbCertVisibility ) {
		cbCertVisibility.addEventListener( 'change', function () {
			const vis = this.value;
			if ( passwordField ) {
				passwordField.style.display = vis === 'password' ? '' : 'none';
			}
			rebuildStatusSelect( vis );
			updateBadge();
		} );
	}

	if ( cbCertStatus ) {
		cbCertStatus.addEventListener( 'change', updateBadge );
	}

	function updateBadge() {
		if ( ! statusBadge ) {
			return;
		}
		const st = cbCertStatus ? cbCertStatus.value : originalPostStatus;
		statusBadge.className = 'cb-cert-status-badge cb-cert-status-badge--' + st;
		const labelMap = {
			publish: badgeData.labelPublish,
			draft: badgeData.labelDraft,
			pending: badgeData.labelPending,
			future: badgeData.labelFuture,
			private: badgeData.labelPrivate,
		};
		statusBadge.textContent = labelMap[ st ] || st;
	}

	if ( publishBtn ) {
		publishBtn.addEventListener( 'click', function () {
			saveCertificateAndReload( getStatus(), publishBtn );
		} );
	}

	function saveCertificateAndReload( status, btn ) {
		const titleVal = titleInputEl ? titleInputEl.value : '';
		const priceEl = document.getElementById( '_lp_certificate_price' );
		const price = parseFloat( ( priceEl && priceEl.value ) || 0 );
		const thumbnail = getThumbUrl();

		if ( ! titleVal.trim() ) {
			lpToastify.show( publishBtnData.textTitleRequired || '', 'error' );
			return;
		}

		lpUtils.lpSetLoadingEl( btn, 1 );
		syncDateToHiddenFields();

		let postDate = '';
		const elMm = document.getElementById( 'mm' );
		const elJj = document.getElementById( 'jj' );
		const elAa = document.getElementById( 'aa' );
		const elHh = document.getElementById( 'hh' );
		const elMn = document.getElementById( 'mn' );
		const elSs = document.getElementById( 'ss' );
		if ( elMm && elJj && elAa && elHh && elMn && elSs ) {
			postDate = elAa.value + '-' + elMm.value + '-' + elJj.value + ' ' +
				elHh.value + ':' + elMn.value + ':' + elSs.value;
		}

		fetchCertAJAX( 'save_certificate', {
			post_id: certId,
			title: titleVal,
			status: status,
			price: price,
			thumbnail: thumbnail,
			post_date: postDate,
			visibility: cbCertVisibility ? cbCertVisibility.value : 'public',
			post_password: ( document.getElementById( 'cb-cert-post-password' ) || {} ).value || '',
		}, function () {
			lpUtils.lpSetLoadingEl( btn, 0 );
			setTimeout( () => {
				window.location.reload();
			}, 1000 );
		} );
	}

	const thumbInput = document.getElementById( '_lp_cert_thumbnail' );

	function openMediaFrame() {
		const frame = wp.media( {
			title: thumbContainerData.textSelectThumbnail || '',
			multiple: false,
			library: { type: 'image' },
		} );

		frame.on( 'select', function () {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			if ( thumbInput ) {
				thumbInput.value = attachment.url;
			}
			renderThumbnailPreview( attachment.url );
		} );

		frame.open();
	}

	function buildThumbnailPreview( url ) {
		const preview = document.createElement( 'div' );
		preview.className = 'lp-cert-thumbnail-preview';

		const img = document.createElement( 'img' );
		img.src = url;
		img.alt = '';
		preview.appendChild( img );

		const removeBtn = document.createElement( 'button' );
		removeBtn.type = 'button';
		removeBtn.className = 'lp-cert-remove-thumbnail';
		removeBtn.title = thumbContainerData.textRemove || '';
		removeBtn.textContent = '×';
		preview.appendChild( removeBtn );

		removeBtn.addEventListener( 'click', function () {
			if ( thumbInput ) {
				thumbInput.value = '';
			}
			renderUploadArea();
		} );

		img.addEventListener( 'click', openMediaFrame );

		return preview;
	}

	function renderThumbnailPreview( url ) {
		if ( ! thumbContainer ) {
			return;
		}
		const old = thumbContainer.querySelector( '.lp-cert-thumbnail-upload-area' );
		if ( old ) {
			old.remove();
		}
		const oldPreview = thumbContainer.querySelector( '.lp-cert-thumbnail-preview' );
		if ( oldPreview ) {
			oldPreview.remove();
		}
		thumbContainer.appendChild( buildThumbnailPreview( url ) );
	}

	function buildUploadArea() {
		const area = document.createElement( 'div' );
		area.className = 'lp-cert-thumbnail-upload-area lp-cert-upload-thumbnail';

		const iconWrap = document.createElement( 'span' );
		iconWrap.className = 'upload-icon';
		const iconImg = document.createElement( 'img' );
		iconImg.src = thumbContainerData.uploadIconUrl || '';
		iconImg.width = 32;
		iconImg.height = 32;
		iconImg.alt = '';
		iconWrap.appendChild( iconImg );
		area.appendChild( iconWrap );

		const textP = document.createElement( 'p' );
		textP.className = 'upload-text';
		const linkSpan = document.createElement( 'span' );
		linkSpan.className = 'upload-link';
		linkSpan.textContent = thumbContainerData.textClickToUpload || '';
		textP.appendChild( linkSpan );
		textP.appendChild( document.createTextNode( ' ' + ( thumbContainerData.textOrDragDrop || '' ) ) );
		area.appendChild( textP );

		const hintP = document.createElement( 'p' );
		hintP.className = 'upload-hint';
		hintP.textContent = thumbContainerData.textJpgPng || '';
		area.appendChild( hintP );

		area.addEventListener( 'click', openMediaFrame );

		return area;
	}

	function renderUploadArea() {
		if ( ! thumbContainer ) {
			return;
		}
		const oldPreview = thumbContainer.querySelector( '.lp-cert-thumbnail-preview' );
		if ( oldPreview ) {
			oldPreview.remove();
		}
		const oldArea = thumbContainer.querySelector( '.lp-cert-thumbnail-upload-area' );
		if ( oldArea ) {
			oldArea.remove();
		}
		thumbContainer.appendChild( buildUploadArea() );
	}

	if ( thumbContainer ) {
		const uploadArea = thumbContainer.querySelector( '.lp-cert-upload-thumbnail' );
		if ( uploadArea ) {
			uploadArea.addEventListener( 'click', openMediaFrame );
		}
		const removeBtn = thumbContainer.querySelector( '.lp-cert-remove-thumbnail' );
		if ( removeBtn ) {
			removeBtn.addEventListener( 'click', function () {
				if ( thumbInput ) {
					thumbInput.value = '';
				}
				renderUploadArea();
			} );
		}
		const previewImg = thumbContainer.querySelector( '.lp-cert-thumbnail-preview img' );
		if ( previewImg ) {
			previewImg.addEventListener( 'click', openMediaFrame );
		}
	}

	const moreMenuTrigger = document.querySelector( '.cb-cert-more-menu__trigger' );
	const moreMenuDropdown = document.querySelector( '.cb-cert-more-menu__dropdown' );
	if ( moreMenuTrigger && moreMenuDropdown ) {
		moreMenuTrigger.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			moreMenuDropdown.classList.toggle( 'is-open' );
		} );
		document.addEventListener( 'click', function ( e ) {
			if ( ! e.target.closest( '.cb-cert-more-menu' ) ) {
				moreMenuDropdown.classList.remove( 'is-open' );
			}
		} );
	}

	const headerTrashBtn = document.querySelector( '.cb-cert-more-menu .submitdelete' );
	if ( headerTrashBtn ) {
		headerTrashBtn.addEventListener( 'click', async function ( e ) {
			e.preventDefault();
			const popupData = headerTrashBtn.dataset;
			if ( moreMenuDropdown ) {
				moreMenuDropdown.classList.remove( 'is-open' );
			}

			const result = await window.lpCertConfirm( {
				title: popupData.popupTitle || '',
				text: popupData.popupText || '',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: popupData.popupConfirm || '',
				cancelButtonText: popupData.popupCancel || '',
				reverseButtons: true,
			} );
			if ( ! result || ! result.isConfirmed ) {
				return;
			}

			fetchCertAJAX( 'change_status_certificate', {
				certificate_id: parseInt( popupData.certId, 10 ) || certId,
				status: 'trash',
			}, function () {
				window.location.href = popupData.backUrl || '';
			} );
		} );
	}

	const titleText = document.querySelector( '.cb-cert-title-text' );
	const titleTextData = titleText ? titleText.dataset : {};
	if ( titleInputEl && titleText ) {
		titleInputEl.addEventListener( 'input', function () {
			titleText.textContent = this.value || ( titleInputData.textPlaceholderTitle || titleTextData.textPlaceholderTitle || '' );
		} );
	}
}() );
