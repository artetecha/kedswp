<?php

namespace Elementor;

use Thim_EL_Kit\Utilities\Widget_Loop_Trait;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\Models\CourseModel;

defined( 'ABSPATH' ) || exit;

class Thim_Ekit_Widget_Loop_Course_Buttons extends Widget_Button {

	use Widget_Loop_Trait;

	public function get_name() {
		return 'thim-loop-course-buttons';
	}

	public function show_in_panel() {
		$post_type = get_post_meta( get_the_ID(), 'thim_loop_item_post_type', true );
		if ( ! empty( $post_type ) && $post_type == 'lp_course' ) {
			return true;
		}

		return false;
	}

	public function get_title() {
		return esc_html__( 'Item Course Button', 'thim-elementor-kit' );
	}

	public function get_icon() {
		return 'thim-eicon eicon-button';
	}

	public function get_keywords() {
		return array( 'course', 'button', 'enroll', 'purchase' );
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls() {
		parent::register_controls();

		// Text is dynamic from LearnPress (Enroll / Purchase / Continue...) — hide it
		$this->update_control(
			'text',
			array(
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			)
		);

		// Link is always the course permalink — hide it
		$this->update_control(
			'link',
			array(
				'type'    => Controls_Manager::HIDDEN,
				'default' => array( 'url' => '' ),
			)
		);
	}

	protected function render() {
		$course = learn_press_get_course();

		// Editor preview: no real course, fall back to Widget_Button default render
		if ( ! $course ) {
			parent::render();
			return;
		}

		$courseModel = CourseModel::find( $course->get_id(), true );

		if ( ! $courseModel ) {
			parent::render();
			return;
		}

		$singleCourseTemplate = SingleCourseTemplate::instance();
		$btn_text             = $singleCourseTemplate->text_button_course( $courseModel );

		if ( empty( $btn_text ) ) {
			return;
		}

		// Override settings so parent render_button() picks up course data
		$this->set_settings( 'text', $btn_text );
		$this->set_settings( 'link', array(
			'url'         => $course->get_permalink(),
			'is_external' => false,
			'nofollow'    => false,
		) );

		parent::render();
	}
}
