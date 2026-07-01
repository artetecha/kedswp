<?php
$class = '';
global $post, $portfolio_data;
$category     = empty( $instance['portfolio_category'] ) ? array() : $instance['portfolio_category'];
$filter_hiden = $instance['filter_hiden'] ? $instance['filter_hiden'] : false;
$class       .= isset( $instance['style-item'] ) ? ' ' . $instance['style-item'] : '';
$class       .= isset( $instance['gutter'] ) && $instance['gutter'] ? ' gutter' : '';
$class       .= isset( $instance['item_size'] ) ? ' ' . $instance['item_size'] : '';
$class       .= isset( $instance['paging'] ) ? ' ' . $instance['paging'] : '';

$paging         = isset( $instance['paging'] ) ? $instance['paging'] : '';
$item_size      = isset( $instance['item_size'] ) ? $instance['item_size'] : '';
$gutter         = isset( $instance['gutter'] ) ? $instance['gutter'] : '';
$column         = isset( $instance['column'] ) ? $instance['column'] : '';
$num_per_view   = isset( $instance['num_per_view'] ) ? $instance['num_per_view'] : '';
$pf_layout      = isset( $instance['style-item'] ) ? ' ' . $instance['style-item'] : 'style01';
$portfolio_taxs = array();

if ( $category == 'all' ) {
	$category = array();
}
if ( isset( $category[''] ) && is_array( $category[''] ) ) {
	$category = $category[''];

}

// Filter position
$css_filter_position = isset( $instance['filter_position'] ) ? ' style="text-align:' . $instance['filter_position'] . ';"' : '';

// Paging
if ( $paging == 'paging' ) {
	if ( is_front_page() ) {
		$paged = ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1;
	} else {
		$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
	}
	if ( $num_per_view != '' ) { // overide number
		$argss = array(
			'post_type'      => 'portfolio',
			'posts_per_page' => $num_per_view,
			'paged'          => $paged,
		);
	} else { // using number in config
		$argss = array(
			'post_type' => 'portfolio',
			'paged'     => $paged,
		);
	}
} else {
	if ( $paging == 'limit' ) {
		if ( $num_per_view != '' ) { // overide number
			$argss = array(
				'post_type'      => 'portfolio',
				'posts_per_page' => $num_per_view,
			);
		} else { // using number in config
			$argss = array(
				'post_type' => 'portfolio',
			);
		}
	} else {
		if ( $paging == 'infinite_scroll' ) {
			if ( is_front_page() ) {
				$paged = ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1;
			} else {
				$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			}
			if ( $num_per_view != '' ) { // overide number
				$argss = array(
					'post_type'      => 'portfolio',
					'posts_per_page' => $num_per_view,
					'paged'          => $paged,
				);
			} else { // using number in config
				$argss = array(
					'post_type' => 'portfolio',
					'paged'     => $paged,
				);
			}
		} else { // show all post
			$argss = array(
				'post_type'      => 'portfolio',
				'posts_per_page' => - 1,
			);
		}
	}
}
if ( ( is_array( $category ) && ! empty( $category ) ) || ( ! is_array( $category ) && $category ) ) {
	$argss['tax_query'][] = array(
		'taxonomy' => 'portfolio_category',
		'field'    => 'term_id',
		'terms'    => $category,
	);
}

$argss['post_status'] = 'publish';

$argss = apply_filters( 'thim_query_portfolio', $argss );

$gallery = new WP_Query( $argss );

$number_total = max( $gallery->post_count, $paging );
if ( is_array( $gallery->posts ) && ! empty( $gallery->posts ) && $gallery->post_count ) {
	foreach ( $gallery->posts as $gallery_post ) {
		// get_the_terms() uses the object term cache pre-loaded by WP_Query (update_post_term_cache=true).
		$post_taxs = get_the_terms( $gallery_post->ID, 'portfolio_category' );
		if ( is_array( $post_taxs ) && ! empty( $post_taxs ) ) {
			foreach ( $post_taxs as $post_tax ) {
				if ( is_array( $category ) && ! empty( $category ) && ( in_array( $post_tax->term_id, $category ) || in_array( $post_tax->parent, $category ) ) ) {
					$portfolio_taxs[ urldecode( $post_tax->slug ) ] = $post_tax->name;
				}
				if ( empty( $category ) || ! isset( $category ) ) {
					$portfolio_taxs[ urldecode( $post_tax->slug ) ] = $post_tax->name;
				}
			}
		}
	}
} else {
	return;
}

wp_enqueue_script( 'thim-portfolio-appear' );
wp_enqueue_script( 'thim-portfolio-widget' );
wp_enqueue_style( 'thim-portfolio' );
?>
<div
	class="wapper_portfolio<?php echo esc_attr( $class ); ?>">
	<?php if ( $filter_hiden != true ) { ?>
		<div class="portfolio-tabs-wapper filters"<?php echo ent2ncr( $css_filter_position ); ?> >
			<ul class="portfolio-tabs">
				<?php if ( empty( $category ) ) { ?>
					<li><a href class="filter active"
							data-filter="*"><?php echo esc_html__( 'All', 'eduma' ); ?></a>
					</li>
					<?php if ( $portfolio_taxs ) : ?>
						<?php foreach ( $portfolio_taxs as $portfolio_tax_slug => $portfolio_tax_name ) : ?>
							<li>
								<a class="filter" href
									data-filter=".<?php echo ent2ncr( $portfolio_tax_slug ); ?>"><?php echo ent2ncr( $portfolio_tax_name ); ?></a>
							</li>
						<?php endforeach; ?>
						<?php
					endif;
				} else {
					$term = get_term( $category, 'portfolio_category' );
					$name = $term->name;
					$slug = $term->slug;
					?>
					<li>
						<a class="filter active" href data-filter=".<?php echo $slug; ?>"><?php echo $name; ?></a>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
	<?php } ?>

	<div class="portfolio_column">
		<ul class="content_portfolio <?php echo esc_attr( $instance['style-item'] ); ?>">
			<?php
			while ( $gallery->have_posts() ) :
				$gallery->the_post();

				$post_id      = get_the_ID();
				$class_column = $column . '-col';
				// Get post meta once.
				$feature_images = get_post_meta( $post_id, 'feature_images', true );
				$bk_ef          = get_post_meta( $post_id, 'thim_portfolio_bg_color_ef', true );
				$bg             = $bk_ef ? sprintf( 'style="background-color:%s;"', esc_attr( $bk_ef ) ) : '';

				// Size mapping for cleaner code.
				$size_map = array(
					'size11' => array(
						'image' => 'portfolio_size11',
						'class' => '',
					),
					'size12' => array(
						'image' => 'portfolio_size12',
						'class' => ' height_large',
					),
					'size21' => array(
						'image' => 'portfolio_size21',
						'class' => ' item_large',
					),
					'size22' => array(
						'image' => 'portfolio_size22',
						'class' => ' height_large item_large',
					),
				);

				$images_size  = 'portfolio_size11';
				$item_classes = $class_size = '';

				if ( 'multigrid' === $item_size ) {
					if ( isset( $size_map[ $feature_images ] ) ) {
						$images_size = $size_map[ $feature_images ]['image'];
						$class_size  = $size_map[ $feature_images ]['class'];
					} else {
						// Random size if not specified.
						$random_key  = array_rand( $size_map );
						$images_size = $size_map[ $random_key ]['image'];
						$class_size  = $size_map[ $random_key ]['class'];
					}
					$class_size .= ' ' . $class_column;
				} elseif ( 'masonry' === $item_size ) {
					$images_size = 'full';
					$class_size  = $class_column;
				} else {
					$class_size = $class_column;
				}

				// Build category classes.
				$terms_id  = array();
				$item_cats = get_the_terms( $post_id, 'portfolio_category' );
				if ( $item_cats && ! is_wp_error( $item_cats ) ) {
					foreach ( $item_cats as $item_cat ) {
						$item_classes .= urldecode( $item_cat->slug ) . ' ';
						$terms_id[]    = $item_cat->term_id;
					}
				}

				// Get image.
				$image_id   = get_post_thumbnail_id( $post_id );
				$post_title = get_the_title( $post_id );
				$imgurl     = false;

				if ( 'masonry' === $item_size ) {
					$width  = 600;
					$imgurl = wp_get_attachment_image_src( $image_id, 'full' );
					if ( $imgurl && isset( $imgurl[1], $imgurl[2] ) && $imgurl[1] > 0 ) {
						$ratio  = $imgurl[2] / $imgurl[1];
						$height = (int) round( $width * $ratio );
						$imgurl = wp_get_attachment_image_src( $image_id, array( $width, $height ) );
					}
					$image_url = sprintf(
						'<img src="%s" alt="%s"   />',
						$imgurl ? esc_url( $imgurl[0] ) : '',
						esc_attr( $post_title ),
					);
				} else {
					$imgurl = wp_get_attachment_image_src( $image_id, array( 480, 320 ) );
					if ( 'multigrid' === $item_size && $imgurl ) {
						$image_url = sprintf(
							'<div class="thumb-img" style="background-image: url(%s);"><img src="%s" alt="%s" /></div>',
							esc_url( $imgurl[0] ),
							esc_url( $imgurl[0] ),
							esc_attr( $post_title )
						);
					} else {
						$image_url = sprintf(
							'<img src="%s" alt="%s" />',
							$imgurl ? esc_url( $imgurl[0] ) : '',
							esc_attr( $post_title )
						);
					}
				}

				$permalink = get_permalink( $post_id );
				$cat_links = array();
				if ( $item_cats && ! is_wp_error( $item_cats ) ) {
					foreach ( $item_cats as $term ) {
						$cat_links[] = sprintf(
							'<a href="%s">%s</a>',
							esc_url( get_term_link( $term ) ),
							esc_html( $term->name )
						);
					}
				}
				?>
				<li class="element-item <?php echo esc_attr( trim( $item_classes . 'item_portfolio ' . $class_size ) ); ?>">
					<div data-color="<?php echo esc_attr( $bk_ef ); ?>" <?php echo $bg; ?>>
						<div class="portfolio-image">
							<div class="img-portfolio">
								<?php if ( 'style09' === $pf_layout && $imgurl ) : ?>
									<div class="image-popup">
										<a href="<?php echo esc_url( $imgurl[0] ); ?>">
											<i aria-hidden="true" class="edu-expand"></i>
										</a>
										<a href="<?php echo esc_url( $permalink ); ?>">
											<i aria-hidden="true" class="edu-plus"></i>
										</a>
									</div>
								<?php endif; ?>

								<?php
								$date_end = get_post_meta( $post_id, 'portfolio_date_end', true );
								if ( 'style11' === $pf_layout && ! empty( $date_end ) ) :
									?>
									<div class="date"><i class="edu-clock"></i><?php echo esc_html( $date_end ); ?>
									</div>
								<?php endif; ?>

								<?php echo wp_kses_post( $image_url ); ?>
							</div>
							<div class="portfolio-hover" <?php echo $bg; ?>>
								<div class="thumb-bg">
									<div class="mask-content">
										<?php
										printf(
											'<h3><a href="%1$s" title="%2$s">%2$s</a></h3>',
											esc_url( $permalink ),
											esc_html( $post_title )
										);
										?>

										<?php if ( 'style11' !== $pf_layout ) : ?>
											<span class="p_line"></span>
											<?php if ( ! empty( $cat_links ) ) : ?>
												<div
													class="cat_portfolio"><?php echo wp_kses_post( implode( ', ', $cat_links ) ); ?></div>
											<?php endif; ?>
										<?php endif; ?>

										<?php if ( 'style10' === $pf_layout ) : ?>
											<div
												class="date"><?php echo esc_html( get_the_date( get_option( 'date_format' ) ) ); ?></div>
										<?php endif; ?>

										<?php if ( 'style09' === $pf_layout || 'style11' === $pf_layout ) : ?>
											<p class="description"><?php echo esc_html( get_the_excerpt() ); ?></p>
										<?php endif; ?>

										<a href="<?php echo esc_url( $permalink ); ?>"
											title="<?php echo esc_attr( $post_title ); ?>"
											class="btn_zoom"><?php echo esc_html__( 'View More', 'eduma' ); ?></a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</li>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</ul>
		<?php
		$show_readmore = $instance['show_readmore'];
		$btn_text      = esc_html__( 'View More', 'eduma' );
		if ( $show_readmore ) {
			echo '<div class="read-more">';
			echo '<a class="thim-button" href="' . esc_url( home_url( '/' ) ) . 'portfolio/">' . $btn_text . '</a>';
			echo '</div>';
		}
		if ( $paging == 'paging' ) {
			portfolio_pagination( $gallery->max_num_pages, $range = 2, $paged );
		}

		if ( $paging == 'infinite_scroll' ) {
			portfolio_pagination( $gallery->max_num_pages, $range = 2, $paged );
		}
		?>
	</div>
</div>
<!-- .wapper portfolio -->
