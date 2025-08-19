<?php

namespace MapSVG;

class SVGFileController extends Controller
{

	/**
	 * Forces generated SVG file download in the browser
	 * @param $request
	 *
	 * @return array|string[]
	 * @throws \Exception
	 */
	public static function download($request)
	{
		// Initialize WP_Filesystem
		if (!function_exists('WP_Filesystem')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();
		global $wp_filesystem;

		if (!$wp_filesystem) {
			wp_send_json_error('Filesystem access not available', 500);
			return;
		}

		$file_path = MAPSVG_UPLOADS_DIR . DIRECTORY_SEPARATOR . "mapsvg.svg";

		if ($wp_filesystem->exists($file_path)) {
			$file_contents = $wp_filesystem->get_contents($file_path);

			if ($file_contents === false) {
				wp_send_json_error('Could not read file', 500);
				return;
			}

			// Set headers for file download
			nocache_headers(); // WordPress function to prevent caching
			header('Content-Type: image/svg+xml');
			header('Content-Disposition: attachment; filename=mapsvg.svg');
			header('Content-Length: ' . strlen($file_contents));

			echo $file_contents;
			exit;
		} else {
			wp_send_json_error('File not found', 404);
		}
	}

	/**
	 * Uploads an SVG file.
	 * @param $request
	 * @return \WP_REST_Response
	 */
	public static function create($request)
	{
		$files = $request->get_file_params();
		$filesRepo = new SVGFileRepository();
		$file = new SVGFile($files['file']);
		$file = $filesRepo->create($file);
		return self::render(array('file' => array(
			'name' => $file->name,
			'relativeUrl' => $file->relativeUrl,
			'pathShort' => $file->pathShort,
			'serverPath' => $file->serverPath
		)));
	}

	/**
	 * Updates an SVG file
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	public static function update($request)
	{
		$files = $request->get_file_params();
		$filesRepo = new SVGFileRepository();
		$file = new SVGFile($files['file']);
		$filesRepo->save($file);
		static::updateLastChanged($file);

		return self::render(array('status' => 'OK'));
	}

	/**
	 * Copies an SVG file
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	public static function copy($request)
	{
		$filesRepo = new SVGFileRepository();
		$file = new SVGFile(["relativeUrl" => $request['file']['path']]);
		$newFile = $filesRepo->copy($file);
		return self::render(array('file' => $newFile));
	}

	/**
	 * Updates "lastChanged" timestamp for all maps created from the provided SVG file.
	 * @param SVGFile $file
	 * @param $updateTitles
	 */
	private static function updateLastChanged($file, $updateTitles = null)
	{
		$mapsRepo = RepositoryFactory::get("map");
		$query = new Query(array('filters' => array('svgFilePath' => $file->relativeUrl)));
		$maps = $mapsRepo->find($query);

		foreach ($maps["items"] as $map) {
			/** @var $map Map */
			$map->update(array('svgFileLastChanged' => $file->lastChanged()));
			$mapsRepo->updateFromSvg($map, $updateTitles);
		}
	}

	/**
	 * Updates "lastChanged" timestamp for all maps created from the provided SVG file.
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 */
	public static function reload($request)
	{
		$file = new SVGFile($request['file']);
		$updateTitles = $request['updateTitles'] === 'true';
		static::updateLastChanged($file, $updateTitles);
		return self::render(array('file' => $file));
	}
}
