<?php
/**
 * Gradebook table.
 *
 * @var array $template_args Template arguments.
 */

use LearnPress\Gradebook\TemplateHooks\Admin\GradebookTemplateRenderer;

defined( 'ABSPATH' ) || exit;

$headers     = $template_args['headers'] ?? array();
$rows_html   = $template_args['rows_html'] ?? '';
$table_class = $template_args['table_class'] ?? '';
?>
<div class="table-container">
	<table class="lp-gradebook-table wp-list-table widefat fixed striped table-view-list student <?php echo esc_attr( $table_class ); ?>">
		<thead>
			<tr>
				<?php foreach ( $headers as $header ) : ?>
					<?php
					$class      = $header['class'] ?? '';
					$label      = $header['label'] ?? '';
					$label_html = $header['label_html'] ?? '';
					?>
					<th class="<?php echo esc_attr( $class ); ?>"<?php
						if ( ! empty( $header['sort_field'] ) ) {
							echo sprintf( ' data-sort-field="%s" data-sort-default="%s"', esc_attr( $header['sort_field'] ), esc_attr( $header['sort_default'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>><?php echo $label_html ? wp_kses_post( $label_html ) : esc_html( $label ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php echo wp_kses( $rows_html, GradebookTemplateRenderer::allowed_html() ); ?>
		</tbody>
	</table>
</div>
