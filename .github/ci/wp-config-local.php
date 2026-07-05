<?php
/**
 * CI-only WordPress configuration.
 *
 * Copied to keds/wp-config-local.php by .github/workflows/pr-checks.yml and
 * picked up by the non-Upsun fallback in keds/wp-config.php. The copy is
 * gitignored and must never be committed.
 */

define( 'DB_NAME', getenv( 'WORDPRESS_DB_NAME' ) ?: 'wordpress' );
define( 'DB_USER', getenv( 'WORDPRESS_DB_USER' ) ?: 'wp' );
define( 'DB_PASSWORD', getenv( 'WORDPRESS_DB_PASSWORD' ) ?: 'wp' );
define( 'DB_HOST', getenv( 'WORDPRESS_DB_HOST' ) ?: '127.0.0.1:3306' );

define( 'WP_HOME', 'http://127.0.0.1:8080' );
define( 'WP_SITEURL', WP_HOME );

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', '/tmp/wp-debug.log' );
define( 'WP_DEBUG_DISPLAY', false );

define( 'WP_ENVIRONMENT_TYPE', 'local' );

// The redis-cache drop-in is installed unconditionally by the composer
// postbuild script; point it at the CI service container and degrade
// gracefully instead of failing every request if it's unreachable.
define( 'WP_REDIS_CLIENT', 'phpredis' );
define( 'WP_REDIS_HOST', '127.0.0.1' );
define( 'WP_REDIS_PORT', 6379 );
define( 'WP_REDIS_PREFIX', 'keds:ci:' );
define( 'WP_CACHE_KEY_SALT', WP_REDIS_PREFIX );
define( 'WP_REDIS_GRACEFUL', true );
define( 'WP_REDIS_TIMEOUT', 0.5 );
define( 'WP_REDIS_READ_TIMEOUT', 0.5 );

// Premium plugins call license/telemetry endpoints from activation hooks;
// make those wp_remote_* calls fail fast instead of stacking timeouts.
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'WP_ACCESSIBLE_HOSTS', 'localhost,127.0.0.1' );

// Deterministic keys are fine for a throwaway CI database.
define( 'AUTH_KEY', 'ci-auth-key' );
define( 'SECURE_AUTH_KEY', 'ci-secure-auth-key' );
define( 'LOGGED_IN_KEY', 'ci-logged-in-key' );
define( 'NONCE_KEY', 'ci-nonce-key' );
define( 'AUTH_SALT', 'ci-auth-salt' );
define( 'SECURE_AUTH_SALT', 'ci-secure-auth-salt' );
define( 'LOGGED_IN_SALT', 'ci-logged-in-salt' );
define( 'NONCE_SALT', 'ci-nonce-salt' );
