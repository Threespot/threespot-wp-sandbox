<?php

	class WS_Form_Admin {

		// The ID of this plugin.
		private $plugin_name;

		// The version of this plugin.
		private $version;

		// HTML editor settings
		private $html_editor_settings = '';

		// Submit fields
		private $submit_fields = false;

		// Form ID
		private $form_id;

		// User meta for hidden columns
		private $user_meta_hidden_columns;

		// Show intro
		private $intro;

		// Hooks
		private $hook_suffix_form = false;
		private $hook_suffix_form_add = false;
		private $hook_suffix_form_sub = false;
		private $hook_suffix_form_edit = false;
		private $hook_suffix_form_submit = false;
		private $hook_suffix_form_settings = false;
		private $hook_suffix_form_welcome = false;
		private $hook_suffix_form_migrate = false;
		private $hook_suffix_form_upgrade = false;
		private $hook_suffix_form_add_ons = false;

		// Initialize the class and set its properties.
		public function __construct() {

			$this->plugin_name = WS_FORM_NAME;
			$this->version = WS_FORM_VERSION;
			$this->user_meta_hidden_columns = 'managews-form_page_ws-form-submitcolumnshidden';	// AJAX function is in helper API
			$this->intro = WS_Form_Common::option_get('intro', false);
			$this->customize_enabled = (WS_Form_Common::option_get('framework', 'ws-form') === 'ws-form');

			// Activator to check for edition and version changes
			require_once WS_FORM_PLUGIN_DIR_PATH . 'includes/class-ws-form-activator.php';
			WS_Form_Activator::activate();
		}

		// Register the stylesheets for the admin area.
		public function enqueue_styles($hook) {

			switch($hook) {

				// Form - List
				case $this->hook_suffix_form_sub :

					// CSS - Framework
					wp_enqueue_style($this->plugin_name . '-css-layout', WS_Form_Common::get_api_path('helper/ws_form_css_admin'), array(), $this->version . '.' . WS_FORM_EDITION, 'all');

					// CSS - Template
					wp_enqueue_style($this->plugin_name . '-wizard', plugin_dir_url(__FILE__) . 'css/ws-form-admin-wizard.css', array(), $this->version, 'all');

					break;

				// Form - Add
				case $this->hook_suffix_form_add : 		

					// CSS - Framework
					wp_enqueue_style($this->plugin_name . '-css-layout', WS_Form_Common::get_api_path('helper/ws_form_css_admin'), array(), $this->version . '.' . WS_FORM_EDITION, 'all');

					// CSS - Template
					wp_enqueue_style($this->plugin_name . '-wizard', plugin_dir_url(__FILE__) . 'css/ws-form-admin-wizard.css', array(), $this->version, 'all');

					break;

				// Form - Edit
				case $this->hook_suffix_form_edit :

					// CSS - Intro
					if($this->intro) {

						wp_enqueue_style($this->plugin_name . '-css-intro', plugin_dir_url(__FILE__) . 'css/external/introjs/introjs.min.css', array(), $this->version, 'all');
					}

					// CSS - Select2 (Check made because WooCommerce enqueues this)
					if(!wp_style_is('select2', 'enqueued')) {

						wp_enqueue_style('select2', plugin_dir_url(__FILE__) . 'css/external/select2/select2.min.css', array(), '4.0.13', 'all');
					}

					// CSS - Framework
					wp_enqueue_style($this->plugin_name . '-css-layout', WS_Form_Common::get_api_path('helper/ws_form_css_admin'), array(), $this->version . '.' . WS_FORM_EDITION, 'all');

					// CSS - Framework
					wp_enqueue_style($this->plugin_name . '-css-layout', WS_Form_Common::get_api_path('helper/ws_form_css_admin'), array(), $this->version . '.' . WS_FORM_EDITION, 'all');

					break;

				// Form - Submissions
				case $this->hook_suffix_form_submit :	

					// CSS - JQuery UI
					wp_enqueue_style($this->plugin_name . '-css-jquery', plugin_dir_url(__FILE__) . 'jquery/jquery-ui.css', array(), $this->version, 'all');
					break;

				// Dashboard
				case 'index.php' :

					// CSS - Dashboard
					wp_enqueue_style($this->plugin_name . '-css-wp-dashboard', plugin_dir_url(__FILE__) . 'css/ws-form-admin-wp-dashboard.css', array(), $this->version, 'all');
					break;
				// WordPress Posts
				case 'post.php' : 
				case 'post-new.php' :

					// CSS - Template
					wp_enqueue_style($this->plugin_name . '-wizard', plugin_dir_url(__FILE__) . 'css/ws-form-admin-wizard.css', array(), $this->version, 'all');
			}

			// CSS - WordPress (Used throughout WordPress to style admin icon and other integral functions like the 'Add Form' feature)
			wp_enqueue_style($this->plugin_name . '-wp', plugin_dir_url(__FILE__) . 'css/ws-form-admin-wp.css', array(), $this->version, 'all');

			if(strpos($hook, WS_FORM_NAME) !== false) {

				// CSS - Admin
				wp_enqueue_style($this->plugin_name . '-admin', plugin_dir_url(__FILE__) . 'css/ws-form-admin.css', array(), $this->version, 'all');

				if(is_rtl()) {

					// CSS - RTL
					wp_enqueue_style($this->plugin_name . '-rtl', plugin_dir_url(__FILE__) . 'css/ws-form-admin-rtl.css', array(), $this->version, 'all');
				}
			}
		}

		// Register the JavaScript for the admin area.
		public function enqueue_scripts($hook) {

			// Get currency symbol
			$currencies = WS_Form_Config::get_currencies();
			$currency = WS_Form_Common::option_get('currency', 'USD');
			$currency_found = isset($currencies[$currency]) && isset($currencies[$currency]['symbol']);
			$currency_symbol = $currency_found ? $currencies[$currency]['symbol'] : '$';
			// Sidebar reset ID
			$settings_form_admin = WS_Form_Config::get_settings_form_admin();
			$sidebar_id = array_keys($settings_form_admin['sidebars']);
			$sidebar_reset_id = WS_Form_Common::get_query_var('sidebar', 'toolbox');
			if(!in_array($sidebar_reset_id, $sidebar_id)) { $sidebar_reset_id = 'toolbox'; }

			// Sidebar tab key
			$sidebar_tab_key = WS_Form_Common::get_query_var('tab', false);

			// WP NONCE
			$x_wp_nonce = wp_create_nonce('wp_rest');

			// Enqueued scripts settings
			$ws_form_settings = array(

				// Nonce
				'nonce'						=> $x_wp_nonce,		// Backward compatibility for older add-ons (Will be removed eventually)
				'x_wp_nonce'				=> $x_wp_nonce,
				'wsf_nonce_field_name'		=> WS_FORM_POST_NONCE_FIELD_NAME,
				'wsf_nonce'					=> wp_create_nonce(WS_FORM_POST_NONCE_ACTION_NAME),

				// URL
				'url'						=> WS_Form_Common::get_api_path(),

				// Permalink
				'permalink_custom'			=> (get_option('permalink_structure') != ''),

				// Admin framework
				'framework_admin'			=> 'ws-form',

				// Default label - Group
				'label_default_group'		=> WS_FORM_DEFAULT_GROUP_NAME,

				// Default label - Section
				'label_default_section'		=> WS_FORM_DEFAULT_SECTION_NAME,

				// Default label - Field
				'label_default_field'		=> WS_FORM_DEFAULT_FIELD_NAME,

				// HTML Editor settings
				'html_editor_settings'		=> $this->html_editor_settings,

				// Field prefix
				'field_prefix'				=> WS_FORM_FIELD_PREFIX,

				// Use X-HTTP-Method-Override?
				'ajax_http_method_override'	=> WS_Form_Common::option_get('ajax_http_method_override', true),

				// Locale
				'locale'					=> get_locale(),

				// Edition
				'edition'					=> WS_FORM_EDITION,

				// Version
				'version'					=> WS_FORM_VERSION,

				// Date / time format
				'date_format'				=> get_option('date_format'),
				'time_format'				=> get_option('time_format'),

				// Sidebar
				'sidebar_reset_id'			=> $sidebar_reset_id,
				'sidebar_tab_key'			=> $sidebar_tab_key,

				// Preview update
				'helper_live_preview'		=> WS_Form_Common::option_get('helper_live_preview', true),

				// RTL
				'rtl'						=> is_rtl(),
				// E-Commerce
				'currency_symbol'			=> $currency_symbol
			);

			// Form class
			wp_register_script($this->plugin_name . '-form-common', plugin_dir_url(__DIR__) . 'shared/js/ws-form.js', array('jquery'), $this->version, true);

			// Form class - Admin
			wp_register_script($this->plugin_name, plugin_dir_url(__DIR__) . 'admin/js/ws-form-admin.js', array('jquery', $this->plugin_name . '-form-common'), $this->version, true);

			// Scripts by hook
			switch($hook) {

				// WS Form - Welcome / Forms
				case $this->hook_suffix_form_sub :
				case $this->hook_suffix_form_welcome :

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					break;

				// WS Form - Add Form
				case $this->hook_suffix_form_add :

					// JQuery UI
					wp_enqueue_script('jquery-ui-tabs');

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					break;

				// WS Form - Edit Form
				case $this->hook_suffix_form_edit :

					// JQuery UI
					wp_enqueue_script('jquery-ui-core');
					wp_enqueue_script('jquery-ui-draggable');
					wp_enqueue_script('jquery-ui-sortable');
					wp_enqueue_script('jquery-ui-droppable');
					wp_enqueue_script('jquery-ui-tabs');
					wp_enqueue_script('jquery-ui-resizable');
					wp_enqueue_script('jquery-ui-slider');
					wp_enqueue_script('jquery-touch-punch');

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					// Enqueue WP editors
					global $wp_version;

					// TinyMCE
					if(version_compare($wp_version, '4.8', '>=')) {

						// Enable rich editing for this view (Overrides 'Disable the visual editor when writing' option for current user)
						add_filter('user_can_richedit', function($user_can_richedit) { return true; });

						wp_enqueue_editor();
						wp_enqueue_media();
					}

					// CodeMirror
					if(version_compare($wp_version, '4.9', '>=')) {

						wp_enqueue_code_editor(array('type' => 'text/html'));
					}

					// Intro
					if($this->intro) {

						wp_enqueue_script($this->plugin_name . '-intro', plugin_dir_url(__FILE__) . 'js/external/introjs/intro.min.js', array('jquery'), $this->version, true);
					}

					// Select2 (Check made because WooCommerce enqueues this)
					if(!wp_script_is('select2', 'enqueued')) {

						wp_enqueue_script('select2', plugin_dir_url(__FILE__) . 'js/external/select2/select2.full.min.js', array('jquery'), $this->version, true);
					}

					break;

				// WS Form - Form Submissions
				case $this->hook_suffix_form_submit :

					// JQuery UI
					wp_enqueue_script('jquery-ui-datepicker');

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					break;

				// WS Form - Migrate
				case $this->hook_suffix_form_migrate :

					// JQuery UI
					wp_enqueue_script('jquery-ui-tabs');

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					break;

				// WS Form - Settings
				case $this->hook_suffix_form_settings :

					// WordPress Media
					wp_enqueue_media();

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					break;

				// WordPress Posts
				case 'post.php' : 
				case 'post-new.php' : 

					$post_type = WS_Form_Common::get_query_var('post_type', 'post');
					$render_media_button = apply_filters('wsf_render_media_button', true, $post_type);
					if($render_media_button) {

						add_action('media_buttons', array($this, 'media_button'));
						add_action('admin_footer', array($this, 'media_buttons_html'));
					}

					if(WS_Form_Common::is_block_editor()) {

						// Create public instance
						$ws_form_public = new WS_Form_Public();

						// Set visual builder scripts to enqueue
						do_action('wsf_enqueue_visual_builder');

						// Enqueue scripts
						$ws_form_public->enqueue();

						// Add public footer to speed up loading of config
						$ws_form_public->wsf_form_json[0] = true;
						add_action('admin_footer', array($ws_form_public, 'wp_footer'));

					} else {

						// WS Form
						wp_enqueue_script($this->plugin_name . '-form-common');
						wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
						wp_enqueue_script($this->plugin_name);
					}

					break;

				// Dashboard
				case 'index.php' :

					// Chart
					wp_enqueue_script($this->plugin_name . '-chart', plugin_dir_url(__FILE__) . 'js/external/chart/Chart.min.js', array('jquery'), $this->version, true);

					// WS Form
					wp_enqueue_script($this->plugin_name . '-form-common');
					wp_localize_script($this->plugin_name . '-form-common', 'ws_form_settings', $ws_form_settings);
					wp_enqueue_script($this->plugin_name);

					break;

				// Plugins
				case 'plugins.php' :

					// Feedback
					add_action('admin_footer', array($this, 'feedback'));
					break;
			}

			// Enqueue admin count submit unread script
			$disable_count_submit_unread = WS_Form_Common::option_get('disable_count_submit_unread', false);
			if(!$disable_count_submit_unread) {

				wp_register_script($this->plugin_name . '-admin-count-submit-unread', plugin_dir_url(__FILE__) . 'js/ws-form-admin-count-submit-unread.js', array('jquery'), $this->version, false);

				$ws_form_form = new WS_Form_Form();
				$count_submit_unread_total = $ws_form_form->db_get_count_submit_unread_total();

				$ws_form_admin_count_submit_read_settings = array(

					'count_submit_unread_total' => $count_submit_unread_total,
					'count_submit_unread_ajax_url' => WS_Form_Common::get_api_path('helper/count_submit_unread')
				);
				wp_localize_script($this->plugin_name . '-admin-count-submit-unread', 'ws_form_admin_count_submit_read_settings', $ws_form_admin_count_submit_read_settings);
				wp_enqueue_script($this->plugin_name . '-admin-count-submit-unread');
			}
		}

		// Feedback
		public function feedback() {
?>
<script>

	(function($) {

		'use strict';

		var wsf_feedback_deactivate_url = false;

		// Close modal
		function wsf_feedback_modal_close() {

			$('#wsf-feedback-modal').hide();
			$('#wsf-feedback-modal-backdrop').hide();
			$(document).keydown = null;

			if(wsf_feedback_deactivate_url !== false) {

				location.href = wsf_feedback_deactivate_url;
			}
		}

		// On load
		$(function() {

			// Modal open
			$('[data-slug="ws-form"] .deactivate a, [data-slug="ws-form-pro"] .deactivate a').click(function(e) {

				e.preventDefault();

				wsf_feedback_deactivate_url = $(this).attr('href');

				// Show modal
				$('#wsf-feedback-modal-backdrop').show();
				$('#wsf-feedback-modal').show();
				$('[data-action="wsf-feedback-submit"]').attr('disabled', false);

				// Escape key
				$(document).keydown(function(e) {

					if(e.keyCode == 27) { 

						// Close modal
						wsf_feedback_modal_close();
					}
				});
			});

			// Click modal backdrop
			$(document).on('click', '#wsf-feedback-modal-backdrop', function(e) {

				// Close modal
				wsf_feedback_modal_close();
			});

			// Click close button
			$('[data-action="wsf-close"]').click(function() {

				// Close modal
				wsf_feedback_modal_close();
			});

			// Toggle fields
			$('[name="wsf_feedback_reason"]').change(function() {

				var feedback_reason_other = $('#wsf-feedback-reason-other').is(':checked');

				if(feedback_reason_other) {

					$('#wsf-feedback-reason-other-text').show().focus();

				} else {

					$('#wsf-feedback-reason-other-text').hide();
				}

				var feedback_reason_found_better_plugin = $('#wsf-feedback-reason-found-better-plugin').is(':checked');

				if(feedback_reason_found_better_plugin) {

					$('#wsf-feedback-reason-found-better-plugin-select').show().focus();

				} else {

					$('#wsf-feedback-reason-found-better-plugin-select').hide();
				}

				var feedback_reason_error = $('#wsf-feedback-reason-error').is(':checked');

				if(feedback_reason_error) {

					$('#wsf-feedback-reason-error-wrapper').show();

				} else {

					$('#wsf-feedback-reason-error-wrapper').hide();
				}
			});

			// Submit
			$('[data-action="wsf-feedback-submit"]').click(function() {

				$(this).attr('disabled', '');

				$.ajax({

					url: '<?php echo esc_html(WS_Form_Common::get_api_path('helper/deactivate_feedback_submit/')); ?>',
					data: {

						'wsf_nonce_field_name' : '<?php echo esc_attr(WS_FORM_POST_NONCE_FIELD_NAME); ?>',
						'wsf_nonce': '<?php echo esc_attr(wp_create_nonce(WS_FORM_POST_NONCE_ACTION_NAME)); ?>',
						'feedback_reason': $('[name="wsf_feedback_reason"]:checked').val(),
						'feedback_reason_error': $('[name="wsf_feedback_reason_error"]').val(),
						'feedback_reason_found_better_plugin': $('[name="wsf_feedback_reason_found_better_plugin"]').val(),
						'feedback_reason_other': $('[name="wsf_feedback_reason_other"]').val(),
					},
					type: 'POST',
					beforeSend: function(xhr) {

						xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_html(wp_create_nonce('wp_rest')); ?>');
					},
					complete: function(data){

						wsf_feedback_modal_close();
					}
				});
			});

			// Defaults
			$('#wsf-feedback-reason-other-text').hide();
			$('#wsf-feedback-reason-found-better-plugin-select').hide();
			$('#wsf-feedback-reason-error-wrapper').hide();
		});

	})(jQuery);

</script>

<!-- WS Form - Modal - Feedback -->
<div id="wsf-feedback-modal-backdrop" class="wsf-modal-backdrop" style="display:none;"></div>

<div id="wsf-feedback-modal" class="wsf-modal" style="display:none; margin-left:-200px; margin-top:-180px; width: 400px;">

<div id="wsf-feedback">

<!-- WS Form - Modal - Feedback - Header -->
<div class="wsf-modal-title"><?php

	echo WS_Form_Common::get_admin_icon('#002e5f', false); // phpcs:ignore

?><?php esc_html_e('Feedback', 'ws-form'); ?></div>
<div class="wsf-modal-close" data-action="wsf-close" title="<?php esc_attr_e('Close', 'ws-form'); ?>"></div>
<!-- /WS Form - Modal - Feedback - Header -->

<!-- WS Form - Modal - Feedback - Content -->
<div class="wsf-modal-content">

<form id="wsf-feedback-form">

<fieldset>

<p><?php esc_html_e(sprintf(__('We would greatly appreciate your feedback about why you are deactivating %s. Thank you for your help!', 'ws-form'), WS_FORM_NAME_PRESENTABLE)); ?></p>

<label><input type="radio" name="wsf_feedback_reason" value="Temporary" /> <?php esc_html_e('Temporarily deactivating', 'ws-form'); ?></label>

<label><input type="radio" id="wsf-feedback-reason-error" name="wsf_feedback_reason" value="Error" /> <?php esc_html_e('The plugin did not work', 'ws-form'); ?></label>

<div id="wsf-feedback-reason-error-wrapper">
<textarea id="wsf-feedback-reason-error-text" name="wsf_feedback_reason_error" placeholder="<?php esc_attr_e('Please describe the error...', 'ws-form'); ?>" rows="3"></textarea>
<p><em><?php esc_html_e('Need help? ', 'ws-form'); ?><?php

	echo sprintf('<a href="%s" target="_blank">%s</a>', WS_Form_Common::get_plugin_website_url('/support/', 'plugins_deactivate'), esc_html__('Get Support', 'ws-form')); // phpcs:ignore

?></em></p>
</div>

<label><input type="radio" name="wsf_feedback_reason" value="No Longer Need" /> <?php esc_html_e('I no longer need the plugin', 'ws-form'); ?></label>

<label><input type="radio" id="wsf-feedback-reason-found-better-plugin" name="wsf_feedback_reason" value="Found Better Plugin" /> <?php esc_html_e('I found a better plugin', 'ws-form'); ?></label>

<select id="wsf-feedback-reason-found-better-plugin-select" name="wsf_feedback_reason_found_better_plugin">
<option value=""><?php esc_html_e('Select...', 'ws-form'); ?></option>
<option value="Caldera Forms"><?php esc_html_e('Caldera Forms', 'ws-form'); ?></option>
<option value="Contact Form 7"><?php esc_html_e('Contact Form 7', 'ws-form'); ?></option>
<option value="Formidable Forms"><?php esc_html_e('Formidable Forms', 'ws-form'); ?></option>
<option value="Gravity Forms"><?php esc_html_e('Gravity Forms', 'ws-form'); ?></option>
<option value="Ninja Forms"><?php esc_html_e('Ninja Forms', 'ws-form'); ?></option>
<option value="Visual Form Builder"><?php esc_html_e('Visual Form Builder', 'ws-form'); ?></option>
<option value="weForms"><?php esc_html_e('weForms', 'ws-form'); ?></option>
<option value="WPForms"><?php esc_html_e('WPForms', 'ws-form'); ?></option>
<option value="Other"><?php esc_html_e('Other', 'ws-form'); ?></option>
</select>

<label><input type="radio" id="wsf-feedback-reason-other" name="wsf_feedback_reason" value="Other" /> <?php esc_html_e('Other', 'ws-form'); ?></label>

<textarea id="wsf-feedback-reason-other-text" name="wsf_feedback_reason_other" placeholder="<?php esc_attr_e('Please specify...', 'ws-form'); ?>" rows="3"></textarea>

</fieldset>

</form>

</div>
<!-- /WS Form - Modal - Feedback - Content -->

<!-- WS Form - Modal - Feedback - Buttons -->
<div class="wsf-modal-buttons">

<div id="wsf-modal-buttons-cancel">
<a data-action="wsf-close"><?php esc_html_e('Skip &amp; Deactivate', 'ws-form'); ?></a>
</div>

<div id="wsf-modal-buttons-feedback-submit">
<button class="button button-primary" data-action="wsf-feedback-submit"><?php esc_html_e('Submit &amp; Deactivate', 'ws-form'); ?></button>
</div>

</div>
<!-- /WS Form - Modal - Feedback - Buttons -->

</div>

</div>
<!-- /WS Form - Modal - Feedback -->
<?php
		}

		// Customize register
		public function customize_register($wp_customize) {

			if($this->customize_enabled && WS_Form_Common::can_user('customize')) {

				new WS_Form_Customize($wp_customize);
			}
		}

		// Media button
		public function media_button() {

			// Build add form button
?><button class="button wsf-button-add-form"><span class="wsf-button-add-form-icon"><?php

	echo WS_Form_Common::get_admin_icon('#888888', false);	// phpcs:ignore

?></span><?php esc_html_e('Add WS Form', 'ws-form'); ?></button><?php

		}

		// Media buttons - HTML
		public function media_buttons_html() {
?>
<script>

	(function($) {

		'use strict';

		function wsf_add_form_modal_close() {

			$('#wsf-add-form-modal').hide();
			$('#wsf-add-form-modal-backdrop').hide();
			$(document).keydown = null;
		}

		// On load
		$(function() {

			// Modal - Actions
			$('[data-action]', $('#wsf-add-form-modal')).click(function() {

				var action = $(this).attr('data-action');

				switch(action) {

					case 'wsf-close' :

						// Close modal
						wsf_add_form_modal_close();

						break;

					case 'wsf-inject' :

						// Get form ID
						var id = $('#wsf-post-add-form-id').val();

						// Build shortcode
						var shortcode = '[<?php echo esc_html(WS_FORM_SHORTCODE); ?> id="' + id + '"]';

						// Insert into editor
						wp.media.editor.insert(shortcode);

						// Close modal
						wsf_add_form_modal_close();

						break;

					case 'wsf-add' :

						location.href = '<?php echo esc_html(WS_Form_Common::get_admin_url('ws-form-add')); ?>';

						// Close modal
						wsf_add_form_modal_close();

						break;
				}
			});

			// Open modal
			$(document).on('click', '.wsf-button-add-form', function(e) {

				e.preventDefault();

				// Show modal
				$('#wsf-add-form-modal-backdrop').show();
				$('#wsf-add-form-modal').show();

				// Escape key
				$(document).keydown(function(e) {

					if(e.keyCode == 27) { 

						// Close modal
						wsf_add_form_modal_close();
					}
				});
			});

			// Click modal backdrop
			$(document).on('click', '#wsf-add-form-modal-backdrop', function(e) {

				// Close modal
				wsf_add_form_modal_close();
			});
		});

	})(jQuery);

</script>

<!-- WS Form - Modal - Add Form -->
<div id="wsf-add-form-modal-backdrop" class="wsf-modal-backdrop" style="display:none;"></div>

<div id="wsf-add-form-modal" class="wsf-modal" style="display:none; margin-left:-200px; margin-top:-100px; width: 400px;">

<div id="wsf-add-form">

<!-- WS Form - Modal - Add Form - Header -->
<div class="wsf-modal-title"><?php

	echo WS_Form_Common::get_admin_icon('#002e5f', false);	// phpcs:ignore

?><?php esc_html_e('Add WS Form', 'ws-form'); ?></div>
<div class="wsf-modal-close" data-action="wsf-close" title="<?php esc_attr_e('Close', 'ws-form'); ?>"></div>
<!-- /WS Form - Modal - Add Form - Header -->

<!-- WS Form - Modal - Add Form - Content -->
<div class="wsf-modal-content">

<form>
<?php

	// Get forms from API
	$ws_form_form = New WS_Form_Form();
	$forms = $ws_form_form->db_read_all('', 'NOT status="trash"', 'label', '', '', false);

	if($forms) {
?>
<label for="wsf-post-add-form-id"><?php esc_html_e('Select the form you want to add...', 'ws-form'); ?></label>
<select id="wsf-post-add-form-id">
<?php
		foreach($forms as $form) {

?><option value="<?php echo esc_attr($form['id']); ?>"><?php echo esc_html($form['label']); ?> (ID: <?php echo esc_html($form['id']); ?>)</option>
<?php
		}
?>
</select>
<?php
	} else {
?>
<p><?php esc_html_e("You haven't created any forms yet.", 'ws-form'); ?></p>
<p><a href="<?php echo esc_attr(WS_Form_Common::get_admin_url('ws-form-add')); ?>"><?php esc_html_e('Click here to create a form', 'ws-form'); ?></a></p>
<?php
	}
?>
</form>

</div>
<!-- /WS Form - Modal - Add Form - Content -->

<!-- WS Form - Modal - Add Form - Buttons -->
<div class="wsf-modal-buttons">

<div id="wsf-modal-buttons-cancel">
<a data-action="wsf-close"><?php esc_html_e('Cancel', 'ws-form'); ?></a>
</div>

<div id="wsf-modal-buttons-add-form">
<?php

	if($forms) {
?>
<button class="button button-primary" data-action="wsf-inject"><?php esc_html_e('Insert WS Form', 'ws-form'); ?></button>
<?php
	} else {
?>
<button class="button button-primary" data-action="wsf-add"><?php esc_html_e('Add WS Form', 'ws-form'); ?></button>
<?php
	}
?>
</div>

</div>
<!-- /WS Form - Modal - Add Form - Buttons -->

</div>

</div>
<!-- /WS Form - Modal - Add Form -->
<?php
		}

		// Add admin menu pages (visible and hidden)
		public function admin_menu() {

			// Unread submission span
			$disable_count_submit_unread = WS_Form_Common::option_get('disable_count_submit_unread', false);
			$count_submit_unread_total_html = $disable_count_submit_unread ? '' : '<span class="wsf-submit-unread-total wsf-submit-unread"></span>';

			// Forms - List
			$this->hook_suffix_form = add_menu_page(

				__('WS Form', 'ws-form'),
				__('WS Form', 'ws-form') . $count_submit_unread_total_html,
				'read_form',
				$this->plugin_name,
				false,
				WS_Form_Common::get_admin_icon(),
				35
			);
			add_action('load-' . $this->hook_suffix_form, array($this, 'ws_form_wp_list_table_form_options'));

			// Welcome (Hidden)
			$this->hook_suffix_form_welcome = add_submenu_page(

				'options.php',
				__('Welcome', 'ws-form'),
				__('Welcome to WS Form', 'ws-form'),
				'manage_options_wsform',
				$this->plugin_name . '-welcome',
				array($this, 'admin_page_welcome')
			);

			// Forms - List (Sub Menu)
			$this->hook_suffix_form_sub = add_submenu_page(

				$this->plugin_name,
				__('Forms', 'ws-form'),
				__('Forms', 'ws-form'),
				'read_form',
				$this->plugin_name,
				array($this, 'admin_page_form')
			);

			// Form - Add
			$this->hook_suffix_form_add = add_submenu_page(

				$this->plugin_name,
				__('Add New', 'ws-form'),
				__('Add New', 'ws-form'),
				'create_form',
				$this->plugin_name . '-add',
				array($this, 'admin_page_form_add')
			);

			// Form - Submissions
			$this->hook_suffix_form_submit = add_submenu_page(

				$this->plugin_name,
				__('Submissions', 'ws-form'),
				__('Submissions', 'ws-form') . $count_submit_unread_total_html,
				'read_submission',
				$this->plugin_name . '-submit',
				array($this, 'admin_page_form_submit')
			);

			add_action('load-' . $this->hook_suffix_form_submit, array($this, 'ws_form_wp_list_table_submit_options'));
			add_filter('default_hidden_columns', array($this, 'ws_form_default_hidden_columns'), 10, 2); 

			// Forms - Edit (Hidden)
			$this->hook_suffix_form_edit = add_submenu_page(

				'options.php',
				__('Edit Form', 'ws-form'),
				__('WS Form', 'ws-form'),
				'edit_form',
				$this->plugin_name . '-edit',
				array($this, 'admin_page_form_edit')
			);

			// Customize
			$customize_url = sprintf('customize.php?return=%s&wsf_panel_open=true', urlencode(remove_query_arg(wp_removable_query_args(), wp_unslash($_SERVER['REQUEST_URI']))));
			$page = WS_Form_Common::get_query_var('page');
			$id = intval(WS_Form_Common::get_query_var('id'));
			if(($page === 'ws-form-edit') && ($id > 0)) {
				$customize_url .= sprintf('&wsf_preview_form_id=%u', $id);
			}
			if($this->customize_enabled) {

				$this->hook_suffix_customize = add_submenu_page(

					$this->plugin_name,
					__('Customize', 'ws-form'),
					__('Customize', 'ws-form'),
					'customize',
					$customize_url
				);
			}

			// Settings
			$this->hook_suffix_form_settings = add_submenu_page(

				$this->plugin_name,
				__('Settings', 'ws-form'),
				__('Settings', 'ws-form'),
				'manage_options_wsform',
				$this->plugin_name . '-settings',
				array($this, 'admin_page_settings')
			);

			// Form - Migrate
			$this->hook_suffix_form_migrate = add_submenu_page(

				$this->plugin_name,
				__('Migrate', 'ws-form'),
				__('Migrate', 'ws-form'),
				'import_form',
				$this->plugin_name . '-migrate',
				array($this, 'admin_page_form_migrate')
			);
			// Add-Ons
			$this->hook_suffix_form_add_ons = add_submenu_page(

				$this->plugin_name,
				__('Add-Ons', 'ws-form'),
				__('<span style="color: #3399DD;">Add-Ons</span>', 'ws-form'),
				'manage_options_wsform',
				$this->plugin_name . '-add-ons',
				array($this, 'admin_page_add_ons')
			);
		}

		// Default hidden submit columns
		public function ws_form_default_hidden_columns($hidden, $screen) {

			$form_id = WS_Form_Common::get_query_var('id');
			if(!$screen) { return $hidden; }
			if(!isset($screen->id)) { return $hidden; }
			if($form_id == 0) { return $hidden; }

			// Process hidden columns by screen ID
			switch($screen->id) {

				case 'ws-form_page_ws-form-submit' :

					$ws_form_submit = new WS_Form_Submit;
					$ws_form_submit->form_id = $form_id;
					$submit_fields = $ws_form_submit->db_get_submit_fields();

					foreach($submit_fields as $id => $field) {

						$field_hidden = $field['hidden'];
						if($field_hidden) { $hidden[] = WS_FORM_FIELD_PREFIX . $id; }
					}

					break;
			}

			return $hidden;
		}

		// Form screen options
		public function ws_form_wp_list_table_form_options() {

			$option = 'per_page';

			$args = array(
				'label' => 'Forms per page:',
				'default' => 20,
				'option' => 'ws_form_items_per_page_form'
			);

			add_screen_option($option, $args);

			// Create forms object (List of forms)
			$this->ws_form_wp_list_table_form_obj = new WS_Form_WP_List_Table_Form();
		}

		// Submission screen options
		public function ws_form_wp_list_table_submit_options() {

			$option = 'per_page';

			$args = array(
				'label' => 'Submissions per page:',
				'default' => 20,
				'option' => 'ws_form_items_per_page_submit'
			);

			add_screen_option($option, $args);

			// Create forms object (List of forms)
			$this->ws_form_wp_list_table_submit_obj = new WS_Form_WP_List_Table_Submit();
		}

		// Set screen option
		public function ws_form_set_screen_option($status, $option, $value) {

			switch($option) {

				case 'ws_form_items_per_page_form' :
				case 'ws_form_items_per_page_submit' :

					return $value;				
			}

			return $status;
		}

		// Gutenberg Editor Block
		public function enqueue_block_editor_assets() {

			// Get forms from API
			$ws_form_form = New WS_Form_Form();
			$forms = $ws_form_form->db_read_all('', 'NOT status="trash"', 'label', '', '', false);

			// Enqueue block JavaScript in footer
			wp_enqueue_script(

				'wsf-block',
				plugins_url('admin/js/ws-form-block.js', WS_FORM_PLUGIN_ROOT_FILE),
				array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor'),
				$this->version,
				true
			);

			// Get SVG
			$ws_form_wizard = new WS_Form_Wizard();
			$ws_form_wizard->id = 'contact-us';
			$svg = $ws_form_wizard->get_svg();
			$svg = str_replace('#label', $ws_form_wizard->label, $svg);

			// Localize block JavaScript
			wp_localize_script('wsf-block', 'wsf_settings_block', array(

				// Add Form
				'form_add' => array(

					'name'				=> 'wsf-block/form-add',
					'label'				=> WS_FORM_NAME_PRESENTABLE,
					'description'		=> sprintf(__('Add a form to your web page using %s.', 'ws-form'), WS_FORM_NAME_PRESENTABLE),
					'category'			=> WS_FORM_NAME,
					'keywords'			=> array(WS_FORM_NAME_PRESENTABLE, __('form', 'ws-form')),
					'preview'			=> $svg,
					'no_forms'			=> __("You haven't created any forms yet.", 'ws-form'),
					'form_not_selected'	=> __('Choose the form you would like add in the block settings sidebar.', 'ws-form'),
					'options_label'		=> __('Form', 'ws-form'),
					'options_select'	=> __('Select...', 'ws-form'),
					'id'				=> __('ID', 'ws-form'),
					'add'				=> __('Add New', 'ws-form'),
					'url_add'			=> WS_Form_Common::get_admin_url('ws-form-add'),
					'form_action'		=> WS_Form_Common::get_api_path() . 'submit'
				),

				'forms'						=> $forms,
			));
		}

		// Gutenbery Editor Block - Register category
		public function block_categories($categories, $post) {

			return array_merge(

				$categories,

				array(

					array(

						'slug'  => WS_FORM_NAME,
						'title' => WS_FORM_NAME_PRESENTABLE
					)
				)
			);
		}

		// Gutenberg Editor Blocks - Register
		public function register_blocks() {

			if(function_exists('register_block_type')) {

				$block_config = array(

					'editor_script'		=> 'wsf-block',

					'render_callback'	=> array($this, 'block_render'),

					'attributes'		=> array(

						'form_id'	=> array(

							'type'    => 'string'
						)
					)
				);

				register_block_type('wsf-block/form-add', $block_config);
			}
		}

		// Block rendering
		public function block_render($attributes, $content) {

			// Do not render if form ID is not set
			if(!isset($attributes['form_id'])) { return ''; }

			$form_id = intval($attributes['form_id']);

			// Do not render if form ID = 0
			if($form_id == 0) { return ''; }

			$return_html = do_shortcode(sprintf('[%s id="%u"]', WS_FORM_SHORTCODE, $form_id));

			return $return_html;
		}

		// Plugins loaded
		public function plugins_loaded() {

			// Run plugins loaded
			do_action('wsf_plugins_loaded');
		}

		// WP loaded
		public function current_screen() {

			if(WS_Form_Common::is_block_editor()) {

				// Force framework to be ws-form
				add_filter('wsf_option_get', array('WS_Form_Common', 'option_get_framework_ws_form'), 10, 2);
			}
		}

		// Form processing
		public function init() {

			// Register block
			self::register_blocks();

			// AJAX handler for hidden column changes (Form ID specific)
	        add_action('wp_ajax_ws_form_hidden_columns', array($this, 'ws_form_hidden_columns'), 1);

			add_filter('set-screen-option', array($this, 'ws_form_set_screen_option'), 10, 3);

			// Get current page
 			$page = WS_Form_Common::get_query_var('page');
			if($page === '') { return true; }

			// Do on specific WS Form pages
			switch($page) {

				// Forms
				case 'ws-form' :

					if(!WS_Form_Common::can_user('read_form')) { break; }

					// Read form ID and action
					$this->form_id = intval(WS_Form_Common::get_query_var_nonce('id', '', false, false, true, 'POST'));
					$action = WS_Form_Common::get_query_var_nonce('action', '', false, false, true, 'POST');
					if($action == '-1') { $action = WS_Form_Common::get_query_var_nonce('action2'); }

					// Process action
					switch($action) {

						case 'wsf-add-blank' : 		self::form_add_blank(); break;
						case 'wsf-add-wizard' : 	self::form_add_wizard(WS_Form_Common::get_query_var_nonce('id')); break;
						case 'wsf-add-action' : 	self::form_add_action(WS_Form_Common::get_query_var_nonce('action_id'), WS_Form_Common::get_query_var_nonce('list_id'), WS_Form_Common::get_query_var_nonce('list_sub_id', false)); break;
						case 'wsf-clone' : 			self::form_clone($this->form_id); break;
						case 'wsf-delete' : 		self::form_delete($this->form_id); self::redirect('ws-form', false, self::get_filter_query()); break;
						case 'wsf-export' : 		self::form_export($this->form_id); break;
						case 'wsf-restore' : 		self::form_restore($this->form_id); self::redirect('ws-form', false); break;
						case 'wsf-bulk-delete' : 	self::form_bulk('delete'); break;
						case 'wsf-bulk-restore' : 	self::form_bulk('restore'); break;
						case '-1':

							// Check for delete_all
							if(WS_Form_Common::get_query_var_nonce('delete_all') != '') {

								// Empty trash
								if(WS_Form_Common::get_query_var_nonce('delete_all')) { self::form_trash_delete(); }
							}
							break;
					}

					break;

				// Submissions
				case 'ws-form-submit' :

					if(!WS_Form_Common::can_user('read_submission')) { break; }

					// Read form ID, submit ID and action
					$this->form_id = intval(WS_Form_Common::get_query_var('id', 0));
					if(!$this->form_id) { break; }
					$submit_id = intval(WS_Form_Common::get_query_var_nonce('submit_id', ''));
					$action = WS_Form_Common::get_query_var_nonce('action', '');
					if($action == '-1') { $action = WS_Form_Common::get_query_var_nonce('action2'); }

					// Process action
					switch($action) {

						case 'wsf-delete' :

							self::submit_delete($submit_id, true);
							self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());
							break;

						case 'wsf-restore' : 			

							self::submit_restore($submit_id, true);
							self::redirect('ws-form-submit', $this->form_id);
							break;

						case 'wsf-export' : 			self::submit_export($submit_id); break;
						case 'wsf-bulk-delete' : 		self::submit_bulk('delete'); break;
						case 'wsf-bulk-restore' : 		self::submit_bulk('restore'); break;
						case 'wsf-bulk-export' : 		self::submit_bulk('export'); break;
						case 'wsf-bulk-spam' : 			self::submit_bulk('spam'); break;
						case 'wsf-bulk-not-spam' : 		self::submit_bulk('not-spam'); break;
						case 'wsf-bulk-read' : 			self::submit_bulk('read'); break;
						case 'wsf-bulk-not-read' : 		self::submit_bulk('not-read'); break;
						case 'wsf-bulk-starred' : 		self::submit_bulk('starred'); break;
						case 'wsf-bulk-not-starred' : 	self::submit_bulk('not-starred'); break;
						case '-1':

							// Check for delete_all
							if(WS_Form_Common::get_query_var_nonce('delete_all') != '') {

								// Empty trash
								if(WS_Form_Common::get_query_var_nonce($this->form_id, 'delete_all')) { self::submit_trash_delete(); }
							}
							break;
					}

					// Action
					do_action('wsf_table_submit_action', $action, $submit_id);

					// Process hidden columns
					if($this->form_id == 0) { break; }

					// Read hidden columns for current form
					$form_hidden_columns = get_user_option($this->user_meta_hidden_columns . '-' . $this->form_id);

					if($form_hidden_columns === '') {

						// Create fresh hidden columns array
						$form_hidden_columns = [];

						$ws_form_submit = new WS_Form_Submit;
						$ws_form_submit->form_id = $this->form_id;
						$submit_fields = $ws_form_submit->db_get_submit_fields();

						foreach($submit_fields as $id => $field) {

							$field_hidden = $field['hidden'];
							if($field_hidden) { $form_hidden_columns[] = WS_FORM_FIELD_PREFIX . $id; }
						}

						// Other fields to hide
						$form_hidden_columns[] = 'date_updated';
					}

					// Write hidden columns back to user meta for current form
					update_user_option(get_current_user_id(), $this->user_meta_hidden_columns, $form_hidden_columns, true);

					break;
	
				// Settings
				case 'ws-form-settings' :

					// Read form ID and action
					$action = WS_Form_Common::get_query_var_nonce('action', '', false, false, true, 'POST');

					switch($action) {

						case 'wsf-settings-update' :

							// Get options
							$options = WS_Form_Config::get_options();

							// Get current tab
							$tabCurrent = WS_Form_Common::get_query_var_nonce('tab', 'appearance');
							if($tabCurrent == 'setup') { $tabCurrent = 'appearance'; }				// Backward compatibility

							// File upload checks
							$upload_checks = WS_Form_Common::uploads_check();
							$max_upload_size = $upload_checks['max_upload_size'];
							$max_uploads = $upload_checks['max_uploads'];

							$fields = [];

							// Save current mode
							$mode_old = WS_Form_Common::option_get('mode');

							// Build field list
							if(isset($options[$tabCurrent]['fields'])) {

								$fields = $fields + $options[$tabCurrent]['fields'];
							}
							if(isset($options[$tabCurrent]['groups'])) {

								$groups = $options[$tabCurrent]['groups'];

								foreach($groups as $group) {

									$fields = $fields + $group['fields'];
								}
							}

							// Update fields
							self::settings_update_fields($fields, $max_uploads, $max_upload_size);

							// Update fields if mode has changed
							$mode = WS_Form_Common::option_get('mode');

							if($mode_old != $mode) {

								foreach($options as $tab => $attributes) {

									if(isset($attributes['fields'])) {

										$fields = $attributes['fields'];
										self::setting_mode_change_fields($fields, $mode);
									}

									if(isset($attributes['groups'])) {

										$groups = $attributes['groups'];

										foreach($groups as $group) {

											$fields = $group['fields'];

											self::setting_mode_change_fields($fields, $mode);
										}
									}
								}
							}

							break;
					}

					do_action('wsf_settings');

					break;

				// Welcome page
				case 'ws-form-welcome' :

					// Disable nag notices
					if(!defined('DISABLE_NAG_NOTICES')) {

						define('DISABLE_NAG_NOTICES', true);
					}

					break;
			}

			// Do on every WS Form page
			if(strpos($page, $this->plugin_name) !== false) {

				// Except welcome
				if(strpos($page, $this->plugin_name . '-welcome') === false) {

					// Check if set-up needs to be run
					$setup = WS_Form_Common::option_get('setup');
					if(
						empty($setup) &&
						(WS_Form_Common::get_query_var('skip_welcome') == '')
					) {

						wp_redirect(WS_Form_Common::get_admin_url('ws-form-welcome'));
					}
				}
			}

			// Run nags
			do_action('wsf_nag');
		}

		// Get filter query
		public function get_filter_query() {

			$submit_filter_query_array = array();
			$submit_filter_query_lookups = array('date_from', 'date_to', 'paged', 'ws-form-status');

			foreach($submit_filter_query_lookups as $submit_filter_query_lookup) {

				if(WS_Form_Common::get_query_var($submit_filter_query_lookup) != '') { $submit_filter_query_array[] = $submit_filter_query_lookup . '=' . WS_Form_Common::get_query_var($submit_filter_query_lookup); }
			}

			$submit_filter_query_array[] = WS_FORM_POST_NONCE_FIELD_NAME . '=' . wp_create_nonce(WS_FORM_POST_NONCE_ACTION_NAME);

			return implode('&', $submit_filter_query_array);
		}

		// Form - Create
		public function form_add_blank() {

			$ws_form_form = New WS_Form_Form();
			$ws_form_form->db_create();

			if($ws_form_form->id > 0) {

				// Redirect
				self::redirect('ws-form-edit', $ws_form_form->id);
			}
		}

		// Form - Create from wizard
		public function form_add_wizard($id) {

			$ws_form_form = New WS_Form_Form();
			$ws_form_form->db_create_from_wizard($id);

			if($ws_form_form->id > 0) {

				// Redirect
				self::redirect('ws-form-edit', $ws_form_form->id);
			}
		}

		// Form - Create from action
		public function form_add_action($action_id, $list_id, $list_sub_id = false) {

			$ws_form_form = New WS_Form_Form();
			$ws_form_form->db_create_from_action($action_id, $list_id, $list_sub_id);

			if($ws_form_form->id > 0) {

				// Redirect
				self::redirect('ws-form-edit', $ws_form_form->id);
			}
		}

		// Form - Clone
		public function form_clone($id) {

			$ws_form_form = New WS_Form_Form();
			$ws_form_form->id = $id;
			$ws_form_form->db_clone();

			if($ws_form_form->id > 0) { self::redirect('ws-form', false, self::get_filter_query()); }
		}

		// Form - Delete
		public function form_delete($id) {

			$ws_form_form = New WS_Form_Form();
			$ws_form_form->id = $id;
			$ws_form_form->db_delete();

			// No redirect here in case it is called by bulk loop
		}

		// Form - Export
		public function form_export($id) {

			$ws_form_form = New WS_Form_Form();
			$ws_form_form->id = $id;
			$ws_form_form->db_download_json();

			// No redirect here in case it is called by bulk loop
		}

		// Form - Restore
		public function form_restore($id) {

			$ws_form_form = New WS_Form_Form();
			$ws_form_form->id = $id;
			$ws_form_form->db_restore();

			// No redirect here in case it is called by bulk loop
		}

		// Form - Bulk
		public function form_bulk($method = '') {

			$ids = WS_Form_Common::get_query_var_nonce('bulk-ids');

			if(!$ids || (count($ids) == 0)) { return false; }

			switch($method) {

				case 'delete' :

					foreach ($ids as $id) { self::form_delete($id); }
					self::redirect('ws-form', false, self::get_filter_query());
					break;

				case 'restore' :

					foreach ($ids as $id) { self::form_restore($id); }
					self::redirect('ws-form', false);
					break;
			}
		}

		// Form - Empty trash
		public function form_trash_delete() {

			$ws_form_form = New WS_Form_Form();
			$ws_form_form->db_trash_delete();

			// Redirect
			self::redirect('ws-form', false, self::get_filter_query());
		}

		// Submit - Delete
		public function submit_delete($id, $update_count_submit_unread) {

			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_delete(false, $update_count_submit_unread);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Restore
		public function submit_restore($id, $update_count_submit_unread) {

			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_restore($update_count_submit_unread);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Mark As Spam
		public function submit_spam($id, $update_count_submit_unread) {

			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_set_status('spam', $update_count_submit_unread);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Mark As Not Spam
		public function submit_not_spam($id, $update_count_submit_unread) {

			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_set_status('not_spam', $update_count_submit_unread);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Mark As Read
		public function submit_read($id, $update_count_submit_unread) {

			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_set_viewed(true, $update_count_submit_unread);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Mark As Unread
		public function submit_not_read($id, $update_count_submit_unread) {

			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_set_viewed(false, $update_count_submit_unread);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Mark As Starred
		public function submit_starred($id) {

			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->db_set_starred(true);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Mark As Not Starred
		public function submit_not_starred($id) {

			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->id = $id;
			$ws_form_submit->db_set_starred(false);

			// No redirect here in case it is called by bulk loop
		}

		// Submit - Bulk
		public function submit_bulk($method = '') {

			$ids = WS_Form_Common::get_query_var_nonce('bulk-ids');

			if(!$ids || (count($ids) == 0)) { return false; }

			switch($method) {

				case 'delete' :

					foreach ($ids as $id) { self::submit_delete($id, false); }

					self::update_count_submit_unread();

					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());

					break;

				case 'restore' :

					foreach ($ids as $id) { self::submit_restore($id, false); }

					self::update_count_submit_unread();

					self::redirect('ws-form-submit', $this->form_id);

					break;

				case 'export' :

					self::submit_export($ids);
					break;

				case 'spam' :

					foreach ($ids as $id) { self::submit_spam($id); }

					self::update_count_submit_unread();

					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());

					break;

				case 'not-spam' :

					foreach ($ids as $id) { self::submit_not_spam($id); }

					self::update_count_submit_unread();

					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());

					break;

				case 'read' :

					foreach ($ids as $id) { self::submit_read($id, false); }

					self::update_count_submit_unread();

					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());

					break;

				case 'not-read' :

					foreach ($ids as $id) { self::submit_not_read($id, false); }

					self::update_count_submit_unread();

					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());

					break;

				case 'starred' :

					foreach ($ids as $id) { self::submit_starred($id); }
					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());
					break;

				case 'not-starred' :

					foreach ($ids as $id) { self::submit_not_starred($id); }
					self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());
					break;
			}
		}

		// Submit - Update statistics
		public function update_count_submit_unread() {

			// Update form submit unread count statistic
			$ws_form_form = new WS_Form_Form();
			$ws_form_form->id = $this->form_id;
			$ws_form_form->db_update_count_submit_unread();
		}

		// Submit - Export
		public function submit_export($ids) {

			// Build filename
			$filename = WS_Form_Common::filename_datestamp('ws-form-submit', 'csv');

			// HTTP headers
			WS_Form_Common::file_download_headers($filename, 'text/csv');

			// Start CSV output
			$csv_out = fopen('php://output', 'w');

			// New submit object
			$ws_form_submit = New WS_Form_Submit();

			// Set form ID
			$ws_form_submit->form_id = $this->form_id;

			// Export CSV
			$ws_form_submit->db_export_csv($csv_out, $ids);

			exit;
		}

		// Submit - Empty trash
		public function submit_trash_delete() {

			$ws_form_submit = New WS_Form_Submit();
			$ws_form_submit->form_id = $this->form_id;
			$ws_form_submit->db_trash_delete();

			// Redirect
			self::redirect('ws-form-submit', $this->form_id, self::get_filter_query());
		}

		public function settings_update_fields($fields, $max_uploads, $max_upload_size) {

			// Update
			foreach(array_reverse($fields) as $field => $attributes) {

				// Hidden values
				if($attributes['type'] === 'hidden') { continue; }

				// Condition
				if(isset($attributes['condition'])) {

					$condition_result = true;
					foreach($attributes['condition'] as $condition_field => $condition_value) {

						$condition_value_check = WS_Form_Common::option_get($condition_field);
						if($condition_value_check != $condition_value) {

							$condition_result = false;
							break;
						}
					}
					if(!$condition_result) { continue; }
				}

				$value = WS_Form_Common::get_query_var_nonce($field);

				// Process fields
				switch($field) {

					case 'license_key' :

						$action_mode = WS_Form_Common::get_query_var_nonce('action_mode');

						$plugin_licensing = new WS_Form_Licensing(WS_FORM_LICENSE_ITEM_ID);

						switch($action_mode) {

							case 'activate' :

								$plugin_licensing->activate($value);
								break;

							case 'deactivate' :

								$plugin_licensing->deactivate($value);
								break;
						}

						break;

					default :

						do_action('wsf_settings_update_fields', $field, $value);
				}

				// Write by type
				switch($attributes['type']) {

					case 'hidden' : break;				

					case 'static' : break;				

					case 'number' : 

						// Round numbers
						$value = floatval($value);
						if(isset($attributes['absint'])) { $value = absint($value); }

						// Minimum
						if(isset($attributes['minimum'])) {

							if($value < $attributes['minimum']) { $value = $attributes['minimum']; }
						}

						// Maximum
						if(isset($attributes['maximum'])) {

							$maximum = $attributes['maximum'];

							switch($maximum) {

								case '#max_upload_size' : $maximum = $max_upload_size; break;
								case '#max_uploads' : $maximum = $max_uploads; break;
							}

							if($value > $maximum) { $value = $maximum; }
						}

						WS_Form_Common::option_set($field, $value);

						break;

					case 'checkbox' :

						$value = ($value === '1');

						WS_Form_Common::option_set($field, $value);

						break;

					default :

						WS_Form_Common::option_set($field, $value);
				}
			}

			// Add admin message
			if(WS_Form_Common::get_admin_message_count() == 0) {

				WS_Form_Common::admin_message_push('Successfully saved settings!');
			}
		}

		public function setting_mode_change_fields($fields, $mode) {

			// Update
			foreach($fields as $field => $attributes) {

				// Set according to mode
				if(isset($attributes['type']) && ($attributes['type'] != 'static') && isset($attributes['mode']) && isset($attributes['mode'][$mode])) {

					$value = $attributes['mode'][$mode];

					WS_Form_Common::option_set($field, $value);
				}
			}
		}

		// Redirect
		public function redirect($page_slug = 'ws-form', $item_id = false, $path_extra = '') {

			wp_redirect(WS_Form_Common::get_admin_url($page_slug, $item_id, $path_extra));
			exit;
		}

		// Settings links
		public function plugin_action_links($links) {

			// Add-Ons
			array_unshift($links, sprintf('<a href="%s">%s</a>', WS_Form_Common::get_admin_url('ws-form-settings&tab=add_ons'), __('Add-Ons', 'ws-form')));
			// Settings
			array_unshift($links, sprintf('<a href="%s">%s</a>', WS_Form_Common::get_admin_url('ws-form-settings'), __('Settings', 'ws-form')));

			return $links;
		}

		// Dashboard glance items
		public function dashboard_glance_items( $items = array() ) {

			if(!WS_Form_Common::can_user('read_form')) { return $items; }

			// Get form count
			$ws_form_form = new WS_Form_Form;
			$form_count = $ws_form_form->db_get_count_by_status();

			// Build text
			$text = sprintf(_n('%s Form', '%s Forms', $form_count, 'ws-form'), $form_count);

			// Add item
			if(WS_Form_Common::can_user('read_form')) {

				$url = WS_Form_Common::get_admin_url('ws-form');
				$items[] = sprintf('<a class="wsf-dashboard-glance-count" href="%s">%s</a>', $url, $text) . "\n";

			} else {

				$items[] = sprintf('<span class="wsf-dashboard-glance-count">%1</span>', $text) . "\n";
			}

			return $items;
		}

		// Theme switch, so reset preview template
		public function switch_theme() {

			WS_Form_Common::option_set('preview_template', '');			
		}

		public function wp_dashboard_setup() {

			if(!WS_Form_Common::can_user('read_submission')) { return $items; }

			wp_add_dashboard_widget(

				'wsf_dashboard_widget',
				__('WS Form', 'ws-form'),
				array($this, 'dashboard_widget')
			);
		}

		public function dashboard_widget() {

			$ws_form_form_stat = new WS_Form_Form_Stat();
			$ws_form_form_stat->date_ranges_init();

			$current_user_id = get_current_user_id();

			// Select form
			$ws_form_form = New WS_Form_Form();
			$forms = $ws_form_form->db_read_all('', "NOT (status = 'trash')", 'label ASC', '', '', false);

			if($forms) {

				$wsf_dashboard_widget_form_id = WS_Form_Common::option_get('wsf_dashboard_widget_form_id_' . $current_user_id, $forms[0]['id'], true);
				$wsf_dashboard_widget_date_range = WS_Form_Common::option_get('wsf_dashboard_widget_date_range_' . $current_user_id, 'today', true);
?>
<canvas id="wsf_dashboard_widget_chart" width="400" height="300"></canvas>

<div id="wsf_dashboard_widget_chart_error" class="wsf-alert wsf-alert-information wsf-align-center"><p><?php esc_html_e('No form statistics data available.', 'ws-form'); ?></p></div>

<form id="wsf_dashboard_widget_form">
<ul class="wsf-list-inline">
<li>
<label class="wsf-label" for="wsf_dashboard_widget_form_id">Form</label>
<select id="wsf_dashboard_widget_form_id" class="wsf-field">
<option value="0"><?php esc_html_e('All Forms', 'ws-form'); ?></option>
<?php
				foreach($forms as $form) {

?><option value="<?php echo esc_attr($form['id']); ?>"<?php if($form['id'] == $wsf_dashboard_widget_form_id) { ?> selected="selected"<?php } ?>><?php echo esc_html($form['label']); ?> (<?php esc_html_e('ID', 'ws-form'); ?>: <?php echo esc_html($form['id']); ?>)</option>
<?php
				}
?>
</select>
</li>
<li>
<label class="wsf-label" for="wsf_dashboard_widget_date_range">Date Range</label>
<select id="wsf_dashboard_widget_date_range" class="wsf-field">
<?php
				foreach($ws_form_form_stat->date_ranges as $date_range_key => $date_range) {

?><option value="<?php echo esc_attr($date_range_key); ?>"<?php if($date_range_key == $wsf_dashboard_widget_date_range) { ?> selected="selected"<?php } ?>><?php echo esc_html($date_range['label']); ?></option>
<?php
				}
?>
</select>
</li>
</ul>
</form>

<script>

	var wsf_dashboard_widget_chart = false;

	(function($) {

		'use strict';

		$(function() {

			$('#wsf_dashboard_widget_form_id, #wsf_dashboard_widget_date_range').change(function() {

				wsf_dashboard_widget_render();
			})

			// Initial render
			wsf_dashboard_widget_render();
		});

		function wsf_dashboard_widget_render() {

			// Dashboard parameters
			var form_id = parseInt($('#wsf_dashboard_widget_form_id').val());
			var date_range = $('#wsf_dashboard_widget_date_range').val();

			// Request chart data
			$.ajax({

				method: 'GET',
				url: '<?php echo esc_attr(WS_Form_Common::get_api_path('form/stat/get_chart_data/')); ?>',
				beforeSend: function(xhr) {

					xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>');
				},
				data: {

					'wsf_nonce_field_name' : '<?php echo esc_attr(WS_FORM_POST_NONCE_FIELD_NAME); ?>',
					'wsf_nonce': '<?php echo esc_attr(wp_create_nonce(WS_FORM_POST_NONCE_ACTION_NAME)); ?>',
					'form_id' 		: form_id,
					'date_range' 	: date_range,
					'random'		: '<?php echo esc_attr(wp_generate_password(12, false, false)); ?>'
				},
				statusCode: {

					// Success
					200: function(response) {

						if(
							(typeof(response.error) === 'undefined') ||
							(response.error === true) ||
							(typeof(response.config) === 'undefined')

						) { wsf_dashboard_widget_error(); return; }

						// Show chart
						$('#wsf_dashboard_widget_chart').show();
						$('#wsf_dashboard_widget_chart_error').hide();

						var chart_context = document.getElementById('wsf_dashboard_widget_chart').getContext('2d');

						// Prevent decimals on Y axis
						response.config.options.scales.yAxes[0].ticks.userCallback = function(label, index, labels) { if(Math.floor(label) === label) { return label; } };

						if(wsf_dashboard_widget_chart === false) {

							Chart.defaults.global.defaultFontFamily = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif";
							Chart.defaults.global.defaultFontSize = 13;
							wsf_dashboard_widget_chart = new Chart(chart_context, response.config);

						} else {

							// If same number of y axis elements and type the same
							if(
								(response.config.data.labels.length == wsf_dashboard_widget_chart.data.labels.length) &&
								(response.config.type == wsf_dashboard_widget_chart.config.type)
							) {

								for(var dataset_index in response.config.data.datasets) {

									var data = response.config.data.datasets[dataset_index].data;
									wsf_dashboard_widget_chart.data.datasets[dataset_index].data = data;

									var label = response.config.data.datasets[dataset_index].label;
									wsf_dashboard_widget_chart.data.datasets[dataset_index].label = label;
								}

							} else {

								// Otherwise reload
								wsf_dashboard_widget_chart.config.type = response.config.type;
								wsf_dashboard_widget_chart.data = response.config.data;
							}

							wsf_dashboard_widget_chart.options = response.config.options;
							wsf_dashboard_widget_chart.update();
						}
					}
				}
			});
		}

		function wsf_dashboard_widget_error() {

			// Show error
			$('#wsf_dashboard_widget_chart').hide();
			$('#wsf_dashboard_widget_chart_error').show();
		}

	})(jQuery);

</script>
<?php
			} else {
?>
<?php

	echo WS_Form_Config::get_logo_svg();	// phpcs:ignore
?>
<h2 class="wsf-align-center"><?php echo esc_html(sprintf(__('Welcome to %s', 'ws-form'), WS_FORM_NAME_PRESENTABLE)); ?></h2>
<p class="wsf-align-center"><?php esc_html_e('This dashboard widget will show statistics for any submissions you receive.'); ?></p>
<div class="wsf-align-center"><a class="wsf-button wsf-button-small wsf-button-information" href="<?php echo esc_attr(WS_Form_Common::get_admin_url('ws-form-add')); ?>" title="<?php esc_attr_e('Add New', 'ws-form'); ?>"><?php

	WS_Form_Common::render_icon_16_svg('plus');	// phpcs:ignore

?> <?php esc_html_e('Add New', 'ws-form'); ?></a></div>
<?php
			}
		}

		// WooCommerce
		public function ws_form_config_options_woocommerce($config) {

			// Disable config options so they cannot be changed
			$config['ecommerce']['groups']['price']['fields']['currency']['disabled'] = true;
			$config['ecommerce']['groups']['price']['fields']['currency_position']['disabled'] = true;
			$config['ecommerce']['groups']['price']['fields']['price_thousand_separator']['disabled'] = true;
			$config['ecommerce']['groups']['price']['fields']['price_decimal_separator']['disabled'] = true;
			$config['ecommerce']['groups']['price']['fields']['price_decimals']['disabled'] = true;

			// Add admin notice
			$config['ecommerce']['groups']['price']['message'] = sprintf(__('These settings are controlled by WooCommerce <a href="%s">Edit</a>', 'ws-form'), WS_Form_Common::get_admin_url('wc-settings'));

			return $config;
		}

		// Admin page - Welcome
		public function admin_page_welcome() {

			include_once 'partials/ws-form-welcome.php';
		}

		// Admin page - Form
		public function admin_page_form() {

			include_once 'partials/ws-form-form.php';
		}

		// Admin page - Form - Add
		public function admin_page_form_add() {

			include_once 'partials/ws-form-form-add.php';
		}

		// Admin page - Form - Edit
		public function admin_page_form_edit() {

			include_once 'partials/ws-form-form-edit.php';
		}

		// Admin page - Form - Submissions
		public function admin_page_form_submit() {

			include_once 'partials/ws-form-form-submit.php';
		}

		// Admin page - Form - Migrate
		public function admin_page_form_migrate() {

			include_once 'partials/ws-form-form-migrate.php';
		}

		// Admin page - Form - Delete
		public function admin_page_form_delete() {

			include_once 'partials/ws-form-form-delete.php';
		}

		// Admin page - Settings
		public function admin_page_settings() {

			include_once 'partials/ws-form-settings.php';
		}

		// Admin page - Upgrade to PRO
		public function admin_page_upgrade() {

			include_once 'partials/ws-form-upgrade.php';
		}

		// Admin page - Add-Ons
		public function admin_page_add_ons() {

			include_once 'partials/ws-form-add-ons.php';
		}
	}
