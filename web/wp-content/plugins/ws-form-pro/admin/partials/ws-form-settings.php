<?php

	// Get options
	$options = WS_Form_Config::get_options();

	// Get current tab
	$tab_current = WS_Form_Common::get_query_var('tab', false);
	if($tab_current === false) {
		$tab_current = WS_Form_Common::get_query_var_nonce('tab', 'appearance', false, false, true, 'POST');	
	}
	if($tab_current == 'setup') { $tab_current = 'appearance'; }				// Backward compatibility

	// Check tab is valid
	if(!isset($options[$tab_current])) {
?>
<script>

	location.href = '<?php echo esc_html(WS_Form_Common::get_admin_url('ws-form-settings')); ?>';

</script>
<?php

		exit;
	}

	// File upload checks
	$upload_checks = WS_Form_Common::uploads_check();
	$max_upload_size = $upload_checks['max_upload_size'];
	$max_uploads = $upload_checks['max_uploads'];

	// Loader icon
	WS_Form_Common::loader();
?>
<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?>">

<!-- Header -->
<div class="wsf-heading">
<h1 class="wp-heading-inline"><?php esc_html_e('Settings', 'ws-form'); ?></h1>
</div>
<hr class="wp-header-end">
<!-- /Header -->
<?php

	// Review nag
	WS_Form_Common::review();
	
	// SSL Warning
	if(($tab_current == 'data') && !is_ssl()) {

		WS_Form_Common::admin_message_render(__('Your website is not configured to use a secure certificate. We recommend enabling SSL to ensure your submission data is securely transmitted.', 'ws-form'), 'notice-warning', false, false);
	}
?>
<h2 class="nav-tab-wrapper"> 
<?php

	// Render tabs
	foreach($options as $tab => $fields) {
?>
<a href="<?php echo esc_attr(admin_url('admin.php?page=ws-form-settings&tab=' . $tab)); ?>" class="nav-tab<?php if($tab_current == $tab) { ?> nav-tab-active<?php } ?>"><?php echo esc_html($fields['label']); ?></a>
<?php

	}
?>
</h2>

<form method="post" action="admin.php?page=ws-form-settings<?php echo ($tab_current != '') ? '&tab=' . urlencode($tab_current) : ''; ?>" novalidate="novalidate" id="wsf-settings" enctype="multipart/form-data">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="tab" value="<?php echo esc_attr($tab_current); ?>" />
<input type="hidden" name="action" value="wsf-settings-update" />
<input type="hidden" name="action_mode" id="wsf_action_mode" value="" />
<input type="hidden" name="action_license_action_id" id="wsf_action_license_action_id" value="" />
<input type="hidden" name="page" value="ws-form-settings" />
<?php

	$js_on_change = '';
	$save_button = false;

	if(isset($options[$tab_current]['fields'])) {

		$fields = $options[$tab_current]['fields'];
		$save_button = $save_button || render_fields($this, $fields, $max_uploads, $max_upload_size, $js_on_change);
	}

	if(isset($options[$tab_current]['groups'])) {

		$groups = $options[$tab_current]['groups'];

		foreach($groups as $group) {

			$heading = isset($group['heading']) ? $group['heading'] : false;
			$description = isset($group['description']) ? $group['description'] : false;
			$fields = $group['fields'];
			$message = isset($group['message']) ? $group['message'] : false;

			$save_button_return = render_fields($this, $fields, $max_uploads, $max_upload_size, $js_on_change, $heading, $description, $message);
			$save_button = $save_button || $save_button_return;
		}
	}

	if($save_button) {
?>
<p><input type="submit" name="wsf_submit" id="wsf_submit" class="wsf-button wsf-button-primary" value="Save Changes"></p>
<?php
	}
?>
</form>

<script>

	(function($) {

		'use strict';

		// On load
		$(function() {

			var wsf_obj = new $.WS_Form();

			var file_frame;

			$('#wsf-settings').submit(function() {

				// mod_security fix
				$('input[type="text"]').each(function() {

					var input_string = $(this).val();
					var output_string = wsf_obj.mod_security_fix(input_string);
					$(this).val(output_string);
				});
			});

			// Set mode and submit
			$('[data-action="wsf-mode-submit"]').click(function() {

				$('#wsf_action_mode').val($(this).attr('data-mode'));
				$('#wsf-settings').submit();
			});

			// Framework detect
			$('[data-action="wsf-framework-detect"]').click(function() {

				var click_this = this;
				var for_id = '#' + $(this).attr('data-for');

				// Detect framework
				wsf_obj.framework_detect(function(framework) {

					// Set framework to that detected
					$(for_id).val(framework.type);

					// Switch loader off
					wsf_obj.loader_off();

				}, function() {

					// Set framework to default
					$(for_id).val('<?php echo esc_html(WS_FORM_DEFAULT_FRAMEWORK); ?>');

					// Show info message
					wsf_obj.message('<?php esc_html_e('Your current theme does not contain a recognized framework. Using WS Form as the form framework.'); ?>', true, 'notice-info');

					// Switch loader off
					wsf_obj.loader_off();
				});
			});

			// Max upload size
			$('[data-action="wsf-max-upload-size"]').click(function() {

				var for_id = $(this).attr('data-for');
				$('#' + for_id).val(<?php echo esc_html($max_upload_size); ?>);
			});

			// Max uploads
			$('[data-action="wsf-max-uploads"]').click(function() {

				var for_id = $(this).attr('data-for');
				$('#' + for_id).val(<?php echo esc_html($max_uploads); ?>);
			});

			// Image selector
			$('[data-action="wsf-image"]').click(function(e) {

				var for_id = $(this).attr('data-for');

				// If the media frame already exists, reopen it.
				if(file_frame) {

					// Open frame
					file_frame.open();
					return;
				}

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({

					title: 'Select image',
					button: {
						text: 'Use this image',
					},
					multiple: false
				});

				// When an image is selected, run a callback.
				file_frame.on('select', function() {

					// We set multiple to false so only get one image from the uploader
					var attachment = file_frame.state().get('selection').first().toJSON();

					// Sets the image ID
					var image_id = attachment.id;
					var image_id_obj = $('#' + for_id);
					image_id_obj.val(image_id);

					// Get thumbnail size
					if(typeof(attachment.sizes.<?php echo esc_html(WS_FORM_SETTINGS_IMAGE_PREVIEW_SIZE); ?>) !== 'undefined') {
						var image_size = attachment.sizes.<?php echo esc_html(WS_FORM_SETTINGS_IMAGE_PREVIEW_SIZE); ?>;
					} else {
						var image_size = attachment.sizes.thumbnail;	
					}
					var image_width = image_size.width;
					var image_height = image_size.height;
					var image_url = image_size.url;

					// Set the preview
					var image_obj = $('#' + for_id + '_preview_image');
					if(image_obj.length == 0) {

						$('<div id="' + for_id + '_preview" class="wsf-settings-image-preview"><img id="' + for_id + '_preview_image" src="' + image_url + '" width="' + image_width + '" height="' + image_height + '" class="attachment-<?php echo esc_html(WS_FORM_SETTINGS_IMAGE_PREVIEW_SIZE); ?> size-<?php echo esc_html(WS_FORM_SETTINGS_IMAGE_PREVIEW_SIZE); ?>" /><div data-action="wsf-image-reset" data-for="' + for_id + '"><?php WS_Form_Common::render_icon_16_svg('minus-circle'); ?></div></div>').insertAfter(image_id_obj);

							// Image selector reset
							$('[data-action="wsf-image-reset"]').click(function(e) {

								var for_id = $(this).attr('data-for');
								var image_id_obj = $('#' + for_id);
								image_id_obj.val('');
								$('#' + for_id + '_preview').remove();
							});

					} else {

						image_obj.attr('src', image_url);
						image_obj.removeAttr('srcset');
						image_obj.attr('width', image_width);
						image_obj.attr('height', image_height);
					}
				});

				// Finally, open the modal
				file_frame.open();
			});

			// Image selector reset
			$('[data-action="wsf-image-reset"]').on('click', function(e) {

				var for_id = $(this).attr('data-for');
				var image_id_obj = $('#' + for_id);
				image_id_obj.val('');
				$('#' + for_id + '_preview').remove();
			});

			$('[wsf-file]').on('change', function() {

				var id = $(this).attr('id');
				var files = $(this)[0].files;
				var label_obj = $('label[for="' + id + '"][data-wsf-file-label]');

				if(files.length == 0) {

					// Set back to field label
					var label = '';

				} else {

					// Build label of filenames
					var filenames = [];
					for(var file_index = 0; file_index < files.length; file_index++) {

						filenames.push(files[file_index].name);
					}
					var label = filenames.join(', ');
				}

				label_obj.html(label);
			});
<?php

	if($js_on_change != '') {

		echo $js_on_change;	// phpcs:ignore
	}
?>
		});

	})(jQuery);

</script>

</div>
<?php

	function render_fields($wsform, $fields, $max_uploads, $max_upload_size, &$js_on_change, $heading = false, $description = false, $message = false) {

		global $image_preview_size;

		// Heading
		if($heading !== false) {
?>
<h2 class="title"><?php echo esc_html($heading); ?></h2>
<?php
		}

		// Message
		if($message !== false) {

			echo sprintf('<p><em>%s</em></p>', $message);	// phpcs:ignore
		}

		// Description
		if($description !== false) {
?>
<p><?php echo esc_html($description); ?></p>
<?php
		}
?>
<table class="form-table"><tbody>
<?php
		$save_button = false;

		foreach($fields as $field => $config) {

			// Hidden values
			if($config['type'] == 'hidden') { continue; }

			// Condition
			if(isset($config['condition'])) {

				$condition_result = true;
				foreach($config['condition'] as $condition_field => $condition_value) {

					$condition_value_check = WS_Form_Common::option_get($condition_field);
					if($condition_value_check != $condition_value) {

						$condition_result = false;
						break;
					}
				}
				if(!$condition_result) { continue; }
			}

			// Minimum
			$minimum = (isset($config['minimum'])) ? intval($config['minimum']) : false;

			// Maximum
			if(isset($config['maximum'])) {

				$maximum = $config['maximum'];

				switch($maximum) {

					case '#max_upload_size' : $maximum = $max_upload_size; break;
					case '#max_uploads' : $maximum = $max_uploads; break;
				}

			} else {

				$maximum = false;
			}
?>
<tr>
<?php
			if($config['label'] !== false) {
?>
<th scope="row"><label class="wsf-label" for="wsf_<?php echo esc_attr($field); ?>"><?php esc_html_e($config['label']); ?></label></th>
<?php
			}
?>
<td<?php if($config['label'] === false) { ?> colspan="2"<?php } ?>><?php

			$default = isset($config['default']) ? $config['default'] : false;
			$value = WS_Form_Common::option_get($field, $default, true);
			if(!is_array($value)) { $value = esc_html($value); }

			// Attributes
			$attributes = array();

			// Name
			$multiple = isset($config['multiple']) ? $config['multiple'] : false;
			$attributes[] = sprintf('name="%s%s"', esc_attr($field), $multiple ? '[]' : '');

			// ID
			$attributes[] = sprintf('id="wsf_%s"', esc_attr($field));

			// Type
			$attributes[] = sprintf('type="%s"', esc_attr($config['type']));

			// Size
			$size = isset($config['size']) ? intval($config['size']) : false;
			if($size !== false) { $attributes[] = sprintf('size="%u"', $size); }

			// Minimum
			if($minimum !== false) { $attributes[] = sprintf('min="%u"', $minimum); }

			// Maximum
			if($maximum !== false) { $attributes[] = sprintf('max="%u"', $maximum); }

			// Disabled
			$disabled = isset($config['disabled']) ? $config['disabled'] : false;
			$attributes[] = $disabled ? ' disabled' : '';

			$attributes = ((count($attributes) > 0) ? ' ' : '') . implode(' ', $attributes);

			switch($config['type']) {

				// Static value
				case 'static' :

					switch($field) {

						// Version
						case 'version' : echo esc_html(WS_FORM_VERSION); break;

						// License description
						case 'license_status' : 

							$plugin_licensing = new WS_Form_Licensing(WS_FORM_LICENSE_ITEM_ID);
							echo esc_html($plugin_licensing->license_status());
							break;

						// System
						case 'system' :

							echo wp_kses(WS_Form_Common::get_system_report_html(), wp_kses_allowed_html('post'));
							break;

						// Encryption
						case 'encryption_status' :

							echo wp_kses(WS_Form_Common::get_encryption_status_html(), wp_kses_allowed_html('post'));
							break;
						default :

							// Other
							$value = apply_filters('wsf_settings_static', $value, $field);
							echo wp_kses($value, wp_kses_allowed_html('post'));
					}
					break;

				// Text field
				case 'text' :
?>
<input class="wsf-field" value="<?php echo esc_attr($value); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
					$save_button = true;
					break;

				// Email field
				case 'email' :
?>
<input class="wsf-field" value="<?php echo esc_attr($value); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
					$save_button = true;
					break;

				// Url field
				case 'url' :
?>
<input class="wsf-field" value="<?php echo esc_attr($value); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
					$save_button = true;
					break;

				// File field
				case 'file' :
?>
<input class="wsf-field" wsf-file<?php

	echo $attributes;	// phpcs:ignore

?> />
<label for="<?php echo esc_attr(sprintf('wsf_%s', $field)); ?>" class="wsf-label" data-wsf-file-label>&nbsp;</label>

<?php
					$save_button = true;
					break;

				// Password field
				case 'password' :
?>
<input class="wsf-field" value="<?php echo esc_attr($value); ?>" autocomplete="new-password"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
					$save_button = true;
					break;

				// Key field
				case 'key' :
?>
<input class="wsf-field" value="<?php echo esc_attr($value); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
					$save_button = true;
					break;

				// Number field
				case 'number' :
?>
<input class="wsf-field" value="<?php echo esc_attr($value); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
					$save_button = true;
					break;

				// Color field
				case 'color' :
?>
<input value="<?php echo esc_attr($value); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
					$save_button = true;
					break;

				// Checkbox field
				case 'checkbox' :
?>
<input class="wsf-field wsf-switch" value="1"<?php if($value) { ?> checked="checked"<?php } ?><?php

	echo $attributes;	// phpcs:ignore

?> />
<label for="wsf_<?php echo esc_attr($field); ?>" class="wsf-label">&nbsp;</label>
<?php
					$save_button = true;
					break;

				// Selectbox field
				case 'select' :

?>
<select class="wsf-field" name="<?php echo esc_attr($field); ?><?php if($multiple) { ?>[]<?php } ?>" id="wsf_<?php echo esc_attr($field); ?>"<?php if(($size !== false) && ($size > 1)) { ?> size="<?php echo esc_attr($size); ?>"<?php } ?><?php if($multiple) { ?> multiple="multiple"<?php } ?><?php

	echo $attributes;	// phpcs:ignore

?>>
<?php
					// Render options
					$options = $config['options'];
					$option_selected = is_array($value) ? $value : array($value);
					foreach($options as $option_value => $option_array) {

						$option_text = $option_array['text'];

?><option value="<?php echo esc_attr($option_value); ?>"<?php if(in_array($option_value, $option_selected)) { ?> selected<?php } ?>><?php echo esc_html($option_text); ?></option>
<?php
					}
?>
</select>
<?php
					$save_button = true;
					break;

				// Selectbox field (Number)
				case 'select_number' :
?>
<select class="wsf-field" name="<?php echo esc_attr($field); ?>" id="wsf_<?php echo esc_attr($field); ?>"<?php

	echo $attributes;	// phpcs:ignore

?>>
<?php
					// Render options
					$minimum = isset($config['minimum']) ? $config['minimum'] : 1;
					$maximum = isset($config['maximum']) ? $config['maximum'] : 100;
					for($option_value = $minimum; $option_value <= $maximum; $option_value++) {

?><option value="<?php echo esc_attr($option_value); ?>"<?php if($option_value == $value) { ?> selected<?php } ?>><?php echo esc_html($option_value); ?></option>
<?php
					}
?>
</select>
<?php
					$save_button = true;
					break;

				// Image
				case 'image' :
?>
<input name="<?php echo esc_attr($field); ?>" type="hidden" id="wsf_<?php echo esc_attr($field); ?>" value="<?php echo esc_attr($value); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
					// Get the image ID
					$image_id = intval($value);
					if($image_id == 0) { break; }

					// Show preview image
					$image = wp_get_attachment_image($image_id, WS_FORM_SETTINGS_IMAGE_PREVIEW_SIZE, false, array('id' => 'wsf_' . $field . '_preview_image'));
					if($image) {
?>
<div id="wsf_<?php echo esc_attr($field); ?>_preview" class="wsf-settings-image-preview"><?php

	echo $image;	// phpcs:ignore

?><div data-action="wsf-image-reset" data-for="wsf_<?php echo esc_attr($field); ?>"><?php WS_Form_Common::render_icon_16_svg('minus-circle'); ?></div></div>
<?php
					}

					$save_button = true;
					break;

				// Image size
				case 'image_size' :
?>
<select class="wsf-field" name="<?php echo esc_attr($field); ?>" id="wsf_<?php echo esc_attr($field); ?>"<?php

	echo $attributes;	// phpcs:ignore

?>>
<?php
					// Render image sizes
					$image_sizes = get_intermediate_image_sizes();
					$image_sizes[] = 'full';
					foreach($image_sizes as $image_size) {

?><option value="<?php echo esc_attr($image_size); ?>"<?php if($image_size == $value) { ?> selected<?php } ?>><?php echo esc_html($image_size); ?></option>
<?php
					}
?>
</select>
<?php
					$save_button = true;
					break;
			}

			// Buttons
			if(isset($config['button'])) {

				$button = $config['button'];

				switch($button) {

					case 'wsf-license' :

						$license_activated = WS_Form_Common::option_get('license_activated', false);
						if($license_activated) {
?>
<input class="wsf-button" type="button" data-action="wsf-mode-submit" data-mode="deactivate" value="<?php esc_attr_e('Deactivate', 'ws-form'); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
						} else {
?>
<input class="wsf-button" type="button" data-action="wsf-mode-submit" data-mode="activate" value="<?php esc_attr_e('Activate', 'ws-form'); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
						}

						break;

					case 'wsf-framework-detect' :
?>
<input class="wsf-button" type="button" data-action="wsf-framework-detect" data-for="wsf_<?php echo esc_attr($field); ?>" value="<?php esc_attr_e('Detect', 'ws-form'); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
						break;

					case 'wsf-key-generate' :
?>
<input type="button" class="wsf-button" data-action="wsf-key-generate" data-for="wsf_<?php echo esc_attr($field); ?>" value="<?php esc_attr_e('Generate', 'ws-form'); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
						break;

					case 'wsf-max-upload-size' :
?>
<input type="button" class="wsf-button" data-action="wsf-max-upload-size" data-for="wsf_<?php echo esc_attr($field); ?>" value="<?php esc_attr_e('Use php.ini value', 'ws-form'); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
						break;

					case 'wsf-max-uploads' :
?>
<input type="button" class="wsf-button" data-action="wsf-max-uploads" data-for="wsf_<?php echo esc_attr($field); ?>" value="<?php esc_attr_e('Use php.ini value', 'ws-form'); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
						break;

					case 'wsf-image' :

?>
<input type="button" class="wsf-button" data-action="wsf-image" data-for="wsf_<?php echo esc_attr($field); ?>" value="<?php esc_attr_e('Select image...', 'ws-form'); ?>"<?php

	echo $attributes;	// phpcs:ignore

?> />
<?php
						break;

					default :

						// Other
						$value = apply_filters('wsf_settings_button', '', $field, $button);
						echo $value;	// phpcs:ignore
				}
			}

			if(isset($config['help'])) {

				$help = $config['help'];
				echo '<p class="wsf-helper" id="' . esc_attr($field) . '_description">';
				echo wp_kses($help, wp_kses_allowed_html('post'));
				echo '</p>';
			}

			if(isset($config['data_change']) && $config['data_change'] == 'reload') {

				$js_on_change .= "\n			$('#wsf_$field').change(function() { $('#wsf-settings').submit(); });";
			}
?></td>
</tr>
<?php
		}
?>
</tbody></table>
<?php

		return $save_button;
	}
