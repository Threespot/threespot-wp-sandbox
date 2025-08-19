<?php


namespace MapSVG;


class PostsRepository
{

    function get_custom_posts($data)
    {
        $args = array(
            'post_type' => 'post', // You can replace 'post' with your custom post type
            'posts_per_page' => 10, // Number of posts to return
        );

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $posts = array();
            while ($query->have_posts()) {
                $query->the_post();
                $posts[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'date' => get_the_date(),
                    'author' => get_the_author(),
                    'link' => get_permalink(),
                );
            }
            wp_reset_postdata();
            return new \WP_REST_Response($posts, 200);
        } else {
            return new \WP_REST_Response('No posts found', 404);
        }
    }

    /**
     * Find posts by title.
     * Used in MapSVG Database forms, when users attaches posts to MapSVG DB objects
     */
    public function find($query)
    {

        $db = Database::get();

        $results = $db->get_results("SELECT id, post_title, post_content FROM " . $db->posts() . " WHERE post_type='" . esc_sql($query->filters['post_type']) . "' AND post_title LIKE '" . esc_sql($query->search) . "%' AND post_status='publish' LIMIT 20", ARRAY_A);
        foreach ($results as $key => $post) {
            $results[$key]['url'] = get_permalink($post['id']);
            if (function_exists('get_fields')) {
                $post["acf"] = get_fields($post["id"]);
            }
        }

        return $results;
    }
}
