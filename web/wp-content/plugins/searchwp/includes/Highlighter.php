<?php

/**
 * SearchWP Highlighter.
 *
 * @package SearchWP
 * @author  Jon Christopher
 */

namespace SearchWP;

/**
 * Class Highlighter wraps search query matches in markup.
 *
 * @since 4.0
 */
class Highlighter {

	/**
	 * Wraps search query matches in a <mark> tag.
	 *
	 * @since 4.0
	 *
	 * @param string          $haystack The full text to highlight within.
	 * @param string|string[] $needle   The search query.
	 *
	 * @return string
	 */
	public static function apply( $haystack, $needle ) {

		/**
		 * Filters whether to highlight partial matches.
		 *
		 * @since 4.0
		 *
		 * @param bool $partial Whether to highlight partial matches.
		 */
		$partial   = apply_filters( 'searchwp\highlighter\partial_matches', Settings::get( 'partial_matches' ) );
		$highlight = '<mark class="searchwp-highlight">$1</mark>';

		/**
		 * Filters whether to perform a case-insensitive search.
		 *
		 * @since 4.0
		 *
		 * @param bool $case_insensitive Whether to perform a case-insensitive search.
		 */
		$case_insensitive = apply_filters( 'searchwp\highlighter\case_insensitive', true )
			? 'i'
			: '';

		$needles_exact_matches   = Utils::map_needles_for_regex( (array) $needle, false );
		$needles_partial_matches = $partial ? Utils::map_needles_for_regex( (array) $needle, true ) : [];
		$needles                 = array_merge( $needles_exact_matches, $needles_partial_matches );
		$pattern                 = sprintf( Utils::$word_match_pattern, implode( '|', $needles ) ) . $case_insensitive;

		// Apply highlighting.
		$highlit = preg_replace( $pattern, $highlight, $haystack );

		// Remove separation between back-to-back matches to make one continuous match.
		$highlit = preg_replace( '/<\/mark>(\s*)<mark class="searchwp-highlight">/mu', '$1', $highlit );

		return $highlit;
	}
}
