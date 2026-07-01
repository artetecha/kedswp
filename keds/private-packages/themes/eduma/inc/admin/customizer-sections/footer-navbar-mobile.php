<?php
/**
 * Section Nav Mobile
 *
 * @package Eduma
 */
thim_customizer()->add_section(
	array(
		'id'       => 'nav_bar_mobile',
		'priority' => 60,
		'title'    => esc_html__( 'Mobile Footer Navbar', 'eduma' ),
		'icon'     => 'dashicons-align-right',
	)
);
// Enable or disable top bar
thim_customizer()->add_field(
	array(
		'id'       => 'navbar_mobile_show',
		'type'     => 'switch',
		'label'    => esc_html__( 'Enable', 'eduma' ),
		'section'  => 'nav_bar_mobile',
		'default'  => false,
		'priority' => 10,
		'choices'  => array(
			'on'  => esc_html__( 'Yes', 'eduma' ),
			'off' => esc_html__( 'No', 'eduma' ),
		),
	)
);
$item_mobile = apply_filters(
	'thim_item_nav_mobile_footer',
	array(
		'home'     => esc_html__( 'Home', 'eduma' ),
		'account'  => esc_html__( 'Account', 'eduma' ),
		'articles' => esc_html__( 'Articles', 'eduma' ),
		'search'   => esc_html__( 'Search', 'eduma' ),
	)
);

if ( class_exists( 'WooCommerce' ) ) {
	$item_mobile['shop'] = esc_html__( 'Shop', 'eduma' );
	$item_mobile['cart'] = esc_html__( 'Cart', 'eduma' );
}

if ( class_exists( 'LearnPress' ) ) {
	$item_mobile['course'] = esc_html__( 'Course', 'eduma' );
}


thim_customizer()->add_field(
	array(
		'id'              => 'nav_mobile_item',
		'type'            => 'sortable',
		'label'           => esc_html__( 'Nav Item', 'eduma' ),
		'section'         => 'nav_bar_mobile',
		'priority'        => 10,
		'default'         => array(
			'home',
			'course',
			'search',
			'account',
		),
		'choices'         => $item_mobile,
		'active_callback' => array(
			array(
				'setting'  => 'navbar_mobile_show',
				'operator' => '===',
				'value'    => true,
			),
		),
	)
);

thim_customizer()->add_field(
	array(
		'type'            => 'select',
		'id'              => 'nav_mobile_search',
		'section'         => 'nav_bar_mobile',
		'priority'        => 10,
		'label'           => esc_html__( 'Search Options', 'eduma' ),
		'default'         => 'course',
		'choices'         => array(
			'course' => esc_html__( 'Search Course - Popup', 'eduma' ),
			'page'   => esc_html__( 'Page Search', 'eduma' ),
		),
		'active_callback' => array(
			array(
				'setting'  => 'navbar_mobile_show',
				'operator' => '===',
				'value'    => true,
			),
		),
	)
);
// Footer Text Color
thim_customizer()->add_field(
	array(
		'type'            => 'multicolor',
		'id'              => 'nav_mobile_color',
		'label'           => esc_html__( 'Colors Setting', 'eduma' ),
		'section'         => 'nav_bar_mobile',
		'priority'        => 20,
		'choices'         => array(
			'background' => esc_html__( 'Background', 'eduma' ),
			'text'       => esc_html__( 'Text', 'eduma' ),
			'hover'      => esc_html__( 'Hover & Active', 'eduma' ),
		),
		'default'         => array(
			'background' => '#ffffff',
			'text'       => '#333',
			'hover'      => '#ffb606',
		),
		'transport'       => 'postMessage',
		'active_callback' => array(
			array(
				'setting'  => 'navbar_mobile_show',
				'operator' => '===',
				'value'    => true,
			),
		),
	)
);

// Enable or disable top bar
thim_customizer()->add_field(
	array(
		'id'              => 'captcha_form_login',
		'type'            => 'switch',
		'label'           => esc_html__( 'Login Popup Form options', 'eduma' ),
		'description'     => esc_html__( 'Show Captcha', 'eduma' ),
		'section'         => 'nav_bar_mobile',
		'default'         => false,
		'priority'        => 20,
		'choices'         => array(
			true  => esc_html__( 'Yes', 'eduma' ),
			false => esc_html__( 'No', 'eduma' ),
		),
		'active_callback' => array(
			array(
				'setting'  => 'navbar_mobile_show',
				'operator' => '===',
				'value'    => true,
			),
		),
	)
);
thim_customizer()->add_field(
	array(
		'type'            => 'text',
		'id'              => 'terms_form_login',
		'label'           => '',
		'description'     => esc_html__( 'Terms of Service link', 'eduma' ),
		'section'         => 'nav_bar_mobile',
		'priority'        => 20,
		'active_callback' => array(
			array(
				'setting'  => 'navbar_mobile_show',
				'operator' => '===',
				'value'    => true,
			),
		),
	)
);
