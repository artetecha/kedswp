<?php
/**
 * Site Kit is gone for good: it was deleted on Pantheon (replaced by the
 * hand-pasted GTM container, now keds-google-tag-manager.php) and the
 * package is removed from composer.json in the same change. This clears
 * what it left in the database: its options (including the encrypted OAuth
 * credentials, undecryptable under this site's salts anyway), per-user
 * tokens in usermeta, and its scheduled hooks, which have executed as
 * no-ops on every cron run since the plugin went inactive.
 */

return static function () {
	global $wpdb;

	$option_names = $wpdb->get_col(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'googlesitekit\_%'"
	);
	foreach ( $option_names as $option_name ) {
		delete_option( $option_name );
	}
	echo 'Deleted ' . count( $option_names ) . " Site Kit options.\n";

	$meta_rows = $wpdb->get_results(
		"SELECT user_id, meta_key FROM {$wpdb->usermeta} WHERE meta_key LIKE '%googlesitekit\_%'"
	);
	foreach ( $meta_rows as $row ) {
		delete_user_meta( $row->user_id, $row->meta_key );
	}
	echo 'Deleted ' . count( $meta_rows ) . " Site Kit usermeta rows.\n";

	foreach ( array(
		'googlesitekit_cron_update_remote_features',
		'googlesitekit_email_reporting_cleanup',
		'googlesitekit_email_reporting_monitor',
	) as $hook ) {
		wp_unschedule_hook( $hook );
	}
	echo "Unscheduled Site Kit cron hooks.\n";
};
