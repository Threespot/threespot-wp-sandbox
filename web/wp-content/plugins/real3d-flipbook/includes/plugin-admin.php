<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$globals_settings = get_option("real3dflipbook_global");

if(!$globals_settings)
	r3dfb_setDefaults();	

function r3dfb_setDefaults(){

	$defaults = r3dfb_getDefaults();

	delete_option("real3dflipbook_global");
	add_option("real3dflipbook_global", $defaults);

}

function r3dfb_getDefaults(){
	return array(
      'mode' => 'normal', 
      'viewMode' => 'webgl', 
      'pageTextureSize' => '2048', 
      'pageTextureSizeSmall' => '1500', 
      'pageTextureSizeMobile' => '', 
      'pageTextureSizeMobileSmall' => '1024', 
      'zoomMin' => '0.9', 
       'zoomStep' => '2',
       'zoomSize' => '',
       'zoomReset' => 'false',
       'doubleClickZoom' => 'true',
       'singlePageMode' => 'false',
       'pageFlipDuration' => '1',
       'sound' => 'true',
       'startPage' => '1',
       'deeplinking' => Array(
            'enabled' => 'false',
            'prefix' => ''
        ),
       'responsiveView' => 'true',
       'responsiveViewTreshold' => '768',
       'textLayer' => 'false',
       'pdfPageScale' => '',
       'backCover' => 'true', 
       'height' => '400',
       'responsiveHeight' => 'true',
       'aspectRatio' => '2',
       'thumbnailsOnStart' => 'false',
       'contentOnStart' => 'false',
       'searchOnStart' => '',
       'tableOfContentCloseOnClick' => 'true',
       'thumbsCloseOnClick' => 'true',
       'autoplayOnStart' => 'false',
       'autoplayInterval' => '3000',
       'autoplayStartPage' => '1',
       'autoplayLoop' => 'true',
       'rightToLeft' => 'false',
       'pageWidth' => '',
       'pageHeight' => '',
       'thumbSize' => '130',
       'logoImg' => '',
       'logoUrl' => '',
       'logoCSS' => 'position:absolute;left:0;top:0;',
       'menuSelector' => '',
       'zIndex' => 'auto',
       'preloaderText' => '',
       'googleAnalyticsTrackingCode' => '',
       'pdfBrowserViewerIfIE' => 'false',
       'viewModeMobile' => '',
       'pageTextureSizeMobile' => '',
       'aspectRatioMobile' => '',
       'singlePageModeIfMobile' => 'false',
       'pdfBrowserViewerIfMobile' => 'false',
       'pdfBrowserViewerFullscreen' => 'true',
       'pdfBrowserViewerFullscreenTarget' => '_blank',
       'btnTocIfMobile' => 'true',
       'btnThumbsIfMobile' => 'true',
       'btnShareIfMobile' => 'false',
       'btnDownloadPagesIfMobile' => 'true',
       'btnDownloadPdfIfMobile' => 'true',
       'btnSoundIfMobile' => 'false',
       'btnExpandIfMobile' => 'true',
       'btnPrintIfMobile' => 'false',
       'logoHideOnMobile' => 'false',
       'mobile' => Array(
         'thumbnailsOnStart' => 'false',
         'contentOnStart' => 'false',
       ),
       'lightboxCSS' => '',
       'lightboxLink' => '',
       'lightboxLinkNewWindow' => 'true',
       'lightboxBackground' => 'rgb(81, 85, 88)',
       'lightboxBackgroundPattern' => '',
       'lightboxBackgroundImage' => '',
       'lightboxContainerCSS' => 'display:inline-block;padding:10px;',
       'lightboxThumbnailHeight' => '300',
       'lightboxThumbnailUrlCSS' => 'display:block;',
       'lightboxThumbnailInfo' => 'false',
       'lightboxThumbnailInfoText' => '',
       'lightboxThumbnailInfoCSS' => 'top: 0;  width: 100%; height: 100%; font-size: 16px; color: #000; background: rgba(255,255,255,.8); ',
       'showTitle' => 'false',
       'hideThumbnail' => 'false',
       'lightboxText' => '',
       'lightboxTextCSS' => 'display:block;',
       'lightboxTextPosition' => 'top',
       'lightBoxOpened' => 'false',
       'lightBoxFullscreen' => 'false',
       'lightboxCloseOnClick' => 'false',
       'lightboxMarginV' => '0',
       'lightboxMarginH' => '0',
       'lights' => 'true',
       'lightPositionX' => '0',
       'lightPositionY' => '150',
       'lightPositionZ' => '1400',
       'lightIntensity' => '0.6',
       'shadows' => 'true',
       'shadowMapSize' => '2048',
       'shadowOpacity' => '0.2',
       'shadowDistance' => '15',
       'pageHardness' => '2',
       'coverHardness' => '2',
       'pageRoughness' => '1',
       'pageMetalness' => '0',
       'pageSegmentsW' => '6',
       'pageSegmentsH' => '1',
       'pageMiddleShadowSize' => '2',
       'pageMiddleShadowColorL' => '#999999',
       'pageMiddleShadowColorR' => '#777777',
       'antialias' => 'false',
       'pan' => '0',
       'tilt' => '0',
       'rotateCameraOnMouseDrag' => 'true',
       'panMax' => '20',
       'panMin' => '-20',
       'tiltMax' => '0',
       'tiltMin' => '-60',
       'currentPage' => Array(
            'enabled' => 'true',
            'title' => 'Current page',
            'hAlign' => 'left',
            'vAlign' => 'top'
        ),
       'btnAutoplay' => Array(
            'enabled' => 'true',
            'icon' => 'fa-play',
            'iconAlt' => 'fa-pause',
            'icon2' => 'play_arrow',
            'iconAlt2' => 'pause',
            'title' => 'Autoplay'
        ),
       'btnNext' => Array(
            'enabled' => 'true',
            'icon' => 'fa-chevron-right',
            'icon2' => 'chevron_right',
            'title' => 'Next Page'
        ),
       'btnLast' => Array(
            'enabled' => 'false',
            'icon' => 'fa-angle-double-right',
            'icon2' => 'last_page',
            'title' => 'Last Page'
        ),
       'btnPrev' => Array(
            'enabled' => 'true',
            'icon' => 'fa-chevron-left',
            'icon2' => 'chevron_left',
            'title' => 'Previous Page'
        ),
       'btnFirst' => Array(
            'enabled' => 'false',
            'icon' => 'fa-angle-double-left',
            'icon2' => 'first_page',
            'title' => 'First Page'
        ),
       'btnZoomIn' => Array(
            'enabled' => 'true',
            'icon' => 'fa-plus',
            'icon2' => 'zoom_in',
            'title' => 'Zoom in'
        ),
       'btnZoomOut' => Array(
            'enabled' => 'true',
            'icon' => 'fa-minus',
            'icon2' => 'zoom_out',
            'title' => 'Zoom out'
        ),
       'btnToc' => Array(
            'enabled' => 'true',
            'icon' => 'fa-list-ol',
            'icon2' => 'toc',
            'title' => 'Table of Contents'
        ),
       'btnThumbs' => Array(
            'enabled' => 'true',
            'icon' => 'fa-th-large',
            'icon2' => 'view_module',
            'title' => 'Pages'
        ),
       'btnShare' => Array(
            'enabled' => 'true',
            'icon' => 'fa-share-alt',
            'icon2' => 'share',
            'title' => 'Share'
        ),
       'btnDownloadPages' => Array(
            'enabled' => 'false',
            'url' => '',
            'icon' => 'fa-download',
            'icon2' => 'file_download',
            'title' => 'Download pages'
        ),
       'btnDownloadPdf' => Array(
            'enabled' => 'false',
            'url' => '',
            'icon' => 'fa-file',
            'icon2' => 'picture_as_pdf',
            'title' => 'Download PDF',
            'forceDownload' => 'true',
            'openInNewWindow' => 'true'
        ),
       'btnSound' => Array(
            'enabled' => 'true',
            'icon' => 'fa-volume-up',
            'iconAlt' => 'fa-volume-off',
            'icon2' => 'volume_up',
            'iconAlt2' => 'volume_mute',
            'title' => 'Sound'
        ),
       'btnExpand' => Array(
            'enabled' => 'true',
            'icon' => 'fa-expand',
            'iconAlt' => 'fa-compress',
            'icon2' => 'fullscreen',
            'iconAlt2' => 'fullscreen_exit',
            'title' => 'Toggle fullscreen'
        ),
       'btnSelect' => Array(
            'enabled' => 'true',
            'icon' => 'fas fa-i-cursor',
            'icon2' => 'text_format',
            'title' => 'Select tool'
        ),
       'btnSearch' => Array(
            'enabled' => 'false',
            'icon' => 'fas fa-search',
            'icon2' => 'search',
            'title' => 'Search'
        ),
        'btnBookmark' => Array(
            'enabled' => 'false',
            'icon' => 'fas fa-bookmark',
            'icon2' => 'bookmark',
            'title' => 'Bookmark'
        ),
       'btnPrint' => Array(
            'enabled' => 'true',
            'icon' => 'fa-print',
            'icon2' => 'print',
            'title' => 'Print'
        ),
        'btnClose' => Array(
         'enabled' => 'true'
        ),

       'google_plus' => Array(
            'enabled' => 'true',
            'url' => ''
        ),
       'twitter' => Array(
            'enabled' => 'true',
            'url' => '',
            'description' => ''
        ),
       'facebook' => Array(
            'enabled' => 'true',
            'url' => '',
            'description' => '',
            'title' => '',
            'image' => '',
            'caption' => ''
        ),
       'pinterest' => Array(
            'enabled' => 'true',
            'url' => '',
            'image' => '',
            'description' => ''
        ),
       'email' => Array(
            'enabled' => 'true',
            'url' => '',
            'description' => ''
        ),
       'theme' => 'default',
       'skin' => 'light',
       'useFontAwesome5' => 'true',
       'sideNavigationButtons' => 'true',
       'backgroundColor' => 'rgb(81, 85, 88)',
       'backgroundPattern' => '',
       'backgroundImage' => '',
       'backgroundTransparent' => 'false',

       'menuBackground' => '',
       'menuShadow' => '',
       'menuMargin' => '0',
       'menuPadding' => '0',
       'menuOverBook' => 'false',
       'menuFloating' => 'false',
       'menuTransparent' => 'false',

       'menu2Background' => '',
       'menu2Shadow' => '',
       'menu2Margin' => '0',
       'menu2Padding' => '0',
       'menu2OverBook' => 'true',
       'menu2Floating' => 'false',
       'menu2Transparent' => 'true',

       'skinColor' =>'',
        'skinBackground' =>'',

       'hideMenu' => 'false',
       'menuAlignHorizontal' => 'center',
       'btnColor' => '',
       'btnBackground' => 'none',
       'btnRadius' => '0',
       'btnMargin' => '0',
       'btnSize' => '14',
       'btnPaddingV' => '10',
       'btnPaddingH' => '10',
       'btnShadow' => '',
       'btnTextShadow' => '',
       'btnBorder' => '',
       'sideBtnColor' => '#fff',
       'sideBtnBackground' => 'rgba(0,0,0,.3)',
       'sideBtnRadius' => '0',
       'sideBtnMargin' => '0',
       'sideBtnSize' => '30',
       'sideBtnPaddingV' => '5',
       'sideBtnPaddingH' => '5',
       'sideBtnShadow' => '',
       'sideBtnTextShadow' => '',
       'sideBtnBorder' => '',
       'closeBtnColor' => '#FFF',
       'closeBtnBackground' => 'rgba(0,0,0,.4)',
       'closeBtnRadius' => '0',
       'closeBtnMargin' => '0',
       'closeBtnSize' => '20',
       'closeBtnPadding' => '5',
       'closeBtnTextShadow' => '',
       'closeBtnBorder' => '',
       'currentPageMarginV' => '5',
       'currentPageMarginH' => '5',
       'arrowsAlwaysEnabledForNavigation' => 'false',
       'touchSwipeEnabled' => 'true',
       'rightClickEnabled' => 'true',
       'strings' => Array(
            'print' => 'Print',
            'printLeftPage' => 'Print left page',
            'printRightPage' => 'Print right page',
            'printCurrentPage' => 'Print current page',
            'printAllPages' => 'Print all pages',
            'download' => 'Download',
            'downloadLeftPage' => 'Download left page',
            'downloadRightPage' => 'Download right page',
            'downloadCurrentPage' => 'Download current page',
            'downloadAllPages' => 'Download all pages',
            'bookmarks' => 'Bookmarks',
            'bookmarkLeftPage' => 'Bookmark left page',
            'bookmarkRightPage' => 'Bookmark right page',
            'bookmarkCurrentPage' => 'Bookmark current page',
            'search' => 'Search',
            'findInDocument' => 'Find in document',
            'pagesFoundContaining' => 'pages found containing',
            'thumbnails' => 'Thumbnails',
            'tableOfContent' => 'Table of Contents',
            'share' => 'Share',
            'pressEscToClose' => 'Press ESC to close',
        )
        
     );
}

function r3dfb_admin_notice(){

}




add_action( 'wp_ajax_r3d_preview', 'r3dfb_preview_callback' );
add_action( 'wp_ajax_nopriv_r3d_preview', 'r3dfb_preview_callback' );

function r3dfb_preview_callback() {

	$previewOptions = ($_POST);

	$globals = get_option("real3dflipbook_global");

	echo json_encode(array_merge($globals, $previewOptions));

	wp_die(); // this is required to terminate immediately and return a proper response

}

add_action( 'wp_ajax_r3d_save', 'r3dfb_save_callback' );
add_action( 'wp_ajax_nopriv_r3d_save', 'r3dfb_save_callback' );


function r3d_sanitize_array($arr)
{
   // foreach ($arr as $key => $val) {

   //    if(is_array($val))
   //      $arr[$key] = r3d_sanitize_array($val);
   //    else
   //      $arr[$key] = sanitize_text_field($val);
   //      $arr[$key] = wp_kses_post($val);

   // }

   return $arr;
}


function r3dfb_save_callback() {

  check_ajax_referer( 'saving-real3d-flipbook', 'security' );
  
  $current_id = $page_id = '';

  unset($_POST['security']);
  unset($_POST['action']);

  // trace($_POST);

  $data = r3d_sanitize_array($_POST);

  // trace($data);

  if (isset($data['id']) ) {
    $current_id = intval($data['id']);
  }

  $reak3dflipbooks_converted = get_option("reak3dflipbooks_converted");

  if(!$reak3dflipbooks_converted){

    $flipbooks = get_option("flipbooks");
    if(!$flipbooks){
      $flipbooks = array();
    }

    add_option('reak3dflipbooks_converted', true);
    $real3dflipbooks_ids = array();
    //trace('converting flipbooks...');
    foreach ($flipbooks as $b) {
      $id = $b['id'];
      //trace($id);
      delete_option('real3dflipbook_'.(string)$id);
      add_option('real3dflipbook_'.(string)$id, $b);
      array_push($real3dflipbooks_ids,(string)$id);
    }
    // trace($real3dflipbooks_ids);
  }else{
    // trace($real3dflipbooks_ids);
    $real3dflipbooks_ids = get_option('real3dflipbooks_ids');
    if(!$real3dflipbooks_ids){
      $real3dflipbooks_ids = array();
    }
    $flipbooks = array();
    foreach ($real3dflipbooks_ids as $id) {
      // trace($id);
      $book = get_option('real3dflipbook_'.$id);
      if($book){

        $flipbooks[$id] = $book;
        // array_push($flipbooks,$book);

        
      }else{
        //remove id from array
        $real3dflipbooks_ids = array_diff($real3dflipbooks_ids, array($id));
      }
    }
  }
  
  update_option('real3dflipbooks_ids', $real3dflipbooks_ids);

  if (!isset($data['pages']) ) {
    $data['pages'] = array();
  }

  if (!isset($data['tableOfContent']) ) {
    $data['tableOfContent'] = array();
  }
  
  if($flipbooks[(string)$current_id]){
    update_option('real3dflipbook_'.(string)$current_id, $data);
  } else{
    add_option('real3dflipbook_'.(string)$current_id, $data);
    array_push($real3dflipbooks_ids,$current_id);
    update_option('real3dflipbooks_ids',$real3dflipbooks_ids);
  }

  echo json_encode(get_option("real3dflipbooks_ids"));

  wp_die(); // this is required to terminate immediately and return a proper response
}


add_action( 'wp_ajax_r3d_save_general', 'r3d_save_general_callback' );
add_action( 'wp_ajax_nopriv_r3d_save_general', 'r3d_save_general_callback' );

function r3d_save_general_callback() {

  check_ajax_referer( 'r3d_nonce', 'security' );

  unset($_POST['security']);
  unset($_POST['action']);
  $data = r3d_sanitize_array($_POST);

  update_option('real3dflipbook_global', $data);

  wp_die(); 

} 

add_action( 'wp_ajax_r3d_reset_general', 'r3d_reset_general_callback' );
add_action( 'wp_ajax_nopriv_r3d_reset_general', 'r3d_reset_general_callback' );

function r3d_reset_general_callback() {

  check_ajax_referer( 'r3d_nonce', 'security' );

  r3dfb_setDefaults();

  wp_die(); // this is required to terminate immediately and return a proper response

}


add_action( 'wp_ajax_r3d_save_page', 'r3dfb_save_page_callback' );
add_action( 'wp_ajax_nopriv_r3d_save_page', 'r3dfb_save_page_callback' );

function r3dfb_save_page_callback() {

	check_ajax_referer( 'saving-real3d-flipbook', 'security' );

	$id = intval($_POST['id']);
	$book = get_option('real3dflipbook_'.$id);
	$bookName = $book['name'];
	$upload_dir = wp_upload_dir();
	$booksFolder = $upload_dir['basedir'] . '/real3d-flipbook/';
	$bookFolder = $booksFolder . 'flipbook_' . $id . '/';
	$file = $bookFolder.$_POST['page'].".jpg";
	$data = $_POST['dataurl'];
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
		echo(($upload_dir['baseurl'] . '/real3d-flipbook/flipbook_' .$id . '/'.$_POST['page'].'.jpg'));
	}

	wp_die();

}



add_action( 'wp_ajax_r3d_save_page_json', 'r3dfb_save_page_json_callback' );
add_action( 'wp_ajax_nopriv_r3d_save_page_json', 'r3dfb_save_page_json_callback' );

function r3dfb_save_page_json_callback() {

  check_ajax_referer( 'saving-real3d-flipbook', 'security' );

  $id = intval($_POST['id']);
  $book = get_option('real3dflipbook_'.$id);
  $bookName = $book['name'];
  $upload_dir = wp_upload_dir();
  $booksFolder = $upload_dir['basedir'] . '/real3d-flipbook/';
  $bookFolder = $booksFolder . 'flipbook_' . $id . '/';
  $file = $bookFolder.$_POST['page'].".json";
  $data = stripslashes($_POST['dataurl']);
  // $uri = substr($data,strpos($data, ",") + 1);

  if (!file_exists($booksFolder)) {
    mkdir($booksFolder, 0777, true);
  }

  if (!file_exists($bookFolder)) {
    mkdir($bookFolder, 0777, true);
  }

  if(!file_put_contents($file, $data)){
    echo " failed writing image ".$file;
  }else{
    echo(($upload_dir['baseurl'] . '/real3d-flipbook/flipbook_' .$id . '/'.$_POST['page'].'.json'));
  }

  wp_die();

}



function real3d_flipbook_add_new(){
	$_GET['action'] = "add_new";
	real3d_flipbook_admin();
}

function r3dfb_elementor_init(){
	//elementor init
}
add_action('elementor/init', 'r3dfb_elementor_init');



