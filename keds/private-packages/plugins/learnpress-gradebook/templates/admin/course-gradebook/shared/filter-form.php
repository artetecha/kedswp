<?php
/**
 * Gradebook filter form.
 *
 * @var array $template_args Template arguments.
 */

use LearnPress\Gradebook\TemplateHooks\Admin\GradebookTemplateRenderer;

defined( 'ABSPATH' ) || exit;

$fields_html  = $template_args['fields_html'] ?? '';
$button_class = $template_args['button_class'] ?? 'search-button';
$button_label = $template_args['button_label'] ?? __( 'Search', 'learnpress-gradebook' );
$form_class   = $template_args['form_class'] ?? '';
?>
<form class="lp-gradebook-filter <?php echo esc_attr( $form_class ); ?>">
	<?php echo wp_kses( $fields_html, GradebookTemplateRenderer::allowed_html() ); ?>
	<button class="<?php echo esc_attr( $button_class ); ?>" type="button">
		<span class="dashicons dashicons-search"></span>
		<?php echo esc_html( $button_label ); ?>
		<span></span>
	</button>
</form>
