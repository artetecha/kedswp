<?php
namespace LearnPress\Upsell\Package;

use LearnPress\Models\CourseModel;
use LP_Addon_Upsell_Preload;
use LP_Database;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

class Checkout {
	public static $key_meta_package_courses = '_lp_package_courses';

	protected static $instance = null;

	public function __construct() {
		// Cart.
		add_filter( 'learn-press/purchase/item-types/can-purchase', array( $this, 'add_post_type' ) );
		add_filter( 'learn-press/review-order/cart-item-subtotal', array( $this, 'cart_item_subtotal' ), 10, 3 );
		add_filter( 'learnpress/cart/calculate_sub_total/item_type_' . LP_PACKAGE_CPT, array( $this, 'calculate_sub_total' ), 10, 2 );

		// Order and checkout
		add_filter( 'learnpress/order/add-item/item_type_' . LP_PACKAGE_CPT, array( $this, 'add_order_item' ), 10, 3 );
		add_action( 'learn-press/added-order-item-data', array( $this, 'add_order_meta' ), 10, 3 );
		add_filter( 'learn-press/order-item-not-course-id', array( $this, 'order_item_link_not_course_id' ), 10, 2 );
		add_filter( 'learn-press/order-item-not-course', array( $this, 'admin_order_item_html' ), 10, 1 );

		// Checkout page
		add_filter( 'learn-press/review-order/item', array( $this, 'review_order_item' ), 10, 2 );
		add_action( 'learn-press/checkout/cart-item', array( $this, 'checkout_cart_item' ), 10, 2 );
	}

	/**
	 * Add package post type to list of post types can purchase.
	 *
	 * @param $types
	 *
	 * @return mixed
	 */
	public function add_post_type( $types ) {
		$types[] = LP_PACKAGE_CPT;

		return $types;
	}

	public function review_order_item( $itemModel, $cart_item ) {
		if ( ! empty( $cart_item['item_id'] ) && get_post_type( $cart_item['item_id'] ) === LP_PACKAGE_CPT ) {
			return new Package( absint( $cart_item['item_id'] ) );
		}

		return $itemModel;
	}

	public function cart_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {
		if ( ! empty( $cart_item['item_id'] ) && get_post_type( $cart_item['item_id'] ) === LP_PACKAGE_CPT ) {
			$package = new Package( absint( $cart_item['item_id'] ) );

			$price        = $package->get_price();
			$row_subtotal = $price * $cart_item['quantity'];

			$subtotal = learn_press_format_price( $row_subtotal, true );
		}

		return $subtotal;
	}

	public function calculate_sub_total( $subtotal, $item ) {
		if ( ! empty( $item['item_id'] ) ) {
			$package  = new Package( absint( $item['item_id'] ) );
			$subtotal = $package->get_price() * absint( $item['quantity'] );
		}

		return $subtotal;
	}

	public function add_order_item( $item ) {
		if ( get_post_type( $item['item_id'] ) === LP_PACKAGE_CPT ) {
			$package = new Package( absint( $item['item_id'] ) );

			$item['quantity']        = absint( $item['quantity'] );
			$subtotal                = $package->get_price() * absint( $item['quantity'] );
			$item['item_type']       = get_post_type( $item['item_id'] );
			$item['subtotal']        = $subtotal;
			$item['total']           = $subtotal;
			$item['order_item_name'] = get_the_title( $item['item_id'] );
		}

		return $item;
	}

	/**
	 * Add package id to order item meta with key '_lp_package_post_id'.
	 * Add course list of package to order item meta with key '_lp_package_courses'.
	 *
	 * @param $item_id
	 * @param $item
	 * @param $order_id
	 *
	 * @return int|mixed
	 */
	public function add_order_meta( $item_id = 0, $item = array(), $order_id = 0 ) {
		if ( get_post_type( $item['item_id'] ) === LP_PACKAGE_CPT ) {
			$package = new Package( absint( $item['item_id'] ) );
			learn_press_add_order_item_meta( $item_id, '_lp_package_post_id', absint( $item['item_id'] ) );
			$course_ids   = $package->get_course_list();
			$data_courses = [];
			foreach ( $course_ids as $course_id ) {
				$course                     = CourseModel::find( $course_id, true );
				$data_courses[ $course_id ] = apply_filters(
					'lp/upsell/package/order-item-meta/course',
					[
						'id'    => $course_id,
						'title' => $course->get_title(),
						'price' => $course->get_price(),
					],
					$course
				);
			}
			self::update_extra_value(
				$item_id,
				self::$key_meta_package_courses,
				json_encode( $data_courses, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			);
		}

		return $item_id;
	}

	public function order_item_link_not_course_id( $link, $item ) {
		$user = wp_get_current_user();

		if ( ! $user ) {
			return $link;
		}

		if ( isset( $item['_lp_package_post_id'] ) ) {
			$edit_post_link = get_edit_post_link( $item['_lp_package_post_id'] );
			$title          = get_the_title( $item['_lp_package_post_id'] );

			if ( empty( $edit_post_link ) ) {
				$edit_post_link = '#';
			}

			if ( ! is_admin() ) {
				$edit_post_link = get_permalink( $item['_lp_package_post_id'] );

				if ( empty( $edit_post_link ) ) {
					$edit_post_link = '#';
				}
			}

			$link       = '<a href="' . $edit_post_link . '">' . $title . '</a>';
			$courses_rs = self::get_extra_value( $item['id'], self::$key_meta_package_courses );
			$courses    = json_decode( $courses_rs );
			if ( ! empty( $courses ) ) {
				$link .= '<ol>';
				foreach ( $courses as $course ) {
					$link_course = get_permalink( $course->id ?? 0 );
					if ( is_admin() ) {
						$link_course = get_edit_post_link( $course->id ?? 0 );
					}
					$link .= sprintf( '<li><a href="%s">%s</a></li>', $link_course, $course->title ?? '' );
				}
				$link .= '</ol>';
			}
		}

		return $link;
	}

	// Display package item in admin order.
	public function admin_order_item_html( $item = array() ) {
		if ( empty( $item['_lp_package_post_id'] ) ) {
			return;
		}

		$package_id = absint( $item['_lp_package_post_id'] );
		?>
		<tr class="order-item-row" data-item_id="<?php echo $item['id']; ?>" data-id="<?php echo $package_id; ?>" data-remove_nonce="<?php echo wp_create_nonce( 'remove_order_item' ); ?>">
			<td class="column-name">
				<?php if ( isset( $order ) && 'pending' === $order->get_status() ) : ?>
					<a class="remove-order-item" href="">
						<span class="dashicons dashicons-trash"></span>
					</a>
				<?php endif; ?>

				<?php
				$link_item = '<a href="' . get_edit_post_link( $item['_lp_package_post_id'] ) . '" target="_blank" rel="noopener"><strong style="font-size:15px">' . get_the_title( $item['_lp_package_post_id'] ) . '</strong></a>';

				echo apply_filters( 'learn_press/order_detail_cert_item_link', $link_item, $item );
				?>
			</td>

			<td class="column-price align-right">
				<?php echo learn_press_format_price( $item['total'] ?? 0, $currency_symbol ?? '$' ); ?>
			</td>

			<td class="column-quantity align-right">
				<small class="times">×</small>
				<?php echo $item['quantity'] ?? 0; ?>
			</td>

			<td class="column-total align-right"><?php echo learn_press_format_price( $item['total'] ?? 0, $currency_symbol ?? '$' ); ?></td>
		</tr>
		<?php
		$courses_rs = self::get_extra_value( $item['id'], self::$key_meta_package_courses );
		$courses    = json_decode( $courses_rs );
		foreach ( $courses as $course ) {
			LP_Addon_Upsell_Preload::$addon->get_admin_template( 'order/package/item-course.php', compact( 'course', 'item' ) );
		}
	}

	/**
	 * Insert/Update extra value (Temporary, will be removed when added on LP_Order_DB )
	 *
	 * @param int    $user_item_id
	 * @param string $meta_key
	 * @param string $value
	 * @since 4.0.0
	 * @version 1.0.0
	 * @author tungnx
	 *
	 * @return int|false The number of rows inserted|updated, or false on error.
	 */
	public static function update_extra_value( $order_item_id = 0, $meta_key = '', $value = '' ) {
		$lp_db = LP_Database::getInstance();

		$data   = array(
			'learnpress_order_item_id' => $order_item_id,
			'meta_key'                 => $meta_key,
			'extra_value'              => $value,
		);
		$format = array( '%d', '%s', '%s' );

		$check_exist_data = $lp_db->wpdb->get_var(
			$lp_db->wpdb->prepare(
				"
				SELECT meta_id FROM {$lp_db->tb_lp_order_itemmeta}
				WHERE learnpress_order_item_id = %d
				AND meta_key = %s
				",
				$order_item_id,
				$meta_key
			)
		);

		if ( $check_exist_data ) {
			$result = $lp_db->wpdb->update(
				$lp_db->tb_lp_order_itemmeta,
				$data,
				array(
					'learnpress_order_item_id' => $order_item_id,
					'meta_key'                 => $meta_key,
				),
				$format
			);
		} else {
			$result = $lp_db->wpdb->insert( $lp_db->tb_lp_order_itemmeta, $data, $format );
		}

		return $result;
	}

	/**
	 * Get extra value (Temporary, will be removed when added on LP_Order_DB )
	 *
	 * @param int $order_item_id
	 * @param string $meta_key
	 *
	 * @return string|null
	 */
	public static function get_extra_value( int $order_item_id = 0, string $meta_key = '' ) {
		$lp_db = LP_Database::getInstance();

		return $lp_db->wpdb->get_var(
			$lp_db->wpdb->prepare(
				"
				SELECT `extra_value` FROM $lp_db->tb_lp_order_itemmeta
				WHERE `learnpress_order_item_id` = %d
				AND `meta_key` = %s
				",
				$order_item_id,
				$meta_key
			)
		);
	}

	public function checkout_cart_item( $package, $cart_item ) {
		if ( ! $package instanceof Package ) {
			return;
		}

		$section = [
			'td_image' => sprintf(
				'<td class="course-thumbnail">%s</td>',
				wp_kses_post( $package->get_image_html() )
			),
			'td_name'  => sprintf(
				'<td class="course-name"><a href="%s" class="course-name">%s</a></td>',
				esc_url_raw( $package->get_permalink() ),
				wp_kses_post( $package->get_title() )
			),
			'td_total' => sprintf(
				'<td class="course-total col-number">%s</td>',
				learn_press_format_price( $package->get_price() * $cart_item['quantity'] )
			),
		];

		echo Template::combine_components( $section );
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Checkout::instance();
