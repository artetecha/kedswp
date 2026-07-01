<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_User_Items_DB
 *
 * @since 3.2.8.6
 */
class LP_Assigment_DB extends LP_User_Items_DB {
	private static $_instance;
	public static $user_item_id_col    = 'learnpress_user_item_id';
	public static $extra_value_col     = 'extra_value';
	public static $answer_note_key     = '_lp_assignment_answer_note';
	public static $answer_upload_key   = '_lp_assignment_answer_upload';
	public static $instructor_note_key = '_lp_assignment_instructor_note';

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Insert extra value
	 *
	 * @param int    $user_item_id
	 * @param string $meta_key
	 * @param string $value
	 */
	public function insert_extra_value( $user_item_id = 0, $meta_key = '', $value = '' ) {
		$data   = array(
			'learnpress_user_item_id' => $user_item_id,
			'meta_key'                => $meta_key,
			'extra_value'             => $value,
		);
		$format = array( '%s', '%s' );
		$this->wpdb->insert( $this->tb_lp_user_itemmeta, $data, $format );
	}

	/**
	 * @param int    $user_item_id
	 * @param string $meta_key
	 * @param string $value
	 */
	public function update_extra_value( $user_item_id = 0, $meta_key = '', $value = '' ) {
		$data   = array(
			'learnpress_user_item_id' => $user_item_id,
			'meta_key'                => $meta_key,
			'extra_value'             => $value,
		);
		$format = array( '%s', '%s' );

		$check_exist_data = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"
				SELECT meta_id FROM $this->tb_lp_user_itemmeta
				WHERE " . self::$user_item_id_col . ' = %d
				AND meta_key = %s
				',
				$user_item_id,
				$meta_key
			)
		);

		if ( $check_exist_data ) {
			$this->wpdb->update(
				$this->tb_lp_user_itemmeta,
				$data,
				array(
					self::$user_item_id_col => $user_item_id,
					'meta_key'              => $meta_key,
				),
				$format
			);
		} else {
			$this->wpdb->insert( $this->tb_lp_user_itemmeta, $data, $format );
		}
	}

	/**
	 * Get extra value
	 *
	 * @param int    $user_item_id
	 * @param string $meta_key
	 */
	public function get_extra_value( $user_item_id = 0, $meta_key = '' ) {
		return $this->wpdb->get_var(
			$this->wpdb->prepare(
				'
				SELECT ' . self::$extra_value_col . " FROM $this->tb_lp_user_itemmeta
				WHERE " . self::$user_item_id_col . ' = %d
				AND meta_key = %s
				',
				$user_item_id,
				$meta_key
			)
		);
	}
}
