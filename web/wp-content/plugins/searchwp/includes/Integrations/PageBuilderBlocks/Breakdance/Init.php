<?php

namespace SearchWP\Integrations\PageBuilderBlocks\Breakdance;

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
				require_once __DIR__ . '/SearchForms/element.php';
				require_once __DIR__ . '/SearchResults/element.php';
			}
		);
	}
}
