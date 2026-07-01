export function initSliderProgress( slider ) {
	if ( ! slider || slider.type !== 'range' ) {
		return;
	}

	updateSliderProgress( slider );

	slider.addEventListener( 'input', () => {
		updateSliderProgress( slider );
	} );
}

export function updateSliderProgress( slider ) {
	if ( ! slider ) {
		return;
	}

	const min = parseFloat( slider.min ) || 0;
	const max = parseFloat( slider.max ) || 100;
	const value = parseFloat( slider.value ) || 0;
	const progress = ( ( value - min ) / ( max - min ) ) * 100;

	slider.style.setProperty( '--slider-progress', `${progress}%` );
}

export function initAllSliders( selector = 'input[type="range"]' ) {
	const sliders = document.querySelectorAll( selector );
	sliders.forEach( ( slider ) => {
		initSliderProgress( slider );
	} );
}
