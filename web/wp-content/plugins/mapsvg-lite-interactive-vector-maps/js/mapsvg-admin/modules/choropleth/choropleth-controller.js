;(function ($, window, MapSVG) {
  let MapSVGAdminChoroplethController = function (container, admin, mapsvg) {
    this.name = "choropleth"
    MapSVGAdminController.call(this, container, admin, mapsvg)
  }
  window.MapSVGAdminChoroplethController = MapSVGAdminChoroplethController
  MapSVG.extend(MapSVGAdminChoroplethController, window.MapSVGAdminController)

  MapSVGAdminChoroplethController.prototype.viewLoaded = function () {
    let _this = this

    this.view.find(".mapsvg-select2").mselect2()

    this.updateChoroplethSourceFields()
    this.updateChoroplethPaletteColors()

    let paletteColors = _this.mapsvg.getOptions().choropleth.coloring.palette.colors
    if (paletteColors.length < 2) {
      $("#mapsvg-delete-last-palette-color-btn").attr("disabled", "true")
    }
  }

  MapSVGAdminChoroplethController.prototype.setEventHandlers = function () {
    let _this = this

    $("#mapsvg-controls-choropleth").on("change", ":radio", function () {
      let on = MapSVG.parseBoolean($("#mapsvg-controls-choropleth :radio:checked").val())

      $("#table-regions").removeClass("mapsvg-choropleth-on")

      if (on) $("#table-regions").addClass("mapsvg-choropleth-on")
      on ? $("#mapsvg-choropleth-options").show() : $("#mapsvg-choropleth-options").hide()

      _this.admin.updateScroll()
    })

    this.view
      .find(".cpicker")
      .colorpicker()
      .on("changeColor.colorpicker", function () {
        let input = $(this).find("input")

        if (input.val() == "") $(this).find("i").css({ "background-color": "" })
        _this.formToObjectUpdate({ target: input[0] })
      })

    this.view.find("#mapsvg-choropleth-control").on("change", ":radio", function () {
      let value = MapSVG.parseBoolean($("#mapsvg-choropleth-control").find(":radio:checked").val())

      if (value) $("#table-regions").addClass("mapsvg-choropleth-on")
      else $("#table-regions").removeClass("mapsvg-choropleth-on")
    })

    this.view.find('input[name="choropleth[source]"]').on("change", function (e) {
      _this.updateChoroplethSourceFields(this.value)

      _this.mapsvg.setChoropleth({
        sourceField: null,
        sourceFieldSelect: {
          on: null,
          variants: [],
        },
      })

      if (this.value === "database") {
        $('input[name="choropleth[bubbleMode]"]')
          .bootstrapToggle("on")
          .parents(".toggle.btn-default")
          .addClass("disabled")
      } else {
        let input_bubbleMode = $('input[name="choropleth[bubbleMode]"]')
        $(input_bubbleMode)
          .bootstrapToggle($(input_bubbleMode).data("last-regions-state"))
          .parents(".toggle.btn-default")
          .removeClass("disabled")
      }

      $('input[name="choropleth[sourceFieldSelect][on]"]').bootstrapToggle("off")
      $('select[name="choropleth[sourceFieldSelect][variants]"]').val(null).trigger("change")
    })

    this.view.find("#mapsvg-add-palette-color-btn").on("click", function (e) {
      let colors = _this.mapsvg.getOptions().choropleth.coloring.palette.colors,
        lastColor = colors[colors.length - 1],
        newColor = jQuery.extend(true, {}, lastColor)

      newColor.from = lastColor.to
      newColor.to = newColor.from + lastColor.to - lastColor.from
      newColor.description = ""

      colors.push(newColor)

      _this.mapsvg.setChoropleth({ coloring: { palette: { colors: colors } } })

      let newColorIdx = $(".mapsvg-coloring-palette-color").length

      _this.addChoroplethPaletteColor(newColor, newColorIdx)

      _this.initColorPickers()

      _this.initDeletePaletteColorBtns()
    })

    this.view.find('input[name="choropleth[coloring][mode]"]').on("change", function (e) {
      if ($(this).val() === "gradient") {
        _this.view.find("#mapsvg-choropleth-gradient-options").toggle(true)
        _this.view.find("#mapsvg-choropleth-palette-options").toggle(false)
      } else {
        _this.view.find("#mapsvg-choropleth-gradient-options").toggle(false)
        _this.view.find("#mapsvg-choropleth-palette-options").toggle(true)
      }
    })

    this.view.find('input[name="choropleth[bubbleMode]"]').on("change", function (e) {
      if ($('input[name="choropleth[source]"]:checked').val() === "regions") {
        $(this).data("last-regions-state", $(this).is(":checked") ? "on" : "off")
      }
    })
  }

  MapSVGAdminChoroplethController.prototype.updateChoroplethSourceFields = function (source) {
    source = source || this.mapsvg.getOptions().choropleth.source

    let sourceRepository

    if (source === "regions") {
      sourceRepository = this.mapsvg.regionsRepository
    } else {
      sourceRepository = this.mapsvg.objectsRepository
    }
    let schema = sourceRepository.getSchema()

    let sourceField = this.mapsvg.getOptions().choropleth.sourceField
    let sourceFieldVariants = this.mapsvg.getOptions().choropleth.sourceFieldSelect.variants
      ? this.mapsvg.getOptions().choropleth.sourceFieldSelect.variants
      : []

    let select_sourceField = this.view.find("#mapsvg-choropleth-source-fields").empty()
    let select_sourceFieldVariants = this.view.find("#mapsvg-choropleth-source-field-variants")

    select_sourceFieldVariants.empty()
    select_sourceField.append($("<option " + (!sourceField ? "selected" : "") + "></option>"))

    schema.fields.forEach(function (field) {
      if (field.type === "text") {
        select_sourceField.append(
          "<option " +
            (sourceField === field.name ? "selected" : "") +
            ">" +
            field.name +
            "</option>",
        )
        select_sourceFieldVariants.append(
          "<option " +
            (sourceFieldVariants.includes(field.name) ? "selected" : "") +
            ">" +
            field.name +
            "</option>",
        )
      }
    })

    select_sourceField.trigger("change")
    select_sourceFieldVariants.trigger("change")
  }

  MapSVGAdminChoroplethController.prototype.updateChoroplethPaletteColors = function (colors) {
    colors = colors || this.mapsvg.getOptions().choropleth.coloring.palette.colors

    const _this = this

    jQuery(".mapsvg-coloring-palette-color").remove()

    colors.forEach(function (color, idx) {
      _this.addChoroplethPaletteColor(color, idx)
    })

    this.initColorPickers()

    this.initDeletePaletteColorBtns()
  }

  MapSVGAdminChoroplethController.prototype.addChoroplethPaletteColor = function (color, idx) {
    let row = $(this.templates.paletteColor({ color: color, idx: idx }))
    this.view.find("#mapsvg-choropleth-palette-colors-list").append(row)
  }

  MapSVGAdminChoroplethController.prototype.initColorPickers = function () {
    const _this = this

    this.view
      .find(".cpicker")
      .colorpicker()
      .on("changeColor.colorpicker", function () {
        let input = $(this).find("input")

        if (input.val() == "") $(this).find("i").css({ "background-color": "" })

        _this.formToObjectUpdate({ target: input[0] })
      })
  }

  MapSVGAdminChoroplethController.prototype.initDeletePaletteColorBtns = function () {
    const _this = this

    this.view.find(".mapsvg-delete-palette-color-btn").off("click")

    this.view.find(".mapsvg-delete-palette-color-btn").on("click", function (e) {
      let colors = _this.mapsvg.getOptions().choropleth.coloring.palette.colors,
        btn = this

      if (colors.length > 1) {
        colors.splice($(btn).data("idx"), 1)

        _this.mapsvg.setChoropleth({ coloring: { palette: { colors: colors } } })

        _this.updateChoroplethPaletteColors(colors)
      } else {
        alert("At least one color should remain!")
      }
    })
  }
})(jQuery, window, window.MapSVG)
