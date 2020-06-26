<?php

	add_filter('wsf_config_migrate', 'wsf_migrate_wpforms');

	function wsf_migrate_wpforms($migrate) {

		$migrate['wpforms'] = array(

			// Name of the plugin to migrate
			'label' => __('WPForms', 'ws-form'),

			// Version
			'version' => '1.5.x+',

			// Paths to detect this plugin
			'detect' => array('wpforms-lite/wpforms.php', 'wpforms/wpforms.php'),

			// Tables to detect this plugins data
			'detect_table' => array('wpforms_entries'),

			// Lookups
			'plugin_variables' => array(

				'{admin_email}' => '#blog_admin_email',
				'{all_fields}' 	=> '#email_submission',
				'{entry_id}' 	=> '#submit_id',
				'{form_id}' 	=> '#form_id',
				'{form_title}' 	=> '#form_label',
				'{page_title}' 	=> '#post_title',
				'{page_url}' 	=> '#post_url',
				'{page_id}' 	=> '#page_id',
				'{url_referer}' => '#tracking_referrer',

				'{user_id}' 			=> '#user_id',
				'{user_ip}' 			=> '#tracking_remote_ip',
				'{entry_geolocation}' 	=> '#tracking_geo_location',

				'{author_id}' 		=> '#author_id',
				'{author_display}' 	=> '#author_display',
				'{author_email}' 	=> '#author_email',

				'{user_display}' 	=> '#user_display_name',
				'{user_full_name}' 	=> '#user_first_name #user_last_name',
				'{user_first_name}' => '#user_first_name',
				'{user_last_name}' 	=> '#user_last_name',
				'{user_email}' 		=> '#user_email',
				'{user_display}' 	=> '#user_display_name',

				'{url_login}' 			=> '#url_login',
				'{url_logout}' 			=> '#url_logout',
				'{url_register}' 		=> '#url_register',
				'{url_lost_password}' 	=> '#url_lost_password',
			),

			// Forms
			'form' => array(

				// Form table configuration
				'table_record'		=> array(

					// SQL parts
					'count'			=> 'ID',							// Field to use if counting records
					'select'		=> 'ID,post_title,post_content,post_status',
					'from'			=> '#table_prefixposts',
					'join'			=> '',
					'where'			=> "post_type='wpforms'",
					'where_single'	=> ' AND ID=#form_id',
					'order_by'		=> 'post_title',

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

						array('source' => 'ID', 'type' => 'record', 'destination' => 'id'),
						array('source' => 'post_status', 'type' => 'record', 'destination' => 'status', 'default' => 'draft'),

						array('value_option' => 'wpforms_settings', 'type' => 'array', 'map' => array(

							array('source' => 'recaptcha-type', 'type' => 'scratch_global', 'destination' => 'recaptcha_recaptcha_type'),
							array('source' => 'recaptcha-site-key', 'type' => 'scratch_global', 'destination' => 'recaptcha_site_key'),
							array('source' => 'recaptcha-secret-key', 'type' => 'scratch_global', 'destination' => 'recaptcha_secret_key'),
							array('type' => 'scratch_global', 'destination' => 'recaptcha_enabled', 'value' => 'on', 'condition' => array('#recaptcha_recaptcha_type' => ''), 'condition_logic' => '!==')
						)),

						array('source' => 'post_content', 'type' => 'json', 'map' => array(

							// Settings
							array('source' => 'settings', 'type' => 'array', 'map' => array(

								array('source' => 'form_title', 'type' => 'record', 'destination' => 'label', WS_FORM_DEFAULT_FORM_NAME),
								array('source' => 'form_class', 'type' => 'meta', 'destination' => 'class_form_wrapper'),
								array('source' => 'submit_text', 'type' => 'scratch_global', 'destination' => 'submit_text'),
								array('source' => 'submit_class', 'type' => 'scratch_global', 'destination' => 'submit_class'),
								array('source' => 'honeypot', 'type' => 'meta', 'destination' => 'honeypot', 'lookup' => array(

									array('find' => '', 'replace' => ''),
									array('find' => '1', 'replace' => 'on')
								)),
							)),

							// Email Blog Action
							array('source' => 'notifications', 'type' => 'json', 'map' => array(

								array('type' => 'foreach_action', 'map' => array(

									array('source' => 'notification_name', 'type' => 'scratch', 'destination' => 'email_blog_label', 'condition' => array('#meta_value' => 'Default Notification'), 'action' => 'email_blog'),
									array('source' => 'email', 'type' => 'scratch', 'destination' => 'email_blog_to_email'),
									array('source' => 'subject', 'type' => 'scratch', 'destination' => 'email_blog_subject'),
									array('source' => 'sender_name', 'type' => 'scratch', 'destination' => 'email_blog_from_name'),
									array('source' => 'sender_address', 'type' => 'scratch', 'destination' => 'email_blog_from_email'),
									array('source' => 'message', 'type' => 'scratch', 'destination' => 'email_blog_message'),
									array('source' => 'replyto', 'type' => 'scratch', 'destination' => 'email_blog_reply_to_email'),
								))
							)),

							// Confirmations
							array('source' => 'confirmation_type', 'type' => 'scratch', 'destination' => 'confirmation_message', 'condition' => array('#meta_value' => 'message'), 'action' => 'message'),
							array('source' => 'confirmation_type', 'type' => 'scratch', 'destination' => 'confirmation_message', 'condition' => array('#meta_value' => 'redirect'), 'action' => 'redirect_url'),
							array('source' => 'confirmation_type', 'type' => 'scratch', 'destination' => 'confirmation_message', 'condition' => array('#meta_value' => 'page'), 'action' => 'redirect_wp_page'),

							array('source' => 'confirmation_message', 'type' => 'scratch', 'destination' => 'confirmation_message'),
							array('source' => 'confirmation_message_scroll', 'type' => 'scratch', 'destination' => 'confirmation_message_scroll', 'lookup' => array(

									array('find' => '', 'replace' => ''),
									array('find' => '1', 'replace' => 'on')
							)),
							array('source' => 'confirmation_page', 'type' => 'scratch', 'destination' => 'confirmation_page'),
							array('source' => 'confirmation_redirect', 'type' => 'scratch', 'destination' => 'confirmation_redirect'),
						))
					)
				),

				'action'	=> array(

					// Notification
					'email_blog' => array(

						'action_id'	=>	'email',
						'meta'		=>	array(

							'action_from_email' => '#email_blog_from_email',
							'action_from_name' => '#email_blog_from_name',
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

					// Save to database
					'database' => array(

						'action_id'	=>	'database',
						'force'		=>	true
					),

					// Message
					'message' => array(

						'action_id'	=>	'message',
						'meta'		=>	array(

							'action_message_message' => '#confirmation_message',
							'action_message_scroll_top' => '#confirmation_message_scroll'
						)
					),

					// Redirect
					'redirect_url' => array(

						'action_id'	=>	'redirect',
						'meta'		=>	array(

							'action_redirect_url' => '#confirmation_redirect'
						)
					),

					// WP Page
					'redirect_wp_page' => array(

						'action_id'	=>	'redirect',
						'meta'		=>	array(

							'action_redirect_url' => '/?p=#confirmation_page'
						)
					),
				)
			),

			'group' => array(

				'table_record'		=> array(

					// SQL parts
					'count'			=> 'ID',							// Field to use if counting records
					'select'		=> 'ID,post_title,post_content,post_status',
					'from'			=> '#table_prefixposts',
					'join'			=> '',
					'where'			=> "post_type='wpforms'",
					'where_single'	=> ' AND ID=#form_id',
					'order_by'		=> 'post_title',

					'sql_transpose'	=> array(

						'data_field'		=> 'post_content',
						'data_format'		=> 'json',
						'data_sub_field'	=> 'fields'
					),

					'map' => array(

						array('source' => 'type', 'type' => 'scratch', 'destination' => 'type_source')
					),

					'map_by_type' => array(

						'pagebreak'	=>	array(

							array('type' => 'record', 'destination' => 'id', 'value' => true),
							array('source' => 'title', 'type' => 'record', 'destination' => 'label'),
							array('source' => 'css', 'type' => 'meta', 'destination' => 'class_group_wrapper'),
							array('source' => 'position', 'type' => 'scratch', 'destination' => 'position'),
							array('group' => true, 'condition' => array('#position' => ''))
						)
					)
				)
			),

			'section' => array(

				'table_record'		=> array(

					// SQL parts
					'count'			=> 'ID',							// Field to use if counting records
					'select'		=> 'ID,post_title,post_content,post_status',
					'from'			=> '#table_prefixposts',
					'join'			=> '',
					'where'			=> "post_type='wpforms'",
					'where_single'	=> ' AND ID=#form_id',
					'order_by'		=> 'post_title',

					'sql_transpose'	=> array(

						'data_field'		=> 'post_content',
						'data_format'		=> 'json',
						'data_sub_field'	=> 'fields'
					),

					'map' => array(

						array('source' => 'type', 'type' => 'scratch', 'destination' => 'type_source')
					),

					'map_by_type' => array(

						'pagebreak'	=>	array(

							array('source' => 'position', 'type' => 'scratch', 'destination' => 'position'),
							array('group' => true, 'condition' => array('#position' => ''))
						),

						'divider'	=>	array(

							array('source' => 'label', 'type' => 'record', 'destination' => 'label', 'default' => WS_FORM_DEFAULT_SECTION_NAME),
							array('source' => 'label_disable', 'type' => 'meta', 'destination' => 'label_render', 'lookup' => array(

									array('find' => '', 'replace' => 'on'),
									array('find' => '1', 'replace' => '')
							)),
							array('source' => 'css', 'type' => 'meta', 'destination' => 'class_section_wrapper'),
							array('section' => true),
						)
					)
				)
			),

			'field' => array(

				'table_record'		=> array(

					// SQL parts
					'count'			=> 'ID',							// Field to use if counting records
					'select'		=> 'ID,post_title,post_content,post_status',
					'from'			=> '#table_prefixposts',
					'join'			=> '',
					'where'			=> "post_type='wpforms'",
					'where_single'	=> ' AND ID=#form_id',
					'order_by'		=> 'post_title',

					'sql_transpose'	=> array(

						'data_field'		=> 'post_content',
						'data_format'		=> 'json',
						'data_sub_field'	=> 'fields'
					),

					'map'		=> array(

						array('source' => 'id', 'type' => 'record', 'destination' => 'id'),
						array('source' => 'parent_id', 'type' => 'meta', 'destination' => 'parent_id'),	// Used for e-commerce field mapping
						array('source' => 'type', 'type' => 'scratch', 'destination' => 'type_source'),
						array('source' => 'type', 'type' => 'record', 'destination' => 'type', 'lookup' => array(

							array('find' => 'text', 'replace' => 'text'),
							array('find' => 'textarea', 'replace' => 'textarea'),
							array('find' => 'select', 'replace' => 'select'),
							array('find' => 'radio', 'replace' => 'radio'),
							array('find' => 'checkbox', 'replace' => 'checkbox'),
							array('find' => 'number', 'replace' => 'number'),
							array('find' => 'name', 'replace' => false),
							array('find' => 'email', 'replace' => 'email'),

							array('find' => 'website', 'replace' => 'url'),
							array('find' => 'address', 'replace' => false),
							array('find' => 'password', 'replace' => 'password'),
							array('find' => 'phone', 'replace' => 'tel'),
							array('find' => 'date-time', 'replace' => 'datetime'),
							array('find' => 'hidden', 'replace' => 'hidden'),
							array('find' => 'html', 'replace' => 'html'),
							array('find' => 'file-upload', 'replace' => 'file'),
							array('find' => 'pagebreak', 'replace' => false),
							array('find' => 'divider', 'replace' => false),
							array('find' => 'rating', 'replace' => 'rating'),
							array('find' => 'signature', 'replace' => 'signature'),
							array('find' => 'captcha', 'replace' => false),

							array('find' => 'payment-single', 'replace' => 'price'),
							array('find' => 'payment-multiple', 'replace' => 'price_radio'),
							array('find' => 'payment-checkbox', 'replace' => 'price_checkbox'),
							array('find' => 'payment-select', 'replace' => 'price_select'),
							array('find' => 'payment-total', 'replace' => 'cart_total'),
						)),

						array('source' => 'label', 'type' => 'record', 'destination' => 'label', 'default' => '#field_label_default'),
						array('source' => 'label_hide', 'type' => 'meta', 'destination' => 'label_render', 'lookup' => array(

							array('find' => '', 'replace' => 'on'),
							array('find' => '1', 'replace' => '')
						), 'default' => 'on'),

						array('source' => 'required', 'type' => 'meta', 'destination' => 'required', 'lookup' => array(

							array('find' => '', 'replace' => ''),
							array('find' => '1', 'replace' => 'on')
						)),
						array('source' => 'address_field_hidden', 'type' => 'meta', 'destination' => 'hidden', 'default' => ''),
						array('source' => 'sublabel_hide', 'type' => 'meta', 'destination' => 'render_label', 'default' => 'on'),
						array('source' => 'input_mask', 'type' => 'meta', 'destination' => 'input_mask'),
						array('source' => 'placeholder', 'type' => 'meta', 'destination' => 'placeholder'),
						array('source' => 'description', 'type' => 'meta', 'destination' => 'help'),
						array('source' => 'css', 'type' => 'meta', 'destination' => 'class_field_wrapper'),
						array('source' => 'default_value', 'type' => 'meta', 'destination' => 'default_value'),
						array('source' => 'breakpoint_size_25', 'type' => 'meta', 'destination' => 'breakpoint_size_25'),
						array('source' => 'ecommerce_field_id', 'type' => 'meta', 'destination' => 'ecommerce_field_id'),
						array('source' => 'submission_id_source', 'type' => 'scratch', 'destination' => 'submission_id_source'),
						array('source' => 'submission_sub_meta_key', 'type' => 'scratch', 'destination' => 'submission_sub_meta_key')
					),

					'map_by_type' => array(

						'pagebreak'	=>	array(

							array('source' => 'position', 'type' => 'scratch', 'destination' => 'position'),
							array('group' => true, 'condition' => array('#position' => ''))
						),

						'divider'	=>	array(

							array('section' => true)
						),

						'date-time'	=>	array(

							array('source' => 'format', 'type' => 'meta', 'destination' => 'input_type_datetime', 'lookup' => array(

								array('find' => 'date-time', 'replace' => 'datetime-local'),
								array('find' => 'date', 'replace' => 'date'),
								array('find' => 'time', 'replace' => 'time')
							)),

							array('source' => 'date_placeholder', 'type' => 'meta', 'destination' => 'placeholder')
						),

						'phone' =>	array(

							array('source' => 'format', 'type' => 'meta', 'destination' => 'input_mask', 'lookup' => array(

									array('find' => 'us', 'replace' => '(999) 999-9999'),
									array('find' => 'international', 'replace' => '')
							))
						),

						'address'	=>	array(

							array('source' => 'scheme', 'type' => 'scratch', 'destination' => 'scheme'),
							array('source' => 'sublabel_hide', 'type' => 'scratch', 'destination' => 'address_field_hidden', 'lookup' => array(

								array('find' => '', 'replace' => 'on'),
								array('find' => '1', 'replace' => '')
							)),

							array('source' => 'address1_placeholder', 'type' => 'scratch', 'destination' => 'address1_placeholder'),
							array('source' => 'address1_default', 'type' => 'scratch', 'destination' => 'address1_default'),
							array('source' => 'address2_placeholder', 'type' => 'scratch', 'destination' => 'address2_placeholder'),
							array('source' => 'address2_default', 'type' => 'scratch', 'destination' => 'address2_default'),
							array('source' => 'address2_hide', 'type' => 'scratch', 'destination' => 'address2_hide'),							
							array('source' => 'city_placeholder', 'type' => 'scratch', 'destination' => 'city_placeholder'),
							array('source' => 'city_default', 'type' => 'scratch', 'destination' => 'city_default'),
							array('source' => 'state_placeholder', 'type' => 'scratch', 'destination' => 'state_placeholder'),
							array('source' => 'state_default', 'type' => 'scratch', 'destination' => 'state_default'),
							array('source' => 'postal_placeholder', 'type' => 'scratch', 'destination' => 'postal_placeholder'),
							array('source' => 'postal_default', 'type' => 'scratch', 'destination' => 'postal_default'),
							array('source' => 'postal_hide', 'type' => 'scratch', 'destination' => 'postal_hide'),							
							array('source' => 'country_placeholder', 'type' => 'scratch', 'destination' => 'country_placeholder'),
							array('source' => 'country_default', 'type' => 'scratch', 'destination' => 'country_default'),
							array('source' => 'country_hide', 'type' => 'scratch', 'destination' => 'country_hide'),

							array('type' => 'scratch', 'destination' => 'country_show_1', 'value' => '1', 'condition' => array('#country_hide' => false)),
							array('type' => 'scratch', 'destination' => 'country_show_2', 'value' => '1', 'condition' => array('#scheme' => 'international')),
							array('type' => 'scratch', 'destination' => 'country_show', 'value' => 'show', 'condition' => array('#country_hide#scheme' => '11')),

							array('type' => 'records', 'merge' => array(

								// Address Line 1
								array('merge' => array('label' => __('Address Line 1', 'ws-form'), 'type' => 'text', 'placeholder' => '#address1_placeholder', 'default_value' => '#address1_default', 'address_field_hidden' => '#address_field_hidden', 'submission_id_source' => '#id', 'submission_sub_meta_key' => 'address1')),

								// Address Line 2
								array('merge' => array('label' => __('Address Line 2', 'ws-form'), 'type' => 'text', 'placeholder' => '#address2_placeholder', 'default_value' => '#address2_default', 'address_field_hidden' => '#address_field_hidden', 'submission_id_source' => '#id', 'submission_sub_meta_key' => 'address2'), 'condition' => array('#address2_hide' => false)),

								// City
								array('merge' => array('label' => __('City', 'ws-form'), 'type' => 'text', 'placeholder' => '#city_placeholder', 'default_value' => '#city_default', 'address_field_hidden' => '#address_field_hidden', 'submission_id_source' => '#id', 'submission_sub_meta_key' => 'city'), 'size_percent' => 50),

								// State
								array('merge' => array('label' => __('State', 'ws-form'), 'type' => 'select', 'choices' => array(

									array('value' => 'AL', 'text' => 'Alabama'),
									array('value' => 'AK', 'text' => 'Alaska'),
									array('value' => 'AZ', 'text' => 'Arizona'),
									array('value' => 'AR', 'text' => 'Arkansas'),
									array('value' => 'CA', 'text' => 'California'),
									array('value' => 'CO', 'text' => 'Colorado'),
									array('value' => 'CT', 'text' => 'Connecticut'),
									array('value' => 'DE', 'text' => 'Delaware'),
									array('value' => 'DC', 'text' => 'District of Columbia'),
									array('value' => 'FL', 'text' => 'Florida'),
									array('value' => 'GA', 'text' => 'Georgia'),
									array('value' => 'HI', 'text' => 'Hawaii'),
									array('value' => 'ID', 'text' => 'Idaho'),
									array('value' => 'IL', 'text' => 'Illinois'),
									array('value' => 'IN', 'text' => 'Indiana'),
									array('value' => 'IA', 'text' => 'Iowa'),
									array('value' => 'KS', 'text' => 'Kansas'),
									array('value' => 'KY', 'text' => 'Kentucky'),
									array('value' => 'LA', 'text' => 'Louisiana'),
									array('value' => 'ME', 'text' => 'Maine'),
									array('value' => 'MD', 'text' => 'Maryland'),
									array('value' => 'MA', 'text' => 'Massachusetts'),
									array('value' => 'MI', 'text' => 'Michigan'),
									array('value' => 'MN', 'text' => 'Minnesota'),
									array('value' => 'MS', 'text' => 'Mississippi'),
									array('value' => 'MO', 'text' => 'Missouri'),
									array('value' => 'MT', 'text' => 'Montana'),
									array('value' => 'NE', 'text' => 'Nebraska'),
									array('value' => 'NV', 'text' => 'Nevada'),
									array('value' => 'NH', 'text' => 'New Hampshire'),
									array('value' => 'NJ', 'text' => 'New Jersey'),
									array('value' => 'NM', 'text' => 'New Mexico'),
									array('value' => 'NY', 'text' => 'New York'),
									array('value' => 'NC', 'text' => 'North Carolina'),
									array('value' => 'ND', 'text' => 'North Dakota'),
									array('value' => 'OH', 'text' => 'Ohio'),
									array('value' => 'OK', 'text' => 'Oklahoma'),
									array('value' => 'OR', 'text' => 'Oregon'),
									array('value' => 'PA', 'text' => 'Pennsylvania'),
									array('value' => 'RI', 'text' => 'Rhode Island'),
									array('value' => 'SC', 'text' => 'South Carolina'),
									array('value' => 'SD', 'text' => 'South Dakota'),
									array('value' => 'TN', 'text' => 'Tennessee'),
									array('value' => 'TX', 'text' => 'Texas'),
									array('value' => 'UT', 'text' => 'Utah'),
									array('value' => 'VT', 'text' => 'Vermont'),
									array('value' => 'VA', 'text' => 'Virginia'),
									array('value' => 'WA', 'text' => 'Washington'),
									array('value' => 'WV', 'text' => 'West Virginia'),
									array('value' => 'WI', 'text' => 'Wisconsin'),
									array('value' => 'WY', 'text' => 'Wyoming')
								), 'placeholder' => '#state_placeholder', 'default_value' => '#state_default', 'address_field_hidden' => '#address_field_hidden', 'submission_id_source' => '#id', 'submission_sub_meta_key' => 'state'), 'size_percent' => 50, 'condition' => array('#scheme' => 'us')),

								// State / Province / Region
								array('merge' => array('label' => __('State / Province / Region', 'ws-form'), 'type' => 'text', 'placeholder' => '#state_placeholder', 'default_value' => '#state_default', 'address_field_hidden' => '#address_field_hidden', 'submission_id_source' => '#id', 'submission_sub_meta_key' => 'state'), 'size_percent' => 50, 'condition' => array('#scheme' => 'international')),

								// Zip
								array('merge' => array('label' => __('Zip', 'ws-form'), 'type' => 'text', 'placeholder' => '#postal_placeholder', 'default_value' => '#postal_default', 'address_field_hidden' => '#address_field_hidden', 'submission_id_source' => '#id', 'submission_sub_meta_key' => 'postal'), 'size_percent' => 50, 'condition' => array('#postal_hide' => false)),

								// Country
								array('merge' => array('type' => 'select', 'choices' => array(

									array('value' => 'Afghanistan', 'text' => 'Afghanistan'),
									array('value' => 'Åland Islands', 'text' => 'Åland Islands'),
									array('value' => 'Albania', 'text' => 'Albania'),
									array('value' => 'Algeria', 'text' => 'Algeria'),
									array('value' => 'American Samoa', 'text' => 'American Samoa'),
									array('value' => 'Andorra', 'text' => 'Andorra'),
									array('value' => 'Angola', 'text' => 'Angola'),
									array('value' => 'Anguilla', 'text' => 'Anguilla'),
									array('value' => 'Antarctica', 'text' => 'Antarctica'),
									array('value' => 'Antigua and Barbuda', 'text' => 'Antigua and Barbuda'),
									array('value' => 'Argentina', 'text' => 'Argentina'),
									array('value' => 'Armenia', 'text' => 'Armenia'),
									array('value' => 'Aruba', 'text' => 'Aruba'),
									array('value' => 'Australia', 'text' => 'Australia'),
									array('value' => 'Austria', 'text' => 'Austria'),
									array('value' => 'Azerbaijan', 'text' => 'Azerbaijan'),
									array('value' => 'Bahamas', 'text' => 'Bahamas'),
									array('value' => 'Bahrain', 'text' => 'Bahrain'),
									array('value' => 'Bangladesh', 'text' => 'Bangladesh'),
									array('value' => 'Barbados', 'text' => 'Barbados'),
									array('value' => 'Belarus', 'text' => 'Belarus'),
									array('value' => 'Belgium', 'text' => 'Belgium'),
									array('value' => 'Belize', 'text' => 'Belize'),
									array('value' => 'Benin', 'text' => 'Benin'),
									array('value' => 'Bermuda', 'text' => 'Bermuda'),
									array('value' => 'Bhutan', 'text' => 'Bhutan'),
									array('value' => 'Bolivia', 'text' => 'Bolivia'),
									array('value' => 'Bonaire, Sint Eustatius and Saba', 'text' => 'Bonaire, Sint Eustatius and Saba'),
									array('value' => 'Bosnia and Herzegovina', 'text' => 'Bosnia and Herzegovina'),
									array('value' => 'Botswana', 'text' => 'Botswana'),
									array('value' => 'Bouvet Island', 'text' => 'Bouvet Island'),
									array('value' => 'Brazil', 'text' => 'Brazil'),
									array('value' => 'British Indian Ocean Territory', 'text' => 'British Indian Ocean Territory'),
									array('value' => 'Brunei Darrussalam', 'text' => 'Brunei Darrussalam'),
									array('value' => 'Bulgaria', 'text' => 'Bulgaria'),
									array('value' => 'Burkina Faso', 'text' => 'Burkina Faso'),
									array('value' => 'Burundi', 'text' => 'Burundi'),
									array('value' => 'Cambodia', 'text' => 'Cambodia'),
									array('value' => 'Cameroon', 'text' => 'Cameroon'),
									array('value' => 'Canada', 'text' => 'Canada'),
									array('value' => 'Cape Verde', 'text' => 'Cape Verde'),
									array('value' => 'Cayman Islands', 'text' => 'Cayman Islands'),
									array('value' => 'Central African Republic', 'text' => 'Central African Republic'),
									array('value' => 'Chad', 'text' => 'Chad'),
									array('value' => 'Chile', 'text' => 'Chile'),
									array('value' => 'China', 'text' => 'China'),
									array('value' => 'Christmas Island', 'text' => 'Christmas Island'),
									array('value' => 'Cocos Islands', 'text' => 'Cocos Islands'),
									array('value' => 'Colombia', 'text' => 'Colombia'),
									array('value' => 'Comoros', 'text' => 'Comoros'),
									array('value' => 'Congo, Democratic Republic of the', 'text' => 'Congo, Democratic Republic of the'),
									array('value' => 'Congo, Republic of the', 'text' => 'Congo, Republic of the'),
									array('value' => 'Cook Islands', 'text' => 'Cook Islands'),
									array('value' => 'Costa Rica', 'text' => 'Costa Rica'),
									array('value' => 'Côte d&#039;Ivoire', 'text' => 'Côte d&#039;Ivoire'),
									array('value' => 'Croatia', 'text' => 'Croatia'),
									array('value' => 'Cuba', 'text' => 'Cuba'),
									array('value' => 'Curaçao', 'text' => 'Curaçao'),
									array('value' => 'Cyprus', 'text' => 'Cyprus'),
									array('value' => 'Czech Republic', 'text' => 'Czech Republic'),
									array('value' => 'Denmark', 'text' => 'Denmark'),
									array('value' => 'Djibouti', 'text' => 'Djibouti'),
									array('value' => 'Dominica', 'text' => 'Dominica'),
									array('value' => 'Dominican Republic', 'text' => 'Dominican Republic'),
									array('value' => 'Ecuador', 'text' => 'Ecuador'),
									array('value' => 'Egypt', 'text' => 'Egypt'),
									array('value' => 'El Salvador', 'text' => 'El Salvador'),
									array('value' => 'Equatorial Guinea', 'text' => 'Equatorial Guinea'),
									array('value' => 'Eritrea', 'text' => 'Eritrea'),
									array('value' => 'Estonia', 'text' => 'Estonia'),
									array('value' => 'Eswatini (Swaziland)', 'text' => 'Eswatini (Swaziland)'),
									array('value' => 'Ethiopia', 'text' => 'Ethiopia'),
									array('value' => 'Falkland Islands', 'text' => 'Falkland Islands'),
									array('value' => 'Faroe Islands', 'text' => 'Faroe Islands'),
									array('value' => 'Fiji', 'text' => 'Fiji'),
									array('value' => 'Finland', 'text' => 'Finland'),
									array('value' => 'France', 'text' => 'France'),
									array('value' => 'French Guiana', 'text' => 'French Guiana'),
									array('value' => 'French Polynesia', 'text' => 'French Polynesia'),
									array('value' => 'French Southern Territories', 'text' => 'French Southern Territories'),
									array('value' => 'Gabon', 'text' => 'Gabon'),
									array('value' => 'Gambia', 'text' => 'Gambia'),
									array('value' => 'Georgia', 'text' => 'Georgia'),
									array('value' => 'Germany', 'text' => 'Germany'),
									array('value' => 'Ghana', 'text' => 'Ghana'),
									array('value' => 'Gibraltar', 'text' => 'Gibraltar'),
									array('value' => 'Greece', 'text' => 'Greece'),
									array('value' => 'Greenland', 'text' => 'Greenland'),
									array('value' => 'Grenada', 'text' => 'Grenada'),
									array('value' => 'Guadeloupe', 'text' => 'Guadeloupe'),
									array('value' => 'Guam', 'text' => 'Guam'),
									array('value' => 'Guatemala', 'text' => 'Guatemala'),
									array('value' => 'Guernsey', 'text' => 'Guernsey'),
									array('value' => 'Guinea', 'text' => 'Guinea'),
									array('value' => 'Guinea-Bissau', 'text' => 'Guinea-Bissau'),
									array('value' => 'Guyana', 'text' => 'Guyana'),
									array('value' => 'Haiti', 'text' => 'Haiti'),
									array('value' => 'Heard and McDonald Islands', 'text' => 'Heard and McDonald Islands'),
									array('value' => 'Holy See', 'text' => 'Holy See'),
									array('value' => 'Honduras', 'text' => 'Honduras'),
									array('value' => 'Hong Kong', 'text' => 'Hong Kong'),
									array('value' => 'Hungary', 'text' => 'Hungary'),
									array('value' => 'Iceland', 'text' => 'Iceland'),
									array('value' => 'India', 'text' => 'India'),
									array('value' => 'Indonesia', 'text' => 'Indonesia'),
									array('value' => 'Iran', 'text' => 'Iran'),
									array('value' => 'Iraq', 'text' => 'Iraq'),
									array('value' => 'Ireland', 'text' => 'Ireland'),
									array('value' => 'Isle of Man', 'text' => 'Isle of Man'),
									array('value' => 'Israel', 'text' => 'Israel'),
									array('value' => 'Italy', 'text' => 'Italy'),
									array('value' => 'Jamaica', 'text' => 'Jamaica'),
									array('value' => 'Japan', 'text' => 'Japan'),
									array('value' => 'Jersey', 'text' => 'Jersey'),
									array('value' => 'Jordan', 'text' => 'Jordan'),
									array('value' => 'Kazakhstan', 'text' => 'Kazakhstan'),
									array('value' => 'Kenya', 'text' => 'Kenya'),
									array('value' => 'Kiribati', 'text' => 'Kiribati'),
									array('value' => 'Kuwait', 'text' => 'Kuwait'),
									array('value' => 'Kyrgyzstan', 'text' => 'Kyrgyzstan'),
									array('value' => 'Lao People&#039;s Democratic Republic', 'text' => 'Lao People&#039;s Democratic Republic'),
									array('value' => 'Latvia', 'text' => 'Latvia'),
									array('value' => 'Lebanon', 'text' => 'Lebanon'),
									array('value' => 'Lesotho', 'text' => 'Lesotho'),
									array('value' => 'Liberia', 'text' => 'Liberia'),
									array('value' => 'Libya', 'text' => 'Libya'),
									array('value' => 'Liechtenstein', 'text' => 'Liechtenstein'),
									array('value' => 'Lithuania', 'text' => 'Lithuania'),
									array('value' => 'Luxembourg', 'text' => 'Luxembourg'),
									array('value' => 'Macau', 'text' => 'Macau'),
									array('value' => 'Macedonia', 'text' => 'Macedonia'),
									array('value' => 'Madagascar', 'text' => 'Madagascar'),
									array('value' => 'Malawi', 'text' => 'Malawi'),
									array('value' => 'Malaysia', 'text' => 'Malaysia'),
									array('value' => 'Maldives', 'text' => 'Maldives'),
									array('value' => 'Mali', 'text' => 'Mali'),
									array('value' => 'Malta', 'text' => 'Malta'),
									array('value' => 'Marshall Islands', 'text' => 'Marshall Islands'),
									array('value' => 'Martinique', 'text' => 'Martinique'),
									array('value' => 'Mauritania', 'text' => 'Mauritania'),
									array('value' => 'Mauritius', 'text' => 'Mauritius'),
									array('value' => 'Mayotte', 'text' => 'Mayotte'),
									array('value' => 'Mexico', 'text' => 'Mexico'),
									array('value' => 'Micronesia', 'text' => 'Micronesia'),
									array('value' => 'Moldova', 'text' => 'Moldova'),
									array('value' => 'Monaco', 'text' => 'Monaco'),
									array('value' => 'Mongolia', 'text' => 'Mongolia'),
									array('value' => 'Montenegro', 'text' => 'Montenegro'),
									array('value' => 'Montserrat', 'text' => 'Montserrat'),
									array('value' => 'Morocco', 'text' => 'Morocco'),
									array('value' => 'Mozambique', 'text' => 'Mozambique'),
									array('value' => 'Myanmar', 'text' => 'Myanmar'),
									array('value' => 'Namibia', 'text' => 'Namibia'),
									array('value' => 'Nauru', 'text' => 'Nauru'),
									array('value' => 'Nepal', 'text' => 'Nepal'),
									array('value' => 'Netherlands', 'text' => 'Netherlands'),
									array('value' => 'New Caledonia', 'text' => 'New Caledonia'),
									array('value' => 'New Zealand', 'text' => 'New Zealand'),
									array('value' => 'Nicaragua', 'text' => 'Nicaragua'),
									array('value' => 'Niger', 'text' => 'Niger'),
									array('value' => 'Nigeria', 'text' => 'Nigeria'),
									array('value' => 'Niue', 'text' => 'Niue'),
									array('value' => 'Norfolk Island', 'text' => 'Norfolk Island'),
									array('value' => 'North Korea', 'text' => 'North Korea'),
									array('value' => 'Northern Mariana Islands', 'text' => 'Northern Mariana Islands'),
									array('value' => 'Norway', 'text' => 'Norway'),
									array('value' => 'Oman', 'text' => 'Oman'),
									array('value' => 'Pakistan', 'text' => 'Pakistan'),
									array('value' => 'Palau', 'text' => 'Palau'),
									array('value' => 'Palestine, State of', 'text' => 'Palestine, State of'),
									array('value' => 'Panama', 'text' => 'Panama'),
									array('value' => 'Papua New Guinea', 'text' => 'Papua New Guinea'),
									array('value' => 'Paraguay', 'text' => 'Paraguay'),
									array('value' => 'Peru', 'text' => 'Peru'),
									array('value' => 'Philippines', 'text' => 'Philippines'),
									array('value' => 'Pitcairn', 'text' => 'Pitcairn'),
									array('value' => 'Poland', 'text' => 'Poland'),
									array('value' => 'Portugal', 'text' => 'Portugal'),
									array('value' => 'Puerto Rico', 'text' => 'Puerto Rico'),
									array('value' => 'Qatar', 'text' => 'Qatar'),
									array('value' => 'Réunion', 'text' => 'Réunion'),
									array('value' => 'Romania', 'text' => 'Romania'),
									array('value' => 'Russia', 'text' => 'Russia'),
									array('value' => 'Rwanda', 'text' => 'Rwanda'),
									array('value' => 'Saint Barthélemy', 'text' => 'Saint Barthélemy'),
									array('value' => 'Saint Helena', 'text' => 'Saint Helena'),
									array('value' => 'Saint Kitts and Nevis', 'text' => 'Saint Kitts and Nevis'),
									array('value' => 'Saint Lucia', 'text' => 'Saint Lucia'),
									array('value' => 'Saint Martin', 'text' => 'Saint Martin'),
									array('value' => 'Saint Pierre and Miquelon', 'text' => 'Saint Pierre and Miquelon'),
									array('value' => 'Saint Vincent and the Grenadines', 'text' => 'Saint Vincent and the Grenadines'),
									array('value' => 'Samoa', 'text' => 'Samoa'),
									array('value' => 'San Marino', 'text' => 'San Marino'),
									array('value' => 'Sao Tome and Principe', 'text' => 'Sao Tome and Principe'),
									array('value' => 'Saudi Arabia', 'text' => 'Saudi Arabia'),
									array('value' => 'Senegal', 'text' => 'Senegal'),
									array('value' => 'Serbia', 'text' => 'Serbia'),
									array('value' => 'Seychelles', 'text' => 'Seychelles'),
									array('value' => 'Sierra Leone', 'text' => 'Sierra Leone'),
									array('value' => 'Singapore', 'text' => 'Singapore'),
									array('value' => 'Sint Maarten', 'text' => 'Sint Maarten'),
									array('value' => 'Slovakia', 'text' => 'Slovakia'),
									array('value' => 'Slovenia', 'text' => 'Slovenia'),
									array('value' => 'Solomon Islands', 'text' => 'Solomon Islands'),
									array('value' => 'Somalia', 'text' => 'Somalia'),
									array('value' => 'South Africa', 'text' => 'South Africa'),
									array('value' => 'South Georgia', 'text' => 'South Georgia'),
									array('value' => 'South Korea', 'text' => 'South Korea'),
									array('value' => 'South Sudan', 'text' => 'South Sudan'),
									array('value' => 'Spain', 'text' => 'Spain'),
									array('value' => 'Sri Lanka', 'text' => 'Sri Lanka'),
									array('value' => 'Sudan', 'text' => 'Sudan'),
									array('value' => 'Suriname', 'text' => 'Suriname'),
									array('value' => 'Svalbard and Jan Mayen Islands', 'text' => 'Svalbard and Jan Mayen Islands'),
									array('value' => 'Sweden', 'text' => 'Sweden'),
									array('value' => 'Switzerland', 'text' => 'Switzerland'),
									array('value' => 'Syria', 'text' => 'Syria'),
									array('value' => 'Taiwan', 'text' => 'Taiwan'),
									array('value' => 'Tajikistan', 'text' => 'Tajikistan'),
									array('value' => 'Tanzania', 'text' => 'Tanzania'),
									array('value' => 'Thailand', 'text' => 'Thailand'),
									array('value' => 'Timor-Leste', 'text' => 'Timor-Leste'),
									array('value' => 'Togo', 'text' => 'Togo'),
									array('value' => 'Tokelau', 'text' => 'Tokelau'),
									array('value' => 'Tonga', 'text' => 'Tonga'),
									array('value' => 'Trinidad and Tobago', 'text' => 'Trinidad and Tobago'),
									array('value' => 'Tunisia', 'text' => 'Tunisia'),
									array('value' => 'Turkey', 'text' => 'Turkey'),
									array('value' => 'Turkmenistan', 'text' => 'Turkmenistan'),
									array('value' => 'Turks and Caicos Islands', 'text' => 'Turks and Caicos Islands'),
									array('value' => 'Tuvalu', 'text' => 'Tuvalu'),
									array('value' => 'Uganda', 'text' => 'Uganda'),
									array('value' => 'Ukraine', 'text' => 'Ukraine'),
									array('value' => 'United Arab Emirates', 'text' => 'United Arab Emirates'),
									array('value' => 'United Kingdom', 'text' => 'United Kingdom'),
									array('value' => 'United States', 'text' => 'United States'),
									array('value' => 'Uruguay', 'text' => 'Uruguay'),
									array('value' => 'US Minor Outlying Islands', 'text' => 'US Minor Outlying Islands'),
									array('value' => 'Uzbekistan', 'text' => 'Uzbekistan'),
									array('value' => 'Vanuatu', 'text' => 'Vanuatu'),
									array('value' => 'Venezuela', 'text' => 'Venezuela'),
									array('value' => 'Vietnam', 'text' => 'Vietnam'),
									array('value' => 'Virgin Islands, British', 'text' => 'Virgin Islands, British'),
									array('value' => 'Virgin Islands, U.S.', 'text' => 'Virgin Islands, U.S.'),
									array('value' => 'Wallis and Futuna', 'text' => 'Wallis and Futuna'),
									array('value' => 'Western Sahara', 'text' => 'Western Sahara'),
									array('value' => 'Yemen', 'text' => 'Yemen'),
									array('value' => 'Zambia', 'text' => 'Zambia'),
									array('value' => 'Zimbabwe', 'text' => 'Zimbabwe')

								), 'placeholder' => '#country_placeholder', 'default_value' => '#country_default', 'address_field_hidden' => '#address_field_hidden', 'submission_id_source' => '#id', 'submission_sub_meta_key' => 'postal'), 'size_percent' => 50, 'condition' => array('#country_show' => 'show'))
							))
						),

						'file-upload'	=>	array(

							array('source' => 'extensions', 'type' => 'meta', 'destination' => 'accept', 'lookup_csv_join' => ',', 'lookup_csv' => array(

								'gif' => 'image/gif',
								'png' => 'image/png',
								'jpg' => 'image/jpeg',
								'html' => 'text/html',
								'dot' => 'application/msword',
								'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
								'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
								'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
								'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
								'xls' => 'application/vnd.ms-excel',
								'xlt' => 'application/vnd.ms-excel',
								'xla' => 'application/vnd.ms-excel',
								'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
								'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
								'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
								'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
								'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
								'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
								'ppt' => 'application/vnd.ms-powerpoint',
								'pot' => 'application/vnd.ms-powerpoint',
								'pps' => 'application/vnd.ms-powerpoint',
								'ppa' => 'application/vnd.ms-powerpoint',
								'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
								'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
								'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
								'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
								'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
								'potm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
								'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
								'txt' => 'text/plain',
								'ogv' => 'video/og',
								'mp4' => 'video/mp4',
								'mpg' => 'video/mpg',
								'webm' => 'video/webm',
								'mpg' => 'video/mpg',
								'mov' => 'video/mov',
								'3gp' => 'video/3gp',
								'ogg' => 'audio/og',
								'mp3' => 'audio/mp3',
								'mpg' => 'audio/mpg',
								'pdf' => 'application/pdf'
							))
						),


						'name'	=>	array(

							array('source' => 'format', 'type' => 'scratch', 'destination' => 'format'),
							array('source' => 'sublabel_hide', 'type' => 'scratch', 'destination' => 'sublabel_hide', 'lookup' => array(

								array('find' => '', 'replace' => 'on'),
								array('find' => '1', 'replace' => '')
							)),

							array('source' => 'simple_placeholder', 'type' => 'scratch', 'destination' => 'simple_placeholder'),
							array('source' => 'simple_default', 'type' => 'scratch', 'destination' => 'simple_default'),

							array('source' => 'first_placeholder', 'type' => 'scratch', 'destination' => 'first_placeholder'),
							array('source' => 'first_default', 'type' => 'scratch', 'destination' => 'first_default'),
							array('source' => 'middle_placeholder', 'type' => 'scratch', 'destination' => 'middle_placeholder'),							
							array('source' => 'middle_default', 'type' => 'scratch', 'destination' => 'middle_default'),
							array('source' => 'last_placeholder', 'type' => 'scratch', 'destination' => 'last_placeholder'),
							array('source' => 'last_default', 'type' => 'scratch', 'destination' => 'last_default'),

							array('type' => 'records', 'auto_size' => true, 'merge' => array(

								// First Name
								array('merge' => array('label' => __('First Name', 'ws-form'), 'type' => 'text', 'placeholder' => '#first_placeholder', 'default_value' => '#first_default', 'sublabel_hide' => '#sublabel_hide', 'submission_id_source' => '#id', 'submission_sub_meta_key' => 'first')),

								// Middle Name
								array('merge' => array('label' => __('Middle Name', 'ws-form'), 'type' => 'text', 'placeholder' => '#middle_placeholder', 'default_value' => '#middle_default', 'sublabel_hide' => '#sublabel_hide', 'submission_id_source' => '#id', 'submission_sub_meta_key' => 'middle'), 'condition' => array('#format' => 'first-middle-last')),

								// Last Name
								array('merge' => array('label' => __('Last Name', 'ws-form'), 'type' => 'text', 'placeholder' => '#last_placeholder', 'default_value' => '#last_default', 'sublabel_hide' => '#sublabel_hide', 'submission_id_source' => '#id', 'submission_sub_meta_key' => 'last')),

							), 'condition' => array('#format' => 'simple'), 'condition_logic' => '!=='),

							array('type' => 'records', 'merge' => array(

								// First Name
								array('merge' => array('label' => '#label', 'type' => 'text', 'placeholder' => '#simple_placeholder', 'default_value' => '#simple_default', 'sublabel_hide' => '#sublabel_hide', 'submission_id_source' => '#id')),

							), 'condition' => array('#format' => 'simple'))
						),

						'checkbox'	=>	array(

							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_checkbox', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'isSelected', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => '', 'replace' => ''),
									array('find' => '1', 'replace' => 'on')
								)),
								array('source' => 'value', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'text', 'type' => 'record', 'destination' => 'label'),
								array('type' => 'record', 'destination' => 'required', 'value' => '#required')
							)),
							array('source' => 'enableSelectAll', 'type' => 'meta', 'destination' => 'select_all', 'lookup' => array(

								array('find' => '', 'replace' => ''),
								array('find' => '1', 'replace' => 'on')
							))
						),

						'checkbox'	=>	array(

							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_checkbox', 'process' => array(array('process' => 'datagrid')), 'map' => array(

								array('source' => 'default', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => '', 'replace' => ''),
									array('find' => '1', 'replace' => 'on')
								)),
								array('source' => 'value', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'label', 'type' => 'record', 'destination' => 'label'),
							))
						),

						'select'	=>	array(

							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_select', 'process' => array(array('process' => 'datagrid')), 'map' => array(

								array('source' => 'default', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => '', 'replace' => ''),
									array('find' => '1', 'replace' => 'on')
								)),
								array('source' => 'value', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'label', 'type' => 'record', 'destination' => 'label')
							)),
						),

						'radio'	=>	array(

							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_radio', 'process' => array(array('process' => 'datagrid')), 'map' => array(

								array('source' => 'default', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => '', 'replace' => ''),
									array('find' => '1', 'replace' => 'on')
								)),
								array('source' => 'value', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'label', 'type' => 'record', 'destination' => 'label'),
							)),
							array('source' => 'random', 'type' => 'meta', 'destination' => 'data_grid_rows_randomize', 'lookup' => array(

								array('find' => '', 'replace' => ''),
								array('find' => '1', 'replace' => 'on')
							))
						),

						'rating'	=>	array(

							array('source' => 'icon', 'type' => 'meta', 'destination' => 'rating_icon'),
							array('source' => 'icon_size', 'type' => 'meta', 'destination' => 'rating_size', 'lookup' => array(

								array('find' => 'small', 'replace' => '18'),
								array('find' => 'medium', 'replace' => '28'),
								array('find' => 'large', 'replace' => '38')
							)),
							array('source' => 'icon_color', 'type' => 'meta', 'destination' => 'rating_color_on', 'default' => '#e27730'),
							array('source' => 'scale', 'type' => 'meta', 'destination' => 'rating_max', 'default' => '5')
						),

						'product'		=>	array(

							array('source' => 'price', 'type' => 'meta', 'destination' => 'default_value'),

							array('source' => 'format', 'type' => 'scratch', 'destination' => 'format'),

							// Single
							array('type' => 'meta', 'destination' => 'readonly', 'value' => 'on', 'condition' => array('#format' => 'single')),

							// Hidden
							array('type' => 'meta', 'destination' => 'hidden', 'value' => 'on', 'condition' => array('#format' => 'hidden')),
						),

						'payment-checkbox'	=>	array(

							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_checkbox_price', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'default', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => '', 'replace' => ''),
									array('find' => '1', 'replace' => 'on')
								)),
								array('source' => 'value', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'label', 'type' => 'record', 'destination' => 'label'),
								array('type' => 'record', 'destination' => 'required', 'value' => '#required')
							)),
							array('type' => 'meta', 'destination' => 'checkbox_price_field_value', 'value' => '0'),
							array('type' => 'meta', 'destination' => 'required', 'value' => '')
						),

						'payment-multiple'	=>	array(

							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_radio_price', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'default', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => '', 'replace' => ''),
									array('find' => '1', 'replace' => 'on')
								)),
								array('source' => 'value', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'label', 'type' => 'record', 'destination' => 'label'),
							)),
							array('type' => 'meta', 'destination' => 'radio_price_field_value', 'value' => '0')
						),

						'payment-select'	=>	array(

							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_select_price', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'default', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => '', 'replace' => ''),
									array('find' => '1', 'replace' => 'on')
								)),
								array('source' => 'value', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'label', 'type' => 'record', 'destination' => 'label'),
							)),
							array('type' => 'meta', 'destination' => 'select_price_field_value', 'value' => '0')
						)
					)
				),

				// Fields to add at the end
				'table_record_append'	=> array(

					array('type' => 'recaptcha', 'label' => __('reCAPTCHA', 'ws-form'), 'meta' => array(

						'recaptcha_recaptcha_type' => '#recaptcha_recaptcha_type',
						'recaptcha_site_key' => '#recaptcha_site_key',
						'recaptcha_secret_key' => '#recaptcha_secret_key'

					), 'condition' => array('#recaptcha_enabled' => 'on')),

					array('type' => 'submit', 'label' => '#submit_text', 'meta' => array(

						'class_field'	=>	'#submit_class'
					))
				),

				// Field type processing
				// br_to_newline 		Convert br tags to newlines
				// strip_tags 			Strip HTML tags
				// csv_to_array 		Comma separated values to array
				// upload_url_to_file 	URL to file
				'process'	=>	array(

					// WS Form field type
					'checkbox'		=>	array(array('process' => 'newline_to_array')),
					'radio'			=>	array(array('process' => 'newline_to_array')),
					'select'		=>	array(array('process' => 'newline_to_array')),
					'signature' 	=>	array(array('process' => 'upload_url_to_file')),
					'file' 			=>	array(array('process' => 'upload_url_to_file'))
				)
			),

			'submission' => array(

				'table_record'		=> array(

					'count'		=> 'entry_id',							// Field to use if counting records
					'select'	=> 'entry_id,user_id,status,viewed,starred,date,date_modified,ip_address,user_agent',
					'from' 		=> '#table_prefixwpforms_entries',
					'join'		=> '',
					'where' 	=> 'form_id=#form_id',

					'map'	=> array(

						// Mandatory mappings
						array('source' => 'entry_id', 'type' => 'record', 'destination' => 'id'),
						array('source' => 'user_id', 'type' => 'record', 'destination' => 'user_id'),
						array('source' => 'status', 'type' => 'record', 'destination' => 'status', 'lookup' => array(

							array('find' => '', 'replace' => 'publish')
						)),
						array('source' => 'viewed', 'type' => 'record', 'destination' => 'viewed'),
						array('source' => 'starred', 'type' => 'record', 'destination' => 'starred'),
						array('source' => 'date', 'type' => 'record', 'destination' => 'date_added'),
						array('source' => 'date_modified', 'type' => 'record', 'destination' => 'date_updated'),
						array('source' => 'ip_address', 'type' => 'meta', 'destination' => 'tracking_remote_ip', 'condition' => array('#meta_value' => ''), 'condition_logic' => '!=='),
						array('source' => 'user_agent', 'type' => 'meta', 'destination' => 'tracking_agent', 'condition' => array('#meta_value' => ''), 'condition_logic' => '!=='),
					),

					'limit' => 25
				),

				'table_metadata'	=> array(

					'select'	=> 'fields',
					'from' 		=> '#table_prefixwpforms_entries',
					'join'		=> '',
					'where' 	=> 'form_id=#form_id',

					'meta_key'		=> 'id',
					'meta_value'	=> 'value',

					'sql_transpose'	=> array(

						'data_field'		=> 'fields',
						'data_format'		=> 'json'
					),

					'meta_key_mask' => '#id'
				)
			)
		);

		return $migrate;
	}