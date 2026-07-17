<?php
/**
 * Plugin Name: KEDS Font Fallbacks (Magic Fallbacks)
 * Description: Eliminates font-swap layout shift (CLS) by defining metric-matched fallback
 *              fonts and injecting them into the theme (--thim-font-*) and Elementor
 *              (--e-global-typography-*) font-family CSS variables. During the swap window the
 *              browser paints a size-matched fallback instead of a bare generic, so text does
 *              not reflow when the real webfont arrives. OMGF still owns loading/preload/unload;
 *              this plugin only supplies the fallback metrics — the free equivalent of OMGF
 *              Pro's "Magic Fallbacks".
 * Version: 1.0.0
 * Author: KEDS
 * Author URI: https://kingsdivinity.org
 *
 * ---------------------------------------------------------------------------
 * Tuning the metrics
 * ---------------------------------------------------------------------------
 * The size-adjust / *-override values below are SEED approximations. They are already far
 * better than a bare generic, but for pixel-exact matching regenerate them against the actual
 * woff2 files OMGF produced, e.g. with capsize:
 *
 *   npm i -D @capsizecss/unpack @capsizecss/core
 *   node -e '
 *     const {fromFile}=require("@capsizecss/unpack");
 *     const {createFontStack}=require("@capsizecss/core");
 *     (async()=>{
 *       const m=await fromFile("path/to/WorkSans-Regular.woff2");
 *       // arialMetrics: the Arial record from @capsizecss/metrics
 *       console.log(createFontStack([m, arialMetrics]));
 *     })();'
 *
 * or the "Fontaine" project / https://screenspan.net/fallback. Paste the resulting numbers
 * into the config array (or override at runtime via the `keds_font_fallbacks_config` filter).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fallback definitions, keyed by the primary (web) font family.
 *
 * fallback = the @font-face name we mint for the metric-matched system font
 * local    = the system font whose glyphs we borrow (must exist on the client to take effect)
 * generic  = the last-resort generic that terminates the stack
 * The four override properties are the metric match (percentages, as strings).
 *
 * @return array<string,array<string,string>>
 */
function keds_font_fallbacks_config(): array {
	$config = [
		// CLS-critical: body / nav / most UI text.
		'Work Sans'         => [
			'fallback'           => 'WorkSans Fallback',
			'local'              => 'Arial',
			'generic'            => 'sans-serif',
			'size-adjust'        => '95.5%',
			'ascent-override'    => '94%',
			'descent-override'   => '24.7%',
			'line-gap-override'  => '0%',
		],
		// CLS-critical: every heading, including the hero H1.
		'Libre Baskerville' => [
			'fallback'           => 'LibreBaskerville Fallback',
			'local'              => 'Georgia',
			'generic'            => 'serif',
			'size-adjust'        => '106%',
			'ascent-override'    => '90%',
			'descent-override'   => '24%',
			'line-gap-override'  => '0%',
		],
		// Elementor primary — buttons ("Start Here"), some widget headings.
		'Roboto Slab'       => [
			'fallback'           => 'RobotoSlab Fallback',
			'local'              => 'Georgia',
			'generic'            => 'serif',
			'size-adjust'        => '106%',
			'ascent-override'    => '103%',
			'descent-override'   => '27%',
			'line-gap-override'  => '0%',
		],
		// Elementor text/accent.
		'Roboto'            => [
			'fallback'           => 'Roboto Fallback',
			'local'              => 'Arial',
			'generic'            => 'sans-serif',
			'size-adjust'        => '100%',
			'ascent-override'    => '92.7%',
			'descent-override'   => '24.4%',
			'line-gap-override'  => '0%',
		],
		// Elementor secondary.
		'Roboto Condensed'  => [
			'fallback'           => 'RobotoCondensed Fallback',
			'local'              => 'Arial Narrow',
			'generic'            => 'sans-serif',
			'size-adjust'        => '88%',
			'ascent-override'    => '100%',
			'descent-override'   => '26%',
			'line-gap-override'  => '0%',
		],
	];

	return apply_filters( 'keds_font_fallbacks_config', $config );
}

/**
 * Map of the CSS custom properties this site uses for font-family, to the primary family each
 * one currently carries. Discovered on kingsdivinity.org; override via filter if it changes.
 *
 * @return array<string,string>
 */
function keds_font_fallbacks_vars(): array {
	return apply_filters(
		'keds_font_fallbacks_vars',
		[
			'--thim-font-body-font-family'                => 'Work Sans',
			'--thim-font-title-font-family'               => 'Libre Baskerville',
			'--e-global-typography-primary-font-family'   => 'Roboto Slab',
			'--e-global-typography-secondary-font-family' => 'Roboto Condensed',
			'--e-global-typography-text-font-family'      => 'Roboto',
			'--e-global-typography-accent-font-family'    => 'Roboto',
		]
	);
}

/**
 * Sanitise a token so a stray filter value can never break out of the <style> context.
 * Allows the characters valid in font-family names / CSS custom-property identifiers.
 */
function keds_font_fallbacks_clean( string $value ): string {
	return preg_replace( '/[^A-Za-z0-9 %.,_-]/', '', $value );
}

/**
 * Print the fallback @font-face rules and the variable overrides.
 *
 * Priority 99 + the specificity trick (`:root:root` beats the theme's `:root`;
 * `body[class*="elementor-kit"]` beats Elementor's `.elementor-kit-NNN`) guarantees these win
 * the cascade regardless of source order, without hardcoding the kit id.
 */
function keds_font_fallbacks_print(): void {
	if ( is_admin() ) {
		return;
	}

	$config = keds_font_fallbacks_config();
	$vars   = keds_font_fallbacks_vars();

	// 1. Metric-matched @font-face declarations.
	$faces = '';
	foreach ( $config as $primary => $f ) {
		$faces .= sprintf(
			'@font-face{font-family:"%s";src:local("%s");size-adjust:%s;ascent-override:%s;descent-override:%s;line-gap-override:%s;}',
			keds_font_fallbacks_clean( $f['fallback'] ),
			keds_font_fallbacks_clean( $f['local'] ),
			keds_font_fallbacks_clean( $f['size-adjust'] ),
			keds_font_fallbacks_clean( $f['ascent-override'] ),
			keds_font_fallbacks_clean( $f['descent-override'] ),
			keds_font_fallbacks_clean( $f['line-gap-override'] )
		);
	}

	// 2. Redefine each font-family variable as: "Primary", "Primary Fallback", generic.
	$decls = '';
	foreach ( $vars as $var => $primary ) {
		if ( empty( $config[ $primary ] ) ) {
			continue;
		}
		$f      = $config[ $primary ];
		$decls .= sprintf(
			'%s:"%s","%s",%s;',
			keds_font_fallbacks_clean( $var ),
			keds_font_fallbacks_clean( $primary ),
			keds_font_fallbacks_clean( $f['fallback'] ),
			keds_font_fallbacks_clean( $f['generic'] )
		);
	}

	if ( '' === $decls ) {
		return;
	}

	$css = $faces . ':root:root,body[class*="elementor-kit"]{' . $decls . '}';

	// Static, sanitised CSS — not user input; escaping would corrupt the stylesheet.
	echo "\n<style id=\"keds-font-fallbacks\">" . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_head', 'keds_font_fallbacks_print', 99 );
