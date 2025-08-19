<?php

namespace MapSVG;

class RegionsController extends Controller
{

	public static function index($request)
	{
		$regionsRepository = RepositoryFactory::get($request['_collection_name']);
		$response   = array();

		$query = new Query($request->get_params());

		$response = $regionsRepository->find($query);

		if ($query->withSchema) {
			$response['schema'] = $regionsRepository->getSchema();
		}
		return self::render($response, 200);
	}

	public static function create($request)
	{

		$regionsRepository = RepositoryFactory::get($request['_collection_name']);
		$response = array();
		$response['region'] = $regionsRepository->create($request['region']);
		return self::render($response, 200);
	}

	public static function get($request)
	{
		$regionsRepository = RepositoryFactory::get($request['_collection_name']);
		$response   = array();
		$response['region'] = $regionsRepository->findById($request['id']);
		if ($response['region']) {
			return self::render($response, 200);
		} else {
			return self::render(["message" => "Region not found"], 404);
		}
	}



	public static function clear($request)
	{
		$regionsRepository = RepositoryFactory::get($request['_collection_name']);
		$regionsRepository->clear();
		return self::render([], 200);
	}

	public static function update($request)
	{
		$regionsRepository = RepositoryFactory::get($request['_collection_name']);
		$regionsRepository->update($request['region']);
		return self::render([], 200);
	}

	public static function delete($request)
	{
		$regionsRepository = RepositoryFactory::get($request['_collection_name']);
		$regionsRepository->delete($request['id']);
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

		$regionsRepository = RepositoryFactory::get($request['_collection_name']);

		$data = json_decode($request['regions'], true);
		$regionsRepository->import($data);

		return self::render([], 200);
	}
}
