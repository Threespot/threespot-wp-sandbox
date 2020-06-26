<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 */
final class WS_Form {

	// Loader
	protected $loader;

	// Plugin name
	protected $plugin_name;

	// Version
	protected $version;

	// WooCommerce
	protected $woocommerce_active;

	// Plugin Public
	public $plugin_public;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 */
	public function __construct() {

		$this->plugin_name = WS_FORM_NAME;
		$this->version = WS_FORM_VERSION;
		$this->woocommerce_active = is_plugin_active('woocommerce/woocommerce.php');

		$plugin_path = plugin_dir_path(dirname(__FILE__));

		// The class responsible for all common functions
		require_once $plugin_path . 'includes/class-ws-form-common.php';

		// The class responsible for licensing
		require_once $plugin_path . 'includes/class-ws-form-licensing.php';

		$this->load_dependencies();

		$this->plugin_public = new WS_Form_Public();

		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_public_shortcodes();
		$this->define_api_hooks();

		$this->wizard = false;
	}

	// Load the required dependencies for this plugin.
	private function load_dependencies() {

		// Configuration (Options, field types, field variables)
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-config.php';

		// The class responsible for orchestrating the actions and filters of the core plugin.
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-loader.php';

		// The class responsible for defining internationalization functionality of the plugin.
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-i18n.php';

		// The class responsible for customizing
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-customize.php';

		// The classes responsible for populating WP List Tables
		if(is_admin()) {

			require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/wp/class-wp-list-table-ws-form.php';
			require_once WS_FORM_PLUGIN_DIR_PATH . 'admin/class-ws-form-wp-list-table-form.php';
			require_once WS_FORM_PLUGIN_DIR_PATH . 'admin/class-ws-form-wp-list-table-submit.php';
		}

		// The class responsible for defining all actions that occur in the admin area.
		require_once WS_FORM_PLUGIN_DIR_PATH . 'admin/class-ws-form-admin.php';

		// The class responsible for defining all actions that occur in the public-facing side of the site.
		require_once WS_FORM_PLUGIN_DIR_PATH . 'public/class-ws-form-public.php';

		// The class responsible for managing form previews.
		require_once WS_FORM_PLUGIN_DIR_PATH . 'public/class-ws-form-preview.php';

		// The class responsible for the widget
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-widget.php';

		// Core
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-core.php';

		// Object classes
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-meta.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-form.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-group.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-section.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-field.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-submit-meta.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-submit.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-wizard.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-css.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-encryption.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-migrate.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-form-stat.php';

		// Object classes - Actions
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-action.php';

		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-akismet.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-database.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-message.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-redirect.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-email.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-data-erasure-request.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-data-export-request.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-search.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-conversion.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-hook.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-javascript.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/actions/class-ws-form-action-custom-api.php';

		// Object classes - Data Sources
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/core/class-ws-form-data-source.php';

		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/data-sources/class-ws-form-data-source-post.php';

		// API core
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api.php';

		// API
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-helper.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-config.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-form.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-group.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-section.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-field.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-submit.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-wizard.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-form-stat.php';
		require_once WS_FORM_PLUGIN_DIR_PATH . 'api/class-ws-form-api-migrate.php';

		// Functions
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/functions.php';

		// Third party

		// Beaver Builder
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/beaver-builder/fl-ws-form.php';

		// Divi
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/divi/ws-form/ws-form.php';

		// Elementor
		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/third-party/elementor/elementor.php';

		$this->loader = new WS_Form_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WS_Form_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 */
	private function set_locale() {

		$plugin_i18n = new WS_Form_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	private function define_admin_hooks() {

		$plugin_admin = new WS_Form_Admin();

		// General
		$this->loader->add_action('init', $plugin_admin, 'init');
		$this->loader->add_action('admin_menu', $plugin_admin, 'admin_menu');

		// Enqueuing
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 9999);	// Make sure we're overriding other styles
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		// Admin notifications
		$this->loader->add_action('admin_notices', 'WS_Form_Common', 'admin_messages_render');

		// Customize
		$this->loader->add_action('customize_register', $plugin_admin, 'customize_register');

		// Theme switching
		$this->loader->add_action('switch_theme', $plugin_admin, 'switch_theme');

		// Plugins
		$this->loader->add_filter('plugin_action_links_' . WS_FORM_PLUGIN_BASENAME, $plugin_admin, 'plugin_action_links');

		// Gutenberg
		$this->loader->add_action('enqueue_block_editor_assets', $plugin_admin, 'enqueue_block_editor_assets');
		$this->loader->add_filter('block_categories', $plugin_admin, 'block_categories', 10, 2);

		// Dashboard
		$this->loader->add_filter('dashboard_glance_items', $plugin_admin, 'dashboard_glance_items');
		$this->loader->add_action('wp_dashboard_setup', $plugin_admin, 'wp_dashboard_setup');

		// Plugins loaded (Initializes actions)
		$this->loader->add_action('plugins_loaded', $plugin_admin, 'plugins_loaded', 10);

		// Current screen
		$this->loader->add_action('current_screen', $plugin_admin, 'current_screen', 10);

		// Licensing
		$plugin_licensing = new WS_Form_Licensing(WS_FORM_LICENSE_ITEM_ID);
		$plugin_licensing->transient_check();
		$this->loader->add_action('admin_init', $plugin_licensing, 'updater');

		// WooCommerce
		if($this->woocommerce_active) {

			$this->loader->add_filter('wsf_config_options', $plugin_admin, 'ws_form_config_options_woocommerce');
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 */
	private function define_public_hooks() {

		// General
		$this->loader->add_action('init', $this->plugin_public, 'init');
		$this->loader->add_action('wp', $this->plugin_public, 'wp');

		// Enqueuing
		$this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue');

		// Footer
		$this->loader->add_action('wp_footer', $this->plugin_public, 'wp_footer', 9999);

		// NONCE management
		$this->loader->add_filter('nonce_user_logged_out', $this->plugin_public, 'nonce_user_logged_out', 9999, 2);

		// Divi
		$this->loader->add_action('wp_ajax_ws_form_divi_form', $this->plugin_public, 'ws_form_divi_form');
		// WooCommerce
		if($this->woocommerce_active) {

			$this->loader->add_filter('wsf_option_get', $this->plugin_public, 'ws_form_option_get_woocommerce', 10, 2);
		}
	}

	/**
	 * Register all of the shortcodes related to the public-facing functionality
	 * of the plugin.
	 */
	private function define_public_shortcodes() {

		$this->loader->add_shortcode('ws_form', $this->plugin_public, 'shortcode_ws_form');
	}

	/**
	 * Register all of the hooks related to the API
	 */
	private function define_api_hooks() {

		$plugin_api = new WS_Form_API();

		// Initialize API
		$this->loader->add_action('rest_api_init', $plugin_api, 'api_rest_api_init');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}
}
