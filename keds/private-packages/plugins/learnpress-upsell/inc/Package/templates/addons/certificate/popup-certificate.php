<?php
/**
 * The template for displaying popup certificate.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $template_id ) && ! isset( $certificate ) ) {
	return;
}
?>

<div id="certificate-popup">
	<div class="certificate">
		<div id="<?php echo esc_attr( $template_id ); ?>" class="certificate-preview">
			<div class="certificate-preview-inner">
				<canvas></canvas>
			</div>

			<input class="lp-data-config-cer" type="hidden" value="<?php echo htmlspecialchars( $certificate ); ?>">
		</div>

		<?php
		learn_press_certificates_buttons( $certificate );
		?>
	</div>
	<a href="" class="close-popup"></a>
</div>
