// @ts-ignore
import { mapsvgCore } from "@/Core/Mapsvg"
import { geocode, parseBoolean } from "@/Core/Utils"
import Bloodhound from "bloodhound-js"
import "typeahead.js"
import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { Server } from "../../../Infrastructure/Server/Server"
import { GeoPoint, Location } from "../../../Location/Location.js"
import { Marker } from "../../../Marker/Marker.js"
import { FormBuilder } from "../../FormBuilder.js"
import { FormElement } from "../FormElement.js"

const $ = jQuery

/**
 *
 */
export class LocationFormElement extends FormElement {
  language: string
  markerImages: { url: string; file: string; folder: string; relativeUrl?: string }[]
  markersByField: { [key: string]: any }
  markerField: string
  markersByFieldEnabled: boolean
  defaultMarkerPath: string
  location: Location
  marker: Marker
  languages: { label: string; value: string }[]
  backupData?: {
    location: Location
    Marker: Marker
  }
  isGeo: boolean

  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)

    this.label = this.label || (options.label === undefined ? "Location" : options.label)
    this.name = "location"
    this.db_type = "text"
    this.typeLabel = "Location"
    this.languages = [
      { value: "sq", label: "Albanian" },
      { value: "ar", label: "Arabic" },
      {
        value: "eu",
        label: "Basque",
      },
      { value: "be", label: "Belarusian" },
      { value: "bg", label: "Bulgarian" },
      {
        value: "my",
        label: "Burmese",
      },
      { value: "bn", label: "Bengali" },
      { value: "ca", label: "Catalan" },
      {
        value: "zh-cn",
        label: "Chinese (simplified)",
      },
      { value: "zh-tw", label: "Chinese (traditional)" },
      {
        value: "hr",
        label: "Croatian",
      },
      { value: "cs", label: "Czech" },
      { value: "da", label: "Danish" },
      {
        value: "nl",
        label: "Dutch",
      },
      { value: "en", label: "English" },
      {
        value: "en-au",
        label: "English (australian)",
      },
      { value: "en-gb", label: "English (great Britain)" },
      {
        value: "fa",
        label: "Farsi",
      },
      { value: "fi", label: "Finnish" },
      { value: "fil", label: "Filipino" },
      {
        value: "fr",
        label: "French",
      },
      { value: "gl", label: "Galician" },
      { value: "de", label: "German" },
      {
        value: "el",
        label: "Greek",
      },
      { value: "gu", label: "Gujarati" },
      { value: "iw", label: "Hebrew" },
      {
        value: "hi",
        label: "Hindi",
      },
      { value: "hu", label: "Hungarian" },
      { value: "id", label: "Indonesian" },
      {
        value: "it",
        label: "Italian",
      },
      { value: "ja", label: "Japanese" },
      { value: "kn", label: "Kannada" },
      {
        value: "kk",
        label: "Kazakh",
      },
      { value: "ko", label: "Korean" },
      { value: "ky", label: "Kyrgyz" },
      {
        value: "lt",
        label: "Lithuanian",
      },
      { value: "lv", label: "Latvian" },
      { value: "mk", label: "Macedonian" },
      {
        value: "ml",
        label: "Malayalam",
      },
      { value: "mr", label: "Marathi" },
      { value: "no", label: "Norwegian" },
      {
        value: "pl",
        label: "Polish",
      },
      { value: "pt", label: "Portuguese" },
      {
        value: "pt-br",
        label: "Portuguese (brazil)",
      },
      { value: "pt-pt", label: "Portuguese (portugal)" },
      {
        value: "pa",
        label: "Punjabi",
      },
      { value: "ro", label: "Romanian" },
      { value: "ru", label: "Russian" },
      {
        value: "sr",
        label: "Serbian",
      },
      { value: "sk", label: "Slovak" },
      { value: "sl", label: "Slovenian" },
      {
        value: "es",
        label: "Spanish",
      },
      { value: "sv", label: "Swedish" },
      { value: "tl", label: "Tagalog" },
      {
        value: "ta",
        label: "Tamil",
      },
      { value: "te", label: "Telugu" },
      { value: "th", label: "Thai" },
      {
        value: "tr",
        label: "Turkish",
      },
      { value: "uk", label: "Ukrainian" },
      { value: "uz", label: "Uzbek" },
      {
        value: "vi",
        label: "Vietnamese",
      },
    ]
    this.language = options.language
    this.markerImages = mapsvgCore.markerImages
    this.markersByField = options.markersByField
    this.markerField = options.markerField
    this.markersByFieldEnabled = parseBoolean(options.markersByFieldEnabled)
    this.defaultMarkerPath =
      options.defaultMarkerPath || this.formBuilder.mapsvg.getData().options.defaultMarkerImage

    this.templates.marker = Handlebars.compile($("#mapsvg-data-tmpl-marker").html())
  }

  init() {
    super.init()
    if (this.value) {
      this.renderMarker()
    }

    this.renderMarkerSelector()
  }

  getSchema(): { [p: string]: any } {
    const schema = super.getSchema()
    schema.language = this.language
    schema.defaultMarkerPath = this.defaultMarkerPath
    schema.markersByField = this.markersByField
    schema.markerField = this.markerField
    schema.markersByFieldEnabled = parseBoolean(this.markersByFieldEnabled)
    return schema
  }

  getData(): { name: string; value: any } {
    return { name: this.name, value: this.value }
  }

  getDataForTemplate(): { [p: string]: any } {
    const data = super.getDataForTemplate()

    if (this.formBuilder.admin) {
      data.languages = this.languages
      data.markerImages = window.mapsvgAdmin
        ? window.mapsvgAdmin.getData().options.markerImages
        : []
      data.markersByField = this.markersByField
      data.markerField = this.markerField
      data.markersByFieldEnabled = parseBoolean(this.markersByFieldEnabled)
      const _this = this
      data.markerImages.forEach(function (m) {
        if (m.relativeUrl === _this.defaultMarkerPath) {
          m.default = true
        } else {
          m.default = false
        }
      })
    }
    data.language = this.language

    if (this.value) {
      data.location = this.value
      if (data.location.marker) {
        data.location.img =
          (this.value.marker.src.indexOf(mapsvgCore.routes.uploads) === 0 ? "uploads/" : "") +
          this.value.marker.src.split("/").pop()
      }
    }

    data.showUploadBtn = data.external.filtersMode

    return data
  }

  initEditor() {
    super.initEditor()
    this.renderMarkerSelector()
    this.fillMarkersByFieldOptions(this.markerField)
  }

  setDomElements() {
    super.setDomElements()

    this.domElements.markerImageButton = $(this.domElements.main).find("img")[0]
  }

  setEventHandlers() {
    super.setEventHandlers()

    const _this = this

    // Google geocoding
    const server = new Server(mapsvgCore.routes.api)

    if (_this.formBuilder.mapsvg.isGeo()) {
      const locations = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace("formatted_address"),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
          url: server.getUrl("geocoding") + "?address=%QUERY%&language=" + this.language,
          wildcard: "%QUERY%",
          transform: function (response) {
            if (response.error_message) {
              alert(response.error_message)
            }
            return response.results
          },
          rateLimitWait: 600,
        },
      })
      const thContainer = $(this.domElements.main).find(".typeahead")
      const tH = thContainer
        .typeahead(
          { minLength: 3 },
          {
            name: "mapsvg-addresses",
            display: "formatted_address",
            async: true,
            // @ts-ignore
            source: (query, sync, async) => {
              const preg = new RegExp(
                /^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/,
              )

              if (preg.test(query)) {
                const latlng = query.split(",").map((item) => item.trim())
                const item = {
                  formatted_address: latlng.join(","),
                  address_components: [],
                  geometry: {
                    location: {
                      lat: () => latlng[0],
                      lng: () => latlng[1],
                    },
                  },
                }
                sync([item])
                return
              }

              geocode({ address: query }, async)
            },
          },
        )
        .on("typeahead:asyncrequest", function (e) {
          $(e.target).addClass("tt-loading")
        })
        .on("typeahead:asynccancel typeahead:asyncreceive", function (e) {
          $(e.target).removeClass("tt-loading")
        })

      thContainer.on("typeahead:select", (ev, item) => {
        this.setValue(null, false)

        const address: { [key: string]: string } = {}
        address.formatted = item.formatted_address
        item.address_components.forEach((addr_item) => {
          const type = addr_item.types[0]
          address[type] = addr_item.long_name
          if (addr_item.short_name != addr_item.long_name) {
            address[type + "_short"] = addr_item.short_name
          }
        })

        const locationData = {
          address: address,
          geoPoint: new GeoPoint(item.geometry.location.lat(), item.geometry.location.lng()),
          img: this.getMarkerImage(this.formBuilder.getData()),
          imagePath: this.getMarkerImage(this.formBuilder.getData()),
        }

        this.setValue(locationData, false)
        thContainer.typeahead("val", "")
        this.triggerChanged()
      })
    }

    $(this.domElements.main).on("change", ".marker-file-uploader", function () {
      _this.markerIconUpload(this)
    })

    $(this.domElements.main).on("click", ".mapsvg-marker-image-btn-trigger", function (e) {
      e.preventDefault()

      $(this).toggleClass("active")
      _this.toggleMarkerSelector()
    })

    $(this.domElements.main).on("click", ".mapsvg-marker-delete", function (e) {
      e.preventDefault()
      _this.setValue(null)
      _this.triggerChanged()
    })
  }

  setEditorEventHandlers() {
    super.setEditorEventHandlers()

    const _this = this

    $(this.domElements.edit).on("change", ".marker-file-uploader", function () {
      _this.markerIconUpload(this)
    })

    $(this.domElements.edit).on("change", 'select[name="markerField"]', function () {
      const fieldName = $(this).val()
      _this.resetMarkersByField()
      const newOptions = _this.fillMarkersByFieldOptions(fieldName)
      _this.setMarkersByField(newOptions)
    })
    $(this.domElements.edit).on("click", ".mapsvg-marker-image-selector button", function (e) {
      e.preventDefault()
      const src = $(this).find("img").attr("src")
      $(this).parent().find("button").removeClass("active")
      $(this).addClass("active")
      _this.setDefaultMarkerPath(src)
    })
    $(this.domElements.edit).on("click", ".mapsvg-marker-image-btn-trigger", function (e) {
      $(this).toggleClass("active")
      _this.toggleMarkerSelectorInLocationEditor.call(_this, $(this), e)
    })
  }

  /**
   * Upload new marker icon
   *
   * @param {HTMLInputElement} input
   */
  markerIconUpload(input) {
    const uploadBtn = $(input).closest(".btn-file").buttonLoading(true)

    for (let i = 0; i < input.files.length; i++) {
      const data = new FormData()

      data.append("file", input.files[0])

      const server = new Server(mapsvgCore.routes.api)

      server
        .ajax("markers", {
          type: "POST",
          data: data,
          processData: false,
          contentType: false,
        })
        .done((resp) => {
          if (resp.error) {
            alert(resp.error)
          } else {
            const marker = resp.marker
            const newMarker = `
                            <button class="btn btn-outline-secondary mapsvg-marker-image-btn mapsvg-marker-image-btn-choose active">
                                <img src="${marker.relativeUrl}" />
                            </button>
                            </button>
                        `

            $(this.domElements.markerSelector)
              .find(".mapsvg-marker-image-btn.active")
              .removeClass("active")
            $(newMarker).appendTo(this.domElements.markerSelector)

            this.setMarkerImage(marker.relativeUrl)
            const markerImages = window.mapsvgAdmin.getData().options.markerImages
            markerImages.push(marker)
          }
        })
        .always(function () {
          uploadBtn.buttonLoading(false)
        })
    }
  }

  mayBeAddDistanceRow() {
    const _this = this
    if (!this.domElements.editDistanceRow) {
      this.domElements.editDistanceRow = $($("#mapsvg-edit-distance-row").html())[0]
    }
    // if there's something in the last status edit field, add +1 status row
    const z = $(this.domElements.edit).find(".mapsvg-edit-distance-row:last-child input")
    if (z && z.last() && z.last().val() && (z.last().val() + "").trim().length) {
      const newRow = $(this.templates.editDistanceRow).clone()
      newRow.insertAfter($(_this.domElements.edit).find(".mapsvg-edit-distance-row:last-child"))
    }
    const rows = $(_this.domElements.edit).find(".mapsvg-edit-distance-row")
    const row1 = rows.eq(rows.length - 2)
    const row2 = rows.eq(rows.length - 1)

    if (
      row1.length &&
      row2.length &&
      !row1.find("input:eq(0)").val().toString().trim() &&
      !row2.find("input:eq(0)").val().toString().trim()
    ) {
      row2.remove()
    }
  }

  fillMarkersByFieldOptions(fieldName) {
    const _this = this
    const field = _this.formBuilder.mapsvg.objectsRepository.getSchema().getField(fieldName)
    const options = {}

    if (field) {
      const markerImg = _this.formBuilder.mapsvg.options.defaultMarkerImage
      const rows = []
      field.options.forEach(function (option) {
        const value = field.type === "region" ? option.id : option.value
        const label = field.type === "region" ? option.title || option.id : option.label
        const img =
          _this.markersByField && _this.markersByField[value]
            ? _this.markersByField[value]
            : markerImg
        rows.push(
          '<tr data-option-id="' +
            value +
            '"><td>' +
            label +
            '</td><td><button class="btn dropdown-toggle btn-outline-secondary mapsvg-marker-image-btn-trigger mapsvg-marker-image-btn"><img src="' +
            img +
            '" class="new-marker-img" /><span class="caret"></span></button></td></tr>',
        )
        options[value] = img
      })
      $("#markers-by-field").empty().append(rows)
    }
    return options
  }

  renderMarker(marker?: Marker) {
    // if (!this.value && !(marker && marker.location)) {
    //     return false;
    // }
    if (marker && marker.location) {
      this.value = marker.location.getData()
    }
    this.renderMarkerHtml()
  }

  renderMarkerHtml() {
    if (!this.value) {
      $(this.domElements.main).find(".mapsvg-new-marker").hide()
    } else {
      $(this.domElements.main)
        .find(".mapsvg-new-marker")
        .show()
        .html(this.templates.marker(this.value))
    }
  }

  toggleMarkerSelector() {
    if (
      this.domElements.markerSelectorWrap &&
      $(this.domElements.markerSelectorWrap).is(":visible")
    ) {
      $(this.domElements.markerSelectorWrap).hide()
      return
    }
    if (
      this.domElements.markerSelectorWrap &&
      $(this.domElements.markerSelectorWrap).not(":visible")
    ) {
      $(this.domElements.markerSelector).find(".active").removeClass("active")
      if (this.value && this.value.markerImagePath) {
        $(this.domElements.markerSelector)
          .find('[src="' + this.value.markerImagePath + '"]')
          .parent()
          .addClass("active")
      }
      $(this.domElements.markerSelectorWrap).show()
      return
    }
  }

  renderMarkerSelector() {
    const _this = this
    const view = this.formBuilder.editMode ? this.domElements.edit : this.domElements.main
    if (!view) return
    const currentImage = this.value ? this.value.markerImagePath : null
    const markerImages = window.mapsvgAdmin ? window.mapsvgAdmin.getData().options.markerImages : []
    const images = markerImages.map(function (image) {
      return (
        '<button class="btn btn-outline-secondary mapsvg-marker-image-btn mapsvg-marker-image-btn-choose ' +
        (currentImage == image.relativeUrl ? "active" : "") +
        '"><img src="' +
        image.relativeUrl +
        '" /></button>'
      )
    })

    this.domElements.markerSelectorWrap = $(view).find(".mapsvg-marker-image-selector")[0]
    this.domElements.markerSelector = $(this.domElements.markerSelectorWrap).find(
      ".mapsvg-marker-images",
    )[0]

    // delete previous marker image selector and reset events
    if (this.domElements.markerSelector) {
      $(this.domElements.markerSelector).empty()
    }

    if (!this.formBuilder.editMode) {
      this.domElements.markerSelectorWrap.style.display = "none"
      $(this.domElements.markerSelector).on(
        "click",
        ".mapsvg-marker-image-btn-choose",
        function (e) {
          e.preventDefault()
          const src = $(this).find("img").attr("src")

          $(_this.domElements.markerSelectorWrap).hide()
          $(_this.domElements.markerSelector).find(".active").removeClass("active")
          $(this).addClass("active")
          $(_this.domElements.main)
            .find(".mapsvg-marker-image-btn-trigger")
            .toggleClass("active", false)
          $(_this.domElements.markerImageButton).attr("src", src)

          _this.setMarkerImage(src)
        },
      )
    }
    $(this.domElements.markerSelector).html(images.join(""))
  }

  private setMarkerImage(src: string) {
    this.setDefaultMarkerPath(src)
    const value = this.value
    if (value) {
      value.img = src
      value.imagePath = src
      this.setValue(value)
      this.triggerChanged()
      this.renderMarker()
    }
  }

  toggleMarkerSelectorInLocationEditor(jQueryObj, e) {
    e.preventDefault()
    const _this = this
    if (jQueryObj.data("markerSelector") && jQueryObj.data("markerSelector").is(":visible")) {
      jQueryObj.data("markerSelector").hide()
      return
    }
    if (jQueryObj.data("markerSelector") && jQueryObj.data("markerSelector").not(":visible")) {
      jQueryObj.data("markerSelector").show()
      return
    }

    const markerBtn = $(this).closest("td").find(".mapsvg-marker-image-btn-trigger")
    const currentImage = markerBtn.attr("src")
    const markerImages = window.mapsvgAdmin ? window.mapsvgAdmin.getData().options.markerImages : []
    const images = markerImages.map(function (image) {
      return (
        '<button class="btn btn-outline-secondary mapsvg-marker-image-btn mapsvg-marker-image-btn-choose ' +
        (currentImage == image.relativeUrl ? "active" : "") +
        '"><img src="' +
        image.relativeUrl +
        '" /></button>'
      )
    })

    if (!jQueryObj.data("markerSelector")) {
      const ms = $('<div class="mapsvg-marker-image-selector"></div>')
      jQueryObj.closest("td").append(ms)
      jQueryObj.data("markerSelector", ms)
    } else {
      jQueryObj.data("markerSelector").empty()
    }

    jQueryObj.data("markerSelector").html(images.join(""))

    jQueryObj.data("markerSelector").on("click", ".mapsvg-marker-image-btn-choose", function (e) {
      e.preventDefault()
      const src = $(this).find("img").attr("src")
      jQueryObj.data("markerSelector").hide()
      const td = $(this).closest("td")
      const fieldId = $(this).closest("tr").data("option-id")
      const btn = td.find(".mapsvg-marker-image-btn-trigger")
      btn.toggleClass("active", false)
      btn.find("img").attr("src", src)
      _this.setMarkerByField(fieldId, src)
    })
  }

  setMarkersByField(options: { [key: string]: string }): void {
    this.markersByField = options
  }
  resetMarkersByField() {
    this.markersByField = {}
  }
  setMarkerByField(fieldId, markerImg) {
    this.markersByField = this.markersByField || {}
    this.markersByField[fieldId] = markerImg
  }

  deleteMarker() {
    const _this = this

    $(this.domElements.main).find(".mapsvg-new-marker").hide()
    $(this.domElements.main).find(".mapsvg-marker-id").attr("disabled", "disabled")
  }

  destroy() {
    super.destroy()
    this.domElements.markerSelector && $(this.domElements.markerSelector).popover("dispose")
  }

  setDefaultMarkerPath(path: string): void {
    this.defaultMarkerPath = path
    this.formBuilder.mapsvg.setDefaultMarkerImage(path)
  }

  getMarkerImage(data: { [key: string]: any }): string {
    let fieldValue

    if (this.markersByFieldEnabled) {
      fieldValue = data[this.markerField]
      if (!fieldValue) {
        return this.defaultMarkerPath || mapsvgCore.defaultMarkerImage
      } else {
        if (this.markerField === "regions") {
          fieldValue = fieldValue[0] && fieldValue[0].id
        } else if (typeof fieldValue === "object" && fieldValue.length) {
          fieldValue = fieldValue[0].value
        }
        if (this.markersByField[fieldValue]) {
          return (
            this.markersByField[fieldValue] ||
            this.defaultMarkerPath ||
            mapsvgCore.defaultMarkerImage
          )
        }
      }
    } else {
      return this.defaultMarkerPath || mapsvgCore.defaultMarkerImage
    }
  }

  setValue(value: any, updateInput = true): void {
    this.value = value
    if (this.value) {
      if (!this.value.address) {
        this.value.address = {}
      }
      if (Object.keys(this.value.address).length < 2 && this.value.geoPoint) {
        this.value.address.formatted = this.value.geoPoint.lat + "," + this.value.geoPoint.lng
      }
    }
    this.renderMarker()
  }
}
