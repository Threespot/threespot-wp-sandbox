<?php
namespace App;

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function() {
    /**
     * FIXME: Register the navigation menus
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sage'),
        'secondary_navigation' => __('Secondary Navigation', 'sage'),
        'footer_navigation' => __('Footer Navigation', 'sage')
    ]);

    /**
     * Disable the default block patterns.
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     * @link https://make.wordpress.org/core/2020/07/16/block-patterns-in-wordpress-5-5/
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Enable responsive embed support
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Enable page excerpts
     * @link https://developer.wordpress.org/reference/functions/add_post_type_support/
     */
    add_post_type_support('page', 'excerpt');

    /**
     * Enable author field for CPTs
     * @link https://developer.wordpress.org/reference/functions/add_post_type_support/
     */
    // add_post_type_support('resource', 'author');

    /**
     * Enable “wide” and “full” alignment in supporting blocks
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#wide-alignment
     */
    add_theme_support('align-wide');

}, 20);

/**
 * Remove “Category:”, “Tag:”, and “Author:” from the_archive_title
 * https://wordpress.stackexchange.com/a/179590/185703
 */
add_filter('get_the_archive_title', function($title) {
  if (is_tax() ) { //for custom post types
    $title = sprintf( __( '%1$s' ), single_term_title('', false));
  } elseif (is_post_type_archive()) {
    $title = post_type_archive_title('', false);
  } elseif (is_author() ) {
    $title = 'Posts by ' . get_the_author();
  } elseif (is_category()) {
    $title = single_cat_title('', false);
  } elseif (is_tag() ) {
    $title = single_tag_title('Tag: ', false);
  }
  return $title;
});

/**
 * Set custom excerpt word count
 * https://developer.wordpress.org/reference/hooks/excerpt_length/
 * Note: We also have a trim_excerpt() helper function to set a custom length
 */
add_filter('excerpt_length', function() {
  return 25;
});

/**
 * Remove the default “read more” link in the excerpt, just use “…”
 * https://developer.wordpress.org/reference/hooks/excerpt_more/
 */
add_filter('excerpt_more', function ($more) {
    // return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
    return '…';
});

/**
 * Remove password-protected page title prefix (defaults to “Protected:”)
 * https://developer.wordpress.org/reference/hooks/private_title_format/
 */
add_filter('protected_title_format', function($prepend) {
    return '%s';
});

/**
 * Remove default “Uncategorized” category on save when another is present
 * https://wordpress.stackexchange.com/a/254691/185703
 */
add_action('save_post', function($post_id) {
  // Note: The “default_category” ID is returned as a string so cast as an integer
  $default_category = (int) get_option('default_category');
  $has_default_category = has_category($default_category, $post_id);
  $post_categories = get_the_category($post_id);

  if ($has_default_category && count($post_categories) > 1) {
    wp_remove_object_terms($post_id, $default_category, 'category');
  }
});

/**
 * Optional: Register the theme sidebars.
 *
 * @return void
 */
// add_action('widgets_init', function () {
//     $config = [
//         'before_widget' => '<section class="widget %1$s %2$s">',
//         'after_widget' => '</section>',
//         'before_title' => '<h3>',
//         'after_title' => '</h3>'
//     ];
//
//     register_sidebar([
//         'name' => __('Primary', 'sage'),
//         'id' => 'sidebar-primary'
//     ] + $config);
//
//     register_sidebar([
//         'name' => __('Footer', 'sage'),
//         'id' => 'sidebar-footer'
//     ] + $config);
// });
