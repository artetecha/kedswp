import { FabricImage } from 'fabric';
import { selectors } from 'AssetsJsPath/backend/builder/selectors';

let canvas = null;
let layerManager = null;

export function initImageBackground( canvasInstance, layerManagerInstance ) {
	canvas = canvasInstance;
	layerManager = layerManagerInstance;
}

export function loadMoreBackgroundImages( button ) {
	const grid = document.querySelector( selectors.elBackgroundBgMainGrid );

	if ( ! grid || ! button || button.disabled ) {
		return;
	}

	const offset = parseInt( button.dataset.offset || '0', 10 );
	const perPage = parseInt( button.dataset.perPage || '20', 10 );

	button.disabled = true;
	button.classList.add( 'is-loading' );

	const dataSend = {
		action: 'certificate_builder_load_background_images',
		offset: offset,
		per_page: perPage,
	};

	window.lpAJAXG.fetchAJAX( dataSend, {
		success: ( response ) => {
			const { status, data } = response;

			if ( status === 'success' && data ) {
				if ( data.html ) {
					grid.insertAdjacentHTML( 'beforeend', data.html );
				}

				if ( ! data.has_more || ! data.html ) {
					button.remove();
				} else {
					button.dataset.offset = data.next_offset || ( offset + perPage );
				}
			}
		},
		error: () => {
			console.error( 'Error loading more background images' );
		},
		completed: () => {
			if ( document.contains( button ) ) {
				button.disabled = false;
				button.classList.remove( 'is-loading' );
			}
		},
	} );
}

export async function setCanvasBackgroundImage( imageUrl, skipSave = false ) {
	if ( ! canvas || ! imageUrl ) {
		return;
	}

	canvas.set( 'backgroundColor', '' );

	try {
		const img = await FabricImage.fromURL( imageUrl, { crossOrigin: 'anonymous' } );

		if ( ! img ) {
			console.error( 'Failed to load background image' );
			return;
		}

		const canvasWidth = canvas.getWidth();
		const canvasHeight = canvas.getHeight();
		const imgWidth = img.width;
		const imgHeight = img.height;

		const scaleX = canvasWidth / imgWidth;
		const scaleY = canvasHeight / imgHeight;
		const scale = Math.max( scaleX, scaleY );

		const canvasData = window.lpCertCanvasData || {};
		const opacity = ( canvasData.backgroundOpacity ?? 100 ) / 100;

		img.set( {
			scaleX: scale,
			scaleY: scale,
			originX: 'center',
			originY: 'center',
			left: canvasWidth / 2,
			top: canvasHeight / 2,
			opacity: opacity,
		} );

		canvas.backgroundImage = img;
		canvas.renderAll();

		canvasData.background = imageUrl;
		canvasData.baseBackgroundColor = null;
		window.lpCertCanvasData = canvasData;

		const swatches = document.querySelectorAll( selectors.elBackgroundColorSwatch );
		swatches.forEach( swatch => swatch.classList.remove( 'active' ) );

		if ( layerManager && ! skipSave ) {
			layerManager.saveCanvasLayers( false );
		}
	} catch ( error ) {
		console.error( 'Error loading background image:', error );
	}
}
