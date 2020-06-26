<?php

	class WS_Form_Action_Conversion extends WS_Form_Action {

		public $id = 'conversion';
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
		public $type;

		// Config - Google
		public $tag_action;
		public $tag_category;
		public $tag_label;
		public $tag_value;
		public $tag_custom;

		// Config - Facebook - Standard
		public $fb_standard_event;
		public $fb_standard_properties;

		// Config - Facebook - Custom
		public $fb_custom_event;
		public $fb_custom_properties;

		public function __construct() {

			// Set label
			$this->label = __('Conversion Tracking', 'ws-form');

			// Set label for actions pull down
			$this->label_action = __('Conversion Tracking', 'ws-form');

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

			// Run conversion tag
			switch($this->type) {

				case 'google' :

					// Fire event
					parent::success(__('Google conversion tag added to queue', 'ws-form'), array(

						array(

							'action' 		=> $this->id,
							'type' 			=> $this->type,
							'parse_values' 	=> array(

								'action' 	=> WS_Form_Common::parse_variables_process($this->tag_action),
								'category' 	=> WS_Form_Common::parse_variables_process($this->tag_category),
								'label' 	=> WS_Form_Common::parse_variables_process($this->tag_label),
								'value' 	=> WS_Form_Common::parse_variables_process($this->tag_value)
							)
						)
					));

					break;

				case 'facebook_standard' :

					// Build params
					$params_array = array();
					foreach($this->fb_standard_properties as $fb_standard_property) {

						// Checks
						if(!isset($fb_standard_property['action_' . $this->id . '_fb_standard_property_key'])) { continue; }
						if($fb_standard_property['action_' . $this->id . '_fb_standard_property_key'] == '') { continue; }
						if(!isset($fb_standard_property['action_' . $this->id . '_fb_standard_property_value'])) { continue; }

						$params_array[] = $fb_standard_property['action_' . $this->id . '_fb_standard_property_key'] . ": '" . WS_Form_Common::parse_variables_process($fb_standard_property['action_' . $this->id . '_fb_standard_property_value']) . "'";
					}
					$params = (count($params_array) > 0) ? (', {' . implode(', ', $params_array) . '}') : '';

					// Fire event
					parent::success(__('Facebook (Standard) conversion tag added to queue', 'ws-form'), array(

						array(

							'action'		=> $this->id,
							'type' 			=> $this->type,
							'parse_values' 	=> array(

								'event' 	=> $this->fb_standard_event,
								'params' 	=> $params
							)
						)
					));

					break;

				case 'facebook_custom' :

					// Build params
					$params_array = array();
					foreach($this->fb_custom_properties as $fb_custom_property) {

						// Checks
						if(!isset($fb_custom_property['action_' . $this->id . '_fb_custom_property_key'])) { continue; }
						if($fb_custom_property['action_' . $this->id . '_fb_custom_property_key'] == '') { continue; }
						if(!isset($fb_custom_property['action_' . $this->id . '_fb_custom_property_value'])) { continue; }

						$params_array[] = $fb_custom_property['action_' . $this->id . '_fb_custom_property_key'] . ": '" . WS_Form_Common::parse_variables_process($fb_custom_property['action_' . $this->id . '_fb_custom_property_value']) . "'";
					}
					$params = (count($params_array) > 0) ? (', {' . implode(', ', $params_array) . '}') : '';

					// Fire event
					parent::success(__('Facebook (Custom) conversion tag added to queue', 'ws-form'), array(

						array(

							'action'		=> $this->id,
							'type' 			=> $this->type,
							'parse_values' 	=> array(

								'event' 	=> $this->fb_custom_event,
								'params' 	=> $params
							)
						)
					));

					break;

				default :

					// Invalid type
					parent::error(__('No conversion tag type in action configuration', 'ws-form'));
			}
		}

		public function load_config($config) {

			$this->type = parent::get_config($config, 'action_' . $this->id . '_type');

			// Google Analytics
			$this->tag_action = parent::get_config($config, 'action_' . $this->id . '_tag_action');
			$this->tag_category = parent::get_config($config, 'action_' . $this->id . '_tag_category');
			$this->tag_label = parent::get_config($config, 'action_' . $this->id . '_tag_label');
			$this->tag_value = parent::get_config($config, 'action_' . $this->id . '_tag_value');
			$this->tag_value = parent::get_config($config, 'action_' . $this->id . '_tag_value');

			// Facebook - Standard
			$this->fb_standard_event = parent::get_config($config, 'action_' . $this->id . '_fb_standard_event');
			$this->fb_standard_properties = parent::get_config($config, 'action_' . $this->id . '_fb_standard_properties');

			// Facebook - Custom
			$this->fb_custom_event = parent::get_config($config, 'action_' . $this->id . '_fb_custom_event');
			$this->fb_custom_properties = parent::get_config($config, 'action_' . $this->id . '_fb_custom_properties');
		}

		// Get settings
		public function get_action_settings() {

			$settings = array(

				'meta_keys'		=> array(

					'action_' . $this->id . '_type',

					// Google Analytics
					'action_' . $this->id . '_tag_action',
					'action_' . $this->id . '_tag_category',
					'action_' . $this->id . '_tag_label',
					'action_' . $this->id . '_tag_value',

					// Facebook - Standard
					'action_' . $this->id . '_fb_standard_event',
					'action_' . $this->id . '_fb_standard_properties',

					// Facebook - Custom
					'action_' . $this->id . '_fb_custom_event',
					'action_' . $this->id . '_fb_custom_properties'
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

				// Type
				'action_' . $this->id . '_type'	=> array(

					'label'						=>	__('Type', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	array(

						array('value' => 'google', 'text' => __('Google Analytics', 'ws-form')),
						array('value' => 'facebook_standard', 'text' => __('Facebook (Standard)', 'ws-form')),
						array('value' => 'facebook_custom', 'text' => __('Facebook (Custom)', 'ws-form'))
					),
					'default'					=>	'apply_filter'
				),

				// Tag action
				'action_' . $this->id . '_tag_action'	=> array(

					'label'			=>	__('Event Action', 'ws-form'),
					'type'			=>	'text',
					'help'			=>	__('e.g. generate_lead', 'ws-form'),
					'select_list'	=>	true,
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_type',
							'meta_value'		=>	'google'
						)
					)
				),

				// Tag category
				'action_' . $this->id . '_tag_category'	=> array(

					'label'			=>	__('Event Category', 'ws-form'),
					'type'			=>	'text',
					'help'			=>	__('Leave blank for none.', 'ws-form'),
					'select_list'	=>	true,
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_type',
							'meta_value'		=>	'google'
						)
					)
				),

				// Tag label
				'action_' . $this->id . '_tag_label'	=> array(

					'label'			=>	__('Event Label', 'ws-form'),
					'type'			=>	'text',
					'help'			=>	__('Leave blank for none.', 'ws-form'),
					'select_list'	=>	true,
					'condition'		=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_type',
							'meta_value'		=>	'google'
						)
					)
				),

				// Tag value
				'action_' . $this->id . '_tag_value'	=> array(

					'label'			=>	__('Event Value', 'ws-form'),
					'type'			=>	'text',
					'help'			=>	__('Leave blank for none.', 'ws-form'),
					'select_list'	=>	true,
					'condition'		=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_type',
							'meta_value'		=>	'google'
						)
					)
				),

				// Facebook - Standard - Event name
				'action_' . $this->id . '_fb_standard_event'	=> array(

					'label'			=>	__('Event Name', 'ws-form'),
					'type'			=>	'select',
					'options'		=>	array(

						array('value' => 'AddPaymentInfo', 'text' => __('Add Payment Info', 'ws-form')),
						array('value' => 'AddToCart', 'text' => __('Add To Cart', 'ws-form')),
						array('value' => 'AddToWishlist', 'text' => __('Add To Wish list', 'ws-form')),
						array('value' => 'CompleteRegistration', 'text' => __('Complete Registration', 'ws-form')),
						array('value' => 'Contact', 'text' => __('Contact', 'ws-form')),
						array('value' => 'CustomizeProduct', 'text' => __('Customize Product', 'ws-form')),
						array('value' => 'Donate', 'text' => __('Donate', 'ws-form')),
						array('value' => 'FindLocation', 'text' => __('Find Location', 'ws-form')),
						array('value' => 'InitiateCheckout', 'text' => __('Initiate Checkout', 'ws-form')),
						array('value' => 'Lead', 'text' => __('Lead', 'ws-form')),
						array('value' => 'PageView', 'text' => __('Page View', 'ws-form')),
						array('value' => 'Purchase', 'text' => __('Purchase', 'ws-form')),
						array('value' => 'Schedule', 'text' => __('Schedule', 'ws-form')),
						array('value' => 'Search', 'text' => __('Search', 'ws-form')),
						array('value' => 'StartTrial', 'text' => __('Start Trial', 'ws-form')),
						array('value' => 'SubmitApplication', 'text' => __('Submit Application', 'ws-form')),
						array('value' => 'Subscribe', 'text' => __('Subscribe', 'ws-form')),
						array('value' => 'ViewContent', 'text' => __('View Content', 'ws-form'))
					),
					'default'		=>	'',
					'condition'		=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_type',
							'meta_value'		=>	'facebook_standard'
						)
					)
				),

				// Facebook - Standard - Object properties
				'action_' . $this->id . '_fb_standard_properties'	=> array(

					'label'			=>	__('Object Properties', 'ws-form'),
					'type'			=>	'repeater',
					'help'			=>	__('Add properties to the Facebook event', 'ws-form'),
					'meta_keys'		=>	array(

						'action_' . $this->id . '_fb_standard_property_key',
						'action_' . $this->id . '_fb_standard_property_value'
					),
					'select_list'	=>	true,
					'condition'		=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_type',
							'meta_value'		=>	'facebook_standard'
						)
					)
				),

				// Facebook - Standard - Object property key
				'action_' . $this->id . '_fb_standard_property_key'	=> array(

					'label'			=>	__('Key', 'ws-form'),
					'type'			=>	'select',
					'options'		=>	array(

						array('value' => 'content_category', 'text' => 'content_category'),
						array('value' => 'content_ids', 'text' => 'content_ids'),
						array('value' => 'content_name', 'text' => 'content_name'),
						array('value' => 'content_type', 'text' => 'content_type'),
						array('value' => 'contents', 'text' => 'contents'),
						array('value' => 'currency', 'text' => 'currency'),
						array('value' => 'num_items', 'text' => 'num_items'),
						array('value' => 'predicted_ltv', 'text' => 'predicted_ltv'),
						array('value' => 'search_string', 'text' => 'search_string'),
						array('value' => 'status', 'text' => 'status'),
						array('value' => 'value', 'text' => 'value'),
					),
					'default'		=>	'',
				),

				// Facebook - Standard - Object property value
				'action_' . $this->id . '_fb_standard_property_value'	=> array(

					'label'			=>	__('Value', 'ws-form'),
					'type'			=>	'text'
				),

				// Facebook - Custom - Object property value
				'action_' . $this->id . '_fb_custom_event'	=> array(

					'label'			=>	__('Event Name', 'ws-form'),
					'type'			=>	'text',
					'select_list'	=>	true,
					'condition'		=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_type',
							'meta_value'		=>	'facebook_custom'
						)
					)
				),

				// Facebook - Standard - Object properties
				'action_' . $this->id . '_fb_custom_properties'	=> array(

					'label'			=>	__('Object Properties', 'ws-form'),
					'type'			=>	'repeater',
					'help'			=>	__('Add properties to the Facebook event', 'ws-form'),
					'meta_keys'		=>	array(

						'action_' . $this->id . '_fb_custom_property_key',
						'action_' . $this->id . '_fb_custom_property_value'
					),
					'select_list'	=>	true,
					'condition'		=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_type',
							'meta_value'		=>	'facebook_custom'
						)
					)
				),

				// Facebook - Custom - Object property key
				'action_' . $this->id . '_fb_custom_property_key'	=> array(

					'label'			=>	__('Key', 'ws-form'),
					'type'			=>	'text'
				),

				// Facebook - Custom - Object property value
				'action_' . $this->id . '_fb_custom_property_value'	=> array(

					'label'			=>	__('Value', 'ws-form'),
					'type'			=>	'text'
				)
			);

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys);

			return $meta_keys;
		}
	}

	new WS_Form_Action_Conversion();
