import apiFetch from '@wordpress/api-fetch';
import Toastify from 'toastify-js';
import { lpSetLoadingEl } from '../utils';

const applyCoupon = () => {
	// Types
	interface CouponResponse {
		message: string;
		status: string;
		data: {
			output: string;
			total: string;
		};
	}

	const fetchAPI = async (
		couponCode: string,
		elBtnApplyCoupon: HTMLElement
	) => {
		const elInputCouponCode = document.querySelector(
			'input[name=coupon_code]'
		) as HTMLInputElement;

		lpSetLoadingEl( elBtnApplyCoupon, 1 );

		const response: CouponResponse = await apiFetch( {
			path: '/learnpress-coupon/v1/frontend/apply-coupon',
			method: 'POST',
			data: {
				coupon_code: couponCode,
			},
		} );

		const { message, status, data } = response;

		lpSetLoadingEl( elBtnApplyCoupon, 0 );

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
			if ( elInputCouponCode ) {
				elInputCouponCode.value = '';
			}
			updateDisplay( data );
		}
	};

	const updateDisplay = ( data: CouponResponse[ 'data' ] ) => {
		const { output, total } = data;

		const couponHtml: string = output || '';
		const originalElement = document.querySelector( '.cart-subtotal' );
		const totalElement = document.querySelector(
			'.order-total .col-number'
		) as HTMLElement;

		if ( totalElement ) {
			totalElement.innerHTML = total;
		}

		if ( originalElement && couponHtml.length ) {
			const couponElements = Array.from(
				document.querySelectorAll( '.lp-applied-coupon' )
			);

			if ( couponElements.length ) {
				const lastCouponEl =
					couponElements[ couponElements.length - 1 ];
				lastCouponEl.insertAdjacentHTML( 'afterend', couponHtml );
			} else {
				originalElement.insertAdjacentHTML( 'afterend', couponHtml );
			}
		}
	};

	// Events
	document.addEventListener( 'click', ( e ) => {
		const target = e.target as HTMLElement;
		if ( target.classList.contains( 'lp-coupon-apply' ) ) {
			e.preventDefault();
			const elInputCouponCode = document.querySelector(
				'input[name=coupon_code]'
			) as HTMLInputElement;
			if ( ! elInputCouponCode ) {
				return;
			}

			let code = target.dataset.code;
			if ( ! code ) {
				code = elInputCouponCode.value;
				if ( ! code ) {
					return;
				}
			} else {
				elInputCouponCode.value = code;
			}

			fetchAPI( code, target );
		}
	} );

	document.addEventListener( 'keydown', ( e ) => {
		const target = e.target as HTMLElement;
		if ( e.key === 'Enter' ) {
			if ( target.id === 'lp_coupon_code' ) {
				e.preventDefault();
				const value = target.value;
				if ( value ) {
					const el = target.closest( '.lp-coupon__wrapper' );

					if ( el ) {
						const elApplyCoupon =
							el.querySelector( '.lp-coupon-apply' );

						if ( elApplyCoupon ) {
							elApplyCoupon.click();
						}
					}
				}
			}
		}
	} );
};

export default applyCoupon;
