<?php

namespace LearnPress\Certificate\Upgrade;

use Exception;
use LearnPress\Certificate\Models\CertificatePostModel;
use LP_REST_Response;

abstract class CertificateUpgradeBase {
	public function handle( array $params ) {
		$response = new LP_REST_Response();

		return $response;
	}
}
