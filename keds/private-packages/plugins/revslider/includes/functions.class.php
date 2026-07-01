<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/
 * @copyright 2024 ThemePunch
 */
 
if(!defined('ABSPATH')) exit();

class RevSliderFunctions extends RevSliderData {

	public function __construct(){
		parent::__construct();
	}

	/**
	 * attempt to load cache for _get_global_settings
	 * @return mixed
	 */
	public function get_global_settings(){
		return $this->get_wp_cache('_get_global_settings');
	}

	/**
	 * Get Global Settings
	 **/
	protected function _get_global_settings(){
		$gs = $this->get_options(['system', 'settings'], '');
		$gs = (!is_array($gs)) ? json_decode($gs, true) : $gs;
		
		return apply_filters('rs_get_global_settings', $gs);
	}

	/**
	 * update general settings
	 */
	public function set_global_settings($global, $merge = false){
		$this->delete_wp_cache('_get_global_settings');
		if($this->_truefalse($merge) === true){
			$_global = $this->get_global_settings();
			if(!is_array($_global)) $_global = [];
			if(!is_array($global)) $global = [];
			$global = array_replace_recursive($_global, $global);
		}
		
		$global = json_encode($global);
		
		$this->update_option(['system', 'settings'], $global);
		
		return true;
	}


	/**
	 * get all additions from the update checks
	 * @since: 6.2.0
	 **/
	public function get_addition($key = ''){
		$additions = (array)$this->get_options(['system', 'additions'], []);
		$additions = (!is_array($additions)) ? json_decode($additions, true) : $additions;
		
		return (empty($key)) ? $additions : $this->get_val($additions, $key);
	}


	/**
	 * throw an error
	 **/
	public function throw_error($message, $code = null){
		if(!empty($code)) throw new Exception($message, $code);

		throw new Exception($message);
	}


	/**
	 * get value from array. if not - return alternative
	 * 
	 * @param mixed $arr  could be array | object | scalar 
	 * @param mixed $key  could be array | string
	 * @param mixed $default  value to return if key not found
	 * @return mixed  
	 */
	public function get_val($arr, $key, $default = ''){
		//scalar =  int, float, string и bool
		if(is_scalar($arr)) return $default;
		//convert obj to array 
		if(is_object($arr)) $arr = (array)$arr;
		//if key is string, check immediately 
		if(!is_array($key)) return (isset($arr[$key])) ? $arr[$key] : $default;
		//loop thru keys
		foreach($key as $v){
			if(is_object($arr)) $arr = (array)$arr;
			if(isset($arr[$v])) {
				$arr = $arr[$v];
			} else {
				return $default;
			}
		}
		return $arr;
	}

	
	/**
	 * set parameter
	 * @since: 6.0
	 */
	public function set_val(&$base, $name, $value){
		if(is_array($name)){
			foreach($name as $key){
				if(is_array($base)){
					if(!isset($base[$key])) $base[$key] = [];
					$base = &$base[$key];
				}elseif(is_object($base)){
					if(!isset($base->$key)) $base->$key = new stdClass();
					$base = &$base->$key;
				}
			}
			$base = $value;
		}else{
			$base[$name] = $value;
		}
	}
	
	/**
	 * get POST variable
	 */
	public function get_post_var($key, $default = '', $esc = true){
		$val = $this->get_var($_POST, $key, $default);
		return ($esc) ? esc_html($val) : $val;
	}
	
	/**
	 * get GET variable
	 */
	public function get_get_var($key, $default = '', $esc = true){
		$val = $this->get_var($_GET, $key, $default);
		return ($esc) ? esc_html($val) : $val;
	}
	
	/**
	 * get POST or GET variable in this order
	 */
	public function get_request_var($key, $default = '', $esc = true){
		$val = (array_key_exists($key, $_POST)) ? $this->get_var($_POST, $key, $default) : $this->get_var($_GET, $key, $default);
		return ($esc) ? esc_html($val) : $val;
	}
	
	/**
	 * get a variable from an array,
	 */
	public function get_var($arr, $key, $default = ''){
		return (isset($arr[$key])) ? $arr[$key] : $default;
	}
	
	/**
	 * check for true and false in all possible ways
	 * @since: 6.0
	 **/
	public function _truefalse($v){
		if(in_array($v, ['false', false, 'off', NULL, 0, -1, "0"], true)) return false;
		if(in_array($v, ['true', true, 'on', 1, "1"], true)) return true;
		
		return $v;
	}
	
	/**
	 * validate that some value is numeric
	 */
	public function validate_numeric($val, $fn = 'Field'){
		$this->validate_not_empty($val, $fn);
		
		if(!is_numeric($val)) $this->throw_error($fn.__(' needs to be numeric', 'revslider'));
	}
	
	/**
	 * validate that some variable not empty
	 */
	public function validate_not_empty($val, $fn = 'Field'){
		if(empty($val) && is_numeric($val) == false) $this->throw_error($fn.__(' needs to not be empty', 'revslider'));
	}
	
	/**
	 * encode array into json for client side
	 */
	public function json_encode_client_side($arr){
		if(empty($arr)) return '{}';

		$json = (defined('JSON_INVALID_UTF8_IGNORE')) ? json_encode($arr, JSON_INVALID_UTF8_IGNORE) : json_encode($arr);
		$json = (!empty($json)) ? addslashes($json) : $json;
		
		return (empty($json)) ? '{}' : "'".$json."'";
	}
	
	
	/**
	 * turn a string into an array, check also for slashes!
	 * @since: 6.0
	 */
	public function json_decode_slashes($data){
		if(gettype($data) !== 'string') return $data;
	
		$data_decoded = json_decode(stripslashes($data), true);
		return (empty($data_decoded)) ? json_decode($data, true) : $data_decoded;
	}
	
	
	/**
	 * Convert std class to array, with all sons
	 * @return array|null
	 */
	public function class_to_array($arr){
		return json_decode(json_encode($arr), true);
	}
	
	/**
	 * Convert std class to array, single
	 * @return array
	 */
	public function class_to_array_single($arr){
		return (array)$arr;
	}
	
	/**
	 * Check Array for Value Recursive
	 */
	public function in_array_r($needle, $haystack, $strict = false){
		if(!is_array($haystack) || empty($haystack)) return false;

		foreach($haystack ?? [] as $item){
			if(($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict)))	return true;
		}
	
		return false;
	}
	
	/**
	 * compress an array/object/string to a string
	 * @since 6.6.0
	 **/
	public function do_compress($data, $level = 9){
		if(is_array($data) || is_object($data)) $data = json_encode($data);
		
		if(!function_exists('gzcompress') || !function_exists('gzuncompress')) return $data; //gzencode / gzdecode

		return base64_encode(gzcompress($data, $level));
	}

	/**
	 * decompress an string to an array/object/string
	 * @since 6.6.0
	 **/
	public function do_uncompress($data){
		if($data === false || empty($data) || is_array($data) || is_object($data)) return $data;
		$_data = json_decode($data, true);
		if(is_array($_data) || is_object($_data)) return $_data;
		if(!function_exists('gzcompress') || !function_exists('gzuncompress')) return $data; //gzencode / gzdecode

		$data = gzuncompress(base64_decode($data));
		$_data = json_decode($data, true);

		return (!empty($_data)) ? $_data : $data;
	}

	/**
	 * get attachment image url
	 */
	public function get_url_attachment_image($id, $size = 'full'){
		require_once(ABSPATH . 'wp-load.php');
		require_once(ABSPATH . 'wp-includes/pluggable.php');
		$image	= wp_get_attachment_image_src($id, $size);
		$url	= (empty($image)) ? false : $this->get_val($image, 0);
		if($url === false) $url = wp_get_attachment_url($id);
		
		return $url;
	}


	/**
	 * gets a temporary path where files can be stored
	 **/
	public function get_temp_path($path = 'rstemp'){
		if(function_exists('sys_get_temp_dir')){
			$temp = sys_get_temp_dir();
			if(@is_dir($temp) && wp_is_writable($temp)){
				$dir = trailingslashit($temp).$path.'/';
				if(!is_dir($dir)) @mkdir($dir, 0777, true);
				if(is_dir($dir) && wp_is_writable($dir)) return $dir;
			}
		}
	
		$temp = ini_get('upload_tmp_dir');
		if(@is_dir($temp) && wp_is_writable($temp)){
			$dir = trailingslashit($temp).$path.'/';
			if(!is_dir($dir)) @mkdir($dir, 0777, true);
			if(is_dir($dir) && wp_is_writable($dir)) return $dir;
		}

		$temp_dir = get_temp_dir();
		if(wp_is_writable($temp_dir)){
			$dir = trailingslashit($temp_dir).$path.'/';
			if(!is_dir($dir)) @mkdir($dir, 0777, true);
			if(is_dir($dir) && wp_is_writable($dir)) return $dir;
		}
		
		$upload_dir = wp_upload_dir();
		$dir		= $upload_dir['basedir'].'/'.$path.'/';
		if(!is_dir($dir)) @mkdir($dir, 0777, true);

		return $dir;
	}
	
	
	/**
	 * retrieve the image id from the given image url
	 */
	public function get_image_id_by_url($url){
		if($url === '') return false;

		$url = preg_replace('/-\d+x\d+(?=\.\w{3,4}$)/', '', $url);		
		$id	 = attachment_url_to_postid($url);

		return (is_null($id) || $id === 0) ? false : $id; //fix for B-5855627275
	}
	
	/**
	 * retrieve the image id from the given image filename/basename
	 * @since: 6.1.5
	 */
	public function get_image_id_by_basename($basename){
		global $wpdb;
		
		$var = $wpdb->get_var($wpdb->prepare("SELECT `post_id` FROM `".$wpdb->postmeta."` WHERE `meta_value` LIKE %s LIMIT 0,1", '%/'.$basename));
		
		return intval($var) ?: false;
	}
	
	/**
	 * get image url from image path.
	 */
	public function get_image_url_from_path($path){
		if(empty($path) || substr($path, -1) === '/' || substr($path, -1) === '\\') return ''; //check if the path ends with /, if yes its not a correct image path
		
		//check if it has an extension, if not leave it as it is
		if(empty(strtolower(pathinfo($path, PATHINFO_EXTENSION)))) return $path;
		
		//protect from absolute url
		$lower		= strtolower($path);
		$base_url	= $this->get_base_url();
		$return		= (strpos($lower, 'http://') !== false || strpos($lower, 'https://') !== false || strpos($lower, 'www.') === 0) ? $path : $base_url.$path;
		
		return ($return !== $base_url) ? preg_replace('~(?<!:)//+~', '/', $return) : '';
	}
	
	/**
	 * Check if Path is a Valid Image File
	 **/
	public function check_valid_image($url){
		if(empty($url)) return false;

		$ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
		
		return (in_array($ext, ['gif', 'jpg', 'jpeg', 'png', 'webp'])) ? $url : false;
	}
	
	/**
	 * get the upload URL of images
	 */
	public static function get_base_url(){
		return (is_multisite() == false) ? content_url().'/' : wp_upload_dir()['baseurl'].'/';
	}
	
	/**
	 * strip slashes recursive
	 * @since: 5.0
	 */
	public static function stripslashes_deep($value){
		if(empty($value)) return $value;

		$value = is_array($value) ? array_map(['RevSliderFunctions', 'stripslashes_deep'], $value) : stripslashes($value);
		
		return $value;
	}
	
	/**
	 * esc attr recursive
	 * @since: 6.0
	 */
	public static function esc_attr_deep($value){
		$value = is_array($value) ? array_map(['RevSliderFunctions', 'esc_attr_deep'], $value) : esc_attr($value);
		
		return $value;
	}
	
	
	/**
	 * get post types with categories for client side.
	 */
	public function get_post_types_with_categories_for_client(){
		$c = 0;
		$ret = [];
		$post_types = $this->get_post_types_with_taxonomies();
		foreach($post_types as $name => $tax){
			$cat = [];
			if(empty($tax)){
				$ret[$name] = $cat;
				continue;
			}
			
			foreach($tax as $tax_name => $tax_title){
				$cats = $this->get_categories_assoc($tax_name);
				if(empty($cats)) continue;

				$c++;
				$cat['option_disabled_'.$c] = '---- '. $tax_title .' ----';
				foreach($cats as $catID => $catTitle){
					$cat[$tax_name.'_'.$catID] = $catTitle;
				}
			}
			
			$ret[$name] = $cat;
		}

		return $ret;
	}
	
	
	/**
	 * get post types array with taxomonies
	 */
	public function get_post_types_with_taxonomies(){
		$post_types = $this->get_post_type_assoc();
		
		foreach($post_types ?? [] as $post_type => $title){
			$post_types[$post_type]	= $this->get_post_type_taxonomies($post_type);
		}
		
		return $post_types;
	}
	
	
	/**
	 * 
	 * get array of post types with categories (the taxonomies is between).
	 * get only those taxomonies that have some categories in it.
	 */
	public function get_post_types_with_categories(){
		$post_types_categories	= [];
		$post_types				= $this->get_post_types_with_taxonomies();
		
		foreach($post_types as $name => $tax){
			$ptwc = [];
			foreach($tax ?? [] as $tax_name => $tax_title){
				$cats = $this->get_categories_assoc($tax_name);
				if(!empty($cats)){
					$ptwc[$tax_name] = [
						'title'	=> $tax_title,
						'cats'	=> $cats
					];
				}
			}
			$post_types_categories[$name] = $ptwc;
		}
		
		return $post_types_categories;
	}
	
	
	/**
	 * get all the post types including custom ones
	 * the put to top items will be always in top (they must be in the list)
	 */
	public function get_post_type_assoc($put_to_top = []){
		$build_in		= ['post' => 'post', 'page'=>'page'];
		$custom_types	= get_post_types(['_builtin' => false]);
		
		//top items validation - add only items that in the customtypes list
		$top_updated	= [];
		foreach($put_to_top ?? [] as $top){
			if(in_array($top, $custom_types) == true){
				$top_updated[$top] = $top;
				unset($custom_types[$top]);
			}
		}
		
		$post_types = array_merge($top_updated, $build_in, $custom_types);
		
		//update label
		foreach($post_types ?? [] as $key => $type){
			$post_types[$key] = $this->get_post_type_title($type);
		}
		
		return $post_types;
	}
	
	
	/**
	 * return post type title from the post type
	 */
	public static function get_post_type_title($post_type){
		$obj_type = get_post_type_object($post_type);
		
		return (empty($obj_type)) ? ($post_type) : $obj_type->labels->singular_name;
	}
	
	
	/**
	 * get post type taxomonies
	 */
	public function get_post_type_taxonomies($post_type){
		$tax = get_object_taxonomies(['post_type' => $post_type], 'objects');
		
		if(empty($tax)) return [];

		$names	= [];
		foreach($tax ?? [] as $obj_tax){
			if($post_type === 'product' && !in_array($obj_tax->name, ['product_cat', 'product_tag'])) continue;
			$names[$obj_tax->name] = $obj_tax->labels->name;
		}
		
		return $names;
	}
	
	
	/**
	 * get post categories list assoc - id / title
	 */
	public function get_categories_assoc($taxonomy = 'category'){
		$categories	= [];
		if(strpos($taxonomy, ',') !== false){
			$taxes = explode(',', $taxonomy);
			foreach($taxes ?? [] as $tax){
				$cats		= $this->get_categories_assoc($tax);
				$categories	= array_merge($categories, $cats);
			}
		}else{
			$args = ['taxonomy' => $taxonomy, 'number' => 10000, 'hide_empty' => false];
			$cats = get_categories($args);
			foreach($cats ?? [] as $cat){
				$num				= $cat->count;
				$id					= $cat->cat_ID;
				$name				= ($num == 1) ? 'item' : 'items';
				$title				= $cat->name . ' ('.$num.' '.$name.')';
				$categories[$id]	= $title;
			}
		}
		
		return $categories;
	}
	
	
	/**
	 * check if css string is rgb
	 **/
	public function is_rgb($rgba){
		return (strpos($rgba, 'rgb') !== false) ? true : false;
	}
	
	
	/**
	 * check if file is in zip
	 * @since: 5.0
	 * $folder in v6 was images
	 */
	public function check_file_in_zip($path, $file, $alias, &$imported, $add_path = false, $folder = 'media'){ 
		global $wp_filesystem;
		
		$file = (is_array($file)) ? $this->get_val($file, 'url') : $file;
		if(trim($file) === '' || strpos($file, 'http') !== false) return $file; //http -> external image, do not change

		$strip	= false;
		$zimage	= $wp_filesystem->exists($path.$folder.'/'.$file);
		if(!$zimage){
			$zimage	= $wp_filesystem->exists(str_replace('//', '/', $path.$folder.'/'.$file));
			$strip	= true;
		}

		if($zimage !== false){
			if(!isset($imported[$folder.'/'.$file])){
				//check if we are object folder, if yes, do not import into media library but add it to the object folder
				$uimg = ($strip == true) ? str_replace('//', '/', $folder.'/'.$file) : $file; //pclzip
				
				if(strpos($uimg, 'revslider/objects/') === 0){ 
					// we are from the object library, copy the image to the objects folder if false
					/* @var RevSliderObjectLibrary $obj */
					$obj = RevSliderGlobals::instance()->get('RevSliderObjectLibrary');
					$importImage = $obj->_import_object($path.$folder.'/'.$uimg);
				}else{
					$importImage = $this->import_media($path.$folder.'/'.$uimg, $alias.'/');
					if (!$importImage['success']) $importImage = false;
				}
				
				if($importImage !== false){
					$imported[$folder.'/'.$file] = $importImage['path'];
					
					$file = $importImage['path'];
				}
			}else{
				$file = $imported[$folder.'/'.$file];
			}
		}

		if($add_path){
			$updir = wp_upload_dir()['baseurl'];
			if(strpos($file, $updir) === false) $file = str_replace('uploads/uploads/', 'uploads/', $updir . '/' . $file);
		}
		
		return $file;
	}
	
	/**
	 * Import the Media as it is
	 *
	 * @param string $name
	 * @param string $bitmap
	 * @return array
	 **/
	public function import_media_raw($name, $bitmap){
		$data = substr($bitmap, strpos($bitmap, ',') + 1);
		$data = base64_decode(str_replace(' ', '+', $data));
		if($data === false) {
			return [
				'success' => false,
				'message' => __('Image has an invalid type', 'revslider'),
			];
		}

		if(!preg_match('/^data:image\/(\w+);base64,/', $bitmap, $type)) {
			return [
				'success' => false,
				'message' => __('Image has an invalid data', 'revslider'),
			];
		}

		$type = strtolower($type[1]); // jpg, png, gif
		if(!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
			return [
				'success' => false,
				'message' => __('Image has an invalid type', 'revslider'),
			];
		}

		if(strpos($name, '.') !== false) $name = explode('.', $name)[0];

		$path = $this->get_temp_path('rstemp');
		$name = preg_replace("/[^a-zA-Z0-9\-\.\_]/", '', $name.'.'.$type);
		
		if(file_put_contents($path.$name, $data) === false) {
			return [
				'success' => false,
				'message' => __('Image could not be saved', 'revslider'),
			];
		}

		return $this->import_media($path.$name , 'video-media/');
	}

	/**
	 * normalize slashes in file path
	 * do not use with urls
	 *
	 * @param string $path
	 * @return string
	 */
	public function normalize_slashes($path){
		return preg_replace('#/+#', '/', $path);
	}

	/**
	 * @param string $value
	 * @return boolean
	 */
	function path_or_url_exists($value){
		// Remote URL
		if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
			$response = wp_remote_head( $value );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			return wp_remote_retrieve_response_code( $response ) === 200;
		}

		// Local filesystem path
		return file_exists( $value ) && is_readable( $value );
	}
	
	/**
	 * Import media from url
	 *
	 * @param string $file_url    URL of the existing file from the original site
	 * @param int    $folder_name The slidername will be used as a folder name in import
	 * @return array
	 */
	public function import_media($file_url, $folder_name, $filename = '', $post_content = '', $post_excerpt = ''){
		global $SR_GLOBALS;
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		WP_Filesystem();
		/* @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		if (!$this->path_or_url_exists($file_url)){
			return [
				'success' => false,
				'message' => sprintf( __( 'Source file not exists: %s', 'revslider' ), $file_url),
			];
		}

		$path_info = pathinfo($file_url);
		if(empty($path_info['extension']) || in_array(strtolower($path_info['extension']), $SR_GLOBALS['bad_extensions'])){
			return [
				'success' => false,
				'message' => sprintf( __( 'Extension not allowed: %s', 'revslider' ), $path_info['extension']),
			];
		}

		// folder inside uploads/revslider/
		$folder_name = trailingslashit( ltrim( $folder_name, '/' ) );

		$ul_dir = wp_upload_dir();
		$paths = [
			'sr' => 'revslider/',
			'basedir' => trailingslashit($ul_dir['basedir']),
			'baseurl' => trailingslashit($ul_dir['baseurl']),
			'filename' => (empty($filename)) ? basename($file_url) : $filename,
		];
		// relative to the upload dir
		$paths['relative'] = $this->normalize_slashes($paths['sr'] . $folder_name . $paths['filename']);
		// absolute path
		$paths['absolute'] = $paths['basedir'] . $paths['relative'];

		if (!wp_mkdir_p(dirname($paths['absolute']))) {
			return [
				'success' => false,
				'message' => sprintf( __( 'Could not create destination directory: %s', 'revslider' ), $paths['absolute']),
			];
		}

		$atc_id	= $this->get_image_id_by_url($paths['relative']);
		if (empty($atc_id)) {
			$atc_id = $this->get_image_id_by_basename($paths['filename']);
			if (!empty($atc_id)) {
				// the image was found through get_image_id_by_basename()
				// new save = found file location, if the files are the same
				$attached_file_path = get_attached_file($atc_id);
				if(!empty($attached_file_path) && @md5_file($attached_file_path) === @md5_file($file_url)){
					$paths['absolute'] = $attached_file_path;
					$paths['relative'] = str_replace($paths['basedir'], '', $paths['absolute']);
				}
			}
		}
		
		/**
		 * check if the files have matching md5, if not change the filename
		 * change save_dir so that the file is not
		 **/
		if (!empty($atc_id)) {
			if(!$wp_filesystem->exists($paths['absolute']) || @md5_file($file_url) !== @md5_file($paths['absolute'])){
				$fn_info = pathinfo($paths['filename']);
				$nr = 0;
				while(true){
					$nr++;
					$paths['filename'] = $fn_info['filename'] . $nr . '.' . $fn_info['extension'];
					$paths['relative'] = dirname($paths['relative']) . '/' . $paths['filename'];
					$paths['absolute'] = $paths['basedir'] . $paths['relative'];
					if(!$wp_filesystem->exists($paths['absolute'])){
						break;
					}
				}
				$atc_id = $this->get_image_id_by_url($paths['relative']);
			}
			//check if the file really exists in the filesystem, if not reset and redownload
			if(!$wp_filesystem->exists($paths['absolute'])) $atc_id = false;
		}

		if (empty($atc_id)) {
			@copy($file_url, $paths['absolute']);
			if(!$wp_filesystem->exists($paths['absolute'])){
				return [
					'success' => false,
					'message' => sprintf( __( 'Could not create destination file: %s', 'revslider' ), $paths['absolute']),
				];
			}

			$paths = apply_filters('revslider_import_media_insert_attachment_before', $paths);

			$file_info = getimagesize($paths['absolute']);

			// Create an array of attachment data to insert into wp_posts table
			$artdata = [
				'post_author'	 => 1, 
				'post_date'		 => current_time('mysql'),
				'post_date_gmt'	 => current_time('mysql'),
				'post_title'	 => $paths['filename'],
				'post_status'	 => 'inherit',
				'comment_status' => 'closed',
				'ping_status'	 => 'closed',
				'post_name'		 => sanitize_title_with_dashes(str_replace('_', '-', $paths['filename'])),
				'post_modified'	 => current_time('mysql'),
				'post_modified_gmt' => current_time('mysql'),
				'post_parent'	 => '',
				'post_type'		 => 'attachment',
				'guid'			 => $paths['baseurl'] . $paths['relative'],
				'post_mime_type' => $this->get_val($file_info, 'mime'),
				'post_excerpt'	 => $post_excerpt,
				'post_content'	 => $post_content
			];
			//insert the database record
			$attach_id = wp_insert_attachment($artdata, $paths['relative']);
			
			//generate metadata and thumbnails
			add_filter('intermediate_image_sizes_advanced', ['RevSliderFunctions', 'temporary_remove_sizes'], 10, 2);
			
			$rs_meta_create = get_option('rs_image_meta_todo', []);
			if(!isset($rs_meta_create[$attach_id])){
				$rs_meta_create[$attach_id] = $paths['absolute'];
				update_option('rs_image_meta_todo', $rs_meta_create);
			}
			if($attach_data = @wp_generate_attachment_metadata($attach_id, $paths['absolute'])){
				@wp_update_attachment_metadata($attach_id, $attach_data);
			}
		}else{
			$attach_id = $atc_id;
		}
		
		$paths['upload_relative'] = !is_multisite() ? 'uploads/' . $paths['relative'] : $paths['relative'];
		
		return [
			'success' => true,
			'id'      => $attach_id,
			'path'    => $paths['upload_relative'],
			'url'     => $paths['baseurl'] . $paths['relative'],
		];
	}
	
	
	/**
	 * temporary remove image sizes so that only the needed thumb will be created
	 * @since: 6.0
	 **/
	public static function temporary_remove_sizes($sizes, $meta = false){
		return (!empty($sizes) && isset($sizes['thumbnail'])) ? ['thumbnail' => $sizes['thumbnail']] : $sizes;
	}
	
	
	/**
	 * get wp-content path
	 */
	public function get_upload_path(){
		if(is_multisite()){
			global $wpdb;
			return (!defined('BLOGUPLOADDIR')) ? ABSPATH . 'wp-content/uploads/sites/' . $wpdb->blogid : BLOGUPLOADDIR;
		}
		
		return (!empty(WP_CONTENT_DIR)) ? WP_CONTENT_DIR . '/' : ABSPATH . 'wp-content/uploads/';
	}
	
	/**
	 * get contents of the static css file
	 */
	public function get_static_css(){
		return $this->get_options(['other', 'static-css'], '');
	}
	
	/**
	 * get contents of the static css file
	 */
	public function update_static_css($css){
		$css = str_replace(["\'", '\"', '\\\\'], ["'", '"', '\\'], trim($css));
		update_option('revslider-static-css', $css);
		return $css;
	}

	/**
	 * get the client browser with version
	 **/
	public function get_browser(){
		$u_agent	= $this->get_val($_SERVER, 'HTTP_USER_AGENT');
		$bname		= 'Unknown';
		$platform	= 'Unknown';
		$version	= '';
		$ub			= '';

		// get platform
		if (preg_match('/linux/i', $u_agent)) {
			$platform = 'linux';
		} elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
			$platform = 'mac';
		} elseif (preg_match('/windows|win32/i', $u_agent)) {
			$platform = 'windows';
		}

		// get name of useragent
		if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) {
			$bname = 'Internet Explorer';
			$ub = 'MSIE';
		} elseif(preg_match('/Firefox/i',$u_agent)) {
			$bname = 'Mozilla Firefox';
			$ub = 'Firefox';
		} elseif(preg_match('/OPR/i',$u_agent))	{
			$bname = 'Opera';
			$ub = 'Opera';
		} elseif(preg_match('/Chrome/i',$u_agent) && !preg_match('/Edg/i',$u_agent)) {
			$bname = 'Google Chrome';
			$ub = 'Chrome';
		} elseif(preg_match('/Safari/i',$u_agent) && !preg_match('/Edg/i',$u_agent)) {
			$bname = 'Apple Safari';
			$ub = 'Safari';
		} elseif(preg_match('/Netscape/i',$u_agent)) {
			$bname = 'Netscape';
			$ub = 'Netscape';
		} elseif(preg_match('/Edg/i',$u_agent)) {
			$bname = 'Edge';
			$ub = 'Edg';
		} elseif(preg_match('/Trident/i',$u_agent)) {
			$bname = 'Internet Explorer';
			$ub = 'MSIE';
		}

		// get version
		$known		= ['Version', $ub, 'other'];
		$pattern	= '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		if (!preg_match_all($pattern, $u_agent, $matches)){ /* */ }
		// see how many we have
		$i			= count($this->get_val($matches, 'browser'));
		$version	= $this->get_val($matches, ['version', 0]);
		if ($i != 1) {
			//we will have two since we are not using the 'other' argument yet
			//see if the version is before or after the name
			$version = (strripos($u_agent, 'Version') < strripos($u_agent,$ub)) ? $version : $this->get_val($matches, ['version', 1]);
		}

		// check if we have a number
		if ($version == null || $version == '') $version = '0';

		return [
			'name'		=> $bname,
			'version'	=> $version,
			'platform'	=> $platform
		];
    }

	/**
	 * Change FontURL to new URL (added for chinese support since google is blocked there)
	 * @since: 5.0
	 */
	public function modify_fonts_url($url, $remove = true){
		$gs = $this->get_global_settings();
		$df = $this->get_val($gs, ['fonts', 'url'], '');
		$df = (!in_array($df, ['', 'off'])) ? $df : $url;

		return ($remove) ? $this->remove_http($df) : $df;
	}
	
	/**
	 * convert date to the date format that the user chose.
	 */
	public function convert_post_date($date, $with_time = false){
		if(empty($date)) return $date;

		return ($with_time) ? date_i18n(get_option('date_format').' '.get_option('time_format'), strtotime($date)) : date_i18n(get_option('date_format'), strtotime($date));
	}
	
	
	/**
	 * return biggest value of object depending on which devices are enabled
	 * @since: 5.0
	 **/
	public function get_biggest_device_setting($obj, $enabled_devices, $default = '########'){
		$dv = $this->get_val($obj, ['d', 'v']);
		if($this->get_val($enabled_devices, 'd') === true && $dv != '') return $dv;
		if($default !== '########') return $default;
		$nv = $this->get_val($obj, ['n', 'v']);
		if($this->get_val($enabled_devices, 'n') === true && $nv != '') return $nv;
		$tv = $this->get_val($obj, ['t', 'v']);
		if($this->get_val($enabled_devices, 't') === true && $tv != '') return $tv;
		$mv = $this->get_val($obj, ['m', 'v']);
		if($this->get_val($enabled_devices, 'm') === true && $mv != '') return $mv;
		
		return '';
	}

	/**
	 * return biggest value of object depending on which devices are enabled
	 * @since: 5.0
	 **/
	public function get_biggest_device_setting_v7($obj, $enabled_devices, $default = '########'){
		if(empty($obj)) return ($default !== '########') ? $default : '';
		
		$devices = ['ld', 'd', 'n', 't', 'm'];
		foreach($devices as $key => $device){
			if($this->get_val($enabled_devices, $device) === true && !in_array($obj[$key], ['', '#a'])) return $obj[$key];
		}

		return ($default !== '########') ? $default : $obj[1];
	}

	/**
	 * return biggest value of object depending on which devices are enabled
	 * @since: 5.0
	 **/
	public function get_biggest_size_setting_v7($obj, $enabled_devices, $default = '########'){
		$devices = ['ld', 'd', 'n', 't', 'm'];
		$biggest = false;

		foreach($devices ?? [] as $key => $device){
			if($this->get_val($enabled_devices, $device) === true && !in_array($obj[$key], ['', '#a'])) if($biggest === false || intval($obj[$key]) > $biggest) $biggest = $obj[$key];
		}

		return ($biggest === false && $default !== '########') ? $default : intval($biggest);
	}
	
	
	/**
	 * normalize object with device informations depending on what is enabled for the Slider
	 * @since: 5.0
	 **/
	public function normalize_device_settings($obj, $enabled_devices, $return = 'obj', $default = [], $set_to_if = [], $use = ','){ //array -> from -> to
		/*d n t m*/
		$obj			= $this->fill_device_settings($obj);
		$_def			= (!empty($default)) ? reset($default) : '########';
		$inherit_size	= $this->get_biggest_device_setting($obj, $enabled_devices, $_def);
		
		if(!empty($set_to_if)){
			foreach($obj as $device => $key){
				foreach($set_to_if as $from => $to){
					if(trim($this->get_val($obj, [$device, 'v'])) == $from) $obj[$device]['v'] = $to;
				}
			}
		}

		$device_types = ['d', 'n', 't', 'm'];
		foreach($device_types as $device){
			if($enabled_devices[$device] === true){
				$value = $this->get_val($obj, [$device, 'v'], '');
				if($value === ''){
					$obj[$device]['v'] = ($_def !== '########') ? $_def : $inherit_size;
				}else{
					$inherit_size = $value;
				}
			}else{
				$obj[$device]['v'] = $inherit_size;
			}
		}
	
		switch ($return) {
			case 'obj':
				return [
					'd' => $obj['d']['v'],
					'n' => $obj['n']['v'],
					't' => $obj['t']['v'],
					'm' => $obj['m']['v']
				];
			case 'html-array':
				$html_array = ($obj['d']['v'] === $obj['n']['v'] && $obj['d']['v'] === $obj['m']['v'] && $obj['d']['v'] === $obj['t']['v']) ? $obj['d']['v'] : implode($use, array_column($obj, 'v'));

				return (!empty($default) && in_array($html_array, $default)) ? '' : $html_array;
			case 'array':
				$array = [];
				if($obj['d']['v'] === $obj['n']['v'] && $obj['d']['v'] === $obj['m']['v'] && $obj['d']['v'] === $obj['t']['v']){
					$array[$obj['d']['v']] = $obj['d']['v'];
				}else{
					$array[$obj['d']['v']] = $this->get_val($obj, ['d', 'v']);
					$array[$obj['n']['v']] = $this->get_val($obj, ['n', 'v']);
					$array[$obj['t']['v']] = $this->get_val($obj, ['t', 'v']);
					$array[$obj['m']['v']] = $this->get_val($obj, ['m', 'v']);
					if(!empty($array)){
						foreach($array as $k => $v){
							if(trim($v) === ''){
								unset($array[$k]);
							}
						}
					}
				}
				
				return $array;
		}
	
		return $obj;
	}
	
	/**
	 * fill object with default values
	 * @since: 6.0
	 **/
	public function fill_device_settings($obj){
		$push = ['d', 'n', 't', 'm'];
		
		if(is_string($obj)){
			$t = $obj;
			$obj = [];
			foreach($push as $p){
				$obj[$p] = ['v' => $t];
			}
			return $obj;
		}
		
		$_obj = [];
		foreach($push as $p){
			$_obj[$p] = (!isset($obj[$p])) ? [] : $obj[$p];
			if(!isset($_obj[$p]['v'])){
				$_obj[$p]['v'] = '';
				$_obj[$p]['u'] = '';
			}
		}
		
		return $_obj;
	}

	/**
	 * get the values for the given transition
	 **/
	public function get_slide_transition_values($transition, $base_transitions = []){
		if(empty($base_transitions)) $base_transitions = $this->get_base_transitions();
		foreach($base_transitions as $t){
			if(!is_array($t)) continue;
			foreach($t as $_t){
				if(!is_array($_t)) continue;
				foreach($_t as $name => $values){
					if($name !== $transition) continue;
					
					return $values;
				}
			}
		}
		return [];
	}
	
	
	/**
	 * get a random slide transition for the given main and grp
	 **/
	public function get_random_slide_transition($main, $grp, $base_transitions = []){
		if(empty($base_transitions)) $base_transitions = $this->get_base_transitions();
		
		if(!is_array($grp) && !empty($grp)) $grp = explode(',', $grp);
		if($grp === '') $grp = [];
		
		$items = [];
		foreach($base_transitions as $m => $bt){
			if(!is_string($m) || $m === 'random' || $m === 'custom' || ($main !== 'all' && $main !== $m)) continue;
			foreach($bt as $g => $_bt){
				if(is_array($_bt) && $g !== 'icon' && (empty($grp) || in_array($g, $grp))){
					foreach($_bt as $e => $__bt){
						$items[] = $e;
					}
				}
			}
		}
		
		$num = (!empty($items)) ? array_rand($items, 1) : false;
		return ($num !== false) ? $items[$num] : '';
	}
	
	
	/**
	 * Remove http:// and https://
	 * @since: 6.0.0
	 **/
	public function remove_http($url, $special = 'auto'){
		switch($special){
			case 'http':
				$url = str_replace('https://', 'http://', $url);
				if(strpos($url, 'http://') === false) $url = 'http://'.$url;
			break;
			case 'https':
				$url = str_replace('http://', 'https://', $url);
				if(strpos($url, 'https://') === false) $url = 'https://'.$url;
			break;
			case 'keep': //do nothing
			break;
			case 'auto':
			default:
				$url = str_replace(['http://', 'https://'], '//' , $url);
			break;
		}
		return $url;
	}

	/**
	 * set the memory limit to at least 256MB if possible
	 * @since: 6.1.6
	 **/
	public static function set_memory_limit(){
		wp_raise_memory_limit('revslider');
	}
	
	
	/**
	 * Check if page is edited in Gutenberg
	 */
	public function _is_gutenberg_page(){
		if(isset($_GET['action']) && $_GET['action'] == 'elementor') return false; // Elementor Page Edit
		if(isset($_GET['vc_action']) && $_GET['vc_action'] == 'vc_inline') return false; // WP Bakery Front Edit
		if(function_exists('is_gutenberg_page') && is_gutenberg_page()) return true; // Gutenberg Edit with WP < 5
		if(function_exists('get_current_screen')){
			$current_screen = get_current_screen();
			if(!empty($current_screen) && method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor()) return true; //Gutenberg Edit with WP >= 5
		}
		return false;
	}
	
	
	
	/**
	 * get custom transitions
	 **/
	public function get_custom_slidetransitions(){
		$custom = $this->get_options(['other', 'slide-transitions'], []);
		
		return apply_filters('rs_get_custom_slidetransitions', $custom);
	}
	
	
	/**
	 * get custom transitions
	 **/
	public function save_custom_slidetransitions($template){
		$custom = $this->get_custom_slidetransitions();
		
		//empty custom templates?
		if(empty($custom)){
			$custom = [];
			$new_id = 1;
		}else{
			$id = $this->get_val($template, 'id', 0);
			//custom templates exist
			$new_id = ($id > 0) ? $id : max(array_keys($custom)) + 1;
		}
		
		//update or insert template
		$custom[$new_id]['title']	= $template['obj']['title'];
		$custom[$new_id]['preset']	= $template['obj']['preset'];
		//return the ID the template was saved with
		$this->update_option(['other', 'slide-transitions'], $custom);

		return $new_id;
	}
	
	
	/**
	 * get custom transitions
	 **/
	public function delete_custom_slidetransitions($template){
		//load templates array
		$custom = $this->get_custom_slidetransitions();
		
		$id = intval($this->get_val($template, 'id', 0));
		//custom template exist
		if($id > 0 && isset($custom[$id])){
			//delete given ID
			unset($custom[$id]);
			//save the resulting templates array again
			$this->update_option(['other', 'slide-transitions'], $custom);
			return true;	
		}
		
		return false;
	}
	/**
	 * push the matieral icons css into the global variable
	 **/
	public function add_material_icons(){
		global $SR_GLOBALS;
		if($this->get_val($SR_GLOBALS, ['icon_sets', 'Materialicons', 'css'], false) !== false) return '';

		$gs = $this->get_global_settings();

		if($this->get_val($gs, ['fonts', 'download'], 'off') === 'off'){
			$font_face = "@font-face {
  font-family: 'Material Icons';
  font-style: normal;
  font-weight: 400;  
  src: url(//fonts.gstatic.com/s/materialicons/v41/flUhRq6tzZclQEJ-Vdg-IuiaDsNcIhQ8tQ.woff2) format('woff2');
}";
		}else{
			$font_face = "@font-face {
font-family: 'Material Icons';
font-style: normal;
font-weight: 400;  

src: local('Material Icons'),
local('MaterialIcons-Regular'),
  url(".RS_PLUGIN_URL_CLEAN."public/css/fonts/material/MaterialIcons-Regular.woff2) format('woff2'),
  url(".RS_PLUGIN_URL_CLEAN."public/css/fonts/material/MaterialIcons-Regular.woff) format('woff'),  
  url(".RS_PLUGIN_URL_CLEAN."public/css/fonts/material/MaterialIcons-Regular.ttf) format('truetype');
}";
		}

		$this->set_val($SR_GLOBALS, ['icon_sets', 'Materialicons', 'css'], "/* 
ICON SET 
*/
".$font_face."

rs-module .material-icons {
  font-family: 'Material Icons';
  font-weight: normal;
  font-style: normal;
	font-size: inherit;
  display: inline-block;  
  text-transform: none;
  letter-spacing: normal;
  word-wrap: normal;
  white-space: nowrap;
  direction: ltr;
  vertical-align: top;
  line-height: inherit;
  /* Support for IE. */
  font-feature-settings: 'liga';

  -webkit-font-smoothing: antialiased;
  text-rendering: optimizeLegibility;
  -moz-osx-font-smoothing: grayscale;
}");
	}
	
	/**
	 * get the current page id
	 * @since: 6.0
	 **/
	public function get_current_page_id(){
		$id = '';
		
		if(is_front_page() == true || is_home() == true){
			$id = 'homepage';
		}else{
			global $post;
			$id = (isset($post->ID)) ? $post->ID : $id;
		}
		
		return $id;
	}

	/**
	 * this will return the exact alias of the rev_slider modules on given posts/pages
	 **/
	public function get_shortcode_from_page($ids){
		$_shortcodes = [];
		$ids		 = (!is_array($ids)) ? (array)$ids : $ids;

		foreach($ids as $id){
			$post = get_post($id);
			$sc = [];
			if(is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'rev_slider') || has_shortcode($post->post_content, 'sr7'))){
				preg_match_all('/\[sr7.*alias=.(.*)"\]/', $post->post_content, $shortcodes);
				preg_match_all('/\[rev_slider.*alias=.(.*)"\]/', $post->post_content, $shortcodesold);
				if(isset($shortcodes[1]) && $shortcodes[1] !== '') $sc = $shortcodes[1];
				if(isset($shortcodesold[1]) && $shortcodesold[1] !== '') $sc = array_merge($sc, $shortcodesold[1]);
				
				if(!empty($sc)){
					foreach($sc as $k => $s){
						if(strpos($s, '"') !== false) $s = $this->get_val(explode('"', $s), 0);
						if(!in_array($s, $_shortcodes)) $_shortcodes[] = $s;
					}
				}
			}
		}
		
		return $_shortcodes;
	}

	/**
	 * checks if any shortcode format is present in given string
	 */
	public function has_any_shortcode($text){
		return (preg_match('/\[.*?\]/', $text)) ? true : false;
	}

	/**
	 * open and checks a zip file for filetypes
	 **/
	public function check_bad_files($zip_file, $extensions_allowed = false){
		global $SR_GLOBALS;
		if(class_exists('ZipArchive')){
			$zip = new ZipArchive;
			$success = $zip->open($zip_file);
			
			if($success !== true) $this->throw_error(__("Can't open zip file", 'revslider'));

			for($i = 0; $i < $zip->numFiles; $i++){
				$path_info = pathinfo($zip->getNameIndex($i));
				if(!isset($path_info['extension'])) continue;
			
				$pi = strtolower($path_info['extension']);
				if($extensions_allowed !== false){
					if(!in_array($pi, $extensions_allowed)) $this->throw_error(__("zip file contains illegal files", 'revslider'));
				}else{
					if(in_array($pi, $SR_GLOBALS['bad_extensions'])) $this->throw_error(__("zip file contains illegal files", 'revslider'));
				}
			}
		}else{ //fallback to pclzip
			require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
			
			$pclzip = new PclZip($zip_file);
			
			$content = $pclzip->listContent();
			if(is_array($content) && !empty($content)){
				foreach($content as $file){
					if(!isset($file['filename'])) continue;

					$path_info = pathinfo($file['filename']);
					if(!isset($path_info['extension'])) continue;

					$pi = strtolower($path_info['extension']);
					if($extensions_allowed !== false){
						if(!in_array($pi, $extensions_allowed)) $this->throw_error(__("zip file contains illegal files", 'revslider'));
					}else{
						if(in_array($pi, $SR_GLOBALS['bad_extensions'])) $this->throw_error(__("zip file contains illegal files", 'revslider'));
					}
				}
			}
		}
	}
	
	/**
	 * generate missing attachement metadata for images
	 * @since: 6.0
	 **/
	public function generate_attachment_metadata(){
		$rs_meta_create = $this->get_options(['other', 'image-meta'], []);
		
		if(!empty($rs_meta_create)){
			foreach($rs_meta_create as $attach_id => $save_dir){
				unset($rs_meta_create[$attach_id]);
				$this->update_option(['other', 'image-meta'], $rs_meta_create);

				if($attach_data = @wp_generate_attachment_metadata($attach_id, $save_dir)){
					@wp_update_attachment_metadata($attach_id, $attach_data);
				}
			}
		}
	}
	
	/**
	 * set the font clean for import
	 */
	public function set_clean_font_import($font, $class = '', $url = '', $variants = [], $subsets = []){
		global $SR_GLOBALS;
		
		if(!isset($SR_GLOBALS['fonts'])) $SR_GLOBALS['fonts'] = ['queue' => [], 'loaded' => []]; //if this is called without revslider.php beeing loaded
		
		if(!empty($variants) || !empty($subsets)){
			if(!isset($SR_GLOBALS['fonts']['queue'][$font])) $SR_GLOBALS['fonts']['queue'][$font] = [];
			if(!isset($SR_GLOBALS['fonts']['queue'][$font]['variants'])) $SR_GLOBALS['fonts']['queue'][$font]['variants'] = [];
			if(!isset($SR_GLOBALS['fonts']['queue'][$font]['subsets'])) $SR_GLOBALS['fonts']['queue'][$font]['subsets'] = [];
			
			if(!empty($variants)){
				foreach($variants as $k => $v){
					//check if the variant is already in loaded
					if(!in_array($v, $SR_GLOBALS['fonts']['queue'][$font]['variants'], true)){
						$SR_GLOBALS['fonts']['queue'][$font]['variants'][] = $v;
					}else{ //already included somewhere, so do not call it anymore
						unset($variants[$k]);
					}
				}
			}
			if(!empty($subsets)){
				foreach($subsets as $k => $v){
					if(!in_array($v, $SR_GLOBALS['fonts']['queue'][$font]['subsets'], true)){
						$SR_GLOBALS['fonts']['queue'][$font]['subsets'][] = $v;
					}else{ //already included somewhere, so do not call it anymore
						unset($subsets[$k]);
					}
				}
			}
			if($url !== ''){
				$SR_GLOBALS['fonts']['queue'][$font]['url'] = $url;
			}
		}
	}

	
	/**
	 * get categories list, copy the code from default wp functions
	 */
	public function get_categories_html($cat_ids, $tax = null, $post_id = '', $full = false){
		global $wp_rewrite;

		if(!empty($post_id) && $full === false) return get_the_category_list(', ', null, $post_id);
		
		$categories	= ($full === true && !empty($cat_ids)) ? $cat_ids :  $this->get_categories_by_id($cat_ids, $tax);
		$errors		= $this->get_val($categories, 'errors');
		$list		= [];
		$err		= '';
		$rel 		= (is_object($wp_rewrite) && $wp_rewrite->using_permalinks()) ? 'rel="category tag"' : 'rel="category"';
		
		if(!empty($errors)){
			foreach($errors as $error){
				$err .= implode(',', $error);
			}
			$this->throw_error(__('retrieving categories error: '.esc_html($err)));
		}
		
		foreach($categories as $category){
			$link = get_category_link($this->get_val($category, 'term_id'));
			$name = $this->get_val($category, 'name');

			$list[] = (!empty($link)) ? '<a href="' . esc_url($link) . '" title="' . esc_attr(sprintf(__('View all posts in %s', 'revslider'), $name)) .'" '. $rel .'>'. $name .'</a>' : $name;
		}

		return (!empty($list)) ? implode(', ', $list) : '';
	}

	/**
	 * get text intro, limit by number of words
	 */
	public function get_text_intro($text, $limit){
		$limit++;
		$array = explode(' ', $text, $limit);
		
		if(count($array) >= $limit){
			array_pop($array);
			$intro = implode(' ', $array);
			$intro = trim($intro);
			$intro .= (!empty($intro)) ? '...' : '';
		}else{
			$intro = $text;
		}
		
		return preg_replace('`\[[^\]]*\]`', '', $intro);
	}
	
	/**
	 * get text intro, limit by number of words
	 */
	public function get_text_intro_chars($text, $limit){
		$intro = substr($text, 0, $limit);
		return preg_replace('`\[[^\]]*\]`', '', $intro);
	}

	/**
	 * convert assoc array to array
	 */
	public static function assoc_to_array($assoc){
		return array_values($assoc ?? []);
	}

	/**
	 * filter non-allowed chars for html classes / IDs
	 * 
	 * @param array|string $classes
	 * @return array|string
	 */
	public function filter_class_name($classes){
		$single = false;
		if(!is_array($classes)){
			$classes = [$classes];
			$single = true;
		}

		$classes = array_map(function($className) {
			return preg_replace('/[^a-zA-Z \d_-]/', '', $className);
		}, $classes);

		return $single ? $classes[0] : $classes;
	}

	public function add_deprecation_message($old_func, $new_func){
		global $SR_GLOBALS;

		if(isset($SR_GLOBALS['deprecated'][$old_func])) return;
		//_deprecated_function($old_func, '7.0', $new_func);
		$SR_GLOBALS['deprecated'][$old_func] = $new_func;
	}
	
	/**
	 * Add Meta Generator Tag in FrontEnd
	 * @since: 5.4.3
		//NOT COMPRESSED VERSION
		function setREVStartSize(e){	
			//window.requestAnimationFrame(function() {	
				window.RSIW = window.RSIW===undefined ? window.innerWidth : window.RSIW;	
				window.RSIH = window.RSIH===undefined ? window.innerHeight : window.RSIH;	
				try {								
					var pw = document.getElementById(e.c).parentNode.offsetWidth,
						newh;
					pw = pw===0 || isNaN(pw) || (e.l=="fullwidth" || e.layout=="fullwidth") ? window.RSIW : pw;
					e.tabw = e.tabw===undefined ? 0 : parseInt(e.tabw);
					e.thumbw = e.thumbw===undefined ? 0 : parseInt(e.thumbw);
					e.tabh = e.tabh===undefined ? 0 : parseInt(e.tabh);
					e.thumbh = e.thumbh===undefined ? 0 : parseInt(e.thumbh);
					e.tabhide = e.tabhide===undefined ? 0 : parseInt(e.tabhide);
					e.thumbhide = e.thumbhide===undefined ? 0 : parseInt(e.thumbhide);
					e.mh = e.mh===undefined || e.mh=="" || e.mh==="auto" ? 0 : parseInt(e.mh,0);
					if(e.layout==="fullscreen" || e.l==="fullscreen")
						newh = Math.max(e.mh,window.RSIH);
					else{					
						e.gw = Array.isArray(e.gw) ? e.gw : [e.gw];
						for (var i in e.rl) if (e.gw[i]===undefined || e.gw[i]===0) e.gw[i] = e.gw[i-1];
						e.gh = e.el===undefined || e.el==="" || (Array.isArray(e.el) && e.el.length==0)? e.gh : e.el;
						e.gh = Array.isArray(e.gh) ? e.gh : [e.gh];
						for (var i in e.rl) if (e.gh[i]===undefined || e.gh[i]===0) e.gh[i] = e.gh[i-1];
											
						var nl = new Array(e.rl.length),
							ix = 0,
							sl;
						e.tabw = e.tabhide>=pw ? 0 : e.tabw;
						e.thumbw = e.thumbhide>=pw ? 0 : e.thumbw;
						e.tabh = e.tabhide>=pw ? 0 : e.tabh;
						e.thumbh = e.thumbhide>=pw ? 0 : e.thumbh;
						for (var i in e.rl) nl[i] = e.rl[i]<window.RSIW ? 0 : e.rl[i];
						sl = nl[0];									
						for (var i in nl) if (sl>nl[i] && nl[i]>0) { sl = nl[i]; ix=i;}
						var m = pw>(e.gw[ix]+e.tabw+e.thumbw) ? 1 : (pw-(e.tabw+e.thumbw)) / (e.gw[ix]);
						newh =  (e.gh[ix] * m) + (e.tabh + e.thumbh);
					}				
					var el = document.getElementById(e.c);
					if (el!==null && el) el.style.height = newh+"px";
					el = document.getElementById(e.c+"_wrapper");
					if (el!==null && el) el.style.height = newh+"px";
				} catch(e){
					console.log("Failure at Presize of Slider:" + e)
				}
			//}
		  };
	 */
	public static function js_set_start_size(){
		global $SR_GLOBALS;

		if(isset($SR_GLOBALS['js_startsize_init']) && $SR_GLOBALS['js_startsize_init'] === true) return false;
		
		$script = '<script>';
		$script .= 'function setREVStartSize(e){
			//window.requestAnimationFrame(function() {
				window.RSIW = window.RSIW===undefined ? window.innerWidth : window.RSIW;
				window.RSIH = window.RSIH===undefined ? window.innerHeight : window.RSIH;
				try {
					var pw = document.getElementById(e.c).parentNode.offsetWidth,
						newh;
					pw = pw===0 || isNaN(pw) || (e.l=="fullwidth" || e.layout=="fullwidth") ? window.RSIW : pw;
					e.tabw = e.tabw===undefined ? 0 : parseInt(e.tabw);
					e.thumbw = e.thumbw===undefined ? 0 : parseInt(e.thumbw);
					e.tabh = e.tabh===undefined ? 0 : parseInt(e.tabh);
					e.thumbh = e.thumbh===undefined ? 0 : parseInt(e.thumbh);
					e.tabhide = e.tabhide===undefined ? 0 : parseInt(e.tabhide);
					e.thumbhide = e.thumbhide===undefined ? 0 : parseInt(e.thumbhide);
					e.mh = e.mh===undefined || e.mh=="" || e.mh==="auto" ? 0 : parseInt(e.mh,0);
					if(e.layout==="fullscreen" || e.l==="fullscreen")
						newh = Math.max(e.mh,window.RSIH);
					else{
						e.gw = Array.isArray(e.gw) ? e.gw : [e.gw];
						for (var i in e.rl) if (e.gw[i]===undefined || e.gw[i]===0) e.gw[i] = e.gw[i-1];
						e.gh = e.el===undefined || e.el==="" || (Array.isArray(e.el) && e.el.length==0)? e.gh : e.el;
						e.gh = Array.isArray(e.gh) ? e.gh : [e.gh];
						for (var i in e.rl) if (e.gh[i]===undefined || e.gh[i]===0) e.gh[i] = e.gh[i-1];
											
						var nl = new Array(e.rl.length),
							ix = 0,
							sl;
						e.tabw = e.tabhide>=pw ? 0 : e.tabw;
						e.thumbw = e.thumbhide>=pw ? 0 : e.thumbw;
						e.tabh = e.tabhide>=pw ? 0 : e.tabh;
						e.thumbh = e.thumbhide>=pw ? 0 : e.thumbh;
						for (var i in e.rl) nl[i] = e.rl[i]<window.RSIW ? 0 : e.rl[i];
						sl = nl[0];
						for (var i in nl) if (sl>nl[i] && nl[i]>0) { sl = nl[i]; ix=i;}
						var m = pw>(e.gw[ix]+e.tabw+e.thumbw) ? 1 : (pw-(e.tabw+e.thumbw)) / (e.gw[ix]);
						newh =  (e.gh[ix] * m) + (e.tabh + e.thumbh);
					}
					var el = document.getElementById(e.c);
					if (el!==null && el) el.style.height = newh+"px";
					el = document.getElementById(e.c+"_wrapper");
					if (el!==null && el) {
						el.style.height = newh+"px";
						el.style.display = "block";
					}
				} catch(e){
					console.log("Failure at Presize of Slider:" + e)
				}
			//});
		  };';
		$script .= '</script>' . "\n";
		echo apply_filters('revslider_add_setREVStartSize', $script);
		
		$SR_GLOBALS['js_startsize_init'] = true;
	}

	public function set_html_id_v7($html_id, $check_for_duplication){
		global $SR_GLOBALS;

		if($check_for_duplication){ //check if it already exists, if yes change it and add attribute for console output
			$ids = $this->get_val($SR_GLOBALS, ['collections', 'ids']);
			if(in_array($html_id, $ids, true)){
				$i = 0;
				do{$i++; }while(in_array($html_id.'_'.$i, $ids, true));
				$html_id .= '_'.$i;
			}
		}
		if(!in_array($html_id, $SR_GLOBALS['collections']['ids'])) $SR_GLOBALS['collections']['ids'][] = $html_id;

		return $html_id;
	}

	/**
	 * compress the css
	 **/
	public function compress_css($buffer){
		/* remove comments */
		$buffer = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!", '', $buffer) ;
		/* remove tabs, spaces, newlines, etc. */
		$arr = ["\r\n", "\r", "\n", "\t", '  ', '    ', '    '];
		$rep = ['', '', '', '', ' ', ' ', ' '];
		$buffer = str_replace($arr, $rep, $buffer);
		/* remove whitespaces around {}:, */
		$buffer = preg_replace("/\s*([\{\}:,])\s*/", "$1", $buffer);
		/* remove last ; */
		$buffer = str_replace(';}', '}', $buffer);
		
		return $buffer;
	}

	/**
	 * parse css stylesheet to an array
	 **/
	public function css_to_array($css){
		
		while(strpos($css, '/*') !== false){
			if(strpos($css, '*/') === false) return false;
			$start	= strpos($css, '/*');
			$end	= strpos($css, '*/') + 2;
			$css	= str_replace(substr($css, $start, $end - $start), '', $css);
		}
		
		preg_match_all('/(?ims)([a-z0-9\,\s\.\:#_\-@]+)\{([^\}]*)\}/', $css, $arr);

		$result = [];
		foreach($arr[0] as $i => $x){
			$selector = trim($arr[1][$i]);
			if(strpos($selector, '{') !== false || strpos($selector, '}') !== false) return false;
			$rules = explode(';', trim($arr[2][$i]));
			$result[$selector] = [];
			foreach($rules as $strRule){
				if(!empty($strRule)){
					$rule = explode(':', $strRule);
					
					//put back everything but not $rule[0];
					$key = trim($rule[0]);
					unset($rule[0]);
					$values = implode(':', $rule);
					
					$result[$selector][trim($key)] = trim(str_replace("'", '"', $values));
				}
			}
		}
		
		return $result;
	}

	
	/**
	 * Get nested array value by path
	 */
	/*public function array_get_path(&$array, $path, $default = false) {
		$ref = &$array;
		foreach ($path as $key) {
			if (!is_array($ref) || !array_key_exists($key, $ref)) {
				return $default;
			}
			$ref = &$ref[$key];
		}
		return $ref;
	}

	/**
	 * Set nested array value by path
	 */
	/*public function array_set_path(&$array, $path, $value) {
		$ref = &$array;
		foreach ($path as $key) {
			if (!isset($ref[$key]) || !is_array($ref[$key])) {
				$ref[$key] = [];
			}
			$ref = &$ref[$key];
		}
		$ref = $value;
	}*/


	public function array_get_path($root, array $path, $default = false) {
		if(empty($path)) return $default;

		$key = array_shift($path);

		if($key === '__ARRAY__'){
			if(!is_array($root)) return $default;
			$out = [];
			foreach ($root as $item) {
				$out[] = empty($path) ? $item : $this->array_get_path($item, $path, $default);
			}
			return $out;
		}

		if(!is_array($root) || !array_key_exists($key, $root)) return $default;
		if(empty($path)) return $root[$key];

		return $this->array_get_path($root[$key], $path, $default);
	}

	public function array_set_path(&$root, array $path, $value) {
		if(empty($path)) return;

		$key = array_shift($path);

		if($key === '__ARRAY__'){
			if(!is_array($root)) return;

			$isListValue = is_array($value);
			$i = 0;
			foreach ($root as $idx => &$item) {
				$valForThis = $isListValue && array_key_exists($i, $value) ? $value[$i] : $value;

				if (!empty($path)) {
					$this->array_set_path($item, $path, $valForThis);
				} else {
					// At ['...','__ARRAY__'] the element itself is the leaf; set it directly.
					$item = $valForThis;
				}
				$i++;
			}
			unset($item);
			return;
		}

		// Build shells as needed
		if(!isset($root[$key]) || !is_array($root[$key])){
			if (!isset($root[$key])) $root[$key] = [];
			elseif (!is_array($root[$key])) $root[$key] = [];
		}

		if(empty($path)){
			$root[$key] = $value;
			return;
		}

		$this->array_set_path($root[$key], $path, $value);
	}
	
	/**
	 * get options depending on given keys as array
	 * $db -> get directly from database, every time its true
	 */
	public function get_options($keys = [], $default = '', $db = false, $field = 'sr-options'){
		global $SR_GLOBALS;
		$options_field = ($field === 'sr-options') ? 'options' : $field;

		$options = ($db === false && !empty($SR_GLOBALS[$options_field])) ? $SR_GLOBALS[$options_field] : get_option($field, '');
		
		if(!is_array($options)) $options = json_decode($options, true);

		if($db === false && empty($SR_GLOBALS[$options_field])) $SR_GLOBALS[$options_field] = $options;

		return (empty($keys)) ? $options : $this->get_val($options, $keys, $default);
	}

	/**
	 * save all options into json format
	 */
	public function update_all_options($options, $field = 'sr-options'){
		global $SR_GLOBALS;

		update_option($field, $options);

		$options_field = ($field === 'sr-options') ? 'options' : $field;

		$SR_GLOBALS[$options_field] = $options;

		do_action('revslider_update_all_options', $options, $field);
	}

	/**
	 * update internal options by given keys of array
	 * revslider-valid and revslider-code will be double saved for the usage in older addons
	 * so that they can still be updated if they are on an older version pre v7
	 */
	public function update_option($keys, $value, $field = 'sr-options'){
		$extra_save = [
			'revslider-valid' => ['system', 'valid'],
			'revslider-code' => ['system', 'license'],
		];
		$options = $this->get_options([], '', true, $field);
		if(empty($options)) $options = [];

		$this->set_val($options, $keys, $value);
		$this->update_all_options($options, $field);

		foreach($extra_save as $handle => $v){
			if(array_diff($v, $keys)) continue;
			update_option($handle, $value);
			break;
		}
	}

	/**
	 * deletes the option in our internal options by given keys of array
	 */
	public function delete_option($keys, $field = 'sr-options'){
		$options	= $this->get_options([], '', true, $field);
		$_options	= &$options;
		$keys		= (array)$keys;
		$last_key	= array_pop($keys);

		foreach($keys ?? [] as $key){
			if(!isset($_options[$key])) return false;
			$_options = &$_options[$key];
		}

		if(!isset($_options[$last_key])) return false;
		
		unset($_options[$last_key]);
		
		$this->update_all_options($options, $field);

		return true;
	}

	
	/**
	 * Create a temporary fake page/post
	 * @since: 6.0
	 **/
	public function create_fake_post($content, $title = 'Slider Revolution'){
		$fake_id				= 999999999;
		$post					= new stdClass();
		$post->ID				= $fake_id;
		$post->post_author		= get_current_user_id();
		$post->post_date		= current_time('mysql');
		$post->post_date_gmt	= current_time('mysql', 1);
		$post->post_title		= $title;
		$post->post_content		= $content;
		$post->post_status		= 'publish';
		$post->comment_status	= 'closed';
		$post->ping_status		= 'closed';
		$post->post_name		= 'rs-fake-page-' . rand(1, 99999);
		$post->post_type		= 'page';
		$post->filter			= 'raw';

		$wp_post = new WP_Post($post);

		wp_cache_add($wp_post->ID, $wp_post, 'posts');

		return $wp_post;
	}


	/**
	 * global function to add revslider textdomain
	 **/
	public function _t($text) {
		return __($text, 'revslider');
	}

}
