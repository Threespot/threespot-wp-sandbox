<?php

namespace MapSVG;

class ObjectsRepository extends Repository
{

	public static $className = 'ObjectDynamic';

	/**
	 * Creates new record in the database and creates relation records
	 * @param array $data
	 * @return string
	 */
	public function create($data)
	{
		$object =  parent::create($data);
		$this->setRelationsForObject($object);
		return $object;
	}

	/**
	 * Updates the record in the database and updates relation records
	 * @param array $data
	 * @return string
	 */
	public function update($data)
	{
		$object = parent::update($data);
		$this->setRelationsForObject($object);
		return $object;
	}

	/**
	 * Deletes the record from the database and deletes all relation records from the intermediate table
	 * @param array $data
	 * @return string
	 */
	public function delete($id)
	{
		parent::delete($id);
		$this->deleteRelations($id);
		return 1;
	}

	/**
	 * Clears the table
	 * @return int|void
	 */
	public function clear()
	{
		parent::clear();
		$this->deleteAllRelations();
		return 1;
	}

	/**
	 * Clears all relations for an object
	 * @param $id
	 * @return int
	 */
	public function deleteRelations($id)
	{
		if ($this->schema->isRemote()) {
			return;
		}
		$db = Database::get();
		$db->delete($db->mapsvg_prefix . 'r2o', array('objects_table' => $this->id, 'object_id' => $id));
		return 1;
	}

	/**
	 * Deletes all relations for the whole table
	 * @param $id
	 * @return int
	 */
	public function deleteAllRelations()
	{
		if ($this->schema->isRemote()) {
			return;
		}
		$db = Database::get();
		$db->delete($db->mapsvg_prefix . 'r2o', array('objects_table' => $this->id));
		return 1;
	}

	/**
	 * Sets relations for an object
	 */
	public function setRelationsForObject($object)
	{
		if ($this->schema->isRemote()) {
			return;
		}
		$db = Database::get();

		$regions_sql_values = array();

		$data = $object->getData();

		if ($data['id'] && isset($data['regions']) && is_array($data['regions'])) {
			$db->delete($db->mapsvg_prefix . 'r2o', array(
				'objects_table' => $this->id,
				'object_id' => $data['id']
			));

			foreach ($data['regions'] as $region) {
				$region = (array)$region;
				$regions_sql_values[] = "('" . esc_sql($this->id) . "','" . esc_sql($region['tableName']) . "','" . esc_sql($data['id']) . "','" . esc_sql($region['id']) . "')";
			}

			if (!empty($regions_sql_values)) {
				$query2 = "INSERT INTO " . $db->mapsvg_prefix . 'r2o' . " (objects_table, regions_table, object_id, region_id) VALUES ";
				$query2 .= implode(", ", $regions_sql_values);
				$db->query($query2);
			}
		}
	}


	/**
	 * Sets relations for all objects
	 */
	public function setRelationsForAllObjects()
	{
		if ($this->schema->isRemote()) {
			return;
		}
		$db = Database::get();

		$this->deleteAllRelations();

		$objects = $this->source->find($this->id);

		$regions_sql_values = array();
		foreach ($objects as $object) {
			$_regions = json_decode($object->regions);
			if ($_regions) {
				foreach ($_regions as $region) {
					$regions_sql_values[] = "('" . esc_sql($this->id) . "','" . esc_sql($region->tableName) . "','" . esc_sql($object->id) . "','" . esc_sql($region->id) . "')";
				}
			}
		}
		if (!empty($regions_sql_values)) {
			$query2 = "INSERT INTO " . $db->mapsvg_prefix . 'r2o' . " (objects_table, regions_table, object_id, region_id) VALUES ";
			$query2 .= implode(", ", $regions_sql_values);
			$db->query($query2);
		}
	}
}
