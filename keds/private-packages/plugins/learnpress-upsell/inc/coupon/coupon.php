<?php
namespace LearnPress\Upsell\Coupon;

use LearnPress\Upsell\Coupon\Core_Functions;

class Coupon {

	protected $id = 0;

	public function __construct( $data = '' ) {
		if ( is_numeric( $data ) && $data > 0 ) {
			$this->set_id( $data );
		} elseif ( $data instanceof self ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_string( $data ) ) {
			$this->set_id( Core_Functions::instance()->get_coupon_id_by_code( $data ) );
		} elseif ( ! empty( $data->ID ) ) {
			$this->set_id( absint( $data->ID ) );
		} else {
			$this->set_id( 0 );
		}
	}

	public function get_id() {
		return $this->id;
	}

	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	public function get_coupon_code() {
		return Core_Functions::instance()->format_coupon_code( get_the_title( $this->get_id() ) );
	}

	public function get_status() {
		$status = get_post_status( $this->id );

		return $status;
	}

	public function exists() {
		return $this->get_id() > 0 && LP_COUPON_CPT === get_post_type( $this->get_id() );
	}

	public function is_visible() {
		$visible = false;

		if ( $this->get_status() === 'publish' ) {
			$visible = true;
		}

		if ( 'trash' === $this->get_status() ) {
			$visible = false;
		} elseif ( 'publish' !== $this->get_status() && ! current_user_can( 'edit_post', $this->get_id() ) ) {
			$visible = false;
		}

		return apply_filters( 'learnpress_coupon/is_visible', $visible, $this );
	}

	public function get_discount_type() {
		return get_post_meta( $this->get_id(), 'discount_type', true );
	}

	public function get_discount_amount() {
		return get_post_meta( $this->get_id(), 'discount_amount', true );
	}

	public function get_usage_count() {
		return absint( get_post_meta( $this->get_id(), 'usage_count', true ) );
	}

	public function set_usage_count( $count ) {
		return update_post_meta( $this->get_id(), 'usage_count', absint( $count ) );
	}

	public function get_usage_limit() {
		return absint( get_post_meta( $this->get_id(), 'usage_limit', true ) );
	}

	public function get_usage_limit_per_user() {
		return absint( get_post_meta( $this->get_id(), 'usage_limit_per_user', true ) );
	}

	public function get_used_by() {
		return array_filter( (array) get_post_meta( $this->get_id(), '_used_by', false ) );
	}

	// YYYY-MM-DD HH:MM:SS
	public function get_discount_start_date() {
		return get_post_meta( $this->get_id(), 'discount_start_date', true );
	}

	// YYYY-MM-DD HH:MM:SS
	public function get_discount_end_date() {
		return get_post_meta( $this->get_id(), 'discount_end_date', true );
	}

	public function get_include_package_ids() {
		return array_filter( (array) get_post_meta( $this->get_id(), 'include_packages', true ) );
	}

	public function get_include_course_ids() {
		return array_filter( (array) get_post_meta( $this->get_id(), 'include_courses', true ) );
	}

	public function get_include_course_category_ids() {
		return array_filter( (array) get_post_meta( $this->get_id(), 'include_course_categories', true ) );
	}

	public function get_exclude_course_ids() {
		return array_filter( (array) get_post_meta( $this->get_id(), 'exclude_courses', true ) );
	}

	public function get_exclude_package_ids() {
		return array_filter( (array) get_post_meta( $this->get_id(), 'exclude_packages', true ) );
	}

	public function get_exclude_course_category_ids() {
		return array_filter( (array) get_post_meta( $this->get_id(), 'exclude_course_categories', true ) );
	}

	public function get_email_restrictions() {
		return array_filter( (array) get_post_meta( $this->get_id(), 'email_restrictions', true ) );
	}
}
