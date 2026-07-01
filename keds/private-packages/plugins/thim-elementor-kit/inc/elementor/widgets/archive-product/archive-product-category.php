<?php

namespace Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;

use Thim_EL_Kit\GroupControlTrait;

/**
 * Widget Archive Product Category - Displays product categories in archive pages
 * Features:
 * - Only shows in product archive pages
 * - Hides current term when viewing that term's archive
 * - Show All button to display all products
 * - Hook for category image filter
 * - Slider support
 */
class Thim_Ekit_Widget_Archive_Product_Category extends Widget_Base { 
	use GroupControlTrait;

	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
	}

	public function get_name() {
		return 'thim-ekits-archive-product-category';
	}

	public function get_title() {
		return esc_html__( 'Archive Product Categories', 'thim-elementor-kit' );
	}

	public function get_icon() {
		return 'thim-eicon eicon-product-categories';
	}

	public function get_categories() {
		return array( \Thim_EL_Kit\Elementor::CATEGORY_ARCHIVE_PRODUCT );
	}

	public function get_keywords() {
		return [
			'thim',
			'categories',
			'archive',
			'product',
			'filter',
			'slider',
		];
	}

	public function get_style_depends(): array {
		return ['e-swiper'];
	}

	public function get_script_depends(): array {
		return ['thim-ekit-slider'];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout Settings', 'thim-elementor-kit' ),
			)
		);

		$this->add_control(
			'display_type',
			array(
				'label'   => esc_html__( 'Display Type', 'thim-elementor-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'list',
				'options' => array(
					'list'   => esc_html__( 'List (Inline)', 'thim-elementor-kit' ),
					'grid'   => esc_html__( 'Grid', 'thim-elementor-kit' ),
					'slider' => esc_html__( 'Slider', 'thim-elementor-kit' ),
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'     => esc_html__( 'Columns', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'size' => 4,
				),
				'tablet_default' => array(
					'size' => 3,
				),
				'mobile_default' => array(
					'size' => 2,
				),
				'range'     => array(
					'px' => array(
						'min' => 1,
						'max' => 12,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .thim-archive-categories.thim-archive-categories-grid' => 'grid-template-columns: repeat({{SIZE}}, 1fr);',
				),
				'condition' => array(
					'display_type' => 'grid',
				),
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'     => esc_html__( 'Column Gap', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'size' => 10,
				),
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .thim-archive-categories.thim-archive-categories-grid' => 'gap: {{SIZE}}{{UNIT}};',
				),
				'condition' => array(
					'display_type' => 'grid',
				),
			)
		);


		$this->add_control(
			'hide_current_term',
			array(
				'label'        => esc_html__( 'Hide Current Term', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'thim-elementor-kit' ),
				'label_off'    => esc_html__( 'No', 'thim-elementor-kit' ),
				'return_value' => 'yes',
				'default'      => 'no',
				'description'  => esc_html__( 'Hide the current category when viewing its archive page', 'thim-elementor-kit' ),
			)
		);

		$this->add_control(
			'show_counts',
			array(
				'label'        => esc_html__( 'Show Product Count', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'thim-elementor-kit' ),
				'label_off'    => esc_html__( 'Hide', 'thim-elementor-kit' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label'        => esc_html__( 'Show Category Image', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'thim-elementor-kit' ),
				'label_off'    => esc_html__( 'Hide', 'thim-elementor-kit' ),
				'return_value' => 'yes',
				'default'      => 'no',
				'description'  => esc_html__( 'Show category thumbnail image if available', 'thim-elementor-kit' ),
			)
		);

		$this->add_control(
			'hide_empty',
			array(
				'label'        => esc_html__( 'Hide Empty Categories', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'thim-elementor-kit' ),
				'label_off'    => esc_html__( 'No', 'thim-elementor-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'       => esc_html__( 'Limit', 'thim-elementor-kit' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => -1,
				'max'         => 100,
				'step'        => 1,
				'default'     => -1,
				'description' => esc_html__( 'Set -1 to show all categories', 'thim-elementor-kit' ),
			)
		);

		$this->add_control(
			'only_parent',
			array(
				'label'        => esc_html__( 'Only Parent Categories', 'thim-elementor-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'thim-elementor-kit' ),
				'label_off'    => esc_html__( 'No', 'thim-elementor-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// Slider Settings
		$this->_register_settings_slider();

		// Style Controls
		$this->register_style_wrapper();
		$this->register_style_category_item();
		$this->register_style_icon(); // Only shows when filter provides image
		
		// Slider Style Controls
		$this->_register_setting_slider_dot_style( array( 'display_type' => 'slider' ,'slider_show_pagination!' => 'none' ) );
		$this->_register_setting_slider_nav_style( array( 'display_type' => 'slider','slider_show_arrow' => 'yes' ) ); 
	}

	protected function _register_settings_slider() {
		$this->start_controls_section(
			'slider_settings_section',
			[
				'label'     => esc_html__( 'Slider Settings', 'thim-elementor-kit' ),
				'condition' => [
					'display_type' => 'slider',
				],
			]
		);

		$this->add_responsive_control(
			'slidesPerView',
			array(
				'label'              => esc_html__( 'Slides Per View', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::NUMBER,
				'min'                => 1,
				'max'                => 20,
				'step'               => 1,
				'default'            => 6,
				'tablet_default'     => 4,
				'mobile_default'     => 2,
				'frontend_available' => true,
				'devices'            => array('widescreen', 'desktop', 'tablet', 'mobile'),
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
				'default'            => 10,
				'frontend_available' => true,
				'devices'            => array('widescreen', 'desktop', 'tablet', 'mobile'),
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
				'default'            => 'no',
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
			'slider_loop',
			[
				'label'              => esc_html__( 'Enable Loop?', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::SWITCHER,
				'label_on'           => esc_html__( 'Yes', 'thim-elementor-kit' ),
				'label_off'          => esc_html__( 'No', 'thim-elementor-kit' ),
				'return_value'       => 'yes',
				'default'            => 'no',
				'frontend_available' => true,
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
			]
		);

		$this->add_control(
			'slider_show_pagination',
			[
				'label'              => esc_html__( 'Pagination Options', 'thim-elementor-kit' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => 'none',
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

	protected function register_style_wrapper() {
		$this->start_controls_section(
			'section_style_wrapper',
			array(
				'label' => esc_html__( 'Wrapper', 'thim-elementor-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition'  => array(
					'display_type' => 'list',
				),
			)
		);

		$this->add_responsive_control(
			'wrapper_gap',
			array(
				'label'      => esc_html__( 'Gap Between Items', 'thim-elementor-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'default'    => array(
					'size' => 10,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .thim-archive-categories:not(.thim-archive-categories-slider)' => 'gap: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'display_type' => 'list',
				),
			)
		);

		$this->add_responsive_control(
			'wrapper_align',
			array(
				'label'     => esc_html__( 'Alignment', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => esc_html__( 'Start', 'thim-elementor-kit' ),
						'icon'  => 'eicon-h-align-left',
					),
					'center'     => array(
						'title' => esc_html__( 'Center', 'thim-elementor-kit' ),
						'icon'  => 'eicon-h-align-center',
					),
					'flex-end'   => array(
						'title' => esc_html__( 'End', 'thim-elementor-kit' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'default'   => 'flex-start',
				'selectors' => array(
					'{{WRAPPER}} .thim-archive-categories:not(.thim-archive-categories-slider)' => 'justify-content: {{VALUE}};',
				),
				'condition' => array(
					'display_type' => 'list',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function register_style_category_item() {
		$this->start_controls_section(
			'section_style_item',
			array(
				'label' => esc_html__( 'Category Item', 'thim-elementor-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'item_typography',
				'label'    => esc_html__( 'Name Typography', 'thim-elementor-kit' ),
				'selector' => '{{WRAPPER}} .thim-archive-category-item .category-name',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'count_typography',
				'label'     => esc_html__( 'Count Typography', 'thim-elementor-kit' ),
				'selector'  => '{{WRAPPER}} .thim-archive-category-item .category-count',
				'condition' => array(
					'show_counts' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'item_padding',
			array(
				'label'      => esc_html__( 'Padding', 'thim-elementor-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top'    => 8,
					'right'  => 16,
					'bottom' => 8,
					'left'   => 16,
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .thim-archive-category-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'item_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'thim-elementor-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array(
					'top'    => 50,
					'right'  => 50,
					'bottom' => 50,
					'left'   => 50,
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .thim-archive-category-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_item_style' );

		$this->start_controls_tab(
			'tab_item_normal',
			array(
				'label' => esc_html__( 'Normal', 'thim-elementor-kit' ),
			)
		);

		$this->add_control(
			'item_color',
			array(
				'label'     => esc_html__( 'Text Color', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#333333',
				'selectors' => array(
					'{{WRAPPER}} .thim-archive-category-item' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'item_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .thim-archive-category-item' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'item_border',
				'selector' => '{{WRAPPER}} .thim-archive-category-item',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'item_box_shadow',
				'selector' => '{{WRAPPER}} .thim-archive-category-item',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_item_hover',
			array(
				'label' => esc_html__( 'Hover/Active', 'thim-elementor-kit' ),
			)
		);

		$this->add_control(
			'item_color_hover',
			array(
				'label'     => esc_html__( 'Text Color', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .thim-archive-category-item:hover, {{WRAPPER}} .thim-archive-category-item.is-active' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'item_bg_color_hover',
			array(
				'label'     => esc_html__( 'Background Color', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#E57A35',
				'selectors' => array(
					'{{WRAPPER}} .thim-archive-category-item:hover, {{WRAPPER}} .thim-archive-category-item.is-active' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'item_border_color_hover',
			array(
				'label'     => esc_html__( 'Border Color', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .thim-archive-category-item:hover, {{WRAPPER}} .thim-archive-category-item.is-active' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function register_style_icon() {
		$this->start_controls_section(
			'section_style_icon',
			array(
				'label'     => esc_html__( 'Category Icon/Image', 'thim-elementor-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => esc_html__( 'Icon Size', 'thim-elementor-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 10,
						'max' => 100,
					),
				),
				'default'    => array(
					'size' => 20,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .thim-archive-category-item .category-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .thim-archive-category-item .category-icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; object-fit: contain;',
				),
			)
		);

		$this->add_responsive_control(
			'icon_spacing',
			array(
				'label'      => esc_html__( 'Spacing', 'thim-elementor-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 30,
					),
				),
				'default'    => array(
					'size' => 8,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .thim-archive-category-item .category-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}
	public function render() {
		$settings = $this->get_settings_for_display();

		// Get current queried object (for detecting current category)
		$current_term    = get_queried_object();
		$current_term_id = 0;
		$is_shop_page    = is_shop();
		$shop_url        = wc_get_page_permalink( 'shop' );

		if ( is_a( $current_term, 'WP_Term' ) && $current_term->taxonomy === 'product_cat' ) {
			$current_term_id = $current_term->term_id;
		}

		// Build query args for categories
		$cat_args = array(
			'taxonomy'   => 'product_cat',
			'orderby'    => 'name',
			'order'      => 'asc',
			'hide_empty' => $settings['hide_empty'] === 'yes',
			'parent'     => $settings['only_parent'] === 'yes' ? 0 : '',
		);

		// Apply limit
		$limit = isset( $settings['limit'] ) ? intval( $settings['limit'] ) : -1;
		if ( $limit > 0 ) {
			$cat_args['number'] = $limit;
		}

		// Get categories
		$categories = get_terms( $cat_args );

		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			return;
		}

		// Filter: Hide current term if enabled
		if ( $settings['hide_current_term'] === 'yes' && $current_term_id > 0 ) {
			$categories = array_filter( $categories, function( $cat ) use ( $current_term_id ) {
				return $cat->term_id !== $current_term_id;
			});
		}

		// Render navigation and pagination for slider layout
		if ( $settings['display_type'] === 'slider' ) {
			$this->render_nav_pagination_slider( $settings );
		}

		// Render based on display type
		if ( $settings['display_type'] === 'slider' ) {
			$this->render_slider_layout( $categories, $settings, $current_term_id, $is_shop_page, $shop_url );
		} elseif ( $settings['display_type'] === 'grid' ) {
			$this->render_grid_layout( $categories, $settings, $current_term_id, $is_shop_page, $shop_url );
		} else {
			$this->render_list_layout( $categories, $settings, $current_term_id, $is_shop_page, $shop_url );
		}
	}

	protected function render_list_layout( $categories, $settings, $current_term_id, $is_shop_page, $shop_url ) {
		?>
		<div class="thim-archive-categories thim-archive-categories-list">
			<?php $this->render_category_items( $categories, $settings, $current_term_id, false ); ?>
		</div>
		<?php
	}

	protected function render_grid_layout( $categories, $settings, $current_term_id, $is_shop_page, $shop_url ) {
		?>
		<div class="thim-archive-categories thim-archive-categories-grid">
			<?php $this->render_category_items( $categories, $settings, $current_term_id, false ); ?>
		</div>
		<?php
	}

	protected function render_slider_layout( $categories, $settings, $current_term_id, $is_shop_page, $shop_url ) {
		$swiper_class = \Elementor\Plugin::$instance->experiments->is_feature_active('e_swiper_latest') ? 'swiper' : 'swiper-container';
		$slider_class = 'thim-archive-categories thim-archive-categories-slider thim-ekits-sliders ' . esc_attr($swiper_class);
		?>
		<div class="<?php echo esc_attr($slider_class); ?>">
			<div class="swiper-wrapper">
				<?php $this->render_category_items( $categories, $settings, $current_term_id, true ); ?>
			</div>
		</div>
		<?php
	}

	protected function render_category_items( $categories, $settings, $current_term_id, $is_slider = false ) {
		foreach ( $categories as $category ) :
			$category_link = get_term_link( $category );
			if ( is_wp_error( $category_link ) ) {
				continue;
			}

			$is_active = $current_term_id === $category->term_id ? ' is-active' : '';

			// Get category image
			$category_image = '';
			
			if ( $settings['show_image'] === 'yes' ) {
				// Try to get WooCommerce category thumbnail
				$thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
				if ( $thumbnail_id ) {
					$image_url = wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' );
					if ( $image_url ) {
						$category_image = '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $category->name ) . '">';
					}
				}
			}
			if ( $is_slider ) : ?>
				<div class="swiper-slide">
			<?php endif; ?>
					<a href="<?php echo esc_url( $category_link ); ?>" class="thim-archive-category-item<?php echo esc_attr( $is_active ); ?>" data-term-id="<?php echo esc_attr( $category->term_id ); ?>">
						<?php if ( ! empty( $category_image ) ) : ?>
							<span class="category-icon"><?php echo $category_image; ?></span>
						<?php endif; ?>
						<span class="category-name"><?php echo esc_html( $category->name ); ?></span>
						<?php if ( $settings['show_counts'] === 'yes' ) : ?>
							<span class="category-count">(<?php echo esc_html( $category->count ); ?>)</span>
						<?php endif; ?>
					</a>
			<?php if ( $is_slider ) : ?>
				</div>
			<?php endif;
		endforeach;
	}
}
