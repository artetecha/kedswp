<?php
/**
 * Switch the active theme from the classic Eduma theme to the block/FSE
 * `online-learning-child` theme (a child of ThimPress "Online Learning"),
 * and activate the Thim Blocks plugin the block theme relies on.
 *
 * Composer installs both the parent theme, the child theme and thim-blocks;
 * this migration only flips WordPress runtime state. LearnPress and all its
 * add-ons are unaffected (they are theme-independent plugins), and the Eduma
 * theme stays installed-but-inactive so the ThimPress add-on entitlement and
 * update path are preserved.
 *
 * Elementor and thim-elementor-kit are intentionally left ACTIVE here: any
 * page still built with Elementor keeps rendering inside the new theme's
 * page template while its content is rebuilt in blocks. Their teardown is a
 * later migration, once no content references them.
 *
 * Idempotent: re-running simply re-asserts the same theme + plugin state.
 */

return static function () {
	if ( ! function_exists( 'activate_plugin' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// Refuse to switch to a theme that is not actually installed — better to
	// abort the deploy than to leave the site pointing at a missing theme.
	$theme = wp_get_theme( 'online-learning-child' );
	if ( ! $theme->exists() ) {
		throw new RuntimeException( 'Theme online-learning-child is not installed; cannot switch.' );
	}
	if ( $theme->errors() ) {
		throw new RuntimeException( 'Theme online-learning-child has errors: ' . implode( '; ', $theme->errors()->get_error_messages() ) );
	}

	switch_theme( 'online-learning-child' );

	// The block theme depends on Thim Blocks (patterns, account-page login).
	$result = activate_plugin( 'thim-blocks/thim-blocks.php' );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( 'Failed to activate thim-blocks: ' . $result->get_error_message() );
	}
};
