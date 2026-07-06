<?php
/**
 * Quiz chart wrapper.
 *
 * @var array $template_args Template arguments.
 */

use LearnPress\Gradebook\TemplateHooks\Admin\GradebookTemplateRenderer;

defined( 'ABSPATH' ) || exit;

$attempted_questions_html = $template_args['attempted_questions_html'] ?? '';
?>
<div class="quiz-chart-wrapper chart_sc chart_sc3" style="display: none;">
	<div class="quiz_detail">
		<?php echo wp_kses( $attempted_questions_html, GradebookTemplateRenderer::allowed_html() ); ?>
	</div>
	<div class="pie-chart-wrapper detail-chart">
		<canvas class="pie-chart-canvas"></canvas>
	</div>
</div>
