<?php
/**
 * Template for displaying content of assignment.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/assignments/single-assignment/content.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Assignments/Templates
 * @version  3.0.1
 */

use LearnPressAssignment\Models\AssignmentPostModel;

defined( 'ABSPATH' ) || exit();

if ( ! isset( $assignmentModel ) ) {
	$current_assignment = LP_Global::course_item();
	$assignmentModel    = AssignmentPostModel::find( $current_assignment->get_id(), true );
}
?>

<div class="learn_press_assignment_content">
	<h4 class="learn_press_assignment_content_title"><?php echo esc_html__( 'Task', 'learnpress-assignments' ); ?></h4>
	<?php echo wp_kses_post( $assignmentModel->get_the_content() ); ?>
</div>
