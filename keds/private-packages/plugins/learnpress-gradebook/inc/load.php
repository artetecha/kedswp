<?php

use LearnPress\Filters\Course\CourseJsonFilter;
use LearnPress\Gradebook\Databases\GradebookDB;
use LearnPress\Gradebook\TemplateHooks\Admin\AdminRecentActivityTemplate;
use LearnPress\Gradebook\TemplateHooks\Admin\AdminStudentDetailTemplate;
use LearnPress\Gradebook\TemplateHooks\Admin\AdminStudentOverviewTemplate;
use LearnPress\Gradebook\TemplateHooks\Admin\AdminCourseGradebookTemplate;
use LearnPress\Gradebook\TemplateHooks\Admin\AdminUserCourseGradebookTemplate;
use LearnPress\Gradebook\TemplateHooks\Admin\AdminUserQuizGradebookTemplate;
use LearnPress\Gradebook\Permission;
use LearnPress\Helpers\Template;
/**
 * Class LP_Addon_Gradebook
 *
 * @since 4.0.0
 * @author Nhamdv <daonham95@gmail.com>
 */
class LP_Addon_Gradebook extends LP_Addon {
	public $version         = LP_ADDON_GRADEBOOK_VER;
	public $require_version = LP_ADDON_GRADEBOOK_REQUIRE_VER;
	public $plugin_file     = LP_ADDON_GRADEBOOK_PLUGIN_FILE;
	public $text_domain     = 'learnpress-gradebook';

	public static $instances;

	public static function instance() {
		if ( is_null( self::$instances ) ) {
			self::$instances = new self();
		}

		return self::$instances;
	}

	/**
	 * LP_Addon_Gradebook constructor.
	 */
	public function __construct() {
		parent::__construct();

		add_filter( 'manage_lp_course_posts_columns', array( $this, 'manage_course_posts_columns' ) );
		add_action( 'manage_lp_course_posts_custom_column', array( $this, 'manage_course_post_column' ), 10, 2 );

		add_action( 'admin_menu', array( $this, 'register_submenu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_enqueue_scripts_on_admin' ) );
		add_filter( 'learn-press/settings/addons/sections', array( $this, 'register_settings_section' ) );
		add_filter( 'learn-press/settings/addons/fields-gradebook', array( $this, 'settings_section_fields' ) );
		add_filter( 'learn-press/api-admin-tools/permission', array( $this, 'rest_admin_tools_permission' ), 10, 2 );

		// Add tab to profile.
		add_filter( 'learn-press/profile-tabs', array( $this, 'profile_tabs' ) );
		AdminRecentActivityTemplate::instance();
		AdminStudentOverviewTemplate::instance();
		AdminStudentDetailTemplate::instance();

		AdminCourseGradebookTemplate::instance();
		AdminUserCourseGradebookTemplate::instance();
		AdminUserQuizGradebookTemplate::instance();
	}

	protected function _includes() {
		require_once LP_ADDON_GRADEBOOK_PLUGIN_PATH . '/inc/functions.php';
		require_once LP_ADDON_GRADEBOOK_PLUGIN_PATH . '/inc/class-database.php';
		require_once LP_ADDON_GRADEBOOK_PLUGIN_PATH . '/inc/class-rest-controller.php';
	}

	public function load_enqueue_scripts_on_admin( $hook ) {
		$min    = '.min';
		$ver    = LP_ADDON_GRADEBOOK_VER;
		$is_rtl = is_rtl() ? '-rtl' : '';
		if ( LP_Debug::is_debug() ) {
			$min = '';
			$ver = uniqid();
		}

		$suffix = '.min';

		if ( LP_Debug::is_debug() && apply_filters( 'learnpress/gradebook/enqueue/debug/enable', true ) ) {
			$suffix = '';
		}

		// course-gradebook added in add_submenu_page.
		if ( false !== strpos( $hook, 'course-gradebook' ) ) {
			wp_register_style(
				'lp-gradebook-course-style',
				$this->get_plugin_url( '/assets/dist/css/' . $this->get_dist_css_file_name( 'course-gradebook', $min, $is_rtl ) ),
				array(),
				$ver
			);

			$screen = LP_Request::get_param( 'screen', 1, 'int' );
			if ( $screen === 2 ) {
				$file_info = $this->get_script_asset_info( 'user-course-gradebook', $suffix );
				wp_register_script(
					'user-course-gradebook',
					$this->get_plugin_url( '/assets/dist/js/user-course-gradebook' . $suffix . '.js' ),
					$file_info['dependencies'],
					$file_info['version'],
					array(
						'strategy' => 'async',
					)
				);
			} elseif ( $screen === 3 ) {
				wp_register_style( 'gradebook-user-quiz', $this->get_plugin_url( '/assets/dist/css/gradebook-user-quiz.css' ) );
				$file_info = $this->get_script_asset_info( 'user-quiz-gradebook', $suffix );
				wp_register_script(
					'gradebook-user-quiz',
					$this->get_plugin_url( '/assets/dist/js/user-quiz-gradebook' . $suffix . '.js' ),
					$file_info['dependencies'],
					$file_info['version'],
					array(
						'strategy' => 'async',
					)
				);
				wp_localize_script(
					'gradebook-user-quiz',
					'gradebookUserQuizData',
					array(
						'course_id' => LP_Request::get_param( 'course_id', 1, 'int' ),
						'user_id'   => LP_Request::get_param( 'student', 1, 'int' ),
						'quiz_id'   => LP_Request::get_param( 'quiz_id', 1, 'int' ),
					)
				);
			} else {
				$file_info = $this->get_script_asset_info( 'course-gradebook', $suffix );
				wp_register_script(
					'course-gradebook-js',
					$this->get_plugin_url( '/assets/dist/js/course-gradebook' . $suffix . '.js' ),
					$file_info['dependencies'],
					$file_info['version'],
					array(
						'strategy' => 'async',
					)
				);
			}
		}

		// Load styles.
		wp_register_style(
			'lp-gradebook-admin-style',
			$this->get_plugin_url( "assets/dist/css/gradebook-admin{$min}.css" ),
			array(),
			$ver
		);

		// Load scripts.
		wp_register_script(
			'lp-gradebook-admin-script',
			$this->get_plugin_url( "assets/dist/js/gradebook-admin{$min}.js" ),
			array(),
			$ver,
			array( 'strategy' => 'async' )
		);
	}

	protected function get_script_asset_info( string $script_name, string $suffix ): array {
		$asset_file = LP_ADDON_GRADEBOOK_PLUGIN_PATH . "/assets/dist/js/{$script_name}{$suffix}.asset.php";
		if ( ! file_exists( $asset_file ) ) {
			$asset_file = LP_ADDON_GRADEBOOK_PLUGIN_PATH . "/assets/dist/js/{$script_name}.asset.php";
		}

		if ( ! file_exists( $asset_file ) ) {
			return array(
				'dependencies' => array(),
				'version'      => LP_ADDON_GRADEBOOK_VER,
			);
		}

		$file_info = include $asset_file;

		$dependencies = ! empty( $file_info['dependencies'] ) && is_array( $file_info['dependencies'] ) ? $file_info['dependencies'] : array();
		$dependencies = array_values( array_unique( array_merge( $dependencies, array( 'wp-api-fetch', 'wp-url' ) ) ) );

		return array(
			'dependencies' => $dependencies,
			'version'      => ! empty( $file_info['version'] ) ? $file_info['version'] : LP_ADDON_GRADEBOOK_VER,
		);
	}

	protected function get_dist_css_file_name( string $style_name, string $min, string $is_rtl ): string {

		$candidates = array();
		if ( $is_rtl && $min ) {
			$candidates[] = "{$style_name}{$is_rtl}{$min}.css";
		}
		if ( $is_rtl ) {
			$candidates[] = "{$style_name}{$is_rtl}.css";
		}
		if ( $min ) {
			$candidates[] = "{$style_name}{$min}.css";
		}
		$candidates[] = "{$style_name}.css";

		foreach ( $candidates as $candidate ) {
			if ( file_exists( LP_ADDON_GRADEBOOK_PLUGIN_PATH . "/assets/dist/css/{$candidate}" ) ) {
				return $candidate;
			}
		}

		return "{$style_name}.css";
	}

	public function register_submenu_page() {

		// set global title for fix bug php warning PHP Deprecated:  strip_tags(): Passing null to parameter #1 ($string) of type string is deprecated in add_submenu_page Course Gradebook
		if ( isset( $_GET['page'] ) && 'course-gradebook' === sanitize_text_field( $_GET['page'] ) ) {
			global $title;
			$title = esc_html__( 'Course Gradebook', 'learnpress-gradebook' );
		}

		$cap = Permission::instructors_enabled() ? 'edit_published_lp_courses' : 'manage_options';

		add_submenu_page(
			'',
			esc_html__( 'Course Gradebook', 'learnpress-gradebook' ),
			'course-gradebook',
			$cap,
			'course-gradebook',
			array( $this, 'add_submenu_page_callback' )
		);
		add_submenu_page(
			'learn_press',
			esc_html__( 'Gradebook Manager', 'learnpress-gradebook' ),
			esc_html__( 'Gradebook', 'learnpress-gradebook' ),
			$cap,
			'learnpress-gradebook',
			array( $this, 'gradebook_admin_manager_screen' )
		);
	}

	/**
	 * Register the Gradebook section under LearnPress > Settings > Addons.
	 *
	 * @param array $sections Section key => label.
	 *
	 * @return array
	 */
	public function register_settings_section( $sections ) {

		$sections['gradebook'] = esc_html__( 'Gradebook', 'learnpress-gradebook' );

		return $sections;
	}

	/**
	 * Fields for the Gradebook settings section.
	 *
	 * @param array $fields Fields registered by earlier callbacks.
	 *
	 * @return array
	 */
	public function settings_section_fields( $fields ) {

		$gradebook_fields = include LP_ADDON_GRADEBOOK_PLUGIN_PATH . '/config/settings.php';

		return array_merge( (array) $fields, (array) $gradebook_fields );
	}

	/**
	 * Allow instructors to use only the read-only admin tools Gradebook relies on.
	 */
	public function rest_admin_tools_permission( $permission, $request = null ): bool {
		if ( $permission || ! $request instanceof WP_REST_Request ) {
			return $permission;
		}

		$route = $request->get_route();
		if ( ! in_array( $route, array( '/lp/v1/admin/tools/search-course', '/lp/v1/admin/tools/search-user' ), true ) ) {
			return $permission;
		}

		$can_view = Permission::can_view_gradebook();
		if ( $can_view && Permission::is_scoped_user() ) {
			if ( '/lp/v1/admin/tools/search-course' === $route ) {
				add_filter( 'lp/courses-json/filter', array( $this, 'scope_rest_search_courses' ) );
				add_filter( 'lp/courses/filter', array( $this, 'scope_rest_search_courses' ) );
			} else {
				add_filter( 'learn-press/rest-admin-tools/args-search-users', array( $this, 'scope_rest_search_users_args' ) );
			}
		}

		return $can_view;
	}

	/**
	 * Scope admin-tools search-course results to instructor/co-instructor courses.
	 */
	public function scope_rest_search_courses( $filter ) {
		if ( ! Permission::is_scoped_user() ) {
			return $filter;
		}

		$alias = '';
		if ( $filter instanceof CourseJsonFilter ) {
			$alias = 'c';
		} elseif ( $filter instanceof LP_Course_Filter ) {
			$alias = 'p';
		}

		if ( $alias ) {
			$allowed         = Permission::get_allowed_course_ids();
			$allowed_courses = is_array( $allowed ) ? Permission::get_scope_sql_in( $allowed ) : '';
			$filter->where[] = sprintf( 'AND %s.ID IN (%s)', $alias, $allowed_courses ?: '0' );
		}

		return $filter;
	}

	/**
	 * Scope admin-tools search-user results to students in instructor/co-instructor courses.
	 */
	public function scope_rest_search_users_args( array $args ): array {
		if ( ! Permission::is_scoped_user() ) {
			return $args;
		}

		$allowed     = Permission::get_allowed_course_ids();
		$student_ids = is_array( $allowed ) ? GradebookDB::getInstance()->get_student_ids_by_course_ids( $allowed ) : array();
		if ( ! empty( $args['include'] ) ) {
			$student_ids = array_values( array_intersect( array_map( 'absint', (array) $args['include'] ), $student_ids ) );
		}

		$args['include'] = $student_ids ?: array( 0 );

		return $args;
	}

	/**
	 * Admin gradebook callback.
	 */
	public function add_submenu_page_callback() {
		do_action( 'learn-press/gradebook/course-gradebook' );
	}
		/**
		 * Add grade book column to course page in admin.
		 *
		 * @param  array $column
		 *
		 * @return array
		 */
	public function manage_course_posts_columns( $column ) {
		$date                = ! empty( $column['date'] ) ? $column['date'] : null;
		$column['gradebook'] = esc_html__( 'Gradebook', 'learnpress-gradebook' );

		if ( $date ) {
			unset( $column['date'] );
			$column['date'] = $date;
		}

		return $column;
	}

		/**
		 * Add the grade book column content.
		 *
		 * @param $column
		 * @param $post_id
		 */
	public function manage_course_post_column( $column, $post_id ) {
		switch ( $column ) {
			case 'gradebook':
				printf(
					'<a class="button" href="%s">%s</a>',
					learn_press_gradebook_nonce_url( array( 'course_id' => $post_id ) ),
					esc_html__( 'View', 'learnpress-gradebook' )
				);
				break;
		}
	}

		/**
		 * Add custom tabs into user's profile.
		 *
		 * @param array $tabs
		 *
		 * @return mixed
		 */
	public function profile_tabs( $tabs ) {

		if ( ! Permission::can_view_gradebook() ) {
			return $tabs;
		}
		$tabs['gradebook'] = array(
			'title'    => esc_html__( 'Gradebook', 'learnpress-gradebook' ),
			'slug'     => 'gradebook',
			'callback' => array( $this, 'profile_tab_content' ),
			'priority' => 12,
			'icon'     => '<i class="fa fa-database" aria-hidden="true"></i>',
		);

		return $tabs;
	}

		/**
		 * Content of profile courses page.
		 */
	public function profile_tab_content() {
		?>
		<div>
			<a href="<?php echo esc_url( admin_url( '/edit.php?post_type=lp_course' ) ); ?>" class="button">
			<?php esc_html_e( 'Go to Gradebook', 'learnpress-gradebook' ); ?>
			</a>
		</div>
			<?php
	}

		/**
		 * Admin Gradebook manager screen.
		 */
	public function gradebook_admin_manager_screen() {

		if ( ! Permission::can_view_gradebook() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'learnpress-gradebook' ) );
		}

		$current_tab     = LP_Request::get_param( 'tab', 'recent-activity' );
		$current_section = LP_Request::get_param( 'section' );
		$tabs            = array(
			'recent-activity'  => esc_html__( 'Recent Activity', 'learnpress-gradebook' ),
			'student-overview' => esc_html__( 'Student Overview', 'learnpress-gradebook' ),
		);

		$html_list_tabs = '';
		foreach ( $tabs as $slug => $label ) {
			$html_list_tabs .= sprintf(
				'<a href="%s" class="nav-tab %s">%s</a>',
				esc_attr( '?page=learnpress-gradebook&tab=' . $slug ),
				esc_attr( $slug === $current_tab ? 'nav-tab-active' : '' ),
				$label
			);
		}

		ob_start();
		$args = array(
			'tab'     => $current_tab,
			'section' => $current_section,
		);
		do_action( 'learn-press/gradebook/admin-view', $args );
		$html_content = ob_get_clean();

		$section = array(
			'h2'               => sprintf(
				'<h2 class="nav-tab-wrapper">%s</h2>',
				$html_list_tabs
			),
			'wrap'             => '<div class="lp-admin-tabs">',
			'wrap-content'     => '<div class="lp-admin-tab-content">',
			'content'          => $html_content,
			'wrap-content-end' => '</div>',
			'wrap-end'         => '</div>',
		);

		echo Template::combine_components( $section );
	}
}
