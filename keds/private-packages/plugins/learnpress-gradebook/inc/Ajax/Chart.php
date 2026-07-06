<?php

namespace LearnPress\Gradebook\Ajax;

use Exception;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Gradebook\Permission;
use LP_Datetime;
use LP_Helper;
use LP_Request;
use LP_REST_Response;
use stdClass;
use Throwable;

/**
 * Class ExportCSV
 *
 * @since 4.0.8
 * @version 1.0.0
 */

class Chart extends AbstractAjax {
	/**
	 * Export student courses to CSV
	 *
	 * Call on file js: assets/src/js/admin/student-detail.js
	 *
	 * @return void
	 */
	public function lp_gradebook_data_chart_student() {
		$response = new LP_REST_Response();

		try {
			if ( ! Permission::can_view_gradebook() ) {
				throw new Exception( __( 'You do not have permission to access this page.', 'learnpress-gradebook' ) );
			}

			$data_str    = LP_Request::get_param( 'data' );
			$data        = LP_Helper::json_decode( $data_str, true );
			$user_id     = intval( $data['user_id'] ?? 0 );
			$filter_days = LP_Helper::sanitize_params_submitted( $data['filter_days'] ?? 'last7days' );

			if ( ! $user_id ) {
				throw new Exception( __( 'Param is invalid!', 'learnpress-gradebook' ) );
			}
			if ( ! Permission::can_view_student( $user_id ) ) {
				throw new Exception( __( 'You do not have permission to view this student.', 'learnpress-gradebook' ) );
			}

			$date_now                = gmdate( LP_Datetime::$format, time() );
			$userItemsDB             = UserItemsDB::getInstance();
			$filter                  = new UserItemsFilter();
			$filter->only_fields     = array(
				'SUM( ui.graduation = "in-progress" ) AS inprogress',
				'SUM( ui.graduation = "passed" ) AS passed',
				'SUM( ui.graduation = "failed" ) AS failed',
			);
			$filter->join[]          = "INNER JOIN $userItemsDB->tb_users AS u ON ui.user_id = u.ID";
			$filter->item_type       = LP_COURSE_CPT;
			$filter->user_id         = $user_id;
			$filter->limit           = -1;
			$filter->run_query_count = false;
			$filter->group_by        = 'date';
			$filter->order_by        = 'date';
			$filter->order           = 'DESC';

			$allowed = Permission::get_allowed_course_ids();
			if ( is_array( $allowed ) ) {
				if ( empty( $allowed ) ) {
					throw new Exception( __( 'No data found!', 'learnpress-gradebook' ) );
				}

				$filter->where[] = 'AND ui.item_id IN (' . Permission::get_scope_sql_in( $allowed ) . ')';
			}

			$data_chart = array();
			switch ( $filter_days ) {
				case 'last7days':
					$filter->only_fields[] = 'CAST(ui.start_time AS DATE) as date';
					$filter->where[]       = "AND ui.start_time >= DATE_ADD('$date_now', INTERVAL -6 DAY)";
					$query_data            = $userItemsDB->get_user_items( $filter );
					$data_chart            = $this->map_barchart_day_data( $query_data );
					break;
				case 'last30days':
					$filter->only_fields[] = 'CAST(ui.start_time AS DATE) as date';
					$filter->where[]       = "AND ui.start_time >= DATE_ADD('$date_now', INTERVAL -30 DAY)";
					$query_data            = $userItemsDB->get_user_items( $filter );
					$data_chart            = $this->map_barchart_day_data( $query_data, 30 );
					break;
				case 'last12months':
					$filter->only_fields[] = "DATE_FORMAT( ui.start_time , '%m-%Y') as date";
					$filter->where[]       = "AND EXTRACT(YEAR_MONTH FROM ui.start_time) >= EXTRACT(YEAR_MONTH FROM DATE_ADD('$date_now', INTERVAL -12 MONTH))";
					$query_data            = $userItemsDB->get_user_items( $filter );
					$data_chart            = $this->map_barchart_month_data( $query_data );
					break;
				default:
					break;
			}

			$chart_label                          = array();
			$inprogress_datasets                  = new stdClass();
			$inprogress_datasets->label           = __( 'In Progress', 'learnpress-gradebook' );
			$inprogress_datasets->backgroundColor = '#00a0d2';
			$inprogress_datasets->borderColor     = '#00a0d2';
			$inprogress_datasets->data            = array();

			$passed_datasets                  = new stdClass();
			$passed_datasets->label           = __( 'Passed', 'learnpress-gradebook' );
			$passed_datasets->backgroundColor = '#46b450';
			$passed_datasets->borderColor     = '#46b450';
			$passed_datasets->data            = array();

			$failed_datasets                  = new stdClass();
			$failed_datasets->label           = __( 'Failed', 'learnpress-gradebook' );
			$failed_datasets->backgroundColor = '#dc3232';
			$failed_datasets->borderColor     = '#dc3232';
			$failed_datasets->data            = array();

			foreach ( $data_chart as $key => $value ) {
				$chart_label[]               = $key;
				$inprogress_datasets->data[] = intval( $value->inprogress );
				$passed_datasets->data[]     = intval( $value->passed );
				$failed_datasets->data[]     = intval( $value->failed );
			}

			$response->data   = array(
				'labels'   => $chart_label,
				'datasets' => array(
					$inprogress_datasets,
					$passed_datasets,
					$failed_datasets,
				),
			);
			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	public function map_barchart_day_data( array $query_data, $days = 6 ): array {
		$data = array();
		for ( $i = $days; $i >= 0; $i-- ) {
			$date            = gmdate( 'Y-m-d', strtotime( -$i . 'days' ) );
			$row             = new stdClass();
			$row->date       = $date;
			$row->inprogress = 0;
			$row->passed     = 0;
			$row->failed     = 0;
			$data[ $date ]   = $row;
		}

		if ( ! empty( $query_data ) ) {
			foreach ( $query_data as $row ) {
				$data[ $row->date ] = $row;
			}
		}

		return $data;
	}

	public function map_barchart_month_data( array $query_data, $months = 11 ): array {
		$data = array();
		for ( $i = $months; $i >= 0; $i-- ) {
			$date            = gmdate( 'm-Y', strtotime( -$i . 'months' ) );
			$row             = new stdClass();
			$row->date       = $date;
			$row->inprogress = 0;
			$row->passed     = 0;
			$row->failed     = 0;
			$data[ $date ]   = $row;
		}

		if ( ! empty( $query_data ) ) {
			foreach ( $query_data as $row ) {
				$data[ $row->date ] = $row;
			}
		}

		return $data;
	}
}
