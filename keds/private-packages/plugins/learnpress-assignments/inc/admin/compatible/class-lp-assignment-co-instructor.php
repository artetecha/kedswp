<?php
/**
 * Class LP_Assignment_Co_Instructor.
 *
 * @author  ThimPress
 * @package LearnPress/Assignments/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Assignment_Co_Instructor' ) ) {

	/**
	 * Class LP_Assignment_Co_Instructor
	 */
	class LP_Assignment_Co_Instructor {

		/**
		 * LP_Assignment_Co_Instructor constructor.
		 */
		public function __construct() {

			if ( ! class_exists( 'LP_Co_Instructor_Preload' ) ) {
				return;
			}

			add_filter( 'learn-press/co-instructor/item-types', array( $this, 'add_item_types' ) );
			//add_action( 'learn-press/co-instructor/pre-get-posts', array( $this, 'pre_get_posts' ), 10, 4 );

			//add_filter( 'learn-press/assignments/allow-access', array( $this, 'allow_access_admin' ), 10, 2 );

			//add_filter( 'learn-press/co-instructor/edit-admin-bar', array( $this, 'edit_admin_bar' ), 10, 2 );
		}

		/**
		 * @param $can_edit
		 * @param $item_id
		 *
		 * @return bool
		 * @deprecated 4.1.2
		 */
		/*public function edit_admin_bar( $can_edit, $item_id ) {
			$type = get_post_type( $item_id );
			if ( $type == LP_ASSIGNMENT_CPT ) {
				if ( in_array( $item_id, $this->co_instructor_valid_assignments() ) ) {
					return false;
				}
			}

			return $can_edit;
		}*/

		/**
		 * Allow co-instructor access assignment admin students, evaluate page.
		 *
		 * @param $return
		 * @param $assignment_id
		 *
		 * @return bool
		 * @deprecated 4.1.2
		 */
		/*public function allow_access_admin( $return, $assignment_id ) {
			if ( in_array( $assignment_id, $this->co_instructor_valid_assignments() ) ) {
				return true;
			}

			return $return;
		}*/

		/**
		 * @return array|bool|mixed
		 * @deprecated 4.1.2
		 */
		/*public function co_instructor_valid_assignments() {

			$load    = LP_Addon_Co_Instructor::instance();
			$courses = $load->get_available_courses();

			return $this->get_available_assignments( $courses );
		}*/

		/**
		 * Update pre get assignment.
		 *
		 * @param $types
		 *
		 * @return array
		 */
		public function add_item_types( $types ) {
			$types[] = LP_ASSIGNMENT_CPT;

			return $types;
		}

		/**
		 * @param $load LP_Addon_Co_Instructor
		 * @param $courses
		 * @param $post_type
		 * @param $query WP_Query
		 * @deprecated 4.1.2
		 */
		/*public function pre_get_posts( $load, $courses, $post_type, $query ) {
			if ( $post_type == LP_ASSIGNMENT_CPT ) {
				$assignments = $this->get_available_assignments( $courses );
				if ( count( $assignments ) == 0 ) {
					$query->set( 'post_type', 'lp_empty' );
				} else {
					$query->set( 'post_type', $post_type );
					$query->set( 'post__in', $assignments );
				}
				add_filter( 'views_edit-lp_assignment', array( $load, 'restrict_co_instructor_items' ), 20 );

				return;
			}
		}*/

		/**
		 * @param $courses
		 *
		 * @return array|bool|mixed
		 * @deprecated 4.1.2
		 */
		/*public function get_available_assignments( $courses ) {
			$user_id = get_current_user_id();

			// Cache available assignments for instructor
			$assignments = wp_cache_get( 'user-' . $user_id, 'co-instructor-assignments' );
			if ( false === $assignments ) {
				global $wpdb;

				$query = $wpdb->prepare(
					"
					SELECT ID FROM $wpdb->posts
					WHERE post_type = %s
					AND post_author = %d
					",
					'lp_assignment',
					get_current_user_id()
				);

				$assignments = $wpdb->get_col( $query );
				if ( $courses ) {
					foreach ( $courses as $course_id ) {
						$temp        = $this->get_available_assignment_from_course( $course_id );
						$assignments = array_unique( array_merge( $assignments, $temp ) );
					}
				}

				wp_cache_set( 'user-' . $user_id, $assignments, 'co-instructor-assignments' );
			}

			return $assignments;
		}*/

		/**
		 * @param null $course_id
		 *
		 * @return array
		 * @deprecated 4.2.7.4
		 */
		/*public function get_available_assignment_from_course( $course_id = null ) {
			if ( empty( $course_id ) ) {
				return array();
			}

			$course      = learn_press_get_course( $course_id );
			$assignments = $course->get_items( LP_ASSIGNMENT_CPT );

			$available = array();

			if ( $assignments ) {
				foreach ( $assignments as $assignment_id ) {
					$available[ $assignment_id ] = absint( $assignment_id );
				}
			}

			return $available;
		}*/
	}
}

new LP_Assignment_Co_Instructor();
