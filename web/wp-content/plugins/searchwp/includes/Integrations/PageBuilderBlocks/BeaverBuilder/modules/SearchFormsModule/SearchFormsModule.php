<?php

namespace SearchWP\Integrations\PageBuilderBlocks\BeaverBuilder\modules\SearchWPFormsModule;

use SearchWP\Integrations\PageBuilderBlocks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SearchWPFormsModule handles SearchWP forms integration with Beaver Builder.
 *
 * @since 4.5.0
 */
class SearchWPFormsFLBuilderModule extends \FLBuilderModule {

	/**
	 * Constructor.
	 *
	 * @since 4.5.0
	 */
	public function __construct() {

		parent::__construct(
			[
				'name'            => PageBuilderBlocks::get_text( 'form_label' ),
				'description'     => PageBuilderBlocks::get_text( 'search_form_description' ),
				'category'        => esc_html__( 'SearchWP', 'searchwp' ),
				'dir'             => SEARCHWP_PLUGIN_DIR . '/includes/Integrations/PageBuilderBlocks/BeaverBuilder/modules/SearchFormsModule/',
				'url'             => SEARCHWP_PLUGIN_URL . '/includes/Integrations/PageBuilderBlocks/BeaverBuilder/modules/SearchFormsModule/',
				'icon'            => 'search.svg',
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

		$form_options = PageBuilderBlocks::get_form_options();

		// Register the module with Beaver Builder.
		\FLBuilder::register_module(
			__CLASS__,
			[
				'swp-flbuilder-form' => [
					'title'    => PageBuilderBlocks::get_text( 'search_form_title' ),
					'sections' => [
						'general' => [
							'title'  => '',
							'fields' => [
								'form_id' => [
									'type'    => 'select',
									'label'   => PageBuilderBlocks::get_text( 'form_label' ),
									'default' => '0',
									'options' => $form_options,
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

		$form_id = 0;

		if ( isset( $this->settings ) && is_object( $this->settings ) && ! empty( $this->settings->form_id ) ) {
			$form = \SearchWP\Forms\Storage::get( absint( $this->settings->form_id ) );

			if ( ! empty( $form ) ) {
				$form_id = $this->settings->form_id;
			}
		}

		printf( '[searchwp_form id="%d"]', absint( $form_id ) );

		// If no form is selected, show a placeholder in the builder.
		if ( \FLBuilderModel::is_builder_active() && empty( $form_id ) ) {
			add_filter(
				'do_shortcode_tag',
				function ( $output, $tag, $attr ) {
					if ( $tag !== 'searchwp_form' ) {
						return $output;
					}

					if ( empty( $attr['id'] ) || absint( $attr['id'] ) === 0 ) {
						return PageBuilderBlocks::get_component_preview( PageBuilderBlocks::get_text( 'select_form_from_settings' ) );
					} else {
						return $output;
					}
				},
				10,
				3
			);
		}
	}
}

// Register the module.
( new SearchWPFormsFLBuilderModule() )->register_module();
