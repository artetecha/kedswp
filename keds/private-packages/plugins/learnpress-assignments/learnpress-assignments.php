<?php
/**
 * Plugin Name: LearnPress - Assignments
 * Plugin URI: https://thimpress.com/product/assignments-add-on-for-learnpress/
 * Description: Assignments add-on for LearnPress.
 * Author: ThimPress
 * Version: 4.2.1
 * Author URI: http://thimpress.com
 * Tags: learnpress, lms, assignment
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Text Domain: learnpress-assignments
 * Domain Path: /languages/
 * Require_LP_Version: 4.4.2
 *
 * @package learnpress-assigments
 */

defined( 'ABSPATH' ) || exit;

use LearnPressAssignment\Ajax\AssignmentAjax;
use LearnPressAssignment\Ajax\SendEmailAjax;

const LP_ADDON_ASSIGNMENT_FILE     = __FILE__;
const LP_ADDON_ASSIGNMENT_PATH     = __DIR__;
const LP_ADDON_ASSIGNMENT_INC_PATH = LP_ADDON_ASSIGNMENT_PATH . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR;

/**
 * Class LP_Addon_Assignment_Preload
 */
class LP_Addon_Assignment_Preload {
	/**
	 * @var array
	 */
	public static $addon_info = array();
	/**
	 * @var LP_Addon_Assignment $addon
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
	 * LP_Addon_Assignment_Preload constructor.
	 */
	protected function __construct() {
		$can_load = true;
		define( 'LP_ADDON_ASSIGNMENT_BASENAME', plugin_basename( __FILE__ ) );

		// Set version addon for LP check .
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		self::$addon_info = get_file_data(
			LP_ADDON_ASSIGNMENT_FILE,
			array(
				'Name'               => 'Plugin Name',
				'Require_LP_Version' => 'Require_LP_Version',
				'Version'            => 'Version',
			)
		);

		define( 'LP_ADDON_ASSIGNMENT_VER', self::$addon_info['Version'] );
		define( 'LP_ADDON_ASSIGNMENT_REQUIRE_VER', self::$addon_info['Require_LP_Version'] );

		// Check LP activated .
		if ( ! is_plugin_active( 'learnpress/learnpress.php' ) ) {
			$can_load = false;
		} else {
			// Check version LP
			if ( version_compare(
				LP_ADDON_ASSIGNMENT_REQUIRE_VER,
				get_option( 'learnpress_version', '3.0.0' ), '>' )
			) {
				$can_load = false;
			}
		}

		if ( ! $can_load ) {
			add_action( 'admin_notices', array( $this, 'show_note_errors_require_lp' ) );
			/*deactivate_plugins( LP_ADDON_ASSIGNMENT_BASENAME );

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}*/

			return;
		}

		include_once LP_ADDON_ASSIGNMENT_PATH . '/vendor/autoload.php';

		add_filter( 'learn-press/email-actions-hooks', array( $this, 'email_notify_hook' ) );

		/**
		 * Hook learn-press/ready call on hook 'init' with priority -1000
		 */
		add_action( 'learn-press/ready', array( $this, 'load' ) );
		add_action( 'learn-press/register-ajax-handlers', [ $this, 'register_ajax' ] );
	}

	/**
	 * Hook notify email assignment
	 *
	 * @uses SendEmailAjax::send_mail_assignment_instructor_evaluated
	 * @uses SendEmailAjax::send_mail_assignment_student_submitted
	 *
	 * @param array $email_hooks
	 *
	 * @return array
	 */
	public function email_notify_hook( array $email_hooks ): array {
		$email_hooks['learn-press/assignment/instructor-evaluated'] = 'send_mail_assignment_instructor_evaluated';
		$email_hooks['learn-press/assignment/student-submitted']    = 'send_mail_assignment_student_submitted';

		return $email_hooks;
	}

	/**
	 * Load addon
	 */
	public function load() {
		require_once LP_ADDON_ASSIGNMENT_PATH . '/inc/load.php';
		self::$addon = LP_Addon_Assignment::instance();
	}

	/**
	 * Register ajax handler
	 */
	public function register_ajax() {
		AssignmentAjax::catch_lp_ajax();
		SendEmailAjax::catch_lp_ajax();
	}

	public function show_note_errors_require_lp() {
		?>
		<div class="notice notice-error">
			<p><?php echo( 'Please active <strong>LearnPress version ' . LP_ADDON_ASSIGNMENT_REQUIRE_VER . ' or later</strong> before active <strong>' . self::$addon_info['Name'] . '</strong>' ); ?></p>
		</div>
		<?php
	}
}

LP_Addon_Assignment_Preload::instance();
