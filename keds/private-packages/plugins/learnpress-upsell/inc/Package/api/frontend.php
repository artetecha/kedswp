<?php
namespace LearnPress\Upsell\Package\API;

use LearnPress\Helpers\Template;
use LearnPress\Upsell\Package\Package;
use LearnPress\Upsell\Package\Template_Functions;
use LearnPress\Upsell\TemplateHooks\ArchivePackage;
use LP_REST_Response;
use Throwable;

class FrontEnd {

	protected static $instance = null;

	const NAMESPACE = 'learnpress-package/v1/frontend';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_api' ) );
	}

	public function register_rest_api() {
		register_rest_route(
			self::NAMESPACE,
			'/add-to-cart',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_to_cart' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/package-load-more-course',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'load_more_package_course' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function load_more_package_course( $request ) {
		$course_id = $request->get_param( 'course_id' );
		$page      = $request->get_param( 'page' );

		$template_function = new Template_Functions();

		$query = $template_function->query_package_tab_in_course( $course_id, $page );

		if ( empty( $query ) ) {
			return rest_ensure_response(
				array(
					'html'       => '',
					'total_page' => 0,
				)
			);
		}

		ob_start();
		if ( ! empty( $query['packages'] ) ) {
			foreach ( $query['packages'] as $package ) {
				echo ArchivePackage::instance()->render_package( $package );
				//do_action('lp/upsell/layout/course-tab/item-package', $package );
			}
		}
		$html = ob_get_clean();

		return rest_ensure_response(
			array(
				'html'       => $html,
				'total_page' => $query['total_page'],
			)
		);
	}

	/**
	 * Add package to cart.
	 *
	 * @param $request
	 *
	 * @return LP_REST_Response
	 * @since 4.0.0
	 * @version 1.0.1
	 */
	public function add_to_cart( $request ): LP_REST_Response {
		$response   = new LP_REST_Response();
		$package_id = $request->get_param( 'package_id' );

		try {
			$package_id = absint( $package_id );

			if ( empty( $package_id ) ) {
				throw new \Exception( 'Package ID is invalid!' );
			}

			$package = new Package( $package_id );
			if ( ! $package->exists() ) {
				throw new \Exception( 'Package not found' );
			}

			$cart = \LearnPress::instance()->cart;

			if ( ! learn_press_enable_cart() ) {
				$cart->empty_cart();
			}

			$item_data = array(
				'data' => get_post( $package_id ),
			);

			$cart_id = $cart->add_to_cart( $package_id, 1, $item_data );
			if ( empty( $cart_id ) ) {
				throw new \Exception( 'Cart is empty.' );
			}

			$redirect = learn_press_get_page_link( 'checkout' );
			if ( empty( $redirect ) ) {
				throw new \Exception( 'Please setup LearnPress Checkout page' );
			}

			/*ob_start();
			Template::print_message(
				esc_html__( 'Add package to cart success. Redirecting...', 'learnpress-upsell' )
			);
			$message_success          = ob_get_clean();*/
			$response->status         = 'success';
			$response->message        = esc_html__( 'Add package to cart success. Redirecting...', 'learnpress-upsell' );
			$response->data->redirect = $redirect;
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

FrontEnd::instance();
