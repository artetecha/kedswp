<?php
/**
 * Plugin Name: KEDS Upsun plugin configuration
 * Description: KEDS-specific configuration for the generic Upsun mu-plugin (page-cache tuning for PMPro/LearnPress, SMTP ownership).
 * Version: 1.0.0
 * Author: KEDS
 * Author URI: https://kingsdivinity.org
 *
 * Loads before upsun-loader.php (k < u) and the Upsun plugin boots its
 * modules at muplugins_loaded priority 0, so these filters are always
 * registered in time.
 */

defined( 'ABSPATH' ) || exit;

/**
 * PMPro's visit-dedup cookie adds Set-Cookie to every anonymous response,
 * which makes the router treat them all as uncacheable. PMPro degrades
 * gracefully without it (visits are simply counted per uncached request).
 */
add_filter( 'pmpro_set_visit_cookie', '__return_false' );

/**
 * LearnPress guest sessions set lp_session_guest on every anonymous page
 * view; stripping it keeps those responses cacheable. Guest sessions are
 * IP-based instead (learn_press_store_ip_customer_session, enabled by a
 * deploy migration).
 */
add_filter( 'upsun_page_cache_strip_cookies', function ( array $prefixes ) {
	$prefixes[] = 'lp_session_guest';

	return $prefixes;
} );

/**
 * Belt and braces for LearnPress pages that are dynamic without setting
 * DONOTCACHEPAGE (the Upsun plugin already covers the WooCommerce ones).
 */
add_filter( 'upsun_page_cache_skip', function ( $skip ) {
	if ( $skip ) {
		return $skip;
	}

	if ( function_exists( 'learn_press_is_checkout' ) && ( learn_press_is_checkout() || learn_press_is_profile() ) ) {
		return true;
	}

	return $skip;
} );

/**
 * wp-mail-smtp owns outgoing mail on KEDS; keep the Upsun SMTP relay out
 * of the way.
 */
add_filter( 'upsun_configure_smtp', '__return_false' );

/**
 * Preview safety: the LearnPress Stripe add-on has no runtime test-mode
 * switch (LP bulk-loads settings through its own settings cache, so option
 * filters never reach the gateway), so the safe move is removing the
 * gateway from checkout on previews entirely. WooCommerce Stripe test mode,
 * wp_mail interception (which also covers FluentCRM sends), and WooCommerce
 * webhook pausing are handled by the Upsun plugin's built-in protections.
 */
add_filter( 'upsun_safe_previews_actions', function ( array $protections ) {
	$protections['learnpress-stripe'] = array(
		'label'    => 'LearnPress Stripe',
		'register' => function () {
			add_filter( 'learn-press/payment-methods', function ( $methods ) {
				if ( is_array( $methods ) && function_exists( 'Upsun\\is_preview_environment' ) && \Upsun\is_preview_environment() ) {
					unset( $methods['stripe'] );
				}

				return $methods;
			}, 999 );
		},
		'status'   => function () {
			if ( ! defined( 'LP_ADDON_STRIPE_PAYMENT_PATH' ) && ! class_exists( 'LP_Gateway_Stripe' ) ) {
				return array(
					'state'  => 'inactive',
					'detail' => 'LearnPress Stripe not detected',
				);
			}

			return array(
				'state'  => 'active',
				'detail' => 'gateway removed from checkout (no runtime test-mode switch)',
			);
		},
	);

	return $protections;
} );
