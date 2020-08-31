var pluginDir = (function(scripts) {
    var scripts = document.getElementsByTagName('script'),
        script = scripts[scripts.length - 1];
    if (script.getAttribute.length !== undefined) {
        return script.src.split('js/edit_flipbook')[0]
    }
    return script.getAttribute('src', -1).split('js/edit_flipbook')[0]
})();


(function($) {

    $(document).ready(function() {

        PDFJS = pdfjsLib

        postboxes.save_state = function(){
            return;
        };
        postboxes.save_order = function(){
            return;
        };

        if(postboxes.handle_click && !postboxes.handle_click.guid)
            postboxes.add_postbox_toggles();

        var $editPageModal = $("#edit-page-modal");
        var $modalBackdrop = $(".media-modal-backdrop");

        $(".media-modal-close").click(closeModal);

        function closeModal(){
            $editPageModal.hide()
            $modalBackdrop.hide()
            $("body").css("overflow", "auto");
        }

        $('#real3dflipbook-admin').show()

        var pdfDocument = null
       
        $('.creating-page').hide()

        options = $.parseJSON(window.flipbook)

        function convertStrings(obj) {

            $.each(obj, function(key, value) {

                if (typeof(value) == 'object' || typeof(value) == 'array') {
                    convertStrings(value)
                } else if (!isNaN(value)) {
                    if (obj[key] == "")
                        delete obj[key]
                    else if(key != "security")
                        obj[key] = Number(value)
                } else if (value == "true") {
                    obj[key] = true
                } else if (value == "false") {
                    obj[key] = false
                }
            });

        }
        convertStrings(options)

        var title
        if (options.status == "draft") {
            title = 'Add New Flipbook'
        } else {
            title = 'Edit Flipbook'
        }

        $("#edit-flipbook-text").text(title)
        $("#title").val(r3d_stripslashes(options.name))


        addOptionGeneral(
            "mode", 
            "dropdown",         
            "Mode", 
            '<strong>normal</strong> - embedded in a container div<br/><strong>lightbox</strong> - opens in fullscreen overlay on click<br/><strong>fullscreen</strong> - covers entire page',
            ["normal", "lightbox", "fullscreen"]
        );
        
        addOptionGeneral(
            "viewMode", 
            "dropdown",         
            "View mode", 
            '<strong>webgl</strong> - realistic 3D page flip with lights and shadows<br/><strong>3d</strong> - CSS 3D flip<br/><strong>swipe</strong> - horizontal swipe<br/><strong>simple</strong> - no animation',
            ["webgl", "3d", "2d", "swipe", "simple"]
        );
        
        addOptionGeneral( 
            "zoomMin",          
            "text", 
            "Initial zoom", 
            'Initial book zoom, recommended between 0.8 and 1'
        );
        
        addOptionGeneral( 
            "zoomStep",         
            "text", 
            "Zoom step", 
            'Between 1.1 and 4'
        );

        addOptionGeneral( 
            "zoomSize",         
            "text", 
            "Zoom size",
            "Override maximum zoom, for example 4000 will zoom the page until page height on screen is 4000px)"
        );

        addOptionGeneral( 
            "zoomReset",         
            "checkbox", 
            "Reset Zoom", 
            'Reset zoom after page flip, window resize, exit from fullscreen or toggle toc, thumbs, bookmarks, search'
        );

        addOptionGeneral( 
            "doubleClickZoom",         
            "checkbox", 
            "Double click zoom"
        );

        addOptionGeneral( 
            "singlePageMode",   
            "checkbox", 
            "Single page view", 
            'Display one page at a time'
        );

        addOptionGeneral( 
            "pageFlipDuration", 
            "text", 
            "Flip duration", 
            'Duration of flip animation, recommended between 0.5 and 2'
        );

        addOptionGeneral( 
            "sound",            
            "checkbox", 
            "Page flip sound"
        );
        addOptionGeneral( 
            "startPage",        
            "text", 
            "Start page", 
            'Open flipbook at this page at start'
        );

        addOptionGeneral( 
            "deeplinking[enabled]", 
            "checkbox", 
            "Deep linking", 
            'enable to use URL hash to link to specific page, for example #2 will open page 2'
        );

        addOptionGeneral( 
            "deeplinking[prefix]", 
            "text", 
            "Deep linking prefix", 
            'custom deep linking prefix, for example "book1_", link to page 2 will have URL hash #book1_2'
        );
        
        addOptionGeneral( 
            "responsiveView",   
            "checkbox",
            "Responsive view", 
            'Switching from two page layout to one page layout if flipbook width is below certain treshold'
        );
        
        addOptionGeneral( 
            "responsiveViewTreshold", 
            "text", 
            "Responsive view treshold", 
            'Treshold (screen width in px) for responsive view feature'
        );

        addOptionGeneral( 
            "pageTextureSize",  
            "text", 
            "PDF page size (full)", 
            'height of rendered PDF pages in px.'
        );

        addOptionGeneral( 
            "pageTextureSizeSmall",  
            "text", 
            "PDF page size (small)", 
            'height of rendered PDF pages in px'
        );
        
        addOptionGeneral( 
            "textLayer", 
            "checkbox", 
            "PDF text layer", 
            'PDF text selection'
        );
        
        addOptionGeneral( 
            "pdfPageScale", 
            "text", 
            "PDF page scale"
        );

        addOptionGeneral( 
            "backCover", 
            "checkbox", 
            "Back cover"
        );
        
        addOptionGeneral( 
            "aspectRatio", 
            "text", 
            "Container responsive ratio", 
            'Container width / height ratio, recommended between 1 and 2'
        );
        
        addOptionGeneral( 
            "thumbnailsOnStart", 
            "checkbox", 
            "Show Thumbnails on start"
        );
        
        addOptionGeneral( 
            "contentOnStart", 
            "checkbox", 
            "Show Table of Contents on start"
        );

        addOptionGeneral( 
            "searchOnStart", 
            "text", 
            "Search PDF on start"
        );
        
        addOptionGeneral( 
            "tableOfContentCloseOnClick",
             "checkbox", 
             "Close Table of Contents when page is clicked"
        );
        
        addOptionGeneral( 
            "thumbsCloseOnClick", 
            "checkbox", 
            "Close Thumbnails when page is clicked"
        );
        
        addOptionGeneral( 
            "autoplayOnStart", 
            "checkbox", 
            "Autoplay on start"
        );
        
        addOptionGeneral( 
            "autoplayInterval", 
            "text", 
            "Autoplay interval (ms)"
        );
        
        addOptionGeneral( 
            "autoplayStartPage", 
            "text", 
            "Autoplay start page"
        );

        addOptionGeneral( 
            "autoplayLoop", 
            "checkbox", 
            "Autoplay loop"
        );
        
        addOptionGeneral( 
            "rightToLeft", 
            "checkbox", 
            "Right to left mode", 
            'Flipping from right to left (inverted)'
        );
        
        addOptionGeneral( 
            "thumbSize",
             "text", 
             "Thumbnail size", 
             'Thumbnail height for thumbnails view'
        );

        addOptionGeneral( 
            "logoImg",
             "selectImage", 
             "Logo image",
              'Logo image that will be displayed inside the flipbook container'
        );

        addOptionGeneral( 
            "logoUrl",
             "text", 
             "Logo link", 
             'URL that will be opened on logo click'
        );

        addOptionGeneral( 
            "logoCSS", 
            "textarea",
             "Logo CSS",
             'Custom CSS for logo'
        );

        addOptionGeneral( 
            "menuSelector",
            "text",
            "Menu css selector",
            'Example "#menu" or ".navbar". Used with mode "fullscreen" so the flipbook will be resized correctly below the menu'
        );

        addOptionGeneral( 
            "zIndex", 
            "text", 
            "Container z-index",
            'Set z-index of flipbook container'
        );

        addOptionGeneral( 
            'preloaderText', 
            'text', 
            'Preloader text', 
            'Text that will be displayed under the preloader spinner'
        );

        addOptionGeneral( 
            'googleAnalyticsTrackingCode', 
            'text', 
            'Google analytics tracking code'
        );

        addOptionGeneral( 
            "pdfBrowserViewerIfIE",         
            "checkbox",         
            "Download PDF instead of displaying flipbook if browser is Internet Explorer", 
            'For PDF flipbook'
        );

        addOptionGeneral( 
            "arrowsAlwaysEnabledForNavigation",         
            "checkbox",         
            "Force keyboard arrows for navigation", 
            'Enable keyboard arrows for navigation even if not fullscreen'
        );

        addOptionGeneral( 
            "touchSwipeEnabled",         
            "checkbox",         
            "Touch swipe to turn page", 
            'Turn pages with touch & swipe or click & drag'
        );

        addOptionGeneral( 
            "rightClickEnabled",         
            "checkbox",         
            "Right click context menu", 
            'Disable to prevent right click image download'
        );

        addOptionMobile( 
            "modeMobile", "dropdown",         
            "Mode", 
            'Override default mode for mobile',
            ["", "normal", "lightbox", "fullscreen"]
        );

        addOptionMobile( 
            "viewModeMobile", "dropdown",         
            "View mode", 
            'Override default view mode for mobile',
            ["", "webgl", "3d", "2d", "swipe", "simple"]
        );

        addOptionMobile( 
            "pageTextureSizeMobile",  
            "text", 
            "PDF page size (full)", 
            'The height of rendered PDF pages in px'
        );

        addOptionMobile( 
            "pageTextureSizeMobileSmall",  
            "text", 
            "PDF page size (small)", 
            'The height of rendered PDF pages in px'
        );

        addOptionMobile( 
            "aspectRatioMobile", 
            "text", 
            "Container responsive ratio", 
            'Container width / height ratio, recommended between 1 and 2'
        );

        addOptionMobile( 
            "singlePageModeIfMobile", 
            "checkbox", 
            "Single page view", 
            'Display one page at a time'
        );

        addOptionMobile( 
            "pdfBrowserViewerIfMobile",
            "checkbox", 
            "Use default device PDF viewer instead of flipbook", 
            'Opens PDF file directly in browser, instead of flipbook'
        );

        addOptionMobile(
            "mobile[contentOnStart]", 
            "checkbox", 
            "Show Table of Contents on start"
        );

        addOptionMobile(
            "mobile[thumbnailsOnStart]", 
            "checkbox", 
            "Show Thumbnails on start"
        );

        // addOptionMobile(
        //     "mobile[touchSwipeEnabled]", 
        //     "checkbox", 
        //     "Touch swipe to turn page", 
        //     'Turn pages with touch & swipe or click & drag'
        // );

        addOptionMobile(
         "pdfBrowserViewerFullscreen", 
         "checkbox", 
         "Default device PDF viewer fullscreen"
        );

        addOptionMobile( 
            "pdfBrowserViewerFullscreenTarget",
            "dropdown", 
            "Default device PDF viewer target", 
            'Opens PDF file in new tab or in same tab',
            ["_self", "_blank"]
        );

        addOptionMobile( 
            "btnTocIfMobile", 
            "checkbox", 
            "Button Table of Contents"
        );

        addOptionMobile( 
            "btnThumbsIfMobile", 
            "checkbox", 
            "Button Thumbnails"
        );

        addOptionMobile( 
            "btnShareIfMobile", 
            "checkbox", 
            "Button Share"
        );

        addOptionMobile( 
            "btnDownloadPagesIfMobile", 
            "checkbox", 
            "Button Download pages"
        );

        addOptionMobile( 
            "btnDownloadPdfIfMobile", 
            "checkbox", 
            "Button View pdf"
        );

        addOptionMobile( 
            "btnSoundIfMobile", 
            "checkbox", 
            "Button Sound"
        );

        addOptionMobile( 
            "btnExpandIfMobile", 
            "checkbox", 
            "Button Fullscreen"
        );

        addOptionMobile( 
            "btnPrintIfMobile", 
            "checkbox", 
            "Button Print"
        );

        addOptionMobile( 
            "logoHideOnMobile",
             "checkbox", 
             "Hide logo"
        );

        addOptionLightbox( 
            "lightboxCssClass", 
            "text", 
            "CSS class", 
            'CSS class that will trigger lightbox. Add this CSS class to any element that you want to trigger lightbox (Flipbook shortcode needs to be on the page)'
        );

        addOptionLightbox( 
            "lightboxBackground", 
            "color", 
            "Overlay background", 
            'CSS value'
        );

        addOptionLightbox( 
            "lightboxBackgroundPattern", 
            "selectImage", 
            "Overlay background pattern", 
            'Lightbox background image (repeated)'
        );

        addOptionLightbox( 
            "lightboxBackgroundImage", 
            "selectImage", 
            "Overlay background image", 
            'Lightbox background image'
        );

        addOptionLightbox( 
            "lightboxContainerCSS", 
            "textarea", 
            "Thumbnail container CSS"
        );
        
        addOptionLightbox( 
            "lightboxThumbnailUrl", 
            "selectImage", 
            "Thumbnail", 
            'Image that will be displayed in place of shortcode, and will trigger lightbox on click'
        );
        
        var $thumbRow = $("input[name='lightboxThumbnailUrl']").parent()
        var $btnGenerateThumb = $('<a class="generate-thumbnail-button button-secondary button80" href="#">Generate thumbnail</a>').appendTo($thumbRow)
        
        addOptionLightbox( 
            "lightboxThumbnailHeight", 
            "text", 
            "Thumbnail height", 
            'Height of thumbnail that will be generated from PDF'
        );

        addOptionLightbox( 
            "lightboxThumbnailUrlCSS", 
            "textarea", 
            "Thumbnail CSS",  
            'Custom CSS for lightbox thumbnail image'
        );

        addOptionLightbox( 
            "lightboxThumbnailInfo", 
            "checkbox", 
            "Thumbnail info",  
            'book info displayed over thumbnail'
        );

        addOptionLightbox( 
            "lightboxThumbnailInfoText", 
            "text", 
            "Thumbnail info text",  
            'if not set book name will be used'
        );

        addOptionLightbox( 
            "lightboxThumbnailInfoCSS", 
            "textarea", 
            "Thumbnail info CSS",  
            'custom CSS'
        );

        addOptionLightbox( 
            "lightboxText", 
            "text", 
            "Text link", 
            'Text that will be displayed in place of shortcode'
        );

        addOptionLightbox( 
            "lightboxTextCSS", 
            "textarea", 
            "Text link CSS",
            'Custom CSS for text link'
        );

        addOptionLightbox( 
            "lightboxTextPosition", 
            "dropdown", 
            "Text link position", 
            'Text link above or below the thumbnail',
            ["top", "bottom"]
        );

        addOptionLightbox( 
            "lightBoxOpened", 
            "checkbox", 
            "Opened on start", 
            'Lightbox will open automatically on page load'
        );

        addOptionLightbox( 
            "lightBoxFullscreen", 
            "checkbox", 
            "Openes in fullscreen", 
            'Opening the lightbox will put lightbox element to real fullscreen'
        );

        addOptionLightbox( 
            "lightboxCloseOnClick", 
            "checkbox", 
            "Closes when clicked outside the book", 
            'Close lightbox if clicked on the overlay but outside the book'
        );

        addOptionLightbox( 
            "showTitle", 
            "checkbox", 
            "Show title"
        );

        addOptionLightbox( 
            "hideThumbnail", 
            "checkbox", 
            "Hide thumbnail"
        );

        addOptionLightbox( 
            "lightboxMarginV", 
            "text", 
            "Vertical margin",
            'Lightbox overlay vertical margin'
        );

        addOptionLightbox   ( 
            "lightboxMarginH", 
            "text", 
            "Horizontal margin",
            'Lightbox overlay horizontal margin'
        );

        addOptionLightbox( 
            "lightboxLink", 
            "text", 
            "Link", 
            'Open URL (instead of opening flipbook)'
        );

        addOptionLightbox( 
            "lightboxLinkNewWindow", 
            "checkbox", 
            "Link opens in new window"
        );

        function addMenuButton(name, icon, iconAlt){
            addOption(
                name, 
                name+"[enabled]", 
                "checkbox", 
                "Enabled"
            );

            addOption(
                name, 
                name+"[title]", 
                "text", 
                "Title"
            );

            addOption(
                name, 
                name+"[vAlign]", 
                "dropdown", 
                 "Vertical align", 
                "",
                ['top', 'bottom']
            );

            addOption(
                name, 
                name+"[hAlign]", 
                "dropdown", 
                 "Horizontal align", 
                "",
                ['right', 'left', 'center']
            );

            addOption(
                name, 
                name+"[order]", 
                "text", 
                "Order"
            );

            if(icon)
                addOption(
                    name, 
                    name+"[icon]", 
                    "text", 
                    "Font awesome icon"
                );

            if(iconAlt)
                addOption(
                    name, 
                    name+"[iconAlt]", 
                    "text", 
                    "Alt Font awesome icon"
                );

            if(icon)
                addOption(
                    name, 
                    name+"[icon2]", 
                    "text", 
                    "Material icon"
                );

            if(iconAlt)
                addOption(
                    name, 
                    name+"[iconAlt2]", 
                    "text", 
                    "Alt Material icon"
                );

        }

        addMenuButton("currentPage")
        addMenuButton("btnAutoplay", 1, 1)
        addMenuButton("btnNext", 1)
        addMenuButton("btnPrev", 1)
        addMenuButton("btnFirst", 1)
        addMenuButton("btnLast", 1)
        addMenuButton("btnZoomIn", 1)
        addMenuButton("btnZoomOut", 1)
        addMenuButton("btnToc", 1)
        addMenuButton("btnThumbs", 1)
        addMenuButton("btnShare", 1)
        addMenuButton("btnSound", 1, 1)
        addMenuButton("btnExpand", 1, 1)
        addMenuButton("btnDownloadPages", 1)
        addMenuButton("btnDownloadPdf", 1)
        addMenuButton("btnPrint", 1)
        addMenuButton("btnSelect", 1)
        addMenuButton("btnSearch", 1)
        addMenuButton("btnBookmark", 1)

        
        addOption(
            "btnDownloadPages", 
            "btnDownloadPages[url]", 
            "selectFile", 
            "URL of zip file containing all pages"
        );

        addOption(
            "btnDownloadPdf", 
            "btnDownloadPdf[url]", 
            "selectFile", 
            "PDF file URL"
        );

        addOption(
            "btnDownloadPdf", 
            "btnDownloadPdf[forceDownload]", 
            "checkbox", 
            "force download"
        );

        addOption(
            "btnDownloadPdf", 
            "btnDownloadPdf[openInNewWindow]", 
            "checkbox", 
            "open PDF in new browser window"
        );

        addOption(
            "btnPrint", 
            "printPdfUrl", 
            "selectFile", 
            "PDF file for printing"
        );

        addOption(
            "google_plus", 
            "google_plus[enabled]", 
            "checkbox", 
            "Enabled"
        );

        addOption(
            "google_plus", 
            "google_plus[url]", 
            "text", 
            "URL"
        );

        addOption(
            "twitter", 
            "twitter[enabled]", 
            "checkbox", 
            "Enabled"
        );

        addOption(
            "twitter", 
            "twitter[url]", 
            "text", 
            "URL"
        );

        addOption(
            "twitter", 
            "twitter[description]", 
            "text", 
            "Description"
        );

        addOption(
            "facebook", 
            "facebook[enabled]", 
            "checkbox", 
            "Enabled"
        );

        addOption(
            "facebook", 
            "facebook[url]", 
            "text", 
            "URL"
        );

        addOption(
            "facebook", 
            "facebook[description]", 
            "text", 
            "Description"
        );

        addOption(
            "facebook", 
            "facebook[title]", 
            "text", 
            "Title"
        );

        addOption(
            "facebook", 
            "facebook[image]", 
            "text", 
            "Image"
        );

        addOption(
            "facebook", 
            "facebook[caption]", 
            "text", 
            "Caption"
        );

        addOption(
            "pinterest", 
            "pinterest[enabled]", 
            "checkbox", 
            "Enabled"
        );

        addOption(
            "pinterest", 
            "pinterest[url]", 
            "text", 
            "URL"
        );

        addOption(
            "pinterest", 
            "pinterest[image]", 
            "text", 
            "Image"
        );

        addOption(
            "pinterest", 
            "pinterest[description]", 
            "text", 
            "Description"
        );

        addOption(
            "email", 
            "email[enabled]", 
            "checkbox", 
            "Enabled"
        );

        addOption(
            "email", 
            "email[url]", 
            "text", 
            "URL"
        );

        addOption(
            "email", 
            "email[description]", 
            "text", 
            "Description"
        );

        
         addOptionWebgl(
            "lights", 
            "checkbox", 
            "Lights",
            'realistic lightning, disable for faster performance'
        );

        addOptionWebgl(
            "lightPositionX", 
            "text", 
            "Light pposition x", 
            'between -500 and 500'
        );

        addOptionWebgl(
            "lightPositionY", 
            "text", 
            "Light position y", 
            'between -500 and 500'
        );

        addOptionWebgl(
            "lightPositionZ", 
            "text", 
            "Light position z", 
            'between 1000 and 2000'
        );

        addOptionWebgl(
            "lightIntensity", 
            "text", 
            "Light intensity", 
            'between 0 and 1'
        );

        addOptionWebgl(
            "shadows", 
            "checkbox", 
            "Shadows", 
            'realistic page shadows, disable for faster performance'
        );

        addOptionWebgl(
            "shadowOpacity", 
            "text", 
            "Shadow opacity", 
            'between 0 and 1'
        );

        addOptionWebgl(
            "pageHardness", 
            "text", 
            "Page hardness", 
            'between 1 and 5'
        );

        addOptionWebgl(
            "coverHardness", 
            "text", 
            "Cover hardness", 
            'between 1 and 5'
        );

        addOptionWebgl(
            "pageRoughness", 
            "text", 
            "Page material roughness", 
            'between 0 and 1'
        );

        addOptionWebgl(
            "pageMetalness", 
            "text", 
            "Page material metalness", 
            'between 0 and 1'
        );

        addOptionWebgl(
            "pageSegmentsW", 
            "text", 
            "Page segments W", 
            'between 3 and 20'
        );

        addOptionWebgl(
            "pageMiddleShadowSize", 
            "text", 
            "Page middle shadow size", 
            'shadow in the middle of the book'
        );

        addOptionWebgl(
            "pageMiddleShadowColorL", 
            "color", 
            "left page middle shadow color"
        );

        addOptionWebgl(
            "pageMiddleShadowColorR", 
            "color", 
            "right page middle shadow color"
        );

        addOptionWebgl(
            "antialias", 
            "checkbox", 
            "Antialiasing", 
            'disable for faster performance'
        );

        addOptionWebgl(
            "pan", 
            "text", 
            "Camera pan angle", 
            'between -10 and 10'
        );

        addOptionWebgl(
            "tilt", 
            "text", 
            "Camera tilt angle", 
            'between -30 and 0'
        );

        addOptionWebgl(
            "rotateCameraOnMouseDrag", 
            "checkbox", 
            "rotate camera on mouse drag"
        );

        addOptionWebgl(
            "panMax", 
            "text", 
            "Camera pan max angle", 
            'between 0 and 20'
        );

        addOptionWebgl(
            "panMin", 
            "text", 
            "Camera pan min angle", 
            'between -20 and 0'
        );

        addOptionWebgl(
            "tiltMax", 
            "text", 
            "Camera tilt max angle", 
            'between -60 and 0'
        );

        addOptionWebgl(
            "tiltMin", 
            "text", 
            "Camera tilt min angle", 
            'between -60 and 0'
        );

        //UI
        
        addOption(
            "menu-bar-2", 
            "menu2Background", 
            "color", 
            "Background color", 
            'custom CSS'
        );

        addOption(
            "menu-bar-2", 
            "menu2Shadow", 
            "text", 
            "Shadow", 
            'custom CSS'
        );

        addOption(
            "menu-bar-2", 
            "menu2Margin", 
            "text", 
            "Margin"
        );

        addOption(
            "menu-bar-2", 
            "menu2Padding", 
            "text", 
            "Padding"
        );

        addOption(
            "menu-bar-2", 
            "menu2OverBook", 
            "checkbox", 
            "Over book", 
            'menu covers the book (overlay)'
        );

        addOption(
            "menu-bar-2", 
            "menu2Transparent", 
            "checkbox", 
            "Transoarent", 
            'menu has no background'
        );

        addOption(
            "menu-bar-2", 
            "menu2Floating", 
            "checkbox", 
            "Floating", 
            'small menu floating over book, not full width'
        );

        addOption(
            "menu-bar", 
            "menuBackground", 
            "color", 
            "Background color", 
            'custom CSS'
        );

        addOption(
            "menu-bar", 
            "menuShadow", 
            "text", 
            "Shadow", 
            'custom CSS'
        );

        addOption(
            "menu-bar", 
            "menuMargin", 
            "text", 
            "Margin"
        );

        addOption(
            "menu-bar", 
            "menuPadding", 
            "text", 
            "Padding"
        );

        addOption(
            "menu-bar", 
            "menuOverBook", 
            "checkbox", 
            "Over book", 
            'menu covers the book (overlay)'
        );

        addOption(
            "menu-bar", 
            "menuTransparent", 
            "checkbox", 
            "Transoarent", 
            'Menu has no background'
        );

        addOption(
            "menu-bar", 
            "menuFloating", 
            "checkbox", 
            "Floating", 
            'small menu floating over book, not full width'
        );

        addOption(
            "menu-bar", 
            "hideMenu", 
            "checkbox", 
            "Hide menu", 
            'hide menu completely'
        );

        addOption(
            "menu-buttons", 
            "btnColor", 
            "color", 
            "Color"
        );

        addOption(
            "menu-buttons", 
            "btnBackground", 
            "color", 
            "Background color"
        );

        addOption(
            "menu-buttons", 
            "btnRadius", 
            "text", 
            "Radius", 
            'px'
        );

        addOption(
            "menu-buttons", 
            "btnMargin", 
            "text", 
            "Margin", 
            'px'
        );

        addOption(
            "menu-buttons", 
            "btnSize", 
            "text", 
            "Size", 
            'between 8 and 20'
        );

        addOption(
            "menu-buttons", 
            "btnPaddingV", 
            "text", 
            "Padding vertical", 
            'between 0 and 20'
        );

        addOption(
            "menu-buttons", 
            "btnPaddingH", 
            "text", 
            "Padding horizontal", 
            'between 0 and 20'
        );

        addOption(
            "menu-buttons", 
            "btnShadow", 
            "text", 
            "Box shadow", 
            'custom CSS'
        );

        addOption(
            "menu-buttons", 
            "btnTextShadow", 
            "text", 
            "Text shadow", 
            'custom CSS'
        );

        addOption(
            "menu-buttons", 
            "btnBorder", 
            "text", 
            "Border", 
            'custom CSS'
        );

        addOption(
            "side-buttons", 
            "sideNavigationButtons", 
            "checkbox", 
            "Enabled",
            "Arrows on the sides"
        );

        addOption(
            "side-buttons", 
            "sideBtnColor", 
            "color", 
            "Color"
        );

        addOption(
            "side-buttons", 
            "sideBtnBackground", 
            "color", 
            "Background color"
        );

        addOption(
            "side-buttons", 
            "sideBtnRadius", 
            "text", 
            "Radius", 
            'px'
        );

        addOption(
            "side-buttons", 
            "sideBtnMargin", 
            "text", 
            "Margin", 
            'px'
        );

        addOption(
            "side-buttons", 
            "sideBtnSize", 
            "text", 
            "Size", 
            'Side buttons margin size, between 8 and 50'
        );

        addOption(
            "side-buttons", 
            "sideBtnPaddingV", 
            "text", 
            "Padding vertical", 
            'Side buttons padding vertical, between 0 and 10'
        );

        addOption(
            "side-buttons", 
            "sideBtnPaddingH", 
            "text", 
            "Padding horizontal", 
            'Side buttons padding horizontal, between 0 and 10'
        );

        addOption(
            "side-buttons", 
            "sideBtnShadow", 
            "text", 
            "Box shadow", 
            'custom CSS'
        );

        addOption(
            "side-buttons", 
            "sideBtnTextShadow", 
            "text", 
            "Text shadow", 
            'custom CSS'
        );

        addOption(
            "side-buttons", 
            "sideBtnBorder", 
            "text", 
            "Border", 
            'custom CSS'
        );

        addOption(
            "current-page", 
            "currentPagePositionV", 
            "dropdown", 
            'Current page display vertical position',
            "Vertical position", ["top", "bottom"]
        );

        addOption(
            "current-page", 
            "currentPagePositionH", 
            "dropdown", 
            "Horizontal position",
            'Current page display horizontal position',
            ["left", "right"]
        );

        addOption(
            "current-page", 
            "currentPageMarginV", 
            "text", 
            "Vertical margin", 
            'between 0 and 10'
        );

        addOption(
            "current-page", 
            "currentPageMarginH", 
            "text", 
            "Horizontal margin", 
            'between 0 and 10'
        );

        addOption(
            "ui", 
            "layout", 
            "dropdown", 
            "UI Layout", 
            'select one of premade UI layouts',
            ["1", "2", "3", "4"]
        );

        addOption(
            "ui", 
            "skin", 
            "dropdown", 
            "Skin", 
            'select one of premade skins',
            ["light", "dark", "gradient"]
        );

        addOption(
            "skin", 
            "skinColor", 
            "color", 
            "Color", 
            'global UI color, CSS value'
        );
        
        addOption(
            "ui", 
            "icons", 
            "dropdown", 
            "Icon set", 
            'choose Font Awesome or Material icons',
            ["font awesome", "material"]
        );

        addOption(
            "skin", 
            "skinBackground", 
            "color", 
            "Background color", 
            'global UI background color, CSS value'
        );

        

        addOption(
            "menu-floating", 
            "floatingBtnColor", 
            "color", 
            "Color", 
            'CSS value'
        );

        addOption(
            "menu-floating", 
            "floatingBtnBackground", 
            "color", 
            "Background color", 
            'CSS value'
        );

        addOption(
            "menu-floating", 
            "floatingBtnColorHover", 
            "color", 
            "Hover color", 
            'CSS value'
        );

        addOption(
            "menu-floating", 
            "floatingBtnBackgroundHover", 
            "color", 
            "Hover background color", 
            'CSS value'
        );

        addOption(
            "menu-floating", 
            "floatingBtnSize", 
            "text", 
            "Size"
        );

        addOption(
            "menu-floating", 
            "floatingBtnRadius", 
            "text", 
            "Radius"
        );

        addOption(
            "menu-floating", 
            "floatingBtnBorder", 
            "text", 
            "Border",
            "CSS value"
        );

        addOption(
            "close-button", 
            "btnClose[color]", 
            "color", 
            "Color",
            "CSS value"
        );

        addOption(
            "close-button", 
            "btnClose[background]", 
            "color", 
            "Background color",
            "CSS value"
        );

        addOption(
            "close-button", 
            "btnClose[colorHover]", 
            "color", 
            "Hover color",
            "CSS value"
        );

        addOption(
            "close-button", 
            "btnClose[backgroundHover]", 
            "color", 
            "Hover background color",
            "CSS value"
        );

        addOption(
            "close-button", 
            "btnClose[size]", 
            "text", 
            "Size",
            "px"
        );

        addOption(
            "close-button", 
            "btnClose[border]", 
            "text", 
            "Border",
            "CSS value"
        );

        addOption(
            "close-button", 
            "btnClose[radius]", 
            "text", 
            "Radius",
            "px"
        );

        addOption(
            "sidebar", 
            "sideMenuOverBook", 
            "checkbox", 
            "Over book layer"
        );

        addOption(
            "sidebar", 
            "sideMenuOverMenu", 
            "checkbox", 
            "Over bottom menu"
        );

        addOption(
            "sidebar", 
            "sideMenuOverMenu2", 
            "checkbox", 
            "Over top menu"
        );

        addOption(
            "bg", 
            "backgroundColor", 
            "color", 
            "Color",
            'CSS value, example #333 or rgba(0,0,0,0.5)'
        );

        addOption(
            "bg", 
            "backgroundPattern", 
            "selectImage", 
            "Image pattern (repeat)", 
            'Flipbook container background pattern'
        );

        addOption(
            "bg", 
            "backgroundImage", 
            "selectImage", 
            "Image", 
            'Flipbook container background image'
        );

        addOption(
            "bg", 
            "backgroundTransparent", 
            "checkbox", 
            "Transparent", 
            'Flipbook container will have transparent background'
        );


        //translate

        addOption(
            "translate", 
            "strings[print]", 
            "text", 
            "Print"
        );

        addOption(
            "translate", 
            "strings[printLeftPage]", 
            "text", 
            "Print left page"
        );

        addOption(
            "translate", 
            "strings[printRightPage]", 
            "text", 
            "Print right page"
        );

        addOption(
            "translate", 
            "strings[printCurrentPage]", 
            "text", 
            "Print current page"
        );

        addOption(
            "translate", 
            "strings[printAllPages]", 
            "text", 
            "Print all pages"
        );

        addOption(
            "translate", 
            "strings[download]", 
            "text", 
            "Download"
        );

        addOption(
            "translate", 
            "strings[downloadLeftPage]", 
            "text", 
            "Download left page"
        );

        addOption(
            "translate", 
            "strings[downloadRightPage]", 
            "text", 
            "Download right page"
        );

        addOption(
            "translate", 
            "strings[downloadCurrentPage]", 
            "text", 
            "Download current page"
        );

        addOption(
            "translate", 
            "strings[downloadAllPages]", 
            "text", 
            "Download all pages"
        );

        addOption(
            "translate", 
            "strings[bookmarks]", 
            "text", 
            "Bookmarks"
        );

        addOption(
            "translate", 
            "strings[bookmarkLeftPage]", 
            "text", 
            "Bookmark left page"
        );

        addOption(
            "translate", 
            "strings[bookmarkRightPage]", 
            "text", 
            "Bookmark right page"
        );

        addOption(
            "translate", 
            "strings[bookmarkCurrentPage]", 
            "text", 
            "Bookmark current page"
        );

        addOption(
            "translate", 
            "strings[search]", 
            "text", 
            "Search"
        );

        addOption(
            "translate", 
            "strings[findInDocument]", 
            "text", 
            "Find in document"
        );

        addOption(
            "translate", 
            "strings[pagesFoundContaining]", 
            "text", 
            "pages found containing"
        );

        addOption(
            "translate", 
            "strings[thumbnails]", 
            "text", 
            "Thumbnails"
        );

        addOption(
            "translate", 
            "strings[tableOfContent]", 
            "text", 
            "Table of Contents"
        );

        addOption(
            "translate", 
            "strings[share]", 
            "text", 
            "Share"
        );

        addOption(
            "translate", 
            "strings[pressEscToClose]", 
            "text", 
            "Press ESC to close"
        );

        setOptionValue('pdfUrl', options.pdfUrl)

        $('input.alpha-color-picker').alphaColorPicker()

        var ui_layouts = {
            'default':{

                menuOverBook: false,
                menuFloating: false,
                menuBackground: '',
                menuShadow: '',
                menuMargin: 0,
                menuPadding: 0,
                menuTransparent:false,

                menu2OverBook:true,
                menu2Floating: false,
                menu2Background:'',
                menu2Shadow: '',
                menu2Margin: 0,
                menu2Padding: 0,
                menu2Transparent:true,

                btnMargin:2,
                sideMenuOverMenu:false,
                sideMenuOverMenu2:true,

                currentPage:{hAlign:'left', vAlign:'top'},
                btnAutoplay:{hAlign:'center', vAlign:'bottom'},
                btnSound:{hAlign:'center', vAlign:'bottom'},
                btnExpand:{hAlign:'center', vAlign:'bottom'},
                btnZoomIn:{hAlign:'center', vAlign:'bottom'},
                btnZoomOut:{hAlign:'center', vAlign:'bottom'},
                btnSearch:{hAlign:'center', vAlign:'bottom'},
                btnBookmark:{hAlign:'center', vAlign:'bottom'},
                btnToc:{hAlign:'center', vAlign:'bottom'},
                btnThumbs:{hAlign:'center', vAlign:'bottom'},
                btnShare:{hAlign:'center', vAlign:'bottom'},
                btnPrint:{hAlign:'center', vAlign:'bottom'},
                btnDownloadPages:{hAlign:'center', vAlign:'bottom'},
                btnDownloadPdf:{hAlign:'center', vAlign:'bottom'},
                btnSelect:{hAlign:'center', vAlign:'bottom'},
            },
            "1":{

            },
            "2":{ // bottom 2
                currentPage:{vAlign:'bottom', hAlign:'center'},
                btnAutoplay:{hAlign:'left'},
                btnSound:{hAlign:'left'},
                btnExpand:{hAlign:'right'},
                btnZoomIn:{hAlign:'right'},
                btnZoomOut:{hAlign:'right'},
                btnSearch:{hAlign:'left'},
                btnBookmark:{hAlign:'left'},
                btnToc:{hAlign:'left'},
                btnThumbs:{hAlign:'left'},
                btnShare:{hAlign:'right'},
                btnPrint:{hAlign:'right'},
                btnDownloadPages:{hAlign:'right'},
                btnDownloadPdf:{hAlign:'right'},
                btnSelect:{hAlign:'right'}
            },
            "3":{ // top 
                menuTransparent:true,
                menu2Transparent:false,
                menu2OverBook:false,
                menu2Padding:5,
                btnMargin:5,
                currentPage:{vAlign:'top', hAlign:'center'},
                btnPrint:{vAlign:'top',hAlign:'right'},
                btnDownloadPdf:{vAlign:'top',hAlign:'right'},
                btnDownloadPages:{vAlign:'top',hAlign:'right'},
                btnThumbs:{vAlign:'top',hAlign:'left'},
                btnToc:{vAlign:'top',hAlign:'left'},
                btnBookmark:{vAlign:'top',hAlign:'left'},
                btnSearch:{vAlign:'top',hAlign:'left'},
                btnSelect:{vAlign:'top',hAlign:'right'},
                btnShare:{vAlign:'top',hAlign:'right'},
                btnAutoplay:{hAlign:'right'},
                btnExpand:{hAlign:'right'},
                btnZoomIn:{hAlign:'right'},
                btnZoomOut:{hAlign:'right'},
                btnSound:{hAlign:'right'},
                menuPadding:5
            },
            "4":{ // top 2
                menu2Transparent:false,
                menu2OverBook:false,
                sideMenuOverMenu2:false,
                currentPage:{vAlign:'top', hAlign:'center'},
                btnAutoplay:{vAlign:'top', hAlign:'left'},
                btnSound:{vAlign:'top', hAlign:'left'},
                btnExpand:{vAlign:'top', hAlign:'right'},
                btnZoomIn:{vAlign:'top', hAlign:'right'},
                btnZoomOut:{vAlign:'top', hAlign:'right'},
                btnSearch:{vAlign:'top', hAlign:'left'},
                btnBookmark:{vAlign:'top', hAlign:'left'},
                btnToc:{vAlign:'top', hAlign:'left'},
                btnThumbs:{vAlign:'top', hAlign:'left'},
                btnShare:{vAlign:'top', hAlign:'right'},
                btnPrint:{vAlign:'top', hAlign:'right'},
                btnDownloadPages:{vAlign:'top', hAlign:'right'},
                btnDownloadPdf:{vAlign:'top', hAlign:'right'},
                btnSelect:{vAlign:'top', hAlign:'right'}
                
            }
        }

        $('select[name="layout"]').change(function(){
            
            var name = this.value

            var defaults = ui_layouts['default']
            for (var key in defaults) {
                setOptionValue(key, defaults[key])
            }

            var obj = ui_layouts[name]
            for (var key in obj) {
                setOptionValue(key, obj[key])
            }

            setOptionValue('layout', name)
        })

        function previewPDFPages(){

            PDFJS.GlobalWorkerOptions.workerSrc = pluginDir + 'js/pdf.worker.min.js'

            var params = {
                cMapPacked: true,
                cMapUrl: "cmaps/",
                // disableAutoFetch: false,
                // disableCreateObjectURL: false,
                // disableFontFace: false,
                // disableRange: false,
                disableAutoFetch: true,
                disableStream: true,
                // isEvalSupported: true,
                // maxImageSize: -1,
                // pdfBug: false,
                // postMessageTransfers: true,
                url: options.pdfUrl
                // verbosity: 1
            }

            var loadingTask = PDFJS.getDocument(params)

            loadingTask.promise.then(function(pdf) {
                pdfDocument = pdf
                creatingPage = 1
                // loadPageFromPdf(pdf)

                createEmptyPages(pdf)

                pdf.getPage(1).then(function(page){
                    generateLightboxThumbnail(page)
                })
            })

        }

        function updateSaveBar() {

            if ((window.innerHeight + window.scrollY) >= (document.body.scrollHeight - 50)) {

                $("#r3d-save").removeClass("r3d-save-sticky")
                $("#r3d-save-holder").hide()

            } else {

                $("#r3d-save").addClass("r3d-save-sticky")
                $("#r3d-save-holder").show()

            }

        }

        $('#real3dflipbook-admin .nav-tab').click(function(e) {
            e.preventDefault()
            $('#real3dflipbook-admin .tab-active').hide()
            $('.nav-tab-active').removeClass('nav-tab-active')
            var a = jQuery(this).addClass('nav-tab-active')
            var id = "#" + a.attr('data-tab')
            jQuery(id).addClass('tab-active').fadeIn()
            window.location.hash = a.attr('data-tab').split("-")[1]
            updateSaveBar()

        })

        $('#real3dflipbook-admin .nav-tab').focus(function(e) {

            this.blur()
        })

        if(window.location.hash && $('.nav-tab[data-tab="tab-'+window.location.hash.split("#")[1]+'"]').length){
            $($('.nav-tab[data-tab="tab-'+window.location.hash.split("#")[1]+'"]')[0]).trigger('click')
        }else{
            $($('#real3dflipbook-admin .nav-tab')[0]).trigger('click')
        }


        function sortOptions(){

            function sortTocItems(tocItems, prefix){
                var prefix = prefix || 'tableOfContent'
                for (var i = 0; i < tocItems.length; i++) {
                    $item = $(tocItems[i])
                    $item.find('.toc-title').attr('name', prefix + '['+i+'][title]')
                    $item.find('.toc-page').attr('name', prefix + '['+i+'][page]')

                    var $items = $item.children('.toc-item-wrapper')
                    if($items.length > 0){
                        sortTocItems($items, prefix + '['+i+'][items]')

                    }
                    
                }

            }
  
            var tocItems = $("#toc-items").children(".toc-item-wrapper")
            sortTocItems(tocItems)
            
            var pages = $('#pages-container .page')

            for (var i = 0; i < pages.length; i++) {
                $item = $(pages[i])
                $item.find('.page-src').attr('name', 'pages['+i+'][src]')
                $item.find('.page-thumb').attr('name', 'pages['+i+'][thumb]')
                $item.find('.page-title').attr('name', 'pages['+i+'][title]')
                $item.find('.page-html-content').attr('name', 'pages['+i+'][htmlContent]')
                $item.find('.page-json').attr('name', 'pages['+i+'][json]')
            }
        }

        var $form = $('#real3dflipbook-options-form')
        var previewFlipbook

        if(options.status == "draft")
            $('.create-button').show()
        else
            $('.save-button').show()

        $('.flipbook-reset-defaults').click(function(e) {
            e.preventDefault()
            var inputs = $form.find('.global-option')
            inputs.each(function(){
                $(this).val('')
            })
        })

        function enableSave(){
            $('.save-button').prop('disabled', '').css('pointer-events', 'auto')
            $('.create-button').prop('disabled', '').css('pointer-events', 'auto')
        }

        function disableSave(){
            return
            $('.save-button').prop('disabled', 'disabled').css('pointer-events', 'none')
            $('.create-button').prop('disabled', 'disabled').css('pointer-events', 'none')
        }

        disableSave()

        $('.flipbook-preview').click(function(e) {

            e.preventDefault()

            sortOptions()

            $form.find('.spinner').css('visibility', 'visible')

            disableSave()
            // var data = $form.serialize() + '&action=r3d_preview'

            var data = 'action=r3d_preview'
            var arr = $form.serializeArray()

            arr.forEach( function(element, index) {

                if(element.value != '') data += ('&' + element.name + '=' + encodeURIComponent(element.value.trim()))

            });

            $.ajax({
                type: "POST",
                url: $form.attr('action'), //.replace('admin-ajax','admin'),
                data: data,
                success: function(response, textStatus, jqXHR) {

                    $form.find('.spinner').css('visibility', 'hidden')
                    $form.find('.save-button').prop('disabled', '').css('pointer-events', 'auto')

                    var o = $.parseJSON(response)
                    convertStrings(o)

                    o.assets = {
                        preloader: pluginDir + "images/preloader.jpg",
                        left: pluginDir + "images/left.png",
                        overlay: pluginDir + "images/overlay.jpg",
                        flipMp3: pluginDir + "mp3/turnPage.mp3",
                        shadowPng: pluginDir + "images/shadow.png"
                    };

                    o.pages = o.pages || []

                    for (var key in o.pages) {
                        o.pages[key].htmlContent = unescape(o.pages[key].htmlContent)
                    }

                    if (o.pages.length < 1 && !getOptionValue('pdfUrl')) {
                        alert('Flipbook has no pages!')
                        e.preventDefault()
                        return false
                    }

                    var lightboxElement = $('<p></p>')
                    o.lightBox = true
                    o.lightBoxOpened = true
                    // o.lightboxBackground = o.backgroundImage || o.background || o.backgroundColor
                    // o.lightboxBackground = 'rgba(0,0,0,.5)'
                    if(previewFlipbook)
                        previewFlipbook.dispose()

                    previewFlipbook = lightboxElement.flipBook(o)

                    $(window).trigger('resize')

                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    alert("Status: " + textStatus);
                    alert("Error: " + errorThrown);
                }
            })

        });

        $form.submit(function(e) {

            e.preventDefault();

            var pagesContainer = $("#pages-container");
            var pagesCount = pagesContainer.find(".page").length;

            if (pagesCount < 1 && !getOptionValue('pdfUrl')) {
                alert('Flipbook has no pages!')
                return false
            }

            sortOptions()

            $form.find('.spinner').css('visibility', 'visible')

            disableSave()

            var data = 'action=r3d_save'
            var arr = $form.serializeArray()

            arr.forEach( function(element, index) {

                if(element.value != '') data += ('&' + element.name + '=' + encodeURIComponent(element.value.trim()))

            });

            data += ('&bookId=' + options.id + '&security=' + options.security + '&id=' + options.id + '&date=' + encodeURIComponent(options.date) )

            if(options.status == "draft")
                data  += '&status=published';
                
            $.ajax({

                type: "POST",
                url: $form.attr('action'),
                data: data,

                success: function(data, textStatus, jqXHR) {

                    $('.spinner').css('visibility', 'hidden')
                    $('.create-button').hide()
                    $('.save-button').show()
                    enableSave()
                    $("#edit-flipbook-text").text("Edit Flipbook")

                    removeAllNotices()
                    if(options.status == "draft"){
                        addNotice("Flipbook published")
                        options.status = "published"
                   } else{
                        addNotice("Flipbook updated")
                   }

                },

                error: function(XMLHttpRequest, textStatus, errorThrown) {

                    alert("Status: " + textStatus);
                    alert("Error: " + errorThrown);

                }
            })

        })

        $(window).scroll(function() {
            updateSaveBar()
        })

        $(window).resize(function() {
            updateSaveBar()
        })

        updateSaveBar()

        function unsaved() { // $('.unsaved').show()
        }

        function addOptionGeneral(name, type, desc, help, values){
            addOption('general',name, type, desc, help, values)
        }

        function addOptionMobile(name, type, desc, help, values){
            addOption('mobile',name, type, desc, help, values)
        }

        function addOptionLightbox(name, type, desc, help, values){
            addOption('lightbox',name, type, desc, help, values)
        }

        function addOptionWebgl(name, type, desc, help, values){
            addOption('webgl',name, type, desc, help, values)
        }

        function addOption(section, name, type, desc, help, values){

            var defaultValue = options.globals[name];

            if(typeof defaultValue == 'undefined')
                defaultValue = ""

            if(name.indexOf("[") != -1){
                if(options.globals[name.split("[")[0]])
                    defaultValue = options.globals[name.split("[")[0]][name.split("[")[1].split("]")[0]]
            }

            var val = options[name]
            if (options[name.split("[")[0]] && name.indexOf("[") != -1 && typeof(options[name.split("[")[0]]) != 'undefined') {
                 val = options[name.split("[")[0]][name.split("[")[1].split("]")[0]]
            } 

            //val = val || defaultValue
            if(typeof val == 'string')
                val = r3d_stripslashes(val)

            var table = $("#flipbook-" + section + "-options");
            var tableBody = table.find('tbody');
            var row = $('<tr valign="top"  class="field-row"></tr>').appendTo(tableBody);
            var th = $('<th scope="row">' + desc + '</th>').appendTo(row);
            var td = $('<td></td>').appendTo(row);
            var elem

            switch (type) {

                case "text":
                    elem = $('<input type="text" name="' + name + '" placeholder="Global setting"/>').appendTo(td);
                    if(typeof val != 'undefined')
                        elem.attr('value', val);
                    elem.addClass("global-option")
                    break;

                case "color":
                    elem = $('<input type="text" name="' + name + '" class="alpha-color-picker" placeholder="Global setting"/>').appendTo(td);
                    elem.attr('value', val);
                    elem.addClass("global-option")
                    break;

                case "textarea":
                    elem = $('<textarea name="' + name + '" placeholder="Global setting"/>').appendTo(td);
                    if(typeof val != 'undefined')
                        elem.attr('value', val);
                    elem.addClass("global-option")
                    break;

                case "checkbox":
                    elem = $('<select name="' + name + '"></select>').appendTo(td);
                    var globalSetting = $('<option name="' + name + '" value="">Global setting</option>').appendTo(elem);
                    var enabled = $('<option name="' + name + '" value="true">Enabled</option>').appendTo(elem);
                    var disabled = $('<option name="' + name + '" value="false">Disabled</option>').appendTo(elem);

                    if(val == true) enabled.attr('selected', 'true');
                    else if(val == false) disabled.attr('selected', 'true');
                    else globalSetting.attr('selected', 'true');
                    elem.addClass("global-option")
                    break;

                case "selectImage":
                    elem = $('<input type="hidden" name="' + name + '"/><img name="' + name + '"><a class="select-image-button button-secondary button80" href="#">Select image</a><a class="remove-image-button button-secondary button80" href="#">Remove image</a>').appendTo(td);
                    $(elem[0]).attr("value", val);
                    $(elem[1]).attr("src", val);
                    break;

                case "selectFile":
                    elem = $('<input type="text" name="' + name + '"/><a class="select-image-button button-secondary button80" href="#">Select file</a>').appendTo(td);
                    elem.attr('value', val);
                    break;

                case "dropdown":
                
                    elem = $('<select name="' + name + '"></select>').appendTo(td);

                    var globalSetting = $('<option name="' + name + '" value="">Global setting</option>')
                    .appendTo(elem)
                    .attr('selected', 'true');

                    for (var i = 0; i < values.length; i++) {
                        var option = $('<option name="' + name + '" value="' + values[i] + '">' + values[i] + '</option>').appendTo(elem);
                        if (val == values[i]) {
                            option.attr('selected', 'true');
                        }
                    }
                    elem.addClass("global-option")
                    break;

            }

            if(type == 'checkbox')
                defaultValue = defaultValue ? 'Enabled' : 'Disabled'

            if(type != 'selectImage' && type != 'selectFile')
                $('<span class="default-setting">Global setting : <strong>'+defaultValue+'</strong></span>').appendTo(td)

             if(typeof help != 'undefined')
                var p = $('<p class="description">'+help+'</p>').appendTo(td)

        }

        if(options.pdfUrl)
            previewPDFPages()

        else if(options.pages && options.pages.length){

            for (var i = 0; i < options.pages.length; i++) {
                var page = options.pages[i];
                var pagesContainer = $("#pages-container");
                var pageItem = createPageHtml(i, page);
                pageItem.appendTo(pagesContainer);

            }
        }

        $('.page-delete').show()
        // $('.replace-page').show()

        $('.page').click(function(e) {
            expandPage($(this).attr("id"))
        })

        generateLightboxThumbnail()

        if (options.socialShare == null)
            options.socialShare = [];

        for (var i = 0; i < options.socialShare.length; i++) {
            var share = options.socialShare[i];
            var shareContainer = $("#share-container");
            var shareItem = createShareHtml(i, share.name, share.icon, share.url, share.target);
            shareItem.appendTo(shareContainer);

        }

        if (options.tableOfContent == null)
            options.tableOfContent = [];

        for (var i = 0; i < options.tableOfContent.length; i++) {

            var item = options.tableOfContent[i];
            var tocContainer = $("#toc-items");
            var tocItem = createTocItem(item.title, item.page, item.items, item.dest);
            tocItem.appendTo(tocContainer);
        }

        $(".ui-sortable").sortable({

            update:  function (event, ui) {
                updatePageOrder()
            }
        });

        addListeners();

        closeModal()

        $('#add-share-button').click(function(e) {

            e.preventDefault()

            var shareContainer = $("#share-container");
            var shareCount = shareContainer.find(".share").length;
            var shareItem = createShareHtml("socialShare[" + shareCount + "]", "", "", "", "", "_blank");
            shareItem.appendTo(shareContainer);

        });

        function addTocItem(){
            var index = $('.toc-item').length
            var $item = createTocItem().appendTo("#toc-items")
        }

        function saveCanvasToServer(canvas, name, onComplete){

            var dataurl = canvas.toDataURL("image/jpeg", .9)

            $.ajax({

                type: "POST",
                url: 'admin-ajax.php?page=real3d_flipbook_admin',
                data : {
                    action : 'r3d_save_page',
                    id: options.id,
                    page : name,
                    dataurl : dataurl,
                    security : options.security
                },

                success: function(response, textStatus, jqXHR) {

                    onComplete(r3d_stripslashes(response))

                },

                error: function(XMLHttpRequest, textStatus, errorThrown) {

                    // console.log(creatingPage, XMLHttpRequest, textStatus, errorThrown)

                }

            })

        }

        function selectJpgImages() {

            if (getOptionValue('pdfUrl')) {

                clearPages()
                options.pages = []

            }

            setOptionValue('pdfUrl', '')

            closeModal()

            var pdf_uploader = wp.media({
                title: 'Select images',
                button: {
                    text: 'Send to Flipbook'
                },
                library: { type: ['image' ]},
                multiple: true // Set this to true to allow multiple files to be selected
            }).on('select', function() {

                var arr = pdf_uploader.state().get('selection');
                var pages = new Array();

                for (var i = 0; i < arr.models.length; i++) {
                    var url = arr.models[i].attributes.sizes.full.url;
                    var thumb = (typeof(arr.models[i].attributes.sizes.medium) != "undefined") ? arr.models[i].attributes.sizes.medium.url : url;
                    var title = arr.models[i].attributes.title;
                    pages.push({
                        title: title,
                        src: url,
                        thumb: thumb
                    });
                }

                var pagesContainer = $("#pages-container");
                var pagesCount = pagesContainer.find(".page").length;
                
                for (var i = 0; i < pages.length; i++) {

                    var pageItem = createPageHtml(pagesCount+i, pages[i]);
                    
                    pageItem.appendTo(pagesContainer);
                    pageItem.hide().fadeIn();

                    pageItem.click(function(e) {
                        expandPage($(this).attr('id'))
                    })

                }

                $('.page-delete').show()
                // $('.replace-page').show()

                clearLightboxThumbnail()

                generateLightboxThumbnail()

            }).open();

        }

        /**
         * Create and show a dismissible admin notice
         */
        function addNotice( msg ) {
             
            var div = document.createElement( 'div' );
            $(div).addClass( 'notice notice-info' ).css('position', 'relative').fadeIn();
             
            var p = document.createElement( 'p' );
             
            $(p).text( msg ).appendTo($(div));
             
            var b = document.createElement( 'button' );
            $(b).attr( 'type', 'button' ).addClass( 'notice-dismiss' ).appendTo($(div));
         
            var bSpan = document.createElement( 'span' );
            $(bSpan).addClass( 'screen-reader-text' ).text( 'Dismiss this notice' ).appendTo($(b));
             
            var h1 = document.getElementsByTagName( 'h1' )[0];
            h1.parentNode.insertBefore( div, h1.nextSibling);
         
            $(b).click(function () {
                div.parentNode.removeChild( div );
            });
         
        }

        function removeAllNotices(){
            $(".notice").remove()
        }

        function clearPages() {
            $('.page').remove();
        }

        function clearLightboxThumbnail() {
            $("input[name='lightboxThumbnailUrl']").attr('value', '')
            $("img[name='lightboxThumbnailUrl']").attr('src', '')
        }

        function removePage(index) {
            $('#pages-container').find('#' + index).remove();

            closeModal()
        }

        function addListeners() {
            $('.submitdelete').click(function() {
                $(this).parent().parent().animate({
                    'opacity': 0
                }, 100).slideUp(100, function() {
                    $(this).remove();
                });
                // $('.unsaved').show()
            });

            $('.add-pdf-pages-button').click(function(e) {
                e.preventDefault();

                if ($('.page').length == 0 || confirm('All current pages will be lost. Are you sure?')) {

                    selectPdfFile(previewPDFPages)

                }
            })

            $('.delete-pages-button').click(function(e) {
                e.preventDefault();

                if ($('.page').length == 0 || confirm('Delete all pages. Are you sure?')) {

                    clearPages()

                    options.pages = []

                }
            })

            $('.select-image-button').click(function(e) {
                e.preventDefault();

                var $input = $(this).parent().find("input")
                var $img = $(this).parent().find("img")

                var pdf_uploader = wp.media({
                    title: 'Select file',
                    button: {
                        text: 'Select'
                    },
                    multiple: false // Set this to true to allow multiple files to be selected
                }).on('select', function() {

                    // $('.unsaved').show()
                    var arr = pdf_uploader.state().get('selection');
                    var selected = arr.models[0].attributes.url

                    $input.val(selected)
                    $img.attr('src', selected)
                }).open();
            })

            $('.generate-thumbnail-button').click(function(e){
                e.preventDefault()
                if(pdfDocument ){
                    
                    setOptionValue('lightboxThumbnailUrl', "")

                    $("input[name='lightboxThumbnailUrl']").attr('value', "")
                
                    pdfDocument.getPage(1).then(function(page){
                        generateLightboxThumbnail(page)
                   })
                }
                else
                    generateLightboxThumbnail()
                
                
            })

            $('.remove-image-button').click(function(e) {
                e.preventDefault();

                var $input = $(this).parent().find("input")
                var $img = $(this).parent().find("img")

                $input.val('')
                $img.attr('src', '')
            })

            $('.delete-all-pages-button').click(function(e) {

                e.preventDefault();

                clearPages()

            });

            $('.delete-page').click(function(e) {

                e.preventDefault();

                if (confirm('Delete page. Are you sure?')) {

                    removePage(editingPageIndex)
                }

            });

            $('.add-jpg-pages-button').click(function(e) {
                //open editor to select one or multiple images and create pages from them
                e.preventDefault();

                if (getOptionValue('pdfUrl')) {
                    if ($('.page').length == 0 || confirm('All current pages will be lost. Are you sure?')) {

                        selectJpgImages()

                    }
                } else
                    selectJpgImages()

            });

            $('.add-toc-item').click(function(e) {

                e.preventDefault();

                addTocItem()

            });

            $('.toc-delete-all').click(function(e) {

                e.preventDefault();

                if($(".toc-item-wrapper").length == 0 || confirm('Delete current table on contets?'))
                    $("#toc-items").empty();
                
            });

            $('.load-pdf-outline').click(function(e) {

                e.preventDefault();

                if(getOptionValue('pdfUrl') == ''){
                    alert("Only for PDF flipbook")
                    return
                }

                if($(".toc-item-wrapper").length == 0 || confirm('Delete current table on contets?')){

                    var tocContainer = $("#toc-items").empty();

                    pdfDocument.getOutline().then(function(outline){

                        if(outline && outline.length){

                            for (var i = 0; i < outline.length; i++) {

                                var item = outline[i];

                                var tocItem = createTocItem(item.title, item.page, item.items, item.dest);
                                tocItem.appendTo(tocContainer);

                            }

                        }

                    })

                }

            });

            $('.replace-page').click(function(event) {
                replacePage()
            });

        }

        function replacePage() {
            var pdf_uploader = wp.media({
                title: 'Select image',
                button: {
                    text: 'Select'
                },
                library: {
                        type: ['image' ]
                },
                multiple: false // Set this to true to allow multiple files to be selected
            }).on('select', function() {

                var selected = pdf_uploader.state().get('selection').models[0];

                var src = selected.attributes.sizes.full.url;
                var thumb = (typeof(selected.attributes.sizes.medium) != "undefined") ? selected.attributes.sizes.medium.url : null;
                
                setSrc(editingPageIndex, src)
                setThumb(editingPageIndex, thumb)
                setEditingPageThumb(src)

            }).open();
        }

        function selectPdfFile(onPdfSelected, pdf2jpg) {

            var pdf_uploader = wp.media({
                title: 'Select PDF',
                button: {
                    text: 'Send to Flipbook'
                },
                library: {
                        type: ['application/pdf' ]
                },
                multiple: false // Set this to true to allow multiple files to be selected
            }).on('select', function() {

                // $('.unsaved').show()
                var arr = pdf_uploader.state().get('selection');
                var pdfUrl = arr.models[0].attributes.url
                // $("input[name='pdfUrl']").attr('value', pdfUrl);

                setOptionValue('pdfUrl', pdfUrl)
                
                if(!pdf2jpg){
                        setOptionValue('type', 'pdf')
                }

                if(!pdf2jpg || getOptionValue("pdfUrl")){
                        clearPages()
                        clearLightboxThumbnail()

                        options.pages = []
                }

                closeModal()
                        
                $('#pages-container').removeClass('ui-sortable')

                onPdfSelected(pdfUrl)

            }).open();
        }

        function createTocItem(title, page, items, dest) {

            if (title == 'undefined' || typeof(title) == 'undefined')
                title = ''
            title = r3d_stripslashes(title)

            if (page == 'undefined' || typeof(page) == 'undefined')
                page = ''
            
            var $itemWrapper = $('<div class="toc-item-wrapper">')
            // var $toggle = $('<span>+</span>').appendTo($itemWrapper)
            var $item = $('<div class="toc-item"><input type="text" class="toc-title" placeholder="Title" value="'+title+'"></input><span> : </span><input type="number" placeholder="Page number" class="toc-page" value="'+page+'"></input></div>').appendTo($itemWrapper)
            
            if(pdfDocument && dest){
                pdfDocument.getPageIndex(dest[0] || dest).then(function(index){
                    $item.children('.toc-page').val(index + 1)
                })
            }

            var $controls = $('<div>').addClass('toc-controls').appendTo($item)
            // var $btnAddSubItem = $('<button type="button" class="button-secondary toc-add-sub">Add sub item</button>')
            var $btnAddSubItem = $('<span>').addClass('toc-add-sub fa fa-plus').attr('title','Add sub item')
            .appendTo($controls)
            .click(function(){
                // console.log(this)
                var $subItem = createTocItem().appendTo($itemWrapper).addClass('toc-sub-item')
                // var $toggle = $('<span>').addClass('toc-toggle fa fa-caret-right').prependTo($subItem)
            })
            var $btnDelete = $('<span>').addClass('fa fa-times toc-delete').attr('title', 'Delete itemm')
            .appendTo($controls)
            .click(function(){
                if($itemWrapper.find('.toc-item-wrapper').length == 0 || confirm('Delete item and all children') ){

                    $itemWrapper.fadeOut(300,function(){$(this).remove()})
                }
            })

            if(items){
                for (var i = 0; i < items.length; i++) {
                    var item = items[i]
                    var $subItem = createTocItem(item.title, item.page, item.items, item.dest).appendTo($itemWrapper).addClass('toc-sub-item')
                }
            }

            return $itemWrapper.fadeIn()

        }

        function createPageHtml(id, page) {

            var title = page.title || ''
            var src = page.src
            var thumb = page.thumb
            var json = page.json || ''
            var htmlContent = page.htmlContent || ''
            var pageNumber = id + 1

            htmlContent = unescape(htmlContent);

            title = r3d_stripslashes(title)

            var $page = $(
                '<li id="' + id + '"class="page">' + 
                    '<div class="page-img"><img src="' + thumb + '"></div>' + 
                    '<span class="page-number">' + pageNumber + '</span>' + 
                    '<div style="display:block;">' + 
                        '<input class="page-title" type="hidden" placeholder="title" value="' + title + '" readonly/>' + 
                        '<input class="page-src" type="hidden" placeholder="src" value="' + src + '" readonly/>' + 
                        '<input class="page-thumb" type="hidden" placeholder="thumb" value="' + thumb + '" readonly/>' + 
                        '<input class="page-json" type="hidden" placeholder="thumb" value="' + json + '" readonly/>' + 
                        '<input class="page-html-content" type="hidden" placeholder="htmlContent" value="' + escape(htmlContent) + '"readonly/>' + 
                    '</div>' + 
                '</li>');

            var $img = $page.find('img')

            $img.bind('load', function() {
                var h = $(this).height()
                var w = $(this).width()
                var ch = $page.find('.page-img').height()
                // res.find('.page-img').css('width', ch * w / h + 'px')
            })
           
            var $del = $('<span>X</span>').addClass('page-delete').appendTo($page).click(function(e){
                
                e.preventDefault();
                e.stopPropagation()

                var pageId = Number($(this).parent().attr('id'))

                if (confirm('Delete page ' + (pageId + 1) + '. Are you sure?')) {

                    removePage(pageId)

                    updatePageOrder()
                }

            })

            var $edit = $('<button>Edit</button>').addClass('page-edit').appendTo($page)

            $page.hover(
                function() {
                    $del.addClass('page-delete-visible')
                    $edit.addClass('page-edit-visible')
                }, 
                function() {
                    $del.removeClass('page-delete-visible')
                    $edit.removeClass('page-edit-visible')
                }
            );


            return $page
        }

        function updatePageOrder(){
            $('.page').each(function(index, page){
                $(page).attr('id', index).find('.page-number').text(index + 1)

            })
        }

        function createShareHtml(prefix, id, name, icon, url, target) {

            if (typeof(target) == 'undefined' || target != "_self")
                target = "_blank";

            var markup = $('<div id="' + id + '"class="share">' + '<h4>Share button ' + id + '</h4>' + '<div class="tabs settings-area">' + '<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all" role="tablist">' + '<li><a href="#tabs-1">Icon name</a></li>' + '<li><a href="#tabs-2">Icon css class</a></li>' + '<li><a href="#tabs-3">Link</a></li>' + '<li><a href="#tabs-4">Target</a></li>' + '</ul>' + '<div id="tabs-1" class="ui-tabs-panel ui-widget-content ui-corner-bottom">' + '<div class="field-row">' + '<input id="page-title" name="' + prefix + '[name]" type="text" placeholder="Enter icon name" value="' + name + '" />' + '</div>' + '</div>' + '<div id="tabs-2" class="ui-tabs-panel ui-widget-content ui-corner-bottom">' + '<div class="field-row">' + '<input id="image-path" name="' + prefix + '[icon]" type="text" placeholder="Enter icon CSS class" value="' + icon + '" />' + '</div>' + '</div>' + '<div id="tabs-3" class="ui-tabs-panel ui-widget-content ui-corner-bottom">' + '<div class="field-row">' + '<input id="image-path" name="' + prefix + '[url]" type="text" placeholder="Enter link" value="' + url + '" />' + '</div>' + '</div>' + '<div id="tabs-4" class="ui-tabs-panel ui-widget-content ui-corner-bottom">' + '<div class="field-row">' // + '<input id="image-path" name="'+prefix+'[target]" type="text" placeholder="Enter link" value="'+target+'" />'

                +
                '<select id="social-share" name="' + prefix + '[target]">' // + '<option name="'+prefix+'[target]" value="_self">_self</option>'
                // + '<option name="'+prefix+'[target]" value="_blank">_blank</option>'
                +
                '</select>' + '</div>' + '</div>' + '<div class="submitbox deletediv"><span class="submitdelete deletion">x</span></div>' + '</div>' + '</div>' + '</div>');

            var values = ["_self", "_blank"];
            var select = markup.find('select');

            for (var i = 0; i < values.length; i++) {
                var option = $('<option name="' + prefix + '[target]" value="' + values[i] + '">' + values[i] + '</option>').appendTo(select);
                if (typeof(options["socialShare"][id]) != 'undefined') {
                    if (options["socialShare"][id]["target"] == values[i]) {
                        option.attr('selected', 'true');
                    }
                }
            }

            return markup;
        }

        function getOptionValue(optionName, type) {
            var type = type || 'input'
            var opiton = $(type + "[name='" + optionName + "']")
            return opiton.attr('value') || options.globals[optionName];
        }

        function getOption(optionName, type) {
            var type = type || 'input'
            var opiton = $(type + "[name='" + optionName + "']")
            return opiton;
        }

        function onModeChange() {
            if (getOptionValue('mode', 'select') == 'lightbox')
                $('[href="#tab-lightbox"]').closest('li').show();
            else
                $('[href="#tab-lightbox"]').closest('li').hide();
        }

        getOption('mode', 'select').change(onModeChange)
        onModeChange()

        function onViewModeChange() {
            if (getOptionValue('viewMode', 'select') == 'webgl')
                $('[href="#tab-webgl"]').closest('li').show();
            else
                $('[href="#tab-webgl"]').closest('li').hide();
        }

        getOption('viewMode', 'select').change(onViewModeChange)
        onViewModeChange()

        function setOptionValue(optionName, value, type) {

            options[optionName] = value

            if(typeof value == 'object'){
                for(var key in value){
                    setOptionValue(optionName + '[' + key + ']', value[key])
                }
                return null
            }
            var type = type || 'input'
            var $elem = $(type + "[name='" + optionName + "']").attr('value', value).prop('checked', value);

            if(value === true) value = "true"
            else if(value === false) value = "false"

            $("select[name='" + optionName + "']").val(value);
            $("input[name='" + optionName + "']").val(value).trigger("keyup");

            return $elem
        }

        function setColorOptionValue(optionName, value) {
            var $elem = $("input[name='" + optionName + "']").attr('value', value);
            $elem.wpColorPicker()
            return $elem
        }

        function renderPdfPage(pdfPage, onComplete, height) {
            var context, scale, viewport, canvas, context, renderContext;

            viewport = pdfPage.getViewport({scale:1});
            scale = (height || 80) / viewport.height
            viewport = pdfPage.getViewport({scale:scale});
            canvas = document.createElement('canvas');
            context = canvas.getContext('2d');
            canvas.width = viewport.width
            canvas.height = viewport.height

            renderContext = {
                canvasContext: context,
                viewport: viewport,
                intent: 'display' // intent:'print'
            };

            pdfPage.cleanupAfterRender = true


            var renderTask = pdfPage.render(renderContext);
            renderTask.promise.then(function () {
                pdfPage.cleanup()
                onComplete(canvas)
            });

        }

        function generateLightboxThumbnail(pdfPage) {

            var thumb = $($('.page')[0]).find('.page-thumb').attr('value')
            var lightboxThumbnailUrl = $("input[name='lightboxThumbnailUrl']").attr('value')
            if (lightboxThumbnailUrl == "") {

                if (!pdfPage) {
                    $("input[name='lightboxThumbnailUrl']").attr('value', thumb)
                    $("img[name='lightboxThumbnailUrl']").attr('src', thumb)
                    enableSave()
                } else {
                    var height = getOptionValue('lightboxThumbnailHeight');
                    var viewport = pdfPage.getViewport({scale:1});
                    var scale = height / viewport.height
                    var viewport = pdfPage.getViewport({scale:scale});
                    var canvas = document.createElement('canvas');
                    var context = canvas.getContext('2d');
                    canvas.width = viewport.width
                    canvas.height = viewport.height

                    renderContext = {
                        canvasContext: context,
                        viewport: viewport,
                        intent: 'display' // intent:'print'
                    };

                    pdfPage.cleanupAfterRender = true

                    var renderTask = pdfPage.render(renderContext);
                    renderTask.promise.then(function () {
                        pdfPage.cleanup()

                        // var thumbUrl = canvas.toDataURL()
                        // $("input[name='lightboxThumbnailUrl']").attr('value', thumbUrl)
                        // $("img[name='lightboxThumbnailUrl']").attr('src', thumbUrl)
                        // enableSave()
                        // loadPageFromPdf(pdfDocument, 1)

                        // return

                        saveCanvasToServer(canvas, "thumb", function(thumbUrl){
                            var d = new Date()
                            //no cache
                            thumbUrl += ('?' + d.getTime())
                            $("input[name='lightboxThumbnailUrl']").attr('value', thumbUrl)
                            $("img[name='lightboxThumbnailUrl']").attr('src', thumbUrl)
                            enableSave()
                            loadPageFromPdf(pdfDocument, 1)
                        })
                        
                    });

                }   
            }else{
                enableSave()
                if(pdfPage) loadPageFromPdf(pdfDocument, 1)
            }

        }

        var editingPageIndex;

        function expandPage(index) {

            editingPageIndex = Number(index)

            $editPageModal.show()
            $modalBackdrop.show()

            $editPageModal.find('h1').text('Edit page ' + (editingPageIndex + 1))

            var src = getSrc(editingPageIndex)
            
            if (src) {
                $('.delete-page').show()
                // $('.replace-page').show()
                $('#edit-page-img').show()
                setEditingPageThumb(src)
            } else if(options.pdfUrl){
                pdfDocument.getPage(editingPageIndex + 1).then(function(pdfPage){
                    renderPdfPage(pdfPage, function(canvas){
                        var src = canvas.toDataURL()
                        setEditingPageThumb(src)
                    }, 1000)
                })
            }else {
                $('.delete-page').hide()
                // $('.replace-page').hide()
                $('#edit-page-img').hide()
            }

            setEditingPageTitle(getTitle(index))
            setEditingPageHtmlContent(unescape(getHtmlContent(index)))

        }

        $editPageModal.find('.left').click(function(){
            var numPages = $('#pages-container .page').length
            editingPageIndex = (Number(editingPageIndex) - 1 + numPages) % numPages
            expandPage(editingPageIndex)
        })

        $editPageModal.find('.right').click(function(){
            var numPages = $('#pages-container .page').length
            editingPageIndex = (Number(editingPageIndex) + 1 + numPages) % numPages
            expandPage(editingPageIndex)
        })

        function setEditingPageTitle(title) {
            $('#edit-page-title').val(title)
        }

        function getEditingPageTitle() {
            return $('#edit-page-title').val()
        }

        function setEditingPageSrc(val) {
            $('#edit-page-src').val(val)
        }

        function getEditingPageSrc() {
            return $('#edit-page-src').val()
        }

        function setEditingPageThumb(val) {
            // $('#edit-page-thumb').val(val)
            $('#edit-page-img').attr('src', val)
        }

        function getEditingPageThumb() {
            return $('#edit-page-thumb').val()
        }

        function setEditingPageHtmlContent(htmlContent) {
            $('#edit-page-html-content').val(htmlContent)
        }

        function getEditingPageHtmlContent() {
            return $('#edit-page-html-content').val()
        }

        function getPage(index) {
            return $($('#pages-container li')[index])
        }

        function getTitle(index) {
            return getPage(index).find('.page-title').val()
        }

        function setTitle(index, val) {
            getPage(index).find('.page-title').val(val)
        }

        function getSrc(index) {
            return getPage(index).find('.page-src').val()
        }

        function setSrc(index, val) {
            getPage(index).find('.page-src').val(val)
        }

        function getThumb(index) {
            return getPage(index).find('.page-thumb').val()
        }

        function setThumb(index, val) {
            getPage(index).find('.page-thumb').val(val)
            getPage(index).find('.page-img').find('img').attr('src', val)
            // getPage(index).find('.page-img').css('background', 'url("' + val + '")')
        }

        function getHtmlContent(index) {
            return getPage(index).find('.page-html-content').val()
        }

        function setHtmlContent(index, val) {

            getPage(index).find('.page-html-content').val(val)
        }

        $('#edit-page-title').bind('change keyup paste', function() {

            setTitle(editingPageIndex, $(this).val())

        })

        $('#edit-page-html-content').bind('change keyup paste', function() {

            setHtmlContent(editingPageIndex, escape($(this).val()))

        })

        $('.preview-pdf-pages').click(function(e) {
            e.preventDefault();
            
            if(pdfDocument && getOptionValue('pdfUrl') != ''){
                // createEmptyPages(pdfDocument)
                loadPageFromPdf(pdfDocument, 1)
            }
                
        })

        function loadPageFromPdf(pdf, pageIndex) {

            // $(".pdf-to-jpg-info").text("PDF pages preview")

            if (!pdf.pageScale) {
                pdf.getPage(1).then(function(page) {
                    var v = page.getViewport({scale:1})

                    pdf.pageScale = v.height / 150
                    pdf.thumbScale = v.height / 150

                    loadPageFromPdf(pdf, pageIndex)

                })
                return
            }

            pdf.getPage(creatingPage).then(function getPage(page) {

                var pagesContainer = $("#pages-container");

                renderPdfPage(page, function(canvas) {

                    var pageItem = $("#pages-container").find("#" + (creatingPage - 1))
                    var thumb = canvas.toDataURL()

                    pageItem.find('.page-img').find('img').attr('src', thumb)

                    if (creatingPage < pdf._pdfInfo.numPages) {

                        creatingPage++
                        loadPageFromPdf(pdf)

                    }

                })

            })
        }

        function createEmptyPages(pdf) {
            
            var numPages = pdf._pdfInfo.numPages
            var pagesContainer = $("#pages-container");

            pdf.getPage(1).then(function(page) {
                var v = page.getViewport({scale:1})

                for (var i = 0; i < numPages; i++) {
                    var p = options.pages && options.pages[i] ? options.pages[i] : null
                    var title = p && p.title ? p.title : ''
                    var src = p && p.src ? p.src : ''
                    var thumb = p && p.thumb ? p.thumb : ''
                    var htmlContent = p && p.htmlContent ? p.htmlContent : ''
                    var page = {title: title, src: src, thumb: thumb, htmlContent: htmlContent}
                    var pageItem = createPageHtml(i, page);
                    //pageItem.find('.page-img').css('min-width', 80 * v.width / v.height + 'px')
                    //pageItem.find('.page-img').empty()
                    pageItem.appendTo(pagesContainer).click(function(e) {
                        expandPage($(this).attr("id"))
                    })
                }

                $('.page-delete').hide()
                // $('.replace-page').hide()

            })

        }

    });
})(jQuery);

function r3d_stripslashes(str) {
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Ates Goral (http://magnetiq.com)
    // +      fixed by: Mick@el
    // +   improved by: marrtins
    // +   bugfixed by: Onno Marsman
    // +   improved by: rezna
    // +   input by: Rick Waldron
    // +   reimplemented by: Brett Zamir (http://brett-zamir.me)
    // +   input by: Brant Messenger (http://www.brantmessenger.com/)
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: stripslashes('Kevin\'s code');
    // *     returns 1: "Kevin's code"
    // *     example 2: stripslashes('Kevin\\\'s code');
    // *     returns 2: "Kevin\'s code"
    return (str + '').replace(/\\(.?)/g, function(s, n1) {
        switch (n1) {
            case '\\':
                return '\\';
            case '0':
                return '\u0000';
            case '':
                return '';
            default:
                return n1;
        }
    });
}