<?php

	class WS_Form_Action_Search extends WS_Form_Action {

		const QUERY_VAR_FORM_ID = 'wsf-search-form-id';

		public $id = 'search';
		public $pro_required = false;
		public $label;
		public $label_action;
		public $events;
		public $multiple = false;
		public $configured = true;
		public $priority = 150;
		public $can_repost = false;
		public $form_add = false;

		// Config
		public $field_id_search_query;

		public function __construct() {

			// Set label
			$this->label = __('Search', 'ws-form');

			// Set label for actions pull down
			$this->label_action = __('Run WordPress Search', 'ws-form');

			// Events
			$this->events = array('submit');

			// Register action
			parent::register($this);

			// Register config filters
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);

			if(!is_admin()) {

				// Search filters
				add_filter('pre_get_posts', array($this, 'pre_get_posts'));
			}
		}

		public function pre_get_posts($query) {
 
 			// Get form ID
 			$form_id = intval(WS_Form_Common::get_query_var(self::QUERY_VAR_FORM_ID));
 			if($form_id == 0) { return $query; }

 			// Check if this is a search
			if(!$query->is_search || is_admin()) { return $query; }

			// Read form
			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $form_id;
			$form_object = $ws_form_form->db_read();

			// Get search action
			$actions = WS_Form_Action::get_form_actions($form_object, false, 0, $this->id);
			if(count($actions) != 1) { return $query; }

			// Get config
			$config = $actions[0];

			// Load config
			self::load_config($config);

			// Post type filtering
			$post_type_filter = array();
			if(count($this->post_types) > 0) {

				foreach($this->post_types as $row) {

					if(isset($row['action_' . $this->id . '_post_type'])) {

						$post_type = $row['action_' . $this->id . '_post_type'];
						if(post_type_exists($post_type)) { $post_type_filter[] = $post_type; }
					}
				}
			}

			if(count($post_type_filter) > 0) {

				$query->set('post_type', $post_type_filter);
			}

		 	return $query;
		}

		public function post($form, &$submit, $config) {

			// Load config
			self::load_config($config);

			// Get search query
			$search_query = parent::get_submit_value($submit, WS_FORM_FIELD_PREFIX . $this->field_id_search_query, '');

			// Build search URL
			$params = array(

				's' => $search_query,
				self::QUERY_VAR_FORM_ID => $form->id,
				WS_FORM_POST_NONCE_FIELD_NAME => wp_create_nonce(WS_FORM_POST_NONCE_ACTION_NAME)
			);
			$search_url = sprintf('/?%s', http_build_query($params));

			// Redirect
			parent::success(__('Search added to queue: ', 'ws-form') . $search_query, array(

				array(

					'action'					=> 'redirect',
					'url' 						=> WS_Form_Common::parse_variables_process($search_url, $form, $submit),
				)
			));
		}

		public function load_config($config) {

			$this->field_id_search_query = parent::get_config($config, 'action_' . $this->id . '_field_id_search_query');
			$this->post_types = parent::get_config($config, 'action_' . $this->id . '_post_types', array());
			if(!is_array($this->post_types)) { $this->post_types = array(); }
		}

		// Get settings
		public function get_action_settings() {

			$settings = array(

				'meta_keys'		=> array(

					'action_' . $this->id . '_field_id_search_query',
					'action_' . $this->id . '_post_types'
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

				// Opt-In field
				'action_' . $this->id . '_field_id_search_query' => array(

					'label'					=>	__('Search Query Field', 'ws-form'),
					'type'					=>	'select',
					'options'				=>	'fields',
					'options_blank'			=>	__('Select...', 'ws-form'),
					'fields_filter_type'	=>	array('text', 'search'),
					'help'					=>	__('Text field containing the search query', 'ws-form')
				),

				// Post types
				'action_' . $this->id . '_post_types'	=> array(

					'label'					=>	__('Filter By Post Types', 'ws-form'),
					'type'					=>	'repeater',
					'help'					=>	__('Enter post types to filter search by.', 'ws-form'),
					'meta_keys'				=>	array(

						'action_' . $this->id . '_post_type'
					)
				),

				// Post type
				'action_' . $this->id . '_post_type'	=> array(

					'label'					=>	__('Post Types', 'ws-form'),
					'type'					=>	'select',
					'options'				=>	array(),
					'options_blank'			=>	__('Select...', 'ws-form'),
				),
			);

			// Post types
			$post_types_exclude = array('attachment');
			$post_types = get_post_types(array('show_in_menu' => true), 'objects', 'or');

			foreach($post_types as $post_type) {

				$post_type_name = $post_type->name;

				if(in_array($post_type_name, $post_types_exclude)) { continue; }

				$config_meta_keys['action_' . $this->id . '_post_type']['options'][] = array('value' => $post_type_name, 'text' => $post_type->labels->singular_name);
			}

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys);

			return $meta_keys;
		}
	}

	new WS_Form_Action_Search();
