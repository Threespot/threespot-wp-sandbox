<?php

namespace MapSVG;

/**
 * Controller Class for purchase code.
 * @package MapSVG
 */
class PurchasecodeController extends Controller
{

	/**
	 * Checks and updates purchase code in the database
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 */
	public static function update($request)
	{

		$licenseKey = $request['purchase_code'];


		$response = Remote::get(MAPSVG_API_URL . '/licenses/' . $licenseKey . '/validate/' . MAPSVG_PLAN);
		$error = "Something went wrong";

		if ($response && isset($response['body'])) {
			$data = json_decode($response['body'], true);
			if ($response['status'] !== "OK") {
				if (isset($data['error'])) {
					$error = $data['error'];
				}
			}
		}

		if ($response && $response['status'] === "OK") {
			Options::set('purchase_code', $licenseKey);
			return self::render([], 200);
		} else {
			return self::render(["error" => $error], 400);
		}
	}
}
