( function() {
	const modal = document.getElementById( 'lp-cert-upgrade-modal' );
	if ( ! modal ) {
		return;
	}

	const config = typeof lpCertUpgrade !== 'undefined' ? lpCertUpgrade : {};
	const nonce = config.nonce || '';
	const ajaxUrl = config.ajaxUrl || '';
	const i18n = config.i18n || {};

	const checkbox = modal.querySelector( 'input[name="agree_terms"]' );
	const startBtn = modal.querySelector( '.lp-cert-upgrade-start' );
	const cancelBtn = modal.querySelector( '.lp-cert-upgrade-cancel' );
	const progress = modal.querySelector( '.lp-cert-upgrade-modal__progress' );
	const progressFill = modal.querySelector( '.lp-cert-upgrade-modal__progress-fill' );
	const progressText = modal.querySelector( '.lp-cert-upgrade-modal__progress-text' );
	const result = modal.querySelector( '.lp-cert-upgrade-modal__result' );
	const terms = modal.querySelector( '.lp-cert-upgrade-modal__terms' );
	const checkboxLabel = modal.querySelector( '.lp-cert-upgrade-modal__checkbox' );

	let isUpgrading = false;

	function closeModal() {
		if ( isUpgrading ) {
			return;
		}
		modal.style.display = 'none';
	}

	document.addEventListener( 'click', function( e ) {
		const btn = e.target.closest( '.lp-cert-upgrade-btn' );
		if ( btn ) {
			e.preventDefault();
			modal.style.display = '';
		}
	} );

	cancelBtn.addEventListener( 'click', closeModal );
	modal.querySelector( '.lp-cert-upgrade-modal__overlay' ).addEventListener( 'click', closeModal );

	checkbox.addEventListener( 'change', function() {
		startBtn.disabled = ! this.checked;
	} );

	startBtn.addEventListener( 'click', function() {
		if ( isUpgrading ) {
			return;
		}

		isUpgrading = true;
		startBtn.disabled = true;
		startBtn.textContent = i18n.upgrading || 'Upgrading...';
		cancelBtn.disabled = true;
		checkbox.disabled = true;
		terms.style.display = 'none';
		checkboxLabel.style.display = 'none';
		progress.style.display = '';
		result.style.display = 'none';

		doUpgrade( 0, 0 );
	} );

	function doUpgrade( processed, total ) {
		const formData = new FormData();
		formData.append( 'action', 'lp_cert_upgrade_db' );
		formData.append( 'nonce', nonce );
		formData.append( 'processed', processed || 0 );
		formData.append( 'total', total || 0 );

		fetch( ajaxUrl, {
			method: 'POST',
			body: formData,
		} )
			.then( function( res ) {
				return res.json();
			} )
			.then( function( response ) {
				const data = response.data || {};
				const status = response.status;
				const message = response.message || '';

				if ( status === 'success' ) {
					const pct = data.total > 0 ? Math.round( ( data.processed / data.total ) * 100 ) : 100;
					progressFill.style.width = pct + '%';
					progressText.textContent = pct + '% (' + data.processed + '/' + data.total + ')';

					if ( ! data.done ) {
						doUpgrade( data.processed, data.total );
					} else {
						result.className = 'lp-cert-upgrade-modal__result success';
						result.innerHTML = '<strong>' + ( i18n.success || 'Success!' ) + '</strong> ' + message;
						result.style.display = '';

						setTimeout( function() {
							location.reload();
						}, 2000 );
					}
				} else {
					result.className = 'lp-cert-upgrade-modal__result error';
					result.innerHTML = message;
					result.style.display = '';

					isUpgrading = false;
					cancelBtn.disabled = false;
				}
			} )
			.catch( function() {
				result.className = 'lp-cert-upgrade-modal__result error';
				result.innerHTML = '<strong>' + ( i18n.error || 'Error!' ) + '</strong> ' + ( i18n.unexpected || 'An unexpected error occurred.' );
				result.style.display = '';

				isUpgrading = false;
				cancelBtn.disabled = false;
			} );
	}
} )();
