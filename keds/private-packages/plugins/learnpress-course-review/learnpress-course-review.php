<?php
/**
 * Plugin Name: LearnPress - Course Review
 * Plugin URI: https://thimpress.com/product/learnpress-course-review/
 * Description: Adding review for course.
 * Author: ThimPress
 * Version: 4.2.0
 * Author URI: http://thimpress.com
 * Tags: learnpress
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: learnpress-course-review
 * Domain Path: /languages/
 * Require_LP_Version: 4.3.2.3
 * Requires Plugins: learnpress
 *
 * @package learnpress-course-review
 */

use LearnPress\CourseReview\CourseReviewShortCode;
use LearnPress\CourseReview\TemplateHooks\CourseRatingTemplate;
use LearnPress\CourseReview\TemplateHooks\FilterCourseRatingTemplate;
use LearnPress\CourseReview\TemplateHooks\TemplateHooks;

defined( 'ABSPATH' ) || exit;

const LP_ADDON_COURSE_REVIEW_FILE = __FILE__;

/**
 * Class LP_Addon_Course_Review_Preload
 */
class LP_Addon_Course_Review_Preload {
	/**
	 * @var array|string[]
	 */
	public static $addon_info = array();
	/**
	 * @var LP_Addon_Course_Review $addon
	 */
	public static $addon;

	/**
	 * Singleton.
	 *
	 * @return LP_Addon_Course_Review_Preload|mixed
	 */
	public static function instance() {
		static $instance;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * LP_Addon_Course_Review_Preload constructor.
	 */
	protected function __construct() {
		// Set Base name plugin.
		define( 'LP_ADDON_COURSE_REVIEW_BASENAME', plugin_basename( LP_ADDON_COURSE_REVIEW_FILE ) );

		// Set version addon for LP check .
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		self::$addon_info = get_file_data(
			LP_ADDON_COURSE_REVIEW_FILE,
			array(
				'Name'               => 'Plugin Name',
				'Require_LP_Version' => 'Require_LP_Version',
				'Version'            => 'Version',
			)
		);

		define( 'LP_ADDON_COURSE_REVIEW_VER', self::$addon_info['Version'] );
		define( 'LP_ADDON_COURSE_REVIEW_REQUIRE_VER', self::$addon_info['Require_LP_Version'] );

		// Check LP activated .
		if ( ! is_plugin_active( 'learnpress/learnpress.php' ) ) {
			add_action( 'admin_notices', array( $this, 'show_note_errors_require_lp' ) );

			deactivate_plugins( LP_ADDON_COURSE_REVIEW_BASENAME );

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}

			return;
		}

		// Sure LP loaded.
		add_action( 'learn-press/ready', array( $this, 'load' ) );
	}

	/**
	 * Load addon
	 */
	public function load() {
		include_once 'vendor/autoload.php';
		include_once 'inc/load.php';
		self::$addon = LP_Addon_Course_Review::instance();
		//LP_Addon_Review_List_Rating_Reviews_Template::instance();
		FilterCourseRatingTemplate::instance();
		CourseRatingTemplate::instance();
		TemplateHooks::instance();
		CourseReviewShortCode::instance();
	}

	public function show_note_errors_require_lp() {
		?>
		<div class="notice notice-error">
			<p><?php echo( 'Please active <strong>LearnPress version ' . LP_ADDON_COURSE_REVIEW_REQUIRE_VER . ' or later</strong> before active <strong>' . self::$addon_info['Name'] . '</strong>' ); ?></p>
		</div>
		<?php
	}
}

LP_Addon_Course_Review_Preload::instance();
