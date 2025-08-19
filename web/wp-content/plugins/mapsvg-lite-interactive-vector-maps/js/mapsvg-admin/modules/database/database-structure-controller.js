;(function ($, window, MapSVG) {
  var MapSVGAdminDatabaseStructureController = function (
    container,
    admin,
    mapsvg,
    databaseService,
  ) {
    this.name = "database-structure"
    this.scrollable = false
    this.database = mapsvg.objectsRepository
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminDatabaseStructureController = MapSVGAdminDatabaseStructureController
  MapSVG.extend(MapSVGAdminDatabaseStructureController, window.MapSVGAdminController)

  MapSVGAdminDatabaseStructureController.prototype.viewDidAppear = function () {
    var _this = this
    MapSVGAdminController.prototype.viewDidAppear.call(this)
    this.formBuilder = new mapsvg.formBuilder({
      schema: _this.database.getSchema(),
      editMode: true,
      mapsvg: _this.mapsvg,
      admin: _this.admin,
      container: this.contentView,
      events: {
        saveSchema: ({ data: { formBuilder, fields } }) => {
          var schema = _this.database.getSchema()
          schema.update({ fields })
          let schemRepo = mapsvg.useRepository("schemas", this.mapsvg)
          schemRepo.init()
          let oldMarkersByFieldEnabled = _this.database.getSchema().getField("location")
            ? _this.database.getSchema().getField("location").markersByFieldEnabled
            : null
          let newMarkersByFieldEnabled = schema.getField("location")
            ? schema.getField("location").markersByFieldEnabled
            : null
          schemRepo
            .update(schema)
            .done(function () {
              if (newMarkersByFieldEnabled || oldMarkersByFieldEnabled) {
                _this.mapsvg.objectsRepository.getLoaded().forEach((object) => {
                  object.reloadMarkerImage()
                })
              }
              formBuilder.updateExtraParamsInFormElements()
              $.growl.notice({ title: "", message: "Settings saved", duration: 700 })
            })
            .fail((response) => {
              MapSVG.handleFailedRequest(response)
            })
          this.admin.save(true)
        },
        init: () => {
          setTimeout(() => {
            $(".tooltip").remove()
          }, 200)
        },
      },
    })
    this.formBuilder.init()

    // _this.formBuilder.view.appendTo(this.contentView);
  }
  MapSVGAdminDatabaseStructureController.prototype.viewDidDisappear = function () {
    MapSVGAdminController.prototype.viewDidDisappear.call(this)
    this.formBuilder && this.formBuilder.destroy()
  }

  MapSVGAdminDatabaseStructureController.prototype.setEventHandlers = function () {
    var _this = this
  }
})(jQuery, window, window.MapSVG)
