;(function () {
  tinymce.PluginManager.add("mapsvg", function (editor, url) {
    // Add Button to Visual Editor Toolbar
    editor.addButton("mapsvg", {
      title: "Add a map @mapsvg",
      cmd: "mapsvg",
      image:
        "data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI3LjUuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCAxOTIgMTkyIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAxOTIgMTkyOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxwYXRoIGQ9Ik05NS4yLDAuMkM0Mi41LDAuMiwwLjEsNDMuNCwwLjEsOTYuMVM0My40LDE5Miw5Ni4xLDE5MlMxOTIsMTQ4LjgsMTkyLDk2LjFTMTQ3LjksMC4yLDk1LjIsMC4yeiBNMTU4LjMsMTM5LjNIMzMKCWMtNS4yLDAtOC42LTYtNi0xMC40TDkwLDIwYzIuNi00LjMsOS41LTQuMywxMi4xLDBsNjMuMSwxMDguOUMxNjYuOSwxMzMuMiwxNjMuNSwxMzkuMywxNTguMywxMzkuM3oiLz4KPC9zdmc+Cg==",
    })

    var mapsvgMaps
    // Add Command when Button Clicked

    var link = jQuery(
      '<a href="#TB_inline?width=100%&height=auto&inlineId=mapsvg-choose-map" class="thickbox"></a>',
    ).appendTo("body")

    function showModal() {
      link.trigger("click")
    }

    jQuery("body").on("click", ".mapsvg-insert-shortcode", function () {
      var id = jQuery(this).data("id")
      var title = jQuery(this).data("title")
      editor.execCommand(
        "mceInsertContent",
        false,
        '[mapsvg id="' + id + '" title="' + title + '"]',
      )
      tb_remove()
    })

    editor.addCommand("mapsvg", function () {
      if (mapsvgMaps) return showModal()

      let box = jQuery(
        '<div id="mapsvg-choose-map" style="display:none;"><div class="wrap"><h1>Maps @mapsvg ' +
          '<a href="/wp-admin/admin.php?page=mapsvg-config" target="_blank" class="page-title-action">Add New</a>' +
          '</h1><div class="mapsvg-maps-list"></div></div></div>',
      )

      fetch("/wp-json/mapsvg/v1/" + "maps")
        .then((response) => response.json())
        .then((response) => {
          response.items.forEach(function (map) {
            box
              .find(".mapsvg-maps-list")
              .append(
                '<a data-id="' +
                  map.id +
                  '" data-title="' +
                  map.title +
                  '" href="#" class="mapsvg-insert-shortcode">' +
                  map.title +
                  "</a>",
              )
          })
          jQuery("body").append(box)
          showModal()
        })
    })
  })
})()
