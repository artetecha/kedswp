<?php
/**
 * Plugin load class.
 *
 * @author   ThimPress
 * @package  LearnPress/Co-Instructor/Classes
 * @version  3.0.1
 */

use LearnPress\Databases\PostDB;
use LearnPress\Filters\PostFilter;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Addon_Co_Instructor' ) ) {
	/**
	 * Class LP_Addon_Co_Instructor
	 */
	class LP_Addon_Co_Instructor extends LP_Addon {
		public static $instance;

		/**
		 * @var string
		 */
		public $version = LP_ADDON_CO_INSTRUCTOR_VER;

		/**
		 * @var string
		 */
		public $require_version = LP_ADDON_CO_INSTRUCTOR_REQUIRE_VER;

		/**
		 * Path file addon.
		 *
		 * @var string
		 */
		public $plugin_file = LP_ADDON_CO_INSTRUCTOR_FILE;

		public $text_domain = 'learnpress-co-instructor';

		public static function instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * LP_Addon_Co_Instructor constructor.
		 */
		public function __construct() {
			parent::__construct();
			$this->hooks();
		}

		/**
		 * Define Learnpress Co-Instructor constants.
		 *
		 * @since 3.0.0
		 */
		protected function _define_constants() {
			define( 'LP_ADDON_CO_INSTRUCTOR_INC', LP_ADDON_CO_INSTRUCTOR_PATH . '/inc/' );
			define( 'LP_ADDON_CO_INSTRUCTOR_TEMPLATE', LP_ADDON_CO_INSTRUCTOR_PATH . '/templates/' );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @since 3.0.0
		 */
		protected function _includes() {
			include_once LP_ADDON_CO_INSTRUCTOR_INC . 'functions.php';
			include_once LP_ADDON_CO_INSTRUCTOR_INC . 'class-lp-co-instructor-database.php';
		}

		/**
		 * Hook into actions and filters.
		 */
		protected function hooks() {
			if ( current_user_can( ADMIN_ROLE ) || current_user_can( LP_TEACHER_ROLE ) ) {
				add_filter( 'learn-press/profile-tabs', array( $this, 'add_profile_instructor_tab' ) );
				add_filter(
					'learnpress/get-post-type-lp-on-backend',
					array( $this, 'get_items_of_co_instructor' ),
					11
				);
			}

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// add co-instructor settings in admin settings page
			add_filter(
				'learn-press/profile-settings-fields/sub-tabs',
				array( $this, 'co_instructor_settings' ),
				10,
				2
			);

			// Add field Co-Instructors
			add_filter( 'learnpress/course/metabox/tabs', [ $this, 'field_choice_co_instructors' ], 10, 2 );
		}

		/**
		 * @param array $tabs
		 * @param int $post_id
		 *
		 * @return array
		 */
		public function field_choice_co_instructors( array $tabs, int $post_id ): array {
			$users = array();

			$post_author = get_post( $post_id )->post_author ?? '';
			if ( empty( $post_author ) ) {
				return $tabs;
			}

			$data_struct = [
				'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/search-user' ),
				'dataSendApi' => [
					'role_in'   => ADMIN_ROLE . ',' . LP_TEACHER_ROLE,
					'id_not_in' => $post_author,
				],
				'dataType'    => 'users',
				'keyGetValue' => [
					'value'      => 'ID',
					'text'       => '{{display_name}}(#{{ID}}) - {{user_email}}',
					'key_render' => [
						'display_name' => 'display_name',
						'user_email'   => 'user_email',
						'ID'           => 'ID',
					],
				],
				'setting'     => [
					'placeholder' => esc_html__( 'Choose User', 'learnpress' ),
				],
			];

			$tabs['author']['content']['_lp_co_teacher'] = new LP_Meta_Box_Select_Field(
				esc_html__( 'Co-Instructors', 'learnpress-co-instructor' ),
				__( 'Colleagues will work with you.', 'learnpress-co-instructor' ),
				[],
				[
					'options'           => $users,
					'style'             => 'min-width:200px;',
					'tom_select'        => true,
					'multiple'          => true,
					'multil_meta'       => true,
					'custom_attributes' => [ 'data-struct' => htmlentities2( json_encode( $data_struct ) ) ],
				]
			);

			return $tabs;
		}

		/**
		 * Assets.
		 */
		public function enqueue_scripts() {
			$min = '.min';
			$rtl = '';
			$ver = LP_ADDON_CO_INSTRUCTOR_VER;
			if ( LP_Debug::is_debug() ) {
				$ver = uniqid();
				$rtl = is_rtl() ? '-rtl' : '';
				$min = '';
			}
			wp_register_style(
				'lp-co-instructor-css',
				$this->get_plugin_url( "assets/dist/css/co-instructor{$rtl}{$min}.css" ),
				[],
				$ver
			);

			if ( LP_Page_Controller::is_page_single_course() ) {
				wp_enqueue_style( 'lp-co-instructor-css' );
			}
		}

		/**
		 * Pre query items for co-instructor.
		 *
		 * @param WP_Query $query
		 *
		 * @return WP_Query
		 * @version 1.0.2
		 */
		public function get_items_of_co_instructor( WP_Query $query ) {
			$current_user = wp_get_current_user();
			if ( ! $current_user ) {
				return $query;
			}

			if ( user_can( $current_user, ADMIN_ROLE ) ) {
				return $query;
			}

			if ( ! user_can( $current_user, LP_TEACHER_ROLE ) || ! is_admin()
				|| ! function_exists( 'get_current_screen' ) ) {
				return $query;
			}

			$current_screen   = get_current_screen();
			$screen_check_arr = array( 'edit-' . LP_COURSE_CPT );

			if ( ! in_array( $current_screen->id, $screen_check_arr ) ) {
				return $query;
			}

			// Get course ids of co-instructor
			$filter              = new PostFilter();
			$postDB              = PostDB::getInstance();
			$filter->post_type   = LP_COURSE_CPT;
			$filter->only_fields = array( 'DISTINCT(ID) AS ID' );
			$filter->where[]     = "AND (
				post_author = {$current_user->ID}
				OR ID IN (
					SELECT post_id FROM {$postDB->tb_postmeta}
					WHERE meta_key = '_lp_co_teacher'
					AND meta_value = '{$current_user->ID}'
				)
			)";
			$filter->limit       = - 1;

			$post_ids = $postDB->get_posts( $filter );
			$post_ids = $postDB::get_values_by_key( $post_ids );
			$query->set( 'post__in', $post_ids );

			return $query;
		}

		/**
		 * Add co-instructor settings in admin settings.
		 *
		 * @param $settings
		 * @param $object
		 *
		 * @return array
		 */
		public function co_instructor_settings( $settings, $object ) {
			$instructor_setting = array(
				'title'       => esc_html__( 'Instructor', 'learnpress-co-instructor' ),
				'id'          => 'profile_endpoints[profile-instructor]',
				'default'     => 'instructor',
				'type'        => 'text',
				'placeholder' => '',
				'desc'        =>
					__(
						'This is a slug and should be unique.',
						'learnpress-co-instructor'
					) . sprintf(
						' %s <code>[profile/admin/instructor]</code>',
						__( 'Example link is', 'learnpress-co-instructor' )
					),
			);

			$instructor_setting = apply_filters(
				'learn_press_page_settings_item_instructor',
				$instructor_setting,
				$settings,
				$object
			);

			$new_settings = array();

			foreach ( $settings as $index => $setting ) {
				$new_settings[] = $setting;

				if ( isset( $setting['id'] ) && $setting['id'] === 'profile_endpoints[profile-order-details]' ) {
					$new_settings[]     = $instructor_setting;
					$instructor_setting = false;
				}
			}

			if ( $instructor_setting ) {
				$new_settings[] = $instructor_setting;
			}

			return $new_settings;
		}

		/**
		 * Add instructor tab in profile page.
		 *
		 * @param $tabs
		 *
		 * @return array
		 */
		public function add_profile_instructor_tab( $tabs ) {
			$tab = apply_filters(
				'learn-press-co-instructor/profile-tab',
				array(
					'title'    => esc_html__( 'Co-Instructor', 'learnpress-co-instructor' ),
					'icon'     => '<i class="fas fa-user-edit"></i>',
					'callback' => array( $this, 'profile_instructor_tab_content' ),
				),
				$tabs
			);

			$instructor_endpoint = LearnPress::instance()->settings()->get( 'profile_endpoints.profile-instructor', 'instructor' );

			if ( empty( $instructor_endpoint ) || empty( $tab ) ) {
				return $tabs;
			}

			if ( in_array( $instructor_endpoint, array_keys( $tabs ) ) ) {
				return $tabs;
			}

			$instructor = array( $instructor_endpoint => $tab );

			$course_endpoint = LearnPress::instance()->settings()->get( 'profile_endpoints.profile-courses' );

			if ( ! empty( $course_endpoint ) ) {
				$pos  = array_search( $course_endpoint, array_keys( $tabs ) ) + 1;
				$tabs = array_slice( $tabs, 0, $pos, true ) + $instructor + array_slice(
					$tabs,
					$pos,
					count( $tabs ) - 1,
					true
				);
			} else {
				$tabs = $tabs + $instructor;
			}

			return $tabs;
		}

		/**
		 * Get instructor tab content in profile page.
		 *
		 * @param $current
		 * @param $tab
		 * @param $user
		 */
		public function profile_instructor_tab_content( $current, $tab, $user ) {
			learn_press_get_template(
				'profile-tab.php',
				array(
					'user'    => $user,
					'current' => $current,
					'tab'     => $tab,
				),
				learn_press_template_path() . '/addons/co-instructors/',
				LP_ADDON_CO_INSTRUCTOR_PATH . '/templates/'
			);
		}

		/**
		 * Get all course instructors.
		 *
		 * @param CourseModel $course
		 *
		 * @return mixed
		 */
		public function get_instructors( CourseModel $courseModel ) {
			return (array) get_post_meta( $courseModel->get_id(), '_lp_co_teacher' );
		}

		/**
		 * Check condition user can edit course (has is co-instructor).
		 *
		 * @param int $user_id
		 * @param CourseModel $course
		 *
		 * @return bool
		 * @since 4.0.4
		 * @version 1.0.0
		 */
		public function is_co_in_course( int $user_id, CourseModel $course ): bool {
			if ( ! user_can( $user_id, LP_TEACHER_ROLE ) ) {
				return false;
			}

			$instructors = $this->get_instructors( $course );
			if ( ! $instructors ) {
				return false;
			}

			if ( in_array( $user_id, $instructors ) ) {
				return true;
			}

			return false;
		}

		public function get_courses_by_co_instructor( $user_id ) {
			return [];
		}
	}
}
