<?php
/**
 * KEDS (Online Learning Child) theme functions.
 *
 * The parent ("Online Learning") is a block/FSE theme. This child exists to
 * hold the KEDS brand (theme.json), the site header/footer, the marketing
 * templates and the block patterns as version-controlled code, so the design
 * deploys through Composer like every other package.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the PARENT stylesheet on the front end.
 *
 * The parent enqueues `get_stylesheet_uri()` under the `thim-style` handle.
 * In a child-theme context that resolves to THIS theme's style.css, so the
 * parent's own block CSS would otherwise never load. Enqueue it explicitly at
 * priority 9 (before the parent's priority-10 hook), so the child sheet the
 * parent enqueues still cascades last and wins.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style(
			'online-learning-parent',
			get_template_directory_uri() . '/style.css',
			array(),
			wp_get_theme( get_template() )->get( 'Version' )
		);
	},
	9
);

/**
 * Make the parent stylesheet available inside the Site/Post editor too, so the
 * editor preview matches the front end. The parent already adds its own
 * `style.css` (which resolves to the child's) as an editor style.
 */
add_action(
	'after_setup_theme',
	static function () {
		add_editor_style( get_template_directory_uri() . '/style.css' );
	},
	11
);
