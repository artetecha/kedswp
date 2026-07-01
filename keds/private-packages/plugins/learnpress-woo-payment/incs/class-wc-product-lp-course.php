<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Product' ) ) {
	return;
}

/**
 * Class WC_Product_LP_Course
 */
class WC_Product_LP_Course extends WC_Product {
	/**
	 * @var bool|CourseModel
	 */
	public $course = false;

	public function __construct( $product = 0 ) {
		if ( is_numeric( $product ) && $product > 0 ) {
			$this->set_id( $product );
		} elseif ( $product instanceof self ) {
			$this->set_id( absint( $product->get_id() ) );
		} elseif ( ! empty( $product->ID ) ) {
			$this->set_id( absint( $product->ID ) );
		}

		$this->course = CourseModel::find( $this->get_id(), true );
	}
	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return LP_COURSE_CPT;
	}

	public function __get( $key ) {
		if ( $key === 'id' ) {
			return $this->get_id();
		} elseif ( $key === 'post' ) {
			return get_post( $this->get_id() );
		}

		return parent::__get( $key );
	}

	/**
	 * Get Price Description
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_price( $context = 'view' ) {
		$price = $this->course ? $this->course->get_price() : 0;

		return apply_filters( 'learn-press/woo-course/price', $price, $this->course );
	}

	/**
	 * Returns the product's regular price.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string price
	 */
	public function get_regular_price( $context = 'view' ) {
		$regular_price = $this->course ? $this->course->get_regular_price() : 0;

		return apply_filters( 'learn-press/woo-course/regular-price', $regular_price, $this->course );
	}

	/**
	 * Returns the product's sale price.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string price
	 */
	public function get_sale_price( $context = 'view' ) {
		$sale_price = $this->course ? $this->course->get_sale_price() : 0;

		return apply_filters( 'learn-press/woo-course/sale-price', $sale_price, $this->course );
	}

	/**
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		$name = $this->course ? $this->course->get_title() : '';

		return apply_filters( 'learn-press/woo-course/name', $name, $this->course );
	}

	/**
	 * @param string $context
	 *
	 * @return bool
	 */
	public function exists( $context = 'view' ) {
		return $this->course;
	}

	/**
	 * Check if a product is purchasable
	 */
	public function is_purchasable() {
		return true;
	}

	public function is_sold_individually() {
		return true;
	}

	/**
	 * Check if a product is virtual.
	 *
	 * @return bool
	 */
	public function is_virtual() {
		return apply_filters( 'learn_press_wc_product_lp_course_is_virtual', true, $this );
	}

	public function is_downloadable() {
		return apply_filters( 'learn_press_wc_product_lp_course_is_downloadable', true, $this );
	}

	/**
	 * Get main image ID.
	 *
	 * @since  4.1.5
	 * @param  string $context
	 * @return false|int
	 */
	public function get_image_id( $context = 'view' ) {
		$coursePostModel = new CoursePostModel( $this->course );
		return get_post_thumbnail_id( $coursePostModel );
	}

	/**
	 * Returns the main product image.
	 *
	 * @param  string $size (default: 'woocommerce_thumbnail').
	 * @param  array  $attr Image attributes.
	 * @param  bool   $placeholder True to return $placeholder if no image is found, or false to return an empty string.
	 * @return string
	 */
	public function get_image( $size = 'woocommerce_thumbnail', $attr = array(), $placeholder = true ) {
		$image_id = $this->get_image_id();

		if ( $image_id ) {
			$image = wp_get_attachment_image( $image_id, $size, false, $attr );
		} elseif ( $placeholder ) {
			$image = wc_placeholder_img( $size );
		} else {
			$image = '';
		}
		return apply_filters( 'woocommerce_product_get_image', $image, $this, $size, $attr, $placeholder, $image );
	}

	public function get_status( $context = 'view' ) {
		return $this->course ? $this->course->post_status : '';
	}
}
