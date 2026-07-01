<?php

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;
use Elementor\Group_Control_Border;

// Fields tab content
$content_fields = LPElementorControls::add_fields_in_section(
	'content',
	esc_html__( 'Content', 'learnpress-upsell' ),
	Controls_Manager::TAB_CONTENT,
	[
		'tag'       => LPElementorControls::add_control_type(
			'tag',
			esc_html__( 'Tag', 'learnpress-upsell' ),
			esc_html__( 'Big Sale', 'learnpress-upsell' )
		),
        'title'       => LPElementorControls::add_control_type(
			'title',
			esc_html__( 'Title', 'learnpress-upsell' ),
			esc_html__( 'Buy this course in package', 'learnpress-upsell' )
		),
    ]
);

// Fields tab style
$style_fields = array_merge(
    LPElementorControls::add_fields_in_section(
		'tag',
		esc_html__( 'Tag', 'learnpress-upsell' ),
		Controls_Manager::TAB_STYLE,
        LPElementorControls::add_controls_style_text(
            'tag',
            '.lp-course-packages__top span',
            [
                'tag_radius'       => LPElementorControls::add_responsive_control_type(
                    'tag_radius',
                    esc_html__( 'Border Radius', 'learnpress-upsell' ),
                    [],
                    Controls_Manager::DIMENSIONS,
                    [
                        'size_units' => [ 'px', '%', 'custom' ],
                        'selectors'  => array(
                            '{{WRAPPER}} .lp-course-packages__top span' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ),
                    ]
                )
            ]
        )
	),
    LPElementorControls::add_fields_in_section(
		'title',
		esc_html__( 'Title', 'learnpress-upsell' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'title',
			'.lp-course-packages__top h4'
		)
	),
	LPElementorControls::add_fields_in_section(
		'item',
		esc_html__( 'Package Item', 'learnpress-upsell' ),
		Controls_Manager::TAB_STYLE,
		[
			'item_margin'     => LPElementorControls::add_responsive_control_type(
				'item_margin',
				esc_html__( 'Margin', 'learnpress-upsell' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						'{{WRAPPER}} .learnpress-package__items' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
			'item_border'     => LPElementorControls::add_group_control_type(
				'item_border',
				Group_Control_Border::get_type(),
				'{{WRAPPER}} .learnpress-package__items'
			),
			'item_radius'     => LPElementorControls::add_responsive_control_type(
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
		'image',
		esc_html__( 'Image', 'learnpress-upsell' ),
		Controls_Manager::TAB_STYLE,
        [
            'image_width' => LPElementorControls::add_responsive_control_type(
				'image_width',
                esc_html__( 'Width Image', 'learnpress-upsell' ),
                [],
                Controls_Manager::SLIDER,
                [
                    'size_units' => array( 'px', '%', 'custom' ),
                    'range'      => array(
                        'px' => array(
                            'min'  => 1,
                            'max'  => 500,
                            'step' => 5,
                        ),
                    ),
                    'selectors'  => array(
                        '{{WRAPPER}} .learnpress-package__items > a' => 'flex-basis: {{SIZE}}{{UNIT}};',
                    ),
                ]
			),
			'meta_width' => LPElementorControls::add_responsive_control_type(
				'meta_width',
                esc_html__( 'Content Image', 'learnpress-upsell' ),
                [],
                Controls_Manager::SLIDER,
                [
                    'size_units' => array( 'px', '%', 'custom' ),
                    'range'      => array(
                        'px' => array(
                            'min'  => 1,
                            'max'  => 500,
                            'step' => 5,
                        ),
                    ),
                    'selectors'  => array(
                        '{{WRAPPER}} .learnpress-package__meta' => 'flex-basis: {{SIZE}}{{UNIT}};',
                    ),
                ]
			),
            'image_radius'       => LPElementorControls::add_responsive_control_type(
                'image_radius',
                esc_html__( 'Border Radius', 'learnpress-upsell' ),
                [],
                Controls_Manager::DIMENSIONS,
                [
                    'size_units' => [ 'px', '%', 'custom' ],
                    'selectors'  => array(
                        '{{WRAPPER}} .learnpress-package__image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ),
                ]
            )
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
		esc_html__( 'Title Package', 'learnpress-upsell' ),
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
			'regular_price_color'       => LPElementorControls::add_control_type_color(
				'regular_price_color',
				esc_html__( 'Color', 'learnpress-upsell' ),
				[
					'{{WRAPPER}} .price' => 'color: {{VALUE}};',
				]
			),
			'regular_price_typography'    => LPElementorControls::add_group_control_type(
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
			'origin_price_color'       => LPElementorControls::add_control_type_color(
				'origin_price_color',
				esc_html__( 'Color', 'learnpress-upsell' ),
				[
					'{{WRAPPER}} .origin-price' => 'color: {{VALUE}};',
				]
			),
			'origin_price_typography'    => LPElementorControls::add_group_control_type(
				'origin_price_typography',
				Group_Control_Typography::get_type(),
				'{{WRAPPER}} .origin-price'
			),
			'origin_price_margin'       => LPElementorControls::add_responsive_control_type(
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
			)
		]
	),
	LPElementorControls::add_fields_in_section(
		'btn_loadmore',
		esc_html__( 'Load More', 'learnpress-upsell' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_button(
			'loadmore',
			'.lp-course-packages__loadmore__btn'
		)
	)
);

return apply_filters(
	'learn-press/elementor/list-package',
	array_merge(
		apply_filters(
			'learn-press/elementor/list-package/tab-content',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/list-package/tab-styles',
			$style_fields
		)
	)
);
