;(function ($, window, MapSVG) {
  var MapSVGAdminFloorsListController = function (container, admin, mapsvg) {
    var _this = this
    this.name = "floors-list"
    this.database = new MapSVG.DatabaseService(
      {
        type: "local",
        dbObject: mapsvg.getData().options.floors,
      },
      mapsvg,
    )
    this.database.on("change", function () {
      // _this.mapsvg.setFloorsControl();
    })
    this.database.setSchema([
      { name: "id", label: "ID", visible: true, type: "id" },
      {
        name: "object_id",
        label: "Object ID",
        visible: true,
        type: "select",
        optionsGrouped: true,
        options: mapsvg.getGroupSelectOptions(),
      },
      { name: "title", label: "Title", visible: true, type: "text" },
      { name: "visible", label: "Visible", visible: false, type: "checkbox" },
    ])
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminFloorsListController = MapSVGAdminFloorsListController
  MapSVG.extend(MapSVGAdminFloorsListController, window.MapSVGAdminController)

  MapSVGAdminFloorsListController.prototype.viewLoaded = function () {
    var _this = this
    _this.redrawDataList()
  }

  MapSVGAdminFloorsListController.prototype.viewDidAppear = function () {
    MapSVGAdminController.prototype.viewDidAppear.call(this)
    // if(this.databaseTimestamp < this.database.lastChangeTime){
    //     this.redrawDataList();
    // }
  }
  MapSVGAdminFloorsListController.prototype.viewDidDisappear = function () {
    MapSVGAdminController.prototype.viewDidDisappear.call(this)
    this.closeFormHandler()
  }

  MapSVGAdminFloorsListController.prototype.setEventHandlers = function () {
    var _this = this

    $("#mapsvg-btn-floor-add").on("click", function (e) {
      e.preventDefault()
      _this.btnAdd = $(this)
      // _this.btnAdd.hide();
      _this.btnAdd.addClass("disabled")
      _this.editDataRow()
    })
    this.view
      .on("click", ".mapsvg-data-row", function (e) {
        if (!$(this).hasClass("active")) {
          _this.editDataRow($(this))
        }
      })
      .on("click", ".mapsvg-floor-view-toggle", function (e) {
        e.preventDefault()
        e.stopPropagation()
        $(this).toggleClass("active")
        var id = $(this).closest("tr").data("id")
        var obj = _this.database.getLoadedObject(id)
        obj.visible = !$(this).hasClass("active")
        if (obj.visible) {
          $(this).find("i").toggleClass("fa-eye", true)
          $(this).find("i").toggleClass("fa-eye-slash", false)
        } else {
          $(this).find("i").toggleClass("fa-eye", false)
          $(this).find("i").toggleClass("fa-eye-slash", true)
        }
        _this.mapsvg.setGroups()
        _this.mapsvg.setFloorsControl()
      })
      .on("click", ".mapsvg-floor-delete", function (e) {
        e.preventDefault()
        e.stopPropagation()
        var row = $(this).closest("tr")
        _this.deleteDataRow(row)
      })
  }

  MapSVGAdminFloorsListController.prototype.getTemplateData = function () {
    var _this = this
    return {
      fields: _this.getDataFieldsForTemplate(true),
      data: _this.database.getLoaded(),
    }
  }

  MapSVGAdminFloorsListController.prototype.getDataFieldsForTemplate = function (onlyVisible) {
    var _this = this
    var _fields = [
      { name: "id", visible: true, type: "id" },
      { name: "object_id", visible: true, type: "select" },
      { name: "title", visible: true, type: "text" },
    ]
    // var schema = this.database.getSchema();
    // if(schema){
    //     schema.forEach(function(obj){
    //         if(onlyVisible){
    //             if(obj.visible)
    //                 return _fields.push(obj);
    //         }else{
    //             return _fields.push(obj);
    //         }
    //     });
    // }
    return _fields
  }

  MapSVGAdminFloorsListController.prototype.redrawDataList = function () {
    var _this = this

    _this.redraw()

    var pager = this.mapsvg.getPagination(function () {
      _this.redrawDataList()
    })
    this.view.find(".mapsvg-pagination-container").html(pager)
  }

  MapSVGAdminFloorsListController.prototype.getObjectRow = function (obj) {
    return this.view.find("#mapsvg-floor-row-" + obj.id)
  }
  MapSVGAdminFloorsListController.prototype.addDataRow = function (obj) {
    var _this = this
    var d = {
      fields: _this.database.getColumns({ visible: true }),
      params: obj,
    }
    var row = $(_this.templates.item(d))

    this.view.find("#mapsvg-floors-list-table tbody").prepend(row)
    return row
  }

  MapSVGAdminFloorsListController.prototype.updateDataRow = function (obj, row) {
    var _this = this
    var d = {
      fields: _this.database.getColumns({ visible: true }),
      params: obj,
    }

    var newRow = $(_this.templates.item(d))
    row = row || $("#mapsvg-floor-row-" + obj.id)
    row.replaceWith(newRow)
    newRow.addClass("mapsvg-row-updated")

    setTimeout(function () {
      newRow.removeClass("mapsvg-row-updated")
    }, 2600)
  }

  MapSVGAdminFloorsListController.prototype.deleteDataRow = function (row) {
    var _this = this
    var id = row.data("id")
    var object = this.database.getLoadedObject(id)
    if (!object) return false
    if (object.marker) _this.mapsvg.markerDelete(object.marker)
    this.database.delete(id)
    row.fadeOut(300, function () {
      row.remove()
    })
  }

  MapSVGAdminFloorsListController.prototype.editDataRow = function (row, scrollTo) {
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
      _dataRecord = _this.database.getLoadedObject(id)
    } else {
      _dataRecord = { visible: true }
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
      $("#mapsvg-btn-floor-add").removeClass("disabled")
    }
    if (_this.formContainer) _this.formContainer.empty().remove()

    _this.formContainer = $('<div class="mapsvg-modal-edit"></div>')
    this.view.append(_this.formContainer)

    _this.formBuilder = new mapsvg.formBuilder({
      container: _this.formContainer,
      schema: _this.database.getSchema(),
      editMode: false,
      mapsvg: _this.mapsvg,
      mediaUploader: mediaUploader,
      data: _dataRecord,
      admin: _this.admin,
      events: {
        save: function (data) {
          if (newRecord) {
            _this.saveDataObject(data)
            this.redraw()
          } else {
            _this.updateDataObject(data)
            this.close()
          }
        },
        close: function () {
          _this.closeFormHandler()
        },
      },
    })
    _this.formBuilder.init()
  }

  MapSVGAdminFloorsListController.prototype.saveDataObject = function (obj) {
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
      this.database
        .create(obj)
        .done(function (_obj) {
          if (creating) {
            _this.updateDataRow(_obj, row)
          }
        })
        .fail(function () {
          $.growl.error({ title: "Server error", message: "Can't save object" })
          row.remove()
        })
    } else {
      this.database.update(obj).fail(function () {
        $.growl.error({ title: "Server error", message: "Can't update object" })
      })
      if (obj.marker) {
        var marker = _this.mapsvg.getMarker(obj.marker.id)
        marker.setId("marker_" + obj.id)
        marker.setObject(obj)
      }
      this.closeFormHandler()
      this.updateDataRow(obj)
    }
  }
  MapSVGAdminFloorsListController.prototype.updateDataObject = function (obj) {
    var _this = this
    this.database.update(obj).fail(function () {
      $.growl.error({ title: "Server error", message: "Can't update object" })
    })
    if (obj.marker) {
      var marker = _this.mapsvg.getMarker(obj.marker.id)
      marker.setId("marker_" + obj.id)
      marker.setObject(obj)
    }
    this.closeFormHandler()
    this.updateDataRow(obj)
  }
  MapSVGAdminFloorsListController.prototype.closeFormHandler = function () {
    var _this = this
    $("#mapsvg-btn-floor-add").removeClass("disabled")
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
})(jQuery, window, window.MapSVG)
