<?php
/**
 * Plugin Name: KEDS Edge Cache
 * Description: Lets the Upsun router cache anonymous frontend pages: suppresses PMPro's visit cookie and emits s-maxage on session-free responses.
 * Version: 1.0.0
 * Author: KEDS
 * Author URI: https://kingsdivinity.org
 */

defined( 'ABSPATH' ) || exit;

/**
 * PMPro's visit-dedup cookie adds Set-Cookie to every anonymous response,
 * which makes the router treat them all as uncacheable. PMPro degrades
 * gracefully without it (visits are simply counted per uncached request).
 */
add_filter( 'pmpro_set_visit_cookie', '__return_false' );

/**
 * Emit Cache-Control for the router on anonymous, session-free page views.
 * max-age=0 keeps browsers revalidating; s-maxage lets the router serve
 * repeat anonymous hits without touching PHP. The route config keys the
 * cache on auth/session cookies, so logged-in users can never be served
 * a cached anonymous page.
 */
add_action( 'template_redirect', function () {
	if ( headers_sent() || is_admin() || is_user_logged_in() ) {
		return;
	}

	if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}

	if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		|| ( defined( 'DOING_CRON' ) && DOING_CRON )
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) // set by Woo/LearnPress on cart, checkout, account pages
	) {
		return;
	}

	if ( is_preview() || is_customize_preview() || post_password_required() ) {
		return;
	}

	// Any auth/session/commerce cookie means a personalised response.
	foreach ( array_keys( $_COOKIE ) as $name ) {
		if ( preg_match( '/^(wordpress_|wp-postpass|wp_woocommerce_session_|woocommerce_|PHPSESSID|comment_author_)/', (string) $name ) ) {
			return;
		}
	}

	// If another component already declared this response uncacheable or is
	// establishing a session, respect that instead of overriding it.
	foreach ( headers_list() as $sent ) {
		if ( 0 === stripos( $sent, 'set-cookie:' ) ) {
			return;
		}
		if ( 0 === stripos( $sent, 'cache-control:' ) && preg_match( '/no-cache|no-store|private/i', $sent ) ) {
			return;
		}
	}

	// Belt and braces for pages Woo/LP mark dynamic without DONOTCACHEPAGE.
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		return;
	}
	if ( function_exists( 'learn_press_is_checkout' ) && ( learn_press_is_checkout() || learn_press_is_profile() ) ) {
		return;
	}

	header( 'Cache-Control: public, max-age=0, s-maxage=600' );
}, 99 );
