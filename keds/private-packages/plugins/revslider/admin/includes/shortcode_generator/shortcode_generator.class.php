<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/
 * @copyright 2026 ThemePunch
 */

if(!defined('ABSPATH')) exit();

class RevSliderShortcodeWizard extends RevSliderFunctions {

	public static function enqueue_scripts(){
		global $pagenow;

		$f = RevSliderGlobals::instance()->get('RevSliderFunctions');
		$action = $f->get_val($_GET, 'action');
		if($action === 'elementor') return;

		// only add scripts if native WordPress editor, Gutenberg or Visual Composer
		// Elementor has its own hooks for adding scripts
		
		if($action === 'edit' || in_array($pagenow, ['post-new.php', 'site-editor.php', 'widgets.php']) || $f->get_val($_GET, 'vc_action', '') === 'vc_inline'){
			self::add_scripts();
		}
	}

	/**
	 * add the styles through the block editor filter
	 */
	public static function sr_theme_block_editor_assets(){
		self::add_styles();
	}

	public static function add_styles(){}

	public static function add_scripts($elementor = false, $divi = false, $skipSvg = false) {
		global $SR_GLOBALS;
		$f = RevSliderGlobals::instance()->get('RevSliderFunctions');
		$action = $f->get_val($_GET, 'action');
		if($elementor && $action !== 'elementor') return;

		require_once(RS_PLUGIN_PATH . 'admin/includes/functions-admin.class.php');
		require_once(RS_PLUGIN_PATH . 'admin/includes/template.class.php');
		require_once(RS_PLUGIN_PATH . 'admin/includes/folder.class.php');
		require_once(RS_PLUGIN_PATH . 'public/revslider-front.class.php');

		//check user permissions
		if(!current_user_can('edit_posts') && !current_user_can('edit_pages')) return;

		// checks for built-in gutenberg version
		$current_screen = function_exists('get_current_screen') ? get_current_screen() : '';
		$is_gutenberg = !empty($current_screen) && method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor();

		if(!$elementor && !$divi){
			//verify the post type
			global $typenow, $pagenow;

			if($pagenow !== 'site-editor.php'){
				$post_types = get_post_types();
				if(empty($post_types) || !is_array($post_types)) $post_types = ['post', 'page'];
				if(!in_array($typenow, $post_types) && $pagenow !== 'widgets.php') return;
			}
			
			// checks for old plugin version
			if(!$is_gutenberg) $is_gutenberg = function_exists('is_gutenberg_page') && is_gutenberg_page();

			// gutenberg
			if(!$is_gutenberg){
				add_filter('mce_external_plugins', ['RevSliderShortcodeWizard', 'add_tinymce_shortcode_editor_plugin']);
				add_filter('mce_buttons', ['RevSliderShortcodeWizard', 'add_tinymce_shortcode_editor_button']);
			}

			if($pagenow !== 'site-editor.php') self::add_styles(); //the styles need to be added through the block editor filter in site editor
		}
		
		//add v7 scripts/css
		$rs_front	= RevSliderGlobals::instance()->get('RevSliderFront');
		$rs_fonts	= RevSliderGlobals::instance()->get('RevSliderFonts');
		$rs_output	= RevSliderGlobals::instance()->get('RevSlider7Output');
		wp_enqueue_script('sr7', RS_PLUGIN_URL_CLEAN . 'public/js/sr7.js', '', RS_REVISION, ['strategy' => 'async']);	
		wp_enqueue_script('sr7page', RS_PLUGIN_URL_CLEAN . 'public/js/page.js', '', RS_REVISION, ['strategy' => 'async']);

		if (!$is_gutenberg) wp_enqueue_style('sr7css', RS_PLUGIN_URL_CLEAN . 'public/css/sr7.css', '', RS_REVISION);


		wp_enqueue_script('_tpt', RS_PLUGIN_URL_CLEAN . 'public/js/libs/tptools.js', '', RS_REVISION, ['strategy' => 'async']);		
		add_action('wp_footer', [$rs_fonts, 'load_google_fonts']);
		add_action('wp_footer', [$rs_output, 'add_js'], 100);

		$rsaf = new RevSliderFunctionsAdmin();
		$rsa = $rsaf->get_short_library();
		if(!empty($rsa)) $obj = $rsaf->json_encode_client_side($rsa);

		$favs = $rsaf->get_options(['favorites', 'favorites'], []);
		$favs = !empty($favs) ? $rsaf->json_encode_client_side($favs) : false;
		
		$rs_color_picker_presets = RSColorpicker::get_color_presets();
		
		$global = $rs_front->get_global_settings();
		echo $rs_front->js_add_header_scripts();

		echo self::get_shortcode_javascript();
		?>
		<script>
			var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php', 'relative' ) ); ?>';
		</script>
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
		<?php
		if (!$skipSvg) {
			echo file_get_contents(RS_PLUGIN_PATH . 'admin/assets/images/sprite.svg');
		}
	}

	public static function get_shortcode_javascript(){
		$rsaf = new RevSliderFunctionsAdmin();
		$sr_valid = $rsaf->_truefalse($rsaf->get_options(['system', 'valid'], 'false'));
		ob_start();
		?>
		<script>
			window.SR7			??= {};
			SR7.E				??= {gAddons:{}};
			SR7.E.registered	= <?php echo ($sr_valid !== true) ? 'false' : 'true'; ?>;

			SR7.LANG ??= {};
			SR7.LANG["Please wait..."] = "<?php __('Please wait...', 'revslider'); ?>";
			SR7.LANG["Premium"] = "<?php _e('Premium', 'revslider'); ?>";
			SR7.LANG["Slider"] = "<?php _e('Slider', 'revslider'); ?>";
			SR7.LANG["Hero"] = "<?php _e('Hero', 'revslider'); ?>";
			SR7.LANG["Carousel"] = "<?php _e('Carousel', 'revslider'); ?>";
			SR7.LANG["Per Pages"] = "<?php _e('Per Page', 'revslider'); ?>";
			SR7.LANG["All Items"] = "<?php _e('All Items', 'revslider'); ?>";
			SR7.LANG["Show all items"] = "<?php _e('Show all items', 'revslider'); ?>";
		</script>
		<?php
		return ob_get_clean();
	}

	public static function enqueue_files(){
		echo '<div id="rb_modal_underlay" style="display:none"></div>';

		echo "<script>";
	
		echo "class SrSp extends HTMLElement {";
		echo "	static get observedAttributes() {return ['h', 'w'];}";
		echo "	constructor() {super();}";
		echo "	connectedCallback() {this.updateDimensions();}";
		echo "	attributeChangedCallback(name, oldValue, newValue) {this.updateDimensions();}";
		echo "	updateDimensions() {";
		echo "		const height = this.getAttribute('h');";
		echo "		const width = this.getAttribute('w');";
		echo "		if (width !== null) {";
		echo "		this.style.display = 'inline-block';";
		echo '		this.style.width = `${width}px`;';
		echo '		this.style.height = height ? `${height}px` : \'auto\';';
		echo "		} else {";
		echo "		this.style.display = 'block';";
		echo '		this.style.height = height ? `${height}px` : \'auto\';';
		echo "		}";
		echo "	}";
		echo "}";
		echo "if (!customElements.get('sr-sp')) customElements.define('sr-sp', SrSp);";
		
		echo "window.SR7 ??= {};";
		echo "SR7.LIB ??= {};";
		echo "SR7.E ??= {};";
		echo "SR7.E.block_nonce = '" . wp_create_nonce('revslider_actions') . "';"; // This nonce is working for Divi builder
		echo "</script>";

		echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">';

		echo '<div class="sr--block--editor--popup--wrap" style="display:none;">';
		require_once(RS_PLUGIN_PATH . 'admin/views/popups.php');
		echo '</div>';
	}


	/**
	 * add script tinymce shortcode script
	 * @since: 5.1.1
	 */
	public static function add_tinymce_shortcode_editor_plugin($plugin_array){
		//this is an OLD js from sr6. needs to be updated or removed
		//$plugin_array['revslider_sc_button'] = RS_PLUGIN_URL . 'admin-sr6/assets/js/shortcode_generator/tinymce.js';

		return $plugin_array;
	}

	/**
	 * Add button to tinymce
	 * @since: 5.1.1
	 */
	public static function add_tinymce_shortcode_editor_button($buttons){
		array_push($buttons, 'revslider_sc_button');

		return $buttons;
	}

	/**
	 * add wildcards metabox variables to posts
	 * @var $post_types: null = all, post = only posts
	 */
	public static function add_slider_meta_box($post_types = null){
		try {
			$post_types = [];
			register_post_meta('', 'rs_blank_template', [
				'show_in_rest'   => true,
				'single'         => true,
				'type'           => 'string'
			]);
			register_post_meta('', 'rs_page_bg_color', [
				'show_in_rest'   => true,
				'single'         => true,
				'type'           => 'string'
			]);
			register_post_meta( '', 'slide_template_v7', [
				'show_in_rest'   => true,
				'single'         => true,
				'type'           => 'string'
			]);
		} catch (Exception $e){}
	}

	/**
	 * add wildcards metabox variables to posts
	 * @var $post_types: null = all, post = only posts
	 */
	public static function add_slider_meta_box_assets($post_types = null){
		try {
			wp_enqueue_script('slider_revolution_metabox_js', RS_PLUGIN_URL_CLEAN . 'admin/includes/meta_box/build/index.js', ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-core-data', 'wp-data'], RS_REVISION);
			wp_enqueue_style('slider_revolution_metabox_css', RS_PLUGIN_URL_CLEAN . 'admin/includes/meta_box/build/index.css', RS_REVISION);
		} catch (Exception $e){}
	}

	/**
	 * on save post meta. Update metaboxes data from post, add it to the post meta 
	 */
	public static function on_updated_post_meta($meta_id, $post_id, $meta_key, $meta_value){
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;
		if(empty($post_id)) return false;
		if(function_exists('is_user_logged_in') && !is_user_logged_in() || !current_user_can('edit_post', $post_id)) return $post_id;

		if ($meta_key === "slide_template_v7") {
			if (in_array($meta_value, ['', 'default'])) {
				delete_post_meta($post_id, $meta_key);
			}
		} else if ($meta_key === "rs_page_bg_color") {
			if(strtolower($meta_value) === '#ffffff'){
				delete_post_meta($post_id, $meta_key);
			}
		} else if ($meta_key === "rs_blank_template") {
			if(empty($meta_value) && get_post_meta($post_id, '_wp_page_template', true) == '../public/views/revslider-page-template.php'){
				update_post_meta($post_id, '_wp_page_template', '');
			}
			if(!empty($meta_value) &&  $meta_value == 'on'){
				update_post_meta($post_id, '_wp_page_template', '../public/views/revslider-page-template.php');
			}
		}
	}

	/**
	 * Enqueue html content for the Divi Builder
	 */
	public static function enqueue_divi_builder_files() {
		add_action('wp_enqueue_scripts', ['RevSliderShortcodeWizard', 'enqueue_files']);
	}

	/**
	 * Enqueue html content for the WPBakery Builder
	 */
	public static function enqueue_wpbakery_files() {
		add_action('wp_enqueue_scripts', ['RevSliderShortcodeWizard', 'enqueue_files']);
	}	

	/**
	 * Enqueue styles for WP Bakery
	 */
	public static function enqueue_wpbakery_styles() {
		wp_enqueue_style('slider_revolution_wpbakery_css', RS_PLUGIN_URL_CLEAN . 'admin/includes/shortcode_generator/wpbakery/assets/css/sr7-wpbakery.css', '', RS_REVISION);
	}	

}
