<?php
/**
 * Gradebook database queries built on the LearnPress ORM / Filter layer.
 *
 * Modern replacement for {@see \LP_Gradebook_Database}. All internal Gradebook
 * plugin code uses this class. The legacy global `LP_Gradebook_Database` is
 * retained (frozen) for backward compatibility with existing customizations.
 *
 * Every query routes through `UserItemsFilter` + `UserItemsDB::get_user_items()`
 * (the `execute()` pipeline). The only parameterized-SQL helper is the
 * "latest user/course row" subquery, injected via `$filter->where[]` — the same
 * pattern LearnPress core uses inside its own DB classes.
 *
 * @package LearnPress\Gradebook
 * @since   4.1.2
 */

namespace LearnPress\Gradebook\Databases;

use LearnPress\Databases\DataBase;
use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Models\UserItems\UserItemModel;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use stdClass;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class GradebookDB
 *
 * @since 4.1.2
 */
class GradebookDB extends DataBase {
	/**
	 * @var GradebookDB|null
	 */
	private static $_instance = null;

	protected function __construct() {
		parent::__construct();
	}

	/**
	 * @return GradebookDB
	 */
	public static function getInstance(): GradebookDB {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get student IDs that have course user_items in the given courses.
	 *
	 * @param array $course_ids Course IDs.
	 *
	 * @return array<int>
	 */
	public function get_student_ids_by_course_ids( array $course_ids ): array {
		$course_ids = array_values( array_filter( array_map( 'absint', $course_ids ) ) );
		if ( empty( $course_ids ) ) {
			return array();
		}

		try {
			$filter                  = new UserItemsFilter();
			$filter->item_type       = LP_COURSE_CPT;
			$filter->item_ids        = $course_ids;
			$filter->only_fields     = array( 'DISTINCT ui.user_id AS user_id' );
			$filter->where[]         = 'AND ui.user_id > 0';
			$filter->limit           = -1;
			$filter->run_query_count = false;

			$rows = (array) UserItemsDB::getInstance()->get_user_items( $filter );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );

			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'absint', wp_list_pluck( $rows, 'user_id' ) ) ) ) );
	}

	/**
	 * Get the in-progress item rows for a student's latest attempt at a course.
	 *
	 * @param int    $course_id Course ID.
	 * @param int    $user_id   User ID.
	 * @param string $status    Optional user_item status filter.
	 * @param string $item_type Optional user_item item_type filter.
	 * @param array  $item_ids  Item IDs to constrain to (required, non-empty).
	 *
	 * @return array Array of row objects (or empty array).
	 */
	public function get_item_id_in_user_items( $course_id, $user_id, $status = '', $item_type = '', $item_ids = array() ) {
		$item_ids = array_values( array_filter( array_map( 'absint', (array) $item_ids ) ) );
		if ( empty( $item_ids ) ) {
			return array();
		}

		$parent_id = $this->get_latest_user_course_id( absint( $course_id ), absint( $user_id ) );
		if ( ! $parent_id ) {
			return array();
		}

		try {
			$filter            = new UserItemsFilter();
			$filter->user_id   = absint( $user_id );
			$filter->parent_id = $parent_id;
			$filter->item_ids  = $item_ids;
			if ( ! empty( $status ) ) {
				$filter->status = $status;
			}
			if ( ! empty( $item_type ) ) {
				$filter->item_type = $item_type;
			}
			$filter->only_fields     = array( 'item_id', 'start_time', 'end_time', 'graduation', 'status' );
			$filter->order_by        = 'start_time';
			$filter->order           = 'DESC';
			$filter->limit           = - 1;
			$filter->run_query_count = false;

			return (array) UserItemsDB::getInstance()->get_user_items( $filter );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );

			return array();
		}
	}

	/**
	 * List students enrolled/finished in a course (or count them when $calc).
	 *
	 * @param int    $course_id  Course ID.
	 * @param int    $limit      Page size.
	 * @param int    $page       Page number.
	 * @param string $graduation Optional graduation filter.
	 * @param string $username   Optional display-name/email search.
	 * @param bool   $calc        When true, return the total row count (int).
	 *
	 * @return array|int|false Rows (ARRAY_A), count (int), or false on missing course.
	 */
	public function get_all_students( $course_id, $limit, $page, $graduation = '', $username = '', $calc = false ) {
		if ( ! $course_id ) {
			return false;
		}

		$course_id = absint( $course_id );
		$limit     = min( 100, max( 1, absint( $limit ) ) );
		$page      = max( 1, absint( $page ) );

		try {
			$db                = UserItemsDB::getInstance();
			$filter            = new UserItemsFilter();
			$filter->item_id   = $course_id;
			$filter->item_type = LP_COURSE_CPT;
			$filter->statues   = array( LP_COURSE_ENROLLED, LP_COURSE_FINISHED );
			if ( ! empty( $graduation ) ) {
				$filter->graduation = $graduation;
			}
			$filter->join[] = "INNER JOIN {$db->tb_users} AS u ON u.ID = ui.user_id";
			if ( ! empty( $username ) ) {
				$like            = '%' . $db->wpdb->esc_like( $username ) . '%';
				$filter->where[] = $db->wpdb->prepare( 'AND ( u.display_name LIKE %s OR u.user_email LIKE %s )', $like, $like );
			}
			$filter->where[] = 'AND ui.user_item_id IN (' . $this->get_latest_user_courses_query( 0, false, $course_id ) . ')';

			if ( $calc ) {
				$filter->only_fields = array( 'ui.user_item_id' );
				$filter->field_count = UserItemsFilter::COL_USER_ITEM_ID;
				$filter->query_count = true;

				return (int) $db->get_user_items( $filter );
			}

			$filter->only_fields     = array( 'u.user_nicename', 'u.display_name', 'u.user_email', 'ui.user_id', 'ui.start_time', 'ui.graduation' );
			$filter->order_by        = 'ui.start_time';
			$filter->order           = 'DESC';
			$filter->limit           = $limit;
			$filter->page            = $page;
			$filter->run_query_count = false;

			$rows = (array) $db->get_user_items( $filter );

			return array_map( static fn( $row ) => (array) $row, $rows );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );

			return $calc ? 0 : array();
		}
	}

	/**
	 * All enrolled/finished students of a course for CSV export (no paging).
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return array|false Rows (ARRAY_A) or false on missing course.
	 */
	public function lp_gradebook_get_all_students_for_export( int $course_id ) {
		if ( ! $course_id ) {
			return false;
		}

		try {
			$db                      = UserItemsDB::getInstance();
			$filter                  = new UserItemsFilter();
			$filter->item_id         = $course_id;
			$filter->item_type       = LP_COURSE_CPT;
			$filter->statues         = array( LP_COURSE_ENROLLED, LP_COURSE_FINISHED );
			$filter->join[]          = "INNER JOIN {$db->tb_users} AS u ON u.ID = ui.user_id";
			$filter->where[]         = 'AND ui.user_item_id IN (' . $this->get_latest_user_courses_query( 0, false, $course_id ) . ')';
			$filter->only_fields     = array( 'ui.user_id', 'u.user_nicename', 'u.user_email', 'ui.start_time', 'ui.graduation' );
			$filter->order_by        = 'ui.start_time';
			$filter->order           = 'DESC';
			$filter->limit           = - 1;
			$filter->run_query_count = false;

			$rows = (array) $db->get_user_items( $filter );

			return array_map( static fn( $row ) => (array) $row, $rows );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );

			return array();
		}
	}

	/**
	 * Paginated enrolled/finished students of a course for chunked export.
	 *
	 * @param int $course_id Course ID.
	 * @param int $limit     Page size.
	 * @param int $page      Page number.
	 *
	 * @return array|false Rows (ARRAY_A) or false on missing course.
	 */
	public function get_all_item_students_for_export( int $course_id, int $limit = 5, int $page = 1 ) {
		if ( ! $course_id ) {
			return false;
		}

		$limit = min( 100, max( 1, absint( $limit ) ) );
		$page  = max( 1, absint( $page ) );

		try {
			$db                      = UserItemsDB::getInstance();
			$filter                  = new UserItemsFilter();
			$filter->item_id         = $course_id;
			$filter->item_type       = LP_COURSE_CPT;
			$filter->statues         = array( LP_COURSE_ENROLLED, LP_COURSE_FINISHED );
			$filter->join[]          = "INNER JOIN {$db->tb_users} AS u ON u.ID = ui.user_id";
			$filter->where[]         = 'AND ui.user_item_id IN (' . $this->get_latest_user_courses_query( 0, false, $course_id ) . ')';
			$filter->only_fields     = array( 'ui.user_id', 'u.user_nicename', 'u.user_email', 'ui.start_time', 'ui.end_time', 'ui.graduation' );
			$filter->order_by        = 'ui.start_time';
			$filter->order           = 'DESC';
			$filter->limit           = $limit;
			$filter->page            = $page;
			$filter->run_query_count = false;

			$rows = (array) $db->get_user_items( $filter );

			return array_map( static fn( $row ) => (array) $row, $rows );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );

			return array();
		}
	}

	/**
	 * Total enrolled/finished students of a course (export count).
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return int
	 */
	public function get_all_export_count( int $course_id ) {
		try {
			$db                  = UserItemsDB::getInstance();
			$filter              = new UserItemsFilter();
			$filter->item_id     = $course_id;
			$filter->item_type   = LP_COURSE_CPT;
			$filter->statues     = array( LP_COURSE_ENROLLED, LP_COURSE_FINISHED );
			$filter->join[]      = "INNER JOIN {$db->tb_users} AS u ON u.ID = ui.user_id";
			$filter->where[]     = 'AND ui.user_item_id IN (' . $this->get_latest_user_courses_query( 0, false, $course_id ) . ')';
			$filter->only_fields = array( 'ui.user_item_id' );
			$filter->field_count = UserItemsFilter::COL_USER_ITEM_ID;
			$filter->query_count = true;

			return (int) $db->get_user_items( $filter );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );

			return 0;
		}
	}

	/**
	 * Item status rows for a student's latest attempt at a course.
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 *
	 * @return array Rows (ARRAY_A).
	 */
	public function get_all_status_item( int $course_id, int $user_id ) {
		$parent_id = $this->get_latest_user_course_id( $course_id, $user_id );
		if ( ! $parent_id ) {
			return array();
		}

		try {
			$filter                  = new UserItemsFilter();
			$filter->parent_id       = $parent_id;
			$filter->user_id         = $user_id;
			$filter->only_fields     = array( 'status', 'item_id' );
			$filter->limit           = - 1;
			$filter->run_query_count = false;

			$rows = (array) UserItemsDB::getInstance()->get_user_items( $filter );

			return array_map( static fn( $row ) => (array) $row, $rows );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );

			return array();
		}
	}

	/**
	 * Get data about students to render in the enrolled/finished line chart.
	 *
	 * @param int   $course_id Course ID.
	 * @param mixed $from      Reference time (mysql string or timestamp).
	 * @param mixed $by        Bucket: days|months|years.
	 * @param float $time_ago  Number of buckets back from $from.
	 *
	 * @return array{labels:array,datasets:array}
	 */
	public function lp_gradebook_get_chart_students( int $course_id, $from = null, $by = null, $time_ago = 0 ) {
		$labels   = array();
		$datasets = array();

		if ( is_null( $from ) ) {
			$from = current_time( 'mysql', true );
		}

		if ( is_null( $by ) ) {
			$by = 'days';
		}

		switch ( $by ) {
			case 'days':
				$date_format = 'M d';
				break;
			case 'months':
				$date_format = 'M Y';
				break;
			case 'years':
				$date_format = 'Y';
				break;
		}

		$results = array(
			'enrolled' => array(),
			'finished' => array(),
		);

		$from_time = is_numeric( $from ) ? $from : strtotime( $from );

		switch ( $by ) {
			case 'days':
				$date_format = 'M d Y';
				$_from       = - $time_ago + 1;
				$_from       = gmdate( 'Y-m-d', strtotime( "{$_from} {$by}", $from_time ) );
				$_to         = gmdate( 'Y-m-d', $from_time );
				$_sql_format = '%Y-%m-%d';
				$_key_format = 'Y-m-d';
				break;

			case 'months':
				$date_format = 'M Y';
				$_from       = - $time_ago + 1;
				$_from       = gmdate( 'Y-m-01', strtotime( "{$_from} {$by}", $from_time ) );
				$days        = gmdate( 't', mktime( 0, 0, 0, gmdate( 'm', $from_time ), 1, gmdate( 'Y', $from_time ) ) );
				$_to         = gmdate( 'Y-m-' . $days, $from_time );
				$_sql_format = '%Y-%m';
				$_key_format = 'Y-m';
				break;

			case 'years':
				$date_format = 'Y';
				$_from       = - $time_ago + 1;
				$_from       = gmdate( 'Y-01-01', strtotime( "{$_from} {$by}", $from_time ) );
				$days        = gmdate( 't', mktime( 0, 0, 0, gmdate( 'm', $from_time ), 1, gmdate( 'Y', $from_time ) ) );
				$_to         = gmdate( 'Y-12-' . $days, $from_time );
				$_sql_format = '%Y';
				$_key_format = 'Y';
				break;
		}

		$result_enroll = $this->query_chart_counts( $course_id, 'enrolled', $_sql_format, $_from, $_to );
		if ( $result_enroll ) {
			foreach ( $result_enroll as $v ) {
				$results['enrolled'][ $v->d ] = $v;
			}
		}

		$result_finished = $this->query_chart_counts( $course_id, 'finished', $_sql_format, $_from, $_to );
		if ( $result_finished ) {
			foreach ( $result_finished as $v ) {
				$results['finished'][ $v->d ] = $v;
			}
		}

		for ( $i = - $time_ago + 1; $i <= 0; $i++ ) {
			$date     = strtotime( "$i $by", $from_time );
			$labels[] = gmdate( $date_format, $date );
			$key      = gmdate( $_key_format, $date );

			$enrolled = ! empty( $results['enrolled'][ $key ] ) ? $results['enrolled'][ $key ]->c : 0;
			$finished = ! empty( $results['finished'][ $key ] ) ? $results['finished'][ $key ]->c : 0;

			$datasets[0]['data'][] = $enrolled;
			$datasets[1]['data'][] = $finished;
		}

		$dataset_params = array(
			array(
				'color1' => 'rgba(54, 162, 235, %s)',
				'color2' => '#FFF',
				'label'  => esc_html__( 'Enrolled', 'learnpress-gradebook' ),
			),
			array(
				'color1' => 'rgba(255, 205, 86, %s)',
				'color2' => '#FFF',
				'label'  => esc_html__( 'Finished', 'learnpress-gradebook' ),
			),
		);

		foreach ( $dataset_params as $k => $v ) {
			$datasets[ $k ]['backgroundColor'] = sprintf( $v['color1'], '0.2' );
			$datasets[ $k ]['borderColor']     = sprintf( $v['color1'], '1' );
			$datasets[ $k ]['label']           = $v['label'];
		}

		return array(
			'labels'   => $labels,
			'datasets' => $datasets,
		);
	}

	/**
	 * Grouped count of a course's user_items by formatted start_time, for a status.
	 *
	 * Replaces the legacy `HAVING d BETWEEN ...` with an equivalent `start_time`
	 * range `WHERE` (see plan decision D9).
	 *
	 * @param int    $course_id  Course ID.
	 * @param string $status     user_item status (enrolled|finished).
	 * @param string $sql_format DATE_FORMAT pattern (constant; not user input).
	 * @param string $from       Lower date bound (Y-m-d / Y-m-01 / Y-01-01).
	 * @param string $to         Upper date bound.
	 *
	 * @return array Row objects with ->c (count) and ->d (formatted date).
	 */
	private function query_chart_counts( int $course_id, string $status, string $sql_format, string $from, string $to ): array {
		try {
			$db                      = UserItemsDB::getInstance();
			$filter                  = new UserItemsFilter();
			$filter->item_id         = $course_id;
			$filter->status          = $status;
			$filter->join[]          = "INNER JOIN {$db->tb_users} AS users ON users.ID = ui.user_id";
			$filter->where[]         = 'AND ui.user_item_id IN (' . $this->get_latest_user_courses_query( 0, false, $course_id ) . ')';
			$filter->where[]         = $db->wpdb->prepare( 'AND ui.start_time BETWEEN %s AND %s', $from . ' 00:00:00', $to . ' 23:59:59' );
			$filter->only_fields     = array(
				'COUNT(ui.user_id) AS c',
				$db->wpdb->prepare( 'DATE_FORMAT(ui.start_time, %s) AS d', $sql_format ),
			);
			$filter->group_by        = 'd';
			$filter->order_by        = 'd';
			$filter->order           = 'ASC';
			$filter->run_query_count = false;

			return (array) $db->get_user_items( $filter );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );

			return array();
		}
	}

	/**
	 * Enrolled vs finished totals for the course pie chart.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return array{labels:array,datasets:array}
	 */
	public function lp_gradebook_get_pie_chart_students( int $course_id ) {
		$labels   = array( esc_html__( 'Enrolled', 'learnpress-gradebook' ), esc_html__( 'Finished', 'learnpress-gradebook' ) );
		$datasets = array();

		$result_enrolled = '0';
		$result_finished = '0';

		try {
			$db                      = UserItemsDB::getInstance();
			$filter                  = new UserItemsFilter();
			$filter->item_id         = $course_id;
			$filter->join[]          = "INNER JOIN {$db->tb_users} AS u ON u.ID = ui.user_id";
			$filter->where[]         = 'AND ui.user_item_id IN (' . $this->get_latest_user_courses_query( 0, false, $course_id ) . ')';
			$filter->only_fields     = array(
				"SUM(ui.status = 'enrolled') AS enrolled",
				"SUM(ui.status = 'finished') AS finished",
			);
			$filter->run_query_count = false;

			$rows = (array) $db->get_user_items( $filter );
			if ( ! empty( $rows ) ) {
				// Cast to string to mirror the legacy get_var() return type (and NULL → '0').
				$result_enrolled = (string) (int) $rows[0]->enrolled;
				$result_finished = (string) (int) $rows[0]->finished;
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		$datasets['data']            = array( $result_enrolled, $result_finished );
		$datasets['backgroundColor'] = array(
			'rgba(255, 205, 86, 0.2)',
			'rgba(54, 162, 235, 0.2)',
		);
		$datasets['borderColor']     = array(
			'rgba(255, 205, 86, 1)',
			'rgba(54, 162, 235, 1)',
		);

		return array(
			'labels'   => $labels,
			'datasets' => $datasets,
		);
	}

	/**
	 * All items of a student's latest course attempt for CSV export.
	 *
	 * @param int $student_id Student user ID.
	 * @param int $course_id  Course ID.
	 *
	 * @return array|false Rows (ARRAY_A) or false on missing args.
	 */
	public function lp_gradebook_get_all_items_for_export( int $student_id, int $course_id ) {
		if ( ! $student_id || ! $course_id ) {
			return false;
		}

		$parent_id = $this->get_latest_user_course_id( $course_id, $student_id );
		if ( ! $parent_id ) {
			return array();
		}

		try {
			$db                      = UserItemsDB::getInstance();
			$filter                  = new UserItemsFilter();
			$filter->user_id         = $student_id;
			$filter->parent_id       = $parent_id;
			$filter->join[]          = "INNER JOIN {$db->tb_posts} AS p ON p.ID = ui.item_id";
			$filter->only_fields     = array( 'DISTINCT p.post_title', 'ui.item_type', 'ui.start_time', 'ui.end_time', 'ui.graduation', 'ui.status' );
			$filter->limit           = - 1;
			$filter->run_query_count = false;

			$rows = (array) $db->get_user_items( $filter );

			return array_map( static fn( $row ) => (array) $row, $rows );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );

			return array();
		}
	}

	/**
	 * All quiz-question results for a student (optionally scoped to a course attempt).
	 *
	 * @param int $quiz_id    Quiz ID.
	 * @param int $student_id Student user ID.
	 * @param int $course_id  Optional course scope.
	 *
	 * @return array|false Row objects with ->result, or false on missing args.
	 */
	public function lp_gradebook_get_all_questions_result( int $quiz_id, int $student_id, int $course_id = 0 ) {
		if ( ! $quiz_id || ! $student_id ) {
			return false;
		}

		$parent_id = 0;
		if ( $course_id ) {
			$parent_id = $this->get_latest_user_course_id( $course_id, $student_id );
			if ( ! $parent_id ) {
				return array();
			}
		}

		try {
			$db              = UserItemsDB::getInstance();
			$filter          = new UserItemsFilter();
			$filter->item_id = $quiz_id;
			$filter->user_id = $student_id;
			if ( $parent_id ) {
				$filter->parent_id = $parent_id;
			}
			$filter->join[]          = "INNER JOIN {$db->tb_lp_user_item_results} AS uir ON uir.user_item_id = ui.user_item_id";
			$filter->only_fields     = array( 'uir.result' );
			$filter->limit           = - 1;
			$filter->run_query_count = false;

			return (array) $db->get_user_items( $filter );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );

			return array();
		}
	}

	/**
	 * Latest quiz result + end time for a student (optionally scoped to a course).
	 *
	 * @param int $quiz_id    Quiz ID.
	 * @param int $student_id Student user ID.
	 * @param int $limit      Number of latest rows to consider.
	 * @param int $course_id  Optional course scope.
	 *
	 * @return array{result?:mixed,'end-time'?:mixed}|false
	 */
	public function lp_gradebook_get_qizz_result( int $quiz_id, int $student_id, $limit = 1, int $course_id = 0 ) {
		if ( ! $quiz_id || ! $student_id ) {
			return false;
		}

		$output = array();
		$limit  = max( 1, absint( $limit ) );

		$parent_id = 0;
		if ( $course_id ) {
			$parent_id = $this->get_latest_user_course_id( $course_id, $student_id );
			if ( ! $parent_id ) {
				return $output;
			}
		}

		try {
			$db              = UserItemsDB::getInstance();
			$filter          = new UserItemsFilter();
			$filter->item_id = $quiz_id;
			$filter->user_id = $student_id;
			if ( $parent_id ) {
				$filter->parent_id = $parent_id;
			}
			$filter->join[]          = "INNER JOIN {$db->tb_lp_user_item_results} AS uir ON uir.user_item_id = ui.user_item_id";
			$filter->only_fields     = array( 'uir.result', 'ui.end_time' );
			$filter->order_by        = 'uir.id';
			$filter->order           = 'DESC';
			$filter->limit           = $limit;
			$filter->run_query_count = false;

			$result = (array) $db->get_user_items( $filter );
			if ( ! empty( $result ) ) {
				$output['result']   = $result[0]->result;
				$output['end-time'] = $result[0]->end_time;
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $output;
	}

	/**
	 * Per-student course graduation breakdown for the profile pie chart.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return false|stdClass
	 */
	public function get_student_course_pie_chart_data( $user_id ) {
		$filter            = new LP_User_Items_Filter();
		$filter->user_id   = absint( $user_id );
		$filter->item_type = LP_COURSE_CPT;
		$data              = LP_User_Items_DB::getInstance()->count_status_by_items( $filter );

		if ( empty( $data ) ) {
			return false;
		}

		$output             = new stdClass();
		$output->user_id    = absint( $user_id );
		$output->inprogress = (int) ( $data->{LP_COURSE_GRADUATION_IN_PROGRESS} ?? 0 );
		$output->passed     = (int) ( $data->{LP_COURSE_GRADUATION_PASSED} ?? 0 );
		$output->failed     = (int) ( $data->{LP_COURSE_GRADUATION_FAILED} ?? 0 );

		return $output;
	}

	/**
	 * Per-student course counts grouped by time, for the profile bar chart.
	 *
	 * @param int    $user_id     User ID.
	 * @param string $filter_type last7days|last30days|last12months.
	 *
	 * @return array
	 */
	public function get_student_course_bar_chart_data( $user_id = 0, $filter_type = 'last7days' ) {
		$chart_data              = array();
		$filter                  = new LP_User_Items_Filter();
		$lpuidb                  = LP_User_Items_DB::getInstance();
		$filter->only_fields     = array(
			'SUM(ui.graduation = "in-progress" ) AS inprogress',
			'SUM(ui.graduation = "passed" ) AS passed',
			'SUM(ui.graduation = "failed" ) AS failed',
			'COUNT(ui.user_item_id) AS total_courses',
		);
		$filter->join[]          = "INNER JOIN $lpuidb->tb_users AS u ON ui.user_id = u.ID";
		$filter->item_type       = 'lp_course';
		$filter->user_id         = $user_id;
		$filter->run_query_count = false;
		$filter->where[]         = 'AND ui.user_item_id IN (' . $this->get_latest_user_courses_query( absint( $user_id ), true ) . ')';
		switch ( $filter_type ) {
			case 'last7days':
				$filter->only_fields[] = 'CAST(ui.start_time AS DATE) as uidate';
				$filter->where[]       = 'AND ui.start_time >= DATE_ADD(CURDATE(), INTERVAL -6 DAY)';
				$filter->group_by      = 'uidate';
				$filter->order_by      = 'uidate';
				$filter->order         = 'DESC';
				$query_data            = $lpuidb->get_user_items( $filter );
				$chart_data            = $this->map_barchart_day_data( $query_data, 6 );
				break;
			case 'last30days':
				$filter->only_fields[] = 'CAST(ui.start_time AS DATE) as uidate';
				$filter->where[]       = 'AND ui.start_time >= DATE_ADD(CURDATE(), INTERVAL -29 DAY)';
				$filter->group_by      = 'uidate';
				$filter->order_by      = 'uidate';
				$filter->order         = 'DESC';
				$query_data            = $lpuidb->get_user_items( $filter );
				$chart_data            = $this->map_barchart_day_data( $query_data, 29 );
				break;
			case 'last12months':
				$filter->only_fields[] = "DATE_FORMAT( ui.start_time , '%m-%Y') as uidate";
				$filter->where[]       = 'AND EXTRACT(YEAR_MONTH FROM ui.start_time) >= EXTRACT(YEAR_MONTH FROM DATE_ADD(CURDATE(), INTERVAL -12 MONTH))';
				$filter->group_by      = 'uidate';
				$filter->order_by      = 'uidate';
				$filter->order         = 'DESC';
				$query_data            = $lpuidb->get_user_items( $filter );
				$chart_data            = $this->map_barchart_month_data( $query_data, 11 );
				break;
			default:
				break;
		}

		return $chart_data;
	}

	/**
	 * Build a zero-filled day series and overlay query results.
	 *
	 * @param array $query_data Grouped rows keyed by uidate.
	 * @param int   $days       Number of days back.
	 *
	 * @return array
	 */
	public function map_barchart_day_data( array $query_data, $days = 6 ) {
		$data = array();
		for ( $i = $days; $i >= 0; $i-- ) {
			$date            = wp_date( 'Y-m-d', strtotime( -$i . 'days' ) );
			$row             = new stdClass();
			$row->uidate     = $date;
			$row->inprogress = 0;
			$row->passed     = 0;
			$row->failed     = 0;
			$data[ $date ]   = $row;
		}
		if ( ! empty( $query_data ) ) {
			foreach ( $query_data as $row ) {
				$data[ $row->uidate ] = $row;
			}
		}

		return $data;
	}

	/**
	 * Build a zero-filled month series and overlay query results.
	 *
	 * @param array $query_data Grouped rows keyed by uidate.
	 * @param int   $months     Number of months back.
	 *
	 * @return array
	 */
	public function map_barchart_month_data( array $query_data, $months = 11 ) {
		$data = array();
		for ( $i = $months; $i >= 0; $i-- ) {
			$date            = wp_date( 'm-Y', strtotime( -$i . 'months' ) );
			$row             = new stdClass();
			$row->uidate     = $date;
			$row->inprogress = 0;
			$row->passed     = 0;
			$row->failed     = 0;
			$data[ $date ]   = $row;
		}
		if ( ! empty( $query_data ) ) {
			foreach ( $query_data as $row ) {
				$data[ $row->uidate ] = $row;
			}
		}

		return $data;
	}

	/**
	 * Build a subquery selecting the latest course row for each user/course pair.
	 *
	 * @param int  $user_id        Optional user scope.
	 * @param bool $exclude_cancel Whether cancelled latest rows should be excluded.
	 * @param int  $course_id      Optional course scope.
	 *
	 * @return string
	 */
	private function get_latest_user_courses_query( int $user_id = 0, bool $exclude_cancel = false, int $course_id = 0 ): string {
		$where = $this->wpdb->prepare( 'WHERE latest_ui.item_type = %s', LP_COURSE_CPT );
		if ( $user_id ) {
			$where .= $this->wpdb->prepare( ' AND latest_ui.user_id = %d', $user_id );
		}
		if ( $course_id ) {
			$where .= $this->wpdb->prepare( ' AND latest_ui.item_id = %d', $course_id );
		}
		if ( $exclude_cancel ) {
			$where .= $this->wpdb->prepare( ' AND latest_ui.status != %s', UserItemModel::STATUS_CANCEL );
		}

		return "SELECT MAX(latest_ui.user_item_id)
			FROM {$this->tb_lp_user_items} AS latest_ui
			{$where}
			GROUP BY latest_ui.user_id, latest_ui.item_id";
	}

	/**
	 * Get the latest user-course row ID.
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 *
	 * @return int
	 */
	private function get_latest_user_course_id( int $course_id, int $user_id ): int {
		try {
			$filter                  = new UserItemsFilter();
			$filter->item_id         = $course_id;
			$filter->user_id         = $user_id;
			$filter->item_type       = LP_COURSE_CPT;
			$filter->only_fields     = array( 'user_item_id' );
			$filter->order_by        = 'user_item_id';
			$filter->order           = 'DESC';
			$filter->limit           = 1;
			$filter->run_query_count = false;

			$rows = (array) UserItemsDB::getInstance()->get_user_items( $filter );

			return ! empty( $rows ) ? (int) $rows[0]->user_item_id : 0;
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );

			return 0;
		}
	}
}
