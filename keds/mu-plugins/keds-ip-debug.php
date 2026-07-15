<?php
/**
 * Plugin Name: KEDS IP debug (TEMPORARY — remove after diagnosis)
 * Description: Logs the client-IP-related $_SERVER values on any request that
 *   includes ?upsun_ipdebug=1, to diagnose how the client IP arrives through
 *   Cloudflare -> Upsun router -> PHP. Writes to the app error log only (never
 *   to the response), so it exposes nothing to the requester.
 *
 * Remove this file once the Cloudflare real-IP handling is fixed.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	if ( ! isset( $_GET['upsun_ipdebug'] ) ) {
		return;
	}

	$keys = array(
		'REMOTE_ADDR',                 // what PHP sees (post-router, post-mu-plugin)
		'UPSUN_ORIGINAL_REMOTE_ADDR',  // set only if the upsun cloudflare module restored
		'HTTP_X_FORWARDED_FOR',        // forwarding chain (expect real-client + CF edge)
		'HTTP_X_CLIENT_IP',            // Upsun router's client-IP header
		'HTTP_X_FORWARDED_PROTO',
		'HTTP_CF_CONNECTING_IP',       // Cloudflare's real-visitor header
		'HTTP_CF_RAY',                 // present => request transited Cloudflare
		'HTTP_CF_VISITOR',
		'HTTP_TRUE_CLIENT_IP',         // CF Enterprise alt of CF-Connecting-IP
	);

	$out = array();
	foreach ( $keys as $k ) {
		$out[ $k ] = $_SERVER[ $k ] ?? '(unset)';
	}

	error_log( 'KEDS-IPDEBUG ' . wp_json_encode( $out ) );
}, 0 );
