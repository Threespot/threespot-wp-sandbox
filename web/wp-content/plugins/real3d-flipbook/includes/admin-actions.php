<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$current_action = $current_id = $page_id = '';
// handle action from url
if (isset($_GET['action']) ) {
	$current_action = sanitize_text_field($_GET['action']);
}

if (isset($_GET['bookId']) ) {
	$current_id = sanitize_text_field($_GET['bookId']);
}

if (isset($_GET['pageId']) ) {
	$page_id = sanitize_text_field($_GET['pageId']);
}

$url=admin_url( "admin.php?page=real3d_flipbook_admin" );

$reak3dflipbooks_converted = get_option("reak3dflipbooks_converted");

if(!$reak3dflipbooks_converted){

	$flipbooks = get_option("flipbooks");
	if(!$flipbooks){
		$flipbooks = array();
	}

	add_option('reak3dflipbooks_converted', true);
	$real3dflipbooks_ids = array();

	foreach ($flipbooks as $b) {
		$id = $b['id'];
		//trace($id);
		delete_option('real3dflipbook_'.(string)$id);
		add_option('real3dflipbook_'.(string)$id, $b);
		array_push($real3dflipbooks_ids,(string)$id);
	}

}else{

	$real3dflipbooks_ids = get_option('real3dflipbooks_ids');
	if(!$real3dflipbooks_ids){
		$real3dflipbooks_ids = array();
	}
	$flipbooks = array();
	foreach ($real3dflipbooks_ids as $id) {
		// trace($id);
		$book = get_option('real3dflipbook_'.$id);
		if($book){

			/*if(!isset($book['post_id'])){
	          $postId = wp_insert_post(array(
	            'post_title'=> $book["name"], 
	            'post_type'=>'r3d', 
	            // 'post_content'=>'demo text',
	            'post_status'   => 'publish',
	            'meta_input' => array(
	            	'id' => $id
	            )
	          ));

	          $book["post_id"] = $postId;
	          update_option('real3dflipbook_'.$id, $book);

	        }
	        */

			$flipbooks[$id] = $book;
			// array_push($flipbooks,$book);
		}else{
			//remove id from array
			$real3dflipbooks_ids = array_diff($real3dflipbooks_ids, array($id));
		}
	}
}

update_option('real3dflipbooks_ids', $real3dflipbooks_ids);

switch( $current_action ) {

	case 'edit':

		include("edit-flipbook.php");
		break;
		
	case 'add_new':

		$new_id = 0;
		$highest_id = 0;

		foreach ($real3dflipbooks_ids as $id) {
			if((int)$id > $highest_id) {
				$highest_id = (int)$id;
			}
		}

		$current_id = $highest_id + 1;
		//create new book 
		$book = array(	
			"id" => $current_id, 
			"name" => "flipbook " . $current_id,
			"pages" => array(),
			"date" => current_time( 'mysql' ),
			"status" => "draft"
		);
		$flipbooks[$current_id] = $book;

		include("edit-flipbook.php");
		break;
		
	case 'generate_json':
		include("flipbooks.php");
		break;
	
	
	default:

		include("flipbooks.php");
		break;
		
}


