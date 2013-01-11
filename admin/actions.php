<?php

/* Trigger custom actions */
/**
 * Initialises plug-in actions
 * Hooked onto init
 * @since 1.0
 * @ignore
 * @access private
 */
function plugincodexgen_init(){
	if( !empty($_REQUEST['plugincodexgen']) && !empty($_REQUEST['plugincodexgen']['action']) ){
		do_action('plugincodexgen_action-'.trim($_REQUEST['plugincodexgen']['action']));
	}
}
add_action('init', 'plugincodexgen_init',11);

/* Generate function/hook documentation */
add_action('plugincodexgen_action-generate-documentation','_plugincodexgen_generate_documentation');


/* Ajax actions */
add_action('wp_ajax_plugin_codex_gen_suggest_function', '_plugincodexgen_suggest');
add_action('wp_ajax_plugin_codex_gen_suggest_hook', '_plugincodexgen_suggest');
add_action('wp_ajax_plugin_codex_gen_wiki',  '_plugincodexgen_generate_wiki');

add_action('wp_ajax_plugin_codex_search',  '_plugincodexgen_search');
add_action('wp_ajax_nopriv_plugin_codex_search',  '_plugincodexgen_search');


/**
 * Ajax callback - searches for functions by name
 * Hooked onto wp_ajax_plugin_codex_gen_suggest
 * @since 1.0
 * @ignore
 * @access private
 */
function _plugincodexgen_suggest(){

	if( !current_user_can('manage_options') )
		die;

	if( empty($_REQUEST['q']) )
		die;

	if( current_filter() == 'wp_ajax_plugin_codex_gen_suggest_function' ){
		$search = plugincodex_get_functions( array(
					's' => plugincodex_sanitize_function_name($_REQUEST['q']),
					 'orderby' => 'match',
					'number' => 15,
				));
	}else{
		$search = plugincodex_get_hooks( array(
					's' => plugincodex_sanitize_function_name($_REQUEST['q']),
					 'orderby' => 'match',
					'number' => 15,
				));
	}
	$items = wp_list_pluck($search,'name');
	echo implode("\n", $items);
	die;
}

/**
 * Ajax callback - preview function wiki
 * Hooked onto wp_ajax_plugin_codex_gen_wiki
 * @since 1.0
 * @ignore
 * @access private
 */
function  _plugincodexgen_generate_wiki() {

	if( !current_user_can('manage_options') )
		die;

	if( empty($_REQUEST['function']) && empty($_REQUEST['hook']) )
		wp_die('');

	if( !empty($_REQUEST['function']) ){
		$function = plugincodex_sanitize_function_name($_REQUEST['function']);
		$data = array_pop( plugincodex_get_functions( array('functions__in'=>array($function)) ) );
	}else{
		$hook = plugincodex_sanitize_function_name($_REQUEST['hook']);
		$data = array_pop( plugincodex_get_hooks( array('hooks__in'=>array($hook)) ) );
	}

	$wiki = $data->get_wiki();

	printf('<hr><h1> %s </h1><hr>',__('Preview','plugincodexgen'));
	echo $wiki;
	if( is_plugin_active('wp-markdown/wp-markdown.php') ){
		$md = wpmarkdown_html_to_markdown(wpautop($wiki));
		echo '<hr><h1> MarkDown </h1><hr>';
		echo '<br /><textarea style="width:100%;height:90%;" class="code">'.esc_textarea($md).'</textarea>';
	}

	echo '<hr><h1> HTML </h1><hr>';
	echo '<br /><textarea style="width:100%;height:90%;" class="code">'.esc_textarea($wiki).'</textarea>';
	die;
}

/**
 * Generates documentation for functions
 * Hooked onto plugincodexgen_action-generate-documentation
 * @since 1.0
 * @ignore
 * @access private
 */
function _plugincodexgen_generate_documentation(){

	check_admin_referer('plugincodexgen-bulk-action', '_pcgpnonce');

	if( isset($_GET['function'])  ){
		$functions__in = $_GET['function'];
		_plugincodexgen_generate_function_documentation($functions__in);

	}elseif( isset($_GET['hook'])  ){
		$hooks__in = $_GET['hook'];
		_plugincodexgen_generate_hook_documentation($hooks__in);
	}
}

function _plugincodexgen_generate_function_documentation($functions__in){

	$query['functions__in'] = array_map('trim',$functions__in);
	if( !empty($_GET['path']) )
		$query['path'] = $_GET['path'];

	$functions = plugincodex_get_functions($query);	

	foreach ( $functions as $function ){
		$wiki = $function->get_wiki();

		if( $function->get_package() ){
			$package = get_term_by( 'slug', $function->get_package(), 'pcg_package');
			if( !$package ){
				$tt_id = wp_insert_term( $function->get_package(),'pcg_package' );
				$term_id = (int) $tt_id['term_id'];
			}else{
				$term_id = (int) $package->term_id;
			}
		}else{
			$term_id =0;
		}
		$post_arr = array(
			'post_content'=>$wiki,//Just use $wiki for MD
			'post_type'=>'pcg_function',
			'tax_input'=>array(
				'pcg_package'=>array($term_id ),
			),
		);

		if( $posts = get_posts(array('post_type'=>'pcg_function','post_status'=>'any','name'=>sanitize_title($function->name), 'numberposts'=>1)) ){
			//Updating the page
			$post_arr['ID'] = $posts[0]->ID;
			$id = wp_update_post($post_arr);
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

/**
 * Generates documentation for hooks
 * Hooked onto plugincodexgen_action-generate-hook-documentation
 * @since 1.0
 * @ignore
 * @access private
 */
function _plugincodexgen_generate_hook_documentation( $hooks__in ){

	/* Set up query */
	$query['hooks__in'] = array_map('trim',$hooks__in);
	if( !empty($_GET['path']) )
		$query['path'] = $_GET['path'];

	$hooks = plugincodex_get_hooks($query);	

	foreach ( $hooks as $hook ){
		$wiki = $hook->get_wiki();

		$type = get_term_by( 'slug', $hook->type, 'pcg_hook_type');
		if( !$type ){
			$tt_id = wp_insert_term( $hook->type,'pcg_hook_type' );
			$term_id = (int) $tt_id['term_id'];
		}else{
			$term_id = (int) $type->term_id;
		}

		$post_arr = array(
			'post_content'=>$wiki,
			'post_type'=>'pcg_hook',
			'tax_input'=>array(
				'pcg_hook_type'=>array($term_id ),
			),
		);
		
		if( $posts = get_posts(array('post_type'=>'pcg_hook','post_status'=>'any','name'=>sanitize_title($hook->name), 'numberposts'=>1)) ){
			//Updating the page
			$post_arr['ID'] = $posts[0]->ID;
			$id = wp_update_post($post_arr);
			update_post_meta($id,'_plugincodex_type', $hook->type);
		}else{
			//Creating the page
			$post_arr = array_merge($post_arr,array(
				'post_title'=>$hook->name,
				'post_name'=>sanitize_title($hook->name),
			));
			$post_id = wp_insert_post($post_arr);	
			update_post_meta($post_id,'_plugincodex_type', $hook->type);
		}
	}

	wp_redirect(add_query_arg(array('hooks__in'=>false,'plugincodexgen'=> false,'_pcgpnonce'=>false)));
	exit();
}

function _plugincodexgen_search(){
		
		$term = trim(esc_attr($_GET["term"]));

		$query = new WP_Query(array(
			'post_type'=>array('pcg_function', 'pcg_hook',/*'page','post'*/),
			'order'=>'asc',
			's'=>$term,
			'numberposts'=> 15,		
		));

		$results = array();
		if( $query->have_posts() ):
			while( $query->have_posts() ): $query->the_post();

				switch( get_post_type() ):
					case 'page':
						$category = 'Page';
					break;
					case 'post':
						$category = 'Post';
					break;
					case 'pcg_hook':
						$terms = get_the_terms( get_the_ID(), 'pcg_hook_type');
						if( $terms ){
							$package = array_pop($terms);
							$category = 'Hooks ('.$package->name.')';
						}
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

				$results[] = array( 'label'=> get_the_title(), 'category' => ucfirst($category), 'url' => get_permalink());
			endwhile;
		endif;
		wp_reset_postdata();

		//echo JSON to page  
		$response = json_encode($results);
		echo $response;  
		exit;
}
