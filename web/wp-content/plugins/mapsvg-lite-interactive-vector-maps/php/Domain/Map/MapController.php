<?php

namespace MapSVG;

/**
 * Map Controller Class
 * @package MapSVG
 */
class MapController extends Controller
{

	/**
	 * Returns all maps
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	public static function index($request)
	{
		/**
		 * @var MapsRepository
		 */
		$mapsRepository = RepositoryFactory::get("map");
		$query = new Query($request->get_params());
		$maps = $mapsRepository->find($query);

		return self::render($maps);
	}

	/**
	 * Returns a map by ID
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	public static function get($request)
	{
		/**
		 * @var MapsRepository
		 */
		$mapsRepository = RepositoryFactory::get("map");
		$response   = array();
		$map = $mapsRepository->findById($request['id']);

		$response['map'] = $map;


		$withData = isset($request["withData"]) ? explode(",", $request["withData"]) : false;
		$loadRegions = $withData && in_array("regions", $withData);
		$loadObjects = $withData && in_array("objects", $withData);

		$regionsRepo = $map->getRegions();
		$objectsRepo = $map->getObjects();

		/**
		 * Get regions & schema
		 */
		if ($regionsRepo && $loadRegions) {
			$sortBy  = (isset($map->options['menu']) && $map->options['menu']['source'] == 'regions' ? (isset($map->options['menu']['sortBy']) ?  $map->options['menu']['sortBy'] : 'id') : (isset($map->options['menu']) && strpos($map->options['source'], 'geo-cal') !== false ? 'title' : 'id'));
			$sortDir = isset($map->options['menu']) &&  $map->options['menu']['source'] == 'regions' ? (isset($map->options['menu']['sortDirection']) ?  $map->options['menu']['sortDirection'] : 'desc') : 'asc';
			$sort = [["field" => $sortBy, "order" => $sortDir]];

			$regionsQuery = array(
				'perpage' => 0,
				'sortBy'  => (isset($map->options['menu']) && $map->options['menu']['source'] == 'regions' ? (isset($map->options['menu']['sortBy']) ?  $map->options['menu']['sortBy'] : 'id') : (isset($options['menu']) && strpos($map->options['source'], 'geo-cal') !== false ? 'title' : 'id')),
				"sort" => $sort
			);
			if (isset($map->options['menu']) && isset($map->options['menu']['filterout']) && $map->options['menu']['source'] == 'regions' && !empty($map->options['menu']['filterout']['field'])) {
				$regionsQuery['filterout'][$map->options['menu']['filterout']['field']] = $map->options['menu']['filterout']['val'];
			}

			if ($regionsRepo) {
				$response['regions'] = $regionsRepo->find(new Query($regionsQuery));
				$response['regions']["schema"] = $regionsRepo->getSchema();
			} else {
				$response['regions'] = ["items" => [], "hasMore" => false];
			}
		} else {
			$response['regions'] = ["items" => [], "hasMore" => false];
			if ($regionsRepo) {
				$response['regions']["schema"] = $regionsRepo->getSchema();
			}
		}


		/**
		 * Get objects & schema
		 */
		if ($objectsRepo && $loadObjects) {

			$dataType = $objectsRepo->getSchema()->type;
			$sortBy  = (isset($map->options['menu']) && $map->options['menu']['source'] == 'database' ? (isset($map->options['menu']['sortBy']) ?  $map->options['menu']['sortBy'] : 'id') : (isset($map->options['menu']) && strpos($map->options['source'], 'geo-cal') !== false ? 'title' : 'id'));
			$sortDir = isset($map->options['menu']) &&  $map->options['menu']['source'] == 'database' ? (isset($map->options['menu']['sortDirection']) ?  $map->options['menu']['sortDirection'] : 'desc') : 'asc';
			$sort = [["field" => $sortBy, "order" => $sortDir]];

			$objectsQuery = array(
				'perpage' => isset($map->options['database']) && isset($map->options['database']['pagination']) && (int)$map->options['database']['pagination']['on'] ? $map->options['database']['pagination']['perpage'] : 0,
				"sort" => $sort
			);
			if (isset($map->options['menu']) && isset($map->options['menu']['filterout']) && $map->options['menu']['source'] == 'database' && !empty($map->options['menu']['filterout']['field'])) {
				$objectsQuery['filterout'][$map->options['menu']['filterout']['field']] = $map->options['menu']['filterout']['val'];
			}


			if ($objectsRepo) {
				// For API need to request from front-end to be able to use request middleware
				if ($dataType !== "api") {
					// Need to keep "objects" generic name here! Because on map load we don't know schema name yet						
					$response['objects'] = $objectsRepo->find(new Query($objectsQuery));
				} else {
					$response['objects'] = [];
				}

				$response['objects']["schema"] = $objectsRepo->getSchema();
			} else {
				$response['objects'] = ["items" => [], "hasMore" => false];
			}
		} else {
			$response['objects'] = ["items" => [], "hasMore" => false];
			// if ($objectsRepo) {
			// 	$response['objects']["schema"] = $objectsRepo->getSchema();
			// }
		}



		return self::render($response, 200);;
	}

	/**
	 * Returns a map by ID
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	public static function getSvg($request)
	{
		$mapsRepository = RepositoryFactory::get("map");
		$map = $mapsRepository->findById($request['id']);
		$svgFile = new SVGFile(["relativeUrl" => $map->options['source']]);

		$etag = $map->svgFileLastChanged;
		$last_modified = gmdate('D, d M Y H:i:s', $map->svgFileLastChanged) . ' GMT';

		// Get headers sent by the client - properly sanitized
		$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_IF_MODIFIED_SINCE'])) : false;
		$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_IF_NONE_MATCH'])) : false;

		// Check if the file has been modified
		if (($if_none_match && $if_none_match == $etag) || ($if_modified_since && $if_modified_since == $last_modified)) {
			// File has not been modified
			header('HTTP/1.1 304 Not Modified');
			exit;
		}

		// Use WordPress Filesystem API
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		WP_Filesystem();
		global $wp_filesystem;

		// Output file contents
		if ($wp_filesystem->exists($svgFile->serverPath)) {
			header('Content-Type: image/svg+xml');
			clearstatcache(true, $svgFile->serverPath);
			header('Content-Length: ' . $wp_filesystem->size($svgFile->serverPath));
			$maxAge = 30 * 24 * 60 * 60; // 1 month
			header('Cache-Control: public, max-age=' . $maxAge);
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
			header('Last-Modified: ' . $last_modified);

			// Use WordPress Filesystem to read and output file
			echo $wp_filesystem->get_contents($svgFile->serverPath); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			Logger::error("SVG file not found: " . $svgFile->serverPath);
			return new \WP_Error('file_open_error', 'Unable to open SVG file', array('status' => 404));
		}

		exit;
	}


	/**
	 * Creates a new map
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	public static function create($request)
	{

		$map = $request['map'];

		/**
		 * @var MapsRepository
		 */
		$mapsRepository = RepositoryFactory::get("map");

		$map["options"] = json_decode($map["options"], true);
		$map["options"]["filtersSchema"] =
			[
				 
				// START distance_search
				["type" => "distance", "db_type" => "varchar(255)", "label" => "Search by address", "name" => "distance", "value" => "", "searchable" => "", "options" => [["value" => "10", "default" => true, "selected" => true], ["value" => "30", "default" => false], ["value" => "50", "default" => false], ["value" => "100", "default" => false]], "optionsDict" => [], "distanceControl" => "select", "distanceUnits" => "km", "distanceUnitsLabel" => "km", "fromLabel" => "from", "addressField" => true, "addressFieldPlaceholder" => "Address", "userLocationButton" => "", "placeholder" => "", "language" => "", "country" => "", "searchByZip" => "", "zipLength" => 5, "parameterName" => "Object.distance", "parameterNameShort" => "distance", "visible" => true]
				// END				
			];


		// Create map
		$map = $mapsRepository->create([
			'options' => $map['options'],
			'version' => MAPSVG_VERSION
		]);



		// Define schemas and update model in DB
		if (!isset($map->options['database']) || !isset($map->options['database']['schemas'])) {

			if (!isset($map->options['database'])) {
				$map->options['database'] = [];
			}
			if (!isset($map->options['database']['schemas'])) {
				$map->options['database']['schemas'] = [];
			}

			$regionsTableName = "regions_" . $map->id;
			$objectsTableName = "objects_" . $map->id;

			$map->options['database']['schemas'] = [
				'regions' => [
					'objectNameSingular' => 'region',
					'objectNamePlural' => 'regions',
					'name' => $regionsTableName,
					'apiEndpoints' => [
						['url' => 'regions/' . $regionsTableName, 'method' => 'GET', 'name' => 'index'],
						['url' => 'regions/' . $regionsTableName . '/[:id]', 'method' => 'GET', 'name' => 'show'],
						['url' => 'regions/' . $regionsTableName, 'method' => 'POST', 'name' => 'create'],
						['url' => 'regions/' . $regionsTableName . '/[:id]', 'method' => 'PUT', 'name' => 'update'],
						['url' => 'regions/' . $regionsTableName . '/[:id]', 'method' => 'DELETE', 'name' => 'delete'],
						['url' => 'regions/' . $regionsTableName, 'method' => 'DELETE', 'name' => 'clear'],
					],
				],
				'objects' => [
					'objectNameSingular' => 'object',
					'objectNamePlural' => 'objects',
					'name' => $objectsTableName,
					'apiEndpoints' => [
						['url' => 'objects/' . $objectsTableName, 'method' => 'GET', 'name' => 'index'],
						['url' => 'objects/' . $objectsTableName . '/[:id]', 'method' => 'GET', 'name' => 'show'],
						['url' => 'objects/' . $objectsTableName, 'method' => 'POST', 'name' => 'create'],
						['url' => 'objects/' . $objectsTableName . '/[:id]', 'method' => 'PUT', 'name' => 'update'],
						['url' => 'objects/' . $objectsTableName . '/[:id]', 'method' => 'DELETE', 'name' => 'delete'],
						['url' => 'objects/' . $objectsTableName, 'method' => 'DELETE', 'name' => 'clear'],
					],
				],
			];
		}

		$mapsRepository->update($map);

		// Create repos
		$map->regions = RepositoryFactory::create($map->options['database']['schemas']["regions"]["name"]);
		$map->objects = RepositoryFactory::create($map->options['database']['schemas']["objects"]["name"]);

		// Load regions table
		$map->setRegionsTable();

		return self::render(['map' => $map], 200);
	}

	/**
	 * Creates a new map based on the settings of a v2.4.1 map
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public static function createFromV2($request)
	{

		/**
		 * @var MapsRepository
		 */
		$mapsRepository = RepositoryFactory::get("map");
		/**
		 * @var SchemaRepository
		 */
		$schemaRepo = RepositoryFactory::get("schema");

		$mapData = $request['map'];

		if (is_string($mapData['options'])) {
			$mapData['options'] = json_decode($mapData['options'], true);
		}

		$map = $mapsRepository->create([
			'options' => $mapData['options'],
			'version' => MAPSVG_VERSION
		]);

		// 1. Add tooltip/popover fields to regions and objects tables
		$schemaRegions = $map->getRegions()->getSchema();

		$addTooltipToRegions = false;
		$addPopoverToRegions = false;
		$addTooltipToObjects = false;
		$addPopoverToObjects = false;

		$goToLinkRegion = false;
		$goToLinkMarker = false;

		if (isset($mapData['options']['regions'])) {

			// Set disabled status
			$update = false;
			$statusData = array();
			foreach ($mapData['options']['regions'] as $region) {
				if (
					isset($region['href']) ||
					isset($region['disabled']) ||
					(isset($region['tooltip']) && strlen($region['tooltip']) > 0) ||
					(isset($region['popover']) && strlen($region['popover']) > 0)
				) {
					$_data = array(
						'id' => $region['id']
					);

					if (isset($region['href'])) {
						$_data['link'] = $region['href'];
						$goToLinkRegion = true;
					}
					if (isset($region['disabled'])) {
						$_data['status'] = filter_var($region['disabled'], FILTER_VALIDATE_BOOLEAN);
					}
					if (isset($region['tooltip']) && strlen($region['tooltip']) > 0) {
						$addTooltipToRegions = true;
						$_data['tooltip'] = $region['tooltip'];
					}

					if (isset($region['popover']) && strlen($region['popover']) > 0) {
						$addPopoverToRegions = true;
						$_data['popover'] = $region['popover'];
					}
					$updateData[] = $_data;
				}
			}

			if ($addPopoverToRegions) {
				$schemaRegions->addField(array(
					"name"    => "popover",
					"label"   => "Popover",
					"type"    => "textarea",
					"db_type" => "text",
					"visible" => true,
					"html" => true,
					"help" => "Use plain text or HTML"
				));
			}
			if ($addTooltipToRegions) {
				$schemaRegions->addField(array(
					"name"    => "tooltip",
					"label"   => "Tooltip",
					"type"    => "textarea",
					"db_type" => "text",
					"visible" => true,
					"html" => true,
					"help" => "Use plain text or HTML"
				));
			}
			if ($addPopoverToRegions || $addTooltipToRegions) {
				$schemaRepo->update($schemaRegions);
			}

			if (count($updateData) > 0) {
				$map->getRegions()->createOrUpdateAll($updateData);
			}
		}

		if (isset($mapData['options']['markers'])) {
			foreach ($mapData['options']['markers'] as $marker) {
				if (isset($marker['tooltip']) && strlen($marker['tooltip']) > 0) {
					$addTooltipToObjects = true;
				}
				if (isset($marker['popover']) && strlen($marker['popover']) > 0) {
					$addPopoverToObjects = true;
				}
			}

			$schemaObjects = $map->getObjects()->getSchema();

			if ($addPopoverToObjects) {
				$schemaObjects->addField(array(
					"name"    => "popover",
					"label"   => "Popover",
					"type"    => "textarea",
					"db_type" => "text",
					"visible" => true,
					"html" => true,
					"help" => "Use plain text or HTML"
				));
			}
			if ($addTooltipToObjects) {
				$schemaObjects->addField(array(
					"name"    => "tooltip",
					"label"   => "Tooltip",
					"type"    => "textarea",
					"db_type" => "text",
					"visible" => true,
					"html" => true,
					"help" => "Use plain text or HTML"
				));
			}
			if ($addPopoverToObjects || $addTooltipToObjects) {
				$schemaRepo->update($schemaObjects);
			}
		}

		// 2. Add data from map.options.regions to the regions table
		// 3. Convert map.options.markers to objects and insert to the objects table
		if (isset($mapData['options']['markers'])) {
			foreach ($mapData['options']['markers'] as $marker) {
				$object = array(
					'location' => array(
						'img' => $marker['src']
					)
				);
				if (isset($marker['href'])) {
					$object['link'] = $marker['href'];
					$goToLinkMarker = true;
				}
				if (isset($marker['geoCoords'])) {
					$object['location']['geoPoint'] = array("lat" => $marker['geoCoords'][0], "lng" => $marker['geoCoords'][1]);
				}
				if (isset($marker['x']) && isset($marker['y'])) {
					$object['location']['svgPoint'] = array("x" => $marker['x'], "y" => $marker['y']);
				}
				$map->getObjects()->create($object);
			}
		}
		// 4. Move events
		$oldEvents = [
			'afterLoad' => ['afterLoad'],
			'beforeLoad' => ['beforeLoad'],
			'onClick' => ['click.region', 'click.marker'],
			'mouseOver' => ['mouseover.region', 'mouseover.marker'],
			'mouseOut' => ['mouseout.region', 'mouseout.marker']
		];

		$mapData['options']['events'] = array();
		foreach ($oldEvents as $oldEvtName => $newEvents) {
			if (isset($mapData['options'][$oldEvtName])) {
				foreach ($newEvents as $newEventName) {
					$map->options['events'][$newEventName] = $mapData['options'][$oldEvtName];
				}
			}
		}

		// 5. If gauge == ON add the "choropleth" fields to regions
		// TODO Vyacheslav: Проверить работу после апгрейда choropleth механик
		if (isset($mapData['options']['choropleth']) && isset($mapData['options']['choropleth']['on']) && filter_var($mapData['options']['choropleth']['on'], FILTER_VALIDATE_BOOLEAN)) {
			$map->options['choropleth']['sourceField'] = 'stat';
			$schemaRegions->addField(array(
				"name" => "stat",
				"label" => "Stat",
				"type" => "text",
				"db_type" => "varchar(10)",
				"visible" => true
			));
			$schemaRepo->update($schemaRegions);
			foreach ($mapData['options']['regions'] as $region) {
				if (isset($region['gaugeValue'])) {
					$newRegionData = array(
						'id' => $region['id'],
						'stat' => $region['gaugeValue']
					);
					$map->getRegions()->update($newRegionData);
				}
			}
		}

		// 6. Convert menu
		if (isset($map->options['menu']) && isset($map->options['menu']['on']) && filter_var($map->options['menu']['on'], FILTER_VALIDATE_BOOLEAN)) {
			$map->options['menu']['source'] = 'regions';
			$map->options['containers'] = ["leftSidebar" => ["on" => true]];
		} elseif (isset($map->options['menuMarkers']) && isset($map->options['menuMarkers']['on']) && filter_var($map->options['menuMarkers']['on'], FILTER_VALIDATE_BOOLEAN)) {
			$map->options['menuMarkers']['source'] = 'database';
			$map->options['containers'] = ["leftSidebar" => ["on" => true]];
		}

		// 7. Convert templates, set actions
		if (!isset($map->options['tooltips'])) {
			$map->options['tooltips'] = [];
		}
		$map->options['tooltips']['on'] = true;
		$map->options['actions']['region']['click']['showPopover'] = true;
		$map->options['actions']['region']['click']['showDetails'] = false;
		$map->options['actions']['marker']['click']['showPopover'] = true;
		$map->options['actions']['marker']['click']['showDetails'] = false;
		$map->options['actions']['directoryItem']['click']['showPopover'] = true;
		$map->options['actions']['directoryItem']['click']['showDetails'] = false;
		if ($goToLinkMarker) {
			$map->options['actions']['marker']['click']['showPopover'] = false;
			$map->options['actions']['marker']['click']['goToLink'] = true;
		}
		if ($goToLinkRegion) {
			$map->options['actions']['region']['click']['showPopover'] = false;
			$map->options['actions']['region']['click']['goToLink'] = true;
		}
		$map->options['templates']['popoverMarker'] = '{{popover}}';
		$map->options['templates']['popoverRegion'] = '{{popover}}';
		$map->options['templates']['tooltipMarker'] = '{{tooltip}}';
		$map->options['templates']['tooltipRegion'] = '{{tooltip}}';

		$mapsRepository->update($map);

		return self::render(['map' => $map], 200);;
	}

	/**
	 * Creates a copy of a map
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	public static function copy($request)
	{
		$repository = RepositoryFactory::get("map");
		$newMap = $repository->copy($request['id'], json_decode($request['options'], true));
		return self::render(['map' => $newMap], 200);
	}

	/**
	 * Updates a map
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	public static function update($request)
	{

		$mapData = (array)$request['map'];

		// Prevent blockage by Apache's mod_sec
		if (isset($request['map']) && isset($request['map']['options'])) {
			$mapData['options'] = str_replace("!mapsvg-encoded-slct", "select", $mapData['options']);
			$mapData['options'] = str_replace("!mapsvg-encoded-tbl", "table", $mapData['options']);
			$mapData['options'] = str_replace("!mapsvg-encoded-db", "database", $mapData['options']);
			$mapData['options'] = str_replace("!mapsvg-encoded-vc", "varchar", $mapData['options']);
			$mapData['options'] = str_replace("!mapsvg-encoded-int", "int(11)", $mapData['options']);
		}

		$mapsRepository = RepositoryFactory::get("map");
		$map = $mapsRepository->findById($mapData['id']);
		$map->update($mapData);
		$mapsRepository->update($map);

		return self::render([], 200);
	}

	/**
	 * Updates a map status by ID
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public static function delete($request)
	{

		$mapsRepository = RepositoryFactory::get("map");
		$map = $mapsRepository->findById($request['id']);
		$map->setStatus(0);
		$mapsRepository->update($map);

		return self::render([], 200);
	}
}
