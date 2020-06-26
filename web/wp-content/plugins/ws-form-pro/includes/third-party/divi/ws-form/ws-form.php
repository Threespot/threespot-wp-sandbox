<?php

	if(!function_exists('divi_extensions_init_wsform')) {

		function divi_extensions_init_wsform() {

	        require_once plugin_dir_path(__FILE__) . 'includes/ws-form.php';
		}
		add_action('divi_extensions_init', 'divi_extensions_init_wsform');
	}
