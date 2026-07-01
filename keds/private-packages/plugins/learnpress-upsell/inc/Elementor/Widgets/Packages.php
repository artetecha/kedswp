<?php
/**
 * Class ListPackage
 *
 * @sicne 4.0.4
 * @version 1.0.0
 */
namespace LP_Addon_Upsell\Elementor\Widgets;

use LearnPress\Helpers\Template;
use LearnPress\Upsell\Package\Package;
use LearnPress\Upsell\TemplateHooks\ArchivePackage;
use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;

class Packages extends LPElementorWidgetBase {

    public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Packages', 'learnpress-upsell' );
		$this->name     = 'packages';
		$this->keywords = [ 'package' ];
		$this->add_style_depends( 'learnpress-package' );
		$this->add_script_depends( 'learnpress-package' );
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = require LP_ADDON_UPSELL_PATH . '/inc/Elementor/config/packages.php';
		parent::register_controls();
	}

	/**
	 * Show content of widget
	 *
	 * @return void
	 */
	protected function render() {
		try {
			$settings = $this->get_settings_for_display();
			$sort_in  = $settings['sort_in'] ?? 'post_date';
			$limit    = $settings['limit'] ?? 6;
			$order    = $settings['order_by'] ?? 'DESC';

			$packages = array();

			$query_args = array(
				'post_type'      => LP_PACKAGE_CPT,
				'post_status'    => array( 'publish' ),
				'orderby'        => $sort_in,
				'order'          => $order,
				'posts_per_page' => $limit,
			);

			$query = new \WP_Query();

			if ( empty( $query ) ) {
				return;
			}

			$packages = $query->query( $query_args );

			ob_start();
			if ( ! empty( $packages ) ) {
				foreach ( $packages as $package ) {
					$package = new Package( $package );
					echo ArchivePackage::instance()->render_package( $package );
				}
			} else {
				echo ArchivePackage::instance()->html_no_package_found();
			}

			$content = ob_get_clean();

			$slide_class = $slide_class_inner = '';
			$navigation  = [];

			if ( isset( $settings['layout'] ) && $settings['layout'] == 'slider' ) {
				$slide_class       = 'lp-upsell-glider-contain';
				$slide_class_inner = 'lp-upsell-glider';

				$arrow_slide = [];
				if ( isset( $settings['show_arrow'] ) && $settings['show_arrow'] == 'yes' ) {
					$arrow_slide = [
						'wrapper_prev'     => '<button aria-label="Previous" class="glider-prev">',
						'icon_prev'        => '<i class="lp-icon-arrow-left"></i>',
						'wrapper_prev_end' => '</button>',
						'wrapper_next'     => '<button aria-label="Next" class="glider-next">',
						'icon_next'        => '<i class="lp-icon-arrow-right"></i>',
						'wrapper_next_end' => '</button>',
					];
				}

				$pagination = '';
				if ( isset( $settings['paginations'] ) ) {
					switch ( $settings['paginations'] ) {
						case 'bullets':
							$pagination = '<div role="tablist" class="dots"></div>';
							break;
						case 'progress':
							$pagination = '<div class="progress-bar"><div class="progress-bar__inner"></div></div>';
							break;
						default:
							$pagination = '';
							break;
					}
				}

				$navigation = [
					'arrow_slide' => Template::combine_components( $arrow_slide ),
					'pagination'  => $pagination,
				];
			}

			$section = [
				'wrapper'           => '<div class="lp-packages-elementor ' . $slide_class . '">',
				'wrapper_inner'     => '<ul class="' . $slide_class_inner . '">',
				'content'           => $content,
				'wrapper_inner_end' => '</ul>',
				'navigation'        => Template::combine_components( $navigation ),
				'wrapper_end'       => '</div>',
			];

			echo Template::combine_components( $section );

		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
