<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/
 * @copyright 2024 ThemePunch
 */
 
if(!defined('ABSPATH')) exit();

class RevSliderFonts extends RevSliderFunctions {

	
	/**
	 * Load Used Google Fonts
	 * add google fonts of all sliders found on the page
	 * @since: 6.0
	 */
	public function load_google_fonts(){
		$fonts	= $this->print_clean_font_import_v7();
		
		if(empty($fonts)) return;

		echo "\n".$fonts."\n";

		global $SR_GLOBALS;
		if(empty($SR_GLOBALS['fonts']['loaded']) && empty($SR_GLOBALS['fonts']['custom'])) return;
	
		$domFonts = [];
		echo '<script>'."\n";
		$branches = ['loaded', 'custom'];
		foreach($branches as $branch){
			foreach($SR_GLOBALS['fonts'][$branch] ?? [] as $handle => $values){
				$handle = preg_replace('/[^-0-9a-zA-Z+]/', '', str_replace(' ', '+', $handle));
				if(isset($values['url'])){
					echo "_tpt.R.fonts.customFonts['". $handle ."'] = ". json_encode($values) .";"."\n";
				}else{
					$domFonts[$handle] = [
						'normal'	=> $this->get_val($values, ['variants', 'normal'], []),
						'italic'	=> $this->get_val($values, ['variants', 'italic'], [])
					];
				}
			}
		}

		if(!empty($domFonts)){
			echo "_tpt.R.fonts.domFonts = ". json_encode($domFonts) .";"."\n";
		}
		echo '</script>'."\n";
	}

	/**
	 * print html font import
	 */
	public function print_clean_font_import_v7(){
		global $SR_GLOBALS;

		$gs		= $this->get_global_settings();
		$fdl	= $this->get_val($gs, ['fonts', 'download'], 'off');
		$ret	= '';
		$tcf	= '';
		$tcf2	= '';
		$font_first = true;
		$fonts	= [];

		if(!empty($SR_GLOBALS['fonts']['queue'])){
			foreach($SR_GLOBALS['fonts']['queue'] as $f_n => $f_s){
				if(!isset($f_s['url'])) continue; //if url is not set, continue
				
				if(isset($f_s['load']) && $f_s['load'] === true){ //only load if we are true
					$ret .=  RS_T3.'<link href="' . esc_html($f_s['url']) . '" rel="stylesheet" property="stylesheet" media="all" type="text/css" />'."\n";
				}
				if(!isset($SR_GLOBALS['fonts']['loaded'][$f_n])) $SR_GLOBALS['fonts']['loaded'][$f_n] = [];
				$SR_GLOBALS['fonts']['loaded'][$f_n] = ['url' => $this->get_val($f_s, 'url')];
			}
		}

		if($fdl === 'disable') return $ret;

		if(!empty($SR_GLOBALS['fonts']['queue'])){
			foreach($SR_GLOBALS['fonts']['queue'] as $f_n => $f_s){
				if(!is_bool($f_s)) continue;
				$loaded = false;
				switch($f_n){
					case 'Materialicons':
						$ret .= RS_T3.'<link href="' . RS_PLUGIN_URL_CLEAN . 'public/css/fonts/material/material-icons.css" rel="stylesheet" property="stylesheet" media="all" type="text/css" />'."\n";
						$loaded = ['url' => RS_PLUGIN_URL_CLEAN . 'public/css/fonts/material/material-icons.css', 'icon' => true, 'family' => 'Materialicons'];
					break;
					case 'FontAwesome':
						$ret .= RS_T3.'<link href="' . RS_PLUGIN_URL_CLEAN . 'public/css/fonts/font-awesome/css/font-awesome.css" rel="stylesheet" property="stylesheet" media="all" type="text/css" />'."\n";
						$loaded = ['url' => RS_PLUGIN_URL_CLEAN . 'public/css/fonts/font-awesome/css/font-awesome.css', 'icon' => true, 'family' => 'FontAwesome'];
					break;
					case 'PeIcon':
						$ret .= RS_T3.'<link href="' . RS_PLUGIN_URL_CLEAN . 'public/css/fonts/pe-icon-7-stroke/css/pe-icon-7-stroke.css" rel="stylesheet" property="stylesheet" media="all" type="text/css" />'."\n";
						$loaded = ['url' => RS_PLUGIN_URL_CLEAN . 'public/css/fonts/pe-icon-7-stroke/css/pe-icon-7-stroke.css', 'icon' => true, 'family' => 'Pe-icon-7-stroke'];
					break;
					case 'RevIcon':
						$ret .= RS_T3.'<link href="' . RS_PLUGIN_URL_CLEAN . 'public/css/fonts/revicons/css/revicons.css" rel="stylesheet" property="stylesheet" media="all" type="text/css" />'."\n";
						$loaded = ['url' => RS_PLUGIN_URL_CLEAN . 'public/css/fonts/revicons/css/revicons.css', 'icon' => true, 'family' => 'revicons'];
					break;
				}
				if($loaded === false) continue;

				if(!isset($SR_GLOBALS['fonts']['loaded'][$f_n])) $SR_GLOBALS['fonts']['loaded'][$f_n] = $loaded;
			}
		}

		if(!empty($SR_GLOBALS['fonts']['queue'])){
			$this->remove_wordpress_global_fonts();

			$font_types = ['normal', 'italic'];
			
			foreach($SR_GLOBALS['fonts']['queue'] as $f_n => $f_s){
				if(empty($f_n)) continue;
				if(isset($f_s['url']) && !empty($f_s['url'])) continue; //ignore custom
			
				$_variants	= $this->get_val($f_s, 'variants', ['normal' => [], 'italic' => []]);
				$_subsets	= $this->get_val($f_s, 'subsets', []);
				if(!empty($_variants['normal']) || !empty($_variants['italic']) || !empty($_subsets)){
					if(!isset($SR_GLOBALS['fonts']['loaded'][$f_n])) $SR_GLOBALS['fonts']['loaded'][$f_n] = [];
					if(!isset($SR_GLOBALS['fonts']['loaded'][$f_n]['variants'])) $SR_GLOBALS['fonts']['loaded'][$f_n]['variants'] = [];
					if(!isset($SR_GLOBALS['fonts']['loaded'][$f_n]['variants']['normal'])) $SR_GLOBALS['fonts']['loaded'][$f_n]['variants']['normal'] = [];
					if(!isset($SR_GLOBALS['fonts']['loaded'][$f_n]['variants']['italic'])) $SR_GLOBALS['fonts']['loaded'][$f_n]['variants']['italic'] = [];
					if(!isset($SR_GLOBALS['fonts']['loaded'][$f_n]['subsets'])) $SR_GLOBALS['fonts']['loaded'][$f_n]['subsets'] = [];
					
					if(strpos($f_n, 'href=') === false){
						$google_slug = str_replace(["'", '"', '+'], ['', '', ' '], $f_n);
						if(!isset($googlefonts)) include(RS_PLUGIN_PATH . 'includes/googlefonts.php');
						if(!isset($googlefonts[$google_slug])) continue; //check if font found in our own google fonts list

						$fonts[$f_n] = ['font' => $f_n, 'normal' => $this->get_val($_variants, 'normal', []), 'italic' => $this->get_val($_variants, 'italic', [])]; //$t_tcf; //we do not want to add the subsets
						$t_tcf = '';

						if($font_first == false) $t_tcf .= '&family=';
						$t_tcf .= preg_replace('/[^-0-9a-zA-Z+]/', '', str_replace(' ', '+', $f_n)).':';

						if(!empty($_variants['normal']) || !empty($_variants['italic'])){
							$mgfirst = true;
							$italic = false;
							if(!empty($f_s['variants']['italic'])){
								$t_tcf .= 'ital,';
								$italic = true;
							}
							$t_tcf .= 'wght@';

							$weights = [];
							foreach($font_types as $ft){
								if(!isset($f_s['variants'][$ft])) continue;
								$weights[$ft] = [];
								foreach($f_s['variants'][$ft] as $variant){
									if(in_array($variant, $SR_GLOBALS['fonts']['loaded'][$f_n]['variants'][$ft], true)) continue;

									$cft = ($ft === 'italic') ? $variant.'italic' : $variant;
									if(!in_array($cft , $googlefonts[$google_slug]['variants'])){
										if($ft === 'italic'){
											//check if it exists in normal weights and add if not already added
											if(in_array($variant, $googlefonts[$google_slug]['variants'])){
												if(!isset($weights['normal'])) $weights['normal'] = [];
												if(!in_array($variant, $weights['normal'])) $weights['normal'][] = $variant;
											}
										}
										continue;
									}

									$SR_GLOBALS['fonts']['loaded'][$f_n]['variants'][$ft][] = $variant;
									//if($variant === 'italic') continue;
									
									$weights[$ft][] = $variant;
								}
							}
							if(empty($weights)) continue;
							
							$i = 0;
							foreach($weights ?? [] as $weight_type => $weight_values){
								if(empty($weight_values)) continue;

								asort($weight_values); //sort as we need to start from low to high

								foreach($weight_values as $weight){
									if(!$mgfirst) $t_tcf .= ';';

									$t_tcf .= ($italic === true) ? $i.','.$weight : $weight;
									$mgfirst = false;
								}
								$i++;
							}
							
							//we did not add any variants, so dont add the font
							if($mgfirst === true) continue;

							$tcf .= $t_tcf;
						}
					}else{
						if($fdl === 'preload'){
							//from URL to normal italic formats fetching
							$font_full = $this->get_font_weights_by_url($f_n);
							if(!empty($font_full['font'])) $fonts[$font_full['font']] = $font_full;
						}else{
							$tcf2 .= html_entity_decode(stripslashes($f_n));
						}
					}
					$font_first = false;
				}
			}
		}

		if($fdl === 'preload'){
			$ret .= $this->preload_fonts($fonts);
		}else{
			$url = $this->modify_fonts_url('https://fonts.googleapis.com/css2?family=');
			$ret .= ($tcf !== '') ?  RS_T3.'<link href="'.$url.$tcf.'&display=swap" rel="stylesheet" property="stylesheet" media="all" type="text/css" >'."\n" : '';
			$ret .= ($tcf2 !== '') ?  RS_T3.html_entity_decode(stripslashes($tcf2)) : '';
		}

		return apply_filters('revslider_printCleanFontImport', $ret);
	}

	/**
	 * create a list of normal and italic weights to be useable by preload_fonts()
	 * 
	 * @param string $font_url - the font url to be parsed
	 * @return array - array with font name, normal weights, and italic weights
	 */
	public function get_font_weights_by_url($font_url){
		$font_full = ['font' => '', 'normal' => [], 'italic' => []];

		if(strpos($font_url, ':') === false) return $font_full;
		$f_raw = explode(':', $font_url);

		if(empty($f_raw) || !is_array($f_raw) || !isset($f_raw[1])) return $font_full;

		if(strpos($f_raw[1], ',') !== false && strpos($f_raw[1], ';') === false || intval($f_raw[1]) > 0){
			$f_raw[1]	= str_replace(['%2C', 'wght', '@0,', ';0,', '@', '&family='], [',', '', '', ',', '', ''], $f_raw[1]);
			$font_full['font'] = $f_raw[0];
			$weights = explode(',', $f_raw[1]);
			foreach($weights ?? [] as $wk => $weight){
				$weight = strtolower($weight);
				if(strpos($weight, 'ital') !== false){
					$weight = str_replace(['italic', 'ital'], '', $weight);
					if(intval($weight) === 0) $weight = 400;
					$font_full['italic'][$weight] = $weight;
				}else{
					$font_full['normal'][$weight] = $weight;
				}
			}
		}else{ //no /css2 process here as we seem to be /css
			$f_raw[1]	= str_replace(['%2C', 'wght', '@0,', ';0,', '@', ';', '&family='], [',', '', '', ',', '', ',', ''], $f_raw[1]);
			$weights	= explode(',', $f_raw[1]);
			foreach($weights ?? [] as $wk => $weight){
				if($weight === 'ital' || $weight === 'italic'){
					$wk = intval($wk);
					if($wk < 100) continue;

					$font_full['italic'][$wk] = $wk;
				}else{
					$weight = intval($weight);
					if($weight < 100) continue;

					$font_full['normal'][$weight] = $weight;
				}
			}
		}

		if(empty($font_full['normal']) && empty($font_full['italic'])) $font_full['normal'] = ['400'];
		if(!empty($font_full['normal'])) $font_full['normal'] = array_unique($font_full['normal']);
		if(!empty($font_full['italic'])) $font_full['italic'] = array_unique($font_full['italic']);

		return $font_full;
	}
	
	/**
	 * preloading fonts and return style for it
	 **/
	public function preload_fonts($fonts, $style = true, $all = false){
		$ret = '';
		
		if(empty($fonts)) return $ret;
	
		if(!function_exists('download_url')) require_once ABSPATH . 'wp-admin/includes/file.php';
		$allowed_mime_types = ['ttf'  => 'font/ttf', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'otf'  => 'font/otf'];
		$upload_dir	= wp_upload_dir();
		$base_dir	= $upload_dir['basedir'];
		$base_url	= $this->remove_http($upload_dir['baseurl']);
		$tp_google_ts = $this->get_options(['timestamps', 'google-fonts'], 0);
		$types		= [
			//--- original
			'ttf'	=> ['user-agent' => ''],
			'woff'	=> ['accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8', 'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.10240'],
			'woff2'	=> ['accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8', 'user-agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:73.0) Gecko/20100101 Firefox/73.0'],
			//--- original end
			/*--- alternative
			//'ttf'	=> ['user-agent' => 'Mozilla/5.0 (Unknown; Linux x86_64) AppleWebKit/538.1 (KHTML, like Gecko) Safari/538.1 Daum/4.1'],
			//'woff'	=> ['user-agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0'],
			//'woff2'	=> ['user-agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0'],
			//'eot'	=> ['user-agent' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)'],
			//'svg'	=> ['user-agent' => 'Mozilla/4.0 (iPad; CPU OS 4_0_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/4.1 Mobile/9A405 Safari/7534.48.3'],
			//--- alternative 2 end */
		];
		$fonts_css	= $this->get_options(['fonts', 'fonts'], []);
		if(!is_array($fonts_css)) $fonts_css = [];
		$load = 'ttf';

		if($all === false){
			$_browser	= $this->get_browser();
			$version	= $this->get_val($_browser, 'version', '0');
			$browser	= $this->get_val($_browser, 'name', '');
			//Chrome 6+ , Firefox 3.6+ IE9+, Safari 5.1+  -> WOFF
			//Chrome 26+, Operae23+, Firefox 39+ -> Woff2
			switch(strtolower($browser)){
				case 'mozilla firefox':
					if(version_compare($version, '3.6', '>=')) $load = 'woff';
					if(version_compare($version, '39', '>=')) $load = 'woff2';
				break;
				case 'edge':
					$load = 'woff2';
				break;
				case 'google chrome':
					if(version_compare($version, '6', '>=')) $load = 'woff';
					if(version_compare($version, '26', '>=')) $load = 'woff2';
				break;
				case 'apple safari':
					if(version_compare($version, '5.1', '>=')) $load = 'woff';
				break;
				case 'opera':
					if(version_compare($version, '23', '>=')) $load = 'woff';
				break;
				case 'internet explorer':
					if(version_compare($version, '9', '>=')) $load = 'woff';
				break;
			}
		}
		if(!isset($googlefonts)) include(RS_PLUGIN_PATH . 'includes/googlefonts.php');

		foreach($fonts ?? [] as $key => $_font){
			//check if we downloaded the font already
			$font		= (isset($_font['font'])) ? $_font['font'] : $key;
			$font_name	= preg_replace('/[^-a-z0-9 ]+/i', '', $font);
			$font_name	= strtolower(str_replace(' ', '-', esc_attr($font_name)));
			$font		= preg_replace('/[^-a-zA-Z0-9+ ]+/i', '', $font);
			$gfont		= str_replace('+', ' ', $font);
			$font_loaded = [];
			if(!isset($googlefonts[$gfont])) continue; //check if font found in our own google fonts list

			$collection = ['normal' => array_unique($this->get_val($_font, 'normal', [])), 'italic' => array_unique($this->get_val($_font, 'italic', []))];

			if(empty($collection['normal']) && !empty($collection['italic'])) $collection['normal'][] = 400;

			$mgfirst = true;
			$italic	 = false;
			$font	.= ':';
			if(!empty($collection['italic'])){
				$font .= 'ital,';
				$italic = true;
			}
			$font .= 'wght@';

			$i = 0;
			$cycles = ['normal', 'italic'];
			
			foreach($cycles ?? [] as $cycle){
				$weight_values = $collection[$cycle];
				if(empty($weight_values)) continue;

				asort($weight_values); //sort as we need to start from low to high

				foreach($weight_values as $weight){
					if(!$mgfirst) $font .= ';';

					$font .= ($italic === true) ? $i.','.$weight : $weight;
					$mgfirst = false;
				}
				$i++;
			}

			foreach($types as $ftype => $options){
				if($load !== $ftype && $all === false) continue;
				$f_download = false;
				foreach($collection as $font_style => $weight){
					if(empty($weight)) continue;
					
					foreach($weight as $w){
						$_css = $this->get_val($fonts_css, [$font_name, $ftype, $w, $font_style], false);

						if(!empty($_css) && is_array($_css)){
							foreach($_css as $uc => $fw){
								if(empty($fw) || !is_array($fw)) continue;
								
								foreach($fw as $_fw => $font_css){
									$start = strpos($font_css, '###BASE###');
									if($start === false) continue;
									$end = strpos($font_css, ')', $start + 10);
									$file_raw = substr($font_css, $start + 10, $end - ($start + 10));

									if(!is_file($base_dir.'/themepunch/gfonts/'. $file_raw) || filemtime($base_dir.'/themepunch/gfonts/'. $file_raw) < $tp_google_ts){
										$f_download = true;
										break;
									}
								}
							}
						}else{
							$f_download = true;
						}

						if($f_download) break;
					}
				}

				if($f_download){
					if(!is_dir($base_dir.'/themepunch/')) mkdir($base_dir.'/themepunch/');
					if(!is_dir($base_dir.'/themepunch/gfonts/')) mkdir($base_dir.'/themepunch/gfonts/');
					if(!is_dir($base_dir.'/themepunch/gfonts/'.$font_name)) mkdir($base_dir.'/themepunch/gfonts/'.$font_name);

					$content = wp_safe_remote_get('https://fonts.googleapis.com/css2?family='.$font, $options);
					$body	 = $this->get_val($content, 'body', '');
					$body	 = explode('}', $body);

					if(empty($body)) continue;
				
					foreach($body ?? [] as $b){
						if(preg_match("/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/", $b, $found_fonts)){
							$found_font	= rtrim($found_fonts[0], ')');
							$path     = parse_url($found_font, PHP_URL_PATH);
							$filename = $path ? basename($path) : 'font';
							if(!preg_match('/\.[a-z0-9]{2,5}$/i', $filename)) $filename = md5($found_font) . '.' . $load;

							$file_type	= wp_check_filetype($filename, $allowed_mime_types);
							if($this->get_val($file_type, 'ext', false) === false || $this->get_val($file_type, 'type', false) === false) continue;
							$found_fw	= (preg_match("/(?<=font-weight:)(.*)(?=;)/", $b, $found_fw)) ? trim($found_fw[0]) : '400';
							$found_fs	= (preg_match("/(?<=font-style:)(.*)(?=;)/", $b, $found_fs)) ? trim($found_fs[0]) : 'normal';
							$found_ur	= (preg_match("/(?<=\/\*)(.*)(?=\*\/)/", $b, $found_ur)) ? trim($found_ur[0]) : '';

							$found_ur	= (empty($found_ur)) ? 'all' : $found_ur;
							$found_fs	= ($found_fs !== 'normal') ? 'italic' : $found_fs;
							$found_fw	= (empty($found_fw)) ? '400' : $found_fw;
							$file		= $base_dir.'/themepunch/gfonts/'. $font_name . '/' . $filename;
							$_file		= '###BASE###'. $font_name . '/' . $filename;
							if(!in_array($filename, $font_loaded)){
								$tmp = download_url($found_font, 4);
								if(!is_wp_error($tmp)){
									if(!is_dir(dirname($file))) @mkdir(dirname($file));
									copy($tmp, $file);
									@unlink($tmp);
								}
								
								$font_loaded[] = $filename;
							}

							if(strpos($b, 'font-display') === false) $b .= '  font-display: swap;'."\n";
							
							if(!isset($fonts_css[$font_name]))									$fonts_css[$font_name] = [];
							if(!isset($fonts_css[$font_name][$ftype]))							$fonts_css[$font_name][$ftype] = [];
							if(!isset($fonts_css[$font_name][$ftype][$found_fw]))				$fonts_css[$font_name][$ftype][$found_fw] = [];
							if(!isset($fonts_css[$font_name][$ftype][$found_fw][$found_fs]))	$fonts_css[$font_name][$ftype][$found_fw][$found_fs] = [];
							$fonts_css[$font_name][$ftype][$found_fw][$found_fs][$found_ur]	= str_replace($found_font, $_file, $b . '}');
						}
					}
				}

				foreach($collection as $font_style => $weights){
					if(empty($weights) || !is_array($weights)) continue;
				
					if($style === true)	$ret .= '<style class="sr7-inline-css">';
					$format = ($ftype !== 'ttf') ? $ftype : 'truetype';
					foreach($weights ?? [] as $weight){
						$_css = $this->get_val($fonts_css, [$font_name, $ftype, $weight, $font_style], false);

						if(!empty($_css) && is_array($_css)){
							foreach($_css as $fw => $font_css){
								if(empty($font_css)) continue;
								
								$ret .= str_replace('###BASE###', $base_url.'/themepunch/gfonts/', $font_css);
							}
						}else{
							if(!isset($fonts_css[$font_name]))									$fonts_css[$font_name] = [];
							if(!isset($fonts_css[$font_name][$ftype]))							$fonts_css[$font_name][$ftype] = [];
							if(!isset($fonts_css[$font_name][$ftype][$weight]))					$fonts_css[$font_name][$ftype][$weight] = [];
							if(!isset($fonts_css[$font_name][$ftype][$weight][$font_style]))	$fonts_css[$font_name][$ftype][$weight][$font_style] = [];
							$fonts_css[$font_name][$ftype][$weight][$font_style]['all']	= '/* '.$weight.' '.$font_style.' does not exist  */';
						}
					}
					if($style === true)	$ret .= '</style>';
				}
			}
		}

		$this->update_option(['fonts', 'fonts'], $fonts_css);

		return $ret;
	}

	
	/**
	 * get a collection of all used fonts, either in a grid or from the whole plugin
	 **/
	public function collect_used_fonts($save = true, $fetch_all = true, $page = 1){
		$used_fonts	= $this->get_options(['fonts', 'collected'], []);
		foreach($used_fonts ?? [] as $handle => $font){
			if(!isset($used_fonts[$handle]['subset'])) $used_fonts[$handle] = ['normal' => [], 'italic' => [], 'subset' => []];
		}
		$more		= false;
		$sr			= new RevSliderSlider();
		$sl			= new RevSliderSlide();

		//get all slider, init them and get subsets and get_used_fonts
		$page = intval($page);
		if($page <= 0) $page = 1;

		$sliders = $sr->get_sliders(false, $page);
		foreach($sliders ?? [] as $slider){
			$gf	= $slider->get_param('fonts', []);
			foreach($gf ?? [] as $handle => $data){
				if(!isset($used_fonts[$handle]) || !isset($used_fonts[$handle]['subset'])) $used_fonts[$handle] = ['normal' => [], 'italic' => [], 'subset' => []]; //we are on the old format if subset does not exist

				foreach($used_fonts[$handle] as $k => $v){
					if(is_string($v)) continue;

					$d = $this->get_val($data, $k, []);
					foreach($d ?? [] as $f => $d){
						if(in_array($f, $used_fonts[$handle][$k])) continue;

						$used_fonts[$handle][$k][] = $f;
					}
				}

			}
		}
		if(count($sliders) >= 50) $more = true;

		foreach($used_fonts ?? [] as $font => $data){
			if(isset($data['font'])) continue;
			$used_fonts[$font]['font'] = $font;
		}

		if($fetch_all === true){
			/*if(class_exists('ThemePunch_Fonts') && method_exists('ThemePunch_Fonts', 'collect_used_fonts')){
				$esg_fonts = new ThemePunch_Fonts();
				$return = $esg_fonts->collect_used_fonts(false, false, $page);
				$fonts = $this->get_val($return, 'fonts', []);
				$_more = $this->get_val($return, 'more', false);
				if($_more === true) $more = true;
				//merge esg and revslider

				foreach($fonts ?? [] as $handle => $urls){
					if(empty($urls) || !is_array($urls)) continue;
					if(!isset($used_fonts[$handle]) ) $used_fonts[$handle] = [];
					if(!in_array($handle, $used_fonts[$handle])) {
						foreach($urls ?? [] as $url){
							if(!in_array($url, $used_fonts[$handle])) $used_fonts[$handle][] = $url;
						}
					}
				}
			}*/
		}

		$used_fonts = apply_filters('punchfonts_collect_fonts_v7', $used_fonts);
		if($save === true) $this->update_option(['fonts', 'collected'], $used_fonts);

		return ['fonts' => $used_fonts, 'more' => $more];
	}
	
	
	/**
	 * removes fonts from queue, that are already loaded by WordPress
	 */
	public function remove_wordpress_global_fonts(){
		global $SR_GLOBALS;

		if(!class_exists('WP_Font_Face_Resolver')) return;
		if(!method_exists('WP_Font_Face_Resolver', 'get_fonts_from_theme_json' )) return;
		if(!method_exists('WP_Font_Face_Resolver', 'get_fonts_from_style_variations' )) return;
		
		$wp_font_list = [];
		$wp_fonts = WP_Font_Face_Resolver::get_fonts_from_theme_json();
		if(empty($wp_fonts)) $wp_fonts = WP_Font_Face_Resolver::get_fonts_from_style_variations();
		foreach($wp_fonts ?? [] as $wp_font){
			foreach($wp_font ?? [] as $_font){
				$wpff = $this->get_val($_font, 'font-family');
				$wpfs = $this->get_val($_font, 'font-style');
				$wpfw = $this->get_val($_font, 'font-weight');
				if(empty($wpff)) continue;
				if(empty($wpfs)) continue;
				if(empty($wpfw)) continue;
				if(!isset($wp_font_list[$wpff])) $wp_font_list[$wpff] = [];
				if(!isset($wp_font_list[$wpff]['variants'])) $wp_font_list[$wpff]['variants'] = [];
				if(strpos($wpfw, ' ') !== false){
					$wpfw = explode(' ', $wpfw);
					$wp_font_list[$wpff]['variants'][$wpfs] = [
						'from'	=> $this->get_val($wpfw, 0),
						'to'	=> $this->get_val($wpfw, 1)
					];
				}else{
					$wp_font_list[$wpff]['variants'][$wpfs] = $wpfw;
				}
			}
		}

		if(!empty($wp_font_list)){
			foreach($SR_GLOBALS['fonts']['queue'] as $f_n => $f_s){		
				if(empty($f_n)) continue;
				if(isset($f_s['url']) && !empty($f_s['url'])) continue; //ignore custom
				if(!isset($wp_font_list[$f_n])) continue;
				$_variants	= $this->get_val($f_s, 'variants', ['normal' => [], 'italic' => []]);
				foreach($_variants ?? [] as $f_w => $f_v){
					$from	= (isset($wp_font_list[$f_n]['variants'][$f_w]) && is_array($wp_font_list[$f_n]['variants'][$f_w])) ? intval($wp_font_list[$f_n]['variants'][$f_w]['from']) : false;
					$to		= (isset($wp_font_list[$f_n]['variants'][$f_w]) && is_array($wp_font_list[$f_n]['variants'][$f_w])) ? intval($wp_font_list[$f_n]['variants'][$f_w]['to']) : false;
					$exact	= (isset($wp_font_list[$f_n]['variants'][$f_w]) && !is_array($wp_font_list[$f_n]['variants'][$f_w])) ? intval($wp_font_list[$f_n]['variants'][$f_w]) : false;

					foreach($f_v ?? [] as $f_v_id => $f_v_check){
						if($exact !== false){
							if(intval($f_v_check) === $exact) unset($SR_GLOBALS['fonts']['queue'][$f_n]['variants'][$f_w][$f_v_id]);
						}else{
							if(intval($f_v_check) >= $from && intval($f_v_check) <= $to) unset($SR_GLOBALS['fonts']['queue'][$f_n]['variants'][$f_w][$f_v_id]);
						}
					}
				}

				if(
					(!isset($SR_GLOBALS['fonts']['queue'][$f_n]['variants']['normal']) || empty($SR_GLOBALS['fonts']['queue'][$f_n]['variants']['normal'])) && 
					(!isset($SR_GLOBALS['fonts']['queue'][$f_n]['variants']['italic']) || empty($SR_GLOBALS['fonts']['queue'][$f_n]['variants']['italic']))
				){
					unset($SR_GLOBALS['fonts']['queue'][$f_n]);
				}
			}
		}
	}
}
