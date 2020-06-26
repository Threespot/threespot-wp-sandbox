<?php

	add_filter('wsf_config_migrate', 'wsf_migrate_vfb');

	function wsf_migrate_vfb($migrate) {

		$migrate['vfb'] = array(

			// Name of the plugin to migrate
			'label' => __('Visual Form Builder', 'ws-form'),

			// Version
			'version' => '3.3.x+',

			// Paths to detect this plugin
			'detect' => array('visual-form-builder/visual-form-builder.php', 'vfb-pro/vfb-pro.php'),

			// Tables to detect this plugins data
			'detect_table' => array('vfbp_forms', 'vfbp_formmeta'),

			// Forms
			'form' => array(

				// Form table configuration
				'table_record'		=> array(

					// SQL parts
					'count'			=> 'id',							// Field to use if counting records
					'select'		=> 'id,title,status,data',
					'from'			=> '#table_prefixvfbp_forms',
					'join'			=> '',
					'where'			=> '',
					'where_single'	=> 'id=#form_id',
					'order_by'		=> 'title',

					// Map plugin fields to WS Form parts

					// source 		Plugin key
					// destination 	WS Form key 
					// type
					//		scratch 		Disregarded prior to DB write, used for lookups
					// 		record 			Save at record level
					// 		meta 			Save at meta level 			(Form and field data only)
					// 		meta-submit 	Save at submit meta level 	(Submission data only)
					// 		serialize 		Process as serialized data
					'map'	=> array(

						array('source' => 'id', 'type' => 'record', 'destination' => 'id'),
						array('source' => 'title', 'type' => 'record', 'destination' => 'label', 'default' => WS_FORM_DEFAULT_FORM_NAME),
						array('source' => 'status', 'type' => 'record', 'destination' => 'status'),
						array('source' => 'data', 'type' => 'serialize', 'map' => array(

							array('source' => 'label-alignment', 'type' => 'meta', 'destination' => 'label_position_form', 'lookup' => array(

								array('find' => '', 'replace' => 'top'),
								array('find' => 'horizontal', 'replace' => 'horizontal')
							)),

							array('source' => 'on-submit', 'type' => 'scratch', 'destination' => 'action_javascript_javascript', 'action' => 'javascript'),
							array('source' => 'limit', 'type' => 'meta', 'destination' => 'submit_limit'),
							array('source' => 'limit-message', 'type' => 'meta', 'destination' => 'submit_limit_message'),
							array('source' => 'expiration', 'type' => 'meta', 'destination' => 'date_expire'),
							array('source' => 'expiration-message', 'type' => 'meta', 'destination' => 'date_expire_message'),
						))
					),
				),

				'table_metadata'	=> array(

					'select' 	=> 'meta_key,meta_value',
					'from' 		=> '#table_prefixvfbp_formmeta',
					'join' 		=> '',
					'where' 	=> 'form_id=#record_id',

					'map' => array(

						// Notification
						array('source' => 'from-name', 'type' => 'scratch', 'destination' => 'email_blog_from_name', 'action' => 'email_blog'),
						array('source' => 'reply-to', 'type' => 'scratch', 'destination' => 'email_blog_from_email', 'action' => 'email_blog'),
						array('source' => 'subject', 'type' => 'scratch', 'destination' => 'email_blog_subject', 'action' => 'email_blog'),
						array('source' => 'email-to', 'type' => 'scratch', 'destination' => 'email_blog_to_email', 'action' => 'email_blog'),
						array('source' => 'cc', 'type' => 'scratch', 'destination' => 'email_blog_cc_email', 'action' => 'email_blog'),
						array('source' => 'bcc', 'type' => 'scratch', 'destination' => 'email_blog_bcc_email', 'action' => 'email_blog'),

						// Autoresponder
						array('source' => 'notify-name', 'type' => 'scratch', 'destination' => 'email_user_from_name', 'action' => 'email_user'),
						array('source' => 'notify-email', 'type' => 'scratch', 'destination' => 'email_user_from_email', 'action' => 'email_user'),
						array('source' => 'notify-subject', 'type' => 'scratch', 'destination' => 'email_user_subject', 'action' => 'email_user'),
						array('source' => 'notify-email-to', 'type' => 'scratch', 'destination' => 'email_user_to_email', 'action' => 'email_user'),
						array('source' => 'notify-message', 'type' => 'scratch', 'destination' => 'email_user_message', 'action' => 'email_user'),

						// Confirmation
						array('source' => 'text-message', 'type' => 'scratch', 'destination' => 'message_message', 'action' => 'message'),
						array('source' => 'wp-page', 'type' => 'scratch', 'destination' => 'wp_page', 'action' => 'redirect_wp_page'),
						array('source' => 'redirect', 'type' => 'scratch', 'destination' => 'redirect_url', 'action' => 'redirect_url'),
					)
				),

				'action'	=> array(

					// On Submit
					'javascript' => array(

						'action_id'	=>	'javascript',
						'meta'		=>	array(

							'action_javascript_javascript' => '#action_javascript_javascript'
						)
					),

					// Notification
					'email_blog' => array(

						'action_id'	=>	'email',
						'meta'		=>	array(

							'action_email_from_email' => '#email_blog_from_email',
							'action_email_from_name' => '#email_blog_from_name',
							'action_email_to' => array(
								array(

									'action_email_email' => '#email_blog_to_email',
									'action_email_name' => ''
								)
							),
							'action_email_cc' => array(
								array(

									'action_email_email' => '#email_blog_cc_email',
									'action_email_name' => ''
								)
							),
							'action_email_bcc' => array(
								array(

									'action_email_email' => '#email_blog_bcc_email',
									'action_email_name' => ''
								)
							),
							'action_email_subject' => '#email_blog_subject'
						)
					),

					// Autoresponder
					'email_user' => array(

						'action_id'	=>	'email',
						'meta'		=>	array(

							'action_email_from_email' => '#email_user_from_email',
							'action_email_from_name' => '#email_user_from_name',
							'action_email_to' => array(
								array(

									'action_email_email' => '#field(#email_user_to_email)',
									'action_email_name' => ''
								)
							),
							'action_email_message_textarea' => '#email_user_message',
							'action_email_message_text_editor' => '#email_user_message',
							'action_email_message_html_editor' => '#email_user_message',
						)
					),

					// Save to database
					'database' => array(

						'action_id'	=>	'database',
						'force'		=>	true
					),

					// Message
					'message' => array(

						'action_id'	=>	'message',
						'meta'		=>	array(

							'action_message_message' => '#message_message'
						)
					),

					// Redirect
					'redirect_url' => array(

						'action_id'	=>	'redirect',
						'meta'		=>	array(

							'action_redirect_url' => '#redirect_url'
						)
					),

					// WP Page
					'redirect_wp_page' => array(

						'action_id'	=>	'redirect',
						'meta'		=>	array(

							'action_redirect_url' => '/?p=#wp_page'
						)
					),
				)
			),

			'group' => array(

				'records' => 'inline',

				'table_record'		=> array(

					'count'		=> 'id',							// Field to use if counting records
					'select'	=> 'id,field_type,field_order,data',
					'from'		=> '#table_prefixvfbp_fields',
					'join'		=> '',
					'where'		=> 'form_id=#form_id',
					'order_by'	=> 'field_order',

					'map'		=> array(

						array('source' => 'field_type', 'type' => 'scratch', 'destination' => 'type_source'),
						array('source' => 'data', 'type' => 'serialize', 'map_by_type' => array(

							'page-break'	=>	array(

								array('type' => 'record', 'destination' => 'id', 'value' => true),
								array('group' => true),
							)
						))
					)
				)
			),

			'group' => array(

				'records' => 'inline',

				'table_record'		=> array(

					'count'		=> 'id',							// Field to use if counting records
					'select'	=> 'id,field_type,field_order,data',
					'from'		=> '#table_prefixvfbp_fields',
					'join'		=> '',
					'where'		=> 'form_id=#form_id',
					'order_by'	=> 'field_order',

					'map'		=> array(

						array('source' => 'field_type', 'type' => 'scratch', 'destination' => 'type_source'),
						array('source' => 'data', 'type' => 'serialize', 'map_by_type' => array(

							'page-break'	=>	array(

								array('type' => 'record', 'destination' => 'id', 'value' => true),
								array('source' => 'label', 'type' => 'record', 'destination' => 'label'),
								array('group' => true),
							)
						))
					)
				)
			),

			'section' => array(

				'records' => 'inline',

				'table_record'		=> array(

					'count'		=> 'id',							// Field to use if counting records
					'select'	=> 'id,field_type,field_order,data',
					'from'		=> '#table_prefixvfbp_fields',
					'join'		=> '',
					'where'		=> 'form_id=#form_id',
					'order_by'	=> 'field_order',

					'map'		=> array(

						array('source' => 'field_type', 'type' => 'scratch', 'destination' => 'type_source'),
						array('source' => 'data', 'type' => 'serialize', 'map_by_type' => array(

							'page-break'	=>	array(

								array('type' => 'record', 'destination' => 'id', 'value' => true),
								array('group' => true),
							)
						))
					)
				)
			),

			'field' => array(

				'table_record'		=> array(

					'count'		=> 'id',							// Field to use if counting records
					'select'	=> 'id,field_type,field_order,data',
					'from'		=> '#table_prefixvfbp_fields',
					'join'		=> '',
					'where'		=> 'form_id=#form_id',
					'order_by'	=> 'field_order',

					'map'		=> array(

						array('source' => 'id', 'type' => 'record', 'destination' => 'id'),
						array('source' => 'field_type', 'type' => 'scratch', 'destination' => 'type_source'),
						array('source' => 'field_type', 'type' => 'record', 'destination' => 'type', 'lookup' => array(

								array('find' => 'text', 'replace' => 'text'),
								array('find' => 'submit', 'replace' => 'submit'),
								array('find' => 'heading', 'replace' => 'texteditor'),
								array('find' => 'textarea', 'replace' => 'textarea'),
								array('find' => 'checkbox', 'replace' => 'checkbox'),
								array('find' => 'radio', 'replace' => 'radio'),
								array('find' => 'select', 'replace' => 'select'),
								array('find' => 'address', 'replace' => 'textarea'),
								array('find' => 'date', 'replace' => 'datetime'),
								array('find' => 'email', 'replace' => 'email'),
								array('find' => 'url', 'replace' => 'url'),
								array('find' => 'currency', 'replace' => 'price'),
								array('find' => 'number', 'replace' => 'number'),
								array('find' => 'time', 'replace' => 'datetime'),
								array('find' => 'phone', 'replace' => 'tel'),
								array('find' => 'html', 'replace' => 'textarea'),
								array('find' => 'file-upload', 'replace' => 'file'),
								array('find' => 'instructions', 'replace' => 'texteditor'),
								array('find' => 'name', 'replace' => 'text'),
								array('find' => 'captcha', 'replace' => 'recaptcha'),
								array('find' => 'hidden', 'replace' => 'hidden'),
								array('find' => 'color-picker', 'replace' => 'color'),
								array('find' => 'autocomplete', 'replace' => 'text'),
								array('find' => 'range-slider', 'replace' => 'range'),
								array('find' => 'min', 'replace' => 'number'),
								array('find' => 'max', 'replace' => 'number'),
								array('find' => 'range', 'replace' => 'number'),
								array('find' => 'rating', 'replace' => 'rating'),
								array('find' => 'likert', 'replace' => 'text'),
								array('find' => 'page-break', 'replace' => false),
								array('find' => 'knob', 'replace' => 'number'),
							)
						),
						array('source' => 'field_order', 'type' => 'record', 'destination' => 'sort_index'),
						array('source' => 'data', 'type' => 'serialize', 'map_by_type' => array(

							'heading'	=>	array(

								array('type' => 'record', 'destination' => 'label', 'value' => __('Text Editor', 'ws-form')),
								array('source' => 'heading-type', 'type' => 'scratch', 'destination' => 'heading_type'),
								array('source' => 'css', 'type' => 'scratch', 'destination' => 'css', 'mask' => ' class="#value"', 'mask_disregard_on_empty' => true),
								array('source' => 'label', 'type' => 'meta', 'destination' => 'text_editor', 'mask' => '<#heading_type#css>#value</#heading_type>'),
							),

							'date'	=>	array(

								array('source' => 'default_value', 'type' => 'meta', 'destination' => 'default_value', 'process' => array(array('process' => 'date_to_input_value'))),
								array('type' => 'meta', 'destination' => 'input_type_datetime', 'value' => 'date'),
								array('source' => 'date', 'type' => 'array', 'map' => array(

									array('source' => 'start-date', 'type' => 'meta', 'destination' => 'min_date', 'process' => array(array('process' => 'date_to_input_value'))),
									array('source' => 'end-date', 'type' => 'meta', 'destination' => 'max_date', 'process' => array(array('process' => 'date_to_input_value')))
								))
							),

							'time'	=>	array(

								array('source' => 'default_value', 'type' => 'meta', 'destination' => 'default_value', 'process' => array(array('process' => 'time_to_input_value'))),
								array('type' => 'meta', 'destination' => 'input_type_datetime', 'value' => 'time')
							),

							'html'	=>	array(

								array('type' => 'meta', 'destination' => 'input_type_textarea', 'value' => 'tinymce'),
							),

							'file-upload'	=>	array(

								array('source' => 'file', 'type' => 'array', 'map' => array(

									array('source' => 'allowed-file-types', 'type' => 'meta', 'destination' => 'accept', 'lookup_csv_join' => ',', 'lookup_csv' => array(

										'image' => 'image/gif,image/png,image/jpeg',
										'html' => 'text/html',
										'office' => 'application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation',
										'gdocs' => 'application/vnd.google-apps.audio,application/vnd.google-apps.document,application/vnd.google-apps.drawing,application/vnd.google-apps.file,application/vnd.google-apps.folder,application/vnd.google-apps.form,application/vnd.google-apps.fusiontable,application/vnd.google-apps.map,application/vnd.google-apps.photo,application/vnd.google-apps.presentation,application/vnd.google-apps.script,application/vnd.google-apps.site,application/vnd.google-apps.spreadsheet,application/vnd.google-apps.unknown,application/vnd.google-apps.video,application/vnd.google-apps.drive-sdk',
										'text' => 'text/plain',
										'video' => 'video/og,video/mp4,video/webm,video/mpg,video/mov,video/3gp',
										'audio' => 'audio/og,audio/mp3,audio/mpg,audio/wav',
										'pdf' => 'application/pdf'
									)),
									array('source' => 'multiple', 'type' => 'meta', 'destination' => 'multiple_file', 'lookup' => array(

										array('find' => '', 'replace' => ''),
										array('find' => '1', 'replace' => 'on')
									))
								))
							),

							'instructions'	=>	array(

								array('type' => 'meta', 'destination' => 'help', 'value' => ''),
								array('source' => 'css', 'type' => 'scratch', 'destination' => 'css', 'mask' => ' class="#value"', 'mask_disregard_on_empty' => true),
								array('source' => 'description', 'type' => 'meta', 'destination' => 'text_editor', 'mask' => '<p#css>#value</p>'),
							),

							'captcha'	=>	array(

								array('type' => 'array', 'value_option' => 'vfbp_settings', 'map' => array(

									array('source' => 'recaptcha-public-key', 'type' => 'meta', 'destination' => 'recaptcha_site_key'),
									array('source' => 'recaptcha-private-key', 'type' => 'meta', 'destination' => 'recaptcha_secret_key')
								)),
								array('source' => 'captcha', 'type' => 'array', 'map' => array(

									array('source' => 'type', 'type' => 'meta', 'destination' => 'recaptcha_type', 'lookup' => array(

										array('find' => '', 'replace' => 'image'),
										array('find' => 'audio', 'replace' => 'audio'),
									)),

									array('source' => 'theme', 'type' => 'meta', 'destination' => 'recaptcha_theme', 'lookup' => array(

										array('find' => '', 'replace' => 'light'),
										array('find' => 'dark', 'replace' => 'dark'),
									)),

									array('source' => 'lang', 'type' => 'meta', 'destination' => 'recaptcha_language')
								))
							),

							'hidden'	=>	array(

								array('source' => 'hidden', 'type' => 'array', 'map' => array(

									array('source' => 'option', 'type' => 'meta', 'destination' => 'default_value', 'lookup' => array(

										array('find' => 'form_id', 'replace' => '#form_id'),
										array('find' => 'form_title', 'replace' => '#form_label'),
										array('find' => 'ip', 'replace' => '#tracking_remote_ip'),
										array('find' => 'uid', 'replace' => '#submit_hash'),
										array('find' => 'sequential-num', 'replace' => '#submit_id'),
										array('find' => 'date-today', 'replace' => '#client_date'),
										array('find' => 'post_id', 'replace' => '#post_id'),
										array('find' => 'post_title', 'replace' => '#post_title'),
										array('find' => 'post_url', 'replace' => '#post_url'),
										array('find' => 'current_user_id', 'replace' => '#user_id'),
										array('find' => 'current_user_name', 'replace' => '#user_display_name'),
										array('find' => 'current_user_username', 'replace' => '#user_login'),
										array('find' => 'current_user_email', 'replace' => '#user_email')
									))
								))
							),

							'range-slider'	=>	array(

								array('source' => 'range-slider', 'type' => 'array', 'map' => array(

									array('source' => 'min', 'type' => 'meta', 'destination' => 'min'),
									array('source' => 'max', 'type' => 'meta', 'destination' => 'max'),
									array('source' => 'step', 'type' => 'meta', 'destination' => 'step')
								))
							),

							'knob'	=>	array(

								array('source' => 'knob', 'type' => 'array', 'map' => array(

									array('source' => 'min', 'type' => 'meta', 'destination' => 'min'),
									array('source' => 'max', 'type' => 'meta', 'destination' => 'max'),
									array('source' => 'step', 'type' => 'meta', 'destination' => 'step')
								))
							),

							'rating'	=>	array(

								array('source' => 'rating', 'type' => 'array', 'map' => array(

									array('source' => 'max', 'type' => 'meta', 'destination' => 'rating_max'),
									array('source' => 'icon', 'type' => 'meta', 'destination' => 'rating_icon', 'lookup' => array(

										array('find' => 'star-v1', 'replace' => 'star'),
										array('find' => 'star-v2', 'replace' => 'star'),
										array('find' => 'heart-v1', 'replace' => 'heart'),
										array('find' => 'heart-v2', 'replace' => 'heart'),
										array('find' => 'check-v1', 'replace' => 'check'),
										array('find' => 'flag-v1', 'replace' => 'flag')
									)),
								))
							),

							'page-break'	=>	array(

								array('group' => true)
							),

							'checkbox'	=>	array(

								array('source' => 'options', 'type' => 'meta', 'destination' => 'data_grid_checkbox', 'process' => array(array('process' => 'datagrid')), 'map' => array(

									array('source' => 'default', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

										array('find' => '', 'replace' => ''),
										array('find' => '1', 'replace' => 'on')
									)),
									array('source' => 'label', 'type' => 'record', 'destination' => 'label'),
									array('type' => 'record', 'destination' => 'required', 'value' => '#required')
								)),
							),

							'radio'	=>	array(

								array('source' => 'options', 'type' => 'meta', 'destination' => 'data_grid_radio', 'process' => array(array('process' => 'datagrid')), 'map' => array(

									array('source' => 'default', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

										array('find' => '', 'replace' => ''),
										array('find' => '1', 'replace' => 'on')
									)),
									array('source' => 'label', 'type' => 'record', 'destination' => 'label')
								)),
							),

							'select'	=>	array(

								array('source' => 'options', 'type' => 'meta', 'destination' => 'data_grid_select', 'process' => array(array('process' => 'datagrid')), 'map' => array(

									array('source' => 'default', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

										array('find' => '', 'replace' => ''),
										array('find' => '1', 'replace' => 'on')
									)),
									array('source' => 'label', 'type' => 'record', 'destination' => 'label')
								)),
							)

						),

						'map' => array(

							array('source' => 'label', 'type' => 'meta', 'destination' => 'label_render', 'value' => '', 'condition' => array('#meta_value' => '')),
							array('source' => 'label', 'type' => 'record', 'destination' => 'label', 'default' => '#field_label_default'),
							array('source' => 'description', 'type' => 'meta', 'destination' => 'help'),
							array('source' => 'cols-options', 'type' => 'meta', 'destination' => 'class_inline', 'lookup' => array(

								array('find' => 'inline', 'replace' => 'on'),
								array('find' => '', 'replace' => ''),
								array('find' => '2', 'replace' => ''),
								array('find' => '3', 'replace' => '')
							)),
							array('source' => 'required', 'type' => 'meta', 'destination' => 'required', 'lookup' => array(

								array('find' => '', 'replace' => ''),
								array('find' => '1', 'replace' => 'on')
							)),
							array('source' => 'min-num', 'type' => 'meta', 'destination' => 'min'),
							array('source' => 'max-num', 'type' => 'meta', 'destination' => 'max'),
							array('source' => 'min-words', 'type' => 'meta', 'destination' => 'min_length_words'),
							array('source' => 'max-words', 'type' => 'meta', 'destination' => 'max_length_words'),
							array('source' => 'default_value', 'type' => 'meta', 'destination' => 'default_value'),
							array('source' => 'placeholder', 'type' => 'meta', 'destination' => 'placeholder'),
							array('source' => 'cols', 'type' => 'meta', 'destination' => 'breakpoint_size_25'),
							array('source' => 'textarea-rows', 'type' => 'meta', 'destination' => 'rows'),
							array('source' => 'input-mask', 'type' => 'meta', 'destination' => 'pattern'),
							array('source' => 'css', 'type' => 'meta', 'destination' => 'class_field'),
						))
					)
				),

				// Field type processing
				// br_to_newline 	Convert br tags to newlines
				// strip_tags 		Strip HTML tags
				// csv_to_array 	Comma separated values to array
				'process'	=>	array(

					// WS Form field type
					'textarea'		=>	array(array('process' => 'br_to_newline'), array('process' => 'strip_tags')),
					'checkbox'		=>	array(array('process' => 'csv_to_array')),
					'radio'			=>	array(array('process' => 'csv_to_array')),
					'select'		=>	array(array('process' => 'csv_to_array')),
					'signature' 	=>	array(array('process' => 'img_base64_to_file')),
					'file' 			=>	array(array('process' => 'upload_url_to_file'))
				)
			),

			'submission' => array(

				'table_record'		=> array(

					'count'		=> 'id',							// Field to use if counting records
					'select'	=> 'ID,post_date,post_status,post_author',
					'from' 		=> '#table_prefixposts',
					'join'		=> ' LEFT JOIN wp_postmeta ON (wp_postmeta.post_id = wp_posts.id AND wp_postmeta.meta_key = \'_vfb_form_id\')',
					'where' 	=> 'wp_posts.post_type=\'vfb_entry\' AND wp_postmeta.meta_value = \'#form_id\'',

					'map'	=> array(

						// Mandatory mappings
						array('source' => 'ID', 'type' => 'record', 'destination' => 'id'),
						array('source' => 'post_status', 'type' => 'record', 'destination' => 'status'),
						array('source' => 'post_author', 'type' => 'record', 'destination' => 'user_id'),

						// Optional mappings
						array('source' => 'post_date', 'type' => 'record', 'destination' => 'date_added'),
						array('source' => 'post_date', 'type' => 'record', 'destination' => 'date_updated')
					),

					'limit' => 25
				),

				'table_metadata'	=> array(

					'select' 	=> 'meta_key,meta_value',
					'from' 		=> '#table_prefixpostmeta',
					'join' 		=> '',
					'where' 	=> 'post_id=#record_id',

					'meta_key_mask' => '_vfb_field-#id',
				)
			)
		);

		return $migrate;
	}