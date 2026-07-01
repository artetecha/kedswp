(function ($) {
	'use strict'
	$(document).ready(function () {
		// Define SVG icons
		const svgPrev = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>`;
		const svgNext = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
            <polyline points="9 18 15 12 9 6"></polyline>
        </svg>`;

		// Check if Slick is defined before initializing sliders
		if (typeof $.fn.slick !== 'undefined') {
			$('.portfolio-sliders').slick({
				infinite      : true,
				slidesToShow  : 1,
				slidesToScroll: 1,
				dots          : false,
				arrows        : true,
				prevArrow     : `<button type="button" class="slick-prev">${svgPrev}</button>`,
				nextArrow     : `<button type="button" class="slick-next">${svgNext}</button>`,
			});
		}
	});
})(jQuery)

