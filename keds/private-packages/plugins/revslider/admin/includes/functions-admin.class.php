<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/
 * @copyright 2024 ThemePunch
 */
 
if(!defined('ABSPATH')) exit();

class RevSliderFunctionsAdmin extends RevSliderFunctions {
	
	/**
	 * get the full object of: 
	 * +- Slider Templates
	 * +- Created Slider
	 * +- Object Library Images
	 * - Object Library Videos
	 * +- SVG
	 * +- Font Icons
	 * - layers
	 **/
	public function get_full_library($include = ['all'], $tmp_slide_uid = [], $refresh_from_server = false, $get_static_slide = false, $page = false){
		$include	= (array)$include;
		$template	= new RevSliderTemplate();
		/* @var RevSliderObjectLibrary $library */
		$library	= RevSliderGlobals::instance()->get('RevSliderObjectLibrary');
		$slide		= new RevSliderSlide();
		$object		= [];
		$tmp_slide_uid = ($tmp_slide_uid !== false) ? (array)$tmp_slide_uid : [];

		if($refresh_from_server === true){ //refresh list from server
			if(in_array('all', $include) || in_array('moduletemplates', $include)){ 
				$template->_get_template_list(true);
				$object['moduletemplates']['tags'] = $object['moduletemplates']['tags'] ?? $template->get_template_categories();
				asort($object['moduletemplates']['tags']);
			}
			if(in_array('all', $include) || in_array('layers', $include) || in_array('videos', $include) || in_array('images', $include) || in_array('objects', $include)){
				$library->_get_list(true);
			}
			if(in_array('all', $include) || in_array('layers', $include)){
				$object['layers']['tags'] = $object['layers']['tags'] ?? $library->get_objects_categories('4');
				asort($object['layers']['tags']);
			}
			if(in_array('all', $include) || in_array('videos', $include)){
				$object['videos']['tags'] = $object['videos']['tags'] ?? $library->get_objects_categories('3');
				asort($object['videos']['tags']);
			}
			if(in_array('all', $include) || in_array('images', $include)){
				$object['images']['tags'] = $object['images']['tags'] ?? $library->get_objects_categories('2');
				asort($object['images']['tags']);
			}
			if(in_array('all', $include) || in_array('objects', $include)){
				$object['objects']['tags'] = $object['objects']['tags'] ?? $library->get_objects_categories('1');
				asort($object['objects']['tags']);
			}
			$object = apply_filters('revslider_get_full_library_refresh', $object, $include, $tmp_slide_uid, $refresh_from_server, $get_static_slide, $this);
		}

		if(in_array('moduletemplates', $include) || in_array('all', $include))		$object['moduletemplates']['items']		 = $object['moduletemplates']['items'] ?? $template->get_tp_template_sliders_for_library($refresh_from_server, $page);
		if(in_array('moduletemplateslides', $include) || in_array('all', $include))	$object['moduletemplateslides']['items'] = $object['moduletemplateslides']['items'] ?? $template->get_tp_template_slides_for_library($tmp_slide_uid);
		if(in_array('modules', $include) || in_array('all', $include))				$object['modules']['items']				 = $object['modules']['items'] ?? $this->get_slider_overview();
		if(in_array('moduleslides', $include) || in_array('all', $include))			$object['moduleslides']['items']		 = $object['moduleslides']['items'] ?? $slide->get_slides_for_library($tmp_slide_uid, $get_static_slide);
		if(in_array('svgs', $include) || in_array('all', $include))					$object['svgs']['items']				 = $object['svgs']['items'] ?? $library->get_svg_sets_full();
		if(in_array('svgcustom', $include) || in_array('all', $include))			$object['svgcustom']['items']			 = $object['svgcustom']['items'] ?? $library->get_custom_svgs();
		if(in_array('icons', $include) || in_array('all', $include))				$object['icons']['items']				 = $object['icons']['items'] ?? $library->get_font_icons();
		if(in_array('layers', $include) || in_array('all', $include))				$object['layers']['items']				 = $object['layers']['items'] ?? $library->load_objects('4');
		if(in_array('videos', $include) || in_array('all', $include))				$object['videos']['items']				 = $object['videos']['items'] ?? $library->load_objects('3');
		if(in_array('images', $include) || in_array('all', $include))				$object['images']['items']				 = $object['images']['items'] ?? $library->load_objects('2');
		if(in_array('objects', $include) || in_array('all', $include))				$object['objects']['items']				 = $object['objects']['items'] ?? $library->load_objects('1');

		return apply_filters('revslider_get_full_library', $object, $include, $tmp_slide_uid, $refresh_from_server, $get_static_slide, $this);
	}
	
	
	/**
	 * get the short library with categories and how many elements exist
	 **/
	public function get_short_library($sliders = false){
		$template = new RevSliderTemplate();
		/* @var RevSliderObjectLibrary $library */
		$library = RevSliderGlobals::instance()->get('RevSliderObjectLibrary');
		$sliders = ($sliders === false) ? $this->get_slider_overview() : $sliders;
		
		$slider_cat = [];
		foreach($sliders ?? [] as $slider){
			$tags = $this->get_val($slider, 'tags', []);
			foreach($tags ?? [] as $tag){
				if(trim($tag) !== '' && !isset($slider_cat[$tag])) $slider_cat[$tag] = ucwords($tag);
			}
		}

		$m_templates = $template->get_template_categories();
		$svgs		 = $library->get_svg_categories();
		$icons		 = $library->get_font_tags();
		$objects	 = $library->get_objects_categories('1');
		asort($m_templates);
		asort($slider_cat);
		asort($svgs);
		asort($icons);
		asort($objects);
		$tags		 = [
			'moduletemplates' => ['tags' => $m_templates],
			'modules'	=> ['tags' => $slider_cat],
			'svgs'		=> ['tags' => $svgs],
			'icons'		=> ['tags' => $icons],
			'layers'	=> ['tags' => $library->get_objects_categories('4')],
			'videos'	=> ['tags' => $library->get_objects_categories('3')],
			'images'	=> ['tags' => $library->get_objects_categories('2')],
			'objects'	=> ['tags' => $objects]
		];

		$custom = $library->get_custom_tags();
		foreach($custom ?? [] as $tag_name => $tag_value){
			$tags[$tag_name] = ['tags' => $tag_value];
		}
		
		return apply_filters('revslider_get_short_library', $tags, $library, $this);
	}
	
	/**
	 * get the elements library
	 **/
	public function get_elements_library(){
		/* @var RevSliderObjectLibrary $library */
		$library = RevSliderGlobals::instance()->get('RevSliderObjectLibrary');
		$icons	 = $library->get_font_tags();
		asort($icons);
		$tags = [
			'icons'	    => ['tags' => $icons],
			'layers'	=> ['tags' => $library->get_objects_categories('4')],
			'videos'	=> ['tags' => $library->get_objects_categories('3')],
			'images'	=> ['tags' => $library->get_objects_categories('2')]
		];
		return $tags;
	}
	
	/**
	 * Get Sliders data for the overview page
	 **/
	public function get_slider_overview(){
		global $SR_GLOBALS;
		$SR_GLOBALS['data_init'] = false;

		$rs_folder	= new RevSliderFolder();
		$rs_slider	= new RevSliderSlider();
		
		$_sliders	= $rs_slider->get_sliders(false);
		$folders	= $rs_folder->get_folders();		
		$_sliders 	= array_merge($_sliders, $folders);
		$data		= [];
		
		$updv6		= new RevSliderPluginUpdateV6();
		$_slider_ids_v6 = $updv6->slider_v6_has_no_v7(); //check if v6 sliders are not migrated properly
		
		$_sliders_v6 = [];
		if(!empty($_slider_ids_v6)){
			$SR_GLOBALS['v6'] = true;
			foreach($_slider_ids_v6 as $v6_slider_id){
				$rs_slider	= new RevSliderSlider();
				$rs_slider->init_by_id($v6_slider_id);
				$_sliders_v6[]	= $rs_slider;
			}
			$SR_GLOBALS['v6'] = false;
		}

		$slider_combined = [
			['sliders' => $_sliders, 'type' => 'v7', 'ids' => 'all'],
			['sliders' => $_sliders_v6, 'type' => 'v6', 'ids' => $_slider_ids_v6]
		];

		foreach($slider_combined as $sliders){
			if(empty($sliders['sliders'])) continue;
			
			$SR_GLOBALS['v6'] = ($sliders['type'] === 'v6') ? true : false;

			$rs_slide	= new RevSliderSlide();
			$_slides_raw = $rs_slide->get_all_slides_raw($sliders['ids']);
			$slides_raw = $this->get_val($_slides_raw, 'first_slides', []);
			$slides_ids = $this->get_val($_slides_raw, 'slide_ids', []);
			
			foreach($sliders['sliders'] ?? [] as $k => $slider){
				$slide_ids	= [];
				$slides		= [];
				$sid		= $slider->get_id();
				foreach($slides_raw ?? [] as $s => $r){
					if($r->get_slider_id() !== $sid) continue;
					
					foreach($slides_ids as $_s => $_sv){
						if($this->get_val($_sv, 'slider_id') === $sid){
							$slide_ids[] = $this->get_val($_sv, 'id');
							unset($slides_ids[$_s]);
						}
					}
					$slides[] = $r;
					unset($slides_raw[$s]);
				}
				if(empty($slide_ids)) $slide_ids = false;
				
				$slides = (empty($slides)) ? false : $slides;
				
				$slider->init_layer = false;
				if($SR_GLOBALS['v6']){
					$_slider = $slider->get_overview_data_v6(false, $slides, $slide_ids); 
				}else{
					$_slider = $slider->get_overview_data(false, $slides, $slide_ids); 
				}
				$data[] = $_slider;
				unset($sliders[$k]);
			}
		}

		$SR_GLOBALS['v6'] = false;
		$SR_GLOBALS['data_init'] = true;
		
		return $data;
	}
	
	
	/**
	 * insert custom animations
	 */
	public function insert_animation($animation, $type){
		$handle = $this->get_val($animation, 'name', false);
		$result = false;
		
		if($handle !== false && trim($handle) !== ''){
			global $wpdb;
			
			//check if handle exists
			$arr = [
				'handle'	=> $this->get_val($animation, 'name'),
				'params'	=> json_encode($animation),
				'settings'	=> $type
			];
			
			$result = $wpdb->insert($wpdb->prefix . RevSliderFront::TABLE_LAYER_ANIMATIONS, $arr);
		}

		return ($result) ? $wpdb->insert_id : $result;
	}
	
	
	/**
	 * update custom animations
	 */
	public function update_animation($animation_id, $animation, $type){
		global $wpdb;
		
		$arr = [
			'handle'	=> $this->get_val($animation, 'name'),
			'params'	=> json_encode($animation),
			'settings'	=> $type
		];
		
		$result = $wpdb->update($wpdb->prefix . RevSliderFront::TABLE_LAYER_ANIMATIONS, $arr, ['id' => $animation_id]);
		
		return ($result) ? $animation_id : $result;
	}
	
	
	/**
	 * delete custom animations
	 * @param int $animation_id
	 */
	public function delete_animation($animation_id){
		global $wpdb;
		
		return $wpdb->delete($wpdb->prefix . RevSliderFront::TABLE_LAYER_ANIMATIONS, ['id' => $animation_id]);
	}
	
	
	/**
	 * @since: 5.3.0
	 * create a page with revslider shortcodes included
	 **/
	public static function create_slider_page($added, $modals = [], $additions = []){
		global $wp_version;
		
		$new_page_id = 0;
		
		if(!is_array($added)) return apply_filters('revslider_create_slider_page', $new_page_id, $added);
		
		$f = RevSliderGlobals::instance()->get('RevSliderFunctions');
		$content = '';
		
		//get alias of all new Sliders that got created and add them as a shortcode onto a page
		foreach($added ?? [] as $sid){
			$slider = new RevSliderSlider();
			$slider->init_by_id($sid);
			$alias = $slider->get_alias();
			if(empty($alias)) continue;
			
			$usage		= isset($modals[$sid]) ? ' usage="modal"' : '';
			$addition	= (isset($additions[$sid])) ? ' ' . $additions[$sid] : '';
			if(strpos($addition, 'usage=\"modal\"') !== false) $usage = ''; //remove as not needed two times
			
			if(version_compare($wp_version, '5.0', '>=')){ //add gutenberg code
				$ov_data = $slider->get_overview_data();
				$title	 = $slider->get_val($ov_data, 'title', '');
				$img	 = $slider->get_val($ov_data, ['bg', 'src'], '');
				$wrap_addition	= ($img !== '') ? ',"sliderImage":"'.$img.'"' : '';
				$div_addition	= ($title !== '') ? ' data-slidertitle="'.$title.'"' : '';
				
				$zindex_pos = strpos($addition, 'zindex=\"');
				if($zindex_pos !== false){
					$zindex = substr($addition, $zindex_pos + 9, strpos($addition, '\"', $zindex_pos + 9) - ($zindex_pos + 9));
					$div_addition .= ' style="z-index:'.$zindex.';"';
					$wrap_addition .= ',"zindex":"'.$zindex.'"';
				}

				$div_addition .= ' data-modal="'.(empty($usage) ? 'false' : 'true').'"';
				
				$content .= '<!-- wp:themepunch/revslider {"checked":true'.$wrap_addition.'} -->'."\n";
				$content .= '<div class="wp-block-themepunch-revslider revslider" '.$div_addition.'>';
			}
			
			$content .= '[rev_slider alias="'.$alias.'"'.$usage.$addition.'][/rev_slider]'; //this way we will reorder as last comes first
			
			if(version_compare($wp_version, '5.0', '>=')){ //add gutenberg code
				$content .= '</div>'."\n".'<!-- /wp:themepunch/revslider -->'."\n";
			}
		}
		
		if($content !== ''){
			$page_id = $f->get_options(['other', 'page-id'], 1);
			$new_page_id = wp_insert_post(
				[
					'post_title'    => wp_strip_all_tags('RevSlider Page '.$page_id), //$title
					'post_content'  => $content,
					'post_type'   	=> 'page',
					'post_status'   => 'draft',
					'page_template' => '../public/views/revslider-page-template.php'
				]
			);
			
			if(is_wp_error($new_page_id)) $new_page_id = 0; //fallback to 0
			
			$page_id++;
			$f->update_option(['other', 'page-id'], $page_id);
		}
		
		return apply_filters('revslider_create_slider_page', $new_page_id, $added);
	}
	
	/**
	 * add notices from ThemePunch
	 * @since: 4.6.8
	 * @return array
	 */
	public function get_notices(){
		$_n = [];
		$notices = (array)$this->get_options(['overview', 'notices'], []);
		$rs_valid = $this->_truefalse($this->get_options(['system', 'valid'], 'false'));
		
		if(empty($notices)) return $_n;

		$n_discarted = $this->get_options(['overview', 'notices-dc'], []);
		foreach($notices ?? [] as $notice){
			if(in_array($notice->code, $n_discarted)) continue;
			if(isset($notice->version) && version_compare($notice->version, RS_REVISION, '<=')) continue;
			if(isset($notice->registered)){ //if this is set, only show the notice if the plugin state is the same
				$registered = $this->_truefalse($notice->registered);
				if($registered !== $rs_valid) continue;
			}
			if(isset($notice->show_until) && $notice->show_until !== '0000-00-00 00:00:00'){
				if(strtotime($notice->show_until) < time()) continue;
			}

			$_n[] = $notice;
		}
		
		return $_n;
	}
	
	/**
	 * returns an object of current system values
	 **/
	public function get_system_requirements(){
		global $wpdb;
		$dir	= wp_upload_dir();
		$basedir = $this->get_val($dir, 'basedir').'/';
		$ml		= ini_get('memory_limit');
		$mlb	= wp_convert_hr_to_bytes($ml);
		$umf	= ini_get('upload_max_filesize');
		$umfb	= wp_convert_hr_to_bytes($umf);
		$pms	= ini_get('post_max_size');
		$pmsb	= wp_convert_hr_to_bytes($pms);
		$map	= $wpdb->get_row("SHOW VARIABLES LIKE 'max_allowed_packet';");
		$map	= $this->get_val($map, 'Value', 0);
		

		$mlg  = ($mlb >= 268435456) ? true : false;
		$umfg = ($umfb >= 33554432) ? true : false;
		$pmsg = ($pmsb >= 33554432) ? true : false;
		$mapg = ($map >= 16777216) ? true : false;
		
		return [
			'memory_limit' => [
				'has' => size_format($mlb),
				'min' => size_format(268435456),
				'good'=> $mlg
			],
			'upload_max_filesize' => [
				'has' => size_format($umfb),
				'min' => size_format(33554432),
				'good'=> $umfg
			],
			'post_max_size' => [
				'has' => size_format($pmsb),
				'min' => size_format(33554432),
				'good'=> $pmsg
			],
			'max_allowed_packet' => [
				'has' => size_format($map),
				'min' => size_format(16777216),
				'good'=> $mapg
			],
			'upload_folder_writable'	=> wp_is_writable($basedir),
			'zlib_enabled'				=> function_exists('gzcompress') && function_exists('gzuncompress'),
			'object_library_writable'	=> wp_image_editor_supports(['methods' => ['resize', 'save']]),
			'server_connect'			=> $this->get_options(['system', 'connect'], false),
		];
	}
	
	/**
	 * import a media file uploaded through the browser to the media library
	 **/
	public function import_upload_media(){
		global $SR_GLOBALS;
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		
		global $wp_filesystem;
		WP_Filesystem();
		
		$import_file = $this->get_val($_FILES, 'import_file');
		$error		 = $this->get_val($import_file, 'error');
		$return		 = ['error' => __('File not found', 'revslider')];
		
		switch($error){
			case UPLOAD_ERR_NO_FILE:
				return ['error' => __('No file sent', 'revslider')];
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return ['error' => __('Exceeded filesize limit', 'revslider')];
			default:
			break;
		}
		
		$path = $this->get_val($import_file, 'tmp_name');
		if(isset($path['error'])) return ['error' => $path['error']];
		
		if(file_exists($path) == false) return ['error' => __('File not found', 'revslider')];
		if($this->get_val($import_file, 'size') > wp_max_upload_size()) return ['error' => __('Exceeded filesize limit', 'revslider')];

		$mime_types = array_merge($this->get_val($SR_GLOBALS, ['mime_types', 'image']), $this->get_val($SR_GLOBALS, ['mime_types', 'video']));
		$file_mime	= mime_content_type($path);
		if(!in_array($file_mime, $mime_types)) return ['error' => __('WordPress doesn\'t allow this filetype', 'revslider')];

		$file_name	= basename($this->get_val($import_file, 'name'));
		$file_type	= wp_check_filetype($file_name, $mime_types);
		if($this->get_val($file_type, 'ext', false) === false || $this->get_val($file_type, 'type', false) === false) return ['error' => __('WordPress doesn\'t allow this filetype', 'revslider')];
		
		$upload_dir = wp_upload_dir();
		$new_path	= $path;
		$i			= 0;
		while(file_exists($new_path)){
			$i++;
			$new_path = $upload_dir['path']. '/' .$i. '-' .$file_name;
		}
		
		if(!move_uploaded_file($path, $new_path)) return $return;
		$upload_id = wp_insert_attachment(
			[
				'guid'			 => $new_path, 
				'post_mime_type' => $file_mime,
				'post_title'	 => preg_replace( '/\.[^.]+$/', '', $file_name),
				'post_name'		 => sanitize_title_with_dashes(str_replace('_', '-', $file_name)),
				'post_content'	 => '',
				'post_status'	 => 'inherit'
			],
			$new_path
		);
		
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		
		@wp_update_attachment_metadata($upload_id, wp_generate_attachment_metadata($upload_id, $new_path));
		
		$img_dim = @wp_get_attachment_image_src($upload_id, 'full');
		$width	 = ($img_dim !== false) ? $this->get_val($img_dim, 1, '') : '';
		$height	 = ($img_dim !== false) ? $this->get_val($img_dim, 2, '') : '';
		
		return ['error' => false, 'id' => $upload_id, 'path' => wp_get_attachment_url($upload_id), 'width' => $width, 'height' => $height]; //$new_path
	}
	
	
	/**
	 * Create Multilanguage for JavaScript
	 */
	public function get_javascript_multilanguage(){
		$t = [$this, '_t'];
		$lang = [
			'0 Free Modules Remaining' => __('0 Free Modules Remaining', 'revslider'),
			'1 Free Module Remaining' => __('1 Free Module Remaining', 'revslider'),
			'3D' => __('3D', 'revslider'),
			'& Addons' => __('& Addons', 'revslider'),
			'AI' => __('AI', 'revslider'),
			'AI Generation' => __('AI Generation', 'revslider'),
			'AI Generated Image' => __('AI Generated Image', 'revslider'),
			'AI Credit' => __('AI Credit', 'revslider'),
			'AI Credits' => __('AI Credits', 'revslider'),
			'Action Mode' => __('Action Mode', 'revslider'),
			'Action Required' => __('Action Required', 'revslider'),
			'Actions' => __('Actions', 'revslider'),
			'Accessibility' => __('Accessibility', 'revslider'),
			'Activating Addons' => __('Activating Addons', 'revslider'),
			'Add Clean Finish' => __('Add Clean Finish', 'revslider'),
			'Add AI credits' => __('Add AI credits', 'revslider'),
			'Add New Action' => __('Add New Action', 'revslider'),
			'Add New Frame' => __('Add New Frame', 'revslider'),
			'Add Filter' => __('Add Filter', 'revslider'),
			'Add Level' => __('Add Level', 'revslider'),
			'Add Slide' => __('Add Slide', 'revslider'),
			'Import Slide' => __('Import Slide', 'revslider'),
			'Import Slide(s)' => __('Import Slide(s)', 'revslider'),
			'Add Slide(s)' => __('Add Slide(s)', 'revslider'),
			'Add to Favorites' => __('Add to Favorites', 'revslider'),
			'Add to Module' => __('Add to Module', 'revslider'),
			'Addon added Successfully' => __('Addon Added Successfully', 'revslider'),
			'Addon removed Successfully' => __('Addon Removed Successfully', 'revslider'),
			'Add-ons are temporarily unavailable. Register your Slider Revolution license to enable all add-on functionality.' => __('Addons are temporarily unavailable. Register your Slider Revolution license to enable all addon functionality.', 'revslider'),
			'Add Custom SVG' => __('Add Custom SVG', 'revslider'),
			'Add/Remove in Editor' => __('Add/Remove in Editor', 'revslider'),
			'Add Path' => __('Add Path', 'revslider'),
			'Added Functionality' => __('Added Functionality', 'revslider'),
			'Addon' => __('Addon', 'revslider'),
			'Addons' => __('Addons', 'revslider'),
			'Addon Guide' => __('Addon Guide', 'revslider'),
			'Addon Updates' => __('Addon Updates', 'revslider'),
			'Addons Installed Successfully' => __('Addons Installed Successfully', 'revslider'),
			'Action Triggered' => __('Action Triggered', 'revslider'),
			'Alias' => __('Alias', 'revslider'),
			'Alias Contains' => __('Alias Contains', 'revslider'),
			'All' => __('All', 'revslider'),
			'All Elements' => __('All Elements', 'revslider'),
			'All Items' => __('All Items', 'revslider'),
			'All Layers' => __('All Layers', 'revslider'),
			'All Opened Modal' => __('All Opened Modal', 'revslider'),			
			'All Slides' => __('All Slides', 'revslider'),
			'All Layer Types' => __('All Layer Types', 'revslider'),
			'None Global Slides' => __('No Static Slides', 'revslider'),		
			'Anchor' => __('Anchor', 'revslider'),
			'Anchors' => __('Anchors', 'revslider'),
			'Popup blocked. Allow popups for this site or use the built-in preview.' => __('Popup blocked. Allow popups for this site or use the built-in preview.', 'revslider'),
			'Preview failed. Check console for details.' => __('Preview failed. Check console for details.', 'revslider'),
			'An error occurred while preparing the preview.' => __('An error occurred while preparing the preview.', 'revslider'),
			'Animate both Direction (Yoyo Effect)' => __('Animate in Both Directions (Yoyo Effect)', 'revslider'),
			'Animated Color in Frame' => __('Animated Color in Frame', 'revslider'),
			'Animate by Chars' => __('Animate by Chars.', 'revslider'),
			'Animate by Lines' => __('Animate by Lines', 'revslider'),
			'Animate by Words' => __('Animate by Words', 'revslider'),
			'Animate to \'From\' Frame'		=> $t('Animate to \'From\' Frame'),
			'Animation' => __('Animation', 'revslider'),
			'Animation Mode' => __('Animation Mode', 'revslider'),
			'Apply' => __('Apply', 'revslider'),
			'Approximate Remaining Time' => __('Approximate Remaining Time', 'revslider'),
			'Arc' => __('Arc', 'revslider'),
			'Are you sure you want to delete this folder?' => __('Are you sure you want to delete this folder?', 'revslider'),
			'Use as Modal' => __('Use as Modal', 'revslider'),
			'Attributes' => __('Attributes', 'revslider'),
			'Attributes to Edit' => __('Attributes to Edit', 'revslider'),
			'Audio' => __('Audio', 'revslider'),
			'Animation Frames' => __('Animation Frames', 'revslider'),
			'Animation & Scenes' => __('Animation & Scenes', 'revslider'),
			'Audios' => __('Audios', 'revslider'),
			'Auto Adjust' => __('Auto Adjust', 'revslider'),
			'Use Default Values' => __('Use Default Values', 'revslider'),
			'Auto Direction' => __('Auto Direction', 'revslider'),
			'Auto Order' => __('Auto Order', 'revslider'),
			'Auto Rotate' => __('Auto Rotate', 'revslider'),
			'Auto Rotated Direction' => __('Auto Rotated Direction', 'revslider'),
			'Available' => __('Available', 'revslider'),
			'Free Limit: 3' => __('Free Limit: 3', 'revslider'),
			'BG Color' => __('BG Color', 'revslider'),
			'beforeafter' => __('Before After', 'revslider'),
			'Before After' => __('Before After', 'revslider'),
			'Back to Parent' => __('Back to Parent', 'revslider'),
			'Backdropfilter' => __('Backdrop Filter', 'revslider'),
			'Background' => __('Background', 'revslider'),
			'Bg Color' => __('BG Color', 'revslider'),
			'Background Color' => __('Background Color', 'revslider'),
			'Blur' => __('Blur', 'revslider'),
			'Bottom' => __('Bottom', 'revslider'),
			'Bottom Left' => __('Bottom Left', 'revslider'),
			'Bottom Right' => __('Bottom Right', 'revslider'),
			'Bounce' => __('Bounce', 'revslider'),
			'Buttons' => __('Buttons', 'revslider'),
			'Border' => __('Border', 'revslider'),
			'Border Style' => __('Border Style', 'revslider'),
			'Border Color' => __('Border Color', 'revslider'),
			'Border Radius' => __('Border Radius', 'revslider'),
			'Shadow Color' => __('Shadow Color', 'revslider'),
			'Browser & Device' => __('Browser & Device', 'revslider'),
			'Burst' => __('Burst', 'revslider'),
			'Bulk Editor' => __('Bulk Editor', 'revslider'),
			'Bulk Style Editor' => __('Bulk Style Editor', 'revslider'),
			'Bulk Edit Targets' => __('Bulk Edit Targets', 'revslider'),
			'Button' => __('Button', 'revslider'),			
			'Buttons' => __('Buttons', 'revslider'),
			'bubblemorph' => __('Bubblemorph', 'revslider'),
			'Bubblemorph' => __('Bubblemorph', 'revslider'),
			'Block Overlay' => __('Block Overlay', 'revslider'),
			'Call Slide' => __('Call Slide', 'revslider'),
			'Cancel' => __('Cancel', 'revslider'),
			'Carousel' => __('Carousel', 'revslider'),
			'Carousel Settings' => __('Carousel Settings', 'revslider'),
			'Center Diagonals' => __('Center Diagonals', 'revslider'),
			'Center-Out' => __('Center-Out', 'revslider'),
			'Create Slider Module' => __('Create Slider Module', 'revslider'),			
			'Create Hero Module' => __('Create Hero Module', 'revslider'),			
			'Creating Template' => __('Creating Template', 'revslider'),			
			'Clipping Path' => __('Clipping Path', 'revslider'),
			'charts' => __('Charts', 'revslider'),
			'Charts' => __('Charts', 'revslider'),
			'Change' => __('Change', 'revslider'),
			'Change to' => __('Change to', 'revslider'),
			'Circle' => __('Circle', 'revslider'),
			'click' => __('click', 'revslider'),			
			'Click to select an Action' => __('Click to select an Action', 'revslider'),			
			'Clear' => __('Clear', 'revslider'),
			'Close' => __('Close', 'revslider'),
			'Collecting Google Fonts' => __('Collecting Google Fonts', 'revslider'),
			'Color Picker' => __('Color Picker', 'revslider'),
			'Colors in Use' => __('Colors in Use', 'revslider'),
			'Copy the content above and use it to paste the layer in another Slider Revolution installation.' => __('Copy the content above and use it to paste the layer in another Slider Revolution installation.', 'revslider'),
			'Core Engine' => __('Core Engine', 'revslider'),
			'Columns' => __('Columns', 'revslider'),
			'Coming Animation' => __('Coming Animation', 'revslider'),
			'Coming BG over Leaving' => __('Coming BG over Leaving', 'revslider'),
			'Configure Settings' => __('Configure Settings', 'revslider'),
			'Container' => __('Container', 'revslider'),
			'Containers' => __('Containers', 'revslider'),
			'Content' => __('Content', 'revslider'),
			'Contents' => __('Contents', 'revslider'),
			'Content Clipping' => __('Content Clipping', 'revslider'),
			'Content Flow' => __('Content Flow', 'revslider'),
			'Content Source' => __('Content Source', 'revslider'),
			'Content copied to clipboard!' => __('Content copied to clipboard!', 'revslider'),
			'Cont.' => __('Cont.', 'revslider'),
			'Copy' => __('Copy', 'revslider'),
			'Cross-Module Actions Updated' => __('Cross-Module Actions Updated', 'revslider'),
			'Create Blank Page' => __('Create Blank Page', 'revslider'),
			'Create your first Slider Revolution Module' => __('Create Your First Slider Revolution Module', 'revslider'),			
			'Currently Importing the' => __('Currently Importing the', 'revslider'),
			'Custom' => __('Custom', 'revslider'),
			'Custom Text Color' => __('Custom Text Color', 'revslider'),
			'Custom BG Color' => __('Custom BG Color', 'revslider'),
			'Custom Value' => __('Custom Value', 'revslider'),
			'Customize' => __('Customize', 'revslider'),
			'Customize Action' => __('Customize Action', 'revslider'),
			'Custom Horizontal' => __('Custom Horizontal', 'revslider'),
			'Custom Scripts' => __('Custom Scripts', 'revslider'),
			'Custom Vertical' => __('Custom Vertical', 'revslider'),
			'CustomIcon' => __('Custom Icon', 'revslider'),
			'Current Focused Modal' => __('Current Focused Modal', 'revslider'),
			'Current Slide' => __('Current Slide', 'revslider'),
			'Current Slide Layers' => __('Current Slide Layers', 'revslider'),
			'Dark Mode' => __('Go Dark', 'revslider'),
			'Default' => __('Default', 'revslider'),
			'Defaults' => __('Defaults', 'revslider'),
			'3/3 Free Modules' => __('3/3 Free Modules', 'revslider'),
			'Delete' => __('Delete', 'revslider'),			
			'Delay' => __('Delay', 'revslider'),
			'Delete All Children' => __('Delete All Children', 'revslider'),
			'Delete All Layers' => __('Delete All Layers', 'revslider'),
			'Delete Multiple Modules' => __('Delete Multiple Modules', 'revslider'),
			'Delete Everything' => __('Delete Everything', 'revslider'),
			'Delete Folder' => __('Delete Folder', 'revslider'),
			'Deleting Multiple Modules' => __('Deleting Multiple Modules', 'revslider'),
			'Delete Path' => __('Delete Path', 'revslider'),
			'Delete Unmigrated Modules' => __('Delete Unmigrated Modules', 'revslider'),
			'Depth' => __('Depth', 'revslider'),
			'Deregister License' => __('Deregister License', 'revslider'),
			'Deregistering License' => __('Deregistering License', 'revslider'),
			'Design Mode' => __('Design Mode', 'revslider'),
			'Diagonal Center Left to Right' => __('Diagonal Center Left to Right', 'revslider'),
			'Diagonal Center Right to Left' => __('Diagonal Center Right to Left', 'revslider'),
			'Diagonals' => __('Diagonals', 'revslider'),
			'Dimensions' => __('Dimensions', 'revslider'),
			'Dir' => __('Dir.', 'revslider'),
			'Direct Transition' => __('Direct Transition', 'revslider'),
			'Direction Based' => __('Direction Based', 'revslider'),
			'Disable Addon' => __('Disable Addon', 'revslider'),
			'Disable Global Addon' => __('Disable Global Addon', 'revslider'),
			'Disable Menu' => __('Disable Menu', 'revslider'),
			'Disabled' => __('Disabled', 'revslider'),
			'Distance' => __('Distance', 'revslider'),			
			'Drag & Drop Import File' => __('Drag & Drop Import File', 'revslider'),
			'Drag functionality is available only in List Mode' => __('Drag functionality is available only in List Mode', 'revslider'),
			'Double Slide Effect' => __('Double Slide Effect', 'revslider'),
			'Downloading...' => __('Downloading...', 'revslider'),
			'Downloading Template' => __('Downloading Template', 'revslider'),
			'Downloading Template Images' => __('Downloading Template Images', 'revslider'),
			'Duplicate' => __('Duplicate', 'revslider'),
			'Done' => __('Done', 'revslider'),
			'Duplicate Path' => __('Duplicate Path', 'revslider'),
			'duotonefilters' => __('Duotone', 'revslider'),
			'Duotonefilters' => __('Duotone Filters', 'revslider'),
			'Duration' => __('Duration', 'revslider'),
			'Dynamic' => __('Dynamic', 'revslider'),
			'Dynamic Content' => __('Dynamic Content', 'revslider'),
			'Easing' => __('Easing', 'revslider'),
			'Edit' => __('Edit', 'revslider'),
			'Editable' => __('Editable', 'revslider'),
			'Editor Tour' => __('Editor Tour', 'revslider'),
			'Editing' => __('Editing', 'revslider'),
			'Edit Frames in' => __('Edit Frames in', 'revslider'),
			'Edit Images' => __('Edit Images', 'revslider'),
			'Edit Motion Path' => __('Edit Motion Path', 'revslider'),
			'Edit Path Rules' => __('Edit Path Rules', 'revslider'),
			'Effect' => __('Effect', 'revslider'),
			'Eight' => __('Eight', 'revslider'),
			'Element' => __('Layer', 'revslider'),			
			'Element edited successfully' => __('Layer Edited Successfully', 'revslider'),			
			'Elements edited successfully' => __('Layers Edited Successfully', 'revslider'),			
			'Element Path' => __('Layer Path', 'revslider'),
			'Element Selection' => __('Layer Selection', 'revslider'),
			'Elements on' => __('Layers on', 'revslider'),
			'Element on' => __('Layer on', 'revslider'),
			'Enable Global Addon' => __('Enable Global Addon', 'revslider'),
			'Enable Repeat on Timeline' => __('Enable Repeat on Timeline', 'revslider'),
			'is Enabled' => __('is Enabled', 'revslider'),
			'Enabled' => __('Enabled', 'revslider'),
			'End' => __('End', 'revslider'),
			'Everything uploaded successfully' => __('Everything Uploaded Successfully', 'revslider'),
			'Every Second Slides' => __('Every Second Slide', 'revslider'),
			'Existing Modules' => __('Existing Modules', 'revslider'),
			'Export' => __('Export', 'revslider'),
			'Extra Style' => __('Extra Style', 'revslider'),
			'FX' => __('FX', 'revslider'),
			'Factory' => __('Built-in', 'revslider'),
			'Fade through Dark' => __('Fade Through Dark', 'revslider'),
			'Fade through Light' => __('Fade Through Light', 'revslider'),
			'Fade through Transparent' => __('Fade Through Transparent', 'revslider'),
			'Failed' => __('Failed', 'revslider'),
			'Failure at Adjusting Modal IDs' => __('Failure at Adjusting Modal IDs', 'revslider'),
			'First Slide Only' => __('First Slide Only', 'revslider'),
			'First Slide' => __('First Slide', 'revslider'),
			'File Size' => __('File Size', 'revslider'),
			'Filter' => __('Filter', 'revslider'),
			'First Frame in Scene' => __('First Frame in Scene', 'revslider'),
			'Filters' => __('Filters', 'revslider'),
			'Filter Animation' => __('Filter Animation', 'revslider'),
			'Filmstrip' => __('Filmstrip', 'revslider'),
			'filmstrip' => __('Filmstrip', 'revslider'),
			'fluiddynamics' => __('Fluid Dyn.', 'revslider'),
			'Fluiddynamics' => __('Fluid Dyn.', 'revslider'),
			'Fix' => __('Fix', 'revslider'),
			'Fixed' => __('Fixed', 'revslider'),
			'Fixing...' => __('Fixing...', 'revslider'),
			'Font' => __('Font', 'revslider'),
			'Font Weight' => __('Font Weight', 'revslider'),
			'Font Style' => __('Font Style', 'revslider'),
			'Font Family' => __('Font Family', 'revslider'),
			'Font Families in Use' => __('Font Families in Use', 'revslider'),
			'Font Size' => __('Font Size', 'revslider'),
			'Font Settings' => __('Font Settings', 'revslider'),
			'Folder' => __('Folder', 'revslider'),
			'Font(s) to Cache' => __('Font(s) to Cache', 'revslider'),
			'Frame' => __('Frame', 'revslider'),
			'Free QSG Module' => __('Free QSG Module', 'revslider'),
			'First Scene' => __('First Scene', 'revslider'),
			'Second Scene' => __('Second Scene', 'revslider'),
			'from Scene' => __('from Scene', 'revslider'),
			'Fri' => __('Fri', 'revslider'),
			'from' => __('from', 'revslider'),
			'From' => __('From', 'revslider'),
			'In Timeline frames' => __('Play IN in This Scene', 'revslider'),
			'Frames from Other Scene' => __('Frames from Other Scene', 'revslider'),
			'Out Timeline frames' => __('Play OUT in This Scene', 'revslider'),
			'Move here from' => __('Play IN in This Scene', 'revslider'),
			
			'From' => __('From', 'revslider'),
			'From Center' => __('From Center', 'revslider'),
			'From Edges' => __('From Edges', 'revslider'),
			'From End' => __('From End', 'revslider'),
			'From Layer Library' => __('From Layer Library', 'revslider'),
			'From Start' => __('From Start', 'revslider'),
			'Full' => __('Full', 'revslider'),
			'for the' => __('for the', 'revslider'),
			'Gap' => __('Gap', 'revslider'),
			'Module Settings' => __('Module Configuration', 'revslider'),
			'General' => __('General', 'revslider'),
			'Generate' => __('Generate', 'revslider'),
			'Generated slider' => __('Generated Slider', 'revslider'),
			'Generated hero' => __('Generated Hero', 'revslider'),
			'Generated carousel' => __('Generated Carousel', 'revslider'),
			'Generating Image' => __('Generating Image', 'revslider'),
			'General Video Layers' => __('General Video Layers', 'revslider'),
			'Global' => __('Global', 'revslider'),
			'Global Slide Layers' => __('Static Slide Layers', 'revslider'),
			'Global Settings Not Saved' => __('Global Settings Not Saved', 'revslider'),
			'Global Settings Saved' => __('Global Settings Saved', 'revslider'),
			'Global Settings for Parallax' => __('Module Setting for Parallax ', 'revslider'),
			'Global Slide' => __('Static Slide', 'revslider'),
			'GlobalElements' => __('Edit Static Layers', 'revslider'),
			'Google Font' => __('Google Font', 'revslider'),
			'Google Fonts' => __('Google Fonts', 'revslider'),
			'Google Fonts Cached' => __('Google Fonts Cached', 'revslider'),
			'Groups' => __('Groups', 'revslider'),
			'Has Actions' => __('Has Actions', 'revslider'),
			'Headline' => __('Headline', 'revslider'),
			'Heart' => __('Heart', 'revslider'),
			'Hero' => __('Hero', 'revslider'),
			'Headlines' => __('Headlines', 'revslider'),
			'Hidden' => __('Hidden', 'revslider'),
			'"Hidden on Device"' => __('"Hidden on Device"', 'revslider'),
			'hovermorph' => __('Hover Morph', 'revslider'),
			'Horizontal' => __('Horizontal', 'revslider'),
			'Horizontal & Vertical' => __('Horizontal & Vertical', 'revslider'),
			'Horizontal Center' => __('Horizontal Center', 'revslider'),
			'Hover Style' => __('Hover Style', 'revslider'),
			'Icon/SVG' => __('Icon/SVG', 'revslider'),
			'Icons' => __('Icons', 'revslider'),
			'items selected' => __('items selected', 'revslider'),
			'Idle' => __('Idle', 'revslider'),
			'Idle Tab' => __('Idle Tab', 'revslider'),
			'Idle Thumb' => __('Idle Thumb', 'revslider'),
			'In Animation' => __('In Animation', 'revslider'),
			'Image' => __('Image', 'revslider'),
			'Image generation is taking too long, process aborted. Please try again later.' => __('Image generation is taking too long, process aborted. Please try again later.', 'revslider'),
			'Images' => __('Images', 'revslider'),
			'Import' => __('Import', 'revslider'),
			'Import Layer' => __('Import Layer', 'revslider'),
			'Import Selected Layers' => __('Import Selected Layers', 'revslider'),
			'Infinity' => __('Infinity', 'revslider'),
			'Infinity Horizontal' => __('Infinity Horizontal', 'revslider'),
			'Initial State' => __('Initial State', 'revslider'),
			'In Scene' => __('In Scene', 'revslider'),
			'Insert' => __('Insert', 'revslider'),
			'Install Package' => __('Install Package', 'revslider'),
			'Install Template' => __('Install Template', 'revslider'),
			'Installed' => __('Installed', 'revslider'),
			'Installed Successfully' => __('Installed Successfully', 'revslider'),
			'Installing' => __('Installing', 'revslider'),
			'In' => __('In', 'revslider'),
			'is not available' => __('is not available', 'revslider'),
			'Inv' => __('Inv.', 'revslider'),
			'Invisible' => __('Invisible', 'revslider'),
			'Inheriting Size Source and Target need to be different!' => __('Inheriting Size Source and Target need to be different!', 'revslider'),
			'Keep' => __('Keep', 'revslider'),
			'Keep Current' => __('Keep Current', 'revslider'),
			'Keep Preset Defaults' => __('Keep Preset Defaults', 'revslider'),
			'Keep Custom Settings' => __('Keep Custom Settings', 'revslider'),
			'Keyframes' => __('Keyframes', 'revslider'),
			'Last Slide' => __('Last Slide', 'revslider'),
			'Last Source' => __('Last Source', 'revslider'),
			'Last Used' => __('Last Used', 'revslider'),
			'Layer' => __('Layer', 'revslider'),
			'Layer is locked in editor' => __('Layer is locked in editor', 'revslider'),
			'Layer is unlocked in editor' => __('Layer is unlocked in editor', 'revslider'),
			'Layer is visible in editor' => __('Layer is visible in editor', 'revslider'),
			'Layer is hidden in editor' => __('Layer is hidden in editor', 'revslider'),
			'Layer Import' => __('Layer Import', 'revslider'),
			'Layers' => __('Layers', 'revslider'),
			'Layers Selected' => __('Layers Selected', 'revslider'),
			'Layers in Scene' => __('Layers in Scene', 'revslider'),
			'Layout & Design' => __('Layout, Size, Position', 'revslider'),
			'Leaving BG over Coming' => __('Leaving BG over Coming', 'revslider'),
			'Save and Leave' => __('Save and Leave', 'revslider'),
			'Stay on Page' => __('Stay on Page', 'revslider'),
			'Leave without Saving' => __('Leave without Saving', 'revslider'),
			'You are about leaving the Page...' => __('You are about leaving the Page...', 'revslider'),
			'Last' => __('Last', 'revslider'),
			'Last Slide Only' => __('Last Slide Only', 'revslider'),
			'Left' => __('Left', 'revslider'),
			'Letter Spacing' => __('Letter Spacing', 'revslider'),
			'Level' => __('Level', 'revslider'),
			'Leaving Animation' => __('Leaving Animation', 'revslider'),
			'Light Mode' => __('Go Light', 'revslider'),
			'distortion' => __('Distortion', 'revslider'),
			'Distortion' => __('Distortion', 'revslider'),
			'Line' => __('Line', 'revslider'),
			'Line Break After' => __('Line Break After', 'revslider'),
			'Line Height' => __('Line Height', 'revslider'),
			'Link' => __('Link', 'revslider'),
			'Links' => __('Links', 'revslider'),
			'Lim' => __('Lim', 'revslider'),
			'Last Scene' => __('Last Scene', 'revslider'),
			'Load / Save Preset' => __('Load / Save Preset', 'revslider'),
			'Loading Editor Resources' => __('Loading Editor Resources', 'revslider'),
			'Loading Library Content' => __('Loading Library Content', 'revslider'),
			'Loading Library Element' => __('Loading Library Element', 'revslider'),
			'Loading Modules' => __('Loading Modules', 'revslider'),
			'Loading Template Previews' => __('Loading Template Previews', 'revslider'),
			'Lock/Unlock' => __('Lock/Unlock', 'revslider'),
			'lottie' => __('Lottie', 'revslider'),
			'Lottie' => __('Lottie', 'revslider'),
			'Loop' => __('Loop', 'revslider'),
			'Loop Effects' => __('Loop Effects', 'revslider'),
			'Lum' => __('Lum.', 'revslider'),
			'Marker Name' => __('Marker Name', 'revslider'),
			'Marker State' => __('Marker State', 'revslider'),
			'Marker Position' => __('Marker Position', 'revslider'),
			'Mask Shifting' => __('Mask Shifting', 'revslider'),
			'Masking' => __('Masking', 'revslider'),
			'Mask' => __('Mask', 'revslider'),
			'Max' => __('Max', 'revslider'),
			'Meta Handle too short or not set' => __('Meta Handle too short or not set', 'revslider'),
			'Meta Type not set' => __('Meta Type not set', 'revslider'),
			'Meta with Handle exists already' => __('Meta with Handle exists already', 'revslider'),
			'Mirror Slide Direction' => __('Mirror Slide Direction', 'revslider'),
			'Mirrored Direction' => __('Mirrored Direction', 'revslider'),
			'Mirrored' => __('Mirrored', 'revslider'),
			'migratenow' => __('Migrate', 'revslider'),
			'Migrate V6' => __('Migrate V6', 'revslider'),
			'Min' => __('Min', 'revslider'),
			'Module' => __('Module', 'revslider'),
			'Modules has been exported' => __('Modules has been exported', 'revslider'),
			'Module could not be created' => __('Module could not be created', 'revslider'),
			'Modules' => __('Modules', 'revslider'),
			'Mon' => __('Mon', 'revslider'),
			'Mono' => __('Mono.', 'revslider'),
			'Month_01' => __('January', 'revslider'),
			'Month_02' => __('February', 'revslider'),
			'Month_03' => __('March', 'revslider'),
			'Month_04' => __('April', 'revslider'),
			'Month_05' => __('May', 'revslider'),
			'Month_06' => __('June', 'revslider'),
			'Month_07' => __('July', 'revslider'),
			'Month_08' => __('August', 'revslider'),
			'Month_09' => __('September', 'revslider'),
			'Month_10' => __('October', 'revslider'),
			'Month_11' => __('November', 'revslider'),
			'Month_12' => __('December', 'revslider'),
			'Motion' => __('Motion', 'revslider'),
			'Motion & Effects' => __('Motion & Effects', 'revslider'),
			'Motion Path' => __('Motion Path', 'revslider'),			
			'Motion Path Done' => __('Motion Path Done', 'revslider'),
			'Move Layers to Parent' => __('Move Layers to Parent', 'revslider'),
			'Move Layers to Root' => __('Move Layers to Root', 'revslider'),
			'Move Layers to Sibling' => __('Move Layers to Sibling', 'revslider'),
			'Move to Parent' => __('Move to Parent', 'revslider'),
			'mousetrap' => __('Mouse Trap', 'revslider'),
			'Mousetrap' => __('Mouse Trap', 'revslider'),
			'mouseenter' => __('Mouse Enter', 'revslider'),
			'mouseleave' => __('Mouse Leave', 'revslider'),
			'Multiple Elements Selected' => __('Multiple Elements Selected', 'revslider'),
			'Multiple Sources' => __('Multiple Sources', 'revslider'),
			'Multipe Modules deleted' => __('Multipe Modules deleted', 'revslider'),
			'Multiple Targets' => __('Multiple Targets', 'revslider'),
			'Multiple Layers' => __('Multiple Layers', 'revslider'),
			'N/A' => __('N/A', 'revslider'),
			'Name' => __('Name', 'revslider'),
			'New Path' => __('New Path', 'revslider'),
			'Add a new scene' => __('Add a new scene', 'revslider'),			
			'newscene' => __('Add a new scene', 'revslider'),			
			'Unnamed Path' => __('Unnamed Path', 'revslider'),
			'Max folder depth reached' => __('Max folder depth reached', 'revslider'),
			'Navigation' => __('Navigation', 'revslider'),
			'Navigation Arrows' => __('Arrows', 'revslider'),
			'Navigation Bullets' => __('Bullets', 'revslider'),
			'Navigation Scrubber' => __('Scrubber', 'revslider'),
			'Navigation Tabs' => __('Tabs', 'revslider'),
			'Navigation Thumbnails' => __('Thumbnails', 'revslider'),
			'New Action' => __('New Action', 'revslider'),
			'New Folder' => __('New Folder', 'revslider'),
			'New Slide' => __('New Slide', 'revslider'),
			'New Slider' => __('New Slider', 'revslider'),
			'Next Month' => __('Next Month', 'revslider'),
			'Next Slide' => __('Next Slide', 'revslider'),
			'No' => __('No', 'revslider'),
			'No Delays' => __('No Delays', 'revslider'),
			'No Layer Selected' => __('No Layer Selected', 'revslider'),
			'No Items Found' => __('No Items Found', 'revslider'),
			'No Keyframes' => __('No Keyframes', 'revslider'),
			'No layers to import found here' => __('No layers to import found here', 'revslider'),
			'No library items were found that match the criteria.' => __('No library items were found that match the criteria.', 'revslider'),
			'Library items not loaded. Please try again later or update the library.' => __('Library items not loaded. Please try again later or update the library.', 'revslider'),
			'No License Key Entered' => __('No License Key Entered', 'revslider'),
			'Select a layer to place the generated image.' => __('Select a layer to place the generated image.', 'revslider'),
			'No Scenes Found' => __('No Scenes Found', 'revslider'),
			'Not Compatible' => __('Not Compatible', 'revslider'),
			'Not In Scene' => __('Not In Scene', 'revslider'),
			'Not Installed' => __('Not Installed', 'revslider'),
			'Not Registered' => __('Not Registered', 'revslider'),						
			'Not Migrated' => __('Not Migrated', 'revslider'),
			'Not enough Space between Frames' => __('Not enough Space between Frames. Shift or resize Frames first.', 'revslider'),
			'None' => __('None', 'revslider'),
			'Opacity' => __('Opacity', 'revslider'),
			'Offset' => __('Offset', 'revslider'),
			'Further Filter(s)' => __('Further Filter(s)', 'revslider'),
			'Out Animation' => __('Out Animation', 'revslider'),
			'Orig X' => __('Orig. X', 'revslider'),
			'Orig Y' => __('Orig. Y', 'revslider'),
			'Orig Z' => __('Orig. Z', 'revslider'),
			'Original Source' => __('Original Source', 'revslider'),
			'Out' => __('Out', 'revslider'),
			'Scene Out' => __('Scene Out', 'revslider'),
			'Outdated' => __('Outdated', 'revslider'),
			'Opacity is 0%' => __('Opacity is 0%', 'revslider'),
			'Overlay' => __('Overlay', 'revslider'),
			'Overlay Color' => __('Overlay Color', 'revslider'),
			'Overflow' => __('Overflow', 'revslider'),
			'Overwrite' => __('Overwrite', 'revslider'),
			'Play' => __('Play', 'revslider'),
			'Path Name' => __('Path Name', 'revslider'),
			'Package' => __('Package', 'revslider'),
			'Palette' => __('Palette', 'revslider'),
			'Palette Not Renamed' => __('Palette Not Renamed', 'revslider'),
			'panorama' => __('Panorama', 'revslider'),
			'Panorama' => __('Panorama', 'revslider'),
			'Paintbrush' => __('Paintbrush', 'revslider'),
			'paintbrush' => __('Paintbrush', 'revslider'),
			'Package Installed Successfully' => __('Package Installed Successfully', 'revslider'),
			'Package Requirements' => __('Package Requirements', 'revslider'),
			'Pan Zoom Animation' => __('Pan Zoom Animation', 'revslider'),
			'Parallax' => __('Parallax', 'revslider'),
			'Parent' => __('Parent', 'revslider'),
			'Parsing Sequence' => __('Parsing Sequence', 'revslider'),
			'Paste' => __('Paste', 'revslider'),
			'Paste After Selected Elemnt' => __('Paste After Selected Elemnt', 'revslider'),
			'Paste Before Selected Element' => __('Paste Before Selected Element', 'revslider'),
			'Paste Inside Selected Element' => __('Paste Inside Selected Element', 'revslider'),
			'Paste Right Here' => __('Paste Right Here', 'revslider'),
			'Paste as Last Element' => __('Paste as Last Element', 'revslider'),
			'Paste in Root' => __('Paste in Root', 'revslider'),
			'Paste here content from Global Clipboard.' => __('Paste here content from Global Clipboard.', 'revslider'),
			'Path Presets' => __('Path Presets', 'revslider'),
			'Path Rules' => __('Path Rules', 'revslider'),
			'Partial' => __('Partial', 'revslider'),
			'particlewave' => __('Par. Wave', 'revslider'),
			'Particlewave' => __('Par. Wave', 'revslider'),
			'Pending' => __('Pending', 'revslider'),
			'Pending Migration' => __('Pending Migration', 'revslider'),
			'Per Pages' => __('Per Page', 'revslider'),
			'Performance Priority' => __('Performance Priority', 'revslider'),
			'PickIcon' => __('Pick Icon', 'revslider'),
			'Pick your Hero design' => __('Pick your Hero design', 'revslider'),
			'Play Scene' => __('Play Scene', 'revslider'),
			'Trigger Out Animation' => __('Trigger Out Animation', 'revslider'),
			'Please change the image dimensions to start upscaling' => __('Please change the image dimensions to start upscaling', 'revslider'),
			'Please choose an image to upscale' => __('Please choose an image to upscale', 'revslider'),
			'Please wait while the Addons are being activated' => __('Please wait while the Addons are being activated', 'revslider'),
			'Please enter a prompt to generate an image.' => __('Please enter a prompt to generate an image.', 'revslider'),
			
			'Position' => __('Position', 'revslider'),
			'Preparing Template Installation' => __('Preparing Template Installation', 'revslider'),
			'polyfold' => __('Polyfold', 'revslider'),
			'Polyfold' => __('Polyfold', 'revslider'),
			'Precached' => __('Precached', 'revslider'),
			'Precaching Google Fonts' => __('Precaching Google Fonts', 'revslider'),
			'Premium' => __('Premium', 'revslider'),
			'Preset' => __('Preset', 'revslider'),			
			'_advancedmode' => __('Easily preview & assign animations', 'revslider'),
			'_presetsmode' => __('Manually configure keyframes', 'revslider'),

			'Processing Video...' => __('Processing Video...', 'revslider'),
			'Progress' => __('Progress', 'revslider'),
			'Preset with handle' => __('Preset with handle', 'revslider'),
			'Prev Month' => __('Prev Month', 'revslider'),
			'Preview' => __('Preview', 'revslider'),
			'Preview From' => __('Preview From', 'revslider'),
			'Preview Reset' => __('Preview Reset', 'revslider'),			
			'Publish' => __('Publish', 'revslider'),
			'Published' => __('Published', 'revslider'),
			'Random' => __('Random', 'revslider'),
			'Random Slide' => __('Random Slide', 'revslider'),			
			'Rectangle' => __('Rectangle', 'revslider'),
			'Register License on this Website' => __('Register License on this Website', 'revslider'),
			'Register License to Update' => __('Register License to Update', 'revslider'),
			'Register Plugin' => __('Register Plugin', 'revslider'),
			'Registering License' => __('Registering License', 'revslider'),
			'Registered License' => __('Registered License', 'revslider'),
			'Registered' => __('Registered', 'revslider'),
			'Range Start' => __('Range Start', 'revslider'),
			'Range End' => __('Range End', 'revslider'),
			'Remove' => __('Remove', 'revslider'),
			'Remove Background' => __('Remove Background', 'revslider'),
			'Removing Background' => __('Removing Background', 'revslider'),
			'Remove Level' => __('Remove Level', 'revslider'),
			'Remove Repeat' => __('Remove Repeat', 'revslider'),
			'Remove from Module' => __('Remove from Module', 'revslider'),
			'Rename' => __('Rename', 'revslider'),
			'Rename Success' => __('Rename Success', 'revslider'),
			'Renamed to' => __('Renamed to', 'revslider'),
			'Repeat Amount' => __('Repeat Amount', 'revslider'),
			'Repeat Frames' => __('Repeat Frames', 'revslider'),
			'Requirements' => __('Requirements', 'revslider'),
			'Reset' => __('Reset', 'revslider'),
			'Restart Editor' => __('Restart Editor', 'revslider'),
			'Reset & Exit' => __('Reset & Exit', 'revslider'),
			'Reset to' => __('Reset to', 'revslider'),
			'Reset All Layers' => __('Reset All Layers', 'revslider'),
			'Reset Scene Layers' => __('Reset Scene Layers', 'revslider'),
			'Respect Margin Offsets' => __('Respect Margin Offsets', 'revslider'),
			'Responsivity' => __('Responsivity', 'revslider'),
			'reveal' => __('Reveal', 'revslider'),
			'Reveal' => __('Reveal', 'revslider'),
			'revealer' => __('Revealer', 'revslider'),
			'Revealer' => __('Revealer', 'revslider'),
			'Reverse Spiral' => __('Reverse Spiral', 'revslider'),
			'Right' => __('Right', 'revslider'),
			'Root' => __('Root', 'revslider'),
			'Run Bulk Edit' => __('Run Bulk Edit', 'revslider'),
			'RotationX' => __('X Rotation', 'revslider'),
			'RotationY' => __('Y Rotation', 'revslider'),
			'RotationZ' => __('Z Rotation', 'revslider'),
			'Rows' => __('Rows', 'revslider'),
			'S Cruve' => __('S Cruve', 'revslider'),
			'Sat' => __('Sat', 'revslider'),
			'Save' => __('Save', 'revslider'),
			'Save & Exit' => __('Save & Exit', 'revslider'),
			'Save Global Settings' => __('Save Global navigation settings, or reset to Original?', 'revslider'),
			'Save before Exit ?' => __('Save before Exit ?', 'revslider'),
			'Saved' => __('Saved', 'revslider'),
			'Saving Datas..' => __('Saving Datas..', 'revslider'),
			'scroll video' => __('Scroll Video', 'revslider'),
			'scrollvideo' => __('Scroll Video', 'revslider'),
			'Scrollvideo' => __('Scroll Video', 'revslider'),
			'Scroll Video' => __('Scroll Video', 'revslider'),
			'Scale X' => __('Scale X', 'revslider'),
			'Scale Y' => __('Scale Y', 'revslider'),
			'Scene' => __('Scene', 'revslider'),
			'Scene Deleted' => __('Scene Deleted', 'revslider'),
			'Scenes' => __('Scenes', 'revslider'),
			'Select some Modules or Folders to delete' => __('Select some Modules or Folders to delete', 'revslider'),
			'Select some Modules or Folders to export' => __('Select some Modules or Folders to export', 'revslider'),
			'Select some Modules or Folders to group' => __('Select some Modules or Folders to group', 'revslider'),
			'Select a Scene before Transfer' => __('Select a Scene before Transfer', 'revslider'),
			'Scene Name' => __('Scene Name', 'revslider'),
			'Scene Frames transfered successfully' => __('Scene Frames transfered successfully', 'revslider'),
			'Scene Name Updated to' => __('Scene Name Updated to', 'revslider'),
			'Scene Settings' => __('Scene Settings', 'revslider'),
			'Scene Layers' => __('Scene Layers', 'revslider'),
			'snow' => __('Snow', 'revslider'),
			'Snow' => __('Snow', 'revslider'),
			'Scroll & Parallax' => __('Scroll & Parallax', 'revslider'),
			'Search' => __('Search', 'revslider'),
			'Search in' => __('Search in', 'revslider'),
			'SearchElements' => __('Search Elements', 'revslider'),
			'Select Audio' => __('Select Audio', 'revslider'),
			'Selected' => __('Selected', 'revslider'),
			'Selected Bullet' => __('Selected Bullet', 'revslider'),
			'Selected Tab' => __('Selected Tab', 'revslider'),
			'Selected Thumb' => __('Selected Thumb', 'revslider'),
			'Select a Layer First' => __('Select a Layer First', 'revslider'),
			'Select one or more Slides' => __('Select one or more Slides', 'revslider'),
			'Select Background Image' => __('Select Background Image', 'revslider'),
			'Select Image' => __('Select Image', 'revslider'),
			'Select Template' => __('Select Template', 'revslider'),
			'Select Video' => __('Select Video', 'revslider'),
			'Selected Layers' => __('Selected Layers', 'revslider'),
			'Select Slide To Continue' => __('Select Slide To Continue', 'revslider'),
			'Select Slide(s) To Continue' => __('Select Slide(s) To Continue', 'revslider'),
			'Sepia' => __('Sepia', 'revslider'),
			'Speed' => __('Speed', 'revslider'),
			'Settings' => __('Settings', 'revslider'),
			'Settings saved' => __('Settings saved', 'revslider'),
			'Side Color (on first Animation)' => __('Side Color (on first Animation)', 'revslider'),
			'suredeletescene' => __('Deleting this Scene will remove all its Layer animations and Action links.<br>Continue?', 'revslider'),			
			'No Reset' => __('No Reset', 'revslider'),
			'Simulate' => __('Simulate', 'revslider'),
			'Simulate Event' => __('Simulate Event', 'revslider'),
			'Shadow Color' => __('Shadow Color', 'revslider'),
			'shapeburst' => __('Shapeburst', 'revslider'),
			'Shapeburst' => __('Shapeburst', 'revslider'),
			'Shape' => __('Shape', 'revslider'),
			'Shapes' => __('Shapes', 'revslider'),
			'Show All' => __('Show All', 'revslider'),
			'Show all items' => __('Show all items', 'revslider'),
			'Show/Hide' => __('Show/Hide', 'revslider'),
			'Size' => __('Size', 'revslider'),
			'Skew X' => __('Skew X', 'revslider'),
			'Skew Y' => __('Skew Y', 'revslider'),
			'Skin' => __('Skin', 'revslider'),
			'slicey' => __('Slicey', 'revslider'),
			'Slicey' => __('Slicey', 'revslider'),
			'Slide' => __('Slide', 'revslider'),
			'Slides Order could not be saved' => __('Slides Order could not be saved', 'revslider'),
			'Slide Based' => __('Slide Based', 'revslider'),
			'Slider Revolution Is Now Updated' => __('Slider Revolution Is Now Updated', 'revslider'),
			'Slider Revolution Updated Not Available' => __('Slider Revolution Updated Not Available', 'revslider'),
			'Slide Background' => __('Slide Background', 'revslider'),
			'Slide Direction Based' => __('Slide Direction Based', 'revslider'),
			'SlideShow & Progress' => __('SlideShow, Order &  Progress', 'revslider'),
			'Slide Index' => __('Slide Index', 'revslider'),
			'Slides could not be created in Module' => __('Slides could not be created in Module', 'revslider'),
			'Slider' => __('Slider', 'revslider'),
			'Slider Revolution Elements' => __('Slider Revolution Elements', 'revslider'),
			'Slider Revolution Library' => __('Slider Revolution JS Libraries', 'revslider'),
			'Slides' => __('Slides', 'revslider'),
			'Source' => __('Source', 'revslider'),
			'Spacings' => __('Spacing', 'revslider'),
			'Spiral' => __('Spiral', 'revslider'),
			'Split' => __('Split', 'revslider'),
			'SR7 Global Clipboard is not recognized.' => __('SR7 Global Clipboard is not recognized.', 'revslider'),
			'You’re already on the latest version. No new update is available at the moment. Please try again later.' => __('You’re already on the latest version. No new update is available at the moment. Please try again later.', 'revslider'),
			'Square' => __('Square', 'revslider'),
			'Stacking Order' => __('Stacking Order', 'revslider'),
			'Stage' => __('Stage', 'revslider'),
			'Start' => __('Start', 'revslider'),
			'svg' => __('SVG', 'revslider'),
			'Stroke Color' => __('Stroke Color', 'revslider'),
			'Stroke Width' => __('Stroke Width', 'revslider'),
			'Static Slide' => __('Static Slide', 'revslider'),
			'Static Slide Only' => __('Static Slide Only', 'revslider'),
			'Static Slide Above' => __('Static Slide Above', 'revslider'),
			'Static Slide Below' => __('Static Slide Below', 'revslider'),
			'Style' => __('Style', 'revslider'),
			'Quick Add' => __('Quick Add', 'revslider'),
			'Extra Style' => __('Extra Style', 'revslider'),
			'Successful' => __('Successful', 'revslider'),
			'Sun' => __('Sun', 'revslider'),
			'sunbeam' => __('SunBeam', 'revslider'),
			'Sunbeam' => __('SunBeam', 'revslider'),
			'SunBeam' => __('SunBeam', 'revslider'),
			'Tag' => __('Tag', 'revslider'),
			'Tags' => __('Tags', 'revslider'),
			'Target' => __('Target', 'revslider'),
			'template' => __('Template', 'revslider'),
			'Template' => __('Template', 'revslider'),
			'Template Paths' => __('Editing Paths', 'revslider'),
			'Template Installed Successfully' => __('Template Installed Successfully', 'revslider'),
			'Templates Library' => __('Templates Library', 'revslider'),
			'Text' => __('Text', 'revslider'),
			'Text Settings' => __('Text Settings', 'revslider'),
			'Text Transform' => __('Text Transform', 'revslider'),
			'Text Decoration' => __('Text Decoration', 'revslider'),
			'Text Color' => __('Text Color', 'revslider'),
			'Text Splitting' => __('Text Splitting', 'revslider'),
			'Texts' => __('Texts', 'revslider'),
			'Tilt' => __('Tilt', 'revslider'),
			'Title' => __('Title', 'revslider'),
			'The' => __('The', 'revslider'),
			'The generated image could not be saved to your Media Library.' => __('The generated image could not be saved to your Media Library.', 'revslider'),
			'This will delete all RevSlider 6 modules and Databases that were not migrated to RevSlider 7.<br> This action cannot be undone. Are you sure you want to proceed?' => __('This will delete all RevSlider 6 modules and Databases that were not migrated to RevSlider 7.<br> This action cannot be undone. Are you sure you want to proceed?', 'revslider'),

			'The new version is ready. Reload to enable all improvements and fixes.' => __('The new version is ready. Reload to enable all improvements and fixes.', 'revslider'),

			'The slide’s background layer cannot be deleted.' => __('The slide’s background layer cannot be deleted.', 'revslider'),
			'thecluster' => __('The Cluster', 'revslider'),
			'Thecluster' => __('The Cluster', 'revslider'),
			'The generated image has been saved to your WordPress Media Library.' => __('The generated image has been saved to your WordPress Media Library.', 'revslider'),
			'The generated images have been saved to your WordPress Media Library.' => __('The generated images have been saved to your WordPress Media Library.', 'revslider'),
			'The last slide cannot be deleted.' => __('The last slide cannot be deleted.', 'revslider'),
			'There is no image on this slide to pick color from' => __('There is no image on this slide to pick color from', 'revslider'),
			'This will overwrite the Custom Preset with the current Settings' => __('This will overwrite the Custom Preset with the current Settings', 'revslider'),
			'This will overwrite the Global Preset with the current Settings' => __('This will overwrite the Global Preset with the current Settings', 'revslider'),
			'This will overwrite the Global Skin with the current Settings' => __('This will overwrite the Global Skin with the current Settings', 'revslider'),
			'Thu' => __('Thu', 'revslider'),
			'To' => __('To', 'revslider'),
			'to Scene' => __('to Scene', 'revslider'),
			'Toggle' => __('Toggle', 'revslider'),
			'Toggle Slide' => __('Toggle Slide', 'revslider'),
			'Toggle Scenes' => __('Toggle Scenes', 'revslider'),
			'Too close! Move markers further apart.' => __('Too close! Move markers further apart.', 'revslider'),
			'Top' => __('Top', 'revslider'),
			'Top Left' => __('Top Left', 'revslider'),
			'Top Right' => __('Top Right', 'revslider'),
			'Transition' => __('Transition', 'revslider'),
			'Transition Behavior' => __('Transition Behavior', 'revslider'),
			'Transition Style' => __('Transition Style', 'revslider'),
			'Tue' => __('Tue', 'revslider'),
			'typewriter' => __('Typewriter', 'revslider'),
			'Typewriter' => __('Typewriter', 'revslider'),
			'Type' => __('Type', 'revslider'),
			'Use "IN" Timeline' => __('Use "IN" Timeline', 'revslider'),
			'Uniform Gaps' => __('Uniform Gaps', 'revslider'),
			'Unlock AI' => __('Unlock AI', 'revslider'),
			'Unpublish' => __('Unpublish', 'revslider'),
			'Unpublished' => __('Unpublished', 'revslider'),
			'Unsaved' => __('Unsaved', 'revslider'),
			'Update Addon' => __('Update Addon', 'revslider'),
			'Update Available' => __('Update Available', 'revslider'),
			'Update Inline Scripts' => __('Update Inline Scripts', 'revslider'),
			'Update Modal ID\'s'			=> $t('Update Modal ID\'s'),
			'Update Path' => __('Update Path', 'revslider'),
			'Update System' => __('Update System', 'revslider'),
			'Updating Slider Revolution' => __('Updating Slider Revolution', 'revslider'),
			'Update To Latest Version' => __('Update To Latest Version', 'revslider'),
			'Updates Available' => __('Updates Available', 'revslider'),
			'Updating' => __('Updating', 'revslider'),
			'Updating Addons Library' => __('Updating Addons Library', 'revslider'),
			'Updating Elements Library' => __('Updating Elements Library', 'revslider'),
			'Uploading' => __('Uploading', 'revslider'),
			'Uploading Files' => __('Uploading Files', 'revslider'),
			'Uploading...' => __('Uploading...', 'revslider'),
			'Upscale' => __('Upscale', 'revslider'),
			'Upscaled image successfully added to your Media Library' => __('Upscaled image successfully added to your Media Library', 'revslider'),
			'Use' => __('Use', 'revslider'),
			'Use this Audio' => __('Use this Audio', 'revslider'),
			'Use Filters' => __('Use Filters', 'revslider'),
			'Use Motion Filter' => __('Use Motion Filter', 'revslider'),
			'Use this Image' => __('Use this Image', 'revslider'),
			'Use this Video' => __('Use this Video', 'revslider'),
			'Use Shadow Effect' => __('Use Shadow Effect', 'revslider'),
			'Used By:' => __('Used By:', 'revslider'),
			'User Interaction' => __('User Interaction', 'revslider'),
			'Vertical Center' => __('Vertical Center', 'revslider'),
			'Vertical' => __('Vertical', 'revslider'),
			'Video' => __('Video', 'revslider'),
			'Videos' => __('Videos', 'revslider'),
			'Visibility' => __('Visibility', 'revslider'),
			'Visible' => __('Visible', 'revslider'),			
			'Visual Effects' => __('Visual Effects', 'revslider'),			
			'Wave' => __('Wave', 'revslider'),
			'Wave Loop' => __('Wave Loop', 'revslider'),
			'We save your current data and connect to your Facebook Page' => __('We save your current data and connect to your Facebook Page', 'revslider'),
			'Weight' => __('Weight', 'revslider'),
			'Wed' => __('Wed', 'revslider'),
			'may be Edited' => __('May Be Edited', 'revslider'),
			'WordPess Library' => __('WordPess Library', 'revslider'),
			'Wordpress Library' => __('Wordpress Library', 'revslider'),
			'Wrapper Motion' => __('Wrapper Motion', 'revslider'),
			'X Rotation' => __('X Rotation', 'revslider'),
			'XYZ' => __('XYZ', 'revslider'),
			'Y Rotation' => __('Y Rotation', 'revslider'),
			'You can not insert elements deeper than 5 levels.' => __('You can not insert elements deeper than 5 levels.', 'revslider'),
			'You can not insert multiple places elements, please select max one Element before you paste.' => __('You can not insert multiple places elements, please select max one Element before you paste.', 'revslider'),
			'Your Modules' => __('Your Modules', 'revslider'),
			'Z Rotation' => __('Z Rotation', 'revslider'),
			'ZigZag' => __('ZigZag', 'revslider'),
			'Zindex' => __('Z-Index', 'revslider'),
			'Capitalize' => __('Capitalize', 'revslider'),
			'Uppercase' => __('Uppercase', 'revslider'),
			'Lowercase' => __('Lowercase', 'revslider'),
			'None' => __('None', 'revslider'),
			'Normal' => __('Normal', 'revslider'),
			'Italic' => __('Italic', 'revslider'),			
			'Underline' => __('Underline', 'revslider'),
			'Overline' => __('Overline', 'revslider'),
			'Line Through' => __('Line Through', 'revslider'),			
			'Text Align Left' => __('Text Align Left', 'revslider'),
			'Text Align Center' => __('Text Align Center', 'revslider'),
			'Text Align Right' => __('Text Align Right', 'revslider'),

			'Move Column Left' => __('Move Column Left', 'revslider'),
			'Move Column Right' => __('Move Column Right', 'revslider'),

			'Group Layers' => __('Group Layers', 'revslider'),
			'Ungroup Layers' => __('Ungroup Layers', 'revslider'),

			'Delete Layer' => __('Delete Layer', 'revslider'),
			'Duplicate Layer' => __('Duplicate Layer', 'revslider'),

			'Edit Content' => __('Edit Content', 'revslider'),
			'Change Media' => __('Change Media', 'revslider'),

			'Add Element' => __('Add Element', 'revslider'),
			'Add Before' => __('Add Before', 'revslider'),
			'Add After' => __('Add After', 'revslider'),

			'Column Width' => __('Column Width', 'revslider'),
			'Margin' => __('Margin', 'revslider'),
			'Padding' => __('Padding', 'revslider'),

			'Position X' => __('Position X', 'revslider'),
			'Position Y' => __('Position Y', 'revslider'),

			'Relative Position' => __('Relative Position', 'revslider'),
			'Absolute Position' => __('Absolute Position', 'revslider'),

			'Display Inline' => __('Display Inline', 'revslider'),
			'Display Block' => __('Display Block', 'revslider'),

			'Content Align Top' => __('Content Align Top', 'revslider'),
			'Content Align Middle' => __('Content Align Middle', 'revslider'),
			'Content Align Center' => __('Content Align Center', 'revslider'),
			'Content Align Bottom' => __('Content Align Bottom', 'revslider'),

			'Top Left' => __('Top Left', 'revslider'),
			'Top Center' => __('Top Center', 'revslider'),
			'Top Right' => __('Top Right', 'revslider'),
			'Middle Left' => __('Middle Left', 'revslider'),
			'Middle Center' => __('Middle Center', 'revslider'),
			'Middle Right' => __('Middle Right', 'revslider'),
			'Bottom Left' => __('Bottom Left', 'revslider'),
			'Bottom Center' => __('Bottom Center', 'revslider'),
			'Bottom Right' => __('Bottom Right', 'revslider'),

			'Move to Top Rows' => __('Move to Top Rows', 'revslider'),
			'Move to Middle Rows' => __('Move to Middle Rows', 'revslider'),
			'Move to Bottom Rows' => __('Move to Bottom Rows', 'revslider'),

			'Generate AI Image' => __('Generate AI Image', 'revslider'),
			'Open SR Library' => __('Open SR Library', 'revslider'),
			'SR Library' => __('SR Library', 'revslider'),
			'Edit Text Layer' => __('Edit Text Layer', 'revslider'),
			
			'Edit Page' => __('Edit Page', 'revslider'),			
			'Show Live' => __('Show Live', 'revslider'),			
			'Blank Page Creation Failed' => __('Blank Page Creation Failed', 'revslider'),			
			'Blank Page Successfully Created' => __('Blank Page Created Successfully', 'revslider'),
			'A new blank page has been created in your WordPress pages. You can now edit the page to customize it further or view it live on your website.' => __('A new blank page has been created in your WordPress pages. You can now edit the page to customize it further or view it live on your website.', 'revslider'),

			'not in this Scene' => __('not in this Scene', 'revslider'),
			'Not in this Scene' => __('Not in this Scene', 'revslider'),
			'Device Visibility' => __('Device Visibility', 'revslider'),
			'Animation Visibility' => __('Animation Visibility', 'revslider'),
			'Editor Visibility' => __('Editor Visibility', 'revslider'),
			'Style Visibility' => __('Style Visibility', 'revslider'),
			'Environment' => __('Environment', 'revslider'),


			'Click "Generate" to Fix Mistakes' => __('Click "Generate" to Fix Mistakes', 'revslider'),
			'Click "Generate" to Improve Clarity' => __('Click "Generate" to Improve Clarity', 'revslider'),
			'Click "Generate" to Improve' => __('Click "Generate" to Improve', 'revslider'),
			'Click "Generate" to Expand' => __('Click "Generate" to Expand', 'revslider'),
			'Click "Generate" to Shorten' => __('Click "Generate" to Shorten', 'revslider'),
			'Click "Generate" to Change Tone' => __('Click "Generate" to Change Tone', 'revslider'),
			'Click "Generate" to Translate' => __('Click "Generate" to Translate', 'revslider'),
			'Press' => __('Press', 'revslider'),
			'for Bulk Actions' => __('for Bulk Actions', 'revslider'),			
			
			'Press #AI# to continue editing, or #PLUS# to use as variant' => __('Press #AI# to continue editing, or #PLUS# to use as variant', 'revslider'),
			'Wait for text generation to finish ...' => __('Wait for text generation to finish ...', 'revslider'),
			
			'Fixing spelling & grammar...' => __('Fixing spelling & grammar...', 'revslider'),
			'Improving text clarity...' => __('Improving text clarity...', 'revslider'),
			'Expanding your text...' => __('Expanding your text...', 'revslider'),
			'Shortening and refining...' => __('Shortening and refining...', 'revslider'),
			'Adjusting tone and style...' => __('Adjusting tone and style...', 'revslider'),
			'Adjusting and refining...' => __('Adjusting and refining...', 'revslider'),
			'Translating your text...' => __('Translating your text...', 'revslider'),
			
			'currentlyhaveaccess' => __('You currently have access to<br>these Slider Revolution features:', 'revslider'),
			'currentlydonthaveaccess'			=> $t('You currently dont\' have access to<br>these Slider Revolution features:'),

			'License Activated' => __('License Activated', 'revslider'),
			'License Deregistered' => __('License Deregistered', 'revslider'),
			'Invalid License Key' => __('Invalid License Key', 'revslider'),
			'No Key Entered' => __('No Key Entered', 'revslider'),
			'Register this License Key' => __('Register this License Key', 'revslider'),
			'Register License to unlock' => __('Register License to unlock', 'revslider'),

			'wdesktop' => __('Wide Desktop', 'revslider'),
			'desktop' => __('Desktop', 'revslider'),
			'laptop' => __('Notebook', 'revslider'),
			'tablet' => __('Tablet', 'revslider'),
			'mobile' => __('Mobile', 'revslider'),
			'Solid' => __('Solid', 'revslider'),
			'Dotted' => __('Dotted', 'revslider'),			
			'Dashed' => __('Dashed', 'revslider'),
			'Double' => __('Double', 'revslider'),
			'addon' => __('addon', 'revslider'),
			'blur' => __('Blur', 'revslider'),
			'bottom' => __('Bottom', 'revslider'),
			'cat_tag' => __('Categories & Tags', 'revslider'),
			'center' => __('Center', 'revslider'),
			'chars' => __('chars.', 'revslider'),
			'circle' => __('Circle', 'revslider'),
			'clip' => __('clip', 'revslider'),
			'color' => __('color', 'revslider'),
			'column' => __('Column', 'revslider'),
			'could_not_be_installed' => __('could not be installed.<br>The import process was canceled.', 'revslider'),
			'cover' => __('Cover', 'revslider'),
			'cycle' => __('Cycle', 'revslider'),
			'delete' => __('Delete', 'revslider'),
			'deleted' => __('deleted', 'revslider'),
			'drop_preset' => __('Add Palette', 'revslider'),
			'drop_search' => __('Search', 'revslider'),
			'duplicate' => __('Duplicate', 'revslider'),
			'duplicated' => __('duplicated', 'revslider'),
			'elibel_effects' => __('Effect', 'revslider'),
			'elibel_icons' => __('Icon', 'revslider'),
			'elibel_svgs' => __('SVG', 'revslider'),
			'elibel_images' => __('Image', 'revslider'),
			'elibel_layers' => __('Layer', 'revslider'),
			'elibel_videos' => __('Video', 'revslider'),
			'elibtag_all' => __('All Elements', 'revslider'),
			'elibtag_effects' => __('Effects', 'revslider'),
			'elibtag_icons' => __('Font Icons', 'revslider'),
			'elibtag_svgs' => __('SVG Icons', 'revslider'),
			'elibtag_images' => __('Images', 'revslider'),
			'elibtag_layers' => __('Layers', 'revslider'),
			'elibtag_videos' => __('Videos', 'revslider'),
			'embed' => __('Embed', 'revslider'),
			'exits already' => __('exits already', 'revslider'),
			'export' => __('Export', 'revslider'),
			'filter' => __('Filter', 'revslider'),
			'folder' => __('Folder', 'revslider'),
			'group' => __('Group', 'revslider'),
			'hline' => __('Horizontal Line', 'revslider'),
			'html' => __('HTML', 'revslider'),
			'inherit' => __('Inherit', 'revslider'),
			'itself' => __('Self', 'revslider'),
			'layer' => __('layer', 'revslider'),
			'left' => __('Left', 'revslider'),
			'libtag_Added Functionality' => __('Added Functionality', 'revslider'),
			'libtag_Dynamic Content' => __('Dynamic Content', 'revslider'),
			'libtag_Visual Effects' => __('Visual Effects', 'revslider'),
			'libtag_all' => __('All Categories', 'revslider'),
			'libtag_carousel' => __('Carousel', 'revslider'),
			'libtag_dynamic' => __('Dynamic Content', 'revslider'),
			'libtag_hero' => __('Hero', 'revslider'),
			'libtag_slider' => __('Slider', 'revslider'),
			'libtag_special' => __('Special Effects', 'revslider'),
			'libtag_website' => __('One Pager', 'revslider'),
			'lines' => __('lines', 'revslider'),
			'loop' => __('loop', 'revslider'),
			'mask' => __('mask', 'revslider'),
			'max_allowed_packet' => __('Max. Allowed Package', 'revslider'),
			'memory_limit' => __('Memory Limit', 'revslider'),
			'minutes left' => __('minutes left', 'revslider'),
			'module' => __('Module', 'revslider'),
			'motionpath' => __('MotionPath', 'revslider'),
			'next_prev' => __('Next / Previous', 'revslider'),
			'object_library_writable' => __('Object Library', 'revslider'),
			'of' => __('of', 'revslider'),
			'opacity' => __('Opacity', 'revslider'),
			'optimize' => __('Optimize', 'revslider'),
			'pan' => __('pan', 'revslider'),
			'parent' => __('Parent', 'revslider'),
			'popular' => __('Popular', 'revslider'),
			'post_max_size' => __('Max. Post Size', 'revslider'),
			'preview' => __('Preview', 'revslider'),
			'published' => __('Published', 'revslider'),
			'rX' => __('X Rotation', 'revslider'),
			'rY' => __('Y Rotation', 'revslider'),
			'rZ' => __('Z Rotation', 'revslider'),
			'random' => __('Random', 'revslider'),
			'recent' => __('Recent', 'revslider'),
			'related' => __('Related', 'revslider'),
			'rename' => __('Rename', 'revslider'),
			'right' => __('Right', 'revslider'),
			'rotationX' => __('X Rotation', 'revslider'),
			'rotationY' => __('Y Rotation', 'revslider'),
			'rotationZ' => __('Z Rotation', 'revslider'),
			'row' => __('Row', 'revslider'),
			'sX' => __('Scale X', 'revslider'),
			'sY' => __('Scale Y', 'revslider'),
			'samplelink' => __('Sample Link', 'revslider'),
			'sampletext' => __('A sample text paragraph<br>for your content needs', 'revslider'),
			'saved' => __('saved', 'revslider'),
			'Scale' => __('Scale', 'revslider'),
			'seconds left' => __('seconds left', 'revslider'),
			'selectfont' => __('Select Font Family', 'revslider'),
			'server_connect' => __('ThemePunch Server', 'revslider'),
			'settings not saved yet' => __('settings not saved yet', 'revslider'),
			'single' => __('Single', 'revslider'),
			'skX' => __('Skew X', 'revslider'),
			'skY' => __('Skew Y', 'revslider'),
			'slide' => __('Slide', 'revslider'),
			'spread' => __('Spread', 'revslider'),
			'square' => __('Square', 'revslider'),
			'stage' => __('Stage', 'revslider'),
			'thumb' => __('Thumb', 'revslider'),
			'thumbs' => __('Thumbs', 'revslider'),
			'title' => __('Title', 'revslider'),
			'to' => __('to', 'revslider'),
			'top' => __('Top', 'revslider'),
			'unpublished' => __('Unpublished', 'revslider'),
			'upload_folder_writable' => __('Upload folder writable', 'revslider'),
			'upload_max_filesize' => __('Upload Max Filesize', 'revslider'),
			'vline' => __('Vertical Line', 'revslider'),
			'with current Settings' => __('with current Settings', 'revslider'),
			'words' => __('words', 'revslider'),
			'Weather' => __('Weather', 'revslider'),
			'weather' => __('Weather', 'revslider'),
			'wrapper' => __('Wrapper', 'revslider'),
			'x' => __('X', 'revslider'),
			'y' => __('Y', 'revslider'),
			'z' => __('Z', 'revslider'),
			'zlib_enabled' => __('Zlib Library', 'revslider'),
			//Animation Translatings
			'Block Transitions (SFX)' => __('Block Transitions (SFX)', 'revslider'),
			'Letter Transitions' => __('Letter Transitions', 'revslider'),
			'Masked Transitions' => __('Masked Transitions', 'revslider'),
			'Pop Ups' => __('Pop Ups', 'revslider'),
			'Random Transitions' => __('Random Transitions', 'revslider'),
			'Rotations' => __('Rotations', 'revslider'),
			'Simple Transitions' => __('Simple Transitions', 'revslider'),
			'Skew Transitions' => __('Skew Transitions', 'revslider'),
			'Slide Transitions' => __('Slide Transitions', 'revslider'),
			'Block' => __('Block', 'revslider'),
			'from' => __('from', 'revslider'),
			'Letters' => __('Letters', 'revslider'),
			'Fly' => __('Fly', 'revslider'),
			'Letter' => __('Letter', 'revslider'),
			'Flip' => __('Flip', 'revslider'),
			'Cycle' => __('Cycle', 'revslider'),
			'Masked' => __('Masked', 'revslider'),
			'Zoom' => __('Zoom', 'revslider'),
			'Smooth' => __('Smooth', 'revslider'),
			'Mask' => __('Mask', 'revslider'),
			'Pop' => __('Pop', 'revslider'),
			'Up' => __('Up', 'revslider'),
			'Back' => __('Back', 'revslider'),			
			'Rotate' => __('Rotate', 'revslider'),			
			'Short' => __('Short', 'revslider'),
			'Long' => __('Long', 'revslider'),
			'Skew' => __('Skew', 'revslider'),
			'Fade' => __('Fade', 'revslider'),
			'Chars' => __('Chars.', 'revslider'),
			'Cube' => __('Cube', 'revslider'),
			'Slide Flip' => __('Slide Flip', 'revslider'),
			'Dynamic Toss' => __('Dynamic Toss', 'revslider'),
			'Stage Turn' => __('Stage Turn', 'revslider'),		
			'motion_photos_auto' => __('Advanced', 'revslider'),		
			'Base' => __('Base', 'revslider'),	
			'columns' => __('Columns', 'revslider'),	
			'Grid' => __('Grid', 'revslider'),	
			'Splits' => __('Splits', 'revslider'),	
			'$trans_fade' => __('Fade', 'revslider'),	
			'$trans_horizontalslide' => __('Horizontal Slide', 'revslider'),	
			'$trans_verticalslide' => __('Veertical Slide', 'revslider'),	
			'$trans_gradient' => __('Gradient', 'revslider'),	
			'$trans_slide' => __('Slide', 'revslider'),	
			'$trans_simple' => __('Simple', 'revslider'),	
			'$trans_boxes' => __('Boxes', 'revslider'),	
			'$trans_circle' => __('Circle Effects', 'revslider'),	
			'$trans_columns' => __('Columns', 'revslider'),	
			'$trans_rows' => __('Rows', 'revslider'),	
			'$trans_cross' => __('Cross', 'revslider'),	
			'$trans_parallax' => __('Parallax', 'revslider'),	
			'$trans_singleparallax' => __('Single Parallax Effect', 'revslider'),	
			'$trans_double' => __('Double Parallax Effect', 'revslider'),	
			'$trans_zoom' => __('Zoom', 'revslider'),
			'$trans_zoomslidein' => __('Zoom Out & Slide In', 'revslider'),
			'$trans_zoomslideout' => __('Slide Out & Zoom In', 'revslider'),
			'$trans_simplezoomin' => __('Zoom In', 'revslider'),
			'$trans_simplezoomout' => __('Zoom Out', 'revslider'),
			'$trans_blurzoom' => __('Blur & Zoom', 'revslider'),
			'$trans_brightness' => __('Brightness Transformation', 'revslider'),
			'$trans_grayscale' => __('Mono Transformation', 'revslider'),
			'$trans_sephia' => __('Sephia Transformation', 'revslider'),
			'$trans_in' => __('In', 'revslider'),
			'$trans_out' => __('Out', 'revslider'),
			'$trans_indark' => __('Dark In', 'revslider'),
			'$trans_outdark' => __('Dark Out', 'revslider'),
			'$trans_inlight' => __('Light In', 'revslider'),
			'$trans_outlight' => __('Light Out', 'revslider'),
			'$trans_filter' => __('Filters', 'revslider'),
			'$trans_effects' => __('Effects', 'revslider'),
			'$trans_boxes1' => __('Boxes Effects', 'revslider'),
			'$trans_boxes2' => __('Boxes Effects', 'revslider'),
			'$trans_boxes3' => __('Boxes Effects', 'revslider'),
			'$trans_boxes4' => __('Boxes Effects', 'revslider'),
			'$trans_slideover' => __('Coming Slide', 'revslider'),	
			'$trans_slideinout' => __('Coming & Leaving Slide', 'revslider'),
			'$trans_slideinoutfadein' => __('Both + Fade In', 'revslider'),
			'$trans_slideinoutfadeinout' => __('Both + Fade on & Out', 'revslider'),
			'$trans_remove' => __('Leaving Slide', 'revslider'),
			'$trans_stageturn' => __('Stage Turn', 'revslider'),
			'$trans_dynamictoss' => __('Dynamic Toss', 'revslider'),
			'$trans_slideflip' => __('Slide Flip', 'revslider'),
			'$trans_notransition' => __('No Transition', 'revslider'),	
			'$trans_simplefade' => __('Simple Fade', 'revslider'),	
			'$trans_zoomandfade' => __('Zoom & Fade', 'revslider'),	
			'$trans_crossfade' => __('Cross Fade', 'revslider'),	
			'$trans_dark' => __('Dark', 'revslider'),	
			'$trans_easy' => __('Light', 'revslider'),	
			'$trans_light' => __('Light', 'revslider'),	
			'$trans_vertical' => __('Vertical', 'revslider'),	
			'$trans_verticalsimple' => __('Vertical', 'revslider'),	
			'$trans_horizontalsimple' => __('Horizontal', 'revslider'),	
			'$trans_verticalzoom' => __('+Zoom', 'revslider'),	
			'$trans_horizontalzoom' => __('+Zoom', 'revslider'),	
			'$trans_cube' => __('Cube', 'revslider'),	
			'$trans_up' => __('Up', 'revslider'),	
			'$trans_bounce' => __('Bounce', 'revslider'),	
			'$trans_easeback' => __('EaseBack', 'revslider'),	
			'$trans_down' => __('Down', 'revslider'),	
			'$trans_left' => __('Left', 'revslider'),	
			'$trans_right' => __('Right', 'revslider'),	
			'$trans_tiny' => __('Tiny', 'revslider'),	
			'$trans_medium' => __('Medium', 'revslider'),				
			'$trans_strong' => __('Strong', 'revslider'),	
			'$trans_horizontal' => __('Horizontal', 'revslider'),	
			'$trans_transparent' => __('Transparent', 'revslider'),			
			'$trans_vary' => __('Alternate', 'revslider'),	
			'$trans_varydark' => __('A. Dark', 'revslider'),	
			'$trans_uniform' => __('Uniform', 'revslider'),	
			'$trans_horizontalrows' => __('Rows Horizontal Slide', 'revslider'),	
			'$trans_horizontalcols' => __('Columns Horizontal Slide', 'revslider'),	
			'$trans_verticalcols' => __('Columns Vertical Slide', 'revslider'),
			'$trans_verticalrows' => __('Rows Vertical Slide', 'revslider'),
			'$trans_horizontalboxes' => __('Boxes Horizontal Slide', 'revslider'),	
			'$trans_verticalboxes' => __('Boxes Vertical Slide', 'revslider'),
			'$trans_curtain' => __('Curtain Slots', 'revslider'),
			'$trans_rotation' => __('Rotated Slots', 'revslider'),
			'$trans_3deffects' => __('3D Effects', 'revslider'),
			'$trans_cuteffects' => __('Cut Effects', 'revslider'),
			'$trans_pulleffects' => __('Pull Effects', 'revslider'),
			'$trans_noiseeffects' => __('Noise Effects', 'revslider'),
			'$trans_dreameffects' => __('Dream Effects', 'revslider'),
			'$trans_Splits' => __('Splits', 'revslider'),
			'$trans_slidingboxes' => __('Sliding Boxes', 'revslider'),
			'$trans_slidingandzoomingboxes' => __('Sliding & Zooming Boxes', 'revslider'),
			'$trans_flyingboxes' => __('Flying Boxes', 'revslider'),


			'$trans_blck' => __('Colored Block', 'revslider'),
			'$trans_uncover' => __('Uncover', 'revslider'),
			'$trans_normal' => __('Normal', 'revslider'),
			'$trans_yoyo' => __('Yoyo', 'revslider'),
			'$trans_inplace' => __('In Place', 'revslider'),
			'$trans_mono' => __('Mono', 'revslider'),
			'$trans_fade' => __('Fade', 'revslider'),
			'$trans_flatter' => __('Flatter', 'revslider'),
			'$trans_floaty' => __('Floaty', 'revslider'),
			'$trans_fast' => __('Fast', 'revslider'),
			'$trans_soft' => __('Soft', 'revslider'),
			'$trans_heavy' => __('Heavy', 'revslider'),
			'$trans_sharp' => __('Sharp', 'revslider'),
			'$trans_pop' => __('pop', 'revslider'),
			'$trans_lightning' => __('Lightning', 'revslider'),
			'$trans_orbit' => __('Orbit', 'revslider'),
			'$trans_wave' => __('Wave', 'revslider'),
			'$trans_noloop' => __('No Loop', 'revslider'),
			'$trans_pulse' => __('Pulse', 'revslider'),
			'$trans_wiggleh' => __('Wiggle Horizontal', 'revslider'),
			'$trans_wigglev' => __('Wiggle Vertical', 'revslider'),			
			'$trans_flyin' => __('Fly In', 'revslider'),
			'$trans_flyinrandom' => __('Fly In Random', 'revslider'),
			'$trans_glide' => __('Glide', 'revslider'),
			'$trans_reveal' => __('Reveal', 'revslider'),
			'$trans_conceal' => __('Conceal', 'revslider'),
			'$trans_flip' => __('Flip', 'revslider'),
			'$trans_flipslide' => __('Flip & Slide', 'revslider'),
			'$trans_zoomin' => __('Zoom In', 'revslider'),
			'$trans_pressleft' => __('Press Left', 'revslider'),
			'$trans_pressright' => __('Press Right', 'revslider'),
			'$trans_popin' => __('Pop In', 'revslider'),
			'$trans_splitreveal' => __('Split Reveal', 'revslider'),
			'$trans_perspective' => __('Perspective', 'revslider'),
			'$trans_pendulum' => __('Pendulum', 'revslider'),
			'$trans_spin' => __('Spin', 'revslider'),
			'$trans_slide' => __('Slide', 'revslider'),
			'$trans_skew' => __('Skew', 'revslider'),
			'$trans_popup' => __('Pop Up', 'revslider'),
			'$trans_zoom' => __('Zoom', 'revslider'),
			'$trans_zoomin' => __('Zoom In', 'revslider'),
			'$trans_zoomout' => __('Zoom Out', 'revslider'),
			'$trans_masktrans' => __('Masked', 'revslider'),
			'$trans_throw' => __('Throw', 'revslider'),
			'$trans_diagonal' => __('Diagonal', 'revslider'),
			'$trans_shortdistance' => __('Short Distance', 'revslider'),
			'$trans_longdistance' => __('Long Distance', 'revslider'),
			'Letter Transitions' => __('Letter', 'revslider'),

//Actions
			'Element Actions' => __('Element Actions', 'revslider'),
			'Active Video in Slide' => __('Active Video in Slide', 'revslider'),
			'Slide BG Video' => __('Slide BG Video', 'revslider'),
			'Play Scene' => __('Play Scene', 'revslider'),
			'Toggle Scene' => __('Toggle Scene', 'revslider'),
			'Toggle Class' => __('Toggle Class', 'revslider'),
			'click_on' => __('Click on', 'revslider'),
			'Simulate User Click' => __('Simulate User Click', 'revslider'),
			'Slide Actions' => __('Slide Actions', 'revslider'),
			'Go To Slide' => __('Go To Slide', 'revslider'),
			'Go To Previous Slide' => __('Go To Previous Slide', 'revslider'),
			'Previous Slide' => __('Previous Slide', 'revslider'),
			'Go To Next Slide' => __('Go To Next Slide', 'revslider'),
			'Pause Slide Playback' => __('Pause Slide Playback', 'revslider'),
			'Resume Slide Playback' => __('Resume Slide Playback', 'revslider'),
			'Toggle Playback' => __('Toggle Playback', 'revslider'),
			'Simple Link' => __('Simple Link', 'revslider'),
			'Link Actions' => __('Link Actions', 'revslider'),
			'Menu Link' => __('Menu Link', 'revslider'),
			'Menu Link & Scroll' => __('Menu Link & Scroll', 'revslider'),
			'Simple Link' => __('Simple Link', 'revslider'),
			'Call Back' => __('Call Back', 'revslider'),
			'Call Back Function' => __('Call Back Function', 'revslider'),
			'Below Module' => __('Below Module', 'revslider'),
			'Gyroscope' => __('Gyroscope', 'revslider'),
			'Gyroscope Permission' => __('Gyroscope Permission', 'revslider'),
			'iOS Gyroscope' => __('iOS Gyroscope', 'revslider'),
			'iOS G. Permission' => __('iOS G. Permission', 'revslider'),
			'iOS Gyroscope Permission' => __('iOS Gyroscope Permission', 'revslider'),
			'Scroll' => __('Scroll', 'revslider'),
			'Scroll To' => __('Scroll To', 'revslider'),
			'Scroll To ID' => __('Scroll To ID', 'revslider'),
			'Scroll Below' => __('Scroll Below', 'revslider'),
			'Scroll Below Module' => __('Scroll Below Module', 'revslider'),
			'Modal' => __('Modal', 'revslider'),
			'Open Modal' => __('Open Modal', 'revslider'),
			'Close Modal' => __('Close Modal', 'revslider'),
			'Media' => __('Media', 'revslider'),
			'start_video' => __('Play Media', 'revslider'),
			'Run Script' => __('Run Script', 'revslider'),
			'Play Video' => __('Play Media', 'revslider'),
			'stop_video' => __('Stop Media', 'revslider'),
			'Stop Video' => __('Stop Media', 'revslider'),
			'toggle_video' => __('Toggle Media', 'revslider'),
			'Toggle Video' => __('Toggle Media', 'revslider'),
			'mute_video' => __('Mute Media', 'revslider'),
			'Mute Video' => __('Mute Media', 'revslider'),
			'unmute_video' => __('Unmute Media', 'revslider'),
			'Unmute Video' => __('Unmute Media', 'revslider'),
			'toggle_mute_video' => __('Toggle Mute Media', 'revslider'),
			'Toggle Mute Video' => __('Toggle Mute Media', 'revslider'),
			'Full Screen' => __('Full Screen', 'revslider'),
			'Enter Fullscreen' => __('Enter Fullscreen', 'revslider'),
			'Exit Fullscreen' => __('Exit Fullscreen', 'revslider'),
			'Toggle Fullscreen' => __('Toggle Fullscreen', 'revslider'),
			'Link Placeholder' => __('Link Placeholder', 'revslider'),
			'Alt-Click' => __('Alt-Click', 'revslider'),
			'Alt-Drag' => __('Alt-Drag', 'revslider'),
			'Shift-Click' => __('Shift-Click', 'revslider'),
			'Delete key' => __('Delete key', 'revslider'),
			'Click/Drag' => __('Click/Drag', 'revslider'),
			'to add points' => __('to add points', 'revslider'),
			'to get handles from corners' => __('to get handles from corners', 'revslider'),
			'to select multiple points' => __('to select multiple points', 'revslider'),
			'to remove points' => __('to remove points', 'revslider'),
			'the path itself' => __('the path itself', 'revslider'),
			'handles for fine-tuning curves' => __('handles for fine-tuning curves', 'revslider'),
			'Saving your settings and finalizing adjustments' => __('Saving your settings and finalizing adjustments','revslider')
		];

		return apply_filters('revslider_get_javascript_multilanguage', $lang);
	}
	
	/**
	 * returns all image sizes that have the same aspect ratio, rounded on the second
	 * @since: 6.1.4
	 **/
	public function get_same_aspect_ratio_images($images){
		$return = [];
		$images = (array)$images;
		/* @var RevSliderObjectLibrary $objlib */
		$objlib = RevSliderGlobals::instance()->get('RevSliderObjectLibrary');
		$upload_dir = wp_upload_dir();
		
		foreach($images ?? [] as $key => $image){
			//check if we are from object library
			if($objlib->_is_object($image)){
				$_img = $image;
				$image = $objlib->get_correct_size_url($image, 100, true);
				$objlib->_check_object_exist($image); //check to redownload if not downloaded yet
				
				$sizes = $objlib->get_sizes();
				$return[$key] = [];
				
				if(empty($sizes)) continue;
					
				foreach($sizes ?? [] as $size){
					$url = $objlib->get_correct_size_url($image, $size);
					$file = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
					$_size = getimagesize($file);
					$return[$key][$size] = [
						'url'	=> $url,
						'width'	=> $this->get_val($_size, 0),
						'height'=> $this->get_val($_size, 1),
						'size'	=> filesize($file)
					];
					
					if($_img === $url) $return[$key][$size]['default'] = true;
				}
				
				//$image = $objlib->get_correct_size_url($image, 100, true);
				$file = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image);
				$_size = getimagesize($file);
				$return[$key][100] = [
					'url'	=> $image,
					'width'	=> $this->get_val($_size, 0),
					'height'=> $this->get_val($_size, 1),
					'size'	=> filesize($file)
				];
				if($_img === $return[$key][100]['url']) $return[$key][100]['default'] = true;
			}else{
				$_img = (intval($image) === 0) ? $this->get_image_id_by_url($image) : $image;
				$img_data = wp_get_attachment_metadata($_img);
				
				if(empty($img_data)) continue;
				if(intval($this->get_val($img_data, 'width', 1)) === 0 || intval($this->get_val($img_data, 'height', 1)) === 0) continue;
				$return[$key] = [];
				$ratio = round($this->get_val($img_data, 'width', 1) / $this->get_val($img_data, 'height', 1), 2);
				$sizes = $this->get_val($img_data, 'sizes', []);
				$file = $upload_dir['basedir'] .'/'. $this->get_val($img_data, 'file');
				$return[$key]['orig'] = [
					'url'	=> $upload_dir['baseurl'] .'/'. $this->get_val($img_data, 'file'),
					'width'	=> $this->get_val($img_data, 'width'),
					'height'=> $this->get_val($img_data, 'height'),
					'size'	=> filesize($file)
				];
				if($image === $return[$key]['orig']['url']) $return[$key]['orig']['default'] = true;
			
				foreach($sizes ?? [] as $sn => $sv){
					if(intval($this->get_val($sv, 'width', 1)) === 0 || intval($this->get_val($sv, 'height', 1)) === 0) continue;
					$_ratio = round($this->get_val($sv, 'width', 1) / $this->get_val($sv, 'height', 1), 2);
					if($_ratio !== $ratio) continue;
						
					$i = wp_get_attachment_image_src($_img, $sn);
					if($i === false) continue;
					
					$file = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $this->get_val($i, 0));
					$return[$key][$sn] = [
						'url'	=> $this->get_val($i, 0),
						'width'	=> $this->get_val($sv, 'width'),
						'height'=> $this->get_val($sv, 'height'),
						'size'	=> filesize($file)
					];
					if($image === $return[$key][$sn]['url']) $return[$key][$sn]['default'] = true;
				}
			}
		}
		
		return $return;
	}
	
	/**
	 * get all available languages from Slider Revolution
	 **/
	public function get_available_languages(){
		$lang_codes = [
			'de_DE' => __('German', 'revslider'),
			'en_US' => __('English', 'revslider'),
			'fr_FR' => __('French', 'revslider'),
			'zh_CN' => __('Chinese', 'revslider')
		];
		
		$lang = get_available_languages(RS_PLUGIN_PATH.'languages/');
		$_lang = [];
		foreach($lang ?? [] as $k => $v){
			if(strpos($v, 'revsliderhelp-') !== false) continue;
			
			$_lc = str_replace('revslider-', '', $v);
			$_lang[$_lc] = (isset($lang_codes[$_lc])) ? $lang_codes[$_lc] : $_lc;
		}
		
		return $_lang;
	}

	/**
	 * function to check if the current page is a post/page in edit mode
	 */
	public function is_edit_page(){
		if(!is_admin()) return false;
		global $pagenow;

		return in_array($pagenow, ['post.php', 'post-new.php', 'widgets.php']);
	}
}
