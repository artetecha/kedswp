<?php
namespace LearnPress\Upsell\Coupon;

class Core_Functions {

	protected static $instance = null;

	public function is_coupons_enabled() {
		return 'yes' === \LP_Settings::instance()->get( 'coupon.enable' );
	}

	/**
	 * Check enable show list coupon on checkout page.
	 *
	 * @return bool
	 * @since 4.0.5
	 * @version 1.0.0
	 */
	public function is_show_list_coupon_checkout(): bool {
		return 'yes' === \LP_Settings::instance()->get( 'coupon.show_list_coupons_checkout', 'no' );
	}

	public function get_coupon_id_by_code( $code ) {
		global $wpdb;

		$coupon_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_title = %s",
				LP_COUPON_CPT,
				'publish',
				$code
			)
		);

		return absint($coupon_id);
	}

	public function validate_coupon_exists( $coupon ) {
		if ( ! $coupon->exists() ) {
			return false;
		}

		if ( ! $coupon->is_visible() ) {
			return false;
		}

		return true;
	}

	public function validate_coupon_usage_limit( $coupon ) {
		$usage_limit = absint( $coupon->get_usage_limit() );
		$usage_count = absint( $coupon->get_usage_count() );

		if ( ! $usage_limit ) {
			return true;
		}

		if ( $usage_count >= $usage_limit ) {
			return false;
		}

		return true;
	}

	public function validate_coupon_user_usage_limit( $coupon, $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Is guest.
		if ( empty( $user_id ) ) {
			return true;
		}

		$usage_count = $this->get_usage_by_user_id( $coupon, $user_id );

		if ( ! empty( $coupon->get_usage_limit_per_user() ) && $usage_count >= $coupon->get_usage_limit_per_user() ) {
			return false;
		}

		return true;
	}

	public function validate_coupon_expiry_date( $coupon ) {
		$start_date = $coupon->get_discount_start_date(); // YYYY-MM-DD HH:MM:SS
		$end_date = $coupon->get_discount_end_date(); // YYYY-MM-DD HH:MM:SS

		if ( ! empty( $start_date ) && strtotime( get_gmt_from_date( $start_date ) ) > time() ) {
			return false;
		}

		if ( ! empty( $end_date ) && strtotime( get_gmt_from_date( $end_date ) ) < time() ) {
			return false;
		}

		return true;
	}


	public function validate_coupon_packages_ids( $coupon ) {
		if ( count( $coupon->get_include_package_ids() ) > 0 ) {
			$valid = true;

			foreach( $this->get_items_from_cart() as $item ) {
				$item_type = get_post_type( $item['item_id'] );

				if ( $item_type === LP_PACKAGE_CPT && ! in_array( $item['item_id'], $coupon->get_include_package_ids() ) ) {
					$valid = false;
					break;
				}
			}

			if ( ! $valid ) {
				return false;
			}
		}

		return true;
	}

	public function validate_coupon_course_ids( $coupon ) {
		if ( count( $coupon->get_include_course_ids() ) > 0 ) {
			$valid = true;

			foreach( $this->get_items_from_cart() as $item ) {
				$item_type = get_post_type( $item['item_id'] );

				if ( $item_type === LP_COURSE_CPT && ! in_array( $item['item_id'], $coupon->get_include_course_ids() ) ) {
					$valid = false;
					break;
				}
			}

			if ( ! $valid ) {
				return false;
			}
		}

		return true;
	}

	public function validate_coupon_course_categories( $coupon ) {
		if ( count( $coupon->get_include_course_category_ids() ) > 0 ) {
			$valid = true;

			foreach( $this->get_items_from_cart() as $item ) {
				$item_type = get_post_type( $item['item_id'] );

				if ( $item_type === LP_COURSE_CPT ) {
					$course_category_ids = wp_get_post_terms( $item['item_id'], 'course_category', array( 'fields' => 'ids' ) );

					if ( ! empty( $course_category_ids ) ) {
						$intersect = array_intersect( $course_category_ids, $coupon->get_include_course_category_ids() );

						if ( empty( $intersect ) ) {
							$valid = false;
							break;
						}
					}
				}
			}

			if ( ! $valid ) {
				return false;
			}
		}

		return true;
	}

	public function validate_coupon_excluded_items( $coupon ) {
		$valid = true;

		foreach( $this->get_items_from_cart() as $item ) {
			$item_type = get_post_type( $item['item_id'] );

			if ( $item_type === LP_COURSE_CPT && count( $coupon->get_exclude_course_ids() ) > 0 && in_array( $item['item_id'], $coupon->get_exclude_course_ids() ) ) {
				$valid = false;
				break;
			}

			if ( $item_type === LP_PACKAGE_CPT && count( $coupon->get_exclude_package_ids() ) > 0 && in_array( $item['item_id'], $coupon->get_exclude_package_ids() ) ) {
				$valid = false;
				break;
			}
		}

		if ( ! $valid ) {
			return false;
		}

		return true;
	}

	public function validate_coupon_excluded_course_category_ids( $coupon ) {
		if ( $coupon->get_exclude_course_category_ids() ) {
			$valid = true;

			foreach( $this->get_items_from_cart() as $item ) {
				$item_type = get_post_type( $item['item_id'] );

				if ( $item_type === LP_COURSE_CPT ) {
					$course_category_ids = wp_get_post_terms( $item['item_id'], 'course_category', array( 'fields' => 'ids' ) );

					if ( ! empty( $course_category_ids ) ) {
						$intersect = array_intersect( $course_category_ids, $coupon->get_exclude_course_category_ids() );

						if ( ! empty( $intersect ) ) {
							$valid = false;
							break;
						}
					}
				}
			}

			if ( ! $valid ) {
				return false;
			}
		}

		return true;
	}

	public function is_has_discounted( $coupon ) {
		$coupon_code = $coupon->get_coupon_code();

		if ( ! $coupon_code ) {
			return false;
		}

		$applied_coupons = $this->get_applied_coupons();

		if ( ! in_array( $coupon_code, $applied_coupons ) ) {
			return false;
		}

		return true;
	}

	public function get_applied_coupons() {
		$cart_items = $this->get_items_from_cart();

		$applied_coupons = array();

		foreach ($cart_items as $cart_key => $cart_item) {
			$applied_coupons = ! empty( $cart_item['applied_coupons'] ) ? $cart_item['applied_coupons'] : array();
		}

		return $applied_coupons;
	}

	public function set_applied_coupons( $coupon ) {
		$coupon_code = $coupon->get_coupon_code();

		if ( ! $coupon_code ) {
			return false;
		}

		$cart = LP()->cart;

		$cart_items = $this->get_items_from_cart();

		foreach ($cart_items as $cart_key => $cart_item) {
			$applied_coupons = ! empty( $cart_item['applied_coupons'] ) ? $cart_item['applied_coupons'] : array();

			if ( ! in_array( $coupon_code, $applied_coupons ) ) {
				$applied_coupons[] = $coupon_code;
			}

			$cart_items[$cart_key]['applied_coupons'] = $applied_coupons;
		}

		$cart->update_session( $cart_items );
	}

	public function remove_applied_coupons( $coupon_code ) {
		$cart = LP()->cart;

		$cart_items = $this->get_items_from_cart();

		foreach ($cart_items as $cart_key => $cart_item) {
			$applied_coupons = ! empty( $cart_item['applied_coupons'] ) ? $cart_item['applied_coupons'] : array();
			$discount_amount = ! empty( $cart_item['discount_amount'] ) ? $cart_item['discount_amount'] : array();

			if ( in_array( $coupon_code, $applied_coupons ) ) {
				$applied_coupons = array_diff( $applied_coupons, array( $coupon_code ) );

				// remove key $coupon_code in discount_amount.
				if ( array_key_exists( $coupon_code, $discount_amount ) ) {
					unset( $discount_amount[$coupon_code] );
				}
			}

			$cart_items[$cart_key]['applied_coupons'] = $applied_coupons;
			$cart_items[$cart_key]['discount_amount'] = $discount_amount;
		}

		$cart->update_session( $cart_items );
	}

	public function get_items_from_cart() {
		$cart = LP()->cart;

		return $cart->get_items();
	}

	public function get_usage_by_user_id( $coupon, $user_id = 0 ) {
		global $wpdb;

		$usage_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( meta_id ) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_used_by' AND meta_value = %d;",
				$coupon->get_id(),
				$user_id
			)
		);

		return $usage_count;
	}

	public function get_usage_by_email( $coupon, $email ) {
		global $wpdb;

		$usage_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( meta_id ) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_used_by' AND meta_value = %s;",
				$coupon->get_id(),
				$email
			)
		);

		return $usage_count;
	}

	public function calculate_discount_amount( $coupon, $cart_subtotal ) {
		$discount_amount = 0;

		$discount_type = $coupon->get_discount_type();

		if ( $discount_type === 'percent' ) {
			$discount_amount = $cart_subtotal * $coupon->get_discount_amount() / 100;
		} else {
			$discount_amount = $coupon->get_discount_amount();
		}

		return $discount_amount;
	}

	public function increase_usage_count( $coupon, $used_by = '' ) {
		$new_count = $this->update_usage_count_meta( $coupon, 'increase' );

		if ( $used_by ) {
			add_post_meta( $coupon->get_id(), '_used_by', strtolower( $used_by ) );
		}

		return $new_count;
	}

	public function decrement_usage_count( $coupon, $used_by = '' ) {
		global $wpdb;
		$new_count = $this->update_usage_count_meta( $coupon, 'decrease' );

		if ( $used_by ) {
			$meta_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = '_used_by' AND meta_value = %s AND post_id = %d LIMIT 1;",
					$used_by,
					$coupon->get_id()
				)
			);
			if ( $meta_id ) {
				delete_metadata_by_mid( 'post', $meta_id );
			}
		}

		return $new_count;
	}

	public function update_usage_count_meta( $coupon, $action = 'increase' ) {
		global $wpdb;

		$id       = $coupon->get_id();
		$operator = ( 'increase' === $action ) ? '+' : '-';

		add_post_meta( $id, 'usage_count', $coupon->get_usage_count(), true );

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE $wpdb->postmeta SET meta_value = meta_value {$operator} 1 WHERE meta_key = 'usage_count' AND post_id = %d;",
				$id
			)
		);

		// Get the latest value direct from the DB, instead of possibly the WP meta cache.
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'usage_count' AND post_id = %d;", $id ) );
	}

	public function is_coupon_emails_allowed( $check_emails, $restrictions ) {
		foreach ( $check_emails as $check_email ) {
			// With a direct match we return true.
			if ( in_array( $check_email, $restrictions, true ) ) {
				return true;
			}

			// Go through the allowed emails and return true if the email matches a wildcard.
			foreach ( $restrictions as $restriction ) {
				// Convert to PHP-regex syntax.
				$regex = '/^' . str_replace( '*', '(.+)?', $restriction ) . '$/';
				preg_match( $regex, $check_email, $match );
				if ( ! empty( $match ) ) {
					return true;
				}
			}
		}

		// No matches, this one isn't allowed.
		return false;
	}

	public function format_coupon_code( $coupon_code ) {
		$coupon_code = html_entity_decode( $coupon_code );
		$coupon_code = trim( $coupon_code );
		$coupon_code = function_exists( 'mb_strtolower' ) ? mb_strtolower( $coupon_code ) : strtolower( $coupon_code );

		return $coupon_code;
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
