;(function ($, window, MapSVG) {
  var MapSVGAdminFiltersSettingsController = function (container, admin, mapsvg) {
    this.name = "filters-settings"
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminFiltersSettingsController = MapSVGAdminFiltersSettingsController
  MapSVG.extend(MapSVGAdminFiltersSettingsController, window.MapSVGAdminController)

  MapSVGAdminFiltersSettingsController.prototype.viewLoaded = function () {
    var _this = this
    _this.mapsvg.regionsRepository.events.on("schemaChange", function () {
      _this.render()
    })
  }

  MapSVGAdminFiltersSettingsController.prototype.setEventHandlers = function () {
    var _this = this
    this.view.find('input[name="filters[source]"]').on("change", function (e) {
      var visibleRegionsFields = $(this).val() == "regions"
      _this.view.find("#mapsvg-control-frs").toggle(visibleRegionsFields)
    })
  }

  MapSVGAdminFiltersSettingsController.prototype.getTemplateData = function () {
    var _this = this
    var statusField = _this.mapsvg.regionsRepository.getSchema().getFieldByType("status")
    var options = statusField ? statusField.options : []
    return {
      filters: _this.mapsvg.options.filters,
      statuses: options,
    }
  }
})(jQuery, window, window.MapSVG)
