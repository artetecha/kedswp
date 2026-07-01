<?php

/**
 * Get template part (for templates like the shop-loop).
 *
 * @param $slug
 * @param string $name
 *
 * @return mixed|string
 */
function tp_portfolio_get_template_part( $slug, $name = '' ) {

	$plugin_path = untrailingslashit( plugin_dir_path( TP_PORTFOLIO_PLUGIN_FILE ) );

	$template = '';
	// Look in yourtheme/slug-name.php and yourtheme/portfolio/slug-name.php
	if ( $name ) {
		$template = locate_template( array( "{$slug}-{$name}.php", 'portfolio/' . "{$slug}-{$name}.php" ) );
	}
	// Get default slug-name.php
	if ( ! $template && $name && file_exists( $plugin_path . "/templates/{$slug}-{$name}.php" ) ) {
		$template = $plugin_path . "/templates/{$slug}-{$name}.php";
	}
	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/portfolio/slug.php
	if ( ! $template ) {
		$template = locate_template( array( "{$slug}.php", 'portfolio/' . "{$slug}.php" ) );
	}
	// Allow 3rd party plugin filter template file from their plugin
	$template = apply_filters( 'get_template_part', $template, $slug, $name );

	return $template;
}

/**
 * Get template type
 *
 * @param $name
 */
function tp_portfolio_get_template_type( $name ) {
	$template = '';

	// Look in yourtheme/pofolio/type/name.php
	if ( $name ) {
		$template = locate_template( "/portfolio/type/{$name}.php" );
	}

	// Get default name.php
	if ( ! $template && $name && file_exists( CORE_PLUGIN_PATH . "/templates/type/{$name}.php" ) ) {
		$template = CORE_PLUGIN_PATH . "/templates/type/{$name}.php";
	}

	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'tp_portfolio_get_template_type', $template, $name );

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Load a template with optional data.
 *
 * @param string $template_name Name of the template file (relative to the theme directory).
 * @param array $data Data to pass to the template (default is an empty array).
 * @param bool $return Whether to return the template output as a string (default is false).
 *
 * @return string|void Template output if $return is true; otherwise, it loads the template directly.
 */
function tp_portfolio_get_template( $name, $args = array() ) {

	if ( empty( $name ) ) {
		return;
	}

	// Tìm trong theme
	$template = locate_template( "portfolio/{$name}.php" );

	// Fallback plugin
	if ( ! $template ) {
		$core_template = CORE_PLUGIN_PATH . "/templates/{$name}.php";
		if ( file_exists( $core_template ) ) {
			$template = $core_template;
		}
	}

	if ( ! $template || ! file_exists( $template ) ) {
		return;
	}

	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args, EXTR_SKIP );
		include $template;
	} else {
		load_template( $template, false );
	}
}

if ( ! function_exists( 'portfolio_pagination' ) ) {
	function portfolio_pagination( $pages = '', $range = 2, $paged = 1 ) {
		global $wp_rewrite;
		if ( $GLOBALS['wp_query']->max_num_pages < 2 ) {
			return;
		}
		$paged        = get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1;
		$pagenum_link = html_entity_decode( get_pagenum_link() );
		$query_args   = array();
		$url_parts    = explode( '?', $pagenum_link );

		if ( isset( $url_parts[1] ) ) {
			wp_parse_str( $url_parts[1], $query_args );
		}

		$pagenum_link = esc_url( remove_query_arg( array_keys( $query_args ), $pagenum_link ) );
		$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';

		$format  = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
		$format .= $wp_rewrite->using_permalinks() ? user_trailingslashit( 'page/%#%', 'paged' ) : '?paged=%#%';
		// Set up paginated links.
		$links = paginate_links(
			array(
				'base'      => $pagenum_link,
				'format'    => $format,
				'total'     => $GLOBALS['wp_query']->max_num_pages,
				'current'   => $paged,
				'mid_size'  => 2,
				'add_args'  => array_map( 'urlencode', $query_args ),
				'prev_text' => __( '«' ),
				'next_text' => __( '»' ),
				'type'      => 'array',
			)
		);

		if ( $links ) : ?>
			<ul class="loop-pagination tp-loop-pagination">
				<?php
				foreach ( $links as $link ) {
					echo '<li>' . $link . '</li>';
				}
				?>
			</ul><!-- .pagination -->
			<?php
		endif;
	}
}
function is_video_embed( $embed_code ) {
	$pattern = '/<iframe.*?src=["\'].*?(youtube\.com|youtu\.be|vimeo\.com).*?["\'].*?>.*?<\/iframe>/i';
	if ( preg_match( $pattern, $embed_code ) ) {
		return true;
	}

	return false;
}

function thim_portfolio_check_ifarme_video( $sc_video, $video_type ) {
	if ( empty( $sc_video ) ) {
		return;
	}
	if ( $video_type == 'youtube' ) {
		if ( is_video_embed( $sc_video ) ) {
			return $sc_video;
		} else {
			return '<iframe  src="https://www.youtube.com/embed/' . $sc_video . '"></iframe>';
		}
	} elseif ( $video_type == 'vimeo' ) {
		if ( is_video_embed( $sc_video ) ) {
			return $sc_video;
		} else {
			return '<iframe src="https://vimeo.com/' . $sc_video . '"></iframe>';
		}
	}
}

function thim_portfolio_taxonomy( $taxonomies = 'portfolio_category' ) {
	$terms = get_the_terms( get_the_ID(), $taxonomies );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$taxonomies_links = array_map(
			function ( $term ) {
				return sprintf(
					'<a href="%s">%s</a>',
					esc_url( get_term_link( $term ) ),
					esc_html( $term->name )
				);
			},
			$terms
		);

		return $taxonomies_links;
	}

	return false;
}

if ( ! function_exists( 'thim_portfolio_taxonomy_archive_header' ) ) {
	function thim_portfolio_taxonomy_archive_header() {
		echo '<div class="tp-portfolio-header">';
		the_archive_title( '<h1 class="page-title">', '</h1>' );
		the_archive_description( '<div class="portfolio-description">', '</div>' );
		echo '</div>';
	}
}

if ( ! function_exists( 'thim_portfolio_start_loop' ) ) {
	function thim_portfolio_start_loop() {
		$column = apply_filters( 'thim_portfolio_option_column', 3 );
		echo '<div class="archive-portfolios columns-' . $column . '">';
	}
}

if ( ! function_exists( 'thim_portfolio_end_loop' ) ) {
	function thim_portfolio_end_loop() {
		echo '</div>';
	}
}

if ( ! function_exists( 'thim_no_portfolio_found' ) ) {
	function thim_no_portfolio_found() {
		printf( '<div class="no-portfolio-message">%s</div>', esc_html__( 'No portfolio were found matching your selection.', 'tp-portfolio' ) );
	}
}
if ( ! function_exists( 'thim_portfolio_template_item_start' ) ) {
	function thim_portfolio_template_item_start() {
		echo '<div class="portfolio-item">';
	}
}
if ( ! function_exists( 'thim_portfolio_template_item_end' ) ) {
	function thim_portfolio_template_item_end() {
		echo '</div>';
	}
}
if ( ! function_exists( 'thim_portfolio_template_loop_thumbnail' ) ) {
	function thim_portfolio_template_loop_thumbnail() {
		$current_filter = current_filter();

		$post_id    = get_the_ID();
		$image_size = apply_filters( 'thim_portfolio_thumbnail_size', array( 500, 290 ) );
		if ( is_single() && $current_filter == 'thim_portfolio_before_single_content' ) {
			$image_size = 'full';
		}
		$html_thumbnail = '';

		// Get post thumbnail
		if ( has_post_thumbnail( $post_id ) ) {
			$thumbnail_id   = get_post_thumbnail_id( $post_id );
			$thumbnail_args = array(
				'src'   => wp_get_attachment_image( $thumbnail_id, $image_size ),
				'title' => get_the_title( $post_id ),
			);

			$html_thumbnail = is_single() ? $thumbnail_args['src'] : sprintf(
				'<a href="%s" title="%s">%s</a>',
				get_the_permalink( $post_id ),
				esc_attr( $thumbnail_args['title'] ),
				$thumbnail_args['src']
			);
		}

		// Handle portfolio types
		$select_portfolio = get_post_meta( $post_id, 'selectPortfolio', true );
		switch ( $select_portfolio ) {
			case 'portfolio_type_image':
				if ( $slides = get_post_meta( $post_id, 'project_item_slides', true ) ) {
					$html_thumbnail = is_single() ? wp_get_attachment_image( $slides, $image_size ) : sprintf(
						'<a href="%s" title="%s">%s</a>',
						get_the_permalink( $post_id ),
						esc_attr( $thumbnail_args['title'] ),
						wp_get_attachment_image( $slides, $image_size )
					);
				}
				break;
			case 'portfolio_type_gallery':
				if ( $gallerys = get_post_meta( $post_id, 'project_item_slides', false ) ) {
					if ( is_array( $gallerys ) && ! empty( $gallerys ) ) {
						$gallerys = array_map(
							function ( $item ) {
								return sprintf(
									'<div class="columns"><a href="%s" data-rel="prettyPhoto[gallery]">%s</a></div>',
									wp_get_attachment_image_url( $item, 'full' ),
									wp_get_attachment_image( $item, array( 480, 320 ) )
								);
							},
							$gallerys
						);

						$html_thumbnail = sprintf(
							'<div class="portfolio-gallery">%s</div>',
							implode( '', $gallerys )
						);
					}
				}
				break;

			case 'portfolio_type_slider':
				if ( $sliders = get_post_meta( $post_id, 'portfolio_sliders', false ) ) {
					if ( is_array( $sliders ) && ! empty( $sliders ) ) {
						wp_enqueue_script( 'slick' );
						wp_enqueue_script( 'tp-portfolio-scripts' );
						wp_enqueue_style( 'slick' );

						$slides = array_map(
							function ( $item ) use ( $image_size ) {
								if ( substr( $item, 0, 2 ) == 'v.' ) {
									return sprintf(
										'<div class="slider-item"><div class="video-content"><iframe src="http://player.vimeo.com/video/%s?title=0&amp;byline=0&amp;portrait=0&amp;color=ffffff" width="auto" height="500px" frameborder="0"></iframe></div></div>',
										substr( $item, 2 )
									);
								} elseif ( substr( $item, 0, 2 ) == 'y.' ) {
									return sprintf(
										'<div class="slider-item"><div class="video-content"><iframe title="YouTube video player" class="youtube-player" type="text/html" width="auto" height="500px" src="https://www.youtube.com/embed/%s" frameborder="0"></iframe></div></div>',
										substr( $item, 2 )
									);
								} else {
									return sprintf(
										'<div class="slider-item">%s</div>',
										wp_get_attachment_image( $item, $image_size )
									);
								}
							},
							$sliders
						);

						$html_thumbnail = sprintf(
							'<div class="portfolio-sliders">%s</div>',
							implode( '', $slides )
						);
					}
				}
				break;
			case 'portfolio_type_video':
				if ( $video = get_post_meta( $post_id, 'project_video_embed', true ) ) {
					$html_thumbnail = sprintf(
						'<div class="video-content">%s</div>',
						thim_portfolio_check_ifarme_video(
							$video,
							get_post_meta( $post_id, 'project_video_type', true )
						)
					);
				}
				break;
		}

		if ( $html_thumbnail ) {
			printf(
				'<div class="portfolio-thumbnail">%s</div>',
				apply_filters( 'thim_portfolio_html_thumbnail', $html_thumbnail )
			);
		}
	}
}
if ( ! function_exists( 'thim_portfolio_template_title_start' ) ) {
	function thim_portfolio_template_title_start() {
		echo '<div class="inner-content">';
	}
}
if ( ! function_exists( 'thim_portfolio_template_title_end' ) ) {
	function thim_portfolio_template_title_end() {
		echo '</div>';
	}
}
if ( ! function_exists( 'thim_portfolio_template_loop_title' ) ) {
	function thim_portfolio_template_loop_title() {
		printf(
			'<h3 class="title"><a href="%s" title="%s">%s</a></h3>',
			esc_url( get_permalink( get_the_ID() ) ),
			esc_attr( get_the_title( get_the_ID() ) ),
			get_the_title( get_the_ID() )
		);
	}
}
if ( ! function_exists( 'thim_portfolio_template_loop_meta' ) ) {
	function thim_portfolio_template_loop_meta() {
		$html = '';
		if ( thim_portfolio_taxonomy() ) {
			$html .= sprintf( '<div class="cat_portfolio">%s</div>', implode( ', ', thim_portfolio_taxonomy() ) );
		}
		$html .= sprintf( '<div class="date-time">%s</div>', get_the_date() );
		printf( '<div class="meta-data">%s</div>', $html );
	}
}
if ( ! function_exists( 'thim_portfolio_template_loop_read_more' ) ) {
	function thim_portfolio_template_loop_read_more() {
		printf(
			'<div class="read-more"><a href="%s" title="%s">%s</a></div>',
			esc_url( get_permalink( get_the_ID() ) ),
			esc_attr( get_the_title( get_the_ID() ) ),
			esc_html__( 'Read more', 'tp-portfolio' )
		);
	}
}
if ( ! function_exists( 'thim_portfolio_template_single_title' ) ) {
	function thim_portfolio_template_single_title() {
		the_title( '<h1 class="entry-title">', '</h1>' );
	}
}
if ( ! function_exists( 'thim_portfolio_template_single_meta' ) ) {
	function thim_portfolio_template_single_meta() {
		$post_id   = get_the_ID();
		$meta_data = array(
			'repeater'    => get_post_meta( $post_id, 'portfolio_repeater', true ),
			'date_end'    => get_post_meta( $post_id, 'portfolio_date_end', true ),
			'file_upload' => get_post_meta( $post_id, 'portfolio_file_upload', true ),
		);

		$terms = get_the_terms( $post_id, 'portfolio_category' );

		// Check if any meta data exists
		if ( array_filter( $meta_data ) ) {
			echo '<div class="portfolio-meta-data"><ul class="list-item">';
			// Display categories
			if ( thim_portfolio_taxonomy() ) {
				printf(
					'<li><div class="label">%s</div><div class="cat_portfolio value">%s</div></li>',
					esc_html__( 'Category:', 'tp-portfolio' ),
					implode( ', ', thim_portfolio_taxonomy() )
				);
			}
			// Display end date
			if ( ! empty( $meta_data['date_end'] ) ) {
				printf(
					'<li><div class="label">%s</div><div class="date value">%s</div></li>',
					esc_html__( 'Finish date', 'tp-portfolio' ),
					esc_html( $meta_data['date_end'] )
				);
			}

			// Display repeater fields
			if ( ! empty( $meta_data['repeater'] ) && is_array( $meta_data['repeater'] ) ) {
				foreach ( $meta_data['repeater'] as $item ) {
					if ( ! empty( $item['title'] ) && ! empty( $item['description'] ) ) {
						printf(
							'<li><div class="label">%s</div><div class="item value">%s</div></li>',
							esc_html( $item['title'] ),
							esc_html( $item['description'] )
						);
					}
				}
			}

			echo '</ul></div>';
		}
	}
}
if ( ! function_exists( 'thim_portfolio_template_single_content' ) ) {
	function thim_portfolio_template_single_content() {
		echo '<div class="portfolio-content">';
		the_content();
		echo '	</div>';
	}
}
if ( ! function_exists( 'thim_portfolio_template_single_content_pdf' ) ) {
	function thim_portfolio_template_single_content_pdf() {
		$type     = get_post_meta( get_the_ID(), 'selectPortfolio', true );
		$pdf_file = get_post_meta( get_the_ID(), 'portfolio_file_upload', true );
		if ( ! empty( $pdf_file ) && $type == 'portfolio_pdf' ) {
			echo '<div class="pdf-content">';
			echo '<iframe src="' . esc_url( $pdf_file['url'] ) . '" width="100%" height="800px"></iframe>';
			echo '</div>';
		}
	}
}

if ( ! function_exists( 'thim_portfolio_template_single_meta_footer' ) ) {
	function thim_portfolio_template_single_meta_footer() {
		$pdf_file = get_post_meta( get_the_ID(), 'portfolio_file_upload', true );
		if ( ! empty( $pdf_file ) ) {
			echo '<div class="pdf-download">';
			echo '<div class="label">' . __( 'File attached:', 'tp-portfolio' ) . '</div>';
			echo '<div class="value"><a href="' . $pdf_file['url'] . '" download>' . $pdf_file['name'] . '</a></div>';
			echo '</div>';
		}
		// show tag
		$tag = thim_portfolio_taxonomy( 'portfolio_tag' );
		if ( $tag ) {
			printf(
				'<div class="entry-footer"><div class="label">%s</div><div class="tag-portfolio">%s</div></div>',
				esc_html__( 'Tags:', 'tp-portfolio' ),
				implode( ', ', $tag )
			);
		}
	}
}

if ( ! function_exists( 'thim_portfolio_template_single_comment' ) ) {
	function thim_portfolio_template_single_comment() {
		//       If comments are open or we have at least one comment, load up the comment template
		if ( comments_open() || '0' != get_comments_number() ) :
			comments_template();
		endif;
	}
}

if ( ! function_exists( 'thim_portfolio_output_related' ) ) {
	function thim_portfolio_output_related() {
		$limit   = apply_filters( 'thim_portfolio_related_limit', 3 );
		$heading = apply_filters( 'thim_related_portfolio_heading', __( 'Related Projects', 'tp-portfolio' ) );
		$column  = apply_filters( 'thim_portfolio_option_column', 3 );

		$args          = array(
			'posts_per_page' => $limit,
			'post_type'      => 'portfolio',
			'post_status'    => 'publish',
			'post__not_in'   => array( get_the_ID() ),
			'tax_query'      => array(
				array(
					'taxonomy' => 'portfolio_category',
					'field'    => 'id',
					'terms'    => wp_get_post_terms( get_the_ID(), 'portfolio_category', array( 'fields' => 'ids' ) ),
					'operator' => 'IN',
				),
			),
		);
		$query_related = new WP_Query( $args );

		if ( $query_related->have_posts() ) {
			echo '<div class="related-portfolio">';

			if ( $heading ) {
				printf( '<h3 class="related-title">%s</h3>', esc_html__( $heading ) );
			}

			echo '<div class="archive-portfolios columns-' . $column . '">';

			do_action('thim_related_portfolio_before_loop' );

			while ( $query_related->have_posts() ) :
				$query_related->the_post();

				tp_portfolio_get_template( 'content-portfolio' );

			endwhile;

			do_action('thim_related_portfolio_after_loop');

			echo '</div>';

			echo '</div>';
		}
		wp_reset_postdata();
	}
}
