<?php

class Codex_Generator_Phpdoc_Parser {

	static $arrays = array();
	static $versions = array();
	static $paths = array();

	/**
	 * Parses function information, using PHPDoc and Reflection.
	 *
	 * @param string $function Function name.
	 *
	 * @return boolean|array false is failed or containing descriptions, tags and parameters
	 */
	static function parse($function) {

		if( isset( self::$arrays[$function] ) )
			return self::$arrays[$function];

		$reflect = new ReflectionFunction($function);
		$output = array();
		$output['name'] = $reflect->getName();
		$output['path'] = self::sanitize_path($reflect->getFileName());

		if( !in_array($output['path'], self::$paths) )
			self::$paths[] = $output['path'];

		$output['line'] = absint($reflect->getStartLine());

		$params = self::parse_params( $reflect->getParameters() );
		$doc = $reflect->getDocComment();
		$output['has_doc'] = !empty($doc);
		$doc = self::parse_doc($doc);
		$output = array_merge($output, $doc);
		$output['parameters'] = self::merge_params($params, $doc['tags']['param']);

		if( isset($output['tags']['deprecated']) ){
	        	//If function is superceded by a new one, this should be marked with @see
			if( isset($output['tags']['see']) ){
				$replacement = is_array($output['tags']['see']) ? $output['tags']['see'][0] : $output['tags']['see'];
			}else{
	                	$replacement = false;
			}
       
			//Note: $output['tags']['deprecated'] will be TRUE if @deprecated tag is present but has no value
                	$value = is_string($output['tags']['deprecated']) ? $output['tags']['deprecated'] : false;
                	list($version, $description) = self::explode(' ', $value, 2, false);

                	if( $version )
                	        $version = self::trim_version( self::sanitize_version($version));

                	$output['tags']['deprecated'] = compact('version','description','replacement');
	        }

		if( isset($output['tags']['since']) ) {

			$version = self::trim_version( self::sanitize_version($doc['tags']['since']) );

			if( !empty($version) ) {

				$output['tags']['since'] = $version;

				if( !in_array($version, self::$versions) )
					self::$versions[] = $version;
			}
		}

		self::$arrays[$function] = $output;

		return $output;
	}

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
			$line = trim($line, " *\t{}");

			// Start or end
			if ('/' == $line)
				continue;

			// Empty lines as start
			if (empty($line) && !$short_desc)
				continue;

			// Tag, also means done with descriptions
			if( '@' == substr($line, 0, 1) ) {

				if( !$did_long_desc )
					$did_long_desc = $did_short_desc = true;

				$line = trim($line, '@');
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

					$short_desc .= $prepend_short_desc . $line;
					$prepend_short_desc = ' ';
				}

				continue;
			}

			// Long description
			if( !$did_long_desc ) {

				if( !empty($line) ) {

					if( '-' == substr($line, 0, 1) )
						$prepend_long_desc .= "\n*";

					$long_desc .= $prepend_long_desc . $line;
					$prepend_long_desc = ' ';
				}
				 else {

					 $prepend_long_desc = "\n\n";
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

		return compact( 'short_desc', 'long_desc', 'tags' );
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
