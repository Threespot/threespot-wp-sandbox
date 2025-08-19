;(function ($, window, MapSVG) {
  var MapSVGAdminFloorsController = function (container, admin, mapsvg) {
    this.name = "floors"
    this.scrollable = false
    this.isParent = true
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminFloorsController = MapSVGAdminFloorsController
  MapSVG.extend(MapSVGAdminFloorsController, window.MapSVGAdminController)

  MapSVGAdminFloorsController.prototype.viewLoaded = function () {
    var _this = this
    this.controllers.list = new MapSVGAdminFloorsListController(
      "mapsvg-floors-list",
      _this.admin,
      _this.mapsvg,
    )
    this.controllers.list.toolbarView = this.toolbarView
    _this.activeController = this.controllers.list
    MapSVGAdminController.prototype.viewLoaded.call(this)
  }

  MapSVGAdminFloorsController.prototype.viewDidAppear = function () {
    MapSVGAdminController.prototype.viewDidAppear.call(this)
    var _this = this
  }
  MapSVGAdminFloorsController.prototype.viewDidDisappear = function () {
    MapSVGAdminController.prototype.viewDidDisappear.call(this)
  }

  MapSVGAdminFloorsController.prototype.setEventHandlers = function () {
    var _this = this
  }
})(jQuery, window, window.MapSVG)
