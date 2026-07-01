import { SYSTEM_FONTS, TEXT_DEFAULTS } from './config';

export function replaceNewlinesForLoad( data ) {
	if ( ! data ) return data;

	if ( Array.isArray( data ) ) {
		return data.map( item => replaceNewlinesForLoad( item ) );
	}

	if ( typeof data === 'object' && data !== null ) {
		const result = { ...data };
		if ( result.text && typeof result.text === 'string' ) {
			result.text = result.text.replace( /\{n\}/g, '\n' ).replace( /\/n/g, '\n' );
		}
		if ( result.layers && Array.isArray( result.layers ) ) {
			result.layers = result.layers.map( layer => replaceNewlinesForLoad( layer ) );
		}
		return result;
	}

	return data;
}

export function blockDefaultContextMenu() {
	const builderContainer = document.querySelector('.lp-certificate-edit-builder');
	if (builderContainer) {
		builderContainer.addEventListener('contextmenu', (e) => {
			e.preventDefault();
			return false;
		});
	}
}

export function convertTitlesToTooltips() {
	const container = document.querySelector( '.lp-certificate-edit-wrapper' );
	if ( ! container ) return;

	container.querySelectorAll( '[title]' ).forEach( ( el ) => {
		const title = el.getAttribute( 'title' );
		if ( title ) {
			el.setAttribute( 'data-tooltip', title );
			el.removeAttribute( 'title' );
		}
	} );
}

export function generateLayerId( index = null ) {
	const timestamp = Date.now();
	const random = Math.random().toString( 36 ).substr( 2, 9 );
	if ( index !== null ) {
		return `layer_${ timestamp }_${ index }_${ random }`;
	}
	return `layer_${ timestamp }_${ random }`;
}

export function autoResizeCanvasToFit( canvas, options = {} ) {
	if ( ! canvas ) {
		return null;
	}

	const {
		insetX = 0,
		insetY = 0,
	} = options;

	const canvasWrapper = canvas.wrapperEl;
	const container = canvasWrapper?.parentElement;

	if ( ! canvasWrapper || ! container ) {
		return null;
	}

	container.style.position = 'relative';
	container.style.overflow = 'hidden';

	const containerStyles = window.getComputedStyle( container );
	const paddingX = parseFloat( containerStyles.paddingLeft || 0 ) + parseFloat( containerStyles.paddingRight || 0 );
	const paddingY = parseFloat( containerStyles.paddingTop || 0 ) + parseFloat( containerStyles.paddingBottom || 0 );
	const containerWidth = container.clientWidth - paddingX - insetX;
	const containerHeight = container.clientHeight - paddingY - insetY;

	if ( containerWidth < 100 || containerHeight < 100 ) {
		return () => autoResizeCanvasToFit( canvas, options );
	}

	const canvasWidth = canvas.getWidth();
	const canvasHeight = canvas.getHeight();

	const scaleX = containerWidth / canvasWidth;
	const scaleY = containerHeight / canvasHeight;
	const scale = Math.min( scaleX, scaleY, 1 );

	if ( scale < 1 ) {
		canvasWrapper.style.transform = `scale(${ scale })`;
		canvasWrapper.style.transformOrigin = 'top center';
	} else {
		canvasWrapper.style.transform = '';
	}

	return null;
}

export async function resolveFont( fontFamily ) {
	if ( SYSTEM_FONTS.includes( fontFamily ) ) {
		return fontFamily;
	}

	const existingStyleTag = document.getElementById( 'lp-certificates-fonts-gg' );
	if ( existingStyleTag && existingStyleTag.textContent.includes( fontFamily ) ) {
		try {
			await document.fonts.load( `16px "${ fontFamily }"` );
			await document.fonts.ready;
		} catch ( e ) {
			console.warn( `Font "${ fontFamily }" failed to load.` );
			return TEXT_DEFAULTS.FONT_FAMILY;
		}
		return fontFamily;
	}

	console.warn( `Font "${ fontFamily }" is not available. Please add it in LearnPress > Settings > Certificates > Google Fonts.` );
	return TEXT_DEFAULTS.FONT_FAMILY;
}
