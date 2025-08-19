<?php

namespace MapSVG;

/**
 * Logger class that sends the data from PHP to browser console.
 */
class Logger
{

	static $clockwork;
	static $logToFile;

	// Define message types as constants
	const ERROR = 'ERROR';
	const WARNING = 'WARNING';
	const INFO = 'INFO';
	const DEBUG = 'DEBUG';

	private static bool $isFinalized = false;

	private static function clockworkEnabled()
	{
		return self::$clockwork;
	}



	/**
	 * Initialize the Clockwork library
	 */
	public static function init($params)
	{

		$canLog = defined('MAPSVG_DEBUG') && MAPSVG_DEBUG;

		self::$clockwork = null;
		

		if ($params["logToFile"]) {
			self::$logToFile = true;
		}
	}

	/**
	 * Save the logged data for Clockwork. The data becomes accessible by an API URL
	 * /wp-json/mapsvg/v1/clockwork/
	 */
	public static function finish()
	{
		if (!self::$isFinalized) {
			
			self::$isFinalized = true;
		}
	}

	public static function sendHeaders()
	{
		error_log("headers sent");
		if (static::clockworkEnabled()) {
			error_log("headers sent");
			return self::$clockwork->sendHeaders();
		}
	}

	/**
	 * Return metadata for Clockwork
	 */
	public static function getMetaData($request)
	{
		
	}

	/**
	 * Add a log to Clockwork and/or file
	 */
	public static function error($data, $label = null)
	{
		$message = self::formatMessage(self::ERROR, $data);

		if (static::clockworkEnabled()) {
			clock($message);
		}
		if (static::$logToFile && WP_DEBUG) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log($message);
			// phpcs:enable
		}
	}

	/**
	 * Add an info log
	 */
	public static function info($data, $label = null)
	{
		$message = self::formatMessage(self::INFO, $data);

		if (static::clockworkEnabled()) {
			clock($message);
		}
		if (static::$logToFile && WP_DEBUG) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log($message);
			// phpcs:enable
		}
	}

	/**
	 * Add a database query, with timing - to Clockwork logs
	 */
	public static function addDatabaseQuery($query, $time)
	{
		
	}

	/**
	 * Format the log message with type
	 */
	private static function formatMessage($type, $data)
	{
		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$message = is_array($data) || is_object($data) ? print_r($data, true) : $data;
		// phpcs:enable
		return "[{$type}] {$message}";
	}
}
