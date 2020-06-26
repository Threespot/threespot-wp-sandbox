(function($) {

	'use strict';

	$('[data-slug="ws-form"] .deactivate a, [data-slug="ws-form-pro"] .deactivate a').click(function(e) {

		e.preventDefault();

		var deactivate_url = $(this).attr('href');

		alert(deactivate_url);
	});

})(jQuery);
