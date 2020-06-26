<?php

	class WS_Form_API_Field extends WS_Form_API {

		public function __construct() {

			// Call parent on WS_Form_API
			parent::__construct();
		}

		// API - GET
		public function api_get($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { parent::api_access_denied(); }

			$ws_form_field = new WS_Form_Field();
			$ws_form_field->id = self::api_get_id($parameters);

			// Send JSON response
			parent::api_json_response($ws_form_field->db_read());
		}

		// API - POST
		public function api_post($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_field = new WS_Form_Field();
			$ws_form_field->id = self::api_get_id($parameters);
			$ws_form_field->form_id = self::api_get_form_id($parameters);
			$ws_form_field->section_id = self::api_get_section_id($parameters);

			// Get field type ID
			$ws_form_field->type = WS_Form_Common::get_query_var_nonce('type', '', $parameters);

			// Get next sibling ID
			$next_sibling_id = intval(WS_Form_Common::get_query_var_nonce('next_sibling_id', 0, $parameters));

			// Create field
			$ws_form_field->db_create($next_sibling_id);

			// Width factor
			$width_factor = WS_Form_Common::get_query_var_nonce('width_factor', false, $parameters);
			if($width_factor !== false) {

				// Get framework info and calculate breakpoint meta key and value for 50% width
				$framework_id = WS_Form_Common::option_get('framework');
				$framework_column_count = WS_Form_Common::option_get('framework_column_count');
				$frameworks = WS_Form_Config::get_frameworks();
				$framework_breakpoints = $frameworks['types'][$framework_id]['breakpoints'];
				reset($framework_breakpoints);
				$breakpoint_first = key($framework_breakpoints);
				$breakpoint_meta_key = 'breakpoint_size_' . $breakpoint_first;
				$breakpoint_meta_value = round($framework_column_count * $width_factor);

				// Build meta data
				$field_meta = New WS_Form_Meta();
				$field_meta->object = 'field';
				$field_meta->parent_id = $ws_form_field->id;
				$field_meta->db_update_from_array(array($breakpoint_meta_key => $breakpoint_meta_value));
			}

			// Build api_json_response
			$api_json_response = $ws_form_field->db_read();

			// Describe transaction for history
			$history = array(

				'object'		=>	'field',
				'method'		=>	'post',
				'label'			=>	$ws_form_field->label,
				'section_id'	=>	$ws_form_field->section_id,
				'id'			=>	$ws_form_field->id
			);

			// Update checksum
			$ws_form_field->db_checksum();

			// Send JSON response
			parent::api_json_response($api_json_response, $ws_form_field->form_id, $history);
		}

		// API - POST - Download - CSV
		public function api_post_download_csv($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('export_form')) { parent::api_access_denied(); }

			$ws_form_field = new WS_Form_Field();
			$ws_form_field->id = self::api_get_id($parameters);
			$ws_form_field->form_id = self::api_get_form_id($parameters);

			// Get meta key
			$meta_key = WS_Form_Common::get_query_var_nonce('meta_key', false, $parameters);
			if($meta_key === false) { parent::api_throw_error(__('Meta key not specified', 'ws-form')); }

			$meta_value_url = WS_Form_Common::get_query_var_nonce('meta_value', false, $parameters);
			if($meta_value_url !== false) {

				// Get meta value (Scratch)
				$meta_value_json = urldecode($meta_value_url);
				$meta_value = json_decode($meta_value_json);

			} else {

				// Get meta value (Database)
				$ws_form_meta = New WS_Form_Meta();
				$ws_form_meta->object = 'field';
				$ws_form_meta->parent_id = $ws_form_field->id;
				$meta_value = $ws_form_meta->db_get_object_meta($meta_key);
				if($meta_value == '') { parent::api_throw_error(__('Meta value empty', 'ws-form')); }
			}

			// Get file index
			$group_index = WS_Form_Common::get_query_var_nonce('group_index', false, $parameters);
			if($group_index === false) { parent::api_throw_error(__('Group index not specified', 'ws-form')); }
			$group_index = intval($group_index);
			if($group_index < 0) { parent::api_throw_error(__('Group index invalid', 'ws-form')); }

			// Get columns
			if(!isset($meta_value->columns)) { parent::api_throw_error(__('Columns not found', 'ws-form')); }
			$columns = $meta_value->columns;

			// Get group
			if(!isset($meta_value->groups[$group_index])) { parent::api_throw_error(__('Group index invalid', 'ws-form')); }
			$group = $meta_value->groups[$group_index];

			// Get group label
			if(!isset($group->label)) { parent::api_throw_error(__('Group label not found', 'ws-form')); }
			$group_label = $group->label;

			// Get group rows
			if(!isset($group->rows)) { parent::api_throw_error(__('Group rows not found', 'ws-form')); }
			$rows = $group->rows;

			// Build filename
			$filename = strtolower($group_label) . '.csv';

			// HTTP headers
			WS_Form_Common::file_download_headers($filename, 'application/octet-stream');

			// Open stream
			$out = fopen('php://output', 'w');

			// Build header
			$row_array = array('wsf_id', 'wsf_default', 'wsf_required', 'wsf_disabled', 'wsf_hidden');
			foreach($columns as $column) {

				if(!isset($column->label)) { parent::api_throw_error(__('Column label not found', 'ws-form')); }
				$row_array[] = $column->label;
			}
			fputcsv($out, $row_array);

			// Build rows
			foreach($rows as $row) {

				if(!isset($row->data)) { parent::api_throw_error(__('Row data not found', 'ws-form')); }

				$default = isset($row->default) ? $row->default : '';
				$required = isset($row->required) ? $row->required : '';
				$disabled = isset($row->disabled) ? $row->disabled : '';
				$hidden = isset($row->hidden) ? $row->hidden : '';

				$data = array($row->id, $default, $required, $disabled, $hidden);
				$data = array_merge($data, (array)$row->data);

				fputcsv($out, $data);
			}

			// Close stream
			fclose($out);

			// Exit (Ensures WordPress intentional 'null' is not sent)
			exit;
		}

		// API - POST - Upload - CSV
		public function api_post_upload_csv($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_field = new WS_Form_Field();
			$ws_form_field->id = self::api_get_id($parameters);
			$ws_form_field->form_id = self::api_get_form_id($parameters);

			define('UTF32_BIG_ENDIAN_BOM'   , chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
			define('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
			define('UTF16_BIG_ENDIAN_BOM'   , chr(0xFE) . chr(0xFF));
			define('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
			define('UTF8_BOM'               , chr(0xEF) . chr(0xBB) . chr(0xBF));

			// Auto detect line endings
			ini_set("auto_detect_line_endings", true);

			// Get meta key
			$meta_key = WS_Form_Common::get_query_var_nonce('meta_key', false, $parameters);
			if($meta_key === false) { parent::api_throw_error(__('Meta key not specified', 'ws-form')); }

			// Get files
			if(!isset($_FILES)) { parent::api_throw_error(__('No files found', 'ws-form')); }
			if(!isset($_FILES['file'])) { parent::api_throw_error(__('No files found', 'ws-form')); }

			// Run through files
			$file = $_FILES['file'];

			// Read file data
			$file_name = $file['name'];
			$file_type = $file['type'];
			$file_tmp_name = $file['tmp_name'];
			$file_error = $file['error'];
			$file_size = $file['size'];

			// Error
			if($file_error != 0) { parent::api_throw_error(__('File upload error', 'ws-form') . ': ' . $file_error); }

			// Check file extension
			$ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
			if($ext !== 'csv') { parent::api_throw_error(__('Unsupported file extension', 'ws-form') . ': ' . $ext); }

			// Check file format
			$file_string = file_get_contents($file_tmp_name);

			// Determine character encoding
			$first2 = substr($file_string, 0, 2);
			$first3 = substr($file_string, 0, 3);
			$first4 = substr($file_string, 0, 3);

			$char_encoding = false;
			if ($first3 == UTF8_BOM) $char_encoding =  'UTF-8';
			elseif ($first4 == UTF32_BIG_ENDIAN_BOM) $char_encoding =  'UTF-32BE';
			elseif ($first4 == UTF32_LITTLE_ENDIAN_BOM) $char_encoding =  'UTF-32LE';
			elseif ($first2 == UTF16_BIG_ENDIAN_BOM) $char_encoding =  'UTF-16BE';
			elseif ($first2 == UTF16_LITTLE_ENDIAN_BOM) $char_encoding =  'UTF-16LE';

			// Convert string
			if($char_encoding) {

				$file_string = mb_convert_encoding($file_string, 'UTF-8', $char_encoding);
			}

			// Save file
			file_put_contents($file_tmp_name, $file_string);

			// Read file
			$file_array = file($file_tmp_name);
			if($file_array === false) { parent::api_throw_error(__('Error reading file', 'ws-form')); }

			// Map file to array
			$array = array_map('str_getcsv', $file_array);

			// Read header
			$columns = array_shift($array);
			if(is_null($columns)) { parent::api_throw_error(__('Unable to read header row of file', 'ws-form')); }
			if(count($columns) == 0) { parent::api_throw_error(__('No columns to process', 'ws-form')); }

			// Read current meta value
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'field';
			$ws_form_meta->parent_id = $ws_form_field->id;
			$meta_value = $ws_form_meta->db_get_object_meta($meta_key, false, false);
			if(!$meta_value) { parent::api_throw_error(__('Unable to read meta data', 'ws-form') + ': ' + $meta_key); }

			// Set group_index to 0 (Select first tab)
			$meta_value->group_index = 0;

			// Build columns
			$columns_new = array();
			$column_key_id = -1;
			$column_key_wsf_id = -1;
			$column_key_wsf_default = -1;
			$column_key_wsf_required = -1;
			$column_key_wsf_disabled = -1;
			$column_key_wsf_hidden = -1;

			$column_index = 0;
			foreach($columns as $key => $column) {

				switch(strtolower($column)) {

					case 'wsf_id' :

						$column_key_wsf_id = $key;
						break;

					case 'wsf_default' :

						$column_key_wsf_default = $key;
						break;

					case 'wsf_required' :

						$column_key_wsf_required = $key;
						break;

					case 'wsf_disabled' :

						$column_key_wsf_disabled = $key;
						break;

					case 'wsf_hidden' :

						$column_key_wsf_hidden = $key;
						break;

					case 'id' :
						$column_key_id = $key;

					default:

						$columns_new[] = array(

							'id' => $column_index,
							'label' => $column
						);
						$column_index++;
				}
			}
			$meta_value->columns = $columns_new;

			// Get default group configuration
			$meta_keys = WS_Form_Config::get_meta_keys(0, false);
			if(!isset($meta_keys[$meta_key])) { parent::api_throw_error(__('Unknown meta key', 'ws-form') + ': ' + $meta_key); }
			if(!isset($meta_keys[$meta_key]['default'])) { parent::api_throw_error(__('Default not found', 'ws-form') + ': ' + $meta_key); }
			if(!isset($meta_keys[$meta_key]['default']['groups'])) { parent::api_throw_error(__('Groups not found', 'ws-form') + ': ' + $meta_key); }
			if(!isset($meta_keys[$meta_key]['default']['groups'][0])) { parent::api_throw_error(__('Group[0] not found', 'ws-form') + ': ' + $meta_key); }

			$group = $meta_keys[$meta_key]['default']['groups'][0];

			// Re-process array to match required format for data grid
			$id_array = [];
			foreach($array as $key => $row) {

				// UTF-8 encode the row
				$row_id = -1;
				$column_index = 0;
				$data = [];
				$default = '';
				$required = '';
				$disabled = '';
				$hidden = '';

				foreach($row as $column_key => $field) {

					$field_lower = strtolower($field);

					switch($column_key) {

						case $column_key_wsf_id:

							$row_id = intval($field_lower);
							if($row_id > 0) { $id = $row_id; }
							break;

						case $column_key_wsf_default:

							$default = ($field_lower != '') ? 'on' : '';
							break;

						case $column_key_wsf_required :

							$required = ($field_lower != '') ? 'on' : '';
							break;

						case $column_key_wsf_disabled :

							$disabled = ($field_lower != '') ? 'on' : '';
							break;

						case $column_key_wsf_hidden :

							$hidden = ($field_lower != '') ? 'on' : '';
							break;

						case $column_key_id :

							$row_id = intval($field_lower);
							if($row_id > 0) { $id = $row_id; }

						default :

							$data[$column_index] = $field;
							$column_index++;
					}
				}

				// ID row not found
				if($row_id == -1) {

					$max_id = 0;
					foreach($id_array as $id) {

						if($id > $max_id) { $max_id = $id; }
					}
					$id = $max_id + 1;
				}

				// Add ID to ID array
				$id_array[] = $id;

				// Build row
				$array[$key] = array(

					'id'		=> $id,
					'default'	=> $default,
					'disabled'	=> $disabled,
					'required'	=> $required,
					'hidden'	=> $hidden,
					'data'		=> $data
				);
			}

			// Build label
			$label = strtolower($file_name);
			$label = str_replace('_', ' ', $label);
			$label = str_replace('-', ' ', $label);
			$label = str_replace('.csv', '', $label);
			$label = ucwords($label);

			// Build group
			$group['label'] = $label;
			$group['page'] = 0;
			$group['rows'] = $array;

			// Add to meta value
			$meta_value->groups = array($group);
			$meta_value->group_index = 0;

			// Get section ID
			$ws_form_field->section_id = $ws_form_field->db_get_section_id();

			// Describe transaction for history
			$history = array(

				'object'		=>	'field',
				'method'		=>	WS_Form_Common::get_query_var_nonce('history_method', 'post'),
				'label'			=>	$ws_form_field->db_get_label($ws_form_field->table_name, $ws_form_field->id),
				'section_id'	=>	$ws_form_field->section_id,
				'id'			=>	$ws_form_field->id
			);

			// Update checksum
			$ws_form_field->db_checksum();

			// Send JSON response
			parent::api_json_response($meta_value, $ws_form_field->form_id, $history);
		}

		// API - PUT
		public function api_put($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_field = new WS_Form_Field();
			$ws_form_field->id = self::api_get_id($parameters);
			$ws_form_field->form_id = self::api_get_form_id($parameters);

			// Get field data
			$field_object = WS_Form_Common::get_query_var_nonce('field', false, $parameters);
			if(!$field_object) { return false; }

			// Put field
			$ws_form_field->db_update_from_object($field_object);

			// Get section ID
			$ws_form_field->section_id = $ws_form_field->db_get_section_id();

			// Describe transaction for history
			$history = array(

				'object'		=>	'field',
				'method'		=>	WS_Form_Common::get_query_var_nonce('history_method', 'put'),
				'label'			=>	$ws_form_field->db_get_label($ws_form_field->table_name, $ws_form_field->id),
				'section_id'	=>	$ws_form_field->section_id,
				'id'			=>	$ws_form_field->id
			);

			// Update checksum
			$ws_form_field->db_checksum();

			// Send JSON response
			parent::api_json_response([], $ws_form_field->form_id, isset($field_object->history_suppress) ? false : $history);
		}

		// API - PUT - SORT INDEX
		public function api_put_sort_index($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$api_json_response = [];

			$ws_form_field = new WS_Form_Field();
			$ws_form_field->id = self::api_get_id($parameters);
			$ws_form_field->form_id = self::api_get_form_id($parameters);
			$ws_form_field->section_id = self::api_get_section_id($parameters);

			// Get next sibling ID
			$next_sibling_id = intval(WS_Form_Common::get_query_var_nonce('next_sibling_id', 0, $parameters));

			// Process sort index
			$ws_form_field->db_object_sort_index($ws_form_field->table_name, 'section_id', $ws_form_field->section_id, $next_sibling_id);

			// Describe transaction for history
			$history = array(

				'object'		=>	'field',
				'method'		=>	'put_sort_index',
				'label'			=>	$ws_form_field->db_get_label($ws_form_field->table_name, $ws_form_field->id),
				'section_id'	=>	$ws_form_field->section_id,
				'id'			=>	$ws_form_field->id
			);

			// Update checksum
			$ws_form_field->db_checksum();

			// Send JSON response
			parent::api_json_response($api_json_response, $ws_form_field->form_id, $history);
		}

		// API - PUT - CLONE
		public function api_put_clone($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$api_json_response = [];

			$ws_form_field = new WS_Form_Field();
			$ws_form_field->id = self::api_get_id($parameters);
			$ws_form_field->form_id = self::api_get_form_id($parameters);

			// Read
			$ws_form_field->db_read();

			// Get section ID
			$ws_form_field->section_id = $ws_form_field->db_get_section_id();

			// Get next sibling ID
			$next_sibling_id = intval(WS_Form_Common::get_query_var_nonce('next_sibling_id', 0, $parameters));

			// Get sort_index
			$ws_form_field->sort_index = $ws_form_field->db_object_sort_index_get($ws_form_field->table_name, 'section_id', $ws_form_field->section_id, $next_sibling_id);

			// Rename
			$ws_form_field->label = sprintf(__('%s (Copy)', 'ws-form'), $ws_form_field->label);

			// Clone
			$ws_form_field->id = $ws_form_field->db_clone();

			// Remember label before change
			$label = $ws_form_field->label;

			// Build api_json_response
			$api_json_response = $ws_form_field->db_read();

			// Describe transaction for history
			$history = array(

				'object'		=>	'field',
				'method'		=>	'put_clone',
				'label'			=>	$label,
				'section_id'	=>	$ws_form_field->section_id,
				'id'			=>	$ws_form_field->id
			);

			// Update checksum
			$ws_form_field->db_checksum();

			// Send JSON response
			parent::api_json_response($api_json_response, $ws_form_field->form_id, $history);
		}

		// API - DELETE
		public function api_delete($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { parent::api_access_denied(); }

			$ws_form_field = new WS_Form_Field();
			$ws_form_field->id = self::api_get_id($parameters);
			$ws_form_field->form_id = self::api_get_form_id($parameters);

			// Get section ID
			$ws_form_field->section_id = $ws_form_field->db_get_section_id();

			// Get label (We do this because once its deleted, we can't reference it)
			$label = $ws_form_field->db_get_label($ws_form_field->table_name, $ws_form_field->id);

			// Delete field
			$ws_form_field->db_delete();

			// Clean up sort index for section
			$ws_form_field->db_object_sort_index_clean($ws_form_field->table_name, 'section_id', $ws_form_field->section_id);

			// Describe transaction for history
			$history = array(

				'object'		=>	'field',
				'method'		=>	'delete',
				'label'			=>	$label,
				'section_id'	=>	$ws_form_field->section_id,
				'id'			=>	$ws_form_field->id
			);

			// Update checksum
			$ws_form_field->db_checksum();

			// Send JSON response
			parent::api_json_response([], $ws_form_field->form_id, $history);
		}

		// Get form ID
		public function api_get_form_id($parameters) {

			return intval(WS_Form_Common::get_query_var_nonce('id', 0, $parameters));
		}

		// Get section ID
		public function api_get_section_id($parameters) {

			return intval(WS_Form_Common::get_query_var_nonce('section_id', 0, $parameters));
		}

		// Get section ID from (used to determine where a field was dragged from)
		public function api_get_section_id_from($parameters) {

			return intval(WS_Form_Common::get_query_var_nonce('section_id_from', 0, $parameters));
		}

		// Get field ID
		public function api_get_id($parameters) {

			return intval(WS_Form_Common::get_query_var_nonce('field_id', 0, $parameters));
		}
	}
