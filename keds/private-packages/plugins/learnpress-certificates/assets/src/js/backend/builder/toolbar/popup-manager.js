let popupContainer = null;
let activePopup = null;
let activePopupOriginalParent = null;
let activeTrigger = null;
let popupLocked = false;

export function lockActivePopup() {
	popupLocked = true;
}

export function unlockActivePopup() {
	popupLocked = false;
}

export function initPopupManager() {
	popupContainer = document.createElement( 'div' );
	popupContainer.className = 'lp-cert-popup-container';
	popupContainer.style.cssText = `
		position: fixed;
		z-index: 100001;
		background: #fff;
		border: 1px solid #ddd;
		border-radius: 6px;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
		display: none;
	`;
	document.body.appendChild( popupContainer );

	document.addEventListener( 'click', handleDocumentClick );
	window.addEventListener( 'scroll', repositionPopup, true );

	const wrapper = document.querySelector( '.lp-cert-builder-canvas-wrapper' );
	if ( wrapper ) {
		const resizeObserver = new ResizeObserver( repositionPopup );
		resizeObserver.observe( wrapper );
	}
}

function positionPopup() {
	if ( ! activeTrigger || ! popupContainer ) {
		return;
	}

	const triggerRect = activeTrigger.getBoundingClientRect();
	const popupRect = popupContainer.getBoundingClientRect();

	let top = triggerRect.bottom + 6;

	if ( top + popupRect.height > window.innerHeight - 4 ) {
		top = triggerRect.top - popupRect.height - 6;
	}

	let left = triggerRect.left;

	if ( left + popupRect.width > window.innerWidth - 4 ) {
		left = triggerRect.right - popupRect.width;
	}

	left = Math.max( 4, Math.min( left, window.innerWidth - popupRect.width - 4 ) );
	top = Math.max( 4, Math.min( top, window.innerHeight - popupRect.height - 4 ) );

	popupContainer.style.top = top + 'px';
	popupContainer.style.left = left + 'px';
}

function repositionPopup() {
	if ( activePopup && activeTrigger ) {
		positionPopup();
	}
}

export function showPopupInContainer( popup, trigger ) {
	if ( ! popupContainer || ! popup ) {
		return;
	}

	if ( activePopup && activePopup !== popup ) {
		hideActivePopup();
	}

	activePopupOriginalParent = popup.parentElement;
	activePopup = popup;
	activeTrigger = trigger;

	popupContainer.innerHTML = '';
	popupContainer.appendChild( popup );
	popupContainer.style.display = 'block';
	popup.classList.add( 'is-visible' );

	if ( trigger ) {
		trigger.classList.add( 'is-active' );
	}

	requestAnimationFrame( () => {
		positionPopup();
	} );
}

export function hideActivePopup() {
	if ( popupLocked || ! activePopup ) {
		return;
	}

	activePopup.classList.remove( 'is-visible' );

	if ( activePopupOriginalParent ) {
		activePopupOriginalParent.appendChild( activePopup );
	}

	if ( popupContainer ) {
		popupContainer.style.display = 'none';
	}

	const triggers = document.querySelectorAll( '.is-active' );
	triggers.forEach( ( el ) => {
		if ( el.classList.contains( 'lp-cert-canvas-toolbar__trigger' ) ||
			 el.classList.contains( 'lp-cert-image-toolbar__trigger' ) ||
			 el.classList.contains( 'lp-cert-svg-toolbar__trigger' ) ||
			 el.classList.contains( 'lp-cert-text-toolbar__trigger' ) ||
			 el.classList.contains( 'lp-cert-text-toolbar__color-trigger' ) ||
			 el.classList.contains( 'lp-cert-svg-toolbar__fill-trigger' ) ||
			 el.classList.contains( 'lp-cert-image-toolbar__position-toggle' ) ||
			 el.classList.contains( 'lp-cert-text-toolbar__position-toggle' ) ||
			 el.classList.contains( 'lp-cert-svg-toolbar__position-toggle' ) ) {
			el.classList.remove( 'is-active' );
		}
	} );

	activePopup = null;
	activePopupOriginalParent = null;
	activeTrigger = null;
}

export function isPopupVisible() {
	return activePopup !== null && activePopup.classList.contains( 'is-visible' );
}

export function getActivePopup() {
	return activePopup;
}

function handleDocumentClick( e ) {
	if ( ! activePopup ) {
		return;
	}

	if ( ! e.target || ! e.target.closest ) {
		return;
	}

	const isClickOnTrigger = e.target.closest( '.lp-cert-canvas-toolbar__trigger' ) ||
							  e.target.closest( '.lp-cert-image-toolbar__trigger' ) ||
							  e.target.closest( '.lp-cert-svg-toolbar__trigger' ) ||
							  e.target.closest( '.lp-cert-text-toolbar__trigger' ) ||
							  e.target.closest( '.lp-cert-text-toolbar__color-trigger' ) ||
							  e.target.closest( '.lp-cert-svg-toolbar__fill-trigger' ) ||
							  e.target.closest( '.lp-cert-image-toolbar__position-toggle' ) ||
							  e.target.closest( '.lp-cert-text-toolbar__position-toggle' ) ||
							  e.target.closest( '.lp-cert-svg-toolbar__position-toggle' );

	const isClickInsidePopup = popupContainer && popupContainer.contains( e.target );

	if ( ! isClickOnTrigger && ! isClickInsidePopup ) {
		hideActivePopup();
	}
}

export function togglePopupInContainer( popup, trigger ) {
	if ( ! popup ) {
		return;
	}

	if ( activePopup === popup && popup.classList.contains( 'is-visible' ) ) {
		hideActivePopup();
	} else {
		showPopupInContainer( popup, trigger );
	}
}
