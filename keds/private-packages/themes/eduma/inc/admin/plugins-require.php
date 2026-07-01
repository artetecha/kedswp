<?php

function thim_get_all_plugins_require( $plugins ) {
	$extra_plugin = array();

	$plugins = array(
		array(
			'name'     => 'LearnPress',
			'slug'     => 'learnpress',
			'required' => false,
			'icon'     => 'https://ps.w.org/learnpress/assets/icon-128x128.gif',
		),

		array(
			'name'     => 'WooCommerce Add-On for LearnPress',
			'slug'     => 'learnpress-woo-payment',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'Certificates Add-On for LearnPress',
			'slug'     => 'learnpress-certificates',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'Gradebook Add-On for LearnPress',
			'slug'     => 'learnpress-gradebook',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'Stripe Add-On for LearnPress',
			'slug'     => 'learnpress-stripe',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'Content Drip Add-On for LearnPress',
			'slug'     => 'learnpress-content-drip',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'Live Course Add-On for LearnPress',
			'slug'     => 'learnpress-live',
			'required' => false,
			'premium'  => true,
			'add-on'   => true,
		),

		array(
			'name'     => 'Random Quiz Add-On for LearnPress',
			'slug'     => 'learnpress-random-quiz',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'Co-Instructors Add-On for LearnPress',
			'slug'     => 'learnpress-co-instructor',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'Sorting Choice Add-On for LearnPress',
			'slug'     => 'learnpress-sorting-choice',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'Commission Add-On for LearnPress',
			'slug'     => 'learnpress-commission',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'WPML Add-On for LearnPress',
			'slug'     => 'learnpress-wpml',
			'required' => false,
			'premium'  => true,
			'add-on'   => true,
		),

		array(
			'name'     => 'Collections Add-On for LearnPress',
			'slug'     => 'learnpress-collections',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),
		array(
			'name'     => 'LearnPress - Upsell',
			'slug'     => 'learnpress-upsell',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),
		array(
			'name'     => 'LearnPress - Assignments',
			'slug'     => 'learnpress-assignments',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),
		array(
			'name'     => 'LearnPress - Announcements',
			'slug'     => 'learnpress-announcements',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),
		array(
			'name'       => 'Paid Memberships Pro',
			'slug'       => 'paid-memberships-pro',
			'required'   => false,
			'no-install' => true,
			'premium'    => true,
			'source'     => 'https://license.paidmembershipspro.com/downloads/free/paid-memberships-pro.zip',
		),
		array(
			'name'     => 'Interactive Content – H5P',
			'slug'     => 'h5p',
			'required' => false,
		),
		array(
			'name'     => 'Paid Memberships Pro Add-On for LearnPress',
			'slug'     => 'learnpress-paid-membership-pro',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'BuddyPress',
			'slug'     => 'buddypress',
			'required' => false,
		),

		array(
			'name'     => 'bbPress',
			'slug'     => 'bbpress',
			'required' => false,
		),

		array(
			'name'     => 'LearnPress – Course Review',
			'slug'     => 'learnpress-course-review',
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'LearnPress – Prerequisites Courses',
			'slug'     => 'learnpress-prerequisites-courses',
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'LearnPress – Export Import',
			'slug'     => 'learnpress-import-export',
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'LearnPress – BuddyPress Integration',
			'slug'     => 'learnpress-buddypress',
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'H5P Add-On for LearnPress',
			'slug'     => 'learnpress-h5p',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'Authorize.Net Add-On for LearnPress',
			'slug'     => 'learnpress-authorizenet-payment',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'Coming Soon Add-On for LearnPress',
			'slug'     => 'learnpress-coming-soon-courses',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'myCRED Add-On for LearnPress',
			'slug'     => 'learnpress-mycred',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'Student List Add-On for LearnPress',
			'slug'     => 'learnpress-students-list',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'LearnPress – Course Wishlist',
			'slug'     => 'learnpress-wishlist',
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'LearnPress – bbPress Integration',
			'slug'     => 'learnpress-bbpress',
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'     => 'Learnpress Instamojo',
			'slug'     => 'learnpress-instamojo-payment',
			'required' => false,
			'premium'  => true,
			'add-on'   => true,
		),

		array(
			'name'        => 'Learnpress Razorpay',
			'slug'        => 'learnpress-razorpay-payment',
			'required'    => false,
			'premium'     => true,
			'add-on'      => true,
			'description' => 'Razorpay payment gateway for LearnPress',
		),
		array(
			'name'     => 'WP Events Manager',
			'slug'     => 'wp-events-manager',
			'required' => false,
		),
		array(
			'name'     => '2Checkout Add-On for LearnPress',
			'slug'     => 'learnpress-2checkout-payment',
			'premium'  => true,
			'required' => false,
			'add-on'   => true,
		),
		array(
			'name'     => 'WP Events Manager - WooCommerce Payment ',
			'slug'     => 'wp-events-manager-woocommerce-payment-methods-integration',
			'required' => false,
			'add-on'   => true,
		),

		array(
			'name'       => 'Instagram Feed',
			'slug'       => 'instagram-feed',
			'no-install' => true,
			'required'   => false,
		),

		array(
			'name'        => 'Contact Form 7',
			'slug'        => 'contact-form-7',
			'required'    => false,
			'description' => 'Just another contact form plugin. Simple but flexible',
		),

		array(
			'name'     => 'MailChimp for WordPress',
			'slug'     => 'mailchimp-for-wp',
			'required' => false,
		),

		array(
			'name'       => 'Loco Translate',
			'slug'       => 'loco-translate',
			'required'   => false,
			'silent'     => true,
			'no-install' => true,
		),

		array(
			'name'     => 'Thim Portfolio',
			'slug'     => 'tp-portfolio',
			'premium'  => true,
			'required' => false,
		),

		// array(
		//  'name'       => 'Thim Twitter',
		//  'slug'       => 'thim-twitter',
		//  'premium'    => true,
		//  'required'   => false,
		//  'no-install' => true,
		// ),

		array(
			'name'        => 'Elementor Page Builder',
			'slug'        => 'elementor',
			'required'    => false,
			'description' => 'The most advanced frontend drag & drop page builder. Create high-end, pixel perfect websites at record speeds. Any theme, any page, any design.',
			'icon'        => 'https://ps.w.org/elementor/assets/icon-128x128.gif',
		),
		array(
			'name'       => 'Thim Elementor Kit',
			'slug'       => 'thim-elementor-kit',
			// 'premium'     => true,
			'no-install' => true,
			'required'   => false,
		),

		array(
			'name'        => 'WooCommerce',
			'slug'        => 'woocommerce',
			'required'    => false,
			'description' => 'An eCommerce toolkit that helps you sell anything. Beautifully.',
			// 'icon'        => 'https://ps.w.org/woocommerce/assets/icon.svg',
		),
		array(
			'name'     => 'Woo Booster Toolkit',
			'slug'     => 'woo-booster-toolkit',
			'premium'  => true,
			'required' => false,
		),
		array(
			'name'        => 'TP Social Login',
			'slug'        => 'tp-login',
			'premium'     => true,
			'required'    => false,
			'description' => 'TP Login – Advanced Login Security with CAPTCHA, Social Login & Protection Tools',
		),
	);

	if ( get_theme_mod( 'thim_page_builder_chosen' ) == 'visual_composer' ) {
		$extra_plugin = array(
			array(
				'name'     => 'WPBakery Page Builder',
				'slug'     => 'js_composer',
				'premium'  => true,
				'required' => false,
				'icon'     => 'https://s3.envato.com/files/260579516/wpb-logo.png',
			),
		);
	}

	if ( class_exists( 'RevSlider' ) ) {
		$extra_plugin = array(
			array(
				'name'       => 'Revolution Slider',
				'slug'       => 'revslider',
				'premium'    => true,
				'required'   => false,
				'no-install' => true,
			),
		);
	}

	return array_merge( $plugins, $extra_plugin );
}

add_filter( 'thim_core_get_all_plugins_require', 'thim_get_all_plugins_require' );

add_filter( 'thim_core_plugin_icon_install', 'thim_custom_plugin_icon', 10, 2 );
if ( ! function_exists( 'thim_custom_plugin_icon' ) ) {
	function thim_custom_plugin_icon( $icon, $plugin ) {
		if ( ! $plugin->is_wporg() ) {
			$icon = 'https://updates.thimpress.com/images/' . $plugin->get_slug() . '.png';
		}

		return $icon;
	}
}
