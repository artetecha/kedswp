<?php
/**
 * Scheduled events carried over in the Pantheon database whose plugins do
 * not exist in this build (Pantheon mu-plugin, Object Cache Pro). They
 * execute as no-ops but re-appear with every content import. Only known
 * dead hooks are removed: a hook having no obvious listener is NOT proof
 * it is dead — the original (shell) version of this migration also deleted
 * jetpack_clean_nonces and jetpack_v2_heartbeat, believing them Pantheon
 * leftovers, but they are maintained by WooCommerce's embedded
 * automattic/jetpack-connection package, which re-creates them within
 * seconds of deletion (verified live on pr-52, 2026-07-12). Deleting them
 * was harmless churn; they are deliberately no longer targeted.
 */

return static function () {
	foreach ( array(
		'pantheon_cron',
		'objectcache_metrics_snapshot',
		'objectcache_prune_analytics',
	) as $hook ) {
		wp_unschedule_hook( $hook );
	}
};
