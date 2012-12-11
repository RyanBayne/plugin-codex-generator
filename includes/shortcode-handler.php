<?php
/**
 * plugin_codex shortcode handler
 * @since 1.0
 * @ignore
 * @access private
*/
function _plugincodex_handle_shortcode( $atts ){
     extract(shortcode_atts(array(
		'object' => 'function',
		'type'=>false,
		'package' => false,
		'numberposts'=>-1,
     ), $atts));

	$query = array(
		'post_type'=> ( $object == 'function' ? 'pcg_function' : 'pcg_hook' ),
		'orderby'=>'title',
		'order'=>'asc',
		'numberposts'=> $numberposts,		
	);

	if( $package ){
		$query['tax_query'][] = array(
			'taxonomy'=>'pcg_package',
			'terms' => explode(',', $package),
			'field'=>'slug',
		);
	}
	if( $type ){
		$query['tax_query'][] = array(
			'taxonomy'=>'pcg_hook_type',
			'terms' => explode(',', $type),
			'field'=>'slug',
		);
	}

	$functions = get_posts($query);
	if( !$functions )
		return '';

	$html = '<ul>';
	foreach( $functions as $function ){
		if( $type == 'hook' )
			$html .= sprintf('<li> <a href="%s"> %s </a> (%s) </li>', get_permalink($function), get_the_title($function->ID),get_post_meta($function->ID,'_plugincodex_type', true)); 
		else
			$html .= sprintf('<li> <a href="%s"> %s </a> </li>', get_permalink($function), get_the_title($function->ID)); 
	}
	$html .='</ul>';

	return apply_filters('plugincodex_plugin_codex_shortcode', $html, $atts);
}
add_shortcode( 'plugin_codex', '_plugincodex_handle_shortcode' );
