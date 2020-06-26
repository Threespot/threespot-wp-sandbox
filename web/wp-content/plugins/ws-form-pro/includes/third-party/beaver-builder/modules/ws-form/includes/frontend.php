<?php

/**
 * You have access to two variables in this file: 
 * 
 * $module An instance of your module class.
 * $settings The module's settings.
 */

?>
<div class="fl-ws-form">
<?php

	if(
		isset($settings->form_id) &&
		!empty($settings->form_id)
	) {

		// Render form
		$form_id = $settings->form_id;
		echo do_shortcode(WS_Form_Common::shortcode($form_id));
	}
?>
</div>
