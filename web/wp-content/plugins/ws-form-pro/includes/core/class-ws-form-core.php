<?php

	class WS_Form_Core {

		// Get SET SQL from data array (key => value pairs) for each field in $fields
		public function get_wpdb_data($fields, $object, $insert = false) {

			global $wpdb;

			$data = array();
			$format = array();

			foreach($fields as $field) {

				if(!is_null($field)) { $field = trim($field); }
				$value = '';
				$format_set = false;

				// Set value if found
				if(property_exists($object, $field)) { $value = $object->{$field}; }

				// Check for arrays
				if(is_array($value)) { $value = serialize($value); }

				switch($field) {

					case 'parent_section_id' :

						if($insert) { $value = 0; }
						$format_set = '%d';
						break;

					case 'child_count' :

						if($insert) { $value = 0; }
						$format_set = '%d';
						break;

					case 'date_added' :

						if($insert) { $value = WS_Form_Common::get_mysql_date(); }
						break;

					case 'date_updated' :

						$value = WS_Form_Common::get_mysql_date();
						break;

					case 'user_id' :

						$format_set = '%d';
						break;

					case 'sort_index' :

						$format_set = '%d';
						break;

					case 'form_id' :

						if($insert) { $value = $this->form_id; }
						$format_set = '%d';
						break;

					case 'group_id' :

						if($insert) { $value = $this->group_id; }
						$format_set = '%d';
						break;

					case 'section_id' :

						if($insert) { $value = $this->section_id; }
						$format_set = '%d';
						break;

					case 'spam_level' :

						$format_set = '%d';
						break;
				}

				// Check for null values
				if(is_null($value)) { $format_set = null; }

				$data[$field] = $value;
				$format[] = ($format_set !== false) ? $format_set : '%s';
			}

			$return_array = array('data' => $data, 'format' => $format);

			return $return_array;
		}

		// Update (then Insert on fail) an object
		public function db_update_insert($table_name, $fields_update, $fields_insert, $object, $object_type, $id = 0, $insert = true) {

			global $wpdb;

			// See if ID already exists
			if(is_null($wpdb->get_var("SELECT id FROM $table_name WHERE id = $id LIMIT 1"))) {

				// Get wpdb insert data and format
				$wpdb_insert_array = self::get_wpdb_data(explode(',', $fields_insert), $object, true);

				// Set ID (Ensure ID is set back correctly)
				if(isset($object->id) && !isset($wpdb_insert_array['data']['id'])) {

					$wpdb_insert_array['data']['id'] = $object->id;
					$wpdb_insert_array['format'][] = '%d';
				}

				// Truncate label
				if(
					isset($wpdb_insert_array['data']['label']) &&
					(strlen($wpdb_insert_array['data']['label']) > WS_FORM_FIELD_LABEL_MAX_LENGTH)
				) {

					$wpdb_insert_array['data']['label'] = substr($wpdb_insert_array['data']['label'], 0, WS_FORM_FIELD_LABEL_MAX_LENGTH);
				}

				// Insert
				$insert_count = $wpdb->insert($table_name, $wpdb_insert_array['data'], $wpdb_insert_array['format']);
				if($insert_count === false) {

					self::db_throw_error(__('Unable to insert', 'ws-form') . ' ' . $object_type);
				}

				return $wpdb->insert_id;

			} else {

				// Get wpdb update data and format
				$wpdb_update_array = self::get_wpdb_data(explode(',', $fields_update), $object, false);

				// Update
				$update_count = $wpdb->update($table_name, $wpdb_update_array['data'], array('id' => $id), $wpdb_update_array['format'], array('%d'));
				if($update_count === false) {

					self::db_throw_error(__('Unable to update', 'ws-form') . ' ' . $object_type);
				}

				return $id;
			}
		}

		// Object sort index processing
		public function db_object_sort_index($table_name, $parent_field, $parent_id, $next_sibling_id) {

			global $wpdb;

			// Get current parent_id
			$sql = sprintf("SELECT %s FROM %s WHERE id = %u LIMIT 1;", $parent_field, $table_name, $parent_id);
			$parent_id_old = $wpdb->get_var($sql);

			// Get new sort index
			$sort_index = self::db_object_sort_index_get($table_name, $parent_field, $parent_id, $next_sibling_id);

			// Update sort index
			$sql = sprintf("UPDATE %s SET %s = %u, sort_index = %u WHERE id = %u;", $table_name, $parent_field, $parent_id, $sort_index, $this->id);
			if($wpdb->query($sql) === false) { self::db_throw_error(__('Error adjusting sort index', 'ws-form')); }

			// Clean up sort indexes
			self::db_object_sort_index_clean($table_name, $parent_field, $parent_id);

			if($parent_id != $parent_id_old) {

				// Clean up sort indexes of old parent
				self::db_object_sort_index_clean($table_name, $parent_field, $parent_id_old);
			}

			return $sort_index;
		}

		// Clean object sort indexes
		public function db_object_sort_index_clean($table_name, $parent_field, $parent_id) {

			global $wpdb;

			// Clean up sort indexes
			$wpdb->query('SET @i := 0;');
			$sql = sprintf("UPDATE %s SET sort_index = (@i := @i + 1) WHERE %s = %u ORDER BY sort_index;", $table_name, $parent_field, $parent_id);
			if($wpdb->query($sql) === false) { self::db_throw_error(__('Error tidying sort index', 'ws-form')); }
		}

		// Get next sort index
		public function db_object_sort_index_get($table_name, $parent_field, $parent_id, $next_sibling_id = 0) {

			global $wpdb;

			// Work out sort index
			if($next_sibling_id == 0) {

				// Get next sort_index
				$sql = sprintf("SELECT IFNULL(MAX(sort_index), 0) FROM %s WHERE %s = %u;", $table_name, $parent_field, $parent_id);
				$sort_index = $wpdb->get_var($sql) + 1;
				if(is_null($sort_index)) { self::db_throw_error(__('Unable to determine sort index', 'ws-form')); }

			} else {

				// Adopt sort_index of next sibling
				$sql = sprintf("SELECT sort_index FROM %s WHERE id = %u LIMIT 1;", $table_name, $next_sibling_id);
				$sort_index = $wpdb->get_var($sql);
				if(is_null($sort_index)) { self::db_throw_error('Unable to determine sort index'); }

				// Increment records below and including current sort index
				$sql = sprintf("UPDATE %s SET sort_index = (sort_index + 1) WHERE %s = %u AND sort_index >= %u;", $table_name, $parent_field, $parent_id, $sort_index);
				if($wpdb->query($sql) === false) { self::db_throw_error(__('Error adjusting sort indexes', 'ws-form')); }
			}

			return $sort_index;
		}

		// Get object label
		public function db_object_get_label($table_name, $object_id) {

			global $wpdb;

			if($object_id == 0) { self::db_throw_error(__('Object ID is zero, cannot get label', 'ws-form')); }

			$sql = sprintf("SELECT label FROM %s WHERE id = %u LIMIT 1;", $table_name, $object_id);
			$object_label = $wpdb->get_var($sql);
			if($object_label === false) { self::db_throw_error(__('Error getting object label', 'ws-form')); }

			return $object_label;
		}

		// Build meta data for an object
		public function build_meta_data($meta_data, $meta_keys, $meta_values = false) {

			$return_array = [];

			foreach($meta_data as $key => $value) {

				if(is_array($value)) {

					if($key === 'meta_keys') {

						foreach($value as $meta_key) {

							if(!(isset($meta_keys[$meta_key]['dummy']) && $meta_keys[$meta_key]['dummy'] == true)) {

								if(isset($meta_keys[$meta_key]['default'])) {

									// Check default value for variables
									if(
										($meta_values !== false) &&
										is_string($meta_keys[$meta_key]['default']) &&
										(strpos($meta_keys[$meta_key]['default'], '#') !== false)
									) {

										$meta_keys[$meta_key]['default'] = WS_Form_Common::mask_parse($meta_keys[$meta_key]['default'], $meta_values);
									}

									$meta_value = $meta_keys[$meta_key]['default'];

								} else {

									$meta_value = '';
								}

								// Handle boolean values
								$meta_value = is_bool($meta_value) ? ($meta_value ? 'on' : '') : $meta_value;

								// Handle key changes
								if(isset($meta_keys[$meta_key]['key'])) {

									$meta_key = $meta_keys[$meta_key]['key'];
								}

								// Add to return array
								$return_array[$meta_key] = $meta_value;
							}
						}

					} else {

						// Follow
						$return_array = array_merge($return_array, self::build_meta_data($value, $meta_keys, $meta_values));
					}
				}
			}

			return $return_array;
		}

		// Throw error
		public function db_throw_error($error) {
			
			throw new Exception($error);
		}
	}
