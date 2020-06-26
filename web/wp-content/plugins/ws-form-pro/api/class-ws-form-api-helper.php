<?php

	class WS_Form_API_Helper extends WS_Form_API {

		public function __construct() {

			// Call parent on WS_Form_API
			parent::__construct();
		}

		// API - Detect framework
		public function api_framework_detect($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('manage_options_wsform')) { parent::api_access_denied(); }

			// Get file path if provided
			$path = WS_Form_Common::get_query_var_nonce('path', '', $parameters);

			// Get framework auto detect configuration
			$frameworks = WS_Form_Config::get_frameworks(false);
			if(!isset($frameworks['auto_detect'])) { self::api_framework_detect_error(); }

			$auto_detect = $frameworks['auto_detect'];

			// Get framework type lookups
			$types = (isset($auto_detect['types'])) ? $auto_detect['types'] : false;
			if($types === false) { self::api_framework_detect_error(); }

			// Get framework filename exclusions
			$exclude_filenames = (isset($auto_detect['exclude_filenames'])) ? $auto_detect['exclude_filenames'] : false;

			// Get website URL
			$url = site_url($path);
			if(!$url) { return false; }

			// Make HTTP request to get URL
			$response = wp_remote_get($url);
			if(!is_array($response)) { self::api_framework_detect_error(); }
			if(!isset($response['body'])) { self::api_framework_detect_error(); }

			// Read body resposne
			$http_body = $response['body']; // use the content
			if($http_body == '') { self::api_framework_detect_error(); }
			if((strpos($http_body, 'css') === false) && (strpos($http_body, 'CSS') === false)) { self::api_framework_detect_error(); }

			// Start DOM document
			$dom_doc = new DOMDocument();

			// Load HTML into DOM document (diseregard parse errors)
			libxml_use_internal_errors(true);
			if(!$dom_doc->loadHTML($http_body)) { self::api_framework_detect_error(); }
			libxml_use_internal_errors(false);

			// Look for link tags
			$domcss = $dom_doc->getElementsByTagName('link');
			foreach($domcss as $links) {

				// Look for rel attributes
				if(strtolower($links->getAttribute('rel')) != "stylesheet") { continue; }

				// Get href attribute
				$css_url = $links->getAttribute('href');

				// Do we recognize the file name?
				if($exclude_filenames !== false) {

					foreach($exclude_filenames as $exclude_filename) {

						if(strpos($css_url, $exclude_filename) !== false) { continue 2; }
					}
				}

				// Request CSS document
				$css_response = wp_remote_get($css_url);

				// Get wp_remote_get
				if(!is_array($css_response)) { continue; }

				// Load response body into string
				$css_body = $css_response['body'];

				// Run through each framework type
				foreach($types as $type => $type_strings) {

					$lookup_strings_found = true;

					// Run through each string to find in the framework
					foreach($type_strings as $type_string) {

						// Look for element in CSS body (Case sensitive)
						if(strpos($css_body, $type_string) === false) {

							$lookup_strings_found = false;
							break;
						}
					}

					// If all strings are found, return that framework
					if($lookup_strings_found) {

						// Return framework data
						$return_array = array();
						$return_array['type'] = $type;
						$return_array['framework'] = $frameworks['types'][$type];
						self::api_json_response($return_array, 0, false);
					}
				}
			}

			// Unable to find a framework
			self::api_framework_detect_error();
		}

		// API - Detect framework - Error
		public function api_framework_detect_error() {

			// Return framework data
			$return_array = array();
			$return_array['type'] = false;
			$return_array['framework'] = false;
			self::api_json_response($return_array, 0, false);
		}


		// API - Push setup
		public function api_setup_push($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('manage_options_wsform')) { parent::api_access_denied(); }

			// Get framework
			$framework = WS_Form_Common::get_query_var_nonce('framework', '', $parameters);
			if($framework == '') { self::api_throw_error(__('Framework not specified', 'ws-form')); }

			// Check framework
			$frameworks = WS_Form_Config::get_frameworks(false);
			if(!isset($frameworks['types'][$framework])) { self::api_throw_error(__('Invalid framework specified', 'ws-form')); }

			// Get mode
			$mode = WS_Form_Common::get_query_var_nonce('mode', '', $parameters);
			if($mode == '') { $mode = WS_FORM_DEFAULT_MODE; }

			// Check mode
			$modes = explode(',', WS_FORM_MODES);
			if(!in_array($mode, $modes)) { self::api_throw_error(__('Invalid mode specified', 'ws-form')); }

			// Set framework
			WS_Form_Common::option_set('framework', $framework);

			// Set mode
			WS_Form_Common::option_set('mode', $mode);

			// Configure settings according to mode selected
			$options = WS_Form_Config::get_options();
			foreach($options as $tab => $data) {

				if(isset($data['fields'])) {

					$fields = $data['fields'];
				}

				if(isset($data['groups'])) {

					$groups = $data['groups'];

					foreach($groups as $group) {

						$fields = $group['fields'];

						self::api_set_push_options($mode, $fields);
					}
				}
			}

			// Set setup (true = complete)
			WS_Form_Common::option_set('setup', true);

			// Success
			self::api_json_response([], 0, false);
		}

		// API - Push setup - Set options
		public function api_set_push_options($mode, $fields) {

			foreach($fields as $key => $attributes) {

				if(
					isset($attributes['type']) && 
					($attributes['type'] != 'static') && 
					isset($attributes['mode']) &&
					isset($attributes['mode'][$mode])
				) {

					$value = $attributes['mode'][$mode];

					WS_Form_Common::option_set($key, $value);
				}
			}
		}

		// API - Support contact submit
		public function api_support_contact_submit() {

			// User capability check
			if(!WS_Form_Common::can_user('manage_options_wsform')) { parent::api_access_denied(); }

			// Read support inquiry fields
			$data = array(

				'contact_first_name'	=> WS_Form_Common::get_query_var_nonce('contact_first_name'),
				'contact_last_name'		=> WS_Form_Common::get_query_var_nonce('contact_last_name'),
				'contact_email'			=> WS_Form_Common::get_query_var_nonce('contact_email'),
				'contact_inquiry'		=> WS_Form_Common::get_query_var_nonce('contact_inquiry')
			);

			// Push form
			$contact_push_form = WS_Form_Common::get_query_var_nonce('contact_push_form');
			$form_id = intval(WS_Form_Common::get_query_var_nonce('id'));
			if($contact_push_form && ($form_id > 0)) {

				// Create form file attachment
				$ws_form_form = new WS_Form_Form();
				$ws_form_form->id = $form_id;

				// Get form
				$form_object = $ws_form_form->db_read(true, true);

				// Clean form
				unset($form_object->checksum);
				unset($form_object->published_checksum);

				// Stamp form data
				$form_object->identifier = WS_FORM_IDENTIFIER;
				$form_object->version = WS_FORM_VERSION;
				$form_object->time = time();

				// Add checksum
				$form_object->checksum = md5(json_encode($form_object));

				$form_json = wp_json_encode($form_object);

				// Add to data
				$data['contact_form'] = $form_json;
			}

			// Push system
			$contact_push_system = WS_Form_Common::get_query_var_nonce('contact_push_system');
			if($contact_push_system) {

				// Add to data
				$data['contact_system'] = wp_json_encode(WS_Form_Config::get_system());
			}

			// Filters
			$timeout = apply_filters('wsf_api_call_timeout', WS_FORM_API_CALL_TIMEOUT);
			$sslverify = apply_filters('wsf_api_call_verify_ssl',WS_FORM_API_CALL_VERIFY_SSL);

			// Build args
			$args = array(

				'method'		=>	'POST',
				'body'			=>	http_build_query($data),
				'user-agent'	=>	'WSForm/' . WS_FORM_VERSION . ' (wsform.com)',
				'timeout'		=>	$timeout,
				'sslverify'		=>	$sslverify
			);

			// URL
			$url = 'https://wsform.com/plugin-support/contact.php';

			// Call using Wordpress wp_remote_get
			$response = wp_remote_get($url, $args);

			// Check for error
			if($api_response_error = is_wp_error($response)) {

				// Handle error
				$api_response_error_message = $response->get_error_message();
				$api_response_headers = array();
				$api_response_body = '';
				$api_response_http_code = 0;

			} else {

				// Handle response
				$api_response_error_message = '';
				$api_response_headers = wp_remote_retrieve_headers($response);
				$api_response_body = wp_remote_retrieve_body($response);
				$api_response_http_code = wp_remote_retrieve_response_code($response);
			}

			// Return response
			return array('error' => $api_response_error, 'error_message' => $api_response_error_message, 'response' => $api_response_body, 'http_code' => $api_response_http_code);
		}

		// API - Deactivate feedback submit
		public function api_deactivate_feedback_submit() {

			// User capability check
			if(!WS_Form_Common::can_user('manage_options_wsform')) { parent::api_access_denied(); }

			// Read support inquiry fields
			$data = array(

				'feedback_reason'						=> WS_Form_Common::get_query_var_nonce('feedback_reason'),
				'feedback_reason_error'					=> WS_Form_Common::get_query_var_nonce('feedback_reason_error'),
				'feedback_reason_found_better_plugin'	=> WS_Form_Common::get_query_var_nonce('feedback_reason_found_better_plugin'),
				'feedback_reason_other'					=> WS_Form_Common::get_query_var_nonce('feedback_reason_other')
			);

			// Filters
			$timeout = apply_filters('wsf_api_call_timeout', WS_FORM_API_CALL_TIMEOUT);
			$sslverify = apply_filters('wsf_api_call_verify_ssl',WS_FORM_API_CALL_VERIFY_SSL);

			// Build args
			$args = array(

				'method'		=>	'POST',
				'body'			=>	http_build_query($data),
				'user-agent'	=>	'WSForm/' . WS_FORM_VERSION . ' (wsform.com)',
				'timeout'		=>	$timeout,
				'sslverify'		=>	$sslverify
			);

			// URL
			$url = 'https://wsform.com/plugin-support/deactivate_feedback.php';

			// Call using Wordpress wp_remote_get
			$response = wp_remote_get($url, $args);

			// Check for error
			if($api_response_error = is_wp_error($response)) {

				// Handle error
				$api_response_error_message = $response->get_error_message();
				$api_response_headers = array();
				$api_response_body = '';
				$api_response_http_code = 0;

			} else {

				// Handle response
				$api_response_error_message = '';
				$api_response_headers = wp_remote_retrieve_headers($response);
				$api_response_body = wp_remote_retrieve_body($response);
				$api_response_http_code = wp_remote_retrieve_response_code($response);
			}

			// Return response
			return array('error' => $api_response_error, 'error_message' => $api_response_error_message, 'response' => $api_response_body, 'http_code' => $api_response_http_code);
		}

		// API - WS Form Admin CSS
		public function api_ws_form_css_admin() {

			// Output HTTP header
			self::api_css_header();

			// Output CSS
			$ws_form_css = new WS_Form_CSS();
			echo $ws_form_css->get_admin();	// phpcs:ignore

			exit;
		}

		// API - WS Form Public CSS
		public function api_ws_form_css() {

			// Output HTTP header
			self::api_css_header();

			// Output CSS
			$ws_form_css = new WS_Form_CSS();
			echo $ws_form_css->get_public();	// phpcs:ignore

			exit;
		}

		// API - WS Form Skin CSS
		public function api_ws_form_css_skin() {

			// Output HTTP header
			self::api_css_header();

			// Output CSS
			$ws_form_css = new WS_Form_CSS();
			echo $ws_form_css->get_skin();	// phpcs:ignore

			exit;
		}

		// API - WS Form Skin CSS - RTL
		public function api_ws_form_css_skin_rtl() {

			// Output HTTP header
			self::api_css_header();

			// Output CSS
			$ws_form_css_rtl = new WS_Form_CSS();
			echo $ws_form_css_rtl->get_skin_rtl();	// phpcs:ignore

			exit;
		}

		// API - Email CSS
		public function api_css_email() {

			// Output HTTP header
			self::api_css_header();

			// Output CSS
			$ws_form_css = new WS_Form_CSS();
			echo $ws_form_css->get_email();	// phpcs:ignore

			exit;
		}

		// API - CSS Cache Header
		public function api_css_header() {

			// Content type
			header("Content-type: text/css; charset: UTF-8");

			// Caching
			$css_cache_duration = 	WS_Form_Common::option_get('css_cache_duration', 86400);
			header("Pragma: public");
			header("Cache-Control: maxage=" . $css_cache_duration);
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $css_cache_duration) . ' GMT');
		}

		// API - File download
		public function api_file_download($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('read_submission')) { parent::api_access_denied(); }

			// Get submit hash
			$hash = WS_Form_Common::get_query_var_nonce('hash', '', $parameters);
			if($hash == '' || (strlen($hash) != 32)) { self::api_throw_error(__('Hash not specified', 'ws-form')); }

			// Get field ID
			$field_id = intval(WS_Form_Common::get_query_var_nonce('field_id', '', $parameters));
			if($field_id == 0) { self::api_throw_error(__('Field ID not specified', 'ws-form')); }

			// Get section repeatable index
			$section_repeatable_index = intval(WS_Form_Common::get_query_var_nonce('section_repeatable_index', '', $parameters));

			// Get file index
			$file_index = intval(WS_Form_Common::get_query_var_nonce('file_index', '', $parameters));
			if($file_index < 0) { self::api_throw_error(__('File index invalid', 'ws-form')); }

			// Get submit record
			$ws_form_submit = new WS_Form_Submit();
			$ws_form_submit->hash = $hash;
			$submit = $ws_form_submit->db_read_by_hash(true, false, false);

			// Get field
			$meta_key_suffix = (($section_repeatable_index > 0) ? ('_' . $section_repeatable_index) : '');
			if(!isset($submit['meta'][WS_FORM_FIELD_PREFIX . $field_id . $meta_key_suffix])) { self::api_throw_error(__('Field ID not found', 'ws-form')); }
			$field = $submit['meta'][WS_FORM_FIELD_PREFIX . $field_id . $meta_key_suffix];

			// Get files
			if(!isset($field['value'])) { self::api_throw_error(__('Field data not found', 'ws-form')); }
			$files = $field['value'];

			// Get file
			if(!isset($files[$file_index])) { self::api_throw_error(__('Field data not found', 'ws-form')); }
			$file = $files[$file_index];

			// Get file name
			if(!isset($file['name'])) { self::api_throw_error(__('File name not found', 'ws-form')); }
			$file_name = $file['name'];

			// Get file type
			if(!isset($file['type'])) { self::api_throw_error(__('File type not found', 'ws-form')); }
			$file_type = $file['type'];

			// Get file path
			if(!isset($file['path'])) { self::api_throw_error(__('File path not found', 'ws-form')); }
			$file_path = $file['path'];

			// Set HTTP headers
			header('Content-Type: ' . $file_type);

			// Make browser download file instead of viewing it
			$download = (WS_Form_Common::get_query_var_nonce('download', '', $parameters) !== '');
			if($download) {

				header("Content-Transfer-Encoding: Binary"); 
				header("Content-disposition: attachment; filename=\"" . $file_name . "\""); 
			}

			// Get base upload_dir
			$upload_dir = wp_upload_dir()['basedir'];

			// Build file path
			$file_path_full = $upload_dir . '/' . $file_path;

			// Push file to browser
			readfile($file_path_full);

			exit;
		}

		// Hidden columns changed via AJAX request
		public function api_user_meta_hidden_columns($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('read_submission')) { parent::api_access_denied(); }

			// Get form ID
			$form_id = intval(WS_Form_Common::get_query_var_nonce('id', '', $parameters));
			if($form_id == 0) { exit; }

			// Get hidden columns
			$form_hidden_columns_string = WS_Form_Common::get_query_var_nonce('hidden', '', $parameters);
			$form_hidden_columns = explode(',', $form_hidden_columns_string);

			// Write hidden columns back to user meta for current form
			update_user_option(get_current_user_id(), 'managews-form_page_ws-form-submitcolumnshidden-' . $form_id, $form_hidden_columns, true);

			self::api_json_response();
		}

		// API - Review nag dismiss
		public function api_review_nag_dismiss($parameters) {

			WS_Form_Common::option_set('review_nag', true);

			return array('error' => false);
		}

		// API - Test API is working
		public function api_test($parameters) {

			// REST API test
			wp_set_current_user(0);
			setup_userdata(0);
			$access = apply_filters('rest_authentication_errors', true);

			if(is_wp_error($access)) {

				return array('error' => true, 'error_message' => $access->get_error_message());

			} else {

				return array('error' => false);
			}
		}

		// API - System
		public function api_system($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('manage_options_wsform')) { parent::api_access_denied(); }

			return WS_Form_Config::get_system();
		}

		// Get count submit unread total
		public function api_count_submit_unread($parameters) {

			$ws_form_form = new WS_Form_Form();
			$count_submit_unread_total = $ws_form_form->db_get_count_submit_unread_total();

			return array('count_submit_unread_total' => $count_submit_unread_total);
		}

		// Intro
		public function api_intro($paramters) {

			$hints = [

				[
					'hint' 			=> __('<strong>Toolbox</strong><br />Drag-and-drop or click a field type to add it to your form. The \'Undo\' tab contains a history of your form edits. You can go back to any step if you make a mistake.', 'ws-form'),
					'element' 		=> '[data-action-sidebar="toolbox"]',
					'sidebar_open' 	=> 'toolbox',
					'button_url'	=> 'https://wsform.com/knowledgebase/the-layout-editor/'
				],

				[
					'hint' 			=> __('<strong>Actions</strong><br />Actions run whenever a form is submitted or saved. You can send emails, show messages, redirect to a page, integrate with a CRM and more.', 'ws-form'),
					'element' 		=> '[data-action-sidebar="action"]',
					'sidebar_open' 	=> 'action',
					'button_url'	=> 'https://wsform.com/knowledgebase/introduction-actions/'
				],

				[
					'hint' 			=> __('<strong>Support</strong><br />Need help? Click here to browse and search the WS Form PRO knowledge base or send us a support ticket.', 'ws-form'),
					'element' 		=> '[data-action-sidebar="support"]',
					'sidebar_open' 	=> 'support',
					'button_url'	=> 'https://wsform.com/knowledgebase/'
				],

				[
					'hint' 			=> __('<strong>Form Settings</strong><br />Form settings include tracking, spam settings, submission count limiting and scheduling. You can also add custom CSS classes and edit the behavior of the form.', 'ws-form'),
					'element' 		=> '[data-action-sidebar="form"]',
					'sidebar_open' 	=> 'form',
					'button_url'	=> 'https://wsform.com/knowledgebase/form-settings/'
				],

				[
					'hint' 			=> __('<strong>Publish</strong><br />Once you have finished editing your form, click this button to publish it. Any changes made before publishing can only be seen by you.', 'ws-form'),
					'element' 		=> '[data-action="wsf-publish"]',
					'button_url'	=> 'https://wsform.com/knowledgebase/publishing-forms/'
				],

				[
					'hint' 			=> __('<strong>Conditional Logic</strong><br />Use conditional logic to make your form interactive! For example, you could show or hide sections of a form to make it easier to complete.', 'ws-form'),
					'element' 		=> '[data-action-sidebar="conditional"]',
					'sidebar_open' 	=> 'conditional',
					'button_url'	=> 'https://wsform.com/knowledgebase/conditional-logic/'
				],

				[
					'hint' 			=> __('<strong>Breakpoint Selector</strong><br />You can create unique form layouts for each screen width. To choose a different screen width, drag the breakpoint selector left or right. If you change a section or field width it will apply to that one breakpoint and all those above it.', 'ws-form'),
					'element' 		=> '#wsf-breakpoints',
					'button_url'	=> 'https://wsform.com/knowledgebase/responsive-forms/'
				],
			];

			WS_Form_Common::option_set('intro', false);

			return $hints;
		}
	}