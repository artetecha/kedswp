<?php
/**
 * Template for displaying introduction of assignment.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/assignments/single-assignment/intro.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Assignments/Templates
 * @version  3.0.1
 */

use LearnPress\Models\CourseModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LearnPressAssignment\TemplateHooks\SingleAssignmentTemplate;

defined( 'ABSPATH' ) || exit();

// prioritize use $courseModel send via param, not use global variable
global $lpCourseModel; // Variable from LP v4.2.7.4
if ( ! isset( $courseModel ) ) {
	if ( $lpCourseModel instanceof CourseModel ) {
		$courseModel = $lpCourseModel;
	} else {
		$courseModel = CourseModel::find( get_the_ID(), true );
	}
}

if ( ! isset( $assignmentModel ) ) {
	$current_assignment = LP_Global::course_item();
	$assignmentModel    = AssignmentPostModel::find( $current_assignment->get_id(), true );
}

$max_retake_count = $assignmentModel->get_max_mark();
$passing_grade    = $assignmentModel->get_passing_grade();
$total_can_retake = $assignmentModel->get_retake_count();
$intro_content    = $assignmentModel->get_the_content();
?>

<ul class="assignment-intro">
	<li>
		<label><?php esc_html_e( 'Duration', 'learnpress-assignments' ); ?></label>
		<span><?php echo SingleAssignmentTemplate::instance()->html_duration( $assignmentModel ); ?></span>
	</li>
	<li>
		<label><?php esc_html_e( 'Total grade', 'learnpress-assignments' ); ?></label>
		<span><?php echo sprintf( _n( '%s point', '%s points', $max_retake_count, 'learnpress-assignments' ), $max_retake_count ); ?></span>
	</li>
	<li>
		<label><?php esc_html_e( 'Passing grade', 'learnpress-assignments' ); ?></label>
		<span><?php echo sprintf( _n( '%s point', '%s points', $passing_grade ), 'learnpress-assignments', $passing_grade ); ?></span>
	</li>
	<li>
		<label><?php esc_html_e( 'Re-attempts allowed', 'learnpress-assignments' ); ?></label>
		<span>
			<?php
			if ( $total_can_retake > 0 ) {
				echo sprintf( '%1$d %2$s', $total_can_retake, _n( 'time', 'times', $total_can_retake, 'learnpress-assignments' ) );
			} elseif ( $total_can_retake == 0 ) {
				echo esc_html__( 'No', 'learnpress-assignments' );
			} else {
				echo esc_html__( 'Unlimited', 'learnpress-assignments' );
			}
			?>
		</span>
	</li>
</ul>

<?php if ( $intro_content != '' ) { ?>
	<h4 class="assignment-description"><?php echo esc_html__( 'Introduction', 'learnpress-assignments' ); ?></h4>

	<p><?php echo wp_kses_post( $intro_content ); ?></p>
	<?php
}
