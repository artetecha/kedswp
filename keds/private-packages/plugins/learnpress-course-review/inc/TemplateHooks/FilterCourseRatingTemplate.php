<?php
/**
 * Class FilterCourseRatingTemplate
 *
 * Filter course by rating
 * @since 4.1.2
 * @version 1.0.1
 */

namespace LearnPress\CourseReview\TemplateHooks;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\FilterCourseTemplate;
use LP_Addon_Course_Review;
use LP_Course;
use LP_Course_DB;
use LP_Course_Filter;
use Throwable;

class FilterCourseRatingTemplate {
	use Singleton;

	public function init() {
		//add field to widget settings
		add_filter( 'learn-press/widget/course-filter/settings', [ $this, 'add_course_filter_widget_fields' ] );
		// add html to filter course widget
		add_action( 'learn-press/filter-courses/sections/field/html', [ $this, 'filter_section_field' ], 10, 4 );
		// handle query course
		add_filter( 'learn-press/courses/handle_params_for_query_courses', [ $this, 'handle_filter_params_c_review_star' ], 10, 2 );
		// Add sort by rating to course archive page
		add_filter( 'learn-press/courses/order-by/values', [ $this, 'add_sort_by_rating' ] );
		// Query order by rating
		add_filter( 'lp/courses/filter/order_by/rating', [ $this, 'query_order_by_rating' ] );
		// Load style on widget course filter
		add_action(
			'learn-press/widget/before',
			function ( $widget, $args, $instance ) {
				if ( $widget->id_base === 'learnpress_widget_course_filter' ) {
					wp_enqueue_style( 'course-review' );
				}
			},
			10,
			3
		);
		// Load style on list courses
		add_action(
			'learn-press/list-courses/layout',
			function () {
				wp_enqueue_style( 'course-review' );
			}
		);
	}

	/**
	 * add widget setting fields
	 * @param array $settings course filter setting fields
	 */
	public function add_course_filter_widget_fields( array $settings ): array {
		$fields = [];
		foreach ( $settings['fields']['options'] as $key => $value ) {
			$fields[ $key ] = $value;
			if ( 'level' === $key ) {
				$fields['course_review'] = [
					'id'    => 'course_review',
					'label' => esc_html__( 'Course Reviews', 'learnpress-course-review' ),
				];
			}
		}
		$settings['fields']['options'] = $fields;

		$order_fields = [];
		foreach ( $settings['fields']['std'] as $value ) {
			$order_fields[] = $value;
			if ( 'level' === $value ) {
				$order_fields[] = 'course_review';
			}
		}
		$settings['fields']['std'] = $order_fields;

		return $settings;
	}

	/**
	 * filter_section_field add html to course filter widget
	 * @param  array &$sections
	 * @param  string $field    filter field
	 * @param  array $data      FilterCourseTemplate $data
	 */
	public function filter_section_field( &$sections, $field, $data ) {
		if ( $field == 'course_review' ) {
			$sections[ $field ] = self::html_filter_course_review( $data );
		}
	}

	/**
	 * Html course review field
	 *
	 * @param  array $data
	 * @return string html
	 * @since 4.1.2
	 * @version 1.0.0
	 */
	public static function html_filter_course_review( array $data = [] ): string {
		$content = '';
		try {
			FilterCourseTemplate::instance()->check_param_url_has_lang( $data );
			$params_url    = $data['params_url'] ?? [];
			$data_selected = $params_url['c_review_star'] ?? '';
			ob_start();

			for ( $i = 0; $i < 5; $i++ ) {
				$checked = checked( $data_selected, $i, false );
				?>
				<div class="lp-course-filter__field">
					<input type="radio" name="c_review_star" id="review-star-<?php echo $i; ?>"
							value="<?php echo $i; ?>" <?php echo $checked; ?>/>
					<?php
					if ( 0 === $i ) {
						echo esc_html__( 'All rating', 'learnpress-course-review' );
					} else {
						// Show star rating filter
						$section = [
							'wrapper'     => sprintf( '<div class="lp-filter-item-star review-star-%d">', $i ),
							'content'     => CourseRatingTemplate::instance()->html_rated_star( $i ),
							'up'          => sprintf( '<span>%s</span>', __( '& up', 'learnpress-course-review' ) ),
							'wrapper_end' => '</div>',
						];

						echo Template::combine_components( $section );
					}
					?>
				</div>
				<?php
			}

			$content = ob_get_clean();

			$wrapper = apply_filters(
				'lp/filter-courses/sections/review/wrapper',
				[
					'wrapper'     => '<div class="lp-field-star">',
					'content'     => $content,
					'wrapper_end' => '</div>',
				],
				$data
			);

			$content = Template::combine_components( $wrapper );

			$content = FilterCourseTemplate::instance()->html_item( esc_html__( 'Rating', 'learnpress-course-review' ), $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getFile() . ': ' . $e->getLine() . ': ' . $e->getMessage() );
		}

		return $content;
	}

	public function handle_filter_params_c_review_star( LP_Course_Filter $filter, array $params ) {
		// error_log('params-'.json_encode($params));
		if ( ! empty( $params['c_review_star'] ) ) {
			$lp_course_db    = LP_Course_DB::getInstance();
			$tb_postmeta     = $lp_course_db->tb_postmeta;
			$filter->join[]  = "INNER JOIN $tb_postmeta AS pmrated ON p.ID = pmrated.post_id";
			$filter->where[] = $lp_course_db->wpdb->prepare( 'AND pmrated.meta_key = %s', LP_Addon_Course_Review::META_KEY_RATING_AVERAGE );
			$filter->where[] = $lp_course_db->wpdb->prepare( 'AND pmrated.meta_value >= %d', intval( $params['c_review_star'] ) );
		}
	}

	/**
	 * Show option sort by rating
	 * @param array $list_order_by
	 *
	 * @return array
	 */
	public function add_sort_by_rating( array $list_order_by = [] ): array {
		$list_order_by['rating'] = esc_html__( 'Average Ratings', 'learnpress-course-review' );

		return $list_order_by;
	}

	/**
	 * Query order by rating
	 *
	 * @param LP_Course_Filter $filter
	 *
	 * @return LP_Course_Filter
	 */
	public function query_order_by_rating( LP_Course_Filter $filter ): LP_Course_Filter {
		$lp_course_db     = LP_Course_DB::getInstance();
		$filter->join[]   = "INNER JOIN $lp_course_db->tb_postmeta AS rpm ON p.ID = rpm.post_id";
		$filter->where[]  = $lp_course_db->wpdb->prepare( 'AND rpm.meta_key = %s', LP_Addon_Course_Review::META_KEY_RATING_AVERAGE );
		$filter->order_by = 'rpm.meta_value';
		$filter->order    = 'DESC';

		return $filter;
	}
}
