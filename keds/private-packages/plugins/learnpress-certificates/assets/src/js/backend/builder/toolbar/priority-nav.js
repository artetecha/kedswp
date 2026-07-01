const SELECTORS = {
	toolbarArea: '.lp-cert-builder-toolbar-area',
	content: '.lp-cert-toolbar-content',
	moreWrapper: '.lp-cert-toolbar-more',
	moreBtn: '.lp-cert-toolbar-more__btn',
	moreDropdown: '.lp-cert-toolbar-more__dropdown',
};

let toolbarArea = null;
let content = null;
let moreWrapper = null;
let moreBtn = null;
let moreDropdown = null;
let resizeObserver = null;
let currentToolbarId = null;

export function initPriorityNav() {
	toolbarArea = document.querySelector( SELECTORS.toolbarArea );
	if ( ! toolbarArea ) return;

	content = toolbarArea.querySelector( SELECTORS.content );
	moreWrapper = toolbarArea.querySelector( SELECTORS.moreWrapper );
	moreBtn = toolbarArea.querySelector( SELECTORS.moreBtn );
	moreDropdown = toolbarArea.querySelector( SELECTORS.moreDropdown );

	if ( ! content || ! moreWrapper || ! moreBtn || ! moreDropdown ) return;

	setupEvents();
	setupResizeObserver();

	setTimeout( updatePriorityNav, 100 );
}

function setupEvents() {
	moreBtn.addEventListener( 'click', ( e ) => {
		e.stopPropagation();
		toggleMoreDropdown();
	} );

	document.addEventListener( 'click', ( e ) => {
		if ( ! moreWrapper.contains( e.target ) ) {
			closeMoreDropdown();
		}
	} );

	window.addEventListener( 'scroll', closeMoreDropdown, true );
}

function setupResizeObserver() {
	if ( window.ResizeObserver ) {
		resizeObserver = new ResizeObserver( debounce( updatePriorityNav, 100 ) );
		resizeObserver.observe( toolbarArea );
	} else {
		window.addEventListener( 'resize', debounce( updatePriorityNav, 150 ) );
	}
}

function restoreItemsToToolbar() {
	const dropdownItems = Array.from( moreDropdown.children );

	dropdownItems.forEach( item => {
		const originalParentId = item.dataset.originalParent;
		if ( originalParentId ) {
			const originalParent = document.getElementById( originalParentId );
			if ( originalParent ) {
				originalParent.appendChild( item );
			}
		}
		delete item.dataset.originalParent;
	} );
}

function updatePriorityNav() {
	if ( ! content || ! moreWrapper || ! moreDropdown ) return;

	const visibleToolbar = content.querySelector( '.is-visible' );

	if ( ! visibleToolbar ) {
		restoreItemsToToolbar();
		moreWrapper.style.display = 'none';
		currentToolbarId = null;
		return;
	}

	const controls = visibleToolbar.querySelector( '[class$="__controls"]' );
	if ( ! controls ) {
		restoreItemsToToolbar();
		moreWrapper.style.display = 'none';
		currentToolbarId = null;
		return;
	}

	if ( ! controls.id ) {
		controls.id = 'toolbar-controls-' + Math.random().toString( 36 ).substr( 2, 9 );
	}

	if ( currentToolbarId && currentToolbarId !== controls.id ) {
		restoreItemsToToolbar();
	}

	currentToolbarId = controls.id;

	const dropdownItems = Array.from( moreDropdown.children );
	dropdownItems.forEach( item => {
		if ( item.dataset.originalParent === controls.id ) {
			controls.appendChild( item );
			delete item.dataset.originalParent;
		}
	} );

	moreWrapper.style.display = 'none';

	const items = Array.from( controls.children );
	if ( items.length === 0 ) return;
	const toolbarStyle = window.getComputedStyle( toolbarArea );
	const toolbarPaddingLeft = parseFloat( toolbarStyle.paddingLeft ) || 0;
	const toolbarPaddingRight = parseFloat( toolbarStyle.paddingRight ) || 0;
	const availableWidth = toolbarArea.clientWidth - toolbarPaddingLeft - toolbarPaddingRight;

	const contentWidth = controls.scrollWidth;

	if ( contentWidth <= availableWidth ) {
		moreWrapper.style.display = 'none';
		return;
	}

	moreWrapper.style.display = 'flex';
	const moreWidth = moreWrapper.offsetWidth + 8;
	const targetWidth = availableWidth - moreWidth;
	let currentWidth = 0;
	const itemWidths = [];

	items.forEach( ( item, index ) => {
		const rect = item.getBoundingClientRect();
		const style = window.getComputedStyle( item );
		const marginLeft = parseFloat( style.marginLeft ) || 0;
		const marginRight = parseFloat( style.marginRight ) || 0;
		const gap = index > 0 ? 8 : 0;
		const totalWidth = rect.width + marginLeft + marginRight + gap;

		itemWidths.push( {
			item,
			width: totalWidth,
			cumulative: currentWidth + totalWidth,
		} );

		currentWidth += totalWidth;
	} );

	let cutoffIndex = items.length;

	for ( let i = items.length - 1; i >= 0; i-- ) {
		if ( itemWidths[ i ].cumulative <= targetWidth ) {
			cutoffIndex = i + 1;
			break;
		}
	}

	if ( cutoffIndex >= items.length ) {
		cutoffIndex = items.length - 1;
	}

	const itemsToHide = items.slice( cutoffIndex );

	if ( itemsToHide.length === 0 ) {
		moreWrapper.style.display = 'none';
		return;
	}

	itemsToHide.forEach( item => {
		item.dataset.originalParent = controls.id;
		moreDropdown.appendChild( item );
	} );
}

function toggleMoreDropdown() {
	if ( moreDropdown.classList.contains( 'is-visible' ) ) {
		closeMoreDropdown();
	} else {
		openMoreDropdown();
	}
}

function openMoreDropdown() {
	moreDropdown.classList.add( 'is-visible' );
	moreBtn.classList.add( 'is-active' );
	positionDropdown();
}

function closeMoreDropdown() {
	moreDropdown.classList.remove( 'is-visible' );
	moreBtn.classList.remove( 'is-active' );
}

function positionDropdown() {
	const btnRect = moreBtn.getBoundingClientRect();
	const dropdownWidth = moreDropdown.offsetWidth || 200;

	moreDropdown.style.position = 'fixed';
	moreDropdown.style.top = `${ btnRect.bottom + 8 }px`;

	let left = btnRect.right - dropdownWidth;
	if ( left < 16 ) {
		left = 16;
	}
	moreDropdown.style.left = `${ left }px`;
}

function debounce( fn, delay ) {
	let timeout;
	return function( ...args ) {
		clearTimeout( timeout );
		timeout = setTimeout( () => fn.apply( this, args ), delay );
	};
}

export function refreshPriorityNav() {
	setTimeout( updatePriorityNav, 50 );
}
