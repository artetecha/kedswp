<?php
/**
 * Admin View: Assignment evaluate page.
 *
 * @version 1.0.2
 */

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LearnPressAssignment\Models\UserAssignmentModel;

defined( 'ABSPATH' ) || exit();
?>

<?php
$assignment_id = LP_Request::get_param( 'assignment_id', 0, 'int' );
$user_id       = LP_Request::get_param( 'user_id', 0, 'int' );
$course_id     = LP_Request::get_param( 'course_id', 0, 'int' );

if ( ! learn_press_assignment_verify_url( $assignment_id ) ) {
	?>
	<div id="error-page">
		<p><?php _e( 'Sorry, you are not allowed to access this page.', 'learnpress-assignments' ); ?></p>
	</div>
	<?php
	return;
}

$courseModel = CourseModel::find( $course_id, true );
if ( ! $courseModel ) {
	esc_html_e( 'Invalid course', 'learnpress-assignments' );

	return;
}

$assignmentModel = AssignmentPostModel::find( $assignment_id, true );
if ( ! $assignmentModel ) {
	esc_html_e( 'Invalid course', 'learnpress-assignments' );

	return;
}

$userModel = UserModel::find( $user_id, true );
if ( ! $userModel ) {
	esc_html_e( 'Invalid user', 'learnpress-assignments' );

	return;
}

$userAssignmentModel = UserAssignmentModel::find( $user_id, $course_id, $assignment_id, true );
if ( ! $userAssignmentModel
	|| ! in_array(
		$userAssignmentModel->get_status(),
		[
			$userAssignmentModel::STATUS_COMPLETED,
			$userAssignmentModel::STATUS_EVALUATED,
		]
	) ) {
	esc_html_e( 'Invalid user assignment', 'learnpress-assignments' );

	return;
}

$user_item_id   = $userAssignmentModel->get_user_item_id();
$evaluated      = $userAssignmentModel->get_status() === $userAssignmentModel::STATUS_EVALUATED;
$last_answer    = $userAssignmentModel->get_meta_value_from_key( $userAssignmentModel::META_KEY_ANSWER_NOTE, '', true );
$uploaded_files = learn_press_assignment_get_uploaded_files( $user_item_id );
?>

<div class="wrap" id="learn-press-evaluate">
	<h2><?php esc_html_e( 'Evaluate Form', 'learnpress-assignments' ); ?></h2>
	<a href="<?php echo esc_url( learn_press_assignment_students_url( $assignment_id ) ); ?>">
		<?php esc_html_e( 'Back to list students', 'learnpress-assignments' ); ?>
	</a>

	<div id="poststuff" class="<?php echo $evaluated ? esc_attr( 'assignment-evaluated' ) : ''; ?>">
		<form method="post">
			<input type="hidden" name="user_item_id" value="<?php echo esc_attr( $user_item_id ); ?>">

			<div id="post-body" class="metabox-holder columns-2">
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables ui-sortable">
						<div id="submitdiv" class="postbox ">
							<h2 class="hndle ui-sortable-handle">
								<span><?php esc_html_e( 'Actions', 'learnpress-assignments' ); ?></span>
							</h2>

							<div class="inside">
								<div class="submitbox" id="submitpost">
									<div id="minor-publishing">
										<div id="major-publishing-actions">
											<?php if ( ! $evaluated ) : ?>
												<button type="button"
														class="button button-large lp-btn-action-instructor-assignment"
														value="save">
													<?php esc_html_e( 'Save', 'learnpress-assignments' ); ?>
												</button>
											<?php endif; ?>
											<button type="button"
													class="button button-primary button-large lp-btn-action-instructor-assignment"
													value="<?php echo $evaluated ? 're-evaluate' : 'evaluate'; ?>">
												<?php $evaluated ? esc_html_e( 'Re Evaluate', 'learnpress-assignments' ) : esc_html_e( 'Evaluate', 'learnpress-assignments' ); ?>
											</button>
											<input name="action" type="hidden"
													value="<?php echo $evaluated ? 're-evaluate' : 'evaluate'; ?>">
											<input name="assignment-action-nonce" type="hidden"
													value="<?php echo wp_create_nonce( 'lp-assignment-instructor-action' ); ?>"/>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="postbox-container-2" class="postbox-container">
					<div class="inside">
						<h3 class="assignment-title">
							<a href="<?php echo $assignmentModel->get_edit_link(); ?>" target="_blank"
								rel="noreferrer noopener">
								<?php echo esc_html( $assignmentModel->get_the_title() ); ?>
							</a>
						</h3>
						<div class="lp-meta-box">
							<div class="lp-meta-box__inner">
							<?php
							$section_student = [
								'start' => '<div class="form-field">',
								'label' => sprintf( '<label>%s</label>', __( 'Student', 'learnpress-assignments' ) ),
								'info'  => sprintf(
									'<div><a href="%1$s">%2$s(%3$s)</a></div>',
									learn_press_user_profile_link( $user_id ),
									$userModel->get_display_name(),
									$userModel->get_email()
								),
								'end'   => '</div>',
							];
							echo Template::combine_components( $section_student );

							$end_time               = new LP_Datetime( $userAssignmentModel->get_end_time() );
							$section_time_submitted = [
								'start' => '<div class="form-field">',
								'label' => sprintf( '<label>%s</label>', __( 'Submitted on', 'learnpress-assignments' ) ),
								'info'  => sprintf(
									'<div>%s (%s)</div>',
									$end_time->format( LP_Datetime::I18N_FORMAT_HAS_TIME ),
									LP_Datetime::get_timezone_string()
								),
								'end'   => '</div>',
							];
							echo Template::combine_components( $section_time_submitted );

							$section_time_spent = [
								'start' => '<div class="form-field">',
								'label' => sprintf( '<label>%s</label>', __( 'Time spent', 'learnpress-assignments' ) ),
								'info'  => sprintf(
									'<div>%s</div>',
									$userAssignmentModel->get_time_interval()
								),
								'end'   => '</div>',
							];
							echo Template::combine_components( $section_time_spent );

							$section_task = [
								'start' => '<div class="form-field">',
								'label' => sprintf( '<label>%s</label>', __( 'Task', 'learnpress-assignments' ) ),
								'info'  => sprintf(
									'<div class="lp-assignment-task-instructor-evaluate">%s</div>',
									wpautop( Template::sanitize_html_content( $assignmentModel->get_the_content() ) )
								),
								'end'   => '</div>',
							];
							echo Template::combine_components( $section_task );
							?>
							</div>
						</div>

						<div class="submission-heading">
							<h4 style="font-size: 16px; margin-bottom: 10px;"><?php esc_html_e( 'Submission', 'learnpress-assignments' ); ?></h4>
							<p class="description"><?php esc_html_e( 'Include student assignment answer and attach files.', 'learnpress-assignments' ); ?></p>
						</div>
						<?php do_action( 'learn-press/assignment/evaluate/before-student-answer', $user_item_id, $user_id, $assignment_id ); ?>
						<div class="answer-content" style="display: grid; grid-template-columns: 180px 1fr;">
							<h4><label
									for="user-answer"><?php esc_html_e( 'Answer', 'learnpress-assignments' ); ?></label>
							</h4>
							<div>
								<?php
								wp_editor(
									$last_answer,
									'assignment-editor-student-answer',
									array(
										'media_buttons' => false,
										'textarea_rows' => 15,
										'quicktags'     => false,
										'tinymce'       => array(
											'toolbar' => false,
										),
									)
								);
								?>
								<i><?php esc_html_e( 'Instructor can not modify submission of student, every change has no effect.', 'learnpress-assignments' ); ?></i>
							</div>
						</div>

						<div class="answer-uploads"
							style="display: grid; grid-template-columns: 180px 1fr; align-items: center;">
							<div>
								<label for="user-uploads">
									<?php esc_html_e( 'Attach File', 'learnpress-assignments' ); ?>
								</label>
							</div>
							<div>
								<?php if ( $uploaded_files ) : ?>
									<ul class="assignment-files assignment-uploaded list-group list-group-flush">
										<?php foreach ( $uploaded_files as $file ) : ?>
											<li class="list-group-item">
												<a href="<?php echo esc_url( get_site_url() . '/' . $file->file ); ?>"
													target="_blank" rel="noopener noreferrer">
													<?php echo $file->filename; ?>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php else : ?>
									<i><?php esc_html_e( 'There is no assignments attach file(s).', 'learnpress-assignments' ); ?></i>
								<?php endif; ?>
							</div>
						</div>
						<?php do_action( 'learn-press/assignment/evaluate/after-student-answer', $user_item_id, $user_id, $assignment_id ); ?>
						<div>
							<h4 style="font-size: 16px; margin-bottom: 10px;"><?php esc_html_e( 'Evaluation', 'learnpress-assignments' ); ?></h4>
							<p class="description"><?php esc_html_e( 'Your evaluation about student submission.', 'learnpress-assignments' ); ?></p>
						</div>

						<?php
						LP_Assignment_Evaluate::instance( $assignmentModel, $user_item_id, $evaluated )->display();
						?>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
