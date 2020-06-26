<?php

	class WS_Form_Action_Akismet_V1 extends WS_Form_Action {

		public $id = 'akismetv1';
		public $pro_required = false;
		public $label;
		public $label_action;
		public $events;
		public $multiple = false;
		public $configured = true;
		public $priority = 25;
		public $can_repost = false;
		public $form_add = false;

		// Config
		public $api_key;
		public $api_endpoint;
		public $field_email;
		public $field_mapping;
		public $spam_level_reject = '';
		public $admin_no_run;
		public $test;

		public function __construct() {

			// Determine if we can register this action
			if(($this->api_key = self::get_key()) === false) { return false; }

			// Build 
			$this->api_endpoint = 'https://' . $this->api_key . '.rest.akismet.com/1.1/';

			// Set label
			$this->label = __('Akismet', 'ws-form');

			// Set label for actions pull down
			$this->label_action = __('Spam Check with Akismet', 'ws-form');

			// Events
			$this->events = array('save', 'submit');

			// Register action
			parent::register($this);

			// Register config filters
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);
		}

		public function get_key() {

			return is_callable(array('Akismet', 'get_api_key')) ? Akismet::get_api_key() : ((function_exists('akismet_get_key')) ? akismet_get_key() : false);
		}

		public function post($form, &$submit, $config) {

			// Load config
			self::load_config($config);

			// Do not run if administator
			if($this->admin_no_run && WS_Form_Common::can_user('manage_options_wsform')) { return true; }

			// Reset spam level
			$spam_level = 0;

			// Build post request
			$data = array(

				'blog'			=>	get_option('home'),
				'blog_lang'		=>	get_locale(),
				'blog_charset'	=>	get_locale(),
				'user_ip'		=>	WS_Form_Common::get_http_env(array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR')),
				'user_agent'	=>	WS_Form_Common::get_http_env(array('HTTP_USER_AGENT')),
				'referrer'		=>	WS_Form_Common::get_http_env(array('HTTP_REFERER')),
				'comment_type'	=>	'contact-form',
			);

			// Build comment_email
			if(
				($this->field_email != '') &&
				isset($submit->meta) &&
				isset($submit->meta[WS_FORM_FIELD_PREFIX . $this->field_email]) &&
				isset($submit->meta[WS_FORM_FIELD_PREFIX . $this->field_email]['value'])
			) {

				$email_address = $submit->meta[WS_FORM_FIELD_PREFIX . $this->field_email]['value'];
				if(filter_var($email_address, FILTER_VALIDATE_EMAIL)) { $data['comment_author_email'] = $email_address; }
			}

			// Build comment_content
			$comment_content_array = array();
			foreach($this->field_mapping as $field_map) {

				$field_id = $field_map['ws_form_field'];
				$submit_value = parent::get_submit_value($submit, WS_FORM_FIELD_PREFIX . $field_id, false);
				if($submit_value !== false) {

					$comment_content_array[] = $submit_value;
				}
			}
			if(count($comment_content_array) > 0) {

				$data['commment_content'] = implode("\n", $comment_content_array);
			}

			// Test
			if($this->test) { $data['is_test'] = true; }

			// Add permalink if available
			if($permalink = get_permalink()) { $data['permalink'] = $permalink; }

			// Build query string
			$query_string = http_build_query($data);

			// POST
			$api_response = parent::api_call($this->api_endpoint, 'comment-check', 'POST', $query_string, false, false, false, false, 'text/plain', 'application/x-www-form-urlencoded');

			// Check for X-akismet-pro-tip header
			if(($pro_tip = parent::api_get_header($api_response, 'X-akismet-pro-tip')) !== false) {

				switch($pro_tip) {

					case 'discard' :

						$spam_level = WS_FORM_SPAM_LEVEL_MAX;
						break;
				}
			}

			// Process response
			if($spam_level == 0) {

				switch($api_response['http_code']) {

					case 200 :

						// Get response string
						$response = trim($api_response['response']);
 						switch($response) {

							// Not spam
							case 'false' :

								parent::success(__('Submitted form content to Akismet (Not spam)', 'ws-form'));
								break;

							case 'true' :

								$spam_level = (WS_FORM_SPAM_LEVEL_MAX * 0.75);		// 0.75 Shows up as orange in submit table
								parent::error(__('Submitted form content to Akismet (Detected spam)', 'ws-form'));
								break;

							case '' :

								parent::error(__('An error occurred when submitting the form content to Akismet', 'ws-form'));
								break;
						}
						break;

					default :

						parent::error(__('An error occurred when submitting the form content to Akismet', 'ws-form'));
				}
			}	

			// Set spam level on submit record
			if(is_null(parent::$spam_level) || (parent::$spam_level < $spam_level)) { parent::$spam_level = $spam_level; }

			// Check spam level (Return halt if submission should be rejected)
			$this->spam_level_reject = intval($this->spam_level_reject);
			if($this->spam_level_reject > 0) {

				if($spam_level >= $this->spam_level_reject) { return 'halt'; }
			}

			return $spam_level;
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
					'help'							=>	__('Select which field contains the email address of the person submitting the form', 'ws-form')
				),

				// Field mapping
				'action_' . $this->id . '_field_mapping'	=> array(

					'label'						=>	__('Fields To Check For Spam', 'ws-form'),
					'type'						=>	'repeater',
					'help'						=>	__('Select which WS Form fields to Akismet will check for spam', 'ws-form'),
					'meta_keys'					=>	array(

						'ws_form_field_edit'
					)
				),

				// List ID
				'action_' . $this->id . '_spam_level_reject'	=> array(

					'label'						=>	__('Submission Rejection', 'ws-form'),
					'type'						=>	'select',
					'help'						=>	__('Reject submission if spam level meets this criteria', 'ws-form'),
					'options'					=>	array(

						array('value' => '', 'text' => __('Accept All Spam', 'ws-form')),
						array('value' => '75', 'text' => __('Reject Suspected Spam', 'ws-form')),
						array('value' => '100', 'text' => __('Reject Blatant Spam', 'ws-form')),
					)
				),

				// Administrator
				'action_' . $this->id . '_admin_no_run'	=> array(

					'label'						=>	__('Bypass If Administrator', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	__('If checked, this action will not run if you are signed in as an administrator', 'ws-form'),
					'default'					=>	''
				),

				// Test
				'action_' . $this->id . '_test'	=> array(

					'label'						=>	__('Test Mode', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	__('If checked, Akismet will run in test mode', 'ws-form'),
					'default'					=>	''
				)
			);

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys);

			return $meta_keys;
		}

		public function load_config($config) {

			// Get configuration
			$this->field_email =		parent::get_config($config, 'action_' . $this->id . '_field_email');
			$this->field_mapping =		parent::get_config($config, 'action_' . $this->id . '_field_mapping');
			$this->spam_level_reject =	parent::get_config($config, 'action_' . $this->id . '_spam_level_reject', 75);
			$this->admin_no_run =		parent::get_config($config, 'action_' . $this->id . '_admin_no_run');
			$this->test =				parent::get_config($config, 'action_' . $this->id . '_test');
		}

		// Get settings
		public function get_action_settings() {

			$settings = array(

				'meta_keys'		=> array(

					'action_' . $this->id . '_field_email',
					'action_' . $this->id . '_field_mapping',
					'action_' . $this->id . '_spam_level_reject',
					'action_' . $this->id . '_test',
					'action_' . $this->id . '_admin_no_run'
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

	new WS_Form_Action_Akismet_V1();
