/**
 * JS handle for screen Recent Activity
 *
 * @since 4.0.8
 * @version 1.0.0
 */

import * as lpUtils from './utils.js';

const filter = ( e, target ) => {
	if ( target.classList.contains( 'gradebook-filter-btn' ) ) {
		target.disabled = true;
		target.querySelector( 'span:not(.dashicons)' ).className =
			'spinner is-active';
		let lpGradebookFilter = document.querySelector(
			'.lp-gradebook-filter'
		);
		let lpTarget = document.querySelector( '.lp-target' );
		let dataSend = window.lpAJAXG.getDataSetCurrent( lpTarget );
		dataSend.args.paged = 1;
		dataSend = handleDataSendArgs( dataSend, lpGradebookFilter );
		window.lpAJAXG.setDataSetCurrent( lpTarget, dataSend );
		const lpTargetY =
			lpTarget.getBoundingClientRect().top + window.scrollY - 100;
		window.scrollTo( { top: lpTargetY } );

		const callBack = callBackArgs( lpTarget, target );

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
};

document.addEventListener( 'click', ( e ) => {
	let target = e.target;
	if ( target.classList.contains( 'gradebook-filter-btn' ) ) {
		target.disabled = true;
		target.querySelector( 'span:not(.dashicons)' ).className =
			'spinner is-active';
		let lpGradebookFilter = document.querySelector(
			'.lp-gradebook-filter'
		);
		let lpTarget = document.querySelector( '.lp-target' );
		let dataSend = window.lpAJAXG.getDataSetCurrent( lpTarget );
		dataSend.args.paged = 1;
		dataSend = handleDataSendArgs( dataSend, lpGradebookFilter );
		window.lpAJAXG.setDataSetCurrent( lpTarget, dataSend );
		const lpTargetY =
			lpTarget.getBoundingClientRect().top + window.scrollY - 100;
		window.scrollTo( { top: lpTargetY } );

		const callBack = callBackArgs( lpTarget, target );

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	} else if ( target.classList.contains( 'gradebook-reset-btn' ) ) {
		target.disabled = true;
		target.querySelector( 'span:not(.dashicons)' ).className =
			'spinner is-active';
		let lpGradebookFilter = document.querySelector(
			'.lp-gradebook-filter'
		);
		lpGradebookFilter.reset();
		let lpTarget = document.querySelector( '.lp-target' );
		let dataSend = window.lpAJAXG.getDataSetCurrent( lpTarget );
		dataSend.args.paged = 1;
		dataSend = handleDataSendArgs( dataSend, lpGradebookFilter );
		window.lpAJAXG.setDataSetCurrent( lpTarget, dataSend );
		const lpTargetY =
			lpTarget.getBoundingClientRect().top + window.scrollY - 100;
		window.scrollTo( { top: lpTargetY } );

		const callBack = callBackArgs( lpTarget, target );

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
} );
document.addEventListener( 'reset', ( e ) => {
	if ( e.target.classList.contains( 'lp-gradebook-filter' ) ) {
		e.target
			.querySelectorAll( '.tomselected' )
			.forEach( ( tomselectedElement ) => {
				tomselectedElement.tomselect.clear();
			} );
	}
} );
