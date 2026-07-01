<?php
/**
 * Assignment API
 *
 * @author Nhamdv <daonham95@gmail.com>
 */

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LearnPressAssignment\Models\UserAssignmentModel;
use LearnPressAssignment\TemplateHooks\UserAssignmentTemplate;

if ( class_exists( 'LP_REST_Jwt_Posts_Controller' ) ) {
	class LP_Jwt_Assignment_V1_Controller extends LP_REST_Jwt_Posts_Controller {
		protected $namespace = 'learnpress/v1';

		protected $rest_base = 'assignments';

		protected $post_type = LP_ASSIGNMENT_CPT;

		protected $hierarchical = true;

		public function register_routes() {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
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
				'/' . $this->rest_base . '/(?P<id>[\d]+)',
				array(
					'args'   => array(
						'id' => array(
							'description' => esc_html__( 'Unique identifier for the resource.', 'learnpress-assignments' ),
							'type'        => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_item' ),
						'permission_callback' => array( $this, 'get_item_permissions_check' ),
						'args'                => array(
							'context' => $this->get_context_param(
								array(
									'default' => 'view',
								)
							),
						),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/start',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'start_assignment' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description'       => esc_html__( 'Unique identifier for the resource.', 'learnpress-assignments' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/retake',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'retake_assignment' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description'       => esc_html__( 'Unique identifier for the resource.', 'learnpress-assignments' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/submit',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'submit_assignment' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'action' => array(
							'description'       => esc_html__( 'Send or Save', 'learnpress-assignments' ),
							'type'              => 'string',
							'default'           => 'send',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'id'     => array(
							'description'       => esc_html__( 'Unique identifier for the resource.', 'learnpress-assignments' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'note'   => array(
							'description'       => esc_html__( 'User note.', 'learnpress-assignments' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'file'   => array(
							'description' => esc_html__( 'File.', 'learnpress-assignments' ),
							'type'        => 'array',
						),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/delete-submit-file',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'delete_submit_file' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'course_id'     => array(
							'description'       => esc_html__( 'Course ID', 'learnpress-assignments' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'assignment_id' => array(
							'description'       => esc_html__( 'Assignment ID.', 'learnpress-assignments' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'file_id'       => array(
							'description'       => esc_html__( 'File ID.', 'learnpress-assignments' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				)
			);
		}

		public function retake_assignment( $request ) {
			$response = array(
				'data'    => array(
					'status' => 400,
				),
				'message' => esc_html__( 'There was an error starting the assignment.', 'learnpress-assignments' ),
			);

			try {
				$id        = $request->get_param( 'id' );
				$user      = learn_press_get_current_user();
				$course_id = $this->get_course_by_item_id( $id );

				if ( ! $course_id ) {
					throw new Exception( esc_html__( 'This Assignment need assign in a course', 'learnpress-assignments' ) );
				}

				if ( ! $user ) {
					throw new Exception( esc_html__( 'Username is not available!', 'learnpress-assignments' ) );
				}

				if ( ! $user->has_item_status( array( 'completed', 'evaluated' ), $id, $course_id ) ) {
					throw new Exception( esc_html__( 'You cannot retake this assignment.', 'learnpress-assignments' ) );
				}

				$assignment = new LP_Assignment( $id );

				$retake_count = $assignment->get_retake_count();

				$user_item_id = $this->get_user_item_id( $user, $id, $course_id );

				$retaken = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_retaken', true );
				$retaken = ! empty( $retaken ) ? absint( $retaken ) : 0;

				if ( absint( $retake_count ) <= $retaken ) {
					throw new Exception( esc_html__( 'You cannot retake this assignment.', 'learnpress-assignments' ) );
				}

				$retake = learn_press_assignment_start( $user, $id, $course_id, 'retake', true );

				if ( is_wp_error( $retake ) ) {
					throw new Exception( $retake->get_error_message() );
				} else {
					$response = array(
						'data'    => array(
							'status' => 200,
						),
						'message' => esc_html__( 'Your Assignment has been started successfully.', 'learnpress-assignments' ),
					);
					do_action( 'learn-press/assignment/student-retake-assignment', $retake, $user->get_id(), $id, $course_id );
				}
			} catch ( \Throwable $th ) {
				$response['message'] = $th->getMessage();
			}

			return rest_ensure_response( $response );
		}

		public function delete_submit_file( $request ) {
			$lp_file_system = LP_WP_Filesystem::instance();

			$response = new LP_REST_Response();

			try {
				$file_id       = $request->get_param( 'file_id' );
				$course_id     = $request->get_param( 'course_id' );
				$assignment_id = $request->get_param( 'assignment_id' );
				$user          = UserModel::find( get_current_user_id(), true );

				$courseModel = CourseModel::find( $course_id, true );
				if ( ! $courseModel ) {
					throw new Exception( esc_html__( 'Course not found', 'learnpress-assignments' ) );
				}

				$assignmentModel = AssignmentPostModel::find( $assignment_id, true );
				if ( ! $assignmentModel ) {
					throw new Exception( esc_html__( 'Assignment not found', 'learnpress-assignments' ) );
				}

				if ( ! $user ) {
					throw new Exception( esc_html__( 'User is not available!', 'learnpress-assignments' ) );
				}

				$userAssignment = UserAssignmentModel::find( $user->get_id(), $course_id, $assignment_id, true );
				if ( ! $userAssignment
					|| ! in_array(
						$userAssignment->get_status(),
						[
							$userAssignment::STATUS_STARTED,
							$userAssignment::STATUS_DOING,
						]
					) ) {
					throw new Exception( esc_html__( 'You cannot delete this file.', 'learnpress-assignments' ) );
				}

				$uploaded_files = $userAssignment->get_user_files_uploaded();
				$file           = $uploaded_files[ $file_id ]->file ?? '';
				if ( empty( $file ) ) {
					throw new Exception( esc_html__( 'File is not available!', 'learnpress-assignments' ) );
				}

				unset( $uploaded_files[ $file_id ] );
				if ( $lp_file_system->lp_filesystem->delete( ABSPATH . $file ) ) {
					$userAssignment->set_meta_value_for_key( $userAssignment::META_KEY_ANSWER_UPLOAD, $uploaded_files, true );
				} else {
					throw new Exception( esc_html__( 'Remove file failed, maybe there is issue with the permission.', 'learnpress-assignments' ) );
				}

				$number_file_can_upload                 = $assignmentModel->get_file_number_allow() - count( $uploaded_files );
				$response->status                       = 'success';
				$response->message                      = esc_html__( 'Remove file successfully', 'learnpress-assignments' );
				$response->data->number_file_can_upload = (int) $number_file_can_upload;
			} catch ( Throwable $e ) {
				$response->message = $e->getMessage();
			}

			return rest_ensure_response( $response );
		}

		public function submit_assignment( $request ) {
			$response = array(
				'data'    => array(
					'status' => 400,
				),
				'message' => esc_html__( 'There was an error starting the assignment.', 'learnpress-assignments' ),
			);

			try {
				$action    = $request->get_param( 'action' );
				$id        = $request->get_param( 'id' );
				$note      = $request->get_param( 'note' );
				$files     = $request->get_file_params();
				$user      = learn_press_get_current_user();
				$course_id = $this->get_course_by_item_id( $id );

				if ( ! $course_id ) {
					throw new Exception( esc_html__( 'This Assignment need assign in a course', 'learnpress-assignments' ) );
				}

				if ( ! $user ) {
					throw new Exception( esc_html__( 'Username is not available!', 'learnpress-assignments' ) );
				}

				if ( $user->has_item_status( array( 'completed' ), $id, $course_id ) ) {
					throw new Exception( esc_html__( 'You is already send Answer, please wait the evaluated result!', 'learnpress-assignments' ) );
				}

				if ( ! $user->has_item_status( array( 'started', 'doing' ), $id, $course_id ) ) {
					throw new Exception( esc_html__( 'Please start Assignment to continue!', 'learnpress-assignments' ) );
				}

				$user_item_id = $this->get_user_item_id( $user, $id, $course_id );

				$assignment_db = LP_Assigment_DB::getInstance();

				$assignment_db->update_extra_value( $user_item_id, LP_Assigment_DB::$answer_note_key, $note );

				if ( ! empty( $files ) ) {
					$files = $files['file'];

					if ( ! empty( $files['name'][0] ) ) {
						$allow_file_amount  = get_post_meta( $id, '_lp_upload_files', true );
						$uploaded_files     = learn_press_assignment_get_uploaded_files( $user_item_id );
						$total_files        = $uploaded_files ? count( $uploaded_files ) : 0;
						$file_extension     = get_post_meta( $id, '_lp_file_extension', true );
						$file_extension     = $file_extension ? preg_replace( '#\s#', '', $file_extension ) : '*';
						$file_extension_arr = explode( ',', $file_extension );
						$max_upload_size    = get_post_meta( $id, '_lp_upload_file_limit', true );

						add_filter(
							'upload_dir',
							function ( $dir ) use ( $id, $user ) {
								$more_path = '/assignments';

								if ( isset( $id ) && $id ) {
									$more_path .= '/' . $id;
								}

								if ( isset( $user ) && $user->get_id() ) {
									$more_path .= '/' . $user->get_id();
								}

								$dir['path']   = $dir['basedir'] . $more_path;
								$dir['url']    = $dir['baseurl'] . $more_path;
								$dir['subdir'] = $more_path;

								return $dir;
							}
						);

						$file_uploaded = 0;

						foreach ( $files['name'] as $key => $value ) {
							if ( $total_files >= $allow_file_amount ) {
								throw new Exception( esc_html__( 'Your uploaded files reach the maximum amount!', 'learnpress-assignments' ) );
							}

							$file = array(
								'name'     => $files['name'][ $key ],
								'type'     => $files['type'][ $key ],
								'tmp_name' => $files['tmp_name'][ $key ],
								'error'    => $files['error'][ $key ],
								'size'     => $files['size'][ $key ],
							);

							if ( $file['size'] > $max_upload_size * 1024 * 1024 ) {
								$response['items'][ $key ]['message'] = sprintf( esc_html__( " The size of your %1\$s file is over %2\$d Mb(s).\n", 'learnpress-assignments' ), $file['name'], $max_upload_size );
								continue;
							}

							if ( ! in_array( '*', $file_extension_arr ) ) {
								$ext = wp_check_filetype( $file['name'] )['ext'];

								if ( $ext && ! in_array( strtolower( $ext ), $file_extension_arr ) ) {
									$response['items'][ $key ]['message'] = sprintf( esc_html__( '%s is not allowed!', 'learnpress-assignments' ), $file['name'] );
									continue;
								}
							}

							// Include filesystem functions to get access to wp_handle_upload().
							require_once ABSPATH . 'wp-admin/includes/file.php';

							$movefile = wp_handle_upload( $file, array( 'test_form' => false ) );

							if ( $movefile && ! isset( $movefile['error'] ) ) {
								$movefile['filename']                       = $files['name'][ $key ];
								$movefile['file']                           = str_replace( ABSPATH, '', $movefile['file'] );
								$movefile['url']                            = str_replace( get_site_url(), '', $movefile['url'] );
								$movefile['saved_time']                     = current_time( 'Y-m-d H:i:s' );
								$movefile['size']                           = $file['size'];
								$uploaded_files[ md5( $movefile['file'] ) ] = $movefile;
								++ $total_files;
								++ $file_uploaded;
							} else {
								throw new Exception( $movefile['error'] );
							}
						}

						remove_filter(
							'upload_dir',
							function ( $dir ) use ( $id, $user ) {
								$more_path = '/assignments';

								if ( isset( $id ) && $id ) {
									$more_path .= '/' . $id;
								}

								if ( isset( $user ) && $user->get_id() ) {
									$more_path .= '/' . $user->get_id();
								}

								$dir['path']   = $dir['basedir'] . $more_path;
								$dir['url']    = $dir['baseurl'] . $more_path;
								$dir['subdir'] = $more_path;

								return $dir;
							}
						);

						$assignment_db->update_extra_value( $user_item_id, $assignment_db::$answer_upload_key, json_encode( $uploaded_files ) );

						if ( $file_uploaded !== count( $files['name'] ) ) {
							throw new Exception( esc_html__( 'Some file uploaded error!', 'learnpress-assignments' ) );
						}
					}
				}

				learn_press_update_assignment_item( $id, $course_id, $user, 'doing', $user_item_id );

				$response = array(
					'data'    => array(
						'status' => 200,
					),
					'message' => esc_html__( 'The progress was saved! Your file(s) were uploaded successfully!', 'learnpress-assignments' ),
				);

				if ( $action === 'send' ) {
					$evaluate_author = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_evaluate_author', true );

					if ( ! $evaluate_author ) {
						learn_press_update_user_item_meta( $user_item_id, '_lp_assignment_evaluate_author', 0 );
					}

					learn_press_update_assignment_item( $id, $course_id, $user, 'completed', $user_item_id );

					$response['message'] = esc_html__( 'What you did was sent to the instructors, please wait the evaluated result!', 'learnpress-assignments' );

					do_action( 'learn-press/assignment/student-submitted', $user->get_id(), $id );
				}
			} catch ( \Throwable $th ) {
				$response['data']['status'] = 400;
				$response['message']        = $th->getMessage();
			}

			return rest_ensure_response( $response );
		}

		public function start_assignment( $request ) {
			$response = array(
				'data'    => array(
					'status' => 400,
				),
				'message' => esc_html__( 'There was an error starting the assignment.', 'learnpress-assignments' ),
			);

			try {
				$id        = $request->get_param( 'id' );
				$user      = learn_press_get_current_user();
				$course_id = $this->get_course_by_item_id( $id );

				if ( ! $course_id ) {
					throw new Exception( esc_html__( 'This Assignment need assign in a course', 'learnpress-assignments' ) );
				}

				if ( ! $user ) {
					throw new Exception( esc_html__( 'Username is not available!', 'learnpress-assignments' ) );
				}

				if ( ! $user->has_course_status( $course_id, array( 'enrolled' ) ) || $user->has_item_status(
					array(
						'started',
						'doing',
						'completed',
						'evaluated',
					),
					$id,
					$course_id
				) ) {
					throw new Exception( esc_html__( 'You cannot start this Assignment', 'learnpress-assignments' ) );
				}

				$start = learn_press_assignment_start( $user, $id, $course_id, 'start', true );

				if ( is_wp_error( $start ) ) {
					throw new Exception( $start->get_error_message() );
				} else {
					$response = array(
						'data'    => array(
							'status' => 200,
						),
						'message' => esc_html__( 'Your Assignment has been started successfully.', 'learnpress-assignments' ),
					);
					do_action( 'learn-press/assignment/student-start-assignment', $start, $user->get_id(), $id, $course_id );
				}
			} catch ( \Throwable $th ) {
				$response['message'] = $th->getMessage();
			}

			return rest_ensure_response( $response );
		}

		public function prepare_object_for_response( $object, $request ) {
			$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
			$data    = $this->get_assignment_data( $object, $context, $request );

			$response = rest_ensure_response( $data );

			return apply_filters( "lp_jwt_rest_prepare_{$this->post_type}_object", $response, $object, $request );
		}

		protected function get_assignment_data( $object, $context = 'view' ) {
			$request = func_num_args() >= 2 ? func_get_arg( 2 ) : new WP_REST_Request( '', '', array( 'context' => $context ) );
			$fields  = $this->get_fields_for_response( $request );

			$id   = ! empty( $object->ID ) ? $object->ID : $object->get_id();
			$post = get_post( $id );
			$data = array();

			$assigned = $this->get_assigned( $id );

			$course_id = 0;

			if ( ! empty( $assigned ) && method_exists( $object, 'set_course' ) ) {
				$course_id = $assigned['course']['id'];
				$object->set_course( $course_id );
			}

			foreach ( $fields as $field ) {
				switch ( $field ) {
					case 'id':
						$data['id'] = $id;
						break;
					case 'name':
						$data['name'] = $post->post_title;
						break;
					case 'slug':
						$data['slug'] = $post->post_name;
						break;
					case 'permalink':
						$data['permalink'] = $object->get_permalink();
						break;
					case 'date_created':
						$data['date_created'] = lp_jwt_prepare_date_response( $post->post_date_gmt, $post->post_date );
						break;
					case 'date_created_gmt':
						$data['date_created_gmt'] = lp_jwt_prepare_date_response( $post->post_date_gmt );
						break;
					case 'date_modified':
						$data['date_modified'] = lp_jwt_prepare_date_response( $post->post_modified_gmt, $post->post_modified );
						break;
					case 'date_modified_gmt':
						$data['date_modified_gmt'] = lp_jwt_prepare_date_response( $post->post_modified_gmt );
						break;
					case 'status':
						$data['status'] = $post->post_status;
						break;
					case 'content':
						$data['content'] = 'view' === $context ? apply_filters( 'the_content', $post->post_content ) : $post->post_content;
						break;
					case 'excerpt':
						$data['excerpt'] = $post->post_excerpt;
						break;
					case 'assigned':
						$data['assigned'] = $assigned;
						break;
					case 'retake_count':
						$data['retake_count'] = absint( $object->get_retake_count() );
						break;
					case 'retaken':
						$data['retaken'] = $this->get_retaken( $id, $course_id );
						break;
					case 'duration':
						$data['duration'] = $this->get_duration( $object, $course_id );
						break;
					case 'introdution':
						$data['introdution'] = $object->get_introduction();
						break;
					case 'passing_grade':
						$data['passing_grade'] = $object->get_passing_grade();
						break;
					case 'allow_file_type':
						$data['allow_file_type'] = $object->get_file_extension();
						break;
					case 'files_amount':
						$data['files_amount'] = absint( $object->get_files_amount() );
						break;
					case 'attachment':
						$data['attachment'] = $this->get_attachment( $object, $course_id );
						break;
					case 'results':
						$data['results'] = $this->get_results( $id, $course_id );
						break;
					case 'assignment_answer':
						$data['assignment_answer'] = $this->get_assignment_answer( $id, $course_id );
						break;
					case 'evaluation':
						$data['evaluation'] = $this->get_evaluation( $id, $course_id );
						break;
					case 'can_finish_course':
						$data['can_finish_course'] = $this->check_can_finish_course( $id );
						break;
				}
			}

			return $data;
		}

		public function check_can_finish_course( $id ) {
			$user = learn_press_get_current_user();

			if ( ! $user || ! $id ) {
				return falase;
			}

			$course_id = $this->get_course_by_item_id( $id );

			if ( empty( $course_id ) ) {
				return false;
			}

			$course = learn_press_get_course( $course_id );

			if ( $user && $course ) {
				$check = $user->can_show_finish_course_btn( $course );

				if ( $check['status'] === 'success' ) {
					return true;
				}

				return false;
			}

			return false;
		}

		/**
		 * @param int $id
		 * @param int $course_id
		 *
		 * @return array|void
		 */
		public function get_evaluation( int $id, int $course_id ): array {
			$output        = array();
			$user          = learn_press_get_current_user();
			$assignment_db = LP_Assigment_DB::getInstance();

			if ( empty( $user ) || empty( $course_id ) || empty( $id ) ) {
				return $output;
			}

			if ( ! $user->has_item_status( array( 'evaluated' ), $id, $course_id ) ) {
				return $output;
			}

			$user_item_id = $this->get_user_item_id( $user, $id, $course_id );

			$reference_files = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_evaluate_upload', true );
			// $instructor_note = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_instructor_note', true );
			$instructor_note = $assignment_db->get_extra_value( $user_item_id, $assignment_db::$instructor_note_key );
			if ( empty( $instructor_note ) ) { // get value old from column meta_value
				$instructor_note = learn_press_get_user_item_meta( $user_item_id, $assignment_db::$instructor_note_key, true );
			}

			$result = learn_press_assignment_get_result( $id, $user->get_id(), $course_id );

			$output['mark']       = $result['mark'] ?? '';
			$output['user_mark']  = $result['user_mark'] ?? '';
			$output['graduation'] = $result['grade'] ?? '';
			$output['result']     = $result['result'] ?? '';

			if ( ! empty( $reference_files ) ) {
				foreach ( $reference_files as $attachment ) {
					$output['reference_files'][] = array(
						'id'   => $attachment,
						'url'  => wp_get_attachment_url( $attachment ),
						'name' => wp_basename( wp_get_attachment_url( $attachment ) ),
					);
				}
			} else {
				$output['reference_files'] = array();
			}

			$output['instructor_note'] = ! empty( $instructor_note ) ? $instructor_note : '';

			return $output;
		}

		public function get_assignment_answer( $id, $course_id ) {
			$output = array();
			$user   = learn_press_get_current_user();

			if ( empty( $user ) || empty( $course_id ) || empty( $id ) ) {
				return $output;
			}

			$user_item_id = $this->get_user_item_id( $user, $id, $course_id );

			if ( ! $user->has_item_status( array( 'started', 'doing', 'completed', 'evaluated' ), $id, $course_id ) ) {
				return $output;
			}

			$assignment_db = LP_Assigment_DB::getInstance();
			$content       = $assignment_db->get_extra_value( $user_item_id, $assignment_db::$answer_note_key );

			$output['note'] = ! empty( $content ) ? $content : '';

			$uploaded_files = learn_press_assignment_get_uploaded_files( $user_item_id );

			$output['file'] = $uploaded_files;

			return $output;
		}

		public function get_results( $assignment_id, $course_id ) {
			$result = [];

			if ( empty( $course_id ) || empty( $assignment_id ) ) {
				return $result;
			}

			$user = UserModel::find( get_current_user_id(), true );
			if ( ! $user ) {
				return $result;
			}

			$assignmentPostModel = AssignmentPostModel::find( $assignment_id, true );
			if ( ! $assignmentPostModel ) {
				return $result;
			}

			$userAssignmentModel = UserAssignmentModel::find( $user->get_id(), $course_id, $assignment_id, true );
			if ( ! $userAssignmentModel ) {
				return $result;
			}

			$start_time                = $userAssignmentModel->get_start_time();
			$start_time_obj            = new LP_Datetime( $start_time );
			$end_time                  = $userAssignmentModel->get_end_time();
			$end_time_obj              = new LP_Datetime( $end_time );
			$expire_time_obj           = $userAssignmentModel->get_expiration_time();
			$output['status']          = $userAssignmentModel->get_status();
			$output['start_time']      = $start_time_obj->format( LP_Datetime::I18N_FORMAT_HAS_TIME );
			$output['expiration_time'] = $expire_time_obj->format( LP_Datetime::I18N_FORMAT_HAS_TIME );
			$output['end_time']        = $end_time_obj->format( LP_Datetime::I18N_FORMAT_HAS_TIME );

			return $output;
		}

		public function get_attachment( $object, $course_id ) {
			$result    = [];
			$userModel = UserModel::find( get_current_user_id(), true );
			if ( ! $userModel ) {
				return $result;
			}

			$id                  = ! empty( $object->ID ) ? $object->ID : $object->get_id();
			$assignmentPostModel = AssignmentPostModel::find( $id, true );
			if ( ! $assignmentPostModel ) {
				return $result;
			}

			$userAssignmentModel = UserAssignmentModel::find( $userModel->get_id(), $course_id, $id, true );
			if ( ! $userAssignmentModel ||
				! in_array(
					$userAssignmentModel->get_status(),
					[
						$userAssignmentModel::STATUS_STARTED,
						$userAssignmentModel::STATUS_DOING,
						$userAssignmentModel::STATUS_COMPLETED,
						$userAssignmentModel::STATUS_EVALUATED,
					]
				) ) {
				return $result;
			}

			$attachment_ids = $assignmentPostModel->get_attachments_assignment();
			if ( ! empty( $attachment_ids ) ) {
				foreach ( $attachment_ids as $att_id ) {
					$file      = get_attached_file( $att_id );
					$file_name = esc_html( pathinfo( $file, PATHINFO_FILENAME ) );
					$file_info = wp_check_filetype( $file );
					if ( ! $file ) {
						continue;
					}

					$link_file = wp_get_attachment_url( $att_id );
					$result[]  = array(
						'id'   => $att_id,
						'url'  => esc_url( $link_file ),
						'name' => sprintf( '%s.%s', $file_name, $file_info['ext'] ),
					);
				}
			}

			return $result;
		}

		public function get_duration( $item, $course_id ) {
			$output = array();

			$format = array(
				'day'    => esc_html__( '%s days', 'learnpress-assignments' ),
				'hour'   => esc_html__( '%s hours', 'learnpress-assignments' ),
				'minute' => esc_html__( '%s mins', 'learnpress-assignments' ),
				'second' => esc_html__( '%s secs', 'learnpress-assignments' ),
			);

			$output['format'] = $item->get_duration() ? $item->get_duration()->to_timer( $format, true ) : '';
			$output['time']   = $item->get_duration() ? $item->get_duration()->get() : '';

			$assignment_id = ! empty( $item->ID ) ? $item->ID : $item->get_id();

			$remaining_time = false;

			$userModel = UserModel::find( get_current_user_id(), true );
			if ( $userModel ) {
				$userAssignmentModel = UserAssignmentModel::find( $userModel->get_id(), $course_id, $assignment_id, true );
				if ( $userAssignmentModel ) {
					$remaining_time = $userAssignmentModel->get_time_remaining();
				}
			}

			if ( ! $remaining_time ) {
				$remaining_time = $output['time'];
			}

			$output['time_remaining'] = $remaining_time;

			return $output;
		}

		public function get_retaken( $id, $course_id ) {
			$user = learn_press_get_current_user();

			if ( empty( $user ) || empty( $course_id ) || empty( $id ) ) {
				return 0;
			}

			$user_item_id = $this->get_user_item_id( $user, $id, $course_id );

			$redo_time = learn_press_get_user_item_meta( $user_item_id, '_lp_assignment_retaken', true );

			return $redo_time ? absint( $redo_time ) : 0;
		}

		public function get_user_item_id( $user, $id, $course_id ) {
			$user_item_id = 0;
			$course_data  = $user->get_course_data( $course_id );
			if ( $course_data ) {
				$user_item    = $course_data->get_item( $id );
				$user_item_id = $user_item ? $user_item->get_user_item_id() : 0;
			}

			return $user_item_id;
		}

		protected function get_object( $assignment = 0 ) {
			global $post;

			if ( false === $assignment && isset( $post, $post->ID ) && LP_ASSIGNMENT_CPT === get_post_type( $post->ID ) ) {
				$id = absint( $post->ID );
			} elseif ( is_numeric( $assignment ) ) {
				$id = $assignment;
			} elseif ( $assignment instanceof LP_Assignment ) {
				$id = $assignment->get_id();
			} elseif ( ! empty( $assignment->ID ) ) {
				$id = $assignment->ID;
			}

			return new LP_Assignment( $id );
		}

		/**
		 * Checks if a course can be read.
		 *
		 * Correctly handles courses with the inherit status.
		 *
		 * @return bool Whether the post can be read.
		 * *@author Nhamdv
		 *
		 */
		public function check_read_permission( $post_id ) {
			if ( empty( absint( $post_id ) ) ) {
				return false;
			}

			$post = get_post( $post_id );

			if ( ! $post ) {
				return false;
			}

			if ( lp_rest_check_post_permissions( $this->post_type, 'read', $post_id ) ) {
				return true;
			}

			$post_status_obj = get_post_status_object( $post->post_status );
			if ( ! $post_status_obj || ! $post_status_obj->public ) {
				return false;
			}

			$user_id = get_current_user_id();

			if ( ! $user_id ) {
				return false;
			}

			$user = learn_press_get_user( $user_id );

			// Get course ID by lesson ID assigned.
			$course_id = $this->get_course_by_item_id( $post_id );

			if ( empty( $course_id ) ) {
				return false;
			}

			$can_view_content_course = $user->can_view_content_course( $course_id );

			$can_view_item = $user->can_view_item( $post_id, $can_view_content_course );

			if ( ! $can_view_item->flag ) {
				return false;
			}

			// Can we read the parent if we're inheriting?
			if ( 'inherit' === $post->post_status && $post->post_parent > 0 ) {
				$parent = get_post( $post->post_parent );

				if ( $parent ) {
					return $this->check_read_permission( $parent );
				}
			}

			return true;
		}

		public function get_assigned( $id ) {
			$courses = learn_press_get_item_courses( $id );

			$output = array();

			if ( $courses ) {
				foreach ( $courses as $course ) {
					$output['course'] = array(
						'id'      => $course->ID,
						'title'   => $course->post_title,
						'slug'    => $course->post_name,
						'content' => $course->post_content,
						'author'  => $course->post_author,
					);
				}
			}

			return $output;
		}

		/**
		 * Get course ID by assignment ID assigned.
		 *
		 * @param [type] $item_id
		 *
		 * @return void
		 */
		protected function get_course_by_item_id( $item_id ) {
			static $output;

			global $wpdb;

			if ( empty( $item_id ) ) {
				return false;
			}

			if ( ! isset( $output ) ) {
				$output = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT c.ID FROM {$wpdb->posts} c
					INNER JOIN {$wpdb->learnpress_sections} s ON c.ID = s.section_course_id
					INNER JOIN {$wpdb->learnpress_section_items} si ON si.section_id = s.section_id
					WHERE si.item_id = %d ORDER BY si.section_id DESC LIMIT 1
					",
						$item_id
					)
				);
			}

			if ( $output ) {
				return absint( $output );
			}

			return false;
		}

		public function get_item_schema() {
			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => $this->post_type,
				'type'       => 'object',
				'properties' => array(
					'id'                => array(
						'description' => __( 'Unique identifier for the resource.', 'learnpress-assignments' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'name'              => array(
						'description' => __( 'Assignment name.', 'learnpress-assignments' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'slug'              => array(
						'description' => __( 'Assignment slug.', 'learnpress-assignments' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'permalink'         => array(
						'description' => __( 'Assignment URL.', 'learnpress-assignments' ),
						'type'        => 'string',
						'format'      => 'uri',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_created'      => array(
						'description' => __( "The date the Course was created, in the site's timezone.", 'learnpress-assignments' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_created_gmt'  => array(
						'description' => __( 'The date the Course was created, as GMT.', 'learnpress-assignments' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_modified'     => array(
						'description' => __( "The date the Course was last modified, in the site's timezone.", 'learnpress-assignments' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'date_modified_gmt' => array(
						'description' => __( 'The date the Course was last modified, as GMT.', 'learnpress-assignments' ),
						'type'        => 'date-time',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'status'            => array(
						'description' => __( 'Assignment status (post status).', 'learnpress-assignments' ),
						'type'        => 'string',
						'default'     => 'publish',
						'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
						'context'     => array( 'view', 'edit' ),
					),
					'content'           => array(
						'description' => __( 'Content Assignment.', 'learnpress-assignments' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'excerpt'           => array(
						'description' => __( 'Retrieves the Assignment excerpt..', 'learnpress-assignments' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'assigned'          => array(
						'description' => __( 'Assigned.', 'learnpress-assignments' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'items'       => array(
							'id'      => array(
								'description' => __( 'Item ID.', 'learnpress-assignments' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'title'   => array(
								'description' => __( 'Title.', 'learnpress-assignments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'slug'    => array(
								'description' => __( 'Item slug.', 'learnpress-assignments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'content' => array(
								'description' => __( 'Item Content.', 'learnpress-assignments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'author'  => array(
								'description' => __( 'Item Author.', 'learnpress-assignments' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
					'retake_count'      => array(
						'description' => __( 'Retake count.', 'learnpress-assignments' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
					),
					'retaken'           => array(
						'description' => __( 'Retaken.', 'learnpress-assignments' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
					),
					'duration'          => array(
						'description' => __( 'Duration.', 'learnpress-assignments' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'items'       => array(
							'format' => array(
								'description' => __( 'Format.', 'learnpress-assignments' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'time'   => array(
								'description' => __( 'Time.', 'learnpress-assignments' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
					'introdution'       => array(
						'description' => __( 'Introdution.', 'learnpress-assignments' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'passing_grade'     => array(
						'description' => __( 'Passing grade.', 'learnpress-assignments' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'allow_file_type'   => array(
						'description' => __( 'Allow file type.', 'learnpress-assignments' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'files_amount'      => array(
						'description' => __( 'File amount can upload files.', 'learnpress-assignments' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
					),
					'attachment'        => array(
						'description' => __( 'Attachment.', 'learnpress-assignments' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
					),
					'results'           => array(
						'description' => __( 'List of course user data.', 'learnpress-assignments' ),
						'type'        => 'array',
						'context'     => array( 'view' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'graduation'      => array(
									'description' => __( 'Graduation', 'learnpress-assignments' ),
									'type'        => 'string',
									'context'     => array( 'view' ),
								),
								'status'          => array(
									'description' => __( 'Status', 'learnpress-assignments' ),
									'type'        => 'string',
									'context'     => array( 'view' ),
									'readonly'    => true,
								),
								'start_time'      => array(
									'description' => __( 'Start time', 'learnpress-assignments' ),
									'type'        => 'string',
									'context'     => array( 'view' ),
									'readonly'    => true,
								),
								'end_time'        => array(
									'description' => __( 'End time', 'learnpress-assignments' ),
									'type'        => 'string',
									'context'     => array( 'view' ),
									'readonly'    => true,
								),
								'expiration_time' => array(
									'description' => __( 'Expiration time', 'learnpress-assignments' ),
									'type'        => 'string',
									'context'     => array( 'view' ),
									'readonly'    => true,
								),
							),
						),
					),
					'assignment_answer' => array(
						'description' => __( 'User answer Assignments', 'learnpress-assignments' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
					),
					'evaluation'        => array(
						'description' => __( 'Evaluation', 'learnpress-assignments' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
					),
					'can_finish_course' => array(
						'description' => __( 'Can finish the course', 'learnpress-assignments' ),
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
