<?php

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;
use Elementor\Group_Control_Border;

// Fields tab content
$content_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'content',
		esc_html__( 'Content', 'learnpress-upsell' ),
		Controls_Manager::TAB_CONTENT,
		[
			'layout'   => LPElementorControls::add_control_type_select(
				'layout',
				esc_html__( 'Layout', 'learnpress-upsell' ),
				[
					'base'   => esc_html__( 'Default', 'learnpress-upsell' ),
					'slider' => esc_html__( 'Slider', 'learnpress-upsell' ),
				],
				'base'
			),
			'column'   => LPElementorControls::add_control_type(
				'column',
				esc_html__( 'Column', 'learnpress-upsell' ),
				3,
				Controls_Manager::NUMBER,
				[
					'min'       => 1,
					'max'       => 5,
					'condition' => [
						'layout' => 'base',
					],
					'selectors' => array(
						'{{WRAPPER}}' => '--lp-packages-columns: repeat({{VALUE}}, 1fr)',
					),
				]
			),
			'sort_in'  => LPElementorControls::add_control_type_select(
				'sort_in',
				esc_html__( 'Sort In', 'learnpress-upsell' ),
				[
					'post_date'  => esc_html__( 'Default', 'learnpress-upsell' ),
					'post_title' => esc_html__( 'Title', 'learnpress-upsell' ),
					'popular'    => esc_html__( 'Popular', 'learnpress-upsell' ),
				],
				'post_date'
			),
			'order_by' => LPElementorControls::add_control_type_select(
				'order_by',
				esc_html__( 'Order By', 'learnpress-upsell' ),
				[
					'DESC' => esc_html__( 'DESC', 'learnpress-upsell' ),
					'ASC'  => esc_html__( 'ASC', 'learnpress-upsell' ),
				],
				'DESC'
			),
			'limit'    => LPElementorControls::add_control_type(
				'limit',
				esc_html__( 'Limit', 'learnpress-upsell' ),
				9,
				Controls_Manager::NUMBER,
				[
					'min' => -1,
					'max' => 100,
				]
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'slider',
		esc_html__( 'Slider', 'learnpress-upsell' ),
		Controls_Manager::TAB_CONTENT,
		[
			LPElementorControls::add_control_type(
				'show_arrow',
				esc_html__( 'Show Arrow', 'learnpress-upsell' ),
				'yes',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Yes', 'learnpress-upsell' ),
					'label_off'    => esc_html__( 'No', 'learnpress-upsell' ),
					'return_value' => 'yes',
				]
			),
			LPElementorControls::add_control_type_select(
				'paginations',
				esc_html__( 'Pagination Options', 'learnpress-upsell' ),
				[
					'hide'     => esc_html__( 'Hide', 'learnpress-upsell' ),
					'bullets'  => esc_html__( 'Bullets', 'learnpress-upsell' ),
					'progress' => esc_html__( 'Progress', 'learnpress-upsell' ),
				],
				'progress'
			),
		]
//		, // Wait LP v4.2.7.4 release to use this condition.
//		[
//			'condition' => [
//				'layout' => 'slider',
//			],
//		]
	)
);

// Fields tab style
$style_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'item',
		esc_html__( 'Item', 'learnpress-upsell' ),
		Controls_Manager::TAB_STYLE,
		[
			'row_gap'      => LPElementorControls::add_control_type(
				'row_gap',
				esc_html__( 'Rows Gap', 'learnpress-upsell' ),
				[
					'size' => 30,
				],
				Controls_Manager::SLIDER,
				[
					'size_units' => array( 'px', '%', 'custom' ),
					'range'      => array(
						'px' => array(
							'min'  => 0,
							'max'  => 100,
							'step' => 5,
						),
					),
					'condition'  => [
						'layout' => 'base',
					],
					'selectors'  => array(
						'{{WRAPPER}}' => '--lp-packages-row-gap: {{SIZE}}{{UNIT}}',
					),
				]
			),
			'column_gap'   => LPElementorControls::add_control_type(
				'column_gap',
				esc_html__( 'Columns Gap', 'learnpress-upsell' ),
				[
					'size' => 30,
				],
				Controls_Manager::SLIDER,
				[
					'size_units' => array( 'px', '%', 'custom' ),
					'range'      => array(
						'px' => array(
							'min'  => 0,
							'max'  => 100,
							'step' => 5,
						),
					),
					'selectors'  => array(
						'{{WRAPPER}}' => '--lp-packages-column-gap: {{SIZE}}{{UNIT}}',
					),
				]
			),
			'item_padding' => LPElementorControls::add_responsive_control_type(
				'item_padding',
				esc_html__( 'Padding', 'learnpress-upsell' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						'{{WRAPPER}} .learnpress-package__meta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
			'item_border'  => LPElementorControls::add_group_control_type(
				'item_border',
				Group_Control_Border::get_type(),
				'{{WRAPPER}} .learnpress-package__items'
			),
			'item_radius'  => LPElementorControls::add_responsive_control_type(
				'item_radius',
				esc_html__( 'Radius', 'learnpress-upsell' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						'{{WRAPPER}} .learnpress-package__items' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'count',
		esc_html__( 'Count Courses', 'learnpress-upsell' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'count',
			'.learnpress-package__count-courses'
		)
	),
	LPElementorControls::add_fields_in_section(
		'package',
		esc_html__( 'Title', 'learnpress-upsell' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'package',
			'.learnpress-package__title'
		)
	),
	LPElementorControls::add_fields_in_section(
		'style_regular_price',
		esc_html__( 'Regular Price', 'learnpress-upsell' ),
		Controls_Manager::TAB_STYLE,
		[
			'regular_price_color'      => LPElementorControls::add_control_type_color(
				'regular_price_color',
				esc_html__( 'Color', 'learnpress-upsell' ),
				[
					'{{WRAPPER}} .price' => 'color: {{VALUE}};',
				]
			),
			'regular_price_typography' => LPElementorControls::add_group_control_type(
				'regular_price_typography',
				Group_Control_Typography::get_type(),
				'{{WRAPPER}} .price'
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'style_origin_price',
		esc_html__( 'Origin Price', 'learnpress-upsell' ),
		Controls_Manager::TAB_STYLE,
		[
			'origin_price_color'      => LPElementorControls::add_control_type_color(
				'origin_price_color',
				esc_html__( 'Color', 'learnpress-upsell' ),
				[
					'{{WRAPPER}} .origin-price' => 'color: {{VALUE}};',
				]
			),
			'origin_price_typography' => LPElementorControls::add_group_control_type(
				'origin_price_typography',
				Group_Control_Typography::get_type(),
				'{{WRAPPER}} .origin-price'
			),
			'origin_price_margin'     => LPElementorControls::add_responsive_control_type(
				'origin_price_margin',
				esc_html__( 'Margin', 'learnpress-upsell' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						'{{WRAPPER}} .origin-price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
		]
	)
);

return apply_filters(
	'learn-press/elementor/packages',
	array_merge(
		apply_filters(
			'learn-press/elementor/packages/tab-content',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/packages/tab-styles',
			$style_fields
		)
	)
);
