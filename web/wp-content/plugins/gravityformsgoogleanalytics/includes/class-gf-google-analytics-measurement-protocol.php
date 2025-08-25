<?php

namespace Gravity_Forms\Gravity_Forms_Google_Analytics;

defined( 'ABSPATH' ) || die();

use GFFormsModel;

/**
 * Gravity Forms Google Analytics Measurement Protocol.
 *
 * @since     1.0.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2019, Rocketgenius
 */
class GF_Google_Analytics_Measurement_Protocol {
	/**
	 * The Endpoint for the Measurement Protocol
	 *
	 * @since 1.0.0
	 * @var string $endpoint The Measurement Protocol endpoint.
	 */
	//private $endpoint = 'https://www.google-analytics.com/debug/mp/collect?'; // Debug endpoint.
	private $endpoint = 'https://www.google-analytics.com/mp/collect?';

	/**
	 * The Client ID for the Measurement Protocol
	 *
	 * @since 1.0.0
	 * @var string $cid The Client ID.
	 */
	private $cid = '';

	/**
	 * The Measurement Protocol hit type
	 *
	 * @since 1.0.0
	 * @var string $t Hit Type.
	 */
	private $t = 'event';

	/**
	 * The document path
	 *
	 * @since 1.0.0
	 * @var string $dp The document path.
	 */
	private $dp = '';

	/**
	 * The document location
	 *
	 * @since 1.0.0
	 * @var string $dl The document location.
	 */
	private $dl = '';

	/**
	 * The document title
	 *
	 * @since 1.0.0
	 * @var string $dt The document title.
	 */
	private $dt = '';

	/**
	 * The document host name
	 *
	 * @since 1.0.0
	 * @var string $dh The document host name.
	 */
	private $dh = '';

	/**
	 * The IP Address of the user.
	 *
	 * @since 1.0.0
	 * @var string $uip The IP Address of the user.
	 */
	private $uip = '';

	/**
	 * The API secret for sending events.
	 *
	 * @since 1.0.0
	 * @var string $api_secret The API secret
	 */
	private $api_secret = '';

	/**
	 * The Submission Parameters for the feed.
	 *
	 * @since 2.0.0
	 * @var array $parameters The submission parameters
	 */
	private $parameters = array();

	/**
	 * The name for the event.
	 *
	 * @since 2.0.0
	 * @var bool $event_name The event name to be sent to Google Analytics.
	 */
	private $event_name = '';

	/**
	 * Init function. Attempts to get the client's CID
	 *
	 * @since 1.0.0
	 */
	public function init( $api_secret, $event_name = 'gform_submission' ) {
		$this->cid        = $this->create_client_id();
		$this->api_secret = $api_secret;
		$this->event_name = $event_name;
	}

	/**
	 * Sets the custom event parameters
	 *
	 * @since 2.0.0
	 *
	 * @param array $parameters The user's IP address.
	 */
	public function set_params( $parameters ) {
		$this->parameters = $parameters;
	}

	/**
	 * Gets the custom event parameters.
	 *
	 * @since 2.3
	 *
	 * @return array Returns the custom event parameters.
	 */
	public function get_params() {
		return $this->parameters;
	}

	/**
	 * Sets the User's IP
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_ip The user's IP address.
	 */
	public function set_user_ip_address( $user_ip ) {
		$this->uip = $user_ip;
	}

	/**
	 * Sets the document path
	 *
	 * @since 1.0.0
	 *
	 * @param string $document_path The path of the document.
	 */
	public function set_document_path( $document_path ) {
		$this->dp = $document_path;
	}

	/**
	 * Sets the document host
	 *
	 * @since 1.0.0
	 *
	 * @param string $document_host The host of the document.
	 */
	public function set_document_host( $document_host ) {
		$this->dh = $document_host;
	}

	/**
	 * Sets the document location
	 *
	 * @since 1.0.0
	 *
	 * @param string $document_location The location of the document.
	 */
	public function set_document_location( $document_location ) {
		$this->dl = $document_location;
	}

	/**
	 * Sets the document title
	 *
	 * @since 1.0.0
	 *
	 * @param string $document_title The document title for the page being submitted.
	 */
	public function set_document_title( $document_title ) {
		$this->dt = $document_title;
	}

	/**
	 * Sends the data to the measurement protocol
	 *
	 * @since 1.0.0
	 *
	 * @param string $ua_code    The UA code to send the event to.
	 * @param string $event_name The event name to be used.
	 */
	public function send( $google_analytics_code ) {

		// Get variables in wp_remote_post body format.
		$user_properties_vars = array( 'dp', 'dl', 'dt', 'dh', 'uip' );
		$user_properties      = array();
		foreach ( $user_properties_vars as $key => $user_properties_var ) {
			if ( empty( $this->{ $user_properties_vars[ $key ] } ) ) {
				// Empty params cause the payload to fail in testing.
				continue;
			}
			$user_properties[ $user_properties_var ] = $this->{$user_properties_vars[ $key ]};
		}

		$session_id = $this->get_browser_session_id( $google_analytics_code );

		if ( $session_id ) {
			$this->parameters['session_id'] = $session_id;
		}

		$url = $this->endpoint . 'measurement_id=' . $google_analytics_code . '&api_secret=' . $this->api_secret;

		$body = array(
			'client_id' => $this->cid,
			'events'    => array(
				'name'   => $this->event_name,
				'params' => $this->parameters,
			),
		);

		gf_google_analytics()->log_debug( __METHOD__ . '(): Sending data to Google Analytics Measurement Protocol. URL (last 4 of api_secret only): ' . substr( $url, 0, strpos( $url, 'api_secret=' ) + 11 ) . 'XXXXXXX' . substr( $url, -4 ) . ' - Body: ' . print_r( $body, true ) );

		// Perform the POST.
		return wp_remote_post(
			$url,
			array(
				'body' => wp_json_encode( $body ),
			)
		);
	}


	/**
	 * Create a GUID on Client specific values
	 *
	 * @since 1.0.0
	 *
	 * @return string New Client ID.
	 */
	private function create_client_id() {

		// collect user specific data.
		if ( isset( $_COOKIE['_ga'] ) ) {

			$ga_cookie = explode( '.', sanitize_text_field( wp_unslash( $_COOKIE['_ga'] ) ) );
			if ( isset( $ga_cookie[2] ) ) {

				// check if uuid.
				if ( $this->check_uuid( $ga_cookie[2] ) ) {

					// uuid set in cookie.
					return $ga_cookie[2];
				} elseif ( isset( $ga_cookie[2] ) && isset( $ga_cookie[3] ) ) {

					// google default client id.
					return $ga_cookie[2] . '.' . $ga_cookie[3];
				}
			}
		}

		// nothing found - return random uuid client id.
		return GFFormsModel::get_uuid();
	}

	/**
	 * Get the session id.
	 *
	 * @since 2.3.0
	 * @since 2.4.0 added support for the GS2 cookie format.
	 *
	 * @param string $measurement_id The measurement id.
	 *
	 * @return string|null The session id.
	 */
	private function get_browser_session_id( $measurement_id ) {
		$session_id = null;

		// Cookie name example: '_ga_1YS1VWHG3V'.
		$cookie_name = '_ga_' . str_replace( 'G-', '', $measurement_id );

		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			$cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );

			// Check for the new GS2 cookie format.
			if ( str_contains( $cookie_value, '$' ) && preg_match( '/s(\d+)/', $cookie_value, $matches ) ) {
				$session_id = $matches[1];
			}

			// Fallback to the dot delimited GS1 format.
			if ( ! $session_id ) {
				$parts = explode( '.', $cookie_value );
				if ( isset( $parts[3] ) && is_numeric( $parts[3] ) ) {
					$session_id = $parts[3];
				}
			}
		}

		/**
		 * Filter the GA4 session ID used in Measurement Protocol requests.
		 *
		 * @param string|null $session_id     Parsed session ID or null if not found.
		 * @param string      $cookie_name    Name of the GA cookie used.
		 * @param string      $measurement_id GA4 Measurement ID.
		 */
		$session_id = apply_filters( 'gform_googleanalytics_mp_session_id', $session_id, $cookie_name, $measurement_id );

		return $session_id;
	}

	/**
	 * Check if is a valid uuid v4
	 *
	 * @since 1.0.0
	 *
	 * @param string $uuid The UUID to check.
	 *
	 * @return bool If the UUID is valid
	 */
	private function check_uuid( $uuid ) {
		return preg_match( '#^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$#i', $uuid );
	}
}
