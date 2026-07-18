<?php
/**
 * KEDS (Eduma Child) theme bootstrap.
 *
 * Goal: modernise the classic Eduma theme into a block-editor-first stack
 * without touching LearnPress or its premium add-ons. Almost all styling is
 * expressed in theme.json; this file only wires up the few things PHP must do:
 * enqueue the child stylesheet, opt into editor features, and register the
 * KEDS block pattern category so the /patterns library shows up grouped.
 *
 * @package eduma-child
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'KEDS_CHILD_VERSION' ) ) {
	define( 'KEDS_CHILD_VERSION', '1.0.0' );
}

/**
 * Enqueue the child stylesheet after the parent/theme assets.
 *
 * Eduma manages its own asset pipeline via thim-core, so we do not assume a
 * specific parent handle; we simply load the child stylesheet late (priority
 * 20) so its scoped chrome overrides win. Design tokens themselves come from
 * theme.json, which WordPress enqueues automatically for any theme that ships
 * one — classic themes included.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'eduma-child',
			get_stylesheet_uri(),
			array(),
			KEDS_CHILD_VERSION
		);
	},
	20
);

/**
 * Opt the child into the editor features we rely on.
 *
 * - editor-styles + wp-block-styles: front-end/editor parity for core blocks.
 * - align-wide: patterns use .alignwide / .alignfull sections.
 * - responsive-embeds & html5: modern markup for embedded media.
 */
add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'editor-styles' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support(
			'html5',
			array( 'search-form', 'gallery', 'caption', 'style', 'script' )
		);
	},
	// Late, so we augment rather than fight Eduma's own after_setup_theme setup.
	20
);

/**
 * Register the KEDS block pattern category.
 *
 * Individual patterns live as PHP files under /patterns and are auto-registered
 * by WordPress; each declares `Categories: keds` in its header to land here.
 */
add_action(
	'init',
	function () {
		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category(
				'keds',
				array(
					'label'       => __( 'KEDS', 'eduma-child' ),
					'description' => __( 'Brand sections for King\'s Evangelical Divinity School.', 'eduma-child' ),
				)
			);
		}
	}
);
