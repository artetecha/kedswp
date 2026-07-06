import { selectors } from 'AssetsJsPath/backend/builder/selectors';

let canvas = null;
let layerManager = null;

export function initLibraryImages( canvasInstance, layerManagerInstance ) {
	canvas = canvasInstance;
	layerManager = layerManagerInstance;
}

export function showLibraryFullView() {
	const mainView = document.querySelector( selectors.elLibraryMainView );
	const fullView = document.querySelector( selectors.elLibraryFullView );

	if ( ! mainView || ! fullView ) {
		return;
	}

	mainView.style.display = 'none';
	fullView.style.display = 'block';
}

export function showLibraryMainView() {
	const mainView = document.querySelector( selectors.elLibraryMainView );
	const fullView = document.querySelector( selectors.elLibraryFullView );

	if ( ! mainView || ! fullView ) {
		return;
	}

	fullView.style.display = 'none';
	mainView.style.display = 'block';
}

export async function insertLibraryImage( imageUrl ) {
	if ( ! layerManager || ! imageUrl ) {
		return null;
	}

	const result = await layerManager.insertFabricElement( 'image', imageUrl, { name: 'Image' } );
	return result;
}
