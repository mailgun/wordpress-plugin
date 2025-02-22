<?php
/**
 * @file wp-content/plugins/wordpress-plugin/includes/widget.php
 * Mailgun-wordpress-plugin - Sending mail from Wordpress using Mailgun
 * Copyright (C) 2016 Mailgun, et al.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @package Mailgun
 */
class List_Widget extends \WP_Widget {

    /**
     * Register widget with WordPress.
     */
    public function __construct() {
        parent::__construct(
            // Base ID of your widget
            'list_widget',
            // Widget name will appear in UI
            __('Mailgun List Widget', 'wpb_widget_domain'),
            // Widget description
            array( 'description' => __('Mailgun list widget', 'wpb_widget_domain') )
        );
    }

    /**
     * @param mixed $args
     * @param mixed $instance
     * @return void
     * @throws JsonException
     */
    public function widget( $args, $instance ) {
        $mailgun = Mailgun::getInstance();

        if ( ! isset($instance['list_address']) || ! $instance['list_address']) {
            return;
        }
        // vars
        $list_address = apply_filters('list_address', $instance['list_address']);

        if (isset($instance['collect_name'])) {
            $args['collect_name'] = true;
        }

        if (isset($instance['list_title'])) {
            $args['list_title'] = $instance['list_title'];
        }

        if (isset($instance['list_description'])) {
            $args['list_description'] = $instance['list_description'];
        }

        $mailgun->list_form($list_address, $args);
    }

    // Widget Backend

    /**
     * @param mixed $instance
     * @return string|void
     */
    public function form( $instance ) {
        if (isset($instance['list_address'])) {
            $list_address = $instance['list_address'];
        } else {
            $list_address = __('New list_address', 'wpb_widget_domain');
        }

        if (isset($instance['collect_name']) && $instance['collect_name'] === 'on') {
            $collect_name = 'checked';
        } else {
            $collect_name = '';
        }

        $list_title       = $instance['list_title'] ?? null;
        $list_description = $instance['list_description'] ?? null;

        // Widget admin form
        ?>
        <div class="mailgun-list-widget-back">
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('list_title')); ?>"><?php _e('Title (optional):'); ?></label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('list_title')); ?>" name="<?php echo esc_attr($this->get_field_name('list_title')); ?>" type="text" value="<?php echo esc_attr($list_title); ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('list_description'); ?>"><?php _e('Description (optional):'); ?></label> 
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('list_description')); ?>" name="<?php echo esc_attr($this->get_field_name('list_description')); ?>" type="text" value="<?php echo esc_attr($list_description); ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('list_address'); ?>"><?php _e('List addresses (required):'); ?></label> 
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('list_address')); ?>" name="<?php echo esc_attr($this->get_field_name('list_address')); ?>" type="text" value="<?php echo esc_attr($list_address); ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('collect_name'); ?>"><?php _e('Collect name:'); ?></label> 
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('collect_name')); ?>" name="<?php echo esc_attr($this->get_field_name('collect_name')); ?>" type="checkbox" <?php echo esc_attr($collect_name); ?> />
            </p>
        </div>
        <?php
    }

    /**
     * @param mixed $new_instance
     * @param mixed $old_instance
     * @return array
     */
    public function update( $new_instance, $old_instance ) {
        return $new_instance;
    }
}
