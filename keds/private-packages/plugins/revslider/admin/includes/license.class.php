<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/
 * @copyright 2024 ThemePunch
 * @since	  6.2.0
 */

if(!defined('ABSPATH')) exit();

class RevSliderLicense extends RevSliderFunctions {
	/**
	 * Activate the Plugin through the ThemePunch Servers
	 **/
	public function activate_plugin($code){
		$rstrack = new RevSliderTracking();
		$rstrack->_run(true);

		$rslb = RevSliderGlobals::instance()->get('RevSliderLoadBalancer');
		$data = [
			'code'		=> urlencode($code),
			'version'	=> urlencode(RS_REVISION),
			'product'	=> urlencode(RS_PLUGIN_SLUG),
			'addition'	=> apply_filters('revslider_activate_plugin_info_addition', [])
		];
		
		$response	  = $rslb->call_url('activate.php', $data, 'updates');
		$version_info = wp_remote_retrieve_body($response);
		
		if(is_wp_error($version_info)) return false;
		if($version_info == 'valid'){
			$this->update_option(['system', 'valid'], 'true');
			$this->update_option(['system', 'license'], $code);
			$this->update_option(['system', 'deregister'], 'false');

			return true;
		}
		if($version_info == 'exist') return 'exist';
		if($version_info == 'banned') return 'banned';
		
		return false;
	}
	
	
	/**
	 * Deactivate the Plugin through the ThemePunch Servers
	 **/
	public function deactivate_plugin(){
		$rstrack = new RevSliderTracking();
		$rstrack->_run(false);

		$rslb = RevSliderGlobals::instance()->get('RevSliderLoadBalancer');
		$code = $this->get_options(['system', 'license'], '');
		$data = [
			'code'		=> urlencode($code),
			'product'	=> urlencode(RS_PLUGIN_SLUG),
			'addition'	=> apply_filters('revslider_deactivate_plugin_info_addition', [])
		];
		
		$res = $rslb->call_url('deactivate.php', $data, 'updates');
		$vi	 = wp_remote_retrieve_body($res);
		
		if(is_wp_error($vi) || $vi != 'valid') return false;
	
		$this->update_option(['system', 'valid'], 'false');
		$this->update_option(['system', 'license'], '');
		//$this->update_option(['system', 'deregister'], 'true');

		return true;
	}
}