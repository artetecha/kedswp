<?php
/**
 * Gradebook course admin layout.
 *
 * @var array $template_args Template arguments.
 */

use LearnPress\Gradebook\TemplateHooks\Admin\GradebookTemplateRenderer;

defined( 'ABSPATH' ) || exit;

$header_html       = $template_args['header_html'] ?? '';
$ajax_content_html = $template_args['ajax_content_html'] ?? '';
$wrap_class        = $template_args['wrap_class'] ?? '';
?>
<div class="learnpress-gradebook wrap <?php echo esc_attr( $wrap_class ); ?>">
	<?php echo wp_kses( $header_html, GradebookTemplateRenderer::allowed_html() ); ?>
	<?php echo wp_kses( $ajax_content_html, GradebookTemplateRenderer::allowed_html() ); ?>
</div>
