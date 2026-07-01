<?php
/**
 * LearnPress Co-Instructor Hook Template
 *
 * @since 4.0.4
 * @version 1.0.0
 */

namespace LP_Addon_Co_Instructor;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LP_Addon_Co_Instructor;
use LP_Co_Instructor_Preload;
use LP_Email_Enrolled_Course_Co_Instructor;
use LP_Email_Finished_Course_Co_Instructor;
use LP_Emails;
use LP_Model_User_Can_View_Course_Item;
use WP_Screen;
use LP_CO_Instructor_DB;
use LP_Helper;
use LP_Course_DB;

class Hook {
	use Singleton;

	/**
	 * @var LP_Addon_Co_Instructor $addon
	 */
	public $addon;

	public function init() {
		$this->addon = LP_Co_Instructor_Preload::$addon;

		// Set role for co-instructor
		$teacher_role = get_role( LP_TEACHER_ROLE );
		if ( $teacher_role ) {
			$teacher_role->add_cap( 'edit_others_' . LP_COURSE_CPT . 's' );
		}

		// Remove capability of instructor when deactivate plugin
		register_deactivation_hook( $this->addon->plugin_file, array( $this, 'remove_teacher_capabilities' ) );

		// Check co-instructor can view/edit post
		add_action( 'current_screen', array( $this, 'check_co_instructor_can_view_edit_post' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'check_co_instructor_can_save_post' ), 10, 4 );

		// Load all items of instructor created for assign to course
		add_filter(
			'learn-press/modal-search-items/args',
			array( $this, 'load_all_items_instructor_on_course' ),
			10,
			1
		);

		// Register emails
		$this->emails_setting();
		// Email group
		add_filter( 'learn-press/emails/finished-course', [ $this, 'add_emails_group_finished_course' ] );
		add_filter( 'learn-press/emails/enrolled-course', [ $this, 'add_emails_group_enrolled_course' ] );

		add_filter( 'learnpress/course/can-view-content', [ $this, 'can_view_course_content' ], 10, 3 );
		add_filter( 'lp/single-instructor/courses/query/filter', array( $this, 'add_co_instructor_param' ) );
		add_filter( 'lp/api/profile/courses/own/filter', array( $this, 'add_co_instructor_param' ) );
		add_filter( 'lp/course/query/filter', array( $this, 'query_co_instructor_courses' ) );
		add_filter( 'lp/profile/instructor/statistic', array( $this, 'co_instructor_statistic' ), 10, 2 );
		add_filter( 'learn-press/assignments/allow-access', array( $this, 'allow_access_assignment' ), 10, 2 );
	}

	/**
	 * Plugin install, add teacher capacities.
	 *
	 * @since 3.0.0
	 */
	public function install() {
		$teacher_role = get_role( LP_TEACHER_ROLE );
		// Set capability for co-instructor
		if ( $teacher_role ) {
			// Can edit course of another instructor
			$teacher_role->add_cap( 'edit_others_' . LP_COURSE_CPT );
		}
	}

	/**
	 * Remove teacher capacities.
	 *
	 * @since 3.0.0
	 */
	public function remove_teacher_capabilities() {
		/*** Remove cab of instructor can edit post not yourself */
		$teacher_role = get_role( LP_TEACHER_ROLE );

		if ( $teacher_role ) {
			$teacher_role->remove_cap( 'edit_others_lp_courses' );
		}
		/*** End */
	}

	/**
	 * Check instructor can edit post of another instructor
	 *
	 * @param WP_Screen $current_screen Current WP_Screen object.
	 *
	 * @return void
	 */
	public function check_co_instructor_can_view_edit_post( $current_screen ) {
		$screen_check_arr = apply_filters(
			'learn-press/co-instructor/screen-check',
			[ LP_COURSE_CPT ],
			$current_screen
		);

		if ( ! $current_screen || ! in_array( $current_screen->id, $screen_check_arr )
			|| ! isset( $_GET['post'] ) ) {
			return;
		}

		$user_id = get_current_user_id();
		$post_id = absint( $_GET['post'] );
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		if ( $user_id == $post->post_author || current_user_can( ADMIN_ROLE ) ) {
			return;
		}

		// Check post type
		switch ( $post->post_type ) {
			case LP_COURSE_CPT:
				$course = CourseModel::find( $post_id, true );
				if ( ! $course ) {
					return;
				}

				$can_edit = $this->addon->is_co_in_course( $user_id, $course );
				if ( ! $can_edit ) {
					wp_die( 'Sorry, you are not allowed to edit this post.' );
				}
				break;
			case LP_LESSON_CPT:
			case LP_QUESTION_CPT:
			case LP_QUIZ_CPT:
			default:
				do_action( 'learn-press/co-instructor/check-can-edit-post', $post, $user_id );
				break;
		}
	}

	/**
	 * Check instructor can save post of another instructor
	 *
	 * @param $data
	 * @param $postarr
	 * @param $unsanitized_postarr
	 * @param $update
	 *
	 * @return mixed
	 */
	public function check_co_instructor_can_save_post( $data, $postarr, $unsanitized_postarr, $update ) {
		if ( ! $update ) {
			return $data;
		}

		$user_id = get_current_user_id();
		$post_id = absint( $postarr['ID'] ?? 0 );
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return $data;
		}

		if ( $user_id == $post->post_author || current_user_can( ADMIN_ROLE ) ) {
			return $data;
		}

		// Check post type
		switch ( $post->post_type ) {
			case LP_COURSE_CPT:
				$course = CourseModel::find( $post_id, true );
				if ( ! $course ) {
					return $data;
				}

				$can_edit = $this->addon->is_co_in_course( $user_id, $course );
				if ( ! $can_edit ) {
					wp_die( 'Sorry, you are not allowed to edit this post.' );
				}
				break;
			case LP_LESSON_CPT:
			case LP_QUESTION_CPT:
			case LP_QUIZ_CPT:
			default:
				do_action( 'learn-press/co-instructor/check-can-save-post', $post, $user_id );
				break;
		}

		return $data;
	}

	/**
	 * Load all items of instructor created for assign to course
	 * @move from file LP_Co_Instructor_Preload
	 *
	 * @param $args_query
	 *
	 * @return mixed
	 */
	public function load_all_items_instructor_on_course( $args_query ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return $args_query;
		}

		if ( ! user_can( $user_id, LP_TEACHER_ROLE ) ) {
			return $args_query;
		}

		if ( isset( $args_query['author'] ) ) {
			// Logic 1: only load items of instructor yourself
			unset( $args_query['author'] );
			$args_query['author__in'] = array( $user_id );

			// Logic 2: load items of instructor and co-instructors
		}

		return $args_query;
	}

	/**
	 * Add email settings
	 */
	public function emails_setting() {
		if ( ! class_exists( 'LP_Emails' ) ) {
			return;
		}

		$emails = LP_Emails::instance()->emails;

		$emails[ LP_Email_Finished_Course_Co_Instructor::class ] = include_once LP_ADDON_CO_INSTRUCTOR_PATH . '/inc/emails/class-lp-co-instructor-email-finished-course.php';
		$emails[ LP_Email_Enrolled_Course_Co_Instructor::class ] = include_once LP_ADDON_CO_INSTRUCTOR_PATH . '/inc/emails/class-lp-co-instructor-email-enrolled-course.php';

		LP_Emails::instance()->emails = $emails;
	}

	/**
	 * @param array $group
	 *
	 * @return array
	 */
	public function add_emails_group_finished_course( array $group ): array {
		$group[] = 'finished-course-co-instructor';

		return $group;
	}

	/**
	 * @param array $group
	 *
	 * @return array
	 */
	public function add_emails_group_enrolled_course( array $group ): array {
		$group[] = 'enrolled-course-co-instructor';

		return $group;
	}

	/**
	 * Check can view content of course
	 *
	 * @param LP_Model_User_Can_View_Course_Item $can_view_item
	 * @param int $user_id
	 * @param \LP_Course $course
	 *
	 * @return LP_Model_User_Can_View_Course_Item
	 */
	public function can_view_course_content( $can_view_item, $user_id, $course ) {
		$courseModel     = CourseModel::find( $course->get_id(), true );
		$is_co_in_course = $this->addon->is_co_in_course( $user_id, $courseModel );
		if ( ! $is_co_in_course ) {
			return $can_view_item;
		}

		$can_view_item->flag = true;
		$can_view_item->key  = 'co-instructor';

		return $can_view_item;
	}
	/**
	 * add param to query courses in Single Instructor page
	 * @param [type] $filter [description]
	 */
	public function add_co_instructor_param( $filter ) {
		$filter->filter_extra = 'extra_co_instructor';
		return $filter;
	}
	/**
	 * Add query String to query courses in Single Instructor page
	 * @param  [type] $filter [description]
	 * @return [type]         [description]
	 */
	public function query_co_instructor_courses( $filter ) {
		if ( ! empty( $filter->post_author ) && ! empty( $filter->filter_extra ) && $filter->filter_extra === 'extra_co_instructor' ) {
			$lpcoi_db       = LP_CO_Instructor_DB::getInstance();
			$tb_postmeta    = $lpcoi_db->tb_postmeta;
			$filter->join[] = "INNER JOIN $tb_postmeta AS pm_co_instructor ON p.ID = pm_co_instructor.post_id";
			if ( is_array( $filter->post_status ) ) {
				$status_in_str   = LP_Helper::db_format_array( $filter->post_status, '%s' );
				$condition_arr   = array_merge(
					array( '_lp_co_teacher', $filter->post_author, LP_COURSE_CPT ),
					$filter->post_status
				);
				$filter->where[] = $lpcoi_db->wpdb->prepare( "OR (( pm_co_instructor.meta_key = %s AND pm_co_instructor.meta_value = %d AND p.post_type = %s  AND p.post_status IN ($status_in_str) )) ", $condition_arr );
			} else {
				$filter->where[] = $lpcoi_db->wpdb->prepare( 'OR (( pm_co_instructor.meta_key = %s AND pm_co_instructor.meta_value = %d AND p.post_type = %s )) ', '_lp_co_teacher', $filter->post_author, LP_COURSE_CPT );
			}
		}
		return $filter;
	}
	/**
	 * hook to display Co Instructor Statistic
	 * @param  array $statistic
	 * @param  UserModel $user
	 * @return array $statistic
	 */
	public function co_instructor_statistic( $statistic, $user ) {
		if ( $user && $user->get_id() ) {
			$user_id       = $user->get_id();
			$lpcoi_db      = LP_CO_Instructor_DB::getInstance();
			$total_courses = $lpcoi_db->get_post_of_instructor( $user_id );
			if ( ! empty( $total_courses ) ) {
				$published_course = 0;
				$pending_course   = 0;
				$total_student    = 0;
				foreach ( $total_courses as $course_id ) {
					$course = CourseModel::find( intval( $course_id ), true );
					if ( $course ) {
						$total_student += $course->count_students();
						if ( $course->get_status() === 'publish' ) {
							++$published_course;
						} elseif ( $course->get_status() === 'pending' ) {
							++$pending_course;
						}
					}
				}
				$statistic['published_course'] = $published_course;
				$statistic['pending_course']   = $pending_course;
				$statistic['total_student']    = $total_student;
				$statistic['total_course']     = count( $total_courses );
			}
		}
		return $statistic;
	}

	/**
	 * Allow co-instructor view assignment of another author assign course has co-in.
	 *
	 * @param boolean $allowed
	 * @param integer $assignment_id
	 *
	 * @return boolean
	 * @throws Exception
	 */
	public function allow_access_assignment( $allowed, $assignment_id ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return $allowed;
		}

		if ( ! current_user_can( LP_TEACHER_ROLE ) ) {
			return $allowed;
		}

		$current_course_id = LP_Course_DB::getInstance()->get_course_by_item_id( intval( $assignment_id ) );
		if ( ! $current_course_id ) {
			return $allowed;
		}

		$lpcoi_db      = LP_CO_Instructor_DB::getInstance();
		$total_courses = $lpcoi_db->get_post_of_instructor( $user_id );
		if ( ! empty( $total_courses ) && in_array( $current_course_id, $total_courses ) ) {
			$allowed = true;
		}

		return $allowed;
	}
}
