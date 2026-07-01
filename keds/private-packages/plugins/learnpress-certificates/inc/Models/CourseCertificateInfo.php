<?php

namespace LearnPress\Certificate\Models;

use Exception;
use LearnPress\Models\CourseModel;
use LP_Helper;

class CourseCertificateInfo extends CourseModel {

	const META_KEY_CERT_INFO = 'course_certificate_info';
	const META_KEY_CERT_ID   = '_lp_cert';
	public $certPostModel;

	/**
	 * Get certificate post model
	 *
	 * @return CertificatePostModel|false|mixed
	 */
	public function get_cert_post_model() {
		if ( ! $this->certPostModel ) {
			$cer_id_assign_course = $this->get_meta_value_by_key( self::META_KEY_CERT_ID );
			if ( ! $cer_id_assign_course ) {
				return false;
			}
			$this->certPostModel = CertificatePostModel::find( $cer_id_assign_course, true );
			if ( ! $this->certPostModel ) {
				return false;
			}
		}
		return $this->certPostModel;
	}

	/**
	 * Get certificate info set for a course
	 *
	 * @throws Exception
	 */
	public function get_certificate_info(): array {
		$data = $this->get_meta_value_by_key( self::META_KEY_CERT_INFO );
		if ( empty( $data ) ) {
			return [];
		}

		return LP_Helper::json_decode( $data, true );
	}

	/**
	 * Get certificate image display in course
	 *
	 * @throws Exception
	 */
	public function get_cert_image_url(): string {
		$info = $this->get_certificate_info();
		if ( ! empty( $info['image'] ) ) {
			return $info['image'];
		}

		$certPostModel = $this->get_cert_post_model();
		if ( ! $certPostModel ) {
			return plugins_url( '/assets/images/no-image.png', LP_ADDON_CERTIFICATES_FILE );
		}

		$image_url = $certPostModel->get_thumbnail();
		if ( ! empty( $image_url ) ) {
			return $image_url;
		}

		return plugins_url( '/assets/images/no-image.png', LP_ADDON_CERTIFICATES_FILE );
	}

	/**
	 * Check if certificate is enabled for a course
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function is_enabled(): bool {
		$info   = $this->get_certificate_info();
		$enable = (int) ( $info['enable'] ?? 0 );

		return $enable && $this->get_cert_post_model();
	}
}
