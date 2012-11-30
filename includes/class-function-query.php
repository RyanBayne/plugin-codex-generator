<?php

function plugincodex_get_functions( $args=array() ){

	$plugin = get_option('plugin-codex_plugin');

	$args = array_merge( array(
		'functions__in'=>false,
	),$args);

	$query = new PCG_Function_Query($args);
	
	return $query->get_results();

}

class PCG_Function{

	var $path;
	var $short_desc;
	var $long_desc;
	var $deprecated = false;
	var $since = false;

	public function get_wiki( $markdown=true) {
		$output='';

		/* Deprecated Check */
		if( !empty($this->deprecated) ){

			//Check if deprecated version is given
			if( !empty($function->deprecated['version']) ){
				$output .= sprintf("This function has been **deprecated** since %s. ", $function->deprecated['version']);
			}else{
				$output .= sprintf("This function is **deprecated**.");
			}

			//Check if replacement is given.
			if( isset($function->deprecated['replacement']) ){

				if(  $replacement = get_posts(array('post_type'=>'page','name'=>sanitize_title($function->deprecated['replacement']), 'numberposts'=>1))  ){
					$url = get_permalink($replacement[0]);
					$output .= sprintf("Use [`%s`](%s) instead.\n\n", $function->deprecated['replacement'], $url);					
				}else{
					$output .= sprintf("Use `%s` instead.\n\n", $function->deprecated['replacement']);
				}

			}else{
				$output .= sprintf("No replacement has been specified.\n\n");
			}
		}

		/* Description */
		$output .= $this->compile_wiki_section('## Description ', $this->short_desc, $this->long_desc)."\n";

		/* Usage*/
		$text_params = !empty($this->parameters) ? '$' . implode(', $', array_keys($this->parameters)) : '';
		$output .=  $this->compile_wiki_section('## Usage ', "     <?php {$this->name}( {$text_params} ); ?>     "."\n");

		/* Parameters */
		if( $this->parameters ){
			$output .= "## Parameters \n";
			foreach( $this->parameters as $param ) {

				$type = isset($param['type']) ? plugincodex_type_to_string($param['type'], 'wiki') : '';
				$description = isset($param['description']) ? $param['description'] : '';
				$optional = $param['optional'];

				if( $param['has_default'] )
					$optional .= '|'. plugincodex_value_to_string($param['default']);

				$output .= " * **{$param['name']}** ({$type}) - {$description}. ({$optional})\n";
			}
			$output .= "\n";
		}

		/* Return values */
		if( !empty($this->doc['tags']['return']) ) {

			list( $type, $description ) =plugincodex_padded_explode(' ', $this->doc['tags']['return'], 2, '');
			$type = plugincodex_type_to_string($type, 'wiki');
			$output .="## Return Values \n\n * ({$type}) - {$description}.\n\n";
		}

		/* Since */
		$since = !empty($this->doc['tags']['since']) ? $this->doc['tags']['since'] : false;
		if( !empty($since) ) {
			$output .= "\n";
			if (strlen($since) > 3 && '.0' === substr($since, -2))
				$since = substr($since, 0, 3);

			$output .= $this->compile_wiki_section('## Change Log',"Since: {$since}");
		}

		/* Location */
		$path = str_replace(ABSPATH.'wp-content/plugins/event-organiser/','',$this->path);
		$url = esc_url("https://github.com/stephenh1988/Event-Organiser/tree/master/{$path}");
		if( !empty($this->line) )
			$url .= '#L'.intval($this->line);
		//$output .="## Located \n  This function can be found in [`{$path}`]({$url}) \n";
		$output .="## Located \n  This function can be found in `{$path}` \n";

		return $this->generated_content = $output;
	}


	function compile_wiki_section($title, $content) {

		$items = is_array($content) ? $content : array_slice(func_get_args(), 1);
		$items = array_filter($items);

		if (empty($items))
			return '';

		array_unshift($items, $title);

		return implode("\n\n", $items) . "\n\n";
	}

	/**
	 * Sanitizes function name, replaces spaces with undescores.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	 function sanitize_function_name( $name ) {

		$name = wp_kses($name, array());
		$name = trim( $name, ' ()' );
		$name = str_replace(' ', '_', $name);

		return $name;
	}

	function update_meta( $post_id ){

		$meta = array(
			'path'=>$this->path,
			'line'=>$this->line,
			'short_desc'=>$this->short_desc,
			'long_desc'=>$this->long_desc,
			'parameters'=>$this->parameters,
			'since'=> $this->since,
			'deprecated' => $this->deprecated
		);

		foreach( $meta as $key => $value )
			update_post_meta($post_id,'_plugincodex_'.$key, $value );

		$parts = $this->get_name_parts();

		wp_set_object_terms( $post_id, $parts, 'pcg_stems' );

	}


	function get_name_parts(){
		$parts = explode('_',$this->name);
		//Filter out commen 'non entities'
		$parts  = array_diff($parts, array('the','update','get','delete'));

		$parts = apply_filters('plugincodex_function_name_parts', $parts, $this->name);

		return $parts;
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
											'functions__in'=>false,
										   ));


	}

	function get_results() {
		$args = $this->args;

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
		$files = array_filter( plugincodex_get_plugin_path($files) ,'plugincodex_filter_php_file');
			
		$results =array();
		foreach( $files as $file ){
			$new_results = self::parse_file($file);
			if( $new_results )
				$results = array_merge($results, $new_results );
		}

		$results = array_filter($results, array(&$this,'array_filter'));

		usort($results, array(&$this, 'usort'));

		$this->count = count($results);

		if ('desc' == $args['order'])
			$results = array_reverse($results);

		if ($args['number'] > 0)
			$results = array_slice($results, $args['offset'], $args['number']);

		return $results;
	}

	function parse_file( $file ){
		extract($this->args);
		try{
			$reflect = new PHP_Reflect();
		    	$reflect->scan($file);

		} catch (Exception $e) {
			    continue;
		}

		$_functions = $reflect->getFunctions();

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

	function parse_function( $_function ){
		$function = new PCG_Function();
		$function->path = isset($_function['file']) ? plugincodex_sanitize_path($_function['file']): false;
		$function->line = absint($_function['startLine']);
	

		/* Parse the docblock and merge into details from PHPReflect */
		$doc = $_function['docblock'];
		$doc = Codex_Generator_Phpdoc_Parser::parse_doc($doc);
		$function->has_doc = !empty($doc);
		$function->doc = $doc;
		/* //TODO $function = array_merge($function, $doc); */

		/* Parse params from PHPReflect and merge with details from the docblock */
		$params = Codex_Generator_Phpdoc_Parser::parse_params( $_function['arguments'] );
		$function->parameters = Codex_Generator_Phpdoc_Parser::merge_params( $params, $doc['tags']['param']);		
		//unset($function['tags']['param']);

		/* Handle the @deprecated tag */
		if( isset($function->doc['tags']['deprecated']) ){

			//If function is superceded by a new one, this should be marked with @see
			$replacement = isset($function->doc['tags']['see']) ? trim($function->doc['tags']['see'],'()') : false;
				       
			//Note: $output['tags']['deprecated'] will be TRUE if @deprecated tag is present but has no value
			$value = is_string($function->doc['tags']['deprecated']) ? $function->doc['tags']['deprecated'] : false;
			list($version, $description) = plugincodex_explode(' ', $value, 2, false);
			
			if( $version )
				$version = plugincodex_sanitize_version($version);

			$function->deprecated = compact('version','description','replacement');
		}

		/* Handle the @since tag */
		if( isset($function->doc['tags']['since']) ) {

			$version = plugincodex_sanitize_version($function->doc['tags']['since']);

			if( !empty($version) ) {
				$function->doc['tags']['since'] = $version;
				$function->since = $version;
			}
		}
		Codex_Generator_Phpdoc_Parser::$paths[] = $function->path;
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
