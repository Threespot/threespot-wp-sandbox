// @ts-ignore
import Bloodhound from "bloodhound-js"
// import "typeahead.js"

import { mapsvgCore } from "@/Core/Mapsvg"
import { geocode, parseBoolean, ucfirst } from "@/Core/Utils"
import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { Server } from "../../../Infrastructure/Server/Server"
import { Location } from "../../../Location/Location"
import { FormBuilder } from "../../FormBuilder.js"
import { FormElement, FormElementOptions } from "../FormElement.js"
import GeocoderRequest = google.maps.GeocoderRequest

const $ = jQuery
/**
 *
 */
export class DistanceFormElement extends FormElement {
  declare inputs: {
    units: HTMLInputElement
    geoPoint: HTMLInputElement
    length: HTMLInputElement
    address: HTMLInputElement
    country: HTMLInputElement
  }
  declare value: {
    units: string
    geoPoint: { lat: string | number; lng: string | number }
    length: number
    address: string
    country: string
  }
  distanceControl: string
  distanceUnits: string
  distanceUnitsLabel: string
  fromLabel: string
  userLocationButton: boolean
  addressField: boolean
  addressFieldPlaceholder: string
  languages: { [key: string]: string }[]
  countries: string[]
  country: string
  language: string
  searchByZip: boolean
  zipLength: number
  isGeo: boolean
  defaultLength: number
  spinner: HTMLElement

  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)

    this.name = "distance"
    this.typeLabel = "Search by address"

    this.label = this.label || (options.label === undefined ? "Search radius" : options.label)
    this.distanceControl = options.distanceControl || "select"
    this.distanceUnits = options.distanceUnits || "km"
    this.distanceUnitsLabel = options.distanceUnitsLabel || "km"
    this.fromLabel = options.fromLabel || "from"
    this.placeholder = options.placeholder
    this.userLocationButton = options.userLocationButton || false
    this.type = options.type
    this.addressField = options.addressField || true
    this.addressFieldPlaceholder = options.addressFieldPlaceholder || "Address"
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
    this.countries = window.mapsvg.countries
    this.country = options.country
    this.language = options.language
    this.searchByZip = options.searchByZip
    this.zipLength = parseInt(options.zipLength) || 5
    this.userLocationButton = parseBoolean(options.userLocationButton)
    this.options = options.options || [
      { value: "10", default: true },
      { value: "30", default: false },
      { value: "50", default: false },
      { value: "100", default: false },
    ]

    let defOption: FormElementOptions
    if (!this.value) {
      this.value = {
        units: this.distanceUnits,
        geoPoint: { lat: null, lng: null },
        // @ts-expect-error length
        length: this.options.find((o) => o.default)?.value || this.options[0]?.value,
        address: "",
        country: this.country,
      }
    }
    this.defaultLength = this.value.length
  }

  setDomElements(): void {
    super.setDomElements()

    this.inputs.units = <HTMLInputElement>$(this.domElements.main).find('[name="distanceUnits"]')[0]
    this.inputs.geoPoint = <HTMLInputElement>(
      $(this.domElements.main).find('[name="distanceGeoPoint"]')[0]
    )
    this.inputs.length = <HTMLInputElement>(
      $(this.domElements.main).find('[name="distanceLength"]')[0]
    )
    this.inputs.address = <HTMLInputElement>$(this.domElements.main).find('[name="distance"]')[0]
  }

  getSchema(): { [p: string]: any } {
    const schema = super.getSchema()
    schema.distanceControl = this.distanceControl
    schema.distanceUnits = this.distanceUnits
    schema.distanceUnitsLabel = this.distanceUnitsLabel
    schema.fromLabel = this.fromLabel
    schema.addressField = this.addressField
    schema.addressFieldPlaceholder = this.addressFieldPlaceholder
    schema.userLocationButton = this.userLocationButton
    schema.placeholder = this.placeholder
    schema.language = this.language
    schema.country = this.country
    schema.searchByZip = this.searchByZip
    schema.zipLength = parseInt(this.zipLength + "")
    schema.userLocationButton = parseBoolean(this.userLocationButton)
    if (schema.distanceControl === "none") {
      schema.distanceDefault = schema.options.filter(function (o) {
        return o.default
      })[0].value
    }

    schema.options.forEach(function (option, index) {
      if (schema.options[index].value === "") {
        schema.options.splice(index, 1)
      } else {
        schema.options[index].default = parseBoolean(schema.options[index].default)
        // data.optionsDict[option.value] = option;
      }
    })

    return schema
  }

  getDataForTemplate(): { [key: string]: any } {
    const data = super.getDataForTemplate()

    if (this.formBuilder.admin) {
      data.languages = this.languages
      data.countries = this.countries
    }
    data.language = this.language
    data.country = this.country
    data.searchByZip = this.searchByZip
    data.zipLength = this.zipLength
    data.userLocationButton = parseBoolean(this.userLocationButton)
    return data
  }

  initEditor() {
    super.initEditor()
    this.mayBeAddDistanceRow()
    if ($().mselect2) {
      $(this.domElements.edit).find("select").mselect2()
    }
  }

  setEventHandlers() {
    super.setEventHandlers()

    const _this = this

    $(this.domElements.edit).on(
      "keyup change paste",
      ".mapsvg-edit-distance-row input",
      function () {
        _this.mayBeAddDistanceRow()
      },
    )

    const server = new Server(mapsvgCore.routes.api)

    // TODO check if distance filter is working:
    // Google geocoding
    const locations = new Bloodhound({
      datumTokenizer: Bloodhound.tokenizers.obj.whitespace("formatted_address"),
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      remote: {
        url:
          server.getUrl("geocoding") +
          "?address=" +
          (this.searchByZip === true ? "zip%20" : "") +
          "%QUERY%&language=" +
          this.language +
          (this.country ? "&country=" + this.country : ""),
        wildcard: "%QUERY%",
        transform: function (response) {
          if (response.error_message) {
            console.error(response.error_message)
          }
          return response.results
        },
        rateLimitWait: 600,
      },
    })

    const thContainer = $(this.domElements.main).find(".typeahead")
    this.spinner = $("<span class='spinner-border spinner-border-sm'></span>")[0]

    if (this.searchByZip) {
      // var tH = thContainer.typeahead({minLength: this.zipLength, autoselect: true}, {
      //     name: 'mapsvg-addresses',
      //     source: locations,
      //     display: 'formatted_address',
      // });
      $(this.domElements.main).find(".mapsvg-distance-fields").addClass("search-by-zip")
      // thContainer.on('typeahead:asyncreceive',function(ev,query,dataset){
      //     console.log(ev,query,dataset);
      // });
      thContainer.on("change keyup", (e) => {
        const zip = $(e.target).val().toString()
        if (zip.length === _this.zipLength) {
          $(this.spinner).appendTo($(e.target).closest(".distance-search-wrap"))
          this.formBuilder.setIsLoading(true)
          locations.search(
            $(e.target).val(),
            (data) => this.handleAddressFieldChange(zip, data),
            (data) => this.handleAddressFieldChange(zip, data, true),
          )
        }
      })
    } else {
      // @ts-ignore
      const tH = thContainer
        .typeahead(
          { minLength: 3 },
          {
            name: "mapsvg-addresses",
            display: "formatted_address",
            limit: 5,
            async: true,
            // @ts-ignore
            source: (query, sync, async) => {
              const request = { address: query } as GeocoderRequest
              if (this.country) {
                request.componentRestrictions = { country: this.country }
              }
              geocode(request, async)
            },
          },
        )
        .on("typeahead:asyncrequest", (e) => {
          $(this.spinner).appendTo($(e.target).closest(".twitter-typeahead"))
          this.formBuilder.setIsLoading(true)
        })
        .on("typeahead:asynccancel typeahead:asyncreceive", (e) => {
          $(this.spinner).remove()
          this.formBuilder.setIsLoading(false)
        })
      $(this.domElements.main).find(".mapsvg-distance-fields").removeClass("search-by-zip")
    }

    if (_this.userLocationButton) {
      const userLocationButton = $(this.domElements.main).find(".user-location-button")
      userLocationButton.on("click", () => {
        _this.formBuilder.mapsvg.showUserLocation((location: Location) => {
          locations.search(
            location.geoPoint.lat + "," + location.geoPoint.lng,
            (data) => this.setAddressByUserLocation(data, location),
            (data) => this.setAddressByUserLocation(data, location),
          )
          this.setValue({ geoPoint: location.geoPoint })
          this.triggerChanged()
        })
      })
    }

    thContainer.on("change keyup", (e) => {
      const input = <HTMLInputElement>e.target
      if (input.value === "" && this.getValue() !== null) {
        this.setValue(null)
        this.triggerChanged()
      }
    })
    thContainer.on("typeahead:select", (ev, item) => {
      this.setValue({
        address: item.formatted_address,
        geoPoint: item.geometry.location.toJSON(),
      })
      this.triggerChanged()
      thContainer.blur()
    })

    $(this.inputs.geoPoint).on("change", (e) => {
      const geoPoint = e.target.value.split(",").map((value) => parseFloat(value))
      this.setValue({ geoPoint: { lat: geoPoint[0], lng: geoPoint[1] } }, false)
      this.triggerChanged()
    })
    $(this.inputs.length).on("change", (e) => {
      this.setLength(parseInt(e.target.value), false)
      this.triggerChanged()
    })
  }
  setAddressByUserLocation(data, location): void {
    if (data && data[0]) {
      this.setAddress(data[0].formatted_address)
    } else {
      this.setAddress(location.geoPoint.lat + "," + location.geoPoint.lng)
    }
  }
  handleAddressFieldChange(zip: string, data, removeSpinner = false): void {
    if (removeSpinner) {
      $(this.spinner).remove()
      this.formBuilder.setIsLoading(false)
    }
    if (data && data[0]) {
      this.setValue({ address: zip, geoPoint: data[0].geometry.location }, false)
      this.triggerChanged()
    }
  }

  addSelect2(): void {
    if ($().mselect2) {
      $(this.domElements.main)
        .find("select")
        .mselect2()
        .on("select2:focus", function () {
          $(this).mselect2("open")
        })
    }
  }

  mayBeAddDistanceRow(): void {
    const _this = this
    const editDistanceRow = $($("#mapsvg-edit-distance-row").html())
    // if there's something in the last status edit field, add +1 status row
    const z = $(_this.domElements.edit).find(".mapsvg-edit-distance-row:last-child input")
    if (z && z.last() && z.last().val() && z.last().val().toString().trim().length) {
      const newRow = editDistanceRow.clone()
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

  setValue(value: any, updateInput = true): void {
    if (value === null) {
      this.setGeoPoint(null)
      this.setAddress("")
    } else {
      for (const key in value) {
        if (typeof this.value[key] !== "undefined") {
          const method = "set" + ucfirst(key)
          if (typeof this[method] === "function") {
            this[method](value[key], updateInput)
          }
        }
      }
    }
  }

  setGeoPoint(geoPoint: { lat: number; lng: number }, updateInput = true): void {
    this.value.geoPoint = geoPoint === null ? { lat: null, lng: null } : geoPoint
    if (updateInput) {
      this.setInputGeoPointValue(geoPoint)
    }
  }

  setInputGeoPointValue(geoPoint: { lat: number; lng: number }): void {
    this.inputs.geoPoint.value = geoPoint ? geoPoint.lat + "," + geoPoint.lng : ""
  }

  setLength(length: number | string, updateInput = true): void {
    this.value.length = parseInt(length.toString())
    this.defaultLength = this.value.length
    if (updateInput) {
      this.setInputLengthValue(this.value.length)
    }
  }

  setInputLengthValue(length: number): void {
    this.inputs.length.value = length.toString()
  }

  setAddress(address: string, updateInput = true): void {
    this.value.address = address !== null ? address : ""
    if (updateInput) {
      this.setInputAddressValue(this.value.address)
    }
  }

  setInputAddressValue(address: string): void {
    this.inputs.address.value = address
  }

  getValue(): any {
    return this.value.geoPoint.lat === null ? null : this.value
  }
}
