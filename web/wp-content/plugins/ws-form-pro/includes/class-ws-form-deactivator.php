<?php

	/**
	 * Fired during plugin deactivation
	 */

	class WS_Form_Deactivator {

		public static function deactivate() {

			// Process action deactivations
			foreach(WS_Form_Action::$actions as $action) {

				if(method_exists($action, 'deactivate')) {

					$action->deactivate();
				}
			}
		}
	}
