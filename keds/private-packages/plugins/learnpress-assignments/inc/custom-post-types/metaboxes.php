<?php

use LearnPress\Helpers\Template;
use LearnPressAssignment\Models\AssignmentPostModel;

class LP_Meta_Box_Assignment_Attachments extends LP_Meta_Box {

	private static $_instance = null;

	public $post_type = LP_ASSIGNMENT_CPT;

	public function add_meta_box() {
		add_meta_box(
			'assignment_attachments',
			esc_html__( 'Documentations', 'learnpress-assignments' ),
			array(
				$this,
				'output',
			),
			$this->post_type,
			'normal',
			'high'
		);
	}

	public function metabox( $post_id = 0 ) {
		return apply_filters(
			'lp/metabox/assignment-attachments/lists',
			array(
				'_lp_attachments' => new LP_Meta_Box_File_Field(
					esc_html__( 'Attachments', 'learnpress-assignments' ),
					esc_html__( 'Attach the related documentations here.', 'learnpress-assignments' ),
					'',
					array(
						'multil' => true,
					)
				),
			)
		);
	}

	public function output( $post ) {
		parent::output( $post );

		$assignmentModel = AssignmentPostModel::find( $post->ID, true );
		?>

		<div class="lp-meta-box lp-meta-box--assignment-attachments">
			<div class="lp-meta-box__inner">
				<?php
				do_action( 'learnpress/assignment-attachments/before' );
				// Check if add_filter to old version.
				$is_old = false;

				foreach ( $this->metabox( $post->ID ) as $key => $object ) {
					if ( is_a( $object, 'LP_Meta_Box_Field' ) ) {
						$object->id = $key;
						learn_press_echo_vuejs_write_on_php( $object->output( $post->ID ) );
					} elseif ( is_array( $object ) ) {
						$is_old = true;
					}
				}

				if ( $is_old ) {
					lp_meta_box_output( $this->metabox( $post->ID ) );
				}

				ob_start();
				wp_editor( $assignmentModel->get_introduction_task(), '_lp_introduction' );
				$intro_editor  = ob_get_clean();
				$section_intro = [
					'start' => '<div class="form-field">',
					'label' => sprintf( '<label>%s</label>', __( 'Introduction', 'learnpress-assignments' ) ),
					'intro' => sprintf(
						'<div class="lp-assignment-introduction">%s%s</div>',
						$intro_editor,
						sprintf(
							'<span class="description">%s</span>',
							__( 'Short description displayed before the student starts the assignment.', 'learnpress-assignments' )
						)
					),
					'end'   => '</div>',
				];
				echo Template::combine_components( $section_intro );

				do_action( 'learnpress/assignment-attachments/after' );
				?>
			</div>
		</div>

		<?php
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

class LP_Meta_Box_Assignment extends LP_Meta_Box {

	private static $_instance = null;

	public $post_type = LP_ASSIGNMENT_CPT;

	public function add_meta_box() {
		add_meta_box(
			'assignment_settings',
			esc_html__( 'General Settings', 'learnpress-assignments' ),
			array(
				$this,
				'output',
			),
			$this->post_type,
			'normal',
			'high'
		);
	}

	public function metabox( $post_id = 0 ) {
		return apply_filters(
			'lp/metabox/assignment/lists',
			array(
				'_lp_duration'          => new LP_Meta_Box_Duration_Field(
					esc_html__( 'Duration', 'learnpress-assignments' ),
					esc_html__( 'Set 0 for unlimited time.', 'learnpress-assignments' ),
					'3',
					array(
						'default_time'      => 'day',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				),
				'_lp_mark'              => new LP_Meta_Box_Text_Field(
					esc_html__( 'Mark', 'learnpress-assignments' ),
					esc_html__( 'Maximum mark can the students receive.', 'learnpress-assignments' ),
					10,
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.1',
						),
						'style'             => 'width: 70px;',
					)
				),
				'_lp_passing_grade'     => new LP_Meta_Box_Text_Field(
					esc_html__( 'Passing Grade', 'learnpress-assignments' ),
					esc_html__( 'Requires user reached this point to pass the assignment.', 'learnpress-assignments' ),
					8,
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.1',
						),
						'style'             => 'width: 70px;',
					)
				),
				'_lp_retake_count'      => new LP_Meta_Box_Text_Field(
					esc_html__( 'Re-take', 'learnpress-assignments' ),
					esc_html__( 'How many times the user can re-take this assignment. Set to 0 to disable', 'learnpress-assignments' ),
					0,
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
						'style'             => 'width: 70px;',
					)
				),
				'_lp_upload_files'      => new LP_Meta_Box_Text_Field(
					esc_html__( 'Upload files', 'learnpress-assignments' ),
					esc_html__( 'Number files the user can upload with this assignment. Set to 0 to disable', 'learnpress-assignments' ),
					1,
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
						'style'             => 'width: 70px;',
					)
				),
				'_lp_file_extension'    => new LP_Meta_Box_Text_Field(
					esc_html__( 'File Extensions', 'learnpress-assignments' ),
					esc_html__( 'Which types of file will be allowed uploading?', 'learnpress-assignments' ),
					'jpg,txt,zip,pdf,doc,docx,ppt'
				),
				'_lp_upload_file_limit' => new LP_Meta_Box_Text_Field(
					esc_html__( 'Size Limit', 'learnpress-assignments' ),
					esc_html__( 'Set Maximum Attachment size for upload ( set less than 128 MB)', 'learnpress-assignments' ),
					2,
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',  //min upload file size
							'step' => '1',
							'max'  => '128', //max upload file size
						),
						'style'             => 'width: 70px;',
					)
				),
			)
		);
	}

	public function output( $post ) {
		parent::output( $post );
		?>

		<div class="lp-meta-box lp-meta-box--assignments">
			<div class="lp-meta-box__inner">
				<?php
				do_action( 'learnpress/assignment-settings/before' );
				// Check if add_filter to old version.
				$is_old = false;

				foreach ( $this->metabox( $post->ID ) as $key => $object ) {
					if ( is_a( $object, 'LP_Meta_Box_Field' ) ) {
						$object->id = $key;
						learn_press_echo_vuejs_write_on_php( $object->output( $post->ID ) );
					} elseif ( is_array( $object ) ) {
						$is_old = true;
					}
				}

				if ( $is_old ) {
					lp_meta_box_output( $this->metabox( $post->ID ) );
				}

				do_action( 'learnpress/assignment-settings/after' );
				?>
			</div>
		</div>

		<?php
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

LP_Meta_Box_Assignment_Attachments::instance();
LP_Meta_Box_Assignment::instance();
