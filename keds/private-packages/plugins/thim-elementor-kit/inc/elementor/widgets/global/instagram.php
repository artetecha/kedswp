<?php

namespace Elementor;

use Elementor\Group_Control_Image_Size;
use Thim_EL_Kit\GroupControlTrait;

class Thim_Ekit_Widget_Instagram extends Widget_Base {
	use GroupControlTrait;

	public function __construct( $data = array(), $args = null ) { 
		parent::__construct( $data, $args );
	}

	public function get_name() {
		return 'thim-ekits-instagram';
	}

	public function get_title() {
		return esc_html__( 'Instagram Feed', 'thim-elementor-kit' );
	}

	public function get_icon() {
		return 'thim-eicon eicon-instagram-post';
	}

	public function get_style_depends(): array {
		return ['e-swiper'];
	}

	public function get_categories() {
		return array( \Thim_EL_Kit\Elementor::CATEGORY );
	}

	public function get_keywords() {
		return [
			'instagram',
			'instagram feed',
			'social media',
			'social feed',
			'instagram embed',
			'thim',
		];
	}

	public function get_help_url() {
		return '';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_image',
			[
				'label' => esc_html__( 'General', 'thim-elementor-kit' ),
			]
		);
		$this->add_control(
			'accesstoken',
			[
				'label'       => esc_html__( 'Access Token', 'thim-elementor-kit' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'description' => '<a href="https://developers.facebook.com/docs/instagram-basic-display-api/getting-started" target="_blank">' . __( 'Get Access Token',
						'thim-elementor-kit' ) . '</a>',
			]
		);
		$this->add_control(
			'data_cache_limit',
			[
				'label'       => __( 'Data Cache Time', 'thim-elementor-kit' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'default'     => 60,
				'description' => __( 'Cache expiration time (Minutes)', 'thim-elementor-kit' )
			]
		);
		$this->add_control(
			'sort_by',
			[
				'label'   => esc_html__( 'Sort By', 'thim-elementor-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none'         => esc_html__( 'None', 'thim-elementor-kit' ),
					'most-recent'  => esc_html__( 'Most Recent', 'thim-elementor-kit' ),
					'least-recent' => esc_html__( 'Least Recent', 'thim-elementor-kit' ),
				],
			]
		);

		$this->add_control(
			'show_number_item',
			array(
				'label'   => esc_html__( 'Show Number Item', 'thim-elementor-kit' ),
				'default' => '6',
				'type'    => Controls_Manager::NUMBER,
			)
		);
		$this->add_control(
			'show_caption',
			[
				'label'        => esc_html__( 'Display Caption', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);
		$this->add_control(
			'caption_length',
			[
				'label'     => esc_html__( 'Max Caption Length', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 2000,
				'default'   => 60,
				'condition' => [
					'show_caption' => 'yes',
				],
			]
		);
		$this->add_control(
			'show_date',
			[
				'label'        => esc_html__( 'Display Date', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_likes',
			[
				'label'        => esc_html__( 'Display Likes Count', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_comments',
			[
				'label'        => esc_html__( 'Display Comments Count', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'enable_link',
			[
				'label'        => esc_html__( 'Enable Link', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'link_target',
			[
				'label'        => esc_html__( 'Open in new window?', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'enable_link' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'image_size',
				'default'   => 'full',
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		$this->_register_option_style();
		$this->_register_settings_slider();
		$this->_register_setting_slider_dot_style( ['layout' => 'slider'] );
		$this->_register_setting_slider_nav_style( ['layout' => 'slider'] );
	}

	protected function _register_settings_slider() {
		$this->start_controls_section(
			'slider_settings_section',
			[
				'label'     => esc_html__( 'Slider Settings', 'thim-elementor-kit' ),
				'condition' => [
					'layout' => 'slider',
				],
			]
		);

		$this->add_responsive_control(
			'slidesPerView',
			array(
				'label'              => esc_html__( 'Slides Per View', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::NUMBER,
				'min'                => 1,
				'max'                => 10,
				'step'               => 1,
				'default'            => 3,
				'tablet_default'     => 2,
				'mobile_default'     => 1,
				'frontend_available' => true,
				'devices'            => array('widescreen', 'desktop', 'tablet', 'mobile'),
				'condition'          => array(
					'layout'        => 'slider',
					'slider_effect!' => 'stack',
				),
			)
		);

		$this->add_responsive_control(
			'slidesPerGroup',
			array(
				'label'              => esc_html__( 'Slides Per Group', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::NUMBER,
				'min'                => 1,
				'max'                => 10,
				'step'               => 1,
				'default'            => 1,
				'frontend_available' => true,
				'devices'            => array('widescreen', 'desktop', 'tablet', 'mobile'),
				'condition'          => array(
					'layout'        => 'slider',
					'slider_effect!' => 'stack',
				),
			)
		);

		$this->add_responsive_control(
			'spaceBetween',
			array(
				'label'              => esc_html__( 'Space Between', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::NUMBER,
				'min'                => 0,
				'max'                => 100,
				'step'               => 1,
				'default'            => 30,
				'frontend_available' => true,
				'devices'            => array('widescreen', 'desktop', 'tablet', 'mobile'),
				'condition'          => array(
					'layout'        => 'slider',
					'slider_effect!' => 'stack',
				),
			)
		);
		$this->add_control(
			'slider_speed',
			[
				'label'              => esc_html__( 'Speed', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::NUMBER,
				'min'                => 1,
				'max'                => 10000,
				'step'               => 1,
				'default'            => 1000,
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'slider_autoplay',
			[
				'label'              => esc_html__( 'Autoplay', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::SWITCHER,
				'label_on'           => esc_html__( 'Yes', 'thim-elementor-kit' ),
				'label_off'          => esc_html__( 'No', 'thim-elementor-kit' ),
				'return_value'       => 'yes',
				'default'            => 'yes',
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'autoplay_speed',
			[
				'label'              => esc_html__( 'Autoplay Speed', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::NUMBER,
				'min'                => 1000,
				'max'                => 10000,
				'step'               => 100,
				'default'            => 3000,
				'frontend_available' => true,
				'condition'          => [
					'slider_autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'pause_on_interaction',
			[
				'label'              => esc_html__( 'Pause on Interaction', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::SWITCHER,
				'label_on'           => esc_html__( 'Yes', 'thim-elementor-kit' ),
				'label_off'          => esc_html__( 'No', 'thim-elementor-kit' ),
				'return_value'       => 'yes',
				'default'            => 'yes',
				'frontend_available' => true,
				'condition'          => [
					'slider_autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'pause_on_hover',
			[
				'label'              => esc_html__( 'Pause on Hover', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::SWITCHER,
				'default'            => 'yes',
				'label_on'           => esc_html__( 'Yes', 'thim-elementor-kit' ),
				'label_off'          => esc_html__( 'No', 'thim-elementor-kit' ),
				'return_value'       => 'yes',
				'frontend_available' => true,
				'condition'          => [
					'slider_autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'centered_slides',
			[
				'label'              => esc_html__( 'Centered Slides', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::SWITCHER,
				'label_on'           => esc_html__( 'Yes', 'thim-elementor-kit' ),
				'label_off'          => esc_html__( 'No', 'thim-elementor-kit' ),
				'return_value'       => 'yes',
				'default'            => 'no',
				'frontend_available' => true,
				'condition'          => array(
					'layout'        => 'slider',
					'slider_effect!' => 'stack',
				),
			]
		);

		$this->add_control(
			'slider_loop',
			[
				'label'              => esc_html__( 'Enable Loop?', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::SWITCHER,
				'label_on'           => esc_html__( 'Yes', 'thim-elementor-kit' ),
				'label_off'          => esc_html__( 'No', 'thim-elementor-kit' ),
				'return_value'       => 'yes',
				'default'            => 'yes',
				'frontend_available' => true,
				'condition'          => array(
					'layout'        => 'slider',
					'slider_effect!' => 'stack',
				),
			]
		);

		$this->add_control(
			'slider_show_arrow',
			[
				'label'              => esc_html__( 'Show Arrow', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::SWITCHER,
				'label_on'           => esc_html__( 'Yes', 'thim-elementor-kit' ),
				'label_off'          => esc_html__( 'No', 'thim-elementor-kit' ),
				'return_value'       => 'yes',
				'default'            => 'yes',
				'frontend_available' => true,
				'condition'          => array(
					'layout'        => 'slider',
					'slider_effect!' => 'stack',
				),
			]
		);

		$this->add_control(
			'slider_show_pagination',
			[
				'label'              => esc_html__( 'Pagination Options', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => 'bullets',
				'options'            => [
					'none'     => esc_html__( 'Hide', 'thim-elementor-kit' ),
					'bullets'  => esc_html__( 'Bullets', 'thim-elementor-kit' ),
					'fraction' => esc_html__( 'Fraction', 'thim-elementor-kit' ),
				],
				'frontend_available' => true,
			]
		);
		$this->end_controls_section();
	}

	protected function _register_option_style() {
		$this->start_controls_section(
			'_register_option_style_tab',
			[
				'label' => esc_html__( 'Options', 'thim-elementor-kit' ),
			]
		);

		$this->add_control(
			'layout',
			[
				'label'   => esc_html__( 'Layout', 'thim-elementor-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => [
					'default' => esc_html__( 'Default (Grid)', 'thim-elementor-kit' ),
					'slider'  => esc_html__( 'Slider', 'thim-elementor-kit' ),
				],
			]
		);

		$this->add_control(
			'item_style',
			[
				'label'   => esc_html__( 'Item Style', 'thim-elementor-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'overlay',
				'options' => [
					'overlay' => esc_html__( 'Overlay', 'thim-elementor-kit' ),
					'card'    => esc_html__( 'Card', 'thim-elementor-kit' ),
				],
			]
		);
		$this->add_control(
			'slider_effect',
			[
				'label'              => esc_html__( 'Slider Effect', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => 'normal',
				'options'            => [
					'normal' => esc_html__( 'Normal', 'thim-elementor-kit' ),
					'stack'  => esc_html__( 'Stack (fan)', 'thim-elementor-kit' ),
				],
				'frontend_available' => true,
				'condition' => [
					'layout' => 'slider',
				],
			]
		);

		$this->add_responsive_control(
			'stack_slider_horizontal_fan_items_show',
			array(
				'label'              => esc_html__('Number Items Show', 'thim-elementor-kit'),
				'type'               => Controls_Manager::NUMBER,
				'min'                => 1,
				'max'                => 10,
				'step'               => 1,
				'default'            => 3,
				'frontend_available' => true,
				'condition'          => array(
					'layout'        => 'slider',
					'slider_effect' => 'stack',
				),
				'selectors'          => array(
					'{{WRAPPER}}' => '--stack-fan-items-show: {{VALUE}};',
				),
			)
		);	
		$this->add_responsive_control(
			'stack_slider_horizontal_fan_spacing',
			array(
				'label'              => esc_html__('Spacing', 'thim-elementor-kit'),
				'type'               => Controls_Manager::NUMBER,
				'min'                => 0,
				'max'                => 1000,
				'step'               => 10,
				'default'            => 50,
				'frontend_available' => true,
				'condition'          => array(
					'layout'        => 'slider',
					'slider_effect' => 'stack',
				),
				'selectors'          => array(
					'{{WRAPPER}}' => '--stack-fan-spacing: {{VALUE}}px;',
				),
			)
		);
		$this->add_responsive_control(
			'stack_slider_horizontal_fan_pos_y',
			array(
				'label'              => esc_html__('Position Y', 'thim-elementor-kit'),
				'type'               => Controls_Manager::NUMBER,
				'min'                => -1000,
				'max'                => 1000,
				'step'               => 10,
				'default'            => 0,
				'frontend_available' => true,
				'condition'          => array(
					'layout'        => 'slider',
					'slider_effect' => 'stack',
				),
				'selectors'          => array(
					'{{WRAPPER}}' => '--stack-fan-pos-y: {{VALUE}}px;',
				),
			)
		);
		$this->add_responsive_control(
			'stack_slider_slide_width',
			array(
				'label'              => esc_html__('Slide Width', 'thim-elementor-kit'),
				'type'               => Controls_Manager::NUMBER,
				'min'                => 1,
				'max'                => 10000,
				'step'               => 1,
				'default'            => 400,
				'frontend_available' => true,
				'condition'          => array(
					'layout'        => 'slider',
					'slider_effect' => 'stack',
				),
				'selectors'          => array(
					'{{WRAPPER}} .thim-ekits-stack-slider .swiper-slide' => 'width: {{SIZE}}px !important;',
				),
			)
		);
		$this->add_responsive_control(
			'columns',
			array(
				'label'     => esc_html__( 'Columns', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'size' => 6,
				),
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 10,
					),
				),
				'selectors' => array(
					'{{WRAPPER}}' => '--thim-ekit-instagram-columns: repeat({{SIZE}}, 1fr)', 
				),
				'condition' => [
					'layout' => 'default',
				],
			)
		);
		$this->add_responsive_control(
			'column_gap',
			array(
				'label'     => esc_html__( 'Columns Gap', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'size' => 20,
				),
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors' => array(
					'{{WRAPPER}}' => '--thim-ekit-instagram-column-gap: {{SIZE}}{{UNIT}}',
				),
				'condition' => [
					'layout' => 'default',
				],
			)
		);

		$this->end_controls_section();
	}

	public function render() {
		$settings = $this->get_settings_for_display();
		if ( empty( $settings['accesstoken'] ) ) {
			return;
		}
		
		// Render navigation and pagination for slider layout
		if ( $settings['layout'] === 'slider' ) {
			$this->render_nav_pagination_slider($settings);
		}
		
		// Add Swiper wrapper for slider layout
		if ( $settings['layout'] === 'slider' ) {
			$swiper_class = \Elementor\Plugin::$instance->experiments->is_feature_active('e_swiper_latest') ? 'swiper' : 'swiper-container';
			$slider_class = ' thim-ekits-sliders ' . esc_attr($swiper_class);
			
			// Add stack class if effect is stack
			$stack_class = '';
			$stack_effect_attr = '';
			if ( ! empty( $settings['slider_effect'] ) && $settings['slider_effect'] === 'stack' ) {
				$stack_class = ' thim-ekits-stack-slider';
				$stack_effect_attr = ' data-stack-direction="horizontal" data-stack-effect="fan"'; 
			}
			?>
			<div class="tp-instagram__wrapper tp-instagram__slider<?php echo esc_attr($slider_class . $stack_class); ?>"<?php echo $stack_effect_attr; ?>>
				<div class="swiper-wrapper">
					<?php echo $this->_render_items( $settings ); ?>
				</div>
			</div>
			<?php
		} else {
			echo '<div class="tp-instagram__list tp-instagram__wrapper">' . $this->_render_items( $settings ) . '</div>';
		}
	}

	public function _render_items( $settings ) {
		// Cache key only depends on accesstoken, not on cache time settings
		$key  = 'thim_ekits_instagram_' . md5( str_replace( '.', '_', $settings['accesstoken'] ) );
		$html = '';
		
		// Ensure data_cache_limit has a valid value (default 60 minutes)
		$cache_time = ! empty( $settings['data_cache_limit'] ) ? intval( $settings['data_cache_limit'] ) : 60;
		
		$cached_data = get_transient( $key );
		
		if ( $cached_data === false ) {
			$request_args   = array(
				'timeout' => 60,
			);
			$instagram_data = wp_remote_retrieve_body(
				wp_remote_get(
					'https://graph.instagram.com/me/media/?fields=username,id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,like_count,comments_count&limit=' . $settings['show_number_item'] . '&access_token=' . $settings['accesstoken'],
					$request_args
				)
			);
			$data_check = json_decode( $instagram_data, true );
			
			if ( ! empty( $data_check['data'] ) ) {
				set_transient( $key, $instagram_data, ( $cache_time * MINUTE_IN_SECONDS ) );
			}
		} else {
			$instagram_data = $cached_data;
		}

		$instagram_data = json_decode( $instagram_data, true );

		if ( empty( $instagram_data['data'] ) ) {
			return;
		}

		switch ( $settings['sort_by'] ) {
			case 'most-recent':
				usort( $instagram_data['data'], function ( $a, $b ) {
					return (int) ( strtotime( $a['timestamp'] ) < strtotime( $b['timestamp'] ) );
				} );
				break;

			case 'least-recent':
				usort( $instagram_data['data'], function ( $a, $b ) {
					return (int) ( strtotime( $a['timestamp'] ) > strtotime( $b['timestamp'] ) );
				} );
				break;
		}

		if ( $items = $instagram_data['data'] ) {
			foreach ( $items as $item ) {
				$img_alt_posted_by = ! empty( $item['username'] ) ? $item['username'] : '-';
				$img_alt_content   = __( 'Photo by ', 'thim-elementor-kit' ) . $img_alt_posted_by;

				if ( 'yes' === $settings['enable_link'] ) {
					$target = ( $settings['link_target'] ) ? 'target=_blank' : 'target=_self';
				} else {
					$item['permalink'] = '#';
					$target            = '';
				}

				// Check item style
			if ( $settings['item_style'] === 'card' ) {
				$html .= $this->_render_card_layout( $item, $settings, $target, $img_alt_content );
			} else {
				$html .= $this->_render_overlay_layout( $item, $settings, $target, $img_alt_content ); 
			}
			}
		}

		return $html;
	}

	public function _render_caption( $settings, $item ) {
		$caption_length = ( ! empty( $settings['caption_length'] ) & $settings['caption_length'] > 0 ) ? $settings['caption_length'] : 60;
		if ( $settings['show_caption'] && ! empty( $item['caption'] ) ) {
			return '<p class="caption-text">' . substr( $item['caption'], 0, intval( $caption_length ) ) . '...</p>';
		}
	}

	public function _render_date_post( $settings, $item ) {
		if ( $settings['show_date'] ) {
			return '<div class="tp-instagram__meta">' . date( "d M Y", strtotime( $item['timestamp'] ) ) . '</div>';
		}
	}

	public function _render_stats( $settings, $item ) {
		$html = '';
		if ( $settings['show_likes'] || $settings['show_comments'] ) {
			$html .= '<div class="tp-instagram__stats">';
			
			if ( $settings['show_likes'] && isset( $item['like_count'] ) ) {
				$html .= '<span class="tp-instagram__likes">';
				$html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
				$html .= '<path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="white"/>';
				$html .= '</svg>';
				$html .= ' ' . number_format( $item['like_count'] );
				$html .= '</span>';
			}
			
			if ( $settings['show_comments'] && isset( $item['comments_count'] ) ) {
				$html .= '<span class="tp-instagram__comments">';
				$html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
				$html .= '<path d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18zM18 14H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z" fill="white"/>';
				$html .= '</svg>';
				$html .= ' ' . number_format( $item['comments_count'] );
				$html .= '</span>';
			}
			
			$html .= '</div>';
		}
		
		return $html;
	}

	protected function _get_image_style( $settings ) {
		// Get image size settings
		$image_size = isset( $settings['image_size_size'] ) ? $settings['image_size_size'] : 'full';
		
		// If full size, no need to set dimensions
		if ( $image_size === 'full' || empty( $image_size ) ) {
			return '';
		}
		
		$width = '';
		$height = '';
		
		// If custom size is selected, use custom dimensions
		if ( $image_size === 'custom' ) {
			$width = ! empty( $settings['image_size_custom_dimension']['width'] ) ? intval( $settings['image_size_custom_dimension']['width'] ) : '';
			$height = ! empty( $settings['image_size_custom_dimension']['height'] ) ? intval( $settings['image_size_custom_dimension']['height'] ) : '';
		} else {
			// For WordPress image sizes, map to approximate pixel widths
			$size_map = [
				'thumbnail' => ['width' => 150, 'height' => 150],
				'medium'    => ['width' => 300, 'height' => 300],
				'medium_large' => ['width' => 768, 'height' => ''],
				'large'     => ['width' => 1024, 'height' => 1024],
			];
			
			if ( isset( $size_map[ $image_size ] ) ) {
				$width = $size_map[ $image_size ]['width'];
				$height = $size_map[ $image_size ]['height'];
			}
		}
		
		// Build inline style
		$styles = [];
		
		if ( ! empty( $width ) ) {
			$styles[] = 'width: ' . esc_attr( $width ) . 'px';
		}
		
		if ( ! empty( $height ) ) {
			$styles[] = 'height: ' . esc_attr( $height ) . 'px';
		}
		
		// Add object-fit if both dimensions are set
		if ( ! empty( $width ) && ! empty( $height ) ) {
			$styles[] = 'object-fit: cover';
		}
		
		return ! empty( $styles ) ? ' style="' . implode( '; ', $styles ) . ';"' : '';
	}

	public function _render_overlay_layout( $item, $settings, $target, $img_alt_content ) {
		$slide_class = $settings['layout'] === 'slider' ? ' swiper-slide' : '';
		$html = '<div class="tp-instagram__item' . esc_attr($slide_class) . '">
				<a href="' . esc_url( $item['permalink'] ) . '" ' . esc_attr( $target ) . '> ';

		if ( $item['media_type'] == 'VIDEO' ) {
			$html .= '<div class="tp-instagram__video"><video width="400" controls>
					  <source src="' . esc_url( $item['media_url'] ) . '" type="video/mp4">
					   <source src="' . esc_url( $item['media_url'] ) . '" type="video/ogg">
				</video></div>';
		} else {
			$image_style = $this->_get_image_style( $settings );
			$html .= '<div class="tp-instagram__image"><img alt="' . $img_alt_content . '" class="instagram-img" src="' . esc_url( $item['media_url'] ) . '"' . $image_style . '>
						 <div class="tp-instagram__caption">';
			$html .= '<svg fill="none" height="30" viewbox="0 0 48 49" width="30" xmlns="http://www.w3.org/2000/svg">
							 <path d="M24 0C17.487 0 16.668 0.030625 14.109 0.147C11.55 0.2695 9.807 0.679875 8.28 1.28625C6.67828 1.90126 5.22747 2.86597 4.029 4.11294C2.80823 5.33701 1.86332 6.81786 1.26 8.4525C0.666 10.0083 0.261 11.7906 0.144 14.3937C0.03 17.0122 0 17.8452 0 24.5031C0 31.1548 0.03 31.9878 0.144 34.6001C0.264 37.2094 0.666 38.9887 1.26 40.5475C1.875 42.1584 2.694 43.5243 4.029 44.8871C5.361 46.2499 6.699 47.089 8.277 47.7137C9.807 48.3201 11.547 48.7336 14.103 48.853C16.665 48.9694 17.481 49 24 49C30.519 49 31.332 48.9694 33.894 48.853C36.447 48.7305 38.196 48.3201 39.723 47.7137C41.3237 47.0984 42.7735 46.1337 43.971 44.8871C45.306 43.5243 46.125 42.1584 46.74 40.5475C47.331 38.9887 47.736 37.2094 47.856 34.6001C47.97 31.9878 48 31.1548 48 24.5C48 17.8452 47.97 17.0122 47.856 14.3968C47.736 11.7906 47.331 10.0083 46.74 8.4525C46.1368 6.81781 45.1918 5.33694 43.971 4.11294C42.7729 2.86551 41.322 1.90073 39.72 1.28625C38.19 0.679875 36.444 0.266438 33.891 0.147C31.329 0.030625 30.516 0 23.994 0H24.003H24ZM21.849 4.41613H24.003C30.411 4.41613 31.17 4.43756 33.699 4.557C36.039 4.66419 37.311 5.06537 38.157 5.39919C39.276 5.84325 40.077 6.37612 40.917 7.23362C41.757 8.09112 42.276 8.90575 42.711 10.0511C43.041 10.9117 43.431 12.2102 43.536 14.5989C43.653 17.1806 43.677 17.9554 43.677 24.4939C43.677 31.0323 43.653 31.8102 43.536 34.3919C43.431 36.7806 43.038 38.0761 42.711 38.9397C42.3262 40.0035 41.7121 40.9653 40.914 41.7541C40.074 42.6116 39.276 43.1414 38.154 43.5855C37.314 43.9224 36.042 44.3205 33.699 44.4308C31.17 44.5471 30.411 44.5747 24.003 44.5747C17.595 44.5747 16.833 44.5471 14.304 44.4308C11.964 44.3205 10.695 43.9224 9.849 43.5855C8.8065 43.1933 7.86338 42.5675 7.089 41.7541C6.29025 40.9641 5.67517 40.0013 5.289 38.9366C4.962 38.0761 4.569 36.7776 4.464 34.3888C4.35 31.8071 4.326 31.0323 4.326 24.4877C4.326 17.9462 4.35 17.1745 4.464 14.5928C4.572 12.2041 4.962 10.9056 5.292 10.0419C5.727 8.89962 6.249 8.08194 7.089 7.22444C7.929 6.36694 8.727 5.83713 9.849 5.39306C10.695 5.05619 11.964 4.65806 14.304 4.54781C16.518 4.44369 17.376 4.41306 21.849 4.41V4.41613ZM36.813 8.48312C36.4348 8.48312 36.0603 8.55917 35.7109 8.70692C35.3615 8.85467 35.044 9.07123 34.7765 9.34423C34.5091 9.61724 34.297 9.94134 34.1522 10.298C34.0075 10.6547 33.933 11.037 33.933 11.4231C33.933 11.8092 34.0075 12.1915 34.1522 12.5482C34.297 12.9049 34.5091 13.229 34.7765 13.502C35.044 13.775 35.3615 13.9916 35.7109 14.1393C36.0603 14.2871 36.4348 14.3631 36.813 14.3631C37.5768 14.3631 38.3094 14.0534 38.8495 13.502C39.3896 12.9507 39.693 12.2029 39.693 11.4231C39.693 10.6434 39.3896 9.89559 38.8495 9.34423C38.3094 8.79287 37.5768 8.48312 36.813 8.48312ZM24.003 11.9192C22.3682 11.8932 20.7447 12.1994 19.2269 12.8201C17.7092 13.4407 16.3275 14.3633 15.1625 15.5343C13.9974 16.7053 13.0721 18.1011 12.4405 19.6406C11.809 21.1801 11.4837 22.8325 11.4837 24.5015C11.4837 26.1706 11.809 27.823 12.4405 29.3625C13.0721 30.902 13.9974 32.2978 15.1625 33.4688C16.3275 34.6397 17.7092 35.5624 19.2269 36.183C20.7447 36.8036 22.3682 37.1099 24.003 37.0838C27.2386 37.0323 30.3246 35.684 32.5949 33.33C34.8652 30.9759 36.1377 27.805 36.1377 24.5015C36.1377 21.1981 34.8652 18.0271 32.5949 15.6731C30.3246 13.3191 27.2386 11.9708 24.003 11.9192ZM24.003 16.3323C26.125 16.3323 28.1601 17.1928 29.6606 18.7246C31.161 20.2563 32.004 22.3338 32.004 24.5C32.004 26.6662 31.161 28.7437 29.6606 30.2754C28.1601 31.8072 26.125 32.6677 24.003 32.6677C21.881 32.6677 19.8459 31.8072 18.3454 30.2754C16.845 28.7437 16.002 26.6662 16.002 24.5C16.002 22.3338 16.845 20.2563 18.3454 18.7246C19.8459 17.1928 21.881 16.3323 24.003 16.3323Z" fill="white"/>
						  </svg>';
			$html .= $this->_render_date_post( $settings, $item );
			$html .= $this->_render_stats( $settings, $item );
			$html .= $this->_render_caption( $settings, $item );
			$html .= '</div></div>';
		}
		$html .= '</a></div>';
		
		return $html;
	}

	public function _render_card_layout( $item, $settings, $target, $img_alt_content ) {
		$username = ! empty( $item['username'] ) ? $item['username'] : 'Instagram';
		$time_ago = human_time_diff( strtotime( $item['timestamp'] ), current_time( 'timestamp' ) );
		$avatar_letter = strtoupper( substr( $username, 0, 1 ) );
		$slide_class = $settings['layout'] === 'slider' ? ' swiper-slide' : '';
		
		$html = '<div class="tp-instagram__item tp-instagram__item--card' . esc_attr($slide_class) . '">';
		
		// Card Header
		$html .= '<div class="tp-instagram__card-header">';
		$html .= '<div class="tp-instagram__user-info">';
		$html .= '<div class="tp-instagram__avatar"><span>' . esc_html( $avatar_letter ) . '</span></div>';
		$html .= '<span class="tp-instagram__username">' . esc_html( $username ) . '</span>';
		$html .= '<svg class="tp-instagram__verified" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M6.01625 1.7966C6.58251 0.728315 7.70566 0 9 0C10.2943 0 11.4175 0.728315 11.9837 1.79659C13.1395 1.44161 14.4487 1.7208 15.364 2.63604C16.2792 3.55127 16.5584 4.86046 16.2034 6.01625C17.2717 6.58251 18 7.70566 18 9C18 10.2943 17.2717 11.4175 16.2034 11.9838C16.5584 13.1395 16.2792 14.4487 15.364 15.364C14.4487 16.2792 13.1395 16.5584 11.9838 16.2034C11.4175 17.2717 10.2943 18 9 18C7.70566 18 6.58251 17.2717 6.01625 16.2034C4.86046 16.5584 3.55127 16.2792 2.63604 15.364C1.72081 14.4487 1.44162 13.1395 1.7966 11.9838C0.728316 11.4175 0 10.2943 0 9C0 7.70566 0.728315 6.58251 1.79659 6.01625C1.44161 4.86046 1.7208 3.55128 2.63604 2.63604C3.55127 1.7208 4.86046 1.44161 6.01625 1.7966ZM13.2862 6.74152C13.3521 6.80742 13.3521 6.91427 13.2862 6.98017L7.95641 12.31C7.89051 12.3759 7.78366 12.3759 7.71776 12.31L4.77443 9.36665C4.70852 9.30075 4.70852 9.19391 4.77443 9.12801L5.72902 8.17341C5.79492 8.10751 5.90177 8.10751 5.96767 8.17341L7.71776 9.9235C7.78366 9.9894 7.89051 9.9894 7.95641 9.9235L12.093 5.78693C12.1589 5.72102 12.2657 5.72102 12.3316 5.78693L13.2862 6.74152Z" fill="#1DA1F2"/>
				<path d="M13.2862 6.98017C13.3521 6.91427 13.3521 6.80742 13.2862 6.74152L12.3316 5.78693C12.2657 5.72102 12.1589 5.72102 12.093 5.78693L7.95641 9.9235C7.89051 9.9894 7.78366 9.9894 7.71776 9.9235L5.96767 8.17341C5.90177 8.10751 5.79492 8.10751 5.72902 8.17341L4.77443 9.12801C4.70852 9.19391 4.70852 9.30075 4.77443 9.36665L7.71776 12.31C7.78366 12.3759 7.89051 12.3759 7.95641 12.31L13.2862 6.98017Z" fill="white"/>
				</svg>
				';
		$html .= '</div>';
		$html .= '<div class="tp-instagram__menu">';
		$html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
		$html .= '<circle cx="12" cy="5" r="1.5" fill="currentColor"/>';
		$html .= '<circle cx="12" cy="12" r="1.5" fill="currentColor"/>';
		$html .= '<circle cx="12" cy="19" r="1.5" fill="currentColor"/>';
		$html .= '</svg>';
		$html .= '</div>';
		$html .= '</div>';
		
		// Card Image
		$html .= '<a href="' . esc_url( $item['permalink'] ) . '" ' . esc_attr( $target ) . '>';
		if ( $item['media_type'] == 'VIDEO' ) {
			$html .= '<div class="tp-instagram__card-media"><video width="100%" controls>';
			$html .= '<source src="' . esc_url( $item['media_url'] ) . '" type="video/mp4">';
			$html .= '</video></div>';
		} else {
			$image_style = $this->_get_image_style( $settings );
			$html .= '<div class="tp-instagram__card-media">';
			$html .= '<img alt="' . $img_alt_content . '" src="' . esc_url( $item['media_url'] ) . '"' . $image_style . '>';
			$html .= '</div>';
		}
		$html .= '</a>';
		
		// Card Footer
		$html .= '<div class="tp-instagram__card-footer">';
		
	// Action buttons (like, comment, share)
		$html .= '<div class="tp-instagram__actions">';
		$html .= '<div class="tp-instagram__actions-left">';
		if ( $settings['show_likes'] && isset( $item['like_count'] ) ) {
			
		$html .= '<button class="tp-instagram__action-btn">';
		$html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M22.5002 9.18754C22.5018 9.90177 22.3618 10.6092 22.0882 11.269C21.8147 11.9288 21.413 12.5277 20.9065 13.0313L12.5346 21.5269C12.4648 21.5978 12.3816 21.6541 12.2899 21.6925C12.1982 21.7309 12.0997 21.7507 12.0002 21.7507C11.9008 21.7507 11.8023 21.7309 11.7106 21.6925C11.6188 21.6541 11.5356 21.5978 11.4659 21.5269L3.09398 13.0313C2.07332 12.0119 1.49942 10.6287 1.49854 9.18617C1.49766 7.7436 2.06987 6.35977 3.0893 5.3391C4.10872 4.31843 5.49185 3.74453 6.93442 3.74365C8.37698 3.74277 9.76081 4.31499 10.7815 5.33441L12.0002 6.47348L13.2274 5.33066C13.9891 4.57278 14.9582 4.05754 16.0125 3.85001C17.0667 3.64247 18.1588 3.75193 19.1509 4.16458C20.143 4.57722 20.9906 5.27455 21.5867 6.16853C22.1828 7.0625 22.5007 8.11305 22.5002 9.18754Z" fill="#FF0000"/>
				</svg>
				';
				$html .= '<div class="tp-instagram__likes-count">' . number_format( $item['like_count'] ) . '</div>';
				$html .= '</button>';
		}
		// View comments
		if ( $settings['show_comments'] && isset( $item['comments_count'] ) ) {
			
			$html .= '<button class="tp-instagram__action-btn">';
			$html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M12.0001 2.4375C10.3424 2.43715 8.71304 2.86774 7.27195 3.68703C5.83086 4.50632 4.62751 5.68616 3.77995 7.11081C2.93238 8.53545 2.46973 10.156 2.43737 11.8133C2.40501 13.4707 2.80406 15.108 3.59537 16.5647L2.50599 19.8337C2.42887 20.065 2.41768 20.3132 2.47367 20.5504C2.52966 20.7877 2.65063 21.0047 2.823 21.1771C2.99538 21.3494 3.21236 21.4704 3.44962 21.5264C3.68688 21.5824 3.93505 21.5712 4.1663 21.4941L7.43537 20.4047C8.71504 21.0991 10.1364 21.4922 11.591 21.5539C13.0457 21.6156 14.4952 21.3444 15.8291 20.7608C17.163 20.1772 18.346 19.2968 19.288 18.1866C20.2299 17.0764 20.9059 15.7657 21.2644 14.3546C21.6229 12.9435 21.6545 11.4691 21.3567 10.044C21.0588 8.61879 20.4395 7.28044 19.5459 6.13097C18.6523 4.98149 17.508 4.05125 16.2003 3.41117C14.8926 2.77108 13.456 2.43806 12.0001 2.4375ZM12.0001 20.4375C10.5169 20.4379 9.05979 20.0472 7.77568 19.305C7.69007 19.2558 7.59317 19.2296 7.49443 19.2291C7.4339 19.2294 7.3738 19.2392 7.31631 19.2581L3.81099 20.4263C3.77796 20.4373 3.7425 20.4389 3.70861 20.4309C3.67471 20.4229 3.64372 20.4056 3.61909 20.381C3.59447 20.3563 3.57719 20.3253 3.56919 20.2914C3.56119 20.2576 3.56279 20.2221 3.5738 20.1891L4.74193 16.6875C4.7672 16.6118 4.7761 16.5317 4.76801 16.4523C4.75993 16.3729 4.73506 16.2962 4.69506 16.2272C3.76436 14.6195 3.39026 12.7495 3.6308 10.9075C3.87134 9.06544 4.71306 7.35428 6.02539 6.03945C7.33773 4.72463 9.0473 3.87965 10.8889 3.63562C12.7305 3.39159 14.6011 3.76213 16.2106 4.68978C17.8201 5.61742 19.0784 7.05031 19.7904 8.76612C20.5024 10.4819 20.6283 12.3848 20.1484 14.1794C19.6686 15.9741 18.6099 17.5602 17.1366 18.6917C15.6633 19.8232 13.8577 20.4369 12.0001 20.4375Z" fill="black"/>
				</svg>';
			$html .= '<div class="tp-instagram__view-comments">' .  number_format( $item['comments_count'] ) . '</div>';
			$html .= '</button>';
		
		}
	
		$html .= '<button class="tp-instagram__action-btn">';
		$html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M21.1764 2.82379C21.0125 2.6573 20.8065 2.53821 20.5804 2.47913C20.3544 2.42005 20.1165 2.42317 19.892 2.48816H19.8817L1.8883 7.94723C1.63274 8.02124 1.40572 8.17117 1.23731 8.37715C1.0689 8.58312 0.967073 8.83542 0.945315 9.10058C0.923557 9.36575 0.982899 9.63126 1.11548 9.86194C1.24805 10.0926 1.4476 10.2775 1.68767 10.3922L9.70892 14.296L13.6052 22.3125C13.7102 22.5373 13.8772 22.7273 14.0867 22.8602C14.2962 22.9931 14.5393 23.0633 14.7874 23.0625C14.8249 23.0625 14.8633 23.0625 14.9008 23.0578C15.1665 23.037 15.4195 22.9351 15.6255 22.7659C15.8315 22.5968 15.9807 22.3685 16.053 22.1119L21.5092 4.11848V4.10816C21.5746 3.88395 21.5781 3.64625 21.5196 3.42018C21.461 3.19411 21.3424 2.98806 21.1764 2.82379ZM20.4264 3.79879L14.9749 21.7903V21.8007C14.9644 21.838 14.9427 21.8711 14.9127 21.8956C14.8827 21.92 14.8458 21.9346 14.8072 21.9373C14.7686 21.9399 14.7301 21.9306 14.697 21.9105C14.6639 21.8904 14.6378 21.8605 14.6224 21.825L10.8086 13.9894L15.4024 9.39566C15.4546 9.3434 15.4961 9.28136 15.5244 9.21307C15.5526 9.14479 15.5672 9.0716 15.5672 8.99769C15.5672 8.92379 15.5526 8.8506 15.5244 8.78232C15.4961 8.71403 15.4546 8.65199 15.4024 8.59973C15.3501 8.54746 15.2881 8.50601 15.2198 8.47772C15.1515 8.44944 15.0783 8.43488 15.0044 8.43488C14.9305 8.43488 14.8573 8.44944 14.789 8.47772C14.7207 8.50601 14.6587 8.54746 14.6064 8.59973L10.0127 13.1935L2.17048 9.37504C2.13573 9.35884 2.10678 9.33239 2.08753 9.29923C2.06828 9.26607 2.05966 9.22781 2.06282 9.1896C2.06599 9.15139 2.0808 9.11507 2.10524 9.08553C2.12969 9.056 2.1626 9.03467 2.19955 9.02441H2.20986L20.2014 3.57004C20.233 3.56116 20.2665 3.56101 20.2981 3.56959C20.3298 3.57817 20.3586 3.59517 20.3814 3.61879C20.4045 3.64193 20.4212 3.67072 20.4298 3.7023C20.4383 3.73388 20.4385 3.76714 20.4302 3.79879H20.4264Z" fill="black"/>
				</svg>';
		$html .= '</button>';
		$html .= '</div>';
		$html .= '<div class="tp-instagram__actions-right">';
		$html .= '<button class="tp-instagram__action-btn">';
		$html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M17.25 3.1875H6.75C6.4019 3.1875 6.06806 3.32578 5.82192 3.57192C5.57578 3.81806 5.4375 4.1519 5.4375 4.5V21C5.43747 21.1004 5.46432 21.199 5.51527 21.2856C5.56622 21.3721 5.63941 21.4434 5.72724 21.4921C5.81506 21.5408 5.91433 21.5651 6.01472 21.5625C6.11511 21.5599 6.21296 21.5304 6.29813 21.4772L11.9991 17.9147L17.7019 21.4772C17.787 21.5304 17.8849 21.5599 17.9853 21.5625C18.0857 21.5651 18.1849 21.5408 18.2728 21.4921C18.3606 21.4434 18.4338 21.3721 18.4847 21.2856C18.5357 21.199 18.5625 21.1004 18.5625 21V4.5C18.5625 4.1519 18.4242 3.81806 18.1781 3.57192C17.9319 3.32578 17.5981 3.1875 17.25 3.1875ZM17.4375 19.9847L12.2972 16.7728C12.2078 16.7169 12.1045 16.6873 11.9991 16.6873C11.8936 16.6873 11.7903 16.7169 11.7009 16.7728L6.5625 19.9847V4.5C6.5625 4.45027 6.58225 4.40258 6.61742 4.36742C6.65258 4.33225 6.70027 4.3125 6.75 4.3125H17.25C17.2997 4.3125 17.3474 4.33225 17.3826 4.36742C17.4177 4.40258 17.4375 4.45027 17.4375 4.5V19.9847Z" fill="black"/>
					</svg>
					';
		$html .= '</button>';
		$html .= '</div>';
		$html .= '</div>';

		
		// Caption with username
		if ( $settings['show_caption'] && ! empty( $item['caption'] ) ) {
			$caption_length = ( ! empty( $settings['caption_length'] ) & $settings['caption_length'] > 0 ) ? $settings['caption_length'] : 60;
			$html .= '<div class="tp-instagram__caption-wrapper">';
			$html .= '<span class="tp-instagram__caption-username">' . esc_html( $username ) . '</span> ';
			$html .= '<span class="tp-instagram__caption-text">' . substr( $item['caption'], 0, intval( $caption_length ) ) . '...</span>';
			$html .= '</div>';
		}
		// Time
		$html .= '<div class="tp-instagram__time">' . sprintf( __( '%s ago', 'thim-elementor-kit' ), $time_ago ) . '</div>';
		$html .= '</div>';
		
		$html .= '</div>';
		
		return $html;
	}
}
