<?php

/**
 * A wrapper for get_post_meta, prefixes key with _plugincodex_
 *
 * Dispite the name this is used to retrieve meta for hooks as well as functions
 *
 * @since 1.0
 * @uses get_post_meta()
 * @link http://codex.wordpress.org/Function_Reference/get_post_meta
 * 
 * @param int $post_id Post ID.
 * @param string $key (without the prefix '_plugincodex_')
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
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
	var $link = false;
	var $see = false;
	var $uses =false;
	var $used_by =false;

	public function get_wiki() {
		$wiki='';

		/* Deprecated Check */
		if( !empty($this->deprecated) ){

			//Check if deprecated version is given
			if( !empty($this->deprecated['version']) ){
				$wiki .= sprintf(__("This function has been <strong>deprecated</strong> since %s.",'plugincodexgen'), $this->deprecated['version']);
			}else{
				$wiki .= sprintf(__("This function is <strong>deprecated</strong>.",'plugincodexgen'));
			}

			//Check if replacement is given.
			if( isset($this->deprecated['replacement']) ){
				//Replacement given, try to find a link to this function's page
				if(  $replacement = get_posts(array('post_type'=>'pcg_function','name'=>sanitize_title($this->deprecated['replacement']), 'numberposts'=>1))  ){
					$url = get_permalink($replacement[0]);
					$wiki .= ' '.sprintf(__("Use <a href='%s'><code>%s</code></a> instead",'plugincodexgen')."\n\n", $url, $this->deprecated['replacement'].'()');					
				}else{
					$wiki .= ' '.sprintf(__("Use <code>%s</code> instead",'plugincodexgen')."\n\n", $this->deprecated['replacement'].'()');
				}

			}else{
				$wiki .= sprintf(__("No replacement has been specified.",'plugincodexgen')."\n\n");
			}
		}

		/* Description */
		$wiki .= $this->compile_wiki_section(sprintf('<h3>%s</h3>',__('Description','plugincodexgen')), $this->short_desc, $this->long_desc)."\n";

		/* Usage*/
		$text_params = !empty($this->parameters) ? '$' . implode(', $', array_keys($this->parameters)) : '';
		$wiki .=  $this->compile_wiki_section(sprintf('<h3>%s</h3>',__('Usage','plugincodexgen')), '<pre><code>'.esc_html("     <?php {$this->name}( {$text_params} ); ?>     ")."\n".'</code></pre>');

		/* Parameters */
		if( $this->parameters ){
			$wiki .= sprintf('<h3>%s</h3>',__('Parameters','plugincodexgen'))." \n";
			$wiki .= '<ul>';
			foreach( $this->parameters as $param ) {

				$type = isset($param['type']) ? plugincodex_type_to_string($param['type'], 'wiki') : '';
				$description = isset($param['description']) ? trim($param['description'],'.') : '';
				$optional = $param['optional'];

				if( $param['has_default'] )
					$optional .= '|'. plugincodex_value_to_string($param['default']);

				$wiki .= " <li> <strong>{$param['name']}</strong> ({$type}) - {$description} ({$optional})</li>\n";
			}
			$wiki .= '</ul>';
			$wiki .= "\n";
		}

		/* @see / @uses / @used-by */
		if( $this->see || $this->uses || $this->used_by ){
			$wiki .= sprintf('<h3>%s</h3>',__('See','plugincodexgen'))." \n";
			$wiki .= '<ul>';

			$types = array('see'=>__('See','plugincodexgen'),'uses'=>__('Uses','plugincodexgen'),'used_by'=>__('Used By','plugincodexgen'));
			foreach( $types as $type => $label ){

				if( !$this->{$type} )
					continue;

				foreach( $this->{$type} as $reference) {
					$link = false;

					if( $reference != rtrim($reference, '()') ){
						//Function / method - try to get link
						if( $reference_post = get_posts(array('post_type'=>'pcg_function','name'=>sanitize_title(rtrim($reference, '()')), 'numberposts'=>1)) )
							$link = get_permalink($reference_post[0]);
					}
					
					if( $link )
						$wiki .= sprintf("<li> %s: <a href='%s'><code>%s</code></a></li>\n",$label, $link, esc_html($reference));
					else
						$wiki .= sprintf("<li> %s: <code>%s</code></li>\n", $label, esc_html($reference));
				}
			}

			$wiki .= '</ul>';
			$wiki .= "\n";
		}


		/* @link */
		if( $this->link ){
			$wiki .= sprintf('<h3>%s</h3>',__('Resources','plugincodexgen'))." \n";
			$wiki .= '<ul>';
			foreach( $this->link as $link ) {
				$wiki .= sprintf("<li><a href='%s'>%s</a></li>\n",esc_url($link['url']), esc_html($link['description']));
			}
			$wiki .= '</ul>';
			$wiki .= "\n";
		}

		/* Return values */
		if( !empty($this->doc['tags']['return']) ) {

			list( $type, $description ) =plugincodex_padded_explode(' ', $this->doc['tags']['return'], 2, '');
			$type = plugincodex_type_to_string($type, 'wiki');
			$wiki .="<h3>Return Values</h3> \n\n  <ul><li> ({$type}) - {$description} </li></ul>\n\n";
		}

		/* Since */
		$since = !empty($this->doc['tags']['since']) ? $this->doc['tags']['since'] : false;
		if( !empty($since) ) {
			$wiki .= "\n";
			if (strlen($since) > 3 && '.0' === substr($since, -2))
				$since = substr($since, 0, 3);

			$wiki .= $this->compile_wiki_section(sprintf('<h3>%s</h3>',__('Change Log','plugincodexgen')),__("Since:",'plugincodexgen')." {$since}");
		}

		/* Location */
		$path = $this->path;
		if( !empty($this->line) )
			$path .= ' ('.__('line:').' '.$this->line.')';

		$wiki .= sprintf('<h3>%s</h3>',__('Location','plugincodexgen'))." \n";
		$wiki .=sprintf(__("This function can be found in <code>%s</code>",'plugincodexgen'), $path)." \n";

		return $this->generated_content = $wiki;
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
