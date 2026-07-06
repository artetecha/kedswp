<?php

namespace LearnPress\Gradebook\Ajax;

use Exception;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Gradebook\Permission;
use LearnPress\Gradebook\TemplateHooks\Admin\AdminRecentActivityTemplate;
use LearnPress\Models\UserItems\UserCourseModel;
use LP_Datetime;
use LP_Helper;
use LP_Request;
use LP_REST_Response;
use Throwable;

/**
 * Class ExportCSV
 *
 * @since 4.0.8
 * @version 1.0.0
 */

class ExportCSV extends AbstractAjax {
	/**
	 * Export student courses to CSV
	 *
	 * Call on file js: assets/src/js/admin/student-detail.js
	 *
	 * @return void
	 */
	public function lp_gradebook_export_student_courses() {
		$response = new LP_REST_Response();

		try {
			if ( ! Permission::can_view_gradebook() ) {
				throw new Exception( __( 'You do not have permission to access this page.', 'learnpress-gradebook' ) );
			}

			$data_str    = LP_Request::get_param( 'data' );
			$data        = LP_Helper::json_decode( $data_str, true );
			$user_id     = intval( $data['user_id'] ?? 0 );
			$paged       = intval( $data['paged'] ?? 1 );
			$delete_file = $data['delete_file'] ?? 0;

			if ( ! $user_id ) {
				throw new Exception( __( 'Param is invalid!', 'learnpress-gradebook' ) );
			}
			$wp_upload_dir = wp_upload_dir();
			$file_name     = 'lp-gradebook.csv';
			$file_path     = $wp_upload_dir['basedir'] . '/' . $file_name;

			if ( $delete_file ) {
				// Delete file csv after downloaded.
				if ( file_exists( $file_path ) ) {
					wp_delete_file( $file_path );
				}

				$response->status        = 'success';
				$response->data->deleted = 1;
				$response->message       = __( 'Delete file successfully!', 'learnpress-gradebook' );

				wp_send_json( $response );
			}

			if ( ! Permission::can_view_student( $user_id ) ) {
				throw new Exception( __( 'You do not have permission to view this student.', 'learnpress-gradebook' ) );
			}

			$filter              = new UserItemsFilter();
			$total_rows          = 0;
			$userItemsDB         = UserItemsDB::getInstance();
			$filter->only_fields = array(
				UserItemsFilter::COL_USER_ITEM_ID,
				UserItemsFilter::COL_USER_ID,
				UserItemsFilter::COL_ITEM_ID,
			);
			$filter->user_id     = $user_id;
			$filter->item_type   = LP_COURSE_CPT;
			$filter->limit       = 10;
			$filter->page        = $paged;

			$allowed = Permission::get_allowed_course_ids();
			if ( is_array( $allowed ) ) {
				if ( empty( $allowed ) ) {
					throw new Exception( __( 'No data found!', 'learnpress-gradebook' ) );
				}

				$filter->where[] = 'AND ui.item_id IN (' . Permission::get_scope_sql_in( $allowed ) . ')';
			}

			$items = $userItemsDB->get_user_items( $filter, $total_rows );
			if ( ! $items ) {
				throw new Exception( __( 'No data found!', 'learnpress-gradebook' ) );
			}

			$total_pages = UserItemsDB::get_total_pages( $filter->limit, $total_rows );

			// Handle create file CSV.
			if ( $paged === 1 ) {
				$header = array(
					__( 'Course', 'learnpress-gradebook' ),
					__( 'Start date', 'learnpress-gradebook' ),
					__( 'End date', 'learnpress-gradebook' ),
					__( 'Progress', 'learnpress-gradebook' ),
					__( 'Status', 'learnpress-gradebook' ),
				);

				$file = fopen( $file_path, 'w' );
				fputcsv( $file, $header );
			} else {
				$file = fopen( $file_path, 'a' );
			}

			foreach ( $items as $itemObj ) {
				$userCourseModel = UserCourseModel::find( $itemObj->user_id, $itemObj->item_id );
				if ( ! $userCourseModel ) {
					continue;
				}

				$start_time_obj = new LP_Datetime( $userCourseModel->get_start_time() );
				$end_time_obj   = new LP_Datetime( $userCourseModel->get_end_time() );
				$progress       = $userCourseModel->calculate_course_results();

				$row = array(
					$userCourseModel->get_course_model()->get_title(),
					$start_time_obj->format( LP_Datetime::I18N_FORMAT_HAS_TIME ),
					$end_time_obj->format( LP_Datetime::I18N_FORMAT_HAS_TIME ),
					$progress['result'],
					AdminRecentActivityTemplate::get_status_label( $userCourseModel->get_status() ),
				);

				fputcsv( $file, $row );
			}

			fclose( $file );

			$file_url = $wp_upload_dir['baseurl'] . '/' . $file_name;

			$response->status         = 'success';
			$response->data->file     = $file_url;
			$response->data->filename = 'student-courses.csv';
			$response->data->finish   = $paged >= $total_pages ? 1 : 0;
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
