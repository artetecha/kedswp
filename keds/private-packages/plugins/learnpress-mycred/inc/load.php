<?php
/**
 * Plugin load class.
 *
 * @author   ThimPress
 * @package  LearnPress/myCRED/Classes
 * @version  3.0.2
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Addon_myCRED' ) ) {
	/**
	 * Class LP_Addon_MyCred.
	 */
	class LP_Addon_myCRED extends LP_Addon {
		public $version            = LP_ADDON_MYCRED_VER;
		public $require_version    = LP_ADDON_MYCRED_REQUIRE_VER;
		public $plugin_file        = LP_ADDON_MYCRED_FILE;
		public $text_domain        = 'learnpress-mycred';
		protected static $instance = null;

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * LP_Addon_Students_List constructor.
		 */
		public function __construct() {
			parent::__construct();
			$this->hooks();
		}

		/**
		 * Define Learnpress myCRED constants.
		 *
		 * @since 3.0.0
		 */
		protected function _define_constants() {
			define( 'LP_ADDON_MYCRED_PATH', dirname( LP_ADDON_MYCRED_FILE ) );
			define( 'LP_ADDON_MYCRED_INC', LP_ADDON_MYCRED_PATH . '/inc/' );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @since 3.0.0
		 */
		protected function _includes() {
			include_once LP_ADDON_MYCRED_PATH . '/inc/hooks/mycred-hook-learnpress-learner.php';
			include_once LP_ADDON_MYCRED_PATH . '/inc/hooks/mycred-hook-learnpress-instructor.php';
		}

		/**
		 * Init hooks.
		 */
		protected function hooks() {
			add_filter( 'mycred_setup_addons', array( $this, 'register_mycred_addon' ), 10, 1 );
			add_filter( 'mycred_load_modules', array( $this, 'load_learnpress_cred_addon' ), 10, 2 );
			add_filter( 'mycred_setup_hooks', array( $this, 'register_hook_instructor' ) );
			add_filter( 'mycred_setup_hooks', array( $this, 'register_hook_learner' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}

		/**
		 * Assets.
		 */
		public function enqueue_assets() {}

		/**
		 * Register Learnpress addon for myCRED.
		 *
		 * @return mixed
		 */
		public function register_mycred_addon( $installed ) {
			$installed['learnpress'] = array(
				'name'        => 'LearnPress',
				'description' => __( 'Integrating with learning management system provided by LearnPress.', 'learnpress-mycred' ),
				'addon_url'   => 'https://thimpress.com/product/mycred-add-on-for-learnpress/',
				'version'     => '3.0.0',
				'author'      => 'ThimPress',
				'author_url'  => 'http://thimpress.com',
				'screenshot'  => 'https://thimpress.com/wp-content/uploads/2015/07/myCRED.jpg',
			);

			return $installed;
		}

		/**
		 * @param $modules
		 * @param $point_types
		 *
		 * @return mixed
		 */
		public function load_learnpress_cred_addon( $modules, $point_types ) {
			$file = LP_ADDON_MYCRED_PATH . '/inc/addon/mycred-addon-learnpress.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				$modules['solo']['learnpress'] = new myCRED_LearnPress_Module();
				$modules['solo']['learnpress']->load();
			}

			return $modules;
		}

		/**
		 * Register hook LearnPress for instructor.
		 *
		 * @param $installed
		 *
		 * @return mixed
		 */
		public function register_hook_instructor( $installed ) {
			$installed['learnpress_instructor'] = array(
				'title'       => __( 'LearnPress: for instructors', 'learnpress-mycred' ),
				'description' => __( 'Award %_plural% to users who are teaching in LearnPress courses system.', 'learnpress-mycred' ),
				'callback'    => array( 'myCred_LearnPress_Instructor' ),
			);

			return $installed;
		}

		/**
		 * Register hook LearnPress for learner.
		 *
		 * @param $installed
		 *
		 * @return mixed
		 */
		public function register_hook_learner( $installed ) {
			$installed['learnpress_learner'] = array(
				'title'       => __( 'LearnPress: for students', 'learnpress-mycred' ),
				'description' => __( 'Award %_plural% to users who are learning in LearnPress courses system.', 'learnpress-mycred' ),
				'callback'    => array( 'myCred_LearnPress_Learner' ),
			);

			return $installed;
		}
	}
}
