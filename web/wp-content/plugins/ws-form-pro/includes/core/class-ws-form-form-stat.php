<?php

	class WS_Form_Form_Stat extends WS_Form_Core {

		public $form_id;

		public $table_name;

		public $date_ranges;

		public function __construct() {

			global $wpdb;

			$this->table_name = $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . 'form_stat';

			add_filter('wsf_form_stat_check', array($this, 'form_stat_check'), 10, 1);
		}

		// Add form view
		public function db_add_view() {

			self::db_check_form_id();

			return self::db_add_count('view');
		}

		// Add form save
		public function db_add_save() {

			self::db_check_form_id();

			return self::db_add_count('save');
		}

		// Add form submit
		public function db_add_submit() {

			self::db_check_form_id();

			return self::db_add_count('submit');
		}

		// Add count
		public function db_add_count($type) {

			self::db_check_form_id();

			if(!apply_filters('wsf_form_stat_check', true)) { return true; };

			global $wpdb;

			$time_bounds = self::db_get_time_bounds();

			// Get existing record
			$sql = sprintf("SELECT id, count_view, count_save, count_submit FROM %s WHERE form_id = %u AND date_added >= '%s' AND date_added < '%s' LIMIT 1;", $this->table_name, $this->form_id, date('Y-m-d H:i:s', $time_bounds['start']), date('Y-m-d H:i:s', $time_bounds['finish']));
			$row = $wpdb->get_row($sql);
			if(is_null($row)) {

				// Build SQL - New record
				switch($type) {

					case 'view' :
					case 'save' :
					case 'submit' :

						$sql = sprintf("INSERT INTO %s (date_added, form_id, count_%s) VALUES ('%s', %u, 1);", $this->table_name, esc_sql($type), WS_Form_Common::get_mysql_date(), $this->form_id);
						break;

					default :

						$sql = false;
				}

				// Create record
				if($sql !== false) {

					$rows_inserted = $wpdb->query($sql);

					if($rows_inserted == 0) { parent::db_throw_error(__('Unable to insert stats record.', 'ws-form')); }
					if($rows_inserted === false) { parent::db_throw_error(__('Stats record insert failed.', 'ws-form')); }

					$this->id = $wpdb->insert_id;

				} else {

					return false;
				}

			} else {

				// Build SQL - Existing record
				$this->id = $row->id;
				
				switch($type) {

					case 'view' :
					case 'save' :
					case 'submit' :

						$sql = sprintf('UPDATE %1$s SET count_%2$s = (count_%2$s + 1) WHERE id = %3$u LIMIT 1', $this->table_name, esc_sql($type), $this->id);
						break;

					default :

						$sql = false;
				}

				// Update record
				if($sql !== false) {

					$rows_updated = $wpdb->query($sql);

					if($rows_updated == 0) { parent::db_throw_error(__('Stats record not found.', 'ws-form')); }
					if($rows_updated === false) { parent::db_throw_error(__('Stats record update failed.', 'ws-form')); }

				} else {

					parent::db_throw_error(__('Invalid stats count type.', 'ws-form'));
				}
			}

			return true;
		}

		// Delete stats records
		public function db_delete() {

			self::db_check_form_id();

			global $wpdb;

			// Delete
			$sql = sprintf("DELETE FROM %s WHERE form_id = %u;", $this->table_name, $this->form_id);
			if($wpdb->query($sql) === false) { parent::db_throw_error(__('Error deleting stats', 'ws-form')); }

			return true;
		}

		// Get counts
		public function db_get_counts() {

			self::db_check_form_id();

			global $wpdb;

			// Get totals
			$sql = sprintf("SELECT SUM(count_view) AS count_view_total, SUM(count_save) AS count_save_total, SUM(count_submit) AS count_submit_total FROM %s WHERE form_id = %u;", $this->table_name, $this->form_id);
			$row = $wpdb->get_row($sql);
			if(!is_null($row)) {

				$count_view_total = $row->count_view_total;
				$count_save_total = $row->count_save_total;
				$count_submit_total = $row->count_submit_total;

			} else {

				$count_view_total = 0;
				$count_save_total = 0;
				$count_submit_total = 0;
			}

			return array(

				'count_view' => $count_view_total,
				'count_save' => $count_save_total,
				'count_submit' => $count_submit_total
			);
		}

		// Get date data started collecting
		public function db_get_date_since() {

			self::db_check_form_id();

			global $wpdb;

			// Get totals
			$sql = sprintf("SELECT date_added FROM %s WHERE form_id = %u ORDER BY date_added LIMIT 1;", $this->table_name, $this->form_id);
			$date_added = $wpdb->get_var($sql);

			$return_value = is_null($date_added) ? false : date_i18n(get_option('date_format'), strtotime(get_date_from_gmt($date_added)));

			return $return_value;
		}

		// Get time bounds
		public function db_get_time_bounds() {

			// Get midnight to midnight of current date in GMT format
			$date_time_local_start = strtotime(get_gmt_from_date(date('Y-m-d 00:00:00', current_time('timestamp', 0))));
			$date_time_local_finish = strtotime('+1 day', $date_time_local_start);

			return(array('start' => $date_time_local_start, 'finish' => $date_time_local_finish));
		}

		// Check form_id
		public function db_check_form_id() {

			if($this->form_id <= 0) { parent::db_throw_error(__('Invalid form ID', 'ws-form')); }

			return true;
		}

		// Get chart data - By time
		public function db_get_chart_data_time($time_from_gmt = false, $time_to_gmt = false) {

			global $wpdb;

			$where_sql = '';
			$where_array = array();

			// Form ID
			if($this->form_id > 0) { $where_array[] = sprintf('form_id = %u', $this->form_id); }

			// Time from
			if($time_from_gmt !== false) { $where_array[] = sprintf('date_added >= \'%s\'', date('Y-m-d H:i:s', $time_from_gmt)); }

			// Time to
			if($time_to_gmt !== false) { $where_array[] = sprintf('date_added < \'%s\'', date('Y-m-d H:i:s', $time_to_gmt)); }

			// Build WHERE SQL
			if(count($where_array) > 0) {

				$where_sql = ' WHERE ' . implode(' AND ', $where_array);
			}

			// Get min and max date ranges
			$sql = sprintf("SELECT MIN(date_added) AS date_added_from, MAX(date_added) AS date_added_to
 FROM %s%s ORDER BY date_added;", $this->table_name, $where_sql, $this->form_id);
			$date_range_row = $wpdb->get_row($sql);
			if(is_null($date_range_row)) { return false; }
			if(is_null($date_range_row->date_added_from)) { return false; }
			if(is_null($date_range_row->date_added_to)) { return false; }

			// Get from and to

			// If a from date is specified, the date start should be that date
			if($time_from_gmt !== false) {

				$date_added_from = $time_from_gmt;

			} else {

				$date_added_from = strtotime($date_range_row->date_added_from);
			}
			if($time_to_gmt !== false) {

				$date_added_to = $time_to_gmt;

			} else {

				$date_added_to = current_time('timestamp', 1);
			}

			// Get form stat data
			$sql = sprintf("SELECT date_added, count_view, count_save, count_submit FROM %s%s ORDER BY date_added;", $this->table_name, $where_sql, $this->form_id);
			$form_stats = $wpdb->get_results($sql);
			if(is_null($form_stats)) { return false; }

			// Build form stat array
			$count_view_total = 0;
			$count_save_total = 0;
			$count_submit_total = 0;
			$form_stat_array = array();
			foreach($form_stats as $form_stat) {

				$date_added_local = get_date_from_gmt($form_stat->date_added, 'Y-m-d');
				if(isset($form_stat_array[$date_added_local])) {

					// Accumulate
					$form_stat_array[$date_added_local]->count_view += $form_stat->count_view;
					$form_stat_array[$date_added_local]->count_save += $form_stat->count_save;
					$form_stat_array[$date_added_local]->count_submit += $form_stat->count_submit;

				} else {

					// Initial
					$form_stat_array[$date_added_local] = $form_stat;
				}

				// Totals
				$count_view_total += $form_stat->count_view;
				$count_save_total += $form_stat->count_save;
				$count_submit_total += $form_stat->count_submit;
			}

			$date_added_from_local = get_date_from_gmt(date('Y-m-d H:i:s', $date_added_from), 'Y-m-d');
			$date_added_to_local = get_date_from_gmt(date('Y-m-d H:i:s', $date_added_to), 'Y-m-d');

			// Build final data
			$chart_data_labels = array();
			$chart_data_dataset_count_view = array();
			$day_index = 0;
			do {

				// Convert date in database to local time
				$date_added_current_local = date('Y-m-d', strtotime($date_added_from_local) + ($day_index * 86400));

				// Add label
				$chart_data_labels[] = date('M j', strtotime($date_added_current_local));

				// Build datasets
				if(isset($form_stat_array[$date_added_current_local])) {

					$form_stat = $form_stat_array[$date_added_current_local];
					$chart_data_dataset_count_view[] = $form_stat->count_view;
					$chart_data_dataset_count_save[] = $form_stat->count_save;
					$chart_data_dataset_count_submit[] = $form_stat->count_submit;

				} else {

					$chart_data_dataset_count_view[] = 0;
					$chart_data_dataset_count_save[] = 0;
					$chart_data_dataset_count_submit[] = 0;
				}

				$day_index++;

			} while($date_added_current_local != $date_added_to_local);

			// Build final data
			$chart_data = array(

				'labels' => $chart_data_labels,

				'datasets' => array(

					array(

						'label' 			=> sprintf('%s (%s)', __('Submissions', 'ws-form'), number_format($count_submit_total)),
						'borderColor' 		=> '#002E5F',
						'borderWidth' 		=> 2,
						'pointRadius' 		=> 2,
						'backgroundColor' 	=> 'rgba(0, 46, 95, 0.5)',
						'fill' 				=> true,
						'data' 				=> $chart_data_dataset_count_submit,
						'pointRadius'		=> 1,
						'pointHitRadius'	=> 5
					),

					array(

						'label' 			=> sprintf('%s (%s)', __('Saves', 'ws-form'), number_format($count_save_total)),
						'borderColor' 		=> '#2A9E1A',
						'borderWidth' 		=> 2,
						'pointRadius' 		=> 2,
						'backgroundColor' 	=> 'rgba(42, 158, 26, 0.25)',
						'fill' 				=> true,
						'data' 				=> $chart_data_dataset_count_save,
						'pointRadius'		=> 1,
						'pointHitRadius'	=> 5
					),

					array(

						'label' 			=> sprintf('%s (%s)', __('Views', 'ws-form'), number_format($count_view_total)),
						'borderColor' 		=> '#39D',
						'borderWidth' 		=> 2,
						'pointRadius' 		=> 2,
						'backgroundColor' 	=> 'rgba(51, 153, 221, 0.25)',
						'fill' 				=> true,
						'data' 				=> $chart_data_dataset_count_view,
						'pointRadius'		=> 1,
						'pointHitRadius'	=> 5
					)
				)
			);

			return $chart_data;
		}

		// Get chart data - By totals
		public function db_get_chart_data_total($time_from_gmt = false, $time_to_gmt = false) {

			global $wpdb;

			$where_sql = '';
			$where_array = array();

			// Form ID
			if($this->form_id > 0) { $where_array[] = sprintf('form_id = %u', $this->form_id); }

			// Time from
			if($time_from_gmt !== false) { $where_array[] = sprintf('date_added >= \'%s\'', date('Y-m-d H:i:s', $time_from_gmt)); }

			// Time to
			if($time_to_gmt !== false) { $where_array[] = sprintf('date_added < \'%s\'', date('Y-m-d H:i:s', $time_to_gmt)); }

			// Build WHERE SQL
			if(count($where_array) > 0) {

				$where_sql = ' WHERE ' . implode(' AND ', $where_array);
			}

			// Get form stat data
			$sql = sprintf("SELECT SUM(count_view) AS count_view, SUM(count_save) AS count_save, SUM(count_submit) AS count_submit FROM %s%s ORDER BY date_added;", $this->table_name, $where_sql, $this->form_id);
			$form_stats = $wpdb->get_row($sql);
			if(is_null($form_stats)) { return false; }

			// Build form stat array
			$count_view_total = $form_stats->count_view;
			$count_save_total = $form_stats->count_save;
			$count_submit_total = $form_stats->count_submit;

			if(
				($count_view_total == 0) &&
				($count_save_total == 0) &&
				($count_submit_total == 0)

			) { return false; }

			// Build final data
			$chart_data = array(

				'labels' => array(__('Total Counts', 'ws-form')),

				'datasets' => array(

					array(

						'label' 			=> sprintf('%s (%s)', __('Submissions', 'ws-form'), number_format($count_submit_total)),
						'borderColor' 		=> '#002E5F',
						'borderWidth' 		=> 2,
						'pointRadius' 		=> 2,
						'backgroundColor' 	=> 'rgba(0, 46, 95, 0.5)',
						'fill' 				=> true,
						'data' 				=> array($count_submit_total)
					),

					array(

						'label' 			=> sprintf('%s (%s)', __('Saves', 'ws-form'), number_format($count_save_total)),
						'borderColor' 		=> '#2A9E1A',
						'borderWidth' 		=> 2,
						'pointRadius' 		=> 2,
						'backgroundColor' 	=> 'rgba(42, 158, 26, 0.25)',
						'fill' 				=> true,
						'data' 				=> array($count_save_total)
					),

					array(

						'label' 			=> sprintf('%s (%s)', __('Views', 'ws-form'), number_format($count_view_total)),
						'borderColor' 		=> '#39D',
						'borderWidth' 		=> 2,
						'pointRadius' 		=> 2,
						'backgroundColor' 	=> 'rgba(51, 153, 221, 0.25)',
						'fill' 				=> true,
						'data' 				=> array($count_view_total)
					)
				)
			);

			return $chart_data;
		}

		// Check to see whether stat record should be created
		public function form_stat_check($return_value = true) {

			// Do not log if stats are disabled
			if(WS_Form_Common::option_get('disable_form_stats')) { return false; }

			// Do not log if superadmin, admin, author, editor, contributor
			if(current_user_can('edit_posts')) { return false; }

			return $return_value;
		}

		// Build date ranges
		public function date_ranges_init() {

			$this->date_ranges = array(

				'today'	=>	array(

					'label' 	=> __('Today', 'ws-form'),
					'time_from'	=> '0 days',
					'time_to'	=> '0 days',
					'type'		=> 'bar',
					'data'		=> 'total'
				),

				'yesterday'	=>	array(

					'label' 	=> __('Yesterday', 'ws-form'),
					'time_from'	=> '-1 days',
					'time_to'	=> '-1 days',
					'type'		=> 'bar',
					'data'		=> 'total'
				),

				'last_7_days'	=>	array(

					'label' 	=> __('Last 7 Days', 'ws-form'),
					'time_from'	=> '-7 days',
					'time_to'	=> '-1 day',
					'type'		=> 'line',
					'data'		=> 'time'
				),

				'last_30_days'	=>	array(

					'label' 	=> __('Last 30 Days', 'ws-form'),
					'time_from'	=> '-30 days',
					'time_to'	=> '-1 day',
					'type'		=> 'line',
					'data'		=> 'time'
				),

				'last_60_days'	=>	array(

					'label' 	=> __('Last 60 Days', 'ws-form'),
					'time_from'	=> '-60 days',
					'time_to'	=> '-1 day',
					'type'		=> 'line',
					'data'		=> 'time'
				),

				'last_90_days'	=>	array(

					'label' 	=> __('Last 90 Days', 'ws-form'),
					'time_from'	=> '-90 days',
					'time_to'	=> '-1 day',
					'type'		=> 'line',
					'data'		=> 'time'
				),

				'month_to_date'	=>	array(

					'label' 	=> __('Month To Date', 'ws-form'),
					'time_from'	=> 'first day of this month',
					'time_to'	=> false,
					'type'		=> 'line',
					'data'		=> 'time'
				),

				'last_month'	=>	array(

					'label' 	=> __('Last Month', 'ws-form'),
					'time_from'	=> 'first day of last month',
					'time_to'	=> 'last day of last month',
					'type'		=> 'line',
					'data'		=> 'time'
				),

				'year_to_date'	=>	array(

					'label' 	=> __('Year To Date', 'ws-form'),
					'time_from'	=> 'first day of january',
					'time_to'	=> false,
					'type'		=> 'line',
					'data'		=> 'time'
				),

				'last_year'	=>	array(

					'label' 	=> __('Last Year', 'ws-form'),
					'time_from'	=> 'first day of january last year',
					'time_to'	=> 'last day of december last year',
					'type'		=> 'line',
					'data'		=> 'time'
				)
			);
		}
	}
