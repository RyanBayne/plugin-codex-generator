<?php

function plugincodex_get_functions( $args=array() ){

	$plugin = get_option('plugin-codex_plugin');

	$args = array_merge( array(
		'functions__in'=>false,
	),$args);

	$query = new PCG_Function_Query($args);
	return $query->get_results();
}

function plugincodex_get_hooks( $args=array() ){

	$plugin = get_option('plugin-codex_plugin');
	$query = new PCG_Hook_Query($args);
	return $query->get_results();
}


class PCG_Hook_Query{

	public $args;
	public $count;
	public $paths=array();

	function __construct($args=array()) {
		$this->args = wp_parse_args($args, array(
											'type'=>false,
											 'path' => false,
											'hooks__in'=>array(),
											 'orderby' => 'name',
											 'order' => 'asc',
											 'number' => -1,
											 'offset' => 0,
										   ));

		if( !empty($this->args['type']) )
			$this->args['type'] = ( strtolower($args['type']) == 'action' ? 'action' : 'filter' );
	}


	function get_results() {
		$args = $this->args;
		extract($this->args);

		/* Get plugin fiels */
		$plugin = get_option('plugin-codex_plugin');
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		$files = get_plugin_files($plugin);

		/* Filter plug-in files */
		if( $args['path'] ){
			foreach( $files as $i => $file ){
				if( strpos($file, $args['path']) !== 0 )
					unset($files[$i]);
			}
		}

		//Get absolute paths to file
		$files = apply_filters('plugincodex_relative_plugin_paths', $files, $plugin, $args);
		$files = array_filter( plugincodex_get_plugin_path($files) ,'plugincodex_filter_php_file');
		$files = apply_filters('plugincodex_absolute_plugin_paths', $files, $plugin, $args);
			
		//Parse files
		$results =array();
		$parse = new PCG_Hook_Parser();
		foreach( $files as $file ){
			$parse->parse_file($file);
		}

		$results = $parse->hooks;

		//Filter results
		$results = array_filter($results, array(&$this,'array_filter'));

		//Collect data
		$this->count = count($results);
		foreach( $results as $hook ){
			$this->paths = array_merge($this->paths, wp_list_pluck($hook->location,'path'));
		}
		$this->paths = array_unique($this->paths);
		$this->paths = array_map('plugincodex_sanitize_path',$this->paths);

		//Sort
		usort($results, array(&$this, 'usort'));

		if ('desc' == $args['order'])
			$results = array_reverse($results);

		//Limit query
		if ($args['number'] > 0)
			$results = array_slice($results, $args['offset'], $args['number']);

		return $results;
	}

	function array_filter($hook) {
		$args = $this->args;
		if( !empty($args['hooks__in']) && !in_array($hook->name, $args['hooks__in']) ){
			return false;
		}

		if( !empty($args['type']) && $hook->type !=$args['type'] ){
			return false;
		}
		return true;
	}

	function usort($first, $second) {

		switch( $this->args['orderby'] ) {

			case 'name':
				return strcmp($first->name, $second->name);
			break;

			case 'arguments':
				if( count($first->arguments) == count($second->arguments) )
					return 0;
	
				return count($first->arguments) > count($second->arguments) ? 1 : -1;
			break;
		}

		return 0;
	}

}



class PCG_Function_Query {

	static $user_functions;

	public $args;
	public $count;

	function __construct($args=array()) {

		if( !empty($args['version_compare']) )
			$args['version_compare'] = self::sanitize_compare($args['version_compare']);

		$this->args = wp_parse_args($args, array(
											 's' => false,
											 'match' => 'fuzzy',
											 'version' => false,
											 'version_compare' => '=',
											 'path' => false,
											'functions__in'=>array(),
											 'orderby' => 'name',
											 'order' => 'asc',
											 'number' => -1,
											 'offset' => 0,
											 'return' => 'name',
											'skip_private'=>true,
										   ));
	}


	function get_results() {
		$args = $this->args;
		extract($this->args);

		/* Get plugin fiels */
		$plugin = get_option('plugin-codex_plugin');
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		$files = get_plugin_files($plugin);

		/* Filter plug-in files */
		if( $args['path'] ){
			foreach( $files as $i => $file ){
				if( strpos($file, $args['path']) !== 0 )
					unset($files[$i]);
			}
		}

		//Get absolute paths to file
		$files = apply_filters('plugincodex_relative_plugin_paths', $files, $plugin, $args);
		$files = array_filter( plugincodex_get_plugin_path($files) ,'plugincodex_filter_php_file');
		$files = apply_filters('plugincodex_absolute_plugin_paths', $files, $plugin, $args);
			
		$results =array();
		foreach( $files as $file ){
			$new_results = $this->parse_file_functions($file);
			if( $new_results )
				$results = array_merge($results, $new_results );
		}

		$this->paths = wp_list_pluck( $results, 'path');
		$results = array_filter($results, array(&$this,'array_filter'));

		usort($results, array(&$this, 'usort'));

		$this->count = count($results);

		if ('desc' == $args['order'])
			$results = array_reverse($results);

		if ($args['number'] > 0)
			$results = array_slice($results, $args['offset'], $args['number']);

		return $results;
	}


	function parse_file_functions( $file ){
		extract($this->args);
		try{
			$reflect = new PHP_Reflect();
			$reflect->scan($file);
		    	$this->reflect = $reflect;
			$_functions = $reflect->getFunctions();
			$this->page_package = $this->get_page_package();
		} catch (Exception $e) {
			    continue;
		}

		if( !$_functions )
			return array();

		$_functions = array_pop($_functions);

		$functions =array();
		foreach( $_functions as $name => $_function ){
			if( !empty($functions__in) && !in_array($name, $functions__in) ){
				continue;
			}

			if( isset($functions[$name]) )
				continue;

			$function = self::parse_function($_function);
			$function->name = $name;
			$functions[$name]  = $function;
		}
		return $functions;
	}

	function get_page_package(){

			//Hack to get page-level doc: reflect::tokens has been changed from protected to achieve this
			$tokens = $this->reflect->tokens;

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
				$result = array(
			            'fullPackage' => '',
			            'package'     => '',
			            'subpackage'  => ''
				);
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
				$value = plugincodex_sanitize_version($value);
			break;

		endswitch;

		return $value;
	}


	function parse_function( $_function ){
		$function = new PCG_Function();
		$function->path = isset($_function['file']) ? plugincodex_sanitize_path($_function['file']): false;
		$function->line = absint($_function['startLine']);

		/* Parse the docblock and merge into details from PHPReflect */
		$doc = $_function['docblock'];
		$doc = Codex_Generator_Phpdoc_Parser::parse_doc($doc);
		$function->has_doc = !empty($doc);
		$function->doc = $doc;
		$function->long_desc = isset($doc['long_desc']) ? $doc['long_desc'] : '';
		$function->short_desc = isset($doc['short_desc']) ? $doc['short_desc'] : '';

		/* Tags */
		$tags = array('package','since','see','uses','used-by','link');
		foreach( $tags as $tag ){
			if( isset($function->doc['tags'][$tag] ) ){
				$_tag = str_replace('-','_', $tag);
				$function->{$_tag} = $this->parse_tag($tag, $function->doc['tags'][$tag] );
			}
		}

		/* Parse params from PHPReflect and merge with details from the docblock */
		$params = Codex_Generator_Phpdoc_Parser::parse_params( $_function['arguments'] );
		$function->parameters = Codex_Generator_Phpdoc_Parser::merge_params( $params, $doc['tags']['param']);		


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
				       
			//Note: $output['tags']['deprecated'] will be TRUE if @deprecated tag is present but has no value
			$value = is_string($doc_tags['deprecated']) ? $doc_tags['deprecated'] : false;
			list($version, $description) = plugincodex_explode(' ', $value, 2, false);
			
			if( $version )
				$version = plugincodex_sanitize_version($version);

			$function->deprecated = compact('version','description','replacement');
		}

		return $function;		
	}


	function get_names() {

		$functions =  plugincodex_get_functions();

		foreach( $functions as $key => $function )
			if( false === strpos($function['name'], $this->args['s']) )
				unset($functions[$key]);

		return array_slice(wp_list_pluck($functions,'name'), 0, $this->args['number']);
	}

	function get_array( $function ) {

		return Codex_Generator_Phpdoc_Parser::parse($function);
	}

	function array_filter($function) {

		if( !empty( $this->args['s'] ) ){
			if ('fuzzy' == $this->args['match']) {

				if( false === strpos($function->name, $this->args['s']) )
					return false;
			}
			else {
				
				if( 0 !== strpos($function->name, $this->args['s']) );
					return false;
			}
		}

		if( !empty($this->args['functions__in']) ){
			if( !in_array($function->name, $this->args['functions__in']) ){
				return false;
			}
		}


		if( !empty($this->args['skip_private']) ){
			if( !empty($function->doc['tags']['access']) && 'private' == $function->doc['tags']['access'] )
				return false;
		}

		if( isset($function->doc['tags']['ignore']) )
			return false;

		if( !empty($this->args['version']) )
			if( empty($function['tags']['since']) )
				return false;
			else
				return version_compare($function['tags']['since'], $this->args['version'],  $this->args['version_compare']);

		if( !empty($this->args['path']) ){
			if( 0 !== strpos($function->path, $this->args['path']) )
				return false;
		}

		if( 'version' == $this->args['orderby'] && empty($function->doc['tags']['since']) )
			return false;

		return true;
	}


	function usort($first, $second) {

		switch( $this->args['orderby'] ) {

			case 'name':
				return strcmp($first->name, $second->name);
			break;

			case 'match':
				$pos_first = strpos($first->name, $this->args['s']);
				$pos_second = strpos($second->name, $this->args['s']);

				if ($pos_first != $pos_second)
					return $pos_first > $pos_second ? 1 : -1;

				return strcmp($first->name, $second->name);

			break;

			case 'version':
				return version_compare($first->doc['tags']['since'], $second->doc['tags']['since']);
			break;

			case 'file':
				return strcmp($first->path, $second->path);
			break;
		}

		return 0;
	}
}


class PCG_Hook_Parser{

	var $pos = 0;
	var $hooks = array();

	function parse_file( $file ){
		$this->pos=0;
		$this->tokens = token_get_all(file_get_contents($file));
		$this->file = $file;
		$total = count($this->tokens);

		while( $this->pos < $total ){
			$token = $this->tokens[$this->pos];
			if( is_array($token) ){
				$t = (int) $token[0];

				if( token_name($t) == 'T_STRING' && ( 'do_action' === $token[1] || 'apply_filters' === $token[1] ) ){
					$hook= $this->parse_hook();
					$name = trim($hook->name);
					if( !isset($this->hooks[$name]) ){
						//Make location an array of arrays (hooks can be called more than once)
						$hook->location = array($hook->location);
						$this->hooks[$name] = (object) $hook;
					}else{
						//Hook already recorded, just add location
						$this->hooks[$hook->name]->location[] = $hook->location;
					}
				}
			}
			$this->pos++;
		}
	}

	function parse_hook(){
		$hook= new PCG_Hook();
		/* Get type */
		$token = $this->tokens[$this->pos];

		$hook->type = ( 'do_action' == $token[1] ? 'action' : 'filter' );
		$line = $token[2];
		$path = $this->file;
		$hook->location = compact('line','path');

		$this->pos++;//Left paranthesis

		/* Get name */
		while ( $token != ',' && $token !=')' ){
			$this->pos++;

			$token = $this->tokens[$this->pos];

			if( is_array($token) && token_name($token[0]) == 'T_WHITESPACE' )
				continue;

			if( is_array($token) )
				$hook->name .= $token[1];
			elseif( $token != ',' && $token !=')' )
				$hook->name .= $token;

			if( $token == ')' )
				$this->pos--;
		}
		$hook->name = trim($hook->name ,"'\"'");

		$left_paran = 1;
		$right_paran = 0;
		while ( $left_paran != $right_paran ){
			$this->pos++;

			$token = $this->tokens[$this->pos];

			if( is_array($token) && token_name($token[0]) == 'T_WHITESPACE' )
				continue;

			if( is_array($token) ){
				$type = token_name((int)$token[0]);
			}else{
				$type = $token;
			}

			if( $type == '.' ){
				$hook->arguments[] = '.';

			}elseif( in_array($type, array('T_LNUMBER', 'T_VARIABLE')) ){
				$argument = trim($token[1],'\'"');
				$this->pos++;
				if( is_array($this->tokens[$this->pos]) &&  '->' == $this->tokens[$this->pos][1] ){
					$this->pos++;
					$argument.='->'.$this->tokens[$this->pos][1];
					$this->pos++;
				}
				$this->pos--;
				$hook->arguments[] = esc_html($argument);

			}elseif( 'T_CONSTANT_ENCAPSED_STRING' == $type ){
				$hook->arguments[] = esc_html("'".trim($token[1],'\'"')."'");
	
			}elseif( $type == 'T_ARRAY' ){
				$hook->arguments[] = '$array';//$type;			
				$left_paran_arr = 0;
				$right_paran_arr = 0;
				$this->pos++;
				$_token = $this->tokens[$this->pos];

				if( $_token == '(' )
						$left_paran_arr++;
				
				$k=0;
				while ( $left_paran_arr != $right_paran_arr && $k<50 ){
					$this->pos++;

					$_token = $this->tokens[$this->pos];
					if( $_token == '(' )
						$left_paran_arr++;

					if( $_token == ')' )
						$right_paran_arr++;

					$k++;
				}
			}elseif( $type == 'T_STRING' ){
				//Functions
				$argument = trim($token[1],'\'"');
				$left_paran_arr = 0;
				$right_paran_arr = 0;

				$this->pos++;
				$_token = $this->tokens[$this->pos];

				if( $_token == '(' ){
					$argument .= $_token;
					$left_paran_arr++;
					while ( $left_paran_arr != $right_paran_arr ){
						$this->pos++;

						$_token = $this->tokens[$this->pos];
						if( $_token == '(' )
							$left_paran_arr++;

						if( $_token == ')' )
							$right_paran_arr++;
					}
					$argument .= '...'.$_token;
				}else{
					$this->pos--;
				}

				$hook->arguments[] = trim($argument);
			}

			if( !empty($hook->arguments) ){
				$args = implode(',', $hook->arguments );
				$args = str_replace(',.','.', $args);
				$args = str_replace('.,','.', $args);
				$hook->arguments = explode(',', $args);
			}

			if( $token == '(' )
				$left_paran++;

			if( $token == ')' )
				$right_paran++;
		}
		return $hook;
	}
}
