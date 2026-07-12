<?php
/**
 * wp-native-php-sessions is Pantheon's workaround for multi-container PHP
 * session storage. On single-instance Upsun, PHP's default file sessions
 * work; the plugin only added a DB round-trip per session request. Its
 * sole consumer here is PMPro's checkout captcha flow, which falls back
 * to file sessions transparently. Silent deactivation: the plugin's code
 * is no longer in the build, so its deactivation hooks cannot run.
 */

return static function () {
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	foreach ( (array) get_option( 'active_plugins', array() ) as $basename ) {
		if ( 0 === strpos( (string) $basename, 'wp-native-php-sessions/' ) ) {
			deactivate_plugins( $basename, true );
		}
	}
};
