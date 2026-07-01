<?php

namespace Elementor;

use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use Thim_EL_Kit\Elementor;

class Thim_Ekit_Widget_Course_Price extends Widget_Base {

	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
	}

	public function get_name() {
		return 'thim-ekits-course-price';
	}

	public function get_title() {
		return esc_html__( ' Course Price', 'thim-elementor-kit' );
	}

	public function get_icon() {
		return 'thim-eicon eicon-price-list';
	}

	public function get_categories() {
		return array( Elementor::CATEGORY_SINGLE_COURSE );
	}

	public function get_help_url() {
		return '';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_price_style',
			array(
				'label' => esc_html__( 'Price', 'thim-elementor-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'text_align',
			array(
				'label'     => esc_html__( 'Alignment', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
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
				'selectors' => array(
					'{{WRAPPER}}' => 'text-align: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => esc_html__( 'Color', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .thim-ekit-single-course__price__price' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .thim-ekit-single-course__price__price',
			)
		);

		$this->add_control(
			'regular_heading',
			array(
				'label'     => esc_html__( 'Origin Price', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'regular_price_color',
			array(
				'label'     => esc_html__( 'Color', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .thim-ekit-single-course__price__origin' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'regular_price_typography',
				'selector' => '{{WRAPPER}} .thim-ekit-single-course__price__origin',
			)
		);

		$this->add_control(
			'free_heading',
			array(
				'label'     => esc_html__( 'Free Price', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'free_price_color',
			array(
				'label'     => esc_html__( 'Color', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .thim-ekit-single-course__price__price.free' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'free_price_typography',
				'selector' => '{{WRAPPER}} .thim-ekit-single-course__price__price.free',
			)
		);

		$this->add_control(
			'prefix_suffix_heading',
			array(
				'label'     => esc_html__( 'Prefix and Suffix Price', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'prefix_suffix_price_color',
			array(
				'label'     => esc_html__( 'Color', 'thim-elementor-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .course-price-prefix, {{WRAPPER}} .course-price-suffix' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'prefix_suffix_price_typography',
				'selector' => '{{WRAPPER}} .course-price-prefix, {{WRAPPER}} .course-price-suffix',
			)
		);

		$this->end_controls_section();
	}

	public function render() {
		do_action( 'thim-ekit/modules/single-course/before-preview-query' );
		$course = learn_press_get_course();

		if ( ! $course ) {
			return;
		}
		$courseModel = CourseModel::find( $course->get_id(), true );
		$userModel   = UserModel::find( get_current_user_id(), true );
		if ( $userModel instanceof UserModel ) {
			$userCourse = UserCourseModel::find( $userModel->get_id(), $courseModel->get_id(), true );
			if ( $userCourse && ( $userCourse->has_enrolled_or_finished() || $userCourse->has_purchased() ) ) {
				return;
			}
		}

		$singleCourseTemplate = SingleCourseTemplate::instance();
		?>

		<div class="thim-ekit-single-course__price">

			<?php
			if ( is_callable( [ $singleCourseTemplate, 'html_price_prefix' ] ) ) {
				$price_prefix = $singleCourseTemplate->html_price_prefix( $courseModel );
				if ( ! empty( $price_prefix ) ) {
					echo wp_kses_post( $price_prefix );
				}
			}
			if ( $course->is_free() ) {
				printf( '<span class="thim-ekit-single-course__price__price free">%s</span>', esc_html__( 'Free', 'learnpress' ) );
			} else {
				if ( $course->has_sale_price() ) {
					printf( '<span class="thim-ekit-single-course__price__origin">%s</span>', $course->get_regular_price_html() );
				}
				printf( '<span class="thim-ekit-single-course__price__price">%s</span>', learn_press_format_price( $course->get_price(), true ) );
			}
			if ( is_callable( [ $singleCourseTemplate, 'html_price_suffix' ] ) ) {
				$price_suffix = $singleCourseTemplate->html_price_suffix( $courseModel );
				if ( ! empty( $price_suffix ) ) {
					echo wp_kses_post( $price_suffix );
				}
			} ?>
		</div>

		<?php
		do_action( 'thim-ekit/modules/single-course/after-preview-query' );
	}
}
