<?php

/**
 * Registers scripts & styles for shortcode
 * @since 1.0.7
 * @ignore
 * @access private
*/
function _plugincodexgen_register_script(){
	$ver = '1.0.7';
	wp_register_script('pcg-search',PLUGIN_CODEX_GENERATOR_URL.'/includes/search.js',array('jquery','jquery-ui-autocomplete'),$ver);
	wp_register_style('pcg-search',PLUGIN_CODEX_GENERATOR_URL.'/includes/search.css',array(),$ver);
}
add_action('wp_enqueue_scripts','_plugincodexgen_register_script');


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


add_shortcode( 'plugin_codex_search', '_plugincodex_handle_search_shortcode' );
function _plugincodex_handle_search_shortcode( $atts ){
	$html =sprintf('<form method="post">
					<p><input type="text" class="pcg-search" name="pcg-search-term" placeholder="Search for function or hook" value="%s"> 
						<input type="submit" name="pcg-search-submit" value="Search"></p></form>',
					(isset($_POST['pcg-search-term']) ? esc_attr($_POST['pcg-search-term']) : '' )
				);

	if( isset($_POST['pcg-search-term']) ):

		$html .= '<div id="#pcg-eo-booking">';

		$term = trim($_POST['pcg-search-term']);

		$query = new WP_Query(array(
			'post_type'=>array('pcg_function', 'pcg_hook'),
			'order'=>'asc',
			's'=>$term,
			'numberposts'=> 15,		
		));

		$results = array();
		$html .= '<h3 id="pcg-search-results"> Results </h3>';
		if( $query->have_posts() ):
			$html .= '<ul>';
			while( $query->have_posts() ): $query->the_post();

				switch( get_post_type() ):
					case 'page':
						$category = 'Page';
					break;
					case 'post':
						$category = 'Post';
					break;
					case 'pcg_hook':
						$category = 'Hook';
					break;
					case 'pcg_function':
						$terms = get_the_terms( get_the_ID(), 'pcg_package');
						if( $terms ){
							$package = array_pop($terms);
							$category = $package->name;
						}
					break;
					default:
						$category ='';
				endswitch;
				$html .= sprintf('<li> %s: <strong><a href="%s"> %s </a></strong></li>',ucfirst($category),get_permalink(), get_the_title());
			endwhile;
			$html .= '</ul>';
		else:
			$html .= '<p> No results found </p>';
		endif;
		wp_reset_postdata();

		$html .='</div>';
	endif;

	wp_enqueue_script('pcg-search');
	wp_enqueue_style('pcg-search');
	wp_localize_script('pcg-search','plugincodexgen',array('ajax_url'=>admin_url('admin-ajax.php')));
	return $html;
}
