<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/
 * @copyright 2024 ThemePunch
 */

if(!defined('ABSPATH')) exit();

class RevSliderAddons extends RevSliderFunctions {
	//private $addon_version_required = '2.0.0'; //this holds the globally needed addon version for the current RS version
	private $addons_path			= '/revslider/addons/';
	private $addons_basedir			= '';
	private $addons_baseurl			= '';
	private $addons_list			= [];
	private $addons_list_short		= [];

	//Minimum addon version required by the CURRENT core version (keys = official addon slugs from the rs-addons list). Baseline 7.0.0 = "any SR7 build is fine"; raise an entry only when a core change drops backward compatibility for that addon. Alphabetical, one line per official addon.
	private $addon_version_required = [
		'revslider-404-addon'				=> '7.0.0',
		'revslider-aitranslate-addon'		=> '7.0.0',
		'revslider-backup-addon'			=> '7.0.0',
		'revslider-beforeafter-addon'		=> '7.0.0',
		'revslider-bubblemorph-addon'		=> '7.0.0',
		'revslider-carouselx-addon'			=> '7.1.0', //the harmonized core dropped carouselx's hardcoded 3dshowcase/fade support - it now lives in the addon's srCarExt hooks, so an older carouselx renders wrong (Track 2 R1f). Force the update.
		'revslider-charts-addon'			=> '7.0.0',
		'revslider-commerce-addon'			=> '7.0.0',
		'revslider-depthforge-addon'		=> '7.0.0',
		'revslider-distortion-addon'		=> '7.0.0',
		'revslider-domainswitch-addon'		=> '7.0.0',
		'revslider-duotonefilters-addon'	=> '7.0.0',
		'revslider-explodinglayers-addon'	=> '7.0.0',
		'revslider-featured-addon'			=> '7.0.0',
		'revslider-filmstrip-addon'			=> '7.1.1', //7.1.0 had a regression (blank, no error) + needs SR7.t() from core 7.1.1; force the update
		'revslider-fluiddynamics-addon'		=> '7.0.0',
		'revslider-gallery-addon'			=> '7.0.0',
		'revslider-hovermorph-addon'		=> '7.0.0',
		'revslider-login-addon'				=> '7.0.0',
		'revslider-lottie-addon'			=> '7.0.0',
		'revslider-maintenance-addon'		=> '7.0.0',
		'revslider-mousetrap-addon'			=> '7.0.0',
		'revslider-paintbrush-addon'		=> '7.0.0',
		'revslider-panorama-addon'			=> '7.0.0',
		'revslider-particles-addon'			=> '7.0.0',
		'revslider-particlewave-addon'		=> '7.0.0',
		'revslider-polyfold-addon'			=> '7.0.0',
		'revslider-prevnextposts-addon'		=> '7.0.0',
		'revslider-refresh-addon'			=> '7.0.0',
		'revslider-relposts-addon'			=> '7.0.0',
		'revslider-revealer-addon'			=> '7.0.0',
		'revslider-scrollvideo-addon'		=> '7.0.0',
		'revslider-shapeburst-addon'		=> '7.0.6',
		'revslider-sharing-addon'			=> '7.0.0',
		'revslider-slicey-addon'			=> '7.0.0',
		'revslider-snow-addon'				=> '7.0.0',
		'revslider-spectrumlines-addon'		=> '7.0.0',
		'revslider-sunbeam-addon'			=> '7.0.0',
		'revslider-thecluster-addon'		=> '7.0.0',
		'revslider-transitionpack-addon'	=> '7.0.0',
		'revslider-typewriter-addon'		=> '7.0.0',
		'revslider-weather-addon'			=> '7.0.0',
		'revslider-whiteboard-addon'		=> '7.0.0',
	];

	/**
	 * @var RevSliderLoadBalancer
	 */
	private $rslb;
	
	public function __construct(){
		$upload_dir = wp_upload_dir();
		$this->addons_basedir = $upload_dir['basedir'] . $this->addons_path;
		$this->addons_baseurl = $upload_dir['baseurl'] . $this->addons_path;

		$this->rslb = RevSliderGlobals::instance()->get('RevSliderLoadBalancer');

		add_action('revslider_update_all_options', array($this, 'on_update_all_options'), 10, 2);
	}

	/**
	 * clear the addon list
	 */
	public function clear_addon_list(){
		$this->addons_list = [];
		$this->addons_list_short = [];
	}

	/**
	 * clear the addon list on rs-addons option change
	 *
	 * @param array $options
	 * @param string $field
	 */
	public function on_update_all_options($options, $field){
		if('rs-addons' === $field){
			$this->clear_addon_list();
		}
	}
	
	/**
	 * get all the addons with information
	 *
	 * @param bool $short return only the short list
	 * @return array
	 **/
	public function get_addon_list($short = false){
		if(!function_exists('get_plugins')) require_once ABSPATH . 'wp-admin/includes/plugin.php';
		
		if(!$short && !empty($this->addons_list)){
			return $this->addons_list;
		}
		if($short && !empty($this->addons_list_short)){
			return $this->addons_list_short;
		}

		$addons	= $this->get_options(['addons'], [], false, 'rs-addons');
		$addons	= (array)$addons;
		$addons = array_reverse($addons, true);
		if(empty($addons)) return $addons;

		$plugins = get_plugins();

		foreach($addons as $k => $addon){
			if(!is_object($addon)) continue;

			if(array_key_exists($addon->slug.'/'.$addon->slug.'.php', $plugins)){
				$addons[$k]->full_title	= $plugins[$addon->slug.'/'.$addon->slug.'.php']['Name'];
				$addons[$k]->active		= is_plugin_active($addon->slug.'/'.$addon->slug.'.php');
				$addons[$k]->installed	= $plugins[$addon->slug.'/'.$addon->slug.'.php']['Version'];
			}else{
				$addons[$k]->active	 = false;
				$addons[$k]->installed = false;
			}
		}

		if(!$short) {
			$this->addons_list = $addons;
			return $addons;
		}

		$_addons = [];
		foreach($addons as $k => $addon){
			if(!is_object($addon)) continue;

			$k = str_replace(['revslider-', '-addon'], '', $k);
			$addon		 = apply_filters('sr_get_addon_data', $addon, $addon->slug);
			$addon->slug = str_replace(['revslider-', '-addon'], '', $k);
			$_addons[$k] = $addon;
		}

		$this->addons_list_short = $_addons;

		return $_addons;
	}

	public function get_addon_data($handle, $short = false){
		$list		= $this->get_addon_list($short);
		$_handle	= str_replace(['revslider-', '-addon'], '', $handle);
		$addon		= $this->get_val($list, $_handle, false);

		return $addon;
	}
	
	/**
	 * get a specific addon version
	 **/
	public function get_addon_version($handle){
		$list = $this->get_addon_list();
		return $this->get_val($list, [$handle, 'installed'], false);
	}

	/**
	 * check if any addon is below version x (for RS6.0 this is version 2.0)
	 * if yes give a message that tells to update
	 **/
	public function check_addon_version(){
		$rs_addons	= $this->get_addon_list();
		$update		= [];
		
		if(empty($rs_addons)) return $update;
	
		foreach($rs_addons ?? [] as $handle => $addon){
			$installed = $this->get_val($addon, 'installed');
			if(trim($installed) === '') continue;
			if($this->get_val($addon, 'active', false) === false) continue;
			
			$version = $this->get_val($this->addon_version_required, $handle, false);
			if($version !== false && version_compare($installed, $version, '<')){
				$handle = str_replace(['revslider-', '-addon'], '', $handle);
				$update[$handle] = (version_compare($version, $this->get_val($addon, 'available'), '>')) ? $version : $this->get_val($addon, 'available');
			}
		}
		
		return $update;
	}
	
	/**
	 * Install Add-On/Plugin
	 *
	 * @since 6.0
	 */

	public function install_addon($addon, $force = false){
		if(empty($addon) || 0 !== strpos($addon, 'revslider-')) return false;
		
		if(!function_exists('get_plugins')) require_once ABSPATH . 'wp-admin/includes/plugin.php';
		
		if($this->_truefalse($this->get_options(['system', 'valid'], 'false')) !== true) return __('Please activate Slider Revolution', 'revslider');
		
		//check if downloaded already
		$plugins	= get_plugins();
		$addon_path = $addon.'/'.$addon.'.php';

		if(!array_key_exists($addon_path, $plugins) || $this->_truefalse($force) === true || !file_exists(WP_PLUGIN_DIR.'/'.$addon_path)){
			//download if nessecary
			$this->download_addon($addon);
		}

		//activate
		return $this->activate_addon($addon_path);
	}
	
	
	/**
	 * Download Add-On/Plugin
	 *
	 * @since    1.0.0
	 */
	public function download_addon($addon){
		if($this->_truefalse($this->get_options(['system', 'valid'], 'false')) !== true) return __('Please activate Slider Revolution', 'revslider');
		
		$plugin_slug	= basename($addon);
		if(0 !== strpos($plugin_slug, 'revslider-')) die( '-1' );

		$code	= $this->get_options(['system', 'license'], '');
		$rattr	= [
			'code'		=> urlencode($code),
			'version'	=> urlencode(RS_REVISION),
			'product'	=> urlencode(RS_PLUGIN_SLUG),
			'type'		=> urlencode($plugin_slug)
		];
		$get = $this->rslb->call_url('addons/'.$plugin_slug.'/download.php', $rattr);
		if(is_wp_error($get)) return false;
		
		if($get && $get['body'] != 'invalid' && wp_remote_retrieve_response_code($get) == 200){
			$upload_dir	= wp_upload_dir();
			$file		= $upload_dir['basedir']. '/revslider/templates/' . $plugin_slug . '.zip';
			@mkdir(dirname($file), 0777, true);
			$ret		= @file_put_contents($file, $get['body']);
			
			require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
			require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
			if(!function_exists('WP_Filesystem')) require_once ABSPATH . 'wp-admin/includes/file.php';
			
			$fsd = new WP_Filesystem_Direct(false);
			WP_Filesystem();
			
			global $wp_filesystem;

			$upload_dir	= wp_upload_dir();
			$d_path		= WP_PLUGIN_DIR;
			$fsd->rmdir($d_path . '/' . $plugin_slug, true); //remove the addon folder if exists

			$unzipfile	= unzip_file($file, $d_path);

			if(is_wp_error($unzipfile)){
				define('FS_METHOD', 'direct'); //lets try direct. 

				WP_Filesystem();  //WP_Filesystem() needs to be called again since now we use direct !

				//@chmod($file, 0775);
				$unzipfile = unzip_file($file, $d_path);
				if(is_wp_error($unzipfile)){
					$d_path = WP_PLUGIN_DIR;
					$unzipfile = unzip_file($file, $d_path);
					
					if(is_wp_error($unzipfile)){
						$f		= basename($file);
						$d_path = str_replace($f, '', $file);

						$unzipfile = unzip_file($file, $d_path);
					}
				}
			}
			
			@unlink($file);
			
			$this->flush_wp_cache();
			$this->clear_addon_list();

			return true;
		}

		return false;
	}
	
	/**
	 * Activates Installed Add-On/Plugin
	 */
	public function activate_addon($candidate, $network_wide = null){
		if(empty($candidate) || 0 !== strpos($candidate, 'revslider-')) return false;
		
		// Determine network_wide default
		if($network_wide === null) $network_wide = is_multisite() && is_network_admin();
		
		// If already inactive, report cleanly
		$isActive = true;
		if(!is_plugin_active($candidate) || (is_multisite() && !is_plugin_active_for_network($candidate))){
			// Activate
			$result = activate_plugins($candidate, false, (bool) $network_wide);
			if (is_wp_error($result)) return false;
			
			// Check result
			$isActive = is_plugin_active($candidate) || (is_multisite() && is_plugin_active_for_network($candidate));
		}
		
		$this->clear_addon_list();

		return $isActive;
	}

	/**
	 * Deactivate an addon by handle or plugin path.
	 *
	 * @param string      $addon         Handle like 'revslider-404-addon' OR full plugin path 'revslider-404-addon/index.php'
	 * @param null|bool   $network_wide  Pass true/false to force network-wide deactivation on multisite. NULL = auto.
	 * @return bool
	 */
	public function deactivate_addon($addon, $network_wide = null){
		if(empty($addon) || 0 !== strpos($addon, 'revslider-')) return false;
		
		$candidate = $this->find_addon_path($addon);
		if($candidate === null) return false;

		// Determine network_wide default
		if($network_wide === null) $network_wide = is_multisite() && is_network_admin();

		// If already inactive, report cleanly
		if(!is_plugin_active($candidate) && !(is_multisite() && is_plugin_active_for_network($candidate))) return true;

		// Deactivate
		deactivate_plugins($candidate, false, (bool) $network_wide);

		// Check result
		$stillActive = is_plugin_active($candidate) || (is_multisite() && is_plugin_active_for_network($candidate));

		$this->clear_addon_list();

		return ! $stillActive;
	}


	public function find_addon_path($addon, $status = 'activated'){
		if(!function_exists('get_plugins')) require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// If it already looks like a plugin path and the file exists, use it directly
		$candidate = null;
		if(strpos($addon, '/') !== false && str_ends_with(strtolower($addon), '.php')){
			$full = trailingslashit(WP_PLUGIN_DIR) . $addon;
			if(file_exists($full)) $candidate = $addon; // already a valid plugin basename
		}

		// Otherwise, treat input as "handle" (e.g., 'revslider-404-addon') and try to resolve
		if(!$candidate){
			$handle = strtolower(trim($addon));

			// Helper to slugify strings similar to WP-style slugs
			$slugify = function ($s) {
				$s = strtolower($s);
				$s = preg_replace('~[^\pL\d]+~u', '-', $s);
				$s = trim($s, '-');
				$s = preg_replace('~[^-\w]+~', '', $s);
				return $s;
			};

			$plugins   = get_plugins();
			$found = 0;

			foreach($plugins ?? [] as $base => $data){
				// $base e.g. "revslider-404-addon/index.php"
				$dir  = strtolower(dirname($base));                 // "revslider-xxx-addon"
				$file = strtolower(basename($base, '.php'));        // "index" (or "revslider-xxx-addon")

				// Scoring heuristics (prefer exact dir match, then file, then TextDomain, then Name slug, then "contains")
				if($dir === $handle)                      $found = max($found, 100);
				if($file === $handle)                     $found = max($found, 90);

				if($found > 0 && is_plugin_active($base)){
					$plugin_status = is_plugin_active($base);

					if($plugin_status !== true && $status === 'activated') continue;
					if($plugin_status === true && $status === 'deactivated') continue;
					
					$candidate = $base;
					break;
				}
			}
		}

		return $candidate;

	}

	public function _get_media_url($handle, $download = true){
		return $this->_check_file_path($handle, true, $download);
	}

	/**
	 * check if image was uploaded, if yes, return path or url
	 */
	public function _check_file_path($image, $url = false, $download = true){
		if(!wp_mkdir_p($this->addons_basedir)) return $image;
		
		$base_url = ($url) ? $this->addons_baseurl : $this->addons_basedir;
		$file     = $this->addons_basedir . $image;
		if(file_exists($file)){
			if(!$this->check_checksum($image, $file)) {
				$this->rslb->download_url( $image, $file, 'updates', false, $this->addons_basedir);
			}
			return $base_url . $image;
		}
		
		if($download !== true) return $image;
		
		$this->rslb->download_url($image, $file, 'updates', false, $this->addons_basedir);

		return (file_exists($file)) ? $base_url . $image : $image;
	}

	public function check_checksum($image, $file){
		$addons_list = $this->get_addon_list(true);
		foreach($addons_list ?? [] as $key => $addon){
			//if($this->get_val($addon, 'background') === $image) return md5_file($file) !== $this->get_val($addon, 'background_md5');
			if($this->get_val($addon, ['logo', 'img_file']) === $image) return md5_file($file) === $this->get_val($addon, ['logo', 'img_md5']);
			if($this->get_val($addon, 'banner_file') === $image) return md5_file($file) === $this->get_val($addon, 'banner_md5');
		}

		return false; //image not found
	}

	/**
	 * get the addons that need to be migrated from old to new addon slugs during upgrade
	 * 
	 * @return array
	 */
	public function get_addons_to_migrate(){
		return [
			'revslider-domain-switch-addon' => 'revslider-domainswitch-addon',
			'revslider-prevnext-posts-addon' => 'revslider-prevnextposts-addon',
			'revslider-rel-posts-addon' => 'revslider-relposts-addon',
			'revslider-liquideffect-addon' => 'revslider-distortion-addon'
		];
	}
	
	/**
	 * get the addons that need to be removed during upgrade
	 * 
	 * @return array
	 */
	public function get_addons_to_remove(){
		return [
			'revslider-backup-addon',
		];
	}

	/**
	 * flush all cache
	 */
	public function flush_wp_cache(){
		wp_clean_plugins_cache(true);
		parent::flush_wp_cache();
	}
}
