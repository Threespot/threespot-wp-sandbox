(function($) {

    $(document).ready(function() {

        $('#real3dflipbook-admin').show()

        $('.creating-page').hide()

        postboxes.save_state = function(){
            return;
        };
        postboxes.save_order = function(){
            return;
        };

        if(postboxes.handle_click && !postboxes.handle_click.guid)
            postboxes.add_postbox_toggles();

        options = $.parseJSON(window.options)

        function convertStrings(obj) {

            $.each(obj, function(key, value) {
                // console.log(key + ": " + options[key]);
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

        var title = 'General settings'

        $("#edit-flipbook-text").text(title)

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
            'initial book zoom, recommended between 0.8 and 1'
        );
        
        addOptionGeneral( 
            "zoomStep",         
            "text", 
            "Zoom step", 
            'between 1.1 and 4'
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
            'display one page at a time'
        );

        addOptionGeneral( 
            "pageFlipDuration", 
            "text", 
            "Flip duration", 
            'duration of flip animation, recommended between 0.5 and 2'
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
            'open flipbook at this page at start'
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
            'switching from two page layout to one page layout if flipbook width is below certain treshold'
        );
        
        addOptionGeneral( 
            "responsiveViewTreshold", 
            "text", 
            "Responsive view treshold", 
            'treshold (screen width in px) for responsive view feature'
        );

        addOptionGeneral(
            "pageTextureSize",  
            "text", 
            "PDF page size (full)", 
            'height of rendered PDF pages in px'
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
            'container width / height ratio, recommended between 1 and 2'
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
            'flipping from right to left'
        );
        
        addOptionGeneral( 
            "thumbSize",
            "text", 
            "Thumbnail size", 
            'thumbnail height for thumbnails view'
        );

        addOptionGeneral( 
            "logoImg",
            "selectImage", 
            "Logo image",
            'logo image that will be displayed inside the flipbook container'
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
            'custom CSS for logo'
        );

        addOptionGeneral( 
            "menuSelector",
            "text",
            "Menu CSS selector",
            'example "#menu" or ".navbar". Used with mode "fullscreen" so the flipbook will be resized correctly below the menu'
        );

        addOptionGeneral( 
            "zIndex", 
            "text", 
            "Container z-index",
            'set z-index of flipbook container'
        );

        addOptionGeneral( 
            'preloaderText', 
            'text', 
            'Preloader text', 
            'text that will be displayed under the preloader spinner'
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
            'for PDF flipbook'
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
            'height of rendered PDF pages in px'
        );

        addOptionMobile( 
            "pageTextureSizeMobileSmall",  
            "text", 
            "PDF page size (small)", 
            'height of rendered PDF pages in px'
        );

        addOptionMobile( 
            "aspectRatioMobile", 
            "text", 
            "Container responsive ratio", 
            'container width / height ratio, recommended between 1 and 2'
        );

        addOptionMobile( 
            "singlePageModeIfMobile", 
            "checkbox", 
            "Single page view", 
            'display one page at a time'
        );

        addOptionMobile( 
            "pdfBrowserViewerIfMobile",
            "checkbox", 
            "Use default device PDF viewer instead of flipbook", 
            'opens PDF file directly in browser, instead of flipbook'
        );

        addOptionMobile(
            "pdfBrowserViewerFullscreen", 
            "checkbox", 
            "Default device PDF viewer fullscreen"
        );

        addOptionMobile( 
            "pdfBrowserViewerFullscreenTarget",
            "dropdown", 
            "Default device PDF viewer target", 
            'opens PDF file in new tab or in same tab',
            ["_self", "_blank"]
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
            "Button View PDF"
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
            "lightboxThumbnailHeight", 
            "text", 
            "Thumbnail height", 
            'height of thumbnail that will be generated from PDF'
        );

        addOptionLightbox( 
            "lightboxThumbnailUrlCSS", 
            "textarea", 
            "Thumbnail CSS",  
            'custom CSS'
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
            'custom CSS'
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
            'lightbox will open automatically on page load'
        );

        addOptionLightbox( 
            "lightBoxFullscreen", 
            "checkbox", 
            "Openes in fullscreen", 
            'opening the lightbox will put lightbox element to real fullscreen'
        );

        addOptionLightbox( 
            "lightboxCloseOnClick", 
            "checkbox", 
            "Closes when clicked outside the book", 
            'close lightbox if clicked on the overlay but outside the book'
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
            'lightbox overlay vertical margin'
        );

        addOptionLightbox   ( 
            "lightboxMarginH", 
            "text", 
            "Horizontal margin",
            'lightbox overlay horizontal margin'
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
                ['', 'bottom','top']
            );

            addOption(
                name, 
                name+"[hAlign]", 
                "dropdown", 
                 "Horizontal align", 
                "",
                ['', 'center','right', 'left' ]
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
        // addMenuButton("btnClose", true)

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
            "ui", 
            "useFontAwesome5", 
            "checkbox", 
            "Use Font Awesome 5", 
            'Disable to use default theme Font Awesome'
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

            updateSaveBar()

        })

        $('#real3dflipbook-admin .nav-tab').focus(function(e) {

            this.blur()
        })

        $($('#real3dflipbook-admin .nav-tab')[0]).trigger('click')

        var $form = $('#real3dflipbook-options-form')

        $form.submit(function(e) {

            e.preventDefault();

            $form.find('.spinner').css('visibility', 'visible')

            $form.find('.save-button').prop('disabled', 'disabled').css('pointer-events', 'none')
            $form.find('.create-button').prop('disabled', 'disabled').css('pointer-events', 'none')
            
            var data = 'action=r3d_save_general&security=' + window.r3d_nonce
            var arr = $form.serializeArray()

            arr.forEach( function(element, index) {

                if(element.value != '') data += ('&' + element.name + '=' + encodeURIComponent(element.value.trim()))

            });
   
            $.ajax({

                type: "POST",
                url: $form.attr('action'), //.replace('admin-ajax','admin'),
                data: data,

                success: function(data, textStatus, jqXHR) {

                    $('.spinner').css('visibility', 'hidden')
                    $('.save-button').prop('disabled', '').css('pointer-events', 'auto')
                    $('.create-button').hide()
                    $('.save-button').show()
                    $("#edit-flipbook-text").text("Edit Flipbook")

                    removeAllNotices()
                    addNotice("Settings updated")

                },

                error: function(XMLHttpRequest, textStatus, errorThrown) {

                    alert("Status: " + textStatus);
                    alert("Error: " + errorThrown);

                }
            })

        })

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

         $('.flipbook-reset-defaults').click(function(e){
            e.preventDefault()
            if(confirm("Reset Global settings?")){


               var data = 'action=r3d_reset_general&security=' + window.r3d_nonce 

                $.ajax({

                    type: "POST",
                    url: 'admin-ajax.php?page=real3d_flipbook_admin',
                    data: data,


                    success: function(data, textStatus, jqXHR) {

                        location.reload()

                    },

                    error: function(XMLHttpRequest, textStatus, errorThrown) {

                        alert("Status: " + textStatus);
                        alert("Error: " + errorThrown);

                    }
                })

            }
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

        



        // flipbook-options

        if (options.socialShare == null)
            options.socialShare = [];

        for (var i = 0; i < options.socialShare.length; i++) {
            var share = options.socialShare[i];
            var shareContainer = $("#share-container");
            var shareItem = createShareHtml(i, share.name, share.icon, share.url, share.target);
            shareItem.appendTo(shareContainer);

        }

        // $(".tabs").tabs();
        $(".ui-sortable").sortable();

        $('#add-share-button').click(function(e) {

            e.preventDefault()

            var shareContainer = $("#share-container");
            var shareCount = shareContainer.find(".share").length;
            var shareItem = createShareHtml("socialShare[" + shareCount + "]", "", "", "", "", "_blank");
            shareItem.appendTo(shareContainer);

        });

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

            var val = options[name]
            if (options[name.split("[")[0]] && name.indexOf("[") != -1 && typeof(options[name.split("[")[0]]) != 'undefined') {
                 val = options[name.split("[")[0]][name.split("[")[1].split("]")[0]]
            } 

            //val = val || defaultValue
            if(typeof val == 'strings')
                val = r3d_stripslashes(val)

            var table = $("#flipbook-" + section + "-options");
            var tableBody = table.find('tbody');
            var row = $('<tr valign="top"  class="field-row"></tr>').appendTo(tableBody);
            var th = $('<th scope="row">' + desc + '</th>').appendTo(row);
            var td = $('<td></td>').appendTo(row);
            var elem

            switch (type) {

                case "text":
                    elem = $('<input type="text" name="' + name + '"/>').appendTo(td);
                    if(typeof val != 'undefined')
                        elem.attr('value', val);
                    break;

                case "color":
                    elem = $('<input type="text" name="' + name + '" class="alpha-color-picker"/>').appendTo(td);
                    elem.attr('value', val);
                    break;

                case "textarea":
                    elem = $('<textarea name="' + name + '"/>').appendTo(td);
                    if(typeof val != 'undefined')
                        elem.attr('value', val);
                    break;

                case "checkbox":
                    elem = $('<select name="' + name + '"></select>').appendTo(td);
                    var enabled = $('<option name="' + name + '" value="true">Enabled</option>').appendTo(elem);
                    var disabled = $('<option name="' + name + '" value="false">Disabled</option>').appendTo(elem);

                    if(val == true) enabled.attr('selected', 'true');
                    else if(val == false) disabled.attr('selected', 'true');
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

                    for (var i = 0; i < values.length; i++) {
                        var option = $('<option name="' + name + '" value="' + values[i] + '">' + values[i] + '</option>').appendTo(elem);
                        if (val == values[i]) {
                            option.attr('selected', 'true');
                        }
                    }
                    break;

            }

             if(typeof help != 'undefined')
                var p = $('<p class="description">'+help+'</p>').appendTo(td)

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
            return opiton.attr('value');
        }

        function getOption(optionName, type) {
            var type = type || 'input'
            var opiton = $(type + "[name='" + optionName + "']")
            return opiton;
        }

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

        $('.remove-image-button').click(function(e) {
            e.preventDefault();

            var $input = $(this).parent().find("input")
            var $img = $(this).parent().find("img")

            $input.val('')
            $img.attr('src', '')
        })
        
        function setOptionValue(optionName, value, type) {

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
            $("input[name='" + optionName + "']").val(value).trigger('keyup');

            return $elem
        }

        function setColorOptionValue(optionName, value) {
            var $elem = $("input[name='" + optionName + "']").attr('value', value);
            $elem.wpColorPicker()
            return $elem
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