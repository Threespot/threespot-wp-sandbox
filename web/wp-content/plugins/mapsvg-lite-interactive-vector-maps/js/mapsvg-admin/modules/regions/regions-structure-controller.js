;(function ($, window, MapSVG) {
  var MapSVGAdminRegionsStructureController = function (container, admin, mapsvg, databaseService) {
    this.name = "regions-structure"
    this.scrollable = false
    this.database = mapsvg.regionsRepository
    //this.schemaRepository
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminRegionsStructureController = MapSVGAdminRegionsStructureController
  MapSVG.extend(MapSVGAdminRegionsStructureController, window.MapSVGAdminController)

  MapSVGAdminRegionsStructureController.prototype.viewDidAppear = function () {
    MapSVGAdminController.prototype.viewDidAppear.call(this)
    this.formBuilder = new mapsvg.formBuilder({
      schema: this.database.getSchema(),
      editMode: true,
      mapsvg: this.mapsvg,
      admin: this.admin,
      container: this.contentView,
      types: ["text", "textarea", "checkbox", "radio", "select", "image", "status", "date", "post"],
      events: {
        saveSchema: ({ data: { formBuilder, fields } }) => {
          var schema = this.database.getSchema()
          schema.update({ fields })
          let schemRepo = new mapsvg.useRepository("schemas", this.mapsvg)
          schemRepo.init()
          schemRepo
            .update(schema)
            .done(() => {
              let _status = this.database.getSchema().getField("status")
              if (_status) {
                const regionStatuses = _status.options.toObject()
                this.mapsvg.setRegionStatuses(regionStatuses)
                this.admin.save(true)
              }
              $.growl.notice({ title: "", message: "Settings saved", duration: 700 })
            })
            .fail(() => {
              $.growl.error({
                title: "Server error",
                message: "Can't save settings",
              })
            })
        },
        init: (formBuilder) => {
          setTimeout(() => {
            $(".tooltip").remove()
          }, 200)
        },
      },
    })
    this.formBuilder.init()
  }
  MapSVGAdminRegionsStructureController.prototype.viewDidDisappear = function () {
    MapSVGAdminController.prototype.viewDidDisappear.call(this)
    this.formBuilder && this.formBuilder.destroy()
  }

  MapSVGAdminRegionsStructureController.prototype.setEventHandlers = function () {
    var _this = this
  }
})(jQuery, window, window.MapSVG)
