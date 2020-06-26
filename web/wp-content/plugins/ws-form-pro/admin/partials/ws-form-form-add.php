<?php

	global $wpdb;

	// Get core wizard data
	$ws_form_wizard = new WS_Form_Wizard;
	$wizard_categories = $ws_form_wizard->read_config();

	// Add action categories
	$actions = $ws_form_wizard->db_get_actions();
	if($actions !== false) {

		foreach($actions as $action) {

			$action->action_id = $action->id;
			$action->action_list_sub_modal_label = isset($action->list_sub_modal_label) ? $action->list_sub_modal_label : false;
			$wizard_categories[] = $action;
		}
	}

	// Loader icon
	WS_Form_Common::loader();
?>
<script>

	// Localize
	var ws_form_settings_language_form_add_create = '<?php esc_html_e('Create', 'ws-form'); ?>';

</script>

<div id="wsf-wrapper" class="<?php WS_Form_Common::wrapper_classes(); ?>">

<!-- Header -->
<div class="wsf-heading">
<h1 class="wp-heading-inline"><?php esc_html_e('Add New', 'ws-form'); ?></h1>
</div>
<hr class="wp-header-end">
<!-- /Header -->
<?php

	// Review nag
	WS_Form_Common::review();
?>
<!-- Wizard -->
<div id="wsf-form-add">

<p><?php esc_html_e('Start a with a blank form or select a template. You can modify any template form with your own fields.', 'ws-form'); ?></p>

<!-- Tabs - Categories -->
<ul id="wsf-form-add-tabs">
<?php

	// Loop through wizards
	foreach ($wizard_categories as $wizard_category)  {

		$action_id = isset($wizard_category->action_id) ? $wizard_category->action_id : false;

?><li><a href="<?php echo esc_attr(sprintf('#wsf_wizard_category_%s', $wizard_category->id)); ?>"><?php echo esc_html($wizard_category->label); ?><?php

		if(($action_id !== false) && ($wizard_category->reload)) {

?><span data-action="wsf-api-reload" data-action-id="<?php echo esc_html($action_id); ?>" data-method="lists_fetch" title="<?php esc_attr_e('Update', 'ws-form'); ?>"><?php WS_Form_Common::render_icon_16_svg('reload'); ?></span><?php

		}

?></a></li>
<?php

	}
?>
</ul>
<!-- Tabs - Categories -->
<?php

	// Loop through wizards
	foreach ($wizard_categories as $wizard_category)  {
?>
<!-- Tab Content: <?php echo esc_html($wizard_category->label); ?> -->
<div id="<?php echo esc_attr(sprintf('wsf_wizard_category_%s', $wizard_category->id)); ?>"<?php if(isset($wizard_category->action_id)) { ?> data-action-id="<?php echo esc_attr($wizard_category->action_id); ?>"<?php } ?><?php if(isset($wizard_category->action_list_sub_modal_label)) { ?> data-action-list-sub-modal-label="<?php echo esc_html($wizard_category->action_list_sub_modal_label); ?>"<?php } ?> style="display: none;">
<ul class="wsf-templates">
<?php
		$ws_form_wizard->wizard_category_render($wizard_category);
?>
</ul>

</div>
<!-- /Tab Content: <?php echo esc_html($wizard_category->label); ?> -->
<?php

	}
?>

</div>
<!-- /Wizard -->

<!-- Loading -->
<div id="wsf-form-add-loading"><p><?php esc_html_e("Your form is being built... just a moment.", 'ws-form'); ?></p></div>
<!-- /Loading -->

<!-- WS Form - Modal - List Sub -->
<div id="wsf-list-sub-modal-backdrop" class="wsf-modal-backdrop" style="display:none;"></div>

<div id="wsf-list-sub-modal" class="wsf-modal" style="display:none; margin-left:-200px; margin-top:-100px; width: 400px;">

<div id="wsf-list-sub">

<!-- WS Form - Modal - List Sub - Header -->
<div class="wsf-modal-title"></div>
<div class="wsf-modal-close" data-action="wsf-close" title="<?php esc_attr_e('Close', 'ws-form'); ?>"></div>
<!-- /WS Form - Modal - List Sub - Header -->

<!-- WS Form - Modal - List Sub - Content -->
<div class="wsf-modal-content">

<form>

<select id="wsf-list-sub-id"></select>

</form>

</div>
<!-- /WS Form - Modal - List Sub - Content -->

<!-- WS Form - Modal - List Sub - Buttons -->
<div class="wsf-modal-buttons">

<div id="wsf-modal-buttons-cancel">
<a data-action="wsf-close"><?php esc_html_e('Cancel', 'ws-form'); ?></a>
</div>

<div id="wsf-modal-buttons-list-sub">
<button class="button button-primary" data-action="wsf-add-wizard-action-modal"><?php esc_html_e('Create', 'ws-form'); ?></button>
</div>

</div>
<!-- /WS Form - Modal - List Sub - Buttons -->

</div>

</div>
<!-- /WS Form - Modal - List Sub -->

<!-- Form Actions -->
<form action="<?php echo esc_attr(WS_Form_Common::get_admin_url()); ?>" id="ws-form-action-do" method="post">
<input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>">
<?php wp_nonce_field(WS_FORM_POST_NONCE_ACTION_NAME, WS_FORM_POST_NONCE_FIELD_NAME); ?>
<input type="hidden" name="page" value="ws-form">
<input type="hidden" id="ws-form-action" name="action" value="">
<input type="hidden" id="ws-form-id" name="id" value="">
<input type="hidden" id="ws-form-action-id" name="action_id" value="">
<input type="hidden" id="ws-form-list-id" name="list_id" value="">
<input type="hidden" id="ws-form-list-sub-id" name="list_sub_id" value="">
<?php

	do_action('wsf_form_add_hidden');
?>
</form>
<!-- /Form Actions -->

<script>

	(function($) {

		'use strict';

		// On load
		$(function() {

			// Init wizard functionality
			var wsf_obj = new $.WS_Form();

			wsf_obj.wizard();
		});

	})(jQuery);

</script>

</div>
