<?php
/**
 * Runs on the Upsun container via `wp eval-file - <action> [slug]`.
 * Generic licensed-update channel: reads WordPress's own update transients,
 * which premium plugins (Fluent pro, Paid Memberships Pro, ...) populate
 * with authenticated download URLs when their license is active on this
 * site. Requests are identical to what wp-admin would make — same
 * credentials, same egress IP; tokens never leave the container.
 *
 * Actions:
 *   list          JSON: for every plugin/theme WP knows an update for,
 *                 {slug: {"version": v, "package": bool}} — package tells
 *                 whether a download URL is actually available.
 *   coverage      One slug per line: every plugin/theme visible to the WP
 *                 update system (response + no_update), i.e. packages whose
 *                 updater is wired up — used by the coverage audit.
 *   link <slug>   Print the (authenticated) download URL for one package.
 *                 Treat output as a secret.
 */

$action = $args[0] ?? 'list';

wp_update_plugins();
wp_update_themes();

$plugin_tr = get_site_transient( 'update_plugins' );
$theme_tr  = get_site_transient( 'update_themes' );

// plugin_file => update object, keyed down to directory slug.
function keds_plugin_updates( $tr, $key ) {
	$out = array();
	foreach ( (array) ( $tr->$key ?? array() ) as $file => $data ) {
		$slug = dirname( $file );
		if ( '.' === $slug || '' === $slug ) {
			continue; // single-file plugins are never vendored packages
		}
		$out[ $slug ] = (object) $data;
	}
	return $out;
}

if ( 'list' === $action ) {
	$out = array( 'plugins' => array(), 'themes' => array() );

	foreach ( keds_plugin_updates( $plugin_tr, 'response' ) as $slug => $data ) {
		if ( ! empty( $data->new_version ) ) {
			$out['plugins'][ $slug ] = array(
				'version' => $data->new_version,
				'package' => ! empty( $data->package ),
			);
		}
	}

	foreach ( (array) ( $theme_tr->response ?? array() ) as $slug => $data ) {
		$data = (array) $data;
		if ( ! empty( $data['new_version'] ) ) {
			$out['themes'][ $slug ] = array(
				'version' => $data['new_version'],
				'package' => ! empty( $data['package'] ),
			);
		}
	}

	// FORCE_OBJECT: empty PHP arrays would serialize as [] (JSON list) and
	// break map-shaped parsing on the consumer side. Every array in $out is
	// a map, so forcing objects is lossless.
	echo json_encode( $out, JSON_FORCE_OBJECT );
	exit( 0 );
}

if ( 'coverage' === $action ) {
	$seen = array();
	foreach ( array( 'response', 'no_update' ) as $key ) {
		foreach ( keds_plugin_updates( $plugin_tr, $key ) as $slug => $data ) {
			$seen[ $slug ] = true;
		}
		foreach ( (array) ( $theme_tr->$key ?? array() ) as $slug => $data ) {
			$seen[ $slug ] = true;
		}
	}
	echo implode( "\n", array_keys( $seen ) ), "\n";
	exit( 0 );
}

if ( 'link' === $action ) {
	$slug = $args[1] ?? '';
	if ( '' === $slug ) {
		fwrite( STDERR, "link action needs a slug.\n" );
		exit( 1 );
	}

	$plugins = keds_plugin_updates( $plugin_tr, 'response' );
	$package = $plugins[ $slug ]->package ?? '';

	if ( '' === $package ) {
		$themes  = (array) ( $theme_tr->response ?? array() );
		$package = ( (array) ( $themes[ $slug ] ?? array() ) )['package'] ?? '';
	}

	if ( empty( $package ) ) {
		fwrite( STDERR, "no download url for {$slug} (unlicensed, or no update pending)\n" );
		exit( 1 );
	}

	echo $package;
	exit( 0 );
}

fwrite( STDERR, "unknown action {$action}\n" );
exit( 1 );
