<?php

/**
 * Class Lesson Post Model
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.0
 */

namespace LearnPress\Certificate\Models;

use Exception;
use LearnPress\Filters\PostFilter;
use LearnPress\Models\PostModel;
use LP_Cache;

class CertificatePostModel extends PostModel {
	/**
	 * @var string Post Type
	 */
	public $post_type = LP_ADDON_CERTIFICATES_CERT_CPT;

	/**
	 * Const meta key
	 */
	const META_KEY_PRICE      = '_lp_certificate_price';
	const META_KEY_THUMBNAIL  = '_lp_cert_thumbnail';
	const META_KEY_TEMPLATE   = '_lp_cert_template';
	const META_KEY_CER_LAYERS = '_lp_cert_layers'; // old key
	const META_KEY_LAYER      = '_lp_layer'; // new key for refactor code after.

	/**
	 * Get post certificate by ID
	 *
	 * @param int $post_id
	 * @param bool $check_cache
	 *
	 * @return false|static
	 */
	public static function find( int $post_id, bool $check_cache = false ) {
		$filter            = new PostFilter();
		$filter->ID        = $post_id;
		$filter->post_type = LP_ADDON_CERTIFICATES_CERT_CPT;

		$key_cache = "certificatePostModel/find/{$post_id}";
		$lp_cache  = new LP_Cache();

		// Check cache
		if ( $check_cache ) {
			$certificatePostModel = $lp_cache->get_cache( $key_cache );
			if ( $certificatePostModel instanceof self ) {
				return $certificatePostModel;
			}
		}

		$certificatePostModel = self::get_item_model_from_db( $filter );
		// Set cache
		if ( $certificatePostModel instanceof CertificatePostModel ) {
			$lp_cache->set_cache( $key_cache, $certificatePostModel );
		}

		return $certificatePostModel;
	}

	/**
	 * Get certificate price
	 *
	 * @return float
	 */
	public function get_price(): float {
		return (float) $this->get_meta_value_by_key( self::META_KEY_PRICE, 0 );
	}

	/**
	 * @param float $price
	 *
	 * @return void
	 */
	public function set_price( float $price ) {
		$this->save_meta_value_by_key( self::META_KEY_PRICE, $price );
	}

	/**
	 * Get certificate thumbnail
	 *
	 * @return string
	 */
	public function get_thumbnail(): string {
		return $this->get_meta_value_by_key( self::META_KEY_THUMBNAIL, '' );
	}

	/**
	 * @param string $thumbnail
	 *
	 * @return void
	 * @throws Exception
	 */
	public function set_thumbnail( string $thumbnail ) {
		$this->save_meta_value_by_key( self::META_KEY_THUMBNAIL, $thumbnail );
	}

	/**
	 * Get certificate thumbnail
	 *
	 * @return array
	 */
	public function get_cer_layers(): array {
		return $this->get_meta_value_by_key( self::META_KEY_CER_LAYERS, [] );
	}

	/**
	 * @param array $layers
	 *
	 * @return void
	 * @throws Exception
	 */
	public function set_cer_layers( array $layers ) {
		$this->save_meta_value_by_key( self::META_KEY_CER_LAYERS, $layers );
	}

	/**
	 * @return string
	 */
	public function get_layer(): string {
		return $this->get_meta_value_by_key( self::META_KEY_LAYER );
	}

	/**
	 * @param array $layer
	 *
	 * @return void
	 * @throws Exception
	 */
	public function set_layer( array $layer ) {
		$this->save_meta_value_by_key( self::META_KEY_LAYER, json_encode( $layer, JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * @return bool
	 */
	public function is_free(): bool {
		return $this->get_price() === 0.0;
	}

	public function clean_caches() {
		$lp_cache = new LP_Cache();
		$lp_cache->clear( "certificatePostModel/find/{$this->get_id()}" );
	}

	public function check_capabilities_create(): bool {
		return current_user_can( 'edit_' . $this->post_type . 's' );
	}

	public function check_capabilities_update(): bool {
		return current_user_can( 'edit_' . $this->post_type, $this->ID );
	}

	public function get_edit_link( bool $is_course_builder = false ) {
		if ( ( $is_course_builder || lp_cert_is_course_builder() )
			&& class_exists( \LearnPress\CourseBuilder\CourseBuilder::class )
			&& method_exists( \LearnPress\CourseBuilder\CourseBuilder::class, 'get_link_course_builder' ) ) {
			return \LearnPress\CourseBuilder\CourseBuilder::get_link_course_builder( 'certificates/' . $this->ID );
		}

		return parent::get_edit_link();
	}
}
