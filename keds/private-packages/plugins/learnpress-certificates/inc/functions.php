<?php

/**
 * Check if current page is Course Builder frontend.
 *
 * @return bool
 */
function lp_cert_is_course_builder(): bool {
	return ! is_admin()
		&& class_exists( \LearnPress\CourseBuilder\CourseBuilder::class )
		&& method_exists( '\LP_Page_Controller', 'is_page_course_builder' )
		&& \LP_Page_Controller::is_page_course_builder();
}

function lp_cert_share_link_enabled(): bool {
	return 'yes' === LearnPress::instance()->settings()->get( 'certificates.socials_share_link', 'no' );
}

function lp_cert_render_toggle( array $args = array() ): string {
	$args = wp_parse_args( $args, array(
		'name'             => '',
		'id'               => '',
		'checked'          => false,
		'value'            => '1',
		'disabled'         => false,
		'attrs'            => array(),
		'input_attrs'      => array(),
		'label_text'       => '',
		'label_text_class' => '',
	) );

	$label_classes = array( 'lp-cert-toggle' );
	if ( ! empty( $args['attrs']['class'] ) ) {
		$label_classes[] = $args['attrs']['class'];
		unset( $args['attrs']['class'] );
	}

	$label_attrs = array_merge(
		array( 'class' => trim( implode( ' ', array_filter( $label_classes ) ) ) ),
		$args['attrs']
	);
	if ( ! empty( $args['id'] ) ) {
		$label_attrs['for'] = $args['id'];
	}

	$input_attrs = array_merge(
		array(
			'type'  => 'checkbox',
			'value' => $args['value'],
		),
		$args['input_attrs']
	);
	if ( ! empty( $args['name'] ) ) {
		$input_attrs['name'] = $args['name'];
	}
	if ( ! empty( $args['id'] ) ) {
		$input_attrs['id'] = $args['id'];
	}

	$label_html = '';
	foreach ( $label_attrs as $k => $v ) {
		$label_html .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}

	$input_html = '';
	foreach ( $input_attrs as $k => $v ) {
		$input_html .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}
	if ( $args['checked'] ) {
		$input_html .= ' checked';
	}
	if ( $args['disabled'] ) {
		$input_html .= ' disabled';
	}

	$text_html = '';
	if ( $args['label_text'] ) {
		$text_class = esc_attr( $args['label_text_class'] );
		if ( ! empty( $args['id'] ) ) {
			$text_html = sprintf(
				'<label for="%1$s" class="%2$s">%3$s</label>',
				esc_attr( $args['id'] ),
				$text_class,
				esc_html( $args['label_text'] )
			);
		} else {
			$text_html = sprintf(
				'<span class="%1$s">%2$s</span>',
				$text_class,
				esc_html( $args['label_text'] )
			);
		}
	}

	$html = sprintf(
		'<label%1$s><input%2$s /><span class="lp-cert-toggle__slider" aria-hidden="true"></span></label>%3$s',
		$label_html,
		$input_html,
		$text_html
	);

	return lp_cert_toggle_styles() . $html;
}

function lp_cert_toggle_styles(): string {
	static $printed = false;
	if ( $printed ) {
		return '';
	}
	$printed = true;

	return '<style id="lp-cert-toggle-inline-css">'
		. '.lp-cert-toggle{position:relative;display:inline-block;width:46px;min-width:46px;height:26px;margin:0;cursor:pointer;user-select:none}'
		. '.lp-cert-toggle input{position:absolute;width:0;height:0;opacity:0;pointer-events:none}'
		. '.lp-cert-toggle__slider{position:absolute;inset:0;background:#c3c4c7;border-radius:26px;transition:background-color .2s ease}'
		. '.lp-cert-toggle__slider::before{content:"";position:absolute;top:3px;left:3px;width:20px;height:20px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:transform .2s ease}'
		. '.lp-cert-toggle input:checked + .lp-cert-toggle__slider{background:#28a746}'
		. '.lp-cert-toggle input:checked + .lp-cert-toggle__slider::before{transform:translateX(20px)}'
		. '.lp-cert-toggle input:disabled + .lp-cert-toggle__slider{opacity:.6;cursor:not-allowed}'
		. '.lp-cert-toggle-text{font-size:14px;font-weight:600;line-height:1.5;color:#23282d}'
		. '.lp-cert-toggle-text__sub{margin-top:2px;font-size:14px;font-weight:400;line-height:1.5;color:#666;max-width:none;white-space:normal}'
		. '</style>';
}

/**
 * @param LP_User_Certificate $certificate
 */
add_action( 'learn-press/certificates/after-certificate-content', 'learn_press_certificates_buttons', 10 );
function learn_press_certificates_buttons( $certificate ) {
	$twitter    = LearnPress::instance()->settings()->get( 'certificates.socials_twitter' );
	$facebook   = LearnPress::instance()->settings()->get( 'certificates.socials_facebook' );
	$share_link = LearnPress::instance()->settings()->get( 'certificates.socials_share_link' );

	$socials = array();

	if ( 'yes' === $twitter || 'yes' === $facebook || 'yes' === $share_link ) {
		if ( $facebook === 'yes' ) {
			/*
			$link      = 'https://www.facebook.com/sharer/sharer.php?u=';
			$socials[] = sprintf(
				'<a href="%s" class="social-fb-svg social-cert" target="_blank"><img src="%s" alt="share-certificate-facebook"></a>',
				$link,
				LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/facebook.svg' )
			);
			*/
			$link      = 'https://www.facebook.com/sharer/sharer.php?u=';
			$label     = esc_html__( 'Share Facebook', 'learnpress-certificates' );
			$socials[] = sprintf(
				'<a href="%1$s" class="social-fb-svg social-cert certificate-action__button" target="_blank" aria-label="%3$s"><img src="%2$s" alt="share-certificate-facebook"><span class="certificate-action__label">%3$s</span></a>',
				$link,
				LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/facebook.svg' ),
				$label
			);
		}

		if ( $twitter === 'yes' ) {
			/*
			$link      = 'https://twitter.com/intent/tweet?text=';
			$socials[] = sprintf(
				'<a href="%s" class="social-twitter-svg social-cert" target="_blank"><img src="%s" alt="share-certificate-twitter"></a>',
				$link,
				LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/twitter.svg' )
			);
			*/
			$link      = 'https://twitter.com/intent/tweet?text=';
			$label     = esc_html__( 'Share X', 'learnpress-certificates' );
			$socials[] = sprintf(
				'<a href="%1$s" class="social-twitter-svg social-cert certificate-action__button" target="_blank" aria-label="%3$s"><img src="%2$s" alt="share-certificate-twitter"><span class="certificate-action__label">%3$s</span></a>',
				$link,
				LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/twitter.svg' ),
				$label
			);
		}

		if ( $share_link === 'yes' ) {
			$cert_url  = $certificate->get_permalink();
			/*
			$socials[] = sprintf(
				'<button type="button" class="social-copy-link social-cert" data-cert-url="%s" title="%s"><img src="%s" alt="copy-certificate-link"><span class="lp-cert-copy-tooltip">%s</span></button>',
				esc_attr( $cert_url ),
				esc_attr__( 'Copy Link', 'learnpress-certificates' ),
				LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/svg/copy-link.svg' ),
				esc_html__( 'Copied!', 'learnpress-certificates' )
			);
			*/
			$label     = esc_html__( 'Share Link', 'learnpress-certificates' );
			$socials[] = sprintf(
				'<button type="button" class="social-share-link social-cert certificate-action__button" data-cert-url="%1$s" title="%2$s" aria-label="%3$s"><img src="%4$s" alt="share-certificate-link"><span class="certificate-action__label">%3$s</span><span class="lp-cert-share-tooltip">%5$s</span></button>',
				esc_attr( $cert_url ),
				esc_attr__( 'Share Link', 'learnpress-certificates' ),
				$label,
				LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/svg/copy-link.svg' ),
				esc_html__( 'Copied!', 'learnpress-certificates' )
			);
		}
	}
	$socials = apply_filters( 'learn-press/certificates/socials-share', $socials, $certificate );
	LP_Addon_Certificates_Preload::$addon->get_template(
		'buttons-action.php',
		compact( 'socials', 'certificate' )
	);
}

if ( ! function_exists( 'learn_press_certificate_buy_button' ) ) {
	function learn_press_certificate_buy_button( $course ) {
		$course_id = $course->get_id();

		if ( $course_id ) {
			$lp_woo_payment_enable = 'no';

			if ( is_plugin_active( 'learnpress-woo-payment/learnpress-woo-payment.php' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				$lp_woo_payment_enable = LearnPress::instance()->settings()->get( 'woo-payment.enable', 'no' );
			}

			if ( class_exists( 'WooCommerce' ) && $lp_woo_payment_enable == 'yes' ) {
				$wc_cart = WC()->cart;
				if ( ! $wc_cart ) {
					include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
					include_once WC_ABSPATH . 'includes/class-wc-cart.php';
					wc_load_cart();
					$wc_cart = WC()->cart;
				}

				$cert_id_assign_of_course = get_post_meta( $course_id, '_lp_cert', true );
				$flag_found               = false;

				// Check certificate added to cart
				foreach ( $wc_cart->get_cart() as $cart_item ) {
					if ( isset( $cart_item['lp_cert_id'] )
						&& $cart_item['lp_cert_id'] == $cert_id_assign_of_course
						&& $cart_item['course_id'] == $course_id ) {
						$flag_found = true;
					}
				}

				if ( $flag_found ) {
					$woo_cart_url = wc_get_cart_url();
					if ( class_exists( 'SitePress' ) ) {
						$current_lang = apply_filters( 'wpml_current_language', null );
						$woo_cart_url = add_query_arg( [ 'lang' => $current_lang ], $woo_cart_url );
					}
					echo '<a class="btn-lp-cert-view-cart" href="' . $woo_cart_url . '"><button class="lp-button">' . __( 'View cart certificate', 'learnpress-certificates' ) . '</button></a>';
				} else {
					LP_Addon_Certificates_Preload::$addon->get_template( 'button-woo-certificate-add-to-cart.php', compact( 'course' ) );
				}
			} else {
				LP_Addon_Certificates_Preload::$addon->get_template( 'button-purchase-certificate.php', compact( 'course' ) );
			}
		}
	}
}
