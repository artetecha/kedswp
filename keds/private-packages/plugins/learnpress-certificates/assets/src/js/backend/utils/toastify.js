const ICON_MAP = {
	success: 'lp-cert-icon-success',
	info: 'lp-cert-icon-info',
	warning: 'lp-cert-icon-warning',
	danger: 'lp-cert-icon-danger',
	error: 'lp-cert-icon-danger',
};

const TITLE_MAP = {
	success: 'Success',
	info: 'Info',
	warning: 'Warning',
	danger: 'Error',
	error: 'Error',
};

let toastContainer = null;

function initToastContainer() {
	if ( toastContainer ) {
		return toastContainer;
	}

	toastContainer = document.createElement( 'div' );
	toastContainer.className = 'lp-cert-alert-container';
	document.body.appendChild( toastContainer );

	return toastContainer;
}

export function showToastify( message, type = 'info', title = '', options = {} ) {
	const {
		closable = true,
		duration = 5000,
		position = 'top',
		container = null,
	} = options;

	const toastTitle = title || TITLE_MAP[ type ] || 'Info';
	const toast = createToastElement( message, type, toastTitle, closable );

	if ( position === 'inline' && container ) {
		container.insertBefore( toast, container.firstChild );
	} else {
		const toastContainer = initToastContainer();
		toastContainer.appendChild( toast );
	}

	requestAnimationFrame( () => {
		toast.classList.add( 'is-visible' );
	} );

	if ( duration > 0 ) {
		setTimeout( () => {
			closeToastify( toast );
		}, duration );
	}

	return toast;
}

function createToastElement( message, type, title, closable ) {
	const toast = document.createElement( 'div' );
	toast.className = `lp-cert-alert lp-cert-alert--${ type }`;

	const iconClass = ICON_MAP[ type ] || ICON_MAP.info;

	let html = `
		<span class="lp-cert-alert__icon ${ iconClass }"></span>
		<div class="lp-cert-alert__content">
			<p class="lp-cert-alert__title">${ title }</p>
			<p class="lp-cert-alert__message">${ message }</p>
		</div>
	`;

	if ( closable ) {
		html += `<button type="button" class="lp-cert-alert__close" aria-label="Close">&times;</button>`;
	}

	toast.innerHTML = html;

	if ( closable ) {
		const closeBtn = toast.querySelector( '.lp-cert-alert__close' );
		closeBtn.addEventListener( 'click', () => {
			closeToastify( toast );
		} );
	}

	return toast;
}

export function closeToastify( toast ) {
	if ( ! toast ) {
		return;
	}

	toast.classList.remove( 'is-visible' );
	toast.classList.add( 'is-closing' );

	setTimeout( () => {
		toast.remove();
	}, 300 );
}
