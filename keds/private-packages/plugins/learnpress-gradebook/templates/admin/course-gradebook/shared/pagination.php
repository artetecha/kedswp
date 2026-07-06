<?php
/**
 * Gradebook pagination.
 *
 * @var array $template_args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$pagination_items = $template_args['pagination_items'] ?? array();
if ( empty( $pagination_items ) ) {
	return;
}
?>
<div class="pagination-wrapper">
	<ul class="pagination">
		<?php if ( is_array( $pagination_items ) ) : ?>
			<?php foreach ( $pagination_items as $item_html ) : ?>
				<li><?php echo wp_kses_post( $item_html ); ?></li>
			<?php endforeach; ?>
		<?php else : ?>
			<?php echo wp_kses_post( $pagination_items ); ?>
		<?php endif; ?>
	</ul>
</div>
