<?php
class PCG_Hook{	

	var $path;
	var $line;
	var $name = false;
	var $arguments=array();
	var $type = false;
	var $location=false;

	function get_wiki(){
		$wiki='';

		/* Usage */
		$wiki .= '<h3> Usage </h3>';

		$function = ( $this->type == 'action' ? 'add_action' : 'apply_filter' );
		$args = count($this->arguments);
		$_arguments = esc_html(implode(',',$this->arguments));
		$return = ( $this->type == 'action' ? '' : 'return '.$this->arguments[0].';');

		if( $args ){
			$wiki .= "<pre><code>\n"
				."{$function}('{$this->name}','my_custom_callback',10,$args);\n"
				."function my_custom_callback( {$_arguments} ){\n"
				."	//Callback performs operation\n"
				."	{$return}\n"
				."}\n"
				."</code></pre>";
		}

		/* Locations */
		if( $this->location ){
			$wiki .= "<h3>Location At</h3> \n";
			$wiki .= '<ul>';
			foreach( $this->location as $location ) {
				$file = plugincodex_sanitize_path($location['path']);
				$line = !empty($location['line']) ? ' #L'.intval($location['line']) : '';
				$segments = explode('/', $file);
				$_location = implode( ' / ', $segments ).$line;

				$wiki .= " <li> {$_location} </li>\n";
			}
			$wiki .= '</ul>';
			$wiki .= "\n";
		}

		return $this->generated_content = $wiki;
	}
}

