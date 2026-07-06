import { selectors } from './selectors';
import { scrollToSelectedLayer } from './layers';

export function toggleMenu( args ) {
	const { e, target } = args;
	const menuItem = target.closest( selectors.elMenuItem );
	if ( ! menuItem ) {
		return;
	}

	const menuId = menuItem.dataset.menu;
	if ( ! menuId ) {
		return;
	}

	const isActive = menuItem.classList.contains( 'active' );
	const sidebarTools = document.querySelector( selectors.elSidebarTools );

	if ( isActive ) {
		menuItem.classList.remove( 'active' );
		document.querySelectorAll( selectors.elInserterItem ).forEach( ( item ) => {
			item.classList.remove( 'active' );
		} );
		if ( sidebarTools ) {
			sidebarTools.classList.add( 'hidden' );
		}
	} else {
		document.querySelectorAll( selectors.elMenuItem ).forEach( ( item ) => {
			item.classList.remove( 'active' );
		} );

		document.querySelectorAll( selectors.elInserterItem ).forEach( ( item ) => {
			item.classList.remove( 'active' );
		} );

		menuItem.classList.add( 'active' );
		const inserterItem = document.querySelector( `.lp-inserter-item.${ menuId }` );
		if ( inserterItem ) {
			inserterItem.classList.add( 'active' );
		}
		if ( sidebarTools ) {
			sidebarTools.classList.remove( 'hidden' );
		}

		if ( menuId === 'layers' ) {
			setTimeout( () => {
				scrollToSelectedLayer();
			}, 50 );
		}
	}
}

export function toggleGroup( args ) {
	const { e, target } = args;
	const groupHeader = target.closest( selectors.elGroupHeader );
	if ( ! groupHeader ) {
		return;
	}

	const group = groupHeader.closest( '.lp-cert-inserter-group' );
	if ( ! group ) {
		return;
	}

	group.classList.toggle( 'expanded' );
}

export function openMenu( menuId ) {
	const menuItem = document.querySelector( `${ selectors.elMenuItem }[data-menu="${ menuId }"]` );
	if ( ! menuItem ) {
		return;
	}

	const sidebarTools = document.querySelector( selectors.elSidebarTools );

	document.querySelectorAll( selectors.elMenuItem ).forEach( ( item ) => {
		item.classList.remove( 'active' );
	} );

	document.querySelectorAll( selectors.elInserterItem ).forEach( ( item ) => {
		item.classList.remove( 'active' );
	} );

	menuItem.classList.add( 'active' );
	const inserterItem = document.querySelector( `.lp-inserter-item.${ menuId }` );
	if ( inserterItem ) {
		inserterItem.classList.add( 'active' );
	}
	if ( sidebarTools ) {
		sidebarTools.classList.remove( 'hidden' );
	}
}

export function isLayersMenuActive() {
	const menuItem = document.querySelector( `${ selectors.elMenuItem }[data-menu="layers"]` );
	return menuItem?.classList.contains( 'active' ) ?? false;
}
