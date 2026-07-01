<?php
if ( class_exists( 'LP_REST_Jwt_Posts_Controller' ) ) {
	class LP_Assignment_Evaluate_V1_Controller extends LP_REST_Jwt_Posts_Controller {
		protected $namespace = 'learnpress/v1';

		protected $rest_base = 'assignment/evaluated';

		protected $post_type = LP_ASSIGNMENT_CPT;

		public function register_routes() {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<assignment_id>[\d]+)',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_items' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<assignment_id>[\d]+)/(?P<user_id>[\d-]+)',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_item_by_student' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_evaluate_item' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/delete/(?P<assignment_id>[\d]+)/(?P<user_id>[\d-]+)',
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'delete_evaluate_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/reset/(?P<assignment_id>[\d]+)/(?P<user_id>[\d-]+)',
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'reset_evaluate_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/sendmail/(?P<assignment_id>[\d]+)/(?P<user_id>[\d-]+)',
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'sendmail_evaluate_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				)
			);
		}

		public function get_items_permissions_check( $request ) {
			$user_id = get_current_user_id();

			// If admin or post author.
			if ( current_user_can( 'manage_options' ) || get_post_field( 'post_author', $request['assignment_id'] ) === $user_id ) {
				return true;
			}

			if ( apply_filters( 'learnpress/assignment/api/evaluate/permission', false, $request['assignment_id'], $user_id ) ) {
				return true;
			}

			return false;
		}

		public function sendmail_evaluate_item( $request ) {
			$assignment_id = $request['assignment_id'];
			$user_id       = $request['user_id'];

			try {
				$email = new LP_Email_Assignment_Evaluated_User();

				$result = $email->handle( array( $user_id, $assignment_id ) );

				if ( ! $result ) {
					throw new Exception( __( 'Send mail failed', 'learnpress-assignments' ) );
				}

				return rest_ensure_response(
					array(
						'success' => true,
						'message' => esc_html__(
							'Send mail to student successfully',
							'learnpress-assignments'
						),
					)
				);
			} catch ( \Throwable $th ) {
				return new WP_Error( 'error', $th->getMessage() );
			}
		}

		public function reset_evaluate_item( $request ) {
			$assignment_id = $request['assignment_id'];
			$user_id       = $request['user_id'];

			try {
				if ( empty( $assignment_id ) ) {
					throw new Exception( __( 'Assignment ID is required', 'learnpress-assignments' ) );
				}

				if ( empty( $user_id ) ) {
					throw new Exception( __( 'User ID is required', 'learnpress-assignments' ) );
				}

				$lp_user_item    = LP_User_Items_DB::getInstance();
				$filter          = new LP_User_Items_Filter();
				$filter->user_id = $user_id;
				$filter->item_id = $assignment_id;

				$item_object  = $lp_user_item->get_user_course_item( $filter, true );
				$user_item_id = $item_object->user_item_id;

				if ( ! $user_item_id ) {
					return;
				}

				learn_press_delete_user_item_meta( $user_item_id, 'grade' );
				learn_press_delete_user_item_meta( $user_item_id, '_lp_assignment_mark' );
				learn_press_delete_user_item_meta( $user_item_id, '_lp_assignment_instructor_note' );
				learn_press_delete_user_item_meta( $user_item_id, '_lp_assignment_evaluate_upload' );
				learn_press_update_user_item_meta( $user_item_id, '_lp_assignment_evaluate_author', 0 );

				$user_curd = new LP_User_CURD();
				$result    = $user_curd->update_user_item_status( $user_item_id, 'completed' );

				if ( ! $result ) {
					throw new Exception( __( 'Reset evaluate failed', 'learnpress-assignments' ) );
				}

				$response = array(
					'success' => true,
					'message' => __( 'Reset evaluate successfully', 'learnpress-assignments' ),
				);

				return rest_ensure_response( $response );
			} catch ( \Throwable $th ) {
				return new WP_Error( 'error', $th->getMessage() );
			}
		}

		public function delete_evaluate_item( $request ) {
			$assignment_id = $request['assignment_id'];
			$user_id       = $request['user_id'];

			try {
				if ( empty( $assignment_id ) ) {
					throw new Exception( __( 'Assignment ID is required', 'learnpress-assignments' ) );
				}

				if ( empty( $user_id ) ) {
					throw new Exception( __( 'User ID is required', 'learnpress-assignments' ) );
				}

				$user_curd = new LP_User_CURD();

				$result = $user_curd->delete_user_item(
					array(
						'item_id' => $assignment_id,
						'user_id' => $user_id,
					)
				);

				if ( ! $result ) {
					throw new Exception( __( 'Delete evaluate failed', 'learnpress-assignments' ) );
				}

				return rest_ensure_response(
					array(
						'success' => true,
						'message' => __( 'Delete evaluate successfully', 'learnpress-assignments' ),
					)
				);
			} catch ( \Throwable $th ) {
				return new WP_Error( 'error', $th->getMessage() );
			}
		}

		public function update_evaluate_item( $request ) {
			$assignment_id = $request['assignment_id'];
			$user_id       = $request['user_id'];
			$type          = $request['type'];
			$mark          = ! empty( $request['mark'] ) ? absint( $request['mark'] ) : '';
			$note          = ! empty( $request['note'] ) ? sanitize_text_field( $request['note'] ) : '';
			$upload        = ! empty( $request['upload'] ) ? array_map( 'absint', $request['upload'] ) : array();

			try {
				if ( empty( $user_id ) ) {
					throw new Exception( __( 'Invalid student', 'learnpress-assignments' ) );
				}

				$assignment = LP_Assignment::get_assignment( $assignment_id );

				if ( ! $assignment ) {
					throw new Exception( __( 'Invalid assignment', 'learnpress-assignments' ) );
				}

				$lp_user_item    = LP_User_Items_DB::getInstance();
				$filter          = new LP_User_Items_Filter();
				$filter->user_id = $user_id;
				$filter->item_id = $assignment_id;

				$item_object  = $lp_user_item->get_user_course_item( $filter, true );
				$user_item_id = $item_object->user_item_id;

				$assigment_db = LP_Assigment_DB::getInstance();

				if ( $type !== 're-evaluate' ) {
					learn_press_update_user_item_meta( $user_item_id, '_lp_assignment_mark', $mark );
					$assigment_db->update_extra_value( $user_item_id, LP_Assigment_DB::$instructor_note_key, $note );

					learn_press_update_user_item_meta( $user_item_id, '_lp_assignment_evaluate_upload', $upload );
					learn_press_update_user_item_meta( $user_item_id, '_lp_assignment_evaluate_author', $user_id );
				}

				$course    = learn_press_get_item_courses( $assignment_id );
				$lp_course = learn_press_get_course( $course[0]->ID );
				$user      = learn_press_get_user( $user_id );
				//$course_data = $user->get_course_data( $lp_course->get_id() );
				$user_curd = new LP_User_CURD();

				switch ( $type ) {
					case 'evaluate':
						learn_press_update_user_item_field(
							array(
								'graduation' => ( $mark >= $assignment->get_data( 'passing_grade' ) ? 'passed' : 'failed' ),
								'user_id'    => $user_id,
							),
							array( 'user_item_id' => $user_item_id )
						);

						$user_curd->update_user_item_status( $user_item_id, 'evaluated' );

						//$course_data->calculate_course_results();

						do_action( 'learn-press/assignment/instructor-evaluated', $user_id, $assignment_id );

						break;
					case 're-evaluate':
						$user_curd->update_user_item_status( $user_item_id, 'completed' );

						do_action( 'learn-press/instructor-re-evaluated-assignment', $assignment_id, $user_id );
						break;
					default:
						break;
				}

				do_action( 'learn-press/save-evaluate-form', $type );

				if ( $type === 'save' ) {
					$message = __( 'Save successfully', 'learnpress-assignments' );
				} elseif ( $type === 're-evaluate' ) {
					$message = __( 'Re-Evaluate successfully', 'learnpress-assignments' );
				} else {
					$message = __( 'Evaluate successfully', 'learnpress-assignments' );
				}

				$response = array(
					'success' => true,
					'message' => $message,
				);

				return rest_ensure_response( $response );
			} catch ( \Throwable $th ) {
				return new WP_Error( 'lp_assignment_evaluate', $th->getMessage() );
			}
		}

		public function get_item_by_student( $request ) {
			$assignment_id = $request['assignment_id'];
			$user_id       = $request['user_id'];

			try {
				if ( empty( $user_id ) ) {
					throw new Exception( __( 'Invalid student', 'learnpress-assignments' ) );
				}

				$assignment = LP_Assignment::get_assignment( $assignment_id );

				if ( empty( $assignment ) ) {
					throw new Exception( __( 'Invalid assignment', 'learnpress-assignments' ) );
				}

				$assignment_db = LP_Assigment_DB::getInstance();
				$user          = learn_press_get_user( $user_id );
				$course        = learn_press_get_item_courses( $assignment_id );
				$lp_course     = learn_press_get_course( $course[0]->ID );

				$lp_user_item    = LP_User_Items_DB::getInstance();
				$filter          = new LP_User_Items_Filter();
				$filter->user_id = $user_id;
				$filter->item_id = $assignment_id;

				$item_object  = $lp_user_item->get_user_course_item( $filter, true );
				$user_item_id = $item_object->user_item_id ?? 0;

				if ( empty( $user_item_id ) ) {
					throw new Exception( __( 'Invalid assignment', 'learnpress-assignments' ) );
				}

				$last_answer    = $assignment_db->get_extra_value( $user_item_id, $assignment_db::$answer_note_key );
				$uploaded_files = learn_press_assignment_get_uploaded_files( $user_item_id );
				$evaluated      = $user->has_item_status( array( 'evaluated' ), $assignment_id, $lp_course->get_id() );

				$data = array(
					'evaluated' => $evaluated,
					'student'   => array(
						'answer'      => $last_answer,
						'attachments' => array(),
					),
				);

				if ( ! empty( $uploaded_files ) ) {
					foreach ( $uploaded_files as $attachment ) {
						$data['student']['attachments'][] = array(
							'name' => $attachment->filename,
							'url'  => esc_url_raw( get_site_url() . '/' . $attachment->file ),
						);
					}
				}

				$mark            = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_mark', true );
				$instructor_note = $assignment_db->get_extra_value( $user_item_id, $assignment_db::$instructor_note_key );

				if ( empty( $instructor_note ) ) { // get value old from column meta_value
					$instructor_note = learn_press_get_user_item_meta( $user_item_id, $assignment_db::$instructor_note_key, true );
				}

				$upload = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_evaluate_upload', true );

				$data['instructor'] = array(
					'mark'          => array(
						'value' => $mark ? $mark : 0,
						'min'   => 0,
						'step'  => 1,
						'max'   => (float) $assignment->get_data( 'mark' ),
					),
					'passing_grade' => (float) $assignment->get_passing_grade(),
					'note'          => $instructor_note ? $instructor_note : '',
					'upload'        => $upload ? $upload : array(),
				);

				return rest_ensure_response( $data );
			} catch ( \Throwable $th ) {
				return new WP_Error( 'lp_assignment_evaluate', $th->getMessage() );
			}
		}

		public function get_items( $request ) {
			$query_args     = $this->prepare_objects_query( $request );
			$data_evaluates = $this->get_data_evaluate( $query_args );
			$total_count    = $this->get_student_lists( $query_args, true );

			$data = array();
			if ( ! empty( $data_evaluates ) ) {
				foreach ( (array) $data_evaluates as $evaluate ) {
					$note   = $this->prepare_item_for_response( $evaluate, $request );
					$note   = $this->prepare_response_for_collection( $note );
					$data[] = $note;
				}
			}

			$response = rest_ensure_response( $data );
			$response->header( 'X-WP-Total', absint( $total_count ) );
			$response->header( 'X-WP-TotalPages', (int) ceil( $total_count / (int) $query_args['per_page'] ) );

			return $response;
		}

		public function prepare_item_for_response( $data, $request ) {
			$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

			$data = $this->add_additional_fields_to_object( $data, $request );
			$data = $this->filter_response_by_context( $data, $context );

			$response = rest_ensure_response( $data );

			return $response;
		}

		protected function get_data_evaluate( $query_args ) {
			$assignment_id = (int) $query_args['assignment_id'];
			$students      = $this->get_student_lists( $query_args, false );
			$course        = learn_press_get_item_courses( $assignment_id );

			if ( empty( $course[0]->ID ) ) {
				return array();
			}

			$lp_course = learn_press_get_course( $course[0]->ID );

			$output = array();

			foreach ( $students as $id ) {
				$user         = learn_press_get_user( $id );
				$user_item_id = 0;
				$course_data  = $user->get_course_data( $lp_course->get_id() );

				if ( $course_data ) {
					$assignment_item = $course_data->get_item( $assignment_id );

					if ( $assignment_item ) {
						$user_item_id = $assignment_item->get_user_item_id();
					}
				}

				$evaluated     = $user->has_item_status( array( 'evaluated' ), $assignment_id, $lp_course->get_id() );
				$instructor    = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_evaluate_author', true );
				$mark          = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_mark', true );
				$passing_grade = get_post_meta( $assignment_id, '_lp_passing_grade', true );

				$instructor_user = get_user_by( 'id', $instructor );
				$instructor_name = $instructor_user ? $instructor_user->user_login : '';

				if ( ! $evaluated ) {
					$result = array();
				} else {
					$result = (float) $mark >= (float) $passing_grade ? array(
						'id'    => 'passed',
						'label' => __( 'Passed', 'learnpress-assignments' ),
					) : array(
						'id'    => 'failed',
						'label' => __( 'Failed', 'learnpress-assignments' ),
					);
				}

				$output[] = array(
					'id'         => $id,
					'name'       => $user->get_data( 'user_login' ),
					'email'      => $user->get_data( 'email' ),
					'status'     => $evaluated ? esc_html__( 'Evaluated', 'learnpress-assignments' ) : esc_html__( 'Not evaluate', 'learnpress-assignments' ),
					'instructor' => $instructor_name,
					'mark'       => $mark ? (float) $mark : '',
					'result'     => $result,
					'evaluated'  => $evaluated,
				);
			}

			return $output;
		}

		protected function get_student_lists( $query_args, $total ) {
			global $wpdb;

			$paged         = (int) $query_args['page'];
			$assignment_id = (int) $query_args['assignment_id'];
			$per_page      = (int) $query_args['per_page'];
			$search        = $query_args['search'];
			$status        = $query_args['status'];
			$result        = $query_args['result'];

			$offset = ( $paged - 1 ) * $per_page;

			if ( $total ) {
				$select = 'COUNT( DISTINCT student.ID )';
			} else {
				$select = 'DISTINCT student.ID';
			}

			$sql = "SELECT {$select} FROM {$wpdb->users} AS student INNER JOIN {$wpdb->prefix}learnpress_user_items AS user_item ON user_item.user_id = student.ID";

			$sql .= $wpdb->prepare( ' WHERE user_item.item_id=%d AND user_item.item_type=%s', $assignment_id, 'lp_assignment' );

			if ( ! empty( $search ) ) {
				$s    = '%' . $wpdb->esc_like( $search ) . '%';
				$sql .= $wpdb->prepare( ' AND (student.user_login LIKE %s OR student.user_email LIKE %s)', $s, $s );
			}

			if ( ! empty( $status ) ) {
				$sql .= $wpdb->prepare( ' AND user_item.status=%s', $status );
			} else {
				$sql .= $wpdb->prepare( ' AND user_item.status IN (%s, %s)', 'completed', 'evaluated' );
			}

			if ( ! empty( $result ) ) {
				$sql .= $wpdb->prepare( ' AND user_item.graduation=%s', $result );
			}

			if ( ! $total ) {
				$sql .= $wpdb->prepare( ' LIMIT %d, %d', $offset, $per_page );
			}

			if ( ! $total ) {
				$students = $wpdb->get_col( $sql );
			} else {
				$students = $wpdb->get_var( $sql );
			}

			return $students;
		}

		protected function prepare_objects_query( $request ) {
			$args                  = array();
			$args['assignment_id'] = $request['assignment_id'];
			$args['order']         = $request['order'];
			$args['per_page']      = $request['per_page'];
			$args['page']          = $request['page'];
			$args['search']        = $request['search'];
			$args['status']        = $request['status'];
			$args['result']        = $request['result'];

			$args = apply_filters( 'learnpress/assignment/api/evaluate/prepare_objects_query', $args, $request );

			return $args;
		}

		public function get_collection_params() {
			$params             = array();
			$params['context']  = $this->get_context_param( array( 'default' => 'view' ) );
			$params['order']    = array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'learnpress-assignments' ),
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => array( 'asc', 'desc' ),
				'validate_callback' => 'rest_validate_request_arg',
			);
			$params['page']     = array(
				'description'       => __( 'Current page of the collection.', 'learnpress-assignments' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			);
			$params['per_page'] = array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'learnpress-assignments' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);
			$params['search']   = array(
				'description'       => __( 'Limit results to those matching a string.', 'learnpress-assignments' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			);
			$params['status']   = array(
				'description'       => __( 'Limit result set to items assigned a specific status.', 'learnpress-assignments' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			);
			$params['result']   = array(
				'description'       => __( 'Limit result set to items assigned a specific result.', 'learnpress-assignments' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			);

			return $params;
		}

		public function get_item_schema() {
			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'note',
				'type'       => 'object',
				'properties' => array(
					'id'         => array(
						'description' => __( 'ID' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'name'       => array(
						'description' => __( 'User login name.' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'email'      => array(
						'description' => __( 'The email address for the user.' ),
						'type'        => 'string',
						'format'      => 'email',
						'context'     => array( 'view' ),
						'required'    => true,
					),
					'status'     => array(
						'description' => __( 'Evaluate status.' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'instructor' => array(
						'description' => __( 'Instructor name.' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'mark'       => array(
						'description' => __( 'Mark.' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'result'     => array(
						'description' => __( 'Result' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'evaluated'  => array(
						'description' => __( 'Evaluated' ),
						'type'        => 'boolean',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
				),
			);

			return $this->add_additional_fields_schema( $schema );
		}
	}
}
