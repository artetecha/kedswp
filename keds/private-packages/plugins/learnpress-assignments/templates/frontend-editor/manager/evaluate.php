<?php
/**
 * Admin View: Assignment evaluate page.
 *
 * @deprecated 4.1.7 use on shortcode [assignment_evaluate_form], not run correctly
 */

return;

defined( 'ABSPATH' ) || exit();
if ( ! class_exists( 'LP_Assignment_Evaluate' ) ) {
	require_once LP_ADDON_ASSIGNMENTS_INC . 'admin/class-lp-assignment-evaluate.php';
}
$assignment_id = LP_Request::get_int( 'assignment_id' );
$user_id       = LP_Request::get_int( 'user_id' );

if ( ! $user_id ) {
	_e( 'Invalid student', 'learnpress-assignments' );

	return;
}

$assignment = learn_press_get_assignment( $assignment_id );
if ( ! $assignment_id || ! $assignment ) {
	_e( 'Invalid course', 'learnpress-assignments' );

	return;
} ?>

<?php
global $frontend_editor;
$post_manage           = $frontend_editor->post_manage;
$assignment_db         = LP_Assigment_DB::getInstance();
$user                  = learn_press_get_user( $user_id );
$evaluate_page         = get_page_link( get_option( 'assignment_evaluate_page_id' ) );
$man_page              = get_page_link( get_option( 'assignment_students_man_page_id' ) );
$get_course_of_item_rs = learn_press_get_item_courses( $assignment_id );
if ( ! $get_course_of_item_rs ) {
	return;
}

$course = $get_course_of_item_rs[0] ?? 0;
if ( ! $course ) {
	return;
}

$lp_course = learn_press_get_course( $course->ID );
if ( ! $lp_course ) {
	return;
}

$course_edit_url = $post_manage->get_edit_post_link( get_post_type( $course->ID ), $course->ID );
$course_data     = $user->get_course_data( $lp_course->get_id() );
if ( ! $course_data ) {
	return;
}

$evaluated = $user->has_item_status( array( 'evaluated' ), $assignment_id, $lp_course->get_id() );
$user_item = $course_data->get_item( $assignment_id );
if ( ! $user_item ) {
	return;
}

$user_item_id   = $user_item->get_user_item_id();
$last_answer    = $assignment_db->get_extra_value( $user_item_id, $assignment_db::$answer_note_key );
$uploaded_files = learn_press_assignment_get_uploaded_files( $user_item_id );
?>

<div class="wrap" id="learn-press-evaluate-front">
	<div id="evaluate-form-lpa-header">
		<h3 id="lpa-page-title"><?php esc_html_e( 'Evaluate Form', 'learnpress-assignments' ); ?></h3>
		<h4 class="lpa-sub-page-title"><a href="<?php echo esc_url( $man_page . '?assignment_id=' . $assignment_id ); ?>"><?php _e( 'Back to list students', 'learnpress-assignments' ); ?></a></h4>
	</div>
	<div id="poststuff-front" class="<?php echo $evaluated ? esc_attr( 'assignment-evaluated' ) : ''; ?>">
		<form method="post" id="lpa-e-evaluate-form">
			<input type="hidden" name="user_item_id" value="<?php echo esc_attr( $user_item_id ); ?>">

				<div id="postbox-container-2" class="postbox-container">
					<div class="inside">

						<h3 class="assignment-title"> <a href="<?php esc_attr_e( $course_edit_url . '/' . $assignment_id ); ?>" target="_blank"><?php echo $assignment->get_title(); ?></a>
						</h3>
						<?php
						$intro = get_post_meta( $assignment_id, '_lp_introduction', true );
						if ( $intro ) {
							?>
							<div class="assignment-content"><?php echo $intro; ?></div>
						<?php } ?>

						<div class="rwmb-field rwmb-heading-wrapper submission-heading">
							<h4><?php _e( 'Submission', 'learnpress-assignments' ); ?></h4>
							<p class="description"><?php _e( 'Include student assignment answer and attach files.', 'learnpress-assignments' ); ?></p>
						</div>
						<div class="rwmb-field answer-content">
							<div class="rwmb-label">
								<label for="user-answer"><?php _e( 'Answer', 'learnpress-assignments' ); ?></label>
							</div>
							<div class="rwmb-input">
								<?php
								wp_editor(
									$last_answer,
									'assignment-editor-student-answer',
									array(
										'media_buttons' => false,
										'textarea_rows' => 10,
									)
								);
								?>
								<i><?php _e( 'Instructor can not modify submission of student, every change has no effect.', 'learnpress-assignments' ); ?></i>
							</div>
						</div>
						<div class="rwmb-field answer-uploads">
							<div class="rwmb-label">
								<label for="user-uploads"><?php _e( 'Attach File', 'learnpress-assignments' ); ?></label>
							</div>
							<div class="rwmb-input">
								<?php if ( $uploaded_files ) { ?>
									<ul class="assignment-files assignment-uploaded list-group list-group-flush">
										<?php foreach ( $uploaded_files as $file ) { ?>
											<li class="list-group-item"><a
													href="<?php echo get_site_url() . $file->url; ?>"
													target="_blank"><?php echo $file->filename; ?></a></li>
										<?php } ?>
									</ul>
								<?php } else { ?>
									<i><?php _e( 'There is no assignments attach file(s).', 'learnpress-assignments' ); ?></i>
								<?php } ?>
							</div>
						</div>

						<div class="rwmb-field rwmb-heading-wrapper">
							<h4><?php _e( 'Evaluation', 'learnpress-assignments' ); ?></h4>
							<p class="description"><?php _e( 'Your evaluation about student submission.', 'learnpress-assignments' ); ?></p>
						</div>

						<?php LP_Assignment_Evaluate::instance( $assignment, $user_item_id, $evaluated )->display(); ?>
					</div>
					<div class="inside">
						<div class="submitbox" id="submitpost">
							<div id="minor-publishing">
								<div id="major-publishing-actions">
									<?php if ( ! $evaluated ) { ?>
										<input name="action" type="submit" class="button button-large" value="<?php _e( 'save', 'learnpress-assignments' ); ?>">
									<?php } ?>
									<input name="action" type="submit" class="button button-large evaluate" value="<?php echo $evaluated ? esc_attr( 're-evaluate', 'learnpress-assignments' ) : esc_attr( 'evaluate', 'learnpress-assignments' ); ?>">
									<input type="hidden" name="lp-ajax" value="lpae-evaluate">
									<input type="hidden" name="evaluate-page" value="<?php echo esc_attr( $evaluate_page ); ?>">
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
