<?php

namespace LP_PMS;

use Exception;
use LearnPress\Models\CourseModel;
use LP_Addon;
use LP_Addon_Paid_Memberships_Pro_Preload;
use LP_Debug;
use LP_Meta_Box_Select_Field;
use LP_PMS_DB;
use LP_PMS_Order;
use LP_Settings;
use stdClass;

defined( 'ABSPATH' ) || exit;

/**
 * Class LP_Addon_Paid_Memberships_Pro
 */
class Addon extends LP_Addon {
	public $settings        = array();
	public $version         = LP_ADDON_PMPRO_VER;
	public $require_version = LP_ADDON_PMPRO_REQUIRE_VER;
	public $plugin_file     = LP_ADDON_PMPRO_FILE;
	public $text_domain     = 'learnpress-paid-membership-pro';

	/**
	 * @var
	 */
	public $pmpro_levels;

	/**
	 * @var
	 */
	protected $user_level;

	public static function instance() {
		static $instance;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * LP_Addon_Paid_Memberships_Pro constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->hooks();
		Hook::instance();
	}

	protected function _includes() {
		require_once LP_ADDON_PMPRO_PATH . '/inc/functions.php';
		include 'background-process/class-lp-pms-background-process.php';
		include 'classes/class-lp-pms-order.php';

		new Settings();
		if ( is_admin() ) {
			require_once LP_ADDON_PMPRO_DIR . 'inc/pmpro-admin/pmpro-admin.php';
		}
	}

	/**
	 * Get page id of the Membership Levels page.
	 *
	 * @return false|mixed|null
	 */
	public function get_pms_levels_page_id() {
		return get_option( 'pmpro_levels_page_id', 0 );
	}

	/**
	 * Init hooks
	 */
	protected function hooks() {
		// get all memberships
		add_action( 'learn-press/content-single', array( $this, 'get_pmpro_get_all_levels' ), - 1 );

		// Add the Memberships tab in LearnPress Settings page
		add_filter( 'learn-press/admin/settings-tabs-array', array( $this, 'admin_settings' ) );

		// add scripts to frontend
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ) );

		// add scripts to admin
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		/* Override page list levels Templates */
		add_filter( 'pmpro_pages_custom_template_path', array( $this, 'learn_press_pmpro_pages_custom_template_path' ), 10, 5 );
		add_action( 'pmpro_checkout_after_pricing_fields', array( $this, 'learn_press_pmpro_checkout_after_pricing_fields' ) );

		//add_filter( 'pmpro_email_data', array( $this, 'learn_press_pmpro_email_data' ), 10, 2 );

		//add_filter( 'learn-press/purchase-course-button-text', array( $this, 'learn_press_pmpro_purchase_button_text' ), 10, 2 );

		add_shortcode( 'lp_pmpro_courses', array( $this, 'learn_press_page_levels_short_code' ) );

		// Add the Memberships tab to Course settings in edit Course page
		add_action(
			'learnpress/course/metabox/tabs',
			function ( $tabs, $post_id ) {
				$course = CourseModel::find( $post_id, true );
				if ( ! $course ) {
					return $tabs;
				}

				if ( $course->is_offline() ) {
					return $tabs;
				}

				$options_levels = array();
				$pmpro_levels   = lp_pmpro_get_all_levels();

				foreach ( $pmpro_levels as $pmpro_level ) {
					$options_levels[ $pmpro_level->id ] = $pmpro_level->name;
				}

				$values = get_post_meta( $post_id, '_lp_pmpro_levels', false ) ?? array();

				$tabs['paid-memberships-pro'] = array(
					'label'    => esc_html__( 'Course Memberships', 'learnpress-paid-membership-pro' ),
					'target'   => 'lp-course-membership-pro',
					'icon'     => 'dashicons-groups',
					'priority' => 90,
					'content'  => array(
						'_lp_pmpro_levels' => new LP_Meta_Box_Select_Field(
							esc_html__( 'Select membership levels', 'learnpress-paid-membership-pro' ),
							[],
							array(),
							[
								'value'       => $values,
								'options'     => $options_levels,
								'tom_select'  => true,
								'multiple'    => true,
								'style'       => 'min-width:200px;',
								'custom_save' => true, // Custom for save metabox
							]
						),
					),
				);

				return $tabs;
			},
			10,
			2
		);

		add_action(
			'learnpress/admin/metabox/select/save',
			function ( $id, $raw_values, $post_id = 0 ) {
				if ( $id === '_lp_pmpro_levels' ) {
					$values       = get_post_meta( $post_id, '_lp_pmpro_levels', false ) ?? array();
					$pmpro_levels = isset( $raw_values ) ? (array) wp_unslash( $raw_values ) : array();

					$array_values = ! empty( $values ) ? array_values( $values ) : array();
					$level_values = ! empty( $pmpro_levels ) ? array_values( $pmpro_levels ) : array();

					$del_course_ids = array_diff( $array_values, $level_values );
					$new_course_ids = array_diff( $level_values, $array_values );

					foreach ( $del_course_ids as $level_id ) {
						$coursesOnLevel = LP_PMS_DB::getInstance()->getCoursesOnLevel( $level_id );
						unset( $coursesOnLevel[ $post_id ] );
						delete_post_meta( $post_id, '_lp_pmpro_levels', $level_id );
						$this->update_order_single_course_when_update_level( $level_id, $coursesOnLevel );
					}

					foreach ( $new_course_ids as $level_id ) {
						$coursesOnLevel             = LP_PMS_DB::getInstance()->getCoursesOnLevel( $level_id );
						$newCourseOnLevel           = new stdClass();
						$newCourseOnLevel->ID       = $post_id;
						$newCourseOnLevel->title    = get_the_title( $post_id );
						$coursesOnLevel[ $post_id ] = $newCourseOnLevel;
						add_post_meta( $post_id, '_lp_pmpro_levels', $level_id, false );
						$this->update_order_single_course_when_update_level( $level_id, $coursesOnLevel );
					}
				}
			},
			10,
			3
		);
	}

	/**
	 * update order in single course when update level.
	 *
	 * @param $level_id , $coursesOnLevel
	 */

	public function update_order_single_course_when_update_level( $level_id, $coursesOnLevel ) {

		$level_course_ids = array();

		foreach ( $coursesOnLevel as $value ) {
			$level_course_ids[] = $value->ID;
		}

		$auto_update_courses_on_level = LP_Settings::get_option( 'pmpro_update_access_course', 'no' );
		if ( $auto_update_courses_on_level != 'yes' ) {
			return;
		}

		$lp_orders = LP_PMS_DB::getInstance()->getLastOrderOfUsersHasLevel( $level_id );
		if ( is_array( $lp_orders ) && count( $lp_orders ) > 0 ) {
			LP_PMS_Order::getInstance()->handleLevelChangeCourses( $lp_orders, $level_course_ids, $level_id );
		}
	}

	public function get_pmpro_get_all_levels() {
		$this->pmpro_levels = lp_pmpro_get_all_levels();
	}

	/**
	 * callback function for hook 'learn-press/admin/settings-tabs-array'.
	 * it add new tab in LearnPress Settings page.
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function admin_settings( $tabs ) {
		$tabs['membership'] = new Settings();

		return $tabs;
	}

	public function frontend_enqueue_scripts() {
		$min    = '.min';
		$ver    = LP_ADDON_PMPRO_VER;
		$is_rtl = is_rtl() ? '-rtl' : '';
		if ( LP_Debug::is_debug() ) {
			$min = '';
			$ver = uniqid();
		}

		wp_register_style(
			'lp-pms-style',
			LP_ADDON_PMPRO_URL . "assets/dist/css/lp-pms-style{$is_rtl}{$min}.css",
			[],
			$ver
		);

		if ( is_archive() || learn_press_is_course() ) {
			wp_enqueue_style( 'lp-pms-style' );
		}
	}

	public function admin_enqueue_scripts() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		$ver = LP_ADDON_PMPRO_VER;
		$min = '.min';
		if ( LP_Debug::is_debug() ) {
			$min = '';
			$ver = uniqid();
		}

		if ( LP_Debug::is_debug() ) {
			$ver = uniqid();
		}

		wp_register_script(
			'learn-press-pms-script',
			LP_ADDON_PMPRO_URL . "assets/js/dist/admin-lp-pms{$min}.js",
			array(),
			$ver,
			[ 'strategy' => 'async' ]
		);

		if ( $screen ) {
			$check_valid = in_array( $screen->id, [ 'memberships_page_pmpro-membershiplevels', LP_COURSE_CPT ] );
			if ( $check_valid ) {
				wp_enqueue_script( 'learn-press-pms-script' );
			}
		}
	}

	/**
	 * Load template overwrite for Paid Memberships Pro
	 *
	 * @param $default_templates
	 * @param $page_name
	 * @param $type
	 * @param $where
	 * @param $ext
	 *
	 * @return mixed
	 * @since 3.0.0
	 * @version 1.0.1
	 */
	public function learn_press_pmpro_pages_custom_template_path( $default_templates, $page_name, $type, $where, $ext ) {
		// Set info for file levels.php to not out of date
		try {
			if ( $page_name === 'levels' && is_admin() ) {
				$path_file                 = LP_ADDON_PMPRO_TEMP . '/pages/levels-version.php';
				$page_levels_original_info = get_file_data( PMPRO_DIR . '/pages/levels.php', array( 'Version' => 'Version' ) );
				//$page_levels_override_info = get_file_data( $path_file, array( 'Version' => 'Version' ) );

				//if ( $page_levels_override_info['Version'] !== $page_levels_original_info['Version'] ) {
				$folder_name_rewrite = learn_press_template_path();
				$from_theme_path     = sprintf(
					'%s/%s/%s/%s/%s',
					get_template_directory(),
					$folder_name_rewrite,
					'addons',
					str_replace( 'learnpress-', '', $this->plugin_folder_name ),
					'levels.php'
				);

				$path_load = LP_ADDON_PMPRO_TEMP . '/pages/levels.php';
				if ( file_exists( $from_theme_path ) ) {
					$path_load = $from_theme_path;
				}

				$content = "<?php
/**
 * Template: Levels
 * Version: {$page_levels_original_info['Version']}
 * Version of PMS, for not out of date
 */
include '{$path_load}';
";

				file_put_contents( $path_file, $content );
				//}

				$default_templates[] = $path_file;

				return $default_templates;
			}
		} catch ( Exception $e ) {

		}
		// End set

		$template = learn_press_pmpro_locate_template( "{$type}/{$page_name}.{$ext}" );

		if ( file_exists( $template ) ) {
			$default_templates[] = $template;
		}

		return $default_templates;
	}

	public function learn_press_pmpro_checkout_after_pricing_fields() {
		LP_Addon_Paid_Memberships_Pro_Preload::$addon->get_template( 'pages/checkout-custom-pricing' );
	}

	public function learn_press_page_levels_short_code() {
		echo do_shortcode( '[pmpro_levels]' );
	}

	/**
	 * Get option only buy course via membership
	 *
	 * @return bool
	 * @since 4.0.7
	 * @version 1.0.0
	 */
	public function is_only_buy_via_pms(): bool {
		return LP_Settings::get_option( 'buy_through_membership', 'no' ) === 'yes';
	}

	/**
	 * Get option only buy course via membership
	 *
	 * @return bool
	 * @since 4.0.9
	 * @version 1.0.0
	 */
	public function enable_update_access_courses(): bool {
		return LP_Settings::get_option( 'pmpro_update_access_course', 'no' ) === 'yes';
	}

	/**
	 * Get levels of course assigned.
	 *
	 * @param CourseModel $courseModel
	 *
	 * @return mixed
	 * @since 4.0.7
	 * @version 1.0.0
	 */
	public function get_levels_of_course( CourseModel $courseModel ) {
		return get_post_meta( $courseModel->get_id(), '_lp_pmpro_levels' ) ?? [];
	}
}
