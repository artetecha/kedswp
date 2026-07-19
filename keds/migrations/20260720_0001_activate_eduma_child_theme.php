<?php
/**
 * Activate the KEDS block-refresh child theme (`eduma-child`).
 *
 * Composer installs the theme code; this migration flips WordPress onto it.
 * `eduma-child` is a child of the stock `eduma` theme, so it inherits every
 * Eduma template and the LMS/Elementor chrome keeps working — it only adds the
 * theme.json design system + the KEDS block pattern library.
 *
 * GO-LIVE NOTE: on deploy to production (merge to main) this switches the
 * PRODUCTION theme. On the preview env it's a no-op if the theme is already
 * active. Idempotent: runs once per database.
 *
 * Theme mods are stored per-theme, so any Eduma *Customizer* settings do not
 * carry over automatically — on KEDS the header/footer/menus are Elementor
 * (Thim Elementor Kit), which is theme-independent, so the chrome is
 * unaffected (verified on the pr-83 preview after activation).
 */

return static function () {
	$target  = 'eduma-child';
	$current = get_stylesheet();

	if ( $current === $target ) {
		echo "Theme already '{$target}'; nothing to do.\n";
		return;
	}

	// Only ever switch away from stock Eduma — never clobber an unexpected theme.
	if ( 'eduma' !== $current ) {
		throw new RuntimeException(
			"Refusing to switch theme: expected 'eduma' active, found '{$current}'."
		);
	}

	if ( ! wp_get_theme( $target )->exists() ) {
		throw new RuntimeException( "Theme '{$target}' is not installed." );
	}

	switch_theme( $target );

	if ( get_stylesheet() !== $target ) {
		throw new RuntimeException( "switch_theme did not stick; still on '" . get_stylesheet() . "'." );
	}

	echo "Switched theme '{$current}' -> '{$target}'.\n";
};
