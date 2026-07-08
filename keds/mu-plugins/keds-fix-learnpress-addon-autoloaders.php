<?php
/**
 * Plugin Name: KEDS Fix LearnPress Add-on Autoloader Paths
 * Description: Pre-loads the Composer autoloaders of LearnPress add-ons that include theirs with a cwd-relative path. Under wp-cli run from /app the relative include resolves to /app/vendor/autoload.php instead, so the add-ons' own classes are never registered and course-review fatals on bootstrap.
 * Version: 1.0.0
 * Author: KEDS
 * Author URI: https://kingsdivinity.org
 */

defined( 'ABSPATH' ) || exit;

// These add-ons do `include_once 'vendor/autoload.php'` in their preload
// class. PHP resolves that against include_path ('.') before the calling
// script's directory, so with cwd=/app (SSH, deploy hooks, anything that
// doesn't cd into wordpress/) it silently loads the project autoloader at
// /app/vendor/autoload.php and the add-on's namespace stays unregistered.
// Requiring each add-on's real autoloader here, before regular plugins
// load, makes the later relative include a no-op wherever it resolves.
// None of these vendor trees has an autoload_files.php, so loading them
// early has no side effects; loading one for a deactivated add-on only
// registers an unused classmap.
foreach ( array(
	'learnpress-course-review',
	'learnpress-paid-membership-pro',
	'learnpress-students-list',
) as $keds_lp_addon ) {
	$keds_lp_autoloader = WP_PLUGIN_DIR . '/' . $keds_lp_addon . '/vendor/autoload.php';
	if ( is_readable( $keds_lp_autoloader ) ) {
		require_once $keds_lp_autoloader;
	}
}
unset( $keds_lp_addon, $keds_lp_autoloader );
