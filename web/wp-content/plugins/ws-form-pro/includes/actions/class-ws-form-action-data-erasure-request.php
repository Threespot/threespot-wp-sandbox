<?php

	class WS_Form_Action_Data_Erasure_Request extends WS_Form_Action {

		public $id = 'data_erasure_request';
		public $pro_required = false;
		public $label;
		public $label_action;
		public $multiple = false;
		public $events;
		public $configured = true;
		public $priority = 150;
		public $can_repost = false;
		public $form_add = false;

		// Config
		public $field_email;

		// Constantsfalse, 
		const ERASER_ACTION_TYPE = 'remove_personal_data';

		public function __construct() {

			// Determine if we can register this action
			if(!self::wordpress_validate()) { return false; }

			// Set label
			$this->label = __('Data Erasure Request', 'ws-form');

			// Set label for actions pull down
			$this->label_action = __('Data Erasure Request', 'ws-form');

			// Events
			$this->events = array('submit');

			// Register config filters
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);
			add_filter('wp_privacy_personal_data_erasers', array($this, 'eraser_register'), 10);

			// Register action
			parent::register($this);
		}

		public function wordpress_validate() {

			// Check required WordPress functions exist
			return (function_exists('wp_create_user_request') && function_exists('wp_send_user_request'));
		}

		public function post($form, &$submit, $config) {

			// Load config
			self::load_config($config);

			// Email field is configured
			if($this->field_email == '') { parent::error(__('Email field not configured', 'ws-form')); return false; }

			// Get email address
			if(!isset($submit->meta)) { parent::error(__('No submit meta found', 'ws-form')); return false; }
			$submit_meta = $submit->meta;

			if(
				!isset($submit_meta[WS_FORM_FIELD_PREFIX . $this->field_email]) ||
				!isset($submit_meta[WS_FORM_FIELD_PREFIX . $this->field_email]['value'])

			) { parent::error(__('Unable to find email field in submit data', 'ws-form')); }

			$email_address = $submit_meta[WS_FORM_FIELD_PREFIX . $this->field_email]['value'];

			// Validate email address
			if(!filter_var($email_address, FILTER_VALIDATE_EMAIL)) { parent::error(__('Invalid email address', 'ws-form')); return false; }

			// Create user request
			$request_id = wp_create_user_request($email_address, self::ERASER_ACTION_TYPE);

			// Check for request error
			if(is_wp_error($request_id)) {

				$errors = array();
				foreach($request_id->errors as $error) {

					foreach($error as $error_id => $error_message) {

						$errors[] = $error_message;
					}
				}

				parent::error($errors);

				return false;
			}
			if(!$request_id) { parent::error(__('Error making delete data request', 'ws-form')); return false; }

			// Send user request
			wp_send_user_request($request_id);

			// Success
			parent::success(array(__('Delete data request successfully processed', 'ws-form') . ' (' . esc_html__('ID', 'ws-form') . ': ' . $request_id . ')'));

			return true;
		}

		public function eraser($email_address, $page = 1) {

			// Delete submits containing email address provided
			$ws_form_submit = new WS_Form_Submit();
			$db_eraser_return = $ws_form_submit->db_eraser($email_address);

			// Return
			if($db_eraser_return !== false) {

				return $db_eraser_return;	

			} else {

				return array(

					'items_removed' => false,
					'items_retained' => false,
					'messages' => array(),
					'done' => true,
				);
			}
		}

		public function eraser_register($erasers) {

			$erasers[WS_FORM_USER_REQUEST_IDENTIFIER] = array(

				'eraser_friendly_name' => WS_FORM_NAME_PRESENTABLE,
				'callback'             => array($this, 'eraser')
			);

			return $erasers;
		}

		public function load_config($config) {

			$this->field_email = parent::get_config($config, 'action_' . $this->id . '_field_email');
		}

		// Meta keys for this action
		public function config_meta_keys($meta_keys = array(), $form_id = 0) {

			// Build config_meta_keys
			$config_meta_keys = array(

				// Email field
				'action_' . $this->id . '_field_email'	=> array(

					'label'							=>	__('Email Field', 'ws-form'),
					'type'							=>	'select',
					'options'						=>	'fields',
					'options_blank'					=>	__('Select...', 'ws-form'),
					'fields_filter_type'			=>	array('email'),
					'help'							=>	__('Select which field contains the email address for the delete data request', 'ws-form')
				)
			);

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys);

			return $meta_keys;
		}

		// Get settings
		public function get_action_settings() {

			$settings = array(

				'meta_keys'		=> array(

					'action_' . $this->id . '_field_email'
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
	}

	new WS_Form_Action_Data_Erasure_Request();
