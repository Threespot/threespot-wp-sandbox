<?php

namespace SearchWP\Integrations\PageBuilderBlocks\Divi;

use SearchWP\Integrations\PageBuilderBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use ET_Builder_Module;

/**
 * Class SearchWPFormsModule.
 *
 * @since 4.5.0
 */
class SearchWPFormsModule extends ET_Builder_Module {

	/**
	 * Module slug.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public $slug = 'searchwp_forms_selector';

	/**
	 * VB support.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public $vb_support = 'on';

	/**
	 * Init module.
	 *
	 * @since 4.5.0
	 */
	public function init() {

		$this->name = PageBuilderBlocks::get_text( 'search_form_title' );
	}

	/**
	 * Get the list of settings.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public function get_fields(): array {

		$form_options = PageBuilderBlocks::get_form_options();

		// When passing the array of forms [id => title], Divi, for unknown reasons, uses the form title as the ID.
		// The only solution is to create the array in a not numerical order.
		// We therefore move the first item to the end of the array.
		$first_item = $form_options[0];
		unset( $form_options[0] );
		$form_options[0] = $first_item;

		return [
			'form_id' => [
				'label'           => PageBuilderBlocks::get_text( 'form_label' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'toggle_slug'     => 'fields',
				'options'         => $form_options,
				'default'         => 0,
			],
		];
	}


	/**
	 * Disable advanced fields configuration.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public function get_advanced_fields_config() {

		return [
			'link_options' => false,
			'text'         => false,
			'background'   => false,
			'borders'      => false,
			'box_shadow'   => false,
			'button'       => false,
			'filters'      => false,
			'fonts'        => false,
		];
	}

	/**
	 * Render module on the frontend.
	 *
	 * @since 4.5.0
	 *
	 * @param array  $attrs       List of unprocessed attributes.
	 * @param string $content     Content being processed.
	 * @param string $render_slug Slug of module that is used for rendering output.
	 *
	 * @return string
	 */
	public function render( $attrs, $content = null, $render_slug = '' ) {

		if ( empty( $this->props['form_id'] ) ) {
			return '';
		}

		return do_shortcode(
			sprintf(
				'[searchwp_form id="%1$s"]',
				absint( $this->props['form_id'] )
			)
		);
	}
}
