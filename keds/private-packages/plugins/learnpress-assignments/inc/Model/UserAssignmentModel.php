<?php

/**
 * Class UserItemModel
 *
 * @package LearnPress/Classes
 * @version 1.0.1
 * @since 4.1.2
 */

namespace LearnPressAssignment\Models;

use DateTime;
use Exception;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\UserItemMeta\UserItemMetaModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;
use LP_Assigment_DB;
use LP_Cache;
use LP_Datetime;
use LP_Helper;
use LP_Quiz;
use LP_User;
use LP_User_Item_Meta_DB;
use LP_User_Item_Meta_Filter;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use LP_WP_Filesystem;
use stdClass;
use Throwable;
use WP_Error;

class UserAssignmentModel extends UserItemModel {
	/**
	 * Item type Course
	 *
	 * @var string Item type
	 */
	public $item_type = LP_ASSIGNMENT_CPT;
	/**
	 * Ref type Order
	 *
	 * @var string
	 */
	public $ref_type = LP_COURSE_CPT;

	/**
	 * Constant key meta
	 */
	const META_KEY_RETAKEN_COUNT   = '_lp_assignment_retaken';
	const META_KEY_ANSWER_NOTE     = '_lp_assignment_answer_note';
	const META_KEY_ANSWER_UPLOAD   = '_lp_assignment_answer_upload';
	const META_KEY_MARK            = '_lp_assignment_mark';
	const META_KEY_INSTRUCTOR_NOTE = '_lp_assignment_instructor_note';
	const META_KEY_EVALUATE_UPLOAD = '_lp_assignment_evaluate_upload';
	const META_KEY_EVALUATE_AUTHOR = '_lp_assignment_evaluate_author';

	/**
	 * Constant status
	 */
	const STATUS_DOING     = 'doing';
	const STATUS_EVALUATED = 'evaluated';

	/**
	 * Get User attend assignment.
	 *
	 * @return false|UserModel
	 */
	public function get_user_model() {
		return UserModel::find( $this->user_id, true );
	}

	/**
	 * Get Course of assignment.
	 *
	 * @return false|CourseModel
	 */
	public function get_course_model() {
		return CourseModel::find( $this->ref_id, true );
	}

	/**
	 * Get Assignment of user.
	 *
	 * @return false|AssignmentPostModel
	 */
	public function get_assignment_model() {
		return AssignmentPostModel::find( $this->item_id, true );
	}

	/**
	 * Get UserCourseModel.
	 *
	 * @return false|UserCourseModel
	 */
	public function get_user_course_model() {
		return UserCourseModel::find( $this->user_id, $this->ref_id, true );
	}

	/**
	 * Find Assignment Item by user_id, course_id, assignment_id.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @param int $assignment_id
	 * @param bool $check_cache
	 *
	 * @return false|UserItemModel|static
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public static function find( int $user_id, int $course_id, int $assignment_id, bool $check_cache = false ) {
		$filter                = new LP_User_Items_Filter();
		$filter->user_id       = $user_id;
		$filter->item_id       = $assignment_id;
		$filter->item_type     = LP_ASSIGNMENT_CPT;
		$filter->ref_id        = $course_id;
		$filter->ref_type      = LP_COURSE_CPT;
		$key_cache             = "userAssignmentModel/find/{$user_id}/{$assignment_id}/{$filter->item_type}/{$course_id}/{$filter->ref_type}";
		$lpUserAssignmentCache = new LP_Cache();

		// Check cache
		if ( $check_cache ) {
			$userAssignmentModel = $lpUserAssignmentCache->get_cache( $key_cache );
			if ( $userAssignmentModel instanceof UserAssignmentModel ) {
				return $userAssignmentModel;
			}
		}

		$userAssignmentModel = static::get_user_item_model_from_db( $filter );
		// Set cache
		if ( $userAssignmentModel instanceof UserAssignmentModel ) {
			if ( ! $userAssignmentModel->meta_data instanceof stdClass ) {
				$userAssignmentModel->meta_data = new stdClass();
			}
			$lpUserAssignmentCache->set_cache( $key_cache, $userAssignmentModel );
		}

		return $userAssignmentModel;
	}

	/**
	 * Time spend user did assignment.
	 *
	 * @return string
	 */
	public function get_time_interval(): string {
		$interval = __( '--', 'learnpress-assignments' );

		try {
			if ( ! in_array( $this->get_status(), [ LP_ASSIGNMENT_STATUS_EVALUATED, 'completed' ] ) ) {
				return $interval;
			}

			$start_time = new DateTime( $this->start_time );
			$end_time   = new DateTime( $this->end_time );
			$interval   = LP_Datetime::format_human_time_diff( $start_time, $end_time );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $interval;
	}

	/**
	 * Get time remaining when user doing assignment.
	 *
	 * @return int seconds
	 */
	public function get_time_remaining(): int {
		$time_remaining  = 0;
		$assignmentModel = $this->get_assignment_model();
		if ( ! $assignmentModel instanceof AssignmentPostModel ) {
			return $time_remaining;
		}

		$duration          = $assignmentModel->get_duration();
		$start_time_stamp  = strtotime( $this->get_start_time() );
		$expire_time_stamp = strtotime( '+' . $duration, $start_time_stamp );
		$time_remaining    = $expire_time_stamp - time();

		if ( $time_remaining <= 0 ) {
			$time_remaining = 0;
		}

		return $time_remaining;
	}

	/**
	 * Get retaken count, number of times the assignment has been retaken.
	 *
	 * @return int
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public function get_retaken_count(): int {
		return (int) $this->get_meta_value_from_key( self::META_KEY_RETAKEN_COUNT, 0 );
	}

	/**
	 * Get author evaluate assignment.
	 *
	 * @return false|UserModel
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public function get_author_evaluated() {
		$author_evaluate    = false;
		$author_evaluate_id = $this->get_meta_value_from_key( self::META_KEY_EVALUATE_AUTHOR, 0 );
		if ( $author_evaluate_id ) {
			$author_evaluate = UserModel::find( $author_evaluate_id, true );
		}

		return $author_evaluate;
	}

	/**
	 * Get evaluate upload files by instructor.
	 *
	 * @return mixed|array
	 */
	public function get_evaluate_instructor_upload_files() {
		return maybe_unserialize( $this->get_meta_value_from_key( self::META_KEY_EVALUATE_UPLOAD ) );
	}

	/**
	 * @return false|string
	 */
	public function get_instructor_note() {
		return $this->get_meta_value_from_key( self::META_KEY_INSTRUCTOR_NOTE, '', true );
	}

	/**
	 * Get mark of user.
	 *
	 * @return float
	 */
	public function get_user_mark() {
		return (float) $this->get_meta_value_from_key( self::META_KEY_MARK );
	}

	/**
	 * @return string
	 */
	public function get_user_answered(): string {
		return $this->get_meta_value_from_key( self::META_KEY_ANSWER_NOTE, '', true );
	}

	/**
	 * Get files user uploaded when answered assignment.
	 *
	 * @return array
	 */
	public function get_user_files_uploaded(): array {
		$uploaded_files = [];

		try {
			$uploaded_file_meta = $this->get_meta_value_from_key( self::META_KEY_ANSWER_UPLOAD, '', true );
			if ( ! empty( $uploaded_file_meta ) ) {
				$uploaded_files = (array) LP_Helper::json_decode( $uploaded_file_meta );
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $uploaded_files;
	}

	/**
	 * Get metadata from key
	 *
	 * @param string $key
	 * @param mixed $default_value
	 * @param bool $get_extra
	 *
	 * @return false|string
	 * @Todo: when LP v4.2.7.4 release will remove this function.
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public function get_meta_value_from_key( string $key, $default_value = false, bool $get_extra = false ) {
		if ( $this->meta_data instanceof stdClass && isset( $this->meta_data->{$key} ) ) {
			return $this->meta_data->{$key};
		}

		$user_item_metadata = $this->get_meta_model_from_key( $key );
		if ( $user_item_metadata instanceof UserItemMetaModel ) {
			if ( ! $this->meta_data instanceof stdClass ) {
				$this->meta_data = new stdClass();
			}

			if ( $get_extra ) {
				$data = $user_item_metadata->extra_value;
			} else {
				$data = $user_item_metadata->meta_value;
				if ( empty( $data ) ) {
					$data = $default_value;
				}
			}

			$this->meta_data->{$key} = $data;
		} else {
			$data = $default_value;
		}

		return $data;
	}

	/**
	 * Update meta value for key.
	 *
	 * @param string $key
	 * @param mixed $value if type is extra, value is must string.
	 * @param bool $is_extra
	 *
	 * @return void
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public function set_meta_value_for_key( string $key, $value, bool $is_extra = false ) {
		if ( ! $this->meta_data instanceof stdClass ) {
			$this->meta_data = new stdClass();
		}

		$this->meta_data->{$key} = $value;
		$lp_db                   = LP_User_Items_DB::getInstance();
		if ( $is_extra ) {
			if ( is_object( $value ) || is_array( $value ) ) {
				$value = json_encode( $value );
			}
			$lp_db->update_extra_value( $this->get_user_item_id(), $key, $value );
		} else {
			learn_press_update_user_item_meta( $this->get_user_item_id(), $key, $value );
		}

		//$this->clean_caches();
	}

	/**
	 * Handle user upload files.
	 *
	 * @param $files
	 *
	 * @return void
	 * @throws Exception
	 */
	public function upload_files_of_student( $files ) {
		if ( empty( $files ) ) {
			return;
		}

		if ( empty( $files['name'][0] ) ) {
			return;
		}

		$assignmentModel          = $this->get_assignment_model();
		$lp_file_system           = LP_WP_Filesystem::instance();
		$uploaded_files           = $this->get_user_files_uploaded();
		$number_file_upload_allow = $assignmentModel->get_file_number_allow();
		if ( $number_file_upload_allow <= 0 ) {
			throw new Exception( esc_html__( 'You can not upload any file!', 'learnpress-assignments' ) );
		}

		$number_file_can_upload = $assignmentModel->get_file_number_allow() - count( $uploaded_files );
		$total_files_upload_now = count( $files['name'] );
		$file_size_allow        = $assignmentModel->get_file_size_allow() * 1024 * 1024; // convert to byte
		$file_extends_allow     = $assignmentModel->get_file_extends_allow(); // convert to byte

		if ( $number_file_can_upload < $total_files_upload_now ) {
			throw new Exception( 'You can only upload ' . $number_file_can_upload . ' files' );
		}

		foreach ( $files['name'] as $key => $value ) {
			$file_name = $files['name'][ $key ];
			$file_type = $files['type'][ $key ];
			$file_tmp  = $files['tmp_name'][ $key ];
			$file_size = $files['size'][ $key ];

			// Check file size
			if ( $file_size > $file_size_allow ) {
				throw new Exception( sprintf( __( 'The size of your %1$s file is over %2$d Mb(s).', 'learnpress-assignments' ), $file_name, $file_size_allow ) );
			}

			// Check type file
			$check_file = wp_check_filetype_and_ext( $file_tmp, $file_name, get_allowed_mime_types() );
			$file_ext   = $check_file['ext'] ?? '';
			if ( ! in_array( $file_ext, $file_extends_allow ) ) {
				throw new Exception( sprintf( esc_html__( 'File %s type is invalid!', 'learnpress' ), $file_name ) );
			}

			$file_upload = [
				'name'     => $file_name,
				'type'     => $file_type,
				'tmp_name' => $file_tmp,
				'error'    => 0,
				'size'     => $file_size,
			];

			$upload_result = $lp_file_system->lp_handle_upload( $file_upload, [ 'test_form' => false ] );
			if ( $upload_result && ! isset( $upload_result['error'] ) ) {
				$file_uploaded                                   = [
					'filename'   => $file_name,
					'file'       => str_replace( ABSPATH, '', $upload_result['file'] ),
					'url'        => str_replace( get_site_url(), '', $upload_result['url'] ),
					'type'       => $upload_result['type'] ?? $file_type,
					'size'       => $file_size,
					'saved_time' => gmdate( LP_Datetime::$format, time() ),
				];
				$uploaded_files[ md5( $file_uploaded['file'] ) ] = $file_uploaded;
			} else {
				throw new Exception( $upload_result['error'] );
			}
		}

		$this->set_meta_value_for_key( self::META_KEY_ANSWER_UPLOAD, $uploaded_files, true );
	}

	/**
	 * Evaluate assignment.
	 *
	 * @param array $data
	 * @param bool $only_save
	 *
	 * @return void
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.1.2
	 */
	public function instructor_evaluate_assignment( array $data, bool $only_save = false ) {
		$author = $data['author'] ?? null;
		if ( ! $author instanceof UserModel ) {
			throw new Exception( esc_html__( 'Author not found', 'learnpress-assignments' ) );
		}

		$this->check_author_can_evaluate_assignment( $author );

		$assignmentModel = $this->get_assignment_model();
		if ( ! $assignmentModel instanceof AssignmentPostModel ) {
			throw new Exception( esc_html__( 'Assignment not found', 'learnpress-assignments' ) );
		}

		if ( $this->get_status() !== self::STATUS_COMPLETED ) {
			throw new Exception( esc_html__( 'User not submit assignment', 'learnpress-assignments' ) );
		}

		$mark     = (float) $data['mark'] ?? 0;
		$mark_max = $assignmentModel->get_max_mark();
		if ( $mark > $mark_max ) {
			throw new Exception( sprintf( esc_html__( 'Mark max equal %s', 'learnpress-assignments' ), $mark_max ) );
		}

		$passing_grade       = $assignmentModel->get_passing_grade();
		$instructor_note     = $data['instructor_note'] ?? '';
		$instructor_document = $data['instructor_document'] ?? '';
		$this->set_meta_value_for_key( self::META_KEY_MARK, $mark );
		$this->set_meta_value_for_key( self::META_KEY_INSTRUCTOR_NOTE, $instructor_note, true );
		$this->set_meta_value_for_key( self::META_KEY_EVALUATE_UPLOAD, $instructor_document );
		$this->set_meta_value_for_key( self::META_KEY_EVALUATE_AUTHOR, $author->get_id() );

		if ( ! $only_save ) {
			$this->status     = self::STATUS_EVALUATED;
			$this->graduation = ( $mark >= $passing_grade ? 'passed' : 'failed' );
		}

		$this->save();

		do_action( 'learn-press/assignment/instructor-evaluated', $this->user_id, $assignmentModel->get_id() );
	}

	/**
	 * Re-evaluate assignment.
	 *
	 * @param array $data [author] who re-evaluate assignment.
	 *
	 * @return void
	 * @throws Exception
	 * @version 1.0.1
	 * @since 4.1.2
	 */
	public function instructor_re_evaluate_assignment( array $data ) {
		$author = $data['author'] ?? null;
		if ( ! $author instanceof UserModel ) {
			throw new Exception( esc_html__( 'Author not found', 'learnpress-assignments' ) );
		}

		$this->check_author_can_evaluate_assignment( $author );

		$assignmentModel = $this->get_assignment_model();
		if ( ! $assignmentModel instanceof AssignmentPostModel ) {
			throw new Exception( esc_html__( 'Assignment not found', 'learnpress-assignments' ) );
		}

		if ( $this->get_status() !== $this::STATUS_EVALUATED ) {
			throw new Exception( esc_html__( 'The result has not been evaluated!', 'learnpress-assignments' ) );
		}

		$this->delete_meta( self::META_KEY_MARK );
		$this->delete_meta( self::META_KEY_INSTRUCTOR_NOTE );
		$this->delete_meta( self::META_KEY_EVALUATE_UPLOAD );
		$this->delete_meta( self::META_KEY_EVALUATE_AUTHOR );
		$this->status     = self::STATUS_COMPLETED;
		$this->graduation = '';
		// $this->end_time   = null;
		$this->save();

		do_action( 'learn-press/assignment/instructor-re-evaluated', $this->user_id, $assignmentModel->get_id() );
	}

	/**
	 * Get number retake remaining.
	 *
	 * @return int
	 */
	public function get_remaining_retake(): int {
		$assignmentModel  = $this->get_assignment_model();
		$retake_max       = $assignmentModel->get_retake_count();
		$retaken_count    = $this->get_retaken_count();
		$remaining_retake = $retake_max - $retaken_count;
		if ( $remaining_retake <= 0 ) {
			$remaining_retake = 0;
		}

		return $remaining_retake;
	}

	/**
	 * Handle retake assignment.
	 *
	 * @return void
	 * @throws Exception
	 * @since 4.1.2
	 * @version 1.0.2
	 */
	public function handle_retake() {
		$status = $this->get_status();
		if ( ! in_array(
			$status,
			[
				self::STATUS_COMPLETED,
				self::STATUS_EVALUATED,
			]
		) ) {
			throw new Exception( __( 'You must complete this Assignment', 'learnpress-assignments' ) );
		}

		$userCourseModel = UserCourseModel::find( $this->user_id, $this->ref_id, true );
		if ( ! $userCourseModel instanceof UserCourseModel ) {
			throw new Exception( __( 'User course invalid!', 'learnpress-assignments' ) );
		} elseif ( $userCourseModel->has_finished() ) {
			throw new Exception( __( 'Course is finished!', 'learnpress-assignments' ) );
		} elseif ( $userCourseModel->timestamp_remaining_duration() === 0 ) {
			throw new Exception( __( 'Course is expired', 'learnpress-assignments' ) );
		}

		$remaining_retake = $this->get_remaining_retake();
		$retaken_count    = $this->get_retaken_count();
		if ( $remaining_retake === 0 ) {
			throw new Exception( __( 'You have reached the maximum number of retakes', 'learnpress-assignments' ) );
		}

		$can_retake = apply_filters( 'learn-press/assignment/user/can-retake', true, $this );
		if ( $can_retake instanceof WP_Error ) {
			throw new Exception( $can_retake->get_error_message() );
		}

		++ $retaken_count;
		$this->status     = LP_ITEM_STARTED;
		$this->graduation = '';
		$this->start_time = gmdate( LP_Datetime::$format, time() );
		$this->end_time   = null;

		$this->delete_meta( self::META_KEY_MARK );
		$this->delete_meta( self::META_KEY_INSTRUCTOR_NOTE );
		$this->delete_meta( self::META_KEY_EVALUATE_UPLOAD );
		$this->delete_meta( self::META_KEY_EVALUATE_AUTHOR );
		$this->set_meta_value_for_key( self::META_KEY_RETAKEN_COUNT, $retaken_count );

		$this->save();

		// Hook old
		if ( has_action( 'learn-press/assignment/student-retake-assignment' ) ) {
			do_action(
				'learn-press/assignment/student-retake-assignment',
				$this->get_user_item_id(),
				$this->user_id,
				$this->item_id,
				$this->ref_id
			);
		}

		do_action( 'learn-press/assignment/user/retake', $this );
	}

	/**
	 * Check user can finish course or not.
	 * Function temporary. LP v4.2.7.5 will provide it, when LP release will use instead.
	 *
	 * @return bool|WP_Error
	 * @throws Exception
	 *
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public function can_finish_course() {
		$can_finish = true;

		try {
			$courseModel = $this->get_course_model();
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not exists!', 'learnpress' ) );
			}

			$userCourseModel = UserCourseModel::find( $this->user_id, $courseModel->get_id() );
			if ( ! $userCourseModel instanceof UserCourseModel ) {
				throw new Exception( __( 'User Course invalid!', 'learnpress' ) );
			}

			if ( $userCourseModel->has_finished() ) {
				throw new Exception( __( 'Course is finished!', 'learnpress' ) );
			}

			if ( ! $userCourseModel->has_enrolled() ) {
				throw new Exception( __( 'Course is not enroll!', 'learnpress' ) );
			}

			$course_results = $userCourseModel->calculate_course_results();
			if ( ! $course_results['pass'] ) {
				$allow_finish_when_all_item_completed = $courseModel->get_meta_value_by_key( CoursePostModel::META_KEY_HAS_FINISH, 'yes' );
				if ( $allow_finish_when_all_item_completed ) {
					$course_total_items_obj = $courseModel->get_total_items();
					if ( $course_total_items_obj && $course_results['completed_items'] < (int) $course_total_items_obj->count_items ) {
						throw new Exception( __( 'You must complete all items in course', 'learnpress' ) );
					}
				} else {
					throw new Exception( __( 'You must passed course', 'learnpress' ) );
				}
			}
		} catch ( Throwable $e ) {
			$can_finish = new WP_Error( 'lp_user_course_can_finish_err', $e->getMessage() );
		}

		return $can_finish;
	}

	/**
	 * Check author can evaluate assignment.
	 *
	 * @throws Exception
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public function check_author_can_evaluate_assignment( $author ) {
		$assignmentModel = $this->get_assignment_model();
		if ( ! $assignmentModel instanceof AssignmentPostModel ) {
			throw new Exception( esc_html__( 'Assignment not found', 'learnpress-assignments' ) );
		}

		$assignment_author = $assignmentModel->get_author_model();
		if ( ! $assignment_author instanceof UserModel ) {
			throw new Exception( esc_html__( 'Assignment author not found', 'learnpress-assignments' ) );
		}

		if ( ! user_can( $author->get_id(), ADMIN_ROLE )
			&& ( user_can( $author->get_id(), LP_TEACHER_ROLE ) && $author->get_id() !== $assignment_author->get_id() ) ) {
			$can_evaluate = apply_filters( 'learn-press/assignment/can-evaluate', false, $author, $assignmentModel, $this );
			if ( ! $can_evaluate ) {
				throw new Exception( esc_html__( 'You do not have permission to evaluate this assignment', 'learnpress-assignments' ) );
			}
		}
	}

	/**
	 * Delete meta from key.
	 *
	 * @param string $key
	 *
	 * @return void
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public function delete_meta( string $key ) {
		learn_press_delete_user_item_meta( $this->get_user_item_id(), $key );
		$this->meta_data->{$key} = null;
		$this->clean_caches();
	}

	/**
	 * Delete user item.
	 *
	 * @throws Exception
	 * @since 4.1.2
	 * @version 1.0.1
	 */
	public function delete() {
		//Delete meta data of user item.
		$lp_user_item_meta_db = LP_User_Item_Meta_DB::getInstance();
		$filter               = new LP_User_Item_Meta_Filter();
		$filter->where[]      = $lp_user_item_meta_db->wpdb->prepare( 'AND learnpress_user_item_id = %d', $this->get_user_item_id() );
		$filter->collection   = $lp_user_item_meta_db->tb_lp_user_itemmeta;
		$lp_user_item_meta_db->delete_execute( $filter );
		$this->meta_data = null;

		// Delete user item.
		$lp_user_item_db    = LP_User_Items_DB::getInstance();
		$filter             = new LP_User_Items_Filter();
		$filter->where[]    = $lp_user_item_db->wpdb->prepare( 'AND user_item_id = %d', $this->get_user_item_id() );
		$filter->collection = $lp_user_item_db->tb_lp_user_items;
		$lp_user_item_db->delete_execute( $filter );

		$this->clean_caches();
	}

	/**
	 * Clean caches.
	 *
	 * @return void
	 */
	public function clean_caches() {
		parent::clean_caches();

		// Clear cache user item.
		$lp_cache  = new LP_Cache();
		$key_cache = "userAssignmentModel/find/{$this->user_id}/{$this->item_id}/{$this->item_type}/{$this->ref_id}/{$this->ref_type}";
		$lp_cache->clear( $key_cache );
	}
}
