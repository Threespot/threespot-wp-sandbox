<?php

namespace SearchWP\Integrations\PageBuilderBlocks\BeaverBuilder;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init handles Beaver Builder integration.
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

				// Load module files.
				require_once __DIR__ . '/modules/SearchFormsModule/SearchFormsModule.php';
				require_once __DIR__ . '/modules/SearchTemplatesModule/SearchTemplatesModule.php';
			}
		);
	}
}
