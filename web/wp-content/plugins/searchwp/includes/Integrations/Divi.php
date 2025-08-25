<?php
/**
 * SearchWP Divi Integration.
 *
 * @package SearchWP
 * @author  Jon Christopher
 */

namespace SearchWP\Integrations;

/**
 * Class Divi is responsible for customizing SearchWP's Native implementation to work with Divi queries.
 *
 * @since 4.1.5
 */
class Divi extends PageBuilder {

	/**
	 * Name used for canonical reference to Integration.
	 *
	 * @since 4.1.5
	 *
	 * @var   string
	 */
	protected $name = 'divi';

	/**
	 * Constructor.
	 *
	 * @since 4.1.5
	 */
	public function __construct() {

		// Divi needs this to run every time.
		$this->run_once = false;

		$this->modify_native_behavior();
	}

	/**
	 * Prevent \SearchWP\Native from being strict to is_main_query().
	 *
	 * @since 4.1.8
	 *
	 * @param boolean   $strict Whether \SearchWP\Native should be strict.
	 * @param \WP_Query $query  The WP_Query instance.
	 *
	 * @return boolean
	 */
	public function strict( $strict, $query ) {

		$strict = false;

		// Prevent this from happening again.
		if ( $this->run_once ) {
			remove_filter( 'searchwp\native\strict', [ $this, 'strict' ], 990 );
		}

		return $strict;
	}
}
