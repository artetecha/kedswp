<?php
/**
 * Plugin load class.
 *
 * @author   ThimPress
 * @package  LearnPress/Course-Review/Classes
 * @version  3.0.2
 */

// Prevent loading this file directly
use LearnPress\CourseReview\CourseReviewCache;
use LearnPress\CourseReview\CourseReviewWidget;
use LearnPress\CourseReview\Databases\CourseReviewsDB;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Addon_Course_Review' ) ) {
	/**
	 * Class LP_Addon_Course_Review.
	 */
	class LP_Addon_Course_Review extends LP_Addon {
		public $version         = LP_ADDON_COURSE_REVIEW_VER;
		public $require_version = LP_ADDON_COURSE_REVIEW_REQUIRE_VER;
		public $plugin_file     = LP_ADDON_COURSE_REVIEW_FILE;
		public $text_domain     = 'learnpress-course-review';

		/**
		 * @var string
		 */
		private static $comment_type = 'review';

		const META_KEY_RATING_AVERAGE = 'lp_course_rating_average';
		const META_KEY_ENABLE         = '_lp_course_review_enable';

		public static $instance = null;

		/**
		 * Get instance class.
		 *
		 * @return LP_Addon_Course_Review|null
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * LP_Addon_Course_Review constructor.
		 */
		public function __construct() {
			parent::__construct();
			$this->hooks();
		}

		/**
		 * Define Learnpress Course Review constants.
		 *
		 * @since 3.0.0
		 */
		protected function _define_constants() {
			define( 'LP_ADDON_COURSE_REVIEW_PATH', dirname( LP_ADDON_COURSE_REVIEW_FILE ) );
			define( 'LP_ADDON_COURSE_REVIEW_PER_PAGE', apply_filters( 'learn-press/course-review/per-page', 5 ) );
			define( 'LP_ADDON_COURSE_REVIEW_TMPL', LP_ADDON_COURSE_REVIEW_PATH . '/templates/' );
			define( 'LP_ADDON_COURSE_REVIEW_URL', untrailingslashit( plugins_url( '/', __DIR__ ) ) );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @since 3.0.0
		 */
		protected function _includes() {
			require_once LP_ADDON_COURSE_REVIEW_PATH . '/inc/functions.php';
			// Rest API
			require_once LP_ADDON_COURSE_REVIEW_PATH . '/inc/rest-api/jwt/class-lp-rest-review-v1-controller.php';
			require_once LP_ADDON_COURSE_REVIEW_PATH . '/inc/rest-api/class-rest-api.php';
			// Background
			require_once LP_ADDON_COURSE_REVIEW_PATH . '/inc/background/class-lp-course-review-background.php';
		}

		/**
		 * Init hooks.
		 */
		protected function hooks() {
			// Enqueue assets.
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );
			add_action( 'learn-press/admin-default-styles', array( $this, 'admin_enqueue_assets' ) );
			add_filter( 'learn-press/course-tabs', array( $this, 'add_course_tab_reviews' ), 5 );
			// Clear cache when update comment. (Approve|Un-approve|Edit|Spam|Trash)
			add_action(
				'wp_set_comment_status',
				function ( $comment_id ) {
					$comment = get_comment( $comment_id );
					if ( ! $comment ) {
						return;
					}

					$post_id = $comment->comment_post_ID;
					$user_id = $comment->user_id;
					if ( LP_COURSE_CPT !== get_post_type( $post_id ) ) {
						return;
					}

					$courseReviewCache = new CourseReviewCache( true );
					$courseReviewCache->clean_rating( $post_id, $user_id );
				}
			);
			add_filter(
				'learnPress/prepare_struct_courses_response/courseObjPrepare',
				[
					$this,
					'rest_api_courses',
				],
				10,
				2
			);
			// Add setting field to course.
			add_filter(
				'lp/course/meta-box/fields/general',
				function ( $fields, $post_id ) {
					$fields[ self::META_KEY_ENABLE ] = new LP_Meta_Box_Checkbox_Field(
						esc_html__( 'Enable reviews', 'learnpress-course-review' ),
						esc_html__( 'Show reviews for this course', 'learnpress-course-review' ),
						'yes'
					);

					return $fields;
				},
				10,
				2
			);

			$this->init_comment_table();
			$this->calculate_rating_average_courses();

			// Add widget
			add_action(
				'learn-press/widgets/register',
				function ( $widgets ) {
					$widgets[] = CourseReviewWidget::instance();

					return $widgets;
				}
			);

			/**
			 * Frontend: exclude course review from comment default of WP.
			 *
			 * @param $wp_comment_query WP_Comment_Query
			 */
			add_action(
				'pre_get_comments',
				function ( &$wp_comment_query ) {
					if ( is_admin() ) {
						return;
					}

					$post_id = $wp_comment_query->query_vars['post_id'];
					if ( ! $post_id ) {
						return;
					}

					$courseModel = CourseModel::find( $post_id, true );
					if ( ! $courseModel instanceof CourseModel ) {
						return;
					}

					$wp_comment_query->query_vars['type__not_in'] = self::$comment_type;
				}
			);
		}

		/**
		 * Admin assets.
		 * @since 4.0.0
		 * @version 1.0.1
		 */
		public function admin_enqueue_assets( $styles ) {
			$is_rtl = is_rtl() ? '-rtl' : '';
			$min    = '.min';
			$v      = LP_ADDON_COURSE_REVIEW_VER;
			if ( LP_Debug::is_debug() ) {
				$v   = rand();
				$min = '';
			}

			$styles['course-review'] = new LP_Asset_Key(
				$this->get_plugin_url( "assets/dist/css/admin{$is_rtl}{$min}.css" ),
				[],
				[ 'edit-comments', LP_COURSE_CPT ],
				0
			);

			return $styles;
		}

		/**
		 * Single course assets.
		 *
		 * @since 4.0.0
		 * @version 1.0.2
		 */
		public function frontend_assets() {
			$is_rtl = is_rtl() ? '-rtl' : '';
			$min    = '.min';
			$v      = LP_ADDON_COURSE_REVIEW_VER;
			if ( LP_Debug::is_debug() ) {
				$v   = rand();
				$min = '';
			}

			wp_register_style(
				'course-review',
				$this->get_plugin_url( "assets/dist/css/course-review{$is_rtl}{$min}.css" ),
				[],
				$v
			);
			wp_register_script(
				'course-review',
				$this->get_plugin_url( "assets/dist/js/course-review{$min}.js" ),
				[],
				$v,
				[ 'strategy' => 'async' ]
			);

			// For case load AJAX on tab "Courses", "My Courses" of LearnPress.
			if ( LP_Page_Controller::is_page_profile() ) {
				wp_enqueue_style( 'course-review' );
			}
		}

		/**
		 * Check if not register style, load style inline.
		 *
		 * @return void
		 * @since 4.1.2
		 * @version 1.0.0
		 */
		public function check_load_file_style() {
			// Check if has action, this action add to LearnPress on v4.2.7.2
			if ( has_action( 'learn-press/widget/before' ) ) {
				return;
			}

			$is_rtl = is_rtl() ? '-rtl' : '';
			$min    = '.min';
			if ( LP_Debug::is_debug() ) {
				$min = '';
			}
			$file_style = LP_Addon_Course_Review_Preload::$addon->get_plugin_url( "assets/css/course-review{$is_rtl}{$min}.css" );
			?>
			<style id="lp-course-review-star-style">
				<?php echo wp_remote_fopen( $file_style ); ?>
			</style>
			<?php
		}

		public function init_comment_table() {
			//wp_enqueue_style( 'course-review', LP_ADDON_COURSE_REVIEW_URL . '/assets/css/course-review.css' );

			add_filter( 'admin_comment_types_dropdown', array( $this, 'add_comment_type_filter' ) );

			add_filter( 'comment_row_actions', array( $this, 'edit_comment_row_actions' ), 10, 2 );
		}

		public function edit_comment_row_actions( $actions, $comment ) {
			if ( ! $comment || $comment->comment_type != 'review' ) {
				return $actions;
			}
			unset( $actions['reply'] );

			return $actions;
		}

		public function add_comment_type_filter( $cmt_types ) {
			$cmt_types[ self::$comment_type ] = __( 'Course review', 'learnpress-course-review' );

			return $cmt_types;
		}

		public function add_course_tab_reviews( $tabs ) {
			$course = CourseModel::find( get_the_ID(), true );
			if ( ! $course ) {
				return $tabs;
			}

			if ( ! $this->is_enable( $course ) ) {
				return $tabs;
			}

			$tabs['reviews'] = array(
				'title'    => __( 'Reviews', 'learnpress-course-review' ),
				'priority' => 60,
				'callback' => array( $this, 'add_course_tab_reviews_callback' ),
			);

			return $tabs;
		}

		/**
		 * Callback for course tab reviews.
		 *
		 * @return void
		 * @since 4.0.0
		 * @version 1.0.1
		 */
		public function add_course_tab_reviews_callback() {
			$course_id   = get_the_ID();
			$courseModel = CourseModel::find( $course_id, true );
			if ( empty( $courseModel ) ) {
				return;
			}

			$userModel = $courseModel::find( get_current_user_id(), true );
			do_action( 'learn-press/course-review/rating-reviews', $courseModel, $userModel );
		}

		/**
		 * Get rating of course.
		 *
		 * @param int $course_id
		 *
		 * @return array
		 * @since 4.0.6
		 * @version 1.0.0
		 */
		public function get_rating_of_course( int $course_id = 0 ): array {
			$courseReviewCache = new CourseReviewCache( true );
			$rating            = [
				'course_id' => $course_id,
				'total'     => 0,
				'rated'     => 0,
				'items'     => [
					5 => [
						'rated'         => 5,
						'total'         => 0,
						'percent'       => 0,
						'percent_float' => 0,
					],
					4 => [
						'rated'         => 4,
						'total'         => 0,
						'percent'       => 0,
						'percent_float' => 0,
					],
					3 => [
						'rated'         => 3,
						'total'         => 0,
						'percent'       => 0,
						'percent_float' => 0,
					],
					2 => [
						'rated'         => 2,
						'total'         => 0,
						'percent'       => 0,
						'percent_float' => 0,
					],
					1 => [
						'rated'         => 1,
						'total'         => 0,
						'percent'       => 0,
						'percent_float' => 0,
					],
				],
			];

			try {
				$rating_cache = $courseReviewCache->get_rating( $course_id );
				if ( false !== $rating_cache ) {
					return json_decode( $rating_cache, true );
				}

				$courseReviewsDB = CourseReviewsDB::getInstance();
				$rating_rs       = $courseReviewsDB->count_rating_of_course( $course_id );
				if ( ! $rating_rs ) {
					throw new Exception();
				}

				$rating['total'] = (int) $rating_rs->total;
				$total_rating    = 0;
				for ( $star = 1; $star <= 5; $star ++ ) {
					$key = '';
					switch ( $star ) {
						case 1:
							$key = 'one';
							break;
						case 2:
							$key = 'two';
							break;
						case 3:
							$key = 'three';
							break;
						case 4:
							$key = 'four';
							break;
						case 5:
							$key = 'five';
							break;
					}

					// Calculate total rating by type.
					$rating['items'][ $star ]['rated']   = $star;
					$rating['items'][ $star ]['total']   = (int) $rating_rs->{$key};
					$rating['items'][ $star ]['percent'] = (int) ( $rating_rs->total ? $rating_rs->{$key} * 100 / $rating_rs->total : 0 );

					// Sum rating.
					$count_star    = $rating_rs->{$key};
					$total_rating += $count_star * $star;
				}

				// Calculate average rating.
				$rating_average = $rating_rs->total ? $total_rating / $rating_rs->total : 0;
				if ( is_float( $rating_average ) ) {
					$rating_average = (float) number_format( $rating_average, 1 );
				}
				$rating['rated'] = $rating_average;

				// Set cache
				$courseReviewCache->set_rating( $course_id, json_encode( $rating ) );
			} catch ( Throwable $e ) {
				if ( ! empty( $e->getMessage() ) ) {
					LP_Debug::error_log( $e );
				}
			}

			return $rating;
		}

		/**
		 * Calculate rating average all courses.
		 * For case user upgrade plugin (not install First)
		 * Apply new feature - filter course rating, need param from meta to compare.
		 * For a long time will remove this function.
		 *
		 * @return void
		 * @since 4.1.2
		 */
		public function calculate_rating_average_courses() {
			if ( ! is_admin() ) {
				return;
			}

			$is_calculated = get_option( 'lp_calculated_rating_average_courses' );
			if ( ! empty( $is_calculated ) ) {
				return;
			}

			update_option( 'lp_calculated_rating_average_courses', 1 );
			$params = [ 'handle_name' => 'calculate_rating_average_courses' ];
			LPCourseReviewBackGround::instance()->data( $params )->dispatch();
		}

		/**
		 * Set rating average for course
		 *
		 * @param int $course_id
		 * @param float $average
		 *
		 * @return void
		 * @since 4.1.2
		 * @version 1.0.0
		 */
		public static function set_course_rating_average( int $course_id, float $average ) {
			update_post_meta( $course_id, LP_Addon_Course_Review::META_KEY_RATING_AVERAGE, $average );
		}

		/**
		 * Get SVG star.
		 *
		 * @return string
		 * @since 4.1.2
		 * @version 1.0.1
		 */
		public static function get_svg_star() {
			$path_default = LP_ADDON_COURSE_REVIEW_PATH . '/assets/images/svg-star.svg';
			$path         = realpath( apply_filters( 'learn-press/course-review/svg-star', $path_default ) );
			if ( ! $path ) {
				$path = $path_default;
			}

			return file_get_contents( $path );
		}

		/**
		 * Hook get rating for API of APP.
		 *
		 * @param stdClass|mixed $courseObjPrepare
		 * @param CourseModel $course form LP v4.2.6.9
		 *
		 * @return stdClass|mixed
		 * @since 4.1.3
		 * @version 1.0.0
		 */
		public function rest_api_courses( $courseObjPrepare, CourseModel $course ) {
			$courseObjPrepare->rating = learn_press_get_course_rate( $course->get_id() );

			return $courseObjPrepare;
		}

		/**
		 * Check course review is enable.
		 *
		 * @param CourseModel $course
		 *
		 * @return bool
		 * @since 4.1.5
		 * @version 1.0.0
		 */
		public function is_enable( CourseModel $course ): bool {
			$enable = $course->get_meta_value_by_key( self::META_KEY_ENABLE, 'yes' );

			return 'yes' === $enable;
		}

		/**
		 * Get average rating of course.
		 *
		 * @param CourseModel $course
		 *
		 * @return float
		 * @since 4.1.5
		 * @version 1.0.0
		 */
		public function get_average_rated( CourseModel $course ): float {
			return (float) number_format( (float) $course->get_meta_value_by_key( LP_Addon_Course_Review::META_KEY_RATING_AVERAGE, 0 ), 1 );
		}

		/**
		 * Check user can review course.
		 *
		 * @param UserModel $user
		 * @param CourseModel $course
		 *
		 * @return bool
		 * @since 4.1.5
		 * @version 1.0.0
		 */
		public function check_user_can_review_course( UserModel $user, CourseModel $course ): bool {
			$can_review = false;

			$userCourse = UserCourseModel::find( $user->get_id(), $course->get_id(), true );
			if ( $userCourse &&
				( $userCourse->has_enrolled_or_finished() || ( $course->is_offline() && $userCourse->has_purchased() ) )
				&& ! learn_press_get_user_rate( $course->get_id(), $user->get_id() ) ) {
				$can_review = true;
			}

			return $can_review;
		}

		/**
		 * Submit review
		 *
		 * @param array $data
		 * key courseModel
		 * key userModel
		 * key rating
		 * key review_title
		 * key review_content
		 *
		 * @return bool|WP_Error
		 * @since 4.1.6
		 * @version 1.0.0
		 */
		public static function submit_review( array $data ) {
			$flag = false;

			try {
				$course_id = $data['course_id'] ?? false;
				$user_id   = $data['user_id'] ?? false;
				$rating    = $data['rating'] ?? 0;
				$title     = $data['review_title'] ?? '';
				$content   = $data['review_content'] ?? '';

				$courseModel = CourseModel::find( $course_id, true );
				if ( ! $courseModel instanceof CourseModel ) {
					throw new Exception( esc_html__( 'Course is invalid!', 'learnpress-course-review' ) );
				}

				$userModel = UserModel::find( $user_id, true );
				if ( ! $userModel instanceof UserModel ) {
					throw new Exception( esc_html__( 'User is invalid!', 'learnpress-course-review' ) );
				}

				if ( empty( $rating ) ) {
					throw new Exception( esc_html__( 'Rating is requirement!', 'learnpress-course-review' ) );
				}

				if ( empty( $title ) ) {
					throw new Exception( esc_html__( 'Title is not empty!', 'learnpress-course-review' ) );
				}

				if ( empty( $content ) ) {
					throw new Exception( esc_html__( 'Content is not empty!', 'learnpress-course-review' ) );
				}

				if ( ! LP_Addon_Course_Review_Preload::$addon->check_user_can_review_course( $userModel, $courseModel ) ) {
					throw new Exception( esc_html__( 'You can not submit review.', 'learnpress-course-review' ) );
				}

				$add_review = learn_press_add_course_review(
					array(
						'user_id'   => $userModel->get_id(),
						'course_id' => $courseModel->get_id(),
						'rate'      => $rating,
						'title'     => $title,
						'content'   => $content,
						'force'     => true, // Not use cache.
					)
				);

				if ( ! $add_review instanceof WP_Error ) {
					$flag = true;
				} else {
					throw new Exception( $add_review->get_error_message() );
				}
			} catch ( Throwable $e ) {
				$flag = new WP_Error( 'lp_review_error', $e->getMessage() );
			}

			return $flag;
		}
	}
}
