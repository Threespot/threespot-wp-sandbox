(function($){

    $(document).ready(function() {

        var $btnInsert = $('#r3d-insert-btn');
        var $btnSelectPDF = $('#r3d-select-pdf');
        var $btnSelectImages = $('#r3d-select-images');
        var $selectFlipbook = $('#r3d-select-flipbook');
        var $pdfUrlInput = $('#r3d-pdf-url');
        var $pagesContainer = $('.r3d-pages')
        var pages = [];
        var thumbs = [];

        $btnInsert.on('click', function() {
            generateShortcode()
        })

        function generateShortcode(){
            var selectedId = $('#r3d-select-flipbook option:selected').val();
            var pdfUrl = $pdfUrlInput.val()

            var shortcode = '[real3dflipbook'

            if(pdfUrl)
                shortcode += (' pdf="' + pdfUrl + '"')
            if(selectedId)
                shortcode += (' id="' + selectedId + '"')
            if(pages.length){
                shortcode += (' pages="' + pages.join(',') + '"')
                shortcode += (' thumbs="' + thumbs.join(',') + '"')
            }
            if($('#r3d-mode').val())
                shortcode += ' mode="'+ $('#r3d-mode').val() +'"';
            if($('#r3d-class').val())
                shortcode += ' lightboxcssclass="'+ $('#r3d-class').val() +'"';
            if($('#r3d-class').val() == 'no')
                shortcode += ' thumb=""';
            shortcode += ']'
            window.send_to_editor(shortcode);
            tb_remove();
        }

        $selectFlipbook.change(handleInsertFlipbookButton)

        $pdfUrlInput.change(function(){
            $pagesContainer.empty()
            pages = []
            thumbs = []
            handleInsertFlipbookButton()
        })

        function handleInsertFlipbookButton(){
            var selectedId = $('#r3d-select-flipbook option:selected').val();
            var pdfUrl = $pdfUrlInput.val()

            if(selectedId || pdfUrl || pages.length)
                $btnInsert.prop('disabled', '')
            else
                $btnInsert.prop('disabled', 'disabled')
        }

        $('.r3d-insert-flipbook-button').click(function(){
            setTimeout(function(){
                $('#TB_ajaxContent').addClass("r3d-TB_ajaxContent")
                $('#TB_window').addClass("r3d-TB_window")
            },0)
            
        })

        $btnSelectPDF.on('click', function() {

            var pdf_uploader = wp.media({
                title: 'Select PDF',
                button: {
                    text: 'Send to Flipbook'
                },
                library: {
                    type: ['application/pdf' ]
                },
                multiple: false
            }).on('select', function() {

                var arr = pdf_uploader.state().get('selection');
                var pdfUrl = arr.models[0].attributes.url

                $pdfUrlInput.val(pdfUrl).trigger('change')
                
            }).open();

        })

        $btnSelectImages.on('click', function() {

            var images_uploader = wp.media({
                title: 'Select images',
                button: {
                    text: 'Send to Flipbook'
                },
                library: { 
                    type: ['image' ]
                },
                multiple: true
            }).on('select', function() {

                var arr = images_uploader.state().get('selection');

                $pdfUrlInput.val('').trigger('change');

                $('<p>Pages</p>').appendTo($pagesContainer)

                for (var i = 0; i < arr.models.length; i++) {
                    var src = arr.models[i].attributes.sizes.full.url;
                    var thumb = (typeof(arr.models[i].attributes.sizes.medium) != "undefined") ? arr.models[i].attributes.sizes.medium.url : src;
                    pages.push(src);
                    thumbs.push(thumb);
                    $('<img>').attr('src', thumb).appendTo($pagesContainer).css({'height':'50px', 'margin': '5px'})
                }

                handleInsertFlipbookButton()
                
            }).open();

        })

        $( 'body' ).on( 'thickbox:removed', function(){
            $btnInsert.prop('disabled', 'disabled')
            $selectFlipbook.val('')
            $pdfUrlInput.val('')
            $('.r3d-setting').val('')
            $('#TB_ajaxContent').removeClass("r3d-TB_ajaxContent")
            $('#TB_window').removeClass("r3d-TB_window")
            $('.r3d-row-lightbox').hide()
            $pagesContainer.empty()
            pages = []
            thumbs = []
        });

        $('#r3d-mode').bind('change', function(){
            var mode = this.value
            if(mode == 'lightbox'){
                $('.r3d-row-lightbox').show()
            }else{
                $('.r3d-row-lightbox').hide()
            }
        })


    });

})(jQuery);

