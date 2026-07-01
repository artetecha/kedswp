<?php
/**
 * Plugin load class.
 *
 * @author   ThimPress
 * @package  LearnPress/Wishlist/Classes
 * @version  4.0.1
 */

// Prevent loading this file directly
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\Wishlist\Elementor\WishListElementorHandler;
use LearnPress\Wishlist\TemplateHooks\CoursesWishlistTemplate;
use LearnPress\Wishlist\TemplateHooks\CourseWishlistTemplate;
use LearnPress\Wishlist\Gutenberg\Blocks\ButtonWishListBlockType;

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'LP_Addon_Wishlist' ) ) {
	/**
	 * Class LP_Addon_Wishlist.
	 */
	class LP_Addon_Wishlist extends LP_Addon {
		public $version         = LP_ADDON_WISHLIST_VER;
		public $require_version = LP_ADDON_WISHLIST_REQUIRE_VER;
		public $plugin_file     = LP_ADDON_WISHLIST_FILE;
		public $text_domain     = 'learnpress-wishlist';

		public static function instance() {
			static $instance = null;
			if ( is_null( $instance ) ) {
				$instance = new self();
			}

			return $instance;
		}

		/**
		 * @var string
		 */
		protected $_tab_slug = '';

		const META_KEY = '_lpr_wish_list';

		/**
		 * LP_Addon_Wishlist constructor.
		 */
		public function __construct() {
			parent::__construct();
			$this->hooks();
			add_filter( 'learn-press/profile-tabs', array( $this, 'wishlist_tab' ), 100, 1 );
			$this->_tab_slug = sanitize_title( __( 'wishlist', 'learnpress-wishlist' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_style' ) );
		}

		/**
		 * Includes files.
		 */
		protected function _includes() {
			include_once $this->plugin_folder_path . '/inc/functions.php';
			if ( is_plugin_active( 'elementor/elementor.php' ) ) {
				//include_once LP_ADDON_WISHLIST_INC . 'Elementor/WishListElementorHandler.php';
				WishListElementorHandler::instance();
			}

			// Rest API
			include_once $this->plugin_folder_path . '/inc/rest-api/class-lp-rest-wishlist-v1-controller.php';
			include_once $this->plugin_folder_path . '/inc/rest-api/class-rest-api.php';
		}

		/**
		 * Init hooks.
		 */
		protected function hooks() {
			add_action( 'learn-press/after-course-buttons', array( $this, 'wishlist_button' ), 100 );
			add_filter( 'learn_press_profile_tab_endpoints', array( $this, 'profile_tab_endpoints' ) );
			add_filter(
				'learn-press/single-course/offline/section-right/info-meta',
				[
					$this,
					'single_course_offline',
				],
				10,
				3
			);
			add_action( 'lp/template/archive-course/description', [ $this, 'load_js_css_on_archive_course' ] );
			add_action( 'learn-press/user-profile', [ $this, 'load_js_css_on_archive_course' ] );
			LP_Request::register_ajax( 'toggle_course_wishlist', array( $this, 'toggle_course_wishlist' ) );
			add_filter( 'learn-press/config/block-elements', array( $this, 'add_block_elements' ) );

			//$this->rewrite_endpoint();
			add_filter(
				'learn-press/single-course/social-share/sections',
				[
					$this,
					'display_on_single_course_modern_layout',
				],
				10,
				3
			);
			// Load js, css on list courses
			add_action(
				'learn-press/list-courses/layout',
				function () {
					wp_enqueue_style( 'lp-course-wishlist' );
					wp_enqueue_script( 'lp-course-wishlist-script' );
				}
			);
			add_action(
				'learn-press/single-instructor/layout',
				function () {
					wp_enqueue_style( 'lp-course-wishlist' );
					wp_enqueue_script( 'lp-course-wishlist-script' );
				}
			);
			// Load js, css for course related section (fires BEFORE AJAX)
			add_action(
				'learn-press/single-course/courses-related/layout',
				function () {
					wp_enqueue_style( 'lp-course-wishlist' );
					wp_enqueue_script( 'lp-course-wishlist-script' );
				}
			);
			add_filter(
				'learn-press/layout/list-courses/item/section-top',
				[
					$this,
					'display_on_list_course_layout',
				],
				10,
				3
			);
			// Hook for course related section (uses different filter)
			add_filter(
				'learn-press/list-courses/related/layout/item/section',
				[
					$this,
					'display_on_related_course_layout',
				],
				10,
				3
			);
		}


		/**
		 * Wishlist scripts.
		 */
		public function enqueue_assets() {
			$ver = LP_ADDON_WISHLIST_VER;
			$min = '.min';
			if ( LP_Debug::is_debug() ) {
				$min = '';
				$ver = uniqid();
			}
			$is_rtl = is_rtl() ? '-rtl' : '';

			wp_register_style(
				'lp-course-wishlist',
				$this->get_plugin_url( "/assets/dist/css/wishlist{$is_rtl}{$min}.css" ),
				[],
				$ver
			);
			wp_register_script(
				'lp-course-wishlist',
				$this->get_plugin_url( "/assets/dist/js/wishlist{$min}.js" ),
				[ 'jquery' ],
				$ver,
				[ 'strategy' => 'async' ]
			);
			wp_register_script(
				'lp-course-wishlist-script',
				$this->get_plugin_url( "/assets/dist/js/lp-wishlist{$min}.js" ),
				[],
				$ver,
				[ 'strategy' => 'async' ]
			);

			// Load js for Eduma theme override old template button.
			$path        = 'addons/' . str_replace( 'learnpress-', '', $this->plugin_folder_name ) . '/';
			$is_override = Template::check_template_is_override( $path . 'button.php' );
			if ( $is_override ) {
				wp_enqueue_script( 'lp-course-wishlist' );
			}
		}

		/**
		 * Register or enqueue admin styles
		 *
		 * @param array $styles
		 *
		 * @return array
		 * @since 4.0.9
		 * @version 1.0.0
		 */
		public function enqueue_admin_style() {
			// Only enqueue on Site Editor or Block Editor screens for performance optimization
			$screen = get_current_screen();
			if ( ! $screen ) {
				return;
			}

			// Check if we're in Site Editor, Block Editor, or widget block editor
			$allowed_screens = [
				'site-editor',           // Site Editor (FSE)
				'widgets',               // Block-based widgets
			];

			$is_block_editor   = $screen->is_block_editor();
			$is_allowed_screen = in_array( $screen->id, $allowed_screens, true );

			if ( ! $is_block_editor && ! $is_allowed_screen ) {
				return;
			}

			$ver = LP_ADDON_WISHLIST_VER;
			$min = '.min';
			if ( LP_Debug::is_debug() ) {
				$min = '';
				$ver = uniqid();
			}
			$is_rtl = is_rtl() ? '-rtl' : '';

			wp_register_style(
				'lp-admin-wishlist-block',
				LP_Addon_Wishlist_Preload::$addon->get_plugin_url( "/assets/dist/css/admin-wishlist-block{$is_rtl}{$min}.css" ),
				[],
				$ver
			);

			wp_enqueue_style( 'lp-admin-wishlist-block' );
		}

		/**
		 * Rewrite endpoint.
		 */
		public function rewrite_endpoint() {
			$endpoint                                       = preg_replace( '!_!', '-', $this->get_tab_slug() );
			LearnPress::instance()->query_vars[ $endpoint ] = $endpoint;
			add_rewrite_endpoint( $endpoint, EP_ROOT | EP_PAGES );
		}

		public function profile_tab_endpoints( $endpoints ) {
			$endpoints[] = $this->get_tab_slug();

			return $endpoints;
		}

		public function toggle_course_wishlist() {
			$nonce = ! empty( $_POST['nonce'] ) ? $_POST['nonce'] : null;
			if ( ! wp_verify_nonce( $nonce, 'course-toggle-wishlist' ) ) {
				die( __( 'You have not permission to do this action', 'learnpress-wishlist' ) );
			}

			$course_id = ! empty( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
			$user_id   = get_current_user_id();

			$user = UserModel::find( $user_id, true );
			if ( ! $user ) {
				return;
			}

			if ( ( get_post_type( $course_id ) != 'lp_course' ) || ! $user_id ) {
				return;
			}
			$state    = ! empty( $_POST['state'] ) ? $_POST['state'] : false;
			$wishlist = self::get_courses_wishlist( $user_id );
			if ( $state === false ) {
				$state = in_array( $course_id, $wishlist ) ? 'off' : 'on';
			}
			$pos = array_search( $course_id, $wishlist );
			if ( $state == 'on' ) {
				if ( $pos === false ) {
					$wishlist[] = $course_id;
				}
			} else {
				if ( $pos !== false ) {
					unset( $wishlist[ $pos ] );
				}
			}
			if ( sizeof( $wishlist ) ) {
				$user->set_meta_value_by_key( '_lpr_wish_list', $wishlist );
			} else {
				delete_user_meta( $user_id, '_lpr_wish_list' );
				unset( $user->meta_data->_lpr_wish_list );
			}

			learn_press_send_json(
				array(
					'state'       => $state,
					'course_id'   => $course_id,
					'user_id'     => $user_id,
					'title'       => $this->_get_state_title( $state ),
					'message'     => '',
					'button_text' => $state != 'on' ? __(
						'Add to Wishlist',
						'learnpress-wishlist'
					) : __( 'Remove from Wishlist', 'learnpress-wishlist' ),
				)
			);
		}

		/**
		 * @param string $state
		 *
		 * @return mixed
		 */
		private function _get_state_title( $state = 'on' ) {
			return $state == 'on' ? __(
				'Remove this course from your wishlist',
				'learnpress-wishlist'
			) : __( 'Add this course to your wishlist', 'learnpress-wishlist' );
		}

		/**
		 * @param string $state
		 *
		 * @return mixed
		 */
		private function _get_state_message( $state = 'on' ) {
			return $state == 'on' ? __(
				'This course added to your wishlist',
				'learnpress-wishlist'
			) : __( 'This course removed from your wishlist', 'learnpress-wishlist' );
		}

		/**
		 * Show wishlist button.
		 *
		 * @param int $course_id
		 * @param string $layout
		 *
		 * @return void
		 */
		public function wishlist_button( $course_id = 0, $layout = 'modern' ) {
			$user_id = get_current_user_id();
			if ( ! $course_id ) {
				$course_id = get_the_ID();
			}

			//   If user or course are invalid then return.
			if ( ! $user_id || ! $course_id ) {
				return;
			}

			$course = CourseModel::find( $course_id, true );
			if ( ! $course ) {
				return;
			}

			$user = UserModel::find( $user_id, true );
			if ( ! $user ) {
				return;
			}

			$path        = 'addons/' . str_replace( 'learnpress-', '', $this->plugin_folder_name ) . '/';
			$is_override = Template::check_template_is_override( $path . 'button.php' );
			if ( $is_override || $layout === 'old' ) {
				// Load template old, for some themes override.
				wp_enqueue_style( 'lp-course-wishlist' );
				wp_enqueue_script( 'lp-course-wishlist' );

				$classes = array( 'course-wishlist' );
				$state   = $this->has_in_wishlist( $course_id, $user ) ? 'on' : 'off';

				if ( $state == 'on' ) {
					$classes[] = 'on';
				}
				$classes = apply_filters( 'learn_press_course_wishlist_button_classes', $classes, $course_id );
				$title   = $this->_get_state_title( $state );

				$this->get_template(
					'button.php',
					compact( 'user_id', 'course_id', 'classes', 'title', 'state' )
				);
			} else {
				echo CourseWishlistTemplate::instance()->html_button_action( $course );
			}
		}

		public function get_tab_slug() {
			return apply_filters( 'learn_press_course_wishlist_tab_slug', $this->_tab_slug, $this );
		}

		/**
		 * Add Wishlist tab to user profile.
		 *
		 * @param $tabs
		 *
		 * @return mixed
		 */
		public function wishlist_tab( $tabs ) {
			$tabs['wishlist'] = array(
				'title'    => __( 'Wishlist', 'learnpress-wishlist' ),
				'slug'     => $this->get_tab_slug(),
				'callback' => array( $this, 'wishlist_tab_content' ),
				'priority' => 20,
				'icon'     => '<i class="lp-icon-heart-o"></i>',
			);

			return $tabs;
		}

		/**
		 * Display content of tab Wishlist.
		 *
		 * @since 4.0.0
		 * @version 1.0.1
		 */
		public function wishlist_tab_content() {
			$profile      = LP_Profile::instance();
			$viewing_user = $profile->get_user();

			$path        = 'addons/' . str_replace( 'learnpress-', '', $this->plugin_folder_name ) . '/';
			$is_override = Template::check_template_is_override( $path . 'user-wishlist.php' );
			if ( $is_override ) {
				$this->get_template(
					'user-wishlist.php',
					array(
						'wishlist' => $this->get_wishlist_courses( $viewing_user->get_id() ),
					)
				);
			} else {
				echo CoursesWishlistTemplate::instance()->html_list_courses();
			}
		}

		public function get_wishlist_courses( $user_id ) {
			$course_ids_wishlist = self::get_courses_wishlist( $user_id );
			if ( ! $course_ids_wishlist ) {
				return array();
			}

			$args     = array(
				'post_type'           => 'lp_course',
				'post__in'            => $course_ids_wishlist,
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
				'posts_per_page'      => - 1,
			);
			$query    = new WP_Query( $args );
			$wishlist = array();
			global $post;
			if ( $query->have_posts() ) :
				while ( $query->have_posts() ) :
					$query->the_post();
					$wishlist[ $post->ID ] = $post;
				endwhile;
			endif;
			wp_reset_postdata();

			return $wishlist;
		}

		/**
		 * Get course ids wishlist
		 *
		 * @param int $user_id
		 *
		 * @return array
		 * @since 4.0.8
		 * @version 1.0.0
		 */
		public static function get_courses_wishlist( int $user_id ): array {
			if ( ! $user_id ) {
				return [];
			}

			$user = UserModel::find( $user_id, true );
			if ( empty( $user ) ) {
				return [];
			}

			$wish_list = $user->get_meta_value_by_key( self::META_KEY, [] );

			return $wish_list;
		}

		/**
		 * Add or remove course to wishlist
		 *
		 * @param UserModel $userModel
		 * @param CourseModel $courseModel
		 *
		 * @return boolean
		 */
		public static function user_add_or_remove_wishlist_course(
			UserModel $userModel,
			CourseModel $courseModel
		): bool {
			$wishlist_courses = self::get_courses_wishlist( $userModel->get_id() );

			$course_id = $courseModel->get_id();

			if ( in_array( $course_id, $wishlist_courses, true ) ) {
				// Remove from wishlist.
				unset( $wishlist_courses[ array_search( $course_id, $wishlist_courses, true ) ] );
				$status_wishlisted = false;
			} else {
				// Add to wishlist.
				$wishlist_courses[] = $course_id;
				$wishlist_courses   = array_unique( $wishlist_courses );
				$status_wishlisted  = true;
			}

			$userModel->set_meta_value_by_key( self::META_KEY, $wishlist_courses );

			return $status_wishlisted;
		}

		/**
		 * Update course ids wishlist for user
		 *
		 * @param UserModel $user
		 * @param array $wish_list
		 *
		 * @return void
		 */
		public static function update_courses_wishlist( UserModel $user, array $wish_list ) {
			$user->set_meta_value_by_key( self::META_KEY, $wish_list );
		}

		/**
		 * Check course has in wishlist
		 *
		 * @param int $course_id
		 * @param UserModel $user
		 *
		 * @return bool
		 * @since 4.0.8
		 * @version 1.0.0
		 */
		public function has_in_wishlist( int $course_id, UserModel $user ): bool {
			$wish_list = self::get_courses_wishlist( $user->get_id() );

			return in_array( $course_id, $wish_list );
		}

		/**
		 * Template button ico wishlist
		 *
		 * @param CourseModel $course
		 * @param UserModel|false $user
		 *
		 * @return string
		 * @since 4.0.8
		 * @version 1.0.1
		 */
		/*public function html_btn_ico_wishlist( CourseModel $course, $user ): string {
			if ( empty( $user ) ) {
				return '';
			}

			wp_enqueue_style( 'lp-course-wishlist' );
			wp_enqueue_script( 'lp-course-wishlist' );

			$is_in_wishlist = $this->has_in_wishlist( $course->get_id(), $user );

			$class                = '';
			$label                = __( 'add to wishlist', 'learnpress-wishlist' );
			$profile_wishlist_url = '';
			if ( $is_in_wishlist ) {
				$class = 'active';
				$label = __( 'view', 'learnpress-wishlist' );

				$profile              = LP_Profile::instance( $user->get_id() );
				$profile_wishlist_url = $profile->get_tab_link( 'wishlist' );
			}

			return sprintf(
				'<a class="lp-item-wishlist %s" data-item-id="%d" href="%s" target="_blank">%s</a>',
				$class,
				$course->get_id(),
				$profile_wishlist_url,
				$label
			);
		}*/

		/**
		 * @param array $section
		 * @param CourseModel $courseModel
		 * @param false|UserModel $user
		 *
		 * @return array
		 * @since 4.0.8
		 * @version 1.0.1
		 */
		public function single_course_offline( array $section, CourseModel $courseModel, $user ): array {
			return Template::insert_value_to_position_array(
				$section,
				'after',
				'buttons',
				'wishlist',
				sprintf(
					'<div class="item-meta">%s</div>',
					CourseWishlistTemplate::instance()->html_button_action( $courseModel )
				)
			);
		}

		public function add_block_elements( $elements ) {
			$elements[] = new ButtonWishListBlockType();

			return $elements;
		}

		/**
		 * Load js and css on archive course
		 */
		public function load_js_css_on_archive_course() {
			wp_enqueue_style( 'lp-course-wishlist' );
			wp_enqueue_script( 'lp-course-wishlist' );
		}

		/**
		 * Show wishlist button on single course modern layout
		 *
		 * @param array $section
		 * @param $courseModel
		 * @param $data
		 *
		 * @return array
		 * @since 4.0.8
		 * @version 1.0.0
		 */
		public function display_on_single_course_modern_layout( $section, $courseModel = null, $data = [] ): array {
			// Skip for block themes, need add via block widget
			if ( wp_is_block_theme() ) {
				return $section;
			}

			// Return early if section is not array or courseModel is not provided
			if ( ! is_array( $section ) || empty( $courseModel ) ) {
				return is_array( $section ) ? $section : [];
			}

			$html_button = CourseWishlistTemplate::instance()->html_button_action( $courseModel );

			$section_new = Template::insert_value_to_position_array(
				$section,
				'before',
				'toggle',
				'wishlist',
				$html_button
			);

			return $section_new;
		}

		public function display_on_list_course_layout( $section, $courseModel = null, $data = [] ): array {
			// Skip for block themes, need add via block widget
			if ( wp_is_block_theme()
				&& ! LP_Page_Controller::is_page_instructor()
				&& ! LP_Page_Controller::is_page_profile()
				&& ! ( isset( $data['id_url'] ) && $data['id_url'] ) == 'wishlist-courses' ) {
				return $section;
			}

			// Return early if section is not array or courseModel is not provided
			if ( ! is_array( $section ) || empty( $courseModel ) ) {
				return is_array( $section ) ? $section : [];
			}

			$html_button = CourseWishlistTemplate::instance()->html_button_action( $courseModel, [ 'layout' => 'icon-only' ] );

			$section_new = Template::insert_value_to_position_array(
				$section,
				'before',
				'img',
				'wishlist',
				$html_button
			);

			return $section_new;
		}

		/**
		 * Show wishlist button on course related layout
		 *
		 * @param array $section
		 * @param CourseModel $courseModel
		 * @param array $data
		 *
		 * @return array
		 * @since 4.0.9
		 */
		public function display_on_related_course_layout( $section, $courseModel = null, $data = [] ): array {
			// Return early if section is not array or courseModel is not provided
			if ( ! is_array( $section ) || empty( $courseModel ) ) {
				return is_array( $section ) ? $section : [];
			}

			$html_button = CourseWishlistTemplate::instance()->html_button_action( $courseModel, [ 'layout' => 'icon-only' ] );

			// Insert wishlist button after wrapper_start (before 'top' key)
			$section_new = Template::insert_value_to_position_array(
				$section,
				'after',
				'wrapper_start',
				'wishlist',
				$html_button
			);

			return $section_new;
		}
	}
}
