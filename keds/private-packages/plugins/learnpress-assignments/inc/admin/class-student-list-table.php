<?php
/**
 * Class LP_Student_Assignment_List_Table.
 *
 * @author  ThimPress
 * @package LearnPress/Assignments/Classes
 * @version 4.0.0
 */

use LearnPress\Models\CourseModel;
use LearnPressAssignment\Models\AssignmentPostModel;

defined( 'ABSPATH' ) || exit;

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) || ! class_exists( 'WP_Posts_List_Table' ) ) {
	// include_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/comment.php';
	require_once ABSPATH . 'wp-admin/includes/post.php';
	require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
	require_once ABSPATH . 'wp-admin/includes/screen.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
	require_once ABSPATH . 'wp-admin/includes/template.php';
	$GLOBALS['hook_suffix'] = '';
}

if ( ! class_exists( 'LP_Student_Assignment_List_Table' ) ) {
	/**
	 * Class LP_Student_Assignment_List_Table
	 */
	class LP_Student_Assignment_List_Table extends WP_List_Table {

		/**
		 * @var LP_Assignment
		 */
		protected $assignment = null;

		/**
		 * @var int
		 */
		public $per_page = 10;

		/**
		 * LP_Student_Assignment_List_Table constructor.
		 *
		 * @param $assignment_id
		 */
		public function __construct( $assignment_id ) {
			parent::__construct();

			$this->assignment = AssignmentPostModel::find( $assignment_id, true );
			$this->per_page   = $this->get_items_per_page( 'users_per_page', $this->per_page );

			$this->prepare_items();
		}

		/**
		 * @return array
		 */
		public function get_columns() {
			$url = learn_press_assignment_students_url( $this->assignment->get_id() );

			return array(
				'id'         => esc_html__( 'ID', 'learnpress-assignments' ),
				'name'       => esc_html__( 'Name', 'learnpress-assignments' ),
				'email'      => esc_html__( 'Email', 'learnpress-assignments' ),
				'status'     => wp_kses( sprintf( __( '<a href="%s">Status</a>', 'learnpress-assignments' ), esc_url( $url ) ), array( 'a' => array( 'href' => array() ) ) ),
				'instructor' => wp_kses( sprintf( __( '<a href="%s">Instructor</a>', 'learnpress-assignments' ), esc_url( $url ) ), array( 'a' => array( 'href' => array() ) ) ),
				'mark'       => esc_html__( 'Mark', 'learnpress-assignments' ),
				'result'     => wp_kses( sprintf( __( '<a href="%s">Result</a>', 'learnpress-assignments' ), esc_url( $url ) ), array( 'a' => array( 'href' => array() ) ) ),
				'actions'    => esc_html__( 'Actions', 'learnpress-assignments' ),
			);
		}

		/**
		 * @param object $item
		 */
		public function column_cb( $item ) {
			echo '<input type="checkbox" name="items[]" value="' . $item . '">';
		}

		/**
		 * Prepare items.
		 */
		public function prepare_items() {
			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = array();
			$this->_column_headers = array( $columns, $hidden, $sortable );
			$this->items           = $this->get_users_assignment();
		}

		/**
		 * Get users attend assignment.
		 *
		 * @return array
		 * @since 4.1.2
		 * @version 1.0.0
		 */
		private function get_users_assignment(): array {
			$users_assignment = [];
			$total_rows       = 0;

			try {
				$key_search_user = LP_Request::get_param( 's' );
				$filter_status   = LP_Request::get_param( 'filter_status' );

				$lp_users_assignment_db = LP_User_Items_DB::getInstance();
				$filter                 = new LP_User_Items_Filter();
				$filter->item_id        = $this->assignment->get_id();
				$filter->item_type      = LP_ASSIGNMENT_CPT;
				$filter->where[]        = $lp_users_assignment_db->wpdb->prepare( 'AND ui.status IN (%s,%s)', 'completed', 'evaluated' );
				$filter->page           = LP_Request::get_param( 'paged', 1, 'int' );
				$filter->limit          = $this->per_page;
				$filter->join[]         = "INNER JOIN {$lp_users_assignment_db->tb_users} AS users ON ui.user_id = users.ID";
				if ( ! empty( $key_search_user ) ) {
					$filter->where[] = $lp_users_assignment_db->wpdb->prepare(
						'AND (users.user_login LIKE %s OR users.user_email LIKE %s OR users.display_name LIKE %s)',
						'%' . $key_search_user . '%',
						'%' . $key_search_user . '%',
						'%' . $key_search_user . '%'
					);
				}

				$users_assignment_rs = $lp_users_assignment_db->get_user_items( $filter, $total_rows );

				$this->set_pagination_args(
					array(
						'total_items' => $total_rows,
						'per_page'    => $this->per_page,
					)
				);

				foreach ( $users_assignment_rs as $user_assignment ) {
					$user = learn_press_get_user( $user_assignment->user_id );

					$users_assignment[] = array(
						'user'       => $user,
						'assignment' => $this->assignment,
						'course'     => CourseModel::find( $user_assignment->ref_id, true ),
					);
				}
			} catch ( Throwable $e ) {
				error_log( __METHOD__ . ': ' . $e->getMessage() );
			}

			return $users_assignment;
		}

		/**
		 * Get items.
		 *
		 * @return array
		 */
		private function get_items() {
			$items             = array();
			$students_per_page = $this->get_students_list();
			$total_per_page    = count( $students_per_page );
			$students_all      = $this->get_students_list( 1 );
			$total_all         = count( $students_all );

			$this->set_pagination_args(
				array(
					'total_items' => $total_all,
					'per_page'    => $this->per_page,
				)
			);

			for ( $i = 0; $i < $total_per_page; $i ++ ) {
				$user = learn_press_get_user( $students_per_page[ $i ]['ID'] );

				$items[] = array(
					'user'       => $user,
					'assignment' => $this->assignment,
				);
			}

			return $items;
		}

		/**
		 * @return array|null|object
		 */
		private function get_students_list( $get_all = 0 ) {
			global $wpdb;

			$paged             = LP_Request::get_param( 'paged', 1, 'int' );
			$limit_start       = ( $paged - 1 ) * $this->per_page;
			$assignment_id     = $this->assignment->get_id();
			$search            = LP_Request::get_param( 's' );
			$filter_status     = LP_Request::get_param( 'filter_status' );
			$filter_instructor = LP_Request::get_param( 'filter_instructor', null );
			$filter_result     = LP_Request::get_param( 'filter_result', null );

			$sql = "SELECT DISTINCT student.* FROM {$wpdb->users} AS student INNER JOIN {$wpdb->prefix}learnpress_user_items AS user_item ON user_item.user_id = student.ID";

			if ( ! is_null( $filter_instructor ) || ! is_null( $filter_result ) ) {
				$sql .= " LEFT JOIN {$wpdb->prefix}learnpress_user_itemmeta AS user_itemmeta  ON user_item.user_item_id = user_itemmeta.learnpress_user_item_id";
			}

			$sql .= $wpdb->prepare( " WHERE user_item.item_id = %d AND user_item.item_type = 'lp_assignment'", $assignment_id );

			if ( $search ) {
				$search = '%' . $wpdb->esc_like( $search ) . '%';
				$sql   .= $wpdb->prepare( ' AND (student.user_login LIKE %s OR student.user_email LIKE %s)', $search, $search );
			}

			if ( ! $filter_status ) {
				$sql .= " AND user_item.status IN ('completed', 'evaluated')";
			} else {
				$sql .= " AND user_item.status = '$filter_status'";
			}

			if ( ! empty( $filter_instructor ) ) {
				$sql .= $wpdb->prepare( " AND user_itemmeta.meta_key = '_lp_assignment_evaluate_author' AND user_itemmeta.meta_value = %s", $filter_instructor );
			}

			if ( ! empty( $filter_result ) ) {
				$sql .= $wpdb->prepare( ' AND user_item.graduation=%s', $filter_result );
			}

			if ( ! $get_all ) {
				$sql .= $wpdb->prepare( ' LIMIT %d, %d', $limit_start, $this->per_page );
			} else {
				$sql .= 'ORDER BY student.user_login ASC';
			}

			$students = $wpdb->get_results( $sql, ARRAY_A );

			return $students;
		}

		/**
		 * @param string $which
		 */
		protected function extra_tablenav( $which ) {
			if ( $which != 'top' ) {
				return;
			}
			?>
			<div class="alignleft actions search-box">
				<input type="search"
						placeholder="<?php esc_attr_e( 'Enter the student...', 'learnpress-assignments' ); ?>" name="s"
						value="<?php _admin_search_query(); ?>">
				<input type="submit" id="search-submit" class="button"
						value="<?php esc_attr_e( 'Search student', 'learnpress-assignments' ); ?>">
			</div>
			<?php
		}

		/**
		 * @param object $item
		 * @param string $column_name
		 */
		public function column_default( $item, $column_name ) {
			$user = $item['user'];
			/**
			 * @var $lp_assignment LP_Assignment
			 */
			$lp_assignment = $item['assignment'];
			$assignment_id = $lp_assignment->get_id();
			$courseModel   = $item['course'];

			$user_item_id = 0;
			$course_data  = $user->get_course_data( $courseModel->get_id() );
			if ( $course_data ) {
				$assignment_item = $course_data->get_item( $assignment_id );
				if ( $assignment_item ) {
					$user_item_id = $assignment_item->get_user_item_id();
				}
			}

			$mark       = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_mark', true );
			$instructor = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_evaluate_author', true );
			$evaluated  = $user->has_item_status( array( 'evaluated' ), $assignment_id, $courseModel->get_id() );

			switch ( $column_name ) {
				case 'id':
					echo $user->get_id();
					break;
				case 'name':
					echo '<strong>' . $user->get_data( 'user_login' ) . '</strong>';
					break;
				case 'email':
					echo $user->get_data( 'email' );
					break;
				case 'status':
					?>
					<?php $status = $evaluated ? __( 'Evaluated', 'learnpress-assignments' ) : __( 'Not evaluate', 'learnpress-assignments' ); ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'filter_status' => $evaluated ? 'evaluated' : 'completed' ) ) ); ?>"><?php echo $status; ?></a>
					<?php
					break;
				case 'instructor':
					$user = get_user_by( 'id', $instructor );
					$name = $user ? $user->user_login : '-';
					?>
					<a href="<?php echo esc_url( add_query_arg( array( 'filter_instructor' => $user ? $instructor : 0 ) ) ); ?>"><?php echo $name; ?></a>
					<?php
					break;
				case 'mark':
					echo $mark !== false ? $mark : '-';
					break;
				case 'result':
					if ( ! $evaluated ) {
						echo '-';
					} else {
						$pass   = $mark >= $lp_assignment->get_passing_grade();
						$result = $pass ? __( 'Passed', 'learnpress-assignments' ) : __( 'Failed', 'learnpress-assignments' );
						?>
						<a href="<?php echo esc_url( add_query_arg( array( 'filter_result' => $pass ? 'passed' : 'failed' ) ) ); ?>"><?php echo $result; ?></a>
						<?php
					}
					break;
				case 'actions':
					?>
					<div class="assignment-students-actions" data-user_id="<?php echo esc_attr( $user->get_id() ); ?>"
						data-assignment_id="<?php echo esc_attr( $lp_assignment->get_id() ); ?>"
						data-course_id="<?php echo esc_attr( $courseModel->get_id() ); ?>"
						data-user-item-id="<?php echo esc_attr( $user_item_id ); ?>">

						<?php
						printf(
							'<a href="%s" class="view" title="%s">%s</a>',
							learn_press_assignment_evaluate_url(
								[
									'user_id'       => $user->get_id(),
									'assignment_id' => $lp_assignment->get_id(),
									'course_id'     => $courseModel->get_id(),
								]
							),
							esc_attr__( 'Evaluate', 'learnpress-assignments' ),
							'<i class="dashicons dashicons-welcome-write-blog"></i>'
						);

						printf(
							'<a href="#" data-action="lp_assignment_delete_submission" title="%s">%s</a>',
							esc_attr__( 'Delete submission', 'learnpress-assignments' ),
							'<i class="dashicons dashicons-trash"></i>'
						);

						if ( $evaluated ) {
							printf(
								'<a href="#" data-action="lp_assignment_re_evaluate" title="%s">%s</a>',
								esc_attr__( 'Re Evaluate', 'learnpress-assignments' ),
								'<i class="dashicons dashicons-update"></i>'
							);
							printf(
								'<a href="#" data-action="lp_assignment_send_evaluated_mail" title="%s">%s</a>',
								esc_attr__( 'Send evaluated mail', 'learnpress-assignments' ),
								'<i class="dashicons dashicons-email-alt"></i>'
							);
						}
						?>
					</div>
					<?php
					break;
				default:
					break;
			}
		}

		function pagination( $which ) {
			global $mode;

			parent::pagination( $which );
		}
	}
}

if ( ! class_exists( 'LP_Assignment_fe_Student_List_Table' ) ) {
	class LP_Assignment_fe_Student_List_Table extends WP_List_Table {

		/**
		 * @var LP_Assignment
		 */
		protected $assignment = null;

		/**
		 * @var int
		 */
		public $per_page = 10;

		/**
		 * LP_Student_Assignment_List_Table constructor.
		 *
		 * @param $assignment_id
		 */
		public function __construct( $assignment_id ) {
			parent::__construct();

			$this->assignment = learn_press_get_assignment( $assignment_id );
			$this->per_page   = $this->get_items_per_page( 'users_per_page', $this->per_page );

			$this->prepare_items();

			add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		}

		/**
		 * Assets
		 */
		public function assets() {
			wp_enqueue_style( 'list-table', get_site_url() . '/wp-admin/css/list-tables.css' );
			wp_enqueue_style( 'list-table', get_site_url() . '/wp-admin/css/common.css' );
			wp_enqueue_style( 'list-table', get_site_url() . '/wp-admin/css/dashboard.css' );
		}

		/**
		 * @return array
		 */
		public function get_columns() {
			return array(
				'id'         => esc_html__( 'ID', 'learnpress-assignments' ),
				'name'       => esc_html__( 'Name', 'learnpress-assignments' ),
				'email'      => esc_html__( 'Email', 'learnpress-assignments' ),
				'status'     => esc_html__( 'Status', 'learnpress-assignments' ),
				'instructor' => esc_html__( 'Instructor', 'learnpress-assignments' ),
				'mark'       => esc_html__( 'Mark', 'learnpress-assignments' ),
				'result'     => esc_html__( 'Result', 'learnpress-assignments' ),
				'actions'    => esc_html__( 'Actions', 'learnpress-assignments' ),
			);
		}

		/**
		 * @param object $item
		 */
		public function column_cb( $item ) {
			echo '<input type="checkbox" name="items[]" value="' . $item . '">';
		}

		/**
		 * Prepare items.
		 */
		public function prepare_items() {
			$columns  = $this->get_columns();
			$hidden   = array();
			$sortable = array();

			$this->_column_headers = array( $columns, $hidden, $sortable, '' );
			$this->items           = $this->get_items();
		}

		/**
		 * Get items.
		 *
		 * @return array
		 */
		private function get_items() {
			$items             = array();
			$students_per_page = $this->get_students_list();
			$total_per_page    = count( $students_per_page );
			$students_all      = $this->get_students_list( 1 );
			$total_all         = count( $students_all );

			$this->set_pagination_args(
				array(
					'total_items' => $total_all,
					'per_page'    => $this->per_page,
				)
			);

			for ( $i = 0; $i < $total_per_page; $i ++ ) {
				$user = learn_press_get_user( $students_per_page[ $i ]['ID'] );

				$items[] = array(
					'user'       => $user,
					'assignment' => $this->assignment,
				);
			}

			return $items;
		}

		/**
		 * @return array|null|object
		 */
		private function get_students_list( $get_all = 0 ) {
			global $wpdb;

			$paged             = isset( $_GET['paged'] ) ? $_GET['paged'] : 1;
			$limit_start       = ( $paged - 1 ) * $this->per_page;
			$assignment_id     = $this->assignment->get_id();
			$search            = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : '';
			$filter_status     = ( isset( $_REQUEST['filter_status'] ) ) ? $_REQUEST['filter_status'] : '';
			$filter_instructor = ( isset( $_REQUEST['filter_instructor'] ) ) ? $_REQUEST['filter_instructor'] : null;
			$filter_result     = ( isset( $_REQUEST['filter_result'] ) ) ? $_REQUEST['filter_result'] : null;

			$sql = "SELECT DISTINCT student.* FROM {$wpdb->users} AS student
				INNER JOIN {$wpdb->prefix}learnpress_user_items AS user_item  ON user_item.user_id = student.ID ";

			if ( ! is_null( $filter_instructor ) || ! is_null( $filter_result ) ) {
				$sql .= "LEFT JOIN {$wpdb->prefix}learnpress_user_itemmeta AS user_itemmeta  ON user_item.user_item_id = user_itemmeta.learnpress_user_item_id ";
			}

			$sql .= "WHERE user_item.item_id = $assignment_id AND user_item.item_type = 'lp_assignment'";

			if ( $search ) {
				$sql .= " AND (student.user_login LIKE '%%{$search}%%' OR student.user_email LIKE '%%{$search}%%')";
			}

			if ( ! $filter_status ) {
				$sql .= "AND user_item.status IN ('completed', 'evaluated')";
			} else {
				$sql .= "AND user_item.status = '$filter_status'";
			}

			if ( ! is_null( $filter_instructor ) ) {
				$sql .= " AND user_itemmeta.meta_key = '_lp_assignment_evaluate_author' AND user_itemmeta.meta_value = $filter_instructor";
			}

			if ( ! empty( $filter_result ) ) {
				$sql .= $wpdb->prepare( ' AND user_item.graduation=%s', $filter_result );
			}

			if ( ! $get_all ) {
				$sql  .= ' LIMIT %d, %d';
				$query = $wpdb->prepare( $sql, $limit_start, $this->per_page );
			} else {
				$sql  .= 'ORDER BY %s';
				$query = $wpdb->prepare( $sql, 'student.user_login ASC' );
			}
			$students = $wpdb->get_results( $query, ARRAY_A );

			return $students;
		}

		/**
		 * @param string $which
		 */
		protected function extra_tablenav( $which ) {
			if ( $which != 'top' ) {
				return;
			}
		}

		/**
		 * @param object $item
		 * @param string $column_name
		 */
		public function column_default( $item, $column_name ) {
			/**
			 * @var $user LP_User
			 */
			$user = $item['user'];

			/**
			 * @var $lp_assignment LP_Assignment
			 */
			$lp_assignment = $item['assignment'];
			$assignment_id = $lp_assignment->get_id();

			$course       = learn_press_get_item_courses( $assignment_id );
			$lp_course    = learn_press_get_course( $course[0]->ID );
			$user_item_id = 0;
			$course_data  = $user->get_course_data( $lp_course->get_id() );
			if ( $course_data ) {
				$assignment_item = $course_data->get_item( $assignment_id );
				if ( $assignment_item ) {
					$user_item_id = $assignment_item->get_user_item_id();
				}
			}

			$mark       = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_mark', true );
			$instructor = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_evaluate_author', true );
			$evaluated  = $user->has_item_status( array( 'evaluated' ), $assignment_id, $lp_course->get_id() );

			switch ( $column_name ) {
				case 'id':
					echo $user->get_id();
					break;
				case 'name':
					echo $user->get_data( 'user_login' );
					break;
				case 'email':
					echo $user->get_data( 'email' );
					break;
				case 'status':
					$status = $evaluated ? __( 'Evaluated', 'learnpress-assignments' ) : __( 'Not evaluate', 'learnpress-assignments' );
					echo $status;
					break;
				case 'instructor':
					$user = get_user_by( 'id', $instructor );
					$name = $user ? $user->user_login : '-';
					echo $name;
					break;
				case 'mark':
					echo $mark ? $mark : '-';
					break;
				case 'result':
					if ( ! $evaluated ) {
						echo '-';
					} else {
						$pass   = $mark >= $lp_assignment->get_data( 'passing_grade' );
						$result = $pass ? __( 'Passed', 'learnpress-assignments' ) : __( 'Failed', 'learnpress-assignments' );
						echo $result;
					}
					break;
				case 'actions':
					$eval_page = get_option( 'assignment_evaluate_page_id' );
					$eval_url  = get_page_link( $eval_page ) . '?assignment_id=' . $lp_assignment->get_id() . '&user_id=' . $user->get_id();
					?>
					<div class="assignment-students-actions" data-user_id="<?php echo esc_attr( $user->get_id() ); ?>"
						data-assignment_id="<?php echo esc_attr( $lp_assignment->get_id() ); ?>"
						data-recommend="
						<?php
						if ( ! $user_item_id ) {
							esc_attr__( 'Something wrong! Should delete this!', 'learnpress-assignments' );
						}
						?>
							"
						data-user-item-id="<?php echo esc_attr( $user_item_id ); ?>">
						<?php
						printf( '<a href="%s" class="view" title="%s"><i class="dashicons dashicons-welcome-write-blog"></i></a>', $eval_url, esc_attr__( 'Evaluate', 'learnpress-assignments' ) );
						printf(
							' <a href="%s" class="delete" title="%s"><i class="dashicons dashicons-trash"></i></a>',
							'#',
							esc_attr__( 'Delete submission', 'learnpress-assignments' )
						);
						if ( $evaluated ) {
							printf( '<a href="%s" class="reset" title="%s"><i class="dashicons dashicons-update"></i></a>', '#', esc_attr__( 'Reset result', 'learnpress-assignments' ) );
							printf( '<a href="%s" class="send-mail" title="%s"><i class="dashicons dashicons-email-alt"></i></a>', '#', esc_attr__( 'Send evaluated mail', 'learnpress-assignments' ) );
						}
						?>
					</div>
					<?php
					break;
				default:
					break;
			}
		}

		function pagination( $which ) {
			global $mode;

			parent::pagination( $which );
		}
	}
}
