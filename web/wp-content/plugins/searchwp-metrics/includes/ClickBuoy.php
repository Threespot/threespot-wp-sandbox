<?php

namespace SearchWP_Metrics;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClickBuoy.
 *
 * @since 1.0.0
 *
 * @package SearchWP_Metrics
 */
class ClickBuoy {

	/**
	 * The metrics object.
	 *
	 * @since 1.0.0
	 *
	 * @var \SearchWP_Metrics
	 */
	private $metrics;

	/**
	 * The ID object.
	 *
	 * @since 1.0.0
	 *
	 * @var \SearchWP_Metrics\ID
	 */
	private $id;

	/**
	 * The search query.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $search_query;

	/**
	 * The modifier to apply to the buoy.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private $modifier = 1;

	/**
	 * ClickBuoy constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->metrics = new \SearchWP_Metrics();
		$this->id      = new \SearchWP_Metrics\ID();
	}

	/**
	 * Initialize the ClickBuoy.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {

		add_action( 'searchwp_metrics_click', [ $this, 'track_click' ] );

		// These hooks implement the buoy.
		add_filter( 'searchwp\query\search_string', [ $this, 'store_search_query' ] );
		add_filter( 'searchwp\query\mods', [ $this, 'implement_mod' ] );
	}

	/**
	 * Implement the buoy Mod.
	 *
	 * @since 1.2.0
	 *
	 * @param array $mods The current mods.
	 *
	 * @return mixed
	 */
	public function implement_mod( $mods ) {

		global $wpdb;

		$meta_key = $this->get_meta_key_for_query( $this->search_query );

		$mod = new \SearchWP\Mod();
		$mod->set_local_table( $wpdb->postmeta );
		$mod->on( 'post_id', [ 'column' => 'id' ] );
		$mod->on( 'meta_key', [ 'value' => $meta_key ] );
		$mod->weight(
			function ( $mod ) {
				return "( {$this->modifier} * ( COALESCE({$mod->get_local_table_alias()}.meta_value, 0) ) )";
			}
		);

		$mods[] = $mod;

		return $mods;
	}

	/**
	 * Store the search query for later use.
	 *
	 * @since 1.0.0
	 *
	 * @param string $terms The search query.
	 *
	 * @return string
	 */
	public function store_search_query( $terms ) {

		$this->search_query = is_array( $terms ) ? implode( ' ', $terms ) : trim( $terms );

		return $terms;
	}

	/**
	 * Returns meta key to use for submitted query.
	 *
	 * @since 1.0.0
	 *
	 * @param string $query The query to get the meta key for.
	 */
	public function get_meta_key_for_query( $query ) {

		return $this->metrics->get_db_prefix() . 'click_buoy_' . md5( $query );
	}

	/**
	 * Callback to click event to increment the click count buoy stored as post meta.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The arguments passed to the click event.
	 */
	public function track_click( $args ) {

		// The meta key will be based on a hash of the original search query in case Metrics data
		// is reset; if that happens the IDs will no longer match and the buoy would be all wrong.
		$query_from_hash = $this->id->get_query_from_hash_id( absint( $args['hash'] ) );
		$meta_key        = $this->get_meta_key_for_query( $query_from_hash['query'] );

		// Determine the current click count and increment.
		$current_click_count = get_post_meta( $args['post_id'], $meta_key, true );
		$current_click_count = empty( $current_click_count ) ? 1 : absint( $current_click_count ) + 1;

		update_post_meta( $args['post_id'], $meta_key, $current_click_count );
	}
}
