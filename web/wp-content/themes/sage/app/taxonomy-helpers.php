<?php
namespace App;

/**
 * Returns the primary term for the chosen taxonomy set by Yoast SEO
 * or the first term selected.
 *
 * @link https://www.tannerrecord.com/how-to-get-yoasts-primary-category/
 * @param integer $post The post id.
 * @param string $taxonomy The taxonomy to query. Defaults to category.
 * @return array The term with keys of 'title', 'slug', and 'url'.
 */
function get_primary_term($post = 0, $taxonomy = 'category')
{
    if (!$post) {
        $post = get_the_ID();
    }

    $post_type = get_post_type($post);
    $taxonomies = get_object_taxonomies($post_type);

    if (!in_array($taxonomy, $taxonomies)) {
        return false;
    }

    $terms = get_the_terms($post, $taxonomy);
    $primary_term = new \stdClass();// create empty object

    if ($terms) {
        $term_display = '';
        $term_slug = '';
        $term_link = '';
        if (class_exists('\WPSEO_Primary_Term')) {
            $wpseo_primary_term = new \WPSEO_Primary_Term($taxonomy, $post);
            $wpseo_primary_term = $wpseo_primary_term->get_primary_term();
            $term = get_term($wpseo_primary_term);
            if (is_wp_error($term)) {
                $term_display = $terms[0]->name;
                $term_slug = $terms[0]->slug;
                $term_link = get_term_link($terms[0]->term_id);
            } else {
                $term_display = $term->name;
                $term_slug = $term->slug;
                $term_link = get_term_link($term->term_id);
            }
        } else {
            $term_display = $terms[0]->name;
            $term_slug = $terms[0]->slug;
            $term_link = get_term_link($terms[0]->term_id);
        }
        $primary_term->url = $term_link;
        $primary_term->slug = $term_slug;
        $primary_term->title = $term_display;
    }
    return $primary_term;
}

/**
 * Return taxonomy terms based on post type
 * Used by get_custom_post_labels()
 * @param int $post_id
 * @return object
 */
function get_post_type_taxonomy($post_id) {
    $post_type = get_post_type($post_id);

    switch ($post_type) {
        // // FIXME: Map custom taxonomies to custom post types
        // case 'event':
        //     $post_type = get_the_terms($post_id, 'event_type');
        //     break;
        // // FIXME: Map custom taxonomies to custom post types
        // case 'person':
        //     $post_type = get_the_terms($post_id, 'person_type');
        //     break;
        // // FIXME: Example of hiding the custom post type
        // case 'career':
        //     $post_type = null;
        //     break;
        // FIXME: Set the default taxonomy
        default:
            $post_type = get_the_tags($post_id);
    }

    // Use the first if multiple
    if (is_array($post_type)) {
        $post_type = $post_type[0];
    }

    return $post_type;
}

/**
 * Determine which post type superheader text to display in content listings,
 * and what URL to use if it should link to an archive page.
 * @param int $post_id
 * @return array
 */
function get_custom_post_type($post_id) {
    $post_type = get_post_type($post_id);
    $custom_post_type = [];

    switch ($post_type) {
        case 'page':
            $custom_post_type = false;
            break;
        case 'post':
            $custom_post_type = false;
            break;
        // FIXME: Example of a custom post type
        // case 'press_release':
        //     $custom_post_type['title'] = get_post_type_object($post_type)->labels->singular_name;
        //     $custom_post_type['url'] = get_post_type_archive_link($post_type);
        //     break;
        default:
            $custom_post_type['title'] = ucwords($post_type);
            $custom_post_type['url'] = get_post_type_archive_link($post_type) ?: null;
        // NOTE: Use this code if not supporting default post type labels
        // default:
        //     $custom_post_type = ['title' => '', 'url' => false];
    }

    return $custom_post_type;
}

/**
 * Determine what post type and taxonomy superheader text to display
 * on content listing components (e.g. Event | Webinar)
 * @param int $post_id
 * @return array
 */
function get_custom_post_labels($post_id) {
    $primary_label = get_custom_post_type($post_id);
    $secondary_label = [];
    $post_type = get_post_type($post_id);
    $post_type_tax = get_post_type_taxonomy($post_id);

    if ($post_type_tax) {
      $secondary_label['title'] = $post_type_tax->name;
      $secondary_label['url'] = get_term_link($post_type_tax->term_id, $post_type_tax->taxonomy);

      // FIXME: Example of post type without a secondary label
      // if ($post_type == 'career') {
      //     $secondary_label = false;
      // }
    }

    // FIXME: Example of post type without a primary label
    // if ($post_type == 'person') {
    //     $primary_label = false;
    // }

    return [
        'primary' => $primary_label,
        'secondary' => $secondary_label,
    ];
}
