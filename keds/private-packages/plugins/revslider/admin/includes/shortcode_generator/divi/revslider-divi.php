<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/
 * @copyright 2026 ThemePunch
 */

if(!defined('ABSPATH')) exit();

/**
 * Divi 4 Fallback
 */
add_action( 'divi_extensions_init', function() {
	if (defined('ET_CORE_VERSION') && version_compare(ET_CORE_VERSION, '5', ">=")) return;
	require_once RS_PLUGIN_PATH . 'admin/includes/shortcode_generator/divi/divi4/includes/RevsliderDivi.php';
});

/**
 * Proceed with Divi 5
 */
require RS_PLUGIN_PATH . 'admin/includes/shortcode_generator/divi/modules/Modules.php';

/**
 * Enqueue style and scripts of Module Extension Example for Visual Builder.
 *
 * @since ??
 */
function d5_revslider_module_enqueue_vb_scripts() {
	if ( et_builder_d5_enabled() && et_core_is_fb_enabled() ) {
		$plugin_dir_url = RS_PLUGIN_URL_CLEAN . 'admin/includes/shortcode_generator/divi/';

		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			[
				'name'   => 'd5-revslider-module-builder-bundle-script',
				'version' => '1.0.0',
				'script' => [
					'src' => "{$plugin_dir_url}scripts/bundle.js",
					'deps'               => [
						'divi-module-library',
						'divi-vendor-wp-hooks',
					],
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				],
			]
		);

		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			[
				'name'   => 'd5-revslider-module-builder-vb-bundle-style',
				'version' => '1.0.0',
				'style' => [
					'src' => "{$plugin_dir_url}styles/vb-bundle.css",
					'deps'               => [],
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				],
			]
		);

		RevSliderShortcodeWizard::enqueue_divi_builder_files();
	}
}
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'd5_revslider_module_enqueue_vb_scripts' );

/**
 * Enqueue style and scripts of Module Extension Example
 *
 * @since ??
 */
function d5_revslider_module_enqueue_frontend_scripts() {
	$plugin_dir_url = RS_PLUGIN_URL_CLEAN . 'admin/includes/shortcode_generator/divi/';
	wp_enqueue_style( 'd5-revslider-module-builder-bundle-style', "{$plugin_dir_url}styles/bundle.css", array(), '1.0.0' );
	wp_enqueue_script('revbuilder-backend', RS_PLUGIN_URL_CLEAN . 'admin/assets/js/tools/tools.js', [], RS_REVISION, false);
}
add_action( 'wp_enqueue_scripts', 'd5_revslider_module_enqueue_frontend_scripts' );
