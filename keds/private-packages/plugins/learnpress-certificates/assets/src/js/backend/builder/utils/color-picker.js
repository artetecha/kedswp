import iro from '@jaames/iro';

let idCounter = 0;

function parseColorToHex( input ) {
	if ( ! input ) {
		return null;
	}

	// Already hex: #fff or #ffffff
	if ( /^#[0-9A-Fa-f]{6}$/.test( input ) ) {
		return input.toLowerCase();
	}
	if ( /^#[0-9A-Fa-f]{3}$/.test( input ) ) {
		const r = input[1], g = input[2], b = input[3];
		return ( '#' + r + r + g + g + b + b ).toLowerCase();
	}
	// Hex without #
	if ( /^[0-9A-Fa-f]{6}$/.test( input ) ) {
		return '#' + input.toLowerCase();
	}
	if ( /^[0-9A-Fa-f]{3}$/.test( input ) ) {
		const r = input[0], g = input[1], b = input[2];
		return ( '#' + r + r + g + g + b + b ).toLowerCase();
	}

	// rgb(r, g, b) or rgba(r, g, b, a)
	const rgbMatch = input.match( /^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/ );
	if ( rgbMatch ) {
		const toHex = ( n ) => Math.min( 255, Math.max( 0, parseInt( n ) ) ).toString( 16 ).padStart( 2, '0' );
		return ( '#' + toHex( rgbMatch[1] ) + toHex( rgbMatch[2] ) + toHex( rgbMatch[3] ) ).toLowerCase();
	}

	// CSS color name — use browser to parse
	const temp = document.createElement( 'span' );
	temp.style.color = input;
	if ( temp.style.color ) {
		document.body.appendChild( temp );
		const computed = getComputedStyle( temp ).color;
		document.body.removeChild( temp );
		const m = computed.match( /^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/ );
		if ( m ) {
			const toHex = ( n ) => parseInt( n ).toString( 16 ).padStart( 2, '0' );
			return ( '#' + toHex( m[1] ) + toHex( m[2] ) + toHex( m[3] ) ).toLowerCase();
		}
	}

	return null;
}

export function initColorPicker( container, opts = {} ) {
	const el = typeof container === 'string' ? document.querySelector( container ) : container;
	if ( ! el ) {
		return null;
	}

	if ( ! el.id ) {
		el.id = 'iro-picker-' + ( ++idCounter );
	}

	const initialColor = opts.color || '#ffffff';
	const width = opts.width || 200;
	let isProgrammatic = false;

	const pickerEl = document.createElement( 'div' );
	el.appendChild( pickerEl );

	const picker = new iro.ColorPicker( pickerEl, {
		width: width,
		color: initialColor,
		borderWidth: 1,
		borderColor: '#ddd',
		layoutDirection: 'vertical',
		layout: [
			{
				component: iro.ui.Box,
				options: {},
			},
			{
				component: iro.ui.Slider,
				options: {
					sliderType: 'hue',
				},
			},
		],
	} );

	const hexWrap = document.createElement( 'div' );
	hexWrap.className = 'iro-hex-input-wrap';
	const hexInput = document.createElement( 'input' );
	hexInput.type = 'text';
	hexInput.className = 'iro-hex-input';
	hexInput.value = initialColor;
	// hexInput.maxLength = 7;
	hexWrap.appendChild( hexInput );
	el.appendChild( hexWrap );

	hexInput.addEventListener( 'keydown', function( e ) {
		if ( e.key === 'Enter' ) {
			e.preventDefault();
			hexInput.blur();
		}
	} );

	hexInput.addEventListener( 'change', function() {
		const val = hexInput.value.trim();
		const hex = parseColorToHex( val );
		if ( hex ) {
			isProgrammatic = true;
			picker.color.hexString = hex;
			isProgrammatic = false;
			hexInput.value = hex;
			if ( opts.onChange ) {
				opts.onChange( hex );
			}
		} else {
			hexInput.value = picker.color.hexString;
		}
	} );

	picker.on( 'color:change', function( color ) {
		const hex = color.hexString;
		hexInput.value = hex;
		if ( ! isProgrammatic && opts.onChange ) {
			opts.onChange( hex );
		}
	} );

	function setValue( color ) {
		if ( ! color ) {
			return;
		}
		isProgrammatic = true;
		try {
			picker.color.hexString = color;
		} catch ( e ) {
		}
		hexInput.value = color;
		isProgrammatic = false;
	}

	return { picker, setValue };
}
