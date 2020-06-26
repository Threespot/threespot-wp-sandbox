<?php

	class WS_Form_Form extends WS_Form_Core {

		public $id;
		public $checksum;
		public $new_lookup;
		public $variable_repair_field_array;
		public $label;
		public $meta;

		public $table_name;

		const DB_INSERT = 'label,user_id,date_added,date_updated,version';
		const DB_UPDATE = 'label,user_id,date_updated';
		const DB_SELECT = 'label,status,checksum,published_checksum,count_stat_view,count_stat_save,count_stat_submit,count_submit,count_submit_unread,id';

 		const FILE_ACCEPTED_MIME_TYPES = 'application/json';

		public function __construct() {

			global $wpdb;

			$this->id = 0;
			$this->table_name = $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . 'form';
			$this->checksum = '';
			$this->new_lookup = array();
			$this->new_lookup['form'] = array();
			$this->new_lookup['group'] = array();
			$this->new_lookup['section'] = array();
			$this->new_lookup['field'] = array();
			$this->label = WS_FORM_DEFAULT_FORM_NAME;
			$this->meta = array();

			// Variables to fix
			$this->variable_repair_field_array = array(

				'select_option_text', 
				'radio_label',
				'field',
				'ecommerce_field_price',
				'checkbox_label'
			);
		}

		// Create form
		public function db_create($create_group = true) {

			// User capability check
			if(!WS_Form_Common::can_user('create_form')) { return false; }

			global $wpdb;

			// Add form
			$sql = sprintf("INSERT INTO %s (%s) VALUES ('%s', %u, '%s', '%s', '%s');", $this->table_name, self::DB_INSERT, esc_sql($this->label), WS_Form_Common::get_user_id(), WS_Form_Common::get_mysql_date(), WS_Form_Common::get_mysql_date(), WS_FORM_VERSION);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error adding form', 'ws-form')); }

			// Get inserted ID
			$this->id = $wpdb->insert_id;

			// Build meta data array
			$settings_form_admin = WS_Form_Config::get_settings_form_admin();
			$meta_data = $settings_form_admin['sidebars']['form']['meta'];
			$meta_keys = WS_Form_Config::get_meta_keys();
			$meta_keys = apply_filters('wsf_form_create_meta_keys', $meta_keys);
			$meta_data_array = self::build_meta_data($meta_data, $meta_keys);
			$meta_data_array = array_merge($meta_data_array, $this->meta);

			// Build meta data
			$form_meta = New WS_Form_Meta();
			$form_meta->object = 'form';
			$form_meta->parent_id = $this->id;
			$form_meta->db_update_from_array($meta_data_array);

			// Build first group
			if($create_group) {

				$ws_form_group = New WS_Form_Group();
				$ws_form_group->form_id = $this->id;
				$ws_form_group->db_create();
			}

			// Run action
			do_action('wsf_form_create', $this);

			return $this->id;
		}

		public function db_create_from_wizard($id) {

			if(empty($id)) { return false; }

			// Create new form
			self::db_create();

			// Load wizard form data
			$ws_form_wizard = New WS_Form_Wizard();
			$ws_form_wizard->id = $id;
			$ws_form_wizard->read();
			$form_object = $ws_form_wizard->form;

			// Ensure form attributes are reset
			$form_object->status = 'draft';
			$form_object->count_submit = 0;
			$form_object->count_submit_unread = 0;

			// Create form
			self::db_update_from_object($form_object, true, true);

			// Fix data - Conditional ID's
			self::db_conditional_repair();

			// Fix data - Action ID's
			self::db_action_repair();

			// Fix data - Meta ID's
			self::db_meta_repair();

			// Set checksum
			self::db_checksum();

			return $this->id;
		}

		public function db_create_from_action($action_id, $list_id, $list_sub_id = false) {

			// Create new form
			self::db_create(false);

			if($this->id > 0) {

				// Modify form so it matches action list
				WS_Form_Action::update_form($this->id, $action_id, $list_id, $list_sub_id);

				return $this->id;

			} else {

				return false;
			}
		}

		// Read record to array
		public function db_read($get_meta = true, $get_groups = false, $checksum = false, $form_parse = false) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			global $wpdb;

			self::db_check_id();

			// Read form
			$sql = sprintf("SELECT %s FROM %s WHERE id = %u LIMIT 1;", self::DB_SELECT, $this->table_name, $this->id);
			$form_array = $wpdb->get_row($sql, 'ARRAY_A');

			if($form_array === null) { parent::db_throw_error(__('Unable to read form', 'ws-form')); }

			// Process groups (Done first in case we are requesting only fields)
			if($get_groups) {

				// Read sections
				$ws_form_group = New WS_Form_Group();
				$ws_form_group->form_id = $this->id;
				$ws_form_group_return = $ws_form_group->db_read_all($get_meta, $checksum);

				$form_array['groups'] = $ws_form_group_return;
			}

			// Set class variables
			foreach($form_array as $key => $value) {

				$this->{$key} = $value;
			}

			// Process meta data
			if($get_meta) {

				// Read meta
				$ws_form_meta = New WS_Form_Meta();
				$ws_form_meta->object = 'form';
				$ws_form_meta->parent_id = $this->id;
				$metas = $ws_form_meta->db_read_all();
				$form_array['meta'] = $this->meta = $metas;
			}

			// Convert into object
			$form_object = json_decode(json_encode($form_array));

			// Form parser
			if(isset($form_object->groups) && $form_parse) {

				$form_object = self::form_parse($form_object);
			}

			// Return array
			return $form_object;
		}

		// Read - Published data
		public function db_read_published($form_parse = false) {

			// No capabilities required, this is a public method

			global $wpdb;

			// Get contents of published field
			$sql = sprintf("SELECT checksum, published FROM %s WHERE id = %u LIMIT 1;", $this->table_name, $this->id);
			$published_row = $wpdb->get_row($sql);

			if($published_row === null) { parent::db_throw_error(__('Unable to read published form data', 'ws-form')); }

			// Read published JSON string
			$published_string = $published_row->published;

			// Empty published field (Never published)
			if($published_string == '') { return false; }

			// Inject latest checksum
			$form_object = json_decode($published_string);
			$form_object->checksum = $published_row->checksum;

			// Form parser
			if(isset($form_object->groups) && $form_parse) {

				$form_object = self::form_parse($form_object);
			}

			return $form_object;
		}

		// Set - Published
		public function db_publish() {

			// User capability check
			if(!WS_Form_Common::can_user('publish_form')) { return false; }

			global $wpdb;

			// Set form as published
			$sql = sprintf("UPDATE %s SET status = 'publish', date_publish = '%s', date_updated = '%s' WHERE id = %u LIMIT 1;", $this->table_name, WS_Form_Common::get_mysql_date(), WS_Form_Common::get_mysql_date(), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error publishing form', 'ws-form')); }

			// Read full form
			$form_object = self::db_read(true, true);

			// Update checksum
			self::db_checksum();

			// Set checksums
			$form_object->checksum = $this->checksum;
			$form_object->published_checksum = $this->checksum;

			// Apply filters
			apply_filters('wsf_form_publish', $form_object);

			// JSON encode
			$form_json = wp_json_encode($form_object);

			// Publish form
			$sql = sprintf("UPDATE %s SET published = '%s', published_checksum = '%s' WHERE id = %u LIMIT 1;", $this->table_name, esc_sql($form_json), esc_sql($this->checksum), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error publishing form', 'ws-form')); }

			// Do action
			do_action('wsf_form_publish', $form_object);
		}

		// Parse form
		public function form_parse($form_object) {

			// Field types
			$field_types = WS_Form_Config::get_field_types_flat();

 			// Form fields
			$fields = WS_Form_Common::get_fields_from_form($form_object, true);

			// Meta keys
			$meta_keys = false;

			// Process fields
			foreach($fields as $field) {

				if(!isset($field->type)) { continue; }

				// Get field type
				$field_type = $field->type;

				// Check to see if field is a price field or credit card (these also require input mask to be enqueued)
				if(isset($field_types[$field_type])) {

					$field_config = $field_types[$field_type];

					// Get keys
					$field_key = $field->field_key;
					$section_key = $field->section_key;
					$group_key = $field->group_key;

					// WPAutoP
					$meta_wpautop = isset($field_config['meta_wpautop']) ? $field_config['meta_wpautop'] : false;					
					if($meta_wpautop !== false) {

						if(!is_array($meta_wpautop)) { $meta_wpautop = array($meta_wpautop); }

						foreach($meta_wpautop as $meta_wpautop_meta_key) {

							// Check meta key exists
							if(!isset($field->meta->{$meta_wpautop_meta_key})) { continue; }

							// Update form_object
							$form_object->groups[$group_key]->sections[$section_key]->fields[$field_key]->meta->{$meta_wpautop_meta_key} = wpautop($field->meta->{$meta_wpautop_meta_key});
						}
					}

					// do_shortcode
					$meta_do_shortcode = isset($field_config['meta_do_shortcode']) ? $field_config['meta_do_shortcode'] : false;
					if($meta_do_shortcode !== false) {

						if(!is_array($meta_do_shortcode)) { $meta_do_shortcode = array($meta_do_shortcode); }

						foreach($meta_do_shortcode as $meta_do_shortcode_meta_key) {

							// Check meta key exists
							if(!isset($field->meta->{$meta_do_shortcode_meta_key})) { continue; }

							// Update form_object
							$form_object->groups[$group_key]->sections[$section_key]->fields[$field_key]->meta->{$meta_do_shortcode_meta_key} = WS_Form_Common::do_shortcode($field->meta->{$meta_do_shortcode_meta_key});
						}
					}

					// Data sources
					$data_source = isset($field_config['data_source']) ? $field_config['data_source'] : false;
					if(
						($data_source !== false) &&
						isset($data_source['id'])
					) {

						// Get meta key
						$meta_key = $data_source['id'];

						// Get meta keys if not set
						if($meta_keys === false) { $meta_keys = WS_Form_Config::get_meta_keys(); }

						if(isset($meta_keys[$meta_key])) {

							$meta_key_config = $meta_keys[$meta_key];

							// Check if data source enabled
							$data_source_enabled = isset($meta_key_config['data_source']) ? $meta_key_config['data_source'] : false;

							if($data_source_enabled) {

								// Check if data source ID is set
								$data_source_id = WS_Form_Common::get_object_meta_value($field, 'data_source_id', '');

								if(
									($data_source_id !== '') &&
									isset(WS_Form_Data_Source::$data_sources[$data_source_id]) &&
									method_exists(WS_Form_Data_Source::$data_sources[$data_source_id], 'get_data_source_settings')
								) {

									$data_source = WS_Form_Data_Source::$data_sources[$data_source_id];

									// Get data source settings
									$meta_keys = $data_source->get_data_source_meta_keys();

									// Configure
									foreach($meta_keys as $meta_key_single) {

										$data_source->{$meta_key_single} = WS_Form_Common::get_object_meta_value($field, $meta_key_single, false);
									}

									// Get existing meta_value
									$meta_value = WS_Form_Common::get_object_meta_value($field, $meta_key, false);

									// Get replacement meta_value
									$get_return = $data_source->get(1, $meta_key, $meta_value, true);	// true = form_parse to ignore paging

									// Set meta_key
									$form_object->groups[$group_key]->sections[$section_key]->fields[$field_key]->meta->{$meta_key} = $get_return['meta_value'];
								}
							}
						}
					}
				}
			}

			return $form_object;
		}

		// Set - Published
		public function db_draft() {

			// User capability check
			if(!WS_Form_Common::can_user('publish_form')) { return false; }

			global $wpdb;

			// Set form as published
			$sql = sprintf("UPDATE %s SET status = 'draft', date_publish = '', date_updated = '%s', published = '', published_checksum = '' WHERE id = %u LIMIT 1;", $this->table_name, WS_Form_Common::get_mysql_date(), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error drafting form', 'ws-form')); }

			// Read full form
			$form_object = self::db_read(true, true);

			// Update checksum
			self::db_checksum();
		}

		// Import reset
		public function db_import_reset() {

			// User capability check
			if(!WS_Form_Common::can_user('publish_form')) { return false; }

			global $wpdb;

			// Delete meta
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'form';
			$ws_form_meta->parent_id = $this->id;
			$ws_form_meta->db_delete_by_object();

			// Delete form groups
			$ws_form_group = New WS_Form_Group();
			$ws_form_group->form_id = $this->id;
			$ws_form_group->db_delete_by_form(false);

			// Set form as published
			$sql = sprintf("UPDATE %s SET status = 'draft', date_publish = NULL, date_updated = '%s', published = '', published_checksum = NULL WHERE id = %u LIMIT 1;", $this->table_name, WS_Form_Common::get_mysql_date(), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error resetting form', 'ws-form')); }
		}

		// Read - All
		public function db_read_all($join = '', $where = '', $order_by = '', $limit = '', $offset = '', $count_submit_update_all = true, $bypass_user_capability_check = false, $select = '') {

			// User capability check
			if(!$bypass_user_capability_check && !WS_Form_Common::can_user('read_form')) { return false; }

			global $wpdb;

			// Update count submit on all forms
			if($count_submit_update_all) { self::db_count_update_all(); }

			// Get form data
			if($select == '') { $select = self::DB_SELECT; }
			
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

			return $wpdb->get_results($sql, 'ARRAY_A');
		}

		// Delete
		public function db_delete() {

			// User capability check
			if(!WS_Form_Common::can_user('delete_form')) { return false; }

			global $wpdb;

			self::db_check_id();

			// Read the form status
			self::db_read(false, false);

			// If status is trashed, do a permanent delete of the data
			if($this->status == 'trash') {

				// Delete meta
				$ws_form_meta = New WS_Form_Meta();
				$ws_form_meta->object = 'form';
				$ws_form_meta->parent_id = $this->id;
				$ws_form_meta->db_delete_by_object();

				// Delete form groups
				$ws_form_group = New WS_Form_Group();
				$ws_form_group->form_id = $this->id;
				$ws_form_group->db_delete_by_form();

				// Delete form stats
				$ws_form_form_stat = New WS_Form_Form_Stat();
				$ws_form_form_stat->form_id = $this->id;
				$ws_form_form_stat->db_delete();

				// Delete form
				$sql = sprintf("DELETE FROM %s WHERE id = %u;", $this->table_name, $this->id);
				if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error deleting form', 'ws-form')); }

				// Do action
				do_action('wsf_form_delete', $this->id);

			} else {

				// Set status to 'trash'
				self::db_set_status('trash');

				// Do action
				do_action('wsf_form_trash', $this->id);
			}

			return true;
		}

		// Delete trashed forms
		public function db_trash_delete() {

			// Get all trashed forms
			$forms = self::db_read_all('', "status='trash'");

			foreach($forms as $form) {

				$this->id = $form['id'];
				self::db_delete();
			}

			return true;
		}

		// Clone
		public function db_clone() {

			// User capability check
			if(!WS_Form_Common::can_user('create_form')) { return false; }

			global $wpdb;

			// Read form data
			$form_object = self::db_read(true, true);

			// Clone form
			$sql = sprintf("INSERT INTO %s (%s) VALUES ('%s', %u, '%s', '%s', '%s');", $this->table_name, self::DB_INSERT, esc_sql(sprintf(__('%s (Copy)', 'ws-form'), $this->label)), WS_Form_Common::get_mysql_date(), WS_Form_Common::get_mysql_date(), WS_Form_Common::get_user_id(), WS_FORM_VERSION);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error cloning form', 'ws-form')); }

			// Get new form ID
			$this->id = $wpdb->insert_id;

			// Build form (As new)
			self::db_update_from_object($form_object, true, true);

			// Fix data - Conditional ID's
			self::db_conditional_repair();

			// Fix data - Action ID's
			self::db_action_repair();

			// Fix data - Meta ID's
			self::db_meta_repair();

			// Update checksum
			self::db_checksum();

			// Update form label
			$sql = sprintf("UPDATE %s SET label =  '%s' WHERE id = %u;", $this->table_name, esc_sql(sprintf(__('%s (Copy)', 'ws-form'), $this->label)), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error update form label', 'ws-form')); }

			return $this->id;
		}

		// Restore
		public function db_restore() {

			// User capability check
			if(!WS_Form_Common::can_user('delete_form')) { return false; }

			self::db_set_status('draft');

			// Do action
			do_action('wsf_form_restore', $this->id);
		}

		// Set status of form
		public function db_set_status($status) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			global $wpdb;

			self::db_check_id();

			// Ensure provided form status is valid
			self::db_check_status($status);

			// Update form record
			$sql = sprintf("UPDATE %s SET status = '%s' WHERE id = %u LIMIT 1;", $this->table_name, esc_sql($status), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error setting form status', 'ws-form')); }

			return true;
		}

		// Check form status
		public function db_check_status($status) {

			// Check status is valid
			$valid_statuses = explode(',', WS_FORM_STATUS_FORM);
			if(!in_array($status, $valid_statuses)) { parent::db_throw_error(__('Invalid form status: ' . $status, 'ws-form')); }

			return true;
		}

		// Get form status name
		public function db_get_status_name($status) {

			switch($status) {

				case 'draft' : 		return __('Draft', 'ws-form'); break;
				case 'publish' : 	return __('Published', 'ws-form'); break;
				case 'trash' : 		return __('Trash', 'ws-form'); break;
				default :			return $status;
			}
		}

		// Update all count_submit values
		public function db_count_update_all() {

			// Update form submit count
			global $wpdb;

			// Get all forms
			$sql = sprintf("SELECT id, count_stat_view,count_stat_save,count_stat_submit,count_submit,count_submit_unread FROM %s", $this->table_name);
			$forms = $wpdb->get_results($sql, 'ARRAY_A');

			foreach($forms as $form) {

				$this->id = $form['id'];

				// Update
				self::db_count_update($form);
			}
		}

		// Set count_submit
		public function db_count_update($form = false) {

			global $wpdb;

			self::db_check_id();

			// Get form stat totals
			$ws_form_form_stat = New WS_Form_Form_Stat();
			$ws_form_form_stat->form_id = $this->id;
			$count_array = $ws_form_form_stat->db_get_counts();

			// Get form submit total
			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->form_id = $this->id;
			$count_submit = $ws_form_submit->db_get_count_submit();
			$count_submit_unread = $ws_form_submit->db_get_count_submit_unread();

			// Check if new values are different from existing values
			$data_same = (

				($form) &&
				(intval($count_array['count_view']) == $form['count_stat_view']) &&
				(intval($count_array['count_save']) == $form['count_stat_save']) &&
				(intval($count_array['count_submit']) == $form['count_stat_submit']) &&
				(intval($count_submit) == $form['count_submit']) &&
				(intval($count_submit_unread) == $form['count_submit_unread'])
			);

			if(!$data_same) {

				// Update form record
				$sql = sprintf("UPDATE %s SET count_stat_view = %u, count_stat_save = %u, count_stat_submit = %u, count_submit = %u, count_submit_unread = %u WHERE id = %u LIMIT 1;", $this->table_name, intval($count_array['count_view']), intval($count_array['count_save']), intval($count_array['count_submit']), intval($count_submit), intval($count_submit_unread), $this->id);
				if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error updating counts', 'ws-form')); }
			}
		}

		// Set count_submit
		public function db_update_count_submit_unread($bypass_user_capability_check = false) {

			global $wpdb;

			self::db_check_id();

			// Get form submit total
			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->form_id = $this->id;
			$count_submit_unread = $ws_form_submit->db_get_count_submit_unread($bypass_user_capability_check);

			// Update form record
			$sql = sprintf("UPDATE %s SET count_submit_unread = %u WHERE id = %u LIMIT 1;", $this->table_name, intval($count_submit_unread), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error updating submit unread count', 'ws-form')); }
		}

		// Get total submissions unread
		public function db_get_count_submit_unread_total() {

			global $wpdb;

			$sql = sprintf("SELECT SUM(count_submit_unread) AS count_submit_unread FROM %s WHERE status IN ('publish', 'draft');", $this->table_name);
			$count_submit_unread = $wpdb->get_var($sql);
			return empty($count_submit_unread) ? 0 : intval($count_submit_unread);
		}

		// Get checksum of current form and store it to database
		public function db_checksum() {

			global $wpdb;

			self::db_check_id();

			// Get form data
			$form_object = self::db_read(true, true, true);

			// Remove any variables that change each time checksum calculated or don't affect the public form
			unset($form_object->checksum);
			unset($form_object->published_checksum);
			unset($form_object->meta->tab_index);
			unset($form_object->meta->breakpoint);

			// Serialize
			$form_serialized = serialize($form_object);

			// MD5
			$this->checksum = md5($form_serialized);

			// SQL escape
			$this->checksum = str_replace("'", "''", $this->checksum);

			// Update form record
			$sql = sprintf("UPDATE %s SET checksum = '%s' WHERE id = %u LIMIT 1;", $this->table_name, esc_sql($this->checksum), $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error setting checksum', 'ws-form')); }

			return $this->checksum;
		}

		// Get form count by status
		public function db_get_count_by_status($status = '') {

			global $wpdb;

			if(!WS_Form_Common::check_form_status($status, false)) { $status = ''; }

			$sql = sprintf("SELECT COUNT(id) FROM %s WHERE", $this->table_name);
			if($status == '') { $sql .= " NOT(status = 'trash')"; } else { $sql .= " status = '" . esc_sql($status) . "'"; }

			$form_count = $wpdb->get_var($sql);
			if(is_null($form_count)) { $form_count = 0; }

			return $form_count; 
		}

		// Push form from array (if full, include all groups, sections, fields)
		public function db_update_from_object($form_object, $full = true, $new = false) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Store old form ID
			$form_object_id_old = isset($form_object->id) ? $form_object->id : false;

			// Check for form ID in $form_object
			if(isset($form_object->id) && !$new) { $this->id = intval($form_object->id); }

			if(!$new) { self::db_check_id(); }

			// Update / Insert
			$this->id = parent::db_update_insert($this->table_name, self::DB_UPDATE, self::DB_INSERT, $form_object, 'form', $this->id, false);

			// Add to lookups
			if($form_object_id_old !== false) {

				$this->new_lookup['form'][$form_object_id_old] = $this->id;
			}

			// Base meta for new records
			if(!isset($form_object->meta) || !is_object($form_object->meta)) { $form_object->meta = new stdClass(); }
			if($new) {

				$settings_form_admin = WS_Form_Config::get_settings_form_admin();
				$meta_data = $settings_form_admin['sidebars']['form']['meta'];
				$meta_keys = WS_Form_Config::get_meta_keys();
				$meta_keys = apply_filters('wsf_form_create_meta_keys', $meta_keys);
				$meta_data_array = self::build_meta_data($meta_data, $meta_keys);
				$form_object->meta = (object) array_merge($meta_data_array, (array) $form_object->meta);
			}

			// Update meta
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'form';
			$ws_form_meta->parent_id = $this->id;
			$ws_form_meta->db_update_from_object($form_object->meta);

			// Full update?
			if($full) {

				// Update groups
				$ws_form_group = New WS_Form_Group();
				$ws_form_group->form_id = $this->id;
				$ws_form_group->db_update_from_array($form_object->groups, $new);

				if($new) {
					$this->new_lookup['group'] = $this->new_lookup['group'] + $ws_form_group->new_lookup['group'];
					$this->new_lookup['section'] = $this->new_lookup['section'] + $ws_form_group->new_lookup['section'];
					$this->new_lookup['field'] = $this->new_lookup['field'] + $ws_form_group->new_lookup['field'];
				}
			}

			return true;
		}

		// Conditional repair (Repairs a duplicated conditional and replaces ID's with new_lookup values)
		public function db_conditional_repair() {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Check form ID
			self::db_check_id();

			// Read conditional
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'form';
			$ws_form_meta->parent_id = $this->id;
			$conditional = $ws_form_meta->db_get_object_meta('conditional');

			// Data integrity check
			if(!isset($conditional->groups)) { return true; }
			if(!isset($conditional->groups[0])) { return true; }
			if(!isset($conditional->groups[0]->rows)) { return true; }

			// Run through each conditional (data grid rows)
			$rows = $conditional->groups[0]->rows;

			foreach($rows as $row_index => $row) {

				// Data integrity check
				if(!isset($row->data)) { continue; }
				if(!isset($row->data[1])) { continue; }

				$data = $row->data[1];

				// Data integrity check
				if(gettype($data) !== 'string') { continue; }
				if($data == '') { continue; }

				// Converts conditional JSON string to object
				$conditional_json_decode = json_decode($data);
				if(is_null($conditional_json_decode)) { continue; }

				// Process IF conditions
				$if = $conditional_json_decode->if;

				// Run through each group in $if
				foreach($if as $key_if => $group) {

					$conditions = $group->conditions;

					// Run through each condition
					foreach($conditions as $key_condition => $condition) {

						if(isset($condition->object) && isset($this->new_lookup[$condition->object]) && isset($this->new_lookup[$condition->object][$condition->object_id])) {
							$condition->object_id = $this->new_lookup[$condition->object][$condition->object_id];
						}

						// String replace - Field
						foreach($this->new_lookup['field'] as $field_id_old => $field_id_new) {

							if(isset($condition->value)) {

								foreach($this->variable_repair_field_array as $variable_repair_field) {

									$condition->value = str_replace('#' . $variable_repair_field . '(' . $field_id_old . ')', ($field_id_new != '') ? '#' . $variable_repair_field . '(' . $field_id_new . ')' : '', $condition->value);
									$condition->value = str_replace('#' . $variable_repair_field . '(' . $field_id_old . ',', ($field_id_new != '') ? '#' . $variable_repair_field . '(' . $field_id_new . ',' : '', $condition->value);
								}
							}
						}

						// String replace - Section
						foreach($this->new_lookup['section'] as $section_id_old => $section_id_new) {

							if(isset($condition->value)) {

								$condition->value = str_replace('#section_row_count(' . $section_id_old . ')', ($section_id_new != '') ? '#section_row_count(' . $section_id_new . ')' : '', $condition->value);
							}
						}
					}
				}

				// Process THEN actions
				$then = $conditional_json_decode->then;

				// Run through each group in $then
				foreach($then as $key_then => $then_single) {

					if(isset($then_single->object) && isset($this->new_lookup[$then_single->object]) && isset($this->new_lookup[$then_single->object][$then_single->object_id])) {
						$then_single->object_id = $this->new_lookup[$then_single->object][$then_single->object_id];
					}

					// String replace - Field
					foreach($this->new_lookup['field'] as $field_id_old => $field_id_new) {

						if(isset($then_single->value)) {

							foreach($this->variable_repair_field_array as $variable_repair_field) {

								$then_single->value = str_replace('#' . $variable_repair_field . '(' . $field_id_old . ')', ($field_id_new != '') ? '#' . $variable_repair_field . '(' . $field_id_new . ')' : '', $then_single->value);
								$then_single->value = str_replace('#' . $variable_repair_field . '(' . $field_id_old . ',', ($field_id_new != '') ? '#' . $variable_repair_field . '(' . $field_id_new . ',' : '', $then_single->value);
							}
						}
					}

					// String replace - Section
					foreach($this->new_lookup['section'] as $section_id_old => $section_id_new) {

						if(isset($then_single->value)) {

							$then_single->value = str_replace('#section_row_count(' . $section_id_old . ')', ($section_id_new != '') ? '#section_row_count(' . $section_id_new . ')' : '', $then_single->value);
						}
					}
				}

				// Process ELSE actions
				$else = $conditional_json_decode->else;

				// Run through each group in $else
				foreach($else as $key_else => $else_single) {

					if(isset($else_single->object) && isset($this->new_lookup[$else_single->object]) && isset($this->new_lookup[$else_single->object][$else_single->object_id])) {
						$else_single->object_id = $this->new_lookup[$else_single->object][$else_single->object_id];
					}

					// String replace - Field
					foreach($this->new_lookup['field'] as $field_id_old => $field_id_new) {

						if(isset($else_single->value)) {

							foreach($this->variable_repair_field_array as $variable_repair_field) {

								$else_single->value = str_replace('#' . $variable_repair_field . '(' . $field_id_old . ')', ($field_id_new != '') ? '#' . $variable_repair_field . '(' . $field_id_new . ')' : '', $else_single->value);
								$else_single->value = str_replace('#' . $variable_repair_field . '(' . $field_id_old . ',', ($field_id_new != '') ? '#' . $variable_repair_field . '(' . $field_id_new . ',' : '', $else_single->value);
							}
						}
					}

					// String replace - Section
					foreach($this->new_lookup['section'] as $section_id_old => $section_id_new) {

						if(isset($else_single->value)) {

							$else_single->value = str_replace('#section_row_count(' . $section_id_old . ')', ($section_id_new != '') ? '#section_row_count(' . $section_id_new . ')' : '', $else_single->value);
						}
					}
				}

				// Write conditional
				$conditional_json_encode = wp_json_encode($conditional_json_decode);
				$conditional->groups[0]->rows[$row_index]->data[1] = $conditional_json_encode;
				$meta_data_array = array('conditional' => $conditional);
				$ws_form_meta->db_update_from_array($meta_data_array);
			}
		}

		// Action repair (Repairs a duplicated action and replaces ID's with new_lookup values)
		public function db_action_repair() {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Check form ID
			self::db_check_id();

			// Read action
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'form';
			$ws_form_meta->parent_id = $this->id;
			$action = $ws_form_meta->db_get_object_meta('action');

			// Data integrity check
			if(!isset($action->groups)) { return true; }
			if(!isset($action->groups[0])) { return true; }
			if(!isset($action->groups[0]->rows)) { return true; }

			// Run through each action (data grid rows)
			$rows = $action->groups[0]->rows;

			foreach($rows as $row_index => $row) {

				// Data integrity check
				if(!isset($row->data)) { continue; }
				if(!isset($row->data[1])) { continue; }

				$data = $row->data[1];

				// Data integrity check
				if(gettype($data) !== 'string') { continue; }
				if($data == '') { continue; }

				// Converts action JSON string to object
				$action_json_decode = json_decode($data);
				if(is_null($action_json_decode)) { continue; }

				$action_id = $action_json_decode->id;

				// Skip actions that are not installed
				if(!isset(WS_Form_Action::$actions[$action_id])) { continue; }

				// Process metas
				$metas = $action_json_decode->meta;

				// Run through each meta
				foreach($metas as $meta_key => $meta_value) {

					if(is_array($meta_value)) {

						foreach($meta_value as $repeater_key => $repeater_row) {

							if(isset($repeater_row->ws_form_field)) {

								$ws_form_field = $repeater_row->ws_form_field;

								if(isset($this->new_lookup['field']) && isset($this->new_lookup['field'][$ws_form_field])) {

									$metas->{$meta_key}[$repeater_key]->ws_form_field = $this->new_lookup['field'][$ws_form_field];
								}
							}

							foreach($repeater_row as $key => $value) {

								// String replace - Field
								foreach($this->new_lookup['field'] as $field_id_old => $field_id_new) {

									foreach($this->variable_repair_field_array as $variable_repair_field) {

										$metas->{$meta_key}[$repeater_key]->{$key} = str_replace('#' . $variable_repair_field . '(' . $field_id_old . ')', ($field_id_new != '') ? '#' . $variable_repair_field . '(' . $field_id_new . ')' : '',$metas->{$meta_key}[$repeater_key]->{$key});
										$metas->{$meta_key}[$repeater_key]->{$key} = str_replace('#' . $variable_repair_field . '(' . $field_id_old . ',', ($field_id_new != '') ? '#' . $variable_repair_field . '(' . $field_id_new . ',' : '',$metas->{$meta_key}[$repeater_key]->{$key});
									}
								}

								// String replace - Section
								foreach($this->new_lookup['section'] as $section_id_old => $section_id_new) {

									$metas->{$meta_key}[$repeater_key]->{$key} = str_replace('#section_row_count(' . $section_id_old . ')', ($section_id_new != '') ? '#section_row_count(' . $section_id_new . ')' : '', $metas->{$meta_key}[$repeater_key]->{$key});
								}
							}
						}

					} else {

						if(isset($this->new_lookup['field']) && isset($this->new_lookup['field'][$meta_value])) {
							$metas->{$meta_key} = $this->new_lookup['field'][$meta_value];
						}

						// String replace - Field
						foreach($this->new_lookup['field'] as $field_id_old => $field_id_new) {

							foreach($this->variable_repair_field_array as $variable_repair_field) {

								$metas->{$meta_key} = str_replace('#' . $variable_repair_field . '(' . $field_id_old . ')', ($field_id_new != '') ? '#' . $variable_repair_field . '(' . $field_id_new . ')' : '', $metas->{$meta_key});
								$metas->{$meta_key} = str_replace('#' . $variable_repair_field . '(' . $field_id_old . ',', ($field_id_new != '') ? '#' . $variable_repair_field . '(' . $field_id_new . ',' : '', $metas->{$meta_key});
							}
						}

						// String replace - Section
						foreach($this->new_lookup['section'] as $section_id_old => $section_id_new) {

							$metas->{$meta_key} = str_replace('#section_row_count(' . $section_id_old . ')', ($section_id_new != '') ? '#section_row_count(' . $section_id_new . ')' : '', $metas->{$meta_key});
						}
					}
				}

				// Write action
				$action_json_encode = wp_json_encode($action_json_decode);
				$action->groups[0]->rows[$row_index]->data[1] = $action_json_encode;
				$meta_data_array = array('action' => $action);
				$ws_form_meta->db_update_from_array($meta_data_array);
			}
		}

		// Meta repair - Update any field references in meta data
		public function db_meta_repair() {

			// Get form object
			$form_object = self::db_read(true, true);

 			// Get form fields
			$fields = WS_Form_Common::get_fields_from_form($form_object, true);
			if(count($fields) == 0) { return; }

			// Get field meta
			$meta_keys = WS_Form_Config::get_meta_keys();

			// Look for field meta that uses fields for option lists, and also repeater fields
			$meta_key_check = array();
			foreach($meta_keys as $meta_key => $meta_key_config) {

				// Check for meta_keys that contain #section_id
				if(isset($meta_key_config['default']) && ($meta_key_config['default'] === '#section_id')) {

					$meta_key_check[$meta_key] = array('repeater' => false, 'section_id' => true, 'meta_key' => $meta_key);
					continue;
				}

				// Check for meta_keys that use field for options
				if(isset($meta_key_config['options']) && ($meta_key_config['options'] === 'fields')) {

					$meta_key_check[$meta_key] = array('repeater' => false, 'section_id' => false, 'meta_key' => $meta_key);
					continue;
				}

				// Check for meta_keys that use fields for repeater fields
				if(isset($meta_key_config['type']) && ($meta_key_config['type'] === 'repeater')) {

					if(!isset($meta_key_config['meta_keys'])) { continue; }

					foreach($meta_key_config['meta_keys'] as $meta_key_repeater) {

						if(!isset($meta_keys[$meta_key_repeater])) { continue; }

						$meta_key_repeater_config = $meta_keys[$meta_key_repeater];

						if(isset($meta_key_repeater_config['options']) && ($meta_key_repeater_config['options'] === 'fields')) {

							$meta_key_check[$meta_key] = array('repeater' => true, 'section_id' => false, 'meta_key' => $meta_key_repeater);
							continue;
						}
					}
				}
			}

			// Run through each field and look for these meta keys
			foreach($fields as $field) {

				// Get field meta as array
				$field_meta = (array) $field->meta;
				if(count($field_meta) == 0) { continue; }

				$field_meta_update = false;

				// Find meta keys that contain only field numbers to make sure we don't update other numeric values
				$keys_to_process = array_intersect_key($field_meta, $meta_key_check);
				foreach($keys_to_process as $meta_key => $meta_value) {

					// Check for repeater
					$repeater = $meta_key_check[$meta_key]['repeater'];
					if($repeater) {

						$repeater_meta_key = $meta_key_check[$meta_key]['meta_key'];

						foreach($field_meta[$meta_key] as $repeater_index => $repeater_row) {

							$meta_value = intval($field_meta[$meta_key][$repeater_index]->{$repeater_meta_key});

							if(isset($this->new_lookup['field']) && isset($this->new_lookup['field'][$meta_value])) {

								$field_meta[$meta_key][$repeater_index]->{$repeater_meta_key} = $this->new_lookup['field'][$meta_value];
								$field_meta_update = true;
							}
						}
					}

					// Check for section_id
					$section_id = $meta_key_check[$meta_key]['section_id'];
					if($section_id) {

						$section_id_meta_key = $meta_key_check[$meta_key]['meta_key'];
						$section_id_old = $field_meta[$section_id_meta_key];
						if(isset($this->new_lookup['section']) && isset($this->new_lookup['section'][$section_id_old])) {

							$field_meta[$section_id_meta_key] = $this->new_lookup['section'][$section_id_old];
							$field_meta_update = true;
						}
					}

					$meta_value = intval($field_meta[$meta_key]);

					if(isset($this->new_lookup['field']) && isset($this->new_lookup['field'][$meta_value])) {

						$field_meta[$meta_key] = $this->new_lookup['field'][$meta_value];
						$field_meta_update = true;
					}
				}

				// Variable replace
				foreach($this->new_lookup['field'] as $field_id_old => $field_id_new) {

					foreach($this->variable_repair_field_array as $variable_repair_field) {

						$field_meta = str_replace('#' . $variable_repair_field . '(' . $field_id_old . ')', ($field_id_new != '') ? '#' . $variable_repair_field . '(' . $field_id_new . ')' : '', $field_meta, $counter);
						if($counter > 0) { $field_meta_update = true; }

						$field_meta = str_replace('#' . $variable_repair_field . '(' . $field_id_old . ',', ($field_id_new != '') ? '#' . $variable_repair_field . '(' . $field_id_new . ',' : '', $field_meta, $counter);
						if($counter > 0) { $field_meta_update = true; }
					}
				}

				foreach($this->new_lookup['section'] as $section_id_old => $section_id_new) {

					$field_meta = str_replace('#section_row_count(' . $section_id_old . ')', ($section_id_new != '') ? '#section_row_count(' . $section_id_new . ')' : '', $field_meta, $counter);
					if($counter > 0) { $field_meta_update = true; }
				}

				// Update meta data
				if($field_meta_update) {

					// Update meta data
					$ws_form_meta = new WS_Form_Meta();
					$ws_form_meta->object = 'field';
					$ws_form_meta->parent_id = $field->id;
					$ws_form_meta->db_update_from_array($field_meta);
				}
			}
		}

		// Get form to preview
		public function db_get_preview_form_id() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			global $wpdb;

			// Get contents of published field
			$sql = sprintf("SELECT id FROM %s ORDER BY date_updated DESC LIMIT 1;", $this->table_name);
			$form_id = $wpdb->get_Var($sql);

			if(is_null($form_id)) { return 0; } else { return $form_id; }
		}

		// Get form label
		public function db_get_label() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			return parent::db_object_get_label($this->table_name, $this->id);
		}

		// Check id
		public function db_check_id() {

			if($this->id <= 0) { parent::db_throw_error(__('Invalid form ID', 'ws-form')); }
			return true;
		}

		// API - POST - Download - JSON
		public function db_download_json($published = false) {

			// User capability check
			if(!$published && !WS_Form_Common::can_user('export_form')) { parent::api_access_denied(); }

			// Check form ID
			self::db_check_id();

			// Get form
			if($published) {

				$form = self::db_read_published();

			} else {

				$form = self::db_read(true, true);
			}

			// Clean form
			unset($form->checksum);
			unset($form->published_checksum);

			// Stamp form data
			$form->identifier = WS_FORM_IDENTIFIER;
			$form->version = WS_FORM_VERSION;
			$form->time = time();
			$form->status = 'draft';
			$form->count_submit = 0;

			// Add checksum
			$form->checksum = md5(json_encode($form));

			// Build filename
			$filename = 'ws-form-' . strtolower($form->label) . '.json';

			// HTTP headers
			WS_Form_Common::file_download_headers($filename, 'application/octet-stream');

			// Output JSON
			echo wp_json_encode($form);
			
			exit;
		}

		// Find pages a form is embedded on
		public function db_get_locations() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { parent::api_access_denied(); }

			// Return array
			$form_to_post_array = array();

			// Get post types
			$post_types_exclude = array('attachment');
			$post_types = get_post_types(array('show_in_menu' => true), 'objects', 'or');
			$args_post_types = array();

			foreach($post_types as $post_type) {

				$post_type_name = $post_type->name;

				if(in_array($post_type_name, $post_types_exclude)) { continue; }

				$args_post_types[] = $post_type_name;
			}

			// Post types
			$args = array(

				'post_type' 		=> $args_post_types,
				'posts_per_page' 	=> -1
			);

			// Apply filter
			$args = apply_filters('wsf_get_locations_args', $args);

			// Get posts
			$posts = get_posts($args);

			// Run through each post
			foreach($posts as $post) {

				// Look for forms in the post content
				$form_id_array = self::find_shortcode_in_string($post->post_content);

				// Run filter
				$form_id_array = apply_filters('wsf_get_locations_post', $form_id_array, $post, $this->id);

				if(count($form_id_array) > 0) {

					foreach($form_id_array as $form_id) {

						if(
							($this->id > 0) &&
							($this->id != $form_id)
						) {

							continue;
						}

						// Get post type
						$post_type = get_post_type_object($post->post_type);

						// If found, register in the return array
						if(!isset($form_to_post_array[$form_id])) { $form_to_post_array[$form_id] = array(); }
						if(!isset($form_to_post_array[$form_id][$post->post_type . '-' . $post->ID])) {

							$form_to_post_array[$form_id][$post->post_type . '-' . $post->ID] = array(

								'id'		=> $post->ID,
								'type'		=> $post->post_type,
								'type_name'	=> $post_type->labels->singular_name,
								'title'		=> (empty($post->post_title) ? $post->ID : $post->post_title)
							);
						}
					}
				}
			}

			// Get registered sidebars
			global $wp_registered_sidebars;

			// Get current widgets
			$sidebars_widgets = get_option('sidebars_widgets');
			$wsform_widgets = get_option('widget_' . WS_FORM_WIDGET);

			if($sidebars_widgets !== false) {

				// Run through each widget
				foreach($sidebars_widgets as $sidebars_widget_id => $sidebars_widget) {

					if(!is_array($sidebars_widget)) { continue; }

					// Check if the sidebar exists
					if(!isset($wp_registered_sidebars[$sidebars_widget_id])) { continue; }
					if(!isset($wp_registered_sidebars[$sidebars_widget_id]['name'])) { continue; }

					foreach($sidebars_widget as $setting) {

						// Is this a WS Form widget?
						if(strpos($setting, WS_FORM_WIDGET) !== 0) { continue; }

						// Get widget instance
						$setting_array = explode('-', $setting);
						if(!isset($setting_array[1])) { continue; }
						$widget_instance = intval($setting_array[1]);

						// Check if that widget instance is valid
						if(!isset($wsform_widgets[$widget_instance])) { continue; }
						if(!isset($wsform_widgets[$widget_instance]['form_id'])) { continue; }

						// Get form ID used by widget ID
						$form_id = intval($wsform_widgets[$widget_instance]['form_id']);
						if($form_id === 0) { continue; }

						if(
							($this->id > 0) &&
							($this->id !== $form_id)
						) {

							continue;
						}

						// If found, register in the return array
						if(!isset($form_to_post_array[$form_id])) { $form_to_post_array[$form_id] = array(); }
						if(!isset($form_to_post_array[$form_id]['widget-' . $sidebars_widget_id])) {

							$form_to_post_array[$form_id]['widget-' . $sidebars_widget_id] = array(

								'id'		=> $sidebars_widget_id,
								'type'		=> 'widget',
								'type_name'	=> __('Widget', 'ws-form'),
								'title'		=> $wp_registered_sidebars[$sidebars_widget_id]['name']
							);
						}
					}
				}
			}

			return $form_to_post_array;
		}

		// Find WS Form shortcodes or Gutenberg blocks in a string
		public function find_shortcode_in_string($input) {

			$form_id_array = array();

			// Gutenberg block search
			if(function_exists('parse_blocks')) {

				$parse_blocks = parse_blocks($input);
				foreach($parse_blocks as $parse_block) {

					if(!isset($parse_block['blockName'])) { continue; }
					if(!isset($parse_block['attrs'])) { continue; }
					if(!isset($parse_block['attrs']['form_id'])) { continue; }

					$block_name = $parse_block['blockName'];

					if(strpos($block_name, 'wsf-block/') === 0) {

						$form_id_array[] = intval($parse_block['attrs']['form_id']);
					}
				}
			}

			// Shortcode search
			$has_shortcode = has_shortcode($input, WS_FORM_SHORTCODE);

			$pattern = get_shortcode_regex();
			if(
				preg_match_all('/'. $pattern .'/s', $input, $matches) &&
				array_key_exists(2, $matches) &&
				in_array(WS_FORM_SHORTCODE, $matches[2])
			) {

				foreach( $matches[0] as $key => $value) {

					$get = str_replace(" ", "&" , $matches[3][$key] );
			        parse_str($get, $output);

			        if(isset($output['id'])) {

			        	$form_id_array[] = (int) filter_var($output['id'], FILTER_SANITIZE_NUMBER_INT);
					}
				}
			}

			return $form_id_array;
		}

		public function get_svg($published = false) {

			self::db_check_id();

			try {

				if($published) {

					// Published
					$form_object = self::db_read_published();

				} else {

					// Draft
					$form_object = self::db_read(true, true);
				}

			} catch(Exception $e) { return false; }

			return self::get_svg_from_form_object($form_object, true);
		}

		// Get SVG of form
		public function get_svg_from_form_object($form_object, $label = false) {

			// SVG defaults
			$svg_width = 140;
			$svg_height = 180;

			// Get form column count
			$svg_columns = intval(WS_Form_Common::option_get('framework_column_count', 0));
			if($svg_columns == 0) { self::db_throw_error(__('Invalid framework column count', 'ws-form')); }

			// Read skin
			$skin_color_default = WS_Form_Common::option_get('skin_color_default');
			$skin_color_default_inverted = WS_Form_Common::option_get('skin_color_default_inverted');
			$skin_color_default_light = WS_Form_Common::option_get('skin_color_default_light');
			$skin_color_default_lighter = WS_Form_Common::option_get('skin_color_default_lighter');
			$skin_color_default_lightest = WS_Form_Common::option_get('skin_color_default_lightest');
			$skin_color_primary = WS_Form_Common::option_get('skin_color_primary');
			$skin_color_success = WS_Form_Common::option_get('skin_color_success');
			$skin_color_danger = WS_Form_Common::option_get('skin_color_danger');
			$skin_border_radius = floatval(WS_Form_Common::option_get('skin_border_radius'));
			if($skin_border_radius > 0) { $skin_border_radius = ($skin_border_radius / 4); }
			$skin_grid_gutter = floatval(WS_Form_Common::option_get('skin_grid_gutter'));
			if($skin_grid_gutter > 0) { $skin_grid_gutter = ($skin_grid_gutter / 4); }

			// Columns
			$col_index_max = $svg_columns;
			$col_width = 10.8333;

			// Rows
			$row_spacing = $skin_grid_gutter;

			// Gutter
			$gutter_width = $skin_grid_gutter;

			// Fields
			$field_height = 8;
			$field_adjust_x = -0.17;

			// Labels
			$label_font_size = 6;
			$label_margin_bottom = 2;
			$label_offset_y = 0;
			$label_margin_x = 2;
			$label_margin_y = 1;
			$label_inside_y = ($field_height / 2) + ($label_font_size / 2) - 1;

			// Origin
			$origin_x = ($col_width / 2);
			$origin_y = 27;

			// Offset
			$offset_x = $origin_x;
			$offset_y = $origin_y;

			// Gradient
			$gradient_height = 20;

			$row_height_max = 0;

			// Get form fields
			$fields = array();
			foreach($form_object->groups as $group) {

				foreach($group->sections as $section) {

					foreach($section->fields as $field) {

						// Get field size
						$field_size_columns = intval((isset($field->meta->breakpoint_size_25)) ? $field->meta->breakpoint_size_25 : 12);

						// Get field offset
						$field_offset_columns = intval((isset($field->meta->breakpoint_offset_25)) ? $field->meta->breakpoint_offset_25 : 0);

						// Add to fields
						$fields[] = array(

							'label'				=>	$field->label,
							'label_render'		=>	WS_Form_Common::get_object_meta_value($field, 'label_render', false),
							'required'			=>	WS_Form_Common::get_object_meta_value($field, 'required', false),
							'type'				=>	$field->type,
							'size'				=>	$field_size_columns,
							'offset'			=>	$field_offset_columns,
							'object'			=>	$field
						);
					}
				}

				// Skip other groups (tabs)
				break;
			}

			$field_type_buttons = apply_filters('wsf_wizard_svg_buttons', array(

				'submit' => array('fill' => $skin_color_primary, 'color' => $skin_color_default_inverted),
				'save' => array('fill' => $skin_color_success, 'color' => $skin_color_default_inverted),
				'clear' => array('fill' => $skin_color_default_lighter, 'color' => $skin_color_default),
				'reset' => array('fill' => $skin_color_default_lighter, 'color' => $skin_color_default),
				'tab_previous' => array('fill' => $skin_color_default_lighter, 'color' => $skin_color_default),
				'tab_next' => array('fill' => $skin_color_default_lighter, 'color' => $skin_color_default),
				'button' => array('fill' => $skin_color_default_lighter, 'color' => $skin_color_default),
				'section_add' => array('fill' => $skin_color_default_lighter, 'color' => $skin_color_default),
				'section_delete' => array('fill' => $skin_color_danger, 'color' => $skin_color_default_inverted),
				'section_up' => array('fill' => $skin_color_default_lighter, 'color' => $skin_color_default),
				'section_down' => array('fill' => $skin_color_default_lighter, 'color' => $skin_color_default)
			));

			$field_type_price_span = apply_filters('wsf_wizard_svg_price_span', array());

			// Build SVG
			$svg = sprintf(
				'<svg xmlns="http://www.w3.org/2000/svg" class="wsf-responsive" viewBox="0 0 %u %u"><rect height="100%%" width="100%%" fill="#FFFFFF"/>',
				$svg_width,
				$svg_height
			);

			// Definitions
			$svg .= '<defs>';

			// Gradient ID
			$gradient_id = 'wsf-wizard-bottom' . (isset($form_object->checksum) ? '-' . $form_object->checksum : '');

			// Definitions - Gradient - Bottom
			$svg .= '<linearGradient id="' . $gradient_id . '" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" style="stop-color:rgb(255,255,255);stop-opacity:0" /><stop offset="100%" style="stop-color:rgb(255,255,255);stop-opacity:1" /></linearGradient>';

			$svg .= '</defs>';

			// Label
			$svg .= sprintf('<text fill="%s" class="wsf-wizard-title"><tspan x="%u" y="16">%s</tspan></text>',
				$skin_color_default,
				is_rtl() ? ($svg_width - 5) : 5,
				(($label !== false) ? $form_object->label : '#label')
			);

			// Process each field
			$col_index = 0;
			$svg_array = array();
			$label_found = false;
			foreach($fields as $field) {

				// Field size and offset
				$field_size_columns = ($field['size'] > 0) ? $field['size'] : $svg_columns;
				$field_offset_columns = ($field['offset'] > 0) ? $field['offset'] : 0;

				// Field width
				$field_cols = ($col_index_max / $field_size_columns);
				$field_width = ($field_size_columns * $col_width) - (($field_cols > 1) ? ((1 - (1 / $field_cols)) * $gutter_width) : 0);

				// Field offset width
				$field_cols_offset = ($field_offset_columns > 0) ? ($col_index_max / $field_offset_columns) : 0;
				$field_width_offset = ($field_cols_offset > 0) ? ($field_offset_columns * $col_width) - (($field_cols_offset > 1) ? ((1 - (1 / $field_cols_offset)) * $gutter_width) - $gutter_width : 0) : 0;

				// Field - X
				if(is_rtl()) {

					$field_x = $svg_width - (($offset_x + $field_adjust_x) + $field_width_offset + $field_width);

				} else {

					$field_x = ($offset_x + $field_adjust_x) + $field_width_offset;
				}

				// Label - X
				if(is_rtl()) {

					$label_x = $field_x + $field_width;

				} else {

					$label_x = $field_x;
				}

				// Process by field type

				// Buttons
				if(isset($field_type_buttons[$field['type']])) {

					$label_button_x = $field_x + ($field_width / 2);
					$button_fill = $field_type_buttons[$field['type']]['fill'];
					$button_fill_label = $field_type_buttons[$field['type']]['color'];

					// Button - Rectangle
					$svg_field = '<rect x="' . $field_x . '" y="0" fill="' . $button_fill . '" stroke="' . $button_fill . '" rx="' . $skin_border_radius . '" width="' . $field_width . '" height="' . $field_height . '"/>';

					// Button - Label
					$svg_field .= '<text transform="translate(' . $label_button_x . ',' . $label_inside_y . ')" class="wsf-wizard-label" fill="' . $button_fill_label . '" text-anchor="middle">' . $field['label'] . '</text>';

					// Add to SVG array
					$svg_array[] = array('svg' => $svg_field, 'height' => $field_height);

				} elseif (isset($field_type_price_span[$field['type']])) {

					// Price Span - Rectangle
					$svg_field = '<rect x="' . $field_x . '" y="0" fill="' . $skin_color_default_inverted . '" stroke="' . $skin_color_default_lighter . '" stroke-dasharray="2 1" rx="' . $skin_border_radius . '" width="' . $field_width . '" height="' . $field_height . '"/>';

					// Price Span - Label
					$svg_field .= '<text fill="' . $skin_color_default . '" transform="translate(' . $label_x . ',' . $label_inside_y . ')" class="wsf-wizard-label">' . $field['label'] . '</text>';

					// Add to SVG array
					$svg_array[] = array('svg' => $svg_field, 'height' => $field_height);

				} else {

					// Render label
					$label_render = $field['label_render'];
					$label_offset_x = 0;

					// Move/force label if inline with an SVG element
					switch($field['type']) {

						case 'checkbox' :
						case 'price_checkbox' :
						case 'radio' :
						case 'price_radio' :

							$label_render = true;
							$label_offset_x = is_rtl() ? ($field_height + $label_margin_x) * -1 : ($field_height + $label_margin_x);
							break;
					}

					if($label_render) {

						// Label (Origin is bottom left of text)
						$svg_field = '<text fill="' . $skin_color_default . '" transform="translate(' . ($label_x + $label_offset_x) . ',' . $label_font_size . ')" class="wsf-wizard-label">' . $field['label'] . ($field['required'] ? '<tspan fill="' . $skin_color_danger . '"> *</tspan>' : '') . '</text>';
						$label_offset_y = $label_font_size + $label_margin_bottom;
						$label_found = true;

					} else {

						$svg_field = '';
						$label_offset_y = 0;
					}

					// Process by type
					switch($field['type']) {

						case 'progress' :

							// Progress - Random width
							$progress_width = rand($field_width / 6, $field_width - ($field_width / 6));

							// Progress - Rectangle - Outer
							$svg_field .= '<rect x="' . $field_x . '" y="' . $label_offset_y . '" fill="' . $skin_color_default_lighter . '" stroke="' . $skin_color_default_lighter . '" rx="' . $skin_border_radius . '" width="' . $field_width . '" height="' . ($field_height / 2) . '"/>';

							// Progress - Rectangle - Inner
							$svg_field .= '<rect x="' . (is_rtl() ? ($field_x + $field_width - $progress_width) : $field_x) . '" y="' . $label_offset_y . '" fill="' . $skin_color_primary . '" stroke="' . $skin_color_primary . '" rx="' . $skin_border_radius . '" width="' . $progress_width . '" height="' . ($field_height / 2) . '"/>';

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $label_offset_y + ($field_height / 2));

							break;

						case 'range' :
						case 'price_range' :

							// Range - Random x position
							$range_x = rand($field_width / 6, $field_width - ($field_width / 6));

							// Range - Rectangle
							$svg_field .= '<rect x="' . $field_x . '" y="' . (($label_offset_y + ($field_height / 2)) - 1) . '" fill="' . $skin_color_default_lightest . '" rx="' . $skin_border_radius . '" width="' . $field_width . '" height="' . ($field_height / 4) . '"/>';

							// Range - Circle (Slider)
							$svg_field .= '<circle cx="' . ($field_x + $range_x) . '" cy="' . ($label_offset_y + ($field_height / 2)) . '" r="' . ($field_height / 2) . '" fill="' . $skin_color_primary . '"/>
							';

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $label_offset_y + $field_height);

							break;

						case 'textarea' :

							// Textarea - Rectangle
							$svg_field .= '<rect x="' . $field_x . '" y="' . $label_offset_y . '" fill="' . $skin_color_default_inverted . '" stroke="' . $skin_color_default_lighter . '" rx="' . $skin_border_radius . '" width="' . $field_width . '" height="' . ($field_height * 2) . '"/>';

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $label_offset_y + ($field_height * 2));

							break;

						case 'signature' :

							// Signature - Rectangle
							$svg_field .= '<rect x="' . $field_x . '" y="' . $label_offset_y . '" fill="' . $skin_color_default_inverted . '" stroke="' . $skin_color_default_lighter . '" rx="' . $skin_border_radius . '" width="' . $field_width . '" height="' . ($field_height * 2) . '"/>';

							// Signature - Icon
							$svg_field .= '<path transform="translate(' . ($field_x + (is_rtl() ? ($field_width - 16) : 3)) . ',' . ($label_offset_y + 2) . ') scale(0.75)" fill="' . $skin_color_default_lighter . '" d="M13.3 3.9l-.6-.2a1 1 0 00-.6.2c-1 .8-1.7 1.8-2.1 3-.3.6-.4 1.2-.3 1.7.9-.3 1.8-.7 2.5-1.3.8-.6 1.3-1.4 1.5-2.3v-.6l-.4-.5zM0 12.4h15.6v1.2H0v-1.2zM2.1 8l1.3-1.3.8.8-1.3 1.3 1.3 1.3-.8.8-1.3-1.3-1.3 1.3-.8-.8 1.3-1.3L0 7.5l.8-.8L2.1 8zm13.6 2.8v.4h-1.2l-.1-.7c-.3-.2-.9-.1-1.8.2l-.4.1c-.6.2-1.2.2-1.8.1-.6-.1-1.1-.5-1.5-1-.9.2-2 .2-3.5.2V8.9l3.1-.1c-.1-.7 0-1.5.2-2.3.3-.8.7-1.5 1.2-2.2.5-.7 1.1-1.2 1.7-1.5.8-.5 1.6-.4 2.4.2.4.3.7.8.9 1.4.1.3 0 .6-.1 1s-.3.9-.6 1.3c-.3.5-.7.9-1.1 1.2-.8.7-1.8 1.2-2.8 1.6.5.3 1.2.3 1.8.1l1-.3c.7-.1 1.2-.1 1.6 0 .5.2.7.4.8.8l.2.7z"/>';

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $label_offset_y + ($field_height * 2));

							break;

						case 'rating' :

							$rating_color_on = WS_Form_Common::get_object_meta_value($field['object'], 'rating_color_on', '#FFCC00');
							$rating_color_off = WS_Form_Common::get_object_meta_value($field['object'], 'rating_color_off', '#CECED2');

							// Rating
							for($rating_index = 0; $rating_index < 5; $rating_index++) {

								$field_rating_offset_x = ($rating_index * 9);

								$rating_color = ($rating_index < 3) ? $rating_color_on : $rating_color_off;

								$svg_field .= '<path transform="translate(' . ($field_x + (is_rtl() ? $field_width - 8 - ($field_rating_offset_x) : $field_rating_offset_x)) . ',' . ($label_offset_y) . ') scale(0.5)" d="M12.9 15.8c-1.6-1.2-3.2-2.5-4.9-3.7-1.6 1.3-3.3 2.5-4.9 3.7 0 0-.1 0-.1-.1.6-2 1.2-4 1.9-6C3.3 8.4 1.7 7.2 0 5.9h6C6.7 3.9 7.3 2 8 0h.1c.7 1.9 1.3 3.9 2 5.9H16V6c-1.6 1.3-3.2 2.5-4.9 3.8.6 1.9 1.3 3.9 1.8 6 .1-.1 0 0 0 0z" fill="' . $rating_color . '" />';
							}

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $label_offset_y + $field_height);

							break;



						case 'texteditor' :
						case 'html' :
						case 'message' :

							// Rectangle
							$svg_field .= '<rect x="' . $field_x . '" y="' . $label_offset_y . '" fill="' . $skin_color_default_inverted . '" stroke="' . $skin_color_default_lighter . '" stroke-dasharray="2 1" rx="' . $skin_border_radius . '" width="' . $field_width . '" height="' . ($field_height * 2) . '"/>';

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $label_offset_y + ($field_height * 2));

							break;

						case 'divider' :

							// Divider - Line
							$svg_field .= '<line x1="' . $field_x . '" x2="' . ($field_x + $field_width) . '" y1="' . ($label_offset_y + ($field_height / 2)) . '" y2="' . ($label_offset_y + ($field_height / 2)) . '" stroke="' . $skin_color_default_lighter . '"/>';

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $label_offset_y + $field_height);

							break;

						case 'hidden' :

							// Add to SVG array
							$svg_array[] = array('svg' => '', 'height' => 0);

							break;

						case 'spacer' :

							// Add to SVG array
							$svg_array[] = array('svg' => '', 'height' => $field_height);

							break;

						case 'section_icons' :

							// Section Icons - Path +
							$svg_field .= '<path transform="translate(' . ($field_x + (is_rtl() ? ($field_height + 3) : 0)) . ',' . $label_offset_y . ')" d="M7.7,1.3A4.82,4.82,0,0,0,4.5,0,4.82,4.82,0,0,0,1.3,1.3,4.22,4.22,0,0,0,0,4.5,4.82,4.82,0,0,0,1.3,7.7,4.22,4.22,0,0,0,4.5,9,4.82,4.82,0,0,0,7.7,7.7,4.22,4.22,0,0,0,9,4.5,4.82,4.82,0,0,0,7.7,1.3Zm-3.2,7A3.8,3.8,0,1,1,8.3,4.5,3.8,3.8,0,0,1,4.5,8.3Zm.4-4.2H6.5v.7H4.9V6.4H4.1V4.9H2.6V4.1H4.2V2.6h.7Z"/>';

							// Section Icons - Path -
							$svg_field .= '<path transform="translate(' . ($field_x + (is_rtl() ? 0 : ($field_height + 3))) . ',' . $label_offset_y . ')" d="M4.5,9A4.82,4.82,0,0,1,1.3,7.7,4.22,4.22,0,0,1,0,4.5,4.82,4.82,0,0,1,1.3,1.3,4.22,4.22,0,0,1,4.5,0,4.82,4.82,0,0,1,7.7,1.3,4.22,4.22,0,0,1,9,4.5,4.82,4.82,0,0,1,7.7,7.7,4.22,4.22,0,0,1,4.5,9ZM4.5.7A3.8,3.8,0,1,0,8.3,4.5,3.8,3.8,0,0,0,4.5.7ZM6.4,4.1H2.6v.7H6.5V4.1Z"/>';

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $label_offset_y + $field_height);

							break;

						case 'color' :

							// Color - Random Fill
							$rect_fill = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
							$rect_x = (is_rtl() ? ($field_x + $field_width - $field_height) : $field_x);

							// Default - Rectangle
							$svg_field .= '<rect x="' . $field_x . '" y="' . $label_offset_y . '" fill="' . $skin_color_default_inverted . '" stroke="' . $skin_color_default_lighter . '" rx="' . $skin_border_radius . '" width="' . $field_width . '" height="' . $field_height . '"/>';

							// Color - Rectangle
							$svg_field .= '<rect x="' . $rect_x . '" y="' . $label_offset_y . '" fill="' . $rect_fill . '" stroke="' . $skin_color_default_lighter . '" rx="' . $skin_border_radius . '" width="' . $field_height . '" height="' . $field_height . '"/>';

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $label_offset_y + $field_height);

							break;

						case 'checkbox' :
						case 'price_checkbox' :

							$rect_x = (is_rtl() ? ($svg_width - $field_x - $field_height) : $field_x);

							// Checkbox - Rectangle
							$svg_field .= '<rect x="' . $rect_x . '" y="0" fill="' . $skin_color_default_inverted . '" stroke="' . $skin_color_default_lighter . '" rx="' . $skin_border_radius . '" width="' . $field_height . '" height="' . $field_height . '"/>';

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $field_height);

							break;

						case 'radio' :
						case 'price_radio' :

							$circle_x = ((is_rtl() ? ($svg_width - $field_x - $field_height) : $field_x) + ($field_height / 2));

							// Radio - Circle
							$svg_field .= '<circle cx="' . $circle_x . '" cy="' . ($field_height / 2) . '" fill="' . $skin_color_default_inverted . '" stroke="' . $skin_color_default_lighter . '" r="' . ($field_height / 2) . '"/>';

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $field_height);

							break;

						case 'file' :

							$button_width = ($field_width / 4);
							$button_xpos = (is_rtl() ? $field_x : ($field_x + $field_width - $button_width));
							$label_button_x = $button_xpos + ($button_width / 2);

							// File - Rectangle - Outer
							$svg_field .= '<rect x="' . $field_x . '" y="' . $label_offset_y . '" fill="' . $skin_color_default_inverted . '" stroke="' . $skin_color_default_lighter . '" rx="' . $skin_border_radius . '" width="' . $field_width . '" height="' . $field_height . '"/>';

							// File - Rectangle - Button
							$svg_field .= '<rect x="' . $button_xpos . '" y="' . $label_offset_y . '" fill="' . $skin_color_default_lighter . '" stroke="' . $skin_color_default_lighter . '" rx="' . $skin_border_radius . '" width="' . ($field_width / 4) . '" height="' . $field_height . '"/>';

							// File - Text - Button
							$svg_field .= '<text fill="' . $skin_color_default . '" transform="translate(' . $label_button_x . ' ' . $label_inside_y . ')" class="wsf-wizard-label" text-anchor="middle">Browse</text>';

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $label_offset_y + $field_height);

							break;

						default :

							// Default - Rectangle
							$svg_field .= '<rect x="' . $field_x . '" y="' . $label_offset_y . '" fill="' . $skin_color_default_inverted . '" stroke="' . $skin_color_default_lighter . '" rx="' . $skin_border_radius . '" width="' . $field_width . '" height="' . $field_height . '"/>';

							// Add to SVG array
							$svg_array[] = array('svg' => $svg_field, 'height' => $label_offset_y + $field_height);
					}
				}

				// Col index
				$col_index += $field_size_columns + $field_offset_columns;
				if($col_index >= $col_index_max) {

					// Process row
					$get_svg_row_return = self::get_svg_row($svg_array);

					// Return row
					$svg .= sprintf('<g transform="translate(0,%f)">%s</g>', $offset_y, $get_svg_row_return['svg']);

					// Work out position of offset_x and offset_y
					$offset_y += $get_svg_row_return['height'] + $row_spacing;

					// Reset for next row
					$col_index = 0;
					$svg_array = array();
					$offset_x = $origin_x;

				} else {

					$offset_x += $field_width + $gutter_width;
				}
			}

			// Add last row
			if(count($svg_array) > 0) {

				// Process row
				$get_svg_row_return = self::get_svg_row($svg_array);

				// Return row
				$svg .= sprintf('<g transform="translate(0,%f)">%s</g>', $offset_y, $get_svg_row_return['svg']);
			}

			// Left rectangle
			$svg .= sprintf('<rect x="0" y="0" width="%u" height="%u" fill="#fff" />', $origin_x - 1, $svg_height);

			// Right rectangle
			$svg .= sprintf('<rect x="%f" y="0" width="%u" height="%u" fill="#fff" />', ($svg_width - $origin_x) + 1, $origin_x, $svg_height);

			// Bottom rectangles
			$svg .= sprintf('<rect x="0" y="%f" width="%u" height="%u" fill="url(#%s)" />', ($svg_height - $gradient_height - $origin_x), $svg_width, $gradient_height, $gradient_id);
			$svg .= sprintf('<rect x="0" y="%f" width="%u" height="%u" fill="#fff" />', ($svg_height - $origin_x), $svg_width, $origin_x + 1);

			// End of SVG
			$svg .= '</svg>';

			return $svg;
		}

		// Get SVG row
		public function get_svg_row($svg_array) {

			$svg = '';
			$height = 0;

			// Get overall height
			foreach($svg_array as $svg_field) {

				$svg_field_height = $svg_field['height'];

				if($svg_field_height > $height) { $height = $svg_field_height; }
			}

			// Build SVG
			foreach($svg_array as $svg_field) {

				$svg_field_svg = $svg_field['svg'];
				$svg_field_height = $svg_field['height'];

				$svg .= sprintf('<g transform="translate(0,%f)">%s</g>', ($height - $svg_field_height), $svg_field_svg);
			}

			return array('svg' => $svg, 'height' => $height);
		}
	}