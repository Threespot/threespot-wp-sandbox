import { ObjectWithEvents } from "@/Core/Controller"
import { mapsvgCore } from "@/Core/Mapsvg"
import { ucfirst } from "@/Core/Utils"
import { ArrayIndexed } from "../Core/ArrayIndexed"
import { EventWithData, Events } from "../Core/Events"
import { ResizeSensor } from "../Core/ResizeSensor"
import { Schema } from "../Infrastructure/Server/Schema"
import { SchemaField } from "../Infrastructure/Server/SchemaField"
import { GeoPoint, Location, SVGPoint } from "../Location/Location"
import { MapSVGMap } from "../Map/Map"
import { Marker } from "../Marker/Marker"
import { Region } from "../Region/Region"
import { Form } from "./Form"
import { FormElement } from "./FormElements/FormElement"
import { FormElementFactory } from "./FormElements/FormElementFactory"
import * as FormElementTypes from "./FormElements/index"
import { FormElementInterface } from "./FormElements/index"
const $ = jQuery

export enum FormBuilderEvents {
  AFTER_LOAD = "afterLoad",
  CLEAR = "clear",
}

/**
 * Generates a {@link Form} by provided {@link Schema}
 */
export class FormBuilder {
  parent: ObjectWithEvents
  container: HTMLElement
  namespace: string
  mediaUploader: any // WP Media uploader class
  schema: Schema
  events: Events
  formElementFactory: FormElementFactory

  structureChanged: boolean

  isLoading: boolean

  editMode: boolean
  filtersMode: boolean
  filtersHide: boolean
  showFiltersButton: FormElementTypes.FormElementInterface
  modal: boolean
  admin: any // MapSVG Admin class?
  mapsvg: MapSVGMap
  data: { [key: string]: any }
  clearButton: boolean
  clearButtonText: string
  searchButton: boolean
  searchButtonText: string
  showButtonText: string
  template: string
  closeOnSave: boolean
  newRecord: boolean
  types: ({ name: string; premium: boolean } | string)[]
  sortable: any
  saved: boolean
  scrollable: boolean
  showNames: boolean

  templates: { [key: string]: () => void }
  elements: { [key: string]: { [key: string]: HTMLElement } }
  form: HTMLFormElement
  formEditor: HTMLFormElement
  view: HTMLElement

  formElements: ArrayIndexed<FormElementTypes.FormElementInterface>
  currentlyEditing: FormElementTypes.FormElementInterface
  markerBackup: { id: string; src: string; svgPoint: SVGPoint; geoPoint: GeoPoint }
  location: Location
  scrollApi: any // jScrollPane instance

  backupData?: {
    location: Location
    marker: Marker
  }
  editingMarker?: Marker
  mediaUploaderisOpenedFor: FormElementInterface

  constructor(options: { [key: string]: any }) {
    // schema, editMode, mapsvg, mediaUploader, data, admin, namespace
    const _this = this

    // options
    this.container = options.container
    this.namespace = options.namespace
    this.mediaUploader = options.mediaUploader
    this.schema = options.schema || []
    this.editMode = options.editMode == undefined ? false : options.editMode
    this.filtersMode = options.filtersMode == undefined ? false : options.filtersMode
    this.filtersHide = options.filtersHide == undefined ? false : options.filtersHide
    this.modal = options.modal == undefined ? false : options.modal
    this.admin = options.admin
    this.mapsvg = <MapSVGMap>options.mapsvg
    this.data = options.data || {}
    this.clearButton = options.clearButton || false
    this.clearButtonText = options.clearButtonText || ""
    this.searchButton = options.searchButton || false
    this.searchButtonText = options.searchButtonText || ""
    this.showButtonText = options.showButtonText || ""
    this.scrollable =
      typeof options.scrollable !== "undefined"
        ? options.scrollable
        : !_this.editMode && !_this.filtersMode
    this.showNames = typeof options.showNames !== "undefined" ? options.showNames : true

    this.events = new Events({
      context: this,
      contextName: "form",
      map: this.mapsvg,
      parent: options.parent && options.parent.events ? options.parent.events : undefined,
    })

    this.formElementFactory = new FormElementFactory({
      mapsvg: this.mapsvg,
      formBuilder: this,
      mediaUploader: this.mediaUploader,
      editMode: this.editMode,
      filtersMode: this.filtersMode,
      namespace: this.namespace,
      showNames: this.showNames,
    })

    if (options.events && Object.keys(options.events).length > 0) {
      for (const eventName in options.events) {
        this.events.on(eventName, options.events[eventName])
      }
    }
    this.template = "form-builder"
    this.closeOnSave = options.closeOnSave === true
    this.newRecord = options.newRecord === true
    this.types = options.types || [
      "text",
      "textarea",
      "checkbox",
      "radio",
      "select",
      "image",
      "region",
      "location",
      "post",
      "date",
    ]

    this.templates = {}
    this.elements = {}
    this.view = $("<div />").addClass("mapsvg-form-builder")[0]
    if (this.editMode) $(this.view).addClass("full-flex")
    if (!this.showNames) {
      $(this.view).addClass("hide-names")
    }

    this.formElements = new ArrayIndexed("name")
  }

  private loadTemplates(callback: () => void): void {
    $.get(
      mapsvgCore.routes.root + "dist/" + this.template + ".html?v=" + mapsvgCore.version,
      (data) => {
        $(data).appendTo("body")
        mapsvgCore.templatesLoaded[this.template] = true
        Handlebars.registerPartial("dataMarkerPartial", $("#mapsvg-data-tmpl-marker").html())
        if (this.editMode) {
          Handlebars.registerPartial(
            "markerByFieldPartial",
            $("#mapsvg-markers-by-field-tmpl-partial").html(),
          )
        }
        callback()
      },
    )
  }

  init() {
    if (!mapsvgCore.templatesLoaded[this.template]) {
      this.loadTemplates(() => this.init())
      return
    }

    const _this = this

    this.form = document.createElement("form")
    this.form.className = "mapsvg-data-form-view"
    if (!this.filtersMode) {
      this.form.classList.add("form-horizontal")
    }

    if (this.editMode) {
      const template = document.getElementById("mapsvg-form-editor-tmpl-ui").innerHTML
      const templateCompiled = Handlebars.compile(template)

      const typesData = this.types.map((type) => {
        const name = typeof type === "string" ? type : type.name
        return {
          name,
          label: name === "distance" ? "Address search" : ucfirst(name),
          premium: typeof type === "object" ? type.premium : false,
        }
      })
      this.view.innerHTML = templateCompiled({ types: typesData })
      this.view.classList.add("edit")
      this.form.classList.add("mapsvg-data-flex-full")
      this.form.classList.add("mapsvg-data-container")
      $(this.view).find(".mapsvg-data-preview").prepend(this.form)
      this.formEditor = <HTMLFormElement>$(this.view).find("#mapsvg-data-form-edit")[0]
    } else {
      this.view.appendChild(this.form)
    }

    _this.elements = {
      buttons: {
        text: $(_this.view).find("#mapsvg-data-btn-text")[0],
        textarea: $(_this.view).find("#mapsvg-data-btn-textarea")[0],
        checkbox: $(_this.view).find("#mapsvg-data-btn-checkbox")[0],
        radio: $(_this.view).find("#mapsvg-data-btn-radio")[0],
        select: $(_this.view).find("#mapsvg-data-btn-select")[0],
        image: $(_this.view).find("#mapsvg-data-btn-image")[0],
        region: $(_this.view).find("#mapsvg-data-btn-region")[0],
        marker: $(_this.view).find("#mapsvg-data-btn-marker")[0],
        saveSchema: $(_this.view).find("#mapsvg-data-btn-save-schema")[0],
      },
      containers: {
        buttons_add: $(_this.view).find("#mapsvg-data-buttons-add")[0],
      },
    }

    _this.redraw()
  }

  getForm(): HTMLFormElement {
    return this.form
  }
  getFormEditor(): HTMLFormElement {
    return this.formEditor
  }

  viewDidLoad() {}

  setEventHandlers() {
    const _this = this

    $(this.getForm()).on("submit", (e) => {
      e.preventDefault()
    })

    if (this.filtersMode && this.clearButton) {
      $(this.elements.buttons.clearButton).on("click", (e) => {
        e.preventDefault()
        this.clearAllFields()
      })
    }

    if (this.editMode) {
      $(this.view).on("click", "#mapsvg-data-buttons-add button[data-create]", function (e) {
        e.preventDefault()
        const type = $(this).data("create")
        const formElement = _this.formElementFactory.create({ type: type })
        _this.addField(formElement)
      })
      $(this.view).on("click", "#mapsvg-data-btn-save-schema", function (e) {
        e.preventDefault()
        const fields = _this.getSchema()

        const counts = {}
        _this.formElements.forEach(function (elem) {
          counts[elem.name] = (counts[elem.name] || 0) + 1
        })

        $(_this.getForm()).find(".form-group").removeClass("has-error")
        const errors = []

        const reservedFields = [
          "lat",
          "lon",
          "lng",
          "location",
          "location_lat",
          "location_lon",
          "location_lng",
          "location_address",
          "location_img",
          "marker",
          "marker_id",
          "regions",
          "region_id",
          "post",
          "post_title",
          "post_url",
          "keywords",
          "status",
        ]
        const reservedFieldsToTypes = {
          regions: "region",
          status: "status",
          post: "post",
          marker: "marker",
          location: "location",
        }

        let errUnique, errEmpty

        _this.formElements.forEach(function (formElement: FormElementTypes.FormElementInterface) {
          let err = false

          // If that's not Form Builder for Filters (when there is no "name" parameter)
          // we should check if names are non-empty and unique
          if (!_this.filtersMode) {
            if (counts[formElement.name] > 1) {
              if (!errUnique) {
                errUnique = "Field names should be unique"
                errors.push(errUnique)
                err = true
              }
            } else if (formElement.name.length === 0) {
              if (!errEmpty) {
                errEmpty = "Field name can't be empty"
                errors.push(errEmpty)
                err = true
              }
            } else if (reservedFields.indexOf(formElement.name) != -1) {
              // if reserved field name is for proper type of object then it's OK
              if (
                !reservedFieldsToTypes[formElement.name] ||
                (reservedFieldsToTypes[formElement.name] &&
                  reservedFieldsToTypes[formElement.name] != formElement.type)
              ) {
                const msg =
                  'Field name "' + formElement.name + '" is reserved, please set another name'
                errors.push(msg)
                err = true
              }
            }
          }

          if (formElement.options && formElement.type != "region" && formElement.type != "marker") {
            const vals = formElement.options.map(function (obj: { label: string; value: string }) {
              return obj.value
            })
            const uniq = [...Array.from(new Set(vals).values())]
            if (vals.length != uniq.length) {
              errors.push('Check "Options" list - values should not repeat')
              err = true
            }
          }

          err && $(formElement.domElements.main).addClass("has-error")
        })

        if (errors.length === 0) {
          _this.events.trigger("saveSchema", { formBuilder: _this, fields })
        } else {
          jQuery.growl.error({ title: "Errors", message: errors.join("<br />") })
        }
      })
      setTimeout(function () {
        const el = _this.getForm()
        // @ts-ignore
        _this.sortable = new Sortable(el, {
          animation: 150,
          onStart: function () {
            $(_this.getForm()).addClass("sorting")
          },
          onEnd: function () {
            setTimeout(function () {
              $(_this.getForm()).removeClass("sorting")
              _this.formElements.clear()
              $(el)
                .find(".form-group")
                .each(function (index, elem) {
                  _this.formElements.push($(elem).data("formElement"))
                })
            }, 500)
          },
        })
      }, 1000)
    } else {
      // Save
      // $(_this.view).on('click','button.btn-save',function(e){
      //     e.preventDefault();
      //     _this.save();
      // });
      // Close
      // jQuery(_this.view).on('click','button.btn-close', function(){
      //     alert(1);
      // });
      // $(_this.view).on('click','button.btn-close',function(e){
      //     e.preventDefault();
      //     _this.close();
      // });
    }

    new ResizeSensor(this.view, function () {
      _this.scrollApi && _this.scrollApi.reinitialise()
    })
  }

  private clearAllFields() {
    this.formElements.forEach((f) => f.setValue(null))
    this.events.trigger(FormBuilderEvents.CLEAR, { formBuilder: this })
  }

  setFormElementEventHandlers(formElement: FormElementTypes.FormElementInterface) {
    if (this.editMode) {
      formElement.events.on("click", (event: EventWithData<{ formElement: FormElement }>) => {
        const { formElement } = event.data
        this.edit(formElement)
      })
      formElement.events.on("delete", (event: EventWithData<{ formElement: FormElement }>) => {
        const { formElement } = event.data
        this.deleteField(formElement)
      })
    }
  }

  save() {
    const _this = this

    if (_this.markerBackup) {
      const marker = _this.mapsvg.getEditingMarker()
      marker.events.off("change")
      _this.markerBackup = marker.getOptions()
      _this.mapsvg.unsetEditingMarker()
    }

    const data = _this.getData()
    _this.saved = true

    this.events.trigger("save", { formBuilder: this, data })
  }

  getFormElementByType(type: string): FormElementTypes.FormElementInterface {
    return this.formElements.find((el) => el.type === type)
  }
  getFormElementByName(name: string): FormElementTypes.FormElementInterface {
    return this.formElements.find((el) => el.name === name)
  }

  getData(): { [key: string]: any } {
    const data = {}
    this.formElements.forEach((formElement) => {
      if (formElement.type !== "save" && formElement.type !== "empty") {
        const _formElementData = formElement.getData()
        data[_formElementData.name] = _formElementData.value
      }
    })
    return data
  }

  reset(): void {
    this.formElements.forEach((formElement) => {
      formElement.setValue(null)
    })
  }

  update(data: { [key: string]: any }): void {
    this.schema.getFields().forEach((field) => {
      const formElement = <FormElement>this.formElements.get(field.name)
      if (formElement) {
        if (typeof data[field.name] !== "undefined") {
          if (formElement.getValue() !== data[field.name]) {
            formElement.setValue(data[field.name])
          }
        } else {
          formElement.setValue(null)
        }
      }
    })
  }

  redraw(): void {
    const _this = this

    delete _this.markerBackup

    $(this.container).empty()
    $(this.getForm()).empty()
    this.formElements.clear()

    this.schema &&
      this.schema.fields.length > 0 &&
      this.schema.fields.forEach((field: SchemaField) => {
        if (this.filtersMode) {
          if (field.type == "distance") {
            field.value = this.data.distance
              ? this.data.distance
              : field.value !== undefined
                ? field.value
                : null
          } else if (field.type == "search") {
            field.value = this.data.search
          } else {
            field.value = this.data[field.parameterNameShort]
          }
        } else {
          field.value = this.data
            ? this.data[field.name]
            : field.value !== undefined
              ? field.value
              : null
        }

        if (field.type == "location" && !this.editMode) {
          // add Marker Object into formElement
          if (field.value && field.value.marker && field.value.marker.id) {
            //this.markerBackup = fieldSettings.value.marker.getOptions();
            //this.mapsvg.setEditingMarker(fieldSettings.value.marker);
          }
          this.admin && this.admin.setMode && this.admin.setMode("editMarkers")
          this.admin && this.admin.enableMarkersMode(true)
        } else if (field.type == "post") {
          field.post = this.data["post"]
        } else if (field.type === "region") {
          field.options = new ArrayIndexed("id", this.getRegionsList())
        }

        const formElement = this.formElementFactory.create(field)
        if (this.filtersMode) {
          if (
            !this.filtersHide ||
            (this.filtersHide && this.modal && field.type !== "search") ||
            (!this.modal && field.type === "search")
          ) {
            this.addField(formElement)
          }
        } else {
          this.addField(formElement)
        }
      })

    if (!_this.editMode) {
      if (this.schema.fields.length === 0 && !this.filtersMode) {
        const formElement = this.formElementFactory.create({ type: "empty" })
        _this.addField(formElement)
      } else {
        if (_this.admin && !_this.admin.isMetabox) {
          const formElement = this.formElementFactory.create({ type: "save" })
          formElement.events.on("click.btn.save", () => {
            this.save()
          })
          formElement.events.on("click.btn.close", () => {
            this.close()
          })
          _this.addField(formElement)
        }
      }
    }

    // If part of filters is hidden in a modal, and what is showing now is NOT a modal,
    // then add a "Show filters" button that opens a modal with remaining filers.
    if (_this.filtersMode && _this.filtersHide && !_this.modal) {
      const formElement = this.formElementFactory.create({
        type: "modal",
        showButtonText: _this.showButtonText,
      })
      this.showFiltersButton = _this.addField(formElement)
    }

    if (this.scrollable) {
      const nano = $('<div class="nano"></div>')
      const nanoContent = $('<div class="nano-content"></div>')
      nano.append(nanoContent)
      // nanoContent.html(this.view);
      // $(_this.container).html(nano.html());
      nanoContent.append(this.view)
      $(_this.container).append(nano)

      // @ts-ignore
      nano.jScrollPane({ contentWidth: "0px", mouseWheelSpeed: 30 })
      _this.scrollApi = nano.data("jsp")
    } else {
      // $(_this.container).html(this.view);
      $(_this.container).append(this.view)
    }

    // If FormBuilder is used to create filters and "Add clear button" option is "on"
    // then add the "Clear all" button
    if (_this.filtersMode && _this.clearButton) {
      _this.elements.buttons.clearButton = $(
        '<div class="form-group mapsvg-filters-reset-container">' +
          '<button type="button" class="btn btn-outline-secondary mapsvg-filters-reset">' +
          _this.clearButtonText +
          "</button></div>",
      )[0]
      $(this.getForm()).append(_this.elements.buttons.clearButton)
    }
    if (_this.filtersMode && _this.searchButton) {
      _this.elements.buttons.searchButton = $(
        '<div class="form-group mapsvg-filters-reset-container" id="mapsvg-search-container"><button class="btn btn-outline-secondary mapsvg-filters-reset">' +
          _this.searchButtonText +
          "</button></div>",
      )[0]
      $(this.getForm()).append(_this.elements.buttons.searchButton)
    }

    if (!this.editMode && !_this.filtersMode)
      $(this.view).find("input:visible,textarea:visible").not(".tt-hint").first().focus()

    const cm = $(this.container).find(".CodeMirror")

    cm.each(function (index, el) {
      // @ts-ignore
      if (el) el.CodeMirror.refresh()
    })
    _this.setEventHandlers()

    const popoverTriggerList = [].slice.call(
      document.querySelectorAll('[data-bs-toggle="popover"][data-premium="true"]'),
    )
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
      return new window.bootstrap.Popover(popoverTriggerEl, {
        content:
          '<i class="bi bi-star-fill"></i> Premium feature <a class="btn btn-xs btn-outline-primary" target="_blank" href="https://mapsvg.com/pricing">Upgrade</a>',
        html: true,
        trigger: "focus",
        placement: "top",
      })
    })

    this.events.trigger("init", { formBuilder: this, data: this.getData() })
    this.events.trigger(FormBuilderEvents.AFTER_LOAD, { formBuilder: this, data: this.getData() })
  }

  updateExtraParamsInFormElements() {
    this.formElements.forEach((formElement) => {
      formElement.setExternal(this.formElementFactory.getExtraParams())
      if (formElement.type === "location") {
        formElement.redraw()
      }
    })
  }

  deleteField(formElement: FormElement) {
    this.formElements.delete(formElement.name)
  }

  getExtraParams() {
    const databaseFields = []
    this.mapsvg.objectsRepository
      .getSchema()
      .getFields()
      .forEach(function (obj) {
        if (
          obj.type == "text" ||
          obj.type == "region" ||
          obj.type == "textarea" ||
          obj.type == "post" ||
          obj.type == "select" ||
          obj.type == "radio" ||
          obj.type == "checkbox"
        ) {
          if (obj.type == "post") {
            databaseFields.push("Object.post.post_title")
          } else {
            databaseFields.push("Object." + obj.name)
          }
        }
      })
    let databaseFieldsFilterableShort = []
    databaseFieldsFilterableShort = this.mapsvg.objectsRepository
      .getSchema()
      .getFields()
      .filter(function (obj) {
        return obj.type == "select" || obj.type == "radio" || obj.type == "region"
      })
      .map(function (obj) {
        return obj.name
      })
    let markerFieldsShort = []
    markerFieldsShort = databaseFieldsFilterableShort.filter(
      (o) => o.type === "select" || o.type === "radio",
    )
    const regionFields = this.mapsvg.regionsRepository
      .getSchema()
      .getFields()
      .map(function (obj) {
        if (
          obj.type == "status" ||
          obj.type == "text" ||
          obj.type == "textarea" ||
          obj.type == "post" ||
          obj.type == "select" ||
          obj.type == "radio" ||
          obj.type == "checkbox"
        ) {
          if (obj.type == "post") {
            return "Region.post.post_title"
          } else {
            return "Region." + obj.name
          }
        }
      })
    return {
      databaseFields: databaseFields,
      databaseFieldsFilterableShort: databaseFieldsFilterableShort,
      regionFields: regionFields,
      markerFieldsShort: markerFieldsShort,
    }
  }

  addField(formElement: FormElementTypes.FormElementInterface) {
    const _this = this

    // Check if field can be added - some fields can be added only once:
    if (
      ["region", "marker", "post", "status", "distance", "location", "search"].indexOf(
        formElement.type,
      ) != -1
    ) {
      let repeat = false
      _this.formElements.forEach(function (control) {
        if (control.type == formElement.type) repeat = true
      })
      if (repeat) {
        jQuery.growl.error({
          title: "Error",
          message: 'You can add only 1 "' + ucfirst(formElement.type) + '" field',
        })
        return
      }
    }

    _this.formElements.push(formElement)

    _this.getForm().append(formElement.domElements.main)

    this.setFormElementEventHandlers(formElement)

    if (this.editMode) {
      if (formElement.protected) {
        formElement.hide()
      } else {
        this.edit(formElement)
      }
    }
    return formElement
  }

  edit(formElement: FormElementTypes.FormElementInterface) {
    const _this = this

    // destroy previous editor
    _this.currentlyEditing && _this.currentlyEditing.destroyEditor()

    // create new  editor
    this.getFormEditor().appendChild(formElement.getEditor())
    // setTimeout(function(){
    formElement.initEditor()
    _this.currentlyEditing = formElement
    $(_this.getForm()).find(".form-group.active").removeClass("active")
    $(formElement.domElements.main).addClass("active")
    // }, 500);
  }

  get() {
    // return this.formElements.map(function(c){
    //     return c.get();
    // });
  }

  getSchema() {
    return this.formElements.map(function (formElement) {
      return formElement.getSchema()
    })
  }

  close() {
    // $('body').off('keydown.mapsvg');

    this.formElements.forEach((formElement) => formElement.destroy())
    this.mediaUploader && this.mediaUploader.off("select")
    if (this.mapsvg) {
      $(window).off("keydown.form.mapsvg-" + this.mapsvg.id)
    }
    this.events.trigger("close", { formBuilder: this })
  }

  destroy() {
    $(this.view).empty().remove()
    if (this.mapsvg) {
      $(window).off("keydown.form.mapsvg-" + this.mapsvg.id)
    }

    this.sortable = null
  }

  toJSON(addEmpty) {
    const obj = {}

    function add(obj, name, value) {
      if (!addEmpty && !value) return false
      if (name.length == 1) {
        obj[name[0]] = value
      } else {
        if (obj[name[0]] == null) {
          if (name[1] === "") {
            obj[name[0]] = []
          } else {
            obj[name[0]] = {}
          }
        }

        if (obj[name[0]].length !== undefined) {
          obj[name[0]].push(value)
        } else {
          add(obj[name[0]], name.slice(1), value)
        }
      }
    }

    $(this.elements.containers.formView)
      .find("input, textarea, select")
      .each(function () {
        if (
          !$(this).data("skip") &&
          !$(this).prop("disabled") &&
          $(this).attr("name") &&
          !(
            !addEmpty &&
            $(this).attr("type") == "checkbox" &&
            $(this).attr("checked") == undefined
          ) &&
          !($(this).attr("type") == "radio" && $(this).attr("checked") == undefined)
        ) {
          let value
          if ($(this).attr("type") == "checkbox") {
            value = $(this).prop("checked")
          } else {
            value = $(this).val()
          }
          add(obj, $(this).attr("name").replace(/]/g, "").split("["), value)
        }
      })

    return obj
  }

  getRegionsList(): { id: string; title: string }[] {
    return this.mapsvg.regions.map(function (r) {
      return { id: r.id, title: r.title }
    })
  }
  getRegionsAsArray(): Region[] {
    return this.mapsvg.regions
  }

  private setRegions(location: Location) {
    const regionsFormElement = <FormElementInterface>this.formElements.get("regions")

    if (this.mapsvg.options.source.indexOf("/geo-calibrated/usa.svg") !== -1) {
      // If this is the map of USA, choose the corresponding state by region ID in the "regions" field
      regionsFormElement.setValue(["US-" + location.address.state_short])
    } else if (this.mapsvg.options.source.indexOf("/geo-calibrated/world.svg") !== -1) {
      // If this is the world.svg map, choose the corresponding country by country ID in the "regions" field
      if (location.address.country_short) {
        regionsFormElement.setValue([location.address.country_short])
      }
    } else {
      if (location.address.administrative_area_level_1) {
        this.mapsvg.regions.forEach((_region: Region) => {
          if (
            _region.title === location.address.administrative_area_level_1 ||
            _region.title === location.address.administrative_area_level_2 ||
            _region.id ===
              location.address.country_short +
                "-" +
                location.address.administrative_area_level_1_short
          ) {
            regionsFormElement.setValue([_region.id])
          }
        })
      }
    }
  }

  setIsLoading(value: boolean): void {
    this.isLoading = value
    if (this.searchButton) {
      if (this.isLoading) {
        $(this.elements.buttons.searchButton).find("button").attr("disabled", "disabled")
      } else {
        $(this.elements.buttons.searchButton).find("button").removeAttr("disabled")
      }
    }
  }
}
