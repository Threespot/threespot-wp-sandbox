;(function ($, window, MapSVG) {
  var MapSVGAdminRegionsController = function (container, admin, mapsvg) {
    this.name = "regions"
    this.scrollable = false
    this.isParent = true
    this.deps = [
      { path: "js/mapsvg-admin/modules/regions/regions-list-controller.js" },
      { path: "js/mapsvg-admin/modules/regions/regions-csv-controller.js" },
      { path: "js/mapsvg-admin/modules/regions/regions-settings-controller.js" },
      { path: "js/mapsvg-admin/modules/regions/regions-structure-controller.js" },
    ]

    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminRegionsController = MapSVGAdminRegionsController
  MapSVG.extend(MapSVGAdminRegionsController, window.MapSVGAdminController)

  MapSVGAdminRegionsController.prototype.viewLoaded = function () {
    var _this = this
    this.controllers.list = new MapSVGAdminRegionsListController(
      "mapsvg-regions-list",
      _this.admin,
      _this.mapsvg,
    )
    this.controllers.structure = new MapSVGAdminRegionsStructureController(
      "mapsvg-regions-structure",
      _this.admin,
      _this.mapsvg,
    )
    this.controllers.settings = new MapSVGAdminRegionsSettingsController(
      "mapsvg-regions-settings",
      _this.admin,
      _this.mapsvg,
    )
    this.controllers.csv = new MapSVGAdminRegionsCsvController(
      "mapsvg-data-r-csv",
      _this.admin,
      _this.mapsvg,
    )

    this.controllers.list.toolbarView = this.toolbarView
    this.controllers.structure.toolbarView = this.toolbarView
    this.controllers.csv.toolbarView = this.toolbarView

    this.activeController = this.controllers.list
    MapSVGAdminController.prototype.viewLoaded.call(this)
  }

  MapSVGAdminRegionsController.prototype.viewDidAppear = function () {
    MapSVGAdminController.prototype.viewDidAppear.call(this)

    var _this = this
    if (
      _this.activeController &&
      _this.activeController instanceof MapSVGAdminDatabaseStructureController
    ) {
      _this.admin.rememberPanelsState()
      _this.admin.togglePanel("left", false)
    }
  }
  MapSVGAdminRegionsController.prototype.viewDidDisappear = function () {
    MapSVGAdminController.prototype.viewDidDisappear.call(this)
    this.admin.restorePanelsState()
  }

  MapSVGAdminRegionsController.prototype.setEventHandlers = function () {
    var _this = this

    $("#mapsvg-regions-menu a")
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

        if ($(this).attr("href") == "#mapsvg-regions-list") {
          _this.toolbarView.find(".mapsvg-toolbar-buttons").show()
        } else {
          _this.toolbarView.find(".mapsvg-toolbar-buttons").hide()
        }

        if ($(this).attr("href") == "#mapsvg-regions-structure") {
          _this.admin.rememberPanelsState()
          _this.admin.togglePanel("left", false)
        } else {
          _this.admin.restorePanelsState()
        }

        // if($(this).attr('href') == '#mapsvg-regions-structure') {
        //     $('#mapsvg-tabs').addClass('no-padding');
        //     $('.mapsvg-panel-left').addClass('closed');
        //     $('.mapsvg-panel-right').addClass('fullscreen');
        //     setTimeout(function(){
        //         _this.admin.resizeDashboard();
        //     }, 400);
        // }else{
        //     $('#mapsvg-tabs').removeClass('no-padding');
        //     $('.mapsvg-panel-left').removeClass('closed');
        //     $('.mapsvg-panel-right').removeClass('fullscreen');
        //     setTimeout(function(){
        //         _this.admin.resizeDashboard();
        //     }, 400);
        // }
      })
  }
})(jQuery, window, window.MapSVG)
