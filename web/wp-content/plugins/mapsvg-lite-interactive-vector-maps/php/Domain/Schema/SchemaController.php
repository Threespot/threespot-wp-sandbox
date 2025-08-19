<?php

namespace MapSVG;


class SchemaController extends Controller
{

	/**
	 * Returns all schemas
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public static function index($request)
	{
		$schemaRepository = RepositoryFactory::get("schema");
		$response   = array();
		$query = new Query($request->get_params());
		$response = $schemaRepository->find($query);

		return new \WP_REST_Response($response, 200);
	}

	/**
	 * Creates new schema
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public static function create($request)
	{

		if (!isset($request['schema'])) {
			return new \WP_REST_Response(["data" => ["error" => "Schema is required"]], 400);
		}
		$data = static::formatReceivedData($request['schema']);
		$schemaRepository = RepositoryFactory::get("schema");

		$originalName = $data["name"];
		// Replace dashes with underscores in the table name
		$data["name"] = str_replace('-', '_', $data["name"]);

		$tableExists = $schemaRepository->findByName($data["name"]);
		if ($tableExists) {
			return new \WP_REST_Response(["data" => ["error" => "Data source with the name '" . $data["name"] . "' already exists"]], 400);
		}

		$schemaRepository = RepositoryFactory::get("schema");
		$response = array();

		$response['schema'] = $schemaRepository->create($data);


		if ($data["type"] === "post") {
			$post_types = Options::get("mappable_post_types") ?? [];
			$post_type = str_replace('posts_', '', $originalName);

			if (!in_array($post_type, $post_types)) {
				$post_types[] = $post_type;
				Options::set("mappable_post_types", $post_types);
			}
		}

		return self::render($response, 200);
	}

	/**
	 * Updates schema
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public static function update($request)
	{
		$schemaRepository = RepositoryFactory::get("schema");
		$data = static::formatReceivedData($request['schema']);

		if (isset($data['name'])) {
			if ($data["type"] === "post") {
				// Prohibit changing the name for the schema for posts
				unset($data['name']);
			} else {
				// Replace dashes with underscores in the table name
				$data["name"] = str_replace('-', '_', $data["name"]);
			}
		}
		$schemaRepository->update($data);

		return self::render([], 200);
	}

	/**
	 * Workaround for Apache mod_sec module that blocks request by special words
	 * such as "select, table, database, varchar".
	 * Those words are replaced by MapSVG with special placeholders on the client side
	 * before sending the data to server. Then those placeholders need to be replaced back with the words.
	 *
	 * @param array $data
	 * @return array
	 */
	public static function formatReceivedData($data)
	{

		if (isset($data)) {
			if (!is_string($data)) {
				$data = wp_json_encode($data, JSON_UNESCAPED_UNICODE);
			}
			$data = str_replace("!mapsvg-encoded-slct", "select",   $data);
			$data = str_replace("!mapsvg-encoded-tbl",  "table",    $data);
			$data = str_replace("!mapsvg-encoded-db",   "database", $data);
			$data = str_replace("!mapsvg-encoded-vc",   "varchar",  $data);
			$data = str_replace("!mapsvg-encoded-int",   "int(11)",  $data);
			$data = json_decode($data, true);
		}
		return $data;
	}
}
