<?php
/**
 * Template for Upgrade Database Modal
 *
 * @since 4.2.0
 * @version 1.0.0
 */
?>

<div id="lp-cert-upgrade-modal" class="lp-cert-upgrade-modal" style="display: none;">
	<div class="lp-cert-upgrade-modal__overlay"></div>
	<div class="lp-cert-upgrade-modal__content">
		<div class="lp-cert-upgrade-modal__header">
			<h2><?php esc_html_e( 'Upgrade Database', 'learnpress-certificates' ); ?></h2>
		</div>
		<div class="lp-cert-upgrade-modal__body">
			<div class="lp-cert-upgrade-modal__terms">
				<h3><?php esc_html_e( 'ACCEPTANCE OF TERMS', 'learnpress-certificates' ); ?></h3>
				<p>
					1. <?php esc_html_e( 'We do not take responsibility for the loss data that is not from our LearnPress plugins. This means we cannot guarantee the safety of data unrelated to LearnPress during the database upgrade process.', 'learnpress-certificates' ); ?>
					<em><?php esc_html_e( "It's essential to back up your entire website before upgrading.", 'learnpress-certificates' ); ?></em>
				</p>
				<p>
					2. <?php esc_html_e( 'We are not responsible for the issues caused by interrupting the upgrade process. Interrupting the process before finishing can lead to problems on your website. Please ensure that the LearnPress database is upgraded successfully.', 'learnpress-certificates' ); ?>
				</p>
				<p>
					3. <?php esc_html_e( 'A stable internet connection is essential for a successful upgrade since any Internet disconnection can lead to unexpected problems. We do not take responsibility for any issues caused by any disconnection.', 'learnpress-certificates' ); ?>
				</p>

				<h3><?php esc_html_e( 'RECOMMENDATIONS', 'learnpress-certificates' ); ?></h3>
				<p>
					1. <?php esc_html_e( 'Back up your website before upgrading. This will make sure you can restore your website in case any unexpected errors happen.', 'learnpress-certificates' ); ?>
				</p>
				<p>
					2. <?php esc_html_e( 'Maintain a stable Internet connection throughout the process. This will minimize the risk of breaking down your website.', 'learnpress-certificates' ); ?>
				</p>
				<p>
					3. <?php esc_html_e( 'Do not interrupt the process once it starts. You should let the process run for a successful database update.', 'learnpress-certificates' ); ?>
				</p>
			</div>
			<div class="lp-cert-upgrade-modal__progress" style="display: none;">
				<div class="lp-cert-upgrade-modal__progress-bar">
					<div class="lp-cert-upgrade-modal__progress-fill"></div>
				</div>
				<p class="lp-cert-upgrade-modal__progress-text">
					<?php esc_html_e( 'Upgrading...', 'learnpress-certificates' ); ?>
				</p>
			</div>
			<div class="lp-cert-upgrade-modal__result" style="display: none;"></div>
			<label class="lp-cert-upgrade-modal__checkbox">
				<input type="checkbox" name="agree_terms"/>
				<?php esc_html_e( 'I agree the new Terms of Service.', 'learnpress-certificates' ); ?>
			</label>
		</div>
		<div class="lp-cert-upgrade-modal__footer">
			<button type="button" class="button lp-cert-upgrade-cancel">
				<?php esc_html_e( 'Cancel', 'learnpress-certificates' ); ?>
			</button>
			<button type="button" class="button button-primary lp-cert-upgrade-start" disabled>
				<?php esc_html_e( 'Upgrade', 'learnpress-certificates' ); ?>
			</button>
		</div>
	</div>
</div>
