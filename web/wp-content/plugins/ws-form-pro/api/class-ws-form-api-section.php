<?php

	class WS_Form_API_Section extends WS_Form_API {
	
		public function __construct() {

			// Call parent on WS_Form_API
			parent::__construct();
		}

		// API - POST
		public function api_post($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_section = new WS_Form_Section();
			$ws_form_section->id = self::api_get_id($parameters);
			$ws_form_section->form_id = self::api_get_form_id($parameters);
			$ws_form_section->group_id = self::api_get_group_id($parameters);

			// Get next sibling ID
			$next_sibling_id = intval(WS_Form_Common::get_query_var_nonce('next_sibling_id', 0, $parameters));

			// Set breakpoint meta
			$ws_form_section->db_set_breakpoint_size_meta();

			// Create section
			$ws_form_section->db_create($next_sibling_id);

			// Build api_json_response
			$api_json_response = $ws_form_section->db_read();

			// Add empty fields element
			$api_json_response->fields = [];

			// Describe transaction for history
			$history = array(

				'object'		=>	'section',
				'method'		=>	'post',
				'label'			=>	WS_FORM_DEFAULT_SECTION_NAME,
				'group_id'		=>	$ws_form_section->group_id,
				'id'			=>	$ws_form_section->id
			);

			// Update checksum
			$ws_form_section->db_checksum();

			parent::api_json_response($api_json_response, $ws_form_section->form_id, $history);
		}

		// API - PUT
		public function api_put($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_section = new WS_Form_Section();
			$ws_form_section->id = self::api_get_id($parameters);
			$ws_form_section->form_id = self::api_get_form_id($parameters);

			// Get section data
			$section_object = WS_Form_Common::get_query_var_nonce('section', false, $parameters);
			if(!$section_object) { return false; }

			// Put section
			$ws_form_section->db_update_from_object($section_object, false);

			// Describe transaction for history
			$history = array(

				'object'		=>	'section',
				'method'		=>	WS_Form_Common::get_query_var_nonce('history_method', 'put'),
				'label'			=>	$ws_form_section->db_get_label($ws_form_section->table_name, $ws_form_section->id),
				'group_id'		=>	$ws_form_section->group_id,
				'id'			=>	$ws_form_section->id
			);

			// Update checksum
			$ws_form_section->db_checksum();

			// Send JSON response
			parent::api_json_response([], $ws_form_section->form_id, isset($section_object->history_suppress) ? false : $history);
		}

		// API - PUT - SORT INDEX
		public function api_put_sort_index($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_section = new WS_Form_Section();
			$ws_form_section->id = self::api_get_id($parameters);
			$ws_form_section->form_id = self::api_get_form_id($parameters);
			$ws_form_section->group_id = self::api_get_group_id($parameters);

			// Get next sibling ID
			$next_sibling_id = intval(WS_Form_Common::get_query_var_nonce('next_sibling_id', 0, $parameters));

			// Process sort index
			$ws_form_section->db_object_sort_index($ws_form_section->table_name, 'group_id', $ws_form_section->group_id, $next_sibling_id);

			// Describe transaction for history
			$history = array(

				'object'		=>	'section',
				'method'		=>	'put_sort_index',
				'label'			=>	$ws_form_section->db_get_label($ws_form_section->table_name, $ws_form_section->id),
				'group_id'		=>	$ws_form_section->group_id,
				'id'			=>	$ws_form_section->id
			);

			// Update checksum
			$ws_form_section->db_checksum();

			// Send JSON response
			parent::api_json_response([], $ws_form_section->form_id, $history);
		}

		// API - PUT - CLONE
		public function api_put_clone($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_section = new WS_Form_Section();
			$ws_form_section->id = self::api_get_id($parameters);
			$ws_form_section->form_id = self::api_get_form_id($parameters);

			// Read
			$ws_form_section->db_read();

			// Get group ID
			$ws_form_section->group_id = $ws_form_section->db_get_group_id();

			// Get next sibling ID
			$next_sibling_id = intval(WS_Form_Common::get_query_var_nonce('next_sibling_id', 0, $parameters));

			// Get sort_index
			$ws_form_section->sort_index = $ws_form_section->db_object_sort_index_get($ws_form_section->table_name, 'group_id', $ws_form_section->group_id, $next_sibling_id);

			// Rename
			$ws_form_section->label = sprintf(__('%s (Copy)', 'ws-form'), $ws_form_section->label);

			// Clone
			$ws_form_section->id = $ws_form_section->db_clone();

			// Remember label before change
			$label = $ws_form_section->label;

			// Build api_json_response
			$api_json_response = $ws_form_section->db_read(true, true);

			// Describe transaction for history
			$history = array(

				'object'		=>	'section',
				'method'		=>	'put_clone',
				'label'			=>	$label,
				'group_id'		=>	$ws_form_section->group_id,
				'id'			=>	$ws_form_section->id
			);

			// Update checksum
			$ws_form_section->db_checksum();

			parent::api_json_response($api_json_response, $ws_form_section->form_id, $history);
		}

		// API - DELETE
		public function api_delete($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_section = new WS_Form_Section();
			$ws_form_section->id = self::api_get_id($parameters);
			$ws_form_section->form_id = self::api_get_form_id($parameters);

			// Get label (We do this because once its deleted, we can't reference it)
			$label = $ws_form_section->db_get_label($ws_form_section->table_name, $ws_form_section->id);

			// Delete section
			$ws_form_section->db_delete();

			// Describe transaction for history
			$history = array(

				'object'		=>	'section',
				'method'		=>	'delete',
				'label'			=>	$label,
				'group_id'		=>	$ws_form_section->group_id,
				'id'			=>	$ws_form_section->id
			);

			// Update checksum
			$ws_form_section->db_checksum();

			// Send JSON response
			parent::api_json_response([], $ws_form_section->form_id, $history);
		}

		// Get form ID
		public function api_get_form_id($parameters) {

			return intval(WS_Form_Common::get_query_var_nonce('id', 0, $parameters));
		}

		// Get group ID
		public function api_get_group_id($parameters) {

			return intval(WS_Form_Common::get_query_var_nonce('group_id', 0, $parameters));
		}

		// Get section ID
		public function api_get_id($parameters) {

			return intval(WS_Form_Common::get_query_var_nonce('section_id', 0, $parameters));
		}
	}