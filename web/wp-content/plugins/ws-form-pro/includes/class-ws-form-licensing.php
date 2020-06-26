<?php

	/**
	 * Manages plugin licensing
	 */

	// Include EDD software licensing plugin updater
	if(!class_exists('EDD_SL_Plugin_Updater_WS_Form')) {

		include('licensing/edd/EDD_SL_Plugin_Updater_WS_Form.php');
	}

	class WS_Form_Licensing {

		const LICENSE_ENDPOINT = 'https://wsform.com/';
		const LICENSE_TRANSIENT_ID = 'wsf-license-status';
		const LICENSE_TRANSIENT_EXPIRY = 86400;	// 1 Day

		private $item_id;
		private $prefix;
		private $name;
		private $version;
		private $author;

		private $path;
		private $tab_license;

		private $test_mode;

		public function __construct($item_id, $action_id = '', $name = WS_FORM_NAME_PRESENTABLE, $version = WS_FORM_VERSION, $author = WS_FORM_AUTHOR, $path = false, $test_mode = false) {

			$this->item_id = $item_id;
			$this->prefix = ($action_id != '') ? 'action_' . $action_id . '_' : '';
			$this->name = $name;
			$this->version = $version;
			$this->author = $author;

			$this->path = ($path === false) ? WS_FORM_PLUGIN_ROOT_FILE : $path;
			$this->tab_license = ($action_id != '') ? 'action_' . $action_id : 'license';

			$this->test_mode = $test_mode;

			// Resets done in test mode
			if($this->test_mode) {

				set_site_transient('update_plugins', null);
			}
		}

		// Transient check
		public function transient_check() {

			// Check license transient
			$license_transient = get_transient($this->prefix . self::LICENSE_TRANSIENT_ID);
			if($license_transient === false) {

				// Check license
				self::check();

				// Store license status in case we want to pull it from the transient
				$license_status = array(

					'key'			=>	WS_Form_Common::option_get($this->prefix . 'license_key', ''),
					'activated'		=>	WS_Form_Common::option_get($this->prefix . 'license_activated', false),
					'expires'		=>	WS_Form_Common::option_get($this->prefix . 'license_expires', ''),
					'time'			=>	time()
				);

				// Set transient
				set_transient($this->prefix . self::LICENSE_TRANSIENT_ID, $license_status, self::LICENSE_TRANSIENT_EXPIRY);
			}

			// Add nag action
			add_action('wsf_nag', array($this, 'nag'));
		}

		// Nag
		public function nag() {

			// Check to see if license is activated
			$license_activated = WS_Form_Common::option_get($this->prefix . 'license_activated', false);
			if(!$license_activated) {

				// If license is not activated, show a nag
				WS_Form_Common::admin_message_push(sprintf(__('%s is not licensed. Please enter your license key <a href="%s">here</a> to receive software updates and support.', 'ws-form'), $this->name, WS_Form_Common::get_admin_url('ws-form-settings', false, 'tab=' . $this->tab_license)), 'notice-warning', false, true);
			}
		}

		// Updater
		public function updater() {

			// Get license key
			$license_key = WS_Form_Common::option_get($this->prefix . 'license_key', '');

			// Build updater args
			$args = array(

				'version' => $this->version,
				'license' => $license_key,
				'item_id' => $this->item_id,
				'author'  => $this->author,
				'beta'    => false
			);

			// Test mode
			if($this->test_mode) { $args['test_mode'] = true; };

			// Create new updater instance
			$edd_updater = new EDD_SL_Plugin_Updater_WS_Form(self::LICENSE_ENDPOINT, $this->path, $args);
		}

		// Is this plugin activated?
		public function is_licensed() {

			return WS_Form_Common::option_get($this->prefix . 'license_activated', false);
		}

		// Get license status as string
		public function license_status() {

			if(self::is_licensed()) {

				$license_expires = WS_Form_Common::option_get($this->prefix . 'license_expires', '');
				return 'Licensed' . (($license_expires != '') ? ' (Expires: ' . date(get_option('date_format'), $license_expires) . ')' : '');

			} else {

				return 'Unlicensed';
			}
		}

		// Activate
		public function activate($license_key) {

			// Stop self checking
			if(self::LICENSE_ENDPOINT == trailingslashit(home_url())) { return false; }

			// API parameters
			$api_params = array(

				'edd_action'	=> 'activate_license',
				'license'		=> $license_key,
				'item_id'		=> $this->item_id,
				'url'			=> home_url()
			);

			// Call the EDD 
			$response = wp_remote_post(self::LICENSE_ENDPOINT, array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));

			// Check for errors
			if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {

				if (is_wp_error($response)) {

					return self::activate_error($response->get_error_message());

				} else {

					return self::activate_error(__('An error occurred, please try again.', 'ws-form'));
				}

			} else {

				$license_data = json_decode(wp_remote_retrieve_body($response));

				if($license_data->success === false) {

					// Set license key deactivated
					WS_Form_Common::option_set($this->prefix . 'license_activated', false);

					// Reset license expiry
					self::reset_license_expiry();

					switch($license_data->error) {

						case 'expired' :

							return self::activate_error(

								sprintf(

									__('Your license key expired on %s.', 'ws-form'),
								
									date_i18n(

										get_option('date_format'),
										strtotime($license_data->expires, current_time('timestamp'))
									)
								)

							);

						case 'disabled' :
						case 'revoked' :

							return self::activate_error(__('Your license key has been disabled.', 'ws-form'));

						case 'missing' :

							return self::activate_error(__('Invalid license.', 'ws-form'));

						case 'invalid' :
						case 'site_inactive' :

							return self::activate_error(__('Your license is not active for this URL.', 'ws-form'));

						case 'license_not_activable' :

							return self::activate_error(__('License not activable. You are entering the license key for the package you purchased rather than the license key for the plugin itself.', 'ws-form'));

						case 'item_name_mismatch' :

							return self::activate_error(sprintf(__('This appears to be an invalid license key for %s.', 'ws-form'), $this->name));

						case 'no_activations_left' :

							return self::activate_error(__('Your license key has reached its activation limit.', 'ws-form'));

						case 'invalid_item_id' :

							return self::activate_error(__('Invalid item ID.', 'ws-form'));

						default :

							return self::activate_error(__('An error occurred, please try again.', 'ws-form'));
					}
				}
			}

			// Set license key activated
			WS_Form_Common::option_set($this->prefix . 'license_activated', true);

			// Set license expiry
			self::set_license_expiry($license_data);

			// Show success message
			WS_Form_Common::admin_message_push(__('License key successfully activated', 'ws-form'));

			return true;
		}

		// Deactivate
		public function deactivate($license_key) {

			// Stop self checking
			if(self::LICENSE_ENDPOINT == trailingslashit(home_url())) { return false; }

			// API parameters
			$api_params = array(

				'edd_action'	=> 'deactivate_license',
				'license'		=> $license_key,
				'item_id'		=> $this->item_id,
				'url'			=> home_url()
			);

			// Call the EDD 
			$response = wp_remote_post(self::LICENSE_ENDPOINT, array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));

			// Check for errors
			if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {

				if (is_wp_error($response)) {

					return self::activate_error($response->get_error_message());

				} else {

					return self::activate_error(__('An error occurred, please try again.', 'ws-form'));
				}

			}

			$license_data = json_decode(wp_remote_retrieve_body($response));

			if($license_data->license === 'deactivated') {

				// Set license key level
				WS_Form_Common::option_set($this->prefix . 'license_activated', false);

				// Reset license expiry
				self::reset_license_expiry();

				// Show success message
				WS_Form_Common::admin_message_push(__('License key successfully deactivated', 'ws-form'));

				return true;
			}

			return false;
		}

		// License key processing error
		public function activate_error($message) {

			// Error
			WS_Form_Common::admin_message_push($message, 'notice-error');

			// Set license key level
			WS_Form_Common::option_set($this->prefix . 'license_activated', false);

			return false;
		}

		// Check license
		public function check() {

			// Stop self checking
			if(self::LICENSE_ENDPOINT == trailingslashit(home_url())) { return false; }

			// Get license key
			$license_key = WS_Form_Common::option_get($this->prefix . 'license_key', '');

			$api_params = array(

				'edd_action'	=> 'check_license',
				'license'		=> $license_key,
				'item_id'		=> $this->item_id,
				'url'			=> home_url()
			);

			// Call the custom API.
			$response = wp_remote_post(self::LICENSE_ENDPOINT, array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));
			if(is_wp_error($response)) {

				return self::activate_error($response->get_error_message());
			}

			$license_data = json_decode(wp_remote_retrieve_body($response));

			if($license_data->license == 'valid') {

				self::set_license_expiry($license_data);

				return true;

			} else {

				// Set license key invalid
				WS_Form_Common::option_set($this->prefix . 'license_activated', false);

				self::reset_license_expiry();

				return false;
			}			
		}

		// Set license expiry
		public function set_license_expiry($license_data) {

			if(!isset($license_data->expires)) { self::reset_license_expiry(); return false; }

			$license_expires_string = $license_data->expires;

			if(($license_expires = strtotime($license_expires_string)) === false) { self::reset_license_expiry(); return false; }

			WS_Form_Common::option_set($this->prefix . 'license_expires', $license_expires);
		}

		// Reset license expiry
		public function reset_license_expiry() {

			WS_Form_Common::option_set($this->prefix . 'license_expires', '');
		}
	}
