<?php
namespace App;

// Optional: Update local JSON directories
// https://www.advancedcustomfields.com/resources/local-json/
// add_filter('acf/settings/save_json', function($path) {
//   $path = get_stylesheet_directory() . '/resources/acf-json';
//   return $path;
// });
// add_filter('acf/settings/load_json', function($paths) {
//   $paths[] = get_stylesheet_directory() . '/resources/acf-json';
//   return $paths;
// });

/**
 * Create “Theme Settings” ACF options page
 * https://www.advancedcustomfields.com/resources/options-page/
 */
add_action('init', function() {
    if (function_exists('acf_add_options_page')) {
        acf_add_options_page(array(
            'page_title' => 'Theme Settings',
            'menu_title' => 'Theme Settings',
            'menu_slug' => 'theme-settings',
            'capability' => 'edit_posts',
            'redirect' => false,
        ));
    }
});

/**
 * Register custom wysiwyg toolbars
 * https://www.advancedcustomfields.com/resources/customize-the-wysiwyg-toolbars/
 */
add_filter('acf/fields/wysiwyg/toolbars' , function($toolbars) {
    // Customize the “basic” toolbar buttons
    $toolbars['Basic'][1] = ['formatselect', 'bold', 'italic', 'bullist', 'numlist', 'link', 'unlink', 'pastetext', 'removeformat', 'charmap', 'undo', 'redo'];

    // Add a new toolbar called "Very Simple" with only 1 row of buttons
    $toolbars['Very Simple'] = [];
    $toolbars['Very Simple'][1] = ['bold', 'italic', 'link', 'unlink', 'pastetext', 'removeformat', 'charmap', 'undo', 'redo'];

    return $toolbars;
});

/**
 * Add toggle to hide ACF field label
 * https://support.advancedcustomfields.com/forums/topic/field-label-showhide-option#post-51372
 */
add_action('acf/render_field_settings', function($field) {
	acf_render_field_setting( $field, array(
		'label'			=> __('Hide Label?'),
		'instructions'	=> 'This will hide the label text in the admin (useful when text is redundant)',
		'name'			=> 'hide_label',
		'type'			=> 'true_false',
		'ui'			=> 1,
	), true);
});

add_filter('acf/prepare_field', function($field) {
	if (array_key_exists('hide_label', $field) && $field['hide_label'] == true) {
		echo '<style type="text/css">.acf-field-', substr($field['key'],6),' > .acf-label {display: none;}</style>';
	}
	return $field;
});
