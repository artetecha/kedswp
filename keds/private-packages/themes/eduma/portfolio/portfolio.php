<?php
/*
 * Since Portfolio 2.0
 */

namespace Eduma_Theme;

use WP_Query;

/**
 * init class.
 */
class Thim_Portfolio_Init {
	/**
	 * @var Thim_Portfolio_Init
	 */
	protected static $_instance;

	/**
	 * WC_Init
	 */
	public function __construct() {
		$this->thim_hook_layout_archive_portfolio();
		add_action( 'pre_get_posts', array( $this, 'thim_portffolio_post_filter' ) );
		add_filter( 'thim_portfolio_meta_boxes', array( $this, 'thim_before_portfolio_type' ), 20 );
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function thim_hook_layout_archive_portfolio() {
		// Hook layout archive portfolio
		add_action( 'thim_portfolio_layout_start', 'thim_wrapper_div_open', 1 );
		add_action( 'thim_portfolio_before_archive_portfolio', 'thim_wapper_page_title', 2 );
		add_action( 'thim_portfolio_before_archive_portfolio', 'thim_wrapper_loop_start', 10 );
		add_action( 'thim_portfolio_layout_end', 'thim_wrapper_loop_end', 50 );
		add_action( 'thim_portfolio_layout_end', 'thim_wrapper_div_close', 51 );
		remove_action( 'thim_portfolio_loop_header', 'thim_portfolio_taxonomy_archive_header' );
		remove_action( 'thim_portfolio_before_loop', 'thim_portfolio_start_loop', 10 );
		remove_action( 'thim_portfolio_after_loop', 'thim_portfolio_end_loop', 10 );
		remove_action( 'thim_portfolio_after_loop', 'portfolio_pagination', 20 );
		add_action( 'thim_portfolio_before_loop', array( $this, 'thim_portfolio_start_loop_tag' ), 1 );
		add_action( 'thim_portfolio_before_loop', array( $this, 'thim_portfolio_tab_filter' ), 8 );
		add_action( 'thim_portfolio_before_loop', array( $this, 'thim_portfolio_before_content_tag' ), 12 );
		add_action( 'thim_portfolio_after_loop', array( $this, 'thim_portfolio_after_loop_tag' ), 20 );
		add_action( 'thim_portfolio_after_loop', array( $this, 'thim_portfolio_after_content_tag' ), 15 );
		//hook layout single portfolio
		add_action( 'thim_portfolio_before_single_portfolio', 'thim_wapper_page_title', 2 );
		add_action( 'thim_portfolio_before_single_portfolio', 'thim_wrapper_loop_start', 10 );
		remove_action( 'thim_portfolio_single_content', 'thim_portfolio_template_single_title', 10 );
		add_action( 'thim_related_portfolio_before_loop', array( $this, 'thim_portfolio_related_open_tag' ), 10 );
		add_action( 'thim_related_portfolio_after_loop', array( $this, 'thim_portfolio_after_loop_tag' ), 10 );
		add_action( 'thim_portfolio_single_content', array( $this, 'thim_portfolio_single_content_meta_data' ), 35 );
		// check update plugin
		add_action( 'admin_notices', array( $this, 'thim_mesage_update_notice' ), 35 );
	}

	/**
	 * Set unlimit events in archive
	 *
	 * @param $query
	 */
	public function thim_portffolio_post_filter( $query ) {
		if ( $query->is_main_query() && ! is_admin() && is_post_type_archive( 'portfolio' ) ) {
			$query->set( 'posts_per_page', - 1 );
		}
	}

	public function thim_before_portfolio_type( $meta_boxes ) {
		foreach ( $meta_boxes as $box_index => $box ) {
			if ( isset( $box['id'] ) && $box['id'] === 'portfolio_settings' && isset( $box['fields'] ) ) {
				$pos = null;
				foreach ( $box['fields'] as $i => $field ) {
					if ( isset( $field['id'] ) && $field['id'] === 'selectPortfolio' ) {
						$pos = $i;
						break;
					}
				}
				$new_field = array(
					array(
						'name'    => esc_html__( 'Multigrid Size', 'tp-portfolio' ),
						'id'      => 'feature_images',
						'type'    => 'select',
						'options' => array(
							'random' => 'Random',
							'size11' => 'Size 1x1(480 x 320)',
							'size12' => 'Size 1x2(480 x 640)',
							'size21' => 'Size 2x1(960 x 320)',
							'size22' => 'Size 2x2(960 x 640)',
						),
						'tab'     => 'Type',
						'icon'    => 'dashicons-filter',
					),
					array(
						'name' => __( 'Background Color', 'eduma' ),
						'id'   => 'thim_portfolio_bg_color_ef',
						'type' => 'color',
						'tab'  => 'Type',
						'icon' => 'dashicons-filter',
					),
				);
				if ( $pos === null ) {
					$meta_boxes[ $box_index ]['fields'][] = $new_field;
				} else {
					array_splice( $meta_boxes[ $box_index ]['fields'], $pos, 0, $new_field );
				}
				break;
			}
		}

		return $meta_boxes;
	}

	public function thim_portfolio_tab_filter() {
		$query_object = get_queried_object();
		// Item style
		$category = isset( $query_object->term_id ) ? $query_object->term_id : '';
		$args     = array(
			'post_type'      => 'portfolio',
			'posts_per_page' => - 1,
		);

		if ( ( is_array( $category ) && ! empty( $category ) ) || ( ! is_array( $category ) && $category ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'portfolio_category',
				'field'    => 'term_id',
				'terms'    => $category,
			);
		}

		$gallery = new WP_Query( $args );
		if ( is_array( $gallery->posts ) && ! empty( $gallery->posts ) && $gallery->post_count ) {
			foreach ( $gallery->posts as $gallery_post ) {
				$post_taxs = wp_get_post_terms(
					$gallery_post->ID,
					'portfolio_category',
					array(
						'fields' => 'all',
					)
				);
				if ( is_array( $post_taxs ) && ! empty( $post_taxs ) ) {
					foreach ( $post_taxs as $post_tax ) {
						if ( is_array( $category ) && ! empty( $category ) && ( in_array( $post_tax->term_id, $category, true ) || in_array( $post_tax->parent, $category, true ) ) ) {
							$portfolio_taxs[ urldecode( $post_tax->slug ) ] = $post_tax->name;
						}
						if ( empty( $category ) || ! isset( $category ) ) {
							$portfolio_taxs[ urldecode( $post_tax->slug ) ] = $post_tax->name;
						}
					}
				}
			}
		} else {
			exit;
		}
		if ( ! empty( $portfolio_taxs ) ) : ?>
			<div class="portfolio-tabs-wapper filters">
				<ul class="portfolio-tabs">
					<li><a href="#" class="filter active"
							data-filter="*"><?php echo esc_html__( 'All', 'eduma' ); ?></a></li>
					<?php foreach ( $portfolio_taxs as $portfolio_tax_slug => $portfolio_tax_name ) : ?>
						<li>
							<a class="filter" href="#"
								data-filter=".<?php echo esc_attr( $portfolio_tax_slug ); ?>"><?php echo esc_html( $portfolio_tax_name ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		endif;
	}

	public function thim_portfolio_start_loop_tag() {
		$item_size    = get_theme_mod( 'thim_portfolio_item_size', 'same' );
		$pf_layout    = get_theme_mod( 'thim_portfolio_cate_style_chosen', 'style01' );
		$gutter       = true; // Always enabled
		$class_gutter = $gutter ? ' gutter' : '';
		?>
		<div class="wapper_portfolio classic classic<?php echo esc_attr( $class_gutter ); ?> <?php echo esc_attr( $item_size ); ?> all <?php echo esc_attr( $pf_layout ); ?>">
		<?php
	}

	public function thim_portfolio_after_loop_tag() {
		?>
		</div>
		<?php
	}

	public function thim_portfolio_before_content_tag() {
		$pf_layout = get_theme_mod( 'thim_portfolio_cate_style_chosen', 'style01' );
		?>
		<div class="portfolio_column">
	<ul class="content_portfolio <?php echo esc_attr( $pf_layout ); ?>">
		<?php
	}

	public function thim_portfolio_after_content_tag() {
		echo '</ul></div>';
	}

	public function thim_portfolio_related_open_tag() {
		$pf_layout = get_theme_mod( 'thim_portfolio_cate_style_chosen', 'style01' );
		echo '<div class="wapper_portfolio"><ul class="' . esc_attr( $pf_layout ) . '">';
	}
	public function thim_portfolio_single_content_meta_data() {
		if ( ! get_theme_mod( 'thim_portfolio_about_author', false ) ) {
			return;
		}
		?>
		<div class="thim-portfolio-single-share">
			<?php do_action( 'thim_social_share' ); ?>
		</div>
		<div class="thim-portfolio-single-author">
			<h4>
				<?php echo esc_html__( 'About the Instructor', 'eduma' ); ?>
			</h4>
			<?php do_action( 'thim_about_author' ); ?>
		</div>
		<?php
	}
	public function thim_mesage_update_notice() {
		if ( ! defined( 'THIM_PORTFOLIO_VERSION' ) || version_compare( THIM_PORTFOLIO_VERSION, '2.0', '>=' ) ) {
			return;
		}
		echo '<div class="notice notice-error">';

		printf(
			'<p><strong>%s</strong><br/>%s</p>',
			__( 'Portfolio Plugin Update Required', 'eduma' ),
			esc_html__( 'Your Portfolio plugin version is outdated. Please update to version 2.0 or higher for best experience and security.', 'eduma' )
		);

		printf( '<p><a href="%s" class="button button-primary">%s</a></p>', esc_url( admin_url( 'admin.php?page=thim-plugins' ) ), esc_html__( 'Go to Plugins', 'eduma' ) );
		echo '</div>';
	}
}

Thim_Portfolio_Init::getInstance();
