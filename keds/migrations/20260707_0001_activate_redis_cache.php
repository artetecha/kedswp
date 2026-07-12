<?php
/**
 * The Pantheon active_plugins list (imported before db-import.sh learned to
 * preserve the local list) lacks redis-cache: Pantheon ran Object Cache Pro,
 * which this build removed. The object-cache drop-in works either way, but
 * the plugin provides the `wp redis` command deploy.sh relies on and the
 * admin UI. Idempotent; also re-runs after every content import as insurance.
 */

return static function () {
	if ( ! function_exists( 'activate_plugin' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$result = activate_plugin( 'redis-cache/redis-cache.php' );

	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( $result->get_error_message() );
	}
};
