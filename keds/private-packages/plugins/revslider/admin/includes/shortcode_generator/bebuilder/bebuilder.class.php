<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/
 * @copyright 2026 ThemePunch
 */

if(!defined('ABSPATH')) exit();

class RevSliderBeBuilder {

	const SCRIPT_HANDLE = 'sr7-bebuilder';
	const STYLE_HANDLE = 'sr7-bebuilder-css';

	public static function init() {
		add_action('admin_enqueue_scripts', ['RevSliderBeBuilder', 'enqueue_admin_assets']);
		add_action('mfn_header_enqueue', ['RevSliderBeBuilder', 'enqueue_live_builder_assets'], 20);
		add_action('mfn_footer_enqueue', ['RevSliderBeBuilder', 'render_live_builder_files'], 20);
	}

	public static function enqueue_admin_assets($hook_suffix) {
		if(self::is_bebuilder_available() === false || self::is_live_builder_request() === true) return;
		if(!in_array($hook_suffix, ['post.php', 'post-new.php'], true)) return;

		self::enqueue_assets();
	}

	public static function enqueue_live_builder_assets() {
		if(self::is_bebuilder_available() === false || self::is_live_builder_request() === false) return;

		self::enqueue_assets();
	}

	public static function render_live_builder_files() {
		if(self::is_bebuilder_available() === false || self::is_live_builder_request() === false) return;

		RevSliderShortcodeWizard::enqueue_files();
	}

	protected static function enqueue_assets() {
		RevSliderShortcodeWizard::add_scripts(false, false, true);
		wp_enqueue_script('tpgsap', RS_PLUGIN_URL_CLEAN . 'public/js/libs/tpgsap.js', '', RS_REVISION, ['strategy' => 'async']);
		wp_enqueue_script('_tpt', RS_PLUGIN_URL_CLEAN . 'public/js/libs/tptools.js', '', RS_REVISION, ['strategy' => 'async']);
		wp_enqueue_script('revbuilder-backend', RS_PLUGIN_URL_CLEAN . 'admin/assets/js/tools/tools.js', [], RS_REVISION, false);
		wp_enqueue_script('sr7-tools-shortcode', RS_PLUGIN_URL_CLEAN . 'admin/assets/js/tools/shortcode.js', [], RS_REVISION, true);
		wp_enqueue_style(self::STYLE_HANDLE, RS_PLUGIN_URL_CLEAN . 'admin/includes/shortcode_generator/bebuilder/assets/css/sr7-bebuilder.css', [], RS_REVISION);
		wp_enqueue_script(self::SCRIPT_HANDLE, RS_PLUGIN_URL_CLEAN . 'admin/includes/shortcode_generator/bebuilder/assets/js/sr7-bebuilder.js', ['revbuilder-backend', 'sr7-tools-shortcode'], RS_REVISION, true);
		wp_localize_script(self::SCRIPT_HANDLE, 'SR7ShortcodeData', [
			'ajaxurl'		=> admin_url('admin-ajax.php'),
			'plugin_url'	=> RS_PLUGIN_URL_CLEAN
		]);
	}

	protected static function is_bebuilder_available() {
		return defined('MFN_THEME_VERSION') || class_exists('MfnVisualBuilder') || function_exists('mfn_opts_get');
	}

	protected static function is_live_builder_request() {
		$action = sanitize_key(wp_unslash($_GET['action'] ?? ''));

		return $action === apply_filters('betheme_slug', 'mfn') . '-live-builder';
	}
}
