;(function ($, window, mapsvgGlobal, MapSVG) {
  var MapSVGAdminGalleryListController = function (container, admin, mapsvg) {
    var _this = this
    this.name = "gallery-list"
    _this.schema = new mapsvgGlobal.schema({
      fields: [
        { name: "id", label: "ID", visible: false, type: "id" },
        {
          name: "type",
          label: "Gallery type",
          visible: true,
          type: "radio",
          options: [
            { label: "Original thumbnails", value: "original" },
            { label: "Square thumbnails", value: "multi" },
            { label: "Justified thumbnails", value: "justified" },
            {
              label: "First image only",
              value: "single",
            },
            // {label: "First large image + square thumbnails", value: 'combo'},
            { label: "Slider", value: "slider" },
          ],
        },
        {
          name: "background",
          label: "Gallery: background color",
          visible: true,
          placeholder: "#EEEEEE",
          type: "colorpicker",
        },
        {
          name: "thumb_width",
          label: "Thumbnails: width",
          visible: true,
          placeholder: "50",
          type: "text",
        },
        {
          name: "thumb_height",
          label: "Thumbnails: height",
          visible: true,
          placeholder: "50",
          type: "text",
        },
        {
          name: "thumb_margin",
          label: "Thumbnails: margin",
          visible: true,
          placeholder: "3",
          type: "text",
        },
        // {name: 'padding', label: 'Gallery: padding', visible: true, type: 'text'},
        {
          name: "max_height",
          label: "Slider: max-height",
          visible: true,
          type: "text",
          placeholder: "250",
        },
        {
          name: "lightbox",
          label: "Lightbox",
          visible: true,
          type: "checkbox",
          help: "Open lightbox on click on a thumbnail.",
          value: 1,
        },
        {
          name: "lb_button_show",
          label: "Lightbox: button",
          visible: true,
          type: "checkbox",
          help: 'Add lightbox opening button into the gallery. Usable only for "First image" gallery type.',
        },
        {
          name: "lb_button_text",
          label: "Lightbox: button text",
          visible: true,
          type: "text",
          value: "View all ({{counter}})",
          help: 'You can use {{counter}} tag inside of the button text to show total number of photos. Example: "View all {{counter}}"',
        },
        // {name: 'lb_show_title', label: 'Lightbox: show title', visible: true, type: 'checkbox', help: 'Show image title in the lightbox'},
        // {name: 'lb_show_desc', label: 'Lightbox:  show description', visible: true, type: 'checkbox', help: 'Show image description in the lightobx'}
      ],
    })
    // this.templatesURL = mapsvg_gal_paths.templates;
    MapSVGAdminController.call(this, container, admin, mapsvg)

    if (!this.mapsvg.options.galleries) {
      this.mapsvg.options.galleries = new mapsvgGlobal.arrayIndexed("id", [], {
        autoId: true,
      })
    } else if (!(this.mapsvg.options.galleries instanceof mapsvgGlobal.arrayIndexed)) {
      this.mapsvg.options.galleries = new mapsvgGlobal.arrayIndexed(
        "id",
        this.mapsvg.options.galleries,
        { autoId: true },
      )
    }
  }
  window.MapSVGAdminGalleryListController = MapSVGAdminGalleryListController
  MapSVG.extend(MapSVGAdminGalleryListController, window.MapSVGAdminController)

  MapSVGAdminGalleryListController.prototype.viewLoaded = function () {
    var _this = this
    _this.redrawDataList()
  }

  MapSVGAdminGalleryListController.prototype.viewDidAppear = function () {
    MapSVGAdminController.prototype.viewDidAppear.call(this)
  }
  MapSVGAdminGalleryListController.prototype.viewDidDisappear = function () {
    MapSVGAdminController.prototype.viewDidDisappear.call(this)
    this.closeFormHandler()
  }

  MapSVGAdminGalleryListController.prototype.setEventHandlers = function () {
    var _this = this

    $("#mapsvg-btn-gallery-add").on("click", function (e) {
      e.preventDefault()
      _this.btnAdd = $(this)
      // _this.btnAdd.hide();
      _this.btnAdd.addClass("disabled")
      _this.editDataRow()
    })
    var click
    this.view.on("mousedown", ".mapsvg-data-row", function (e) {
      click = window.mapsvg.utils.env.getMouseCoords(e)
    })
    this.view
      .on("mouseup", ".mapsvg-data-row", function (e) {
        var click2 = window.mapsvg.utils.env.getMouseCoords(e)
        if (click.x != click2.x) return
        if (
          $(e.target).hasClass("mapsvg-copy-shortcode") ||
          $(e.target).parent().hasClass("mapsvg-copy-shortcode")
        ) {
          var str = e.target.dataset["shortcode"]
          var el = document.createElement("textarea")
          el.value = str
          el.setAttribute("readonly", "")
          el.style.position = "absolute"
          el.style.left = "-9999px"
          document.body.appendChild(el)
          el.select()
          document.execCommand("copy")
          document.body.removeChild(el)
          $.growl.notice({
            title: "",
            message: "Tag copied to clipboard",
            duration: 1000,
          })
          return
        }
        if (!$(this).hasClass("active")) {
          _this.editDataRow($(this))
        }
      })
      .on("mouseup", ".mapsvg-gallery-delete", function (e) {
        e.preventDefault()
        e.stopPropagation()
        var row = $(this).closest("tr")
        _this.deleteDataRow(row)
      })
    // .on('mouseup','input',function(e){
    //     e.stopPropagation();
    //     $(this).select();
    // });
  }

  MapSVGAdminGalleryListController.prototype.getTemplateData = function () {
    var _this = this
    return {
      fields: _this.getDataFieldsForTemplate(true),
      data: _this.mapsvg.options.galleries,
      map_id: _this.mapsvg.id,
    }
  }

  MapSVGAdminGalleryListController.prototype.getDataFieldsForTemplate = function (onlyVisible) {
    var _this = this
    // var _fields = [
    //     {name: 'id', visible: true, type: 'id'},
    //     {name: 'title', visible: true, type: 'text'}
    // ];

    return _this.schema
  }

  MapSVGAdminGalleryListController.prototype.redrawDataList = function () {
    var _this = this

    _this.redraw()

    var pager = this.mapsvg.getPagination(function () {
      _this.redrawDataList()
    })
    this.view.find(".mapsvg-pagination-container").html(pager)
  }

  MapSVGAdminGalleryListController.prototype.getObjectRow = function (obj) {
    return this.view.find("#mapsvg-gallery-row-" + obj.id)
  }
  MapSVGAdminGalleryListController.prototype.addDataRow = function (obj) {
    var _this = this
    var d = {
      fields: this.schema.getColumns({ visible: true }),
      params: obj,
      map_id: _this.mapsvg.id,
    }
    var row = $(_this.templates.item(d))

    this.view.find("#mapsvg-gallery-list-table tbody").prepend(row)
    return row
  }

  MapSVGAdminGalleryListController.prototype.updateDataRow = function (obj, row) {
    var _this = this
    var d = {
      fields: this.schema.getColumns({ visible: true }),
      params: obj,
      map_id: _this.mapsvg.id,
    }

    var newRow = $(_this.templates.item(d))
    row = row || $("#mapsvg-gallery-row-" + obj.id)
    row.replaceWith(newRow)
    newRow.addClass("mapsvg-row-updated")

    setTimeout(function () {
      newRow.removeClass("mapsvg-row-updated")
    }, 2600)
  }

  MapSVGAdminGalleryListController.prototype.deleteDataRow = function (row) {
    var _this = this
    var id = row.data("id")
    var object = this.mapsvg.options.galleries.get(id)
    if (!object) return false
    if (object.marker) _this.mapsvg.markerDelete(object.marker)
    this.mapsvg.options.galleries.delete(id)
    row.fadeOut(300, function () {
      row.remove()
    })
  }

  MapSVGAdminGalleryListController.prototype.editDataRow = function (row, scrollTo) {
    var _this = this

    var newRecord = !row ? true : false

    var _dataRecord = {}

    if (_this.tableDataActiveRow) _this.tableDataActiveRow.removeClass("mapsvg-row-selected")

    if (row) {
      _this.updateScroll()
      if (scrollTo) _this.contentWrap.data("jsp").scrollToElement(row, true, false)
      _this.tableDataActiveRow = row
      _this.tableDataActiveRow.addClass("mapsvg-row-selected")
      var id = _this.tableDataActiveRow.data("id")
      _dataRecord = this.mapsvg.options.galleries.get(id)
    } else {
      _dataRecord = {
        type: "multi",
        thumb_width: 50,
        thumb_height: 50,
        thumb_margin: 3,
        max_height: 250,
        lightbox: true,
      }
    }

    var mediaUploader = (wp.media.frames.file_frame = wp.media({
      title: "Choose images",
      button: {
        text: "Choose images",
      },
      multiple: true,
    }))

    if (_this.formBuilder) {
      _this.formBuilder.destroy()
      _this.formBuilder = null
      _this.formBuilderRow && _this.formBuilderRow.remove()
      $("#mapsvg-btn-gallery-add").removeClass("disabled")
    }
    if (_this.formContainer) _this.formContainer.empty().remove()

    _this.formContainer = $('<div class="mapsvg-modal-edit"></div>')
    this.view.append(_this.formContainer)

    _this.formBuilder = new mapsvg.formBuilder({
      container: _this.formContainer,
      schema: _this.schema,
      editMode: false,
      mapsvg: _this.mapsvg,
      mediaUploader: mediaUploader,
      data: _dataRecord,
      admin: _this.admin,
      events: {
        save: function (event) {
          const { formBuilder, data } = event.data
          if (newRecord) {
            _this.saveDataObject(data)
          } else {
            _this.updateDataObject(data)
          }
          this.close()
          $().mapsvgadmin().save()
        },
        close: function () {
          _this.closeFormHandler()
        },
      },
    })
    this.formBuilder.init()
  }

  MapSVGAdminGalleryListController.prototype.saveDataObject = function (obj) {
    var _this = this
    var row, creating
    if (obj.id) {
      row = this.getObjectRow(obj)
    }
    if (!(row && row.length)) {
      creating = true
      row = this.addDataRow(obj)
    }
    if (creating) {
      this.mapsvg.options.galleries.push(obj)
      _this.updateDataRow(obj, row)
    } else {
      this.updateDataObject(obj)
    }
  }
  MapSVGAdminGalleryListController.prototype.updateDataObject = function (obj) {
    var _this = this
    this.mapsvg.options.galleries.update(obj)
    this.closeFormHandler()
    this.updateDataRow(obj)
  }
  MapSVGAdminGalleryListController.prototype.closeFormHandler = function () {
    var _this = this
    $("#mapsvg-btn-gallery-add").removeClass("disabled")
    _this.mapsvg.showMarkers()

    if (_this.formBuilder) {
      _this.formBuilder.destroy()
      _this.formBuilder = null
      _this.formContainer.empty().remove()
      // _this.formBuilderRow && _this.formBuilderRow.remove();
      _this.tableDataActiveRow && _this.tableDataActiveRow.removeClass("mapsvg-row-selected")
      _this.tableDataActiveRow &&
        !_this.tableDataActiveRow.hasClass("mapsvg-row-updated") &&
        _this.tableDataActiveRow.addClass("mapsvg-row-closed")
      setTimeout(function () {
        _this.tableDataActiveRow &&
          !_this.tableDataActiveRow.hasClass("mapsvg-row-updated") &&
          _this.tableDataActiveRow.removeClass("mapsvg-row-closed")
      }, 1600)
      // WP Media Uploader inserts a.browser links, remove them:
      $("a.browser").remove()

      // _this.admin.setPreviousMode();
    }

    this.updateScroll()
  }
})(jQuery, window, mapsvg, window.MapSVG)
