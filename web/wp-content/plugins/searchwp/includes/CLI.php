<?php

/**
 * SearchWP CLI.
 *
 * @package SearchWP
 * @author  Jon Christopher
 */

namespace SearchWP;

/**
 * Class CLI is responsible for adding WP CLI commands.
 *
 * @since 4.0
 */
class CLI {

	/**
	 * Constructor.
	 *
	 * @since 4.0
	 * @return void
	 */
	public function __construct() {
		if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		\WP_CLI::add_command( 'searchwp index',       [ $this, 'index' ] );
		\WP_CLI::add_command( 'searchwp reindex',     [ $this, 'reindex' ] );
		\WP_CLI::add_command( 'searchwp diagnostics', [ $this, 'diagnostics' ] );
	}

	/**
	 * Trigger the Indexer, optionally rebuilding it.
	 *
	 * ## OPTIONS
	 *
	 * [--site=<all|ids>]
	 * : Whether to rebuild the index before indexing.
	 *
	 * [--rebuild]
	 * : Whether to rebuild the index before indexing.
	 *
	 * @since 4.0
	 */
	public static function index( $args = [], $assoc_args = [] ) {
		gc_enable();

		add_filter( 'searchwp\debug', '__return_false', 99999 );

		$arguments = wp_parse_args( $assoc_args, [
			'rebuild' => false,
		] );

		if ( is_numeric( $arguments['site'] ) ) {
			$arguments['site'] = [ absint( $arguments['site'] ) ];
		} else if ( false !== strpos( $arguments['site'], ',' ) ) {
			$arguments['site'] = array_filter( array_unique( array_map( function( $site ) {
				return is_numeric( $site ) ? absint( $site ) : false;
			}, explode( ',', $arguments['site'] ) ) ) );
		} else {
			$arguments['site'] = 'all';
		}

		$index   = \SearchWP::$index;
		$indexer = \SearchWP::$indexer;

		// Prevent the indexer from running because this supercedes it.
		$indexer->pause();
		$indexer->_destroy_queue();

		/**
		 * Rebuild the index?
		 */
		if ( ! empty( $arguments['rebuild'] ) && 'all' === $arguments['site'] ) {
			\WP_CLI::line( 'Dropping index' );
			$index->truncate_tables();
		} else if ( is_array( $arguments['site'] ) ) {
			foreach( $arguments['site'] as $site_id ) {
				\WP_CLI::line( 'Dropping index for site ' . $site_id );
				$index->drop_site( $site_id );
			}
		}

		$entries = [];

		if ( is_multisite() ) {
			foreach( get_sites( [ 'fields' => 'ids' ] ) as $site_id ) {
				if ( is_array( $arguments['site'] ) && ! in_array( $site_id, $arguments['site'] ) ) {
					\WP_CLI::line( 'Skipping site ' . $site_id );
					continue;
				}

				\WP_CLI::line( 'Enqueueing entries for site ' . $site_id );

				switch_to_blog( $site_id );
				$indexer->init();
				$entries = array_merge( $entries, self::setup_site_entries() );
				restore_current_blog();
				$indexer->init();
			}
		} else {
			\WP_CLI::line( 'Enqueueing entries...' );
			$entries = self::setup_site_entries();
		}

		if ( ! empty( $entries ) ) {
			$progress = \WP_CLI\Utils\make_progress_bar( 'Building index:', count( $entries ) );

			foreach ( $entries as $key => $entry ) {

				$switched_site = false;
				if ( is_multisite() && $entry['site'] != get_current_blog_id() ) {
					switch_to_blog( $entry['site'] );
					$indexer->init();
					$switched_site = true;
				}

				$source = $index->get_source_by_name( $entry['source'] );
				$entry  = apply_filters( 'searchwp\indexer\entry', new Entry( $source, $entry['id'] ) );

				if ( ! $entry instanceof Entry ) {
					$progress->tick();
					continue;
				}

				if ( false === $index->add( $entry ) ) {
					$index->mark_entry_as( $entry, 'omitted' );
				}

				if ( $switched_site ) {
					restore_current_blog();
					$indexer->init();
				}

				// Clean up variables and cache usage (because it doesn't apply here).
				unset( $entry );
				unset( $source );
				unset( $entries[ $key ] );
				gc_collect_cycles();

				$progress->tick();
			}

			$progress->finish();
		}

		$indexer->unpause();

		\WP_CLI::success( 'Index built!' );
	}

	/**
	 * Reindex entries.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : ID(s) of entries (for the Source) to reindex (space separated).
	 *
	 * [--source=<source>]
	 * : Source of entries to reindex. Omit (or use `post`) as a shortcut for all WP_Post-based Sources.
	 *
	 * ## EXAMPLES
	 *
	 * 	# Reindex WP_Post 114
	 * 	$ wp searchwp reindex 114
	 *
	 * 	# Reindex WP_Posts 88 and 314
	 * 	$ wp searchwp reindex 88 314
	 *
	 * 	# Reindex all WP_Posts of Page post type
	 * 	$ wp searchwp reindex $(wp post list --post_type=page --format=ids)
	 *
	 * 	# Reindex WP_User 2
	 * 	$ wp searchwp reindex 2 --source=User
	 *
	 * @since 4.1
	 */
	public static function reindex( $args = [], $assoc_args = [] ) {
		$arguments = wp_parse_args( $assoc_args, [
			'source' => 'post',
		] );

		$ids = array_unique( array_filter( array_map( 'trim', $args ) ) );

		if ( empty( $ids ) ) {
			\WP_CLI::error( 'No ID(s) provided.' );
		}

		$index = \SearchWP::$index;

		foreach ( $ids as $entry_id ) {
			global $wpdb;

			// Maybe look up source name.
			$source = $arguments['source'];
			if ( 'post' === $arguments['source'] ) {
				$source = self::normalize_source_for_entry_id( $arguments['source'], $entry_id );
			}

			if ( empty( $source ) ) {
				// This Entry wasn't indexed in the first place, so we'll prep to index it.
				$source = 'post' . SEARCHWP_SEPARATOR . get_post_type( $entry_id );
			} else {
				// Index drop.
				$wpdb->query( $wpdb->prepare( "
					DELETE FROM {$index->get_tables()['index']->table_name}
					WHERE id = %s AND source = %s AND site = %d",
					$entry_id,
					$source,
					get_current_blog_id()
				) );

				// Status drop.
				$wpdb->query( $wpdb->prepare( "
					DELETE FROM {$index->get_tables()['status']->table_name}
					WHERE id = %s AND source = %s AND site = %d",
					$entry_id,
					$source,
					get_current_blog_id()
				) );
			}

			$entry = apply_filters( 'searchwp\indexer\entry', new Entry( $source, $entry_id ) );

			if ( ! $entry instanceof Entry ) {
				\WP_CLI::error( $source . ' [' . $entry_id . '] is not a valid Entry' );
				continue;
			}

			$index->introduce( new \SearchWP\Entries( $index->get_source_by_name( $source ), [ $entry_id ] ) );

			if ( false === $index->add( $entry ) ) {
				$index->mark_entry_as( $entry, 'omitted' );
				\WP_CLI::error( $source . ' [' . $entry_id . '] failed to index' );
			} else {
				\WP_CLI::line( 'Reindexed ' . $source . ' [' . $entry_id . ']' );
			}
		}

		\WP_CLI::success( 'Done!' );
	}

	/**
	 * Diagnose information about the index.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : ID of Entry to diagnose.
	 *
	 * <tokens|status>
	 * : Whether to retrieve the tokens for or status of the Entry.
	 *
	 * [--source=<source>]
	 * : Source of Entry to diagnose. Omit (or use `post`) as a shortcut for any WP_Post-based Source.
	 *
	 * @since 4.1
	 */
	public static function diagnostics( $args = [], $assoc_args = [] ) {
		$operation = $args[0];
		$entry_id  = $args[1];
		$arguments = wp_parse_args( $assoc_args, [
			'source' => 'post',
		] );

		$source = self::normalize_source_for_entry_id( $arguments['source'], $entry_id );

		switch ( $operation ) {
			case 'tokens':
				$entry  = new \SearchWP\Entry( \SearchWP::$index->get_source_by_name( $source ), $entry_id );
				$tokens = \SearchWP::$index->get_tokens_for_entry( $entry );

				$tokens = array_unique( $tokens );

				sort( $tokens );

				\WP_CLI::line( wp_json_encode( $tokens ) );
			break;

			case 'status':
				$status = \SearchWP::$index->get_source_id_status( $source, $entry_id );

				if ( $status->queued ) {
					\WP_CLI::line( 'queued:' . strtotime( $status->queued ) );
				} else if ( $status->indexed ) {
					\WP_CLI::line( 'indexed:' . strtotime( $status->indexed ) );
				} else if ( $status->omitted ) {
					\WP_CLI::line( 'omitted:' . strtotime( $status->omitted ) );
				}
			break;
		}
	}

	/**
	 * Retrieves unindexed site entries and introduces them to the Index.
	 *
	 * @since 4.0
	 * @return array
	 */
	private static function setup_site_entries() {
		global $wpdb;

		$entries = [];
		$index   = \SearchWP::$index;

		foreach ( Settings::get_engines( true ) as $engine ) {
			foreach ( $engine->get_sources() as $source ) {
				$site      = get_current_blog_id();
				$timestamp = current_time( 'mysql' );

				while ( ! empty ( $unindexed_entries_ids = $source->get_unhandled_ids( 100 ) ) ) {
					$these_entries = array_map( function( $entry_id ) use ( $source, $site ) {
						return [
							'source' => $source->get_name(),
							'id'     => $entry_id,
							'site'   => $site,
						];
					}, $unindexed_entries_ids );

					unset( $unindexed_entries_ids );

					// Introduce these Entries to the Index.
					$values       = [];
					$placeholders = [];

					foreach ( $these_entries as $this_entry ) {
						array_push(
							$values,
							$this_entry['id'],
							$this_entry['source'],
							$timestamp,
							$site
						);
						$placeholders[] = '( %s, %s, %s, %d )';
					}

					$wpdb->query( $wpdb->prepare( "
						INSERT INTO {$index->get_tables()['status']->table_name}
						( id, source, queued, site )
						VALUES " . implode( ', ', $placeholders ),
						$values
					) );

					$entries = array_merge( $entries, $these_entries );

					unset( $these_entries );
				}
			}
		}

		return $entries;
	}

	/**
	 * Many commands allow for the --source to be omitted, which then assumes a WP_Post Source which needs to be retrieved based on ID.
	 *
	 * @since 4.1
	 * @param string $source_name The Source name.
	 * @param string $entry_id    The Entry ID.
	 * @return string             The Source name.
	 */
	private static function normalize_source_for_entry_id( $source_name, $entry_id ) {
		global $wpdb;

		if ( 'post' === $source_name ) {
			$index = \SearchWP::$index;

			$source_name = $wpdb->get_var( $wpdb->prepare( "
				SELECT source FROM {$index->get_tables()['status']->table_name}
				WHERE id = %s AND source LIKE %s AND site = %d",
				$entry_id,
				'post' . SEARCHWP_SEPARATOR . '%',
				get_current_blog_id()
			) );
		}

		return $source_name;
	}
}
