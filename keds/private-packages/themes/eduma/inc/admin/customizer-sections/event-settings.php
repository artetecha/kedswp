<?php
/**
 * Section Settings
 *
 * @package Eduma
 */

thim_customizer()->add_section(
	array(
		'id'       => 'event_setting',
		'panel'    => 'event',
		'title'    => esc_html__( 'Settings', 'eduma' ),
		'priority' => 20,
	)
);

thim_customizer()->add_field(
	array(
		'id'       => 'thim_tab_event_style',
		'type'     => 'select',
		'label'    => esc_html__( 'Tab Style', 'eduma' ),
		'priority' => 10,
		'default'  => '',
		'multiple' => 0,
		'section'  => 'event_setting',
		'choices'  => array(
			''        => esc_html__( 'Default', 'eduma' ),
			'style_1' => esc_html__( 'Style 1', 'eduma' ),
		),
	)
);

thim_customizer()->add_field(
	array(
		'id'       => 'thim_event_change_order_tab',
		'type'     => 'sortable',
		'label'    => esc_html__( 'Change Order Tab', 'eduma' ),
		'tooltip'  => esc_html__( 'Allows you can show/hide and change the order of the tabs', 'eduma' ),
		'section'  => 'event_setting',
		'default'  => array(
			'happening',
			'upcoming',
			'expired',
		),
		'priority' => 10,
		'choices'  => array(
			'happening' => esc_html__( 'Happening', 'eduma' ),
			'upcoming'  => esc_html__( 'Upcoming', 'eduma' ),
			'expired'   => esc_html__( 'Expired', 'eduma' ),
		),
	)
);

thim_customizer()->add_field(
	array(
		'id'       => 'thim_tab_event_layout',
		'type'     => 'select',
		'label'    => esc_html__( 'Layout', 'eduma' ),
		'priority' => 15,
		'default'  => '',
		'multiple' => 0,
		'section'  => 'event_setting',
		'choices'  => array(
			''     => esc_html__( 'List', 'eduma' ),
			'grid' => esc_html__( 'Grid', 'eduma' ),
		),
	)
);

thim_customizer()->add_field(
	array(
		'type'     => 'number',
		'id'       => 'thim_event_limit_post',
		'label'    => esc_html__( 'Per page posts', 'eduma' ),
		'default'  => 6,
		'section'  => 'event_setting',
		'priority' => 20,
	)
);

thim_customizer()->add_field(
	array(
		'id'       => 'thim_event_display_year',
		'type'     => 'switch',
		'label'    => esc_html__( 'Show Year', 'eduma' ),
		'tooltip'  => esc_html__( 'Show year on date of all place display events.', 'eduma' ),
		'section'  => 'event_setting',
		'default'  => false,
		'priority' => 25,
	)
);

thim_customizer()->add_field(
	array(
		'id'       => 'thim_event_button_view_detail_event',
		'type'     => 'switch',
		'label'    => esc_html__( 'View Detail', 'eduma' ),
		'tooltip'  => esc_html__( 'Show button view detail single event.', 'eduma' ),
		'section'  => 'event_setting',
		'default'  => false,
		'priority' => 30,
	)
);

// Enable or disable countdown
thim_customizer()->add_field(
	array(
		'id'       => 'thim_event_countdown_archive_event',
		'type'     => 'switch',
		'label'    => esc_html__( 'Countdown', 'eduma' ),
		'tooltip'  => esc_html__( 'Show countdown in archive event.', 'eduma' ),
		'section'  => 'event_setting',
		'default'  => false,
		'priority' => 35,
	)
);
