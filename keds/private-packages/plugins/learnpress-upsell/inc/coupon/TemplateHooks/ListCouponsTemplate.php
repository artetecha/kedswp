<?php
/**
 * Template hooks Archive Package.
 *
 * @since 4.0.4
 * @version 1.0.1
 */
namespace LearnPress\Upsell\TemplateHooks;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LearnPress\Upsell\Coupon\Coupon;
use LP_Database;
use LP_Datetime;
use LP_Post_DB;
use LP_Post_Type_Filter;
use stdClass;

class ListCouponsTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		add_action( 'lp/upsell/layout/list-coupon', [ $this, 'sections' ] );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'learnpress-package' );
	}

	/**
	 * Register callback for ajax.
	 *
	 * @param $callbacks
	 *
	 * @return mixed
	 */
	public function allow_callback( $callbacks ) {
		$callbacks[] = get_class( $this ) . ':render_coupons';

		return $callbacks;
	}

	/**
	 * Layout default list courses.
	 * @use self::render_coupons()
	 *
	 * @return void
	 * @since 4.2.5.8
	 * @version 1.0.0
	 */
	public function sections() {
		$callback = [
			'class'  => get_class( $this ),
			'method' => 'render_coupons',
		];
		$content  = TemplateAJAX::load_content_via_ajax(
			[
				'paged'  => 1,
				'id_url' => 'list-coupons',
			],
			$callback
		);
		echo $content;
	}

	/**
	 * Render template list courses with settings param.
	 *
	 * @param array $settings
	 *
	 * @return stdClass { content: string_html }
	 * @throws \Exception
	 * @version 1.0.0
	 * @since 4.0.4
	 */
	public static function render_coupons( array $settings = [] ): stdClass {
		$date_now = gmdate( 'Y-m-d H:i:s' );
		$lpDate   = new LP_Datetime( $date_now );
		// Get format date by Timezone config option.
		// Time zone MYSQL must same.
		$lpDateByCompareTimestamp = $lpDate->getTimestamp() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		$lpDateByCompare          = new LP_Datetime( $lpDateByCompareTimestamp );
		$date_now_to_compare      = $lpDateByCompare->format( 'Y-m-d H:i' ); // Config on Backend is Y-m-d H:i.
		$post_db                  = LP_Post_DB::getInstance();
		$filter                   = new LP_Post_Type_Filter();
		$filter->page             = $settings['paged'] ?? 1;
		$filter->limit            = 1;
		$filter->post_type        = LP_COUPON_CPT;
		$filter->post_status      = [ 'publish' ];
		$filter->join[]           = 'INNER JOIN ' . $post_db->tb_postmeta . ' AS cpm ON p.ID = cpm.post_id';
		$filter->where[]          = $post_db->wpdb->prepare( 'AND cpm.meta_key = %s AND cpm.meta_value > 0', 'discount_amount' );
		$filter->join[]           = 'INNER JOIN ' . $post_db->tb_postmeta . ' AS cpms ON p.ID = cpms.post_id';
		$filter->where[]          = $post_db->wpdb->prepare( 'AND cpms.meta_key = %s', 'discount_start_date' );
		$filter->where[]          = $post_db->wpdb->prepare(
			'AND ( cpms.meta_value < %s OR cpms.meta_value = %s OR cpms.meta_value = "" )',
			$date_now_to_compare,
			$date_now_to_compare
		);
		$filter->join[]           = 'INNER JOIN ' . $post_db->tb_postmeta . ' AS cpme ON p.ID = cpme.post_id';
		$filter->where[]          = $post_db->wpdb->prepare( 'AND cpme.meta_key = %s', 'discount_end_date' );
		$filter->where[]          = $post_db->wpdb->prepare(
			'AND ( cpme.meta_value > %s OR cpme.meta_value = %s OR cpme.meta_value = "" )',
			$date_now_to_compare,
			$date_now_to_compare
		);

		$total_rows  = 0;
		$coupons     = LP_Post_DB::getInstance()->get_posts( $filter, $total_rows );
		$total_pages = LP_Database::get_total_pages( $filter->limit, $total_rows );

		// HTML section courses.
		ob_start();
		if ( empty( $coupons ) ) {
			echo '';
		} else {
			foreach ( $coupons as $couponsObj ) {
				$coupon = new Coupon( $couponsObj->ID );
				echo static::render_coupon( $coupon, $settings );
			}
		}
		$html_courses = ob_get_clean();

		$html_btn_load_more = '';
		if ( $total_pages > $settings['paged'] ) {
			$html_btn_load_more = sprintf(
				'<button class="lp-button %s">%s</button>',
				'lp-btn-load-more-coupon',
				__( 'Load more', 'learnpress-upsell' )
			);
		}

		$section_coupons = [
			'wrapper'     => '<ul class="lp-list-coupons" >',
			'courses'     => $html_courses,
			'wrapper_end' => '</ul>',
		];

		$section = apply_filters(
			'learn-press/layout/coupons',
			[
				'coupons'       => Template::combine_components( $section_coupons ),
				'btn_load_more' => $html_btn_load_more,
			],
			$coupons,
			$settings
		);

		$content              = new stdClass();
		$content->content     = Template::combine_components( $section );
		$content->total_pages = $total_pages;
		$content->paged       = $filter->page;

		return $content;
	}

	/**
	 * @param Coupon $coupon
	 * @param array $settings
	 *
	 * @return string
	 */
	public static function render_coupon( Coupon $coupon, array $settings = [] ): string {
		$html = '';

		$code   = $coupon->get_coupon_code();
		$amount = (float) $coupon->get_discount_amount();

		switch ( $coupon->get_discount_type() ) {
			case 'percent':
				$discount = $amount . '%';
				break;
			case 'fixed':
				$discount = learn_press_format_price( $amount );
				break;
			default:
				$discount = '';
				break;
		}

		$icon = file_get_contents( LP_ADDON_UPSELL_PATH . '/public/coupon-icon.svg' );

		$section = [
			'wrapper'     => '<li class="lp-coupon">',
			'image'       => $icon,
			'main'        => '<div class="lp-coupon__wrapper">',
			'description' => sprintf( '<p class="lp-coupon__desc">%s</p>', get_the_content( false, false, $coupon->get_id() ) ),
			'code'        => sprintf( '<h5>%s</h5>', $code ),
			'discount'    => sprintf( '<p>%s %s</p>', $discount, __( 'Off', 'learnpress-upsell' ) ),
			'main_end'    => '</div>',
			'button'      => sprintf( '<button class="lp-button lp-coupon-apply" data-code="%s" type="button">Apply</button>', $code ),
			'wrapper_end' => '</li>',
		];

		return Template::combine_components( $section );
	}
}
