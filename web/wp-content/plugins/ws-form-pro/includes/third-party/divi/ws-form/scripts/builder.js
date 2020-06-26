window.ws_form_divi_init = function(form_id) {

	(function($) {

		// Reset each form
		var instance_id = 1;
		$('.wsf-form').each(function() {

			$(this).html('').attr('data-instance-id', instance_id).attr('id', 'ws-form-' + instance_id);
			instance_id++;
		});

		// Render each form
		$('.wsf-form').each(function() {

			// Reset events and HTML
			$(this).off().html('');

			// Get attributes
			var id = $(this).attr('id');
			var form_id = $(this).attr('data-id');
			var instance_id = $(this).attr('data-instance-id');

			// Render form
			var ws_form = new $.WS_Form();
			window.wsf_form_instances[instance_id] = ws_form;

			ws_form.render({

				'obj' : 		'#' + id,
				'form_id':		form_id
			});
		});

	})(jQuery);
}
