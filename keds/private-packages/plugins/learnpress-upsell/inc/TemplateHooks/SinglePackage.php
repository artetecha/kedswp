<?php
/**
 * Template hooks Single Package.
 *
 * @since 1.0.0
 * @version 1.0.1
 */
namespace LearnPress\Upsell\TemplateHooks;

use LearnPress\Helpers\Singleton;
use LearnPress\Upsell\Package\Package;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Course;

class SinglePackage {
	use Singleton;

	public function init() {
		add_action( 'lp/upsell/layout/single-package', [ $this, 'sections' ] );
	}

	public function sections() {
		global $post;
		if ( ! $post ) {
			return;
		}

		if ( LP_PACKAGE_CPT !== get_post_type( $post->ID ) ) {
			return;
		}

		$package = new Package( $post->ID );

		if ( post_password_required() ) {
			echo get_the_password_form();
			return;
		}

		// Hook old for theme, not use on the next version.
		// @deprecated hook 4.0.4
		$html_wrapper = apply_filters(
			'lp/upsell/single-package/wrapper',
			[
				'<div class="learnpress-packages__wrapper">' => '</div>',
				'<div class="single-package-wrapper">' => '</div>',
			]
		);

		$html_breadcrumb = '';
		if ( ! has_filter( 'lp/upsell/single-package/wrapper' ) ) {
			ob_start();
			learn_press_breadcrumb();
			$html_breadcrumb = ob_get_clean();
		}

		$section_main = [
			'wrapper'     => '<div class="learnpress-single-package__main">',
			'left'        => $this->left_sections( $package ),
			'right'       => $this->right_sections( $package ),
			'wrapper_end' => '</div>',
		];

		$sections = apply_filters(
			'learn-press/upsell/single-package/sections',
			[
				'wrapper'            => '<div class="lp-content-area">',
				'wrapper_single'     => '<div class="learnpress-single-package">',
				'breadcrumb'         => $html_breadcrumb,
				'main'               => Template::combine_components( $section_main ),
				'related'            => $this->related_sections( $package ),
				'wrapper_single_end' => '</div>',
				'wrapper_end'        => '</div>',
			]
		);

		$html = Template::instance()->nest_elements( $html_wrapper, Template::combine_components( $sections ) );

		echo $html;
	}

	/**
	 * Left sections.
	 *
	 * @param Package $package
	 *
	 * @return string
	 */
	public function left_sections( Package $package ): string {
		$html = '';

		$html_add_to_cart = $this->html_add_to_cart( $package );
		// Old hook Addon LP Woo 4.1.4 and below is using.
		$hook_old_add_to_cart = apply_filters(
			'lp/upsell/single-package/header/right/sections',
			[]
		);

		if ( has_filter( 'lp/upsell/single-package/header/right/sections' ) ) {
			$html_add_to_cart = $hook_old_add_to_cart['add-to-cart']['text_html'];
		}

		$left_sections = apply_filters(
			'learn-press/upsell/single-package/left/sections',
			[
				'wrapper'            => '<div class="learnpress-single-package__left">',
				'wrapper_sticky'     => '<div class="learnpress-single-package__left-sticky">',
				'image'              => $this->html_image( $package ),
				'count_courses'      => $this->html_count_courses( $package ),
				'price'              => $this->html_price( $package ),
				'add_to_cart'        => $html_add_to_cart,
				'wrapper_sticky_end' => '</div>',
				'wrapper_end'        => '</div>',
			]
		);

		return Template::combine_components( $left_sections );
	}

	/**
	 * Right sections.
	 *
	 * @param Package $package
	 *
	 * @return string
	 */
	public function right_sections( Package $package ): string {
		$right_sections = apply_filters(
			'learn-press/upsell/single-package/right/sections',
			[
				'wrapper'     => '<div class="learnpress-single-package__right">',
				'title'       => $this->html_title( $package, 'h1' ),
				'content'     => $this->html_description( $package ),
				'list-course' => $this->list_course( $package ),
				'wrapper_end' => '</div>',
			]
		);

		return Template::combine_components( $right_sections );
	}

	/**
	 * Get html list course of Package.
	 *
	 * @param Package $package
	 *
	 * @return string
	 */
	public function list_course( Package $package ): string {
		$course_ids = $package->get_course_list();
		$html       = '';

		// HTML section courses.
		ob_start();
		if ( empty( $course_ids ) ) {
			echo sprintf( '<p class="learn-press-message success">%s!</p>', __( 'No courses', 'learnpress-upsell' ) );
		} else {
			foreach ( $course_ids as $course_id ) {
				$course = CourseModel::find( $course_id, true );
				echo $this->item_course( $course );
			}
		}
		$html_courses = ob_get_clean();

		$section_courses = [
			'wrapper'     => '<ul class="learn-press-courses-package">',
			'courses'     => $html_courses,
			'wrapper_end' => '</ul>',
		];

		$sections = apply_filters(
			'learn-press/upsell/single-package/list-course',
			[
				'wrapper'     => '<div class="learnpress-single-package__courses">',
				'title'       => sprintf( '<h3>%s</h3>', __( 'Course included', 'learnpress-upsell' ) ),
				'course'      => Template::combine_components( $section_courses ),
				'wrapper_end' => '</div>',
			]
		);

		return Template::combine_components( $sections );
	}

	public function item_course( CourseModel $course ): string {
		$singleCourseTemplate = SingleCourseTemplate::instance();

		$section_image = [
			'wrapper'     => '<div class="course-thumbnail">',
			'img'         => sprintf(
				'<a href="%s">%s</a>',
				$course->get_permalink(),
				$singleCourseTemplate->html_image( $course )
			),
			'wrapper_end' => '</div>',
		];

		$section_meta = [
			'wrapper'     => '<div class="course-meta">',
			'student'     => $singleCourseTemplate->html_count_student( $course ),
			'lesson'      => $singleCourseTemplate->html_count_item( $course, LP_LESSON_CPT ),
			'duration'    => $singleCourseTemplate->html_duration( $course ),
			'wrapper_end' => '</div>',
		];

		$section_content = apply_filters(
			'learn-press/upsell/single-package/item-course-content',
			[
				'wrapper'     => '<div class="course-content">',
				'level'       => $singleCourseTemplate->html_level( $course ),
				'price'       => $singleCourseTemplate->html_price( $course ),
				'title'       => sprintf(
					'<a href="%s">%s</a>',
					$course->get_permalink(),
					$singleCourseTemplate->html_title( $course, 'h4' )
				),
				'meta'        => Template::combine_components( $section_meta ),
				'wrapper_end' => '</div>',
			]
		);

		$section = apply_filters(
			'learn-press/upsell/single-package/item-course',
			[
				'wrapper_li'     => '<li class="course">',
				'image'          => Template::combine_components( $section_image ),
				'content'        => Template::combine_components( $section_content ),
				'wrapper_li_end' => '</li>',
			]
		);

		return Template::combine_components( $section );
	}

	/**
	 * Get related packages.
	 *
	 * @param Package $package
	 *
	 * @return string
	 */
	public function related_sections( Package $package ): string {
		$html = '';

		$related_packages = $package->get_related_package_ids();
		if ( empty( $related_packages ) ) {
			return $html;
		}

		ob_start();
		foreach ( $related_packages as $package_id ) {
			$item_package = new Package( $package_id );
			echo ArchivePackage::instance()->render_package( $item_package );
		}
		$html_related_packages = ob_get_clean();

		$section_content = [
			'wrapper'     => '<ul class="learnpress-related-package__list">',
			'content'     => $html_related_packages,
			'wrapper_end' => '</ul>',
		];

		$section = apply_filters(
			'learn-press/upsell/single-package/related',
			[
				'wrapper'     => '<div class="learnpress-related-package">',
				'title'       => sprintf( '<h2>%s</h2>', __( 'Other Packages', 'learnpress-upsell' ) ),
				'content'     => Template::combine_components( $section_content ),
				'wrapper_end' => '</div>',
			],
			$package
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML title of Package.
	 *
	 * @param Package $package
	 * @param string $tag_html
	 *
	 * @return string
	 */
	public function html_title( Package $package, string $tag_html = 'span' ): string {

		$title = $package->get_title();

		$section = apply_filters(
			'learn-press/upsell/single-package/html-title',
			[
				'wrapper'     => sprintf( '<%s class="learnpress-package__title">', $tag_html ),
				'title'       => $title,
				'wrapper_end' => "</{$tag_html}>",
			],
			$package
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML description of Package.
	 *
	 * @param Package $package
	 *
	 * @return string
	 */
	public function html_description( Package $package ): string {
		$content = $package->get_content();

		$section = apply_filters(
			'learn-press/upsell/single-package/html-description',
			[
				'wrapper'           => '<div class="learnpress-single-package__content">',
				'wrapper_inner'     => '<div class="learnpress-single-package__content-inner">',
				'content'           => $content,
				'wrapper_inner_end' => '</div>',
				'btn-show-more'     => sprintf( '<span class="lp-show-more-content">%s</span>', __( 'show more', 'learnpress-upsell' ) ),
				'btn-show-less'     => sprintf( '<span class="lp-show-more-content less lp-hidden">%s</span>', __( 'show less', 'learnpress-upsell' ) ),
				'wrapper_end'       => '</div>',
			],
			$package
		);

		return Template::combine_components( $section );
	}

	/**
	 * Get html image of Package.
	 *
	 * @param Package $package
	 *
	 * @return string
	 */
	public function html_image( Package $package ): string {

		$image = $package->get_image( 'full' );

		$section = apply_filters(
			'learn-press/upsell/single-package/html-image',
			[
				'wrapper'     => '<div class="learnpress-package__image">',
				'image'       => $image,
				'wrapper_end' => '</div>',
			],
			$package
		);

		return Template::combine_components( $section );
	}

	/**
	 * Get html count courses of Package.
	 *
	 * @param Package $package
	 *
	 * @return string
	 * @since 4.0.4
	 * @version 1.0.0
	 */
	public function html_count_courses( Package $package ): string {
		$count = count( $package->get_course_list() );

		$section = apply_filters(
			'learn-press/upsell/single-package/html-count-courses',
			[
				'wrapper'       => '<div class="learnpress-package__count-courses">',
				'count_courses' => sprintf(
					'%d %s',
					$count,
					_n( 'Course included', 'Courses included', $count, 'learnpress-upsell' )
				),
				'wrapper_end'   => '</div>',
			],
			$package
		);

		return Template::combine_components( $section );
	}

	/**
	 * Get html count courses of Package.
	 *
	 * @param Package $package
	 *
	 * @return string
	 * @since 4.0.4
	 * @version 1.0.0
	 */
	public function html_price( Package $package ): string {
		$price         = $package->get_price();
		$regular_price = $package->get_regular_price();
		$price_html    = '';

		if ( $price == 0 && ! $package->get_new_price_enabled() ) {
			$price_html .= sprintf( '<span class="free">%s</span>', esc_html__( 'Free', 'learnpress-upsell' ) );
		} else {
			if ( $package->is_on_sale() ) {
				$price_html .= sprintf( '<span class="origin-price">%s</span>', learn_press_format_price( $regular_price, true ) );
			}

			$price_html .= sprintf( '<span class="price">%s</span>', learn_press_format_price( $price, true ) );
		}

		$price_html = sprintf( '<span class="lp-package-price">%s</span>', $price_html );
		return apply_filters( 'learn-press/upsell/single-package/html-price', $price_html, $package );
	}

	/**
	 * Get html add to cart of Package.
	 *
	 * @param Package $package
	 *
	 * @return string
	 */
	public function html_add_to_cart( Package $package ): string {
		$section = apply_filters(
			'learn-press/upsell/single-package/html-add-to-cart',
			[
				'wrapper'     => '<form class="learnpress-single-package__add-cart" method="post">',
				'input'       => sprintf(
					'<input type="hidden" name="package_id" value="%s">',
					$package->get_id()
				),
				'button'      => sprintf(
					'<button class="lp-button lp-buy-package" type="submit"> %s </button>',
					__( 'Buy Now', 'learnpress-upsell' )
				),
				'wrapper_end' => '</form>',
			],
			$package
		);

		return Template::combine_components( $section );
	}
}
