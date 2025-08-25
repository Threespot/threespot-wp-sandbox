<?php
/**
 * SearchWP Onboarding Wizard.
 *
 * @since 4.3.14
 */

namespace SearchWP\Admin\Wizards;

use SearchWP\Admin\Extensions\Extensions;
use SearchWP\Admin\Views\EnginesView;
use SearchWP\Engine;
use SearchWP\Forms\Storage;
use SearchWP\License;
use SearchWP\Settings;
use SearchWP\Source;
use SearchWP\Utils;

/**
 * Class Onboarding is responsible for SearchWP Onboarding Wizard.
 *
 * @since 4.3.14
 */
class Onboarding {

	/**
	 * Onboarding Wizard page slug.
	 *
	 * @since 4.3.14
	 *
	 * @var string
	 */
	private static $slug = 'searchwp-onboarding-wizard';

	/**
	 * Init class.
	 *
	 * @since 4.3.14
	 */
	public static function init() {

		if ( ! is_admin() || wp_doing_cron() ) {
			return;
		}

		self::hooks();
	}

	/**
	 * Run hooks.
	 *
	 * @since 4.3.14
	 */
	private static function hooks() {

		if ( Utils::is_swp_admin_page( 'onboarding-wizard' ) && ! wp_doing_ajax() ) {
			self::admin_hooks();
		}

		if ( wp_doing_ajax() ) {
			self::ajax_hooks();
		}
	}

	/**
	 * Run admin hooks.
	 *
	 * @since 4.3.14
	 */
	private static function admin_hooks() {

		add_action( 'admin_menu', [ __CLASS__, 'add_dashboard_page' ] );
		add_action( 'admin_head', [ __CLASS__, 'hide_dashboard_page_from_menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );

		add_action( 'searchwp\settings\header', '__return_false' );
	}

	/**
	 * Run AJAX hooks.
	 *
	 * @since 4.3.14
	 */
	private static function ajax_hooks() {

		add_action( 'wp_ajax_searchwp_onboarding_load_page', [ __CLASS__, 'ajax_load_page' ] );
	}

	/**
	 * Add dashboard page.
	 *
	 * @since 4.3.14
	 */
	public static function add_dashboard_page() {

		add_dashboard_page(
			esc_html__( 'SearchWP Onboarding Wizard', 'searchwp' ),
			esc_html__( 'SearchWP Onboarding Wizard', 'searchwp' ),
			Settings::get_capability(),
			self::$slug,
			[ __CLASS__, 'render' ]
		);
	}

	/**
	 * Hide dashboard page from the admin menu.
	 *
	 * @since 4.3.14
	 */
	public static function hide_dashboard_page_from_menu() {

		remove_submenu_page( 'index.php', self::$slug );
	}

	/**
	 * Output the assets needed for the Wizard.
	 *
	 * @since 4.3.14
	 */
	public static function assets() {

		$handle = SEARCHWP_PREFIX . self::$slug;

		wp_enqueue_style(
			$handle,
			SEARCHWP_PLUGIN_URL . 'assets/css/admin/pages/onboarding-wizard.css',
			[
				Utils::$slug . 'buttons',
				Utils::$slug . 'input',
				Utils::$slug . 'toggle-switch',
				Utils::$slug . 'style',
			],
			SEARCHWP_VERSION
		);

		wp_enqueue_script(
			$handle,
			SEARCHWP_PLUGIN_URL . 'assets/js/admin/pages/onboarding-wizard.js',
			[],
			SEARCHWP_VERSION,
			true
		);
	}

	/**
	 * Render the page.
	 *
	 * @since 4.3.14
	 */
	public static function render() {

		$page = isset( $_GET['subpage'] ) && Utils::is_swp_admin_page( 'onboarding-wizard' ) ? sanitize_key( $_GET['subpage'] ) : 'welcome'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$allowed_pages = [
			'welcome',
			'engines',
			'integrations',
			'features',
			'license',
			'finish',
		];

		?>
		<div class="swp-content-container">
			<div class="swp-onboarding-wizard swp-flex--col swp-flex--align-c">
				<div class="swp-onboarding-header">
					<div class="swp-padding-b40">
						<?php self::header_logo(); ?>
					</div>
				</div>
				<div class="swp-onboarding-wizard-body swp-flex--col swp-flex--gap25">
					<?php
					if ( is_callable( [ __CLASS__, 'render_' . $page ] ) && in_array( $page, $allowed_pages, true ) ) {
						call_user_func( [ __CLASS__, 'render_' . $page ] );
					} else {
						wp_safe_redirect( add_query_arg( [ 'page' => self::$slug ], admin_url() ) );
					}
					?>
				</div>
			</div>
			<?php if ( $page !== 'finish' ) : ?>
				<div class="swp-flex--col swp-flex--align-c">
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'searchwp-forms' ], admin_url( 'admin.php' ) ) ); ?>" class="swp-text-gray swp-padding18"><?php esc_html_e( 'Close and Exit Wizard Without Saving', 'searchwp' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the Welcome page.
	 *
	 * @since 4.3.14
	 */
	private static function render_welcome() {

		Settings::update( 'onboarding_wizard', [] );

		$sources = \SearchWP::get_sources();

		$active_sources   = array_filter( $sources, [ Utils::class, 'any_engine_has_source' ] );
		$inactive_sources = array_diff_key( $sources, $active_sources );
		?>
		<h1 class="swp-h1"><?php esc_html_e( 'Welcome to the SearchWP Setup Wizard!', 'searchwp' ); ?></h1>
		<p class="swp-p"><?php esc_html_e( 'SearchWP makes it easy to configure your site`s search settings without the need to hire an expert. And it takes less than 10 minutes too! We pre-selected the most popular search sources to make it even faster!', 'searchwp' ); ?></p>

		<div class="swp-hr"></div>

		<div class="swp-flex--row swp-flex--gap80">
			<div class="swp-flex--col swp-flex--gap12">
				<p class="swp-p"><?php esc_html_e( 'Sources enabled by default:', 'searchwp' ); ?></p>
				<ul>
					<?php foreach ( $active_sources as $active_source ) : ?>
						<li><?php echo esc_html( $active_source->get_label() ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="swp-flex--col swp-flex--gap12">
				<p class="swp-p"><?php esc_html_e( 'Other searchable sources:', 'searchwp' ); ?></p>
				<ul>
					<?php foreach ( $inactive_sources as $inactive_source ) : ?>
						<li class="swp-gray-check"><?php echo esc_html( $inactive_source->get_label() ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

		<div class="swp-hr"></div>

		<p class="swp-p"><?php esc_html_e( 'To customize enabled search sources list click "Make Changes".', 'searchwp' ); ?></p>
		<p class="swp-p"><?php esc_html_e( 'Click "Accept Default" if the default selection looks good to you. SearchWP will save the changes and automatically create a Search Form you can put anywhere on your site.', 'searchwp' ); ?></p>

		<div id="swp-onboarding-error-msg" class="swp-text-red" style="display: none;"></div>
		<div class="swp-hr"></div>

		<div class="swp-flex--row swp-justify-between">
			<button class="swp-onboarding-page-nav-btn swp-button" type="button" data-current-page="welcome" data-action="change_default"><?php esc_html_e( 'Make Changes', 'searchwp' ); ?></button>
			<button class="swp-onboarding-page-nav-btn swp-button swp-button--green" type="button" data-current-page="welcome" data-action="accept_default"><?php esc_html_e( 'Accept Default', 'searchwp' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render the Engines configuration page.
	 *
	 * @since 4.3.14
	 */
	private static function render_engines() {

		$sources = \SearchWP::get_sources();

		$default_sources = array_intersect_key(
			$sources,
			array_filter(
				array_map(
					function ( $source ) {
						// A Source is only default if it represents WP_Post.
						if ( 0 !== strpos( $source->get_name(), 'post' . SEARCHWP_SEPARATOR ) ) {
							return false;
						}

						return json_decode( wp_json_encode( $source ), true );
					},
					\SearchWP::$index->get_default_sources( true )
				)
			)
		);

		$default_settings       = Settings::get_engine_settings( 'default' );
		$active_default_sources = ! empty( $default_settings['sources'] ) ? $default_settings['sources'] : [];

		$custom_sources        = array_diff_key( $sources, $default_sources );
		$active_custom_sources = array_filter( $custom_sources, [ Utils::class, 'any_engine_has_source' ] );
		?>
		<h1 class="swp-h1"><?php esc_html_e( 'Search Engine Configuration', 'searchwp' ); ?></h1>
		<p class="swp-p"><?php esc_html_e( 'The SearchWP Default engine is already configured and currently powers your searches instead of the WP native search. Customize the sources for the Default engine below or select Custom sources to create a separate Supplemental engine.', 'searchwp' ); ?></p>

		<div class="swp-hr"></div>

		<div class="swp-flex--row swp-flex--gap80">
			<div class="swp-flex--col swp-flex--gap12">
				<p class="swp-p"><?php esc_html_e( 'Default engine sources:', 'searchwp' ); ?></p>
				<div class="swp-flex--col swp-flex--gap12">
					<?php foreach ( $default_sources as $default_source ) : ?>
						<label class="swp-toggle swp-flex--row swp-flex--align-c swp-flex--gap9">
							<input class="swp-toggle-checkbox" type="checkbox" name="<?php echo esc_html( $default_source->get_name() ); ?>"<?php checked( array_key_exists( $default_source->get_name(), $active_default_sources ) ); ?> data-swp-input-group="default_sources">
							<div class="swp-toggle-switch swp-toggle-switch--mini"></div>
							<span><?php echo esc_html( $default_source->get_label() ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="swp-flex--col swp-flex--gap12">
				<p class="swp-p"><?php esc_html_e( 'Custom sources:', 'searchwp' ); ?></p>
				<div id="swp-onboarding-custom-sources" class="swp-flex--col swp-flex--gap12">
					<?php foreach ( $custom_sources as $custom_source ) : ?>
						<label class="swp-toggle swp-flex--row swp-flex--align-c swp-flex--gap9">
							<input class="swp-toggle-checkbox" type="checkbox" name="<?php echo esc_html( $custom_source->get_name() ); ?>"<?php checked( array_key_exists( $custom_source->get_name(), $active_custom_sources ) ); ?> data-swp-input-group="custom_sources">
							<div class="swp-toggle-switch swp-toggle-switch--mini"></div>
							<span><?php echo esc_html( $custom_source->get_label() ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="swp-hr"></div>

		<p id="swp-onboarding-default-engine-only-msg" class="swp-p"<?php echo empty( $active_custom_sources ) ? '' : ' style="display: none;"'; ?>><?php esc_html_e( 'SearchWP will save the changes and automatically create a customizable Search Form you can put anywhere on your site.', 'searchwp' ); ?></p>
		<p id="swp-onboarding-supplemental-engine-created-msg" class="swp-p"<?php echo empty( $active_custom_sources ) ? ' style="display: none;"' : ''; ?>><?php esc_html_e( 'A separate Supplemental engine will be created to handle the Custom sources. It will include all sources selected on this screen. To utilize the new engine use the Supplemental Search Form. SearchWP will automatically create this form for you.', 'searchwp' ); ?></p>

		<div id="swp-onboarding-error-msg" class="swp-text-red" style="display: none;"></div>
		<div class="swp-hr"></div>

		<div class="swp-flex--row swp-justify-between">
			<button class="swp-onboarding-page-nav-btn swp-button" type="button" data-current-page="engines" data-action="back"><?php esc_html_e( 'Back', 'searchwp' ); ?></button>
			<button class="swp-onboarding-page-nav-btn swp-button swp-button--green" type="button" data-current-page="engines" data-action="next"><?php esc_html_e( 'Next', 'searchwp' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Get data for the Integrations page.
	 *
	 * @since 4.3.14
	 *
	 * @return array
	 */
	private static function get_render_integrations_data() {

		$extensions              = Extensions::get_all();
		$integrations            = array_filter(
			$extensions,
			function ( $extension ) {
				if ( empty( $extension['integration'] ) ) {
					return false;
				}
				if ( ! in_array( $extension['integration']['type'], [ 'plugin', 'theme' ], true ) ) {
					return false;
				}

				return true;
			}
		);
		$installed_plugins       = array_keys( get_plugins() );
		$applicable_integrations = array_filter(
			$integrations,
			function ( $extension ) use ( $installed_plugins ) {
				if ( empty( $extension['integration']['file'] ) ) {
					return false;
				}
				// Check if the integration target plugin is installed.
				if ( ! in_array( $extension['integration']['file'], $installed_plugins, true ) ) {
					return false;
				}
				// Plugin deactivation is not allowed from the wizard.
				if ( $extension['plugin_active'] ) {
					return false;
				}

				return true;
			}
		);
		$active_integrations     = array_filter(
			$integrations,
			function ( $extension ) {
				return $extension['plugin_active'];
			}
		);
		$other_integrations      = array_diff_key( $integrations, $applicable_integrations, $active_integrations );
		$supported_products      = array_column( array_column( $other_integrations, 'integration' ), 'name' );

		return [
			'active_integrations'     => $active_integrations,
			'applicable_integrations' => $applicable_integrations,
			'supported_products'      => $supported_products,
		];
	}

	/**
	 * Render the Integrations page.
	 *
	 * @since 4.3.14
	 */
	private static function render_integrations() {

		$data = self::get_render_integrations_data();

		?>
		<h1 class="swp-h1"><?php esc_html_e( 'SearchWP Integrations', 'searchwp' ); ?></h1>
		<p class="swp-p"><?php esc_html_e( 'SearchWP deeply integrates with your existing WP themes and plugins!', 'searchwp' ); ?></p>

		<?php if ( ! empty( $data['active_integrations'] ) ) : ?>
			<div class="swp-flex--col swp-flex--gap12">
				<p class="swp-p"><?php esc_html_e( 'Activated:', 'searchwp' ); ?></p>
				<ul>
					<?php foreach ( $data['active_integrations'] as $active_integration ) : ?>
						<li><?php echo esc_html( $active_integration['title'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $data['applicable_integrations'] ) ) : ?>
			<div class="swp-flex--col swp-flex--gap12">
				<p class="swp-p"><?php esc_html_e( 'Recommended:', 'searchwp' ); ?></p>
				<div class="swp-hr"></div>
				<?php foreach ( $data['applicable_integrations'] as $extension_slug => $extension_data ) : ?>
					<div class="swp-flex--row swp-flex--gap17">
						<label class="swp-toggle">
							<input class="swp-toggle-checkbox" type="checkbox" name="<?php echo esc_attr( $extension_slug ); ?>" checked="checked" data-swp-input-group="integrations">
							<div class="swp-toggle-switch"></div>
						</label>
						<div class="swp-flex--col swp-flex--gap9">
							<div class="swp-flex--row swp-flex--align-c swp-flex--gap9">
								<h3 class="swp-h3"><?php echo esc_html( $extension_data['title'] ); ?></h3>
								<?php if ( ! in_array( 'standard', $extension_data['license'], true ) ) : ?>
									<span class="swp-pro-badge"><?php esc_html_e( 'PRO', 'searchwp' ); ?></span>
								<?php endif; ?>
							</div>
							<span class="swp-text-gray"><?php echo esc_html( $extension_data['excerpt'] ); ?></span>
						</div>
					</div>
					<div class="swp-hr"></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( empty( $data['applicable_integrations'] ) && empty( $data['active_integrations'] ) ) : ?>
			<div class="swp-flex--col swp-flex--gap12">
				<p class="swp-p"><?php esc_html_e( 'Currently your website doesn`t require any specific integrations, but SearchWP offers dedicated integration packages for many popular WP products:', 'searchwp' ); ?></p>
				<div class="swp-flex--row swp-justify-between">
					<?php foreach ( array_chunk( $data['supported_products'], ceil( count( $data['supported_products'] ) / 3 ) ) as $supported_product_column ) : ?>
						<ul>
							<?php foreach ( $supported_product_column as $supported_product ) : ?>
								<li><?php echo esc_html( $supported_product ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endforeach; ?>
				</div>
			</div>
			<p class="swp-p"><?php esc_html_e( 'If you decide to use any of these products in the future, the integrations can be installed from the SearchWP Extensions page.', 'searchwp' ); ?></p>
		<?php else : ?>
			<div class="swp-flex--col swp-flex--gap9">
				<p class="swp-p"><?php esc_html_e( 'SearchWP also provides powerful integrations with these WP products:', 'searchwp' ); ?></p>
				<p class="swp-margin0"><?php echo esc_html( implode( ', ', $data['supported_products'] ) ); ?>.</p>
			</div>
		<?php endif; ?>

		<div id="swp-onboarding-error-msg" class="swp-text-red" style="display: none;"></div>
		<div class="swp-hr"></div>

		<div class="swp-flex--row swp-justify-between">
			<button class="swp-onboarding-page-nav-btn swp-button" type="button" data-current-page="integrations" data-action="back"><?php esc_html_e( 'Back', 'searchwp' ); ?></button>
			<button class="swp-onboarding-page-nav-btn swp-button swp-button--green" type="button" data-current-page="integrations" data-action="next"><?php esc_html_e( 'Next', 'searchwp' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render the Features page.
	 *
	 * @since 4.3.14
	 */
	private static function render_features() {

		$extensions_shortlist = [
			'searchwp-live-ajax-search'     => 'Perform a live search as the user types and display the results under the search form.',
			'searchwp-custom-results-order' => 'Add an interface to be able to manually rearrange the search results order for any search query.',
			'searchwp-redirects'            => 'For the search queries of your choice, redirect users to a page instead of displaying the search results.',
			'searchwp-metrics'              => 'Enable enhanced version of search Statistics with insights and recommendations.',
			'searchwp-related'              => 'Display related entries (posts, pages, products etc.) inside the content of selected post types.',
		];

		$extensions = Extensions::get_all();

		$feature_extensions    = array_filter(
			$extensions,
			function ( $extension ) {
				return empty( $extension['integration'] );
			}
		);
		$applicable_extensions = array_filter(
			$extensions,
			function ( $extension ) use ( $extensions_shortlist ) {
				// Plugin deactivation is not allowed from the wizard.
				if ( $extension['plugin_active'] ) {
					return false;
				}
				if ( ! array_key_exists( $extension['slug'], $extensions_shortlist ) ) {
					return false;
				}

				return true;
			}
		);
		$active_extensions     = array_filter(
			$feature_extensions,
			function ( $extension ) {
				return $extension['plugin_active'];
			}
		);
		$other_extensions      = array_diff_key( $feature_extensions, $applicable_extensions, $active_extensions );

		?>
		<h1 class="swp-h1"><?php esc_html_e( 'SearchWP Features', 'searchwp' ); ?></h1>
		<p class="swp-p"><?php esc_html_e( 'SearchWP has additional Feature packages to extend its functionality!', 'searchwp' ); ?></p>

		<?php if ( ! empty( $active_extensions ) ) : ?>
			<div class="swp-flex--col swp-flex--gap12">
				<p class="swp-p"><?php esc_html_e( 'Activated:', 'searchwp' ); ?></p>
				<ul>
					<?php foreach ( $active_extensions as $active_extension ) : ?>
						<li><?php echo esc_html( $active_extension['title'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $applicable_extensions ) ) : ?>
			<div class="swp-flex--col swp-flex--gap12">
				<p class="swp-p"><?php esc_html_e( 'Recommended:', 'searchwp' ); ?></p>
				<div class="swp-hr"></div>
				<?php foreach ( $applicable_extensions as $extension_slug => $extension_data ) : ?>
					<div class="swp-flex--row swp-flex--gap17">
						<label class="swp-toggle">
							<input class="swp-toggle-checkbox" type="checkbox" name="<?php echo esc_attr( $extension_slug ); ?>" checked="checked" data-swp-input-group="features">
							<div class="swp-toggle-switch"></div>
						</label>
						<div class="swp-flex--col swp-flex--gap9">
							<div class="swp-flex--row swp-flex--align-c swp-flex--gap9">
								<h3 class="swp-h3"><?php echo esc_html( $extension_data['title'] ); ?></h3>
								<?php if ( ! in_array( 'standard', $extension_data['license'], true ) ) : ?>
									<span class="swp-pro-badge"><?php esc_html_e( 'PRO', 'searchwp' ); ?></span>
								<?php endif; ?>
							</div>
							<span class="swp-text-gray"><?php echo esc_html( $extensions_shortlist[ $extension_slug ] ); ?></span>
						</div>
					</div>
					<div class="swp-hr"></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="swp-flex--col swp-flex--gap9">
			<p class="swp-p"><?php esc_html_e( 'SearchWP also provides more powerful extensions that can be installed from the Extensions menu:', 'searchwp' ); ?></p>
			<p class="swp-margin0"><?php echo esc_html( implode( ', ', array_column( $other_extensions, 'title' ) ) ); ?>.</p>
		</div>

		<div id="swp-onboarding-error-msg" class="swp-text-red" style="display: none;"></div>
		<div class="swp-hr"></div>

		<div class="swp-flex--row swp-justify-between">
			<button class="swp-onboarding-page-nav-btn swp-button" type="button" data-current-page="features" data-action="back"><?php esc_html_e( 'Back', 'searchwp' ); ?></button>
			<button class="swp-onboarding-page-nav-btn swp-button swp-button--green" type="button" data-current-page="features" data-action="next"><?php esc_html_e( 'Next', 'searchwp' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Get data for the License page.
	 *
	 * @since 4.3.14
	 *
	 * @return array
	 */
	private static function get_render_license_data() {

		$license_is_active = License::is_active();
		$license_type      = License::get_type();

		$wizard_data = Settings::get_single( 'onboarding_wizard', 'array' );
		$extensions  = Extensions::get_all();

		$extensions_list = [];
		foreach ( $wizard_data as $data_key => $data_value ) {
			if ( ! in_array( $data_key , [ 'integrations', 'features' ], true ) ) {
				continue;
			}

			foreach ( array_keys( array_filter( $data_value ) ) as $extension ) {
				$extensions_list[ $extension ] = $extensions[ $extension ];
			}
		}

		$standard_extensions = array_filter(
			$extensions_list,
			function ( $extension ) {
				return in_array( 'standard', $extension['license'], true );
			}
		);

		$pro_extensions = array_diff_key( $extensions_list, $standard_extensions );

		return [
			'license_is_active'   => $license_is_active,
			'license_type'        => $license_type,
			'extensions_list'     => $extensions_list,
			'standard_extensions' => $standard_extensions,
			'pro_extensions'      => $pro_extensions,
		];
	}

	/**
	 * Render the License page when the license is not active.
	 *
	 * @since 4.3.14
	 *
	 * @param array $data Data needed for the page rendering.
	 */
	private static function render_license_inactive( $data ) {

		?>
		<h1 class="swp-h1"><?php esc_html_e( 'SearchWP License Key', 'searchwp' ); ?></h1>

		<?php if ( ! empty( $data['extensions_list'] ) ) : ?>
			<div class="swp-flex--col swp-flex--gap12">
				<p class="swp-p"><?php esc_html_e( 'SearchWP license is required to unlock the following features:', 'searchwp' ); ?></p>
				<ul>
					<?php foreach ( $data['extensions_list'] as $extension_data ) : ?>
						<li>
							<?php echo esc_html( $extension_data['title'] ); ?>
							<?php if ( ! in_array( 'standard', $extension_data['license'], true ) ) : ?>
								<span class="swp-pro-badge"><?php esc_html_e( 'PRO', 'searchwp' ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<p class="swp-margin0"><?php esc_html_e( 'IMPORTANT: You won`t have access to this functionality until the extensions have been unlocked and installed.', 'searchwp' ); ?></p>
			</div>
		<?php else : ?>
			<p class="swp-p"><?php esc_html_e( 'An active license is needed to access new features & extensions, plugin updates (including security improvements), and our world class support!', 'searchwp' ); ?></p>
		<?php endif; ?>

		<?php self::get_license_upsell(); ?>

		<div class="swp-flex--col swp-flex--gap9">
			<p class="swp-p"><?php esc_html_e( 'Already have a license? Simply enter your license key below!', 'searchwp' ); ?></p>

			<div class="swp-flex--row sm:swp-flex--wrap swp-flex--gap12">
				<input id="swp-license" class="swp-input swp-w-2/5">
				<button id="swp-license-activate" class="swp-button swp-button--green"><?php esc_html_e( 'Verify Key', 'searchwp' ); ?></button>
			</div>

			<p id="swp-license-error-msg" class="swp-desc--btm swp-text-red" style="display:none"></p>

			<p class="swp-margin0">
				<?php
				printf(
					wp_kses(
						/* translators: %1$s - Searchwp.com Account Dashboard URL; %2$s - SearchWP Pricing Page URL. */
						__( 'Your license key can be found in your <a href="%1$s" target="_blank" rel="noopener noreferrer">SearchWP Account Dashboard</a>. Don’t have a license? <a href="%2$s" target="_blank" rel="noopener noreferrer">Sign up today!</a>', 'searchwp' ),
						[
							'a' => [
								'href'   => [],
								'rel'    => [],
								'target' => [],
							],
						]
					),
					'https://searchwp.com/account/?utm_campaign=SearchWP&utm_source=WordPress&utm_medium=Onboarding+Wizard+Account+Dashboard+Link&utm_content=SearchWP+Account+Dashboard',
					'https://searchwp.com/buy/?utm_campaign=SearchWP&utm_source=WordPress&utm_medium=Onboarding+Wizard+Sign+Up+Link&utm_content=Sign+Up+Today'
				);
				?>
			</p>
		</div>

		<div id="swp-onboarding-error-msg" class="swp-text-red" style="display: none;"></div>
		<div class="swp-hr"></div>

		<div class="swp-flex--row swp-justify-between">
			<button class="swp-onboarding-page-nav-btn swp-button" type="button" data-current-page="license" data-action="back"><?php esc_html_e( 'Back', 'searchwp' ); ?></button>
			<button class="swp-onboarding-page-nav-btn swp-button" type="button" data-current-page="license" data-action="skip"><?php esc_html_e( 'Skip this Step', 'searchwp' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render the License page when the license is Standard.
	 *
	 * @since 4.3.14
	 *
	 * @param array $data Data needed for the page rendering.
	 */
	private static function render_license_standard( $data ) {

		?>
		<h1 class="swp-h1"><?php esc_html_e( 'SearchWP License Key', 'searchwp' ); ?></h1>

		<div class="swp-flex--col swp-flex--gap9">
			<p class="swp-p"><?php esc_html_e( 'SearchWP license is active!', 'searchwp' ); ?></p>
			<?php $license = Settings::get( 'license' ); ?>
			<p class="swp-margin0">
				Your license key level is <b><span id="swp-license-type"><?php echo esc_html( strtoupper( License::get_type() ) ); ?></span></b>. <span id="swp-license-remaining"><?php echo esc_html( $license['remaining'] ); ?></span>.
			</p>
		</div>

		<div class="swp-flex--col swp-flex--gap12">
			<?php if ( ! empty( $data['pro_extensions'] ) ) : ?>
				<p class="swp-p"><?php esc_html_e( 'SearchWP Pro license is required to unlock the following features:', 'searchwp' ); ?></p>
				<ul>
					<?php foreach ( $data['pro_extensions'] as $extension_data ) : ?>
						<li><?php echo esc_html( $extension_data['title'] ); ?> <span class="swp-pro-badge"><?php esc_html_e( 'PRO', 'searchwp' ); ?></span></li>
					<?php endforeach; ?>
				</ul>
				<p class="swp-margin0"><?php esc_html_e( 'IMPORTANT: You won`t have access to this functionality until the extensions have been unlocked and installed.', 'searchwp' ); ?></p>
			<?php else : ?>
				<p class="swp-p"><?php esc_html_e( 'Get access to SearchWP Pro extensions by upgrading your Standard license to Pro!', 'searchwp' ); ?></p>
			<?php endif; ?>

			<?php self::get_license_upsell(); ?>
		</div>


		<div class="swp-flex--col swp-flex--gap9">
			<p class="swp-p"><?php esc_html_e( 'To access SearchWP Pro features enter your Pro license key below.', 'searchwp' ); ?></p>

			<div class="swp-flex--row sm:swp-flex--wrap swp-flex--gap12">
				<input id="swp-license" class="swp-input swp-w-2/5">
				<button id="swp-license-activate" class="swp-button swp-button--green"><?php esc_html_e( 'Verify Pro Key', 'searchwp' ); ?></button>
			</div>

			<p id="swp-license-error-msg" class="swp-desc--btm swp-text-red" style="display:none"></p>
		</div>

		<?php if ( ! empty( $data['standard_extensions'] ) ) : ?>
			<div class="swp-flex--col swp-flex--gap12">
				<p class="swp-p"><?php esc_html_e( 'The following features are unlocked and will be installed/activated:', 'searchwp' ); ?></p>
				<ul>
					<?php foreach ( $data['standard_extensions'] as $extension_data ) : ?>
						<li><?php echo esc_html( $extension_data['title'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div id="swp-onboarding-error-msg" class="swp-text-red" style="display: none;"></div>
		<div class="swp-hr"></div>

		<div class="swp-flex--row swp-justify-between">
			<button class="swp-onboarding-page-nav-btn swp-button" type="button" data-current-page="license" data-action="back"><?php esc_html_e( 'Back', 'searchwp' ); ?></button>
			<?php if ( ! empty( $data['standard_extensions'] ) ) : ?>
				<button class="swp-onboarding-page-nav-btn swp-button" type="button" data-current-page="license" data-action="install"><?php esc_html_e( 'Install Unlocked Only', 'searchwp' ); ?></button>
			<?php endif; ?>
			<?php if ( empty( $data['standard_extensions'] ) && ! empty( $data['pro_extensions'] ) ) : ?>
				<button class="swp-onboarding-page-nav-btn swp-button" type="button" data-current-page="license" data-action="skip"><?php esc_html_e( 'Skip Pro Installation', 'searchwp' ); ?></button>
			<?php endif; ?>
			<?php if ( empty( $data['extensions_list'] ) ) : ?>
				<button class="swp-onboarding-page-nav-btn swp-button swp-button--green" type="button" data-current-page="license" data-action="skip"><?php esc_html_e( 'Next', 'searchwp' ); ?></button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the License page when the license is Pro or Agency.
	 *
	 * @since 4.3.14
	 *
	 * @param array $data Data needed for the page rendering.
	 */
	private static function render_license_pro_agency( $data ) {

		?>
		<h1 class="swp-h1"><?php esc_html_e( 'SearchWP License Key', 'searchwp' ); ?></h1>

		<div class="swp-flex--col swp-flex--gap9">
			<p class="swp-p"><?php esc_html_e( 'SearchWP license is active!', 'searchwp' ); ?></p>
			<p class="swp-margin0"><?php esc_html_e( 'Active license gives you access to new features, extensions, plugin updates (including security improvements), and our world class support!', 'searchwp' ); ?></p>

			<?php $license = Settings::get( 'license' ); ?>
			<p class="swp-margin0">
				<?php esc_html_e( 'Your license key level is', 'searchwp' ); ?> <b><span id="swp-license-type"><?php echo esc_html( strtoupper( License::get_type() ) ); ?></span></b>. <span id="swp-license-remaining"><?php echo esc_html( $license['remaining'] ); ?></span>.
			</p>
		</div>

		<?php self::get_license_upsell(); ?>

		<?php if ( ! empty( $data['extensions_list'] ) ) : ?>
			<div class="swp-flex--col swp-flex--gap12">
				<p class="swp-p"><?php esc_html_e( 'The following features will be installed and activated once you click "Install Extensions" button:', 'searchwp' ); ?></p>
				<ul>
					<?php foreach ( $data['extensions_list'] as $extension_data ) : ?>
						<li>
							<?php echo esc_html( $extension_data['title'] ); ?>
							<?php if ( ! in_array( 'standard', $extension_data['license'], true ) ) : ?>
								<span class="swp-pro-badge"><?php esc_html_e( 'PRO', 'searchwp' ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php else : ?>
			<p class="swp-p"><?php esc_html_e( 'Click Next to get to the final step!', 'searchwp' ); ?></p>
		<?php endif; ?>

		<div id="swp-onboarding-error-msg" class="swp-text-red" style="display: none;"></div>
		<div class="swp-hr"></div>

		<div class="swp-flex--row swp-justify-between">
			<button class="swp-onboarding-page-nav-btn swp-button" type="button" data-current-page="license" data-action="back"><?php esc_html_e( 'Back', 'searchwp' ); ?></button>
			<?php if ( ! empty( $data['extensions_list'] ) ) : ?>
				<button class="swp-onboarding-page-nav-btn swp-button swp-button--green" type="button" data-current-page="license" data-action="install"><?php esc_html_e( 'Install Extensions', 'searchwp' ); ?></button>
			<?php else : ?>
				<button class="swp-onboarding-page-nav-btn swp-button swp-button--green" type="button" data-current-page="license" data-action="skip"><?php esc_html_e( 'Next', 'searchwp' ); ?></button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the License page.
	 *
	 * @since 4.3.14
	 */
	private static function render_license() {

		$data = self::get_render_license_data();

		if ( $data['license_is_active'] && $data['license_type'] === 'standard' ) {
			self::render_license_standard( $data );

			return;
		}

		if ( $data['license_is_active'] && in_array( $data['license_type'], [ 'pro', 'agency' ], true ) ) {
			self::render_license_pro_agency( $data );

			return;
		}

		self::render_license_inactive( $data );
	}

	/**
	 * Render the final page.
	 *
	 * @since 4.3.14
	 */
	private static function render_finish() {
		?>
		<h1 class="swp-h1"><?php esc_html_e( 'Congratulations, you`re all set!', 'searchwp' ); ?></h1>
		<p class="swp-p"><?php esc_html_e( 'The Onboarding is completed: SearchWP configured Search Engines, created Search Forms and installed selected extensions for you.', 'searchwp' ); ?></p>

		<div class="swp-flex--col swp-flex--gap12">
			<p class="swp-p"><?php esc_html_e( 'Here`s what we recommend to do next:', 'searchwp' ); ?></p>
			<ul>
				<li>
					<?php
					printf(
						wp_kses(
							/* translators: %1$s - SearchWP Search Forms URL. */
							__( 'View and Embed your <a href="%1$s" target="_blank" rel="noopener noreferrer">Search Forms</a>.', 'searchwp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						esc_url( add_query_arg( [ 'page' => 'searchwp-forms' ], admin_url( 'admin.php' ) ) )
					);
					?>
				</li>
				<li>
					<?php
					printf(
						wp_kses(
							/* translators: %1$s - SearchWP Templates URL. */
							__( 'Customize SearchWP <a href="%1$s" target="_blank" rel="noopener noreferrer">Search Results Page</a>.', 'searchwp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						esc_url( add_query_arg( [ 'page' => 'searchwp-templates' ], admin_url( 'admin.php' ) ) )
					);
					?>
				</li>
				<li>
					<?php
					printf(
						wp_kses(
							/* translators: %1$s - SearchWP Algorithm URL. */
							__( 'Fine-tune your <a href="%1$s" target="_blank" rel="noopener noreferrer">Search Engine</a> settings.', 'searchwp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						esc_url( add_query_arg( [ 'page' => 'searchwp-algorithm' ], admin_url( 'admin.php' ) ) )
					);
					?>
				</li>
			</ul>
		</div>

		<div class="swp-flex--col swp-flex--gap12">
			<p class="swp-p"><?php esc_html_e( 'Discover more powerful features by reading our blog:', 'searchwp' ); ?></p>
			<ul>
				<li>
					<?php
					printf(
						wp_kses(
							/* translators: %1$s - Search Forms Documentation URL. */
							__( 'Read Search Forms <a href="%1$s" target="_blank" rel="noopener noreferrer">documentation</a> to unlock its full potential.', 'searchwp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://searchwp.com/documentation/setup/search-forms/'
					);
					?>
				</li>
				<li>
					<?php
					printf(
						wp_kses(
							/* translators: %1$s - Live Autocomplete Article URL. */
							__( 'Check how you can add <a href="%1$s" target="_blank" rel="noopener noreferrer">live autocomplete</a> to your search forms.', 'searchwp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://searchwp.com/how-to-add-wordpress-live-autocomplete-search/'
					);
					?>
				</li>
				<li>
					<?php
					printf(
						wp_kses(
							/* translators: %1$s - Search PDF Article URL. */
							__( 'See how you can make your <a href="%1$s" target="_blank" rel="noopener noreferrer">PDFs searchable</a>.', 'searchwp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://searchwp.com/how-to-make-wordpress-search-pdf-files/'
					);
					?>
				</li>
			</ul>
		</div>

		<p class="swp-p">
			<?php
			printf(
				wp_kses(
					/* translators: %1$s - Searchwp.com Account Support URL. */
					__( 'If you have questions, or need further assistance with the setup, let us know via <a href="%1$s" target="_blank" rel="noopener noreferrer">Support Form</a>!', 'searchwp' ),
					[
						'a' => [
							'href'   => [],
							'rel'    => [],
							'target' => [],
						],
					]
				),
				'https://searchwp.com/account/support/'
			);
			?>
		</p>

		<div id="swp-onboarding-error-msg" class="swp-text-red" style="display: none;"></div>
		<div class="swp-hr"></div>

		<div class="swp-flex--row swp-justify-between">
			<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'searchwp-forms' ], admin_url( 'admin.php' ) ) ); ?>" class="swp-button swp-button--green swp-margin-l-auto"><?php esc_html_e( 'Finish and go to the Search Forms', 'searchwp' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Renders the license upsell notice.
	 *
	 * @since 4.3.14
	 */
	private static function get_license_upsell() {

		$license_type = License::get_type();

		$upsell_text = __( 'Buy SearchWP Pro Today »', 'searchwp' );
		$upsell_url  = 'https://searchwp.com/buy/?utm_source=WordPress&utm_medium=Onboarding+Wizard+License+Field+Upsell+Link&utm_campaign=SearchWP&utm_content=Buy+SearchWP+Pro+Today';
		$bonus_text  = '';

		if ( $license_type === 'standard' ) {
			$upsell_text = __( 'Get SearchWP Pro Today »', 'searchwp' );
			$upsell_url  = 'https://searchwp.com/account/downloads/?utm_source=WordPress&utm_medium=Onboarding+Wizard+License+Field+Upsell+Link&utm_campaign=SearchWP&utm_content=Get+SearchWP+Pro+Today';
			$bonus_text  = __( '<strong>Bonus:</strong> SearchWP Standard users get up to <span class="green">$200 off their upgrade price</span>, automatically applied at checkout!', 'searchwp' );
		}

		if ( in_array( $license_type, [ 'pro', 'agency' ], true ) ) {
			return;
		}

		?>
		<div class="swp-flex--col swp-flex--gap12">

			<?php if ( ! empty( $upsell_text ) ) : ?>
				<p class="swp-margin0"><a href="<?php echo esc_url( $upsell_url ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_html( $upsell_text ); ?>"><?php echo esc_html( $upsell_text ); ?></a></p>
			<?php endif; ?>

			<?php if ( ! empty( $bonus_text ) ) : ?>
				<p class="swp-margin0">
					<?php
					echo wp_kses(
						$bonus_text,
						[
							'strong' => [],
							'span'   => [
								'class' => [],
							],
						]
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders the header logo.
	 *
	 * @since 4.3.14
	 *
	 * @return void
	 */
	private static function header_logo() {
		?>
		<svg fill="none" height="40" viewBox="0 0 186 40" width="186" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
			<clipPath id="a">
				<path d="m0 0h26.2464v40h-26.2464z"/>
			</clipPath>
			<g fill="#456b47">
				<path d="m51.2968 15.3744c-.1125.2272-.225.4544-.45.568-.1126.1136-.3376.1136-.5626.1136-.2251 0-.4501-.1136-.7876-.2272-.2251-.2272-.5626-.3408-1.0127-.568s-.7876-.4544-1.3502-.568c-.4501-.2272-1.1252-.2272-1.8003-.2272s-1.1251.1136-1.5752.2272-.9001.3408-1.1252.6816c-.3375.2272-.5625.568-.6751.9088-.1125.3409-.225.7953-.225 1.2497 0 .568.1125 1.0224.4501 1.4768.3375.3408.7876.6817 1.2377 1.0225.5625.2272 1.1251.4544 1.8002.6816l2.0253.6816c.6751.2272 1.3502.568 2.0253.7953.6751.3408 1.2377.6816 1.8003 1.2496.5626.4544.9001 1.136 1.2377 1.8177.3375.6816.45 1.5904.45 2.6129 0 1.136-.225 2.1584-.5625 3.0673-.3376.9088-.9002 1.8176-1.5753 2.4993-.6751.6816-1.5752 1.2496-2.5879 1.704-1.0126.4544-2.2503.568-3.488.568-.7876 0-1.4627-.1136-2.2503-.2272s-1.4627-.3408-2.1378-.6816c-.6751-.2272-1.3502-.568-1.9128-1.0224-.1125-.1136-.2251-.2272-.4501-.2272-.6751-.5681-.9001-1.4769-.4501-2.2721l.5626-.9089c.1125-.1136.2251-.3408.4501-.3408.225-.1136.3375-.1136.5626-.1136.225 0 .5626.1136.9001.3408.3376.2272.6751.4545 1.1252.7953s.9001.568 1.5752.7952c.5626.2272 1.3502.3408 2.1378.3408 1.2377 0 2.2504-.3408 2.9255-.9088s1.0126-1.4769 1.0126-2.6129c0-.6816-.1125-1.1361-.45-1.5905-.3376-.4544-.7877-.7952-1.2377-1.0224-.5626-.2272-1.1252-.4544-1.8003-.6816s-1.3502-.3408-2.0253-.5681c-.6751-.2272-1.3502-.4544-2.0253-.7952s-1.2377-.6816-1.8003-1.2496c-.5626-.4544-.9001-1.1361-1.2377-1.9313-.3375-.7952-.45-1.7041-.45-2.8401 0-.9088.225-1.7041.5626-2.6129.3375-.7952.9001-1.5905 1.5752-2.2721s1.4627-1.136 2.4754-1.5904c1.0126-.3408 2.1378-.5681 3.3755-.5681 1.4627 0 2.7004.2273 3.9381.6817.7876.3408 1.5752.6816 2.1378 1.136.5626.3408.6751 1.1361.3375 1.7041z"/>
				<path d="m62.4361 17.7601c1.1252 0 2.0253.2272 2.9255.5681.9001.3408 1.6877.9088 2.3628 1.4768s1.1252 1.4769 1.5753 2.4993c.3375 1.0224.5625 2.0449.5625 3.2945v.7952c0 .2273-.1125.3409-.1125.4545-.1125.1136-.225.2272-.3375.2272s-.2251.1136-.4501.1136h-10.5766c.1125 1.8176.5626 3.0673 1.4627 3.8625.7877.7952 1.9128 1.2497 3.263 1.2497.6751 0 1.2377-.1136 1.6878-.2273.4501-.1136.9001-.3408 1.2377-.568.3375-.2272.6751-.3408.9001-.568.225-.1136.5626-.2272.7876-.2272.1125 0 .3376 0 .4501.1136s.225.1136.3375.3408l.2251.3408c.5626.7953.45 1.8177-.3376 2.3857-.1125 0-.1125.1136-.225.1136-.5626.3408-1.1252.6816-1.8003.9088-.5626.2273-1.2377.3409-1.9128.4545s-1.2376.1136-1.8002.1136c-1.2377 0-2.2504-.2272-3.263-.5681-1.0127-.3408-1.9128-1.0224-2.7004-1.8176s-1.3502-1.7041-1.8003-2.8401c-.4501-1.1361-.6751-2.4993-.6751-3.9762 0-1.136.225-2.272.5626-3.2945.3375-1.0224.9001-1.9312 1.5752-2.7265.6751-.7952 1.5753-1.3632 2.5879-1.8176 1.0127-.4545 2.1378-.6817 3.488-.6817zm0 2.9537c-1.2377 0-2.1378.3408-2.8129 1.0225-.6751.6816-1.1251 1.704-1.3502 2.9537h7.6512c0-.568-.1126-1.0225-.2251-1.4769s-.3375-.9088-.6751-1.2496c-.3375-.3408-.6751-.6816-1.1251-.7952-.4501-.1137-.7877-.4545-1.4628-.4545z"/>
				<path d="m85.277 33.5511c0 .9089-.7877 1.7041-1.6878 1.7041h-.225c-.3376 0-.6751-.1136-.9002-.2272-.225-.1136-.3375-.3408-.45-.6817l-.3376-1.2496c-.45.3408-.9001.6816-1.2377 1.0224-.45.3409-.9001.5681-1.2376.7953-.4501.2272-.9002.3408-1.4628.4544-.45.1136-1.0126.1136-1.6877.1136s-1.3502-.1136-2.0253-.3408c-.5626-.2272-1.1252-.4544-1.5752-.9089-.4501-.3408-.7877-.9088-1.0127-1.4768s-.3375-1.2497-.3375-2.0449c0-.6816.1125-1.2496.5625-1.9313.3376-.6816.9002-1.2496 1.6878-1.704s1.8003-.9088 3.1505-1.2497c1.3502-.3408 2.9254-.4544 4.8382-.4544v-1.0224c0-1.1361-.2251-2.0449-.6751-2.6129-.4501-.568-1.2377-.7952-2.1378-.7952-.6751 0-1.2377.1136-1.6878.2272s-.7876.3408-1.1252.568c-.3375.2272-.6751.3408-.9001.568-.3375.1136-.6751.2272-1.0126.2272-.2251 0-.5626-.1136-.6751-.2272-.2251-.1136-.3376-.3408-.4501-.568v-.1136c-.4501-.7952-.225-1.7041.4501-2.1585 1.6877-1.136 3.713-1.8177 5.9633-1.8177 1.0127 0 1.9128.1137 2.7004.4545.7877.3408 1.4628.7952 2.0253 1.3632.5626.568.9002 1.2497 1.2377 2.1585.3376.7952.4501 1.7041.4501 2.7265v9.2019zm-7.9887-.9088c.45 0 .7876 0 1.1251-.1136.3376-.1136.6751-.2272 1.0127-.3408.3375-.1136.6751-.3408.9001-.5681.3376-.2272.5626-.4544.9002-.7952v-2.8401c-1.2377 0-2.2504.1136-3.038.2272s-1.4627.3408-1.9128.568c-.45.2273-.7876.5681-1.0126.7953-.2251.3408-.3376.6816-.3376 1.0224 0 .6816.2251 1.2497.6751 1.5905.4501.3408 1.0127.4544 1.6878.4544z"/>
				<path d="m88.2024 33.8918v-13.8597c0-.9088.7876-1.704 1.6877-1.704h.7877c.45 0 .6751.1136.9001.2272.1125.1136.225.4544.3375.7952l.2251 2.0449c.5626-1.0225 1.3502-1.9313 2.1378-2.4993s1.8003-.9089 2.8129-.9089h.4501c.9001.1136 1.5752 1.0225 1.4627 1.9313l-.3375 1.7041c0 .2272-.1126.3408-.2251.4544s-.225.1136-.4501.1136c-.1125 0-.3375 0-.6751-.1136-.3375-.1136-.6751-.1136-1.1251-.1136-.9002 0-1.5753.2272-2.1378.6816-.5626.4544-1.1252 1.1361-1.5753 2.0449v9.0883c0 .9088-.7876 1.7041-1.6877 1.7041h-.9002c-.9001 0-1.6877-.6817-1.6877-1.5905z"/>
				<path d="m112.619 21.7363c-.113.1136-.225.2272-.338.3408s-.338.1136-.563.1136-.45-.1136-.562-.2272c-.225-.1136-.45-.2272-.675-.4544-.225-.1136-.563-.3408-1.013-.4544-.337-.1137-.9-.2273-1.463-.2273-.675 0-1.35.1136-1.912.3409-.563.2272-1.013.6816-1.351 1.136-.337.4544-.675 1.136-.787 1.8177-.225.6816-.225 1.4768-.225 2.3856 0 .9089.112 1.7041.337 2.4993.225.6817.45 1.3633.788 1.8177.337.4544.788.9088 1.35 1.136.563.2273 1.125.3409 1.8.3409s1.126-.1136 1.576-.2273c.45-.1136.787-.3408 1.012-.568s.563-.3408.675-.568c.225-.1136.45-.2272.675-.2272.338 0 .563.1136.788.3408l.225.3408c.563.6816.45 1.8177-.337 2.3857-.113.1136-.225.2272-.225.2272-.563.3408-1.126.6816-1.688.9088-.563.2273-1.125.3409-1.8.4545-.563.1136-1.238.1136-1.801.1136-1.012 0-2.025-.2272-2.925-.5681-.9-.3408-1.688-1.0224-2.476-1.704-.675-.7952-1.237-1.7041-1.687-2.8401-.4504-1.1361-.5629-2.3857-.5629-3.7489 0-1.2497.225-2.3857.5629-3.5218.337-1.0224.9-2.0448 1.575-2.8401.675-.7952 1.575-1.3632 2.588-1.8176 1.012-.4545 2.25-.6817 3.6-.6817 1.238 0 2.363.2272 3.376.5681.45.2272.787.3408 1.238.6816.787.568 1.012 1.5904.45 2.3857z"/>
				<path d="m115.094 33.8919v-21.5848c0-.9088.787-1.7041 1.688-1.7041h.787c.9 0 1.688.7953 1.688 1.7041v7.9523c.675-.6816 1.35-1.1361 2.138-1.5905.787-.3408 1.687-.568 2.813-.568.9 0 1.8.1136 2.475.4544s1.35.7952 1.8 1.3633c.45.568.9 1.2496 1.125 2.0448.225.7953.338 1.7041.338 2.6129v9.3156c0 .9088-.788 1.704-1.688 1.704h-.787c-.901 0-1.688-.7952-1.688-1.704v-9.3156c0-1.0224-.225-1.8176-.675-2.3857-.45-.568-1.238-.9088-2.138-.9088-.788 0-1.463.2272-2.138.568-.562.3408-1.125.7953-1.688 1.2497v10.7924c0 .9088-.787 1.704-1.687 1.704h-.788c-.788-.1136-1.575-.7952-1.575-1.704z"/>
				<path d="m132.197 12.9886c-.338-1.136.45-2.1585 1.575-2.1585h1.575c.45 0 .675.1136 1.013.2272.225.2273.45.4545.562.7953l4.163 14.8821c.113.3408.225.7952.225 1.1361.113.4544.113.9088.225 1.3632.113-.4544.225-.9088.338-1.3632.112-.4545.225-.7953.338-1.1361l4.838-14.8821c.112-.2272.225-.4544.562-.6816.225-.2273.563-.3409 1.013-.3409h1.35c.45 0 .675.1136 1.013.2272.225.2273.45.4545.562.7953l4.839 14.8821c.225.6816.45 1.5905.675 2.3857.112-.4544.112-.9088.225-1.2496.112-.4545.225-.7953.225-1.1361l4.163-14.8821c.112-.3408.225-.568.562-.6816.226-.2273.563-.3409 1.013-.3409h1.238c1.125 0 1.913 1.1361 1.575 2.1585l-6.526 21.3576c-.225.6816-.9 1.2496-1.575 1.2496h-1.688c-.788 0-1.35-.4544-1.575-1.136l-5.176-15.9046c-.112-.2272-.112-.4544-.225-.6816-.112-.2272-.112-.568-.225-.7952-.112.3408-.112.568-.225.7952s-.113.4544-.225.6816l-5.063 15.791c-.225.6816-.9 1.136-1.576 1.136h-1.687c-.788 0-1.35-.4544-1.576-1.2496z"/>
				<path d="m172.69 26.8484v7.0435c0 .9088-.788 1.704-1.688 1.704h-1.238c-.9 0-1.687-.7952-1.687-1.704v-21.4712c0-.9089.787-1.7041 1.687-1.7041h6.301c1.688 0 3.038.2272 4.276.568s2.138.9089 2.925 1.5905c.788.6816 1.351 1.4768 1.688 2.4993.338 1.0224.563 2.0449.563 3.1809 0 1.2496-.225 2.2721-.563 3.2945-.45 1.0225-1.012 1.8177-1.8 2.6129-.788.6816-1.8 1.2497-2.926 1.7041-1.237.4544-2.587.568-4.163.568h-3.375zm0-3.6353h3.375c.788 0 1.576-.1136 2.138-.3408.675-.2273 1.125-.5681 1.575-.9089s.675-.9088.901-1.4768c.225-.5681.337-1.2497.337-1.9313s-.112-1.2496-.337-1.8177c-.226-.568-.563-1.0224-.901-1.3632-.45-.3408-.9-.6816-1.575-.9089-.675-.2272-1.35-.3408-2.138-.3408h-3.375z"/>
			</g>
			<g clip-path="url(#a)">
				<g clip-rule="evenodd" fill="#456b47" fill-rule="evenodd">
					<path d="m24.5846 16.0458c0-.7083-.6797-1.4326-1.6619-1.4326v-1.7192c1.7686 0 3.3811 1.3387 3.3811 3.1518v16.5043c0 1.8132-1.6125 3.1519-3.3811 3.1519h-2.4068v-1.7192h2.4068c.9822 0 1.6619-.7243 1.6619-1.4327z"/>
					<path d="m.057373 16.0458c0-1.8131 1.612567-3.1518 3.381087-3.1518v1.7192c-.98219 0-1.66189.7243-1.66189 1.4326v16.5043c0 .7084.6797 1.4327 1.66189 1.4327h2.29226v1.7192h-2.29226c-1.76852 0-3.381087-1.3387-3.381087-3.1519z"/>
					<path d="m5.94932 16.2056c.25995-.6058.52876-1.2322.83483-1.8954l1.56096.7205c-.26042.5642-.51794 1.1623-.77746 1.765-.38453.893-.77343 1.7962-1.18259 2.6145-.87996 1.7599-1.36619 3.5097-1.06846 5.1968l.00446.0254.00295.0255c.31338 2.716 1.65716 4.9094 4.10687 6.6135l-.98178 1.4113c-2.81518-1.9584-4.45017-4.5703-4.83002-7.8025-.38037-2.2007.27811-4.3385 1.22828-6.2389.40036-.8007.7427-1.5985 1.10196-2.4357z"/>
					<path d="m21.13 17.6879c.1672.3555.3376.718.5103 1.0923l.0036.0078.0035.0078c.8235 1.8824 1.467 3.8834 1.2129 6.1702l-.0008.0075c-.376 3.1333-2.0144 5.7413-4.8125 7.8094l-1.0219-1.3825c2.473-1.8278 3.8144-4.0323 4.127-6.628.2029-1.8351-.2978-3.4985-1.0763-5.2796-.1596-.3457-.3213-.6894-.4829-1.0329-.519-1.1035-1.0373-2.2055-1.4838-3.3662l1.6046-.6172c.422 1.0971.9038 2.1216 1.4163 3.2114z"/>
					<path d="m4.46831 17.2516c.28355-.3807.82208-.4595 1.20285-.176l16.16044 12.0344c.3808.2835.4596.8221.176 1.2028-.2835.3808-.822.4596-1.2028.1761l-16.16046-12.0344c-.38077-.2836-.45958-.8221-.17603-1.2029z"/>
					<path d="m22.0076 17.2516c.2836.3808.2048.9193-.176 1.2029l-16.16044 12.0344c-.38077.2835-.9193.2047-1.20285-.1761-.28355-.3807-.20474-.9193.17603-1.2028l16.16046-12.0344c.3808-.2835.9193-.2047 1.2028.176z"/>
				</g>
				<path d="m18.1089 11.2321h-9.74213c-.34384 0-.57307-.2292-.57307-.5731v-1.48995c0-1.60458 1.26075-2.75072 2.7507-2.75072h5.2722c1.6046 0 2.7507 1.26075 2.7507 2.75072v1.37535c.1147.4585-.1146.6877-.4584.6877z" fill="#77a872"/>
				<path clip-rule="evenodd" d="m10.5444 7.27791c-1.03889 0-1.89112.7846-1.89112 1.89112v1.20347h9.05442v-1.20347c0-1.03889-.7846-1.89112-1.8911-1.89112zm-3.61032 1.89112c0-2.10264 1.66926-3.61031 3.61032-3.61031h5.2722c2.1026 0 3.6103 1.66925 3.6103 3.61031v1.28517c.0656.3566.0348.7697-.2292 1.1217-.2951.3934-.7352.5158-1.0888.5158h-9.74215c-.36726 0-.74011-.1262-1.0233-.4094-.2832-.2832-.40937-.656-.40937-1.0233z" fill="#456b47" fill-rule="evenodd"/>
				<path clip-rule="evenodd" d="m5.04306 12.3209c-.32755 0-.51576.1883-.51576.5158v.6304h17.192v-.6304c0-.3275-.1882-.5158-.5158-.5158zm-2.23495.5158c0-1.277.95792-2.235 2.23495-2.235h16.16044c1.2771 0 2.235.958 2.235 2.235v.9169c0 .3673-.1262.7401-.4094 1.0233s-.656.4094-1.0233.4094h-17.76503c-.36726 0-.74011-.1262-1.0233-.4094s-.40936-.656-.40936-1.0233z" fill="#456b47" fill-rule="evenodd"/>
				<path clip-rule="evenodd" d="m7.73657 5.61605c0-3.11085 2.44793-5.558738 5.55873-5.558738 3.1109 0 5.5587 2.447888 5.5587 5.558738 0 .46159-.1409 1.12803-.2548 1.58384l-.2599 1.0396-.9585-.47923c-.4293-.21463-.854-.3677-1.2202-.3677h-5.8452c-.43253 0-.69723.07877-.97424.28653l-1.09117.81838-.2675-1.33748c-.02047-.10234-.04319-.21143-.06586-.32022-.03398-.16313-.06783-.32558-.0937-.46359-.04491-.23951-.08636-.50482-.08636-.76013zm5.55873-3.83954c-2.1613 0-3.83953 1.67818-3.83953 3.83954 0 .03853.003.08603.00978.14544.27521-.06286.55735-.08813.84985-.08813h5.8452c.3357 0 .6605.05655.9604.1404.009-.07754.0139-.14461.0139-.19771 0-2.16136-1.6782-3.83954-3.8396-3.83954z" fill="#456b47" fill-rule="evenodd"/>
				<path d="m19.9427 39.1977h-13.63894c-1.14613 0-2.06304-.9169-2.06304-2.063v-2.7508c0-1.2607 1.03152-2.2922 2.29227-2.2922h13.18051c1.2607 0 2.2923 1.0315 2.2923 2.2922v2.7508c0 1.1461-.9169 2.063-2.0631 2.063z" fill="#77a872"/>
				<path clip-rule="evenodd" d="m6.53297 32.9513c-.78601 0-1.43267.6467-1.43267 1.4327v2.7507c0 .6714.53205 1.2034 1.20344 1.2034h13.63896c.6714 0 1.2034-.532 1.2034-1.2034v-2.7507c0-.786-.6466-1.4327-1.4326-1.4327zm-3.15187 1.4327c0-1.7355 1.41638-3.1519 3.15187-3.1519h13.18053c1.7355 0 3.1518 1.4164 3.1518 3.1519v2.7507c0 1.6209-1.3017 2.9226-2.9226 2.9226h-13.63896c-1.62088 0-2.92264-1.3017-2.92264-2.9226z" fill="#456b47" fill-rule="evenodd"/>
			</g>
		</svg>
		<?php
	}

	/**
	 * Load next page AJAX callback.
	 *
	 * @since 4.3.14
	 */
	public static function ajax_load_page() {

		// Run a security check.
		Utils::check_ajax_permissions();

		// Input data is sanitized in self::set_inputs_data().
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data = isset( $_POST['data'] ) ? json_decode( wp_unslash( $_POST['data'] ), true ) : null;

		if ( empty( $data ) ) {
			wp_send_json_error();
		}

		$response = [];

		if ( ! isset( $data['action'], $data['current_page'] ) ) {
			wp_send_json_error();
		}

		$subpage = self::get_subpage_to_load( $data );

		self::set_inputs_data( $data );

		if ( empty( $subpage ) ) {
			$response['target_url'] = esc_url_raw( add_query_arg( [ 'page' => self::$slug ], admin_url( 'index.php' ) ) );
		} else {
			$response['target_url'] = esc_url_raw(
				add_query_arg(
					[
						'page'    => self::$slug,
						'subpage' => $subpage,
					],
					admin_url( 'index.php' )
				)
			);
		}

		wp_send_json_success( $response );
	}

	/**
	 * Sanitize and set inputs data received from the frontend.
	 *
	 * @since 4.3.14
	 *
	 * @param array $data Data received from the frontend.
	 */
	private static function set_inputs_data( $data ) {

		if ( empty( $data['inputs'] ) || ! is_array( $data['inputs'] ) ) {
			return;
		}

		$wizard_data = Settings::get_single( 'onboarding_wizard', 'array' );

		foreach ( $data['inputs'] as $input_group_name => $input_group ) {
			$wizard_data[ $input_group_name ] = array_filter(
				$input_group,
				function ( $input_value, $input_name ) {
					return is_bool( $input_value ) && $input_name === sanitize_key( $input_name );
				},
				ARRAY_FILTER_USE_BOTH
			);
		}

		Settings::update( 'onboarding_wizard', $wizard_data );
	}

	/**
	 * Get a subpage to load next.
	 *
	 * @since 4.3.14
	 *
	 * @param array $data Data received from the frontend.
	 *
	 * @return string Subpage slug.
	 */
	private static function get_subpage_to_load( $data ) {

		$subpage = '';

		if ( $data['current_page'] === 'welcome' ) {
			$subpage = self::get_subpage_to_load_after_welcome( $data );
		}

		if ( $data['current_page'] === 'engines' ) {
			$subpage = self::get_subpage_to_load_after_engines( $data );
		}

		if ( $data['current_page'] === 'integrations' ) {
			$subpage = self::get_subpage_to_load_after_integrations( $data );
		}

		if ( $data['current_page'] === 'features' ) {
			$subpage = self::get_subpage_to_load_after_features( $data );
		}

		if ( $data['current_page'] === 'license' ) {
			$subpage = self::get_subpage_to_load_after_license( $data );
		}

		return $subpage;
	}

	/**
	 * Get a subpage to load after Welcome subpage.
	 *
	 * @since 4.3.14
	 *
	 * @param array $data Data received from the frontend.
	 *
	 * @return string Subpage slug.
	 */
	private static function get_subpage_to_load_after_welcome( $data ) {

		$subpage = '';

		if ( $data['action'] === 'accept_default' ) {
			self::update_forms();
			$subpage = 'integrations';
		}

		if ( $data['action'] === 'change_default' ) {
			$subpage = 'engines';
		}

		return $subpage;
	}

	/**
	 * Get a subpage to load after Engines subpage.
	 *
	 * @since 4.3.14
	 *
	 * @param array $data Data received from the frontend.
	 *
	 * @return string Subpage slug.
	 */
	private static function get_subpage_to_load_after_engines( $data ) {

		$subpage = '';

		if ( $data['action'] === 'next' ) {
			self::update_engines( $data['inputs'] );
			self::update_forms();
			$subpage = 'integrations';
		}

		return $subpage;
	}

	/**
	 * Get a subpage to load after Integrations subpage.
	 *
	 * @since 4.3.14
	 *
	 * @param array $data Data received from the frontend.
	 *
	 * @return string Subpage slug.
	 */
	private static function get_subpage_to_load_after_integrations( $data ) {

		$subpage = '';

		if ( $data['action'] === 'next' ) {
			$subpage = 'features';
		}

		return $subpage;
	}

	/**
	 * Get a subpage to load after Features subpage.
	 *
	 * @since 4.3.14
	 *
	 * @param array $data Data received from the frontend.
	 *
	 * @return string Subpage slug.
	 */
	private static function get_subpage_to_load_after_features( $data ) {

		$subpage = '';

		if ( $data['action'] === 'next' ) {
			$subpage = 'license';
		}
		if ( $data['action'] === 'back' ) {
			$subpage = 'integrations';
		}

		return $subpage;
	}

	/**
	 * Get a subpage to load after License subpage.
	 *
	 * @since 4.3.14
	 *
	 * @param array $data Data received from the frontend.
	 *
	 * @return string Subpage slug.
	 */
	private static function get_subpage_to_load_after_license( $data ) {

		$subpage = '';

		if ( $data['action'] === 'install' ) {
			self::update_extensions();
			$subpage = 'finish';
		}
		if ( $data['action'] === 'skip' ) {
			$subpage = 'finish';
		}
		if ( $data['action'] === 'back' ) {
			$subpage = 'features';
		}

		return $subpage;
	}

	/**
	 * Update engines.
	 *
	 * @since 4.3.14
	 *
	 * @param array $data Data received from the frontend.
	 */
	private static function update_engines( $data ) {

		$engines_config         = json_decode( wp_json_encode( Settings::get_engines() ), true );
		$updated_engines_config = $engines_config;

		if ( ! empty( $data['default_sources'] ) ) {
			$updated_engines_config = self::update_engines_config_default( $updated_engines_config, $data );
		}

		if ( ! empty( $data['custom_sources'] ) ) {
			$updated_engines_config = self::update_engines_config_supplemental( $updated_engines_config, $data );
		}

		if ( self::is_any_engines_sources_changed( $engines_config, $updated_engines_config ) ) {
			Settings::update_engines_config( $updated_engines_config );
			EnginesView::remove_invalid_index_content( $engines_config, $updated_engines_config );
			\SearchWP::$index->reset();
		}
	}

	/**
	 * Check if sources of an engine were changed.
	 *
	 * @since 4.3.14
	 *
	 * @param array $engine_config         Original engine config.
	 * @param array $updated_engine_config Updated engine config.
	 *
	 * @return bool
	 */
	private static function is_engine_sources_changed( $engine_config, $updated_engine_config ) {

		if ( empty( $engine_config['sources'] ) && empty( $updated_engine_config['sources'] ) ) {
			return false;
		}

		if ( empty( $engine_config['sources'] ) && ! empty( $updated_engine_config['sources'] ) ) {
			return true;
		}

		if ( ! empty( $engine_config['sources'] ) && empty( $updated_engine_config['sources'] ) ) {
			return true;
		}

		return array_diff_key( $engine_config['sources'], $updated_engine_config['sources'] ) || array_diff_key( $updated_engine_config['sources'], $engine_config['sources'] );
	}

	/**
	 * Check if sources of the default or supplemental engines were changed.
	 *
	 * @since 4.3.14
	 *
	 * @param array $engines_config         Original engines config.
	 * @param array $updated_engines_config Updated engines config.
	 *
	 * @return bool
	 */
	private static function is_any_engines_sources_changed( $engines_config, $updated_engines_config ) {

		foreach ( [ 'default', 'supplemental' ] as $engine ) {

			if ( empty( $engines_config[ $engine ] ) && ! empty( $updated_engines_config[ $engine ] ) ) {
				return true;
			}

			if ( ! empty( $engines_config[ $engine ] ) && empty( $updated_engines_config[ $engine ] ) ) {
				return true;
			}

			if (
				! empty( $engines_config[ $engine ] ) &&
				! empty( $updated_engines_config[ $engine ] ) &&
				self::is_engine_sources_changed( $engines_config[ $engine ], $updated_engines_config[ $engine ] )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Update default engine's config.
	 *
	 * @since 4.3.14
	 *
	 * @param array $engines_config All engines configs.
	 * @param array $data           Data received from the frontend.
	 *
	 * @return array All engines configs.
	 */
	private static function update_engines_config_default( $engines_config, $data ) {

		$engine_config = ! empty( $engines_config['default'] ) ? $engines_config['default'] : [ 'sources' => [] ];

		$engines_config['default'] = self::get_updated_engine_config( 'default', $engine_config, $data['default_sources'] );

		return $engines_config;
	}

	/**
	 * Update supplemental engine's config.
	 *
	 * @since 4.3.14
	 *
	 * @param array $engines_config All engines configs.
	 * @param array $data           Data received from the frontend.
	 *
	 * @return array All engines configs.
	 */
	private static function update_engines_config_supplemental( $engines_config, $data ) {

		$engine_config              = ! empty( $engines_config['supplemental'] ) ? $engines_config['supplemental'] : [ 'sources' => [] ];
		$combined_sources           = ! empty( $data['default_sources'] ) ? array_merge( $data['default_sources'], $data['custom_sources'] ) : $data['custom_sources'];
		$is_custom_sources_selected = ! empty( array_keys( array_filter( $data['custom_sources'] ) ) );

		// Create supplemental engine only if it doesn't exist and custom sources are selected.
		if ( empty( $engine_config['sources'] ) && $is_custom_sources_selected ) {
			$engines_config['supplemental'] = self::get_updated_engine_config( 'supplemental', $engine_config, $combined_sources );
		}

		// Update supplemental engine if it exists and custom sources are selected.
		if ( ! empty( $engine_config['sources'] ) && $is_custom_sources_selected ) {
			$updated_engine_config = self::get_updated_engine_config( 'supplemental', $engine_config, $combined_sources );
			if ( self::is_engine_sources_changed( $engine_config, $updated_engine_config ) ) {
				$engines_config['supplemental'] = $updated_engine_config;
			}
		}

		// Delete supplemental engine if it exists and all sources are unselected.
		if ( ! empty( $engine_config['sources'] ) && ! $is_custom_sources_selected ) {
			unset( $engines_config['supplemental'] ); // Delete supplemental engine if no sources are selected.
		}

		return $engines_config;
	}

	/**
	 * Get the engine config updated with a new set of sources.
	 *
	 * @since 4.3.14
	 *
	 * @param string $engine_name   Name of the engine to update.
	 * @param array  $engine_config Engine config.
	 * @param array  $sources_list  Sources list in a 'source.name' => bool format.
	 */
	private static function get_updated_engine_config( $engine_name, $engine_config, $sources_list ) {

		$available_sources = \SearchWP::$index->get_default_sources();

		foreach ( $sources_list as $source_name => $source_is_enabled ) {
			if ( ! array_key_exists( $source_name, $available_sources ) ) {
				continue;
			}
			if ( $source_is_enabled && ! array_key_exists( $source_name, $engine_config['sources'] ) ) {
				self::set_source_default_attributes( $available_sources[ $source_name ] );
				$engine_config['sources'][ $source_name ] = json_decode( wp_json_encode( $available_sources[ $source_name ] ), true );
			}
			if ( ! $source_is_enabled && array_key_exists( $source_name, $engine_config['sources'] ) ) {
				unset( $engine_config['sources'][ $source_name ] );
			}
		}

		$engine_config['label']    = empty( $engine_config['label'] ) ? '' : sanitize_text_field( $engine_config['label'] );
		$engine_config['settings'] = empty( $engine_config['settings'] ) ? [] : $engine_config['settings'];

		// Build an Engine model.
		$engine = new Engine( $engine_name, Utils::normalize_engine_config( $engine_config ) );

		// Extract a (validated) config from the model.
		$updated_config = Utils::normalize_engine_config( json_decode( wp_json_encode( $engine ), true ) );

		unset( $updated_config['name'] );

		return $updated_config;
	}

	/**
	 * Set default attributes for the source.
	 * The logic comes from SearchWP\Index\Controller->get_default_sources().
	 *
	 * @since 4.3.14
	 *
	 * @param Source $source Source object.
	 */
	private static function set_source_default_attributes( $source ) {

		$attributes = $source->get_attributes();

		foreach ( $attributes as $attribute ) {

			$attribute_default = $attribute->get_default();

			if (
				empty( $attribute_default ) ||
				! empty( $attribute->options_ajax_tag ) // Skip if options are provided via AJAX.
			) {
				continue;
			}

			$options = $attribute->get_options();

			if ( ! empty( $options ) && is_array( $options ) ) {
				$settings = call_user_func_array(
					'array_merge',
					array_map(
						function ( $option ) use ( $attribute_default ) {
							return [ $option->get_value() => $attribute_default ];
						},
						$options
					)
				);
			} else {
				$settings = $attribute_default;
			}

			$attribute->set_settings( $settings );
		}
	}

	/**
	 * Create Search Forms for the search Engines.
	 *
	 * @since 4.3.14
	 */
	private static function update_forms() {

		$forms = Storage::get_all();

		$allowed_engines  = [ 'default', 'supplemental' ];
		$engines_settings = Settings::_get_engines_settings();
		$engines_settings = array_intersect_key( $engines_settings, array_flip( $allowed_engines ) );

		foreach ( $engines_settings as $engine => $engine_settings ) {
			if ( empty( $engine_settings['label'] ) ) {
				continue;
			}

			$form_title     = $engine_settings['label'] . ' Engine Form';
			$existing_forms = array_filter(
				$forms,
				function ( $form ) use ( $form_title ) {
					return ! empty( $form['title'] ) && $form['title'] === $form_title;
				}
			);

			if ( empty( $existing_forms ) ) {
				Storage::add(
					[
						'title'      => $form_title,
						'engine'     => $engine,
						'input_name' => $engine === 'default' ? 's' : 'swps',
					]
				);
			}
		}
	}

	/**
	 * Activate/install extensions.
	 *
	 * @since 4.3.14
	 */
	private static function update_extensions() {

		$wizard_data = Settings::get_single( 'onboarding_wizard', 'array' );
		$extensions  = Extensions::get_all();

		$wizard_data['extensions_activated'] = [];

		$integrations = isset( $wizard_data['integrations'] ) ? array_keys( array_filter( $wizard_data['integrations'] ) ) : [];
		$features     = isset( $wizard_data['features'] ) ? array_keys( array_filter( $wizard_data['features'] ) ) : [];

		$extensions_to_activate = array_merge( $integrations, $features );

		// License check is performed while setting extension statuses in Extension::set_statuses().
		foreach ( $extensions_to_activate as $extension_to_activate ) {
			$activation_result = null;

			if ( $extensions[ $extension_to_activate ]['plugin_status'] === 'missing' ) {
				$activation_result = self::activate_extension( self::install_extension( $extension_to_activate ) );
			}
			if ( $extensions[ $extension_to_activate ]['plugin_status'] === 'inactive' ) {
				$activation_result = self::activate_extension( $extension_to_activate );
			}

			if ( ! is_null( $activation_result ) && ! is_wp_error( $activation_result ) ) {
				$wizard_data['extensions_activated'][] = $extension_to_activate;
			}
		}

		Settings::update( 'onboarding_wizard', $wizard_data );
	}

	/**
	 * Install extension.
	 *
	 * @since 4.3.14
	 *
	 * @param string $extension_slug Slug of the extension to install.
	 *
	 * @return \WP_Error|string Returns plugin basename on success.
	 */
	private static function install_extension( $extension_slug ) {

		// TODO: Unify ajax plugin (un)installation across AboutUsView, ExtensionsView and Extensions.

		$generic_error = esc_html__( 'There was an error while performing your request.', 'searchwp' );

		// Check if new installations are allowed.
		if ( ! Extensions::current_user_can_install() ) {
			return new \WP_Error( 'install_extension', $generic_error, 'general_error' );
		}

		$error = esc_html__( 'Could not install the extension. Please download it from searchwp.com and install it manually.', 'searchwp' );

		if ( empty( $extension_slug ) ) {
			return new \WP_Error( 'install_extension', $error, 'no_extension_slug_provided' );
		}

		$extension = Extensions::get( sanitize_key( wp_unslash( $extension_slug ) ) );

		if ( empty( $extension ) ) {
			return new \WP_Error( 'install_extension', $error, 'not_in_searchwp_extensions_list' );
		}

		$download_link = Extensions::get_download_url( $extension );

		if ( empty( $download_link ) ) {
			return new \WP_Error( 'install_extension', $error, 'no_download_link' );
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $download_link, [ 'overwrite_package' => true ] );

		$install_error = self::get_extension_install_error( $result, $skin );

		if ( is_wp_error( $install_error ) ) {
			return $install_error;
		}

		// Flush the cache and return the newly installed plugin basename.
		wp_cache_flush();

		$plugin_basename = $upgrader->plugin_info();

		if ( empty( $plugin_basename ) ) {
			return new \WP_Error( 'install_extension', $error, 'no_plugin_basename' );
		}

		return $extension_slug;
	}

	/**
	 * Process extension install errors if any.
	 *
	 * @since 4.3.14
	 *
	 * @param bool|\WP_Error         $result Uprgrader install method result.
	 * @param \WP_Ajax_Upgrader_Skin $skin   AJAX upgrader skin.
	 *
	 * @return \WP_Error|null
	 */
	private static function get_extension_install_error( $result, $skin ) {

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( is_wp_error( $skin->result ) ) {
			return $skin->result;
		}

		if ( $skin->get_errors()->has_errors() ) {
			return $skin->get_errors();
		}

		if ( is_null( $result ) ) {
			global $wp_filesystem;

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof \WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
				return $wp_filesystem->errors;
			}
		}

		return null;
	}

	/**
	 * Activate extension.
	 *
	 * @since 4.3.14
	 *
	 * @param string|\WP_Error $extension_slug Slug of the extension to activate.
	 *
	 * @return true|\WP_Error True when finished or WP_Error if there were errors during a plugin activation.
	 */
	private static function activate_extension( $extension_slug ) {

		if ( is_wp_error( $extension_slug ) ) {
			return $extension_slug;
		}

		// Check for permissions.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return new \WP_Error( 'activate_extension', esc_html__( 'Plugin activation is disabled for you on this site.', 'searchwp' ), 'user_cannot_activate_plugins' );
		}

		$error = esc_html__( 'Could not activate the extension. Please activate it on the Plugins page.', 'searchwp' );

		if ( empty( $extension_slug ) ) {
			return new \WP_Error( 'activate_extension', $error, 'no_extension_slug_provided' );
		}

		$extension = Extensions::get( sanitize_key( $extension_slug ) );

		if ( empty( $extension['file_name'] ) ) {
			return new \WP_Error( 'activate_extension', $error, 'not_in_searchwp_extensions_list' );
		}

		$result = activate_plugins( $extension['file_name'] );

		// Prevent automatic Live Ajax activation redirect.
		if ( $result === true && $extension_slug === 'searchwp-live-ajax-search' ) {
			delete_transient( 'searchwp_live_search_activation_redirect' );
		}

		return $result;
	}
}
