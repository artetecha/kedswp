<?php
/**
 * Plugin Name: KEDS Homepage Polish
 * Description: Front-page-only CSS refinements to the Elementor homepage — brand-coloured
 *              CTAs (indigo primary / indigo-outline secondary), brand icons, and the
 *              "Why KEDS works for you" benefits given tinted icon badges + card
 *              containment. Pure CSS, scoped to is_front_page(); no Elementor rebuilding,
 *              fully reversible (deactivate/remove the file). Targets are Elementor element
 *              ids (hero buttons d14a6ef/989cbc5) and the benefits section (3c4a760); if the
 *              homepage is restructured in Elementor those ids may change.
 * Version: 1.0.0
 * Author: KEDS
 * Author URI: https://kingsdivinity.org
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_front_page() ) {
			return;
		}

		$css = <<<CSS
:root{
	--keds-indigo:#24256c;--keds-indigo-dk:#1a1b52;--keds-gold:#ffb606;
	--keds-gold-dk:#d99a00;--keds-tint:#eef0f8;--keds-border:#e2e4ed;
}

/* Buttons: replace the plain black outline CTAs with brand styling. */
.elementor-widget-button .elementor-button{
	border-radius:8px!important;padding:14px 32px!important;font-weight:600!important;
	transition:background-color .15s ease,border-color .15s ease,color .15s ease!important;
}
/* Hero primary CTA (Start Here) — filled indigo, gold on hover. */
.elementor-element-d14a6ef .elementor-button{
	background-color:var(--keds-indigo)!important;color:#fff!important;
	border:2px solid var(--keds-indigo)!important;
}
.elementor-element-d14a6ef .elementor-button:hover{
	background-color:var(--keds-gold)!important;border-color:var(--keds-gold)!important;
	color:var(--keds-indigo-dk)!important;
}
/* Hero secondary CTA (Learn More) — indigo outline, fills on hover. */
.elementor-element-989cbc5 .elementor-button{
	background-color:transparent!important;color:var(--keds-indigo)!important;
	border:2px solid var(--keds-indigo)!important;
}
.elementor-element-989cbc5 .elementor-button:hover{
	background-color:var(--keds-indigo)!important;border-color:var(--keds-indigo)!important;
	color:#fff!important;
}

/* Icons: brand indigo instead of the default #333 grey. */
.elementor-icon,.elementor-icon svg,.elementor-icon svg *{
	color:var(--keds-indigo)!important;fill:var(--keds-indigo)!important;
}

/* Benefits ("Why KEDS works for you", section 3c4a760): tinted icon badges
   + card containment so the 2x2 reads as premium tiles instead of floating text. */
.elementor-element-3c4a760 .elementor-widget-thim-icon-box > .elementor-widget-container{
	background:#fff;border:1px solid var(--keds-border);border-radius:14px;
	padding:2rem 1.75rem;height:100%;
	box-shadow:0 2px 14px rgba(36,37,108,.05);
	transition:box-shadow .2s ease,transform .2s ease;
}
.elementor-element-3c4a760 .elementor-widget-thim-icon-box:hover > .elementor-widget-container{
	box-shadow:0 10px 26px rgba(36,37,108,.10);transform:translateY(-3px);
}
/* Benefit icons are Thim icon-boxes: .boxes-icon > .inner-icon > .icon > svg
   (Font Awesome, #333, 60px). Badge the inner-icon + recolour/resize the glyph. */
.elementor-element-3c4a760 .boxes-icon{margin-bottom:1.1rem!important;}
.elementor-element-3c4a760 .boxes-icon .inner-icon{
	display:inline-flex!important;align-items:center;justify-content:center;
	width:3.5rem;height:3.5rem;border-radius:14px;background:var(--keds-tint);
}
.elementor-element-3c4a760 .boxes-icon .icon{
	color:var(--keds-indigo)!important;font-size:26px!important;line-height:1!important;
}
.elementor-element-3c4a760 .boxes-icon svg{
	width:26px!important;height:26px!important;fill:var(--keds-indigo)!important;
}

/* Closing "Ready to Rise Up?" CTA (section a874fd0): indigo gradient band,
   white copy, gold button — matching the concept's final call to action. */
.elementor-element-a874fd0{
	background:linear-gradient(135deg,var(--keds-indigo),var(--keds-indigo-dk))!important;
}
.elementor-element-a874fd0 .elementor-heading-title,
.elementor-element-a874fd0 .elementor-widget-text-editor,
.elementor-element-a874fd0 .elementor-widget-text-editor *{color:#fff!important;}
.elementor-element-a874fd0 .elementor-button{
	background-color:var(--keds-gold)!important;color:var(--keds-indigo-dk)!important;
	border-color:var(--keds-gold)!important;
}
.elementor-element-a874fd0 .elementor-button:hover{
	background-color:#fff!important;border-color:#fff!important;color:var(--keds-indigo-dk)!important;
}
CSS;

		$ver = defined( 'KEDS_HOMEPAGE_POLISH_VER' ) ? KEDS_HOMEPAGE_POLISH_VER : '1.0.0';
		wp_register_style( 'keds-homepage-polish', false, array(), $ver );
		wp_enqueue_style( 'keds-homepage-polish' );
		wp_add_inline_style( 'keds-homepage-polish', $css );
	},
	// Late so the inline CSS lands after Elementor's own styles.
	99
);
