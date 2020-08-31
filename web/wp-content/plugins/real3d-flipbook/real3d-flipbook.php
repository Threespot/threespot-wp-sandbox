<?php

	/*
	Plugin Name: Real3D Flipbook
	Plugin URI: https://codecanyon.net/item/real3d-flipbook-wordpress-plugin/6942587?ref=creativeinteractivemedia
	Description: Premium Responsive Real 3D FlipBook  
	Version: 3.17.1
	Author: creativeinteractivemedia
	Author URI: http://codecanyon.net/user/creativeinteractivemedia?ref=creativeinteractivemedia
	*/

	include_once( plugin_dir_path(__FILE__).'/includes/Real3DFlipbook.php' );

	if(!function_exists("trace")){
		function trace($var){
			echo('<script type="text/javascript">console.log(' .json_encode($var). ')</script>');
		}
	}

	$real3dflipbook = Real3DFlipbook::get_instance();
	define('REAL3D_FLIPBOOK_VERSION', '3.17.1');
	$real3dflipbook->PLUGIN_VERSION = REAL3D_FLIPBOOK_VERSION;
	$real3dflipbook->PLUGIN_DIR_URL = plugin_dir_url( __FILE__ );
	$real3dflipbook->PLUGIN_DIR_PATH = plugin_dir_path( __FILE__ );