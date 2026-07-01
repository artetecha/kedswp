<?php
/**
 * Template hooks Archive Package.
 *
 * @since 1.0.0
 * @version 1.0.1
 */
namespace LearnPress\Upsell\TemplateHooks;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Upsell\Package\Package;
use LP_Request;
use Throwable;

class ArchivePackage {
	use Singleton;

	public function init() {
		add_action( 'lp/upsell/layout/archive-package', [ $this, 'sections' ] );
	}

	public function sections() {
		// Hook old for theme, not use on the next version.
		$html_wrapper = apply_filters(
			'lp/upsell/archive-package/wrapper',
			[]
		);

		$section_header = [];
		if ( ! has_filter( 'lp/upsell/archive-package/wrapper' ) ) {
			ob_start();
			learn_press_breadcrumb();
			$html_breadcrumb = ob_get_clean();

			$section_header = [
				'wrapper'     => '<div class="learnpress-archive-package__header">',
				'page_title'  => sprintf( '<h1>%s</h1>', $this->page_title() ),
				'breadcrumb'  => $html_breadcrumb,
				'wrapper_end' => '</div>',
			];
		}

		$section_main = [
			'wrapper'     => '<div class="learnpress-archive-package__main">',
			'filter'      => $this->filter_sections(),
			'list'        => $this->list_package_section(),
			'pagination'  => $this->pagination(),
			'wrapper_end' => '</div>',
		];

		$sections = apply_filters(
			'learn-press/upsell/archive-package/sections',
			[
				'wrapper'             => '<div class="lp-content-area">',
				'wrapper_archive'     => '<div class="learnpress-archive-package">',
				'header'              => Template::combine_components( $section_header ),
				'main'                => Template::combine_components( $section_main ),
				'wrapper_archive_end' => '</div>',
				'wrapper_end'         => '</div>',
			]
		);

		$html = Template::instance()->nest_elements( $html_wrapper, Template::combine_components( $sections ) );

		echo $html;
	}

	/**
	 * Filter Section.
	 *
	 * @return string
	 */
	public function filter_sections(): string {
		global $wp_query;

		$data = array(
			'total_page'    => (int) $GLOBALS['wp_query']->max_num_pages,
			'current'       => max( 1, $GLOBALS['wp_query']->get( 'paged', 1 ) ),
			'per_page'      => $wp_query->get( 'posts_per_page' ),
			'total_package' => $wp_query->found_posts,
		);

		$section = apply_filters(
			'learn-press/upsell/archive-package/filter/section',
			[
				'wrapper'      => '<div class="learnpress-packages__top">',
				'result-count' => $this->html_result_count( $data ),
				'ordering'     => $this->html_ordering(),
				'search-form'  => $this->html_search_form(),
				'wrapper_end'  => '</div>',
			]
		);

		return Template::combine_components( $section );
	}

	/**
	 * List Package Section.
	 *
	 * @return string
	 */
	public function list_package_section(): string {
		$content = '';
		ob_start();
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				if ( LP_PACKAGE_CPT !== get_post_type( get_the_ID() ) ) {
					continue;
				}

				$package = new Package( get_the_ID() );
				echo $this->render_package( $package );
			}
		} else {
			echo $this->html_no_package_found();
		}
		$content = ob_get_clean();

		$section = apply_filters(
			'learn-press/upsell/archive-package/list-package',
			[
				'wrapper'     => '<ul class="learnpress-packages__archive">',
				'content'     => $content,
				'wrapper_end' => '</ul>',
			]
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML pagination.
	 *
	 * @return string
	 */
	public function pagination(): string {
		global $wp_query;

		$data_pagination = array(
			'total'    => $GLOBALS['wp_query']->max_num_pages,
			'current'  => max( 1, $GLOBALS['wp_query']->get( 'paged', 1 ) ),
			'base'     => esc_url_raw( str_replace( 999999999, '%#%', get_pagenum_link( 999999999, false ) ) ),
			'format'   => '',
			'per_page' => $wp_query->get( 'posts_per_page' ),
		);

		$pagination = paginate_links(
			apply_filters(
				'learn-press/upsell/archive-package/pagination',
				array(
					'base'      => $data_pagination['base'],
					'format'    => $data_pagination['format'],
					'add_args'  => false,
					'current'   => max( 1, $data_pagination['current'] ),
					'total'     => $data_pagination['total'],
					'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
					'next_text' => is_rtl() ? '&larr;' : '&rarr;',
					'type'      => 'list',
					'end_size'  => 3,
					'mid_size'  => 3,
				)
			)
		);

		$section = apply_filters(
			'learn-press/upsell/archive-package/pagination/section',
			[
				'wrapper'     => '<ul class="learnpress-packages__pagination">',
				'pagination'  => $pagination,
				'wrapper_end' => '</ul>',
			]
		);

		return Template::combine_components( $section );
	}

	/**
	 * Render HTML item Package.
	 *
	 * @param Package $package
	 *
	 * @return string
	 */
	public function render_package( Package $package ): string {
		$singlePackage = SinglePackage::instance();

		$meta_data = apply_filters(
			'learn-press/upsell/archive-package/item/meta/sections',
			[
				'wrapper'       => '<div class="learnpress-package__meta">',
				'count-courses' => $singlePackage->html_count_courses( $package ),
				'title'         => sprintf(
					'<a href="%s">%s</a>',
					esc_url_raw( get_permalink( $package->get_id() ) ),
					$singlePackage->html_title( $package, 'h4' )
				),
				'price'         => $singlePackage->html_price( $package ),
				'wrapper_end'   => '</div>',
			],
			$package
		);

		$section = apply_filters(
			'learn-press/upsell/archive-package/item/wrapper',
			[
				'wrapper'           => '<li>',
				'wrapper_inner'     => '<div class="learnpress-package__items">',
				'image'             => sprintf(
					'<a href="%s">%s</a>',
					esc_url_raw( get_permalink( $package->get_id() ) ),
					$singlePackage->html_image( $package )
				),
				'content'           => Template::combine_components( $meta_data ),
				'wrapper_inner_end' => '</div>',
				'wrapper_end'       => '</li>',
			],
			$package
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML result count.
	 *
	 * @param $data
	 *
	 * @return string
	 */
	public function html_result_count( $data ): string {
		$html = '';

		try {
			$total_package = $data['total_package'];
			$total_page    = $data['total_page'];
			$per_page      = $data['per_page'];
			$current       = $data['current'];

			$content = '';
			if ( 1 === intval( $total_package ) ) {
				$content = __( 'Showing the single result', 'learnpress-upsell' );

			} elseif ( $total_page == 1 ) {
				$content = sprintf(
					_n( 'Showing all %d result', 'Showing all %d results', $total_package, 'learnpress-upsell' ),
					$total_package
				);

			} else {
				$first = ( $per_page * $current ) - $per_page + 1;
				$last  = min( $total_package, $per_page * $current );

				$content = sprintf( _nx( 'Showing %1$d&ndash;%2$d of %3$d result', 'Showing %1$d&ndash;%2$d of %3$d results', $total_package, 'with first and last result', 'learnpress-upsell' ), $first, $last, $total_package );
			}

			if ( ! empty( $content ) ) {
				$html = '<span class="learnpress-packages__result-count">' . $content . '</span>';
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $html;
	}

	/**
	 * HTML ordering.
	 *
	 * @return string
	 */
	public function html_ordering(): string {
		$values = apply_filters(
			'learn-press/upsell/archive-package/ordering/values',
			array(
				'menu_order' => esc_html__( 'Default sorting', 'learnpress-upsell' ),
				'date'       => esc_html__( 'Sort by latest', 'learnpress-upsell' ),
				'price'      => esc_html__( 'Low to high', 'learnpress-upsell' ),
				'price_desc' => esc_html__( 'High to low', 'learnpress-upsell' ),
			)
		);

		$order_by = LP_Request::get_param( 'orderby', 'menu_order' );
		if ( ! array_key_exists( $order_by, $values ) ) {
			$order_by = current( array_keys( $values ) );
		}

		$content = '<select name="order_by">';
		foreach ( $values as $id => $name ) {
			$content .= sprintf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $id ),
				selected( $order_by, $id, false ),
				esc_html( $name )
			);
		}
		$content .= '</select>';

		$section = apply_filters(
			'learnpress/upsell/archive-package/ordering',
			[
				'wrapper'     => '<form class="learnpress-packages__ordering" method="get">',
				'post-type'   => sprintf(
					'<input type="hidden" name="post_type" value="%s">',
					esc_attr( LP_PACKAGE_CPT )
				),
				'content'     => $content,
				'wrapper_end' => '</form>',
			]
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML search form.
	 *
	 * @return string
	 */
	public function html_search_form(): string {
		$search_unique_id = uniqid( 'search-' );

		$section =
			apply_filters(
				'learnpress/upsell/archive-package/search-form',
				[
					'wrapper'     => '<form class="learnpress-packages__search" method="get" role="search">',
					'label'       => sprintf(
						'<label class="screen-reader-text" for="%s">%s</label>',
						esc_attr( $search_unique_id ),
						esc_html__( 'Search for:', 'learnpress-upsell' )
					),
					'search'      => sprintf(
						'<input type="search" id="%s" name="s" placeholder="%s" value="%s">',
						esc_attr( $search_unique_id ),
						esc_html__( 'Search packages&hellip;', 'learnpress-upsell' ),
						get_search_query()
					),
					'button'      => sprintf(
						'<button type="submit" value="%s">%s</button>',
						esc_attr( 'Search', 'learnpress-upsell' ),
						esc_html__( 'Search', 'learnpress-upsell' )
					),
					'post-type'   => sprintf(
						'<input type="hidden" name="post_type" value="%s">',
						esc_attr( LP_PACKAGE_CPT )
					),
					'wrapper_end' => '</form>',
				]
			);

		return Template::combine_components( $section );
	}

	/**
	 * HTML no package found.
	 *
	 * @return string
	 */
	public function html_no_package_found(): string {
		$section = apply_filters(
			'learnpress/upsell/archive-package/no-package-found',
			[
				'wrapper'     => '<p class="learnpress-packages__no-packages-found">',
				'content'     => sprintf( __( 'No packages found which match your selection.', 'learnpress-upsell' ) ),
				'wrapper_end' => '</p>',
			]
		);

		return Template::combine_components( $section );
	}

	/**
	 * Show title of Archive Package.
	 *
	 * @return mixed|void|null
	 */
	public function page_title() {
		if ( is_search() ) {
			$page_title = sprintf( __( 'Search results: &ldquo;%s&rdquo;', 'learnpress-upsell' ), get_search_query() );

			if ( get_query_var( 'paged' ) ) {
				$page_title .= sprintf( __( '&nbsp;&ndash; Page %s', 'learnpress-upsell' ), get_query_var( 'paged' ) );
			}
		} elseif ( is_tax() ) {
			$page_title = single_term_title( '', false );
		} else {
			$page_title = __( 'Package', 'learnpress-upsell' );
		}

		return apply_filters( 'lp/upsell/archive-package/page_title', $page_title );
	}
}
