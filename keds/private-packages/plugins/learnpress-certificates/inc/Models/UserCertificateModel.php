<?php

namespace LearnPress\Certificate\Models;

use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LP_Cache;
use LP_User_Items_Filter;

/**
 * Class UserItemModel
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.1.7
 */
class UserCertificateModel extends UserItemModel {
	/**
	 * Item type Course
	 *
	 * @var string Item type
	 */
	public $item_type = 'lp_certificate';
	/**
	 * Ref type Order
	 *
	 * @var string
	 */
	public $ref_type = LP_ORDER_CPT;

	public function __construct( $data = null ) {
		parent::__construct( $data );
	}

	/**
	 * Find User Item by user_id, item_id, item_type.
	 *
	 * @param int $user_id
	 * @param int $certificate_id
	 * @param UserCourseModel $userCourseModel
	 * @param bool $check_cache
	 *
	 * @return false|UserItemModel|static
	 * @since 4.1.7
	 * @version 1.0.0
	 */
	public static function find( int $user_id, int $certificate_id, UserCourseModel $userCourseModel, bool $check_cache = false ) {
		static $staticData = [];

		$parent_id         = $userCourseModel->get_user_item_id();
		$filter            = new LP_User_Items_Filter();
		$filter->user_id   = $user_id;
		$filter->item_id   = $certificate_id;
		$filter->item_type = 'lp_certificate';
		$filter->parent_id = $userCourseModel->get_user_item_id();
		$key_cache         = "userCertificateModel/find/{$user_id}/{$certificate_id}/{$filter->item_type}/{$parent_id}";
		$lpCache           = new LP_Cache();

		// Check cache
		if ( $check_cache ) {
			$userCourseModel = $lpCache->get_cache( $key_cache );
			if ( $userCourseModel instanceof UserCertificateModel ) {
				return $userCourseModel;
			}

			if ( isset( $staticData[ $key_cache ] ) ) {
				return $staticData[ $key_cache ];
			}
		}

		$userCourseModel = static::get_user_item_model_from_db( $filter );

		// Set cache
		if ( $userCourseModel instanceof UserCertificateModel ) {
			$lpCache->set_cache( $key_cache, $userCourseModel );
		}

		$staticData[ $key_cache ] = $userCourseModel;

		return $userCourseModel;
	}

	/**
	 * Clean caches.
	 *
	 * @return void
	 *
	 * @since 4.2.5.4
	 * @version 1.0.1
	 */
	public function clean_caches() {
		$key_cache = "userCertificateModel/find/{$this->user_id}/{$this->item_id}/{$this->item_type}/{$this->parent_id}";
		$lpCache   = new LP_Cache();
		$lpCache->clear( $key_cache );

		parent::clean_caches();
	}
}
