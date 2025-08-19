<?php
namespace MapSVG;

class Post {
	/**
	 * Find posts by title.
	 * Used in MapSVG Database forms, when users attaches posts to MapSVG DB objects
	 */
	public static function find($query){

		$db = Database::get();

		$results = $db->get_results("SELECT id, post_title, post_content FROM ".$db->posts()." WHERE post_type='".esc_sql($query->filters['post_type'])."' AND post_title LIKE '".esc_sql($query->search)."%' AND post_status='publish' LIMIT 20", ARRAY_A);
		foreach($results as $r){
			$r->url = get_permalink($r->id);
			$r->ID = $r->id;
			if (function_exists('get_fields') ) {
				$r->acf = get_fields($r->id);
			}
		}

		return $results;
	}

}