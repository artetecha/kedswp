<?php
/**
 * Scheduled events carried over in the Pantheon database whose plugins do
 * not exist in this build (Pantheon mu-plugin, Object Cache Pro, Jetpack).
 * They execute as no-ops but re-appear with every content import. Only
 * known Pantheon-origin hooks are removed: a hook having no listener is
 * NOT proof it is dead (e.g. Site Kit registers its cron listeners
 * conditionally), so never delete by that heuristic.
 */

return static function () {
	foreach ( array(
		'pantheon_cron',
		'objectcache_metrics_snapshot',
		'objectcache_prune_analytics',
		'jetpack_clean_nonces',
		'jetpack_v2_heartbeat',
	) as $hook ) {
		wp_unschedule_hook( $hook );
	}
};
