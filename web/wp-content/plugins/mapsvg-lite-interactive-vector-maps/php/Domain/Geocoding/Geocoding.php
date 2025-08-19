<?php


namespace MapSVG;

/**
 * Geocoding Class.
 * Handles requests to Google Geocoding API.
 */
class Geocoding
{

	public $geocoding_quota_per_second;
	private $permanent_error;
	private $apiKey;

	public function __construct($apiKey = '')
	{
		$this->geocoding_quota_per_second = 1;
		$this->apiKey = $apiKey;
		$this->permanent_error = '';
	}

	public function get($address, $return_as_array = true, $convert_latlng_to_address = true)
	{

		if (empty($address)) {
			return false;
		}

		if ($this->permanent_error !== '') {
			return $return_as_array ? json_decode($this->permanent_error, true) : $this->permanent_error;
		}

		if (!$this->apiKey) {
			$this->apiKey = Options::get('google_geocoding_api_key');
			if (!$this->apiKey) {
				$this->apiKey = Options::get('google_api_key');
			}
		}
		$address_is_coordinates = false;
		$reg_latlng = "/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?)[\s]?[,\s]?[\s]?[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/";
		if (preg_match($reg_latlng, $address)) {
			$address_is_coordinates = true;
			if (strpos($address, ',') !== false) {
				$delimiter = ',';
			} elseif (strpos($address, ' ') !== false) {
				$delimiter = ' ';
			}
			$coords = explode($delimiter, $address);
			$coords[0] = trim($coords[0]);
			$coords[1] = trim($coords[1]);
			$coords_item = array(
				"geometry" => array("location" => array("lat" => $coords[0], "lng" => $coords[1])),
				"formatted_address" => $address,
				"address_components" => array()
			);
		}

		if ((!$address_is_coordinates || $convert_latlng_to_address === true) && $this->apiKey) {
			if ($this->geocoding_quota_per_second > 49) {
				sleep(1);
				$this->geocoding_quota_per_second = 1;
			}
			$address = urlencode($address);

			// if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'mapsvg_geocoding')) {
			// 	return new \WP_Error('invalid_nonce', 'Nonce verification failed');
			// }
			$lang = isset($_REQUEST['language']) ? sanitize_text_field(wp_unslash($_REQUEST['language'])) : 'en';  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
			$country = isset($_REQUEST['country']) ? '&components=country:' . sanitize_text_field(wp_unslash($_REQUEST['country'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

			$data    = Remote::get('https://maps.googleapis.com/maps/api/geocode/json?key=' . $this->apiKey . '&address=' . $address . '&sensor=true&language=' . $lang . $country);
			if ($data && !isset($data['error_message'])) {
				$response = json_decode($data['body'], true);
				if ($response['status'] === 'OVER_DAILY_LIMIT' || $response['status'] === 'OVER_QUERY_LIMIT') {
					$this->permanent_error = $data;
				} else {
					if ($address_is_coordinates) {
						array_unshift($response['results'], $coords_item);
					}
				}
			} else {
				$response = $data;
			}
		} else {
			if ($address_is_coordinates) {
				$response = array(
					"status"  => "OK",
					"results" => array($coords_item)
				);
			} else {
				$response = array('status' => 'NO_API_KEY', 'error_message' => 'No Google Geocoding API key. Add the key on MapSVG start screen.');
			}
		}
		return $return_as_array ? $response : wp_json_encode($response);
	}
}
