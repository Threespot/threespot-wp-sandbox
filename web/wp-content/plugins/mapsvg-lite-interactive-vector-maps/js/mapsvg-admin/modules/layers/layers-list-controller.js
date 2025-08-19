;(function ($, window, MapSVG) {
  var MapSVGAdminLayersListController = function (container, admin, _mapsvg) {
    var _this = this
    this.name = "layers-list"
    this.groups = _mapsvg.groups
    this.schema = new mapsvg.schema({
      fields: [
        { name: "id", label: "ID", visible: true, type: "id" },
        {
          name: "objects",
          help: "You can select multiple objects",
          multiselect: true,
          label: "Objects",
          visible: true,
          type: "select",
          optionsGrouped: true,
          options: _mapsvg.getGroupSelectOptions(),
        },
        { name: "title", label: "Title", visible: true, type: "text" },
        { name: "visible", label: "Visible", visible: false, type: "checkbox" },
      ],
    })
    MapSVGAdminController.call(this, container, admin, _mapsvg)
  }
  window.MapSVGAdminLayersListController = MapSVGAdminLayersListController
  MapSVG.extend(MapSVGAdminLayersListController, window.MapSVGAdminController)

  MapSVGAdminLayersListController.prototype.viewLoaded = function () {
    var _this = this
    _this.redrawDataList()
  }

  MapSVGAdminLayersListController.prototype.viewDidDisappear = function () {
    MapSVGAdminController.prototype.viewDidDisappear.call(this)
    this.closeFormHandler()
  }

  MapSVGAdminLayersListController.prototype.setEventHandlers = function () {
    var _this = this

    $("#mapsvg-btn-layer-add").on("click", function (e) {
      e.preventDefault()
      _this.btnAdd = $(this)
      _this.btnAdd.addClass("disabled")
      _this.editDataRow()
    })
    this.view
      .on("click", ".mapsvg-data-row", function (e) {
        if (!$(this).hasClass("active")) {
          _this.editDataRow($(this))
        }
      })
      .on("click", ".mapsvg-layer-view-toggle", function (e) {
        e.preventDefault()
        e.stopPropagation()
        $(this).toggleClass("active")
        var id = $(this).closest("tr").data("id")
        var obj = _this.mapsvg.groups.get(id)
        obj.visible = !$(this).hasClass("active")
        $(this).find("i").toggleClass("bi-eye", obj.visible)
        $(this).find("i").toggleClass("bi-eye-slash", !obj.visible)
        _this.mapsvg.setGroups()
        _this.mapsvg.setLayersControl()
      })
      .on("click", ".mapsvg-layer-delete", function (e) {
        e.preventDefault()
        e.stopPropagation()
        var row = $(this).closest("tr")
        _this.deleteDataRow(row)
        _this.admin.save(true)
      })

    $(window).on("keydown.form.layers-list", (e) => {
      // @ts-ignore
      if ((e.metaKey || e.ctrlKey) && e.keyCode == 13)
        // @ts-ignore
        this.formBuilder && this.formBuilder.save()
      else if (e.keyCode == 27)
        // @ts-ignore
        this.formBuilder && this.formBuilder.close()
    })
  }

  MapSVGAdminLayersListController.prototype.getTemplateData = function () {
    var _this = this
    return {
      fields: _this.getDataFieldsForTemplate(true),
      data: _this.mapsvg.groups,
    }
  }

  MapSVGAdminLayersListController.prototype.getDataFieldsForTemplate = function (onlyVisible) {
    var _this = this
    var _fields = [
      { name: "id", visible: true, type: "id" },
      {
        name: "objects",
        visible: true,
        type: "select",
        optionsGrouped: true,
        options: _this.mapsvg.getGroupSelectOptions(),
      },
      { name: "title", visible: true, type: "text" },
    ]
    return _fields
  }

  MapSVGAdminLayersListController.prototype.redrawDataList = function () {
    var _this = this

    _this.redraw()

    var pager = this.mapsvg.getPagination(function () {
      _this.redrawDataList()
    })
    this.view.find(".mapsvg-pagination-container").html(pager)
  }

  MapSVGAdminLayersListController.prototype.getObjectRow = function (obj) {
    return this.view.find("#mapsvg-layer-row-" + obj.id)
  }
  MapSVGAdminLayersListController.prototype.addDataRow = function (obj) {
    var _this = this
    var d = {
      fields: this.schema.getColumns({ visible: true }),
      params: obj,
    }
    var row = $(_this.templates.item(d))

    this.view.find("#mapsvg-layers-list-table tbody").prepend(row)
    return row
  }

  MapSVGAdminLayersListController.prototype.updateDataRow = function (obj, row) {
    var _this = this
    var d = {
      fields: this.schema.getColumns({ visible: true }),
      params: obj,
    }

    var newRow = $(_this.templates.item(d))
    row = row || $("#mapsvg-layer-row-" + obj.id)
    row.replaceWith(newRow)
    newRow.addClass("mapsvg-row-updated")

    setTimeout(function () {
      newRow.removeClass("mapsvg-row-updated")
    }, 2600)
  }

  MapSVGAdminLayersListController.prototype.deleteDataRow = function (row) {
    var _this = this
    var id = row.data("id")
    var object = this.mapsvg.groups.get(id)
    if (!object) return false
    this.mapsvg.groups.delete(id)
    this.mapsvg.setGroups()
    this.mapsvg.setLayersControl()
    row.fadeOut(300, function () {
      row.remove()
    })
  }

  MapSVGAdminLayersListController.prototype.editDataRow = function (row, scrollTo) {
    var newRecord = !row

    var _dataRecord = {}

    if (this.tableDataActiveRow) this.tableDataActiveRow.removeClass("mapsvg-row-selected")

    if (row) {
      this.updateScroll()
      if (scrollTo) this.contentWrap.data("jsp").scrollToElement(row, true, false)
      this.tableDataActiveRow = row
      this.tableDataActiveRow.addClass("mapsvg-row-selected")
      var id = this.tableDataActiveRow.data("id")
      _dataRecord = this.mapsvg.groups.get(id)
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

    if (this.formBuilder) {
      this.formBuilder.destroy()
      this.formBuilder = null
      this.formBuilderRow && this.formBuilderRow.remove()
      $("#mapsvg-btn-layer-add").removeClass("disabled")
    }
    if (this.formContainer) this.formContainer.empty().remove()

    this.formContainer = $('<div class="mapsvg-modal-edit"></div>')
    this.view.append(this.formContainer)

    this.formBuilder = new mapsvg.formBuilder({
      container: this.formContainer,
      schema: this.schema,
      editMode: false,
      mapsvg: this.mapsvg,
      mediaUploader: mediaUploader,
      data: _dataRecord,
      admin: this.admin,
      events: {
        save: ({ data: { formBuilder, data } }) => {
          this.formBuilder.close()
          if (newRecord) {
            this.saveDataObject(data)
            this.editDataRow()
          } else {
            this.updateDataObject(data)
          }
        },
        close: () => {
          this.closeFormHandler()
        },
      },
    })
    this.formBuilder.init()
  }

  MapSVGAdminLayersListController.prototype.saveDataObject = function (obj) {
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
      this.mapsvg.groups.push(obj)
      if (creating) {
        _this.updateDataRow(obj, row)
      }
    } else {
      this.mapsvg.groups.update(obj)
      this.closeFormHandler()
      this.updateDataRow(obj)
    }
    this.mapsvg.setGroups()
    this.admin.save(true)
    this.mapsvg.setLayersControl()
  }
  MapSVGAdminLayersListController.prototype.updateDataObject = function (obj) {
    var _this = this
    this.mapsvg.groups.update(obj)
    this.mapsvg.setGroups()
    this.mapsvg.setLayersControl()

    this.closeFormHandler()
    this.updateDataRow(obj)
    this.admin.save(true)
  }
  MapSVGAdminLayersListController.prototype.closeFormHandler = function () {
    var _this = this
    $("#mapsvg-btn-layer-add").removeClass("disabled")
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
