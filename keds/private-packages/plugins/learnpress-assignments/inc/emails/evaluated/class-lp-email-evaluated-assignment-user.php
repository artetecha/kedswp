<?php
/**
 * Class LP_Email_Assignment_Evaluated_User
 *
 * @author   ThimPress
 * @package  LearnPress/Assignments/Classes/Email
 * @version  3.0.1
 * @editor tungnx
 * @modify 4.1.3
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Email_Assignment_Evaluated_User' ) ) {
	class LP_Email_Assignment_Evaluated_User extends LP_Email_Assignment_Type {
		/**
		 * LP_Email_Assignment_Evaluated_User constructor.
		 */
		public function __construct() {
			$this->id          = 'evaluated-assignment-user';
			$this->title       = __( 'User', 'learnpress-assignments' );
			$this->description = __( 'Send this email to user when teacher have evaluated assignment.', 'learnpress-assignments' );

			$this->template_base  = LP_ADDON_ASSIGNMENTS_TEMPLATE;
			$this->template_html  = 'emails/evaluated-assignment-user.php';
			$this->template_plain = 'emails/plain/evaluated-assignment-user.php';

			$this->default_subject = __( '[{{site_title}}] Your assignment has been evaluated ({{assignment_name}})', 'learnpress-assignments' );
			$this->default_heading = __( 'Evaluated Assignment', 'learnpress-assignments' );

			parent::__construct();

			$this->support_variables[] = '{{instructor_name}}';
		}

		/**
		 * Handle send email to user
		 *
		 * @param array $params
		 *
		 * @author  tungnx
		 * @since 4.0.1
		 * @version 1.0.0
		 */
		public function handle( array $params ) {
			if ( ! $this->check_params( $params ) ) {
				return;
			}

			$this->set_receive( $this->student->get_email() );
			$this->set_data_content();
			$this->send_email();
		}
	}

	return new LP_Email_Assignment_Evaluated_User();
}
