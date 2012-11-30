<?php
 /*
* add's custom taxonomies (categories and tags) and then custom post type 'event'.
*/ 

//Register the custom taxonomy Event-category
add_action( 'init', 'plugincodexgen_create_custom_types', 10 );
function plugincodexgen_create_custom_types() {

  	$labels = array(
		'name' => __('Functions','plugincodexgen'),
		'singular_name' => __('Function','plugincodexgen'),
		'add_new' => _x('Add New','post'),
		'add_new_item' => __('Add New Function','plugincodexgen'),
		'edit_item' =>  __('Edit Function','plugincodexgen'),
		'new_item' => __('New Function','plugincodexgen'),
		'all_items' =>__('All Function','plugincodexgen'),
		'view_item' =>__('View Function','plugincodexgen'),
		'search_items' =>__('Search functions','plugincodexgen'),
		'not_found' =>  __('No functions found','plugincodexgen'),
		'not_found_in_trash' =>  __('No functions found in Trash','plugincodexgen'),
		'parent_item_colon' => '',
		'menu_name' => __('Plug-in Docs','plugincodexgen'),
  	);

	if( !plugincodexgen_get_option('prettyurl') ){
		$ticket_rewrite = false;
	}else{
		$ticket_rewrite = array( 'slug' =>plugincodexgen_get_option('prettyurl'), 'with_front' => false,'feeds'=> true,'pages'=> true );
	}

	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'query_var' => true,
		//'capability_type' => 'tracticket',
		'rewrite' => $ticket_rewrite,
		/*'capabilities' => array(
			'publish_posts' => 'publish_tracticket',
			'edit_posts' => 'edit_tractickets',
			'edit_others_posts' => 'edit_others_tractickets',
			'delete_posts' => 'delete_events',
			'delete_others_posts' => 'delete_others_tractickets',
			'read_private_posts' => 'read_private_tractickets',
			'edit_post' => 'edit_tracticket',
			'delete_post' => 'delete_tracticket',
			'read_post' => 'read_tracticket',
		),*/
		'has_archive' => true,
		'hierarchical' => false,
		'menu_position' => apply_filters('plugincodexgen_menu_position',16),
		'supports' => plugincodexgen_get_option('supports'),
	  ); 	
	
	register_post_type('pcg_function', apply_filters('plugincodexgen_register_pcg_function', $args) );

  	$labels = array(
    		'name' => _x( 'Function Stems', 'taxonomy general name' ),
    		'singular_name' => _x( 'Stem', 'taxonomy singular name' ),
    		'search_items' =>  __( 'Search Stems' ),
    		'all_items' => __( 'All Stems' ),
    		'edit_item' => __( 'Edit Stem' ), 
    		'update_item' => __( 'Update Stem' ),
    		'add_new_item' => __( 'Add New Stem' ),
    		'new_item_name' => __( 'New Stem Name' ),
    		'menu_name' => __( 'Stem' ),
    	);
	register_taxonomy('pcg_stems',array('pcg_function'), array(
    		'hierarchical' => false,
		'labels' => $labels,
    		'show_ui' => true,
  	));

	
}
