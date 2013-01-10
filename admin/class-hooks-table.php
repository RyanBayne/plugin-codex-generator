<?php

if (!class_exists('WP_List_Table'))
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

class Plugin_Codex_Generator_Hooks_Table extends PCG_Admin_Table {

	function __construct() {
		parent::__construct(array('singular' => 'hook','plural' => 'hooks', 'ajax' => false));
    	}

	function prepare_items() {

		$screen = get_current_screen();
		//Screen options use screen ID with '-' replaced by '_'.
		$screen_id = str_replace('-','_',$screen->id);
		$per_page = (int) get_user_option( $screen_id.'_per_page');

		if( empty($per_page) )
			$per_page = 20;

		$current_page = $this->get_pagenum();
		$offset = $current_page > 1 ? $per_page*($current_page-1) : 0;

		$query = array(
			'number' => $per_page,
			'return' => 'array',
			'offset' => $offset,
		);

		foreach( array('s','orderby','order','path','type') as $arg )
			if( isset($_GET[$arg]) )
				$query[$arg] = esc_attr($_GET[$arg]);

		$search = new PCG_Hook_Query($query);
		$data = $search->get_results();

		$total_items = $search->count;
		$this->paths = $search->paths;

		$this->items = $data;
		$this->set_pagination_args( array(
			'total_items' => $total_items,
            		'per_page'    => $per_page,
            		'total_pages' => ceil($total_items/$per_page),
        	) );
	}

	function get_columns(){
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'name' => __('Name', 'plugincodexgen'),
			'type' => __('Filter/Action','plugincodexgen'),
			'arguments' => __('Arguments', 'plugincodexgen'),
			'file' => __('Location', 'plugincodexgens'),
			'get' => __('Get', 'plugincodexgen'),
		);
		return $columns;
    	}

	function get_sortable_columns() {
        	$sortable_columns = array(
        	    'name'     => array('name', empty( $_GET['orderby'] ) ),
        	    'arguments'    => array('arguments',false),
        	);
        	return $sortable_columns;
	    }


	function column_name($item) {
		$name = esc_html($item->name);
		return "<span class='code'>{$name}</span>";
	}

	function column_type($item) {
		return sprintf('<a href="%s"> %s </a>', add_query_arg('type',$item->type), $item->type );
	}

	function column_file($item) {

		$_location ='';
		foreach( $item->location as $location ){
			$file = plugincodex_sanitize_path($location['path']);
			$line = !empty($location['line']) ? ' #L'.intval($location['line']) : '';
			$segments = explode('/', $file);
			$_location .= implode( ' / ', $segments ).$line.'</br>';
		}
		return $_location;
	}
}
