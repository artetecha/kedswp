<?php
/**
 * Template hooks Single Assignment.
 *
 * @since 4.1.2
 * @version 1.0.0
 */

namespace LearnPressAssignment\TemplateHooks;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LearnPressAssignment\Models\UserAssignmentModel;
use LP_Addon_Assignment;
use LP_Datetime;
use Throwable;

class SingleAssignmentTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/content-item-summary/lp_assignment', [ $this, 'section_assignment_default' ] );
	}

	/**
	 * Layout default display content assignment.
	 *
	 * @return void
	 */
	public function section_assignment_default() {
		global $lpCourseModel;
		if ( $lpCourseModel instanceof CourseModel ) {
			$courseModel = $lpCourseModel;
		} else {
			$courseModel = CourseModel::find( get_the_ID(), true );
		}

		if ( ! $courseModel instanceof CourseModel ) {
			return;
		}

		$item = \LP_Global::course_item();
		if ( ! $item instanceof \LP_Assignment ) {
			return;
		}

		$assignmentModel = AssignmentPostModel::find( $item->get_id(), true );
		if ( ! $assignmentModel instanceof AssignmentPostModel ) {
			return;
		}

		$userModel = UserModel::find( get_current_user_id(), true );
		if ( ! $userModel instanceof UserModel ) {
			return;
		}

		$html_content        = '';
		$userAssignmentModel = UserAssignmentModel::find( $userModel->get_id(), $courseModel->get_id(), $item->get_id(), true );
		if ( ! $userAssignmentModel instanceof UserAssignmentModel ) {
			$html_content = $this->layout_user_not_start( $userModel, $assignmentModel, $courseModel );
		} else {
			if ( in_array(
				$userAssignmentModel->get_status(),
				[
					$userAssignmentModel::STATUS_STARTED,
					$userAssignmentModel::STATUS_DOING,
				]
			) ) {
				$html_content = $this->layout_user_started( $userAssignmentModel );
			} elseif ( $userAssignmentModel->get_status() === $userAssignmentModel::STATUS_COMPLETED ) {
				$html_content = $this->layout_user_submitted( $userAssignmentModel );
			} elseif ( $userAssignmentModel->get_status() === $userAssignmentModel::STATUS_EVALUATED ) {
				$html_content = $this->layout_instructor_evaluated( $userAssignmentModel );
			}
		}

		echo sprintf(
			'<div class="lp-course-assignment-content %1$s">%2$s</div>',
			$userAssignmentModel ? esc_attr( $userAssignmentModel->get_status() ) : '',
			$html_content
		);
	}

	/**
	 * Layout when user not start assignment.
	 *
	 * @param UserModel $userModel
	 * @param AssignmentPostModel $assignmentModel
	 * @param CourseModel $courseModel
	 *
	 * @return string
	 */
	public function layout_user_not_start( $userModel, $assignmentModel, $courseModel ): string {
		$html = '';

		try {
			$section = apply_filters(
				'learn-press/assignment/layout/user-not-start',
				[
					'title'        => $this->html_title( $assignmentModel ),
					'infos'        => $this->html_information( $assignmentModel ),
					'introduction' => $this->html_introduction_task( $assignmentModel ),
					'btn_start'    => $this->html_btn_start( $assignmentModel, $courseModel ),
				],
				$userModel,
				$assignmentModel,
				$courseModel
			);

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Layout when user started, doing assignment.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 *
	 * @return string
	 */
	public function layout_user_started( UserAssignmentModel $userAssignmentModel ): string {
		$html = '';

		try {
			$assignmentModel          = $userAssignmentModel->get_assignment_model();
			$singleAssignmentTemplate = SingleAssignmentTemplate::instance();
			$userAssignmentTemplate   = UserAssignmentTemplate::instance();

			$section_banner_status = [
				'wrapper'     => '<ul class="assignment-banner-status">',
				'countdown'   => sprintf(
					'<li>%s</li>',
					$userAssignmentTemplate->html_count_down( $userAssignmentModel )
				),
				'wrapper_end' => '</ul>',
			];

			$section = apply_filters(
				'learn-press/assignment/layout/user-started',
				[
					'banner_status'       => Template::combine_components( $section_banner_status ),
					'title'               => $singleAssignmentTemplate->html_title( $assignmentModel ),
					'infos'               => $singleAssignmentTemplate->html_information( $assignmentModel, $userAssignmentModel ),
					'description'         => $singleAssignmentTemplate->html_description( $assignmentModel ),
					'attachment'          => $singleAssignmentTemplate->html_attachments_file( $assignmentModel ),
					'section_user_answer' => $userAssignmentTemplate->html_form_user_edit_answer( $userAssignmentModel ),
				],
				$userAssignmentModel
			);

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Layout when user submitted assignment for instructor.
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 *
	 * @return string
	 */
	public function layout_user_submitted( UserAssignmentModel $userAssignmentModel ): string {
		$assignmentModel          = $userAssignmentModel->get_assignment_model();
		$singleAssignmentTemplate = SingleAssignmentTemplate::instance();
		$userAssignmentTemplate   = UserAssignmentTemplate::instance();

		$end_time      = new LP_Datetime( $userAssignmentModel->get_end_time() );
		$banner_status = [
			'date_submit' => sprintf(
				'<div class="assignment-status-submitted-time"><span>%1$s</span>: <span>%2$s (%3$s)</span></div>',
				__( 'Submitted on', 'learnpress-assignments' ),
				$end_time->format( LP_Datetime::I18N_FORMAT_HAS_TIME ),
				LP_Datetime::get_timezone_string()
			),
			'time_spend'  => sprintf(
				'<div class="assignment-status-time-spend"><span>%1$s</span>: <span>%2$s</span></div>',
				__( 'Time spent', 'learnpress-assignments' ),
				$userAssignmentModel->get_time_interval()
			),
			'status'      => sprintf(
				'<span class="assignment-status-label %1$s">%2$s</span>',
				$userAssignmentModel->get_status(),
				__( 'Waiting to be marked', 'learnpress-assignments' )
			),
		];

		$li_list = '';
		foreach ( $banner_status as $item ) {
			$li_list .= sprintf( '<li>%s</li>', $item );
		}

		$section_banner_status = [
			'wrapper'     => '<ul class="assignment-banner-status">',
			'li_list'     => $li_list,
			'wrapper_end' => '</ul>',
		];

		$section = apply_filters(
			'learn-press/assignment/layout/user-submitted',
			[
				'title'                 => $singleAssignmentTemplate->html_title( $assignmentModel ),
				'banner_status'         => Template::combine_components( $section_banner_status ),
				'section_user_answered' => $userAssignmentTemplate->html_user_answered( $userAssignmentModel ),
				'infos'                 => $singleAssignmentTemplate->html_information( $assignmentModel, $userAssignmentModel ),
				'description'           => $singleAssignmentTemplate->html_description( $assignmentModel ),
				'attachment'            => $singleAssignmentTemplate->html_attachments_file( $assignmentModel ),
				'btn_retake'            => $userAssignmentTemplate->html_btn_retake( $userAssignmentModel ),
			],
			$userAssignmentModel
		);

		return Template::combine_components( $section );
	}

	/**
	 * Layout when instructor evaluated assignment of user..
	 *
	 * @param UserAssignmentModel $userAssignmentModel
	 *
	 * @return string
	 */
	public function layout_instructor_evaluated( UserAssignmentModel $userAssignmentModel ): string {
		$html                     = '';
		$courseModel              = $userAssignmentModel->get_course_model();
		$assignmentModel          = $userAssignmentModel->get_assignment_model();
		$singleAssignmentTemplate = SingleAssignmentTemplate::instance();
		$userAssignmentTemplate   = UserAssignmentTemplate::instance();

		$display_name_author = '';
		$author_evaluated    = $userAssignmentModel->get_author_evaluated();
		if ( $author_evaluated ) {
			$display_name_author = $author_evaluated->get_display_name();
		}

		$end_time      = new LP_Datetime( $userAssignmentModel->get_end_time() );
		$banner_status = [
			'date_submit' => sprintf(
				'<div class="assignment-status-submitted-time"><span>%1$s</span>: <span>%2$s (%3$s)</span></div>',
				__( 'Submitted on', 'learnpress-assignments' ),
				$end_time->format( LP_Datetime::I18N_FORMAT_HAS_TIME ),
				LP_Datetime::get_timezone_string()
			),
			'time_spent'  => sprintf(
				'<div class="assignment-status-time-spend"><span>%1$s</span>: <span>%2$s</span></div>',
				__( 'Time spent', 'learnpress-assignments' ),
				$userAssignmentModel->get_time_interval()
			),
			'mark'        => sprintf(
				'<div class="assignment-status-mark">
					<span>%1$s</span>: <span>%2$s</span> %3$s
				</div>',
				__( 'Marked', 'learnpress-assignments' ),
				$userAssignmentModel->get_user_mark() . '/' . $assignmentModel->get_max_mark(),
				! empty( $display_name_author ) ? sprintf( '%s %s', __( 'by', 'learnpress-assignments' ), $display_name_author ) : ''
			),
			'status'      => sprintf(
				'<span class="assignment-status-label %1$s">%2$s</span>',
				$userAssignmentModel->get_graduation(),
				LP_Addon_Assignment::get_i18n_value( $userAssignmentModel->get_graduation() )
			),
		];

		$li_list = '';
		foreach ( $banner_status as $item ) {
			$li_list .= sprintf( '<li>%s</li>', $item );
		}

		$section_banner_status = [
			'wrapper'     => '<ul class="assignment-banner-status">',
			'li_list'     => $li_list,
			'wrapper_end' => '</ul>',
		];

		$section = [
			'title'                 => $singleAssignmentTemplate->html_title( $assignmentModel ),
			'banner_status'         => Template::combine_components( $section_banner_status ),
			'mark_result'           => $userAssignmentTemplate->html_mark_result( $userAssignmentModel ),
			'instructor_note'       => $userAssignmentTemplate->html_instructor_note( $userAssignmentModel ),
			'section_user_answered' => $userAssignmentTemplate->html_user_answered( $userAssignmentModel ),
			'infos'                 => $singleAssignmentTemplate->html_information( $assignmentModel, $userAssignmentModel ),
			'description'           => $singleAssignmentTemplate->html_description( $assignmentModel ),
			'attachment'            => $singleAssignmentTemplate->html_attachments_file( $assignmentModel ),
			'btn_retake'            => $userAssignmentTemplate->html_btn_retake( $userAssignmentModel ),
			'btn_finish_course'     => $userAssignmentTemplate->html_btn_finish_course( $userAssignmentModel ),
		];

		return Template::combine_components( $section );
	}

	/**
	 * Get display title course.
	 *
	 * @param AssignmentPostModel $assignment
	 * @param string $tag_html
	 *
	 * @return string
	 */
	public function html_title( $assignment, string $tag_html = 'h1' ): string {
		$tag_html = sanitize_key( $tag_html );

		$section = apply_filters(
			'learn-press/assignment/html-title',
			[
				'wrapper'     => sprintf( '<%s class="assignment-title">', esc_attr( $tag_html ) ),
				'content'     => wp_kses_post( $assignment->get_the_title() ),
				'wrapper_end' => "</{$tag_html}>",
			],
			$assignment,
			$tag_html
		);

		return Template::combine_components( $section );
	}

	/**
	 * Get html description assignment.
	 *
	 * @param AssignmentPostModel $assignment
	 *
	 * @return string
	 * @since 4.1.2
	 * @version 1.0.1
	 */
	public function html_description( $assignment ): string {
		$html = '';

		try {
			global $post;
			$post = get_post( $assignment->get_id() );
			setup_postdata( $post );
			$content = get_the_content( null, false, $post );
			$content = apply_filters( 'the_content', $content );
			$content = str_replace( ']]>', ']]&gt;', $content );
			wp_reset_postdata();
			if ( empty( $content ) ) {
				return $html;
			}

			$section = [
				'wrapper'     => '<div class="assignment-description">',
				'label'       => sprintf( '<div class="assignment-description-label">%s</div>', __( 'Task', 'learnpress-assignments' ) ),
				'description' => Template::sanitize_html_content( $content ),
				'wrapper_end' => '</div>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html description assignment.
	 *
	 * @param AssignmentPostModel $assignment
	 *
	 * @return string
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public function html_introduction_task( $assignment ): string {
		$html = '';

		try {
			$content = $assignment->get_introduction_task();
			if ( empty( $content ) ) {
				return $html;
			}

			$section = [
				'wrapper'     => '<div class="assignment-task-introduction">',
				'title'       => '<div class="assignment-task-introduction-title">' . __( 'Introduction', 'learnpress-assignments' ) . '</div>',
				'description' => Template::sanitize_html_content( $content ),
				'wrapper_end' => '</div>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html description assignment.
	 *
	 * @param AssignmentPostModel $assignment
	 * @param CourseModel $courseModel
	 * @uses AssignmentAjax::start_assignment()
	 *
	 * @return string
	 * @since 4.1.2
	 * @version 1.0.1
	 */
	public function html_btn_start( $assignment, $courseModel ): string {
		$html = '';

		try {
			$section = apply_filters(
				'learn-press/assignment/html-btn-start',
				[
					'form'         => '<form name="lp-form-start-assignment" class="lp-form-start-assignment" method="post">',
					'btn_start'    => sprintf(
						'<button type="submit" name="start-assignment" class="lp-button lp-btn-start-assignment">%s</button>',
						__( 'Start Assignment', 'learnpress-assignments' )
					),
					'input_params' => $this->html_input_params( $courseModel->get_id(), $assignment->get_id() ),
					'input_ajax'   => sprintf(
						'<input type="hidden" name="lp-load-ajax" value="%s">',
						esc_attr( 'start_assignment' )
					),
					'nonce_field'  => wp_nonce_field( 'wp_rest', 'nonce', false, false ),
					'form_end'     => '</form>',
				],
				$assignment,
				$courseModel
			);

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html information assignment.
	 *
	 * @param AssignmentPostModel $assignment
	 * @param bool $userAssignmentModel
	 *
	 * @return string
	 */
	public function html_information( $assignment, $userAssignmentModel = false ): string {
		$html = '';

		try {
			$info = [
				'duration'            => [
					'label' => esc_html__( 'Duration', 'learnpress-assignments' ),
					'value' => $this->html_duration( $assignment ),
				],
				'total_grade'         => [
					'label' => esc_html__( 'Total grade', 'learnpress-assignments' ),
					'value' => $this->html_max_point( $assignment ),
				],
				'passing_grade'       => [
					'label' => esc_html__( 'Passing grade', 'learnpress-assignments' ),
					'value' => $this->html_passing_grade( $assignment ),
				],
				're_attempts_allowed' => [
					'label' => esc_html__( 'Re-attempts allowed', 'learnpress-assignments' ),
					'value' => $this->html_retake_number( $assignment, $userAssignmentModel ),
				],
			];

			$li_infos = '';
			foreach ( $info as $item ) {
				$li_infos .= sprintf(
					'<li>
						<label>%s</label>
						<span>%s</span>
					</li>',
					$item['label'],
					$item['value']
				);
			}

			$section = [
				'wrapper'     => '<ul class="assignment-information">',
				'list_li'     => $li_infos,
				'wrapper_end' => '</ul>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html duration course.
	 *
	 * @param AssignmentPostModel $assignment
	 *
	 * @return string
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public function html_duration( $assignment ): string {
		$html = '';

		try {

			$duration        = $assignment->get_meta_value_by_key( $assignment::META_KEY_DURATION, '' );
			$duration_arr    = explode( ' ', $duration );
			$duration_number = floatval( $duration_arr[0] ?? 0 );
			$duration_type   = $duration_arr[1] ?? '';
			if ( $duration_number <= 0 ) {
				$duration_str = __( 'Unlimited', 'learnpress-assignments' );
			} else {
				$duration_str = LP_Datetime::get_string_plural_duration( $duration_number, $duration_type );
			}

			$html = sprintf( '<span class="assignment-duration">%s</span>', $duration_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html attachments file.
	 *
	 * @param AssignmentPostModel $assignment
	 *
	 * @return string
	 */
	public function html_attachments_file( $assignment ): string {
		$html = '';

		try {
			$assignment_ids = $assignment->get_attachments_assignment();
			$assignment_ids = apply_filters( 'learn-press/assignment/attachment-files', $assignment_ids, $assignment );
			if ( empty( $assignment_ids ) ) {
				return $html;
			}

			$html_list_li_files = '';
			foreach ( $assignment_ids as $att_id ) {
				$file      = get_attached_file( $att_id );
				$file_name = esc_html( pathinfo( $file, PATHINFO_FILENAME ) );
				$file_info = wp_check_filetype( $file );
				if ( ! $file ) {
					continue;
				}

				$link_file = wp_get_attachment_url( $att_id );

				$html_list_li_files .= sprintf(
					'<li>
						<div class="assignment-file">
							<a href="%1$s" target="_blank">%2$s</a>
							<span class="file-size">%3$s</span>
						</div>
					</li>',
					//wp_get_attachment_link( $att_id, 'none', false, false, sprintf( '%s.%s', $file_name, $file_info['ext'] ) ),
					esc_url( $link_file ),
					sprintf( '%s.%s', $file_name, $file_info['ext'] ),
					size_format( filesize( get_attached_file( $att_id ) ), 2 )
				);
			}

			$section_list = [
				'wrapper'     => '<ul class="assignment-files assignment-documentations">',
				'list_li'     => $html_list_li_files,
				'wrapper_end' => '</ul>',
			];

			$section = [
				'wrapper'     => '<div class="assignment-attachment">',
				'title'       => sprintf(
					'<div class="assignment-attachment-title">%s</div>',
					esc_html__( 'Attachment File(s):', 'learnpress-assignments' )
				),
				'list'        => Template::combine_components( $section_list ),
				'wrapper_end' => '</div>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html retake number.
	 *
	 * @param AssignmentPostModel $assignment
	 * @param bool $userAssignmentModel
	 *
	 * @return string
	 */
	public function html_retake_number( $assignment, $userAssignmentModel = false ): string {
		$html = '';

		try {
			$retake_number = $assignment->get_retake_count();
			if ( $retake_number > 0 ) {
				if ( $userAssignmentModel instanceof UserAssignmentModel ) {
					$remaining_retake = $userAssignmentModel->get_remaining_retake();
					if ( $remaining_retake <= 0 ) {
						$content = __( 'You have run out of retakes', 'learnpress-assignments' );
					} else {
						$content = sprintf(
							'%1$d/%2$d %3$s',
							$remaining_retake,
							$retake_number,
							_n( 'time', 'times', $retake_number, 'learnpress-assignments' )
						);
					}
				} else {
					$content = sprintf(
						'%1$d %2$s',
						$retake_number,
						_n( 'time', 'times', $retake_number, 'learnpress-assignments' )
					);
				}
			} elseif ( $retake_number == 0 ) {
				$content = esc_html__( 'No', 'learnpress-assignments' );
			} else {
				$content = esc_html__( 'Unlimited', 'learnpress-assignments' );
			}

			$html = sprintf( '<span class="assignment-retake-number">%s</span>', $content );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html retake number.
	 *
	 * @param AssignmentPostModel $assignment
	 *
	 * @return string
	 */
	public function html_passing_grade( $assignment ): string {
		$html = '';

		try {
			$passing_grade     = $assignment->get_passing_grade();
			$passing_grade_str = sprintf( '%1$s %2$s', $passing_grade, _n( 'point', 'points', $passing_grade, 'learnpress-assignments' ) );

			$html = sprintf( '<span class="assignment-passing-grade">%s</span>', $passing_grade_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html retake number.
	 *
	 * @param AssignmentPostModel $assignment
	 *
	 * @return string
	 */
	public function html_max_point( $assignment ): string {
		$html = '';

		try {
			$max_point     = $assignment->get_max_mark();
			$max_point_str = sprintf( '%1$s %2$s', $max_point, _n( 'point', 'points', $max_point, 'learnpress-assignments' ) );

			$html = sprintf( '<span class="assignment-max-point">%s</span>', $max_point_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html input hidden has params course_id, assignment_id.
	 *
	 * @param int $course_id
	 * @param int $assignment_id
	 *
	 * @return string
	 */
	public function html_input_params( int $course_id, int $assignment_id ): string {
		$html = '';

		try {
			$section = [
				'input_course_id'     => sprintf(
					'<input type="hidden" name="course-id" value="%s">',
					esc_attr( $course_id )
				),
				'input_assignment_id' => sprintf(
					'<input type="hidden" name="assignment-id" value="%s">',
					esc_attr( $assignment_id )
				),
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}
}
