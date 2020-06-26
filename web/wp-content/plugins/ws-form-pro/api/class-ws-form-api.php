<?php

	class WS_Form_API {

		public $error;
		public $error_message;
		public $error_code;

		protected $loader;

		public function __construct() {

			$this->error = false;
			$this->error_message = '';
			$this->error_code = 0;
		}

		// Throw API error
		public function api_throw_error($message, $error_code = 400) {

			$this->error = true;
			$this->error_message = $message;
			$this->error_code = $error_code;

			$this->api_json_response();
		}

		// Handle JSON response
		public function api_json_response($data = false, $form_id = 0, $history = false, $form_full = true, $form_published = false, $form_parse = false) {

			$json_array = [];

			// Set error
			$json_array['error'] = $this->error;

			// New data
			if($data !== false) { $json_array['data'] = $data; }

			// Set HTTP content type head
			header('Content-Type: application/json');

			// API error
			if($this->error) {

				// Set error message
				$json_array['error_message'] = $this->error_message;

				// Set response code
				switch($this->error_code) {

					case '403' :

						header('HTTP/1.1 403 Forbidden', true, 403);
						break;

					case '404' :

						header('HTTP/1.1 404 Not Found', true, 403);
						break;

					default :

						header('HTTP/1.1 400 Bad Request', true, 400);
				}
			}

			if($form_id > 0) {

				// Build form array
				$ws_form_form = New WS_Form_Form();
				$ws_form_form->id = $form_id;

				try {

					if($form_published) {

						// Published
						$json_array['form'] = $ws_form_form->db_read_published($form_parse);

					} else {

						// Draft
						$json_array['form'] = $ws_form_form->db_read($form_full, $form_full, false, $form_parse);
					}

				} catch (Exception $e) {

					self::api_throw_error($e->getMessage());
				}

				$json_array['form_full'] = $form_full || $form_published;

				if(is_array($history)) {

					// History data array (Describes what changed), or false for do not store in history
					$history['date'] = date(get_option('date_format'), current_time('timestamp'));
					$history['time'] = date(get_option('time_format'), current_time('timestamp'));
					$json_array['history'] = $history;
				}
			}

			// Push JSON response
			$json_return = wp_json_encode($json_array);
			if(json_last_error() !== 0) {

				// Set response code
				header('HTTP/1.1 400 Bad Request', true, 400);

				// Build error JSON
				$json_array = array(

					'error' => 			true,
					'error_message' =>	'JSON encoding error: ' . json_last_error_msg() . ' (' . json_last_error() . ')'
				);

				echo wp_json_encode($json_array);
				exit;
			}

			echo $json_return; // phpcs:ignore

			// Stop execution
			exit;
		}

		// Set up RESTful API
		public function api_rest_api_init() {

			/* API - Config */
			$plugin_api_config = new WS_Form_API_Config();
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/config/', array('methods' => 'GET', 'callback' => array($plugin_api_config, 'api_get')));

			/* API - Helper */
			$plugin_api_helper = new WS_Form_API_Helper();
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/test/', array('methods' => 'GET', 'callback' => array($plugin_api_helper, 'api_test')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/system/', array('methods' => 'GET', 'callback' => array($plugin_api_helper, 'api_system')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/intro/', array('methods' => 'GET', 'callback' => array($plugin_api_helper, 'api_intro')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/framework_detect/', array('methods' => 'POST', 'callback' => array($plugin_api_helper, 'api_framework_detect')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/setup_push/', array('methods' => 'POST', 'callback' => array($plugin_api_helper, 'api_setup_push')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/ws_form_css/', array('methods' => 'GET', 'callback' => array($plugin_api_helper, 'api_ws_form_css')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/ws_form_css_skin/', array('methods' => 'GET', 'callback' => array($plugin_api_helper, 'api_ws_form_css_skin')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/ws_form_css_skin_rtl/', array('methods' => 'GET', 'callback' => array($plugin_api_helper, 'api_ws_form_css_skin_rtl')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/ws_form_css_admin/', array('methods' => 'GET', 'callback' => array($plugin_api_helper, 'api_ws_form_css_admin')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/css_email/', array('methods' => 'GET', 'callback' => array($plugin_api_helper, 'api_css_email')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/file_download/', array('methods' => 'GET', 'callback' => array($plugin_api_helper, 'api_file_download')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/user_meta_hidden_column/', array('methods' => 'POST', 'callback' => array($plugin_api_helper, 'api_user_meta_hidden_columns')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/support_contact_submit/', array('methods' => 'POST', 'callback' => array($plugin_api_helper, 'api_support_contact_submit')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/deactivate_feedback_submit/', array('methods' => 'POST', 'callback' => array($plugin_api_helper, 'api_deactivate_feedback_submit')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/count_submit_unread/', array('methods' => 'GET', 'callback' => array($plugin_api_helper, 'api_count_submit_unread')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/review_nag/dismiss/', array('methods' => 'POST', 'callback' => array($plugin_api_helper, 'api_review_nag_dismiss')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/helper/shortcode/', array('methods' => 'POST', 'callback' => array($plugin_api_helper, 'api_review_nag_dismiss')));

			/* API - Form */
			$plugin_api_form = new WS_Form_API_Form();
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/submit/', array('methods' => 'POST', 'callback' => array($plugin_api_form, 'api_submit')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/download/json/', array('methods' => 'POST', 'callback' => array($plugin_api_form, 'api_post_download_json')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/upload/json/', array('methods' => 'POST', 'callback' => array($plugin_api_form, 'api_post_upload_json')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/full/', array('methods' => 'GET', 'callback' => array($plugin_api_form, 'api_get_full')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/published/', array('methods' => 'GET', 'callback' => array($plugin_api_form, 'api_get_published')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/checksum/', array('methods' => 'GET', 'callback' => array($plugin_api_form, 'api_get_checksum')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/full/', array('methods' => 'PUT', 'callback' => array($plugin_api_form, 'api_put_full')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/', array('methods' => 'PUT', 'callback' => array($plugin_api_form, 'api_put')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/publish/', array('methods' => 'PUT', 'callback' => array($plugin_api_form, 'api_put_publish')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/draft/', array('methods' => 'PUT', 'callback' => array($plugin_api_form, 'api_put_draft')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/locations/', array('methods' => 'GET', 'callback' => array($plugin_api_form, 'api_get_locations')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/svg/draft/', array('methods' => 'GET', 'callback' => array($plugin_api_form, 'api_get_svg_draft')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/svg/published/', array('methods' => 'GET', 'callback' => array($plugin_api_form, 'api_get_svg_published')));

			/* API - Form Stats */
			$plugin_api_form_stat = new WS_Form_API_Form_Stat();
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/stat/get_chart_data/', array('methods' => 'GET', 'callback' => array($plugin_api_form_stat, 'api_get_chart_data')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/form/(?P<form_id>[\d]+)/stat/add_view/', array('methods' => 'POST', 'callback' => array($plugin_api_form_stat, 'api_add_view')));

			/* API - Group */
			$plugin_api_group = new WS_Form_API_Group();
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/group/', array('methods' => 'POST', 'callback' => array($plugin_api_group, 'api_post')));

			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/group/(?P<group_id>[\d]+)/', array('methods' => 'PUT', 'callback' => array($plugin_api_group, 'api_put')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/group/(?P<group_id>[\d]+)/sort_index/', array('methods' => 'PUT', 'callback' => array($plugin_api_group, 'api_put_sort_index')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/group/(?P<group_id>[\d]+)/clone/', array('methods' => 'PUT', 'callback' => array($plugin_api_group, 'api_put_clone')));

			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/group/(?P<group_id>[\d]+)/', array('methods' => 'DELETE', 'callback' => array($plugin_api_group, 'api_delete')));

			/* API - Section */
			$plugin_api_section = new WS_Form_API_Section();
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/section/', array('methods' => 'POST', 'callback' => array($plugin_api_section, 'api_post')));

			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/section/(?P<section_id>[\d]+)/', array('methods' => 'PUT', 'callback' => array($plugin_api_section, 'api_put')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/section/(?P<section_id>[\d]+)/sort_index/', array('methods' => 'PUT', 'callback' => array($plugin_api_section, 'api_put_sort_index')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/section/(?P<section_id>[\d]+)/clone/', array('methods' => 'PUT', 'callback' => array($plugin_api_section, 'api_put_clone')));

			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/section/(?P<section_id>[\d]+)/', array('methods' => 'DELETE', 'callback' => array($plugin_api_section, 'api_delete')));

			/* API - Field */
			$plugin_api_field = new WS_Form_API_Field();
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/field/', array('methods' => 'POST', 'callback' => array($plugin_api_field, 'api_post')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/field/(?P<field_id>[\d]+)/upload/csv/', array('methods' => 'POST', 'callback' => array($plugin_api_field, 'api_post_upload_csv')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/field/(?P<field_id>[\d]+)/download/csv/', array('methods' => 'POST', 'callback' => array($plugin_api_field, 'api_post_download_csv')));

			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/field/(?P<field_id>[\d]+)/', array('methods' => 'PUT', 'callback' => array($plugin_api_field, 'api_put')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/field/(?P<field_id>[\d]+)/sort_index/', array('methods' => 'PUT', 'callback' => array($plugin_api_field, 'api_put_sort_index')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/field/(?P<field_id>[\d]+)/clone/', array('methods' => 'PUT', 'callback' => array($plugin_api_field, 'api_put_clone')));

			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/field/(?P<field_id>[\d]+)/', array('methods' => 'DELETE', 'callback' => array($plugin_api_field, 'api_delete')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/field/(?P<field_id>[\d]+)/', array('methods' => 'GET', 'callback' => array($plugin_api_field, 'api_get')));

			/* API - Submit */
			$plugin_api_submit = new WS_Form_API_Submit();
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/submit/', array('methods' => 'POST', 'callback' => array($plugin_api_submit, 'api_post')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/submit/(?P<submit_id>[\d]+)/action/', array('methods' => 'POST', 'callback' => array($plugin_api_submit, 'api_repost')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/submit/(?P<submit_id>[\d]+)/', array('methods' => 'PUT', 'callback' => array($plugin_api_submit, 'api_put')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/submit/download/csv/', array('methods' => 'POST', 'callback' => array($plugin_api_submit, 'api_post_download_csv')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/submit/(?P<submit_id>[\d]+)/', array('methods' => 'GET', 'callback' => array($plugin_api_submit, 'api_get')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/submit/hash/(?P<wsf_hash>[a-zA-Z0-9]+)/', array('methods' => 'GET', 'callback' => array($plugin_api_submit, 'api_get_by_hash')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/submit/(?P<submit_id>[\d]+)/starred/on/', array('methods' => 'PUT', 'callback' => array($plugin_api_submit, 'api_put_starred_on')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/submit/(?P<submit_id>[\d]+)/starred/off/', array('methods' => 'PUT', 'callback' => array($plugin_api_submit, 'api_put_starred_off')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/submit/(?P<submit_id>[\d]+)/viewed/on/', array('methods' => 'PUT', 'callback' => array($plugin_api_submit, 'api_put_viewed_on')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/submit/(?P<submit_id>[\d]+)/viewed/off/', array('methods' => 'PUT', 'callback' => array($plugin_api_submit, 'api_put_viewed_off')));

			/* API - Wizard */
			$plugin_api_wizard = new WS_Form_API_Wizard();
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/wizard/action/', array('methods' => 'GET', 'callback' => array($plugin_api_wizard, 'api_get_actions')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/wizard/action/(?P<action_id>[a-zA-Z0-9]+)/', array('methods' => 'GET', 'callback' => array($plugin_api_wizard, 'api_get_action_wizards')));
			/* API - Migrate */
			$plugin_api_migrate = new WS_Form_API_Migrate();
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/migrate/field_mapping/', array('methods' => 'POST', 'callback' => array($plugin_api_migrate, 'api_field_mapping')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/migrate/submission/import/', array('methods' => 'POST', 'callback' => array($plugin_api_migrate, 'api_submission_import')));
			register_rest_route(WS_FORM_RESTFUL_NAMESPACE, '/migrate/form/import/', array('methods' => 'POST', 'callback' => array($plugin_api_migrate, 'api_form_import')));
		}

		// Access denied
		public function api_access_denied() {

			echo wp_json_encode(array('edition' => WS_FORM_EDITION, 'error' => true, 'error_message' => __('Access denied', 'ws-form')));
			exit;
		}
	}
