import { openMenu } from 'AssetsJsPath/backend/builder/menu';
import { hideActivePopup } from '../popup-manager';

const BG_BTN_SELECTOR = '.lp-cert-canvas-toolbar__bg';

export function initBackground() {
	const bgBtn = document.querySelector( BG_BTN_SELECTOR );
	if ( bgBtn ) {
		bgBtn.addEventListener( 'click', () => {
			hideActivePopup();
			openMenu( 'backgrounds' );
		} );
	}
}
