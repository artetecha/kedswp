
<?php
/**
 * Plugin Name: KEDS Disable LearnPress guest session
 * Description: Prevent LearnPress from starting guest sessions so pages stay cacheable.
 * Version: 1.0
 * Author: KEDS
 * Author URI: https://kingsdivinity.org
 */

defined( 'ABSPATH' ) || exit;

/**
 * Strip the lp_session_guest Set-Cookie header for guest users.
 * This runs after LearnPress sets the cookie, but before headers are sent.
 */
add_action( 'send_headers', function () {

	// Only on frontend, only for guests.
	if ( is_admin() || ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) ) {
		return;
	}

	// Remove the lp_session_guest cookie by expiring it (best-effort cleanup of existing cookies).
	if ( ! empty( $_COOKIE['lp_session_guest'] ) ) {
		$path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
		$domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
		$secure = is_ssl();

		setcookie( 'lp_session_guest', '', time() - YEAR_IN_SECONDS, $path, $domain, $secure, true );
		unset( $_COOKIE['lp_session_guest'] );
	}

}, 999 ); // Very late priority to run after LearnPress.

/**
 * Use header_register_callback to strip Set-Cookie headers containing lp_session_guest
 * just before they're sent to the client.
 */
if ( function_exists( 'header_register_callback' ) ) {
	header_register_callback( function () {

		// Only on frontend, only for guests.
		if ( is_admin() || ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) ) {
			return;
		}

		// Get all headers that are about to be sent.
		$headers = headers_list();
		$cleaned_headers = [];

		foreach ( $headers as $header ) {
			// If it's a Set-Cookie header for lp_session_guest, skip it.
			if ( stripos( $header, 'Set-Cookie:' ) === 0 && stripos( $header, 'lp_session_guest=' ) !== false ) {
				continue; // Don't include this header.
			}
			$cleaned_headers[] = $header;
		}

		// Clear all headers and re-send only the cleaned ones.
		if ( count( $cleaned_headers ) < count( $headers ) ) {
			header_remove(); // Remove all headers.

			foreach ( $cleaned_headers as $header ) {
				header( $header, false ); // Re-send cleaned headers.
			}
		}
	} );
}

/**
 * Debug headers.
 */
add_filter( 'wp_headers', function ( array $headers ) {
	$headers['X-KEDS-MU'] = '1';

	if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
		$headers['X-KEDS-LP-SESSION'] = 'skipped-logged-in';
	} elseif ( is_admin() ) {
		$headers['X-KEDS-LP-SESSION'] = 'skipped-admin';
	} else {
		$headers['X-KEDS-LP-SESSION'] = 'cookie-stripped';
	}

	return $headers;
} );