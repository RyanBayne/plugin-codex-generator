<?php
class PCG_Hook{	

	var $path;
	var $line;
	var $name = false;
	var $arguments=array();
	var $type = false;
	var $location=false;
	var $link = false;

	function get_wiki(){
		$wiki='';

		/* Description */
		/* If WP-MarkDown is activated - parse as MD */
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if( is_plugin_active('WP-MarkDown/wp-markdown.php') && function_exists('wpmarkdown_markdown_to_html') ){
			$this->short_desc = wpmarkdown_markdown_to_html($this->short_desc);
			$this->long_desc = wpmarkdown_markdown_to_html($this->long_desc);
		}
		$wiki .= $this->compile_wiki_section(sprintf('<h3>%s</h3>',__('Description','plugincodexgen')), $this->short_desc, $this->long_desc)."\n";

		/* Usage */
		$wiki .= sprintf('<h3>%s</h3>',__('Usage','plugincodexgen'));

		$function = ( $this->type == 'action' ? 'add_action' : 'apply_filter' );
		$args = count($this->parameters);
		$_param_names = wp_list_pluck($this->parameters,'name');
		$_arguments = esc_html('$'.implode(', $',$_param_names));
		$return = ( $this->type == 'action' ? '' : 'return $'.reset($_param_names).';');

		if( $args ){
			$wiki .= "<pre><code>\n"
				."{$function}('{$this->name}','my_custom_callback',10,$args);\n"
				."function my_custom_callback( {$_arguments} ){\n"
				."	//Callback performs operation\n"
				."	{$return}\n"
				."}\n"
				."</code></pre>";
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

		/* Locations */
		if( $this->location ){
		$wiki .= sprintf('<h3>%s</h3>',__('Location','plugincodexgen'));
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

	function compile_wiki_section($title, $content) {

		$items = is_array($content) ? $content : array_slice(func_get_args(), 1);
		$items = array_filter($items);

		if (empty($items))
			return '';

		array_unshift($items, $title);

		return implode("\n\n", $items) . "\n\n";
	}

}

