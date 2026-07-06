<?php
/**
 * Shared sortable-column helpers for admin Gradebook tables.
 *
 * @package LearnPress\Gradebook\TemplateHooks\Admin
 */

namespace LearnPress\Gradebook\TemplateHooks\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Provides sort-arg resolution and sortable <th> header definitions.
 *
 * Consumers define a SORTABLE_COLUMNS map:
 *   const SORTABLE_COLUMNS = array(
 *       '<field>' => array( 'order_by' => '<SQL>', 'default' => 'ASC'|'DESC' ),
 *   );
 *
 * @since 4.1.2
 */
trait SortableColumnsTrait {

	/**
	 * Resolve safe sort arguments from request args.
	 *
	 * Returns an empty field/order_by when no valid sort is requested, so callers
	 * can preserve the query's natural order until the user clicks a column.
	 *
	 * @param array  $args          Request args (sort_field, sort_direction).
	 * @param array  $columns       Sortable column map.
	 * @param string $default_field Optional default column key (empty = no default sort).
	 *
	 * @return array{field:string,direction:string,order_by:string}
	 */
	protected static function resolve_sort_args( array $args, array $columns, string $default_field = '' ): array {
		$sort_field = sanitize_key( $args['sort_field'] ?? '' );
		if ( ! isset( $columns[ $sort_field ] ) ) {
			$sort_field = isset( $columns[ $default_field ] ) ? $default_field : '';
		}

		if ( '' === $sort_field ) {
			return array(
				'field'     => '',
				'direction' => '',
				'order_by'  => '',
			);
		}

		$sort_direction = strtoupper( (string) ( $args['sort_direction'] ?? '' ) );
		if ( ! in_array( $sort_direction, array( 'ASC', 'DESC' ), true ) ) {
			$sort_direction = strtoupper( (string) ( $columns[ $sort_field ]['default'] ?? 'DESC' ) );
		}

		return array(
			'field'     => $sort_field,
			'direction' => $sort_direction,
			'order_by'  => (string) ( $columns[ $sort_field ]['order_by'] ?? '' ),
		);
	}

	/**
	 * Build a sortable table-header definition for shared/table.php.
	 *
	 * @param string $field       Column key (must exist in $columns).
	 * @param string $label       Column label.
	 * @param array  $sort        Resolved sort args from resolve_sort_args().
	 * @param array  $columns     Sortable column map.
	 * @param string $extra_class Extra <th> class names.
	 *
	 * @return array
	 */
	protected static function sortable_header( string $field, string $label, array $sort, array $columns, string $extra_class = '' ): array {
		$default      = strtolower( (string) ( $columns[ $field ]['default'] ?? 'desc' ) );
		$active_field = $sort['field'] ?? '';
		$active_dir   = strtolower( (string) ( $sort['direction'] ?? '' ) );

		$classes = array_filter(
			array(
				$extra_class,
				'lp-gradebook-sortable',
				$field === $active_field ? 'lp-gradebook-sorted-' . $active_dir : '',
			)
		);

		return array(
			'label'        => $label,
			'class'        => implode( ' ', $classes ),
			'label_html'   => sprintf( '<label>%s<span class="sort-indicator"></span></label>', esc_html( $label ) ),
			'sort_field'   => $field,
			'sort_default' => $default,
		);
	}
}
