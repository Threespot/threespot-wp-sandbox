/*
author http://codecanyon.net/user/creativeinteractivemedia
*/

var R3D = R3D || {};

(function($) {

    function downloadObjectAsJson(exportObj, exportName){
        var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(exportObj);
        var downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href",     dataStr);
        downloadAnchorNode.setAttribute("download", exportName + ".json");
        document.body.appendChild(downloadAnchorNode); // required for firefox
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
      }


    if(typeof flipbooks_json != "undefined"){
        downloadObjectAsJson(flipbooks_json, "flipbooks")
    }

    $('.copy-json').click(function(){
        var copyText = document.getElementById("copy-text-hidden");
        copyText.value = flipbooks_json

          /* Select the text field */
        copyText.select();

          /* Copy the text inside the text field */
        document.execCommand("copy");
    })

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

        function addBook(book) {
            
            $(  '<tr>' + 
                    '<th scope="row" class="manage-column column-cb check-column">' + 
                        '<input type="checkbox" class="row-checkbox" name="' + book.id + '">' + 
                    '</th>      ' + 
                    '<td style="vertical-align: top;">' + 
                        '<a href="#" class="edit" name="' + book.id + '"><div style="height:100px; width: 70px;background-image:url(' + (book.thumb || emptyImgSrc) + ');background-color:transparent;background-size:contain;background-position:center;background-repeat:no-repeat;">'+'</a>' + 
                        // '<a href="#" class="edit" name="' + book.id + '"><img height="100" src="' + (book.thumb || emptyImgSrc) + '">'+'</a>' + 
                    '</td>' + 
                    '<td style="vertical-align: top;">' + 
                        '<strong><a href="#" class="edit" title="Edit" name="' + book.id + '">' + book.name + '</a></strong>' + 
                        '<div class="row-actions"><span name="' + book.id + '" class="edit"><a href="#" title="Edit this item">Edit</a> | </span><span class="inline hide-if-no-js duplicate" name="' + book.id + '"><a href="#" title="Duplicate flipbook" >Duplicate</a> | </span><span class="trash" name="' + book.id + '" ><a href="#" title="Move to trash" >Trash</a></span>' + 
                        '</div>' + 
                    '</td>' + 
                    '<td>[real3dflipbook id="' + book.id + '"]   <div id="'+book.id+'" class="button-secondary copy-shortcode">Copy</div>        </td>' + 
                    '<td>' + book.date + '</td>' + 
                '</tr>').appendTo($table)
        }

        var keys = []
        for (var key in this.books) {
            keys.push(key);
            if(typeof this.books[key].date == 'undefined')
                this.books[key].date = '';
        }

        function dynamicSort(property) {
            var sortOrder = 1;
            if(property[0] === "-") {
                sortOrder = -1;
                property = property.substr(1);
            }
            return function (a,b) {
                var result = (a[property] < b[property]) ? -1 : (a[property] > b[property]) ? 1 : 0;
                return result * sortOrder;
            }
        }
        this.books.sort(dynamicSort("date"))
        this.books.reverse()

        var pageSize = 20
        var currentPage = 0
        var totalPages = parseInt(keys.length / pageSize) + 1

        if (keys.length < pageSize)
            $('.tablenav-pages').addClass('one-page')
        $('.total-pages').text(totalPages)
        $('.displaying-num').text(keys.length + ' items')

        $('.items-20').click(function(){
            pageSize = 100
            totalPages = parseInt(keys.length / pageSize) + 1
            showPage(0)
            if (keys.length < pageSize)
                $('.tablenav-pages').addClass('one-page')
            $('.total-pages').text(totalPages)
            $('.displaying-num').text(keys.length + ' items')
        })

        $('.items-100').click(function(){
            pageSize = 100
            totalPages = parseInt(keys.length / pageSize) + 1
            showPage(0)
            if (keys.length < pageSize)
                $('.tablenav-pages').addClass('one-page')
            $('.total-pages').text(totalPages)
            $('.displaying-num').text(keys.length + ' items')
        })

        $('.items-all').click(function(){
            pageSize = keys.length
            totalPages = 1
            showPage(0)
            if (keys.length < pageSize)
                $('.tablenav-pages').addClass('one-page')
            $('.total-pages').text(totalPages)
            $('.displaying-num').text(keys.length + ' items')
            
        })

        $('#name').click(function(){
           
            $(this).toggleClass("asc")
            $(this).toggleClass("desc")

            self.books.sort(dynamicSort("name"))
            if($(this).hasClass("asc"))
                self.books.reverse()
            showPage(0)
        })

        $('#date').click(function(){

            $(this).toggleClass("asc")
            $(this).toggleClass("desc")

            self.books.sort(dynamicSort("date"))
            if($(this).hasClass("asc"))
                self.books.reverse()
            showPage(0)
        })

        function showPage(index) {
            $('#flipbooks-table').empty()
            for (var i = pageSize * index; i < pageSize * (index + 1); i++) {
                var book = self.books[i]
                if (book)
                    addBook(book)
            }
            $('.current-page').val(index + 1)

            $('.edit').click(function(e) {
                e.preventDefault()
                var id = this.getAttribute("name")
                window.location = window.location.origin + window.location.pathname + '?page=real3d_flipbook_admin&action=edit&bookId=' + id
            })
            $('.duplicate').click(function(e) {
                e.preventDefault()
                var id = this.getAttribute("name")
                duplicateFlipbook(id)
            })
            $('.import').click(function(e) {
                e.preventDefault()
                importFlipbooks()
            })
            $('.trash').click(function(e) {
                e.preventDefault()
                var id = this.getAttribute("name")
                deleteFlipbooks(id)
            })
            $('.undo').click(function(e) {
                e.preventDefault()
                window.location = window.location.origin + window.location.pathname + '?page=real3d_flipbook_admin&action=undo'
            })
        }

        showPage(currentPage)

        $('.first-page').click(function() {
            currentPage = 0
            showPage(currentPage)
        })
        $('.prev-page').click(function() {
            if (currentPage > 0) currentPage--;
            showPage(currentPage)
        })
        $('.next-page').click(function() {
            if (currentPage < (totalPages - 1)) currentPage++;
            showPage(currentPage)
        })
        $('.last-page').click(function() {
            currentPage = totalPages - 1
            showPage(currentPage)
        })

        $('.bulkactions-apply').click(function() {
            var action = $(this).parent().find('select').val()
            if (action != '-1') {
                var list = []
                $('.row-checkbox').each(function() {
                    if ($(this).is(':checked'))
                        list.push($(this).attr('name'))
                })
                if (list.length > 0) {
                    deleteFlipbooks(list)
                }
            }
        })

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