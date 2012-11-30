<?php

add_action('init', 'plugincodexgen_init',11);
add_action('plugincodexgen_action-generate-documentation','_plugincodexgen_generate_documentation');


function plugincodexgen_init(){
	if( !empty($_REQUEST['plugincodexgen']) && !empty($_REQUEST['plugincodexgen']['action']) ){
		do_action('plugincodexgen_action-'.trim($_REQUEST['plugincodexgen']['action']));
	}
}


function _plugincodexgen_generate_documentation(){

	check_admin_referer('plugincodexgen-bulk-action', '_pcgpnonce');

	/* Set up query */
	$functions__in = isset($_GET['function']) ? $_GET['function'] : array();
	$query['functions__in'] = array_map('trim',$functions__in);
	if( isset($_GET['path']) )
		$query['path'] = $_GET['path'];

	$functions = plugincodex_get_functions($query);	

	foreach ( $functions as $function ){
		$wiki = $function->get_wiki();
		$post_arr = array(
			'post_content'=>plugincodex_markdown($wiki),//Just use $wiki for MD
			'post_type'=>'pcg_function'
		);

		if( $posts = get_posts(array('post_type'=>'pcg_function','post_status'=>'any','name'=>sanitize_title($function->name), 'numberposts'=>1)) ){
			//Updating the page
			$post_arr['ID'] = $posts[0]->ID;
			wp_update_post($post_arr);
			$function->update_meta($post_arr['ID']);
		}else{
			//Creating the page
			$post_arr = array_merge($post_arr,array(
				'post_title'=>$function->name,
				'post_name'=>sanitize_title($function->name),
			));
			$post_id = wp_insert_post($post_arr);	
			$function->update_meta($post_id);
		}
	}

	wp_redirect(add_query_arg(array('functions__in'=>false,'plugincodexgen'=> false,'_pcgpnonce'=>false)));
	exit();
}
