;(function ($, window, MapSVG) {
  var MapSVGAdminFiltersController = function (container, admin, mapsvg) {
    this.name = "filters"
    this.scrollable = false
    this.isParent = true
    this.deps = [
      { path: "js/mapsvg-admin/modules/filters/filters-settings-controller.js" },
      { path: "js/mapsvg-admin/modules/filters/filters-structure-controller.js" },
    ]
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminFiltersController = MapSVGAdminFiltersController
  MapSVG.extend(MapSVGAdminFiltersController, window.MapSVGAdminController)

  MapSVGAdminFiltersController.prototype.viewLoaded = function () {
    var _this = this
    // this.controllers.list = new MapSVGAdminFiltersListController('mapsvg-data-list', _this.admin, _this.mapsvg);
    this.controllers.structure = new MapSVGAdminFiltersStructureController(
      "mapsvg-filters-structure",
      _this.admin,
      _this.mapsvg,
    )
    this.controllers.settings = new MapSVGAdminFiltersSettingsController(
      "mapsvg-filters-settings",
      _this.admin,
      _this.mapsvg,
    )
    _this.activeController = this.controllers.settings
    MapSVGAdminController.prototype.viewLoaded.call(this)
  }

  MapSVGAdminFiltersController.prototype.viewDidAppear = function () {
    // this.admin.restorePanelsState();
    var _this = this
    if (
      _this.activeController &&
      _this.activeController instanceof MapSVGAdminFiltersStructureController
    ) {
      _this.admin.rememberPanelsState()
      _this.admin.togglePanel("left", false)
    }
  }
  MapSVGAdminFiltersController.prototype.viewDidDisappear = function () {
    this.admin.restorePanelsState()
  }

  MapSVGAdminFiltersController.prototype.setEventHandlers = function () {
    var _this = this

    $("#mapsvg-filters-menu a")
      .click(function (e) {
        e.preventDefault()
        $(this).tab("show")
      })
      .on("shown.bs.tab", function (e) {
        var container = $($(this).attr("href"))
        var controller = container.data("controller")
        _this.activeController = controller
        controller.viewDidAppear()

        var previousTabId = $(e.relatedTarget).attr("href")
        if (previousTabId) {
          var prevControllerName = $(previousTabId).attr("data-controller")
          _this.controllers[prevControllerName].viewDidDisappear()
        }

        if ($(this).attr("href") == "#mapsvg-filters-structure") {
          _this.admin.rememberPanelsState()
          _this.admin.togglePanel("left", false)
        } else {
          _this.admin.restorePanelsState()
        }

        if ($(this).attr("href") == "#mapsvg-filters-settings") {
          $(".mapsvg-toolbar-buttons").show()
        }
      })
  }
})(jQuery, window, window.MapSVG)
