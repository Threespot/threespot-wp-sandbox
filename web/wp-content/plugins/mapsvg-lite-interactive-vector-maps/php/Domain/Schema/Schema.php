<?php

namespace MapSVG;

/**
 * Class that stores information about custom table structure.
 * @package MapSVG
 */
class Schema extends Model
{

	public static $slugOne  = 'schema';
	public static $slugMany = 'schemas';

	/**
	 * @var string
	 */
	public $type;

	/**
	 * @var string|number
	 */
	public $id;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 */
	public $objectNameSingular;
	/**
	 * @var string
	 */
	public $objectNamePlural;

	/**
	 * @var array
	 */
	public $apiEndpoints;
	/**
	 * @var boolean
	 */
	public $remote;
	/**
	 * @var object | null
	 */
	public $apiAuthorization;
	/**
	 * @var string | null
	 */
	public $apiBaseUrl;
	/**
	 * @var string
	 */
	public $title;
	public $fields = array();

	private $prevFields = array();

	public function __construct($data)
	{
		$data = (array)$data;
		parent::__construct($data);
		if (!isset($data["type"])) {
			$name = static::getTypeByName($data["name"]);
			$this->setType($name);
		}
	}

	public static function getTypeByName($name)
	{
		if (strpos($name, "region") === 0) {
			$schemaType = "region";
		} elseif (strpos($name, "object") === 0) {
			$schemaType = "object";
		} elseif (strpos($name, "post") === 0) {
			$schemaType = "post";
		} elseif (strpos($name, "schema") === 0) {
			$schemaType = "schema";
		} elseif (strpos($name, "map") === 0) {
			$schemaType = "map";
		} elseif (strpos($name, "token") === 0) {
			$schemaType = "token";
		} elseif (strpos($name, "logs") === 0) {
			$schemaType = "logs";
		} else {
			$schemaType = "object";
		}
		return $schemaType;
	}



	function isRemote()
	{
		return !$this->isLocal();
	}

	function isLocal()
	{
		return $this->type !== "api";
	}

	function setType($val)
	{
		$this->type = $val;

		if ($this->type === "object" || $this->type === "post" || $this->type === "region") {
			if ($this->type === "object" || $this->type === "post") {
				$defaultApiEndpoints = [
					['url' => "/objects/%name%", 'method' => "GET", 'name' => "index"],
					['url' => "/objects/%name%/[:id]", 'method' => "GET", 'name' => "show"],
					['url' => "/objects/%name%", 'method' => "POST", 'name' => "create"],
					['url' => "/objects/%name%/[:id]", 'method' => "PUT", 'name' => "update"],
					['url' => "/objects/%name%/[:id]", 'method' => "DELETE", 'name' => "delete"],
					['url' => "/objects/%name%/[:id]/import", 'method' => "POST", 'name' => "import"],
					['url' => "/objects/%name%", 'method' => "DELETE", 'name' => "clear"]
				];
			}
			if ($this->type === "region") {
				$defaultApiEndpoints = [
					['url' => "/regions/%name%", 'method' => "GET", 'name' => "index"],
					['url' => "/regions/%name%/[:id]", 'method' => "GET", 'name' => "show"],
					['url' => "/regions/%name%", 'method' => "POST", 'name' => "create"],
					['url' => "/regions/%name%/[:id]", 'method' => "PUT", 'name' => "update"],
					['url' => "/regions/%name%/[:id]/import", 'method' => "POST", 'name' => "import"],
					['url' => "/regions/%name%/[:id]", 'method' => "DELETE", 'name' => "delete"],
				];
			}

			foreach ($defaultApiEndpoints as &$endpoint) {
				$name = $this->name ? $this->name : '';
				$endpoint['url'] = str_replace('%name%', $name, $endpoint['url']);
			}
			$this->setApiEndpoints($defaultApiEndpoints);
			$this->setApiBaseUrl($this->getDefaultApiBaseUrl());
		}
	}

	private function getDefaultApiBaseUrl()
	{
		$base_url = trailingslashit(home_url());
		return $base_url . 'wp-json/mapsvg/v1/';
	}


	function setApiAuthorization($authorization)
	{
		$this->apiAuthorization = $authorization;
	}

	function setApiBaseUrl($url)
	{
		if (!isset($url) || empty($url)) {
			$url = "";
		}
		$this->apiBaseUrl = rtrim($url, "/");
	}


	function setObjectNameSingular($name)
	{
		$this->objectNameSingular = $name;
	}

	function setObjectNamePlural($name)
	{
		$this->objectNamePlural = $name;
	}

	function setApiEndPoints($value)
	{
		$this->apiEndpoints = is_string($value) ? json_decode($value, true) : $value;
	}

	/**
	 * Get all fields types from regions / database table schema
	 *
	 * @return array|bool List of field types
	 */
	function getFieldTypes()
	{
		$db_types = array();
		foreach ($this->fields as $s) {
			$db_types[$s->name] = $s->type;

			if ($s->name === 'post_id') {
				$db_types['post'] = 'post';
			}
		}
		return $db_types;
	}

	function getFieldNames()
	{
		$db_names = array();
		foreach ($this->fields as $s) {
			$db_names[] = $s->name;
		}
		return $db_names;
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function setName($name)
	{
		$this->name = str_replace(' ', '_', $name);
	}
	public function getName()
	{
		return $this->name;
	}
	public function setTitle($title)
	{
		$this->title = $title;
	}
	public function getTitle()
	{
		return $this->title;
	}

	public function setPrevFields()
	{
		if (!empty($this->fields)) {
			$this->prevFields = $this->fields;
		}
	}
	public function getPrevFields()
	{
		return $this->prevFields;
	}
	public function clearPrevFields()
	{
		return $this->prevFields = array();
	}


	public function setFields($fields)
	{

		$this->setPrevFields();

		if (is_string($fields)) {
			$fields = json_decode($fields);
		}
		$this->fields = array();

		if ($fields) foreach ($fields as $key => $field) {
			$this->fields[$key] = $this->formatField((object)$field);
		}

		return true;
	}

	public function getFieldsOptions()
	{
		$fieldsOptions = array();
		if (is_array($this->fields)) {
			foreach ($this->fields as $field) {
				if (isset($field->options)) {
					$optionsDict = new \stdClass();
					$fieldsOptions[$field->name] = array();
					foreach ($field->options as $option) {
						$key = isset($option->value) ? $option->value : $option->id;
						$optionsDict->{$key} = $option;
						$fieldsOptions[$field->name][$key] = (array)$option;
					}
				}
			}
		}
	}

	public function addField($field, $prepend = false)
	{
		$this->setPrevFields();

		if (!is_object($field)) {
			$field = json_decode(wp_json_encode($field), FALSE);
		}

		if ($prepend) {
			$this->fields = array_merge([$field], $this->fields);
		} else {
			$this->fields[] = $this->formatField((object)$field);
		}
	}

	public function removeField($fieldName)
	{
		$this->setPrevFields();
		for ($i = 0; $i < count($this->fields); $i++) {
			$field = $this->fields[$i];
			if ($field->name === $fieldName) {
				array_splice($this->fields, $i, 1);
			}
		}
	}

	public function getField($fieldName)
	{
		$resultField = false;
		if (!empty($this->fields)) {
			foreach ($this->fields as $field) {
				if ($field->name === $fieldName) {
					$resultField = $field;
				}
			}
		}
		return $resultField;
	}

	public function renameField($currentName, $newName)
	{
		$resultField = false;
		foreach ($this->fields as $key => $field) {
			if ($field->name === $currentName) {
				$this->fields[$key]->name = $newName;
			}
		}
	}

	public function getFieldOptions($fieldName)
	{
		$fields = $this->getField($fieldName);
		$options = array();
		foreach ($fields->options as $option) {
			$key = isset($option->value) ? $option->value : $option->id;
			$options[$key] = (array)$option;
		}
		return $options;
	}

	public function formatField($field)
	{
		$booleans = array('searchable', 'readonly', 'protected', 'visible', 'auto_increment', 'not_null');
		foreach ($booleans as $booleanFieldName) {
			if (isset($field->{$booleanFieldName})) {
				$field->{$booleanFieldName} = filter_var($field->{$booleanFieldName}, FILTER_VALIDATE_BOOLEAN);
			}
		}
		return $field;
	}

	public function getSearchableFields($fields = null, $onlyNames = false, $onlyFulltext = false)
	{
		$searchable_fields = array();

		$_fields = $fields ? $fields : $this->fields;

		foreach ($_fields as $field) {
			if (isset($field->searchable) && $field->searchable === true) {
				$field = (array)$field;
				if ($field['type'] === 'location') {
					$field['name'] = $field['name'] . '_address';
				} elseif (($field['type'] === 'select' && (!isset($field['multiselect']) || $field['multiselect'] !== true)) || $field['type'] === 'radio') {
					$field['name'] = $field['name'] . '_text';
				}
				if ($onlyFulltext === false) {
					$searchable_fields[] = $field;
				} else {
					// Don't add incompatible column types to fulltext index
					if ($field["db_type"] !== "text" && $field["db_type"] !== "longtext" && strpos($field["db_type"], "varchar") === false) {
						continue;
					}
					if ($field['type'] === 'text') {
						if (!isset($field['searchType']) || $field['searchType'] === 'fulltext') {
							$searchable_fields[] = $field;
						}
					} else {
						$searchable_fields[] = $field;
					}
				}
			}
		}
		if ($onlyNames) {
			$names = [];
			foreach ($searchable_fields as $field) {
				$names[] = $field['name'];
			}
			return $names;
		} else {
			return json_decode(wp_json_encode($searchable_fields, JSON_UNESCAPED_UNICODE), true);
		}
	}
}
