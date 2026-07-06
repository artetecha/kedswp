<?php
/**
 * Gradebook status select.
 *
 * @var array $template_args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$name     = $template_args['name'] ?? 'item-status';
$options  = $template_args['options'] ?? array();
$selected = $template_args['selected'] ?? '';
?>
<div class="filter-status">
	<select name="<?php echo esc_attr( $name ); ?>" class="filter-input">
		<?php foreach ( $options as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected, $value ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>
