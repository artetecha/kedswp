<?php

/**
 * Panel 404
 *
 * @package Eduma
 */

thim_customizer()->add_section(
	array(
		'id'       => '404_page',
		'panel'    => 'general',
		'priority' => 150,
		'title'    => esc_html__( '404 Page', 'eduma' ),
	)
);
thim_customizer()->add_field(
	array(
		'id'       => 'show_title_404',
		'type'     => 'switch',
		'label'    => esc_html__( 'Header & Footer', 'eduma' ),
		'tooltip'  => esc_html__( 'Show/Hide Header & Footer, Top heading banner.', 'eduma' ),
		'section'  => '404_page',
		'default'  => true,
		'priority' => 9,
		'choices'  => array(
			'on'  => esc_html__( 'Show', 'eduma' ),
			'off' => esc_html__( 'Hide', 'eduma' ),
		),
	)
);
thim_customizer()->add_field(
	array(
		'type'            => 'text',
		'id'              => 'thim_single_404_page_title',
		'label'           => esc_html__( 'Custom title', 'eduma' ),
		'tooltip'         => esc_html__( 'Allows you can setup custom title.', 'eduma' ),
		'section'         => '404_page',
		'priority'        => 10,
		'active_callback' => array(
			array(
				'setting'  => 'show_title_404',
				'operator' => '===',
				'value'    => true,
			),
		),
	)
);
thim_customizer()->add_field(
	array(
		'type'            => 'text',
		'id'              => 'thim_single_404_sub_title',
		'label'           => esc_html__( 'Sub Heading', 'eduma' ),
		'tooltip'         => esc_html__( 'Allows you can setup sub heading.', 'eduma' ),
		'section'         => '404_page',
		'priority'        => 11,
		'active_callback' => array(
			array(
				'setting'  => 'show_title_404',
				'operator' => '===',
				'value'    => true,
			),
		),
	)
);
// Page Title Background Color
thim_customizer()->add_field(
	array(
		'id'              => 'thim_single_404_bg_color',
		'type'            => 'color',
		'label'           => esc_html__( 'Background Color', 'eduma' ),
		'tooltip'         => esc_html__( 'If you do not use background image, then can use background color for page title on heading top. ', 'eduma' ),
		'section'         => '404_page',
		'default'         => 'rgba(0,0,0,0.5)',
		'priority'        => 12,
		'choices'         => array( 'alpha' => true ),
		'transport'       => 'postMessage',
		'js_vars'         => array(
			array(
				'choice'   => 'color',
				'element'  => '.top_site_main>.overlay-top-header',
				'property' => 'background',
			),
		),
		'active_callback' => array(
			array(
				'setting'  => 'show_title_404',
				'operator' => '===',
				'value'    => true,
			),
		),
	)
);
thim_customizer()->add_field(
	array(
		'id'              => 'thim_single_404_title_color',
		'type'            => 'color',
		'label'           => esc_html__( 'Title Color', 'eduma' ),
		'tooltip'         => esc_html__( 'Allows you can select a color make text color for title.', 'eduma' ),
		'section'         => '404_page',
		'default'         => '#ffffff',
		'priority'        => 13,
		'choices'         => array( 'alpha' => true ),
		'transport'       => 'postMessage',
		'js_vars'         => array(
			array(
				'choice'   => 'color',
				'element'  => '.top_site_main h1, .top_site_main h2',
				'property' => 'color',
			),
		),
		'active_callback' => array(
			array(
				'setting'  => 'show_title_404',
				'operator' => '===',
				'value'    => true,
			),
		),
	)
);
thim_customizer()->add_field(
	array(
		'id'              => 'thim_single_404_sub_title_color',
		'type'            => 'color',
		'label'           => esc_html__( 'Sub Title Color', 'eduma' ),
		'tooltip'         => esc_html__( 'Allows you can select a color make sub title color page title.', 'eduma' ),
		'section'         => '404_page',
		'default'         => '#999',
		'priority'        => 14,
		'choices'         => array( 'alpha' => true ),
		'transport'       => 'postMessage',
		'js_vars'         => array(
			array(
				'choice'   => 'color',
				'element'  => '.top_site_main .banner-description',
				'property' => 'color',
			),
		),
		'active_callback' => array(
			array(
				'setting'  => 'show_title_404',
				'operator' => '===',
				'value'    => true,
			),
		),
	)
);
thim_customizer()->add_field(
	array(
		'type'            => 'image',
		'id'              => 'thim_single_404_top_image',
		'label'           => esc_html__( 'Background Heading', 'eduma' ),
		'priority'        => 15,
		'transport'       => 'postMessage',
		'section'         => '404_page',
		'default'         => THIM_URI . 'images/bg-page.jpg',
		'active_callback' => array(
			array(
				'setting'  => 'show_title_404',
				'operator' => '===',
				'value'    => true,
			),
		),
	)
);

thim_customizer()->add_field(
	array(
		'type'      => 'image',
		'id'        => 'thim_single_404_left',
		'label'     => esc_html__( 'Image Left', 'eduma' ),
		'priority'  => 29,
		'transport' => 'postMessage',
		'section'   => '404_page',
		'default'   => THIM_URI . 'images/image-404.jpg',
	)
);
thim_customizer()->add_field(
	array(
		'type'        => 'text',
		'id'          => 'thim_single_404_title',
		'label'       => esc_html__( 'Title', 'eduma' ),
		'tooltip'     => esc_html__( 'Allows you can setup  title.', 'eduma' ),
		'description' => esc_html__( 'If you use this {{something}} format it will change the title color, ex: 404 {{Error!}}', 'eduma' ),
		'section'     => '404_page',
		'priority'    => 31,
	)
);
thim_customizer()->add_field(
	array(
		'type'     => 'textarea',
		'id'       => 'thim_single_404_content',
		'label'    => esc_html__( 'Content', 'eduma' ),
		'tooltip'  => esc_html__( 'Allows you can setup sub heading.', 'eduma' ),
		'section'  => '404_page',
		'priority' => 32,
	)
);
thim_customizer()->add_field(
	array(
		'id'       => 'show_back_home_404',
		'type'     => 'switch',
		'label'    => esc_html__( 'Show Button Home Page', 'eduma' ),
		'section'  => '404_page',
		'default'  => false,
		'priority' => 34,
		'choices'  => array(
			'on'  => esc_html__( 'Yes', 'eduma' ),
			'off' => esc_html__( 'No', 'eduma' ),
		),
	)
);
thim_customizer()->add_field(
	array(
		'type'            => 'text',
		'id'              => 'thim_single_404_button_title',
		'label'           => esc_html__( 'Button Title', 'eduma' ),
		'tooltip'         => esc_html__( 'Allows you can setup custom button title.', 'eduma' ),
		'section'         => '404_page',
		'priority'        => 40,
		'active_callback' => array(
			array(
				'setting'  => 'show_back_home_404',
				'operator' => '===',
				'value'    => true,
			),
		),
	)
);
