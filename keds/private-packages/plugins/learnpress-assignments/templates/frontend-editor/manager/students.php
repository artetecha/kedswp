<?php
/**
 * Frontend View: Assignment student page.
 *
 * @version 1.0.1
 * @deprecated 4.1.7 use on shortcode [assignment_students_manager], not run correctly
 */

return;

use LearnPressAssignment\Models\AssignmentPostModel;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Student_Assignment_List_Table' ) ) {
	require_once LP_ADDON_ASSIGNMENTS_INC . 'admin/class-student-list-table.php';
}

global $frontend_editor;

$post_manage     = $frontend_editor->post_manage;
$assignment_id   = LP_Request::get_param( 'assignment_id', 0, 'int' );
$assignmentModel = AssignmentPostModel::find( $assignment_id, true );
if ( ! $assignmentModel ) {
	_e( 'Invalid assignment', 'learnpress-assignments' );

	return;
}

$list_table      = new LP_Assignment_fe_Student_List_Table( $assignment_id );
$course          = learn_press_get_item_courses( $assignment_id );
$lp_course       = learn_press_get_course( $course[0]->ID );
$course_edit_url = $post_manage->get_edit_post_link( get_post_type( $course[0]->ID ), $course[0]->ID );
?>

<div class="wrap" id="learn-press-assignment-front">
	<div id="lp-assignments-header">
		<h3 id="lpa-page-title"><?php esc_html_e( 'Assignment Students', 'learnpress-assignments' ); ?></h3>
		<h4 class="lpa-sub-page-title">
			<?php _e( 'Assignment', 'learnpress-assignments' ); ?>
			<a href="<?php esc_url( $course_edit_url . '/' . $assignment_id ); ?>" target="_blank"><?php echo $assignmentModel->get_the_title(); ?></a>
			<?php _e( 'of course', 'learnpress-assignments' ); ?>
			<a href="<?php esc_url( $course_edit_url ); ?>" target="_blank"><?php echo $lp_course->get_title(); ?></a>
		</h4>
	</div>
	<form method="post" id="lpa-manager-page">
		<?php $list_table->display(); ?>
	</form>
</div>
