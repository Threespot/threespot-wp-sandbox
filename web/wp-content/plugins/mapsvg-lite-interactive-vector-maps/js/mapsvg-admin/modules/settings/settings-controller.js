;(function ($, window, MapSVG) {
  var MapSVGAdminSettingsController = function (container, admin, mapsvg) {
    this.name = "settings"
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminSettingsController = MapSVGAdminSettingsController
  MapSVG.extend(MapSVGAdminSettingsController, window.MapSVGAdminController)

  MapSVGAdminSettingsController.prototype.getTemplateData = function () {
    let options = this.mapsvg.getOptions(true, null, this.admin.getData().optionsDelta)

    options.zoomLevels = []
    var a = 1
    while (a < 21) {
      options.zoomLevels.push(a++)
    }
    options.viewBoxObject = {
      x: options.viewBox[0],
      y: options.viewBox[1],
      width: options.viewBox[2],
      height: options.viewBox[3],
    }
    options.svgFiles = this.admin.getData().options.svgFiles
    return options
  }

  MapSVGAdminSettingsController.prototype.reloadSvgFile = function (updateTitles) {
    var server = new mapsvg.server(mapsvg.routes.api)
    server
      .post("svgfile/reload", {
        file: { relativeUrl: this.mapsvg.options.source },
        updateTitles: updateTitles,
      })
      .done(function () {
        window.location.reload()
      })
  }

  MapSVGAdminSettingsController.prototype.viewLoaded = function () {
    var _this = this

    _this.updateGaugeFields()

    this.view.find(".mapsvg-select2").mselect2()

    // Move modal to body level otherwise backdrop will cover it
    $("#reloadSvgModal").appendTo("body")

    zoomLimit = _this.mapsvg.getData().options.zoom.limit

    $("#mapsvg-controls-zoomlimit").ionRangeSlider({
      type: "double",
      grid: true,
      min: 0,
      max: 22,
      from_min: 0,
      from_max: 21,
      to_min: 1,
      to_max: 22,
      onFinish: function () {
        var limit = $("#mapsvg-controls-zoomlimit").val().split(";")
        _this.mapsvg.update({ zoom: { limit: [limit[0], limit[1]] } })
      },
      from: zoomLimit[0],
      to: zoomLimit[1],
    })

    _this.view.find(".mapsvg-current-zoomlevel").html(this.mapsvg.zoomLevel)
    _this.mapsvg.events.on("zoom", function (event) {
      _this.view.find(".mapsvg-current-zoomlevel").html(event.map.zoomLevel)
    })

    this.view.find(".geo-grid").toggle(this.mapsvg.isGeo())
    this.view.find(".svg-grid").toggle(!this.mapsvg.isGeo())
  }

  MapSVGAdminSettingsController.prototype.setEventHandlers = function () {
    var _this = this

    this.mapsvg.events.on("afterChangeBounds", (event) => {
      const { center, zoomLevel } = event.data
      _this.setViewBoxControls({ center, zoomLevel })
    })
    const data = {
      center: {
        geoPoint: this.mapsvg.getCenterGeoPoint(),
        svgPoint: this.mapsvg.getCenterSvgPoint(),
      },
      // viewBox: this.mapsvg.viewBox,
      zoomLevel: this.mapsvg.zoomLevel,
    }
    _this.setViewBoxControls(data)

    $("#mapsvg-controls-width").on("keyup", function (e) {
      _this.setHeight(e)
    })
    $("#mapsvg-controls-height").on("keyup", function (e) {
      _this.setWidth(e)
    })
    $("#mapsvg-controls-ratio").on("change", function (e) {
      _this.keepRatioClickHandler(e)
    })
    $("#size-template-values").on("click", "div", function (e) {
      const [width, height] = $(e.target)
        .text()
        .split(":")
        .map((val) => val.trim())
      const v = _this.mapsvg.viewBoxSetBySize(width, height)
      $("#mapsvg-controls-width").val(width)
      $("#mapsvg-controls-height").val(height)
      // _this.setViewBoxControls(v)
    })

    $("#mapsvg-controls-set-viewbox").on("click", function (e) {
      e.preventDefault()
      var viewBox = _this.mapsvg.getViewBox()
      _this.setViewBoxControls({ viewBox })
    })
    $("#mapsvg-controls-reset-viewbox-initial").on("click", function (e) {
      e.preventDefault()
      var v = _this.mapsvg.viewBoxReset("custom")
    })
    $("#mapsvg-controls-reset-viewbox").on("click", function (e) {
      e.preventDefault()
      var viewBox = _this.mapsvg.viewBoxReset("initial")
      _this.setViewBoxControls({ viewBox })
    })

    $("#mapsvg-controls-reset-size").on("click", function (e) {
      e.preventDefault()
      var { width, height } = _this.mapsvg.svgDefault
      const v = _this.mapsvg.viewBoxSetBySize(width, height)
      $("#mapsvg-controls-width").val(width)
      $("#mapsvg-controls-height").val(height)
    })

    this.view.on("change", "#mapsvg-header-control", function () {
      _this.admin.resizeSVGCanvas()
    })

    $("#mapsvg-controls-zoom").on("change", ":radio", function () {
      var on = MapSVG.parseBoolean($("#mapsvg-controls-zoom :radio:checked").val())
      on ? $("#mapsvg-controls-zoom-options").show() : $("#mapsvg-controls-zoom-options").hide()
      _this.admin.updateScroll()
    })
    $("#mapsvg-controls-scroll").on("change", ":radio", function () {
      var on = MapSVG.parseBoolean($("#mapsvg-controls-scroll :radio:checked").val())
      on ? $("#mapsvg-controls-scroll-options").show() : $("#mapsvg-controls-scroll-options").hide()
      _this.admin.updateScroll()
    })

    function change_file(path) {
      $.get(path, function (xmlData) {
        var $data = $(xmlData)

        // Default width/height/viewBox from SVG
        var svgTag = $data.find("svg")
        var _data = { svgDefault: {} }
        _data.$svg = svgTag

        _data.svgDefault.width = svgTag.attr("width")
        _data.svgDefault.height = svgTag.attr("height")
        _data.svgDefault.viewBox = svgTag.attr("viewBox")

        if (_data.svgDefault.width && _data.svgDefault.height) {
          _data.svgDefault.width = parseFloat(_data.svgDefault.width.replace(/px/g, ""))
          _data.svgDefault.height = parseFloat(_data.svgDefault.height.replace(/px/g, ""))
          _data.svgDefault.viewBox = _data.svgDefault.viewBox
            ? _data.svgDefault.viewBox.split(" ")
            : [0, 0, _data.svgDefault.width, _data.svgDefault.height]
        } else if (_data.svgDefault.viewBox) {
          _data.svgDefault.viewBox = _data.svgDefault.viewBox.split(" ")
          _data.svgDefault.width = parseFloat(_data.svgDefault.viewBox[2])
          _data.svgDefault.height = parseFloat(_data.svgDefault.viewBox[3])
        } else {
          alert("MapSVG needs width/height or viewBox parameter to be present in SVG file.")
          return false
        }
        _this.mapsvg.update({
          svgFileLastChanged: _this.mapsvg.svgFileLastChanged++,
          source: path,
          initialViewBox: _data.svgDefault.viewBox,
          width: _data.svgDefault.width,
          height: _data.svgDefault.height,
        })
        _this.admin.save(true).done(function () {
          _this.reloadSvgFile(true)
        })
      })
    }
    this.view.on("click", "#mapsvg-controls-file-remove", function (e) {
      change_file(mapsvg.routes.maps + "geo-calibrated/empty.svg")
    })

    const reloadTitlesModal = new bootstrap.Modal(document.getElementById("reloadSvgModal"), {
      keyboard: false,
    })
    $("#mapsvg-controls-file-reload").on("click", () => {
      reloadTitlesModal.show()
    })

    $("#mapsvg-controls-file-reload-with-titles, #mapsvg-controls-file-reload-no-titles").on(
      "click",
      function (e) {
        let updateTitles = $(this).data("update-titles")
        _this.reloadSvgFile(updateTitles)
      },
    )
    this.view.on("click", "#mapsvg-controls-file-change", function (e) {
      $("#mapsvg-hidden-file-select").show()
    })
    this.view.on("click", "#mapsvg-controls-file-hide", function (e) {
      e.preventDefault()
      $("#mapsvg-hidden-file-select").hide()
    })
    this.view
      .find("#mapsvg-select2-map")
      .mselect2()
      .on("select2:select", function () {
        var path = $(this).find("option:selected").attr("data-relative-url")
        change_file(path)
      })

    this.mapsvg.events.on("sizeChange", function () {
      _this.admin.resizeDashboard()
    })

    var thContainer = _this.view.find("#mapsvg-search-address")
    var tH = thContainer.typeahead(null, {
      name: "mapsvg-addresses",
      display: "formatted_address",
      source: (query, sync, async) => {
        MapSVG.geocode({ address: query }, async)
      },
      async: true,
      minLength: 2,
    })
    thContainer.on("typeahead:select", function (ev, item) {
      var b = item.geometry.bounds ? item.geometry.bounds : item.geometry.viewport
      const bounds = new google.maps.LatLngBounds(b.getSouthWest(), b.getNorthEast())
      if (_this.mapsvg.googleMaps.map) {
        _this.mapsvg.googleMaps.map.fitBounds(bounds)
      } else {
        const viewBox = _this.mapsvg.converter.convertGoogleBoundsToViewBox(bounds)
        _this.mapsvg.fitViewBox(viewBox)
      }
    })
  }

  MapSVGAdminSettingsController.prototype.setViewBoxControls = function (data) {
    if (data.center) {
      if (data.center.geoPoint) {
        this.view
          .find("#initial-position-geo-center")
          .html(data.center.geoPoint.lat + ", " + data.center.geoPoint.lng)
      }
      if (data.center.svgPoint) {
        this.view
          .find("#initial-position-svg-center")
          .html(data.center.svgPoint.x + ", " + data.center.svgPoint.y)
      }
    }

    if (typeof data.zoomLevel === "number") {
      this.view.find(".initial-position-zoom").html(data.zoomLevel)
    }
    if (data.viewBox) {
      $("#mapsvg-controls-viewbox").val(data.viewBox.toString()).trigger("change")
    }
  }

  MapSVGAdminSettingsController.prototype.setWidth = function () {
    var _this = this

    var w = Number($("#mapsvg-controls-width").val())
    var h = Number($("#mapsvg-controls-height").val())
    if (_this.mapsvg.options.lockAspectRatio) {
      w = Math.round((h * _this.mapsvg.svgDefault.width) / _this.mapsvg.svgDefault.height)
      $("#mapsvg-controls-width").val(w)
    }
    _this.mapsvg.viewBoxSetBySize(w, h)
    _this.admin.resizeDashboard()
  }
  MapSVGAdminSettingsController.prototype.setHeight = function () {
    var _this = this

    var w = Number($("#mapsvg-controls-width").val())
    var h = Number($("#mapsvg-controls-height").val())

    if (_this.mapsvg.options.lockAspectRatio) {
      h = Math.round((w * _this.mapsvg.svgDefault.height) / _this.mapsvg.svgDefault.width)
      $("#mapsvg-controls-height").val(h)
    }
    _this.mapsvg.viewBoxSetBySize(w, h)
    _this.admin.resizeDashboard()
  }
  MapSVGAdminSettingsController.prototype.keepRatioClickHandler = function () {
    var _this = this
    if ($("#mapsvg-controls-ratio").is(":checked")) {
      _this.setHeight()
    }
  }
  MapSVGAdminSettingsController.prototype.setWidthViewbox = function () {
    var _this = this
    if ($("#mapsvg-controls-ratio").is(":checked"))
      var k = _this.mapsvg.getData().svgDefault.width / _this.mapsvg.getData().svgDefault.height
    else var k = $("#map_width").val() / $("#map_height").val()

    var new_width = Math.round($("#viewbox_height").val() * k)

    if (new_width > _this.mapsvg.getData().svgDefault.viewBox[2]) {
      new_width = _this.mapsvg.getData().svgDefault.viewBox[2]
      var new_height = _this.mapsvg.getData().svgDefault.viewBox[3] * k
      $("#viewbox_height").val(new_height)
    }

    $("#viewbox_width").val(new_width)
  }
  MapSVGAdminSettingsController.prototype.setViewBoxRatio = function () {
    var _this = this
    var mRatio = $("#map_width").val() / $("#map_height").val()
    var vRatio = $("#viewbox_width").val() / $("#viewbox_height").val()

    if (mRatio != vRatio) {
      if (mRatio >= vRatio) {
        // viewBox is too tall
        $("#viewbox_height").val(_this.mapsvg.getData().svgDefault.viewBox[2] * mRatio)
      } else {
        // viewBox is too wide
        $("#viewbox_width").val(_this.mapsvg.getData().svgDefault.viewBox[3] / mRatio)
      }
    }
  }
  MapSVGAdminSettingsController.prototype.setHeightViewbox = function () {
    var _this = this

    if ($("#mapsvg-controls-ratio").is(":checked"))
      var k = _this.mapsvg.getData().svgDefault.height / _this.mapsvg.getData().svgDefault.width
    else var k = $("#map_height").val() / $("#map_width").val()

    var new_height = Math.round($("#viewbox_width").val() * k)

    if (new_height > _this.mapsvg.getData().svgDefault.viewBox[3]) {
      new_height = _this.mapsvg.getData().svgDefault.viewBox[3]
      var new_width = _this.mapsvg.getData().svgDefault.viewBox[2] * k
      $("#viewbox_width").val(new_width)
    }

    $("#viewbox_height").val(new_height)
  }

  MapSVGAdminSettingsController.prototype.updateGaugeFields = function () {
    var _this = this
    var fields = _this.mapsvg.regionsRepository.getSchema().getFields()
    var choroplethField = _this.mapsvg.options.regionChoroplethField
    var select = this.view.find("#mapsvg-region-data-fields").empty()
    select.append("<option></option>")
    fields.forEach(function (f) {
      select.append(
        "<option " + (choroplethField == f.name ? "selected" : "") + ">" + f.name + "</option>",
      )
    })
    select.trigger("change")
  }
})(jQuery, window, window.MapSVG)
