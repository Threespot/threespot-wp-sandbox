<?php

namespace MapSVG;

class RegionsRepository extends Repository
{

	public static $className = 'Region';
	public $prefix = "";

	public function __construct($tableName = null)
	{
		$this->prefix = "";
		$this->db = Database::get();
		parent::__construct($tableName);
	}

	public function getTableName()
	{
		return $this->db->mapsvg_prefix . $this->id;
	}

	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}

	// /**
	//  * Returns an array of Entities by provided Query
	//  * @param Query $query Query for the database
	//  * @return array<Schema>
	//  */
	public function find(Query $query = null)
	{
		if ($query === null) {
			$query = new Query(array('perpage' => 0, 'filters' => array()));
		}
		if ($this->prefix) {
			$query->filters["prefix"] = $this->prefix;
		}
		if (!$query || !$query->sort) {
			$query->sort = [["field" => "id", "order" => "ASC"]];
		}
		return parent::find($query);
	}

	/**
	 * Updates all provided regions in the database
	 * @param array $objects
	 */
	public function createOrUpdateAll($objects)
	{
		$fields = array();
		$duplicateUpdateMysql = array();
		foreach ($objects as $object) {
			$keys = array_keys($object);
			if (array_diff($keys, $fields)) {
				$fields = $keys;
			}
		}

		$_fields = array();
		foreach ($fields as $k => $v) {
			$_fields[$k] = '`' . $v . '`';
			$duplicateUpdateMysql[] = '`' . $v . '` = VALUES(`' . $v . '`)';
		}

		$regions = array();
		foreach ($objects as $k => $object) {
			$data = array();
			foreach ($fields as $key => $fieldName) {
				$data[$fieldName] = isset($object[$fieldName]) ? esc_sql($object[$fieldName]) : '';
			}
			$regions[] = "('" . implode("','", $data) . "')";
		}

		$this->db->query('INSERT INTO ' . static::getTableName() . ' (' . implode(',', $_fields) . ') VALUES ' . implode(',', $regions) . ' ON DUPLICATE KEY UPDATE ' . implode(',', $duplicateUpdateMysql));
	}
}
