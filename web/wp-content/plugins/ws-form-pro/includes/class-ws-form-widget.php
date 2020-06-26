<?php

	// WS Form Widget

	class WS_Form_Widget extends WP_Widget {

		// Main constructor
		public function __construct() {

			parent::__construct(

				WS_FORM_WIDGET,
				WS_FORM_NAME_PRESENTABLE,

				array(
					'description' => __('Displays a form from your WS Form form library.', 'ws-form'),
					'customize_selective_refresh' => true,
				)
			);
		}

		// The widget form (for the backend)
		public function form($instance) {

			// Set widget defaults
			$defaults = array(

				'title'		=> '',
				'form_id'	=> ''
			);
	
			// Parse current settings with defaults
			extract(wp_parse_args((array) $instance, $defaults ));

			// Get forms from API
			$ws_form_form = New WS_Form_Form();
			$forms = $ws_form_form->db_read_all('', 'NOT status="trash"', 'label', '', '', false);

			if($forms) {
?>
<p><label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'ws-form'); ?></label> 
<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

<p><label for="<?php echo esc_attr($this->get_field_id('form_id')); ?>"><?php esc_html_e('Select the form you want to add...', 'ws-form'); ?></label>

<select id="<?php echo esc_attr($this->get_field_id('form_id')); ?>" name="<?php echo esc_attr($this->get_field_name('form_id')); ?>">
<option value=""><?php esc_html_e('Select...', 'ws-form'); ?></option>
<?php
				foreach($forms as $form) {

?><option value="<?php echo esc_attr($form['id']); ?>"<?php if($form['id'] == $form_id) { ?> selected="selected"<?php } ?>><?php echo sprintf('%s (ID: %u)', esc_html($form['label']), esc_html($form['id'])); ?></option>
<?php
				}
?>
</select></p>
<?php
			} else {
?>
<p><?php esc_html_e("You haven't created any forms yet.", 'ws-form'); ?></p>
<p><a href="<?php echo esc_attr(WS_Form_Common::get_admin_url('ws-form-add')); ?>"><?php esc_html_e('Click here to create a form', 'ws-form'); ?></a></p>
<?php
			}
		}

		// Update widget settings
		public function update($new_instance, $old_instance) {

			$instance = $old_instance;
			$instance['title']    	= isset($new_instance['title'] ) ? wp_strip_all_tags($new_instance['title']) : '';
			$instance['form_id']    = isset($new_instance['form_id'] ) ? wp_strip_all_tags($new_instance['form_id']) : '';
			return $instance;
		}

		// Display the widget
		public function widget($args, $instance) {

			extract($args);

			// Check the widget options
			$title = apply_filters('widget_title', isset($instance['title']) ? $instance['title'] : '');
			$form_id = isset($instance['form_id']) ? $instance['form_id'] : '';

			// WordPress core before_widget hook (always include)
			echo $before_widget;	// phpcs:ignore

			// Display the title
			if(!empty($title)) {

				echo $args['before_title'];	// phpcs:ignore
				echo esc_html($title);
				echo $args['after_title'];	// phpcs:ignore
			}

			// Display the widget
			echo do_shortcode('[' . WS_FORM_SHORTCODE . ' id=' . $form_id . ']');

			// WordPress core after_widget hook (always include)
			echo $after_widget;	 // phpcs:ignore
		}

	}

	// Register the widget
	function ws_form_widget() {

		register_widget('WS_Form_Widget');
	}
	add_action('widgets_init', 'ws_form_widget');
