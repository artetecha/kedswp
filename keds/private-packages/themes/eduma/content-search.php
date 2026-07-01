<?php
/**
 * The template part for displaying results in search pages.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package thim
 */


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

<article id="post-<?php the_ID(); ?>" <?php post_class( 'col-sm-12' ); ?>>
	<div class="content-inner">
		<?php
		do_action( 'thim_entry_top', 'full' );
		?>

		<div class="entry-content">

			<header class="entry-header">
				<?php
				printf(
					'<h2 class="entry-title"><a href="%1$s" rel="bookmark">%2$s</a></h2>',
					esc_url( $post_link['url'] ),
					wp_kses_post( $post_link['title'] )
				);
				thim_entry_meta();
				?>
			</header>

			<div class="entry-summary">
				<?php
				the_excerpt();
				?>
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
