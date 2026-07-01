<?php
/**
 * Class LP_Email_Assignment_Submitted_User
 *
 * @author   ThimPress
 * @package  LearnPress/Assignments/Classes/Email
 * @version  3.0.1
 * @editor tungnx
 * @modify 4.0.1
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Email_Assignment_Submitted_User' ) ) {
	class LP_Email_Assignment_Submitted_User extends LP_Email_Assignment_Type {
		/**
		 * LP_Email_Assignment_Submitted_User constructor.
		 */
		public function __construct() {
			$this->id          = 'submitted-assignment-user';
			$this->title       = __( 'User', 'learnpress-assignments' );
			$this->description = __( 'Send this email to user when they have submitted assignment.', 'learnpress-assignments' );

			$this->template_base  = LP_ADDON_ASSIGNMENTS_TEMPLATE;
			$this->template_html  = 'emails/submited-assignment-user.php';
			$this->template_plain = 'emails/plain/submitted-assignment-user.php';

			$this->default_subject = __( '[{{site_title}}] You just submitted assignment ({{assignment_name}})', 'learnpress-assignments' );
			$this->default_heading = __( 'New Submit Assignment', 'learnpress-assignments' );

			parent::__construct();
		}

		public function handle( array $params ) {
			if ( ! $this->check_params( $params ) ) {
				return;
			}

			$this->set_receive( $this->student->get_email() );
			$this->set_data_content();
			$this->send_email();
		}
	}

	return new LP_Email_Assignment_Submitted_User();
}
