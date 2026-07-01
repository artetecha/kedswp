<?php
namespace LearnPress\Upsell\Package;

use LP_Addon_Upsell;

class Post_Type {

	private static $instance = null;

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 100 );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Package', 'learnpress-upsell' ),
			'singular_name'      => __( 'Package', 'learnpress-upsell' ),
			'menu_name'          => __( 'Package', 'learnpress-upsell' ),
			'name_admin_bar'     => __( 'Package', 'learnpress-upsell' ),
			'add_new'            => __( 'Add New', 'learnpress-upsell' ),
			'add_new_item'       => __( 'Add New Package', 'learnpress-upsell' ),
			'new_item'           => __( 'New Package', 'learnpress-upsell' ),
			'edit_item'          => __( 'Edit Package', 'learnpress-upsell' ),
			'view_item'          => __( 'View Package', 'learnpress-upsell' ),
			'all_items'          => __( 'Packages', 'learnpress-upsell' ),
			'search_items'       => __( 'Search Packages', 'learnpress-upsell' ),
			'parent_item_colon'  => __( 'Parent Packages:', 'learnpress-upsell' ),
			'not_found'          => __( 'No packages found.', 'learnpress-upsell' ),
			'not_found_in_trash' => __( 'No packages found in Trash.', 'learnpress-upsell' ),
		);

		$archive_page = \LP_Settings::instance()->get( 'package.archive' );
		$has_archive  = ! empty( $archive_page ) && get_post( $archive_page ) ? urldecode( get_page_uri( $archive_page ) ) : 'lp-package';
		$args         = array(
			'labels'              => $labels,
			'description'         => __( 'Description.', 'learnpress-upsell' ),
			'public'              => true,
			'show_ui'             => true,
			'map_meta_cap'        => true,
			'publicly_queryable'  => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => true,
			'query_var'           => true,
			'show_in_rest'        => true,
			'capability_type'     => 'post',
			'exclude_from_search' => false,
			'has_archive'         => $has_archive,
			'hierarchical'        => true,
			'menu_position'       => null,
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
			'rewrite'             => array(
				'slug'         => LP_Addon_Upsell::get_permalink_slug(),
				'hierarchical' => true,
				'with_front'   => false,
				'feeds'        => true,
			),
		);

		register_post_type( LP_PACKAGE_CPT, $args );

		register_taxonomy(
			'learnpress_package_tag',
			array( LP_PACKAGE_CPT ),
			array(
				'labels'                => array(
					'name'                       => __( 'Package Tags', 'learnpress-upsell' ),
					'singular_name'              => __( 'Tag', 'learnpress-upsell' ),
					'search_items'               => __( 'Search Package Tags', 'learnpress-upsell' ),
					'popular_items'              => __( 'Popular Package Tags', 'learnpress-upsell' ),
					'all_items'                  => __( 'All Package Tags', 'learnpress-upsell' ),
					'parent_item'                => null,
					'parent_item_colon'          => null,
					'edit_item'                  => __( 'Edit Package Tag', 'learnpress-upsell' ),
					'update_item'                => __( 'Update Package Tag', 'learnpress-upsell' ),
					'add_new_item'               => __( 'Add A New Package Tag', 'learnpress-upsell' ),
					'new_item_name'              => __( 'New Package Tag Name', 'learnpress-upsell' ),
					'separate_items_with_commas' => __( 'Separate tags with commas', 'learnpress-upsell' ),
					'add_or_remove_items'        => __( 'Add or remove tags', 'learnpress-upsell' ),
					'choose_from_most_used'      => __( 'Choose from the most used tags', 'learnpress-upsell' ),
					'menu_name'                  => __( 'Package Tags', 'learnpress-upsell' ),
				),
				'public'                => true,
				'hierarchical'          => false,
				'show_ui'               => true,
				'show_in_menu'          => false,
				'update_count_callback' => '_update_post_term_count',
				'query_var'             => true,
				'show_in_rest'          => true,
				'rewrite'               => array(
					'slug'       => _x( 'learnpress-package-tag', 'slug', 'learnpress-upsell' ),
					'with_front' => false,
				),
			)
		);
	}

	public function admin_menu() {
		add_action(
			'parent_file',
			function ( $parent_file ) {
				global $current_screen;

				$taxonomy = $current_screen->taxonomy;

				if ( $taxonomy == 'learnpress_package_tag' ) {
					$parent_file = 'learn_press';
				}

				return $parent_file;
			}
		);
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
Post_Type::instance();
