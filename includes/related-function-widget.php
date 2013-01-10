<?php

class PCG_Related_Function_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'pcg-related-function-widget', 'description' => __( "Displays related functions. This widget will only appear on single function pages",'plugincodexgen') );
		parent::__construct('pcg-related-functions', __('Related Functions','plugincodexgen'), $widget_ops);
		$this->alt_option_name = 'pcg-related-function-widgets';

		add_action( 'save_post', array(&$this, 'flush_widget_cache') );
		add_action( 'deleted_post', array(&$this, 'flush_widget_cache') );
	}

	function widget($args, $instance) {

		if( !is_singular('pcg_function') )
			return false;

		$cache = wp_cache_get('pcg-related-function-widgets', 'widget');

		if ( !is_array($cache) )
			$cache = array();

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		extract($args);

		$html ='';
		$title = $instance['title'];

		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) )
 			$number = 10;

		$posts = $this->get_related_by( get_queried_object_id(), 'pcg_stems', array('numberposts'=>$number) );

		if( $posts ):

			$html = $before_widget; 

			$widget = apply_filters('widget_title', $title, $instance, $this->id_base);

			if ( $title ) 
				$html .= $before_title . $title . $after_title; 

			$html .= '<ul>';

				global $post;
				foreach( $posts as $post ): setup_postdata($post);
					$html .= sprintf('<li><a href="%s" title="%s">%s</a></li>',
						get_permalink(),
						esc_attr(get_the_title()),
						get_the_title()
					);
				endforeach;

			$html .= '</ul>';

			$html .= $after_widget; 

			// Reset the global $the_post as this query will have stomped on it
			wp_reset_postdata();

		endif;

		$cache[$args['widget_id']] = apply_filters('plugincodex_related_functions_widget', $html, $args, $instance);
		wp_cache_set('pcg-related-function-widgets', $cache, 'widget');

		echo $html;
	}

	function get_related_by( $post_id, $tax, $args=array() ){
		$terms = get_the_terms($post_id,$tax);
		if( !$terms )
			return false;

		$terms = array_values(array_map('intval',wp_list_pluck($terms, 'term_id')));

		$args = wp_parse_args(array(
			'post_type'=>'pcg_function',
			'suppress_filters'=>false,
			'tax_query'=>array(
				array(
					'taxonomy' => $tax,
					'field' => 'id',
					'terms' => $terms,
				),
			),
			'post__not_in'=> array($post_id)
		), $args);

		add_filter('posts_clauses', array($this,'related_clauses'),10,2);
		$posts = get_posts($args);	
		remove_filter('posts_clauses', array($this,'related_clauses'));
		return $posts;
	}	

	function related_clauses( $clauses, $q ){
		$clauses['fields'] .=', COUNT( wp_term_relationships.object_id) AS r';
		$clauses['orderby'] ='r '.$q->get('order');
		return $clauses;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$this->flush_widget_cache();
		return $instance;
	}


	function flush_widget_cache() {
		wp_cache_delete('pcg-related-function-widgets', 'widget');
	}


	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number = isset($instance['number']) ? absint($instance['number']) : 5;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','plugincodexgen'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of functions to show:','plugincodexgen'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
}
