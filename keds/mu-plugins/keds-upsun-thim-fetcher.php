<?php
/**
 * Plugin Name: KEDS Upsun ThimPress Vendor Fetcher
 * Description: Registers a ThimPress fetcher for `wp upsun vendor` (upsun-wp >= 0.5) so the vendoring engine can resolve authenticated Eduma / thim-core / LearnPress-add-on / revslider downloads through thim-core's own update classes, reading the site's purchase token from the database. Replaces the retired keds/scripts/thim-update-helper.php. Inert outside the vendoring CLI — it only adds a strategy object to a filter that fires during `wp upsun vendor`.
 * Version: 1.0.0
 * Author: KEDS
 * Author URI: https://kingsdivinity.org
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register lazily, inside the filter callback rather than at file load.
 *
 * The Upsun\Fetcher interface is provided by the upsun mu-plugin's
 * autoloader, whose loader file (upsun-loader.php) sorts AFTER this "keds-*"
 * file in the mu-plugins load order — so the interface does not yet exist
 * when this file runs, and an `implements \Upsun\Fetcher` at load time would
 * fatal. The upsun_vendor_fetchers filter only fires from inside
 * `wp upsun vendor`, long after every plugin (thim-core) and the Upsun
 * autoloader are up, so requiring the class there is always safe.
 */
add_filter(
	'upsun_vendor_fetchers',
	function ( array $fetchers ): array {
		// upsun-wp < 0.5 (or not installed): the framework isn't there.
		if ( ! interface_exists( '\Upsun\Fetcher' ) ) {
			return $fetchers;
		}

		require_once __DIR__ . '/inc/class-keds-thimpress-fetcher.php';
		$fetchers[] = new Keds_ThimPress_Fetcher();

		return $fetchers;
	}
);
