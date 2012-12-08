<?php
 /*
* add's custom taxonomies (categories and tags) and then custom post type 'event'.
*/ 

//Register the custom post post types & taxonomies
add_action( 'init', 'plugincodexgen_create_custom_types', 10 );
function plugincodexgen_create_custom_types() {

  	$labels = array(
		'name' => __('Functions','plugincodexgen'),
		'singular_name' => __('Function','plugincodexgen'),
		'add_new' => _x('Add New','post'),
		'add_new_item' => __('Add New Function','plugincodexgen'),
		'edit_item' =>  __('Edit Function','plugincodexgen'),
		'new_item' => __('New Function','plugincodexgen'),
		'all_items' =>__('Function Pages','plugincodexgen'),
		'view_item' =>__('View Function','plugincodexgen'),
		'search_items' =>__('Search functions','plugincodexgen'),
		'not_found' =>  __('No functions found','plugincodexgen'),
		'not_found_in_trash' =>  __('No functions found in Trash','plugincodexgen'),
		'parent_item_colon' => '',
		'menu_name' => __('Plug-in Docs','plugincodexgen'),
  	);

	if( !plugincodexgen_get_option('function_rewrite') ){
		$function_rewrite = false;
	}else{
		$function_rewrite = array( 'slug' =>plugincodexgen_get_option('function_rewrite'), 'with_front' => false,'feeds'=> true,'pages'=> true );
	}
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'query_var' => true,
		'rewrite' => $function_rewrite,
		'has_archive' => true,
		'hierarchical' => false,
		'menu_position' => apply_filters('plugincodexgen_menu_position',16),
		'supports' => plugincodexgen_get_option('supports'),
	  ); 	
	
	register_post_type('pcg_function', apply_filters('plugincodexgen_register_pcg_function', $args) );

	$labels = array(
		'name' => __('Hooks','plugincodexgen'),
		'singular_name' => __('Hook','plugincodexgen'),
		'add_new' => _x('Add New','post'),
		'add_new_item' => __('Add New Hook','plugincodexgen'),
		'edit_item' =>  __('Edit Hook','plugincodexgen'),
		'new_item' => __('New Hook','plugincodexgen'),
		'all_items' =>__('Hook Pages','plugincodexgen'),
		'view_item' =>__('View Hook','plugincodexgen'),
		'search_items' =>__('Search hooks','plugincodexgen'),
		'not_found' =>  __('No hooks found','plugincodexgen'),
		'not_found_in_trash' =>  __('No hooks found in Trash','plugincodexgen'),
		'menu_name' => __('Hook pages','plugincodexgen'),
  	);
	if( !plugincodexgen_get_option('hook_rewrite') ){
		$hook_rewrite = false;
	}else{
		$hook_rewrite = array( 'slug' =>plugincodexgen_get_option('hook_rewrite'), 'with_front' => false,'feeds'=> true,'pages'=> true );
	}
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_in_menu'=>'edit.php?post_type=pcg_function',
		'show_ui' => true, 
		'query_var' => true,
		'has_archive' => true,
		'hierarchical' => false,
		'rewrite'=>$hook_rewrite,
		'supports' => plugincodexgen_get_option('supports'),
	  ); 	

	register_post_type('pcg_hook', apply_filters('plugincodexgen_register_pcg_hook', $args) );

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
  	$labels = array(
    		'name' => _x( 'Packages', 'taxonomy general name' ),
    		'singular_name' => _x( 'Package', 'taxonomy singular name' ),
    		'search_items' =>  __( 'Search Packages' ),
    		'all_items' => __( 'All Packages' ),
    		'edit_item' => __( 'Edit Package' ), 
    		'update_item' => __( 'Update Package' ),
    		'add_new_item' => __( 'Add New Package' ),
    		'new_item_name' => __( 'New Package Name' ),
    		'menu_name' => __( 'Package' ),
    	);
	register_taxonomy('pcg_package',array('pcg_function'), array(
    		'hierarchical' => true,
		'labels' => $labels,
    		'show_ui' => true,
  	));	
  	$labels = array(
    		'name' => _x( 'Hook Type', 'taxonomy general name' ),
    		'singular_name' => _x( 'Hook Type', 'taxonomy singular name' ),
    		'search_items' =>  __( 'Search Hook Types' ),
    		'all_items' => __( 'All Hook Types' ),
    		'edit_item' => __( 'Edit Hook Type' ), 
    		'update_item' => __( 'Update Hook Type' ),
    		'add_new_item' => __( 'Add New Hook Type' ),
    		'new_item_name' => __( 'New Hook Type Name' ),
    	);
	register_taxonomy('pcg_hook_type',array('pcg_hook'), array(
    		'hierarchical' => false,
    		'show_ui' => true,
		'labels' => $labels,
  	));	

}

add_action('admin_menu','_plugincodex_remove_submenu');
function _plugincodex_remove_submenu(){
	remove_submenu_page( 'edit.php?post_type=pcg_function', 'post-new.php?post_type=pcg_function' );
}

add_action('edit_form_advanced','_pcg_function_additional_info_tinymce');
function _pcg_function_additional_info_tinymce( ){
	if( 'pcg_function' != get_post_type(get_the_ID()) && 'pcg_hook' != get_post_type(get_the_ID()) )
		return;
	wp_nonce_field('plugincodex_update_'.get_the_ID(),'_pcgnonce');
	wp_editor( plugincodex_get_function_meta(get_the_ID(),'other_notes',true), 'pcg_tinymce');
}

add_action('save_post','_pcg_function_hook_save_post');
function _pcg_function_hook_save_post( $post_id ){

	//make sure data came from our meta box
	if( !isset($_POST['_pcgnonce']) || !wp_verify_nonce($_POST['_pcgnonce'],'plugincodex_update_'.$post_id) ) 
		return;

	if( 'pcg_function' != get_post_type($post_id) && 'pcg_hook' != get_post_type($post_id) )
		return;

	// verify this is not an auto save routine. 
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		return;

	//authentication checks
	if ( !current_user_can('edit_post', $post_id) ) 
		return;

	$other_notes = $_POST['pcg_tinymce'];
	update_post_meta($post_id,'_plugincodex_other_notes', $other_notes);
	return;
}
