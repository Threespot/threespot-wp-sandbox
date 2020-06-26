<?php

	class WS_Form_Action_Database extends WS_Form_Action {

		public $id = 'database';
		public $pro_required = false;
		public $label;
		public $label_action;
		public $multiple = false;
		public $events;
		public $configured = true;
		public $priority = array(0, 200);
		public $can_repost = false;
		public $form_add = true;

		// Config
		public $field_filter;
		public $field_filter_mapping;
		public $expire;
		public $expire_duration;

		public function __construct() {

			// Set label
			$this->label = __('Database', 'ws-form');

			// Set label for actions pull down
			$this->label_action = __('Save Submission', 'ws-form');

			// Events
			$this->events = array('save', 'submit');

			// Register config filters
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);

			// Scheduled event
		    if(!wp_next_scheduled('ws_form_wp_cron_action_database')) {

				wp_schedule_event(time(), 'hourly', 'ws_form_wp_cron_action_database');
				add_action('ws_form_wp_cron_action_database', array($this, 'action_database_wp_cron'));
			}

			// Register action
			parent::register($this);
		}

		public function post($form, &$submit, $config, $count) {

			// Load config
			self::load_config($config);

			// Set expiry
			if($this->expire) {

				$expire_duration = intval($this->expire_duration);

				if($expire_duration > 0) {

					$submit->date_expire = WS_Form_Common::get_mysql_date('+' . $expire_duration . ' days');
				}
			}

			// If form being submitted, set status to publish
			if($submit->post_mode == 'submit') { $submit->status = 'publish'; }

			// Spam check - If spam_level > threshold put this submission in the 'spam' status
			$submit->spam_level = parent::$spam_level;
			$spam_level = intval($submit->spam_level);
			$spam_threshold = intval(WS_Form_Common::get_object_meta_value($form, 'spam_threshold', 50));
			if($spam_level >= $spam_threshold) { $submit->status = 'spam'; }

			// Add submit
			if($submit->id == 0) { $submit->db_create(); }

			// Stamp
			if($count === 1) { $submit->db_stamp(); }

			// Filter data
			if($this->field_filter != '') {

				// Get array of all mapped fields
				$field_id_array = array();
				if(is_array($this->field_filter_mapping)) {

					foreach($this->field_filter_mapping as $field) {

						// Skip blank records
						if(!isset($field['ws_form_field']) || ($field['ws_form_field'] == '')) { continue; }

						// Get field ID to filter
						$field_id_array[$field['ws_form_field']] = true;
					}
				}

				// Run through submit meta
				if(isset($submit->meta)) {

					// Process according to logic
					$field_filter_logic = $this->field_filter;

					foreach($submit->meta as $key => $field) {

						$field_found = (isset($field_id_array[$field['id']]));

						switch($field_filter_logic) {

							case 'include' :

								if(!$field_found) { unset($submit->meta[$key]); }
								break;

							case 'exclude' :

								if($field_found) { unset($submit->meta[$key]); }
								break;
						}
					}
				}
			}

			// Update
			$submit->db_update();

			// Success
			parent::success(array(__('Form saved to database', 'ws-form')));

			return true;
		}

		public function load_config($config) {

			$this->field_filter = parent::get_config($config, 'action_' . $this->id . '_field_filter');
			$this->field_filter_mapping = parent::get_config($config, 'action_' . $this->id . '_field_filter_mapping');
			$this->expire = parent::get_config($config, 'action_' . $this->id . '_expire');
			$this->expire_duration = parent::get_config($config, 'action_' . $this->id . '_expire_duration', 90);
		}

		// Meta keys for this action
		public function config_meta_keys($meta_keys = array(), $form_id = 0) {

			// Build config_meta_keys
			$config_meta_keys = array(

				// Field filter
				'action_' . $this->id . '_field_filter'	=> array(

					'label'						=>	__('Fields To Save', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	array(

						array('value' => '', 'text' => __('Save All Fields', 'ws-form')),
						array('value' => 'include', 'text' => __('Save Filtered Fields', 'ws-form')),
						array('value' => 'exclude', 'text' => __('Exclude Filtered Fields', 'ws-form'))
					),
					'help'						=>	__('Select which fields should be saved to submissions.', 'ws-form'),
					'default'					=>	''
				),

				// Field mapping
				'action_' . $this->id . '_field_filter_mapping'	=> array(

					'label'						=>	__('Filtered Fields', 'ws-form'),
					'type'						=>	'repeater',
					'help'						=>	__('Select which fields to filter.', 'ws-form'),
					'meta_keys'					=>	array(

						'ws_form_field_save'
					),
					'default'					=>	'',
					'condition'					=>	array(

						array(

							'logic'			=>	'!=',
							'meta_key'		=>	'action_' . $this->id . '_field_filter',
							'meta_value'	=>	''
						)
					)
				),

				// Expire
				'action_' . $this->id . '_expire'	=> array(

					'label'						=>	__('Auto Expire Submissions', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	__('Check this box to have submissions automatically delete after a specified number of days.', 'ws-form'),
					'default'					=>	''
				),

				'action_' . $this->id . '_expire_duration'	=> array(

					'label'						=>	__('Expiry Duration (Days)', 'ws-form'),
					'type'						=>	'number',
					'help'						=>	__('How many days until a submission is automatically deleted?', 'ws-form'),
					'default'					=>	'',
					'min'						=>	1,
					'step'						=>	1,
					'default'					=>	'90',
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							'meta_key'		=>	'action_' . $this->id . '_expire',
							'meta_value'	=>	'on'
						)
					)
				),
			);

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys);

			return $meta_keys;
		}

		// Get settings
		public function get_action_settings() {

			$settings = array(

				'meta_keys'		=> array(

					'action_' . $this->id . '_field_filter',
					'action_' . $this->id . '_field_filter_mapping',
					'action_' . $this->id . '_expire',
					'action_' . $this->id . '_expire_duration'
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

		// Scheduled event
		public function action_database_wp_cron() {

			// Move expired submissions to trash
			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->db_delete_expired();
		}

		// Deactivate
		public function deactivate() {

			wp_clear_scheduled_hook('ws_form_wp_cron_action_database');
		}
	}

	new WS_Form_Action_Database();
