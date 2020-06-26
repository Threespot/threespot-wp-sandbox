<?php

	class WS_Form_Migrate extends WS_Form_Core {

		public $id;
		public $config;
		public $process;
		public $sub_meta_key;

		public $fields;

		public $debug;
		public $create_actions;
		public $return_scratch_global;
		public $id_to_sub_meta_keys;

		public function __construct() {

			// User capability check
			if(!WS_Form_Common::can_user('create_form')) { return false; }

			$this->config = self::get_migrate();
			$this->process = false;
			$this->sub_meta_key = false;

			// Get field meta data
			$this->fields = WS_Form_Config::get_field_types_flat(true);

			// Debug
			$this->debug = false;
			$this->create_actions = true;
			$this->return_scratch_global = array();
			$this->id_to_sub_meta_keys = array();
		}

		public function get_migrations() {

			$migrations = array();
			$select = true;

			foreach($this->config as $id => $config) {

				// Check if plug-in is active
				$active = self::is_active($id);

				// Check if form data exists
				$data = self::is_data($id);

				$migration_single = array(

					'id'		=> $id,
					'label'		=> $config['label'],
					'version'	=> $config['version'],
					'active'	=> $active,
					'data'		=> $data,
					'select'	=> ($data && $select)
				);
				if($data) { $select = false; }

				$migrations[$id] = $migration_single;
			}

			return $migrations;
		}

		public function is_active($id) {

			// Get config
			if(!isset($this->config[$id])) { return false; }
			$config = $this->config[$id];

			foreach($config['detect'] as $plugin) {

				if(is_plugin_active($plugin)) { return true; break; }
			}

			return false;
		}

		public function is_data($id) {

			global $wpdb;

			// Get config
			if(!isset($this->config[$id])) { return false; }
			$config = $this->config[$id];

			$tables_found = true;

			foreach($config['detect_table'] as $table) {

				$table_name = $wpdb->prefix . $table;
				if(!$wpdb->get_var("SHOW TABLES LIKE '$table_name'")) { $tables_found = false; break; }
			}

			return $tables_found;
		}

		public function get_forms() {

			self::db_check_id();

			$forms = self::get_data('form');
		}

		public function get_submission_meta_key($id) {

			$meta_key_mask = $this->config[$this->id]['submission']['table_metadata']['meta_key_mask'];

			$meta_key_lookups = array('id' => $id);

			return WS_Form_Common::mask_parse($meta_key_mask, $meta_key_lookups);
		}

		public function get_process($form_id, $id) {

			if($this->process === false) {

				// Build process array
				$this->process = array();

				// Get source fields
				$get_data_return = self::get_data('field', $form_id);
				$fields_source = $get_data_return['data'];

				// Types
				$types = $this->config[$this->id]['field']['process'];

				foreach($fields_source as $field_id_source => $field_config) {

					if(!isset($field_config['type'])) { continue; }
					$field_type = $field_config['type'];

					if(!isset($types[$field_type])) { continue; }
					$this->process[$field_id_source] = $types[$field_type];
				}
			}

			return isset($this->process[$id]) ? $this->process[$id] : false;
		}

		public function get_data($part, $form_id = false, $offset = false, $meta_map = false, $count = false) {

			$this->debug("*** get_data $part start ***");

			global $wpdb;

			self::db_check_id();

			// Check it is active
			if(!self::is_data($this->id)) { return false; }

			// Get config
			if(!isset($this->config[$this->id])) { return false; }
			$plugin_import_config = $this->config[$this->id];

			// Part config
			if(!isset($plugin_import_config[$part])) { return false; }
			$part_config = $plugin_import_config[$part];

			// Return array
			$data = array();

			// Counters
			$records_processed = 0;
			$sort_index_group = 0;
			$sort_index_section = 0;
			$sort_index_field = 0;

			$create_group = true;
			$create_section = true;
			$records_since_create_group = 0;

			// Build mask values
			$mask_record_lookups = array(

				'table_prefix'	=> $wpdb->prefix,
				'form_id'		=> $form_id
			);

			// Build SQL records
			$table_record			= $part_config['table_record'];

			// Get base lookups
			$plugin_variables = isset($plugin_import_config['plugin_variables']) ? $plugin_import_config['plugin_variables'] : array();

			$limit = isset($table_record['limit']) ? $table_record['limit'] : false;

			$sql_record_count		= WS_Form_Common::mask_parse($table_record['count'], $mask_record_lookups);
			$sql_record_select 		= WS_Form_Common::mask_parse($table_record['select'], $mask_record_lookups);
			$sql_record_from 		= WS_Form_Common::mask_parse($table_record['from'], $mask_record_lookups);
			$sql_record_join 		= WS_Form_Common::mask_parse($table_record['join'], $mask_record_lookups);
			$sql_record_where 		= ((isset($table_record['where']) && ($table_record['where'] != '')) ? ' WHERE ' . WS_Form_Common::mask_parse($table_record['where'], $mask_record_lookups) : '');
			$sql_record_where		.= (($part == 'form') && ($form_id == false)) ? '' : (($sql_record_where == '') ? ' WHERE ' : '') . ((isset($table_record['where_single']) && ($table_record['where_single'] != '')) ? WS_Form_Common::mask_parse($table_record['where_single'], $mask_record_lookups) : '');
			$sql_record_order_by	= (isset($table_record['order_by']) && ($table_record['order_by'] != '')) ? ' ORDER BY ' . WS_Form_Common::mask_parse($table_record['order_by'], $mask_record_lookups) : '';
			$sql_record_limit		= ($limit !== false) ? ' LIMIT ' . $limit : '';
			$sql_record_offset		= ($offset !== false) ? ' OFFSET ' . $offset : '';

			// Build SQL masks
			if(isset($part_config['table_metadata'])) {

				$table_metadata 			= $part_config['table_metadata'];

				$sql_metadata_mask_select 	= $table_metadata['select'];
				$sql_metadata_mask_from 	= $table_metadata['from'];
				$sql_metadata_mask_join 	= $table_metadata['join'];
				$sql_metadata_mask_where 	= (isset($table_metadata['where']) && $table_metadata['where'] != '') ? ' WHERE ' . $table_metadata['where'] : '';

				// Set custom meta map
				if($meta_map !== false) { $table_metadata['map'] = $meta_map; }

			} else {

				$table_metadata = false;
			}

			// Retrieve table records
			if($count) {

				// Get record count
				$sql = "SELECT COUNT($sql_record_count) as records_total FROM $sql_record_from$sql_record_join$sql_record_where;";
				$this->debug("SQL: $sql");
				$records_total = $wpdb->get_var($sql);
				return is_null($records_total) ? 0 : $records_total;

			} else {

				// Get records
				$sql = "SELECT SQL_CALC_FOUND_ROWS $sql_record_select FROM $sql_record_from$sql_record_join$sql_record_where$sql_record_order_by$sql_record_limit$sql_record_offset;";
				$this->debug("SQL: $sql");
				$table_records = $wpdb->get_results($sql, 'ARRAY_A');
			}

			// Counters
			$records_total = intval($wpdb->get_var('SELECT FOUND_ROWS()'));

			// Actions
			$return_actions = array();

			// Groups
			$return_group_index = false;

			// Sections
			$return_section_index = false;

			$table_records_base = $table_records;

			// SQL Transpose
			if(isset($table_record['sql_transpose'])) {

				$table_records = $table_records[0];

				$sql_transpose = $table_record['sql_transpose'];

				$data_field = $sql_transpose['data_field'];
				$data_format = $sql_transpose['data_format'];

				if(!isset($table_records[$data_field])) { return false; }
				$table_records = $table_records[$data_field];

				switch($data_format) {

					case 'serialize' :

						if(($table_records = @unserialize($table_records)) === false) { return false; }
						break;

					case 'json' :

						if(is_null($table_records = @json_decode($table_records))) { return false; }
						break;

					default :

						return false;
				}

				if(is_object($table_records)) { $table_records = json_decode(json_encode($table_records), true); }

				$table_records_base = $table_records;

				if(isset($sql_transpose['data_sub_field'])) {

					$table_records = $table_records[$sql_transpose['data_sub_field']];
				}
			}

			$return_group_next = false;
			$return_single_next = false;

			$table_record_process = true;

			// Blank form
			$return_single = array();
			$return_single['group_id'] = 0;
			$return_single['section_id'] = 0;

			do {

				$table_record_key = key($table_records);
				$table_record_read = current($table_records);
				if($table_record_read === false) { $table_record_process = false; break; }
				next($table_records);

				// Build record array
				$return_single = array();
				$return_single['meta'] = array();
				$return_single['scratch'] = array();
				$return_lookups = array();
				$return_lookups['group_id'] = ($return_group_index === false) ? 0 : $return_group_index;
				$return_lookups['section_id'] = ($return_section_index === false) ? 0 : $return_section_index;

				// Regular mapping
				if(isset($table_record['map'])) {

					foreach($table_record['map'] as $map) {

						// Get source
						if(isset($map['source'])) {

							// Get meta data
							$source = $map['source'];
							$meta_value = (isset($table_record_read[$source])) ? $table_record_read[$source] : '';

						} else {

							// No source specified
							$meta_value = '';
						}

						// Process map
						self::debug("- Map Process");
						$map_process_return = self::map_process($return_single, $return_lookups, $return_actions, $return_group_index, $create_group, $records_since_create_group, $return_section_index, $create_section, $map, $meta_value, $table_records_base, $table_records, $table_record_key, $part_config, $source, $plugin_variables);
						self::debug("- Map Process - End");
						if($map_process_return === false) { $this->debug('Stop processing map'); break; }
					}
					if($map_process_return === false) { continue; }
				}

				// Mapping by type
				if(
					isset($return_lookups['type_source']) &&
					isset($table_record['map_by_type']) &&
					isset($table_record['map_by_type'][$return_lookups['type_source']])
				) {

					foreach($table_record['map_by_type'][$return_lookups['type_source']] as $map) {

						// Get source
						if(isset($map['source'])) {

							// Get meta data
							$source = $map['source'];
							$meta_value = (isset($table_record_read[$source])) ? $table_record_read[$source] : '';

						} else {

							// No source specified
							$meta_value = '';
						}

						// Process map
						self::debug("- Map Process (By Type: " . $return_lookups['type_source'] . ")");
						$map_process_return = self::map_process($return_single, $return_lookups, $return_actions, $return_group_index, $create_group, $records_since_create_group, $return_section_index, $create_section, $map, $meta_value, $table_records_base, $table_records, $table_record_key, $part_config, $source, $plugin_variables);
						self::debug("- Map Process (By Type: " . $return_lookups['type_source'] . ") - End");
						if($map_process_return === false) { $this->debug('Stop processing map'); break; }
					}
					if($map_process_return === false) { continue; }
				}

				if($table_metadata !== false) {

					$this->debug('Processing table meta data');

					$partial_meta_key_mask = isset($part_config['partial_meta_key_mask']) ? $part_config['partial_meta_key_mask'] : '#meta_key.#partial_index';

					// Build SQL parts
					$mask_metadata_lookups	= array(

						'table_prefix'	=> $wpdb->prefix,
						'record_id'		=> $return_single['id'],
						'form_id'		=> $form_id
					);

					$sql_metadata_select 	= WS_Form_Common::mask_parse($sql_metadata_mask_select, $mask_metadata_lookups);
					$sql_metadata_from 		= WS_Form_Common::mask_parse($sql_metadata_mask_from, $mask_metadata_lookups);
					$sql_metadata_join 		= WS_Form_Common::mask_parse($sql_metadata_mask_join, $mask_metadata_lookups);
					$sql_metadata_where 	= WS_Form_Common::mask_parse($sql_metadata_mask_where, $mask_metadata_lookups);

					// Retrieve record meta data
					$sql = "SELECT $sql_metadata_select FROM $sql_metadata_from$sql_metadata_join$sql_metadata_where;";
					$this->debug("SQL: $sql");
					$meta_records = $wpdb->get_results($sql, 'ARRAY_A');

					// SQL Transpose
					if(isset($table_metadata['sql_transpose'])) {

						$meta_records = $meta_records[0];

						$sql_transpose = $table_metadata['sql_transpose'];

						$data_field = $sql_transpose['data_field'];
						$data_format = $sql_transpose['data_format'];

						if(!isset($meta_records[$data_field])) { return false; }
						$meta_records = $meta_records[$data_field];

						switch($data_format) {

							case 'serialize' :

								if(($meta_records = @unserialize($meta_records)) === false) { return false; }
								break;

							case 'json' :

								if(is_null($meta_records = @json_decode($meta_records))) { return false; }
								break;

							default :

								return false;
						}

						if(is_object($meta_records)) { $meta_records = json_decode(json_encode($meta_records), true); }

						$meta_records_base = $meta_records;

						if(isset($sql_transpose['data_sub_field'])) {

							$meta_records = $meta_records[$sql_transpose['data_sub_field']];
						}
					}

					$meta_key_key = (isset($table_metadata['meta_key'])) ? $table_metadata['meta_key'] : 'meta_key';
					$meta_value_key = (isset($table_metadata['meta_value'])) ? $table_metadata['meta_value'] : 'meta_value';

					// Expand data
					if(count($this->id_to_sub_meta_keys) > 0) {

						$meta_records_new = array();

						foreach($meta_records as $key => $record) {

							if(is_object($record)) { $record = json_decode(json_encode($record), true); }

							$meta_key = isset($record[$meta_key_key]) ? $record[$meta_key_key] : $key;

							if(isset($this->id_to_sub_meta_keys[$meta_key])) {

								foreach($this->id_to_sub_meta_keys[$meta_key] as $sub_key => $sub_meta_key) {

									$meta_record = $record;
									$meta_record[$meta_key_key] = $sub_key;
									$meta_record[$meta_value_key] = isset($record[$sub_meta_key]) ? $record[$sub_meta_key] : $record[$meta_value_key];

									$meta_records_new[$sub_key] = $meta_record;
								}

							} else {

								$meta_records_new[(string) $key] = $record;
							}
						}

						$meta_records = $meta_records_new;
					}

					foreach($meta_records as $meta_record_key => $meta_record) {

						$this->debug("Processing meta key: $meta_record_key");
						$this->debug("Processing meta data: " . print_r($meta_record, true));

						// Get meta data
						$meta_key = isset($meta_record[$meta_key_key]) ? $meta_record[$meta_key_key] : false;
						$meta_value = isset($meta_record[$meta_value_key]) ? $meta_record[$meta_value_key] : '';

						// Build meta array
						if($meta_key !== false) {

							// Meta mapping type
							foreach($table_metadata['map'] as $map) {

								// Get source
								$source = $map['source'];

								// Build partial key
								$partial_meta_key_lookups = array(

									'meta_key' => $source,
									'partial_index' => 1
								);
								$partial_meta_key = WS_Form_Common::mask_parse($partial_meta_key_mask, $partial_meta_key_lookups);

								if(($source == $meta_key) || ($partial_meta_key == $meta_key)) {

									// Process map
									self::map_process($return_single, $return_lookups, $return_actions, $return_group_index, $create_group, $records_since_create_group, $return_section_index, $create_section, $map, $meta_value, $table_records_base, $meta_records, $meta_record_key, $part_config, $source, $plugin_variables, $meta_key_key, $meta_value_key);
								}
							}
						}
					}
				}

				self::debug("- $part data after map processing: " . print_r($return_single, true));
				self::debug("- $part lookups after map processing: " . print_r($return_lookups, true));

				// Check for actions
				if(
					($part == 'form') &&
					($form_id !== false) &&
					(isset($part_config['action'])) &&
					$this->create_actions
				) {

					$form_action_index = 0;

					// Get meta keys
					$meta_keys = WS_Form_Config::get_meta_keys();

					// Get add form actions
					$meta_action = $meta_keys['action']['default'];

					foreach($part_config['action'] as $part_config_id => $part_config_action) {

						// Should we set this action up?
						if(!isset($part_config_action['force'])) {

							if(!isset($return_actions[$part_config_id])) { continue; }
							if(!$return_actions[$part_config_id]) { continue; }
						}

						$action_id = $part_config_action['action_id'];

						// If action is not installed and active, skip
						if(!isset(WS_Form_Action::$actions[$action_id])) { continue; }

						// Get action
						$action = WS_Form_Action::$actions[$action_id];

						// Process custom meta data
						$metas = isset($part_config_action['meta']) ? $part_config_action['meta'] : array();
						$action_meta_lookups = array();

						foreach($metas as $meta_key => $meta_value) {

							if(is_array($meta_value)) {

								// Repeaters
								foreach($meta_value as $repeater_index => $repeater_row) {

									foreach($repeater_row as $key => $value) {

										$meta_value[$repeater_index][$key] = WS_Form_Common::mask_parse($value, $return_lookups);
									}
								}

							} else {

								$meta_value = WS_Form_Common::mask_parse($meta_value, $return_lookups);
							}

							$action_meta_lookups[$meta_key] = $meta_value;
						}

						// Add action
						$meta_action['groups'][0]['rows'][] = WS_Form_Action::update_form_action($form_action_index++, $action_id, $action_meta_lookups);
					}

					$return_single['meta']['action'] = $meta_action;
				}

				// Add to ID to type source lookups
				if(
					isset($return_lookups['id']) &&
					isset($return_lookups['submission_sub_meta_key']) &&
					($return_lookups['submission_sub_meta_key'] != '')
				) {

					if(!isset($this->id_to_sub_meta_keys[$return_lookups['submission_id_source']])) { $this->id_to_sub_meta_keys[$return_lookups['submission_id_source']] = array(); }
					$this->id_to_sub_meta_keys[$return_lookups['submission_id_source']][$return_lookups['id']] = $return_lookups['submission_sub_meta_key'];
				}

				// Delete scratch
				unset($return_single['scratch']);

				$this->debug("records_since_create_group: $records_since_create_group");

				if(!$create_group && !$create_section) { $records_since_create_group++; }

				$group_created = false;
				if($create_group && ($part !== 'form')) {

					self::debug("- create_group true");

					if($return_group_index === false) { $return_group_index = 0; } else { $return_group_index++; };
					$return_single['group_id'] = $return_group_index;
					$return_lookups['group_id'] = $return_group_index;
					self::debug("- Group ID is now: " . $return_group_index);

					if($part == 'group') {

						self::debug("- Injecting Group");

						// Build group data
						if(!isset($return_lookups['label'])) {

							$return_lookups['label'] = self::data_lookup($part_config, 'label_group', $table_records_base, $return_lookups);
						}
						if(!isset($return_lookups['class_group_wrapper'])) {

							$return_lookups['class_group_wrapper'] = '';
						}

						$data[$return_group_index] = array(

							'id'			=>	$return_group_index,
							'label'			=>	($return_lookups['label'] != '') ? $return_lookups['label'] : WS_FORM_DEFAULT_GROUP_NAME,
							'meta'			=>	array(

								'class_group_wrapper' => $return_lookups['class_group_wrapper']
							),
							'sort_index' 	=> $sort_index_group++
						);

						self::debug(" - Injected group data at index $return_group_index: " . print_r($data[$return_group_index], true));
					}

					// Reset sort index
					$sort_index_section = 0;
					$sort_index_field = 0;

					$create_group = false;
					$create_section = true;
					$records_since_create_group = 0;
					$group_created = true;
				}

				$return_single['group_id'] = $return_group_index;

				if($create_section && ($part !== 'form')) {

					self::debug("- create_section true");

					if($group_created || ($records_since_create_group > 0)) {

						if($return_section_index === false) { $return_section_index = 0; } else { $return_section_index++; };
						$return_single['section_id'] = $return_section_index;
						$return_lookups['section_id'] = $return_section_index;
						self::debug("- Section ID is now: " . $return_section_index);
					}

					if($part == 'section') {

						self::debug("- Injecting Section");

						// Build section data
						if(!isset($return_lookups['label'])) {

							$return_lookups['label'] = self::data_lookup($part_config, 'label_section', $table_records_base, $return_lookups);
						}
						if(!isset($return_lookups['class_section_wrapper'])) {

							$return_lookups['class_section_wrapper'] = '';
						}

						$data[$return_section_index] = array(

							'id'			=>	$return_section_index,
							'label'			=>	($return_lookups['label'] != '') ? $return_lookups['label'] : WS_FORM_DEFAULT_SECTION_NAME,
							'group_id'		=>	$return_single['group_id'],
							'meta'			=>	array(

								'class_section_wrapper' => $return_lookups['class_section_wrapper']
							),
							'sort_index'	=>	$sort_index_section++
						);

						self::debug(" - Injected section data at index $return_section_index: " . print_r($data[$return_section_index], true));
					}

					// Reset sort index
					$sort_index_field = 0;

					$create_section = false;
				}

				$return_single['section_id'] = $return_section_index;

				// Add to return array
				if(
					isset($return_single['id']) &&
					(
						(($part == 'field') && ($return_single['type'] !== false)) ||
						($part != 'field')
					)
				) {

					// Ensure sort index is set
					if(!isset($return_single['sort_index'])) {

						$return_single['sort_index'] = $sort_index_field++;
					}

					$data_index = false;

					switch($part) {

						case 'group' :
						case 'section' :

							next($table_records);
							break;

						default :

							$data_index = $return_single['id'];
					}

					if($data_index !== false) {

						self::debug("- Adding $part Record");
						self::debug("- Record data: " . print_r($return_single, true));

						$data[$data_index] = $return_single;
					}
				}

				$records_processed++;

				self::debug("- END record processing for $part\n");

			} while ($table_record_process);

			// Add extra records
			$extra_id = 0;
			$table_record_append = isset($part_config['table_record_append']) ? $part_config['table_record_append'] : false;
			if($table_record_append !== false) {

				$sort_index = isset($return_single['sort_index']) ? ($return_single['sort_index'] + 1) : $sort_index_field;

				foreach($table_record_append as $table_record) {

					// Conditional
					if(isset($table_record['condition'])) {

						if(!self::condition_process($table_record, $this->return_scratch_global)) { continue; }
					}

					// Parse with global variables
					foreach($table_record as $key => $value) {

						$table_record[$key] = WS_Form_Common::mask_parse($value, $this->return_scratch_global);
					}

					$id = 'form_' . $extra_id++;

					// Add mandatory attributes
					$table_record['id'] = $id;
					$table_record['group_id'] = $return_single['group_id'];
					$table_record['section_id'] = $return_single['section_id'];
					$table_record['sort_index'] = $sort_index;

					// Add to data
					$data[$id] = $table_record;

					$sort_index++;
				}
			}

			// Calculate return values
			$records_total_processed = $offset + $records_processed;
			$records_progress = ($records_total > 0) ? (($records_total_processed / $records_total) * 100) : 0;
			if($records_progress > 100) { $records_progress = 100; }
			$records_remaining = ($records_total_processed < $records_total);

			return array('data' => $data, 'total' => $records_total, 'progress' => $records_progress, 'remaining' => $records_remaining, 'offset' => $records_total_processed);
		}

		// Process map
		public function map_process(&$return_single, &$return_lookups, &$return_actions, &$return_group_index, &$create_group, &$records_since_create_group, &$return_section_index, &$create_section, $map, $meta_value, $table_records_base, &$table_records, &$table_record_key, $part_config, $source, $plugin_variables, $meta_key_key = false, $meta_value_key = false) {

			$this->debug('Processing map: ' . print_r($map, true));

			// Add meta_value to lookups
			if(!is_array($meta_value)) {

				// Trim
				$meta_value = trim($meta_value);

				$return_lookups['meta_value'] = $meta_value;
			}

			// Condition
			if(isset($map['condition'])) {

				if(!self::condition_process($map, $return_lookups)) { return; }
			}

			// Default
			if(isset($map['default'])) {

				if($meta_value == '') {

					if(
						($map['default'] == '#field_label_default') &&
						(isset($return_single['type'])) &&
						(isset($this->fields[$return_single['type']]))
					) {

						$return_lookups['field_label_default'] = $this->fields[$return_single['type']]['label'];
					}

					$meta_value = WS_Form_Common::mask_parse($map['default'], $return_lookups);
				}
			}

			// Data lookup
			if(isset($map['data_lookup'])) {

				$lookup_id = $map['data_lookup'];

				$this->debug('Processing map data lookup: ' . $lookup_id);

				$meta_value = self::data_lookup($part_config, $lookup_id, $table_records_base, $return_lookups);
			}

			// Add group
			if(isset($map['group'])) {

				self::debug("- map[group] found");

				$create_group = true;
				$create_section = true;
				unset($return_single['id']);

				return;
			}

			// Add section
			if(isset($map['section'])) {

				self::debug("- map[section] found");

				$create_section = true;
				unset($return_single['id']);

				return;
			}

			// Value
			if(isset($map['records_to_lookups']) && is_array($meta_value)) {

				foreach($meta_value as $key => $record) {

					$records_to_lookups_prefix = false;

					foreach($record as $key => $value) {

						if($key == $map['records_to_lookups']) { $records_to_lookups_prefix = $value . '_'; }
					}

					if($records_to_lookups_prefix !== false) {

						foreach($record as $key => $value) {

							$return_lookups[$records_to_lookups_prefix . $key] = $value;
						}
					}
				}
			}

			// Value
			if(isset($map['value'])) {

				$meta_value = WS_Form_Common::mask_parse($map['value'], $return_lookups);
				$meta_value = WS_Form_Common::mask_parse($meta_value, $this->return_scratch_global);

				// Data grid parsing
				if(is_array($meta_value)) {

					if(
						isset($meta_value['groups'])
					) {

						foreach($meta_value['groups'] as $datagrid_group_index => $datagrid_group) {

							foreach($datagrid_group['rows'] as $datagrid_row_index => $datagrid_row) {

								foreach($datagrid_row['data'] as $datagrid_data_index => $datagrid_data) {

									$meta_value['groups'][$datagrid_group_index]['rows'][$datagrid_row_index]['data'][$datagrid_data_index] = WS_Form_Common::mask_parse($datagrid_data, $return_lookups);
								}
							}
						}
					}
				}
			}

			// Value
			if(isset($map['value_option'])) {

				$meta_value = get_option($map['value_option'], '');
			}

			// Process
			if(isset($map['process'])) {

				$this->debug('Running processes');

				foreach($map['process'] as $processes) {

					$process = $processes['process'];

					$this->debug('Running process: ' . $process);

					switch($process) {

						// Datagrid
						case 'datagrid' :
						case 'datagrid_2_column' :

							if($meta_value == '') { break; }

							if(!isset($map['destination'])) { break; }

							if(is_array($meta_value)) {

								$data = $meta_value;

							} else {

								if(($data = @unserialize($meta_value)) === false) {

									if(is_array($meta_value)) { $data = $meta_value; } else { break; }
								}
							}

							$datagrid_data = array();

							foreach($data as $row) {

								// Process mappings
								if(isset($map['map'])) {

									// Process map
									$datagrid_single = array();
									$datagrid_single['meta'] = array();
									$datagrid_single['scratch'] = array();
									$datagrid_lookups = $return_lookups;
									$datagrid_actions = array();
									$datagrid_group_index = false;
									$datagrid_section_index = false;

									foreach($map['map'] as $map_map) {

										// Get source
										if(isset($map_map['source'])) {

											// Get meta value
											$source = $map_map['source'];
											$meta_value = (isset($row[$source])) ? $row[$source] : '';

										} else {

											// No source specified
											$meta_value = '';
										}

										self::map_process($datagrid_single, $datagrid_lookups, $datagrid_actions, $datagrid_group_index, $create_group, $records_since_create_group, $datagrid_section_index, $return_secton_created, $map_map, $meta_value, $table_records_base, $table_records, $table_record_key, $part_config, $source, $plugin_variables);

									}

									$datagrid_data[] = $datagrid_single;

								} else {

									$datagrid_data = false;
								}
							}

							// Get base meta_key
							$meta_keys = WS_Form_Config::get_meta_keys();
							if(!isset($meta_keys[$map['destination']])) { break; }
							$meta_value = $meta_keys[$map['destination']]['default'];

							// Initialize rows
							$rows = array();
							$row_id = 1;

							foreach($datagrid_data as $row) {

								// Build rows
								$rows[] = array(

									'id'		=> $row_id,
									'default'	=> isset($row['default']) ? $row['default'] : '',
									'required'	=> isset($row['required']) ? $row['required'] : '',
									'disabled'	=> isset($row['disabled']) ? $row['disabled'] : '',
									'hidden'	=> isset($row['hidden']) ? $row['hidden'] : '',
									'data'		=> ($process == 'datagrid_2_column') ?

													array(isset($row['value']) ? $row['value'] : '', isset($row['label']) ? $row['label'] : '') :
													array(isset($row['label']) ? $row['label'] : '')
								);

								$row_id++;
							}

							// Set colums
							if($process == 'datagrid_2_column') {

								$meta_value['columns'] = array(

									array('id' => 0, 'label' => __('Value', 'ws-form')),
									array('id' => 1, 'label' => __('Label', 'ws-form')),
								);

								switch($map['destination']) {

									case 'data_grid_select' :

										$field_label_meta_key = 'select_field_label';
										break;

									case 'data_grid_checkbox' :

										$field_label_meta_key = 'checkbox_field_label';
										break;

									case 'data_grid_radio' :

										$field_label_meta_key = 'radio_field_label';
										break;

									case 'data_grid_select_price' :

										$field_label_meta_key = 'select_price_field_label';
										break;

									case 'data_grid_checkbox_price' :

										$field_label_meta_key = 'checkbox_price_field_label';
										break;

									case 'data_grid_radio_price' :

										$field_label_meta_key = 'radio_price_field_label';
										break;
								}

								$return_single['meta'][$field_label_meta_key] = 1;
							}

							// Set rows
							$meta_value['groups'][0]['rows'] = $rows;

							break;

						// Prefix with a zero if single character
						case 'prefix_zero' :

							if(strlen((string) $meta_value) == 1) { $meta_value = '0' . $meta_value; }
							break;

						// Convert date to ISO 8601 format
						case 'datetime_to_html5' :

							$this->debug('Input: ' . $meta_value);
							$meta_value_time = strtotime($meta_value);
							$this->debug('Time: ' . $meta_value_time);
							$meta_value = date('Y-m-d',$meta_value_time) . "T" . date('H:i:s',$meta_value_time);
							$this->debug('Output: ' . $meta_value);
							break;

						// Convert br tags to new lines
						case 'br_to_newline' :

							if($meta_value == '') { break; }

							// Remove existing new lines
						    $breaks = array("\n","\r");  
						    $meta_value = str_ireplace($breaks, '', $meta_value);

						    // Replace <br> tags with new lines
						    $breaks = array('<br />','<br>','<br/>');  
						    $meta_value = str_ireplace($breaks, "\n", $meta_value);

						    break;

						// Strip HTML tags
						case 'strip_tags' :

							if($meta_value == '') { break; }

						    $meta_value = strip_tags($meta_value);

						    break;

						// CSV to array
						case 'csv_to_array' :

							if($meta_value == '') { break; }

							$meta_value = explode(',', $meta_value);

							break;

						// img tag containing base64 to WS Form file
						case 'img_base64_to_file' :

							if($meta_value == '') { break; }

							$regex = '/src="(data:image\/[^;]+;base64[^"]+)"/';
							preg_match_all($regex, $meta_value, $matches, PREG_SET_ORDER);
							if(
								is_array($matches) &&
								isset($matches[0]) &&
								isset($matches[0][1])
							) {

								$meta_value = 'base64_to_file,' . $matches[0][1];

							} else {

								$meta_value = '';
							}

							break;

						// JSON Decode
						case 'json_decode' :

							if($meta_value == '') { break; }

							// Check to see if value is JSON
							if(!is_null($meta_value_json_decoded = @json_decode($meta_value))) {

								if(is_array($meta_value_json_decoded)) {

									$meta_value = $meta_value_json_decoded;
								}
							}

							break;

						// Newlines to array
						case 'newline_to_array' :

							if($meta_value == '') { break; }

							$meta_value = explode("\n", $meta_value);

							break;

						// Partials to array
						case 'partials_to_array' :

							$partial_index = 1;

							$partial_meta_key_mask = isset($part_config['partial_meta_key_mask']) ? $part_config['partial_meta_key_mask'] : '#meta_key.#partial_index';

							$meta_value = array();

							do {

								$partial_meta_key_found = false;

								// Build partial key
								$partial_meta_key_lookups = array(

									'meta_key' => $source,
									'partial_index' => $partial_index
								);
								$partial_meta_key = WS_Form_Common::mask_parse($partial_meta_key_mask, $partial_meta_key_lookups);

								foreach($table_records as $table_record_key => $table_record_read) {

									if(!isset($table_record_read['meta_key'])) { continue; }

									if($table_record_read['meta_key'] == $partial_meta_key) {

										$meta_value[] = $table_record_read['meta_value'];

										$partial_meta_key_found = true;
									}
								}

								$partial_index++;

							} while ($partial_meta_key_found !== false);

							break;

						// Image URL (wp-content/uploads) to file
						case 'upload_url_to_file' :

							if($meta_value == '') { break; }

							// Check to see if value is JSON
							if(!is_null($meta_value_json_decoded = @json_decode($meta_value))) {

								if(is_array($meta_value_json_decoded)) {

									$meta_value_array = $meta_value_json_decoded;

								} else {

									break;
								}

							} else {

								$meta_value_array = explode(',', $meta_value);
							}

							foreach($meta_value_array as $meta_value_array_key => $meta_value) {

								$meta_value = trim($meta_value);
								$url_parsed = parse_url($meta_value);
								if($url_parsed === false) { break; }
								$meta_value_array[$meta_value_array_key] = $url_parsed['path'];
							}

							$meta_value = 'upload_url_to_file,' . implode(',', $meta_value_array);

							break;

						// Image filename to file
						case 'filename_to_file' :

							if($meta_value == '') { break; }

							$source_path = isset($processes['source_path']) ? $processes['source_path'] : '';

							$upload_dir = wp_upload_dir();
							$wp_uploads = $upload_dir['basedir'];

							// Build source path
							$source_path_lookups = array(

								'wp_uploads' => $wp_uploads,
								'filename' => $meta_value
							);

							$source_path = WS_Form_Common::mask_parse($source_path, $source_path_lookups);

							$meta_value = 'filename_to_file,' . $source_path;

							break;

						// Convert date to HTML date value format
						case 'date_to_input_value' :

							if($meta_value == '') { break; }

						    $meta_value = date('Y-m-d', strtotime($meta_value));

						    break;

						// Convert time to HTML date value format
						case 'time_to_input_value' :

							if($meta_value == '') { break; }

						    $meta_value = date('H:i:s', strtotime($meta_value));

						    break;
					}
				}
			}

			// Mask
			if(isset($map['mask'])) {

				$return_lookups['value'] = $meta_value;

				$mask_disregard_on_empty = isset($map['mask_disregard_on_empty']) ? $map['mask_disregard_on_empty'] : false;

				if(!(
					($meta_value == '') &&
					$mask_disregard_on_empty
				)) {

					$mask = isset($map['mask']) ? $map['mask'] : '';
					$meta_value = WS_Form_Common::mask_parse($mask, $return_lookups);
				}
			}

			// Lookup
			if(isset($map['lookup'])) {

				foreach($map['lookup'] as $lookup) {

					$find = $lookup['find'];
					$replace = $lookup['replace'];

					if($meta_value === $find) { $meta_value = $replace; break; }
				}
			}

			// Lookup
			if(isset($map['lookup_csv'])) {

				$meta_value_array =[];
				$meta_value_join = isset($map['lookup_csv_join']) ? $map['lookup_csv_join'] : ',';
				$meta_array = explode(',', $meta_value);

				foreach($meta_array as $meta_single) {

					$meta_single = trim($meta_single);

					foreach($map['lookup_csv'] as $change_this => $to_this) {

						if($meta_single == $change_this) { $meta_value_array[] = $to_this; break; }
					}
				}

				$meta_value = implode($meta_value_join, $meta_value_array);
			}

			// Enable action
			if(isset($map['action'])) {

				$return_actions[$map['action']] = ($meta_value != '');
			}

			// Process by type
			switch($map['type']) {

				// Split a field into fields
				case 'records' :

					$table_records_new = array();

					$column_count = intval(WS_Form_Common::option_get('framework_column_count', 12));

					// Merge
					if(isset($map['merge'])) {

						$meta_value = array();

						foreach($map['merge'] as $key => $merge_record) {

							$condition_return = true;

							if(isset($merge_record['condition'])) {

								$condition_return = self::condition_process($merge_record, $return_lookups);
							}

							if(!$condition_return) { continue; }

							// Build record
							$record = $merge_record['merge'];
							$record['id'] = $return_lookups['id'] . '.' . $key;

							// Parse record
							foreach($record as $record_key => $record_value) {

								$record[$record_key] = WS_Form_Common::mask_parse($record_value, $return_lookups);
							}

							// Add optional size
							if(isset($merge_record['size_percent'])) {

								$record['breakpoint_size_25'] = round($column_count * (intval($merge_record['size_percent']) / 100));
							}

							$meta_value[] = $record;
						}

						// No fields left? Bail!
						if(count($meta_value) == 0) { return false; }
					}

					// Partial merge
					if(isset($map['partial_merge'])) {

						if(!is_array($meta_value)) { break; }

						foreach($meta_value as $key => $merge_record) {

							foreach($map['partial_merge'] as $partial) {

								if(!self::ends_with($merge_record[$partial['id']], $partial['id_partial'])) { continue; }

								if(!isset($partial['condition'])) { continue; }

								$condition_return = true;

								foreach($partial['condition'] as $condition_if_this => $condition_equals_this) {

									if(substr($condition_if_this, 0, 1) != '#') {

										if(isset($merge_record[$condition_if_this])) {

											if($merge_record[$condition_if_this] !== $condition_equals_this) { $condition_return = false; }
										}

										unset($partial['condition'][$condition_if_this]);
									}
								}

								if(count($partial['condition']) > 0) {

									$condition_return = self::condition_process($partial, $return_lookups);
								}

								if(!$condition_return) {

									unset($meta_value[$key]);
									break;
								}
							}
						}

						// No fields left? Bail!
						if(count($meta_value) == 0) { return false; }

						$meta_value_new = array();

						foreach($meta_value as $key => $record) {

							$partial_record_index = 0;

							foreach($map['partial_merge'] as $partial) {

								if(self::ends_with($record[$partial['id']], $partial['id_partial'])) {

									// Parse merge
									foreach($partial['merge'] as $merge_key => $merge_value) {

										$partial['merge'][$merge_key] = WS_Form_Common::mask_parse($merge_value, $return_lookups);
									}

									// Merge
									$record = array_merge($record, $partial['merge']);
									$record['id'] = $record[$partial['id']];
									if(!empty($return_lookups['id'])) { $record['parent_id'] = $return_lookups['id']; }

									// Add optional size
									if(isset($partial['size_percent'])) {

										$record['breakpoint_size_25'] = round($column_count * (intval($partial['size_percent']) / 100));
									}

									$meta_value_new[$key] = $record;
								}
							}
						}

						$meta_value = $meta_value_new;
					}

					// Columns widths
					if(isset($map['auto_size']) && $map['auto_size']) {

						$column_count_remaining = $column_count;
						$field_count_remaining = count($meta_value);
						$column_size_total = 0;
						foreach($meta_value as $key => $record) {

							$column_size = round($column_count_remaining / $field_count_remaining);

							// Add data
							$meta_value[$key]['breakpoint_size_25'] = $column_size;

							$field_count_remaining--;
							$column_count_remaining -= $column_size;
						}
					}

					// Inject
					$first_key = false;
					foreach($table_records as $key => $record) {

						if($key == $table_record_key) {

							foreach($meta_value as $key => $record) {

								$current_key = (string) $record['id'];
								$table_records_new[$current_key] = $record;
								if($first_key === false) { $first_key = $current_key; }
							}
	
						} else {

							$table_records_new[(string) $key] = $record;
						}
					}

					$table_records = $table_records_new;

					// Move internal pointer to newly created records
					if($first_key !== false) {

						while (key($table_records) !== $first_key) { next($table_records); }
					}

					return false;

				// Record level data
				case 'record' :

					if(!is_array($meta_value) && !is_bool($meta_value)) {

						$meta_value = WS_Form_Common::mask_parse($meta_value, $plugin_variables, '');
						$return_lookups[$map['destination']] = $meta_value;
						$this->debug('Set record value: ' . $meta_value);
					}
					$return_single[$map['destination']] = $meta_value;
					break;

				// Import data is not store in the final record or meta data, it is used to define other form elements such as action settings
				case 'scratch' :

					if(!is_array($meta_value)) {

						$meta_value = WS_Form_Common::mask_parse($meta_value, $plugin_variables, '');
						$return_lookups[$map['destination']] = $meta_value;
						$this->debug('Set scratch value: ' . $meta_value);
					}
					$return_single['scratch'][$map['destination']] = $meta_value;
					break;

				// Scratch global
				case 'scratch_global' :

					if(!is_array($meta_value)) {

						$meta_value = WS_Form_Common::mask_parse($meta_value, $plugin_variables, '');
						$this->return_scratch_global[$map['destination']] = $meta_value;
						$this->debug('Set global scratch value: ' . $meta_value);
					}
					break;

				// Regular WS Form meta data
				case 'meta' :

					if(!is_array($meta_value)) {

						$meta_value = WS_Form_Common::mask_parse($meta_value, $plugin_variables, '');
						$return_lookups[$map['destination']] = $meta_value;
						$this->debug('Set meta value: ' . $meta_value);
					}
					$return_single['meta'][$map['destination']] = $meta_value;
					break;

				// Submit meta
				case 'meta-submit' :

					if(!is_array($meta_value)) { $meta_value = WS_Form_Common::mask_parse($meta_value, $return_lookups, ''); }
					if(isset($map['destination'])) {

						$key = $map['destination'];
						$return_single['meta'][$key] = array(

							'id' 	=> $key,
							'value' => $meta_value
						);
					}
					break;

				// For Each
				case 'foreach_action' :

					$foreach_actions = array();

					if(!is_array($meta_value)) { break; }

					foreach($meta_value as $data) {

						// Regular mapping
						if(isset($map['map'])) {

							foreach($map['map'] as $map_map) {

								// Get source
								if(isset($map_map['source'])) {

									// Get meta value
									$source = $map_map['source'];
									$meta_value = (isset($data[$source])) ? $data[$source] : '';

								} else {

									// No source specified
									$meta_value = $data;
								}

								// Process map
								self::map_process($return_single, $return_lookups, $foreach_actions, $return_group_index, $create_group, $records_since_create_group, $return_section_index, $create_section, $map_map, $meta_value, $table_records_base, $table_records, $table_record_key, $part_config, $source, $plugin_variables);
							}
						}

						if(count($foreach_actions) > 0) {

							$return_actions = array_merge($return_actions, $foreach_actions);
							break;
						}
					}

					break;

				// Serialized data that needs post processing
				case 'array' :
				case 'object' :
				case 'serialize' :
				case 'json' :

					switch($map['type']) {

						case 'array' :
						case 'object' :

							$data = $meta_value;
							break;

						case 'serialize' :

							if(($data = @unserialize($meta_value)) === false) { $data = array(); }
							break;

						case 'json' :

							if(is_null($data = @json_decode($meta_value))) { $data = array(); }
							break;
					}

					if(is_object($data)) { $data = json_decode(json_encode($data), true); }

					if(is_array($data)) {

						// Regular mapping
						if(isset($map['map'])) {

							foreach($map['map'] as $map_map) {

								// Get source
								if(isset($map_map['source'])) {

									// Get meta value
									$source = $map_map['source'];
									$meta_value = (isset($data[$source])) ? $data[$source] : '';

								} else {

									// No source specified
									$meta_value = $data;
								}

								// Process map
								self::map_process($return_single, $return_lookups, $return_actions, $return_group_index, $create_group, $records_since_create_group, $return_section_index, $create_section, $map_map, $meta_value, $table_records_base, $table_records, $table_record_key, $part_config, $source, $plugin_variables);
							}
						}

						// Mapping by type
						if(
							isset($return_lookups['type_source']) &&
							isset($map['map_by_type']) &&
							isset($map['map_by_type'][$return_lookups['type_source']])
						) {

							foreach($map['map_by_type'][$return_lookups['type_source']] as $map_map) {

								// Get source
								if(isset($map_map['source'])) {

									// Get meta value
									$source = $map_map['source'];
									$meta_value = (isset($data[$source])) ? $data[$source] : '';

								} else {

									// No source specified
									$meta_value = '';
								}

								// Process map
								self::map_process($return_single, $return_lookups, $return_actions, $return_group_index, $create_group, $records_since_create_group, $return_section_index, $create_section, $map_map, $meta_value, $table_records_base, $table_records, $table_record_key, $part_config, $source, $plugin_variables);
							}
						}
					}

					break;
			}
		}

		// Check id
		public function db_check_id() {

			foreach($this->config as $valid_id => $config) {

				if($valid_id == $this->id) { return true; }
			}

			parent::db_throw_error(__('Invalid migrate ID', 'ws-form'));
		}

		public function get_migrate() {

			include WS_FORM_PLUGIN_DIR_PATH . 'includes/core/migrate/gravity_forms.php';
			include WS_FORM_PLUGIN_DIR_PATH . 'includes/core/migrate/vfb.php';
			include WS_FORM_PLUGIN_DIR_PATH . 'includes/core/migrate/wpforms.php';

			// Apply filter
			$migrate = apply_filters('wsf_config_migrate', array());

			return $migrate;
		}

		public function get_config() {

			self::db_check_id();

			return $this->config[$this->id];
		}

		public function debug($message) {

			if($this->debug) {

				echo "- $message\n";	// phpcs:ignore
			}
		}

		public function data_lookup($config, $id, $table_records_base, $return_lookups) {

			if(!isset($config['data_lookups'])) { return ''; }
			if(!isset($config['data_lookups'][$id])) { return ''; }
			$data_lookup = $config['data_lookups'][$id];
			$data_current = $table_records_base;

			$this->debug('Found data lookup: ' . print_r($data_lookup, true));

			do {

				foreach($data_lookup as $data_current_key => $data_lookup) {

					$this->debug('  - Processing lookup key: ' . $data_current_key);

					// Step into data
					if(!isset($data_current[$data_current_key])) { return ''; }
					$data_current = $data_current[$data_current_key];

					// If end of location, get value
					if(!is_array($data_lookup)) {

						$data_index = false;

						$this->debug('  - Required index: ' . $data_lookup);

						$data_index = intval(WS_Form_Common::mask_parse($data_lookup, $return_lookups));

						$this->debug('  - Using index: ' . $data_index);

						$this->debug('  - Looking in: ' . print_r($data_current, true));

						if(($data_index !== false) && isset($data_current[$data_index])) { $meta_value = $data_current[$data_index]; } else { return ''; }

						$this->debug('  - Found lookup value: ' . $meta_value);

						return $meta_value;
					}
				}

			} while(is_array($data_lookup));

			return '';
		}

		public function condition_process($map, $return_lookups) {

			$condition_logic = isset($map['condition_logic']) ? $map['condition_logic'] : '===';

			if(is_string($map['condition'])) { $conditions = array($map['condition']); }

			foreach($map['condition'] as $condition_if_this => $condition_equals_this) {

				$condition_if_this = is_string($condition_if_this) ? WS_Form_Common::mask_parse($condition_if_this, $return_lookups) : $condition_if_this;
				$condition_equals_this = is_string($condition_equals_this) ? WS_Form_Common::mask_parse($condition_equals_this, $return_lookups) : $condition_equals_this;

				$this->debug("Checking $condition_if_this $condition_logic $condition_equals_this");

//				echo "Checking $condition_if_this $condition_logic $condition_equals_this\n";

				$condition_outcome = true;

				switch($condition_logic) {

					case '===' : if(!($condition_if_this === $condition_equals_this)) { $condition_outcome = false; } break;
					case '!==' : if(!($condition_if_this !== $condition_equals_this)) { $condition_outcome = false; } break;
					case '==' : if(!($condition_if_this == $condition_equals_this)) { $condition_outcome = false; } break;
					case '!=' : if(!($condition_if_this != $condition_equals_this)) { $condition_outcome = false; } break;
				}

				if(!$condition_outcome) {

					$this->debug('Condition failed, returning');
					return false;
				}
			}

			return true;
		}

		public function ends_with($haystack, $needle) {

			$length = strlen($needle);
			if ($length == 0) {
				return true;
			}

			return (substr($haystack, -$length) === $needle);
		}
	}