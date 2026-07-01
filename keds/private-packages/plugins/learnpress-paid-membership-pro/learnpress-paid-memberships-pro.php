<?php
/**
 * Plugin Name: LearnPress - Paid Membership Pro Integration
 * Plugin URI: https://thimpress.com/product/paid-memberships-pro-add-learnpress/
 * Description: Paid Membership Pro add-on for LearnPress.
 * Author: ThimPress
 * Version: 4.1.2
 * Author URI: http://thimpress.com
 * Tags: learnpress, lms
 * Text Domain: learnpress-paid-membership-pro
 * Domain Path: /languages/
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Require_LP_Version: 4.3.2.4
 *
 * @package learnpress-paid-membership
 */

use LP_PMS\Addon;

defined( 'ABSPATH' ) || exit;

define( 'LP_ADDON_PMPRO_PATH', __DIR__ );
const LP_ADDON_PMPRO_TEMP = LP_ADDON_PMPRO_PATH . DIRECTORY_SEPARATOR . 'templates';
define(
	'GMC_PHYS_PATH',
	trailingslashit( WP_PLUGIN_DIR . '/' . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) )
);

/**
 * Class LP_Addon_Paid_Memberships_Pro_Preload
 */
class LP_Addon_Paid_Memberships_Pro_Preload {
	/**
	 * @var array
	 */
	public static $addon_info = array();
	/**
	 * @var LP_PMS\Addon $addon
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
	 * LP_Addon_Paid_Memberships_Pro_Preload constructor.
	 */
	protected function __construct() {
		$can_load = true;
		// Set version addon for LP check .
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		define( 'LP_ADDON_PMPRO_FILE', __FILE__ );
		self::$addon_info = get_file_data(
			LP_ADDON_PMPRO_FILE,
			array(
				'Name'               => 'Plugin Name',
				'Require_LP_Version' => 'Require_LP_Version',
				'Version'            => 'Version',
			)
		);

		define( 'LP_ADDON_PMPRO_VER', self::$addon_info['Version'] );
		define( 'LP_ADDON_PMPRO_REQUIRE_VER', self::$addon_info['Require_LP_Version'] );
		define( 'LP_ADDON_PMPRO_DIR', plugin_dir_path( __FILE__ ) );
		define( 'LP_ADDON_PMPRO_URL', plugin_dir_url( __FILE__ ) );
		define( 'LP_ADDON_PMPRO_BASE_NAME', plugin_basename( __FILE__ ) );

		// Check LP activated .
		if ( ! is_plugin_active( 'learnpress/learnpress.php' ) ) {
			$can_load = false;
		} elseif ( version_compare( LP_ADDON_PMPRO_REQUIRE_VER, get_option( 'learnpress_version', '3.0.0' ), '>' ) ) {
			$can_load = false;
		}

		if ( ! $can_load ) {
			add_action( 'admin_notices', array( $this, 'show_note_errors_require_lp' ) );
			deactivate_plugins( LP_ADDON_PMPRO_BASE_NAME );

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}

			return;
		}

		// Check PMS activated .
		if ( ! $this->check_pms_activated() ) {
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
		self::$addon = Addon::instance();

		include_once 'inc/classes/class-lp-pms-db.php';
		include_once 'inc/classes/class-lp-pms-woo.php';
	}

	/**
	 * Check plugin Woo activated.
	 */
	public function check_pms_activated(): bool {
		if ( ! is_plugin_active( 'paid-memberships-pro/paid-memberships-pro.php' ) ) {
			add_action( 'admin_notices', array( $this, 'show_note_errors_install_plugin_pms' ) );

			deactivate_plugins( LP_ADDON_PMPRO_BASE_NAME );

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}

			return false;
		}

		return true;
	}

	/**
	 * Show note errors must install plugin LearnPress.
	 *
	 * @return void
	 */
	public function show_note_errors_require_lp() {
		?>
		<div class="notice notice-error">
			<p><?php echo( 'Please active <strong>LP version ' . LP_ADDON_PMPRO_REQUIRE_VER . ' or later</strong> before active <strong>' . self::$addon_info['Name'] . '</strong>' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show note errors must install plugin PMS.
	 *
	 * @return void
	 */
	public function show_note_errors_install_plugin_pms() {
		?>
		<div class="notice notice-error">
			<p>
				<?php echo 'Please activate the <strong>Paid Memberships Pro</strong> plugin before activating the <strong>LearnPress PMS plugin</strong>'; ?>
			</p>
			<p>
				<?php
				echo sprintf(
					'%s <a href="%s">%s</a> %s',
					__( 'You can download ' ),
					'https://license.paidmembershipspro.com/downloads/free/paid-memberships-pro.zip',
					__( 'here', 'learnpress-paid-membership-pro' ),
					sprintf(
						'%s <a href="%s">%s</a>',
						__( 'or read', 'learnpress-paid-membership-pro' ),
						'https://www.paidmembershipspro.com/documentation/download/',
						__( 'the guide', 'learnpress-paid-membership-pro' )
					)
				);
				?>
			</p>
		</div>
		<?php
	}
}

LP_Addon_Paid_Memberships_Pro_Preload::instance();
