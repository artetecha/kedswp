<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;

/**
 * Class LP_Certificate
 */
class LP_User_Certificate extends LP_Certificate {

	/**
	 * Certificate post ID
	 *
	 * @var int
	 */
	protected $_id = 0;

	/**
	 * Layers
	 *
	 * @var null
	 */
	protected $_layers = null;

	/**
	 * @var string
	 */
	protected $_data_key = '';

	protected $_data = null;

	/**
	 * LP_Certificate constructor.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @param int $certificate_id
	 */
	public function __construct( $user_id = 0, $course_id = 0, $certificate_id = 0 ) {
		$this->_data = array(
			'user_id'   => $user_id,
			'course_id' => $course_id,
			'cert_id'   => $certificate_id,
		);
		//$this->_data = get_option( self::get_cert_key( $user_id, $course_id, $certificate_id ) );
		parent::__construct( $certificate_id );
	}

	public function get_data( $key = false ) {
		if ( empty( $this->_data ) ) {
			return false;
		}

		return false !== $key && array_key_exists( $key, $this->_data ) ? $this->_data[ $key ] : $this->_data;
	}

	/**
	 * Get link of user's certificate
	 * @param string $context
	 *
	 * @return bool|string
	 * @since 4.0.0
	 * @version 1.0.2
	 */
	public function get_permalink( $context = 'profile' ) {
		$user_id = $this->get_user_id();
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$key       = self::get_cert_key( $user_id, $this->get_data( 'course_id' ), $this->get_data( 'cert_id' ), false );
		$permalink = trailingslashit( get_home_url() ) . urlencode( LP_Settings::get_option( 'lp_cert_slug', 'certificates' ) ) . '/' . $key;

		return apply_filters( 'learn-press/certificates/permalink', $permalink, $user_id, $this->get_data( 'course_id' ), $this->get_data( 'cert_id' ), $context );
	}

	public function get_layers( $json = false ) {
		$layers = parent::get_layers();
		if ( $layers ) {
			$data = $this->get_data();
			foreach ( $layers as $k => $layer ) {
				$layers[ $k ]->apply( $data );

				if ( $json ) {
					$layers[ $k ] = $layers[ $k ]->options;
				}
			}
		}

		return $layers;
	}

	public function get_user_id() {
		return ! empty( $this->_data['user_id'] ) ? $this->_data['user_id'] : false;
	}

	public function get_course_id() {
		return ! empty( $this->_data['course_id'] ) ? $this->_data['course_id'] : false;
	}

	public function get_share_option_key() {
		return 'lp_cert_shared_' . $this->_get_cert_key( false );
	}

	public function is_shared() {
		$value = get_option( $this->get_share_option_key(), null );
		if ( null === $value ) {
			return true;
		}

		return '1' === (string) $value;
	}

	public function set_shared( $shared ) {
		return update_option( $this->get_share_option_key(), $shared ? '1' : '0', false );
	}

	public function can_view( $user_id = 0 ) {
		$global_enabled = lp_cert_share_link_enabled();
		$cert_public    = $global_enabled && $this->is_shared();

		if ( $cert_public ) {
			return true;
		}

		$user_id   = (int) $user_id;
		$owner_id  = (int) $this->get_user_id();
		$course_id = (int) $this->get_course_id();

		if ( $user_id && $owner_id && $user_id === $owner_id ) {
			return true;
		}

		if ( $user_id && user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		if ( $user_id && $course_id ) {
			$instructor_id = (int) get_post_field( 'post_author', $course_id );
			if ( $instructor_id && $user_id === $instructor_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 *
	 */
	public function _get_cert_key( $prefix = true ) {
		return self::get_cert_key( $this->get_data( 'user_id' ), $this->get_data( 'course_id' ), $this->get_data( 'cert_id' ), $prefix );
	}

	/**
	 *
	 */
	public function get_file_path() {
		$cert_key = $this->_get_cert_key( false );
		$lp_certs = get_user_meta( $this->get_user_id(), '_lp_certs', true );
		$lp_certs = (array) $lp_certs;
		$res      = false;

		if ( isset( $lp_certs[ $cert_key ] ) ) {
			$res = $lp_certs[ $cert_key ];
		}

		return $res;
	}

	public function get_image_file() {
		$cert_key = $this->_get_cert_key( false );
		if ( empty( $cert_key ) ) {
			return false;
		}

		$uploads = wp_upload_dir();
		$path    = $uploads['basedir'] . '/learn-press-cert/' . $cert_key . '.png';

		if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
			return false;
		}

		if ( 0 >= (int) filesize( $path ) ) {
			return false;
		}

		if ( ! $this->is_valid_cached_image_file( $path ) ) {
			return false;
		}

		return array(
			'path'      => $path,
			'url'       => $uploads['baseurl'] . '/learn-press-cert/' . $cert_key . '.png',
			// Use a proxy URL for <img src> so templates do not expose the uploads path directly.
			'proxy_url' => LP_Helper::get_link_no_cache( home_url( 'certificate/image/' . $cert_key ) ),
		);
	}

	protected function is_valid_cached_image_file( $path ) {
		$image_size = @getimagesize( $path );
		if ( ! $image_size ) {
			return false;
		}

		$json_data = $this->get_json_data();
		$width     = isset( $json_data['canvas_width'] ) ? (int) $json_data['canvas_width'] : 0;
		$height    = isset( $json_data['canvas_height'] ) ? (int) $json_data['canvas_height'] : 0;

		if ( $width <= 0 || $height <= 0 ) {
			return true;
		}

		return $image_size[0] >= $width && $image_size[1] >= $height;
	}

	public function get_json_data() {
		$lp_layer_raw = get_post_meta( $this->get_id(), '_lp_layer', true );

		if ( ! empty( $lp_layer_raw ) ) {
			return $this->get_json_data_new_builder( $lp_layer_raw );
		}

		$json = array(
			'id'           => $this->get_id(),
			'name'         => $this->get_name(),
			'layers'       => $this->get_layers( true ),
			'template'     => $this->get_template(),
			'preview'      => $this->get_preview(),
			'systemFonts'  => LP_Certificate::system_fonts(),
			'user_id'      => $this->get_user_id(),
			'course_id'    => $this->get_course_id(),
			'key_cer'      => LP_Certificate::get_cert_key( $this->get_user_id(), $this->get_course_id(), $this->get_id(), false ),
			'permalink'    => $this->get_permalink(),
			'no_image_url' => plugins_url( 'assets/images/no-image.png', LP_ADDON_CERTIFICATES_FILE ),
		);

		return apply_filters( 'learn-press/certificate/user-json-data', $json, $this->get_user_id(), $this->get_course_id(), $this->get_id() );
	}

	protected function get_json_data_new_builder( $lp_layer_raw ) {
		$builder_data = json_decode( $lp_layer_raw, true );

		if ( empty( $builder_data ) || ! is_array( $builder_data ) ) {
			$json = array(
				'id'           => $this->get_id(),
				'name'         => $this->get_name(),
				'layers'       => $this->get_layers( true ),
				'template'     => $this->get_template(),
				'preview'      => $this->get_preview(),
				'systemFonts'  => LP_Certificate::system_fonts(),
				'user_id'      => $this->get_user_id(),
				'course_id'    => $this->get_course_id(),
				'key_cer'      => LP_Certificate::get_cert_key( $this->get_user_id(), $this->get_course_id(), $this->get_id(), false ),
				'no_image_url' => plugins_url( 'assets/images/no-image.png', LP_ADDON_CERTIFICATES_FILE ),
			);

			return apply_filters( 'learn-press/certificate/user-json-data', $json, $this->get_user_id(), $this->get_course_id(), $this->get_id() );
		}

		$layers     = $builder_data['layers'] ?? [];
		$background = $builder_data['background'] ?? '';
		$width      = $builder_data['width'] ?? 842;
		$height     = $builder_data['height'] ?? 595;

		$replacements = $this->get_text_replacements();
		$permalink    = $this->get_permalink();

		foreach ( $layers as &$layer ) {
			if ( ! empty( $layer['text'] ) && is_string( $layer['text'] ) ) {
				$original_text = $layer['text'];

				$effective_replacements = $replacements;
				/*if ( ! empty( $layer['formatDate'] ) && ! empty( $this->_date_timestamps ) ) {
					$fmt            = $layer['formatDate'];
					$date_overrides = [
						'[TIME]' => $this->_date_timestamps['[TIME]'] ?? '',
					];
					foreach ( [ '[COURSE_START_DATE]', '[COURSE_END_DATE]' ] as $date_key ) {
						if ( array_key_exists( $date_key, $this->_date_timestamps ) ) {
							$ts                          = $this->_date_timestamps[ $date_key ] ?? '';
							$date_overrides[ $date_key ] = $ts;
						}
					}
					$effective_replacements = array_merge( $replacements, $date_overrides );
				}*/

				$replaced_text = strtr( $original_text, $effective_replacements );

				if ( $replaced_text !== $original_text ) {
					$layer['_placeholder_text'] = $original_text;
				}

				$layer['text'] = $replaced_text;
			}

			if ( isset( $layer['type_layer'] ) && $layer['type_layer'] === 'qr_code' ) {
				$layer['qr_url'] = $permalink;
			}
		}
		unset( $layer );

		$json = array(
			'id'            => $this->get_id(),
			'name'          => $this->get_name(),
			'builder_data'  => true,
			'canvas_width'  => $width,
			'canvas_height' => $height,
			'background'    => $background,
			'layers'        => $layers,
			'template'      => $this->get_template(),
			'preview'       => $this->get_preview(),
			'systemFonts'   => LP_Certificate::system_fonts(),
			'user_id'       => $this->get_user_id(),
			'course_id'     => $this->get_course_id(),
			'key_cer'       => LP_Certificate::get_cert_key( $this->get_user_id(), $this->get_course_id(), $this->get_id(), false ),
			'permalink'     => $permalink,
			'no_image_url'  => plugins_url( 'assets/images/no-image.png', LP_ADDON_CERTIFICATES_FILE ),
		);

		return apply_filters( 'learn-press/certificate/user-json-data', $json, $this->get_user_id(), $this->get_course_id(), $this->get_id() );
	}

	public static function get_available_placeholders( $cert_id = 0 ) {
		$dummy = new self( 0, 0, $cert_id );
		$raw   = $dummy->get_text_replacements();

		$label_map = array(
			'STUDENT_NAME'             => __( 'Student name', 'learnpress-certificates' ),
			'COURSE_TITLE'             => __( 'Course title', 'learnpress-certificates' ),
			'INSTRUCTOR_NAME'          => __( 'Instructor name', 'learnpress-certificates' ),
			'TIME'                     => __( 'Current time', 'learnpress-certificates' ),
			'COURSE_START_DATE'        => __( 'Course start date', 'learnpress-certificates' ),
			'COURSE_END_DATE'          => __( 'Course end date', 'learnpress-certificates' ),
			'COURSE_DESCRIPTION'       => __( 'Course description', 'learnpress-certificates' ),
			'COURSE_SHORT_DESCRIPTION' => __( 'Short description', 'learnpress-certificates' ),
			'COURSE_PRICE'             => __( 'Course price', 'learnpress-certificates' ),
			'COURSE_COUNT_STUDENT'     => __( 'Student count', 'learnpress-certificates' ),
			'COURSE_LEVEL'             => __( 'Course level', 'learnpress-certificates' ),
			'COURSE_DURATION'          => __( 'Course duration', 'learnpress-certificates' ),
			'COURSE_CAPACITY'          => __( 'Max students', 'learnpress-certificates' ),
			'COURSE_COUNT_LESSON'      => __( 'Lesson count', 'learnpress-certificates' ),
			'COURSE_COUNT_QUIZ'        => __( 'Quiz count', 'learnpress-certificates' ),
		);

		$preview_fields = apply_filters( 'learn-press/certificate/builder/preview-fields', array() );
		if ( is_array( $preview_fields ) ) {
			foreach ( $preview_fields as $field ) {
				if ( ! is_array( $field ) || empty( $field['placeholder'] ) ) {
					continue;
				}
				$label_map[ $field['placeholder'] ] = isset( $field['label'] ) ? $field['label'] : $field['placeholder'];
			}
		}

		$placeholders = array();

		foreach ( array_keys( $raw ) as $raw_key ) {
			$key = trim( $raw_key, '[]' );
			if ( empty( $key ) ) {
				continue;
			}
			$placeholders[] = array(
				'key'   => $key,
				'label' => isset( $label_map[ $key ] ) ? $label_map[ $key ] : $key,
			);
		}

		return apply_filters( 'learn-press/certificate/available-placeholders', $placeholders, $cert_id );
	}

	public function get_text_replacements() {
		$replacements = [
			'[STUDENT_NAME]'             => '',
			'[COURSE_TITLE]'             => '',
			'[INSTRUCTOR_NAME]'          => '',
			'[TIME]'                     => '',
			'[COURSE_START_DATE]'        => '',
			'[COURSE_END_DATE]'          => '',
			'[COURSE_DESCRIPTION]'       => '',
			'[COURSE_SHORT_DESCRIPTION]' => '',
			'[COURSE_PRICE]'             => '',
			'[COURSE_COUNT_STUDENT]'     => '',
			'[COURSE_LEVEL]'             => '',
			'[COURSE_DURATION]'          => '',
			'[COURSE_CAPACITY]'          => '',
			'[COURSE_COUNT_LESSON]'      => '',
			'[COURSE_COUNT_QUIZ]'        => '',
		];

		$user_id   = $this->get_user_id();
		$course_id = $this->get_course_id();

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			return $replacements;
		}

		$userModel = UserModel::find( $user_id, true );
		if ( ! $userModel ) {
			return $replacements;
		}

		$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
		if ( ! $userCourseModel ) {
			return $replacements;
		}

		// Student name
		$replacements['[STUDENT_NAME]'] = $userModel->get_display_name();
		if ( empty( $userModel->get_display_name() ) ) {
			$replacements['[STUDENT_NAME]'] = $userModel->get_username();
		}

		// Course title
		$replacements['[COURSE_TITLE]'] = $courseModel->get_title();

		// Instructor name
		$instructorModel = $courseModel->get_author_model();
		if ( $instructorModel ) {
			$replacements['[INSTRUCTOR_NAME]'] = $instructorModel->get_display_name();
			if ( empty( $instructorModel->get_display_name() ) ) {
				$replacements['[INSTRUCTOR_NAME]'] = $instructorModel->get_username();
			}
		}

		$lpDateTimeNow          = new LP_Datetime();
		$now_str                = $lpDateTimeNow->format( LP_Datetime::I18N_FORMAT );
		$replacements['[TIME]'] = $now_str;

		// Description
		$replacements['[COURSE_DESCRIPTION]'] = html_entity_decode(
			wp_strip_all_tags( $courseModel->get_description() ),
			ENT_QUOTES | ENT_HTML5,
			'UTF-8'
		);

		// Short description
		$replacements['[COURSE_SHORT_DESCRIPTION]'] = html_entity_decode(
			wp_strip_all_tags( $courseModel->get_short_description() ),
			ENT_QUOTES | ENT_HTML5,
			'UTF-8'
		);

		// Price
		if ( $courseModel->is_free() ) {
			$price_text = esc_html__( 'Free', 'learnpress' );
		} elseif ( $courseModel->has_no_enroll_requirement() ) {
			$price_text = '';
		} else {
			$price_text = html_entity_decode( learn_press_format_price( $courseModel->get_price() ) );
		}
		$replacements['[COURSE_PRICE]'] = $price_text;

		// Count student
		$count_student                          = $courseModel->get_total_user_enrolled_or_purchased();
		$count_student                         += $courseModel->get_fake_students();
		$replacements['[COURSE_COUNT_STUDENT]'] = sprintf(
			'%d %s',
			$count_student,
			_n( 'Student', 'Students', $count_student, 'learnpress' )
		);

		// Level
		$level = $courseModel->get_meta_value_by_key( CoursePostModel::META_KEY_LEVEL, '' );
		if ( empty( $level ) ) {
			$level = 'all';
		}
		$levels                         = lp_course_level();
		$replacements['[COURSE_LEVEL]'] = $levels[ $level ] ?? $levels['all'] ?? $level;

		// Duration
		$duration        = $courseModel->get_meta_value_by_key( CoursePostModel::META_KEY_DURATION, '' );
		$duration_arr    = explode( ' ', $duration );
		$duration_number = floatval( $duration_arr[0] ?? 0 );
		$duration_type   = $duration_arr[1] ?? '';
		if ( empty( $duration_number ) ) {
			$duration_str = __( 'Lifetime', 'learnpress' );
		} else {
			$duration_str = LP_Datetime::get_string_plural_duration( $duration_number, $duration_type );
		}
		$replacements['[COURSE_DURATION]'] = $duration_str;

		// Capacity
		$capacity = (int) $courseModel->get_meta_value_by_key( CoursePostModel::META_KEY_MAX_STUDENTS, 0 );
		if ( $capacity === 0 ) {
			$replacements['[COURSE_CAPACITY]'] = __( 'Unlimited', 'learnpress' );
		} else {
			$replacements['[COURSE_CAPACITY]'] = sprintf(
				'%d %s',
				$capacity,
				_n( 'Student', 'Students', $capacity, 'learnpress' )
			);
		}

		// Count lesson
		$info_total_items = $courseModel->get_total_items();
		if ( ! empty( $info_total_items ) ) {
			$count_lesson                          = $info_total_items->lp_lesson ?? 0;
			$replacements['[COURSE_COUNT_LESSON]'] = sprintf(
				'%d %s',
				$count_lesson,
				_n( 'Lesson', 'Lessons', $count_lesson, 'learnpress' )
			);

			// Count quiz
			$count_quiz                          = $info_total_items->lp_quiz ?? 0;
			$replacements['[COURSE_COUNT_QUIZ]'] = sprintf(
				'%d %s',
				$count_quiz,
				_n( 'Quiz', 'Quizzes', $count_quiz, 'learnpress' )
			);
		}

		$start_time      = $userCourseModel->get_start_time();
		$lpDateTimeStart = new LP_Datetime( $start_time );
		$end_time        = $userCourseModel->get_end_time();
		$lpDateTimeEnd   = new LP_Datetime( $end_time );

		$replacements['[COURSE_START_DATE]'] = $lpDateTimeStart->format( LP_Datetime::I18N_FORMAT );
		$replacements['[COURSE_END_DATE]']   = $lpDateTimeEnd->format( LP_Datetime::I18N_FORMAT );

		return apply_filters( 'learn-press/certificate/text-replacements', $replacements, $user_id, $course_id, $this->get_id() );
	}

	public function __toString() {
		return LP_Helper::json_encode( $this->get_json_data() );
	}
}
