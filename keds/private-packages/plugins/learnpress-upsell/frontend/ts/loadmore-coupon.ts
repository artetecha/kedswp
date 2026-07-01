import { lpSetLoadingEl } from '../utils';
import Toastify from 'toastify-js';

const loadMoreCoupon = () => {
	const classListCoupons = '.lp-list-coupons';

	const loadMoreCoupon = ( btnLoadMore: HTMLElement ) => {
		lpSetLoadingEl( btnLoadMore, 1 );

		const elLPTarget = btnLoadMore.closest( '.lp-target' ) as HTMLElement;
		const dataSendStr = elLPTarget.dataset.send || '{}';
		const dataSend = JSON.parse( dataSendStr ) || {};
		dataSend.args.paged += 1;

		elLPTarget.dataset.send = JSON.stringify( dataSend );

		const callBack = {
			success: ( response ) => {
				//console.log( 'response', response );
				const { status, message, data } = response;
				const newEl = document.createElement( 'div' );
				newEl.innerHTML = data.content || '';
				const elListCoupons =
					elLPTarget.querySelector( classListCoupons );
				if ( ! elListCoupons ) {
					return;
				}

				const btnLoadMoreNew = newEl.querySelector(
					'.lp-btn-load-more-coupon'
				);
				const elListCouponsNew = newEl.querySelector(
					classListCoupons
				) as HTMLElement;
				if ( ! btnLoadMoreNew ) {
					btnLoadMore.remove();
				}

				elListCoupons.insertAdjacentHTML(
					'beforeend',
					elListCouponsNew.innerHTML
				);
			},
			error: ( error: any ) => {
				Toastify( {
					text: error,
					gravity: lpData.toast.gravity, // `top` or `bottom`
					position: lpData.toast.position, // `left`, `center` or `right`
					className: `${ lpData.toast.classPrefix } error`,
					close: lpData.toast.close == 1,
					stopOnFocus: lpData.toast.stopOnFocus == 1,
					duration: lpData.toast.duration,
				} ).showToast();
			},
			completed: () => {
				lpSetLoadingEl( btnLoadMore, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	};

	// Events
	document.addEventListener( 'click', ( e ) => {
		const target = e.target as HTMLElement;
		if ( target.classList.contains( 'lp-btn-load-more-coupon' ) ) {
			e.preventDefault();

			const elLPTarget = target.closest( '.lp-target' ) as HTMLElement;
			if ( ! elLPTarget ) {
				return;
			}

			loadMoreCoupon( target );
		}
	} );
};

export default loadMoreCoupon;
