<?php

	class WS_Form_API_Migrate extends WS_Form_API {

		public function __construct() {

			// Call parent on WS_Form_API
			parent::__construct();
		}

		// API - Form - Import
		public function api_form_import($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('import_form')) { parent::api_access_denied(); }

			// Read configuration data
			$wsf_form_migrate = WS_Form_Common::get_query_var_nonce('wsf_form_migrate', '', $parameters);
			$wsf_field_mapping = WS_Form_Common::get_query_var_nonce('wsf_field_mapping', '', $parameters);

			// Checks
			$ws_form_migrate_id = $wsf_form_migrate['id'];
			if(empty($ws_form_migrate_id)) { self::api_throw_error(__('Plugin ID not specified', 'ws-form')); }

			$ws_form_migrate_form_id_source = $wsf_form_migrate['form_id_source'];
			if(empty($ws_form_migrate_form_id_source)) { self::api_throw_error(__('Source form ID not specified', 'ws-form')); }

			// Initialize migrate
			$ws_form_migrate = new WS_Form_Migrate();
			$ws_form_migrate->id = $ws_form_migrate_id;

			// Read form data
			$return_data = $ws_form_migrate->get_data('form', $ws_form_migrate_form_id_source);

			// Check return data
			if(
				(!isset($return_data['data'])) ||
				(!isset($return_data['data'][$ws_form_migrate_form_id_source]))
			) {

				self::api_throw_error(__('Form data not found', 'ws-form'));
			}

			// Extract form
			$form = $return_data['data'][$ws_form_migrate_form_id_source];

			// Add meta data for future lookups
			$form['meta']['source_form_id'] = $ws_form_migrate_form_id_source;
			$form['meta']['source_id'] = $form['id'];

			// Read field data
			$return_data = $ws_form_migrate->get_data('field', $ws_form_migrate_form_id_source);

			// Check return data
			if(!isset($return_data['data'])) { self::api_throw_error(__('Field data not found', 'ws-form')); }

			// Extract fields
			$fields = $return_data['data'];

			// Build form data
			$ws_form_form = new WS_Form_Form();
			$ws_form_form->db_create();

			// Read group data
			$return_data = $ws_form_migrate->get_data('group', $ws_form_migrate_form_id_source);

			// Check return data
			if(($return_data !== false) && !isset($return_data['data'])) { self::api_throw_error(__('Group data not found', 'ws-form')); }

			if(($return_data !== false) && count($return_data['data']) > 0) {

				// Extract groups
				$groups = $return_data['data'];

			} else {

				// Single group
				$groups = array(

					0 => array(

						'id' => 0,
						'label' => $form['label'],
						'meta' => array()
					)
				);
			}

			// Read section data
			$return_data = $ws_form_migrate->get_data('section', $ws_form_migrate_form_id_source);

			// Check return data
			if(($return_data !== false) && !isset($return_data['data'])) { self::api_throw_error(__('Section data not found', 'ws-form')); }

			if(($return_data !== false) && count($return_data['data']) > 0) {

				// Extract sections
				$sections = $return_data['data'];

			} else {

				// Single section
				$sections = array(

					0 => array(

						'id' => 0,
						'label' => $groups[0]['label'],
						'meta' => array()
					)
				);
			}

			// Build base group
			$form['groups'] = array();

			// Get field types
			$field_types = WS_Form_Config::get_field_types_flat();

			// Assign fields to each group
			foreach($groups as $group_id => $group) {

				// Build group
				$form['groups'][$group_id] = $group;
				$form['groups'][$group_id]['sections'] = array();

				$section_first = true;

				foreach($sections as $section_id => $section) {

					if(!isset($section['group_id'])) { continue; }
					$section_group_id = $section['group_id'];
					if($section_group_id != $group_id) { continue; }

					// Show section label on 2nd section onwards
					if(!$section_first) {

						$section['meta']['label_render'] = 'on';
					}

					// Build section
					$form['groups'][$group_id]['sections'][$section_id] = $section;
					$form['groups'][$group_id]['sections'][$section_id]['fields'] = array();

					// Section fields
					foreach($fields as $field_id => $field) {

						if(!isset($field['type']) || !isset($field_types[$field['type']]) || ($field['type'] == '')) { $field['type'] = 'text'; }

						if(!isset($field['group_id'])) { continue; }
						$field_group_id = $field['group_id'];
						if($field_group_id != $group_id) { continue; }

						if(!isset($field['section_id'])) { continue; }
						$field_section_id = $field['section_id'];
						if($field_section_id != $section_id) { continue; }

						// Build fields
						$form['groups'][$group_id]['sections'][$section_id]['fields'][$field_id] = $field;
					}

					$section_first = false;
				}
			}

			// Create form
			$form_object = json_decode(json_encode($form));
			$ws_form_form->db_update_from_object($form_object, true, true);

			// Fix data - Action ID's
			$ws_form_form->db_action_repair();

			// Fix data - Meta ID's
			$ws_form_form->db_meta_repair();

			// Set checksum
			$ws_form_form->db_checksum();

			// Check if we should publish the form
			if($form_object->status == 'publish') {

				$ws_form_form->db_publish();
			}

			// Set field mapping option key
			$field_mapping = array();
			$field_mapping_option_key = 'migrate_field_mapping_' . $ws_form_migrate_id . '_' . $ws_form_migrate_form_id_source . '_' . $ws_form_form->id;

			// Re-read form so we can get the fields
			$form_object = $ws_form_form->db_read();

			// Build lookup tables
			$fields = WS_Form_Common::get_fields_from_form($form_object);

			foreach($ws_form_form->new_lookup['field'] as $field_id_source => $field_id_destination) {

				// Check if we should include this field in the mapping (e.g. Buttons not mapped)
				$field = $fields[$field_id_destination];
				if(!isset($field_types[$field->type])) { continue; }
				$field_type = $field_types[$field->type];
				$submit_save = isset($field_type['submit_save']) ? $field_type['submit_save'] : false;
				if(!$submit_save) { continue; }

				// Ignore empty rows
				if(($field_id_source == 0) && ($field_id_destination == 0)) { continue; }

				// Add to mapping
				$field_mapping[] = array('s' => $field_id_source, 'd' => $field_id_destination);
			}

			// Save to options
			WS_Form_Common::option_set($field_mapping_option_key, $field_mapping);
			WS_Form_Common::option_set('migrate_form_id_destination', $ws_form_form->id);

			// Build return array
			$return_array = array(

				'form_id'	=> $ws_form_form->id
			);

			echo wp_json_encode($return_array);
			exit;
		}

		// API - Submission - Import
		public function api_submission_import($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('import_form')) { parent::api_access_denied(); }

			// Return variables
			$offset = intval(WS_Form_Common::get_query_var_nonce('offset', '', $parameters));
			$remaining = false;
			$progress = 0;
			$processed = 0;
			$created = 0;
			$updated = 0;
			$ignored = 0;

			// Read configuration data
			$wsf_form_migrate = WS_Form_Common::get_query_var_nonce('wsf_form_migrate', '', $parameters);
			$wsf_field_mapping = WS_Form_Common::get_query_var_nonce('wsf_field_mapping', '', $parameters);

			// Checks
			$ws_form_migrate_id = $wsf_form_migrate['id'];
			if(empty($ws_form_migrate_id)) { self::api_throw_error(__('Plugin ID not specified', 'ws-form')); }

			$ws_form_migrate_form_id_source = $wsf_form_migrate['form_id_source'];
			if(empty($ws_form_migrate_form_id_source)) { self::api_throw_error(__('Source form ID not specified', 'ws-form')); }

			$ws_form_migrate_form_id_destination = $wsf_form_migrate['form_id_destination'];
			if(empty($ws_form_migrate_form_id_destination)) { self::api_throw_error(__('Destination form ID not specified', 'ws-form')); }

			$ws_form_migrate_duplicate = $wsf_form_migrate['duplicate'];
			if(empty($ws_form_migrate_duplicate)) { self::api_throw_error(__('Duplicate method not specified', 'ws-form')); }

			// Initialize migrate
			$ws_form_migrate = new WS_Form_Migrate();
			$ws_form_migrate->id = $ws_form_migrate_id;

			// Build map
			$map = array();

			foreach($wsf_field_mapping['source'] as $index => $field_id_source) { 

				$field_id_destination = intval($wsf_field_mapping['destination'][$index]);

				if(!empty($field_id_source) && !empty($field_id_destination)) {

					$map_single = array('source' => $ws_form_migrate->get_submission_meta_key($field_id_source), 'type' => 'meta-submit', 'destination' => $field_id_destination);

					// Add processing to map
					$process = $ws_form_migrate->get_process($ws_form_migrate_form_id_source, $field_id_source);
					if($process !== false) { $map_single['process'] = $process; }

					$map[] = $map_single;
				}
			}

			$get_data_return = $ws_form_migrate->get_data('submission', $ws_form_migrate_form_id_source, $offset, $map);

			$submissions = $get_data_return['data'];

			$progress = $get_data_return['progress'];
			$remaining = $get_data_return['remaining'];
			$offset = $get_data_return['offset'];
			$total = $get_data_return['total'];

			foreach($submissions as $submission) {

				// Build WS Form submit record
				$ws_form_submit = new WS_Form_Submit();
				$ws_form_submit_meta = new WS_Form_Submit_Meta();

				// Attempt to find existing record
				$submission_ignore = false;
				$parent_id = $ws_form_submit_meta->db_read_parent_id_by_meta('source_id', $submission['id'], $ws_form_migrate_form_id_destination);
				if($parent_id !== false) {

					// Existing record found, so handle duplicates according to users configuration
					switch($ws_form_migrate_duplicate) {

						case 'update' : $submission_create = false; break;
						case 'create' : $submission_create = true; break;
						case 'ignore' : $submission_ignore = true;
					}

				} else {

					// Existing record not found, so create
					$submission_create = true;
				}

				if($submission_ignore) { $ignored++; continue; }

				if(!$submission_create) {

					// Update existing
					$ws_form_submit->id = $parent_id;
					$ws_form_submit->db_read();
				}

				// Set submit values
				$ws_form_submit->form_id = $ws_form_migrate_form_id_destination;
				$ws_form_submit->status = isset($submission['status']) ? $submission['status'] : 'publish';
				$ws_form_submit->spam_level = isset($submission['spam_level']) ? $submission['spam_level'] : null;
				if(($ws_form_submit->status == 'spam') && is_null($ws_form_submit->spam_level)) { $ws_form_submit->spam_level = 100; }
				$ws_form_submit->preview = isset($submission['preview']) ? ($submission['preview'] == true) : false;
				$ws_form_submit->date_added = WS_Form_Common::get_mysql_date(isset($submission['date_added']) ? $submission['date_added'] : false);
				$ws_form_submit->date_updated = WS_Form_Common::get_mysql_date(isset($submission['date_updated']) ? $submission['date_updated'] : false);
				$ws_form_submit->count_submit = isset($submission['count_submit']) ? $submission['count_submit'] : 1;
				$ws_form_submit->user_id = isset($submission['user_id']) ? $submission['user_id'] : 0;
				$ws_form_submit->viewed = isset($submission['viewed']) ? $submission['viewed'] : 0;
				$ws_form_submit->starred = isset($submission['starred']) ? $submission['starred'] : 0;

				if($submission_create) {

					// Create new
					$ws_form_submit->db_create();

					// Build meta data
					$ws_form_submit_meta->parent_id = $ws_form_submit->id;
					$ws_form_submit_meta->process($ws_form_submit, $submission['meta']);

					// Add meta data for future lookups
					$submission['meta']['source_form_id'] = $ws_form_migrate_form_id_source;
					$submission['meta']['source_id'] = $submission['id'];

					$ws_form_submit_meta->db_update_from_array($submission['meta'], $ws_form_submit->encrypted);

					$created++;

				} else {

					// Update existing
					$ws_form_submit_meta->process($ws_form_submit, $submission['meta']);
					$ws_form_submit->meta = $submission['meta'];
					$ws_form_submit->db_update();

					$updated++;
				}
			}

			// Update form count_submit
			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $ws_form_migrate_form_id_destination;
			$ws_form_form->db_count_update();

			// Build return array
			$return_array = array(

				'remaining'	=> $remaining,
				'progress'	=> $progress,
				'offset'	=> $offset,
				'total'		=> $total,
				'created'	=> $created,
				'updated'	=> $updated,
				'ignored'	=> $ignored,
			);

			echo wp_json_encode($return_array);
			exit;
		}

		// API Save Field Mapping
		public function api_field_mapping() {

			// Read settings
			$settings = array('id', 'form_id_source', 'method', 'form_id_destination');
			$wsf_form_migrate = WS_Form_Common::get_query_var_nonce('wsf_form_migrate', array());
			foreach($settings as $setting) {

				if(isset($wsf_form_migrate[$setting])) {

					WS_Form_Common::option_set('migrate_' . $setting, $wsf_form_migrate[$setting]);
				}

				$wsf_form_migrate[$setting] = WS_Form_Common::option_get('migrate_' . $setting, false);
			}

			// Set field mapping option key
			$field_mapping_option_key = 'migrate_field_mapping_' . $wsf_form_migrate['id'] . '_' . $wsf_form_migrate['form_id_source'] . '_' . $wsf_form_migrate['form_id_destination'];

			// Read mapping data
			$wsf_field_mapping = WS_Form_Common::get_query_var_nonce('wsf_field_mapping');

			$field_mapping = array();

			foreach($wsf_field_mapping['source'] as $field_mapping_index => $field_id_source) {

				$field_id_source = intval($field_id_source);
				$field_id_destination = intval($wsf_field_mapping['destination'][$field_mapping_index]);

				// Ignore empty rows
				if(($field_id_source == 0) && ($field_id_destination == 0)) { continue; }

				// Add to mapping
				$field_mapping[] = array('s' => $field_id_source, 'd' => $field_id_destination);
			}

			// Save to options
			WS_Form_Common::option_set($field_mapping_option_key, $field_mapping);

			$return_array = array(

				'success'	=> true
			);

			echo wp_json_encode($return_array);
			exit;			
		}
	}