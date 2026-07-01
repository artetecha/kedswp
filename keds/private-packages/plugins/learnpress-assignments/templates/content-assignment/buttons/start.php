<?php
/**
 * Template for displaying Start assignment button.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/assignments/content-assignment/buttons/start.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Assignments/Templates
 * @version  3.0.0
 */

defined( 'ABSPATH' ) || exit();

$course             = learn_press_get_course();
$current_assignment = LP_Global::course_item();
?>

<?php do_action( 'learn-press/before-assignment-start-button' ); ?>

<form name="start-assignment" class="start-assignment" method="post" enctype="multipart/form-data">

	<?php do_action( 'learn-press/begin-assignment-start-button' ); ?>

	<button type="submit" class="button lp-button"><?php esc_html_e( 'Start', 'learnpress-assignments' ); ?></button>

	<?php do_action( 'learn-press/end-assignment-start-button' ); ?>

	<?php learnpress_assignment_action( 'start', $current_assignment->get_id(), $course->get_id(), true ); ?>
	<input type="hidden" name="noajax" value="yes">

</form>

<?php do_action( 'learn-press/after-assignment-start-button' ); ?>
