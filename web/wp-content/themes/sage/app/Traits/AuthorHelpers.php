<?php

namespace App\Traits;

/**
 * Helper methods for WP Default User/Author post type
 */

trait AuthorHelpers
{
    /**
     * Retrieves the author’s full name
     * Defaults to the current post’s author but you can optionally pass a post object
     *
     * @return String
     */
    function get_author_fullname($post_obj = null)
    {
        // Default to the current post if none specified
        global $post;

        // Note: $post can be empty, e.g. search pages with no results
        if (empty($post)) {
            return false;
        }

        $user_id = isset($post_obj) ? $post_obj->post_author : $post->post_author;

        // Note: First and last name return an empty string if undefined
        $firstname = get_the_author_meta('first_name', $user_id);
        $lastname = get_the_author_meta('last_name', $user_id);

        // If both are undefined, return false (instead of empty string)
        if (empty($firstname) && empty($lastname)) {
            return false;
        }

        // Trim to remove space in case one of the names is empty
        return trim($firstname . ' ' . $lastname);
    }

    /**
     * Retrieves the author URL
     * Defaults to the current post’s author but you can optionally pass a post object
     *
     * @return String
     */
    function get_author_url($post_obj = null)
    {
        // Default to the current post if none specified
        global $post;
        $user_id = isset($post_obj) ? $post_obj->post_author : $post->post_author;
        return get_author_posts_url($user_id);
    }
}
