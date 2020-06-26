<?php

	// Loader icon
	WS_Form_Common::loader();

	// Read settings
	$settings = array('id', 'form_id_source', 'method', 'form_id_destination', 'duplicate');
	$wsf_form_migrate = WS_Form_Common::get_query_var_nonce('wsf_form_migrate', array(), false, false, true, 'POST');
	$changed = WS_Form_Common::get_query_var_nonce('changed', false, false, false, true, 'POST');
	$change_do = true;
	foreach($settings as $setting) {

		if(isset($wsf_form_migrate[$setting]) && $change_do) {

			WS_Form_Common::option_set('migrate_' . $setting, $wsf_form_migrate[$setting]);
		}

		if(!$change_do) { WS_Form_Common::option_set('migrate_' . $setting, ''); }

		$wsf_form_migrate[$setting] = WS_Form_Common::option_get('migrate_' . $setting, false);

		if($changed === $setting) { $change_do = false; }
	}

	// Field mapping enabled?
	$show_field_mapping = (

		($wsf_form_migrate['id'] != '') &&
		($wsf_form_migrate['form_id_source'] != '') &&
		(
			($wsf_form_migrate['method'] == 'submissions') &&
			($wsf_form_migrate['form_id_destination'] != '') && 
			($wsf_form_migrate['duplicate'] != '')
		)
	);

	// Show field mappings
	if($show_field_mapping) {

		// Get destination fields
		$ws_form_form = new WS_Form_Form();
		$ws_form_form->id = $wsf_form_migrate['form_id_destination'];
	
		try {

			// Get form object
			$form_object = $ws_form_form->db_read(true, true);

 			// Get form fields
			$fields_destination = WS_Form_Common::get_fields_from_form($form_object);

		} catch (Exception $e) {

			$show_field_mapping = false;
		}
	}

	// Read wsf_mode
	$wsf_mode = WS_Form_Common::get_query_var_nonce('wsf_mode', '', false, false, true, 'POST');

	// Check for field mapping
	if($show_field_mapping) {

		// Set field mapping option key
		$field_mapping_option_key = 'migrate_field_mapping_' . $wsf_form_migrate['id'] . '_' . $wsf_form_migrate['form_id_source'] . '_' . $wsf_form_migrate['form_id_destination'];
	}

	// Loader icon
	WS_Form_Common::loader();
?>
<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?>">

<!-- Header -->
<div class="wsf-heading">
<h1 class="wp-heading-inline"><?php esc_html_e('Migrate', 'ws-form') ?></h1>
<button class="wsf-button wsf-button-small wsf-button-information" id="wsf-migrate-button" disabled data-action="wsf_migrate"><?php WS_Form_Common::render_icon_16_svg('upload'); ?> <?php esc_html_e('Start Import', 'ws-form'); ?></button>
</div>
<hr class="wp-header-end">
<!-- /Header -->
<?php

	// Review nag
	WS_Form_Common::review();
?>
<!-- Wrapper -->
<div id="wsf_migrate">
<?php

	$ws_form_migrate = new WS_Form_Migrate();

	// Get migrations
	$migrations = $ws_form_migrate->get_migrations();
?>
<form action="admin.php?page=ws-form-migrate" id="wsf-migrate-form" method="post">

<table class="form-table"><tbody>

<tr>
<th scope="row"><label for="wsf_migrate_id"><?php esc_html_e('Import From:', 'ws-form'); ?></label></th>
<td><select id="wsf_migrate_id" name="wsf_form_migrate[id]" data-action="wsf-submit" data-id="id">
<option value=""><?php esc_html_e('Select plugin...', 'ws-form'); ?></option>
<?php

	foreach($migrations as $migration) {

?><option value="<?php echo esc_attr($migration['id']); ?>"<?php

		if(($wsf_form_migrate['id'] === false) && $migration['select']) { $wsf_form_migrate['id'] = $migration['id']; }
		if($wsf_form_migrate['id'] == $migration['id']) { ?> selected="selected"<?php }
		if(!$migration['data']) { ?> disabled="disabled"<?php }

?>><?php echo esc_html($migration['label'] . ' (' . $migration['version'] . ')'); ?><?php

		if($migration['active']) { ?> (Active)<?php }

?></option>
<?php

	}
?>
</select></td>
</tr>
<?php

	// Select migrate form
	if($wsf_form_migrate['id'] != '') {

		// Migrate name
		$migrate_name = $migrations[$wsf_form_migrate['id']]['label'];

		// Get forms
		$ws_form_migrate->id = $wsf_form_migrate['id'];
		$get_data_return = $ws_form_migrate->get_data('form');
		$forms = $get_data_return['data'];
?>
<tr>
<th scope="row"><label for="wsf-migrate-form-id-source"><?php echo esc_html(sprintf(__('%s Form:', 'ws-form'), $migrate_name)); ?></label></th>
<td><select id="wsf-migrate-form-id-source" name="wsf_form_migrate[form_id_source]" data-action="wsf-submit" data-id="form_id_source">
<option value=""><?php esc_html_e('Select form...', 'ws-form'); ?></option>
<?php

		foreach($forms as $form) {

?><option value="<?php echo esc_attr($form['id']); ?>"<?php if($wsf_form_migrate['form_id_source'] == $form['id']) { ?> selected="selected"<?php } ?>><?php echo esc_html($form['label']) . ' (' . esc_html__('ID', 'ws-form') . ': ' . esc_html($form['id']) . ')'; ?></option>
<?php

		}
?>
</select></td>
</tr>
<?php
	}

	// Select method
	if(
		($wsf_form_migrate['id'] != '') &&
		($wsf_form_migrate['form_id_source'] != '')
	) {

		// Get submission count
		$record_count = $ws_form_migrate->get_data('submission', $wsf_form_migrate['form_id_source'], false, false, true);
?>
<tr>
<th scope="row"><label for="wsf-migrate-method"><?php esc_html_e('Data To Import:', 'ws-form'); ?></label></th>
<td><select id="wsf-migrate-method" name="wsf_form_migrate[method]" data-action="wsf-submit" data-id="method">
<option value=""><?php esc_html_e('Select...', 'ws-form'); ?></option>
<option value="form"<?php if($wsf_form_migrate['method'] == 'form') { ?> selected="selected"<?php } ?>>Form</option>
<option value="submissions"<?php if($wsf_form_migrate['method'] == 'submissions') { ?> selected="selected"<?php } ?>><?php esc_html_e('Submissions', 'ws-form'); ?> (<?php echo esc_html(sprintf(_n('%u record', '%u records', $record_count, 'ws-form'), $record_count)); ?>)</option>
</select></td>
</tr>
<?php
	}

	// Select WS Form
	if(
		($wsf_form_migrate['id'] != '') &&
		($wsf_form_migrate['form_id_source'] != '') &&
		($wsf_form_migrate['method'] == 'submissions')
	) {

		// Select form
		$ws_form_form = New WS_Form_Form();
		$forms = $ws_form_form->db_read_all('', "NOT (status = 'trash')", 'label, id', '', '', false);

		if($forms) {
?>
<tr>
<th scope="row"><label for="wsf-migrate-form-id-destination"><?php esc_html_e('Import To WS Form:', 'ws-form'); ?></label></th>
<td><select id="wsf-migrate-form-id-destination" name="wsf_form_migrate[form_id_destination]" data-action="wsf-submit" data-id="form_id_destination">
<option value=""><?php esc_html_e('Select form...', 'ws-form'); ?></option>
<?php
			foreach($forms as $form) {

?><option value="<?php echo esc_attr($form['id']); ?>"<?php if($form['id'] == $wsf_form_migrate['form_id_destination']) { ?> selected="selected"<?php } ?>><?php echo esc_html($form['label']) . ' (' . esc_html__('ID', 'ws-form') . ': ' . esc_html($form['id']) . ')'; ?></option>
<?php
			}
?>
</select></td>
</tr>
<?php
		} else {

			echo '<p>' . esc_html__('You first need to migrate or create a form in WS Form to migrate submission data into.', 'ws-form') . '</p>';
		}
	}

	// Select duplicate handling
	if(
		($wsf_form_migrate['id'] != '') &&
		($wsf_form_migrate['form_id_source'] != '') &&
		($wsf_form_migrate['method'] == 'submissions') &&
		($wsf_form_migrate['form_id_destination'] != '')
	) {
?>
<tr>
<th scope="row"><label for="wsf-migrate-duplicate"><?php esc_html_e('Duplicates:', 'ws-form'); ?></label></th>
<td><select id="wsf-migrate-duplicate" name="wsf_form_migrate[duplicate]" data-action="wsf-submit" data-id="duplicate">
<option value=""><?php esc_html_e('Select...', 'ws-form'); ?></option>
<option value="update"<?php if($wsf_form_migrate['duplicate'] == 'update') { ?> selected="selected"<?php } ?>>Update duplicate</option>
<option value="create"<?php if($wsf_form_migrate['duplicate'] == 'create') { ?> selected="selected"<?php } ?>>Add as new duplicate</option>
<option value="ignore"<?php if($wsf_form_migrate['duplicate'] == 'ignore') { ?> selected="selected"<?php } ?>>Ignore and do not import</option>
</select></td>
</tr>
<?php
	}

	// Show field mappings
	if($show_field_mapping) {
?>
<tr>
<th scope="row"><label><?php esc_html_e('Field Mapping', 'ws-form'); ?></label></th>
<td><?php

		// Get saved field mapping
		$field_mapping = WS_Form_Common::option_get($field_mapping_option_key, array());

		// Get source fields
		$get_data_return = $ws_form_migrate->get_data('field', $wsf_form_migrate['form_id_source']);
		$fields_source = $get_data_return['data'];
?>
<div class="wsf-table-migrate-outer">
<table id="wsf_field_mapping" class="wsf-table-migrate"><thead>

<tr>
<th><?php echo esc_html(sprintf(__('%s Field', 'ws-form'), $migrate_name)); ?></th>
<th><?php esc_html_e('WS Form Field', 'ws-form'); ?></th>
<th data-icon></th>
</tr>

</thead>

<tbody>
<?php
		// Output existing field mappings
		foreach($field_mapping as $field_map) {

			$field_id_source = $field_map['s'];
			$field_id_destination = $field_map['d'];

			field_mapping_row($fields_source, $fields_destination, $field_id_source, $field_id_destination);
		}

		field_mapping_row($fields_source, $fields_destination, false, false, true);
?>
</tbody></table>

<div data-action="wsf-field-mapping-add" title="<?php esc_attr_e('Add Row', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('plus-circle'); ?></div>

</td>
</tr>
<?php
	}
?>
</tbody></table>

<input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" id="wsf_changed" name="changed" value="" />
<input type="hidden" id="wsf_action" name="action" value="" />
<input type="hidden" name="page" value="ws-form-migrate" />

</form>

<div id="wsf-migrate-status">

<div id="wsf-migrate-status-form">
<label class="wsf-label"><?php esc_html_e('Importing form...', 'ws-form'); ?></label>
<p class="wsf-helper"><?php esc_html_e('Please wait...', 'ws-form'); ?></p>
</div>
</div>

<div id="wsf-migrate-status-submissions">
<label class="wsf-label"><?php esc_html_e('Importing submissions...', 'ws-form'); ?></label>
<progress id="wsf-migrate-status-progress" class="wsf-progress wsf-progress-large" value="0" max="100"></progress>
<p class="wsf-helper"><?php esc_html_e('Total records processed', 'ws-form'); ?>: <span id="wsf-migrate-status-description"></span></p>
</div>

</div>
<!-- /Wrapper -->

<script>

	'use strict';

	(function($) {

		// On load
		$(function() {

			var wsf_obj = new $.WS_Form();

			// Manually inject language strings (Avoids having to call the full config)
			$.WS_Form.settings_form = [];
			$.WS_Form.settings_form.language = [];
			$.WS_Form.settings_form.language['migrate_status_description_default'] = '<?php esc_html_e('Please wait...', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['migrate_success_submissions'] = '<?php esc_html_e('Successfully imported submissions:', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['migrate_success_form'] = '<?php esc_html_e('Successfully imported form:', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['migrate_created'] = '<?php esc_html_e('%s created.', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['migrate_updated'] = '<?php esc_html_e('%s updated.', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['migrate_ignored'] = '<?php esc_html_e('%s ignored.', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['migrate_view_submissions'] = '<?php esc_html_e('View Submissions', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['migrate_edit_form'] = '<?php esc_html_e('Edit Form', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['migrate_import_submissions'] = '<?php esc_html_e('Import Submissions', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['migrate_error'] = '<?php esc_html_e('An error occurred during the import.', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['error_server'] = '<?php esc_html_e('500 Server error response from server.', 'ws-form'); ?>';

			$.WS_Form.settings_form.language['dismiss'] = '<?php esc_html_e('Dismiss', 'ws-form'); ?>';

			wsf_import_button();

			// Select changes
			$('[data-action="wsf-submit"]').change(function(e) {

				$('#wsf_changed').val($(this).attr('data-id'));

				$('#wsf-migrate-button').attr('disabled', '');

				wsf_obj.loader_on();

				$('#wsf-migrate-form').submit();
			});

			// Delete field mapping row
			$('[data-action="wsf-field-mapping-delete"] > svg').click(function(e) {

				e.preventDefault();

				wsf_field_mapping_remove($(this));
			});

			// Add field mapping row
			$('[data-action="wsf-field-mapping-add"] > svg').click(function(e) {

				e.preventDefault();

				var blank_row = $('#wsf-field-mapping-blank').clone().removeAttr('id');
				$('#wsf_field_mapping tbody').append(blank_row).each(function() {

					$('[data-action="wsf-field-mapping-delete"]', $(this)).click(function(e) {

						e.preventDefault();

						wsf_field_mapping_remove($(this));
					});

					$('[name^="wsf_field_mapping"]', $(this)).change(function() {

						wsf_field_mapping_save();
					});

					wsf_import_button();
				});
			});

			// Start migrate
			$('[data-action="wsf_migrate"]').click(function(e) {

				if(confirm('<?php esc_html_e('Are you sure you want to start the import?', 'ws-form'); ?>')) {

					e.preventDefault();

					wsf_obj.loader_on();

					switch($('[name="wsf_form_migrate[method]"]').val()) {

						case 'form' :

							wsf_migrate_form_import();
							break;

						case 'submissions' :

							wsf_migrate_submission_import();
							break;
					}
				}
			});

			// Migrate field mapping update
			$('[name^="wsf_field_mapping"]').change(function() {

				wsf_field_mapping_save();
			});

			// Delete row
			function wsf_field_mapping_remove(obj) {

				obj.closest('tr').remove();

				wsf_field_mapping_save();
			}

			// Determine whether the import button can be clicked
			function wsf_import_button() {

				// Check mappings
				var field_mapping_valid = false;
				var field_mapping_source_ids = $('[name="wsf_field_mapping[source][]"]').map(function() {return $(this).val();}).get();
				var field_mapping_destination_ids = $('[name="wsf_field_mapping[destination][]"]').map(function() {return $(this).val();}).get();

				for(var field_mapping_index = 0; field_mapping_index < field_mapping_source_ids.length; field_mapping_index++) {

					var field_mapping_source_id = field_mapping_source_ids[field_mapping_index];			
					var field_mapping_destination_id = field_mapping_destination_ids[field_mapping_index];			

					if((field_mapping_source_id != 0) && (field_mapping_destination_id != 0)) { field_mapping_valid = true; break; }
				}

				var button_enable = (<?php echo $show_field_mapping ? 'true' : 'false'; ?> && field_mapping_valid) || <?php echo ($wsf_form_migrate['method'] == 'form') ? 'true' : 'false'; ?>;

				var button_obj = $('#wsf-migrate-button');

				if(button_enable) {

					button_obj.removeAttr('disabled');

				} else {

					button_obj.attr('disabled', '');
				}
			}

			function wsf_field_mapping_save() {

				wsf_import_button();

				wsf_obj.loader_on();

				// Build form data
				var form_data = {

					'wsf_form_migrate[id]' : $('[name="wsf_form_migrate[id]"]').val(),
					'wsf_form_migrate[form_id_source]' : $('[name="wsf_form_migrate[form_id_source]"]').val(),
					'wsf_form_migrate[form_id_destination]' : $('[name="wsf_form_migrate[form_id_destination]"]').val(),
					'wsf_field_mapping[source][]' : $('[name="wsf_field_mapping[source][]"]').map(function() {return $(this).val();}).get(),
					'wsf_field_mapping[destination][]' : $('[name="wsf_field_mapping[destination][]"]').map(function() {return $(this).val();}).get()
				};

				// Call submission import API endpoint
				wsf_obj.api_call('migrate/field_mapping', 'POST', form_data, function(response) {

					wsf_obj.loader_off();

				}, function() {

					wsf_obj.loader_off();
				});
			}

			function wsf_migrate_form_import() {

				$('#wsf-migrate-button').attr('disabled', '');
				$('#wsf-migrate-form').hide();
				$('#wsf-migrate-status-form').show();
				$('#wsf-migrate-status').show();

				// Build form data
				var form_data = {
<?php
	foreach($settings as $setting) {
?>
					'wsf_form_migrate[<?php echo esc_html($setting); ?>]' : $('[name="wsf_form_migrate[<?php echo esc_html($setting); ?>]"]').val(),
<?php
	}
?>
					'wsf_field_mapping[source][]' : $('[name="wsf_field_mapping[source][]"]').map(function() {return $(this).val();}).get()
				};

				// Call form import API endpoint
				wsf_obj.api_call('migrate/form/import', 'POST', form_data, function(response) {

					// Redirect to new form
					if(typeof(response.form_id) !== 'undefined') {

						// Build success message
						var success_message_array = [];
						success_message_array.push($.WS_Form.this.language('migrate_success_form'));
						success_message_array.push($('#wsf-migrate-form-id-source option:selected').text()  + '.');
						success_message_array.push('<a href="admin.php?page=ws-form-edit&id=' + response.form_id + '" target="_blank">' + $.WS_Form.this.language('migrate_edit_form') + '</a>');
						success_message_array.push('<a href="admin.php?page=ws-form-migrate&wsf_form_migrate[method]=submissions&&wsf_form_migrate[duplicate]=update">' + $.WS_Form.this.language('migrate_import_submissions') + '</a>');
						var success_message = success_message_array.join(' ');

						// Process complete
						$.WS_Form.this.message(success_message, true, 'notice-success'); 
						$('#wsf-migrate-button').removeAttr('disabled');
						$('#wsf-migrate-form').show();
						$('#wsf-migrate-status').hide();
						$('#wsf-migrate-status-form').hide();
						wsf_obj.loader_off();

					} else {

						// Error
						$.WS_Form.this.message($.WS_Form.this.language('migrate_error'), true, 'notice-error'); 
						wsf_obj.loader_off();
					}

				}, function() {

					// Error
					$.WS_Form.this.message($.WS_Form.this.language('migrate_error'), true, 'notice-error'); 
					wsf_obj.loader_off();
				});
			}

			function wsf_migrate_submission_import() {

				$('#wsf-migrate-status-progress').val(0);
				$('#wsf-migrate-status-description').html($.WS_Form.this.language('migrate_status_description_default'));
				$('#wsf-migrate-button').attr('disabled', '');
				$('#wsf-migrate-form').hide();
				$('#wsf-migrate-status-submissions').show();
				$('#wsf-migrate-status').show();

				wsf_migrate_submission_import_process(0,0,0,0);
			}

			function wsf_migrate_submission_import_process(offset, created, updated, ignored) {

				// Build form data
				var form_data = {
<?php
	foreach($settings as $setting) {
?>
					'wsf_form_migrate[<?php echo esc_html($setting); ?>]' : $('[name="wsf_form_migrate[<?php echo esc_html($setting); ?>]"]').val(),
<?php
	}
?>
					'wsf_field_mapping[source][]' : $('[name="wsf_field_mapping[source][]"]').map(function() {return $(this).val();}).get(),
					'wsf_field_mapping[destination][]' : $('[name="wsf_field_mapping[destination][]"]').map(function() {return $(this).val();}).get(),
					'offset' : offset
				};

				// Call submission import API endpoint
				wsf_obj.api_call('migrate/submission/import', 'POST', form_data, function(response) {

					// Read response data
					var remaining = (typeof(response.remaining) !== 'undefined') ? response.remaining : false;
					var progress = (typeof(response.progress) !== 'undefined') ? response.progress : false;
					var offset = (typeof(response.offset) !== 'undefined') ? response.offset : 0;
					var total = (typeof(response.total) !== 'undefined') ? response.total : 0;

					created += (typeof(response.created) !== 'undefined') ? response.created : 0;
					updated += (typeof(response.updated) !== 'undefined') ? response.updated : 0;
					ignored += (typeof(response.ignored) !== 'undefined') ? response.ignored : 0;

					// Show status
					$('#wsf-migrate-status-progress').val(progress);
					$('#wsf-migrate-status-description').html(offset + ' of ' + total);

					if(remaining) {

						// Make next AJAX call
						wsf_migrate_submission_import_process(offset, created, updated, ignored);

					} else {

						// Build success message
						var success_message_array = [];
						success_message_array.push($.WS_Form.this.language('migrate_success_submissions'));
						success_message_array.push($('#wsf-migrate-form-id-source option:selected').text()  + '.');
						if(created > 0) { success_message_array.push($.WS_Form.this.language('migrate_created', created)); }
						if(updated > 0) { success_message_array.push($.WS_Form.this.language('migrate_updated', updated)); }
						if(ignored > 0) { success_message_array.push($.WS_Form.this.language('migrate_ignored', ignored)); }
						success_message_array.push('<a href="admin.php?page=ws-form-submit&id=<?php echo esc_attr($wsf_form_migrate['form_id_destination']); ?>" target="_blank">' + $.WS_Form.this.language('migrate_view_submissions') + '</a>');
						var success_message = success_message_array.join(' ');

						// Process complete
						$.WS_Form.this.message(success_message, true, 'notice-success'); 
						$('#wsf-migrate-button').removeAttr('disabled');
						$('#wsf-migrate-form').show();
						$('#wsf-migrate-status').hide();
						$('#wsf-migrate-status-submissions').hide();
						wsf_obj.loader_off();
					}

				}, function() {

					// Error
					$.WS_Form.this.message($.WS_Form.this.language('migrate_error'), true, 'notice-error'); 
					wsf_obj.loader_off();
				});
			}
		});

	})(jQuery);

</script>
<?php

	function field_mapping_row($fields_source, $fields_destination, $field_id_source, $field_id_destination, $blank = false) {
?>
<tr<?php if($blank) { ?> id="wsf-field-mapping-blank"<?php } ?>>
<td>

<select name="wsf_field_mapping[source][]" class="wsf-field wsf-field-small">
<option value="0"><?php esc_html_e('Select...', 'ws-form'); ?></option>
<?php
	
		foreach($fields_source as $field_id => $field_data) {

?><option value="<?php echo esc_attr($field_id); ?>"<?php if(($field_id_source !== false) && ($field_id_source == $field_id)) { ?> selected="selected"<?php } ?>><?php echo esc_html($field_data['label']); ?> (ID: <?php echo esc_html($field_id); ?>)</option>
<?php
		}
?>
</select>

</td>
<td>

<select name="wsf_field_mapping[destination][]" class="wsf-field wsf-field-small">
<option value="0"><?php esc_html_e('Select...', 'ws-form'); ?></option>
<?php
	
		foreach($fields_destination as $field) {

?><option value="<?php echo esc_attr($field->id); ?>"<?php if(($field_id_destination !== false) && ($field_id_destination == $field->id)) { ?> selected="selected"<?php } ?>><?php echo esc_html($field->label); ?> (<?php echo esc_html($field->id); ?>)</option>
<?php
		}
?>
</select>

</td>
<td data-icon><div data-action="wsf-field-mapping-delete" title="<?php esc_attr_e('Delete', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('minus-circle'); ?></div></td>
</tr>
<?php
	}
