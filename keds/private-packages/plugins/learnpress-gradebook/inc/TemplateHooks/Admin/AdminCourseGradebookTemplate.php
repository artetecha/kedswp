<?php
/**
 * Admin course Gradebook TemplateHook.
 *
 * @package LearnPress\Gradebook\TemplateHooks\Admin
 */

namespace LearnPress\Gradebook\TemplateHooks\Admin;

use Exception;
use LearnPress\Gradebook\Permission;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use stdClass;
use Throwable;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use LP_Helper;
use LP_Meta_Box_Select_Field;
use LP_Request;
use LP_Debug;
/**
 * Render the admin course Gradebook screen and its AJAX content.
 *
 * @since 4.1.0
 */
class AdminCourseGradebookTemplate {

	use Singleton;
	use SortableColumnsTrait;

	/**
	 * Number of course-user rows displayed per page.
	 *
	 * @var int
	 */
	public static $limit = 10;

	/**
	 * Sortable columns map: field => [ order_by SQL, default direction ].
	 */
	const SORTABLE_COLUMNS = array(
		'student'    => array(
			'order_by' => 'u.display_name',
			'default'  => 'ASC',
		),
		'email'      => array(
			'order_by' => 'u.user_email',
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
		'status'     => array(
			'order_by' => 'ui.graduation',
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

		return apply_filters( 'learnpress/gradebook/admin/course-gradebook/allow-callback', $callbacks, $this );
	}

	/**
	 * Render the course Gradebook screen layout.
	 *
	 * @return void
	 *
	 * @throws \Exception When the requested course does not exist.
	 */
	public function layout() {
		$screen = LP_Request::get_param( 'screen', 1, 'int' );
		if ( $screen > 1 ) {
			return;
		}
		$course_id   = LP_Request::get_param( 'course_id', 0, 'int' );
		$courseModel = CourseModel::find( $course_id );
		if ( ! $courseModel ) {
			throw new Exception( __( 'The course is not existed', 'learnpress-gradebook' ) );
		}

		if ( ! Permission::can_view_gradebook() || ! Permission::can_view_course( $course_id ) ) {
			throw new Exception( __( 'You do not have permission to access this page.', 'learnpress-gradebook' ) );
		}

		wp_enqueue_style( 'lp-gradebook-course-style' );
		wp_enqueue_script( 'course-gradebook-js' );
		$args     = array(
			'id_url'    => 'course-gradebook',
			'paged'     => LP_Request::get_param( 'paged', 1, 'int' ),
			'limit'     => self::$limit,
			'course_id' => $course_id,
		);
		$callback = array(
			'class'  => self::class,
			'method' => 'render_content',
		);
		$header   = self::section_header( $args, $courseModel );

		$ajax_content = TemplateAJAX::load_content_via_ajax( $args, $callback );
		$layout_html  = GradebookTemplateRenderer::render(
			'shared/layout.php',
			array(
				'header_html'       => $header,
				'ajax_content_html' => $ajax_content,
			)
		);
		$layout_html  = apply_filters( 'learnpress/gradebook/admin/course-gradebook/layout', $layout_html, $args, $courseModel );

		echo $layout_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the paginated course-user table content for TemplateAJAX.
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
			if ( ! $course_id || ! Permission::can_view_course( $course_id ) ) {
				throw new Exception( __( 'You do not have permission to view this course.', 'learnpress-gradebook' ) );
			}

			$args['course_id'] = $course_id;
			$args['limit']     = min( 100, max( 1, intval( $args['limit'] ?? self::$limit ) ) );
			$args['paged']     = max( 1, intval( $args['paged'] ?? 1 ) );

			$result      = self::get_user_course_items( $args, $course_id );
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
				$row_html = '';
				foreach ( $items as $key => $item ) {
					$userCourseModel = UserCourseModel::find( $item->user_id, $item->item_id );
					if ( ! $userCourseModel ) {
						continue;
					}
					$row_html .= self::row_html( $userCourseModel, $key, $args );
				}
				$content->content = GradebookTemplateRenderer::render(
					'shared/table.php',
					array(
						'headers'   => self::table_headers( $result['sort'] ?? array() ),
						'rows_html' => $row_html,
					)
				) . self::pagination( $args, $total_pages );
			}
			$content->total_pages = $total_pages;
			$content->paged       = $args['paged'];
		} catch ( Throwable $e ) {
			$content->content = Template::print_message( $e->getMessage(), 'error', false );
		}
		return apply_filters( 'learnpress/gradebook/admin/course-gradebook/render-content', $content, $args );
	}

	/**
	 * Render the course Gradebook chart, actions, and filters.
	 *
	 * @param array       $args        Screen request arguments.
	 * @param CourseModel $courseModel Course model.
	 *
	 * @return string
	 */
	public static function section_header( $args, $courseModel ) {
		$search_user_selector = self::user_selector();
		$status_selector      = self::status_selector(
			'item-status',
			array(
				'all'         => __( 'All', 'learnpress-gradebook' ),
				'passed'      => __( 'Passed', 'learnpress-gradebook' ),
				'failed'      => __( 'Failed', 'learnpress-gradebook' ),
				'in-progress' => __( 'In-progress(Course)', 'learnpress-gradebook' ),
			)
		);

		$course_chart_html = GradebookTemplateRenderer::render(
			'shared/chart-course.php',
			array(
				'course_id' => $courseModel->get_id(),
			)
		);

		$view_chart_button      = sprintf( '<button class="view-chart-button"> %s <span class="dashicons dashicons-chart-area"></span> <span></span> </button>', __( 'View chart', 'learnpress-gradebook' ) );
		$export_csv_button      = sprintf( '<button class="lp-gradebook-export-csv" data-course="%d"> <span class="dashicons dashicons-download"></span> %s <span></span></button>', $courseModel->get_id(), __( 'Export CSV', 'learnpress-gradebook' ) );
		$export_full_csv_button = sprintf( '<button class="lp-gradebook-export-full-csv" data-course="%d"> <span class="dashicons dashicons-download"></span> %s <span></span></button>', $courseModel->get_id(), __( 'Export Full CSV', 'learnpress-gradebook' ) );
		$course_name            = sprintf( '<span>%1$s: %2$s</span>', esc_html__( 'Course', 'learnpress-gradebook' ), esc_html( get_the_title( $courseModel->get_id() ) ) );
		$header_left_html       = $view_chart_button . $export_csv_button . $export_full_csv_button . $course_name;
		$header_right_html      = GradebookTemplateRenderer::render(
			'shared/filter-form.php',
			array(
				'fields_html'  => $search_user_selector . $status_selector,
				'button_class' => 'search-user-button',
				'button_label' => __( 'Search', 'learnpress-gradebook' ),
			)
		);

		$section_header = $course_chart_html . GradebookTemplateRenderer::render(
			'shared/toolbar.php',
			array(
				'left_html'  => $header_left_html,
				'right_html' => $header_right_html,
			)
		);

		return apply_filters( 'learnpress/gradebook/admin/course-gradebook/section-header', $section_header, $args, $courseModel );
	}

	/**
	 * Render the searchable user selector.
	 *
	 * @return string
	 */
	public static function user_selector() {
		$data_struct_user = array(
			'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/search-user' ),
			'dataType'    => 'users',
			'keyGetValue' => array(
				'value'      => 'ID',
				'text'       => '{{display_name}} — {{user_email}}',
				'key_render' => array(
					'display_name' => 'display_name',
					'user_email'   => 'user_email',
					'ID'           => 'ID',
				),
			),
			'setting'     => array(
				'placeholder' => esc_html__( 'Search student or email…', 'learnpress-gradebook' ),
			),
		);
		$filter_user      = new LP_Meta_Box_Select_Field(
			'',
			'',
			'',
			array(
				'options'           => '',
				'tom_select'        => true,
				'data-saved'        => false,
				'multiple'          => false,
				'custom_attributes' => array( 'data-struct' => htmlentities2( json_encode( $data_struct_user ) ) ),
			)
		);
		$filter_user->id  = 'gradebook_filter_user_ids';
		ob_start();
		$filter_user->output( 0 );
		$user_selector = ob_get_clean();

		return apply_filters( 'learnpress/gradebook/admin/course-gradebook/user-selector', $user_selector );
	}

	/**
	 * Render a Gradebook status selector.
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

		return apply_filters( 'learnpress/gradebook/admin/course-gradebook/status-selector', $status_selector, $id, $options );
	}

	/**
	 * Render one course-user Gradebook table row.
	 *
	 * @param UserCourseModel $userCourseModel User course model.
	 * @param int|string      $key             Row index.
	 * @param array           $args            TemplateAJAX request arguments.
	 *
	 * @return string
	 */
	public static function row_html( $userCourseModel, $key, $args ) {
		$paged           = intval( $args['paged'] );
		$datetime_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$results         = $userCourseModel->calculate_course_results();
		$userModel       = $userCourseModel->get_user_model();
		if ( ! $userModel ) {
			$sections = apply_filters( 'learnpress/gradebook/admin/course-gradebook/row/sections', array(), $userCourseModel, $key, $args );
			$row_html = Template::combine_components( $sections );

			return apply_filters( 'learnpress/gradebook/admin/course-gradebook/row-html', $row_html, $userCourseModel, $key, $args );
		}
		$user_course_link = learn_press_gradebook_nonce_url(
			array(
				'course_id' => $userCourseModel->item_id,
				'screen'    => 2,
				'student'   => $userCourseModel->user_id,
			)
		);
		$sections         = array(
			'row-open'   => '<tr>',
			'number'     => sprintf( '<td>%d</td>', 10 * ( $paged - 1 ) + (int) $key + 1 ),
			'user'       => sprintf(
				'<td class="user-name"><a href="%1$s">%2$s</a></td>',
				esc_url( $user_course_link ),
				esc_html( $userModel->get_display_name() )
			),
			'email'      => sprintf( '<td class="email">%s</td>', esc_html( $userModel->get_email() ) ),
			'start-time' => sprintf( '<td class="start-time">%s</td>', esc_html( wp_date( $datetime_format, strtotime( $userCourseModel->start_time ) ) ) ),
			'end-time'   => sprintf( '<td class="end-time">%s</td>', esc_html( ! empty( $userCourseModel->end_time ) ? wp_date( $datetime_format, strtotime( $userCourseModel->end_time ) ) : '-' ) ),
			'average'    => sprintf( '<td class="average">%s</td>', esc_html( $results['result'] ? $results['result'] . '%' : '0%' ) ),
			'status'     => sprintf(
				'<td class="status %1$s">%2$s</td>',
				esc_attr( $userCourseModel->graduation ),
				esc_html( $userCourseModel->get_string_i18n( $userCourseModel->graduation ?? '' ) )
			),
			'row-close'  => '</tr>',
		);
		$sections         = apply_filters( 'learnpress/gradebook/admin/course-gradebook/row/sections', $sections, $userCourseModel, $key, $args );
		$row_html         = Template::combine_components( $sections );

		return $row_html;
	}

	/**
	 * Get course Gradebook table header definitions.
	 *
	 * @return array
	 */
	public static function table_headers( $sort = array() ) {
		$headers = array(
			array( 'label' => '' ),
			self::sortable_header( 'student', __( 'Student', 'learnpress-gradebook' ), $sort, self::SORTABLE_COLUMNS ),
			self::sortable_header( 'email', __( 'Email', 'learnpress-gradebook' ), $sort, self::SORTABLE_COLUMNS ),
			self::sortable_header( 'start_time', __( 'Start time', 'learnpress-gradebook' ), $sort, self::SORTABLE_COLUMNS ),
			self::sortable_header( 'end_time', __( 'End time', 'learnpress-gradebook' ), $sort, self::SORTABLE_COLUMNS ),
			array( 'label' => __( 'Average', 'learnpress-gradebook' ) ),
			self::sortable_header( 'status', __( 'Status', 'learnpress-gradebook' ), $sort, self::SORTABLE_COLUMNS ),
		);

		return apply_filters( 'learnpress/gradebook/admin/course-gradebook/table-headers', $headers );
	}

	/**
	 * Render pagination for the course Gradebook table.
	 *
	 * @param array $args        TemplateAJAX request arguments.
	 * @param int   $total_pages Total number of pages.
	 *
	 * @return string
	 */
	public static function pagination( $args, $total_pages ) {
		$pagination      = paginate_links(
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

		return apply_filters( 'learnpress/gradebook/admin/course-gradebook/pagination', $pagination_html, $args, $total_pages );
	}

	/**
	 * Query paginated user-course records for a course.
	 *
	 * @param array $args      Query arguments.
	 * @param int   $course_id Course ID.
	 *
	 * @return array
	 */
	public static function get_user_course_items( $args = array(), $course_id = 0 ) {
		$filter            = new LP_User_Items_Filter();
		$total_rows        = 0;
		$lpuidb            = LP_User_Items_DB::getInstance();
		$filter->item_id   = $course_id;
		$filter->item_type = LP_COURSE_CPT;
		$filter->limit     = min( 100, max( 1, intval( $args['limit'] ?? self::$limit ) ) );
		$filter->page      = max( 1, intval( $args['paged'] ?? 1 ) );
		if ( ! empty( $args['gradebook_filter_user_ids'] ) ) {
			$filter->user_id = absint( $args['gradebook_filter_user_ids'] );
		}
		if ( ! empty( $args['item-status'] ) && $args['item-status'] !== 'all' ) {
			$item_status = $args['item-status'];
			if ( in_array( $item_status, array( 'passed', 'failed', 'in-progress' ), true ) ) {
				$filter->graduation = $item_status;
			} else {
				$filter->status = $item_status;
			}
		}
		$sort = self::resolve_sort_args( $args, self::SORTABLE_COLUMNS );
		if ( '' !== $sort['order_by'] ) {
			if ( strpos( $sort['order_by'], 'u.' ) === 0 ) {
				$filter->join[] = "INNER JOIN {$lpuidb->tb_users} AS u ON u.ID = ui.user_id";
			}
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

		return apply_filters( 'learnpress/gradebook/admin/course-gradebook/user-course-items', $result, $args, $course_id, $filter );
	}
}
