<?php
/**
 * Gradebook empty state.
 *
 * @var array $template_args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$message = $template_args['message'] ?? __( 'No data available', 'learnpress-gradebook' );
?>
<div class="notice notice-error inline lp-gradebook-empty-state">
	<p><?php echo esc_html( $message ); ?></p>
</div>
