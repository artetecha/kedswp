<?php
/**
 * Class LP_Email_Assignment_Evaluated_Admin
 *
 * @author   ThimPress
 * @package  LearnPress/Assignments/Classes/Email
 * @version  3.0.1
 * @editor tungnx
 * @modify 4.1.3
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Email_Assignment_Evaluated_Admin' ) ) {
	class LP_Email_Assignment_Evaluated_Admin extends LP_Email_Assignment_Type {
		/**
		 * LP_Email_Assignment_Evaluated_Admin constructor.
		 */
		public function __construct() {
			$this->id          = 'evaluated-assignment-admin';
			$this->title       = __( 'Admin', 'learnpress-assignments' );
			$this->description = __( 'Send this email to admin when they have evaluated assignment.', 'learnpress-assignments' );

			$this->template_base  = LP_ADDON_ASSIGNMENTS_TEMPLATE;
			$this->template_html  = 'emails/evaluated-assignment-admin.php';
			$this->template_plain = 'emails/plain/evaluated-assignment-admin.php';

			$this->default_subject = __( '[{{site_title}}] You just evaluated assignment ({{assignment_name}})', 'learnpress-assignments' );
			$this->default_heading = __( 'Evaluated Assignment', 'learnpress-assignments' );
			$this->recipient       = LearnPress::instance()->settings()->get( 'emails_' . $this->id . '.recipients', $this->_get_admin_email() );

			parent::__construct();
		}

		public function handle( array $params ) {
			if ( ! $this->check_params( $params ) ) {
				return;
			}

			$this->set_data_content();
			$this->send_email();
		}
	}

	return new LP_Email_Assignment_Evaluated_Admin();
}
