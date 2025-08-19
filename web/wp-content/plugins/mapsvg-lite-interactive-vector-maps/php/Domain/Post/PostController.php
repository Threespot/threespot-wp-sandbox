<?php
namespace MapSVG;

class PostController extends Controller {

	/**
	 * Returns the list of WP posts.
	 * Request may contain filter by post type and search string.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public static function index($request){
		$query = new Query($request->get_params());
		$postsRepo = new PostsRepository();
		$posts = $postsRepo->find($query);
		return self::render(['posts'=>$posts]);
	}
}