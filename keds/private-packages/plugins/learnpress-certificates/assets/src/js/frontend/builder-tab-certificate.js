/**
 * JS for builder tab certificate
 *
 * @since 4.2.3
 * @version 1.0.0
 */
import * as lpToastify from 'AssetsJsPath/lpToastify';

const selectors = {
	elCertItem: '.lesson-item[data-certificate-id]',
	elCertExpandedItems: '.certificate-action-expanded__items',
	elCertDuplicate: '.certificate-action-expanded__duplicate',
	elCertTrash: '.certificate-action-expanded__trash',
	elCertPublish: '.certificate-action-expanded__publish',
	elCertDelete: '.certificate-action-expanded__delete',
	elCertActionExpanded: '.certificate-action-expanded',
	elCertStatus: '.lesson-status',
};

const init = () => {
	if ( window._lpCertTabLoaded ) {
		return;
	}
	window._lpCertTabLoaded = true;

	document.addEventListener( 'click', ( e ) => {
		const expandedBtn = e.target.closest( `${ selectors.elCertItem } ${ selectors.elCertActionExpanded }` );
		if ( expandedBtn ) {
			const certItem = expandedBtn.closest( selectors.elCertItem );
			const expandedItems = certItem?.querySelector( selectors.elCertExpandedItems );

			if ( ! expandedItems ) {
				return;
			}

			closeAllExpanded( expandedItems );

			const willOpen = ! expandedItems.classList.contains( 'active' );
			if ( willOpen ) {
				expandedItems.classList.add( 'active' );
				expandedBtn.classList.add( 'active' );
				setExpandedDirection( expandedItems );
			} else {
				expandedItems.classList.remove( 'active', 'is-dropup' );
				expandedBtn.classList.remove( 'active' );
			}
			return;
		}

		const elDuplicate = e.target.closest( selectors.elCertDuplicate );
		if ( elDuplicate ) {
			handleDuplicate( elDuplicate );
			return;
		}

		const elPublish = e.target.closest( selectors.elCertPublish );
		if ( elPublish ) {
			handleChangeStatus( elPublish, 'publish' );
			return;
		}

		const elTrash = e.target.closest( selectors.elCertTrash );
		if ( elTrash ) {
			handleChangeStatus( elTrash, 'trash' );
			return;
		}

		const elDelete = e.target.closest( selectors.elCertDelete );
		if ( elDelete ) {
			handleDelete( elDelete );
			return;
		}

		if ( ! e.target.closest( selectors.elCertActionExpanded ) ) {
			closeAllExpanded();
		}
	} );
};

const getCertId = ( el ) => {
	const certItem = el.closest( selectors.elCertItem );
	return certItem?.dataset?.certificateId || '';
};

const swalFromEl = ( el ) => {
	const ds = el.dataset;
	return {
		title: ds.popupTitle || '',
		text: ds.popupText || '',
		icon: 'warning',
		showCloseButton: true,
		showCancelButton: true,
		confirmButtonText: ds.popupConfirm || '',
		cancelButtonText: ds.popupCancel || '',
		reverseButtons: true,
	};
};

const handleDuplicate = ( el ) => {
	const certId = getCertId( el );
	if ( ! certId ) {
		return;
	}

	closeAllExpanded();

	window.lpCertConfirm( swalFromEl( el ) ).then( ( result ) => {
		if ( result.isConfirmed ) {
			sendAjax( el, 'duplicate_certificate', { certificate_id: certId } );
		}
	} );
};

const handleChangeStatus = ( el, status ) => {
	const certId = getCertId( el );
	if ( ! certId ) {
		return;
	}

	closeAllExpanded();

	if ( status === 'trash' ) {
		window.lpCertConfirm( swalFromEl( el ) ).then( ( result ) => {
			if ( result.isConfirmed ) {
				sendAjax( el, 'change_status_certificate', { certificate_id: certId, status } );
			}
		} );
	} else {
		sendAjax( el, 'change_status_certificate', { certificate_id: certId, status } );
	}
};

const handleDelete = ( el ) => {
	const certId = getCertId( el );
	if ( ! certId ) {
		return;
	}

	closeAllExpanded();

	window.lpCertConfirm( swalFromEl( el ) ).then( ( result ) => {
		if ( result.isConfirmed ) {
			sendAjax( el, 'change_status_certificate', { certificate_id: certId, status: 'delete' } );
		}
	} );
};

const sendAjax = ( el, action, extraData = {} ) => {
	setLoading( el, true );

	const callBack = {
		success: ( response ) => {
			const { status, message, data: resData } = response;
			lpToastify.show( message, status );

			if ( action === 'change_status_certificate' ) {
				if ( extraData.status === 'delete' ) {
					const certLi = el.closest( '.lesson' );
					if ( certLi ) {
						certLi.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
						certLi.style.opacity = '0';
						certLi.style.transform = 'translateX(160px)';
						setTimeout( () => certLi.remove(), 400 );
					}
				} else if ( resData?.status ) {
					const certLi = el.closest( '.lesson' );
					updateStatusUI( certLi, resData.status );
				}
			} else if ( action === 'duplicate_certificate' ) {
				if ( response.status === 'success' && resData?.redirect_url ) {
					window.location.href = resData.redirect_url;
				}
			}
		},
		error: ( error ) => {
			lpToastify.show( error.message || error, 'error' );
		},
		completed: () => {
			setLoading( el, false );
		},
	};

	window.lpAJAXG.fetchAJAX( { action, is_course_builder: 1, ...extraData }, callBack );
};

const updateStatusUI = ( elLi, status ) => {
	if ( ! elLi ) {
		return;
	}
	const elStatus = elLi.querySelector( selectors.elCertStatus );
	if ( elStatus ) {
		elStatus.className = 'lesson-status ' + status;
		elStatus.textContent = status;
	}
};

const setLoading = ( el, loading ) => {
	if ( typeof window.lpUtils?.lpSetLoadingEl === 'function' ) {
		window.lpUtils.lpSetLoadingEl( el, loading ? 1 : 0 );
	}
};

const closeAllExpanded = ( excludeElement = null ) => {
	document.querySelectorAll( `${ selectors.elCertExpandedItems }.active` ).forEach( ( item ) => {
		if ( item === excludeElement ) {
			return;
		}
		item.classList.remove( 'active', 'is-dropup' );
		const certItem = item.closest( selectors.elCertItem );
		const expandedBtn = certItem?.querySelector( selectors.elCertActionExpanded );
		if ( expandedBtn ) {
			expandedBtn.classList.remove( 'active' );
		}
	} );
};

const setExpandedDirection = ( el ) => {
	if ( ! el ) {
		return;
	}
	el.classList.remove( 'is-dropup' );
	const lesson = el.closest( '.lesson' );
	if ( lesson?.matches( ':last-child' ) ) {
		el.classList.add( 'is-dropup' );
	}
};

init();
