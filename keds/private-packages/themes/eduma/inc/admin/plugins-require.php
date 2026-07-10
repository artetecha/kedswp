<?php

function thim_get_all_plugins_require( $plugins ) {
	$extra_plugin = array();

	$plugins = array(
		array(
			'name'        => 'LearnPress',
			'slug'        => 'learnpress',
			'required'    => false,
			'description' => 'LearnPress is a WordPress complete solution for creating a Learning Management System (LMS). It can help you to create courses, lessons and quizzes. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'icon'        => 'https://ps.w.org/learnpress/assets/icon-128x128.gif',
		),

		array(
			'name'        => 'WooCommerce Add-On for LearnPress',
			'slug'        => 'learnpress-woo-payment',
			'premium'     => true,
			'required'    => false,
			'description' => 'Buy courses via Woocommerce. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Certificates Add-On for LearnPress',
			'slug'        => 'learnpress-certificates',
			'premium'     => true,
			'required'    => false,
			'description' => 'Create certificates for online courses. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Gradebook Add-On for LearnPress',
			'slug'        => 'learnpress-gradebook',
			'premium'     => true,
			'required'    => false,
			'description' => 'Manage gradebook for user. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Stripe Add-On for LearnPress',
			'slug'        => 'learnpress-stripe',
			'premium'     => true,
			'required'    => false,
			'description' => 'Stripe payment gateway for LearnPress. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Content Drip Add-On for LearnPress',
			'slug'        => 'learnpress-content-drip',
			'premium'     => true,
			'required'    => false,
			'description' => 'Drip content of course. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Live Course Add-On for LearnPress',
			'slug'        => 'learnpress-live',
			'required'    => false,
			'premium'     => true,
			'description' => 'Manage conferences related to the course for user. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Random Quiz Add-On for LearnPress',
			'slug'        => 'learnpress-random-quiz',
			'premium'     => true,
			'required'    => false,
			'description' => 'Randomize questions inside quiz. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Co-Instructors Add-On for LearnPress',
			'slug'        => 'learnpress-co-instructor',
			'premium'     => true,
			'required'    => false,
			'description' => 'Building courses with other instructors. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Sorting Choice Add-On for LearnPress',
			'slug'        => 'learnpress-sorting-choice',
			'premium'     => true,
			'required'    => false,
			'description' => 'Sorting Choice provide ability to sorting the options of a question to the right order. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Commission Add-On for LearnPress',
			'slug'        => 'learnpress-commission',
			'premium'     => true,
			'required'    => false,
			'description' => 'Commission add-on for LearnPress. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'WPML Add-On for LearnPress',
			'slug'        => 'learnpress-wpml',
			'required'    => false,
			'premium'     => true,
			'description' => 'Support multi languages with WPML for Learnpress LMS system. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Collections Add-On for LearnPress',
			'slug'        => 'learnpress-collections',
			'premium'     => true,
			'required'    => false,
			'description' => 'Collecting related courses into one collection by administrator. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),
		array(
			'name'        => 'LearnPress - Upsell',
			'slug'        => 'learnpress-upsell',
			'premium'     => true,
			'required'    => false,
			'description' => 'Increase revenue by offering premium packages, bonus coupons, and exclusive features to elevate the learning experience for your students. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),
		array(
			'name'        => 'LearnPress - Assignments',
			'slug'        => 'learnpress-assignments',
			'premium'     => true,
			'required'    => false,
			'description' => 'Great way to assign tasks, essays for your students. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),
		array(
			'name'        => 'LearnPress - Announcements',
			'slug'        => 'learnpress-announcements',
			'premium'     => true,
			'required'    => false,
			'description' => 'Announcement is a great way to promote your courses and update new features + contents to your courses, including email notification. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),
		array(
			'name'        => 'Paid Memberships Pro',
			'slug'        => 'paid-memberships-pro',
			'required'    => false,
			'no-install'  => true,
			'premium'     => true,
			'description' => 'The most complete member management and membership subscriptions plugin for WordPress. <cite>By <a href="http://www.paidmembershipspro.com/">Paid Memberships Pro</a>.</cite>',
			'source'      => 'https://license.paidmembershipspro.com/downloads/free/paid-memberships-pro.zip',
		),
		array(
			'name'        => 'Interactive Content – H5P',
			'slug'        => 'h5p',
			'required'    => false,
			'description' => 'Allows you to upload, create, share and use rich interactive content on your WordPress site. <cite>By <a href="http://joubel.com/">Joubel</a>.</cite>',
		),
		array(
			'name'        => 'Paid Memberships Pro Add-On for LearnPress',
			'slug'        => 'learnpress-paid-membership-pro',
			'premium'     => true,
			'required'    => false,
			'description' => 'Paid Membership Pro add-on for LearnPress. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'BuddyPress',
			'slug'        => 'buddypress',
			'required'    => false,
			'description' => 'BuddyPress adds community features to WordPress. Member Profiles, Activity Streams, Direct Messaging, Notifications, and more! <cite>By <a href="http://buddypress.org/">The BuddyPress Community</a>.</cite>',
		),

		array(
			'name'        => 'bbPress',
			'slug'        => 'bbpress',
			'required'    => false,
			'description' => 'bbPress is forum software with a twist from the creators of WordPress. <cite>By <a href="http://bbpress.org/">The bbPress Contributors</a>.</cite>',
		),

		array(
			'name'        => 'LearnPress – Course Review',
			'slug'        => 'learnpress-course-review',
			'required'    => false,
			'description' => 'Adding review for course. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'LearnPress – Prerequisites Courses',
			'slug'        => 'learnpress-prerequisites-courses',
			'required'    => false,
			'description' => 'Course you have to finish before you can enroll to this course. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'LearnPress – Export Import',
			'slug'        => 'learnpress-import-export',
			'required'    => false,
			'description' => 'Export and Import your courses with all lesson and quiz in easiest way. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'LearnPress – BuddyPress Integration',
			'slug'        => 'learnpress-buddypress',
			'required'    => false,
			'description' => 'Using the profile system provided by BuddyPress. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'H5P Add-On for LearnPress',
			'slug'        => 'learnpress-h5p',
			'premium'     => true,
			'required'    => false,
			'description' => 'H5P Content add-on for LearnPress. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Authorize.Net Add-On for LearnPress',
			'slug'        => 'learnpress-authorizenet-payment',
			'premium'     => true,
			'required'    => false,
			'description' => 'Authorize.Net payment gateway for LearnPress. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Coming Soon Add-On for LearnPress',
			'slug'        => 'learnpress-coming-soon-courses',
			'premium'     => true,
			'required'    => false,
			'description' => 'Set a course is “Coming Soon” and schedule to public. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'myCRED Add-On for LearnPress',
			'slug'        => 'learnpress-mycred',
			'premium'     => true,
			'required'    => false,
			'description' => 'Running with the point management system – myCRED. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Student List Add-On for LearnPress',
			'slug'        => 'learnpress-students-list',
			'premium'     => true,
			'required'    => false,
			'description' => 'Students list for LearnPress. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'LearnPress – Course Wishlist',
			'slug'        => 'learnpress-wishlist',
			'required'    => false,
			'description' => 'Wishlist feature. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'LearnPress – bbPress Integration',
			'slug'        => 'learnpress-bbpress',
			'required'    => false,
			'description' => 'Using the forum for courses provided by bbPress. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Learnpress Instamojo',
			'slug'        => 'learnpress-instamojo-payment',
			'required'    => false,
			'premium'     => true,
			'description' => 'Instamojo payment gateway for LearnPress. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Learnpress Razorpay',
			'slug'        => 'learnpress-razorpay-payment',
			'required'    => false,
			'premium'     => true,
			'add-on'      => true,
			'description' => 'Razorpay payment gateway for LearnPress <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
		),
		array(
			'name'        => 'WP Events Manager',
			'slug'        => 'wp-events-manager',
			'required'    => false,
			'description' => 'A complete plugin for Events management and online booking system <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
		),
		array(
			'name'        => '2Checkout Add-On for LearnPress',
			'slug'        => 'learnpress-2checkout-payment',
			'premium'     => true,
			'required'    => false,
			'description' => '2checkout payment gateway for LearnPress. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),
		array(
			'name'        => 'WP Events Manager - WooCommerce Payment ',
			'slug'        => 'wp-events-manager-woocommerce-payment-methods-integration',
			'required'    => false,
			'description' => 'Support paying for a booking with the payment methods provided by Woocommerce <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
			'add-on'      => true,
		),

		array(
			'name'        => 'Instagram Feed',
			'slug'        => 'instagram-feed',
			'no-install'  => true,
			'required'    => false,
			'description' => 'Display beautifully clean, customizable, and responsive Instagram feeds. <cite>By <a href="http://smashballoon.com/">Smash Balloon</a>.</cite>',
		),

		array(
			'name'        => 'Contact Form 7',
			'slug'        => 'contact-form-7',
			'required'    => false,
			'description' => 'Just another contact form plugin. Simple but flexible',
		),

		array(
			'name'        => 'MailChimp for WordPress',
			'slug'        => 'mailchimp-for-wp',
			'required'    => false,
			'description' => 'Mailchimp for WordPress by ibericode. Adds various highly effective sign-up methods to your site. <cite>By <a href="http://ibericode.com/">ibericode</a>.</cite>',
		),

		array(
			'name'        => 'Loco Translate',
			'slug'        => 'loco-translate',
			'required'    => false,
			'silent'      => true,
			'no-install'  => true,
			'description' => 'Translate themes and plugins directly in WordPress <cite>By <a href="http://localise.biz/wordpress/plugin/">Tim Whitlock</a>.</cite>',
		),

		array(
			'name'        => 'Thim Portfolio',
			'slug'        => 'tp-portfolio',
			'premium'     => true,
			'required'    => false,
			'description' => 'A plugin that allows you to show off your portfolio. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
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
			'name'        => 'Thim Elementor Kit',
			'slug'        => 'thim-elementor-kit',
			// 'premium'     => true,
			'no-install'  => true,
			'required'    => false,
			'description' => 'It is page builder for the Elementor page builder. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
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
			'description' => 'Add some features to WooCommerce plugin. <cite>By <a href="http://thimpress.com/">ThimPress</a>.</cite>',
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
		$extra_plugin[] = array(
			'name'        => 'WPBakery Page Builder',
			'slug'        => 'js_composer',
			'premium'     => true,
			'required'    => false,
			'description' => 'Drag and drop page builder for WordPress. Take full control over your WordPress site, build any layout you can imagine – no programming knowledge required. <cite>By <a href="http://wpbakery.com/">Michael M - WPBakery.com</a>.</cite>',
			'icon'        => 'https://s3.envato.com/files/260579516/wpb-logo.png',
		);
	}

	if ( class_exists( 'RevSlider' ) ) {
		$extra_plugin[] = array(
			'name'        => 'Revolution Slider',
			'slug'        => 'revslider',
			'premium'     => true,
			'required'    => false,
			'no-install'  => true,
			'description' => 'Slider Revolution – More than just a WordPress Slider <cite>By <a href="http://themepunch.com/">ThemePunch</a>.</cite>',
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
