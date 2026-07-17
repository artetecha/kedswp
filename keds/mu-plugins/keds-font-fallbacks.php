<?php
/**
 * Plugin Name: KEDS Font Fallbacks (Magic Fallbacks)
 * Description: Eliminates font-swap layout shift (CLS) by defining metric-matched @font-face
 *              fallback fonts ("WorkSans Fallback", "LibreBaskerville Fallback"). The block
 *              theme's theme.json already lists these fallback names in each font-family stack
 *              (e.g. "Work Sans, WorkSans Fallback, sans-serif"); this plugin supplies the
 *              matching @font-face declarations so that, during the swap window, the browser
 *              paints a size-matched fallback instead of a bare generic and text does not reflow
 *              when the real webfont arrives. The theme self-hosts the webfonts via theme.json
 *              fontFace; this plugin only supplies the fallback metrics.
 * Version: 2.0.0
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
	];

	return apply_filters( 'keds_font_fallbacks_config', $config );
}

/**
 * Sanitise a token so a stray filter value can never break out of the <style> context.
 * Allows the characters valid in font-family names / CSS custom-property identifiers.
 */
function keds_font_fallbacks_clean( string $value ): string {
	return preg_replace( '/[^A-Za-z0-9 %.,_-]/', '', $value );
}

/**
 * Print the metric-matched fallback @font-face declarations.
 *
 * The block theme's theme.json font-family stacks already list these fallback
 * family names (e.g. "Work Sans, WorkSans Fallback, sans-serif"), so no CSS
 * variable override is needed: defining the @font-face here is enough for the
 * browser to paint a size-matched fallback during the swap window instead of a
 * bare generic, so text does not reflow when the real webfont arrives.
 */
function keds_font_fallbacks_print(): void {
	if ( is_admin() ) {
		return;
	}

	$config = keds_font_fallbacks_config();

	$faces = '';
	foreach ( $config as $f ) {
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

	if ( '' === $faces ) {
		return;
	}

	// Static, sanitised CSS — not user input; escaping would corrupt the stylesheet.
	echo "\n<style id=\"keds-font-fallbacks\">" . $faces . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_head', 'keds_font_fallbacks_print', 99 );
