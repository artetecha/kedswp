<?php

use LearnPress\Gradebook\TemplateHooks\Admin\AdminRecentActivityTemplate;
use LearnPress\Gradebook\TemplateHooks\Admin\AdminStudentDetailTemplate;
use LearnPress\Gradebook\TemplateHooks\Admin\AdminStudentOverviewTemplate;
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

		// Add tab to profile.
		add_filter( 'learn-press/profile-tabs', array( $this, 'profile_tabs' ) );
		AdminRecentActivityTemplate::instance();
		AdminStudentOverviewTemplate::instance();
		AdminStudentDetailTemplate::instance();
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
		if ( strpos( $hook, 'course-gradebook' ) ) {
			$file_info = include LP_ADDON_GRADEBOOK_PLUGIN_PATH . '/assets/dist/js/gradebook' . $suffix . '.asset.php';

			wp_enqueue_script(
				'learnpress-gradebook-admin',
				$this->get_plugin_url( '/assets/dist/js/gradebook' . $suffix . '.js' ),
				$file_info['dependencies'],
				uniqid(),
				[ 'strategy' => 'defer' ]
			);
		}

		// Load styles.
		wp_register_style(
			'lp-gradebook-admin-style',
			$this->get_plugin_url( "assets/dist/css/gradebook-admin{$min}.css" ),
			[],
			$ver
		);

		// Load scripts.
		wp_register_script(
			'lp-gradebook-admin-script',
			$this->get_plugin_url( "assets/dist/js/gradebook-admin{$min}.js" ),
			[],
			$ver,
			[ 'strategy' => 'async' ]
		);
	}

	public function register_submenu_page() {
		add_submenu_page(
			'',
			esc_html__( 'Course Gradebook', 'learnpress-gradebook' ),
			'course-gradebook',
			'edit_published_lp_courses',
			'course-gradebook',
			array( $this, 'add_submenu_page_callback' )
		);
		add_submenu_page(
			'learn_press',
			esc_html__( 'Gradebook Manager', 'learnpress-gradebook' ),
			esc_html__( 'Gradebook', 'learnpress-gradebook' ),
			'manage_options',
			'learnpress-gradebook',
			array( $this, 'gradebook_admin_manager_screen' )
		);
	}

	/**
	 * Admin gradebook callback.
	 */
	public function add_submenu_page_callback() {
		?>
		<div id="learnpress-gradebook-react"></div>
		<?php
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
		// Only admin or instructor can view.
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'lp_teacher' ) ) {
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

		$section = [
			'h2'               => sprintf(
				'<h2 class="nav-tab-wrapper">%s</h2>',
				$html_list_tabs
			),
			'wrap'             => '<div class="lp-admin-tabs">',
			'wrap-content'     => '<div class="lp-admin-tab-content">',
			'content'          => $html_content,
			'wrap-content-end' => '</div>',
			'wrap-end'         => '</div>',
		];

		echo Template::combine_components( $section );
	}
}
