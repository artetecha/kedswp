<?php
/**
 * Gradebook access and course-scope checks.
 *
 * @package LearnPress\Gradebook
 * @since   4.1.1
 */

namespace LearnPress\Gradebook;

use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\UserItemsFilter;
use LP_CO_Instructor_DB;
use LP_Course_DB;
use LP_Course_Filter;
use LP_Settings;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Central permission and scope resolver for Gradebook data.
 *
 * All checks apply to the current logged-in user.
 */
class Permission {
	const OPTION_ALLOW_INSTRUCTORS = 'gradebook_allow_instructors';

	/**
	 * Per-request course scope cache, keyed by user ID.
	 *
	 * @var array<int, array<int>|null>
	 */
	private static $allowed_course_ids = array();

	/**
	 * Check whether instructor/co-instructor access is enabled.
	 *
	 * @return bool
	 */
	public static function instructors_enabled(): bool {
		return 'yes' === LP_Settings::get_option( self::OPTION_ALLOW_INSTRUCTORS, 'no' );
	}

	/**
	 * Check whether the current user can view Gradebook at all.
	 *
	 * @return bool
	 */
	public static function can_view_gradebook(): bool {
		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return false;
		}

		if ( user_can( $user_id, 'administrator' ) ) {
			return true;
		}

		return self::instructors_enabled() && user_can( $user_id, LP_TEACHER_ROLE );
	}

	/**
	 * Check whether the current user is a scoped, non-admin Gradebook viewer.
	 *
	 * @return bool
	 */
	public static function is_scoped_user(): bool {
		return self::can_view_gradebook() && ! user_can( get_current_user_id(), 'administrator' );
	}

	/**
	 * Resolve course IDs the current user may view.
	 *
	 * `null` means unrestricted admin access; an empty array means no course scope.
	 *
	 * @return array<int>|null
	 */
	public static function get_allowed_course_ids(): ?array {
		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return array();
		}

		if ( user_can( $user_id, 'administrator' ) ) {
			return null;
		}

		if ( ! self::can_view_gradebook() ) {
			return array();
		}

		if ( array_key_exists( $user_id, self::$allowed_course_ids ) ) {
			return self::$allowed_course_ids[ $user_id ];
		}

		if ( class_exists( 'LP_CO_Instructor_DB' ) ) {
			$course_ids = LP_CO_Instructor_DB::getInstance()->get_post_of_instructor( $user_id );
		} else {
			$course_ids = array();

			try {
				$filter                  = new LP_Course_Filter();
				$filter->only_fields     = array( 'ID' );
				$filter->post_author     = $user_id;
				$filter->limit           = -1;
				$filter->run_query_count = false;

				$courses    = LP_Course_DB::getInstance()->get_courses( $filter );
				$course_ids = wp_list_pluck( (array) $courses, 'ID' );
			} catch ( Throwable $e ) {
				error_log( __METHOD__ . ': ' . $e->getMessage() );
			}
		}

		$course_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $course_ids ) ) ) );

		self::$allowed_course_ids[ $user_id ] = $course_ids;

		return $course_ids;
	}

	/**
	 * Check whether the current user can view a specific course.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return bool
	 */
	public static function can_view_course( int $course_id ): bool {
		$course_id = absint( $course_id );
		if ( $course_id < 1 ) {
			return false;
		}

		$allowed = self::get_allowed_course_ids();
		if ( null === $allowed ) {
			return true;
		}

		return in_array( $course_id, $allowed, true );
	}

	/**
	 * Check whether the current user can view a student's course rows.
	 *
	 * @param int $student_id Student user ID.
	 *
	 * @return bool
	 */
	public static function can_view_student( int $student_id ): bool {
		$student_id = absint( $student_id );
		if ( $student_id < 1 ) {
			return false;
		}

		$allowed = self::get_allowed_course_ids();
		if ( null === $allowed ) {
			return true;
		}

		if ( empty( $allowed ) ) {
			return false;
		}

		try {
			$filter                  = new UserItemsFilter();
			$filter->only_fields     = array( UserItemsFilter::COL_USER_ITEM_ID );
			$filter->user_id         = $student_id;
			$filter->item_type       = LP_COURSE_CPT;
			$filter->item_ids        = $allowed;
			$filter->limit           = 1;
			$filter->run_query_count = false;

			$rows = UserItemsDB::getInstance()->get_user_items( $filter );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );

			return false;
		}

		return ! empty( $rows );
	}

	/**
	 * Build a safe SQL IN value list from trusted scoped IDs.
	 *
	 * @param array<int>|null $allowed Allowed course IDs.
	 *
	 * @return string
	 */
	public static function get_scope_sql_in( ?array $allowed ): string {
		if ( ! is_array( $allowed ) ) {
			return '';
		}

		$ids = array_values( array_unique( array_filter( array_map( 'absint', $allowed ) ) ) );

		return implode( ',', $ids );
	}
}
