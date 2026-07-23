<?php
/**
 * ThimPress fetcher for the upsun-wp vendoring engine (upsun-wp >= 0.5).
 *
 * Ported verbatim in behaviour from the retired keds/scripts/thim-update-helper.php:
 * it drives thim-core's own update classes so every request carries the
 * site's activated license, exactly as the wp-admin dashboard would make
 * it. The engine (Upsun\Vendor) owns download/extract/re-vendor; this class
 * answers only "given a slug, is there a newer ThimPress build, and where is
 * its authenticated download?".
 *
 * Load-bearing contract (see Upsun\Fetcher): credentials are read from the
 * site's own stored state — the purchase token in the database, via
 * Thim_Product_Registration — never from an environment variable. Because
 * the token is already in the (cloned) database and nothing is injected from
 * outside, the "tokens never leave the container" guarantee holds for free.
 * This fetcher therefore only resolves on an environment whose database
 * carries the ThimPress activation (i.e. production).
 *
 * @package KEDS
 */

defined( 'ABSPATH' ) || exit;

final class Keds_ThimPress_Fetcher implements \Upsun\Fetcher {

	/**
	 * Memoized slug => latest-version map for ThimPress "external" plugins,
	 * fetched once per CLI process (each `wp upsun vendor` run is its own
	 * process, so this caches within a single resolve, not across them).
	 *
	 * @var array<string,string>|null
	 */
	private $catalog = null;

	public function id(): string {
		return 'thimpress';
	}

	/**
	 * Gate narrowly: only claim packages ThimPress actually distributes —
	 * the active theme, or a slug present in thim-core's external-plugin
	 * catalog. Everything else (Fluent, PMPro, ...) falls through to the
	 * built-in TransientFetcher.
	 */
	public function supports( string $slug, string $type ): bool {
		if ( ! $this->thim_available() ) {
			return false;
		}

		if ( 'theme' === $type && '' !== $slug && $slug === $this->theme_slug() ) {
			return true;
		}

		return array_key_exists( $slug, $this->catalog() );
	}

	/**
	 * The available ThimPress update for the package, or null when none is
	 * pending / not resolvable. The engine compares against the installed
	 * version and only acts on a strictly newer one, so returning the
	 * catalog's latest is sufficient.
	 *
	 * @return array{version:string,url:string,headers:array<string,string>}|null
	 */
	public function available_update( string $slug, string $type ): ?array {
		if ( ! $this->thim_available() ) {
			return null;
		}

		// Theme: latest version from the market /license/version endpoint,
		// download URL from thim-core's registration record.
		if ( 'theme' === $type && '' !== $slug && $slug === $this->theme_slug() ) {
			$version = $this->theme_latest_version( $slug );
			if ( '' === $version ) {
				return null;
			}

			return $this->result( $version, \Thim_Product_Registration::get_url_download_theme() );
		}

		// Plugin: latest version from the external-plugin catalog, download
		// URL from thim-core's plugin manager.
		$catalog = $this->catalog();
		if ( empty( $catalog[ $slug ] ) ) {
			return null;
		}

		return $this->result( $catalog[ $slug ], \Thim_Plugins_Manager::get_link_download_plugin( $slug ) );
	}

	/**
	 * Shape a {version,url,headers} result, rejecting an unusable URL
	 * (WP_Error or empty) the same way the engine's download step would.
	 *
	 * @param string          $version Resolved new version.
	 * @param string|\WP_Error $url     Authenticated download URL.
	 * @return array{version:string,url:string,headers:array<string,string>}|null
	 */
	private function result( string $version, $url ): ?array {
		if ( is_wp_error( $url ) || empty( $url ) ) {
			return null;
		}

		// The token is carried in the URL that thim-core builds; no extra
		// auth headers are needed (matches the retired helper's `link`).
		return array(
			'version' => $version,
			'url'     => (string) $url,
			'headers' => array(),
		);
	}

	/**
	 * Whether thim-core is loaded with the classes this fetcher drives.
	 */
	private function thim_available(): bool {
		return class_exists( '\Thim_Remote_Helper' )
			&& class_exists( '\Thim_Admin_Config' )
			&& class_exists( '\Thim_Plugins_Manager' )
			&& class_exists( '\Thim_Product_Registration' );
	}

	/**
	 * The active theme's template (stylesheet) slug.
	 */
	private function theme_slug(): string {
		return function_exists( 'wp_get_theme' ) ? (string) wp_get_theme()->get_template() : '';
	}

	/**
	 * ThimPress external-plugin catalog as slug => latest-version, fetched
	 * through thim-core's remote helper (same request the dashboard makes).
	 *
	 * @return array<string,string>
	 */
	private function catalog(): array {
		if ( null !== $this->catalog ) {
			return $this->catalog;
		}

		$this->catalog = array();

		$slugs = array();
		foreach ( \Thim_Plugins_Manager::get_external_plugins() as $plugin ) {
			$slugs[] = $plugin->get_slug();
		}

		if ( array() === $slugs ) {
			return $this->catalog;
		}

		$catalog = \Thim_Remote_Helper::post(
			\Thim_Admin_Config::get( 'api_update_plugins' ),
			array( 'body' => array( 'plugins' => $slugs, 'action' => 'plugin_information' ) ),
			true
		);

		if ( ! is_wp_error( $catalog ) && is_array( $catalog ) ) {
			foreach ( $catalog as $item ) {
				if ( isset( $item->slug, $item->version ) ) {
					$this->catalog[ (string) $item->slug ] = (string) $item->version;
				}
			}
		}

		return $this->catalog;
	}

	/**
	 * Latest theme version from the market: GET /license/version?site_code&slug
	 * (mirrors Thim_Theme_Envato_Check_Update::fetch_remote_version). The
	 * site_code is the purchase token from the DB. Empty string when it can't
	 * be resolved.
	 */
	private function theme_latest_version( string $theme_slug ): string {
		$site_code = \Thim_Product_Registration::get_data_theme_register( 'purchase_token' );
		if ( ! $site_code ) {
			return '';
		}

		$url = add_query_arg(
			array(
				'site_code' => $site_code,
				'slug'      => $theme_slug,
			),
			\Thim_Admin_Config::get( 'api_thim_market' ) . '/license/version'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ( $body['status'] ?? '' ) !== 'success' || empty( $body['data']['version'] ) ) {
			return '';
		}

		return (string) $body['data']['version'];
	}
}
