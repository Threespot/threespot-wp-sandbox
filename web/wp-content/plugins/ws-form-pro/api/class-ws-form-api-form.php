<?php

	class WS_Form_API_Form extends WS_Form_API {

		public function __construct() {

			// Call parent on WS_Form_Form
			parent::__construct();
		}

		// API - GET - ALL
		public function api_get_full($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { parent::api_access_denied(); }

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = self::api_get_id($parameters);

			// Check if this is coming from the admin
			$is_admin = (WS_Form_Common::get_query_var_nonce('form_is_admin', 'false', $parameters) == 'true');

			try {

				// Get label
				$label = $ws_form_form->db_get_label();

			} catch (Exception $e) {

				parent::api_throw_error($e->getMessage());
			}

			if($is_admin == 'true') {

				// Describe transaction for history
				$history = array(

					'object'		=>	'form',
					'method'		=>	'get',
					'label'			=>	$label,
					'id'			=>	$ws_form_form->id
				);

			} else {

				$history = false;
			}

			// Send JSON response (By passing form ID, it will get returned in default JSON response)
			parent::api_json_response([], $ws_form_form->id, $history, true, false, !$is_admin);
		}

		// API - GET - Published
		public function api_get_published($parameters) {

			// Send JSON response (By passing form ID, it will get returned in default JSON response)
			parent::api_json_response([], self::api_get_id($parameters), false, true, true);
		}

		// API - GET - Checksum
		public function api_get_checksum($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { parent::api_access_denied(); }

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = self::api_get_id($parameters);
			$checksum = $ws_form_form->db_get_checksum();

			// If checksum not yet calculated, calculate it
			if((is_null($checksum)) || ($checksum == '')) {

				$checksum = $ws_form_form->db_checksum();
			}

			// Send JSON response
			parent::api_json_response([], $ws_form_form->id, false, false);
		}

		// API - POST
		public function api_post($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('create_form')) { parent::api_access_denied(); }

			$api_json_response = [];

			$ws_form_form = new WS_Form_Form();

			// Create form
			$ws_form_form->db_create();

			// Build api_json_response
			$api_json_response = $ws_form_form->db_read();

			// Add default form groups, sections, fields
			$api_json_response->groups = [];

			// Update checksum
			$ws_form_form->db_checksum();

			// Send JSON response
			parent::api_json_response($api_json_response, $ws_form_form->id, false);
		}

		// API - POST - Upload - JSON
		public function api_post_upload_json($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('import_form')) { parent::api_access_denied(); }

			$form_id = self::api_get_id($parameters);

			$ws_form_form = new WS_Form_Form();

			if($form_id == 0) {

				$ws_form_form->db_create();				

			} else {

				$ws_form_form->id = $form_id;
			}

			// Get files
			if(!isset($_FILES)) { self::api_throw_error(__('No files found', 'ws-form')); }
			if(!isset($_FILES['file'])) { self::api_throw_error(__('No files found', 'ws-form')); }

			// Run through files
			$file = $_FILES['file'];

			// Read file data
			$file_name = $file['name'];
			$file_type = $file['type'];
			$file_tmp_name = $file['tmp_name'];
			$file_error = $file['error'];
			$file_size = $file['size'];

			// Error checking
			if($file_error != 0) { self::api_throw_error(__('File upload error', 'ws-form') . ': ' . $file_error); }
			if($file_size == 0) { self::api_throw_error(__('File empty', 'ws-form')); }

			// Check file extension
			$ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
			if($ext !== 'json') { parent::api_throw_error(__('Unsupported file extension', 'ws-form') . ': ' . $ext); }

			// Check file format
			$form_json = file_get_contents($file_tmp_name);

			// Check form JSON format
			$form_object = json_decode($form_json);
			if(is_null($form_object)) { self::api_throw_error(__('JSON corrupt', 'ws-form')); }

			// Checksum test
			$checksum = $form_object->checksum;
			unset($form_object->checksum);
			$checksum_file = md5(json_encode($form_object));
			if($checksum != $checksum_file) { self::api_throw_error(__('JSON corrupt (Checksum error)', 'ws-form')); }

			// Check stamps
			if($form_object->identifier != WS_FORM_IDENTIFIER) { self::api_throw_error(__('JSON corrupt (Not a WS Form JSON file)', 'ws-form')); }

			// Reset form
			$ws_form_form->db_import_reset();

			// Build form
			$ws_form_form->db_update_from_object($form_object, true, true);

			// Fix data - Conditional ID's
			$ws_form_form->db_conditional_repair();

			// Fix data - Action ID's
			$ws_form_form->db_action_repair();

			// Fix data - Meta ID's
			$ws_form_form->db_meta_repair();

			// Update checksum
			$ws_form_form->db_checksum();

			// Describe transaction for history
			$history = ($form_id > 0) ? array(

				'object'		=>	'form',
				'method'		=>	WS_Form_Common::get_query_var_nonce('history_method', 'post_upload_json'),
				'label'			=>	$ws_form_form->db_get_label(),
				'id'			=>	$ws_form_form->id
			) : false;

			// Send JSON response (By passing form ID, it will get returned in default JSON response)
			parent::api_json_response([], $form_id, $history, true);
		}

		// API - POST - Download - JSON
		public function api_post_download_json($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('export_form')) { parent::api_access_denied(); }

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = self::api_get_id($parameters);
			$ws_form_form->db_download_json();
		}

		// API - PUT
		public function api_put($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = self::api_get_id($parameters);

			// Get form data
			$form_object = WS_Form_Common::get_query_var_nonce('form', false, $parameters);
			if(!$form_object) { return false; }

			// Put form as array
			$ws_form_form->db_update_from_object($form_object, false);

			// Describe transaction for history
			$history = array(

				'object'		=>	'form',
				'method'		=>	WS_Form_Common::get_query_var_nonce('history_method', 'put'),
				'label'			=>	$ws_form_form->db_get_label(),
				'id'			=>	$ws_form_form->id
			);

			// Update checksum
			$ws_form_form->db_checksum();

			// Send JSON response
			parent::api_json_response([], $ws_form_form->id, isset($form_object->history_suppress) ? false : $history);
		}

		// API - PUT - ALL
		public function api_put_full($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = self::api_get_id($parameters);

			// Get form data
			$form_object = WS_Form_Common::get_query_var_nonce('form', false, $parameters);
			if(!$form_object) { return false; }

			// Put form as array
			$ws_form_form->db_update_from_object($form_object);

			// Update checksum
			$ws_form_form->db_checksum();

			// Describe transaction for history
			$history = array(

				'object'		=>	'form',
				'method'		=>	WS_Form_Common::get_query_var_nonce('history_method', 'put_full'),
				'label'			=>	$ws_form_form->db_get_label(),
				'id'			=>	$ws_form_form->id
			);

			// Send JSON response
			parent::api_json_response([], $ws_form_form->id, isset($form_object->history_suppress) ? false : $history);
		}

		// API - PUT - Publish
		public function api_put_publish($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('publish_form')) { parent::api_access_denied(); }

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = self::api_get_id($parameters);

			// Publish
			$ws_form_form->db_publish();

			// Send JSON response
			parent::api_json_response([], $ws_form_form->id, false, false);
		}

		// API - PUT - Draft
		public function api_put_draft($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('publish_form')) { parent::api_access_denied(); }

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = self::api_get_id($parameters);

			// Draft
			$ws_form_form->db_draft();

			// Send JSON response
			parent::api_json_response([], false, false, false);
		}

		// API - DELETE
		public function api_delete($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('delete_form')) { parent::api_access_denied(); }

			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = self::api_get_id($parameters);

			// Get label (We do this because once its deleted, we can't reference it)
			$label = $ws_form_form->db_get_label();

			// Delete form
			$ws_form_form->db_delete();

			// Describe transaction for history
			$history = array(

				'object'		=>	'form',
				'method'		=>	'delete',
				'label'			=>	$label,
				'id'			=>	$ws_form_form->id
			);

			// Update checksum
			$ws_form_form->db_checksum();

			// Send JSON response
			parent::api_json_response([], $ws_form_form->id, $history, false);
		}

		// API - GET - Locations
		public function api_get_locations($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { parent::api_access_denied(); }

			// Get locations
			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = self::api_get_id($parameters);
			$return_array = $ws_form_form->db_get_locations();

			return $return_array;
		}

		// API - GET - SVG - Draft
		public function api_get_svg_draft($parameters) {

			self::api_get_svg($parameters, false);
		}

		// API - GET - SVG - Published
		public function api_get_svg_published($parameters) {

			self::api_get_svg($parameters, true);
		}

		// API - GET - SVG - Draft
		public function api_get_svg($parameters, $published) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { parent::api_access_denied(); }

			// Content type
			header('Content-type: text/html');

			// Get form ID
			$form_id = intval(self::api_get_id($parameters));
			if($form_id == 0) { exit; }

			// Return SVG
			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $form_id;
			echo $ws_form_form->get_svg($published);	// phpcs:ignore
			exit;
		}

		// Get form ID
		public function api_get_id($parameters) {

			return intval(WS_Form_Common::get_query_var_nonce('form_id', 0, $parameters));
		}
	}