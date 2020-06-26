<?php

	class WS_Form_WP_List_Table_Submit extends WP_List_Table_WS_Form {

		public $form_id;

		public $date_from;
		public $date_to;

		public $submit_fields = false;
		public $field_data_cache = false;

		public $record_count = false;

		// Construct
	    public function __construct() {

			parent::__construct(array(

				'singular'		=> __('Submission', 'ws-form'),		// Singular label
				'plural'		=> __('Submissions', 'ws-form'),	// Plural label, also this well be one of the table css class
				'ajax'			=> false 							// We won't support Ajax for this table
			));

			// Set primary column
			add_filter('list_table_primary_column',[$this, 'list_table_primary_column'], 10, 2);

			// Get the form ID
			$this->form_id = intval(WS_Form_Common::get_query_var('id'));

			// Filters
			$this->date_from = WS_Form_Common::get_query_var('date_from');
			$this->date_to = WS_Form_Common::get_query_var('date_to');

			// Initialize submit fields
			$this->submit_fields = array();
			$this->field_data_cache = array();

			if($this->form_id > 0) {

				$ws_form_submit = new WS_Form_Submit;
				$ws_form_submit->form_id = $this->form_id;

				$submit_fields = $ws_form_submit->db_get_submit_fields();

				$ws_form_field = New WS_Form_Field();

				if($submit_fields !== false) {

					foreach($submit_fields as $id => $field) {

						$this->submit_fields[$id] = $field['label'];

						$ws_form_field->id = $id;
						$field_object = $ws_form_field->db_read(true);
						$this->field_data_cache[$id] = $field_object;
					}
				}
			}
	    }

	    // Get columns
		public function get_columns() {

			// Initial columns
  		  	$columns = [

				'cb'			=> '<input type="checkbox" />',
				'media'			=> '<div class="wsf-starred wsf-starred-header">' . WS_Form_Config::get_icon_16_svg('rating') . '</div>',
				'id'			=> __('ID', 'ws-form'),
				'status'		=> __('Status', 'ws-form'),
			];

			// Add form fields as columns (Only those that are saved on submit)
			foreach($this->submit_fields as $key => $label) {

				$columns[WS_FORM_FIELD_PREFIX . $key] = strip_tags($label);
			}

			// Add date added
			$columns['date_updated']	= __('Date Updated', 'ws-form');
			$columns['date_added']		= __('Date Added', 'ws-form');

			return $columns;
		}

		// Get sortable columns
		public function get_sortable_columns() {

			$sortable_columns = array(

				'media'		=> array('starred', true),			// Used 'media' as opposed to 'starred' because WordPress considers that a special keyword and excludes it from the screen options column 
				'id'			=> array('id', true),
				'status'		=> array('status', true),
				'date_added'	=> array('date_added', true),
				'date_updated'	=> array('date_updated', true),
			);

			// Add form fields as sortable columns (Only those that are saved on submit)
			foreach($this->submit_fields as $key => $label) {

				$sortable_columns[WS_FORM_FIELD_PREFIX . $key] = array(WS_FORM_FIELD_PREFIX . $key, true);
			}

			return $sortable_columns;
		}

		// Column - Rating
		public function _column_media($item) {

			$starred_title = ($item['starred']) ? __('Starred', 'ws-form') : __('Not Starred', 'ws-form');
			$starred_class = ($item['starred']) ? ' wsf-starred-on' : '';

			$return_html = '<th scope="row" class="manage-column column-is_active"><div data-id="' . $item['id'] . '" data-action-ajax="wsf-submit-starred" class="wsf-starred' . $starred_class . '" title="'. $starred_title . '">' . WS_Form_Config::get_icon_16_svg('rating') . '</div></th>';

			return $return_html;
		}

		// Column - Default
		public function column_default($submit, $column_name) {

			if(!isset($submit[$column_name])) { return ''; }

			// Get field data
			$field = $submit[$column_name];

			// Check field
			if(!is_array($field)) { return $field; }	// Plain text return
			if($field['value'] === '') { return ''; }

			// Get field ID
			$field_id = $field['id'];

			// Get field type
			$field_type = $field['type'];

			// Row delimiter
			$submit_delimiter_row = WS_FORM_SECTION_REPEATABLE_DELIMTIER_SUBMIT;

			// Get section repeatable index
			$index = false;
			$delimiter_row = WS_FORM_SECTION_REPEATABLE_DELIMITER_ROW;
			if(
				isset($submit['section_repeatable']) &&
				isset($field['section_id'])
			) {

				$section_id = intval($field['section_id']);

				if(
					($section_id > 0) &&
					isset($submit['section_repeatable']['section_' . $section_id])
				) {

					$index = isset($submit['section_repeatable']['section_' . $section_id]['index']) ? $submit['section_repeatable']['section_' . $section_id]['index'] : array();
					$delimiter_row = isset($submit['section_repeatable']['section_' . $section_id]['delimiter_row']) ? $submit['section_repeatable']['section_' . $section_id]['delimiter_row'] : WS_FORM_SECTION_REPEATABLE_DELIMITER_ROW;
				}
			}

			// Get values_array
			if($index === false) {

				$values_array = array($field['value']);

			} else {

				$values_array = array();
				foreach($index as $index_single) {

					if(
						isset($submit[$column_name . '_' . $index_single]) &&
						isset($submit[$column_name . '_' . $index_single]['value'])
					) {
						$value = $submit[$column_name . '_' . $index_single]['value'];
						if($value) { $values_array[] = $value; }
					}
				}
			}

			switch($field_type) {

				case 'signature' :
				case 'file' :

					$value = implode($submit_delimiter_row, array_map(function($files) use ($submit, $field_id) {

						$files_html = '';

						if(is_array($files)) {

							foreach($files as $file_index => $file) {

								if(!isset($file['type'])) { continue; }
								if(!isset($file['name'])) { continue; }

								$files_html .= self::file_html($submit['hash'], $field_id, $file_index, $file['type'], $file['name']);
							}
						}

						return $files_html;

					}, $values_array));

					break;

				case 'datetime' :

					$value = implode($submit_delimiter_row, array_map(function($datetime) { return isset($datetime['presentation_full']) ? $datetime['presentation_full'] : ''; }, $values_array));
					break;


				case 'tel' :

					$value = implode($submit_delimiter_row, array_map(function($tel) { return sprintf('<a href="tel:%s">%s</a>', WS_Form_Common::get_tel($tel), $tel); }, $values_array));
					break;

				case 'email' :

					$value = implode($submit_delimiter_row, array_map(function($email) { return sprintf('<a href="mailto:%1$s">%1$s</a>', $email); }, $values_array));
					break;

				case 'url' :

					$value = implode($submit_delimiter_row, array_map(function($url) { return sprintf('<a href="%1$s" target="_blank">%1$s</a>', $url); }, $values_array));
					break;

				case 'rating' :

					$rating_max = WS_Form_Common::get_object_meta_value($this->field_data_cache[$field_id], 'rating_max', 5);
					if(!is_numeric($rating_max)) { $rating_max = 5; }
					if($rating_max < 1) { $rating_max = 1; }

					$value = implode($submit_delimiter_row, array_map(function($rating) use ($rating_max) {

						if(($rating >= 0) && ($rating <= $rating_max)) {

							$value = '<ul class="wsf-submit-rating wsf-list-inline">';

							for($rating_index = 0; $rating_index < $rating_max; $rating_index++) {

								$rating_class = ($rating_index < $rating) ? ' class="wsf-submit-rating-on"' : '';

								$value .= '<li' . $rating_class . '>' . WS_Form_Config::get_icon_16_svg('rating') . '</li>';
							}

							$value .= '</ul>';

						} else {

							$value = $rating;
						}

						return $value;

					}, $values_array));

					break;

				case 'range' :

					$min = WS_Form_Common::get_object_meta_value($this->field_data_cache[$field_id], 'min', 0);
					if(!is_numeric($min)) { $min = 0; }
					$max = WS_Form_Common::get_object_meta_value($this->field_data_cache[$field_id], 'max', 100);
					if(!is_numeric($max)) { $max = 100; }

					$value = implode($submit_delimiter_row, array_map(function($range) use ($min, $max) {

						if($range >= 1 && (($max - $min) >= 1)) {

							$value = sprintf('<progress class="wsf-progress wsf-progress-small" min="%2$s" max="%3$s" value="%1$s"></progress><div class="wsf-helper">%1$s</div>', $range, $min, $max);

						} else {

							$value = $range;
						}

						return $value;

					}, $values_array));

					break;

				case 'color' :

					$value = implode($submit_delimiter_row, array_map(function($color) { return sprintf('<span class="wsf-submit-color-sample" style="background:%1$s"></span><span class="wsf-submit-color">%1$s</span>', $color); }, $values_array));

					break;

				default :

					$value = implode($submit_delimiter_row, array_map(function($value) use ($delimiter_row) { 

						// Check for array (e.g. Checkboxes, Selects)
						return is_array($value) ? implode($delimiter_row, $value) : $value;

					}, $values_array));
			}

			// Apply filter
			$value = apply_filters('wsf_submit_field_type_list', $value, $field_id, $field_type);

			// Check if value is still an array
			if(is_array($value)) { $value = implode(', ', $value); }

			return $value;
		}

		// File
		function file_html($hash, $id, $index, $mime_type, $name) {

			// Get file icon
			$file_types = WS_Form_Config::get_file_types();
			$icon = isset($file_types[$mime_type]) ? $file_types[$mime_type]['icon'] : $file_types['default']['icon'];

			// Download
			$return_html = sprintf('<a href="%s" title="%s">%s</a>', WS_Form_Common::get_api_path('helper/file_download', sprintf('hash=%s&field_id=%u&file_index=%u&_wpnonce=%s&%s=%s&download=1', rawurlencode($hash), rawurlencode($id), rawurlencode($index), wp_create_nonce('wp_rest'), rawurlencode(WS_FORM_POST_NONCE_FIELD_NAME), rawurlencode(wp_create_nonce(WS_FORM_POST_NONCE_ACTION_NAME)))), htmlentities($name), WS_Form_Config::get_icon_16_svg($icon));

			return $return_html;
		}

		// Column - Checkbox
		function column_cb($item) {

			return sprintf('<input type="checkbox" name="bulk-ids[]" value="%u" />', $item['id']);
		}

		// Column - ID
		function column_id($item) {

			// Get ID
			$id = intval($item['id']);

			// Title
			$title = sprintf('<strong><a href="#%1$u" data-action="wsf-view" data-id="%1$u">%1$u</a></strong>', $item['id']);

			// Actions
			$status = WS_Form_Common::get_query_var('ws-form-status');
			$actions = array();
			switch($status) {

				case 'trash' :

					// User capability check
					if(WS_Form_Common::can_user('delete_submission')) {

						$actions['restore'] = 	sprintf('<a href="#" data-action="wsf-restore" data-id="%u">%s</a>', $id, __('Restore', 'ws-form'));
						$actions['delete'] = 	sprintf('<a href="#" data-action="wsf-delete" data-id="%u">%s</a>', $id, __('Delete Permanently', 'ws-form'));
					}
					break;

				case 'spam' :

					$actions['view'] = 			sprintf('<a href="#%1$u" data-action="wsf-view" data-id="%1$u">%2$s</a>', $id, __('View', 'ws-form'));

					// User capability check
					if(WS_Form_Common::can_user('edit_submission')) {

						$actions['edit'] = 		sprintf('<a href="#%1$u" data-action="wsf-edit" data-id="%1$u">%2$s</a>', $id, __('Edit', 'ws-form'));
					}

					// User capability check
					if(WS_Form_Common::can_user('delete_submission')) {

						$actions['delete'] = 	sprintf('<a href="#" data-action="wsf-delete" data-id="%u">%s</a>', $id, __('Delete Permanently', 'ws-form'));
					}
					break;

				default :

					$actions['view'] = 			sprintf('<a href="#%1$u" data-action="wsf-view" data-id="%1$u">%2$s</a>', $id, __('View', 'ws-form'));

					// User capability check
					if(WS_Form_Common::can_user('edit_submission')) {

						$actions['edit'] = 		sprintf('<a href="#%1$u" data-action="wsf-edit" data-id="%1$u">%2$s</a>', $id, __('Edit', 'ws-form'));
					}

					// User capability check
					if(WS_Form_Common::can_user('edit_submission')) {

						$actions['viewed'] = 	sprintf('<a href="#" data-action-ajax="wsf-submit-viewed" data-id="%1$u">%2$s</a>', $id, ($item['viewed']) ? __('Mark as Unread', 'ws-form') : __('Mark as Read', 'ws-form'));
					}

					// User capability check
					if(WS_Form_Common::can_user('delete_submission')) {

						$actions['trash'] = 	sprintf('<a href="#" data-action="wsf-delete" data-id="%u">%s</a>', $id, __('Trash', 'ws-form'));
					}

					// User capability check
					if(WS_Form_Common::can_user('export_submission')) {

						$actions['export'] = 	sprintf('<a href="#" data-action="wsf-export" data-id="%u">%s</a>',$id, __('Export CSV', 'ws-form'));
					}

					// Apply filter
					$actions = apply_filters('wsf_table_submit_column_actions', $actions, $item, $status);
			}

			return $title . $this->row_actions($actions);
		}

		// Column - Status
		function column_status($item) {

			// Was this submit done in preview mode?
			$preview = isset($item['preview']) ? $item['preview'] : false;

			// Spam level indicator
			$spam_level = isset($item['spam_level']) ? $item['spam_level'] : null;
			$spam_level_indicator = is_null($spam_level) ? '' : '<span class="wsf-spam-level" style="background:' . WS_Form_Common::get_green_to_red_rgb($spam_level, 0, WS_FORM_SPAM_LEVEL_MAX) . '" title="' . __('Spam level: ', 'ws-form') . round($spam_level) . '%"></span>';

			// Build title
			$ws_form_submit = New WS_Form_Submit();
			$title = $spam_level_indicator . $ws_form_submit->db_get_status_name($item['status']) . ($preview ? ' (' . __('Preview', 'ws-form') . ')' : '');

			return $title;
		}

		// Column - Date added
		function column_date_added($item) {

			$date_added = $item['date_added'];

			$date_added = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime(get_date_from_gmt($date_added)));

			return $date_added;
		}

		// Column - Date updated
		function column_date_updated($item) {

			$date_updated = $item['date_updated'];

			$date_updated = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime(get_date_from_gmt($date_updated)));

			return $date_updated;
		}

		// Views
		function get_views(){

			// Get data from API
			$ws_form_submit = New WS_Form_Submit();

			$views = array();
			$current = WS_Form_Common::get_query_var('ws-form-status', 'all');
			$all_url = remove_query_arg(array('ws-form-status', 'paged'));

			// All link
			$count_all = $ws_form_submit->db_get_count_by_status($this->form_id);
			if($count_all) {
				$class = ($current === 'all' ? ' class="current"' :'');
				$views['all'] = "<a href=\"{$all_url}\" {$class} >" . __('All', 'ws-form') . " <span class=\"count\">$count_all</span></a>";
			}

			// Draft link
			$count_draft = $ws_form_submit->db_get_count_by_status($this->form_id, 'draft');
			if($count_draft) {
				$draft_url = add_query_arg('ws-form-status', 'draft', $all_url);
				$class = ($current === 'draft' ? ' class="current"' :'');
				$views['draft'] = "<a href=\"{$draft_url}\" {$class} >" . __('In Progress', 'ws-form') . " <span class=\"count\">$count_draft</span></a>";
			}

			// Published link
			$count_publish = $ws_form_submit->db_get_count_by_status($this->form_id, 'publish');
			if($count_publish) {
				$publish_url = add_query_arg('ws-form-status', 'publish', $all_url);
				$class = ($current === 'publish' ? ' class="current"' :'');
				$views['publish'] = "<a href=\"{$publish_url}\" {$class} >" . __('Submitted', 'ws-form') . " <span class=\"count\">$count_publish</span></a>";
			}

			// Spam link
			$count_spam = $ws_form_submit->db_get_count_by_status($this->form_id, 'spam');
			if($count_spam) {
				$spam_url = add_query_arg('ws-form-status', 'spam', $all_url);
				$class = ($current === 'spam' ? ' class="current"' :'');
				$views['spam'] = "<a href=\"{$spam_url}\" {$class} >" . __('Spam', 'ws-form') . " <span class=\"count\">$count_spam</span></a>";
			}

			// Trash link
			$count_trash = $ws_form_submit->db_get_count_by_status($this->form_id, 'trash');
			if($count_trash) {
				$trash_url = add_query_arg('ws-form-status', 'trash', $all_url);
				$class = ($current === 'trash' ? ' class="current"' :'');
				$views['trash'] = "<a href=\"{$trash_url}\" {$class} >" . __('Trash', 'ws-form') . " <span class=\"count\">$count_trash</span></a>";
			}

			return $views;
		}

		// Get form count by status
		function form_count_by_status($status = '') {

			global $wpdb;

			if(!WS_Form_Common::check_submit_status($status, false)) { $status = ''; }

			$sql = "SELECT COUNT(id) FROM {$wpdb->prefix}wsf_form WHERE";
			if($status == '') { $sql .= " NOT(status = 'trash')"; } else { $sql .= " status = '$status'"; }

			$form_count = $wpdb->get_var($sql);
			if(is_null($form_count)) { $form_count = 0; }

			return $form_count; 
		}

		// Get data
		function get_data($per_page = 20, $page_number = 1) {

			global $wpdb;

			// Build JOIN
			$join = '';

			// Build WHERE
			$where = self::get_where();

			// Build ORDER BY
			$order_by = '';
			$order_query_var = WS_Form_Common::get_query_var('order', '');
			$order_by_query_var = WS_Form_Common::get_query_var('order', '');
			if (!empty($order_by_query_var)) {

				$order = !empty($order_query_var) && ($order_query_var == 'desc') ? ' DESC' : ' ASC';

				switch($order_by_query_var) {

					case 'id' :
					case 'starred' :
					case 'status' :
					case 'date_added' :
					case 'date_updated' :

						$order_by = esc_sql($order_by_query_var) . $order;
						break;

					default :

						$order_by = $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . 'submit_meta.meta_value * 1' . $order . ', ' . $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . 'submit_meta.meta_value'. $order;
						$join = 'LEFT OUTER JOIN ' . $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . 'submit_meta ON ' . $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . 'submit_meta.parent_id = ' . $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . 'submit.id';
						$where .= ' AND ' . $wpdb->prefix . WS_FORM_DB_TABLE_PREFIX . "submit_meta.meta_key = '" . esc_sql($order_by_query_var) . "'";

				}

			} else {

				$order_by = 'id DESC';
			}

			// Build LIMIT
			$limit = $per_page;

			// Build OFFSET
			$offset = ($page_number - 1) * $per_page;

			// Get data from core
			$ws_form_submit = New WS_Form_Submit();
			$result = $ws_form_submit->db_read_all($join, $where, $order_by, $limit, $offset);

			return $result;
		}

		public function get_where() {

			// Build WHERE - form_id
			$where = 'form_id = ' . $this->form_id;

			// Build WHERE - status
			$status = WS_Form_Common::get_query_var('ws-form-status');
			if($status == '') { $status == 'all'; }
			if(!WS_Form_Common::check_submit_status($status, false)) { $status = 'all'; }
			if($status != 'all') {
	
				// Filter by status
				$where .= ' AND status = "' . $status . '"';

			} else {

				// Show everything but trash (All)
				$where .= " AND NOT(status = 'trash' OR status = 'spam')";
			}

			// Date from
			if($this->date_from != '') {

				$date_from = WS_Form_Common::get_mysql_date(get_gmt_from_date($this->date_from . ' 00:00:00'));
				if($date_from !== false) { $where .= " AND date_added >= '$date_from'"; }
			}

			// Date to
			if($this->date_to != '') {

				$date_to = WS_Form_Common::get_mysql_date(get_gmt_from_date($this->date_to . ' 23:59:59'));
				if($date_to !== false) { $where .= " AND date_added <= '$date_to'"; }
			}

			return $where;
		}

		// Prepare items
		public function prepare_items() {

			$this->_column_headers = $this->get_column_info();

			$per_page     = $this->get_items_per_page('ws_form_items_per_page_submit', 20);
			$current_page = $this->get_pagenum();
			$total_items  = self::record_count();

			$this->set_pagination_args(array(

				'total_items' => $total_items, //WE have to calculate the total number of items
				'per_page'    => $per_page //WE have to determine how many items to show on a page
			));

			$this->items = self::get_data($per_page, $current_page);
		}

		// Bulk actions - Prepare
		public function get_bulk_actions() {

			$actions = array();
			$status = WS_Form_Common::get_query_var('ws-form-status');

			switch($status) {

				case 'trash' :

					// User capability check
					if(WS_Form_Common::can_user('delete_submission')) {

						$actions['wsf-bulk-restore'] = __('Restore', 'ws-form');
						$actions['wsf-bulk-delete'] = __('Delete Permanently', 'ws-form');
					}
					break;

				case 'spam' :

					// User capability check
					if(WS_Form_Common::can_user('edit_submission')) {

						$actions['wsf-bulk-not-spam'] = __('Mark as Not Spam', 'ws-form');
					}

					// User capability check
					if(WS_Form_Common::can_user('delete_submission')) {

						$actions['wsf-bulk-delete'] = __('Delete Permanently', 'ws-form');
					}

					break;

				default:

					// User capability check
					if(WS_Form_Common::can_user('edit_submission')) {

						$actions['wsf-bulk-read'] = __('Mark as Read', 'ws-form');
						$actions['wsf-bulk-not-read'] = __('Mark as Unread', 'ws-form');
						$actions['wsf-bulk-starred'] = __('Mark as Starred', 'ws-form');
						$actions['wsf-bulk-not-starred'] = __('Mark as Not Starred', 'ws-form');
						$actions['wsf-bulk-spam'] = __('Mark as Spam', 'ws-form');
					}

					// User capability check
					if(WS_Form_Common::can_user('delete_submission')) {

						$actions['wsf-bulk-delete'] = __('Move to Trash', 'ws-form');
					}

					// User capability check
					if(WS_Form_Common::can_user('export_submission')) {

						$actions['wsf-bulk-export'] = __('Export CSV', 'ws-form');
					}
			}

			return $actions;
		}

		// Extra table nav
		function extra_tablenav($which) {

			// Status related buttons
			$status = WS_Form_Common::get_query_var('ws-form-status');
			switch($status) {

				case 'trash' :
?>
		<div class="alignleft actions">
<?php 
			submit_button(__('Empty Trash', 'ws-form'), 'apply', 'delete_all', false );
?>
		</div>
<?php
					break;
			}

			if($which != 'top') { return; }

			// Select form
			$ws_form_form = New WS_Form_Form();
			$ws_form_form->db_count_update_all();
			$forms = $ws_form_form->db_read_all('', "NOT (status = 'trash')", 'label ASC', '', '', false);

			if($forms) {
?>
<div class="alignleft actions">
<select id="wsf_filter_id" name="id">
<option value=""><?php esc_html_e('Select form...', 'ws-form'); ?></option>
<?php
				foreach($forms as $form) {

					// Get submit count
					$count_submit = $form['count_submit'];

?><option value="<?php echo esc_attr($form['id']); ?>"<?php

					// Selected
					if($form['id'] == $this->form_id) { echo ' selected="selected"'; }

					// Disabled
					if($count_submit == 0) { echo ' disabled="disabled"'; }
?>><?php
					// Label
					echo esc_html(sprintf(__('%s (ID: %u)', 'ws-form'), $form['label'], $form['id']));

					// Submit count
					if($count_submit > 0) {

						 echo esc_html(' - ' . sprintf(_n('%u record', '%u records', $count_submit, 'ws-form'), $count_submit));
					}
?></option>
<?php
				}
?>
</select>
<?php
				// Filters
				if($this->form_id > 0) {
?>
<input type="text" id="wsf_filter_date_from" name="date_from" value="<?php echo esc_attr($this->date_from); ?>" placeholder="<?php esc_html_e('Date from', 'ws-form'); ?>" autocomplete="off" />

<input type="text" id="wsf_filter_date_to" name="date_to" value="<?php echo esc_attr($this->date_to); ?>" placeholder="<?php esc_html_e('Date to', 'ws-form'); ?>" autocomplete="off" />

<input type="button" id="wsf_filter_do" class="button" value="Filter" />
<input type="button" id="wsf_filter_reset" class="button" value="Reset" />
<?php
				}
?>
</div>
<?php
			}
		}

		// Set primary column
		public function list_table_primary_column($default, $screen) {

		    if($screen === 'ws-form_page_ws-form-submit') { $default = 'id'; }

		    return $default;
		}

		// Get record count
		public function record_count() {

			// If form ID not set, return 0
			if($this->form_id == 0) { return 0; }

			// Use cached record count to avoid multiple database queries
			if($this->record_count !== false) { return $this->record_count; }

			// Build JOIN
			$join = '';

			// Build WHERE
			$where = self::get_where();

			// Get data from API
			$ws_form_submit = New WS_Form_Submit();
			$this->record_count = $ws_form_submit->db_read_count($join, $where);

			return $this->record_count;
		}

		// No records
		public function no_items() {

			if($this->form_id == 0) {

				esc_html_e('Please select a form.', 'ws-form');

			} else {

				esc_html_e('No submissions avaliable.', 'ws-form');
			}

		}
	}
