<?php

class PCG_Query {

	public $args;
	public $count;

	function get_results() {
		return array();
	}

	function get_files_to_parse(){

		/* Get plugin files */
		$plugin = get_option('plugin-codex_plugin');
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		$files = get_plugin_files($plugin);

		/* Filter plug-in files */
		if( $this->args['path'] ){
			foreach( $files as $i => $file ){
				if( strpos($file, $this->args['path']) !== 0 )
					unset($files[$i]);
			}
		}

		//Get absolute paths to file
		$files = apply_filters('plugincodex_relative_plugin_paths', $files, $plugin, $this->args);
		$files = array_filter( plugincodex_get_plugin_path($files) ,'plugincodex_filter_php_file');
		$files = apply_filters('plugincodex_absolute_plugin_paths', $files, $plugin, $this->args);

		return $files;
	}


	function array_filter($object) {

		if( !empty( $this->args['s'] ) ){
			if( false === strpos($object->name, $this->args['s']) )
				return false;
		}

		if( isset($object->doc['tags']['ignore']) ){
			return false;
		}

		if( !empty($this->args['skip_private']) ){
			if( !empty($object->doc['tags']['access']) && 'private' == $object->doc['tags']['access'] )
				return false;
		}


		/* Functin specific */
		if( !empty($this->args['functions__in']) && !in_array($object->name, $this->args['functions__in']) ){
				return false;
		}

		if( !empty($this->args['package'])  && $object->package != trim($this->args['package']) ){
				return false;
		}

		if( !empty($this->args['version']) ){
			if( empty($function->since) )
				return false;
			else
				return version_compare($object->since, $this->args['version']) == 0;
		}

		if( !empty($this->args['path']) ){
			if( 0 !== strpos($object->path, $this->args['path']) )
				return false;
		}

		if( 'version' == $this->args['orderby'] && empty($object->doc['tags']['since']) )
			return false;

		/* Hook specific */
		if( !empty($this->args['hooks__in']) && !in_array($object->name, $this->args['hooks__in']) ){
			return false;
		}

		if( !empty($this->args['type']) && $object->type !=$this->args['type'] ){
			return false;
		}

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

			case 'arguments':
				if( count($first->arguments) == count($second->arguments) )
					return 0;
	
				return count($first->arguments) > count($second->arguments) ? 1 : -1;
			break;
		}

		return 0;
	}
}

/**
 * Retrieves functions parsed from the plug-in files. This does not query the generated function pages.
 *
 * A wrapper for PCG_Function_Query. The query array can contain:
 *
 * * 's' => To search by name
 * * 'package'=> (string) Functions belonging to some package
 * * 'path' => To filter by file path
 * * 'version' => To filter by version
 * * 'functions__in' => (array) To filter by function name
 * * 'orderby' => Choose an order criteria (name | version | file ).
 * * 'order' => asc|des
 * * 'number' => The number of functions to retrieve. Default -1 (all)
 * * 'offset' => The offset (used for pagination). Default: 0
 * * 'skip_private' => Whether to skip functions with @access private. Default true.
 *
 * @since 1.0
 * 
 * @args array Query array
 * @return array Array of PCG_Function objects
 */
function plugincodex_get_functions( $args=array() ){

	$plugin = get_option('plugin-codex_plugin');

	$args = array_merge( array(
		'functions__in'=>false,
	),$args);

	$query = new PCG_Function_Query($args);
	return $query->get_results();
}


class PCG_Function_Query extends PCG_Query {

	function __construct($args=array()) {

		if( !empty($args['version_compare']) )
			$args['version_compare'] = self::sanitize_compare($args['version_compare']);

		$this->args = wp_parse_args($args, array(
											 's' => false,
											 'match' => 'fuzzy',
											 'version' => false,
											 'path' => false,
											'functions__in'=>array(),
											 'orderby' => 'name',
											 'order' => 'asc',
											 'number' => -1,
											 'offset' => 0,
											 'return' => 'name',
											'skip_private'=>true,
										   ));

		if( $this->args['version'] )
			$this->args['version'] = plugincodex_sanitize_version($this->args['version']);
	}


	function get_results() {
		$args = $this->args;
		$files = $this->get_files_to_parse();
			
		$results =array();
		foreach( $files as $file ){
			$results = array_merge($results, $this->parse_file_functions($file) );
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
			$reflect = new PCG_PHP_Reflect();
			$reflect->scan($file);
		    	$this->reflect = $reflect;
			$_functions = $reflect->getFunctions();
		} catch (Exception $e) {
			    return array();
		}

		if( !$_functions )
			return array();

		return $_functions;
	}
}

/**
 * Retrieves hooks parsed from the plug-in files. This does not query the generated hook pages.
 *
 * A wrapper for PCG_Hook_Query. The query array can contain:
 *
 * * 's' => To search by name
 * * 'type' => To filter by action | filter
 * * 'path' => To filter by file path
 * * 'hooks__in' => (array) To filter by hook name
 * * 'orderby' => Choose an order criteria (name | version | file ).
 * * 'order' => asc|des
 * * 'number' => The number of functions to retrieve. Default -1 (all)
 * * 'offset' => The offset (used for pagination). Default: 0
 *
 * @since 1.0
 * 
 * @args array Query array
 * @return array Array of PCG_Hook objects
 */
function plugincodex_get_hooks( $args=array() ){

	$plugin = get_option('plugin-codex_plugin');
	$query = new PCG_Hook_Query($args);
	return $query->get_results();
}


class PCG_Hook_Query extends PCG_Query{

	public $paths=array();

	function __construct($args=array()) {
		$this->args = wp_parse_args($args, array(
											's'=> false,
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

		$files = $this->get_files_to_parse();
			
		//Parse files
		$hooks=array();

		foreach( $files as $file ){

			$reflect = new PCG_PHP_Reflect();
			$reflect->scan($file);
			$parsed_hooks = $reflect->getWphooks();
			if( !$parsed_hooks ) 
				continue;

			foreach( $parsed_hooks as $name => $hook ){
				if( isset($hooks[$name]) ){
					$_hook = $hooks[$name];
					$_hook->location = array_merge($_hook->location, $hook->location);
					$hooks[$name] =$_hook;

				}else{
					$hooks[$name] = $hook;
				}
			}
		}

		//Filter results
		$hooks = array_filter($hooks, array(&$this,'array_filter'));
		$hooks = apply_filters('plugincodex_filter_hook_results',$hooks, $args);

		//Collect data
		$this->count = count($hooks);
		foreach( $hooks as $hook ){
			$this->paths = array_merge($this->paths, wp_list_pluck($hook->location,'path'));
		}
		$this->paths = array_unique($this->paths);
		$this->paths = array_map('plugincodex_sanitize_path',$this->paths);

		//Sort
		usort($hooks, array(&$this, 'usort'));

		if ('desc' == $args['order'])
			$hooks = array_reverse($hooks);

		//Limit query
		if ($args['number'] > 0)
			$hooks = array_slice($hooks, $args['offset'], $args['number']);

		return $hooks;
	}
}
