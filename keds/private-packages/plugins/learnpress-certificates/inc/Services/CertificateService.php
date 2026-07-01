<?php

namespace LearnPress\Certificate\Services;

defined( 'ABSPATH' ) || exit;

use Exception;
use LearnPress\Certificate\Models\CertificatePostModel;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\PostModel;

/**
 * Class CertificateService
 *
 * Create certificate with data.
 *
 * @package LearnPress\Certificate\Services
 * @since 4.2.3
 * @version 1.0.0
 */
class CertificateService {
	use Singleton;

	public function init() {}

	/**
	 * Create certificate
	 *
	 * @param array $data [ 'post_title' => '', 'post_content' => '', 'post_status' => '', 'post_author' => , ... ]
	 *
	 * @throws Exception
	 */
	public function create( array $data ): CertificatePostModel {
		$certificatePostModelNew = new CertificatePostModel( $data );

		// Set meta data
		if ( isset( $data['meta_input'] ) ) {
			$certificatePostModelNew->meta_data = (object) $data['meta_input'];
		}

		$certificatePostModelNew->save();

		return $certificatePostModelNew;
	}

	/**
	 * Duplicate certificate
	 *
	 * @throws Exception
	 * @since 4.2.3
	 * @version 1.0.0
	 */
	public function duplicate( CertificatePostModel $certificatePostModel ): CertificatePostModel {
		$certificatePostModel->get_all_metadata();
		$certificatePostModelNew              = new CertificatePostModel( $certificatePostModel );
		$certificatePostModelNew->ID          = null;
		$certificatePostModelNew->post_title  = $certificatePostModelNew->post_title . ' (Copy)';
		$certificatePostModelNew->post_status = PostModel::STATUS_DRAFT;
		$certificatePostModelNew->save();

		return $certificatePostModelNew;
	}
}
