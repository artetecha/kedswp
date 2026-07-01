<?php
/**
 * class SendEmailAjax
 *
 * @since 4.1.7
 * @version 1.0.0
 */

namespace LearnPressAssignment\Ajax;

use LearnPress\Ajax\AbstractAjax;
use LP_Debug;
use LP_Email;
use LP_Email_Assignment_Evaluated_Admin;
use LP_Email_Assignment_Evaluated_Instructor;
use LP_Email_Assignment_Evaluated_User;
use LP_Email_Assignment_Submitted_Admin;
use LP_Email_Assignment_Submitted_Instructor;
use LP_Email_Assignment_Submitted_User;
use LP_Helper;
use LP_REST_Response;
use Throwable;

class SendEmailAjax extends AbstractAjax {
	/**
	 * Send mail when order status update to complete
	 *
	 * @since 4.1.7
	 * @version 1.0.0
	 */
	public function send_mail_assignment_instructor_evaluated() {
		try {
			$data_send = LP_Helper::sanitize_params_submitted( $_POST['params'] ?? [] );

			$email_classes = apply_filters(
				'learn-press/assignment/instructor-evaluated/send-mail',
				[
					LP_Email_Assignment_Evaluated_Admin::class,
					LP_Email_Assignment_Evaluated_Instructor::class,
					LP_Email_Assignment_Evaluated_User::class,
				]
			);

			foreach ( $email_classes as $email_class ) {
				if ( class_exists( $email_class ) ) {
					$email = new $email_class();
					/** @var LP_Email $email */
					$email->handle( $data_send );
				}
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}

	/**
	 * Send mail when order status update to complete
	 *
	 * @since 4.1.7
	 * @version 1.0.0
	 */
	public function send_mail_assignment_student_submitted() {
		try {
			$data_send = LP_Helper::sanitize_params_submitted( $_POST['params'] ?? [] );

			$email_classes = apply_filters(
				'learn-press/assignment/student-submitted/send-mail',
				[
					LP_Email_Assignment_Submitted_Admin::class,
					LP_Email_Assignment_Submitted_Instructor::class,
					LP_Email_Assignment_Submitted_User::class,
				]
			);

			foreach ( $email_classes as $email_class ) {
				if ( class_exists( $email_class ) ) {
					$email = new $email_class();
					/** @var LP_Email $email */
					$email->handle( $data_send );
				}
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}
}
