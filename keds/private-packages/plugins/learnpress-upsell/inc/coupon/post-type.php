<?php
namespace LearnPress\Upsell\Coupon;

class Post_Type {

	private static $instance = null;

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'                  => __( 'Coupons', 'learnpress-upsell' ),
			'singular_name'         => __( 'Coupon', 'learnpress-upsell' ),
			'menu_name'             => _x( 'Coupons', 'Admin menu name', 'learnpress-upsell' ),
			'add_new'               => __( 'Add coupon', 'learnpress-upsell' ),
			'add_new_item'          => __( 'Add new coupon', 'learnpress-upsell' ),
			'edit'                  => __( 'Edit', 'learnpress-upsell' ),
			'edit_item'             => __( 'Edit coupon', 'learnpress-upsell' ),
			'new_item'              => __( 'New coupon', 'learnpress-upsell' ),
			'view_item'             => __( 'View coupon', 'learnpress-upsell' ),
			'search_items'          => __( 'Search coupons', 'learnpress-upsell' ),
			'not_found'             => __( 'No coupons found', 'learnpress-upsell' ),
			'not_found_in_trash'    => __( 'No coupons found in trash', 'learnpress-upsell' ),
			'parent'                => __( 'Parent coupon', 'learnpress-upsell' ),
			'filter_items_list'     => __( 'Filter coupons', 'learnpress-upsell' ),
			'items_list_navigation' => __( 'Coupons navigation', 'learnpress-upsell' ),
			'items_list'            => __( 'Coupons list', 'learnpress-upsell' ),
		);

		register_post_type(
			LP_COUPON_CPT,
			array(
				'labels' => $labels,
				'description' => __( 'This is where you can add new coupons that customers can use in your store.', 'learnpress-upsell' ),
				'public' => false,
				'show_ui' => true,
				'capability_type' => 'learnpress_coupon',
				'map_meta_cap'        => true,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_in_menu'        => false,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
			)
		);
	}

	// Instance.
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
Post_Type::instance();
