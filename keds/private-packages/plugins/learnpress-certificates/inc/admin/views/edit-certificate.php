<?php
/**
 * Edit Certificate View.
 *
 * @version 4.2.0
 */

defined( 'ABSPATH' ) || exit;

use LearnPress\Certificate\Models\CertificatePostModel;

if ( ! isset( $certificatePostModel )
	|| ! $certificatePostModel instanceof CertificatePostModel ) {
	return;
}

$layer       = $certificatePostModel->get_layer();
$post_status = $certificatePostModel->post_status;
$post_id     = $certificatePostModel->get_id();
$has_layer   = ! empty( $layer );

$is_new_certificate = ( $post_status === 'auto-draft' );
$default_title      = __( 'Certificate', 'learnpress-certificates' );
?>

<?php if ( $is_new_certificate ) : ?>
<script>
document.addEventListener( 'DOMContentLoaded', function() {
	const titleInput = document.getElementById( 'title' );
	const titleLabel = document.getElementById( 'title-prompt-text' );
	if ( titleInput && ! titleInput.value ) {
		titleInput.value = '<?php echo esc_js( $default_title ); ?>';
		if ( titleLabel ) {
			titleLabel.classList.add( 'screen-reader-text' );
		}
	}
});
</script>
<?php endif; ?>

<!-- Certificate Builder -->
<div class="lp-certificate-edit-builder"
	 data-status="<?php echo esc_attr( $post_status ); ?>"
	 data-cert-id="<?php echo esc_attr( $post_id ); ?>">
	<div class="lp-cert-builder-top-actions">
		<button type="button" class="button lp-btn-cert-undo" disabled title="<?php esc_attr_e( 'Undo (Ctrl+Z)', 'learnpress-certificates' ); ?>">
			<span class="lp-cert-icon-undo"></span>
		</button>
		<button type="button" class="button lp-btn-cert-redo" disabled title="<?php esc_attr_e( 'Redo (Ctrl+Y)', 'learnpress-certificates' ); ?>">
			<span class="lp-cert-icon-redo"></span>
		</button>
		<button type="button" class="button lp-btn-cert-preview" title="<?php esc_attr_e( 'Preview Certificate', 'learnpress-certificates' ); ?>">
			<?php esc_html_e( 'Preview', 'learnpress-certificates' ); ?>
		</button>
		<button type="button" class="button button-primary lp-btn-cert-builder-open-full-screen">
			<span class="dashicons dashicons-editor-expand"></span>
			<?php esc_html_e( 'Fullscreen editor', 'learnpress-certificates' ); ?>
		</button>
		<button type="button" class="button lp-hidden lp-btn-cert-builder-close-full-screen">
			<span class="lp-icon-remove"></span>
		</button>
	</div>

	<?php
	LP_Addon_Certificates::instance()->get_admin_template(
		'certificate-template-selection.php',
		compact( 'certificatePostModel' )
	);

	LP_Addon_Certificates::instance()->get_admin_template(
		'edit-certificate-builder.php',
		compact( 'certificatePostModel' )
	);

	LP_Addon_Certificates::instance()->get_admin_template(
		'certificate-preview-modal.php'
	);
	?>
</div>
