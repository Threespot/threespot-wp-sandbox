<?php

namespace SearchWP\Integrations\PageBuilderBlocks\Bricks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init handles Breakdance integration.
 *
 * @since 4.5.0
 */
class Init {

	/**
	 * Class constructor.
	 *
	 * @since 4.5.0
	 */
	public function __construct() {

		add_action(
			'searchwp\loaded',
			function () {
				// Load element files.
				\Bricks\Elements::register_element( __DIR__ . '/SearchForms.php' );
				\Bricks\Elements::register_element( __DIR__ . '/SearchResults.php' );
			}
		);
	}
}
