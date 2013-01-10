<?php

class PCG_PHP_Reflect extends PHP_Reflect{

	protected $page_package;


	function __construct($args=array()) {

		$args = array_merge($args, 
				array(
		  		'containers'=>array('wp-hook' => 'wphooks'),
				'properties'=>array('wp-hook'=>array('line', 'docblock', 'arguments', 'hooktype'))
			));
		
		parent::__construct($args);
		$this->parserToken['T_WPHOOK'] = array('PHP_Reflect_Token_WPHOOKS', array($this, 'parseToken'));
    	}

    /**
     * Main Parser
     *
     * @return void
     */
    protected function parse()
    {
        $namespace        = FALSE;
        $namespaceEndLine = FALSE;
        $class            = FALSE;
        $classEndLine     = FALSE;
        $interface        = FALSE;
        $interfaceEndLine = FALSE;
        $trait            = FALSE;
        $traitEndLine     = FALSE;

        foreach ($this->tokens as $id => $token) {

            if ('T_HALT_COMPILER' == $token[0]) {
                break;
            }

            $tokenName  = $token[0];
            $text       = $token[1];
            $line       = $token[2];

            $context = array(
                'namespace' => $namespace,
                'class'     => $class,
                'interface' => $interface,
                'trait'     => $trait,
                'context'   => strtolower(str_replace('T_', '', $tokenName))
            );


		if( $context['context'] == 'string' && ( $text == 'do_action' || $text == 'apply_filters') ){
			$context['context'] = 'wp-hook';
			$tokenName = 'T_WPHOOK';
		}

            switch ($tokenName) {
            case 'T_CLOSE_CURLY':
                if ($namespaceEndLine !== FALSE
                    && $namespaceEndLine == $line
                ) {
                    $namespace        = FALSE;
                    $namespaceEndLine = FALSE;
                }
                if ($classEndLine !== FALSE
                    && $classEndLine == $line
                ) {
                    $class        = FALSE;
                    $classEndLine = FALSE;
                }
                if ($interfaceEndLine !== FALSE
                    && $interfaceEndLine == $line
                ) {
                    $interface        = FALSE;
                    $interfaceEndLine = FALSE;
                }
                if ($traitEndLine !== FALSE
                    && $traitEndLine == $line
                ) {
                    $trait        = FALSE;
                    $traitEndLine = FALSE;
                }
                break;
            default:
                if (isset($this->parserToken[$tokenName])) {
                    $tokenClass = $this->parserToken[$tokenName][0];
                    $token = new $tokenClass($text, $line, $id, $this->tokens);

                    call_user_func_array(
                        $this->parserToken[$tokenName][1],
                        array(&$this, $context, $token)
                    );
                }
                break;
            }

            if ($tokenName == 'T_NAMESPACE') {
                $namespace        = $token->getName();
                $namespaceEndLine = $token->getEndLine();

            } elseif ($tokenName == 'T_USE') {
                if ($class !== FALSE) {
                    // warning: don't set $trait value 
                    $traitEndLine = $token->getEndLine();
                }

            } elseif ($tokenName == 'T_TRAIT') {
                $trait        = $token->getName();
                $traitEndLine = $token->getEndLine();

            } elseif ($tokenName == 'T_INTERFACE') {
                $interface        = $token->getName();
                $interfaceEndLine = $token->getEndLine();

            } elseif ($tokenName == 'T_CLASS') {
                $class        = $token->getName();
                $classEndLine = $token->getEndLine();
            }
        }
    }


    function getFunctions()
    {

	$_functions = parent::getFunctions();

	if( ! $_functions )
		return array();

	$this->page_package = $this->get_page_package();

	//Get rid of namespacing
	$_functions = array_pop($_functions);

	$functions =array();
	foreach( $_functions as $name => $_function ){

		$function = new PCG_Function();
		$function->name = $name;
		$function->path = isset($_function['file']) ? plugincodex_sanitize_path($_function['file']): false;
		$function->line = absint($_function['startLine']);

		/* Parse the docblock (add links, etc) */
		$function->doc = $this->parse_doc($_function['docblock']);
		$function->long_desc = isset($function->doc['long_desc']) ? $function->doc['long_desc'] : '';
		$function->short_desc = isset($function->doc['short_desc']) ? $function->doc['short_desc'] : '';

		/* If the page docbloc has a package, assign it to function by default */
		if( $this->page_package )
			$function->package = $this->page_package;

		/* Tags */
		$tags = array('package','since','see','uses','used-by','link');
		foreach( $tags as $tag ){
			if( isset($function->doc['tags'][$tag] ) ){
				$_tag = str_replace('-','_', $tag);
				$function->{$_tag} = $this->parse_tag($tag, $function->doc['tags'][$tag] );
			}
		}
		
		/* Parse params from PHPReflect and merge with details from the docblock */
		$function->parameters = $this->merge_params( $_function['arguments'], $function->doc['tags']['param']);		

		/* Handle the @deprecated tag */
		if( isset($function->doc['tags']['deprecated']) ){
			$doc_tags = $function->doc['tags'];

			//If function is superceded by a new one, this should be marked with @see
			if( isset($doc_tags['see']) ){
				$replacement = is_array($doc_tags['see']) ? $doc_tags['see'][0] : $doc_tags['see'];
				$replacement = trim($replacement,'()');
			}else{
	                	$replacement = false;
			}
				       
			//Note: $doc_tags['deprecated'] will be TRUE if @deprecated tag is present but has no value
			$value = is_string($doc_tags['deprecated']) ? $doc_tags['deprecated'] : false;
			list($version, $description) = plugincodex_explode(' ', $value, 2, false);
			
			if( $version )
				$version = plugincodex_sanitize_version($version);

			$function->deprecated = compact('version','description','replacement');
		}

		$functions[$name]  = $function;
	}

	return $functions;	
     }


     function getWphooks(){

		$parsed_hooks = parent::getWphooks();
		
		if( !$parsed_hooks ) 
			return array();

		$parsed_hooks = array_pop($parsed_hooks);
		$hooks = array();

		foreach( $parsed_hooks as $name => $_hook ){

			if( isset($hooks[$name]) ){
				$hooks[$name]->location[] = array(
						'path' => $this->filename,
						'line' => $_hook['line'],
				);
	
			}else{
				$hook = new PCG_Hook();
				$hook->name = $name;
				$hook->type = $_hook['hooktype'];

				$hook->doc =$this->parse_doc($_hook['docblock']);
				$hook->long_desc = isset($hook->doc['long_desc']) ? $hook->doc['long_desc'] : '';
				$hook->short_desc = isset($hook->doc['short_desc']) ? $hook->doc['short_desc'] : '';

				foreach( $_hook['arguments'] as $arg => $arg_array ){
					if( !isset($_hook['arguments'][$arg]['name'] ) ){
						if( isset($arg_array['typeHint']) ){
							$_hook['arguments'][$arg]['name'] = '$'.$arg_array['typeHint'].'_'.$arg;
						}else{
							$_hook['arguments'][$arg]['name'] = '$arg_'.$arg;
						}
					}
				}

				/* Parse params from PHPReflect and merge with details from the docblock */
				$hook->parameters = $this->merge_params( $_hook['arguments'], $hook->doc['tags']['param']);		

				$hook->path =$this->filename;
				$hook->line = $_hook['line'];
				$hook->location[] = array(
					'path' => $this->filename,
					'line' => $_hook['line'],
				);

				/* Tags */
				$tags = array('package','since','see','uses','used-by','link');
				foreach( $tags as $tag ){
					if( isset($hook->doc['tags'][$tag] ) ){
						$_tag = str_replace('-','_', $tag);
						$hook->{$_tag} = $this->parse_tag($tag, $hook->doc['tags'][$tag] );
					}
				}
		
				$hooks[$name] = $hook;
			}
		}
	
		return $hooks;		
    	}




	function get_page_package(){
		if( !isset($this->filename) )
			return false;

		//Hack to get page-level doc: reflect::tokens has been changed from protected to achieve this
		$tokens = $this->tokens;

		$docblocs = 0;
		$docComment ='';
		foreach( $tokens as $token ){

			if( 'T_DOC_COMMENT' == $token[0] ){
				if( 0 == $docblocs )
					$docComment = $token[1];
	
			$docblocs++;
		}	
		
		if( 'T_FUNCTION' == $token[0] )
			break;
		}

		if( $docblocs > 1 ){
			$result = array('fullPackage' => '','package'     => '','subpackage'  => '');
			if (preg_match('/@package[\s]+([\.\-\w]+)/', $docComment, $matches)) {
				$result['package']     = $matches[1];
				$result['fullPackage'] = $matches[1];
	        	}

			if (preg_match('/@subpackage[\s]+([\.\-\w]+)/', $docComment, $matches)) {
				$result['subpackage']   = $matches[1];
				$result['fullPackage'] .= '.' . $matches[1];
	       		}
			return $result['package'];		
		}
		return false;
	}


	
	function parse_tag( $tag, $value ){
		if( !is_array($value) )
			$value = array($value);

		switch( $tag ):
			case 'link':
				foreach( $value as $i => $link ){
					$link = explode(' ', trim($link));
					$url = array_shift($link);
					$description = implode(' ',$link);
					if( empty($description ) )
						$description = $url;
					$value[$i] = compact('url','description');
				}
			break;

			case 'package':
				if( empty($value) && $this->page_package )
					$value = $this->page_package;
			break;

			case 'since':
				$value = plugincodex_sanitize_version($value[0]);
			break;

		endswitch;

		return $value;
	}


	/**
	 * Parses PHPDoc
	 *
	 * @param string $doc PHPDoc string
	 * @return array of parsed information
	 */
	function parse_doc($doc) {

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
			//Don't use ltrim - Only want asterix and the following space removed
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

				}else {
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

				}else {
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

				}else {
					$tags[$last_tag] .= ' ' . $line;
				}

				continue;
			}
		}
		
		/* Replace inline {@link} */
		$long_desc = preg_replace_callback("/{@link ([^}]*)}/i", array(__CLASS__,'inline_make_clickable'), $long_desc);	
		$short_desc = preg_replace_callback("/{@link ([^}]*)}/i", array(__CLASS__,'inline_make_clickable'), $short_desc);		

		/* Replace inline {@see} */
		$long_desc = preg_replace_callback("/{@see ([^}]*)}/i",array(__CLASS__,'add_see_link'), $long_desc);
		$short_desc = preg_replace_callback("/{@see ([^}]*)}/i",array(__CLASS__,'add_see_link'),$short_desc);

		return compact( 'short_desc', 'long_desc', 'tags' );
	}


	function add_see_link( $replace ){

		$reference = trim($replace[1], '`');
		$link = plugincodex_find_reference_link($reference);
		if( $link )
			$replace[1] = sprintf('<a href="%s">%s</a>', $link, $replace[1]);

		return $replace[1];
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
	 * Merges parameter information, obtained from PHP_Reflect and PHPDoc.
	 *
	 * @param array $from_params info from PHP_Reflect
	 * @param array $from_tags info from PHPDoc
	 * @return array merged info
	 */
	 function merge_params( $from_params, $from_tags ) {

		$params = array();

		foreach ($from_params as $param) {
			$name = trim($param['name'], '$');

			$append = array(
				'name' => $name,
				'has_default' => isset($param['defaultValue']),
				'optional' =>  isset($param['defaultValue']) ? 'optional' : 'required',
			);

			if( $append['has_default'] )
				$append['default'] = $param['defaultValue'];

			$params[$name] = $append;
		}

		foreach( $from_tags as $param ){
			
			list($type, $name, $description) = plugincodex_explode(' ', $param, 3, '');
			$name = trim($name, '$');

			if( !isset($params[$name]) )
				continue;

			if ( !empty($type) )
				$params[$name]['type'] = $type;

			if ( !empty($description) )
				$params[$name]['description'] = $description;
		}

		return $params;
	}
}

?>
