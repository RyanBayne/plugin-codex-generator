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

/* 
 * Known Issues
 *
 * Doesn't document all tags. Notably: @uses and @used-by @sub-package @see @access
 * Would like to add urls that link @uses, @used-by, @see
 * Not confirmed, but a filter with array($this,'callback') or array(__CLASS__,'callback') as an argument will just be interpreted as $array.
 * When applying a filter callback to $this from within a class. The generated docs will just have '$this' as an argument - would be nice to treat it
 * differently from a normal variable. But this is not easy.
 * Classes and their methods are not uspported
 * Although you can generate documents for multiple plug-ins, there is currently no way of discerning them once the documents are generated. This would need
 * a extra taxonomy for pcg_hook & pcg_function.
*/

//Define constants
define('PLUGIN_CODEX_GENERATOR_LINK', add_query_arg(array('page'=> 'plugincodexgen','post_type'=>'pcg_function'), admin_url('edit.php')));
define('PLUGIN_CODEX_GENERATOR_DIR', plugin_dir_path(__FILE__ ));

/**
* Retrieve plug-in option
* @since 1.0
* @ignore
*
* @param string $option The option key
* @param mixed $default The value to use if the option doesn't exist. Default: false.
* @return mixed The option value
*/
function plugincodexgen_get_option($option,$default=false){

      $defaults = array(
	'supports'=>array('title','editor'),
	'function_rewrite'=>'documentation/function',
	'hook_rewrite'=>'documentation/hook',
      );
      $options = get_option('plugin_codex_gen',$defaults);
      $options = wp_parse_args( $options, $defaults );

      if( !isset($options[$option]) )
           return $default;

      return $options[$option];
}

/* Include functions */
require PLUGIN_CODEX_GENERATOR_DIR . 'includes/utility-functions.php';
require PLUGIN_CODEX_GENERATOR_DIR . 'includes/shortcode-handler.php';
require PLUGIN_CODEX_GENERATOR_DIR . 'includes/related-function-widget.php';

/* Hook, Functions & Queries */
require PLUGIN_CODEX_GENERATOR_DIR .'includes/class-pcg-hook.php';
require PLUGIN_CODEX_GENERATOR_DIR .'includes/class-pcg-function.php';
require PLUGIN_CODEX_GENERATOR_DIR .'includes/class-function-query.php';

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
add_action( 'widgets_init', '_plugincodexgen_widgets_init');
function _plugincodexgen_widgets_init(){
	register_widget('PCG_Related_Function_Widget');
}
