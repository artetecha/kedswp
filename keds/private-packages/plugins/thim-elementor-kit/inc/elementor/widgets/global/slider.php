<?php

namespace Elementor;

use Elementor\Modules\NestedElements\Base\Widget_Nested_Base;
use Elementor\Modules\NestedElements\Controls\Control_Nested_Repeater;
use Thim_EL_Kit\GroupControlTrait;

class Thim_Ekit_Widget_Slider extends Widget_Nested_Base {

	use GroupControlTrait;

	protected function get_default_children_elements() {
		return [];
	}

	protected function get_default_repeater_title_setting_key() {
		return 'slide_title';
	}

	protected function get_default_children_title() {
		/* translators: %d: Slide index. */
		return esc_html__( 'Slide #%d', 'thim-elementor-kit' );
	}

	protected function get_default_children_placeholder_selector() {
		return '.swiper-wrapper';
	}

	public function get_name() {
		return 'thim-ekits-slider';
	}

	public function get_title() {
		return esc_html__( 'Slider', 'thim-elementor-kit' );
	}

	public function get_icon() {
		return 'thim-eicon eicon-slider-3d';
	}

	public function get_style_depends(): array {
		return [ 'e-swiper' ];
	}

	public function get_categories() {
		return array( \Thim_EL_Kit\Elementor::CATEGORY );
	}

	public function get_keywords() {
		return [
			'thim',
			'slider',
			'carousel',
			'swiper',
		];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'setting',
			[
				'label' => esc_html__( 'General', 'thim-elementor-kit' ),
			]
		);

		$this->add_control(
			'data_source',
			[
				'label'              => esc_html__( 'Data Source', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => 'slider',
				'options'            => [
					'slider' => esc_html__( 'Choose from Sliders', 'thim-elementor-kit' ),
					'manual' => esc_html__( 'Create Manually', 'thim-elementor-kit' ),
				],
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'slider_id',
			[
				'label'              => __( 'Slider', 'thim-elementor-kit' ),
				'type'               => \Elementor\Controls_Manager::SELECT2,
				'multiple'           => false,
				'options'            => \Thim_EL_Kit\Elementor::get_cat_taxonomy( 'thim_ekits_slider', false, false ),
				'default'            => 'choose',
				'label_block'        => true,
				'frontend_available' => true,
				'condition'          => [
					'data_source' => 'slider',
				],
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'slide_title',
			[
				'label'   => esc_html__( 'Slide Title', 'thim-elementor-kit' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Slide Title', 'thim-elementor-kit' ),
			]
		);

		$this->add_control(
			'manual_slides',
			[
				'label'       => esc_html__( 'Slides', 'thim-elementor-kit' ),
				'type'        => Control_Nested_Repeater::CONTROL_TYPE,
				'fields'      => $repeater->get_controls(),
				'default'     => [],
				'title_field' => '{{{ slide_title }}}',
				'button_text' => esc_html__( 'Add Slide', 'thim-elementor-kit' ),
				'condition'   => [
					'data_source' => 'manual',
				],
			]
		);

		$this->end_controls_section();

		$this->_register_settings_slider(
			true
		);

		$this->start_injection(
			[
				'at' => 'before',
				'of' => 'slidesPerView',
			]
		);

		$this->add_control(
			'effect',
			[
				'label'              => esc_html__( 'Effect', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => 'carousel',
				'render_type'        => 'template',
				'options'            => [
					'carousel' => esc_html__( 'Carousel', 'thim-elementor-kit' ),
					'fade'     => esc_html__( 'Fade', 'thim-elementor-kit' ),
					'creative' => esc_html__( 'Creative', 'thim-elementor-kit' ),
				],
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'effect_single_slide_notice',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Fade and Creative effects support one slide at a time. Item Show and Item Scroll are only used for Carousel.', 'thim-elementor-kit' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => [
					'effect!' => 'carousel',
				],
			]
		);

		$this->end_injection();

		$this->update_control(
			'slidesPerView',
			[
				'render_type' => 'template',
				'condition'   => [
					'effect' => 'carousel',
				],
				'selectors'   => [
					'{{WRAPPER}}' => '--thim-ekits-slider-show: {{VALUE}}',
				],
			]
		);

		$this->update_control(
			'slidesPerGroup',
			[
				'condition' => [
					'effect' => 'carousel',
				],
			]
		);

		$this->update_control(
			'spaceBetween',
			[
				'render_type' => 'template',
				'condition'   => [
					'effect' => 'carousel',
				],
			]
		);

		$this->update_control(
			'slider_autoplay',
			[
				'default' => '',
			]
		);

		$this->_register_setting_slider_dot_style();

		$this->_register_setting_slider_nav_style();
	}

	protected function render() {
		$settings    = $this->get_settings_for_display();
		$data_source = $settings['data_source'] ?? 'slider';
		$effect      = ! empty( $settings['effect'] ) ? $settings['effect'] : 'carousel';

		$slides = 'manual' === $data_source ? $this->get_manual_slides( $settings ) : $this->get_slider_posts( $settings );

		if ( empty( $slides ) ) {
			return;
		}

		$this->render_nav_pagination_slider( $settings );
		$swiper_class = \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_swiper_latest' ) ? 'swiper' : 'swiper-container';
		$class        = 'thim-ekits-sliders thim-slider-effect-' . sanitize_html_class( $effect ) . ' ' . esc_attr( $swiper_class );
		?>

		<div class="<?php echo esc_attr( $class ); ?>">
			<div class="swiper-wrapper">
				<?php
				foreach ( $slides as $index => $slide ) :
					if ( 'manual' === $data_source ) {
						$this->print_child( $index );
					} else {
						echo '<div class="swiper-slide">';
						\Thim_EL_Kit\Utilities\Elementor::instance()->render_loop_item_content( $slide->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '</div>';
					}
				endforeach;
				?>
			</div>
		</div>

		<?php
	}

	protected function get_slider_posts( array $settings ): array {
		$query_args = array(
			'post_type'           => 'thim_ekits_slide',
			'posts_per_page'      => - 1,
			'orderby'             => 'menu_order',
			'order'               => 'ASC',
			'ignore_sticky_posts' => true,
		);

		// Null-safe: avoid Undefined index warning.
		$slider_id = $settings['slider_id'] ?? '';

		if ( is_numeric( $slider_id ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'thim_ekits_slider',
					'field'    => 'term_id',
					'terms'    => $slider_id,
				),
			);
		} else {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'thim_ekits_slider',
					'field'    => 'slug',
					'terms'    => $slider_id,
				),
			);
		}

		$slides = get_posts( $query_args );

		if ( is_wp_error( $slides ) || empty( $slides ) ) {
			return [];
		}

		return $slides;
	}

	protected function get_manual_slides( array $settings ): array {
		if ( empty( $settings['manual_slides'] ) || ! is_array( $settings['manual_slides'] ) ) {
			return [];
		}

		return array_values( $settings['manual_slides'] );
	}

	public function print_child( $index ) {
		$children = $this->get_children();

		if ( ! empty( $children[ $index ] ) && method_exists( $children[ $index ], 'add_render_attribute' ) ) {
			// Add both classes in a single call to avoid duplicate attribute entries.
			$children[ $index ]->add_render_attribute( '_wrapper', 'class', 'swiper-slide thim-ekits-manual-slide' );
		}

		parent::print_child( $index );
	}

	protected function content_template() {
		?>
		<# const isManualSource='manual'===settings.data_source; #>
		<# const manualSlides=Array.isArray( settings.manual_slides ) ? settings.manual_slides : []; #>
		<# const slideTitleFallback='<?php echo esc_js( __( 'Slide', 'thim-elementor-kit' ) ); ?>' ; #>

		<# if ( isManualSource && manualSlides.length ) { #>
		<div class="thim-slider-editor-tabs" role="tablist"
			aria-label="<?php esc_attr_e( 'Slider Titles', 'thim-elementor-kit' ); ?>">
			<# _.each( manualSlides, function( slide, index ) { #>
			<button type="button" class="thim-slider-editor-tab <# if ( 0 === index ) { #>is-active<# } #>"
					data-slide-index="{{ index }}" role="tab"
					aria-selected="{{ 0 === index ? 'true' : 'false' }}">
				{{{ slide.slide_title || ( slideTitleFallback + ' #' + ( index + 1 ) ) }}}
			</button>
			<# } ); #>
		</div>
		<# } #>

		<# if ( settings.slider_show_pagination && 'none' !==settings.slider_show_pagination ) { #>
		<div class="thim-slider-pagination thim-{{ settings.slider_show_pagination }}"></div>
		<# } #>

		<# if ( 'yes'===settings.slider_show_arrow ) { #>
		<div class="thim-slider-nav thim-slider-nav-prev">
			<# if ( settings.slider_arrows_left && settings.slider_arrows_left.value ) { #>
			<# if ( 'svg'===settings.slider_arrows_left.library ) { #>
			<span class="thim-slider-nav-icon"></span>
			<# } else { #>
			<i class="{{ settings.slider_arrows_left.value }}"
				aria-hidden="true"></i>
			<# } #>
			<# } #>
		</div>
		<div class="thim-slider-nav thim-slider-nav-next">
			<# if ( settings.slider_arrows_right && settings.slider_arrows_right.value ) { #>
			<# if ( 'svg'===settings.slider_arrows_right.library ) { #>
			<span class="thim-slider-nav-icon"></span>
			<# } else { #>
			<i class="{{ settings.slider_arrows_right.value }}"
				aria-hidden="true"></i>
			<# } #>
			<# } #>
		</div>
		<# } #>

		<div
			class="thim-ekits-sliders thim-slider-effect-{{ settings.effect || 'carousel' }} swiper">
			<div class="swiper-wrapper">
				<# if ( ! isManualSource && ( ! settings.slider_id
				|| 'choose'===settings.slider_id ) ) { #>
				<div style="padding:30px;text-align:center;color:#888;width:100%;">
					<i class="eicon-info" style="color:#f0ad4e;"></i>
					<?php esc_html_e( 'Please select a slider from the panel.', 'thim-elementor-kit' ); ?>
				</div>
				<# } #>
			</div>
		</div>
		<?php
	}
}
