<?php
/**
 * SearchWP's Indexer. Heavily influenced by @link https://github.com/deliciousbrains/wp-background-processing
 *
 * @package SearchWP
 * @author  Jon Christopher
 */

namespace SearchWP;

use SearchWP\Entry;
use SearchWP\BackgroundProcess;

class Indexer extends BackgroundProcess {
	/**
	 * The Index Controller.
	 *
	 * @since 4.1
	 * @var mixed
	 */
	private $index;

	/**
	 * Constructor.
	 *
	 * @since 4.1
	 */
	function __construct() {
		$this->init();

		// When we switch sites, the Sources will have new database tables.
		// The provider does this as well, but this hook is left in place in case of external use.
		add_action( 'switch_blog', [ $this, 'init' ] );

		// When a site is deleted from the network, we need to drop it too.
		add_action( 'wp_uninitialize_site', [ $this, 'wp_delete_site' ] );
	}

	/**
	 * Initializer.
	 *
	 * @since 4.1
	 * @return void
	 */
	public function init() {
		$this->index      = \SearchWP::$index;
		$this->name       = 'indexer';
		$this->identifier = SEARCHWP_PREFIX . 'indexer_' . get_current_blog_id();

		parent::init();

		do_action( 'searchwp\indexer\init', $this );
	}

	/**
	 * Handler when process time limit has been exceeded.
	 *
	 * @since 4.1
	 */
	protected function time_limit_exceeded() {
		do_action( 'searchwp\debug\log', 'Process time limit exceeded!', 'indexer' );

		// Retrieve the last Entry we tried to index and mark it as omitted.
		$failed_entry = get_option( $this->identifier . '_indexing' );

		if ( empty( $failed_entry ) || ! is_array( $failed_entry ) ) {
			return;
		}

		$this->index->mark_entry_as( new Entry( $failed_entry['source'], $failed_entry['id'] ), 'omitted' );
	}

	/**
	 * Uninstallation routine triggered in uninstall.php.
	 *
	 * @since 4.1
	 * @return void
	 */
	public function _uninstall() {
		delete_option( $this->identifier . '_indexing' );

		parent::_uninstall();
	}

	/**
	 * Callback after a site is deleted from the network.
	 *
	 * @since 4.1
	 * @param WP_Site $site The site that was deleted.
	 * @return void
	 */
	public function wp_delete_site( $site ) {
		$this->index->drop_site( $site->blog_id );
	}

	/**
	 * Cycles the indexer. Indexes queued entries, finds entries to queue.
	 *
	 * @since 4.1
	 * @return int Number of Entries queued during this cycle.
	 */
	public function cycle() {
		$to_index = $this->index->get_queued();

		// First priority is to index what has been queued already.
		if ( ! empty( $to_index ) ) {
			do_action( 'searchwp\debug\log', 'Processing batch (' . count( $to_index ) . ')', 'indexer' );
			$this->index_entries( $to_index );
		}

		// Second priority (if there is nothing ot index) is to queue the next batch.
		$has_updates = $this->queue_unindexed_entries();

		// If there are entries to index, schedule that action.
		if ( $has_updates ) {
			do_action( 'searchwp\debug\log', 'Queued next batch (' . absint( $has_updates ) . ')', 'indexer' );
		} else {
			// If the cron health check is running, this is a good place to be but we don't need to know about it.
			if ( ! wp_doing_cron() ) {
				do_action( 'searchwp\indexer\complete' );
				do_action( 'searchwp\debug\log', 'Index built', 'indexer' );

				delete_option( $this->identifier . '_indexing' );
			}
		}

		return $has_updates;
	}

	/**
	 * Destroys the queue and clears the schedule.
	 *
	 * @since 4.1
	 * @return void
	 */
	public function _destroy_queue() {
		global $wpdb;

		$status = \SearchWP::$index->get_tables()['status']->table_name;
		$wpdb->query( $wpdb->prepare( "
			DELETE FROM {$status}
			WHERE indexed IS NULL
			AND omitted IS NULL
			AND queued IS NOT NULL
			AND site = %d",
			get_current_blog_id()
		) );
	}

	/**
	 * Forcefully wakes up the indexer by destroying the queue and triggering itself.
	 *
	 * @since 4.1
	 * @return void
	 */
	public function _wake_up() {
		do_action( 'searchwp\debug\log', 'Waking up', 'indexer' );
		$this->_destroy_queue();
		$this->unlock_process();
		sleep( 1 );
		$this->trigger();
	}

	/**
	 * Index queued Entries.
	 *
	 * @since 4.1
	 * @param \SearchWP\Entry[] $to_index Entries to index.
	 * @return void
	 */
	private function index_entries( array $to_index ) {
		do_action( 'searchwp\indexer\batch' );

		foreach ( $to_index as $entry_to_index ) {
			$source     = $entry_to_index->source;
			$source_id  = $entry_to_index->id;
			$entry_orig = new Entry( $source, $source_id );
			$entry      = apply_filters( 'searchwp\indexer\entry', $entry_orig );

			if ( ! $entry instanceof Entry ) {
				do_action( 'searchwp\debug\log', 'Invalid Entry detected: omitting', 'indexer' );
				do_action( 'searchwp\debug\log', $entry, 'indexer' );
				$this->index->mark_entry_as( $entry_orig, 'omitted' );

				continue;
			}

			do_action( 'searchwp\debug\log', 'Indexing ' . $source . ':' . $source_id, 'indexer' );
			update_option( $this->identifier . '_indexing', [ 'source' => $source, 'id' => $source_id, ], 'no' );
			$result = $this->index->add( $entry );

			// Did this Entry fail to index?
			if ( false === $result ) {
				do_action( 'searchwp\debug\log', '[Failed] Omitting ' . $source . ':' . $source_id, 'indexer' );
				$this->index->mark_entry_as( $entry, 'omitted' );
			} else {
				delete_option( $this->identifier . '_indexing' );
			}

			// Have we exceeded our time limit?
			if ( time() > $this->start_time + absint( apply_filters( 'searchwp\indexer\process_time_limit', 60 ) ) ) {
				do_action( 'searchwp\debug\log', 'Process time limit exceeded!', 'indexer' );
				break;
			}

			if ( $this->memory_exceeded() ) {
				do_action( 'searchwp\debug\log', 'Memory threshold exceeded!', 'indexer' );
				break;
			}
		}
	}

	/**
	 * Pauses the indexer.
	 *
	 * @since 4.0
	 * @return void
	 */
	public function pause() {
		$this->lock_process();
		Settings::update( 'indexer_paused', true );
	}

	/**
	 * Unpauses the indexer.
	 *
	 * @since 4.0
	 * @return void
	 */
	public function unpause() {
		$this->unlock_process();
		Settings::update( 'indexer_paused', false );
	}

	/**
	 * Adds unindexed Entries to the queue.
	 *
	 * @since 4.0
	 * @return int Number of Entries that were queued.
	 */
	private function queue_unindexed_entries() {
		do_action( 'searchwp\indexer\update' );

		$has_updates = 0;
		$batch_size  = Settings::get_single( 'reduced_indexer_aggressiveness', 'boolean' ) ? 1 : 25;
		$limit       = absint( apply_filters( 'searchwp\indexer\batch_size', $batch_size ) );

		// Iterate over Engines to determine what to index. This will automatically
		// apply Engine Rules as we iterate and queue anything that meets any criteria
		// for any saved Engine. Querying will also apply the same Engine Rules.
		foreach ( Settings::get_engines() as $engine ) {
			foreach ( $engine->get_sources() as $source ) {
				$limit = absint( apply_filters( 'searchwp\indexer\batch_size\\' . $source->get_name() , $limit ) );

				if ( $limit < 1 ) {
					$limit = 1;
				}

				$unindexed_entries = $source->get_unindexed_entries( $limit );

				if ( ! empty( $unindexed_entries->get_ids() ) ) {
					$this->index->introduce( $unindexed_entries );
					$has_updates = count( $unindexed_entries->get_ids() );

					break;
				}
			}

			if ( $has_updates ) {
				do_action( 'searchwp\indexer\has_updates' );
				break;
			}
		}

		return $has_updates;
	}

	/**
	 * Returns WP_Cron interval offset. We want to offset this process to the Index Controller.
	 *
	 * @since 4.1
	 * @return int Offset in seconds.
	 */
	protected function interval_offset() {
		// Run two minutes later.
		return 120;
	}
}
