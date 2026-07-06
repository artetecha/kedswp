<?php

use LearnPress\Certificate\Models\CertificatePostModel;
use LearnPress\Certificate\Models\CourseCertificateInfo;
use LearnPress\Certificate\TemplateHooks\AdminCertificateTemplate;
use LearnPress\Certificate\TemplateHooks\AdminCourseCertificatesNew;
use LearnPress\Certificate\Upgrade\CertificateUpdater;
use LearnPress\Certificates\AdminCourseCertificates;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;

/**
 * Class LP_Certificates_Post_Type
 *
 * Manage post type for certificates
 *
 * @since 3.0.0
 */
class LP_Certificates_Post_Type extends LP_Abstract_Post_Type {
	/**
	 * Type of post
	 *
	 * @var string
	 */
	protected $_post_type = LP_ADDON_CERTIFICATES_CERT_CPT;

	/**
	 * @var bool|LP_Addon_Certificates
	 */
	//public $factory = null;

	/**
	 * LP_Certificates_Post_Type constructor.
	 */
	public function __construct() {
		parent::__construct( $this->_post_type );
		//add_action( 'init', array( $this, 'register_post_type' ), 11 );
		$this->register_post_type();
		add_action( 'add_meta_boxes', array( $this, 'handle_meta_boxes' ) );
		add_action( 'edit_form_after_editor', array( $this, 'cert_editor' ) );
		add_action( 'manage_' . LP_COURSE_CPT . '_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );
		add_filter( 'manage_edit-' . LP_COURSE_CPT . '_columns', array( $this, 'columns_head' ) );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_filter(
			'learn-press/review-order/cart-item-subtotal',
			array( $this, 'lp_cert_cart_item_subtotal' ),
			10,
			3
		);
		// change title item cart if is not course in page checkout.
		add_filter( 'learn-press/review-order/cart-item-name', array( $this, 'lp_cert_cart_item_name' ), 10, 3 );

		// calculate subtotal by item type in cart
		add_filter(
			'learnpress/cart/calculate_sub_total/item_type_lp_cert',
			array(
				$this,
				'lp_cert_cart_calculate_subtotal',
			),
			10,
			2
		);

		// Metabox course tab.
		add_filter(
			'learnpress/course/metabox/tabs',
			function ( $tabs ) {
				$tabs['certificates'] = array(
					'label'    => esc_html__( 'Certificates', 'learnpress-certificates' ),
					'icon'     => 'dashicons-welcome-learn-more',
					'target'   => 'certificate-browser',
					'priority' => 60,
				);

				return $tabs;
			}
		);

		add_action( 'lp_course_data_setting_tab_content', array( $this, 'course_tab_certificate' ) );
		add_action( 'save_post_' . LP_COURSE_CPT, array( $this, 'save_course_certificate_info' ) );

		LP_Request::register_ajax( 'update-course-certificate', array( $this, 'update_course_certificate' ) );

		// add data item to cart by session.
		add_filter(
			'learn-press/get-cart-item-from-session/item_type_lp_cert',
			array(
				$this,
				'lp_cert_cart_get_item_form_session',
			),
			10,
			2
		);

		// add data item type in page checkout.
		if ( version_compare( LEARNPRESS_VERSION, '4.2.8.4', '<' ) ) {
			// Hook old of LearnPress
			add_filter(
				'learn-press/review-order/cart-item-product',
				array(
					$this,
					'lp_cert_review_order_cart_item_cer',
				),
				10,
				2
			);
		} else {
			add_filter(
				'learn-press/review-order/item',
				function ( $itemModel, $cart_item ) {
					$itemModelCertificate = get_post( $cart_item['item_id'] ?? 0 );
					if ( $itemModelCertificate && $itemModelCertificate->post_type !== LP_ADDON_CERTIFICATES_CERT_CPT ) {
						return $itemModel;
					}

					return $itemModelCertificate;
				},
				10,
				2
			);

			add_action(
				'learn-press/checkout/cart-item',
				function ( $itemModel, $cart_item ) {
					if ( ! $itemModel instanceof WP_Post || $itemModel->post_type !== LP_ADDON_CERTIFICATES_CERT_CPT ) {
						return;
					}

					$courseModel = CourseModel::find( $cart_item['course_id'] ?? 0, true );
					if ( ! $courseModel instanceof CourseModel ) {
						return;
					}

					$price_cert = (float) get_post_meta( $itemModel->ID, '_lp_certificate_price', true );

					$singleCourseTemplate = SingleCourseTemplate::instance();
					$section              = [
						'td_image' => sprintf(
							'<td class="course-thumbnail">%s</td>',
							wp_kses_post( $singleCourseTemplate->html_image( $courseModel ) )
						),
						'td_name'  => sprintf(
							'<td class="course-name"><a href="%s" class="course-name">%s</a></td>',
							esc_url_raw( $courseModel->get_permalink() ),
							wp_kses_post( $courseModel->get_title() )
						),
						'td_total' => sprintf(
							'<td class="course-total col-number">%s</td>',
							learn_press_format_price( $price_cert * $cart_item['quantity'] )
						),
					];

					echo Template::combine_components( $section );
				},
				10,
				2
			);
		}

		// change link item cart if is not course in page checkout.
		add_filter(
			'learn-press/review-order/cart-item-link',
			array(
				$this,
				'lp_cert_review_order_cart_item_link',
			),
			10,
			2
		);

		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );

		add_filter( 'default_title', array( $this, 'default_certificate_title' ), 10, 2 );
	}

	public function default_certificate_title( $post_title, $post ) {
		if ( $post->post_type === LP_ADDON_CERTIFICATES_CERT_CPT && empty( $post_title ) ) {
			return 'Certificate';
		}
		return $post_title;
	}

	public function allow_callback( $callbacks ) {
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/admin/AdminCourseCertificates.php';
		$callbacks[] = AdminCourseCertificates::class . ':render_certificates';

		return $callbacks;
	}

	/**
	 * @param array $link
	 * @param array $cart_item : value by cart id;
	 * change link item cart if is not course.
	 */
	public function lp_cert_review_order_cart_item_link( $link, $cart_item ) {

		if ( ! empty( $cart_item['course_id'] ) ) {
			$link = get_the_permalink( $cart_item['course_id'] );
		}

		return $link;
	}

	/**
	 * @param array $item
	 * @param array $cart_item : value by cart id;
	 * show item cart in page checkout
	 */
	public function lp_cert_review_order_cart_item_cer( $item, $cart_item ) {

		if ( ! empty( $cart_item['course_id'] ) ) {
			// Todo: wait LP v4.2.8 release will change "learn_press_get_course" to CourseModel
			$item = learn_press_get_course( $cart_item['course_id'] );
		}

		return $item;
	}

	/**
	 * @param array $data
	 * @param array $values : value by cart id;
	 * add data item to cart by session
	 */
	public function lp_cert_cart_get_item_form_session( $data, $values ) {
		if ( get_post_type( $values['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
			$data = array_merge( $values, array( 'data' => get_post( $values['item_id'] ) ) );
		}

		return $data;
	}

	/**
	 * @param int $subtotal
	 * @param array $cart_item
	 * calculate subtotal by item type in cart
	 */
	public function lp_cert_cart_calculate_subtotal( $subtotal, $cart_item ) {
		if ( get_post_type( $cart_item['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
			$price_cert = get_post_meta( $cart_item['item_id'], '_lp_certificate_price', true );
			$subtotal   = $price_cert * $cart_item['quantity'];
		}

		return $subtotal;
	}

	/**
	 * @param array $title
	 * @param array $cart_item : value by cart id;
	 * @param array $cart_item_key
	 * change title item cart if is not course.
	 */
	public function lp_cert_cart_item_name( $title, $cart_item, $cart_item_key ) {
		if ( get_post_type( $cart_item['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {

			$title_course = '';
			if ( isset( $cart_item['course_id'] ) ) {
				$title_course = get_the_title( $cart_item['course_id'] );
			}

			$title = sprintf(
				'%s %s - %s %s',
				__( 'Certificate:', 'learnpress-certificates' ),
				get_the_title( $cart_item['item_id'] ),
				__( 'Course:', 'learnpress-certificates' ),
				$title_course
			);
		}

		return $title;
	}

	public function lp_cert_cart_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {
		if ( get_post_type( $cart_item['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
			$price_cert = get_post_meta( $cart_item['item_id'], '_lp_certificate_price', true );
			$row_price  = $price_cert * $cart_item['quantity'];

			return learn_press_format_price( $row_price, true );
		}

		return $subtotal;
	}

	/**
	 * Save price for certificate
	 *
	 * @param int $post_id
	 * @param WP_Post|null $post
	 * @param bool $is_update
	 *
	 * @return void
	 * @throws Exception
	 */
	public function save_post( int $post_id, ?WP_Post $post = null, bool $is_update = false ) {
		try {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( ! $post ) {
				$post = get_post( $post_id );
			}

			if ( ! $post || 'revision' === $post->post_type ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$certificateModel = CertificatePostModel::find( $post_id, true );
			if ( ! $certificateModel ) {
				return;
			}

			$price     = LP_Request::get_param( CertificatePostModel::META_KEY_PRICE, null, 'float', 'post' );
			$thumbnail = LP_Request::get_param( CertificatePostModel::META_KEY_THUMBNAIL, null, 'esc_url_raw', 'post' );

			if ( isset( $_POST[ CertificatePostModel::META_KEY_PRICE ] ) ) {
				$certificateModel->set_price( $price );
			}

			if ( isset( $_POST[ CertificatePostModel::META_KEY_THUMBNAIL ] ) ) {
				// Replace domain with absolute path to relative path
				$thumbnail = str_replace( home_url(), '', $thumbnail );
				$certificateModel->set_thumbnail( $thumbnail );
			}

			$certificateModel->clean_caches();
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}

	public function update_course_certificate( $post ) {
		$res = new LP_REST_Response();

		try {
			$course_id = LP_Request::get_param( 'course_id', 0, 'int', 'post' );
			$cert_id   = LP_Request::get_param( 'cert_id', 0, 'int', 'post' );

			if ( $cert_id ) {
				update_post_meta( $course_id, '_lp_cert', $cert_id );
			} else {
				delete_post_meta( $course_id, '_lp_cert' );
			}

			$res->status  = 'success';
			$res->message = __( 'Updated success', 'learnpress-certificates' );
		} catch ( Exception $e ) {
			$res->message = $e->getMessage();
		}

		wp_send_json( $res );
	}

	/**
	 * Show list certificates
	 *
	 * @param $post
	 *
	 * @return void
	 * @since 3.0.0
	 * @version 1.0.1
	 */
	public function course_tab_certificate( $post ) {
		// db_version = 1: display layout old, db_version = 2: display layout new.
		if ( CertificateUpdater::get_current_db_version() === 1 ) {
			include_once LP_ADDON_CERTIFICATES_PATH . '/inc/admin/AdminCourseCertificates.php';
			$args = [
				'id_url'    => 'certificates',
				'course_id' => $post->ID,
				'paged'     => 1,
			];
			/** @uses AdminCourseCertificates::render_certificates() */
			$callBack = [
				'class'  => AdminCourseCertificates::class,
				'method' => 'render_certificates',
			];

			echo Template::instance()->nest_elements(
				[
					'<div id="certificate-browser" class="theme-browser lp-meta-box-course-panels">' => '</div>',
				],
				TemplateAJAX::load_content_via_ajax( $args, $callBack )
			);
		} else {
			echo '<div id="certificate-browser" class="theme-browser lp-meta-box-course-panels">';
			AdminCourseCertificatesNew::render( $post );
			echo '</div>';
		}
	}

	public function save_course_certificate_info( int $post_id ) {
		//include_once LP_ADDON_CERTIFICATES_PATH . '/inc/admin/AdminCourseCertificatesNew.php';
		AdminCourseCertificatesNew::save( $post_id );
	}

	/**
	 * Register Certificate post type
	 *
	 * @since 3.0.0
	 */
	public function register_post_type() {
		//$this->factory = LP_Addon_Certificates_Preload::$addon;

		register_post_type(
			LP_ADDON_CERTIFICATES_CERT_CPT,
			array(
				'labels'              => array(
					'name'          => __( 'Certificate', 'learnpress-certificates' ),
					'menu_name'     => __( 'Certificates', 'learnpress-certificates' ),
					'singular_name' => __( 'Certificate', 'learnpress-certificates' ),
					'add_new_item'  => __( 'Add New Certificate', 'learnpress-certificates' ),
					'edit_item'     => __( 'Edit Certificate', 'learnpress-certificates' ),
					'all_items'     => __( 'Certificates', 'learnpress-certificates' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'has_archive'         => false,
				'capability_type'     => LP_ADDON_CERTIFICATES_CERT_CPT,
				'map_meta_cap'        => true,
				'show_in_menu'        => 'learn_press',
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => true,
				'supports'            => array(
					'title',
					'excerpt',
					'author',
				),
				'rewrite'             => array( 'slug' => 'certificate' ),
				'exclude_from_search' => true,
			)
		);

		/*register_post_type(
			LP_ADDON_CERTIFICATES_USER_CERT_CPT,
			array(
				'labels'             => array(
					'name'          => __( 'User Certificate', 'learnpress-certificates' ),
					'menu_name'     => __( 'User Certificates', 'learnpress-certificates' ),
					'singular_name' => __( 'User Certificate', 'learnpress-certificates' ),
					'add_new_item'  => __( 'Add New Certificate', 'learnpress-certificates' ),
					'edit_item'     => __( 'Edit Certificate', 'learnpress-certificates' ),
					'all_items'     => __( 'User Certificates', 'learnpress-certificates' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'has_archive'        => false,
				'capability_type'    => LP_COURSE_CPT,
				'map_meta_cap'       => true,
				'show_in_menu'       => false,
				'show_in_admin_bar'  => false,
				'show_in_nav_menus'  => false,
				'supports'           => array(
					'title',
					'author',
				),
				'rewrite'            => array( 'slug' => 'user-certificate' ),
			)
		);*/
		$cert_cap   = LP_ADDON_CERTIFICATES_CERT_CPT . 's';
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'read_private_' . $cert_cap );
			$admin_role->add_cap( 'delete_' . $cert_cap );
			$admin_role->add_cap( 'delete_published_' . $cert_cap );
			$admin_role->add_cap( 'edit_' . $cert_cap );
			$admin_role->add_cap( 'edit_published_' . $cert_cap );
			$admin_role->add_cap( 'publish_' . $cert_cap );
			$admin_role->add_cap( 'delete_private_' . $cert_cap );
			$admin_role->add_cap( 'edit_private_' . $cert_cap );
			$admin_role->add_cap( 'delete_others_' . $cert_cap );
			$admin_role->add_cap( 'edit_others_' . $cert_cap );
		}

		// Add capabilities for teacher role
		$teacher_role = get_role( LP_TEACHER_ROLE );
		if ( $teacher_role ) {
			$settings = LP_Settings::instance();
			$teacher_role->add_cap( 'read_private_' . $cert_cap );
			$teacher_role->add_cap( 'delete_published_' . $cert_cap );
			$teacher_role->add_cap( 'edit_published_' . $cert_cap );
			$teacher_role->add_cap( 'edit_' . $cert_cap );
			$teacher_role->add_cap( 'delete_' . $cert_cap );
		}
	}

	/**
	 * Certificates row actions.
	 *
	 * @param array $actions
	 * @param WP_Post $post
	 *
	 * @return mixed
	 */
	public function row_actions( $actions, $post ) {
		// check for your post type
		if ( LP_ADDON_CERTIFICATES_CERT_CPT == $post->post_type ) {
			$actions['export_cert'] = sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'edit.php?post_type=lp_cert&export=' . $post->ID ),
				__( 'Export', 'learnpress-certificates' )
			);
		}

		return $actions;
	}

	/**
	 * Add column to custom post type.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function columns_head( $columns ) {
		switch ( get_post_type() ) {
			case LP_ADDON_CERTIFICATES_CERT_CPT:
				$columns['courses'] = __( 'Courses', 'learnpress-certificates' );
				break;
			case LP_COURSE_CPT:
				$columns['certificate'] = __( 'Certificate', 'learnpress-certificates' );
				break;
		}

		return $columns;
	}

	/**
	 * Custom column content.
	 *
	 * @param string $column
	 * @param int $post_id
	 *
	 * @throws Exception
	 */
	public function columns_content( $column, $post_id = 0 ) {
		global $wpdb;
		switch ( $column ) {
			case 'certificate':
				$courseModel = CourseModel::find( $post_id, true );
				if ( ! $courseModel ) {
					break;
				}

				$certificate_id   = (int) $courseModel->get_meta_value_by_key( '_lp_cert', 0 );
				$certificateModel = CertificatePostModel::find( $certificate_id, true );
				if ( ! $certificateModel ) {
					break;
				}

				// db_version = 1: display layout old.
				if ( CertificateUpdater::get_current_db_version() === 1 ) {
					$certificate = new LP_Certificate( $certificate_id );
					if ( $certificate->get_id() ) {
						$preview = $certificate->get_preview();
						if ( $preview ) {
							echo '<div class="course-cert-preview">';
							echo sprintf(
								'<a href="%s"><img src="%s" alt="%s" /></a>',
								get_edit_post_link( $certificate_id ),
								$preview,
								$certificate->get_name()
							);
							echo '</div>';
						}
					} else {
						_e( '-', 'learnpress-certificates' );
					}
				} else {
					$coursePostModel = new CourseCertificateInfo( $courseModel );
					$img_url         = $coursePostModel->get_cert_image_url();
					echo '<div class="course-cert-preview">';
					echo sprintf(
						'<a href="%s"><img src="%s" alt="" style="max-width:80px;height:auto;" /></a>',
						esc_url( $certificate_id ? get_edit_post_link( $certificate_id ) : '#' ),
						esc_url( $img_url )
					);
					echo '</div>';
				}

				break;
			case 'courses':
				if ( get_post_type() !== LP_ADDON_CERTIFICATES_CERT_CPT ) {
					return;
				}

				$output = '';
				$query  = $wpdb->prepare(
					"
					SELECT p.ID, p.post_title
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
					INNER JOIN {$wpdb->posts} c ON c.ID = pm.meta_value AND pm.meta_key = %s
				    WHERE c.ID = %d
				",
					'_lp_cert',
					'_lp_cert',
					$post_id
				);

				$courses = $wpdb->get_results( $query );
				if ( $courses ) {
					$links = array();
					foreach ( $courses as $course ) {
						$links[] = sprintf(
							'<a href="%s">%s</a>',
							get_edit_post_link( $course->ID ),
							$course->post_title
						);
					}
					$output = join( ' | ', $links );
				}
				if ( $output ) {
					echo $output;
				} else {
					_e( 'Unassigned', 'learnpress-certificates' );
				}
		}
	}

	/**
	 * Add or remove meta boxes to certificate screen
	 */
	public function handle_meta_boxes() {
		// Remove meta boxes
		$meta_boxes_remove = [
			'authordiv',
			'postexcerpt',
			'poststuff',
		];

		foreach ( $meta_boxes_remove as $meta_box ) {
			remove_meta_box( $meta_box, LP_ADDON_CERTIFICATES_CERT_CPT, 'normal' );
		}

		// Add meta boxes
		add_meta_box(
			'lp_certificate_price',
			esc_html__( 'Certificate Price', 'learnpress-certificates' ),
			array( $this, 'render_price_meta_box' ),
			LP_ADDON_CERTIFICATES_CERT_CPT,
			'side',
			'default'
		);

		add_meta_box(
			'lp_certificate_thumbnail',
			esc_html__( 'Certificate Thumbnail', 'learnpress-certificates' ),
			array( $this, 'render_thumbnail_meta_box' ),
			LP_ADDON_CERTIFICATES_CERT_CPT,
			'side',
			'default'
		);
	}

	public function render_price_meta_box( $post ) {
		wp_nonce_field( 'lp-cert-settings-backend', 'certificates_fields' );
		$price = (float) get_post_meta( $post->ID, CertificatePostModel::META_KEY_PRICE, true );
		?>
		<div class="lp-cert-metabox-price">
			<label for="_lp_certificate_price" class="screen-reader-text">
				<?php esc_html_e( 'Price', 'learnpress-certificates' ); ?>
			</label>
			<input
				type="number"
				step="0.01"
				min="0"
				id="_lp_certificate_price"
				name="_lp_certificate_price"
				value="<?php echo esc_attr( $price ); ?>"
				class="widefat"
			/>
			<p class="description">
				<?php esc_html_e( 'Set 0 for free certificate.', 'learnpress-certificates' ); ?>
			</p>
		</div>
		<?php
	}

	public function render_thumbnail_meta_box( $post ) {
		$thumbnail     = get_post_meta( $post->ID, CertificatePostModel::META_KEY_THUMBNAIL, true );
		$thumbnail_url = ! empty( $thumbnail ) ? home_url( $thumbnail ) : '';
		?>
		<div class="lp-cert-metabox-thumbnail">
			<div class="lp-cert-thumbnail-preview" style="margin-bottom: 10px;">
				<?php if ( $thumbnail_url ) : ?>
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="" style="max-width: 100%; height: auto;" />
				<?php endif; ?>
			</div>
			<input
				type="hidden"
				id="_lp_cert_thumbnail"
				name="_lp_cert_thumbnail"
				value="<?php echo esc_attr( $thumbnail_url ); ?>"
			/>
			<button type="button" class="button lp-cert-upload-thumbnail">
				<?php echo $thumbnail_url ? esc_html__( 'Change Thumbnail', 'learnpress-certificates' ) : esc_html__( 'Set Thumbnail', 'learnpress-certificates' ); ?>
			</button>
			<?php if ( $thumbnail_url ) : ?>
				<button type="button" class="button lp-cert-remove-thumbnail" style="margin-left: 5px;">
					<?php esc_html_e( 'Remove', 'learnpress-certificates' ); ?>
				</button>
			<?php endif; ?>
			<p class="description">
				<?php esc_html_e( 'Certificate preview thumbnail image.', 'learnpress-certificates' ); ?>
			</p>
		</div>
		<script>
		jQuery(document).ready(function($) {
			var frame;
			$('.lp-cert-upload-thumbnail').on('click', function(e) {
				e.preventDefault();
				if (frame) {
					frame.open();
					return;
				}
				frame = wp.media({
					title: '<?php echo esc_js( __( 'Select Thumbnail', 'learnpress-certificates' ) ); ?>',
					button: { text: '<?php echo esc_js( __( 'Use as Thumbnail', 'learnpress-certificates' ) ); ?>' },
					multiple: false
				});
				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$('#_lp_cert_thumbnail').val(attachment.url);
					$('.lp-cert-thumbnail-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto;" />');
					$('.lp-cert-upload-thumbnail').text('<?php echo esc_js( __( 'Change Thumbnail', 'learnpress-certificates' ) ); ?>');
					if ($('.lp-cert-remove-thumbnail').length === 0) {
						$('.lp-cert-upload-thumbnail').after('<button type="button" class="button lp-cert-remove-thumbnail" style="margin-left: 5px;"><?php echo esc_js( __( 'Remove', 'learnpress-certificates' ) ); ?></button>');
					}
				});
				frame.open();
			});
			$(document).on('click', '.lp-cert-remove-thumbnail', function(e) {
				e.preventDefault();
				$('#_lp_cert_thumbnail').val('');
				$('.lp-cert-thumbnail-preview').html('');
				$('.lp-cert-upload-thumbnail').text('<?php echo esc_js( __( 'Set Thumbnail', 'learnpress-certificates' ) ); ?>');
				$(this).remove();
			});
		});
		</script>
		<?php
	}

	public function layers() {
		include LP_ADDON_CERTIFICATES_PATH . '/inc/admin/views/box-layers.php';
	}

	public function layer_options() {
		include LP_ADDON_CERTIFICATES_PATH . '/inc/admin/views/box-layer-options.php';
	}

	public function cert_editor( $post ) {
		$wp_screen = get_current_screen();

		if ( $post->post_type !== LP_ADDON_CERTIFICATES_CERT_CPT ) {
			return;
		}

		$certificatePostModel = CertificatePostModel::find( $post->ID, true );
		if ( ! $certificatePostModel ) {
			return;
		}

		// Check database version, if database need update, show notice to update database and hide editor
		$need_update = CertificateUpdater::instance()->check_db_need_upgrade();
		if ( $need_update ) {
			$message = sprintf(
				esc_html__( 'You need to update database to use this feature. %s', 'learnpress-certificates' ),
				sprintf(
					'<a href="#" class="lp-cert-upgrade-btn">%s</a>',
					esc_html__( 'Click to update', 'learnpress-certificates' )
				)
			);
			Template::print_message(
				$message,
				'warning'
			);
			return;
		}

		// Show editor
		$data = [
			'certificate' => $certificatePostModel,
		];
		AdminCertificateTemplate::instance()->edit_layout( $data );
	}
}

return new LP_Certificates_Post_Type();
