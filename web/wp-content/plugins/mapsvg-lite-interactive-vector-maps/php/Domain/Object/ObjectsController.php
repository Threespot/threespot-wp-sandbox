<?php

namespace MapSVG;

use Clockwork\Request\Log;

/**
 * Objects Controller Class
 * @package MapSVG
 */
class ObjectsController extends Controller
{

	public static function create($request)
	{
		$repo = RepositoryFactory::get($request['_collection_name']);
		$response = array();
		if ($request[$repo->schema->objectNameSingular]) {
			$response[$repo->schema->objectNameSingular] = $repo->create($request[$repo->schema->objectNameSingular]);
			return self::render($response, 200);
		} else {
			return self::render(array(), 400);
		}

		return self::render($response, 200);
	}

	public static function get($request)
	{
		$repo = RepositoryFactory::get($request['_collection_name']);
		$response   = array();
		$response[$repo->schema->objectNameSingular] = $repo->findById($request['id']);
		if ($response[$repo->schema->objectNameSingular]) {
			return self::render($response, 200);
		} else {
			return self::render(["message" => "Object not found"], 404);
		}
	}

	public static function index($request)
	{
		$repo = RepositoryFactory::get($request['_collection_name']);
		$response   = array();

		$query = new Query($request->get_params());

		$response = $repo->find($query);

		if ($query->withSchema) {
			$response['schema'] = $repo->getSchema();
		}
		return self::render($response, 200);
	}

	public static function clear($request)
	{
		$repo = RepositoryFactory::get($request['_collection_name']);
		$repo->clear();
		return self::render([], 200);
	}

	public static function update($request)
	{
		$repo = RepositoryFactory::get($request['_collection_name']);
		$name = $repo->schema->objectNameSingular;
		$object = $repo->findById($request[$name]['id']);
		$objectData = $object->getData();
		$object->update($request[$name]);
		$repo->update($object);
		$schema = $repo->getSchema();
		if (strpos($schema->name, "posts_") !== false) {
			$objectData = $object->getData();
			if ($objectData['post']) {
				if ($request[$name]['location']) {
					update_post_meta($objectData['post']->id, "mapsvg_location", wp_json_encode($objectData['location'], JSON_UNESCAPED_UNICODE));
				} else {
					delete_post_meta($objectData['post']->id, "mapsvg_location");
				}
			}
		}
		return self::render([], 200);
	}

	public static function delete($request)
	{

		$repo = RepositoryFactory::get($request['_collection_name']);
		$name = $repo->schema->objectNameSingular;
		$object = $repo->findById($request['id']);
		$schema = $repo->getSchema();
		if (strpos($schema->name, "posts_") !== false) {
			$objectData = $object->getData();
			if ($objectData['post']) {
				if ($request[$name]['location']) {
					update_post_meta($objectData['post']->id, "mapsvg_location", wp_json_encode($objectData['location'], JSON_UNESCAPED_UNICODE));
				} else {
					delete_post_meta($objectData['post']->id, "mapsvg_location");
				}
			}
		}

		$repo->delete($request['id']);
		return self::render([], 200);
	}

	/**
	 * Imports data from a CSV file
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public static function import($request)
	{

		$repo = RepositoryFactory::get($request['_collection_name']);
		$name = $repo->schema->objectNamePlural;
		$data = json_decode($request[$name], true);
		$convertLatLngToAddress = filter_var($request['convertLatlngToAddress'], FILTER_VALIDATE_BOOLEAN);
		$repo->import($data, $convertLatLngToAddress);

		if (isset($repo->geocodingErrors) && count($repo->geocodingErrors) > 0) {
			$response = [];
			$response["error"] = ["geocodingError" => $repo->geocodingErrors];
			return self::render($response, 400);
		} else {
			return self::render([], 200);
		}
	}
}
