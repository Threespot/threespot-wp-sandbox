<?php

	class WS_Form_API_Wizard extends WS_Form_API {

		public function __construct() {

			// Call parent on WS_Form_API
			parent::__construct();
		}

		// API - GET actions that can be used for wizards
		public function api_get_actions($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('create_form')) { parent::api_access_denied(); }

			$ws_form_wizard = new WS_Form_Wizard();

			try {

				$actions = $ws_form_wizard->db_get_actions();

			} catch(Exception $e) {

				// Throw JSON error
				parent::api_throw_error($e->getMessage());
			}

			// Send JSON response
			parent::api_json_response($actions);
		}

		// API - GET action wizards
		public function api_get_action_wizards($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('create_form')) { parent::api_access_denied(); }

			$ws_form_wizard = new WS_Form_Wizard();
			$ws_form_wizard->action_id = self::api_get_action_id($parameters);

			try {

				$wizards = $ws_form_wizard->db_get_action_wizards();

			} catch(Exception $e) {

				// Throw JSON error
				parent::api_throw_error($e->getMessage());
			}

			// Send JSON response
			parent::api_json_response($wizards);
		}

		// Get action ID
		public function api_get_action_id($parameters) {

			return WS_Form_Common::get_query_var_nonce('action_id', 0, $parameters);
		}
	}
