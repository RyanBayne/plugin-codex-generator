<?php
function plugincodex_get_function_meta($post_id, $meta_key, $single=false ){
	return get_post_meta($post_id,'_plugincodex_'.$meta_key, $single );
}


class PCG_Function{

	var $path;
	var $short_desc;
	var $long_desc;
	var $deprecated = false;
	var $since = false;
	var $package = false;

	public function get_wiki() {
		$output='';

		/* Deprecated Check */
		if( !empty($this->deprecated) ){

			//Check if deprecated version is given
			if( !empty($function->deprecated['version']) ){
				$output .= sprintf("This function has been <strong>deprecated</strong> since %s. ", $function->deprecated['version']);
			}else{
				$output .= sprintf("This function is <strong>deprecated</strong>.");
			}

			//Check if replacement is given.
			if( isset($function->deprecated['replacement']) ){

				if(  $replacement = get_posts(array('post_type'=>'page','name'=>sanitize_title($function->deprecated['replacement']), 'numberposts'=>1))  ){
					$url = get_permalink($replacement[0]);
					$output .= sprintf("Use <a href='%s'><code>%s</code></a> instead.\n\n", $url, $function->deprecated['replacement']);					
				}else{
					$output .= sprintf("Use <code>%s</code> instead.\n\n", $function->deprecated['replacement']);
				}

			}else{
				$output .= sprintf("No replacement has been specified.\n\n");
			}
		}

		/* Description */
		$output .= $this->compile_wiki_section('<h3>Description</h3>', $this->short_desc, $this->long_desc)."\n";

		/* Usage*/
		$text_params = !empty($this->parameters) ? '$' . implode(', $', array_keys($this->parameters)) : '';
		$output .=  $this->compile_wiki_section('<h3>Usage</h3>', "     <?php {$this->name}( {$text_params} ); ?>     "."\n");

		/* Parameters */
		if( $this->parameters ){
			$output .= "<h3>Parameters</h3> \n";
			$output .= '<ul>';
			foreach( $this->parameters as $param ) {

				$type = isset($param['type']) ? plugincodex_type_to_string($param['type'], 'wiki') : '';
				$description = isset($param['description']) ? trim($param['description'],'.') : '';
				$optional = $param['optional'];

				if( $param['has_default'] )
					$optional .= '|'. plugincodex_value_to_string($param['default']);

				$output .= " <li> <strong>{$param['name']}</strong> ({$type}) - {$description} ({$optional})</li>\n";
			}
			$output .= '</ul>';
			$output .= "\n";
		}

		/* Return values */
		if( !empty($this->doc['tags']['return']) ) {

			list( $type, $description ) =plugincodex_padded_explode(' ', $this->doc['tags']['return'], 2, '');
			$type = plugincodex_type_to_string($type, 'wiki');
			$output .="<h3>Return Values</h3> \n\n  * ({$type}) - {$description}.\n\n";
		}

		/* Since */
		$since = !empty($this->doc['tags']['since']) ? $this->doc['tags']['since'] : false;
		if( !empty($since) ) {
			$output .= "\n";
			if (strlen($since) > 3 && '.0' === substr($since, -2))
				$since = substr($since, 0, 3);

			$output .= $this->compile_wiki_section('<h3> Change Log</h3>',"Since: {$since}");
		}

		/* Location */
		$path = str_replace(ABSPATH.'wp-content/plugins/event-organiser/','',$this->path);
		$url = esc_url("https://github.com/stephenh1988/Event-Organiser/tree/master/{$path}");
		if( !empty($this->line) )
			$url .= '#L'.intval($this->line);
		//$output .="## Located \n  This function can be found in [`{$path}`]({$url}) \n";

		$output .="<h3>Located</h3> \n  This function can be found in `{$path}` \n";

		return $this->generated_content = $output;
	}

	function get_package(){
		return $this->package;
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
?>
