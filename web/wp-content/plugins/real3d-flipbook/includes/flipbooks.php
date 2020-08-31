<?php 
    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
?>
<div class="wrap">

	<h2>Flipbooks
		<a href='<?php echo $url .'&action=add_new'; ?>' class='add-new-h2'>Add New</a>
	</h2>
	
	<div class="tablenav top">
		<div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
			<select name="action" id="bulk-action-selector-top">
				<option value="-1" selected="selected">Bulk Actions</option>
				<option value="trash">Trash</option>
			</select>

			<input type="submit" id="doaction" class="button action bulkactions-apply" value="Apply">
		</div>


		<!-- <div class="alignleft actions">

		<label class="screen-reader-text" for="cat">Filter by category</label><select name="cat" id="cat" class="postform">
			<option value="0">All categories</option>
			<option class="level-0" value="1">Uncategorized</option>
		</select> 
		<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">		
		</div>-->

		<div class="tablenav-pages">
	        <span class="displaying-num"></span>
			<span class="pagination-links"><a class="first-page" title="Go to the first page" href="#">«</a>
			<a class="prev-page" title="Go to the previous page" href="#">‹</a>
			<span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Select Page</label><input class="current-page" id="current-page-selector" title="Current page" type="text" name="paged" value="1" size="1"> of <span class="total-pages"></span></span>
			<a class="next-page" title="Go to the next page" href="#">›</a>
			<a class="last-page" title="Go to the last page" href="#">»</a></span>
			<!-- <span class="items-20" title="View 20">View 20</span>
			<span class="items-100" title="View 100">View 100</span>
			<span class="items-all" title="View all">View all</span> -->
        </div>

	</div>	
	
	
	
	<table class='flipbooks-table wp-list-table widefat fixed striped pages'>
		<thead>
			<tr>
				<th scope="col" id="cb" class="manage-column column-cb check-column" style="">
					<label class="screen-reader-text" for="cb-select-all-1">Select All</label>
					<input id="cb-select-all-1" type="checkbox">
				</th>

				<th tabindex="-1" scope="col" style="width: 80px;" class="manage-column column-title sorted desc"></th>
			
				<th tabindex="-1" scope="col" id="name" class="manage-column column-title sorted desc"><a href="#"><span>Name</span><span class="sorting-indicator"></span></a></th>
				
				<th style="width:200px">Shortcode</th>
				
				<th tabindex="-1" style="width:100px" scope="col" id="date" class="manage-column column-title sorted desc"><a href="#"><span>Date</span><span class="sorting-indicator"></span></a></th>
			</tr>
			</thead>
			<tbody id="flipbooks-table">
		</tbody>		 
	</table>
	
	<div class="tablenav bottom">
		<div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label>
			<select name="action" id="bulk-action-selector-bottom">
				<option value="-1" selected="selected">Bulk Actions</option>
				<option value="trash">Trash</option>
			</select>

			<input type="submit" id="doaction" class="button action bulkactions-apply" value="Apply">
		</div>



		<!--<div class="alignleft actions">

		<label class="screen-reader-text" for="cat">Filter by category</label><select name="cat" id="cat" class="postform">
			<option value="0">All categories</option>
			<option class="level-0" value="1">Uncategorized</option>
		</select>
		<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">		</div>-->

		<div class="tablenav-pages">
			

			<span class="displaying-num"></span>
			<span class="pagination-links"><a class="first-page" title="Go to the first page" href="#">«</a>
			<a class="prev-page" title="Go to the previous page" href="#">‹</a>
			<span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Select Page</label><input class="current-page" id="current-page-selector" title="Current page" type="text" name="paged" value="1" size="1"> of <span class="total-pages"></span></span>
			<a class="next-page" title="Go to the next page" href="#">›</a>
			<a class="last-page" title="Go to the last page" href="#">»</a></span>
			<!-- <span class="items-20" title="View 20">View 20</span>
			<span class="items-100" title="View 100">View 100</span>
			<span class="items-all" title="View all">View all</span> -->
		</div>
		</div>	
			
	
	<br/>
	<br/>
	<br/>
	<h3>Export flipbooks</h3>
	<p>
		<a class='button-secondary' href='<?php echo admin_url( "admin.php?page=real3d_flipbook_admin&action=download_json" ); ?>'>Download JSON</a>
		<button class="button-secondary copy-json">Copy JSON</button>
	</p>

	<br/> 
	<br/> 
	<br/> 

	<h3>Import flipbooks</h3>
	
	<?php 
		if (isset($_GET['action']) && $_GET['action'] == "generate_json") {
			echo '<textarea id="flipbook-admin-json" rows="20" cols="100" >' . json_encode($flipbooks) . '</textarea>';
		}


	?>
	
	<p>Import flipbooks from JSON( overwrite existing flipbooks)</p>
			
	<textarea name="flipbooks" id="flipbook-admin-json" rows="20" cols="100" placeholder="Paste JSON here"></textarea>
	<p class="submit"><input type="submit" name="submit" id="submit" class="button save-button button-secondary import" value="Import"></p>
	

	<br/>
	<br/>
	<br/>
	<span class="submitbox"><a class="submitdelete delete-all-flipbooks" href='#'>Delete all flipbooks</a></span>
	
	<input type="text" id="copy-text-hidden" value="" style="opacity: 0; pointer-events: none; ">
</div>
<?php

wp_enqueue_script("real3d-flipbook-flipbooks");

$flipbooks_formatted = array();
foreach ($flipbooks as $b) {
	
	//convert dataurl thumbnails
	if (isset($b["lightboxThumbnailUrl"]) && strpos($b["lightboxThumbnailUrl"], 'data:image') !== false) {

	    $id = intval($b['id']);
		$bookName = $b['name'];
		$upload_dir = wp_upload_dir();
		$booksFolder = $upload_dir['basedir'] . '/real3d-flipbook/';
		$bookFolder = $booksFolder . 'flipbook_' . $id . '/';
		$file = $bookFolder."thumb.jpg";
		$data = $b["lightboxThumbnailUrl"];
		$uri = substr($data,strpos($data, ",") + 1);

		if (!file_exists($booksFolder)) {
			mkdir($booksFolder, 0777, true);
		}

		if (!file_exists($bookFolder)) {
			mkdir($bookFolder, 0777, true);
		}

		if(!file_put_contents($file, base64_decode($uri))){
			echo " failed writing image ".$file;
		}else{
		  $b['lightboxThumbnailUrl'] = $upload_dir['baseurl'] . '/real3d-flipbook/flipbook_' .$id . '/thumb.jpg';
		  update_option('real3dflipbook_'.(string)$id, $b);

			// echo(($upload_dir['baseurl'] . '/real3d-flipbook/flipbook_' .$id . '/thumb.jpg'));
		}

	}

	$book = array(	"id" => $b['id'], 
					"name" => $b['name'],
					"date" => $b['date'],
					"thumb" => $b['lightboxThumbnailUrl']
				);


	array_push($flipbooks_formatted,$book);
}

$r3d_nonce = wp_create_nonce( "r3d_nonce");

wp_localize_script( 'real3d-flipbook-flipbooks', 'flipbooks', json_encode($flipbooks_formatted) );
wp_localize_script( 'real3d-flipbook-flipbooks', 'r3d_nonce', $r3d_nonce );

if (isset($_GET['action']) && $_GET['action'] == "download_json") {

	$flipbooks['global'] = get_option( "real3dflipbook_global" );
	$flipbooks['global']['id'] = 'global';

	wp_localize_script( 'real3d-flipbook-flipbooks', 'flipbooks_json', json_encode($flipbooks) );

}