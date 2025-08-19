;(function ($, window, MapSVG) {
  var MapSVGAdminController = function (container, admin, map, events) {
    this.name = this.name || "controller"
    this.container = typeof container == "object" ? container : $("#" + container)
    this.admin = admin
    this.mapsvg = map
    this.templates = {}
    this.templatesURL = this.templatesURL || mapsvg.routes.root + "js/mapsvg-admin/modules/"
    this.scrollable = this.scrollable === undefined ? true : this.scrollable
    this.controllers = {}
    this.activeController = null
    this.events = events || {}
    this.history = { back: "", forward: "" }
    this.deps = this.deps || []
    this._init()
  }
  MapSVGAdminController.prototype.nameCamel = function () {
    var name = this.name
      .split("-")
      .map(function (n, index) {
        if (index === 0) return n
        else return n.charAt(0).toUpperCase() + n.slice(1)
      })
      .join("")
    return name
  }
  MapSVGAdminController.prototype.viewLoaded = function () {
    if (this.activeController) {
      this.activeController.viewDidAppear()
    }
    if (this.events.onload) {
      this.events.onload.call(this)
    }
  }
  MapSVGAdminController.prototype.viewUnloaded = function () {
    if (this.events.unload) {
      this.events.unload.call(this)
    }
  }

  MapSVGAdminController.prototype._viewLoaded = function () {
    var _this = this

    this.loaded = true

    this.view.find(".mapsvg-select2").mselect2({
      minimumResultsForSearch: 20,
    })

    if (this.scrollable)
      // setTimeout(function(){
      _this.updateScroll()
    // }, 500);
  }
  MapSVGAdminController.prototype.viewDidAppear = function () {
    var _this = this
    if (_this.controllers && _this.activeController) {
      _this.activeController.viewDidAppear()
    }
    if (this.scrollable) _this.updateScroll()
    // setTimeout(function(){
    // }, 500);
  }

  MapSVGAdminController.prototype.viewDidDisappear = function () {
    if (this.controllers) {
      for (var name in this.controllers) {
        this.controllers && this.controllers[name] && this.controllers[name].viewDidDisappear()
      }
    }
  }
  MapSVGAdminController.prototype.updateScroll = function () {
    if (!this.contentWrap || !this.scrollable) return

    var noHorizontal = !this.enableHorizontalScroll
      ? { contentWidth: "0px", mouseWheelSpeed: 30 }
      : { mouseWheelSpeed: 30 }

    if (!this.contentWrap.data("jsp")) this.contentWrap.jScrollPane(noHorizontal)
    var jsp = this.contentWrap.data("jsp")
    jsp.reinitialise()

    setTimeout(function () {
      jsp.reinitialise()
    }, 500)
  }

  MapSVGAdminController.prototype._init = async function () {
    var _this = this
    await mapsvg.utils.files.loadFiles(this.deps)
    var folderName = this.name.split("-")[0]
    if (!_this.templatesLoaded)
      $.get(
        _this.templatesURL + folderName + "/" + this.name + ".html?" + Math.random(),
        function (data) {
          $(data).each(function (index, tmpl) {
            var name = $(tmpl).data("name")
            if (name) {
              _this.templates[name] = Handlebars.compile($(tmpl).html())
              if ($(tmpl).data("partial")) {
                Handlebars.registerPartial(
                  _this.nameCamel() + mapsvg.utils.strings.ucfirst(name) + "Partial",
                  $(tmpl).html(),
                )
              }
            }
          })
          _this.templatesLoaded = true
          _this.render()
        },
      ).fail(function (resp) {
        if (resp.status == 404) {
          // alert('Can\'t load "*.hbs" template files. If you have IIS server you need to add .hbs extension to server config file. More info: \nhttps://stackoverflow.com/questions/21545302/asp-net-mvc5-how-to-load-hbs-file');
          alert("Can't load template files.")
        }
      })
    else _this.render()
  }
  MapSVGAdminController.prototype.render = function () {
    this.view && this.view.empty().remove()

    this.view = $("<div />")
      .attr("id", "mapsvg-admin-controller-" + this.name)
      .addClass("mapsvg-view")

    // Wrap cointainer, includes scrollable container
    this.contentWrap = $("<div />")
      .attr("id", "mapsvg-admin-content-" + this.name)
      .addClass("mapsvg-view-wrap")

    // Scrollable container
    this.contentView = $("<div />").addClass("mapsvg-view-content")
    if (this.scrollable) {
      this.contentWrap.addClass("nano")
      this.contentView.addClass("nano-content")
    }
    this.contentWrap.append(this.contentView)

    // Add toolbar if it exists in template file
    if (this.templates.toolbar) {
      this.toolbarView = $("<div />")
        .attr("id", "mapsvg-admin-toolbar-" + this.name)
        .addClass("mapsvg-view-toolbar")
      this.view.append(this.toolbarView)
    }

    this.view.append(this.contentWrap)

    // Add view into container
    this.container.append(this.view)
    this.container.data("controller", this)

    this.redraw()
    this.setEventHandlersCommon()
    this.setEventHandlers()
    this._viewLoaded()
    this.viewLoaded()
  }
  MapSVGAdminController.prototype.redraw = function () {
    this.templateData = this.getTemplateData()

    this.contentView && this.contentView.html(this.templates.main(this.templateData))
    if (this.templates.toolbar) this.toolbarView.html(this.templates.toolbar(this.templateData))
    if (this.scrollable) this.updateScroll()
  }

  MapSVGAdminController.prototype.setMainTemplate = function (name) {
    name = name == "image" ? "image" : "path"
    this.templates.main = this.templates[name]
  }
  MapSVGAdminController.prototype.getTemplateData = function () {
    return this.mapsvg.getOptions(true, null)
  }
  MapSVGAdminController.prototype.setEventHandlersCommon = function () {
    var _this = this
    new MapSVG.ResizeSensor(this.view[0], function () {
      if (_this.scrollable) _this.updateScroll()
    })

    if (!this.isParent) {
      // Run events only if it's child controller to avoid double event firing
      _this.view.on("click", "a.mapsvg-toggle-visibility", function () {
        var selector = $(this).data("toggle-visibility")
        $(selector).toggle()
        if ($(selector).is(":visible")) $(this).text("Hide")
        else $(this).text("Read more")
      })
      _this.view
        .on("change paste", '[data-live="change"]', function (e) {
          _this.formToObjectUpdate(e)
        })
        .on("keyup paste", '[data-live="keyup"]', function (e) {
          _this.formToObjectUpdate(e)
        })
        .on("select", '[data-live="select"]', function (e) {
          _this.formToObjectUpdate(e)
        })
        .on("click", '[data-live="click"]', function (e) {
          _this.formToObjectUpdate(e)
        })
        .on("keypress", "form.safarifix input", function (e) {
          if (e.which == 13 || e.keyCode == 13) e.preventDefault()
        })
        .on("click", "input.input-switch", function () {
          if ($(this).is(":checked")) {
            $(this).closest(".controls").find(".radio").next().attr("disabled", "disabled")
            $(this).parent().next().removeAttr("disabled")
          }
        })
        .on("change", ".mapsvg-toggle-visibility", function () {
          var parent = $(this).closest(".btn-group")
          var on = $(this).is(":checkbox") ? MapSVG.parseBoolean($(this).prop("checked")) : true
          var selector = $(this).data("toggle-visibility")
          var selectorReverse = $(this).data("toggle-visibility-reverse")
          if (selector) on ? $(selector).show() : $(selector).hide()
          if (selectorReverse) on ? $(selectorReverse).hide() : $(selectorReverse).show()
          if (_this.scrollable) _this.updateScroll()
        })
        .on("click", "button.mapsvg-toggle-visibility", function (e) {
          e.preventDefault()
          var selector = $(this).data("toggle-visibility")
          $(selector).toggle()
          if (_this.scrollable) _this.updateScroll()
        })
        .on("click", ".disabled", function (e) {
          e.preventDefault()
          return false
        })
        .on("click", ".btn-group-checkbox a", function () {
          var btn = $(this)
          var type = btn.attr("data-toggle")
          setTimeout(function () {
            var on = btn.hasClass("active")
            if (on)
              btn
                .closest(".btn-group-checkbox")
                .find("input.input-toggle-" + type)
                .val("true")
            else
              btn
                .closest(".btn-group-checkbox")
                .find("input.input-toggle-" + type)
                .val("")
          }, 200)
        })
      _this.view.on("click", ".mapsvg-tab-link", function (e) {
        e.preventDefault()
        $('#mapsvg-tabs-menu a[href="' + $(this).attr("href") + '"]').tab("show")
      })
    }
  }
  MapSVGAdminController.prototype.setEventHandlers = function () {}
  MapSVGAdminController.prototype.show = function () {
    this.view.show()
  }
  MapSVGAdminController.prototype.hide = function () {
    this.view.hide()
  }
  MapSVGAdminController.prototype.destroy = function () {
    this.viewUnloaded()
    this.view.empty().remove()
    if (this.controllers) {
      for (var i in this.controllers) {
        this.controllers[i] && this.controllers[i].view && this.controllers[i].destroy()
      }
    }
  }
  MapSVGAdminController.prototype.objectUpdater = function (e) {
    return this.admin.mapSvgUpdate(e)
  }

  MapSVGAdminController.prototype.formToObjectUpdate = function (e) {
    var _this = this
    var jQueryElem = $(e.target)
    if (jQueryElem.is(":radio")) {
      jQueryElem = jQueryElem.closest(".form-group").find(":radio:checked")
    }
    var delay = parseInt($(jQueryElem).data("delay"))
    jQueryElem.closest(".form-group").removeClass("has-error")

    // if (delay){
    //     var t = $(jQueryElem).data('timer');
    //     t && clearTimeout(t);
    //     $(jQueryElem).data('timer',setTimeout(function() {
    //         _this.mapSvgUpdateFinal(jQueryElem);
    //     }, delay));
    // }else{
    // _this.mapSvgUpdateFinal(jQueryElem);
    // }

    // Validate input field and format if necessary
    var data = _this.validateInput(jQueryElem)

    if (data instanceof TypeError) {
      // If error, highlight input field
      jQueryElem.closest(".form-group").addClass("has-error")
      // TODO highlight line number in CodeMirror
    } else {
      // If no errors, check if attribute is read-only in current map mode
      return _this.formToObjectUpdater(data)
    }
  }
  MapSVGAdminController.prototype.formToObjectUpdater = function (data) {
    var _this = this
    for (var _key in data) {
      var key = _key
    }
    var optmode = _this.admin.getData().optionsMode[_this.admin.getData().mode]
    if (optmode && optmode.hasOwnProperty(key)) {
      // Attribute is read-only, save to dirty
      $.extend(true, _this.mapsvg.optionsDelta, data)
    } else {
      // Attribute can be written into MapSVG instance
      _this.mapsvg.update(data)
    }
  }
  MapSVGAdminController.prototype.mapSvgUpdateFinal = function (jQueryElem) {
    var _this = this
    // Validate input field and format if necessary
    var data = _this.validateInput(jQueryElem)

    if (data instanceof TypeError) {
      // If error, highlight input field
      jQueryElem.closest(".form-group").addClass("has-error")
      // TODO highlight line number in CodeMirror
    } else {
      // If no errors, check if attribute is read-only in current map mode
      for (var _key in data) {
        var key = _key
      }
      if (_this.admin.getData().optionsMode[_this.admin.getData().mode].hasOwnProperty(key)) {
        // Attribute is read-only, save to dirty
        $.extend(true, _this.mapsvg.optionsDelta, data)
      } else {
        // Attribute can be written into MapSVG instance
        _this.mapsvg.update(data)
      }
    }
  }
  MapSVGAdminController.prototype.validateInput = function (jQueryElem) {
    var val
    if (jQueryElem.is(":checkbox")) {
      if (jQueryElem.is(":checked"))
        val =
          jQueryElem.attr("value") && jQueryElem.attr("value") != "on"
            ? jQueryElem.attr("value")
            : true
      else val = false
    } else {
      val = jQueryElem.val()
      if (jQueryElem.data("type") === "int") {
        val = parseInt(val)
      } else if (jQueryElem.data("type") === "float") {
        val = parseFloat(val)
      } else if (jQueryElem.data("type") === "boolean") {
        val = MapSVG.parseBoolean(val)
      }
    }
    var validate = jQueryElem.data("validate")
    if (validate && val != "") {
      if (validate == "function") {
        val = val != "" ? msvg.functionFromString(val) : null
        if (val instanceof TypeError || val instanceof SyntaxError) return val
        // if(val && val.error){
        //     return new TypeError("MapSVG error: error in function", "", val.error.line);
        // }
      } else if (validate == "link") {
        if (!MapSVG.isValidURL(val))
          return new TypeError('MapSVG error: wrong URL format. URL must start with "http://"')
      } else if (validate == "number") {
        if (!$.isNumeric(val)) return new TypeError("MapSVG error: value must be a number")
      } else if (validate == "object") {
        if (data.substr(0, 1) == "[" || data.substr(0, 1) == "{") {
          try {
            var tmp
            eval("tmp = " + val)
            var val = tmp
          } catch (err) {
            return new TypeError("MapSVG error: wrong object format for " + jQueryElem.attr("name"))
          }
        }
      }
    }
    return jQueryElem.inputToObject(val)
  }

  MapSVGAdminController.prototype.slideBack = function () {
    if (this.container.hasClass("mapsvg-slide-forward")) {
      this.container.removeClass("mapsvg-slide-forward")
    } else {
      this.container.addClass("mapsvg-slide-back")
    }
  }
  MapSVGAdminController.prototype.slideForward = function () {
    if (this.container.hasClass("mapsvg-slide-back")) {
      this.container.removeClass("mapsvg-slide-back")
    } else {
      this.container.addClass("mapsvg-slide-forward")
    }
  }

  window.MapSVGAdminController = MapSVGAdminController
})(jQuery, window, window.MapSVG)
