<?php

namespace Thim_EL_Kit\Modules\MegaMenu;

use Thim_EL_Kit\SingletonTrait;
use Thim_EL_Kit\Settings;

class Init {
	use SingletonTrait;

	public function __construct() {
		if ( ! Settings::instance()->get_enable_modules( 'megamenu' ) ) {
			return;
		}

		add_action( 'thim_ekit/admin/enqueue', array( $this, 'admin_enqueue' ) );
		add_action( 'before_delete_post', array( $this, 'before_delete_post' ), 10, 2 );
		add_filter( 'single_template', array( $this, 'load_canvas_template' ) );
		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'add_button_mega_menu' ), 10, 5 );
		add_action( 'admin_head-nav-menus.php', array( $this, 'add_meta_box' ) );
		add_filter( 'wp_nav_menu_args', array( $this, 'modify_nav_menu_args' ), 99999 );

		// for load atomic Elementor v4 local styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_atomic_assets_megamenu' ), 5 );

		$this->includes();
	}

	public function includes() {
		require_once THIM_EKIT_PLUGIN_PATH . 'inc/modules/mega-menu/class-post-type.php';
		require_once THIM_EKIT_PLUGIN_PATH . 'inc/modules/mega-menu/class-rest-api.php';
		require_once THIM_EKIT_PLUGIN_PATH . 'inc/modules/mega-menu/class-menu-walker.php';
	}

	/**
	 * Fires just before the move buttons of a nav menu item in the menu editor.
	 *
	 * @param int $item_id Menu item ID.
	 * @param WP_Post $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param stdClass $args An object of menu item arguments.
	 * @param int $id Nav menu ID.
	 *
	 * @since WP 5.4.0
	 *
	 */
	public function add_button_mega_menu( $item_id, $item, $depth, $args, $id ) {
		if ( $depth == 0 ) {
			echo '<div class="thim-ekits-menu" data-id="' . absint( $item_id ) . '"></div>';
		}
	}

	public function modify_nav_menu_args( $args ) {
		$nav_locations = get_nav_menu_locations();

		$location = $args['theme_location'] ?? false;

		if ( ! empty( $nav_locations ) && $location ) {
			$menu_id = $nav_locations[ $location ];

			if ( $menu_id ) {
				$enable = get_term_meta( $menu_id, Rest_API::ENABLE_MEGA_MENU, true );

				if ( absint( $enable ) ) {
					$args = wp_parse_args(
						array( 
							'menu'       => $menu_id,
							'menu_class' => 'thim-ekits-menu__nav', 
							'walker'     => new Main_Walker(),
						),
						$args
					);
				}
			}
		}

		return $args;
	}

	/**
	 * Add Script Mega Menu to Menu Screen.
	 *
	 * @return void
	 */
	public function admin_enqueue() {
		$screen = get_current_screen();

		$version = THIM_EKIT_VERSION;

		if ( THIM_EKIT_DEV ) {
			$version = time();
		}

		if ( 'nav-menus' === $screen->base ) {
			wp_enqueue_media();
			wp_enqueue_script(
				'thim-ekit-megamenu',
				THIM_EKIT_PLUGIN_URL . 'build/menu.js',
				[
					'lodash',
					'wp-block-editor',
					'wp-components',
					'wp-element',
					'wp-hooks',
					'wp-i18n',
					'wp-media-utils',
					'wp-polyfill',
					'wp-primitives',
					'wp-url',
				],
				$version,
				[ 'strategy' => 'defer' ]
			);
			wp_enqueue_style(
				'thim-ekit-megamenu',
				THIM_EKIT_PLUGIN_URL . 'build/menu.css',
				array( 'wp-components' ),
				$version
			);
			wp_enqueue_style(
				'thim-ekit-admin-font-awesome',
				ELEMENTOR_ASSETS_URL . 'lib/font-awesome/css/all.css',
				array(),
				$version
			);

			$this->localize();
		}
	}

	public function localize() {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$brands_path  = ELEMENTOR_ASSETS_PATH . 'lib/font-awesome/js/brands.js';
		$regular_path = ELEMENTOR_ASSETS_PATH . 'lib/font-awesome/js/regular.js';
		$solid_path   = ELEMENTOR_ASSETS_PATH . 'lib/font-awesome/js/solid.js';

		if ( file_exists( $brands_path ) ) {
			$brands = $wp_filesystem->get_contents( $brands_path );
		}
		if ( file_exists( $regular_path ) ) {
			$regular = $wp_filesystem->get_contents( $regular_path );
		}
		if ( file_exists( $solid_path ) ) {
			$solid = $wp_filesystem->get_contents( $solid_path );
		}

		wp_localize_script(
			'thim-ekit-megamenu',
			'thimEKitMenu',
			apply_filters(
				'thim_ekit/admin/menu/enqueue/localize',
				array(
					'fontAwesome'   => array(
						'brands'  => isset( $brands ) ? json_decode( $brands, true ) : array(),
						'regular' => isset( $regular ) ? json_decode( $regular, true ) : array(),
						'solid'   => isset( $solid ) ? json_decode( $solid, true ) : array(),
					),
					'menuContainer' => apply_filters( 'thim_ekit/mega_menu/menu_container/class', false ),
				)
			)
		);
	}

	/**
	 * Delete Mega Menu before menu is deleted.
	 *
	 * @param integer $post_id
	 * @param \WP_Post $post
	 *
	 * @return void
	 */
	public function before_delete_post( int $post_id, \WP_Post $post ) {
		if ( $post_id && is_nav_menu_item( $post_id ) ) {
			$mega_menu_id = get_post_meta( $post_id, Rest_API::META_KEY, true );

			if ( ! empty( $mega_menu_id ) ) {
				wp_delete_post( $mega_menu_id, true );
			}
		}
	}

	/**
	 *  Single template function which will choose our template
	 *
	 * @param [type] $single_template
	 *
	 * @return void
	 */
	public function load_canvas_template( $single_template ) {
		global $post;

		if ( $post->post_type === Custom_Post_Type::CPT ) {
			$elementor_template = ELEMENTOR_PATH . '/modules/page-templates/templates/canvas.php';

			if ( file_exists( $elementor_template ) ) {
				return $elementor_template;
			}
		}

		return $single_template;
	}

	public function add_meta_box() {
		global $pagenow;

		if ( 'nav-menus.php' === $pagenow ) {
			add_meta_box(
				'thim_ekit-menu-settings',
				esc_html__( 'Thim Menu Settings', 'thim-elementor-kit' ),
				array( $this, 'render_metabox' ),
				'nav-menus',
				'side',
				'high'
			);
		}
	}

	public function render_metabox() {
		?>
		<div id="thim-ekits-menu__settings"></div>
		<?php
	}

	public function get_thim_ekits_menu_ids() {
		$args = [
			'post_type'              => Custom_Post_Type::CPT,
			'posts_per_page'         => -1,
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		];

		$query = new \WP_Query( $args );

		return $query->posts;
	}

	public function register_atomic_assets_megamenu() {
		$mega_menu_ids = $this->get_thim_ekits_menu_ids();

		if ( ! empty( $mega_menu_ids ) ) {
			foreach ( $mega_menu_ids as $id ) {
				do_action( 'elementor/post/render', $id );
			}
		}
	}
}

Init::instance();
