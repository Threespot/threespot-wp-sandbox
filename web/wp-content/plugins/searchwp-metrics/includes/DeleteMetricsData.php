<?php

namespace SearchWP_Metrics;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DeleteMetricsData is responsible for deleting old Metrics data.
 *
 * @since 1.4.2
 */
class DeleteMetricsData {

	/**
	 * Stores the Metrics database prefix.
	 *
	 * @since 1.4.2
	 *
	 * @var string
	 */
	private $db_prefix;

	/**
	 * DeleteMetricsData constructor.
	 *
	 * @since 1.4.2
	 *
	 * @param string $db_prefix WPDB + Metrics DB prefix.
	 */
	public function __construct( $db_prefix ) {

		$this->db_prefix = $db_prefix;
	}

	/**
	 * Init.
	 *
	 * @since 1.4.2
	 */
	public function init() {

		add_action( 'wp_ajax_searchwp_metrics_clear_metrics_data_before', [ $this, 'clear_metrics_data_before' ] );

		// Schedule automatic clearing of data at set intervals.
		if ( ! wp_next_scheduled( SEARCHWP_METRICS_PREFIX . 'maintenance' ) ) {
			wp_schedule_event( time(), 'daily', SEARCHWP_METRICS_PREFIX . 'maintenance' );
		}

		add_action( SEARCHWP_METRICS_PREFIX . 'maintenance', [ $this, 'maintenance' ] );
	}

	/**
	 * Callback for ajax endpoint to clear Metrics data before a specified date.
	 *
	 * @since 1.4.2
	 */
	public function clear_metrics_data_before() {

		check_ajax_referer( 'searchwp_metrics_ajax' );

		// TODO: Create a proper error handling.

		$settings_cap = apply_filters( 'searchwp_metrics_capability_settings', 'manage_options' );
		if ( ! current_user_can( $settings_cap ) ) {
			wp_send_json_success();
		}

		if ( ! isset( $_POST['date'] ) ) {
			wp_send_json_success();
		}

		$date = sanitize_text_field( wp_unslash( $_POST['date'] ) );

		$this->delete_metrics_data( $date );

		wp_send_json_success();
	}

	/**
	 * Maintenance routine to clear old data automatically.
	 *
	 * @since 1.4.5
	 */
	public function maintenance() {

		$clear_metrics_interval = get_option( SEARCHWP_METRICS_PREFIX . 'clear_data_interval' );

		if ( empty( $clear_metrics_interval ) || ! is_numeric( $clear_metrics_interval ) ) {
			return;
		}

		$clear_metrics_interval = absint( $clear_metrics_interval );

		// Get the date to delete data before.
		$date = gmdate( 'Y-m-d', strtotime( '-' . $clear_metrics_interval . ' days' ) );

		$this->delete_metrics_data( $date );
	}

	/**
	 * Delete entries from all metrics tables.
	 *
	 * @since 1.4.2
	 *
	 * @param string $date Delete data before this date.
	 */
	public function delete_metrics_data( $date ) {

		$timestamp = strtotime( $date );

		if ( $timestamp === false ) {
			return;
		}

		$date = gmdate( 'Y-m-d H:i:s', $timestamp );

		$this->delete_metrics_clicks( $date );
		$this->delete_metrics_click_buoy( $date );
		$this->delete_metrics_queries( $date );
		$this->delete_metrics_ids( $date );
		$this->delete_metrics_searches( $date );
	}

	/**
	 * Delete entries from the swpext_metrics_clicks table.
	 *
	 * @since 1.4.2
	 *
	 * @param string $date Delete data before this date.
	 */
	private function delete_metrics_clicks( $date ) {

		global $wpdb;

		$sql = "
			DELETE
			FROM {$this->db_prefix}clicks
			WHERE tstamp < '%s';
		";

		$wpdb->query( $wpdb->prepare( $sql, $date ) );
	}

	/**
	 * Delete click_buoy entries from the postmeta table.
	 *
	 * @since 1.4.2
	 *
	 * @param string $date Delete data before this date.
	 */
	private function delete_metrics_click_buoy( $date ) {

		global $wpdb;

		$sql = "
			DELETE FROM {$wpdb->postmeta}
			WHERE meta_key IN
				(SELECT CONCAT('{$this->db_prefix}click_buoy_', MD5(query))
				FROM {$this->db_prefix}queries
				WHERE id NOT IN
				    (SELECT e.id
				    FROM {$this->db_prefix}queries AS e
				        LEFT JOIN {$this->db_prefix}searches AS s ON e.id = s.query
				    WHERE s.tstamp >= '%s'));
		";

		$wpdb->query( $wpdb->prepare( $sql, $date ) );
	}

	/**
	 * Delete entries from the swpext_metrics_queries table.
	 *
	 * @since 1.4.2
	 *
	 * @param string $date Delete data before this date.
	 */
	private function delete_metrics_queries( $date ) {

		global $wpdb;

		$sql = "
			DELETE FROM {$this->db_prefix}queries
			WHERE id NOT IN
			    (SELECT e.id
			    FROM (SELECT * FROM {$this->db_prefix}queries) AS e
			        LEFT JOIN {$this->db_prefix}searches AS s ON e.id = s.query
			    WHERE s.tstamp >= '%s');
		";

		$wpdb->query( $wpdb->prepare( $sql, $date ) );
	}

	/**
	 * Delete entries from the swpext_metrics_ids table.
	 *
	 * @since 1.4.2
	 *
	 * @param string $date Delete data before this date.
	 */
	private function delete_metrics_ids( $date ) {

		$this->delete_metrics_hash_ids( $date );
		$this->delete_metrics_uid_ids( $date );
	}

	/**
	 * Delete hash entries from the swpext_metrics_ids table.
	 *
	 * @since 1.4.2
	 *
	 * @param string $date Delete data before this date.
	 */
	private function delete_metrics_hash_ids( $date ) {

		global $wpdb;

		$sql = "
			DELETE e
			FROM {$this->db_prefix}ids AS e
			LEFT JOIN {$this->db_prefix}searches AS s
			ON e.id = s.hash
			WHERE s.tstamp < '%s';
		";

		$wpdb->query( $wpdb->prepare( $sql, $date ) );
	}

	/**
	 * Delete uid entries from the swpext_metrics_ids table.
	 *
	 * @since 1.4.2
	 *
	 * @param string $date Delete data before this date.
	 */
	private function delete_metrics_uid_ids( $date ) {

		global $wpdb;

		$sql = "
			DELETE FROM {$this->db_prefix}ids
		    WHERE type = 'uid'
			AND id NOT IN
			    (SELECT e.id
			    FROM (SELECT * FROM {$this->db_prefix}ids) AS e
			        LEFT JOIN {$this->db_prefix}searches AS s ON e.id = s.uid
			    WHERE s.tstamp >= '%s');
		";

		$wpdb->query( $wpdb->prepare( $sql, $date ) );
	}

	/**
	 * Delete entries from the swpext_metrics_searches table.
	 *
	 * @since 1.4.2
	 *
	 * @param string $date Delete data before this date.
	 */
	private function delete_metrics_searches( $date ) {

		global $wpdb;

		$sql = "
			DELETE
			FROM {$this->db_prefix}searches
			WHERE tstamp < '%s';
		";

		$wpdb->query( $wpdb->prepare( $sql, $date ) );
	}
}
