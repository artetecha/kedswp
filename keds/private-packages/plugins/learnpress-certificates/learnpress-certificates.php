<?php
/**
 * Plugin Name: LearnPress - Certificates
 * Plugin URI: https://thimpress.com/product/certificates-add-on-for-learnpress/
 * Description: Create certificates for online courses.
 * Author: ThimPress
 * Version: 4.2.6
 * Author URI: http://thimpress.com
 * Tags: learnpress, lms
 * Text Domain: learnpress-certificates
 * Domain Path: /languages/
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Require_LP_Version: 4.4.1
 * Requires Plugins: learnpress
 *
 * @author Nhamdv - Update for LP4.
 */

use LearnPress\Certificate\Ajax\EditCertificateAjax;
use LearnPress\Certificate\TemplateHooks\AdminCertificateTemplate;
use LearnPress\Certificate\TemplateHooks\CourseBuilder\CBCertificateTemplate;
use LearnPress\CourseBuilder\CourseBuilder;

defined( 'ABSPATH' ) || exit;

const LP_ADDON_CERTIFICATES_FILE = __FILE__;
const LP_ADDON_CERTIFICATES_PATH = __DIR__;

/**
 * Class LP_Addon_Certificates_Preload
 */
class LP_Addon_Certificates_Preload {
	/**
	 * @var array
	 */
	public static $addon_info = array();
	/**
	 * @var LP_Addon_Certificates $addon
	 */
	public static $addon;

	/**
	 * Singleton.
	 *
	 * @return LP_Addon_Certificates_Preload|mixed
	 */
	public static function instance() {
		static $instance;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * LP_Addon_Certificates_Preload constructor.
	 */
	public function __construct() {
		$can_load = true;
		// Set Base name plugin.
		define( 'LP_ADDON_CERTIFICATES_BASENAME', plugin_basename( LP_ADDON_CERTIFICATES_FILE ) );

		// Set version addon for LP check .
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		self::$addon_info = get_file_data(
			LP_ADDON_CERTIFICATES_FILE,
			array(
				'Name'               => 'Plugin Name',
				'Require_LP_Version' => 'Require_LP_Version',
				'Version'            => 'Version',
			)
		);

		define( 'LP_ADDON_CERTIFICATES_VER', self::$addon_info['Version'] );
		define( 'LP_ADDON_CERTIFICATES_REQUIRE_VER', self::$addon_info['Require_LP_Version'] );

		// Check LP activated .
		if ( ! is_plugin_active( 'learnpress/learnpress.php' ) ) {
			$can_load = false;
		} elseif ( version_compare( LP_ADDON_CERTIFICATES_REQUIRE_VER, get_option( 'learnpress_version', '3.0.0' ), '>' ) ) {
			$can_load = false;
		}

		if ( ! $can_load ) {
			add_action( 'admin_notices', array( $this, 'show_note_errors_require_lp' ) );
			/*deactivate_plugins( LP_ADDON_CERTIFICATES_BASENAME );

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}*/

			return;
		}

		include_once LP_ADDON_CERTIFICATES_PATH . '/vendor/autoload.php';

		add_action( 'learn-press/ready', array( $this, 'load' ) );

		// Add API
		add_action( 'learn-press/core-api/includes', array( $this, 'include_api' ) );
		add_action( 'learn-press/core-api/controllers', array( $this, 'register_api' ) );

		// Register AJAX
		add_action(
			'learn-press/register-ajax-handlers',
			function () {
				EditCertificateAjax::catch_lp_ajax();
			}
		);
	}

	/**
	 * Load addon
	 */
	public function load() {
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/load.php';
		self::$addon = LP_Addon_Certificates::instance();
		AdminCertificateTemplate::instance();
		if ( class_exists( CourseBuilder::class ) ) {
			CBCertificateTemplate::instance();
		}
	}

	public function show_note_errors_require_lp() {
		?>
		<div class="notice notice-error">
			<p><?php echo( 'Please active <strong>LearnPress version ' . LP_ADDON_CERTIFICATES_REQUIRE_VER . ' or later</strong> before active <strong>' . self::$addon_info['Name'] . '</strong>' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Include file api.
	 */
	public function include_api() {
		require_once 'inc/rest-api/class-lp-rest-certificate-controller.php';
	}

	/**
	 * Register api controller
	 *
	 * @param array $controllers
	 *
	 * @return mixed
	 */
	public function register_api( $controllers = array() ) {
		$controllers[] = 'LP_REST_Certificate_Controller';

		return $controllers;
	}
}

LP_Addon_Certificates_Preload::instance();
