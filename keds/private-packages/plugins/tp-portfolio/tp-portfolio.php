<?php
/*
Plugin Name: Thim Portfolio
Plugin URI: https://thimpress.com
Description: A plugin that allows you to show off your portfolio.
Author: ThimPress
Version: 2.1
Author URI: https://thimpress.com
Requires at least: 3.8
Tested up to: 6.1.0
Text Domain: tp-portfolio
Domain Path: /languages/
*/
/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! defined( 'TP_PORTFOLIO_PLUGIN_FILE' ) ) {
	define( 'TP_PORTFOLIO_PLUGIN_FILE', __FILE__ );
	define( 'THIM_PORTFOLIO_VERSION', '2.0.0' );
	define( 'CORE_PLUGIN_URL', untrailingslashit( plugins_url( '/', TP_PORTFOLIO_PLUGIN_FILE ) ) );
	define( 'CORE_PLUGIN_PATH', untrailingslashit( plugin_dir_path( TP_PORTFOLIO_PLUGIN_FILE ) ) );
}

if ( ! class_exists( 'Thim_Portfolio' ) ) {
	/**
	 * Class Thim_Portfolio.
	 */
	class Thim_Portfolio {

		/**
		 * Current version of the plugin
		 *
		 * @var string
		 */
		public $version = THIM_PORTFOLIO_VERSION;

		/**
		 * The single instance of the class
		 *
		 * @var Thim_Portfolio object
		 */
		private static $_instance = null;

		/**
		 * Thim_Portfolio constructor.
		 */
		public function __construct() {
			// Prevent duplicate unwanted hooks
			if ( self::$_instance ) {
				return;
			}
			self::$_instance = $this;

			// include files
			$this->includes();
			// hooks
			$this->init_hooks();
		}

		/**
		 * Includes files.
		 */
		public function includes() {
			require_once 'inc/functions.php';
			require_once 'inc/template-hoooks.php';
			require_once 'inc/class-tp-post-types.php';
			// include metabox
			require_once 'inc/class-tp-meta-box.php';
		}

		/**
		 * Init hooks.
		 */
		public function init_hooks() {
			add_action( 'admin_init', array( $this, 'thim_register_meta_boxes' ), 10 );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_scripts' ) );
			add_filter( 'get_the_archive_title', array( $this, 'remove_archive_title' ), 10, 2 );
		}

		public function load_frontend_scripts() {
			wp_register_style( 'slick', CORE_PLUGIN_URL . '/assets/css/slick.css', array(), THIM_PORTFOLIO_VERSION, 'all' );
			wp_enqueue_style( 'tp-portfolio-style', CORE_PLUGIN_URL . '/assets/css/style.css', array(), THIM_PORTFOLIO_VERSION, 'all' );
			wp_register_script( 'slick', CORE_PLUGIN_URL . '/assets/js/slick.min.js', array( 'jquery' ), THIM_PORTFOLIO_VERSION, true );
			wp_register_script( 'tp-portfolio-scripts', CORE_PLUGIN_URL . '/assets/js/tp-portfolio.js', array( 'jquery', 'slick' ), THIM_PORTFOLIO_VERSION, true );
		}

		public function load_admin_scripts() {
			wp_enqueue_style( 'tp-portfolio-admin-style', CORE_PLUGIN_URL . '/assets/css/admin.css', array(), THIM_PORTFOLIO_VERSION, 'all' );
			wp_enqueue_script( 'tp-portfolio-meta-box', CORE_PLUGIN_URL . '/assets/js/admin-meta-box.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-dialog', 'media-upload' ), THIM_PORTFOLIO_VERSION, true );
		}

		/**
		 * Register meta boxes via a filter
		 * Advantages:
		 * - prevents incorrect hook
		 * - prevents duplicated global variables
		 * - allows users to remove/hide registered meta boxes
		 * - no need to check for class existences
		 *
		 * @return void
		 */
		function thim_register_meta_boxes() {
			$meta_boxes = apply_filters( 'thim_meta_boxes', array() );
			foreach ( $meta_boxes as $meta_box ) {
				new Thim_Meta_Box( $meta_box );
			}
		}

		public function remove_archive_title( $title ) {
			if ( is_post_type_archive( 'portfolio' ) ) {
				$title = post_type_archive_title( '', false );
			} elseif ( is_tax( array( 'portfolio_category', 'portfolio_tag' ) ) ) {
				$title = single_term_title( '', false );
			} else {
				$title = preg_replace( '/^.*?: /', '', $title );
			}

			return $title;
		}

		/**
		 * Main plugin instance.
		 *
		 * @return Thim_Portfolio
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}
}

Thim_Portfolio::instance();
