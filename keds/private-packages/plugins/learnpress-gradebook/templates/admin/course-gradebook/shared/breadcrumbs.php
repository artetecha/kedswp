<?php
/**
 * Gradebook breadcrumbs.
 *
 * @var array $template_args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$items = $template_args['items'] ?? array();
if ( empty( $items ) ) {
	return;
}
?>
<nav aria-label="<?php echo esc_attr__( 'breadcrumb', 'learnpress-gradebook' ); ?>">
	<ol class="breadcrumb">
		<?php foreach ( $items as $item ) : ?>
			<?php
			$is_active  = ! empty( $item['active'] );
			$url        = $item['url'] ?? '';
			$label      = $item['label'] ?? '';
			$label_html = $item['label_html'] ?? '';
			?>
			<li class="breadcrumb-item<?php echo esc_attr( $is_active ? ' active' : '' ); ?>"
				<?php if ( $is_active ) : ?>
					aria-current="page"
				<?php endif; ?>
			>
				<?php if ( $url && ! $is_active ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"><?php echo $label_html ? wp_kses_post( $label_html ) : esc_html( $label ); ?></a>
				<?php else : ?>
					<?php echo $label_html ? wp_kses_post( $label_html ) : esc_html( $label ); ?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
