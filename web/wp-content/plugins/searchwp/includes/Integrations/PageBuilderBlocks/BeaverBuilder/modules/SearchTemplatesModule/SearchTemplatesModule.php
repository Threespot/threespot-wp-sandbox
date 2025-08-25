<?php

namespace SearchWP\Integrations\PageBuilderBlocks\BeaverBuilder\modules\SearchWPTemplatesModule;

use SearchWP\Integrations\PageBuilderBlocks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SearchWPTemplatesModule handles SearchWP templates integration with Beaver Builder.
 *
 * @since 4.5.0
 */
class SearchWPTemplatesFLBuilderModule extends \FLBuilderModule {

	/**
	 * Constructor.
	 *
	 * @since 4.5.0
	 */
	public function __construct() {

		parent::__construct(
			[
				'name'            => PageBuilderBlocks::get_text( 'search_results_label' ),
				'description'     => PageBuilderBlocks::get_text( 'search_results_description' ),
				'category'        => esc_html__( 'SearchWP', 'searchwp' ),
				'dir'             => SEARCHWP_PLUGIN_DIR . '/includes/Integrations/PageBuilderBlocks/BeaverBuilder/modules/SearchTemplatesModule/',
				'url'             => SEARCHWP_PLUGIN_URL . '/includes/Integrations/PageBuilderBlocks/BeaverBuilder/modules/SearchTemplatesModule/',
				'icon'            => 'list.svg',
				'editor_export'   => true,
				'enabled'         => true,
				'partial_refresh' => true,
			]
		);
	}

	/**
	 * Register module settings.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	public function register_module() {

		$template_options = PageBuilderBlocks::get_template_options();
		$engine_options   = PageBuilderBlocks::get_search_engines_options();

		// Register the module with Beaver Builder.
		\FLBuilder::register_module(
			__CLASS__,
			[
				'general' => [
					'title'    => PageBuilderBlocks::get_text( 'search_results_label' ),
					'sections' => [
						'general' => [
							'title'  => '',
							'fields' => [
								'template_id' => [
									'type'    => 'select',
									'label'   => PageBuilderBlocks::get_text( 'template_label' ),
									'default' => '',
									'options' => $template_options,
								],
								'engine'      => [
									'type'        => 'select',
									'label'       => PageBuilderBlocks::get_text( 'search_engine_label' ),
									'default'     => '',
									'options'     => $engine_options,
									'description' => PageBuilderBlocks::get_text( 'search_settings_notice' ),
								],
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Render the module output.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	public function render() {

		$template_id = ! empty( $this->settings->template_id ) ? absint( $this->settings->template_id ) : '';
		$engine      = ! empty( $this->settings->engine ) ? sanitize_text_field( $this->settings->engine ) : '';

		printf(
			'[searchwp_template id="%d" engine="%s"]',
			esc_attr( $template_id ),
			esc_attr( $engine )
		);

		if ( \FLBuilderModel::is_builder_active() ) {
			add_filter(
				'do_shortcode_tag',
				function ( $output, $tag ) {
					if ( $tag !== 'searchwp_template' ) {
						return $output;
					}

					return PageBuilderBlocks::get_component_preview( PageBuilderBlocks::get_text( 'search_results_message' ) );
				},
				10,
				2
			);
		}
	}
}

// Register the module.
( new SearchWPTemplatesFLBuilderModule() )->register_module();
