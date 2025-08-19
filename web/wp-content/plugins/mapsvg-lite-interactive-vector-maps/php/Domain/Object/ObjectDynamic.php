<?php

namespace MapSVG;

/**
 * Model with user-defined properties. Model schema is stored in the database.
 * Default schema is stored in a schema.json file in the same folder where the class or child class is located.
 * @package MapSVG
 */
class ObjectDynamic extends Model implements \JsonSerializable {

	public static $slugOne  = 'object';
	public static $slugMany = 'objects';
	public $id;

    /* @private $data Stores custom fields and values */
    private $data = array();

    public function __construct($data) {
    	parent::__construct($data);
    }

	public function update($params)
	{
		foreach($params as $paramName => $options){
			$methodName = 'set'.ucfirst($paramName);
			if(method_exists($this, $methodName)){
				$this->{$methodName}($options);
				$this->data[$paramName] = $this->{$paramName};
			} else {
				$this->data[$paramName] = $options;
			}
		}
		return $this;
	}

	/**
	 * Property "getter", tries to get the asked property from the .data where all data from the database
	 * with user-defined structure is stored.
	 * There data is stored in the .data to avoid default and user-defined name collisions.
	 *
	 * @param string $name
	 * @return mixed|null
	 */
	public function __get($name)
    {
	    if (property_exists($this, $name)){
            return $this->{$name};
        } elseif (array_key_exists($name, $this->data)) {
		    return $this->data[$name];
	    } else {
	       return null;
        }
    }

	/**
	 * 'isset' method for properties stored in object including .data
	 *
	 * @param string $name
	 * @return mixed|null
	 */
	public function __isset( $name )
	{
		if (property_exists($this, $name)){
			return $this->{$name} !== null;
		} elseif (array_key_exists($name, $this->data)) {
			return $this->data[$name] !== null;
		} else {
			return false;
		}
	}

	/**
	 * Returns data for json_encode()
	 * @return array|mixed
	 */
		#[\ReturnTypeWillChange]	
    public function jsonSerialize()
    {
        return $this->getData();
    }

	/**
	 * Returns object ID
	 * @return array|mixed
	 */
    public function getId()
    {
    	return $this->data['id'];
    }

	/**
	 * Sets object ID
	 * @return array|mixed
	 */
    public function setId($id)
    {
    	$this->id = $id;
    	$this->data['id'] = $id;
    }

	/**
	 * Returns the object data that is stored in the database
	 * @return array
	 */
    public function getData()
    {
        return $this->data;
    }

}
