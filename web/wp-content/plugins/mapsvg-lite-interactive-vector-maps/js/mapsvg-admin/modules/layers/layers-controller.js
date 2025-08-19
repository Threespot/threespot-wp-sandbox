;(function ($, window, MapSVG) {
  var MapSVGAdminLayersController = function (container, admin, mapsvg) {
    this.name = "layers"
    this.scrollable = false
    this.isParent = true
    this.deps = [
      { path: "js/mapsvg-admin/modules/layers/layers-list-controller.js" },
      { path: "js/mapsvg-admin/modules/layers/layers-settings-controller.js" },
    ]

    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminLayersController = MapSVGAdminLayersController
  MapSVG.extend(MapSVGAdminLayersController, window.MapSVGAdminController)

  MapSVGAdminLayersController.prototype.viewLoaded = function () {
    var _this = this
    this.controllers.list = new MapSVGAdminLayersListController(
      "mapsvg-layers-list",
      _this.admin,
      _this.mapsvg,
    )
    this.controllers.settings = new MapSVGAdminLayersSettingsController(
      "mapsvg-layers-settings",
      _this.admin,
      _this.mapsvg,
    )
    this.controllers.list.toolbarView = this.toolbarView
    this.controllers.settings.toolbarView = this.toolbarView
  }

  MapSVGAdminLayersController.prototype.viewDidAppear = function () {
    MapSVGAdminController.prototype.viewDidAppear.call(this)
    var _this = this
  }
  MapSVGAdminLayersController.prototype.viewDidDisappear = function () {
    MapSVGAdminController.prototype.viewDidDisappear.call(this)
  }

  MapSVGAdminLayersController.prototype.setEventHandlers = function () {
    var _this = this

    $("#mapsvg-layers-menu a").click(function (e) {
      e.preventDefault()
      $(this).tab("show")
      $("#mapsvg-btn-layer-add").toggle($(this).attr("href") === "#mapsvg-layers-list")
    })
  }
})(jQuery, window, window.MapSVG)
