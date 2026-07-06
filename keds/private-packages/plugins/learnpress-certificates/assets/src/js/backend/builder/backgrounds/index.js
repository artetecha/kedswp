export {
	initColorBackground,
	setupColorPicker,
	updateBackgroundColorActiveState,
	setCanvasBackgroundColor
} from './color';

export {
	initImageBackground,
	loadMoreBackgroundImages,
	setCanvasBackgroundImage
} from './image';

import { initColorBackground } from './color';
import { initImageBackground } from './image';

export function initBackgrounds( canvasInstance, layerManagerInstance ) {
	initColorBackground( canvasInstance, layerManagerInstance );
	initImageBackground( canvasInstance, layerManagerInstance );
}
