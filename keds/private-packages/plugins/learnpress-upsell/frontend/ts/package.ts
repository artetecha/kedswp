// Ajax Add to Cart use typescript
import apiFetch from '@wordpress/api-fetch';
import { lpSetLoadingEl, lpShowHideEl } from '../utils.js';
import Toastify from 'toastify-js';
import Glider from 'glider-js';
import 'glider-js/glider.min.css';

export function AddToCart() {
	let toast = Toastify( {
		gravity: lpData.toast.gravity, // `top` or `bottom`
		position: lpData.toast.position, // `left`, `center` or `right`
		close: lpData.toast.close == 1,
		className: `${ lpData.toast.classPrefix }`,
		stopOnFocus: lpData.toast.stopOnFocus == 1,
		duration: lpData.toast.duration,
	} );

	// Types
	interface packageResponse {
		message: string;
		status: string;
		data: {
			redirect: string;
		};
	}

	// Events
	document.addEventListener( 'submit', ( e ) => {
		const target = e.target as HTMLFormElement;
		if (
			target.classList.contains( 'learnpress-single-package__add-cart' )
		) {
			e.preventDefault();
			addPackageToCart( target );
		}
	} );

	const addPackageToCart = ( form: HTMLFormElement ) => {
		const status = 'error';
		const btnBuy = form.querySelector(
			'.lp-buy-package'
		) as HTMLButtonElement;
		if ( btnBuy ) {
			lpSetLoadingEl( btnBuy, 1 );
		}

		const formData = new FormData( form );
		const dataSend = Object.fromEntries( formData.entries() );

		apiFetch( {
			path: '/learnpress-package/v1/frontend/add-to-cart',
			method: 'POST',
			data: dataSend,
		} )
			.then( ( response: packageResponse ) => {
				const { message, status, data } = response;

				if ( status === 'success' ) {
					btnBuy.remove();
					//form.insertAdjacentHTML( 'beforeend', message );
					toast.options.text = message;
					toast.options.className += ` ${ status }`;
					toast.showToast();
					window.location = data.redirect;
				} else {
					lpSetLoadingEl( btnBuy, 0 );
					toast.options.text = message;
					toast.options.className += ` ${ status }`;
					toast.showToast();
				}
			} )
			.catch( ( error: Error ) => {
				lpSetLoadingEl( btnBuy, 0 );
				toast.options.text = error.message;
				toast.options.className += ` ${ status }`;
				toast.showToast();
			} );
	};
}

export function AddShowMoreButton() {
	document.addEventListener( 'click', ( e ) => {
		const target = e.target as HTMLElement;
		if ( target.classList.contains( 'lp-show-more-content' ) ) {
			const content = target.closest(
				'.learnpress-single-package__content'
			);
			if ( content ) {
				const contentInner = content.querySelector(
					'.learnpress-single-package__content-inner'
				);

				if ( contentInner ) {
					contentInner.classList.toggle( 'show-all' );

					const targetLess = content.querySelector(
						'.lp-show-more-content.less'
					);
					const targetMore = content.querySelector(
						'.lp-show-more-content:not(.less)'
					);
					if ( contentInner.classList.contains( 'show-all' ) ) {
						lpShowHideEl( targetMore, 0 );
						lpShowHideEl( targetLess, 1 );
					} else {
						lpShowHideEl( targetMore, 1 );
						lpShowHideEl( targetLess, 0 );
					}
				}
			}
		}
	} );

	document.addEventListener( 'DOMContentLoaded', () => {
		const content = document.querySelector(
			'.learnpress-single-package__content'
		) as HTMLElement;
		if ( content ) {
			const contentInner = content.querySelector(
				'.learnpress-single-package__content-inner'
			) as HTMLElement;

			if ( contentInner ) {
				const height = contentInner.offsetHeight;

				if ( height <= 150 ) {
					const elShowMore = content.querySelector(
						'.lp-show-more-content'
					) as HTMLElement;

					if ( elShowMore ) {
						elShowMore.remove();
					}
				}
			}
		}
	} );
}

export function Slider() {
    const carousels = document.querySelectorAll('.lp-upsell-glider');

    if ( carousels ) {
        carousels.forEach( ( carousel ) => {
            new Glider( carousel, {
                slidesToShow: 1,
                slidesToScroll: 1,
                duration: 2,
                rewind: true,
                draggable: true,
                scrollLock: true,
                dots: '.dots',
                arrows: {
                    prev: '.glider-prev',
                    next: '.glider-next'
                },
                responsive: [
                    {
                        breakpoint: 768,
                        rewind: true,
                        settings: {
                            slidesToShow: 2,
                        }
                    },
                    {
                        breakpoint: 1024,
                        rewind: true,
                        settings: {
                            slidesToShow: 2,
                        }
                    },
                    {
                        breakpoint: 1200,
                        rewind: true,
                        settings: {
                            slidesToShow: 3,
                        }
                    }
                ]
            });

            const progressBar = document.querySelector('.progress-bar__inner') as HTMLElement;
			if ( ! progressBar ) {
				return;
			}

            const initialProgress = 20;

            function updateProgressBar(): void {
                const scrollLeft = carousel.scrollLeft;
                const maxScrollLeft = carousel.scrollWidth - carousel.clientWidth;

                if (maxScrollLeft > 0) {
                    const progressPercent = (scrollLeft / maxScrollLeft) * (100 - initialProgress) + initialProgress;
                    progressBar.style.width = `${Math.min(progressPercent, 100)}%`;
                }
            }

            progressBar.style.width = `${initialProgress}%`;

            carousel.addEventListener('scroll', updateProgressBar);
        });
    }
};