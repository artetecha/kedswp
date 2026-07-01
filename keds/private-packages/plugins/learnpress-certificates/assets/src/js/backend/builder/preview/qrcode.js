import { FabricImage } from 'fabric';
import QRCode from 'qrcode';

const DEFAULT_QR_URL = 'https://thimpress.com/';

export function isQrObject( obj ) {
	return obj.get( 'type_layer' ) === 'qr_code';
}

export function collectQrObject( obj, cloned, qrObjects ) {
	const displayWidth = ( obj.width || 100 ) * ( obj.scaleX || 1 );
	const displayHeight = ( obj.height || 100 ) * ( obj.scaleY || 1 );
	const size = Math.max( displayWidth, displayHeight );

	qrObjects.set( cloned, {
		left: obj.get( 'left' ),
		top: obj.get( 'top' ),
		originX: obj.get( 'originX' ) || 'center',
		originY: obj.get( 'originY' ) || 'center',
		size: size,
		qr_url: obj.get( 'qr_url' ) || null,
	} );

	cloned.set( 'visible', false );
}

export async function generateQRDataURL( url, size ) {
	try {
		return await QRCode.toDataURL( url, {
			width: size,
			margin: 0,
			color: {
				dark: '#000000',
				light: '#00000000',
			},
		} );
	} catch ( error ) {
		console.error( 'Error generating QR code:', error );
		return null;
	}
}

export async function applyQrReplacements( qrObjects, qrUrl, canvas ) {
	for ( const [ obj, info ] of qrObjects ) {
		const url = qrUrl || info.qr_url || DEFAULT_QR_URL;
		if ( obj._qrImage ) {
			canvas.remove( obj._qrImage );
			obj._qrImage = null;
		}

		const size = info.size || 100;
		const renderSize = size * 3;
		const dataUrl = await generateQRDataURL( url, renderSize );

		if ( dataUrl ) {
			const qrImage = await FabricImage.fromURL( dataUrl );
			if ( qrImage ) {
				qrImage.set( {
					left: info.left,
					top: info.top,
					originX: info.originX,
					originY: info.originY,
					scaleX: size / qrImage.width,
					scaleY: size / qrImage.height,
					selectable: false,
					evented: false,
				} );

				obj.set( 'visible', false );
				obj._qrImage = qrImage;
				canvas.add( qrImage );
			}
		}
	}
}

export function resetQrField() {
	return DEFAULT_QR_URL;
}
