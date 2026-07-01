<?php
/**
 * Class PackageElementorHandler
 *
 * Hook to register widgets, dynamic tags, ... for LearnPress Elementor handler.
 *
 * @since 4.0.4
 * @version 1.0.0
 */
namespace LP_Addon_Upsell\Elementor;

use LearnPress\Helpers\Singleton;
use LP_Addon_Upsell\Elementor\Widgets\ListPackageOneCourse;
use LP_Addon_Upsell\Elementor\Widgets\Packages;

class PackageElementorHandler {
    use Singleton;

	/**
	 * Hooks to register widgets, dynamic tags, ...
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'lp/elementor/widgets', [ $this, 'register_widgets' ] );
	}

    /**
	 * @param $lp_widgets array
	 * @return mixed
	 */
	public function register_widgets( array $lp_widgets ): array {
		include_once LP_ADDON_UPSELL_PATH . '/inc/Elementor/Widgets/ListPackageOneCourse.php';
		include_once LP_ADDON_UPSELL_PATH . '/inc/Elementor/Widgets/Packages.php';

		$lp_widgets['list-package']   = ListPackageOneCourse::class;
		$lp_widgets['packages']       = Packages::class;

		return $lp_widgets;
	}
}