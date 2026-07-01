<?php

namespace Thim_EL_Kit\Modules\NotFound;

use Thim_EL_Kit\Modules\Modules;
use Thim_EL_Kit\SingletonTrait;

class Init extends Modules {
	use SingletonTrait;

	public function __construct() {
		$this->tab      = 'not-found';
		$this->tab_name = esc_html__( '404 Page', 'thim-elementor-kit' );

		parent::__construct();
	}

	public function template_include( $template ) {
		if ( is_404() ) {
			$this->template_include = true;
		}

		return parent::template_include( $template );
	}

	public function is( $condition ) {
		if ( $condition['type'] === 'all_404' || $condition['type'] === 'all' ) {
			return is_404();
		}
		return false;
	}

	public function priority( $type ) {
		return 10;
	}

	public function get_conditions() {
		return array(
			array(
				'label'    => esc_html__( '404 Page', 'thim-elementor-kit' ),
				'value'    => 'all_404',
				'is_query' => false,
			),
		);
	}
}

Init::instance();
