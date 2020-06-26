<?php

	class DiviExtension_WS_Form extends DiviExtension {

		public $gettext_domain = 'ws-form';
		public $name = 'ws-form-divi';
		public $version = '1.0.0';

		public function __construct($name = 'ws-form-divi', $args = array()) {

			$this->plugin_dir     = plugin_dir_path(__FILE__);
			$this->plugin_dir_url = plugin_dir_url($this->plugin_dir);

			parent::__construct($name, $args);

			// Dequeue frontend JS (not required)
			add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'), 11);
		}

		public function wp_enqueue_scripts() {

			// Dequeue frontend JS (not required)
		    wp_dequeue_script('ws-form-divi-frontend-bundle');

		    if(!et_core_is_fb_enabled()) {

				// Dequeue frontend styles (not required)
			    wp_dequeue_style('ws-form-divi-styles');

			} else {

				// Enqueue all WS Form scripts
				do_action('wsf_enqueue_core');
			}
		}
	}

	new DiviExtension_WS_Form;
