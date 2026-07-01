<?php

namespace LearnPress\WooPayment\TemplateHooks;

use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseButtonBlockType;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LP_WC_Hooks;

/**
 * Class LPWooTemplate
 *
 * @since 4.1.4
 * @version 1.0.0
 */
class LPWooTemplate {
	use Singleton;

	public function init() {
		add_action( 'learnpress/woo-payment/btn-add-item-to-cart/layout', [ $this, 'btn_add_to_cart' ] );
	}

	/**
	 * Button add item to cart/view cart
	 * Require item id and item type
	 *
	 * @param array $item [ 'id', 'type' ]
	 *
	 * @return void
	 * @since 4.1.4
	 * @version 1.0.2
	 */
	public function btn_add_to_cart( array $item ) {
		if ( empty( $item['id'] ) || empty( $item['type'] ) ) {
			return;
		}
		wp_enqueue_style( 'lp-woo-css' );
		wp_enqueue_script( 'lp-woo-payment-js' );

		$is_added_to_cart = LP_WC_Hooks::instance()->is_added_in_cart( $item['id'] );
		if ( ! $is_added_to_cart ) {
			$section = [
				'form'               => '<form name="form-add-item-to-cart" method="post">',
				'button_add_to_cart' => sprintf(
					'<button class="lp-button lp-btn-add-item-to-cart %s" type="submit">%s</button>',
					esc_attr( $item['type'] ),
					__( 'Add to cart', 'learnpress-woo-payment' )
				),
				'input_id'           => sprintf(
					'<input type="hidden" name="item-id" value="%s"/>',
					esc_attr( $item['id'] )
				),
				'input_type'         => sprintf(
					'<input type="hidden" name="item-type" value="%s"/>',
					esc_attr( $item['type'] )
				),
				'form_end'           => '</form>',
			];

			$html_btn_cart = Template::combine_components( $section );
		} else {
			$html_btn_cart = $this->html_btn_view_cart();
		}

		$section = apply_filters(
			'learn-press/lp-woo/html-btn-add-item-to-cart',
			[
				'wrapper'     => '<div class="wrap-btn-add-course-to-cart">',
				'btn_cart'    => $html_btn_cart,
				'wrapper_end' => '</div>',
			],
			$item,
			$is_added_to_cart,
			$html_btn_cart
		);

		echo Template::combine_components( $section );
	}

	/**
	 * HTML button view cart
	 *
	 * @return string
	 * @since 4.1.5
	 * @version 1.0.0
	 */
	public function html_btn_view_cart(): string {
		$html_btn_cart = sprintf(
			'<a href="%s"><button class="lp-button lp-btn-woo-view-cart">%s</button></a>',
			esc_url_raw( wc_get_cart_url() ),
			__( 'View cart', 'learnpress-woo-payment' )
		);

		return apply_filters( 'learn-press/lp-woo/html-btn-view-cart', $html_btn_cart );
	}
}
