<?php

/**
 * Class Assignment Post Model
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.1.2
 */

namespace LearnPressAssignment\Models;

use LearnPress\Helpers\Template;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\PostModel;
use LP_Assignment_Filter;
use LP_Datetime;
use Throwable;


class AssignmentPostModel extends PostModel {
	/**
	 * @var string Post Type
	 */
	public $post_type = LP_ASSIGNMENT_CPT;

	/**
	 * Const meta key
	 */
	const META_KEY_MARK                  = '_lp_mark';
	const META_KEY_PASSING_GRADE         = '_lp_passing_grade';
	const META_KEY_RETAKE_COUNT          = '_lp_retake_count';
	const META_KEY_DURATION              = '_lp_duration';
	const META_KEY_ATTACHMENTS           = '_lp_attachments';
	const META_KEY_TASK_INTRODUCTION     = '_lp_introduction';
	const META_KEY_FILE_EXTENSION_ALLOW  = '_lp_file_extension';
	const META_KEY_FILE_NUMBER_ALLOW     = '_lp_upload_files';
	const META_KEY_FILE_SIZE_LIMIT_ALLOW = '_lp_upload_file_limit';

	/**
	 * Get post assignment by ID
	 *
	 * @param int $post_id
	 * @param bool $check_cache
	 *
	 * @return false|static
	 */
	public static function find( int $post_id, bool $check_cache = false ) {
		$filter_post     = new LP_Assignment_Filter();
		$filter_post->ID = $post_id;

		return self::get_item_model_from_db( $filter_post );
	}

	/**
	 * Get the introduction task.
	 *
	 * @return string
	 */
	public function get_introduction_task(): string {
		return $this->get_meta_value_by_key( self::META_KEY_TASK_INTRODUCTION, '' );
	}

	/**
	 * Get max mark of assignment
	 *
	 * @return float
	 */
	public function get_max_mark(): float {
		return (float) $this->get_meta_value_by_key( self::META_KEY_MARK, 10 );
	}

	/**
	 * Get max mark of assignment
	 *
	 * @return string
	 */
	public function get_duration(): string {
		return $this->get_meta_value_by_key( self::META_KEY_DURATION, '3 day' );
	}

	/**
	 * Get max mark of assignment
	 *
	 * @return float
	 */
	public function get_passing_grade(): float {
		return (float) $this->get_meta_value_by_key( self::META_KEY_PASSING_GRADE, 8 );
	}

	/**
	 * Get retake count option.
	 *
	 * @return int
	 */
	public function get_retake_count(): int {
		return (int) $this->get_meta_value_by_key( self::META_KEY_RETAKE_COUNT, 0 );
	}

	public function get_edit_link() {
		return get_edit_post_link( $this->get_id() );
	}

	/**
	 * Get attachments of assignment.
	 *
	 * @return mixed
	 */
	public function get_attachments_assignment() {
		return $this->get_meta_value_by_key( self::META_KEY_ATTACHMENTS, [] );
	}

	/**
	 * Get file extension allow upload of assignment.
	 *
	 * @return array
	 */
	public function get_file_extends_allow(): array {
		$file_extends_allow_arr = [];
		$file_extends_allow     = $this->get_meta_value_by_key( self::META_KEY_FILE_EXTENSION_ALLOW, 'jpg,txt,zip,pdf,doc,docx,ppt' );
		if ( ! empty( $file_extends_allow ) ) {
			$file_extends_allow_arr = explode( ',', $file_extends_allow );
		}

		return $file_extends_allow_arr;
	}

	/**
	 * Get file size allow upload of assignment.
	 *
	 * @return float
	 */
	public function get_file_size_allow(): float {
		return (float) $this->get_meta_value_by_key( self::META_KEY_FILE_SIZE_LIMIT_ALLOW, 2 );
	}

	/**
	 * Get files number allow upload of assignment.
	 *
	 * @return int
	 */
	public function get_file_number_allow(): int {
		return (int) $this->get_meta_value_by_key( self::META_KEY_FILE_NUMBER_ALLOW, 0 );
	}
}
