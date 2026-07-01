<?php
/**
 * Class LP_Email_Assignment_Submitted_Admin
 *
 * @author   ThimPress
 * @package  LearnPress/Assignments/Classes/Email
 * @version  4.0.1
 * @editor tungnx
 * @modify 4.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Email_Assignment_Submitted_Instructor' ) ) {
	/**
	 * Class LP_Email_Assignment_Submitted_Admin.
	 */
	class LP_Email_Assignment_Submitted_Instructor extends LP_Email_Assignment_Type {
		public function __construct() {
			$this->id          = 'submitted-assignment-instructor';
			$this->title       = __( 'Instructor', 'learnpress-assignments' );
			$this->description = __( 'Send this email to Instructor when user have submitted assignment.', 'learnpress-assignments' );

			$this->template_base  = LP_ADDON_ASSIGNMENTS_TEMPLATE;
			$this->template_html  = 'emails/submited-assignment-instructor.php';
			$this->template_plain = 'emails/plain/submitted-assignment-admin.php';

			$this->default_subject = __( '[{{site_title}}] Student submit assignment ({{assignment_name}})', 'learnpress-assignments' );
			$this->default_heading = __( 'New Submit Assignment', 'learnpress-assignments' );

			parent::__construct();
		}

		public function handle( array $params ) {
			if ( ! $this->check_params( $params ) ) {
				return;
			}

			$this->set_receive( $this->instructor->get_email() );
			$this->set_data_content();
			$this->send_email();
		}
	}

	return new LP_Email_Assignment_Submitted_Instructor();
}
