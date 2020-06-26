<?php

	class WS_Form_API_Form_Stat extends WS_Form_API {

		public function __construct() {

			// Call parent on WS_Form_API
			parent::__construct();
		}

		// API - Get chart data - Time based
		public function api_get_chart_data($parameters) {

			$ws_form_form_stat = new WS_Form_Form_Stat();
			$ws_form_form_stat->date_ranges_init();

			$current_user_id = get_current_user_id();

			// User capability check
			if(!WS_Form_Common::can_user('manage_options_wsform')) { parent::api_access_denied(); }

			// Get form ID
			$form_id = intval(WS_Form_Common::get_query_var_nonce('form_id', '', $parameters));

			// Save form ID
			WS_Form_Common::option_set('wsf_dashboard_widget_form_id_' . $current_user_id, $form_id);

			// Date range
			$date_range = WS_Form_Common::get_query_var_nonce('date_range', '', $parameters);
			if(!isset($ws_form_form_stat->date_ranges[$date_range])) { self::api_throw_error(__('Invalid date range', 'ws-form')); }

			// Save date range
			WS_Form_Common::option_set('wsf_dashboard_widget_date_range_' . $current_user_id, $date_range);

			// Build time from and to
			$time_from_gmt = ($ws_form_form_stat->date_ranges[$date_range]['time_from'] !== false) ? strtotime(get_gmt_from_date(date('Y-m-d 00:00:00', strtotime($ws_form_form_stat->date_ranges[$date_range]['time_from'])))) : false;
			$time_to_gmt = ($ws_form_form_stat->date_ranges[$date_range]['time_to'] !== false) ? strtotime(get_gmt_from_date(date('Y-m-d 23:59:59', strtotime($ws_form_form_stat->date_ranges[$date_range]['time_to'])))) : false;

			// Get data
			$ws_form_form_stat->form_id = $form_id;

			// Get data type
			$data_type = $ws_form_form_stat->date_ranges[$date_range]['data'];
			switch($data_type) {

				case 'time' :

					$chart_data = $ws_form_form_stat->db_get_chart_data_time($time_from_gmt, $time_to_gmt);
					break;

				case 'total' :

					$chart_data = $ws_form_form_stat->db_get_chart_data_total($time_from_gmt, $time_to_gmt);
					break;
			}

			// Get form data
			if($form_id == 0) {

				$title = __('All Forms', 'ws-form');

			} else {

				$ws_form_form = new WS_Form_Form();
				$ws_form_form->id = $form_id;
				$form_object = $ws_form_form->db_read(false);

				$title = $form_object->label;
			}

			// Add label to title
			$title .= ' - ' . $chart_type = $ws_form_form_stat->date_ranges[$date_range]['label'];

			$chart_type = $ws_form_form_stat->date_ranges[$date_range]['type'];

			// Return data
			if($chart_data === false) {

				echo wp_json_encode(array('error' => true));

			} else {

				$config = array(

					'type'	=> $chart_type,

					'data'	=> $chart_data,

					'options' => array(

						'responsive' => true,

						'title' => array(

							'display' => true,
							'text' => $title
						),

						'scales' => array(

							'xAxes' => array(

								array(

									'display' 		=> true,
									'scaleLabel' 	=> ($chart_type == 'line') ? array(

										'display' 		=> true,
										'labelString' 	=> __('Date', 'ws-form')
									) : false
								)
							),

							'yAxes' => array(

								array(

									'display' 		=> true,
									'scaleLabel' 	=> array(

										'display' 		=> true,
										'labelString' 	=> __('Count', 'ws-form')
									),
									'ticks' => array(

										'beginAtZero'	=> true
									)
								)
							)
						)
					)
				);

				if($chart_type == 'line') {

					$config['options']['tooltips'] = array(

						'mode' => 'index',
						'intersect' => false,
					);

					$config['options']['hover'] = array(

						'mode' => 'nearest',
						'intersect' =>true
					);
				}

				echo wp_json_encode(array('error' => false, 'config' => $config));
			}

			exit;
		}

		// API - Get chart data - Time based
		public function api_get_chart_data_total($parameters) {

			$ws_form_form_stat = new WS_Form_Form_Stat();
			$ws_form_form_stat->date_ranges_init();

			$current_user_id = get_current_user_id();

			// User capability check
			if(!WS_Form_Common::can_user('manage_options_wsform')) { parent::api_access_denied(); }

			// Get form ID
			$form_id = intval(WS_Form_Common::get_query_var_nonce('form_id', '', $parameters));

			// Save form ID
			WS_Form_Common::option_set('wsf_dashboard_widget_form_id_' . $current_user_id, $form_id);

			// Date range
			$date_range = WS_Form_Common::get_query_var_nonce('date_range', '', $parameters);
			if(!isset($ws_form_form_stat->date_ranges[$date_range])) { self::api_throw_error(__('Invalid date range', 'ws-form')); }

			// Save date range
			WS_Form_Common::option_set('wsf_dashboard_widget_date_range_' . $current_user_id, $date_range);

			// Build time from and to
			$time_from_gmt = ($ws_form_form_stat->date_ranges[$date_range]['time_from'] !== false) ? strtotime(get_gmt_from_date(date('Y-m-d 00:00:00', strtotime($ws_form_form_stat->date_ranges[$date_range]['time_from'])))) : false;
			$time_to_gmt = ($ws_form_form_stat->date_ranges[$date_range]['time_to'] !== false) ? strtotime(get_gmt_from_date(date('Y-m-d 23:59:59', strtotime($ws_form_form_stat->date_ranges[$date_range]['time_to'])))) : false;

			// Get type
			$type = WS_Form_Common::get_query_var_nonce('type', 'line', $parameters);
			if(!in_array($type, array('line'))) { self::api_throw_error(__('Invalid chart type', 'ws-form')); }

			// Get data
			$ws_form_form_stat->form_id = $form_id;
			$chart_data = $ws_form_form_stat->db_get_chart_data_time($time_from_gmt, $time_to_gmt);

			// Get form data
			if($form_id == 0) {

				$title = __('All Forms', 'ws-form');

			} else {

				$ws_form_form = new WS_Form_Form();
				$ws_form_form->id = $form_id;
				$form_object = $ws_form_form->db_read(false);

				$title = $form_object->label;
			}

			// Return data
			if($chart_data === false) {

				echo wp_json_encode(array('error' => true));

			} else {

				$config = array(

					'type'	=> $type,

					'data'	=> $chart_data,

					'options' => array(

						'responsive' => true,

						'title' => array(
							'display' => true,
							'text' => $title
						),

						'tooltips' => array(
							'mode' => 'index',
							'intersect' => false,
						),

						'hover' => array(
							'mode' => 'nearest',
							'intersect' =>true
						),

						'scales' => array(

							'xAxes' => array(

								array(

									'display' 		=> true,
									'scaleLabel' 	=> array(

										'display' 		=> true,
										'labelString' 	=> __('Date', 'ws-form')
									)
								)
							),

							'yAxes' => array(

								array(

									'display' 		=> true,
									'scaleLabel' 	=> array(

										'display' 		=> true,
										'labelString' 	=> __('Count', 'ws-form')
									)
								)
							)
						)
					)
				);

				echo wp_json_encode(array('error' => false, 'config' => $config));
			}

			exit;
		}

		public function api_add_view($parameters) {

			$ws_form_form_stat = new WS_Form_Form_Stat();

			// Get form ID
			$form_id = intval(WS_Form_Common::get_query_var_nonce('form_id', '', $parameters));

			// Log view
			$ws_form_stat = new WS_Form_Form_Stat();
			$ws_form_stat->form_id = $form_id;
			$ws_form_stat->db_add_view();

			return array('error' => false);
		}
	}
