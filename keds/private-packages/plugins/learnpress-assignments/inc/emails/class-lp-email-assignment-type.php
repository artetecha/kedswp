<?php
/**
 * Class LP_Email_Assignment_Type
 *
 * @author   tungnx
 * @version  1.0.0
 * @since 4.0.1
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Email_Assignment_Type' ) ) {
	class LP_Email_Assignment_Type extends LP_Email {
		/**
		 * @var LP_Course
		 */
		public $course;
		/**
		 * Student
		 *
		 * @var LP_User
		 */
		public $student;
		/**
		 * Instructor
		 *
		 * @var LP_User
		 */
		public $instructor;
		/**
		 * @var LP_Assignment
		 */
		public $assignment;

		/**
		 * LP_Email_Assignment_Evaluated_Admin constructor.
		 */
		public function __construct() {
			parent::__construct();

			$variable_on_email_support = apply_filters(
				'lp/assignment/email/submitted',
				array(
					'{{assignment_id}}',
					'{{assignment_name}}',
					'{{assignment_url}}',
					'{{course_id}}',
					'{{course_name}}',
					'{{course_url}}',
					'{{user_id}}',
					'{{user_name}}',
					'{{user_email}}',
					'{{instructor_name}}',
				)
			);

			$this->support_variables = array_merge( $this->support_variables, $variable_on_email_support );
		}

		/**
		 * Check email enable option
		 * Check param valid
		 * Return Order
		 *
		 * @param array $params
		 *
		 * @return bool
		 */
		protected function check_params( array $params ): bool {
			if ( ! $this->enable ) {
				return false;
			}

			if ( count( $params ) < 2 ) {
				return false;
			}

			$user_id       = $params[0] ?? 0;
			$assignment_id = $params[1] ?? 0;

			$this->student = learn_press_get_user( $user_id );

			if ( ! $this->student ) {
				return false;
			}

			$this->assignment = learn_press_get_assignment( $assignment_id );
			if ( ! $this->assignment ) {
				return false;
			}

			$this->assignment->get_permalink();

			$courses      = learn_press_get_item_courses( $assignment_id );
			$this->course = learn_press_get_course( $courses[0]->ID );

			if ( ! $this->course ) {
				return false;
			}

			$this->instructor = learn_press_get_user( get_post_field( 'post_author', $this->course->get_id() ) );

			if ( ! $this->instructor ) {
				return false;
			}

			return true;
		}

		/**
		 * Set data content to send mails
		 */
		public function set_data_content() {
			$this->variables = apply_filters(
				'lp/assignment/email/submitted/variables-mapper',
				array(
					'{{assignment_id}}'   => $this->assignment->get_id(),
					'{{assignment_name}}' => $this->assignment->get_title(),
					'{{assignment_url}}'  => $this->course->get_item_link( $this->assignment->get_id() ),
					'{{course_id}}'       => $this->course->get_id(),
					'{{course_name}}'     => $this->course->get_title(),
					'{{course_url}}'      => $this->course->get_permalink(),
					'{{user_id}}'         => $this->student->get_id(),
					'{{user_name}}'       => $this->student->get_display_name(),
					'{{user_email}}'      => $this->student->get_email(),
					'{{instructor_name}}' => $this->instructor->get_display_name(),
				)
			);

			$variables_common = $this->get_common_variables( $this->email_format );
			$this->variables  = array_merge( $this->variables, $variables_common );
		}
	}
}
