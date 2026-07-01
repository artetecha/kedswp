<?php
defined( 'ABSPATH' ) || exit;

use LearnPress\Upsell\Package\InitPackage;
use LP_Addon_Upsell\Elementor\PackageElementorHandler;
class LP_Addon_Upsell extends LP_Addon {

	public $version = LP_ADDON_UPSELL_VER;

	public $require_version = LP_ADDON_UPSELL_REQUIRE_VER;

	public $plugin_file = LP_ADDON_UPSELL_FILE;

	const MENU_SLUG = 'learnpress-upsell';

	public function __construct() {
		// Check require version of LearnPress.
		if ( ! $this->check_require_version_lp() ) {
			return;
		}

		parent::__construct();
		$this->hooks();
	}

	/**
	 * Instance class.
	 *
	 * @return false|mixed|self
	 */
	public static function instance() {
		static $instance;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function _includes() {
		InitPackage::instance();
		require_once LP_ADDON_UPSELL_PATH . '/inc/coupon/init.php';

		if ( is_plugin_active( 'elementor/elementor.php' ) ) {
			require_once LP_ADDON_UPSELL_PATH . '/inc/Elementor/PackageElementorHandler.php';
			PackageElementorHandler::instance();
		}
	}

	/**
	 * Init hooks.
	 */
	protected function hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 0 );
		add_filter( 'learn-press/admin/settings-tabs-array', array( $this, 'admin_settings' ) );
		add_filter( 'learn-press/rewrite/rules', array( $this, 'add_rule_package' ) );
	}

	public function admin_enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'page_' . self::MENU_SLUG ) === false ) {
			return;
		}

		$file_info = include LP_ADDON_UPSELL_PATH . '/build/upsell.asset.php';

		wp_enqueue_style( 'learnpress-upsell', LP_ADDON_UPSELL_URL . 'build/upsell.css', array(), $file_info['version'] );
		wp_enqueue_script( 'learnpress-upsell', LP_ADDON_UPSELL_URL . 'build/upsell.js', $file_info['dependencies'], $file_info['version'], true );

		wp_localize_script(
			'learnpress-upsell',
			'LP_UPSELL_LOCALIZE',
			array(
				'admin_url' => admin_url(),
				'symbol'    => learn_press_get_currency_symbol(),
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'learnpress-upsell', 'learnpress-upsell', LP_ADDON_UPSELL_PATH . '/languages' );
		}

		// Support for tinymce.
		wp_enqueue_editor();
		wp_enqueue_media(); // Support for tinymce media.
		wp_enqueue_script( 'media-audiovideo' );
		wp_enqueue_style( 'media-views' );
		wp_enqueue_script( 'mce-view' );
		wp_add_inline_script( 'learnpress-upsell', $this->react_tinymce_inline_script(), 'before' );

		do_action( 'learnpress_upsell/admin_enqueue_scripts' );
	}

	public function add_admin_menu() {
		add_submenu_page(
			'learn_press',
			esc_html__( 'Upsell', 'learnpress-upsell' ),
			esc_html__( 'Upsell', 'learnpress-upsell' ),
			'manage_options',
			self::MENU_SLUG,
			function () {
				echo '<div id="learnpress-upsell-root"></div>'; // Use for react app.
			},
			4
		);
	}

	public function admin_settings( $tabs ) {
		$tabs['upsell'] = include_once LP_ADDON_UPSELL_PATH . '/inc/class-settings.php';

		return $tabs;
	}

	private function react_tinymce_inline_script() {
		/** This filter is documented in wp-includes/class-wp-editor.php */
		$editor_settings = apply_filters( 'wp_editor_settings', array( 'tinymce' => true ), 'classic-block' );

		$tinymce_plugins = array(
			'charmap',
			'colorpicker',
			'hr',
			'lists',
			'media',
			'paste',
			'tabfocus',
			'textcolor',
			'fullscreen',
			'wordpress',
			'wpautoresize',
			'wpeditimage',
			'wpemoji',
			'wpgallery',
			'wplink',
			'wpdialogs',
			'wptextpattern',
			'wpview',
		);

		/** This filter is documented in wp-includes/class-wp-editor.php */
		$tinymce_plugins = apply_filters( 'tiny_mce_plugins', $tinymce_plugins, 'classic-block' );
		$tinymce_plugins = array_unique( $tinymce_plugins );

		$disable_captions = false;
		// Runs after `tiny_mce_plugins` but before `mce_buttons`.
		/** This filter is documented in wp-admin/includes/media.php */
		if ( apply_filters( 'disable_captions', '' ) ) {
			$disable_captions = true;
		}

		$toolbar1 = array(
			'formatselect',
			'bold',
			'italic',
			'bullist',
			'numlist',
			'blockquote',
			'alignleft',
			'aligncenter',
			'alignright',
			'link',
			'unlink',
			'wp_more',
			'spellchecker',
			'wp_add_media',
			'wp_adv',
		);

		/** This filter is documented in wp-includes/class-wp-editor.php */
		$toolbar1 = apply_filters( 'mce_buttons', $toolbar1, 'classic-block' );

		$toolbar2 = array(
			'strikethrough',
			'hr',
			'forecolor',
			'pastetext',
			'removeformat',
			'charmap',
			'outdent',
			'indent',
			'undo',
			'redo',
			'wp_help',
		);

		/** This filter is documented in wp-includes/class-wp-editor.php */
		$toolbar2 = apply_filters( 'mce_buttons_2', $toolbar2, 'classic-block' );
		/** This filter is documented in wp-includes/class-wp-editor.php */
		$toolbar3 = apply_filters( 'mce_buttons_3', array(), 'classic-block' );
		/** This filter is documented in wp-includes/class-wp-editor.php */
		$toolbar4 = apply_filters( 'mce_buttons_4', array(), 'classic-block' );
		/** This filter is documented in wp-includes/class-wp-editor.php */
		$external_plugins = apply_filters( 'mce_external_plugins', array(), 'classic-block' );

		$tinymce_settings = array(
			'plugins'              => implode( ',', $tinymce_plugins ),
			'toolbar1'             => implode( ',', $toolbar1 ),
			'toolbar2'             => implode( ',', $toolbar2 ),
			'toolbar3'             => implode( ',', $toolbar3 ),
			'toolbar4'             => implode( ',', $toolbar4 ),
			'external_plugins'     => wp_json_encode( $external_plugins ),
			'classic_block_editor' => true,
		);

		if ( $disable_captions ) {
			$tinymce_settings['wpeditimage_disable_captions'] = true;
		}

		if ( ! empty( $editor_settings['tinymce'] ) && is_array( $editor_settings['tinymce'] ) ) {
			array_merge( $tinymce_settings, $editor_settings['tinymce'] );
		}

		/** This filter is documented in wp-includes/class-wp-editor.php */
		$tinymce_settings = apply_filters( 'tiny_mce_before_init', $tinymce_settings, 'classic-block' );

		// Do "by hand" translation from PHP array to js object.
		// Prevents breakage in some custom settings.
		$init_obj = '';
		foreach ( $tinymce_settings as $key => $value ) {
			if ( is_bool( $value ) ) {
				$val       = $value ? 'true' : 'false';
				$init_obj .= $key . ':' . $val . ',';
				continue;
			} elseif ( ! empty( $value ) && is_string( $value ) && (
				( '{' === $value[0] && '}' === $value[ strlen( $value ) - 1 ] ) ||
				( '[' === $value[0] && ']' === $value[ strlen( $value ) - 1 ] ) ||
				preg_match( '/^\(?function ?\(/', $value ) ) ) {
				$init_obj .= $key . ':' . $value . ',';
				continue;
			}
			$init_obj .= $key . ':"' . $value . '",';
		}

		$init_obj = '{' . trim( $init_obj, ' ,' ) . '}';

		$script = 'window.wpEditorL10n = {
			tinymce: {
				baseURL: ' . wp_json_encode( includes_url( 'js/tinymce' ) ) . ',
				suffix: ' . ( SCRIPT_DEBUG ? '""' : '".min"' ) . ',
				settings: ' . $init_obj . ',
			}
		}';

		return $script;
	}

	/**
	 * Get package permalink slug.
	 *
	 * @return string
	 */
	public static function get_permalink_slug(): string {
		$package_slug = LP_Settings::instance()->get( 'package.slug', '' );
		return untrailingslashit( empty( $package_slug ) ? 'package' : $package_slug );
	}

	/**
	 * Add rewrite rules for package.
	 *
	 * @param array $rules
	 *
	 * @return array
	 */
	public function add_rule_package( $rules ) {
		$package_slug      = self::get_permalink_slug();
		$archive_page_slug = 'lp-packages';
		$archive_page      = \LP_Settings::instance()->get( 'package.archive' );
		if ( $archive_page ) {
			$archive_page_slug = urldecode( get_post_field( 'post_name', $archive_page ) );
		}

		// Rule archive collections
		$rules['archive-package'][] = array(
			"^{$archive_page_slug}/?$" =>
				'index.php?post_type=' . LP_PACKAGE_CPT,
		);

		// Rule single collection
		$rules['single-package'][] = array(
			"^{$package_slug}/([^/]+)/?$" =>
				'index.php?' . LP_PACKAGE_CPT . '=$matches[1]&is_single_package=1',
		);

		return $rules;
	}
}
