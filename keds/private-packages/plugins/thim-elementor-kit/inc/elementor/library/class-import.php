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
	 *
	 * @return array   $data Elementor Imported Data.
	 * @since 2.0.0
	 */
	public function import( $post_id = 0, $data = array() ) {
		if ( ! empty( $post_id ) && ! empty( $data ) ) {
			$data = wp_json_encode( $data, true );
			$data = json_decode( $data, true );

			$el_data          = $data['content'] ?? [];
			$global_classes   = $data['global_classes'] ?? [];
			$global_variables = $data['global_variables'] ?? [];
			
			// import _elementor_data.
			$el_data = $this->replace_elements_ids( $el_data );
			$el_data = $this->process_export_import_content( $el_data, 'on_import' );

			// for v4 compatibility
			$el_data = apply_filters(
				'elementor/template_library/sources/local/import/elements',
				$el_data
			);

			update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $el_data ) ) );

			// get active Kit - where to save global_classes and global_variables.
			$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();

			// Import global_classes
			if ( ! empty( $global_classes ) ) {
				$existing = $kit->get_json_meta( '_elementor_global_classes' );

				$existing_items = $existing['items'] ?? [];
				$existing_order = $existing['order'] ?? [];

				$incoming_items = $global_classes['items'] ?? [];
				$incoming_order = $global_classes['order'] ?? [];

				foreach ( $incoming_items as $id => $class_def ) {
					if ( ! isset( $existing_items[ $id ] ) ) {
						$existing_items[ $id ] = $class_def;
					}
				}

				foreach ( $incoming_order as $id ) {
					if ( ! in_array( $id, $existing_order, true ) ) {
						$existing_order[] = $id;
					}
				}

				$kit->update_json_meta( '_elementor_global_classes', [
					'items' => $existing_items,
					'order' => $existing_order,
				] );
			}

			// Import global_variables
			if ( ! empty( $global_variables ) ) {
				$existing_vars = $kit->get_json_meta( '_elementor_global_variables' );

				$existing_data = $existing_vars['data'] ?? [];
				$incoming_data = $global_variables['data'] ?? [];

				foreach ( $incoming_data as $id => $var_def ) {
					if ( ! isset( $existing_data[ $id ] ) ) {
						$existing_data[ $id ] = $var_def;
					}
				}

				$kit->update_json_meta( '_elementor_global_variables', [
					'data'      => $existing_data,
					'watermark' => $global_variables['watermark'] ?? 0,
					'version'   => $global_variables['version'] ?? 1,
				] );
			}

			// Clear files manager cache after import.
			if ( Plugin::$instance->files_manager instanceof Files_Manager ) {
				Plugin::$instance->files_manager->clear_cache();
			}

			return $el_data;
		}

		return false;
	}
}
