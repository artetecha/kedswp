<?php
/**
 * Class LP_Settings_Submitted_Assignment_Emails
 *
 * @author   ThimPress
 * @package  LearnPress/Assignments/Classes/Email
 * @version  3.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Settings_Submitted_Assignment_Emails' ) ) {
	/**
	 * Class LP_Settings_Submitted_Assignment_Emails
	 */
	class LP_Settings_Submitted_Assignment_Emails extends LP_Settings_Emails_Group {

		/**
		 * LP_Settings_Submitted_Assignment_Emails constructor.
		 */
		public function __construct() {
			$this->group_id = 'submit-assignment-emails';
			$this->items    = array(
				'submitted-assignment-admin',
				'submitted-assignment-instructor',
				'submitted-assignment-user',
			);

			parent::__construct();
		}

		/**
		 * @return string
		 */
		public function __toString() {
			return esc_html__( 'Submitted Assignment', 'learnpress-assignments' );
		}
	}
}

return new LP_Settings_Submitted_Assignment_Emails();
