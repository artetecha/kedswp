<?php

namespace Elementor;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Thim_Ekit_Widget_Lottie extends Widget_Base { 

	public function get_name() {
		return 'thim-ekits-lottie';
	}

	public function get_title() {
		return esc_html__( 'Lottie', 'thim-elementor-kit' ); 
	}

	public function get_icon() {
		return 'thim-eicon eicon-lottie';
	}

	public function get_script_depends() {
		return array( 'thim-ekit-lottie-scripts' );
	}

	public function get_categories() {
		return array( \Thim_EL_Kit\Elementor::CATEGORY );
	}

	public function get_keywords() {
		return array(
			'thim',
			'lottie',
		);
	}

	public function get_base() {
		return basename( __FILE__, '.php' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'lottie_animation',
			array(
				'label' => esc_html__( 'Lottie Animation', 'thim-elementor-kit' ),
			)
		);

		$this->add_control(
			'source_json',
			array(
				'label'       => esc_html__( 'Upload JSON File', 'thim-elementor-kit' ),
				'type'        => Controls_Manager::MEDIA,
				'media_types' => array( 'application/json' ),
				'default'     => array(
					'url' => THIM_EKIT_PLUGIN_URL . 'build/libraries/lottie_default.json',
				),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'        => esc_html__( 'Alignment', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'thim-elementor-kit' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'thim-elementor-kit' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'thim-elementor-kit' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'prefix_class' => 'elementor%s-align-',
				'default'      => 'center',
			)
		);
		$this->end_controls_section();
		$this->_register_lottie_settings();
		$this->_register_lottie_style();
	}

	protected function _register_lottie_settings() {
		$this->start_controls_section(
			'section_lottie_settings',
			array(
				'label' => __( 'Settings', 'thim-elementor-kit' ),
			)
		);

		$this->add_control(
			'trigger',
			array(
				'label'   => esc_html__( 'Trigger', 'thim-elementor-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'arriving_to_viewport' => esc_html__( 'Viewport', 'thim-elementor-kit' ),
					'hover'                => esc_html__( 'On Hover', 'thim-elementor-kit' ),
					'bind_to_scroll'       => esc_html__( 'Scroll', 'thim-elementor-kit' ),
					'none'                 => esc_html__( 'Autoplay', 'thim-elementor-kit' ),
				),
			)
		);

		$this->add_control(
			'viewport',
			array(
				'label'       => esc_html__( 'Viewport', 'thim-elementor-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'render_type' => 'none',
				'conditions'  => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'     => 'trigger',
							'operator' => '===',
							'value'    => 'arriving_to_viewport',
						),
						array(
							'name'     => 'trigger',
							'operator' => '===',
							'value'    => 'bind_to_scroll',
						),
					),
				),
				'default'     => array(
					'sizes' => array(
						'start' => 0,
						'end'   => 100,
					),
					'unit'  => '%',
				),
				'labels'      => array(
					__(
						'Bottom',
						'thim-elementor-kit'
					),
					__(
						'Top',
						'thim-elementor-kit'
					),
				),
				'scales'      => 1,
				'handles'     => 'range',
			)
		);
		$this->add_control(
			'loop',
			array(
				'label'        => esc_html__( 'Loop', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'render_type'  => 'none',
				'condition'    => array(
					'trigger!' => 'bind_to_scroll',
				),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'on_hover_out',
			array(
				'label'       => esc_html__( 'On Hover Out', 'thim-elementor-kit' ),
				'type'        => Controls_Manager::SELECT,
				'render_type' => 'none',
				'condition'   => array(
					'trigger' => 'on_hover',
				),
				'default'     => 'default',
				'options'     => array(
					'default' => esc_html__( 'Default', 'thim-elementor-kit' ),
					'reverse' => esc_html__( 'Reverse', 'thim-elementor-kit' ),
					'pause'   => esc_html__( 'Pause', 'thim-elementor-kit' ),
				),
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'   => __( 'Animation Speed', 'thim-elementor-kit' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 1,
				'min'     => 0.1,
				'max'     => 3,
				'step'    => 0.1,
			)
		);

		$this->add_control(
			'start_point',
			array(
				'label'       => esc_html__( 'Start Point', 'thim-elementor-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'render_type' => 'none',
				'default'     => array(
					'size' => '0',
					'unit' => '%',
				),
				'size_units'  => array( '%' ),
				'condition'   => array(
					'trigger' => 'bind_to_scroll',
				),
			)
		);

		$this->add_control(
			'end_point',
			array(
				'label'       => esc_html__( 'End Point', 'thim-elementor-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'render_type' => 'none',
				'default'     => array(
					'size' => '100',
					'unit' => '%',
				),
				'size_units'  => array( '%' ),
				'condition'   => array(
					'trigger' => 'bind_to_scroll',
				),
			)
		);

		$this->add_control(
			'reverse_animation',
			array(
				'label'        => esc_html__( 'Reverse', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'render_type'  => 'none',
				'conditions'   => array(
					'relation' => 'and',
					'terms'    => array(
						array(
							'name'     => 'trigger',
							'operator' => '!==',
							'value'    => 'bind_to_scroll',
						),
						array(
							'name'     => 'trigger',
							'operator' => '!==',
							'value'    => 'on_hover',
						),
					),
				),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'renderer',
			array(
				'label'     => esc_html__( 'Renderer', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'svg',
				'options'   => array(
					'svg'    => esc_html__( 'SVG', 'thim-elementor-kit' ),
					'canvas' => esc_html__( 'Canvas', 'thim-elementor-kit' ),
				),
				'separator' => 'before',
			)
		);
		$this->end_controls_section();
	}

	protected function _register_lottie_style() {
		$this->start_controls_section(
			'style',
			array(
				'label' => __( 'Lottie', 'thim-elementor-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'width',
			array(
				'label'          => __( 'Width', 'thim-elementor-kit' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => array(
					'unit' => '%',
				),
				'tablet_default' => array(
					'unit' => '%',
				),
				'mobile_default' => array(
					'unit' => '%',
				),
				'size_units'     => array( '%', 'px', 'vw' ),
				'range'          => array(
					'%'  => array(
						'min' => 1,
						'max' => 100,
					),
					'px' => array(
						'min' => 1,
						'max' => 1000,
					),
					'vw' => array(
						'min' => 1,
						'max' => 100,
					),
				),
				'selectors'      => array(
					'{{WRAPPER}} .thim-lottie-animations svg'    => 'width: {{SIZE}}{{UNIT}}!important;',
					'{{WRAPPER}} .thim-lottie-animations canvas' => 'width: {{SIZE}}{{UNIT}}!important;',
				),
			)
		);

		$this->add_responsive_control(
			'space',
			array(
				'label'          => __( 'Max Width', 'thim-elementor-kit' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => array(
					'unit' => '%',
				),
				'tablet_default' => array(
					'unit' => '%',
				),
				'mobile_default' => array(
					'unit' => '%',
				),
				'size_units'     => array( '%', 'px', 'vw' ),
				'range'          => array(
					'%'  => array(
						'min' => 1,
						'max' => 100,
					),
					'px' => array(
						'min' => 1,
						'max' => 1000,
					),
					'vw' => array(
						'min' => 1,
						'max' => 100,
					),
				),
				'selectors'      => array(
					'{{WRAPPER}} .thim-lottie-animations svg'    => 'max-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .thim-lottie-animations canvas' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'separator_panel_style',
			array(
				'type'  => Controls_Manager::DIVIDER,
				'style' => 'thick',
			)
		);

		$this->start_controls_tabs( 'image_effects' );

		$this->start_controls_tab( 'normal', array( 'label' => __( 'Normal', 'thim-elementor-kit' ) ) );

		$this->add_control(
			'opacity',
			array(
				'label'     => __( 'Opacity', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'max'  => 1,
						'min'  => 0.10,
						'step' => 0.01,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .thim-lottie-animations svg'    => 'opacity: {{SIZE}};',
					'{{WRAPPER}} .thim-lottie-animations canvas' => 'opacity: {{SIZE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name'     => 'css_filters',
				'selector' => '{{WRAPPER}} .thim-lottie-animations svg,{{WRAPPER}} .thim-lottie-animations canvas',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'hover', array( 'label' => __( 'Hover', 'thim-elementor-kit' ) ) );

		$this->add_control(
			'opacity_hover',
			array(
				'label'     => __( 'Opacity', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'max'  => 1,
						'min'  => 0.10,
						'step' => 0.01,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .thim-lottie-animations:hover svg'    => 'opacity: {{SIZE}};',
					'{{WRAPPER}} .thim-lottie-animations:hover canvas' => 'opacity: {{SIZE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name'     => 'css_filters_hover',
				'selector' => '{{WRAPPER}} .thim-lottie-animations:hover svg,{{WRAPPER}} .thim-lottie-animations:hover canvas',
			)
		);

		$this->add_control(
			'background_hover_transition',
			array(
				'label'     => __( 'Transition Duration', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'max'  => 3,
						'step' => 0.1,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .thim-lottie-animations svg'    => 'transition-duration: {{SIZE}}s',
					'{{WRAPPER}} .thim-lottie-animations canvas' => 'transition-duration: {{SIZE}}s',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	public function thim_lottie_attributes( $settings ) {
		$attributes = array(
			'loop'            => $settings['loop'] == 'yes' ? $settings['loop'] : 'no',
			'autoplay'        => 'yes',
			// TODO: reverse
			'speed'           => $settings['speed'],
			'trigger'         => $settings['trigger'],
			'reverse'         => $settings['reverse_animation'],
			'scroll_start'    => isset( $settings['start_point'] ) ? $settings['start_point']['size'] : '0',
			'scroll_end'      => isset( $settings['end_point'] ) ? $settings['end_point']['size'] : '100',
			'lottie_renderer' => $settings['renderer'],
		);

		return json_encode( $attributes );
	}

	protected function render() {
		$settings    = $this->get_settings_for_display();
		$lottie_json = $settings['source_json']['url'];
		if ( $lottie_json ) {
			echo '<div class="thim-lottie-animations-wrapper"><div class="thim-lottie-animations" data-settings="' . esc_attr( $this->thim_lottie_attributes( $settings ) ) . '" data-json-url="' . esc_url( $lottie_json ) . '"></div></div>';
		}
	}
}
