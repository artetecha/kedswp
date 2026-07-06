
export class ContextMenu {
	constructor( layerManager ) {
		this.layerManager = layerManager;
		this.contextMenu = null;
		this.menuItems = [];
	}

	get canvas() {
		return this.layerManager?.canvas || null;
	}

	init() {
		const contextMenu = document.createElement( 'div' );
		contextMenu.id = 'lp-cert-context-menu';
		contextMenu.style.cssText = `
			position: fixed;
			background: white;
			border: 1px solid #ccc;
			border-radius: 4px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.15);
			padding: 4px 0;
			display: none;
			z-index: 100001;
			min-width: 140px;
		`;

		const menuItemsConfig = [
			{ label: 'Copy', action: 'handleCopy', shortcut: 'Ctrl+C', requiresSelection: true },
			{ label: 'Paste', action: 'handlePaste', shortcut: 'Ctrl+V', requiresSelection: false },
			{ label: 'Duplicate', action: 'handleDuplicate', shortcut: 'Ctrl+D', requiresSelection: true },
			{ type: 'separator', requiresSelection: true },
			{ label: 'Delete', action: 'handleDeleteLayer', shortcut: 'Del', color: '#d63638', requiresSelection: true },
		];

		menuItemsConfig.forEach( ( item ) => {
			if ( item.type === 'separator' ) {
				const separator = document.createElement( 'div' );
				separator.style.cssText = `
					height: 1px;
					background: #e0e0e0;
					margin: 4px 0;
				`;
				contextMenu.appendChild( separator );
				this.menuItems.push( { element: separator, requiresSelection: true, isSeparator: true, requiresImage: item.requiresImage || false } );
				return;
			}

			if ( item.submenu ) {
				const submenuItem = this.createSubmenuItem( item );
				contextMenu.appendChild( submenuItem );
				this.menuItems.push( { element: submenuItem, requiresSelection: item.requiresSelection, isSeparator: false, hasSubmenu: true, requiresImage: item.requiresImage || false } );
				return;
			}

			const menuItem = this.createMenuItem( item );
			contextMenu.appendChild( menuItem );
			this.menuItems.push( { element: menuItem, requiresSelection: item.requiresSelection, isSeparator: false, requiresImage: item.requiresImage || false } );
		} );

		document.body.appendChild( contextMenu );

		document.addEventListener( 'click', () => {
			this.hide();
		} );

		window.addEventListener( 'scroll', () => {
			this.hide();
		} );

		this.contextMenu = contextMenu;
	}

	createMenuItem( { label, action, shortcut, color } ) {
		const menuItem = document.createElement( 'div' );
		menuItem.style.cssText = `
			padding: 8px 16px;
			cursor: pointer;
			color: ${ color || '#333' };
			font-size: 14px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 20px;
		`;

		const labelSpan = document.createElement( 'span' );
		labelSpan.textContent = label;

		const shortcutSpan = document.createElement( 'span' );
		shortcutSpan.textContent = shortcut || '';
		shortcutSpan.style.cssText = `
			font-size: 12px;
			color: #999;
		`;

		menuItem.appendChild( labelSpan );
		menuItem.appendChild( shortcutSpan );

		menuItem.addEventListener( 'mouseenter', () => {
			menuItem.style.background = '#f0f0f0';
		} );

		menuItem.addEventListener( 'mouseleave', () => {
			menuItem.style.background = 'transparent';
		} );

		menuItem.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			if ( this[ action ] ) {
				this[ action ]();
			} else if ( this.layerManager && this.layerManager[ action ] ) {
				this.layerManager[ action ]();
			}
			this.hide();
		} );

		return menuItem;
	}

	createSubmenuItem( { label, submenu } ) {
		const wrapper = document.createElement( 'div' );
		wrapper.style.cssText = `position: relative;`;

		const trigger = document.createElement( 'div' );
		trigger.style.cssText = `
			padding: 8px 16px;
			cursor: pointer;
			color: #333;
			font-size: 14px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 20px;
		`;

		const labelSpan = document.createElement( 'span' );
		labelSpan.textContent = label;

		const arrowSpan = document.createElement( 'span' );
		arrowSpan.textContent = '▸';
		arrowSpan.style.cssText = `font-size: 29px; color: #999;`;

		trigger.appendChild( labelSpan );
		trigger.appendChild( arrowSpan );

		const submenuEl = document.createElement( 'div' );
		submenuEl.style.cssText = `
			position: absolute;
			left: 100%;
			top: -4px;
			background: white;
			border: 1px solid #ccc;
			border-radius: 4px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.15);
			padding: 4px 0;
			display: none;
			min-width: 140px;
			z-index: 100002;
		`;

		submenu.forEach( ( sub ) => {
			const subItem = document.createElement( 'div' );
			subItem.style.cssText = `
				padding: 8px 16px;
				cursor: pointer;
				font-size: 14px;
				color: #333;
			`;
			subItem.textContent = sub.label;

			subItem.addEventListener( 'mouseenter', () => {
				subItem.style.background = '#f0f0f0';
			} );

			subItem.addEventListener( 'mouseleave', () => {
				subItem.style.background = 'transparent';
			} );

			subItem.addEventListener( 'click', ( e ) => {
				e.stopPropagation();
				if ( this[ sub.action ] ) {
					this[ sub.action ]();
				}
				this.hide();
			} );

			submenuEl.appendChild( subItem );
		} );

		wrapper.appendChild( trigger );
		wrapper.appendChild( submenuEl );

		wrapper.addEventListener( 'mouseenter', () => {
			trigger.style.background = '#f0f0f0';
			submenuEl.style.display = 'block';

			const rect = submenuEl.getBoundingClientRect();
			if ( rect.right > window.innerWidth ) {
				submenuEl.style.left = 'auto';
				submenuEl.style.right = '100%';
			}
			if ( rect.bottom > window.innerHeight ) {
				submenuEl.style.top = 'auto';
				submenuEl.style.bottom = '0';
			}
		} );

		wrapper.addEventListener( 'mouseleave', () => {
			trigger.style.background = 'transparent';
			submenuEl.style.display = 'none';
		} );

		return wrapper;
	}

	show( x, y, pasteOnly = false ) {
		if ( ! this.contextMenu ) {
			return;
		}

		const activeObj = this.canvas?.getActiveObject();
		const typeLayer = activeObj?.get( 'type_layer' ) || '';
		const isImageType = typeLayer === 'image';

		this.menuItems.forEach( ( { element, requiresSelection, isSeparator, hasSubmenu, requiresImage } ) => {
			if ( pasteOnly && requiresSelection ) {
				element.style.display = 'none';
			} else if ( requiresImage && ! isImageType ) {
				element.style.display = 'none';
			} else {
				element.style.display = ( isSeparator || hasSubmenu ) ? 'block' : 'flex';
			}
		} );

		this.contextMenu.style.display = 'block';
		this.contextMenu.style.left = x + 'px';
		this.contextMenu.style.top = y + 'px';

		const rect = this.contextMenu.getBoundingClientRect();
		if ( rect.right > window.innerWidth ) {
			this.contextMenu.style.left = ( x - rect.width ) + 'px';
		}
		if ( rect.bottom > window.innerHeight ) {
			this.contextMenu.style.top = ( y - rect.height ) + 'px';
		}
	}

	hide() {
		if ( this.contextMenu ) {
			this.contextMenu.style.display = 'none';
		}
	}
}
