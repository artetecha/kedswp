<?php

namespace LearnPress\Gradebook\TemplateHooks\Admin;

use Exception;
use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Gradebook\Permission;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Datetime;
use LP_Profile;
use stdClass;
use Throwable;
use WP_Error;
use LP_User_Items_DB;
use LP_Helper;
use LP_Meta_Box_Select_Field;
use LP_Request;

/**
 * Class AdminRecentActivityTemplate
 *
 * @package LearnPress\Gradebook\TemplateHooks\Admin
 *
 * @since 4.0.8
 * @version 1.0.0
 */
class AdminRecentActivityTemplate {
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

	/**
	 * Layout for recent activity tab.
	 */
	public function layout( array $data = array() ) {
		$current_tab = $data['tab'] ?? '';
		if ( $current_tab !== 'recent-activity' ) {
			return;
		}

		wp_enqueue_style( 'lp-gradebook-admin-style' );
		wp_enqueue_script( 'lp-gradebook-admin-script' );

		$args = array(
			'id_url' => 'gradebook-recent-activity',
			'paged'  => LP_Request::get_param( 'paged', 1, 'int' ),
			'limit'  => 10,
		);

		/**
		 * @use self::render_content
		 */
		$callback = array(
			'class'  => self::class,
			'method' => 'render_content',
		);
		$filter   = $this->html_filter( $args );
		$content  = TemplateAJAX::load_content_via_ajax( $args, $callback );
		$section  = array(
			'wrap'     => '<div class="lp-gradebook-recent-activity wrap">',
			'filter'   => $filter,
			'content'  => $content,
			'wrap_end' => '</div>',
		);
		echo Template::combine_components( $section );
	}

	/**
	 * Render content for recent activity tab.
	 *
	 * @param array $args
	 *
	 * @return stdClass
	 * @throws WP_Error
	 * @since 4.0.8
	 * @version 1.0.0
	 */
	public static function render_content( array $args ): stdClass {
		$content = new stdClass();
		try {
			if ( ! Permission::can_view_gradebook() ) {
				throw new Exception( __( 'You do not have permission to access this page.', 'learnpress-gradebook' ) );
			}

			$paged      = max( 1, intval( $args['paged'] ?? 1 ) );
			$limit      = min( 100, max( 1, intval( $args['limit'] ?? 10 ) ) );
			$start_date = LP_Helper::sanitize_params_submitted( $args['start_date'] ?? '' );
			$end_date   = LP_Helper::sanitize_params_submitted( $args['end_date'] ?? '' );
			$course_ids = wp_parse_id_list( $args['course_ids'] ?? '' );
			$user_ids   = wp_parse_id_list( $args['user_ids'] ?? '' );
			$status     = LP_Helper::sanitize_params_submitted( $args['status'] ?? '' );

			$filter     = new UserItemsFilter();
			$total_rows = 0;

			$userItemsDB      = UserItemsDB::getInstance();
			$filter->order_by = "GREATEST(COALESCE(start_time, '0000-00-00 00:00:00'), COALESCE(end_time, '0000-00-00 00:00:00'))";
			$filter->order    = 'DESC';
			$filter->limit    = $limit;
			$filter->page     = $paged;

			$allowed = Permission::get_allowed_course_ids();
			if ( is_array( $allowed ) && empty( $allowed ) ) {
				$content->content     = sprintf( '<p>%s</p>', esc_html__( 'No activities found!', 'learnpress' ) );
				$content->total_pages = 0;
				$content->paged       = 1;

				return $content;
			}

			if ( ! empty( $start_date ) ) {
				$gmt_from_date   = get_gmt_from_date( "$start_date 00:00:00" );
				$filter->where[] = $userItemsDB->wpdb->prepare(
					"AND GREATEST(COALESCE(start_time, '0000-00-00 00:00:00'), COALESCE(end_time, '0000-00-00 00:00:00')) >= %s",
					$gmt_from_date
				);
			}

			if ( ! empty( $end_date ) ) {
				$gmt_to_date     = get_gmt_from_date( "$end_date 23:59:59" );
				$filter->where[] = $userItemsDB->wpdb->prepare(
					"AND GREATEST(COALESCE(start_time, '0000-00-00 00:00:00'), COALESCE(end_time, '0000-00-00 00:00:00')) <= %s",
					$gmt_to_date
				);
			}

			// Find item by course IDs and item_type = course or ref_type = course.
			if ( is_array( $allowed ) ) {
				if ( ! empty( $course_ids ) ) {
					$course_ids = array_values( array_intersect( $course_ids, $allowed ) );
					if ( empty( $course_ids ) ) {
						$content->content     = sprintf( '<p>%s</p>', esc_html__( 'No activities found!', 'learnpress' ) );
						$content->total_pages = 0;
						$content->paged       = 1;

						return $content;
					}
				} else {
					$course_ids = $allowed;
				}
			}

			if ( ! empty( $course_ids ) ) {
				$course_ids_in   = implode( ',', $course_ids );
				$filter->where[] = $userItemsDB->wpdb->prepare(
					"AND ( (ui.item_id IN ({$course_ids_in}) AND ui.item_type = %s) OR (ui.ref_id IN ({$course_ids_in}) AND ui.ref_type = %s) )",
					LP_COURSE_CPT,
					LP_COURSE_CPT
				);
			}

			if ( ! empty( $user_ids ) ) {
				$filter->where[] = 'AND ui.user_id IN (' . implode( ',', $user_ids ) . ')';
			}

			if ( ! empty( $status ) && $status !== 'all' ) {
				if ( in_array(
					$status,
					array(
						UserItemModel::GRADUATION_IN_PROGRESS,
						UserItemModel::GRADUATION_FAILED,
						UserItemModel::GRADUATION_PASSED,
					),
					true
				) ) {
					$filter->graduation = $status;
				} else {
					$filter->status = $status;
				}
			}

			$items = $userItemsDB->get_user_items( $filter, $total_rows );

			$total_pages = LP_User_Items_DB::get_total_pages( $limit, $total_rows );

			if ( empty( $items ) ) {
				$content->content = sprintf(
					'<p>%s</p>',
					esc_html__( 'No activities found!', 'learnpress' )
				);
			} else {
				$activities = '';
				foreach ( $items as $item ) {
					$userItemModel = UserItemModel::find_user_item( $item->user_id, $item->item_id, $item->item_type, $item->ref_id );
					if ( ! $userItemModel ) {
						continue;
					}

					$activities .= self::render_item( $userItemModel );
				}

				$section          = array(
					'wrap_start' => '<div class="recent-activity-container">',
					'activities' => $activities,
					'wrap_end'   => '</div>',
					'pagination' => Template::instance()->html_pagination(
						array(
							'total_pages' => $total_pages,
							'paged'       => $paged,
						)
					),
				);
				$content->content = Template::combine_components( $section );
			}
			$content->total_pages = $total_pages;
			$content->paged       = $paged;
		} catch ( Throwable $e ) {
			$content->content = Template::print_message( $e->getMessage(), 'error', false );
		}

		return $content;
	}

	/**
	 * HTML for each item.
	 *
	 * @param UserItemModel $userItemModel
	 *
	 * @return string
	 */
	public static function render_item( UserItemModel $userItemModel ): string {
		$lp_profile_user = LP_Profile::instance( $userItemModel->user_id );
		$userModel       = $userItemModel->get_user_model();
		if ( $userModel ) {
			$user_name = sprintf(
				'<a href="%1$s" ><strong>%2$s</strong></a>',
				$lp_profile_user->get_tab_link(),
				$userModel->get_display_name()
			);
		} elseif ( $userItemModel->user_id == 0 ) {
			if ( $userItemModel->ref_type === LP_ORDER_CPT ) {
				$lp_order    = learn_press_get_order( $userItemModel->ref_id );
				$guest_email = $lp_order ? $lp_order->get_user_email() : '';
				$user_name   = sprintf(
					'%s (%s)',
					__( 'Guest', 'learnpress-gradebook' ),
					$guest_email
				);
			} else {
				$user_name = __( 'Guest', 'learnpress-gradebook' );
			}
		} else {
			$user_name = sprintf( __( 'User ID: %d (Deleted)', 'learnpress-gradebook' ), $userItemModel->user_id );
		}

		$date_time     = ! empty( $userItemModel->end_time ) ? $userItemModel->end_time : $userItemModel->start_time;
		$lp_date_time  = new LP_Datetime( $date_time );
		$display_time  = $lp_date_time->format( LP_Datetime::I18N_FORMAT_HAS_TIME );
		$diff_time     = (int) abs( time() - $lp_date_time->getTimestamp() );
		$has_just_text = '';
		if ( $diff_time < HOUR_IN_SECONDS ) {
			$has_just_text = __( 'just', 'learnpress-gradebook' ) . ' ';
		}

		$status = $userItemModel->get_status();
		if ( empty( $status ) ) {
			$status = 'started';
		} elseif ( ! empty( $userItemModel->get_graduation() )
			&& $userItemModel->get_graduation() !== UserItemModel::GRADUATION_IN_PROGRESS ) {
			$status = $userItemModel->get_graduation();
		}

		$status_label = self::get_status_label( $status );

		$html_activity_content = '';
		if ( $userItemModel->item_type === LP_COURSE_CPT ) {
			$userCourseModel = new UserCourseModel( $userItemModel );
			$courseModel     = $userCourseModel->get_course_model();
			$coursePostModel = new CoursePostModel( $courseModel );

			$html_activity_content = sprintf(
				__( '%1$s %2$s in the Course %3$s', 'learnpress-gradebook' ),
				$user_name,
				$has_just_text . $status_label,
				sprintf(
					'<a href="%1$s" ><strong>%2$s</strong></a>',
					$coursePostModel->get_permalink(),
					$courseModel->get_title()
				),
			);
		} elseif ( $userItemModel->ref_type === LP_COURSE_CPT ) {
			$courseModel       = CourseModel::find( $userItemModel->ref_id, true );
			$itemModelOfCourse = $courseModel->get_item_model( $userItemModel->item_id, $userItemModel->item_type );
			$item_type_label   = CourseModel::item_types_label( $userItemModel->item_type );

			$html_activity_content = sprintf(
				__( '%1$s %2$s the %3$s %4$s of the Course %5$s', 'learnpress-gradebook' ),
				$user_name,
				$has_just_text . $status_label,
				$item_type_label,
				sprintf(
					'<a href="%1$s" ><strong>%2$s</strong></a>',
					$itemModelOfCourse->get_permalink(),
					$itemModelOfCourse->get_the_title()
				),
				sprintf(
					'<a href="%1$s"><strong>%2$s</strong></a>',
					$courseModel->get_permalink(),
					$courseModel->get_title()
				),
			);
		}

		if ( ! $html_activity_content ) {
			return '';
		}

		$section = array(
			'wrap'     => "<div class='activity-item'>",
			'main'     => sprintf(
				'<div class="activity-content">%s</div>',
				$html_activity_content
			),
			'time'     => sprintf(
				'<div class="activity-time">%s</div>',
				$display_time
			),
			'wrap_end' => '</div>',
		);

		return Template::combine_components( $section );
	}

	public function html_filter( $args ): string {
		$html_start_date = sprintf(
			'<div class="filter-field">
				<div class="label">%1$s</div>
				<input type="date" name="%2$s" class="%2$s">
			</div>',
			__( 'From', 'learnpress-gradebook' ),
			'start_date',
		);
		$html_end_date   = sprintf(
			'<div class="filter-field">
				<div class="label">%1$s</div>
				<input type="date" name="%2$s" class="%2$s">
			</div>',
			__( 'To', 'learnpress-gradebook' ),
			'end_date',
		);
		$course_selector = sprintf(
			'<div class="filter-field">
				<div class="label">%1$s</div>%2$s
			</div>',
			__( 'Course', 'learnpress-gradebook' ),
			$this->html_filter_by_courses_field()
		);
		$user_selector   = sprintf(
			'<div class="filter-field">
				<div class="label">%1$s</div>%2$s
				</div>',
			__( 'User', 'learnpress-gradebook' ),
			$this->html_filter_by_users_field( $args )
		);
		$status_selector = $this->html_filter_status_field();

		$form_sections = array(
			'wrap_start'      => '<form class="lp-form-admin-filter lp-form-gradebook-filter">',
			'start_date'      => $html_start_date,
			'end_date'        => $html_end_date,
			'course'          => $course_selector,
			'users'           => $user_selector,
			'status_selector' => $status_selector,
			'action_buttons'  => $this->html_action_buttons(),
			'wrap_end'        => '</form>',
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
				'multiple'          => false,
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
	 * HTML filter by users field.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function html_filter_by_users_field( array $args ): string {
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
				'options'           => array(),
				'tom_select'        => true,
				'data-saved'        => array(),
				'multiple'          => false,
				'name_no_bracket'   => true,
				'custom_attributes' => array( 'data-struct' => htmlentities2( json_encode( $data_struct_user ) ) ),
			)
		);
		$filter_user->id  = 'user_ids';
		ob_start();
		$filter_user->output( 0 );

		return ob_get_clean();
	}

	/**
	 * HTML filter status field.
	 *
	 * @return string
	 */
	public static function html_filter_status_field(): string {
		$options = apply_filters(
			'lp/gradebook/recent-activity/status-options',
			array(
				'all'                                 => __( 'All', 'learnpress-gradebook' ),
				UserItemModel::STATUS_FINISHED        => __( 'Finished', 'learnpress-gradebook' ),
				UserItemModel::GRADUATION_PASSED      => __( 'Passed', 'learnpress-gradebook' ),
				UserItemModel::GRADUATION_FAILED      => __( 'Failed', 'learnpress-gradebook' ),
				UserItemModel::GRADUATION_IN_PROGRESS => __( 'In-progress (Course)', 'learnpress-gradebook' ),
			)
		);

		$select_html = sprintf( '<select name="%1$s" class="%1$s">', 'status' );
		if ( ! empty( $options ) ) {
			foreach ( $options as $key => $opt_label ) {
				$select_html .= sprintf( '<option value="%1$s">%2$s</option>', $key, $opt_label );
			}
		}
		$select_html .= '</select>';

		$sections = array(
			'wrap'         => '<div class="filter-field">',
			'select_label' => sprintf(
				'<div class="label">%s</div>',
				__( 'Status', 'learnpress-gradebook' )
			),
			'select_html'  => $select_html,
			'wrap_end'     => '</div>',
		);

		return Template::combine_components( $sections );
	}

	/**
	 * HTML action buttons.
	 *
	 * @return string
	 */
	public function html_action_buttons(): string {
		$sections = array(
			'wrap_start' => '<div class="filter-actions" data-element=".lp-gradebook-recent-activity">',
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

	public static function get_status_label( $status = '' ) {
		if ( ! $status ) {
			$status = LP_ITEM_STARTED;
		}

		$statuses = array(
			LP_COURSE_ENROLLED               => esc_html__( 'enrolled', 'learnpress-gradebook' ),
			LP_COURSE_PURCHASED              => esc_html__( 'purchased', 'learnpress-gradebook' ),
			LP_ITEM_COMPLETED                => esc_html__( 'completed', 'learnpress-gradebook' ),
			LP_ITEM_STARTED                  => esc_html__( 'started', 'learnpress-gradebook' ),
			LP_COURSE_FINISHED               => esc_html__( 'finished', 'learnpress-gradebook' ),
			LP_COURSE_GRADUATION_PASSED      => esc_html__( 'passed', 'learnpress-gradebook' ),
			LP_COURSE_GRADUATION_FAILED      => esc_html__( 'failed', 'learnpress-gradebook' ),
			LP_COURSE_GRADUATION_IN_PROGRESS => esc_html__( 'in progress', 'learnpress-gradebook' ),
		);

		return $statuses[ $status ] ?? $status;
	}
}
