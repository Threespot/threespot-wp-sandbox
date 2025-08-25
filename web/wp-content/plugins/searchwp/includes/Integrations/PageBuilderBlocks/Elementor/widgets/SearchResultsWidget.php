<?php

namespace SearchWP\Integrations\PageBuilderBlocks\Elementor\Widgets;

use SearchWP\Integrations\PageBuilderBlocks;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class SearchResultsWidget handles the SearchWP results template widget for Elementor.
 *
 * @since 4.5.0
 */
class SearchResultsWidget extends \Elementor\Widget_Base {

	/**
	 * Get the name of the widget.
	 *
	 * @since 4.5.0
	 *
	 * @return string Widget name.
	 */
	public function get_name() {

		return 'searchwp_search_results';
	}

	/**
	 * Get the title of the widget.
	 *
	 * @since 4.5.0
	 *
	 * @return string Widget title.
	 */
	public function get_title() {

		return PageBuilderBlocks::get_text( 'search_results_label' );
	}

	/**
	 * Get the icon of the widget.
	 *
	 * @since 4.5.0
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {

		return 'eicon-search-results';
	}

	/**
	 * Get the categories of the widget.
	 *
	 * @since 4.5.0
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {

		return [ 'searchwp' ];
	}

	/**
	 * Get the keywords of the widget.
	 *
	 * @since 4.5.0
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {

		return [ 'searchwp', 'search', 'results', 'template' ];
	}

	/**
	 * Register the widget controls.
	 *
	 * @since 4.5.0
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => PageBuilderBlocks::get_text( 'results_settings_label' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$template_options = PageBuilderBlocks::get_template_options();

		$this->add_control(
			'template_id',
			[
				'label'   => PageBuilderBlocks::get_text( 'template_label' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $template_options,
			]
		);

		$engine_options = PageBuilderBlocks::get_search_engines_options();

		$this->add_control(
			'engine',
			[
				'label'   => PageBuilderBlocks::get_text( 'search_engine_label' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $engine_options,
			]
		);

		$this->add_control(
			'custom_panel_alert',
			[
				'type'       => \Elementor\Controls_Manager::ALERT,
				'alert_type' => 'warning',
				'heading'    => '',
				'content'    => PageBuilderBlocks::get_text( 'search_settings_notice' ),
			]
		);

		$this->end_controls_section();
	}



	/**
	 * Render the widget output on the frontend.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();

		// Output the form using a direct render method rather than shortcode for the frontend.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo \SearchWP\Templates\Frontend::render(
			[
				'id'     => $settings['template_id'],
				'engine' => $settings['engine'],
			]
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the widget output in the editor.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	protected function content_template() {

		echo PageBuilderBlocks::get_component_preview( PageBuilderBlocks::get_text( 'search_results_message' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
