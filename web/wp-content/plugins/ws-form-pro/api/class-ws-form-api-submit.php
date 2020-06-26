<?php

	class WS_Form_API_Submit extends WS_Form_API {

		public $ws_form_submit;

		private $duration_server_start;
		private $spam_level;

		public function __construct() {

			// Initialize
			$this->ws_form_submit = New WS_Form_Submit();
			$this->duration_server_start = microtime(true);
			$this->spam_level = null;

			// Call parent on WS_Form_API
			parent::__construct();
		}

		// API - GET
		public function api_get($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('read_submission')) { parent::api_access_denied(); }

			$this->ws_form_submit->id = self::api_get_id($parameters);
			$this->ws_form_submit->form_id = self::api_get_form_id($parameters);

			try {

				// Mark as viewed
				$this->ws_form_submit->db_set_viewed();

				// Send JSON response
				self::api_json_response($this->ws_form_submit->db_read(true, true));

			} catch(Exception $e) { self::api_throw_error($e->getMessage()); }
		}

		// API - GET - By hash
		public function api_get_by_hash($parameters) {

			// No capabilities required, this is a public method

			// Get form hash
			$this->ws_form_submit->hash = self::api_get_hash($parameters);

			try {

				// Send JSON response
				self::api_json_response($this->ws_form_submit->db_read_by_hash(true, true, false, true));

			} catch(Exception $e) { self::api_throw_error($e->getMessage()); }
		}

		// API - POST
		public function api_post($parameters) {

			// No capabilities required, this is a public method

			try {

				// Set up submit from post
				$this->ws_form_submit->setup_from_post();

				// Get form object (This was set up as a result of setup_from_post running)
				$form_object = $this->ws_form_submit->form_object;

				// Process form validation errors
				self::api_validation_error_process();

				// Set up action
				add_action('wsf_actions_post_complete', array($this, 'api_post_complete'), 10, 2);

				// Get action_id
				$action_id = intval(WS_Form_Common::get_query_var_nonce('wsf_action_id'));

				// Process all actions
				do_action('wsf_actions_post', $form_object, $this->ws_form_submit, 'wsf_actions_post_complete', $action_id);

			} catch(Exception $e) { self::api_throw_error_submit($e->getMessage()); }
		}

		// API - POST - Complete
		public function api_post_complete($action_complete_array) {

			// No capabilities required, this is a public method

			// Get processing time in milliseconds
			$submit_duration_server = round((microtime(true) - $this->duration_server_start) * 1000);

			// Create response
			$json_response = ['count' => $this->ws_form_submit->count_submit, 'submit_duration_server' => $submit_duration_server, 'submit_duration_user' => $this->ws_form_submit->duration, 'post_mode' => $this->ws_form_submit->post_mode];

			// Add js to response
			if(isset($action_complete_array['js']) && is_array($action_complete_array['js']) && count($action_complete_array['js']) > 0) { $json_response['js'] = $action_complete_array['js']; }

			// Check if debug is enabled
			$debug = WS_Form_Common::debug_enabled();
			if($debug) {

				// Add logs to response
				if(isset($action_complete_array['logs']) && is_array($action_complete_array['logs']) && count($action_complete_array['logs']) > 0) { $json_response['logs'] = $action_complete_array['logs']; }
			}

			// Add errors to response
			if(isset($action_complete_array['errors']) && is_array($action_complete_array['errors']) && count($action_complete_array['errors']) > 0) { $json_response['errors'] = $action_complete_array['errors']; }

			// Log save or submit
			$ws_form_form_stat = new WS_Form_Form_Stat();
			$ws_form_form_stat->form_id = $this->ws_form_submit->form_id;

			switch($this->ws_form_submit->post_mode) {

				case 'save' :

					$ws_form_form_stat->db_add_save();
					break;

				case 'submit' :

					$ws_form_form_stat->db_add_submit();
					break;
			}

			// Send response
			self::api_json_response_submit($json_response);
		}

		// API - REPOST (This is called to repost an action)
		public function api_repost($parameters) {

			try {

				// User capability check
				if(!WS_Form_Common::can_user('edit_submission')) { parent::api_access_denied(); }

				$this->ws_form_submit->id = self::api_get_id($parameters);
				$action_index = self::api_get_action_index($parameters);

				// Read submit
				$this->ws_form_submit->db_read(true, false);

				// Read form_object
				$this->ws_form_submit->db_form_object_read();

				// Get submit actions
				$actions = (@unserialize($this->ws_form_submit->actions) !== false) ? unserialize($this->ws_form_submit->actions) : false;
				if($actions === false) { self::api_throw_error(__('No actions found', 'ws-form')); }

				// Get action
				if(!isset($actions[$action_index])) { self::api_throw_error(__('Action index not found', 'ws-form')); }
				$action = $actions[$action_index];

				// Set up action for 
				add_action('wsf_action_repost_complete', array($this, 'api_repost_complete'), 10, 1);

				do_action('wsf_action_repost', $this->ws_form_submit->form_object, $this->ws_form_submit, $action, 'wsf_action_repost_complete');

			} catch(Exception $e) { self::api_throw_error($e->getMessage()); }
		}

		// API - POST - Complete
		public function api_repost_complete($return_array) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_submission')) { parent::api_access_denied(); }

			// Send response
			parent::api_json_response($return_array, false, false);
		}

		// API - PUT
		public function api_put($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_submission')) { parent::api_access_denied(); }

			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->form_id = self::api_get_form_id($parameters);
			$ws_form_submit->id = self::api_get_id($parameters);

			// Get field data
			$submit_object = WS_Form_Common::get_query_var_nonce('submit', false, $parameters);
			if(!$submit_object) { return false; }

			// Serialize actions (We need to do this because the actions are sent to us as an array)
			if(isset($submit_object->actions) && is_array($submit_object->actions)) {

				$submit_object->actions = serialize($submit_object->actions);
			}

			// Serialize section_repeatable (We need to do this because section_repeatable is sent to us as an object)
			if(isset($submit_object->section_repeatable) && is_object($submit_object->section_repeatable)) {

				// Convert to array
				$section_repeatable = json_decode(json_encode($submit_object->section_repeatable), true);
				$submit_object->section_repeatable = serialize($section_repeatable);
				$section_ids = array_keys($section_repeatable);

				// Remove repeatable fallbacks
				if(isset($submit_object->meta)) {

					foreach((array) $submit_object->meta as $key => $meta) {

						$section_id = isset($meta->section_id) ? $meta->section_id : false;
						$repeatable_index = isset($meta->repeatable_index) ? $meta->repeatable_index : false;

						if(
							in_array('section_' . $section_id, $section_ids) &&
							($repeatable_index === false)
						) {

							unset($submit_object->meta->{$key});
						}
					}
				}
			}

			// Meta data to array
			$submit_object->meta = json_decode(json_encode($submit_object->meta), true);

			// Put field
			$ws_form_submit->db_update_from_object($submit_object);

			// Send JSON response
			parent::api_json_response([], false, false);
		}

		// Handle JSON response
		public function api_json_response_submit($data = false) {

			$json_array = [];

			// Normal response
			if(!$this->ws_form_submit->error) {

				$json_array['hash'] = $this->ws_form_submit->hash;

			} else {

				if(isset($this->ws_form_submit->error_message)) {

					$json_array['error_message'] = $this->ws_form_submit->error_message;
				}
			}

			// Set nonce
			$json_array['x_wp_nonce'] = wp_create_nonce('wp_rest');

			// Set error
			$json_array['error'] = $this->ws_form_submit->error;
			$json_array['error_validation'] = $this->ws_form_submit->error_validation;

			// New data
			if($data !== false) { $json_array['data'] = $data; }

			// JSON encode
			$json_return = wp_json_encode($json_array);

			// Check for JSON encoding error
			if(json_last_error() !== 0) {

				// Set response code
				header('HTTP/1.1 400 Bad Request', true, 400);

				// Build error JSON
				$json_array = array(

					'error' => 			true,
					'error_message' =>	'JSON encoding error: ' . json_last_error_msg() . ' (' . json_last_error() . ')'
				);

				echo wp_json_encode($json_array);
				exit;
			}

			// API error
			if($this->ws_form_submit->error) {

				// Set response code
				switch($this->ws_form_submit->error_code) {

					case '403' :

						header('HTTP/1.1 403 Forbidden', true, 403);
						break;

					case '404' :

						header('HTTP/1.1 404 Not Found', true, 403);
						break;

					default :

						header('HTTP/1.1 400 Bad Request', true, 400);
				}

				// Set error message
				$json_array['error_message'] = $this->ws_form_submit->error_message;

				echo wp_json_encode($json_array);
				exit;
			}

			// Set HTTP content type head
			header('Content-Type: application/json');

			// Output JSON response
			echo $json_return; // phpcs:ignore

			// Stop execution
			exit;
		}

		// API - PUT - Starred - On
		public function api_put_starred_on($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_submission')) { parent::api_access_denied(); }

			$this->ws_form_submit->id = self::api_get_id($parameters);

			// Publish
			$this->ws_form_submit->db_set_starred(true);

			// Send JSON response
			parent::api_json_response([], false, false, false);
		}

		// API - PUT - Starred - Off
		public function api_put_starred_off($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_submission')) { parent::api_access_denied(); }

			$this->ws_form_submit->id = self::api_get_id($parameters);

			// Publish
			$this->ws_form_submit->db_set_starred(false);

			// Send JSON response
			parent::api_json_response([], false, false, false);
		}

		// API - PUT - Viewed - On
		public function api_put_viewed_on($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_submission')) { parent::api_access_denied(); }

			$this->ws_form_submit->id = self::api_get_id($parameters);
			$this->ws_form_submit->form_id = self::api_get_form_id($parameters);

			// Publish
			$this->ws_form_submit->db_set_viewed(true);

			// Send JSON response
			parent::api_json_response([], false, false, false);
		}

		// API - PUT - Viewed - Off
		public function api_put_viewed_off($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_submission')) { parent::api_access_denied(); }

			$this->ws_form_submit->id = self::api_get_id($parameters);
			$this->ws_form_submit->form_id = self::api_get_form_id($parameters);

			// Publish
			$this->ws_form_submit->db_set_viewed(false);

			// Send JSON response
			parent::api_json_response([], false, false, false);
		}

		// API - Throw error
		public function api_throw_error_submit($message, $error_code = 400) {

			$this->ws_form_submit->error = true;
			$this->ws_form_submit->error_message = $message;
			$this->ws_form_submit->error_code = $error_code;

			self::api_json_response_submit();
		}

		// API - Validation error
		public function api_validation_error_process() {

			if(!$this->ws_form_submit->error_validation) { return; }

			$action_complete_array = array();
			$action_complete_array['js'] = array();
			$action_complete_array['errors'] = array();

			// Add errors to response
			foreach($this->ws_form_submit->error_validation_messages as $message) {

				// Add to JS actions
				$action_complete_array['js'][] = array(

					'action' => 'message',
					'message' => $message,
					'type' => 'danger',
					'method' => 'before',
					'duration' => 4000,
					'form_hide' => false,
					'clear' => true,
					'scroll_top' => false,
					'form_show' => false,
					'message_hide' => true
				);

				// Add to debug error log
				$action_complete_array['errors'][] = $message;
			}

			// Add errors to response
			foreach($this->ws_form_submit->error_validation_fields as $field) {

				$field_id 				= $field['field_id'];
				$invalid_feedback 		= $field['invalid_feedback'];

				// Add to JS actions
				$action_complete_array['js'][] = array(

					'action' 				=> 'field_invalid_feedback',
					'invalid_feedback' 	=> $invalid_feedback,
					'field_id' 				=> $field_id
				);
			}

			// No actions should be run, so just return the submit JSON response
			self::api_post_complete($action_complete_array);
		}

		// Get form ID
		public function api_get_form_id($parameters) {

			// Public
			$form_id = WS_Form_Common::get_query_var_nonce('wsf_form_id', false, $parameters);

			// Admin
			if($form_id === false) {

				$form_id = WS_Form_Common::get_query_var_nonce('id', false, $parameters);
			}

			return intval($form_id);
		}

		// Get hash
		public function api_get_hash($parameters) {

			return WS_Form_Common::get_query_var_nonce('wsf_hash', '', $parameters, true);
		}

		// Get hash
		public function api_get_action_index($parameters) {

			return WS_Form_Common::get_query_var_nonce('action_index', 0, $parameters);
		}

		// Get submit ID
		public function api_get_id($parameters) {

			return intval(WS_Form_Common::get_query_var_nonce('submit_id', 0, $parameters));
		}
	}