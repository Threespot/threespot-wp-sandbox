<?php

namespace MapSVG;

use Exception;
use JsonSerializable;

/**
 * Core Repository class.
 * Doing CRUD operations for Entities in the database.
 * Stores cached list of Entities received from the last query.
 * Contains Schema of the Entity.
 * @package MapSVG
 */
class Repository implements JsonSerializable
{

	/** @var string Name of the Entity class */
	public static $className = 'Object';

	/* @var Database $db Database instance */
	protected $db;

	/* @var string $id Unique short name of the table */
	public $id;

	/* @var array  Array of Entities received from the database */
	protected $objects;

	/**
	 * @var Schema $schema  Class containing the list of DB fields and their settings
	 */
	public $schema;

	/**
	 * @var boolean Is repository remote or local
	 */
	public $remote;

	/* @var boolean Whether output JSON data should be provided with the Schema */
	private $renderJsonWithSchema = false;

	public $geocodingErrors;

	/**
	 * @var DataSource
	 */
	public $source;

	/**
	 * Lazy-loaded property to check if ACF is active
	 */
	private static $isAcfActive = null;

	/**
	 * Lazy-loaded property to check if Metabox is active
	 */
	private static $isMetaboxActive = null;

	/**
	 * Check if ACF is active
	 * @return bool
	 */
	private static function isAcfActive()
	{
		if (self::$isAcfActive === null) {
			self::$isAcfActive = function_exists('get_fields');
		}
		return self::$isAcfActive;
	}

	/**
	 * Check if Metabox is active
	 * @return bool
	 */
	private static function isMetaboxActive()
	{
		if (self::$isMetaboxActive === null) {
			self::$isMetaboxActive = function_exists('rwmb_meta');
		}
		return self::$isMetaboxActive;
	}

	/**
	 * @param Schema $schema
	 */
	public function __construct($schema = null)
	{

		if ($schema !== null) {
			$this->schema = $schema;
		} else {
			$this->loadSchema(static::getDefaultSchema());
		}

		$this->id = $this->schema->name;
		$this->geocodingErrors = [];
		$this->source = $this->schema->isRemote() ? new ApiDataSource($this->schema) : new DbDataSource($this->schema);
	}

	/**
	 * Instance builder - calls setter methods for every given parameter
	 * @param array $params
	 * @return $this
	 */
	public function build($params)
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
	 * Returns the namespaced Entity class name
	 * @return string
	 */
	public function getModelClass()
	{
		return __NAMESPACE__ . '\\' . static::$className;
	}

	/**
	 * Creates new instance of the Entity class
	 * @return object
	 */
	public function newObject($data)
	{
		$class = $this->getModelClass();
		return new $class($data);
	}

	/**
	 * Tells that the result JSON should be provided with the Schema
	 * @return string
	 */
	public function withSchema()
	{
		$this->renderJsonWithSchema = true;
	}

	/**
	 * Specifies what data should be used for wp_json_encode()
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize()
	{
		$data = new \stdClass();
		if (empty($this->objects)) {
			$this->fill();
		}

		$data->{$this->schema->objectNamePlural} = array("items" => $this->objects);

		if ($this->renderJsonWithSchema) {
			$data->{$this->schema->objectNamePlural}["schema"] = $this->schema;
		}

		return $data;
	}

	/**
	 * Fills repository cache with Entities by using the Query object
	 * @param Query $query - Query for the database
	 * @param boolean $skipSchema - whether Schema loading should be skipped
	 * @return string
	 */
	public function fill($query = array(), $skipSchema = false)
	{
		$query = new Query($query);
		$this->objects = $this->find($query);
		if (!$skipSchema) {
			$this->loadSchema();
		}
		return $this;
	}

	/**
	 * Clears the Entity table in the database
	 */
	public function clear()
	{
		$this->source->truncate();
	}

	/**
	 * Returns the Schema
	 * @return Schema
	 */
	public function getSchema()
	{
		return $this->schema;
	}

	/**
	 * Returns the entity class name without the namespace
	 * @return string
	 */
	public function getClassName()
	{
		return static::$className;
	}

	/**
	 * Returns the ID (short Entity table name)
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Returns a cached array of Entity objects
	 * @return array
	 */
	public function getObjects()
	{
		return $this->objects;
	}

	/**
	 * Loads Entity table schema
	 * @param array|null $schema
	 * @return Schema
	 */
	public function loadSchema($schema = null)
	{

		$this->schema = new Schema($schema);

		if ($schema) {
			$this->schema = new Schema($schema);
		} else {
			return;
			// /**
			//  * @var SchemaRepository
			//  */
			// $schemaRepo = RepositoryFactory::get("schema");
			// $this->schema = $schemaRepo->findByName();
		}

		$this->remote = $this->schema->remote;
		return $this->schema;
	}

	/**
	 * Returns a full table name of the Entity
	 * @return mixed
	 */
	public static function tableName(string $shortTableName)
	{
		$db = Database::get();
		return $db->mapsvg_prefix . $shortTableName;
	}

	/**
	 * Returns a full table name of the Entity
	 * @return mixed
	 */
	public function getTableName()
	{
		return $this->db->mapsvg_prefix . $this->id;
	}

	/**
	 * Returns a short table name of the Entity (without prefixes)
	 * @return mixed
	 */
	public function getTableNameShort()
	{
		return $this->id;
	}

	/**
	 * Creates new object
	 * @param array $data
	 */
	public function create($data)
	{

		if (is_string($data)) {
			$data = json_decode($data, true);
		}

		if (!is_object($data)) {
			$object    = $this->newObject($data);
		} else {
			$object    = $data;
		}

		$dataForDB = $this->encodeParams($object->getData());

		$data = $this->source->create($dataForDB);
		$object->setId($data["id"]);

		return $object;
	}

	/**
	 * Returns a single Entity object by ID
	 * @return mixed
	 */
	public function findById($id)
	{
		$data = $this->source->findOne(["id" => $id]);

		if ($this->schema->isRemote()) {
			return $data;
		} else {
			return $this->decodeParams((array)$data);
		}
	}

	/**
	 * Returns a single Entity object by query
	 * @param array $where
	 * @return mixed
	 */
	public function findOne($where)
	{
		$data = $this->source->findOne($where);
		if ($this->schema->isRemote()) {
			return $data;
		} else {
			return $this->decodeParams((array)$data);
		}
		return $data;
	}



	/**
	 * Returns an array of Entities by provided Query
	 * @param Query $query Query for the database
	 * @return array<Schema>3
	 */
	public function find(Query $query = null)
	{
		if ($query === null) {
			$query = new Query(array('perpage' => 0));
		}
		$data = $this->source->find($query);

		if ($this->schema->isRemote()) {

			// For remote API data sources, return data as-is
			return $data;
		} else {
			// For local data, format it
			$items = [];


			if ($data && $this->schema->getFieldTypes()) {
				foreach ($data as $index => $object) {
					try {
						$objectFormatted = $this->decodeParams($object);
						$items[] = $objectFormatted;
					} catch (\Exception $e) {
						Logger::error($e);
					}
				}
			}


			$response = array();
			$hasMore = $query->perpage > 0 && count($data) > $query->perpage;

			if ($hasMore) {
				array_pop($items);
			}

			$response = array(
				"items" => $items,
				"page" => $query->page,
				"take" => $query->perpage,
				"perpage" => $query->perpage,
				"hasMore" => $hasMore
			);
			return $response;
		}
	}

	/**
	 * Updates an Entity in the database
	 * @param $object
	 *
	 * @return string
	 */
	public function update($object)
	{
		$params = $this->encodeParams($object);
		$this->source->update($params, array('id' => $params['id']));
		return is_object($object) ? $object : $this->newObject($object);
	}

	/**
	 * Deletes an Entity from the database
	 * @param $object
	 *
	 * @return string
	 */
	public function delete($id)
	{
		return $this->source->delete(array('id' => $id));
	}


	/**
	 * Static method that creates a new table
	 * @param string|array|Schema $schemaOrName
	 * @return string
	 */
	public static function createRepository($schemaOrName)
	{
		$repo = RepositoryFactory::get("schema");
		$name = '';
		if (is_string($schemaOrName)) {
			$schemaData = static::getDefaultSchema();
			$schemaData['name'] = $schemaOrName;
		} elseif (is_array($schemaOrName) || is_object($schemaOrName)) {
			$schemaData = $schemaOrName;
		}

		$schema = $repo->create($schemaData);
		return new static($schemaData['name']);
	}

	/**
	 * Import CSV data to regions / database
	 */
	public function import($data, $convertLatlngToAddress = false)
	{
		$_data  = array();
		foreach ($data as $index => $object) {
			$_data[$index] = $this->encodeParams($object, $convertLatlngToAddress);
		}

		$this->source->import($_data);

		if (isset($_data[0]['regions'])) {
			$this->setRelationsForAllObjects();
		}
	}

	public function setRelationsForAllObjects() {}

	/**
	 * Formats data for the insertion into a database
	 *
	 * @param object|array $data
	 * @param bool $convertLatLngToAddress
	 *
	 * @return array
	 */
	public function encodeParams($data, $convertLatLngToAddress = false)
	{

		$this->geocodingErrors = [];

		if (is_object($data) && method_exists($data, 'getData')) {
			$data = $data->getData();
		}

		$formattedData = array();
		$fieldTypes = $this->schema->getFieldTypes();

		foreach ($data as $key => $value) {
			$field = $this->schema->getField($key);
			if ($field) switch ($field->type) {
				case 'post':
					if (is_array($value)) {
						$formattedData['post'] = $value['id'];
					} elseif (is_object($value)) {
						$formattedData['post'] = $value->id;
					} else {
						$formattedData['post'] = $value;
					}
					break;
				case 'region':
					if (!empty($data[$key]) && is_array($data[$key])) {
						$formattedData[$key] = wp_json_encode($data[$key], JSON_UNESCAPED_UNICODE);
					} else {
						$formattedData[$key] = '';
					}
					break;
				case 'status':
					$key_text = $key . '_text';
					$options = $this->schema->getFieldOptions($field->name);
					if (isset($options[$value]['value'])) {
						$formattedData[$key] = $value;
						$formattedData[$key_text] = $options[$value]['label'];
					} else {
						$formattedData[$key] = '';
						$formattedData[$key . '_text'] = '';
					}
					break;
				case 'select':
				case 'radio':
					$key_text = $key . '_text';
					$fieldOptions = $this->schema->getFieldOptions($field->name);

					if (isset($field->multiselect) && filter_var($field->multiselect, FILTER_VALIDATE_BOOLEAN)) {
						$formattedData[$key] = wp_json_encode($data[$key], JSON_UNESCAPED_UNICODE);
					} else {
						if (isset($fieldOptions[$value])) {
							$formattedData[$key] = $value;
							$formattedData[$key_text] = $fieldOptions[$value]['label'];
						} else {
							if ($value === '') {
								$formattedData[$key] = '';
								$formattedData[$key_text] = '';
							} else {
								$options = [];
								foreach ($fieldOptions as $fieldOptionIndex => $fieldOptionValue) {
									$options[$fieldOptionValue['value']] = $fieldOptions[$fieldOptionIndex];
								}
								if (isset($options[$value])) {
									$formattedData[$key] = $options[$value];
									$formattedData[$key_text] = $value;
								} else {
									$formattedData[$key] = '';
									$formattedData[$key_text] = '';
								}
							}
						}
					}
					break;
				case 'checkbox':
					$formattedData[$key] = (int)($data[$key] === true || $data[$key] === 'true' || $data[$key] === '1' || $data[$key] === 1);
					break;
				case 'image':
				case 'marker':
					if (is_array($data[$key])) {
						$formattedData[$key] = wp_json_encode($data[$key], JSON_UNESCAPED_UNICODE);
					} else {
						$formattedData[$key] = $data[$key];
					}
					break;
				case 'location':
					if (!empty($data[$key])) {
						$location = array();

						if (is_array($data[$key])) {
							$location = $data[$key];
						} else {
							$location = json_decode($data[$key]);
						}

						if (isset($location['geoPoint']) && !empty($location['geoPoint'])) {
							$formattedData['location_lat'] = $location['geoPoint']['lat'];
							$formattedData['location_lng'] = $location['geoPoint']['lng'];
						} else if (isset($location['svgPoint']) && !empty($location['svgPoint'])) {
							$formattedData['location_x'] = $location['svgPoint']['x'];
							$formattedData['location_y'] = $location['svgPoint']['y'];
						}
						if (isset($location['address'])) {
							$formattedData['location_address'] = isset($location['address']) ? wp_json_encode($location['address'], JSON_UNESCAPED_UNICODE) : '';
						}

						$formattedData['location_img'] = isset($location['img']) ? $location['img'] : '';


						if (!empty($location)) {

							$addressYesCoordsNo = (isset($location['address']) && !empty($location['address']) && is_string($location['address']))
								&& (!isset($location['geoPoint']) || empty($location['geoPoint']));
							$addressNoCoordsYes = (!isset($location['address']) || empty($location['address']))
								&& (isset($location['geoPoint']) && !empty($location['geoPoint']));

							if ($addressYesCoordsNo || $addressNoCoordsYes) {
								$geo = new Geocoding();
								if ($addressNoCoordsYes) {
									$response = $geo->get($location['geoPoint']['lat'] . ',' . $location['geoPoint']['lng'], true, $convertLatLngToAddress);
								} elseif ($addressYesCoordsNo) {
									$response = $geo->get($location['address']);
								}

								if ($response && isset($response['status'])) {

									switch ($response['status']) {
										case 'OK':
											$address = array();

											if ($addressNoCoordsYes && $convertLatLngToAddress) {
												$result = $response['results'][1];
											} else {
												$result = $response['results'][0];
											}

											if ($addressYesCoordsNo) {
												$formattedData['location_lat'] = $result['geometry']['location']['lat'];
												$formattedData['location_lng'] = $result['geometry']['location']['lng'];
											} else {
												$formattedData['location_lat'] = $location['geoPoint']['lat'];
												$formattedData['location_lng'] = $location['geoPoint']['lng'];
											}
											$address = array();
											$address['formatted'] = $result['formatted_address'];
											foreach ($result['address_components'] as $addr_item) {
												$type = $addr_item['types'][0];
												$address[$type] = $addr_item['long_name'];
												if ($addr_item['short_name'] != $addr_item['long_name']) {
													$address[$type . '_short'] = $addr_item['short_name'];
												}
											}

											$formattedData['location_address'] = wp_json_encode($address, JSON_UNESCAPED_UNICODE);

											break;
										case 'ZERO_RESULTS':
										case 'OVER_DAILY_LIMIT':
										case 'OVER_QUERY_LIMIT':
										case 'REQUEST_DENIED':
										case 'INVALID_REQUEST':
										case 'UNKNOWN_ERROR':
										case 'CONNECTION_ERROR':
										case 'NO_API_KEY':
											if (count($this->geocodingErrors) < 5) {
												$this->geocodingErrors[] =  $response['status'];
											}
										default:
											null;
											break;
									}
								}
							}
						}
					} else {
						$formattedData['location_address'] = '';
						$formattedData['location_lat'] = null;
						$formattedData['location_lng'] = null;
						$formattedData['location_x'] = null;
						$formattedData['location_y'] = null;
						$formattedData['location_img'] = '';
					}
					break;
				case 'json':
					$formattedData[$key] = wp_json_encode($value, JSON_UNESCAPED_UNICODE);
					break;
				case 'datetime':
					$formattedData[$key] = $value;
					break;
				default:
					$formattedData[$key] = $value;
					break;
			}
		}

		return $formattedData;
	}

	public function decodeParams($data)
	{

		if (!$data) {
			return null;
		}

		$data_formatted = array();

		$data_formatted['id'] = $data['id'];
		$fieldTypes = $this->schema->getFieldTypes();


		foreach ($fieldTypes as $field_name => $field_type) {
			switch ($field_type) {
				case 'status':
					$data_formatted[$field_name] = $data[$field_name];
					if (!empty($data[$field_name . '_text'])) {
						$data_formatted[$field_name . '_text'] = $data[$field_name . '_text'];
					}
					break;
				case 'radio':
				case 'select':
					if (!empty($data[$field_name])) {
						if (strpos($data[$field_name], '[{') === 0) {
							$data_formatted[$field_name] = json_decode(stripslashes($data[$field_name]));
						} else {
							$data_formatted[$field_name] = $data[$field_name];
						}
					}
					if (!empty($data[$field_name . '_text'])) {
						$data_formatted[$field_name . '_text'] = $data[$field_name . '_text'];
					}
					break;
				case 'region':
					if (!empty($data[$field_name])) {
						$data_formatted[$field_name] = json_decode(stripslashes($data[$field_name]));
					}
					break;
				case 'post':
					if (!empty($data['post'])) {
						$data_formatted['post'] = (int)$data['post'];
						$data_formatted['post'] = get_post((int)$data['post']);
						if ($data_formatted['post']) {
							$data_formatted['post']->id = $data_formatted['post']->ID;

							// $data_formatted['post']->post_content = wpautop($data_formatted['post']->post_content);
							// $data_formatted['post']->post_content = apply_filters( 'the_content', do_blocks( preg_replace( '/\[mapsvg.*?\]/', '', $data_formatted['post']->post_content ) ) );

							$data_formatted['post']->post_content = preg_replace('/\[mapsvg.*?\]/', '', $data_formatted['post']->post_content);

							if (has_blocks($data_formatted['post']->post_content)) {
								$blocks = parse_blocks($data_formatted['post']->post_content);
								$content_markup = '';
								foreach ($blocks as $block) {
									// render_block renders a single block into a HTML string
									$content_markup .= render_block($block);
								}
								global $wp_embed;
								$content_markup = $wp_embed->autoembed(do_blocks($content_markup)); //render youtube block properly
								$data_formatted['post']->post_content = wpautop($content_markup);
							}

							$data_formatted['post']->url = get_permalink($data_formatted['post']);
							$data_formatted['post']->image = get_the_post_thumbnail_url($data_formatted['post']->ID, 'full');
							$data_formatted['post']->images = array(
								'thumbnail' => get_the_post_thumbnail_url($data_formatted['post']->ID, 'thumbnail'),
								'medium' => get_the_post_thumbnail_url($data_formatted['post']->ID, 'medium'),
								'large' => get_the_post_thumbnail_url($data_formatted['post']->ID, 'large'),
								'full' => get_the_post_thumbnail_url($data_formatted['post']->ID, 'full')
							);
							// Get ACF fields
							if (self::isAcfActive()) {
								$data_formatted['post']->acf = get_fields($data['post']);
							}
							// Get Metabox fields
							if (self::isMetaboxActive()) {
								$metabox_fields = rwmb_get_object_fields($data_formatted['post']->ID);
								$data_formatted['post']->metabox = [];
								foreach ($metabox_fields as $field_id => $field) {
									$data_formatted['post']->metabox[$field_id] = rwmb_get_value($field_id, [], $data_formatted['post']->ID);
								}
							}
							$data_formatted['post']->meta = get_post_meta($data_formatted['post']->ID);
						}
					}
					break;
				case 'checkbox':
					$data_formatted[$field_name] = (bool)$data[$field_name];
					break;
				case 'image':
				case 'marker':
					if (!empty($data[$field_name])) {
						$data_formatted[$field_name] = json_decode(stripslashes($data[$field_name]));
					}
					break;
				case 'location':
					if (($data['location_lat'] && $data['location_lng'] &&
							(float)$data['location_lat'] !== (float)0 && (float)$data['location_lng'] !== (float)0)
						||
						($data['location_x'] && $data['location_y'])
					) {
						$data_formatted[$field_name] = array(
							'address' => isset($data['location_address']) ? json_decode($data['location_address']) : '',
							'img'     => isset($data['location_img'])     ? $data['location_img'] : ''
						);
						if (!empty($data['location_lat']) && !empty($data['location_lng'])) {
							$data_formatted[$field_name]['geoPoint'] = array('lat' => (float)$data['location_lat'], 'lng' => (float)$data['location_lng']);
						}
						if (!empty($data['location_x']) && !empty($data['location_y'])) {
							$data_formatted[$field_name]['svgPoint'] = array('x' => (float)$data['location_x'], 'y' => (float)$data['location_y']);
						}
					} else {
						$data_formatted[$field_name] = '';
					}
					break;
				case 'datetime':
					if (!empty($data[$field_name])) {
						$data_formatted[$field_name] = $data[$field_name];
					} else {
						$data_formatted[$field_name] = '';
					}
					break;
				case 'json':
					if (!empty($data[$field_name])) {
						$data_formatted[$field_name] = json_decode($data[$field_name], true);
					} else {
						$data_formatted[$field_name] = [];
					}
					break;
				default:
					$data_formatted[$field_name] = isset($data[$field_name]) ? $data[$field_name] : '';
					break;
			}
		}


		return $this->newObject($data_formatted);
	}


	public function getSort($sort)
	{
		return $sort;
	}

	/**
	 * Reads the default schema from .json file which should be present in the same folder where
	 * repository class is located
	 *
	 * @return \Mapsvg\Schema
	 * @throws \ReflectionException
	 */
	public static function getDefaultSchema()
	{
		$reflector = new \ReflectionClass(get_called_class());
		$filename = $reflector->getFileName();
		$dir = dirname($filename);
		$schema_file = $dir . '/schema.json';

		require_once(ABSPATH . 'wp-admin/includes/file.php');
		WP_Filesystem();
		global $wp_filesystem;

		if (!$wp_filesystem->exists($schema_file)) {
			throw new \Exception("Schema file not found: " . esc_html($schema_file));
		}

		$schema = $wp_filesystem->get_contents($schema_file);
		if ($schema === false) {
			throw new \Exception("Failed to read schema file");
		}

		$schema = json_decode($schema, true);
		if (!$schema) {
			throw new \Exception("Invalid JSON in schema file: " . esc_html($schema_file));
		}

		return $schema;
	}
}
