<?php

/**
 * SearchWP OptionsView.
 *
 * @package SearchWP
 * @author  Jon Christopher
 */

namespace SearchWP\Admin;

use SearchWP\Utils;
use SearchWP\Settings;
use SearchWP\Statistics;
use SearchWP\Admin\Views\EnginesView;
use SearchWP\Admin\Views\SupportView;
use SearchWP\Admin\Views\SettingsView;
use SearchWP\Admin\Views\AdvancedView;
use SearchWP\Admin\Views\StatisticsView;

/**
 * Class OptionsView is responsible for implementing the options screen into the WordPress Admin area.
 *
 * @since 4.0
 */
class OptionsView {

	/**
	 * Slug for this view.
	 *
	 * @since 4.0
	 * @var string
	 */
	private static $slug;

	private $extensions;

	/**
	 * OptionsView constructor.
	 *
	 * @since 4.0
	 */
	function __construct() {
		self::$slug = Utils::$slug;

		add_action( 'admin_menu', [ __CLASS__, 'add' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ], 999 );

		add_action( 'network_admin_menu', function() {
			add_menu_page(
				'SearchWP',
				'SearchWP',
				Settings::get_capability(),
				self::$slug,
				function() {
					do_action( 'searchwp\debug\log', 'Displaying network options page', 'settings' );
					?>
					<div class="wrap">
						<div style="max-width: 60em;">
							<h1>SearchWP</h1>
							<?php
							echo wp_kses(
								__( '<p>Cross-site searches are possible in SearchWP. Any Engine from any site can be used for a cross-site search.</p><p><strong>Note</strong>: SearchWP\'s Engines control what is indexed on each sub-site. If the Engine you are using to perform the search has different Sources/Attributes/Rules than the Engine(s) on the sub-sites you are searching the <em>results may not be accurate</em>.</p><p>For example: if Posts have been added to the Engine you are using for the search, but a sub-site does not have an Engine with Posts enabled, <strong>that sub-site will not return Posts</strong>.</p><p>For a comprehensive cross-site search, ensure that <em>all sites</em> share a similar configuration and applicable Engine.</p>', 'searchwp' ),
								[
									'p'      => [],
									'strong' => [],
									'em'     => [],
								]
							);
							?>
						</div>
					</div>
					<?php
				}
			);
		} );

		// Add internal tabs.
		do_action( 'searchwp\settings\nav\before' );

		if ( apply_filters( 'searchwp\settings\nav\engines', true ) ) {
			new EnginesView();
			do_action( 'searchwp\settings\nav\engines' );
		}

		if ( apply_filters( 'searchwp\settings\nav\settings', true ) ) {
			new SettingsView();
			do_action( 'searchwp\settings\nav\settings' );
		}

		if ( apply_filters( 'searchwp\settings\nav\advanced', true ) ) {
			new AdvancedView();
			do_action( 'searchwp\settings\nav\advanced' );
		}

		if ( apply_filters( 'searchwp\settings\nav\statistics', true ) ) {
			new StatisticsView();
			do_action( 'searchwp\settings\nav\statistics' );
		}

		if ( apply_filters( 'searchwp\settings\nav\support', true ) ) {
			new SupportView();
			do_action( 'searchwp\settings\nav\support' );
		}

		do_action( 'searchwp\settings\nav\after' );

		// Add Extensions Tab and callbacks.
		// TODO: Extension handling can (should) be its own class.
		$this->init_extensions();

		do_action( 'searchwp\settings\init' );
	}

	/**
	 * Initialize all registered Extensions.
	 *
	 * @since 4.0
	 * @return void
	 */
	private function init_extensions() {
		$this->prime_extensions();

		$extensions = array_filter( (array) $this->extensions, function( $extension ) {
			return ! empty( $extension->public );
		} );

		if ( ! empty( $extensions ) ) {
			new NavTab([
				'tab'   => 'extensions',
				'label' => __( 'Extensions', 'searchwp' ),
				'icon'  => 'dashicons dashicons-arrow-down',
			]);
		}

		add_action( 'searchwp\settings\view\extensions', [ $this, 'render_extension_view' ] );
		add_action( 'searchwp\settings\footer',          [ $this, 'render_extensions_dropdown' ] );
	}

	/**
	 * Renders the Extensions dropdown on the Options screen.
	 *
	 * @since 4.0
	 * @return void
	 */
	public function render_extensions_dropdown() {
		$extensions = array_filter( (array) $this->extensions, function( $extension ) {
			return ! empty( $extension->public );
		} );

		if ( empty( $extensions ) ) {
			return;
		}

		?>
		<div id="searchwp-extensions-dropdown">
			<ul class="swp-dropdown-menu">
				<?php foreach ( $extensions as $extension ) : ?>
					<?php if ( ! empty( $extension->public ) && isset( $extension->slug ) && isset( $extension->name ) ) : ?>
						<?php
						$the_link = add_query_arg(
							array(
								'page'      => 'searchwp',
								'tab'       => 'extensions',
								'extension' => $extension->slug,
							),
							admin_url( 'options-general.php' )
						);
						?>
						<li><a href="<?php echo esc_url( $the_link ); ?>"><?php echo esc_html( $extension->name ); ?></a></li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		</div>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			$('.searchwp-settings-nav-tab-extensions').after($('#searchwp-extensions-dropdown').clone());
		});
			jQuery(document).ready(function($){
				var $extensions_toggler = $('.searchwp-settings-nav-tab-extensions');
				var $extensions_dropdown = $('#searchwp-extensions-dropdown');

				$extensions_dropdown.hide();
				$extensions_toggler.data('showing',false);

				// Bind the click.
				$extensions_toggler.click(function(e){
					e.preventDefault();
					if ($extensions_toggler.data('showing')){
						$extensions_dropdown.hide();
						$extensions_toggler.data('showing',false);
						$extensions_toggler.removeClass('searchwp-showing-dropdown');
						$extensions_dropdown.removeClass('searchwp-sub-menu-active');
					} else {
						if ($extensions_toggler.hasClass('nav-tab-active')) {
							$extensions_dropdown.addClass('searchwp-sub-menu-active');
						} else {
							$extensions_dropdown.removeClass('searchwp-sub-menu-active');
						}
						$extensions_dropdown.show();
						$extensions_toggler.data('showing',true);
						$extensions_toggler.addClass('searchwp-showing-dropdown');
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Renders the view for an Extension.
	 *
	 * @since 4.0
	 * @return void
	 */
	public function render_extension_view() {
		if ( empty( $this->extensions ) || ! isset( $_GET['extension'] ) ) {
			return;
		}

		// Find out which extension we're working with.
		$extension = array_filter( $this->extensions, function( $attributes, $extension ) {
			return isset( $attributes->slug ) && $attributes->slug === $_GET['extension'] && method_exists( $this->extensions[ $extension ], 'view' );
		}, ARRAY_FILTER_USE_BOTH );

		if ( empty( $extension ) ) {
			return;
		}

		reset( $extension );
		$extension = key( $extension );
		$attributes = $this->extensions[ $extension ];
		?>
		<div class="wrap" id="searchwp-<?php echo esc_attr( $attributes->slug ); ?>-wrapper">
			<div id="icon-options-general" class="icon32"><br /></div>
			<div class="<?php echo esc_attr( $attributes->slug ); ?>-container">
				<h2>SearchWP <?php echo esc_html( $attributes->name ); ?></h2>
				<?php $this->extensions[ $extension ]->view(); ?>
			</div>
			<p class="searchwp-extension-back">
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=searchwp' ) ); ?>"><?php esc_html_e( 'Back to SearchWP Settings', 'searchwp' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Prime and prepare registered Extensions.
	 *
	 * @since 4.0
	 * @return void
	 */
	private function prime_extensions() {
		$extensions = apply_filters( 'searchwp\extensions', [] );

		if ( ! is_array( $extensions ) || empty( $extensions ) ) {
			return;
		}

		foreach ( $extensions as $extension => $path ) {
			$class_name = 'SearchWP' . $extension;

			if ( ! class_exists( $class_name ) && file_exists( $path ) ) {
				include_once( $path );
			}

			$this->extensions[ $extension ] = new $class_name( $this->extensions );

			// Add plugin row action.
			if ( isset( $this->extensions[ $extension ]->min_searchwp_version )
				&& version_compare( SEARCHWP_VERSION, $this->extensions[ $extension ]->min_searchwp_version, '<' ) ) {
				do_action( 'searchwp\debug\log', 'after_plugin_row_' . plugin_basename( $path ) );
				add_action( 'after_plugin_row_' . plugin_basename( $path ), [ $this, 'plugin_row' ], 11, 3 );
			}
		}
	}

	/**
	 * Enqueue the assets related to the OptionsView.
	 *
	 * @since 4.0
	 * @param $hook
	 */
	public static function assets( $hook ) {
		if ( 'settings_page_' . self::$slug !== $hook ) {
			return;
		}

		wp_enqueue_style(
			self::$slug . '_admin_settings',
			SEARCHWP_PLUGIN_URL . 'assets/styles/settings.css',
			false,
			SEARCHWP_VERSION
		);

		wp_enqueue_script( 'jquery' );
	}

	/**
	 * Adds SearchWP entry to Settings menu. Implements Settings UI.
	 *
	 * @since 4.0
	 * @return void
	 */
	public static function add() {
		global $submenu;

		if ( apply_filters( 'searchwp\options\settings_screen', true ) ) {
			add_options_page(
				'SearchWP',
				'SearchWP',
				Settings::get_capability(),
				self::$slug,
				function() {
					do_action( 'searchwp\settings\page' );
					do_action( 'searchwp\debug\log', 'Displaying options page', 'settings' );
					?>
					<div class="wrap">
						<?php
							self::header();
							self::view();
							self::footer();
						?>
					</div>
					<?php
				}
			);
		}

		if ( apply_filters( 'searchwp\options\dashboard_stats_link', true ) ) {
			add_dashboard_page(
				__( 'Search Statistics', 'searchwp' ),
				__( 'Search Stats', 'searchwp' ),
				Statistics::$capability,
				self::$slug . '-stats',
				function() {}
			);

			// Override the link for the Search Stats Admin Menu entry.
			if ( is_array( $submenu ) && array_key_exists( 'index.php', $submenu ) ) {
				foreach ( $submenu['index.php'] as $index => $dashboard_submenu ) {
					if ( 'searchwp-stats' !== $dashboard_submenu[2] ) {
						continue;
					}

					$submenu['index.php'][ $index ][2] = esc_url_raw( add_query_arg( [
						'page' => self::$slug,
						'tab'  => 'statistics',
					], admin_url( 'options-general.php' ) ) );

					break;
				}
			}
		}

	}

	/**
	 * Renders the main view.
	 *
	 * @since 4.0
	 * @return void
	 */
	public static function view() {
		$view = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'default';

		do_action( 'searchwp\settings\before\\' . $view );
		do_action( 'searchwp\settings\view\\' . $view );
		do_action( 'searchwp\settings\after\\' . $view );
	}

	/**
	 * Renders the footer.
	 *
	 * @since 4.0
	 * @return void
	 */
	public static function footer() {
		?>
		</div> <!-- /.searchwp-settings-view -->
		<?php
		do_action( 'searchwp\settings\footer' );
	}

	/**
	 * Renders the header.
	 *
	 * @since 4.0
	 * @return void
	 */
	public static function header() {
		do_action( 'searchwp\settings\header\before' );

		?>
			<div class="searchwp-settings-header postbox">
				<p class="searchwp-logo" title="SearchWP">
					<svg width="40" height="54" viewBox="0 0 40 54" fill="none" xmlns="http://www.w3.org/2000/svg"><mask id="searchwp-logo-mask-a" fill="#fff"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.648 10c-.959 0-1.781.68-1.946 1.625C2.175 14.658 1.112 21.34 1 26.5c-.114 5.288 1.134 13.417 1.712 16.864.16.952.985 1.636 1.95 1.636h30.684c.958 0 1.78-.675 1.944-1.619.584-3.339 1.824-11.12 1.71-16.381-.112-5.195-1.194-12.217-1.72-15.36A1.969 1.969 0 0035.33 10H4.648zm2.729 5a.99.99 0 00-.987.873C6.153 17.885 5.593 22.984 5.5 27c-.096 4.116.94 10.066 1.341 12.2.088.468.496.8.972.8H32.18a.983.983 0 00.97-.798c.404-2.134 1.445-8.085 1.349-12.202-.093-4.016-.658-9.117-.897-11.128a.99.99 0 00-.986-.872H7.377z"/></mask><path fill-rule="evenodd" clip-rule="evenodd" d="M4.648 10c-.959 0-1.781.68-1.946 1.625C2.175 14.658 1.112 21.34 1 26.5c-.114 5.288 1.134 13.417 1.712 16.864.16.952.985 1.636 1.95 1.636h30.684c.958 0 1.78-.675 1.944-1.619.584-3.339 1.824-11.12 1.71-16.381-.112-5.195-1.194-12.217-1.72-15.36A1.969 1.969 0 0035.33 10H4.648zm2.729 5a.99.99 0 00-.987.873C6.153 17.885 5.593 22.984 5.5 27c-.096 4.116.94 10.066 1.341 12.2.088.468.496.8.972.8H32.18a.983.983 0 00.97-.798c.404-2.134 1.445-8.085 1.349-12.202-.093-4.016-.658-9.117-.897-11.128a.99.99 0 00-.986-.872H7.377z" fill="#BFCDC2"/><path d="M2.702 11.625l.986.171-.986-.171zM1 26.5l1 .022-1-.022zm1.712 16.864l-.986.166.986-.166zm34.578.017l-.985-.172.985.172zM39 27l1-.022L39 27zm-1.72-15.36l.987-.165-.987.165zM6.39 15.873l.993.117-.993-.117zM5.5 27l-1-.023 1 .023zm1.341 12.2l-.983.185.983-.184zm26.31.002l.983.185-.982-.185zM34.5 27l-1 .023 1-.023zm-.897-11.128l.993-.118-.993.118zM3.688 11.796a.967.967 0 01.96-.796V9a2.967 2.967 0 00-2.93 2.454l1.97.342zM2 26.522c.11-5.077 1.16-11.693 1.688-14.726l-1.97-.342C1.188 14.487.112 21.236 0 26.478l2 .044zm1.698 16.677C3.118 39.74 1.888 31.7 2 26.522l-2-.044c-.116 5.397 1.15 13.617 1.726 17.052l1.972-.33zm.963.801a.965.965 0 01-.963-.8l-1.972.33A2.965 2.965 0 004.66 46v-2zm30.684 0H4.662v2h30.684v-2zm.96-.79a.962.962 0 01-.96.79v2a2.962 2.962 0 002.93-2.447l-1.97-.344zM38 27.021c.111 5.15-1.11 12.837-1.695 16.187l1.97.344c.582-3.328 1.84-11.201 1.725-16.575l-2 .044zm-1.706-15.217C36.82 14.95 37.89 21.907 38 27.022l2-.044c-.114-5.275-1.208-12.362-1.733-15.503l-1.973.33zM35.33 11a.97.97 0 01.964.805l1.973-.33A2.969 2.969 0 0035.33 9v2zM4.648 11H35.33V9H4.648v2zm2.735 4.99v.001a.02.02 0 01-.001.004.013.013 0 01-.003.004h-.002c-.002.001-.002.001 0 .001v-2a1.99 1.99 0 00-1.98 1.756l1.986.234zM6.5 27.023c.092-3.964.646-9.022.883-11.033l-1.986-.234c-.237 2.013-.802 7.154-.897 11.22l2 .047zm1.324 11.993C7.42 36.87 6.407 31.02 6.5 27.023l-2-.046c-.098 4.236.959 10.285 1.358 12.409l1.966-.37zM7.813 39c-.002 0-.002 0 0 0l.003.002a.026.026 0 01.008.014l-1.966.37A1.983 1.983 0 007.813 41v-2zm24.368 0H7.813v2H32.18v-2zm-.012.016a.027.027 0 01.008-.014.014.014 0 01.004-.002c.001 0 .001 0 0 0v2c.945 0 1.774-.664 1.953-1.613l-1.965-.371zM33.5 27.023c.093 3.997-.926 9.848-1.33 11.993l1.964.371c.402-2.123 1.464-8.173 1.366-12.41l-2 .046zm-.89-11.033c.239 2.01.798 7.069.89 11.033l2-.046c-.095-4.069-.665-9.21-.904-11.223l-1.986.236zm.007.01c.001 0 0 0 0 0l-.002-.002-.003-.003a.025.025 0 01-.002-.004v-.001l1.986-.236A1.99 1.99 0 0032.616 14v2zm-25.24 0h25.24v-2H7.377v2z" fill="#839788" mask="url(#searchwp-logo-mask-a)"/><path d="M30.5 23.099c0 6.592-.971 12.613-2.571 17.25-.978 2.835-2.14 3.732-3.34 4.022-.635.154-1.327.148-2.105.095-.214-.014-.437-.033-.667-.052-.579-.047-1.198-.099-1.817-.099s-1.238.052-1.817.1c-.23.018-.453.037-.667.051-.778.053-1.47.059-2.106-.095-1.199-.29-2.36-1.187-3.339-4.022-1.6-4.637-2.571-10.658-2.571-17.25 0-4.879.532-9.447 1.458-13.374.087-.368.276-.584.526-.729.271-.156.637-.24 1.073-.273.431-.033.883-.013 1.316.006h.008l.036.002c.394.017.824.036 1.137-.025 1.419-.276 2.533-.212 3.33-.083.398.066.72.148.97.215l.117.033.212.057c.078.02.202.05.317.05.11 0 .268-.014.453-.031l.122-.012c.176-.016.39-.037.652-.059.65-.054 1.592-.12 2.914-.16.21-.007.51-.044.834-.083l.44-.052c.507-.055 1.064-.1 1.595-.073.535.026 1.002.122 1.353.315.331.182.573.456.679.902.926 3.927 1.458 8.495 1.458 13.374z" fill="#fff" stroke="#839788"/><path d="M9.317 40.85a1.5 1.5 0 011.493-1.35h18.38a1.5 1.5 0 011.493 1.35l.6 6a1.5 1.5 0 01-1.493 1.65H10.21a1.5 1.5 0 01-1.493-1.65l.6-6z" fill="#BFCDC2" stroke="#839788"/><path d="M14.496 2a.5.5 0 01.5-.5h10a.5.5 0 01.5.5v8.5h-11V2z" stroke="#839788" stroke-width="3" stroke-linejoin="round"/><path d="M11.496 7a1.5 1.5 0 011.5-1.5h14a1.5 1.5 0 011.5 1.5v4.5h-17V7z" fill="#BFCDC2" stroke="#839788"/><path d="M8.496 10a1.5 1.5 0 011.5-1.5h20a1.5 1.5 0 011.5 1.5v4.5h-23V10zM5.066 48.588A1.5 1.5 0 016.51 47.5H33.49a1.5 1.5 0 011.443 1.088l.857 3a1.5 1.5 0 01-1.442 1.912H5.65a1.5 1.5 0 01-1.442-1.912l.857-3z" fill="#BFCDC2" stroke="#839788"/><path d="M9.242 35.802l11.084-9.66 11.27-8.63" stroke="#839788" stroke-width="2"/><path d="M30.69 35.747l-11.047-9.63L8.4 17.508" stroke="#839788" stroke-width="2"/></svg>
				</p>
				<nav class="searchwp-settings-header-nav">
					<button class="button searchwp-settings-header-nav-toggle">Menu</button>
					<ul>
						<?php do_action( 'searchwp\settings\nav\tab' ); ?>
					</ul>
				</nav>
				<script>
					jQuery(document).ready(function($){
						$('.searchwp-settings-header-nav-toggle').click(function(e){
							e.preventDefault();
							$('.searchwp-settings-header-nav > ul').toggle();
						});
					});
				</script>
			</div>
			<div class="searchwp-settings-view">
		<?php

		do_action( 'searchwp\settings\header\after' );
	}
}
