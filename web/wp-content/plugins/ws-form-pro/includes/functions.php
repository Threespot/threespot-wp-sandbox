<?php
	
	// Helper functions for developers

	// Get tab from form
	function &wsf_form_get_tab(&$form_object, $tab_id = false) {

		if($tab_id === false) { throw new Exception('Invalid tab ID'); }

		$tab = &wsf_form_get_tabs($form_object, $tab_id);

		return $tab;
	}

	// Get section from form
	function &wsf_form_get_section(&$form_object, $section_id = false) {

		if($section_id === false) { throw new Exception('Invalid section ID'); }

		$section = &wsf_form_get_sections($form_object, $section_id);

		return $section;
	}

	// Get field from form
	function &wsf_form_get_field(&$form_object, $field_id = false) {

		if($field_id === false) { throw new Exception('Invalid field ID'); }

		$field = &wsf_form_get_fields($form_object, $field_id);

		return $field;
	}

	// Get form tab(s)
	function &wsf_form_get_tabs(&$form_object, $tab_id = false) {

		if(!is_object($form_object)) { return false; }

		$return_tabs = [];

		$tabs = &$form_object->groups;

		foreach($tabs as &$tab) {

			if($tab_id !== false) {

				if($tab->id == $tab_id) { return $tab; }

			} else {

				$return_tabs[$tab->id] = &$tab;
			}
		}

		return ($tab_id !== false) ? false : $return_tabs;
	}

	// Get form section(s)
	function &wsf_form_get_sections(&$form_object, $section_id = false) {

		if(!is_object($form_object)) { return false; }

		$return_sections = [];

		$tabs = &$form_object->groups;

		foreach($tabs as &$tab) {

			$sections = &$tab->sections;

			foreach($sections as &$section) {

				if($section_id !== false) {

					if($section->id == $section_id) { return $section; }

				} else {

					$return_sections[$section->id] = &$section;
				}
			}
		}

		return ($section_id !== false) ? false : $return_sections;
	}

	// Get form field(s)
	function &wsf_form_get_fields(&$form_object, $field_id = false) {

		if(!is_object($form_object)) { return false; }

		$return_fields = [];

		$tabs = &$form_object->groups;

		foreach($tabs as &$tab) {

			$sections = &$tab->sections;

			foreach($sections as &$section) {

				$fields = &$section->fields;

				foreach($fields as &$field) {

					if($field_id !== false) {

						if($field->id == $field_id) { return $field; }

					} else {

						$return_fields[$field->id] = &$field;
					}
				}
			}
		}

		return ($field_id !== false) ? false : $return_fields;
	}

	// Clear field rows
	function &wsf_field_rows_clear(&$field, $group_id = false) {

		wsf_field_check($field);

		$datagrid = &wsf_field_get_datagrid($field);

		$groups = &wsf_datagrid_get_groups($datagrid, $group_id);
		
		foreach($groups as &$group) {

			$group->rows = array();
		}

		return $field;
	}

	// Add a row to a field
	function &wsf_field_row_add(&$field, $row = false, $group_id = 0) {

		wsf_row_check($row);

		$datagrid = &wsf_field_get_datagrid($field);

		$group = &$datagrid->groups[0];

		wsf_group_check($group);

		if(!isset($row->default)) { $row->default = false; }
		if(!isset($row->required)) { $row->required = false; }
		if(!isset($row->disabled)) { $row->disabled = false; }
		if(!isset($row->hidden)) { $row->hidden = false; }

		$row->id = wsf_group_row_id_next($group);

		$group->rows[] = $row;

		return $field;
	}

	// Get field datagrid
	function &wsf_field_get_datagrid(&$field) {

		if(!isset($field->meta)) { throw new Exception('Field meta data not found'); }

		switch($field->type) {

			case 'select' : $meta_key = 'data_grid_select'; break;
			case 'radio' : $meta_key = 'data_grid_radio'; break;
			case 'checkbox' : $meta_key = 'data_grid_checkbox'; break;
			default : $meta_key = 'data_grid';
		}

		if(!isset($field->meta->{$meta_key})) { throw new Exception('Field meta key ' . $meta_key . ' not found'); }

		return $field->meta->{$meta_key};
	}

	// Get datagrid group(s)
	function &wsf_datagrid_get_groups(&$datagrid, $group_id = false) {

		$groups = &$datagrid->groups;

		if($group_id !== false) {

			if($groups[$group_id]) { throw new Exception('Group ID not found'); }

			$groups = array(&$groups[$group_id]);
		}

		return $groups;
	}

	// Get next datagrid group row ID
	function wsf_group_row_id_next($group) {

		$rows = $group->rows;

		$id_max = 0;
		foreach($rows as $row) {

			if(!isset($row->id)) { throw new Exception('Row ID not found'); }
			if($row->id > $id_max) { $id_max = $row->id; }
		}

		return ++$id_max;
	}

	// Check field data is valid
	function wsf_field_check(&$field) {

		if(
			!is_object($field) ||
			!isset($field->type)
		) { throw new Exception('Invalid field'); }

		return true;
	}

	// Check group data is valid
	function wsf_group_check(&$group) {

		if(
			!is_object($group) ||
			!isset($group->rows)
		) { throw new Exception('Invalid group'); }

		return true;
	}

	// Check row data is valid
	function wsf_row_check(&$row) {

		if(
			!is_object($row) ||
			!isset($row->data) ||
			!is_array($row->data)
		) { throw new Exception('Invalid row'); }

		return true;
	}

	// Get submit object by hash
	function wsf_submit_get_by_hash($submit_hash) {

		$ws_form_submit = New WS_Form_Submit();
		$ws_form_submit->hash = $submit_hash;
		$ws_form_submit->db_read_by_hash(true, true, false, true);

		return $ws_form_submit;
	}

	// Get submit meta value
	function wsf_submit_get_value($submit, $meta_key, $default_value = '', $protected = false) {

		return WS_Form_Action::get_submit_value($submit, $meta_key, $default_value, $protected);
	}

	// Get submit meta value - Repeatable
	function get_submit_value_repeatable($submit, $meta_key, $default_value = '', $protected = false) {

		return WS_Form_Action::get_submit_value_repeatable($submit, $meta_key, $default_value, $protected);
	}

	// Set submit meta field value
	function wsf_submit_set_field_value($submit, $field_id, $meta_value = '') {

		// Get submit ID
		if(is_object($submit)) {

			$submit = isset($submit->id) ? $submit->id : false;
		}
		$submit_id = intval($submit);
		if($submit_id === 0) { throw new Exception('Invalid submit ID'); }

		// Check field ID
		$field_id = intval($field_id);
		if($field_id === 0) { throw new Exception('Invalid field ID'); }

		// Build meta data
		$meta = array(array(

			'id' => $field_id,
			'value' => $meta_value
		));

		// Update submit meta data
		$ws_form_submit_meta = New WS_Form_Submit_Meta();
		$ws_form_submit_meta->parent_id = $submit_id;
		return $ws_form_submit_meta->db_update_from_array($meta);
	}
