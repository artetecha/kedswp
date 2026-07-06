<?php
/**
 * Admin user-course Gradebook TemplateHook.
 *
 * @package LearnPress\Gradebook\TemplateHooks\Admin
 */

namespace LearnPress\Gradebook\TemplateHooks\Admin;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Gradebook\Permission;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Helper;
use LP_Request;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use stdClass;
use Throwable;
use Exception;

/**
 * Render the admin user-course Gradebook screen and its AJAX content.
 *
 * @since 4.1.0
 */
class AdminUserCourseGradebookTemplate {

	use Singleton;
	use SortableColumnsTrait;

	/**
	 * Number of course-item rows displayed per page.
	 *
	 * @var int
	 */
	public static $limit = 10;

	/**
	 * Sortable columns map: field => [ order_by SQL, default direction ].
	 */
	const SORTABLE_COLUMNS = array(
		'title'      => array(
			'order_by' => 'p.post_title',
			'default'  => 'ASC',
		),
		'type'       => array(
			'order_by' => 'ui.item_type',
			'default'  => 'ASC',
		),
		'start_time' => array(
			'order_by' => 'ui.start_time',
			'default'  => 'DESC',
		),
		'end_time'   => array(
			'order_by' => 'ui.end_time',
			'default'  => 'DESC',
		),
		'graduation' => array(
			'order_by' => 'ui.graduation',
			'default'  => 'ASC',
		),
		'status'     => array(
			'order_by' => 'ui.status',
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

		return apply_filters( 'learnpress/gradebook/admin/user-course-gradebook/allow-callback', $callbacks, $this );
	}

	/**
	 * Render the user-course Gradebook screen layout.
	 *
	 * @return void
	 *
	 * @throws Exception When required request data or models are missing.
	 */
	public function layout() {
		$screen = LP_Request::get_param( 'screen', 1, 'int' );
		if ( $screen !== 2 ) {
			return;
		}

		$course_id = LP_Request::get_param( 'course_id', 0, 'int' );
		$user_id   = LP_Request::get_param( 'student', 0, 'int' );
		if ( ! $course_id || ! $user_id ) {
			throw new Exception( __( 'Course and user is required', 'learnpress-gradebook' ) );
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

		wp_enqueue_style( 'lp-gradebook-course-style' );
		wp_enqueue_script( 'user-course-gradebook' );

		$args     = array(
			'id_url'    => 'user-course-gradebook',
			'paged'     => LP_Request::get_param( 'paged', 1, 'int' ),
			'limit'     => self::$limit,
			'course_id' => $course_id,
			'user_id'   => $user_id,
		);
		$callback = array(
			'class'  => self::class,
			'method' => 'render_content',
		);

		$layout_html = GradebookTemplateRenderer::render(
			'shared/layout.php',
			array(
				'header_html'       => self::section_header( $args, $courseModel, $userModel ),
				'ajax_content_html' => TemplateAJAX::load_content_via_ajax( $args, $callback ),
			)
		);
		$layout_html = apply_filters( 'learnpress/gradebook/admin/user-course-gradebook/layout', $layout_html, $args, $courseModel, $userModel );

		echo $layout_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the paginated user course-item table content for TemplateAJAX.
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
			if ( ! $course_id || ! Permission::can_view_course( $course_id ) ) {
				throw new Exception( __( 'You do not have permission to view this course.', 'learnpress-gradebook' ) );
			}
			if ( ! $user_id ) {
				throw new Exception( __( 'User is required', 'learnpress-gradebook' ) );
			}

			$args['course_id'] = $course_id;
			$args['user_id']   = $user_id;
			$args['limit']     = min( 100, max( 1, intval( $args['limit'] ?? self::$limit ) ) );
			$args['paged']     = max( 1, intval( $args['paged'] ?? 1 ) );

			$result      = self::get_user_course_items( $args );
			$items       = $result['items'];
			$total_pages = $result['total_pages'];
			if ( empty( $items ) ) {
				$content->content = GradebookTemplateRenderer::render(
					'shared/empty-state.php',
					array(
						'message' => __( 'No course found', 'learnpress-gradebook' ),
					)
				);
			} else {
				$rows_html = '';
				foreach ( $items as $key => $item ) {
					$userItemModel = UserItemModel::find_user_item( $item->user_id, $item->item_id, $item->item_type, $item->ref_id, $item->ref_type );
					if ( ! $userItemModel ) {
						continue;
					}
					$rows_html .= self::row_html( $userItemModel, $key, $args );
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
		return apply_filters( 'learnpress/gradebook/admin/user-course-gradebook/render-content', $content, $args );
	}

	/**
	 * Render one user course-item Gradebook table row.
	 *
	 * @param UserItemModel $userItemModel User item model.
	 * @param int|string    $key           Row index.
	 * @param array         $args          TemplateAJAX request arguments.
	 *
	 * @return string
	 */
	public static function row_html( $userItemModel, $key, $args ) {
		$paged           = intval( $args['paged'] );
		$datetime_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$item_title      = get_the_title( $userItemModel->item_id );
		$item_title_cell = sprintf( '<td><span class="lp-gradebook-item__title">%s</span></td>', esc_html( $item_title ) );

		if ( $userItemModel->item_type === LP_QUIZ_CPT ) {
			$quiz_link       = learn_press_gradebook_nonce_url(
				array(
					'course_id' => $userItemModel->ref_id,
					'screen'    => 3,
					'student'   => $userItemModel->user_id,
					'quiz_id'   => $userItemModel->item_id,
				)
			);
			$item_title_cell = sprintf( '<td class="user-name"><a href="%1$s">%2$s</a></td>', esc_url( $quiz_link ), esc_html( $item_title ) );
		}

		$item_type_object = get_post_type_object( $userItemModel->item_type );
		$item_type_label  = $item_type_object ? $item_type_object->labels->singular_name : __( 'Unknown', 'learnpress-gradebook' );

		$sections = array(
			'row-open'   => '<tr>',
			'number'     => sprintf( '<td>%d</td>', 10 * ( $paged - 1 ) + (int) $key + 1 ),
			'title'      => $item_title_cell,
			'type'       => sprintf( '<td class="email">%s</td>', esc_html( $item_type_label ) ),
			'start-time' => sprintf( '<td class="start-time">%s</td>', esc_html( wp_date( $datetime_format, strtotime( $userItemModel->start_time ) ) ) ),
			'end-time'   => sprintf( '<td class="end-time">%s</td>', esc_html( ! empty( $userItemModel->end_time ) ? wp_date( $datetime_format, strtotime( $userItemModel->end_time ) ) : '-' ) ),
			'graduation' => sprintf(
				'<td class="status %1$s">%2$s</td>',
				esc_attr( $userItemModel->graduation ),
				esc_html( $userItemModel->get_string_i18n( $userItemModel->graduation ?? '' ) )
			),
			'status'     => sprintf(
				'<td class="status %1$s">%2$s</td>',
				esc_attr( $userItemModel->status ),
				esc_html( $userItemModel->get_string_i18n( $userItemModel->status ?? '' ) )
			),
			'row-close'  => '</tr>',
		);
		$sections = apply_filters( 'learnpress/gradebook/admin/user-course-gradebook/row/sections', $sections, $userItemModel, $key, $args );
		$row_html = Template::combine_components( $sections );

		return $row_html;
	}

	/**
	 * Get user-course Gradebook table header definitions.
	 *
	 * @return array
	 */
	public static function table_headers( $sort = array() ) {
		$headers = array(
			array( 'label' => '' ),
			self::sortable_header( 'title', __( 'Title', 'learnpress-gradebook' ), $sort, self::SORTABLE_COLUMNS ),
			self::sortable_header( 'type', __( 'Type', 'learnpress-gradebook' ), $sort, self::SORTABLE_COLUMNS ),
			self::sortable_header( 'start_time', __( 'Start time', 'learnpress-gradebook' ), $sort, self::SORTABLE_COLUMNS ),
			self::sortable_header( 'end_time', __( 'End time', 'learnpress-gradebook' ), $sort, self::SORTABLE_COLUMNS ),
			self::sortable_header( 'graduation', __( 'Graduation', 'learnpress-gradebook' ), $sort, self::SORTABLE_COLUMNS ),
			self::sortable_header( 'status', __( 'Status', 'learnpress-gradebook' ), $sort, self::SORTABLE_COLUMNS ),
		);

		return apply_filters( 'learnpress/gradebook/admin/user-course-gradebook/table-headers', $headers );
	}

	/**
	 * Render pagination for the user-course Gradebook table.
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

		return apply_filters( 'learnpress/gradebook/admin/user-course-gradebook/pagination', $pagination_html, $args, $total_pages );
	}

	/**
	 * Render breadcrumbs, chart, actions, and filters for the user-course screen.
	 *
	 * @param array       $args        Screen request arguments.
	 * @param CourseModel $courseModel Course model.
	 * @param UserModel   $userModel   User model.
	 *
	 * @return string
	 */
	public static function section_header( $args, $courseModel, $userModel ) {
		$course_link = learn_press_gradebook_nonce_url(
			array(
				'course_id' => $courseModel->get_id(),
				'screen'    => 1,
			)
		);

		$breadcrumbs_html = GradebookTemplateRenderer::render(
			'shared/breadcrumbs.php',
			array(
				'items' => array(
					array(
						'url'   => $course_link,
						'label' => get_the_title( $args['course_id'] ),
					),
					array(
						'label'  => $userModel->display_name,
						'active' => true,
					),
				),
			)
		);

		$status_selector = self::status_selector(
			'item-status',
			array(
				'all'       => __( 'All', 'learnpress-gradebook' ),
				'started'   => __( 'Started', 'learnpress-gradebook' ),
				'completed' => __( 'Completed', 'learnpress-gradebook' ),
			)
		);

		$header_right_html = GradebookTemplateRenderer::render(
			'shared/filter-form.php',
			array(
				'fields_html'  => sprintf( '<input name="name-search" placeholder="%s"/>', esc_attr__( 'Search lesson, quiz, ...', 'learnpress-gradebook' ) ) . $status_selector,
				'button_class' => 'search-button',
				'button_label' => __( 'Search', 'learnpress-gradebook' ),
			)
		);

		$section_header = $breadcrumbs_html . GradebookTemplateRenderer::render(
			'shared/chart-course.php',
			array(
				'course_id' => $courseModel->get_id(),
			)
		) . GradebookTemplateRenderer::render(
			'shared/toolbar.php',
			array(
				'left_html'  => sprintf( '<button class="view-chart-button"> %1$s <span class="dashicons dashicons-chart-area"></span> <span></span> </button><button class="lp-gradebook-export-csv" data-course="%2$d" data-student="%3$d"> <span class="dashicons dashicons-download"></span> %4$s <span></span></button>', esc_html__( 'View chart', 'learnpress-gradebook' ), $courseModel->get_id(), $userModel->get_id(), esc_html__( 'Export CSV', 'learnpress-gradebook' ) ),
				'right_html' => $header_right_html,
			)
		);

		return apply_filters( 'learnpress/gradebook/admin/user-course-gradebook/section-header', $section_header, $args, $courseModel, $userModel );
	}

	/**
	 * Render a user-course item status selector.
	 *
	 * @param string $id      Select field name.
	 * @param array  $options Status options keyed by value.
	 *
	 * @return string
	 */
	public static function status_selector( $id, $options ) {
		$status_selector = GradebookTemplateRenderer::render(
			'shared/status-select.php',
			array(
				'name'    => $id,
				'options' => $options,
			)
		);

		return apply_filters( 'learnpress/gradebook/admin/user-course-gradebook/status-selector', $status_selector, $id, $options );
	}

	/**
	 * Query paginated course-item records for a user.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array
	 */
	public static function get_user_course_items( $args = array() ) {
		$course           = learn_press_get_course( (int) $args['course_id'] );
		$item_ids         = $course->get_item_ids();
		$filter           = new LP_User_Items_Filter();
		$total_rows       = 0;
		$lpuidb           = LP_User_Items_DB::getInstance();
		$filter->ref_id   = absint( $args['course_id'] );
		$filter->ref_type = LP_COURSE_CPT;
		$filter->limit    = min( 100, max( 1, intval( $args['limit'] ?? self::$limit ) ) );
		$filter->page     = max( 1, intval( $args['paged'] ?? 1 ) );
		$filter->user_id  = absint( $args['user_id'] );
		$filter->item_ids = $item_ids;
		if ( ! empty( $args['item-status'] ) && $args['item-status'] !== 'all' ) {
			$item_status = $args['item-status'];
			if ( in_array( $item_status, array( 'passed', 'failed', 'in-progress' ), true ) ) {
				$filter->graduation = $item_status;
			} else {
				$filter->status = $item_status;
			}
		}
		$sort           = self::resolve_sort_args( $args, self::SORTABLE_COLUMNS );
		$need_post_join = ! empty( $args['name-search'] ) || strpos( $sort['order_by'], 'p.' ) === 0;
		if ( $need_post_join ) {
			$filter->join[] = "INNER JOIN $lpuidb->tb_posts AS p ON p.ID = ui.item_id";
		}
		if ( ! empty( $args['name-search'] ) ) {
			$like_string     = '%' . $lpuidb->wpdb->esc_like( sanitize_text_field( $args['name-search'] ) ) . '%';
			$filter->where[] = $lpuidb->wpdb->prepare( 'AND p.post_title LIKE %s', $like_string );
		}
		if ( '' !== $sort['order_by'] ) {
			$filter->order_by = $sort['order_by'];
			$filter->order    = $sort['direction'];
		}

		$items       = $lpuidb->get_user_items( $filter, $total_rows );
		$total_pages = LP_User_Items_DB::get_total_pages( $filter->limit, $total_rows );

		$result = array(
			'items'       => $items,
			'total_pages' => $total_pages,
			'sort'        => $sort,
		);

		return apply_filters( 'learnpress/gradebook/admin/user-course-gradebook/user-course-items', $result, $args, $filter );
	}
}
