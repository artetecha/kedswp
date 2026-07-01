<?php

/**
 * Class Thim_LP_Filter_Layout
 *
 * @since   from LP v4.2.5.6
 * @version 1.0.0
 */

use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseOfflineTemplate;

class Thim_LP_Layout {

	public function __construct() {
		add_filter( 'lp/show-archive-course/title', '__return_false' );
		// Section top
		add_filter( 'learn-press/layout/list-courses/section/top', array( $this, 'course_layout_archive_section_top' ), 10, 3 );

		add_filter( 'learn-press/layout/list-courses/section', array( $this, 'layout_list_courses_section' ), 10, 3 );
		// Content item
		add_filter( 'learn-press/layout/list-courses/item/section-top', array( $this, 'course_item_section_top' ), 10, 3 );
		add_filter( 'learn-press/layout/list-courses/item/section/bottom', array( $this, 'course_item_section_bottom' ), 15, 3 );
		// remove div wrapper li
		add_filter( 'learn-press/layout/list-courses/item-li', array( $this, 'course_item_li' ), 10, 3 );
		//Remove div wrapper image
		add_filter( 'learn-press/course/html-image', array( $this, 'thim_course_html_image' ), 10 );

		// fix for LP < 2.7.5.
		add_filter( 'learn-press/list-courses/layout/section', array( $this, 'thim_list_course_layout_section_old' ), 10, 3 );
		if ( ! thim_is_new_learnpress( '4.2.7.5' ) ) {
			add_filter( 'learn-press/list-courses/layout/section/top', array( $this, 'thim_list_course_layout_section_top_old' ), 10, 3 );
		}

		add_action( 'learn-press/after-main-content', array( $this, 'thim_lp_show_courses_after_archive' ), 10 );
		// instructor Course
		add_filter( 'learn-press/single-instructor/courses/sections', array( $this, 'lp_instructor_courses_sections' ), 10, 3 );

		// Single Course
		add_filter( 'learn-press/course/html-curriculum', array( $this, 'remove_title_of_course_html_curriculum' ), 10, 4 );

		//
		add_filter( 'learn-press/single-course/offline/sections', array( $this, 'lp_single_courses_offline_sections' ), 10 );
		// Mesage install add-on
		add_filter( 'learn-press/admin/manager-addons/section', array( $this, 'lp_admin_manager_addons_section' ), 10 );
		// Layout collection
		add_filter( 'learn-press/layout/list-collections/section', array( $this, 'lp_list_collection_layout' ), 10, 3 );

		add_filter(
			'learn-press/single-collection/layout/section',
			function ( $sections ) {
				$sections['wrap'] = '<div class="lp-single-collection">' . $this->top_heading_collection();

				return $sections;
			},
			10,
			2
		);
		//      add_filter( 'learn-press/single-collection/layout/section-top', array( $this, 'lp_single_collection_layout_section_top' ), 10, 2 );
		add_filter(
			'learn-press/single-collection-learning/layout/section',
			function ( $sections ) {
				$sections['container'] = '<div class="lp-collection-learning">' . $this->top_heading_collection();

				return $sections;
			},
			10,
			2
		);
	}

	public function course_layout_archive_section_top( $sections, $course, $settings ) {
		$listCoursesTemplate = ListCoursesTemplate::instance();
		$section             = array(
			'wrapper'                   => '<div class="lp-courses-bar switch-layout-container">',
			'switch_layout'             => $this->html_switch_layout(),
			'courses_result'            => sprintf( '<div class="course-index">%s</div>', $listCoursesTemplate->html_courses_page_result( $settings ) ),
			'order_by'                  => $sections['order_by'] ?? '',
			'btn_filter_courses_mobile' => $sections['btn_filter_courses_mobile'],
			'search'                    => sprintf( '<div class="courses-searching">%s</div>', $sections['search'] ),
			'wrapper_end'               => '</div>',
		);

		if ( ! get_theme_mod( 'thim_display_course_sort', true ) ) {
			unset( $section['order_by'] );
		}

		return $section;
	}

	public function html_switch_layout() {
		$layouts = learn_press_courses_layouts();
		$active  = learn_press_get_courses_layout();

		$html_layouts = '';
		foreach ( $layouts as $layout => $value ) {
			if ( $layout == 'grid' ) {
				$icon = 'th-large';
			} else {
				$icon = $layout;
			}
			$html_layouts .= sprintf(
				'<input type="radio" name="lp-switch-layout-btn" value="%s" id="lp-switch-layout-btn-%s" %s>
				<label class="lp-switch-btn %s" title="%s" for="lp-switch-layout-btn-%s"><i class="' . eduma_font_icon( '%s' ) . '"></i></label>',
				esc_attr( $layout ),
				esc_attr( $layout ),
				checked( $layout, $active, false ),
				esc_attr( $layout ),
				sprintf( esc_attr__( 'Switch to %s', 'learnpress' ), $value ),
				esc_attr( $layout ),
				esc_attr( $icon )
			);
		}

		return '<div class="switch-layout">' . $html_layouts . '</div>';
	}

	public function layout_list_courses_section( $sections, $courses, $settings ) {
		$skin = $settings['skin'] ?? ( wp_is_mobile() ? 'grid' : learn_press_get_courses_layout() );
		// HTML section courses.
		$html_courses = '';
		if ( empty( $courses ) ) {
			$html_courses = Template::print_message( __( 'No courses found', 'learnpress' ), 'info', false );
		} else {
			global $post;
			foreach ( $courses as $courseObj ) {
				$course = CourseModel::find( $courseObj->ID, true );
				if ( ! $course ) {
					continue;
				}
				$post = get_post( $course->get_id() );
				setup_postdata( $post );
				ob_start();
				learn_press_get_template_part( 'content', 'course' );
				$html_courses .= ob_get_clean();
			}
			wp_reset_postdata();
		}

		$section_courses = [
			'wrapper'     => sprintf( '<div id="thim-course-archive" class="thim-course-%1$s"><ul class="learn-press-courses lp-list-courses-no-css %1$s" data-layout="%1$s">', $skin ),
			'courses'     => $html_courses,
			'wrapper_end' => '</ul></div>',
		];

		$sections['courses'] = Template::combine_components( $section_courses );

		return $sections;
	}

	public function course_item_li( $sections ) {
		if ( get_post_type() == 'lp_collection' ) {

		} else {
			unset( $sections['wrapper_li'] );
			unset( $sections['wrapper_li_end'] );
		}

		return $sections;
	}

	public function course_item_section_top( $sections, $course, $settings ) {
		$singleCourseTemplate = SingleCourseTemplate::instance();
		$section['wrapper']   = $sections['wrapper'];

		$section['img'] = sprintf( '<a href="%s" class="thumb">%s</a>', esc_url( $course->get_permalink() ), $singleCourseTemplate->html_image( $course ) );

		/*ob_start();
		if ( class_exists( 'LP_Addon_Wishlist' ) ) {
			LP_Addon_Wishlist_Preload::$addon->wishlist_button( $course->get_id() );
		}
		$wishlist_html       = ob_get_clean();
		$section['wishlist'] = $wishlist_html;*/

		$section['read_more'] = '<a class="course-readmore" href="' . esc_url( get_the_permalink( $course->get_id() ) ) . '" >' . esc_html__( 'Enroll Now', 'eduma' ) . '</a>';

		if ( class_exists( 'LP_WC_Hooks' ) && method_exists( 'LP_WC_Hooks', 'button_add_to_cart' ) ) {
			$section_btn = LP_WC_Hooks::instance()->button_add_to_cart( $section, $course, '' );
			if ( isset( $section_btn['button_add_to_cart'] ) && ! empty( $section_btn['button_add_to_cart'] ) ) {
				$section['read_more'] = $section_btn['button_add_to_cart'];
			}
		}

		if ( thim_lp_style_content_course() == 'layout_style_2' ) {
			$section['price'] = $singleCourseTemplate->html_price( $course );
		}

		$section ['wrapper_end'] = $sections['wrapper_end'];

		return $section;
	}

	public function course_item_section_bottom( $sections, $course, $settings ) {

		$html_review = $html_after_title = '';

		if ( ! $course instanceof CourseModel ) {
			$course = CourseModel::find( $course->get_id(), true );
		}
		$singleCourseTemplate = SingleCourseTemplate::instance();
		$author               = $singleCourseTemplate->html_instructor( $course, true );

		$count_student  = $course->get_total_user_enrolled_or_purchased();
		$count_student += $course->get_fake_students();

		$html_lesson = $singleCourseTemplate->html_count_item( $course, LP_LESSON_CPT, true );
		if ( $course->is_offline() ) {
			$singleCourseOfflineTemplate = SingleCourseOfflineTemplate::instance();
			if ( ! empty( $meta_data['lesson'] ) ) {
				$html_lesson = $singleCourseOfflineTemplate->html_lesson_info( $course, false );
			}
		}

		$html_before_title = $author;

		// change html for layout style 2
		if ( thim_lp_style_content_course() == 'layout_style_2' ) {
			$html_review       = $sections['review'] ?? '';
			$html_after_title  = $author;
			$html_before_title = list_item_course_cat( $course->get_id(), true );
		}

		// HTML bottom section end.
		$section_bottom_end = apply_filters(
			'learn-press/layout/list-courses/item/section/bottom/end',
			[
				'short_des'   => sprintf(
					'<div class="course-description">%s</div>',
					$singleCourseTemplate->html_short_description( $course, intval( get_theme_mod( 'thim_learnpress_excerpt_length', 25 ) ) )
				),
				'wrapper'     => '<div class="course-meta">',
				'author'      => $author,
				'lesson'      => sprintf(
					'<div class="course-lesson"><i class="lp-icon-file"></i> %s</div>',
					$html_lesson
				),
				'student'     => sprintf(
					'<div class="course-students"><i class="lp-icon-students"></i><div class="course-count-item lp_student">%s</div></div>',
					$count_student
				),
				'price'       => $singleCourseTemplate->html_price( $course ),
				'review'      => $html_review,
				'wrapper_end' => '</div>',
			],
			$course,
			$settings
		);

		if ( class_exists( 'LP_Addon_Coming_Soon_Courses' ) && learn_press_is_coming_soon( $course->get_id() ) ) {
			$section_bottom_end = apply_filters(
				'learn-press/layout/list-courses/item/section/bottom/end',
				[
					'short_des'   => sprintf(
						'<div class="course-description">%s</div>',
						$singleCourseTemplate->html_short_description( $course, intval( get_theme_mod( 'thim_learnpress_excerpt_length', 25 ) ) )
					),
					'coming_soon' => sprintf(
						'<div class="message message-warning learn-press-message coming-soon-message">%s</div>',
						esc_html__( 'Coming soon', 'eduma' )
					),
				],
				$course,
				$settings
			);
		}

		// Remove Default Review
		unset( $sections['review'] );

		$section = [
			'wrapper'      => '<div class="thim-course-content">',
			'before_title' => $html_before_title,
			'title'        => sprintf(
				'<h2 class="course-title"><a class="course-permalink" href="%s">%s</a></h2>',
				esc_url( $course->get_permalink() ),
				esc_html( $course->get_title() )
			),
			'after_title'  => $html_after_title,
			'info'         => Template::combine_components( $section_bottom_end ),
			'wrapper_end'  => '</div>',
		];

		return $section;
	}

	public function thim_course_html_image( $sections ) {
		unset( $sections['wrapper'] );
		unset( $sections['wrapper_end'] );

		return $sections;
	}

	public function thim_list_course_layout_section_old( $sections, $courses, $settings ) {
		$skin = learn_press_get_courses_layout();
		// Handle layout
		$html_courses_wrapper = [
			'<div id="thim-course-archive" class="thim-course-' . $skin . '"><ul class="learn-press-courses">' => '</ul></div>',
		];
		ob_start();
		if ( empty( $courses ) ) {
			echo sprintf( '<p class="learn-press-message success">%s!</p>', __( 'No courses found', 'learnpress' ) );
		} else {
			global $post;
			foreach ( $courses as $courseObj ) {
				$course = learn_press_get_course( $courseObj->ID );
				$post   = get_post( $course->get_id() );
				setup_postdata( $post );
				learn_press_get_template_part( 'content', 'course' );
			}
		}
		$html_courses = Template::instance()->nest_elements( $html_courses_wrapper, ob_get_clean() );
		wp_reset_postdata();

		$sections['courses'] = [
			'text_html' => $html_courses,
		];

		return $sections;
	}

	public function thim_list_course_layout_section_top_old( $sections, $courses, $settings ) {

		$listCoursesTemplate = ListCoursesTemplate::instance();
		$section             = [
			'wrapper'        => [ 'text_html' => '<div class="thim-course-top lp-courses-bar switch-layout-container">' ],
			'switch_layout'  => [ 'text_html' => $this->html_switch_layout() ],
			'courses_result' => [ 'text_html' => sprintf( '<div class="course-index">%s</div>', $listCoursesTemplate->html_courses_page_result( $settings ) ) ],
			'order_by'       => [ 'text_html' => $listCoursesTemplate->html_order_by( $settings['order_by'] ?? 'post_date' ) ],
			'search'         => [ 'text_html' => sprintf( '<div class="courses-searching">%s</div>', $listCoursesTemplate->html_search_form( $settings ) ) ],
			'close_wrapper'  => [ 'text_html' => '</div>' ],
		];
		if ( ! get_theme_mod( 'thim_display_course_sort', true ) ) {
			unset( $section['order_by'] );
		}

		return $section;
	}

	public function thim_lp_show_courses_after_archive() {

		if ( ! function_exists( 'learn_press_is_courses' ) ) {
			return;
		}

		if ( ! ( learn_press_is_courses() || is_post_type_archive( 'lp_course' ) ) ) {
			return;
		}

		if ( ! get_theme_mod( 'thim_learnpress_cate_show_new', false ) ) {
			return;
		}

		$post_per_page = get_theme_mod( 'thim_learnpress_cate_new_number', 3 );

		$args = array(
			'post_type'      => 'lp_course',
			'posts_per_page' => $post_per_page,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$courses = get_posts( $args );

		if ( empty( $courses ) ) {
			return;
		}

		echo '<div class="thim-courses-after-archive">';
		echo '<h2 class="thim-courses-after-archive__title">' . esc_html__( 'New Courses', 'your-textdomain' ) . '</h2>';
		echo '<ul class="learn-press-courses thim-course-grid">';

		global $post;

		foreach ( $courses as $courseObj ) {

			$course = learn_press_get_course( $courseObj->ID );

			if ( ! $course ) {
				continue;
			}

			$post = get_post( $course->get_id() );
			setup_postdata( $post );
			learn_press_get_template_part( 'content', 'course' );
		}

		wp_reset_postdata();

		echo '</ul>';
		echo '</div>';
	}

	public function remove_title_of_course_html_curriculum( $sections ) {
		unset( $sections['title'] );

		return $sections;
	}

	public function lp_instructor_courses_sections( $sections, $courses, $settings ) {
		$sections['wrapper'] = '<div class="instructor-courses learn-press-courses thim-course-grid">';

		return $sections;
	}

	public function lp_single_courses_offline_sections( $sections ) {
		ob_start();
		do_action( 'thim_lp_after_single_course_summary' );
		$html_courses_related = ob_get_clean();

		$sections['related_courses'] = $html_courses_related;

		return $sections;
	}

	public function lp_admin_manager_addons_section( $sections ) {
		$sections['note-theme'] = sprintf(
			'<div class="notice notice-install-addon">With the Add-ons included in Eduma, you can install and update them in <a href="%s">Eduma -> Plugins</a></div>',
			admin_url( 'admin.php?page=thim-plugins' )
		);

		return $sections;
	}

	public function lp_list_collection_layout( $sections ) {
		ob_start();
		do_action( 'thim_wrapper_loop_start' );
		$sections['container'] = ob_get_clean();
		unset( $sections['title'] );
		ob_start();
		do_action( 'thim_wrapper_loop_end' );
		$sections['container_end'] = ob_get_clean();

		return $sections;
	}

	public function top_heading_collection() {
		$html_top_heading = '';
		if ( get_theme_mod( 'thim_header_position', 'header_overlay' ) == 'header_overlay' ) {
			$cate_top_image = get_theme_mod( 'thim_collection_single_top_image' );
			if ( is_numeric( $cate_top_image ) ) {
				$cate_top_attachment = wp_get_attachment_image_src( $cate_top_image, 'full' );
				$cate_top_image_src  = $cate_top_attachment[0];
			} else {
				$cate_top_image_src = $cate_top_image;
			}
			$style_bg    = $cate_top_image_src ? ' style=" background-image:url(' . $cate_top_image_src . ')"' : '';
			$bg_color    = get_theme_mod( 'thim_collection_single_bg_color' );
			$style_color = $bg_color ? ' style=" background-color:' . $bg_color . '"' : '';

			$html_top_heading  = '<div class="top_heading_out">';
			$html_top_heading .= '<div class="top_site_main"' . $style_bg . '>
					<span class="overlay-top-header"' . $style_color . '></span>
			</div>';
			$html_top_heading .= '</div>';
		}

		return $html_top_heading;
	}
}

new Thim_LP_Layout();
