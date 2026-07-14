<?php
/**
 * Class LP_Assignment_CURD
 *
 * @author  ThimPress
 * @package LearnPress/Assignments/Classes/CURD
 * @since   3.0.0
 */

use LearnPress\Models\UserItems\UserItemModel;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Assignment_CURD' ) ) {

	/**
	 * Class LP_Assignment_CURD
	 */
	class LP_Assignment_CURD extends LP_Object_Data_CURD implements LP_Interface_CURD {

		/**
		 * Create assignment, with default meta.
		 *
		 * @param $args
		 *
		 * @return int|WP_Error
		 */
		public function create( &$args ) {
			$args = wp_parse_args(
				$args,
				array(
					'id'      => '',
					'status'  => 'publish',
					'title'   => __( 'New Assignment', 'learnpress-assignments' ),
					'content' => '',
					'author'  => learn_press_get_current_user_id(),
				)
			);

			$assignment_id = wp_insert_post(
				array(
					'ID'           => $args['id'],
					'post_type'    => LP_ASSIGNMENT_CPT,
					'post_status'  => $args['status'],
					'post_title'   => $args['title'],
					'post_content' => $args['content'],
					'post_author'  => $args['author'],
				)
			);

			if ( $assignment_id ) {
				// add default meta for new assignment
				$default_meta = LP_Assignment::get_default_meta();

				if ( is_array( $default_meta ) ) {
					foreach ( $default_meta as $key => $value ) {
						update_post_meta( $assignment_id, '_lp_' . $key, $value );
					}
				}
			}

			return $assignment_id;
		}

		/**
		 * @param object $assignment
		 */
		public function update( &$assignment ) {
			// TODO: Implement update() method.
		}

		/**
		 * Delete assignment.
		 *
		 * @param object $assignment_id
		 *
		 * @since 3.0.0
		 *
		 */
		public function delete( &$assignment_id ) {
			// course curd
			$curd = new LP_Course_CURD();

			// allow hook
			do_action( 'learn-press/before-delete-assignment', $assignment_id );

			// remove assignment from course items
			$curd->remove_item( $assignment_id );
		}

		/**
		 * Duplicate assignment.
		 *
		 * @param $assignment_id
		 * @param array $args
		 *
		 * @return mixed|WP_Error
		 * @since 3.0.0
		 *
		 */
		public function duplicate( &$assignment_id, $args = array() ) {
			if ( ! $assignment_id ) {
				return new WP_Error( __( '<p>Op! ID not found</p>', 'learnpress-assignments' ) );
			}

			if ( get_post_type( $assignment_id ) != LP_ASSIGNMENT_CPT ) {
				return new WP_Error( __( '<p>Op! The assignment does not exist</p>', 'learnpress-assignments' ) );
			}

			// ensure that user can create assignment
			if ( ! current_user_can( 'edit_posts' ) ) {
				return new WP_Error( __( '<p>Sorry! You don\'t have permission to duplicate this assignment</p>', 'learnpress-assignments' ) );
			}

			// duplicate assignment
			$new_assignment_id = learn_press_duplicate_post( $assignment_id, $args );

			if ( ! $new_assignment_id || is_wp_error( $new_assignment_id ) ) {
				return new WP_Error( __( '<p>Sorry! Failed to duplicate assignment!</p>', 'learnpress-assignments' ) );
			}

			do_action( 'learn-press/item/after-duplicate', $assignment_id, $new_assignment_id, $args );

			return $new_assignment_id;
		}

		/**
		 * Load assignment data.
		 *
		 * @param LP_Assignment $assignment
		 *
		 * @return object
		 * @throws Exception
		 * @since 3.0.0
		 *
		 */
		public function load( &$assignment ) {
			// assignment id
			$id = $assignment->get_id();

			if ( ! $id || get_post_type( $id ) !== LP_ASSIGNMENT_CPT ) {
				throw new Exception( sprintf( __( 'Invalid assignment with ID "%d".', 'learnpress-assignments' ), $id ) );
			}
			/*$assignment->set_data_via_methods(
				array(
					'retake_count'   => get_post_meta( $assignment->get_id(), '_lp_retake_count', true ),
					'mark'           => get_post_meta( $assignment->get_id(), '_lp_mark', true ),
					'introduction'   => get_post_meta( $assignment->get_id(), '_lp_introduction', true ),
					'file_extension' => get_post_meta( $assignment->get_id(), '_lp_file_extension', true ),
					'files_amount'   => get_post_meta( $assignment->get_id(), '_lp_upload_files', true ),
					'passing_grade'  => get_post_meta( $assignment->get_id(), '_lp_passing_grade', true ),
				)
			);*/

			$assignment->set_retake_count( get_post_meta( $assignment->get_id(), '_lp_retake_count', true ) );
			$assignment->set_mark( get_post_meta( $assignment->get_id(), '_lp_mark', true ) );
			$assignment->set_introduction( get_post_meta( $assignment->get_id(), '_lp_introduction', true ) );
			$assignment->set_file_extension( get_post_meta( $assignment->get_id(), '_lp_file_extension', true ) );
			$assignment->set_files_amount( get_post_meta( $assignment->get_id(), '_lp_upload_files', true ) );
			$assignment->set_passing_grade( get_post_meta( $assignment->get_id(), '_lp_passing_grade', true ) );

			return $assignment;
		}

		/**
		 * @param $assignment
		 *
		 * @return array|null|object
		 */
		public function get_students( $assignment ) {

			global $wpdb;

			$assignment = LP_Assignment::get_assignment( $assignment );
			$query      = $wpdb->prepare(
				"
				SELECT DISTINCT student.* FROM {$wpdb->users} AS student
				INNER JOIN {$wpdb->prefix}learnpress_user_items AS user_item
				ON user_item.user_id = student.ID
				WHERE user_item.item_id = %d AND user_item.item_type = %s AND user_item.status IN (%s, %s)
			",
				$assignment->get_id(),
				LP_ASSIGNMENT_CPT,
				'completed',
				'evaluated'
			);

			$students = $wpdb->get_results( $query, ARRAY_A );

			return $students;
		}

		/**
		 * @param int $user_id
		 * @param string $filter_status
		 *
		 * @return LP_Query_List_Table
		 * @since 4.1.2
		 * @version 1.0.1
		 */
		public function query_profile_assignments( int $user_id = 0, string $filter_status = 'all' ) {
			$assignments = [
				'total' => 0,
				'items' => [],
				'paged' => $args['paged'] ?? 1,
				'limit' => 10,
				'pages' => 0,
			];

			try {
				$lp_user_item_db = LP_User_Items_DB::getInstance();
				$total_rows      = 0;

				$filter            = new LP_User_Items_Filter();
				$filter->user_id   = $user_id;
				$filter->item_type = LP_ASSIGNMENT_CPT;
				$filter->ref_type  = LP_COURSE_CPT;
				$filter->page      = $assignments['paged'];
				$filter->limit     = $assignments['limit'];
				$filter->join[]    = $lp_user_item_db->wpdb->prepare(
					"INNER JOIN {$lp_user_item_db->tb_posts} AS pas ON pas.ID = item_id AND pas.post_type = %s",
					LP_ASSIGNMENT_CPT
				);

				switch ( $filter_status ) {
					case 'completed':
					case 'evaluated':
						$filter->status = $filter_status;
						break;
					case LP_COURSE_GRADUATION_PASSED:
					case LP_COURSE_GRADUATION_FAILED:
						$filter->graduation = $filter_status;
						break;
					case 'default':
						break;
				}

				$userAssignments      = $lp_user_item_db->get_user_items( $filter, $total_rows );
				$assignments['total'] = $total_rows;
				$assignments['items'] = $userAssignments;
				$assignments['pages'] = LP_Database::get_total_pages( $assignments['limit'], $total_rows );
			} catch ( Throwable $e ) {
				error_log( __METHOD__ . ': ' . $e->getMessage() );
			}

			return new LP_Query_List_Table( $assignments );
		}

		/**
		 * @param $profile LP_Profile
		 *
		 * @return mixed
		 */
		public function get_assignments_filters( $profile ) {
			$url      = $profile->get_tab_link( 'assignments' );
			$defaults = array(
				'all'       => sprintf( '<a href="%s">%s</a>', esc_url( $url ), __( 'All', 'learnpress-assignments' ) ),
				'completed' => sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'filter-status', 'completed', $url ) ), __( 'Submitted', 'learnpress-assignments' ) ),
				'evaluated' => sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'filter-status', 'evaluated', $url ) ), __( 'Evaluated', 'learnpress-assignments' ) ),
				'passed'    => sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'filter-status', 'passed', $url ) ), __( 'Passed', 'learnpress-assignments' ) ),
				'failed'    => sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'filter-status', 'failed', $url ) ), __( 'Failed', 'learnpress-assignments' ) ),
			);

			return apply_filters( 'learn-press/profile/assignments-filters', $defaults );
		}
	}

}
