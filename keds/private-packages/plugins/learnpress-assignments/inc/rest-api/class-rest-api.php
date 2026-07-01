<?php
/**
 * Register Rest API
 *
 * @author Nhamdv <daonham95@gmail.com>
 */
class LP_Assignment_Rest_API {
	protected static $instance = null;

	public function __construct() {
		add_filter( 'lp_rest_api_get_rest_namespaces', array( $this, 'assignment_rest_api_init' ) );
	}

	public function assignment_rest_api_init( $data ) {
		$data['learnpress/v1']['assignments']          = 'LP_Jwt_Assignment_V1_Controller';
		$data['learnpress/v1']['assignment/evaluated'] = 'LP_Assignment_Evaluate_V1_Controller';

		return $data;
	}

	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}
}

LP_Assignment_Rest_API::instance();
