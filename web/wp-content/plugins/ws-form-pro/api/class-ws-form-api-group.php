<?php

	class WS_Form_API_Group extends WS_Form_API {

		public function __construct() {

			// Call parent on WS_Form_API
			parent::__construct();
		}

		// API - POST
		public function api_post($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_group = new WS_Form_Group();
			$ws_form_group->id = self::api_get_id($parameters);
			$ws_form_group->form_id = self::api_get_form_id($parameters);

			$api_json_response = [];

			// Save tab index
			$ws_form_group->db_tab_index_save($parameters);

			// Get next sibling ID
			$next_sibling_id = intval(WS_Form_Common::get_query_var_nonce('next_sibling_id', 0, $parameters));

			// Create group
			$ws_form_group->db_create($next_sibling_id);

			// Build api_json_response
			$api_json_response = $ws_form_group->db_read(true, true);	// True on get sections because we create a default first section in db_create

			// Describe transaction for history
			$history = array(

				'object'		=>	'group',
				'method'		=>	'post',
				'label'			=>	WS_FORM_DEFAULT_GROUP_NAME,
				'form_id'		=>	$ws_form_group->form_id,
				'id'			=>	$ws_form_group->id
			);

			// Update checksum
			$ws_form_group->db_checksum();

			// Send JSON response
			parent::api_json_response($api_json_response, $ws_form_group->form_id, $history);
		}

		// API - PUT
		public function api_put($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_group = new WS_Form_Group();
			$ws_form_group->id = self::api_get_id($parameters);
			$ws_form_group->form_id = self::api_get_form_id($parameters);

			// Get group data
			$group_object = WS_Form_Common::get_query_var_nonce('group', false, $parameters);
			if(!$group_object) { return false; }

			// Put group
			$ws_form_group->db_update_from_object($group_object, false);

			// Describe transaction for history
			$history = array(

				'object'		=>	'group',
				'method'		=>	WS_Form_Common::get_query_var_nonce('history_method', 'put'),
				'label'			=>	$ws_form_group->db_get_label($ws_form_group->table_name, $ws_form_group->id),
				'form_id'		=>	$ws_form_group->form_id,
				'id'			=>	$ws_form_group->id
			);

			// Update checksum
			$ws_form_group->db_checksum();

			// Send JSON response
			parent::api_json_response([], $ws_form_group->form_id, isset($group_object->history_suppress) ? false : $history);
		}

		// API - PUT - SORT INDEX
		public function api_put_sort_index($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_group = new WS_Form_Group();
			$ws_form_group->id = self::api_get_id($parameters);
			$ws_form_group->form_id = self::api_get_form_id($parameters);

			// Store tab index
			$ws_form_group->db_tab_index_save($parameters);

			// Get next sibling ID
			$next_sibling_id = intval(WS_Form_Common::get_query_var_nonce('next_sibling_id', 0, $parameters));

			// Process sort index
			$ws_form_group->db_object_sort_index($ws_form_group->table_name, 'form_id', $ws_form_group->form_id, $next_sibling_id);

			// Describe transaction for history
			$history = array(

				'object'		=>	'group',
				'method'		=>	'put_sort_index',
				'label'			=>	$ws_form_group->db_get_label($ws_form_group->table_name, $ws_form_group->id),
				'form_id'		=>	$ws_form_group->form_id,
				'id'			=>	$ws_form_group->id
			);

			// Update checksum
			$ws_form_group->db_checksum();

			// Send JSON response
			parent::api_json_response([], $ws_form_group->form_id, $history);
		}

		// API - PUT - CLONE
		public function api_put_clone($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_group = new WS_Form_Group();
			$ws_form_group->id = self::api_get_id($parameters);
			$ws_form_group->form_id = self::api_get_form_id($parameters);

			// Save tab index
			$ws_form_group->db_tab_index_save($parameters);

			// Read
			$ws_form_group->db_read();

			// Get next sibling ID
			$next_sibling_id = intval(WS_Form_Common::get_query_var_nonce('next_sibling_id', 0, $parameters));

			// Get sort_index
			$ws_form_group->sort_index = $ws_form_group->db_object_sort_index_get($ws_form_group->table_name, 'form_id', $ws_form_group->form_id, $next_sibling_id);

			// Rename
			$ws_form_group->label = sprintf(__('%s (Copy)', 'ws-form'), $ws_form_group->label);

			// Clone
			$ws_form_group->id = $ws_form_group->db_clone();

			// Remember label before change
			$label = $ws_form_group->label;

			// Build api_json_response
			$api_json_response = $ws_form_group->db_read(true, true);

			// Describe transaction for history
			$history = array(

				'object'		=>	'group',
				'method'		=>	'put_clone',
				'label'			=>	$label,
				'form_id'		=>	$ws_form_group->form_id,
				'id'			=>	$ws_form_group->id
			);

			// Update checksum
			$ws_form_group->db_checksum();

			parent::api_json_response($api_json_response, $ws_form_group->form_id, $history);
		}

		// API - DELETE
		public function api_delete($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_group = new WS_Form_Group();
			$ws_form_group->id = self::api_get_id($parameters);
			$ws_form_group->form_id = self::api_get_form_id($parameters);

			// Save tab index
			$ws_form_group->db_tab_index_save($parameters);

			// Get label (We do this because once its deleted, we can't reference it)
			$label = $ws_form_group->db_get_label($ws_form_group->table_name, $ws_form_group->id);

			// Delete group
			$ws_form_group->db_delete();

			// Describe transaction for history
			$history = array(

				'object'		=>	'group',
				'method'		=>	'delete',
				'label'			=>	$label,
				'form_id'		=>	$ws_form_group->form_id,
				'id'			=>	$ws_form_group->id
			);

			// Update checksum
			$ws_form_group->db_checksum();

			// Send JSON response
			parent::api_json_response([], $ws_form_group->form_id, $history);
		}

		// Get form ID
		public function api_get_form_id($parameters) {

			return intval(WS_Form_Common::get_query_var_nonce('id', 0, $parameters));
		}

		// Get group ID
		public function api_get_id($parameters) {

			return intval(WS_Form_Common::get_query_var_nonce('group_id', 0, $parameters));
		}

	}