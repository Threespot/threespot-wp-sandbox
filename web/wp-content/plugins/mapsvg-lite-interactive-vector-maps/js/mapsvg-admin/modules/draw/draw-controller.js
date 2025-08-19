;(function ($, window, MapSVG) {
  var MapSVGAdminDrawController = function (container, admin, map) {
    this.name = "draw"
    this.map = $(map.containers.map)
    this.svg = $(map.containers.svg)
    this.changed = false
    this.scrollable = false
    this.changes = []
    this.mode = "edit"
    this.snap = true
    this.lastClickTime = 0
    this.lastChangeTime = 0
    this.hints = []
    this.hintHover = ""

    MapSVGAdminController.call(this, container, admin, map)
    this.filepath = this.mapsvg.options.source
    if (this.filepath.indexOf(mapsvg.routes.uploads) == -1) {
      this.readOnly = true
    }
  }
  window.MapSVGAdminDrawController = MapSVGAdminDrawController
  MapSVG.extend(MapSVGAdminDrawController, window.MapSVGAdminController)

  MapSVGAdminDrawController.prototype.viewDidAppear = function () {
    var _this = this
    MapSVGAdminController.prototype.viewDidAppear.call(this)
    this.defScroll = _this.mapsvg.options.scroll.on
    _this.mapsvg.setScroll({ on: false })
    $("#mapsvg-save").hide()
    $("#mapsvg-save-svg").show()
    if (_this.readOnly) {
      _this.setReadOnly(true)
    }

    // Revert colors and statues of regions
    //        this.mapsvg.regions.forEach(function(r){r.setFill()});

    this.loaded && _this.setEventHandlers()

    this.reindex()
  }

  MapSVGAdminDrawController.prototype.reindex = function (on) {
    var _this = this
    var elems = this.svg[0].getElementsByTagName("*")
    for (var i = 0; i < elems.length; i++) {
      $(elems[i]).data("index", i)
      _this.lastindex = i
    }
  }

  MapSVGAdminDrawController.prototype.viewUnloaded = function (on) {
    var _this = this

    MapSVGAdminController.prototype.viewUnloaded.call(_this)

    $("#mapsvg-save-svg").hide()
    $("#mapsvg-save").show()

    // Revert colors and statues of regions
    //        this.mapsvg.setColorsIgnore(false);
    //        this.mapsvg.regions.forEach(function(r){r.setFill()});

    _this.mapsvg.eventsRestore()
    _this.mapsvg.setScroll({ on: this.defScroll })

    this.resaveMsg && this.resaveMsg.remove()

    if (_this.editingShape) {
      if (_this.editingShape.unclosed) {
        _this.removeShape(_this.editingShape.obj)
      } else {
        _this.editingShapeUnset()
      }
    }

    if (_this.controllers.region) {
      _this.admin.unloadController(_this.controllers.region)
      _this.controllers.region = null
    }

    $(window).off(".draw.mapsvg")
    this.map.off(".draw.mapsvg")
    this.view && this.view.off()
    $("body").off("click.mapsvg-save-svg")
    $("body").off("click.mapsvg-save-svg-copy")

    _this.hide()
    _this.viewDidDisappear()
  }

  MapSVGAdminDrawController.prototype.viewLoaded = function (on) {
    MapSVGAdminController.prototype.viewLoaded.call(this)
    this.hint()
  }

  MapSVGAdminDrawController.prototype.setReadOnly = function (on) {
    if (on) {
      this.readOnly = true
      $("#mapsvg-admin").addClass("mapsvg-read-only")
      $("#mapsvg-save-svg").prop("disabled", true)
      this.resaveMsg = $('<div id="mapsvg-save-svg-copy-message" class="well"></div>')
      this.resaveMsg.html(
        'This SVG file is "read-only". Save a copy of the file into WP Uploads folder?<br /><br />',
      )
      var btn = $(
        '<button class="btn btn-sm btn-primary" id="mapsvg-save-svg-copy" data-loading-text="Saving...">Save</button>',
      )
      this.resaveMsg.append(btn)
      $("#mapsvg-admin").append(this.resaveMsg)
    } else {
      if (this.readOnly) {
        this.readOnly = false
        $("#mapsvg-admin").removeClass("mapsvg-read-only")
        $("#mapsvg-save-svg").prop("disabled", false)
        this.resaveMsg.remove()
      }
    }
  }

  MapSVGAdminDrawController.prototype.clickToSVG = function (e) {
    var _this = this
    var mc = mapsvg.utils.env.getMouseCoords(e)
    var x = mc.x - _this.map.offset().left
    var y = mc.y - _this.map.offset().top
    //var screenPoint = new mapsvg.screenPoint(x,y);
    return _this.mapsvg.getConverter().convertPixelToSVG({ x: x, y: y })
  }

  MapSVGAdminDrawController.prototype.setEventHandlers = function () {
    var _this = this

    $(window).off(".draw.mapsvg")
    this.map.off(".draw.mapsvg")
    this.view.off()

    this.view
      .on("mouseenter", "#mapsvg-draw-tools .btn", function (e) {
        if ($(this).data("hint")) {
          _this.hintHover = $(this).data("hint")
          _this.hint()
        }
      })
      .on("mouseleave", "#mapsvg-draw-tools .btn", function (e) {
        if ($(this).data("hint")) {
          _this.hintHover = null
          _this.hint()
        }
      })

    switch (this.mode) {
      case "draw":
        _this.mapsvg.eventsPrevent("click")
        _this.mapsvg.eventsPrevent("mouseover")
        _this.mapsvg.eventsPrevent("mouseout")

        var lastClickTime = 0
        _this.map.on("click.draw.mapsvg", function (e) {
          var coords = _this.clickToSVG(e)
          var thisClickTime = new Date().getTime()

          if (thisClickTime - _this.lastClickTime < 300) {
            // Double click
            _this.closePath()
          } else {
            // Single click
            if (!_this.editingShape) {
              _this.startPath(coords)
            } else {
              _this.addPointToPath(_this.editingShape.lastPoint)
            }
          }
          _this.lastClickTime = thisClickTime
        })
        break
      case "edit":
        _this.mapsvg.eventsPrevent("click")
        _this.mapsvg.eventsPrevent("mouseover")
        _this.mapsvg.eventsPrevent("mouseout")
        // _this.mapsvg.$map.on('click.draw.mapsvg','.mapsvg-region',function(){
        //     _this.editShape(this);
        // });

        // Delete
        $(window).on("keyup.draw.mapsvg", function (e) {
          e.preventDefault()
          // TODO may need to change form-control class after moving to BS5!
          if (!$(e.target).hasClass("form-control") && e.keyCode == 8) {
            if (_this.editingPoint) {
              _this.removeEditingPoint()
            } else if (_this.editingShape) {
              _this.removeShape(_this.editingShape.obj)
            }
          }
        })

        // Highlight
        $(_this.map).on("mouseover.draw.mapsvg", ".mapsvg-region", function () {
          // if(_this.isDragging || (_this.editingShape && _this.editingShape.obj === this))
          //     return;
          // !$(this).data('defaultCss') && $(this).data('defaultCss', $(this).attr('style'));
          // $(this).css({
          //     stroke: 'black',
          //     'stroke-width': 1/_this.mapsvg.scale+'px'
          // });
        })
        $(_this.map).on("mouseout.draw.mapsvg", ".mapsvg-region", function () {
          // if(_this.isDragging || (_this.editingShape && _this.editingShape.obj === this))
          //     return;
          // $(this).prop('style',$(this).data('defaultCss'));
        })

        // Drag point
        // _this.map.on('mousedown.draw.mapsvg','.mapsvg-path-point',function(e){
        //     e.originalEvent.preventDefault();
        //     _this.dragPointStart(e);
        // });

        // Drag shape
        // _this.map.on('mousedown.draw.mapsvg','.mapsvg-custom-shape',function(e){
        _this.map.on("mousedown.draw.mapsvg", function (e) {
          e.originalEvent.preventDefault()

          if ($(e.target).hasClass("mapsvg-path-point-new")) {
            var index = _this.addNewPoint(
              _this.editingShape.newPoint.getAttribute("cx"),
              _this.editingShape.newPoint.getAttribute("cy"),
              e,
            )
            _this.dragPointStart(e, index)
            return
          } else if ($(e.target).hasClass("mapsvg-path-point")) {
            _this.dragPointStart(e)
            return
          }

          if (
            ["path", "image", "circle", "polyline", "polygon", "ellipse", "rect"].indexOf(
              e.target.tagName,
            ) === -1
          ) {
            return false
          }

          if (!_this.editingShape || _this.editingShape.obj !== this) {
            _this.editShape(e.target)
          }
          if (_this.editingPoint) {
            _this.editingPointUnset()
          }

          if (
            ["path", "image", "polygon", "polyline", "rect", "ellipse", "circle"].indexOf(
              e.target.tagName,
            ) !== -1
          ) {
            _this.dragShapeStart(e)
          }
        })

        // TODO check why just .mapsvg-image-background?
        _this.map.on("mouseup.draw.mapsvg", ".mapsvg-image-background", function (e) {
          e.originalEvent.preventDefault()

          if (!_this.editingShape || _this.editingShape.obj !== this) {
            _this.editShape(this)
          }

          // _this.dragShapeStart(e);
          // _this.map.on('mousemove.2.draw.mapsvg',function(_e){
          //     _this.dragShapeMove(_e);
          // });
          // _this.map.on('mouseup.2.draw.mapsvg',function(_e){
          //     _this.dragShapeEnd(_e);
          //     // _this.editShape(_this.editingShape.obj);
          // });
        })

        if (_this.editingShape) {
          $(window).off("keyup.esc.draw.mapsvg")
          $(window).on("keyup.esc.draw.mapsvg", function (e) {
            if (e.keyCode == 27) {
              if (_this.editingShape) {
                _this.editingShapeUnset()
              }
            }
          })

          if (_this.editingShape.obj.tagName == "path") {
            // Add new point
            _this.map.on("mousemove.draw.mapsvg", function (e) {
              if (_this.isDragging) {
                return
              }
              var point = _this.mapsvg.convertMouseToSVG(e)
              // check if mouse is on path line
              var inter = _this.getIntersectionWithEditingShape(point)
              // check if mouse is not too close to vertex points
              if (inter) {
                var scale = _this.mapsvg.getScale()
                _this.editingShape.points.forEach(function (point) {
                  var x = point.getAttribute("cx")
                  var y = point.getAttribute("cy")
                  if (Math.abs(inter.x - x) < 10 / scale && Math.abs(inter.y - y) < 10 / scale) {
                    inter = false
                  }
                })
              }

              if (inter) {
                _this.editingShape.newPoint.setAttribute("cx", inter.x)
                _this.editingShape.newPoint.setAttribute("cy", inter.y)
                _this.editingShape.newPoint.style.display = "block"
              } else {
                _this.editingShape.newPoint.style.display = "none"
              }
            })
            // $(_this.editingShape.newPoint).on('mousedown',function(e){
            //     var index = _this.addNewPoint(_this.editingShape.newPoint.getAttribute('cx'),_this.editingShape.newPoint.getAttribute('cy'), e);
            //     _this.dragPointStart(e, index);
            // });
          }
        }

        break
      default:
        break
    }

    $("body").off("click.mapsvg-save-svg")
    $("body").on("click.mapsvg-save-svg", "#mapsvg-save-svg", function () {
      _this.saveSvg()
    })
    this.view.on("change", "#mapsvg-draw-mode input", function (e) {
      _this.setMode($(this).val())
    })
    this.view.on("change", "#mapsvg-draw-btn-magnet", function () {
      _this.setSnap($(this).prop("checked"))
    })
    this.view.on("click", "#mapsvg-draw-undo", function () {
      _this.undo()
    })
    if (_this.readOnly) {
      $("body").on("click.mapsvg-save-copy", "#mapsvg-save-svg-copy", function () {
        $(this).buttonLoading(true)
        var server = new mapsvg.server(mapsvg.routes.api)
        server
          .post("svgfile/copy", { file: { path: _this.filepath } })
          .done(function (data) {
            // data = JSON.parse(data);
            if (data.file && data.file.relativeUrl) {
              _this.filepath = data.file.relativeUrl
              _this.mapsvg.update({ source: _this.filepath })
              _this.admin.save(true)
              _this.setReadOnly(false)
            } else {
              if (data.error) alert(data.error)
            }
          })
          .always(function () {
            $("#mapsvg-save-svg-copy").buttonLoading(false)
          })
      })
    }
  }

  MapSVGAdminDrawController.prototype.drawStart = function (e) {}
  MapSVGAdminDrawController.prototype.drawMove = function (e) {}
  MapSVGAdminDrawController.prototype.drawEnd = function (e) {}
  MapSVGAdminDrawController.prototype.dragPointStart = function (e, index) {
    var _this = this

    _this.isDragging = true
    var index = index !== undefined ? index : $(e.target).data("index")
    _this.editingShape.pathData = _this.editingShape.obj.getPathData({ normalize: true })
    var point = _this.editingShape.pathData[index]

    if (_this.editingPoint) {
      _this.editingPointUnset()
    }

    var circle = _this.editingShape.points[index]
    $(circle).addClass("active")

    _this.editingPoint = {
      pointOriginal: $.extend(true, {}, point),
      circle: circle,
      index: index,
    }

    _this.dragStartMouseCoords = _this.clickToSVG(e)
    _this.editingShape.d = _this.editingShape.obj.getAttribute("d")

    _this.loadSnapPoints()

    _this.map.on("mousemove.dragpoint.draw.mapsvg", function (_e) {
      _this.dragPointMove(_e)
    })
    _this.map.on("mouseup.dragpoint.draw.mapsvg", function (_e) {
      _this.dragPointEnd(_e, point)
    })
    _this.hint()
  }
  MapSVGAdminDrawController.prototype.dragPointMove = function (e) {
    var _this = this
    var coords = _this.clickToSVG(e)

    var d = {
      x: coords.x - _this.dragStartMouseCoords.x,
      y: coords.y - _this.dragStartMouseCoords.y,
    }
    var point = $.extend(true, {}, _this.editingPoint.pointOriginal)

    point.values[0] += d.x
    point.values[1] += d.y

    // Do snap
    if (this.snap) {
      point.values = _this.doSnap(point.values)
    }

    _this.editingPoint.circle.setAttribute("cx", point.values[0])
    _this.editingPoint.circle.setAttribute("cy", point.values[1])

    _this.editingShape.pathData[_this.editingPoint.index] = point
    _this.editingShape.obj.setPathData(_this.editingShape.pathData)
  }
  MapSVGAdminDrawController.prototype.dragPointEnd = function (e) {
    var _this = this
    _this.map.off("mousemove.dragpoint.draw.mapsvg")
    _this.map.off("mouseup.dragpoint.draw.mapsvg")
    this.isDragging = false
    var coords = _this.clickToSVG(e)

    if (coords.x != _this.dragStartMouseCoords.x && coords.y != _this.dragStartMouseCoords.y) {
      _this.addChange(
        "update",
        _this.editingShape.obj,
        { d: _this.editingShape.obj.getAttribute("d") },
        { d: _this.editingShape.d },
      )
    }
    _this.loadIntersectionPoints()
  }

  MapSVGAdminDrawController.prototype.getDragShapeProperties = function (e) {
    var _this = this
    switch (_this.editingShape.obj.tagName) {
      case "path":
        return { d: _this.editingShape.obj.getAttribute("d") }
      case "ellipse":
      case "circle":
        return {
          cx: parseFloat(_this.editingShape.obj.getAttribute("cx")),
          cy: parseFloat(_this.editingShape.obj.getAttribute("cy")),
        }
      case "image":
      case "rect":
        return {
          x: parseFloat(_this.editingShape.obj.getAttribute("x")),
          y: parseFloat(_this.editingShape.obj.getAttribute("y")),
        }
      case "polygon":
      case "polyline":
        return { points: parseFloat(_this.editingShape.obj.getAttribute("points")) }
      default:
        break
    }
  }

  MapSVGAdminDrawController.prototype.dragShapeStart = function (e) {
    var _this = this

    this.isDragging = true

    // _this.editingShape.points && _this.editingShape.points.forEach(function(point){
    //     _this.svg[0].removeChild(point);
    // });
    // _this.editingShape.points = [];

    _this.dragXY = _this.clickToSVG(e)
    _this.dragStartMouseCoords = _this.dragXY
    _this.editingShape.dragProperties = _this.getDragShapeProperties()
    _this.map.on("mousemove.shape_draw.mapsvg", function (_e) {
      _this.dragShapeMove(_e)
    })
    _this.map.on("mouseup.shape_draw.mapsvg", function (_e) {
      _this.dragShapeEnd(_e)
    })
  }
  MapSVGAdminDrawController.prototype.dragShapeMove = function (e, shape, start) {
    var _this = this
    var coords = _this.clickToSVG(e)
    var d = { x: coords.x - _this.dragXY.x, y: coords.y - _this.dragXY.y }

    switch (_this.editingShape.obj.tagName) {
      case "path":
        var points = _this.editingShape.obj.getPathData({ normalize: true })
        points.forEach(function (point, index) {
          if (point.values && point.values.length) {
            for (var i in point.values) {
              point.values[i] += i % 2 ? d.y : d.x
            }
            var x = point.values[point.values.length - 2]
            var y = point.values[point.values.length - 1]
            if (_this.editingShape.points[index]) {
              _this.editingShape.points[index].setAttribute("cx", point.values[0])
              _this.editingShape.points[index].setAttribute("cy", point.values[1])
            }
          }
        })
        _this.editingShape.obj.setPathData(points)
        break
      case "ellipse":
      case "circle":
        var x = parseFloat(_this.editingShape.obj.getAttribute("cx")) + d.x
        var y = parseFloat(_this.editingShape.obj.getAttribute("cy")) + d.y
        _this.editingShape.obj.setAttribute("cx", x)
        _this.editingShape.obj.setAttribute("cy", y)
        break
      case "image":
      case "rect":
        var x = parseFloat(_this.editingShape.obj.getAttribute("x")) + d.x
        var y = parseFloat(_this.editingShape.obj.getAttribute("y")) + d.y
        _this.editingShape.obj.setAttribute("x", x)
        _this.editingShape.obj.setAttribute("y", y)
        break
      case "polygon":
      case "polyline":
        var points = _this.editingShape.obj.getAttribute("points").replace(/\s\s+/g, " ").split(" ")
        for (var i in points) {
          var point = points[i].split(",")
          point[0] = parseFloat(point[0]) + d.x
          point[1] = parseFloat(point[1]) + d.y
          points[i] = point.join(",")
        }
        _this.editingShape.obj.setAttribute("points", points.join(" "))
        break
      default:
        break
    }
    _this.dragXY = coords
  }
  MapSVGAdminDrawController.prototype.dragShapeEnd = function (e) {
    var _this = this
    _this.map.off("mousemove.shape_draw.mapsvg")
    _this.map.off("mouseup.shape_draw.mapsvg")
    this.isDragging = false
    var coords = _this.clickToSVG(e)
    if (coords.x != _this.dragStartMouseCoords.x && coords.y != _this.dragStartMouseCoords.y) {
      _this.addChange(
        "update",
        _this.editingShape.obj,
        _this.getDragShapeProperties(),
        _this.editingShape.dragProperties,
      )
    }
    if (_this.editingShape.obj.tagName == "path") {
      _this.loadIntersectionPoints()
    }
  }

  MapSVGAdminDrawController.prototype.editShape = async function (svgObject) {
    var _this = this
    _this.editingShapeUnset()

    _this.editingShape = { obj: svgObject }

    // convert polygon to path
    if (svgObject.tagName == "polygon" || svgObject.tagName == "polyline") {
      var path = document.createElementNS("http://www.w3.org/2000/svg", "path")
      for (var attribute in svgObject.attributes) {
        if (!["points"].includes(attribute.name)) {
          path.setAttribute(attribute.name, attribute.value)
        }
      }
      path.setPathData(svgObject.getPathData({ normalized: true }))

      _this.removeShape(svgObject)
      var parentNode = svgObject.parentNode

      svgObject = path

      parentNode.appendChild(svgObject)
      _this.addChange("create", svgObject)
      _this.reindex()
      var index = $(_this.changes[_this.changes.length - 1].data).data("index")
    }

    _this.editingShape = { obj: svgObject }

    var cssClass = svgObject.getAttribute("class") || ""
    if (!cssClass || cssClass.indexOf("active") == -1) {
      svgObject.setAttribute("class", cssClass + " active")
    }

    if (_this.controllers.region) {
      _this.admin.unloadController(_this.controllers.region)
      _this.controllers.region = null
    }

    // if(!_this.controllers.region){
    _this.controllers.region = await _this.admin.loadController("tab_draw_region", "draw-region")
    _this.controllers.region.events = {
      onload: function () {
        this.setObject(svgObject)
      },
    }
    _this.controllers.region.setDrawer(_this)
    // }else{
    //     _this.admin.loadController('tab_draw_region','drawRegion');
    //     _this.controllers.region.setObject(svgObject);
    // }

    if (svgObject.tagName == "path") {
      var path = svgObject.getPathData({ normalize: true })
      _this.editingShape.points = []

      path.forEach(function (_point, index) {
        if (_point.values && _point.values.length) {
          var point = document.createElementNS(_this.svg[0].namespaceURI, "circle")
          var x = _point.values[_point.values.length - 2]
          var y = _point.values[_point.values.length - 1]
          point.setAttribute("cx", x)
          point.setAttribute("cy", y)
          point.setAttribute("r", 5 / _this.mapsvg.getScale())
          point.setAttribute("class", "mapsvg-path-point")
          $(point).css("stroke-width", 1)
          $(point).attr("data-stroke-width", 1)
          $(point).data("index", index)
          _this.svg[0].appendChild(point)
          _this.editingShape.points.push(point)
        }
      })

      // new Point placeholder
      var point = document.createElementNS(_this.svg[0].namespaceURI, "circle")
      point.setAttribute("cx", 0)
      point.setAttribute("cy", 0)
      point.setAttribute("r", 5 / _this.mapsvg.getScale())
      point.setAttribute("class", "mapsvg-path-point mapsvg-path-point-new")
      $(point).css("stroke-width", 1)
      $(point).attr("data-stroke-width", 1)
      $(point).hide()
      _this.svg[0].appendChild(point)
      _this.editingShape.newPoint = point

      _this.loadIntersectionPoints()
      _this.mapsvg.adjustStrokes()

      _this.adjustPointsSize = () => {
        var _this = this
        if (_this.editingShape) {
          _this.editingShape.points &&
            _this.editingShape.points.forEach(function (point) {
              point.setAttribute("r", 5 / _this.mapsvg.getScale())
              $(point).css("stroke-width", 1 / _this.mapsvg.getScale())
            })
          if (_this.editingShape.newPoint) {
            _this.editingShape.newPoint.setAttribute("r", 5 / _this.mapsvg.getScale())
            $(_this.editingShape.newPoint).css("stroke-width", 1 / _this.mapsvg.getScale())
          }
        }
      }

      _this.mapsvg.events.off("zoom", _this.adjustPointsSize)
      _this.mapsvg.events.on("zoom", _this.adjustPointsSize)
    }

    _this.setEventHandlers()

    this.hint()
  }

  MapSVGAdminDrawController.prototype.adjustPointsSize = function () {
    var _this = this
    if (_this.editingShape) {
      _this.editingShape.points &&
        _this.editingShape.points.forEach(function (point) {
          point.setAttribute("r", 5 / _this.mapsvg.getScale())
          $(point).css("stroke-width", 1 / _this.mapsvg.getScale())
        })
      if (_this.editingShape.newPoint) {
        _this.editingShape.newPoint.setAttribute("r", 5 / _this.mapsvg.getScale())
        $(_this.editingShape.newPoint).css("stroke-width", 1 / _this.mapsvg.getScale())
      }
    }
  }

  MapSVGAdminDrawController.prototype.editingPointUnset = function () {
    var _this = this
    $(_this.editingPoint.circle).removeClass("active")
    _this.editingPoint = null
    this.hint()
  }

  MapSVGAdminDrawController.prototype.editingShapeUnset = function () {
    var _this = this
    if (_this.editingShape && _this.editingShape.obj) {
      _this.mapsvg.events.off("zoom", _this.adjustPointsSize)

      _this.editingShape.points &&
        _this.editingShape.points.forEach(function (point) {
          _this.svg[0].removeChild(point)
        })
      if (_this.editingShape.newPoint) {
        $(_this.editingShape.newPoint).remove()
        _this.editingShape.newPoint = null
      }
      if (_this.editingShape.stroke) {
        _this.svg[0].removeChild(_this.editingShape.stroke)
      }
      // $(this).prop('style',$(this).data('defaultCss'));
      // $(this).data('defaultCss', null);
      if (_this.editingShape.obj.getAttribute("class").indexOf("active") != -1) {
        _this.editingShape.obj.setAttribute(
          "class",
          _this.editingShape.obj.getAttribute("class").replace(/active/g, ""),
        )
      }
    }
    _this.map.off("mousemove.draw.mapsvg")
    this.editingShape = null
    _this.editingPoint = null
    this.hint()
  }
  MapSVGAdminDrawController.prototype.setMode = function (mode) {
    this.mode = mode
    this.editingShapeUnset()
    this.setEventHandlers()
    this.hint()
  }
  MapSVGAdminDrawController.prototype.hint = function (hint) {
    var _this = this
    if (this.hintHover) {
      $("#mapsvg-draw-status-line").html(this.hintHover)
    } else {
      switch (this.mode) {
        case "draw":
          if (!this.editingShape || !this.editingShape.obj) {
            this.setHint(
              "<code>Click</code> to start drawing. Hold <code>Spacebar</code> to scroll.",
            )
          } else {
            this.setHint(
              "<code>Click</code> to add point. <code>Enter</code>: close path. <code>Double Click</code>: add point & close path. <code>Esc</code>: cancel. Hold <code>Spacebar</code> to scroll.",
            )
          }
          break
        case "edit":
          if (!this.editingShape || !this.editingShape.obj) {
            this.setHint(
              "<code>Click</code> on an object to edit it. Hold  <code>Spacebar</code> to scroll.",
            )
          } else {
            if (_this.editingPoint) {
              this.setHint(
                "<b>Point</b>: <code>Backspace</code>: delete point. Hold  <code>Spacebar</code> to scroll.",
              )
            } else {
              var shapeName = this.editingShape.obj.tagName
              var hint = "<b>" + this.editingShape.obj.tagName + "</b>"
              // if(shapeName == 'path'){
              hint +=
                ": <code>Drag</code> the object" +
                (shapeName == "path" ? " or its points" : "") +
                ". <code>Backspace</code>: delete object. Hold  <code>Spacebar</code> to scroll."
              // }
              this.setHint(hint)
            }
          }

          break
      }
    }
  }
  MapSVGAdminDrawController.prototype.setHint = function (hint) {
    $("#mapsvg-draw-status-line").html(hint)
  }
  MapSVGAdminDrawController.prototype.setSnap = function (on) {
    this.snap = on
    if (this.snap) this.loadSnapPoints()
  }
  MapSVGAdminDrawController.prototype.startPath = function (coords) {
    var _this = this

    _this.editingShape = {}
    _this.editingShape.unclosed = true
    _this.editingShape.obj = document.createElementNS(_this.svg[0].namespaceURI, "path")
    _this.editingShape.obj.setAttribute("d", "M" + coords.x + " " + coords.y + " ")
    _this.editingShape.d = _this.editingShape.obj.getAttribute("d")
    _this.editingShape.lastPoint = coords
    _this.editingShape.obj.setAttribute("class", "active mapsvg-custom-shape mapsvg-region")
    $(_this.editingShape.obj)
      .css({
        fill: "rgba(255,0,0,0.4)",
        stroke: "rgba(255,100,100,0.4)",
        "stroke-width": 1,
      })
      .attr("data-stroke-width", 1)
    _this.svg[0].appendChild(_this.editingShape.obj)
    _this.mapsvg.adjustStrokes()

    _this.loadSnapPoints()
    _this.map.on("mousemove.draw.mapsvg", function (e) {
      _this.moveLastPathPoint(e)
    })
    $(window).off("keyup.draw.mapsvg")
    $(window).on("keyup.draw.mapsvg", function (e) {
      if (e.keyCode == 13) {
        _this.closePath()
      } else if (e.keyCode == 27) {
        if (_this.editingShape) {
          if (_this.editingShape.unclosed) {
            _this.removeShape(_this.editingShape.obj)
            _this.map.off("mousemove.draw.mapsvg")
          } else {
            _this.editingShapeUnset()
          }
        }
      }
    })

    this.hint()

    return _this.editingShape
  }

  MapSVGAdminDrawController.prototype.moveLastPathPoint = function (e) {
    var _this = this
    _this.editingShape.lastPoint = _this.clickToSVG(e)

    // Do snap
    if (this.snap) {
      _this.editingShape.lastPoint = _this.doSnap(_this.editingShape.lastPoint)
    }
    _this.editingShape.obj.setAttribute(
      "d",
      _this.editingShape.d +
        "L" +
        _this.editingShape.lastPoint.x +
        " " +
        _this.editingShape.lastPoint.y +
        " ",
    )
  }
  MapSVGAdminDrawController.prototype.closePath = function () {
    var _this = this
    _this.editingShape.obj.setAttribute(
      "d",
      _this.editingShape.d +
        "L" +
        _this.editingShape.lastPoint.x +
        " " +
        _this.editingShape.lastPoint.y +
        " ",
    )
    _this.editingShape.obj.setAttribute("d", _this.editingShape.d + "z")
    var id = prompt("Enter Region ID")
    id = id.replace(/\s/g, "_")
    if (id) {
      _this.editingShape.obj.setAttribute("id", id)
      _this.addChange("create", _this.editingShape.obj)
      $(_this.editingShape.obj).data("index", ++_this.lastindex)
      _this.editingShape = null
    } else {
      _this.removeShape(_this.editingShape.obj)
    }
    this.hint()
    _this.map.off("mousemove.draw.mapsvg")
    $(window).off("keyup.draw.mapsvg")
  }
  MapSVGAdminDrawController.prototype.addPointToPath = function (coords) {
    var _this = this

    _this.editingShape.obj.setAttribute(
      "d",
      _this.editingShape.d + "L" + coords.x + " " + coords.y + " ",
    )
    _this.editingShape.d = _this.editingShape.obj.getAttribute("d")

    return _this.editingShape
  }
  MapSVGAdminDrawController.prototype.updateShape = function (svgObject, data, isUndo) {
    var _this = this
    var dataOld = {}
    var obj = typeof svgObject == "string" ? _this.svg.find("#" + svgObject)[0] : svgObject
    for (var prop in data) {
      if (prop === "style" && typeof data[prop] != "string") {
        dataOld.style = {}
        for (var i in data[prop]) {
          dataOld.style[i] = $(obj).css(i) || obj.getAttribute(i)
          if (obj.getAttribute(i)) obj.removeAttribute(i)
        }
        if (i === "stroke-width") {
          $(obj).attr("data-stroke-width", data[prop][i])
          _this.mapsvg.adjustStrokes()
        } else {
          $(obj).css(data[prop])
        }
        !isUndo && _this.addChange("update", obj, data, dataOld)
      } else {
        // TODO the code below will break if there are multiple properties
        if (prop == "id") {
          dataOld[prop] = _this.getObjectId(obj)
        } else {
          dataOld[prop] = obj.getAttribute(prop)
        }
        !isUndo && _this.addChange("update", obj, data, dataOld)

        if (prop == "id") {
          $(obj).data("new-id", data[prop])
        } else {
          obj.setAttribute(prop, data[prop])
        }
      }
    }
  }
  MapSVGAdminDrawController.prototype.addChange = function (type, obj, dataNew, dataOld) {
    var _this = this

    switch (type) {
      case "create":
        _this.changes.push({ action: "create", data: obj })
        break
      case "update":
        var lastChange = _this.changes[_this.changes.length - 1]
        var prop = Object.keys(dataNew)[0]
        var thisChangeTime = new Date().getTime()

        if (
          thisChangeTime - _this.lastChangeTime < 500 &&
          lastChange &&
          lastChange.action == "update" &&
          lastChange.id == _this.getObjectId(obj) &&
          lastChange.dataNew[prop]
        ) {
          // if the same parameter was changed quickly (<500ms), just overwrite previous change
          // to prevent multiple change events of the same property
          _this.changes[_this.changes.length - 1].dataNew[prop] = dataNew[prop]
        } else {
          // if new parameter was changed - add it into change chain
          _this.changes.push({
            action: "update",
            data: obj,
            idReal: obj.id,
            id: _this.getObjectId(obj),
            dataOld: dataOld,
            dataNew: dataNew,
          })
        }
        _this.lastChangeTime = thisChangeTime
        break
      case "delete":
        _this.changes.push({ action: "delete", data: obj })
        break
      default:
        break
    }
    _this.changed = true
  }

  MapSVGAdminDrawController.prototype.addUpdateChange = function (obj, dataOld, dataNew) {}

  // Current (possibly changed) ID
  MapSVGAdminDrawController.prototype.getObjectId = function (obj) {
    return $(obj).data("new-id")
      ? $(obj).data("new-id")
      : $(obj).data("id")
        ? $(obj).data("id")
        : obj.id
  }

  MapSVGAdminDrawController.prototype.saveSvg = function () {
    var _this = this
    $("#mapsvg-save-svg").buttonLoading(true)
    var indexShift = 0

    $.get(_this.filepath + "?v=" + Math.random()).done(function (xmlData) {
      // Commit changes
      if (_this.changes.length) {
        _this.changes.forEach(function (change) {
          switch (change.action) {
            // case "create":
            //     var index = $(change.data.parentNode).data('index');
            //     var container = $(xmlData).find('svg')[0].getElementsByTagName('*')[index];
            //     $(change.data).clone().appendTo($(container));
            //     _this.reindex();
            //     break;
            // case "delete":
            //     var index = $(change.data).data('index');
            //     $(xmlData).find('svg')[0].getElementsByTagName('*')[index].remove();
            //     change.data.remove();
            //     _this.reindex();
            //     break;
            case "update":
              // Update IDs
              for (var prop in change.dataNew) {
                if (prop == "id") {
                  change.data.setAttribute(prop, change.dataNew[prop])
                }
              }
              break
            default:
              break
          }
        })
      }

      // Cancel editing points
      if (_this.editingShape) {
        if (_this.editingShape.unclosed) {
          _this.removeShape(_this.editingShape.obj)
        } else {
          _this.editingShapeUnset()
        }
      }

      if (_this.controllers.region) {
        _this.admin.unloadController(_this.controllers.region)
        _this.controllers.region = null
      }

      var newSVG = _this.svg.clone(true)

      // Cancel zoom
      newSVG.css("transform", "")

      // Delete "deleted" (actually just hidden) objects
      newSVG.find("[data-deleted]").remove()

      // Delete editing points
      newSVG.find(".mapsvg-path-point").remove()

      // Restore stroke-width, remove classes
      newSVG.find("path, polygon, circle, ellipse, rect").each(function (index) {
        if ($(this).attr("data-stroke-width")) {
          $(this).css("stroke-width", $(this).attr("data-stroke-width"))
        }
        if ($(this).attr("class")) {
          $(this).attr(
            "class",
            $(this)
              .attr("class")
              .replace(/active/g, ""),
          )
          $(this).attr(
            "class",
            $(this)
              .attr("class")
              .replace(/mapsvg-custom-shape/g, ""),
          )
          $(this).attr(
            "class",
            $(this)
              .attr("class")
              .replace(/mapsvg-region/g, ""),
          )
          $(this).attr(
            "class",
            $(this)
              .attr("class")
              .replace(/mapsvg-region-hover/g, ""),
          )
          $(this).attr(
            "class",
            $(this)
              .attr("class")
              .replace(/mapsvg-disabled/g, ""),
          )
        }
      })

      var fileSizeInMb = newSVG[0].outerHTML.length / (1024 * 1024)
      const { phpIni } = window.mapsvgAdmin.getData().options
      var postMaxSizeInMb = phpIni.post_max_size.indexOf("M")
        ? phpIni.post_max_size.replace("M", "")
        : phpIni.post_max_size / (1024 * 1024)
      var fileSizeInMbString = fileSizeInMb.toFixed(2) + "Mb"
      if (fileSizeInMb > postMaxSizeInMb) {
        $.growl.error({
          title: "Error",
          message:
            "Can't save the file. File size (" +
            fileSizeInMbString +
            ') exceeds php.ini "post_max_size" value (' +
            window.mapsvgAdmin.options.phpIni.post_max_size +
            '). More info: <a href="https://mapsvg.com/docs/troubleshooting/uploading-big-files" target="_blank">Uploading big files</a>',
          duration: 50000,
        })
        $("#mapsvg-save-svg").buttonLoading(false)
        return false
      }

      var server = new mapsvg.server(mapsvg.routes.api)

      const formData = new FormData()
      formData.append("path", _this.filepath)
      var parts = _this.filepath.split("/")
      var name = parts.pop()
      var file = {
        filename: name,
        data: newSVG[0].outerHTML.replace(/\sgeoViewBox/gi, " mapsvg:geoViewBox"),
      }

      var files = []
      files.push(file)

      _this.admin.appendFilesToFormData(formData, files)

      server
        .post("svgfile/update", formData)
        .done(function (data) {
          newSVG = null

          _this.changed = false
          _this.changes = []
          _this.mapsvg.options.svgFileVersion++

          _this.mapsvg.reloadRegions()
          _this.mapsvg.adjustStrokes()

          _this.mapsvg.regionsRepository.find().done(function () {
            _this.mapsvg.adjustStrokes()
          })
        })
        .fail((response) => {
          MapSVG.handleFailedRequest(response)
        })
        .always(function () {
          $("#mapsvg-save-svg").buttonLoading(false)
        })
    })
  }
  MapSVGAdminDrawController.prototype.close = function () {
    var _this = this
  }
  MapSVGAdminDrawController.prototype.undo = function () {
    var _this = this

    if (!_this.changes.length) return false

    var change = _this.changes.pop()

    switch (change.action) {
      case "create":
        if (_this.editingShape && _this.editingShape.obj === change.data) _this.editingShapeUnset()
        change.data.remove()
        break
      case "delete":
        // _this.svg.append(change.data);
        change.data.style.display = "block"
        change.data.setAttribute("id", change.data.getAttribute("data-id"))
        change.data.setAttribute("data-id", "")
        change.data.removeAttribute("data-deleted")
        break
      case "update":
        _this.updateShape(change.idReal, change.dataOld, true)
        if (
          !_this.isReverting &&
          _this.editingShape &&
          _this.editingShape.obj.id == change.idReal
        ) {
          _this.editShape(_this.editingShape.obj)
        }
        break
      default:
        break
    }
    return true
  }

  MapSVGAdminDrawController.prototype.revert = function () {
    var _this = this
    var more = true
    _this.isReverting = true
    while (more) {
      more = _this.undo()
    }
  }

  MapSVGAdminDrawController.prototype.removeEditingPoint = function (shape) {
    var _this = this
    _this.editingShape.pathData.splice(_this.editingPoint.index, 1)

    if (_this.editingShape.pathData.length == 2) {
      _this.removeShape(_this.editingShape.obj)
    } else {
      if (_this.editingShape.pathData[0].type != "M") {
        _this.editingShape.pathData[0].type = "M"
      }
      _this.editingShape.points.splice(_this.editingPoint.index, 1)
      _this.editingShape.obj.setPathData(_this.editingShape.pathData)
      $(_this.editingPoint.circle).remove()
      _this.editingPoint = null
      _this.editingShape.points.forEach(function (_point, index) {
        $(_point).data("index", index)
      })
    }

    this.hint()
  }

  MapSVGAdminDrawController.prototype.removeShape = function (shape) {
    var _this = this

    if (shape) {
      var isEditing = _this.editingShape && _this.editingShape.obj == shape
      if (!_this.editingShape || (isEditing && !_this.editingShape.unclosed)) {
        _this.addChange("delete", shape)
      }
      if (isEditing) {
        _this.editingShape.obj.style.display = "none"
        _this.editingShape.obj.setAttribute("data-id", _this.editingShape.obj.id)
        _this.editingShape.obj.setAttribute("id", "")
        _this.editingShape.obj.setAttribute("data-deleted", "true")
        // _this.editingShape.obj.remove();
        if (_this.editingShape.points) {
          _this.editingShape.points.forEach(function (point) {
            point.remove()
          })
        }
        if (_this.editingShape.stroke) {
          $(_this.editingShape.stroke).remove()
        }
        _this.editingShape = null
        if (_this.controllers.region) {
          _this.admin.unloadController(_this.controllers.region)
          _this.controllers.region = null
        }
        _this.setEventHandlers()
      }
    }
  }

  MapSVGAdminDrawController.prototype.doSnap = function (point) {
    var _this = this
    var search = true
    var _point
    var dist = 10 / _this.mapsvg.getScale()
    var i = _this.snapPoints.length - 1
    while (search && i >= 0) {
      if (
        _this.snapPoints[i] &&
        _this.snapPoints[i].values &&
        _this.snapPoints[i].values[0] != undefined
      ) {
        _point = _this.snapPoints[i]
        if (
          Math.abs(point[0] - _point.values[0]) < dist &&
          Math.abs(point[1] - _point.values[1]) < dist
        ) {
          point[0] = _point.values[0]
          point[1] = _point.values[1]
          search = false
        }
      }
      i--
    }
    return point
  }
  MapSVGAdminDrawController.prototype.loadSnapPoints = function () {
    var _this = this
    this.snapPoints = []
    // var snapObjects = _this.svg.find('.mapsvg-custom-shape');
    var snapObjects = _this.svg
      .find("path, polygon, rect, circle, ellipse, polyline")
      .not(":hidden")
      .not(".mapsvg-path-point")
    snapObjects.each(function (i, object) {
      if (!_this.editingShape || object !== _this.editingShape.obj) {
        if ($(object).css("display") != "none")
          _this.snapPoints = _this.snapPoints.concat(object.getPathData({ normalize: true }))
      }
    })
  }
  MapSVGAdminDrawController.prototype.loadIntersectionPoints = function () {
    var _this = this
    _this.editingShape.pathLength = _this.editingShape.obj.getTotalLength()
    _this.editingShape.pathPoints = []

    for (var j = 0; j < _this.editingShape.pathLength; j++) {
      _this.editingShape.pathPoints.push(_this.editingShape.obj.getPointAtLength(j))
    }
  }

  MapSVGAdminDrawController.prototype.getIntersectionWithEditingShape = function (point) {
    var _this = this
    for (var j = 0; j < _this.editingShape.pathPoints.length; j++) {
      var intersection = _this.pointIntersect(point, _this.editingShape.pathPoints, j)
      if (intersection) {
        return intersection
      }
    }
  }

  MapSVGAdminDrawController.prototype.getPreviousPointIndex = function (point) {
    var _this = this
    var cont = true
    var j = 0
    var lastIndex = 1
    var i = 1

    while (cont) {
      var p = _this.editingShape.pathPoints[j]
      if (p.x == point.x && p.y == point.y) {
        cont = false
      } else {
        var p2 = _this.editingShape.points[i]
        p2 = { x: p2.getAttribute("cx"), y: p2.getAttribute("cy") }
        if (p2.x == p.x && p2.y == p.y) {
          lastIndex = i
          i++
        }
      }
      j++
    }
    return lastIndex
  }

  MapSVGAdminDrawController.prototype.pointIntersect = function (p1, points, index) {
    var p2 = points[index]
    var dist = 2 / this.mapsvg.scale // 5 screen pixels converted to SVG points
    var intersects = Math.abs(p1.x - p2.x) < dist && Math.abs(p1.y - p2.y) < dist
    // Find closest point on the path to the mouse pointer

    if (intersects) {
      /*
            var index2 = index;
            var d1 = Math.abs(p1.x - p2.x);
            var d2 = Math.abs(p1.y - p2.y);
            var delta;
            if(Math.abs(p1.x - points[index+1].x) < d1 && Math.abs(p1.y - points[index+1].y) < d2){
                delta = 1;
            } else {
                delta = -1;
            }
            index2 += delta;
            while(Math.abs(p1.x - points[index2].x) < d1 && Math.abs(p1.y - points[index2].y) < d2){
                index2 += delta;
            }

            index2 -= delta;

            return points[index2];

             */
      return points[index]
    } else {
      return false
    }
  }

  MapSVGAdminDrawController.prototype.addNewPoint = function (x, y) {
    var _this = this
    var p = { x: parseFloat(x), y: parseFloat(y) }

    var start = 1
    var index = 1
    _this.editingShape.pathData = _this.editingShape.obj.getPathData({ normalize: true })
    var ind
    for (var i = 1; i < _this.editingShape.pathData.length; i++) {
      var pointIndex1 = i - 1
      var pointIndex2 = i
      var startPoint = _this.editingShape.pathData[pointIndex1]
      var endPoint = _this.editingShape.pathData[pointIndex2]
      if (startPoint.values) {
        if (endPoint.type == "Z" || endPoint.type == "z") {
          endPoint = _this.editingShape.pathData[0]
        }
        startPoint = { x: startPoint.values[0], y: startPoint.values[1] }
        endPoint = { x: endPoint.values[0], y: endPoint.values[1] }

        if (_this.distanceToLine(startPoint, endPoint, p) == 0) {
          index = i
        }
      }
    }

    var pathPoint = {
      type: "L",
      values: [x, y],
    }
    _this.editingShape.pathData.splice(index, 0, pathPoint)
    _this.editingShape.d = _this.editingShape.obj.getAttribute("d")
    _this.editingShape.obj.setPathData(_this.editingShape.pathData)
    _this.addChange(
      "update",
      _this.editingShape.obj,
      { d: _this.editingShape.obj.getAttribute("d") },
      { d: _this.editingShape.d },
    )

    _this.editShape(_this.editingShape.obj)
    return index
  }

  MapSVGAdminDrawController.prototype.isOnLine = function (startPoint, endPoint, point) {
    var f = function (somex) {
      return (
        ((endPoint.y - startPoint.y) / (endPoint.x - startPoint.x)) * (somex - startPoint.x) +
        startPoint.y
      )
    }
    return Math.abs(f(point.px) - point.py) < 1e-3 // tolerance, rounding errors
    // && point.px >= startPoint.x && point.px <= endPoint.x;      // are they also on this segment?
  }
  MapSVGAdminDrawController.prototype.distanceToLine = function (line1, line2, pnt) {
    var L2 = (line2.x - line1.x) * (line2.x - line1.x) + (line2.y - line1.y) * (line2.y - line1.y)
    if (L2 == 0) return false
    var s = ((line1.y - pnt.y) * (line2.x - line1.x) - (line1.x - pnt.x) * (line2.y - line1.y)) / L2
    return (Math.abs(s) * Math.sqrt(L2)).toFixed(3)
  }
})(jQuery, window, window.MapSVG)
