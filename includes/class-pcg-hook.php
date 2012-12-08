<?php
class PCG_Hook{	

	var $path;
	var $line;
	var $name = false;
	var $arguments=array();
	var $type = false;
	var $location=false;

	function get_wiki(){
		$output='';

		/* Usage */
		$output .= '<h3> Usage </h3>';

		$function = ( $this->type == 'action' ? 'add_action' : 'apply_filter' );
		$args = count($this->arguments);
		$_arguments = esc_html(implode(',',$this->arguments));
		$return = ( $this->type == 'action' ? '' : 'return '.$this->arguments[0].';');

		if( $args ){
			$output .= "<pre><code>\n"
				."{$function}('{$this->name}','my_custom_callback',10,$args);\n"
				."function my_custom_callback( {$_arguments} ){\n"
				."	//Callback performs operation\n"
				."	{$return}\n"
				."}\n"
				."</code></pre>";
		}

		/* Locations */
		if( $this->location ){
			$output .= "<h3>Location At</h3> \n";
			$output .= '<ul>';
			foreach( $this->location as $location ) {
				$file = plugincodex_sanitize_path($location['path']);
				$line = !empty($location['line']) ? ' #L'.intval($location['line']) : '';
				$segments = explode('/', $file);
				$_location = implode( ' / ', $segments ).$line;

				$output .= " <li> {$_location} </li>\n";
			}
			$output .= '</ul>';
			$output .= "\n";
		}

		return $this->generated_content = $output;
	}
}

