<?php

	// Fired during plugin uninstall
	class WS_Form_Uninstaller {

		public static function uninstall() {
			
			$uninstall_options = WS_Form_Common::option_get('uninstall_options', false);
			$uninstall_database = WS_Form_Common::option_get('uninstall_database', false);

			// Delete options
			if($uninstall_options) {

				delete_option(WS_FORM_IDENTIFIER);
				delete_site_option(WS_FORM_IDENTIFIER);
			}

			// Delete database tables
			if($uninstall_database) {

				// Drop WS Form tables
				global $wpdb;

				// Get table prefix
				$table_prefix = $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX;

				// Tables to delete
				$tables = array('form', 'form_meta', 'form_stat', 'group', 'group_meta', 'section', 'section_meta', 'field', 'field_meta', 'submit', 'submit_meta', 'wizard', 'wizard_category');

				// Run through each table and delete
				foreach($tables as $table_name) {

					$sql = sprintf("DROP TABLE IF EXISTS %s%s;", $table_prefix, $table_name);
					$wpdb->query($sql);
				}
			}
		}
	}
