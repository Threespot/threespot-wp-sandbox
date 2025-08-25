<?php

namespace App\View\Composers;
use App\Traits\AuthorHelpers;
use Roots\Acorn\View\Composer;

class Search extends Composer
{
    use AuthorHelpers;

    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'search',
        'archive',
        'partials.filters',
        'partials.listing-item',
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    public function with()
    {
        return [
            'search_query' => get_search_query(),
            'filter_post_type_objects' => $this->filter_post_type_objects(),
            'author_fullname' => $this->author_fullname(),
            'author_url' => $this->author_url(),
            'filters_form_action' => $this->get_filters_form_action(),
            'active_filters' => $this->active_filters(),
            'filter_reset_url' => $this->filter_reset_url(),
            'results_info' => $this->results_info(),
            'current_post_type' => $this->current_post_type(),
            'current_taxonomy' => $this->current_taxonomy(),
            'pagination_desktop' => $this->pagination_desktop(),
            'pagination_mobile' => $this->pagination_mobile(),
        ];
    }

    /**
     * Get array of custom post type objects for search filters
     * @return {array} $filter_post_type_objects
     *
     * @link https://developer.wordpress.org/reference/functions/get_post_types/#comment-929
     */
    public function filter_post_type_objects()
    {
        // Get array of custom post types excluding built-in public post types
        // (e.g. page, post, attachment, revision, nav_menu_item)
        $post_type_objects = get_post_types(['public' => true, '_builtin' => false], 'objects', 'and');

        // Remove if-so trigger post type
        unset($post_type_objects['ifso_triggers']);

        // Sort alphabetically
        uasort($post_type_objects, function($a, $b) {
            return strcasecmp($a->label, $b->label);
        });

        return $post_type_objects;
    }

    /**
     * Get author’s full name (see /Traits/AuthorHelpers.php)
     * @return {string}
     */
    public function author_fullname()
    {
        return $this->get_author_fullname();
    }

    /**
     * Get author URL see /Traits/AuthorHelpers.php)
     * @return {string}
     */
    public function author_url()
    {
        return $this->get_author_url();
    }

    /**
     * Get form action URL for filters component
     * @return string Cleaned URL for search form action
     */
    public function get_filters_form_action() {
        global $wp;
        $current_url = home_url($wp->request);

        // Remove pagination segments from URL (like /page/2/)
        $url_without_pagination = preg_replace('/\/page\/\d+\/?/', '/', $current_url);

        return esc_url($url_without_pagination);
    }

    /**
     * Get currently active filters from query vars
     * @return {array} $filters
     */
    public function active_filters()
    {
        // FIXME: Add filters
        $filter_params = [
          'post_type',
        ];

        $filters = [];

        foreach ($filter_params as &$param) {
          $query_var = get_query_var($param);
          if(!empty($query_var) && !($param == 'post_type' && $query_var == 'any')) {
            $filters[$param] = $query_var;
          }
        }

        // Ignore “post_type” filter if we‘re on a post type archive with no other filters applied
        if (count($filters) == 1 && array_key_exists('post_type', $filters)) {
            if (is_post_type_archive($filters['post_type'])) {
                return false;
            }
        }

        return $filters;
    }

    /**
     * Get current URL with search query, used to reset filters on search and archive pages
     * @return {string} $filter_reset_url
     */
    public function filter_reset_url() {
        global $wp;
        $url = home_url($wp->request, 'https');
        $args = [];

        // Add back the search query on search pages
        if (is_search()) {
            $args['s'] = get_search_query();
        }

        // Keep “post_type” on post type archives or else all content will be shown
        if (is_post_type_archive()) {
            $args['post_type'] = get_query_var('post_type');
        }

        if (!empty($args)) {
            $url = add_query_arg($args, $url);
        }

        return esc_url_raw($url) . '#results';
    }

    /**
     * Content listing page results info
     * @return {string} $results_info
     */
    public function results_info()
    {
        global $wp_query;
        $post_count = $wp_query->found_posts;
        $query = get_search_query();
        $for = " for “{$query}”";

        if ($post_count == 0) {
            return false;
        }
        elseif ($post_count == 1) {
            return 'Showing 1 result'.($query ? $for : '');
        }

        $paged = get_query_var('paged') ? get_query_var('paged') : 1;// defaults to first page
        $from = get_query_var('posts_per_page') * ($paged == 1 ? 0 : ($paged - 1));
        $to = $from + $wp_query->post_count;

        // Add 1 to $from when $paged > 1
        if ($paged > 1) {
            $from++;
        }

        // Don’t show range if only 1 page
        if ($to == $post_count) {
            $str = "Showing all $post_count results";
        }
        else {
            $str = 'Showing '.($from == 0 ? 1 : $from).'–'.$to.' of '.$post_count.' results';
        }

        if ($query) {
            $str .= $for;
        }

        return $str;
    }

    /**
     * Get current post type (e.g. to determine which filters to show on archive pages)
     * @return {string}
     */
    public function current_post_type() {
        // Ignore the search page
        if (is_search() && !is_archive()) {
            return false;
        }

        return get_query_var('post_type');
    }

    /**
     * Get current taxonomy (e.g. to determine which filters to show on archive pages)
     * @return {string}
     */
    public function current_taxonomy() {
        $queried_obj = get_queried_object();

        if (empty($queried_obj)) {
            return false;
        }

        // On post type archives a WP_Post_Type object is returned
        if (is_post_type_archive()) {
            return !empty($queried_obj->taxonomies) ? $queried_obj->taxonomies[0] : false;
        }

        // On taxonomy term archives a WP_Term object is returned
        if (is_tax()) {
            return $queried_obj->taxonomy;
        }

        return false;
    }

    /**
     * Default WP pagination markup for desktop view
     */
    public function pagination_desktop()
    {
        return paginate_links([
            'add_fragment' => '#results',
            'mid_size' => 1,
            'before_page_number' => '<span class="u-screenreader">' . __('Page', 'sage') . '</span>',
            'next_text' => '<span class="text">Next</span><svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 9 14" preserveAspectRatio="xMidYMid meet" width="9" height="14" focusable="false" aria-hidden="true"><path d="M2.118 13.676.644 12.324 5.524 7 .645 1.676 2.118.324 8.238 7z"/></svg>',
            'prev_text' => '<svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 9 14" preserveAspectRatio="xMidYMid meet" width="9" height="14" focusable="false" aria-hidden="true"><path d="m6.763.324 1.474 1.352L3.356 7l4.881 5.324-1.474 1.352L.643 7z"/></svg><span class="text">Previous</span>',
            'type' => 'array'
        ]);
    }

    /**
     * Default WP pagination markup for mobile view
     */
    public function pagination_mobile()
    {
        return paginate_links([
            'add_fragment' => '#results',
            'mid_size' => 0,
            'before_page_number' => '<span class="u-screenreader">' . __('Page', 'sage') . '</span>',
            'next_text' => '<span class="text">Next</span><svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 9 14" preserveAspectRatio="xMidYMid meet" width="9" height="14" focusable="false" aria-hidden="true"><path d="M2.118 13.676.644 12.324 5.524 7 .645 1.676 2.118.324 8.238 7z"/></svg>',
            'prev_text' => '<svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 9 14" preserveAspectRatio="xMidYMid meet" width="9" height="14" focusable="false" aria-hidden="true"><path d="m6.763.324 1.474 1.352L3.356 7l4.881 5.324-1.474 1.352L.643 7z"/></svg><span class="text">Previous</span>',
            'type' => 'array',
        ]);
    }

}
