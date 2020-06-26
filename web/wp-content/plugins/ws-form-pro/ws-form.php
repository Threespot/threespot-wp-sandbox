<?php
	/**
	 * WS Form PRO
	 *
	 * @link              https://wsform.com/
	 * @since             1.0.0
	 * @package           WS_Form_PRO
	 *
	 * @wordpress-plugin
	 * Plugin Name:       WS Form PRO
	 * Plugin URI:        https://wsform.com/
	 * Description:       Build Better WordPress Forms
	 * Version:           1.6.24
	 * Author:            Westguard Solutions
	 * Author URI:        https://wsform.com/
	 * Text Domain:       ws-form
	 * Domain Path:       /languages
	 */

	// If this file is called directly, abort.
	if( !defined('WPINC') ) {
		die;
	}

	// Load plugin.php
	if( !function_exists('is_plugin_active') ) {

		include_once(ABSPATH . 'wp-admin/includes/plugin.php');
	}

	if(!is_plugin_active('ws-form/ws-form.php')) {

		// Constants
		define('WS_FORM_NAME', 'ws-form');
		define('WS_FORM_VERSION', '1.6.24');
		define('WS_FORM_NAME_PRESENTABLE', 'WS Form PRO');
		define('WS_FORM_EDITION', 'pro');
		define('WS_FORM_PLUGIN_BASENAME_COUNTERPART', 'ws-form/ws-form.php');
		define('WS_FORM_LICENSE_ITEM_ID', 498);
		define('WS_FORM_RECAPTCHA_ENDPOINT', 'https://www.google.com/recaptcha/api/siteverify');
		define('WS_FORM_POST_NONCE_FIELD_NAME', 'wsf_nonce');
		define('WS_FORM_POST_NONCE_ACTION_NAME', 'wsf_post');
		define('WS_FORM_UPLOAD_DIR', 'ws-form');
		define('WS_FORM_IDENTIFIER', 'ws_form');
		define('WS_FORM_DB_TABLE_PREFIX', 'wsf_');
		define('WS_FORM_SHORTCODE', 'ws_form');
		define('WS_FORM_WIDGET', 'ws_form_widget');
		define('WS_FORM_CAPABILITY_PREFIX', 'wsf_');
		define('WS_FORM_USER_REQUEST_IDENTIFIER', 'ws-form');
		define('WS_FORM_AUTHOR', 'Westguard Solutions');

		define('WS_FORM_DEFAULT_FORM_NAME', __('New form', 'ws-form'));
		define('WS_FORM_DEFAULT_GROUP_NAME', __('Tab', 'ws-form'));
		define('WS_FORM_DEFAULT_SECTION_NAME', __('Section', 'ws-form'));
		define('WS_FORM_DEFAULT_FIELD_NAME', __('Field', 'ws-form'));
		define('WS_FORM_DEFAULT_DATA_SOURCE_NAME', __('Data source', 'ws-form'));
		define('WS_FORM_DEFAULT_FRAMEWORK', 'ws-form');
		define('WS_FORM_DEFAULT_MODE', 'basic');

		define('WS_FORM_RESTFUL_NAMESPACE', 'ws-form/v1');

		define('WS_FORM_STATUS_FORM', 'draft,publish,trash');
		define('WS_FORM_STATUS_SUBMIT', 'draft,publish,error,spam,trash');

		define('WS_FORM_COMPATIBILITY_NAME', 'caniuse.com');
		define('WS_FORM_COMPATIBILITY_URL', 'https://caniuse.com');
		define('WS_FORM_COMPATIBILITY_MASK', 'https://caniuse.com/#feat=#compatibility_id');

		define('WS_FORM_MODES', 'basic,advanced');

		define('WS_FORM_SPAM_LEVEL_MAX', 100);		// 0 = Not spam, 100 = Spam

		define('WS_FORM_OPTION_PREFIX', 'ws_form');
		define('WS_FORM_FIELD_LABEL_MAX_LENGTH', 190);
		define('WS_FORM_FIELD_PREFIX', 'field_');
		define('WS_FORM_FIELD_PREFIX_PUBLIC_', 'wsf_field_');

		define('WS_FORM_PLUGIN_ROOT_FILE', __FILE__);
		define('WS_FORM_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));
		define('WS_FORM_PLUGIN_DIR_URL', plugin_dir_url(__FILE__));
		define('WS_FORM_PLUGIN_BASENAME', plugin_basename(__FILE__));
		define('WS_FORM_PLUGIN_INCLUDES', includes_url());

		define('WS_FORM_SETTINGS_IMAGE_PREVIEW_SIZE', 'full');

		define('WS_FORM_MIN_VERSION_WORDPRESS', '4.4');
		define('WS_FORM_MIN_VERSION_PHP', '5.6');
		define('WS_FORM_MIN_VERSION_MYSQL', '5.1');
		define('WS_FORM_MIN_INPUT_VARS', 100);

		define('WS_FORM_API_CALL_TIMEOUT', '10');
		define('WS_FORM_API_CALL_VERIFY_SSL', true);

		define('WS_FORM_SECTION_REPEATABLE_DELIMITER_SECTION', ',');
		define('WS_FORM_SECTION_REPEATABLE_DELIMITER_ROW', ';');
		define('WS_FORM_SECTION_REPEATABLE_DELIMTIER_SUBMIT', '<br />');

		define('WS_FORM_REVIEW_NAG_DURATION', 14);
	}

	// Activate
	function activate_ws_form_pro() {

		if(is_plugin_active('ws-form/ws-form.php')) {

			deactivate_plugins('ws-form/ws-form.php');
		}

		require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-activator.php';
		WS_Form_Activator::activate();
	}

	// Deactivate
	function deactivate_ws_form_pro() {

		require_once WS_FORM_PLUGIN_DIR_PATH. 'includes/class-ws-form-deactivator.php';
		WS_Form_Deactivator::deactivate();
	}

	// Uninstall
	function uninstall_ws_form_pro() {

		require_once WS_FORM_PLUGIN_DIR_PATH. 'includes/class-ws-form-uninstaller.php';
		WS_Form_Uninstaller::uninstall();
	}

	// Register hooks for plugin activation, deactivation and uninstall
	register_activation_hook(__FILE__, 'activate_ws_form_pro');
	register_deactivation_hook(__FILE__, 'deactivate_ws_form_pro');
	register_uninstall_hook(__FILE__, 'uninstall_ws_form_pro');

	if(!is_plugin_active('ws-form/ws-form.php')) {

		require WS_FORM_PLUGIN_DIR_PATH. 'includes/class-ws-form.php';

		function run_ws_form_pro() {

			$plugin = new WS_Form();
			$plugin->run();
		}
		run_ws_form_pro();
	}
