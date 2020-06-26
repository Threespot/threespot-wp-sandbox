<?php

	// Get ID of form (0 = New)
	$form_id = intval(WS_Form_Common::get_query_var('id', 0));

	// Loader icon
	WS_Form_Common::loader();
?>
<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?>">

<!-- Header -->
<div class="wsf-loading-hidden">
<div class="wsf-heading">
<h1 class="wp-heading-inline"><?php esc_html_e('Edit Form', 'ws-form') ?></h1>

<!-- Form actions -->
<?php

	// Publish
	if(WS_Form_Common::can_user('publish_form')) {
?>
<button data-action="wsf-publish" class="wsf-button wsf-button-small wsf-button-information" disabled><?php WS_Form_Common::render_icon_16_svg('publish'); ?> <?php esc_html_e('Publish', 'ws-form'); ?></button>
<?php
	}

	// Preview
?>
<a data-action="wsf-preview" class="wsf-button wsf-button-small" href="<?php echo esc_attr(WS_Form_Common::get_preview_url($form_id)); ?>" target="wsf-preview-<?php echo esc_attr($form_id); ?>"><?php WS_Form_Common::render_icon_16_svg('visible'); ?> <?php esc_html_e('Preview', 'ws-form'); ?></a>
<?php

	// Submissions
	if(WS_Form_Common::can_user('read_submission')) {
?>
<a data-action="wsf-submission" class="wsf-button wsf-button-small" href="<?php echo esc_attr(admin_url('admin.php?page=ws-form-submit&id=' . $form_id)); ?>"><?php WS_Form_Common::render_icon_16_svg('table'); ?> <?php esc_html_e('Submissions', 'ws-form'); ?></a>
<?php
	}

	// Hook for additional buttons
	do_action('wsf_form_edit_nav_left');
?>
<ul class="wsf-settings">
<li data-action="wsf-undo" title="<?php esc_attr_e('Undo', 'ws-form'); ?>" class="wsf-undo-inactive"><?php WS_Form_Common::render_icon_16_svg('undo'); ?></li>
<li data-action="wsf-redo" title="<?php esc_attr_e('Redo', 'ws-form'); ?>" class="wsf-redo-inactive"><?php WS_Form_Common::render_icon_16_svg('redo'); ?></li>
<?php

	// Upload
	if(WS_Form_Common::can_user('import_form')) {
?>
<li data-action="wsf-form-upload" title="<?php esc_attr_e('Import', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('upload'); ?></li>
<?php
	}

	// Download
	if(WS_Form_Common::can_user('export_form')) {
?>
<li data-action="wsf-form-download" title="<?php esc_attr_e('Export', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('download'); ?></li>
<?php
	}
?>
</ul>
<?php

	// Upload
	if(WS_Form_Common::can_user('import_form')) {
?>
<input type="file" class="wsf-file-upload" id="wsf-form-upload-file" accept=".json"/>
<?php
	}
?>
</div>
</div>
<hr class="wp-header-end">
<!-- /Header -->
<?php

	// Review nag
	WS_Form_Common::review();
?>
<!-- Wrapper -->
<div id="poststuff" class="wsf-loading-hidden"><div id="post-body" class="metabox-holder columns-2"><div id="post-body-content" style="position: relative;">

<!-- Title -->
<div id="titlediv">
<div id="titlewrap">

<label class="screen-reader-text" id="title-prompt-text" for="title"><?php esc_html_e('Form Name', 'ws-form'); ?></label>
<input type="text" id="title" class="wsf-field" data-action="wsf-form-label" name="form_label" size="30" value="" spellcheck="true" autocomplete="off" />

</div>
</div>
<!-- /Title -->

<!-- Left Column -->
<div id="postbox-container-2" class="postbox-container">

<!-- Form -->
<div id="wsf-form" class="wsf-form wsf-form-canvas"></div>
<!-- /Form -->

<!-- Breakpoints -->
<div id="wsf-breakpoints"></div>
<!-- /Breakpoints -->

</div>
<!-- /Left Column -->

<!-- Sidebars -->
<div id="wsf-sidebars"></div>
<!-- /Sidebars -->

</div></div></div>
<!-- /Wrapper -->

<!-- Popover -->
<div id="wsf-popover" class="wsf-ui-cancel"></div>
<!-- /Popover -->

<!-- Field Draggable Container (Fixes Chrome bug) -->
<div class="wsf-field-select"><div id="wsf-field-draggable"><ul></ul></div></div>
<!-- /Field Draggable Container (Fixes Chrome bug) -->

</div>
<!-- /Wrapper -->

<script>

	var wsf_obj = null;

	(function($) {

		'use strict';

		// On load
		$(function() {

			// Manually inject language strings (Avoids having to call the full config)
			$.WS_Form.settings_form = [];
			$.WS_Form.settings_form.language = [];
			$.WS_Form.settings_form.language['error_server'] = '<?php esc_html_e('500 Server error response from server.', 'ws-form'); ?>';

			// Initialize WS Form
			var wsf_obj = new $.WS_Form();
			wsf_obj.render({

				'obj' : 	'#wsf-form',
				'form_id':	<?php echo esc_attr($form_id); ?>
			});
			wsf_obj.menu_highlight();
		});

	})(jQuery);

</script>
