<?php
namespace App;
use Illuminate\Support\Facades\Vite;

/**
 * Enqueue custom admin stylesheet (admin.scss)
 */
add_action('admin_enqueue_scripts', function($hook) {
    // Only enqueue custom styles on certain admin pages
    $include_hooks = [
        'edit.php',
        'edit-tags.php',
        'index.php',// Dashboard
        'post-new.php',
        'post.php',
        'toplevel_page_theme-settings',// Theme Settings
    ];

    if (in_array($hook, $include_hooks)) {
        echo Vite::withEntryPoints(['resources/styles/admin.scss'])->toHtml();
    }
});

/**
 * Enqueue custom Gutenberg JS
 * https://developer.wordpress.org/block-editor/how-to-guides/enqueueing-assets-in-the-editor/
 * https://make.wordpress.org/core/2023/07/18/miscellaneous-editor-changes-in-wordpress-6-3/#post-editor-iframed
 * https://developer.wordpress.org/block-editor/tutorials/javascript/extending-the-block-editor/
 */
add_action('enqueue_block_editor_assets', function() {
    // NOTE: The “enqueue_block_editor_assets” hook also fires on the front end so we
    //       need to check to make sure we’re in the admin before enqueueing.
    if (is_admin()) {
        // Load custom Gutenberg editor script
        echo Vite::withEntryPoints('resources/scripts/gutenberg.js')->toHtml();

        // NOTE: If we load the CSS here it won’t work when the Vite dev server is running.
        //       See add_filter('block_editor_settings_all') below for better approach.
        //
        // $css_url = Vite::asset('resources/styles/gutenberg.scss');
        // if (file_exists($css_url)) {
        //     $relative = str_replace(get_theme_file_path(), '', $css_url);
        //     $css_url = get_theme_file_uri($relative);
        //     wp_enqueue_style('gutenberg-css', $css_url);
        // }
    }
});

/**
 * Load custom Gutenberg styles
 */
add_filter('block_editor_settings_all', function ($settings) {
    $css_path = Vite::asset('resources/styles/gutenberg.scss');

    $settings['styles'][] = [
        'css' => "@import url('{$css_path}')",
    ];

    return $settings;
});

/**
 * Remove dashboard widgets
 *  https://codex.wordpress.org/Function_Reference/remove_meta_box
 */
add_action('wp_dashboard_setup', function() {
    // Note: Use 'dashboard-network' as the second parameter to remove widgets from a network dashboard.

    remove_action('welcome_panel', 'wp_welcome_panel');// “Welcome” panel
    remove_meta_box('dashboard_primary', 'dashboard', 'side');// WordPress blog
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');// Quick draft
    remove_meta_box('dashboard_secondary', 'dashboard', 'side');// WordPress news

    // remove_meta_box('dashboard_activity', 'dashboard', 'normal');// Activity
    // remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');// Incoming links (if enabled)
    // remove_meta_box('dashboard_php_nag', 'dashboard', 'normal');// Remove “PHP Version” (if applicable)
    // remove_meta_box('dashboard_plugins', 'dashboard', 'normal');// Plugins
    // remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');// Recent comments
    // remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');// Recent drafts
    // remove_meta_box('dashboard_right_now', 'dashboard', 'normal');// Right now (old versions of WP)

    // Yoast widget
    // https://yoast.com/help/how-to-hide-the-seo-dashboard-widget/
    remove_meta_box('wpseo-dashboard-overview', 'dashboard', 'normal');
});

/**
 * Remove comments from admin menu
 */
add_action('admin_menu', function() {
    remove_menu_page('edit-comments.php');
});

/**
 * Admin overrides
 */
add_action('admin_init', function() {
    // Return is not in the admin
    if (!is_admin()) {
        return;
    }

    // Disable default functionality per post type (e.g. comments, trackbacks)
    // https://developer.wordpress.org/reference/functions/remove_post_type_support/
    $post_types = get_custom_post_types();

    foreach ($post_types as $post_type) {
        // Disable comments on all post types
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }

    // Redirect any user trying to access comments page
    // https://gist.github.com/mattclements/eab5ef656b2f946c4bfb
    global $pagenow;
    if ($pagenow === 'edit-comments.php') {
        wp_redirect(admin_url());
        exit;
    }

    // Optional: Remove the Posts section from “Add menu items”
    // global $wp_post_types;
    // if (isset($wp_post_types['post'])) {
    //     $wp_post_types['post']->show_in_nav_menus = false;
    // }
}, 11);

/**
 * Customize the WP admin bar
 * https://developer.wordpress.org/reference/hooks/wp_before_admin_bar_render/
 * https://jasonyingling.me/removing-items-from-the-wordpress-admin-bar/
 */
add_action('wp_before_admin_bar_render', function() {
    global $wp_admin_bar;

    // Check if user is an admin
    $current_user = wp_get_current_user();
    $is_admin_user = in_array('administrator', $current_user->roles);

    // Optional: Check if user has a threespot.com email
    // $user_email = $current_user->data->user_email;
    // $is_threespot = str_contains($user_email, '@threespot.com');

    // Remove menu items
    $wp_admin_bar->remove_node('comments');
    $wp_admin_bar->remove_node('customize');
    $wp_admin_bar->remove_node('fwp-cache');// FacetWP
    $wp_admin_bar->remove_node('gform-forms');// Gravity Forms
    $wp_admin_bar->remove_node('searchwp');// SearchWP
    $wp_admin_bar->remove_node('wp-logo');// WordPress logo
    $wp_admin_bar->remove_node('wpseo-menu');// Yoast
    // $wp_admin_bar->remove_node('my-account')
    // $wp_admin_bar->remove_node('new-content');// New content menu
    // $wp_admin_bar->remove_node('new-media');// Hide new media link
    // $wp_admin_bar->remove_node('new-post');// Hide new post link

    // $wp_admin_bar->remove_node('site-name');

    // Optional: Customize site name
    // $wp_admin_bar->add_node([
    //     'id' => 'site-name',
    //     'title' => 'FIXME'
    // ]);

    // Remove “Howdy” text from admin bar
    // https://wpintensity.com/change-howdy-text-wordpress/
    $my_account = $wp_admin_bar->get_node('my-account');

    $wp_admin_bar->add_node([
        'id' => 'my-account',
        // Remove “Howdy, ” text
        'title' => str_replace('Howdy,', '', $my_account->title)
        // Alternate way to remove everything before the username
        // 'title' => substr($my_account->title, strpos($my_account->title, '<span class="display-name">')),
        // Remove “Howdy, ” and username leaving just the avatar thumbnail
        // - strpos() logic copied from https://wordpress.org/plugins/no-nonsense/
        // - https://plugins.trac.wordpress.org/browser/no-nonsense/trunk/functions.php
        // 'title' => substr($my_account->title, strpos($my_account->title, '<img')),
    ]);

    // Remove items in the admin
    if (is_admin()) {
        // Hide items for non-admins
        if (!$is_admin_user) {
            // Query Monitor
            $wp_admin_bar->remove_node('query-monitor');
        }
    }

    // Remove items on the front end
    if (!is_admin()) {
        $wp_admin_bar->remove_node('search');
        $wp_admin_bar->remove_node('updates');
        $wp_admin_bar->remove_node('duplicate-post');// Duplicate Post
    }

    // Remove these items only in production
    if ($_ENV['PANTHEON_ENVIRONMENT'] == 'production') {
        $wp_admin_bar->remove_node('pantheon-hud');// Pantheon HUD
    }
}, 99);

/**
 * Remove admin footer text (e.g. “Thank you for creating with WordPress.”)
 * https://developer.wordpress.org/reference/hooks/admin_footer_text/
 */
add_filter('admin_footer_text', function() {
    return null;
});

/**
 * Remove items from customizer
 */
add_action('customize_register', function ($wp_customize) {
    // $wp_customize->remove_panel('nav_menus');
    $wp_customize->remove_section('colors');
    // $wp_customize->remove_section('background_image');
    $wp_customize->remove_section('custom_css');
    // $wp_customize->remove_section('header_image');
    $wp_customize->remove_section('static_front_page');
    // $wp_customize->remove_section('title_tagline');
}, 50);

/**
 * Add “author” filter to admin listing pages
 * @link https://rudrastyh.com/wordpress/filter-posts-by-author.html
 *
 * Note: There’s also a plugin that does this but it didn’t
 *       seem necessary given how simple the code is.
 *       https://wordpress.org/plugins/author-filters/
 */
add_action('restrict_manage_posts', function() {
	$params = [
		'name' => 'author',
		'show_option_all' => 'All authors',
    ];

	if (isset($_GET['user'])) {
		$params['selected'] = $_GET['user'];
	}

	wp_dropdown_users( $params );
});

/**
 * Lower priority of Yoast metabox
 * https://developer.yoast.com/customization/yoast-seo/filters/change-metabox-prio-filter/
 */
add_filter('wpseo_metabox_prio', function() {
    return 'low';
});

/**
 * Collapse Yoast metabox by default
 * https://wordpress.stackexchange.com/questions/4381/make-custom-metaboxes-collapse-by-default
 */
add_filter('admin_init', function() {
    $post_types = get_custom_post_types();

    // Collapse the Yoast metabox for each post type
    foreach ($post_types as $post_type) {
        add_filter("get_user_option_closedpostboxes_$post_type", function($closed) {
            $closed = $closed ? $closed : [];
            $closed[] = 'ame-cpe-content-permissions';// Admin Menu Editor content permissions
            $closed[] = 'wpseo_meta';// Yoast
            return $closed;
        }, 10, 1);
    }
});

/**
 * Remove “SEO Manager” and “SEO Editor” user roles created by Yoast
 */
if (get_role('wpseo_manager')) {
  remove_role('wpseo_manager');
}
if (get_role('wpseo_editor')) {
  remove_role('wpseo_editor');
}


/**
 * Customize TinyMCE editor
 * @link https://developer.wordpress.org/reference/hooks/tiny_mce_before_init/
 * @link https://www.tiny.cloud/docs/configure/content-appearance/#body_class
 */
add_filter('tiny_mce_before_init', function($mceInit) {
    // Add “u-richtext” to default TinyMCE editor
	$mceInit['body_class'] = 'u-richtext';

    // Optional: Enqueue admin.css inside of wysiwyg iframe
    // $css_url = Vite::asset('resources/styles/admin.scss');
    //
    // if ($css_url) {
    //     if (isset($mceInit['content_css']) && $mceInit['content_css']) {
    //         $mceInit['content_css'] .= ',' . $css_url;
    //     } else {
    //         $mceInit['content_css'] = $css_url;
    //     }
    // }

	return $mceInit;
});

/**
 * Optional: Install TinyMCE word count plugin
 * @link https://www.tiny.cloud/docs/tinymce/6/wordcount/
 */
// add_filter('mce_external_plugins', function($plugins) {
//     // Note: WP uses TinyMCE version 4
//     $plugins['wordcount'] = 'https://cdn.tiny.cloud/1/no-api-key/tinymce/4/plugins/wordcount/plugin.min.js';
//     return $plugins;
// });

/**
 * Remove unhelpful warnings from Site Health that due to hosting provider
 * https://docs.pantheon.io/wordpress-known-issues#automatic-updates
 * https://developer.wordpress.org/reference/hooks/site_status_tests/
 */
add_filter('site_status_tests', function($tests) {
    // Remove “Background updates are not working as expected” warning
	unset($tests['async']['background_updates']);

    // Remove “Disk space available to safely perform updates” warning
    unset($tests['direct']['available_updates_disk_space']);

    // Remove “You should remove inactive themes” warning
    unset($tests['direct']['theme_version']);

    // Remove “The upgrade directory cannot be created” warning
    unset($tests['direct']['update_temp_backup_writable']);

	return $tests;
});

/**
 * Remove WP version from markup
 * https://www.wpbeginner.com/wp-tutorials/the-right-way-to-remove-wordpress-version-number/
 */
remove_action('wp_head', 'wp_generator');

/**
 * Disable XML-RPC for enhanced security
 * https://kinsta.com/blog/xmlrpc-php/
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Set default Screen Options for new users
 */
add_action('user_register', function($user_id, $items_per_page = 50) {
    $post_types = get_custom_post_types();

    // Create array of columns to hide by default
    $hide_column_names = [
        'wpseo-focuskw',
        'wpseo-linked',
        'wpseo-links',
        'wpseo-metadesc',
        'wpseo-score',
        'wpseo-score-readability',
        'wpseo-title',
    ];

    // Update default Screen Options on each post type
    foreach ($post_types as $post_type) {
        // Update the items per page value
        $meta_key = "edit_{$post_type}_per_page";
        $current_value = get_user_meta($user_id, $meta_key, true);

        // Just in case, make sure it hasn’t been set before updating
        if (empty($current_value)) {
            update_user_meta($user_id, $meta_key, $items_per_page);
        }

        // Get hidden columns array
        $hidden_columns_key = "manageedit-{$post_type}columnshidden";
        $hidden_columns = get_user_meta($user_id, $hidden_columns_key, true);

        // Create placeholder array if it doesn’t exist
        if (!is_array($hidden_columns)) {
            $hidden_columns = [];
        }

        // Add items to hidden coluns array
        foreach ($hide_column_names as $column_name) {
            if (!in_array($column_name, $hidden_columns)) {
                $hidden_columns[] = $column_name;
            }
        }

        // Update screen options
        update_user_meta($user_id, $hidden_columns_key, $hidden_columns);
    }
});

/**
 * Customize robots.txt after Yoast runs
 * https://wordpress.org/support/topic/disable-robots-txt-changing-by-yoast-seo/#post-16177384
 *
 * Note: Pantheon only allows custom robots.txt on the live site
 * https://docs.pantheon.io/bots-and-indexing
 **/
add_filter('robots_txt', function($output, $public) {
    // Check if site is private
    if ($public === '0') {
		return "User-agent: *\nDisallow: /\nDisallow: /*\nDisallow: /*?\n";
    }

    $rules = '';

    // Import robots.txt file
    $robots_file_path = get_template_directory() . '/robots.txt';
    if (file_exists($robots_file_path)) {
        $rules .= file_get_contents($robots_file_path);
    }
    else {
        // Add basic WP rules
        $rules = "User-agent: *\n";
        $rules .= "Disallow: /wp-admin/\n";// Block admin area access
        $rules .= "Allow: /wp-admin/admin-ajax.php\n";// Allow AJAX functionality
        $rules .= "Disallow: /wp-includes/\n";// Protect core files
        $rules .= "Disallow: /wp-content/plugins/\n";// Hide plugin structure
        $rules .= "Disallow: /wp-content/themes/\n";// Prevent theme enumeration
        $rules .= "Disallow: /*?*\n";// Block parameter-based URLs
    }

    // Extract sitemap URL from Yoast output
    preg_match('/Sitemap: (.+)/', $output, $matches);
    $sitemap_url = !empty($matches[1]) ? trim($matches[1]) : '';
    if (!empty($sitemap_url)) {
        $rules .= "\nSitemap: " . $sitemap_url . "\n";
    }

    return $rules;
}, 100000, 2); // Priority must be higher than Yoast’s 99999

/**
 * Optional: Hide “posts” from nav menu items
 */
// add_filter('register_post_type_args', function($args, $name) {
//     if ($name == 'post') {
//         $args['show_in_nav_menus'] = false;
//     }
//
//     return $args;
// }, 10, 2);

/**
 * Hide default “category” taxonomy from nav menu items
 */
add_filter('register_taxonomy_args', function($args, $name) {
    if ($name == 'category') {
        $args['show_in_nav_menus'] = false;
    }

    return $args;
}, 10, 2);

/**
 * Optional: Extend Yoast’s primary term functionality to custom taxonomies
 * https://developer.yoast.com/customization/yoast-seo/filters/primary-term-taxonomies-filter/
 */
// add_filter('wpseo_primary_term_taxonomies', function($taxonomies, $post_type) {
//     $custom_taxonomies = ['example'];
//
//     foreach ($custom_taxonomies as $name) {
//         // Check if the taxonomy is registered for this post type
//         if (is_object_in_taxonomy($post_type, $name)) {
//             // Add the taxonomy to the list of primary term taxonomies
//             $taxonomies[$name] = get_taxonomy($name);
//         }
//     }
//
//     return $taxonomies;
// }, 10, 2);
