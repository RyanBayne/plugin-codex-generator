<?php

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


class PCG_Hook_Query{

	public $args;
	public $count;
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

		if( !empty( $args['s'] ) ){
			if( false === strpos($hook->name, $args['s']) )
					return false;
		}
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
