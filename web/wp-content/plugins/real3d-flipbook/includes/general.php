<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<div class='wrap'>
   <div id='real3dflipbook-admin' style="display:none;">
      <a href="admin.php?page=real3d_flipbook_admin" class="back-to-list-link">&larr; 
      <?php _e('Back to flipbooks list', 'flipbook'); ?>
      </a>
      <h1>Global Settings</h1>
      <p>Global default settings for all flipbooks.</p>
      <form method="post" id="real3dflipbook-options-form" enctype="multipart/form-data" action="admin-ajax.php?page=real3d_flipbook_admin&action=save_settings">
         <div>
            <h2 id="r3d-tabs" class="nav-tab-wrapper wp-clearfix">
               <a href="#" class="nav-tab" data-tab="tab-general">General</a>
               <a href="#" class="nav-tab" data-tab="tab-lightbox">Lightbox</a>
               <a href="#" class="nav-tab" data-tab="tab-webgl">WebGL</a>
               <a href="#" class="nav-tab" data-tab="tab-mobile">Mobile</a>
               <a href="#" class="nav-tab" data-tab="tab-ui">UI</a>
               <a href="#" class="nav-tab" data-tab="tab-menu">Menu Buttons</a>
               <a href="#" class="nav-tab" data-tab="tab-translate">Translate</a>
            </h2>
         </div>
         <div class="">
            <div id="tab-general" style="display:none;">
               <table class="form-table" id="flipbook-general-options">
                  <tbody></tbody>
               </table>
            </div>
            <div id="tab-normal"  style="display:none;">
               <table class="form-table" id="flipbook-normal-options">
                  <tbody></tbody>
               </table>
            </div>
            <div id="tab-mobile"  style="display:none;">
               <p class="description">
               <p>Override settings for mobile devices (use different view mode, smaller textures ect)</p>
               </p>
               <table class="form-table" id="flipbook-mobile-options">
                  <tbody></tbody>
               </table>
            </div>
            <div id="tab-lightbox"  style="display:none;">
               <table class="form-table" id="flipbook-lightbox-options">
                  <tbody></tbody>
               </table>
            </div>
            <div id="tab-webgl"  style="display:none;">
               <table class="form-table" id="flipbook-webgl-options">
                  <tbody></tbody>
               </table>
            </div>
            <div id="tab-menu"  style="display:none;">
               <div id="poststuff">
                  <div class="meta-box-sortables">
                     <h3>Menu buttons</h3>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Current page</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Current page</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-currentPage-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: First page</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>First page</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnFirst-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Previous page</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Previous page</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnPrev-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Next page</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Next page</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnNext-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Last page</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Last page</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnLast-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Autoplay</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Autoplay</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnAutoplay-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Zoom in</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Zoom in</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnZoomIn-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Zoom out</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Zoom out</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnZoomOut-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Table of Contents</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Table of Contents</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnToc-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Thumbnails</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Thumbnails</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnThumbs-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Share</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Share</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnShare-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Print</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Print</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnPrint-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Download pages</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Download pages</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnDownloadPages-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Download PDF</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Download PDF</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnDownloadPdf-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Sound</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Sound</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnSound-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Fullscreen</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Fullscreen</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnExpand-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Select tool</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Select tool</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnSelect-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Search</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Search</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnSearch-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Bookmark</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Bookmark</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnBookmark-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>

                     <h3>Social share buttons</h3>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Share on Google plus</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Share on Google plus</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-google_plus-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Share on Twitter</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Share on Twitter</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-twitter-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Share on Facebook</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Share on Facebook</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-facebook-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Share on Pinterest</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Share on Pinterest</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-pinterest-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Share by Email</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Share by Email</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-email-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            
            <div id="tab-ui"  style="display:none;">
               <div id="poststuff">
                  <div class="meta-box-sortables">

                     <table class="form-table" id="flipbook-ui-options">
                        <tbody></tbody>
                     </table>
                     <h3>Advanced settings</h3>
                     <p>Override layout and skin settings</p>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Skin</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Skin</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-skin-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Flipbook background</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Flipbook background</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-bg-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Top Menu</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Top Menu</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-menu-bar-2-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Bottom Menu</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Bottom Menu</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-menu-bar-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Buttons</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Buttons</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-menu-buttons-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>

                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Floating buttons (on transparent menu)</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Floating buttons (on transparent menu)</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-menu-floating-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>

                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Side navigation buttons</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Side navigation buttons</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-side-buttons-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>

                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Close lightbox button</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Close lightbox button</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-close-button-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>


                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Sidebar</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Sidebar</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-sidebar-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            
            <div id="tab-translate"  style="display:none;">
               <table class="form-table" id="flipbook-translate-options">
                  <tbody></tbody>
               </table>
            </div>
         </div>
   </div>
   <p id="r3d-save" class="submit">
   <span class="spinner"></span>
   <!-- <a class="update-all-flipbooks alignright" href='#'>Save this settings for all flipbooks</a> --> 
   <input type="submit" name="btbsubmit" id="btbsubmit" class="alignright button save-button button-primary" value="Save">
   <a href="#" class="alignright flipbook-reset-defaults button button-secondary">Reset to defaults</a>
   </p>
   <div id="r3d-save-holder" style="display: none;" />
   </form>
   </div>
</div>
<?php 

wp_enqueue_media();
// add_thickbox(); 
wp_enqueue_style( 'real3d-flipbook-font-awesome'); 
wp_enqueue_script( 'alpha-color-picker');
wp_enqueue_style( 'alpha-color-picker');
wp_enqueue_script( "real3d-flipbook-settings"); 
wp_enqueue_style( 'real3d-flipbook-admin-css'); 

$flipbook_global = get_option( "real3dflipbook_global" );

$flipbook_global_defaults = r3dfb_getDefaults();

$flipbook = array_merge($flipbook_global_defaults, $flipbook_global);

$r3d_nonce = wp_create_nonce( "r3d_nonce");
wp_localize_script( 'real3d-flipbook-settings', 'r3d_nonce', $r3d_nonce );

$flipbook["globals"] = $flipbook_global;
$flipbook["globals_defaults"] = $flipbook_global_defaults;
wp_localize_script( 'real3d-flipbook-settings', 'options', json_encode($flipbook) );