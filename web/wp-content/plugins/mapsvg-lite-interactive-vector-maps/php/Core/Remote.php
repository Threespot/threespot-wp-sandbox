<?php

namespace MapSVG;

/**
 * Class that gets data from a remote server via WordPress HTTP API
 * @package MapSVG
 */
class Remote
{
	public static function get($url)
	{
		$response = wp_remote_get($url, array(
			'timeout' => 30,
			'sslverify' => true
		));

		if (is_wp_error($response)) {
			return array(
				"body" => "",
				"status" => "ERROR",
				"error_message" => $response->get_error_message()
			);
		}

		return array(
			"body" => wp_remote_retrieve_body($response),
			"status" => "OK"
		);
	}
}
