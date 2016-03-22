<?php

class list_widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			// Base ID of your widget
			'list_widget',
	
			// Widget name will appear in UI
			__('Mailgun List Widget', 'wpb_widget_domain'),
	
			// Widget description
			array( 'description' => __( 'Mailgun list widget', 'wpb_widget_domain' ), )
		);
	}

	// Creating widget front-end
	// This is where the action happens
	public function widget($args, $instance) {
		global $mailgun;

		// vars
		$list_address = apply_filters('list_address', $instance['list_address']);

		if( isset($instance['collect_name']) ) {
			$args['collect_name'] = true;
		}

		if( isset($instance['list_title']) ) {
			$args['list_title'] = $instance['list_title'];
		}

		if( isset($instance['list_description']) ) {
			$args['list_description'] = $instance['list_description'];
		}

		$mailgun->list_form($list_address, $args, $instance);
    }

    // Widget Backend 
    public function form( $instance ) {
		global $mailgun;
    	
        if ( isset( $instance[ 'list_address' ] ) ) {
            $list_address = $instance[ 'list_address' ];
        } else {
            $list_address = __( 'New list_address', 'wpb_widget_domain' );
        }

		if ( isset( $instance[ 'collect_name' ] ) && $instance[ 'collect_name' ] === 'on' ) {
            $collect_name = 'checked';
        } else {
        	$collect_name = '';
        }

        $list_title = isset( $instance[ 'list_title' ] ) ? $instance['list_title'] : null;
        $list_description = isset( $instance[ 'list_description' ] ) ? $instance['list_description'] : null;
        
        // Widget admin form
        ?>
        <div class="mailgun-list-widget-back">
        	<p>
	            <label for="<?php echo $this->get_field_id( 'list_title' ); ?>"><?php _e( 'Title (optional):' ); ?></label> 
	            <input class="widefat" id="<?php echo $this->get_field_id( 'list_title' ); ?>" name="<?php echo $this->get_field_name( 'list_title' ); ?>" type="text" value="<?php echo esc_attr( $list_title ); ?>" />
	        </p>
	        <p>
	            <label for="<?php echo $this->get_field_id( 'list_description' ); ?>"><?php _e( 'Description (optional):' ); ?></label> 
	            <input class="widefat" id="<?php echo $this->get_field_id( 'list_description' ); ?>" name="<?php echo $this->get_field_name( 'list_description' ); ?>" type="text" value="<?php echo esc_attr( $list_description ); ?>" />
	        </p>
	        <p>
	            <label for="<?php echo $this->get_field_id( 'list_address' ); ?>"><?php _e( 'List addresses (required):' ); ?></label> 
	            <input class="widefat" id="<?php echo $this->get_field_id( 'list_address' ); ?>" name="<?php echo $this->get_field_name( 'list_address' ); ?>" type="text" value="<?php echo esc_attr( $list_address ); ?>" />
	        </p>
	        <p>
	            <label for="<?php echo $this->get_field_id( 'collect_name' ); ?>"><?php _e( 'Collect name:' ); ?></label> 
	            <input class="widefat" id="<?php echo $this->get_field_id( 'collect_name' ); ?>" name="<?php echo $this->get_field_name( 'collect_name' ); ?>" type="checkbox" <?php echo esc_attr( $collect_name ); ?> />
	        </p>
        </div>
        <?php 
    }
    
    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
    	$instance = array();
       	$instance = $new_instance; 

        return $instance;
    }
}



