import apiFetch from '@wordpress/api-fetch';
import Toastify from 'toastify-js';

const removeCoupon = () => {
	// Types
	interface CouponResponse {
		message: string;
		status: string;
		data: {
			total: string;
		};
	}

	const fetchAPI = async (
		couponCode: string,
		btnRemoveCoupon: HTMLElement
	) => {
		const elInputCouponCode = document.querySelector(
			'input[name=coupon_code]'
		) as HTMLInputElement;

		const response: CouponResponse = await apiFetch( {
			path: '/learnpress-coupon/v1/frontend/remove-coupon',
			method: 'POST',
			data: {
				coupon_code: couponCode,
			},
		} );

		const { message, status, data } = response;

		Toastify( {
			text: message,
			gravity: lpData.toast.gravity, // `top` or `bottom`
			position: lpData.toast.position, // `left`, `center` or `right`
			className: `${ lpData.toast.classPrefix } ${ status }`,
			close: lpData.toast.close == 1,
			stopOnFocus: lpData.toast.stopOnFocus == 1,
			duration: lpData.toast.duration,
		} ).showToast();

		if ( status === 'success' ) {
			updateDisplay( data );
			const elTr = btnRemoveCoupon.closest( 'tr' );
			if ( elTr ) {
				elTr.remove();
			}
		}

		const btnApplyCoupons = document.querySelectorAll( '.lp-coupon-apply' );
		btnApplyCoupons.forEach( ( btnApplyCoupon ) => {
			btnApplyCoupon.classList.remove( 'loading' );
		} );
	};

	const updateDisplay = ( data: CouponResponse[ 'data' ] ) => {
		const { total } = data;

		const totalElement = document.querySelector(
			'.order-total .col-number'
		) as HTMLElement;

		if ( totalElement ) {
			totalElement.innerHTML = total;
		}
	};

	// Events
	document.addEventListener( 'click', ( e ) => {
		const target = e.target as HTMLElement;
		if ( target.classList.contains( 'lp-coupon__remove' ) ) {
			e.preventDefault();
			const code = target.dataset.code;
			if ( ! code ) {
				return;
			}

			target.textContent = target.dataset.textLoading || '';
			target.style.pointerEvents = 'none';
			fetchAPI( code, target );
		}
	} );
};

export default removeCoupon;
