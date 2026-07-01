let thisEditBuilder;
let editingLayerId = null;
let currentClickOutsideHandler = null;

export function setupLayersPanel( editBuilder ) {
	if ( editBuilder.constructor._loadedLayersPanel ) {
		return;
	}
	editBuilder.constructor._loadedLayersPanel = true;

	thisEditBuilder = editBuilder;

	const layersList = document.getElementById( 'lp-layers-list' );
	if ( ! layersList ) {
		return;
	}

	initLayersPanel();
}

function initLayersPanel() {
	renderLayers();
	setupEventListeners();
	setupCanvasObserver();
}

function setupEventListeners() {
	const layersList = document.getElementById( 'lp-layers-list' );
	if ( ! layersList ) {
		return;
	}

	layersList.addEventListener( 'click', ( e ) => {
		handleLayerClick( e );
	} );

	layersList.addEventListener( 'dblclick', ( e ) => {
		handleLayerDoubleClick( e );
	} );

	const sidebarTools = document.querySelector( '.lp-cert-builder-sidebar-tools' );
	if ( sidebarTools ) {
		sidebarTools.addEventListener( 'click', ( e ) => {
			if ( ! e.target.closest( '.lp-layer-item' ) ) {
				deselectAll();
			}
		} );
	}
}

function setupSortable() {
	const layersList = document.getElementById( 'lp-layers-list' );
	if ( ! layersList || typeof jQuery === 'undefined' || ! jQuery.fn.sortable ) {
		setTimeout( setupSortable, 1000 );
		return;
	}

	const $ = jQuery;
	const $layersList = $( layersList );

	if ( $layersList.hasClass( 'ui-sortable' ) ) {
		$layersList.sortable( 'destroy' );
	}

	$layersList.sortable( {
		handle: '.lp-layer-drag-handle',
		axis: 'y',
		items: '> .lp-layer-item',
		opacity: 0.6,
		cursor: 'grab',
		containment: 'parent',
		scroll: false,
		update: function() {
			const order = $layersList.find( '.lp-layer-item' ).map( function() {
				return $( this ).data( 'layer-id' );
			} ).get();

			reorderLayersByOrder( order );
		}
	} );
}

function setupCanvasObserver() {
	if ( ! thisEditBuilder.canvasManager || ! thisEditBuilder.canvasManager.canvas ) {
		return;
	}

	const canvas = thisEditBuilder.canvasManager.canvas;

	canvas.on( 'object:added', () => {
		setTimeout( () => {
			renderLayers();
			highlightSelectedLayer();
		}, 100 );
	} );

	canvas.on( 'object:removed', () => {
		setTimeout( () => {
			renderLayers();
		}, 100 );
	} );

	canvas.on( 'object:modified', () => {
		highlightSelectedLayer();
	} );

	canvas.on( 'text:changed', ( e ) => {
		const obj = e.target;
		if ( obj && ( obj.type === 'i-text' || obj.type === 'text' ) ) {
			if ( ! editingLayerId ) {
				const layerId = obj.get( 'id' );
				if ( layerId ) {
					const canvasData = window.lpCertCanvasData || {};
					const layers = canvasData.layers || [];
					const layerIndex = layers.findIndex( l => l.id === layerId );
					
					if ( layerIndex !== -1 ) {
						const newText = obj.get( 'text' ) || '';
						layers[layerIndex].text = newText.replace( /\n/g, '{n}' );
						window.lpCertCanvasData = canvasData;
						
						const activeObject = canvas.getActiveObject();
						const activeLayerName = activeObject ? activeObject.get( 'id' ) : null;
						
						renderLayers();
						
						if ( activeLayerName ) {
							setTimeout( () => {
								highlightSelectedLayer();
							}, 10 );
						}
					}
				}
			}
		}
	} );

	canvas.on( 'editing:exited', ( e ) => {
		const obj = e.target;
		if ( obj && ( obj.type === 'i-text' || obj.type === 'text' ) ) {
			const layerId = obj.get( 'id' );
			if ( layerId && ! editingLayerId ) {
				const newText = ( obj.get( 'text' ) || '' ).trim();
				
				if ( newText === '' ) {
					if ( thisEditBuilder.layerManager ) {
						thisEditBuilder.layerManager.deleteLayer( layerId, obj );
						renderLayers();
					}
					return;
				}
				
				const canvasData = window.lpCertCanvasData || {};
				const layers = canvasData.layers || [];
				const layerIndex = layers.findIndex( l => l.id === layerId );
				
				if ( layerIndex !== -1 ) {
					layers[layerIndex].text = newText.replace( /\n/g, '{n}' );
					window.lpCertCanvasData = canvasData;
					
					const activeObject = canvas.getActiveObject();
					const activeLayerName = activeObject ? activeObject.get( 'id' ) : null;
					
					renderLayers();
					
					if ( activeLayerName ) {
						setTimeout( () => {
							highlightSelectedLayer();
						}, 10 );
					}
				}
			}
		}
	} );

}

function renderLayers() {
	const layersList = document.getElementById( 'lp-layers-list' );
	if ( ! layersList ) {
		return;
	}

	const canvasData = window.lpCertCanvasData || {};
	const layers = canvasData.layers || [];

	if ( ! Array.isArray( layers ) || layers.length === 0 ) {
		layersList.innerHTML = '<li class="lp-layers-empty">No layers yet</li>';
		return;
	}

	const sortedLayers = [ ...layers ].reverse(); 

	layersList.innerHTML = sortedLayers.map( ( layer, index ) => {
		return renderLayerItem( layer, index );
	} ).join( '' );

	setupSortable();
}

function renderLayerItem( layer, index ) {
	const layerId = layer.id || '';
	const layerType = layer.type_layer || 'unknown';
	const isTextEdit = layerType === 'text-edit';
	const isImage = [ 'image', 'qr_code' ].includes( layerType );
	const isEditing = editingLayerId === layerId;

	let displayText;
	if ( isTextEdit ) {
		displayText = ( layer.text || 'Text' ).replace( /\{n\}/g, '\n' ).replace( /\/n/g, '\n' );
	} else {
		displayText = layer.name || 'Layer';
	}
	const displayTextShort = displayText.length > 30 ? displayText.substring( 0, 30 ) + '...' : displayText;

	let contentHtml;
	if ( isImage && layer.src ) {
		contentHtml = `<div class="lp-layer-thumbnail"><img src="${ escapeHtml( layer.src ) }" alt="${ escapeHtml( displayTextShort ) }" /></div>`;
	} else if ( isEditing ) {
		contentHtml = `<div class="lp-layer-edit-container">
			<textarea class="lp-layer-edit-input title-input" data-layer-id="${ layerId }" data-edit-type="${ isTextEdit ? 'text' : 'name' }">${ escapeHtml( displayText ) }</textarea>
			<button class="lp-layer-cancel" type="button" data-layer-id="${ layerId }">Cancel</button>
		</div>`;
	} else {
		contentHtml = `<div class="lp-layer-text title-input">${ escapeHtml( displayTextShort ) }</div>`;
	}

	return `
		<li class="lp-layer-item${ isEditing ? ' editing' : '' }" data-layer-id="${ layerId }" data-layer-type="${ layerType }">
			<div class="lp-layer-head">
				<span class="lp-layer-drag-handle movable lp-sortable-handle"><i class="lp-cert-icon lp-cert-icon-drag-handle"></i></span>
				<div class="lp-layer-title-wrapper">
					${ contentHtml }
				</div>
				${ ! isEditing ? `
					<button class="lp-layer-delete" type="button" data-layer-id="${ layerId }" title="Delete">
						<i class="lp-cert-icon lp-cert-icon-remove"></i>
					</button>
				` : '' }
			</div>
		</li>
	`;
}

function escapeHtml( text ) {
	const div = document.createElement( 'div' );
	div.textContent = text;
	return div.innerHTML;
}

function handleLayerClick( e ) {
	const layerItem = e.target.closest( '.lp-layer-item' );
	if ( ! layerItem ) {
		return;
	}

	if ( e.target.closest( '.lp-layer-delete' ) ) {
		e.stopPropagation();
		const layerId = layerItem.dataset.layerId;
		deleteLayer( layerId );
		return;
	}

	if ( e.target.closest( '.lp-layer-cancel' ) ) {
		e.stopPropagation();
		cancelEditing();
		return;
	}

	if ( editingLayerId ) {
		return;
	}

	const layerId = layerItem.dataset.layerId;
	selectLayerOnCanvas( layerId );
}

function handleLayerDoubleClick( e ) {
	const layerItem = e.target.closest( '.lp-layer-item' );
	if ( ! layerItem ) {
		return;
	}

	const layerId = layerItem.dataset.layerId;
	startEditing( layerId );
}

function removeClickOutsideHandler() {
	if ( currentClickOutsideHandler ) {
		document.removeEventListener( 'mousedown', currentClickOutsideHandler );
		currentClickOutsideHandler = null;
	}
}

function startEditing( layerId ) {
	removeClickOutsideHandler();

	editingLayerId = layerId;
	renderLayers();

	const layersList = document.getElementById( 'lp-layers-list' );
	const textarea = layersList?.querySelector( `.lp-layer-edit-input[data-layer-id="${ layerId }"]` );
	if ( textarea ) {
		textarea.focus();
		textarea.setSelectionRange( textarea.value.length, textarea.value.length );
		autoExpandTextarea( textarea );

		textarea.addEventListener( 'input', () => {
			autoExpandTextarea( textarea );
		} );

		textarea.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Enter' && ! e.shiftKey ) {
				e.preventDefault();
				removeClickOutsideHandler();
				saveLayerText( layerId );
			}
			if ( e.key === 'Escape' ) {
				removeClickOutsideHandler();
				cancelEditing();
			}
		} );

		currentClickOutsideHandler = ( e ) => {
			const editContainer = textarea.closest( '.lp-layer-edit-container' );
			if ( editContainer && ! editContainer.contains( e.target ) ) {
				removeClickOutsideHandler();
				saveLayerText( layerId );
			}
		};

		setTimeout( () => {
			if ( currentClickOutsideHandler ) {
				document.addEventListener( 'mousedown', currentClickOutsideHandler );
			}
		}, 10 );
	}
}

function autoExpandTextarea( textarea ) {
	textarea.style.height = 'auto';
	textarea.style.height = textarea.scrollHeight + 'px';
}

function saveLayerText( layerId ) {
	const layersList = document.getElementById( 'lp-layers-list' );
	const textarea = layersList?.querySelector( `.lp-layer-edit-input[data-layer-id="${ layerId }"]` );
	if ( ! textarea ) {
		return;
	}

	const editType = textarea.dataset.editType || 'name';
	const newValue = ( textarea.value || '' ).trim();

	if ( editType === 'text' && newValue === '' ) {
		if ( ! thisEditBuilder.canvasManager || ! thisEditBuilder.canvasManager.canvas ) {
			return;
		}

		const canvas = thisEditBuilder.canvasManager.canvas;
		const objects = canvas.getObjects();
		const fabricObj = objects.find( obj => obj.get( 'id' ) === layerId );

		if ( fabricObj && thisEditBuilder.layerManager ) {
			cancelEditing();
			thisEditBuilder.layerManager.deleteLayer( layerId, fabricObj );
			renderLayers();
		}
		return;
	}

	const canvasData = window.lpCertCanvasData || {};
	const layers = canvasData.layers || [];
	const layerIndex = layers.findIndex( l => l.id === layerId );

	if ( layerIndex === -1 ) {
		return;
	}

	if ( ! thisEditBuilder.canvasManager || ! thisEditBuilder.canvasManager.canvas ) {
		return;
	}

	const canvas = thisEditBuilder.canvasManager.canvas;
	const objects = canvas.getObjects();
	const fabricObj = objects.find( obj => obj.get( 'id' ) === layerId );

	if ( editType === 'text' ) {
		layers[ layerIndex ].text = newValue.replace( /\n/g, '{n}' );
		if ( fabricObj ) {
			fabricObj.set( 'text', newValue );
		}
	} else {
		const finalName = newValue || 'Layer';
		layers[ layerIndex ].name = finalName;
		if ( fabricObj ) {
			fabricObj.set( 'name', finalName );
		}
	}

	if ( fabricObj ) {
		fabricObj.setCoords();
		canvas.renderAll();

		if ( thisEditBuilder.layerManager ) {
			thisEditBuilder.layerManager.updateLayerDataFromObject( layerId, fabricObj );
			thisEditBuilder.layerManager.saveCanvasLayers( false );
		}
	}

	window.lpCertCanvasData = canvasData;

	cancelEditing();
	renderLayers();
}

function cancelEditing() {
	removeClickOutsideHandler();
	editingLayerId = null;
	renderLayers();
}

function selectLayerOnCanvas( layerId ) {
	if ( ! thisEditBuilder.canvasManager || ! thisEditBuilder.canvasManager.canvas ) {
		return;
	}

	const canvas = thisEditBuilder.canvasManager.canvas;
	const objects = canvas.getObjects();
	const fabricObj = objects.find( obj => {
		const objLayerId = obj.get( 'id' );
		return objLayerId === layerId;
	} );

	if ( fabricObj ) {
		canvas.setActiveObject( fabricObj );
		canvas.renderAll();
	}
}

function deleteLayer( layerId ) {
	if ( ! thisEditBuilder.canvasManager || ! thisEditBuilder.canvasManager.canvas ) {
		return;
	}

	const canvas = thisEditBuilder.canvasManager.canvas;
	const objects = canvas.getObjects();
	const fabricObj = objects.find( obj => {
		const objLayerId = obj.get( 'id' );
		return objLayerId === layerId;
	} );

	if ( fabricObj ) {
		canvas.setActiveObject( fabricObj );
		canvas.renderAll();
		
		if ( thisEditBuilder.layerManager ) {
			thisEditBuilder.layerManager.handleDeleteLayer();
			renderLayers();
		}
	}
}

function reorderLayersByOrder( order ) {
	const canvasData = window.lpCertCanvasData || {};
	const layers = canvasData.layers || [];

	if ( layers.length < 2 || order.length !== layers.length ) {
		return;
	}

	const reorderedLayers = [];
	order.forEach( layerId => {
		const layer = layers.find( l => l.id === layerId );
		if ( layer ) {
			reorderedLayers.push( layer );
		}
	} );

	reorderedLayers.reverse();
	canvasData.layers = reorderedLayers;
	window.lpCertCanvasData = canvasData;

	reorderCanvasObjects( reorderedLayers );

	if ( thisEditBuilder.layerManager ) {
		thisEditBuilder.layerManager.saveCanvasLayers( false );
	}

	renderLayers();
	highlightSelectedLayer();
}

function reorderCanvasObjects( orderedLayers ) {
	if ( ! thisEditBuilder.canvasManager || ! thisEditBuilder.canvasManager.canvas ) {
		return;
	}

	const canvas = thisEditBuilder.canvasManager.canvas;
	const objects = canvas.getObjects();

	orderedLayers.forEach( ( layer ) => {
		const fabricObj = objects.find( obj => obj.get( 'id' ) === layer.id );
		if ( fabricObj ) {
			canvas.bringObjectToFront( fabricObj );
		}
	} );

	canvas.requestRenderAll();
}

function highlightSelectedLayer() {
	if ( ! thisEditBuilder || ! thisEditBuilder.canvasManager || ! thisEditBuilder.canvasManager.canvas ) {
		return;
	}

	const canvas = thisEditBuilder.canvasManager.canvas;
	const activeObject = canvas.getActiveObject();
	if ( ! activeObject ) {
		clearHighlight();
		return;
	}

	const layerId = activeObject.get( 'id' );
	if ( ! layerId ) {
		return;
	}

	clearHighlight();

	const layersList = document.getElementById( 'lp-layers-list' );
	const layerItem = layersList?.querySelector( `[data-layer-id="${ layerId }"]` );
	if ( layerItem ) {
		layerItem.classList.add( 'selected' );
	}
}

function scrollToSelectedLayer() {
	if ( ! thisEditBuilder?.canvasManager?.canvas ) {
		return;
	}

	const canvas = thisEditBuilder.canvasManager.canvas;
	const activeObject = canvas.getActiveObject();
	if ( ! activeObject ) {
		return;
	}

	const layerId = activeObject.get( 'id' );
	if ( ! layerId ) {
		return;
	}

	const layersList = document.getElementById( 'lp-layers-list' );
	const layerItem = layersList?.querySelector( `[data-layer-id="${ layerId }"]` );
	if ( ! layerItem || ! layersList ) {
		return;
	}

	const scrollContainer = layersList.closest( '.lp-cert-builder-inserter-area' );
	if ( ! scrollContainer ) {
		return;
	}

	const containerRect = scrollContainer.getBoundingClientRect();
	const itemRect = layerItem.getBoundingClientRect();
	const itemOffsetTop = itemRect.top - containerRect.top + scrollContainer.scrollTop;
	const targetScroll = itemOffsetTop - ( containerRect.height / 2 ) + ( itemRect.height / 2 );

	scrollContainer.scrollTo( {
		top: Math.max( 0, targetScroll ),
		behavior: 'smooth'
	} );
}

function clearHighlight() {
	const layersList = document.getElementById( 'lp-layers-list' );
	if ( layersList ) {
		const selectedItems = layersList.querySelectorAll( '.selected' );
		selectedItems.forEach( item => item.classList.remove( 'selected' ) );
	}
}

function deselectAll() {
	if ( ! thisEditBuilder.canvasManager || ! thisEditBuilder.canvasManager.canvas ) {
		return;
	}

	const canvas = thisEditBuilder.canvasManager.canvas;
	canvas.discardActiveObject();
	canvas.requestRenderAll();
	clearHighlight();
}

export function refreshLayersPanel() {
	renderLayers();
}

export { highlightSelectedLayer, clearHighlight, scrollToSelectedLayer };

