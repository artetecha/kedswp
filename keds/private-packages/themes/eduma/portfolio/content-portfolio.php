<?php
/**
 * Portfolio Content Template
 *
 * @package thimpress
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

// Cache frequently used values.
$post_id = get_the_ID();
// Get layout settings via direct theme mods.
$pf_layout = get_theme_mod( 'thim_portfolio_cate_style_chosen', 'style01' );
$column    = absint( get_theme_mod( 'thim_portfolio_cate_grid_column', 3 ) );
$item_size = get_theme_mod( 'thim_portfolio_item_size', 'same' );
// Column class mapping.
$column_classes = array(
	1 => 'one-col',
	2 => 'two-col',
	3 => 'three-col',
	4 => 'four-col',
	5 => 'five-col',
);
$class_column   = isset( $column_classes[ $column ] ) ? $column_classes[ $column ] : 'three-col';

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
$item_classes = $class_size = ''; // phpcs:ignore

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
$cat_links = array();
$item_cats = get_the_terms( $post_id, 'portfolio_category' );
if ( $item_cats && ! is_wp_error( $item_cats ) ) {
	foreach ( $item_cats as $item_cat ) {
		$item_classes .= urldecode( $item_cat->slug ) . ' ';
		$cat_links[]   = sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_term_link( $item_cat ) ),
			esc_html( $item_cat->name )
		);
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

// Portfolio link and button.
$permalink = get_permalink( $post_id );

?>
<li class="element-item <?php echo esc_attr( trim( $item_classes . 'item_portfolio ' . $class_size ) ); ?>">
	<div data-color="<?php echo esc_attr( $bk_ef ); ?>" <?php echo $bg; ?>>
		<div class="portfolio-image">
			<div class="img-portfolio">
				<?php if ( 'style09' === $pf_layout && $imgurl ) : ?>
					<div class="image-popup">
						<a href="<?php echo esc_url( $imgurl[0] ); ?>"><i aria-hidden="true" class="edu-expand"></i></a>
						<a href="<?php echo esc_url( $permalink ); ?>"><i aria-hidden="true" class="edu-plus"></i></a>
					</div>
				<?php endif; ?>

				<?php
				$date_end = get_post_meta( $post_id, 'portfolio_date_end', true );
				if ( 'style11' === $pf_layout && ! empty( $date_end ) ) :
					?>
					<div class="date"><i class="edu-clock"></i><?php echo esc_html( $date_end ); ?></div>
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

						<a href="<?php echo esc_url( $permalink ); ?>" title="<?php echo esc_attr( $post_title ); ?>"
							class="btn_zoom"><?php echo esc_html__( 'View More', 'eduma' ); ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</li>
