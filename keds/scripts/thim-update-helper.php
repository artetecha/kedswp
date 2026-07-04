<?php
/**
 * Runs on the Upsun container via `wp eval-file - <action> [slug]`.
 * Drives thim-core's own update classes so requests are identical to the
 * ones the wp-admin dashboard makes (same credentials, same egress IP).
 *
 * Actions:
 *   list          JSON map of latest available versions for all
 *                 Thim-distributed plugins plus the active theme.
 *   link <slug>   Print the authenticated download URL for one package
 *                 (theme slug supported). URL contains the license token —
 *                 treat as a secret.
 */

if ( ! class_exists( 'Thim_Remote_Helper' ) || ! class_exists( 'Thim_Admin_Config' ) ) {
	fwrite( STDERR, "thim-core is not loaded on this site.\n" );
	exit( 1 );
}

$action = $args[0] ?? 'list';
$theme_slug = wp_get_theme()->get_template();

if ( 'list' === $action ) {
	$slugs = array();
	foreach ( Thim_Plugins_Manager::get_external_plugins() as $plugin ) {
		$slugs[] = $plugin->get_slug();
	}

	$catalog = Thim_Remote_Helper::post(
		Thim_Admin_Config::get( 'api_update_plugins' ),
		array( 'body' => array( 'plugins' => $slugs, 'action' => 'plugin_information' ) ),
		true
	);

	$out = array( 'plugins' => array(), 'theme' => array( 'slug' => $theme_slug, 'latest' => null ) );

	if ( ! is_wp_error( $catalog ) && is_array( $catalog ) ) {
		foreach ( $catalog as $item ) {
			if ( isset( $item->slug, $item->version ) ) {
				$out['plugins'][ $item->slug ] = $item->version;
			}
		}
	}

	// Theme latest version: GET /license/version?site_code&slug (see
	// Thim_Theme_Envato_Check_Update::fetch_remote_version).
	$site_code = Thim_Product_Registration::get_data_theme_register( 'purchase_token' );
	if ( $site_code ) {
		$url = add_query_arg(
			array( 'site_code' => $site_code, 'slug' => $theme_slug ),
			Thim_Admin_Config::get( 'api_thim_market' ) . '/license/version'
		);
		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ( $body['status'] ?? '' ) === 'success' && ! empty( $body['data']['version'] ) ) {
				$out['theme']['latest'] = $body['data']['version'];
			}
		}
	}

	echo json_encode( $out );
	exit( 0 );
}

if ( 'link' === $action ) {
	$slug = $args[1] ?? '';
	if ( '' === $slug ) {
		fwrite( STDERR, "link action needs a slug.\n" );
		exit( 1 );
	}

	if ( $slug === $theme_slug ) {
		$url = Thim_Product_Registration::get_url_download_theme();
	} else {
		$url = Thim_Plugins_Manager::get_link_download_plugin( $slug );
	}

	if ( is_wp_error( $url ) || empty( $url ) ) {
		fwrite( STDERR, "no download url for {$slug}" . ( is_wp_error( $url ) ? ': ' . $url->get_error_message() : '' ) . "\n" );
		exit( 1 );
	}

	echo $url;
	exit( 0 );
}

fwrite( STDERR, "unknown action {$action}\n" );
exit( 1 );
