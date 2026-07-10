<?php

/**
 * thim functions and definitions
 *
 * @package thim
 */

define( 'THIM_DIR', trailingslashit( get_template_directory() ) );
define( 'THIM_URI', trailingslashit( get_template_directory_uri() ) );

const THIM_THEME_VERSION = '5.9.3';

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 640; /* pixels */
}

function thim_eduma_get_current_url() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	// Strip the home path prefix to avoid duplication.
	$home_path = wp_parse_url( home_url(), PHP_URL_PATH );
	if ( $home_path && '/' !== $home_path && str_starts_with( $request_uri, $home_path ) ) {
		$request_uri = substr( $request_uri, strlen( $home_path ) );
	}

	return esc_url_raw( home_url( $request_uri ) );
}


if ( ! function_exists( 'thim_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function thim_setup() {

		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on thim, use a find and replace
		 * to change 'eduma' to the name of your theme in all the template files
		 */
		load_theme_textdomain( 'eduma', THIM_DIR . 'languages' );
		add_theme_support( 'title-tag' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus(
			array(
				'primary' => esc_html__( 'Primary Menu', 'eduma' ),
			)
		);

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			)
		);
		/* Add WooCommerce support */
		add_theme_support( 'woocommerce' );
		add_theme_support( 'thim-core' );

		// add_theme_support( 'eduma-demo-data' );
		add_theme_support( 'thim-full-widgets' );
		/*
		* Enable support for Post Formats.
		* See http://codex.wordpress.org/Post_Formats
		*/
		add_theme_support(
			'post-formats',
			array(
				'aside',
				'image',
				'video',
				// 'quote',
				'link',
				'gallery',
				'audio',
			)
		);

		// Add support for Block Styles.
		add_theme_support( 'wp-block-styles' );

		// Add support for editor styles.
		add_theme_support( 'editor-styles' );

		// Enqueue editor styles.
		add_editor_style( 'style-editor.css' );

		// Add support for full and wide align images.
		add_theme_support( 'align-wide' );

		// Add support for responsive embedded content.
		add_theme_support( 'responsive-embeds' );

		// Editor color palette.
		add_theme_support(
			'editor-color-palette',
			array(
				array(
					'name'  => esc_html__( 'Primary Color', 'eduma' ),
					'slug'  => 'primary',
					'color' => get_theme_mod( 'thim_body_primary_color', '#ffb606' ),
				),
				array(
					'name'  => esc_html__( 'Title Color', 'eduma' ),
					'slug'  => 'title',
					'color' => get_theme_mod( 'thim_font_title_color', '#333' ),
				),
				array(
					'name'  => esc_html__( 'Sub Title Color', 'eduma' ),
					'slug'  => 'sub-title',
					'color' => '#999',
				),
				array(
					'name'  => esc_html__( 'Border Color', 'eduma' ),
					'slug'  => 'border-input',
					'color' => '#ddd',
				),
			)
		);

		// Add custom editor font sizes.
		add_theme_support(
			'editor-font-sizes',
			array(
				array(
					'name'      => __( 'Small', 'eduma' ),
					'shortName' => __( 'S', 'eduma' ),
					'size'      => 13,
					'slug'      => 'small',
				),
				array(
					'name'      => __( 'Normal', 'eduma' ),
					'shortName' => __( 'M', 'eduma' ),
					'size'      => 15,
					'slug'      => 'normal',
				),
				array(
					'name'      => __( 'Large', 'eduma' ),
					'shortName' => __( 'L', 'eduma' ),
					'size'      => 28,
					'slug'      => 'large',
				),
				array(
					'name'      => __( 'Huge', 'eduma' ),
					'shortName' => __( 'XL', 'eduma' ),
					'size'      => 36,
					'slug'      => 'huge',
				),
			)
		);
		// don't enqueue file css when save customizer
		add_filter( 'thim_core_enqueue_file_css_customizer', '__return_false' );
		// remove wp_global_styles_render_svg_filters
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
		add_filter(
			'thim_prefix_folder_download_data_demo',
			function () {
				return 'eduma';
			}
		);
	}
endif; // thim_setup
add_action( 'after_setup_theme', 'thim_setup' );

/**
 * Register widget area.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_sidebar
 */
if ( ! function_exists( 'thim_widgets_inits' ) ) {
	function thim_widgets_inits() {
		register_sidebar(
			array(
				'name'          => esc_html__( 'Sidebar', 'eduma' ),
				'id'            => 'sidebar',
				'description'   => esc_html__( 'Right Sidebar', 'eduma' ),
				'before_widget' => '<aside id="%1$s" class="widget %2$s">',
				'after_widget'  => '</aside>',
				'before_title'  => '<h4 class="widget-title">',
				'after_title'   => '</h4>',
			)
		);
		if ( get_theme_mod( 'thim_toolbar_show', 'true' ) ) {
			register_sidebar(
				array(
					'name'          => esc_html__( 'Toolbar', 'eduma' ),
					'id'            => 'toolbar',
					'description'   => esc_html__( 'Toolbar Header', 'eduma' ),
					'before_widget' => '<aside id="%1$s" class="widget %2$s">',
					'after_widget'  => '</aside>',
					'before_title'  => '<h4 class="widget-title">',
					'after_title'   => '</h4>',
				)
			);
		}
		register_sidebar(
			array(
				'name'          => esc_html__( 'Menu Right', 'eduma' ),
				'id'            => 'menu_right',
				'description'   => esc_html__( 'Menu Right', 'eduma' ),
				'before_widget' => '<li id="%1$s" class="widget %2$s">',
				'after_widget'  => '</li>',
				'before_title'  => '<h4>',
				'after_title'   => '</h4>',
			)
		);
		if ( 'header_v2' == get_theme_mod( 'thim_header_style', 'header_v1' ) ) {
			register_sidebar(
				array(
					'name'          => esc_html__( 'Menu Top', 'eduma' ),
					'id'            => 'menu_top',
					'description'   => esc_html__( 'Menu top only display with header version 2', 'eduma' ),
					'before_widget' => '<li id="%1$s" class="widget %2$s">',
					'after_widget'  => '</li>',
					'before_title'  => '<h4>',
					'after_title'   => '</h4>',
				)
			);
		}

		register_sidebar(
			array(
				'name'          => esc_html__( 'Footer Top', 'eduma' ),
				'id'            => 'footer_top',
				'description'   => esc_html__( 'Footer Top Sidebar', 'eduma' ),
				'before_widget' => '<aside id="%1$s" class="widget %2$s footer_bottom_widget">',
				'after_widget'  => '</aside>',
				'before_title'  => '<h4 class="widget-title">',
				'after_title'   => '</h4>',
			)
		);

		register_sidebar(
			array(
				'name'          => esc_html__( 'Footer', 'eduma' ),
				'id'            => 'footer',
				'description'   => esc_html__( 'Footer Sidebar', 'eduma' ),
				'before_widget' => '<aside id="%1$s" class="widget %2$s footer_widget">',
				'after_widget'  => '</aside>',
				'before_title'  => '<h4 class="widget-title">',
				'after_title'   => '</h4>',
			)
		);

		if ( 'new-1' != get_theme_mod( 'thim_layout_content_page', 'normal' ) || 'header_v4' != get_theme_mod( 'thim_header_style', 'header_v1' ) ) {
			register_sidebar(
				array(
					'name'          => esc_html__( 'Footer Bottom', 'eduma' ),
					'id'            => 'footer_bottom',
					'description'   => esc_html__( 'Footer Bottom Sidebar', 'eduma' ),
					'before_widget' => '<aside id="%1$s" class="widget %2$s footer_bottom_widget">',
					'after_widget'  => '</aside>',
					'before_title'  => '<h4 class="widget-title">',
					'after_title'   => '</h4>',
				)
			);
		}

		if ( get_theme_mod( 'thim_copyright_show', 'true' ) ) {
			register_sidebar(
				array(
					'name'          => esc_html__( 'Copyright', 'eduma' ),
					'id'            => 'copyright',
					'description'   => esc_html__( 'Copyright', 'eduma' ),
					'before_widget' => '<aside id="%1$s" class="widget %2$s">',
					'after_widget'  => '</aside>',
					'before_title'  => '<h4 class="widget-title">',
					'after_title'   => '</h4>',
				)
			);
		}

		if ( class_exists( 'WooCommerce' ) ) {
			register_sidebar(
				array(
					'name'          => esc_html__( 'Sidebar Shop', 'eduma' ),
					'id'            => 'sidebar_shop',
					'description'   => esc_html__( 'Sidebar Shop', 'eduma' ),
					'before_widget' => '<aside id="%1$s" class="widget %2$s">',
					'after_widget'  => '</aside>',
					'before_title'  => '<h4 class="widget-title">',
					'after_title'   => '</h4>',
				)
			);
		}

		if ( class_exists( 'LearnPress' ) ) {
			register_sidebar(
				array(
					'name'          => esc_html__( 'Sidebar Courses', 'eduma' ),
					'id'            => 'sidebar_courses',
					'description'   => esc_html__( 'Sidebar Courses', 'eduma' ),
					'before_widget' => '<aside id="%1$s" class="widget %2$s">',
					'after_widget'  => '</aside>',
					'before_title'  => '<h4 class="widget-title">',
					'after_title'   => '</h4>',
				)
			);
		}

		if ( class_exists( 'WPEMS' ) ) {
			register_sidebar(
				array(
					'name'          => esc_html__( 'Sidebar Events', 'eduma' ),
					'id'            => 'sidebar_events',
					'description'   => esc_html__( 'Sidebar Events', 'eduma' ),
					'before_widget' => '<aside id="%1$s" class="widget %2$s">',
					'after_widget'  => '</aside>',
					'before_title'  => '<h4 class="widget-title">',
					'after_title'   => '</h4>',
				)
			);
		}
		if ( 'header_v3' == get_theme_mod( 'thim_header_style', 'header_v1' ) ) {
			register_sidebar(
				array(
					'name'          => esc_html__( 'Header', 'eduma' ),
					'id'            => 'header',
					'description'   => esc_html__( 'Sidebar display on header version 3', 'eduma' ),
					'before_widget' => '<aside id="%1$s" class="widget %2$s footer_bottom_widget">',
					'after_widget'  => '</aside>',
					'before_title'  => '<h4 class="widget-title">',
					'after_title'   => '</h4>',
				)
			);
		}
		/**
		 * Feature create sidebar in wp-admin.
		 * Do not remove this.
		 */
		$sidebars = apply_filters( 'thim_core_list_sidebar', array() );
		if ( count( $sidebars ) > 0 ) {
			foreach ( $sidebars as $sidebar ) {
				$new_sidebar = array(
					'name'          => $sidebar['name'],
					'id'            => $sidebar['id'],
					'description'   => esc_html__( 'Custom widgets area.', 'eduma' ),
					'before_widget' => '<aside id="%1$s" class="widget %2$s footer_bottom_widget">',
					'after_widget'  => '</aside>',
					'before_title'  => '<h4 class="widget-title">',
					'after_title'   => '</h4>',
				);

				register_sidebar( $new_sidebar );
			}
		}
	}
}

add_action( 'widgets_init', 'thim_widgets_inits' );

if ( ! function_exists( 'thim_styles' ) ) {
	function thim_styles() {
		$v_asset = THIM_THEME_VERSION;
		$min     = '-min';
		if ( class_exists( 'LP_Debug' ) && LP_Debug::is_debug() ) {
			$v_asset = uniqid();
			$min     = '';
		}
		//      wp_deregister_style( 'font-awesome' );
		if ( ! class_exists( 'TP' ) ) {
			wp_enqueue_style( 'thim-fontgoogle-default', 'https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap', array(), THIM_THEME_VERSION );
		}

		// deregister font awesome in LP
		wp_register_style( 'font-awesome-5-all', THIM_URI . 'assets/css/all.min.css', array(), THIM_THEME_VERSION );
		//      wp_register_style( 'font-awesome-4-shim', THIM_URI . 'assets/css/v4-shims.min.css', array(), THIM_THEME_VERSION );
		wp_register_style( 'ionicons', THIM_URI . 'assets/css/ionicons.min.css' );
		wp_register_style( 'font-pe-icon-7', THIM_URI . 'assets/css/font-pe-icon-7.css' );
		wp_register_style( 'flaticon', THIM_URI . 'assets/css/flaticon.css' );

		wp_register_style( 'thim-portfolio', THIM_URI . 'assets/css/libs/portfolio.css', array(), THIM_THEME_VERSION );
		wp_enqueue_style( 'thim-style', get_stylesheet_uri(), array(), $v_asset );
		if ( is_rtl() ) {
			// Load RTL CSS.
			wp_enqueue_style( 'thim-style-rtl', THIM_URI . 'rtl' . $min . '.css', array(), $v_asset );
		}

		// css inline
		wp_add_inline_style(
			'thim-style',
			apply_filters( 'thim_get_var_css_customizer', '' )
		);

		// fix font icon for child theme
		if ( apply_filters( 'learn_press_child_in_parrent_template_path', '' ) ) {
			wp_enqueue_style( 'ionicons' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'thim_styles', 1001 );

/**
 * Enqueue scripts.
 */
if ( ! function_exists( 'thim_scripts' ) ) {
	function thim_scripts() {
		$v_asset = THIM_THEME_VERSION;
		$min     = '.min';
		if ( class_exists( 'LP_Debug' ) && LP_Debug::is_debug() ) {
			$v_asset = uniqid();
			$min     = '';
		}

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
		// New script update
		wp_register_script( 'thim-content-slider', THIM_URI . 'assets/js/thim-content-slider.js', array( 'jquery' ), THIM_THEME_VERSION, true );
		wp_register_script( 'flexslider', THIM_URI . 'assets/js/jquery.flexslider-min.js', array( 'jquery' ), THIM_THEME_VERSION, true );
		wp_register_script( 'magnific-popup', THIM_URI . 'assets/js/jquery.magnific-popup.min.js', array( 'jquery' ), THIM_THEME_VERSION, true );
		wp_register_script( 'mb-commingsoon', THIM_URI . 'assets/js/mb-commingsoon.min.js', array( 'jquery' ), THIM_THEME_VERSION, true );
		wp_register_script( 'isotope', THIM_URI . 'assets/js/isotope.pkgd.min.js', array( 'jquery' ), THIM_THEME_VERSION, true );
		wp_register_script( 'thim_simple_slider', THIM_URI . 'assets/js/thim_simple_slider.min.js', array( 'jquery' ), THIM_THEME_VERSION, true );
		wp_register_script( 'thim-portfolio-appear', THIM_URI . 'assets/js/jquery.appear.min.js', array( 'jquery' ), THIM_THEME_VERSION, true );
		wp_register_script( 'thim-portfolio-widget', THIM_URI . 'assets/js/portfolio.min.js', array( 'jquery', 'isotope' ), THIM_THEME_VERSION, true );
		wp_register_script( 'search-course-widget', THIM_URI . 'assets/js/search-course' . $min . '.js', array( 'jquery' ), THIM_THEME_VERSION, true );
		wp_register_script( 'waypoints', THIM_URI . 'assets/js/jquery.waypoints.min.js', array( 'jquery' ), THIM_THEME_VERSION, true );
		wp_register_script( 'thim-CountTo', THIM_URI . 'assets/js/jquery.countTo.min.js', array( 'jquery' ), THIM_THEME_VERSION, true );

		wp_enqueue_script( 'thim-main', THIM_URI . 'assets/js/main.min.js', array( 'jquery', 'imagesloaded' ), THIM_THEME_VERSION, true );

		// thim archive api v2
		if ( thim_is_new_learnpress( '4.1.6' ) ) {
			if ( class_exists( 'LP_Page_Controller' ) && LP_PAGE_COURSES === LP_Page_Controller::page_current() ) {
				wp_enqueue_script( 'thim-scripts-course-filter', THIM_URI . 'assets/js/thim-course-filter-v2' . $min . '.js', array( 'wp-hooks' ), $v_asset, true );
			}
		} else {
			wp_enqueue_script( 'thim-scripts-course-filter', THIM_URI . 'assets/js/thim-course-filter' . $min . '.js', array( 'jquery' ), $v_asset, true );
		}

		wp_enqueue_script( 'thim-scripts', THIM_URI . 'assets/js/thim-scripts' . $min . '.js', array( 'jquery' ), $v_asset, true );

		if ( get_post_type() == 'portfolio' && ( is_category() || is_archive() || is_singular( 'portfolio' ) ) ) {
			wp_enqueue_script( 'thim-portfolio-appear' );
			wp_enqueue_script( 'thim-portfolio-widget' );
			wp_enqueue_style( 'thim-portfolio' );
		}
		// Enqueue event archive pagination script and style
		if ( get_theme_mod( 'thim_event_archive_pagition', false ) && ( is_post_type_archive( 'tp_event' ) || is_tax( 'tp_event_category' ) ) ) {
			wp_enqueue_script( 'thim-event-pagination', THIM_URI . 'assets/js/event-pagination.js', array( 'jquery' ), $v_asset, true );
			wp_localize_script(
				'thim-event-pagination',
				'thim_event_ajax',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'thim_event_pagination_nonce' ),
				)
			);
		}
		wp_localize_script(
			'thim-scripts',
			'thim_ajax_vars',
			array(
				'nonce_gallery_popup'     => wp_create_nonce( 'thim_gallery_popup_nonce' ),
				'nonce_load_content_post' => wp_create_nonce( 'thim_load_content_post_nonce' ),
				'nonce_courses_searching' => wp_create_nonce( 'thim_courses_searching_nonce' ),
			)
		);

		wp_dequeue_script( 'framework-bootstrap' );
		wp_dequeue_script( 'bootstrap' );

		// Remove some scripts LearnPress
		wp_dequeue_style( 'tipsy' );
		wp_dequeue_style( 'certificate' );
		wp_dequeue_style( 'fib' );
		wp_dequeue_style( 'sorting-choice' );
		wp_dequeue_style( 'course-wishlist-style' );
		wp_dequeue_script( 'tipsy' );
		wp_dequeue_script( 'course-wishlist-script' );

		if ( is_front_page() ) {
			wp_dequeue_script( 'webfont' );
			wp_dequeue_script( 'fabric-js' );
			wp_dequeue_script( 'certificate' );
		}

		if ( ! thim_use_bbpress() ) {
			wp_dequeue_style( 'bbp-default' );
			wp_dequeue_script( 'bbpress-editor' );
		}

		// Dequeue wp-event-manager styles and scripts if not using event pages
		if ( class_exists( 'WPEMS' ) ) {
			$dequeue_style_event = true;
			if ( get_post_type() == 'tp_event' || is_singular( 'tp_event' ) ) {
				$dequeue_style_event = false;
			}

			if ( $dequeue_style_event ) {
				wp_dequeue_style( 'wpems-countdown-css' );
				wp_dequeue_style( 'wpems-owl-carousel-css' );
				wp_dequeue_style( 'wpems-fronted-css' );
				wp_dequeue_style( 'wpems-magnific-popup-css' );
				wp_dequeue_script( 'wpems-magnific-popup-js' );
				wp_dequeue_script( 'wpems-countdown-plugin-js' );
				wp_dequeue_script( 'wpems-countdown-js' );
				wp_dequeue_script( 'wpems-owl-carousel-js' );
				wp_dequeue_script( 'wpems-frontend-js' );
			}
		}

		// Dequeue woocommerce styles and scripts if not using woocommerce pages
		if ( class_exists( 'WooCommerce' ) && ! is_woocommerce() && ! is_shop() && ! is_product_category() && ! is_product() && ! is_cart() && ! is_checkout() ) {
			wp_dequeue_style( 'wc-blocks-vendors-style' );
			wp_dequeue_style( 'wc-blocks-style' );
			wp_dequeue_style( 'woocommerce-layout' );
			wp_dequeue_style( 'woocommerce-general' );
			wp_dequeue_script( 'woocommerce' );
			wp_dequeue_script( 'jquery-blockui' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'thim_scripts', 1000 );
function thim_custom_admin_scripts() {
	wp_enqueue_script( 'thim-admin-custom-script', THIM_URI . 'assets/js/admin-custom-script.js', array( 'jquery' ), THIM_THEME_VERSION, true );
	wp_enqueue_style( 'thim-admin-theme-style', THIM_URI . 'assets/css/thim-admin.css', array(), THIM_THEME_VERSION );

	// add debug change and check demo import in Customizer -> General -> Utilities
	if ( isset( $_GET['demo_imported'] ) ) {
		wp_add_inline_style(
			'thim-admin-theme-style',
			'body #customize-control-thim_page_builder_chosen{display:block!important;}'
		);
	}

	wp_register_style( 'thim-admin-font-icon7', THIM_URI . 'assets/css/font-pe-icon-7.css', array(), THIM_THEME_VERSION );
	wp_register_style( 'thim-admin-font-flaticon', THIM_URI . 'assets/css/flaticon.css', array(), THIM_THEME_VERSION );
	wp_register_style( 'thim-admin-ionicons', THIM_URI . 'assets/css/ionicons.min.css', array(), THIM_THEME_VERSION );
}

add_action( 'admin_enqueue_scripts', 'thim_custom_admin_scripts' );

// Custom functions.
require_once THIM_DIR . 'inc/custom-functions.php';

// Register functions.
require_once THIM_DIR . 'inc/register-functions.php';

/**
 * Custom template tags for this theme.
 */
require_once THIM_DIR . 'inc/template-tags.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once THIM_DIR . 'woocommerce/woocommerce.php';
}

if ( class_exists( 'WPEMS' ) ) {
	require_once THIM_DIR . 'wp-events-manager/events.php';
}

if ( class_exists( 'BuddyPress' ) ) {
	require_once THIM_DIR . 'buddypress/bp-custom.php';
}

// logo
require_once THIM_DIR . 'inc/header/logo.php';

// Remove references to SiteOrigin Premium
add_filter( 'siteorigin_premium_upgrade_teaser', '__return_false' );

require_once THIM_DIR . 'inc/variables-css.php';
// For use thim-core
require_once THIM_DIR . 'inc/thim-core-function.php';
// Menu footer for
require_once THIM_DIR . 'inc/navbar-mobile.php';

// Migrate customizer options
require_once THIM_DIR . 'inc/migration-options.php';
// For use thim-portfolio
if ( class_exists( 'Thim_Portfolio' ) ) {
	require_once THIM_DIR . 'portfolio/portfolio.php';
}
