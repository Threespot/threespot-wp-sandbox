<?php

namespace SearchWP\Integrations\PageBuilderBlocks\Elementor;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init handles Elementor integration.
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

		// Add action to register the Elementor widgets.
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );

		// Add action to register a custom category.
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_categories' ] );
	}

	/**
	 * Register Elementor widgets.
	 *
	 * @since 4.5.0
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 *
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {

		if ( $widgets_manager !== null ) {
			require_once __DIR__ . '/widgets/SearchFormWidget.php';
			require_once __DIR__ . '/widgets/SearchResultsWidget.php';

			$widgets_manager->register( new \SearchWP\Integrations\PageBuilderBlocks\Elementor\Widgets\SearchFormWidget() );
			$widgets_manager->register( new \SearchWP\Integrations\PageBuilderBlocks\Elementor\Widgets\SearchResultsWidget() );
		}
	}

	/**
	 * Register custom Elementor categories.
	 *
	 * @since 4.5.0
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 *
	 * @return void
	 */
	public function register_categories( $elements_manager ) {

		$elements_manager->add_category(
			'searchwp',
			[
				'title' => esc_html__( 'SearchWP', 'searchwp' ),
				'icon'  => 'fa fa-search',
			]
		);
	}
}
