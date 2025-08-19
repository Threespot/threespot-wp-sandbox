;(function ($, MapSVG) {
  var MapSVGAdminGalleryController = function (container, admin, mapsvg) {
    this.name = "gallery"
    this.scrollable = false
    this.isParent = true
    // this.templatesURL = mapsvg_gal_paths.templates;
    $("#" + container).empty()
    this.deps = [{ path: "js/mapsvg-admin/modules/gallery/gallery-list-controller.js" }]

    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminGalleryController = MapSVGAdminGalleryController
  MapSVG.extend(MapSVGAdminGalleryController, window.MapSVGAdminController)

  MapSVGAdminGalleryController.prototype.viewLoaded = function () {
    var _this = this
    this.controllers.list = new MapSVGAdminGalleryListController(
      "mapsvg-gal-list",
      _this.admin,
      _this.mapsvg,
    )
    this.controllers.list.toolbarView = this.toolbarView
    _this.activeController = this.controllers.list
  }

  MapSVGAdminGalleryController.prototype.viewDidAppear = function () {
    MapSVGAdminController.prototype.viewDidAppear.call(this)
    var _this = this
  }
  MapSVGAdminGalleryController.prototype.viewDidDisappear = function () {
    MapSVGAdminController.prototype.viewDidDisappear.call(this)
    this.admin.restorePanelsState()
  }

  MapSVGAdminGalleryController.prototype.setEventHandlers = function () {
    var _this = this

    $("#mapsvg-gal-tabs a").on("shown.bs.tab", function (e) {
      var container = $($(this).attr("href"))
      var controller = container.data("controller")
      _this.activeController = controller
      controller.viewDidAppear()

      var previousTabId = $(e.relatedTarget).attr("href")
      if (previousTabId) {
        var prevControllerName = $(previousTabId).attr("data-controller")
        _this.controllers[prevControllerName].viewDidDisappear()
      }

      if ($(this).attr("href") == "#mapsvg-gal-list") {
        $(".mapsvg-toolbar-buttons").show()
      }
    })
  }
})(jQuery, window.MapSVG)
