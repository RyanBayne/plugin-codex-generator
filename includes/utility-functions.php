<?php

function plugincodex_filter_php_file( $file ){
	return ( substr( $file, -4, 4) == '.php' );
}


/**
 * Takes a relative plug-in file(s) and returns the absolute path to the file.
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



function plugincodex_get_paths(){


}

/**
 * Adjust type names to forms, supported by Codex.
 *
 * @param mixed $type
 * @param string $context
 *
 * @return string
 */
 function plugincodex_type_to_string($type, $context = '') {

	$type = str_replace('bool', 'boolean', $type);

	return $type;
}

	/**
	 * Turns mixed type values into string representation.
	 *
	 * @param mixed $value
	 *
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
	 * @param string $delimiter
	 * @param string $string
	 * @param int $count
	 * @param mixed $default
	 *
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
	 * @param string $path full local path
	 *
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
		}
		elseif( false !== strpos($path, $content) ) {

			$path = str_replace($content,'',$path);
		}
		else {

			$path = str_replace($abspath, '', $path);
		}

		$path = plugincodex_trim_and_forward_slashes($path);

		return $path;
	}

	/**
	 * Pads exploded array to target number of elements with default value.
	 *
	 * @param string $delimiter
	 * @param string $string
	 * @param int $count
	 * @param mixed $default
	 *
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
	 * @param string $path
	 *
	 * @return string
	 */
	function plugincodex_trim_and_forward_slashes($path) {

		$path = trim($path, '\/');
		$path = str_replace('\\', '/', $path);

		return $path;
	}

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
	 * @param string $name
	 *
	 * @return string
	 */
	function plugincodex_sanitize_function_name( $name ) {

		$name = wp_kses($name, array());
		$name = trim( $name, ' ()' );
		$name = str_replace(' ', '_', $name);

		return $name;
	}

	

