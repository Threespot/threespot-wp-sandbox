;(function ($, window, MapSVG, _mapsvg) {
  var MapSVGAdminDatabaseListController = function (container, admin, mapsvg) {
    var _this = this
    this.name = "database-list"
    this.database = mapsvg.objectsRepository
    this.database.events.on("afterLoad", function () {
      _this.redrawDataList()
    })
    this.enableHorizontalScroll = true

    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminDatabaseListController = MapSVGAdminDatabaseListController
  MapSVG.extend(MapSVGAdminDatabaseListController, window.MapSVGAdminController)

  MapSVGAdminDatabaseListController.prototype.viewLoaded = function () {
    var _this = this
    // this.databaseTimestamp = Date.now();

    _this.redrawDataList()
    _this.btnAdd = $("#mapsvg-btn-data-add")
  }

  MapSVGAdminDatabaseListController.prototype.viewDidAppear = function () {
    MapSVGAdminController.prototype.viewDidAppear.call(this)
    this.admin.getData().controllers.database.toggleButtons()
    if (this.databaseTimestamp < this.database.lastChangeTime) {
      this.redrawDataList()
    }
  }
  MapSVGAdminDatabaseListController.prototype.viewDidDisappear = function () {
    MapSVGAdminController.prototype.viewDidDisappear.call(this)
    this.closeFormHandler()
  }

  MapSVGAdminDatabaseListController.prototype.setEventHandlers = function () {
    var _this = this

    var searchTimer

    $("#mapsvg-data-search").on("keyup", function () {
      searchTimer && clearTimeout(searchTimer)
      var that = this
      searchTimer = setTimeout(function () {
        _this.mapsvg.objectsRepository.find({ search: $(that).val() })
        _this.redrawDataList()
      }, 300)
    })

    $("#mapsvg-data-search-cols").on("click", "li a", function (e) {
      e.preventDefault()
      var field = $(this).text()
      $(this).closest(".input-group").find(".mapsvg-serch-field").text(field)
      _this.searchField = field
    })

    this.toolbarView.on("click", ".mapsvg-data-cols a", function (e) {
      e.preventDefault()

      $(this).closest("li").toggleClass("active")
      var schema = _this.database.getSchema()
      var field = $(this).data("field")

      const fields = [...schema.fields]
      for (var i in fields) {
        if (field === fields[i].name) {
          fields[i].hiddenOnTable = !fields[i].hiddenOnTable
        }
      }
      schema.update({
        fields,
      })
      if (!this.schemaRepo) {
        this.schemaRepo = mapsvg.useRepository("schemas", _this.mapsvg)
        this.schemaRepo.init()
        this.schemaRepo.update(schema)
      }
    })

    $("#mapsvg-btn-data-add").on("click", function (e) {
      e.preventDefault()
      _this.showNewObjectForm()
    })

    this.view
      .on("click", ".mapsvg-data-row", function (e) {
        if (!$(this).hasClass("active")) {
          _this.editDataObject($(this).data("id"))
        }
      })
      .on("click", ".mapsvg-data-delete", function (e) {
        e.preventDefault()
        e.stopPropagation()
        $(this).tooltip("hide")
        var row = $(this).closest("tr")
        _this.deleteDataRow(row)
      })
      .on("click", ".mapsvg-data-copy", function (e) {
        e.preventDefault()
        e.stopPropagation()
        _this.copyDataObject($(this).closest("tr").data("id"))
      })

    $(window).on("keydown.form.database-list", (e) => {
      // @ts-ignore
      if ((e.metaKey || e.ctrlKey) && e.keyCode == 13)
        // @ts-ignore
        this.formBuilder && this.formBuilder.save()
      else if (e.keyCode == 27)
        // @ts-ignore
        this.formBuilder && this.formBuilder.close()
    })
  }

  MapSVGAdminDatabaseListController.prototype.showNewObjectForm = function () {
    let newEmptyObject = new _mapsvg.customObject({}, this.database.schema)
    this.editDataObject(newEmptyObject)
  }
  MapSVGAdminDatabaseListController.prototype.getTemplateData = function () {
    var _this = this
    var data = {
      fields: _this.getDataFieldsForTemplate(true),
      data: _this.database
        .getLoaded()
        .map((o) => o.getData(_this.mapsvg.regionsRepository.schema.name)),
    }
    return data
  }

  MapSVGAdminDatabaseListController.prototype.getDataFieldsForTemplate = function (onlyVisible) {
    var _this = this

    var _fields = []

    var schema = this.database.getSchema()
    if (schema) {
      schema.fields.forEach(function (obj) {
        if (onlyVisible) {
          if (!obj.hiddenOnTable) return _fields.push(obj)
        } else {
          return _fields.push(obj)
        }
      })
    }
    return _fields
  }

  MapSVGAdminDatabaseListController.prototype.redrawDataList = function () {
    var _this = this

    if (!this.contentWrap) {
      return
    }

    _this.redraw()
    this.contentWrap.find(".jspPane").css({ top: 0 })

    var fields = _this.database.getSchema().getFields()
    if (fields.length < 1) {
      $("#mapsvg-data-list-table").hide()
      $("#mapsvg-setup-database-msg").show()
    }
    var colsList = _this.toolbarView.find(".mapsvg-data-cols")
    colsList.empty()
    fields.forEach(function (field) {
      colsList.append(
        $(
          '<li class="' +
            (!field.hiddenOnTable ? "list-group-item active" : "list-group-item") +
            '"><a href="#" data-field="' +
            field.name +
            '">' +
            field.name +
            "</a></li>",
        ),
      )
    })

    var pager = this.mapsvg.getPagination(function () {
      _this.redrawDataList()
    })

    var cont = this.view.find(".mapsvg-pagination-container")
    cont.html(pager)
  }

  MapSVGAdminDatabaseListController.prototype.addDataRow = function (objectInstance) {
    var _this = this
    var d = {
      fields: _this.database.getSchema().getColumns({ visible: true }),
      params: objectInstance.getData(),
    }
    for (var i in d.fields) {
      if (d.fields[i].type == "region") {
        d.fields[i].options = []
        d.fields[i].optionsDict = {}
        _this.mapsvg.regions.forEach(function (region) {
          d.fields[i].options.push({ id: region.id, title: region.title })
          d.fields[i].optionsDict[region.id] = region.title ? region.title : region.id
        })
      }
    }
    var row = $(_this.templates.item(d))
    this.view.find("#mapsvg-data-list-table tbody").prepend(row)
    return row
  }

  MapSVGAdminDatabaseListController.prototype.updateDataRow = function (obj, row) {
    var _this = this
    var d = {
      fields: _this.database.getSchema().getColumns({ visible: true }),
      params: obj,
    }
    for (var i in d.fields) {
      if (d.fields[i].type == "region") {
        d.fields[i].options = []
        d.fields[i].optionsDict = {}
        _this.mapsvg.regions.forEach(function (region) {
          d.fields[i].options.push({ id: region.id, title: region.title })
          d.fields[i].optionsDict[region.id] = region.title ? region.title : region.id
        })
      }
    }

    var newRow = $(_this.templates.item(d))
    row = row || $("#mapsvg-data-" + obj.id)
    row.replaceWith(newRow)
    newRow.addClass("mapsvg-row-updated")

    setTimeout(function () {
      newRow.removeClass("mapsvg-row-updated")
    }, 2600)
  }

  MapSVGAdminDatabaseListController.prototype.deleteDataRow = function (row) {
    var _this = this
    var id = row.data("id")
    var object = this.database.getLoadedObject(id)
    if (object.location && object.location.marker) object.location.marker.delete()
    this.database.delete(id)
    row.fadeOut(300, function () {
      row.remove()
    })
  }

  MapSVGAdminDatabaseListController.prototype.undoDataObject = function (row) {
    var _this = this
    var id = row.data("id")
    var object = this.database.getLoadedObject(id)
    if (object.location && object.location.marker) object.location.marker.delete()
    this.database.delete(id)
    row.fadeOut(300, function () {
      row.remove()
    })
  }

  MapSVGAdminDatabaseListController.prototype.watchLocationChanges = function (
    locationFormElement,
    location,
  ) {
    location.events.on("change", () => {
      if (location.marker.isMoving()) {
        return false
      }
      locationFormElement.renderMarkerHtml()
    })
  }
  MapSVGAdminDatabaseListController.prototype.watchMarkerChanges = function (
    locationFormElement,
    location,
  ) {
    if (location && location.marker) {
      location.marker.events.on("change", () => {
        if (location.marker.isMoving()) {
          return false
        }
        locationFormElement.setValue(location.getData())
      })
    }
  }
  MapSVGAdminDatabaseListController.prototype.createMarkerFromObject = function (object) {
    if (object && object.location) {
      var markerCopy = new _mapsvg.marker({
        location: object.location,
        mapsvg: this.mapsvg,
        object: object,
        id: "marker_" + object.id + "_copy",
      })
      this.mapsvg.markerAdd(markerCopy)
      return markerCopy
    }
  }
  MapSVGAdminDatabaseListController.prototype.createMarkerCopy2 = function (object) {
    let locationTemp, locationField, locationFieldName
    locationField = this.database.getSchema().getFieldByType("location")
    if (locationField) {
      locationFieldName = locationField.name
      locationTemp = new _mapsvg.location(object[locationFieldName].getData())
    }
    let markerCopy = new _mapsvg.marker({
      location: locationTemp,
      mapsvg: this.mapsvg,
    })
    this.mapsvg.markerAdd(markerCopy)

    return markerCopy
  }

  MapSVGAdminDatabaseListController.prototype.editDataObject = function (
    object,
    scrollTo,
    closeOnSave,
  ) {
    let newRecord
    if (typeof object == "string" || typeof object == "number") {
      object = this.database.getLoadedObject(object)
      newRecord = false
    } else {
      newRecord = true
    }

    const objectCopy = object.clone()
    this.objectCopy = objectCopy

    closeOnSave = closeOnSave !== true ? (newRecord ? false : true) : true

    this.btnAdd.addClass("disabled")

    if (this.tableDataActiveRow) this.tableDataActiveRow.removeClass("mapsvg-row-selected")

    if (object && object.id) {
      newRecord = false
      this.updateScroll()
      this.tableDataActiveRow = $("#mapsvg-data-" + object.id)
      this.tableDataActiveRow.addClass("mapsvg-row-selected")
      if (scrollTo)
        this.contentWrap.data("jsp").scrollToElement(this.tableDataActiveRow, true, false)
    }

    if (!this.admin.mediaUploader) {
      this.admin.mediaUploader = wp.media.frames.file_frame = wp.media({
        title: "Choose images",
        button: {
          text: "Choose images",
        },
        multiple: true,
      })
    }

    if (this.formBuilder) {
      this.formBuilder.destroy()
      this.formBuilder = null
      this.formBuilderRow && this.formBuilderRow.remove()
    }
    if (this.formContainer) this.formContainer.empty().remove()

    this.formContainer = $('<div class="mapsvg-modal-edit"></div>')
    this.view.append(this.formContainer)

    var marker_id = object.marker && object.marker.id ? object.marker.id : ""
    // this.mapsvg.hideMarkersExceptOne(marker_id);

    this.formBuilder = new mapsvg.formBuilder({
      container: this.formContainer,
      schema: this.database.getSchema(),
      editMode: false,
      mapsvg: this.mapsvg,
      mediaUploader: this.admin.mediaUploader,
      data: objectCopy.getData(),
      admin: this.admin,
      closeOnSave: closeOnSave,
      events: {
        save: (event) => {
          const { formBuilder, data } = event.data
          if (newRecord) {
            this.saveDataObject(data)
          } else {
            this.updateDataObject(data)
          }

          this.mapsvg.unsetEditingMarker()

          if (closeOnSave) {
            formBuilder.close()
          } else {
            formBuilder.close()
            this.showNewObjectForm()
            // formBuilder.redraw();
            // this.mapsvg.hideMarkers();
          }
        },
        close: () => {
          this.closeFormHandler()
        },
        "change.formElement": (event) => {
          const { formElement, name, value } = event.data
          const data = {}
          data[name] = value

          let marker
          if (objectCopy.location) {
            marker = objectCopy.location.marker
          }
          objectCopy.update(data)

          // Marker has been deleted?
          if (marker && objectCopy.location === null) {
            this.mapsvg.markerDelete(marker)
          }

          if (
            formElement.type === "location" &&
            objectCopy.location &&
            !objectCopy.location.marker
          ) {
            const marker = new _mapsvg.marker({
              object: objectCopy,
              location: objectCopy.location,
              mapsvg: this.mapsvg,
            })
            this.mapsvg.markerAdd(marker)
            this.mapsvg.setEditingMarker(marker)
            this.watchMarkerChanges(formElement, objectCopy.location)
          }
        },
        init: (event) => {
          const { formBuilder, data } = event.data

          this.mapsvg.hideMarkers()

          this.createMarkerFromObject(objectCopy)

          const locationFormElement = formBuilder.getFormElementByType("location")

          if (locationFormElement && objectCopy.location && objectCopy.location.marker) {
            this.watchMarkerChanges(locationFormElement, objectCopy.location)
            this.mapsvg.setEditingMarker(objectCopy.location.marker)
          }

          this.mapsvg.setMarkerEditHandler((location) => {
            if (objectCopy.location) {
              this.mapsvg.markerDelete(objectCopy.location.marker)
            }
            location.setObject(objectCopy)
            objectCopy.location = location
            this.mapsvg.setEditingMarker(objectCopy.location.marker)
            objectCopy.location.marker.setImage()
            locationFormElement.setValue(location.getData())
            this.watchMarkerChanges(locationFormElement, objectCopy.location)
          })
        },
      },
    })
    this.formBuilder.init()
  }

  MapSVGAdminDatabaseListController.prototype.copyDataObject = function (id) {
    let newObject = this.database.getLoadedObject(id).clone()
    newObject.id = null

    if (newObject.location) {
      // object.location = new mapsvg.location(object.location);
      var marker = new _mapsvg.marker({
        location: newObject.location,
        mapsvg: this.mapsvg,
        object: newObject,
      })
      this.mapsvg.markerAdd(marker)
    }

    this.editDataObject(newObject, false, true)
  }

  MapSVGAdminDatabaseListController.prototype.saveDataObject = function (object) {
    var _this = this
    let locationCopy = this.objectCopy.location
    return this.database
      .create(object)
      .done(function (objectWithNewId) {
        var row = _this.addDataRow(objectWithNewId)
        if (objectWithNewId.location) {
          var marker = new _mapsvg.marker({
            location: objectWithNewId.location,
            mapsvg: _this.mapsvg,
            object: objectWithNewId,
          })
          _this.mapsvg.markerAdd(marker)
        }
      })
      .fail(function (response) {
        MapSVG.handleFailedRequest(response)
      })
  }
  MapSVGAdminDatabaseListController.prototype.updateDataObject = function (rawObjectData) {
    var _this = this

    this.closeFormHandler()

    return _this.database.findById(rawObjectData.id).done((object) => {
      let oldLocation = object.location
      this.mapsvg.disableAnimation()
      object.update(rawObjectData)
      if (object.location) {
        if (!oldLocation) {
          var marker = new _mapsvg.marker({
            location: object.location,
            mapsvg: _this.mapsvg,
            object: object,
          })
          _this.mapsvg.markerAdd(marker)
        } else {
          object.location.marker.setId("marker_" + object.id)
          object.location.marker.setObject(object)
        }
      } else {
        if (oldLocation && oldLocation.marker) {
          this.mapsvg.markerDelete(oldLocation.marker)
        }
      }
      setTimeout(() => this.mapsvg.enableAnimation(), 400)
      this.updateDataRow(object.getData(_this.mapsvg.regionsRepository.schema.name))
      _this.database.update(object).fail(function (response) {
        MapSVG.handleFailedRequest(response)
      })
    })
  }
  MapSVGAdminDatabaseListController.prototype.closeFormHandler = function () {
    var _this = this
    _this.btnAdd.removeClass("disabled")
    _this.mapsvg.showMarkers()

    if (this.objectCopy && this.objectCopy.location && this.objectCopy.location.marker) {
      this.mapsvg.unsetEditingMarker()
    }

    // Redraw clusters if clustering is ON
    if (_this.mapsvg.options.clustering.on) {
      _this.mapsvg.clusterizeMarkers()
    }

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

      if (_this.admin.getData().mode === "editMarkers") {
        _this.admin && _this.admin.enableMarkersMode(false)
        _this.admin.setPreviousMode()
      }
    }
    this.updateScroll()
  }
})(jQuery, window, window.MapSVG, mapsvg)
