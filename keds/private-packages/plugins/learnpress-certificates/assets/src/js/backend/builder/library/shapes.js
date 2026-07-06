import { createFabricElement } from 'AssetsJsPath/backend/builder/elements';
import { generateLayerId } from 'AssetsJsPath/backend/builder/utils';
import { refreshLayersPanel } from 'AssetsJsPath/backend/builder/layers/panel';

let canvas = null;
let layerManager = null;

export function initShapesLibrary( canvasInstance, layerManagerInstance ) {
	canvas = canvasInstance;
	layerManager = layerManagerInstance;

	setupEventListeners();
}

function setupEventListeners() {
	document.addEventListener( 'click', handleShapeClick );
}

async function handleShapeClick( e ) {
	const item = e.target.closest( '.lp-inserter-library__item[data-shape]' );
	if ( ! item || ! canvas ) {
		return;
	}

	const shapeDataStr = item.getAttribute( 'data-shape' );
	if ( ! shapeDataStr ) {
		return;
	}

	try {
		const shapeData = JSON.parse( shapeDataStr );
		const typeLayer = shapeData.type_layer;
		if ( ! typeLayer ) {
			return;
		}

		const layerId = generateLayerId();

		const fabricObj = await createFabricElement( typeLayer, {
			...shapeData,
			left: canvas.width / 2,
			top: canvas.height / 2,
			originX: 'center',
			originY: 'center',
			strokeUniform: true,
		} );

		if ( fabricObj ) {
			fabricObj.set( 'id', layerId );
			fabricObj.set( 'type_layer', typeLayer );
			fabricObj.set( 'name', shapeData.name || typeLayer );

			canvas.add( fabricObj );
			fabricObj.setCoords();
			canvas.setActiveObject( fabricObj );
			canvas.requestRenderAll();

			if ( layerManager ) {
				layerManager.saveFabricElementToLayer( fabricObj, layerId );
				layerManager.saveCanvasLayers( false );
			}

			refreshLayersPanel();
		}
	} catch ( error ) {
		console.error( 'Error adding shape:', error );
	}
}
