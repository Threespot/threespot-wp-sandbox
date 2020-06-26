<?php

	class WS_Form_API_Config extends WS_Form_API {

		public function __construct() {

			// Call parent on WS_Form_API
			parent::__construct();
		}

		// API - GET
		public function api_get() {
			
			// Run actions
			try {

				// Send JSON response
				parent::api_json_response(WS_Form_Config::get_config());

			} catch(Exception $e) {

				parent::api_throw_error($e->getMessage());
			}
		}
	}
