<?php

namespace MapSVG;

class SchemaRepository extends Repository
{

	/* @var Database $db Database instance */
	protected $db;

	public static $className = 'Schema';

	/**
	 * Find schema by Collection name
	 *
	 * @param string $name Collection name
	 * @return Schema|null Returns Schema, if found; bool false if not found.
	 */
	function findByName($name)
	{

		$res = $this->source->findOne(["name" => $name]);

		if ($res) {
			$data = $this->decodeParams($res);
			$schema =  new Schema($data);
			return $schema;
		} else {
			return null;
		}
	}

	/**
	 * Creates a new schema and table
	 *
	 * @param Schema $schema
	 * @return Schema
	 */
	function create($schema)
	{
		if (!is_object($schema)) {
			$schema    = $this->newObject($schema);
		}


		if (isset($schema->id)) {
			unset($schema->id);
		}

		// Get default fields for schema
		if ($schema->type === "object" || $schema->type === "region" || $schema->type === "post" || $schema->type === "api") {
			if (empty($schema->fields)) {
				$defSchema = RepositoryFactory::getDefaultSchema($schema->name, $schema->type);
				$schema->fields = $defSchema->fields;
			}
		}

		$dataForDB = $this->encodeParams($schema->getData());

		$data = $this->source->create($dataForDB);
		$schema->setId($data["id"]);

		// Don't create new table for "api"
		if ($schema->type === "object" || $schema->type === "post" || $schema->type === "region") {
			$this->tableSet($schema);
		}

		return $schema;
	}

	/**
	 * @param $schema array options
	 * @param $skip_db_update bool If true, table will not be altered (only the schema is saved)
	 * @return Schema
	 */
	public function update($data, $skip_db_update = false)
	{

		if (!($data instanceof Schema)) {
			/** @var Schema $schema */
			$schema = $this->findById($data['id']);
			$schema->update($data);
		} else {
			$schema = $data;
		}

		$params = $this->encodeParams($schema->getData());
		parent::update($schema, array('id' => $params['id']));

		if (!$skip_db_update) {
			if ($schema->type === "region" || $schema->type === "object" || $schema->type === "post") {
				$this->tableSet($schema);
			}
		}

		return $schema;
	}

	/**
	 * Create or alter custom table structure
	 */
	private function tableSet($schema)
	{

		$fields                 = array();

		if (!$this->db) {
			$this->db = Database::get();
		}

		$tableNameSanitized = $this->db->mapsvg_prefix . preg_replace('/[^a-zA-Z0-9_]/', '', $schema->name);

		$old_searchable_fields  = $schema->getSearchableFields($schema->getPrevFields(), true, true);
		$searchable_fields      = $schema->getSearchableFields(null, true, true);
		$new_field_names        = array('id');
		$primary_key            = '';
		$update_options         = array();
		$new_options            = array();
		$prev_options           = array();
		$clear_fields           = array();

		foreach ($schema->getFields() as $field) {

			$field = (array)$field;

			if ($field['type'] == 'id' && $field['db_type'] == 'varchar(255)') {
				$primary_key = 'PRIMARY KEY  (id(40))';
			} else {
				$primary_key = 'PRIMARY KEY  (id)';
			}

			if ($field['type'] == 'select') {
				if (!isset($field['multiselect'])) {
					$field['multiselect'] = false;
				}
			}

			if ($field['type'] == 'select' && $field['multiselect'] === true) {
				$field['type'] = 'text';
			}

			$field_create_db_string = '`' . $field['name'] . '` ' . $field['db_type'] . (isset($field['db_default']) ? ' DEFAULT ' . $field['db_default'] : '');
			if (isset($field['not_null']) && $field['not_null']) {
				$field_create_db_string .= ' NOT NULL';
			}
			if (isset($field['auto_increment']) && $field['auto_increment']) {
				$field_create_db_string .= ' AUTO_INCREMENT';
			}
			$fields[]          = $field_create_db_string;
			$new_field_names[] = $field['name'];

			if (($field['type'] == 'select' && $field['multiselect'] !== true) || $field['type'] == 'radio' || $field['type'] == 'status') {
				$db_string = $field['name'] . '_text' . ' varchar(255)';
				if (isset($field['db_default'])) {
					foreach ($field['options'] as $opt) {
						$opt = (array)$opt;
						if ((string)$opt['value'] === (string)$field['db_default']) {
							$db_string .= " DEFAULT '" . $opt['label'] . "'";
							break;
						}
					}
				}
				$fields[] = $db_string;
				$new_field_names[] = $field['name'] . '_text';
			}

			if (isset($field['type']) && $field['type'] == 'location') {
				$fields[] = 'location_lat FLOAT(10,7)';
				$fields[] = 'location_lng FLOAT(10,7)';
				$fields[] = 'location_x FLOAT';
				$fields[] = 'location_y FLOAT';
				$fields[] = 'location_address TEXT';
				$fields[] = 'location_img varchar(255)';
				$new_field_names[] = 'location_lat';
				$new_field_names[] = 'location_lng';
				$new_field_names[] = 'location_x';
				$new_field_names[] = 'location_y';
				$new_field_names[] = 'location_address';
				$new_field_names[] = 'location_img';
			}

			if (isset($field['options']) && $field['type'] != 'region') {
				$new_options[$field['name']] = array();
				foreach ($field['options'] as $o) {
					$o = (array)$o;
					$new_options[$field['name']][(string)$o['value']] = $o['label'];
				}
			}
		}

		if (!empty($schema->getPrevFields())) foreach ($schema->getPrevFields() as $_field) {
			$_field = json_decode(wp_json_encode($_field, JSON_UNESCAPED_UNICODE), true);

			if (isset($_field['options']) && $_field['type'] != 'marker' && $_field['type'] != 'region') {
				$prev_options[$_field['name']] = array();
				foreach ($_field['options'] as $_o) {
					$prev_options[$_field['name']][(string)$_o['value']] = $_o['label'];
				}
				if (!isset($prev_options[$_field['name']]) || !is_array($prev_options[$_field['name']]))
					$prev_options[$_field['name']] = array();
				if (!isset($new_options[$_field['name']]) || !is_array($new_options[$_field['name']]))
					$new_options[$_field['name']] = array();

				$diff = array_diff_assoc($new_options[$_field['name']], $prev_options[$_field['name']]);
				if (!isset($_field['multiselect'])) {
					$_field['multiselect'] = false;
				}

				if ($diff) {
					$update_options[] = array(
						'name'             => $_field['name'],
						'type'             => $_field['type'],
						'next_multiselect' => (bool)$_field['multiselect'],
						'prev_multiselect' => (bool)$_field['multiselect'],
						'options'          => $diff
					);
				}

				if ($_field['type'] == 'select' && ((bool)$_field['multiselect'] != (bool)$_field['multiselect'])) {
					$clear_fields[] = $_field['name'];
				}
			}
		}


		$table_exists = $this->db->get_var('SHOW TABLES LIKE \'' . $tableNameSanitized . '\'');

		if ($table_exists && ($searchable_fields != $old_searchable_fields)) {
			$index = $this->db->get_row("SHOW INDEX FROM `{$tableNameSanitized}` WHERE Key_name = '_keywords'", OBJECT);
			if ($index) {
				$this->db->query("DROP INDEX `_keywords` ON {$tableNameSanitized}");
			}
		}

		$searchable_fields_sanitized = '';
		if (!empty($searchable_fields)) {
			$searchable_fields = array_map(function ($field) {
				return '`' . $field . '`';
			}, $searchable_fields);
			$searchable_fields_sanitized = ",\nFULLTEXT KEY _keywords (" . implode(',', $searchable_fields) . ')';
		} else {
			$searchable_fields_sanitized = '';
		}

		if (version_compare($this->db->db_version(), '5.6.0', '<')) {
			$engine = " ENGINE=MyISAM ";
		} else {
			$engine = " ENGINE=InnoDB ";
		}

		$charset_collate   = "default character set utf8\ncollate utf8_unicode_ci";

		$sql = "CREATE TABLE $tableNameSanitized (
		" . implode(",\n", $fields) . ",
		" . $primary_key . $searchable_fields_sanitized . "
		) " . $engine . $charset_collate;

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		// DROP removed columns
		$columns = $this->db->get_col("DESC {$tableNameSanitized}", 0);
		foreach ($columns as $column_name) {
			if (!in_array($column_name, $new_field_names)) {
				$this->db->query("ALTER TABLE {$tableNameSanitized} DROP COLUMN {$column_name}");
			}
		}

		if ($update_options) {
			$field = '';
			foreach ($update_options as $field) {
				foreach ($field['options'] as $id => $label) {
					$data = array();
					if ($field['type'] == 'select' && ($field['prev_multiselect'] === true || $field['next_multiselect'] === true)) {
						if ($field['prev_multiselect'] === true && $field['next_multiselect'] === true) {
							$prev = esc_sql($prev_options[$field['name']][$id]);
							$label = esc_sql($label);
							$query = "UPDATE {$tableNameSanitized} SET `{$field['name']}` = REPLACE(`{$field['name']}`, '\"label\":\"{$prev}\"', '\"label\":\"{$label}\"')";
							$this->db->query($query);
						} else {
							$this->db->query("UPDATE {$tableNameSanitized} SET `{$field['name']}`=''");
						}
					} else {
						$f = $field['name'] . '_text';
						$data[$f] = esc_sql($label);
						$where = array();
						$where[$field['name']] = esc_sql($id);
						$this->db->update($tableNameSanitized, $data, $where);
					}
				}
			}
		}

		if ($clear_fields) {
			$field = '';
			foreach ($clear_fields as $field) {
				$this->db->query("UPDATE {$tableNameSanitized} SET `{$field}`='' ");
			}
		}

		// Clear previous fields list
		$schema->clearPrevFields();
	}

	public function decodeParams($data)
	{
		$data['fields'] = json_decode($data['fields']);
		if (isset($data["apiEndpoints"]) && is_string($data["apiEndpoints"])) {
			$data['apiEndpoints'] = json_decode($data['apiEndpoints'], true);
		}
		return $this->newObject($data);
	}

	public function encodeParams($data, $options = false)
	{

		if (is_object($data) && method_exists($data, 'getData')) {
			$data = $data->getData();
		}

		$data = (array)$data;

		foreach ($data as $key => $val) {
			if ($val === null) {
				unset($data[$key]);
			}
		}
		if (isset($data["fields"]) && !is_string($data['fields'])) {
			$data['fields'] = wp_json_encode($data['fields'], JSON_UNESCAPED_UNICODE);
		}


		if (isset($data["apiEndpoints"]) && !is_string($data["apiEndpoints"])) {
			$data["apiEndpoints"] = wp_json_encode($data["apiEndpoints"], JSON_UNESCAPED_UNICODE);
		}

		return $data;
	}

	public static function tableExists($name)
	{
		$db = Database::get();
		$tableNameSanitized = $db->mapsvg_prefix . preg_replace('/[^a-zA-Z0-9_]/', '', $name);
		$table_exists = $db->get_var($db->prepare("SHOW TABLES LIKE %s", $tableNameSanitized));
		return (bool)$table_exists;
	}
}
