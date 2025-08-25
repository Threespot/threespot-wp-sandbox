<?php

namespace SearchWP\Integrations\PageBuilderBlocks\Divi;

use SearchWP\Integrations\PageBuilderBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use ET_Builder_Module;

/**
 * Class SearchWPResultsModule.
 *
 * @since 4.5.0
 */
class SearchWPResultsModule extends ET_Builder_Module {

	/**
	 * Module slug.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public $slug = 'searchwp_templates_selector';

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

		$this->name = PageBuilderBlocks::get_text( 'search_results_label' );
	}

	/**
	 * Get list of settings.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public function get_fields(): array {

		$templates = \SearchWP\Templates\Storage::get_templates();

		$engines = \SearchWP\Settings::get_engines();

		$fields = [];

		if ( ! empty( $templates ) ) {
			$options = [];

			foreach ( $templates as $id => $template ) {
				$options[ $id ] = isset( $template['title'] ) ? htmlspecialchars_decode( $template['title'], ENT_QUOTES ) : '';
			}

			$options[0] = PageBuilderBlocks::get_text( 'set_by_search_form' );

			$fields['template_id'] = [
				'label'           => PageBuilderBlocks::get_text( 'results_template_label' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'toggle_slug'     => 'main_content',
				'options'         => $options,
				'default'         => 0,
			];
		}

		if ( ! empty( $engines ) ) {
			$options = [];

			foreach ( $engines as $engine ) {
				$options[ $engine->get_name() ] = $engine->get_label();
			}

			$options[0] = PageBuilderBlocks::get_text( 'set_by_search_form' );

			$fields['engine'] = [
				'label'           => PageBuilderBlocks::get_text( 'search_engine_label' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'toggle_slug'     => 'main_content',
				'options'         => $options,
				'default'         => 0,
			];
		}

		return $fields;
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

		$shortcode_atts = [];

		if ( ! empty( $this->props['template_id'] ) ) {
			$shortcode_atts = [
				'id' => absint( $this->props['template_id'] ),
			];
		}

		if ( ! empty( $this->props['engine'] ) ) {
			$shortcode_atts['engine'] = sanitize_text_field( $this->props['engine'] );
		}

		$shortcode = '[searchwp_template';

		foreach ( $shortcode_atts as $key => $value ) {
			$shortcode .= ' ' . $key . '="' . $value . '"';
		}

		$shortcode .= ']';

		return do_shortcode( $shortcode );
	}
}
