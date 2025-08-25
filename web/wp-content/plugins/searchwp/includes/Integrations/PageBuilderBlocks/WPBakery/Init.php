<?php

namespace SearchWP\Integrations\PageBuilderBlocks\WPBakery;

use SearchWP\Integrations\PageBuilderBlocks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init.
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

		add_action( 'searchwp\loaded', [ $this, 'maybe_show_template_preview' ] );

		add_action( 'vc_before_init', [ $this, 'register_search_forms' ] );

		add_action( 'vc_before_init', [ $this, 'register_results_templates' ] );
	}

	/**
	 * Register the SearchWP search form.
	 *
	 * @since 4.5.0
	 */
	public function register_search_forms() {

		$forms = \SearchWP\Forms\Storage::get_all();

		if ( empty( $forms ) ) {
			$form_options = [
				[
					PageBuilderBlocks::get_text( 'no_forms_available' ),
				],
			];
		} else {
			$form_options = array_map(
				function ( $form ) {
					return [
						'value' => (string) $form['id'],
						'label' => $form['title'],
					];
				},
				$forms
			);

			$form_options = array_merge( [ [ 0 => PageBuilderBlocks::get_text( 'select_form' ) ] ], $form_options );
		}

		vc_map(
			[
				'name'        => PageBuilderBlocks::get_text( 'search_form_title' ),
				'base'        => 'searchwp_form',
				'description' => PageBuilderBlocks::get_text( 'search_form_description' ),
				'category'    => __( 'SearchWP', 'searchwp' ),
				'icon'        => SEARCHWP_PLUGIN_URL . 'assets/images/swp-logo-s.svg',
				'params'      => [
					[
						'type'        => 'dropdown',
						'heading'     => PageBuilderBlocks::get_text( 'form_label' ),
						'param_name'  => 'id',
						'value'       => $form_options,
						'description' => PageBuilderBlocks::get_text( 'search_form_description' ),
					],
				],
			]
		);
	}

	/**
	 * Register the SearchWP results templates.
	 *
	 * @since 4.5.0
	 */
	public function register_results_templates() {

		$template_options = PageBuilderBlocks::get_template_options();

		$template_options = array_map(
			function ( $template_label, $template_id ) {
				return [
					'value' => $template_id,
					'label' => $template_label,
				];
			},
			$template_options,
			array_keys( $template_options )
		);

		$engine_options = PageBuilderBlocks::get_search_engines_options();

		$engine_options = array_map(
			function ( $engine_label, $engine_id ) {
				return [
					'value' => $engine_id,
					'label' => $engine_label,
				];
			},
			$engine_options,
			array_keys( $engine_options )
		);

		vc_map(
			[
				'name'                    => PageBuilderBlocks::get_text( 'search_results_label' ),
				'base'                    => 'searchwp_template',
				'description'             => PageBuilderBlocks::get_text( 'search_results_description' ),
				'category'                => __( 'SearchWP', 'searchwp' ),
				'icon'                    => SEARCHWP_PLUGIN_URL . 'assets/images/swp-logo-s.svg',
				'show_settings_on_create' => false,
				'params'                  => [
					[
						'type'        => 'dropdown',
						'heading'     => PageBuilderBlocks::get_text( 'results_template_label' ),
						'param_name'  => 'id',
						'value'       => $template_options,
						'description' => PageBuilderBlocks::get_text( 'search_settings_notice' ),
					],
                    [
                        'type'        => 'dropdown',
                        'heading'     => PageBuilderBlocks::get_text( 'search_engine_label' ),
                        'param_name'  => 'engine',
                        'value'       => $engine_options,
                        'description' => PageBuilderBlocks::get_text( 'search_settings_notice' ),
                    ],
				],
			]
		);
	}

	/**
	 * Show the template preview in the frontend editor.
	 *
	 * @since 4.5.0
	 */
	public function maybe_show_template_preview() {

		// If the current page is the frontend editor, we need to show the template preview.
		if ( vc_is_inline() ) {
			// Override SearchWP template shortcode to render the SearchWP logo and a message.
			remove_shortcode( 'searchwp_template' );

			add_shortcode(
				'searchwp_template',
				function () {
					ob_start();
					?>
					<?php echo PageBuilderBlocks::get_component_preview( PageBuilderBlocks::get_text( 'search_results_message' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php

					return ob_get_clean();
				}
			);
		}
	}
}
