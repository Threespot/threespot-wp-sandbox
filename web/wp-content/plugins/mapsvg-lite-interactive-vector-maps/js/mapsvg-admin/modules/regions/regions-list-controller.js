;(function ($, window, MapSVG) {
  var MapSVGAdminRegionsListController = function (container, admin, mapsvg) {
    var _this = this
    this.name = "regions-list"
    this.database = mapsvg.regionsRepository
    _this.database.events.on("afterLoad", function () {
      _this.redrawDataList()
    })
    this.enableHorizontalScroll = true
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminRegionsListController = MapSVGAdminRegionsListController
  MapSVG.extend(MapSVGAdminRegionsListController, window.MapSVGAdminController)

  MapSVGAdminRegionsListController.prototype.viewLoaded = function () {
    var _this = this
    // var fields = _this.database.getSchema().getFields()

    this.databaseTimestamp = Date.now()

    _this.redrawDataList()
  }

  MapSVGAdminRegionsListController.prototype.getTemplateData = function () {
    var _this = this
    var regions = _this.database.getLoaded()
    var regionOpts = _this.mapsvg.options.regions
    const data = {
      fields: _this.getDataFieldsForTemplate(true),
      data: regions.map((r) => r.getData()),
    }
    data.data.forEach(function (r) {
      r.fill =
        regionOpts && regionOpts[r.id] && regionOpts[r.id].style && regionOpts[r.id].style.fill
          ? regionOpts[r.id].style.fill
          : null
    })
    return data
  }

  MapSVGAdminRegionsListController.prototype.getDataFieldsForTemplate = function (onlyVisible) {
    var _this = this
    var _fields = []
    var schema = this.database.getSchema()
    if (schema) {
      schema.fields.forEach(function (obj) {
        var data = {
          type: obj.type,
          name: obj.name,
          options: obj.options,
          optionsDict: {},
        }
        if (obj.options) {
          // data.options = Array.from(obj.options, function(v, value){ return v[1]; });
          // data.options = Array.from(obj.options, function(v, value){ return v[1]; });
          // data.optionsDict = {};
          obj.options.forEach(function (value, key) {
            data.optionsDict[value.value] = value
          })
        }

        if (onlyVisible) {
          if (!obj.hiddenOnTable) {
            return _fields.push(data)
          }
        } else {
          return _fields.push(data)
        }
      })
    }
    return _fields
  }

  MapSVGAdminRegionsListController.prototype.setEventHandlers = function () {
    var _this = this

    this.toolbarView.on("click", ".mapsvg-data-cols a", function (e) {
      e.preventDefault()

      // $(this).closest('li').toggleClass('active');
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

    this.view.on("click", ".region-cpicker", function (e) {
      e.preventDefault()
      e.stopPropagation()

      setTimeout(function () {
        _this.view.addClass("mapsvg-cpicker-opened")
      }, 200)

      var btn = $(this)
      _this.colorpickerRegionBtn && _this.colorpickerRegionBtn.colorpicker("destroy")
      _this.colorpickerRegionBtn = btn
      _this.colorpickerRegionId = $(this).closest(".mapsvg-data-row").data("region-id")
      var rOptions = _this.mapsvg.getOptions().regions[_this.colorpickerRegionId]
      var curColor = rOptions ? rOptions.fill : ""
      _this.colorpicker = btn
        .colorpicker({
          template:
            '<div class="colorpicker dropdown-menu"><div class="colorpicker-saturation"><i><b></b></i></div><div class="colorpicker-hue"><i></i></div><div class="colorpicker-alpha"><i></i></div><div class="colorpicker-input-wrap"><input class="colorpicker-input" type="text"/><i></i></div><div class="colorpicker-reset-wrap"><button class="btn btn-xs btn-default colorpicker-reset">Reset</button><i></i></div></div>',
        })
        .colorpicker("show")

      var colorInput = $(".colorpicker-input")
      var colorReset = $(".colorpicker-reset")
      colorReset.on("click", function () {
        var rid = _this.colorpickerRegionId
        _this.mapsvg.update({
          regions: {
            [rid]: {
              style: {
                fill: "",
              },
            },
          },
        })
        colorInput.val("")
        btn.css({ background: "" })
        btn.colorpicker("destroy")
      })
      if (curColor) colorInput.val(curColor)
      colorInput.on("focus", function () {
        colorInput.focused = true
      })
      colorInput.on("blur", function () {
        colorInput.focused = false
      })
      colorInput.on("keyup paste", function () {
        var color = $(this).val()
        btn.colorpicker("setValue", color)
      })

      btn.colorpicker().on("changeColor", function (event) {
        var rid = _this.colorpickerRegionId
        var c = event.color.toRGB()
        var color
        if (c.a === 1) {
          color = event.color.toHex()
        } else {
          color = "rgba(" + c.r + "," + c.g + "," + c.b + "," + c.a + ")"
        }
        _this.mapsvg.update({
          regions: {
            [rid]: {
              style: {
                fill: color,
              },
            },
          },
        })
        if (!colorInput.focused) colorInput.val(color)
        btn.css({ background: color })
      })
      if (curColor) btn.colorpicker("setValue", curColor)
      $(window).on("mousedown.colorpicker", function (e) {
        if ($(e.target).not("input")) {
          _this.view.removeClass("mapsvg-cpicker-opened")
          colorInput.blur()
        }
      })
    })

    $("body").on("click", ".colorpicker-input", function () {
      // $(this).select().focus();
    })

    this.view.on("click", ".mapsvg-link-btn", function (e) {
      e.preventDefault()
      var cont = $(this).parent()
      var btn = $(this)
      var row = $(this).closest("tr")
      var region_id = row.data("region-id")
      var region = _this.mapsvg.getRegion(region_id)
      var oldUrl = region.href
      var input = $(
        '<input class="link-editable form-control" value="' + (region.href || "") + '"/>',
      )
      cont.append(input)
      btn.addClass("opened")
      input.select()
      input
        .on("blur", function () {
          var newUrl = $(this).val()
          if (newUrl != oldUrl) {
            region.update({ href: newUrl })
            if (newUrl.length) btn.addClass("active")
            else btn.removeClass("active")
          }
          $(this).off().remove()
          btn.removeClass("opened")
        })
        .on("keypress", function (e) {
          if (e.which == 13 || event.keyCode == 13) {
            e.preventDefault()
            $(this).blur().trigger("blur")
          }
        })
    })

    var searchTimer
    $("#mapsvg-regions-search").on("keyup", function () {
      searchTimer && clearTimeout(searchTimer)
      var that = this
      searchTimer = setTimeout(function () {
        // _this.mapsvg.regionsDatabase.query.search = $(this).val();
        _this.mapsvg.regionsRepository.find({
          search: $(that).val(),
          searchFallback: true,
        })
        _this.redrawDataList()
      }, 300)
    })

    this.view
      .on("mouseover", ".mapsvg-data-row", function () {
        var id = $(this).data("region-id")
        var region = _this.mapsvg.getRegion(id)
        if (!region) {
          return
        }
        // if(region.selected)
        //     _this.mapsvg.deselectRegion(region);
        if (!region.selected) region.highlight()
      })
      .on("mouseout", ".mapsvg-data-row", function () {
        var id = $(this).data("region-id")
        var region = _this.mapsvg.getRegion(id)
        if (!region) {
          return
        }
        // if(region.selected)
        if (!region.selected) region.unhighlight()
        // _this.mapsvg.deselectRegion(region);
        // _this.mapsvg.getRegion(id).unhighlight();
      })
      .on("click", ".mapsvg-data-row", function (e) {
        var t = $(e.target)
        var id = $(this).data("region-id")

        if (t.is(".btn") || t.parent().is(".btn")) {
          return
        } else if (t.data("set-status") !== undefined) {
          _this.mapsvg.regionsRepository.findById(id).done(function (regionObject) {
            var status = t.data("set-status") + ""
            regionObject.update({ status: status })
            _this.updateDataObject(regionObject)

            // var region = _this.mapsvg.getRegion(id);
            // region.setStatus(t.data('set-status'));

            t.closest("ul").find("li").removeClass("active")
            t.parent().addClass("active")
            t.closest("td").find(".mapsvg-status-text").html(t.text())
          })
        } else {
          var region = _this.mapsvg.getRegion(id)
          _this.editRegion(region)
        }
      })

    this.view.on("click", ".mapsvg-data-btn", function (e) {
      e.preventDefault()
      var id = $(this).closest("tr").data("region-id")
      $('#mapsvg-tabs-menu a[href="#tab_database"]').tab("show")
      $("#mapsvg-data-search").val(id).trigger("keyup")
    })

    $(window).on("keydown.form.regions-list", (e) => {
      // @ts-ignore
      if ((e.metaKey || e.ctrlKey) && e.keyCode == 13)
        // @ts-ignore
        this.formBuilder && this.formBuilder.save()
      else if (e.keyCode == 27)
        // @ts-ignore
        this.formBuilder && this.formBuilder.close()
    })
  }

  MapSVGAdminRegionsListController.prototype.updateDataRow = function (region, row) {
    var _this = this
    var regionOpts = _this.mapsvg.getData().options.regions

    var data = {
      fields: _this.getDataFieldsForTemplate(true), //_this.database.getSchema().getColumns({visible: true}),
      region: region.getData(),
    }
    data.id = region.id
    data.fill =
      regionOpts[region.id] && regionOpts[region.id].style && regionOpts[region.id].style.fill
        ? regionOpts[region.id].style.fill
        : null

    var newRow = $(_this.templates.item(data))

    row =
      row ||
      $(
        "#mapsvg-region-" +
          mapsvg.utils.strings.toSnakeCase(region.id).replace(/(:|\(|\)|\.|\[|\]|,|=|@)/g, "\\$1"),
      )
    row.replaceWith(newRow)
    newRow.addClass("mapsvg-row-updated")

    setTimeout(function () {
      newRow.removeClass("mapsvg-row-updated")
    }, 2600)
  }

  MapSVGAdminRegionsListController.prototype.editRegion = function (region, scrollTo) {
    var _this = this

    var row = _this.view.find(
      "#mapsvg-region-" +
        mapsvg.utils.strings.toSnakeCase(region.id).replace(/(:|\(|\)|\.|\[|\]|,|=|@)/g, "\\$1"),
    )

    if (_this.tableDataActiveRow) _this.tableDataActiveRow.removeClass("mapsvg-row-selected")

    if (region && !region.selected) _this.mapsvg.selectRegion(region)

    if (row) {
      _this.updateScroll()
      if (scrollTo) _this.contentWrap.data("jsp").scrollToElement(row, true, false)
      _this.tableDataActiveRow = row
      _this.tableDataActiveRow.addClass("mapsvg-row-selected")
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
    }
    if (_this.formContainer) _this.formContainer.empty().remove()

    this.formContainer = _this.formContainer || $('<div class="mapsvg-modal-edit"></div>')
    this.view.append(_this.formContainer)

    this.formBuilder = new mapsvg.formBuilder({
      container: _this.formContainer,
      schema: this.database.getSchema(),
      editMode: false,
      mapsvg: _this.mapsvg,
      mediaUploader: mediaUploader,
      data: region.getModel().getData(),
      admin: _this.admin,
      events: {
        save: ({ data: { formBuilder, data } }) => {
          this.updateDataObject(data)
          formBuilder.close()
        },
        close: function () {
          _this.closeFormHandler()
        },
      },
    })
    this.formBuilder.init()
  }

  MapSVGAdminRegionsListController.prototype.updateDataObject = function (data) {
    var _this = this

    _this.mapsvg.regionsRepository.findById(data.id).done((regionFromRepo) => {
      regionFromRepo.update(data)
      this.mapsvg.regionsRepository.update(regionFromRepo).fail(function (response) {
        MapSVG.handleFailedRequest(response)
      })
      this.updateDataRow(regionFromRepo)
    })
    this.closeFormHandler()

    // var region = this.mapsvg.getRegion(obj.id);
    // var regionFromRepo = this.mapsvg.regionsRepository.findById(obj.id);
    // var data = {data: obj};
    // if(obj.status)
    //     data.status = obj.status;

    // region.update(data);

    // obj.region_title = region.title || '';
  }

  MapSVGAdminRegionsListController.prototype.closeFormHandler = function () {
    var _this = this
    if (_this.formBuilder) {
      _this.formBuilder.destroy()
      _this.formBuilder = null
      _this.formContainer.empty().remove()
      _this.formBuilderRow && _this.formBuilderRow.remove()
    }

    // WP Media Uploader inserts a.browser links, remove them:
    $("a.browser").remove()

    // _this.tableDataActiveRow && _this.tableDataActiveRow.removeClass('mapsvg-row-selected');
    // _this.tableDataActiveRow && !_this.tableDataActiveRow.hasClass('mapsvg-row-updated') && _this.tableDataActiveRow.addClass('mapsvg-row-closed');
    // setTimeout(function(){
    //     _this.tableDataActiveRow && !_this.tableDataActiveRow.hasClass('mapsvg-row-updated') && _this.tableDataActiveRow.removeClass('mapsvg-row-closed');
    // }, 1600);
  }

  MapSVGAdminRegionsListController.prototype.viewDidAppear = function () {
    MapSVGAdminController.prototype.viewDidAppear.call(this)

    if (this.databaseTimestamp < this.database.lastChangeTime) {
      this.redrawDataList()
    }
  }
  MapSVGAdminRegionsListController.prototype.viewDidDisappear = function () {
    MapSVGAdminController.prototype.viewDidDisappear.call(this)
    this.closeFormHandler()
  }

  MapSVGAdminRegionsListController.prototype.redrawDataList = function () {
    var _this = this
    _this.redraw()

    // _this.view.find('th').each(function(i, th){
    //    $(th).width($(th).width());
    // });

    var fieldsAll = _this.database.getSchema().getColumns()
    var colsList = _this.toolbarView.find(".mapsvg-data-cols")
    colsList.empty()
    fieldsAll.forEach(function (field) {
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
  }
})(jQuery, window, window.MapSVG)
