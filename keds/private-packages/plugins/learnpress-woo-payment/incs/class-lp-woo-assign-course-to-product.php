<?php
/**
 * Class LP_Woo_Assign_Course_To_Product
 *
 * @version 1.0.0
 * @author  minhpd
 * @since 4.0.2
 */

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\WooPayment\LPWooOrderHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LP_Woo_Assign_Course_To_Product {
	/**
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * @var string
	 */
	public static $meta_key_lp_woo_courses_assigned = '_lp_woo_courses_assigned';

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct( $product = 0 ) {
		// add tab
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'courses_data_tabs' ), 11, 1 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'courses_product_panels' ) );
		add_action( 'admin_head', array( $this, 'wcpp_custom_style' ) );

		// save_meta_box
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_courses_data' ), 10, 1 );
		add_filter( 'woocommerce_product_tabs', array( $this, 'view_courses_by_product' ), 100, 1 );

		// Show message on archive courses
		add_filter( 'lp/template/archive-course/description', array( $this, 'archive_courses' ), 99 );

		// Hook show button purchase @deprecated 4.1.5
		/*add_filter(
			'learnpress/course/template/button-purchase/can-show',
			array(
				$this,
				'can_show_button_purchase',
			),
			10,
			3
		);*/
		add_filter(
			'learn-press/single-course/offline/section-right/info-meta/buttons',
			[ $this, 'show_message_single_course_offline' ],
			10,
			3
		);
		// Hook show button enroll
		//add_filter( 'learnpress/course/template/button-enroll/can-show', array( $this, 'can_show_button_add_to_cart' ), 10, 3 );
		// Hook show price course
		add_filter( 'learn_press_course_price_html_free', array( $this, 'hide_show_price_course' ), 10, 3 );
		add_filter( 'learn_press_course_price_html', array( $this, 'hide_show_price_course' ), 10 );
		// Set quantity
		add_filter( 'woocommerce_add_to_cart_quantity', array( $this, 'set_quantity' ), 10, 2 );
		// Create LP Order when WC Order created manual completed
		//add_action( 'woocommerce_process_shop_order_meta', array( $this, 'create_lp_order_by_woo_order_manual' ), 55, 2 );
		// add notice when use purchase course via product
		//add_action( 'learn-press/course-summary-sidebar', array( $this, 'notice_purchase_course_via_product' ), 15 );
	}

	/**
	 * Create lp_order when create by woo order manual
	 *
	 * @author minhpd
	 * @version 1.0.0
	 * @since 4.0.3
	 */
	public function create_lp_order_by_woo_order_manual( $post_id, $post ) {
		if ( ! $post_id ) {
			return;
		}

		$lp_woo_order = new LPWooOrderHandler( 0, $post_id );
		$lp_woo_order->create_lp_order();
	}

	/**
	 * Hook set not show button purchase when enable payment via product
	 *
	 * @param bool $can_show
	 * @param LP_User|UserModel $user
	 * @param LP_Course|CourseModel $course
	 *
	 * @return bool
	 * @deprecated 4.1.5
	 */
	/*public function can_show_button_purchase( bool $can_show, $user, $course ): bool {
		if ( $can_show ) {
			if ( $course instanceof LP_Course ) {
				$this->notice_purchase_course_via_product();
			}
		}

		return apply_filters( 'lp-woo/button-purchase/can-show', false );
	}*/

	/**
	 * Show message on single course offline
	 *
	 * @param array $buttons
	 * @param CourseModel $course
	 * @param UserModel $user
	 *
	 * @return array
	 * @since 4.1.5
	 * @version 1.0.0
	 */
	public function show_message_single_course_offline( array $buttons, $course, $user ) {
		ob_start();
		$this->notice_purchase_course_via_product();
		$html               = ob_get_clean();
		$buttons['btn_buy'] = $html;

		return $buttons;
	}

	/**
	 * Hook set not show button add to cart when enable payment via product
	 *
	 * @param bool $can_show
	 * @param LP_User $user
	 * @param LP_Course $course
	 *
	 * @return bool
	 * @throws Exception
	 * @author minpd
	 * @version 4.0.2
	 */
	/*public function can_show_button_add_to_cart( bool $can_show, LP_User $user, LP_Course $course ): bool {
		$can_show = $user->has_purchased_course( $course->get_id() );
		return apply_filters( 'lp-woo/button-add-to-cart/can-show', $can_show );
	}*/

	/**
	 * Hide price course
	 */
	public function hide_show_price_course( $price ) {
		if ( ! is_admin() ) {
			$price_new = '';

			return apply_filters( 'lp-woo/courses/price/can-show', $price_new, $price );
		}

		return $price;
	}

	/**
	 * Show message on archive courses
	 *
	 * @author minpd
	 * @version 4.0.2
	 */
	public function archive_courses() {
		$shop_page_url   = get_permalink( wc_get_page_id( 'shop' ) );
		$shop_page_title = get_the_title( wc_get_page_id( 'shop' ) );

		$html = sprintf(
			'<p class="course-archive-message-by-via-product">%s %s %s</p>',
			__( 'If you want to buy courses, please go to the', 'learnpress-woo-payment' ),
			'<a href="' . esc_attr( $shop_page_url ) . '"><i>' . esc_html( $shop_page_title ) . '</i></a>',
			__( 'page to buy products assigned courses!', 'learnpress-woo-payment' )
		);

		echo $html;
	}

	/**
	 * Add course data tabs product
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function courses_data_tabs( array $tabs ): array {
		$tabs['course_data'] = array(
			'label'  => __( 'Courses', 'learnpress-woo-payment' ),
			'target' => 'course_product_data',
		);

		return $tabs;
	}

	public function metabox() {
		$data_struct_course = [
			'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/search-course' ),
			'dataType'    => 'courses',
			'keyGetValue' => [
				'value'      => 'ID',
				'text'       => '{{post_title}} (#{{ID}})',
				'key_render' => [
					'post_title' => 'post_title',
					'ID'         => 'ID',
				],
			],
			'setting'     => [
				'placeholder' => esc_html__( 'Choose Course', 'learnpress' ),
			],
		];

		return array(
			self::$meta_key_lp_woo_courses_assigned => new LP_Meta_Box_Select_Field(
				__( 'Assign courses to this product', 'learnpress-woo-payment' ),
				'',
				[],
				[
					'options'           => array(),
					'style'             => 'min-width:200px;',
					'tom_select'        => true,
					'multiple'          => true,
					'custom_attributes' => [ 'data-struct' => htmlentities2( json_encode( $data_struct_course ) ) ],
				]
			),
		);
	}

	/**
	 * Add content tabs courses product
	 */
	public function courses_product_panels() {
		global $post;

		echo '<div id="course_product_data" class="panel woocommerce_options_panel hidden">';

		foreach ( $this->metabox() as $key => $object ) {
			$object->id = $key;
			$object->output( $post->ID );
		}

		echo '</div>';
	}

	/**
	 * Save courses assign
	 */
	public function save_courses_data( $post_id ) {
		if ( ! isset( $_POST[ self::$meta_key_lp_woo_courses_assigned ] ) ) {
			return;
		}

		$courses_data = LP_Request::get_param( self::$meta_key_lp_woo_courses_assigned, [] );

		update_post_meta( $post_id, self::$meta_key_lp_woo_courses_assigned, $courses_data, false );
	}

	/**
	 * CSS To Add Custom tab Icon
	 */
	public function wcpp_custom_style() {
		$screen = get_current_screen();
		if ( ! $screen || $screen->id != 'product' ) {
			return;
		}
		?>
		<style>
			#woocommerce-product-data ul.wc-tabs li.course_data_options a:before {
				font-family: WooCommerce;
				content: '\e006';
			}
		</style>
		<?php
	}

	/**
	 * Add tabs show list courses by product
	 */
	public function view_courses_by_product( $tabs ) {
		global $post;

		$courses = self::get_product_course_ids( $post->ID );

		if ( ! empty( $courses ) ) {
			$tabs['_courses_data'] = array(
				'title'    => __( 'Courses', 'learnpress-woo-payment' ),
				'priority' => 100,
				'callback' => array( $this, 'content_tabs_courses' ),
			);
		}

		return $tabs;
	}

	/**
	 * Show list courses on tab of single Product
	 *
	 * @return void
	 */
	public function content_tabs_courses() {
		global $post;
		wp_enqueue_style( 'lp-woo-css' );
		$user = UserModel::find( get_current_user_id(), true );

		$data                     = self::get_list_courses_assign_to_product( $post->ID, $user );
		$courses_assign_product   = $data['courses_assign_product'];
		$all_courses_out_of_stock = $data['all_courses_out_of_stock'];

		LP_Addon_Woo_Payment_Preload::$addon->get_template(
			'list-courses-assign-product',
			compact( 'courses_assign_product', 'all_courses_out_of_stock' )
		);
	}

	/**
	 * Get list courses assign to product
	 *
	 * @param int $product_id
	 * @param UserModel|false $user
	 *
	 * @return array
	 */
	public static function get_list_courses_assign_to_product( int $product_id, $user ): array {
		$courses_assign_product   = [];
		$all_courses_out_of_stock = false;

		try {
			$course_ids = self::get_product_course_ids( $product_id );
			if ( ! is_array( $course_ids ) || empty( $course_ids ) ) {
				return [];
			}

			$i = $j = 1;
			foreach ( $course_ids as $course_id ) {
				$course = CourseModel::find( $course_id, true );
				if ( empty( $course ) ) {
					continue;
				}

				++$i;
				//$can_purchase = $user->can_purchase_course( $course_id );
				$can_purchase = $course->can_purchase( $user );
				if ( is_wp_error( $can_purchase ) ) {
					if ( $can_purchase->get_error_code() === 'course_out_of_stock' ) {
						$course->meta_data->is_out_stock = $can_purchase->get_error_message();
						++$j;
					}
				}

				$courses_assign_product[] = $course;
			}

			if ( $i === $j ) {
				$all_courses_out_of_stock = true;
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ' ' . $e->getMessage() );
		}

		return compact( 'courses_assign_product', 'all_courses_out_of_stock' );
	}

	/**
	 * Product has courses only add one time to cart
	 *
	 * @param int $quantity
	 * @param int $product_id
	 *
	 * @return int
	 * @since 4.0.2
	 * @author tungnx
	 */
	public function set_quantity( int $quantity, int $product_id ): int {
		$product_has_courses      = self::get_product_course_ids( $product_id );
		$user                     = UserModel::find( get_current_user_id(), true );
		$data_course              = self::get_list_courses_assign_to_product( $product_id, $user );
		$all_courses_out_of_stock = $data_course['all_courses_out_of_stock'];

		if ( ! empty( $product_has_courses ) ) {
			$message  = __( 'Product which has courses is only added one time.', 'learnpress-woo-payment' );
			$cart     = WC()->cart;
			if ( ! $cart ) {
				return $quantity;
			}

			$cart_key = $cart->generate_cart_id( $product_id );
			if ( $all_courses_out_of_stock ) {
				$message  = __( 'All courses are full students, so this product is out of stock.', 'learnpress-woo-payment' );
				$quantity = 0;
				wc_add_notice( $message, 'error' );

				//remove product in cart
				self::remove_product_cart_by_id( $product_id );
			} elseif ( array_key_exists( $cart_key, $cart->cart_contents ) ) {
				$quantity = 0;
				$message .= sprintf( ' <a href="%s" class="button wc-forward">%s</a>', wc_get_cart_url(), __( 'View Cart', 'learnpress-woo-payment' ) );
				wc_add_notice( $message, 'error' );
			} elseif ( $quantity > 1 ) {
				$message .= sprintf( ' <a href="%s" class="button wc-forward">%s</a>', wc_get_cart_url(), __( 'View Cart', 'learnpress-woo-payment' ) );
				wc_add_notice( $message, 'error' );

				$quantity = 1;
			}
		}

		return $quantity;
	}

	/**
	 * Show notice on course.
	 * 1. If course is not assigned to any product.
	 * 2. Show list products assigned to course.
	 *
	 * @return void
	 */
	public function notice_purchase_course_via_product() {
		global $post;
		if ( empty( $post ) ) {
			return;
		}

		try {
			// Check only hooks in LP_WC_Hooks::button_add_to_cart can call, theme call this function will return
			$debug_backtrack = debug_backtrace();
			if ( ! isset( $debug_backtrack[1] )
				|| ! isset( $debug_backtrack[1]['class'] )
				|| ! isset( $debug_backtrack[1]['function'] )
				|| $debug_backtrack[1]['class'] != 'LP_WC_Hooks'
				|| $debug_backtrack[1]['function'] != 'button_add_to_cart'
			) {
				return;
			}

			$products = $this->get_products_assigned_course( $post->ID );

			LP_Addon_Woo_Payment_Preload::$addon->get_template( 'notice', compact( 'products' ) );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ' ' . $e->getMessage() );
		}
	}

	/**
	 * Get products assigned course
	 *
	 * @param $course_id
	 *
	 * @return array|int|string|null
	 * @throws Exception
	 */
	public function get_products_assigned_course( $course_id ) {
		$lp_post_db              = LP_Post_DB::getInstance();
		$filter                  = new LP_Post_Type_Filter();
		$filter->post_type       = 'product';
		$filter->only_fields     = [ 'ID' ];
		$filter->join[]          = 'INNER JOIN ' . $lp_post_db->wpdb->postmeta . ' AS pm ON pm.post_id = p.ID';
		$filter->where[]         = 'AND pm.meta_key = "' . LP_Woo_Assign_Course_To_Product::$meta_key_lp_woo_courses_assigned . '" AND pm.meta_value LIKE ' . "'%" . '"' . $course_id . '"' . "%'";
		$filter->run_query_count = false;
		$filter                  = apply_filters( 'lp/woo-payment/notice-sidebar/get-products', $filter );
		$products                = $lp_post_db->get_posts( $filter );

		return $products;
	}

	/**
	 * Get products assigned course
	 *
	 * @param $course_id
	 *
	 * @return int
	 * @throws Exception
	 * @version 1.0.0
	 * @size 4.1.6
	 */
	public static function check_course_is_assigned_to_product( $course_id ): int {
		$lp_post_db          = LP_Post_DB::getInstance();
		$filter              = new LP_Post_Type_Filter();
		$filter->post_type   = 'product';
		$filter->only_fields = [ 'ID' ];
		$filter->join[]      = 'INNER JOIN ' . $lp_post_db->wpdb->postmeta . ' AS pm ON pm.post_id = p.ID';
		$filter->where[]     = 'AND pm.meta_key = "' . LP_Woo_Assign_Course_To_Product::$meta_key_lp_woo_courses_assigned . '" AND pm.meta_value LIKE ' . "'%" . '"' . $course_id . '"' . "%'";
		$filter->query_count = true;
		$count               = $lp_post_db->get_posts( $filter );

		return (int) $count;
	}

	/**
	 * Remove product in cart
	 *
	 * @param $product_id
	 *
	 * @return void
	 */
	public static function remove_product_cart_by_id( $product_id ) {
		if ( empty( $product_id ) ) {
			return;
		}

		$cart     = WC()->cart;
		$cart_key = $cart->generate_cart_id( $product_id );
		if ( $cart_key ) {
			$cart_item_key = $cart->find_product_in_cart( $cart_key );
			if ( $cart_item_key ) {
				$cart->remove_cart_item( $cart_item_key );
			}
		}
	}

	public static function get_product_course_ids( $product_id ) {
		$course_ids = get_post_meta( $product_id, self::$meta_key_lp_woo_courses_assigned, true );

		if ( empty( $course_ids ) || ! is_array( $course_ids ) ) {
			return array();
		}

		$course_ids = array_filter( $course_ids, function( $id ) {
		    return ! empty( $id ) && get_post_type( intval( $id ) ) === 'lp_course';
		});

		return $course_ids;
	}
}

LP_Woo_Assign_Course_To_Product::instance();
