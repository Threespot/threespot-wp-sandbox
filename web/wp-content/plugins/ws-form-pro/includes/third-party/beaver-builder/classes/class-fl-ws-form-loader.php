<?php
	
/**
 * A class that handles loading custom modules and custom
 * fields if the builder is installed and activated.
 */
class FL_WS_Form_Loader {
	
	/**
	 * Initializes the class once all plugins have loaded.
	 */
	static public function init() {

		add_action( 'plugins_loaded', __CLASS__ . '::setup_hooks' );
	}
	
	/**
	 * Setup hooks if the builder is installed and activated.
	 */
	static public function setup_hooks() {

		if ( ! class_exists( 'FLBuilder' ) ) {
			return;	
		}

		if(
			isset($_GET) && isset($_GET['fl_builder'])	// phpcs:ignore
		) {

			// Disable debug
			add_filter('wsf_debug_enabled', function($debug_render) { return false; }, 10, 1);

			// Enqueue all WS Form scripts
			add_action('wp_enqueue_scripts', function() { do_action('wsf_enqueue_core'); });
		}
		
		// Load custom modules.
		add_action( 'init', __CLASS__ . '::load_modules' );
	}
	
	/**
	 * Loads our custom modules.
	 */
	static public function load_modules() {

		require_once FL_WS_FORM_DIR . 'modules/ws-form/ws-form.php';
	}
	
    static public function get_forms() {

		// Build form list
		$ws_form_form = New WS_Form_Form();
		$forms = $ws_form_form->db_read_all('', "NOT (status = 'trash')", 'label ASC', '', '', false);
		$form_array = array('0' => __('Select form...', 'ws-form'));

		if($forms) {

			foreach($forms as $form) {

				$form_array[$form['id']] = $form['label'] . ' (ID: ' . $form['id'] . ')';
			}
		}

		return $form_array;
    }
}

FL_WS_Form_Loader::init();