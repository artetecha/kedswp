import { openMenu } from 'AssetsJsPath/backend/builder/menu';
import { hideActivePopup } from '../popup-manager';

const LAYERS_BTN_SELECTOR = '.lp-cert-canvas-toolbar__layers';

export function initLayers() {
	const layersBtn = document.querySelector( LAYERS_BTN_SELECTOR );
	if ( layersBtn ) {
		layersBtn.addEventListener( 'click', () => {
			hideActivePopup();
			openMenu( 'layers' );
		} );
	}
}
