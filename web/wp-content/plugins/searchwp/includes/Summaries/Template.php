<?php

namespace SearchWP\Summaries;

/**
 * Email Summaries template class.
 *
 * @since 4.3.16
 */
class Template {

	/**
	 * The title of the email.
	 *
	 * @since 4.3.16
	 *
	 * @var string
	 */
	protected static $title = 'SearchWP';

	/**
	 * Get the summary content.
	 *
	 * @since 4.3.16
	 *
	 * @param array $data Summary data.
	 *
	 * @return string
	 */
	public static function get_content( $data ) {

		ob_start();

		self::get_header();

		self::get_body( $data );

		self::get_footer();

		return ob_get_clean();
	}

	/**
	 * Get the summary header.
	 *
	 * @since 4.3.16
	 */
	private static function get_header() {

		$header_image = [
			'url_dark'  => SEARCHWP_PLUGIN_URL . 'assets/images/searchwp-logo-negative.png',
			'url_light' => SEARCHWP_PLUGIN_URL . 'assets/images/searchwp-logo.png',
		];

		$title = self::$title;

		require SEARCHWP_PLUGIN_DIR . '/includes/Summaries/Templates/summary-header.php';
	}

	/**
	 * Get the summary body.
	 *
	 * @since 4.3.16
	 *
	 * @param array $data Summary data.
	 */
	private static function get_body( $data ) {

		$defaults = [
			'total_users'          => '',
			'total_searches'       => '',
			'failed_searches'      => '',
			'total_results_viewed' => '',
			'clicks_per_search'    => '',
			'searches_per_user'    => '',
			'popular_searches'     => [],
			'engines'              => [],
			'icons'                => [],
			'notification'         => '',
		];

		$summary = wp_parse_args( $data, $defaults );

		require SEARCHWP_PLUGIN_DIR . '/includes/Summaries/Templates/summary-body.php';
	}

	/**
	 * Get the summary footer.
	 *
	 * @since 4.3.16
	 */
	private static function get_footer() {

		require SEARCHWP_PLUGIN_DIR . '/includes/Summaries/Templates/summary-footer.php';
	}
}
