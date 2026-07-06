<?php

namespace LearnPress\Certificate\Upgrade;

use Exception;
use LearnPress\Certificate\Models\CertificatePostModel;
use LearnPress\Databases\PostDB;
use LearnPress\Filters\PostFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\UserModel;
use LP_Addon_Certificates_Preload;
use LP_Debug;
use LP_Step;
use Throwable;

/**
 * Class CertificateUpdater
 *
 * @version 1.0.0
 * @since 4.2.0
 */
class CertificateUpdater {
	use Singleton;

	const DB_VERSION       = 2;
	const DB_VERSION_KEY   = 'certificate_db_version';
	public $db_map_version = [];

	public function init() {
		try {
			// Check only run in Admin screen
			if ( ! is_admin() ) {
				return;
			}

			$this->db_map_version = apply_filters(
				'lp/certificate/upgrade/db/map_version',
				array(
					'1' => CertificateUpgrade2::class,
				)
			);

			$this->init_db_version();

			if ( $this->check_db_need_upgrade() ) {
				add_action( 'admin_notices', array( $this, 'show_upgrade_notice' ) );
				add_action( 'wp_ajax_lp_cert_upgrade_db', array( $this, 'ajax_upgrade_db' ) );
				add_action( 'admin_footer', array( $this, 'render_upgrade_modal' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_upgrade_assets' ) );
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}

	/**
	 * Initialize database version
	 *
	 * @throws Exception
	 */
	public function init_db_version() {
		$db_version_current = self::get_current_db_version();

		if ( ! $db_version_current ) {
			// Check has data certificates old
			$db                  = PostDB::getInstance();
			$filter              = new PostFilter();
			$filter->only_fields = [ 'ID' ];
			$filter->post_type   = LP_ADDON_CERTIFICATES_CERT_CPT;
			$filter->join[]      = $db->wpdb->prepare(
				"INNER JOIN {$db->wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s",
				CertificatePostModel::META_KEY_CER_LAYERS
			);
			$db->get_query_single_row( $filter );
			$query           = $db->get_posts( $filter );
			$data_old_exists = $db->wpdb->get_var( $query );

			// Case old data exists
			if ( $data_old_exists ) {
				$db_version = 1;
			} else { // Case new install or no data old
				$db_version = self::DB_VERSION;
			}

			update_option( self::DB_VERSION_KEY, $db_version, false );
		}
	}

	/**
	 * Check need upgrade database
	 *
	 * @return bool|string
	 */
	public function check_db_need_upgrade() {
		$db_current_version = self::get_current_db_version();
		$db_require_version = self::DB_VERSION;
		if ( version_compare( $db_require_version, $db_current_version, '<=' ) ) {
			return false;
		}

		if ( array_key_exists( $db_current_version, $this->db_map_version ) ) {
			return $this->db_map_version[ $db_current_version . '' ];
		}

		return false;
	}

	/**
	 * Get current database version
	 *
	 * @return int
	 */
	public static function get_current_db_version(): int {
		return (int) get_option( self::DB_VERSION_KEY, 0 );
	}

	/**
	 * Show upgrade notice
	 */
	public function show_upgrade_notice() {
		LP_Addon_Certificates_Preload::$addon->get_admin_template( 'upgrade/notice-upgrade.php' );
	}

	/**
	 * HTML upgrade modal
	 */
	public function render_upgrade_modal() {
		LP_Addon_Certificates_Preload::$addon->get_admin_template( 'upgrade/modal-upgrade.php' );
	}

	public function enqueue_upgrade_assets() {
		$addon = LP_Addon_Certificates_Preload::$addon;
		$min   = LP_Debug::is_debug() ? '' : '.min';
		$ver   = LP_Debug::is_debug() ? uniqid() : $addon->version;

		wp_enqueue_style(
			'cert-upgrade-css',
			$addon->get_plugin_url( "assets/dist/css/cert-upgrade{$min}.css" ),
			[],
			$ver
		);

		wp_enqueue_script(
			'cert-upgrade-js',
			$addon->get_plugin_url( "assets/dist/js/backend/cert-upgrade{$min}.js" ),
			[],
			$ver,
			true
		);

		wp_localize_script( 'cert-upgrade-js', 'lpCertUpgrade', [
			'nonce'   => wp_create_nonce( 'lp_cert_upgrade_db' ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'i18n'    => [
				'upgrading'  => __( 'Upgrading...', 'learnpress-certificates' ),
				'success'    => __( 'Success!', 'learnpress-certificates' ),
				'error'      => __( 'Error!', 'learnpress-certificates' ),
				'unexpected' => __( 'An unexpected error occurred.', 'learnpress-certificates' ),
			],
		] );
	}

	public function ajax_upgrade_db() {
		$response = new LP_Step( __FUNCTION__, 'Update data layer of certificate' );

		try {
			if ( ! wp_verify_nonce( $_POST['nonce'], 'lp_cert_upgrade_db' ) ) {
				throw new Exception( 'Invalid nonce.' );
			}

			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( 'You do not have permission to upgrade certificates.' );
			}

			$name_class_handle_upgrade = $this->check_db_need_upgrade();
			if ( ! class_exists( $name_class_handle_upgrade )
				|| ! is_callable( array( $name_class_handle_upgrade, 'instance' ) ) ) {
				throw new Exception( 'Upgrade class invalid!' );
			}

			$class_handle_upgrade = $name_class_handle_upgrade::instance();
			$current              = self::get_current_db_version();

			$target_version = $current + 1;

			$params = [];

			/**
			 * @var $class_handle_upgrade CertificateUpgradeBase
			 */
			$response = $class_handle_upgrade->handle( $params );

			$all_finish = $response->data->done ?? false;

			if ( $all_finish ) {
				update_option( self::DB_VERSION_KEY, $target_version );
			}

			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
