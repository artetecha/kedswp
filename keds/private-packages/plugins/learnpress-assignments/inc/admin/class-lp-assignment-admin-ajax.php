<?php
/**
 * Class LP_Assignment_Admin_Ajax
 *
 * @author  ThimPress
 * @package LearnPress/Assignments/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LearnPressAssignment\Models\UserAssignmentModel;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Assignment_Admin_Ajax' ) ) {
	/**
	 * Class LP_Assignment_Admin_Ajax
	 */
	class LP_Assignment_Admin_Ajax {

		/**
		 * Add action ajax.
		 */
		public static function init() {

			if ( ! is_user_logged_in() ) {
				return;
			}

			$actions = array(
				'send_evaluated_mail',
				'delete_submission',
				're_evaluate',
				'get_passing_grade_final_assignment',
			);

			foreach ( $actions as $action ) {
				add_action( 'wp_ajax_lp_assignment_' . $action, array( __CLASS__, $action ) );
			}
		}

		/**
		 * Resend evaluated mail.
		 *
		 * @version 1.0.1
		 */
		public static function send_evaluated_mail() {
			$response = new LP_REST_Response();

			try {
				$user_id       = LP_Request::get_param( 'user_id', 0, 'int', 'post' );
				$assignment_id = LP_Request::get_param( 'assignment_id', 0, 'int', 'post' );
				$course_id     = LP_Request::get_param( 'course_id', 0, 'int', 'post' );

				$userModel = UserModel::find( $user_id, true );
				if ( ! $userModel ) {
					throw new Exception( esc_html__( 'Invalid user!', 'learnpress-assignments' ) );
				}

				$courseModel = CourseModel::find( $course_id, true );
				if ( ! $courseModel ) {
					throw new Exception( esc_html__( 'Invalid course!', 'learnpress-assignments' ) );
				}

				$assignmentModel = AssignmentPostModel::find( $assignment_id, true );
				if ( ! $assignmentModel ) {
					throw new Exception( esc_html__( 'Invalid assignment!', 'learnpress-assignments' ) );
				}

				$userAssignmentModel = UserAssignmentModel::find( $user_id, $course_id, $assignment_id, true );
				if ( ! $userAssignmentModel
					|| $userAssignmentModel->get_status() !== $userAssignmentModel::STATUS_EVALUATED ) {
					throw new Exception( esc_html__( 'Instructor not Evaluated!', 'learnpress-assignments' ) );
				}

				$author = UserModel::find( get_current_user_id(), true );
				$userAssignmentModel->check_author_can_evaluate_assignment( $author );

				$email = new LP_Email_Assignment_Evaluated_User();

				$result = $email->handle( [ $user_id, $assignment_id ] );

				if ( $result ) {
					$response->status  = 'success';
					$response->message = __( 'Send mail to student success!', 'learnpress-assignments' );
				} else {
					$response->message = __( 'Send mail to student fail!', 'learnpress-assignments' );
				}
			} catch ( Throwable $e ) {
				$response->message = $e->getMessage();
			}

			wp_send_json( $response );
		}

		/**
		 * Delete user's assignment and user can send it again.
		 *
		 * @version 1.0.1
		 */
		public static function delete_submission() {
			$response = new LP_REST_Response();

			try {
				$user_id       = LP_Request::get_param( 'user_id', 0, 'int', 'post' );
				$assignment_id = LP_Request::get_param( 'assignment_id', 0, 'int', 'post' );
				$course_id     = LP_Request::get_param( 'course_id', 0, 'int', 'post' );

				$userModel = UserModel::find( $user_id, true );
				if ( ! $userModel ) {
					throw new Exception( esc_html__( 'Invalid user', 'learnpress-assignments' ) );
				}

				$courseModel = CourseModel::find( $course_id, true );
				if ( ! $courseModel ) {
					throw new Exception( esc_html__( 'Invalid course', 'learnpress-assignments' ) );
				}

				$assignmentModel = AssignmentPostModel::find( $assignment_id, true );
				if ( ! $assignmentModel ) {
					throw new Exception( esc_html__( 'Invalid assignment', 'learnpress-assignments' ) );
				}

				// Check user course
				$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
				if ( ! $userCourseModel ) {
					throw new Exception( esc_html__( 'Invalid user course', 'learnpress-assignments' ) );
				}

				if ( $userCourseModel->has_finished() ) {
					throw new Exception( esc_html__( 'User has finished course, so you can not delete', 'learnpress-assignments' ) );
				}

				$userAssignmentModel = UserAssignmentModel::find( $user_id, $course_id, $assignment_id, true );
				if ( ! $userAssignmentModel ) {
					throw new Exception( esc_html__( 'Invalid user assignment', 'learnpress-assignments' ) );
				}

				if ( ! in_array(
					$userAssignmentModel->get_status(),
					[
						$userAssignmentModel::STATUS_COMPLETED,
						$userAssignmentModel::STATUS_EVALUATED,
					]
				) ) {
					throw new Exception( esc_html__( 'User assignment not submit', 'learnpress-assignments' ) );
				}

				$userAssignmentModel->delete();
				$response->status  = 'success';
				$response->message = esc_html__( 'Delete submission successful!', 'learnpress-assignments' );
			} catch ( Throwable $e ) {
				$response->message = $e->getMessage();
			}

			wp_send_json( $response );
		}

		/**
		 * Clear the result has evaluated.
		 *
		 * @version 1.0.1
		 */
		public static function re_evaluate() {
			$response = new LP_REST_Response();

			try {
				$user_id       = LP_Request::get_param( 'user_id', 0, 'int', 'post' );
				$assignment_id = LP_Request::get_param( 'assignment_id', 0, 'int', 'post' );
				$course_id     = LP_Request::get_param( 'course_id', 0, 'int', 'post' );

				$author = UserModel::find( get_current_user_id(), true );

				$userAssignmentModel = UserAssignmentModel::find( $user_id, $course_id, $assignment_id, true );
				if ( ! $userAssignmentModel ) {
					throw new Exception( esc_html__( 'Invalid user assignment', 'learnpress-assignments' ) );
				}

				$data = [ 'author' => $author ];
				$userAssignmentModel->instructor_re_evaluate_assignment( $data );

				$response->status  = 'success';
				$response->message = esc_html__( 'Delete evaluated successful!', 'learnpress-assignments' );
			} catch ( Throwable $e ) {
				$response->message = $e->getMessage();
			}

			wp_send_json( $response );
		}

		/**
		 * Get final assignment assign for Course.
		 */
		public static function get_passing_grade_final_assignment() {
			$course_id = LP_Request::get_param( 'course_id', 0, 'int', 'post' );

			try {
				$output = array(
					'status'  => 'fail',
					'message' => '',
				);

				if ( ! $course_id ) {
					throw new Exception( esc_html__( 'No Course avaliable!', 'learnpress-assignments' ) );
				}

				$course = learn_press_get_course( $course_id );

				if ( ! $course ) {
					throw new Exception( esc_html__( 'No Course avaliable!', 'learnpress-assignments' ) );
				}

				$items = $course->get_item_ids();

				if ( $items ) {
					foreach ( $items as $item ) {
						if ( learn_press_get_post_type( $item ) === 'lp_assignment' ) {
							$final_assignment = $item;
						}
					}
				}

				ob_start();
				?>

				<div class="lp-metabox-evaluate-assignment">
					<?php
					if ( isset( $final_assignment ) ) {
						$assignmentPostModel = AssignmentPostModel::find( $final_assignment, true );
						if ( ! $assignmentPostModel ) {
							throw new Exception( esc_html__( 'Invalid assignment', 'learnpress-assignments' ) );
						}

						update_post_meta( $course_id, '_lp_final_assignment', $final_assignment );
						$passing_grade = $assignmentPostModel->get_passing_grade();
						$max_mark      = $assignmentPostModel->get_max_mark();
						$url           = get_edit_post_link( $final_assignment );

						$output['status'] = 'success';
						?>

						<div class="lp-metabox-evaluate-assignment__message">
							<?php
							echo sprintf(
								esc_html__( 'Passing Grade: %s', 'learpress-assignments' ),
								$max_mark != 0 ? ( $passing_grade / $max_mark ) * 100 : $max_mark
							) . '%';
							?>
							-
							<?php
							printf(
								esc_html__( 'Assignment: %s', 'learnpress-assignments' ),
								'<a href="' . $url . '">' . get_the_title( $final_assignment ) . '</a>'
							);
							?>
						</div>

					<?php } else { ?>
						<div
							class="lp-metabox-evaluate-assignment__message lp-metabox-evaluate-assignment__message--error">
							<?php
							esc_html_e(
								'No Assignment item in Course!',
								'learnpress-assignments'
							);
							?>
						</div>
					<?php } ?>
				</div>

				<?php
				$output['message'] = ob_get_clean();
			} catch ( Exception $e ) {
				$output['message'] = '<div class="lp-metabox-evaluate-assignment__message lp-metabox-evaluate-assignment__message--error"">' . $e->getMessage() . '</div>';
			}

			wp_send_json( $output );
		}
	}
}

add_action( 'admin_init', array( 'LP_Assignment_Admin_Ajax', 'init' ) );
