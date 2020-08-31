/*
author http://codecanyon.net/user/creativeinteractivemedia
*/

var R3D = R3D || {};

(function($) {

    R3D.EditFlipbooks = function() {

        this.books = $.parseJSON(flipbooks);
        var arr = []
        for(var key in this.books){
                arr.push(this.books[key])
        }
        this.books = arr
        // console.log(this.books);
        var self = this

        var $table = $('#flipbooks-table')

        var emptyImgSrc = "data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="

        $('.copy-shortcode').click(function(){
            var id = $(this).attr("id")
            var shortcode = "[real3dflipbook id='"+id+"']"
            var copyText = document.getElementById("copy-text-hidden");
            copyText.value = shortcode

            /* Select the text field */
            copyText.select();

            /* Copy the text inside the text field */
            document.execCommand("copy");
        })

        function duplicateFlipbook(id){

            var data = 'action=r3d_duplicate&security=' + window.r3d_nonce + '&currentId=' + id

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

        function importFlipbooks(){

            var json = $('#flipbook-admin-json').val()

            json = JSON.stringify(JSON.parse(json))

            if (confirm('Import flipbooks from JSON. This will delete any existing flipbooks. Are you sure?')) {

                $.ajax({

                    type: "POST",
                    url: 'admin-ajax.php?page=real3d_flipbook_admin',
                    data: {
                        flipbooks: json,
                        security: window.r3d_nonce,
                        action: "r3d_import"
                    },

                    success: function(data, textStatus, jqXHR) {

                        location.reload()

                    },

                    error: function(XMLHttpRequest, textStatus, errorThrown) {

                        alert("Status: " + textStatus);
                        alert("Error: " + errorThrown);

                    }
                })

            }

        }

        function deleteFlipbooks(arr){

            var msg = ''
            var data = 'action=r3d_delete&security=' + window.r3d_nonce

            if(arr){
                if(arr.length == 1) 
                    msg = 'Deleete flipbook ' + arr
                else
                    msg = 'Delete flipbooks ' + arr
                data += '&currentId=' + arr
            }else{
                msg = "Delete all flipbooks"
            }

            if (confirm(msg + '. Are you sure?')) {

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

        }


        $('.delete-all-flipbooks').click(function(e){
            
            e.preventDefault()

            deleteFlipbooks()
 
        })
    }

    $(document).ready(function() {
        new R3D.EditFlipbooks()
    });
})(jQuery);