<?php

/**
 * Class LP_Cert_AJAX
 *
 * Handle ajax for certificates
 *
 * @since 3.1.4
 */
class LP_Cert_AJAX {
	protected static $_instance;
	protected $_hook_arr = array( 'lpCertCreateImage', 'lp_cert_add_to_cart_woo', 'lp_cert_save_draft' );

	protected function __construct() {
		foreach ( $this->_hook_arr as $hook ) {
			add_action( 'wp_ajax_' . $hook, array( $this, $hook ) );
			add_action( 'wp_ajax_nopriv_' . $hook, array( $this, $hook ) );
		}
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/*
	public function lpCertCreateImage() {
		$data = array(
			'code'    => 0,
			'message' => '',
		);

		try {
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$uploads  = wp_upload_dir();
			$cert_dir = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'learn-press-cert';

			$img_cert_name = LP_Request::get_param( 'name_image', '', 'text', 'post' );
			$file_img_cer  = $cert_dir . DIRECTORY_SEPARATOR . $img_cert_name . '.png';

			fopen( $file_img_cer, 'w' );

			$data64 = str_replace( 'data:image/png;base64,', '', LP_Request::get_param( 'data64', '', 'text', 'post' ) );
			$data64 = base64_decode( $data64 );

			$wp_filesystem->put_contents( $file_img_cer, $data64, FS_CHMOD_FILE );

			$data['url_cert'] = $uploads['baseurl'] . '/learn-press-cert/' . $img_cert_name . '.png';
			$data['code']     = 1;
			$data['message']  = 'create image cert success';
		} catch ( Exception $e ) {
			$data['message'] = $e->getMessage();
		}

		wp_send_json( $data );
	}
	*/
	public function lpCertCreateImage() {
		$data = array(
			'code'    => 0,
			'message' => '',
		);

		try {
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				if ( ! WP_Filesystem() ) {
					throw new Exception( 'Cannot initialize filesystem' );
				}
			}

			$uploads  = wp_upload_dir();
			$cert_dir = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'learn-press-cert';

			if ( ! file_exists( $cert_dir ) && ! wp_mkdir_p( $cert_dir ) ) {
				throw new Exception( 'Cannot create certificate directory' );
			}

			$img_cert_name = LP_Request::get_param( 'name_image', '', 'text', 'post' );
			if ( empty( $img_cert_name ) ) {
				throw new Exception( 'name_image is required' );
			}

			$file_img_cer = $cert_dir . DIRECTORY_SEPARATOR . $img_cert_name . '.png';

			$data64 = LP_Request::get_param( 'data64', '', 'text', 'post' );
			if ( empty( $data64 ) ) {
				throw new Exception( 'data64 is required' );
			}

			$data64 = str_replace( 'data:image/png;base64,', '', $data64 );
			$data64 = base64_decode( $data64, true );
			if ( false === $data64 || '' === $data64 ) {
				throw new Exception( 'Certificate image data is invalid' );
			}

			if ( ! $wp_filesystem->put_contents( $file_img_cer, $data64, FS_CHMOD_FILE ) ) {
				throw new Exception( 'Cannot write certificate image' );
			}

			$data['url_cert'] = home_url( 'certificate/image/' . $img_cert_name );
			$data['code']     = 1;
			$data['message']  = 'create image cert success';
		} catch ( Exception $e ) {
			$data['message'] = $e->getMessage();
		}

		wp_send_json( $data );
	}

	/**
	 * Add course id as product id to cart | If add cert id as product will trouble create lp order for certificate
	 * Store lp_cert_id key to cart item
	 */
	public function lp_cert_add_to_cart_woo() {
		$result = array(
			'code'    => 0,
			'message' => __( 'error', 'learnpress-certificates' ),
		);

		if ( ! isset( $_POST['lp_course_id_of_cert'] ) || ! isset( $_POST['lp_cert_id'] ) ) {
			$result['message'] = __( 'Params invalid', 'learnpress-certificates' );

			wp_send_json( $result );
		}

		$course_id = LP_Request::get_param( 'lp_course_id_of_cert', 0, 'int', 'post' );
		$cert_id   = LP_Request::get_param( 'lp_cert_id', 0, 'int', 'post' );

		if ( ! isset( $_POST['purchase-certificate-nonce'] ) || ! wp_verify_nonce( $_POST['purchase-certificate-nonce'], 'purchase-cert-' . $cert_id ) ) {
			$result['message'] = 'params invalid';

			wp_send_json( $result );
		}

		$wc_cart       = WC()->cart;
		$cart_item_key = $wc_cart->add_to_cart( $cert_id );

		if ( $cart_item_key ) {
			$wc_cart->cart_contents[ $cart_item_key ]['course_id']  = $course_id;
			$wc_cart->cart_contents[ $cart_item_key ]['lp_cert_id'] = $cert_id;

			$wc_cart->set_session();

			$result['code']             = 1;
			$result['message']          = $cart_item_key;
			$result['button_view_cart'] = '<a class="btn-lp-cert-view-cart" target="_blank" href="' . wc_get_cart_url() . '"><button class="lp-button">' . __( 'View cart certificate', 'learnpress-certificates' ) . '</button></a>';

			if ( 'yes' == LearnPress::instance()->settings()->get( 'woo-payment_redirect_to_checkout' ) ) {
				$result['redirect_to'] = wc_get_checkout_url();
			}
		} else {
			$wc_notices = wc_get_notices();

			if ( ! empty( $wc_notices['error'] ) ) {
				$result['message'] = esc_html__( 'You cannot add this certificate to your cart. Maybe certificate exists', 'learnpress-certificates' );
			}
		}

		wp_send_json( $result );
	}

	public function lp_cert_save_draft() {
		$result = array(
			'success' => false,
			'data'    => array(
				'message' => __( 'Error saving certificate', 'learnpress-certificates' ),
			),
		);

		$nonce = LP_Request::get_param( 'nonce', '', 'text', 'post' );
		if ( ! wp_verify_nonce( $nonce, 'lp-cert-nonce' ) ) {
			$result['data']['message'] = __( 'Security check failed', 'learnpress-certificates' );
			wp_send_json( $result );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			$result['data']['message'] = __( 'You do not have permission to do this', 'learnpress-certificates' );
			wp_send_json( $result );
		}

		$cert_id       = LP_Request::get_param( 'cert_id', 0, 'int', 'post' );
		$title         = LP_Request::get_param( 'title', 'Certificate', 'text', 'post' );
		$template_type = LP_Request::get_param( 'template_type', 'blank', 'text', 'post' );

		if ( ! $cert_id ) {
			$result['data']['message'] = __( 'Invalid certificate ID', 'learnpress-certificates' );
			wp_send_json( $result );
		}

		$post = get_post( $cert_id );

		if ( ! $post || $post->post_type !== 'lp_cert' ) {
			$result['data']['message'] = __( 'Certificate not found', 'learnpress-certificates' );
			wp_send_json( $result );
		}

		if ( $post->post_status === 'auto-draft' ) {
			$update_result = wp_update_post( array(
				'ID'          => $cert_id,
				'post_status' => 'draft',
				'post_title'  => $title,
			), true );

			if ( is_wp_error( $update_result ) ) {
				$result['data']['message'] = $update_result->get_error_message();
				wp_send_json( $result );
			}
		}

		$result['success']         = true;
		$result['data']['message'] = __( 'Certificate saved as draft', 'learnpress-certificates' );
		$result['data']['cert_id'] = $cert_id;

		wp_send_json( $result );
	}
}

LP_Cert_AJAX::getInstance();
