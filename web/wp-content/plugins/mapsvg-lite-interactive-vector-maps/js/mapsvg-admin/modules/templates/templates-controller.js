;(function ($, window, MapSVG) {
  var MapSVGAdminTemplatesController = function (container, admin, mapsvg) {
    this.name = "templates"
    this.disableHorizontalScroll = true
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminTemplatesController = MapSVGAdminTemplatesController
  MapSVG.extend(MapSVGAdminTemplatesController, window.MapSVGAdminController)

  MapSVGAdminTemplatesController.prototype.viewDidAppear = function () {
    this.setHints()
  }

  MapSVGAdminTemplatesController.prototype.viewLoaded = function () {
    var _this = this
    _this.setEditor()
    $(window).on("resize.codemirror.tmpl", function () {
      _this.resizeEditor()
    })
  }

  MapSVGAdminTemplatesController.prototype.setEventHandlers = function () {
    var _this = this
    this.toolbarView
      .find("select")
      .mselect2()
      .on("change", function () {
        _this.setEditor()
      })
  }

  MapSVGAdminTemplatesController.prototype.setEditor = function () {
    var _this = this
    this.view.find("#mapsvg-template-container").empty()

    this.template = this.toolbarView.find("select").val()
    var textarea = $(
`<textarea id="mapsvg-template-textarea" class="form-control" rows="8"></textarea>`,
)
    
    textarea.val(this.mapsvg.getData().options.templates[this.template])
    this.view.find("#mapsvg-template-container").append(textarea)

    _this.setHints()

    this.editor = window.CodeMirror.fromTextArea(textarea[0], {
      mode: { name: "handlebars", base: "text/html" },
      matchBrackets: true,
      lineNumbers: true,
      theme: "dracula",
      readOnly: true,
      
    })
    this.editor.on("change", function (a, b) {
      _this.setTextareaValue(a, b)
    })
    // When an @ is typed, activate completion
    this.editor.on("inputRead", function (editor, change) {
      if (change.text[0] == "{") editor.showHint({ completeSingle: false })
    })
    _this.resizeEditor()
  }

  MapSVGAdminTemplatesController.prototype.resizeEditor = function () {
    this.view.find(".CodeMirror")[0].CodeMirror.setSize(null, this.contentWrap.height())
    this.view.find(".CodeMirror").height(this.contentWrap.height())
  }

  MapSVGAdminTemplatesController.prototype.getRegionHints = function (child) {
    var prefix = child ? "regions.0." : ""
    var fields = []
    var _this = this

    fields.push("{" + prefix + "id" + "}}")
    fields.push("{" + prefix + "title" + "}}")

    this.mapsvg.regionsRepository.getSchema().fields.forEach(function (obj) {
      if (obj.type == "image") {
        fields.push("{" + prefix + obj.name + ".0.thumbnail}}")
        fields.push("{" + prefix + obj.name + ".0.medium}}")
        fields.push("{" + prefix + obj.name + ".0.full}}")
      } else if (obj.type == "post") {
        fields.push("{" + prefix + "post.post_title}}")
        fields.push("{" + prefix + "post.content}}")
        fields.push("{" + prefix + "post.url}}")
      } else if (obj.type == "select" || obj.type == "radio" || obj.type == "status") {
        fields.push("{" + prefix + obj.name + "_text}}")
        fields.push("{" + prefix + obj.name + "}}")
      } else {
        fields.push("{" + prefix + obj.name + "}}")
      }
    })
    if (!child) fields = fields.concat(_this.getDBHints(true))

    return fields
  }
  MapSVGAdminTemplatesController.prototype.getDBHints = function (child) {
    var prefix = child ? "objects.0." : ""
    var fields = []
    fields.push("{" + prefix + "id" + "}}")

    var _this = this

    this.mapsvg.objectsRepository.getSchema().fields.forEach(function (obj) {
      if (obj.type == "post") {
        fields.push("{" + prefix + "post.post_title}}")
        fields.push("{" + prefix + "post.post_content}}")
        fields.push("{" + prefix + "post.url}}")
      } else if (obj.type == "location") {
        if (_this.mapsvg.getData().mapIsGeo) {
          fields.push("{" + obj.name + ".address.formatted}}")
          fields.push("{" + obj.name + ".address.locality}}")
          fields.push("{" + obj.name + ".address.state}}")
          fields.push("{" + obj.name + ".address.state_short}}")
          fields.push("{" + obj.name + ".address.street_number}}")
          fields.push("{" + obj.name + ".address.route}}")
          fields.push("{" + obj.name + ".address.administrative_area_1}}")
          fields.push("{" + obj.name + ".address.administrative_area_1_short}}")
          fields.push("{" + obj.name + ".address.administrative_area_2}}")
          fields.push("{" + obj.name + ".address.administrative_area_2_short}}")
          fields.push("{" + obj.name + ".address.country}}")
          fields.push("{" + obj.name + ".address.zip}}")
          fields.push("{" + obj.name + ".address.postal_code}}")
          fields.push("{" + obj.name + ".lat}}")
          fields.push("{" + obj.name + ".lng}}")
        }
      } else if (obj.type == "marker") {
        if (_this.mapsvg.getData().mapIsGeo) {
          fields.push("{marker.geoCoords.[0]}}")
          fields.push("{marker.geoCoords.[1]}}")
        }
      } else if (obj.type == "image") {
        fields.push("{" + prefix + obj.name + ".0.thumbnail}}")
        fields.push("{" + prefix + obj.name + ".0.medium}}")
        fields.push("{" + prefix + obj.name + ".0.full}}")
      } else if (obj.type == "region") {
        if (!child) fields = fields.concat(_this.getRegionHints(true))
      } else if (obj.type == "select" || obj.type == "radio") {
        fields.push("{" + prefix + obj.name + "_text}}")
        fields.push("{" + prefix + obj.name + "}}")
      } else {
        fields.push("{" + prefix + obj.name + "}}")
      }
    })

    return fields
  }

  MapSVGAdminTemplatesController.prototype.setHints = function () {
    var addPostFields = false
    var addRegionFields = false
    var fields

    if (
      this.template == "popoverRegion" ||
      this.template == "tooltipRegion" ||
      this.template == "detailsViewRegion" ||
      (this.template == "directoryItem" && this.mapsvg.getData().options.menu.source == "regions")
    ) {
      fields = this.getRegionHints()
    } else {
      fields = this.getDBHints()
    }

    var commands = []
    var arr = fields.concat(commands)
    CodeMirror.registerHelper("hintWords", "xml", arr)
  }

  MapSVGAdminTemplatesController.prototype.setTextareaValue = function (codemirror, changeobj) {
    var _this = this
    var handler = codemirror.getValue()
    var textarea = $(codemirror.getTextArea())
    textarea.val(handler).trigger("change")
    if (
      textarea.attr("name") === "templates[labelMarker]" &&
      this.mapsvg.getData().options.labelsMarkers.on
    ) {
      this.mapsvg.setLabelsMarkers()
    }
    if (
      textarea.attr("name") === "templates[labelRegion]" &&
      this.mapsvg.getData().options.labelsRegions.on
    ) {
      this.mapsvg.setLabelsRegions()
    }
  }

  MapSVGAdminTemplatesController.prototype.init = function () {}
})(jQuery, window, window.MapSVG)
