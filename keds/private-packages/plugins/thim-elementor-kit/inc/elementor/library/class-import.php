<?php

namespace Thim_EL_Kit\Elementor\Library;

use Elementor\TemplateLibrary\Source_Local;
use Elementor\Plugin;
use Elementor\Core\Files\Manager as Files_Manager;

class Import extends Source_Local {

	/**
	 * Update post meta.
	 *
	 * @param integer $post_id Post ID.
	 * @param array $data Elementor Data.
	 * @param string $import_mode 'match_site'
	 *
	 * @return array $data Elementor Imported Data.
	 * @since 2.0.0
	 */
	public function import( $post_id = 0, $data = array(), $import_mode = 'match_site' ) {
		if ( empty( $post_id ) || empty( $data ) || ! get_post( $post_id ) ) {
			return false;
		}

		$data = wp_json_encode( $data );
		$data = json_decode( $data, true );

		if ( null === $data ) {
			return false;
		}

		$el_data = $data['content'] ?? [];

		// import _elementor_data.
		$el_data = $this->replace_elements_ids( $el_data );
		$el_data = $this->process_export_import_content( $el_data, 'on_import' );

		// for v4 compatibility
		$el_data = apply_filters(
			'elementor/template_library/sources/local/import/elements',
			$el_data
		);

		$source = \Elementor\Plugin::$instance->templates_manager->get_source( 'local' );

		if ( ! $source ) {
			$source = null;
		}

		$import_result = apply_filters(
			'elementor/template_library/import/process_content',
			[ 'content' => $el_data ],
			$import_mode,
			$data,
			$source
		);

		if ( is_array( $import_result ) && isset( $import_result['content'] ) ) {
			$el_data = $import_result['content'];
		}

		update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $el_data ) ) );

		// Clear files manager cache after import.
		if ( Plugin::$instance->files_manager instanceof Files_Manager ) {
			Plugin::$instance->files_manager->clear_cache();
		}

		return $el_data;
	}
}
