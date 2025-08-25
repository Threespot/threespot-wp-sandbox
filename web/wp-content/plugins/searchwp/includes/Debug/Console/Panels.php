<?php

namespace SearchWP\Debug\Console;

use SearchWP\Query;
use SearchWP\Support\Arr;
use SearchWP\Debug\Watcher;
use SearchWP\Dependencies\Doctrine\SqlFormatter\SqlFormatter;

/**
 * Panels class manages SearchWP Debugging Console panels nav and content.
 *
 * @since 4.2.9
 */
class Panels {

	/**
	 * Get all panels' settings and content.
	 *
	 * @since 4.2.9
	 */
	public static function get_panels() {

		$panels = array_merge( self::get_query_panels(), self::get_general_panels() );
		$panels = (array) apply_filters( 'searchwp\debug\console\panels', $panels );

		return self::sort_panels( $panels );
	}

	/**
	 * Get panels for all the search queries.
	 *
	 * @since 4.2.9
	 *
	 * @return array
	 */
	private static function get_query_panels() {

		$queries = searchwp()->get( Watcher::class )->get_queries();

		if ( empty( $queries ) ) {
			return self::get_query_panels_empty();
		}

		$count  = count( $queries );
		$panels = [];

		$num = 0;
		foreach ( $queries as $id => $query ) {

			$num++;
			$slug = 'query_' . $num;

			/* translators: %s: Query number. */
			$title = sprintf( _n( 'Query', 'Query %s', $count, 'searchwp' ), number_format_i18n( $num ) ); // phpcs:ignore WordPress.WP.I18n.MismatchedPlaceholders, WordPress.WP.I18n.MissingSingularPlaceholder

			$panels[ $slug ] = [
				'id'         => 'query_' . $id,
				'slug'       => $slug,
				'title'      => $title,
				'position'   => 9 + $num,
				'content'    => self::get_panel_content__query( $query ),
				'sub_panels' => self::get_query_sub_panels( $query, $num ),
			];
		}

		return $panels;
	}

	/**
	 * Get panels list with a single empty panel only.
	 *
	 * @since 4.2.9
	 *
	 * @return array
	 */
	private static function get_query_panels_empty() {

		return [
			'query_1' => [
				'id'       => 'query_empty',
				'slug'     => 'query_1',
				'title'    => __( 'Query', 'searchwp' ),
				'position' => 10,
				'content'  => '[ ' . __( 'NO QUERIES', 'searchwp' ) . ' ]',
			],
		];
	}

	/**
	 * Get general panels applicable to all queries.
	 *
	 * @since 4.2.9
	 *
	 * @return array
	 */
	private static function get_general_panels() {

		return [
			'errors' => [ // TODO: Make errors a conditional sub-panel for every query panel.
				'title'    => __( 'Errors', 'searchwp' ),
				'id'       => 'errors',
				'position' => 20,
				'content'  => self::get_panel_content__errors(),
			],
			'logs'   => [
				'title'    => __( 'Logs', 'searchwp' ),
				'id'       => 'logs',
				'position' => 30,
				'content'  => self::get_panel_content__logs(),
			],
		];
    }

	/**
	 * Get Query panel's sub-panels.
	 *
	 * @since 4.2.9
	 *
	 * @param Query $query Search query object.
	 * @param int   $num   Query number in the nav menu.
	 *
	 * @return array
	 */
	private static function get_query_sub_panels( $query, $num ) {

		$query_id = $query->get_id();

		$sub_panels = [
			'string' => [
				'title'    => __( 'String', 'searchwp' ),
				'id'       => 'string-' . $query_id,
				'slug'     => 'string-' . $num,
				'position' => 10,
				'content'  => self::get_sub_panel_content__string( $query ),
			],
			'tokens' => [
				'title'    => __( 'Tokens', 'searchwp' ),
				'id'       => 'tokens-' . $query_id,
				'slug'     => 'tokens-' . $num,
				'position' => 20,
				'content'  => self::get_sub_panel_content__tokens( $query ),
			],
		];

		if ( $query->get_debug_data( 'subqueries.phrase.queries' ) ) {
			$sub_panels['phrase'] = [
				'title'    => __( 'Phrase', 'searchwp' ),
				'id'       => 'phrase-' . $query_id,
				'slug'     => 'phrase-' . $num,
				'position' => 30,
				'content'  => self::get_sub_panel_content__phrase( $query ),
			];
		}

		if ( $query->get_debug_data( 'subqueries.and.query' ) ) {
			$sub_panels['and'] = [
				'title'    => __( 'And', 'searchwp' ),
				'id'       => 'and-' . $query_id,
				'slug'     => 'and-' . $num,
				'position' => 40,
				'content'  => self::get_sub_panel_content__and( $query ),
			];
		}

		$sub_panels['final'] = [
			'title'    => __( 'Final', 'searchwp' ),
			'id'       => 'final-' . $query_id,
			'slug'     => 'final-' . $num,
			'position' => 50,
			'content'  => self::get_sub_panel_content__final( $query ),
		];

		$sub_panels['relevance'] = [
			'title'    => __( 'Relevance', 'searchwp' ),
			'id'       => 'relevance-' . $query_id,
			'slug'     => 'relevance-' . $num,
			'position' => 60,
			'content'  => self::get_sub_panel_content__relevance( $query ),
		];

		return $sub_panels;
	}

	/**
	 * Sort top-level panels and sub-panels by nav menu position.
	 *
	 * @since 4.2.9
	 *
	 * @param array $panels Panels list.
	 *
	 * @return array
	 */
	private static function sort_panels( $panels ) {

        // Sort top-level panels.
		$panels = wp_list_sort( $panels, 'position', 'ASC', true );

        // Sort sub-panels for every top-level panel.
		foreach ( $panels as $key => $panel ) {
			if ( ! empty( $panel['sub_panels'] ) && is_array( $panel['sub_panels'] ) ) {
				$panel['sub_panels'] = wp_list_sort( $panel['sub_panels'], 'position', 'ASC', true );
				$panels[ $key ]      = $panel;
			}
		}

        return $panels;
    }

	/**
	 * Get Query panel content.
	 *
	 * @since 4.2.9
	 *
	 * @param Query $query Search query object.
	 *
	 * @return string
	 */
	private static function get_panel_content__query( $query ) {

		$query_args = $query->get_args();
		$caller_id  = empty( $query_args['caller_id'] ) ? null : $query_args['caller_id'];

		$output  = '<div class="swp-boxed">';
		$output .= '<section>';
		$output .= '<h3>Summary</h3>';
		$output .= '<pre>';

		$output .= 'ID:               ' . esc_html( $query->get_id() ) . ( $caller_id ? ' (called by <a href="#swp-query_' . esc_attr( $caller_id ) . '" class="swp-panel-link">' . esc_html( $caller_id ) . '</a>)' : '' ) . "\n";
		$output .= 'Keywords:         ' . esc_html( $query->get_keywords() ) . "\n";
		$output .= 'Engine:           ' . esc_html( $query->get_engine()->get_label() ) . "\n";

		if ( ! empty( $query->get_suggested_search() ) ) {
			$output .= 'Suggested Search: ' . esc_html( $query->get_suggested_search() ) . "\n";
		}

		$output .= 'Tokens:           ' . esc_html( implode( ', ', $query->get_tokens() ) ) . "\n";
		$output .= 'Tokens Limit:     ' . esc_html( $query->get_debug_data( 'query.tokens.limit' ) ) . "\n";
		$output .= 'Found Results:    ' . esc_html( $query->found_results ) . "\n";
		$output .= 'Max Pages:        ' . esc_html( $query->max_num_pages ) . "\n";
		$output .= 'Execution Time:   ' . esc_html( $query->execution_time ) . "\n";

		if ( ! empty( $query->get_errors() ) ) {
			$output .= 'Errors:           YES [' . count( $query->get_errors() ) . "]\n";
		}

		$output .= '</pre>';
		$output .= '<br>';

		$output .= '<h4>Results</h4>';
		$output .= self::get_panel_content__query__raw_results( $query );

		$output .= '</section>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Get Query panel content part: raw search results.
	 *
	 * @since 4.2.9
	 *
	 * @param Query $query Search query object.
	 *
	 * @return string
	 */
	private static function get_panel_content__query__raw_results( $query ) {

		$output      = '';
		$raw_results = $query->get_raw_results();

		if ( empty( $raw_results ) || ! extension_loaded( 'mbstring' ) ) {
			if ( empty( $raw_results ) ) {
				$output .= '[ ' . esc_html__( 'NONE', 'searchwp' ) . " ]\n";
			} else {
				$output .= print_r( $raw_results, true );
			}

			return $output;
		}

		$include_exact_matches = isset( $raw_results[0] ) && isset( $raw_results[0]->searchwp_exacts );

		$debug_data = [];

		foreach ( $raw_results as $result ) {

			$switched_site = self::maybe_switch_to_blog( $result->site );

			$title = $result->source;

			if ( strpos( $result->source, 'post' . SEARCHWP_SEPARATOR ) === 0 ) {
				$title = html_entity_decode( get_the_title( $result->id ) );
			}

			if ( $result->source === 'user' ) {
				$user  = get_userdata( $result->id );
				$title = $user instanceof \WP_User ? html_entity_decode( $user->display_name ) : $title;
			}

			if ( strpos( $result->source, 'taxonomy' . SEARCHWP_SEPARATOR ) === 0 ) {
				$term  = get_term( $result->id );
				$title = $term instanceof \WP_Term ? html_entity_decode( $term->name ) : $title;
			}

			$relevance_data = [
				'Relevance' => $result->relevance,
			];

			$exact_matches_data = $include_exact_matches
				? [
					'Exact Matches' => $result->searchwp_exacts ? 'Yes' : 'No',
				]
				: [];

			$source_data = [
				'ID'     => $result->id,
				'Title'  => $title,
				'Source' => $result->source,
				'Site'   => $result->site,
			];

			$debug_data[] = array_merge( $relevance_data, $exact_matches_data, $source_data );

			if ( $switched_site ) {
				restore_current_blog();
			}
		}

		if ( empty( $debug_data ) ) {
			return $output;
		}

		$output = '<table><tbody>';

		$output .= '<tr>';
		$headers = array_keys( Arr::first( $debug_data ) );
		foreach ( $headers as $header ) {
			$output .= '<th>' . esc_html( $header ) . '</th>';
		}
		$output .= '</tr>';

		foreach ( $debug_data as $data ) {
			$output .= '<tr>';
			foreach ( $data as $data_item ) {
				$output .= '<td>' . esc_html( $data_item ) . '</td>';
			}
			$output .= '</tr>';
		}

		$output .= '</tbody></table>';

		return $output;
	}

	/**
	 * Get Query panel's String sub-panel content.
	 *
	 * @since 4.2.9
	 *
	 * @param Query $query Search query object.
	 *
	 * @return string
	 */
	private static function get_sub_panel_content__string( $query ) {

		$string_before_filter = $query->get_debug_data( 'string.filter.before' );
		$string_after_filter  = $query->get_debug_data( 'string.filter.after' );

		$string_before_synonyms = $query->get_debug_data( 'string.synonyms.before' );
		$string_after_synonyms  = $query->get_debug_data( 'string.synonyms.after' );

		$output = '<div class="swp-boxed">';

		$output .= '<section>';
		$output .= '<h3>String Filter</h3>';
		$output .= '<hr>';
		$output .= '<h4>Before</h4>';
		$output .= '<pre>' . esc_html( $string_before_filter ) . '</pre>';
		$output .= '<hr>';
		$output .= '<h4>After</h4>';
		$output .= '<pre>' . esc_html( $string_after_filter ) . '</pre>';
		$output .= '</section>';

		$output .= '<section>';
		$output .= '<h3>Synonyms</h3>';
		$output .= '<hr>';
		$output .= '<h4>Before</h4>';
		$output .= '<pre>' . esc_html( $string_before_synonyms ) . '</pre>';
		$output .= '<hr>';
		$output .= '<h4>After</h4>';
		$output .= '<pre>' . esc_html( $string_after_synonyms ) . '</pre>';
		$output .= '</section>';

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get Query panel's Tokens sub-panel content.
	 *
	 * @since 4.2.9
	 *
	 * @param Query $query Search query object.
	 *
	 * @return string
	 */
	private static function get_sub_panel_content__tokens( $query ) {

		$partial_before = $query->get_debug_data( 'tokens.partial_matches.before' );
		$partial_after  = $query->get_debug_data( 'tokens.partial_matches.after' );

		$partial_sql     = $query->get_debug_data( 'tokens.partial_matches.query.sql' );
		$partial_time    = $query->get_debug_data( 'tokens.partial_matches.query.time' );
		$partial_results = $query->get_debug_data( 'tokens.partial_matches.query.results' );

		$stemming_before = $query->get_debug_data( 'tokens.stemming.before' );
		$stemming_after  = $query->get_debug_data( 'tokens.stemming.after' );
		$stemming_stems  = $query->get_debug_data( 'tokens.stemming.stems' );

		$output = '<div class="swp-boxed">';

		$output .= '<section>';
		$output .= '<h3>Partial Matches</h3>';
		$output .= '<hr>';
		$output .= '<h4>Before' . ( empty( $partial_before ) ? '' : ' ( ' . count( $partial_before ) . ' )' ) . '</h4>';
		$output .= '<pre>' . ( empty( $partial_before ) ? '[NONE]' : implode( ', ', $partial_before ) ) . '</pre>';
		$output .= '<hr>';
		$output .= '<h4>After' . ( empty( $partial_after ) ? '' : ' ( ' . count( $partial_after ) . ' )' ) . '</h4>';
		$output .= '<pre>' . ( empty( $partial_after ) ? '[NONE]' : implode( ', ', $partial_after ) ) . '</pre>';
		$output .= '<hr>';
		$output .= '<h4>SQL Results' . ( empty( $partial_results ) ? '' : ' ( ' . count( $partial_results ) . ' )' ) . '</h4>';
		$output .= '<pre>' . ( empty( $partial_results ) ? '[NONE]' : implode( ', ', $partial_results ) ) . '</pre>';

		if ( $partial_sql ) {
			$output .= '<hr>';
			$output .= '<h4>SQL ( Time: ' . esc_html( $partial_time ) . ' )</h4>';
			$output .= '<pre>' . ( new SqlFormatter() )->format( apply_filters( 'query', $partial_sql ) ) . '</pre>';
		}

		$output .= '</section>';

		$output .= '<section>';
		$output .= '<h3>Stemming</h3>';
		$output .= '<hr>';
		$output .= '<h4>Before' . ( empty( $stemming_before ) ? '' : ' ( ' . count( $stemming_before ) . ' )' ) . '</h4>';
		$output .= '<pre>' . ( empty( $stemming_before ) ? '[NONE]' : implode( ', ', $stemming_before ) ) . '</pre>';
		$output .= '<hr>';
		$output .= '<h4>After' . ( empty( $stemming_after ) ? '' : ' ( ' . count( $stemming_after ) . ' )' ) . '</h4>';
		$output .= '<pre>' . ( empty( $stemming_after ) ? '[NONE]' : implode( ', ', $stemming_after ) ) . '</pre>';
		$output .= '<hr>';
		$output .= '<h4>Stems' . ( empty( $stemming_stems ) ? '' : ' ( ' . count( $stemming_stems ) . ' )' ) . '</h4>';
		$output .= '<pre>' . ( empty( $stemming_stems ) ? '[NONE]' : implode( ', ', $stemming_stems ) ) . '</pre>';
		$output .= '</section>';

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get Query panel's Phrase sub-panel content.
	 *
	 * @since 4.2.9
	 *
	 * @param Query $query Search query object.
	 *
	 * @return string
	 */
	private static function get_sub_panel_content__phrase( $query ) {

		$subqueries = $query->get_debug_data( 'subqueries.phrase.queries' );

		if ( empty( $subqueries ) ) {
			return "[NO PHRASE LOGIC]\n";
		}

		$times   = $query->get_debug_data( 'subqueries.phrase.times' );
		$results = $query->get_debug_data( 'subqueries.phrase.results' );

		$output = '<div class="swp-boxed">';

		foreach ( $subqueries as $key => $subquery ) {

			$output .= '<section>';
			$output .= '<h3>Source: ' . str_replace( '_', '.', $key ) . '</h3>';
			$output .= '<hr>';

			if ( $key === 'index_query' ) {
				$count   = count( array_merge( ...array_values( $results[ $key ] ) ) );
				$output .= '<h4>Results' . ( empty( $results[ $key ] ) ? '' : ' ( ' . $count . ' )' ) . '</h4>';
				foreach ( $results[ $key ] as $site => $ids ) {
					$output .= '<pre>' . ( empty( $ids ) ? '[NONE]' : 'Site ' . $site . ' IDs: ' . implode( ', ', $ids ) ) . '</pre>';
				}
			} else {
				$output .= '<h4>Results' . ( empty( $results[ $key ] ) ? '' : ' ( ' . count( $results[ $key ] ) . ' )' ) . '</h4>';
				$output .= '<pre>' . ( empty( $results[ $key ] ) ? '[NONE]' : 'IDs: ' . implode( ', ', $results[ $key ] ) ) . '</pre>';
			}
			$output .= '<hr>';
			$output .= '<h4>SQL ( Time: ' . esc_html( $times[ $key ] ) . ' )</h4>';
			$output .= '<pre>' . ( new SqlFormatter() )->format( apply_filters( 'query', $subquery ) ) . '</pre>';
			$output .= '</section>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get Query panel's And sub-panel content.
	 *
	 * @since 4.2.9
	 *
	 * @param Query $query Search query object.
	 *
	 * @return string
	 */
	private static function get_sub_panel_content__and( $query ) {

		$output  = '<div class="swp-boxed">';

		$output .= '<section>';
		$output .= self::get_sub_panel_content__and__results( $query );
		$output .= '<hr>';
		$output .= '<h4>Token Groups</h4>';
		$output .= '<pre>' . self::get_sub_panel_content__and__token_groups( $query ) . '</pre>';
		$output .= '<hr>';
		$output .= self::get_sub_panel_content__and__sql( $query );
		$output .= '</section>';

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get Query panel's And sub-panel content part: results.
	 *
	 * @since 4.2.9
	 *
	 * @param Query $query Search query object.
	 *
	 * @return string
	 */
	private static function get_sub_panel_content__and__results( $query ) {

		$results = $query->get_debug_data( 'subqueries.and.results' );

		$output  = '<h4>Results' . ( empty( $results ) ? '' : ' ( ' . count( $results ) . ' )' ) . '</h4>';
		$output .= '<pre>' . ( empty( $results ) ? '[NONE]' : 'IDs: ' . implode( ', ', $results ) ) . '</pre>';

		return $output;
	}

	/**
	 * Get Query panel's And sub-panel content part: token groups.
	 *
	 * @since 4.2.9
	 *
	 * @param Query $query Search query object.
	 *
	 * @return string
	 */
	private static function get_sub_panel_content__and__token_groups( $query ) {

		$token_groups = $query->get_debug_data( 'tokengroups.and' );

		if ( empty( $token_groups ) ) {
			return "[NO TOKEN GROUPS]\n";
		}

		$output = '<table><tbody>';

		foreach ( $token_groups as $token => $ids ) {
			$output .= '<tr><td>' . esc_html( $token ) . '</td><td>' . esc_html( implode( ', ', $ids ) ) . '</td></tr>';
		}

		$output .= '</tbody></table>';

		return $output;
	}

	/**
	 * Get Query panel's And sub-panel content part: SQL.
	 *
	 * @since 4.2.9
	 *
	 * @param Query $query Search query object.
	 *
	 * @return string
	 */
	private static function get_sub_panel_content__and__sql( $query ) {

		$and_query = $query->get_debug_data( 'subqueries.and.query' );

		if ( empty( $and_query ) ) {
			return '<pre>[NO AND LOGIC]</pre>';
		}

		$and_time = $query->get_debug_data( 'subqueries.and.time' );

		$output  = '<h4>SQL ( Time: ' . esc_html( $and_time ) . ' )</h4>';
		$output .= '<pre>' . ( new SqlFormatter() )->format( $and_query ) . '</pre>';

		return $output;
	}

	/**
	 * Get Query panel's Final sub-panel content.
	 *
	 * @since 4.2.9
	 *
	 * @param Query $query Search query object.
	 *
	 * @return string
	 */
	private static function get_sub_panel_content__final( $query ) {

		$results = $query->get_raw_results();

		$output = '<div class="swp-boxed">';

		$output .= '<section>';
		$output .= '<h4>Results' . ( empty( $results ) ? '' : ' ( ' . count( $results ) . ' )' ) . '</h4>';
		$output .= '<pre>' . ( empty( $results ) ? '[NONE]' : 'IDs: ' . implode( ', ', wp_list_pluck( $results, 'id' ) ) ) . '</pre>';
		$output .= '<hr>';
		$output .= '<h4>SQL ( Time: ' . esc_html( $query->query_time ) . ' )</h4>';
		$output .= '<pre>' . ( new SqlFormatter() )->format( $query->get_sql() ) . '</pre>';
		$output .= '</section>';

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get Query panel's Relevance sub-panel content.
	 *
	 * @since 4.3.16
	 *
	 * @param Query $query Search query object.
	 *
	 * @return string
	 */
	private static function get_sub_panel_content__relevance( $query ) {

		$output  = '<div class="swp-boxed">';
		$output .= '<section>';

		$raw_results = $query->get_raw_results();

		if ( empty( $raw_results ) || ! extension_loaded( 'mbstring' ) ) {
			if ( empty( $raw_results ) ) {
				$output .= '[ ' . esc_html__( 'NONE', 'searchwp' ) . " ]\n";
			} else {
				$output .= print_r( $raw_results, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}

			return $output;
		}

		$relevance_data = $query->get_debug_data( 'query.relevance' );

		self::output_sub_panel_content__relevance_results( $raw_results, $relevance_data, $query, $output );

		$output .= '</section>';

		$output .= '</div>';

		return $output;
	}

	/**
	 * Output Relevance sub-panel content.
	 *
	 * @since 4.3.16
	 *
	 * @param array  $raw_results    Raw search results.
	 * @param array  $relevance_data Relevance data.
	 * @param Query  $query          Search query object.
	 * @param string $output         Output string.
	 */
	private static function output_sub_panel_content__relevance_results( $raw_results, $relevance_data, $query, &$output ) {

		foreach ( $raw_results as $result_index => $result ) {

			$switched_site = self::maybe_switch_to_blog( $result->site );

			$output .= '<h4>Result #' . ( $result_index + 1 ) . '</h4>';

			$result_data = self::get_relevance_entry_data( $result );

			$output .= self::output_relevance_result_entry_table( $result_data );

			$debug_data = self::get_relevance_occurrences_data( $result, $relevance_data, $query );

			if ( empty( $debug_data ) ) {
				continue;
			}

			$output .= self::output_relevance_occurrences_table( $debug_data );

			if ( $switched_site ) {
				restore_current_blog();
			}
		}
	}

	/**
	 * Get Relevance sub-panel results data.
	 *
	 * @since 4.3.16
	 *
	 * @param object $result Search result object.
	 *
	 * @return array
	 */
	private static function get_relevance_entry_data( $result ) {

		$title = $result->source;

		if ( strpos( $result->source, 'post' . SEARCHWP_SEPARATOR ) === 0 ) {
			$title = html_entity_decode( get_the_title( $result->id ) );
		}

		if ( $result->source === 'user' ) {
			$user  = get_userdata( $result->id );
			$title = $user instanceof \WP_User ? html_entity_decode( $user->display_name ) : $title;
		}

		if ( strpos( $result->source, 'taxonomy' . SEARCHWP_SEPARATOR ) === 0 ) {
			$term  = get_term( $result->id );
			$title = $term instanceof \WP_Term ? html_entity_decode( $term->name ) : $title;
		}

		return [
			'Relevance' => $result->relevance,
			'ID'        => $result->id,
			'Title'     => $title,
			'Source'    => $result->source,
			'Site'      => $result->site,
		];
	}

	/**
	 * Get Relevance sub-panel result table.
	 *
	 * @since 4.3.16
	 *
	 * @param array $result_data Result data.
	 *
	 * @return string
	 */
	private static function output_relevance_result_entry_table( $result_data ) {

		$output = '<table><tbody>';

		$output .= '<tr>';
		$headers = array_keys( $result_data );
		foreach ( $headers as $header ) {
			$output .= '<th><b>' . esc_html( ucfirst( $header ) ) . '</b></th>';
		}
		$output .= '</tr>';

		$output .= '<tr>';
		foreach ( $result_data as $result_data_item ) {
			$output .= '<td>' . esc_html( $result_data_item ) . '</td>';
		}
		$output .= '</tr>';

		$output .= '</tbody></table><br>';

		return $output;
	}

	/**
	 * Get Relevance sub-panel debug data.
	 *
	 * @since 4.3.16
	 *
	 * @param object $result         Search result object.
	 * @param array  $relevance_data Relevance data.
	 * @param Query  $query          Search query object.
	 *
	 * @return array
	 */
	private static function get_relevance_occurrences_data( $result, $relevance_data, $query ) {

		// Get the engine from the query object.
		$engine  = $query->get_engine();
		$sources = $engine->get_sources();

		$debug_data = [];

		foreach ( $relevance_data as $relevance_item ) {

			if ( $result->id !== $relevance_item['id'] ) {
				continue;
			}

			$source = $sources[ $relevance_item['source'] ];

			unset( $relevance_item['id'] );
			unset( $relevance_item['source'] );

			$attributes = $source->get_attributes();
			$attribute  = $attributes[ $relevance_item['attribute'] ] ?? null;
			$weight     = 0;

			// If the attribute is a meta-field and the settings is "Any Meta Key" it would not match with the relevance item.
			// So we need to get the correct attribute.
			if ( ! $attribute && strpos( $relevance_item['attribute'], 'meta.' ) === 0 ) {
				$attribute = $attributes['meta'];
			}

			// Get the attribute weight as set in the engine.
			$weight = is_a( $attribute, 'SearchWP\Attribute' ) ? $attribute->get_settings() : '';

			// If the weight is an array, get the first value.
			if ( is_array( $weight ) ) {
				$weight = reset( $weight );
			}

			$weight = is_numeric( $weight ) ? $weight : '?';

			// Order the relevance item attributes.
			$ordered_atts = [
				'attribute'   => $relevance_item['attribute'],
				'occurrences' => $relevance_item['occurrences'],
				'weight'      => $weight,
				'relevance'   => $relevance_item['relevance'],
				'token'       => $relevance_item['token'],
				'token_id'    => $relevance_item['token_id'],
			];

			// Get the remaining attributes that were not ordered.
			$remaining_atts = array_diff_key( $relevance_item, $ordered_atts );

			// Add the ordered and remaining attributes to the debug data.
			$debug_data[] = array_merge( $ordered_atts, $remaining_atts );
		}

		return $debug_data;
	}

	/**
	 * Get Relevance sub-panel debug table.
	 *
	 * @since 4.3.16
	 *
	 * @param array $debug_data Debug data.
	 *
	 * @return string
	 */
	private static function output_relevance_occurrences_table( $debug_data ) {

		$relevance = array_column( $debug_data, 'relevance' );
		array_multisort( $relevance, SORT_DESC, $debug_data );

		$output = '<table><tbody>';

		$output .= '<tr>';
		$headers = array_keys( Arr::first( $debug_data ) );
		foreach ( $headers as $header ) {
			$output .= '<th><b>' . esc_html( ucfirst( str_replace( '_', ' ', $header ) ) ) . '</b></th>';
		}
		$output .= '</tr>';

		foreach ( $debug_data as $data ) {
			$output .= '<tr>';
			foreach ( $data as $data_item ) {
				$output .= '<td>' . esc_html( $data_item ) . '</td>';
			}
			$output .= '</tr>';
		}

		$output .= '</tbody></table><br><hr><br>';

		return $output;
	}

	/**
	 * Get Errors panel content.
	 *
	 * @since 4.2.9
	 *
	 * @return string
	 */
	private static function get_panel_content__errors() {

		$queries = searchwp()->get( Watcher::class )->get_queries();

		if ( empty( $queries ) ) {
			return '[NO QUERIES]';
		}

		$output = '';

		$i = 1;
		foreach ( $queries as $query ) {
			$output .= 'Query ' . $i . "\n";
			$output .= "===============\n";

			$output .= 'Keywords:         ' . esc_html( $query->get_keywords() ) . "\n";
			$output .= 'Engine:           ' . esc_html( $query->get_engine()->get_label() ) . "\n\n";

			$errors = $query->get_errors();

			if ( ! empty( $errors ) ) {
				$output .= "Errors:\n";
				$output .= "===============\n";
				foreach ( $errors as $error ) {
					$output .= print_r( is_wp_error( $error ) ? $error->get_error_messages() : $error, true ) . "\n";
				}
			} else {
				$output .= '[NO ERRORS]';
			}

			$output .= "\n\n";

			$i ++;
		}

		return $output;
	}

	/**
	 * Get Logs panel content.
	 *
	 * @since 4.2.9
	 *
	 * @return string
	 */
	private static function get_panel_content__logs() {

		$logs = searchwp()->get( Watcher::class )->get_logs();

		if ( empty( $logs ) ) {
			return '[NO LOGS]';
		}

		return implode( "\n", array_map( 'esc_html', $logs ) );
	}

	/**
	 * Switch to a different site if needed.
	 *
	 * @since 4.3.16
	 *
	 * @param int $site_id Site ID.
	 *
	 * @return bool
	 */
	private static function maybe_switch_to_blog( $site_id ) {

		$current_site = get_current_blog_id();

		if ( $current_site !== absint( $site_id ) ) {
			switch_to_blog( $site_id );

			return true;
		}

		return false;
	}
}
