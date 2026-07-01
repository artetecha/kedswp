import {
	renderCertificateFromJSON,
	downloadCertificate,
	downloadCertificateAsPDF,
} from '../backend/builder/utils/certificate-download';
import { jsPDF } from 'jspdf';

( function( $ ) {
	let $html, el_lp_data_config_cer, el_show_cer_popup_first,
		el_form_certificate_button, el_popup_cert, el_single_certificate,
		el_social_cert, el_need_upload_cert_img_to_server;

	let el_form_lp_cert_add_to_cart_woo;
	let el_form_lp_cert_add_to_cart_lp;

	window.LP_Certificate = function( el, options ) {
		const $el = $( el );
		let canvas = null;
		let el_certificate_result;

		let el_certificate_actions, el_download;
		let name_file_download = 'certificate';

		async function init() {
			el_certificate_actions = $( '.certificate-actions' );

			// If you already have the image, don't render the canvas.
			const $cachedImg = $el.find( '.certificate-result' );
			if ( $cachedImg.length ) {
				el_certificate_result = $cachedImg;
				$el.addClass( 'canvas-preview-ready has-cached-image' );
				onReady();

				$( document ).on( 'click', '[data-cert="' + $el.attr( 'id' ) + '"]', function( e ) {
					e.preventDefault();
					download();
				} );
				return;
			}

			try {
				const canvasEl = $el.find( 'canvas' ).get( 0 );
				if ( ! canvasEl ) {
					console.error( 'LP_Certificate: canvas element not found' );
					return;
				}

				const result = await renderCertificateFromJSON( options, { canvasEl } );
				canvas = result.canvas;

				$el.addClass( 'canvas-preview-ready' );

				if ( canvas ) {
					const data = canvas.toDataURL( { format: 'png', quality: 1, multiplier: 2 } );
					const $img = $( '<img class="certificate-result" />' ).insertBefore( '#' + $el[ 0 ].id );
					el_certificate_result = $img;
					$img.attr( 'src', data );
					$el.hide();
				}

				onReady();
			} catch ( e ) {
				console.error( 'LP_Certificate init error:', e );
			}

			$( document ).on( 'click', '[data-cert="' + $el.attr( 'id' ) + '"]', function( e ) {
				e.preventDefault();
				download();
			} );
		}

		function markCertificateReady() {
			if ( el_form_certificate_button && el_form_certificate_button.length ) {
				el_form_certificate_button.removeClass( 'cert-image-pending' )
					.find( '.lp-button' ).removeClass( 'loading' );
			}

			$( document ).triggerHandler( 'learn-press/certificates/ready' );
		}

		function updateSocialShareLinks( certUrl ) {
			if ( ! certUrl || ! el_social_cert || ! el_social_cert.length ) {
				return;
			}

			$.each( el_social_cert, function() {
				const elLink = $( this ).find( 'a' );
				if ( ! elLink.length ) {
					return;
				}

				const baseHref = elLink.attr( 'data-base-href' ) || elLink.attr( 'href' ) || '';
				if ( ! baseHref ) {
					return;
				}

				elLink.attr( 'data-base-href', baseHref );
				elLink.attr( 'href', baseHref + certUrl );
			} );
		}

		function onReady() {
			// Save only when there are no images.
			const hasPendingSave = $el.find( 'input[name=need_upload_cert_img_to_server]' ).length > 0;
			if ( hasPendingSave ) {
				saveImageToServer();
			} else {
				updateSocialShareLinks( el_certificate_result?.attr( 'src' ) );
				markCertificateReady();
				if ( el_social_cert && el_social_cert.length ) {
					el_social_cert.show();
				}
			}

			if ( el_certificate_actions.length ) {
				el_download = el_certificate_actions.find( '.download' );
			}

			$el.find( '.lp-data-config-cer' ).addClass( 'loaded' );

			$( document ).triggerHandler( 'learn-press/certificates/loaded' );
		}

		function downloadImageFromSource() {
			const imgSrc = el_certificate_result?.attr( 'src' );
			if ( ! imgSrc ) {
				return;
			}

			const link = document.createElement( 'a' );
			link.href = imgSrc;
			link.download = name_file_download + '.png';
			document.body.appendChild( link );
			link.click();
			document.body.removeChild( link );
		}

		function downloadPdfFromSource() {
			const imgSrc = el_certificate_result?.attr( 'src' );
			if ( ! imgSrc ) {
				return;
			}

			const image = new Image();
			image.onload = function() {
				const naturalW = image.naturalWidth || image.width;
				const naturalH = image.naturalHeight || image.height;

				const baseW = ( options && options.canvas_width ) || naturalW;
				const baseH = ( options && options.canvas_height ) || naturalH;

				const pxToMm = 25.4 / 96;
				const pdfWidth = baseW * pxToMm;
				const pdfHeight = baseH * pxToMm;
				const orientation = pdfWidth >= pdfHeight ? 'landscape' : 'portrait';

				const doc = new jsPDF( {
					orientation,
					unit: 'mm',
					format: [ pdfWidth, pdfHeight ],
				} );

				doc.addImage( image, 'PNG', 0, 0, pdfWidth, pdfHeight, '', 'FAST' );
				doc.save( name_file_download + '.pdf' );
			};
			image.src = imgSrc;
		}

		function download() {
			if ( ! el_download || ! el_download.length ) {
				return;
			}

			if ( undefined !== options.name ) {
				name_file_download = options.name;
			}

			const downloadType = el_download.data( 'type-download' );

			if ( canvas && downloadType === 'pdf' ) {
				downloadCertificateAsPDF( canvas, {
					filename: name_file_download + '.pdf',
					multiplier: 2,
				} );
			} else if ( canvas ) {
				downloadCertificate( canvas, {
					filename: name_file_download + '.png',
					format: 'png',
					quality: 1,
					multiplier: 2,
				} );
			} else if ( downloadType === 'pdf' ) {
				downloadPdfFromSource();
			} else {
				downloadImageFromSource();
			}
		}

		function saveImageToServer() {
			if ( ! el_certificate_result || ! el_certificate_result.length ) {
				return;
			}

			let imageSaved = false;

			const data = {
				action: 'lpCertCreateImage',
				data64: el_certificate_result.attr( 'src' ),
				name_image: options.key_cer,
			};

			$.ajax( {
				url: localize_lp_cer_js.url_ajax,
				data,
				method: 'post',
				dataType: 'json',
				beforeSend() {
					el_certificate_actions.append( '<li class="fa fa-spinner">Loading share social...</li>' );
				},
				success( rs ) {
					if ( rs.code === 1 ) {
						imageSaved = true;

						if ( rs.url_cert && el_certificate_result && el_certificate_result.length ) {
							el_certificate_result.attr( 'src', rs.url_cert );
						}

						if ( el_social_cert && el_social_cert.length ) {
							updateSocialShareLinks( rs.url_cert );
							el_social_cert.show();
						}
						markCertificateReady();

						$( document ).triggerHandler( 'learn-press/certificates/image-saved', [ rs.url_cert ] );
					}
				},
				complete() {
					if ( ! imageSaved ) {
						markCertificateReady();
					}

					el_certificate_actions.find( '.fa-spinner' ).remove();
				},
				error( e ) {
					console.log( e );
				},
			} );
		}

		init();
	};

	function getElements() {
		$html = $( 'html, body' );
		el_lp_data_config_cer = $( '.lp-data-config-cer' );
		el_show_cer_popup_first = $( 'input[name=f_auto_show_cer_popup_first]' );
		el_form_certificate_button = $( 'form[name="certificate-form-button"]' );
		el_popup_cert = $( '#certificate-popup' );
		el_single_certificate = $( '.single-certificate-content' );
		el_social_cert = $( '.share-social-cert' );
		el_need_upload_cert_img_to_server = $( 'input[name=need_upload_cert_img_to_server]' );

		el_form_lp_cert_add_to_cart_woo = $( 'form[name=form-lp-cert-add-to-cart-woo]' );
		el_form_lp_cert_add_to_cart_lp = $( 'form[name=form-lp-cert-purchase]' );
	}

	function popupCer() {
		if ( el_popup_cert.length ) {
			let isReady = ! el_form_certificate_button.hasClass( 'cert-image-pending' );
			let hasAutoOpened = false;

			function close() {
				el_popup_cert.fadeOut( function() {
					$html.css( 'overflow', 'auto' );
				} );
			}

			function open() {
				if ( ! isReady ) {
					return;
				}
				$html.css( 'overflow', 'hidden' );
				el_popup_cert.fadeIn();
			}

			el_form_certificate_button.on( 'submit', function( e ) {
				e.preventDefault();
				open();
			} );

			$( document ).on( 'learn-press/certificates/loaded', function() {
				isReady = ! el_form_certificate_button.hasClass( 'cert-image-pending' );
				el_popup_cert.addClass( 'ready' ).hide();

				if ( ! el_popup_cert.data( 'popup-bound' ) ) {
					el_popup_cert.data( 'popup-bound', true );
					$html
						.on( 'keyup', function( e ) {
							if ( e.keyCode === 27 ) {
								close();
							}
						} )
						.on( 'click', '.close-popup', function( e ) {
							close();
							e.preventDefault();
						} );
				}

				if ( el_show_cer_popup_first.length && isReady && ! hasAutoOpened ) {
					hasAutoOpened = true;
					open();
				}
			} );

			$( document ).on( 'learn-press/certificates/ready', function() {
				isReady = true;

				if ( el_popup_cert.hasClass( 'ready' ) && el_show_cer_popup_first.length && ! hasAutoOpened ) {
					hasAutoOpened = true;
					open();
				}
			} );

			$( document ).on( 'learn-press/certificates/image-saved', function() {
				isReady = true;

				if ( el_show_cer_popup_first.length && ! hasAutoOpened ) {
					hasAutoOpened = true;
					open();
				}
			} );
		}
	}

	function addCertToCartWoo( form ) {
		const lang = lpData.urlParams.lang ? `?lang=${ lpData.urlParams.lang }` : '';
		const btn = form.querySelector( '.btn-add-cert-to-cart-woo' );
		const formData = new FormData( form );
		const data = Object.fromEntries( formData.entries() );
		data.action = 'lp_cert_add_to_cart_woo';

		$.ajax( {
			url: localize_lp_cer_js.url_ajax + lang,
			data,
			method: 'post',
			beforeSend() {
				btn.classList.add( 'loading' );
			},
			success( rs ) {
				if ( rs.code === 1 ) {
					if ( undefined !== rs.redirect_to ) {
						window.location.replace( rs.redirect_to );
					} else {
						form.closest( '.wrapper-lp-cert-add-to-cart-woo' ).insertAdjacentHTML( 'beforeend', rs.button_view_cart );
						form.remove();
					}
				} else {
					alert( rs.message );
				}
			},
			error( e ) {
				console.log( e );
			},
			complete() {
				btn.classList.remove( 'loading' );
			},
		} );
	}

	function addCertToCartLP( form ) {
		let message = '';
		let status = '';
		const formData = new FormData( form );
		const data = Object.fromEntries( formData.entries() );
		const btn = form.querySelector( '.btn-purchase-certificate' );
		btn.classList.add( 'loading' );

		const newElMessage = document.createElement( 'div' );
		newElMessage.classList.add( 'learn-press-message' );

		wp.apiFetch( {
			path: '/lp/v1/certificate/purchase',
			method: 'POST',
			data,
		} ).then( ( res ) => {
			const { data } = res;
			status = res.status;
			message = res.message;

			newElMessage.classList.add( status );
			newElMessage.innerHTML = message;
			form.insertAdjacentElement( 'beforeend', newElMessage );

			if ( undefined !== status && status === 'success' ) {
				if ( data.redirect ) {
					setTimeout( function() {
						window.location.href = data.redirect;
					}, 800 );
				}
			}
		} ).catch( ( err ) => {
			newElMessage.classList.add( 'error' );
			newElMessage.innerHTML = err.message;
			form.insertAdjacentElement( 'beforeend', newElMessage );
		} ).then( () => {
			btn.remove();
		} );
	}

	$( document ).ready( function() {
		getElements();
		el_social_cert.hide();

		if ( ! el_show_cer_popup_first.length ) {
			el_form_certificate_button.css( 'display', 'inline-block' );
		}

		popupCer();

		if ( el_lp_data_config_cer.length ) {
			try {
				$.each( el_lp_data_config_cer, function() {
					const data_config_cer = JSON.parse( $( this ).val() ) || {};

					$( this ).val( '' );
					const id_div_parent = '#' + $( this ).closest( 'div' ).attr( 'id' );

					LP_Certificate( id_div_parent, data_config_cer );
				} );
			} catch ( e ) {
				console.log( e );
			}
		}

		document.addEventListener( 'click', function( e ) {
			const shareBtn = e.target.closest( '.social-share-link' );
			if ( ! shareBtn ) {
				return;
			}
			e.preventDefault();
			const url = shareBtn.dataset.certUrl || '';
			if ( ! url ) {
				return;
			}
			const showTooltip = function() {
				const tooltip = shareBtn.querySelector( '.lp-cert-share-tooltip' );
				if ( tooltip ) {
					tooltip.classList.add( 'is-visible' );
					setTimeout( function() {
						tooltip.classList.remove( 'is-visible' );
					}, 2000 );
				}
			};
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( url ).then( showTooltip );
			} else {
				const textarea = document.createElement( 'textarea' );
				textarea.value = url;
				textarea.style.position = 'fixed';
				textarea.style.opacity = '0';
				document.body.appendChild( textarea );
				textarea.select();
				document.execCommand( 'copy' );
				document.body.removeChild( textarea );
				showTooltip();
			}
		} );

		document.addEventListener( 'submit', function( e ) {
			const el = e.target;

			if ( el.getAttribute( 'name' ) === 'form-lp-cert-purchase' ) {
				e.preventDefault();
				addCertToCartLP( el );
			} else if ( el.getAttribute( 'name' ) === 'form-lp-cert-add-to-cart-woo' ) {
				e.preventDefault();
				addCertToCartWoo( el );
			}
		} );
	} );
}( jQuery ) );
