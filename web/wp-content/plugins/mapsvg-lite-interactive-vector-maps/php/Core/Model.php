<?php

namespace MapSVG;

/**
 * Core Model class
 * @package MapSVG
 */
class Model implements \JsonSerializable
{

	public $id;
	//public $status;
	public function __construct($data)
	{
		$this->update((array)$data);
	}

	/**
	 * Updates model properties.
	 * Tries to find corresponding setters for every passed parameter.
	 *
	 * @param array $params
	 * @return $this
	 */
	public function update($params)
	{
		foreach ($params as $paramName => $options) {
			$methodName = 'set' . ucfirst($paramName);
			if (method_exists($this, $methodName)) {
				$this->{$methodName}($options);
			}
		}
		return $this;
	}

	/**
	 * Returns data for json_encode()
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return get_object_vars($this);
	}

	/**
	 * Returns model ID
	 * @return string|number
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Sets model ID
	 * @param $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Returs model properties
	 * @return array
	 */
	public function getData()
	{
		return get_object_vars($this);
	}
}
