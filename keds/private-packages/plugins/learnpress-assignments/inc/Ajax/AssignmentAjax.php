<?php

namespace LearnPressAssignment\Ajax;

use Exception;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LearnPressAssignment\Models\UserAssignmentModel;
use LP_Addon_Assignment;
use LP_Datetime;
use LP_Helper;
use LP_Request;
use LP_REST_Response;
use Throwable;

/**
 * class AjaxBase
 *
 * @since 4.1.6
 * @version 1.0.0
 */
class AssignmentAjax extends AbstractAjax {
	/**
	 * Start assignment
	 *
	 * @return void
	 */
	public function start_assignment() {
		$assignment_id = LP_Request::get_param( 'assignment-id', 'int', 0, 'post' );
		$course_id     = LP_Request::get_param( 'course-id', 'int', 0, 'post' );

		try {
			$assignment = AssignmentPostModel::find( $assignment_id, true );
			if ( empty( $assignment ) ) {
				throw new Exception( esc_html__( 'Assignment is invalid!', 'learnpress-assignments' ) );
			}

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( esc_html__( 'Course is invalid!', 'learnpress-assignments' ) );
			}

			$userModel = UserModel::find( get_current_user_id(), true );
			if ( ! $userModel ) {
				throw new Exception( esc_html__( 'User is invalid!', 'learnpress-assignments' ) );
			}

			$result = LP_Addon_Assignment::user_start_assignment( $userModel, $courseModel, $assignment );
			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}
		} catch ( Throwable $e ) {
			$mess_error = [
				'status'  => 'error',
				'content' => $e->getMessage(),
			];
			learn_press_set_message( $mess_error );
		}

		wp_safe_redirect( LP_Helper::getUrlCurrent() );
		die;
	}

	/**
	 * Save assignment answer
	 *
	 * @return void
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function assignment_save_answer() {
		$response = new LP_REST_Response();

		try {
			$assignment_id  = LP_Request::get_param( 'assignment-id', 0, 'int', 'post' );
			$course_id      = LP_Request::get_param( 'course-id', 0, 'int', 'post' );
			$uploaded_files = $_FILES['_lp_upload_file'] ?? '';
			$user_answer    = LP_Request::get_param( 'assignment-editor-frontend', '', 'html', 'post' );

			$userModel = UserModel::find( get_current_user_id(), true );
			if ( ! $userModel ) {
				throw new Exception( esc_html__( 'User is invalid!', 'learnpress-assignments' ) );
			}

			$assignmentModel = AssignmentPostModel::find( $assignment_id, true );
			if ( empty( $assignmentModel ) ) {
				throw new Exception( esc_html__( 'Assignment is invalid!', 'learnpress-assignments' ) );
			}

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( esc_html__( 'Course is invalid!', 'learnpress-assignments' ) );
			}

			$userCourseModel = UserCourseModel::find( $userModel->get_id(), $courseModel->get_id(), true );
			if ( ! $userCourseModel || ! $userCourseModel->has_enrolled() ) {
				throw new Exception( __( 'User must enroll course first', 'learnpress-assignments' ) );
			}

			$userAssignmentModel = UserAssignmentModel::find(
				$userModel->get_id(),
				$courseModel->get_id(),
				$assignmentModel->get_id(),
				true
			);
			if ( ! $userAssignmentModel || ! in_array(
				$userAssignmentModel->get_status(),
				[
					$userAssignmentModel::STATUS_STARTED,
					$userAssignmentModel::STATUS_DOING,
				]
			) ) {
				throw new Exception( __( 'User must start assignment', 'learnpress-assignments' ) );
			}

			// Save answer of user
			$userAssignmentModel->set_meta_value_for_key(
				$userAssignmentModel::META_KEY_ANSWER_NOTE,
				$user_answer,
				true
			);
			// Save files of user upload.
			$userAssignmentModel->upload_files_of_student( $uploaded_files );

			$userAssignmentModel->status = UserAssignmentModel::STATUS_DOING;
			$userAssignmentModel->save();

			$response->message = esc_html__( 'Your answer has been saved!', 'learnpress-assignments' );

			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		learn_press_set_message(
			[
				'status'  => $response->status,
				'content' => $response->message,
			]
		);

		wp_safe_redirect( LP_Helper::getUrlCurrent() );
		die;
	}

	/**
	 * Save assignment answer
	 *
	 * @return void
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function assignment_submit_answer() {
		$response = new LP_REST_Response();

		try {
			$assignment_id  = LP_Request::get_param( 'assignment-id', 0, 'int', 'post' );
			$course_id      = LP_Request::get_param( 'course-id', 0, 'int', 'post' );
			$uploaded_files = $_FILES['_lp_upload_file'] ?? '';
			$user_answer    = LP_Request::get_param( 'assignment-editor-frontend', '', 'html', 'post' );

			$userModel = UserModel::find( get_current_user_id(), true );
			if ( ! $userModel ) {
				throw new Exception( esc_html__( 'User is invalid!', 'learnpress-assignments' ) );
			}

			$assignmentModel = AssignmentPostModel::find( $assignment_id, true );
			if ( empty( $assignmentModel ) ) {
				throw new Exception( esc_html__( 'Assignment is invalid!', 'learnpress-assignments' ) );
			}

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( esc_html__( 'Course is invalid!', 'learnpress-assignments' ) );
			}

			$userCourseModel = UserCourseModel::find( $userModel->get_id(), $courseModel->get_id(), true );
			if ( ! $userCourseModel || ! $userCourseModel->has_enrolled() ) {
				throw new Exception( __( 'User must enroll course first', 'learnpress-assignments' ) );
			}

			$userAssignmentModel = UserAssignmentModel::find(
				$userModel->get_id(),
				$courseModel->get_id(),
				$assignmentModel->get_id(),
				true
			);
			if ( ! $userAssignmentModel || ! in_array(
				$userAssignmentModel->get_status(),
				[
					$userAssignmentModel::STATUS_STARTED,
					$userAssignmentModel::STATUS_DOING,
				]
			) ) {
				throw new Exception( __( 'User must start assignment', 'learnpress-assignments' ) );
			}

			// Save answer of user
			$userAssignmentModel->set_meta_value_for_key(
				$userAssignmentModel::META_KEY_ANSWER_NOTE,
				$user_answer,
				true
			);
			// Save files of user upload.
			$userAssignmentModel->upload_files_of_student( $uploaded_files );

			$userAssignmentModel->status = $userAssignmentModel::STATUS_COMPLETED;
			$duration                    = $assignmentModel->get_duration();
			$remaining_time              = $userAssignmentModel->get_time_remaining();
			// For case unlimited time or time not expired.
			if ( (int) $duration === 0 || $remaining_time > 0 ) {
				$userAssignmentModel->end_time = gmdate( LP_Datetime::$format, time() );
			} else {
				// Set end time max duration.
				$start_time                    = $userAssignmentModel->get_start_time();
				$end_time_max_stamp            = strtotime( '+' . $duration, strtotime( $start_time ) );
				$userAssignmentModel->end_time = gmdate( LP_Datetime::$format, $end_time_max_stamp );
			}

			$userAssignmentModel->save();

			do_action( 'learn-press/assignment/student-submitted', $userModel->get_id(), $assignmentModel->get_id() );

			$response->message = esc_html__(
				'Your assignment has been submitted successfully! Please wait for your instructor to review and mark it.',
				'learnpress-assignments'
			);

			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		learn_press_set_message(
			[
				'status'  => $response->status,
				'content' => $response->message,
			]
		);

		wp_safe_redirect( LP_Helper::getUrlCurrent() );
		die;
	}

	/**
	 * Retake assignment
	 *
	 * @return void
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function retake_assignment() {
		$response      = new LP_REST_Response();
		$assignment_id = LP_Request::get_param( 'assignment-id', 'int', 0, 'post' );
		$course_id     = LP_Request::get_param( 'course-id', 'int', 0, 'post' );

		try {
			$assignment = AssignmentPostModel::find( $assignment_id, true );
			if ( empty( $assignment ) ) {
				throw new Exception( esc_html__( 'Assignment is invalid!', 'learnpress-assignments' ) );
			}

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( esc_html__( 'Course is invalid!', 'learnpress-assignments' ) );
			}

			$userModel = UserModel::find( get_current_user_id(), true );
			if ( ! $userModel ) {
				throw new Exception( esc_html__( 'User is invalid!', 'learnpress-assignments' ) );
			}

			$userAssignmentModel = UserAssignmentModel::find(
				$userModel->get_id(),
				$courseModel->get_id(),
				$assignment->get_id()
			);
			if ( ! $userAssignmentModel ) {
				throw new Exception( esc_html__( 'User Assignment is invalid!', 'learnpress-assignments' ) );
			}

			$userAssignmentModel->handle_retake();

			$response->status  = 'success';
			$response->message = esc_html__( 'Assignment has been retaken!', 'learnpress-assignments' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		if ( ! empty( $response->message ) ) {
			learn_press_set_message(
				[
					'status'  => $response->status,
					'content' => $response->message,
				]
			);
		}

		wp_safe_redirect( LP_Helper::getUrlCurrent() );
		die;
	}
}
