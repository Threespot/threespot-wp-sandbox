<?php

	class WS_Form_Meta extends WS_Form_Core {

		public $id;
		public $object;
		public $parent_id;
		public $object_meta;

		public $meta_keys;

		const DB_INSERT = 'meta_key,meta_value,parent_id';
		const DB_SELECT = 'meta_key,meta_value';

		public function __construct() {

			$this->id = 0;
			$this->object = '';
			$this->parent_id = 0;
			$this->api_request_methods = ['GET'];
			$this->object_meta = false;
		}

		// Get table name
		public function db_get_table_name() {

			if($this->object == '') { parent::db_throw_error(__('Object not set', 'ws-form')); }

			global $wpdb;

			return $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . $this->object . '_meta';
		}

		// Read meta data
		public function db_read($meta_key) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			global $wpdb;

			$meta_object = new stdClass();

			if($this->parent_id <= 0) { parent::db_throw_error(__('Parent ID not set')); }

			$sql = sprintf("SELECT meta_value FROM %s WHERE parent_id = %u AND meta_key = '%s' LIMIT 1", self::db_get_table_name(), $this->parent_id, $meta_key);
			$meta_value = $wpdb->get_var($sql);

			if(is_null($meta_value)) { return false; }

			if(@unserialize($meta_value) !== false) {

				return unserialize($meta_value);

			} else {

				return $meta_value;
			}
		}

		// Read all meta data
		public function db_read_all() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			global $wpdb;

			$meta_object = new stdClass();

			if($this->parent_id <= 0) { parent::db_throw_error(__('Parent ID not set')); }

			$sql = sprintf("SELECT %s FROM %s WHERE parent_id = %u", self::DB_SELECT, self::db_get_table_name(), $this->parent_id);
			$metas = $wpdb->get_results($sql, 'ARRAY_A');

			if($metas) {

				foreach($metas as $key => $meta) {

					if(@unserialize($meta['meta_value']) !== false) {

						$metas[$key]['meta_value'] = unserialize($meta['meta_value']);

					} else {

						$metas[$key]['meta_value'] = $meta['meta_value'];
					}

					// New meta object
					$meta_object->{$metas[$key]['meta_key']} = $metas[$key]['meta_value'];
				}
			}

			return $meta_object;
		}

		// Delete
		public function db_delete() {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			global $wpdb;

			// Delete meta
			$sql = sprintf("DELETE FROM %s WHERE id = %u;", self::db_get_table_name(), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error deleting meta', 'ws-form')); }
		}

		// Delete all meta in object
		public function db_delete_by_object() {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			global $wpdb;

			$sql = sprintf("DELETE FROM %s WHERE parent_id = %u;", self::db_get_table_name(), $this->parent_id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error deleting object meta', 'ws-form')); }
		}

		// Clone - All
		public function db_clone_all($parent_id_copy_to) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			$meta_keys = WS_Form_Config::get_meta_keys();

			global $wpdb;

			$sql = sprintf("SELECT %s FROM %s WHERE parent_id = %u", self::DB_SELECT, self::db_get_table_name(), $this->parent_id);
			$metas = $wpdb->get_results($sql, 'ARRAY_A');

			if($metas) {

				foreach($metas as $key => $meta) {

					// Read data required for copying
					$this->parent_id = $parent_id_copy_to;
					$this->meta_key = $meta['meta_key'];
					$this->meta_value = $meta['meta_value'];

					// Check to see if we have config for this meta data
					if(isset($meta_keys[$this->meta_key])) {

						// If this meta is a required setting, set it to default value
						$meta_key_config = $meta_keys[$this->meta_key];
						$required_setting = isset($meta_key_config['required_setting']) ? $meta_key_config['required_setting'] : false;
						$default_value = isset($meta_key_config['default']) ? $meta_key_config['default'] : '';
						$this->meta_value = ($required_setting) ? $default_value : $meta['meta_value'];
					}

					self::db_clone();
				}
			}
		}

		// Clone
		public function db_clone() {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			global $wpdb;

			// Clone group
			$sql = sprintf("INSERT INTO %s (%s) VALUES ('%s', '%s', %u);", self::db_get_table_name(), self::DB_INSERT, esc_sql($this->meta_key), esc_sql($this->meta_value), $this->parent_id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error cloning meta', 'ws-form')); }

			// Get new group ID
			$object_id = $wpdb->insert_id;

			return $object_id;
		}

		// Get meta data
		public function db_get_object_meta($meta_key, $meta_value = '', $create = false) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			if($this->parent_id <= 0) { parent::db_throw_error(__('Parent ID not set', 'ws-form')); }

			// Load all the object meta data
			if(!$this->object_meta) { $this->object_meta = self::db_read_all(); }

			// If the meta_key is found, return it
			if(isset($this->object_meta->{$meta_key})) {

				// Found the meta key in the database, so return it
				$meta_value = $this->object_meta->{$meta_key};

				// Check for arrays - Legacy support
				if(is_array($meta_value)) {

					$meta_value = json_decode(json_encode($meta_value));
				}

				return $meta_value;

			} else {

				// Not found

				// Create meta key / value in database?
				if($create) {

					// Check for arrays - Legacy support
					if(is_array($meta_value)) {

						$meta_value = json_decode(json_encode($meta_value));
					}

					$meta_data = [];
					$meta_data[$meta_key] = $meta_value;
					self::db_update_from_array($meta_data);
				}

				return $meta_value;
			}
		}

		// Add meta data from object (Meta data is stored as an object by default to allow for JSON transfer)
		public function db_update_from_object($meta_data_object) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			self::db_update_from_array((array)$meta_data_object);
		}

		// Add meta data from array
		public function db_update_from_array($meta_data_array, $lookups = false) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			if($this->parent_id <= 0) { parent::db_throw_error(__('Parent ID not set', 'ws-form')); }
			if(!is_array($meta_data_array)) { return true; }							// Empty data
			if(count($meta_data_array) === 0) { return true; }							// Empty data

			foreach($meta_data_array as $key => $value) {

				// Serialize arrays
				if(is_array($value) || is_object($value)) { $value = serialize($value); }

				// Field lookup for ecommerce_field_id (Auto mapping)
				if(
					($lookups !== false) &&
					($key == 'ecommerce_field_id') &&
					($value != '') &&
					(substr($value, 0, 7) == 'lookup_')
				) {

					$field_id = substr($value, 7);
					$value = isset($lookups[$field_id]) ? $lookups[$field_id] : '';
				}

				// Build meta data
				$meta_data = array('parent_id' => $this->parent_id, 'meta_key' => $key, 'meta_value' => $value);

				global $wpdb;

				// Get ID of existing meta record
				$sql = sprintf("SELECT id FROM %s WHERE parent_id = %u AND meta_key = '%s' LIMIT 1", self::db_get_table_name(), $this->parent_id, $key);
				$id = $wpdb->get_var($sql);
				if($id) {

					// Existing
					$meta_data['id'] = $id;
				}

				// Replace
				$replace_count = $wpdb->replace(self::db_get_table_name(), $meta_data);
				if($replace_count === false) {

					parent::db_throw_error(__('Unable to replace meta data', 'ws-form') . ': ' . $this->object);
				}
			}

			return true;
		}
	}