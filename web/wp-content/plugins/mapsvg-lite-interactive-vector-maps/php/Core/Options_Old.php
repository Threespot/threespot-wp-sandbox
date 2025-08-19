<?php


namespace MapSVG;


/**
 * Сlass that manages global MapSVG settings in the database
 * @package MapSVG
 */
class Options_Old {

	/**
	 * Returns the list of all MapSVG options (key/value pairs).
	 * @return array
	 */
	public static function getAll(){
		$db = Database::get();
		$res = $db->get_results("SELECT * FROM ".$db->mapsvg_prefix."settings", ARRAY_A);
		$response = array();
		foreach($res as $re){
			$response[$re['key']] = $re['value'];
		}
		return $response;
	}

	/**
	 * Returns an option value by its name
	 * @return string
	 */
	public static function get($field){
		$db = Database::get();
		return $db->get_var("SELECT value FROM ".$db->mapsvg_prefix."settings WHERE `key`='".esc_sql($field)."'");
	}

	/**
	 * Sets an option value
	 * @return array
	 */
	public static function set($field, $value){
		$db = Database::get();
		$db->replace($db->mapsvg_prefix."settings", ["key"=>$field, "value" => $value]);
	}
}
