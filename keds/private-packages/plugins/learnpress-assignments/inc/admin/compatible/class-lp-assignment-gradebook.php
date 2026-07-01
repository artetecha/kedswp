<?php
/**
 * Class LP_Assignment_Gradebook.
 *
 * @author  ThimPress
 * @package LearnPress/Assignments/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Assignment_Gradebook' ) ) {

	/**
	 * Class LP_Assignment_Gradebook
	 */
	class LP_Assignment_Gradebook {

		/**
		 * LP_Assignment_Gradebook constructor.
		 */
		public function __construct() {

			if ( ! class_exists( 'LP_Addon_Gradebook_Preload' ) ) {
				return;
			}

			// add assignment to gradebook query item types
			add_filter( 'learn-press/gradebook/query-item-types', array( $this, 'add_query_type' ) );

			add_action(
				'learn-press/gradebook/details-view/passing-grade',
				[
					$this,
					'passing_grade_details_view',
				]
			);

			add_action( 'learn-press/gradebook/details-view/result', array( $this, 'result_details_view' ) );

			add_action( 'learn-press/gradebook/profile-item-result', array( $this, 'profile_item_result' ) );

			add_filter( 'learn-press/gradebook/export-item-result', array( $this, 'export_assignment_result' ), 10, 2 );

			add_filter( 'learn-press/gradebook/export-item-status', array( $this, 'export_assignment_status' ), 10, 3 );
		}

		/**
		 * @param $item
		 */
		public function profile_item_result( $item ) {
			if ( $data = $this->_get_assignment_data( $item ) ) { ?>
				<td class="course-item-data <?php echo $data['grade']; ?>"><?php echo $data['result']; ?></td>
				<?php
			}
		}

		/**
		 * @param $result
		 * @param $item
		 *
		 * @return mixed
		 */
		public function export_assignment_result( $result, $item ) {
			if ( $data = $this->_get_assignment_data( $item ) ) {
				return $data['result'];
			}

			return $result;
		}

		/**
		 * @param $item
		 *
		 * @return array
		 */
		private function _get_assignment_data( $item ) {
			/**
			 * @var $item LP_User_Item
			 */
			if ( $item->get_data( 'item_type' ) == 'lp_assignment' ) {
				$status    = $item->get_data( 'status' );
				$evaluated = $status == 'evaluated';
				$completed = $status == 'completed';

				return array(
					'grade'  => $evaluated ? 'passed' : 'failed',
					'result' => $evaluated ? '100%' : ( $completed ? '0%' : '-' ),
				);
			}

			return array();
		}

		/**
		 * @param $types
		 *
		 * @return array
		 */
		public function add_query_type( $types ) {
			if ( is_array( $types ) ) {
				$types[] = 'lp_assignment';
			}

			return $types;
		}

		/**
		 * @param $data_item
		 */
		public function passing_grade_details_view( $data_item ) {
			if ( $data_item->post_type == 'lp_assignment' ) {
				$assignment = learn_press_get_assignment( $data_item->ID );
				printf( __( '%1$s/%2$s', 'learnpress-assignments' ), $assignment->get_data( 'passing_grade' ), $assignment->get_data( 'mark' ) );
			}
		}

		/**
		 * @param $data_item
		 */
		public function result_details_view( $data_item ) {
			$item_type = $data_item->post_type;
			if ( $item_type == 'lp_assignment' ) {
				$assignment = learn_press_get_assignment( $data_item->ID );
				if ( in_array( $data_item->status, array( 'completed', 'evaluated' ) ) ) {
					$user_item_id = $data_item->user_item_id;
					?>
					<span class="assignment-result"><?php echo __( $data_item->status, 'learnpress-assignments' ); ?></span>
					<?php
					if ( $data_item->status == 'evaluated' ) {
						$mark = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_mark', true );
						?>
						<span class="assignment-result-details"><?php printf( __( ' - %1$s/%2$s', 'learnpress-assignments' ), $mark, $assignment->get_data( 'mark' ) ); ?></span>
						<?php
					}
				} else {
					echo '-';
				}
			}
		}

		public function export_assignment_status( $label_status, $item_type, $item_status ) {
			if ( $item_type === LP_ASSIGNMENT_CPT ) {
				$statuses_assignment = array(
					'evaluated' => __( 'Evaluated', 'learnpress-assignments' ),
				);
				$label_status        = ! empty( $statuses_assignment[ $item_status ] ) ? $statuses_assignment[ $item_status ] : $label_status;
			}

			return $label_status;
		}
	}
}

new LP_Assignment_Gradebook();
