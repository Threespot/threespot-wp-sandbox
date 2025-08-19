;(function ($, window, MapSVG) {
  var MapSVGAdminJavascriptController = function (container, admin, _mapsvg) {
    this.name = "javascript"
    this.errorLines = []
    this.disableHorizontalScroll = true
    this.scrollable = false

    this.mapAttr = ""

    MapSVGAdminController.call(this, container, admin, _mapsvg)
  }
  window.MapSVGAdminJavascriptController = MapSVGAdminJavascriptController
  MapSVG.extend(MapSVGAdminJavascriptController, window.MapSVGAdminController)

  MapSVGAdminJavascriptController.prototype.viewLoaded = function () {
    this.textarea = $("#mapsvg-js-textarea")
    this.setEditor()
    this.linkTextarea()
  }

  MapSVGAdminJavascriptController.prototype.setEventHandlers = function () {
    var _this = this

    this.toolbarView
      .find("select")
      .mselect2()
      .on("select2:select", () => {
        this.linkTextarea()
      })
  }

  MapSVGAdminJavascriptController.prototype.linkTextarea = function () {
    this.mapAttr = this.toolbarView.find("select").val()
    this.prefix = this.mapAttr.split("[")[0]
    this.rest = this.mapAttr.match(/\[(.*?)\]/)[1]
    
    if (this.prefix === "event") {
      this.event = this.mapAttr
    } else {
      this.event = null
    }

    var content = this.mapsvg.getData().options[this.prefix][this.rest]
    if (!content) content = ""

    content = content && typeof content !== "string" ? MapSVG.convertToText(content) : content
    this.editor.setValue(content)
  }

  MapSVGAdminJavascriptController.prototype.setEditor = function () {
    var _this = this

    this.editor = window.CodeMirror.fromTextArea(this.textarea[0], {
      lint: {
        esversion: 2023,
        asi: true, // Allow missing semicolons
      },
      extraKeys: {
        Tab: function (cm) {
          cm.replaceSelection("  ", "end")
        },
      },
      mode: "javascript",
      matchBrackets: true,
      lineNumbers: true,
      gutters: ["CodeMirror-lint-markers"],
      readOnly: true,
      
    })
    this.editor.setOption("theme", "dracula")
    this.editor.on("change", (codemirror, changeobj) => {
      _this.setTextareaValue(codemirror, changeobj)
    })
    _this.resizeEditor()

    $(window).off("resize.codemirror.js")
    $(window).on("resize.codemirror.js", function () {
      _this.resizeEditor()
    })
    this.textarea.on("change", (e) => {
      const value = e.target.value
      const data = {}
      data[this.rest] = value
      if (this.prefix === "middlewares") {
        this.mapsvg.setMiddlewares(data, true)
      } else {
        this.mapsvg.setEvents(data, true)
      }
    })
  }

  MapSVGAdminJavascriptController.prototype.resizeEditor = function () {
    this.view.find(".CodeMirror")[0].CodeMirror.setSize(null, this.contentWrap.height())
    this.view.find(".CodeMirror").height(this.contentWrap.height())
  }

  MapSVGAdminJavascriptController.prototype.setTextareaValue = function (codemirror, changeobj) {
    var handler = codemirror.getValue()
    var textarea = $(codemirror.getTextArea())
    this.mapsvg.events.off(this.event)
    textarea.val(handler).trigger("change")
  }

  MapSVGAdminJavascriptController.prototype.highlightErrors = function () {
    var _this = this

    var textarea = $(_this.editor.getTextArea())
    var handler = _this.editor.getValue()
    textarea.val(handler)

    _this.editor.operation(function () {
      if (_this.error)
        _this.editor.removeLineClass(_this.error.line - 2, "background", "line-error")

      _this.error = _this.validateInput(textarea)
      if (!(_this.error instanceof TypeError || _this.error instanceof SyntaxError)) {
        _this.error = null
        //_this.setTextareaValue();
      } else {
        console.log(_this.error)

        //_this.editor.addLineClass(_this.error.line-2, 'background', 'line-error');
      }
    })
  }
})(jQuery, window, window.MapSVG)
