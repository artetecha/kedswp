<?php
function thim_eduma_custom_action_footer() {
	// add action for login_popup_footer;
	do_action( 'thim_login_popup_footer' );
	// Nav Footer
	thim_nav_bar_mobile_footer();
}

add_action( 'wp_footer', 'thim_eduma_custom_action_footer', 15 );
// Add Mobile Navbar Mobile
function thim_nav_bar_mobile_footer() {
	// show form login popup in single course
	if ( get_theme_mod( 'thim_learnpress_single_popup', true ) && ! get_theme_mod( 'navbar_mobile_show', false ) ) {
		if ( ! has_action( 'thim_login_popup_footer' ) && is_single() && get_post_type() == 'lp_course' ) {
			echo '<div class="thim-login-popup thim-link-login"><a class="login js-show-popup" href="#" style="display: none"></a>';
			thim_form_login_popup();
			echo '</div>';
		}
	} else {
		if ( ! get_theme_mod( 'navbar_mobile_show', false ) ) {
			return;
		}
		$nav_mobile_items = get_theme_mod( 'nav_mobile_item', [ 'home', 'course', 'search', 'account' ] );
		$active           = '';
		$has_account      = false;
		if ( ! empty( $nav_mobile_items ) ) {
			echo '<div class="navbar-mobile-button">';
			foreach ( $nav_mobile_items as $nav_item ) {
				$nav_item = apply_filters( 'thim_navbar_mobile_button', $nav_item );
				switch ( $nav_item ) {
					case 'home':
						$active = ( is_front_page() && ! is_home() ) ? ' active' : '';
						echo '<a href="' . esc_url( home_url( '/' ) ) . '" title="' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '" class="item-menubar' . $active . '"><i class="' . eduma_font_icon( 'home' ) . '"></i><span>' . esc_html__( 'Home', 'eduma' ) . '</span></a>';
						break;
					case 'course':
						if ( class_exists( 'LearnPress' ) ) {
							$active         = thim_check_learnpress() ? ' active' : '';
							$course_page_id = learn_press_get_page_id( 'courses' );
							echo '<a href="' . get_the_permalink( $course_page_id ) . '" title="' . esc_html__( 'Courses', 'learnpress' ) . '" class="item-menubar' . $active . '"><i class="lp-icon-book-open"></i><span>' . esc_html__( 'Courses', 'learnpress' ) . '</span></a>';
						}
						break;
					case 'shop':
						if ( class_exists( 'WooCommerce' ) ) {
							$active         = is_shop() ? ' active' : '';
							$course_page_id = get_option( 'woocommerce_shop_page_id' );
							$icon_shop      = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M6 2L3 6V20C3 20.5304 3.21071 21.0391 3.58579 21.4142C3.96086 21.7893 4.46957 22 5 22H19C19.5304 22 20.0391 21.7893 20.4142 21.4142C20.7893 21.0391 21 20.5304 21 20V6L18 2H6Z" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
												<path d="M3 6H21" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
												<path d="M16 10C16 11.0609 15.5786 12.0783 14.8284 12.8284C14.0783 13.5786 13.0609 14 12 14C10.9391 14 9.92172 13.5786 9.17157 12.8284C8.42143 12.0783 8 11.0609 8 10" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
												</svg>
												';
							echo '<a href="' . get_the_permalink( $course_page_id ) . '" title="' . esc_html__( 'Shop', 'eduma' ) . '" class="item-menubar' . $active . '">' . $icon_shop . '<span>' . esc_html__( 'Shop', 'eduma' ) . '</span></a>';
						}
						break;
					case 'articles':
						$blog_page_id  = get_option( 'page_for_posts' );
						$active        = ( is_home() || ( is_page() && get_queried_object_id() == $blog_page_id ) ) ? ' active' : '';
						$icon_articles = '<svg width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M2.125 5.5H17.125M14.625 9H4.625M10.625 13H4.625M4.625 17H14.625C16.2819 17 17.625 15.6569 17.625 14V4C17.625 2.34315 16.2819 1 14.625 1H4.625C2.96815 1 1.625 2.34315 1.625 4V14C1.625 15.6569 2.96815 17 4.625 17Z" stroke="var(--nav-mobile-color-text,#333)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
											</svg>';
						echo '<a href="' . esc_url( get_permalink( get_option( 'page_for_posts' ) ) ) . '" title="' . esc_html__( 'Blog', 'eduma' ) . '" class="item-menubar' . $active . '">' . $icon_articles . '<span>' . esc_html__( 'Articles', 'eduma' ) . '</span></a>';
						break;
					case 'search':
						if ( get_theme_mod( 'nav_mobile_search', 'course' ) == 'page' ) {
							$active = ( is_search() ) ? ' active' : '';
							echo '<a href="' . esc_url( home_url( '/?s=&post_type=post' ) ) . '" title="' . esc_html__( 'Search Posts', 'eduma' ) . '" class="item-menubar' . $active . '"><i class="' . eduma_font_icon( 'search' ) . '"></i><span>' . esc_html__( 'Search', 'eduma' ) . '</span></a>';
						} else {
							if ( class_exists( 'LearnPress' ) ) {
								$placeholder = esc_html__( 'What do you want to learn today?', 'eduma' );
								echo '<div class="item-menubar thim-course-search-overlay"><div class="search-toggle flex-center"><i class="' . eduma_font_icon( 'search' ) . '"></i><span>' . esc_html__( 'Search', 'eduma' ) . '</span></div>';
								wp_enqueue_script( 'search-course-widget' );
								thim_form_search_popup( $placeholder, true );
								echo '</div>';
							}
						}
						break;
					case 'account':
						if ( is_user_logged_in() && class_exists( 'LearnPress' ) ) {
							$link_account = learn_press_user_profile_link();
						} else {
							$link_account = thim_get_login_page_url();
						}
						$active = ( $link_account == get_the_permalink( get_the_ID() ) ) ? ' active' : '';
						echo '<div class="item-menubar thim-login-popup thim-link-login' . $active . '"><a class="login js-show-popup flex-center" href="' . esc_url( $link_account ) . '" title="' . esc_html__( 'Account', 'eduma' ) . '"><i class="' . eduma_font_icon( 'user' ) . '"></i><span>' . esc_html__( 'Account', 'eduma' ) . '</span></a></div>';
						$has_account = true;
						break;
					case 'cart':
						if ( class_exists( 'WooCommerce' ) ) {
							$active = is_cart() ? ' active' : '';
							echo '<a href="' . wc_get_cart_url() . '" title="' . esc_html__( 'Cart', 'eduma' ) . '" class="item-menubar' . $active . '"><i class="' . eduma_font_icon( 'shopping-cart' ) . '"></i><span>' . esc_html__( 'Cart', 'eduma' ) . '</span></a>';
						}
						break;
				}
			}
			echo '</div>';
			if ( ! has_action( 'thim_login_popup_footer' ) && $has_account ) {
				$setting            = [];
				$setting['captcha'] = get_theme_mod( 'captcha_form_login', false );
				$setting['term']    = get_theme_mod( 'terms_form_login', '' );
				thim_form_login_popup( $setting );
			}
		}
	}
}
