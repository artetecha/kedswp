<?php
namespace LearnPress\Upsell\Package;

use LearnPress\Models\CourseModel;

class Package {

	protected $id = 0;

	public function __construct( $package = 0 ) {
		if ( is_numeric( $package ) && $package > 0 ) {
			$this->set_id( $package );
		} elseif ( $package instanceof self ) {
			$this->set_id( absint( $package->get_id() ) );
		} elseif ( ! empty( $package->ID ) ) {
			$this->set_id( absint( $package->ID ) );
		} else {
			$this->set_id( 0 );
		}
	}

	public function get_id() {
		return $this->id;
	}

	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	public function get_status() {
		$status = get_post_status( $this->id );

		return $status;
	}

	public function exists() {
		return $this->get_id() > 0 && LP_PACKAGE_CPT === get_post_type( $this->get_id() );
	}

	public function is_visible() {
		$visible = false;

		if ( $this->get_status() === 'publish' ) {
			$visible = true;
		}

		if ( 'trash' === $this->get_status() ) {
			$visible = false;
		} elseif ( 'publish' !== $this->get_status() && ! current_user_can( 'edit_post', $this->get_id() ) ) {
			$visible = false;
		}

		return apply_filters( 'learnpress_package/is_visible', $visible, $this );
	}

	public function get_title() {
		$title = get_the_title( $this->get_id() );

		return apply_filters( 'learnpress_package/get_title', $title, $this );
	}

	public function get_permalink() {
		$permalink = get_permalink( $this->get_id() );

		return apply_filters( 'learnpress_package/get_permalink', $permalink, $this );
	}

	public function get_content() {
		$post = get_post( $this->get_id() );

		if ( ! $post ) {
			return '';
		}

		$content = apply_filters( 'the_content', $post->post_content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		return apply_filters( 'learnpress_package/get_content', $content, $this );
	}

	public function get_short_content( $limit = 20, $more = '...' ) {
		$content = $this->get_content();
		$content = wp_trim_words( $content, $limit, $more );

		return apply_filters( 'learnpress_package/get_short_content', $content, $this );
	}

	public function get_image( $size = 'thumbnail', $attr = array(), $placeholder = true ) {
		$image = '';

		if ( $this->get_image_id() ) {
			$image = wp_get_attachment_image( $this->get_image_id(), $size, false, $attr );
		}

		if ( ! $image && $placeholder ) {
			$image = Core_Functions::instance()->placeholder_img( $size, $attr );
		}

		return apply_filters( 'learnpress_package/get_image', $image, $this, $size, $attr );
	}

	public function get_image_id() {
		$image_id = get_post_thumbnail_id( $this->get_id() );

		return apply_filters( 'learnpress_package/get_image_id', $image_id, $this );
	}

	public function get_regular_price() {
		$price = get_post_meta( $this->get_id(), '_lp_package_price', true );
		$price = $price ? $price : 0;

		return apply_filters( 'learnpress_package/get_regular_price', $price, $this );
	}

	public function get_sale_price() {
		$price = get_post_meta( $this->get_id(), '_lp_package_sale_price', true );
		$price = $price ? $price : 0;

		$enabled = $this->get_new_price_enabled();

		if ( ! $enabled ) {
			$price = 0;
		}

		return apply_filters( 'learnpress_package/get_sale_price', $price, $this );
	}

	public function get_price() {
		$price = $this->get_sale_price();

		if ( ! $price ) {
			$price = $this->get_regular_price();
		}

		return apply_filters( 'learnpress_package/get_price', $price, $this );
	}

	public function get_price_amount() {
		$amount = get_post_meta( $this->get_id(), '_lp_package_new_price_amount', true );
		$amount = $amount ? $amount : 0;

		return apply_filters( 'learnpress_package/get_price_amount', $amount, $this );
	}

	public function get_price_type() {
		$type = get_post_meta( $this->get_id(), '_lp_package_new_price_type', true );
		$type = $type ? $type : 'percent';

		return apply_filters( 'learnpress_package/get_price_type', $type, $this );
	}

	public function get_new_price_enabled() {
		$enabled = get_post_meta( $this->get_id(), '_lp_package_new_price_enabled', true );

		return apply_filters( 'learnpress_package/get_new_price_enabled', $enabled === 'yes', $this );
	}

	public function is_on_sale() {
		$on_sale = false;

		$sale_price    = $this->get_sale_price();
		$regular_price = $this->get_regular_price();

		if ( ! $sale_price && $this->get_new_price_enabled() || $sale_price && $sale_price < $regular_price ) {
			$on_sale = true;
		}

		return apply_filters( 'learnpress_package/is_on_sale', $on_sale, $this );
	}

	/**
	 * Get list courses of Package
	 *
	 * @return mixed|null
	 */
	public function get_course_list() {
		$course_ids = get_post_meta( $this->get_id(), '_lp_package_courses' );
		$course_ids = $course_ids ? $course_ids : array();

		// Check course is exist
		foreach ( $course_ids as $key => $course_id ) {
			$course = CourseModel::find( $course_id, true );

			// Check if course exists.
			if ( ! $course || 'publish' !== $course->post_status ) {
				unset( $course_ids[ $key ] );
			}
		}

		return $course_ids;
	}

	/**
	 * Get related package of Package
	 *
	 * @param $args
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return mixed|void|null
	 */
	public function get_related_package_ids( $args = array() ) {
		$defaults = array(
			'posts_per_page' => 10,
			'orderby'        => 'rand',
			'order'          => 'desc',
		);

		$args = wp_parse_args( $args, $defaults );

		$limit = apply_filters( 'learnpress_upsell_related_package_limit', 3 );

		if ( $limit < 1 ) {
			return;
		}

		$args['posts_per_page'] = $limit;

		$related_args = array(
			'post_type'      => LP_PACKAGE_CPT,
			'posts_per_page' => $args['posts_per_page'],
			'post_status'    => 'publish',
			'post__not_in'   => array( $this->get_id() ),
			'orderby'        => $args['orderby'],
			'tax_query'      => array(),
			//'fields'         => 'ids',
		);

		$post = get_post( $this->get_id() );

		$taxonomies = get_object_taxonomies( $post->post_type );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post->ID, $taxonomy );

			if ( ! empty( $terms ) ) {
				$term_list                   = wp_list_pluck( $terms, 'slug' );
				$related_args['tax_query'][] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $term_list,
				);
			}
		}

		$query               = new \WP_Query( $related_args );
		$related_package_ids = $query->get_posts();

		return apply_filters( 'learnpress_package/get_related_package', $related_package_ids, $this );
	}

	/**
	 * Get data for display on file template.
	 *
	 * @param array $extra_key
	 *
	 * @return object
	 */
	public function get_data_for_display( array $extra_key = [] ): object {
		$data              = new \stdClass();
		$data->id          = $this->get_id();
		$data->title       = $this->get_title();
		$data->description = $this->get_content();
		$data->price       = $this->get_price();

		if ( array_key_exists( 'related', $extra_key ) ) {
			$data->related = $this->get_related_package();
		}

		return $data;
	}

	/**
	 * Get data by string key.
	 *
	 * @param array $key
	 *
	 * @return mixed|void|null
	 */
	public function get_data_by_key( array $key ) {
		switch ( $key ) {
			case 'id':
				return $this->get_id();
			case 'title':
				return $this->get_title();
			case 'description':
				return $this->get_content();
			case 'price':
				return $this->get_price();
			case 'related':
				return $this->get_related_package();
			default:
				return null;
		}
	}

	/**
	 * Return string html title.
	 *
	 * @param array $els [ 'tag_open' => 'tag_close' ]
	 *
	 * @return string
	 */
	public function get_title_html( array $els = [] ): string {
		$title = $this->get_title();
		return Core_Functions::instance()->nest_elements( $els, $title );
	}

	/**
	 * Return string html title.
	 *
	 * @param array $els [ 'tag_open' => 'tag_close' ]
	 * @param array $args_image
	 *
	 * @return string
	 */
	public function get_image_html( array $els = [], array $args_image = [] ): string {
		$size        = $args_image['size'] ?? 'thumbnail';
		$attr        = $args_image['attr'] ?? [];
		$placeholder = $args_image['placeholder'] ?? true;
		$image       = $this->get_image( $size, $attr, $placeholder );

		return Core_Functions::instance()->nest_elements( $els, $image );
	}

	/**
	 * Return string html price.
	 *
	 * @return mixed|null
	 */
	public function get_price_html( array $els = [] ): string {
		$price         = $this->get_price();
		$regular_price = $this->get_regular_price();

		$price_html = __( 'Free', 'learnpress-upsell-package' );
		if ( $price || $regular_price && $this->get_new_price_enabled() ) {
			$price_html = learn_press_format_price( $price, true );
		}

		if ( $this->is_on_sale() && $regular_price ) {
			$regular_price_html = learn_press_format_price( $regular_price, true );

			$price_html = sprintf( '<del aria-hidden="true">%s</del> <ins>%s</ins>', $regular_price_html, $price_html );
		}

		if ( empty( $els ) ) {
			$els = [ '<div class="lp-package-price">' => '</div>' ];
		}

		return Core_Functions::instance()->nest_elements( $els, $price_html );
	}

	/**
	 * Return short content html.
	 *
	 * @param array $els
	 * @param int $limit
	 * @param string $more
	 *
	 * @return string
	 */
	public function get_short_content_html( array $els = [], int $limit = 20, string $more = '...' ): string {
		$content = $this->get_short_content( $limit, $more );

		return Core_Functions::instance()->nest_elements( $els, $content );
	}
}
