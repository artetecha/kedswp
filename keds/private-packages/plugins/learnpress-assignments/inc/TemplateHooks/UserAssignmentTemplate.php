<?php
/**
 * Template hooks Single Assignment.
 *
 * @since 4.1.2
 * @version 1.0.1
 */

namespace LearnPressAssignment\TemplateHooks;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPressAssignment\Models\UserAssignmentModel;
use LP_Datetime;
use LP_Strings;
use LP_Template_Course;
use Throwable;
use WP_Error;

class UserAssignmentTemplate {
	use Singleton;

	public function init() {
	}

	public function html_count_down( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		try {
			if ( ! in_array(
				$userAssignmentModel->get_status(),
				[
					$userAssignmentModel::STATUS_STARTED,
					$userAssignmentModel::STATUS_DOING,
				]
			) ) {
				return $html;
			}

			$assignmentModel   = $userAssignmentModel->get_assignment_model();
			$time_remaining    = $userAssignmentModel->get_time_remaining();
			$duration          = $assignmentModel->get_duration();
			$start_time_stamp  = strtotime( $userAssignmentModel->get_start_time() );
			$expire_time_stamp = strtotime( '+' . $duration, $start_time_stamp );
			$expire_time       = new LP_Datetime( $expire_time_stamp );

			if ( (float) $duration <= 0 ) {
				$time_remaining_str = __( 'Unlimited Time', 'learnpress-assignments' );
			} else {
				if ( $time_remaining > 0 ) {
					if ( $time_remaining > 2 * DAY_IN_SECONDS ) {
						$time_remaining_str = sprintf(
							'%s %s (%s)',
							__( 'Time end at', 'learnpress-assignments' ),
							$expire_time->format( LP_Datetime::I18N_FORMAT_HAS_TIME ),
							LP_Datetime::get_timezone_string()
						);
					} else {
						$time_remaining_str = sprintf(
							'%s: <span class="progress-number">%s</span>',
							__( 'Time remaining' ),
							'--:--'
						);
					}
				} else {
					$time_remaining_str = __( 'Time Up!', 'learnpress-assignments' );
				}
			}

			$section = [
				'wrapper'     => '<div class="progress-item assignment-countdown">',
				'progress'    => $time_remaining_str,
				'wrapper_end' => '</div>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {

		}

		return $html;
	}

	/**
	 * Get html answer of User.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 *
	 * @return string
	 */
	public function html_user_answered( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		try {
			if ( ! in_array(
				$userAssignmentModel->get_status(),
				[
					$userAssignmentModel::STATUS_COMPLETED,
					$userAssignmentModel::STATUS_EVALUATED,
				]
			) ) {
				return $html;
			}

			$user_answered         = $userAssignmentModel->get_user_answered();
			$html_answered_content = __( 'No answer yet.', 'learnpress-assignments' );
			if ( ! empty( $user_answered ) ) {
				$html_answered_content = sprintf(
					'<div class="assignment-user-answered-text">%s</div>',
					wpautop( Template::sanitize_html_content( $user_answered ) )
				);
			}

			$section = [
				'wrapper'        => '<div class="assignment-user-answered-wrapper">',
				'label_answered' => sprintf(
					'<div class="assignment-user-answered-title">%s</div>',
					__( 'Your Answers', 'learnpress-assignments' )
				),
				'answered'       => $html_answered_content,
				'files_uploaded' => $this->html_user_files_uploaded( $userAssignmentModel ),
				'wrapper_end'    => '</div>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			$html = Template::print_message(
				sprintf(
					__( 'Error: %s', 'learnpress-assignments' ),
					$e->getMessage()
				),
				'error',
				false
			);
		}

		return $html;
	}

	/**
	 * Get html files of User submit.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 *
	 * @return string
	 */
	public function html_user_editor_answer( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		try {
			if ( ! in_array(
				$userAssignmentModel->get_status(),
				[
					$userAssignmentModel::STATUS_STARTED,
					$userAssignmentModel::STATUS_DOING,
				]
			) ) {
				return $html;
			}

			ob_start();
			wp_editor(
				$userAssignmentModel->get_user_answered(),
				'assignment-editor-frontend',
				array( 'media_buttons' => false )
			);
			$html = ob_get_clean();
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html files of User submit.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 *
	 * @return string
	 */
	public function html_user_file_edit_upload( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		try {
			$assignmentModel           = $userAssignmentModel->get_assignment_model();
			$file_extension_allows     = $assignmentModel->get_file_extends_allow();
			$file_extension_allows_str = '';
			if ( ! empty( $file_extension_allows ) ) {
				$file_extension_allows_str = '.' . implode( ',.', $file_extension_allows );
			}

			$number_file_upload_allow = $assignmentModel->get_file_number_allow();
			if ( $number_file_upload_allow <= 0 ) {
				return $html;
			}

			$number_files_uploaded  = count( $userAssignmentModel->get_user_files_uploaded() );
			$number_file_can_upload = $assignmentModel->get_file_number_allow() - $number_files_uploaded;

			$section_upload_note = [
				'wrapper'      => '<div class="assignment-upload-note">',
				'file_number'  => sprintf(
					'<div>%s</div>',
					sprintf(
						__( 'File number you can submit now: <span class="assignment-number-file-can-upload">%s</span>', 'learnpress-assignments' ),
						$number_file_can_upload
					)
				),
				'file_size'    => sprintf(
					'<div>%s</div>',
					sprintf(
						__( 'Maximum file size: %sMB', 'learnpress-assignments' ),
						$assignmentModel->get_file_size_allow()
					)
				),
				'file_extends' => sprintf(
					'<div>%s</div>',
					sprintf(
						__( 'Allow file types: %s', 'learnpress-assignments' ),
						implode( ',', $file_extension_allows )
					)
				),
				'wrapper_end'  => '</div>',
			];

			$input_disable = '';
			if ( $number_file_can_upload <= 0 ) {
				$input_disable = 'disabled';
			}

			$section = [
				'wrapper'     => '<div class="assignment-user-edit-upload">',
				'label'       => sprintf( '<div class="assignment-user-edit-upload-title">%s</div>', __( 'Upload your file(s)', 'learnpress-assignments' ) ),
				'input_file'  => sprintf(
					'<input name="%1$s" class="form-control" accept="%2$s" id="_lp_upload_file" type="file" multiple="multiple" %3$s>',
					'_lp_upload_file[]',
					$file_extension_allows_str,
					$input_disable
				),
				'upload_note' => Template::combine_components( $section_upload_note ),
				'uploaded'    => $this->html_user_files_uploaded( $userAssignmentModel, true ),
				'wrapper_end' => '</div>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html files of User uploaded.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 * @param bool $is_editing
	 *
	 * @return string
	 */
	public function html_user_files_uploaded( UserAssignmentModel $userAssignmentModel, $is_editing = false ): string {
		$html = '';

		try {
			$user_files_uploaded = $userAssignmentModel->get_user_files_uploaded();
			if ( empty( $user_files_uploaded ) ) {
				return $html;
			}

			$li_list = '';
			foreach ( $user_files_uploaded as $key_file => $file ) {
				$file_path        = ABSPATH . $file->file;
				$html_action_file = '';
				if ( $is_editing ) {
					$html_action_file = sprintf(
						'<div class="assignment_file_action">
								<a class="assignment_action_icon assignment-delete-user-file" href="#" data-confirm="%1$s" data-file="%2$s" title="remove"></a>
							</div>',
						esc_attr__( 'Do you want to remove this file?', 'learnpress-assignments' ),
						esc_attr( $key_file )
					);
				}

				$size_format = '';
				if ( file_exists( $file_path ) ) {
					$file_size   = filesize( ABSPATH . $file->file );
					$size_format = $file_size ? size_format( $file_size, 2 ) : '';
				}

				$li_list .= sprintf(
					'<li>
						<div class="assignment-file">
							<a href="%s" target="_blank">%s</a>
							<span class="file-size">%s</span>
						</div>
						%s
					</li>',
					esc_url( get_site_url() . $file->url ),
					$file->filename,
					$size_format,
					$html_action_file
				);
			}

			$section = [
				'wrapper'     => '<div class="assignment-user-answered-files">',
				'label'       => sprintf(
					'<div class="assignment-user-answered-files-label">%s</div>',
					__( 'Your Uploaded File(s)', 'learnpress-assignments' )
				),
				'ul'          => '<ul class="assignment-user-files-uploaded assignment-files">',
				'list_li'     => $li_list,
				'ul_end'      => '</ul>',
				'wrapper_end' => '</div>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html button save answer.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 * @uses AssignmentAjax::assignment_save_answer()
	 *
	 * @return string
	 */
	public function html_btn_save_answer( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		try {
			$html = sprintf(
				'<button data-confirm="%s" type="button"  name="controls-button"
					value="save" class="lp-button lp-btn-assignment-save"
					data-action="assignment_save_answer">%s
 				</button>',
				esc_attr__( 'Do you want to save the answer? Your uploaded files will be replaced by the new ones, if any.', 'learnpress-assignments' ),
				__( 'Save Draft', 'learnpress-assignments' )
			);
		} catch ( Throwable $e ) {

		}

		return $html;
	}

	/**
	 * Get html button send answer.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 * @uses AssignmentAjax::assignment_submit_answer()
	 *
	 * @return string
	 */
	public function html_btn_send_answer( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		try {
			$html = sprintf(
				'<button data-confirm="%s" type="button" name="controls-button"
					value="send" class="lp-button lp-btn-assignment-send"
					data-action="assignment_submit_answer">%s
				</button>',
				esc_attr__( 'Do you want to submit the answer? After submission, you can not change the answer or resubmit.', 'learnpress-assignments' ),
				__( 'Submit', 'learnpress-assignments' )
			);
		} catch ( Throwable $e ) {

		}

		return $html;
	}

	/**
	 * Get html form user edit answer, files to submit.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 *
	 * @return string
	 */
	public function html_form_user_edit_answer( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		$section_input_hidden = [
			'course_id'     => sprintf(
				'<input type="hidden" name="course-id" value="%s">',
				esc_attr( $userAssignmentModel->ref_id )
			),
			'assignment_id' => sprintf(
				'<input type="hidden" name="assignment-id" value="%s">',
				esc_attr( $userAssignmentModel->item_id )
			),
			'action'        => '<input type="hidden" name="lp-load-ajax" value="" />', // For form know type action.
			'nonce'         => wp_nonce_field( 'wp_rest', 'nonce', false, false ),
		];

		$section_user_answer = [
			'wrapper'           => '<div class="assignment-user-answering-wrapper">',
			'form'              => '<form name="save-assignment" class="save-assignment" method="post" enctype="multipart/form-data">',
			'title'             => sprintf( '<div class="assignment-user-answering-label">%s</div>', __( 'Your Answers', 'learnpress-assignments' ) ),
			'edit_answer'       => $this->html_user_editor_answer( $userAssignmentModel ),
			'user_files_upload' => $this->html_user_file_edit_upload( $userAssignmentModel ),
			'btn_save'          => $this->html_btn_save_answer( $userAssignmentModel ),
			'btn_send'          => $this->html_btn_send_answer( $userAssignmentModel ),
			'inputs_hidden'     => Template::combine_components( $section_input_hidden ),
			'form_end'          => '</form>',
			'wrapper_end'       => '</div>',
		];

		return Template::combine_components( $section_user_answer );
	}

	/**
	 * Get html result of student.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 *
	 * @return string
	 */
	public function html_mark_result( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		$assignmentModel = $userAssignmentModel->get_assignment_model();

		$max_mark = $assignmentModel->get_max_mark();
		$percent  = 0;
		if ( $max_mark > 0 ) {
			$percent = $userAssignmentModel->get_user_mark() / $max_mark * 100;
		}

		$section = [
			'wrapper'     => sprintf( '<div class="assignment-result %s">', esc_attr( $userAssignmentModel->get_graduation() ) ),
			'progress'    => sprintf(
				'<div class="result-grade %1$s" style="--progress: %2$s;">%3$s</div>',
				esc_attr( $userAssignmentModel->get_graduation() ),
				esc_attr( $percent . '%' ),
				sprintf(
					'<span class="result-achieved student-grade">%1$s</span> %2$s',
					$userAssignmentModel->get_user_mark(),
					sprintf( __( 'out of %s points', 'learnpress-assignments' ), $assignmentModel->get_max_mark() )
				)
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Get HTML instructor note when evaluated.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 *
	 * @return string
	 */
	public function html_instructor_note( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		$html_file_note = $this->html_instructor_note_files( $userAssignmentModel );
		$text_note      = $userAssignmentModel->get_instructor_note();
		if ( empty( $html_file_note ) && empty( $text_note ) ) {
			return $html;
		}

		$section = [
			'wrapper'     => '<div class="assignment-evaluated-instructor">',
			'title'       => sprintf(
				'<div class="assignment-evaluated-instructor-title">%s</div>',
				__( 'Instructor Note', 'learnpress-assignments' )
			),
			'content'     => wpautop( Template::sanitize_html_content( $text_note ) ),
			'files_note'  => $html_file_note,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Get html files instructor note.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 *
	 * @return string
	 */
	public function html_instructor_note_files( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		$files = $userAssignmentModel->get_evaluate_instructor_upload_files();
		if ( empty( $files ) ) {
			return $html;
		}

		$li_files = '';
		foreach ( $files as $att_id ) {
			$li_files .= sprintf(
				'<li>%s</li>',
				wp_get_attachment_link( $att_id, $size = 'none' )
			);
		}

		$section = [
			'ul_files'     => '<ul class="assignment-files assignment-evaluated-instructor-files">',
			'li_files'     => $li_files,
			'ul_files_end' => '</ul>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Get html display button retake.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 * @uses AssignmentAjax::retake_assignment()
	 *
	 * @return string
	 */
	public function html_btn_retake( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		$remaining_retake = $userAssignmentModel->get_remaining_retake();
		if ( $remaining_retake === 0 ) {
			return $html;
		}

		$section = [
			'form'         => '<form name="lp-form-retake-assignment" class="lp-form-retake-assignment" method="post">',
			'btn'          => sprintf(
				'<button type="submit" class="lp-button lp-btn-retake-assignment">%s (+%s)</button>',
				__( 'Retake', 'learnpress-assignments' ),
				$remaining_retake
			),
			'input_params' => SingleAssignmentTemplate::instance()->html_input_params(
				$userAssignmentModel->ref_id,
				$userAssignmentModel->item_id
			),
			'action'       => '<input type="hidden" name="lp-load-ajax" value="retake_assignment" />',
			'nonce'        => wp_nonce_field( 'wp_rest', 'nonce', false, false ),
			'form_end'     => '</form>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Get html button finish course.
	 * Todo: write method temporary, when LP provide this method, use it.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 *
	 * @return string
	 */
	public function html_btn_finish_course( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		try {
			$courseModel = $userAssignmentModel->get_course_model();
			if ( ! $courseModel instanceof CourseModel ) {
				return $html;
			}

			$can_finish_course = $userAssignmentModel->can_finish_course();
			if ( $can_finish_course instanceof WP_Error ) {
				return $html;
			}

			$section = [
				'form'            => sprintf(
					'<form class="%1$s" data-confirm="%2$s" method="post">',
					'lp-form form-button form-button-finish-course',
					sprintf( __( 'Do you want to finish the course "%s"?', 'learnpress-assignments' ), $courseModel->get_title() )
				),
				'btn'             => sprintf(
					'<button class="lp-button lp-btn-finish-course">%s</button>',
					__( 'Finish course', 'learnpress-assignments' )
				),
				'input_course_id' => sprintf(
					'<input type="hidden" name="course-id" value="%s" />',
					$courseModel->get_id()
				),
				'input_nonce'     => sprintf(
					'<input type="hidden" name="finish-course-nonce" value="%s" />',
					wp_create_nonce( sprintf( 'finish-course-%d-%d', $courseModel->get_id(), $userAssignmentModel->user_id ) )
				),
				'input_lp_ajax'   => sprintf(
					'<input type="hidden" name="lp-ajax" value="%s" />',
					'finish-course'
				),
				'input_noajax'    => '<input type="hidden" name="noajax" value="yes"/>',
				'form_end'        => '</form>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {

		}

		return $html;
	}
}
