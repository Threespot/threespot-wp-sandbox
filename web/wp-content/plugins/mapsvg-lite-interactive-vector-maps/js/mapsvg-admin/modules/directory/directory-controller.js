;(function ($, window, MapSVG) {
  var MapSVGAdminDirectoryController = function (container, admin, mapsvg) {
    this.name = "directory"
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminDirectoryController = MapSVGAdminDirectoryController
  MapSVG.extend(MapSVGAdminDirectoryController, window.MapSVGAdminController)

  MapSVGAdminDirectoryController.prototype.setEventHandlers = function () {
    var filterout = this.view.find("#mapsvg-directory-filterout")
    filterout.on("change", "#mapsvg-directory-filter-control", function () {
      if ($(this).val()) {
        filterout.find("#mapsvg-filterout-extra").show()
      } else {
        filterout.find("#mapsvg-filterout-extra").hide()
      }
    })
  }

  MapSVGAdminDirectoryController.prototype.updateDirSource = function (val) {
    val = val || this.mapsvg.getData().options.menu.source
    this.view
      .find("#mapsvg-dir-object-2")
      .html(val == "database" ? "Database object" : "Region object")
  }

  MapSVGAdminDirectoryController.prototype.setDatabase = function (val) {
    this.database = val == "regions" ? this.mapsvg.regionsRepository : this.mapsvg.objectsRepository
  }

  MapSVGAdminDirectoryController.prototype.viewLoaded = function () {
    var _this = this

    _this.setDatabase(this.mapsvg.getData().options.menu.source)
    _this.updateDirSource()
    _this.setSortFields()

    this.view.find("#mapsvg-directory-data-source").on("change", ":radio", function () {
      var val = $(this).val()
      _this.setDatabase(val)
      _this.setSortFields()
      _this.updateDirSource(val)
      if (_this.admin.getData().controllers["actions"])
        _this.admin.getData().controllers["actions"].updateDirSource(val)
      // setTimeout(function(){
      // },1000);
    })
    this.view.find("#mapsvg-directory-sort-control").mselect2()
    this.view.find("#mapsvg-directory-group-control").mselect2()
    this.view.find("#mapsvg-directory-filter-control").mselect2()
    this.view.find("#mapsvg-directory-filter-cond-control").mselect2()
    this.view.on("change", "#mapsvg-details-width :radio", function () {
      var value = $(this).closest(".form-group").find(":radio:checked").val()
      if (value != "full") {
        $("#mapsvg-details-width-custom").prop("disabled", false).trigger("keyup")
      } else {
        $("#mapsvg-details-width-custom").prop("disabled", true)
      }
    })
    _this.updateSortList()
  }
  MapSVGAdminDirectoryController.prototype.updateSortList = function () {
    var _this = this
  }

  MapSVGAdminController.prototype.getTemplateData = function () {
    var data = this.mapsvg.getOptions(true, null, this.admin.getData().optionsDelta)
    data.fullTextMinWord_len = window.mapsvgAdmin.getData().options.fullTextMinWord
    return data
  }

  MapSVGAdminDirectoryController.prototype.setSortFields = function () {
    var _this = this
    var source = $("#mapsvg-directory-data-source :radio:checked").val()
    var _fields = []
    var schema = this.database.getSchema()
    var _fieldsSelect = []
    if (schema) {
      schema.fields.forEach(function (obj) {
        _fields.push(obj.name)
        if (
          (obj.type === "select" && !obj.multiselect) ||
          obj.type === "radio" ||
          obj.type === "status"
        ) {
          _fieldsSelect.push(obj.name)
        }
      })
    }
    // this.sort = {
    //     regions: ['id','title'],
    //     database: _fields
    // };
    // var source = this.mapsvg.getData().options.menu.source;
    // var options = _this.sort[source].map(function(field){
    var options = _fields.map(function (field) {
      return (
        "<option " +
        (_this.templateData.menu.sortBy == field ? "selected" : "") +
        ">" +
        field +
        "</option>"
      )
    })
    var options2 = _fields.map(function (field) {
      return (
        "<option " +
        (_this.templateData.menu.filterout.field == field ? "selected" : "") +
        ">" +
        field +
        "</option>"
      )
    })
    var options3 = _fieldsSelect.map(function (field) {
      return (
        "<option " +
        (_this.templateData.menu.categories.groupBy == field ? "selected" : "") +
        ">" +
        field +
        "</option>"
      )
    })

    options2.unshift('<option value="">(no filter)</option>')
    options3.unshift('<option value="">...</option>')

    this.view.find("#mapsvg-directory-sort-control").html(options).trigger("change")
    this.view.find("#mapsvg-directory-group-control").html(options3).trigger("change")
    this.view.find("#mapsvg-directory-filter-control").html(options2).trigger("change")
    this.view
      .find("#mapsvg-directory-filter-cond-control")
      .val(_this.templateData.menu.filterout.cond)
  }
})(jQuery, window, window.MapSVG)
