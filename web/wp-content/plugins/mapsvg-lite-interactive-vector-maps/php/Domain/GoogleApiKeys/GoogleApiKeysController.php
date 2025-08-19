<?php

namespace MapSVG;

/**
 * Google API keys Controller.
 * Contains just one "update" method that updates API keys in the database.
 * @package MapSVG
 */
class GoogleApiKeysController extends Controller {

	public static function update($request){

		$maps_api_key = trim($request['mapsApiKey']);
		$geocoding_api_key = trim($request['geocodingApiKey']);
		if($maps_api_key){
			Options::set('google_api_key', $maps_api_key);
		}
		if($geocoding_api_key){
			Options::set('google_geocoding_api_key', $geocoding_api_key);
		}

		return self::render(array('status'=>'OK'));
	}
}