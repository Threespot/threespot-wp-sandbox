<?php

namespace SearchWP\Integrations;

use SearchWP\Integrations\PageBuilderBlocks\Elementor\Init as ElementorWidgetInit;
use SearchWP\Integrations\PageBuilderBlocks\Divi\Init as DiviWidgetInit;
use SearchWP\Integrations\PageBuilderBlocks\BeaverBuilder\Init as BeaverBuilderWidgetInit;
use SearchWP\Integrations\PageBuilderBlocks\Breakdance\Init as BreakdanceWidgetInit;
use SearchWP\Integrations\PageBuilderBlocks\WPBakery\Init as WPBakeryWidgetInit;
use SearchWP\Integrations\PageBuilderBlocks\Bricks\Init as BricksWidgetInit;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PageBuilderBlocks handles integration with various page builders.
 *
 * @since 4.5.0
 */
class PageBuilderBlocks {

	/**
	 * Initialize the class.
	 *
	 * @since 4.5.0
     *
	 * @return void
	 */
	public static function init() {

		// Integrations that require to run on 'init' action.
		add_action(
			'init',
			function () {
				self::initialize_elementor_integration();
				self::initialize_divi_integration();
				self::initialize_beaver_builder_integration();

				// Breakdance and Oxygen share the same builder core called Breakdance.
				self::initialize_breakdance_integration();

				self::initialize_bricks_integration();
			}
		);

		// Integrations that require to run before 'init' action.
		self::initialize_wpbakery_integration();
	}

	/**
	 * Initialize Elementor integration.
	 *
	 * @since 4.5.0
     *
	 * @return void
	 */
	private static function initialize_elementor_integration() {
		// Initialize the Elementor integration by creating a new instance.
		if ( did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' ) ) {
			new ElementorWidgetInit();
		}
	}

	/**
	 * Initialize Divi Builder integration.
	 *
	 * @since 4.5.0
     *
	 * @return void
	 */
	private static function initialize_divi_integration() {

		new DiviWidgetInit();
	}

	/**
	 * Initialize Beaver Builder integration.
	 *
	 * @since 4.5.0
     *
	 * @return void
	 */
	private static function initialize_beaver_builder_integration() {

		if ( class_exists( '\FLBuilder' ) ) {
			new BeaverBuilderWidgetInit();
		}
	}

	/**
	 * Initialize Breakdance integration.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	private static function initialize_breakdance_integration() {

		if ( class_exists( '\Breakdance\Elements\Element' ) ) {
			new BreakdanceWidgetInit();
		}
	}

	/**
	 * Initialize WPBakery integration.
	 *
	 * @since 4.5.0
     *
	 * @return void
	 */
	private static function initialize_wpbakery_integration() {

		if ( class_exists( 'WPBakeryVisualComposerAbstract' ) || class_exists( 'Vc_Manager' ) ) {
			new WPBakeryWidgetInit();
		}
	}

	/**
	 * Initialize Bricks Builder integration.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	private static function initialize_bricks_integration() {

		// Check if Bricks Builder is active.
		if ( class_exists( '\Bricks\Builder' ) ) {
			new BricksWidgetInit();
		}
	}

	/**
	 * Get common text strings used across page builder blocks.
	 *
	 * @since 4.5.0
	 *
	 * @param string $key The string key to retrieve.
	 *
	 * @return string The localized text string.
	 */
	public static function get_text( $key ) {

		$strings = [
			// Search Forms related texts.
			'form_label'                 => esc_html__( 'Search Form', 'searchwp' ),
			'form_settings_label'        => esc_html__( 'Form Settings', 'searchwp' ),
			'select_form'                => esc_html__( 'Select Form', 'searchwp' ),
			'search_form_title'          => esc_html__( 'SearchWP Form', 'searchwp' ),
			'select_form_from_settings'  => esc_html__( 'Select a search form from the settings.', 'searchwp' ),
			'no_forms_available'         => esc_html__( 'No forms available', 'searchwp' ),
			'search_form_description'    => esc_html__( 'Display a SearchWP search form.', 'searchwp' ),
			// Search results related texts.
			'search_results_label'       => esc_html__( 'SearchWP Results', 'searchwp' ),
			'results_settings_label'     => esc_html__( 'Results Settings', 'searchwp' ),
			'results_template_label'     => esc_html__( 'Results Template', 'searchwp' ),
			'template_label'             => esc_html__( 'Template', 'searchwp' ),
			'search_engine_label'        => esc_html__( 'Search Engine', 'searchwp' ),
			'set_by_search_form'         => esc_html__( 'Set by Search Form', 'searchwp' ),
			'search_settings_notice'     => esc_html__( 'Search results are based on your form settings and typically don\'t require changes. Use this settings only if you need to override the form settings.', 'searchwp' ),
			'search_results_message'     => esc_html__( 'Search results will appear here, based on the settings of the Search Form pointing to this page (see the Search Form\'s "Target Page" setting).', 'searchwp' ),
			'search_results_description' => esc_html__( 'Display SearchWP search results.', 'searchwp' ),
		];

		return isset( $strings[ $key ] ) ? $strings[ $key ] : '';
	}

	/**
	 * Render the component preview.
	 *
	 * @since 4.5.0
	 *
	 * @param array $notice The notice data.
	 *
	 * @return string The rendered component preview.
	 */
	public static function get_component_preview( $notice = '' ) {

		ob_start();
		?>
		<div class="swp-logo" style="text-align:center;">
			<img src="<?php echo esc_url( SEARCHWP_PLUGIN_URL . 'assets/images/logo.svg' ); ?>" alt="" style="margin: 20px auto;">
			<?php if ( ! empty( $notice ) ) : ?>
			<p><?php echo esc_html( $notice ); ?></p>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get the form options for the select field.
	 *
	 * @since 4.5.0
	 *
	 * @return array Form options.
	 */
	public static function get_form_options() {

		$forms = \SearchWP\Forms\Storage::get_all();

		$options = empty( $forms )
			? [ 0 => self::get_text( 'no_forms_available' ) ]
			: [ 0 => self::get_text( 'select_form' ) ];

		if ( empty( $forms ) ) {
			return $options;
		}

		foreach ( $forms as $form_id => $form ) {
			$options[ $form_id ] = ! empty( $form['title'] )
				? esc_html( $form['title'] )
				/* translators: %d: Form ID. */
				: sprintf( esc_html__( 'Form #%d', 'searchwp' ), $form_id );
		}

		return $options;
	}

	/**
	 * Get the template options for the select field.
	 *
	 * @since 4.5.0
	 *
	 * @return array Template options.
	 */
	public static function get_template_options() {

		$templates = \SearchWP\Templates\Storage::get_templates();

		$options     = [];
		$options[''] = self::get_text( 'set_by_search_form' );

		if ( empty( $templates ) ) {
			return $options;
		}

		foreach ( $templates as $template_id => $template ) {
			$options[ $template_id ] = ! empty( $template['title'] )
				? esc_html( $template['title'] )
				/* translators: %d: Template ID. */
				: sprintf( esc_html__( 'Template #%d', 'searchwp' ), $template_id );
		}

		return $options;
	}

	/**
	 * Get the search engines options for the select field.
	 *
	 * @since 4.5.0
	 *
	 * @return array Search engines options.
	 */
	public static function get_search_engines_options() {

		$engines = \SearchWP\Settings::_get_engines_settings();

		$options     = [];
		$options[''] = self::get_text( 'set_by_search_form' );

		if ( empty( $engines ) ) {
			return $options;
		}

		foreach ( $engines as $engine_name => $engine ) {
			$options[ $engine_name ] = ! empty( $engine['label'] )
				? esc_html( $engine['label'] )
				/* translators: %d: Engine ID. */
				: sprintf( esc_html__( 'Engine #%d', 'searchwp' ), $engine_name );
		}

		return $options;
	}
}
