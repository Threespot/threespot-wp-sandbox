<?php

namespace SearchWP_Metrics;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Utilities.
 *
 * @since 1.0.0
 */
class Utilities {

	/**
	 * The settings object.
	 *
	 * @since 1.0.0
	 *
	 * @var \SearchWP_Metrics\Settings
	 */
	private $settings;

	/**
	 * The after date.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $after = '30 days ago';

	/**
	 * The before date.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $before = 'now';

	/**
	 * The engines.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $engines = [ 'default' ];

	/**
	 * The query limit.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private $limit = 10;

	/**
	 * Allowed intervals for clearing metrics data.
	 *
	 * @since 1.4.5
	 *
	 * @var int[]
	 */
	public $allowed_clear_data_intervals = [ 0, 30, 90, 180, 365 ];

	/**
	 * Utilities constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->settings = new Settings();
	}

	/**
	 * Initializer.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {

		add_action( 'wp_ajax_searchwp_metrics', [ $this, 'get_metrics' ] );
		add_action( 'wp_ajax_searchwp_metrics_ignore_query', [ $this, 'add_ignored_query' ] );
		add_action( 'wp_ajax_searchwp_metrics_unignore_query', [ $this, 'remove_ignored_query' ] );
		add_action( 'wp_ajax_searchwp_metrics_search_queries', [ $this, 'find_search_queries' ] );
		add_action( 'wp_ajax_searchwp_metrics_popular_search_details', [ $this, 'get_popular_search_details' ] );
		add_action( 'wp_ajax_searchwp_metrics_clear_metrics_data', [ $this, 'clear_metrics_data' ] );
		add_action( 'wp_ajax_searchwp_metrics_clear_ignored_queries', [ $this, 'clear_ignored_queries' ] );
		add_action( 'wp_ajax_searchwp_metrics_update_logging_rules', [ $this, 'update_logging_rules' ] );
		add_action( 'wp_ajax_searchwp_metrics_update_settings', [ $this, 'update_settings' ] );
		add_action( 'wp_ajax_searchwp_metrics_set_clear_metrics_data_interval', [ $this, 'set_clear_metrics_data_interval' ] );
	}

	/**
	 * Callback for ajax endpoint to save general settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function update_settings() {

		check_ajax_referer( 'searchwp_metrics_ajax' );

		/**
		 * Filter the capability required to save settings.
		 *
		 * @since 1.0.0
		 *
		 * @param string $settings_cap The capability required to save settings.
		 */
		$settings_cap = apply_filters( 'searchwp_metrics_capability_settings', 'manage_options' );
		if ( ! current_user_can( $settings_cap ) ) {
			wp_send_json_error( __( 'Unable to save settings', 'searchwp-metrics' ) );
		}

		$clear_data_on_uninstall = isset( $_REQUEST['clear_data_on_uninstall'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['clear_data_on_uninstall'] ) ) : false;
		$click_tracking_buoy     = isset( $_REQUEST['click_tracking_buoy'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['click_tracking_buoy'] ) ) : false;

		$metrics = new \SearchWP_Metrics();
		$metrics->save_boolean_option( 'clear_data_on_uninstall', $clear_data_on_uninstall );
		$metrics->save_boolean_option( 'click_tracking_buoy', $click_tracking_buoy );

		wp_send_json_success();
	}

	/**
	 * Callback for ajax endpoint to save the logging rules (blocklists).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function update_logging_rules() {

		check_ajax_referer( 'searchwp_metrics_ajax' );

		$ips   = isset( $_REQUEST['ips'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['ips'] ) ) ) : '';
		$roles = isset( $_REQUEST['roles'] ) ? trim( sanitize_textarea_field( wp_unslash( $_REQUEST['roles'] ) ) ) : '';

		if ( ! empty( $ips ) ) {
			$ips = explode( "\n", $ips );

			$ips = array_filter(
				$ips,
				function ( $ip ) {
					return filter_var( $ip, FILTER_VALIDATE_IP );
				}
			);
		}

		if ( ! empty( $roles ) ) {
			$roles = explode( "\n", $roles );

			$roles = array_filter(
				$roles,
				function ( $role ) {
					return ( is_numeric( $role ) && false !== get_userdata( $role ) ) || ! is_null( get_role( strtolower( $role ) ) );
				}
			);
		}

		$metrics = new \SearchWP_Metrics();
		$metrics->save_option(
			'blocklists',
			[
				'ips'   => $ips,
				'roles' => $roles,
			]
		);

		wp_send_json_success();
	}

	/**
	 * Callback for ajax endpoint to remove all of the ignored queries.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear_ignored_queries() {

		check_ajax_referer( 'searchwp_metrics_ajax' );

		if ( ! defined( 'SEARCHWP_PREFIX' ) ) {
			wp_send_json_error();
		}

		if ( class_exists( '\SearchWP\Settings' ) ) {
			\SearchWP\Settings::delete( 'ignored_queries' );
			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Callback for ajax endpoint to clear all Metrics data (not ignored queries).
	 *
	 * @since 1.0.0
	 *
	 * @param bool $uninstalling Whether this is being called during uninstallation.
	 *
	 * @return void
	 */
	public function clear_metrics_data( $uninstalling = false ) {

		global $wpdb;

		$metrics = new \SearchWP_Metrics();

		if ( ! $uninstalling ) {
			check_ajax_referer( 'searchwp_metrics_ajax' );

			// Truncate all custom database tables. If uninstalling they're going to get DROPPED.
			foreach ( $metrics->get_db_tables() as $table ) {
				$table = $metrics->get_db_prefix() . $table;

				$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}

		$meta_key = str_replace( '_', '\_', $metrics->get_db_prefix() . 'click_buoy_' ) . '%';

		// Remove all click tracking metadata.
		$wpdb->query(
			"DELETE FROM $wpdb->postmeta
				WHERE meta_key LIKE '" . $meta_key . "'"
		);

		if ( ! $uninstalling ) {
			wp_send_json_success();
		}
	}

	/**
	 * Callback for ajax endpoint to retrieve details for Popular searches for a particular engine.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function get_popular_search_details() {

		check_ajax_referer( 'searchwp_metrics_ajax' );

		$after  = isset( $_REQUEST['after'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['after'] ) ) : '30 days ago';
		$before = isset( $_REQUEST['before'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['before'] ) ) : 'now';
		$engine = isset( $_REQUEST['engine'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['engine'] ) ) : '';
		$limit  = isset( $_REQUEST['limit'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['limit'] ) ) : $this->limit;

		if ( ! defined( 'SEARCHWP_VERSION' ) ) {
			wp_send_json_error(
				__( 'SearchWP must be activated', 'searchwp-metrics' )
			);
		}

		if ( ! \SearchWP\Settings::get_engine_settings( $engine ) ) {
			wp_send_json_error(
				__( 'An invalid engine was passed to get_popular_search_details()', 'searchwp-metrics' )
			);
		}

		$payload = [];

		$query = new QueryPopularQueriesOverTimeDetails(
			[
				'after'  => $after,
				'before' => $before,
				'engine' => $engine,
				'limit'  => absint( $limit ),
			]
		);

		$popular_queries = $query->get_results();

		foreach ( $popular_queries as $popular_query ) {
			$clicks_for_query = new QueryPopularClicksOverTime(
				[
					'after'               => $after,
					'before'              => $before,
					'engine'              => $engine,
					'limited_to_searches' => [ $popular_query->id ],
				]
			);

			$clicks_for_query_details = $clicks_for_query->get_results();
			$clicks                   = [];

			foreach ( $clicks_for_query_details as $clicks_for_query_detail ) {
				$clicks[] = [
					'post_id'    => absint( $clicks_for_query_detail->post_id ),
					'post_title' => $clicks_for_query_detail->post_title,
					'clicks'     => absint( $clicks_for_query_detail->clicks ),
					'permalink'  => get_permalink( $clicks_for_query_detail->post_id ),
				];
			}

			$payload[] = [
				'query'  => $popular_query,
				'clicks' => $clicks,
			];
		}

		wp_send_json_success( $payload );
	}

	/**
	 * Callback for query limiter multiselect that searches search queries for an exact match.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function find_search_queries() {

		global $wpdb;

		check_ajax_referer( 'searchwp_metrics_ajax' );

		$search_query = isset( $_REQUEST['searchquery'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['searchquery'] ) ) : '';
		$search_query = strtolower( stripslashes( $search_query ) );

		$search = new QuerySearchSearchQueries(
			[
				'query' => $search_query,
			]
		);

		$search->build_sql();
		$sql = $search->get_sql();

		$replacements = [ $search_query ];

		$ignored_queries = array_map( 'strtolower', \SearchWP\Settings::get( 'ignored_queries', 'array' ) );

		if ( ! empty( $ignored_queries ) ) {
			$replacements = array_merge( $replacements, $ignored_queries );
		}

		$sql = $wpdb->prepare(
			$sql,
			$replacements
		);

		$payload = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		wp_send_json_success( $payload );
	}

	/**
	 * Setter for after property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $after The after date.
	 */
	public function set_after( $after ) {

		$this->after = $after;
	}

	/**
	 * Setter for before property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $before The before date.
	 *
	 * @return void
	 */
	public function set_before( $before ) {

		$this->before = $before;
	}

	/**
	 * Setter for engine property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $engine The engine.
	 *
	 * @return void
	 */
	public function set_engine( $engine ) {

		$this->engine = $engine;
	}

	/**
	 * Adds a query to the local user metadata to ensure it's ignored in Metrics.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_ignored_query() {

		check_ajax_referer( 'searchwp_metrics_ajax' );

		if ( class_exists( '\\SearchWP\\Statistics' ) && method_exists( '\\SearchWP\\Statistics', 'ignore_query' ) ) {
			$query_to_ignore = isset( $_REQUEST['query'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['query'] ) ) : '';

			if ( empty( $query_to_ignore ) ) {
				wp_send_json_error();
			}

			\SearchWP\Statistics::ignore_query( $query_to_ignore );

			wp_send_json_success( [ 'hash' => md5( $query_to_ignore ) ] );
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Removes a query to the local user metadata to ensure it's ignored in Metrics.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function remove_ignored_query() {

		global $wpdb;

		check_ajax_referer( 'searchwp_metrics_ajax' );

		if ( ! class_exists( '\\SearchWP\\Statistics' ) || ! method_exists( '\\SearchWP\\Statistics', 'unignore_query' ) ) {
			wp_send_json_error( 'Unignore method not available!' );
		}

		$query_to_unignore = isset( $_REQUEST['hash'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['hash'] ) ) : '';
		$hash              = $query_to_unignore;

		// Technical debt: the md5 hash is sent here, so we need to reverse lookup. Sorry.
		$metrics = new \SearchWP_Metrics();
		$table   = $metrics->get_db_prefix() . 'queries';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query_to_unignore = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT query
				FROM {$table}
				WHERE md5(query) = %s
				LIMIT 1
			",
				$query_to_unignore
			)
		);
		// phpcs:enable

		if ( empty( $query_to_unignore ) ) {

			$query_to_unignore = $this->find_ignored_query_by_hash( $hash );

			if ( empty( $query_to_unignore ) ) {
				wp_send_json_error();
			}
		}

		\SearchWP\Statistics::unignore_query( $query_to_unignore );

		wp_send_json_success();
	}

	/**
	 * Retrieves the ignored search queries by hash.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash The hash to search for.
	 *
	 * @return array
	 */
	private function find_ignored_query_by_hash( $hash ) {

		// This might be a partial match, so look it up from the stored ignored queries.
		// TODO: This needs a refactor. Do we need to query the table in the first place?
		$ignored_queries = \SearchWP\Settings::get( 'ignored_queries', 'array' );

		if ( ! empty( $ignored_queries ) ) {
			foreach ( $ignored_queries as $ignored_query ) {
				if ( md5( $ignored_query ) === $hash ) {
					return $ignored_query;
				}
			}
		}

		return null;
	}

	/**
	 * Callback to retrieve metrics data.
	 *
	 * @since 1.3
	 *
	 * @return void
	 */
	public function get_metrics() {

		check_ajax_referer( 'searchwp_metrics_ajax' );

		$this->after   = isset( $_REQUEST['after'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['after'] ) ) : '30 days ago';
		$this->before  = isset( $_REQUEST['before'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['before'] ) ) : 'now';
		$this->limit   = isset( $_REQUEST['limit'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['limit'] ) ) : 10;
		$this->engines = isset( $_REQUEST['engines'] ) && is_array( $_REQUEST['engines'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_REQUEST['engines'] ) )
			: [ 'default' ];

		// Persist the chosen engines as a setting.
		$this->settings->set_option( 'last_engines', $this->engines );

		$data = [];

		/**
		 * The max allowed query execution time for this request in seconds.
		 *
		 * @since 1.4.5
		 *
		 * @param int $allowed_execution_time The max allowed query execution time for this request in seconds.
		 */
		$allowed_execution_time = apply_filters( 'searchwp_metrics_requests_max_time', 2 );

		// Sanitize the allowed execution time.
		$allowed_execution_time = is_numeric( $allowed_execution_time ) ? absint( $allowed_execution_time ) : 0;

		// Sanitize the metrics requested array.
		$requested_metrics = isset( $_REQUEST['metrics'] )
			? array_map(
				function ( $metric ) {
					// Sanitize the metric name.
					return sanitize_text_field( wp_unslash( $metric ) );
				},
				(array) $_REQUEST['metrics'] // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			)
			: [];

		// Time how long it takes to get the data for this request.
		$start = microtime( true );

		// Get the data for each single Metric requested until there is no more time.
		foreach ( $requested_metrics as $requested_metric ) {

			$metric_data = $this->get_single_metric( $requested_metric );

			$data[ $requested_metric ] = $metric_data;

			if ( $requested_metric === 'searches_over_time' && $metric_data['searches_count'] === 0 ) {
				$data = $this->get_empty_data( $data );
				break;
			}

			$processed_time = microtime( true ) - $start;

			if ( $processed_time > $allowed_execution_time ) {
				break;
			}
		}

		// Return the data.
		wp_send_json_success( $data );
	}

	/**
	 * Retrieves the data for a single metric.
	 *
	 * @since 1.4.5
	 *
	 * @param string $metric The metric to retrieve.
	 *
	 * @return array
	 */
	private function get_single_metric( $metric ) {

		$data = [];

		switch ( $metric ) {
			case 'searches_over_time':
				$data = $this->get_searches_over_time();
				break;

			case 'popular_queries_over_time':
				$data = $this->get_popular_queries_over_time();
				break;

			case 'popular_clicks_over_time':
				$data = $this->get_popular_clicks_over_time();
				break;

			case 'failed_searches_over_time':
				$data = $this->get_failed_searches_over_time();
				break;

			case 'ignored_queries':
				$data = $this->get_ignored_queries();
				break;

			case 'average_searches_per_user':
				$data = $this->get_average_searches_per_user();
				break;

			case 'average_clicks_per_search':
				$data = $this->get_average_clicks_per_search();
				break;

			case 'average_click_rank':
				$data = $this->get_average_click_rank();
				break;

			case 'total_clicks':
				$data = $this->get_total_clicks();
				break;
		}

		return $data;
	}

	/**
	 * Retrieves the total clicks for an engine during a time frame.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_total_clicks() {

		global $wpdb;

		$payload = [];

		foreach ( $this->engines as $engine ) {
			$query = new QueryTotalClicks(
				[
					'after'  => $this->after,
					'before' => $this->before,
					'engine' => $engine,
				]
			);

			$dataset      = $query->get_results();
			$total_clicks = $wpdb->num_rows;

			$payload[ $engine ] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'statistic'   => $total_clicks,
			];
		}

		return $payload;
	}

	/**
	 * Retrieves the average click rank for an engine during a time frame.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_average_click_rank() {

		$payload = [];

		foreach ( $this->engines as $engine ) {
			$query = new QueryAverageClickRank(
				[
					'after'  => $this->after,
					'before' => $this->before,
					'engine' => $engine,
				]
			);

			$dataset = $query->get_results();

			if ( ! empty( $dataset ) ) {
				$average_click_rank = wp_list_pluck( $dataset, 'average' );
				$formatted_stat     = number_format_i18n( (float) $average_click_rank[0], 2 );
			} else {
				$formatted_stat = 0.00;
			}

			$payload[ $engine ] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'statistic'   => $formatted_stat,
			];
		}

		return $payload;
	}

	/**
	 * Retrieves the average clicks per search (from users that have searched) for an engine during a time frame.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_average_clicks_per_search() {

		$payload = [];

		foreach ( $this->engines as $engine ) {
			$query = new QueryAverageClicksPerSearch(
				[
					'after'  => $this->after,
					'before' => $this->before,
					'engine' => $engine,
				]
			);

			$dataset = $query->get_results();

			if ( ! empty( $dataset ) ) {
				$clicks_per_search = wp_list_pluck( $dataset, 'clicks' );
				$total_clicks      = array_sum( $clicks_per_search );

				if ( empty( $clicks_per_search ) ) {
					$average_clicks_per_search = 0;
				} else {
					$average_clicks_per_search = $total_clicks / count( $clicks_per_search );
				}

				$formatted_stat = number_format_i18n( (float) $average_clicks_per_search, 2 );
			} else {
				$formatted_stat = 0.00;
			}

			$payload[ $engine ] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'statistic'   => $formatted_stat,
			];
		}

		return $payload;
	}

	/**
	 * Retrieves the average searches per user for an engine during a time frame.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_average_searches_per_user() {

		$payload = [];

		foreach ( $this->engines as $engine ) {
			$query = new QueryAverageSearchesPerUser(
				[
					'after'  => $this->after,
					'before' => $this->before,
					'engine' => $engine,
				]
			);

			$dataset = $query->get_results();

			if ( ! empty( $dataset ) ) {
				$uids           = wp_list_pluck( $dataset, 'uid' );
				$uid_counts     = array_count_values( $uids );
				$total_searches = array_sum( $uid_counts );

				if ( empty( $uid_counts ) ) {
					$average_searches_per_user = 0;
				} else {
					$average_searches_per_user = $total_searches / count( $uid_counts );
				}

				$formatted_stat = number_format_i18n( (float) $average_searches_per_user, 2 );
			} else {
				$formatted_stat = 0.00;
			}

			$payload[ $engine ] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'statistic'   => $formatted_stat,
			];
		}

		return $payload;
	}

	/**
	 * Getter for ignored queries.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_ignored_queries() {

		check_ajax_referer( 'searchwp_metrics_ajax' );

		$ignored_queries = \SearchWP\Settings::get( 'ignored_queries', 'array' );

		$payload = [];
		if ( ! empty( $ignored_queries ) ) {
			foreach ( $ignored_queries as $ignored_query_string ) {
				$payload[] = [
					'hash'  => md5( $ignored_query_string ),
					'query' => $ignored_query_string,
				];
			}
		}

		return $payload;
	}

	/**
	 * Retrieves the engine label from the engine name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The engine name.
	 *
	 * @return string
	 */
	public function get_engine_label_from_name( $name = 'default' ) {

		$engine_settings = \SearchWP\Settings::get_engine_settings( $name );

		return isset( $engine_settings['label'] ) ? $engine_settings['label'] : $name;
	}

	/**
	 * AJAX callback that retrieves the number of searches for each day within a date range.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_searches_over_time() {

		// This data will be prepped to be used directly by the charting library.
		$chart_labels   = [];
		$datasets       = [];
		$searches_count = [];

		// We're always working with an array of engines.
		foreach ( $this->engines as $engine ) {
			$query = new QuerySearchesOverTime(
				[
					'after'  => $this->after,
					'before' => $this->before,
					'engine' => $engine,
				]
			);

			$dataset = $query->get_results();

			$searches_count[] = array_sum( wp_list_pluck( $dataset, 'searches' ) );

			$datasets[] = [
				'engine'  => $this->get_engine_label_from_name( $engine ),
				'dataset' => array_map( 'absint', array_values( wp_list_pluck( $dataset, 'searches' ) ) ),
			];

			// Labels need be defined only once.
			$chart_labels = empty( $chart_labels ) ? $this->get_chart_labels_from_results( $dataset ) : $chart_labels;
		}

		$payload = [
			'labels'         => $chart_labels,
			'datasets'       => $datasets,
			'searches_count' => array_sum( $searches_count ),
		];

		return $payload;
	}

	/**
	 * AJAX callback that retrieves common queries over time.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_popular_queries_over_time() {

		// The payload is going to be broken out per engine, each with a unique set of labels.
		$payload = [];

		foreach ( $this->engines as $engine ) {
			$query = new QueryPopularQueriesOverTime(
				[
					'after'  => $this->after,
					'before' => $this->before,
					'engine' => $engine,
					'limit'  => $this->limit,
				]
			);

			$dataset = $query->get_results();

			$payload[] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'labels'      => wp_list_pluck( $dataset, 'query' ),
				'dataset'     => array_map( 'absint', wp_list_pluck( $dataset, 'searchcount' ) ),
			];
		}

		return $payload;
	}

	/**
	 * Retrieves the popdlar clicks for an engine during a time frame.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_popular_clicks_over_time() {

		// This data will be displayed on a Radar chart so we need to find a common set of labels for each dataset.
		$chart_labels = [];
		$datasets     = [];
		$payload      = [];

		foreach ( $this->engines as $engine ) {

			/**
			 * The maximum number of popular clicks to retrieve.
			 *
			 * @since 1.0.0
			 *
			 * @param int $limit The maximum number of popular clicks to retrieve.
			 */
			$limit = apply_filters( 'searchwp_metrics_popular_clicks_limit', 1500 );

			$query = new QueryPopularClicksOverTime(
				[
					'after'  => $this->after,
					'before' => $this->before,
					'engine' => $engine,
					'limit'  => $limit,
				]
			);

			$dataset = $query->get_results();

			if ( empty( $dataset ) ) {
				$payload[] = [];
				continue;
			}

			// To remain somewhat performant, we're going to determine the average number of clicks and use that as the minimum.
			$click_records = wp_list_pluck( $dataset, 'clicks' );
			$avg_clicks    = ceil( array_sum( $click_records ) / count( $click_records ) );

			foreach ( $dataset as $post ) {

				if ( absint( $post->clicks ) < $avg_clicks ) {
					continue;
				}

				$post_id = $post->post_id;

				$searches_for_post_id = $this->get_queries_for_post_ids( $post_id, $engine );
				// $searches_for_post_id is an array of objects with the following keys:
				// - query (the search query used to retrieve that post)
				// - count (the number of searches of that query)

				// We need to track all the search queries used to find all posts
				// for this engine, for use as chart labels.
				$chart_labels = array_merge( $chart_labels, wp_list_pluck( $searches_for_post_id, 'query' ) );

				$datasets[] = [
					'label'     => get_the_title( $post_id ),
					'post_id'   => absint( $post_id ),
					'post_type' => get_post_type( $post_id ),
					'permalink' => get_permalink( $post_id ),
					'raw_data'  => $searches_for_post_id,
				];
			}

			// We need to determine how many posts were found per search query.
			// $chart_labels_counts is an array with keys of search queries and values of the number of times that search query was searched.
			$chart_labels_counts = array_count_values( $chart_labels );

			// Lastly we're going to make a unique list of labels for display in the chart.
			$chart_labels = array_values( array_unique( $chart_labels ) );

			// Now that we've looped through all the queries that resulted in these clicks
			// we need to reexamine the data for each dataset to ensure that counts are correct
			// because new labels have likely been added, so we need to fill those gaps.
			$datasets = $this->process_datasets( $datasets, $chart_labels );

			$payload[] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'labels'      => $chart_labels,
				'counts'      => $chart_labels_counts,
				'insights'    => [
					'analysis'  => $this->get_click_count_analysis( $chart_labels_counts, $datasets ),
					'popular'   => $this->get_click_count_popular_content( $dataset ),
					'underdogs' => $this->get_click_count_underdogs( $engine ),
				],
				'dataset'     => $datasets,
			];
		}

		return $payload;
	}

	/**
	 * Analyzes a dataset from popular_clicks_over_time to determine what content is getting many
	 * clicks despite a low click position, indicating content needs to be on-site-SEO'd.
	 *
	 * @since 1.0.0
	 *
	 * @param string $engine The engine to analyze.
	 *
	 * @return array
	 */
	public function get_click_count_underdogs( $engine ) {

		if ( ! defined( 'SEARCHWP_VERSION' ) ) {
			wp_send_json_error(
				__( 'SearchWP must be activated', 'searchwp-metrics' )
			);
		}

		if ( ! \SearchWP\Settings::get_engine_settings( $engine ) ) {
			wp_send_json_error(
				__( 'An invalid engine was passed to get_queries_for_post_id()', 'searchwp-metrics' )
			);
		}

		$payload = [];

		$query = new QueryUnderdogs(
			[
				'after'        => $this->after,
				'before'       => $this->before,
				'engine'       => $engine,
				'min_avg_rank' => 8,
			]
		);

		$dataset = $query->get_results();

		if ( empty( $dataset ) ) {
			$average_click_count = 0;
		} else {
			$average_click_count = array_sum( wp_list_pluck( $dataset, 'click_count' ) ) / count( $dataset );
		}

		/**
		 * The threshold for determining what is an underdog.
		 *
		 * @since 1.0.0
		 *
		 * @param float $click_count_threshold The threshold for determining what is an underdog.
		 */
		$click_count_threshold = $average_click_count * floatval( apply_filters( 'searchwp_metrics_underdog_click_threshold', 1 ) );

		foreach ( $dataset as $underdog ) {
			$click_count = absint( $underdog->click_count );

			if ( $click_count < $click_count_threshold ) {
				continue;
			}

			$search_queries = new QueryQueriesForPostIds(
				[
					'after'    => $this->after,
					'before'   => $this->before,
					'engine'   => $engine,
					'limit'    => $this->limit,
					'post_ids' => [ $underdog->post_id ],
				]
			);

			$payload[] = [
				'post_id'     => $underdog->post_id,
				'post_title'  => $underdog->post_title,
				'click_count' => $click_count,
				'avg_rank'    => absint( $underdog->avg_rank ),
				'permalink'   => get_permalink( $underdog->post_id ),
				'queries'     => $search_queries->get_results(),
			];
		}

		return $payload;
	}

	/**
	 * Analyzes a dataset from popular_clicks_over_time to determine what content is most popular
	 * by comparing click-through rates to the average click through rate of the overall set.
	 *
	 * @since 1.0.0
	 *
	 * @param array $dataset The dataset to analyze.
	 *
	 * @return array
	 */
	public function get_click_count_popular_content( $dataset ) {

		$clicks = wp_list_pluck( $dataset, 'clicks' );
		if ( empty( $clicks ) ) {
			$average_clicks_per_post = 0;
		} else {
			$average_clicks_per_post = array_sum( $clicks ) / count( $clicks );
		}

		/**
		 * The threshold for determining what is popular content.
		 * Posts with a click rate greater than this threshold over the average click rate will be considered popular.
		 * Default is 4x the average clicks indicates something is popular.
		 *
		 * @since 1.0.0
		 *
		 * @param float $threshold The threshold for determining what is popular content.
		 */
		$threshold = floatval( apply_filters( 'searchwp_metrics_popular_content_threshold', 4 ) );

		$popular_content = [];

		foreach ( $dataset as $result ) {
			if ( absint( $result->clicks ) >= ( $threshold * $average_clicks_per_post ) ) {
				$popular_content[] = [
					'post_id'    => $result->post_id,
					'post_title' => $result->post_title,
					'permalink'  => get_permalink( $result->post_id ),
					'clicks'     => $result->clicks,
				];
			}
		}

		return $popular_content;
	}

	/**
	 * Processes a raw dataset to fill in gaps in the data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $datasets     The dataset to process.
	 * @param array $chart_labels The chart labels to use.
	 *
	 * @return array
	 */
	private function process_datasets( $datasets, $chart_labels ) {

		foreach ( $datasets as $key => $dataset ) {
			$data = $this->process_single_dataset( $dataset, $chart_labels );
			unset( $datasets[ $key ]['raw_data'] );
			$datasets[ $key ]['data'] = $data;
		}

		return $datasets;
	}

	/**
	 * Processes a single dataset to fill in gaps in the data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $dataset      The dataset to process.
	 * @param array $chart_labels The chart labels to use.
	 *
	 * @return array
	 */
	private function process_single_dataset( $dataset, $chart_labels ) {

		$data = [];

		if ( empty( $dataset['raw_data'] ) ) {
			return $data;
		}

		foreach ( $chart_labels as $chart_label ) {
			$found_match = false;

			foreach ( $dataset['raw_data'] as $data_point ) {

				if ( $data_point->query === $chart_label ) {
					$data[]      = absint( $data_point->count );
					$found_match = true;
					break;
				}
			}
			if ( ! $found_match ) {
				$data[] = 0;
			}
		}

		return $data;
	}

	/**
	 * Using a submitted dataset, determines which search phrases are generating too
	 * many clicks to too many results, indicating that content can be improved upon.
	 *
	 * @since 1.0.0
	 *
	 * @param array $counts  The counts of search queries.
	 * @param array $dataset The dataset to analyze.
	 */
	public function get_click_count_analysis( $counts, $dataset ) {

		$notes = [];

		/**
		 * This threshold defines the minimum number of separate posts that were clicked to indicate that
		 * content can be improved upon e.g. there are too many potential search results for the search term.
		 *
		 * @since 1.0.0
		 *
		 * @param int $minimum_click_threshold The minimum number of separate posts that were clicked to indicate that content can be improved upon.
		 */
		$minimum_click_threshold = apply_filters( 'searchwp_metrics_minimum_click_warning_threshold', 4 );

		$i = -1;
		foreach ( $counts as $query => $click_count ) {

			++$i;

			// If this search query did not generate enough separate posts clicks, there's nothing else to do.
			if ( $click_count < absint( $minimum_click_threshold ) ) {
				continue;
			}

			// We have a search query that's generating too many clicks (e.g. visitor not finding what they're looking for).
			$clicks                  = 0;
			$posts_that_were_clicked = [];
			foreach ( $dataset as $search_result ) {
				$these_clicks = isset( $search_result['data'][ $i ] ) ? $search_result['data'][ $i ] : null;

				// This search result doesn't apply.
				if ( empty( $these_clicks ) ) {
					continue;
				}

				$clicks += $these_clicks;

				$posts_that_were_clicked[] = [
					'clicks'     => $these_clicks,
					'post_id'    => $search_result['post_id'],
					'post_type'  => get_post_type( $search_result['post_id'] ),
					'permalink'  => get_permalink( $search_result['post_id'] ),
					'post_title' => $search_result['label'],
				];
			}

			$notes[ $query ] = [
				'query'  => $query,
				'posts'  => $posts_that_were_clicked,
				'clicks' => $clicks,
			];
		}

		return $notes;
	}

	/**
	 * Retrieves all the search queries submitted that resulted in a click
	 * to any number of post IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $post_ids The post IDs to retrieve search queries for.
	 * @param string $engine   The engine to retrieve search queries for.
	 *
	 * @return array
	 */
	public function get_queries_for_post_ids( $post_ids, $engine = 'default' ) {

		if ( ! defined( 'SEARCHWP_VERSION' ) ) {
			wp_send_json_error(
				__( 'SearchWP must be activated', 'searchwp-metrics' )
			);
		}

		if ( ! \SearchWP\Settings::get_engine_settings( $engine ) ) {
			wp_send_json_error(
				__( 'An invalid engine was passed to get_queries_for_post_id()', 'searchwp-metrics' )
			);
		}

		if ( ! is_array( $post_ids ) ) {
			$post_ids = explode( ',', $post_ids );
		}

		$post_ids = array_map( 'absint', $post_ids );
		$post_ids = array_unique( $post_ids );

		$query = new QueryQueriesForPostIds(
			[
				'after'    => $this->after,
				'before'   => $this->before,
				'engine'   => $engine,
				'limit'    => $this->limit,
				'post_ids' => $post_ids,
			]
		);

		return $query->get_results();
	}

	/**
	 * Formats chart labels into the date format we want.
	 *
	 * @since 1.0.0
	 *
	 * @param array $results The results to format.
	 *
	 * @return array
	 */
	public function get_chart_labels_from_results( $results ) {

		return array_map(
			function ( $date ) {
				return date_i18n( 'M j', strtotime( $date ) );
			},
			array_keys( $results )
		);
	}

	/**
	 * AJAX callback that retrieves failed searches over time.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_failed_searches_over_time() {

		// The payload is going to be broken out per engine, each with a unique set of labels.
		$payload = [];

		foreach ( $this->engines as $engine ) {
			$query = new QueryFailedSearchesOverTime(
				[
					'after'  => $this->after,
					'before' => $this->before,
					'engine' => $engine,
					'limit'  => $this->limit * 100,
				]
			);

			$dataset = $query->get_results();

			$payload[] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'labels'      => wp_list_pluck( $dataset, 'query' ),
				'dataset'     => array_map( 'absint', wp_list_pluck( $dataset, 'failcount' ) ),
			];
		}

		return $payload;
	}

	/**
	 * Retrieves the empty data for the metrics.
	 *
	 * @since 1.4.5
	 *
	 * @param array $data The data to merge with the empty data.
	 *
	 * @return array
	 */
	private function get_empty_data( $data ) {

		$metrics_data = [
			'failed_searches_over_time' => [],
			'total_clicks'              => [],
			'average_searches_per_user' => [],
			'average_clicks_per_search' => [],
			'average_click_rank'        => [],
			'popular_queries_over_time' => [],
			'popular_clicks_over_time'  => [],
			'ignored_queries'           => $this->get_ignored_queries(),
		];

		foreach ( $this->engines as $engine ) {
			$metrics_data['failed_searches_over_time'][] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'labels'      => [],
				'dataset'     => [],
			];

			$metrics_data['total_clicks'][ $engine ] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'statistic'   => 0,
			];

			$metrics_data['average_searches_per_user'][ $engine ] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'statistic'   => '0.00',
			];

			$metrics_data['average_clicks_per_search'][ $engine ] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'statistic'   => 0.0,
			];

			$metrics_data['average_click_rank'][ $engine ] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'statistic'   => '0.00',
			];

			$metrics_data['popular_queries_over_time'][] = [
				'engine'      => $engine,
				'engineLabel' => $this->get_engine_label_from_name( $engine ),
				'labels'      => [],
				'dataset'     => [],
			];
		}

		return array_merge( $data, $metrics_data );
	}

	/**
	 * AJAX callback to save the clear metrics data interval setting.
	 *
	 * @since 1.4.5
	 *
	 * @return void
	 */
	public function set_clear_metrics_data_interval() {

		check_ajax_referer( 'searchwp_metrics_ajax' );

		$is_custom_interval = isset( $_REQUEST['interval'] ) && $_REQUEST['interval'] === 'custom';

		$interval_key = $is_custom_interval ? 'custom_interval' : 'interval';

		$interval_value = isset( $_REQUEST[ $interval_key ] ) ? absint( $_REQUEST[ $interval_key ] ) : 0;

		$interval = $interval_value > 0 && ( $is_custom_interval || in_array( $interval_value, $this->allowed_clear_data_intervals, true ) )
			? $interval_value
			: 0;

		$this->settings->set_option( 'clear_data_interval', $interval );

		wp_send_json_success();
	}
}
