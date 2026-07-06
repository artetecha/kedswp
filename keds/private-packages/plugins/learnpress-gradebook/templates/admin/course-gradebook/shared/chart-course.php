<?php
/**
 * Course chart wrapper.
 *
 * @var array $template_args Template arguments.
 */

defined( 'ABSPATH' ) || exit;

$course_id = absint( $template_args['course_id'] ?? 0 );
$filters   = $template_args['filters'] ?? array(
	'last-7-days'   => __( 'Last 7 days', 'learnpress-gradebook' ),
	'last-30-days'  => __( 'Last 30 days', 'learnpress-gradebook' ),
	'last-12-month' => __( 'Last 12 months', 'learnpress-gradebook' ),
);
?>
<div class="course-chart-wrapper chart_sc" data-course="<?php echo esc_attr( $course_id ); ?>" style="display: none;">
	<div class="bar-chart-wrapper detail-chart">
		<div class="bar-chart-filter">
			<?php foreach ( $filters as $filter => $label ) : ?>
				<button class="bar-chart-filter-button" type="button" data-filter="<?php echo esc_attr( $filter ); ?>">
					<?php echo esc_html( $label ); ?><span></span>
				</button>
			<?php endforeach; ?>
		</div>
		<div class="lp-gradebook-wrapper-chart-canvas">
			<canvas class="bar-chart-canvas"></canvas>
		</div>
	</div>
	<div class="pie-chart-wrapper detail-chart">
		<canvas class="pie-chart-canvas"></canvas>
	</div>
</div>
