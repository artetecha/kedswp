<?php
$thumnnail_url = 'https://thimpresswp.github.io/demo-data/eduma/images/';
$plugin_demo   = array(
	'learnpress',
	'mailchimp-for-wp',
	'contact-form-7',
	'woocommerce',
	'wp-events-manager',
	'tp-portfolio',
	'learnpress-course-review',
	'learnpress-wishlist',
	'elementor',
	'thim-elementor-kit',
);

$demo_datas = array(
	'demo-el/demo-main'   => array(
		'title'            => esc_html__( 'Demo Main', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-main/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-main-2026.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo,
		),
	),
	'demo-el/demo-online-learning'   => array(
		'title'            => esc_html__( 'Demo Online Learning', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-online-learning/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-online-learning.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo,
		),
	),
	'demo-el/demo-yoga'              => array(
		'title'            => esc_html__( 'Demo Yoga', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-yoga/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-yoga.jpg' ),
		'plugins_required' => array_merge(
			array(
				'woo-booster-toolkit',
				'learnpress-collections',
			),
			$plugin_demo,
		),
	),
	'demo-el/demo-marketplace'       => array(
		'title'            => esc_html__( 'Demo MarketPlace', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-marketplace/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-marketplace.jpg' ),
		'plugins_required' => $plugin_demo,
	),
	'demo-el/demo-learning-platform' => array(
		'title'            => esc_html__( 'Demo Learning Platform', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-learning-platform/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-learning-platform.jpg' ),
		'plugins_required' => $plugin_demo,
	),
	'demo-el/demo-preschool'         => array(
		'title'            => esc_html__( 'Demo Preschool', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-preschool/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-preschool.jpg' ),
		'plugins_required' => $plugin_demo,
	),
	'demo-el/demo-education-news'    => array(
		'title'            => esc_html__( 'Demo Education News', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-education-news/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-education-news.jpg' ),
		'plugins_required' => array(
			'mailchimp-for-wp',
			'contact-form-7',
			'woocommerce',
			'woo-booster-toolkit',
			'wp-events-manager',
			'elementor',
			'thim-elementor-kit',
		),
	),
	'demo-el/demo-ecommerce'         => array(
		'title'            => esc_html__( 'Demo Ecommerce', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-ecommerce/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-ecommerce.jpg?v=1' ),
		'plugins_required' => array(
			'mailchimp-for-wp',
			'contact-form-7',
			'woocommerce',
			'woo-booster-toolkit',
			'elementor',
			'thim-elementor-kit',
		),
	),
	'demo-el/demo-legacy'            => array(
		'title'            => esc_html__( 'Demo Legacy', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-legacy/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-legacy.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo,
		),
	),
	'demo-el/demo-global-university'            => array(
		'title'            => esc_html__( 'Demo Global Univeristy', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-global-university/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-global-university.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo,
		),
	),
	'demo-el/demo-classic'           => array(
		'title'            => esc_html__( 'Demo Classic', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-classic/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-main.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo,
		),
	),
	'demo-el/demo-elegant'           => array(
		'title'            => esc_html__( 'Demo Elegant', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-elegant/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-elegant.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo
		),
	),
	'demo-el/demo-coursera'          => array(
		'title'            => esc_html__( 'Demo Coursera', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-coursera/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-coursera.jpg' ),
		'plugins_required' => $plugin_demo,
	),
	'demo-el/demo-online-school'     => array(
		'title'            => esc_html__( 'Demo Online School', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-online-school/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-online-school.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo,
		),
	),
	'demo-el/demo-ivy-league'        => array(
		'title'            => esc_html__( 'Demo Ivy League', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-ivy-league',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-ivy-league.jpg' ),
		'plugins_required' => $plugin_demo,
	),
	'demo-el/demo-udemy'             => array(
		'title'            => esc_html__( 'Demo Udemy', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-udemy/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-udemy.jpg' ),
		'plugins_required' => $plugin_demo,
	),
	'demo-el/demo-stanford'          => array(
		'title'            => esc_html__( 'Demo Stanford', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-stanford',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-stanford.jpg' ),
		'plugins_required' => $plugin_demo,
	),
	'demo-el/demo-grad-school'       => array(
		'title'            => esc_html__( 'Demo Grad School', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-grad-school/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-grad-school.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
			),
			$plugin_demo,
		),
	),
	'demo-el/demo-edtech'            => array(
		'title'            => esc_html__( 'Demo Edtech', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-edtech/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-edtech.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo,
		),
	),
	'demo-el/demo-courses-hub'       => array(
		'title'            => esc_html__( 'Demo Courses Hub', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-courses-hub/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-courses-hub.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
			),
			$plugin_demo
		),
	),
	'demo-el/demo-university'        => array(
		'title'            => esc_html__( 'Demo University', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-university/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-university.jpg' ),
		'plugins_required' => $plugin_demo,
	),
	'demo-el/demo-university-home'   => array(
		'title'            => esc_html__( 'Demo University - New', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/university/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-university-home.jpg' ),
		'plugins_required' => array(
			'mailchimp-for-wp',
			'contact-form-7',
			'wp-events-manager',
			'tp-portfolio',
			'elementor',
			'thim-elementor-kit',
		),
	),

	'demo-el/demo-university-home-1' => array(
		'title'            => esc_html__( 'Demo University - Home 1', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/university/home-1/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-university-home-1.jpg' ),
		'plugins_required' => array(
			'mailchimp-for-wp',
			'contact-form-7',
			'wp-events-manager',
			'tp-portfolio',
			'elementor',
			'thim-elementor-kit',
		),
	),
	'demo-el/demo-university-home-2' => array(
		'title'            => esc_html__( 'Demo University - Home 2', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/university/home-2/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-university-home-2.jpg' ),
		'plugins_required' => array(
			'mailchimp-for-wp',
			'contact-form-7',
			'wp-events-manager',
			'tp-portfolio',
			'elementor',
			'thim-elementor-kit',
		),
	),
	'demo-el/demo-university-home-3' => array(
		'title'            => esc_html__( 'Demo University - Home 3', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/university/home-3/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-university-home-3.jpg' ),
		'plugins_required' => array(
			'mailchimp-for-wp',
			'contact-form-7',
			'wp-events-manager',
			'tp-portfolio',
			'elementor',
			'thim-elementor-kit',
		),
	),
	'demo-el/demo-tech-camp'         => array(
		'title'            => esc_html__( 'Demo Tech Camp', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-tech-camp/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-techcamp.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
			),
			$plugin_demo
		),
	),

	'demo-el/demo-languages-school'  => array(
		'title'            => esc_html__( 'Demo Languages School', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-languages-school/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-languages-school.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo
		),
	),
	'demo-el/demo-tutor-lms'         => array(
		'title'            => esc_html__( 'Demo Tutor lms', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-tutor-lms',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-tutor-lms.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo
		),
	),
	'demo-el/demo-rtl'               => array(
		'title'            => esc_html__( 'Demo RTL', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-rtl',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-rtl.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo
		),
	),
	'demo-el/demo-one-instructor'    => array(
		'title'            => esc_html__( 'Demo One Instructor', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-one-instructor/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-one-instructor.jpg' ),
		'plugins_required' => $plugin_demo,
	),
	'demo-el/demo-restaurant'        => array(
		'title'            => esc_html__( 'Demo Restaurant', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-restaurant/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-restaurant.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo
		),
	),
	'demo-el/demo-kindergarten'      => array(
		'title'            => esc_html__( 'Demo Kindergarten - Offline', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-kindergarten/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-kindergarten.jpg' ),
		'plugins_required' => $plugin_demo,
	),
	'demo-el/demo-react'             => array(
		'title'            => esc_html__( 'Demo React', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-react/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-react.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-co-instructor',
			),
			$plugin_demo,
		),
	),
	'demo-el/demo-modern-university' => array(
		'title'            => esc_html__( 'Demo Modern University', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-modern-university',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-modern-university.jpg' ),
		'plugins_required' => $plugin_demo,
	),
	'demo-el/demo-one-course'        => array(
		'title'            => esc_html__( 'Demo One Course', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-one-course/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-one-course.jpg' ),
		'plugins_required' => $plugin_demo,
	),
	'demo-el/demo-kid-art'           => array(
		'title'            => esc_html__( 'Demo Kid Art', 'eduma' ) . ' - Offline',
		'demo_url'         => 'https://eduma.thimpress.com/demo-kid-art/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-kid-art.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',

			),
			$plugin_demo,
		),
	),
	'demo-el/demo-new-art'           => array(
		'title'            => esc_html__( 'Demo New Art', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-new-art/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-new-art.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
			),
			$plugin_demo,
		),
	),
	'demo-el/demo-crypto'            => array(
		'title'            => esc_html__( 'Demo Crypto', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-crypto/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-crypto.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
			),
			$plugin_demo
		),
	),
	'demo-el/demo-instructor'        => array(
		'title'            => esc_html__( 'Demo New Instructor', 'eduma' ),
		'demo_url'         => 'https://eduma.thimpress.com/demo-instructor/',
		'thumbnail_url'    => esc_url( $thumnnail_url . 'demo-instructor.jpg' ),
		'plugins_required' => array_merge(
			array(
				'learnpress-collections',
				'learnpress-upsell',
			),
			$plugin_demo,
		),
	),
);

return $demo_datas;
