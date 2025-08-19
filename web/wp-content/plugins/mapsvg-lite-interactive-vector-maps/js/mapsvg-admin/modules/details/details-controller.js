;(function ($, window, MapSVG) {
  var MapSVGAdminDetailsController = function (container, admin, mapsvg) {
    this.name = "details"
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminDetailsController = MapSVGAdminDetailsController
  MapSVG.extend(MapSVGAdminDetailsController, window.MapSVGAdminController)

  MapSVGAdminDetailsController.prototype.viewLoaded = function () {}
})(jQuery, window, window.MapSVG)
