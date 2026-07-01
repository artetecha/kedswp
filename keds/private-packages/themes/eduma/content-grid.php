<?php
/**
 * Template part for displaying posts in grid layout
 *
 * @package Eduma
 */

defined( 'ABSPATH' ) || exit;

$columns   = absint( thim_blog_grid_column() );
$post_link = has_post_format( 'link' ) && thim_meta( 'thim_link_url' ) && thim_meta( 'thim_link_text' )
	? array(
		'url'   => esc_url( thim_meta( 'thim_link_url' ) ),
		'title' => esc_html( thim_meta( 'thim_link_text' ) ),
	)
	: array(
		'url'   => esc_url( get_permalink() ),
		'title' => esc_html( get_the_title() ),
	);
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'grid-layout-1 blog-grid-' . $columns ); ?>>
	<div class="content-inner">

		<?php do_action( 'thim_entry_top', 'full' ); ?>

		<div class="entry-content">
			<?php
			$thim_meta_tags = get_theme_mod( 'thim_blog_meta_tags', array( 'date', 'comment' ) );
			?>

			<header class="entry-header">
				<div class="entry-contain">
					<?php
					printf(
						'<h2 class="entry-title"><a href="%1$s" rel="bookmark">%2$s</a></h2>',
						esc_url( $post_link['url'] ),
						wp_kses_post( $post_link['title'] )
					);
					thim_entry_meta();
					?>
				</div>
			</header>

			<div class="meta-grid">
 
				<?php
					printf(
						'<div class="author"><i class="edu-user" aria-hidden="true"></i><a href="%1$s">%2$s</a></div>',
						esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
						esc_html( get_the_author() )
					);
					?>
								 

				<div class="date">
						<i class="edu-clock" aria-hidden="true"></i>
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
							<?php echo esc_html( get_the_date( get_option( 'date_format' ) ) ); ?>
						</time>
					</div>
			</div>

			<div class="entry-summary">
				<?php the_excerpt(); ?>
			</div>

			<div class="readmore">
				<a href="<?php echo esc_url( get_permalink() ); ?>" class="read-more">
					<span class="screen-reader-text">
						<?php
						/* translators: %s: Post title */
						printf( esc_html__( 'Read more about %s', 'eduma' ), get_the_title() );
						?>
					</span>
					<?php esc_html_e( 'Read More', 'eduma' ); ?>
				</a>
			</div>
		</div>
	</div>
</article><!-- #post-## -->
