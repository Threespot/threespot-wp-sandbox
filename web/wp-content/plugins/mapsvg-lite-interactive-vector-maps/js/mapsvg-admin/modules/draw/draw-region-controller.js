;(function ($, window, MapSVG) {
  var MapSVGAdminDrawRegionController = function (container, admin, mapsvg) {
    this.name = "draw-region"
    this.svgObject = null
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminDrawRegionController = MapSVGAdminDrawRegionController
  MapSVG.extend(MapSVGAdminDrawRegionController, window.MapSVGAdminController)

  MapSVGAdminDrawRegionController.prototype.setEventHandlers = function () {
    var _this = this
    if (this.svgObject && this.svgObject.tagName == "image") {
      _this.view.on("change", ".mapsvg-draw-image-upload", function (event) {
        $.each(event.target.files, function (index, file) {
          var reader = new FileReader()

          reader.onload = function (event) {
            var pngBase64 = event.target.result
            var image = new Image()

            image.onload = function () {
              _this.drawer.updateShape(_this.svgObject, { "xlink:href": pngBase64 })
              _this.drawer.updateShape(_this.svgObject, { width: this.width })
              _this.drawer.updateShape(_this.svgObject, { height: this.height })
              _this.view.find('[name="width"]').val(this.width)
              _this.view.find('[name="height"]').val(this.height)
            }

            image.src = pngBase64
          }
          reader.readAsDataURL(file)
        })
      })
    } else {
      _this.view
        .find(".cpicker")
        .colorpicker()
        .on("changeColor.colorpicker", function (event) {
          var input = $(this).find("input")
          if (input.val() == "") {
            $(this).find("i").css({ "background-color": "" })
          }
          _this.formToObjectUpdate({ target: input[0] })
        })
    }
  }

  MapSVGAdminDrawRegionController.prototype.setObject = function (svgObject) {
    var _this = this
    this.svgObject = svgObject

    this.setMainTemplate(svgObject.tagName)

    if (this.loaded) {
      this.redraw()
      this.setEventHandlers()
    }
  }

  MapSVGAdminDrawRegionController.prototype.destroy = function () {
    this.view.find(".cpicker").colorpicker("destroy")
    MapSVGAdminController.prototype.destroy.call(this)
  }
  MapSVGAdminDrawRegionController.prototype.viewUnloaded = function () {
    var _this = this
    MapSVGAdminController.prototype.viewUnloaded.call(_this)
  }

  MapSVGAdminDrawRegionController.prototype.setDrawer = function (drawController) {
    var _this = this
    this.drawer = drawController
  }
  MapSVGAdminDrawRegionController.prototype.formToObjectUpdater = function (data) {
    this.drawer.updateShape(this.svgObject, data)
  }

  MapSVGAdminDrawRegionController.prototype.getTemplateData = function () {
    var _this = this
    var data = {}
    if (_this.svgObject) {
      data = {
        id: $(_this.svgObject).data("new-id")
          ? $(_this.svgObject).data("new-id")
          : _this.svgObject.getAttribute("id"),
        title: _this.svgObject.getAttribute("title"),
      }
      switch (this.svgObject.tagName) {
        case "path":
        case "ellipse":
        case "circle":
        case "rect":
        case "polygon":
          data.style = {
            fill: $(_this.svgObject).css("fill") || _this.svgObject.getAttribute("fill"),
            stroke: $(_this.svgObject).css("stroke") || _this.svgObject.getAttribute("stroke"),
            "stroke-width": $(_this.svgObject).data("stroke-width"),
          }
          break
        case "image":
          ;(data.x = _this.svgObject.getAttribute("x")),
            (data.y = _this.svgObject.getAttribute("y")),
            (data.width = _this.svgObject.getAttribute("width")),
            (data.height = _this.svgObject.getAttribute("height"))
          break
      }
    }

    return data
  }
})(jQuery, window, window.MapSVG)
