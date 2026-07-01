import {
	IText,
	Textbox,
	FabricImage,
	Circle,
	Rect,
	Triangle,
	Ellipse,
	Line,
} from 'fabric';

// Valid type_layer values grouped by category
export const ALLOWED_TEXT_TYPES = [ 'text-edit', 'text-static' ];
export const ALLOWED_IMAGE_TYPES = [ 'image', 'qr_code' ];

export function isTextType( customType ) {
	return ALLOWED_TEXT_TYPES.includes( customType );
}

export function isQrCodeType( customType ) {
	return customType === 'qr_code';
}

export function isImageType( customType ) {
	return ALLOWED_IMAGE_TYPES.includes( customType );
}

export function isSvgType( customType ) {
	return customType?.startsWith( 'svg-' ) || false;
}

export function createFabricElement( type, data, options = {} ) {
	switch ( type ) {
		case 'text-edit':
		case 'text-static': {
			const isStatic = type === 'text-static';
			const {
				fontFamily,
				fill,
				fontSize,
				left,
				top,
				originX,
				originY,
				type: fabricType
			} = options;

			const TextClass = fabricType === 'Textbox' ? Textbox : IText;

			return new TextClass( data, {
				left: left,
				top: top,
				fontFamily: fontFamily,
				fill: fill,
				fontSize: fontSize,
				originX: originX,
				originY: originY,
				editable: ! isStatic,
			} );
		}

		case 'image':
		case 'qr_code':
			return FabricImage.fromURL( data, { crossOrigin: 'anonymous' } );

		case 'svg-circle': {
			const {
				left = 0,
				top = 0,
				originX = 'center',
				originY = 'center',
				fill = 'transparent',
				stroke = '',
				strokeWidth = 0,
				radius
			} = data;
			return new Circle( {
				left,
				top,
				originX,
				originY,
				fill,
				stroke,
				strokeWidth,
				radius: radius || 50
			} );
		}

		case 'svg-rect': {
			const {
				left = 0,
				top = 0,
				originX = 'center',
				originY = 'center',
				fill = 'transparent',
				stroke = '',
				strokeWidth = 0,
				width,
				height,
				rx,
				ry
			} = data;
			return new Rect( {
				left,
				top,
				originX,
				originY,
				fill,
				stroke,
				strokeWidth,
				width: width || 100,
				height: height || 100,
				rx: rx || 0,
				ry: ry || 0
			} );
		}

		case 'svg-triangle': {
			const {
				left = 0,
				top = 0,
				originX = 'center',
				originY = 'center',
				fill = 'transparent',
				stroke = '',
				strokeWidth = 0,
				width,
				height
			} = data;
			return new Triangle( {
				left,
				top,
				originX,
				originY,
				fill,
				stroke,
				strokeWidth,
				width: width || 100,
				height: height || 100
			} );
		}

		case 'svg-ellipse': {
			const {
				left = 0,
				top = 0,
				originX = 'center',
				originY = 'center',
				fill = 'transparent',
				stroke = '',
				strokeWidth = 0,
				rx,
				ry
			} = data;
			return new Ellipse( {
				left,
				top,
				originX,
				originY,
				fill,
				stroke,
				strokeWidth,
				rx: rx || 70,
				ry: ry || 45
			} );
		}

		case 'svg-line': {
			// remove key id from data to not warning: fabric Setting type has no effect Line
			delete data.type;

			const {
				left = 0,
				top = 0,
				originX = 'center',
				originY = 'center',
				stroke = '#333333',
				strokeWidth = 3,
				x1,
				y1,
				x2,
				y2
			} = data;

			return new Line( [ x1 || 0, y1 || 0, x2 || 150, y2 || 0 ], {
				left,
				top,
				originX,
				originY,
				stroke,
				strokeWidth
			} );
		}

		default:
			return null;
	}
}
