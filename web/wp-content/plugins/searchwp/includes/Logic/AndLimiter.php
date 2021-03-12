<?php

/**
 * SearchWP AND Limiter.
 *
 * @package SearchWP
 * @author  Jon Christopher
 */

namespace SearchWP\Logic;

use SearchWP\Query;

/**
 * Class AndLimiter is responsible for generating an AND logic clause.
 *
 * @since 4.0
 */
class AndLimiter {

	/**
	 * Query for the limiter.
	 *
	 * @since 4.0
	 * @var Query
	 */
	private $query;

	/**
	 * Whether this limiter is strict.
	 *
	 * @since 4.1
	 * @var bool
	 */
	private $strict;

	/**
	 * AndLimiter constructor.
	 *
	 * @since 4.0
	 */
	function __construct( Query $query, bool $strict = false ) {
		$this->query  = $query;
		$this->strict = $strict;
	}

	/**
	 * Generates and returns the SQL clause for AND logic.
	 *
	 * NOTES: This implementation is as complex as it is primarily due to partial matches, stems, and synonyms.
	 * Ideally we'd be able to simply restrict results to those that have ALL tokens, but when concerning the above
	 * we want to be more advanced than that and allow AND logic to not be cut short by token transformations
	 * that have taken place. This logic will handle these cases by grouping tokens where applicable.
	 *
	 * @since 4.0
	 * @return string
	 */
	public function get_sql() {
		global $wpdb;

		$index   = \SearchWP::$index;
		$args    = $this->query->get_args();
		$tokens  = $this->query->get_tokens();
		$site_in = $this->query->get_site_limit_sql();

		// If there's an invalid token, AND logic fails. Bail out and short circuit.
		if ( array_key_exists( 0, $tokens ) ) {
			return '0=1';
		}

		// AND logic is based on token groups. In order for AND logic to be satisfied there
		// must be a match for all token groups. A token group consists of a token and its
		// keyword stem and any partial matches when applicable.
		$token_groups = array_map( function( $token ) {
			return [ $token ];
		}, array_keys( $tokens ) );

		// Group tokens based on stemming/partial matches if applicable.
		if ( $this->query->use_stems ) {
			$token_groups = $index->group_tokens_by_stem_from_tokens( array_keys( $tokens ) );
		}

		// If we're dealing with partial matches we can further group the groups.
		if ( apply_filters( 'searchwp\query\partial_matches', \SearchWP\Settings::get_single( 'partial_matches', 'boolean' ), [
			'tokens' => $tokens,
			'query'  => $this->query,
		] ) ) {
			// Rebuild the token groups based on partial matches from the original search string.
			$raw_token_groups = $token_groups;
			$token_groups     = [];

			$original_search_tokens = \SearchWP\Utils::tokenize( $this->query->get_keywords() )->get();

			foreach ( $original_search_tokens as $token ) {
				foreach ( $raw_token_groups as $raw_token_group_tokens ) {
					foreach ( $raw_token_group_tokens as $raw_token_group_token ) {
						if ( false !== stripos( $tokens[ $raw_token_group_token ], $token ) ) {
							if ( ! array_key_exists( $token, $token_groups ) ) {
								$token_groups[ $token ] = [];
							}

							$token_groups[ $token ] = array_unique( array_merge(
								(array) $token_groups[ $token ],
								$raw_token_group_tokens
							) );
						}
					}
				}
			}
		}

		if ( count( $token_groups ) < 2 ) {
			return '';
		}

		$token_groups = array_values( $token_groups );

		do_action( 'searchwp\debug\log', 'Trying AND logic', 'query' );

		// These limiters build on one another, and piggyback a parent condition for the first token
		// in the array, which is why the $token_limiters is off by one; we need that 'parent' clause
		// to establish the AND logic limiter itself, so we're structuring the children first.
		$token_limiters = implode( ' AND ', array_map( function( $token_group ) use ( $index, $site_in ) {
			return "id IN (
				SELECT id
				FROM {$index->get_tables()['index']->table_name} s
				WHERE {$site_in}
					AND {$this->get_engine_source_attribute_where_sql()}
					AND token IN ("
					. implode( ', ', array_fill( 0, count( $token_group ), '%d' ) )
					. ') GROUP BY id)';
		}, array_slice( $token_groups, 1 ) ) ); // Off by one because the 'parent' below uses that.

		// Build values array for preparation.
		$values = call_user_func_array( 'array_merge', array_map( function( $tokens ) use ( $args ) {
			return array_merge( $args['site'], $tokens );
		}, $token_groups ) );

		$and_sql_subquery = "
			SELECT id
			FROM (" .
				str_replace( $this->query->get_placeholder(), '%', $wpdb->prepare("
					SELECT id
					FROM {$index->get_tables()['index']->table_name} s
					WHERE {$site_in}
						AND {$this->get_engine_source_attribute_where_sql()}
						AND token IN ("
							. implode( ', ', array_fill( 0, count( $token_groups[0] ), '%d' ) )
						. ")
						AND {$token_limiters}
					GROUP BY id",
					$values
				) )
			. ') AS a';

		// This subquery could get large, so we're going to pre-execute by default.
		if ( apply_filters( 'searchwp\query\logic\and\pre_execute', true ) ) {
			$and_ids = $wpdb->get_col( $and_sql_subquery );

			// If there are many AND results we're looking at a performance hit we can avoid.
			// With that many results the query is going to take longer to run, so we're going
			// to rely on the overall relevance of OR logic here if possible e.g. not strict logic.
			$max_threshold = apply_filters( 'searchwp\query\logic\and\max_threshold', 2 );
			if ( ! $this->strict && count( $and_ids ) > $max_threshold ) {
				$and_ids = [];
			}

			if ( empty( $and_ids ) ) {
				// Force no results.
				$and_ids = [ '' ];
			}

			$and_sql = $wpdb->prepare( "{$index->get_alias()}.id IN ("
				. implode( ', ', array_fill( 0, count( $and_ids ), '%s' ) )
				. ')', $and_ids );
		} else {
			$and_sql = "{$index->get_alias()}.id IN ({$and_sql_subquery})";
		}

		return $and_sql;
	}

	/**
	 * Generate the WHERE clause that limits AND logic to only the Sources/Attributes for this Engine.
	 *
	 * @since 4.1
	 * @return string
	 */
	protected function get_engine_source_attribute_where_sql() {
		global $wpdb;

		$where  = [];
		$values = [];
		$index_alias   = \SearchWP::$index->get_alias();
		$engine_config = array_filter(
			\SearchWP\Utils::normalize_engine_source_settings( $this->query->get_engine() ),
			function( $source ) {
				return ! empty( $source['attributes'] );
			}
		);

		foreach ( $engine_config as $source => $settings ) {
			$where[] = "{$index_alias}.source = %s AND"
				. $this->query->get_source_attributes_as_where_sql( array_keys( $settings['attributes'] ) );

			$values[] = array_merge(
				[ $source ],
				$this->query->get_source_attributes_as_values( array_keys( $settings['attributes'] ) )
			);
		}

		if ( empty( $where ) ) {
			$sql = '1=1';
		} else {
			$sql = $wpdb->prepare(
				'(' . implode( ' OR ', call_user_func( 'array_merge', $where ) ) . ')',
				call_user_func_array( 'array_merge', $values )
			);
		}

		return $sql;
	}
}
