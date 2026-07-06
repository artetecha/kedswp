<?php
/**
 * Admin user-quiz Gradebook TemplateHook.
 *
 * @package LearnPress\Gradebook\TemplateHooks\Admin
 */

namespace LearnPress\Gradebook\TemplateHooks\Admin;

use LearnPress\Databases\QuizQuestionsDB;
use LearnPress\Filters\QuizQuestionsFilter;
use LearnPress\Gradebook\Permission;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Datetime;
use LP_Helper;
use LP_Request;
use LP_User_Items_Result_DB;
use stdClass;
use Throwable;
use Exception;

/**
 * Render the admin user-quiz Gradebook screen and its AJAX content.
 *
 * @since 4.1.0
 */
class AdminUserQuizGradebookTemplate {

	use Singleton;
	use SortableColumnsTrait;

	/**
	 * Number of quiz-question rows displayed per page.
	 *
	 * @var int
	 */
	public static $limit = 10;

	/**
	 * Sortable columns map: field => [ order_by SQL, default direction ].
	 *
	 * Only DB-backed columns are sortable; Type/Correct/Retake are computed in PHP.
	 */
	const SORTABLE_COLUMNS = array(
		'title' => array(
			'order_by' => 'qp.post_title',
			'default'  => 'ASC',
		),
	);

	/**
	 * Register the screen layout and AJAX callback allowlist hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'learn-press/gradebook/course-gradebook', array( $this, 'layout' ) );
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
	}

	/**
	 * Allow callback for AJAX.
	 *
	 * @param array $callbacks Allowed AJAX callback identifiers.
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = get_class( $this ) . ':render_content';

		return apply_filters( 'learnpress/gradebook/admin/user-quiz-gradebook/allow-callback', $callbacks, $this );
	}

	/**
	 * Render the user-quiz Gradebook screen layout.
	 *
	 * @return void
	 *
	 * @throws Exception When required request data or models are missing.
	 */
	public function layout() {
		$screen = LP_Request::get_param( 'screen', 1, 'int' );
		if ( $screen !== 3 ) {
			return;
		}

		$course_id = LP_Request::get_param( 'course_id', 0, 'int' );
		$user_id   = LP_Request::get_param( 'student', 0, 'int' );
		$quiz_id   = LP_Request::get_param( 'quiz_id', 0, 'int' );
		if ( ! $course_id || ! $user_id || ! $quiz_id ) {
			throw new Exception( __( 'Course, user, quiz is required', 'learnpress-gradebook' ) );
		}

		$courseModel = CourseModel::find( $course_id );
		if ( ! $courseModel ) {
			throw new Exception( __( 'The course is not existed', 'learnpress-gradebook' ) );
		}

		if ( ! Permission::can_view_gradebook() || ! Permission::can_view_course( $course_id ) ) {
			throw new Exception( __( 'You do not have permission to access this page.', 'learnpress-gradebook' ) );
		}

		$userModel = UserModel::find( $user_id );
		if ( ! $userModel ) {
			throw new Exception( __( 'User is not existed', 'learnpress-gradebook' ) );
		}

		$userQuizModel = UserQuizModel::find_user_item( $user_id, $quiz_id, LP_QUIZ_CPT, $course_id, LP_COURSE_CPT );
		if ( ! $userQuizModel ) {
			throw new Exception( __( 'User quiz is not existed', 'learnpress-gradebook' ) );
		}

		wp_enqueue_style( 'lp-gradebook-course-style' );
		wp_enqueue_style( 'gradebook-user-quiz' );
		wp_enqueue_script( 'gradebook-user-quiz' );

		$args     = array(
			'id_url'       => 'user-course-gradebook',
			'paged'        => LP_Request::get_param( 'paged', 1, 'int' ),
			'limit'        => self::$limit,
			'course_id'    => $course_id,
			'user_id'      => $user_id,
			'quiz_id'      => $quiz_id,
			'user_item_id' => $userQuizModel->get_user_item_id(),
		);
		$callback = array(
			'class'  => self::class,
			'method' => 'render_content',
		);

		$layout_html = GradebookTemplateRenderer::render(
			'shared/layout.php',
			array(
				'header_html'       => self::section_header( $args, $courseModel, $userModel, $userQuizModel ),
				'ajax_content_html' => TemplateAJAX::load_content_via_ajax( $args, $callback ),
			)
		);
		$layout_html = apply_filters( 'learnpress/gradebook/admin/user-quiz-gradebook/layout', $layout_html, $args, $courseModel, $userModel, $userQuizModel );

		echo $layout_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the paginated user quiz-question table content for TemplateAJAX.
	 *
	 * @param array $args TemplateAJAX request arguments.
	 *
	 * @return stdClass
	 */
	public static function render_content( $args ) {
		$content = new stdClass();
		try {
			if ( ! Permission::can_view_gradebook() ) {
				throw new Exception( __( 'You do not have permission to access this page.', 'learnpress-gradebook' ) );
			}

			$course_id = absint( $args['course_id'] ?? 0 );
			$user_id   = absint( $args['user_id'] ?? 0 );
			$quiz_id   = absint( $args['quiz_id'] ?? 0 );
			if ( ! $course_id || ! Permission::can_view_course( $course_id ) ) {
				throw new Exception( __( 'You do not have permission to view this course.', 'learnpress-gradebook' ) );
			}
			if ( ! $user_id || ! $quiz_id ) {
				throw new Exception( __( 'Course, user, quiz is required', 'learnpress-gradebook' ) );
			}

			$userQuizModel = UserQuizModel::find_user_item( $user_id, $quiz_id, LP_QUIZ_CPT, $course_id, LP_COURSE_CPT );
			if ( ! $userQuizModel ) {
				throw new Exception( __( 'User quiz is not existed', 'learnpress-gradebook' ) );
			}

			$args['course_id']    = $course_id;
			$args['user_id']      = $user_id;
			$args['quiz_id']      = $quiz_id;
			$args['user_item_id'] = $userQuizModel->get_user_item_id();
			$args['limit']        = min( 100, max( 1, intval( $args['limit'] ?? self::$limit ) ) );
			$args['paged']        = max( 1, intval( $args['paged'] ?? 1 ) );

			$result      = self::get_user_questions( $args );
			$items       = $result['items'];
			$total_pages = 0;
			if ( empty( $items ) ) {
				$content->content = GradebookTemplateRenderer::render(
					'shared/empty-state.php',
					array(
						'message' => __( 'No course found', 'learnpress-gradebook' ),
					)
				);
			} else {
				$total_pages = $result['total_pages'];
				$rows_html   = '';
				foreach ( $items as $key => $item ) {
					$rows_html .= self::row_html( $item, $key, $args );
				}

				$content->content = GradebookTemplateRenderer::render(
					'shared/table.php',
					array(
						'headers'   => self::table_headers( $result['sort'] ?? array() ),
						'rows_html' => $rows_html,
					)
				) . self::pagination( $args, $total_pages );
			}
			$content->total_pages = $total_pages;
			$content->paged       = $args['paged'];
		} catch ( Throwable $e ) {
			$content->content = Template::print_message( $e->getMessage(), 'error', false );
		}
		return apply_filters( 'learnpress/gradebook/admin/user-quiz-gradebook/render-content', $content, $args );
	}

	/**
	 * Render one user quiz-question Gradebook table row.
	 *
	 * @param array      $item Question result data.
	 * @param int|string $key  Row index.
	 * @param array      $args TemplateAJAX request arguments.
	 *
	 * @return string
	 */
	public static function row_html( $item, $key, $args ) {
		$paged          = intval( $args['paged'] );
		$retake_details = self::retake_details_html( $item );

		$sections = array(
			'row-open'       => '<tr>',
			'number'         => sprintf( '<td>%d</td>', 10 * ( $paged - 1 ) + (int) $key + 1 ),
			'title'          => sprintf( '<td>%s</td>', wp_kses_post( $item['title'] ?? '' ) ),
			'type'           => sprintf( '<td>%s</td>', esc_html( $item['type'] ?? '' ) ),
			'correct'        => sprintf( '<td>%s</td>', esc_html( $item['correct'] ?? '' ) ),
			'retake-details' => sprintf( '<td>%s</td>', $retake_details ),
			'row-close'      => '</tr>',
		);
		$sections = apply_filters( 'learnpress/gradebook/admin/user-quiz-gradebook/row/sections', $sections, $item, $key, $args );
		$row_html = Template::combine_components( $sections );

		return $row_html;
	}

	/**
	 * Get user-quiz Gradebook table header definitions.
	 *
	 * @return array
	 */
	public static function table_headers( $sort = array() ) {
		$headers = array(
			array( 'label' => '' ),
			self::sortable_header( 'title', __( 'Title', 'learnpress-gradebook' ), $sort, self::SORTABLE_COLUMNS ),
			array( 'label' => __( 'Type', 'learnpress-gradebook' ) ),
			array( 'label' => __( 'Correct', 'learnpress-gradebook' ) ),
			array( 'label' => __( 'Retake details', 'learnpress-gradebook' ) ),
		);

		return apply_filters( 'learnpress/gradebook/admin/user-quiz-gradebook/table-headers', $headers );
	}

	/**
	 * Render retake details for a quiz question.
	 *
	 * @param array $item Question result data.
	 *
	 * @return string
	 */
	public static function retake_details_html( $item ) {
		$retake_details_html = GradebookTemplateRenderer::render(
			'shared/retake-dropdown.php',
			array(
				'retake_count' => $item['retake_count'] ?? 0,
				'retakes'      => $item['retake'] ?? array(),
			)
		);

		return apply_filters( 'learnpress/gradebook/admin/user-quiz-gradebook/retake-details-html', $retake_details_html, $item );
	}

	/**
	 * Render pagination for the user-quiz Gradebook table.
	 *
	 * @param array $args        TemplateAJAX request arguments.
	 * @param int   $total_pages Total number of pages.
	 *
	 * @return string
	 */
	public static function pagination( $args, $total_pages ) {
		$pagination = paginate_links(
			array(
				'base'      => add_query_arg( 'paged', '%#%', LP_Helper::getUrlCurrent() ),
				'format'    => '',
				'current'   => $args['paged'],
				'total'     => $total_pages,
				'prev_text' => __( '&laquo;', 'learnpress-gradebook' ),
				'next_text' => __( '&raquo;', 'learnpress-gradebook' ),
				'type'      => 'array',
				'end_size'  => 2,
				'mid_size'  => 2,
			)
		);

		$pagination_html = '';
		if ( ! empty( $pagination ) ) {
			$pagination_html = GradebookTemplateRenderer::render(
				'shared/pagination.php',
				array(
					'pagination_items' => $pagination,
				)
			);
		}

		return apply_filters( 'learnpress/gradebook/admin/user-quiz-gradebook/pagination', $pagination_html, $args, $total_pages );
	}

	/**
	 * Render breadcrumbs, quiz chart, actions, and filters.
	 *
	 * @param array         $args          Screen request arguments.
	 * @param CourseModel   $courseModel   Course model.
	 * @param UserModel     $userModel     User model.
	 * @param UserQuizModel $userQuizModel User quiz model.
	 *
	 * @return string
	 */
	public static function section_header( $args, $courseModel, $userModel, $userQuizModel ) {
		$course_id = (int) $args['course_id'];
		$user_id   = (int) $args['user_id'];
		$quiz_id   = (int) $args['quiz_id'];

		$course_link      = learn_press_gradebook_nonce_url(
			array(
				'course_id' => $course_id,
				'screen'    => 1,
			)
		);
		$user_course_link = learn_press_gradebook_nonce_url(
			array(
				'course_id' => $course_id,
				'student'   => $user_id,
				'screen'    => 2,
			)
		);

		$breadcrumbs_html = GradebookTemplateRenderer::render(
			'shared/breadcrumbs.php',
			array(
				'items' => array(
					array(
						'url'   => $course_link,
						'label' => get_the_title( $course_id ),
					),
					array(
						'url'   => $user_course_link,
						'label' => $userModel->display_name,
					),
					array(
						'label'  => get_the_title( $quiz_id ),
						'active' => true,
					),
				),
			)
		);

		$header_right_html = GradebookTemplateRenderer::render(
			'shared/filter-form.php',
			array(
				'fields_html'  => sprintf( '<input name="name-search" placeholder="%s"/>', esc_attr__( 'Search lesson, quiz, ...', 'learnpress-gradebook' ) ),
				'button_class' => 'lp-gradebook-search-button',
				'button_label' => __( 'Search', 'learnpress-gradebook' ),
			)
		);

		$section_header = $breadcrumbs_html . GradebookTemplateRenderer::render(
			'shared/chart-quiz.php',
			array(
				'attempted_questions_html' => self::attempted_questions( $args, $userQuizModel ),
			)
		) . GradebookTemplateRenderer::render(
			'shared/toolbar.php',
			array(
				'left_html'  => sprintf( '<button class="view-chart-button"> %1$s <span class="dashicons dashicons-chart-area"></span> <span></span> </button><button class="lp-gradebook-export-csv"> <span class="dashicons dashicons-download"></span> %2$s <span></span></button>', esc_html__( 'View chart', 'learnpress-gradebook' ), esc_html__( 'Export CSV', 'learnpress-gradebook' ) ),
				'right_html' => $header_right_html,
			)
		);

		return apply_filters( 'learnpress/gradebook/admin/user-quiz-gradebook/section-header', $section_header, $args, $courseModel, $userModel, $userQuizModel );
	}

	/**
	 * Render the attempted-questions result summary.
	 *
	 * @param array         $args          Screen request arguments.
	 * @param UserQuizModel $userQuizModel User quiz model.
	 *
	 * @return string
	 */
	public static function attempted_questions( $args, $userQuizModel ) {
		$result       = LP_User_Items_Result_DB::instance()->get_result( absint( $args['user_item_id'] ) );
		$end_time     = $userQuizModel->get_end_time();
		$end_time     = new LP_Datetime( $end_time );
		$end_time_str = $end_time->format( LP_Datetime::I18N_FORMAT_HAS_TIME );

		$attempted_questions = GradebookTemplateRenderer::render(
			'shared/quiz-result.php',
			array(
				'finish_time'      => $end_time_str,
				'question_correct' => $result['question_correct'] ?? 0,
				'question_count'   => $result['question_count'] ?? 0,
				'time_spent'       => $result['time_spend'] ?? '',
				'user_mark'        => $result['user_mark'] ?? 0,
				'mark'             => $result['mark'] ?? 0,
				'passing_grade'    => $result['passing_grade'] ?? '',
				'result'           => $result['result'] ?? 0,
			)
		);

		return apply_filters( 'learnpress/gradebook/admin/user-quiz-gradebook/attempted-questions', $attempted_questions, $args, $userQuizModel, $result );
	}

	/**
	 * Query paginated quiz questions and user attempt results.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array
	 */
	public static function get_user_questions( $args ) {
		$quiz_question_db    = QuizQuestionsDB::getInstance();
		$filter              = new QuizQuestionsFilter();
		$filter->quiz_id     = absint( $args['quiz_id'] );
		$filter->limit       = min( 100, max( 1, intval( $args['limit'] ?? self::$limit ) ) );
		$filter->page        = max( 1, intval( $args['paged'] ?? 1 ) );
		$filter->field_count = $filter::COL_QUIZ_QUESTION_ID;

		$sort           = self::resolve_sort_args( $args, self::SORTABLE_COLUMNS );
		$need_post_join = ! empty( $args['name-search'] ) || strpos( $sort['order_by'], 'qp.' ) === 0;
		if ( $need_post_join ) {
			$filter->join[] = "INNER JOIN $quiz_question_db->tb_posts AS qp ON qp.ID = qq.question_id";
		}
		if ( ! empty( $args['name-search'] ) ) {
			$like_string     = '%' . $quiz_question_db->wpdb->esc_like( sanitize_text_field( $args['name-search'] ) ) . '%';
			$filter->where[] = $quiz_question_db->wpdb->prepare( 'AND qp.post_title LIKE %s', $like_string );
		}
		if ( '' !== $sort['order_by'] ) {
			$filter->order_by = $sort['order_by'];
			$filter->order    = $sort['direction'];
		}

		$total_rows        = 0;
		$questions         = $quiz_question_db->get_quiz_questions( $filter, $total_rows );
		$total_pages       = QuizQuestionsDB::get_total_pages( $filter->limit, $total_rows );
		$user_quiz_results = LP_User_Items_Result_DB::instance()->get_results( absint( $args['user_item_id'] ), 10, false );
		$items             = array();

		if ( ! empty( $questions ) ) {
			foreach ( $questions as $q ) {
				$question_id                   = $q->question_id;
				$question_data                 = (array) $q;
				$question_data['title']        = get_the_title( $question_id );
				$question_data['type']         = learn_press_question_name_from_slug( get_post_meta( $question_id, '_lp_type', true ) );
				$question_data['retake_count'] = ! empty( $user_quiz_results ) ? ( count( $user_quiz_results ) - 1 ) : 0;
				foreach ( $user_quiz_results as $key => $result ) {
					$result = json_decode( $result, true );
					if ( $key === 0 ) {
						$question_data['correct'] = empty( $result['questions'][ $question_id ]['correct'] ) ? esc_html__( 'False', 'learnpress-gradebook' ) : esc_html__( 'True', 'learnpress-gradebook' );
						continue;
					}
					$question_data['retake'][] = empty( $result['questions'][ $question_id ]['correct'] ) ? esc_html__( 'False', 'learnpress-gradebook' ) : esc_html__( 'True', 'learnpress-gradebook' );
				}
				$items[] = $question_data;
			}
		}

		$result = array(
			'items'       => $items,
			'total_pages' => $total_pages,
			'sort'        => $sort,
		);

		return apply_filters( 'learnpress/gradebook/admin/user-quiz-gradebook/user-questions', $result, $args, $filter );
	}
}
