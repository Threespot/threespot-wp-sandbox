<?php

	class WS_Form_CSS {

		// Admin
		public function get_admin() {

			$rtl = is_rtl();

			// Get form column count
			$columns = intval(WS_Form_Common::option_get('framework_column_count', 0));
			if($columns == 0) { self::db_throw_error(__('Invalid framework column count', 'ws-form')); }

			// Read frameworks
			$frameworks = WS_Form_Config::get_frameworks();

			// Get framework ID
			$framework_id = WS_Form_Common::option_get('framework', 'ws-form');

			// Get framework
			$framework = $frameworks['types'][$framework_id];

			// Get column class mask
			$column_class = $framework['columns']['column_css_selector'];
			$columns_default = $framework['columns']['column_count'];

			// Get current framework breakpoints
			$breakpoints_outer = $framework['breakpoints'];
			$breakpoints_inner = $framework['breakpoints'];

			// Build CSS
			$css_return = ".wsf-group:before {\n\tbackground-image: repeating-linear-gradient(to right, #E5E5E5, #E5E5E5 calc((100% / $columns) - 12px), transparent calc((100% / $columns) - 12px), transparent calc(100% / $columns));\n\tbackground-size: calc(100% + 12px) 100%;\n}\n\n";
			$css_return .= ".wsf-section > .wsf-section-inner:before {\n\tbackground-image: repeating-linear-gradient(to right, #E5E5E5, #E5E5E5 calc((100% / $columns) - 12px), transparent calc((100% / $columns) - 12px), transparent calc(100% / $columns));\n\tbackground-size: calc(100% - 12px) 100%;\n\tbackground-position-x: 12px;\n}\n\n";

			// Grid
			$css_return .= ".wsf-sections, .wsf-fields {\n";

			$css_return .= "\tdisplay: -webkit-box;\n";
			$css_return .= "\tdisplay: -ms-flexbox;\n";
			$css_return .= "\tdisplay: flex;\n";
			$css_return .= "\t-ms-flex-wrap: wrap;\n";
			$css_return .= "\tflex-wrap: wrap;\n";

			$css_return .= "}\n\n";

			$breakpoint_outer_index = 0;
			foreach($breakpoints_outer as $key_outer => $breakpoint_outer) {

				// Get outer breakpoint ID and name
				$breakpoint_outer_id = $breakpoint_outer['id'];
				$breakpoint_outer_name = $breakpoint_outer['name'];

				// Output comment
				$css_return .= WS_Form_Common::comment_css($breakpoint_outer_name);

				// Add classes for breakpoint widths to resize admin
				if(WS_Form_Common::option_get('helper_breakpoint_width', false)) {

					// Output max-width statements
					if($breakpoint_outer_index != (count($breakpoints_outer) - 1)) {

						if(!isset($breakpoint_outer['admin_max_width'])) {

							self::db_throw_error(__('Admin max width not defined: ' . $breakpoint_outer_id, 'ws-form'));

						} else {

							$breakpoint_outer_max_width = $breakpoint_outer['admin_max_width'];
						}

						$css_return .= "#wsf-form[data-breakpoint=\"" . $breakpoint_outer_id . "\"] { max-width: " . $breakpoint_outer_max_width . "px; }\n\n";
					}
				}

				// Check for breakpoint specific CSS selector
				if(isset($breakpoint_outer['column_css_selector'])) {

					$column_class_single = $breakpoint_outer['column_css_selector'];

				} else {

					$column_class_single = $column_class;
				}

				// Columns - Run through each column
				for($column_index = 1; $column_index <= $columns; $column_index++) {

					// Create CSS for each column and each breakpoint
					$breakpoint_inner_index = 1;
					foreach($breakpoints_inner as $key_inner => $breakpoint_inner) {

						// Get inner breakpoint ID
						$breakpoint_inner_id = $breakpoint_inner['id'];

						// Build mask values for parser
						$mask_values = ['id' => $breakpoint_outer_id, 'size' => $column_index];

						// COLUMN

						// Get single class
						$class_single = WS_Form_Common::mask_parse($column_class_single, $mask_values);

						// Build CSS selectors
						$css_return .= "#wsf-form[data-breakpoint=\"" . $breakpoint_inner_id . '"] ' . $class_single;

						// Get key of top breakpoint (we'll remove this for the next run)
						if($breakpoint_inner_index == 1) { $breakpoint_inner_key_to_delete = $key_inner; }

						if($breakpoint_inner_index == count($breakpoints_inner)) {

							$column_width_percentage = ($column_index / $columns) * 100;

							$css_return .= " {";

							$css_return .= "\n\t-webkit-box-flex: 0;";
							$css_return .= "\n\t-ms-flex: 0 0 " . $column_width_percentage . "%;";
							$css_return .= "\n\tflex: 0 0 " . $column_width_percentage . "%;";
							$css_return .= "\n\tmax-width: " . $column_width_percentage . "%;";

							$css_return .= "\n}\n\n";

						} else {

							// Add comma (not at last inner breakpoint yet)
							$css_return .= ",\n";
						}

						$breakpoint_inner_index++;
					}
				}

				// Take top key off the inner breakpoints
				unset($breakpoints_inner[$breakpoint_inner_key_to_delete]);

				$breakpoint_outer_index++;
			}

			// Offsets - Run through each column
			$offset_class = $framework['columns']['offset_css_selector'];

			// Get current framework breakpoints
			$breakpoints_outer = $framework['breakpoints'];
			$breakpoints_inner = $framework['breakpoints'];

			foreach($breakpoints_outer as $key_outer => $breakpoint_outer) {

				// Get outer breakpoint ID and name
				$breakpoint_outer_id = $breakpoint_outer['id'];
				$breakpoint_outer_name = $breakpoint_outer['name'];

				// Check for breakpoint specific CSS selector
				if(isset($breakpoint_outer['offset_css_selector'])) {

					$offset_class_single = $breakpoint_outer['offset_css_selector'];

				} else {

					$offset_class_single = $offset_class;
				}

				// Output comment
				$css_return .= WS_Form_Common::comment_css($breakpoint_outer_name . ' - Offsets');

				for($column_index = 0; $column_index < $columns; $column_index++) {

					// Create CSS for each column and each breakpoint
					$breakpoint_inner_index = 1;
					foreach($breakpoints_inner as $key_inner => $breakpoint_inner) {

						// Get inner breakpoint ID
						$breakpoint_inner_id = $breakpoint_inner['id'];

						// Build mask values for parser
						$mask_values = ['id' => $breakpoint_outer_id, 'offset' => $column_index];

						// Get single offset
						$offset_single = WS_Form_Common::mask_parse($offset_class_single, $mask_values);

						// Get key of top breakpoint (we'll remove this for the next run)
						if($breakpoint_inner_index == 1) { $breakpoint_inner_key_to_delete = $key_inner; }

						// Build CSS selectors
						$css_return .= "#wsf-form[data-breakpoint=\"" . $breakpoint_inner_id . '"] ' . $offset_single;

						// Get key of top breakpoint (we'll remove this for the next run)
						if($breakpoint_inner_index == 1) { $breakpoint_inner_key_to_delete = $key_inner; }

						if($breakpoint_inner_index == count($breakpoints_inner)) {

							$column_width_percentage = ($column_index / $columns) * 100;

							// Build offset CSS
							$css_return .= " {";

							$css_return .= "\n\tbackground-size: " . $column_width_percentage . "%;";
							$css_return .= "\n\tmargin-" . ($rtl ? 'right' : 'left') . ": " . $column_width_percentage . "%;";

							$css_return .= "\n}\n\n";

						} else {

							// Add comma (not at last inner breakpoint yet)
							$css_return .= ",\n";
						}

						$breakpoint_inner_index++;
					}
				}

				// Take top key off the inner breakpoints
				unset($breakpoints_inner[$breakpoint_inner_key_to_delete]);
			}

			// Minify
			$css_minify = WS_Form_Common::option_get('css_minify', false);

			return $css_minify ? self::minify($css_return) : $css_return;
		}

		// Public
		public function get_public() {

			$rtl = is_rtl();

			// Build CSS
			$css_return = '';

			// Read frameworks
			$frameworks = WS_Form_Config::get_frameworks();

			// Get framework ID
			$framework_id = WS_Form_Common::option_get('framework', 'ws-form');

			// Get framework
			$framework = $frameworks['types'][$framework_id];

			// Get column class mask
			$column_class = $framework['columns']['column_css_selector'];
			$columns_default = $framework['columns']['column_count'];

			// Get form column count
			$columns = intval(WS_Form_Common::option_get('framework_column_count', 0));
			if($columns == 0) { self::db_throw_error(__('Invalid framework column count', 'ws-form')); }

			$grid_spacing = 0;
			$grid_spacing_unit = 'px';

			// Invalid Feedback
			$css_return .= ".wsf-invalid-feedback,\n";
			$css_return .= "[data-checkbox-min-max] {\n";
			$css_return .= "\tdisplay: none;\n";
			$css_return .= "}\n\n";

			$css_return .= ".wsf-validated .wsf-field:invalid ~ .wsf-invalid-feedback,\n";
			$css_return .= ".wsf-validated .wsf-field.wsf-invalid ~ .wsf-invalid-feedback,\n";
			$css_return .= ".wsf-validated [data-checkbox-min-max]:invalid ~ .wsf-invalid-feedback {\n";
			$css_return .= "\tdisplay: block;\n";
			$css_return .= "}\n\n";

			// Grid
			$css_return .= ".wsf-grid {\n";

			$css_return .= "\tdisplay: -webkit-box;\n";
			$css_return .= "\tdisplay: -ms-flexbox;\n";
			$css_return .= "\tdisplay: flex;\n";
			$css_return .= "\t-ms-flex-wrap: wrap;\n";
			$css_return .= "\tflex-wrap: wrap;\n";

			if($grid_spacing > 0) {

				$css_return .= "\tmargin-left: " . (($grid_spacing / 2) * -1) . $grid_spacing_unit . " !important;\n";
				$css_return .= "\tmargin-right: " . (($grid_spacing / 2) * -1) . $grid_spacing_unit . " !important;\n";
			}

			$css_return .= "}\n\n";

			// Tile
			$css_return .= ".wsf-tile {\n";
			$css_return .= "\tposition: relative;\n";
			$css_return .= "\twidth: 100%;\n";
			$css_return .= "\tbox-sizing: border-box;\n";

			if($grid_spacing > 0) {
				$css_return .= "\tpadding-left: " . ($grid_spacing / 2) . $grid_spacing_unit . " !important;\n";
				$css_return .= "\tpadding-right: " . ($grid_spacing / 2) . $grid_spacing_unit . " !important;\n";
			}
			$css_return .= "}\n\n";

			// Breakpoint CSS
			foreach($framework['breakpoints'] as $key => $breakpoint) {

				// Get outer breakpoint ID and name
				$breakpoint_id = $breakpoint['id'];
				$breakpoint_name = $breakpoint['name'];
				if(isset($breakpoint['min_width'])) {
					$breakpoint_min_width = $breakpoint['min_width'];
				} else {
					$breakpoint_min_width = 0;
				}

				// Output comment
				$css_return .= WS_Form_Common::comment_css($breakpoint_name);

				// Output media query
				$css_indent = '';
				if($breakpoint_min_width > 0) {

					$css_return .= "@media (min-width: " . $breakpoint_min_width . "px) {\n\n";
					$css_indent = "\t";
				}

				// Check for breakpoint specific CSS selector
				if(isset($breakpoint['column_css_selector'])) {

					$column_class_single = $breakpoint['column_css_selector'];

				} else {

					$column_class_single = $column_class;
				}

				// Run through each column
				for($column_index = 1; $column_index <= $columns; $column_index++) {

					// Build mask values for parser
					$mask_values = ['id' => $breakpoint_id, 'size' => $column_index];

					// Get single class
					$class_single = WS_Form_Common::mask_parse($column_class_single, $mask_values);

					// Build CSS selectors
					$css_return .= $css_indent . $class_single;

					$column_width_percentage = round(($column_index / $columns) * 100, 6);

					$css_return .= " {";

					$css_return .= "\n" . $css_indent . "\t-webkit-box-flex: 0 !important;";
					$css_return .= "\n" . $css_indent . "\t-ms-flex: 0 0 " . $column_width_percentage . "% !important;";
					$css_return .= "\n" . $css_indent . "\tflex: 0 0 " . $column_width_percentage . "% !important;";
					$css_return .= "\n" . $css_indent . "\tmax-width: " . $column_width_percentage . "% !important;";

					$css_return .= "\n" . $css_indent . "}\n\n";
				}

				// Close media query
				if($breakpoint_min_width > 0) {

					$css_return .= "}\n\n";
				}
			}

			// Offsets - Run through each column
			$offset_class = $framework['columns']['offset_css_selector'];

			// Breakpoint CSS
			foreach($framework['breakpoints'] as $key => $breakpoint) {

				// Get outer breakpoint ID and name
				$breakpoint_id = $breakpoint['id'];
				$breakpoint_name = $breakpoint['name'];
				if(isset($breakpoint['min_width'])) {
					$breakpoint_min_width = $breakpoint['min_width'];
				} else {
					$breakpoint_min_width = 0;
				}

				// Output comment
				$css_return .= WS_Form_Common::comment_css($breakpoint_name . ' - Offsets');

				// Output media query
				$css_indent = '';
				if($breakpoint_min_width > 0) {

					$css_return .= "@media (min-width: " . $breakpoint_min_width . "px) {\n\n";
					$css_indent = "\t";
				}

				// Check for breakpoint specific CSS selector
				if(isset($breakpoint['offset_css_selector'])) {

					$offset_class_single = $breakpoint['offset_css_selector'];

				} else {

					$offset_class_single = $offset_class;
				}

				for($column_index = 0; $column_index <= $columns; $column_index++) {

					// Build mask values for parser
					$mask_values = ['id' => $breakpoint_id, 'offset' => $column_index];

					// Get single offset
					$offset_single = WS_Form_Common::mask_parse($offset_class_single, $mask_values);

					$column_width_percentage = ($column_index / $columns) * 100;

					// Build CSS selectors
					$css_return .= $css_indent . $offset_single . " {\n";

					// Build offset CSS
					$css_return .= $css_indent . "\tmargin-" . ($rtl ? 'right' : 'left') . ": " . $column_width_percentage . "% !important;\n";

					$css_return .= $css_indent . "}\n\n";
				}

				// Close media query
				if($breakpoint_min_width > 0) {

					$css_return .= "}\n\n";
				}
			}

			$css_return .= ".wsf-bottom {\n";
			$css_return .= "\talign-self: flex-end !important;\n";
			$css_return .= "}\n\n";

			$css_return .= ".wsf-top {\n";
			$css_return .= "\talign-self: flex-start !important;\n";
			$css_return .= "}\n\n";

			$css_return .= ".wsf-middle {\n";
			$css_return .= "\talign-self: center !important;\n";
			$css_return .= "}\n\n";

			// Minify
			$css_minify = WS_Form_Common::option_get('css_minify', false);

			return $css_minify ? self::minify($css_return) : $css_return;
		}

		// Skin
		public function render_skin() {

			// Customizer
			$enable_cache = !(WS_Form_Common::get_query_var('customize_theme') !== '');

			// Colors
			$color_default = WS_Form_Common::option_get('skin_color_default', false, false, $enable_cache);
			$color_default_inverted = WS_Form_Common::option_get('skin_color_default_inverted', false, false, $enable_cache);
			$color_default_light = WS_Form_Common::option_get('skin_color_default_light', false, false, $enable_cache);
			$color_default_lighter = WS_Form_Common::option_get('skin_color_default_lighter', false, false, $enable_cache);
			$color_default_lightest = WS_Form_Common::option_get('skin_color_default_lightest', false, false, $enable_cache);
			$color_primary = WS_Form_Common::option_get('skin_color_primary', false, false, $enable_cache);
			$color_secondary = WS_Form_Common::option_get('skin_color_secondary', false, false, $enable_cache);
			$color_success = WS_Form_Common::option_get('skin_color_success', false, false, $enable_cache);
			$color_information = WS_Form_Common::option_get('skin_color_information', false, false, $enable_cache);
			$color_warning = WS_Form_Common::option_get('skin_color_warning', false, false, $enable_cache);
			$color_danger = WS_Form_Common::option_get('skin_color_danger', false, false, $enable_cache);

			// Components
			$border = (WS_Form_Common::option_get('skin_border', false, false, $enable_cache) == 'true');
			$border_width = WS_Form_Common::option_get('skin_border_width', false, false, $enable_cache);
			$border_style = WS_Form_Common::option_get('skin_border_style', false, false, $enable_cache);
			$border_radius = WS_Form_Common::option_get('skin_border_radius', false, false, $enable_cache);

			// Transitions
			$transition = (WS_Form_Common::option_get('skin_transition', false, false, $enable_cache) == 'true');
			$transition_speed = WS_Form_Common::option_get('skin_transition_speed', false, false, $enable_cache);
			$transition_timing_function = WS_Form_Common::option_get('skin_transition_timing_function', false, false, $enable_cache);

			// Typography
			$font_family = WS_Form_Common::option_get('skin_font_family', false, false, $enable_cache);
			$font_size = WS_Form_Common::option_get('skin_font_size', false, false, $enable_cache);
			$font_size_large = WS_Form_Common::option_get('skin_font_size_large', false, false, $enable_cache);
			$font_size_small = WS_Form_Common::option_get('skin_font_size_small', false, false, $enable_cache);
			$font_weight = WS_Form_Common::option_get('skin_font_weight', false, false, $enable_cache);
			$line_height = WS_Form_Common::option_get('skin_line_height', false, false, $enable_cache);

			// Advanced
			$unit_of_measurement = 'px';
			$grid_gutter = WS_Form_Common::option_get('skin_grid_gutter', false, false, $enable_cache);

			// Spacing
			$spacing = 20;
			$spacing_extra_large = 80;
			$spacing_large = 40;
			$spacing_small = 10;
			$spacing_extra_small = 5;

			// Forms
			$form_background_color = $color_default_inverted;
			$form_border = $border; // true | false
			$form_border_color = $color_default_lighter;
			$form_border_style = $border_style;
			$form_border_width = $border_width;
			$form_border_radius = $border_radius;
			$form_checked_color = $color_primary;
			$form_color = $color_default;
			$form_disabled_background_color = $color_default_lightest;
			$form_disabled_border_color = $form_border_color;
			$form_disabled_color = $color_default_light;
			$form_error_background_color = $form_background_color;
			$form_error_border_color = $color_danger;
			$form_error_color = $form_color;
			$form_focus = true; // true | false
			$form_focus_background_color = $form_background_color;
			$form_focus_border_color = $color_primary;
			$form_focus_color = $form_color;
			$form_font_size = $font_size;
			$form_font_size_large = $font_size_large;
			$form_font_size_small = $font_size_small;
			$form_help_color = $color_default_light;
			$form_invalid_feedback_color = $color_danger;
			$form_hover = false; // true | false
			$form_hover_background_color = $form_background_color;
			$form_hover_border_color = $color_primary;
			$form_hover_color = $form_color;
			$form_label_color = $form_color;
			$form_placeholder_color = $color_default_light;
			$form_spacing_horizontal = $spacing_small;
			$form_spacing_vertical = ($spacing_small * .85);
			$form_transition = $transition; // true | false
			$form_transition_timing_function = $transition_timing_function;
			$form_transition_speed = $transition_speed . 'ms ' . $form_transition_timing_function;
			$input_height = ((round($form_font_size * $line_height) + ($form_spacing_vertical * 2)) + ($form_border_width * 2));
			$checkbox_size = round($form_font_size * $line_height);
			$radio_size = round($form_font_size * $line_height);
			$color_size = $input_height;
			$range_size = $spacing;

			// Wizard mode
			if(WS_Form_Common::get_query_var('wizard') != '') {

				$form_background_color = '#CECED2';
			}
?>
.wsf-form {
	box-sizing: border-box;
	color: <?php self::e($color_default); ?>;
	font-family: <?php self::e($font_family); ?>;
	font-size: <?php self::e($font_size . $unit_of_measurement); ?>;
	font-weight: <?php self::e($font_weight); ?>;
	line-height: <?php self::e($line_height); ?>;
	-webkit-tap-highlight-color: transparent;
	text-size-adjust: 100%;
}

.wsf-form *, .wsf-form *:before, .wsf-form *:after {
	box-sizing: inherit;
}

.wsf-section,
.wsf-fieldset {
	border: none;
	margin: 0;
	min-width: 0;
	padding: 0;
}

.wsf-section > legend,
.wsf-fieldset > legend {
	border: 0;
	font-size: <?php self::e($font_size_large . $unit_of_measurement); ?>;
	margin-bottom: <?php self::e($spacing_small . $unit_of_measurement); ?>;
	padding: 0;
}

.wsf-form ul.wsf-group-tabs {
	border-bottom: <?php self::e($border_width . $unit_of_measurement . ' ' . $border_style . ' ' . $color_default_lighter); ?>;
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	list-style: none;
	margin-bottom: <?php self::e($spacing . $unit_of_measurement); ?>;
	margin-left: -<?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
	margin-right: -<?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
	margin-top: 0;
	padding-left: 0;
	position: relative;
}

.wsf-form ul.wsf-group-tabs > li {
	box-sizing: border-box;
	margin-bottom: -<?php self::e($border_width . $unit_of_measurement); ?>;
	outline: none;
	padding: 0 <?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
	position: relative;
}

.wsf-form ul.wsf-group-tabs > li > a {
	background-color: transparent;
	border: <?php self::e(($border_width . $unit_of_measurement . ' ' . $border_style) . ' transparent'); ?>;
<?php if ($border_radius > 0) { ?>
	border-top-left-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
	border-top-right-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	box-shadow: none;
	color: <?php self::e($color_default); ?>;
	cursor: pointer;
	display: block;
	font-size: <?php self::e($form_font_size . $unit_of_measurement); ?>;
	padding: 11px <?php self::e($spacing . $unit_of_measurement); ?>;
	text-align: center;
	text-decoration: none;
<?php if ($transition) { ?>
	transition: background-color <?php self::e($transition_speed . 'ms ' . $transition_timing_function); ?>, border-color <?php self::e($transition_speed . 'ms ' . $transition_timing_function); ?>;
<?php } ?>
	white-space: nowrap;
}

.wsf-form ul.wsf-group-tabs > li > a:focus {
	outline: 0;
}

.wsf-form ul.wsf-group-tabs > li.wsf-tab-active {
	z-index: 1;
}

.wsf-form ul.wsf-group-tabs > li.wsf-tab-active > a {
	background-color: <?php self::e($color_default_inverted); ?>;
	border-color: <?php self::e($color_default_lighter); ?>;
	border-bottom-color: transparent;
	color: <?php self::e($color_default); ?>;
	cursor: default;
}

.wsf-form ul.wsf-group-tabs > li > a.wsf-tab-disabled {
	color: <?php self::e($color_default_light); ?>;
	cursor: not-allowed;
	pointer-events: none;
}

.wsf-grid {
	margin-left: -<?php self::e(($grid_gutter / 2) . $unit_of_measurement); ?>;
	margin-right: -<?php self::e(($grid_gutter / 2) . $unit_of_measurement); ?>;
}

.wsf-tile {
	padding-left: <?php self::e(($grid_gutter / 2) . $unit_of_measurement); ?>;
	padding-right: <?php self::e(($grid_gutter / 2) . $unit_of_measurement); ?>;
}

.wsf-field-wrapper {
	margin-bottom: <?php self::e($grid_gutter . $unit_of_measurement); ?>;
}

.wsf-field-wrapper[data-type='texteditor'],
.wsf-field-wrapper[data-type='html'],
.wsf-field-wrapper[data-type='divider'],
.wsf-field-wrapper[data-type='message'] {
	margin-bottom: 0;
}

.wsf-inline {
	display: inline-flex;
	flex-direction: column;
	margin-right: <?php self::e($spacing_small . $unit_of_measurement); ?>;
}

.wsf-label-wrapper label.wsf-label {
	padding: <?php self::e(($form_spacing_vertical + $form_border_width) . $unit_of_measurement); ?> 0;
	margin-bottom: 0;
}

label.wsf-label {
	display: block;
<?php if ($form_label_color != $color_default) { ?>
	color: <?php self::e($form_label_color); ?>;
<?php } ?>
	font-family: <?php self::e($font_family); ?>;
	font-size: <?php self::e($form_font_size . $unit_of_measurement); ?>;
	font-weight: <?php self::e($font_weight); ?>;
	line-height: <?php self::e($line_height); ?>;
	margin-bottom: <?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
}

.wsf-invalid-feedback {
	color: <?php self::e($form_invalid_feedback_color); ?>;
	font-size: <?php self::e($form_font_size_small . $unit_of_measurement); ?>;
	line-height: <?php self::e($line_height); ?>;
	margin-top: <?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
}

.wsf-help {
	color: <?php self::e($form_help_color); ?>;
	display: block;
	font-size: <?php self::e($form_font_size_small . $unit_of_measurement); ?>;
	line-height: <?php self::e($line_height); ?>;
	margin-top: <?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
}
canvas.wsf-field {
	background-color: <?php self::e($form_background_color); ?>;
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	cursor: crosshair;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>, background-image <?php self::e($form_transition_speed); ?>;
<?php } ?>
	width: 100%;
}

<?php if ($form_hover) { ?>
canvas.wsf-field:hover {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_hover_background_color); ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_hover_border_color); ?>;
<?php } ?>
<?php if ($form_hover_color != $form_color) { ?>
	color: <?php self::e($form_hover_color); ?>;
<?php } ?>
}
<?php } ?>

canvas.wsf-field:focus {
<?php if ($form_focus) { ?>
<?php if ($form_focus_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_focus_background_color); ?>;
<?php } ?>
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_focus_border_color); ?>;
<?php } ?>
<?php if ($form_focus_color != $form_color) { ?>
	color: <?php self::e($form_focus_color); ?>;
<?php } ?>
	outline: 0;
}
<?php } ?>

canvas.wsf-field.wsf-disabled {
<?php if ($form_disabled_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_disabled_background_color); ?>;
<?php } ?>
<?php if ($form_border) { ?>
<?php if ($form_disabled_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_disabled_border_color); ?>;
<?php } ?>
<?php } ?>
<?php if ($form_disabled_color != $form_color) { ?>
	color: <?php self::e($form_disabled_color); ?>;
<?php } ?>
	cursor: not-allowed;
	opacity: 1;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
}

[data-wsf-legal].wsf-field {
	background-color: <?php self::e($form_background_color); ?>;
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	color: <?php self::e($form_color); ?>;
	font-family: <?php self::e($font_family); ?>;
	font-size: <?php self::e($form_font_size . $unit_of_measurement); ?>;
	font-weight: <?php self::e($font_weight); ?>;
	line-height: <?php self::e($line_height); ?>;
	padding: <?php self::e($form_spacing_vertical . $unit_of_measurement . ' ' . $form_spacing_horizontal . $unit_of_measurement); ?>;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>, background-image <?php self::e($form_transition_speed); ?>;
<?php } ?>
	width: 100%;
}

<?php if ($form_hover) { ?>
[data-wsf-legal].wsf-field:hover {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_hover_background_color); ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_hover_border_color); ?>;
<?php } ?>
<?php if ($form_hover_color != $form_color) { ?>
	color: <?php self::e($form_hover_color); ?>;
<?php } ?>
}
<?php } ?>

[data-wsf-legal].wsf-field h1,
[data-wsf-legal].wsf-field h2,
[data-wsf-legal].wsf-field h3,
[data-wsf-legal].wsf-field h4,
[data-wsf-legal].wsf-field h5,
[data-wsf-legal].wsf-field h6 {
	color: <?php self::e($form_color); ?>;
	font-family: <?php self::e($font_family); ?>;
	line-height: <?php self::e($line_height); ?>;
	margin: 0 0 <?php self::e($form_spacing_vertical . $unit_of_measurement); ?>;
}

[data-wsf-legal].wsf-field p,
[data-wsf-legal].wsf-field ul,
[data-wsf-legal].wsf-field ol,
[data-wsf-legal].wsf-field th,
[data-wsf-legal].wsf-field td {
	color: <?php self::e($form_color); ?>;
	font-family: <?php self::e($font_family); ?>;
	font-size: <?php self::e($form_font_size . $unit_of_measurement); ?>;
	line-height: <?php self::e($line_height); ?>;
	margin: 0 0 <?php self::e($form_spacing_vertical . $unit_of_measurement); ?>;
}

input[type=date].wsf-field,
input[type=datetime-local].wsf-field,
input[type=month].wsf-field,
input[type=password].wsf-field,
input[type=search].wsf-field,
input[type=time].wsf-field,
input[type=week].wsf-field,
input[type=email].wsf-field,
input[type=number].wsf-field,
input[type=tel].wsf-field,
input[type=text].wsf-field,
input[type=search].wsf-field,
input[type=url].wsf-field,
select.wsf-field,
textarea.wsf-field {
	-webkit-appearance: none;
	background-color: <?php self::e($form_background_color); ?>;
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } else { ?>
	border: none;
<?php } ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
	color: <?php self::e($form_color); ?>;
	font-family: <?php self::e($font_family); ?>;
	font-size: <?php self::e($form_font_size . $unit_of_measurement); ?>;
	font-weight: <?php self::e($font_weight); ?>;
	line-height: <?php self::e($line_height); ?>;
	margin: 0;
	padding: <?php self::e($form_spacing_vertical . $unit_of_measurement . ' ' . $form_spacing_horizontal . $unit_of_measurement); ?>;
	touch-action: manipulation;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>, background-image <?php self::e($form_transition_speed); ?>;
<?php } ?>
	width: 100%;
}

input[type=date].wsf-field,
input[type=datetime-local].wsf-field,
input[type=month].wsf-field,
input[type=password].wsf-field,
input[type=search].wsf-field,
input[type=time].wsf-field,
input[type=week].wsf-field,
input[type=email].wsf-field,
input[type=number].wsf-field,
input[type=tel].wsf-field,
input[type=text].wsf-field,
input[type=search].wsf-field,
input[type=url].wsf-field,
select.wsf-field:not([multiple]):not([size]) {
	height: <?php self::e($input_height . $unit_of_measurement); ?>;
}

<?php if(WS_Form_Common::option_get('ui_datepicker', 'on') !== 'off') { ?>

body .xdsoft_datetimepicker .xdsoft_label > .xdsoft_select > div > .xdsoft_option.xdsoft_current {
	background: <?php self::e($color_primary); ?> !important;
	box-shadow: none;
	color: <?php self::e($color_default_inverted); ?> !important;
}

body .xdsoft_datetimepicker .xdsoft_label > .xdsoft_select > div > .xdsoft_option:hover {
	background: <?php self::e($color_primary); ?> !important;
	color: <?php self::e($color_default_inverted); ?> !important;
}

body .xdsoft_datetimepicker .xdsoft_calendar td.xdsoft_today {
    color: <?php self::e($color_primary); ?> !important;
    font-weight: bold;
}

body .xdsoft_datetimepicker .xdsoft_calendar td.xdsoft_current,
body .xdsoft_datetimepicker .xdsoft_timepicker .xdsoft_time_box>div>div.xdsoft_current {
	background: <?php self::e($color_primary); ?> !important;
	box-shadow: none;
	color: <?php self::e($color_default_inverted); ?> !important;
}

body .xdsoft_datetimepicker .xdsoft_calendar td:hover,
body .xdsoft_datetimepicker .xdsoft_timepicker .xdsoft_time_box>div>div:hover {
	background: <?php self::e($color_primary); ?> !important;
	color: <?php self::e($color_default_inverted); ?> !important;
}
<?php } ?>

input[type=date].wsf-field::placeholder,
input[type=datetime-local].wsf-field::placeholder,
input[type=month].wsf-field::placeholder,
input[type=password].wsf-field::placeholder,
input[type=search].wsf-field::placeholder,
input[type=time].wsf-field::placeholder,
input[type=week].wsf-field::placeholder,
input[type=email].wsf-field::placeholder,
input[type=number].wsf-field::placeholder,
input[type=tel].wsf-field::placeholder,
input[type=text].wsf-field::placeholder,
input[type=search].wsf-field::placeholder,
input[type=url].wsf-field::placeholder,
select.wsf-field::placeholder,
textarea.wsf-field::placeholder {
	color: <?php self::e($form_placeholder_color); ?>;
	opacity: 1;
}

<?php if ($form_hover) { ?>
input[type=date].wsf-field:hover:enabled,
input[type=datetime-local].wsf-field:hover:enabled,
input[type=month].wsf-field:hover:enabled,
input[type=password].wsf-field:hover:enabled,
input[type=search].wsf-field:hover:enabled,
input[type=time].wsf-field:hover:enabled,
input[type=week].wsf-field:hover:enabled,
input[type=email].wsf-field:hover:enabled,
input[type=number].wsf-field:hover:enabled,
input[type=tel].wsf-field:hover:enabled,
input[type=text].wsf-field:hover:enabled,
input[type=search].wsf-field:hover:enabled,
input[type=url].wsf-field:hover:enabled,
select.wsf-field:hover:enabled,
textarea.wsf-field:hover:enabled {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_hover_background_color); ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_hover_border_color); ?>;
<?php } ?>
<?php if ($form_hover_color != $form_color) { ?>
	color: <?php self::e($form_hover_color); ?>;
<?php } ?>
}
<?php } ?>

input[type=date].wsf-field:focus,
input[type=datetime-local].wsf-field:focus,
input[type=month].wsf-field:focus,
input[type=password].wsf-field:focus,
input[type=search].wsf-field:focus,
input[type=time].wsf-field:focus,
input[type=week].wsf-field:focus,
input[type=email].wsf-field:focus,
input[type=number].wsf-field:focus,
input[type=tel].wsf-field:focus,
input[type=text].wsf-field:focus,
input[type=search].wsf-field:focus,
input[type=url].wsf-field:focus,
select.wsf-field:focus,
textarea.wsf-field:focus {
<?php if ($form_focus) { ?>
<?php if ($form_focus_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_focus_background_color); ?>;
<?php } ?>
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_focus_border_color); ?>;
<?php } ?>
<?php if ($form_focus_color != $form_color) { ?>
	color: <?php self::e($form_focus_color); ?>;
<?php } ?>
<?php } ?>
	outline: 0;
}

input[type=date].wsf-field:disabled,
input[type=datetime-local].wsf-field:disabled,
input[type=month].wsf-field:disabled,
input[type=password].wsf-field:disabled,
input[type=search].wsf-field:disabled,
input[type=time].wsf-field:disabled,
input[type=week].wsf-field:disabled,
input[type=email].wsf-field:disabled,
input[type=number].wsf-field:disabled,
input[type=tel].wsf-field:disabled,
input[type=text].wsf-field:disabled,
input[type=search].wsf-field:disabled,
input[type=url].wsf-field:disabled,
select.wsf-field:disabled,
textarea.wsf-field:disabled {
<?php if ($form_disabled_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_disabled_background_color); ?>;
<?php } ?>
<?php if ($form_border) { ?>
<?php if ($form_disabled_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_disabled_border_color); ?>;
<?php } ?>
<?php } ?>
<?php if ($form_disabled_color != $form_color) { ?>
	color: <?php self::e($form_disabled_color); ?>;
	-webkit-text-fill-color: <?php self::e($form_disabled_color); ?>;
<?php } else { ?>
	-webkit-text-fill-color: <?php self::e($form_color); ?>;
<?php } ?>
	cursor: not-allowed;
	opacity: 1;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
}

input[type=date].wsf-field::-moz-focus-inner,
input[type=datetime-local].wsf-field::-moz-focus-inner,
input[type=month].wsf-field::-moz-focus-inner,
input[type=password].wsf-field::-moz-focus-inner,
input[type=search].wsf-field::-moz-focus-inner,
input[type=time].wsf-field::-moz-focus-inner,
input[type=week].wsf-field::-moz-focus-inner,
input[type=email].wsf-field::-moz-focus-inner,
input[type=number].wsf-field::-moz-focus-inner,
input[type=tel].wsf-field::-moz-focus-inner,
input[type=text].wsf-field::-moz-focus-inner,
input[type=search].wsf-field::-moz-focus-inner,
input[type=url].wsf-field::-moz-focus-inner,
select.wsf-field::-moz-focus-inner,
textarea.wsf-field::-moz-focus-inner {
	border: 0;
	padding: 0;
}

input[type=number].wsf-field::-webkit-inner-spin-button,
input[type=number].wsf-field::-webkit-outer-spin-button {
	height: auto;
}

input[type=text].wsf-field.minicolors-input {
	padding: <?php self::e(($form_spacing_vertical . $unit_of_measurement . ' ' . $form_spacing_horizontal . $unit_of_measurement) . ' ' . $form_spacing_vertical . $unit_of_measurement . ' ' . $color_size . $unit_of_measurement); ?>;
}

select.wsf-field:not([multiple]):not([size]) {
	background-image: url('data:image/svg+xml,<svg%20width%3D"10"%20height%3D"5"%20viewBox%3D"169%20177%2010%205"%20xmlns%3D"http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg"><path%20fill%3D"<?php echo urlencode($form_color); ?>"%20fill-rule%3D"evenodd"%20d%3D"M174%20182l5-5h-10"%2F><%2Fsvg>');
	background-position: right <?php self::e($spacing_small . $unit_of_measurement); ?> center;
	background-repeat: no-repeat;
	background-size: <?php self::e($spacing_small . $unit_of_measurement . ' ' . $spacing_extra_small . $unit_of_measurement); ?>;
	padding-right: <?php self::e((($form_spacing_horizontal * 2) + $spacing_small) . $unit_of_measurement); ?>;
}

select.wsf-field:not([multiple]):not([size])::-ms-expand {
	display: none;
}

<?php if ($form_hover) { ?>
<?php if ($form_hover_color != $form_color) { ?>
	select.wsf-field:not([multiple]):not([size]):hover {
		background-image: url('data:image/svg+xml,<svg%20width%3D"10"%20height%3D"5"%20viewBox%3D"169%20177%2010%205"%20xmlns%3D"http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg"><path%20fill%3D"<?php echo urlencode($form_hover_color); ?>"%20fill-rule%3D"evenodd"%20d%3D"M174%20182l5-5h-10"%2F><%2Fsvg>');
	}
<?php } ?>
<?php } ?>

<?php if ($form_focus) { ?>
<?php if ($form_focus_color != $form_color) { ?>
select.wsf-field:not([multiple]):not([size]):focus {
	background-image: url('data:image/svg+xml,<svg%20width%3D"10"%20height%3D"5"%20viewBox%3D"169%20177%2010%205"%20xmlns%3D"http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg"><path%20fill%3D"<?php echo urlencode($form_focus_color); ?>"%20fill-rule%3D"evenodd"%20d%3D"M174%20182l5-5h-10"%2F><%2Fsvg>');
}
<?php } ?>
<?php } ?>

select.wsf-field:not([multiple]):not([size]):-moz-focusring {
	color: transparent;
	text-shadow: 0 0 0 #000;
}

select.wsf-field:not([multiple]):not([size]):disabled {
<?php if ($form_disabled_color != $form_color) { ?>
	border-color: <?php self::e($form_disabled_border_color); ?>;
	background-image: url('data:image/svg+xml,<svg%20width%3D"10"%20height%3D"5"%20viewBox%3D"169%20177%2010%205"%20xmlns%3D"http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg"><path%20fill%3D"<?php echo urlencode($form_disabled_color); ?>"%20fill-rule%3D"evenodd"%20d%3D"M174%20182l5-5h-10"%2F><%2Fsvg>');
<?php } ?>
}

select.wsf-field optgroup {
	font-weight: bold;
}

<?php if ($form_disabled_color != $form_color) { ?>
select.wsf-field option:disabled {
	color: <?php self::e($form_disabled_color); ?>;
}
<?php } ?>

textarea.wsf-field {
	overflow: auto;
	resize: vertical;
}

textarea.wsf-field[data-textarea-type='tinymce'] {
	border-top-left-radius: 0;
	border-top-right-radius: 0;
}

[data-type='textarea'] .mce-btn.mce-active button,
[data-type='textarea'] .mce-btn.mce-active:hover button,
[data-type='textarea'] .mce-btn.mce-active i,
[data-type='textarea'] .mce-btn.mce-active:hover i {
	color: #000;
}

input[type=checkbox].wsf-field {
	height: <?php self::e($checkbox_size . $unit_of_measurement); ?>;
	margin: 0;
	opacity: 0;
	position: absolute;
	width: <?php self::e($checkbox_size . $unit_of_measurement); ?>;
}

input[type=checkbox].wsf-field + label.wsf-label {
	display: inline-block;
	margin: 0 0 <?php self::e($spacing_small . $unit_of_measurement); ?>;
	padding-left: <?php self::e(($checkbox_size + $spacing_extra_small) . $unit_of_measurement); ?>;
	position: relative;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
}

input[type=checkbox].wsf-field + label.wsf-label:before {
	background-color: <?php self::e($form_background_color); ?>;
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	content: '';
	cursor: pointer;
	display: inline-block;
	height: <?php self::e($checkbox_size . $unit_of_measurement); ?>;
	left: 0;
	margin-bottom: -<?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
	margin-right: <?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
	position: absolute;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>, box-shadow <?php self::e($form_transition_speed); ?>;
<?php } ?>
	vertical-align: top;
	width: <?php self::e($checkbox_size . $unit_of_measurement); ?>;
}

input[type=checkbox].wsf-field + label.wsf-label + .wsf-invalid-feedback {
	margin-bottom: <?php self::e($spacing_small . $unit_of_measurement); ?>;
	margin-top: -<?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
}

<?php if ($form_hover) { ?>
input[type=checkbox].wsf-field:enabled:hover + label.wsf-label:before {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_hover_background_color); ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_hover_border_colo); ?>
<?php } ?>
}
<?php } ?>

<?php if ($form_focus) { ?>
input[type=checkbox].wsf-field:focus + label.wsf-label:before {
<?php if ($form_focus_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_focus_background_color); ?>;
<?php } ?>
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_focus_border_color); ?>;
<?php } ?>
}
<?php } ?>

input[type=checkbox].wsf-field:disabled + label.wsf-label {
<?php if ($form_disabled_color != $form_color) { ?>
	color: <?php self::e($form_disabled_color); ?>;
<?php } ?>
}

input[type=checkbox].wsf-field:disabled + label.wsf-label:before {
<?php if ($form_disabled_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_disabled_background_color); ?>;
<?php } ?>
<?php if ($form_border) { ?>
<?php if ($form_disabled_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_disabled_border_color); ?>;
<?php } ?>
<?php } ?>
cursor: not-allowed;
}

input[type=checkbox].wsf-field:checked + label.wsf-label:before {
	background-color: <?php self::e($form_checked_color); ?>;
	border-color: <?php self::e($form_checked_color); ?>;
	box-shadow: inset 0 0 0 2px <?php self::e($color_default_inverted); ?>;
}

input[type=checkbox].wsf-field:checked:disabled + label.wsf-label:before {
	opacity: .5;
}

input[type=checkbox].wsf-field.wsf-switch {
	width: <?php self::e(($checkbox_size * 2) . $unit_of_measurement); ?>;
}

input[type=checkbox].wsf-field.wsf-switch + label.wsf-label {
	padding-left: <?php self::e((($checkbox_size * 2) + $spacing_extra_small) . $unit_of_measurement); ?>;
	position: relative;
}

input[type=checkbox].wsf-field.wsf-switch + label.wsf-label:before {
	position: absolute;
	width: <?php self::e(($checkbox_size * 2) . $unit_of_measurement); ?>;
}

input[type=checkbox].wsf-field.wsf-switch + label.wsf-label:after {
	background-color: <?php self::e($form_border_color); ?>;
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	content: '';
	cursor: pointer;
	display: inline-block;
	height: <?php self::e($checkbox_size . $unit_of_measurement); ?>;
	left: 0;
	position: absolute;
	top: 0;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>, left <?php self::e($form_transition_speed); ?>;
<?php } ?>
	vertical-align: top;
	width: <?php self::e($checkbox_size . $unit_of_measurement); ?>;
}

<?php if ($form_hover) { ?>
input[type=checkbox].wsf-field.wsf-switch:enabled:hover + label.wsf-label:after {
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_hover_border_colo); ?>
<?php } ?>
}
<?php } ?>

<?php if ($form_focus) { ?>
input[type=checkbox].wsf-field.wsf-switch:focus + label.wsf-label:after {
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_focus_border_color); ?>;
<?php } ?>
}
<?php } ?>

input[type=checkbox].wsf-field.wsf-switch:disabled + label.wsf-label:after {
<?php if ($form_border) { ?>
<?php if ($form_disabled_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_disabled_border_color); ?>;
<?php } ?>
<?php } ?>
	cursor: not-allowed;
}

input[type=checkbox].wsf-field.wsf-switch:checked + label.wsf-label:before {
	background-color: <?php self::e($form_background_color); ?>;
	box-shadow: none;
}

input[type=checkbox].wsf-field.wsf-switch:checked + label.wsf-label:after {
	background-color: <?php self::e($form_checked_color); ?>;
	border-color: <?php self::e($form_checked_color); ?>;
	left: <?php self::e($checkbox_size . $unit_of_measurement); ?>;
}

input[type=checkbox].wsf-field.wsf-switch:checked:disabled + label.wsf-label:before,
input[type=checkbox].wsf-field.wsf-switch:checked:disabled + label.wsf-label:after {
	opacity: .5;
}

input[type=radio].wsf-field {
	height: <?php self::e($radio_size . $unit_of_measurement); ?>;
	margin: 0;
	opacity: 0;
	position: absolute;
	width: <?php self::e($radio_size . $unit_of_measurement); ?>;
}

input[type=radio].wsf-field + label.wsf-label {
	display: inline-block;
	margin: 0 0 <?php self::e($spacing_small . $unit_of_measurement); ?>;
	padding-left: <?php self::e(($radio_size + $spacing_extra_small) . $unit_of_measurement); ?>;
	position: relative;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
}

input[type=radio].wsf-field + label.wsf-label:before {
	background-color: <?php self::e($form_background_color); ?>;
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } ?>
	border-radius: 50%;
	content: '';
	cursor: pointer;
	display: inline-block;
	height: <?php self::e($radio_size . $unit_of_measurement); ?>;
	left: 0;
	margin-bottom: -<?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
	margin-right: <?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
	position: absolute;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>, box-shadow <?php self::e($form_transition_speed); ?>;
<?php } ?>
	vertical-align: top;
	width: <?php self::e($radio_size . $unit_of_measurement); ?>;
}

input[type=radio].wsf-field + label.wsf-label + .wsf-invalid-feedback {
	margin-bottom: <?php self::e($spacing_small . $unit_of_measurement); ?>;
	margin-top: -<?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
}

<?php if ($form_hover) { ?>
input[type=radio].wsf-field:enabled:hover + label.wsf-label:before {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_hover_background_color); ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_hover_border_colo); ?>
<?php } ?>
}
<?php } ?>

<?php if ($form_focus) { ?>
input[type=radio].wsf-field:focus + label.wsf-label:before {
<?php if ($form_focus_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_focus_background_color); ?>;
<?php } ?>
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_focus_border_color); ?>;
<?php } ?>
}
<?php } ?>

input[type=radio].wsf-field:disabled + label.wsf-label {
<?php if ($form_disabled_color != $form_color) { ?>
	color: <?php self::e($form_disabled_color); ?>;
<?php } ?>
}

input[type=radio].wsf-field:disabled + label.wsf-label:before {
<?php if ($form_disabled_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_disabled_background_color); ?>;
<?php } ?>
<?php if ($form_border) { ?>
<?php if ($form_disabled_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_disabled_border_color); ?>;
<?php } ?>
<?php } ?>
	cursor: not-allowed;
}

input[type=radio].wsf-field:checked + label.wsf-label:before {
	background-color: <?php self::e($form_checked_color); ?>;
	border-color: <?php self::e($form_checked_color); ?>;
	box-shadow: inset 0 0 0 2px <?php self::e($color_default_inverted); ?>;
}

input[type=radio].wsf-field:checked:disabled + label.wsf-label:before {
	opacity: .5;
}

input[type=checkbox].wsf-field.wsf-button + label.wsf-label,
input[type=radio].wsf-field.wsf-button + label.wsf-label {
  	background-color: <?php self::e($color_default_lighter); ?>;
  <?php if ($form_border) { ?>
  	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
  <?php } else { ?>
  	border: none;
  <?php } ?>
  <?php if ($form_border_radius > 0) { ?>
  	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
  <?php } ?>
  	color: <?php self::e($form_color); ?>;
  	cursor: pointer;
  	display: inline-block;
  	font-family: <?php self::e($font_family); ?>;
  	font-size: <?php self::e($form_font_size . $unit_of_measurement); ?>;
  	font-weight: <?php self::e($font_weight); ?>;
  	height: <?php self::e($input_height . $unit_of_measurement); ?>;
  	line-height: <?php self::e($line_height); ?>;
  	padding: <?php self::e($form_spacing_vertical . $unit_of_measurement . ' ' . $form_spacing_horizontal . $unit_of_measurement); ?>;
  	margin: 0 0 <?php self::e(($grid_gutter / 2) . $unit_of_measurement); ?>;
  	text-align: center;
  	text-decoration: none;
  	touch-action: manipulation;
  <?php if ($form_transition) { ?>
  	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>, color <?php self::e($form_transition_speed); ?>;
  <?php } ?>
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
  	vertical-align: middle;
}

input[type=checkbox].wsf-field.wsf-button + label.wsf-label:before,
input[type=radio].wsf-field.wsf-button + label.wsf-label:before {
	display: none;
}

input[type=checkbox].wsf-field.wsf-button:disabled + label.wsf-label,
input[type=radio].wsf-field.wsf-button:disabled + label.wsf-label {
	cursor: not-allowed;
	opacity: .5;
}

input[type=checkbox].wsf-field.wsf-button:checked + label.wsf-label,
input[type=radio].wsf-field.wsf-button:checked + label.wsf-label {
	background-color: <?php self::e($color_primary); ?>;
	border-color: <?php self::e($color_primary); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}

input[type=checkbox].wsf-field.wsf-color,
input[type=radio].wsf-field.wsf-color {
	height: <?php self::e($color_size . $unit_of_measurement); ?>;
	width: <?php self::e($color_size . $unit_of_measurement); ?>;
}

input[type=checkbox].wsf-field.wsf-color + label.wsf-label,
input[type=radio].wsf-field.wsf-color + label.wsf-label {
	margin-left: 0;
	padding-left: 0;
	position: relative;
}

input[type=checkbox].wsf-field.wsf-color + label.wsf-label:before,
input[type=radio].wsf-field.wsf-color + label.wsf-label:before {
	display: none;
}

input[type=checkbox].wsf-field.wsf-color + label.wsf-label > span {
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	cursor: pointer;
	display: inline-block;
	height: <?php self::e($color_size . $unit_of_measurement); ?>;
<?php if ($form_transition) { ?>
	transition: border-color <?php self::e($form_transition_speed); ?>, box-shadow <?php self::e($form_transition_speed); ?>;
<?php } ?>
	vertical-align: middle;
	width: <?php self::e($color_size . $unit_of_measurement); ?>;
}

input[type=radio].wsf-field.wsf-color + label.wsf-label > span {
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } ?>
	border-radius: 50%;
	cursor: pointer;
	display: inline-block;
	height: <?php self::e($color_size . $unit_of_measurement); ?>;
<?php if ($form_transition) { ?>
	transition: border-color <?php self::e($form_transition_speed); ?>, box-shadow <?php self::e($form_transition_speed); ?>;
<?php } ?>
	vertical-align: middle;
	width: <?php self::e($color_size . $unit_of_measurement); ?>;
}

<?php if ($form_hover) { ?>
input[type=checkbox].wsf-field.wsf-color:enabled:hover + label.wsf-label > span,
input[type=radio].wsf-field.wsf-color:enabled:hover + label.wsf-label > span {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_hover_background_color); ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_hover_border_colo); ?>
<?php } ?>
}
<?php } ?>

<?php if ($form_focus) { ?>
input[type=checkbox].wsf-field.wsf-color:focus + label.wsf-label > span,
input[type=radio].wsf-field.wsf-color:focus + label.wsf-label > span {
<?php if ($form_focus_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_focus_background_color); ?>;
<?php } ?>
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_focus_border_color); ?>;
<?php } ?>
}
<?php } ?>

input[type=checkbox].wsf-field.wsf-color:disabled + label.wsf-label > span,
input[type=radio].wsf-field.wsf-color:disabled + label.wsf-label > span {
	cursor: not-allowed;
	opacity: .5;
}

input[type=checkbox].wsf-field.wsf-color:checked + label.wsf-label > span,
input[type=radio].wsf-field.wsf-color:checked + label.wsf-label > span {
	border-color: <?php self::e($form_checked_color); ?>;
	box-shadow: inset 0 0 0 2px <?php self::e($color_default_inverted); ?>;
}

input[type=checkbox].wsf-field.wsf-image + label.wsf-label,
input[type=radio].wsf-field.wsf-image + label.wsf-label {
	margin-left: 0;
	padding-left: 0;
	position: relative;
}

input[type=checkbox].wsf-field.wsf-image + label.wsf-label:before,
input[type=radio].wsf-field.wsf-image + label.wsf-label:before {
	display: none;
}

input[type=checkbox].wsf-field.wsf-image + label.wsf-label > img,
input[type=radio].wsf-field.wsf-image + label.wsf-label > img {
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	cursor: pointer;
	display: inline-block;
	height: auto;
	max-width: 100%;
	padding: 2px;
<?php if ($form_transition) { ?>
	transition: border-color <?php self::e($form_transition_speed); ?>, box-shadow <?php self::e($form_transition_speed); ?>;
<?php } ?>
	vertical-align: middle;
}

<?php if ($form_hover) { ?>
input[type=checkbox].wsf-field.wsf-image:enabled:hover + label.wsf-label > img,
input[type=radio].wsf-field.wsf-image:enabled:hover + label.wsf-label > img {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_hover_background_color); ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_hover_border_colo); ?>
<?php } ?>
}
<?php } ?>

<?php if ($form_focus) { ?>
input[type=checkbox].wsf-field.wsf-image:focus + label.wsf-label > img,
input[type=radio].wsf-field.wsf-image:focus + label.wsf-label > img {
<?php if ($form_focus_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_focus_background_color); ?>;
<?php } ?>
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_focus_border_color); ?>;
<?php } ?>
}
<?php } ?>

input[type=checkbox].wsf-field.wsf-image:disabled + label.wsf-label > img,
input[type=radio].wsf-field.wsf-image:disabled + label.wsf-label > img {
	cursor: not-allowed;
	opacity: .5;
}

input[type=checkbox].wsf-field.wsf-image:checked + label.wsf-label > img,
input[type=radio].wsf-field.wsf-image:checked + label.wsf-label > img {
	border-color: <?php self::e($form_checked_color); ?>;
}

input[type=file].wsf-field {
	height: 0;
	opacity: 0;
	position: absolute;
	width: 0;
}

input[type=file].wsf-field + label.wsf-label {
	background-color: <?php self::e($form_background_color); ?>;
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } else { ?>
	border: none;
<?php } ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
	color: <?php self::e($form_color); ?>;
	cursor: pointer;
	font-family: <?php self::e($font_family); ?>;
	font-size: <?php self::e($form_font_size . $unit_of_measurement); ?>;
	font-weight: <?php self::e($font_weight); ?>;
	height: <?php self::e($input_height . $unit_of_measurement); ?>;
	line-height: <?php self::e($line_height); ?>;
	margin: 0;
	overflow: hidden;
	text-overflow: ellipsis;
	padding: <?php self::e($form_spacing_vertical . $unit_of_measurement . ' ' . $form_spacing_horizontal . $unit_of_measurement); ?>;
	position: relative;
	touch-action: manipulation;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>;
<?php } ?>
	white-space: nowrap;
	width: 100%;
}

input[type=file].wsf-field + label.wsf-label:after {
	background-color: <?php self::e($color_default_lighter); ?>;
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-bottom-right-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
	border-top-right-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	color: <?php self::e($form_color) ?>;
	content: 'Browse';
	display: inline-block;
	font-family: <?php self::e($font_family); ?>;
	font-size: <?php self::e($form_font_size . $unit_of_measurement); ?>;
	font-weight: <?php self::e($font_weight); ?>;
	height: <?php self::e($input_height . $unit_of_measurement); ?>;
	line-height: <?php self::e($line_height); ?>;
	padding: <?php self::e($form_spacing_vertical . $unit_of_measurement . ' ' . $form_spacing_horizontal . $unit_of_measurement); ?>;
	position: absolute;
	right: -<?php self::e($form_border_width . $unit_of_measurement); ?>;
	top: -<?php self::e($form_border_width . $unit_of_measurement); ?>;
	touch-action: manipulation;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>;
<?php } ?>
}

<?php if ($form_hover) { ?>
input[type=file].wsf-field:enabled + label.wsf-label:hover {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_hover_background_color); ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_hover_border_color); ?>;
<?php } ?>
}

input[type=file].wsf-field:enabled + label.wsf-label:hover:after {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($form_border_color, 10)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($form_border_color, 10)); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
input[type=file].wsf-field:focus + label.wsf-label {
<?php if ($form_focus_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_focus_background_color); ?>;
<?php } ?>
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_focus_border_color); ?>;
<?php } ?>
}

input[type=file].wsf-field:focus + label.wsf-label:after {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($form_border_color, 20)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($form_border_color, 20)); ?>;
}
<?php } ?>

input[type=file].wsf-field:disabled + label.wsf-label {
<?php if ($form_disabled_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_disabled_background_color); ?>;
<?php } ?>
<?php if ($form_border) { ?>
<?php if ($form_disabled_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_disabled_border_color); ?>;
<?php } ?>
<?php } ?>
<?php if ($form_disabled_color != $form_color) { ?>
	color: <?php self::e($form_disabled_color); ?>;
<?php } ?>
	cursor: not-allowed;
}

input[type=file].wsf-field:disabled + label.wsf-label:after {
	opacity: .5;
}

input[type=color].wsf-field:not(.minicolors-input) {
	-webkit-appearance: none;
	background: none;
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } else { ?>
	border: none;
<?php } ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
	box-shadow: inset 0 0 0 2px <?php self::e($color_default_inverted); ?>;
	cursor: pointer;
	display: block;
	height: <?php self::e($color_size . $unit_of_measurement); ?>;
	padding: 0;
<?php if ($form_transition) { ?>
	transition: border-color <?php self::e($form_transition_speed); ?>;
<?php } ?>
	width: <?php self::e($color_size . $unit_of_measurement); ?>;
}

<?php if ($form_hover) { ?>
input[type=color].wsf-field:hover:enabled {
<?php if ($form_hover_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_hover_background_color); ?>;
<?php } ?>
<?php if ($form_hover_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_hover_border_color); ?>;
<?php } ?>
<?php if ($form_hover_color != $form_color) { ?>
	color: <?php self::e($form_hover_color); ?>;
<?php } ?>
}
<?php } ?>

input[type=color].wsf-field:focus {
<?php if ($form_focus) { ?>
<?php if ($form_focus_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_focus_border_color); ?>;
<?php } ?>
<?php } ?>
	outline: 0;
}

input[type=color].wsf-field:disabled {
<?php if ($form_border) { ?>
<?php if ($form_disabled_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_disabled_border_color); ?>;
<?php } ?>
<?php } ?>
	cursor: not-allowed;
	opacity: .5;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
}

input[type=color].wsf-field::-moz-focus-inner {
	border: 0;
	padding: 0;
}

input[type=color].wsf-field::-webkit-color-swatch-wrapper {
	padding: 0;
}

input[type=color].wsf-field::-webkit-color-swatch {
	border: none;
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
	box-shadow: inset 0 0 0 2px <?php self::e($color_default_inverted); ?>;
}

input[type=color].wsf-field.minicolors-input {
	padding-left: <?php self::e(($input_height + $form_spacing_horizontal) . $unit_of_measurement); ?>;
}

.minicolors-theme-ws-form .minicolors-swatch {
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } else { ?>
	border: none;
<?php } ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
	cursor: pointer;
	height: <?php self::e(($input_height - ($spacing_extra_small * 2)) . $unit_of_measurement); ?>;
	left: <?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
	top: <?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
	width: <?php self::e(($input_height - ($spacing_extra_small * 2)) . $unit_of_measurement); ?>;
}

.minicolors-theme-ws-form .minicolors-swatch-color {
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
}

input[type=password].wsf-field[data-password-strength-meter] {
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='<?php self::e($spacing_extra_small . $unit_of_measurement); ?>' height='<?php self::e(($spacing_extra_small + $spacing) . $unit_of_measurement); ?>' viewBox='0 0 5 25'%3E%3Cpath fill='<?php echo urlencode($color_default_lighter); ?>' d='M2.5,20 C1.125,20 0,21.125 0,22.5 C0,23.875 1.125,25 2.5,25 C3.875,25 5,23.875 5,22.5 C5,21.125 3.875,20 2.5,20 Z'%3E%3C/path%3E%3Cpath fill='<?php echo urlencode($color_default_lighter); ?>' d='M2.5,10 C1.125,10 0,11.125 0,12.5 C0,13.875 1.125,15 2.5,15 C3.875,15 5,13.875 5,12.5 C5,11.125 3.875,10 2.5,10 Z'%3E%3C/path%3E%3Cpath fill='<?php echo urlencode($color_default_lighter); ?>' d='M2.5,5 C3.875,5 5,3.875 5,2.5 C5,1.125 3.875,0 2.5,0 C1.125,0 0,1.125 0,2.5 C0,3.875 1.125,5 2.5,5 Z'%3E%3C/path%3E%3C/svg%3E");
	background-position: right <?php self::e($spacing_extra_small . $unit_of_measurement); ?> center;
	background-repeat: no-repeat;
	background-size: <?php self::e($spacing_extra_small . $unit_of_measurement . ' ' . ($spacing_extra_small + $spacing) . $unit_of_measurement); ?>;
	padding-right: <?php self::e(($form_spacing_horizontal + ($spacing_extra_small * 2)) . $unit_of_measurement); ?>;
}

input[type=password].wsf-field[data-password-strength-meter=success] {
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='<?php self::e($spacing_extra_small . $unit_of_measurement); ?>' height='<?php self::e(($spacing_extra_small + $spacing) . $unit_of_measurement); ?>' viewBox='0 0 5 25'%3E%3Cpath fill='<?php echo urlencode($color_success); ?>' d='M2.5,20 C1.125,20 0,21.125 0,22.5 C0,23.875 1.125,25 2.5,25 C3.875,25 5,23.875 5,22.5 C5,21.125 3.875,20 2.5,20 Z'%3E%3C/path%3E%3Cpath fill='<?php echo urlencode($color_success); ?>' d='M2.5,10 C1.125,10 0,11.125 0,12.5 C0,13.875 1.125,15 2.5,15 C3.875,15 5,13.875 5,12.5 C5,11.125 3.875,10 2.5,10 Z'%3E%3C/path%3E%3Cpath fill='<?php echo urlencode($color_success); ?>' d='M2.5,5 C3.875,5 5,3.875 5,2.5 C5,1.125 3.875,0 2.5,0 C1.125,0 0,1.125 0,2.5 C0,3.875 1.125,5 2.5,5 Z'%3E%3C/path%3E%3C/svg%3E");
	background-position: right <?php self::e($spacing_extra_small . $unit_of_measurement); ?> center;
	background-repeat: no-repeat;
	background-size: <?php self::e($spacing_extra_small . $unit_of_measurement . ' ' . ($spacing_extra_small + $spacing) . $unit_of_measurement); ?>;
	padding-right: <?php self::e(($form_spacing_horizontal + ($spacing_extra_small * 2)) . $unit_of_measurement); ?>;
}

input[type=password].wsf-field[data-password-strength-meter=warning] {
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='<?php self::e($spacing_extra_small . $unit_of_measurement); ?>' height='<?php self::e(($spacing_extra_small + $spacing) . $unit_of_measurement); ?>' viewBox='0 0 5 25'%3E%3Cpath fill='<?php echo urlencode($color_warning); ?>' d='M2.5,20 C1.125,20 0,21.125 0,22.5 C0,23.875 1.125,25 2.5,25 C3.875,25 5,23.875 5,22.5 C5,21.125 3.875,20 2.5,20 Z'%3E%3C/path%3E%3Cpath fill='<?php echo urlencode($color_warning); ?>' d='M2.5,10 C1.125,10 0,11.125 0,12.5 C0,13.875 1.125,15 2.5,15 C3.875,15 5,13.875 5,12.5 C5,11.125 3.875,10 2.5,10 Z'%3E%3C/path%3E%3Cpath fill='<?php echo urlencode($color_default_lighter); ?>' d='M2.5,5 C3.875,5 5,3.875 5,2.5 C5,1.125 3.875,0 2.5,0 C1.125,0 0,1.125 0,2.5 C0,3.875 1.125,5 2.5,5 Z'%3E%3C/path%3E%3C/svg%3E");
	background-position: right <?php self::e($spacing_extra_small . $unit_of_measurement); ?> center;
	background-repeat: no-repeat;
	background-size: <?php self::e($spacing_extra_small . $unit_of_measurement . ' ' . ($spacing_extra_small + $spacing) . $unit_of_measurement); ?>;
	padding-right: <?php self::e(($form_spacing_horizontal + ($spacing_extra_small * 2)) . $unit_of_measurement); ?>;
}

input[type=password].wsf-field[data-password-strength-meter=danger] {
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='<?php self::e($spacing_extra_small . $unit_of_measurement); ?>' height='<?php self::e(($spacing_extra_small + $spacing) . $unit_of_measurement); ?>' viewBox='0 0 5 25'%3E%3Cpath fill='<?php echo urlencode($color_danger); ?>' d='M2.5,20 C1.125,20 0,21.125 0,22.5 C0,23.875 1.125,25 2.5,25 C3.875,25 5,23.875 5,22.5 C5,21.125 3.875,20 2.5,20 Z'%3E%3C/path%3E%3Cpath fill='<?php echo urlencode($color_default_lighter); ?>' d='M2.5,10 C1.125,10 0,11.125 0,12.5 C0,13.875 1.125,15 2.5,15 C3.875,15 5,13.875 5,12.5 C5,11.125 3.875,10 2.5,10 Z'%3E%3C/path%3E%3Cpath fill='<?php echo urlencode($color_default_lighter); ?>' d='M2.5,5 C3.875,5 5,3.875 5,2.5 C5,1.125 3.875,0 2.5,0 C1.125,0 0,1.125 0,2.5 C0,3.875 1.125,5 2.5,5 Z'%3E%3C/path%3E%3C/svg%3E");
	background-position: right <?php self::e($spacing_extra_small . $unit_of_measurement); ?> center;
	background-repeat: no-repeat;
	background-size: <?php self::e($spacing_extra_small . $unit_of_measurement . ' ' . ($spacing_extra_small + $spacing) . $unit_of_measurement); ?>;
	padding-right: <?php self::e(($form_spacing_horizontal + ($spacing_extra_small * 2)) . $unit_of_measurement); ?>;
}

.wsf-validated canvas.wsf-field.wsf-invalid {
<?php if ($form_error_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_error_background_color); ?>;
<?php } ?>
<?php if ($form_border) { ?>
<?php if ($form_error_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_error_border_color); ?>;
<?php } ?>
<?php } ?>
}

.wsf-validated input[type=date].wsf-field:invalid,
.wsf-validated input[type=datetime-local].wsf-field:invalid,
.wsf-validated input[type=month].wsf-field:invalid,
.wsf-validated input[type=password].wsf-field:invalid,
.wsf-validated input[type=search].wsf-field:invalid,
.wsf-validated input[type=time].wsf-field:invalid,
.wsf-validated input[type=week].wsf-field:invalid,
.wsf-validated input[type=email].wsf-field:invalid,
.wsf-validated input[type=number].wsf-field:invalid,
.wsf-validated input[type=tel].wsf-field:invalid,
.wsf-validated input[type=text].wsf-field:invalid,
.wsf-validated input[type=search].wsf-field:invalid,
.wsf-validated input[type=url].wsf-field:invalid,
.wsf-validated select.wsf-field:invalid,
.wsf-validated textarea.wsf-field:invalid {
<?php if ($form_error_background_color != $form_background_color) { ?>
	background-color: <?php self::e($form_error_background_color); ?>;
<?php } ?>
<?php if ($form_border) { ?>
<?php if ($form_error_border_color != $form_border_color) { ?>
	border-color: <?php self::e($form_error_border_color); ?>;
<?php } ?>
<?php } ?>
<?php if ($form_error_border_color != $form_color) { ?>
	color: <?php self::e($form_error_color); ?>;
<?php } ?>
}

.wsf-validated input[type=date].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=datetime-local].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=month].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=password].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=search].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=time].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=week].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=email].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=number].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=tel].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=text].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=search].wsf-field:-moz-ui-invalid,
.wsf-validated input[type=url].wsf-field:-moz-ui-invalid,
.wsf-validated select.wsf-field:-moz-ui-invalid,
.wsf-validated textarea.wsf-field:-moz-ui-invalid {
	box-shadow: none;
}

<?php if ($form_error_color != $form_color) { ?>
select.wsf-field:not([multiple]):not([size]):invalid {
	background-image: url('data:image/svg+xml,<svg%20width%3D"10"%20height%3D"5"%20viewBox%3D"169%20177%2010%205"%20xmlns%3D"http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg"><path%20fill%3D"<?php echo urlencode($form_error_color); ?>"%20fill-rule%3D"evenodd"%20d%3D"M174%20182l5-5h-10"%2F><%2Fsvg>');
}
<?php } ?>

<?php if ($form_border) { ?>
<?php if ($form_error_border_color != $form_border_color) { ?>
.wsf-validated input[type=checkbox].wsf-field:invalid + label.wsf-label:before,
.wsf-validated input[type=checkbox].wsf-field.wsf-switch:invalid + label.wsf-label:after,
.wsf-validated input[type=radio].wsf-field:invalid + label.wsf-label:before {
	border-color: <?php self::e($form_error_border_color); ?>;
}
<?php } ?>
<?php } ?>
<?php if ($form_border) { ?>
<?php if ($form_error_border_color != $form_border_color) { ?>
.wsf-validated input[type=file].wsf-field:invalid + label.wsf-label {
	border-color: <?php self::e($form_error_border_color); ?>;
}
<?php } ?>
<?php } ?>

input[type=range].wsf-field {
	-webkit-appearance: none;
	background: none;
	background-size: calc(100% - <?php self::e($range_size . $unit_of_measurement); ?>);
	background-position: <?php self::e(($range_size / 2) . $unit_of_measurement); ?> center;
	border: none;
	margin: <?php self::e(($range_size / 2) . $unit_of_measurement); ?> 0 0;
	padding: 0;
	width: 100%;
	--color-lower-track: <?php self::e($color_primary); ?>;
	--color-upper-track: <?php self::e($color_default_lightest); ?>;
	--fill-lower-track: 0%;
}

input[type=range].wsf-field[data-fill-lower-track]::-webkit-slider-runnable-track {
	background-image: linear-gradient(to right, 
		var(--color-lower-track),
		var(--color-lower-track) var(--fill-lower-track),
		var(--color-upper-track) var(--fill-lower-track),
		var(--color-upper-track) 100%
	);
}
input[type=range].wsf-field[data-fill-lower-track]::-moz-range-track {
	background-image: linear-gradient(to right, 
		var(--color-lower-track),
		var(--color-lower-track) var(--fill-lower-track),
		var(--color-upper-track) var(--fill-lower-track),
		var(--color-upper-track) 100%
	);
}

input[type=range].wsf-field::-webkit-slider-runnable-track {
	background-color: <?php self::e($color_default_lightest); ?>;
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	cursor: pointer;
	height: <?php self::e(($range_size / 2) . $unit_of_measurement); ?>;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>;
<?php } ?>
}

input[type=range].wsf-field::-moz-range-track {
	background-color: <?php self::e($color_default_lightest); ?>;
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	cursor: pointer;
	height: <?php self::e(($range_size / 2) . $unit_of_measurement); ?>;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>;
<?php } ?>
}

input[type=range].wsf-field::-ms-track {
	background-color: <?php self::e($color_default_lightest); ?>;
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	cursor: pointer;
	height: <?php self::e(($range_size / 2) . $unit_of_measurement); ?>;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>;
<?php } ?>
	background-color: transparent;
	border-color: transparent;
	border-color: transparent;
	color: transparent;
}

input[type=range].wsf-field::-ms-fill-lower,
input[type=range].wsf-field::-ms-fill-upper {
	background-color: <?php self::e($color_default_lightest); ?>;
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	cursor: pointer;
	height: <?php self::e(($range_size / 2) . $unit_of_measurement); ?>;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>;
<?php } ?>
}

input[type=range].wsf-field[data-fill-lower-track]::-ms-fill-lower {
	background-color: <?php self::e($color_primary); ?>;
}

input[type=range].wsf-field::-webkit-slider-thumb {
	-webkit-appearance: none;
	background-color: <?php self::e($color_primary); ?>;
	border-radius: 50%;
	cursor: pointer;
	height: <?php self::e($range_size . $unit_of_measurement); ?>;
	margin-top: -<?php self::e(($range_size / 4) . $unit_of_measurement); ?>;
	width: <?php self::e($range_size . $unit_of_measurement); ?>;
}

input[type=range].wsf-field::-moz-range-thumb {
	-webkit-appearance: none;
	background-color: <?php self::e($color_primary); ?>;
	border: none;
	border-radius: 50%;
	cursor: pointer;
	height: <?php self::e($range_size . $unit_of_measurement); ?>;
	margin-top: -<?php self::e(($range_size / 4) . $unit_of_measurement); ?>;
	width: <?php self::e($range_size . $unit_of_measurement); ?>;
}

input[type=range].wsf-field::-ms-thumb {
	-webkit-appearance: none;
	background-color: <?php self::e($color_primary); ?>;
	border: none;
	border-radius: 50%;
	cursor: pointer;
	height: <?php self::e($range_size . $unit_of_measurement); ?>;
	margin-top: -<?php self::e(($range_size / 4) . $unit_of_measurement); ?>;
	width: <?php self::e($range_size . $unit_of_measurement); ?>;
}

input[type=range].wsf-field:focus {
	outline: none;
}

input[type=range].wsf-field::-moz-focus-outer {
	border: 0;
}

input[type=range].wsf-field:disabled {
	opacity: .5;
}

input[type=range].wsf-field:disabled::-webkit-slider-runnable-track {
	cursor: not-allowed;
}

input[type=range].wsf-field:disabled::-moz-range-track {
	cursor: not-allowed;
}

input[type=range].wsf-field:disabled::-ms-fill-lower,
input[type=range].wsf-field:disabled::-ms-fill-upper {
	cursor: not-allowed;
}

input[type=range].wsf-field:disabled::-webkit-slider-thumb {
	cursor: not-allowed;
}

input[type=range].wsf-field:disabled::-moz-range-thumb {
	cursor: not-allowed;
}

input[type=range].wsf-field:disabled::-ms-thumb {
	cursor: not-allowed;
}

button.wsf-button {
	-webkit-appearance: none;
	background-color: <?php self::e($color_default_lighter); ?>;
<?php if ($form_border) { ?>
	border: <?php self::e($form_border_width . $unit_of_measurement . ' ' . $form_border_style . ' ' . $form_border_color); ?>;
<?php } else { ?>
	border: none;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	color: <?php self::e($form_color); ?>;
	cursor: pointer;
	display: inline-block;
	font-family: <?php self::e($font_family); ?>;
	font-size: <?php self::e($form_font_size . $unit_of_measurement); ?>;
	font-weight: <?php self::e($font_weight); ?>;
	height: <?php self::e($input_height . $unit_of_measurement); ?>;
	line-height: <?php self::e($line_height); ?>;
	padding: <?php self::e($form_spacing_vertical . $unit_of_measurement . ' ' . $form_spacing_horizontal . $unit_of_measurement); ?>;
	margin: 0;
	text-align: center;
	text-decoration: none;
	touch-action: manipulation;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>;
<?php } ?>
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
	vertical-align: middle;
}

button.wsf-button.wsf-button-full {
	width: 100%;
}

<?php if ($form_hover) { ?>
button.wsf-button:hover {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($form_border_color, 10)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($form_border_color, 10)); ?>;
}
<?php } ?>

button.wsf-button:focus,
button.wsf-button:active {
<?php if ($form_focus) { ?>
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_default_lighter, 20)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($form_border_color, 20)); ?>;
<?php } ?>
	outline: 0;
}

button.wsf-button:disabled {
	background-color: <?php self::e($color_default_lighter); ?>;
	border-color: <?php self::e($form_border_color); ?>;
}

button.wsf-button.wsf-button-primary {
	background-color: <?php self::e($color_primary); ?>;
	border-color: <?php self::e($color_primary); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-primary:hover {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_primary, 10)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_primary, 10)); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-primary:focus,
button.wsf-button.wsf-button-primary:active {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_primary, 20)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_primary, 20)); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-primary:disabled {
	background-color: <?php self::e($color_primary); ?>;
	border-color: <?php self::e($color_primary); ?>;
}

button.wsf-button.wsf-button-secondary {
	background-color: <?php self::e($color_secondary); ?>;
	border-color: <?php self::e($color_secondary); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-secondary:hover {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_secondary, 10)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_secondary, 10)); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-secondary:focus,
button.wsf-button.wsf-button-secondary:active {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_secondary, 20)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_secondary, 20)); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-secondary:disabled {
	background-color: <?php self::e($color_secondary); ?>;
	border-color: <?php self::e($color_secondary); ?>;
}

button.wsf-button.wsf-button-success {
	background-color: <?php self::e($color_success); ?>;
	border-color: <?php self::e($color_success); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-success:hover {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_success, 10)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_success, 10)); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-success:focus,
button.wsf-button.wsf-button-success:active {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_success, 20)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_success, 20)); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-success:disabled {
	background-color: <?php self::e($color_success); ?>;
	border-color: <?php self::e($color_success); ?>;
}

button.wsf-button.wsf-button-information {
	background-color: <?php self::e($color_information); ?>;
	border-color: <?php self::e($color_information); ?>;
	color: <?php self::e($color_default); ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-information:hover {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage(esc_html($color_information), 10)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage(esc_html($color_information), 10)); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-information:focus,
button.wsf-button.wsf-button-information:active {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage(esc_html($color_information), 20)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage(esc_html($color_information), 20)); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-information:disabled {
	background-color: <?php self::e($color_information); ?>;
	border-color: <?php self::e($color_information); ?>;
}

button.wsf-button.wsf-button-warning {
	background-color: <?php self::e($color_warning); ?>;
	border-color: <?php self::e($color_warning); ?>;
	color: <?php self::e($color_default); ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-warning:hover {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_warning, 10)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_warning, 10)); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-warning:focus,
button.wsf-button.wsf-button-warning:active {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_warning, 20)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_warning, 20)); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-warning:disabled {
	background-color: <?php self::e($color_warning); ?>;
	border-color: <?php self::e($color_warning); ?>;
}

button.wsf-button.wsf-button-danger {
	background-color: <?php self::e($color_danger); ?>;
	border-color: <?php self::e($color_danger); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-danger:hover {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_danger, 10)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_danger, 10)); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-danger:focus,
button.wsf-button.wsf-button-danger:active {
	background-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_danger, 20)); ?>;
	border-color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_danger, 20)); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-danger:disabled {
	background-color: <?php self::e($color_danger); ?>;
	border-color: <?php self::e($color_danger); ?>;
}

<?php if ($form_border) { ?>
button.wsf-button.wsf-button-inverted {
	background-color: <?php self::e($form_background_color); ?>;
	border-color: <?php self::e($form_border_color); ?>;
	color: <?php self::e($form_color); ?>;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, color <?php self::e($form_transition_speed); ?>;
<?php } ?>
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted:hover {
	background-color: <?php self::e($color_default_lighter); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted:focus,
button.wsf-button.wsf-button-inverted:active {
	background-color: <?php self::e($color_default_lighter); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted:disabled {
	background-color: <?php self::e($form_background_color); ?>;
}

button.wsf-button.wsf-button-inverted.wsf-button-primary {
	border-color: <?php self::e($color_primary); ?>;
	color: <?php self::e($color_primary); ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-primary:hover {
	background-color: <?php self::e($color_primary); ?>;
	border-color: <?php self::e($color_primary); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-primary:focus {
	background-color: <?php self::e($color_primary); ?>;
	border-color: <?php self::e($color_primary); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted.wsf-button-primary:disabled {
	background-color: <?php self::e($form_background_color); ?>;
	border-color: <?php self::e($color_primary); ?>;
	color: <?php self::e($color_primary); ?>;
}

button.wsf-button.wsf-button-inverted.wsf-button-secondary {
	border-color: <?php self::e($color_secondary); ?>;
	color: <?php self::e($color_secondary); ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-secondary:hover {
	background-color: <?php self::e($color_secondary); ?>;
	border-color: <?php self::e($color_secondary); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-secondary:focus {
	background-color: <?php self::e($color_secondary); ?>;
	border-color: <?php self::e($color_secondary); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted.wsf-button-secondary:disabled {
	background-color: <?php self::e($form_background_color); ?>;
	border-color: <?php self::e($color_secondary); ?>;
	color: <?php self::e($color_secondary); ?>;
}

button.wsf-button.wsf-button-inverted.wsf-button-success {
	border-color: <?php self::e($color_success); ?>;
	color: <?php self::e($color_success); ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-success:hover {
	background-color: <?php self::e($color_success); ?>;
	border-color: <?php self::e($color_success); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-success:focus {
	background-color: <?php self::e($color_success); ?>;
	border-color: <?php self::e($color_success); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted.wsf-button-success:disabled {
	background-color: <?php self::e($form_background_color); ?>;
	border-color: <?php self::e($color_success); ?>;
	color: <?php self::e($color_success); ?>;
}

button.wsf-button.wsf-button-inverted.wsf-button-information {
	border-color: <?php self::e($color_information); ?>;
	color: <?php self::e($color_information); ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-information:hover {
	background-color: <?php self::e($color_information); ?>;
	border-color: <?php self::e($color_information); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-information:focus {
	background-color: <?php self::e($color_information); ?>;
	border-color: <?php self::e($color_information); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted.wsf-button-information:disabled {
	background-color: <?php self::e($form_background_color); ?>;
	border-color: <?php self::e($color_information); ?>;
	color: <?php self::e($color_information); ?>;
}

button.wsf-button.wsf-button-inverted.wsf-button-warning {
	border-color: <?php self::e($color_warning); ?>;
	color: <?php self::e($color_warning); ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-warning:hover {
	background-color: <?php self::e($color_warning); ?>;
	border-color: <?php self::e($color_warning); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-warning:focus {
	background-color: <?php self::e($color_warning); ?>;
	border-color: <?php self::e($color_warning); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted.wsf-button-warning:disabled {
	background-color: <?php self::e($form_background_color); ?>;
	border-color: <?php self::e($color_warning); ?>;
	color: <?php self::e($color_warning); ?>;
}

button.wsf-button.wsf-button-inverted.wsf-button-danger {
	border-color: <?php self::e($color_danger); ?>;
	color: <?php self::e($color_danger); ?>;
}

<?php if ($form_hover) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-danger:hover {
	background-color: <?php self::e($color_danger); ?>;
	border-color: <?php self::e($color_danger); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}
<?php } ?>

<?php if ($form_focus) { ?>
button.wsf-button.wsf-button-inverted.wsf-button-danger:focus {
	background-color: <?php self::e($color_danger); ?>;
	border-color: <?php self::e($color_danger); ?>;
	color: <?php self::e($color_default_inverted); ?>;
}
<?php } ?>

button.wsf-button.wsf-button-inverted.wsf-button-danger:disabled {
	background-color: <?php self::e($form_background_color); ?>;
	border-color: <?php self::e($color_danger); ?>;
	color: <?php self::e($color_danger); ?>;
}
<?php } ?>

button.wsf-button::-moz-focus-inner {
	border: 0;
	margin: 0;
	padding: 0;
}

button.wsf-button:disabled {
	cursor: not-allowed;
	opacity: .5;
	transition: none;
}

.wsf-form-post-lock-progress button[type="submit"].wsf-button {
	cursor: progress;
}
progress.wsf-progress {
  display: block;
  height: 20px;
  vertical-align: baseline;
  width: 100%;
}

progress.wsf-progress[value] {
  -webkit-appearance: none;
  background-color: <?php self::e($color_default_lightest); ?>;
  border: none;
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
}

progress.wsf-progress[value]::-webkit-progress-bar {
  background-color: <?php self::e($color_default_lightest); ?>;
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
}

progress.wsf-progress[value]::-webkit-progress-value {
  background-color: <?php self::e($color_primary); ?>;
<?php if ($form_border_radius > 0) { ?>
	border-bottom-left-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
	border-top-left-radius:  <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
}

progress.wsf-progress[value]::-moz-progress-bar {
  background-color: <?php self::e($color_primary); ?>;
<?php if ($form_border_radius > 0) { ?>
	border-bottom-left-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
	border-top-left-radius:  <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
}

progress.wsf-progress[value]::-ms-fill {
  background-color: <?php self::e($color_primary); ?>;
  border: 0;
}

progress.wsf-progress[value="100"]::-webkit-progress-value {
<?php if ($form_border_radius > 0) { ?>
	border-bottom-right-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
	border-top-right-radius:  <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
}

progress.wsf-progress[value="100"]::-moz-progress-bar {
<?php if ($form_border_radius > 0) { ?>
	border-bottom-right-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
	border-top-right-radius:  <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
}

progress.wsf-progress.wsf-progress-success[value]::-webkit-progress-value {
  background-color: <?php self::e($color_success); ?>;
}

progress.wsf-progress.wsf-progress-success[value]::-moz-progress-bar {
  background-color: <?php self::e($color_success); ?>;
}

progress.wsf-progress.wsf-progress-success[value]::-ms-fill {
  background-color: <?php self::e($color_success); ?>;
}

progress.wsf-progress.wsf-progress-information[value]::-webkit-progress-value {
  background-color: <?php self::e($color_information); ?>;
}

progress.wsf-progress.wsf-progress-information[value]::-moz-progress-bar {
  background-color: <?php self::e($color_information); ?>;
}

progress.wsf-progress.wsf-progress-information[value]::-ms-fill {
  background-color: <?php self::e($color_information); ?>;
}

progress.wsf-progress.wsf-progress-warning[value]::-webkit-progress-value {
  background-color: <?php self::e($color_warning); ?>;
}

progress.wsf-progress.wsf-progress-warning[value]::-moz-progress-bar {
  background-color: <?php self::e($color_warning); ?>;
}

progress.wsf-progress.wsf-progress-warning[value]::-ms-fill {
  background-color: <?php self::e($color_warning); ?>;
}

progress.wsf-progress.wsf-progress-danger[value]::-webkit-progress-value {
  background-color: <?php self::e($color_danger); ?>;
}

progress.wsf-progress.wsf-progress-danger[value]::-moz-progress-bar {
  background-color: <?php self::e($color_danger); ?>;
}

progress.wsf-progress.wsf-progress-danger[value]::-ms-fill {
  background-color: <?php self::e($color_danger); ?>;
}

.wsf-alert {
	background-color: <?php self::e($color_default_lightest); ?>;
<?php if ($form_border) { ?>
	border-left: 4px <?php self::e($form_border_style . ' ' . $form_border_color); ?>;
<?php } ?>
<?php if ($form_border_radius > 0) { ?>
	border-radius: <?php self::e($form_border_radius . $unit_of_measurement); ?>;
<?php } ?>
	font-family: <?php self::e($font_family); ?>;
	font-size: <?php self::e($form_font_size . $unit_of_measurement); ?>;
	font-weight: <?php self::e($font_weight); ?>;
	line-height: <?php self::e($line_height); ?>;
	padding: <?php self::e($spacing_small . $unit_of_measurement); ?>;
	margin-bottom: <?php self::e($grid_gutter . $unit_of_measurement); ?>;
}

.wsf-alert > :first-child {
	margin-top: 0;
}

.wsf-alert > :last-child {
	margin-bottom: 0;
}

.wsf-alert-success {
	background-color: <?php self::e(WS_Form_Common::hex_lighten_percentage($color_success, 85)); ?>;
<?php if ($form_border) { ?>
	border-color: <?php self::e(WS_Form_Common::hex_lighten_percentage($color_success, 40)); ?>;
<?php } ?>
	color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_success, 40)); ?>;
}

.wsf-alert-success a,
.wsf-alert-success a:hover,
.wsf-alert-success a:focus {
	color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_success, 60)); ?>;
}

.wsf-alert-information {
	background-color: <?php self::e(WS_Form_Common::hex_lighten_percentage(esc_html($color_information), 85)); ?>;
<?php if ($form_border) { ?>
	border-color: <?php self::e(WS_Form_Common::hex_lighten_percentage(esc_html($color_information), 40)); ?>;
<?php } ?>
	color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_information, 40)); ?>;
}

.wsf-alert-information a,
.wsf-alert-information a:hover,
.wsf-alert-information a:focus {
	color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_information, 60)); ?>;
}

.wsf-alert-warning {
	background-color: <?php self::e(WS_Form_Common::hex_lighten_percentage($color_warning, 85)); ?>;
<?php if ($form_border) { ?>
	border-color: <?php self::e(WS_Form_Common::hex_lighten_percentage($color_warning, 40)); ?>;
<?php } ?>
	color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_warning, 40)); ?>;
}

.wsf-alert-warning a,
.wsf-alert-warning a:hover,
.wsf-alert-warning a:focus {
	color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_warning, 60)); ?>;
}

.wsf-alert-danger {
	background-color: <?php self::e(WS_Form_Common::hex_lighten_percentage($color_danger, 85)); ?>;
<?php if ($form_border) { ?>
	border-color: <?php self::e(WS_Form_Common::hex_lighten_percentage($color_danger, 40)); ?>;
<?php } ?>
	color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_danger, 40)); ?>;
}

.wsf-alert-danger a,
.wsf-alert-danger a:hover,
.wsf-alert-danger a:focus {
	color: <?php self::e(WS_Form_Common::hex_darken_percentage($color_danger, 60)); ?>;
}

.wsf-text-primary {
	color: <?php self::e($color_primary); ?>;
}

.wsf-text-secondary {
	color: <?php self::e($color_secondary); ?>;
}

.wsf-text-success {
	color: <?php self::e($color_success); ?>;
}

.wsf-text-information {
	color: <?php self::e($color_information); ?>;
}

.wsf-text-warning {
	color: <?php self::e($color_warning); ?>;
}

.wsf-text-danger {
	color: <?php self::e($color_danger); ?>;
}
<?php
		}

		// Skin - RTL
		public function render_skin_rtl() {
			// Components
			$border = (WS_Form_Common::option_get('skin_border', false, false, $enable_cache) == 'true');
			$border_width = WS_Form_Common::option_get('skin_border_width', false, false, $enable_cache);
			$border_style = WS_Form_Common::option_get('skin_border_style', false, false, $enable_cache);
			$border_radius = WS_Form_Common::option_get('skin_border_radius', false, false, $enable_cache);

			// Transitions
			$transition = (WS_Form_Common::option_get('skin_transition', false, false, $enable_cache) == 'true');
			$transition_speed = WS_Form_Common::option_get('skin_transition_speed', false, false, $enable_cache);
			$transition_timing_function = WS_Form_Common::option_get('skin_transition_timing_function', false, false, $enable_cache);

			// Typography
			$font_size = WS_Form_Common::option_get('skin_font_size', false, false, $enable_cache);
			$line_height = WS_Form_Common::option_get('skin_line_height', false, false, $enable_cache);

			// Advanced
			$unit_of_measurement = 'px';
			$grid_gutter = WS_Form_Common::option_get('skin_grid_gutter', false, false, $enable_cache);

			// Spacing
			$spacing = 20;
			$spacing_extra_large = 80;
			$spacing_large = 40;
			$spacing_small = 10;
			$spacing_extra_small = 5;

			// Forms
			$form_border = $border; // true | false
			$form_border_color = $color_default_lighter;
			$form_border_style = $border_style;
			$form_border_width = $border_width;
			$form_border_radius = $border_radius;
			$form_font_size = $font_size;
			$form_spacing_horizontal = $spacing_small;
			$form_spacing_vertical = ($spacing_small * .85);
			$form_transition = $transition; // true | false
			$form_transition_timing_function = $transition_timing_function;
			$form_transition_speed = $transition_speed . 'ms ' . $form_transition_timing_function;
			$input_height = ((round($form_font_size * $line_height) + ($form_spacing_vertical * 2)) + ($form_border_width * 2));
			$checkbox_size = round($form_font_size * $line_height);
			$radio_size = round($form_font_size * $line_height);
			$color_size = $input_height;
			$range_size = $spacing;
?>
.wsf-inline {
	margin-left: <?php self::e($spacing_small . $unit_of_measurement); ?>;
	margin-right: 0;
}

select.wsf-field:not([multiple]):not([size]) {
	background-position: left <?php self::e($spacing_small . $unit_of_measurement); ?> center;
	padding-left: <?php self::e((($form_spacing_horizontal * 2) + $spacing_small) . $unit_of_measurement); ?>;
	padding-right: <?php self::e($form_spacing_horizontal . $unit_of_measurement); ?>;
}

input[type=checkbox].wsf-field + label.wsf-label {
	padding-left: 0;
	padding-right: <?php self::e(($checkbox_size + $spacing_extra_small) . $unit_of_measurement); ?>;
}

input[type=checkbox].wsf-field + label.wsf-label:before {
	left: initial;
	margin-right: <?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
	margin-right: 0;
	right: 0;
}

input[type=checkbox].wsf-field.wsf-switch + label.wsf-label {
	padding-left: 0;
	padding-right: <?php self::e((($checkbox_size * 2) + $spacing_extra_small) . $unit_of_measurement); ?>;
}

input[type=checkbox].wsf-field.wsf-switch + label.wsf-label:after {
	left: auto;
	right: 0;
<?php if ($form_transition) { ?>
	transition: background-color <?php self::e($form_transition_speed); ?>, border-color <?php self::e($form_transition_speed); ?>, right <?php self::e($form_transition_speed); ?>;
<?php } ?>
}

input[type=checkbox].wsf-field.wsf-switch:checked + label.wsf-label:after {
	left: auto;
	right: <?php self::e($checkbox_size . $unit_of_measurement); ?>;
}

input[type=radio].wsf-field + label.wsf-label {
	padding-left: 0;
	padding-right: <?php self::e(($radio_size + $spacing_extra_small) . $unit_of_measurement); ?>;
}

input[type=radio].wsf-field + label.wsf-label:before {
	left: initial;
	margin-right: <?php self::e($spacing_extra_small . $unit_of_measurement); ?>;
	margin-right: 0;
	right: 0;
}

input[type=file].wsf-field + label.wsf-label:after {
<?php if ($form_border_radius > 0) { ?>
	border-bottom-right-radius: 0;
	border-top-right-radius: 0;
<?php } ?>
	right: initial;
	left: -<?php self::e($form_border_width . $unit_of_measurement); ?>;
}

.wsf-alert {
<?php if ($form_border) { ?>
	border-left: none;
	border-right: 4px <?php self::e($form_border_style . ' ' . $form_border_color); ?>;
<?php } ?>
<?php
		}

		public function get_skin() {

			ob_start();
			self::render_skin();
			$css_return = ob_get_contents();
			ob_end_clean();

			// Minify
			$css_minify = WS_Form_Common::option_get('css_minify', false);

			return $css_minify ? self::minify($css_return) : $css_return;
		}

		public function get_skin_rtl() {

			ob_start();
			self::render_skin_rtl();
			$css_return = ob_get_contents();
			ob_end_clean();

			// Minify
			$css_minify = WS_Form_Common::option_get('css_minify', false);

			return $css_minify ? self::minify($css_return) : $css_return;
		}

		public function inline($css) {

			// Output CSS
			return sprintf('<style>%s</style>', $css);
		}

		public function minify($css) {

			// Basic minify
			$css = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $css);
			$css = preg_replace('/\s{2,}/', ' ', $css);
			$css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
			$css = preg_replace('/;}/', '}', $css);
			$css = str_replace(array("\r\n","\r","\n","\t",'  ','    ','    '),"",$css);

			return $css;
		}

		public function get_email() {

			$css_return = '	svg { max-width: 100%; }

	h1, h2, h3, h4 {

		font-family: sans-serif;
		font-weight: bold;
		margin: 0;
		margin-bottom: 10px;"
	}
	h1 {
		font-size: 24px !important;
	}
	h2 {
		font-size: 22px !important;
	}
	h3 {
		font-size: 20px !important;
	}
	h4 {
		font-size: 18px !important;
	}
	p,li,td,span,a {

		font-family: sans-serif;
		font-size: 14px;
		font-weight: normal;
		margin: 0;
		margin-bottom: 10px;"
 	}

	@media only screen and (max-width: 620px) {

		p,li,td,span,a {
			font-size: 16px;
	 	}
		.wrapper {
			padding: 10px !important;
		}
		.content {
			padding: 0 !important;
		}
		.container {
			padding: 0 !important;
			width: 100% !important;
		}
		.main {
			border-left-width: 0 !important;
			border-radius: 0 !important;
			border-right-width: 0 !important;
		}
	}
			';

			// Minify
			$css_minify = WS_Form_Common::option_get('css_minify', false);

			return $css_minify ? self::minify($css_return) : $css_return;
		}

		// Escape CSS values
		public function e($css_value) {

			$css_value = wp_strip_all_tags($css_value);
			$css_value = str_replace(';', '', $css_value);
			echo esc_html($css_value);
		}
	}
