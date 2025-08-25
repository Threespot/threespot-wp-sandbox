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
class SearchFormElement extends \Bricks\Element {

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
	public $name = 'searchwp_search_form';

	/**
	 * Element Icon.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public $icon = 'ti-search';

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

		return PageBuilderBlocks::get_text( 'form_label' );
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
			'form',
		];
	}

	/**
	 * Set controls for the element.
	 *
	 * @since 4.5.0
	 */
	public function set_controls() {

		$options = PageBuilderBlocks::get_form_options();

		// When passing the array of forms [id => title], Bricks, for unknown reasons, uses the form title as the ID.
		// The only solution is to create the array in a not numerical order.
		// We therefore move the first item to the end of the array.
		$first_item = $options[0];
		unset( $options[0] );
		$options[0] = $first_item;

		$this->controls['form_id'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Search form', 'searchwp' ),
			'type'        => 'select',
			'options'     => $options,
			'inline'      => true,
			'placeholder' => esc_html__( 'Select Form', 'searchwp' ),
			'multiple'    => false,
			'searchable'  => true,
			'clearable'   => true,
			'default'     => 0,
		];
	}

	/**
	 * Render the element output.
	 *
	 * @since 4.5.0
	 *
	 * @return void Outputs the search form or a logo placeholder.
	 */
	public function render() {

		$settings = $this->settings;

		$form_id = absint( $settings['form_id'] ?? 0 );
		$form    = \SearchWP\Forms\Storage::get( $form_id );

		if ( empty( $form ) ) {
			if ( ! $this->is_frontend ) {
				echo PageBuilderBlocks::get_component_preview( PageBuilderBlocks::get_text( 'select_form_from_settings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			return;
		}

		// The wrapping DIV is necessary for Bricks to apply the correct styles.
		echo '<div>';

		// Output the form using a direct render method rather than shortcode for the frontend.
		echo \SearchWP\Forms\Frontend::render( [ 'id' => $form_id ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '</div>';
	}
}
