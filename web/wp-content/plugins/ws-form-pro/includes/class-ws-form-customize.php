<?php

	/**
	 * Manages plugin customization
	 */

	class WS_Form_Customize {

		public function __construct($wp_customize) {

			// Add WS Form panel
			self::add_panel($wp_customize);

			// Add sections, settings and controls
			self::add_sections($wp_customize);

			// Add scripts
			wp_add_inline_script('customize-controls', self::customize_controls_after());
		}

		public function add_panel($wp_customize) {

			$wp_customize->add_panel('wsform_panel', array(

				'priority'       	=> 200,
				'theme_supports'	=> '',
				'title'          	=> __('WS Form', 'ws-form'),
			));
		}

		public function add_sections($wp_customize) {

			// Get customize
			$customize_sections = WS_Form_Config::get_customize();

			// Run through each group
			foreach($customize_sections as $customize_section_id => $customize_section) {

				$customize_section_id = WS_FORM_OPTION_PREFIX . '_section_' . $customize_section_id;

				// Add section
				$wp_customize->add_section(

					$customize_section_id,

					array(
						'title'    => $customize_section['heading'],
						'priority' => 10,
						'panel'    => 'wsform_panel',
					)
				);

				$customize_fields = $customize_section['fields'];

				foreach($customize_fields as $customize_field_id => $customize_field) {

					$setting_id = WS_FORM_OPTION_PREFIX . '[' . $customize_field_id . ']';
					$control_id = WS_FORM_OPTION_PREFIX . '_control_' . $customize_field_id;

					switch($customize_field['type']) {

						case 'checkbox' :

							$wp_customize->add_setting(

								$setting_id,

								array(
									'default'           => isset($customize_field['default']) ? $customize_field['default'] : '',
									'type'              => 'option',
									'sanitize_callback' => array($this, 'sanitize_callback_checkbox'),
								)
							);

						default :

							$wp_customize->add_setting(

								$setting_id,

								array(
									'default'           => isset($customize_field['default']) ? $customize_field['default'] : '',
									'type'              => 'option'
								)
							);
					}

					switch($customize_field['type']) {

						case 'select' :

							$wp_customize->add_control(

								$control_id,

								array(
									'label'			=> $customize_field['label'],
									'description'	=> isset($customize_field['description']) ? $customize_field['description'] : '',
									'section'		=> $customize_section_id,
									'settings'		=> $setting_id,
									'type'			=> 'select',
									'choices'		=> $customize_field['choices']
								)
							);

							break;

						case 'color' :

							$wp_customize->add_control(

								new WP_Customize_Color_Control( 

									$wp_customize, 
									$control_id,

									array(
										'label'			=> $customize_field['label'],
										'description'	=> isset($customize_field['description']) ? $customize_field['description'] : '',
										'section'		=> $customize_section_id,
										'settings'		=> $setting_id,
									)
								)
							);

							break;

						default :

							$wp_customize->add_control(

								$control_id,

								array(
									'label'       => $customize_field['label'],
									'description' => isset($customize_field['description']) ? $customize_field['description'] : '',
									'section'     => $customize_section_id,
									'settings'    => $setting_id,
									'type'        => $customize_field['type']
								)
							);
					}
				}
			}
		}

		public function sanitize_callback_checkbox( $checked ) {

			// Boolean check (Have to use strings because WordPress saves false as 1 in preview pane)
			return ((isset($checked) && true == $checked) ? 'true' : 'false');
		}

		public function customize_controls_after() {

			// Work out which form to use for the preview
			$form_id = intval(WS_Form_Common::get_query_var('wsf_preview_form_id'));
			if($form_id === 0) {

				// Find a default form to use
				$ws_form_form = new WS_Form_Form();
				$form_id = $ws_form_form->db_get_preview_form_id();
			}

			if($form_id === 0) { return; }

			// Get form preview URL
			$form_preview_url = WS_Form_Common::get_preview_url($form_id);

			// Start script
			$return_script = "	wp.customize.bind('ready', function() {\n";

			// Determine if we should automatically open the WS Form panel
			$wsf_panel_open = WS_Form_Common::get_query_var('wsf_panel_open');
			if($wsf_panel_open) {

				// Open immediately
				$return_script .= sprintf("		wp.customize.previewer.previewUrl('%s');\n", esc_js($form_preview_url));
				$return_script .= "		wp.customize.panel('wsform_panel').expand();\n";

			} else {

				// Open if WS Form panel is opened
				$return_script .= "		wp.customize.panel('wsform_panel', function(panel) {\n";
				$return_script .= "			panel.expanded.bind(function(is_expanded) {\n";
				$return_script .= "				if(is_expanded) {\n";
				$return_script .= sprintf("					wp.customize.previewer.previewUrl('%s');\n", esc_js($form_preview_url));
				$return_script .= "				}\n";
				$return_script .= "			});\n";
				$return_script .= "		});\n";
			}

			$return_script .= '	});';

			return $return_script;
		}
	}
