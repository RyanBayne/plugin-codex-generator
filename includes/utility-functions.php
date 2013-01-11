<?php
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
	'remote_url_fetch' => true,
      );
      $options = get_option('plugin_codex_gen',$defaults);
      $options = wp_parse_args( $options, $defaults );

      if( !isset($options[$option]) )
		return apply_filters( 'plugincodexgen_default_option_' . $option, $default );

	return apply_filters( 'plugincodexgen_option_' . $option, $options[$option]);
}

/**
 * Simple check to see if the file is a .php or not
 *
 * @since 1.0
 *
 *@param filepath or file name to check
 *@return bool True if it ends in .php extension, false otherwise
*/
function plugincodex_filter_php_file( $file ){
	return ( substr( $file, -4, 4) == '.php' );
}


/**
 * Takes a relative plug-in file(s) and returns the absolute path to the file.
 *
 * @since 1.0
 *
 *@param string|array Relative file or array of files
 *@return string|array Absolute file or array of files
*/
function plugincodex_get_plugin_path( $file ){

	if( is_array($file) ){
		return array_map('plugincodex_get_plugin_path', $file);
	}

	if ( ! is_file( $path = trailingslashit(WPMU_PLUGIN_DIR).$file ) ) {
		if ( ! is_file( $path = trailingslashit(WP_PLUGIN_DIR).$file ) )
			return false;
	}
	return $path;
}

/**
 * Adjust type names to forms, supported by Codex.
 *
 * @since 1.0
 *
 * @param mixed $type
 * @param string $context
 * @return string
 */
 function plugincodex_type_to_string($type, $context = '') {
	return str_replace('bool', 'boolean', $type);
}

/**
 * Turns mixed type values into string representation.
 *
 * @since 1.0
 * @param mixed $value
 * @return string
 */
function plugincodex_value_to_string($value) {

	if( is_null($value) )
		$value = 'null';

	elseif( is_bool($value) )
		$value = $value ? 'true' : 'false';

	elseif( is_string($value) && empty($value) )
		$value = "''";

	elseif( is_array($value) )
		if( empty($value) )
			$value = 'array()';
		else
			$value = "array( '".implode("','", $value) . "')";

	return $value;
}


/**
 * Pads exploded array to target number of elements with default value.
 *
 * @since 1.0
 *
 * @param string $delimiter
 * @param string $string
 * @param int $count
 * @param mixed $default
 * @return array
 */
function plugincodex_padded_explode( $delimiter, $string, $count, $default ) {

	$output = array();
	$pieces = substr_count($string, $delimiter) + 1;

	if ($pieces < 2)
		$output[] = $string;
	elseif ($pieces >= $count)
		$output = explode($delimiter, $string, $count);
	else
		$output = explode($delimiter, $string);

	while ($count > count($output))
		$output[] = $default;

	return $output;
}


/**
 * Retrieves relative path to file, containing a function.
 *
 * @since 1.0
 * @param string $path full local path
 * @return string file path
 */
 function plugincodex_sanitize_path($path) {

	static $abspath, $content, $content_dir, $plugin, $plugin_dir;

	if (empty($abspath)) {

		$abspath = plugincodex_trim_and_forward_slashes(ABSPATH);
		$content = plugincodex_trim_and_forward_slashes(WP_CONTENT_DIR);
		$content_dir = array_pop(preg_split('/[\/\\\]/', $content));
		$plugin = plugincodex_trim_and_forward_slashes(WP_PLUGIN_DIR);
		$plugin_dir = array_pop(preg_split('/[\/\\\]/', $plugin));
	}

	$path = plugincodex_trim_and_forward_slashes($path);

	if( false !== strpos($path, $plugin) ) {
		$path = str_replace($plugin,'',$path);
	
	}elseif( false !== strpos($path, $content) ) {
		$path = str_replace($content,'',$path);
	
	}else{
		$path = str_replace($abspath, '', $path);
	}

	$path = plugincodex_trim_and_forward_slashes($path);

	return $path;
}

/**
 * Pads exploded array to target number of elements with default value.
 *
 * @since 1.0
 *
 * @param string $delimiter
 * @param string $string
 * @param int $count
 * @param mixed $default
 * @return array
 */
 function plugincodex_explode( $delimiter, $string, $count, $default ) {

	$output = array();
	$pieces = substr_count($string, $delimiter) + 1;

	if ($pieces < 2)
		$output[] = $string;
	elseif ($pieces >= $count)
		$output = explode($delimiter, $string, $count);
	else
		$output = explode($delimiter, $string);

	while ($count > count($output))
		$output[] = $default;

	return $output;
}

/**
 * Trims any slashes and turns rest to forward.
 *
 * @since 1.0
 * @param string $path
 * @return string
 */
function plugincodex_trim_and_forward_slashes($path) {

	$path = trim($path, '\/');
	$path = str_replace('\\', '/', $path);
	return $path;
}


/**
 * Sanitizes version. 
 *
 * Removes superflous 0s from version number
 * @since 1.0
 * @param string $name
 * @return string
 */
function plugincodex_sanitize_version($version) {
	if( is_array($version) )
		$version = '1.0';
	$version = preg_replace('/[^\d\.]/', '', $version);
	$version = trim($version, '.');

	if (strlen($version) > 3 && '.0' === substr($version, -2))
		$version = substr($version, 0, 3);

	return $version;
}

/**
 * Sanitizes function name, replaces spaces with undescores.
 *
 * @since 1.0
 * @param string $name
 * @return string
 */
function plugincodex_sanitize_function_name( $name ) {

	$name = wp_kses($name, array());
	$name = trim( $name, ' ()' );
	$name = str_replace(' ', '_', $name);

	return $name;
}


/**
* Removes a selected prefix from the begining of a string
*
* @since 1.0.4
* @param string $string
* @param string $prefix
* @return string If the passed string is prefixed by $prefix, this is removed.
*/
function plugincodex_remove_from_start( $string, $prefix ){

	$p = strlen($prefix);

	if (substr($string, 0, $p) == $prefix) {
		$string = substr($string, $p, strlen($string) );
	} 

	return $string;
}

function plugincodex_find_reference_link($reference, $context = 'unknown'){

	$reference_name = plugincodex_sanitize_function_name($reference);
	$expires = 24*60*60;
	$link = false;

	if( substr($reference,-2) == '()' || $context == 'function' ){
		/* Function names must end with '()' */

		//Check and use cache
		$reference_links = get_transient('pcg_function-'.$reference_name);
		if( !$reference_links )
			$reference_links = array();

		if( isset($reference_links[$reference_name]) ){
			$link = $reference_links[$reference_name];
		
		}else{
			//Not in transient

			if( $replacement = get_posts(array('post_type'=>'pcg_function','name'=>$reference_name, 'numberposts'=>1)) ){
				//Found locally
				$link = get_permalink($replacement[0]);

			}elseif( !$link && plugincodexgen_get_option('remote_url_fetch') ){
				//Not found locally - check wpseek
				$response = wp_remote_get('http://api.wpseek.com/?method=wordpress.getfunction&s='.sanitize_title($reference));
				$body = json_decode(trim(wp_remote_retrieve_body($response),';()'));
				if( $body && isset($body->codexURI) )
					$link =$body->codexURI;
			}

			//Update transient
			$reference_links[$reference_name] = $link;
			set_transient('pcg_function-'.$reference_name,$reference_links, $expires);
		}
	
	}else{
		/* Else assumed to be a hook - perhaps also a class if they become supported? */
		$replacement = get_posts(array('post_type'=>'pcg_hook','name'=>sanitize_title($reference), 'numberposts'=>1));

		if( $replacement )
			$link = get_permalink($replacement[0]);
	}

	return $link;
}
