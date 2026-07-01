import { __ as ____ } from '@wordpress/i18n';

export {
	defaultI18n,
	setLocaleData,
	resetLocaleData,
	getLocaleData,
	subscribe,
	_x,
	_n,
	_nx,
	isRTL,
	hasTranslation,
	sprintf,
} from '@wordpress/i18n';

export const __ = ( text : string ) => ____( text, 'learnpress-upsell' );
