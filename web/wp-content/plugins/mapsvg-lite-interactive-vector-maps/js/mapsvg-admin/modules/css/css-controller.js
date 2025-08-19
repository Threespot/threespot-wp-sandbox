;(function ($, window, MapSVG) {
  var MapSVGAdminCssController = function (container, admin, mapsvg) {
    this.name = "css"
    this.disableHorizontalScroll = true
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminCssController = MapSVGAdminCssController
  MapSVG.extend(MapSVGAdminCssController, window.MapSVGAdminController)

  MapSVGAdminCssController.prototype.viewLoaded = function () {
    var _this = this
    this.editors = {}
    this.textarea = this.view.find("#mapsvg-css-editor")
    this.textarea.val(_this.mapsvg.getData().options.css)
    this.editors.css = window.CodeMirror.fromTextArea(this.textarea[0], {
      mode: "css",
      matchBrackets: true,
      lineNumbers: true,
      theme: "dracula",
      readOnly: true,
      
    })
    this.editors.css.on("change", function () {
      _this.mapsvg.setCss(_this.editors.css.getValue())
    })

    // $(window).on('resize',function(){
    //     _this.view.find('.CodeMirror').css({
    //         height: _this.contentWrap.height()
    //     });
    // });

    // this.liveCSS = $('<style></style>').appendTo('head');

    $(window).on("resize.codemirror.css", function () {
      _this.resizeEditor()
    })
    _this.resizeEditor()

    _this.exampleCode = null
    _this.cssCodeLoaded = false

    this.view
      .on("click", "#mapsvg-css-menu a", function (e) {
        e.preventDefault()

        $(this).tab("show")
        if ($(this).attr("href") == "#mapsvg-css-default") {
          if (!_this.cssCodeLoaded) {
            $.get(_this.mapsvg.getCssUrl(), function (data) {
              $("#mapsvg-css-default-editor").val(data)
              _this.highlighDefaultCss()
              _this.cssCodeLoaded = true
            })
          } else {
            _this.highlighDefaultCss()
          }
        } else {
          _this.exampleCode && _this.exampleCode.toTextArea()
        }
      })
      .on("shown.bs.tab", function (e) {
        _this.resizeEditor()
      })
  }

  MapSVGAdminCssController.prototype.resizeEditor = function () {
    this.view.find(".CodeMirror")[0].CodeMirror.setSize(null, this.contentWrap.height())
    this.view.find(".CodeMirror").height(this.contentWrap.height())
  }

  MapSVGAdminCssController.prototype.highlighDefaultCss = function () {
    var _this = this
    _this.exampleCode = CodeMirror.fromTextArea($("#mapsvg-css-default-editor")[0], {
      mode: "css",
      lineNumbers: true,
      matchBrackets: true,
      readOnly: true,
    })

    _this.resizeEditor()
  }

  MapSVGAdminCssController.prototype.init = function () {}
})(jQuery, window, window.MapSVG)
