const DROPDOWN_CLASS = 'lp-cert-placeholder-autocomplete';

let canvasRef = null;
let dropdownEl = null;
let state = {
	active: false,
	obj: null,
	textarea: null,
	triggerPos: -1,
	filter: '',
	items: [],
	activeIndex: 0,
};

function getPlaceholders() {
	if ( typeof window.lpCertSettings === 'object' && Array.isArray( window.lpCertSettings.placeholders ) ) {
		return window.lpCertSettings.placeholders;
	}
	return [];
}

function ensureDropdown() {
	if ( dropdownEl && document.body.contains( dropdownEl ) ) {
		return dropdownEl;
	}
	dropdownEl = document.createElement( 'ul' );
	dropdownEl.className = DROPDOWN_CLASS;
	dropdownEl.style.display = 'none';
	document.body.appendChild( dropdownEl );
	return dropdownEl;
}

function closeDropdown() {
	state.active = false;
	state.triggerPos = -1;
	state.filter = '';
	state.items = [];
	state.activeIndex = 0;
	if ( dropdownEl ) {
		dropdownEl.style.display = 'none';
		dropdownEl.innerHTML = '';
	}
}

function resetAll() {
	closeDropdown();
	state.obj = null;
	state.textarea = null;
}

function positionDropdown( obj ) {
	if ( ! dropdownEl || ! obj || ! canvasRef ) {
		return;
	}

	const wrapper = canvasRef.wrapperEl || canvasRef.lowerCanvasEl?.parentNode;
	if ( ! wrapper ) {
		return;
	}

	const wrapperRect = wrapper.getBoundingClientRect();
	const zoom = canvasRef.getZoom ? canvasRef.getZoom() : 1;
	const vpt = canvasRef.viewportTransform || [ 1, 0, 0, 1, 0, 0 ];
	const text = obj.text || '';
	const anchorIndex = Math.max( 0, Math.min( state.triggerPos, text.length ) );

	const defaultLineH = ( obj.fontSize || 16 ) * ( obj.lineHeight || 1 );
	let offsetLeft = 0;
	let offsetTop = defaultLineH;

	const loc = typeof obj.get2DCursorLocation === 'function'
		? obj.get2DCursorLocation( anchorIndex )
		: null;

	if ( loc ) {
		if ( obj.__charBounds && obj.__charBounds[ loc.lineIndex ] ) {
			const charBound = obj.__charBounds[ loc.lineIndex ][ loc.charIndex ];
			if ( charBound ) {
				offsetLeft = charBound.left;
			}
		}

		const heightFn = typeof obj.getHeightOfLine === 'function'
			? ( i ) => obj.getHeightOfLine( i )
			: typeof obj._getHeightOfLine === 'function'
				? ( i ) => obj._getHeightOfLine( i )
				: null;

		if ( heightFn ) {
			offsetTop = 0;
			for ( let i = 0; i <= loc.lineIndex; i++ ) {
				offsetTop += heightFn( i );
			}
		}
	}

	offsetLeft *= obj.scaleX || 1;
	offsetTop *= obj.scaleY || 1;

	const rect = obj.getBoundingRect();
	const left = wrapperRect.left + window.scrollX + ( rect.left + offsetLeft ) * zoom + vpt[ 4 ];
	const top = wrapperRect.top + window.scrollY + ( rect.top + offsetTop ) * zoom + vpt[ 5 ] + 4;

	dropdownEl.style.left = left + 'px';
	dropdownEl.style.top = top + 'px';
}

function filterPlaceholders( filter ) {
	const all = getPlaceholders();
	if ( ! filter ) {
		return all.slice();
	}
	const lc = filter.toLowerCase();
	return all.filter( ( p ) => p.key.toLowerCase().indexOf( lc ) === 0 );
}

function scrollActiveItemIntoView() {
	if ( ! dropdownEl ) {
		return;
	}

	const activeItem = dropdownEl.querySelector( '.' + DROPDOWN_CLASS + '__item.is-active' );
	if ( ! activeItem ) {
		return;
	}

	activeItem.scrollIntoView( {
		block: 'nearest',
		inline: 'nearest',
	} );
}

function renderItems() {
	if ( ! dropdownEl ) {
		return;
	}
	dropdownEl.innerHTML = '';
	state.items.forEach( ( item, idx ) => {
		const li = document.createElement( 'li' );
		li.className = DROPDOWN_CLASS + '__item';
		if ( idx === state.activeIndex ) {
			li.classList.add( 'is-active' );
		}
		li.dataset.key = item.key;

		const label = document.createElement( 'span' );
		label.className = DROPDOWN_CLASS + '__label';
		label.textContent = item.label;

		const key = document.createElement( 'span' );
		key.className = DROPDOWN_CLASS + '__key';
		key.textContent = '[' + item.key + ']';

		li.appendChild( label );
		li.appendChild( key );
		li.addEventListener( 'mousedown', ( e ) => {
			e.preventDefault();
			state.activeIndex = idx;
			commitSelection();
		} );
		dropdownEl.appendChild( li );
	} );

	scrollActiveItemIntoView();
}

function showDropdown() {
	if ( ! dropdownEl ) {
		return;
	}
	dropdownEl.style.display = 'block';
	state.active = true;
	positionDropdown( state.obj );
	renderItems();
}

function refreshFromText() {
	if ( ! state.obj || state.triggerPos < 0 ) {
		return;
	}
	const text = state.obj.text || '';
	const cursor = state.textarea ? state.textarea.selectionStart : text.length;

	if ( cursor <= state.triggerPos || text.charAt( state.triggerPos ) !== '[' ) {
		closeDropdown();
		return;
	}

	const filter = text.substring( state.triggerPos + 1, cursor );

	if ( /[^A-Za-z0-9_]/.test( filter ) ) {
		closeDropdown();
		return;
	}

	state.filter = filter;
	state.items = filterPlaceholders( filter );

	if ( state.items.length === 0 ) {
		closeDropdown();
		return;
	}

	state.activeIndex = Math.min( state.activeIndex, state.items.length - 1 );
	renderItems();
	positionDropdown( state.obj );
}

function commitSelection() {
	if ( ! state.active || ! state.obj || state.items.length === 0 ) {
		return;
	}
	const item = state.items[ state.activeIndex ];
	if ( ! item ) {
		return;
	}

	const obj = state.obj;
	const text = obj.text || '';
	const cursor = state.triggerPos + 1 + state.filter.length;
	const insert = '[' + item.key + ']';
	const newCursor = state.triggerPos + insert.length;

	if ( typeof obj.removeChars === 'function' && typeof obj.insertChars === 'function' ) {
		if ( cursor > state.triggerPos ) {
			obj.removeChars( state.triggerPos, cursor );
		}
		obj.insertChars( insert, null, state.triggerPos );
	} else {
		const before = text.substring( 0, state.triggerPos );
		const after = text.substring( cursor );
		obj.set( 'text', before + insert + after );
	}

	obj.selectionStart = newCursor;
	obj.selectionEnd = newCursor;

	if ( typeof obj.initDimensions === 'function' ) {
		obj.initDimensions();
	}
	obj.setCoords();

	if ( state.textarea ) {
		state.textarea.value = obj.text || '';
		state.textarea.selectionStart = newCursor;
		state.textarea.selectionEnd = newCursor;
	}
	if ( typeof obj._updateTextarea === 'function' ) {
		obj._updateTextarea();
	}

	canvasRef.requestRenderAll();

	if ( canvasRef ) {
		canvasRef.fire( 'text:changed', { target: obj } );
	}

	closeDropdown();
}

function handleKeydown( e ) {
	if ( ! state.active ) {
		return;
	}
	switch ( e.key ) {
		case 'ArrowDown':
			e.preventDefault();
			state.activeIndex = ( state.activeIndex + 1 ) % state.items.length;
			renderItems();
			break;
		case 'ArrowUp':
			e.preventDefault();
			state.activeIndex = ( state.activeIndex - 1 + state.items.length ) % state.items.length;
			renderItems();
			break;
		case 'Enter':
		case 'Tab':
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			commitSelection();
			break;
		case 'Escape':
			e.preventDefault();
			closeDropdown();
			break;
		default:
			break;
	}
}

function handleInput() {
	if ( ! state.obj ) {
		return;
	}

	if ( ! state.textarea || state.textarea !== state.obj.hiddenTextarea ) {
		if ( state.obj.hiddenTextarea ) {
			state.textarea = state.obj.hiddenTextarea;
		} else {
			return;
		}
	}
	const text = state.obj.text || '';
	const cursor = state.textarea.selectionStart;

	if ( ! state.active ) {
		if ( cursor > 0 && text.charAt( cursor - 1 ) === '[' ) {
			state.triggerPos = cursor - 1;
			state.filter = '';
			state.items = filterPlaceholders( '' );
			state.activeIndex = 0;
			if ( state.items.length > 0 ) {
				showDropdown();
			}
		}
		return;
	}

	refreshFromText();
}

function attachTextareaListeners( obj ) {
	const textarea = obj.hiddenTextarea;
	if ( ! textarea ) {
		return;
	}
	if ( state.textarea && state.textarea !== textarea ) {
		state.textarea.removeEventListener( 'input', handleInput );
		state.textarea.removeEventListener( 'keydown', handleKeydown, true );
	}
	state.obj = obj;
	state.textarea = textarea;

	textarea.addEventListener( 'input', handleInput );
	textarea.addEventListener( 'keydown', handleKeydown, true );
}

function ensureAttached() {
	if ( ! state.obj || ! state.obj.hiddenTextarea ) {
		return;
	}
	if ( state.textarea !== state.obj.hiddenTextarea ) {
		attachTextareaListeners( state.obj );
	}
}

function detachTextareaListeners() {
	if ( ! state.textarea ) {
		return;
	}
	state.textarea.removeEventListener( 'input', handleInput );
	state.textarea.removeEventListener( 'keydown', handleKeydown, true );
}

function handleDocumentMouseDown( e ) {
	if ( ! state.active || ! dropdownEl ) {
		return;
	}
	if ( dropdownEl.contains( e.target ) ) {
		return;
	}
	closeDropdown();
}

export function initPlaceholderAutocomplete( canvas ) {
	if ( ! canvas ) {
		return;
	}
	canvasRef = canvas;
	ensureDropdown();

	canvas.on( 'text:editing:entered', ( e ) => {
		const obj = e.target;
		if ( ! obj ) {
			return;
		}
		attachTextareaListeners( obj );
	} );

	canvas.on( 'text:changed', ( e ) => {
		const obj = e.target;
		if ( ! obj ) {
			return;
		}
		state.obj = obj;
		ensureAttached();
		handleInput();
	} );

	canvas.on( 'text:editing:exited', () => {
		detachTextareaListeners();
		resetAll();
	} );

	canvas.on( 'selection:cleared', resetAll );

	document.addEventListener( 'mousedown', handleDocumentMouseDown );
}
