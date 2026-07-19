/**
 * KEDS carousel — a tiny, dependency-free enhancement over a native
 * scroll-snap track. Progressive: without JS the slides are still a
 * horizontally scrollable/swipeable row. JS adds prev/next arrows, dots,
 * and optional autoplay (data-autoplay="ms"), and respects reduced motion.
 *
 * Markup contract:
 *   .keds-carousel > .keds-carousel__track > .keds-carousel__slide (xN)
 */
( function () {
	'use strict';

	function initCarousel( root ) {
		var track = root.querySelector( '.keds-carousel__track' );
		if ( ! track ) {
			return;
		}
		var slides = Array.prototype.slice.call( track.children );
		if ( slides.length < 2 ) {
			return;
		}

		function step() {
			var a = slides[ 0 ].getBoundingClientRect();
			var b = slides[ 1 ].getBoundingClientRect();
			return Math.abs( b.left - a.left ) || slides[ 0 ].offsetWidth;
		}
		function index() {
			return Math.round( track.scrollLeft / step() );
		}
		function atEnd() {
			return track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
		}
		function go( i ) {
			i = Math.max( 0, Math.min( i, slides.length - 1 ) );
			track.scrollTo( { left: i * step(), behavior: 'smooth' } );
		}

		function arrow( dir, label, glyph ) {
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.className = 'keds-carousel__arrow keds-carousel__arrow--' + dir;
			b.setAttribute( 'aria-label', label );
			b.innerHTML = glyph;
			root.appendChild( b );
			return b;
		}
		var prev = arrow( 'prev', 'Previous testimonials', '‹' );
		var next = arrow( 'next', 'Next testimonials', '›' );

		var dotsWrap = document.createElement( 'div' );
		dotsWrap.className = 'keds-carousel__dots';
		var dots = slides.map( function ( slide, i ) {
			var d = document.createElement( 'button' );
			d.type = 'button';
			d.className = 'keds-carousel__dot';
			d.setAttribute( 'aria-label', 'Go to slide ' + ( i + 1 ) );
			d.addEventListener( 'click', function () {
				go( i );
			} );
			dotsWrap.appendChild( d );
			return d;
		} );
		root.appendChild( dotsWrap );

		function update() {
			var idx = index();
			dots.forEach( function ( d, i ) {
				d.classList.toggle( 'is-active', i === idx );
			} );
			prev.disabled = idx <= 0;
			next.disabled = atEnd();
		}

		prev.addEventListener( 'click', function () {
			go( index() - 1 );
		} );
		next.addEventListener( 'click', function () {
			go( index() + 1 );
		} );

		var t;
		track.addEventListener( 'scroll', function () {
			window.clearTimeout( t );
			t = window.setTimeout( update, 90 );
		} );
		window.addEventListener( 'resize', update );
		update();

		// Autoplay defaults to 6s; add data-autoplay="0" to disable, or another
		// value in ms. (Block groups don't preserve data-* on re-save, so the
		// default is what normally applies.)
		var attr = root.getAttribute( 'data-autoplay' );
		var delay = attr === null ? 6000 : parseInt( attr, 10 );
		var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		if ( delay && ! reduce ) {
			var timer;
			var play = function () {
				timer = window.setInterval( function () {
					if ( atEnd() ) {
						track.scrollTo( { left: 0, behavior: 'smooth' } );
					} else {
						go( index() + 1 );
					}
				}, delay );
			};
			var stop = function () {
				window.clearInterval( timer );
			};
			root.addEventListener( 'mouseenter', stop );
			root.addEventListener( 'mouseleave', play );
			root.addEventListener( 'focusin', stop );
			root.addEventListener( 'focusout', play );
			play();
		}
	}

	function ready() {
		var nodes = document.querySelectorAll( '.keds-carousel' );
		Array.prototype.forEach.call( nodes, initCarousel );
	}

	if ( document.readyState !== 'loading' ) {
		ready();
	} else {
		document.addEventListener( 'DOMContentLoaded', ready );
	}
} )();
