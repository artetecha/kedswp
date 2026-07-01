<?php
/**
 * The template for displaying button view certificate in single package.
 *
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<form name="certificate-form-button" class="learnpress-single-package__view-certificate" action="<?php echo esc_url( $certificate->get_sharable_permalink() ); ?>" method="post">
	<button class="button">
		<?php esc_html_e( 'Certificate', 'learnpress-upsell' ); ?>
	</button>
</form>
