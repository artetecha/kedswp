<?php
/**
 * Section Header Layout
 *
 * @package Eduma
 */

thim_customizer()->add_section(
	array(
		'id'       => 'header_layout',
		'title'    => esc_html__( 'Layouts', 'eduma' ),
		'panel'    => 'header',
		'priority' => 20,
	)
);
/*
thim_customizer()->add_field(
	array(
		'id'          => 'header_builder',       // ← setting ID = groupKey
		'type'        => 'thim-builder',
		'section'     => 'header_layout',

		// Default layout
		'default'     => array(
			'desktop' => array(
				'top'    => array(
					'left'   => array(),
					'center' => array(),
					'right'  => array(),
				),
				'main'   => array(
					'left'   => array( 'logo' ),
					'center' => array(),
					'right'  => array( 'navigation' ),
				),
				'bottom' => array(
					'left'   => array(),
					'center' => array(),
					'right'  => array(),
				),
			),
			'mobile'  => array(
				'main' => array(
					'left'   => array( 'logo' ),
					'center' => array(),
					'right'  => array(),
				),
			),
		),

		// Available items — key là ID, value là metadata
		'choices'     => array(
			'logo'       => array(
				'name'    => 'Logo',
				'section' => 'title_tagline',
			),
			'navigation' => array(
				'name'    => 'Primary Navigation',
				'section' => 'nav_menus_created_posts',
			),
			'search'     => array(
				'name'    => 'Search',
				'section' => 'header_search',
			),
			'cart'       => array(
				'name'    => 'Cart',
				'section' => 'header_cart',
			),
			// Widget sidebar: section ID = 'sidebar-widgets-{sidebar_id}'
			'menu_right' => array(
				'name'    => 'Menu Right',
				'section' => 'sidebar-widgets-menu_right',
			),
		),

		'input_attrs' => array(
			'group'           => 'header_builder',       // phải trùng với 'id'
			'devices'         => array( 'desktop', 'tablet' ),

			'rows'            => array(
				'desktop' => array( 'top', 'main', 'bottom' ),
				'tablet'  => array( 'top', 'main', 'bottom' ),
			),

			'zones'           => array(
				'desktop' => array(
					'top'    => array( 'left', 'center', 'right' ),
					'main'   => array( 'left', 'center', 'right' ),
					'bottom' => array( 'left', 'center', 'right' ),
				),
				'tablet'  => array(
					'top'    => array( 'left', 'center', 'right' ),
					'main'   => array( 'left', 'center', 'right' ),
					'bottom' => array( 'left', 'center', 'right' ),
				),
			),

			// (Tuỳ chọn) Nhãn cho row
			'row_labels'      => array(
				'desktop' => array(
					'top'    => 'Top Row',
					'main'   => 'Main Row',
					'bottom' => 'Bottom Row',
				),
			),

			// (Tuỳ chọn) Nhãn cho từng zone
			'zone_labels'     => array(
				'desktop' => array(
					'main' => array(
						'left'   => 'Left',
						'center' => 'Center',
						'right'  => 'Right',
					),
				),
			),

			// Section IDs ẩn khỏi sidebar panel list (chỉ accessible qua gear icon)
			'hidden_sections' => array( 'main', 'top', 'bottom', 'header_search' ),
		),
	)
);
*/


thim_customizer()->add_field(
	array(
		'id'            => 'thim_desc_header_tpl',
		'type'          => 'tp_notice',
		'description'   => sprintf( __( 'This header is built by Thim Elementor Kit, you can edit and configure it in %s.', 'eduma' ), '<a href="' . admin_url( 'edit.php?post_type=thim_elementor_kit&thim_elementor_type=header' ) . '" target="_blank">' . __( 'Thim Elementor Kit', 'eduma' ) . '</a>' ),
		'section'       => 'header_layout',
		'priority'      => 11,
		'wrapper_attrs' => array(
			'class' => '{default_class} hide' . thim_customizer_extral_class( 'header' ),
		),
	)
);
// Select Header Layout
thim_customizer()->add_field(
	array(
		'id'            => 'thim_header_style',
		'type'          => 'radio-image',
		'label'         => esc_html__( 'Layout', 'eduma' ),
		'tooltip'       => esc_html__( 'Allows you can select header layout for header on your site. ', 'eduma' ),
		'section'       => 'header_layout',
		'default'       => 'header_v1',
		'priority'      => 10,
		'choices'       => apply_filters(
			'thim_header_layouts',
			array(
				'header_v1' => THIM_URI . 'images/header/header_v1_thumb.jpg',
				'header_v2' => THIM_URI . 'images/header/header_v2_thumb.jpg',
				'header_v3' => THIM_URI . 'images/header/header_v3_thumb.jpg',
				'header_v4' => THIM_URI . 'images/header/header_v4_thumb.jpg',
				'header_v5' => THIM_URI . 'images/header/header_v5_thumb.jpg',
			)
		),
		'wrapper_attrs' => array(
			'class' => '{default_class} thim-col-3' . thim_customizer_extral_class( 'header' ),
		),
	)
);

// Select Header Size
thim_customizer()->add_field(
	array(
		'id'            => 'thim_header_size',
		'type'          => 'select',
		'label'         => esc_html__( 'Size', 'eduma' ),
		'tooltip'       => esc_html__( 'Allows you can select size layout for header layout. ', 'eduma' ),
		'section'       => 'header_layout',
		'priority'      => 15,
		'multiple'      => 0,
		'default'       => 'default',
		'choices'       => array(
			'default'    => esc_html__( 'Default', 'eduma' ),
			'full_width' => esc_html__( 'Full width', 'eduma' ),
		),
		'wrapper_attrs' => array(
			'class' => '{default_class}' . thim_customizer_extral_class( 'header' ),
		),
	)
);

// Select Header Position
thim_customizer()->add_field(
	array(
		'id'            => 'thim_header_position',
		'type'          => 'select',
		'label'         => esc_html__( 'Position', 'eduma' ),
		'tooltip'       => esc_html__( 'Allows you can select position layout for header layout. ', 'eduma' ),
		'section'       => 'header_layout',
		'priority'      => 20,
		'multiple'      => 0,
		'default'       => 'header_overlay',
		'choices'       => array(
			'header_default' => esc_html__( 'Default', 'eduma' ),
			'header_overlay' => esc_html__( 'Overlay', 'eduma' ),
		),
		'wrapper_attrs' => array(
			'class' => '{default_class}' . thim_customizer_extral_class( 'header' ),
		),
	)
);

thim_customizer()->add_field(
	array(
		'type'            => 'select',
		'id'              => 'thim_line_active_item_menu',
		'label'           => esc_html__( 'Line Active Item', 'eduma' ),
		'default'         => 'bottom',
		'priority'        => 13,
		'multiple'        => 0,
		'section'         => 'header_main_menu',
		'choices'         => array(
			'noline' => esc_html__( 'No line', 'eduma' ),
			'top'    => esc_html__( 'Top', 'eduma' ),
			'bottom' => esc_html__( 'Bottom', 'eduma' ),
		),
		'active_callback' => array(
			array(
				'setting'  => 'thim_header_style',
				'operator' => '!=',
				'value'    => 'header_v4',
			),
		),
		'wrapper_attrs'   => array(
			'class' => '{default_class}' . thim_customizer_extral_class( 'header' ),
		),
	)
);
