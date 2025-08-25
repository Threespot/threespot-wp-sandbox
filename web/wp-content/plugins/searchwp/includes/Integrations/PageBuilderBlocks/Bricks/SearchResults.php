<?php

namespace SearchWP\Integrations\PageBuilderBlocks\Bricks;

use SearchWP\Integrations\PageBuilderBlocks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SearchFormElement handles the SearchWP form widget for Bricks.
 *
 * @since 4.5.0
 */
class SearchResultsElement extends \Bricks\Element {

	/**
	 * Element Category.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public $category = 'SearchWP';

	/**
	 * Element Name.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public $name = 'searchwp_search_results';

	/**
	 * Element Icon.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public $icon = 'ti-layout-list-thumb';

	/**
	 * Whether the element is nestable.
	 *
	 * @since 4.5.0
	 *
	 * @var array
	 */
	public $nestable = false;

	/**
	 * Get the element label.
	 *
	 * @since 4.5.0
	 *
	 * @return string The label for the element.
	 */
	public function get_label() {

		return PageBuilderBlocks::get_text( 'search_results_label' );
	}

	/**
	 * Get the element keywords.
	 *
	 * @since 4.5.0
	 *
	 * @return array Array of keywords for the element.
	 */
	public function get_keywords() {

		return [
			'searchwp',
			'search',
			'results',
			'template',
		];
	}

	/**
	 * Set controls for the element.
	 *
	 * @since 4.5.0
	 */
	public function set_controls() {

		$template_options = $this->get_template_options();

		$this->controls['template_id'] = [
			'tab'         => 'content',
			'label'       => PageBuilderBlocks::get_text( 'results_template_label' ),
			'type'        => 'select',
			'options'     => $template_options,
			'inline'      => true,
			'placeholder' => PageBuilderBlocks::get_text( 'set_by_search_form' ),
			'multiple'    => false,
			'searchable'  => true,
			'clearable'   => true,
			'default'     => 0,
		];

		$engine_options = $this->get_engine_options();

		$this->controls['engine'] = [
			'tab'         => 'content',
			'label'       => PageBuilderBlocks::get_text( 'search_engine_label' ),
			'type'        => 'select',
			'options'     => $engine_options,
			'inline'      => true,
			'placeholder' => PageBuilderBlocks::get_text( 'set_by_search_form' ),
			'multiple'    => false,
			'searchable'  => true,
			'clearable'   => true,
			'default'     => 0,
		];

		$this->controls['notice'] = [
			'tab'     => 'content',
			'content' => PageBuilderBlocks::get_text( 'search_settings_notice' ),
			'type'    => 'info',
		];
	}

	/**
	 * Get the template options for the select field.
	 *
	 * @since 4.5.0
	 *
	 * @return array Template options.
	 */
	public function get_template_options() {

		$templates = \SearchWP\Templates\Storage::get_templates();

		if ( empty( $templates ) ) {
			return [];
		}

		$options = [];

		foreach ( $templates as $id => $template ) {
			$options[ $id ] = isset( $template['title'] ) ? htmlspecialchars_decode( $template['title'], ENT_QUOTES ) : '';
		}

		return $options;
	}

	/**
	 * Get the engine options for the select field.
	 *
	 * @since 4.5.0
	 *
	 * @return array Engine options.
	 */
	public function get_engine_options() {

		$engines = \SearchWP\Settings::get_engines();

		if ( empty( $engines ) ) {
			return [];
		}

		$options = [];

		foreach ( $engines as $engine ) {
			$options[ $engine->get_name() ] = $engine->get_label();
		}

		return $options;
	}

	/**
	 * Render the element output.
	 *
	 * @since 4.5.0
	 *
	 * @return void Outputs the search form or a logo placeholder.
	 */
	public function render() {

		if ( ! $this->is_frontend ) {
			echo PageBuilderBlocks::get_component_preview( PageBuilderBlocks::get_text( 'search_results_message' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			return;
		}

		$settings    = $this->settings;
		$template_id = absint( $settings['template_id'] ?? 0 );
		$engine      = isset( $settings['engine'] ) ? sanitize_text_field( $settings['engine'] ) : '';

		// The wrapping DIV is necessary for Bricks to apply the correct styles.
		echo '<div>';

		// Output the form using a direct render method rather than a shortcode for the frontend.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo \SearchWP\Templates\Frontend::render(
			[
				'id'     => $template_id,
				'engine' => $engine,
			]
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '</div>';
	}
}
