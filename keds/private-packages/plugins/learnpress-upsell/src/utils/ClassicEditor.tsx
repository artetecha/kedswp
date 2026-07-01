/**
 * External dependencies
 */
import { debounce } from 'lodash';

import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@/utils/i18n';
import { BACKSPACE, DELETE, F10, isKeyboardEvent } from '@wordpress/keycodes';
import { v4 as uuidv4 } from 'uuid';

const { wp } = window;

const clientId = uuidv4();

function isTmceEmpty( editor ) {
	// When tinyMce is empty the content seems to be:
	// <p><br data-mce-bogus="1"></p>
	// avoid expensive checks for large documents
	const body = editor.getBody();
	if ( body.childNodes.length > 1 ) {
		return false;
	} else if ( body.childNodes.length === 0 ) {
		return true;
	}
	if ( body.childNodes[ 0 ].childNodes.length > 1 ) {
		return false;
	}
	return /^\n?$/.test( body.innerText || body.textContent );
}

export default function ClassicEdit( { content, setContent } ) {
	const didMount = useRef( false );

	useEffect( () => {
		if ( ! didMount.current ) {
			return;
		}

		const editor = window.tinymce.get( `editor-${ clientId }` );
		const currentContent = editor?.getContent();

		if ( currentContent !== content ) {
			editor.setContent( content || '' );
		}
	}, [ content ] );

	useEffect( () => {
		const { baseURL, suffix } = window.wpEditorL10n.tinymce;

		didMount.current = true;

		window.tinymce.EditorManager.overrideDefaults( {
			base_url: baseURL,
			suffix,
		} );

		function onSetup( editor ) {
			let bookmark;

			if ( content ) {
				editor.on( 'loadContent', () => editor.setContent( content ) );
			}

			editor.on( 'blur', () => {
				bookmark = editor.selection.getBookmark( 2, true );
				setContent( editor.getContent() );

				editor.once( 'focus', () => {
					if ( bookmark ) {
						editor.selection.moveToBookmark( bookmark );
					}
				} );

				return false;
			} );

			editor.on( 'mousedown touchstart', () => {
				bookmark = null;
			} );

			const debouncedOnChange = debounce( () => {
				const value = editor.getContent();

				if ( value !== editor._lastChange ) {
					editor._lastChange = value;
					setContent( value );
				}
			}, 250 );
			editor.on( 'Paste Change input Undo Redo', debouncedOnChange );

			// We need to cancel the debounce call because when we remove
			// the editor (onUnmount) this callback is executed in
			// another tick. This results in setting the content to empty.
			editor.on( 'remove', debouncedOnChange.cancel );

			editor.on( 'keydown', ( event ) => {
				if ( isKeyboardEvent.primary( event, 'z' ) ) {
					// Prevent the gutenberg undo kicking in so TinyMCE undo stack works as expected
					event.stopPropagation();
				}

				if (
					( event.keyCode === BACKSPACE ||
						event.keyCode === DELETE ) &&
					isTmceEmpty( editor )
				) {
					event.preventDefault();
					event.stopImmediatePropagation();
				}

				const { altKey } = event;
				/*
				 * Prevent Mousetrap from kicking in: TinyMCE already uses its own
				 * `alt+f10` shortcut to focus its toolbar.
				 */
				if ( altKey && event.keyCode === F10 ) {
					event.stopPropagation();
				}
			} );

			editor.on( 'init', () => {
				const rootNode = editor.getBody();

				// Create the toolbar by refocussing the editor.
				// if ( rootNode.ownerDocument.activeElement === rootNode ) {
				rootNode.blur();
				// editor.focus();
				// }
			} );
		}

		function initialize() {
			const { settings } = window.wpEditorL10n.tinymce;
			window.wp.editor.initialize( `editor-${ clientId }`, {
				tinymce: {
					...settings,
					inline: true,
					content_css: false,
					fixed_toolbar_container: `#toolbar-${ clientId }`,
					setup: onSetup,
				},
			} );
		}

		function onReadyStateChange() {
			if ( document.readyState === 'complete' ) {
				initialize();
			}
		}

		if ( document.readyState === 'complete' ) {
			initialize();
		} else {
			document.addEventListener( 'readystatechange', onReadyStateChange );
		}

		return () => {
			document.removeEventListener(
				'readystatechange',
				onReadyStateChange
			);
			wp.oldEditor.remove( `editor-${ clientId }` );
		};
	}, [] );

	function focus() {
		const editor = window.tinymce.get( `editor-${ clientId }` );
		if ( editor ) {
			editor.focus();
		}
	}

	function onToolbarKeyDown( event ) {
		// Prevent WritingFlow from kicking in and allow arrows navigation on the toolbar.
		event.stopPropagation();
		// Prevent Mousetrap from moving focus to the top toolbar when pressing `alt+f10` on this block toolbar.
		event.nativeEvent.stopImmediatePropagation();
	}

	return (
		<div className="mt-1">
			<div
				key="toolbar"
				id={ `toolbar-${ clientId }` }
				className="block-library-classic__toolbar"
				onClick={ focus }
				data-placeholder={ __( 'Classic' ) }
				onKeyDown={ onToolbarKeyDown }
			/>
			<div
				key="editor"
				id={ `editor-${ clientId }` }
				className="lp-upsell-classic-editor border border-solid border-gray-300 rounded p-2 outline-none min-h-[100px] max-h-80 overflow-y-auto focus:ring-indigo-500 text-gray-900 focus:border-indigo-500 focus:border-2 text-sm"
				style={ { outline: 'none' } }
			/>
		</div>
	);
}
