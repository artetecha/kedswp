import { selectors } from "AssetsJsPath/backend/builder/selectors";
import * as lpUtils from "AssetsJsPath/utils";

export class FullscreenMode {
	constructor() {}

	init() {
		const elBtnOpenFullscreen = document.querySelectorAll( selectors.elBtnOpenFullscreen );
		const elBtnCloseFullscreen = document.querySelectorAll( selectors.elBtnCloseFullscreen );
		const elBtnBackEditGeneral = document.querySelector( selectors.elBtnBackEditGeneral );

		lpUtils.eventHandlers( 'click', [
			{
				selector: selectors.elBtnOpenFullscreen,
				/*callBack: this.openX.name,
				class: this,*/
				callBack: ( args ) => {
					const { e, target } = args;
					e.preventDefault();

					const elEditWrapper = document.querySelector( selectors.elEditWrapper );
					elEditWrapper.classList.add( 'full' );
					elBtnCloseFullscreen.forEach( ( el ) => lpUtils.lpShowHideEl( el, 1 ) );
					elBtnOpenFullscreen.forEach( ( el ) => lpUtils.lpShowHideEl( el, 0 ) );
					lpUtils.lpShowHideEl( elBtnBackEditGeneral, 0 );
				},
			},
			{
				selector: selectors.elBtnCloseFullscreen,
				callBack: ( args ) => {
					const { e, target } = args;
					e.preventDefault();

					const elEditWrapper = document.querySelector( selectors.elEditWrapper );
					elEditWrapper.classList.remove( 'full' );
					elBtnCloseFullscreen.forEach( ( el ) => lpUtils.lpShowHideEl( el, 0 ) );
					elBtnOpenFullscreen.forEach( ( el ) => lpUtils.lpShowHideEl( el, 1 ) );
					lpUtils.lpShowHideEl( elBtnBackEditGeneral, 1 );
				},
			}
		]);
	}
}

