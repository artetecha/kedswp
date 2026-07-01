/**
 * LearnPress Wishlist JS
 *
 * @since 4.0.9
 * @version 1.0.0
 */

import * as lpUtils from './utils.js';
import * as lpToastify from './lpToastify';

export class WishList {
	constructor() {
		this.init();
	}

	static selectors = {
		elBtnWishListAction: '.lp-button-wishlist-action',
	};

	init() {
		this.events();
	}

	events() {
		// Check and attach events only once
		if ( WishList._loadedEvents ) {
			return;
		}

		WishList._loadedEvents = this;

		// Click events
		lpUtils.eventHandlers( 'click', [
			{
				selector: WishList.selectors.elBtnWishListAction,
				class: this,
				callBack: this.addOrRemove.name,
			},
		] );
	}

	addOrRemove( args ) {
		const { e, target } = args;
		e.preventDefault();

		const elBtn = target.closest( WishList.selectors.elBtnWishListAction );
		if ( ! elBtn ) {
			return;
		}

		const elLPTarget = elBtn.closest( '.lp-target' );
		if ( ! elLPTarget ) {
			return;
		}

		const icon = elBtn.querySelector( 'i' );

		lpUtils.lpSetLoadingEl( elBtn, 1 );
		lpUtils.lpShowHideEl( icon, 0 );

		// Call ajax to update question description
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				if ( status === 'success' ) {
					const elWrap = elBtn.closest( '.course-button-wishlist__wrapper-no-css' );
					elWrap.outerHTML = data.content;

					lpToastify.show( message, status );
				} else if ( status === 'error') {
					throw message;
				} else {
					lpToastify.show( message, status );
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				lpUtils.lpShowHideEl( icon, 1 );
				lpUtils.lpSetLoadingEl( elBtn, 0 );
			},
		};

		const dataSend = window.lpAJAXG.getDataSetCurrent( elLPTarget );
		dataSend.args.effect = 1; // Effect: 1 - Add/Remove wishlist
		// Call via AJAX
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}

new WishList();
