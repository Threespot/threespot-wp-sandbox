<?php

/**
 * SearchWP Phrase Limiter.
 *
 * @package SearchWP
 * @author  Jon Christopher
 */

namespace SearchWP\Logic;

use SearchWP\Utils;
use SearchWP\Query;
use SearchWP\Tokens;
use SearchWP\Attribute;

/**
 * Class PhraseLimiter is responsible for generating a phrase logic clause.
 *
 * @since 4.0
 */
class PhraseLimiter {

	/**
	 * Query for the limiter.
	 *
	 * @since 4.0
	 * @var Query
	 */
	private $query;

	/**
	 * Phrases to consider.
	 *
	 * @since 4.0
	 * @var array
	 */
	private $phrases;

	// These are placeholder vars for Issue #329.
	// @link https://github.com/searchwp/searchwp/issues/329
	private $do_and_logic;
	private $and_logic_strict;
	private $and_logic_tokens = [];

	/**
	 * Index
	 *
	 * @since 4.0
	 * @var Index
	 */
	private $index;

	/**
	 * Attributes that support phrases.
	 *
	 * @since 4.0
	 * @var array
	 */
	private $attributes;

	/**
	 * PhraseLimiter constructor.
	 *
	 * @since 4.0
	 */
	function __construct( Query $query, $do_and_logic = true, $and_logic_strict = false ) {
		$this->query   = $query;
		$this->phrases = Utils::get_phrases_from_string( $query->get_keywords() );
		$this->index   = \SearchWP::$index;

		$this->do_and_logic     = $do_and_logic;
		$this->and_logic_strict = $and_logic_strict;
		$this->and_logic_tokens = array_filter( explode( ' ', trim(
			str_ireplace( array_merge( $this->phrases, [ '"' ] ), '', $query->get_keywords() )
		) ) );

		if ( empty( $this->and_logic_tokens ) ) {
			$this->do_and_logic = false;
		}

		$this->parse_sources_attributes();
	}

	/**
	 * Generate the (prepared) SQL for Phrase logic.
	 *
	 * @since 4.0
	 * @return false|string
	 */
	public function get_sql() {

		global $wpdb;

		// If there are no attributes to search for phrases, bail out. Returning false will progress to the next algorithm.
		if ( empty( $this->attributes ) ) {
			return false;
		}

		$phrase_results = $this->get_entries();

		// If there are no phrase results, bail out. Returning false will progress to the next algorithm.
		if ( empty( $phrase_results ) ) {
			return false;
		}

		// phpcs:ignore WPForms.Comments.PHPDocHooks
		do_action( 'searchwp\debug\log', 'Performing phrase search', 'query' );

		$clauses = [];
		foreach ( $phrase_results as $source_name => $ids ) {
			$clauses[] = $wpdb->prepare(
				'(s.source = %s AND s.id IN (' . implode( ', ', array_fill( 0, count( $ids ), '%s' ) ) . '))',
				array_merge( [ $source_name ], $ids )
			);
		}

		return ! empty( $clauses ) ? '(' . implode( ' OR ', $clauses ) . ')' : '';
	}

	/**
	 * Finds IDs that have our phrases in the index.
	 *
	 * @since 4.3.18
	 *
	 * @return array
	 */
	private function get_index_entries() {

		global $wpdb;

		$tokens = $this->get_phrase_tokens();

		if ( empty( $tokens ) ) {
			return [];
		}

		$first_tokens           = implode( ',', $tokens['first_keyword_tokens'] );
		$last_tokens            = implode( ',', $tokens['last_keyword_tokens'] );
		$remaining_tokens       = implode( ',', $tokens['remaining_keywords_tokens'] );
		$remaining_tokens_count = count( $tokens['remaining_keywords_tokens'] );
		$all_tokens             = implode( ',', array_filter( [ $remaining_tokens, $first_tokens, $last_tokens ] ) );

		if ( empty( $all_tokens ) ) {
			return [];
		}

		$site_in = $this->get_site_limit_sql();

		$sources = implode( "','", array_keys( $this->attributes ) );

		// Get the index table name.
		$index_table = $this->index->get_tables()['index']->table_name;

		$remaining_tokens_sql = ! empty( $remaining_tokens_count ) ? "COUNT(CASE WHEN s.token IN ({$remaining_tokens}) THEN 1 END) = {$remaining_tokens_count}" : '';
		$trailing_tokens_sql  = '';

		if ( ! empty( $first_tokens ) || ! empty( $last_tokens ) ) {
			// Determine the opening parenthesis based on the remaining tokens SQL.
			$trailing_tokens_sql = ! empty( $remaining_tokens_sql ) ? ' AND (' : '(';

			// Add conditions for first tokens, if present.
			if ( ! empty( $first_tokens ) ) {
				$trailing_tokens_sql .= "COUNT(CASE WHEN s.token IN ({$first_tokens}) THEN 1 END) > 0";
			}

			// Add OR between first and last tokens if both are present.
			if ( ! empty( $first_tokens ) && ! empty( $last_tokens ) ) {
				$trailing_tokens_sql .= ' OR ';
			}

			// Add conditions for last tokens, if present.
			if ( ! empty( $last_tokens ) ) {
				$trailing_tokens_sql .= "COUNT(CASE WHEN s.token IN ({$last_tokens}) THEN 1 END) > 0";
			}

			// Close the parenthesis.
			$trailing_tokens_sql .= ')';
		}

		$sql = "
			SELECT s.id, s.source, s.attribute, s.site
			FROM {$index_table} s
			WHERE {$site_in}
				AND s.source IN ('{$sources}')
				AND s.token IN ({$all_tokens})
			GROUP BY s.id, s.source, s.attribute,s.site
			HAVING
			    {$remaining_tokens_sql}
				{$trailing_tokens_sql};
		";

		$time_start = microtime( true );

		$index_results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		$time_finish = number_format( microtime( true ) - $time_start, 5 );

		$results = $this->normalize_index_results( $index_results );

		$debug_results = $this->normalize_index_results_for_debug( $index_results );

		$this->query->set_debug_data( 'subqueries.phrase.queries.index_query', $sql );
		$this->query->set_debug_data( 'subqueries.phrase.times.index_query', $time_finish );
		$this->query->set_debug_data( 'subqueries.phrase.results.index_query', $debug_results );

		return $results;
	}

	/**
	 * Get the phrase tokens.
	 *
	 * @since 4.3.18
	 */
	private function get_phrase_tokens() {

		global $wpdb;

		$phrase = implode( ' ', $this->phrases );

		$keywords = explode( ' ', $phrase );

		$first_keyword = $keywords[0];
		$last_keyword  = $keywords[ count( $keywords ) - 1 ];
		$tokens_table  = $this->index->get_tables()['tokens']->table_name;

		// Query SearchWP Tokens table to find any token that ends with the first keyword.
		$first_keyword_tokens_sql = "
			SELECT id
			FROM {$tokens_table}
			WHERE token LIKE %s;
		";

		$first_keyword_tokens = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				$first_keyword_tokens_sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'%' . $first_keyword
			)
		);

		// Query SearchWP Tokens table to find any token that starts with the last keyword.
		$last_keyword_tokens_sql = "
			SELECT id
			FROM {$tokens_table}
			WHERE token LIKE %s;
		";

		$last_keyword_tokens = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				$last_keyword_tokens_sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$last_keyword . '%'
			)
		);

		// Query SearchWP Tokens table to find an exact match for the remaining keywords.
		$remaining_keywords = array_slice( $keywords, 1, -1 );

		$remaining_keyword_tokens = [];

		if ( ! empty( $remaining_keywords ) ) {
			$remaining_keywords_placeholders = implode( ',', array_fill( 0, count( $remaining_keywords ), '%s' ) );

			$remaining_keyword_tokens_sql = "
			SELECT id
			FROM {$tokens_table}
			WHERE token IN ({$remaining_keywords_placeholders});
		";

			$remaining_keyword_tokens = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					$remaining_keyword_tokens_sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					...$remaining_keywords
				)
			);
		}

		sort( $first_keyword_tokens );
		sort( $last_keyword_tokens );
		sort( $remaining_keyword_tokens );

		return [
			'first_keyword_tokens'      => $first_keyword_tokens,
			'last_keyword_tokens'       => $last_keyword_tokens,
			'remaining_keywords_tokens' => $remaining_keyword_tokens,
		];
	}

	/**
	 * Normalize the index results.
	 *
	 * @since 4.3.18
	 *
	 * @param array $results The results from the index.
	 *
	 * @return array
	 */
	private function normalize_index_results( $results ) {

		$normalized = [];

		foreach ( $results as $result ) {
			if ( ! array_key_exists( $result->site, $normalized ) ) {
				$normalized[ $result->site ] = [];
			}

			if ( ! array_key_exists( $result->source, $normalized[ $result->site ] ) ) {
				$normalized[ $result->site ][ $result->source ] = [];
			}

			if ( ! array_key_exists( $result->id, $normalized[ $result->site ][ $result->source ] ) ) {
				$normalized[ $result->site ][ $result->source ][ $result->id ] = [];
			}

			// If results->attribute starts with 'meta.', set it to 'meta'.
			$result->attribute = preg_replace( '/^meta\..*/', 'meta', $result->attribute );

			if ( ! array_key_exists( $result->attribute, $normalized[ $result->site ][ $result->source ][ $result->id ] ) ) {
				$normalized[ $result->site ][ $result->source ][ $result->id ][] = $result->attribute;
			}
		}

		return $normalized;
	}

	/**
	 * Finds IDs that have our phrases.
	 *
	 * @since 4.0
	 *
	 * @return array
	 */
	public function get_entries() {

		$results = $this->get_phrase_entries();

		$results = array_filter( $results );

		// Apply AND logic if applicable.
		$results = $this->apply_and_logic( $results );

		/**
		 * Filter to determine if phrase search should be strict.
		 *
		 * @since 4.0
		 *
		 * @param bool $strict_phrase Whether phrase search should be strict.
		 */
		$strict_phrase = apply_filters( 'searchwp\query\logic\phrase\strict', false );

		// If there were no results, and we're not strict about that, tell the Query the search is revised.
		if ( empty( $results ) && ! $strict_phrase ) {
			$this->query->set_suggested_search(
				new Tokens( str_replace( '"', '', $this->query->get_keywords() ) )
			);
		}

		return $results;
	}

	/**
	 * Get the phrase entries.
	 *
	 * @since 4.3.18
	 *
	 * @return array
	 */
	private function get_phrase_entries() {

		$results       = [];
		$index_results = $this->get_index_entries();

		foreach ( $index_results as $site_id => $index_site_results ) {

			foreach ( $this->group_sources_attributes() as $source_name => $source_phrases ) {
				if ( empty( $index_site_results[ $source_name ] ) ) {
					continue;
				}

				$this->process_source_phrases_subqueries( $source_name, $source_phrases, $index_site_results, $site_id, $results );
			}

			foreach ( $results as $index => $results_group ) {
				$results[ $index ] = array_unique( $results_group );
			}
		}

		return $results;
	}

	/**
	 * Process the source phrases subqueries.
	 *
	 * @since 4.3.18
	 *
	 * @param string $source_name    The source name.
	 * @param array  $source_phrases The source phrases.
	 * @param array  $index_results  The index results.
	 * @param string $site_id        The site ID.
	 * @param array  $results        The results.
	 *
	 * @return void
	 */
	private function process_source_phrases_subqueries( $source_name, $source_phrases, $index_results, $site_id, &$results ) {

		foreach ( $source_phrases as $table => $ids ) {

			$table = $this->get_site_table_name( $table, $site_id );

			foreach ( $ids as $id => $columns ) {
				$subqueries       = [];
				$attributes       = [];
				$values           = [];
				$attribute_values = [];

				$this->parse_source_phrases( $source_name, $table, $id, $columns, $index_results, $subqueries, $attributes, $attribute_values, $values );

				$this->execute_subqueries( $subqueries, $index_results, $source_name, $table, $values, $results );
			}
		}
	}

	/**
	 * Execute the source phrases subqueries.
	 *
	 * @since 4.3.18
	 *
	 * @param array  $subqueries    The subqueries.
	 * @param array  $index_results The index results.
	 * @param string $source_name   The source name.
	 * @param string $table         The source table.
	 * @param array  $values        The query values.
	 * @param array  $results       The results.
	 *
	 * @return void
	 */
	private function execute_subqueries( $subqueries, $index_results, $source_name, $table, $values, &$results ) {

		global $wpdb;

		foreach ( $subqueries as $subquery ) {

			$entry_ids_placeholder = implode( ', ', array_fill( 0, count( $index_results[ $source_name ] ), '%d' ) );

			$sql = "
				SELECT p.id
				FROM ({$subquery}) AS p
				WHERE 1 = 1
				AND p.id IN ({$entry_ids_placeholder})
				GROUP BY p.id";

			$entry_ids = array_map( 'intval', array_keys( $index_results[ $source_name ] ) );

			$values = array_merge( $values, $entry_ids );

			$sql = $wpdb->prepare( $sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$time_start = microtime( true );

			if ( ! isset( $results[ $source_name ] ) ) {
				$results[ $source_name ] = [];
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$current_results         = $wpdb->get_col( $sql );
			$results[ $source_name ] = array_unique( array_merge( $results[ $source_name ], $current_results ) );

			$time_finish = number_format( microtime( true ) - $time_start, 5 );

			$source_name_undot = str_replace( '.', '_', $source_name ) . '_' . $table;
			$this->query->set_debug_data( 'subqueries.phrase.queries.' . $source_name_undot, $sql );
			$this->query->set_debug_data( 'subqueries.phrase.times.' . $source_name_undot, $time_finish );
			$this->query->set_debug_data( 'subqueries.phrase.results.' . $source_name_undot, $current_results );
		}
	}

	/**
	 * Parses the source phrases.
	 *
	 * @since 4.3.18
	 *
	 * @param string $source_name      The source name.
	 * @param string $table            The source table.
	 * @param int    $id               The source ID.
	 * @param array  $columns          The source columns.
	 * @param array  $index_results    The index results.
	 * @param array  $subqueries       The subqueries.
	 * @param array  $attributes       The attributes.
	 * @param array  $attribute_values The attribute values.
	 * @param array  $values           The query values.
	 */
	private function parse_source_phrases( $source_name, $table, $id, $columns, $index_results, &$subqueries, &$attributes, &$attribute_values, &$values ) {

		$wheres           = [];
		$index_attributes = array_unique( array_merge( ...$index_results[ $source_name ] ) );

		foreach ( $columns as $column => $source_attribute ) {
			$attribute_name = $source_attribute['attribute']->get_name();

			if ( ! in_array( $attribute_name, $index_attributes, true ) ) {
				continue;
			}

			$attribute_where = $this->get_attribute_where_clauses( $column, $source_attribute, $values );
			$wheres[]        = implode( ' OR ', $attribute_where );

			$this->add_attribute_conditions( $source_attribute, $attributes, $attribute_values );
		}

		if ( ! empty( $wheres ) ) {
			$subqueries[] = "SELECT {$id} AS id FROM {$table} WHERE 1=1 AND (" . implode( ' OR ', $wheres ) . ')';
		}
	}

	/**
	 * Gets the attribute where clauses.
	 *
	 * @since 4.3.18
	 *
	 * @param string $column           The table column.
	 * @param array  $source_attribute The source attribute.
	 * @param array  $values           The values.
	 */
	private function get_attribute_where_clauses( $column, $source_attribute, &$values ) {

		global $wpdb;

		return array_map(
			function ( $phrase ) use ( $column, $source_attribute, &$values, $wpdb ) {
				$values[] = '%' . $wpdb->esc_like( trim( $phrase ) ) . '%';

				if ( $column === 'meta_value' ) {
					return $this->get_meta_value_clause( $source_attribute, $column );
				}

				return "{$column} LIKE %s";
			},
			$this->phrases
		);
	}

	/**
	 * Gets the meta value clause.
	 *
	 * @since 4.3.18
	 *
	 * @param array  $source_attribute The source attribute.
	 * @param string $column           The column.
	 */
	private function get_meta_value_clause( $source_attribute, $column ) {

		if ( ! empty( $source_attribute['value'] ) && is_array( $source_attribute['value'] ) ) {
			$meta_keys = array_key_exists( '*', $source_attribute['value'] ) ? [] : array_keys( $source_attribute['value'] );

			if ( ! empty( $meta_keys ) ) {
				$meta_key_clause = implode(
					' OR ',
					array_map(
						function ( $meta_key ) {
							$meta_key = str_replace( '*', '%', $meta_key );

							return "meta_key LIKE '{$meta_key}'";
						},
						$meta_keys
					)
				);

				return "{$column} LIKE %s AND ({$meta_key_clause})";
			}
		}

		return "{$column} LIKE %s";
	}

	/**
	 * Adds the attribute conditions.
	 *
	 * @since 4.3.18
	 *
	 * @param array $source_attribute The source attribute.
	 * @param array $attributes       The attributes.
	 * @param array $attribute_values The attribute values.
	 */
	private function add_attribute_conditions( $source_attribute, &$attributes, &$attribute_values ) {

		global $wpdb;

		if ( $source_attribute['attribute']->get_options() ) {
			$attributes[]       = 's.attribute LIKE %s';
			$attribute_values[] = $wpdb->esc_like( $source_attribute['attribute']->get_name() . SEARCHWP_SEPARATOR ) . '%';
		} else {
			$attributes[]       = 's.attribute = %s';
			$attribute_values[] = $source_attribute['attribute']->get_name();
		}
	}

	/**
	 * Apply AND logic to the results.
	 *
	 * @since 4.3.18
	 *
	 * @param array $results The results to apply AND logic to.
	 */
	private function apply_and_logic( $results ) {

		if ( ! empty( $results ) && $this->do_and_logic ) {
			$mods = [];

			foreach ( $results as $source_name => $ids ) {

				$source_id_column = $this->index->get_source_by_name( $source_name )->get_db_id_column();

				$mod = new \SearchWP\Mod( $source_name );
				$mod->set_where(
					[
						[
							'column'  => $source_id_column,
							'value'   => $ids,
							'compare' => 'IN',
						],
					]
				);

				$mods[] = $mod;
			}

			// Prevent recursion caused by quoted/phrase searches set by synonyms.
			add_filter( 'searchwp\synonyms', '__return_empty_array' );

			$and_logic_query = new \SearchWP\Query(
				implode( ' ', $this->and_logic_tokens ),
				[
					'caller_id' => $this->query->get_id(),
					'engine'    => $this->query->get_engine()->get_name(),
					'per_page'  => -1,
					'mods'      => $mods,
				]
			);

			remove_filter( 'searchwp\synonyms', '__return_empty_array' );

			/**
			 * Filter to determine if AND logic should be strict.
			 *
			 * @since 4.0
			 *
			 * @param bool $strict_and_logic Whether AND logic should be strict.
			 */
			$strict_and_logic = apply_filters( 'searchwp\query\logic\phrase\and_logic_strict', $this->and_logic_strict );

			if (
				empty( $and_logic_query->get_results() )
				&& $strict_and_logic
			) {
				$results = [];
			} elseif ( ! empty( $and_logic_query->get_results() ) ) {

				$and_logic_results_normalized = $this->normalize_and_logic_results( $and_logic_query );

				if ( ! empty( $and_logic_results_normalized ) ) {
					$results = $and_logic_results_normalized;
				}
			}
		}

		return $results;
	}

	/**
	 * Normalize the AND logic results.
	 *
	 * @since 4.3.18
	 *
	 * @param \SearchWP\Query $and_logic_query The AND logic query.
	 *
	 * @return array
	 */
	private function normalize_and_logic_results( $and_logic_query ) {

		$and_logic_results_normalized = [];

		foreach ( $and_logic_query->get_results() as $and_logic_result ) {
			if ( ! array_key_exists( $and_logic_result->source, $and_logic_results_normalized ) ) {
				$and_logic_results_normalized[ $and_logic_result->source ] = [];
			}

			$and_logic_results_normalized[ $and_logic_result->source ][] = $and_logic_result->id;
		}

		return $and_logic_results_normalized;
	}

	/**
	 * Groups Phrases configs into associative array of tables and columns.
	 *
	 * @since 4.0
	 * @return array
	 */
	private function group_sources_attributes() {
		$groups = [];

		foreach ( $this->attributes as $source_name => $attributes ) {
			if ( ! array_key_exists( $source_name, $groups ) ) {
				$groups[ $source_name ] = [];
			}

			foreach ( $attributes as $attribute => $phrase_cols ) {
				foreach ( $phrase_cols as $phrase_col ) {
					if ( ! array_key_exists( $phrase_col['table'], $groups[ $source_name ] ) ) {
						$groups[ $source_name ][ $phrase_col['table'] ] = [];
					}

					if ( ! array_key_exists( $phrase_col['id'], $groups[ $source_name ][ $phrase_col['table'] ] ) ) {
						$groups[ $source_name ][ $phrase_col['table'] ][ $phrase_col['id'] ] = [];
					}

					if ( ! array_key_exists( $phrase_col['column'], $groups[ $source_name ][ $phrase_col['table'] ][ $phrase_col['id'] ] ) ) {
						$groups[ $source_name ][ $phrase_col['table'] ][ $phrase_col['id'] ][ $phrase_col['column'] ] = [];
					}

					$source = $this->index->get_source_by_name( $source_name );
					$groups[ $source_name ][ $phrase_col['table'] ][ $phrase_col['id'] ][ $phrase_col['column'] ] = [
						'attribute' => $source->get_attribute( $attribute ),
						'value'     => $phrase_col['value']
					];
				}
			}
		}

		return $groups;
	}

	/**
	 * Retrieves Source Attributes that have Phrases support.
	 *
	 * @since 4.0
	 * @return array
	 */
	private function parse_sources_attributes() {
		foreach ( $this->query->get_engine()->get_sources() as $source ) {
			$applicable_source_attributes = array_filter(
				array_map( function( Attribute $attribute ) use ( $source ) {
					$phrases = $attribute->get_phrases();
					$settings = $attribute->get_settings();

					if ( is_string( $phrases ) ) {
						$table  = $source->get_db_table();

						if ( ! Utils::valid_db_column( $table, $phrases ) ) {
							return false;
						}

						$phrases = [ [
							'table'  => $source->get_db_table(),
							'column' => $phrases,
							'value'  => $settings,
							'id'     => $source->get_db_id_column(),
						] ];
					}
					else if( is_array( $phrases ) ){
						$phrases[0]['value'] = $settings;
					}

					// We need to verify that the Attribute with phrase support has been added to the engine.
					return $phrases && ! empty( $attribute->get_settings() ) ? $phrases : false;
				}, $source->get_attributes() )
			);

			if ( ! empty( $applicable_source_attributes ) ) {
				$this->attributes[ $source->get_name() ] = $applicable_source_attributes;
			}
		}
	}

	/**
	 * Get the site limit SQL.
	 *
	 * @since 4.3.18
	 *
	 * @return string
	 */
	private function get_site_limit_sql() {

		global $wpdb;

		$site_in = '1=1';

		if ( $this->query->get_args()['site'] !== 'all' ) {
			$site_in = $wpdb->prepare( $this->query->get_site_limit_sql(), $this->query->get_args()['site'] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return $site_in;
	}

	/**
	 * Get the table name for the passed site ID.
	 *
	 * @since 4.3.18
	 *
	 * @param string $table   The table name.
	 * @param int    $site_id The site ID.
	 *
	 * @return string
	 */
	private function get_site_table_name( $table, $site_id ) {

		global $wpdb;

		// Get the base table prefix.
		$base_prefix = $wpdb->prefix;

		// Get the table name without the base prefix maybe followed by the blog ID.
		$table_name = preg_replace( "/{$base_prefix}(?:\d_)?/", '', $table );

		// Get the current blog prefix.
		$blog_prefix = $wpdb->get_blog_prefix( $site_id );

		// Return the table name with the current blog prefix.
		return $blog_prefix . $table_name;
	}

	/**
	 * Normalize the index results for debug.
	 *
	 * @since 4.3.18
	 *
	 * @param array $results The results from the index.
	 *
	 * @return array
	 */
	private function normalize_index_results_for_debug( $results ) {

		$normalized = [];

		foreach ( $results as $result ) {
			if ( ! array_key_exists( $result->site, $normalized ) ) {
				$normalized[ $result->site ] = [];
			}

			if ( ! in_array( $result->id, $normalized[ $result->site ], true ) ) {
				$normalized[ $result->site ][] = $result->id;
			}
		}

		// Sort the normalized array by site.
		ksort( $normalized );

		// Convert the array to a string like: "site: site_number: list of ids comma separated".
		foreach ( $normalized as $site => $ids ) {
			$normalized[ $site ] = array_unique( $ids );
		}

		return $normalized;
	}
}
