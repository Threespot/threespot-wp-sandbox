<?php

	class WS_Form_Section extends WS_Form_Core {

		public $id;
		public $parent_section_id;
		public $form_id;
		public $group_id;
		public $child_count;
		public $new_lookup;
		public $label;
		public $meta;
	
		public $table_name;

		const DB_INSERT = 'label,child_count,user_id,date_added,date_updated,sort_index,group_id,parent_section_id';
		const DB_UPDATE = 'label,user_id,date_updated';
		const DB_SELECT = 'label,child_count,sort_index,id';

		public function __construct() {

			global $wpdb;

			$this->id = 0;
			$this->parent_section_id = 0;
			$this->form_id = 0;
			$this->group_id = 0;
			$this->child_count = 0;
			$this->table_name = $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . 'section';
			$this->new_lookup = array();
			$this->new_lookup['section'] = array();
			$this->new_lookup['field'] = array();
			$this->label = WS_FORM_DEFAULT_SECTION_NAME;
			$this->meta = array();
		}

		// Create section
		public function db_create($next_sibling_id = 0) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Check group ID
			self::db_check_group_id();

			global $wpdb;

			// Process sort index
			$sort_index = self::db_object_sort_index_get($this->table_name, 'group_id', $this->group_id, $next_sibling_id);

			// Add section
			$sql = sprintf("INSERT INTO %s (%s) VALUES ('%s', 0, %u, '%s', '%s', %u, %u, 0);", $this->table_name, self::DB_INSERT, esc_sql($this->label), WS_Form_Common::get_user_id(), WS_Form_Common::get_mysql_date(), WS_Form_Common::get_mysql_date(), $sort_index, $this->group_id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error adding section', 'ws-form')); }

			// Get inserted ID
			$this->id = $wpdb->insert_id;

			// Build meta data array
			$settings_form_admin = WS_Form_Config::get_settings_form_admin();
			$meta_data = $settings_form_admin['sidebars']['section']['meta'];
			$meta_keys = WS_Form_Config::get_meta_keys();
			$meta_data = self::build_meta_data($meta_data, $meta_keys);
			$meta_data = (object) array_merge($meta_data, (array) $this->meta);

			// Build meta data
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'section';
			$ws_form_meta->parent_id = $this->id;
			$ws_form_meta->db_update_from_object($meta_data);

			return $this->id;
		}

		// Read record to array
		public function db_read($get_meta = true, $get_fields = false) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			global $wpdb;

			// Add fields
			$sql = sprintf("SELECT %s FROM %s WHERE id = %u LIMIT 1;", self::DB_SELECT, $this->table_name, $this->id);
			$section_array = $wpdb->get_row($sql, 'ARRAY_A');

			if($section_array === null) { parent::db_throw_error(__('Unable to read section', 'ws-form')); }

			foreach($section_array as $key => $value) {

				$this->{$key} = $value;
			}

			if($get_meta) {

				// Read meta
				$section_meta = New WS_Form_Meta();
				$section_meta->object = 'section';
				$section_meta->parent_id = $this->id;
				$metas = $section_meta->db_read_all();
				$section_array['meta'] = $metas;
				$this->meta = $metas;
			}

			if($get_fields) {

				// Read fields
				$ws_form_field = New WS_Form_Field();
				$ws_form_field->section_id = $this->id;
				$fields = $ws_form_field->db_read_all($get_meta);
				$section_array['fields'] = $fields;
			}

			// Convert into object
			$section_object = json_decode(json_encode($section_array));

			// Return array
			return $section_object;
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

		// Read record and all children to array
		public function db_read_all($get_meta = true, $checksum = false) {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			self::db_check_group_id();

			global $wpdb;

			$fields_array = array();

			$sql = sprintf("SELECT %s FROM %s WHERE group_id = %u ORDER BY sort_index", self::DB_SELECT, $this->table_name, $this->group_id);
			$sections = $wpdb->get_results($sql, 'ARRAY_A');

			if($sections) {

				foreach($sections as $key => $section) {

					if($get_meta) {

						// Get meta data for each section
						$section_meta = New WS_Form_Meta();
						$section_meta->object = 'section';
						$section_meta->parent_id = $section['id'];
						$metas = $section_meta->db_read_all();
						$sections[$key]['meta'] = $metas;
					}

					// Checksum
					if($checksum && isset($sections[$key]['date_updated'])) {

						unset($sections[$key]['date_updated']);
					}

					if($section['child_count'] > 0) {

						// Get children
						$ws_form_section = New WS_Form_Section();
						$ws_form_section->group_id = $this->group_id;
						$ws_form_section->parent_section_id = $section['id'];
						$children = $ws_form_section->db_read_all($get_meta, $fields, $checksum);
						$sections[$key]['children'] = $children;

					} else {

						// Get fields
						$ws_form_field = New WS_Form_Field();
						$ws_form_field->section_id = $section['id'];
						$ws_form_field_return = $ws_form_field->db_read_all($get_meta, $checksum);
						$sections[$key]['fields'] = $ws_form_field_return;
					}
				}

				return $sections;

			} else {

				return [];
			}
		}

		// Delete
		public function db_delete($repair = true) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			$parent_section_id = self::db_get_parent_section_id();

			global $wpdb;

			// Delete section
			$sql = sprintf("DELETE FROM %s WHERE id = %u;", $this->table_name, $this->id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error deleting section', 'ws-form')); }

			// Update parent child_count
			if($parent_section_id > 0) {
				self::db_update_child_count($parent_section_id);
			}

			// Delete meta
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'section';
			$ws_form_meta->parent_id = $this->id;
			$ws_form_meta->db_delete_by_object();

			// Delete section fields
			$ws_form_field = New WS_Form_Field();
			$ws_form_field->form_id = $this->form_id;
			$ws_form_field->section_id = $this->id;
			$ws_form_field->db_delete_by_section();

			// Repair conditional, actions and meta data to remove references to this deleted section
			if($repair) {

				$ws_form_form = New WS_Form_Form();
				$ws_form_form->id = $this->form_id;
				$ws_form_form->new_lookup['section'][$this->id] = '';
				$ws_form_form->db_conditional_repair();
				$ws_form_form->db_action_repair();
				$ws_form_form->db_meta_repair();
			}
		}

		// Delete all sections in group
		public function db_delete_by_group($repair = true) {

			self::db_check_group_id();

			global $wpdb;

			if($repair) {

				$ws_form_form = New WS_Form_Form();
				$ws_form_form->id = $this->form_id;
			}

			$sql = sprintf("SELECT %s FROM %s WHERE group_id = %u", self::DB_SELECT, $this->table_name, $this->group_id);
			$sections = $wpdb->get_results($sql, 'ARRAY_A');

			if($sections) {

				foreach($sections as $key => $section) {

					// Delete section
					$this->id = $section['id'];
					self::db_delete(false);
					$ws_form_form->new_lookup['section'][$this->id] = '';
				}
			}

			// Repair conditional, actions and meta data to remove references to these deleted fields
			if($repair) {

				$ws_form_form->db_conditional_repair();
				$ws_form_form->db_action_repair();
				$ws_form_form->db_meta_repair();
			}
		}

		// Clone - All
		public function db_clone_all($group_id_copy_to, $parent_section_id = 0, $parent_section_id_copied = 0) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			global $wpdb;

			$sql = sprintf("SELECT %s FROM %s WHERE group_id = %u AND parent_section_id = %u ORDER BY sort_index", self::DB_SELECT, $this->table_name, $this->group_id, $parent_section_id);
			$sections = $wpdb->get_results($sql, 'ARRAY_A');

			if($sections) {

				foreach($sections as $key => $section) {

					// Read data required for copying
					$this->id = $section['id'];
					$this->label = $section['label'];
					$this->sort_index = $section['sort_index'];
					$this->group_id = $group_id_copy_to;
					$this->parent_section_id = $parent_section_id_copied;
					$this->child_count = $section['child_count'];

					$section_id_new = self::db_clone();

					// Clone children
					if($this->child_count) { self::db_clone_all($group_id_copy_to, $this->id, $section_id_new); }
				}
			}
		}

		// Clone
		public function db_clone() {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			global $wpdb;

			// Clone section
			$sql = sprintf("INSERT INTO %s (%s) VALUES ('%s', %u, %u, '%s', '%s', %u, %u, %u);", $this->table_name, self::DB_INSERT, esc_sql($this->label), $this->child_count, WS_Form_Common::get_user_id(), WS_Form_Common::get_mysql_date(), WS_Form_Common::get_mysql_date(), $this->sort_index, $this->group_id, $this->parent_section_id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error cloning section', 'ws-form')); }

			// Get new section ID
			$section_id_new = $wpdb->insert_id;

			// Clone meta data
			$ws_form_meta = New WS_Form_Meta();
			$ws_form_meta->object = 'section';
			$ws_form_meta->parent_id = $this->id;
			$ws_form_meta->db_clone_all($section_id_new);

			// Clone fields
			$ws_form_field = New WS_Form_Field();
			$ws_form_field->section_id = $this->id;
			$ws_form_field->db_clone_all($section_id_new);

			return $section_id_new;
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

		// Push section from array
		public function db_update_from_object($section_object, $full = true, $new = false) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			// Check for section ID in $section
			if(isset($section_object->id) && !$new) { $this->id = intval($section_object->id); }
			if($new) {

				$this->id = 0;
				$section_object_id_old = (isset($section_object->id)) ? intval($section_object->id) : 0;
				if(isset($section_object->id)) { unset($section_object->id); }
			}

			// Update / Insert
			$this->id = parent::db_update_insert($this->table_name, self::DB_UPDATE, self::DB_INSERT, $section_object, 'section', $this->id);
			if($new && $section_object_id_old) { $this->new_lookup['section'][$section_object_id_old] = $this->id; }

			// Base meta for new records
			if(!isset($section_object->meta) || !is_object($section_object->meta)) { $section_object->meta = new stdClass(); }
			if($new) {

				$settings_form_admin = WS_Form_Config::get_settings_form_admin();
				$meta_data = $settings_form_admin['sidebars']['section']['meta'];
				$meta_keys = WS_Form_Config::get_meta_keys();
				$meta_data_array = self::build_meta_data($meta_data, $meta_keys);
				$section_object->meta = (object) array_merge($meta_data_array, (array) $section_object->meta);
			}

			// Update meta
			if(isset($section_object->meta)) {

				$ws_form_meta = New WS_Form_Meta();
				$ws_form_meta->object = 'section';
				$ws_form_meta->parent_id = $this->id;
				$ws_form_meta->db_update_from_object($section_object->meta);
			}

			if($full) {

				// Update fields
				if(isset($section_object->fields)) {

					$ws_form_field = New WS_Form_Field();
					$ws_form_field->section_id = $this->id;
					$ws_form_field->db_update_from_array($section_object->fields, $new);

					if($new) {

						$this->new_lookup['field'] = $this->new_lookup['field'] + $ws_form_field->new_lookup['field'];
					}
				}

				// Process child sections
				if(isset($section_object->children)) {

					$ws_form_section = New WS_Form_Section();
					$ws_form_section->group_id = $this->group_id;
					$ws_form_section->parent_section_id = $this->id;
					$ws_form_section->db_update_from_array($section_object->children, $new);
				}
			}

			// Update child count
			self::db_update_child_count($this->id);
		}

		// Push all groups from array (including all children sections, fields)
		public function db_update_from_array($sections, $new) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			self::db_check_group_id();

			global $wpdb;

			// Change date_updated to null for all records
			$wpdb->update($this->table_name, array('date_updated' => null), array('group_id' => $this->group_id, 'parent_section_id' => $this->parent_section_id));

			foreach($sections as $section) {

				self::db_update_from_object($section, true, $new);
			}

			// Delete any sections that were not updated
			$wpdb->delete($this->table_name, array('date_updated' => null, 'group_id' => $this->group_id, 'parent_section_id' => $this->parent_section_id));

			return true;
		}

		// Update child_count
		public function db_update_child_count($id) {

			// User capability check
			if(!WS_Form_Common::can_user('edit_form')) { return false; }

			self::db_check_id();

			global $wpdb;

			// Get child_count
			$sql = sprintf("SELECT IFNULL(COUNT(id), 0) FROM %s WHERE parent_section_id = %u;", $this->table_name, $this->id);
			$child_count = $wpdb->get_var($sql);
			if($child_count === false) { parent::db_throw_error(__('Unable to determine section child count', 'ws-form')); }

			// Update section child_count
			$sql = sprintf('UPDATE %s SET child_count = %u WHERE id = %u;', $this->table_name, $child_count, $this->id);
			if($wpdb->query($sql) === false) {

				parent::db_throw_error(__('Unable to update section child count', 'ws-form'));
			}
		}

		// Get group ID
		public function db_get_group_id() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			if($this->id == 0) { parent::db_throw_error(__('Section ID is zero, cannot get group ID', 'ws-form')); }

			global $wpdb;

			$sql = sprintf("SELECT group_id FROM %s WHERE id = %u LIMIT 1;", $this->table_name, $this->id);
			$group_id = $wpdb->get_var($sql);
			if($group_id === false) { parent::db_throw_error(__('Error getting group ID', 'ws-form')); }

			return $group_id;
		}

		// Get section parent ID
		public function db_get_parent_section_id() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			if($this->id == 0) { parent::db_throw_error(__('Section ID is zero, cannot get section parent ID', 'ws-form')); }

			global $wpdb;

			$sql = sprintf("SELECT parent_section_id FROM %s WHERE id = %u LIMIT 1;", $this->table_name, $this->id);
			$parent_section_id = $wpdb->get_var($sql);
			if($parent_section_id === false) { parent::db_throw_error(__('Error getting section parent ID', 'ws-form')); }

			return $parent_section_id;
		}

		// Get breakpoint size meta of last section added
		public function db_set_breakpoint_size_meta() {

			global $wpdb;

			self::db_check_group_id();

			// Get column count of last section added
			$sql = sprintf("SELECT id FROM %s WHERE group_id = %u AND parent_section_id = 0 ORDER BY sort_index DESC LIMIT 1", $this->table_name, $this->group_id);
			$last_section_id = $wpdb->get_var($sql);
			if($last_section_id === false) { parent::db_throw_error(__('Unable to determine last section added', 'ws-form')); }
			$inherit_last_meta = !is_null($last_section_id);

			if($inherit_last_meta) {

				// Get framework
				$framework = WS_Form_Common::option_get('framework');

				// Get framework breakpoints
				$frameworks = WS_Form_Config::get_frameworks();
				$breakpoints = $frameworks['types'][$framework]['breakpoints'];

				// Get breakpoints column counts
				$ws_form_meta = New WS_Form_Meta();
				$ws_form_meta->object = 'section';
				$ws_form_meta->parent_id = $last_section_id;

				// Add framework sizes to section meta to be inherited
				$section_metas = array();
				foreach($breakpoints as $key => $value) {

					$this->meta['breakpoint_size_' . $key] = $ws_form_meta->db_get_object_meta('breakpoint_size_' . $key, '');
				}
			}

			return $this->meta;
		}

		// Check form_id
		public function db_check_form_id() {

			if($this->form_id <= 0) { parent::db_throw_error(__('Invalid form ID', 'ws-form')); }
			return true;
		}

		// Check id
		public function db_check_id() {

			if($this->id <= 0) { parent::db_throw_error(__('Invalid section ID', 'ws-form')); }
			return true;
		}

		// Check form_id
		public function db_check_group_id() {

			if($this->group_id <= 0) { parent::db_throw_error(__('Invalid group ID', 'ws-form')); }
			return true;
		}

		// Get section label
		public function db_get_label() {

			// User capability check
			if(!WS_Form_Common::can_user('read_form')) { return false; }

			return parent::db_object_get_label($this->table_name, $this->id);
		}
	}