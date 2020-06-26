<?php

	// https://github.com/defuse/php-encryption
	require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/encryption/src/Core.php';
	require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/encryption/src/Crypto.php';
	require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/encryption/src/DerivedKeys.php';
	require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/encryption/src/Encoding.php';
	require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/encryption/src/Key.php';
	require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/encryption/src/KeyOrPassword.php';
	require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/encryption/src/RuntimeTests.php';
	require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/encryption/src/Exception/CryptoException.php';
	require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/encryption/src/Exception/BadFormatException.php';
	require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/encryption/src/Exception/EnvironmentIsBrokenException.php';
	require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/encryption/src/Exception/IOException.php';
	require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/encryption/src/Exception/WrongKeyOrModifiedCiphertextException.php';

	class WS_Form_Encryption extends WS_Form_Core {

		private $enabled;
		private $openssl_installed;
		private $key;

		public $key_found;
		public $can_encrypt;
		public $can_decrypt;

		public function __construct() {

			// Set enabled
			self::set_enabled();

			// Set OpenSSL installed
			self::set_openssl_installed();

			// Set encryption key
			self::set_key();

			// Set can_encrypt
			self::set_can_encrypt();

			// Set can_decrypt
			self::set_can_decrypt();
		}

		public function create_random_key() {

			$key = \Defuse\Crypto\Key::createNewRandomKey();
			return $key->saveToAsciiSafeString();
		}

		private function set_enabled() {

			$this->enabled = (WS_Form_Common::option_get('encryption_enabled', false, true) == 'on');
		}

		private function set_openssl_installed() {

			$this->openssl_installed = extension_loaded('openssl');
		}

		private function set_key() {

			if(
				!defined('WS_FORM_ENCRYPTION_KEY') || (WS_FORM_ENCRYPTION_KEY == '')
			) {

				$this->key = false;
				$this->key_found = false;

			} else {

				$this->key = WS_FORM_ENCRYPTION_KEY;
				$this->key_found = true;
			}
		}

		private function set_can_encrypt() {

			$this->can_encrypt = $this->enabled && $this->openssl_installed && ($this->key !== false);
		}

		private function set_can_decrypt() {

			$this->can_decrypt = $this->openssl_installed && ($this->key !== false);
		}

		public function encrypt($input) {

			if(!$this->can_encrypt) { return $input; }

			return \Defuse\Crypto\Crypto::encrypt($input, \Defuse\Crypto\Key::loadFromAsciiSafeString($this->key));
		}

		public function decrypt($input) {

			if(!$this->can_decrypt) { return $input; }

			return \Defuse\Crypto\Crypto::decrypt($input, \Defuse\Crypto\Key::loadFromAsciiSafeString($this->key));
		}
	}
