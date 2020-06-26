<?php

	/**
	 * Common functions used by this plugin
	 */
	class WS_Form_Common {

		// Encryption key, we recommend changing this
		const WS_FORM_COOKIE_PREFIX = 'ws_form_';

		// Admin messages
		public static $admin_messages = array();

		// IP lookup response
		public static $ip_lookup_response = false;

		// Options cache
		public static $options = false;

		// NONCE verified
		public static $nonce_verified = false;

		// Fields cache
		public static $fields = false;

		// Admin messages - Push
		public static function admin_message_push($message, $type = 'notice-success', $dismissible = true, $nag_notice = true) {

			self::$admin_messages[] = array(

				'message'		=>	$message,
				'type'			=>	$type,
				'dismissible'	=>	$dismissible,
				'nag_notice'	=>	$nag_notice
			);
		}

		// Admin messages - Render
		public static function admin_messages_render() {

			// Server side notices
			foreach(self::$admin_messages as $admin_message) {

				$message = $admin_message['message'];
				$type = isset($admin_message['type']) ? $admin_message['type'] : 'notice-success';
				$dismissible = isset($admin_message['dismissible']) ? $admin_message['dismissible'] : true;
				$nag_notice = isset($admin_message['nag_notice']) ? $admin_message['nag_notice'] : false;

				self::admin_message_render($message, $type, $dismissible, $nag_notice);
			}
		}

		// Admin messages - Render single
		public static function admin_message_render($message, $type = 'notice-success', $dismissible = true, $nag_notice = false, $class = '') {

			if(!(defined('DISABLE_NAG_NOTICES') && DISABLE_NAG_NOTICES && $nag_notice)) {

				echo sprintf('<div class="notice %s"><p>%s</p></div>', esc_attr($type . ($dismissible ? ' is-dismissible' : '') . ($class ? ' '  . $class : '')), str_replace("\n", "<br />\n", $message));	// phpcs:ignore
			}
		}

		// Admin messages - Get count
		public static function get_admin_message_count() {

			return count(self::$admin_messages);
		}

		// Wrapper classes
		public static function wrapper_classes() {

			$wrapper_classes_array = array('wrap');

			// Detect if this plugin is being hosted on wordpress.com
			if(
				isset($_SERVER) && 
				isset($_SERVER['HTTP_X_PRESSABLE_PROXY']) && 
				(isset($_SERVER['HTTP_X_PRESSABLE_PROXY']) == 'wordpress')
			) {

				$wrapper_classes_array[] = 'wsf-wpcom';
			}

			// Output classes
			echo esc_attr(implode(' ', $wrapper_classes_array));
		}

		// Get plugin option key value
		public static function option_get($key, $default = false, $default_set = false, $enable_cache = true) {

			// Return default
			$value = $default;

			// Check cache
			if(
				!$enable_cache ||
				(self::$options === false)
			) {

				// Cache options
				self::$options = get_option(WS_FORM_IDENTIFIER, false);
			}
			$options_array = (is_array(self::$options)) ? self::$options : array();

			// If key exists, return the value
			if(isset($options_array[$key])) {

				$value = $options_array[$key];

			} else {

				// Set value
				if($default_set) { self::option_set($key, $default); }
			}

			return apply_filters('wsf_option_get', $value, $key);
		}

		// Set plugin option key value
		public static function option_set($key, $value, $update = true) {

			$options = get_option(WS_FORM_IDENTIFIER, false);
			$options_array = (is_array($options)) ? $options : array();

			if((isset($options_array[$key]) && $update) || (!isset($options_array[$key]))) {

				// Set key to value in options array
				$options_array[$key] = $value;
			}

			// Update WordPress option
			update_option(WS_FORM_IDENTIFIER, $options_array);

			// Update options cache
			self::$options = $options_array;
		}

		// Remove plugin option key value
		public static function option_remove($key) {

			$options = get_option(WS_FORM_IDENTIFIER, false);
			$options_array = (is_array($options)) ? $options : array();

			// If key exists, return the value
			if(isset($options_array[$key])) {

				// Remove key
				unset($options_array[$key]);

				// Update WordPress option
				update_option(WS_FORM_IDENTIFIER, $options_array);

				// Key found and removed
				return true;
			}

			// Did not find key
			return false;
		}

		// Force WS Form framework
		public static function option_get_framework_ws_form($value, $key) {

			return ($key == 'framework') ? 'ws-form' : $value;
		}

		// Get admin URL
		public static function get_admin_url($page_slug = '', $item_id = false, $path_extra = '') {

			$page_path = 'admin.php';
			if($page_slug != '') { $page_path .= '?page=' . $page_slug; }
			if($item_id !== false) { $item_id = intval($item_id); $page_path .= '&id=' . $item_id; }
			if($path_extra) { $page_path .= '&' . $path_extra; }

			return admin_url($page_path);
		}

		// Get plugin website link
		public static function get_plugin_website_url($path = '', $medium = false) {

			return sprintf('https://wsform.com%s?utm_source=ws_form_pro%s', $path, (($medium !== false) ? '&utm_medium=' . $medium : ''));
		}

		// Get query var (NONCE is not available)
		public static function get_query_var($var, $default = '', $parameters = false, $esc_sql = false, $strip_slashes = true) {

			// REST parameters
			if($parameters !== false) {

				if(isset($parameters[$var])) {

					$return_value = $esc_sql ? esc_sql($parameters[$var]) : $parameters[$var];
					$return_value = self::mod_security_fix($return_value);
					return $strip_slashes ? stripslashes_deep($return_value) : $return_value;
				}
			}

			// Get from standard _GET _POST arrays
			$request_method = self::get_request_method();
			if(!$request_method) { return $default; }

			// Regular GET, POST, PUT handling
			switch($request_method) {

				case 'GET' :

					$post_vars = $_GET;		// phpcs:ignore

					break;

				case 'POST' :

					$post_vars = $_POST;	// phpcs:ignore

					break;

				case 'PUT' :

					// PUT method data is in php://input so parse that into $post_vars
					parse_str(file_get_contents('php://input'), $post_vars);
					$strip_slashes = false;

					break;

				default :

					return $default;
			}

			// DATA param (This overcomes standard 1000 POST parameter limitation in PHP)
			if(
				isset($post_vars['data'])
			) {

				$data = $strip_slashes ? stripslashes_deep($post_vars['data']) : $post_vars['data'];
				$data = self::mod_security_fix($data);

				$data_array = is_string($data) ? json_decode($data, true) : array();

				if(isset($data_array[$var])) { return $data_array[$var]; }
			}

			// Get return value
			$return_value = isset($post_vars[$var]) ? ($esc_sql ? esc_sql($post_vars[$var]) : $post_vars[$var]) : $default;
			$return_value = self::mod_security_fix($return_value);
			return $strip_slashes ? stripslashes_deep($return_value) : $return_value;
		}

		// Get request var 
		public static function get_query_var_nonce($var, $default = '', $parameters = false, $esc_sql = false, $strip_slashes = true, $request_method_required = false) {

			// REST parameters
			if($parameters !== false) {

				if(isset($parameters[$var])) {

					$return_value = $esc_sql ? esc_sql($parameters[$var]) : $parameters[$var];
					$return_value = self::mod_security_fix($return_value);
					return $strip_slashes ? stripslashes_deep($return_value) : $return_value;
				}
			}

			// Get from standard _GET _POST arrays
			$request_method = self::get_request_method();
			if(!$request_method) { return $default; }
			if(
				($request_method_required !== false) &&
				($request_method_required !== $request_method)
			) {

				return $default;
			}

			// Check wp_verify_nonce exists
			if(!function_exists('wp_verify_nonce')) { self::error_nonce(); }

			// Regular GET, POST, PUT handling
			switch($request_method) {

				case 'GET' :

					// If value is not set, return the default value
					if(!isset($_GET)) { return $default; }
					if(!isset($_GET[$var]) && !isset($_GET['data'])) { return $default; }

					// NONCE
					if(
						!self::$nonce_verified && (

							!isset($_GET[WS_FORM_POST_NONCE_FIELD_NAME]) ||
							!wp_verify_nonce($_GET[WS_FORM_POST_NONCE_FIELD_NAME], WS_FORM_POST_NONCE_ACTION_NAME)
						)
					) {

						self::error_nonce();

					} else {

						self::$nonce_verified = true;
					}

					$post_vars = $_GET;

					break;

				case 'POST' :

					// If value is not set, return the default value
					if(!isset($_POST)) { return $default; }
					if(!isset($_POST[$var]) && !isset($_POST['data'])) { return $default; }

					// NONCE
					if(
						!self::$nonce_verified && (

							!isset($_POST[WS_FORM_POST_NONCE_FIELD_NAME]) ||
							!wp_verify_nonce($_POST[WS_FORM_POST_NONCE_FIELD_NAME], WS_FORM_POST_NONCE_ACTION_NAME)
						)
					) {

						self::error_nonce();

					} else {

						self::$nonce_verified = true;
					}

					$post_vars = $_POST;

					break;

				case 'PUT' :

					// PUT method data is in php://input so parse that into $post_vars
					parse_str(file_get_contents('php://input'), $post_vars);

					// NONCE
					if(
						!self::$nonce_verified && (

							!isset($post_vars[WS_FORM_POST_NONCE_FIELD_NAME]) ||
							!wp_verify_nonce($post_vars[WS_FORM_POST_NONCE_FIELD_NAME], WS_FORM_POST_NONCE_ACTION_NAME)
						)
					) {

						self::error_nonce();

					} else {

						self::$nonce_verified = true;
					}

					$strip_slashes = false;

					break;

				default :

					return $default;
			}

			// DATA param (This overcomes standard 1000 POST parameter limitation in PHP)
			if(
				isset($post_vars['data'])
			) {

				$data = $strip_slashes ? stripslashes_deep($post_vars['data']) : $post_vars['data'];
				$data = self::mod_security_fix($data);

				$data_array = is_string($data) ? json_decode($data) : array();

				if(isset($data_array->{$var})) { return $data_array->{$var}; }
			}

			// Get return value
			$return_value = isset($post_vars[$var]) ? ($esc_sql ? esc_sql($post_vars[$var]) : $post_vars[$var]) : $default;
			$return_value = self::mod_security_fix($return_value);
			return $strip_slashes ? stripslashes_deep($return_value) : $return_value;
		}

		// nonce error
		public static function error_nonce() {

			esc_html_e('WordPress nonce error, please reload the form and try again. For more information, visit: https://wsform.com/knowledgebase/nonce-error/', 'ws-form');
			exit;
		}

		// mod_security fix
		public static function mod_security_fix($fix_this) {

			if(is_string($fix_this)) {

				$fix_this = str_replace('~WSF%23~', '#', $fix_this);
				$fix_this = str_replace('~WSF%60~', '<', $fix_this);
				$fix_this = str_replace('~WSF%62~', '>', $fix_this);
				$fix_this = str_replace('~WSFTCELES~', 'SELECT', $fix_this);
				$fix_this = str_replace('~WSFtceles~', 'select', $fix_this);
				$fix_this = str_replace('~WSFtceleS~', 'Select', $fix_this);
				$fix_this = str_replace('~WSFelyts~', 'style', $fix_this);
				$fix_this = str_replace('~WSFELYTS~', 'STYLE', $fix_this);
				$fix_this = str_replace('~WSFcrs~', 'src', $fix_this);
				$fix_this = str_replace('~WSFCRS~', 'SRC', $fix_this);
				$fix_this = str_replace('~WSFsnlmx~', 'xmlns', $fix_this);
				$fix_this = str_replace('~WSFSNLMX~', 'XMLNS', $fix_this);
				$fix_this = str_replace('~WSFid_tcejbo~', 'object_id', $fix_this);

				return $fix_this;
			}

			if(is_array($fix_this)) {

				foreach($fix_this as $key => $fix_this_single) {

					$fix_this[$key] = self::mod_security_fix($fix_this_single);
				}

				return $fix_this;
			}

			return $fix_this;
		}

		// Get IP lookup
		// Data requested from geoplugin.net that uses GeoLite data created by MaxMind, available from http://www.maxmind.com
		public static function get_ip_lookup($json_var_array) {

			if(!is_array($json_var_array)) { $json_var_array = array($json_var_array); }

			if(self::$ip_lookup_response === false) {

				// Get remote IP address
				$remote_addr = self::get_http_env(array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'));
				if($remote_addr == '') { return ''; }

				// Do IP Lookup
				self::$ip_lookup_response = file_get_contents(sprintf('http://www.geoplugin.net/php.gp?ip=%s', $remote_addr));
			}

			if(@unserialize(self::$ip_lookup_response) !== false) {

				$ip_lookup_array = unserialize(self::$ip_lookup_response);

				$return_array = array();

				foreach($json_var_array as $json_var) {

					if(isset($ip_lookup_array[$json_var])) {

						$return_array[] = $ip_lookup_array[$json_var];
					}
				}

				return implode(',', $return_array);
			}

			return '';
		}

		// Get MySQL date
		public static function get_mysql_date($date = false) {

			$time = ($date === false) ? time() : strtotime($date);
			if($time === false) { return false; }
			$date = gmdate('Y-m-d H:i:s', $time);
			return $date;
		}

		// Get request method
		public static function get_request_method($valid_request_methods = false) {

			// Check for valid request methods
			if(!$valid_request_methods) { $valid_request_methods = ['GET', 'POST', 'PUT', 'DELETE']; }

			// Check to ensure we can determine request method
			if(!isset($_SERVER) || !isset($_SERVER["REQUEST_METHOD"])) { return false; }

			// Read request method
			$request_method = strtoupper($_SERVER["REQUEST_METHOD"]);

			// Ensure it is valid
			if(!in_array($request_method, $valid_request_methods)) { return false; }

			return ($request_method != '') ? $request_method : false;
		}

		// Get current user ID
		public static function get_user_id($exit_on_zero = true) {

			$user_id = get_current_user_id();
			if(($user_id == 0) && $exit_on_zero) { exit; }
			return($user_id);
		}

		// Get current URL
		public static function get_current_url() {

			if(!isset($_SERVER)) { return false; }

			return sprintf('%s://%s%s', (self::get_http_env('HTTPS') === 'on' ? "https" : "http"), self::get_http_env('HTTP_HOST'), self::get_http_env('REQUEST_URI'));
		}

		// Add query string to URL
		public static function add_query_string($url, $query_string) {

			$url_parsed = parse_url($url);
			if(!isset($url_parsed['path'])) { $url .= '/'; }
			$separator = isset($url_parsed['query']) ? '&' : '?';
			$url .= $separator . $query_string;

			return $url;
		}

		// Echo comment - CSS
		public static function comment_css($comment) {

			// Should CSS be commented?
			$comments_css = self::option_get('comments_css', true);

			if($comments_css) {

				// Echo comment
				$return_css = sprintf("/* %s */\n", $comment);

			} else {

				$return_css = '';
			}

			return $return_css;
		}

		// SVG Render
		public static function render_icon_16_svg($id) {

			$return_html = WS_Form_Config::get_icon_16_svg($id);

			if($return_html !== false) {

				echo $return_html;	// phpcs:ignore
			}

			return $return_html;
		}

		// Check form status
		public static function check_form_status($status, $throw_error = true) {

			// Check status is valid
			$valid_statuses = explode(',', WS_FORM_STATUS_FORM);
			if(!in_array($status, $valid_statuses)) {

				if($throw_error) {

					self::throw_error(__('Attempt to set invalid status on form', 'ws-form'));

				} else {

					return false;
				}
			}

			return true;
		}

		// Check submit status
		public static function check_submit_status($status, $throw_error = true) {

			// Check status is valid
			$valid_statuses = explode(',', WS_FORM_STATUS_SUBMIT);
			if(!in_array($status, $valid_statuses)) {

				if($throw_error) {

					self::throw_error(__('Attempt to set invalid status on submit', 'ws-form'));

				} else {

					return false;
				}
			}

			return true;
		}

		// Get API base path
		public static function get_api_path($path = '', $query_string = false) {

			// Check permalinks
			$permalink_custom = (get_option('permalink_structure') != '');

			if($permalink_custom) {

				$api_path = rest_url() . WS_FORM_RESTFUL_NAMESPACE . '/' . $path;
				if($query_string !== false) { $api_path .= '?' . $query_string; }

			} else {

				$path = '/' . WS_FORM_RESTFUL_NAMESPACE . '/' . $path;
				$api_path = get_site_url() . '/?rest_route=' . rawurlencode($path);
				if($query_string !== false) { $api_path .= '&' . $query_string; }
			}

			return $api_path;
		}

		// Is debug enabled?
		public static function debug_enabled() {

			if(
				self::is_block_editor() ||
				self::is_customize_preview()

			) { return false; }

			$debug_enabled = false;

			switch(self::option_get('helper_debug', 'off')) {

				case 'administrator' : 	

					if(function_exists('wp_get_current_user')) {

						// Works better for multisite than checking roles. Roles are not available in WP_User on multisite
						$debug_enabled = current_user_can('activate_plugins');

					} else {

						$debug_enabled = false;
					}

					break;
	
				case 'on' :

					$debug_enabled = true;

					break;
			}

			$debug_enabled = apply_filters('wsf_debug_enabled', $debug_enabled);

			return $debug_enabled;
		}

		// Get all fields from form
		public static function get_fields_from_form($form_object, $no_cache = false) {

			// Retrieve from cache
			if(!$no_cache && isset(self::$fields[$form_object->id])) { return self::$fields[$form_object->id]; }

			// Get fields
			$fields = self::get_fields_from_form_group($form_object->groups);

			// Add to cache
			self::$fields[$form_object->id] = $fields;

			return $fields;
		}

		// Run through each group
		public static function get_fields_from_form_group($groups) {

			$fields = array();

			foreach($groups as $key => $group) {

				if(isset($groups[$key]->sections)) {

					$section_fields = self::get_fields_from_form_section($key, $group->sections);

					$fields = $fields + $section_fields;
				}
			}

			return $fields;
		}

		// Run through each section
		public static function get_fields_from_form_section($group_key, $sections) {

			$fields = array();

			foreach($sections as $section_key => $section) {

				if(isset($sections[$section_key]->children)) {

					$sections[$section_key]->children = self::get_fields_from_form_section($section->children, $fields);
				}

				// Get section ID
				$section_id = $section->id;

				// Check if repeatable
				$section_repeatable = isset($section->meta) && isset($section->meta->section_repeatable) && !empty($section->meta->section_repeatable);

				if(isset($sections[$section_key]->fields)) {

					$section_fields = array();

					foreach($section->fields as $field_key => $field) {

						$field->group_key = $group_key;
						$field->section_key = $section_key;
						$field->field_key = $field_key;

						$field->section_id = $section_id;
						$field->section_repeatable = $section_repeatable;

						$section_fields[$field->id] = $field;
					}

					$fields = $fields + $section_fields;
				}
			}

			return $fields;
		}

		// Get all sections from form
		public static function get_sections_from_form($form_object, $get_fields = true, $get_meta = true) {

			$sections = self::get_sections_from_form_group($form_object->groups, $get_fields, $get_meta);

			return $sections;
		}

		// Run through each group
		public static function get_sections_from_form_group($groups, $get_fields = true, $get_meta = true) {

			$sections_return = array();

			foreach($groups as $key => $group) {

				if(isset($groups[$key]->sections)) {

					$section_fields = self::get_sections_from_form_section($group->sections, $get_fields, $get_meta);

					$sections_return = $sections_return + $section_fields;
				}
			}

			return $sections_return;
		}

		// Run through each section
		public static function get_sections_from_form_section($sections, $get_fields = true, $get_meta = true) {

			$sections_return = array();

			foreach($sections as $key => $section) {

				if(isset($sections[$key]->children)) {

					$sections[$key]->children = self::get_sections_from_form_section($section->children, $fields);
				}

				// Get section ID
				$section_id = $section->id;

				$sections_return[$section_id] = new stdClass();
				$sections_return[$section_id]->fields = array();
				$sections_return[$section_id]->meta = new stdClass();

				// Check if repeatable
				$sections_return[$section_id]->repeatable = isset($section->meta) && isset($section->meta->section_repeatable) && !empty($section->meta->section_repeatable);

				if(isset($section->fields) && $get_fields) {

					$section_fields = array();

					foreach($section->fields as $field) {

						$section_fields[$field->id] = $field;
					}

					$sections_return[$section_id]->fields = $section_fields;
				}

				if(isset($section->meta) && $get_meta) {

					$sections_return[$section_id]->meta = $section->meta;
				}
			}

			return $sections_return;
		}

		// Mask parse
		public static function mask_parse($mask, $values, $prefix = '#', $single_parse = false) {

			if($mask == '') { return ''; }

			foreach($values as $key => $value) {

				if($single_parse) {

					// Single parse
					$replace = '/' . preg_quote($prefix . $key, '/') . '/';
					$mask = preg_replace($replace, $value, $mask, 1);

				} else {

					// Multi parse (Default)
					$mask = str_replace($prefix . $key, $value, $mask);
				}
			}

			return $mask;
		}

		// Create shortcode
		public static function shortcode($id = false) {

			if($id == false) { return ''; }

			$shortcode = sprintf('[%s id="%u"]', WS_FORM_SHORTCODE, $id);

			return $shortcode;
		}

		// Check file upload capabilities
		public static function uploads_check() {

			// Create file warnings
			$files_warning = [];

			// Read ini settings
			if(!ini_get('upload_max_filesize')) { return(['max_upload_size' => 0, 'max_uploads' => 0, 'errors' => [__('Unable to read PHP upload_max_filesize setting', 'ws-form')]]); }
			$upload_max_filesize = self::ini_shorthand_notation_to_bytes(ini_get('upload_max_filesize'));

			if(!ini_get('post_max_size')) { return(['max_upload_size' => 0, 'max_uploads' => 0, 'errors' => [__('Unable to read PHP post_max_size setting', 'ws-form')]]); }
			$post_max_size = self::ini_shorthand_notation_to_bytes(ini_get('post_max_size'));

			if(!ini_get('memory_limit')) { return(['max_upload_size' => 0, 'max_uploads' => 0, 'errors' => [__('Unable to read PHP memory_limit setting', 'ws-form')]]); }
			$memory_limit = self::ini_shorthand_notation_to_bytes(ini_get('memory_limit'));

			// This limit was added in PHP 5.3.1
			if(ini_get('max_file_uploads')) {
				$max_file_uploads = intval(ini_get('max_file_uploads'));
			} else {
				$max_file_uploads = 20;
			}

			// Calculate recommended maximum upload size
			$max_upload_size = $upload_max_filesize;
			if($max_upload_size > $post_max_size) { $max_upload_size = $post_max_size; }
			if($max_upload_size > $memory_limit) { $max_upload_size = $memory_limit; }

			// Check ini settings
			if($post_max_size < $upload_max_filesize) { $files_warning[] = sprintf(__('Your PHP post_max_size setting (%s) is less than your max_upload_size setting (%s).', 'ws-form'), ini_get('memory_limit'), ini_get('post_max_size')); }
			if($memory_limit < $upload_max_filesize) { $files_warning[] = sprintf(__('Your PHP memory_limit setting (%s) is less than your max_upload_size setting (%s).', 'ws-form'), ini_get('memory_limit'), ini_get('post_max_size')); }
			if($memory_limit < $post_max_size) { $files_warning[] = sprintf(__('Your PHP memory_limit setting (%s) is less than your post_max_size setting (%s).', 'ws-form'), ini_get('memory_limit'), ini_get('post_max_size')); }

			// Check file permissions
			$upload_dir_create = self::upload_dir_create();
			if($upload_dir_create['error']) { $files_warning[] = $upload_dir_create['message']; }

			// Return result
			return ['max_upload_size' => $max_upload_size, 'max_uploads' => $max_file_uploads, 'errors' => $files_warning];
		}

		// Make sure upload folder can be created. Create it if it doesn't exist.
		public static function upload_dir_create($dir = '') {

			// Get base upload_dir
			$upload_dir = wp_upload_dir()['basedir'];

			// Check upload directory can be written to
			if(!is_writeable($upload_dir)) { return ['error' => true, 'message' => __('Your WordPress uploads directory cannot be written to.', 'ws-form')]; }

			// Get 
			$upload_dir_path = WS_FORM_UPLOAD_DIR . (($dir != '') ? '/' . $dir : '');
			$upload_dir_ws_form = $upload_dir . '/' . $upload_dir_path;

			// Check to see if WS Forms upload folder exists
			if(!file_exists($upload_dir_ws_form)) {

				if(!wp_mkdir_p($upload_dir_ws_form)) { return ['error' => true, 'message' => sprintf(__('Unable to create upload folder for WS Form uploaded files (wp-content/uploads/%s).', 'ws-form'), $upload_dir_path)]; }
			}

			return ['error' => false, 'dir' => $upload_dir_ws_form, 'path' => $upload_dir_path];
		}

		// Convert PHP ini shorthand notation to bytes
		public static function ini_shorthand_notation_to_bytes($ini_value) {

			$ini_value  = trim($ini_value);
			$last = strtolower($ini_value[strlen($ini_value)-1]);
			$ini_value  = substr($ini_value, 0, -1); // necessary since PHP 7.1; otherwise optional
			$ini_value = intval($ini_value);

			switch($last) {

				case 'g':
					$ini_value *= 1024;

				case 'm':
					$ini_value *= 1024;

				case 'k':
					$ini_value *= 1024;
			}

			return $ini_value;
		}

		// Get HTTP environment variable (Accepts array for multiple HTTP environment variable checks)
		public static function get_http_env($variable_array) {

			// Checks
			if(!isset($_SERVER)) { return ''; }
			if(!is_array($variable_array)) { $variable_array = array($variable_array); }
			$variable_array_index_last = count($variable_array) - 1;

			// Run through each variable
			foreach($variable_array as $variable_array_index => $variable) {

				if(isset($_SERVER[$variable])) {

					if($variable_array_index == $variable_array_index_last) {

						return $_SERVER[$variable];

					} else {

						if(!empty($_SERVER[$variable])) return $_SERVER[$variable];
					}
				}
			}

			return '';
		}

		// Get hostname
		public static function get_hostname() {

			$protocol = isset($_SERVER) && isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
			return str_replace($protocol, '', strtolower(get_site_url()));
		}

		// Get object meta value
		public static function get_object_meta_value($object, $meta_key, $default_value = '') {

			// Check for meta data
			if(!isset($object->meta)) { return $default_value; }

			// Check for meta_key
			if(!isset($object->meta->{$meta_key})) { return $default_value; }

			return $object->meta->{$meta_key};
		}

		// Get array meta value
		public static function get_array_meta_value($array, $meta_key, $default_value = '') {

			// Check for meta data
			if(!isset($array['meta'])) { return $default_value; }

			// Check for meta_key
			if(!isset($array['meta']->{$meta_key})) { return $default_value; }

			return $array['meta']->{$meta_key};
		}

		// Extract numbers from string
		public static function get_tel($phone) {

			return preg_replace('/[^+\d]+/', '', $phone);
		}

		// Extract float with fraction from string
		public static function get_number($number_input, $default_value = 0, $process_currency = false, $decimals = false) {

			// Convert numbers to text
			if(is_numeric($number_input)) { $number_input = strval($number_input); }

			// Check input is a string
			if(!is_string($number_input)) { return 0; }

			// Trim input
			$number_input = trim($number_input);

			// Convert from current currency
			if($process_currency) {

				$currency = self::get_currency();

				// Filter characters required for parseFloat
				$decimal_separator = $currency['decimal_separator'];
				$thousand_separator = $currency['thousand_separator'];

				// Ensure the decimal separator setting is included in the regex (Add ,. too in case default value includes alternatives)
				$number_input = preg_replace('/[^0-9-' . $decimal_separator . ']/', '', $number_input);

				if($decimal_separator === $thousand_separator) {

					// Convert decimal separators to periods so parseFloat works
					if(substr($number_input, -3, 1) === $decimal_separator) {

						$decimal_index = (strlen($number_input) - 3);
						$number_input = substr($number_input, 0, $decimal_index) . '[dec]' . substr($number_input, $decimal_index + 1);
					}

					// Remove thousand separators
					$number_input = str_replace($thousand_separator, '', $number_input);

					// Replace [dec] back to decimal separator for parseFloat
					$number_input = str_replace('[dec]', '.', $number_input);

				} else {

					// Replace [dec] back to decimal separator for parseFloat
					$number_input = str_replace($decimal_separator, '.', $number_input);
				}
			}

			// parseFloat converts decimal separator to period to ensure that function works
			$number_output = (trim($number_input) === '') ? $default_value : (!is_numeric($number_input) ? $default_value : floatVal($number_input));

			// Round
			if($decimals !== false) { $number_output = round(parseFloat($number_output), $decimals); }

			return $number_output;
		}

		// Get array of MIME types
		public static function get_mime_array($value) {

			if(is_array($value)) { return $value; }

			$mime_array = explode(',', $value);
			$mime_array_return = array();

			foreach($mime_array as $mime) {

				$mime_split = $mime_array = explode('/', $mime);
				if(count($mime_split) !== 2) { continue; }
				if(strlen($mime_split[0]) == 0) { continue; }
				if(strlen($mime_split[1]) == 0) { continue; }
				$mime_array_return[] = strtolower(trim($mime));
			}

			return $mime_array_return;
		}

		// Add datestamp to filename
		public static function filename_datestamp($filename_prefix, $filename_suffix) {

			$filename = $filename_prefix . current_time('-Y-m-d-H-i-s') . '.' . $filename_suffix;
			return sanitize_file_name($filename);
		}

		// Output file download headers
		public static function file_download_headers($filename, $mime_type, $encoding = 'binary') {

			$filename = sanitize_file_name($filename);			// WordPress function

			// HTTP headers
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private', false);
			header('Content-Type: ' . $mime_type);
			header('Content-Disposition: attachment; filename=' . $filename);
			header('Content-Transfer-Encoding: ' . $encoding);
		}

		public static function hex_to_hsl($hex) {

			$hex = array($hex[0].$hex[1], $hex[2].$hex[3], $hex[4].$hex[5]);
			$rgb = array_map(function($part) {

				return hexdec($part) / 255;

			}, $hex);

			$max = max($rgb);
			$min = min($rgb);

			$l = ($max + $min) / 2;

			if ($max == $min) {

				$h = $s = 0;

			} else {

				$diff = $max - $min;
				$s = $l > 0.5 ? $diff / (2 - $max - $min) : $diff / ($max + $min);

				switch($max) {

					case $rgb[0]:
						$h = ($rgb[1] - $rgb[2]) / $diff + ($rgb[1] < $rgb[2] ? 6 : 0);
						break;

					case $rgb[1]:
						$h = ($rgb[2] - $rgb[0]) / $diff + 2;
						break;

					case $rgb[2]:
						$h = ($rgb[0] - $rgb[1]) / $diff + 4;
						break;
				}

				$h /= 6;
			}

			return array($h, $s, $l);
		}

		public static function hsl_to_rgb($hsl) {

			list($h, $s, $l) = $hsl;

			$r; 
			$g; 
			$b;

			$c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
			$x = $c * ( 1 - abs( fmod( ( $h / 60 ), 2 ) - 1 ) );
			$m = $l - ( $c / 2 );
			if ( $h < 60 ) {
				$r = $c;
				$g = $x;
				$b = 0;
			} else if ( $h < 120 ) {
				$r = $x;
				$g = $c;
				$b = 0;			
			} else if ( $h < 180 ) {
				$r = 0;
				$g = $c;
				$b = $x;					
			} else if ( $h < 240 ) {
				$r = 0;
				$g = $x;
				$b = $c;
			} else if ( $h < 300 ) {
				$r = $x;
				$g = 0;
				$b = $c;
			} else {
				$r = $c;
				$g = 0;
				$b = $x;
			}
			$r = ( $r + $m ) * 255;
			$g = ( $g + $m ) * 255;
			$b = ( $b + $m  ) * 255;

			return array( floor( $r ), floor( $g ), floor( $b ) );
		}

		public static function hsl_to_hex($hsl) {

			list($h, $s, $l) = $hsl;

			if ($s == 0) {

				$r = $g = $b = 1;

			} else {

				$q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
				$p = 2 * $l - $q;

				$r = self::hue_to_rgb($p, $q, $h + 1/3);
				$g = self::hue_to_rgb($p, $q, $h);
				$b = self::hue_to_rgb($p, $q, $h - 1/3);
			}

			return self::rgb_to_hex($r) . self::rgb_to_hex($g) . self::rgb_to_hex($b);
		}

		public static function hue_to_rgb($p, $q, $t) {

			if ($t < 0) $t += 1;
			if ($t > 1) $t -= 1;
			if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
			if ($t < 1/2) return $q;
			if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;

			return $p;
		}

		public static function rgb_to_hex($rgb) {

			return str_pad(dechex($rgb * 255), 2, '0', STR_PAD_LEFT);
		}

		public static function hex_hsl_adjust($hex, $hPercent, $sPercent, $lPercent) {

			if($hex == '') { return ''; }

			// Check for hash
			$has_hash = (substr($hex, 0, 1) == '#');
			if($has_hash) { $hex = substr($hex, 1); }

			// Convert to HSL
			$hsl = self::hex_to_hsl($hex);

			$h = $hsl[0];
			$s = $hsl[1];
			$l = $hsl[2];

			// Adjust
			$h = $h + (($hPercent / 100) * $h);
			$s = $s + (($sPercent / 100) * $s);
			$l = $l + (($lPercent / 100) * $l);

			// Convert back to hex
			return ($has_hash ? '#' : '') . self::hsl_to_hex(array($h, $s, $l));
		}

		// Green (0) --> Lime Green (25) --> Yellow (50) --> Orange (75) --> Red (100)
		public static function get_green_to_red_rgb($value, $min = 0, $max = 100) {

			// Calculate ratio
			$ratio = $value / $max;
			if($ratio < 0) { $ratio = 0; }
			if($ratio > 1) { $ratio = 1; }

			// Red
			$r = ($ratio * 2) * 255;
			$r = ($r > 255) ? 255 : $r;

			// Green
			$g = (2 - ($ratio * 2)) * 255;
			$g = ($g > 255) ? 255 : $g;

			// Blue
			$b = 0;

			return "rgb($r,$g,$b)";
		}

		public static function hex_lighten_percentage($hex, $percentage) {

			$rgbhex = str_split(trim($hex, '# '), 2);
			$rgbdec = array_map('hexdec', $rgbhex);

			$hsv = self::rgb_to_hsv($rgbdec[0], $rgbdec[1], $rgbdec[2]);

			$hsv_s = $hsv['S'];
			$hsv['S'] = $hsv_s - ($hsv_s * ($percentage / 100));

			$hsv_v = $hsv['V'];
			$hsv_diff = (1 - $hsv_v);
			$hsv['V'] = $hsv_v + ($hsv_diff * ($percentage / 100));

			$rgblight = self::hsv_to_rgb($hsv['H'], $hsv['S'], $hsv['V']);
			$output = array_map('dechex', $rgblight);
			$output = array_map('self::zero_pad', $output); // gotta zero-pad single-digit hex

			return '#'.implode($output);
		}

		public static function hex_darken_percentage($hex, $percentage) {

			$rgbhex = str_split(trim($hex, '# '), 2);
			$rgbdec = array_map('hexdec', $rgbhex);

			$hsv = self::rgb_to_hsv($rgbdec[0], $rgbdec[1], $rgbdec[2]);

			$hsv_v = $hsv['V'];
			$hsv['V'] = ($hsv_v * ((100 - $percentage) / 100));

			$rgblight = self::hsv_to_rgb($hsv['H'], $hsv['S'], $hsv['V']);
			$output = array_map('dechex', $rgblight);
			$output = array_map('self::zero_pad', $output); // gotta zero-pad single-digit hex

			return '#'.implode($output);
		}

		public static function zero_pad($num) {
			$limit = 2;
			return (strlen($num) >= $limit) ? $num : self::zero_pad('0' . $num);
		}

		public static function rgb_to_hsv($R, $G, $B) {  // RGB Values:Number 0-255 

			$HSL = array(); 

			$var_R = ($R / 255); 
			$var_G = ($G / 255); 
			$var_B = ($B / 255); 

			$var_Min = min($var_R, $var_G, $var_B); 
			$var_Max = max($var_R, $var_G, $var_B); 
			$del_Max = $var_Max - $var_Min; 

			$V = $var_Max; 

			if ($del_Max == 0) { 

				$H = 0; 
				$S = 0; 

			} else { 

				$S = $del_Max / $var_Max; 

				$del_R = ( ( ( $var_Max - $var_R ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max; 
				$del_G = ( ( ( $var_Max - $var_G ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max; 
				$del_B = ( ( ( $var_Max - $var_B ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max; 

				if      ($var_R == $var_Max) $H = $del_B - $del_G; 
				else if ($var_G == $var_Max) $H = ( 1 / 3 ) + $del_R - $del_B; 
				else if ($var_B == $var_Max) $H = ( 2 / 3 ) + $del_G - $del_R; 

				if ($H<0) $H++; 
				if ($H>1) $H--; 
			}

			$HSL['H'] = $H; 
			$HSL['S'] = $S; 
			$HSL['V'] = $V; 

			return $HSL; 
		} 

		public static function hsv_to_rgb($H, $S, $V) {  // HSV Values:Number 0-1 

			$RGB = array(); 

			if($S == 0) 
			{ 
				$R = $G = $B = $V * 255; 
			} 
			else 
			{ 
				$var_H = $H * 6; 
				$var_i = floor( $var_H ); 
				$var_1 = $V * ( 1 - $S ); 
				$var_2 = $V * ( 1 - $S * ( $var_H - $var_i ) ); 
				$var_3 = $V * ( 1 - $S * (1 - ( $var_H - $var_i ) ) ); 

				if       ($var_i == 0) { $var_R = $V     ; $var_G = $var_3  ; $var_B = $var_1 ; } 
				else if  ($var_i == 1) { $var_R = $var_2 ; $var_G = $V      ; $var_B = $var_1 ; } 
				else if  ($var_i == 2) { $var_R = $var_1 ; $var_G = $V      ; $var_B = $var_3 ; } 
				else if  ($var_i == 3) { $var_R = $var_1 ; $var_G = $var_2  ; $var_B = $V     ; } 
				else if  ($var_i == 4) { $var_R = $var_3 ; $var_G = $var_1  ; $var_B = $V     ; } 
				else                   { $var_R = $V     ; $var_G = $var_1  ; $var_B = $var_2 ; } 

				$R = $var_R * 255; 
				$G = $var_G * 255; 
				$B = $var_B * 255; 
			} 

			$RGB['R'] = $R; 
			$RGB['G'] = $G; 
			$RGB['B'] = $B; 

			return $RGB; 
		} 

		// Parse WS Form variables
		public static function parse_variables_process($parse_string, $form = false, $submit = false, $content_type = 'text/html', $depth = 1) {

			if(!is_string($parse_string)) { return $parse_string; }

			// Checks to speed up this function
			if(strpos($parse_string, '#') === false) { return $parse_string; }

			// Get post
			$post = self::get_post_root();

			// Get user
			$user = self::get_user();

			// Initialize variables
			$variables = [];
			$variables_single_parse = [];

			// Parse type
			$lookups_contain_singles = false;

			// Initialize meta data
			$form_meta = self::parse_variables_form_meta($form);
			$submit_meta = self::parse_variables_submit_meta($submit);

			// Check for too many iterations
			if($depth > 100) { return ''; }

			$parse_variables_config = WS_Form_Config::get_parse_variables();

			// Process each parse variable key
			foreach($parse_variables_config as $parse_variables_key => $parse_variables) {

				if(strpos($parse_string, '#' . $parse_variables_key) === false) { continue; }

				foreach($parse_variables['variables'] as $parse_variable => $parse_variable_config) {

					if(strpos($parse_string, '#' . $parse_variable) === false ) { continue; }

					$parsed_variable = '';

					switch($parse_variable) {

						case 'email_tracking' :

							if(!isset($submit->meta)) { break; }

							// Tracking
							$tracking_array = WS_Form_Config::get_tracking(false);

							foreach($tracking_array as $meta_key => $tracking) {

								// Check meta_key is enabled
								if(($form === false) || !self::get_object_meta_value($form, $meta_key, false)) { break; }

								$label = $tracking['label'];

								// Add to fields_all HTML
								switch($content_type) {

									case 'text/html' :

										$parsed_variable .= '<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 10px;"><strong>' . htmlentities($label) . '</strong><br />#' . $meta_key . '</p>';
										break;

									default :

										$parsed_variable .= $label . "\n" . $value . "\n\n";
										break;
								}
							}

							$parsed_variable = self::parse_variables_process($parsed_variable, $form, $submit, $content_type, $depth + 1);

							$variables[$parse_variable] = $parsed_variable;

							break;
						default :

							// Assign value
							$parse_variable_value = (isset($parse_variable_config['value'])) ? $parse_variable_config['value'] : '';
							$parse_variable_attributes = (isset($parse_variable_config['attributes'])) ? $parse_variable_config['attributes'] : false;

							// Single parse? (Used if different value returned each parse, e.g. random_number)
							$parse_variable_single_parse = isset($parse_variable_config['single_parse']) ? $parse_variable_config['single_parse'] : false;

							// If no attributes specified, then just set the value
							if(($parse_variable_attributes === false) && ($parse_variable_value !== false)) { $variables[$parse_variable] = $parse_variable_value; break; }

							// Get number of attributes required
							$variable_attribute_count = isset($parse_variable_config['attributes']) ? count($parse_variable_attributes) : 0;

							// Handle variables
							if($variable_attribute_count > 0) {

								// Do until no more found
								$variable_index_start = 0;
								do {

									// Find position of variable and brackets
									$variable_index_of = strpos($parse_string, '#' . $parse_variable, $variable_index_start);

									// No more instances of variable found
									if($variable_index_of === false) { continue; }

									// Find bracket positions
									$variable_index_of_bracket_start = false;
									$variable_index_of_bracket_finish = false;
									$parse_string_function = substr($parse_string, $variable_index_of + strlen('#' . $parse_variable));

									// Bracket should immediately follow the variable name
									if(substr($parse_string_function, 0, 1) == '(') {

										$variable_index_of_bracket_start = $variable_index_of + strlen('#' . $parse_variable);
										$variable_index_of_bracket_finish = strpos($parse_string, ')', $variable_index_of_bracket_start);
									}

									// Check brackets found
									if(	($variable_index_of_bracket_start === false) ||
										($variable_index_of_bracket_finish === false) ) {

										// Shift index to look for next instance
										$variable_index_start = $variable_index_of + strlen('#' . $parse_variable);

										// Get full string to parse
										$parse_variable_full = '#' . $parse_variable;

										// No brackets found so set attributes as blank
										$variable_attribute_array = [];

									} else {

										// Shift index to look for next instance
										$variable_index_start = $variable_index_of_bracket_finish + 1;

										// Ensure bracket starts immediate after parse_variable
										if((($variable_index_of_bracket_start - $variable_index_of) - strlen('#' . $parse_variable)) !== 0) { continue; };

										// Get full string to parse
										$parse_variable_full = substr($parse_string, $variable_index_of, ($variable_index_of_bracket_finish + 1) - $variable_index_of);

										// Get attribute string
										$variable_attribute_string = substr($parse_string, $variable_index_of_bracket_start + 1, ($variable_index_of_bracket_finish - 1) - $variable_index_of_bracket_start);

										// Replace non standard double quotes
										$variable_attribute_string = str_replace('“', '"', $variable_attribute_string);
										$variable_attribute_string = str_replace('”', '"', $variable_attribute_string);

										// Get attribute array
										$variable_attribute_array = str_getcsv($variable_attribute_string, ',');

										// Trim and strip double quotes
										foreach($variable_attribute_array as $key => $e) {

											$e = trim($e);
											$e = preg_replace('/^"(.+(?="$))"$/', '', $e);
											$variable_attribute_array[$key] = $e;
										}
									}

									// Check each attribute
									foreach($parse_variable_attributes as $parse_variable_attributes_index => $parse_variable_attribute) {

										$parse_variable_attribute_id = $parse_variable_attribute['id'];

										// Was attribute provided for this index?
										$parse_variable_attribute_supplied = isset($variable_attribute_array[$parse_variable_attributes_index]);

										// Check required
										$parse_variable_attribute_required = (isset($parse_variable_attribute['required']) ? $parse_variable_attribute['required'] : true);
										if($parse_variable_attribute_required && !$parse_variable_attribute_supplied) {

											// Syntax error - Attribute count
											self::throw_error(sprintf(__('Syntax error, missing attribute: %s (Expected: %s)', 'ws-form'), '#' . $parse_variable, $parse_variable_attribute_id));
											continue;
										}

										// Check default
										$parse_variable_attribute_default = isset($parse_variable_attribute['default']) ? $parse_variable_attribute['default'] : false;

										if(($parse_variable_attribute_default !== false) && !$parse_variable_attribute_supplied) {

											$variable_attribute_array[$parse_variable_attributes_index] = $parse_variable_attribute_default;
										}

										// Check validity
										$parse_variable_attribute_valid = isset($parse_variable_attribute['valid']) ? $parse_variable_attribute['valid'] : false;
										if($parse_variable_attribute_valid !== false) {

											if(!in_array($variable_attribute_array[$parse_variable_attributes_index], $parse_variable_attribute_valid)) {

												// Syntax error - Attribute count
												self::throw_error(sprintf(__('Syntax error, invalid attribute: %s (Expected: %s)', 'ws-form'), '#' . $parse_variable, implode(', ', $parse_variable_attribute_valid)));
											}
										}
									}

									// Process variable
									switch($parse_variable) {

										case 'query_var' :

											$parsed_variable = isset($_GET) ? isset($_GET[$variable_attribute_array[0]]) ? $_GET[$variable_attribute_array[0]] : '' : '';	// phpcs:ignore
											if($content_type == 'text/html') { $parsed_variable = htmlentities($parsed_variable); }
											break;

										case 'post_var' :

											$parsed_variable = isset($_POST) ? isset($_POST[$variable_attribute_array[0]]) ? $_POST[$variable_attribute_array[0]] : '' : '';	// phpcs:ignore
											if($content_type == 'text/html') { $parsed_variable = htmlentities($parsed_variable); }
											break;

										case 'email_submission' :

											if(!isset($submit->meta)) { break; }

											$render_group_labels = $variable_attribute_array[0];
											$render_section_labels = $variable_attribute_array[1];
											$render_field_labels = $variable_attribute_array[2];
											$render_blank_fields = ($variable_attribute_array[3] == 'true');
											$render_static_fields = ($variable_attribute_array[4] == 'true');

											$value = self::parse_variables_fields_all((object) $form, $submit, $content_type, $render_group_labels, $render_section_labels, $render_field_labels, $render_blank_fields, $render_static_fields);

											$parsed_variable = self::parse_variables_process($value, $form, $submit, $content_type, $depth + 1);

											break;

										case 'field' :

											if(!isset($submit->meta)) { break; }

											if(!is_numeric($variable_attribute_array[0])) { break; }

											$field_id = $variable_attribute_array[0];

											if(!isset($submit->meta[WS_FORM_FIELD_PREFIX . $field_id])) { break; }

											// Get value
											$meta = $submit->meta[WS_FORM_FIELD_PREFIX . $field_id];
											$value = self::parse_variables_meta_value($form, $meta, $content_type);

											$parsed_variable = self::parse_variables_process($value, $form, $submit, $content_type, $depth + 1);

											break;

										case 'post_meta' :

											if($post === null) { break; }

											$post_meta = get_post_meta($post->ID);
											if($post_meta === false) { break; }

											$meta_key = $variable_attribute_array[0];

											if(!isset($post_meta[$meta_key])) { break; }
											if(!isset($post_meta[$meta_key][0])) { break; }

											$parsed_variable = $post_meta[$meta_key][0];
											if(is_array($parsed_variable)) { $parsed_variable = serialize($parsed_variable); }
											break;

										case 'user_meta' :

											// Check we have user data
											if(($user === false) || !$user->ID) { break; }

											$meta_key = $variable_attribute_array[0];

											$parsed_variable = get_user_meta($user->ID, $meta_key, true);
											if(is_array($parsed_variable)) { $parsed_variable = serialize($parsed_variable); }

											break;

										case 'acf_repeater_field' :

											if($post === null) { break; }

											$parent_field = $variable_attribute_array[0];
											$sub_field = $variable_attribute_array[1];

											$parent_field_array = explode(',', $parent_field);
											foreach($parent_field_array as $key => $value) {

												$parent_field_array[$key] = trim($value);
											}

											$parsed_variable = self::acf_repeater_field_walker($parent_field_array, $sub_field, $post);

											break;

										// Date
										case 'post_date_custom' :
										case 'server_date_custom' :

											$parsed_variable = date($variable_attribute_array[0]);
											if($content_type == 'text/html') { $parsed_variable = htmlentities($parsed_variable); }
											break;

										// Random number
										case 'random_number' :

											$random_number_min = intval($variable_attribute_array[0]);
											$random_number_max = intval($variable_attribute_array[1]);
											$parsed_variable = rand($random_number_min, $random_number_max);
											break;

										// Random string
										case 'random_string' :

											$random_string_length = intval($variable_attribute_array[0]);
											$random_string_characters = $variable_attribute_array[1];
											$random_string_character_length = strlen($random_string_characters) - 1;
											$parsed_variable = '';
											for($random_string_index = 0; $random_string_index < $random_string_length; $random_string_index++) { $parsed_variable .= $random_string_characters[rand(0, $random_string_character_length)]; }
											break;

										// Date
										case 'blog_date_custom' :

											$parsed_variable = date($variable_attribute_array[0], current_time('timestamp'));
											if($content_type == 'text/html') { $parsed_variable = htmlentities($parsed_variable); }
											break;

										// User
										case 'user_lost_password_url' :

											// Check we have user data
											if(($user === false) || !$user->ID) { break; }

											// Check we can produce a lost password URL
											if(!(

												isset($user->lost_password_key) && 
												($user->lost_password_key != '') && 
												isset($user->user_login) && 
												($user->user_login != '')

											)) { break; }

											// Get path
											$path = $variable_attribute_array[0];

											if($path !== '') {

												$parsed_variable = network_site_url(sprintf('%s?key=%s&login=%s', $path, rawurlencode($user->lost_password_key),rawurlencode($user->user_login)));

											} else {

												$parsed_variable = network_site_url(sprintf('wp-login.php?action=rp&key=%s&login=%s', rawurlencode($user->lost_password_key), rawurlencode($user->user_login)), 'login');
											}

											break;
									}

									// Assign value
									if($parse_variable_single_parse) {

										$variables_single_parse[substr($parse_variable_full, 1)] = $parsed_variable;
									} else {

										$variables[substr($parse_variable_full, 1)] = $parsed_variable;
									}

								} while ($variable_index_of !== false);
							}
					}
				}
			}

			// Form
			if(strpos($parse_string, 'form')) {

				$variables['form_label'] = $form_meta['label'];
				$variables['form_id'] = $form_meta['id'];
				$variables['form_checksum'] = $form_meta['published_checksum'];

				// These variables are only available on the public side
				$variables['form_obj_id'] = '';
				$variables['form_framework'] = '';
				$variables['form_instance_id'] = 0;
			}

			// Post
			if(strpos($parse_string, 'post')) {

				$variables['post_id'] = (!is_null($post) ? $post->ID : '');
				$variables['post_type'] = (!is_null($post) ? $post->post_type : '');
				$variables['post_title'] = (!is_null($post) ? $post->post_title : '');
				$variables['post_content'] = (!is_null($post) ? $post->post_content : '');
				$variables['post_excerpt'] = (!is_null($post) ? $post->post_excerpt : '');
				$variables['post_url'] = (!is_null($post) ? get_permalink($post->ID) : '');
				$variables['post_url_edit'] = (!is_null($post) ? get_edit_post_link($post->ID) : '');
				$variables['post_date'] = (!is_null($post) ? date(get_option('date_format'), strtotime($post->post_date)) : '');
				$variables['post_time'] = (!is_null($post) ? date(get_option('time_format'), strtotime($post->post_date)) : '');
			}

			// User
			if(strpos($parse_string, 'user')) {

				$user_id = (($user === false) ? 0 : $user->ID);

				$variables['user_id'] = $user_id;
				$variables['user_login'] = (($user_id > 0) ? $user->user_login : '');
				$variables['user_nicename'] = (($user_id > 0) ? $user->user_nicename : '');
				$variables['user_email'] = (($user_id > 0) ? $user->user_email : '');
				$variables['user_display_name'] = (($user_id > 0) ? $user->display_name : '');
				$variables['user_url'] = (($user_id > 0) ? $user->user_url : '');
				$variables['user_registered'] = (($user_id > 0) ? $user->user_registered : '');
				$variables['user_first_name'] = (($user_id > 0) ? get_user_meta($user_id, 'first_name', true) : '');
				$variables['user_last_name'] = (($user_id > 0) ? get_user_meta($user_id, 'last_name', true) : '');
				$variables['user_bio'] = (($user_id > 0) ? get_user_meta($user_id, 'description', true) : '');
				$variables['user_nickname'] = (($user_id > 0) ? get_user_meta($user_id, 'nickname', true) : '');
				$variables['user_admin_color'] = (($user_id > 0) ? get_user_meta($user_id, 'admin_color', true) : '');
				$variables['user_lost_password_key'] = (($user_id > 0) ? $user->lost_password_key : '');
			}

			// Submit
			if(strpos($parse_string, 'submit')) {

				$variables['submit_id'] = $submit_meta['id'];
				$variables['submit_user_id'] = $submit_meta['user_id'];
				$variables['submit_hash'] = $submit_meta['hash'];

				if($form_meta['id'] && $submit_meta['id']) {

					$variables['submit_admin_url'] = get_admin_url(null, 'admin.php?page=ws-form-submit&id=' . $form_meta['id'] . '#' . $submit_meta['id']);

				} else {

					$variables['submit_admin_url'] = get_admin_url(null, 'admin.php?page=ws-form');
				}
			}

			// E-Mail
			if(strpos($parse_string, 'email')) {

				$variables['email_promo'] = sprintf(__('Powered by %s.', 'ws-form'), sprintf(($content_type == 'text/html') ? '<a href="%s" style="color: #999999; font-size: 12px; text-align: center; text-decoration: none;">WS Form</a>' : 'WS Form %s', self::get_plugin_website_url('', 'email_footer')));
			}

			// E-mail - CSS
			if(strpos($parse_string, 'email_css')) {

				$ws_form_css = new WS_Form_CSS();
				$css = $ws_form_css->get_email();
				$variables['email_css'] = $css;
			}

			// E-mail - E-Commerce
			if(strpos($parse_string, 'email_ecommerce')) {

				$variables['email_ecommerce'] = self::parse_variables_ecommerce($submit);
			}

			// E-Commerce
			if(strpos($parse_string, 'ecommerce')) {

				// Get e-commerce config
				$ecommerce_config = WS_Form_Config::get_ecommerce();

				foreach($ecommerce_config['cart_price_types'] as $meta_key => $cart_price_type) {

					$meta_key = 'ecommerce_cart_' . $meta_key;

					if(strpos($parse_string, $meta_key)) {

						$value = isset($submit->meta[$meta_key]) ? $submit->meta[$meta_key] : false;

						if(($value !== '') && ($value !== false)) {

							$variables[$meta_key] = $value;
							$variables[$meta_key . '_currency'] = self::get_price($value);

						} else {

							$variables[$meta_key] = self::get_price(0);
						}
					}
				}

				foreach($ecommerce_config['meta_keys'] as $meta_key => $meta_key_config) {

					if(strpos($parse_string, $meta_key)) {

						$value = isset($submit->meta[$meta_key]) ? $submit->meta[$meta_key] : false;

						if(($value !== '') && ($value !== false)) {

							// Lookup?
							if(
								(isset($meta_key_config['lookup'])) &&
								(isset($ecommerce_config[$meta_key_config['lookup']])) &&
								(isset($ecommerce_config[$meta_key_config['lookup']][$value]))
							) {

								$value = $ecommerce_config[$meta_key_config['lookup']][$value]['label'];

							}

							// Type
							if(isset($meta_key_config['type'])) {

								switch($meta_key_config['type']) {

									case 'price' : $variables[$meta_key . '_currency'] = self::get_price($value); break;
								}
							}

							$variables[$meta_key] = $value;
						}
					}
				}
			}

			// Tracking
			if(strpos($parse_string, 'tracking') && isset($submit->meta)) {

				$tracking_array = WS_Form_Config::get_tracking(false);

				foreach($tracking_array as $meta_key => $tracking) {

					// Check tracking meta_key is enabled
					if(($form === false) || !self::get_object_meta_value($form, $meta_key, false)) { continue; }

					// Check tracking meta_key exists in parse string
					if(strpos($parse_string, '#' . $meta_key) === false) { continue; }

					// Check meta exists in submit data
					if(!isset($submit->meta[$meta_key])) {

						$value = '';

					} else {

						// Get value
						$meta = array(

							'type'	=>	$tracking['type'],
							'value'	=>	$submit->meta[$meta_key]
						);

						// Get parsed version
						$value = self::parse_variables_meta_value($form, $meta, $content_type);
					}

					$variables[$meta_key] = $value;
				}
			}

			// Final sort
			krsort($variables);

			// Parse until no more changes made
			$parse_string_before = $parse_string;
			$parse_string = self::mask_parse($parse_string, $variables);
			$parse_string = self::mask_parse($parse_string, $variables_single_parse, '#', true);
			$parse_string = apply_filters('wsf_config_parse_string', $parse_string);

			if(
				($parse_string !== $parse_string_before) &&
				(strpos($parse_string, '#') !== false)
			) {

				$parse_string = self::parse_variables_process($parse_string, $form, $submit, $content_type, $depth + 1);
			}

			return $parse_string;
		}

		// ACF parent field crawler
		public static function acf_repeater_field_walker($parent_field_array, $sub_field, $post) {

			$return_value = '';

			$parent_field = array_shift($parent_field_array);

			if(have_rows($parent_field, $post->ID)) {

				while(have_rows($parent_field, $post->ID)) {

					the_row();

					$row = get_row();

					if(count($parent_field_array) == 0) {

						$sub_field_value = get_sub_field($sub_field);

						if($sub_field_value !== false) { return $sub_field_value; }

					} else {

						$return_value = self::acf_repeater_field_walker($parent_field_array, $sub_field);
					}
				}
			}

			return $return_value;
		}

		// Parse form data for use with parse_variables
		public static function parse_variables_meta_value($form, $meta, $content_type) {

			$type = $meta['type'];
			$value = $meta['value'];

			if($value == '') { return ''; }

			// HTML encode values
			if($content_type == 'text/html' && !is_array($value)) {

				$value = htmlentities($value);

				switch($type) {

					case 'url' :

						$value = sprintf('<a href="%1$s" target="_blank">%1$s</a>', $value);
						break;

					case 'tel' :

						$value = sprintf('<a href="tel:%1$s">%1$s</a>', $value);
						break;

					case 'email' :

						if(filter_var($value, FILTER_VALIDATE_EMAIL)) {

							$value = sprintf('<a href="mailto:%1$s">%1$s</a>', $value);
						}
						break;

					case 'ip' :

						// Get lookup URL mask
						$ip_lookup_url_mask = self::option_get('ip_lookup_url_mask');
						if(empty($ip_lookup_url_mask)) { $value = htmlentities($value); break; }

						// Get #value for mask
						$ip_lookup_url_mask_values = array('value' => $value);

						// Build lookup URL
						$ip_lookup_url = self::mask_parse($ip_lookup_url_mask, $ip_lookup_url_mask_values);

						$value = '<a href="' . $ip_lookup_url . '" target="_blank">' . htmlentities($value) . '</a>';
						break;

					case 'latlon' :

						if(preg_match('/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/', $value) == 1) {

							// Get lookup URL mask
							$latlon_lookup_url_mask = self::option_get('latlon_lookup_url_mask');
							if(empty($latlon_lookup_url_mask)) { $value = htmlentities($value); break; }

							// Get #value for mask
							$latlon_lookup_url_mask_values = array('value' => $value);

							// Build lookup URL
							$latlon_lookup_url = self::mask_parse($latlon_lookup_url_mask, $latlon_lookup_url_mask_values);

							$value = '<a href="' . $latlon_lookup_url . '" target="_blank">' . htmlentities($value) . '</a>';

						} else {

							switch(intval($value)) {

								case 1 :

									$value = __('User denied the request for geo location', 'ws-form');
									break;

								case 2 :

									$value = __('Geo location information was unavailable', 'ws-form');
									break;

								case 3 :

									$value = __('The request to get user geo location timed out', 'ws-form');
									break;

								default :

									$value = '-';
							}
						}
						break;
				}
			}

			// Process by field type
			switch($type) {

				case 'file' :
				case 'signature' :

					$files = $meta['value'];

					if(empty($files)) { break; }

					// Get embed setting
					$action_email_embed_images = self::option_get('action_email_embed_images', true);

					$value_array = array();

					foreach($files as $file) {

						$file_name = $file['name'];
						$file_size = self::get_file_size($file['size']);
						$file_type = $file['type'];
						$file_path = $file['path'];

						$file_data = false;
						$file_html = $file_name . ' (' . $file_size . ')';

						if($action_email_embed_images) {

							switch($file_type) {

								case 'image/gif':
								case 'image/png':
								case 'image/jpeg':
								case 'image/svg+xml':

									// Get base upload_dir
									$upload_dir = wp_upload_dir()['basedir'];
									$filename_source = $upload_dir . '/' . $file_path;

									$file_data = @file_get_contents($filename_source);
							}

							if($file_data !== false) {

								switch($file_type) {

									case 'image/gif':
									case 'image/png':
									case 'image/jpeg':

										$file_html = sprintf('<img src="data:%s;base64,%s" style="max-width: 100%%;" />', $file_type, base64_encode($file_data));
										break;

									case 'image/svg+xml':

										$file_html = $file_data;
										break;
								}
							}
						}

						$value_array[] = $file_html;
					}
	
					$value = implode((($content_type == 'text/html') ? '<br />' : "\n"), $value_array);

					break;

				case 'datetime' :

					$fields = self::get_fields_from_form($form);

					if(!isset($fields[$meta['id']])) { break; }

					$field = $fields[$meta['id']];

					$input_type_datetime = self::get_object_meta_value((object)$field, 'input_type_datetime', 'date');

					// If submit is read from database, it is split into MySQL and presentable formats
					if(is_array($value) && isset($value['mysql'])) { $value = $value['mysql']; }

					$value = self::get_date_by_type($value, $input_type_datetime);

					break;

				case 'select' :
				case 'checkbox' :
				case 'radio' :

					$fields = self::get_fields_from_form($form);

					if(!isset($fields[$meta['id']])) { break; }

					$field = $fields[$meta['id']];

					$default_value = is_array($value) ? (($content_type == 'text/html') ? implode("<br />", $value) : implode("\n", $value)) : $value;

					$value = self::get_datagrid_value($field, $value, $content_type, $default_value);

					break;

				default :

					$value = is_array($value) ? (($content_type == 'text/html') ? implode("<br />", $value) : implode("\n", $value)) : $value;
			}

			return $value;
		}

		// Parse form data for use with parse_variables
		public static function parse_variables_form_meta($form) {

			$form_meta = array(

				'id'					=>	isset($form->id) ? $form->id : '',
				'label'					=>	isset($form->label) ? $form->label : '',
				'published_checksum'	=>	isset($form->published_checksum) ? $form->published_checksum : '',
			);

			return $form_meta;
		}

		// Parse submit data for use with parse_variables
		public static function parse_variables_submit_meta($submit) {

			$submit_meta = array(

				'id'		=>	isset($submit->id) ? $submit->id : '',
				'hash'		=>	isset($submit->hash) ? $submit->hash : '',
				'user_id'	=>	isset($submit->user_id) ? $submit->user_id : '',
			);

			return $submit_meta;
		}

		// #email_ecommerce
		public static function parse_variables_ecommerce($submit) {

			$ecommerce_html = '';

			$ecommerce_rows = array();

			// Get e-commerce config
			$ecommerce_config = WS_Form_Config::get_ecommerce();

			foreach($ecommerce_config['cart_price_types'] as $meta_key => $cart_price_type) {

				$meta_key = 'ecommerce_cart_' . $meta_key;

				$value = isset($submit->meta[$meta_key]) ? $submit->meta[$meta_key] : false;

				if(($value !== '') && ($value !== false)) {

					$priority = isset($cart_price_type['priority']) ? $cart_price_type['priority'] : 10000;

					$value = self::get_price($value);
					$ecommerce_rows[] = array('label' => $cart_price_type['label'], 'value' => $value, 'priority' => $priority);

				} else {

					continue;
				}
			}

			foreach($ecommerce_config['meta_keys'] as $meta_key => $meta_key_config) {

				$value = isset($submit->meta[$meta_key]) ? $submit->meta[$meta_key] : false;

				if(($value !== '') && ($value !== false)) {

					$label = $meta_key_config['label'];
					$priority = isset($meta_key_config['priority']) ? $meta_key_config['priority'] : 10000;

					// Lookup?
					if(
						(isset($meta_key_config['lookup'])) &&
						(isset($ecommerce_config[$meta_key_config['lookup']])) &&
						(isset($ecommerce_config[$meta_key_config['lookup']][$value]))
					) {

						$value = $ecommerce_config[$meta_key_config['lookup']][$value]['label'];
					}

					// Type
					if(isset($meta_key_config['type'])) {

						switch($meta_key_config['type']) {

							case 'price' : $value = self::get_price($value); break;
						}
					}

					// Push to e-commerce rows
					$ecommerce_rows[] = array('label' => $label, 'value' => $value, 'priority' => $priority);
				}
			}

			// Sort e-commerce rows by priority
			uasort($ecommerce_rows, function ($ecommerce_rows_1, $ecommerce_rows_2) {

				return $ecommerce_rows_1['priority'] < $ecommerce_rows_2['priority'] ? -1 : 1;
			});

			if(count($ecommerce_rows) > 0) {

				foreach($ecommerce_rows as $ecommerce_row) {

					$ecommerce_html .= '<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 10px;"><strong>' . htmlentities($ecommerce_row['label']) . '</strong><br />' . $ecommerce_row['value'] . '</p>';
				}
			}
			return $ecommerce_html;
		}
		// #email_submission
		public static function parse_variables_fields_all($form, $submit, $content_type, $render_group_labels, $render_section_labels, $render_field_labels, $render_blank_fields, $render_static_fields) {

			$fields_all = self::parse_variables_fields_all_group($form->groups, $form, $submit, $content_type, $render_group_labels, $render_section_labels, $render_field_labels, $render_blank_fields, $render_static_fields);

			return $fields_all;
		}

		// Run through each group
		public static function parse_variables_fields_all_group($groups, $form, $submit, $content_type, $render_group_labels, $render_section_labels, $render_field_labels, $render_blank_fields, $render_static_fields) {

			$groups_html = '';

			$group_count = count($groups);
			$group_label_join = '';

			foreach($groups as $key => $group) {

				if(isset($groups[$key]->sections)) {

					$sections_html = self::parse_variables_fields_all_section($group->sections, $form, $submit, $content_type, $render_section_labels, $render_field_labels, $render_blank_fields, $render_static_fields);

					// Should label be rendered?
					$render_label =	(
										(
											$render_group_labels == 'true'
										)
										||
										(
											($render_group_labels == 'auto') && 
											($sections_html != '')
										)
									) && self::get_object_meta_value($group, 'label_render');

					if(($group_count > 0) && $render_label) {

						switch($content_type) {

							case 'text/html' :

								$groups_html .= $group_label_join . ($render_label ? '<h2>' . htmlentities($group->label) . '</h2>' : '');
								$group_label_join = '<hr style="margin: 20px 0" />';
								break;

							default :

								$groups_html .= $group_label_join . "** " . $group->label . " **\n\n";
								$group_label_join = "\n";
						}

					}

					$groups_html .= $sections_html;
				}
			}

			return $groups_html;
		}

		// Run through each section
		public static function parse_variables_fields_all_section($sections, $form, $submit, $content_type, $render_section_labels, $render_field_labels, $render_blank_fields, $render_static_fields) {

			$sections_html = '';

			$section_count = count($sections);
			$section_label_join = '';

			// Unserialize section_repeatable
			$section_repeatable = (@unserialize($submit->section_repeatable) !== false) ? unserialize($submit->section_repeatable) : false;

			// Get field types in single dimension array
			$field_types = WS_Form_Config::get_field_types_flat();

			foreach($sections as $key => $section) {

				if(isset($sections[$key]->children)) {

					$fields_html .= self::parse_variables_fields_html_section($section->children, $form, $submit, $content_type, $render_section_labels, $render_field_labels, $render_blank_fields, $render_static_fields);
				}

				// Build section ID string
				$section_id_string = 'section_' . $section->id;
				$section_repeatable_array = (

					($section_repeatable !== false) &&
					isset($section_repeatable[$section_id_string]) &&
					isset($section_repeatable[$section_id_string]['index'])

				) ? $section_repeatable[$section_id_string]['index'] : [false];

				if(!isset($sections[$key]->fields)) { continue; }

				// Loop through section_repeatable_array
				foreach($section_repeatable_array as $section_repeatable_array_index => $section_repeatable_index) {

					$fields_html = '';

					// Check if repeatable
					$section_repeatable_suffix = '';

					// Repeatable, so render fieldset and set field_name suffix
					if($section_repeatable_index !== false) {

						// Repeatable section found
						$section_repeatable_index = intval($section_repeatable_index);
						if($section_repeatable_index <= 0) { continue; }

						// Render fieldset
						$fields_html .= '<h4>#' . (intval($section_repeatable_array_index) + 1) . '</h4>';

						// Set field_name suffix
						$section_repeatable_suffix = '_' . $section_repeatable_index;
					}

					// Process fields
					foreach($section->fields as $field) {

						$type = $field->type;
						$field_type = $field_types[$type];

						// Check for excluded fields
						$exclude_email = self::get_object_meta_value($field, 'exclude_email', false);
						if($exclude_email) { continue; }

						// Check for static fields
						$field_static = isset($field_type['static']) ? $field_type['static'] : false;
						if($render_static_fields && $field_static) {

							if($field_static === true) {

								// If static set to true, we use the mask_field
								$mask_field = isset($field_type['mask_field']) ? $field_type['mask_field'] : '';
								$fields_html .= self::parse_variables_process($mask_field, $form, $submit, $content_type);

							} else {

								// Get meta value
								$fields_html .= self::parse_variables_process(self::get_object_meta_value($field, $field_static, ''), $form, $submit, $content_type);
							}
							continue;
						}

						// Check to ensure this field is saved
						$submit_save = isset($field_type['submit_save']) ? $field_type['submit_save'] : false;
						if(!$submit_save) { continue; }

						// Get field label
						$label = $field->label;

						// Should label be rendered?
						$render_label =	(

							($render_field_labels == 'true')

							||

							(
								($render_field_labels == 'auto') &&
								self::get_object_meta_value($field, 'label_render')
							)
						);

						// Build field name
						$field_name = WS_FORM_FIELD_PREFIX . $field->id . $section_repeatable_suffix;

						if(isset($submit->meta[$field_name])) {

							// Get submit meta
							$meta = $submit->meta[$field_name];

							// Get field value
							$value = self::parse_variables_meta_value($form, $meta, $content_type);

						} else {

							$value = '';
						}

						// No submit value found
						if($value == '') {

							if($render_blank_fields) {

								$value = '-';

							} else {

								continue;
							}
						}

						// Add to fields_html HTML
						switch($content_type) {

							case 'text/html' :

								$fields_html .= '<p>' . ($render_label ? ('<strong>' . htmlentities($label) . '</strong><br />') : '') . $value . '</p>';
								break;

							default :

								$fields_html .= ($render_label ? ($label . "\n") : '') . $value . "\n\n";
								break;
						}
					}

					// Should label be rendered?
					$render_label =	(

						($render_section_labels == 'true')

						||

						(
							($render_section_labels == 'auto') &&
							($fields_html != '')
						)
					)
					&& self::get_object_meta_value($section, 'label_render')
					&& ($section_repeatable_array_index == 0);

					// Add section title if fields found
					if($render_label) {

						switch($content_type) {

							case 'text/html' :

								$sections_html .= $render_label ? '<h3>' . htmlentities($section->label) . '</h3>' : '';
								break;

							default :

								$sections_html .= $section_label_join . "* " . $section->label . " *\n\n";
								$section_label_join = "\n\n";
						}
					}

					// Add fields
					if($fields_html != '') { $sections_html .= $fields_html; }
				}
			}

			return $sections_html;
		}

		// Get value label lookup
		public static function get_datagrid_value($field, $value_array, $content_type, $default_value) {

			$value_label_lookup_array = array();

			if(!is_array($value_array)) { return $default_value; }

			$field_type = $field->type;

			switch($field_type) {

				case 'select' :

					$datagrid = self::get_object_meta_value($field, 'data_grid_select', false);
					$value_id = self::get_object_meta_value($field, 'select_field_value', 0);
					$email_id = self::get_object_meta_value($field, 'select_field_parse_variable', 0);
					break;

				case 'checkbox' :

					$datagrid = self::get_object_meta_value($field, 'data_grid_checkbox', false);
					$value_id = self::get_object_meta_value($field, 'checkbox_field_value', 0);
					$email_id = self::get_object_meta_value($field, 'checkbox_field_parse_variable', 0);
					break;

				case 'radio' :

					$datagrid = self::get_object_meta_value($field, 'data_grid_radio', false);
					$value_id = self::get_object_meta_value($field, 'radio_field_value', 0);
					$email_id = self::get_object_meta_value($field, 'radio_field_parse_variable', 0);
					break;
			}

			if($datagrid === false) { return $default_value; }
			$value_id = intval($value_id);
			$email_id = intval($email_id);

			// Get data grid rows
			if(!isset($datagrid->groups)) { return $default_value; }
			if(!is_array($datagrid->groups)) { return $default_value; }
			$groups = $datagrid->groups;

			foreach($groups as $group) {

				if(!isset($group->rows)) { continue; }
				if(!is_array($group->rows)) { continue; }

				$rows = $group->rows;

				foreach($rows as $row) {

					if(!isset($row->data)) { continue; }
					if(!is_array($row->data)) { continue; }

					$data = $row->data;

					if(!isset($data[$value_id])) { continue; }
					if(!isset($data[$email_id])) { continue; }

					if(in_array($data[$value_id], $value_array)) {

						$value_label_lookup_array[] = $data[$email_id];
					}
				}
			}

			// Return unique values to avoid duplicates if there are duplicate values
			$value_label_lookup_array = array_unique($value_label_lookup_array);

			return (($content_type == 'text/html') ? implode("<br />", $value_label_lookup_array) : implode("\n", $value_label_lookup_array));
		}

		// Check if user can do a WordPress capability (current_user_can not available on public side)
		public static function can_user($capability) {

			return current_user_can($capability);
		}

		// Loader
		public static function loader() {
?>
<!-- Loader -->
<div id="wsf-loader"></div>
<!-- /Loader -->
<?php
		}

		// Review
		public static function review() {

			// Review nag
			$review_nag = self::option_get('review_nag', false);
			if($review_nag) { return; }

			// Determine if review nag should be shown
			$install_timestamp = intval(self::option_get('install_timestamp', time(), true));
			$review_nag_show = (time() > ($install_timestamp + (WS_FORM_REVIEW_NAG_DURATION * 86400)));
			if(!$review_nag_show) { return; }

			// Show nag
			self::admin_message_render(sprintf(__('<p><strong>Thank you for using %1$s!</strong></p><p>We hope you have enjoyed using the plugin. Positive reviews from awesome users like you help others to feel confident about choosing %1$s too. If convenient, we would greatly appreciate you sharing your happy experiences with the WordPress community. Thank you in advance for helping us out!</p><p class="buttons"><a href="https://wordpress.org/support/plugin/ws-form/reviews/#new-post" class="button button-primary" onclick="wsf_review_nag_dismiss();" target="_blank">Leave a review</a> <a href="#" class="button" onclick="wsf_review_nag_dismiss();">No thanks</a></p>', 'ws-form'), WS_FORM_NAME_PRESENTABLE), 'notice-success', false, false, 'wsf-review');
?>
<script>

	function wsf_review_nag_dismiss() {

		(function($) {

			'use strict';

			// Hide nag
			$('.wsf-review').hide();

			// Call AJAX to prevent review nag appearing again
			$.ajax({ method: 'POST', url: '<?php echo esc_html(self::get_api_path('helper/review_nag/dismiss/')); ?>' });

		})(jQuery);
	}

</script>
<?php
		}

		// Check edition
		public static function is_edition($edition) {

			switch($edition) {

				case 'basic' :

					return true;

				case 'pro' :

					return true;

				default :

					return false;
			}
		}

		// Build data grid meta
		public static function build_data_grid_meta($meta_key, $group_name = false, $columns = false, $rows = false) {

			// Get base meta
			$meta_keys = WS_Form_Config::get_meta_keys();
			if(!isset($meta_keys[$meta_key])) { return false; }
			if(!isset($meta_keys[$meta_key]['default'])) { return false; }
			$meta = $meta_keys[$meta_key]['default'];

			if($group_name !== false) { $meta['groups'][0]['label'] = $group_name; }
			if($columns !== false) { $meta['columns'] = $columns; }
			if($rows !== false) { $meta['groups'][0]['rows'] = $rows; }

			return $meta;
		}

		// Get nice file size
		public static function get_file_size($bytes) {

			if($bytes >= 1048576) {

				$bytes = number_format($bytes / 1048576, 2) . ' MB';

			} elseif ($bytes >= 1024) {

				$bytes = number_format($bytes / 1024, 2) . ' KB';

			} elseif ($bytes > 1) {

				$bytes = $bytes . ' bytes';

			} elseif ($bytes == 1) {

				$bytes = $bytes . ' byte';

			} else {

				$bytes = '0 bytes';
			}

			return $bytes;
		}

		// Get nice date by type
		public static function get_date_by_type($date, $type) {

			if(empty($date)) { return ''; }

			switch($type) {

				case 'date' :

					return date(get_option('date_format'), strtotime($date));

				case 'month' :

					return date('F Y', strtotime($date));

				case 'time' :

					return date(get_option('time_format'), strtotime($date));

				case 'week' :

					return __('Week', 'ws-form') . ' ' . date('W, Y', strtotime($date));

				default :

					return date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($date));
			}
		}

		// Throw error
		public static function throw_error($error) {
			
			throw new Exception($error);
		}

		// Get system report
		public static function get_system_report_html() {

			// Get system report
			$system_report = WS_Form_Config::get_system();

			// Build system report HTML
			$system_report_html = '<table class="wsf-table-system">';

			foreach($system_report as $group_id => $group) {

				$system_report_html .= '<tbody>';

				$system_report_html .= '<tr><th colspan="2"><h2>' . htmlentities($group['label']) . '</h2></th></tr>';

				foreach($group['variables'] as $item_id => $item) {

					// Valid
					// 0 = Ignore, 1 = Yes, 2 = No
					$valid = isset($item['valid']) ? ($item['valid'] ? 1 : 2) : 0;

					$system_report_html .= '<tr';

					switch($valid) {

						case 1 : $system_report_html .= ' class="wsf-system-valid"'; break;
						case 2 : $system_report_html .= ' class="wsf-system-invalid"'; break;
					}

					// Label
					$system_report_html .= '><td><b>' . htmlentities($item['label']);
					if(isset($item['min'])) { $system_report_html .= ' (Min: ' . $item['min'] . ')'; }
					$system_report_html .= '</b></td>';

					// Value
					$system_report_html .= '<td>';

					$value = isset($item['value']) ? $item['value'] : '-';
					$type = isset($item['type']) ? $item['type'] : 'text';

					switch($type) {

						case 'plugins' :

							if(is_array($value)) {

								$plugin_array = array();

								foreach($value as $plugin_path) {

									$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
									$plugin_array[] = sprintf('<a href="%s" target="_blank">%s</a> (%s)', $plugin_data['PluginURI'], $plugin_data['Name'], $plugin_data['Version']);
								}

								$value = implode('<br />', $plugin_array);
							}
							break;

						case 'boolean' :

							$value = $value ? __('Yes', 'ws-form') : __('No', 'ws-form');
							break;

						case 'url' :

							$value = sprintf('<a href="%1$s" target="_blank">%1$s</a>', $value);
							break;

						case 'size' :

							$value = size_format($value);
							break;

						case 'edition' :

							switch($value) {

								case 'basic' : $value = 'Basic'; break;
								case 'pro' : $value = 'PRO'; break;
							}
							break;

						case 'date' :

							$value = ($value != '') ? date(get_option('date_format'), $value) : '-';
							break;
					}

					$system_report_html .= $value;

					// Suffix
					if(isset($item['suffix'])) { $system_report_html .= ' ' . $item['suffix']; }

					// Valid
					switch($valid) {

						case 1 : $system_report_html .= WS_Form_Config::get_icon_16_svg('check'); break;
						case 2 : $system_report_html .= WS_Form_Config::get_icon_16_svg('warning'); break;
					}

					$system_report_html .= '</td></tr>';
				}

				$system_report_html .= '</tbody>';
			}

			$system_report_html .= '</table>';

			return $system_report_html;
		}

		// Get a string formatted for SMTP email addresses
		public static function get_email_address($email, $name = '') {

			// Ensure email is valid
			if(!filter_var($email, FILTER_VALIDATE_EMAIL)) { return false; }

			// Check length
			if(strlen($name) > 255) { $name = substr($name, 0, 255); }

			if(strpos($name, '"') !== false) {

				// Escape double quotes in name
				$name = str_replace('"', '\"', $name);

				// Wrap in double quotes
				$name = '"' . $name . '"';
			}

			// Return full email address
			return (($name != '') ? ($name . ' ') : '') . '<' . $email . '>';
		}

		// Get encryption status HTML
		public static function get_encryption_status_html() {

			$ws_form_encryption = new WS_Form_Encryption();
			if($ws_form_encryption->key_found === false) {			

				$encryption_status_html = '<p>Copy and paste the following code into your <strong>wp-config.php</strong> file after the <strong>Authentication Unique Keys and Salts</strong> section.</p>';

				$encryption_key = htmlentities($ws_form_encryption->create_random_key());
				$encryption_status_html .= "<textarea class=\"wsf-field wsf-encryption-snippet\" rows=\"6\" readonly>/** Encryption Key For WS Form */\ndefine('WS_FORM_ENCRYPTION_KEY', '$encryption_key');</textarea>";

				$encryption_status_html .= '<p><strong style="color: #ff0000;">Caution:</strong> Do not change this key. Changing your encryption key at a later date will make it impossible to decrypt existing submission data. <a href="https://wsform.com/knowledgebase/data-encryption/" target="_blank">Learn More</a></p>';

			} else {

				$encryption_status_html = '<p>Key successfully found in <strong>wp-config.php</strong></p>';

				if($ws_form_encryption->can_encrypt) {

					$encryption_status_html .= '<p><strong>Encrypting submission meta data.</strong></p>';

				} else {

					$encryption_status_html .= '<p><strong>Submission meta data is not being encrypted.</strong></p>';
				}
			}

			return $encryption_status_html;
		}

		// Get preview URL
		public static function get_preview_url($form_id) {

			return get_site_url(null, '/?wsf_preview_form_id=' . $form_id . '&wsf_rand=' . wp_generate_password(12, false, false));
		}

		// Get currency
		public static function get_currency() {

			$return_array = array();

			$price_decimals = intval(self::option_get('price_decimals'));
			$currency_position = self::option_get('currency_position');

			// Get currency symbol
			$currencies = WS_Form_Config::get_currencies();
			$currency = self::option_get('currency', 'USD');
			$currency_found = isset($currencies[$currency]) && isset($currencies[$currency]['symbol']);
			$currency_symbol = $currency_found ? $currencies[$currency]['symbol'] : '$';

			$return_array['prefix'] = $currency_symbol;
			$return_array['suffix'] = '';

			switch($currency_position) {

				case 'right' :

					$return_array['prefix'] = '';
					$return_array['suffix'] = $currency_symbol;
					break;

				case 'left_space' :

					$return_array['prefix'] = $currency_symbol . ' ';
					break;

				case 'right_space' :

					$return_array['prefix'] = '';
					$return_array['suffix'] = ' ' . $currency_symbol;
					break;
			}

			// Price decimals
			$return_array['decimals'] = ($price_decimals < 0) ? 0 : $price_decimals;

			// Separators
			$return_array['decimal_separator'] = self::option_get('price_decimal_separator');
			$return_array['thousand_separator'] = self::option_get('price_thousand_separator');

			return $return_array;
		}

		public static function get_price($price_float, $currency = false, $currency_symbol_render = true) {

			if($currency === false) { $currency = self::get_currency(); }

			if(!is_numeric($price_float)) { $price_float = floatVal($price_float); }

			return ($currency_symbol_render ? $currency['prefix'] : '') . number_format($price_float, intval($currency['decimals']), $currency['decimal_separator'], $currency['thousand_separator']) . ($currency_symbol_render ? $currency['suffix'] : '');
		}

		public static function get_currency_default() {

			return apply_filters('wsf_currency_default', 'USD');
		}

		public static function get_admin_icon($color = '#a0a5aa', $base64 = true) {

			$svg = sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400"><path fill="%s" d="M0 0v400h400V0H0zm336.6 118.9c6.7-.1 13.4 5.4 13.4 13.6-.1 7.4-5.9 13.4-13.4 13.4-8.1 0-13.6-6.7-13.5-13.7 0-7.3 6.1-13.5 13.5-13.3zm-124.4 6.5c-12 48.8-24 97.6-36.1 146.3 0 .2-.2.2-.2.4-.8.1-6 .2-10.4.2h-1.9c-2.1 0-3.7-.1-4.1-.1-.2-.2-.3-.4-.3-.6-.1-.2-.1-.5-.2-.7-1.5-6.6-2.9-13.2-4.4-19.8-2.8-12.2-5.5-24.4-8.2-36.6-2.3-10.2-4.5-20.4-6.8-30.6-.9-4.2-1.9-8.3-2.8-12.5-.6-3-1.1-6.1-1.7-9.1-1-5.5-2-11.2-3-16.7-.1-.4-.2-.8-.3-1.5-.2.6-.3.8-.4 1.1-1.5 9-3.4 17.9-5.2 26.8-.9 4.7-2.1 9.5-3.2 14.2-2.8 12.3-5.7 24.6-8.5 36.9-3.6 15.8-7.3 31.6-11 47.5-.1.5-.3 1-.4 1.5-.2.1-.5.2-.6.2H86.7c-.6-.3-.6-.8-.7-1.2-1.2-4.8-2.3-9.6-3.5-14.5-3.6-15.4-7.4-30.9-11-46.4-3.9-16.4-7.8-32.7-11.7-49.1-2.8-11.5-5.5-22.9-8.1-34.3-.2-.7-.5-1.4-.4-2.1 1.2-.2 11.9-.3 14-.1.1.3.2.7.4 1.1 2.4 10 4.7 19.9 7 29.9 2.5 10.7 5 21.4 7.5 32 1.8 7.8 3.7 15.6 5.5 23.3 1.7 7.4 3.2 14.8 4.7 22.2 1.4 6.8 2.8 13.7 4.3 20.5.1.5.2.9.4 1.3.1.2.3.2.6.3.2-.6.3-1.1.4-1.7 1.8-13.1 4.4-25.9 7.4-38.8 4.3-18.6 8.7-37.2 13.1-55.8l7.8-33.3c.1-.5.2-.9.4-1.4 1.7-.1 3.3 0 5-.1h5c1.7 0 3.3-.1 5 .1.2.6.3 1.2.5 1.7 3.8 16.1 7.6 32.2 11.4 48.2 3 12.7 6 25.3 8.9 38 1.3 5.5 2.3 11.2 3.4 16.8 1.3 6.9 2.5 13.8 3.7 20.6.3 1.7.6 3.2.9 4.9.1.3.2.6.7.6l.3-1.2c.8-5.1 1.8-10 2.9-15 3.9-18.1 8.2-36.1 12.4-54.3 3.6-15.2 7.1-30.4 10.7-45.6 1-4.5 2.1-9 3.2-13.5.1-.3.2-.7.4-1.1 1.2-.2 2.4-.1 3.5-.1h6.8c1.2 0 2.4-.2 3.6.2-.8.5-.8.7-.9 1zm86.3 124.5c-3.6 11.5-11.3 19.1-22.8 22.8-3.2 1-6.6 1.7-10 2-5.2.6-10.4.5-15.7-.1-7-.7-13.8-2.7-20.2-5.9-1.2-.6-2.4-1.2-3.5-2.1.6-1.6 5.4-9.7 6.2-10.7.3.2.6.2.9.5 1.7 1.1 3.5 1.8 5.4 2.5 3.2 1.1 6.3 2.1 9.7 2.8 5.1.9 10.1 1.3 15.3.8 12.9-1.4 19.2-10 21.3-18 1.5-5.6 1.6-11.3.2-16.9-.9-3.9-2.8-7.3-5.4-10.2-1.8-2-3.8-3.8-5.9-5.4-4.2-3.3-8.7-6.2-13.3-8.9-4.7-2.8-9.3-5.5-13.8-8.6-2.9-2.1-5.9-4.1-8.4-6.6-3.7-3.6-6.7-7.7-8.9-12.4-1.8-3.9-2.8-8.1-3.2-12.3-.2-2.5-.2-5.1-.1-7.7.6-8.8 4.2-16.2 10.6-22.3 5.9-5.5 12.8-8.9 20.6-10.4 5.3-1 10.7-1.2 16.1-.7 7.4.6 14.5 2.5 21.1 5.9 1.3.7 2.6 1.4 3.9 2.2.3.2.6.5.9.6-.5 1.3-3.8 7.1-5.7 10.1-.2.2-.3.4-.6.6l-1.2-.6c-5.4-3.1-11.1-5.3-17.2-6.2-5.6-.9-11.2-.9-16.7.5-2.8.7-5.4 1.7-7.8 3.3-5.9 3.9-9.3 9.3-10.2 16.2-.6 4.5-.2 8.9 1.2 13.3 1.1 3.5 3 6.5 5.5 9 2.6 2.6 5.5 4.8 8.5 7 3.3 2.3 6.8 4.4 10.3 6.5 5.8 3.4 11.5 7 16.8 11 2.3 1.7 4.6 3.6 6.6 5.6 5.9 5.9 9.5 13 10.6 21.2 1.4 7.2 1.1 14.5-1.1 21.6zm38 26.4c-7.5.1-13.5-6.2-13.5-13.5 0-6.7 5.4-13.5 13.5-13.4 7.4 0 13.4 5.9 13.4 13.4.1 8-6.5 13.6-13.4 13.5zm-.1-64.8c-7.9-.1-13.4-6.6-13.4-13.4 0-7.7 6.4-13.7 13.5-13.5 6.4-.2 13.4 5.1 13.5 13.5 0 7.4-6.1 13.5-13.6 13.4z"/></svg>', $color);

			return $base64 ? 'data:image/svg+xml;base64,' . base64_encode($svg) : $svg;
		}

		// Load content via AJAX
		public static function ajax_load($url, $id = 'wsf-settings-content') {

			// Build action product ID's
			$action_license_item_id_array = array();

			foreach(get_declared_classes() as $class){

				if(strpos($class, 'WS_Form_Action_') === false) { continue; }
				if(!is_subclass_of($class, 'WS_Form_Action')) { continue; }

				$action = New $class();

				if(method_exists($action, 'get_license_item_id')) {

					$action_license_item_id_array[] = $action->get_license_item_id();
				}
			}

			$action_license_item_ids = implode(',', $action_license_item_id_array);

			$url_variables = array(

				'locale' 					=> rawurlencode(get_locale()),
				'version'					=> rawurlencode(WS_FORM_VERSION),
				'action_license_item_ids'	=> rawurlencode($action_license_item_ids)
			);

			$url = self::mask_parse($url, $url_variables);

			echo '<div id="wsf-settings-content"><script>(function($) {\'use strict\';$(\'#' . esc_html($id) . '\').load(\'' .  esc_html($url) . '\', function(response, status, xhr) { if(status == \'error\') { $(\'#' . esc_html($id) . '\').html(\'' . sprintf(__('<a href="%s" target="_blank">Click here</a> to learn more about WS Form PRO.', 'ws-form'), esc_html(self::get_plugin_website_url('', 'settings'))) . '\'); }});})(jQuery);</script></div>';
		}

		// Get root post
		public static function get_post_root() {

			// Load post (This uses the post ID set before any of the page renders)
			$post = (isset($GLOBALS) && isset($GLOBALS['ws_form_post_root'])) ? $GLOBALS['ws_form_post_root'] : null;

			// Load post by query string (Used by actions when a form is submitted)
			if(is_null($post)) {

				$post_id = intval(self::get_query_var('wsf_post_id', 0));
				if($post_id == 0) { $post_id = intval(self::get_query_var('post_id', 0)); }
				$post = ($post_id > 0) ? get_post($post_id) : null;
				$GLOBALS['ws_form_post_root'] = $post;
			}

			return $post;
		}

		// Get user
		public static function get_user() {

			// Load user
			$user = (isset($GLOBALS) && isset($GLOBALS['ws_form_user'])) ? $GLOBALS['ws_form_user'] : false;

			// Load user by current_user
			if(
				($user === false) &&
				function_exists('wp_get_current_user')
			) {

				$user = wp_get_current_user();
			}

			return $user;
		}

		// Is block editor on page?
		public static function is_block_editor() {

			if(!function_exists('get_current_screen')) { return false; }
			if(!is_object(get_current_screen())) { return false; }
			if(!method_exists(get_current_screen(), 'is_block_editor')) { return false; }

			return get_current_screen()->is_block_editor();
		}

		// Is this a REST request
		public static function is_rest_request() {

			return (defined('REST_REQUEST') && REST_REQUEST);
		}

		// Is this customize preview?
		public static function is_customize_preview() {

			return (self::get_query_var('customize_theme') != '');
		}

		// do_shortcode
		public static function do_shortcode($input) {

			// Get shortcode regex
			$shortcode_regex = get_shortcode_regex();

			// If there are shortcodes in the input
			if(
				preg_match_all('/'. $shortcode_regex .'/s', $input, $matches) &&
				array_key_exists(2, $matches) &&
				!in_array(WS_FORM_SHORTCODE, $matches[2])
			) {

				// Run do_shortcode
				$input = do_shortcode($input);
			}

			return $input;
		}
	}
