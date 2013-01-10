<?php

if (!class_exists('WP_List_Table'))
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

class Plugin_Codex_Generator_Functions_Table extends PCG_Admin_Table {

	function __construct( $args=array() ) {
			parent::__construct(array( 'singular' => 'function', 'plural' => 'functions','ajax' => false));
    	}

	function prepare_items() {

		$screen = get_current_screen();
		$per_page = (int) get_user_option( $screen->id.'_per_page');

		if( empty($per_page) )
			$per_page = 20;

		$current_page = $this->get_pagenum();
		$offset = $current_page > 1 ? $per_page*($current_page-1) : 0;

		$query = array(
			'type'=>$this->_args['singular'],
			'number' => $per_page,
			'return' => 'array',
			'offset' => $offset,
		);

		foreach( array('s','orderby','order','path','version','package') as $arg ){
			if( isset($_GET[$arg]) )
				$query[$arg] = esc_attr($_GET[$arg]);
		}

		$search = new PCG_Function_Query($query);
		$data = $search->get_results();
		$this->paths = $search->paths;

		$total_items = $search->count;
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
			'arguments' => __('Arguments', 'plugincodexgen'),
			'return' => __('Return', 'plugincodexgen'),
			'description' => __('Description', 'plugincodexgen'),
			'version' => __('Version', 'plugincodexgen'),
			'package' => __('Package','plugincodexgen'),
			'file' => __('File', 'plugincodexgen'),
			'get' => __('Get', 'plugincodexgen'),
		);
		return $columns;
    	}

	function get_sortable_columns() {
        	$sortable_columns = array(
        	    'name'     => array('name', empty( $_GET['orderby'] ) ),
        	    'version'    => array('version',false),
        	    'file'    => array('file',false),
        	);
        	return $sortable_columns;
	    }


	function column_name($item) {

		$name = esc_html($item->name).'()';
		if( !empty($item->deprecated) ){
			$name = '<del>'.$name.'</del>';
		}

		return "<span class='code'>{$name}</span>";
	}


	function column_description($item) {

		if( !empty($item->short_desc) )
			$description = $item->short_desc;
		elseif( !empty( $item->long_desc ) )
			list($description) = explode("\n", $item->long_desc, 1);
		else
			$description = '';

		return esc_html( $description );
	}

	function column_return($item) {

		if(!empty($item->doc['tags']['return'])) {
			list($type, $description) = plugincodex_explode(' ', $item->doc['tags']['return'], 2, '');
			$type = esc_html(plugincodex_type_to_string($type));
			$description = esc_html($description);
			return "<span class='code'><em>({$type})</em></span> {$description}";
		}

		return '';
	}

	function column_version($item) {

		$version = '';
		if( !empty($item->since ) ){
			$link = esc_url(add_query_arg('version', $item->since, PLUGIN_CODEX_GENERATOR_LINK));
			$title = esc_attr(sprintf(__('Filter by %s version', 'plugincodexgen'), $item->since));
			$version = "<a href='{$link}' title='{$title}'>{$item->since}</a>";
		}

		return $version;
	}

	function column_file($item) {

		$file = plugincodex_sanitize_path($item->path);
		$segments = explode('/', $file);
		$links = array();
		$pos = 0;

		foreach( $segments as $segment ) {
			$pos++;
			$anchor = $segment;
			$path = implode('/', array_slice($segments,0,$pos));
			$link = add_query_arg('path', $path, PLUGIN_CODEX_GENERATOR_LINK);
			$links[] = "<a href='{$link}'>{$anchor}</a>";
		}

		$line = !empty($item->line) ? ' #L'.intval($item->line) : '';

		return implode( ' / ', $links ).$line;
	}

	function column_package( $item ){
		$link = add_query_arg('package', $item->package, PLUGIN_CODEX_GENERATOR_LINK);
		printf('<a href="%s" title="%s">%s</a>', 
			esc_url($link), 
			sprintf(esc_attr__('Filter by %s package','plugincodexgen'), $item->package), 
			esc_html($item->package)
		);
	}
}
