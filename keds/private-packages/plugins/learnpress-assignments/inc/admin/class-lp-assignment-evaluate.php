<?php
/**
 * Class LP_Assignment_Evaluate.
 *
 * @package LearnPress/Assignments/Classes
 * @version 4.0.0
 * @author Nhamdv - Code is poetry
 */

use LearnPressAssignment\Models\AssignmentPostModel;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Assignment_Evaluate' ) ) {

	/**
	 * Class LP_Assignment_Evaluate
	 */
	class LP_Assignment_Evaluate {

		/**
		 * @var array
		 */
		protected static $_instance = array();

		/**
		 * @var AssignmentPostModel false
		 */
		protected $assignment;

		/**
		 * @var null
		 */
		protected $user_item_id = null;

		/**
		 * @var null
		 */
		protected $evaluated = null;

		/**
		 * LP_Assignment_Evaluate constructor.
		 *
		 * @param $assignment AssignmentPostModel
		 * @param $user_item_id
		 * @param $evaluated
		 */
		public function __construct( AssignmentPostModel $assignment, $user_item_id, $evaluated ) {
			$this->assignment   = $assignment;
			$this->user_item_id = $user_item_id;
			$this->evaluated    = $evaluated;
		}

		/**
		 * Display.
		 */
		public function display() {
			$this->get_settings_v4();
		}

		/**
		 * @return void
		 */
		public function get_settings_v4() {
			$prefix        = '_lp_evaluate_assignment_';
			$assignment_db = LP_Assigment_DB::getInstance();

			$mark = learn_press_get_user_item_meta( $this->user_item_id, '_lp_assignment_mark', true );

			$instructor_note = $assignment_db->get_extra_value( $this->user_item_id, $assignment_db::$instructor_note_key );
			if ( empty( $instructor_note ) ) { // get value old from column meta_value
				$instructor_note = learn_press_get_user_item_meta( $this->user_item_id, $assignment_db::$instructor_note_key, true );
			}

			$upload = learn_press_get_user_item_meta( $this->user_item_id, '_lp_assignment_evaluate_upload', true );
			?>

			<div class="lp-meta-box">
				<div class="lp-meta-box__inner">
					<?php
					lp_meta_box_text_input_field(
						array(
							'id'                => $prefix . 'mark',
							'label'             => esc_html__( 'Mark', 'learnpress-assignments' ),
							'description'       => sprintf(
								'%s (%s)',
								esc_html__( 'Mark for user answer.', 'learnpress-assignments' ),
								sprintf(
									'%s %s',
									esc_html__( 'Max mark', 'learnpress-assignments' ),
									$this->assignment ? $this->assignment->get_max_mark() : ''
								)
							),
							'type'              => 'number',
							'type_input'        => 'number',
							'default'           => $mark ? $mark : 0,
							'custom_attributes' => array(
								'min'  => '0',
								'step' => '0.1',
								'max'  => $this->assignment ? $this->assignment->get_max_mark() : '',
							),
						)
					);

					// Action after student mark
					do_action( 'learn-press/assignment/evaluate/after-student-mark', $this->user_item_id );

					ob_start();
					wp_editor(
						$instructor_note,
						$prefix . 'instructor_note',
						array(
							'media_buttons' => false,
							'textarea_rows' => 10,
						)
					);
					$editor = ob_get_clean();

					printf(
						'<div class="form-field ">
							<label>%s</label>
							%s
						</div>',
						esc_html__( 'Instructor note', 'learnpress-assignments' ),
						$editor
					);

					lp_meta_box_file_input_field(
						array(
							'id'          => $prefix . 'document',
							'label'       => esc_html__( 'Document', 'learnpress-assignments' ),
							'description' => esc_html__( 'Upload files for the right answers, reference, etc.', 'learnpress-assignments' ),
							'multil'      => true,
							'default'     => $upload ? $upload : '',
						)
					);
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * @param $assignment
		 * @param $user_item_id
		 * @param $evaluated
		 *
		 * @return array|LP_Assignment_Evaluate
		 */
		public static function instance( $assignment, $user_item_id, $evaluated ) {
			if ( ! self::$_instance ) {
				self::$_instance = new self( $assignment, $user_item_id, $evaluated );
			}

			return self::$_instance;
		}
	}
}
