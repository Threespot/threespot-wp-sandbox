;(function ($, window, MapSVG) {
  var MapSVGAdminDatabaseSettingsController = function (container, admin, mapsvg) {
    this.name = "database-settings"
    this.database = mapsvg.objectsRepository

    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminDatabaseSettingsController = MapSVGAdminDatabaseSettingsController
  MapSVG.extend(MapSVGAdminDatabaseSettingsController, window.MapSVGAdminController)

  MapSVGAdminDatabaseSettingsController.prototype.setEventHandlers = function () {
    var _this = this
    this.view.on("click", "#mapsvg-clear-database-btn", function () {
      if (confirm("Are you sure you want to clear the database?")) {
        _this.database
          .clear()
          .done(function () {
            $.growl.notice({ title: "", message: "Database is cleared" })
          })
          .fail(function () {
            $.growl.error({
              title: "Server error",
              message: "Can't clear the database",
            })
          })
      }
    })
  }
})(jQuery, window, window.MapSVG)
