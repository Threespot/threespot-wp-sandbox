<?php

namespace SearchWP\Integrations\PageBuilderBlocks\Divi;

use SearchWP\Integrations\PageBuilderBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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

		if ( ! $this->allow_load() ) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Indicate if the current integration is allowed to load.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public function allow_load() {

		if ( function_exists( 'et_divi_builder_init_plugin' ) ) {
			return true;
		}

		$allow_themes = [ 'Divi', 'Extra' ];
		$theme        = wp_get_theme();
		$theme_name   = $theme->get_template();
		$theme_parent = $theme->parent();

		return (bool) array_intersect( [ $theme_name, $theme_parent ], $allow_themes );
	}

	/**
	 * Hooks.
	 *
	 * @since 4.5.0
	 */
	public function hooks() {

		add_action( 'et_builder_ready', [ $this, 'register_modules' ] );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_searchwp_forms_divi_preview', [ $this, 'preview_form' ] );
		}

		if ( $this->is_divi_builder() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'builder_styles' ], 12 );
			add_action( 'wp_enqueue_scripts', [ $this, 'builder_scripts' ] );
		}
	}

	/**
	 * Determine if a current page is opened in the Divi Builder.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	private function is_divi_builder() {

		return ! empty( $_GET['et_fb'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Load styles.
	 *
	 * @since 4.5.0
	 */
	public function builder_styles() {

		wp_enqueue_style(
			'searchwp-divi-module',
			SEARCHWP_PLUGIN_URL . 'includes/Integrations/PageBuilderBlocks/Divi/assets/css/divi-module.css',
			[],
			SEARCHWP_VERSION
		);
	}

	/**
	 * Load scripts.
	 *
	 * @since 4.5.0
	 */
	public function builder_scripts() {

		// Enqueue our Divi module component.
		wp_enqueue_script(
			'searchwp-forms-divi',
			SEARCHWP_PLUGIN_URL . 'includes/Integrations/PageBuilderBlocks/Divi/assets/js/build/index.js',
			[ 'react', 'react-dom', 'wp-api-fetch', 'wp-i18n', 'jquery' ],
			SEARCHWP_VERSION,
			true
		);

		// Localize the script for Search Forms.
		wp_localize_script(
			'searchwp-forms-divi',
			'searchwp_forms_divi_builder',
			[
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'searchwp_divi_builder' ),
				'placeholder'      => SEARCHWP_PLUGIN_URL . 'assets/images/logo.svg',
				'placeholder_text' => PageBuilderBlocks::get_text( 'select_form_from_settings' ),
			]
		);

		// Localize the script for Search Templates.
		wp_localize_script(
			'searchwp-forms-divi',
			'searchwp_templates_divi_builder',
			[
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'nonce'                  => wp_create_nonce( 'searchwp_divi_builder' ),
				'placeholder'            => SEARCHWP_PLUGIN_URL . 'assets/images/logo.svg',
				'placeholder_text_start' => esc_html__( 'Search results will appear here, based on the settings of the', 'searchwp' ),
				'placeholder_text_end'   => esc_html__( 'pointing to this page (see the Search Form\'s "Target Page" setting).', 'searchwp' ),
				'placeholder_forms_link' => esc_html__( 'Search Form', 'searchwp' ),
				'placeholder_forms_url'  => esc_url( admin_url( 'admin.php?page=searchwp-forms' ) ),
				'block_empty_url'        => SEARCHWP_PLUGIN_URL . 'assets/images/logo.svg',
			]
		);
	}

	/**
	 * Register modules.
	 *
	 * @since 4.5.0
	 */
	public function register_modules() {

		if ( ! class_exists( 'ET_Builder_Module' ) ) {
			return;
		}

		new SearchWPFormsModule();
		new SearchWPResultsModule();
	}

	/**
	 * Ajax handler for the forms module preview.
	 *
	 * @since 4.5.0
	 */
	public function preview_form() {

		check_ajax_referer( 'searchwp_divi_builder', 'nonce' );

		$form_id = absint( filter_input( INPUT_POST, 'form_id', FILTER_SANITIZE_NUMBER_INT ) );

		wp_send_json_success(
			do_shortcode(
				sprintf(
					'[searchwp_form id="%1$s"]',
					absint( $form_id )
				)
			)
		);
	}
}
