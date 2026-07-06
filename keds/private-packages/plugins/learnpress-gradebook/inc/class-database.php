<?php
/**
 * Use for call database.
 *
 * @package LearnPress/Gradebook
 * @author Nhamdv
 *
 * @deprecated 4.1.2 Retained for backward compatibility with existing customizations only.
 *             Gradebook plugin code now uses {@see \LearnPress\Gradebook\Databases\GradebookDB}.
 *             This class is frozen; new fixes/features land in GradebookDB.
 */
class LP_Gradebook_Database extends LP_Database {
	private static $_instance = null;

	protected function __construct() {
		parent::__construct();
	}

	/**
	 * Nhamdv
	 *
	 * @param [type] $course_id
	 * @param [type] $user_id
	 * @param string $status
	 * @param string $item_type
	 */
	public function get_item_id_in_user_items( $course_id, $user_id, $status = '', $item_type = '', $item_ids = array() ) {
		$item_ids = array_values( array_filter( array_map( 'absint', $item_ids ) ) );
		if ( empty( $item_ids ) ) {
			return array();
		}

		$parent_id = $this->get_latest_user_course_id( absint( $course_id ), absint( $user_id ) );
		if ( ! $parent_id ) {
			return array();
		}
		$where = $this->wpdb->prepare( 'user_id=%d AND parent_id=%d', absint( $user_id ), $parent_id );
		if ( ! empty( $status ) ) {
			$where .= $this->wpdb->prepare( ' AND status=%s', $status );
		}

		if ( ! empty( $item_type ) ) {
			$where .= $this->wpdb->prepare( ' AND item_type=%s', $item_type );
		}
		// compare item in course with item in-progess
		$where         .= ' AND item_id IN (' . implode( ',', $item_ids ) . ')';
				$where .= ' ORDER BY start_time DESC ';

		$query = "SELECT item_id, start_time, end_time, graduation, status FROM {$this->tb_lp_user_items} WHERE $where";

		return $this->wpdb->get_results( $query );
	}

	/**
	 * query get all students enroll by course ID
	 *
	 * @param int    $courseID
	 * @param int    $limit
	 * @param int    $page
	 * @param float  $time_ago
	 * @param string $graduation
	 * @param int    $average
	 * @param string $username
	 */
	public function get_all_students( $course_id, $limit, $page, $graduation = '', $username = '', $calc = false ) {
		if ( ! $course_id ) {
			return false;
		}

		$course_id = absint( $course_id );
		$limit     = min( 100, max( 1, absint( $limit ) ) );
		$page      = max( 1, absint( $page ) );
		$where     = $this->wpdb->prepare(
			'ui.item_id=%d AND ui.item_type=%s AND ui.status IN (%s,%s)',
			$course_id,
			LP_COURSE_CPT,
			LP_COURSE_ENROLLED,
			LP_COURSE_FINISHED
		);

		if ( ! empty( $graduation ) ) {
			$where .= $this->wpdb->prepare( ' AND ui.graduation=%s', $graduation );
		}

		if ( ! empty( $username ) ) {
			$like   = '%' . $this->wpdb->esc_like( $username ) . '%';
			$where .= $this->wpdb->prepare( ' AND ( u.display_name LIKE %s OR u.user_email LIKE %s )', $like, $like );
		}

		if ( $calc ) {
			$query = "SELECT COUNT(ui.user_item_id)
				FROM {$this->tb_lp_user_items} AS ui
				INNER JOIN {$this->tb_users} AS u ON u.ID = ui.user_id
				WHERE {$where}
				AND ui.user_item_id IN ({$this->get_latest_user_courses_query( 0, false, $course_id )})";

			return (int) $this->wpdb->get_var( $query );
		}

		$offset    = ( $page - 1 ) * $limit;
		$sql_limit = $this->wpdb->prepare( 'LIMIT %d, %d', $offset, $limit );
		$query     = "SELECT u.user_nicename, u.display_name, u.user_email, ui.user_id, ui.start_time, ui.graduation
			FROM {$this->tb_users} AS u
			INNER JOIN {$this->tb_lp_user_items} AS ui ON u.ID = ui.user_id
			WHERE {$where}
			AND ui.user_item_id IN ({$this->get_latest_user_courses_query( 0, false, $course_id )})
			ORDER BY ui.start_time DESC
			{$sql_limit}";

		return $this->wpdb->get_results( $query, ARRAY_A );
	}
	/**
	 * query get all students enroll by course ID for export
	 *
	 * @param int $courseID
	 */
	public function lp_gradebook_get_all_students_for_export( int $course_id ) {
		if ( ! $course_id ) {
			return false;
		}

		$query = $this->wpdb->prepare(
			"SELECT ui.user_id, u.user_nicename, u.user_email, ui.start_time, ui.graduation
			FROM {$this->tb_lp_user_items} AS ui
			INNER JOIN {$this->tb_users} AS u ON u.ID = ui.user_id
			WHERE ui.item_id = %d
			AND ui.item_type = %s
			AND ui.status IN (%s,%s)
			AND ui.user_item_id IN ({$this->get_latest_user_courses_query( 0, false, $course_id )})
			ORDER BY ui.start_time DESC",
			$course_id,
			LP_COURSE_CPT,
			LP_COURSE_ENROLLED,
			LP_COURSE_FINISHED
		);
		return $this->wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * query get all students enroll by course ID for export
	 *
	 * @param int $courseID
	 */
	public function get_all_item_students_for_export( int $course_id, int $limit = 5, int $page = 1 ) {
		if ( ! $course_id ) {
			return false;
		}

		$limit     = min( 100, max( 1, absint( $limit ) ) );
		$page      = max( 1, absint( $page ) );
		$offset    = ( $page - 1 ) * $limit;
		$sql_limit = $this->wpdb->prepare( 'LIMIT %d, %d', $offset, $limit );

		$query = $this->wpdb->prepare(
			"SELECT ui.user_id, u.user_nicename, u.user_email, ui.start_time, ui.end_time, ui.graduation
			FROM {$this->tb_lp_user_items} AS ui
			INNER JOIN {$this->tb_users} AS u ON u.ID = ui.user_id
			WHERE ui.item_id = %d
			AND ui.item_type = %s
			AND ui.status IN (%s,%s)
			AND ui.user_item_id IN ({$this->get_latest_user_courses_query( 0, false, $course_id )})
			ORDER BY ui.start_time DESC
			{$sql_limit}",
			$course_id,
			LP_COURSE_CPT,
			LP_COURSE_ENROLLED,
			LP_COURSE_FINISHED
		);
		return $this->wpdb->get_results( $query, ARRAY_A );
	}

	public function get_all_export_count( int $course_id ) {
		$query = $this->wpdb->prepare(
			"SELECT COUNT(ui.user_item_id)
			FROM {$this->tb_lp_user_items} AS ui
			INNER JOIN {$this->tb_users} AS u ON u.ID = ui.user_id
			WHERE ui.item_id = %d
			AND ui.item_type = %s
			AND ui.status IN (%s,%s)
			AND ui.user_item_id IN ({$this->get_latest_user_courses_query( 0, false, $course_id )})",
			$course_id,
			LP_COURSE_CPT,
			LP_COURSE_ENROLLED,
			LP_COURSE_FINISHED
		);

		return (int) $this->wpdb->get_var( $query );
	}
	public function get_all_status_item( int $course_id, int $user_id ) {
		$parent_id = $this->get_latest_user_course_id( $course_id, $user_id );
		if ( ! $parent_id ) {
			return array();
		}

		$query = $this->wpdb->prepare(
			"SELECT status, item_id FROM {$this->tb_lp_user_items}
			WHERE parent_id = %d AND user_id = %d",
			$parent_id,
			$user_id
		);
		return $this->wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Get data about students to render in chart
	 *
	 * @param int   $courseID
	 * @param null  $from
	 * @param null  $by
	 * @param float $time_ago
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

		$data_format = '';

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

		$result_enroll = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT count(u.user_id) as c, DATE_FORMAT(u.start_time, %s) as d
				FROM {$this->tb_lp_user_items} u
				INNER JOIN {$this->tb_users} users ON users.ID = u.user_id
				WHERE 1 AND u.item_id = %d AND u.status = 'enrolled'
				AND u.user_item_id IN ({$this->get_latest_user_courses_query( 0, false, $course_id )})
				GROUP BY d
				HAVING d BETWEEN %s AND %s
				ORDER BY d ASC",
				$_sql_format,
				$course_id,
				$_from,
				$_to
			)
		);

		if ( $result_enroll ) {
			foreach ( $result_enroll as $k => $v ) {
				$results['enrolled'][ $v->d ] = $v;
			}
		}

		$result_finished = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT count(u.user_id) as c, DATE_FORMAT(u.start_time, %s) as d
				FROM {$this->tb_lp_user_items} u
				INNER JOIN {$this->tb_users} users ON users.ID = u.user_id
				WHERE 1 AND u.item_id = %d AND u.status = 'finished'
				AND u.user_item_id IN ({$this->get_latest_user_courses_query( 0, false, $course_id )})
				GROUP BY d
				HAVING d BETWEEN %s AND %s
				ORDER BY d ASC",
				$_sql_format,
				$course_id,
				$_from,
				$_to
			)
		);

		if ( $result_finished ) {
			foreach ( $result_finished as $k => $v ) {
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
	 * Get data about students to render in chart
	 *
	 * @param int $courseID
	 */
	public function lp_gradebook_get_pie_chart_students( int $course_id ) {
		$labels   = array( esc_html__( 'Enrolled', 'learnpress-gradebook' ), esc_html__( 'Finished', 'learnpress-gradebook' ) );
		$datasets = array();

		$result_enrolled = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT count(ui.user_id)
				FROM {$this->tb_lp_user_items} AS ui
				INNER JOIN {$this->tb_users} AS u ON u.ID = ui.user_id
				WHERE ui.item_id=%d AND ui.status=%s
				AND ui.user_item_id IN ({$this->get_latest_user_courses_query( 0, false, $course_id )})",
				$course_id,
				'enrolled'
			)
		);

		$result_finished = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT count(ui.user_id)
				FROM {$this->tb_lp_user_items} AS ui
				INNER JOIN {$this->tb_users} AS u ON u.ID = ui.user_id
				WHERE ui.item_id=%d AND ui.status=%s
				AND ui.user_item_id IN ({$this->get_latest_user_courses_query( 0, false, $course_id )})",
				$course_id,
				'finished'
			)
		);

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
	 * query get all items by student ID and course ID for export
	 *
	 * @param int $student_id
	 * @param int $course_id
	 */
	public function lp_gradebook_get_all_items_for_export( int $student_id, int $course_id ) {
		if ( ! $student_id || ! $course_id ) {
			return false;
		}

		$parent_id = $this->get_latest_user_course_id( $course_id, $student_id );
		if ( ! $parent_id ) {
			return array();
		}

		$query = $this->wpdb->prepare(
			"SELECT DISTINCT post_title, item_type, start_time, end_time, graduation, status
			FROM {$this->tb_lp_user_items} AS ui
			INNER JOIN {$this->tb_posts} AS p ON p.ID = ui.item_id
			WHERE ui.user_id = %d AND ui.parent_id = %d",
			$student_id,
			$parent_id
		);
		return $this->wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * query get all result question
	 *
	 * @param int $quiz_id
	 * @param int $student_id
	 */
	public function lp_gradebook_get_all_questions_result( int $quiz_id, int $student_id, int $course_id = 0 ) {
		if ( ! $quiz_id || ! $student_id ) {
			return false;
		}

		$where_parent = '';
		if ( $course_id ) {
			$parent_id = $this->get_latest_user_course_id( $course_id, $student_id );
			if ( ! $parent_id ) {
				return array();
			}
			$where_parent = $this->wpdb->prepare( ' AND ui.parent_id = %d', $parent_id );
		}

		$query  = $this->wpdb->prepare(
			"SELECT uir.result FROM {$this->tb_lp_user_item_results} AS uir
			INNER JOIN {$this->tb_lp_user_items} AS ui ON uir.user_item_id = ui.user_item_id
			WHERE ui.item_id = %d
			AND ui.user_id = %d
			{$where_parent}",
			$quiz_id,
			$student_id
		);
		$result = $this->wpdb->get_results( $query );

		return $result;
	}

	/**
	 * query get all result qizz
	 *
	 * @param int $quiz_id
	 * @param int $student_id
	 * @param int $limit
	 */
	public function lp_gradebook_get_qizz_result( int $quiz_id, int $student_id, $limit = 1, int $course_id = 0 ) {
		if ( ! $quiz_id || ! $student_id ) {
			return false;
		}

		$output = array();
		$limit  = max( 1, absint( $limit ) );

		$where_parent = '';
		if ( $course_id ) {
			$parent_id = $this->get_latest_user_course_id( $course_id, $student_id );
			if ( ! $parent_id ) {
				return $output;
			}
			$where_parent = $this->wpdb->prepare( ' AND ui.parent_id = %d', $parent_id );
		}

		$query = $this->wpdb->prepare(
			"SELECT uir.result, ui.end_time FROM {$this->tb_lp_user_item_results} AS uir
		    INNER JOIN {$this->tb_lp_user_items} AS ui ON uir.user_item_id = ui.user_item_id
			WHERE ui.item_id = %d
			AND ui.user_id = %d
			{$where_parent}
			ORDER BY uir.id DESC LIMIT %d",
			$quiz_id,
			$student_id,
			$limit
		);

		$result = $this->wpdb->get_results( $query );

		if ( ! empty( $result ) ) {
			$output['result']   = $result[0]->result;
			$output['end-time'] = $result[0]->end_time;
		}

		return $output;
	}

	/**
	 * get student total courses count by status/graduation
	 *
	 * @param  integer $user_id
	 * @return false|object
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
	 * Get count student courses group by times
	 *
	 * @param  integer $user_id User ID
	 * @param  string  $filter_type
	 * @return array $chart_data
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
			$where .= $this->wpdb->prepare( ' AND latest_ui.status != %s', \LearnPress\Models\UserItems\UserItemModel::STATUS_CANCEL );
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
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT user_item_id
				FROM {$this->tb_lp_user_items}
				WHERE item_id = %d
				AND user_id = %d
				AND item_type = %s
				ORDER BY user_item_id DESC
				LIMIT 1",
				$course_id,
				$user_id,
				LP_COURSE_CPT
			)
		);
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

LP_Gradebook_Database::instance();
