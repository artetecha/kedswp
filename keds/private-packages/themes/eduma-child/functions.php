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
 * IMPORTANT: Eduma enqueues its entire compiled stylesheet via
 * get_stylesheet_uri() (eduma functions.php:400, handle 'thim-style'). With a
 * child theme active, get_stylesheet_uri() resolves to THIS child's style.css,
 * so Eduma's own CSS never loads — the header/footer only look right because
 * Elementor supplies their styling, while everything else (mobile menu, LMS
 * pages, base layout) silently loses Eduma's rules. So we must load the parent
 * stylesheet explicitly, before the child overrides.
 *
 * Design tokens themselves come from theme.json, which WordPress enqueues
 * automatically for any theme that ships one — classic themes included.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		// Eduma's real compiled CSS (parent style.css), which its own
		// get_stylesheet_uri() enqueue misses under a child theme.
		wp_enqueue_style(
			'eduma-parent-style',
			get_template_directory_uri() . '/style.css',
			array(),
			KEDS_CHILD_VERSION
		);
		// Child overrides load after the parent.
		wp_enqueue_style(
			'eduma-child',
			get_stylesheet_uri(),
			array( 'eduma-parent-style' ),
			KEDS_CHILD_VERSION
		);
	},
	9
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
