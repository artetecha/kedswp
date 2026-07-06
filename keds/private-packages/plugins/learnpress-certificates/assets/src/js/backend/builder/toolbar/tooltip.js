let tooltip = null;

export function initTooltip() {
	tooltip = document.createElement( 'div' );
	tooltip.className = 'lp-cert-tooltip';
	tooltip.style.cssText = `
		position: fixed;
		z-index: 999999;
		background: #1d2327;
		color: #fff;
		font-size: 11px;
		font-weight: 500;
		padding: 6px 10px;
		border-radius: 4px;
		pointer-events: none;
		white-space: nowrap;
		opacity: 0;
		transition: opacity 0.15s ease;
	`;
	document.body.appendChild( tooltip );

	document.addEventListener( 'mouseenter', ( e ) => {
		if ( ! e.target || ! e.target.closest ) {
			return;
		}
		const trigger = e.target.closest( '[data-tooltip]' );
		if ( ! trigger ) {
			return;
		}

		const text = trigger.getAttribute( 'data-tooltip' );
		if ( ! text ) {
			return;
		}

		tooltip.textContent = text;
		tooltip.style.opacity = '1';

		const rect = trigger.getBoundingClientRect();
		const tooltipRect = tooltip.getBoundingClientRect();

		let top = rect.bottom + 6;
		let left = rect.left + rect.width / 2 - tooltipRect.width / 2;

		if ( top + tooltipRect.height > window.innerHeight ) {
			top = rect.top - tooltipRect.height - 6;
		}

		left = Math.max( 4, Math.min( left, window.innerWidth - tooltipRect.width - 4 ) );

		tooltip.style.top = top + 'px';
		tooltip.style.left = left + 'px';
	}, true );

	document.addEventListener( 'mouseleave', ( e ) => {
		if ( ! e.target || ! e.target.closest ) {
			return;
		}
		const trigger = e.target.closest( '[data-tooltip]' );
		if ( ! trigger ) {
			return;
		}
		tooltip.style.opacity = '0';
	}, true );
}
