<?php

	class WS_Form_Action_JavaScript extends WS_Form_Action {

		public $id = 'javascript';
		public $pro_required = true;
		public $label;
		public $label_action;
		public $events;
		public $multiple = true;
		public $configured = true;
		public $priority = 150;
		public $can_repost = false;
		public $form_add = false;

		// Config
		public $javascript;

		public function __construct() {

			// Set label
			$this->label = __('Run JavaScript', 'ws-form');

			// Set label for actions pull down
			$this->label_action = __('Run JavaScript', 'ws-form');

			// Events
			$this->events = array('submit');

			// Register action
			parent::register($this);

			// Register config filters
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);
		}

		public function post($form, &$submit, $config) {

			// Load config
			self::load_config($config);

			// Fire event
			parent::success(__('JavaScript added to queue', 'ws-form'), array(

				array(

					'action'		=> $this->id,
					'javascript' 	=> WS_Form_Common::parse_variables_process($this->javascript)
				)
			));
		}

		public function load_config($config) {

			$this->javascript = parent::get_config($config, 'action_' . $this->id . '_javascript');
		}

		// Get settings
		public function get_action_settings() {

			$settings = array(

				'meta_keys'		=> array(

					'action_' . $this->id . '_javascript'
				)
			);

			// Wrap settings so they will work with sidebar_html function in admin.js
			$settings = parent::get_settings_wrapper($settings);

			// Add labels
			$settings->label = $this->label;
			$settings->label_action = $this->label_action;

			// Add multiple
			$settings->multiple = $this->multiple;

			// Add events
			$settings->events = $this->events;

			// Add can_repost
			$settings->can_repost = $this->can_repost;

			// Apply filter
			$settings = apply_filters('wsf_action_' . $this->id . '_settings', $settings);

			return $settings;
		}

		// Meta keys for this action
		public function config_meta_keys($meta_keys = array(), $form_id = 0) {

			// Build config_meta_keys
			$config_meta_keys = array(

				// JavaScript
				'action_' . $this->id . '_javascript'	=> array(

					'label'			=>	__('JavaScript', 'ws-form'),
					'type'			=>	'html_editor',
					'select_list'	=>	true,
					'help'			=>	__('Do not add &lt;script&gt; tags', 'ws-form')
				)
			);

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys);

			return $meta_keys;
		}
	}

	new WS_Form_Action_JavaScript();
