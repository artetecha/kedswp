<?php

/**
 * Theme update checker.
 *
 * Used to be backed by Envato's market API with a hardcoded seller token.
 * Now it asks our own market server (`/license/version`) using the
 * site_code we got when the user activated the license, so update checks
 * no longer leave the consolidated site.
 *
 * The class name is kept for backwards compatibility with callers across
 * the dashboard — only the internals changed.
 *
 * @since 1.1.0
 */
class Thim_Theme_Envato_Check_Update {

	/**
	 * Activation `site_code` saved at license activation time.
	 *
	 * @var string
	 */
	private $site_code;

	/**
	 * Theme slug (Envato slug stored locally) — matches `_slug` on the
	 * server's plugin/theme post.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Local theme version.
	 *
	 * @var string
	 */
	private $local_version;

	/**
	 * Cached server-side data so we hit the network only once per request.
	 *
	 * @var array|false|null
	 */
	private $theme_data = null;

	public function __construct( $slug, $local_version, $site_code = '' ) {
		$this->slug          = (string) $slug;
		$this->local_version = (string) $local_version;
		$this->site_code     = (string) ( $site_code ?: Thim_Product_Registration::get_data_theme_register( 'purchase_token' ) );
	}

	public function can_update() {
		$remote_version = $this->get_remote_version();
		if ( ! $remote_version ) {
			return false;
		}

		return version_compare( $remote_version, $this->local_version, '>' );
	}

	public function get_remote_version() {
		$data = $this->get_theme_data();
		return $data['version'] ?? false;
	}

	/**
	 * Return a stable shape compatible with the dashboard panel that
	 * stores update info in `thim_core_check_update_themes`. Server only
	 * gives back the latest version; richer fields fall back to local
	 * WP_Theme data so the UI keeps working.
	 *
	 * @return array|false
	 */
	public function get_theme_data() {
		if ( null !== $this->theme_data ) {
			return $this->theme_data;
		}

		$version = $this->fetch_remote_version();

		if ( ! $version ) {
			$this->theme_data = false;
			return false;
		}

		// Try matching the slug to a local theme folder first; fall back to
		// the active theme so renamed folders or child themes still surface
		// the parent's metadata in the update panel.
		$wp_theme = null;
		if ( function_exists( 'wp_get_theme' ) ) {
			$wp_theme = wp_get_theme( $this->slug );
			if ( ! $wp_theme || is_wp_error( $wp_theme ) || ! $wp_theme->exists() ) {
				$wp_theme = wp_get_theme();
			}
		}
		$is_theme = $wp_theme && ! is_wp_error( $wp_theme ) && $wp_theme->exists();

		$this->theme_data = array(
			'theme_name'   => $is_theme ? $wp_theme->get( 'Name' ) : $this->slug,
			'description'  => $is_theme ? $wp_theme->get( 'Description' ) : '',
			'version'      => $version,
			'icon'         => '',
			'author_name'  => $is_theme ? $wp_theme->get( 'Author' ) : '',
			'author_url'   => $is_theme ? $wp_theme->get( 'AuthorURI' ) : '',
			'rating'       => 0,
			'rating_count' => 0,
			'url'          => $is_theme ? $wp_theme->get( 'ThemeURI' ) : '',
		);

		return $this->theme_data;
	}

	/**
	 * Hit `/license/version?site_code=…&slug=…` on the market server.
	 */
	private function fetch_remote_version() {
		if ( '' === $this->site_code || '' === $this->slug ) {
			return false;
		}

		$url = add_query_arg(
			array(
				'site_code' => $this->site_code,
				'slug'      => $this->slug,
			),
			Thim_Admin_Config::get( 'api_thim_market' ) . '/license/version'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body ) || ( $body['status'] ?? '' ) !== 'success' ) {
			return false;
		}

		$version = $body['data']['version'] ?? false;

		return $version ? (string) $version : false;
	}
}
