<?php

use Elementor\Controls_Manager;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;

return LPElementorControls::add_fields_in_section(
	'style_button',
	esc_html__( 'Style', 'learnpress-wishlist' ),
	Controls_Manager::TAB_STYLE,
	[
		'layout'       => LPElementorControls::add_control_type_select(
			'layout',
			esc_html__( 'Layout', 'learnpress-wishlist' ),
			[
				'classic'   => esc_html__( 'Classic', 'learnpress-wishlist' ),
				'modern'    => esc_html__( 'Modern', 'learnpress-wishlist' ),
				'icon-only' => esc_html__( 'Icon Only', 'learnpress-wishlist' ),
			],
			'classic'
		),
		'add_button_color'              => LPElementorControls::add_control_type_color(
			'add_button_color',
			esc_html__( 'Color', 'learnpress-wishlist' ),
			[
				'{{WRAPPER}} .lp-button-wishlist-action , {{WRAPPER}} .lp-button-wishlist-action i' => 'color: {{VALUE}};',
			]
		),
		'remove_button_color'           => LPElementorControls::add_control_type_color(
			'remove_button_color',
			esc_html__( 'Color Active, Hover', 'learnpress-wishlist' ),
			[
				'{{WRAPPER}} .lp-button-wishlist-action.wishlisted,{{WRAPPER}} .lp-button-wishlist-action:hover' => 'color: {{VALUE}};',
				'{{WRAPPER}} .lp-button-wishlist-action:hover i, {{WRAPPER}} .lp-button-wishlist-action.wishlisted i' => 'color: {{VALUE}};'
			]
		),
		'button_background_color'       => LPElementorControls::add_control_type_color(
			'button_background_color',
			esc_html__( 'Background Color', 'learnpress-wishlist' ),
			[
				'{{WRAPPER}} .lp-button-wishlist-action' => 'background-color: {{VALUE}};',
			]
		),
		'button_background_color_hover' => LPElementorControls::add_control_type_color(
			'button_background_color_hover',
			esc_html__( 'Background Color Hover', 'learnpress-wishlist' ),
			[
				'{{WRAPPER}} .lp-button-wishlist-action:hover' => 'background-color: {{VALUE}};',
			]
		),
		'button_padding'                => LPElementorControls::add_responsive_control_type(
			'button_padding',
			esc_html__( 'Padding', 'learnpress-wishlist' ),
			[],
			Controls_Manager::DIMENSIONS,
			[
				'size_units' => [ 'px', '%', 'custom' ],
				'selectors'  => array(
					'{{WRAPPER}} .lp-button-wishlist-action' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			]
		),
		'button_typography'             => LPElementorControls::add_group_control_type(
			'button_typography',
			Group_Control_Typography::get_type(),
			'{{WRAPPER}} .lp-button-wishlist-action'
		),
		'button_border'     => LPElementorControls::add_group_control_type(
			'button_border',
			Group_Control_Border::get_type(),
			'{{WRAPPER}} .lp-button-wishlist-action'
		),
		'icon_size'                     => LPElementorControls::add_responsive_control_type(
			'icon_size',
			esc_html__( 'Icon Size', 'learnpress-wishlist' ),
			[],
			Controls_Manager::SLIDER,
			[
				'size_units' => [ 'px', 'em', 'custom' ],
				'selectors'  => array(
					'{{WRAPPER}} .lp-button-wishlist-action i' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			]
		),
		'icon_space'                    => LPElementorControls::add_responsive_control_type(
			'icon_space',
			esc_html__( 'Icon Space', 'learnpress-wishlist' ),
			[],
			Controls_Manager::SLIDER,
			[
				'size_units' => [ 'px', '%', 'custom' ],
				'selectors'  => array(
					'{{WRAPPER}} .lp-button-wishlist-action i' => 'margin-right: {{SIZE}}{{UNIT}};',
				),
			]
		),
		'button_shadow'                 => LPElementorControls::add_group_control_type(
			'button_shadow',
			Group_Control_Text_Shadow::get_type(),
			'{{WRAPPER}} .lp-button-wishlist-action'
		),
	]
);
