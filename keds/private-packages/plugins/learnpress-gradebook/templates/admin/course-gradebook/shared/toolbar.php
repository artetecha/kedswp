<?php
/**
 * Gradebook toolbar.
 *
 * @var array $template_args Template arguments.
 */

use LearnPress\Gradebook\TemplateHooks\Admin\GradebookTemplateRenderer;

defined( 'ABSPATH' ) || exit;

$left_html  = $template_args['left_html'] ?? '';
$right_html = $template_args['right_html'] ?? '';
?>
<div class="student-detail-header">
	<div class="left">
		<?php echo wp_kses( $left_html, GradebookTemplateRenderer::allowed_html() ); ?>
	</div>
	<div class="right">
		<?php echo wp_kses( $right_html, GradebookTemplateRenderer::allowed_html() ); ?>
	</div>
</div>
