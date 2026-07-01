<?php
namespace LearnPress\Upsell\Package;

use LearnPress\Models\CourseModel;
use LearnPress\Upsell\Package\Addon\Certificate;

class InitPackage {

	protected static $instance = null;

	public function __construct() {
		$this->includes();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'save_post', array( $this, 'save_post_course' ), 1000, 3 );
	}

	public function includes() {
		require_once LP_ADDON_UPSELL_PACKAGE_PATH . 'post-type.php';
		require_once LP_ADDON_UPSELL_PACKAGE_PATH . 'package.php';
		require_once LP_ADDON_UPSELL_PACKAGE_PATH . 'api/admin.php';
		require_once LP_ADDON_UPSELL_PACKAGE_PATH . 'api/frontend.php';
		require_once LP_ADDON_UPSELL_PACKAGE_PATH . 'core-functions.php';
		require_once LP_ADDON_UPSELL_PACKAGE_PATH . 'template-loader.php';
		require_once LP_ADDON_UPSELL_PACKAGE_PATH . 'template-functions.php';
		require_once LP_ADDON_UPSELL_PACKAGE_PATH . 'template-hooks.php';
		require_once LP_ADDON_UPSELL_PACKAGE_PATH . 'checkout.php';

		// Addon.
		//require_once LP_ADDON_UPSELL_PACKAGE_PATH . 'addon/certificate.php';
		Certificate::instance();
	}

	public function enqueue_scripts() {
		// Check is archive or single package or single course
		$info = include LP_ADDON_UPSELL_PATH . '/build/frontend.asset.php';
		wp_register_style( 'learnpress-package', LP_ADDON_UPSELL_URL . 'build/frontend.css', array(), $info['version'] );
		wp_register_script(
			'learnpress-package',
			LP_ADDON_UPSELL_URL .
			'build/frontend.js',
			$info['dependencies'],
			$info['version'],
			[ 'strategy' => 'defer' ]
		);

		if ( is_singular( LP_PACKAGE_CPT ) || is_post_type_archive( LP_PACKAGE_CPT )
			|| is_singular( LP_COURSE_CPT ) || Core_Functions::instance()->is_package_taxonomy() ) {
			wp_enqueue_style( 'learnpress' );
			wp_enqueue_style( 'learnpress-package' );
			wp_enqueue_script( 'learnpress-package' );
			wp_enqueue_style(
				'toastify',
				LP_ADDON_UPSELL_URL . 'public/toastify.min.css'
			);
		}
	}

	// Set price for packages when change course price.
	public function save_post_course( $post_id, $post, $update ) {
		if ( LP_COURSE_CPT !== $post->post_type || ! $update ) {
			return;
		}

		$package_ids = Core_Functions::instance()->get_packages_by_course_id( $post_id, 0, 0 );

		if ( empty( $package_ids ) ) {
			return;
		}

		foreach ( $package_ids as $package_id ) {
			$price_type = get_post_meta( $package_id, '_lp_package_new_price_type', true );

			if ( 'fixed' === $price_type ) {
				continue;
			}

			$price = $this->calculate_course_price( $package_id, $post_id );

			update_post_meta( $package_id, '_lp_package_price', $price );

			$price_amount = get_post_meta( $package_id, '_lp_package_new_price_amount', true );

			$sale_price = $price - ( $price * $price_amount / 100 );

			// 2 decimals.
			$sale_price = round( $sale_price, 2 );

			update_post_meta( $package_id, '_lp_package_sale_price', $sale_price );
		}
	}

	private function calculate_course_price( $package_id, $post_id ) {
		$package = new Package( $package_id );

		$course_ids = $package->get_course_list();

		$price = 0;

		if ( empty( $course_ids ) ) {
			return $price;
		}

		foreach ( $course_ids as $course_id ) {
			$course = CourseModel::find( $course_id, true );
			if ( ! $course ) {
				continue;
			}

			$price += $course->get_price();
		}

		return $price;
	}

	// instance
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
