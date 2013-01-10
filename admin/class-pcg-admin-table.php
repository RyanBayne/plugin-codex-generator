<?php

if (!class_exists('WP_List_Table'))
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

class PCG_Admin_Table extends WP_List_Table {

	function __construct( $args=array() ) {
		parent::__construct($args);
    	}

	function prepare_items() {}

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
			$this->search_box( __('Name:','plugincodexgen'),'search-input');
			$this->path_dropdown();
			submit_button( __( 'Filter','plugincodexgen' ), 'secondary', false, false, array( 'id' => 'suggest-submit' ) );
			$this->reset_button();
		}
		else {
			$this->reset_button();
		}
	}

	function generate_docs(){
		$options = array(
			''=> __('Bulk Actions'),
			'generate-documentation' => __('Generate/Update Documentation','plugincodexgen'),
		);
		echo '<select id="functon_action" name="plugincodexgen[action]">';
		foreach( $options as $value => $label ) {
			echo "<option value='{$value}'>{$label}</option>";
		}
		echo '</select>';
		wp_nonce_field('plugincodexgen-bulk-action', '_pcgpnonce',false);
		submit_button( 'Bulk Action', 'secondary', 'submit',false);
	}


	function search_box($text, $input_id) {
		?>
		<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
		<label for="<?php echo $input_id ?>"><span class="description"><?php echo $text; ?></span></label>
		<input type="text" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
		<?php
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
		$page = $_GET['page'];
		$link = add_query_arg('page',$page,PLUGIN_CODEX_GENERATOR_LINK);
		$anchor = __('Reset', 'codex_gen');
		echo "<a href='{$link}' class='reset'>{$anchor}</a>";
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


	function column_arguments($item) {

		$output = '';

		foreach( $item->parameters as $param ) {

			$type = isset( $param['type'] ) ? "<em>({$param['type']})</em>" : '';
			$name = "<strong>\${$param['name']}</strong>";
			$default = $param['has_default'] ? " = ".plugincodex_value_to_string($param['default']) : '';

			$output .= "{$type} {$name} {$default}<br />";
		}

		$output = "<span class='code'>{$output}</span>";

		return $output;
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
        	    /*$1%s*/ $this->_args['singular'],  
        	    /*$2%s*/ $item->name       //The value of the checkbox should be the record's id
        	);
    	}

	function column_package( $item ){
		$link = add_query_arg('package', $item->package, PLUGIN_CODEX_GENERATOR_LINK);
		printf('<a href="%s" title="%s">%s</a>', 
			esc_url($link), 
			sprintf(esc_attr__('Filter by %s package','plugincodexgen'), $item->package), 
			esc_html($item->package)
		);
	}

	function column_get($item) {

		$name = $item->name;
		$href = admin_url('admin-ajax.php');

		$query = array(
			'action' => 'plugin_codex_gen_wiki',
			$this->_args['singular'] => esc_attr($name),
			'width' => 800,
			'height' => 500,
		);

		$href = esc_url(add_query_arg($query, $href));
		$anchor = esc_html(__('Preview','plugincodexgen'));
		$title = esc_attr(sprintf(__('Preview page markup for %s()','plugincodexgen'), $name));

		$page ='';
		if( $posts = get_posts(array('post_type'=>'pcg_'.$this->_args['singular'],'post_status'=>'any','name'=>sanitize_title($item->name))) ){
			$page = $posts[0];
			$page = sprintf('<a target="_blank" href="%s"> Page </a> | ', get_permalink($page));
		}

		return $page."<a href='{$href}' class='thickbox' title='{$title}'>{$anchor}</a>";
	}
}
