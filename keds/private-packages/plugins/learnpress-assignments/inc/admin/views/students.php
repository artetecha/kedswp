<?php
/**
 * Admin View: Assignment student page.
 */

/**
 * Prevent loading this file directly
 */

use LearnPressAssignment\Models\AssignmentPostModel;

defined( 'ABSPATH' ) || exit();
?>

<?php
$assignment_id = LP_Request::get_param( 'assignment_id', 0, 'int' );
$assignment    = AssignmentPostModel::find( $assignment_id, true );
if ( ! $assignment ) {
	_e( 'Invalid assignment', 'learnpress-assignments' );
}
?>

<?php
if ( ! learn_press_assignment_verify_url( $assignment_id ) ) {
	?>
	<div id="error-page">
		<p><?php _e( 'Sorry, you are not allowed to access this page.', 'learnpress-assignments' ); ?></p>
	</div>
	<?php
	return;
}
?>

<?php $list_table = new LP_Student_Assignment_List_Table( $assignment_id ); ?>

<div class="wrap" id="learn-press-assignment">
	<h2><?php esc_html_e( 'Assignment Students', 'learnpress-assignments' ); ?></h2>

	<?php
	echo sprintf(
		'<h3>%1$s: %2$s</h3>',
		esc_html__( 'Assignment', 'learnpress-assignments' ),
		sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_url( $assignment->get_edit_link() ),
			$assignment->get_the_title()
		)
	);

	echo sprintf(
		'<div><a href="%1$s">%2$s</a></div>',
		esc_url( admin_url( 'edit.php?post_type=lp_assignment' ) ),
		esc_html__( 'Back to list assignments', 'learnpress-assignments' )
	);
	?>

	<form method="post">
		<?php $list_table->display(); ?>
	</form>
</div>
