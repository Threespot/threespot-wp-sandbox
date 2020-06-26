<?php

	class WS_Form_Submit extends WS_Form_Core {

		public $id;
		public $form_id;
		public $date_added;
		public $date_updated;
		public $date_expire;
		public $user_id;
		public $hash;
		public $duration;
		public $count_submit;
		public $status;
		public $actions;
		public $section_repeatable;
		public $preview;
		public $spam_level;
		public $starred;
		public $viewed;

		public $meta;
		public $meta_protected;

		public $post_mode;

		public $form_object;

		public $error;
		public $error_message;
		public $error_code;

		public $error_validation;
		public $error_validation_messages;
		public $error_validation_fields;

		public $table_name;
		public $table_name_meta;

		public $bypass_array;

		public $field_types;

		const DB_INSERT = 'form_id,date_added,date_updated,date_expire,user_id,hash,duration,count_submit,status,actions,section_repeatable,preview,spam_level,starred,viewed,encrypted';
		const DB_UPDATE = 'form_id,date_added,date_updated,date_expire,user_id,hash,duration,count_submit,status,actions,section_repeatable,preview,spam_level,starred,viewed,encrypted';
		const DB_SELECT = 'form_id,date_added,date_updated,date_expire,user_id,hash,duration,count_submit,status,actions,section_repeatable,preview,spam_level,starred,viewed,encrypted,id';

		public function __construct() {

			global $wpdb;

			$this->id = 0;
			$this->form_id = 0;
			$this->user_id = WS_Form_Common::get_user_id(false);
			$this->table_name = $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . 'submit';
			$this->table_name_meta = $this->table_name . '_meta';
			$this->hash = '';
			$this->status = 'draft';
			$this->duration = 0;
			$this->count_submit = 0;
			$this->meta = array();
			$this->meta_protected = array();
			$this->actions = '';
			$this->section_repeatable = '';
			$this->preview = false;
			$this->date_added = WS_Form_Common::get_mysql_date();
			$this->date_updated = WS_Form_Common::get_mysql_date();
			$this->date_expire = null;
			$this->spam_level = null;
			$this->starred = false;
			$this->viewed = false;

			$this->post_mode = false;

			$this->error = false;
			$this->error_message = '';
			$this->error_code = 200;

			$this->error_validation = false;
			$this->error_validation_messages = array();
			$this->error_validation_fields = array();

			// Encryption
			$ws_form_encryption = new WS_Form_Encryption();
			$this->encrypted = $ws_form_encryption->can_encrypt;
			// Get field types in single dimension array
			$this->field_types = WS_Form_Config::get_field_types_flat();
		}

		// Create
		public function db_create($update_count_submit_unread = true) {

			// No capabilities required, this is a public method

			// Check form ID
			self::db_check_form_id();

			global $wpdb;

			// get_user_id(false) = Does not exit on zero
			$sql = sprintf("INSERT INTO %s (%s) VALUES (%u, '%s', '%s', %s, %u, '%s', %u, %u, '%s', '%s', '%s', %u, %s, %u, %u, %u);", $this->table_name, self::DB_INSERT, $this->form_id, $this->date_added, $this->date_updated, (is_null($this->date_expire) ? 'NULL' : "'" . $this->date_expire . "'"), $this->user_id, esc_sql($this->hash), $this->duration, $this->count_submit, esc_sql($this->status), esc_sql($this->actions), esc_sql($this->section_repeatable), ($this->preview ? 1 : 0), (is_null($this->spam_level) ? 'NULL' : $this->spam_level), ($this->starred ? 1 : 0), ($this->viewed ? 1 : 0), ($this->encrypted ? 1 : 0));
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error adding submit', 'ws-form')); }

			// Get inserted ID
			$this->id = $wpdb->insert_id;

			// Create hash
			self::db_create_hash();

			// Update hash
			$sql = sprintf("UPDATE %s SET hash = '%s' WHERE id=%u LIMIT 1", $this->table_name, esc_sql($this->hash), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error updating submit', 'ws-form')); }

			// Update form submit unread count statistic
			if($update_count_submit_unread) {

				$ws_form_form = new WS_Form_Form();
				$ws_form_form->id = $this->form_id;
				$ws_form_form->db_update_count_submit_unread();
			}
		}

		// Read record to array
		public function db_read($get_meta = true, $get_expanded = true) {

			// User capability check
			if(!WS_Form_Common::can_user('read_submission')) { return false; }

			self::db_check_id();

			global $wpdb;

			// Add fields
			$sql = sprintf("SELECT %s FROM %s WHERE id = %u LIMIT 1;", self::DB_SELECT, $this->table_name, $this->id);
			$return_array = $wpdb->get_row($sql, 'ARRAY_A');

			if($return_array === null) { parent::db_throw_error(__('Unable to read submission', 'ws-form')); }

			// Set class variables
			foreach($return_array as $key => $value) {

				$this->{$key} = $value;
			}

			// Get user data
			if($get_expanded) {

				self::db_read_expanded($return_array);
			}

			// Process meta data
			if($get_meta) {

				$this->meta = self::db_get_submit_meta($return_array, false);

				$return_array['meta'] = $this->meta;
			}

			// Preview to boolean
			if(isset($this->preview)) { $this->preview = $return_array['preview'] = (bool) $this->preview; }

			// Encrypted to boolean
			if(isset($this->encrypted)) { $this->encrypted = $return_array['encrypted'] = (bool) $this->encrypted; }

			// Return array
			return $return_array;
		}

		// Read expanded data for a record
		public function db_read_expanded(&$return_array, $expand_user = true, $expand_date_added = true, $expand_date_updated = true, $expand_status = true, $expand_actions = true, $expand_section_repeatable = true, $bypass_user_capability_check = false) {

			// User capability check
			if(!$bypass_user_capability_check && !WS_Form_Common::can_user('read_submission')) { return false; }

			if($expand_user && isset($return_array['user_id']) && ($return_array['user_id'] > 0)) {

				$user = get_user_by('ID', $return_array['user_id']);
				if($user !== false) {

					$return_array['user'] = array(

						'first_name' 	=>	$user->first_name,
						'last_name' 	=>	$user->last_name,
						'display_name'	=> $user->display_name
					);
				}
			}

			// Date added
			if($expand_date_added && isset($return_array['date_added'])) {

				$this->date_added_wp = $return_array['date_added_wp'] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime(get_date_from_gmt($return_array['date_added'])));
			}

			// Date updated
			if($expand_date_updated && isset($return_array['date_updated'])) {

				$this->date_updated_wp = $return_array['date_updated_wp'] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime(get_date_from_gmt($return_array['date_updated'])));
			}

			// Status
			if($expand_status && isset($return_array['status'])) {

				$this->status_full = $return_array['status_full'] = self::db_get_status_name($return_array['status']);
			}

			// Unserialize actions
			if($expand_actions && isset($return_array['actions'])) {

				$this->actions = $return_array['actions'] = (@unserialize($return_array['actions']) !== false) ? unserialize($return_array['actions']) : false;
			}

			// Unserialize section_repeatable
			if($expand_section_repeatable && isset($return_array['section_repeatable'])) {

				$this->section_repeatable = $return_array['section_repeatable'] = (@unserialize($return_array['section_repeatable']) !== false) ? unserialize($return_array['section_repeatable']) : false;
			}
		}

		// Read - All
		public function db_read_all($join = '', $where = '', $order_by = '', $limit = '', $offset = '', $get_meta = true, $get_expanded = true, $bypass_user_capability_check = false) {

			// User capability check
			if(!$bypass_user_capability_check && !WS_Form_Common::can_user('read_submission')) { return false; }

			global $wpdb;

			// Get form data
			$select = self::DB_SELECT;
			if($join != '') {

				$select_array = explode(',', $select);
				foreach($select_array as $key => $select) {

					$select_array[$key] = $this->table_name . '.' . $select;
				}
				$select = implode(',', $select_array);
			}

			$sql = sprintf("SELECT %s FROM %s", $select, $this->table_name);

			if($join != '') { $sql .= sprintf(" %s", $join); }
			if($where != '') { $sql .= sprintf(" WHERE %s", $where); }
			if($order_by != '') { $sql .= sprintf(" ORDER BY %s", $order_by); }
			if($limit != '') { $sql .= sprintf(" LIMIT %s", $limit); }
			if($offset != '') { $sql .= sprintf(" OFFSET %s", $offset); }

			$return_array = $wpdb->get_results($sql, 'ARRAY_A');
			if(!$return_array) { return null; }

			foreach($return_array as $key => $submit) {

				// Process expanded data
				if($get_expanded) {

					self::db_read_expanded($submit);
				}				

				// Process meta data
				if($get_meta) {

					// Get meta data
					$submit_meta = self::db_get_submit_meta($submit, $bypass_user_capability_check);

					// Add meta to return array
					$submit = $submit + $submit_meta;
				}
	
				$return_array[$key] = $submit;
			}

			return $return_array;
		}

		// Read - Count
		public function db_read_count($join = '', $where = '') {

			// User capability check
			if(!WS_Form_Common::can_user('read_submission')) { return false; }

			global $wpdb;

			// Get form data
			$select = self::DB_SELECT;
			if($join != '') {

				$select_array = explode(',', $select);
				foreach($select_array as $key => $select) {

					$select_array[$key] = $this->table_name . '.' . $select;
				}
				$select = implode(',', $select_array);
			}

			$sql = sprintf("SELECT COUNT(id) FROM %s", $this->table_name);

			if($join != '') { $sql .= sprintf(" %s", $join); }
			if($where != '') { $sql .= sprintf(" WHERE %s", $where); }

			return $wpdb->get_var($sql);
		}

		// Read by hash
		public function db_read_by_hash($get_meta = true, $get_expanded = true, $form_id_check = true, $bypass_user_capability_check = false) {

			// User capability check
			if(!$bypass_user_capability_check && !WS_Form_Common::can_user('read_submission')) { return false; }

			// Check form ID
			if($form_id_check) { self::db_check_form_id(); }

			// Check hash
			self::db_check_hash();

			global $wpdb;

			// Get form submission
			if($form_id_check) {

				$sql = sprintf("SELECT %s FROM %s WHERE form_id = %u AND hash = '%s' LIMIT 1;", self::DB_SELECT, $this->table_name, $this->form_id, $this->hash);
			} else {

				$sql = sprintf("SELECT %s FROM %s WHERE hash = '%s' LIMIT 1;", self::DB_SELECT, $this->table_name, $this->hash);				
			}
			$return_array = $wpdb->get_row($sql, 'ARRAY_A');
			if($return_array === null) { $this->hash = ''; return false; }

			// Get user data
			if($get_expanded) {

				self::db_read_expanded($return_array, true, true, true, true, true, true, $bypass_user_capability_check);
			}

			// Process meta data
			if($get_meta) {

				$return_array['meta'] = self::db_get_submit_meta($return_array, $bypass_user_capability_check);
			}

			// Set class variables
			foreach($return_array as $key => $value) {

				$this->{$key} = $value;
			}

			// Return array
			return $return_array;
		}

		// Update current submit
		public function db_update() {

			// No capabilities required, this is a public method

			// Check ID
			self::db_check_id();

			// Update / Insert
			$this->id = parent::db_update_insert($this->table_name, self::DB_UPDATE, self::DB_INSERT, $this, 'submit', $this->id);

			// Update meta
			if(isset($this->meta)) {

				$ws_form_submit_meta = New WS_Form_Submit_Meta();
				$ws_form_submit_meta->parent_id = $this->id;
				$ws_form_submit_meta->db_update_from_object($this->meta, $this->encrypted);
			}
		}

		// Push submit from array
		public function db_update_from_object($submit_object) {

			// No capabilities required, this is a public method

			// Check for submit ID in $submit
			if(isset($submit_object->id)) { $this->id = intval($submit_object->id); } else { return false; }

			// Encryption
			$submit_encrypted = isset($submit_object->encrypted) ? $submit_object->encrypted : false;

			// Update / Insert
			$this->id = parent::db_update_insert($this->table_name, self::DB_UPDATE, self::DB_INSERT, $submit_object, 'submit', $this->id);

			// Update meta
			if(isset($submit_object->meta)) {

				$ws_form_submit_meta = New WS_Form_Submit_Meta();
				$ws_form_submit_meta->parent_id = $this->id;
				$ws_form_submit_meta->db_update_from_object($submit_object->meta, $submit_encrypted);
			}
		}

		// Stamp submit with date updated, increase submit count and add duration (if available)
		public function db_stamp() {

			// No capabilities required, this is a public method

			// Check ID
			self::db_check_id();

			// Get duration
			$this->duration = intval(WS_Form_Common::get_query_var_nonce('wsf_duration', 0));

			global $wpdb;

			// Date updated, count submit + 1
			$sql = sprintf("UPDATE %s SET date_updated = '%s', count_submit = count_submit + 1, duration = %u WHERE id=%u LIMIT 1", $this->table_name, WS_Form_Common::get_mysql_date(), $this->duration, $this->id);
			if($wpdb->query($sql) === false) { parent::error(__('Error updating submit date updated', 'ws-form')); }
			$this->count_submit++;

			// User ID
			$sql = sprintf("UPDATE %s SET user_id = %u WHERE id=%u AND (user_id = 0 OR user_id IS NULL) LIMIT 1", $this->table_name, WS_Form_Common::get_user_id(false), $this->id);
			if($wpdb->query($sql) === false) { parent::error(__('Error updating submit user ID', 'ws-form')); }
		}

		// Delete
		public function db_delete($permanent_delete = false, $update_count_submit_unread = true) {

			// User capability check
			if(!WS_Form_Common::can_user('delete_submission')) { return false; }

			self::db_check_id();

			// Read the submit status
			self::db_read(false, false);

			if(in_array($this->status, array('spam', 'trash'))) { $permanent_delete = true; }

			// If status is trashed, do a permanent delete of the data
			if($permanent_delete) {

				global $wpdb;

				// Delete form
				$sql = sprintf("DELETE FROM %s WHERE id = %u;", $this->table_name, $this->id);
				if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error deleting submit', 'ws-form')); }

				// Delete meta
				$ws_form_meta = New WS_Form_Submit_Meta();
				$ws_form_meta->parent_id = $this->id;
				$ws_form_meta->db_delete_by_submit();

			} else {

				// Set status to 'trash'
				self::db_set_status('trash', $update_count_submit_unread);
			}

			return true;
		}

		// Delete trashed submits
		public function db_trash_delete() {

			// User capability check
			if(!WS_Form_Common::can_user('delete_submission')) { return false; }

			self::db_check_form_id();

			// Get all trashed forms
			$submits = self::db_read_all('', "status='trash' AND form_id=" . $this->form_id, '', '', '', false, false);

       		foreach($submits as $submit) {

				$this->id = $submit['id'];
				self::db_delete();
			}

			return true;
		}

		// Export by email
		public function db_exporter($email_address) {

			global $wpdb;

			// User capability check
			if(!WS_Form_Common::can_user('read_submission')) { return false; }

			// Check email address
			if(!filter_var($email_address, FILTER_VALIDATE_EMAIL)) { return false; }

			$data_to_export = array();

			// Get submit records
			$sql = sprintf('SELECT %1$s.id FROM %2$s LEFT OUTER JOIN %1$s ON %1$s.id = %2$s.parent_id WHERE (LOWER(%2$s.meta_value) = \'%3$s\') AND NOT (%1$s.id IS NULL);', $this->table_name, $this->table_name_meta, esc_sql(strtolower($email_address)));

			$submissions = $wpdb->get_results($sql, 'ARRAY_A');

			// Process results
			if($submissions) {

				foreach($submissions as $submission) {

					// Reset submit data
					$submit_data = array();

					// Get submit ID
					$submit_id = $submission['id'];

					// Get submit record
					$this->id = $submit_id;
					$submit = self::db_read();

					// Remove some data that will not be shared for security reasons or internal only
					unset($submit['form_id']);
					unset($submit['user_id']);
					unset($submit['id']);
					unset($submit['actions']);
					unset($submit['preview']);
					unset($submit['status']);

					// Push all submit data
					foreach($submit as $key => $value) {

						if(!is_array($value)) {

							$submit_data[] = array('name' => $key, 'value' => $value);

						} else {

							switch($key) {

								case 'meta' :

									foreach($value as $meta_key => $meta_value) {

										if(is_array($value)) {

											$value = $meta_value['value'];
											$value = is_array($value) ? print_r($value, true) : $value;

										} else {

											$value = $meta_value;											
										}

										$submit_data[] = array('name' => $meta_key, 'value' => $value);
									}

									break;
							}
						}
					}

					$data_to_export[] = array(
						'group_id'    => WS_FORM_USER_REQUEST_IDENTIFIER,
						'group_label' => __('Form Submissions', 'ws-form'),
						'item_id'     => WS_FORM_USER_REQUEST_IDENTIFIER . '-' . $submit['hash'],
						'data'        => $submit_data
					);
				}
			}

			// Return
			return array(

				'data' => $data_to_export,
				'done' => true,
			);
		}

		// Erase by email
		public function db_eraser($email_address) {

			global $wpdb;

			// User capability check
			if(!WS_Form_Common::can_user('delete_submission')) { return false; }

			// Check email address
			if(!filter_var($email_address, FILTER_VALIDATE_EMAIL)) { return false; }

			// Return array
			$items_removed_count = 0;
			$items_retained_count = 0;

			// Get submit records to be deleted
			$sql = sprintf('SELECT %1$s.id FROM %2$s LEFT OUTER JOIN %1$s ON %1$s.id = %2$s.parent_id WHERE (LOWER(%2$s.meta_value) = \'%3$s\') AND NOT (%1$s.id IS NULL);', $this->table_name, $this->table_name_meta, esc_sql(strtolower($email_address)));

			$submissions = $wpdb->get_results($sql, 'ARRAY_A');

			// Process results
			if($submissions) {

				$items_retained_count = count($submissions);

				if($items_retained_count > 0) {

					// Get first record (Delete one record each time eraser is requested to avoid timeouts)
					if(isset($submissions[0]['id'])) {

						// Delete submit record with permanent delete
						$this->id = $submissions[0]['id'];
						self::db_delete(true);

						$items_retained_count--;
						$items_removed_count++;
					}
				}
			}

			// Build return values
			$items_removed = ($items_removed_count > 0);
			$items_retained = ($items_retained_count > 0);
			$done = ($items_retained <= 0);
			$messages = (($items_removed > 0) && ($items_retained <= 0)) ? array(__('WS Form submissions successfully deleted.', 'ws-form')) : array();

			// Return
			return array(

				'items_removed' => $items_removed,
				'items_retained' => $items_retained,
				'messages' => $messages,
				'done' => $done,
			);
		}

		// Delete expired
		public function db_delete_expired($count_update_all = true) {

			global $wpdb;

			$sql = sprintf("UPDATE %s SET status = 'trash' WHERE (NOT date_expire IS NULL) AND (NOT date_expire = '0000-00-00 00:00:00') AND (NOT status = 'trash') AND (date_expire < '%s')", $this->table_name, WS_Form_Common::get_mysql_date());
			$rows_affected = $wpdb->query($sql);

			// Update form submit unread count statistic
			if($count_update_all) {

				$ws_form_form = new WS_Form_Form();
				$ws_form_form->db_count_update_all();
			}

			return $rows_affected;
		}

		// Get submission count by status
		public function db_get_count_by_status($form_id = 0, $status = '') {

			// User capability check
			if(!WS_Form_Common::can_user('read_submission')) { return false; }

			if(!WS_Form_Common::check_submit_status($status, false)) { $status = ''; }
			if($form_id == 0) { return 0; }

			global $wpdb;

			$sql = sprintf("SELECT COUNT(id) FROM %s WHERE", $this->table_name);
			if($status == '') { $sql .= " NOT(status = 'trash' OR status = 'spam')"; } else { $sql .= " status = '$status'"; }
			$sql .= " AND form_id = $form_id;";

			$form_count = $wpdb->get_var($sql);
			if(is_null($form_count)) { $form_count = 0; }

			return $form_count; 
		}

		// Get submit meta
		public function db_get_submit_meta($submit, $bypass_user_capability_check = false) {

			// No capabilities required, this is a public method
			$submit_meta = array();

			// Get submit record ID
			$submit_id = $submit['id'];
			$submit_encrypted = isset($submit['encrypted']) ? $submit['encrypted'] : false;

			// Read meta
			$ws_form_submit_meta = New WS_Form_Submit_Meta();
			$ws_form_submit_meta->parent_id = $submit_id;
			$meta_array = $ws_form_submit_meta->db_read_all($bypass_user_capability_check, $submit_encrypted);

			$field_cache = array();

			// Process meta data
			foreach($meta_array as $index => $meta) {

				// Get field value
				$value = (@unserialize($meta['meta_value']) !== false) ? unserialize($meta['meta_value']) : $meta['meta_value'];

				// Get field ID
				$field_id = intval($meta['field_id']);

				// If field ID found, process and return as array including type
				if($field_id > 0) {

					// Load field data to cache
					if(isset($field_cache[$field_id])) {

						// Use cached version
						$field_object = $field_cache[$field_id];

					} else {

						// Read field data and get type
						$ws_form_field = New WS_Form_Field();
						$ws_form_field->id = $field_id;
						$field_object = $ws_form_field->db_read(true, $bypass_user_capability_check);
						$field_cache[$field_id] = $field_object;
					}

					// If field no longer exists, just return the value
					if($field_object === false) {

						$submit_meta[$meta['meta_key']] = $value;
						continue;
					}

					// Process according to type
					$field_type = $field_object->type;
					switch($field_type) {

						case 'datetime' :

							$value_presentation_full = '';

							if($value != '') {

								$input_type_datetime = WS_Form_Common::get_object_meta_value($field_object, 'input_type_datetime', 'date');

								$value_presentation_full = WS_Form_Common::get_date_by_type($value, $input_type_datetime);
							}

							$value = array('mysql' => $value, 'presentation_full' => $value_presentation_full);

							break;
					}

					// If field type not known, skip
					if(!isset($this->field_types[$field_type])) { continue; };
					$field_type_config = $this->field_types[$field_type];

					// Submit array
					$field_submit_array = (isset($field_type_config['submit_array'])) ? $field_type_config['submit_array'] : false; 

					// Build meta key
					$meta_key = is_null($meta['meta_key']) ? (WS_FORM_FIELD_PREFIX . $field_id) : $meta['meta_key'];

					// Check for repeater
					$repeatable_index = (
						isset($meta['repeatable_index']) &&
						!is_null($meta['repeatable_index'])
					) ? intval($meta['repeatable_index']) : false;

					// Check for section_id
					$section_id = (
						isset($meta['section_id']) &&
						!is_null($meta['section_id'])
					) ? intval($meta['section_id']) : false;

					// Check for repeatable_delimiter_section
					$section_repeatable_section_string = 'section_' . $section_id;
					$section_repeatable_delimiter_section = (
						isset($this->section_repeatable[$section_repeatable_section_string]) &&
						isset($this->section_repeatable[$section_repeatable_section_string]['delimiter_section'])
					) ? $this->section_repeatable[$section_repeatable_section_string]['delimiter_section'] : WS_FORM_SECTION_REPEATABLE_DELIMITER_SECTION;

					// Check for repeatable_delimiter_row
					$section_repeatable_delimiter_row = (
						isset($this->section_repeatable[$section_repeatable_section_string]) &&
						isset($this->section_repeatable[$section_repeatable_section_string]['delimiter_row'])
					) ? $this->section_repeatable[$section_repeatable_section_string]['delimiter_row'] : WS_FORM_SECTION_REPEATABLE_DELIMITER_ROW;

					// Build meta data
					$meta_data = array('id' => $field_id, 'value' => $value, 'type' => $field_type, 'section_id' => $section_id, 'repeatable_index' => $repeatable_index);

					// Add to submit meta
					$submit_meta[$meta_key] = $meta_data;

					// Build fallback value
					if($repeatable_index !== false) {

						$meta_key_base = WS_FORM_FIELD_PREFIX . $field_id;

						$submit_meta_not_set = !isset($submit_meta[$meta_key_base]);

						if($submit_meta_not_set) {

							$submit_meta[$meta_key_base] = $meta_data;
							$submit_meta[$meta_key_base]['repeatable_index'] = false;
						}

						switch($field_type) {

							case 'file' :
							case 'signature' :

								if(!is_array($value)) { $value = array(); }

								if($submit_meta_not_set) {

									$submit_meta[$meta_key_base]['value'] = $value;

								} else {

									foreach($value as $file) {

										$submit_meta[$meta_key_base]['value'][] = $file;
									}
								}
								break;

							case 'datetime' :

								if($submit_meta_not_set) {

									$submit_meta[$meta_key_base]['value'] = $value;

								} else {

									$submit_meta[$meta_key_base]['value']['mysql'] .= $section_repeatable_delimiter_section . self::field_value_stringify($value['mysql'], $field_type, $field_submit_array, $section_repeatable_delimiter_row);
									$submit_meta[$meta_key_base]['value']['presentation_full'] .= $section_repeatable_delimiter_section . self::field_value_stringify($value['presentation_full'], $field_type, $field_submit_array, $section_repeatable_delimiter_row);
								}
								break;

							default :

								if($submit_meta_not_set) {

									$submit_meta[$meta_key_base]['value'] = self::field_value_stringify($submit_meta[$meta_key_base]['value'], $field_type, $field_submit_array, $section_repeatable_delimiter_row);
								} else {

									$submit_meta[$meta_key_base]['value'] .= $section_repeatable_delimiter_section . self::field_value_stringify($value, $field_type, $field_submit_array, $section_repeatable_delimiter_row);
								}

						}
					}

				} else {

					// Return as string
					$submit_meta[$meta['meta_key']] = $value;
				}
			}

			return $submit_meta;
		}

		// Get number for form submissions
		public function db_get_count_submit() {

			// User capability check
			if(!WS_Form_Common::can_user('read_submission')) { return false; }

			// Check form ID
			self::db_check_form_id();

			global $wpdb;

			// Get total number for form submissions
			$sql = sprintf("SELECT COUNT(id) AS count_submit FROM %s WHERE form_id = %u;", $this->table_name, $this->form_id);
			$count_submit = $wpdb->get_var($sql);
			if(!is_null($count_submit)) { return intval($count_submit); } else { return 0; }
		}

		// Get number for form submissions unread
		public function db_get_count_submit_unread($bypass_user_capability_check = false) {

			// User capability check
			if(!$bypass_user_capability_check && !WS_Form_Common::can_user('read_submission')) { return false; }

			// Check form ID
			self::db_check_form_id();

			global $wpdb;

			// Get total number for form submissions that are unread
			$sql = sprintf("SELECT COUNT(id) AS count_submit_unread FROM %s WHERE form_id = %u AND viewed = 0 AND status IN ('publish', 'draft');", $this->table_name, $this->form_id);
			$count_submit_unread = $wpdb->get_var($sql);
			if(!is_null($count_submit_unread)) { return intval($count_submit_unread); } else { return 0; }
		}

		// Restore
		public function db_restore($update_count_submit_unread = true) {

			// User capability check
			if(!WS_Form_Common::can_user('delete_submission')) { return false; }

			self::db_set_status('draft', $update_count_submit_unread);
		}

		// Set starred on / off
		public function db_set_starred($starred = true) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_submission')) { parent::db_access_denied(); }

			self::db_check_id();

			global $wpdb;

			// Build SQL
			$sql = sprintf("UPDATE %s SET starred = %u WHERE id = %u LIMIT 1;", $this->table_name, ($starred ? 1 : 0), $this->id);

			// Update submit record
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error setting starred status', 'ws-form')); }
		}

		// Set a submit record as viewed
		public function db_set_viewed($viewed = true, $update_count_submit_unread = true) {

			// User capability check
			if(!WS_Form_Common::can_user('read_submission')) { return false; }

			// Check ID
			self::db_check_id();

			global $wpdb;

			// Set viewed true
			$sql = sprintf("UPDATE %s SET viewed = %u WHERE id=%u LIMIT 1", $this->table_name, ($viewed ? 1 : 0), $this->id);
			if($wpdb->query($sql) === false) { parent::error(__('Error updating viewed status', 'ws-form')); }

			// Update form submit unread count statistic
			if($update_count_submit_unread) {

				$ws_form_form = new WS_Form_Form();
				$ws_form_form->id = $this->form_id;
				$ws_form_form->db_update_count_submit_unread();
			}
		}

		// Set status of submit
		public function db_set_status($status, $update_count_submit_unread = true) {

			// No capabilities required, this is a public method

			self::db_check_id();

			// Mark As Spam
			switch($status) {

				case 'spam' :

					$sql = sprintf("UPDATE %s SET status = '%s', spam_level = 100 WHERE id = %u LIMIT 1;", $this->table_name, esc_sql($status), $this->id);
					break;

				case 'not_spam' :

					$status = 'publish';
					$sql = sprintf("UPDATE %s SET status = '%s', spam_level = 0 WHERE id = %u LIMIT 1;", $this->table_name, esc_sql($status), $this->id);
					break;

				default :

					$sql = sprintf("UPDATE %s SET status = '%s' WHERE id = %u LIMIT 1;", $this->table_name, esc_sql($status), $this->id);
			}

			// Ensure provided submit status is valid
			self::db_check_status($status);

			global $wpdb;

			// Update submit record
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error setting submit status', 'ws-form')); }

			// Update form submit unread count statistic
			if($update_count_submit_unread) {

				self::db_check_form_id();

				$ws_form_form = new WS_Form_Form();
				$ws_form_form->id = $this->form_id;
				$ws_form_form->db_update_count_submit_unread();
			}

			return true;
		}

		// Check submit status
		public function db_check_status($status) {

			// Check status is valid
			$valid_statuses = explode(',', WS_FORM_STATUS_SUBMIT);
			if(!in_array($status, $valid_statuses)) { parent::db_throw_error(__('Invalid submit status: ' . $status, 'ws-form')); }

			return true;
		}

		// Get submit status name
		public function db_get_status_name($status) {

			switch($status) {

				case 'draft' : 		return __('In Progress', 'ws-form'); break;
				case 'publish' : 	return __('Submitted', 'ws-form'); break;
				case 'error' : 		return __('Error', 'ws-form'); break;
				case 'spam' : 		return __('Spam', 'ws-form'); break;
				case 'trash' : 		return __('Trash', 'ws-form'); break;
				default :			return $status;
			}
		}

		// Get submit columns
		public function db_get_submit_fields() {

			// User capability check
			if(!WS_Form_Common::can_user('read_submission')) { return false; }

			self::db_check_form_id();

			$visible_count = 0;
			$visible_count_max = 5;

			$submit_fields = array();

			// Get form object
			$this->preview = true;
			self::db_form_object_read();

			// Get fields in single dimension array
			$fields = WS_Form_Common::get_fields_from_form($this->form_object);

			// Excluded field types
			$field_types_excluded = array('textarea');

			foreach($fields as $field) {

				if(!isset($this->field_types[$field->type])) { continue; }

				// Get field type
				$field_type_config = $this->field_types[$field->type];

				// Skip unlicensed fields
				if(
					isset($field_type_config['pro_required']) &&
					$field_type_config['pro_required']

				) { continue; }

				// Skip fields that are not saved to meta data
				if(!$field_type_config['submit_save']) { continue; }

				// Determine if field is required
				$required = WS_Form_Common::get_object_meta_value($field, 'required', false);

				// Determine excluded fields
				$excluded = in_array($field->type, $field_types_excluded);

				// Push to submit_fields array
				$submit_fields[$field->id] = array(

					'label' 	=> $field->label,
					'required' 	=> $required,
					'excluded'	=> $excluded,
					'hidden'	=> true,
				);
			}

			// Go through each submit field and if it is required, mark it as not hidden
			foreach($submit_fields as $id => $field) {

				if($visible_count < $visible_count_max) {

					if($field['required'] && !$field['excluded']) {

						$submit_fields[$id]['hidden'] = false;
						$visible_count++;
					}

					if($visible_count == $visible_count_max) { break; }
				}
			}

			if($visible_count < $visible_count_max) {

				// Go through each submit field and if it is not required, mark it as not hidden
				foreach($submit_fields as $id => $field) {

					if($visible_count < $visible_count_max) {

						if(!$field['required'] && !$field['excluded']) {

							$submit_fields[$id]['hidden'] = false;
							$visible_count++;
						}

						if($visible_count == $visible_count_max) { break; }
					}
				}
			}

			return $submit_fields;
		}

		// Create CSV file
		public function db_export_csv(&$file, $submit_ids) {

			// User capability check
			if(!WS_Form_Common::can_user('export_submission')) { return false; }

			// Get field data
			$submit_fields = $this->db_get_submit_fields();

			// Build CSV column headings
			$csv_header = array();

			// Fixed fields
			$csv_header_fields = array('id' => 'Submission ID', 'status_full' => 'Status', 'date_added' => 'Date Added', 'date_updated' => 'Date Updated', 'user_id' => 'User ID', 'user_first_name' => 'User First Name', 'user_last_name' => 'User Last Name', 'duration' => 'Duration (Seconds)');
			foreach(array_slice($csv_header_fields, 1) as $key => $value) { $csv_header[] = $value; } // Ignore first index

			// Form fields
			foreach($submit_fields as $submit_field) { $csv_header[] = $submit_field['label']; }

			// E-Commerce
			$ecommerce = WS_Form_Config::get_ecommerce(false);

			// Builder header columns according to priority
			$csv_header_ecommerce = [];
			foreach($ecommerce['cart_price_types'] as $key => $ecommerce_config) {

				$priority = isset($ecommerce_config['priority']) ? $ecommerce_config['priority'] : 10000;
				$csv_header_ecommerce[] = array('key' => 'ecommerce_cart_' . $key, 'label' => $ecommerce_config['label'], 'priority' => $priority);
			}
			foreach($ecommerce['meta_keys'] as $key => $ecommerce_config) {

				$priority = isset($ecommerce_config['priority']) ? $ecommerce_config['priority'] : 10000;
				$csv_header_ecommerce[] = array('key' => $key, 'label' => $ecommerce_config['label'], 'priority' => $priority);
			}
			uasort($csv_header_ecommerce, function ($csv_header_ecommerce_1, $csv_header_ecommerce_2) {

				return $csv_header_ecommerce_1['priority'] < $csv_header_ecommerce_2['priority'] ? -1 : 1;
			});
			foreach($csv_header_ecommerce as $csv_header_ecommerce_single) {

				$csv_header[] = $csv_header_ecommerce_single['label'];
			}

			// Tracking
			$tracking = WS_Form_Config::get_tracking(false);
			foreach($tracking as $key => $tracking_config) {

				$csv_header[] = $tracking_config['label'];
			}
			// Output header row
			fwrite($file, '"ID",');	// To overcome issue with Excel thinking 'ID,' is an SYLK file
			fputcsv($file, $csv_header);

			// Get id's to download
			if(!is_array($submit_ids)) { $submit_ids = (empty($submit_ids) ? array() : array($submit_ids)); }

			if(count($submit_ids) > 0) {

				// Check integrity of array
				foreach($submit_ids as $key => $submit_id) {

					if(!is_numeric($submit_id)) { unset($submit_ids[$key]); }
				}

				// Throw error if no valid submit ID's
				if(count($submit_ids) == 0) { self::db_throw_error(__('Invalid submit ID')); }

				// Build WHERE sql
				$where = 'id IN (' . implode(',', $submit_ids) . ') AND ';

			} else {

				$where = '';
			}

			$where .= sprintf("(NOT status='trash') AND form_id=%u", $this->form_id);

			global $wpdb;

			// Get form data
			$sql = sprintf("SELECT %s FROM %s WHERE %s ORDER BY date_added", self::DB_SELECT, $this->table_name, $where);

			$submits = $wpdb->get_results($sql, 'ARRAY_A');

			// Process meta data
			foreach($submits as $key => $submit) {

				// Read expanded
				self::db_read_expanded($submit);

				// Get meta data
				$submit_meta = self::db_get_submit_meta($submit);

				// Add meta to return array
				$submit = $submit + $submit_meta;

				$row_array = array();

				// Fixed fields
				foreach($csv_header_fields as $key => $value) {

					switch($key) {

						case 'date_added' :

							$row_array[] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime(get_date_from_gmt($submit['date_added'])));
							break;

						case 'date_updated' :

							$row_array[] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime(get_date_from_gmt($submit['date_updated'])));
							break;

						case 'user_first_name' :

							$row_array[] = isset($submit['user']) ? $submit['user']['first_name'] : '';
							break;

						case 'user_last_name' :

							$row_array[] = isset($submit['user']) ? $submit['user']['last_name'] : '';
							break;

						default :

							$row_array[] = $submit[$key];
					}
				}

				// Form fields
				foreach($submit_fields as $id => $field) {

					$field_name = WS_FORM_FIELD_PREFIX . $id;

					// Get type
					$type = isset($submit[$field_name]) ? (isset($submit[$field_name]['type']) ? $submit[$field_name]['type'] : '') : '';

					// Get value
					$value = isset($submit[$field_name]) ? (isset($submit[$field_name]['value']) ? $submit[$field_name]['value'] : '') : '';

					// Apply filter
					$value = apply_filters('wsf_submit_field_type_csv', $value, $id, $type);

					// Process by type
					switch($type) {

						case 'signature' :
						case 'file' :

							if(!is_array($value)) { break; }

							$value_filenames = array();

							foreach($value as $value_file) {

								$value_filenames[] = $value_file['name'];
							}

							$value = implode(',', $value_filenames);

							break;

						case 'datetime' :

							if(is_array($value)) { $value = $value['mysql']; }
							break;
					}

					// Process array values (e.g. Select, Checkbox, Radio field types)
					if(is_array($value)) { $value = implode(',', $value); }

					// Add column
					$row_array[] = $value;
				}

				// E-Commerce
				foreach($csv_header_ecommerce as $csv_header_ecommerce_single) {

					// Get key
					$key = $csv_header_ecommerce_single['key'];

					// Get value
					$value = isset($submit[$key]) ? $submit[$key] : '';

					// Add column
					$row_array[] = $value;
				}

				// Tracking
				foreach($tracking as $key => $tracking_config) {

					// Get tracking value
					$value = isset($submit[$key]) ? $submit[$key] : '';

					// Add column
					$row_array[] = $value;
				}
				// Output data
				fputcsv($file, $row_array);
			}
		}

		// Setup from post
		public function setup_from_post() {

			// No capabilities required, this is a public method

			// Get form_id
			$this->form_id = intval(WS_Form_Common::get_query_var_nonce('wsf_form_id', 0));
			self::db_check_form_id();

			// Get hash
			$this->hash = WS_Form_Common::get_query_var_nonce('wsf_hash', 0);

			// If hash found, look for form submission
			if(($this->hash != '') && (strlen($this->hash) == 32)) {

				// Read submit by hash
				$this->db_read_by_hash(true, true, true, true);

				// Clear meta data
				$this->meta = array();
				$this->meta_protected = array();

			} else {

				// Create fresh hash for this submission
				$this->db_create_hash();
			}

			// Preview submit?
			$this->preview = (WS_Form_Common::get_query_var_nonce('wsf_preview', false) !== false);

			// Read form
			self::db_form_object_read();

			// Bypass fields
			$bypass = WS_Form_Common::get_query_var_nonce('wsf_bypass', '');
			$this->bypass_array = explode(',', $bypass);

			// Spam protection - Honeypot
			$honeypot_hash = ($this->form_object->published_checksum != '') ? $this->form_object->published_checksum : 'honeypot_unpublished_' . $this->form_id;
			$honeypot_value = WS_Form_Common::get_query_var_nonce("field_$honeypot_hash");
			if($honeypot_value != '') { parent::db_throw_error(__('Spam protection error', 'ws-form')); }

			// Get sections array
			$sections = WS_Form_Common::get_sections_from_form($this->form_object);

			// Are we submitting the form or just saving it?
			$this->post_mode = WS_Form_Common::get_query_var_nonce('wsf_post_mode', false);
			$form_submit = ($this->post_mode == 'submit');

			// Ensure post mode is valid
			if(!in_array($this->post_mode, array('submit', 'save', 'action'))) { parent::db_throw_error(__('Invalid post mode', 'ws-form')); }

			// Build section_repeatable
			$section_repeatable = array();
			$wsf_form_section_repeatable_index_json = WS_Form_Common::get_query_var_nonce('wsf_form_section_repeatable_index', false);
			if(!empty($wsf_form_section_repeatable_index_json)) {

				if(is_null($wsf_form_section_repeatable_index = (array) json_decode($wsf_form_section_repeatable_index_json))) {

					parent::db_throw_error(__('Malformed wsf_form_section_repeatable_index JSON value.', 'ws-form'));
				}

				// Save wsf_form_section_repeatable_index to section_repeatable and parse it to ensure the data is valid
				foreach($wsf_form_section_repeatable_index as $section_id_string => $indexes) {

					$section_repeatable[$section_id_string] = array('index' => array());

					foreach($indexes as $index) {

						if(intval($index) <= 0) { continue; }

						$section_repeatable[$section_id_string]['index'][] = intval($index);
					}
				}
			}

			// Process each section
			foreach($sections as $section_id => $section) {

				if($section->repeatable) {

					$section_id_string = 'section_' . $section_id;

					// Get repeatable indexes for that section
					if(
						!isset($section_repeatable[$section_id_string]) ||
						!isset($section_repeatable[$section_id_string]['index'])
					) {

						parent::db_throw_error(__('Repeatable data error. Section ID not found in wsf_form_section_repeatable_index.', 'ws-form'));
					}

					$section_repeatable_indexes = $section_repeatable[$section_id_string]['index'];

					foreach($section_repeatable_indexes as $section_repeatable_index) {

						self::setup_from_post_section($section, $form_submit, $section_id, $section_repeatable_index, $section_repeatable);
					}

				} else {

					self::setup_from_post_section($section, $form_submit);
				}
			}

			if(!empty($section_repeatable)) {

				$this->section_repeatable = serialize($section_repeatable);
			}
		}

		function setup_from_post_section($section, $form_submit, $section_id = false, $section_repeatable_index = false, &$section_repeatable = array()) {

			// Delimiters
			if($section_repeatable_index !== false) {

				// Get delimiters
				$section_repeatable_delimiter_section = WS_Form_Common::get_object_meta_value($section, 'section_repeatable_delimiter_section', WS_FORM_SECTION_REPEATABLE_DELIMITER_SECTION);
				if($section_repeatable_delimiter_section == '') { $section_repeatable_delimiter_section = WS_FORM_SECTION_REPEATABLE_DELIMITER_SECTION; }
				$section_repeatable_delimiter_row = WS_Form_Common::get_object_meta_value($section, 'section_repeatable_delimiter_row', WS_FORM_SECTION_REPEATABLE_DELIMITER_ROW);
				if($section_repeatable_delimiter_row == '') { $section_repeatable_delimiter_row = WS_FORM_SECTION_REPEATABLE_DELIMITER_ROW; }

				// Add delimiters to section_repeatable
				if(!isset($section_repeatable['section_' . $section_id])) { $section_repeatable['section_' . $section_id] = array(); }
				$section_repeatable['section_' . $section_id]['delimiter_section'] = $section_repeatable_delimiter_section;
				$section_repeatable['section_' . $section_id]['delimiter_row'] = $section_repeatable_delimiter_row;
			}

			// Process each field
			$section_fields = $section->fields;
			foreach($section_fields as $field) {

				// If field type not specified, skip
				if(!isset($field->type)) { continue; };
				$field_type = $field->type;

				// If field type not known, skip
				if(!isset($this->field_types[$field_type])) { continue; };
				$field_type_config = $this->field_types[$field_type];

				// If field is not licensed, skip
				if(
					isset($field_type_config['pro_required']) &&
					$field_type_config['pro_required']

				) { continue; }

				// 

				// Submit array
				$field_submit_array = (isset($field_type_config['submit_array'])) ? $field_type_config['submit_array'] : false; 

				// Is field in a repeatable section?
				$field_section_repeatable = isset($field->section_repeatable) && $field->section_repeatable;

				// Save meta data
				if(!isset($field->id)) { continue; }
				$field_id = intval($field->id);

				// Build field name
				$field_name = WS_FORM_FIELD_PREFIX . $field_id;

				// Field value
				$field_value = WS_Form_Common::get_query_var_nonce($field_name);
				if($section_repeatable_index !== false) {

					$field_value = isset($field_value[$section_repeatable_index]) ? $field_value[$section_repeatable_index] : '';
				}

				// Field required
				$field_required = WS_Form_Common::get_object_meta_value($field, 'required', false);

				// Process according to field type
				switch($field_type) {

					case 'file' :

						// Field accept
						$field_accept = WS_Form_Common::get_object_meta_value($field, 'accept', false);

						// Image processing
						$file_image_max_width = WS_Form_Common::get_object_meta_value($field, 'file_image_max_width', '');
						$file_image_max_height = WS_Form_Common::get_object_meta_value($field, 'file_image_max_height', '');
						$file_image_crop = (WS_Form_Common::get_object_meta_value($field, 'file_image_crop', '') == 'on');
						$file_image_compression = WS_Form_Common::get_object_meta_value($field, 'file_image_compression', '');
						$file_image_mime = WS_Form_Common::get_object_meta_value($field, 'file_image_mime', '');

						// Check $_FILES to see if data for this field exists
						$file_objects = self::db_file_process($field_id, $field_name, $field_required, $field_accept, $file_image_max_width, $file_image_max_height, $file_image_crop, $file_image_compression, $file_image_mime, $form_submit, $section_repeatable_index);
						$field_value = ($file_objects !== false) ? $file_objects : '';

						break;

					case 'signature' :

						$file_objects = self::db_signature_process($field_id, $field_value, $section_repeatable_index);
						$field_value = ($file_objects !== false) ? $file_objects : '';

						break;

					case 'recaptcha' :

						// Only process if form is being submitted
						if($form_submit) {

							// Get reCAPTCHA secret
							$recaptcha_secret_key = WS_Form_Common::get_object_meta_value($field, 'recaptcha_secret_key', false);

							// Process reCAPTCHA
							self::db_recaptcha_process($field_name, $recaptcha_secret_key);
						}

						break;

					case 'price' :
					case 'price_range' :

						$field_value = WS_Form_Common::get_price(WS_Form_Common::get_number($field_value, 0, true));

						break;
				}
				// Check for bypass (Fields that are hidden)
				if(in_array($field_name, $this->bypass_array)) {

					$field_required = false;
					$field_value = '';
				}

				// Handle required fields
				if($form_submit && $field_required && ($field_value == '')) {

					self::db_throw_error_submit_validation(sprintf(__('Required field missing: %s', 'ws-form'), $field->label));
				}

				// Dedupe
				if($field_value != '') {

					$field_dedupe = WS_Form_Common::get_object_meta_value($field, 'dedupe', false);
					if($field_dedupe) {

						// Check for a dupe
						$ws_form_submit_meta = new WS_Form_Submit_Meta();
						if($ws_form_submit_meta->db_dupe_check($this->form_id, $field_id, $field_value)) {

							$field_dedupe_message = WS_Form_Common::get_object_meta_value($field, 'dedupe_message', '');
							if($field_dedupe_message == '') {

								$field_dedupe_message = __('The value entered for #label_lowercase has already been used.', 'ws-form');
							}

							$field_dedupe_message_lookups = array(

								'label_lowercase' 	=> strtolower($field->label),
								'label' 			=> $field->label
							);

							$field_dedupe_message = WS_Form_Common::mask_parse($field_dedupe_message, $field_dedupe_message_lookups);

							$this->error_validation_fields[] = array(

								'field_id' 				=> $field_id,
								'invalid_feedback'  	=> $field_dedupe_message
							);

							$this->error_validation = true;
						}
					}
				}

				// If field type should not be saved, skip
				$submit_save = isset($field_type_config['submit_save']) ? $field_type_config['submit_save'] : false;

				// Build meta_data
				$meta_data = array('id' => $field_id, 'value' => $field_value, 'type' => $field_type, 'section_id' => $section_id, 'repeatable_index' => $section_repeatable_index);
				$meta_key_suffix = (($section_repeatable_index !== false) ? ('_' . $section_repeatable_index) : '');
				if($submit_save !== false) {

					$meta_field = 'meta';

				} else {

					$meta_field = 'meta_protected';
				}

				// Add to submit meta protected
				$this->{$meta_field}[WS_FORM_FIELD_PREFIX . $field_id . $meta_key_suffix] = $meta_data;

				// Build fallback value
				if($section_repeatable_index !== false) {

					$meta_not_set = !isset($this->{$meta_field}[WS_FORM_FIELD_PREFIX . $field_id]);

					if($meta_not_set) {

						$this->{$meta_field}[WS_FORM_FIELD_PREFIX . $field_id] = $meta_data;

						// We don't store the fallback data to the database, it is just made available to any actions that need it
						$this->{$meta_field}[WS_FORM_FIELD_PREFIX . $field_id]['db_ignore'] = true;

						// Set repeatable index to false
						$this->{$meta_field}[WS_FORM_FIELD_PREFIX . $field_id]['repeatable_index'] = false;
					}

					switch($field_type) {

						// Merge files
						case 'file' :
						case 'signature' :

							if($meta_not_set) {

								$this->{$meta_field}[WS_FORM_FIELD_PREFIX . $field_id]['value'] = $field_value;

							} else {

								if(is_array($field_value)) {

									$meta_value = $this->{$meta_field}[WS_FORM_FIELD_PREFIX . $field_id]['value'];

									if(!is_array($meta_value)) {

										// Currently a blank string
										$this->{$meta_field}[WS_FORM_FIELD_PREFIX . $field_id]['value'] = $field_value;

									} else {

										// Currently an array
										$this->{$meta_field}[WS_FORM_FIELD_PREFIX . $field_id]['value'] = array_merge($field_value, $meta_value);
									}
								}
							}

							break;

						// Other fields
						default :

							if($meta_not_set) {

								$this->{$meta_field}[WS_FORM_FIELD_PREFIX . $field_id]['value'] = self::field_value_stringify($this->{$meta_field}[WS_FORM_FIELD_PREFIX . $field_id]['value'], $field_type, $field_submit_array, $section_repeatable_delimiter_row);

							} else {

								$this->{$meta_field}[WS_FORM_FIELD_PREFIX . $field_id]['value'] .= $section_repeatable_delimiter_section . self::field_value_stringify($field_value, $field_type, $field_submit_array, $section_repeatable_delimiter_row);
							}
					}
				}
			}

			// Tracking
			$tracking_array = WS_Form_Config::get_tracking(false);
			foreach($tracking_array as $meta_key => $tracking) {

				// Check meta_key is enabled
				if(WS_Form_Common::get_object_meta_value($this->form_object, $meta_key, false)) {

					// Get value
					switch($tracking['server_source']) {

						case 'http_env' :

							$meta_value = isset($tracking['server_http_env']) ? WS_Form_Common::get_http_env($tracking['server_http_env']) : '';
							break;

						case 'query_var' :

							$meta_value = isset($tracking['server_query_var']) ? WS_Form_Common::get_query_var_nonce($tracking['server_query_var']) : '';
							break;

						case 'ip_lookup' :

							$meta_value = isset($tracking['server_json_var']) ? WS_Form_Common::get_ip_lookup($tracking['server_json_var']) : '';
							break;

						default :

							$meta_value = '';
					}

					// Add to submit meta
					$this->meta[$meta_key] = $meta_value;
				}
			}

			// E-Commerce
			if(WS_Form_Common::get_query_var_nonce('wsf_ecommerce_cart') != '') {

				// Check for e-commerce fields
				$ecommerce_cart = WS_Form_Common::get_query_var_nonce('wsf_ecommerce_cart');
				if(

					is_array($ecommerce_cart) &&
					(count($ecommerce_cart) > 0)
				) {

					foreach($ecommerce_cart as $price_type => $meta_value) {

						// Add to submit meta
						if(floatVal($meta_value <> 0)) {

							$this->meta['ecommerce_cart_' . $price_type] = $meta_value;
						}
					}
				}

				// E-Commerce transaction ID
				$this->meta['ecommerce_transaction_id'] = WS_Form_Common::get_query_var_nonce('wsf_ecommerce_transaction_id');

				// E-Commerce transaction (JSON)
				$this->meta['ecommerce_transaction'] = WS_Form_Common::get_query_var_nonce('wsf_ecommerce_transaction');

				// E-Commerce status
				$this->meta['ecommerce_status'] = WS_Form_Common::get_query_var_nonce('wsf_ecommerce_status');

				// E-Commerce payment method
				$this->meta['ecommerce_payment_method'] = WS_Form_Common::get_query_var_nonce('wsf_ecommerce_payment_method');
			}
		}

		// Meta value stringify
		public function field_value_stringify($field_value, $field_type, $field_submit_array, $section_repeatable_delimiter_row) {

			if($field_submit_array) {

				if(!is_array($field_value)) { $field_value = array($field_value); }

				switch($field_type) {

					case 'file' :
					case 'signature' :

						$field_value = $field_value['name'];
						break;

					case 'datetime' :

						$field_value = is_array($field_value) ? (isset($field_value['presentation_full']) ? $field_value['presentation_full'] : '') : $field_value;
						break;

					default :

						$field_value = implode($section_repeatable_delimiter_row, $field_value);
				}
			}

			return $field_value;
		}

		// Read form object
		public function db_form_object_read() {

			// Check form ID
			self::db_check_form_id();

			// Read form data
			$ws_form_form = New WS_Form_Form();
			$ws_form_form->id = $this->form_id;

			if($this->preview) {

				// Draft
				$form_object = $ws_form_form->db_read(true, true);

				// Form cannot be read
				if($form_object === false) { parent::db_throw_error(__('Unable to read form data (Still logged in?)', 'ws-form')); }

			} else {

				// Published
				$form_object = $ws_form_form->db_read_published();

				// Form not yet published
				if($form_object === false) { parent::db_throw_error(__('No published form data', 'ws-form')); }
			}

			// Filter
			$form_object = apply_filters('wsf_pre_submit_' . $this->form_id, $form_object, $this->preview);
			$form_object = apply_filters('wsf_pre_submit', $form_object, $this->preview);

			// Convert to object
			$this->form_object = $form_object;
		}

		// Process file upload
		public function db_file_process($field_id, $field_name, $field_required, $field_accept, $file_image_max_width, $file_image_max_height, $file_image_crop, $file_image_compression, $file_image_mime, $form_submit, $section_repeatable_index = false) {

			// No capabilities required, this is a public method

			// Initialize file system
			$max_upload_size = intval(WS_Form_Common::option_get('max_upload_size'));
			$max_uploads = intval(WS_Form_Common::option_get('max_uploads'));
			$field_accept = WS_Form_Common::get_mime_array($field_accept);

			// Check $_FILES exists
			if(!isset($_FILES)) { return false; }

			// Check $_FILES[$field_name] exists
			if(!isset($_FILES[$field_name])) { return false; }

			// Check $_FILES[$field_name]['error'] exists
			if(!isset($_FILES[$field_name]['error'])) { return false; }

			// File count
			$file_errors = ($section_repeatable_index === false) ? $_FILES[$field_name]['error'] : $_FILES[$field_name]['error'][$section_repeatable_index];
			$file_count = count($file_errors);
			if($file_count > $max_uploads) {

				self::db_throw_error_submit_validation(sprintf(__('Too many files were uploaded at once (Maximum: %u files)', 'ws-form'), $max_uploads));
				return false;
			}

			// Process files
			$file_objects = array();
			foreach($file_errors as $file_key => $file_error) {

				$file_error = intval($file_error);

				// Error management
				switch($file_error) {

					case 0:
						break;

					case UPLOAD_ERR_INI_SIZE: 
					case UPLOAD_ERR_FORM_SIZE: 

						self::db_throw_error_submit_validation(sprintf(__('The uploaded file was too large (Maximum size: %u bytes)', 'ws-form'), WS_Form_Common::ini_shorthand_notation_to_bytes(ini_get('upload_max_filesize'))));
						return false;

					case UPLOAD_ERR_PARTIAL: 
						parent::db_throw_error(__('The uploaded file was only partially uploaded', 'ws-form')); 
						return false;

					case UPLOAD_ERR_NO_FILE :

						if($field_required && $form_submit) {

							self::db_throw_error_submit_validation(__('No file was uploaded', 'ws-form'));
						}
						return false;

					case UPLOAD_ERR_NO_TMP_DIR: 
						parent::db_throw_error(__('Missing a temporary folder', 'ws-form')); 
						return false;

					case UPLOAD_ERR_CANT_WRITE: 
						parent::db_throw_error(__('Failed to write file to disk', 'ws-form')); 
						return false;

					case UPLOAD_ERR_EXTENSION: 
						parent::db_throw_error(__('File upload stopped by extension', 'ws-form')); 
						return false;

					default :
						parent::db_throw_error(__('Unknown file upload error', 'ws-form')); 
						return false;
				}

				// Get file data
				$file_name = ($section_repeatable_index === false) ? $_FILES[$field_name]['name'][$file_key] : $_FILES[$field_name]['name'][$section_repeatable_index][$file_key];
				$file_type = ($section_repeatable_index === false) ? $_FILES[$field_name]['type'][$file_key] : $_FILES[$field_name]['name'][$section_repeatable_index][$file_key];
				$file_size = intval(($section_repeatable_index === false) ? $_FILES[$field_name]['size'][$file_key] : $_FILES[$field_name]['size'][$section_repeatable_index][$file_key]);
				$file_tmp_name = ($section_repeatable_index === false) ? $_FILES[$field_name]['tmp_name'][$file_key] : $_FILES[$field_name]['tmp_name'][$section_repeatable_index][$file_key];

				// Check file was uploaded by HTTP POST
				if(!is_uploaded_file($file_tmp_name)) { parent::db_throw_error(__('Illegal file upload', 'ws-form')); }

				// Check file name
				if(strpos($file_name, '/') !== false) { parent::db_throw_error(__('Illegal file name', 'ws-form')); }
				if(strpos($file_name, '\\') !== false) { parent::db_throw_error(__('Illegal file name', 'ws-form')); }
				$file_name = basename($file_name);

				// Check file size
				if($file_size > $max_upload_size) {

					self::db_throw_error_submit_validation(sprintf(__('The uploaded file was too large (Maximum size: %u bytes)', 'ws-form'), $max_upload_size));
					return false;
				}

				// Check file type
				if(count($field_accept) > 0) {

					if(!in_array($file_type, $field_accept)) {

						self::db_throw_error_submit_validation(sprintf(__('The uploaded file was not an accepted type (%s)', 'ws-form'), implode(', ', $field_accept)));
						return false;
					}
				}

				// Image processing
				$image_processing_mime_array = array(

					'image/jpeg',
					'image/png',
					'image/gif'
				);
				$image_processing_save = false;
				if(in_array($file_type, $image_processing_mime_array)) {

					$image = wp_get_image_editor($file_tmp_name);

					if(!is_wp_error($image)) {

						// Resize
						if(
							($file_image_max_width != '') ||
							($file_image_max_height != '')
						) {

							$file_image_max_width = intval($file_image_max_width);
							$file_image_max_height = intval($file_image_max_height);

							$image->resize($file_image_max_width, $file_image_max_height, $file_image_crop);
							$image_processing_save = true;
						}

						// Image quality
						if($file_image_compression != '') {

							$file_image_compression = intval($file_image_compression);
							if(
								($file_image_compression >= 1) &&
								($file_image_compression <= 100)
							) {

								$image->set_quality($file_image_compression);
								$image_processing_save = true;
							}
						}

						// Image mime
						if($file_image_mime != '') {

							if($image->supports_mime_type($file_image_mime)) {

								$file_type = $file_image_mime;
								$image_processing_save = true;
							}
						}

						// Save
						if($image_processing_save) {

							// Save file
							$saved_image = $image->save($file_tmp_name, $file_type);

							if($saved_image) {

								// Read saved file data
								$file_name = pathinfo($file_name)['filename'] . '.' . pathinfo($saved_image['file'])['extension'];
								$file_tmp_name = $saved_image['path'];
								$file_size = filesize($file_tmp_name);
								$file_type = $saved_image['mime-type'];

							} else {

								// Error saving file
								parent::db_throw_error(__('Unable to save image file after image optimization.', 'ws-form'));
							}
						}
					}
				}

				// Build file upload path
				$file_upload_path = $this->form_id . '/' . $this->hash . '/' . $field_id;

				// Apply filter
				$file_upload_path = apply_filters('wsf_file_upload_path', $file_upload_path);
				$file_upload_path = apply_filters('wsf_file_upload_path_file', $file_upload_path);

				$upload_dir = WS_Form_Common::upload_dir_create($file_upload_path);
				if($upload_dir['error']) { self::db_throw_error($upload_dir['error']); }
				$file_upload_dir = $upload_dir['dir'];

				// Move uploaded file to WordPress uploads folder
				$file_repeatable_suffix = ($section_repeatable_index !== false) ? ('_' . $section_repeatable_index) : '';
				$file_name_hash = md5($file_upload_dir . '/' . $file_name . $file_repeatable_suffix);
				$move_uploaded_file_destination =  $file_upload_dir . '/' . $file_name_hash;
				rename($file_tmp_name, $move_uploaded_file_destination);

				// Check file object
				$file_object = array();
				$file_object['name'] = $file_name;
				$file_object['hash'] = $file_name_hash;
				$file_object['type'] = $file_type;
				$file_object['size'] = $file_size;
				$file_object['path'] = $upload_dir['path'] . '/' . $file_name_hash;		// Store this in case we change the path structure in future

				// Push to file objects array
				$file_objects[] = $file_object;
			}

			return $file_objects;
		}

		// Process signature
		public function db_signature_process($field_id, $field_value, $section_repeatable_index) {

			$ws_form_submit_meta = new WS_Form_Submit_Meta();
			return $ws_form_submit_meta->process_signature($field_id, $field_value, $this, $section_repeatable_index);
		}

		// Process reCAPTCHA
		public function db_recaptcha_process($field_name, $recaptcha_secret_key) {

			// Check reCAPTCHA response
			if($recaptcha_secret_key == '') {

				parent::db_throw_error(__('reCAPTCHA secret key not set', 'ws-form'));
			}					

			// Get reCAPTCHA response
			$recaptcha_response = WS_Form_Common::get_query_var_nonce('g-recaptcha-response');
			if($recaptcha_response == '') {

				self::db_throw_error_submit_validation(__('Empty reCAPTCHA response', 'ws-form'));
				return false;
			}

			// Check status of reCaptcha from Google reCaptcha endpoint
			$response = wp_remote_post(WS_FORM_RECAPTCHA_ENDPOINT, array(

					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array(),
					'body' => array('secret' => $recaptcha_secret_key, 'response' => $recaptcha_response),
					'cookies' => array()
				)
			);

			if(is_wp_error($response)) {

				$error_message = $response->get_error_message();
				parent::db_throw_error(sprintf(__('reCAPTCHA verification failed (%s)', 'ws-form'), $error_message));

			} else {

				$response_body = wp_remote_retrieve_body($response); 
				if($response_body == '') {

					parent::db_throw_error(__('reCAPTCHA verification response empty', 'ws-form'));
				}

				$response_object = json_decode($response_body);
				if(is_null($response_object)) {

					parent::db_throw_error(__('reCAPTCHA verification response error', 'ws-form'));
				}

				$recaptcha_success = $response_object->success;

				if($recaptcha_success) {

					// Store spam level
					$this->spam_level = isset($response_object->score) ? ((1 - floatval($response_object->score)) * WS_FORM_SPAM_LEVEL_MAX) : $this->spam_level;

					return true;

				} else {

					self::db_throw_error_submit_validation(__('reCAPTCHA invalid', 'ws-form'));
					return false;
				}
			}
		}
		// Handle server side validation error
		public function db_throw_error_submit_validation($messages) {

			if(!is_array($messages)) { $messages = array($messages); }

			foreach($messages as $message) {

				$this->error_validation_messages[] = $message;
			}

			$this->error_validation = true;
		}

		// Remove protected meta data
		public function db_remove_meta_protected() {

			$this->meta_protected = array();
		}

		// Compact
		public function db_compact() {

			// Remove form_object
			if(isset($this->form_object)) { unset($this->form_object); }
			if(isset($this->field_types)) { unset($this->field_types); }
		}

		// Create hash
		public function db_create_hash() {

			if($this->hash == '') { $this->hash = esc_sql(wp_hash($this->id . '_' . $this->form_id . '_' . time())); }

			return $this->hash;
		}

		// Check hash
		public function db_check_hash() {

			if(($this->hash == '') || (strlen($this->hash) != 32)) { parent::db_throw_error(__('Invalid hash ID', 'ws-form')); }
			return true;
		}

		// Check form id
		public function db_check_form_id() {

			if($this->form_id <= 0) { parent::db_throw_error(__('Invalid form ID', 'ws-form')); }
			return true;
		}

		// Check id
		public function db_check_id() {

			if($this->id <= 0) { parent::db_throw_error(__('Invalid submit ID', 'ws-form')); }
			return true;
		}
	}