<?php

if (!class_exists('WP_List_Table'))
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

class Plugin_Codex_Generator_Hooks_Table extends WP_List_Table {

	function __construct() {

		parent::__construct(array(
								 'singular' => 'function',
								 'plural' => 'functions',
								 'ajax' => false,
							));
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

	function display_tablenav( $which ) {

		printf('<div class="tablenav %s">',esc_attr( $which ));

		$this->extra_tablenav( $which );
		$this->pagination( $which );

		echo '<br class="clear" />';
		echo '</div>';
	}


	function extra_tablenav($which) {

		if( 'top' == $which ) {
			$this->generate_docs();
			$this->path_dropdown();
			submit_button( __( 'Filter' ), 'secondary', false, false, array( 'id' => 'function-query-submit' ) );
			$this->reset_button();
		}else {
			$this->reset_button();
		}
	}

	function generate_docs(){
		$options = array(
			''=> __('Bulk Actions'),
			'generate-hook-documentation' => __('Generate/Update Documentation'),
		);
		echo '<select id="functon_action" name="plugincodexgen[action]">';
		foreach( $options as $value => $label ) {
			echo "<option value='{$value}'>{$label}</option>";
		}
		echo '</select>';
		wp_nonce_field('plugincodexgen-bulk-action', '_pcgpnonce',false);
		submit_button( 'Bulk Action', 'secondary', 'submit',false);
	}


	function path_dropdown() {

		$path = isset( $_GET['path'] ) ? esc_html($_GET['path']) : false;

		?>
	<label for="path"><span class="description"><?php _e('Path:','codex_gen'); ?></span></label>
	<select id="path" name='path'>
		<option value=""></option>
		<?php
		$tree = $this->explode_paths_to_tree($this->paths);
		echo $this->print_tree($tree, $path);

		?>
	</select>
		<?php
	}


	function explode_paths_to_tree($paths) {

		$tree = array();
		foreach( $paths as $path ) {
			$parts = explode('/', $path);
			$target =& $tree;
			foreach( $parts as $part )
				$target =& $target[$part];
		}
		return $tree;
	}


	function print_tree($tree, $current, $prepend = '', $val = '') {

		foreach( $tree as $key => $leaf ) {

			$value = trim( "{$val}/{$key}", '/' );
			$selected = selected($value, $current, false);
			echo "<option value='{$value}' {$selected} >{$prepend}{$key}</option>";

			if( is_array($leaf) )
				$this->print_tree($leaf, $current, '- '.$prepend, $val.'/'.$key);
		}
	}

	function reset_button() {
		$link = add_query_arg('page','plugincodexgen-hooks',PLUGIN_CODEX_GENERATOR_LINK);
		$anchor = __('Reset', 'codex_gen');
		echo "<a href='{$link}' class='reset'>{$anchor}</a>";
	}

	function get_columns(){
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'name' => __('Name', 'codex_gen'),
			'type' => __('Filter/Action','codex_gen'),
			'arguments' => __('Arguments', 'codex_gen'),
			'file' => __('Location', 'codex_gen'),
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


	function column_arguments($item) {
		if( isset($item->arguments) ){
			$num = count($item->arguments);
			return $num.'<code>'.implode(',',$item->arguments).'</code>';
		}
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

	/*
	* Checkbox column for Bulk Actions.
	* 
	* @see WP_List_Table::::single_row_columns()
	* @param array $item A singular item (one full row's worth of data)
	* @return string Text to be placed inside the column <td> (movie title only)
	*/
	function column_cb($item){
        	return sprintf(
	            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
        	    /*$1%s*/ 'hook',  
        	    /*$2%s*/ $item->name       //The value of the checkbox should be the record's id
        	);
    	}
}
