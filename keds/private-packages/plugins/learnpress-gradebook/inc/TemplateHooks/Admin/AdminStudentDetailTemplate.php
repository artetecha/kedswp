<?php

namespace LearnPress\Gradebook\TemplateHooks\Admin;

use Exception;
use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Gradebook\Permission;
use LearnPress\Gradebook\TemplateHooks\GradebookTemplate;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use stdClass;
use Throwable;
use LP_Meta_Box_Select_Field;
use LP_Request;

/**
 * class AdminStudentOverviewTemplate
 *
 * @since 4.0.8
 * @version 1.0.0
 */
class AdminStudentDetailTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/gradebook/admin-view', array( $this, 'layout' ) );
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
	}

	/**
	 * Allow callback for AJAX.
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = get_class( $this ) . ':render_content';

		return $callbacks;
	}

	public function layout( $data ) {
		$section = $data['section'] ?? '';
		if ( $section !== 'student-detail' ) {
			return;
		}

		wp_enqueue_style( 'lp-gradebook-admin-style' );
		wp_enqueue_script( 'lp-gradebook-admin-script' );

		$user_id = LP_Request::get_param( 'user_id', 0, 'int' );
		if ( ! Permission::can_view_gradebook() || ! Permission::can_view_student( $user_id ) ) {
			return;
		}

		$args = array(
			'id_url'  => 'gradebook-student-detail',
			'paged'   => LP_Request::get_param( 'paged', 1, 'int' ),
			'limit'   => 10,
			'user_id' => $user_id,
		);

		$userModel = UserModel::find( $user_id, true );
		if ( ! $userModel ) {
			return;
		}

		$callback    = array(
			'class'  => self::class,
			'method' => 'render_content',
		);
		$html_filter = $this->html_filter( $args );
		$content     = TemplateAJAX::load_content_via_ajax( $args, $callback );
		$section     = array(
			'wrap'        => '<div class="lp-gradebook-student-detail wrap">',
			'btn-back'    => sprintf(
				'<a class="lp-button lp-btn-back button-secondary" href="%s">%s</a>',
				admin_url( 'admin.php?page=learnpress-gradebook&tab=student-overview' ),
				__( 'Back to Student Overview', 'learnpress-gradebook' )
			),
			'title'       => sprintf(
				'<h1>%s: %s</h1>',
				__( 'Student Details', 'learnpress-gradebook' ),
				__( 'Courses attended by', 'learnpress-gradebook' ) . ' ' . $userModel->get_display_name()
			),
			'filter'      => $html_filter,
			'btn-actions' => sprintf(
				'<div class="btn-actions">
					<button class="lp-button lp-btn-gradebook-view-chart button-secondary"
						data-micromodal-trigger="lp-gradebook-chart-modal"
						data-send="%s"
						type="button">%s
					</button>
					<button class="lp-button lp-btn-gradebook-export-csv button-secondary"
						data-send="%s"
						type="button">%s
					</button>
				</div>',
				Template::convert_data_to_json(
					array(
						'action'  => 'lp_gradebook_data_chart_student',
						'user_id' => $user_id,
						'args'    => array(
							'id_url' => 'gradebook-chart-student-courses',
						),
					)
				),
				__( 'View Chart', 'learnpress-gradebook' ),
				Template::convert_data_to_json(
					array(
						'action'  => 'lp_gradebook_export_student_courses',
						'user_id' => $user_id,
						'paged'   => 1,
						'limit'   => 10,
						'args'    => array(
							'id_url' => 'gradebook-export-student-courses',
						),
					)
				),
				__( 'Export CSV', 'learnpress-gradebook' )
			),
			'content'     => $content,
			'popup-chart' => GradebookTemplate::html_micromodal(
				array(
					'id'      => 'lp-gradebook-chart-modal',
					'title'   => sprintf(
						__( 'Course statistics of user: %s', 'learnpress-gradebook' ),
						$userModel->get_display_name()
					),
					'content' => $this->html_chart( $user_id ),
				)
			),
			'wrap_end'    => '</div>',
		);

		echo Template::combine_components( $section );
	}

	/**
	 * Render content for AJAX.
	 *
	 * @param array $args
	 *
	 * @return stdClass
	 */
	public static function render_content( array $args = array() ): stdClass {
		$content = new stdClass();

		try {
			if ( ! Permission::can_view_gradebook() ) {
				throw new Exception( __( 'You do not have permission to access this page.', 'learnpress-gradebook' ) );
			}

			$user_id    = absint( $args['user_id'] ?? 0 );
			$course_ids = wp_parse_id_list( $args['course_ids'] ?? '' );
			$limit      = min( 100, max( 1, intval( $args['limit'] ?? 10 ) ) );
			$paged      = max( 1, intval( $args['paged'] ?? 1 ) );
			$userModel  = UserModel::find( $user_id, true );
			if ( ! $userModel ) {
				throw new Exception( __( 'User not found', 'learnpress-gradebook' ) );
			}
			if ( ! Permission::can_view_student( $user_id ) ) {
				throw new Exception( __( 'You do not have permission to view this student.', 'learnpress-gradebook' ) );
			}

			$filter            = new UserItemsFilter();
			$total_rows        = 0;
			$userItemsDB       = UserItemsDB::getInstance();
			$filter->user_id   = $user_id;
			$filter->item_type = LP_COURSE_CPT;
			$filter->limit     = $limit;
			$filter->page      = $paged;
			$filter->order_by  = 'start_time';
			$filter->order     = 'DESC';

			$allowed = Permission::get_allowed_course_ids();
			if ( is_array( $allowed ) ) {
				if ( empty( $allowed ) ) {
					$content->content     = __( 'No course found', 'learnpress-gradebook' );
					$content->total_pages = 0;
					$content->paged       = 1;

					return $content;
				}

				if ( ! empty( $course_ids ) ) {
					$course_ids = array_values( array_intersect( $course_ids, $allowed ) );
					if ( empty( $course_ids ) ) {
						$content->content     = __( 'No course found', 'learnpress-gradebook' );
						$content->total_pages = 0;
						$content->paged       = 1;

						return $content;
					}

					$filter->item_ids = $course_ids;
				} else {
					$filter->where[] = 'AND ui.item_id IN (' . Permission::get_scope_sql_in( $allowed ) . ')';
				}
			} elseif ( ! empty( $course_ids ) ) {
				$filter->item_ids = $course_ids;
			}

			$items       = $userItemsDB->get_user_items( $filter, $total_rows );
			$total_pages = UserItemsDB::get_total_pages( $limit, $total_rows );

			if ( empty( $items ) ) {
				$content->content = __( 'No course found', 'learnpress-gradebook' );
			} else {
				$row_html = '<tbody>';
				foreach ( $items as $item ) {
					$userCourseModel = UserCourseModel::find( $item->user_id, $item->item_id );
					if ( ! $userCourseModel ) {
						continue;
					}
					$row_html .= self::row_html( $userCourseModel );
				}
				$row_html .= '</tbody>';
				$section   = array(
					'table_start' => '<div class="table-container"><table class="lp-admin-table lp-gradebook-table">',
					'table_head'  => self::table_head(),
					'table_body'  => $row_html,
					'table_end'   => '</table></div>',
					'pagination'  => Template::instance()->html_pagination(
						array(
							'total_pages' => $total_pages,
							'paged'       => $paged,
						)
					),
				);
				// $content->content = 'test';
				$content->content = Template::combine_components( $section );
			}
			$content->total_pages = $total_pages;
			$content->paged       = $paged;
		} catch ( Throwable $e ) {
			$content->content = Template::print_message( $e->getMessage(), 'error', false );
		}

		return $content;
	}

	public static function row_html( $userCourseModel ) {
		$datetime_format  = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$results          = $userCourseModel->calculate_course_results();
		$course_link      = learn_press_gradebook_nonce_url(
			array(
				'course_id' => $userCourseModel->item_id,
				'screen'    => 2,
				'student'   => $userCourseModel->user_id,
			)
		);
		$progress_percent = $results['result'] ?? 0;

		$row_cells = array(
			'wrap'       => '<tr>',
			'course'     => sprintf( '<td class="course-name"><a href="%1$s"> %2$s</a></td>', $course_link, get_the_title( $userCourseModel->item_id ) ),
			'start-date' => sprintf( '<td class="start-time">%s</td>', wp_date( $datetime_format, strtotime( $userCourseModel->start_time ) ) ),
			'end-date'   => sprintf( '<td class="end-time">%s</td>', ( ! empty( $userCourseModel->end_time ) ? wp_date( $datetime_format, strtotime( $userCourseModel->end_time ) ) : '-' ) ),
			'progress'   => sprintf( '<td class="Progress">%s</td>', $progress_percent != 0 ? $progress_percent . ' %' : '-' ),
			'status'     => sprintf( '<td class="status %1$s">%2$s</td>', $userCourseModel->graduation, $userCourseModel->get_string_i18n( $userCourseModel->graduation ?? '' ) ),
			'wrap_end'   => '</tr>',
		);

		return Template::combine_components( $row_cells );
	}

	public static function table_head() {
		$fields = array(
			'wrap'       => '<thead><tr>',
			'course'     => sprintf( '<th>%s</th>', __( 'Course', 'learnpress-gradebook' ) ),
			'start-date' => sprintf( '<th>%s</th>', __( 'Start date', 'learnpress-gradebook' ) ),
			'end-date'   => sprintf( '<th>%s</th>', __( 'End date', 'learnpress-gradebook' ) ),
			'progress'   => sprintf( '<th>%s</th>', __( 'Progress', 'learnpress-gradebook' ) ),
			'status'     => sprintf( '<th>%s</th>', __( 'Status', 'learnpress-gradebook' ) ),
		);

		$fields['wrap_end'] = '</tr></thead>';

		return Template::combine_components( $fields );
	}

	public function html_filter( $args ): string {
		$course_selector = sprintf(
			'<div class="filter-field">
				<div class="label">%1$s</div>%2$s
			</div>',
			__( 'Course:', 'learnpress-gradebook' ),
			$this->html_filter_by_courses_field()
		);

		$form_sections = array(
			'wrap_start'     => '<form class="lp-form-admin-filter lp-form-gradebook-filter">',
			'course'         => $course_selector,
			'action_buttons' => $this->html_action_buttons(),
			'wrap_end'       => '</form>',
		);

		return Template::combine_components( $form_sections );
	}

	public static function html_filter_by_courses_field() {
		$data_struct_course = array(
			'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/search-course' ),
			'dataType'    => 'courses',
			'keyGetValue' => array(
				'value'      => 'ID',
				'text'       => '{{post_title}} (#{{ID}})',
				'key_render' => array(
					'post_title' => 'post_title',
					'ID'         => 'ID',
				),
			),
			'setting'     => array(
				'placeholder' => esc_html__( 'Choose Course', 'learnpress-collections' ),
			),
		);
		$filter_course      = new LP_Meta_Box_Select_Field(
			'',
			'',
			'',
			array(
				'options'           => '',
				'tom_select'        => true,
				'data-saved'        => 0,
				'multiple'          => true,
				'name_no_bracket'   => true,
				'custom_attributes' => array( 'data-struct' => htmlentities2( json_encode( $data_struct_course ) ) ),
			)
		);
		$filter_course->id  = 'course_ids';
		ob_start();
		$filter_course->output( 0 );

		return ob_get_clean();
	}

	/**
	 * HTML action buttons.
	 *
	 * @return string
	 */
	public function html_action_buttons(): string {
		$sections = array(
			'wrap_start' => '<div class="filter-actions" data-element=".lp-gradebook-student-detail">',
			'filter_btn' => sprintf(
				'<button class="lp-button button-primary %1$s"
					name="%1$s"
					type="button">%2$s</button>',
				'lp-btn-filter-gradebook',
				__( 'Filter', 'learnpress-gradebook' )
			),
			'reset_btn'  => sprintf(
				'<button class="lp-button button-secondary %1$s"
					name="%1$s"
					type="button">%2$s</button>',
				'lp-btn-reset-gradebook',
				__( 'Reset', 'learnpress-gradebook' )
			),
			'wrap_end'   => '</div>',
		);

		return Template::combine_components( $sections );
	}

	public function html_chart( int $user_id ): string {
		$buttons = array(
			'last7days'    => __( 'Last 7 days', 'learnpress-gradebook' ),
			'last30days'   => __( 'Last 30 days', 'learnpress-gradebook' ),
			'last12months' => __( 'Last 12 months', 'learnpress-gradebook' ),
		);

		$html_filter_chart = '';
		foreach ( $buttons as $filter => $label ) {
			$html_filter_chart .= sprintf(
				'<button class="lp-button lp-btn-gradebook-filter %s"
					data-filter="%s">%s</button>',
				( $filter === 'last7days' ? 'active' : '' ),
				$filter,
				$label
			);
		}

		$html_filter_wrap  = array(
			sprintf(
				'<div class="lp-gradebook-filter-chart-wrap" data-send="%s">',
				Template::convert_data_to_json(
					array(
						'action'  => 'lp_gradebook_data_chart_student',
						'user_id' => $user_id,
						'args'    => array(
							'id_url' => 'gradebook-chart-student-courses',
						),
					)
				),
			) => '</div>',
		);
		$html_filter_chart = Template::instance()->nest_elements( $html_filter_wrap, $html_filter_chart );

		$chart_canvas_html = '<div class="lp-gradebook-wrapper-chart-canvas"><canvas class="chart-canvas"></canvas></div>';

		ob_start();
		lp_skeleton_animation_html( 6 );
		$html_loading          = ob_get_clean();
		$course_chart_sections = array(
			'loading'   => $html_loading,
			'wrap'      => '<div class="lp-gradebook-chart-main lp-hidden">',
			'filter'    => $html_filter_chart,
			'char_html' => $chart_canvas_html,
			'wrap_end'  => '</div>',
		);

		return Template::combine_components( $course_chart_sections );
	}

	public static function section_header( $args ) {
		$user_id   = LP_Request::get_param( 'user_id', 0, 'int' );
		$user_data = get_userdata( $user_id );
		if ( ! $user_data ) {
			return '';
		}
		$data_struct_course = array(
			'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/search-course' ),
			'dataType'    => 'courses',
			'keyGetValue' => array(
				'value'      => 'ID',
				'text'       => '{{post_title}} (#{{ID}})',
				'key_render' => array(
					'post_title' => 'post_title',
					'ID'         => 'ID',
				),
			),
			'setting'     => array(
				'placeholder' => esc_html__( 'Choose Course', 'learnpress-collections' ),
			),
		);
		$student_course     = new LP_Meta_Box_Select_Field(
			'',
			'',
			'',
			array(
				'options'           => '',
				'tom_select'        => true,
				'data-saved'        => intval( $args['gradebook_student_course'] ?? 0 ),
				'multiple'          => false,
				'multil_meta'       => true,
				'custom_attributes' => array( 'data-struct' => htmlentities2( json_encode( $data_struct_course ) ) ),
			)
		);
		$student_course->id = 'gradebook_student_course';
		ob_start();
		$student_course->output( 0 );
		$student_course_select = ob_get_clean();

		$page_title = sprintf( '<h3>%s</h3>', ucfirst( $user_data->display_name ) . __( '\'s attended courses', 'learnpress-gradebook' ) );

		$buttons               = array(
			'last7days'    => __( 'Last 7 days', 'learnpress-gradebook' ),
			'last30days'   => __( 'Last 30 days', 'learnpress-gradebook' ),
			'last12months' => __( 'Last 12 months', 'learnpress-gradebook' ),
		);
		$bar_chart_filter_html = '<div class="bar-chart-filter">';
		foreach ( $buttons as $filter => $label ) {
			$bar_chart_filter_html .= sprintf( '<button class="bar-chart-filter-button" data-filter="%1$s">%2$s<span></span></button>', $filter, $label );
		}
		$bar_chart_filter_html .= '</div>';
		$bar_chart_canvas_html  = '<div><canvas class="bar-chart-canvas"></canvas></div>';
		$bar_chart_html         = '<div class="bar-chart-wrapper">' . $bar_chart_filter_html . $bar_chart_canvas_html . '</div>';

		$pie_chart_html        = '<div class="pie-chart-wrapper" style="min-width: 400px;"> <canvas class="pie-chart-canvas"></canvas> </div>';
		$course_chart_sections = array(
			'wrap_start'     => '<div class="course-chart-wrapper" style="display: none;">',
			'bar_char_html'  => $bar_chart_html,
			'pie_chart_html' => $pie_chart_html,
			'wrap_end'       => '</div>',
		);
		$course_chart_html     = Template::combine_components( $course_chart_sections );

		$view_chart_button            = sprintf( '<button class="view-chart-button"> %s <span class="dashicons dashicons-chart-area"></span> <span></span> </button>', __( 'View chart', 'learnpress-gradebook' ) );
		$export_csv_button            = sprintf( '<button class="lp-gradebook-export-csv" data-user="%d"> <span class="dashicons dashicons-download"></span> %s <span></span></button>', $user_id, __( 'Export CSV', 'learnpress-gradebook' ) );
		$student_name                 = sprintf( '<span>%1$s: %2$s</span>', __( 'Student', 'learnpress-gradebook' ), $user_data->display_name );
		$student_header_left_sections = array(
			'wrap_start'        => '<div class="left">',
			'view_chart_button' => $view_chart_button,
			'export_csv_button' => $export_csv_button,
			'student_name'      => $student_name,
			'wrap_end'          => '</div>',
		);
		$student_header_left_html     = Template::combine_components( $student_header_left_sections );

		$search_course_button          = sprintf( '<button class="search-student-course-button" type="button" > <span class="dashicons dashicons-search"></span> %s <span></span> </button>', __( 'Search', 'learnpress-gradebook' ) );
		$student_header_right_sections = array(
			'wrap_start'           => '<form class="lp-gradebook-filter">',
			'student_course'       => $student_course_select,
			'search_course_button' => $search_course_button,
			'wrap_end'             => '</form>',
		);
		$student_header_right_html     = Template::combine_components( $student_header_right_sections );

		$student_header_sections = array(
			'wrap_start' => '<div class="student-detail-header">',
			'left'       => $student_header_left_html,
			'right'      => $student_header_right_html,
			'wrap_end'   => '</div>',
		);
		$student_header_html     = Template::combine_components( $student_header_sections );

		$header_sections = array(
			'title'          => $page_title,
			'chart'          => $course_chart_html,
			'student_header' => $student_header_html,
		);

		return Template::combine_components( $header_sections );
	}
}
