<?php

namespace SearchWP\Templates;

use SearchWP\License;
use SearchWP\Settings;

/**
 * Manage Search Results Page settings.
 *
 * @since 4.3.6
 */
class Storage {

	/**
	 * Default settings.
	 *
	 * @since 4.3.6
	 *
	 * @var array
	 */
	const DEFAULTS = [
		'swp-layout-theme'          => 'alpha',
		'swp-layout-style'          => 'list',
		'swp-results-per-row'       => 3,
		'swp-image-size'            => '',
		'swp-title-color'           => '',
		'swp-title-font-size'       => '',
		'swp-price-color'           => '',
		'swp-price-font-size'       => '',
		'swp-description-enabled'   => true,
		'swp-button-enabled'        => false,
		'swp-button-label'          => '',
		'swp-button-bg-color'       => '',
		'swp-button-font-color'     => '',
		'swp-button-font-size'      => '',
		'swp-results-per-page'      => 10,
		'swp-pagination-style'      => '',
		'swp-pagination-prev'       => '',
		'swp-pagination-next'       => '',
		'swp-load-more-enabled'     => true,
		'swp-load-more-label'       => '',
		'swp-load-more-bg-color'    => '',
		'swp-load-more-font-color'  => '',
		'swp-load-more-font-size'   => '',
		'swp-promoted-ads-enabled'  => false,
		'swp-promoted-ads-content'  => '',
		'swp-promoted-ads-position' => 1,
	];

	const LAYOUT_THEMES = [
		'alpha'    => 'Minimal',
		'beta'     => 'Compact',
		'gamma'    => 'Columns',
		'epsilon'  => 'Medium',
		'zeta'     => 'Rich',
		'combined' => 'Combined',
	];

	/**
	 * Add a new form with default data.
	 *
	 * @since 4.4.0
	 *
	 * @param array $data Non-default form data to apply to a new form.
	 *
	 * @return int/false Template id.
	 */
	public static function add( $data = [] ) {

		if ( ! self::current_user_can_create_templates() ) {
			return false;
		}

		$option = self::get_option();

		$id = isset( $option['next_id'] ) ? absint( $option['next_id'] ) : 1;
		$id = ! empty( $id ) ? $id : 1;

		$template = wp_parse_args( $data, self::DEFAULTS );

		$template['title'] = sprintf(
			/* translators: %d: Form id. */
			__( 'Results Template %d', 'searchwp' ),
			$id
		);

		$template['id'] = $id;

		$option['templates'][ $id ] = $template;
		$option['next_id']          = $id + 1;

		self::update_option( $option );

		return $id;
	}

	/**
	 * Update a single setting.
	 *
	 * @since 4.3.6
	 *
	 * @param string $id   Template ID.
	 * @param mixed  $data Template Settings.
	 *
	 * @return void
	 */
	public static function update( $id, $data ) {

		$template_id = absint( $id );

		if ( empty( $template_id ) ) {
			return;
		}

		foreach ( $data as $name => $value ) {
			if ( array_key_exists( $name, self::DEFAULTS ) ) {

				switch ( $name ) {
					case 'swp-promoted-ads-content':
						$data[ $name ] = wp_kses_post( $value );
						break;

					default:
						$data[ $name ] = sanitize_text_field( $value );
						break;
				}
			}
		}

		$option = self::get_option();

		$option['templates'][ $template_id ] = $data;

		return self::update_option( $option );
	}

	/**
	 * Delete a single template.
	 *
	 * @since 4.4.0
	 *
	 * @param int $id Template id.
	 *
	 * @return void
	 */
	public static function delete( $id ) {

		$option = self::get_option();

		$template_id = absint( $id );
		if ( empty( $template_id ) ) {
			return;
		}

		if ( ! isset( $option['templates'][ $template_id ] ) ) {
			return;
		}

		unset( $option['templates'][ $template_id ] );

		self::update_option( $option );
	}

	/**
	 * Get forms DB option.
	 *
	 * @since 4.3.6
	 *
	 * @return array
	 */
	private static function get_option() {

		$option = Settings::get( 'results_templates' );

		$option = ! empty( $option ) ? $option : '';

		return json_decode( $option, true );
	}

	/**
	 * Update forms DB option.
	 *
	 * @since 4.3.6
	 *
	 * @param array $data Option data.
	 *
	 * @return mixed|null
	 */
	private static function update_option( $data ) {

		return Settings::update( 'results_templates', wp_json_encode( $data ) );
	}

	/**
	 * Get the template settings.
	 *
	 * @since 4.4.0
	 *
	 * @return array
	 */
	public static function get_templates() {

		$option = self::get_option();

		if ( empty( $option ) ) {
			$option = [
				'templates' => [ 1 => array_merge( [ 'title' => 'Default' ], self::DEFAULTS ) ],
				'next_id'   => 2,
			];

			self::update_option( $option );
		}

		return $option['templates'];
	}

	/**
	 * Get settings for a specific template.
	 *
	 * @since 4.4.0
	 *
	 * @param string $template_id Template ID.
	 *
	 * @return array
	 */
	public static function get_template( $template_id ) {

		$templates = self::get_templates();

		$template_id = absint( $template_id );

		if ( empty( $template_id ) ) {
			return [];
		}

		$template = isset( $templates[ $template_id ] ) ? $templates[ $template_id ] : [];

		$template = wp_parse_args( $template, self::DEFAULTS );

		$template = self::apply_template_settings_filters( $template, $template_id );

		$template['id'] = $template_id;

		return $template;
	}

	/**
	 * Get the layout theme label.
	 *
	 * @since 4.4.0
	 *
	 * @param string $theme Theme.
	 *
	 * @return string
	 */
	public static function get_layout_theme_label( $theme ) {

		$themes = self::LAYOUT_THEMES;

		if ( array_key_exists( $theme, $themes ) ) {
			return $themes[ $theme ];
		}

		return self::LAYOUT_THEMES['alpha'];
	}

	/**
	 * Check if current user can create templates.
	 *
	 * @since 4.4.0
	 *
	 * @return bool
	 */
	public static function current_user_can_create_templates() {

		if ( License::is_active() ) {
			return true;
		}

		$templates_count = count( self::get_templates() );

		// Allow one Results Template without an active license.
		if ( $templates_count < 1 ) {
			return true;
		}

		return false;
	}

	/**
	 * Apply template settings filters.
	 *
	 * @since 4.4.0
	 *
	 * @param array $template    Template settings.
	 * @param int   $template_id Template ID.
	 *
	 * @return array
	 */
	private static function apply_template_settings_filters( $template, $template_id ) {

		/**
		 * Filter the template settings.
		 *
		 * @since 4.4.0
		 *
		 * @param array $template    Template settings.
		 * @param int   $template_id Template ID.
		 */
		apply_filters( 'searchwp\results_template\setting', $template, $template_id );

		// Deprecate the old hook.
		if ( has_filter( 'searchwp\results\setting' ) ) {
			_deprecated_hook( 'searchwp\results\setting', '4.3.6', 'searchwp\results_template\setting' );

			/**
			 * Filter the template settings. This hook is deprecated.
			 *
			 * @since 4.3.6
			 *
			 * @param array $template    Template settings.
			 * @param int   $template_id Template ID.
			 */
			$template = apply_filters( 'searchwp\results\setting', $template, $template_id );
		}

		return $template;
	}
}
