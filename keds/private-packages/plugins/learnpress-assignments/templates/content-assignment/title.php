<?php
/**
 * Template for displaying title of assignment.
 *
 * @author   ThimPress
 * @package  Learnpress/Assignments/Templates
 * @version  3.0.2
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

echo sprintf( '<h1 class="assignment-title">%s</h1>', esc_html( $assignmentModel->get_the_title() ) );

$userAssignmentModel = UserAssignmentModel::find( $user_id, $courseModel->get_id(), $assignmentModel->get_id(), true );
if ( $userAssignmentModel ) {
	if ( in_array(
		$userAssignmentModel->get_status(),
		[
			$userAssignmentModel::STATUS_COMPLETED,
			$userAssignmentModel::STATUS_EVALUATED,
		]
	) ) {
		$result_grade    = learn_press_assignment_get_result( $assignmentModel->get_id(), $user_id, $courseModel->get_id() );
		$graduation      = $userAssignmentModel->get_graduation();
		$end_time        = new LP_Datetime( $userAssignmentModel->get_end_time() );
		$end_time_format = sprintf(
			'%1$s (%2$s)',
			$end_time->format( LP_Datetime::I18N_FORMAT_HAS_TIME ),
			$end_time::get_timezone_string()
		);
		?>
		<ul class="assignment-status pending-evaluation">
			<li class="time">
				<span class="left"><?php esc_html_e( 'Submitted on ', 'learnpress-assignments' ); ?></span>
				<span class="right"><?php echo esc_html( $end_time_format ); ?></span>
			</li>

			<li class="status <?php echo esc_attr( $graduation ); ?> ">
				<span>
					<?php
					if ( empty( $graduation ) ) {
						_e( 'Waiting to be marked', 'learnpress-assignments' );
					} else {
						echo LP_Addon_Assignment::get_i18n_value( $graduation );
					}
					?>
				</span>
			</li>
		</ul>
		<?php
	}
}
