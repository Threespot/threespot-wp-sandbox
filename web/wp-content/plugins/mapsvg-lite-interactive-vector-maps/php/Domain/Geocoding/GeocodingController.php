<?php

namespace MapSVG;

require_once 'Geocoding.php';

/**
 * Geocoding Controller Class.
 * Handles requests to Google Geocoding API.
 */
class GeocodingController extends Controller {

	public static function index($request) {
		$geo = new Geocoding();
		$response = $geo->get($request['address'], true);
		return self::render($response);
	}
}