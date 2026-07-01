<?php
defined( 'ABSPATH' ) || exit;

use LearnPress\Certificate\Models\CertificatePostModel;
use LearnPress\Certificate\Models\CertificateBuilderData;
use LearnPress\Certificate\TemplateHooks\AdminCertificateTemplate;

if ( ! isset( $certificatePostModel )
	|| ! $certificatePostModel instanceof CertificatePostModel ) {
	return;
}

$layer          = $certificatePostModel->get_layer();
$admin_template = AdminCertificateTemplate::instance();

$builder_data = new CertificateBuilderData( $certificatePostModel );
$canvas_data  = [
	'certificate_id' => $certificatePostModel->get_id(),
];

$canvas_data = wp_parse_args( $builder_data->get_raw_layers(), $canvas_data );
?>
<div class="lp-cert-layout-builder-wrapper <?php echo empty( $layer ) ? 'lp-hidden' : ''; ?>">

	<div class="lp-cert-builder-main">
		<!-- Sidebar 1: Menu Navigation (Dark Grey) -->
		<div class="lp-cert-builder-sidebar-nav">
			<?php echo $admin_template->html_menu_builder(); ?>
		</div>

		<!-- Sidebar 2: Builder Tools (White) -->
		<div class="lp-cert-builder-sidebar-tools">
			<?php echo $admin_template->html_inserter(); ?>
		</div>

		<!-- Canvas Area -->
		<div class="lp-cert-builder-canvas-area">
			<div class="lp-cert-builder-toolbar-area">
				<div class="lp-cert-builder-toolbar-area__toolbar-row">
					<div class="lp-cert-builder-toolbar-area__input-container">
						<?php echo $admin_template->html_layer_option(); ?>
					</div>
				</div>
			</div>
			<div class="lp-cert-builder-canvas-wrapper">
				<canvas id="lp-cert-builder-canvas"></canvas>
			</div>
			<?php echo $admin_template->html_position_panel(); ?>
		</div>
	</div>
</div>

<script>
	window.lpCertCanvasData = <?php echo wp_json_encode( $canvas_data ); ?>;
</script>

