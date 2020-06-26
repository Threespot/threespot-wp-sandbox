<?php

	class WS_Form_Group extends WS_Form_Core {

		public $id;
		public $form_id;
		public $new_lookup;
		public $label;
		public $meta;

		public $table_name;

		const DB_INSERT = 'label,user_id,date_added,date_updated,sort_index,form_id';
		const DB_UPDATE = 'label,user_id,date_updated';
		const DB_SELECT = 'label,sort_index,id';

		public function __construct() {

			global $wpdb;

			$this->id = 0;
			$this->form_id = 0;
			$this->table_name = $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . 'group';
			$this->new_lookup = array();
			$this->new_lookup['group'] = array();
			$this->new_lookup['section'] = array();
			$this->new_lookup['field'] = array();
			$this->label = WS_FORM_DEFAULT_GROUP_NAME;
			$this->meta = array();
		}

		// Create group
		public function db_create($next_sibling_id = 0, $create_section = true) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			self::db_check_form_id();

			// Process sort index
			$sort_index = self::db_object_sort_index_get($this->table_name, 'form_id', $this->form_id, $next_sibling_id);

			global $wpdb;

			// Add group
			$sql = sprintf("INSERT INTO %s (%s) VALUES ('%s', %u, '%s', '%s', %u, %u);", $this->table_name, self::DB_INSERT, esc_sql($this->label), WS_Form_Common::get_user_id(), WS_Form_Common::get_mysql_date(), WS_Form_Common::get_mysql_date(), $sort_index, $this->form_id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error adding group', 'ws-form')); }

			// Get inserted ID
			$this->id = $wpdb->insert_id;

			// Build meta data array
			$settings_form_admin = WS_Form_Config::get_settings_form_admin();
			$meta_data = $settings_form_admin['sidebars']['group']['meta'];
			$meta_keys = WS_Form_Config::get_meta_keys();
			$meta_data = self::build_meta_data($meta_data, $meta_keys);
			$meta_data = (object) array_merge($meta_data, (array) $this->meta);

			// Build meta data
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'group';
			$ws_form_meta->parent_id = $this->id;
			$ws_form_meta->db_update_from_object($meta_data);

			// Build first section
			if($create_section) {

				$ws_form_section = New WS_Form_Section();
				$ws_form_section->form_id = $this->form_id;
				$ws_form_section->group_id = $this->id;
				$ws_form_section->db_create();
			}

			return $this->id;
		}

		// Read record to array
		public function db_read($get_meta = true, $get_sections = false) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			global $wpdb;

			// Add fields
			$sql = sprintf("SELECT %s FROM %s WHERE id = %u LIMIT 1;", self::DB_SELECT, $this->table_name, $this->id);
			$group_array = $wpdb->get_row($sql, 'ARRAY_A');

			if($group_array === null) { parent::db_throw_error(__('Unable to read group', 'ws-form')); }

			foreach($group_array as $key => $value) {

				$this->{$key} = $value;
			}

			if($get_meta) {

				// Read meta
				$section_meta = New WS_Form_Meta();
				$section_meta->object = 'group';
				$section_meta->parent_id = $this->id;
				$metas = $section_meta->db_read_all();
				$this->meta = $group_array['meta'] = $metas;
			}

			if($get_sections) {

				// Read sections
				$ws_form_section = New WS_Form_Section();
				$ws_form_section->group_id = $this->id;
				$sections = $ws_form_section->db_read_all($get_meta);
				$this->sections = $group_array['sections'] = $sections;
			}

			// Convert into object
			$group_object = json_decode(json_encode($group_array));

			// Return array
			return $group_object;
		}

		// Check if record exists
		public function db_check() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			global $wpdb;

			$sql = sprintf("SELECT id FROM %s WHERE id = %u LIMIT 1;", $this->table_name, $this->id);
			$return_array = $wpdb->get_row($sql, 'ARRAY_A');

			if($return_array === null) { return false; } else { return true; }
		}

		// Read all group data
		public function db_read_all($get_meta = true, $checksum = false) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			self::db_check_form_id();

			global $wpdb;

			$fields_array = array();

			$sql = sprintf("SELECT %s FROM %s WHERE form_id = %u ORDER BY sort_index", self::DB_SELECT, $this->table_name, $this->form_id);
			$groups = $wpdb->get_results($sql, 'ARRAY_A');

			if($groups) {

				foreach($groups as $key => $group) {

					// Get sections
					$ws_form_section = New WS_Form_Section();
					$ws_form_section->group_id = $group['id'];
					$ws_form_section_return = $ws_form_section->db_read_all($get_meta, $checksum);
					$groups[$key]['sections'] = $ws_form_section_return;

					// Checksum
					if($checksum && isset($groups[$key]['date_updated'])) {

						unset($groups[$key]['date_updated']);
					}

					if($get_meta) {

						// Get meta data for each group
						$group_meta = New WS_Form_Meta();
						$group_meta->object = 'group';
						$group_meta->parent_id = $group['id'];
						$metas = $group_meta->db_read_all();
						$groups[$key]['meta'] = $metas;
					}
				}

				return $groups;

			} else {

				return [];
			}
		}

		// Delete
		public function db_delete($repair = true) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			self::db_check_form_id();
			self::db_check_id();

			global $wpdb;

			// Delete group
			$sql = sprintf("DELETE FROM %s WHERE id = %u;", $this->table_name, $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error deleting group', 'ws-form')); }

			// Delete meta
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'group';
			$ws_form_meta->parent_id = $this->id;
			$ws_form_meta->db_delete_by_object();

			// Delete groups sections
			$ws_form_section = New WS_Form_Section();
			$ws_form_section->form_id = $this->form_id;
			$ws_form_section->group_id = $this->id;
			$ws_form_section->db_delete_by_group();

			// Repair conditional, actions and meta data to remove references to this deleted field
 			if($repair) {

				$ws_form_form = New WS_Form_Form();
				$ws_form_form->id = $this->form_id;
				$ws_form_form->new_lookup['group'][$this->id] = '';
				$ws_form_form->db_conditional_repair();
				$ws_form_form->db_action_repair();
				$ws_form_form->db_meta_repair();
			}

			return true;
		}

		// Delete all groups in form
		public function db_delete_by_form($repair = true) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			self::db_check_form_id();

			global $wpdb;

			if($repair) {

				$ws_form_form = New WS_Form_Form();
				$ws_form_form->id = $this->form_id;
			}

			$sql = sprintf("SELECT %s FROM %s WHERE form_id = %u", self::DB_SELECT, $this->table_name, $this->form_id);
			$groups = $wpdb->get_results($sql, 'ARRAY_A');

			if($groups) {

				foreach($groups as $key => $group) {

					// Delete group
					$this->id = $group['id'];
					self::db_delete(false);
					$ws_form_form->new_lookup['group'][$this->id] = '';
				}
			}

			// Repair conditional, actions and meta data to remove references to these deleted groups
			if($repair) {

				$ws_form_form->db_conditional_repair();
				$ws_form_form->db_action_repair();
				$ws_form_form->db_meta_repair();
			}

			return true;
		}

		// Clone - All
		public function db_clone_all($form_id_copy_to) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			global $wpdb;

			$sql = sprintf("SELECT %s FROM %s WHERE form_id = %u ORDER BY sort_index", self::DB_SELECT, $this->table_name, $this->form_id);
			$groups = $wpdb->get_results($sql, 'ARRAY_A');

			if($groups) {

				foreach($groups as $key => $group) {

					// Read data required for copying
					$this->id = $group['id'];
					$this->label = $group['label'];
					$this->sort_index = $group['sort_index'];
					$this->form_id = $form_id_copy_to;

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
			$sql = sprintf("INSERT INTO %s (%s) VALUES ('%s', %u, '%s', '%s', %u, %u);", $this->table_name, self::DB_INSERT, esc_sql($this->label), WS_Form_Common::get_user_id(), WS_Form_Common::get_mysql_date(), WS_Form_Common::get_mysql_date(), $this->sort_index, $this->form_id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error cloning group', 'ws-form')); }

			// Get new group ID
			$group_id_new = $wpdb->insert_id;

			// Clone meta data
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'group';
			$ws_form_meta->parent_id = $this->id;
			$ws_form_meta->db_clone_all($group_id_new);

			// Clone groups
			$ws_form_section = New WS_Form_Section();
			$ws_form_section->group_id = $this->id;
			$ws_form_section->db_clone_all($group_id_new);

			return $group_id_new;
		}

		// Get checksum of current form and store it to database
		public function db_checksum() {

			// Check form ID
			self::db_check_form_id();

			// Calculate new form checksum
			$form = New WS_Form_Form();
			$form->id = $this->form_id;
			$checksum = $form->db_checksum();

			return $checksum;
		}

		// Push group from array
		public function db_update_from_object($group_object, $full = true, $new = false) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Check for group ID in $group
			if(isset($group_object->id) && !$new) { $this->id = intval($group_object->id); }
			if($new) {

				$this->id = 0;
				$group_object_id_old = (isset($group_object->id)) ? intval($group_object->id) : 0;
				if(isset($group_object->id)) { unset($group_object->id); }
			}

			// Update / Insert
			$this->id = parent::db_update_insert($this->table_name, self::DB_UPDATE, self::DB_INSERT, $group_object, 'group', $this->id);
			if($new && $group_object_id_old) { $this->new_lookup['group'][$group_object_id_old] = $this->id; }

			// Base meta for new records
			if(!isset($group_object->meta) || !is_object($group_object->meta)) { $group_object->meta = new stdClass(); }
			if($new) {

				$settings_form_admin = WS_Form_Config::get_settings_form_admin();
				$meta_data = $settings_form_admin['sidebars']['group']['meta'];
				$meta_keys = WS_Form_Config::get_meta_keys();
				$meta_data_array = self::build_meta_data($meta_data, $meta_keys);
				$group_object->meta = (object) array_merge($meta_data_array, (array) $group_object->meta);
			}

			// Update meta
			if(isset($group_object->meta)) {

				$ws_form_meta = New WS_Form_Meta();
				$ws_form_meta->object = 'group';
				$ws_form_meta->parent_id = $this->id;
				$ws_form_meta->db_update_from_object($group_object->meta);
			}

			if($full) {

				// Update sections
				if(isset($group_object->sections)) {

					$ws_form_section = New WS_Form_Section();
					$ws_form_section->group_id = $this->id;
					$ws_form_section->db_update_from_array($group_object->sections, $new);

					if($new) {
						$this->new_lookup['section'] = $this->new_lookup['section'] + $ws_form_section->new_lookup['section'];
						$this->new_lookup['field'] = $this->new_lookup['field'] + $ws_form_section->new_lookup['field'];
					}
				}
			}
		}

		// Push all groups from array (including all sections, fields)
		public function db_update_from_array($groups, $new = false) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			self::db_check_form_id();

			global $wpdb;

			// Change date_updated to null for all records
			$wpdb->update($this->table_name, array('date_updated' => null), array('form_id' => $this->form_id));

			foreach($groups as $group) {

				self::db_update_from_object($group, true, $new);
			}

			// Delete any groups that were not updated
			$wpdb->delete($this->table_name, array('date_updated' => null, 'form_id' => $this->form_id));

			return true;
		}

		// Check form_id
		public function db_check_form_id() {

			if($this->form_id <= 0) { parent::db_throw_error(__('Invalid form ID', 'ws-form')); }
			return true;
		}

		// Check id
		public function db_check_id() {

			if($this->id <= 0) { parent::db_throw_error(__('Invalid group ID', 'ws-form')); }
			return true;
		}

		// Get group label
		public function db_get_label() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			return parent::db_object_get_label($this->table_name, $this->id);
		}

		// Save tab index
		public function db_tab_index_save($parameters) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Store tab index to form meta
			$form_tab_index = intval(WS_Form_Common::get_query_var('form_tab_index', false, $parameters));
			if($form_tab_index !== false) {

				$group_meta = New WS_Form_Meta();
				$group_meta->object = 'form';
				$group_meta->parent_id = $this->form_id;
				$group_meta->db_update_from_object((object) array('tab_index' => $form_tab_index));
			}

			return $form_tab_index;
		}
	}