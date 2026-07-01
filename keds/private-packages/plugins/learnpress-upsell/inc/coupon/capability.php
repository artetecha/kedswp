<?php
namespace LearnPress\Upsell\Coupon;

class Capability {

	protected static $instance = null;

	public function __construct() {
		add_action( 'init', array( $this, 'create_roles' ) );
	}

	public function create_roles() {
		global $wp_roles;

		if ( ! class_exists( '\WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles();
		}

		$capabilities = $this->capabilities();

		foreach ( $capabilities as $capability ) {
			$wp_roles->add_cap( 'administrator', $capability );
		}
	}

	private function capabilities() {
		$capability_type = 'learnpress_coupon'; // Use in post type.

		return array(
			"edit_{$capability_type}",
			"read_{$capability_type}",
			"delete_{$capability_type}",
			"edit_{$capability_type}s",
			"edit_others_{$capability_type}s",
			"publish_{$capability_type}s",
			"read_private_{$capability_type}s",
			"delete_{$capability_type}s",
			"delete_private_{$capability_type}s",
			"delete_published_{$capability_type}s",
			"delete_others_{$capability_type}s",
			"edit_private_{$capability_type}s",
			"edit_published_{$capability_type}s",
		);
	}

	// Instance.
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
Capability::instance();
