<?php

class PCG_Admin_Page {

	/* @var string $hook_suffix holds plugin page identifier*/
	static $hook_suffix;

	/* @var string $function holds name of function being processed */
	static $function = '';

	/* @var Plugin_Codex_Generator_Functions_Table $table */
	static $table;

	/**
	 * Sets up plugin's hooks during initial load.
	 */
	static function on_load() {

		self::add_method('admin_menu');
		self::add_method('admin_init');
		add_filter('set-screen-option',array(__CLASS__,'set_screen_option'), 10, 3);
	}

	/**
	 * Registers plugin's admin page in Tools section.
	 */
	static function admin_menu() {
		self::$hook_suffix = add_submenu_page( 'edit.php?post_type=pcg_function', __('Codex Generator', 'plugincodexgen'), __('Codex Generator', 'plugincodexgen'), 'manage_options', 'plugincodexgen', array(__CLASS__, 'page'));
	}

	/**
	 * Loads plugin text domain, hooks load and Ajax handler for suggest.
	 */
	static function admin_init() {
		load_plugin_textdomain('plugincodexgen', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/');
		add_action('load-'.self::$hook_suffix, array(__CLASS__, 'load'));
		add_action('wp_ajax_plugin_codex_gen_suggest', array(__CLASS__, 'suggest'));
		add_action('wp_ajax_plugin_codex_gen_wiki', array(__CLASS__, 'wiki'));
		self::add_method('plugin_row_meta', 10, 2);
	}


	function screen_options($options, $screen){
		$options .='<input type="hidden" name="action" value="update-parser-options" />';
		$options .= self::pick_plugin(); 
		$options .= get_submit_button('Update Options','secondary','submit',false); 
		return $options;
	}

	/**
	 * Hooks things only necessary on plugin's page.
	 */
	static function load() {

		self::add_method('admin_enqueue_scripts');
		self::add_method('admin_print_styles');
		self::add_method('admin_print_footer_scripts');

		//Add screen option
		add_screen_option('per_page', array('label' => __('functions', 'plugincodexgen'), 'default' => 15));
		add_filter('screen_settings', array(__CLASS__,'screen_options'), 10, 2);

		self::$table = new Plugin_Codex_Generator_Functions_Table();
		register_column_headers(self::$hook_suffix, self::$table->get_columns());
	}

	function set_screen_option($false, $option, $value) {

		if( isset($_POST['plugin_codex_plugin']) && 'update-parser-options' == $_POST['action'] ){
			$plugin = $_POST['plugin_codex_plugin'];
			update_option('plugin-codex_plugin',$plugin);
		}

		if('tools_page_plugincodexgen_per_page' == $option)
			return $value;

		return $false;
	}

	/**
	 * Enqueues suggest.
	 */
	static function admin_enqueue_scripts() {

		wp_enqueue_script('suggest');
		add_thickbox();
	}

	/**
	 * Outputs bit of CSS for suggest dropdown.
	 */
	static function admin_print_styles() {

		?><style type="text/css">
		#functions-search-input { width: 200px; }
		.top .reset { margin: 0 5px; }
		.ac_results{ min-width: 197px; }
		.tablenav p.search-box { float: left; }
		.widefat .column-version { width: 10%; }
		.widefat .column-links { text-align: left !important; }
		.widefat .column-get { width: 10%; }
		.bottom .button { float:left; margin: 5px 0; }
	</style><?php
	}

	/**
	 * Sets up suggest.
	 */
	static function admin_print_footer_scripts() {

		?><script type="text/javascript">
		jQuery(document).ready(function($) { $('#functions-search-input').suggest(ajaxurl + '?action=plugin_codex_gen_suggest'); });
		</script><?php
	}

	static function plugin_row_meta($plugin_meta, $plugin_file) {

		if( false !== strpos($plugin_file, basename(__FILE__)) ) {

			$link = add_query_arg('page', 'plugincodexgen', admin_url('tools.php'));
			$plugin_meta[] = "<a href='$link'>" . __('Tools > Plugin Codex Generator', 'plugincodexgen') . '</a>';
		}

		return $plugin_meta;
	}

	
	/**
	 * Outputs plugin's admin page.
	 */
	static function page() {
		?>
		<div class="wrap"><?php

			screen_icon('tools');
			echo '<h2>'. __('Codex Generator', 'plugincodexgen') .'</h2>';

			self::$table->prepare_items();
			?>

			<form id="functions" method="get" action="">
				<input type="hidden" name="page" value="plugincodexgen" />
				<input type="hidden" name="post_type" value="pcg_function" />
				<?php self::$table->display(); ?>
			</form>
		</div>
		<?php
	}


	static function pick_plugin(){
		$plugins = get_plugins();
		$plugin = get_option('plugin-codex_plugin');

		$return = sprintf('<strong><label for="plugin_codex_plugin"> %s </label></strong>',__('Select plugin to parse:'));
		$return .= sprintf('<select name="plugin_codex_plugin" id="plugin_codex_plugin">');

		foreach ( $plugins as $plugin_key => $a_plugin ) {
			$return .= sprintf('\n\t<option value="%s" %s>%s</option>',
					esc_attr($plugin_key),
					selected($plugin,$plugin_key,false),
					esc_html($a_plugin['Name'])
				);
		}
		$return .= '</select>';
		return $return;
	}

	/**
	 * Ajax handler for suggest.
	 */
	static function suggest() {

		if( !current_user_can('manage_options') )
			die;

		if( empty($_REQUEST['q']) )
			die;

		$search = plugincodex_get_functions( array(
							's' => self::sanitize_function_name($_REQUEST['q']),
							 'orderby' => 'match',
							'number' => 15,
							));
		$functions = wp_list_pluck($search,'name');
		echo implode("\n", $functions);
		die;
	}


	static function wiki() {

		if( !current_user_can('manage_options') )
			die;

		if( empty($_REQUEST['function']) )
			die;

		$function = self::sanitize_function_name($_REQUEST['function']);

		$data = array_pop( plugincodex_get_functions( array('functions__in'=>array($function)) ) );

		$wiki = $data->get_wiki(false);
		$html = plugincodex_markdown($wiki);
		echo '<hr><h1> Preivew </h1><hr>';
		echo $html;
		echo '<hr><h1> MarkDown </h1><hr>';
		echo '<br /><textarea style="width:100%;height:90%;" class="code">'.esc_textarea($wiki).'</textarea>';
		echo '<hr><h1> HTML </h1><hr>';
		echo '<br /><textarea style="width:100%;height:90%;" class="code">'.esc_textarea($html).'</textarea>';
		die;
	}
	/**
	 * Sanitizes function name, replaces spaces with undescores.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	static function sanitize_function_name( $name ) {

		$name = wp_kses($name, array());
		$name = trim( $name, ' ()' );
		$name = str_replace(' ', '_', $name);

		return $name;
	}

	
	/**
	 * Shorthand for adding methods to hooks of same name.
	 *
	 * @param string $method
	 * @param int $priority
	 * @param int $accepted_args
	 */
	static function add_method($method, $priority = 10, $accepted_args = 1) {

		if( method_exists(__CLASS__, $method) );
			add_action($method, array(__CLASS__,$method), $priority, $accepted_args);
	}
}
