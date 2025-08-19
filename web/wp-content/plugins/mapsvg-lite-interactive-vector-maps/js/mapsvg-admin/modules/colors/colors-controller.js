;(function ($, window, MapSVG) {
  var MapSVGAdminColorsController = function (container, admin, mapsvg) {
    this.name = "colors"
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminColorsController = MapSVGAdminColorsController
  MapSVG.extend(MapSVGAdminColorsController, window.MapSVGAdminController)

  MapSVGAdminColorsController.prototype.setEventHandlers = function () {
    var _this = this

    var selected = _this.mapsvg.options.colors.selected
    var hover = _this.mapsvg.options.colors.hover
    var markerColors = _this.mapsvg.options.colors.markers

    $("#mapsvg-controls-hover-brightness").ionRangeSlider({
      type: "single",
      grid: true,
      min: -100,
      max: 100,
      from: $.isNumeric(hover) ? hover : 0,
    })
    $("#mapsvg-controls-selected-brightness").ionRangeSlider({
      type: "single",
      grid: true,
      min: -100,
      max: 100,
      from: $.isNumeric(selected) ? selected : 0,
    })
    $("#mapsvg-controls-markers-base-o").ionRangeSlider({
      type: "single",
      min: 0,
      max: 100,
      from: markerColors.base.opacity,
    })
    $("#mapsvg-controls-markers-base-s").ionRangeSlider({
      type: "single",
      min: 0,
      max: 100,
      from: markerColors.base.saturation,
    })
    $("#mapsvg-controls-markers-hovered-o").ionRangeSlider({
      type: "single",
      min: 0,
      max: 100,
      from: markerColors.hovered.opacity,
    })
    $("#mapsvg-controls-markers-hovered-s").ionRangeSlider({
      type: "single",
      min: 0,
      max: 100,
      from: markerColors.hovered.saturation,
    })
    $("#mapsvg-controls-markers-unhovered-o").ionRangeSlider({
      type: "single",
      min: 0,
      max: 100,
      from: markerColors.unhovered.opacity,
    })
    $("#mapsvg-controls-markers-unhovered-s").ionRangeSlider({
      type: "single",
      min: 0,
      max: 100,
      from: markerColors.unhovered.saturation,
    })
    $("#mapsvg-controls-markers-active-o").ionRangeSlider({
      type: "single",
      min: 0,
      max: 100,
      from: markerColors.active.opacity,
    })
    $("#mapsvg-controls-markers-active-s").ionRangeSlider({
      type: "single",
      min: 0,
      max: 100,
      from: markerColors.active.saturation,
    })
    $("#mapsvg-controls-markers-inactive-o").ionRangeSlider({
      type: "single",
      min: 0,
      max: 100,
      from: markerColors.inactive.opacity,
    })
    $("#mapsvg-controls-markers-inactive-s").ionRangeSlider({
      type: "single",
      min: 0,
      max: 100,
      from: markerColors.inactive.saturation,
    })

    $(".mapsvg-color-brightness").on("change", ":radio", function () {
      var val = $(this).closest(".mapsvg-color-brightness :radio:checked").val()
      var container = $(this).closest(".form-group")
      if (val == "color") {
        container.find(".cpicker").show()
        container.find(".irs").hide()
      } else {
        container.find(".cpicker").hide()
        container.find(".irs").show()
      }
    })

    if ($.isNumeric(selected)) {
      $("#mapsvg-colors-selected :radio")
        .eq(0)
        .prop("checked", false)
        .parent()
        .removeClass("active")
      $("#mapsvg-colors-selected :radio").eq(1).prop("checked", true).parent().addClass("active")
      $("#mapsvg-colors-selected .cpicker").hide()
      $("#mapsvg-colors-selected .irs").show()
    } else {
      $("#mapsvg-colors-selected :radio").eq(0).prop("checked", true).parent().addClass("active")
      $("#mapsvg-colors-selected :radio")
        .eq(1)
        .prop("checked", false)
        .parent()
        .removeClass("active")
      $("#mapsvg-colors-selected .cpicker").show().find("input").val(selected)
      $("#mapsvg-colors-selected .irs").hide()
    }

    if ($.isNumeric(hover)) {
      $("#mapsvg-colors-hover :radio").eq(0).prop("checked", false).parent().removeClass("active")
      $("#mapsvg-colors-hover :radio").eq(1).prop("checked", true).parent().addClass("active")
      $("#mapsvg-colors-hover .cpicker").hide()
      $("#mapsvg-colors-hover .irs").show()
    } else {
      $("#mapsvg-colors-hover :radio").eq(0).prop("checked", true).parent().addClass("active")
      $("#mapsvg-colors-hover :radio").eq(1).prop("checked", false).parent().removeClass("active")
      $("#mapsvg-colors-hover .cpicker").show().find("input").val(hover)
      $("#mapsvg-colors-hover .irs").hide()
    }

    _this.view
      .find(".cpicker")
      .colorpicker()
      .on("changeColor.colorpicker", function (event) {
        const input = $(this).find("input")[0]

        if (input.value === "") {
          $(this).find("i").css({ "background-color": "" })
        }

        input.value = window.mapsvg.utils.strings.fixColorHash(input.value)

        _this.formToObjectUpdate({ target: input })
      })
  }
})(jQuery, window, window.MapSVG)
