import { Canvas } from 'fabric';
import { jsPDF } from 'jspdf';
import { replaceNewlinesForLoad, resolveFont } from '../utils';
import {
	createFabricElement,
	loadFabricImage,
	isTextType,
	isSvgType,
} from '../elements';
import {
	isQrObject,
	collectQrObject,
	generateQRDataURL,
	applyQrReplacements,
} from '../preview/qrcode';

const DEFAULT_FONT = 'Arial';

async function loadFabricImageWithFallback( source, fallbackSource, label ) {
	try {
		return await loadFabricImage( source );
	} catch ( e ) {
		console.error( `renderCertificate: Error loading ${ label }:`, source, e );
	}

	if ( ! fallbackSource || fallbackSource === source ) {
		return null;
	}

	try {
		return await loadFabricImage( fallbackSource );
	} catch ( e ) {
		console.error( `renderCertificate: Error loading fallback ${ label }:`, fallbackSource, e );
	}

	return null;
}

async function createImageElementWithFallback( customType, source, props, fallbackSource, label ) {
	try {
		return await createFabricElement( customType, source, props );
	} catch ( e ) {
		console.error( `renderCertificate: Error loading ${ label }:`, source, e );
	}

	if ( ! fallbackSource || fallbackSource === source ) {
		return null;
	}

	try {
		return await createFabricElement( customType, fallbackSource, props );
	} catch ( e ) {
		console.error( `renderCertificate: Error loading fallback ${ label }:`, fallbackSource, e );
	}

	return null;
}

export async function renderCertificateFromJSON( jsonData, opts = {} ) {
	const isBuilderData = !! jsonData.builder_data;
	const noImageUrl = jsonData.no_image_url || '';

	let bgWidth = 300;
	let bgHeight = 150;
	let bgSource = null;
	let bgColor = null;

	if ( isBuilderData ) {
		bgWidth = jsonData.canvas_width || 842;
		bgHeight = jsonData.canvas_height || 595;

		const bg = jsonData.background || '';
		if ( /^(https?:\/\/|\/|data:image)/i.test( bg ) ) {
			bgSource = bg;
		} else if ( bg ) {
			bgColor = bg;
		}
	} else if ( jsonData.template ) {
		bgSource = jsonData.template;
	}

	let bgImg = null;
	if ( bgSource ) {
		bgImg = await loadFabricImageWithFallback( bgSource, noImageUrl, 'background' );
		if ( bgImg && ! isBuilderData ) {
			bgWidth = bgImg.width || 300;
			bgHeight = bgImg.height || 150;
		}
	}

	const canvasEl = opts.canvasEl || document.createElement( 'canvas' );

	const canvas = new Canvas( canvasEl, {
		selection: false,
		width: bgWidth,
		height: bgHeight,
		backgroundColor: bgColor || undefined,
		enableRetinaScaling: true,
	} );

	if ( bgImg ) {
		const imgW = bgImg.width;
		const imgH = bgImg.height;
		const scX = bgWidth / imgW;
		const scY = bgHeight / imgH;
		const scale = Math.max( scX, scY );

		bgImg.set( {
			scaleX: scale,
			scaleY: scale,
			originX: 'center',
			originY: 'center',
			left: bgWidth / 2,
			top: bgHeight / 2,
			selectable: false,
			evented: false,
		} );
		canvas.add( bgImg );
		canvas.sendObjectToBack( bgImg );
	}

	if ( isBuilderData ) {
		await _loadBuilderLayers( canvas, jsonData );
	} else {
		await _loadOldLayers( canvas, jsonData.layers || [], noImageUrl );
	}

	canvas.renderAll();

	const dataURL = canvas.toDataURL( { format: 'png', quality: 1 } );

	return { canvas, dataURL };
}

async function _loadBuilderLayers( canvas, data ) {
	const processedData = replaceNewlinesForLoad( data );
	const layers = processedData.layers || [];

	if ( ! Array.isArray( layers ) || layers.length === 0 ) {
		return;
	}

	for ( let i = 0; i < layers.length; i++ ) {
		const layer = layers[ i ];

		try {
			const customType = layer.type_layer;
			if ( ! layer || ! customType ) {
				continue;
			}

			const { id, name, type_layer, type: fabricType, text, src, canvas: _c, group, stateProperties, cacheProperties, _placeholder_text, ...layerProps } = layer;

			let fabricObj = null;
			const isTextElement = isTextType( customType );
			let textReplacementFix = null;

			if ( isTextElement ) {
				const normalizeNewlines = ( s ) => ( String( s ) || '' )
					.replace( /\{n\}/g, '\n' )
					.replace( /\/n/g, '\n' )
					.replace( /&quot;/g, '"' );

				const textContent = normalizeNewlines( text );
				const placeholderContent = _placeholder_text
					? normalizeNewlines( _placeholder_text )
					: textContent;
				const hasReplacement = placeholderContent !== textContent;

				const targetFontFamily = layerProps.fontFamily || DEFAULT_FONT;

				const initialText = hasReplacement ? placeholderContent : textContent;
				const tempProps = { ...layerProps, fontFamily: DEFAULT_FONT, type: fabricType };
				fabricObj = await createFabricElement( customType, initialText, tempProps );

				if ( fabricObj ) {
					fabricObj.set( 'fontFamily', await resolveFont( targetFontFamily ) );

					if ( typeof fabricObj.initDimensions === 'function' ) {
						fabricObj.initDimensions();
					}

					if ( hasReplacement ) {
						const { clipPath: _cp, ...innerProps } = layerProps;
						fabricObj.set( innerProps );
						fabricObj.setCoords();

						const boundingRect = fabricObj.getBoundingRect();
						const visualTop = boundingRect.top;
						const originX = fabricObj.originX || 'center';
						let visualAnchorX = fabricObj.left;
						switch ( originX ) {
							case 'left':
								visualAnchorX = boundingRect.left;
								break;
							case 'right':
								visualAnchorX = boundingRect.left + boundingRect.width;
								break;
							case 'center':
							default:
								visualAnchorX = boundingRect.left + boundingRect.width / 2;
								break;
						}

						fabricObj.set( 'text', textContent );
						if ( typeof fabricObj.initDimensions === 'function' ) {
							fabricObj.initDimensions();
						}

						textReplacementFix = {
							originY: 'top',
							top: visualTop,
							left: visualAnchorX,
						};
					}

					fabricObj.set( 'dirty', true );
				}
			} else if ( customType === 'qr_code' ) {
				const qrUrl = layer.qr_url || src;
				if ( ! qrUrl ) {
					continue;
				}

				const displayWidth = ( layerProps.width || 100 ) * ( layerProps.scaleX || 1 );
				const displayHeight = ( layerProps.height || 100 ) * ( layerProps.scaleY || 1 );
				const qrSize = Math.max( displayWidth, displayHeight );

				const qrRenderSize = qrSize * 3;
				const qrDataURL = await generateQRDataURL( qrUrl, qrRenderSize );
				if ( ! qrDataURL ) {
					continue;
				}

				const qrImage = await loadFabricImage( qrDataURL );
				if ( qrImage ) {
					qrImage.set( {
						left: layerProps.left || 0,
						top: layerProps.top || 0,
						originX: layerProps.originX || 'center',
						originY: layerProps.originY || 'center',
						scaleX: qrSize / qrImage.width,
						scaleY: qrSize / qrImage.height,
						selectable: false,
						evented: false,
						type_layer: 'qr_code',
						qr_url: qrUrl,
					} );
					qrImage.setCoords();
					canvas.add( qrImage );
				}
				continue;
			} else if ( customType === 'image' ) {
				if ( ! src ) {
					continue;
				}
				fabricObj = await createImageElementWithFallback( customType, src, layerProps, data.no_image_url || '', `layer ${ i }` );
			} else if ( isSvgType( customType ) ) {
				fabricObj = await createFabricElement( customType, layer );
			}

			if ( fabricObj ) {
				if ( ! isSvgType( customType ) && ! textReplacementFix ) {
					const { clipPath, ...propsToSet } = layerProps;
					fabricObj.set( propsToSet );
				}

				if ( isTextElement && layerProps.originX && ! textReplacementFix ) {
					fabricObj.set( 'originX', layerProps.originX );
				}

				if ( textReplacementFix ) {
					fabricObj.set( textReplacementFix );
				}

				fabricObj.set( { selectable: false, evented: false } );
				fabricObj.setCoords();
				canvas.add( fabricObj );

				if ( isTextElement && ! textReplacementFix ) {
					handleTextAutoExpand( fabricObj );
				}
			}
		} catch ( layerError ) {
			console.error( `renderCertificate: Error loading layer ${ i }:`, layerError );
		}
	}
}

async function _loadOldLayers( canvas, layers, noImageUrl = '' ) {
	for ( const key in layers ) {
		if ( ! layers.hasOwnProperty( key ) ) {
			continue;
		}
		const layer = layers[ key ];
		if ( ! layer ) {
			continue;
		}

		try {
			const text = _htmlDecode( layer.text ) || '';
			const isUrl = /^(https?|s?ftp):\/\//i.test( text );

			const isImage = layer.fieldType === 'verified-link' ||
				layer.type_layer === 'qr_code' ||
				layer.type === 'qr_code' ||
				layer.type === 'image';

			if ( isImage && isUrl ) {
				const img = await loadFabricImageWithFallback( text, noImageUrl, `old layer ${ key }` );
				if ( ! img ) {
					continue;
				}
				img.set( {
					left: parseFloat( layer.left ) || 0,
					top: parseFloat( layer.top ) || 0,
					originX: layer.originX || 'center',
					originY: layer.originY || 'center',
					selectable: false,
					evented: false,
				} );
				if ( layer.scaleX ) {
					img.set( 'scaleX', parseFloat( layer.scaleX ) );
				}
				if ( layer.scaleY ) {
					img.set( 'scaleY', parseFloat( layer.scaleY ) );
				}
				canvas.add( img );
				continue;
			}

			const textProps = {
				fontSize: parseFloat( layer.fontSize ) || 24,
				left: parseFloat( layer.left ) || 0,
				top: parseFloat( layer.top ) || 0,
				lineHeight: parseFloat( layer.lineHeight ) || 1,
				originX: layer.originX || 'center',
				originY: layer.originY || 'center',
				fontFamily: layer.fontFamily || 'Helvetica',
				fill: layer.fill || layer.color || '#000000',
				textAlign: layer.textAlign || 'center',
				fontWeight: layer.fontWeight || 'normal',
				fontStyle: layer.fontStyle || 'normal',
				angle: parseFloat( layer.angle ) || 0,
				selectable: false,
				evented: false,
			};

			if ( layer.scaleX ) {
				textProps.scaleX = Math.abs( parseFloat( layer.scaleX ) );
				textProps.flipX = parseFloat( layer.scaleX ) < 0;
			}
			if ( layer.scaleY ) {
				textProps.scaleY = Math.abs( parseFloat( layer.scaleY ) );
				textProps.flipY = parseFloat( layer.scaleY ) < 0;
			}

			const customType = layer.type || layer.type_layer || 'text-static';
			const fabricObj = await createFabricElement( customType, text, textProps );
			if ( fabricObj ) {
				fabricObj.set( { selectable: false, evented: false } );
				canvas.add( fabricObj );
			}
		} catch ( e ) {
			console.error( 'renderCertificate: Error loading old layer:', key, e );
		}
	}
}

function _htmlDecode( input ) {
	if ( ! input ) {
		return '';
	}
	const strInput = String( input );
	const e = document.createElement( 'div' );
	e.innerHTML = strInput;
	return e.childNodes.length === 0 ? '' : e.childNodes[ 0 ].nodeValue;
}

function getExpectedOriginX( textAlign ) {
	switch ( textAlign ) {
		case 'left':
			return 'left';
		case 'right':
			return 'right';
		case 'center':
		case 'justify':
		default:
			return 'center';
	}
}

function handleTextAutoExpand( obj ) {
	if ( ! obj ) {
		return;
	}

	const textAlign = obj.textAlign || 'left';
	const originX = obj.originX || 'center';
	const expectedOriginX = getExpectedOriginX( textAlign );

	if ( originX === expectedOriginX ) {
		return;
	}

	const boundingRect = obj.getBoundingRect();
	const visualLeft = boundingRect.left;
	const visualRight = boundingRect.left + boundingRect.width;
	const visualCenter = boundingRect.left + boundingRect.width / 2;

	let newLeft = obj.left;

	switch ( textAlign ) {
		case 'left':
			obj.set( 'originX', 'left' );
			newLeft = visualLeft;
			break;
		case 'right':
			obj.set( 'originX', 'right' );
			newLeft = visualRight;
			break;
		case 'center':
		case 'justify':
			obj.set( 'originX', 'center' );
			newLeft = visualCenter;
			break;
	}

	obj.set( 'left', newLeft );
	obj.setCoords();
}

function isTextObject( obj ) {
	return obj.type === 'i-text' || obj.type === 'textbox';
}

function replacePlaceholders( text, replacements ) {
	if ( ! text || ! replacements ) {
		return text;
	}

	let result = String( text );
	Object.keys( replacements ).forEach( ( key ) => {
		const value = replacements[ key ];
		if ( value ) {
			const regex = new RegExp( `\\[${ key }\\]`, 'g' );
			result = result.replace( regex, String( value ) );
		}
	} );

	return result;
}

async function _cloneCanvasForExport( sourceCanvas, replacements = {} ) {
	const tempCanvasEl = document.createElement( 'canvas' );
	const tempCanvas = new Canvas( tempCanvasEl, {
		width: sourceCanvas.getWidth(),
		height: sourceCanvas.getHeight(),
		backgroundColor: sourceCanvas.backgroundColor || '#ffffff',
		selection: false,
	} );

	if ( sourceCanvas.backgroundImage ) {
		try {
			const bgUrl = sourceCanvas.backgroundImage.getSrc();
			if ( bgUrl ) {
				const img = await loadFabricImage( bgUrl );
				if ( img ) {
					img.set( {
						scaleX: sourceCanvas.backgroundImage.scaleX,
						scaleY: sourceCanvas.backgroundImage.scaleY,
						originX: sourceCanvas.backgroundImage.originX,
						originY: sourceCanvas.backgroundImage.originY,
						left: sourceCanvas.backgroundImage.left,
						top: sourceCanvas.backgroundImage.top,
						opacity: sourceCanvas.backgroundImage.opacity,
					} );
					tempCanvas.backgroundImage = img;
				}
			}
		} catch ( error ) {
			console.error( 'Error copying background:', error );
		}
	}

	const qrObjects = new Map();
	const objects = sourceCanvas.getObjects();

	for ( const obj of objects ) {
		try {
			const cloned = await obj.clone();
			cloned.set( {
				selectable: false,
				evented: false,
			} );

			if ( isQrObject( obj ) ) {
				collectQrObject( obj, cloned, qrObjects );
				tempCanvas.add( cloned );
			} else if ( isTextObject( cloned ) ) {
				handleTextAutoExpand( cloned );

				if ( Object.keys( replacements ).length > 0 ) {
					const originalText = cloned.get( 'text' ) || '';
					const newText = replacePlaceholders( originalText, replacements );

					if ( newText !== originalText ) {
						cloned.setCoords();
						const boundingRect = cloned.getBoundingRect();
						const visualTop = boundingRect.top;
						const originX = cloned.originX || 'center';
						let visualAnchorX = cloned.left;
						switch ( originX ) {
							case 'left':
								visualAnchorX = boundingRect.left;
								break;
							case 'right':
								visualAnchorX = boundingRect.left + boundingRect.width;
								break;
							case 'center':
							default:
								visualAnchorX = boundingRect.left + boundingRect.width / 2;
								break;
						}

						cloned.set( 'text', newText );
						if ( typeof cloned.initDimensions === 'function' ) {
							cloned.initDimensions();
						}
						cloned.set( {
							originY: 'top',
							top: visualTop,
							left: visualAnchorX,
						} );
						cloned.setCoords();
					}
				}

				tempCanvas.add( cloned );
			} else {
				tempCanvas.add( cloned );
			}
		} catch ( error ) {
			console.error( 'Error cloning object:', error );
		}
	}

	if ( qrObjects.size > 0 ) {
		const qrUrl = replacements.QR_CODE || null;
		await applyQrReplacements( qrObjects, qrUrl, tempCanvas );
	}

	tempCanvas.renderAll();

	return tempCanvas;
}

export async function downloadCertificate( sourceCanvas, options = {} ) {
	if ( ! sourceCanvas ) {
		throw new Error( 'Source canvas is required' );
	}

	const {
		replacements = {},
		filename = `certificate-${ Date.now() }.png`,
		format = 'png',
		quality = 1,
		multiplier = 2,
	} = options;

	const tempCanvas = await _cloneCanvasForExport( sourceCanvas, replacements );

	const dataURL = tempCanvas.toDataURL( {
		format,
		quality,
		multiplier,
	} );

	const link = document.createElement( 'a' );
	link.download = filename;
	link.href = dataURL;
	document.body.appendChild( link );
	link.click();
	document.body.removeChild( link );

	tempCanvas.dispose();

	return dataURL;
}

export async function getCertificateDataURL( sourceCanvas, options = {} ) {
	if ( ! sourceCanvas ) {
		throw new Error( 'Source canvas is required' );
	}

	const {
		replacements = {},
		format = 'png',
		quality = 1,
		multiplier = 2,
	} = options;

	const tempCanvas = await _cloneCanvasForExport( sourceCanvas, replacements );

	const dataURL = tempCanvas.toDataURL( {
		format,
		quality,
		multiplier,
	} );

	tempCanvas.dispose();

	return dataURL;
}

export async function downloadCertificateAsPDF( sourceCanvas, options = {} ) {
	if ( ! sourceCanvas ) {
		throw new Error( 'Source canvas is required' );
	}

	const {
		replacements = {},
		filename = `certificate-${ Date.now() }.pdf`,
		multiplier = 2,
	} = options;

	const dataURL = await getCertificateDataURL( sourceCanvas, {
		replacements,
		format: 'png',
		quality: 1,
		multiplier,
	} );

	const pxToMm = 25.4 / 96;
	const pdfWidth = sourceCanvas.getWidth() * pxToMm;
	const pdfHeight = sourceCanvas.getHeight() * pxToMm;

	const orientation = pdfWidth >= pdfHeight ? 'landscape' : 'portrait';

	const pdf = new jsPDF( {
		orientation,
		unit: 'mm',
		format: [ pdfWidth, pdfHeight ],
	} );

	pdf.addImage( dataURL, 'PNG', 0, 0, pdfWidth, pdfHeight );
	pdf.save( filename );
}

export { handleTextAutoExpand, getExpectedOriginX, isTextObject, replacePlaceholders };
