<?php
/**
 * Class LP_Email_Assignment_Evaluated_Admin
 *
 * @author   ThimPress
 * @package  LearnPress/Assignments/Classes/Email
 * @version  4.0.1
 * @editor tungnx
 * @modify 4.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Email_Assignment_Evaluated_Instructor' ) ) {
	class LP_Email_Assignment_Evaluated_Instructor extends LP_Email_Assignment_Type {
		/**
		 * LP_Email_Assignment_Evaluated_Admin constructor.
		 */
		public function __construct() {
			$this->id          = 'evaluated-assignment-instructor';
			$this->title       = __( 'Instructor', 'learnpress-assignments' );
			$this->description = __( 'Send this email to Instructor when they have evaluated assignment.', 'learnpress-assignments' );

			$this->template_base  = LP_ADDON_ASSIGNMENTS_TEMPLATE;
			$this->template_html  = 'emails/evaluated-assignment-instructor.php';
			$this->template_plain = 'emails/plain/evaluated-assignment-admin.php';

			$this->default_subject = __( '[{{site_title}}] You just evaluated assignment ({{assignment_name}})', 'learnpress-assignments' );
			$this->default_heading = __( 'Evaluated Assignment', 'learnpress-assignments' );

			add_filter( 'lp/email/assignment/evaluated', array( $this, 'support_variables' ) );

			parent::__construct();
		}

		public function handle( array $params ) {
			if ( ! $this->check_params( $params ) ) {
				return;
			}

			$this->set_data_content();
			$this->set_receive( $this->instructor->get_email() );
			$this->send_email();
		}
	}

	return new LP_Email_Assignment_Evaluated_Instructor();
}
