;(function ($, window, MapSVG) {
  var MapSVGAdminDatabaseController = function (container, admin, mapsvg) {
    this.name = "database"
    this.scrollable = false
    this.isParent = true
    this.database = mapsvg.objectsRepository
    this.deps = [
      { path: "js/mapsvg-admin/modules/database/database-list-controller.js" },
      { path: "js/mapsvg-admin/modules/database/database-csv-controller.js" },
      { path: "js/mapsvg-admin/modules/database/database-settings-controller.js" },
      { path: "js/mapsvg-admin/modules/database/database-source-controller.js" },
      { path: "js/mapsvg-admin/modules/database/database-structure-controller.js" },
    ]

    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminDatabaseController = MapSVGAdminDatabaseController
  MapSVG.extend(MapSVGAdminDatabaseController, window.MapSVGAdminController)

  MapSVGAdminDatabaseController.prototype.viewLoaded = function () {
    var _this = this
    this.controllers.list = new MapSVGAdminDatabaseListController(
      "mapsvg-data-list",
      _this.admin,
      _this.mapsvg,
    )
    this.controllers.structure = new MapSVGAdminDatabaseStructureController(
      "mapsvg-data-structure",
      _this.admin,
      _this.mapsvg,
    )
    this.controllers.settings = new MapSVGAdminDatabaseSettingsController(
      "mapsvg-data-settings",
      _this.admin,
      _this.mapsvg,
    )
    this.controllers.csv = new MapSVGAdminDatabaseCsvController(
      "mapsvg-data-csv",
      _this.admin,
      _this.mapsvg,
    )
    this.controllers.csv.toolbarView = this.toolbarView
    this.controllers.list.toolbarView = this.toolbarView
    this.controllers.structure.toolbarView = this.toolbarView
    this.controllers.settings.toolbarView = this.toolbarView
    _this.activeController = this.controllers.list
    MapSVGAdminController.prototype.viewLoaded.call(this)
  }

  MapSVGAdminDatabaseController.prototype.viewDidAppear = function () {
    MapSVGAdminController.prototype.viewDidAppear.call(this)
    var _this = this
    if (
      _this.activeController &&
      _this.activeController instanceof MapSVGAdminDatabaseStructureController
    ) {
      _this.admin.rememberPanelsState()
      _this.admin.togglePanel("left", false)
    }
    this.toggleButtons()
  }

  MapSVGAdminDatabaseController.prototype.toggleButtons = function () {
    this.isDbConnectedToPosts =
      this.mapsvg.objectsRepository.getSchema().name.indexOf("posts_") !== -1
    this.isApiSource = this.mapsvg.objectsRepository.getSchema().type === "api"

    this.toolbarView.find("#mapsvg-btn-database-structure").toggle(!this.isDbConnectedToPosts)
    this.toolbarView
      .find("#mapsvg-btn-csv-import")
      .toggle(!this.isDbConnectedToPosts && !this.isApiSource)
    this.toolbarView.find("#mapsvg-btn-data-add").toggle(!this.isDbConnectedToPosts)
    this.container.toggleClass("mapsvg-db-type-posts", this.isDbConnectedToPosts)
  }

  MapSVGAdminDatabaseController.prototype.viewDidDisappear = function () {
    MapSVGAdminController.prototype.viewDidDisappear.call(this)
    this.admin.restorePanelsState()
  }
  MapSVGAdminDatabaseController.prototype.setEventHandlers = function () {
    var _this = this

    _this.database.events.on("loaded", function () {
      _this.setFilters()
    })

    this.view.on("click", ".mapsvg-filter-delete", function () {
      var filterField = $(this).data("filter")
      delete _this.mapsvg.objectsRepository.query.filters[filterField]
      if (filterField === "search") {
        $("#mapsvg-data-search").val("")
        _this.mapsvg.objectsRepository.query.search = ""
      }
      _this.mapsvg.loadDataObjects()
      _this.setFilters()
      _this.controllers.list.redrawDataList()
    })

    // this.toolbarView.on('keyup.menu.mapsvg', '#mapsvg-data-search',function () {
    //     _this.mapsvg.database.query.search = $(this).val();
    //     _this.mapsvg.loadDataObjects();
    //     _this.setFilters();
    //     _this.controllers.list.redrawDataList();
    // });

    $("#mapsvg-data-menu a")
      .click(function (e) {
        e.preventDefault()
        if ($(this).attr("href") === "#mapsvg-data-source") {
          _this.admin.slideToController(_this, "database-source", "back")
          return false
        }
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

        if ($(this).attr("href") == "#mapsvg-data-list") {
          _this.toolbarView.find(".mapsvg-toolbar-buttons").show()
        } else {
          _this.toolbarView.find(".mapsvg-toolbar-buttons").hide()
        }

        if ($(this).attr("href") == "#mapsvg-data-structure") {
          _this.admin.rememberPanelsState()
          _this.admin.togglePanel("left", false)
        } else {
          _this.admin.restorePanelsState()
        }

        if ($(this).attr("href") == "#mapsvg-data-list") {
          $(".mapsvg-toolbar-buttons").show()
        }
      })
  }
  MapSVGAdminDatabaseController.prototype.toggleDataSourceMenu = function () {
    var _this = this
    if (_this.menuVisible) {
      _this.menuVisible = false
      this.contentView.css({ transform: "" })
    } else {
      _this.menuVisible = true
      this.contentView.css({ transform: "translateX(200px)" })
    }
  }
  MapSVGAdminDatabaseController.prototype.loadDataSourceController = function () {
    var _this = this
    if (_this.dataSourceContainer) _this.dataSourceContainer.empty().remove()
    _this.dataSourceContainer = $('<div class="mapsvg-modal-edit"></div>')
    this.contentView.append(_this.dataSourceContainer)
    _this.dataSourceContainer.html("test")
  }
  MapSVGAdminDatabaseController.prototype.setFilters = function (filters) {
    var _this = this
    var filters = this.toolbarView.find(".mapsvg-toolbar-filters").empty()
    if (
      _this.mapsvg.objectsRepository.query.search ||
      (_this.mapsvg.objectsRepository.query.filters &&
        Object.keys(_this.mapsvg.objectsRepository.query.filters).length > 0)
    ) {
      for (var field_name in _this.mapsvg.objectsRepository.query.filters) {
        var filterVals
        var visibleFilterName = field_name
        if (field_name === "regions") {
          filterVals =
            _this.mapsvg.objectsRepository.query.filters[field_name].region_ids.join(", ")
        } else if (field_name === "distance") {
          visibleFilterName = "search by address"
          filterVals = ""
        } else {
          let val = _this.mapsvg.objectsRepository.query.filters[field_name]
          if (typeof val === "object" && val[0] && val[0].value) {
            val = val.map((el) => el.label).join(", ")
          }
          filterVals = val
        }
        filters.append(
          '<div class="mapsvg-filter-tag">' +
            visibleFilterName +
            (filterVals ? ": " + filterVals : "") +
            ' <span class="mapsvg-filter-delete" data-filter="' +
            field_name +
            '">×</span></div>',
        )
      }
      if (_this.mapsvg.objectsRepository.query.search) {
        filters.append(
          '<div class="mapsvg-filter-tag">search: ' +
            _this.mapsvg.objectsRepository.query.search +
            ' <span class="mapsvg-filter-delete" data-filter="search">×</span></div>',
        )
      }
      this.view.addClass("mapsvg-with-filter")
    } else {
      this.view.removeClass("mapsvg-with-filter")
    }

    var elem = document.getElementById("mapsvg-admin-controller-database")
    elem.style.display = "none"
    elem.offsetHeight // no need to store this anywhere, the reference is enough
    elem.style.display = "flex"

    // this.updateTopShift();
  }
})(jQuery, window, window.MapSVG)
