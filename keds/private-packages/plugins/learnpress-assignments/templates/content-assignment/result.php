<?php
/**
 * Template for displaying Assignment after evaluating assignment.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/assignments/content-assignment/result.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Assignments/Templates
 * @version  3.0.2
 * @editor tungnx
 * @modify 4.0.2
 */

use LearnPress\Models\CourseModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LearnPressAssignment\Models\UserAssignmentModel;

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

if ( ! $courseModel instanceof CourseModel
	|| ! $assignmentModel instanceof AssignmentPostModel ) {
	return;
}

$user_id = get_current_user_id();

$userAssignmentModel = UserAssignmentModel::find( $user_id, $courseModel->get_id(), $assignmentModel->get_id(), true );
if ( ! $userAssignmentModel || $userAssignmentModel->get_status() !== $userAssignmentModel::STATUS_EVALUATED ) {
	return;
}
$result_grade    = learn_press_assignment_get_result( $assignmentModel->get_id(), $user_id, $courseModel->get_id() );
$reference_files = $userAssignmentModel->get_evaluate_upload_files();
$instructor_note = $userAssignmentModel->get_instructor_note();
$graduation      = $userAssignmentModel->get_graduation();
$user_mark       = $userAssignmentModel->get_user_mark();
?>

<div class="assignment-result <?php echo esc_attr( $result_grade['grade'] ); ?>">
	<div class="result-grade <?php echo esc_attr( empty( $graduation ) ? 'ungraded' : $graduation ); ?>"
		style="--progress: <?php echo $result_grade['result']; ?>%">
		<?php
		echo sprintf(
			__( '<span class="result-achieved student-grade">%1$s</span> out of %2$s points', 'learnpress-assignments' ),
			$user_mark,
			$assignmentModel->get_max_mark()
		)
		?>
	</div>
</div>
<?php if ( ! empty( $instructor_note ) || ! empty( $reference_files ) ) : ?>
	<div class="learn_press_assignment_reference">
		<?php if ( ! empty( $instructor_note ) ) : ?>
			<h4><?php esc_html_e( 'Instructor Message', 'learnpress-assignments' ); ?></h4>
			<p><?php echo $instructor_note; ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $reference_files ) ) : ?>
			<h4><?php esc_html_e( 'References', 'learnpress-assignments' ); ?></h4>

			<ul class="assignment-files assignment-references">
				<?php foreach ( $reference_files as $att_id ) : ?>
					<li><?php echo wp_get_attachment_link( $att_id, $size = 'none' ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
<?php endif; ?>
