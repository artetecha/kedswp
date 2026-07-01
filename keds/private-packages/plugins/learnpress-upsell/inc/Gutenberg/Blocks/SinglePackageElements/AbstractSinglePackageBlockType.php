<?php
namespace LearnPress\Upsell\Gutenberg\Blocks\SinglePackageElements;

use LearnPress\Models\UserModel;
use LearnPress\Upsell\Gutenberg\Blocks\AbstractPackageBlockType;
use LearnPress\Upsell\Package\Package;
use WP_Block;

/**
 * Class AbstractSinglePackageBlockType
 *
 * Handle register, render block template
 */
abstract class AbstractSinglePackageBlockType extends AbstractPackageBlockType {
	/**
	 * Get package model
	 *
	 * @param $attributes
	 * @param WP_Block|null $block
	 *
	 * @return false|Package
	 */
	public function get_package( $attributes, $block = null ) {
		if ( $block instanceof WP_Block ) {
			$package = $block->context['package'] ?? false;
			if ( $package instanceof Package ) {
				return $package;
			}
		}

		$package_id = ! empty( $attributes['packageId'] ) ? (int) $attributes['packageId'] : get_the_ID();
		$package    = new Package( $package_id );
		if ( ! $package->exists() ) {
			return false;
		}
		return $package;
	}

	/**
	 * Get user model
	 *
	 * @return false|UserModel
	 */
	public function get_user() {
		$user_id = get_current_user_id();
		return UserModel::find( $user_id, true );
	}
}
