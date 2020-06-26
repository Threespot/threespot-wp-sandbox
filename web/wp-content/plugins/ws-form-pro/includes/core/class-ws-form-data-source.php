<?php

	abstract class WS_Form_Data_Source {

		// Variables global to this abstract class
		public static $data_sources = array();
		private static $return_array = array();

		// Register data source
		public function register($object) {

			// Initialize WordPress
			if(count(self::$data_sources) == 0) { self::wp_init(); }

			// Check if pro required for data source
			if(!WS_Form_Common::is_edition($this->pro_required ? 'pro' : 'basic')) { return false; }

			// Get data source ID
			$data_source_id = $this->id;

			// Add action to actions array
			self::$data_sources[$data_source_id] = $object;
		}

		// Get settings wrapper
		public function get_settings_wrapper($settings) {

			$settings_wrapper = new stdClass();

			$settings_wrapper->fieldsets = array(

				$this->id => $settings
			);

			return $settings_wrapper;
		}

		// Get data source settings
		public static function get_settings() {

			$return_settings = array();

			// Build action settings
			foreach(self::$data_sources as $id => $action) {

				if(method_exists($action, 'get_data_source_settings')) {

					$return_settings[$id] = $action->get_data_source_settings();
					array_unshift($return_settings[$id]->{'fieldsets'}[$id]['meta_keys'], 'data_source_id');
				}
			}

			// Add 'Off'
			$return_settings[''] = new stdClass();
			$return_settings['']->{'label'} = __('Off', 'ws-form');
			$return_settings['']->{'fieldsets'} = array('' => array(

				'meta_keys' => array('data_source_id')
			));

			// Sort data sources alphabetically
			uasort($return_settings, function ($action1, $action2) {

			    if ($action1->label == $action2->label) return 0;
			    return $action1->label < $action2->label ? -1 : 1;
			});

			return $return_settings;
		}

		// Get configuration
		public function get_config($config, $meta_key, $default_value = false, $throw_error = false) {

			if(!isset($config['meta']) || !isset($config['meta'][$meta_key])) {

				return $throw_error ? self::get_config_error($config, $meta_key, $default_value) : $default_value;
			}

			return $config['meta'][$meta_key];
		}

		// Get configuration error
		public function get_config_error($config, $meta_key, $default_value = false) {

			if($throw_error) { self::error('Cannot find configuration meta_key: ' + $meta_key, false, false); }

			return $default_value;
		}

		// Action API call response
		public function api_error($error_message) {

			// API response
			$ws_form_api = new WS_Form_API();
			$ws_form_api->api_throw_error($error_message);
		}

		public function wp_init() {

		}
	}
