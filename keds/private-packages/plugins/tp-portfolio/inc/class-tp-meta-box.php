<?php
if ( ! class_exists( 'Thim_Meta_Box' ) ) {
	/**
	 * Thim Theme - Meta Box Management
	 *
	 * Handles dynamic meta box rendering and field management in WordPress admin.
	 *
	 * @class Thim_Meta_Box
	 * @package thimpress
	 * @since 1.0
	 * @author kien16
	 */
	class Thim_Meta_Box {
		/**
		 * Meta box configuration
		 *
		 * @var array
		 */
		public $meta_box;

		/**
		 * Constructor
		 *
		 * @param array $args Meta box configuration arguments.
		 */
		public function __construct( $args ) {
			$this->meta_box = $args;

			// Register meta box and save hooks.
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'save_data' ) );

			// Image field Ajax handlers.
			add_action( 'wp_ajax_thim_attach_media', array( $this, 'wp_ajax_attach_media' ) );
			add_action( 'wp_ajax_thim_edit_media', array( $this, 'wp_ajax_edit_media' ) );
			add_action( 'wp_ajax_thim_reorder_images', array( $this, 'wp_ajax_reorder_images' ) );
			add_action( 'wp_ajax_thim_delete_file', array( $this, 'wp_ajax_delete_file' ) );

			// Image-video field Ajax handlers.
			add_action( 'wp_ajax_thim_attach_image_video', array( $this, 'wp_ajax_attach_image_video' ) );
			add_action( 'wp_ajax_thim_edit_image_video', array( $this, 'wp_ajax_edit_image_video' ) );
			add_action( 'wp_ajax_thim_reorder_image_video', array( $this, 'wp_ajax_reorder_image_video' ) );
			add_action( 'wp_ajax_thim_delete_image_video', array( $this, 'wp_ajax_delete_image_video' ) );
		}

		/**
		 * Enqueue admin styles and scripts
		 *
		 * @return void
		 */
		public function admin_enqueue_scripts() {
		}


		/**
		 * Register meta boxes
		 *
		 * @return void
		 */
		public function add_meta_boxes() {
			foreach ( $this->meta_box['pages'] as $page ) {
				add_meta_box(
					$this->meta_box['id'],
					$this->meta_box['title'],
					array( $this, 'meta_boxes_callback' ),
					$page,
					$this->meta_box['context'] ?? 'normal',
					$this->meta_box['priority'] ?? 'default',
					$this->meta_box['fields']
				);
			}
		}

		/**
		 * Render meta box content
		 *
		 * @param WP_Post $post Post object.
		 * @param array $fields Fields configuration.
		 *
		 * @return void
		 */
		public function meta_boxes_callback( $post, $fields ) {
			// Output nonce field for verification
			echo '<input type="hidden" name="thim_meta_box_nonce" value="' . esc_attr( wp_create_nonce( 'thim_meta_box_save' ) ) . '" />';
			echo '<div class="thim-metabox-tabs">';
			echo '<ul class="thim-tab-nav">';

			$tabs = $this->organize_fields_by_tabs( $fields['args'] );
			echo '</ul>';
			foreach ( $tabs as $tab => $tab_fields ) {
				echo '<div id="tab-' . esc_attr( $tab ) . '" class="thim-tab-content">';
				foreach ( $tab_fields as $field ) {
					$this->render_field( $field, $post->ID );
				}
				echo '</div>';
			}

			echo '</div>';
		}

		/**
		 * Organize fields by tab
		 *
		 * @param array $fields Fields list.
		 *
		 * @return array Organized fields by tab.
		 */
		private function organize_fields_by_tabs( $fields ) {
			$tabs = array();

			foreach ( $fields as $field ) {
				$tab  = $field['tab'] ?? 'default';
				$icon = $field['icon'] ?? 'dashicons-admin-generic';

				if ( ! isset( $tabs[ $tab ] ) ) {
					$tabs[ $tab ] = array();
					echo '<li><a href="#tab-' . esc_attr( $tab ) . '"><span class="dashicons ' . esc_attr( $icon ) . '"></span> ' . esc_html( $tab ) . '</a></li>';
				}

				$tabs[ $tab ][] = $field;
			}

			return $tabs;
		}

		/**
		 * Render a single field
		 *
		 * @param array $field Field configuration.
		 * @param int $post_id Post ID.
		 *
		 * @return void
		 */
		private function render_field( $field, $post_id ) {
			$field_type = $field['type'] ?? 'textfield';

			$method_name = 'field_' . $field_type;
			if ( method_exists( $this, $method_name ) ) {
				call_user_func( array( $this, $method_name ), $field, $post_id );
			}
		}

		/**
		 * Render color picker field
		 *
		 * @param array $field Field configuration.
		 * @param int $post_id Post ID.
		 *
		 * @return void
		 */
		private function field_color( $field, $post_id ) {

			wp_enqueue_style( 'wp-color-picker' );
			$color_value = get_post_meta( $post_id, $field['id'], true );
			$extra_class = $field['class'] ?? '';
			$extra_class = $extra_class ? 'thim-field ' . $extra_class : 'thim-field';
			?>
			<div class="<?php echo esc_attr( $extra_class ); ?>">
				<div class="thim-label">
					<label
						for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['name'] ); ?></label>
				</div>
				<div class="thim-input">
					<input type="text" class="thim-color-field" id="<?php echo esc_attr( $field['id'] ); ?>"
						   name="<?php echo esc_attr( $field['id'] ); ?>"
						   value="<?php echo esc_attr( $color_value ); ?>"/>
				</div>
			</div>
			<?php
		}

		/**
		 * Render repeater field
		 *
		 * @param array $field Field configuration.
		 * @param int $post_id Post ID.
		 *
		 * @return void
		 */
		private function field_repeater( $field, $post_id ) {
			$repeater_data = get_post_meta( $post_id, $field['id'], true );
			$extra_class   = $field['class'] ?? '';
			$extra_class   = $extra_class ? 'thim-field ' . $extra_class : 'thim-field';
			?>
			<div id="portfolio-repeater-wrapper" class="<?php echo esc_attr( $extra_class ); ?>">
				<?php
				if ( ! empty( $repeater_data ) && is_array( $repeater_data ) ) {
					foreach ( $repeater_data as $index => $row ) {
						$this->field_repeater_item( $field, $index, $row['title'] ?? '', $row['description'] ?? '' );
					}
				} else {
					$this->field_repeater_item( $field, 0, '', '' );
				}
				?>
			</div>
			<button type="button" id="add-<?php echo esc_attr( $field['id'] ); ?>" class="button">Add Item</button>
			<?php
		}

		/**
		 * Render repeater item
		 *
		 * @param array $field Field configuration.
		 * @param int $index Item index.
		 * @param string $title Item title.
		 * @param string $description Item description.
		 *
		 * @return void
		 */
		private function field_repeater_item( $field, $index, $title, $description ) {
			?>
			<div class="portfolio-repeater-item">
				<label><?php echo esc_html( $field['name'] ); ?> <?php echo absint( $index ) + 1; ?>:</label>
				<input type="text"
					   name="<?php echo esc_attr( $field['id'] ); ?>[<?php echo absint( $index ); ?>][title]"
					   value="<?php echo esc_attr( $title ); ?>" placeholder="Title">
				<input type="text"
					   name="<?php echo esc_attr( $field['id'] ); ?>[<?php echo absint( $index ); ?>][description]"
					   value="<?php echo esc_attr( $description ); ?>" placeholder="Description">
				<button type="button" class="remove-repeater-item button">Delete</button>
			</div>
			<?php
		}

		/**
		 * Render file upload field
		 *
		 * @param array $field Field configuration.
		 * @param int $post_id Post ID.
		 *
		 * @return void
		 */
		private function field_upload( $field, $post_id ) {
			$file_data   = get_post_meta( $post_id, $field['id'], true );
			$file_url    = $file_data['url'] ?? '';
			$file_name   = $file_data['name'] ?? '';
			$extra_class = $field['class'] ?? '';
			$extra_class = $extra_class ? 'thim-field ' . $extra_class : 'thim-field';
			?>
			<div class="file-preview <?php echo esc_attr( $extra_class ); ?>">
				<div class="thim-label">
					<label
						for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['name'] ); ?></label>
				</div>
				<div class="thim-input">
					<input type="text" id="<?php echo esc_attr( $field['id'] ); ?>_name"
						   name="<?php echo esc_attr( $field['id'] ); ?>_name"
						   value="<?php echo esc_attr( $file_name ); ?>" readonly/>
					<input type="text" id="<?php echo esc_attr( $field['id'] ); ?>_url"
						   name="<?php echo esc_attr( $field['id'] ); ?>_url"
						   value="<?php echo esc_url( $file_url ); ?>" readonly/>
					<button type="button" id="<?php echo esc_attr( $field['id'] ); ?>_button" class="button">Select
						File
					</button>
					<?php if ( $file_url ) : ?>
						<button type="button" class="portfolio_file_delete_button button">Delete File</button>
						<p><strong>Current File:</strong> <a href="<?php echo esc_url( $file_url ); ?>" target="_blank">View
								File</a></p>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}


		/**
		 * Render image-video field
		 *
		 * @param array $field Field configuration.
		 * @param int $post_id Post ID.
		 *
		 * @return void
		 */
		private function field_image_video( $field, $post_id ) {
			$images       = get_post_meta( $post_id, $field['id'], false );
			$attach_nonce = wp_create_nonce( "thim-attach-media_{$field['id']}" );
			$extra_class  = $field['class'] ?? '';
			$extra_class  = $extra_class ? ' ' . $extra_class : '';
			?>
			<div class="thim-field<?php echo esc_attr( $extra_class ); ?>">
				<div class="thim-label"><label><?php echo esc_html( $field['name'] ); ?></label></div>
				<div class="thim-input">
					<ul class="thim-images thim-images-video thim-image-video-uploaded ui-sortable"
						data-field_id="<?php echo esc_attr( $field['id'] ); ?>"
						data-reorder_nonce="<?php echo esc_attr( wp_create_nonce( "thim-reorder-images_{$field['id']}" ) ); ?>"
						data-delete_nonce="<?php echo esc_attr( wp_create_nonce( "thim-delete-file_{$field['id']}" ) ); ?>">
						<?php $this->render_image_video_list( $images ); ?>
					</ul>
					<a href="#" class="button thim-image-video-advanced-upload hide-if-no-js new-files"
					   data-attach_media_nonce="<?php echo esc_attr( $attach_nonce ); ?>">Select or Upload Images</a>
					<a href="javascript:void(null);" class="button-primary thim-video-advanced-upload-k"
					   data-attach_media_nonce="<?php echo esc_attr( $attach_nonce ); ?>">Add Video</a>
					<?php $this->render_video_dialog(); ?>
					<div class="desc"><?php echo isset( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : ''; ?></div>
				</div>
			</div>
			<?php
		}

		/**
		 * Render image-video list
		 *
		 * @param array $images Images array.
		 *
		 * @return void
		 */
		private function render_image_video_list( $images ) {
			foreach ( $images as $image ) {
				$img_url = wp_get_attachment_image_src( $image, 'thumbnail' );
				$link    = get_edit_post_link( $image );
				$url_img = $img_url ? $img_url[0] : '';

				if ( 'v.' === substr( $image, 0, 2 ) ) {
					// Vimeo video
					echo '<li id="item_' . esc_attr( $image ) . '"><iframe src="https://player.vimeo.com/video/' . esc_attr( substr( $image, 2 ) ) . '?title=0&byline=0&portrait=0&color=ffffff" width="150" height="150" frameborder="0"></iframe><div class="thim-image-bar"><a title="Edit" class="thim-edit-file-k" href="#" target="_blank">Edit</a> | <a title="Delete" class="thim-delete-file-k" href="#" data-attachment_id="' . esc_attr( $image ) . '">×</a></div></li>';
				} elseif ( 'y.' === substr( $image, 0, 2 ) ) {
					// YouTube video
					echo '<li id="item_' . esc_attr( $image ) . '"><iframe title="YouTube video player" class="youtube-player" type="text/html" width="150" height="150" src="https://www.youtube.com/embed/' . esc_attr( substr( $image, 2 ) ) . '" frameborder="0"></iframe><div class="thim-image-bar"><a title="Edit" class="thim-edit-file-k" href="#" target="_blank">Edit</a> | <a title="Delete" class="thim-delete-file-k" href="#" data-attachment_id="' . esc_attr( $image ) . '">×</a></div></li>';
				} else {
					// Regular image
					echo '<li id="item_' . esc_attr( $image ) . '"><img src="' . esc_url( $url_img ) . '" /><div class="thim-image-bar"><a title="Edit" class="thim-edit-file" href="' . esc_url( $link ) . '" target="_blank">Edit</a> | <a title="Delete" class="thim-delete-file" href="#" data-attachment_id="' . esc_attr( $image ) . '">×</a></div></li>';
				}
			}
		}

		/**
		 * Render video dialog
		 *
		 * @return void
		 */
		private function render_video_dialog() {
			?>
			<div id="dialog-k" title="Insert Video Code">
				<div class="thim-label">
					<label for="project_video_type">Video</label>
				</div>
				<div class="thim-input">
					<select class="thim-select" name="thim-video-type-k" id="thim-video-type-k" size="0">
						<option value="youtube" selected="selected">Youtube</option>
						<option value="vimeo">Vimeo</option>
					</select>
				</div>
				<div class="thim-label">
					<label for="project_video_embed">Video URL or own Embed Code</label>
				</div>
				<div class="thim-input">
					<textarea class="thim-textarea large-text" name="thim-video-data-k" id="thim-video-data-k" cols="40"
							  rows="8" placeholder=""></textarea>
					<p class="description">Paste the video ID or embed code. Video will show instead of image
						slider.</p>
				</div>
			</div>
			<?php
		}

		/**
		 * Render textarea field
		 *
		 * @param array $field Field configuration.
		 * @param int $post_id Post ID.
		 *
		 * @return void
		 */
		private function field_textarea( $field, $post_id ) {
			$post_meta   = get_post_meta( $post_id, $field['id'], true );
			$extra_class = $field['class'] ?? '';
			$extra_class = $extra_class ? ' ' . $extra_class : '';
			?>
			<div class="thim-field<?php echo esc_attr( $extra_class ); ?>">
				<div class="thim-label"><label><?php echo esc_html( $field['name'] ); ?>:</label></div>
				<div class="thim-input">
					<textarea name="<?php echo esc_attr( $field['id'] ); ?>"
							  id="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_textarea( $post_meta ); ?></textarea>
					<div class="desc"><?php echo isset( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : ''; ?></div>
				</div>
			</div>
			<?php
		}

		/**
		 * Render textfield
		 *
		 * @param array $field Field configuration.
		 * @param int $post_id Post ID.
		 *
		 * @return void
		 */
		private function field_textfield( $field, $post_id ) {
			$post_meta   = get_post_meta( $post_id, $field['id'], true );
			$extra_class = $field['class'] ?? '';
			$extra_class = $extra_class ? ' ' . $extra_class : '';
			$desc        = isset( $field['desc'] ) ? $field['desc'] : '';
			?>
			<div class="thim-field<?php echo esc_attr( $extra_class ); ?>">
				<div class="thim-label"><label><?php echo esc_html( $field['name'] ); ?>:</label></div>
				<div class="thim-input">
					<input type="text" name="<?php echo esc_attr( $field['id'] ); ?>"
						   value="<?php echo esc_attr( $post_meta ); ?>"/>
					<div class="desc"><?php echo wp_kses_post( $desc ); ?></div>
				</div>
			</div>
			<?php
		}

		/**
		 * Render checkbox field
		 *
		 * @param array $field Field configuration.
		 * @param int $post_id Post ID.
		 *
		 * @return void
		 */
		private function field_checkbox( $field, $post_id ) {
			$post_meta   = get_post_meta( $post_id, $field['id'], true );
			$extra_class = $field['class'] ?? '';
			$extra_class = $extra_class ? ' ' . $extra_class : '';
			?>
			<div class="thim-field<?php echo esc_attr( $extra_class ); ?>">
				<div class="thim-label"><label><?php echo esc_html( $field['name'] ); ?></label></div>
				<div class="thim-input">
					<input type="checkbox"
						   name="<?php echo esc_attr( $field['id'] ); ?>" <?php checked( $post_meta, 'on' ); ?> />
					<div class="desc"><?php echo wp_kses_post( $field['desc'] ); ?></div>
				</div>
			</div>
			<?php
		}

		/**
		 * Render select field
		 *
		 * @param array $field Field configuration.
		 * @param int $post_id Post ID.
		 *
		 * @return void
		 */
		private function field_select( $field, $post_id ) {
			$post_meta   = get_post_meta( $post_id, $field['id'], true );
			$extra_class = $field['class'] ?? '';
			$extra_class = $extra_class ? ' ' . $extra_class : '';
			$desc        = isset( $field['desc'] ) ? $field['desc'] : '';
			?>
			<div class="thim-field<?php echo esc_attr( $extra_class ); ?>">
				<div class="thim-label"><label><?php echo esc_html( $field['name'] ); ?></label></div>
				<div class="thim-input">
					<select name="<?php echo esc_attr( $field['id'] ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>">
						<?php foreach ( $field['options'] as $key => $value ) : ?>
							<option
								value="<?php echo esc_attr( $key ); ?>" <?php selected( $post_meta, $key ); ?>><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
					<div class="desc"><?php echo wp_kses_post( $desc ); ?></div>
				</div>
			</div>
			<?php
		}

		/**
		 * Render image field
		 *
		 * @param array $field Field configuration.
		 * @param int $post_id Post ID.
		 *
		 * @return void
		 */
		private function field_image( $field, $post_id ) {
			$images       = get_post_meta( $post_id, $field['id'], false );
			$attach_nonce = wp_create_nonce( "thim-attach-media_{$field['id']}" );
			$extra_class  = $field['class'] ?? '';
			$extra_class  = $extra_class ? ' ' . $extra_class : '';
			?>
			<div class="thim-field<?php echo esc_attr( $extra_class ); ?>">
				<div class="thim-label"><label><?php echo esc_html( $field['name'] ); ?></label></div>
				<div class="thim-input">
					<ul class="thim-images thim-uploaded ui-sortable"
						data-field_id="<?php echo esc_attr( $field['id'] ); ?>"
						data-reorder_nonce="<?php echo esc_attr( wp_create_nonce( "thim-reorder-images_{$field['id']}" ) ); ?>"
						data-delete_nonce="<?php echo esc_attr( wp_create_nonce( "thim-delete-file_{$field['id']}" ) ); ?>">
						<?php $this->render_image_list( $images ); ?>
					</ul>
					<a href="#" class="button thim-image-advanced-upload hide-if-no-js new-files"
					   data-attach_media_nonce="<?php echo esc_attr( $attach_nonce ); ?>">Select or Upload Images</a>
					<div class="desc"><?php echo isset( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : ''; ?></div>
				</div>
			</div>
			<?php
		}

		/**
		 * Render image list
		 *
		 * @param array $images Images array.
		 *
		 * @return void
		 */
		private function render_image_list( $images ) {
			foreach ( $images as $image ) {
				$img_url = wp_get_attachment_image_src( $image, 'thumbnail' );
				$link    = get_edit_post_link( $image );
				$url_img = $img_url ? $img_url[0] : '';
				?>
				<li id="item_<?php echo esc_attr( $image ); ?>">
					<img src="<?php echo esc_url( $url_img ); ?>"/>
					<div class="thim-image-bar">
						<a title="Edit" class="thim-edit-file" href="<?php echo esc_url( $link ); ?>" target="_blank">Edit</a>
						|
						<a title="Delete" class="thim-delete-file" href="#"
						   data-attachment_id="<?php echo esc_attr( $image ); ?>">×</a>
					</div>
				</li>
				<?php
			}
		}

		/**
		 * Ajax callback for attaching media to field
		 *
		 * @return void
		 */
		public static function wp_ajax_attach_media() {
			$post_id        = is_numeric( $_REQUEST['post_id'] ) ? $_REQUEST['post_id'] : 0;
			$field_id       = isset( $_POST['field_id'] ) ? $_POST['field_id'] : 0;
			$attachment_ids = isset( $_POST['attachment_ids'] ) ? $_POST['attachment_ids'] : array();

			check_ajax_referer( "thim-attach-media_{$field_id}" );
			$html = '';
			foreach ( $attachment_ids as $attachment_id ) {
				$img_url = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
				$link    = get_edit_post_link( $attachment_id );
				$html    .= '<li id="item_' . $attachment_id . '">
                            <img src="' . $img_url[0] . '">
                                <div class="thim-image-bar">
                                    <a title="Edit" class="thim-edit-file" href="' . $link . '" target="_blank">Edit</a> |
                                    <a title="Delete" class="thim-delete-file" href="#" data-attachment_id="' . $attachment_id . '">×</a>
                            </div>
                        </li>';

				add_post_meta( $post_id, $field_id, $attachment_id, false );
			}
			wp_send_json_success( $html );
		}

		/**
		 * Ajax callback for attaching media to field
		 *
		 * @return void
		 */
		public static function wp_ajax_edit_media() {
			$post_id        = is_numeric( $_REQUEST['post_id'] ) ? $_REQUEST['post_id'] : 0;
			$field_id       = isset( $_POST['field_id'] ) ? $_POST['field_id'] : 0;
			$attachment_ids = isset( $_POST['attachment_ids'] ) ? $_POST['attachment_ids'] : array();
			$attachment_old = isset( $_POST['attachment_old'] ) ? $_POST['attachment_old'] : 0;

			check_ajax_referer( "thim-attach-media_{$field_id}" );
			foreach ( $attachment_ids as $attachment_id ) {
				update_post_meta( $post_id, $field_id, $attachment_id, $attachment_old );
			}
			wp_send_json_success();
		}

		/**
		 * Ajax callback for reordering images
		 *
		 * @return void
		 */
		public static function wp_ajax_reorder_images() {
			$field_id = isset( $_POST['field_id'] ) ? $_POST['field_id'] : 0;
			$order    = isset( $_POST['order'] ) ? $_POST['order'] : 0;
			$post_id  = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

			check_ajax_referer( "thim-reorder-images_{$field_id}" );

			parse_str( $order, $items );

			delete_post_meta( $post_id, $field_id );
			foreach ( $items['item'] as $item ) {
				add_post_meta( $post_id, $field_id, $item, false );
			}
			wp_send_json_success();
		}

		/**
		 * Ajax callback for deleting files.
		 * Modified from a function
		 *
		 * @return void
		 */
		public static function wp_ajax_delete_file() {
			$post_id       = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
			$field_id      = isset( $_POST['field_id'] ) ? $_POST['field_id'] : 0;
			$attachment_id = isset( $_POST['attachment_id'] ) ? $_POST['attachment_id'] : 0; //change
			$force_delete  = isset( $_POST['force_delete'] ) ? intval( $_POST['force_delete'] ) : 0;

			check_ajax_referer( "thim-delete-file_{$field_id}" );

			delete_post_meta( $post_id, $field_id, $attachment_id );
			$ok = $force_delete ? wp_delete_attachment( $attachment_id ) : true;

			if ( $ok ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( __( 'Error: Cannot delete file', 'tp-portfolio' ) );
			}
		}

		/******************************/
		/**
		 * Ajax callback for attaching media to field
		 *
		 * @return void
		 */
		public static function wp_ajax_attach_image_video() {
			$post_id        = is_numeric( $_REQUEST['post_id'] ) ? $_REQUEST['post_id'] : 0;
			$field_id       = isset( $_POST['field_id'] ) ? $_POST['field_id'] : 0;
			$attachment_ids = isset( $_POST['attachment_ids'] ) ? $_POST['attachment_ids'] : array();

			check_ajax_referer( "thim-attach-media_{$field_id}" );
			$html = '';
			foreach ( $attachment_ids as $attachment_id ) {
				$img_url = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
				$link    = get_edit_post_link( $attachment_id );
				$html    .= '<li id="item_' . $attachment_id . '">
                            <img src="' . $img_url[0] . '">
                                <div class="thim-image-bar">
                                    <a title="Edit" class="thim-edit-file" href="' . $link . '" target="_blank">Edit</a> |
                                    <a title="Delete" class="thim-delete-file" href="#" data-attachment_id="' . $attachment_id . '">×</a>
                            </div>
                        </li>';

				add_post_meta( $post_id, $field_id, $attachment_id, false );
			}
			wp_send_json_success( $html );
		}

		/**
		 * Ajax callback for attaching media to field
		 *
		 * @return void
		 */
		public static function wp_ajax_edit_image_video() {
			$post_id        = is_numeric( $_REQUEST['post_id'] ) ? $_REQUEST['post_id'] : 0;
			$field_id       = isset( $_POST['field_id'] ) ? $_POST['field_id'] : 0;
			$attachment_ids = isset( $_POST['attachment_ids'] ) ? $_POST['attachment_ids'] : array();
			$attachment_old = isset( $_POST['attachment_old'] ) ? $_POST['attachment_old'] : 0;

			check_ajax_referer( "thim-attach-media_{$field_id}" );
			foreach ( $attachment_ids as $attachment_id ) {
				update_post_meta( $post_id, $field_id, $attachment_id, $attachment_old );
				//add_post_meta( $post_id, $field_id, $attachment_id, false );
			}
			wp_send_json_success();
		}

		/**
		 * Ajax callback for reordering images
		 *
		 * @return void
		 */
		public static function wp_ajax_reorder_image_video() {
			$field_id = isset( $_POST['field_id'] ) ? $_POST['field_id'] : 0;
			$order    = isset( $_POST['order'] ) ? $_POST['order'] : 0;
			$post_id  = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

			check_ajax_referer( "thim-reorder-images_{$field_id}" );

			parse_str( $order, $items );

			delete_post_meta( $post_id, $field_id );
			foreach ( $items['item'] as $item ) {
				add_post_meta( $post_id, $field_id, $item, false );
			}
			wp_send_json_success();
		}

		/**
		 * Ajax callback for deleting files.
		 * Modified from a function
		 *
		 * @return void
		 */
		public static function wp_ajax_delete_image_video() {
			$post_id       = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
			$field_id      = isset( $_POST['field_id'] ) ? $_POST['field_id'] : 0;
			$attachment_id = isset( $_POST['attachment_id'] ) ? $_POST['attachment_id'] : 0; //change
			$force_delete  = isset( $_POST['force_delete'] ) ? intval( $_POST['force_delete'] ) : 0;

			check_ajax_referer( "thim-delete-file_{$field_id}" );

			delete_post_meta( $post_id, $field_id, $attachment_id );
			$ok = $force_delete ? wp_delete_attachment( $attachment_id ) : true;

			if ( $ok ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( __( 'Error: Cannot delete file', 'tp-portfolio' ) );
			}
		}

		/**
		 * Save meta box data
		 *
		 * @param int $post_id Post ID.
		 *
		 * @return int Post ID.
		 */
		public function save_data( $post_id ) {
			// Verify POST request and nonce
			if ( ! isset( $_POST['thim_meta_box_nonce'] ) ) {
				return $post_id;
			}

			// Verify nonce
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['thim_meta_box_nonce'] ) ), 'thim_meta_box_save' ) ) {
				return $post_id;
			}

			// Check for autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}

			// Check user permissions
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}

			// Save fields
			foreach ( $this->meta_box['fields'] as $field ) {
				$this->save_field( $post_id, $field );
			}

			return $post_id;
		}

		/**
		 * Save individual field
		 *
		 * @param int $post_id Post ID.
		 * @param array $field Field configuration.
		 *
		 * @return void
		 */
		private function save_field( $post_id, $field ) {
			$field_type = $field['type'] ?? 'textfield';
			$field_id   = $field['id'];

			// Skip if field not in POST (except for upload which handles deletion)
			if ( ! isset( $_POST[ $field_id ] ) && 'upload' !== $field_type && 'repeater' !== $field_type ) {
				return;
			}

			switch ( $field_type ) {
				case 'textfield':
				case 'textarea':
				case 'select':
				case 'checkbox':
				case 'image':
				case 'color':
				case 'image_video':
					$this->save_simple_field( $post_id, $field_id );
					break;

				case 'upload':
					$this->save_upload_field( $post_id, $field );
					break;

				case 'repeater':
					$this->save_repeater_field( $post_id, $field );
					break;
			}
		}

		/**
		 * Save simple field
		 *
		 * @param int $post_id Post ID.
		 * @param string $field_id Field ID.
		 *
		 * @return void
		 */
		private function save_simple_field( $post_id, $field_id ) {
			$old = get_post_meta( $post_id, $field_id, true );
			$new = isset( $_POST[ $field_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_id ] ) ) : '';

			if ( $new && $new !== $old ) {
				update_post_meta( $post_id, $field_id, $new );
			} elseif ( ! $new && $old ) {
				delete_post_meta( $post_id, $field_id );
			}
		}

		/**
		 * Save upload field
		 *
		 * @param int $post_id Post ID.
		 * @param array $field Field configuration.
		 *
		 * @return void
		 */
		private function save_upload_field( $post_id, $field ) {
			$field_id = $field['id'];
			$name_key = $field_id . '_name';
			$url_key  = $field_id . '_url';

			$new_name = isset( $_POST[ $name_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name_key ] ) ) : '';
			$new_url  = isset( $_POST[ $url_key ] ) ? esc_url_raw( wp_unslash( $_POST[ $url_key ] ) ) : '';

			$old_meta = get_post_meta( $post_id, $field_id, true );

			// Normalize old meta
			if ( is_array( $old_meta ) ) {
				$old_name = $old_meta['name'] ?? '';
				$old_url  = $old_meta['url'] ?? '';
			} else {
				$old_name = '';
				$old_url  = $old_meta ?: '';
			}

			if ( empty( $new_name ) && empty( $new_url ) ) {
				delete_post_meta( $post_id, $field_id );
			} else {
				$new_meta = array(
					'name' => $new_name,
					'url'  => $new_url,
				);

				if ( $new_meta !== array( 'name' => $old_name, 'url' => $old_url ) ) {
					update_post_meta( $post_id, $field_id, $new_meta );
				}
			}
		}

		/**
		 * Save repeater field
		 *
		 * @param int $post_id Post ID.
		 * @param array $field Field configuration.
		 *
		 * @return void
		 */
		private function save_repeater_field( $post_id, $field ) {
			$field_id = $field['id'];

			if ( isset( $_POST[ $field_id ] ) && is_array( $_POST[ $field_id ] ) ) {
				// Sanitize repeater data
				$repeater_data = wp_unslash( $_POST[ $field_id ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$data          = array();

				foreach ( $repeater_data as $item ) {
					if ( is_array( $item ) ) {
						$data[] = array(
							'title'       => sanitize_text_field( $item['title'] ?? '' ),
							'description' => sanitize_text_field( $item['description'] ?? '' ),
						);
					}
				}

				// Only update if data exists
				if ( ! empty( $data ) ) {
					update_post_meta( $post_id, $field_id, $data );
				} else {
					delete_post_meta( $post_id, $field_id );
				}
			} else {
				delete_post_meta( $post_id, $field_id );
			}
		}
	}
}
