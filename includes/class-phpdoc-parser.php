<?php

class Codex_Generator_Phpdoc_Parser {

	static $arrays = array();
	static $versions = array();
	static $paths = array();

	/**
	 * Parses PHPDoc
	 *
	 * @param string $doc PHPDoc string
	 *
	 * @return array of parsed information
	 */
	static function parse_doc($doc) {

		$short_desc = $long_desc = $last_tag = '';
		$tags = array('param'=>array());

		$did_short_desc = false;
		$did_long_desc = false;
		$prepend_short_desc = '';
		$prepend_long_desc = '';

		$doc = explode("\n", $doc);

		foreach( $doc as $line ) {

			$line = trim($line);
			$line = trim($line, " \t");
			$line = plugincodex_remove_from_start( $line, '*');
			$line = plugincodex_remove_from_start( $line, ' ');
			$trimmed_line = trim($line,' ');

			// Start or end
			if ('/' == trim($trimmed_line,'*'))
				continue;

			// Empty lines as start
			if (empty($line) && !$short_desc)
				continue;

			// Tag, also means done with descriptions
			if( '@' == substr($trimmed_line, 0, 1) ) {

				if( !$did_long_desc )
					$did_long_desc = $did_short_desc = true;

				$line = trim($trimmed_line, '@');
				list($tag, $value) = plugincodex_explode(' ', $line, 2, true);
				$last_tag = $tag;

				if (!isset($tags[$tag]))
					$tags[$tag] = $value;
				elseif (!is_array($tags[$tag]))
					$tags[$tag] = array($tags[$tag], $value);
				else
					$tags[$tag][] = $value;

				continue;
			}

			// Short description
			if( !$did_short_desc ) {

				if( empty($line) ) {

					$did_short_desc = true;
				}
				else {

					$short_desc .= $prepend_short_desc ."\n".$line;
					$prepend_short_desc = ' ';
				}

				continue;
			}

			// Long description
			if( !$did_long_desc ) {

				if( !empty($line) ) {
					$long_desc .= $prepend_long_desc ."\n". $line;
					$prepend_long_desc = ' ';
				}
				 else {

					 $prepend_long_desc = "\n";
				 }

				continue;
			}

			// Additional line for a tag
			if( !empty($line) && !empty($last_tag)  ) {

				if( is_array($tags[$last_tag]) ) {

					end( $tags[$last_tag] );
					$key = key( $tags[$last_tag] );
					$tags[$last_tag][$key] .= ' ' . $line;
				}
				else {

					$tags[$last_tag] .= ' ' . $line;
				}

				continue;
			}
		}
		
		/* Replace inline {@link} */
		$long_desc = preg_replace_callback(
		        	    "/{@link ([^}]*)}/i",
			            array(__CLASS__,'inline_make_clickable'),
			            $long_desc);		

		return compact( 'short_desc', 'long_desc', 'tags' );
	}

	function inline_make_clickable( $replace ){
		$replace = explode(' ', $replace[1]);
		$url = array_shift($replace);
		$description = implode(' ', $replace);

		if( !$description )
			$description = $url;

		return sprintf('<a href="%s">%s</a>', $url, $description);
	}


	/**
	 * Parses parameters from Reflection.
	 *
	 * @param array $params of ReflectionParameter objects
	 *
	 * @return array of parameters' properties
	 */
	static function parse_params($params) {

		$output = array();

		foreach( $params as $param ) {
			/**
			 * @var ReflectionParameter $param
			 */
			$name = trim($param['name'], '$');
			$append = array(
				'name' => $name,
				'has_default' => isset($param['defaultValue']),
				'optional' =>  isset($param['defaultValue']) ? 'optional' : 'required',
			);

			if( $append['has_default'] )
				$append['default'] = $param['defaultValue'];

			$output[$name] = $append;
		}

		return $output;
	}

	/**
	 * Merges parameter information, obtained from Reflection and PHPDoc.
	 *
	 * @param array $from_params info from Reflection
	 * @param array $from_tags info from PHPDoc
	 *
	 * @return array merged info
	 */
	static function merge_params( $from_params, $from_tags ) {

		foreach ($from_tags as $param) {

			list($type, $name, $description) = plugincodex_explode(' ', $param, 3, '');
			$name = trim($name, '$');

			if( !isset($from_params[$name]) )
				continue;

			if (!empty($type))
				$from_params[$name]['type'] = $type;

			if (!empty($description))
				$from_params[$name]['description'] = $description;
		}

		return $from_params;
	}

	static function get_versions() {

		usort(self::$versions, 'version_compare');

		return self::$versions;
	}

	static function get_paths() {

		sort(self::$paths);

		return self::$paths;
	}
}
