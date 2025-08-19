;(function ($, window, MapSVG) {
  var MapSVGAdminDatabaseCsvController = function (container, admin, mapsvg) {
    this.name = "database-csv"
    this.database = mapsvg.objectsRepository
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminDatabaseCsvController = MapSVGAdminDatabaseCsvController
  MapSVG.extend(MapSVGAdminDatabaseCsvController, window.MapSVGAdminController)

  MapSVGAdminDatabaseCsvController.prototype.setEventHandlers = function () {
    var _this = this
    this.view.find("#mapsvg-btn-csv-upload").on("click", function () {
      var btn = $(this)
      var convertLatlngToAddress = $('input[name="convert_latlng_to_address"]').is(":checked")
      if ($("#mapsvg-csv-file")[0].files[0]) {
        Papa.parse($("#mapsvg-csv-file")[0].files[0], {
          header: true,
          transformHeader: function (header) {
            return header.toLowerCase().split(" ").join("_")
          },
          skipEmptyLines: true,
          complete: function (results) {
            if (results.errors.length === 0) {
              _this.import(results.data, convertLatlngToAddress)
            } else {
              var text = "Errors: \n"

              if (
                results.errors.length === 1 &&
                results.errors[0].code === "UndetectableDelimiter"
              ) {
                var fieldName = results.meta.fields[0]
                var fieldNameIsCorrect =
                  typeof _this.database.schema.getField(fieldName) != "undefined"

                if (results.meta.fields.length === 1 && fieldNameIsCorrect === true) {
                  _this.import(results.data, convertLatlngToAddress)
                } else {
                  text += results.errors[0].message
                  alert(text)
                }
              } else {
                text += results.errors
                  .map(function (e) {
                    return e.message + (e.row !== undefined ? " (row: " + e.row + ")" : "")
                  })
                  .slice(0, 5)
                  .join("\n")
                if (results.errors.length > 5) {
                  text += "\n...and " + (results.errors.length - 5) + " more. "
                }
                alert(text)
              }
            }
          },
        })
      } else {
        $.growl.error({ title: "", message: "Please choose a file" })
      }
    })
  }

  MapSVGAdminDatabaseCsvController.prototype.import = function (data, convertLatlngToAddress) {
    var _this = this

    var btn = $("#mapsvg-btn-csv-upload")
    btn.buttonLoading(true)

    var res = _this.database
      .import(data, convertLatlngToAddress, this.mapsvg)
      .done(function (data) {
        $.growl.notice({ title: "", message: "File uploaded" })
      })
      .always(function () {
        btn.buttonLoading(false)
      })
      .fail(function () {
        $.growl.error({ title: "", message: "Server error" })
      })
  }
})(jQuery, window, window.MapSVG)
