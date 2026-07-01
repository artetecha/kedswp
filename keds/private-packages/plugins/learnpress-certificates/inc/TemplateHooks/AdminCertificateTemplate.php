<?php

namespace LearnPress\Certificate\TemplateHooks;

use LearnPress\Certificate\Models\CertificatePostModel;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LP_Addon_Certificates;
use LP_Debug;
use Throwable;

/**
 * Class AdminCertificateTemplate
 *
 * @package LearnPress\Certificate\TemplateHooks
 *
 * @since 4.2.0
 * @version 1.0.0
 */
class AdminCertificateTemplate {
	use Singleton;

	public array $menus = [];

	public function init() {
		$this->menus = include_once LP_ADDON_CERTIFICATES_PATH . '/config/builder-menus.php';

		// Set hook where you want to display certificate builder layout.
		add_action( 'learn-press/certificate/edit/layout', [ $this, 'edit_layout' ] );
	}

	public function edit_layout( array $data ) {
		try {
			$certificatePostModel = $data['certificate'] ?? null;
			if ( ! $certificatePostModel instanceof CertificatePostModel ) {
				return;
			}

			wp_enqueue_style( 'lp-certificate-builder-css' );
			wp_enqueue_media();
			wp_enqueue_script( 'edit-certificate-js' );

			$templateArgs = [
				'certificate' => $certificatePostModel,
			];

			$builder_mode = isset( $_GET['cer_builder'] ) ? sanitize_text_field( wp_unslash( $_GET['cer_builder'] ) ) : '';

			ob_start();
			LP_Addon_Certificates::instance()->get_admin_template(
				'edit-certificate.php',
				compact( 'certificatePostModel' )
			);
			$builder_content = ob_get_clean();

			$section = [
				'wrap'     => sprintf(
					'<div class="lp-certificate-edit-wrapper" data-cert-id="%s">',
					esc_attr( $certificatePostModel->ID )
				),
				'edit'     => $builder_content,
				'wrap-end' => '</div>',
			];

			echo Template::combine_components( $section );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}

	/**
	 * HTML builder certificate.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_builder( array $data ): string {
		$section = [
			'wrap'     => '<div class="lp-cert-builder-wrap lp-hidden">',
			'menu'     => $this->html_menu_builder(),
			'inserter' => $this->html_inserter(),
			'content'  => sprintf(
				'<div class="lp-cert-builder-content-area">%s%s</div>',
				'<div class="lp-builder-canvas"></div>',
				$this->html_layer_option()
			),
			'wrap-end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML menu(navigation) builder.
	 *
	 * @return string
	 */
	public function html_menu_builder(): string {
		$menus = $this->menus;

		$html_li = '';
		foreach ( $menus as $id => $menu ) {
			$html_li .= sprintf(
				'<li class="lp-cert-builder-menu-item %s" data-menu="%s">
					<i class="%s"></i>
					<span>%s</span>
				</li>',
				$menu['active'] ?? false ? 'active' : '',
				esc_attr( $id ),
				esc_attr( $menu['icon'] ),
				esc_html( $menu['label'] )
			);
		}

		return sprintf( '<ul class="lp-cert-builder-menu">%s</ul>', $html_li );
	}

	/**
	 * HTML inserter area.
	 * When click menu item, will show inserter for that menu.
	 *
	 * @return string
	 */
	public function html_inserter(): string {
		$menus = $this->menus;

		// $search = '<div class="lp-cert-builder-search-wrapper">
		//  <i class="lp-icon-search"></i>
		//  <input name="lp-inserter-search" type="text" class="lp-cert-builder-search-input" placeholder="' . esc_attr__( 'Search...', 'learnpress-certificates' ) . '" />
		// </div>';

		$search = '';

		$html = '';
		foreach ( $menus as $id => $menu ) {
			switch ( $id ) {
				case 'elements':
					$html .= $this->html_inserter_elements();
					break;
				case 'uploads':
					$html .= $this->html_inserter_upload();
					break;
				case 'library':
					$html .= $this->html_inserter_library();
					break;
				case 'backgrounds':
					$html .= $this->html_inserter_backgrounds();
					break;
				case 'templates':
					$html .= $this->html_inserter_templates();
					break;
				case 'layers':
					$html .= $this->html_inserter_layers();
					break;
				default:
					break;
			}
		}

		return sprintf( '<div class="lp-cert-builder-inserter-area">%s%s</div>', $search, $html );
	}

	/**
	 * HTML list elements to insert into certificate.
	 *
	 * @return string
	 */
	public function html_inserter_elements(): string {
		$html_list_group = '';

		$groups = include_once LP_ADDON_CERTIFICATES_PATH . '/config/elements-insert.php';

		foreach ( $groups as $group ) {
			$group_label = $group['label'] ?? '';
			$items       = $group['items'] ?? [];
			$html_items  = '';
			foreach ( $items as $item_id => $item ) {
				$type_layer = $item['type_layer'] ?? 'text-static';

				$html_items .= sprintf(
					'<div class="lp-cert-inserter-item %1$s" data-insert="%2$s" data-type-layer="%3$s" data-label="%4$s">
						<i class="%5$s"></i>
						<span>%6$s</span>
					</div>',
					esc_attr( $item['class'] ?? '' ),
					esc_attr( $item_id ),
					esc_attr( $type_layer ),
					esc_attr( $item['label'] ?? '' ),
					esc_attr( $item['icon'] ?? '' ),
					esc_html( $item['label'] ?? '' )
				);
			}

			$html_list_group .= sprintf(
				'<div class="lp-cert-inserter-group expanded">
					<div class="lp-cert-inserter-group-header">
					<label>%1$s</label>
						<span class="dashicons dashicons-arrow-down-alt2 lp-cert-inserter-group-toggle"></span>
					</div>
					<div class="lp-cert-inserter-group-content">
					<div class="lp-cert-inserter-items">%2$s</div>
					</div>
				</div>',
				esc_html( $group_label ),
				$html_items
			);
		}

		$section = [
			'wrap'     => '<div class="lp-inserter-item elements active">',
			'list'     => $html_list_group,
			'wrap-end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function render_upload_items( array $images ): string {
		$html = '';

		foreach ( $images as $image ) {
			$image_url = wp_get_attachment_image_url( $image->ID, 'thumbnail' );
			$full_url  = wp_get_attachment_image_url( $image->ID, 'full' );

			if ( $image_url ) {
				$html .= sprintf(
					'<div class="lp-inserter-upload-item" data-image-url="%s" data-type-layer="image">
						<img src="%s" alt="%s" loading="lazy">
					</div>',
					esc_attr( $full_url ),
					esc_url( $image_url ),
					esc_attr( $image->post_title )
				);
			}
		}

		return $html;
	}

	public function html_inserter_upload(): string {
		$per_page = 20;
		$images   = get_posts(
			[
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'posts_per_page' => $per_page,
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		$images_html = '';
		if ( ! empty( $images ) ) {
			$images_html .= '<div class="lp-inserter-upload-grid">';
			$images_html .= $this->render_upload_items( $images );
			$images_html .= '</div>';
		} else {
			$images_html = '<div class="lp-inserter-upload-empty"><p>' . esc_html__( 'No images found', 'learnpress-certificates' ) . '</p></div>';
		}

		$has_more  = count( $images ) === $per_page;
		$load_more = $has_more
			? sprintf(
				'<button type="button" class="lp-inserter-upload-load-more" data-offset="%1$d" data-per-page="%2$d">%3$s</button>',
				esc_attr( count( $images ) ),
				esc_attr( $per_page ),
				esc_html__( 'Load more', 'learnpress-certificates' )
			)
			: '';

		$section = [
			'wrap'     => '<div class="lp-inserter-item uploads">',
			'lists'    => '<div class="lp-inserter-upload-area">
				<div class="lp-inserter-upload-header">
					<button type="button" class="button lp-inserter-upload-btn">' . esc_html__( 'Upload', 'learnpress-certificates' ) . '</button>
				</div>
				' . $images_html . '
				' . $load_more . '
			</div>',
			'wrap-end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_inserter_library(): string {
		$shapes = $this->get_shapes_library();

		$html  = '<div class="lp-inserter-item library">';
		$html .= '<div class="lp-inserter-library-area lp-library-main-view">';

		$html .= $this->html_library_default_images_section();

		$html .= '<div class="lp-inserter-library__section">';
		$html .= '<h4 class="lp-inserter-library__title">' . esc_html__( 'Shapes', 'learnpress-certificates' ) . '</h4>';
		$html .= '<div class="lp-inserter-library__grid">';

		foreach ( $shapes as $shape ) {
			$shape_data = htmlspecialchars( wp_json_encode( $shape ), ENT_QUOTES, 'UTF-8' );
			$html      .= '<div class="lp-inserter-library__item" data-shape="' . $shape_data . '">';
			$html      .= '<div class="lp-inserter-library__preview">';
			$html      .= $this->render_shape_preview( $shape );
			$html      .= '</div>';
			$html      .= '<span class="lp-inserter-library__name">' . esc_html( $shape['name'] ) . '</span>';
			$html      .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		$html .= '</div>';

		$html .= $this->html_library_images_full_view();

		$html .= '</div>';

		return $html;
	}

	public function html_library_default_images_section(): string {
		$images   = $this->get_default_library_images();
		$per_page = 6;
		$display  = array_slice( $images, 0, $per_page );

		$html  = '<div class="lp-library-section lp-library-images-section">';
		$html .= '<div class="lp-library-section-header">';
		$html .= '<h4 class="lp-inserter-library__title">' . esc_html__( 'System Images', 'learnpress-certificates' ) . '</h4>';

		if ( count( $images ) > $per_page ) {
			$html .= '<button type="button" class="lp-library-images-view-all">' . esc_html__( 'View all', 'learnpress-certificates' ) . '</button>';
		}

		$html .= '</div>';

		if ( ! empty( $display ) ) {
			$html .= '<div class="lp-library-images-grid">';
			$html .= $this->render_library_image_items( $display );
			$html .= '</div>';
		} else {
			$html .= '<div class="lp-library-images-empty"><p>' . esc_html__( 'No images found', 'learnpress-certificates' ) . '</p></div>';
		}

		$html .= '</div>';

		return $html;
	}

	public function html_library_images_full_view(): string {
		$images = $this->get_default_library_images();

		$html = '<div class="lp-inserter-library-area lp-library-full-view" style="display: none;">';

		$html .= '<div class="lp-library-full-header">';
		$html .= '<button type="button" class="lp-library-images-back">';
		$html .= '<span class="dashicons dashicons-arrow-left-alt2"></span>';
		$html .= '</button>';
		$html .= '<h4 class="lp-inserter-library__title">' . esc_html__( 'Images', 'learnpress-certificates' ) . '</h4>';
		$html .= '</div>';

		$html .= '<div class="lp-library-images-full-grid">';
		$html .= $this->render_library_image_items( $images );
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}

	public function get_default_library_images(): array {
		$images         = [];
		$templates_path = LP_ADDON_CERTIFICATES_PATH . '/assets/images/system-images';
		$templates_url  = \LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/system-images' );

		if ( ! is_dir( $templates_path ) ) {
			return $images;
		}

		$files = defined( 'GLOB_BRACE' ) ? glob( $templates_path . '/*.{png,jpg,jpeg,gif,webp}', \GLOB_BRACE ) : array_merge(
			glob( $templates_path . '/*.png' ),
			glob( $templates_path . '/*.jpg' ),
			glob( $templates_path . '/*.jpeg' ),
			glob( $templates_path . '/*.gif' ),
			glob( $templates_path . '/*.webp' )
		);

		foreach ( $files as $file ) {
			$filename = basename( $file );
			$images[] = [
				'name' => pathinfo( $filename, PATHINFO_FILENAME ),
				'url'  => $templates_url . '/' . $filename,
			];
		}

		return $images;
	}

	public function render_library_image_items( array $images ): string {
		$html = '';

		foreach ( $images as $image ) {
			$html .= sprintf(
				'<div class="lp-library-image-item" data-image-url="%s">
					<img src="%s" alt="%s" loading="lazy">
				</div>',
				esc_attr( $image['url'] ),
				esc_url( $image['url'] ),
				esc_attr( $image['name'] )
			);
		}

		return $html;
	}

	private function get_shapes_library(): array {
		$shapes_file = LP_ADDON_CERTIFICATES_PATH . '/config/library/shapes.php';

		if ( ! file_exists( $shapes_file ) ) {
			return [];
		}

		$shapes = include $shapes_file;

		return is_array( $shapes ) ? $shapes : [];
	}

	private function render_shape_preview( array $shape ): string {
		$type_layer  = $shape['type_layer'] ?? '';
		$fill        = $shape['fill'] ?? 'transparent';
		$stroke      = $shape['stroke'] ?? '';
		$strokeWidth = $shape['strokeWidth'] ?? 0;

		$style = '';
		if ( $fill && $fill !== 'transparent' ) {
			$style .= 'fill:' . esc_attr( $fill ) . ';';
		} else {
			$style .= 'fill:none;';
		}
		if ( $stroke ) {
			$style .= 'stroke:' . esc_attr( $stroke ) . ';stroke-width:' . esc_attr( $strokeWidth ) . ';';
		}

		$svg = '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">';

		switch ( $type_layer ) {
			case 'svg-circle':
				$svg .= '<circle cx="50" cy="50" r="40" style="' . $style . '" />';
				break;
			case 'svg-rect':
				$rx   = $shape['rx'] ?? 0;
				$svg .= '<rect x="10" y="20" width="80" height="60" rx="' . esc_attr( $rx ) . '" style="' . $style . '" />';
				break;
			case 'svg-triangle':
				$svg .= '<polygon points="50,10 90,90 10,90" style="' . $style . '" />';
				break;
			case 'svg-ellipse':
				$svg .= '<ellipse cx="50" cy="50" rx="45" ry="30" style="' . $style . '" />';
				break;
			case 'svg-line':
				$lineStyle = 'stroke:' . esc_attr( $stroke ?: '#333' ) . ';stroke-width:' . esc_attr( max( 3, $strokeWidth ) ) . ';';
				$svg      .= '<line x1="10" y1="50" x2="90" y2="50" style="' . $lineStyle . '" />';
				break;
			default:
				$svg .= '<rect x="10" y="10" width="80" height="80" style="' . $style . '" />';
				break;
		}

		$svg .= '</svg>';

		return $svg;
	}

	public function html_inserter_backgrounds(): string {
		$defaultColors = [
			'#FFFDF5',
			'#FDF6E3',
			'#FBF5F3',
			'#F9FAF8',
			'#F5F7FB',
			'#EAF2F8',
		];

		$colorSwatches = '';
		foreach ( $defaultColors as $color ) {
			$colorSwatches .= sprintf(
				'<button type="button" class="lp-background-color-swatch" data-color="%s" style="background-color: %s;" title="%s"></button>',
				esc_attr( $color ),
				esc_attr( $color ),
				esc_attr( $color )
			);
		}

		$colorInput = '<input type="text" class="lp-background-color-input cert-color-option" value="#ffffff" />';

		$html_main = '<div class="lp-inserter-backgrounds-area lp-background-main-view">';

		$html_main .= '<div class="lp-background-section">';
		$html_main .= '<h3 class="lp-background-section-title">' . esc_html__( 'General', 'learnpress-certificates' ) . '</h3>';
		$html_main .= '<div class="lp-background-color-grid">' . $colorSwatches . '</div>';
		$html_main .= '<div class="lp-background-color-picker-row">';
		$html_main .= $colorInput;
		$html_main .= '</div>';
		$html_main .= '</div>';

		$html_main .= $this->html_background_background_section();

		$html_main .= '</div>';

		$section = [
			'wrap'     => '<div class="lp-inserter-item backgrounds">',
			'main'     => $html_main,
			'wrap-end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_background_background_section(): string {
		$per_page = 20;
		$images   = get_posts(
			[
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'posts_per_page' => $per_page,
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		$html  = '<div class="lp-background-section lp-background-bg-section">';
		$html .= '<h3 class="lp-background-section-title">' . esc_html__( 'Media', 'learnpress-certificates' ) . '</h3>';

		if ( ! empty( $images ) ) {
			$html .= '<div class="lp-background-bg-grid">';
			$html .= $this->render_background_bg_items( $images );
			$html .= '</div>';
		} else {
			$html .= '<div class="lp-background-bg-empty"><p>' . esc_html__( 'No images found', 'learnpress-certificates' ) . '</p></div>';
		}

		$has_more = count( $images ) === $per_page;
		if ( $has_more ) {
			$html .= sprintf(
				'<button type="button" class="lp-background-bg-load-more" data-offset="%1$d" data-per-page="%2$d">%3$s</button>',
				esc_attr( count( $images ) ),
				esc_attr( $per_page ),
				esc_html__( 'Load more', 'learnpress-certificates' )
			);
		}

		$html .= '</div>';

		return $html;
	}

	public function render_background_bg_items( array $images ): string {
		$html = '';

		foreach ( $images as $image ) {
			$image_url = wp_get_attachment_image_url( $image->ID, 'thumbnail' );
			$full_url  = wp_get_attachment_image_url( $image->ID, 'full' );

			if ( $image_url ) {
				$html .= sprintf(
					'<div class="lp-background-bg-item" data-image-url="%s">
						<img src="%s" alt="%s" loading="lazy">
					</div>',
					esc_attr( $full_url ),
					esc_url( $image_url ),
					esc_attr( $image->post_title )
				);
			}
		}

		return $html;
	}

	public function html_inserter_templates(): string {
		$templates = [
			'vertical'     => [
				'name'  => esc_html__( 'Vertical (A4)', 'learnpress-certificates' ),
				'desc'  => esc_html__( '595x842 (px)', 'learnpress-certificates' ),
				'image' => \LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/templates/vertical.png' ),
			],
			'a4-landscape' => [
				'name'  => esc_html__( 'Landscape (A4)', 'learnpress-certificates' ),
				'desc'  => esc_html__( '842x595 (px)', 'learnpress-certificates' ),
				'image' => \LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/templates/landscape.png' ),
			],
		];

		$html_items = '';
		foreach ( $templates as $template_id => $template ) {
			$html_items .= sprintf(
				'<div class="lp-cert-template-apply-item" data-template="%s">
					<div class="lp-cert-template-apply-preview">
						<img src="%s" alt="%s" loading="lazy" />
					</div>
					<div class="lp-cert-template-apply-name">
						<div class="lp-cert-template-apply-title">%s</div>
						<span class="lp-cert-template-apply-desc">%s</span>
					</div>
				</div>',
				esc_attr( $template_id ),
				esc_url( $template['image'] ),
				esc_attr( $template['name'] ),
				esc_html( $template['name'] ),
				esc_html( $template['desc'] )
			);
		}

		$section = [
			'wrap'     => '<div class="lp-inserter-item templates">',
			'content'  => sprintf(
				'<div class="lp-inserter-templates-area" data-popup-title="%s" data-popup-text="%s" data-popup-confirm="%s" data-popup-cancel="%s">%s</div>',
				esc_attr__( 'Are you sure?', 'learnpress-certificates' ),
				esc_attr__( 'Applying this template will delete all current content on the Canvas.', 'learnpress-certificates' ),
				esc_attr__( 'Yes, apply it', 'learnpress-certificates' ),
				esc_attr__( 'Cancel', 'learnpress-certificates' ),
				$html_items
			),
			'wrap-end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_inserter_layers(): string {
		$html  = '<div class="lp-inserter-layers-area">';
		$html .= '<ul class="lp-layers-list" id="lp-layers-list"></ul>';
		$html .= '</div>';

		$section = [
			'wrap'     => '<div class="lp-inserter-item layers">',
			'content'  => $html,
			'wrap-end' => '</div>',
		];

		return Template::combine_components( $section );
	}


	public function html_canvas_header(): string {
		return $this->html_layer_option();
	}

	/**
	 * HTML options for layer.
	 * When choice type inserter, will show options for that layer.
	 *
	 * @return string
	 */
	public function html_layer_option(): string {
		return $this->html_text_toolbar() . $this->html_image_toolbar() . $this->html_svg_toolbar() . $this->html_canvas_toolbar();
	}

	public function html_position_panel(): string {
		$html  = '<div class="lp-cert-position-panel">';
		$html .= '<div class="lp-cert-position-panel__content">';
		$html .= '<div class="lp-cert-position-panel__pos-row">';
		$html .= '<div class="lp-cert-position-panel__pos-group">';
		$html .= '<span class="lp-cert-position-panel__label">X</span>';
		$html .= '<input type="number" class="lp-cert-position-panel__input lp-cert-position-panel__pos-x" value="0" step="1" />';
		$html .= '</div>';
		$html .= '<div class="lp-cert-position-panel__pos-group">';
		$html .= '<span class="lp-cert-position-panel__label">Y</span>';
		$html .= '<input type="number" class="lp-cert-position-panel__input lp-cert-position-panel__pos-y" value="0" step="1" />';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	public function html_image_toolbar(): string {
		$html  = '<div class="lp-cert-image-toolbar">';
		$html .= '<div class="lp-cert-image-toolbar__controls">';

		$html .= '<div class="lp-cert-image-toolbar__popup-wrapper">';
		$html .= '<button type="button" class="lp-cert-image-toolbar__trigger" data-popup="flip" title="' . esc_attr__( 'Flip', 'learnpress-certificates' ) . '">';
		$html .= '<i class="lp-cert-icon lp-cert-icon-flip-horizontal"></i>';
		$html .= '</button>';
		$html .= '<div class="lp-cert-image-toolbar__popup" data-popup-id="flip">';
		$html .= '<div class="lp-cert-image-toolbar__popup-header">';
		$html .= '<span class="lp-cert-image-toolbar__popup-title">' . esc_html__( 'Flip', 'learnpress-certificates' ) . '</span>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-image-toolbar__popup-content">';
		$html .= '<button type="button" class="lp-cert-image-toolbar__popup-btn lp-cert-image-toolbar__flip-y">';
		$html .= '<i class="lp-cert-icon lp-cert-icon-flip-vertical"></i>';
		$html .= '<span>' . esc_html__( 'Flip Vertical', 'learnpress-certificates' ) . '</span>';
		$html .= '</button>';
		$html .= '<button type="button" class="lp-cert-image-toolbar__popup-btn lp-cert-image-toolbar__flip-x">';
		$html .= '<i class="lp-cert-icon lp-cert-icon-flip-horizontal"></i>';
		$html .= '<span>' . esc_html__( 'Flip Horizontal', 'learnpress-certificates' ) . '</span>';
		$html .= '</button>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		// Round Corners
		$html .= '<div class="lp-cert-image-toolbar__popup-wrapper">';
		$html .= '<button type="button" class="lp-cert-image-toolbar__trigger" data-popup="radius" title="' . esc_attr__( 'Round Corners', 'learnpress-certificates' ) . '">';
		$html .= '<i class="lp-cert-icon lp-cert-icon-round-corners"></i>';
		$html .= '</button>';
		$html .= '<div class="lp-cert-image-toolbar__popup" data-popup-id="radius">';
		$html .= '<div class="lp-cert-image-toolbar__popup-header">';
		$html .= '<span class="lp-cert-image-toolbar__popup-title">' . esc_html__( 'Round Corners', 'learnpress-certificates' ) . '</span>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-image-toolbar__popup-content">';
		$html .= '<div class="lp-cert-image-toolbar__slider-row">';
		$html .= '<input type="range" class="lp-cert-image-toolbar__slider lp-cert-image-toolbar__corner-radius-slider" value="0" min="0" max="100" step="1" />';
		$html .= '<input type="number" class="lp-cert-image-toolbar__input lp-cert-image-toolbar__input--small lp-cert-image-toolbar__corner-radius" value="0" min="0" max="100" step="1" />';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		// Opacity
		$html .= '<div class="lp-cert-image-toolbar__popup-wrapper">';
		$html .= '<button type="button" class="lp-cert-image-toolbar__trigger" data-popup="opacity" title="' . esc_attr__( 'Opacity', 'learnpress-certificates' ) . '">';
		$html .= '<i class="lp-cert-icon lp-cert-icon-opacity"></i>';
		$html .= '</button>';
		$html .= '<div class="lp-cert-image-toolbar__popup" data-popup-id="opacity">';
		$html .= '<div class="lp-cert-image-toolbar__popup-header">';
		$html .= '<span class="lp-cert-image-toolbar__popup-title">' . esc_html__( 'Opacity', 'learnpress-certificates' ) . '</span>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-image-toolbar__popup-content">';
		$html .= '<div class="lp-cert-image-toolbar__slider-row">';
		$html .= '<input type="range" class="lp-cert-image-toolbar__slider lp-cert-image-toolbar__opacity" value="100" min="0" max="100" step="1" />';
		$html .= '<input type="number" class="lp-cert-image-toolbar__input lp-cert-image-toolbar__input--small lp-cert-image-toolbar__opacity-value" value="100" min="0" max="100" step="1" />';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="lp-cert-image-toolbar__popup-wrapper">';
		$html .= '<button type="button" class="lp-cert-image-toolbar__trigger" data-popup="resize" title="' . esc_attr__( 'Resize Image', 'learnpress-certificates' ) . '">';
		$html .= esc_html__( 'Resize Image', 'learnpress-certificates' );
		$html .= '</button>';
		$html .= '<div class="lp-cert-image-toolbar__popup" data-popup-id="resize">';
		$html .= '<div class="lp-cert-image-toolbar__popup-header">';
		$html .= '<span class="lp-cert-image-toolbar__popup-title">' . esc_html__( 'Resize Image', 'learnpress-certificates' ) . '</span>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-image-toolbar__popup-content">';
		$html .= '<button type="button" class="lp-cert-image-toolbar__popup-btn lp-cert-image-toolbar__resize-fit">';
		$html .= '<span>' . esc_html__( 'Fit Canvas', 'learnpress-certificates' ) . '</span>';
		$html .= '</button>';
		$html .= '<button type="button" class="lp-cert-image-toolbar__popup-btn lp-cert-image-toolbar__resize-mini">';
		$html .= '<span>' . esc_html__( 'Mini', 'learnpress-certificates' ) . '</span>';
		$html .= '</button>';
		$html .= '<button type="button" class="lp-cert-image-toolbar__popup-btn lp-cert-image-toolbar__resize-original">';
		$html .= '<span>' . esc_html__( '1:1 Original', 'learnpress-certificates' ) . '</span>';
		$html .= '</button>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<button type="button" class="lp-cert-image-toolbar__set-as-bg" title="' . esc_attr__( 'Set as Background', 'learnpress-certificates' ) . '">';
		$html .= esc_html__( 'Set as BG', 'learnpress-certificates' );
		$html .= '</button>';

		$html .= '<button type="button" class="lp-cert-image-toolbar__position-toggle" title="' . esc_attr__( 'Position', 'learnpress-certificates' ) . '">';
		$html .= esc_html__( 'Position', 'learnpress-certificates' );
		$html .= '</button>';

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	public function html_svg_toolbar(): string {
		$preset_colors = [
			'#000000',
			'#FFFFFF',
			'#039BE5',
			'#4CAF50',
			'#FF9800',
			'#F44336',
			'#9C27B0',
			'#00BCD4',
			'transparent',
		];

		$fill_color_presets = '';
		foreach ( $preset_colors as $color ) {
			$style               = $color === 'transparent'
				? 'background: linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%); background-size: 8px 8px; background-position: 0 0, 0 4px, 4px -4px, -4px 0px;'
				: 'background-color: ' . esc_attr( $color ) . ';';
			$fill_color_presets .= sprintf(
				'<button type="button" class="lp-cert-svg-toolbar__color-preset" data-color="%s" style="%s" title="%s"></button>',
				esc_attr( $color ),
				$style,
				$color === 'transparent' ? esc_attr__( 'Transparent', 'learnpress-certificates' ) : esc_attr( $color )
			);
		}

		$html  = '<div class="lp-cert-svg-toolbar">';
		$html .= '<div class="lp-cert-svg-toolbar__controls">';

		// Fill Color
		$html .= '<div class="lp-cert-svg-toolbar__popup-wrapper">';
		$html .= '<button type="button" class="lp-cert-svg-toolbar__fill-trigger" data-popup="fill" title="' . esc_attr__( 'Fill Color', 'learnpress-certificates' ) . '">';
		$html .= '<span class="lp-cert-svg-toolbar__fill-preview"></span>';
		$html .= '</button>';
		$html .= '<div class="lp-cert-svg-toolbar__popup" data-popup-id="fill">';
		$html .= '<div class="lp-cert-svg-toolbar__popup-header">';
		$html .= '<span class="lp-cert-svg-toolbar__popup-title">' . esc_html__( 'Fill Color', 'learnpress-certificates' ) . '</span>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-svg-toolbar__popup-content">';
		// Color section
		$html .= '<div class="lp-cert-svg-toolbar__popup-section">';
		$html .= '<span class="lp-cert-svg-toolbar__popup-label">' . esc_html__( 'Color', 'learnpress-certificates' ) . '</span>';
		$html .= '<div class="lp-cert-svg-toolbar__color-presets">' . $fill_color_presets . '</div>';
		$html .= '</div>';
		// Custom Color section
		$html .= '<div class="lp-cert-svg-toolbar__popup-section">';
		$html .= '<span class="lp-cert-svg-toolbar__popup-label">' . esc_html__( 'Custom Color', 'learnpress-certificates' ) . '</span>';
		$html .= '<div class="lp-cert-svg-toolbar__color-input-wrap">';
		$html .= '<input type="text" class="lp-cert-svg-toolbar__fill-color" value="#000000" />';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		// Stroke
		$html .= '<div class="lp-cert-svg-toolbar__popup-wrapper">';
		$html .= '<button type="button" class="lp-cert-svg-toolbar__trigger lp-cert-svg-toolbar__stroke-trigger" data-popup="stroke" title="' . esc_attr__( 'Stroke', 'learnpress-certificates' ) . '">';
		$html .= '<i class="lp-cert-icon lp-cert-icon-stroke"></i>';
		$html .= '</button>';
		$html .= '<div class="lp-cert-svg-toolbar__popup" data-popup-id="stroke">';
		$html .= '<div class="lp-cert-svg-toolbar__popup-header">';
		$html .= '<span class="lp-cert-svg-toolbar__popup-title">' . esc_html__( 'Stroke', 'learnpress-certificates' ) . '</span>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-svg-toolbar__popup-content">';
		$html .= '<div class="lp-cert-svg-toolbar__popup-row lp-cert-svg-toolbar__popup-row--inline">';
		$html .= '<span class="lp-cert-svg-toolbar__popup-label">' . esc_html__( 'Weight', 'learnpress-certificates' ) . '</span>';
		$html .= '<input type="number" class="lp-cert-svg-toolbar__input lp-cert-svg-toolbar__stroke-width" value="0" min="0" max="50" step="1" />';
		$html .= '</div>';
		$html .= '<div class="lp-cert-svg-toolbar__popup-row lp-cert-svg-toolbar__popup-row--inline">';
		$html .= '<span class="lp-cert-svg-toolbar__popup-label">' . esc_html__( 'Color', 'learnpress-certificates' ) . '</span>';
		$html .= '<div class="lp-cert-svg-toolbar__color-input-wrap">';
		$html .= '<input type="text" class="lp-cert-svg-toolbar__stroke-color" value="#000000" />';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		// Opacity
		$html .= '<div class="lp-cert-svg-toolbar__popup-wrapper">';
		$html .= '<button type="button" class="lp-cert-svg-toolbar__trigger" data-popup="opacity" title="' . esc_attr__( 'Opacity', 'learnpress-certificates' ) . '">';
		$html .= '<i class="lp-cert-icon lp-cert-icon-opacity"></i>';
		$html .= '</button>';
		$html .= '<div class="lp-cert-svg-toolbar__popup" data-popup-id="opacity">';
		$html .= '<div class="lp-cert-svg-toolbar__popup-header">';
		$html .= '<span class="lp-cert-svg-toolbar__popup-title">' . esc_html__( 'Opacity', 'learnpress-certificates' ) . '</span>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-svg-toolbar__popup-content">';
		$html .= '<div class="lp-cert-svg-toolbar__slider-row">';
		$html .= '<input type="range" class="lp-cert-svg-toolbar__slider lp-cert-svg-toolbar__opacity" value="100" min="0" max="100" step="1" />';
		$html .= '<input type="number" class="lp-cert-svg-toolbar__input lp-cert-svg-toolbar__input--small lp-cert-svg-toolbar__opacity-value" value="100" min="0" max="100" step="1" />';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<button type="button" class="lp-cert-svg-toolbar__position-toggle">';
		$html .= esc_html__( 'Position', 'learnpress-certificates' );
		$html .= '</button>';

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	public function html_text_toolbar(): string {
		$system_fonts = \LP_Certificate::system_fonts();
		$google_fonts = \LP_Certificate::google_fonts();

		$font_options = '';

		if ( ! empty( $google_fonts ) ) {
			$font_options .= '<optgroup label="' . esc_attr__( 'System fonts', 'learnpress-certificates' ) . '">';
			foreach ( $system_fonts as $name => $text ) {
				$font_options .= sprintf(
					'<option value="%1$s" style="font-family: %1$s;">%2$s</option>',
					esc_attr( $name ),
					esc_html( $text )
				);
			}
			$font_options .= '</optgroup>';

			$font_options .= '<optgroup label="' . esc_attr__( 'Google fonts', 'learnpress-certificates' ) . '">';
			foreach ( $google_fonts as $font ) {
				$font_options .= sprintf(
					'<option value="%1$s" style="font-family: %1$s;">%1$s</option>',
					esc_attr( $font )
				);
			}
			$font_options .= '</optgroup>';
		} else {
			foreach ( $system_fonts as $name => $text ) {
				$font_options .= sprintf(
					'<option value="%1$s" style="font-family: %1$s;">%2$s</option>',
					esc_attr( $name ),
					esc_html( $text )
				);
			}
		}

		$html  = '<div class="lp-cert-text-toolbar">';
		$html .= '<div class="lp-cert-text-toolbar__controls">';

		$html .= '<div class="lp-cert-text-toolbar__group" title="' . esc_attr__( 'Font Family', 'learnpress-certificates' ) . '">';
		$html .= '<select class="lp-cert-text-toolbar__select lp-cert-text-toolbar__font-family">' . $font_options . '</select>';
		$html .= '</div>';

		$html .= '<div class="lp-cert-text-toolbar__group lp-cert-text-toolbar__font-size-group" title="' . esc_attr__( 'Font Size', 'learnpress-certificates' ) . '">';
		$html .= '<button type="button" class="lp-cert-text-toolbar__stepper lp-cert-text-toolbar__stepper--minus" data-target="font-size" title="' . esc_attr__( 'Decrease Font Size', 'learnpress-certificates' ) . '">&#8722;</button>';
		$html .= sprintf(
			'<input type="number"
			class="lp-cert-text-toolbar__input lp-cert-text-toolbar__font-size" value="20" step="1" title="%s" />',
			esc_attr__( 'Font Size', 'learnpress-certificates' )
		);
		$html .= '<button type="button" class="lp-cert-text-toolbar__stepper lp-cert-text-toolbar__stepper--plus" data-target="font-size" title="' . esc_attr__( 'Increase Font Size', 'learnpress-certificates' ) . '">+</button>';
		$html .= '</div>';

		$html .= '<div class="lp-cert-text-toolbar__popup-wrapper">';
		$html .= '<button type="button" class="lp-cert-text-toolbar__color-trigger" data-popup="color" title="' . esc_attr__( 'Text Color', 'learnpress-certificates' ) . '">';
		$html .= '<span class="lp-cert-text-toolbar__color-preview" style="background-color: #333333;"></span>';
		$html .= '</button>';
		$html .= '<div class="lp-cert-text-toolbar__popup" data-popup-id="color">';
		$html .= '<div class="lp-cert-text-toolbar__popup-header">';
		$html .= '<span class="lp-cert-text-toolbar__popup-title">' . esc_html__( 'Text Color', 'learnpress-certificates' ) . '</span>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-text-toolbar__popup-content">';
		$html .= '<input type="text" class="lp-cert-text-toolbar__font-color" value="#333333" />';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="lp-cert-text-toolbar__group">';
		$html .= '<button type="button" class="lp-cert-text-toolbar__btn lp-cert-text-toolbar__bold" title="' . esc_attr__( 'Bold', 'learnpress-certificates' ) . '"><span class="dashicons dashicons-editor-bold"></span></button>';
		$html .= '<button type="button" class="lp-cert-text-toolbar__btn lp-cert-text-toolbar__italic" title="' . esc_attr__( 'Italic', 'learnpress-certificates' ) . '"><span class="dashicons dashicons-editor-italic"></span></button>';
		$html .= '<button type="button" class="lp-cert-text-toolbar__btn lp-cert-text-toolbar__underline" title="' . esc_attr__( 'Underline', 'learnpress-certificates' ) . '"><span class="dashicons dashicons-editor-underline"></span></button>';
		$html .= '<button type="button" class="lp-cert-text-toolbar__btn lp-cert-text-toolbar__linethrough" title="' . esc_attr__( 'Strikethrough', 'learnpress-certificates' ) . '"><i class="lp-cert-icon lp-cert-icon-text-linethrough"></i></button>';
		$html .= '</div>';

		$html .= '<div class="lp-cert-text-toolbar__group">';
		$html .= '<button type="button" class="lp-cert-text-toolbar__btn lp-cert-text-toolbar__align-left is-active" title="' . esc_attr__( 'Align Left', 'learnpress-certificates' ) . '"><span class="dashicons dashicons-editor-alignleft"></span></button>';
		$html .= '<button type="button" class="lp-cert-text-toolbar__btn lp-cert-text-toolbar__align-right" title="' . esc_attr__( 'Align Right', 'learnpress-certificates' ) . '"><span class="dashicons dashicons-editor-alignright"></span></button>';
		$html .= '<button type="button" class="lp-cert-text-toolbar__btn lp-cert-text-toolbar__align-center" title="' . esc_attr__( 'Align Center', 'learnpress-certificates' ) . '"><span class="dashicons dashicons-editor-aligncenter"></span></button>';
		$html .= '</div>';

		$html .= '<div class="lp-cert-text-toolbar__popup-wrapper">';
		$html .= '<button type="button" class="lp-cert-text-toolbar__trigger lp-cert-text-toolbar__icon-trigger" data-popup="advanced-settings" title="' . esc_attr__( 'Advanced Settings', 'learnpress-certificates' ) . '">';
		$html .= '<i class="lp-cert-icon lp-cert-icon-advanced-settings"></i>';
		$html .= '</button>';
		$html .= '<div class="lp-cert-text-toolbar__popup lp-cert-text-toolbar__settings-popup" data-popup-id="advanced-settings">';
		$html .= '<div class="lp-cert-text-toolbar__sidebar-title">' . esc_html__( 'Advanced Settings', 'learnpress-certificates' ) . '</div>';
		$html .= '<div class="lp-cert-text-toolbar__sidebar-row">';
		$html .= '<span class="lp-cert-text-toolbar__sidebar-label">' . esc_html__( 'Letter Spacing', 'learnpress-certificates' ) . '</span>';
		$html .= '<div class="lp-cert-text-toolbar__group lp-cert-text-toolbar__letter-spacing-group">';
		$html .= '<button type="button" class="lp-cert-text-toolbar__stepper lp-cert-text-toolbar__stepper--minus" data-target="letter-spacing" title="' . esc_attr__( 'Decrease Spacing', 'learnpress-certificates' ) . '">&#8722;</button>';
		$html .= '<input type="number" class="lp-cert-text-toolbar__input lp-cert-text-toolbar__letter-spacing" value="0" min="-100" max="500" step="10" />';
		$html .= '<button type="button" class="lp-cert-text-toolbar__stepper lp-cert-text-toolbar__stepper--plus" data-target="letter-spacing" title="' . esc_attr__( 'Increase Spacing', 'learnpress-certificates' ) . '">+</button>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-text-toolbar__sidebar-row">';
		$html .= '<span class="lp-cert-text-toolbar__sidebar-label">' . esc_html__( 'Line height', 'learnpress-certificates' ) . '</span>';
		$html .= '<div class="lp-cert-text-toolbar__group lp-cert-text-toolbar__line-height-group">';
		$html .= '<button type="button" class="lp-cert-text-toolbar__stepper lp-cert-text-toolbar__stepper--minus" data-target="line-height" title="' . esc_attr__( 'Decrease Line Height', 'learnpress-certificates' ) . '">&#8722;</button>';
		$html .= '<input type="number" class="lp-cert-text-toolbar__input lp-cert-text-toolbar__line-height" value="1.1" min="0.5" max="5" step="0.01" />';
		$html .= '<button type="button" class="lp-cert-text-toolbar__stepper lp-cert-text-toolbar__stepper--plus" data-target="line-height" title="' . esc_attr__( 'Increase Line Height', 'learnpress-certificates' ) . '">+</button>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<div class="lp-cert-text-toolbar__popup-wrapper">';
		$html .= '<button type="button" class="lp-cert-text-toolbar__trigger lp-cert-text-toolbar__icon-trigger lp-cert-text-toolbar__textwrap-trigger" data-popup="textwrap" title="' . esc_attr__( 'Text Wrap', 'learnpress-certificates' ) . '">';
		$html .= '<i class="lp-cert-icon lp-cert-icon-text-wrap"></i>';
		$html .= '</button>';
		$html .= '<div class="lp-cert-text-toolbar__popup" data-popup-id="textwrap">';
		$html .= '<div class="lp-cert-text-toolbar__sidebar-title">' . esc_html__( 'Text Wrap', 'learnpress-certificates' ) . '</div>';
		$html .= '<div class="lp-cert-text-toolbar__sidebar-row">';
		$html .= '<button type="button" class="lp-cert-text-toolbar__btn lp-cert-text-toolbar__convert-textbox">';
		$html .= '<i class="lp-cert-icon lp-cert-icon-text-wrap"></i>';
		$html .= '</button>';
		$html .= '<input type="number" class="lp-cert-text-toolbar__sidebar-input lp-cert-text-toolbar__textbox-width lp-hidden" value="300" min="10" step="1" title="' . esc_attr__( 'Textbox width', 'learnpress-certificates' ) . '" />';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<button type="button" class="lp-cert-text-toolbar__position-toggle" title="' . esc_attr__( 'Position', 'learnpress-certificates' ) . '">';
		$html .= esc_html__( 'Position', 'learnpress-certificates' );
		$html .= '</button>';

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	public function html_canvas_toolbar(): string {
		$html  = '<div class="lp-cert-canvas-toolbar">';
		$html .= '<div class="lp-cert-canvas-toolbar__controls">';

		$html .= '<div class="lp-cert-canvas-toolbar__popup-wrapper">';
		$html .= '<button type="button" class="lp-cert-canvas-toolbar__trigger lp-cert-canvas-toolbar__resize" title="' . esc_attr__( 'Resize', 'learnpress-certificates' ) . '">';
		$html .= esc_html__( 'Resize', 'learnpress-certificates' );
		$html .= '</button>';

		$html .= '<div class="lp-cert-resize-popup">';
		$html .= '<div class="lp-cert-resize-popup__header">';
		$html .= '<span class="lp-cert-resize-popup__title">' . esc_html__( 'Resize', 'learnpress-certificates' ) . '</span>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-resize-popup__body">';
		$html .= '<div class="lp-cert-resize-popup__inputs">';
		$html .= '<div class="lp-cert-resize-popup__input-group">';
		$html .= '<span class="lp-cert-resize-popup__label">' . esc_html__( 'W', 'learnpress-certificates' ) . '</span>';
		$html .= '<div class="lp-cert-resize-popup__input-wrapper">';
		$html .= '<button type="button" class="lp-cert-resize-popup__stepper lp-cert-resize-popup__stepper--minus" data-target="width">−</button>';
		$html .= '<input type="number" class="lp-cert-resize-popup__input lp-cert-canvas-toolbar__resize-width" placeholder="' . esc_attr__( 'Width', 'learnpress-certificates' ) . '" min="100" max="5000" />';
		$html .= '<button type="button" class="lp-cert-resize-popup__stepper lp-cert-resize-popup__stepper--plus" data-target="width">+</button>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-resize-popup__input-group">';
		$html .= '<span class="lp-cert-resize-popup__label">' . esc_html__( 'H', 'learnpress-certificates' ) . '</span>';
		$html .= '<div class="lp-cert-resize-popup__input-wrapper">';
		$html .= '<button type="button" class="lp-cert-resize-popup__stepper lp-cert-resize-popup__stepper--minus" data-target="height">−</button>';
		$html .= '<input type="number" class="lp-cert-resize-popup__input lp-cert-canvas-toolbar__resize-height" placeholder="' . esc_attr__( 'Height', 'learnpress-certificates' ) . '" min="100" max="5000" />';
		$html .= '<button type="button" class="lp-cert-resize-popup__stepper lp-cert-resize-popup__stepper--plus" data-target="height">+</button>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-resize-popup__presets">';
		$html .= '<label class="lp-cert-resize-popup__preset" data-width="595" data-height="842">';
		$html .= '<span class="lp-cert-resize-popup__preset-info">';
		$html .= '<span class="lp-cert-resize-popup__preset-name">' . esc_html__( 'A4 (Portrait)', 'learnpress-certificates' ) . '</span>';
		$html .= '<span class="lp-cert-resize-popup__preset-size">595 × 842 px</span>';
		$html .= '</span>';
		$html .= '<input type="radio" name="resize-preset" />';
		$html .= '</label>';
		$html .= '<label class="lp-cert-resize-popup__preset" data-width="842" data-height="595">';
		$html .= '<span class="lp-cert-resize-popup__preset-info">';
		$html .= '<span class="lp-cert-resize-popup__preset-name">' . esc_html__( 'A4 (Landscape)', 'learnpress-certificates' ) . '</span>';
		$html .= '<span class="lp-cert-resize-popup__preset-size">842 × 595 px</span>';
		$html .= '</span>';
		$html .= '<input type="radio" name="resize-preset" />';
		$html .= '</label>';
		$html .= '<label class="lp-cert-resize-popup__preset" data-width="612" data-height="792">';
		$html .= '<span class="lp-cert-resize-popup__preset-info">';
		$html .= '<span class="lp-cert-resize-popup__preset-name">' . esc_html__( 'US Letter (Portrait)', 'learnpress-certificates' ) . '</span>';
		$html .= '<span class="lp-cert-resize-popup__preset-size">612 × 792 px</span>';
		$html .= '</span>';
		$html .= '<input type="radio" name="resize-preset" />';
		$html .= '</label>';
		$html .= '<label class="lp-cert-resize-popup__preset" data-width="792" data-height="612">';
		$html .= '<span class="lp-cert-resize-popup__preset-info">';
		$html .= '<span class="lp-cert-resize-popup__preset-name">' . esc_html__( 'US Letter (Landscape)', 'learnpress-certificates' ) . '</span>';
		$html .= '<span class="lp-cert-resize-popup__preset-size">792 × 612 px</span>';
		$html .= '</span>';
		$html .= '<input type="radio" name="resize-preset" />';
		$html .= '</label>';
		$html .= '</div>';
		$html .= '<button type="button" class="lp-cert-resize-popup__apply">' . esc_html__( 'Resize', 'learnpress-certificates' ) . '</button>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<button type="button" class="lp-cert-canvas-toolbar__trigger lp-cert-canvas-toolbar__bg" title="' . esc_attr__( 'Background', 'learnpress-certificates' ) . '">';
		$html .= esc_html__( 'Background', 'learnpress-certificates' );
		$html .= '</button>';

		$html .= '<div class="lp-cert-canvas-toolbar__popup-wrapper">';
		$html .= '<button type="button" class="lp-cert-canvas-toolbar__trigger lp-cert-canvas-toolbar__bg-opacity" title="' . esc_attr__( 'Background opacity', 'learnpress-certificates' ) . '">';
		$html .= esc_html__( 'Background Opacity', 'learnpress-certificates' );
		$html .= '</button>';

		$html .= '<div class="lp-cert-opacity-popup">';
		$html .= '<div class="lp-cert-opacity-popup__header">';
		$html .= '<span class="lp-cert-opacity-popup__title">' . esc_html__( 'Background Opacity', 'learnpress-certificates' ) . '</span>';
		$html .= '</div>';
		$html .= '<div class="lp-cert-opacity-popup__body">';
		$html .= '<div class="lp-cert-opacity-popup__slider-wrapper">';
		$html .= '<input type="range" class="lp-cert-opacity-popup__slider" min="0" max="100" value="100" />';
		$html .= '<input type="number" class="lp-cert-opacity-popup__input" min="0" max="100" value="100" />';
		$html .= '<span class="lp-cert-opacity-popup__unit">%</span>';
		$html .= '</div>';
		$html .= '<p class="lp-cert-opacity-popup__no-image" style="display:none;">' . esc_html__( 'Opacity only works with background images. Please add a background image first.', 'learnpress-certificates' ) . '</p>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$buttons = [
			[
				'title' => esc_html__( 'Layer', 'learnpress-certificates' ),
				'class' => 'lp-cert-canvas-toolbar__layers',
			],
			// [
			//  'title' => esc_html__( 'Grid', 'learnpress-certificates' ),
			//  'class' => 'lp-cert-canvas-toolbar__grid',
			// ],
			// [
			//  'title' => esc_html__( 'Ruler', 'learnpress-certificates' ),
			//  'class' => 'lp-cert-canvas-toolbar__ruler',
			// ],
		];

		foreach ( $buttons as $btn ) {
			$html .= '<button type="button" class="lp-cert-canvas-toolbar__trigger ' . esc_attr( $btn['class'] ) . '" title="' . esc_attr( $btn['title'] ) . '">';
			$html .= esc_html( $btn['title'] );
			$html .= '</button>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}
}
