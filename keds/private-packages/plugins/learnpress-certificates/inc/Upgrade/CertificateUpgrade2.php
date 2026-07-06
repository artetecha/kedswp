<?php

namespace LearnPress\Certificate\Upgrade;

use LearnPress\Certificate\Models\CertificatePostModel;
use LearnPress\Helpers\Singleton;
use LP_Request;
use LP_REST_Response;

class CertificateUpgrade2 extends CertificateUpgradeBase {
	use Singleton;

	public function init() {
		// TODO: Implement init() method.
	}

	public function get_total_count(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm_new ON pm_new.post_id = p.ID AND pm_new.meta_key = %s
				WHERE p.post_type = %s
				AND pm_new.meta_id IS NULL
				AND (
					EXISTS (
						SELECT 1 FROM {$wpdb->postmeta} pm1
						WHERE pm1.post_id = p.ID AND pm1.meta_key = %s AND pm1.meta_value != ''
					)
					OR EXISTS (
						SELECT 1 FROM {$wpdb->postmeta} pm2
						WHERE pm2.post_id = p.ID AND pm2.meta_key = %s AND pm2.meta_value != ''
					)
				)",
				'_lp_layer',
				'lp_cert',
				'_lp_cert_layers',
				'_lp_cert_template'
			)
		);
	}

	/**
	 * Handle upgrade process
	 *
	 * @param $params
	 *
	 * @return LP_REST_Response
	 */
	public function handle( $params = [] ): LP_REST_Response {
		$response = new LP_REST_Response();

		global $wpdb;

		$limit = 1;

		$item_processed = $_POST['processed'] ?? 0;
		$total_rows     = LP_Request::get_param( 'total', 0, 'int', 'post' );
		if ( $item_processed == 0 ) {
			$total_rows = $this->get_total_count();
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT p.ID as post_id
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm_new ON pm_new.post_id = p.ID AND pm_new.meta_key = %s
				WHERE p.post_type = %s
				AND pm_new.meta_id IS NULL
				AND (
					EXISTS (
						SELECT 1 FROM {$wpdb->postmeta} pm1
						WHERE pm1.post_id = p.ID AND pm1.meta_key = %s AND pm1.meta_value != ''
					)
					OR EXISTS (
						SELECT 1 FROM {$wpdb->postmeta} pm2
						WHERE pm2.post_id = p.ID AND pm2.meta_key = %s AND pm2.meta_value != ''
					)
				)
				ORDER BY p.ID ASC
				LIMIT %d",
				'_lp_layer',
				'lp_cert',
				'_lp_cert_layers',
				'_lp_cert_template',
				$limit
			)
		);

		$response->data->total = $total_rows;

		if ( empty( $rows ) ) {
			$response->status          = 'success';
			$response->data->done      = 1;
			$response->data->processed = $item_processed;

			return $response;
		}

		$count_converted = 0;
		foreach ( $rows as $row ) {
			$post_id   = (int) $row->post_id;
			$converted = $this->convert_certificate( $post_id );

			if ( $converted ) {
				++ $count_converted;
			}
		}

		$response->status          = 'success';
		$response->data->processed = $item_processed + $count_converted;

		return $response;
	}

	public function convert_certificate( int $post_id ): bool {
		$old_data = get_post_meta( $post_id, '_lp_cert_layers', true );
		if ( is_array( $old_data ) ) {
			$layers = $old_data;
		} elseif ( is_string( $old_data ) && ! empty( $old_data ) ) {
			$layers = maybe_unserialize( $old_data );
		} else {
			$layers = array();
		}

		if ( ! is_array( $layers ) ) {
			$layers = array();
		}

		$background = get_post_meta( $post_id, CertificatePostModel::META_KEY_TEMPLATE, true );
		if ( empty( $background ) ) {
			$background = '#ffffff';
		}

		$dimensions = $this->get_image_dimensions( $background );
		$width      = $dimensions['width'];
		$height     = $dimensions['height'];

		$new_layers = array();
		foreach ( $layers as $layer_id => $layer ) {
			if ( is_object( $layer ) ) {
				$layer = (array) $layer;
			}
			if ( ! is_array( $layer ) ) {
				continue;
			}

			$new_layer = $this->convert_layer( (string) $layer_id, $layer );
			if ( $new_layer ) {
				$new_layers[] = $new_layer;
			}
		}

		$new_data = array(
			'width'      => $width,
			'height'     => $height,
			'background' => $background,
			'layers'     => $new_layers,
		);

		$json_data = wp_json_encode( $new_data, JSON_UNESCAPED_UNICODE );
		$result    = update_post_meta( $post_id, CertificatePostModel::META_KEY_LAYER, $json_data );

		wp_cache_delete( $post_id, 'post_meta' );
		$certificateModel = CertificatePostModel::find( $post_id );
		if ( $certificateModel ) {
			$certificateModel->clean_caches();
		}

		return $result !== false;
	}

	private function get_image_dimensions( string $background ): array {
		$default = array(
			'width'  => 842,
			'height' => 595,
		);

		if ( empty( $background ) || $background[0] === '#' || strpos( $background, 'rgb' ) === 0 ) {
			return $default;
		}

		$file_path = $this->resolve_image_path( $background );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return $default;
		}

		$size = @getimagesize( $file_path );

		if ( $size && $size[0] > 0 && $size[1] > 0 ) {
			return array(
				'width'  => $size[0],
				'height' => $size[1],
			);
		}

		return $default;
	}

	private function resolve_image_path( string $background ): ?string {
		if ( strpos( $background, '/' ) === 0 && strpos( $background, '//' ) !== 0 ) {
			$file_path = ABSPATH . ltrim( $background, '/' );
			if ( file_exists( $file_path ) ) {
				return $file_path;
			}
		}

		$upload_dir  = wp_get_upload_dir();
		$upload_url  = $upload_dir['baseurl'];
		$upload_path = $upload_dir['basedir'];

		if ( strpos( $background, $upload_url ) !== false ) {
			$relative  = str_replace( $upload_url, '', $background );
			$file_path = $upload_path . $relative;
			if ( file_exists( $file_path ) ) {
				return $file_path;
			}
		}

		$site_url = site_url();
		if ( strpos( $background, $site_url ) !== false ) {
			$relative  = str_replace( $site_url, '', $background );
			$file_path = ABSPATH . ltrim( $relative, '/' );
			if ( file_exists( $file_path ) ) {
				return $file_path;
			}
		}

		return null;
	}

	private function convert_qr_layer( string $layer_id, array $old_layer ): array {
		$qr_size = isset( $old_layer['qr_size'] ) ? (int) $old_layer['qr_size'] : 100;

		return array(
			'id'                       => $layer_id,
			'type_layer'               => 'qr_code',
			'type'                     => 'FabricImage',
			'version'                  => '6.9.0',
			'src'                      => \LP_Addon_Certificates_Preload::$addon->get_plugin_url( 'assets/images/qrcode-default.png' ),
			'name'                     => '[QR_CODE]',
			'originX'                  => $old_layer['originX'] ?? 'center',
			'originY'                  => $old_layer['originY'] ?? 'center',
			'left'                     => (float) ( $old_layer['left'] ?? 0 ),
			'top'                      => (float) ( $old_layer['top'] ?? 0 ),
			'scaleX'                   => (float) ( $old_layer['scaleX'] ?? 1 ),
			'scaleY'                   => (float) ( $old_layer['scaleY'] ?? 1 ),
			'angle'                    => (float) ( $old_layer['angle'] ?? 0 ),
			'flipX'                    => ( $old_layer['flipX'] ?? false ) === 'true' || ( $old_layer['flipX'] ?? false ) === true,
			'flipY'                    => ( $old_layer['flipY'] ?? false ) === 'true' || ( $old_layer['flipY'] ?? false ) === true,
			'opacity'                  => (float) ( $old_layer['opacity'] ?? 1 ),
			'visible'                  => ( $old_layer['visible'] ?? 'true' ) !== 'false',
			'backgroundColor'          => '',
			'shadow'                   => null,
			'stroke'                   => null,
			'strokeWidth'              => 0,
			'paintFirst'               => 'fill',
			'globalCompositeOperation' => 'source-over',
			'skewX'                    => 0,
			'skewY'                    => 0,
			'cropX'                    => 0,
			'cropY'                    => 0,
			'qr_size'                  => $qr_size,
		);
	}

	private function convert_layer( string $layer_id, array $old_layer ): ?array {
		$old_type   = $old_layer['type'] ?? 'text';
		$field_type = $old_layer['fieldType'] ?? 'custom';

		$placeholder_map = array(
			'student-name'      => 'STUDENT_NAME',
			'course-name'       => 'COURSE_TITLE',
			'course-start-date' => 'COURSE_START_DATE',
			'course-end-date'   => 'COURSE_END_DATE',
			'current-time'      => 'TIME',
			'verified-link'     => 'QR_CODE',
			'custom'            => '',
		);

		$placeholder    = $placeholder_map[ $field_type ] ?? '';
		$is_placeholder = ! empty( $placeholder ) && $field_type !== 'custom';
		$is_custom      = $field_type === 'custom';

		if ( $field_type === 'verified-link' ) {
			return $this->convert_qr_layer( $layer_id, $old_layer );
		}

		if ( $is_custom || strtolower( $old_type ) === 'itext' ) {
			$type_layer  = 'text-edit';
			$fabric_type = 'IText';
		} else {
			$type_layer  = 'text-static';
			$fabric_type = 'Text';
		}

		$new_layer = array(
			'id'                       => $layer_id,
			'type_layer'               => $type_layer,
			'type'                     => $fabric_type,
			'version'                  => '6.9.0',
			'originX'                  => $old_layer['originX'] ?? 'center',
			'originY'                  => $old_layer['originY'] ?? 'center',
			'left'                     => (float) ( $old_layer['left'] ?? 0 ),
			'top'                      => (float) ( $old_layer['top'] ?? 0 ),
			'width'                    => (float) ( $old_layer['width'] ?? 100 ),
			'height'                   => (float) ( $old_layer['height'] ?? 24 ),
			'fill'                     => $old_layer['fill'] ?? 'rgb(0,0,0)',
			'stroke'                   => $old_layer['stroke'] ?? null,
			'strokeWidth'              => (int) ( $old_layer['strokeWidth'] ?? 0 ),
			'strokeDashArray'          => null,
			'strokeLineCap'            => $old_layer['strokeLineCap'] ?? 'butt',
			'strokeDashOffset'         => 0,
			'strokeLineJoin'           => $old_layer['strokeLineJoin'] ?? 'miter',
			'strokeUniform'            => false,
			'strokeMiterLimit'         => (int) ( $old_layer['strokeMiterLimit'] ?? 4 ),
			'scaleX'                   => (float) ( $old_layer['scaleX'] ?? 1 ),
			'scaleY'                   => (float) ( $old_layer['scaleY'] ?? 1 ),
			'angle'                    => (float) ( $old_layer['angle'] ?? 0 ),
			'flipX'                    => ( $old_layer['flipX'] ?? false ) === 'true' || ( $old_layer['flipX'] ?? false ) === true,
			'flipY'                    => ( $old_layer['flipY'] ?? false ) === 'true' || ( $old_layer['flipY'] ?? false ) === true,
			'opacity'                  => (float) ( $old_layer['opacity'] ?? 1 ),
			'shadow'                   => null,
			'visible'                  => ( $old_layer['visible'] ?? 'true' ) !== 'false',
			'backgroundColor'          => $old_layer['backgroundColor'] ?? '',
			'fillRule'                 => $old_layer['fillRule'] ?? 'nonzero',
			'paintFirst'               => 'fill',
			'globalCompositeOperation' => $old_layer['globalCompositeOperation'] ?? 'source-over',
			'skewX'                    => 0,
			'skewY'                    => 0,
			'text'                     => $is_placeholder ? "[{$placeholder}]" : ( ! empty( $old_layer['variable'] ) ? $old_layer['variable'] : ( $old_layer['text'] ?? '' ) ),
			'fontSize'                 => (int) ( $old_layer['fontSize'] ?? 24 ),
			'fontWeight'               => $old_layer['fontWeight'] ?? 'normal',
			'fontFamily'               => $old_layer['fontFamily'] ?? 'Helvetica',
			'fontStyle'                => $old_layer['fontStyle'] ?? 'normal',
			'lineHeight'               => (float) ( $old_layer['lineHeight'] ?? 1.16 ),
			'textAlign'                => $old_layer['originX'] ?? 'left',
			'charSpacing'              => 0,
			'underline'                => ( $old_layer['textDecoration'] ?? '' ) === 'underline',
			'overline'                 => ( $old_layer['textDecoration'] ?? '' ) === 'overline',
			'linethrough'              => ( $old_layer['textDecoration'] ?? '' ) === 'line-through',
			'textBackgroundColor'      => $old_layer['textBackgroundColor'] ?? '',
			'direction'                => 'ltr',
			'minWidth'                 => 20,
			'splitByGrapheme'          => false,
			'name'                     => $is_placeholder ? "[{$placeholder}]" : ( ! empty( $old_layer['variable'] ) ? $old_layer['variable'] : ( $old_layer['text'] ?? 'Text' ) ),
		);

		$date_field_types = array( 'current-time', 'course-start-date', 'course-end-date' );
		if ( in_array( $field_type, $date_field_types, true ) && ! empty( $old_layer['formatDate'] ) ) {
			$new_layer['formatDate'] = $old_layer['formatDate'];
		}

		return $new_layer;
	}
}
