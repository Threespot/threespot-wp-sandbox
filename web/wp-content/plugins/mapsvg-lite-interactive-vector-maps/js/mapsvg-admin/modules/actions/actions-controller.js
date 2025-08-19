;(function ($, window, MapSVG) {
  var MapSVGAdminActionsController = function (container, admin, mapsvg) {
    this.name = "actions"
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminActionsController = MapSVGAdminActionsController
  MapSVG.extend(MapSVGAdminActionsController, window.MapSVGAdminController)

  MapSVGAdminActionsController.prototype.viewLoaded = function () {
    this.updateDirSource()
  }
  MapSVGAdminActionsController.prototype.setEventHandlers = function () {
    var _this = this
  }

  MapSVGAdminActionsController.prototype.updateDirSource = function (val) {
    val = val || this.mapsvg.getData().options.menu.source
    this.view
      .find("#mapsvg-dir-object")
      .html(val == "database" ? "Database object" : "Region object")
    this.view.find("#mapsvg-dir-source").html(val == "database" ? "Database" : "Regions")

    if (val == "database") {
      this.view
        .find("#mapsvg-dir-link")
        .attr("href", "#")
        .data("template", "detailsView")
        .html("DB Object details view template")
    } else {
      this.view
        .find("#mapsvg-dir-link")
        .attr("href", "#")
        .data("template", "detailsViewRegion")
        .html("Region details view template")
    }
  }

  MapSVGAdminActionsController.prototype.getTemplateData = function () {
    var options = MapSVGAdminController.prototype.getTemplateData.call(this)
    options.databaseFields = this.mapsvg.objectsRepository
      .getSchema()
      .getFields()
      .filter((obj) => obj.type === "text" || obj.type === "textarea" || obj.type === "post")
      .map(function (obj) {
        if (obj.type == "post") {
          return "Object.post.url"
        } else {
          return "Object." + obj.name
        }
      })
    options.regionFields = this.mapsvg.regionsRepository
      .getSchema()
      .getFields()
      .filter((obj) => obj.type === "text" || obj.type === "textarea" || obj.type === "post")
      .map(function (obj) {
        if (obj.type == "post") {
          return "Region.post.url"
        } else {
          return "Region." + obj.name
        }
      })
    options.zoomLevels = []
    var a = 1
    while (a < 21) {
      options.zoomLevels.push(a++)
    }

    options.defTemplates = {
      details: [
        { name: "default", label: "Default" },
        { name: "post", label: "WordPress post" },
        { name: "list", label: "List" },
      ],
      popover: [
        { name: "default", label: "Default" },
        { name: "post", label: "WordPress post" },
        { name: "list", label: "List" },
      ],
      tooltip: {
        region: [
          { name: "title", label: "Title" },
          { name: "imageTitle", label: "Image and title" },
        ],
        object: [
          { name: "title", label: "Title" },
          { name: "imageTitle", label: "Image and title" },
          { name: "address", label: "Address / coordinates" },
        ],
      },
    }
    return options
  }
})(jQuery, window, window.MapSVG)
