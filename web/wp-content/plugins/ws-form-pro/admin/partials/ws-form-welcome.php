<?php

	WS_Form_Common::loader();

	WS_Form_Common::option_set('intro', true);
?>
<!-- Welcome Banner -->
<div id="wsf-welcome">

<!-- Slide 1 - Welcome -->
<div class="wsf-welcome-slide" data-id="1">

<div class="wsf-welcome-copy">
<div class="wsf-welcome-title"><?php echo esc_html(sprintf(__('This is %s', 'ws-form'), WS_FORM_NAME_PRESENTABLE)); ?></div>
<div class="wsf-welcome-intro"><?php esc_html_e('Build Better WordPress Forms', 'ws-form'); ?></div>
</div>

<button class="wsf-welcome-button" data-slide-next-id="2"><?php esc_html_e('Click to Start', 'ws-form'); ?></button>

</div>
<!-- /Slide 1 - Welcome -->

<!-- Slide 2 - Basic / Advanced -->
<div class="wsf-welcome-slide" data-id="2">

<div class="wsf-welcome-copy">
<div class="wsf-welcome-title"><?php esc_html_e('How familiar are you with building forms?', 'ws-form') ?></div>
<div class="wsf-welcome-intro"><?php esc_html_e('If you\'re new to building forms, we\'ll keep it simple.', 'ws-form'); ?></div>
</div>

<button class="wsf-welcome-button" data-slide-next-id="5" data-action="wsf-mode-set" data-value="basic"><?php esc_html_e('Keep It Simple', 'ws-form') ?></button>
<button class="wsf-welcome-button" data-slide-next-id="5" data-action="wsf-mode-set" data-value="basic"><?php esc_html_e('I\'m Familiar', 'ws-form') ?></button>
<button class="wsf-welcome-button" data-slide-next-id="4" data-action="wsf-mode-set" data-value="advanced"><?php esc_html_e('I\'m a Developer', 'ws-form') ?></button>

</div>
<!-- /Slide 2 - Basic / Advanced -->

<!-- Slide 3 - Framework Detect -->
<div class="wsf-welcome-slide" data-id="3">

<div class="wsf-welcome-copy">
<div class="wsf-welcome-title"><?php esc_html_e("You're using ", 'ws-form') ?><span id="wsf-welcome-framework"></span></div>
<div class="wsf-welcome-intro"><?php esc_html_e('Is that correct?', 'ws-form'); ?></div>
</div>

<button class="wsf-welcome-button" data-slide-next-id="5" data-action="wsf-framework-set"><?php esc_html_e('Yes'); ?></button>
<button class="wsf-welcome-button" data-slide-next-id="4"><?php esc_html_e('No', 'ws-form'); ?></button>
<button class="wsf-welcome-button" data-slide-next-id="5"><?php esc_html_e('I\'m Not Sure', 'ws-form'); ?></button>

</div>
<!-- /Slide 3 - Framework Detect -->

<!-- Slide 4 - Framework Select-->
<div class="wsf-welcome-slide" data-id="4">

<div class="wsf-welcome-copy">
<div class="wsf-welcome-title"><?php esc_html_e('Does your theme use a front-end framework?', 'ws-form') ?></div>
<div class="wsf-welcome-intro"><?php esc_html_e('Not sure? No problem. You can change this later.', 'ws-form'); ?></div>
</div>

<select id="framework" data-slide-next-id="5" class="wsf-welcome-select">
<option value=""><?php esc_html_e("Select..."); ?></option>
<option value="<?php echo esc_attr(WS_FORM_DEFAULT_FRAMEWORK); ?>"><?php esc_html_e('I\'m Not Sure', 'ws-form'); ?></option>
<option value="<?php echo esc_attr(WS_FORM_DEFAULT_FRAMEWORK); ?>"><?php esc_html_e('I\'m Not Using A Framework', 'ws-form'); ?></option>
<?php

	$frameworks = WS_Form_Config::get_frameworks(false);
	$framework_types = $frameworks['types'];
	foreach($framework_types as $type => $framework) {

		// Skip default framework (ws-form)
		if($type == WS_FORM_DEFAULT_FRAMEWORK) { continue; }

?><option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($framework['name']); ?></option>
<?php

	}

?></select>

<button class="wsf-welcome-button" data-slide-next-id="5"><?php esc_html_e('Skip This', 'ws-form'); ?></button>

</div>
<!-- /Slide 4 - Framework Select -->

<!-- Slide 5 - Setup Complete -->
<div class="wsf-welcome-slide" data-id="5" data-action="wsf-setup-push">

<div class="wsf-welcome-copy">
<div class="wsf-welcome-title"><?php esc_html_e('All Done!', 'ws-form') ?></div>
<div class="wsf-welcome-intro"><?php esc_html_e('You\'re ready to build your first form.', 'ws-form'); ?></div>
</div>

<div class="wsf-container">
<div class="wsf-video-container">
<iframe id="wsf-video-welcome" src="https://player.vimeo.com/video/289590605?api=1" width="640" height="360" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen allow="autoplay; encrypted-media"></iframe>
</div>
</div>

<script src="https://player.vimeo.com/api/player.js"></script>

<div><button class="wsf-welcome-button" data-action="wsf-form-add"><?php esc_html_e('Get Started...', 'ws-form'); ?></button></div>

</div>
<!-- /Slide 5 - Setup Complete -->

<!-- Slide 6 - API Error -->
<div class="wsf-welcome-slide" data-id="6">

<div class="wsf-welcome-copy">
<div class="wsf-welcome-title"><?php esc_html_e("Whoops! WordPress is not configured properly.", 'ws-form') ?></div>
<div class="wsf-welcome-intro"><?php esc_html_e("For more information, click the 'Help' button below.", 'ws-form'); ?><span class="wsf-welcome-api-error"></span></div>
</div>

<button class="wsf-welcome-button" data-action="wsf-try-again"><?php esc_html_e('Try Again', 'ws-form'); ?></button>
<a href="https://wsform.com/knowledgebase/installation-troubleshooting/" target="_blank" class="wsf-welcome-button"><?php esc_html_e('Help', 'ws-form'); ?></a>

</div>
<!-- /Slide 6 - API Error -->

</div>
<!-- /Welcome Banner -->

<script>

	// Options
	var params_setup = {

		'framework': '<?php echo esc_html(WS_FORM_DEFAULT_FRAMEWORK); ?>',
		'mode': '<?php echo esc_html(WS_FORM_DEFAULT_MODE); ?>'
	};

	var framework_detected = false;

	(function($) {

		'use strict';

		// On load
		$(function() {

			var wsf_obj = new $.WS_Form();
			var wsf_welcome_banner = $('#wsf-welcome');

			// Slide buttons
			$('button.wsf-welcome-button', wsf_welcome_banner).click(function() {

				user_action($(this), $(this).attr('data-value'));
			});

			// Slide select
			$('select', wsf_welcome_banner).change(function() {

				user_action($(this), $(this).val());
			});

			function user_action(obj, value) {

				var slide_next_id = obj.attr('data-slide-next-id');

				// Button actions
				var action_button = obj.attr('data-action');
				switch(action_button) {

					// Set framework type
					case 'wsf-framework-set' :

						if(framework_detected !== false) {

							params_setup['framework'] = framework_detected.type;
						}
						break;

					// Set mode
					case 'wsf-mode-set' :

						params_setup['mode'] = value;
						break;

					// Add form
					case 'wsf-form-add' :

						var iframe = $('#wsf-video-welcome');
						var player = new Vimeo.Player(iframe[0]);
						player.pause();
						location.href='<?php echo esc_html(WS_Form_Common::get_admin_url('ws-form-add')); ?>';
						break;

					// Try again
					case 'wsf-try-again' :

						location.href='<?php echo esc_html(WS_Form_Common::get_admin_url('ws-form-welcome')); ?>';
						break;
				}

				var slide_current = obj.closest('.wsf-welcome-slide');

				slide_current.fadeOut(200, function() {

					// Get next slide object
					var slide_next = $('.wsf-welcome-slide[data-id="' + slide_next_id + '"]');

					// Process action
					var action_slide = slide_next.attr('data-action');
					switch(action_slide) {

						case 'wsf-setup-push' :

							// Turn on loader
							wsf_obj.loader_on();

							// Push setup via API
							wsf_obj.setup_push(params_setup, function() {

								// Success
								slide_next.fadeIn(200);

								// Turn off loader
								wsf_obj.loader_off();

							}, function() {

								// Error
								slide_current.fadeIn(200);

								// Turn off loader
								wsf_obj.loader_off();
							});

							break;

						default :

							slide_next.fadeIn(200);
					}
				});
			}

			// On load
			$(function() {

				// Turn on loader
				wsf_obj.loader_on();

				// Detect framework
				wsf_obj.api_test(function() {

					// API test successful
					var slide_next = $('.wsf-welcome-slide[data-id="1"]');
					slide_next.fadeIn(200);

					// Detect framework
					wsf_obj.framework_detect(function(framework) {

						if(
							(typeof(framework.name) !== 'undefined') &&
							(framework.name !== false)
						) {

							// Remember framework detected
							framework_detected = framework;

							// Set framework name
							$('#wsf-welcome-framework').html(framework.name);

							// Reconfigure path
							$('.wsf-welcome-slide[data-id="2"] .wsf-welcome-button[data-value="advanced"]').attr('data-slide-next-id', '3');
						}

						// Turn off loader
						wsf_obj.loader_off();

					}, function() {

						// Turn off loader
						wsf_obj.loader_off();
					});

				}, function(error_message) {

					// Set error message
					$('.wsf-welcome-api-error').html((error_message !== false) ? 'Error: ' + error_message : '');

					// API test failed, show error page
					$('.wsf-welcome-slide[data-id="1"]').fadeOut(200, function() {

						// Hide all other slides just in case
						$('.wsf-welcome-slide').hide();

						// Show error slide
						var slide_next = $('.wsf-welcome-slide[data-id="6"]');
						slide_next.fadeIn(200);

						// Turn off loader
						wsf_obj.loader_off();
					});
				});
			});
		});

	})(jQuery);

</script>

