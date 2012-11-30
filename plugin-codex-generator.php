<?php
/*
Plugin Name: Plug-in Codex Generator
Plugin URI: http://stephenharris.info
Description: Plug-in Codex Generator generates documentation pages based on sourcecode comments
Author: Stephen Harris
Author URI: http://stephenharris.info
Version: 1.0
Text Domain: plugincodexgen
License Notes: GPLv2 or later
*/

//TODO
//Classes
//Hooks
//Improved UI
//Caching?
//Templates?

define('PLUGIN_CODEX_GENERATOR_LINK', add_query_arg(array('page'=> 'plugincodexgen','post_type'=>'pcg_function'), admin_url('edit.php')));
define('PLUGIN_CODEX_GENERATOR_DIR', plugin_dir_path(__FILE__ ));


function plugincodexgen_get_option($option,$default=false){

      $defaults = array(
	'supports'=>array('title','editor'),
	'prettyurl'=>'documentation/function',
      );
      $options = get_option('plugin_codex_gen',$defaults);
      $options = wp_parse_args( $options, $defaults );

      if( !isset($options[$option]) )
           return $default;

      return $options[$option];
}


require PLUGIN_CODEX_GENERATOR_DIR . 'includes/utility-functions.php';
require PLUGIN_CODEX_GENERATOR_DIR . 'includes/related-function-widget.php';
require PLUGIN_CODEX_GENERATOR_DIR .'includes/class-function-query.php';
require PLUGIN_CODEX_GENERATOR_DIR . 'admin/class-functions-table.php';

if( is_admin() ){
	require PLUGIN_CODEX_GENERATOR_DIR . 'admin/actions.php';
	require PLUGIN_CODEX_GENERATOR_DIR . 'admin/class.php';
	PCG_Admin_Page::on_load();
}

/** Register CPT */
require PLUGIN_CODEX_GENERATOR_DIR . 'includes/cpt.php';

/* Libraries */
require PLUGIN_CODEX_GENERATOR_DIR . 'includes/class-phpdoc-parser.php';
require PLUGIN_CODEX_GENERATOR_DIR . 'includes/markdown-extra.php';
require PLUGIN_CODEX_GENERATOR_DIR.'PHP/Reflect/Autoload.php';

add_action( 'widgets_init', 'plugincodexgen_widgets_init');
function plugincodexgen_widgets_init(){
	register_widget('PCG_Related_Function_Widget');
}
