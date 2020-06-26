<?php

	class WS_Form_Action_Message extends WS_Form_Action {

		public $id = 'message';
		public $pro_required = false;
		public $label;
		public $label_action;
		public $events;
		public $multiple = true;
		public $configured = true;
		public $priority = 150;
		public $can_repost = false;
		public $form_add = true;

		// Config
		public $message;
		public $type;
		public $method;
		public $duration;
		public $form_hide;
		public $clear;
		public $scroll_top;
		public $scroll_top_offset;
		public $scroll_top_duration;
		public $form_show;
		public $message_hide;

		public function __construct() {

			// Set label
			$this->label = __('Message', 'ws-form');

			// Set label for actions pull down
			$this->label_action = __('Show Message', 'ws-form');

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

			// Check message
			if(!empty($this->message)) {

				// Process shortcodes on message
				$message = WS_Form_Common::do_shortcode($this->message);

				// Show the message
				parent::success(sprintf(__('Message added to queue: %s', 'ws-form'), $this->message), array(

					array(

						'action' => $this->id,
						'message' => WS_Form_Common::parse_variables_process($message, $form, $submit, 'text/plain'),
						'type' => $this->type,
						'method' => $this->method,
						'duration' => $this->duration,
						'form_hide' => $this->form_hide,
						'clear' => $this->clear,
						'scroll_top' => $this->scroll_top,
						'scroll_top_offset' => $this->scroll_top_offset,
						'scroll_top_duration' => $this->scroll_top_duration,
						'form_show' => $this->form_show,
						'message_hide' => $this->message_hide
					)
				));

			} else {

				// Invalud message
				parent::error(__('Invalid message', 'ws-form'));
			}
		}

		public function load_config($config) {

			// Get config
			$this->message = parent::get_config($config, 'action_' . $this->id . '_message');
			$this->type = parent::get_config($config, 'action_' . $this->id . '_type');
			$this->method = parent::get_config($config, 'action_' . $this->id . '_method');
			$this->clear = parent::get_config($config, 'action_' . $this->id . '_clear');
			$this->scroll_top = parent::get_config($config, 'action_' . $this->id . '_scroll_top');
			$this->scroll_top_offset = parent::get_config($config, 'action_' . $this->id . '_scroll_top_offset');
			$this->scroll_top_duration = parent::get_config($config, 'action_' . $this->id . '_scroll_top_duration');
			$this->form_hide = parent::get_config($config, 'action_' . $this->id . '_form_hide');
			$this->duration = parent::get_config($config, 'action_' . $this->id . '_duration');
			$this->message_hide = parent::get_config($config, 'action_' . $this->id . '_message_hide');
			$this->form_show = parent::get_config($config, 'action_' . $this->id . '_form_show');
		}

		// Get settings
		public function get_action_settings() {

			$settings = array(

				'meta_keys'		=> array(

					'action_' . $this->id . '_type',
					'action_' . $this->id . '_message',
					'action_' . $this->id . '_method',
					'action_' . $this->id . '_form_hide',
					'action_' . $this->id . '_clear',
					'action_' . $this->id . '_scroll_top',
					'action_' . $this->id . '_scroll_top_offset',
					'action_' . $this->id . '_scroll_top_duration',
					'action_' . $this->id . '_duration',
					'action_' . $this->id . '_message_hide',
					'action_' . $this->id . '_form_show',
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

				// Message
				'action_' . $this->id . '_message'	=> array(

					'label'						=>	__('Content', 'ws-form'),
					'type'						=>	'text_editor',
					'help'						=>	__('Message shown on the form', 'ws-form'),
					'default'					=>	__('Thank you for your inquiry', 'ws-form')
				),

				// Type
				'action_' . $this->id . '_type'	=> array(

					'label'						=>	__('Type', 'ws-form'),
					'type'						=>	'select',
					'help'						=>	__('Style of message to use', 'ws-form'),
					'options'					=>	array(

						array('value' => 'success', 'text' => __('Success', 'ws-form')),
						array('value' => 'information', 'text' => __('Information', 'ws-form')),
						array('value' => 'warning', 'text' => __('Warning', 'ws-form')),
						array('value' => 'danger', 'text' => __('Danger', 'ws-form'))
					),
					'default'					=>	'success'
				),

				// Method
				'action_' . $this->id . '_method'	=> array(

					'label'						=>	__('Position', 'ws-form'),
					'type'						=>	'select',
					'help'						=>	__('Where should the message be added?', 'ws-form'),
					'options'					=>	array(

						array('value' => 'before', 'text' => __('Before Form', 'ws-form')),
						array('value' => 'after', 'text' => __('After Form', 'ws-form'))
					),
					'default'					=>	'before'
				),

				// Form - Clear other messages
				'action_' . $this->id . '_clear'	=> array(

					'label'						=>	__('Clear Other Messages', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	__('Clear any other messages when shown?', 'ws-form'),
					'default'					=>	'on'
				),

				// Form - Scroll to top
				'action_' . $this->id . '_scroll_top'				=> array(

					'label'						=>	__('Scroll To Top', 'ws-form'),
					'type'						=>	'select',
					'default'					=>	'',
					'options'					=>	array(

						array('value' => '', 'text' => __('None', 'ws-form')),
						array('value' => 'instant', 'text' => __('Instant', 'ws-form')),
						array('value' => 'smooth', 'text' => __('Smooth', 'ws-form'))
					)
				),

				'action_' . $this->id . '_scroll_top_offset'		=> array(

					'label'						=>	__('Scroll Offset (Pixels)', 'ws-form'),
					'type'						=>	'number',
					'default'					=>	'0',
					'help'						=>	__('Number of pixels to offset the final scroll position by. Useful for sticky headers, e.g. if your header is 100 pixels tall, enter 100 into this setting.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'!=',
							'meta_key'			=>	'action_' . $this->id . '_scroll_top',
							'meta_value'		=>	''
						)
					)
				),

				'action_' . $this->id . '_scroll_top_duration'	=> array(

					'label'						=>	__('Scroll Duration (ms)', 'ws-form'),
					'type'						=>	'number',
					'default'					=>	'400',
					'help'						=>	__('Duration of the smooth scroll in ms.', 'ws-form'),
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_scroll_top',
							'meta_value'		=>	'smooth'
						)
					)
				),

				// Form - Hide
				'action_' . $this->id . '_form_hide'	=> array(

					'label'						=>	__('Hide Form When Shown', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	__('Hide form when message shown?', 'ws-form'),
					'default'					=>	'on'
				),

				// Duration
				'action_' . $this->id . '_duration'	=> array(

					'label'						=>	__('Show Duration (ms)', 'ws-form'),
					'type'						=>	'number',
					'help'						=>	__('Duration in milliseconds to wait until next action', 'ws-form'),
					'default'					=>	''
				),

				// Message - Hide
				'action_' . $this->id . '_message_hide'	=> array(

					'label'						=>	__('Hide Message After Duration', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	__('Hide message after show duration finishes?', 'ws-form'),
					'default'					=>	'',
					'condition'					=>	array(

						array(

							'logic'			=>	'!=',
							'meta_key'		=>	'action_' . $this->id . '_duration',
							'meta_value'	=>	''
						)
					)
				),

				// Form - Show
				'action_' . $this->id . '_form_show'	=> array(

					'label'						=>	__('Show Form After Duration', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	__('Show form after duration finishes?', 'ws-form'),
					'default'					=>	'',
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							'meta_key'		=>	'action_' . $this->id . '_form_hide',
							'meta_value'	=>	'on',
							'logic_previous'	=>	'&&'
						),

						array(

							'logic'			=>	'!=',
							'meta_key'		=>	'action_' . $this->id . '_duration',
							'meta_value'	=>	'',
							'logic_previous'	=>	'&&'
						)
					)
				),
			);

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys);

			return $meta_keys;
		}
	}

	new WS_Form_Action_Message();
