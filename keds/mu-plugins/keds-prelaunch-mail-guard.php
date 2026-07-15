<?php
/**
 * Plugin Name: KEDS pre-launch mail guard (TEMPORARY — delete at go-live)
 * Description: Hard-stops ALL WordPress-originated outbound mail while KEDS runs on Upsun before go-live. Its mere presence mutes mail; DELETING THIS FILE (git rm + deploy) is the go-live switch. Previews stay protected by the Upsun plugin's SafePreviews regardless of this file.
 * Version: 2.0.0
 * Author: KEDS
 *
 * WHY THIS EXISTS: main on Upsun is a production-TYPE environment but not the
 * live site yet (kingsdivinity.org still serves from Pantheon). It runs a
 * clone of production data with live Brevo credentials, and SafePreviews only
 * mutes mail on NON-production environments — so main would (and did) email
 * real people via cron-driven FluentCRM/Woo/LearnPress sends. Stopgap until
 * cutover.
 *
 * GO LIVE — the whole switch: delete this file, commit, deploy.
 *   - Production then sends normally (Brevo via WP Mail SMTP).
 *   - Previews remain muted by SafePreviews (they never relied on this file).
 *   - Need an emergency re-mute after deletion? WP Mail SMTP → "Do Not Send"
 *     in wp-admin is an instant, no-deploy kill switch.
 *
 * SCOPE: this only stops mail ORIGINATED IN WORDPRESS (wp_mail → WP Mail SMTP
 * → Brevo). Campaigns/automations configured inside the Brevo dashboard send
 * from Brevo's own infrastructure and must be paused there, not here.
 */

defined( 'ABSPATH' ) || exit;

// Belt: WP Mail SMTP honors this and reflects it in its UI (covers its Brevo
// API mailer too, which runs inside wp_mail()).
if ( ! defined( 'WPMS_DO_NOT_SEND' ) ) {
	define( 'WPMS_DO_NOT_SEND', true );
}

// Braces: mailer-agnostic short-circuit at the very top of wp_mail(), before
// WP Mail SMTP constructs or sends anything. Returns true so callers follow
// their success path (no error-driven retries).
add_filter(
	'pre_wp_mail',
	function ( $short_circuit, $atts ) {
		$to = '';

		if ( is_array( $atts ) && isset( $atts['to'] ) ) {
			$to = is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : (string) $atts['to'];
		}

		$subject = is_array( $atts ) ? (string) ( $atts['subject'] ?? '' ) : '';

		error_log( sprintf(
			'[keds-prelaunch-mail-guard] Suppressed wp_mail (pre-launch): to=%s subject=%s',
			$to,
			$subject
		) );

		return true;
	},
	PHP_INT_MAX,
	2
);
