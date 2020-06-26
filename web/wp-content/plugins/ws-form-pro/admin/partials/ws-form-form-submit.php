<?php

	// Form - Submnissions - Admin Page
	$form_id = $this->ws_form_wp_list_table_submit_obj->form_id;

	// Loader
	WS_Form_Common::loader();
?>
<div id="poststuff">
<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?> wsf-sidebar-closed">

<!-- Header -->

<div class="wsf-heading">
<h1 class="wp-heading-inline"><?php esc_html_e('Submissions', 'ws-form'); ?></h1>
<?php

	if($form_id > 0) {

		// User capability check
		if(WS_Form_Common::can_user('edit_form')) {
?>
<a class="wsf-button wsf-button-small wsf-button-information" href="<?php echo esc_attr(admin_url('admin.php?page=ws-form-edit&id=' . $form_id)); ?>"><?php WS_Form_Common::render_icon_16_svg('edit'); ?> <?php esc_html_e('Edit', 'ws-form'); ?></a>
<?php
		}
?>
<a class="wsf-button wsf-button-small" href="<?php echo esc_attr(WS_Form_Common::get_preview_url($form_id)); ?>" target="_blank"><?php WS_Form_Common::render_icon_16_svg('visible'); ?> <?php esc_html_e('Preview', 'ws-form'); ?></a>
<?php

		if($this->ws_form_wp_list_table_submit_obj->record_count() > 0) {

			// User capability check
			if(WS_Form_Common::can_user('export_submission')) {
?>
<button data-action="wsf-export-all" class="wsf-button wsf-button-small"><?php WS_Form_Common::render_icon_16_svg('download'); ?> <?php esc_html_e('Export CSV', 'ws-form'); ?></button>
<?php
			}
		}
	}
?>
</div>
<hr class="wp-header-end">
<!-- /Header -->
<?php

	// Review nag
	WS_Form_Common::review();

	// Prepare
	$this->ws_form_wp_list_table_submit_obj->prepare_items();

	$this->ws_form_wp_list_table_submit_obj->views();
?>
<!-- Submissions Table -->
<div id="wsf-submissions">
<form method="get">
<input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="page" value="ws-form-submit">
<?php

	// Display
	$this->ws_form_wp_list_table_submit_obj->display();
?>
</form>
</div>
<!-- /Submissions Table -->

<!-- View / Edit Sidebar -->
<div id="wsf-sidebars">

	<div id="wsf-sidebar-submit" class="wsf-sidebar wsf-sidebar-closed">

		<!-- Header -->
		<div class="wsf-sidebar-header">

			<h2>
				
				<?php

					WS_Form_Common::render_icon_16_svg('table');
					esc_html_e('Submission', 'ws-form');
				?>

				<!-- Submit ID -->
				<span></span>

			</h2>

		</div>
		<!-- /Header -->

	</div>
	
</div>
<!-- /View / Edit Sidebar -->

<!-- Submissions Actions -->
<form action="<?php echo esc_attr(WS_Form_Common::get_admin_url()); ?>" id="ws-form-action-do" method="post">
<input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="page" value="ws-form-submit">
<input type="hidden" id="ws-form-action" name="action" value="">
<input type="hidden" id="ws-form-id" name="id" value="<?php echo esc_attr($form_id); ?>">
<input type="hidden" id="ws-form-submit-id" name="submit_id" value="">
<input type="hidden" id="ws-form-filter-date-form" name="date_from" value="<?php echo esc_attr(WS_Form_Common::get_query_var_nonce('date_from', '', false, false, true, 'POST')); ?>">
<input type="hidden" id="ws-form-filter-date-to" name="date_to" value="<?php echo esc_attr(WS_Form_Common::get_query_var_nonce('date_to', '', false, false, true, 'POST')); ?>">
<input type="hidden" id="ws-form-paged" name="paged" value="<?php echo esc_attr(WS_Form_Common::get_query_var_nonce('paged', '', false, false, true, 'POST')); ?>">
<input type="hidden" id="ws-form-status" name="ws-form-status" value="<?php echo esc_attr(WS_Form_Common::get_query_var_nonce('ws-form-status', '', false, false, true, 'POST')); ?>">
</form>
<!-- /Submissions Actions -->

<!-- Popover -->
<div id="wsf-popover" class="wsf-ui-cancel"></div>
<!-- /Popover -->

<script>

	(function($) {

		'use strict';

		// On load
		$(function() {

			// Manually inject language strings (Avoids having to call the full config)
			$.WS_Form.settings_form = [];
			$.WS_Form.settings_form.language = [];
			$.WS_Form.settings_form.language['starred_on'] = '<?php esc_html_e('Starred', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['starred_off'] = '<?php esc_html_e('Not Starred', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['viewed_on'] = '<?php esc_html_e('Mark as Unread', 'ws-form'); ?>';
			$.WS_Form.settings_form.language['viewed_off'] = '<?php esc_html_e('Mark as Read', 'ws-form'); ?>';

			// Initialize WS Form
			var wsf_obj = new $.WS_Form();

			wsf_obj.wp_list_table_submit(<?php echo esc_html($form_id); ?>);
		});

	})(jQuery);

</script>

</div>
</div>
