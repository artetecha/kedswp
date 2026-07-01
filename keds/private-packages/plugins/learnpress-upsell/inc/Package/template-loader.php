<?php
namespace LearnPress\Upsell\Package;

class Template_Loader {

	protected static $instance = null;

	public function __construct() {
		add_filter( 'template_include', array( $this, 'template_loader' ) );

		if ( ! is_admin() ) {
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		}
	}

	public function template_loader( $template ) {
		if ( wp_is_block_theme() ) {
			return $template;
		}

		if ( is_embed() ) {
			return $template;
		}

		$default_file = $this->get_template_loader_default_file();

		if ( $default_file ) {
			$template = locate_template( Core_Functions::instance()->template_path() . $default_file );

			if ( ! $template ) {
				$template = LP_ADDON_UPSELL_PATH . '/templates/' . $default_file;
			}
		}

		return $template;
	}

	public function get_template_loader_default_file() {
		if ( is_singular( LP_PACKAGE_CPT ) ) {
			$default_file = 'packages/single-package.php';
		} elseif ( Core_Functions::instance()->is_package_taxonomy() ) {
			$default_file = 'packages/archive-package.php';
		} elseif ( is_post_type_archive( LP_PACKAGE_CPT ) || is_page( Core_Functions::instance()->get_page_id( 'archive' ) ) ) {
			$default_file = 'packages/archive-package.php';
		} else {
			$default_file = '';
		}

		return $default_file;
	}

	public function pre_get_posts( $q ) {
		if ( ! $q->is_main_query() ) {
			return;
		}

		if ( ( $q->is_home() && ! $q->is_posts_page ) && 'page' === get_option( 'show_on_front' ) ) {
			// When orderby is set, WordPress shows posts on the front-page. Get around that here.
			if ( absint( get_option( 'page_on_front' ) ) === absint( Core_Functions::instance()->get_page_id( 'archive' ) ) ) {
				$_query = wp_parse_args( $q->query );
				if ( empty( $_query ) || ! array_diff( array_keys( $_query ), array( 'preview', 'page', 'paged', 'cpage', 'orderby' ) ) ) {
					$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
					$q->is_page = true;
					$q->is_home = false;

					$q->set( 'post_type', LP_PACKAGE_CPT );
				}
			} elseif ( ! empty( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
				$q->is_page     = true;
				$q->is_home     = false;
				$q->is_singular = true;
			}
		}

		if ( $q->is_feed() && $q->is_post_type_archive( LP_PACKAGE_CPT ) ) {
			$q->is_comment_feed = false;
		}

		if ( $q->is_page() && 'page' === get_option( 'show_on_front' ) && absint( $q->get( 'page_id' ) ) === Core_Functions::instance()->get_page_id( 'archive' ) ) {
			$q->set( 'post_type', LP_PACKAGE_CPT );
			$q->set( 'page_id', '' );

			if ( isset( $q->query['paged'] ) ) {
				$q->set( 'paged', $q->query['paged'] );
			}

			global $wp_post_types;

			$package_page = get_post( Core_Functions::instance()->get_page_id( 'archive' ) );

			$wp_post_types[ LP_PACKAGE_CPT ]->ID         = $package_page->ID;
			$wp_post_types[ LP_PACKAGE_CPT ]->post_title = $package_page->post_title;
			$wp_post_types[ LP_PACKAGE_CPT ]->post_name  = $package_page->post_name;
			$wp_post_types[ LP_PACKAGE_CPT ]->post_type  = $package_page->post_type;
			$wp_post_types[ LP_PACKAGE_CPT ]->ancestors  = get_ancestors( $package_page->ID, $package_page->post_type );

			$q->is_singular          = false;
			$q->is_post_type_archive = true;
			$q->is_archive           = true;
			$q->is_page              = true;

			// Remove post type archive name from front page title tag.
			add_filter( 'post_type_archive_title', '__return_empty_string', 5 );

		} elseif ( ! $q->is_post_type_archive( LP_PACKAGE_CPT ) && ! $q->is_tax( get_object_taxonomies( LP_PACKAGE_CPT ) ) ) {
			return;
		}

		$this->package_query( $q );
	}

	public function package_query( $q ) {
		if ( ! is_feed() ) {
			$ordering = $this->get_catalog_ordering_args();
			$q->set( 'orderby', $ordering['orderby'] );
			$q->set( 'order', $ordering['order'] );

			if ( isset( $ordering['meta_key'] ) ) {
				$q->set( 'meta_key', $ordering['meta_key'] );
			}
		}

		$q->set( 'lp_package_query', 'package_query' );

		$per_page = \LP_Settings::instance()->get( 'package.per_page', 10 );

		$q->set( 'posts_per_page', $q->get( 'posts_per_page' ) ? $q->get( 'posts_per_page' ) : absint( $per_page ) );

		add_filter( 'the_posts', array( $this, 'handle_get_posts' ), 10, 2 );
	}

	public function get_catalog_ordering_args( $orderby = '', $order = '' ) {
		if ( ! $orderby ) {
			$orderby_value = isset( $_GET['orderby'] ) ? (string) wp_unslash( $_GET['orderby'] ) : get_query_var( 'orderby' );

			if ( ! $orderby_value ) {
				if ( is_search() ) {
					$orderby_value = 'relevance';
				} else {
					$orderby_value = apply_filters( 'learnpress_package_default_catalog_orderby', 'menu_order' );
				}
			}

			$orderby_value = is_array( $orderby_value ) ? $orderby_value : explode( '-', $orderby_value );
			$orderby       = esc_attr( $orderby_value[0] );
			$order         = ! empty( $orderby_value[1] ) ? $orderby_value[1] : $order;
		}

		$orderby = strtolower( is_array( $orderby ) ? (string) current( $orderby ) : (string) $orderby );
		$order   = strtoupper( is_array( $order ) ? (string) current( $order ) : (string) $order );
		$args    = array(
			'orderby'  => $orderby,
			'order'    => 'DESC' === $order ? 'DESC' : 'ASC',
			'meta_key' => '',
		);

		switch ( $orderby ) {
			case 'id':
				$args['orderby'] = 'ID';
				break;
			case 'menu_order':
				$args['orderby'] = 'menu_order title';
				break;
			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = 'DESC' === $order ? 'DESC' : 'ASC';
				break;
			case 'relevance':
				$args['orderby'] = 'relevance';
				$args['order']   = 'DESC';
				break;
			case 'rand':
				$args['orderby'] = 'rand';
				break;
			case 'date':
				$args['orderby'] = 'date ID';
				$args['order']   = 'ASC' === $order ? 'ASC' : 'DESC';
				break;
			case 'price':
				add_filter( 'posts_clauses', array( $this, 'order_by_price_asc_post_clauses' ) );
				break;
			case 'price_desc':
				add_filter( 'posts_clauses', array( $this, 'order_by_price_desc_post_clauses' ) );
				break;
		}

		return apply_filters( 'learnpress_package_get_catalog_ordering_args', $args, $orderby, $order );
	}

	// Nhamdv: Easy game :D.
	public function order_by_price_asc_post_clauses( $args ) {
		global $wpdb;

		// Order by meta_key _lp_package_sale_price and _lp_package_price.
		$args['join']   .= " LEFT JOIN {$wpdb->postmeta} AS pm1 ON {$wpdb->posts}.ID = pm1.post_id AND pm1.meta_key = '_lp_package_sale_price'";
		$args['join']   .= " LEFT JOIN {$wpdb->postmeta} AS pm2 ON {$wpdb->posts}.ID = pm2.post_id AND pm2.meta_key = '_lp_package_price'";
		$args['orderby'] = 'CAST(pm1.meta_value AS DECIMAL(10,2)) ASC, CAST(pm2.meta_value AS DECIMAL(10,2)) ASC';

		return $args;
	}

	// Nhamdv: Easy game :D.
	public function order_by_price_desc_post_clauses( $args ) {
		global $wpdb;

		// Order by meta_key _lp_package_sale_price and _lp_package_price.
		$args['join']   .= " LEFT JOIN {$wpdb->postmeta} AS pm1 ON {$wpdb->posts}.ID = pm1.post_id AND pm1.meta_key = '_lp_package_sale_price'";
		$args['join']   .= " LEFT JOIN {$wpdb->postmeta} AS pm2 ON {$wpdb->posts}.ID = pm2.post_id AND pm2.meta_key = '_lp_package_price'";
		$args['orderby'] = 'CAST(pm1.meta_value AS DECIMAL(10,2)) DESC, CAST(pm2.meta_value AS DECIMAL(10,2)) DESC';

		return $args;
	}

	public function handle_get_posts( $posts, $query ) {
		if ( 'package_query' !== $query->get( 'lp_package_query' ) ) {
			return $posts;
		}

		remove_filter( 'posts_clauses', array( $this, 'order_by_price_asc_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'order_by_price_desc_post_clauses' ) );

		return $posts;
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Template_Loader::instance();
