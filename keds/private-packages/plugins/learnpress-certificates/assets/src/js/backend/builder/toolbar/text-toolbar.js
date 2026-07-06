import { Textbox, IText } from 'fabric';
import { setControls } from 'AssetsJsPath/backend/builder/core';
import { resolveFont } from 'AssetsJsPath/backend/builder/utils';
import { TEXT_DEFAULTS, TIMING } from 'AssetsJsPath/backend/builder/config';
import { preparePositionPanel } from './position-panel';
import { togglePopupInContainer, hideActivePopup, lockActivePopup, unlockActivePopup } from './popup-manager';
import { initColorPicker as createColorPicker } from 'AssetsJsPath/backend/builder/utils/color-picker';

const SELECTORS = {
	toolbar: '.lp-cert-text-toolbar',
	fontFamily: '.lp-cert-text-toolbar__font-family',
	fontSize: '.lp-cert-text-toolbar__font-size',
	fontColor: '.lp-cert-text-toolbar__font-color',
	colorPreview: '.lp-cert-text-toolbar__color-preview',
	colorTrigger: '.lp-cert-text-toolbar__color-trigger',
	colorPopup: '.lp-cert-text-toolbar__popup[data-popup-id="color"]',
	popup: '.lp-cert-text-toolbar__popup',
	bold: '.lp-cert-text-toolbar__bold',
	italic: '.lp-cert-text-toolbar__italic',
	underline: '.lp-cert-text-toolbar__underline',
	linethrough: '.lp-cert-text-toolbar__linethrough',
	alignLeft: '.lp-cert-text-toolbar__align-left',
	alignCenter: '.lp-cert-text-toolbar__align-center',
	alignRight: '.lp-cert-text-toolbar__align-right',
	alignJustify: '.lp-cert-text-toolbar__align-justify',
	letterSpacing: '.lp-cert-text-toolbar__letter-spacing',
	lineHeight: '.lp-cert-text-toolbar__line-height',
	stepperBtns: '.lp-cert-text-toolbar__stepper',
	advancedTrigger: '.lp-cert-text-toolbar__trigger[data-popup="advanced-settings"]',
	advancedPopup: '.lp-cert-text-toolbar__popup[data-popup-id="advanced-settings"]',
	textwrapTrigger: '.lp-cert-text-toolbar__textwrap-trigger',
	textwrapPopup: '.lp-cert-text-toolbar__popup[data-popup-id="textwrap"]',
	convertTextbox: '.lp-cert-text-toolbar__convert-textbox',
	textboxWidth: '.lp-cert-text-toolbar__textbox-width',
	positionToggle: '.lp-cert-text-toolbar__position-toggle',
};

let canvas = null;
let layerManager = null;
let activeObject = null;
let isInitialized = false;
let colorPickerInitialized = false;
let textColorPicker = null;
let isPopulating = false;

export function initTextToolbar( canvasInstance, layerManagerInstance ) {
	canvas = canvasInstance;
	layerManager = layerManagerInstance;

	if ( isInitialized ) {
		return;
	}

	setupEventListeners();
	isInitialized = true;
}

export function showTextToolbar( fabricObject ) {
	const toolbar = document.querySelector( SELECTORS.toolbar );
	if ( ! toolbar ) {
		return;
	}

	activeObject = fabricObject;
	toolbar.classList.add( 'is-visible' );

	populateToolbarValues( fabricObject );
	initColorPicker();
}

export function hideTextToolbar() {
	const toolbar = document.querySelector( SELECTORS.toolbar );
	if ( toolbar ) {
		toolbar.classList.remove( 'is-visible' );
	}
	hideActivePopup();
	activeObject = null;
}

function populateToolbarValues( obj ) {
	if ( ! obj ) {
		return;
	}

	isPopulating = true;

	const fontFamilySelect = document.querySelector( SELECTORS.fontFamily );
	if ( fontFamilySelect ) {
		fontFamilySelect.value = obj.fontFamily || TEXT_DEFAULTS.FONT_FAMILY;
	}

	const fontSizeInput = document.querySelector( SELECTORS.fontSize );
	if ( fontSizeInput ) {
		fontSizeInput.value = obj.fontSize || TEXT_DEFAULTS.FONT_SIZE;
	}

	const fontColorInput = document.querySelector( SELECTORS.fontColor );
	if ( fontColorInput ) {
		const color = obj.fill || TEXT_DEFAULTS.FILL_COLOR;
		fontColorInput.value = color;

		const colorPreview = document.querySelector( SELECTORS.colorPreview );
		if ( colorPreview ) {
			colorPreview.style.backgroundColor = color;
		}

		if ( textColorPicker ) {
			textColorPicker.setValue( color );
		}
	}

	const boldBtn = document.querySelector( SELECTORS.bold );
	if ( boldBtn ) {
		const isBold = obj.fontWeight === 'bold' || obj.fontWeight >= 700;
		boldBtn.classList.toggle( 'is-active', isBold );
	}

	const italicBtn = document.querySelector( SELECTORS.italic );
	if ( italicBtn ) {
		italicBtn.classList.toggle( 'is-active', obj.fontStyle === 'italic' );
	}

	const underlineBtn = document.querySelector( SELECTORS.underline );
	if ( underlineBtn ) {
		underlineBtn.classList.toggle( 'is-active', obj.underline === true );
	}

	const linethroughBtn = document.querySelector( SELECTORS.linethrough );
	if ( linethroughBtn ) {
		linethroughBtn.classList.toggle( 'is-active', obj.linethrough === true );
	}

	updateAlignButtons( obj.textAlign || 'left' );

	const letterSpacingInput = document.querySelector( SELECTORS.letterSpacing );
	if ( letterSpacingInput ) {
		letterSpacingInput.value = obj.charSpacing || TEXT_DEFAULTS.CHAR_SPACING;
	}

	const lineHeightInput = document.querySelector( SELECTORS.lineHeight );
	if ( lineHeightInput ) {
		lineHeightInput.value = obj.lineHeight || TEXT_DEFAULTS.LINE_HEIGHT;
	}

	updateTextWrapState( obj );

	isPopulating = false;
}

function updateAlignButtons( align ) {
	const alignButtons = {
		left: document.querySelector( SELECTORS.alignLeft ),
		center: document.querySelector( SELECTORS.alignCenter ),
		right: document.querySelector( SELECTORS.alignRight ),
		justify: document.querySelector( SELECTORS.alignJustify ),
	};

	Object.entries( alignButtons ).forEach( ( [ key, btn ] ) => {
		if ( btn ) {
			btn.classList.toggle( 'is-active', key === align );
		}
	} );
}

function initColorPicker() {
	if ( colorPickerInitialized ) {
		return;
	}

	const popupContent = document.querySelector( SELECTORS.colorPopup + ' .lp-cert-text-toolbar__popup-content' );
	const fontColorInput = document.querySelector( SELECTORS.fontColor );
	if ( ! popupContent || ! fontColorInput ) {
		return;
	}

	fontColorInput.style.display = 'none';

	const container = document.createElement( 'div' );
	container.className = 'iro-color-picker-container';
	popupContent.appendChild( container );

	textColorPicker = createColorPicker( container, {
		color: fontColorInput.value || TEXT_DEFAULTS.FILL_COLOR,
		mode: 'inline',
		width: 200,
		onChange: function( color ) {
			applyTextProperty( 'fill', color );
			const colorPreview = document.querySelector( SELECTORS.colorPreview );
			if ( colorPreview ) {
				colorPreview.style.backgroundColor = color;
			}
			fontColorInput.value = color;
		},
	} );

	colorPickerInitialized = true;
}

function setupEventListeners() {
	const colorTrigger = document.querySelector( SELECTORS.colorTrigger );
	const colorPopup = document.querySelector( SELECTORS.colorPopup );
	if ( colorTrigger && colorPopup ) {
		colorTrigger.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			togglePopupInContainer( colorPopup, colorTrigger );
		} );
	}

	const fontFamilySelect = document.querySelector( SELECTORS.fontFamily );
	if ( fontFamilySelect ) {
		fontFamilySelect.addEventListener( 'change', ( e ) => {
			applyTextProperty( 'fontFamily', e.target.value );
		} );
	}

	const fontSizeInput = document.querySelector( SELECTORS.fontSize );
	if ( fontSizeInput ) {
		fontSizeInput.addEventListener( 'change', ( e ) => {
			const size = parseInt( e.target.value, 10 );
			if ( size > 0 ) {
				applyTextProperty( 'fontSize', size );
			}
		} );
	}

	const stepperButtons = document.querySelectorAll( SELECTORS.stepperBtns );
	stepperButtons.forEach( ( btn ) => {
		btn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			const target = btn.getAttribute( 'data-target' );
			let input = null;
			let step = 1;
			let min = 0;
			let max = 999;

			if ( target === 'letter-spacing' ) {
				input = document.querySelector( SELECTORS.letterSpacing );
				step = 10;
				min = -100;
				max = 500;
			} else if ( target === 'line-height' ) {
				input = document.querySelector( SELECTORS.lineHeight );
				step = 0.01;
				min = 0.5;
				max = 5;
			} else {
				input = fontSizeInput;
				step = 1;
				min = 8;
				max = 200;
			}

			if ( ! input ) return;
			let val = parseFloat( input.value ) || 0;
			if ( btn.classList.contains( 'lp-cert-text-toolbar__stepper--plus' ) ) {
				val = Math.min( val + step, max );
			} else {
				val = Math.max( val - step, min );
			}
			input.value = Number.isInteger( step ) ? val : parseFloat( val.toFixed( 2 ) );
			input.dispatchEvent( new Event( 'change' ) );
		} );
	} );

	const advancedTrigger = document.querySelector( SELECTORS.advancedTrigger );
	const advancedPopup = document.querySelector( SELECTORS.advancedPopup );
	if ( advancedTrigger && advancedPopup ) {
		advancedTrigger.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			togglePopupInContainer( advancedPopup, advancedTrigger );
		} );
	}

	const textwrapTrigger = document.querySelector( SELECTORS.textwrapTrigger );
	const textwrapPopup = document.querySelector( SELECTORS.textwrapPopup );
	if ( textwrapTrigger && textwrapPopup ) {
		textwrapTrigger.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			togglePopupInContainer( textwrapPopup, textwrapTrigger );
		} );
	}

	const convertTextboxBtn = document.querySelector( SELECTORS.convertTextbox );
	if ( convertTextboxBtn ) {
		convertTextboxBtn.addEventListener( 'click', () => {
			if ( ! activeObject || ! canvas ) {
				return;
			}
			toggleTextWrap( activeObject );
		} );
	}

	const textboxWidthInput = document.querySelector( SELECTORS.textboxWidth );
	if ( textboxWidthInput ) {
		textboxWidthInput.addEventListener( 'change', ( e ) => {
			if ( isPopulating || ! activeObject || ! canvas ) {
				return;
			}
			const currentType = activeObject.type?.toLowerCase();
			if ( currentType !== 'textbox' ) {
				return;
			}
			let newWidth = parseInt( e.target.value, 10 );
			if ( isNaN( newWidth ) || newWidth < 10 ) {
				newWidth = 10;
				e.target.value = newWidth;
			}
			activeObject.set( 'width', newWidth );
			activeObject.setCoords();
			canvas.requestRenderAll();
			saveLayerData();
		} );
	}

	const positionToggle = document.querySelector( SELECTORS.positionToggle );
	if ( positionToggle ) {
		positionToggle.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			if ( activeObject ) {
				const panel = document.querySelector( '.lp-cert-position-panel' );
				preparePositionPanel( activeObject );
				togglePopupInContainer( panel, positionToggle );
			}
		} );
	}

	const boldBtn = document.querySelector( SELECTORS.bold );
	if ( boldBtn ) {
		boldBtn.addEventListener( 'click', () => {
			if ( ! activeObject ) return;
			const isBold = activeObject.fontWeight === 'bold' || activeObject.fontWeight >= 700;
			applyTextProperty( 'fontWeight', isBold ? 'normal' : 'bold' );
			boldBtn.classList.toggle( 'is-active' );
		} );
	}

	const italicBtn = document.querySelector( SELECTORS.italic );
	if ( italicBtn ) {
		italicBtn.addEventListener( 'click', () => {
			if ( ! activeObject ) return;
			const isItalic = activeObject.fontStyle === 'italic';
			applyTextProperty( 'fontStyle', isItalic ? 'normal' : 'italic' );
			italicBtn.classList.toggle( 'is-active' );
		} );
	}

	const underlineBtn = document.querySelector( SELECTORS.underline );
	if ( underlineBtn ) {
		underlineBtn.addEventListener( 'click', () => {
			if ( ! activeObject ) return;
			applyTextProperty( 'underline', ! activeObject.underline );
			underlineBtn.classList.toggle( 'is-active' );
		} );
	}

	const linethroughBtn = document.querySelector( SELECTORS.linethrough );
	if ( linethroughBtn ) {
		linethroughBtn.addEventListener( 'click', () => {
			if ( ! activeObject ) return;
			applyTextProperty( 'linethrough', ! activeObject.linethrough );
			linethroughBtn.classList.toggle( 'is-active' );
		} );
	}

	const alignments = [ 'left', 'center', 'right', 'justify' ];
	alignments.forEach( ( align ) => {
		const btn = document.querySelector( SELECTORS[ `align${ align.charAt( 0 ).toUpperCase() + align.slice( 1 ) }` ] );
		if ( btn ) {
			btn.addEventListener( 'click', () => {
				applyTextProperty( 'textAlign', align );
				updateAlignButtons( align );
			} );
		}
	} );

	const letterSpacingInput = document.querySelector( SELECTORS.letterSpacing );
	if ( letterSpacingInput ) {
		letterSpacingInput.addEventListener( 'change', ( e ) => {
			const spacing = parseInt( e.target.value, 10 );
			applyTextProperty( 'charSpacing', spacing );
		} );
	}

	const lineHeightInput = document.querySelector( SELECTORS.lineHeight );
	if ( lineHeightInput ) {
		lineHeightInput.addEventListener( 'change', ( e ) => {
			const height = parseFloat( e.target.value );
			if ( height > 0 ) {
				applyTextProperty( 'lineHeight', height );
			}
		} );
	}

}

function applyTextProperty( property, value ) {
	if ( ! activeObject || ! canvas ) {
		return;
	}

	if ( property === 'fontFamily' ) {
		applyFontFamily( activeObject, value );
	} else {
		activeObject.set( property, value );
		activeObject.setCoords();
		canvas.requestRenderAll();
		saveLayerData();
	}
}

async function applyFontFamily( obj, fontFamily ) {
	if ( ! obj || ! canvas ) {
		return;
	}

	obj.set( 'fontFamily', await resolveFont( fontFamily ) );

	if ( typeof obj.initDimensions === 'function' ) {
		obj.initDimensions();
	}
	obj.set( 'dirty', true );
	obj.setCoords();
	canvas.requestRenderAll();
	saveLayerData();
}

export function updateTextboxWidthInput( obj ) {
	if ( ! obj ) {
		return;
	}
	const currentType = obj.type?.toLowerCase();
	if ( currentType !== 'textbox' ) {
		return;
	}
	const widthInput = document.querySelector( SELECTORS.textboxWidth );
	if ( widthInput && ! widthInput.classList.contains( 'lp-hidden' ) ) {
		widthInput.value = Math.round( obj.width || 300 );
	}
}

function updateTextWrapState( obj ) {
	const convertBtn = document.querySelector( SELECTORS.convertTextbox );
	const widthInput = document.querySelector( SELECTORS.textboxWidth );
	if ( ! obj ) {
		return;
	}

	const currentType = obj.type?.toLowerCase();
	const isTextbox = currentType === 'textbox';

	if ( convertBtn ) {
		convertBtn.classList.toggle( 'is-active', isTextbox );
	}
	if ( widthInput ) {
		if ( isTextbox ) {
			widthInput.classList.remove( 'lp-hidden' );
			widthInput.value = Math.round( obj.width || 300 );
		} else {
			widthInput.classList.add( 'lp-hidden' );
		}
	}
}

async function toggleTextWrap( obj ) {
	if ( ! obj || ! canvas || ! layerManager ) {
		return;
	}

	const currentType = obj.type?.toLowerCase();

	if ( currentType === 'textbox' ) {
		await convertToIText( obj );
	} else if ( currentType === 'i-text' || currentType === 'itext' || currentType === 'text' ) {
		await convertToTextbox( obj );
	}
}

async function convertToTextbox( obj ) {
	const props = obj.toObject( [ 'name', 'id', 'type_layer' ] );
	const text = obj.text || '';
	const origType = obj.get( 'type_layer' ) || '';
	const isStatic = origType === 'text-static';

	delete props.type;
	delete props.width;

	const textbox = new Textbox( text, {
		...props,
		width: 300,
		splitByGrapheme: false,
		editable: ! isStatic,
	} );

	textbox.set( 'type_layer', isStatic ? 'text-static' : 'text-edit' );
	setControls( textbox );

	const layerId = obj.get( 'id' );

	lockActivePopup();
	canvas.remove( obj );
	canvas.add( textbox );
	canvas.setActiveObject( textbox );
	canvas.requestRenderAll();
	unlockActivePopup();

	activeObject = textbox;

	if ( layerId ) {
		layerManager.updateLayerDataFromObject( layerId, textbox );
		layerManager.saveCanvasLayers( false, TIMING.DEBOUNCE_SAVE );
	}

	updateTextWrapState( textbox );
}

async function convertToIText( obj ) {
	const props = obj.toObject( [ 'name', 'id', 'type_layer' ] );
	const text = obj.text || '';
	const origType = obj.get( 'type_layer' ) || '';
	const isStatic = origType === 'text-static';

	delete props.type;
	delete props.width;
	delete props.splitByGrapheme;

	const itext = new IText( text, {
		...props,
		editable: ! isStatic,
	} );

	itext.set( 'type_layer', isStatic ? 'text-static' : 'text-edit' );
	setControls( itext );

	const layerId = obj.get( 'id' );

	lockActivePopup();
	canvas.remove( obj );
	canvas.add( itext );
	canvas.setActiveObject( itext );
	canvas.requestRenderAll();
	unlockActivePopup();

	activeObject = itext;

	if ( layerId ) {
		layerManager.updateLayerDataFromObject( layerId, itext );
		layerManager.saveCanvasLayers( false, TIMING.DEBOUNCE_SAVE );
	}

	updateTextWrapState( itext );
}

function saveLayerData() {
	if ( isPopulating || ! activeObject || ! layerManager ) {
		return;
	}

	const layerId = activeObject.get( 'id' );
	if ( ! layerId ) {
		return;
	}
	layerManager.updateLayerDataFromObject( layerId, activeObject );
	layerManager.saveCanvasLayers( false, TIMING.DEBOUNCE_SAVE, false, TIMING.DEBOUNCE_HISTORY );
}

