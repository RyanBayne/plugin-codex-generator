<?php
/*
Plugin Name: Plug-in Codex Generator
Plugin URI: http://stephenharris.info
Description: Plug-in Codex Generator generates documentation pages based on sourcecode comments
Author: Stephen Harris
Author URI: http://stephenharris.info
Version: 1.0.6
Text Domain: plugincodexgen
License Notes: GPLv2 or later
*/

/* 
 * Known Issues
 *
 * **This plug-in does not currently document classes or their methods**
 * Hooks need improved parsing. Should we create docbloc parsing for hooks?
 * Not confirmed, but a hook with array($this,'callback') or array(__CLASS__,'callback') as an argument will just be interpreted as $array.
 * Doesn't document all tags. E.g. @access
 * When applying a filter callback to $this from within a class. The generated docs will just have '$this' as an argument - would be nice to treat it
 * differently from a normal variable. But this is not easy.
 * Although you can generate documents for multiple plug-ins, there is currently no way of discerning them once the documents are generated. This would need
 * a extra taxonomy for pcg_hook & pcg_function.
*/

//Define constants
define('PLUGIN_CODEX_GENERATOR_LINK', add_query_arg(array('page'=> 'plugincodexgen','post_type'=>'pcg_function'), admin_url('edit.php')));
define('PLUGIN_CODEX_GENERATOR_DIR', plugin_dir_path(__FILE__ ));

/**
* Retrieve plug-in option
*
* Since there is limited UI you can alter options by using the plugincodexgen_option_{option}
* filter. 
* @since 1.0
*
* @param string $option The option key
* @param mixed $default The value to use if the option doesn't exist. Default: false.
* @return mixed The option value
*/
function plugincodexgen_get_option($option,$default=false){

      $defaults = array(
	'supports'=>array('title','editor'),//What the post types pcg_hook & pcg_function support
	'function_rewrite'=>'documentation/function',
	'hook_rewrite'=>'documentation/hook',
      );
      $options = get_option('plugin_codex_gen',$defaults);
      $options = wp_parse_args( $options, $defaults );

      if( !isset($options[$option]) )
		return apply_filters( 'plugincodexgen_default_option_' . $option, $default );

	return apply_filters( 'plugincodexgen_option_' . $option, $options[$option]);
}

/* Include functions */
require PLUGIN_CODEX_GENERATOR_DIR . 'includes/utility-functions.php';
require PLUGIN_CODEX_GENERATOR_DIR . 'includes/shortcode-handler.php';
require PLUGIN_CODEX_GENERATOR_DIR . 'includes/related-function-widget.php';

/* Hook, Functions & Queries */
require PLUGIN_CODEX_GENERATOR_DIR .'includes/class-pcg-hook.php';
require PLUGIN_CODEX_GENERATOR_DIR .'includes/class-pcg-function.php';
require PLUGIN_CODEX_GENERATOR_DIR .'includes/class-function-query.php';
require PLUGIN_CODEX_GENERATOR_DIR .'includes/class-hook-query.php';

/* Admin only */
if( is_admin() ){
	require PLUGIN_CODEX_GENERATOR_DIR . 'admin/actions.php';
	require PLUGIN_CODEX_GENERATOR_DIR . 'admin/functions-admin-page.php';
	require PLUGIN_CODEX_GENERATOR_DIR . 'admin/hooks-admin-page.php';
	require PLUGIN_CODEX_GENERATOR_DIR . 'admin/class-functions-table.php';
	require PLUGIN_CODEX_GENERATOR_DIR . 'admin/class-hooks-table.php';
}

/* Register CPT */
require PLUGIN_CODEX_GENERATOR_DIR . 'includes/cpt.php';

/* Libraries */
require PLUGIN_CODEX_GENERATOR_DIR . 'includes/class-phpdoc-parser.php';
require PLUGIN_CODEX_GENERATOR_DIR.'PHP/Reflect/Autoload.php';

/* Widget */
/**
 * Initialises widget
 * @since 1.0
 * @ignore
 * @access private
*/
function _plugincodexgen_widgets_init(){
	register_widget('PCG_Related_Function_Widget');
}
add_action( 'widgets_init', '_plugincodexgen_widgets_init');

/* Activate / Deactivate */
register_activation_hook(__FILE__,'_plugincodexgen_install'); 
register_deactivation_hook( __FILE__, '_plugincodexgen_deactivate' );
register_uninstall_hook( __FILE__,'_plugincodexgen_uninstall');

/**
 * Install routine
 *
 * Hooked using register_activation_hook
 * Adds empty array as options, flushes rewrite rules
 * @since 1.0.1
 * @ignore
*/
function _plugincodexgen_install(){
	add_option('plugin_codex_gen',array());
	flush_rewrite_rules();
}

/**
 * Deactivate routine
 *
 * Hooked using register_deactivation_hook
 * Flushes rewrite rules
 * @since 1.0.1
 * @ignore
*/
function _plugincodexgen_deactivate(){
	flush_rewrite_rules();
}

/**
 * Uninstall routine
 *
 * Hooked using register_uninstall_hook
 * Removes options and flushes rewrite rules
 * @since 1.0.1
 * @ignore
*/
function _plugincodexgen_uninstall(){
	delete_option('plugin_codex_gen');
	flush_rewrite_rules();
}
