<?php
/**
 * LearnPress Coming Soon Hook Template
 *
 * @since 4.1.4
 * @version 1.0.0
 */

namespace LearnPress\CourseReview\TemplateHooks;

use LearnPress\CourseReview\CourseReviewCache;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LP_Addon_Course_Review_Preload;
use LP_Debug;
use LP_Request;
use Throwable;
use WP_Comment;

class TemplateHooks {
	use Singleton;

	public function init() {
		/*
		 * Handle to not active hook learn-press/single-course/offline/section-left on single course Modern layout.
		 * When LP 4.2.8.5 release, need remove this filter.
		 */
		add_filter(
			'learn-press/single-course/modern/section_left',
			function ( $section ) {
				return $section;
			}
		);
		// Show forum after section instructor
		add_filter( 'learn-press/single-course/modern/section-instructor', array( $this, 'show_review_single_course' ), 8, 3 );
		add_filter(
			'learn-press/single-course/offline/section-left',
			[
				$this,
				'single_course_offline_rating_reviews',
			],
			10,
			3
		);
		add_filter(
			'learn-press/single-course/offline/info-bar',
			[ $this, 'single_course_offline_info_bar' ],
			10,
			3
		);
		// Show rating on course archive page
		add_filter( 'learn-press/layout/list-courses/item/section/bottom', array( $this, 'rating_on_list_courses' ), 10, 3 );
		// Display on single classic layout
		add_action( 'learn-press/course-meta-primary-left', array( $this, 'meta_single_course_classic_layout' ), 30 );

		if ( is_admin() ) {
			add_filter( 'comment_text', array( $this, 'admin_show_rated_on_comments' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
			add_action( 'edit_comment', [ $this, 'save_review' ], 10, 2 );
			// Add submenu Course Review to learnpress menu
			add_action(
				'admin_menu',
				function () {
					add_submenu_page(
						'learn_press',
						__( 'Course Reviews', 'learnpress-course-review' ),
						__( 'Course Reviews', 'learnpress-course-review' ),
						'manage_options',
						home_url( '/wp-admin/edit-comments.php?comment_type=review' )
					);
				}
			);
		}
	}

	/**
	 * Show review on single Course Modern layout and Gutenberg.
	 *
	 * @param array $section
	 * @param CourseModel $courseModel
	 * @param $userModel
	 *
	 * @return array|mixed|null
	 */
	public function show_review_single_course( array $section, CourseModel $courseModel, $userModel ) {
		if ( ! LP_Addon_Course_Review_Preload::$addon->is_enable( $courseModel ) ) {
			return $section;
		}

		ob_start();
		do_action( 'learn-press/course-review/rating-reviews', $courseModel, $userModel );
		$html = ob_get_clean();

		return apply_filters(
			'learn-press/course/rating-reviews/position',
			Template::insert_value_to_position_array( $section, 'after', 'wrapper_end', 'review', $html ),
			$html,
			$section,
			$courseModel,
			$userModel
		);
	}

	/**
	 * Add section show list rating on single course offline
	 *
	 * @param array $section
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 *
	 * @return array
	 * @since 4.1.4
	 * @version 1.0.0
	 */
	public function single_course_offline_rating_reviews( array $section, CourseModel $courseModel, $userModel ): array {
		if ( ! $userModel instanceof UserModel ) {
			return $section;
		}

		if ( ! LP_Addon_Course_Review_Preload::$addon->is_enable( $courseModel ) ) {
			return $section;
		}

		$html = CourseRatingTemplate::instance()->html_rating_reviews( $courseModel, $userModel );

		return apply_filters(
			'learn-press/course-offline/rating-reviews/position',
			Template::insert_value_to_position_array( $section, 'after', 'instructor', 'review', $html ),
			$html,
			$section,
			$courseModel,
			$userModel
		);
	}

	/**
	 * Add section show list rating on single course offline
	 *
	 * @param array $section
	 * @param CourseModel $courseModel
	 * @param $userModel
	 *
	 * @return array
	 * @since 4.1.4
	 * @version 1.0.1
	 */
	public function single_course_offline_info_bar( array $section, CourseModel $courseModel, $userModel ): array {

		try {
			if ( ! LP_Addon_Course_Review_Preload::$addon->is_enable( $courseModel ) ) {
				return $section;
			}

			$html = CourseRatingTemplate::instance()->html_tiny_rating_info( $courseModel );

			return apply_filters(
				'learn-press/course-offline/rating-reviews/position',
				Template::insert_value_to_position_array( $section, 'after', 'author', 'number_reviews', $html ),
				$html,
				$section,
				$courseModel,
				$userModel
			);
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $section;
	}

	/**
	 * Show rating on course list courses
	 *
	 * @param array $section
	 * @param CourseModel $courseModel
	 * @param array $setting
	 *
	 * @return array
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function rating_on_list_courses( array $section, CourseModel $courseModel, array $setting ) {
		wp_enqueue_style( 'course-review' );

		$course_rated = LP_Addon_Course_Review_Preload::$addon->get_average_rated( $courseModel );
		$html         = CourseRatingTemplate::instance()->html_rated_star( $course_rated );
		return Template::insert_value_to_position_array( $section, 'before', 'title', 'review', $html );
	}

	/**
	 * Show rating on single course classic layout
	 *
	 * @return void
	 */
	public function meta_single_course_classic_layout() {
		if ( LP_COURSE_CPT !== get_post_type() ) {
			return;
		}

		$courseModel = CourseModel::find( get_the_ID(), true );
		if ( ! $courseModel instanceof CourseModel ) {
			return;
		}

		$course_rated = LP_Addon_Course_Review_Preload::$addon->get_average_rated( $courseModel );
		?>
		<div class="meta-item meta-item-review">
			<div class="meta-item__value">
				<label><?php esc_html_e( 'Review', 'learnpress-course-review' ); ?></label>
				<div>
					<?php
					echo CourseRatingTemplate::instance()->html_rated_star( $course_rated )
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Show rating on comments admin page
	 *
	 * @param $cmt_text
	 *
	 * @return mixed|string
	 */
	public function admin_show_rated_on_comments( $cmt_text ) {
		global $comment;
		if ( ! $comment || $comment->comment_type != 'review' ) {
			return $cmt_text;
		}

		$courseModel = CourseModel::find( $comment->comment_post_ID, true );
		if ( ! $courseModel instanceof CourseModel ) {
			return $cmt_text;
		}

		$rated = get_comment_meta( $comment->comment_ID, '_lpr_rating', true );

		$html_rated_start = CourseRatingTemplate::instance()->html_rated_star( $rated );

		$cmt_text .= $html_rated_start;

		return $cmt_text;
	}

	/**
	 * Added meta box to comment edit screen.
	 * To edit the review rating and review title.
	 *
	 * @param $tag
	 * @param $comment
	 *
	 * @return void
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function add_meta_box( $tag, $comment ) {
		if ( $tag != 'comment' ) {
			return;
		}

		if ( $comment->comment_type != 'review' ) {
			return;
		}

		add_meta_box(
			'lp_course_rating',
			esc_html__( 'Course Rating', 'learnpress-course-review' ),
			function () use ( $comment ) {
				$review_rated = get_comment_meta( $comment->comment_ID, '_lpr_rating', true );
				$review_title = get_comment_meta( $comment->comment_ID, '_lpr_review_title', true );

				$fields = [
					[
						'id'    => 'rated',
						'label' => __( 'Review rated', 'learnpress-course-review' ),
						'name'  => '_lpr_rating',
						'type'  => 'number',
						'value' => $review_rated,
					],
					[
						'id'    => 'title',
						'label' => __( 'Review title', 'learnpress-course-review' ),
						'name'  => '_lpr_review_title',
						'type'  => 'text',
						'value' => $review_title,
					],
				];

				$html_items = '';
				foreach ( $fields as $field ) {
					$html_items .= sprintf(
						'<tr>
							<td class="first" style="width: 100px"><label for="%s">%s</label></td>
							<td><input type="%s" name="%s" size="30" value="%s" id="%s" style="%s" step="1" min="1" max="5" /></td>
						</tr>',
						esc_attr( $field['id'] ),
						esc_html( $field['label'] ),
						esc_attr( $field['type'] ),
						esc_attr( $field['name'] ),
						esc_attr( $field['value'] ),
						esc_attr( $field['id'] ),
						'width: 100%'
					);
				}

				$section = [
					'table'     => '<table class="form-table editcomment">',
					'tbody'     => '<tbody>',
					'items'     => $html_items,
					'tbody_end' => '</tbody>',
					'table_end' => '</table>',
				];

				echo Template::combine_components( $section );
			},
			'comment',
			'normal',
			'high'
		);
	}

	/**
	 * Save review rating and review title when edit comment.
	 *
	 * @param $comment_id
	 * @param $data
	 *
	 * @return void
	 * @since v4.1.6
	 * @version 1.0.0
	 */
	public function save_review( $comment_id, $data ) {
		try {
			$comment = get_comment( $comment_id );
			if ( ! $comment instanceof WP_Comment ) {
				return;
			}

			if ( $comment->comment_type != 'review' ) {
				return;
			}

			if ( ! isset( $_POST['_lpr_rating'] ) && ! isset( $_POST['_lpr_review_title'] ) ) {
				return;
			}

			$review_rated = LP_Request::get_param( '_lpr_rating', 'number' );
			$review_title = LP_Request::get_param( '_lpr_review_title' );
			update_comment_meta( $comment_id, '_lpr_rating', $review_rated );
			update_comment_meta( $comment_id, '_lpr_review_title', $review_title );

			// Clear cache
			$CourseReviewCache = new CourseReviewCache( true );
			$CourseReviewCache->clean_rating( $comment->comment_post_ID, $comment->user_id );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}
}
