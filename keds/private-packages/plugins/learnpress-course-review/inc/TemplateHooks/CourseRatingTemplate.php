<?php
/**
 * LearnPress Coming Soon Hook Template
 *
 * @since 4.1.4
 * @version 1.0.0
 */

namespace LearnPress\CourseReview\TemplateHooks;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Addon_Course_Review;
use LP_Addon_Course_Review_Preload;
use LP_Datetime;
use LP_Debug;
use stdClass;
use Throwable;
use WP_Error;

class CourseRatingTemplate {
	use Singleton;

	public $addon;

	public function init() {
		$this->addon = LP_Addon_Course_Review_Preload::$addon;
		add_action( 'learn-press/course-review/rating-reviews', array( $this, 'layout_rating_reviews' ), 10, 2 );
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * @uses CourseRatingTemplate::render_rating_reviews()
	 * @uses CourseRatingTemplate::submit_review()
	 *
	 * @param $callbacks
	 *
	 * @return mixed
	 */
	public function allow_callback( $callbacks ) {
		$callbacks[] = CourseRatingTemplate::class . ':render_rating_reviews';
		$callbacks[] = CourseRatingTemplate::class . ':submit_review';

		return $callbacks;
	}

	/**
	 * Layout rating reviews
	 *
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 *
	 * return void
	 */
	public function layout_rating_reviews( CourseModel $courseModel, $userModel ) {
		wp_enqueue_script( 'course-review' );
		wp_enqueue_style( 'course-review' );

		$html_wrapper = [
			'<div class="lp-courses-rating-reviews-ajax">' => '</div>',
		];

		/**
		 * @uses self::render_rating_reviews()
		 */
		$callback = [
			'class'  => CourseRatingTemplate::class,
			'method' => 'render_rating_reviews',
		];
		$args     = [
			'id_url'    => 'course-rating-reviews',
			'course_id' => $courseModel->get_id(),
			'paged'     => 1,
		];

		$content = TemplateAJAX::load_content_via_ajax( $args, $callback );

		echo Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Add course rating to course archive page
	 *
	 * @param float|string $rated
	 *
	 * @return string
	 */
	public function html_rated_star( $rated ): string {
		$percent = min( 100, (float) $rated * 20 );

		$html_item = '';
		for ( $i = 1; $i <= 5; $i++ ) {
			$with_star     = $i * 20; // Css set 20px
			$percent_width = max( $with_star <= $percent ? 100 : ( $percent - ( $i - 1 ) * 20 ) * 5, 0 );
			$section_item  = [
				'wrapper'     => '<div class="review-star">',
				'far'         => sprintf(
					'<em class="far lp-review-svg-star">%s</em>',
					LP_Addon_Course_Review::get_svg_star()
				),
				'fas'         => sprintf(
					'<em class="fas lp-review-svg-star" style="width:%s;">%s</em>',
					"{$percent_width}%",
					LP_Addon_Course_Review::get_svg_star()
				),
				'wrapper_end' => '</div>',
			];

			$html_item .= Template::combine_components( $section_item );
		}

		$section = [
			'wrapper'     => '<div class="review-stars-rated">',
			'item'        => $html_item,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Add course rating to course archive page
	 *
	 * @param CourseModel $courseModel
	 *
	 * @return string
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function html_average_rating( CourseModel $courseModel ): string {
		// Get from meta key, check after
		//$average = LP_Addon_Course_Review_Preload::$addon->get_average_rated( $courseModel );

		$course_rate_res = LP_Addon_Course_Review_Preload::$addon->get_rating_of_course( $courseModel->ID );

		$section = [
			'wrapper'     => '<div class="course-rate__summary">',
			'value'       => sprintf(
				'<div class="course-rate__summary-value">%s</div>',
				esc_html( number_format( $course_rate_res['rated'] ?? 0, 1 ) )
			),
			'star'        => sprintf(
				'<div class="course-rate__summary-stars">%s</div>',
				$this->html_rated_star( $course_rate_res['rated'] )
			),
			'text'        => sprintf(
				'<div class="course-rate__summary-text">%s</div>',
				sprintf(
					_n(
						'<span>%d</span> rating',
						'<span>%d</span> ratings',
						$course_rate_res['total'],
						'learnpress-course-review'
					),
					$course_rate_res['total']
				)
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Show rating detail
	 * Number rating of each star (1-5)
	 *
	 * @param CourseModel $courseModel
	 *
	 * @return string
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function html_rating_detail( CourseModel $courseModel ): string {
		$course_rate_res = LP_Addon_Course_Review_Preload::$addon->get_rating_of_course( $courseModel->ID );
		$html_items      = '';

		$items = $course_rate_res['items'] ?? [];
		foreach ( $items as $item ) {
			$section_item = [
				'wrapper'      => '<div class="course-rate__details-row">',
				'star'         => sprintf(
					'<span class="course-rate__details-row-star">%s</span>',
					esc_html( $item['rated'] )
				),
				'fas'          => sprintf(
					'<em class="fas lp-review-svg-star">%s</em>',
					LP_Addon_Course_Review::get_svg_star()
				),
				'percent'      => sprintf(
					'<div class="course-rate__details-row-value">
						<div class="rating-gray"></div>
						<div class="rating" style="width:%s;">
						</div>
					</div>',
					esc_attr( $item['percent'] . '%' )
				),
				'rating-count' => sprintf(
					'<span class="rating-count">%s</span>',
					esc_html( $item['total'] )
				),
				'wrapper_end'  => '</div>',
			];

			$html_items .= Template::combine_components( $section_item );
		}

		$section = [
			'wrapper'     => '<div class="course-rate__details">',
			'items'       => $html_items,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 *
	 * @return string
	 */
	public function html_btn_review( CourseModel $courseModel, $userModel ): string {
		$html = '';

		try {
			if ( ! $userModel instanceof UserModel ) {
				return $html;
			}

			if ( ! LP_Addon_Course_Review_Preload::$addon->check_user_can_review_course( $userModel, $courseModel ) ) {
				return $html;
			}

			$section = [
				'button'      => sprintf(
					'<button class="write-a-review lp-button">%s</button>',
					esc_html__( 'Write a review', 'learnpress-course-review' )
				),
				'wrapper'     => '<div class="course-review-wrapper">',
				'form'        => self::instance()->html_form_review( $courseModel, $userModel ),
				'wrapper_end' => '</div>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	/**
	 * @param CourseModel $courseModel
	 * @param UserModel $userModel
	 *
	 * @return string
	 */
	public function html_form_review( CourseModel $courseModel, UserModel $userModel ): string {
		$html = '';

		try {
			if ( ! LP_Addon_Course_Review_Preload::$addon->check_user_can_review_course( $userModel, $courseModel ) ) {
				return $html;
			}

			$html_stars = '';
			for ( $i = 1; $i <= 5; $i++ ) {
				$html_stars .= sprintf(
					'<li class="choose-star" data-star="%d">
						<span>%s</span>
					</li>',
					$i,
					LP_Addon_Course_Review::get_svg_star()
				);
			}
			$html_stars = sprintf(
				'<ul class="review-stars">%s</ul>',
				$html_stars
			);

			$section = [
				'form'          => '<form class="review-form">',
				'h4'            => sprintf(
					'<h4>%s<a href="" class="close"><i class="lp-icon-close"></i></a></h4>',
					esc_html__( 'Write a review', 'learnpress-course-review' )
				),
				'fields'        => '<ul class="review-fields">',
				'field_title'   => sprintf(
					'<li>
						<label>%s <span class="required">*</span></label>
						<input type="text" name="review_title"/>
					</li>',
					esc_html__( 'Title', 'learnpress-course-review' )
				),
				'field_content' => sprintf(
					'<li>
						<label>%s<span class="required">*</span></label>
						<textarea name="review_content"></textarea>
					</li>',
					esc_html__( 'Content', 'learnpress-course-review' )
				),
				'field_rating'  => sprintf(
					'<li>
						<label>%s<span class="required">*</span></label>
						%s',
					esc_html__( 'Rating (click to choice)', 'learnpress-course-review' ),
					$html_stars
				),
				'field_actions' => sprintf(
					'<li class="review-actions">
						<button type="button" class="lp-button submit-review" data-id="%d">%s</button>
						<button type="button" class="lp-button close">%s</button>
						<span class="error"></span>
					</li>',
					$courseModel->get_id(),
					esc_html__( 'Add review', 'learnpress-course-review' ),
					esc_html__( 'Cancel', 'learnpress-course-review' )
				),
				'fields_hidden' => '<input type="hidden" name="rating" value="0">',
				'fields_end'    => '</ul>',
				'form_end'      => '</form>',
			];

			$args = [
				'id_url'                  => 'lp-submit-review-form',
				'course_id'               => $courseModel->get_id(),
				'html_no_load_ajax_first' => Template::combine_components( $section ),
			];
			/**
			 * @uses self::submit_review()
			 */
			$callback = [
				'class'  => CourseRatingTemplate::class,
				'method' => 'submit_review',
			];

			// Set args for ajax to submit review
			$html = TemplateAJAX::load_content_via_ajax( $args, $callback );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	/**
	 * HTML for list reviews
	 *
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 * @param $settings
	 *
	 * @return string
	 * @since 4.1.6
	 * @version 1.0.2
	 */
	public function html_list_reviews( CourseModel $courseModel, $userModel, $settings ): string {
		$html = '';

		try {
			$reviews_rs  = $settings['reviews_rs'] ?? [];
			$total_pages = $reviews_rs['pages'] ?? 0;

			$html_items = '';
			$reviews    = $reviews_rs['reviews'] ?? [];
			if ( empty( $reviews ) ) {
				return $html;
			}

			foreach ( $reviews as $review ) {
				$date_time_review = new LP_DateTime( $review->comment_date_gmt );

				$section_info = apply_filters(
					'learn-press/course-review/list-reviews/item-info/section',
					[
						'wrapper'                  => '<div class="review-content-right">',
						'wrapper-info'             => '<div class="review-info">',
						'wrapper-author-rated'     => '<div class="author-rated">',
						'rated'                    => self::instance()->html_rated_star( $review->rate ?? 0 ),
						'user-name'                => sprintf(
							'<h4 class="user-name">%s</h4>',
							$review->display_name ?? ''
						),
						'wrapper-author-rated_end' => '</div>',
						'date'                     => sprintf(
							'<div class="review-date">%s</div>',
							$date_time_review->format( LP_DateTime::I18N_FORMAT )
						),
						'wrapper-info_end'         => '</div>',
						'title'                    => sprintf(
							'<h5 class="course-review-title">%s</h5>',
							wp_kses_post( $review->title ?? '' )
						),
						'content'                  => sprintf(
							'<div class="review-content">%s</div>',
							wp_kses_post( $review->content ?? '' )
						),
						'wrapper_end'              => '</div>',
					],
					$review,
					$courseModel,
					$userModel,
					$settings
				);

				$section_item = [
					'li'     => '<li>',
					'avatar' => sprintf(
						'<div class="review-author">%s</div>',
						get_avatar( $review->user_email ?? '' )
					),
					'info'   => Template::combine_components( $section_info ),
					'li_end' => '</li>',
				];

				$html_items .= apply_filters(
					'learn-press/course-review/list-reviews/item/section',
					Template::combine_components( $section_item ),
					$review,
					$courseModel,
					$userModel,
					$settings
				);
			}

			$section = apply_filters(
				'learn-press/course-review/list-reviews/section',
				array(
					'wrapper'       => '<div class="course-reviews">',
					'ul'            => '<ul class="course-reviews-list">',
					'reviews'       => $html_items,
					'ul_end'        => '</ul>',
					'btn_load_more' => $total_pages > 1 ? sprintf(
						'<button class="lp-button course-review-load-more"
							id="course-review-load-more">%s
						</button>',
						esc_html__( 'Load more', 'learnpress-course-review' )
					) : '',
					'wrapper_end'   => '</div>',
				),
				$courseModel,
				$userModel,
				$settings
			);

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	/**
	 * HTML for show message if user has submitted review and it is awaiting approve
	 *
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 *
	 * @return string
	 * @since 4.1.6
	 * @version 1.0.1
	 */
	public function html_unapprove( CourseModel $courseModel, $userModel ): string {
		$html = '';

		try {
			if ( ! $userModel instanceof UserModel ) {
				return $html;
			}

			// Get comments of user for course with comment_approved = 0
			$args           = array(
				'user_id' => $userModel->get_id(),
				'post_ID' => $courseModel->get_id(),
				'type'    => 'review',
				'status'  => 'hold',
			);
			$comments_count = get_comments( $args );

			if ( ! empty( $comments_count ) ) {
				$html = sprintf(
					'<div class="learn-press-message success">%s</div>',
					__( 'Your review has been submitted and is awaiting approve.', 'learnpress-course-review' )
				);
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	/**
	 * Get html tiny rating info
	 *
	 * @param CourseModel $course
	 *
	 * @return string
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function html_tiny_rating_info( CourseModel $course ): string {
		$html = '';

		try {
			$rating_info = LP_Addon_Course_Review_Preload::$addon->get_rating_of_course( $course->get_id() );

			wp_enqueue_style( 'course-review' );

			$html_star = sprintf(
				'<em class="fas lp-review-svg-star">%s</em>',
				LP_Addon_Course_Review::get_svg_star()
			);
			$html      = sprintf(
				'<div class="item-meta">
					<div class="star-info">
					<span class="ico-star">%s</span><span class="info-rating">%d/%d</span> %s
					</div>
				</div>',
				$html_star,
				$rating_info['rated'],
				$rating_info['total'],
				_n( 'Rating', 'Ratings', $rating_info['total'], 'learnpress-course-review' )
			);
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	/**
	 * Render rating reviews
	 *
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 *
	 * @return string
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function html_rating_reviews( CourseModel $courseModel, $userModel ): string {
		$html = '';

		try {
			ob_start();
			LP_Addon_Course_Review_Preload::$addon->add_course_tab_reviews_callback();
			$html_rating_reviews_main = ob_get_clean();

			$section = [
				'wrapper'     => '<div class="lp-rating-reviews-wrapper">',
				'header'      => sprintf(
					'<h3 class="item-title">%s</h3>',
					esc_html__( 'Reviews', 'learnpress-course-review' )
				),
				'content'     => $html_rating_reviews_main,
				'wrapper_end' => '</div>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	/**
	 * Render rating reviews
	 *
	 * @param array $settings
	 *
	 * @return stdClass
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public static function render_rating_reviews( array $settings = [] ): stdClass {
		$content          = new stdClass();
		$content->content = '';

		try {
			$course_id   = $settings['course_id'] ?? 0;
			$user_id     = $settings['user_id'] ?? get_current_user_id();
			$userModel   = UserModel::find( $user_id, true );
			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel instanceof CourseModel ) {
				return $content;
			}

			$paged                  = $settings['paged'] ?? 1;
			$reviews_rs             = learn_press_get_course_review( $courseModel->get_id(), $paged );
			$total_pages            = $reviews_rs['pages'] ?? 0;
			$settings['reviews_rs'] = $reviews_rs;

			$section_course_rate = [
				'wrapper'     => '<div class="course-rate">',
				'average'     => self::instance()->html_average_rating( $courseModel ),
				'detail'      => self::instance()->html_rating_detail( $courseModel ),
				'wrapper_end' => '</div>',
			];

			$section = [
				'wrapper'     => '<div class="learnpress-course-review lp-rating-reviews-wrapper">',
				'header'      => sprintf(
					'<h3 class="item-title">%s</h3>',
					esc_html__( 'Reviews', 'learnpress-course-review' )
				),
				'rate'        => Template::combine_components( $section_course_rate ),
				'btn-review'  => self::instance()->html_btn_review( $courseModel, $userModel ),
				'unapprove'   => self::instance()->html_unapprove( $courseModel, $userModel ),
				'reviews'     => self::instance()->html_list_reviews( $courseModel, $userModel, $settings ),
				'wrapper_end' => '</div>',
			];

			$content->content     = Template::combine_components( $section );
			$content->paged       = $paged;
			$content->total_pages = $total_pages;
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $content;
	}

	/**
	 * Submit review
	 *
	 * @param array $settings
	 *
	 * @return stdClass
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public static function submit_review( array $settings = [] ) {
		$content             = new stdClass();
		$content->status     = 'error';
		$content->content    = '';
		$settings['user_id'] = get_current_user_id();
		$submit_rs           = LP_Addon_Course_Review_Preload::$addon->submit_review( $settings );
		if ( $submit_rs instanceof WP_Error ) {
			$content->content = $submit_rs->get_error_message();
		} else {
			$content->status  = 'success';
			$content->content = __( 'Your review has been submitted and is awaiting approve.', 'learnpress-course-review' );
		}

		return $content;
	}
}
