<?php

namespace LearnPress\Certificate\Models;

use Exception;
use LP_Debug;
use LP_Helper;
use Throwable;

class CertificateBuilderData {
	protected ?CertificatePostModel $certificatePostModel = null;

	protected array $raw_data = [];

	public function __construct( CertificatePostModel $certificate ) {
		$this->certificatePostModel = $certificate;
	}

	/**
	 * Get raw layers data
	 *
	 * @throws Exception
	 */
	public function get_raw_layers() {
		if ( ! empty( $this->raw_data ) ) {
			return $this->raw_data;
		}

		$data = $this->certificatePostModel->get_layer();
		if ( empty( $data ) ) {
			return [];
		}
		$this->raw_data = LP_Helper::json_decode( $data, true );

		return $this->raw_data;
	}

	/**
	 * Save layers data
	 *
	 * @param array $data
	 *
	 * @return true
	 */
	public function save_layers( array $data ) {
		// Default size A4 DPI 96
		$default = array(
			'width'      => 794,
			'height'     => 1123,
			'background' => '#ffffff',
			'layers'     => array(),
		);

		$data_to_save = wp_parse_args( $data, $default );

		$this->certificatePostModel->set_layer( $data_to_save );

		$this->raw_data = [];

		return true;
	}

	/**
	 * Add layer to certificate
	 *
	 * @throws Exception
	 */
	public function add_layer( array $layer_data ) {
		$raw_layers             = $this->get_raw_layers();
		$raw_layers['layers'][] = $layer_data;

		return $this->save_layers( $raw_layers );
	}
}
