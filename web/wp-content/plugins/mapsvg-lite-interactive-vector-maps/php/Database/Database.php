<?php


namespace MapSVG;


/**
 * Proxy class that redirects all method calls to $wpdb
 * @package MapSVG
 */
class Database
{

	public $db;
	public $prefix;
	public $mapsvg_prefix;
	public $posts;
	public $postmeta;
	private static $dbInstance;
	public $insert_id;

	public function __construct()
	{
		global $wpdb;
		$this->db     = $wpdb;
		$this->mapsvg_prefix = $this->db->prefix . MAPSVG_PREFIX;
		$this->prefix = $this->db->prefix;
		$this->postmeta = $this->db->postmeta;
		$this->posts = $this->db->posts;
	}

	public function __get($name)
	{
		if (property_exists($this, $name)) {
			return $this->$name;
		}
		return $this->db->$name;
	}

	private function executeQuery($method, $args)
	{
		$time = microtime(true);
		$res = call_user_func_array([$this->db, $method], $args);
		$this->handleError();
		Logger::addDatabaseQuery($this->db->last_query, $time);
		return $res;
	}

	public function db_version()
	{
		return $this->db->db_version();
	}

	public function posts()
	{
		return $this->db->posts;
	}

	/* @return Database */
	public static function get()
	{
		if (!self::$dbInstance) {
			self::$dbInstance = new self();
		}
		return self::$dbInstance;
	}

	

	public function handleError($string = '')
	{
		if ($this->db->last_error) {
			// $caller = $this->getCaller();
			Logger::error($this->db->last_error);
		}
	}

	public function query($query)
	{
		return $this->executeQuery('query', [$query]);
	}

	public function get_col($query, $num)
	{
		return $this->executeQuery('get_col', [$query, $num]);
	}

	public function get_var($query)
	{
		return $this->executeQuery('get_var', [$query]);
	}

	public function get_row($query, $output = OBJECT)
	{
		return $this->executeQuery('get_row', [$query, $output]);
	}

	public function get_results($query, $responseType = OBJECT)
	{
		return $this->executeQuery('get_results', [$query, $responseType]);
	}

	public function insert($table, $data)
	{
		$res = $this->executeQuery('insert', [$table, $data]);
		$this->insert_id = $this->db->insert_id;
		return $res;
	}

	public function update($table, $data, $where = null)
	{
		return $this->executeQuery('update', [$table, $data, $where]);
	}

	public function replace($table, $data, $where = null)
	{
		return $this->executeQuery('replace', [$table, $data, $where]);
	}

	public function delete($table, $data)
	{
		return $this->executeQuery('delete', [$table, $data]);
	}

	public function clear($table)
	{
		return $this->executeQuery('query', ["DELETE FROM " . $table]);
	}

	public function prepare($data, $values)
	{
		return $this->db->prepare($data, $values);
	}
	public function esc_like($data)
	{
		return $this->db->esc_like($data);
	}
}
