<?php

	add_filter('wsf_config_migrate', 'wsf_migrate_gravity_forms');

	function wsf_migrate_gravity_forms($migrate) {

		$migrate['gravity_forms'] = array(

			// Name of the plugin to migrate
			'label' => __('Gravity Forms', 'ws-form'),

			// Version
			'version' => '3.x.x+',

			// Paths to detect this plugin
			'detect' => array('gravityforms/gravityforms.php'),

			// Tables to detect this plugins data
			'detect_table' => array('gf_form', 'gf_form_meta', 'gf_entry', 'gf_entry_meta'),

			// Lookups
			'plugin_variables' => array(

				'{form_id}' 	=> '#form_id',
				'{form_title}' 	=> '#form_label',
				'{admin_email}' => '#blog_admin_email',
				'{all_fields}' 	=> '#email_submission',
				'{save_link}' 	=> '#post_url',
				'{entry_id}' 	=> '#submit_id',
				'{entry_url}' 	=> '#submit_admin_url',
				'{date_mdy}' 	=> '#server_date',
				'{date_dmy}' 	=> '#server_date',
				'{embed_url}' 	=> '#tracking_host',
				'{ip}' 			=> '#tracking_remote_ip',
				'{user_agent}' 	=> '#tracking_agent',
				'{referer}' 	=> '#tracking_referrer'
			),

			// Forms
			'form' => array(

				// Form table configuration
				'table_record'		=> array(

					// SQL parts
					'count'			=> 'id',							// Field to use if counting records
					'select'		=> 'form_id,display_meta,confirmations,notifications',
					'from'			=> '#table_prefixgf_form_meta',
					'join'			=> '',
					'where'			=> '',
					'where_single'	=> 'form_id=#form_id',
					'order_by'		=> '',

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

						array('source' => 'form_id', 'type' => 'record', 'destination' => 'id'),
						array('source' => 'display_meta', 'type' => 'json', 'map' => array(

							array('source' => 'button', 'type' => 'array', 'map' => array(

								array('source' => 'text', 'type' => 'scratch_global', 'destination' => 'button_text', 'default' => __('Submit', 'ws-form'))
							)),

							array('source' => 'postStatus', 'type' => 'record', 'destination' => 'status', 'default' => 'draft'),
							array('source' => 'title', 'type' => 'record', 'destination' => 'label', 'default' => WS_FORM_DEFAULT_FORM_NAME),
							array('source' => 'labelPlacement', 'type' => 'meta', 'destination' => 'label_position_form', 'lookup' => array(

								array('find' => 'top_label', 'replace' => 'top'),
								array('find' => 'left_label', 'replace' => 'left'),
								array('find' => 'right_label', 'replace' => 'right')
							)),
							array('source' => 'button', 'type' => 'array', 'map' => array(

								array('source' => 'text', 'type' => 'scratch', 'destination' => 'submit_button_text')
							)),
							array('source' => 'cssClass', 'type' => 'meta', 'destination' => 'class_form_wrapper'),
							array('source' => 'enableHoneypot', 'type' => 'meta', 'destination' => 'honeypot', 'lookup' => array(

								array('find' => false, 'replace' => ''),
								array('find' => true, 'replace' => 'on')
							)),

							array('source' => 'limitEntries', 'type' => 'meta', 'destination' => 'submit_limit', 'lookup' => array(

								array('find' => false, 'replace' => ''),
								array('find' => true, 'replace' => 'on')
							)),
							array('source' => 'limitEntriesCount', 'type' => 'meta', 'destination' => 'submit_limit_count'),
							array('source' => 'limitEntriesPeriod', 'type' => 'meta', 'destination' => 'submit_limit_period'),
							array('source' => 'limitEntriesMessage', 'type' => 'meta', 'destination' => 'submit_limit_message'),

							// Scheduling
							array('source' => 'scheduleStartHour', 'type' => 'scratch', 'destination' => 'schedule_start_hour', 'process' => array(array('process' => 'prefix_zero'))),
							array('source' => 'scheduleStartMinute', 'type' => 'scratch', 'destination' => 'schedule_start_minute', 'lookup' => array(

								array('find' => 1, 'replace' => 0)

							), 'process' => array(array('process' => 'prefix_zero'))),
							array('source' => 'scheduleStartAmpm', 'type' => 'scratch', 'destination' => 'schedule_start_ampm'),
							array('source' => 'scheduleStart', 'type' => 'scratch', 'destination' => 'schedule_start_value'),
							array('type' => 'meta', 'destination' => 'schedule_start_datetime', 'value' => '#schedule_start_value #schedule_start_hour:#schedule_start_minute #schedule_start_ampm', 'process' => array(array('process' => 'datetime_to_html5')), 'condition' => array('#schedule_start_value' => ''), 'condition_logic' => '!=='),
							array('source' => 'schedulePendingMessage', 'type' => 'meta', 'destination' => 'schedule_start_message'),
							array('type' => 'meta', 'destination' => 'schedule_start', 'value' => 'on', 'condition' => array('#schedule_start_datetime' => ''), 'condition_logic' => '!=='),

							array('source' => 'scheduleEndHour', 'type' => 'scratch', 'destination' => 'schedule_end_hour', 'process' => array(array('process' => 'prefix_zero'))),
							array('source' => 'scheduleEndMinute', 'type' => 'scratch', 'destination' => 'schedule_end_minute', 'lookup' => array(

								array('find' => 1, 'replace' => 0)

							), 'process' => array(array('process' => 'prefix_zero'))),
							array('source' => 'scheduleEndAmpm', 'type' => 'scratch', 'destination' => 'schedule_end_ampm'),
							array('source' => 'scheduleEnd', 'type' => 'scratch', 'destination' => 'schedule_end_value'),
							array('type' => 'meta', 'destination' => 'schedule_end_datetime', 'value' => '#schedule_end_value #schedule_end_hour:#schedule_end_minute #schedule_end_ampm', 'process' => array(array('process' => 'datetime_to_html5')), 'condition' => array('#schedule_end_value' => ''), 'condition_logic' => '!=='),
							array('source' => 'scheduleMessage', 'type' => 'meta', 'destination' => 'schedule_end_message'),
							array('type' => 'meta', 'destination' => 'schedule_end', 'value' => 'on', 'condition' => array('#schedule_end_datetime' => ''), 'condition_logic' => '!=='),

							// Logged In
							array('source' => 'requireLogin', 'type' => 'meta', 'destination' => 'user_limit_logged_in', 'lookup' => array(

								array('find' => false, 'replace' => ''),
								array('find' => true, 'replace' => 'on')

							)),
							array('source' => 'requireLoginMessage', 'type' => 'meta', 'destination' => 'user_limit_logged_in_message')
   						)),

						// Email Blog Action
						array('source' => 'notifications', 'type' => 'json', 'map' => array(

							array('type' => 'foreach_action', 'map' => array(

								array('source' => 'name', 'type' => 'scratch', 'destination' => 'email_blog_label'),
								array('source' => 'to', 'type' => 'scratch', 'destination' => 'email_blog_to_email'),
								array('source' => 'event', 'type' => 'scratch', 'destination' => 'email_blog_event', 'condition' => array('#meta_value' => 'form_submission'), 'action' => 'email_blog'),
								array('source' => 'cc', 'type' => 'scratch', 'destination' => 'email_blog_cc_email'),
								array('source' => 'bcc', 'type' => 'scratch', 'destination' => 'email_blog_bcc_email'),
								array('source' => 'subject', 'type' => 'scratch', 'destination' => 'email_blog_subject'),
								array('source' => 'message', 'type' => 'scratch', 'destination' => 'email_blog_message'),
								array('source' => 'replyTo', 'type' => 'scratch', 'destination' => 'email_blog_reply_to_email'),
							))
						)),

						// Message
						array('source' => 'confirmations', 'type' => 'json', 'map' => array(

							array('type' => 'foreach_action', 'map' => array(

								array('source' => 'message', 'type' => 'scratch', 'destination' => 'message_message', 'action' => 'message')
							))
						)),

						// Redirect - URL
						array('source' => 'confirmations', 'type' => 'json', 'map' => array(

							array('type' => 'foreach_action', 'map' => array(

								array('source' => 'url', 'type' => 'scratch', 'destination' => 'redirect_url', 'action' => 'redirect_url'),
							))
						)),

						// Redirect - Page
						array('source' => 'confirmations', 'type' => 'json', 'map' => array(

							array('type' => 'foreach_action', 'map' => array(

								array('source' => 'pageId', 'type' => 'scratch', 'destination' => 'redirect_url', 'action' => 'wp_page')
							))
						))
					)
				),

				'action'	=> array(

					// Notification
					'email_blog' => array(

						'action_id'	=>	'email',
						'meta'		=>	array(

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

				'table_record'		=> array(

					// SQL parts
					'count'			=> 'id',							// Field to use if counting records
					'select'		=> 'display_meta',
					'from'			=> '#table_prefixgf_form_meta',
					'join'			=> '',
					'where'			=> 'form_id=#form_id',
					'order_by'		=> '',

					'sql_transpose'	=> array(

						'data_field'		=> 'display_meta',
						'data_format'		=> 'json',
						'data_sub_field'	=> 'fields'
					),

					'map' => array(

						array('source' => 'type', 'type' => 'scratch', 'destination' => 'type_source'),
					),

					'map_by_type' => array(

						'page'	=>	array(

							array('type' => 'record', 'destination' => 'id', 'value' => true),
							array('source' => 'cssClass', 'type' => 'meta', 'destination' => 'class_group_wrapper'),
							array('group' => true),
						)
					)
				),

				'data_lookups' => array(

					'label_group' => array(

						'pagination' => array(

							'pages' => '#group_id'
						)
					)
				)
			),

			'section' => array(

				'table_record'		=> array(

					// SQL parts
					'count'			=> 'id',							// Field to use if counting records
					'select'		=> 'display_meta',
					'from'			=> '#table_prefixgf_form_meta',
					'join'			=> '',
					'where'			=> 'form_id=#form_id',
					'order_by'		=> '',

					'sql_transpose'	=> array(

						'data_field'		=> 'display_meta',
						'data_format'		=> 'json',
						'data_sub_field'	=> 'fields'
					),

					'map' => array(

						array('source' => 'type', 'type' => 'scratch', 'destination' => 'type_source'),
					),

					'map_by_type' => array(

						'page'	=>	array(

							array('group' => true),
						),

						'section'	=>	array(

							array('section' => true),
							array('source' => 'label', 'type' => 'record', 'destination' => 'label', 'default' => WS_FORM_DEFAULT_SECTION_NAME),
							array('source' => 'cssClass', 'type' => 'meta', 'destination' => 'class_section_wrapper')
						)
					)
				)
			),

			'field' => array(

				'table_record'		=> array(

					// SQL parts
					'count'			=> 'id',							// Field to use if counting records
					'select'		=> 'display_meta',
					'from'			=> '#table_prefixgf_form_meta',
					'join'			=> '',
					'where'			=> 'form_id=#form_id',
					'order_by'		=> '',

					'sql_transpose'	=> array(

						'data_field'		=> 'display_meta',
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
							array('find' => 'multiselect', 'replace' => 'select'),
							array('find' => 'number', 'replace' => 'number'),
							array('find' => 'checkbox', 'replace' => 'checkbox'),
							array('find' => 'radio', 'replace' => 'radio'),
							array('find' => 'hidden', 'replace' => 'hidden'),
							array('find' => 'html', 'replace' => 'html'),
							array('find' => 'name', 'replace' => false),
							array('find' => 'date', 'replace' => 'datetime'),
							array('find' => 'time', 'replace' => 'datetime'),
							array('find' => 'phone', 'replace' => 'tel'),
							array('find' => 'address', 'replace' => false),
							array('find' => 'website', 'replace' => 'url'),
							array('find' => 'email', 'replace' => 'email'),
							array('find' => 'fileupload', 'replace' => 'file'),
							array('find' => 'captcha', 'replace' => 'recaptcha'),
							array('find' => 'list', 'replace' => 'text'),
							array('find' => 'consent', 'replace' => 'checkbox'),
							array('find' => 'signature', 'replace' => 'signature'),
							array('find' => 'post_title', 'replace' => 'text'),
							array('find' => 'post_content', 'replace' => 'textarea'),
							array('find' => 'post_excerpt', 'replace' => 'textarea'),
							array('find' => 'post_tags', 'replace' => 'text'),
							array('find' => 'post_category', 'replace' => 'text'),
							array('find' => 'post_image', 'replace' => 'file'),
							array('find' => 'post_custom_field', 'replace' => 'text'),
							array('find' => 'product', 'replace' => 'price'),
							array('find' => 'quantity', 'replace' => 'quantity'),
							array('find' => 'option', 'replace' => 'price_select'),
							array('find' => 'shipping', 'replace' => 'cart_price'),
							array('find' => 'total', 'replace' => 'cart_total'),
							array('find' => 'section', 'replace' => false),
							array('find' => 'page', 'replace' => false)
						)),

						array('source' => 'label', 'type' => 'record', 'destination' => 'label', 'default' => '#field_label_default'),

						array('source' => 'rangeMin', 'type' => 'meta', 'destination' => 'min'),
						array('source' => 'rangeMax', 'type' => 'meta', 'destination' => 'max'),
						array('source' => 'isRequired', 'type' => 'meta', 'destination' => 'required', 'lookup' => array(

							array('find' => false, 'replace' => ''),
							array('find' => true, 'replace' => 'on')
						)),
						array('source' => 'isHidden', 'type' => 'meta', 'destination' => 'hidden', 'lookup' => array(

							array('find' => false, 'replace' => ''),
							array('find' => true, 'replace' => 'on')
						)),
						array('source' => 'errorMessage', 'type' => 'meta', 'destination' => 'invalid_feedback'),
						array('source' => 'visibility', 'type' => 'meta', 'destination' => 'hidden', 'lookup' => array(

							array('find' => 'visible', 'replace' => ''),
							array('find' => 'hidden', 'replace' => 'on'),
							array('find' => 'administrative', 'replace' => 'on'),
						)),
						array('source' => 'inputMask', 'type' => 'scratch', 'destination' => 'input_mask_enabled'),
						array('source' => 'inputMaskValue', 'type' => 'meta', 'destination' => 'input_mask', 'condition' => (array('#input_mask_enabled' => true))),
						array('source' => 'maxLength', 'type' => 'meta', 'destination' => 'max_length'),
						array('source' => 'noDuplicates', 'type' => 'meta', 'destination' => 'dedupe', 'lookup' => array(

							array('find' => false, 'replace' => ''),
							array('find' => true, 'replace' => 'on')
						)),
						array('source' => 'useRichTextEditor', 'type' => 'meta', 'destination' => 'input_type_textarea', 'lookup' => array(

							array('find' => false, 'replace' => ''),
							array('find' => true, 'replace' => 'on')
						)),
						array('source' => 'labelPlacement', 'type' => 'meta', 'destination' => 'label_render', 'lookup' => array(

							array('find' => '', 'replace' => 'on'),
							array('find' => 'hidden_label', 'replace' => '')
						)),
						array('source' => 'placeholder', 'type' => 'meta', 'destination' => 'placeholder'),
						array('source' => 'description', 'type' => 'meta', 'destination' => 'help'),
						array('source' => 'cssClass', 'type' => 'meta', 'destination' => 'class_field_wrapper'),
						array('source' => 'defaultValue', 'type' => 'meta', 'destination' => 'default_value'),
						array('source' => 'enablePasswordInput', 'type' => 'record', 'destination' => 'type', 'value' => 'password', 'condition' => (array('#meta_value' => true))),
						array('source' => 'breakpoint_size_25', 'type' => 'meta', 'destination' => 'breakpoint_size_25'),
						array('source' => 'ecommerce_field_id', 'type' => 'meta', 'destination' => 'ecommerce_field_id'),
						array('source' => 'gwreadonly_enable', 'type' => 'meta', 'destination' => 'readonly', 'lookup' => array(

							array('find' => false, 'replace' => ''),
							array('find' => true, 'replace' => 'on')
						)),
					),

					'map_by_type' => array(

						'page'	=>	array(

							array('group' => true)
						),

						'date'	=>	array(

							array('type' => 'meta', 'destination' => 'input_type_datetime', 'value' => 'date'),
							array('source' => 'inputs', 'type' => 'meta', 'destination' => 'default_value', 'value' => '#MM_defaultValue/#DD_defaultValue/#YYYY_defaultValue', 'records_to_lookups' => 'label', 'condition' => array('#meta_value' => ''), 'condition_logic' => '!==')
						),

						'time'	=>	array(

							array('type' => 'meta', 'destination' => 'input_type_datetime', 'value' => 'time'),
							array('source' => 'inputs', 'type' => 'meta', 'destination' => 'default_value', 'value' => '#HH_defaultValue:#MM_defaultValue #AM/PM_defaultValue', 'records_to_lookups' => 'label')
						),

						'phone' =>	array(

							array('source' => 'phoneFormat', 'type' => 'meta', 'destination' => 'input_mask', 'lookup' => array(

									array('find' => 'standard', 'replace' => '(999) 999-9999'),
									array('find' => 'international', 'replace' => '')
							))
						),

						'section'	=>	array(

							array('section' => true)
						),

						'address'	=>	array(

							array('source' => 'inputs', 'type' => 'records', 'partial_merge' => array(

								// Address Line 1
								array('id' => 'id', 'id_partial' => '.1', 'merge' => array('type' => 'text'), 'condition' => array('isHidden' => false)),

								// Address Line 2
								array('id' => 'id', 'id_partial' => '.2', 'merge' => array('type' => 'text'), 'condition' => array('isHidden' => false)),

								// City
								array('id' => 'id', 'id_partial' => '.3', 'merge' => array('type' => 'text'), 'condition' => array('isHidden' => false), 'size_percent' => 50),

								// State
								array('id' => 'id', 'id_partial' => '.4', 'merge' => array('type' => 'text'), 'condition' => array('isHidden' => false), 'size_percent' => 50),

								// Zip
								array('id' => 'id', 'id_partial' => '.5', 'merge' => array('type' => 'text'), 'condition' => array('isHidden' => false), 'size_percent' => 50),

								// Country
								array('id' => 'id', 'id_partial' => '.6', 'merge' => array('type' => 'select', 'choices' => array(

									array('text' => 'Afghanistan', 'value' => 'Afghanistan'),
									array('text' => 'Åland Islands', 'value' => 'Åland Islands'),
									array('text' => 'Albania', 'value' => 'Albania'),
									array('text' => 'Algeria', 'value' => 'Algeria'),
									array('text' => 'American Samoa', 'value' => 'American Samoa'),
									array('text' => 'Andorra', 'value' => 'Andorra'),
									array('text' => 'Angola', 'value' => 'Angola'),
									array('text' => 'Anguilla', 'value' => 'Anguilla'),
									array('text' => 'Antarctica', 'value' => 'Antarctica'),
									array('text' => 'Antigua and Barbuda', 'value' => 'Antigua and Barbuda'),
									array('text' => 'Argentina', 'value' => 'Argentina'),
									array('text' => 'Armenia', 'value' => 'Armenia'),
									array('text' => 'Aruba', 'value' => 'Aruba'),
									array('text' => 'Australia', 'value' => 'Australia'),
									array('text' => 'Austria', 'value' => 'Austria'),
									array('text' => 'Azerbaijan', 'value' => 'Azerbaijan'),
									array('text' => 'Bahamas', 'value' => 'Bahamas'),
									array('text' => 'Bahrain', 'value' => 'Bahrain'),
									array('text' => 'Bangladesh', 'value' => 'Bangladesh'),
									array('text' => 'Barbados', 'value' => 'Barbados'),
									array('text' => 'Belarus', 'value' => 'Belarus'),
									array('text' => 'Belgium', 'value' => 'Belgium'),
									array('text' => 'Belize', 'value' => 'Belize'),
									array('text' => 'Benin', 'value' => 'Benin'),
									array('text' => 'Bermuda', 'value' => 'Bermuda'),
									array('text' => 'Bhutan', 'value' => 'Bhutan'),
									array('text' => 'Bolivia', 'value' => 'Bolivia'),
									array('text' => 'Bonaire, Sint Eustatius and Saba', 'value' => 'Bonaire, Sint Eustatius and Saba'),
									array('text' => 'Bosnia and Herzegovina', 'value' => 'Bosnia and Herzegovina'),
									array('text' => 'Botswana', 'value' => 'Botswana'),
									array('text' => 'Bouvet Island', 'value' => 'Bouvet Island'),
									array('text' => 'Brazil', 'value' => 'Brazil'),
									array('text' => 'British Indian Ocean Territory', 'value' => 'British Indian Ocean Territory'),
									array('text' => 'Brunei Darrussalam', 'value' => 'Brunei Darrussalam'),
									array('text' => 'Bulgaria', 'value' => 'Bulgaria'),
									array('text' => 'Burkina Faso', 'value' => 'Burkina Faso'),
									array('text' => 'Burundi', 'value' => 'Burundi'),
									array('text' => 'Cambodia', 'value' => 'Cambodia'),
									array('text' => 'Cameroon', 'value' => 'Cameroon'),
									array('text' => 'Canada', 'value' => 'Canada'),
									array('text' => 'Cape Verde', 'value' => 'Cape Verde'),
									array('text' => 'Cayman Islands', 'value' => 'Cayman Islands'),
									array('text' => 'Central African Republic', 'value' => 'Central African Republic'),
									array('text' => 'Chad', 'value' => 'Chad'),
									array('text' => 'Chile', 'value' => 'Chile'),
									array('text' => 'China', 'value' => 'China'),
									array('text' => 'Christmas Island', 'value' => 'Christmas Island'),
									array('text' => 'Cocos Islands', 'value' => 'Cocos Islands'),
									array('text' => 'Colombia', 'value' => 'Colombia'),
									array('text' => 'Comoros', 'value' => 'Comoros'),
									array('text' => 'Congo, Democratic Republic of the', 'value' => 'Congo, Democratic Republic of the'),
									array('text' => 'Congo, Republic of the', 'value' => 'Congo, Republic of the'),
									array('text' => 'Cook Islands', 'value' => 'Cook Islands'),
									array('text' => 'Costa Rica', 'value' => 'Costa Rica'),
									array('text' => 'Côte d&#039;Ivoire', 'value' => 'Côte d&#039;Ivoire'),
									array('text' => 'Croatia', 'value' => 'Croatia'),
									array('text' => 'Cuba', 'value' => 'Cuba'),
									array('text' => 'Curaçao', 'value' => 'Curaçao'),
									array('text' => 'Cyprus', 'value' => 'Cyprus'),
									array('text' => 'Czech Republic', 'value' => 'Czech Republic'),
									array('text' => 'Denmark', 'value' => 'Denmark'),
									array('text' => 'Djibouti', 'value' => 'Djibouti'),
									array('text' => 'Dominica', 'value' => 'Dominica'),
									array('text' => 'Dominican Republic', 'value' => 'Dominican Republic'),
									array('text' => 'Ecuador', 'value' => 'Ecuador'),
									array('text' => 'Egypt', 'value' => 'Egypt'),
									array('text' => 'El Salvador', 'value' => 'El Salvador'),
									array('text' => 'Equatorial Guinea', 'value' => 'Equatorial Guinea'),
									array('text' => 'Eritrea', 'value' => 'Eritrea'),
									array('text' => 'Estonia', 'value' => 'Estonia'),
									array('text' => 'Eswatini (Swaziland)', 'value' => 'Eswatini (Swaziland)'),
									array('text' => 'Ethiopia', 'value' => 'Ethiopia'),
									array('text' => 'Falkland Islands', 'value' => 'Falkland Islands'),
									array('text' => 'Faroe Islands', 'value' => 'Faroe Islands'),
									array('text' => 'Fiji', 'value' => 'Fiji'),
									array('text' => 'Finland', 'value' => 'Finland'),
									array('text' => 'France', 'value' => 'France'),
									array('text' => 'French Guiana', 'value' => 'French Guiana'),
									array('text' => 'French Polynesia', 'value' => 'French Polynesia'),
									array('text' => 'French Southern Territories', 'value' => 'French Southern Territories'),
									array('text' => 'Gabon', 'value' => 'Gabon'),
									array('text' => 'Gambia', 'value' => 'Gambia'),
									array('text' => 'Georgia', 'value' => 'Georgia'),
									array('text' => 'Germany', 'value' => 'Germany'),
									array('text' => 'Ghana', 'value' => 'Ghana'),
									array('text' => 'Gibraltar', 'value' => 'Gibraltar'),
									array('text' => 'Greece', 'value' => 'Greece'),
									array('text' => 'Greenland', 'value' => 'Greenland'),
									array('text' => 'Grenada', 'value' => 'Grenada'),
									array('text' => 'Guadeloupe', 'value' => 'Guadeloupe'),
									array('text' => 'Guam', 'value' => 'Guam'),
									array('text' => 'Guatemala', 'value' => 'Guatemala'),
									array('text' => 'Guernsey', 'value' => 'Guernsey'),
									array('text' => 'Guinea', 'value' => 'Guinea'),
									array('text' => 'Guinea-Bissau', 'value' => 'Guinea-Bissau'),
									array('text' => 'Guyana', 'value' => 'Guyana'),
									array('text' => 'Haiti', 'value' => 'Haiti'),
									array('text' => 'Heard and McDonald Islands', 'value' => 'Heard and McDonald Islands'),
									array('text' => 'Holy See', 'value' => 'Holy See'),
									array('text' => 'Honduras', 'value' => 'Honduras'),
									array('text' => 'Hong Kong', 'value' => 'Hong Kong'),
									array('text' => 'Hungary', 'value' => 'Hungary'),
									array('text' => 'Iceland', 'value' => 'Iceland'),
									array('text' => 'India', 'value' => 'India'),
									array('text' => 'Indonesia', 'value' => 'Indonesia'),
									array('text' => 'Iran', 'value' => 'Iran'),
									array('text' => 'Iraq', 'value' => 'Iraq'),
									array('text' => 'Ireland', 'value' => 'Ireland'),
									array('text' => 'Isle of Man', 'value' => 'Isle of Man'),
									array('text' => 'Israel', 'value' => 'Israel'),
									array('text' => 'Italy', 'value' => 'Italy'),
									array('text' => 'Jamaica', 'value' => 'Jamaica'),
									array('text' => 'Japan', 'value' => 'Japan'),
									array('text' => 'Jersey', 'value' => 'Jersey'),
									array('text' => 'Jordan', 'value' => 'Jordan'),
									array('text' => 'Kazakhstan', 'value' => 'Kazakhstan'),
									array('text' => 'Kenya', 'value' => 'Kenya'),
									array('text' => 'Kiribati', 'value' => 'Kiribati'),
									array('text' => 'Kuwait', 'value' => 'Kuwait'),
									array('text' => 'Kyrgyzstan', 'value' => 'Kyrgyzstan'),
									array('text' => 'Lao People&#039;s Democratic Republic', 'value' => 'Lao People&#039;s Democratic Republic'),
									array('text' => 'Latvia', 'value' => 'Latvia'),
									array('text' => 'Lebanon', 'value' => 'Lebanon'),
									array('text' => 'Lesotho', 'value' => 'Lesotho'),
									array('text' => 'Liberia', 'value' => 'Liberia'),
									array('text' => 'Libya', 'value' => 'Libya'),
									array('text' => 'Liechtenstein', 'value' => 'Liechtenstein'),
									array('text' => 'Lithuania', 'value' => 'Lithuania'),
									array('text' => 'Luxembourg', 'value' => 'Luxembourg'),
									array('text' => 'Macau', 'value' => 'Macau'),
									array('text' => 'Macedonia', 'value' => 'Macedonia'),
									array('text' => 'Madagascar', 'value' => 'Madagascar'),
									array('text' => 'Malawi', 'value' => 'Malawi'),
									array('text' => 'Malaysia', 'value' => 'Malaysia'),
									array('text' => 'Maldives', 'value' => 'Maldives'),
									array('text' => 'Mali', 'value' => 'Mali'),
									array('text' => 'Malta', 'value' => 'Malta'),
									array('text' => 'Marshall Islands', 'value' => 'Marshall Islands'),
									array('text' => 'Martinique', 'value' => 'Martinique'),
									array('text' => 'Mauritania', 'value' => 'Mauritania'),
									array('text' => 'Mauritius', 'value' => 'Mauritius'),
									array('text' => 'Mayotte', 'value' => 'Mayotte'),
									array('text' => 'Mexico', 'value' => 'Mexico'),
									array('text' => 'Micronesia', 'value' => 'Micronesia'),
									array('text' => 'Moldova', 'value' => 'Moldova'),
									array('text' => 'Monaco', 'value' => 'Monaco'),
									array('text' => 'Mongolia', 'value' => 'Mongolia'),
									array('text' => 'Montenegro', 'value' => 'Montenegro'),
									array('text' => 'Montserrat', 'value' => 'Montserrat'),
									array('text' => 'Morocco', 'value' => 'Morocco'),
									array('text' => 'Mozambique', 'value' => 'Mozambique'),
									array('text' => 'Myanmar', 'value' => 'Myanmar'),
									array('text' => 'Namibia', 'value' => 'Namibia'),
									array('text' => 'Nauru', 'value' => 'Nauru'),
									array('text' => 'Nepal', 'value' => 'Nepal'),
									array('text' => 'Netherlands', 'value' => 'Netherlands'),
									array('text' => 'New Caledonia', 'value' => 'New Caledonia'),
									array('text' => 'New Zealand', 'value' => 'New Zealand'),
									array('text' => 'Nicaragua', 'value' => 'Nicaragua'),
									array('text' => 'Niger', 'value' => 'Niger'),
									array('text' => 'Nigeria', 'value' => 'Nigeria'),
									array('text' => 'Niue', 'value' => 'Niue'),
									array('text' => 'Norfolk Island', 'value' => 'Norfolk Island'),
									array('text' => 'North Korea', 'value' => 'North Korea'),
									array('text' => 'Northern Mariana Islands', 'value' => 'Northern Mariana Islands'),
									array('text' => 'Norway', 'value' => 'Norway'),
									array('text' => 'Oman', 'value' => 'Oman'),
									array('text' => 'Pakistan', 'value' => 'Pakistan'),
									array('text' => 'Palau', 'value' => 'Palau'),
									array('text' => 'Palestine, State of', 'value' => 'Palestine, State of'),
									array('text' => 'Panama', 'value' => 'Panama'),
									array('text' => 'Papua New Guinea', 'value' => 'Papua New Guinea'),
									array('text' => 'Paraguay', 'value' => 'Paraguay'),
									array('text' => 'Peru', 'value' => 'Peru'),
									array('text' => 'Philippines', 'value' => 'Philippines'),
									array('text' => 'Pitcairn', 'value' => 'Pitcairn'),
									array('text' => 'Poland', 'value' => 'Poland'),
									array('text' => 'Portugal', 'value' => 'Portugal'),
									array('text' => 'Puerto Rico', 'value' => 'Puerto Rico'),
									array('text' => 'Qatar', 'value' => 'Qatar'),
									array('text' => 'Réunion', 'value' => 'Réunion'),
									array('text' => 'Romania', 'value' => 'Romania'),
									array('text' => 'Russia', 'value' => 'Russia'),
									array('text' => 'Rwanda', 'value' => 'Rwanda'),
									array('text' => 'Saint Barthélemy', 'value' => 'Saint Barthélemy'),
									array('text' => 'Saint Helena', 'value' => 'Saint Helena'),
									array('text' => 'Saint Kitts and Nevis', 'value' => 'Saint Kitts and Nevis'),
									array('text' => 'Saint Lucia', 'value' => 'Saint Lucia'),
									array('text' => 'Saint Martin', 'value' => 'Saint Martin'),
									array('text' => 'Saint Pierre and Miquelon', 'value' => 'Saint Pierre and Miquelon'),
									array('text' => 'Saint Vincent and the Grenadines', 'value' => 'Saint Vincent and the Grenadines'),
									array('text' => 'Samoa', 'value' => 'Samoa'),
									array('text' => 'San Marino', 'value' => 'San Marino'),
									array('text' => 'Sao Tome and Principe', 'value' => 'Sao Tome and Principe'),
									array('text' => 'Saudi Arabia', 'value' => 'Saudi Arabia'),
									array('text' => 'Senegal', 'value' => 'Senegal'),
									array('text' => 'Serbia', 'value' => 'Serbia'),
									array('text' => 'Seychelles', 'value' => 'Seychelles'),
									array('text' => 'Sierra Leone', 'value' => 'Sierra Leone'),
									array('text' => 'Singapore', 'value' => 'Singapore'),
									array('text' => 'Sint Maarten', 'value' => 'Sint Maarten'),
									array('text' => 'Slovakia', 'value' => 'Slovakia'),
									array('text' => 'Slovenia', 'value' => 'Slovenia'),
									array('text' => 'Solomon Islands', 'value' => 'Solomon Islands'),
									array('text' => 'Somalia', 'value' => 'Somalia'),
									array('text' => 'South Africa', 'value' => 'South Africa'),
									array('text' => 'South Georgia', 'value' => 'South Georgia'),
									array('text' => 'South Korea', 'value' => 'South Korea'),
									array('text' => 'South Sudan', 'value' => 'South Sudan'),
									array('text' => 'Spain', 'value' => 'Spain'),
									array('text' => 'Sri Lanka', 'value' => 'Sri Lanka'),
									array('text' => 'Sudan', 'value' => 'Sudan'),
									array('text' => 'Suriname', 'value' => 'Suriname'),
									array('text' => 'Svalbard and Jan Mayen Islands', 'value' => 'Svalbard and Jan Mayen Islands'),
									array('text' => 'Sweden', 'value' => 'Sweden'),
									array('text' => 'Switzerland', 'value' => 'Switzerland'),
									array('text' => 'Syria', 'value' => 'Syria'),
									array('text' => 'Taiwan', 'value' => 'Taiwan'),
									array('text' => 'Tajikistan', 'value' => 'Tajikistan'),
									array('text' => 'Tanzania', 'value' => 'Tanzania'),
									array('text' => 'Thailand', 'value' => 'Thailand'),
									array('text' => 'Timor-Leste', 'value' => 'Timor-Leste'),
									array('text' => 'Togo', 'value' => 'Togo'),
									array('text' => 'Tokelau', 'value' => 'Tokelau'),
									array('text' => 'Tonga', 'value' => 'Tonga'),
									array('text' => 'Trinidad and Tobago', 'value' => 'Trinidad and Tobago'),
									array('text' => 'Tunisia', 'value' => 'Tunisia'),
									array('text' => 'Turkey', 'value' => 'Turkey'),
									array('text' => 'Turkmenistan', 'value' => 'Turkmenistan'),
									array('text' => 'Turks and Caicos Islands', 'value' => 'Turks and Caicos Islands'),
									array('text' => 'Tuvalu', 'value' => 'Tuvalu'),
									array('text' => 'Uganda', 'value' => 'Uganda'),
									array('text' => 'Ukraine', 'value' => 'Ukraine'),
									array('text' => 'United Arab Emirates', 'value' => 'United Arab Emirates'),
									array('text' => 'United Kingdom', 'value' => 'United Kingdom'),
									array('text' => 'United States', 'value' => 'United States'),
									array('text' => 'Uruguay', 'value' => 'Uruguay'),
									array('text' => 'US Minor Outlying Islands', 'value' => 'US Minor Outlying Islands'),
									array('text' => 'Uzbekistan', 'value' => 'Uzbekistan'),
									array('text' => 'Vanuatu', 'value' => 'Vanuatu'),
									array('text' => 'Venezuela', 'value' => 'Venezuela'),
									array('text' => 'Vietnam', 'value' => 'Vietnam'),
									array('text' => 'Virgin Islands, British', 'value' => 'Virgin Islands, British'),
									array('text' => 'Virgin Islands, U.S.', 'value' => 'Virgin Islands, U.S.'),
									array('text' => 'Wallis and Futuna', 'value' => 'Wallis and Futuna'),
									array('text' => 'Western Sahara', 'value' => 'Western Sahara'),
									array('text' => 'Yemen', 'value' => 'Yemen'),
									array('text' => 'Zambia', 'value' => 'Zambia'),
									array('text' => 'Zimbabwe', 'value' => 'Zimbabwe')

								)), 'condition' => array('isHidden' => false), 'size_percent' => 50)
							)),
						),

						'fileupload'	=>	array(

							array('source' => 'file', 'type' => 'array', 'map' => array(

								array('source' => 'allowedExtensions', 'type' => 'meta', 'destination' => 'accept', 'lookup_csv_join' => ',', 'lookup_csv' => array(

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
								)),
								array('source' => 'multipleFiles', 'type' => 'meta', 'destination' => 'multiple_file', 'lookup' => array(

									array('find' => false, 'replace' => ''),
									array('find' => true, 'replace' => 'on')
								))
							))
						),

						'captcha'	=>	array(

							array('type' => 'meta', 'value_option' => 'rg_gforms_captcha_public_key', 'destination' => 'recaptcha_site_key'),
							array('type' => 'meta', 'value_option' => 'rg_gforms_captcha_private_key', 'destination' => 'recaptcha_secret_key'),
							array('source' => 'captchaTheme', 'type' => 'meta', 'destination' => 'recaptcha_theme', 'lookup' => array(

								array('find' => '', 'replace' => 'light'),
								array('find' => 'light', 'replace' => 'light'),
								array('find' => 'dark', 'replace' => 'dark'),
							))
						),

						'consent'	=>	array(

							array('source' => 'checkboxLabel', 'type' => 'scratch', 'destination' => 'consent_label'),
							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_checkbox', 'value' => array(

								// Config
								'rows_per_page'		=>	10,
								'group_index'		=>	0,
								'default'			=>	array(),

								// Columns
								'columns' => array(

									array('id' => 0, 'label' => __('Label', 'ws-form'))
								),

								// Group
								'groups' => array(

									array(

										'label' 		=> __('Checkboxes', 'ws-form'),
										'page'			=> 0,
										'disabled'		=> '',
										'mask_group'	=> '',

										// Rows (Only injected for a new data grid, blank for new groups)
										'rows' 		=> array(

											array(

												'id'		=> 1,
												'default'	=> '',
												'required'	=> '',
												'disabled'	=> '',
												'hidden'	=> '',
												'data'		=> array('#consent_label')
											)
										)
									)
								)
							))
						),

						'name'		=>	array(

							array('source' => 'inputs', 'type' => 'records', 'auto_size' => true, 'partial_merge' => array(

								// Add additional field information (Gravity Forms format)
								array('id' => 'id', 'id_partial' => '.2', 'merge' => array('type' => 'select'), 'condition' => array('isHidden' => false)),
								array('id' => 'id', 'id_partial' => '.3', 'merge' => array('type' => 'text'), 'condition' => array('isHidden' => false)),
								array('id' => 'id', 'id_partial' => '.4', 'merge' => array('type' => 'text'), 'condition' => array('isHidden' => false)),
								array('id' => 'id', 'id_partial' => '.6', 'merge' => array('type' => 'text'), 'condition' => array('isHidden' => false)),
								array('id' => 'id', 'id_partial' => '.8', 'merge' => array('type' => 'text'), 'condition' => array('isHidden' => false))
							)),
						),

						'html'		=>	array(

							array('source' => 'content', 'type' => 'meta', 'destination' => 'html_editor'),
						),

						'checkbox'	=>	array(

							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_checkbox', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'isSelected', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => false, 'replace' => ''),
									array('find' => true, 'replace' => 'on')
								)),
								array('source' => 'value', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'text', 'type' => 'record', 'destination' => 'label'),
								array('type' => 'record', 'destination' => 'required', 'value' => '#required')
							)),
							array('source' => 'enableSelectAll', 'type' => 'meta', 'destination' => 'select_all', 'lookup' => array(

								array('find' => false, 'replace' => ''),
								array('find' => true, 'replace' => 'on')
							))
						),

						'signature'	=>	array(

							array('source' => 'backgroundColor', 'type' => 'meta', 'destination' => 'signature_background_color'),
							array('source' => 'borderColor', 'type' => 'meta', 'destination' => 'signature_border_color'),
							array('source' => 'borderStyle', 'type' => 'meta', 'destination' => 'signature_border_style'),
							array('source' => 'borderWidth', 'type' => 'meta', 'destination' => 'signature_border_width'),
							array('source' => 'penColor', 'type' => 'meta', 'destination' => 'signature_pen_color'),
							array('source' => 'penSize', 'type' => 'meta', 'destination' => 'signature_dot_size'),
							array('type' => 'meta', 'destination' => 'signature_mime', 'value' => 'image/png')
						),

						'radio'	=>	array(

							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_radio', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'isSelected', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => false, 'replace' => ''),
									array('find' => true, 'replace' => 'on')
								)),
								array('source' => 'value', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'text', 'type' => 'record', 'destination' => 'label')
							))
						),

						'select'	=>	array(

							array('source' => 'placeholder', 'type' => 'meta', 'destination' => 'placeholder_row'),
							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_select', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'isSelected', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => false, 'replace' => ''),
									array('find' => true, 'replace' => 'on')
								)),
								array('source' => 'value', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'text', 'type' => 'record', 'destination' => 'label')
							)),
						),

						'multiselect'	=>	array(

							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_select', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'isSelected', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => false, 'replace' => ''),
									array('find' => true, 'replace' => 'on')
								)),
								array('source' => 'value', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'text', 'type' => 'record', 'destination' => 'label'),
								array('type' => 'record', 'destination' => 'required', 'value' => '#required')
							)),
							array('source' => 'multiSelectSize', 'type' => 'meta', 'destination' => 'size', 'default' => '7'),
							array('type' => 'meta', 'destination' => 'multiple', 'value' => 'on')
						),

						'product'		=>	array(

							array('source' => 'basePrice', 'type' => 'scratch', 'destination' => 'base_price'),
							array('source' => 'disableQuantity', 'type' => 'scratch', 'destination' => 'disable_quantity', 'lookup' => array(

								array('find' => false, 'replace' => ''),
								array('find' => true, 'replace' => 'on')
							)),
							array('source' => 'inputType', 'type' => 'scratch', 'destination' => 'input_type'),

							// Single
							array('type' => 'record', 'destination' => 'type', 'value' => 'price', 'condition' => array('#input_type' => 'singleproduct')),
							array('source' => 'inputs', 'type' => 'records', 'auto_size' => true, 'partial_merge' => array(

								// Add additional field information (Gravity Forms format)
								array('id' => 'id', 'id_partial' => '.2', 'merge' => array('label' => '#label', 'type' => 'price', 'gwreadonly_enable' => true, 'defaultValue' => '#base_price')),
								array('id' => 'id', 'id_partial' => '.3', 'merge' => array('type' => 'quantity', 'productField' => '#id'), 'condition' => array('#disable_quantity' => 'on'), 'condition_logic' => '!==')
							), 'condition' => array('#input_type' => 'singleproduct')),

							// Select
							array('type' => 'record', 'destination' => 'type', 'value' => 'price_select', 'condition' => array('#input_type' => 'select')),
							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_select_price', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'isSelected', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => false, 'replace' => ''),
									array('find' => true, 'replace' => 'on')
								)),
								array('source' => 'price', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'text', 'type' => 'record', 'destination' => 'label')
							), 'condition' => array('#input_type' => 'select')),
							array('type' => 'meta', 'destination' => 'select_price_field_value', 'value' => '0', 'condition' => array('#input_type' => 'select')),

							// Radio
							array('type' => 'record', 'destination' => 'type', 'value' => 'price_radio', 'condition' => array('#input_type' => 'radio')),
							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_radio_price', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'isSelected', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => false, 'replace' => ''),
									array('find' => true, 'replace' => 'on')
								)),
								array('source' => 'price', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'text', 'type' => 'record', 'destination' => 'label')
							), 'condition' => array('#input_type' => 'radio')),
							array('type' => 'meta', 'destination' => 'radio_price_field_value', 'value' => '0', 'condition' => array('#input_type' => 'radio')),

							// Hidden
							array('type' => 'meta', 'destination' => 'hidden', 'value' => 'on', 'condition' => array('#input_type' => 'hiddenproduct')),
						),

						'quantity'		=>	array(

							array('source' => 'productField', 'type' => 'meta', 'destination' => 'ecommerce_field_id', 'value' => 'lookup_#meta_value'),
							array('source' => 'defaultValue', 'type' => 'meta', 'destination' => 'default_value', 'default' => '1'),
							array('source' => 'rangeMin', 'type' => 'meta', 'destination' => 'ecommerce_quantity_min', 'default' => '0'),
							array('source' => 'rangeMax', 'type' => 'meta', 'destination' => 'ecommerce_quantity_max'),
						),

						'shipping'		=>	array(

							// Single
							array('source' => 'inputType', 'type' => 'record', 'destination' => 'type', 'value' => 'cart_price', 'condition' => array('#meta_value' => 'singleshipping')),
							array('source' => 'inputType', 'type' => 'meta', 'destination' => 'ecommerce_cart_price_type', 'value' => 'shipping', 'condition' => array('#meta_value' => 'singleshipping')),
							array('source' => 'basePrice', 'type' => 'meta', 'destination' => 'default_value'),
							array('source' => 'inputType', 'type' => 'scratch', 'destination' => 'input_type'),

							// Select
							array('type' => 'record', 'destination' => 'type', 'value' => 'price_select', 'condition' => array('#input_type' => 'select')),
							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_select_price', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'isSelected', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => false, 'replace' => ''),
									array('find' => true, 'replace' => 'on')
								)),
								array('source' => 'price', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'text', 'type' => 'record', 'destination' => 'label'),
								array('type' => 'record', 'destination' => 'required', 'value' => '#required')
							), 'condition' => array('#input_type' => 'select')),
							array('type' => 'meta', 'destination' => 'select_price_field_value', 'value' => '0', 'condition' => array('#input_type' => 'select')),

							// Radio
							array('type' => 'record', 'destination' => 'type', 'value' => 'price_radio', 'condition' => array('#input_type' => 'radio')),
							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_radio_price', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'isSelected', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => false, 'replace' => ''),
									array('find' => true, 'replace' => 'on')
								)),
								array('source' => 'price', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'text', 'type' => 'record', 'destination' => 'label'),
								array('type' => 'record', 'destination' => 'required', 'value' => '#required')
							), 'condition' => array('#input_type' => 'radio')),
							array('type' => 'meta', 'destination' => 'radio_price_field_value', 'value' => '0', 'condition' => array('#input_type' => 'radio')),
						),

						'option'	=>	array(

							array('source' => 'inputType', 'type' => 'scratch', 'destination' => 'input_type'),

							// Select
							array('type' => 'record', 'destination' => 'type', 'value' => 'price_select', 'condition' => array('#input_type' => 'select')),
							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_select_price', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'isSelected', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => false, 'replace' => ''),
									array('find' => true, 'replace' => 'on')
								)),
								array('source' => 'price', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'text', 'type' => 'record', 'destination' => 'label'),
								array('type' => 'record', 'destination' => 'required', 'value' => '#required')
							), 'condition' => array('#input_type' => 'select')),
							array('type' => 'meta', 'destination' => 'select_price_field_value', 'value' => '0', 'condition' => array('#input_type' => 'select')),

							// Checkbox
							array('type' => 'record', 'destination' => 'type', 'value' => 'price_checkbox', 'condition' => array('#input_type' => 'checkbox')),
							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_checkbox_price', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'isSelected', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => false, 'replace' => ''),
									array('find' => true, 'replace' => 'on')
								)),
								array('source' => 'price', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'text', 'type' => 'record', 'destination' => 'label'),
								array('type' => 'record', 'destination' => 'required', 'value' => '#required'),
							), 'condition' => array('#input_type' => 'checkbox')),
							array('type' => 'meta', 'destination' => 'checkbox_price_field_value', 'value' => '0', 'condition' => array('#input_type' => 'checkbox')),

							// Radio
							array('type' => 'record', 'destination' => 'type', 'value' => 'price_radio', 'condition' => array('#input_type' => 'radio')),
							array('source' => 'choices', 'type' => 'meta', 'destination' => 'data_grid_radio_price', 'process' => array(array('process' => 'datagrid_2_column')), 'map' => array(

								array('source' => 'isSelected', 'type' => 'record', 'destination' => 'default', 'lookup' => array(

									array('find' => false, 'replace' => ''),
									array('find' => true, 'replace' => 'on')
								)),
								array('source' => 'price', 'type' => 'record', 'destination' => 'value'),
								array('source' => 'text', 'type' => 'record', 'destination' => 'label'),
								array('type' => 'record', 'destination' => 'required', 'value' => '#required')
							), 'condition' => array('#input_type' => 'radio')),
							array('type' => 'meta', 'destination' => 'radio_price_field_value', 'value' => '0', 'condition' => array('#input_type' => 'radio')),
						),
					)
				),

				// Fields to add at the end
				'table_record_append'	=> array(

					array('type' => 'submit', 'label' => '#button_text')
				),

				// Field type processing
				// br_to_newline 	Convert br tags to newlines
				// strip_tags 		Strip HTML tags
				// csv_to_array 	Comma separated values to array
				'process'	=>	array(

					// WS Form field type
					'checkbox'		=>	array(array('process' => 'json_decode'), array('process' => 'partials_to_array')),
					'radio'			=>	array(array('process' => 'json_decode')),
					'select'		=>	array(array('process' => 'json_decode')),
					'signature' 	=>	array(array('process' => 'filename_to_file', 'source_path' => '#wp_uploads/gravity_forms/signatures/#filename')),
					'file' 			=>	array(array('process' => 'upload_url_to_file'))
				)
			),

			'submission' => array(

				'table_record'		=> array(

					'count'		=> 'id',							// Field to use if counting records
					'select'	=> 'id,date_created,date_updated,is_starred,is_read,ip,source_url,user_agent,payment_amount,payment_status,payment_method,transaction_id,is_fulfilled,created_by,status',
					'from' 		=> '#table_prefixgf_entry',
					'join'		=> '',
					'where' 	=> 'form_id=#form_id',

					'map'	=> array(

						// Mandatory mappings
						array('source' => 'id', 'type' => 'record', 'destination' => 'id'),
						array('source' => 'date_created', 'type' => 'record', 'destination' => 'date_added'),
						array('source' => 'date_updated', 'type' => 'record', 'destination' => 'date_updated'),
						array('source' => 'is_starred', 'type' => 'record', 'destination' => 'starred'),
						array('source' => 'is_read', 'type' => 'record', 'destination' => 'viewed'),
						array('source' => 'ip', 'type' => 'meta', 'destination' => 'tracking_remote_ip', 'condition' => array('#meta_value' => ''), 'condition_logic' => '!=='),
						array('source' => 'source_url', 'type' => 'meta', 'destination' => 'tracking_referrer', 'condition' => array('#meta_value' => ''), 'condition_logic' => '!=='),
						array('source' => 'user_agent', 'type' => 'meta', 'destination' => 'tracking_agent', 'condition' => array('#meta_value' => ''), 'condition_logic' => '!=='),
						array('source' => 'payment_amount', 'type' => 'meta', 'destination' => 'ecommerce_cart_total', 'condition' => array('#meta_value' => ''), 'condition_logic' => '!=='),
						array('source' => 'payment_status', 'type' => 'meta', 'destination' => 'ecommerce_status', 'lookup' => array(

							array('find' => 'Pending', 'replace' => 'pending_payment'),
							array('find' => 'Processing', 'replace' => 'processing'),
							array('find' => 'Active', 'replace' => 'active'),
							array('find' => 'Cancelled', 'replace' => 'cancelled'),
							array('find' => 'Failed', 'replace' => 'failed'),
							array('find' => 'Expired', 'replace' => 'failed'),
							array('find' => 'Refunded', 'replace' => 'refunded'),
							array('find' => 'Reversed', 'replace' => 'refunded'),
							array('find' => 'Authorized', 'replace' => 'authorized'),
							array('find' => 'Paid', 'replace' => 'completed'),
							array('find' => 'Approved', 'replace' => 'completed'),
							array('find' => 'Voided', 'replace' => 'voided')

						), 'condition' => array('#meta_value' => ''), 'condition_logic' => '!=='),

						array('source' => 'payment_method', 'type' => 'meta', 'destination' => 'ecommerce_payment_method', 'condition' => array('#meta_value' => ''), 'condition_logic' => '!=='),
						array('source' => 'transaction_id', 'type' => 'meta', 'destination' => 'ecommerce_transaction_id', 'condition' => array('#meta_value' => ''), 'condition_logic' => '!=='),
						array('source' => 'is_fulfilled', 'type' => 'meta', 'destination' => 'ecommerce_fulfilled', 'lookup' => array(

							array('find' => false, 'replace' => ''),
							array('find' => true, 'replace' => 'on')
						)),
						array('source' => 'created_by', 'type' => 'record', 'destination' => 'user_id'),
						array('source' => 'status', 'type' => 'record', 'destination' => 'status', 'lookup' => array(

							array('find' => 'active', 'replace' => 'publish'),
							array('find' => 'spam', 'replace' => 'publish'),
							array('find' => 'trash', 'replace' => 'publish')
						))
					),

					'limit' => 25
				),

				'table_metadata'	=> array(

					'select' 	=> 'meta_key, meta_value',
					'from' 		=> '#table_prefixgf_entry_meta',
					'join' 		=> '',
					'where' 	=> 'entry_id=#record_id AND form_id=#form_id',

					'meta_key_mask' => '#id'
				)
			)
		);

		return $migrate;
	}