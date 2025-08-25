<?php

namespace SearchWP_Metrics;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Upgrade
 *
 * @package SearchWP_Metrics
 */
class Upgrade {
	/**
	 * @var string Active plugin version
	 *
	 * @since 1.0.0
	 */
	public $version;

	/**
	 * @var mixed|void The last version that was active
	 *
	 * @since 1.0.0
	 */
	public $last_version;

	/**
	 * @var string Charset for the database
	 *
	 * @since 1.0.0
	 */
	private $charset = 'utf8';

	/**
	 * @var string COLLATE SQL (when utf8mb4)
	 *
	 * @since 1.0.0
	 */
	private $collate_sql = '';

	/**
	 * Constructor.
	 *
	 * @param bool|string $version Plugin version being activated.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $version = false ) {

		global $wpdb;

		$this->setup_charset( $wpdb );

		if ( empty( $version ) ) {
			return;
		}

		$this->version      = $version;
		$this->last_version = $this->get_last_version();

		if ( ! $this->needs_upgrade() ) {
			return;
		}

		$this->handle_upgrade();
	}

	/**
	 * Setup charset and collation based on WordPress capabilities.
	 *
	 * @since 1.0.0
	 *
	 * @param \wpdb $wpdb WordPress database instance.
	 *
	 * @return void
	 */
	private function setup_charset( $wpdb ) {

		if ( $wpdb->has_cap( 'utf8mb4' ) ) {
			$this->charset     = 'utf8mb4';
			$this->collate_sql = ' COLLATE utf8mb4_unicode_ci ';
		}
	}

	/**
	 * Get the last active version of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string|int The last version or 0 if not found
	 */
	private function get_last_version() {

		$last_version = get_option( SEARCHWP_METRICS_PREFIX . 'version' );

		return $last_version === false ? 0 : $last_version;
	}

	/**
	 * Check if an upgrade is needed.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean Whether an upgrade is needed
	 */
	private function needs_upgrade() {

		return version_compare( $this->last_version, $this->version, '<' );
	}

	/**
	 * Handle the upgrade process.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function handle_upgrade() {

		if ( version_compare( $this->last_version, '0.1.0', '<' ) ) {
			$this->handle_fresh_install();
		} else {
			$this->upgrade();
			update_option( SEARCHWP_METRICS_PREFIX . 'version', $this->version, 'no' );
		}
	}

	/**
	 * Handle fresh installation of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function handle_fresh_install() {

		add_option( SEARCHWP_METRICS_PREFIX . 'version', $this->version, '', 'no' );

		if ( $this->charset === 'utf8mb4' ) {
			add_option( SEARCHWP_METRICS_PREFIX . 'utf8mb4', true, '', 'no' );
		}
	}

	/**
	 * Check and install tables if needed during plugin activation.
	 *
	 * @since 1.0.0
	 */
	public function maybe_install_tables() {

		if ( ! $this->tables_exist() ) {
			$this->install();
		}
	}

	/**
	 * Determines whether database tables exist.
	 *
	 * @since 1.0.0
	 *
	 * @updated 1.4.8
	 */
	public function tables_exist() {

		global $wpdb;

		// Check cache first.
		$cache_key = SEARCHWP_METRICS_PREFIX . 'tables_exist';
		$cached    = wp_cache_get( $cache_key );

		if ( $cached !== false ) {
			return $cached;
		}

		// Get all tables with our prefix.
		$table_prefix = $wpdb->prefix . 'swpext_metrics_';

		$existing_tables = $wpdb->get_col(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $table_prefix ) . '%'
			)
		);

		// Get the expected table names.
		$metrics = new \SearchWP_Metrics();
		$tables  = [
			$metrics->get_table_name( 'clicks' ),
			$metrics->get_table_name( 'ids' ),
			$metrics->get_table_name( 'queries' ),
			$metrics->get_table_name( 'searches' ),
		];

		// Compare the tables.
		$tables_exist = count( array_intersect( $tables, $existing_tables ) ) === count( $tables );

		// Cache the result for 1 hour.
		wp_cache_set( $cache_key, $tables_exist, '', HOUR_IN_SECONDS );

		return $tables_exist;
	}

	/**
	 * Installation procedure; create database tables
	 *
	 * @since 1.0.0
	 */
	private function install() {
		$this->create_tables();
	}

	/**
	 * Create custom database tables
	 */
	private function create_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$metrics = new \SearchWP_Metrics();

		// Clicks table
		$clicks_table_name = $metrics->get_table_name( 'clicks' );
		$sql = "
			CREATE TABLE $clicks_table_name (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`tstamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Time the click happened',
				`hash` bigint(20) DEFAULT NULL COMMENT 'From searches table, public hash of search that triggered click',
				`position` int(9) unsigned NOT NULL COMMENT 'Position in SERP',
				`post_id` bigint(20) unsigned DEFAULT NULL,
				PRIMARY KEY (`id`),
					KEY `hash` (`hash`),
					KEY `position` (`position`)
			) DEFAULT CHARSET=" . $this->charset . $this->collate_sql;
		dbDelta( $sql );

		// IDS table
		$ids_table_name = $metrics->get_table_name( 'ids' );
		$sql = "
			CREATE TABLE $ids_table_name (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`value` char(32) NOT NULL DEFAULT '',
				`type` varchar(20) DEFAULT 'hash',
				PRIMARY KEY (`id`),
					UNIQUE KEY `keyunique` (`value`),
					KEY `hash` (`type`)
			) DEFAULT CHARSET=" . $this->charset . $this->collate_sql;
		dbDelta( $sql );

		// Queries table
		$queries_table_name = $metrics->get_table_name( 'queries' );

		// If utf8mb4 collation is supported, add it
		$varchar_collate = '';
		if ( 'utf8mb4' === $this->charset ) {
			// Normally it's utfmb4_unicode_ci but that is not strict enough for UNIQUE keys
			$varchar_collate = ' COLLATE utf8mb4_bin ';
		}

		$sql = "
			CREATE TABLE $queries_table_name (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`query` varchar(191) CHARACTER SET utf8mb4 {$varchar_collate} NOT NULL DEFAULT '' COMMENT 'The search query itself',
				PRIMARY KEY (`id`),
					UNIQUE KEY `query` (`query`)
			) DEFAULT CHARSET=" . $this->charset . $varchar_collate;
		dbDelta( $sql );

		// Searches table
		$searches_table_name = $metrics->get_table_name( 'searches' );
		$sql = "
			CREATE TABLE $searches_table_name (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`query` bigint(20) unsigned NOT NULL COMMENT 'ID of search query stored in table',
				`engine` varchar(191) NOT NULL DEFAULT 'default' COMMENT 'Engine used for search',
				`tstamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of search',
				`hits` int(9) unsigned NOT NULL COMMENT 'How many hits were found',
				`hash` bigint(20) unsigned NOT NULL COMMENT 'Public representation of each search, allows linking subsequent events (e.g. click) to this search',
				`uid` bigint(20) unsigned DEFAULT NULL COMMENT 'Anonymous user ID',
				PRIMARY KEY (`id`),
					KEY `engine` (`engine`),
					KEY `query` (`query`),
					KEY `hits` (`hits`),
					KEY `hash` (`hash`),
					KEY `uid` (`uid`),
					KEY `tstamp` (`tstamp`)
			) DEFAULT CHARSET=" . $this->charset . $this->collate_sql;
		dbDelta( $sql );
	}

	/**
	 * Upgrade routine
	 */
	function upgrade() {
		global $wpdb;

		// $busy = get_option( SEARCHWP_METRICS_PREFIX . 'doing_upgrade' );

		// if ( ! empty( $busy ) ) {
		// 	// There's already an upgrade running.
		// 	return;
		// }

		// // Set our flag that an upgrade is running.
		// update_option( SEARCHWP_METRICS_PREFIX . 'doing_upgrade', true, 'no' );

		$metrics = new \SearchWP_Metrics();

		// Improve performance with additional index.
		if ( version_compare( $this->last_version, '1.0.8', '<' ) ) {
			$ids_table_name = $metrics->get_table_name( 'ids' );
			$wpdb->query( "ALTER TABLE {$ids_table_name} ADD INDEX `key` (`value`)" );
		}

		if ( version_compare( $this->last_version, '1.2.5', '<' ) ) {

			$blocklists = get_option( $metrics->get_db_prefix() . 'blacklists' );

			if ( ! empty( $blocklists ) ) {
				update_option( $metrics->get_db_prefix() . 'blocklists', $blocklists );
			}
		}

		if ( version_compare( $this->last_version, '1.4.5', '<' ) ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}swpext_metrics_meta" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// Add index to searches table for tstamp.
			$searches_table = $metrics->get_table_name( 'searches' );
			$wpdb->query( "ALTER TABLE {$searches_table} ADD INDEX `tstamp` (`tstamp`);" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// update_option( SEARCHWP_METRICS_PREFIX . 'doing_upgrade', false, 'no' );
	}
}
