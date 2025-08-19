;(function ($, window, MapSVG) {
  var MapSVGAdminLayersSettingsController = function (container, admin, mapsvg) {
    this.name = "layers-settings"
    this.database = mapsvg.regionsDatabase

    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminLayersSettingsController = MapSVGAdminLayersSettingsController
  MapSVG.extend(MapSVGAdminLayersSettingsController, window.MapSVGAdminController)

  MapSVGAdminLayersSettingsController.prototype.setEventHandlers = function () {
    var _this = this
  }
})(jQuery, window, window.MapSVG)
