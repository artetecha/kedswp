<?php
/**
 * Plugin Name: LearnPress - Upsell
 * Plugin URI: https://thimpress.com/product/upsell-add-on-for-learnpress/
 * Description: Add upsell feature to LearnPress
 * Author: ThimPress
 * Version: 4.0.9
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author URI: http://thimpress.com
 * Tags: learnpress
 * Text Domain: learnpress-upsell
 * Domain Path: /languages/
 * Require_LP_Version: 4.3.2.7
 */

use LearnPress\Upsell\Gutenberg\GutenbergHandleMain;
use LearnPress\Upsell\Package\Order;
use LearnPress\Upsell\TemplateHooks\ArchivePackage;
use LearnPress\Upsell\TemplateHooks\ListCouponsTemplate;
use LearnPress\Upsell\TemplateHooks\SingleCoursePackage;
use LearnPress\Upsell\TemplateHooks\SinglePackage;

defined( 'ABSPATH' ) || exit();

const LP_ADDON_UPSELL_FILE = __FILE__;
const LP_ADDON_UPSELL_PATH = __DIR__;
define( 'LP_ADDON_UPSELL_URL', plugin_dir_url( __FILE__ ) );
define( 'LP_ADDON_UPSELL_BASENAME', plugin_basename( __FILE__ ) );
const LP_PACKAGE_CPT = 'learnpress_package';
const LP_COUPON_CPT  = 'learnpress_coupon';

class LP_Addon_Upsell_Preload {
	/**
	 * @var array
	 */
	public static $addon_info = array();
	/**
	 * @var LP_Addon_Course_Review $addon
	 */
	public static $addon;

	public function __construct() {

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		self::$addon_info = get_file_data(
			LP_ADDON_UPSELL_FILE,
			array(
				'Name'               => 'Plugin Name',
				'Require_LP_Version' => 'Require_LP_Version',
				'Version'            => 'Version',
			)
		);

		define( 'LP_ADDON_UPSELL_VER', self::$addon_info['Version'] );
		define( 'LP_ADDON_UPSELL_REQUIRE_VER', self::$addon_info['Require_LP_Version'] );
		define( 'LP_ADDON_UPSELL_PACKAGE_PATH', LP_ADDON_UPSELL_PATH . '/inc/Package/' );

		if ( ! is_plugin_active( 'learnpress/learnpress.php' ) ) {
			add_action( 'admin_notices', array( $this, 'show_note_errors_require_lp' ) );

			/*deactivate_plugins( LP_ADDON_UPSELL_BASENAME );

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}*/

			return;
		}

		include_once LP_ADDON_UPSELL_PATH . '/vendor/autoload.php';

		// Set priority 9 to load before addons payments like: Stripe, Qpay,...
		add_action( 'learn-press/ready', array( $this, 'load' ), 9 );

		register_activation_hook( LP_ADDON_UPSELL_FILE, array( $this, 'on_activate' ) );
	}

	public function load() {
		include_once LP_ADDON_UPSELL_PATH . '/inc/load.php';
		self::$addon = LP_Addon_Upsell::instance();
		Order::init();
		SinglePackage::instance();
		ArchivePackage::instance();
		SingleCoursePackage::instance();
		ListCouponsTemplate::instance();
		GutenbergHandleMain::instance();
	}

	public function show_note_errors_require_lp() {
		?>
		<div class="notice notice-error">
			<p><?php echo( 'Please active <strong>LP version ' . LP_ADDON_UPSELL_REQUIRE_VER . ' or later</strong> before active <strong>' . self::$addon_info['Name'] . '</strong>' ); ?></p>
		</div>
		<?php
	}

	public function on_activate() {
		$this->create_page();
	}

	public function create_page() {
		$page_title = _x( 'Packages', 'static-page', 'learnpress-upsell' );
		$page_slug  = 'packages';
		try {
			$package_page = \LP_Settings::instance()->get( 'package.archive' );
			if ( ! $package_page || get_post_type( $package_page ) !== 'page' && get_post_status( $package_page ) !== 'publish' ) {
				$package_page       = LP_Helper::create_page(
					array(
						'post_title' => $page_title,
						'post_name'  => $page_slug,
					),
					'learn_press_packages_page_id'
				);
				$options            = \LP_Settings::instance()->get( 'package', array() );
				$options['archive'] = $package_page;
				\LP_Settings::instance()->update( 'package', $options );
			}
		} catch ( Exception $e ) {
			error_log( $e->getMessage() );
		}
	}
}

new LP_Addon_Upsell_Preload();
