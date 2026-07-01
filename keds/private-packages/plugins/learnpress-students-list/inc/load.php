<?php
/**
 * Plugin load class.
 *
 * @author   ThimPress
 * @package  LearnPress/Students-List/Classes
 * @version  3.0.0
 */

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\StudentsList\StudentsListShortCode;
use LearnPress\StudentsList\StudentsListTemplate;
use LearnPress\StudentsList\StudentsListWidget;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Addon_Students_List' ) ) {
	/**
	 * Class LP_Addon_Students_List
	 */
	class LP_Addon_Students_List extends LP_Addon {
		public static $instance;
		/**
		 * @var string
		 */
		public $version = LP_ADDON_STUDENTS_LIST_VER;

		/**
		 * @var string
		 */
		public $require_version = LP_ADDON_STUDENTS_LIST_REQUIRE_VER;

		/**
		 * Path file addon
		 *
		 * @var string
		 */
		public $plugin_file = LP_ADDON_STUDENTS_LIST_FILE;

		public $text_domain = 'learnpress-students-list';

		/**
		 * @return LP_Addon_Students_List
		 */
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
		 * Define Learnpress Students List constants.
		 *
		 * @since 3.0.0
		 */
		protected function _define_constants() {
			define( 'LP_ADDON_STUDENTS_LIST_PATH', dirname( LP_ADDON_STUDENTS_LIST_FILE ) );
			define( 'LP_ADDON_STUDENTS_LIST_INC', LP_ADDON_STUDENTS_LIST_PATH . '/inc/' );
			define( 'LP_ADDON_STUDENTS_LIST_TEMPLATE', LP_ADDON_STUDENTS_LIST_PATH . '/templates/' );
		}

		/**
		 * Includes.
		 */
		protected function _includes() {
			StudentsListTemplate::instance();
			StudentsListShortCode::instance();
		}

		/**
		 * Init hooks.
		 */
		protected function hooks() {
			add_filter(
				'lp/course/meta-box/fields/general',
				function ( $data ) {
					$data['_lp_hide_students_list'] = new LP_Meta_Box_Checkbox_Field(
						esc_html__( 'Students List', 'learnpress-students-list' ),
						esc_html__( 'Hide the students list in each individual course.', 'learnpress-students-list' ),
						'no'
					);

					return $data;
				}
			);

			// add student list tab in single course
			add_filter( 'learn-press/course-tabs', array( $this, 'add_single_course_students_list_tab' ), 5 );
			// Show list students after section instructor
			add_filter( 'learn-press/single-course/modern/section-instructor', array( $this, 'single_course_show_list_students' ), 9, 3 );

			// Enqueue scripts
			add_filter( 'learn-press/frontend-default-scripts', array( $this, 'enqueue_js' ) );
			// Enqueue styles
			add_filter( 'learn-press/frontend-default-styles', array( $this, 'enqueue_style' ) );

			// Add settings
			add_filter( 'learn-press/courses-settings-fields', [ $this, 'settings' ], 10, 1 );

			// Add widget
			add_action(
				'learn-press/widgets/register',
				function ( $widgets ) {
					$widgets[] = StudentsListWidget::instance();
					return $widgets;
				}
			);
		}

		/**
		 * Register or enqueue js
		 *
		 * @param array $scripts
		 *
		 * @return array
		 * @since 4.0.1
		 * @version 1.0.1
		 */
		public function enqueue_js( array $scripts ): array {
			$min = '.min';
			if ( LP_Debug::is_debug() ) {
				$min = '';
			}

			$scripts['addon-lp-students-list'] = new LP_Asset_Key(
				$this->get_plugin_url( "assets/dist/js/students-list{$min}.js" ),
				[],
				[],
				1,
				0,
				LP_ADDON_STUDENTS_LIST_VER,
				[ 'strategy' => 'async' ]
			);

			return $scripts;
		}

		/**
		 * Register or enqueue styles
		 *
		 * @param array $styles
		 *
		 * @return array
		 * @since 4.0.2
		 * @version 1.0.0
		 */
		public function enqueue_style( array $styles ): array {
			$min    = '.min';
			$is_rtl = is_rtl() ? '-rtl' : '';
			if ( LP_Debug::is_debug() ) {
				$min = '';
			}
			$url = $this->get_plugin_url( "assets/dist/css/students-list{$is_rtl}{$min}.css" );

			$styles['addon-lp-students-list'] = new LP_Asset_Key(
				$url,
				[],
				[],
				1,
				0,
				LP_ADDON_STUDENTS_LIST_VER
			);

			return $styles;
		}

		/**
		 * Students list tab in single course page.
		 *
		 * @param $tabs
		 *
		 * @return mixed
		 */
		public function add_single_course_students_list_tab( $tabs ) {
			$course = CourseModel::find( get_the_ID(), true );
			if ( ! $course ) {
				return $tabs;
			}

			if ( $this->enable_hide_students_list( $course ) ) {
				return $tabs;
			}

			$tabs['students-list'] = array(
				'title'    => __( 'Students List', 'learnpress-announcements' ),
				'priority' => 40,
				'callback' => array( $this, 'single_course_students_list_tab_content' ),
			);

			return $tabs;
		}

		/**
		 * Display student list in single course page.
		 * Hook after section instructor
		 * To show on single course Modern layout
		 * and build via Gutenberg. When create a block for announcements done, need check is theme Gutenberg to return, not add to section.
		 *
		 * @param array $section
		 * @param CourseModel $courseModel
		 * @param UserModel|false $userModel
		 *
		 * @return array
		 * @since 4.0.3
		 * @version 1.0.0
		 */
		public function single_course_show_list_students( array $section, CourseModel $courseModel, $userModel ) {
			if ( $this->enable_hide_students_list( $courseModel ) ) {
				return $section;
			}

			ob_start();
			do_action( 'lp-addon-students-list/students-list/layout', $courseModel );
			$html = ob_get_clean();

			return apply_filters(
				'learn-press/addon/students-list/single-course/position',
				Template::insert_value_to_position_array( $section, 'after', 'wrapper_end', 'student-list', $html ),
				$html,
				$section,
				$courseModel,
				$userModel
			);
		}

		/**
		 * Students list tab content in single course page.
		 *
		 * @since 4.0.0
		 * @version 1.0.1
		 */
		public function single_course_students_list_tab_content() {
			$course = CourseModel::find( get_the_ID(), true );
			if ( ! $course ) {
				return;
			}

			do_action( 'lp-addon-students-list/students-list/layout', $course );
		}

		/**
		 * Register setting fields
		 *
		 * @param array $settings LP Course Settings
		 */
		public function settings( array $settings = [] ): array {
			$setting_student_list = include_once LP_ADDON_STUDENTS_LIST_PATH . '/config/settings.php';

			return array_merge( $settings, $setting_student_list );
		}

		/**
		 * Enable option hide students list in course.
		 *
		 * @param CourseModel $course
		 *
		 * @return bool
		 * @since 4.0.3
		 * @version 1.0.0
		 */
		public function enable_hide_students_list( CourseModel $course ): bool {
			return $course->get_meta_value_by_key( '_lp_hide_students_list', 'no' ) === 'yes';
		}
	}
}
