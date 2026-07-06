<?php
/**
 * Certificate Template Selection Template default.
 */

use LearnPress\Certificate\Models\CertificatePostModel;

defined( 'ABSPATH' ) || exit;

if ( ! isset( $certificatePostModel )
	|| ! $certificatePostModel instanceof CertificatePostModel ) {
	return;
}

$layer = $certificatePostModel->get_layer();
?>
<div class="lp-cert-layout-selection-wrapper <?php echo ! empty( $layer ) ? 'lp-hidden' : ''; ?>">
	<div class="lp-cert-template-selection-header">
		<h1><?php esc_html_e( 'Choose template or Create your own', 'learnpress-certificates' ); ?></h1>
		<p class="lp-cert-template-selection-desc">
			<?php esc_html_e( 'Choose our pre-built template or start from scratch and customize it on our certificate builder.', 'learnpress-certificates' ); ?>
		</p>
	</div>

	<?php
	$plugin_url = \LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/templates/' );
	?>
	<div class="lp-cert-template-list">
		<div class="lp-cert-template-item lp-cert-template-create" data-template="blank">
			<div class="lp-cert-template-card">
				<div class="lp-cert-template-icon">
					<span class="lp-cert-icon-plus-cer"></span>
				</div>
				<div class="lp-cert-template-label-create">
					<?php esc_html_e( 'Create your own', 'learnpress-certificates' ); ?>
				</div>
			</div>
			<button type="button" class="lp-button lp-btn-cert-choose-template-first">
				<?php esc_html_e( 'Choose', 'learnpress-certificates' ); ?>
			</button>
		</div>

		<div class="lp-cert-template-item" data-template="vertical">
			<div class="lp-cert-template-card">
				<div class="lp-cert-template-preview">
					<img src="<?php echo esc_url( $plugin_url . 'vertical.png' ); ?>" alt="<?php esc_attr_e( 'Vertical Template', 'learnpress-certificates' ); ?>">
				</div>
				<div class="lp-cert-template-label">
					<div class="lp-cert-template-label-info">
						<div class="lp-cert-template-label-title">
							<?php esc_html_e( 'Vertical (A4)', 'learnpress-certificates' ); ?>
						</div>
						<span class="lp-cert-template-label-desc"><?php esc_html_e( '595x842 (px)', 'learnpress-certificates' ); ?></span>
					</div>
					<button type="button" class="lp-button lp-btn-cert-choose-template-first">
						<?php esc_html_e( 'Choose', 'learnpress-certificates' ); ?>
					</button>
				</div>
			</div>
		</div>

		<div class="lp-cert-template-item" data-template="a4-landscape">
			<div class="lp-cert-template-card">
				<div class="lp-cert-template-preview">
					<img src="<?php echo esc_url( $plugin_url . 'landscape.png' ); ?>" alt="<?php esc_attr_e( 'Landscape Template', 'learnpress-certificates' ); ?>">
				</div>
				<div class="lp-cert-template-label">
					<div class="lp-cert-template-label-info">
						<div class="lp-cert-template-label-title">
							<?php esc_html_e( 'Landscape (A4)', 'learnpress-certificates' ); ?>
						</div>
						<span class="lp-cert-template-label-desc"><?php esc_html_e( '842x595 (px)', 'learnpress-certificates' ); ?></span>
					</div>
					<button type="button" class="lp-button lp-btn-cert-choose-template-first">
						<?php esc_html_e( 'Choose', 'learnpress-certificates' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>

