<?php

namespace SearchWP\Support;

/**
 * String manipulation class.
 *
 * Only general purpose actions are allowed in this class.
 * All actions specific to SearchWP operations should be placed elsewhere.
 *
 * @since 4.2.3
 */
class Str {

	/**
	 * Convert the given string to lower-case.
	 *
	 * @since 4.2.3
	 *
	 * @param string $string Input string.
	 *
	 * @return string
	 */
	public static function lower( string $string ): string {

		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $string ) : strtolower( $string );
	}

	/**
	 * Remove any occurrence of the given string in the subject.
	 *
	 * @since 4.2.4
	 *
	 * @param string|array<string> $search      String(s) to remove.
	 * @param string               $subject     Input string.
	 * @param bool                 $ignore_case Ignore string case.
	 *
	 * @return string
	 */
	public static function remove( $search, string $subject, bool $ignore_case = false ): string {

		if ( $ignore_case ) {
			return str_ireplace( $search, '', $subject );
		}

		return str_replace( $search, '', $subject );
	}

	/**
	 * Remove any occurrence of the single and double quotes in the subject.
	 *
	 * @since 4.2.4
	 *
	 * @param string $string Input string.
	 *
	 * @return string
	 */
	public static function remove_quotes( string $string ): string {

		return self::remove( [ '"', "'" ], $string );
	}

	/**
	 * Find the position of the first occurrence of a substring in a string.
	 *
	 * @since 4.2.4
	 *
	 * @param string $haystack The input string.
	 * @param string $needle   The substring to look for.
	 * @param int    $offset   The search offset.
	 *
	 * @return int|false
	 */
	public static function strpos( string $haystack, string $needle, int $offset = 0 ) {

		return function_exists( 'mb_strpos' ) ? mb_strpos( $haystack, $needle, $offset ) : strpos( $haystack, $needle, $offset );
	}

	/**
	 * Find the position of the last occurrence of a substring in a string.
	 *
	 * @since 4.3.17
	 *
	 * @param string   $haystack The input string.
	 * @param int      $start    The start position.
	 * @param int|null $length   Maximum number of characters to use from string.
	 *
	 * @return false/string
	 */
	public static function substr( string $haystack, int $start, int $length = null ) {

		return function_exists( 'mb_substr' ) ? mb_substr( $haystack, $start, $length ) : substr( $haystack, $start, $length );
	}

	/**
	 * Cut out a portion of a string.
	 *
	 * @since 4.3.17
	 *
	 * @param string   $haystack The input string.
	 * @param int      $start    The start position.
	 * @param int|null $length   Maximum number of characters to use from string.
	 *
	 * @return false|string
	 */
	public static function strcut( string $haystack, int $start, int $length = null ) {

		return function_exists( 'mb_strcut' ) ? mb_strcut( $haystack, $start, $length ) : substr( $haystack, $start, $length );
	}

	/**
	 * Get the length of a string.
	 *
	 * @since 4.3.17
	 *
	 * @param string $haystack The input string.
	 *
	 * @return false|int
	 */
	public static function strlen( string $haystack ) {

		return function_exists( 'mb_strlen' ) ? mb_strlen( $haystack ) : strlen( $haystack );
	}

	/**
	 * Find the position of the first occurrence of a case-insensitive substring in a string.
	 *
	 * @since 4.3.17
	 *
	 * @param string $haystack The input string.
	 * @param string $needle   The substring to look for.
	 * @param int    $offset   The search offset.
	 *
	 * @return false|int
	 */
	public static function stripos( string $haystack, string $needle, int $offset = 0 ) {

		return function_exists( 'mb_stripos' ) ? mb_stripos( $haystack, $needle, $offset ) : stripos( $haystack, $needle, $offset );
	}

	/**
	 * Determine if a given string contains a given substring.
	 *
	 * @since 4.2.4
	 *
	 * @param string          $haystack    The string to search in.
	 * @param string|string[] $needles     The substring(s) to look for.
	 * @param bool            $ignore_case Ignore string case.
	 *
	 * @return bool
	 */
	public static function contains( string $haystack, $needles, bool $ignore_case = false ): bool {

		if ( $ignore_case ) {
			$haystack = self::lower( $haystack );
			$needles  = array_map( [ __CLASS__, 'lower' ], (array) $needles );
		}

		foreach ( (array) $needles as $needle ) {
			if ( $needle !== '' && self::strpos( $haystack, $needle ) !== false ) {
				return true;
			}
		}

		return false;
	}
}
