<?php

	class WS_Form_Action_Email extends WS_Form_Action {

		public $id = 'email';
		public $pro_required = false;
		public $label;
		public $label_action;
		public $events;
		public $multiple = true;
		public $configured = true;
		public $priority = 175;
		public $can_repost = true;
		public $form_add = true;

		// Config
		public $from_email;
		public $from_name;
		public $tos;
		public $ccs;
		public $bccs;
		public $reply_to_email;
		public $subject;
		public $message_editor;
		public $message_wrapper;
		public $message_textarea;
		public $message_text_editor;
		public $message_html_editor;
		public $content_type;
		public $charset;
		public $attachments;
		public $headers;

		public function __construct() {

			// Set label
			$this->label = __('Email', 'ws-form');

			// Set label for actions pull down
			$this->label_action = __('Send Email', 'ws-form');

			// Events
			$this->events = array('submit');

			// Register config filters
			add_filter('wsf_config_options', array($this, 'config_options'), 10, 1);
			add_filter('wsf_config_meta_keys', array($this, 'config_meta_keys'), 10, 2);

			// Register action
			parent::register($this);
		}

		public function post($form, &$submit, $config) {

			// Load config
			self::load_config($config);

			// Ensure minimal config is set
			if($this->from_email == '') { self::error(__('No sender email address specified', 'ws-form')); }
			if(count($this->tos) == 0) { self::error(__('No recipients specified', 'ws-form')); }
			if($this->message_textarea == '' && $this->message_text_editor == '' && $this->message_html_editor == '') { self::error(__('No message specified', 'ws-form')); }

			// Get content type
			if(($this->content_type === false) || ($this->content_type == '')) { $this->content_type = 'text/plain'; }
			$email_content_type = WS_Form_Common::parse_variables_process(trim($this->content_type), $form, $submit, $this->content_type);

			// Get character set
			if(($this->charset === false) || ($this->charset == '')) { $this->charset = '#blog_charset'; }
			$email_charset = WS_Form_Common::parse_variables_process(trim($this->charset), $form, $submit, 'text/plain');

			// Build to address
			$email_to = array();
			foreach($this->tos as $to) {

				// Explode in case the email addresses are comma separated
				$to_array = explode(',', $to['action_' . $this->id . '_email']);
				foreach($to_array as $to_email) {

					$to_email = trim($to_email);
					$to_email = WS_Form_Common::parse_variables_process($to_email, $form, $submit, 'text/plain');

					// Ensure email address is valid
					if(!filter_var($to_email, FILTER_VALIDATE_EMAIL)) { continue; }

					$to_full = WS_Form_Common::get_email_address($to_email, ($to['action_' . $this->id . '_name'] != '') ? WS_Form_Common::parse_variables_process($to['action_' . $this->id . '_name'], $form, $submit, 'text/plain') : '');
					if($to_full !== false) { $email_to[] = $to_full; }
				}
			}
			if(count($email_to) == 0) { self::error(__("Invalid 'to' email address(es)", 'ws-form')); }

			// Build subject
			$email_subject = WS_Form_Common::parse_variables_process(trim($this->subject), $form, $submit, 'text/plain');

			// Build headers
			$email_headers = array();

			// Build headers - From
			$email_from = WS_Form_Common::get_email_address(WS_Form_Common::parse_variables_process($this->from_email, $form, $submit, 'text/plain'), ($this->from_name != '') ? WS_Form_Common::parse_variables_process($this->from_name, $form, $submit, 'text/plain') : '');
			if($email_from === false) { self::error(__("Invalid 'From' email address or display name too long", 'ws-form')); }
			$email_headers[] = 'From: ' . $email_from;

			// Build headers - Reply-To
			$reply_to_email_from = WS_Form_Common::get_email_address(WS_Form_Common::parse_variables_process($this->reply_to_email, $form, $submit, 'text/plain'));
			if($reply_to_email_from !== false) {

				$email_headers[] = 'Reply-To: ' . $reply_to_email_from;
			}

			// Build header - CC's
			if(!empty($this->ccs)) {

				foreach($this->ccs as $cc) {

					// Explode in case the email addresses are comma separated
					$cc_array = explode(',', $cc['action_' . $this->id . '_email']);
					foreach($cc_array as $cc_email) {

						$cc_email = trim($cc_email);
						$cc_email = WS_Form_Common::parse_variables_process($cc_email, $form, $submit, 'text/plain');

						// Ensure email address is valid
						if(!filter_var($cc_email, FILTER_VALIDATE_EMAIL)) { continue; }

						$cc_full = WS_Form_Common::get_email_address($cc_email, ($cc['action_' . $this->id . '_name'] != '') ? WS_Form_Common::parse_variables_process($cc['action_' . $this->id . '_name'], $form, $submit, 'text/plain') : '');
						if($cc_full !== false) { $email_headers[] = 'Cc: ' . $cc_full; }
					}
				}
			}

			// Build header - BCC's
			if(!empty($this->bccs)) {

				foreach($this->bccs as $bcc) {

					// Explode in case the email addresses are comma separated
					$bcc_array = explode(',', $bcc['action_' . $this->id . '_email']);
					foreach($bcc_array as $bcc_email) {

						$bcc_email = trim($bcc_email);
						$bcc_email = WS_Form_Common::parse_variables_process($bcc_email, $form, $submit, 'text/plain');

						// Ensure email address is valid
						if(!filter_var($bcc_email, FILTER_VALIDATE_EMAIL)) { continue; }

						$bcc_full = WS_Form_Common::get_email_address($bcc_email, ($bcc['action_' . $this->id . '_name'] != '') ? WS_Form_Common::parse_variables_process($bcc['action_' . $this->id . '_name'], $form, $submit, 'text/plain') : '');
						if($bcc_full !== false) { $email_headers[] = 'Bcc: ' . $bcc_full; }
					}
				}
			}

			// Builder header - Content Type
			$email_headers[] = 'Content-Type: ' . $email_content_type . ';' . (($email_charset !== false) ? ' charset=' . $email_charset : '');

			// Build attachments
			$email_attachments = array();
			$temp_path = get_temp_dir() . 'ws-form-' . $submit->hash;
			if(is_array($this->attachments) && isset($submit->meta)) {

				foreach($this->attachments as $attachment) {

					// Get field_id
					if(!isset($attachment['ws_form_field']) || empty($attachment['ws_form_field'])) { continue; }
					$field_id = $attachment['ws_form_field'];

					// Get field
					if(!isset($submit->meta[WS_FORM_FIELD_PREFIX . $field_id])) { continue; }
					$field = $submit->meta[WS_FORM_FIELD_PREFIX . $field_id];

					// Get value
					if(!isset($field['value'])) { continue; }
					$files = $field['value'];

					// Check files
					if(!is_array($files)) { continue; }

					// Create temporary directory for storing attachment files
					if(!file_exists($temp_path)) { mkdir($temp_path); }

					// Get base upload_dir
					$upload_dir = wp_upload_dir()['basedir'];

					// Process each file
					foreach($files as $file) {

						// Source
						if(!isset($file['path'])) { continue; }
						$filename_source = $upload_dir . '/' . $file['path'];

						// Destination
						if(!isset($file['name'])) { continue; }
						$filename_destination = $temp_path . '/' . $file['name'];

						// Copy file
						if(!copy($filename_source, $filename_destination)) { self::error(sprintf(__('Unable to process attachment: %s', 'ws-form'), $file->name)); continue; }

						// Add file to email_attachments
						$email_attachments[] = $filename_destination;
					}
				}
			}

			// Attachments filter
			$email_attachments = apply_filters('wsf_action_email_email_attachments', $email_attachments, $form, $submit, $config, $temp_path);

			// Build headers
			if(is_array($this->headers)) {

				foreach($this->headers as $header) {

					$header_key = $header['action_' . $this->id . '_header_key'];
					if($header_key == '') { continue; }

					$header_value = $header['action_' . $this->id . '_header_value'];
					if($header_value == '') { continue; }

					$email_headers[] = sprintf('%s: %s', $header_key, $header_value);
				}
			}

			// Email template
			if($this->message_wrapper) {

				$template_filename = 'templates/' . (($email_content_type == 'text/html') ? 'html/standard.html' : 'plain/standard.txt');

				$email_template = file_get_contents(dirname(__FILE__) . '/' . $template_filename);

			} else {

				$email_template = '#email_message';
			}

			// Add message
			$variables = array();
			switch($email_content_type) {

				case 'text/plain' :

					$variables['email_message'] = $this->message_textarea;
					break;

				case 'text/html' :

					switch($this->message_editor) {

						case 'text_editor' :

							$variables['email_message'] = wpautop($this->message_text_editor);
							break;

						case 'html_editor' :

							$variables['email_message'] = $this->message_html_editor;
							break;
					}
			}

			// Build message - Parse local variables
			$variables['email_subject'] = $email_subject;
			$variables['email_content_type'] = $email_content_type;
			$variables['email_charset'] = $email_charset;
			$email_message = WS_Form_Common::mask_parse($email_template, $variables);

			// Build message - Parse main variables
			$email_message = WS_Form_Common::parse_variables_process($email_message, $form, $submit, $email_content_type);

			// Filters
			$email_to = apply_filters('wsf_action_email_to', $email_to);
			$email_subject = apply_filters('wsf_action_email_subject', $email_subject);
			$email_message = apply_filters('wsf_action_email_message', $email_message);
			$email_headers = apply_filters('wsf_action_email_headers', $email_headers);
			$email_attachments = apply_filters('wsf_action_email_attachments', $email_attachments);

			// If there are any errors, bail
			if(parent::error_count() == 0) {

				// Run wp_mail
				$email_return = wp_mail($email_to, $email_subject, $email_message, $email_headers, $email_attachments);

			} else {

				$email_return = false;
			}

			// Tidy up attachments
			if(count($email_attachments) > 0) {

				foreach($email_attachments as $filename_destination) {

					// Delete each file
					if(file_exists($filename_destination)) {

						unlink($filename_destination);
					}
				}

				// Remove temporary path
				rmdir($temp_path);
			}

			// Check response
			if($email_return) {

				self::success(__('Email successfully sent', 'ws-form'));

			} else {

				self::error(__('Error sending email', 'ws-form'));
			}
		}

		public function load_config($config) {

			// Get configuration
			$this->from_email = 			parent::get_config($config, 'action_' . $this->id . '_from_email');
			$this->from_name = 				parent::get_config($config, 'action_' . $this->id . '_from_name');
			$this->tos = 					parent::get_config($config, 'action_' . $this->id . '_to');
			if(!is_array($this->tos)) { $this->tos = array(); }
			$this->ccs = 					parent::get_config($config, 'action_' . $this->id . '_cc');
			if(!is_array($this->ccs)) { $this->ccs = array(); }
			$this->bccs = 					parent::get_config($config, 'action_' . $this->id . '_bcc');
			if(!is_array($this->bccs)) { $this->bccs = array(); }
			$this->reply_to_email = 		parent::get_config($config, 'action_' . $this->id . '_reply_to_email');
			$this->subject = 				parent::get_config($config, 'action_' . $this->id . '_subject');
			$this->message_editor = 		parent::get_config($config, 'action_' . $this->id . '_message_editor');
			$this->message_wrapper = 		parent::get_config($config, 'action_' . $this->id . '_message_wrapper');
			$this->message_textarea = 		parent::get_config($config, 'action_' . $this->id . '_message_textarea');
			$this->message_text_editor = 	parent::get_config($config, 'action_' . $this->id . '_message_text_editor');
			$this->message_html_editor = 	parent::get_config($config, 'action_' . $this->id . '_message_html_editor');
			$this->content_type =			parent::get_config($config, 'action_' . $this->id . '_content_type');
			$this->attachments = 			parent::get_config($config, 'action_' . $this->id . '_attachments');
			if(!is_array($this->attachments)) { $this->attachments = array(); }
			$this->headers = 				parent::get_config($config, 'action_' . $this->id . '_headers');
			if(!is_array($this->headers)) { $this->headers = array(); }
			$this->charset = 				parent::get_config($config, 'action_' . $this->id . '_charset');
		}

		// Get settings
		public function get_action_settings() {

			$settings = array(

				'meta_keys'		=> array(

					'action_' . $this->id . '_content_type',
					'action_' . $this->id . '_from_email',
					'action_' . $this->id . '_from_name',
					'action_' . $this->id . '_to',
					'action_' . $this->id . '_cc',
					'action_' . $this->id . '_bcc',
					'action_' . $this->id . '_reply_to_email',
					'action_' . $this->id . '_subject',
					'action_' . $this->id . '_message_editor',
					'action_' . $this->id . '_message_wrapper',
					'action_' . $this->id . '_message_textarea',
					'action_' . $this->id . '_message_text_editor',
					'action_' . $this->id . '_message_html_editor',
					'action_' . $this->id . '_attachments',
					'action_' . $this->id . '_headers',
					'action_' . $this->id . '_charset',
				)
			);

			// Wrap settings so they will work with sidebar_html function in admin.js
			$settings = parent::get_settings_wrapper($settings);

			// Add labels
			$settings->label = $this->label;
			$settings->label_action = $this->label_action;

			// Add multiple
			$settings->multiple = $this->multiple;

			// Add events
			$settings->events = $this->events;

			// Add can_repost
			$settings->can_repost = $this->can_repost;

			// Apply filter
			$settings = apply_filters('wsf_action_' . $this->id . '_settings', $settings);

			return $settings;
		}

		// Meta keys for this action
		public function config_meta_keys($meta_keys = array(), $form_id = 0) {

			// Build config_meta_keys
			$config_meta_keys = array(

				// Content type
				'action_' . $this->id . '_content_type'	=> array(

					'label'						=>	__('Content Type', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	array(

						array('value' => 'text/plain', 'text' => __('Plain text', 'ws-form')),
						array('value' => 'text/html', 'text' => __('HTML', 'ws-form')),
					),
					'help'						=>	__('Email content MIME type.', 'ws-form'),
					'default'					=>	'text/html'
				),

				// From - Email
				'action_' . $this->id . '_from_email'	=> array(

					'label'						=>	__('From Email Address', 'ws-form'),
					'type'						=>	'email',
					'help'						=>	__('Email address email sent from.', 'ws-form'),
					'default'					=>	'#blog_admin_email',
					'select_list'				=>	true
				),

				// From - Display Name
				'action_' . $this->id . '_from_name'	=> array(

					'label'						=>	__('From Display Name (Optional)', 'ws-form'),
					'type'						=>	'text',
					'help'						=>	__('Display name email sent from.', 'ws-form'),
					'default'					=>	'#blog_name',
					'select_list'				=>	true
				),

				// To
				'action_' . $this->id . '_to'	=> array(

					'label'						=>	__('To', 'ws-form'),
					'type'						=>	'repeater',
					'meta_keys'					=>	array(

						'action_' . $this->id . '_email',
						'action_' . $this->id . '_name'
					),
					'help'						=>	__('Email address(es) to send email to.', 'ws-form'),
					'default'					=>	array(

						array(

							'action_' . $this->id . '_email' 	=> '#blog_admin_email',
							'action_' . $this->id . '_name' 	=> '#blog_name'
						)
					)
				),

				// CC
				'action_' . $this->id . '_cc'	=> array(

					'label'						=>	__('CC', 'ws-form'),
					'type'						=>	'repeater',
					'meta_keys'					=>	array(

						'action_' . $this->id . '_email',
						'action_' . $this->id . '_name'
					),
					'help'						=>	__('Email address(es) to carbon copy email to.', 'ws-form')
				),

				// BCC
				'action_' . $this->id . '_bcc'	=> array(

					'label'						=>	__('BCC', 'ws-form'),
					'type'						=>	'repeater',
					'meta_keys'					=>	array(

						'action_' . $this->id . '_email',
						'action_' . $this->id . '_name'
					),
					'help'						=>	__('Email address(es) to blind carbon copy email to.', 'ws-form')
				),

				// Reply-To - Email
				'action_' . $this->id . '_reply_to_email'	=> array(

					'label'						=>	__('Reply To Email Address', 'ws-form'),
					'type'						=>	'email',
					'help'						=>	__('Email address replies will be sent to.', 'ws-form'),
					'default'					=>	'',
					'select_list'				=>	true
				),

				// Subject
				'action_' . $this->id . '_subject'	=> array(

					'label'						=>	__('Subject', 'ws-form'),
					'type'						=>	'text',
					'help'						=>	__('Email subject.', 'ws-form'),
					'default'					=>	'#form_label',
					'select_list'				=>	true
				),

				// Message - Format
				'action_' . $this->id . '_message_editor'	=> array(

					'label'						=>	__('Message Editor', 'ws-form'),
					'type'						=>	'select',
					'options'					=>	array(

						array('value' => 'text_editor', 'text' => __('Visual / Text', 'ws-form')),
						array('value' => 'html_editor', 'text' => __('HTML', 'ws-form'))
					),
					'default'					=>	'text_editor',
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							'meta_key'		=>	'action_' . $this->id . '_content_type',
							'meta_value'	=>	'text/html'
						)
					)
				),

				// Message - Wrapper
				'action_' . $this->id . '_message_wrapper'	=> array(

					'label'						=>	__('Wrap message in header and footer?', 'ws-form'),
					'type'						=>	'checkbox',
					'help'						=>	__('Enabling this will wrap your message in a standard header and footer for convenience.', 'ws-form'),
					'default'					=>	'on'
				),

				// Message - Text Area
				'action_' . $this->id . '_message_textarea'	=> array(

					'label'						=>	__('Message', 'ws-form'),
					'type'						=>	'textarea',
					'help'						=>	__('Email message.', 'ws-form'),
					'default'					=>	'#email_submission',
					'condition'					=>	array(

						array(

							'logic'			=>	'==',
							'meta_key'		=>	'action_' . $this->id . '_content_type',
							'meta_value'	=>	'text/plain'
						),
					),
					'select_list'				=>	true
				),

				// Message - WordPress Editor
				'action_' . $this->id . '_message_text_editor'	=> array(

					'label'						=>	__('Message', 'ws-form'),
					'type'						=>	'text_editor',
					'help'						=>	__('Email message.', 'ws-form'),
					'default'					=>	"<h3>#email_subject</h3>\n\n#email_submission",
					'css'						=>	'css_email',
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_content_type',
							'meta_value'		=>	'text/html'
						),

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_message_editor',
							'meta_value'		=>	'text_editor',
							'logic_previous'	=>	'&&'
						)
					),
					'select_list'				=>	true
				),

				// Message - HTML Editor
				'action_' . $this->id . '_message_html_editor'	=> array(

					'label'						=>	__('Message', 'ws-form'),
					'type'						=>	'html_editor',
					'help'						=>	__('Email message.', 'ws-form'),
					'default'					=>	"<h1>#email_subject</h1>\n\n#email_submission",
					'condition'					=>	array(

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_content_type',
							'meta_value'		=>	'text/html'
						),

						array(

							'logic'				=>	'==',
							'meta_key'			=>	'action_' . $this->id . '_message_editor',
							'meta_value'		=>	'html_editor',
							'logic_previous'	=>	'&&'
						)
					),
					'select_list'				=>	true
				),

				// Character set
				'action_' . $this->id . '_charset'	=> array(

					'label'						=>	__('Character Set', 'ws-form'),
					'type'						=>	'text',
					'help'						=>	__('Email character set', 'ws-form'),
					'default'					=>	'#blog_charset',
					'select_list'				=>	true
				),

				// Attachments
				'action_' . $this->id . '_attachments'	=> array(

					'label'						=>	__('Attachments', 'ws-form'),
					'type'						=>	'repeater',
					'meta_keys'					=>	array(

						'ws_form_field_file',
					),
					'help'						=>	__('Email attachments.', 'ws-form')
				),

				// Email address
				'action_' . $this->id . '_email'	=> array(

					'label'						=>	__('Email Address', 'ws-form'),
					'type'						=>	'email'
				),

				// Name
				'action_' . $this->id . '_name'	=> array(

					'label'						=>	__('Display Name', 'ws-form'),
					'type'						=>	'text'
				),

				// Headers
				'action_' . $this->id . '_headers'	=> array(

					'label'						=>	__('Headers', 'ws-form'),
					'type'						=>	'repeater',
					'meta_keys'					=>	array(

						'action_' . $this->id . '_header_key',
						'action_' . $this->id . '_header_value'
					),
					'help'						=>	__('Additional email headers.', 'ws-form')
				),

				// Header key
				'action_' . $this->id . '_header_key'	=> array(

					'label'						=>	__('Header Key', 'ws-form'),
					'type'						=>	'text'
				),

				// Header value
				'action_' . $this->id . '_header_value'	=> array(

					'label'						=>	__('Header Value', 'ws-form'),
					'type'						=>	'text'
				)
			);

			// Merge
			$meta_keys = array_merge($meta_keys, $config_meta_keys);

			return $meta_keys;
		}

		// Plug-in options for this action
		public function config_options($options) {

			$options['action_' . $this->id] = array(

				'label'		=>	$this->label,

				'groups'	=>	array(

					'logo'	=>	array(

						'heading'		=>	__('Logo', 'ws-form'),

						'fields'	=>	array(

							'action_' . $this->id . '_logo'	=>	array(

								'label'		=>	__('Image', 'ws-form'),
								'type'		=>	'image',
								'button'	=>	'wsf-image',
								'help'		=>	__('Use #email_logo in your email template to add this logo.', 'ws-form')
							),

							'action_' . $this->id . '_logo_size'	=>	array(

								'label'		=>	__('Size', 'ws-form'),
								'type'		=>	'image_size',
								'default'	=>	'full',
								'help'		=>	__('Recommended max dimensions: 400 x 200 pixels.')
							)
						)
					),

					'email_submission'	=>	array(

						'heading'		=>	'#email_submission',

						'fields'	=>	array(

							'action_' . $this->id . '_group_labels'	=> array(

								'label'		=>	__('Render Tab Labels', 'ws-form'),
								'type'		=>	'select',
								'default'	=>	'auto',
								'options'	=>	array(

									'auto'				=>	array('text' => __('Auto (Hidden if no sections in tab)', 'ws-form')),
									'true'				=>	array('text' => __('Yes', 'ws-form')),
									'false'				=>	array('text' => __('No', 'ws-form'))
								)
							),

							'action_' . $this->id . '_section_labels'	=> array(

								'label'		=>	__('Render Section Labels', 'ws-form'),
								'type'		=>	'select',
								'default'	=>	'auto',
								'options'	=>	array(

									'auto'				=>	array('text' => __('Auto (Hidden if no fields in section)', 'ws-form')),
									'true'				=>	array('text' => __('Yes', 'ws-form')),
									'false'				=>	array('text' => __('No', 'ws-form'))
								)
							),

							'action_' . $this->id . '_field_labels'	=> array(

								'label'		=>	__('Render Field Labels', 'ws-form'),
								'type'		=>	'select',
								'default'	=>	'auto',
								'options'	=>	array(

									'auto'				=>	array('text' => __("Auto (Hidden if 'Render Label' not checked)", 'ws-form')),
									'true'				=>	array('text' => __('Yes', 'ws-form')),
									'false'				=>	array('text' => __('No', 'ws-form'))
								)
							),

							'action_' . $this->id . '_static_fields'	=>	array(

								'label'		=>	__('Render Static Fields', 'ws-form'),
								'type'		=>	'checkbox',
								'default'	=>	true,
								'help'		=>	__('Render static fields such as text and HTML, if not excluded at a field level.')
							),

							'action_' . $this->id . '_exclude_empty'	=>	array(

								'label'		=>	__('Exclude Empty Fields', 'ws-form'),
								'type'		=>	'checkbox',
								'default'	=>	true,
								'help'		=>	__('Exclude empty fields in email templates.')
							),

							'action_' . $this->id . '_embed_images'	=>	array(

								'label'		=>	__('Embed Images', 'ws-form'),
								'type'		=>	'checkbox',
								'default'	=>	false,
								'help'		=>	__('If checked, file and signature images will be embedded in the email template.')
							)
						)
					)
				)
			);

			return $options;
		}
	}

	new WS_Form_Action_Email();
