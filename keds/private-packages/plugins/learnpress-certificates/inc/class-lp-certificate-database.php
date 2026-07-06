<?php

class LP_Certificate_DB extends LP_Database {
	public static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function add_data_cert_to_user_items( $data_user_item_cert ) {
		$result = $this->wpdb->insert(
			self::getInstance()->tb_lp_user_items,
			$data_user_item_cert,
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result;
	}

	/**
	 * Get Courses
	 *
	 * @param LP_Certificate_Filter $filter
	 * @param int $total_rows return total_rows
	 *
	 * @return array|null|int|string
	 * @throws Exception
	 * @author tungnx
	 * @version 1.0.1
	 * @since 4.0.2
	 */
	public function query_certificates( LP_Certificate_Filter $filter, int &$total_rows = 0 ) {
		$default_fields = $this->get_cols_of_table( $this->tb_posts );
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_posts;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'cer';
		}

		// Where
		$filter->where[] = $this->wpdb->prepare( 'AND cer.post_type = %s', $filter->post_type );

		// Find certificate publish.
		$filter->where[] = $this->wpdb->prepare( 'AND cer.post_status = %s', 'publish' );

		$filter = apply_filters( 'lp/certificate/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}
