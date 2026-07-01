<?php
namespace LearnPress\Gradebook\TemplateHooks\Admin;

use Braintree\Exception;
use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Helper;
use stdClass;
use Throwable;
use LP_Meta_Box_Select_Field;
use LP_Request;

/**
 * Class AdminStudentOverviewTemplate
 *
 * @since 4.0.8
 * @version 1.0.0
 */
class AdminStudentOverviewTemplate {
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
		$callbacks[] = self::class . ':render_content';

		return $callbacks;
	}

	public function layout( array $data = [] ) {
		$tab     = $data['tab'] ?? '';
		$section = $data['section'] ?? '';
		if ( $tab !== 'student-overview' || ! empty( $section ) ) {
			return;
		}

		wp_enqueue_style( 'lp-gradebook-admin-style' );
		wp_enqueue_script( 'lp-gradebook-admin-script' );
		$args = array(
			'id_url' => 'gradebook-overview',
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

		$ajax_content = TemplateAJAX::load_content_via_ajax( $args, $callback );
		$section      = array(
			'wrap'         => '<div class="lp-gradebook-student-overview wrap">',
			'header'       => sprintf(
				'<h1>%s</h1>',
				__( 'List students attend courses', 'learnpress-gradebook' )
			),
			'search'       => $this->html_filter( $args ),
			'ajax_content' => $ajax_content,
			'wrap_end'     => '</div>',
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
	public static function render_content( array $args ): stdClass {
		$content = new stdClass();

		try {
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( __( 'You do not have permission to access this page.', 'learnpress-gradebook' ) );
			}

			$user_ids = LP_Helper::sanitize_params_submitted( $args['user_ids'] ?? '' );
			$limit    = intval( $args['limit'] ?? 10 );
			$paged    = intval( $args['paged'] ?? 1 );

			$filter              = new UserItemsFilter();
			$total_rows          = 0;
			$userItemsDB         = UserItemsDB::getInstance();
			$filter->only_fields = array(
				'DISTINCT(u.ID) as user_id',
				'SUM(ui.graduation = "in-progress" ) AS inprogress',
				'SUM(ui.graduation = "passed" ) AS passed',
				'SUM(ui.graduation = "failed" ) AS failed',
				'COUNT(ui.user_item_id) AS total_courses',
			);
			// join to check deleted user
			$filter->join[]      = "INNER JOIN {$userItemsDB->tb_users} AS u ON ui.user_id = u.ID";
			$filter->item_type   = LP_COURSE_CPT;
			$filter->order_by    = 'total_courses';
			$filter->order       = 'DESC';
			$filter->limit       = $limit;
			$filter->page        = $paged;
			$filter->field_count = 'user_id';
			$filter->group_by    = 'user_id';

			// Filter by user ids.
			if ( ! empty( $user_ids ) ) {
				$filter->where[] = "AND ui.user_id IN ({$user_ids})";
			}

			$items       = $userItemsDB->get_user_items( $filter, $total_rows );
			$total_pages = UserItemsDB::get_total_pages( $limit, $total_rows );

			if ( empty( $items ) ) {
				$content->content = sprintf( '<p>%s</p>', __( 'No user found.', 'learnpress-gradebook' ) );
			} else {
				$row_html = '<tbody>';
				foreach ( $items as $itemObj ) {
					$row_html .= self::render_item( $itemObj );
				}
				$row_html .= '</tbody>';

				$section          = array(
					'table_start' => '<div class="table-container"><table class="lp-admin-table lp-gradebook-table">',
					'table_head'  => self::html_table_head(),
					'table_body'  => $row_html,
					'table_end'   => '</table>',
					'pagination'  => Template::instance()->html_pagination(
						[
							'paged'       => $paged,
							'total_pages' => $total_pages,
						]
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
	 * Render a row item.
	 *
	 * @param $itemObj
	 *
	 * @return string
	 */
	public static function render_item( $itemObj ): string {
		$userModel = UserModel::find( $itemObj->user_id );

		$row_fields = array(
			'wrap'        => '<tr>',
			'student'     => sprintf(
				'<td><a href="%1$s">%2$s (%3$s)</a></td>',
				'?page=learnpress-gradebook&tab=overview&section=student-detail&user_id=' . $userModel->get_id(),
				$userModel->get_display_name(),
				$userModel->get_email()
			),
			'in-progress' => sprintf( '<td class="courses-graduation">%s</td>', $itemObj->inprogress ?? '' ),
			'passed'      => sprintf( '<td class="courses-graduation">%s</td>', $itemObj->passed ?? '' ),
			'failed'      => sprintf( '<td class="courses-graduation">%s</td>', $itemObj->failed ?? '' ),
			'total'       => sprintf( '<td class="courses-graduation">%s</td>', $itemObj->total_courses ?? '' ),
			'link-view'   => sprintf(
				'<td class="action">
					<a href="%1$s" class="view-link">%2$s</a>
				</td>',
				'?page=learnpress-gradebook&tab=student-overview&section=student-detail&user_id=' . $userModel->get_id(),
				__( 'View', 'learnpress-gradebook' )
			),
			'wrap_end'    => '</tr>',
		);

		return Template::combine_components( $row_fields );
	}

	public static function html_table_head(): string {
		$fields = array(
			'wrap_start'    => '<thead><tr>',
			'student'       => sprintf( '<th>%s</th>', __( 'Student', 'learnpress-gradebook' ) ),
			'in-progress'   => sprintf( '<th class="count">%s</th>', __( 'In Progress', 'learnpress-gradebook' ) ),
			'passed'        => sprintf( '<th class="count">%s</th>', __( 'Passed', 'learnpress-gradebook' ) ),
			'failed'        => sprintf( '<th class="count">%s</th>', __( 'Failed', 'learnpress-gradebook' ) ),
			'total-courses' => sprintf( '<th class="count">%s</th>', __( 'Total Courses', 'learnpress-gradebook' ) ),
			'link-view'     => sprintf( '<th>%s</th>', __( 'Action', 'learnpress-gradebook' ) ),
			'wrap_end'      => '</tr></thead>',
		);

		return Template::combine_components( $fields );
	}

	/**
	 * HTML filter form.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function html_filter( array $args ): string {
		$user_selector = sprintf(
			'<div class="filter-field">
				<div class="label">%1$s</div>%2$s
				</div>',
			__( 'User:', 'learnpress-gradebook' ),
			$this->html_filter_by_users_field( $args )
		);

		$form_sections = array(
			'wrap_start'     => '<form class="lp-form-admin-filter lp-form-gradebook-filter">',
			'users'          => $user_selector,
			'action_buttons' => $this->html_action_buttons(),
			'wrap_end'       => '</form>',
		);

		return Template::combine_components( $form_sections );
	}

	/**
	 * HTML filter by users field.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public static function html_filter_by_users_field( array $args ): string {
		$data_struct_user = array(
			'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/search-user' ),
			'dataType'    => 'users',
			'keyGetValue' => array(
				'value'      => 'ID',
				'text'       => '{{display_name}}(#{{ID}}) - {{user_email}}',
				'key_render' => array(
					'display_name' => 'display_name',
					'user_email'   => 'user_email',
					'ID'           => 'ID',
				),
			),
			'setting'     => array(
				'placeholder' => esc_html__( 'Choose User', 'learnpress' ),
			),
		);
		$filter_user      = new LP_Meta_Box_Select_Field(
			'',
			'',
			'',
			array(
				'options'           => array(),
				'tom_select'        => true,
				'data-saved'        => [],
				'multiple'          => true,
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
	 * HTML action buttons.
	 *
	 * @return string
	 */
	public function html_action_buttons(): string {
		$sections = array(
			'wrap_start' => '<div class="filter-actions" data-element=".lp-gradebook-student-overview">',
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
}
