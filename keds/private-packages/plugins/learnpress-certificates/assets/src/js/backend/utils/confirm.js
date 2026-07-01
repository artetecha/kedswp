const ICON_TYPES = {
	warning: {
		class: 'lp-cert-confirm__icon--warning',
		content: '!',
	},
	error: {
		class: 'lp-cert-confirm__icon--error',
		content: '&times;',
	},
	success: {
		class: 'lp-cert-confirm__icon--success',
		content: '&#10003;',
	},
	info: {
		class: 'lp-cert-confirm__icon--info',
		content: 'i',
	},
	question: {
		class: 'lp-cert-confirm__icon--question',
		content: '?',
	},
};

let confirmOverlay = null;

function createConfirmElement( options ) {
	const {
		title = 'Are you sure?',
		text = '',
		html = '',
		icon = 'warning',
		showCancelButton = true,
		showConfirmButton = true,
		confirmButtonText = 'Yes',
		cancelButtonText = 'Cancel',
		confirmButtonColor = '#e02200',
		cancelButtonColor = '#6e7881',
		reverseButtons = false,
		showCloseButton = true,
	} = options;

	const overlay = document.createElement( 'div' );
	overlay.className = 'lp-cert-confirm-overlay';

	const popup = document.createElement( 'div' );
	popup.className = 'lp-cert-confirm-popup';
	popup.setAttribute( 'role', 'dialog' );
	popup.setAttribute( 'aria-modal', 'true' );
	popup.setAttribute( 'aria-labelledby', 'lp-cert-confirm-title' );

	let popupHTML = '';

	if ( showCloseButton ) {
		popupHTML += `<button type="button" class="lp-cert-confirm__close" aria-label="Close">&times;</button>`;
	}

	if ( icon && ICON_TYPES[ icon ] ) {
		const iconData = ICON_TYPES[ icon ];
		popupHTML += `
			<div class="lp-cert-confirm__icon ${ iconData.class }">
				<span class="lp-cert-confirm__icon-content">${ iconData.content }</span>
			</div>
		`;
	}

	if ( title ) {
		popupHTML += `<h2 class="lp-cert-confirm__title" id="lp-cert-confirm-title"></h2>`;
	}

	if ( html ) {
		popupHTML += `<div class="lp-cert-confirm__content">${ html }</div>`;
	} else if ( text ) {
		popupHTML += `<div class="lp-cert-confirm__content"></div>`;
	}

	const buttons = [];
	if ( showCancelButton ) {
		buttons.push( {
			type: 'cancel',
			text: cancelButtonText,
			color: cancelButtonColor,
		} );
	}
	if ( showConfirmButton ) {
		buttons.push( {
			type: 'confirm',
			text: confirmButtonText,
			color: confirmButtonColor,
		} );
	}

	if ( reverseButtons ) {
		buttons.reverse();
	}

	if ( buttons.length > 0 ) {
		popupHTML += `<div class="lp-cert-confirm__actions">`;
		buttons.forEach( ( btn ) => {
			const style = btn.color ? `--lp-cert-btn-color: ${ btn.color }` : '';
			popupHTML += `
				<button type="button"
					class="lp-cert-confirm__btn lp-cert-confirm__btn--${ btn.type }"
					style="${ style }"
					data-action="${ btn.type }">
					${ btn.text }
				</button>
			`;
		} );
		popupHTML += `</div>`;
	}

	popup.innerHTML = popupHTML;

	if ( title ) {
		const titleEl = popup.querySelector( '.lp-cert-confirm__title' );
		if ( titleEl ) {
			titleEl.textContent = title;
		}
	}

	if ( ! html && text ) {
		const contentEl = popup.querySelector( '.lp-cert-confirm__content' );
		if ( contentEl ) {
			contentEl.textContent = text;
		}
	}

	overlay.appendChild( popup );

	return overlay;
}

function showConfirm( overlay ) {
	document.body.appendChild( overlay );
	document.body.classList.add( 'lp-cert-confirm-open' );

	requestAnimationFrame( () => {
		overlay.classList.add( 'is-visible' );
		const popup = overlay.querySelector( '.lp-cert-confirm-popup' );
		if ( popup ) {
			popup.classList.add( 'is-visible' );
		}
	} );
}

function hideConfirm( overlay ) {
	overlay.classList.remove( 'is-visible' );
	const popup = overlay.querySelector( '.lp-cert-confirm-popup' );
	if ( popup ) {
		popup.classList.remove( 'is-visible' );
	}

	setTimeout( () => {
		overlay.remove();
		document.body.classList.remove( 'lp-cert-confirm-open' );
		confirmOverlay = null;
	}, 200 );
}

export function lpCertConfirm( options = {} ) {
	return new Promise( ( resolve ) => {
		if ( confirmOverlay ) {
			hideConfirm( confirmOverlay );
		}

		const overlay = createConfirmElement( options );
		confirmOverlay = overlay;

		const handleAction = ( action ) => {
			hideConfirm( overlay );
			if ( action === 'confirm' ) {
				resolve( { isConfirmed: true, isDismissed: false } );
			} else {
				resolve( { isConfirmed: false, isDismissed: true } );
			}
		};

		overlay.querySelectorAll( '.lp-cert-confirm__btn' ).forEach( ( btn ) => {
			btn.addEventListener( 'click', () => {
				handleAction( btn.dataset.action );
			} );
		} );

		const closeBtn = overlay.querySelector( '.lp-cert-confirm__close' );
		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', () => {
				handleAction( 'cancel' );
			} );
		}

		overlay.addEventListener( 'click', ( e ) => {
			if ( e.target === overlay ) {
				handleAction( 'cancel' );
			}
		} );

		const handleKeydown = ( e ) => {
			if ( e.key === 'Escape' ) {
				document.removeEventListener( 'keydown', handleKeydown );
				handleAction( 'cancel' );
			}
		};
		document.addEventListener( 'keydown', handleKeydown );

		showConfirm( overlay );
	} );
}
