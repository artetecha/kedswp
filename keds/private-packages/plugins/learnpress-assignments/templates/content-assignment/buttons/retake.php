<?php
/**
 * Template for displaying Assignment after sent.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/assignment/content-assignment/buttons/ratake.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Assignments/Templates
 * @version  3.0.1
 */

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LearnPressAssignment\Models\UserAssignmentModel;

defined( 'ABSPATH' ) || exit();

$course              = CourseModel::find( get_the_ID(), true );
$user                = UserModel::find( get_current_user_id(), true );
$current_assignment  = LP_Global::course_item();
$assigmentModel      = AssignmentPostModel::find( $current_assignment->get_id(), true );
$userAssignmentModel = UserAssignmentModel::find( get_current_user_id(), $course->get_id(), $current_assignment->get_id(), true );
$retaken_count       = $userAssignmentModel->get_retaken_count();
$remaining_time      = $assigmentModel->get_retake_count() - $retaken_count;
if ( $remaining_time <= 0 ) {
	$can_retake_time = 0;

	return;
} else {
	$can_retake_time = $remaining_time;
}
?>

<?php do_action( 'learn-press/before-assignment-retake-button' ); ?>

<form name="retake-assignment" class="retake-assignment" method="post" enctype="multipart/form-data">

	<?php do_action( 'learn-press/begin-assignment-retake-button' ); ?>

	<button type="submit" data-counter="<?php echo $can_retake_time; ?>"
			class="lp-button button"><?php esc_html_e( 'Retake', 'learnpress-assignments' ); ?>
	</button>

	<?php do_action( 'learn-press/end-assignment-retake-button' ); ?>

	<?php learnpress_assignment_action( 'retake', $assigmentModel->get_id(), $course->get_id(), true ); ?>
	<input type="hidden" name="noajax" value="yes">

</form>

<?php do_action( 'learn-press/after-assignment-retake-button' ); ?>
