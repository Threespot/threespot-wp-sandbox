import { ArrayIndexed } from "../../Core/ArrayIndexed"
import { SchemaField, SchemaFieldProps } from "../../Infrastructure/Server/SchemaField"
import { MapSVGMap } from "../../Map/Map"
import { FormBuilder } from "../FormBuilder"
import { FormElement } from "./FormElement"
import * as FormElementTypes from "./index.js"

const $ = jQuery

/**
 *
 */
export class FormElementFactory {
  mapsvg: MapSVGMap
  formBuilder: FormBuilder
  namespace: string
  editMode: boolean
  filtersMode: boolean
  mediaUploader: any
  showNames: boolean
  types: Record<string, typeof FormElement>

  constructor(options: {
    mapsvg: MapSVGMap
    formBuilder: FormBuilder
    editMode: boolean
    filtersMode: boolean
    namespace: string
    mediaUploader: any
    showNames?: boolean
  }) {
    this.mapsvg = options.mapsvg
    this.editMode = options.editMode
    this.filtersMode = options.filtersMode
    this.namespace = options.namespace
    this.mediaUploader = options.mediaUploader
    this.formBuilder = options.formBuilder
    this.showNames = options.showNames !== false
  }

  create(schemaOptions: SchemaFieldProps | SchemaField): FormElementTypes.FormElementInterface {
    this.types = {
      checkbox: FormElementTypes.CheckboxFormElement,
      checkboxes: FormElementTypes.CheckboxesFormElement,
      date: FormElementTypes.DateFormElement,
      // START distance_search
      distance: FormElementTypes.DistanceFormElement,
      // REPLACE
      // distance: FormElementTypes.EmptyFormElement,
      // END
      empty: FormElementTypes.EmptyFormElement,
      id: FormElementTypes.IdFormElement,
      image: FormElementTypes.ImagesFormElement,
      location: FormElementTypes.LocationFormElement,
      modal: FormElementTypes.ModalFormElement,
      post: FormElementTypes.PostFormElement,
      radio: FormElementTypes.RadioFormElement,
      region: FormElementTypes.RegionsFormElement,
      save: FormElementTypes.SaveFormElement,
      
      select: FormElementTypes.SelectFormElement,
      status: FormElementTypes.StatusFormElement,
      text: FormElementTypes.TextFormElement,
      textarea: FormElementTypes.TextareaFormElement,
      title: FormElementTypes.TitleFormElement,
      colorpicker: FormElementTypes.ColorPickerFormElement,
    }

    const formElement = new this.types[schemaOptions.type](
      schemaOptions instanceof SchemaField ? schemaOptions : new SchemaField(schemaOptions),
      this.formBuilder,
      this.getExtraParams(),
    )
    formElement.init()
    return formElement
  }

  getExtraParams() {
    const databaseFields = []
    const databaseFieldsFilterable = []
    const databaseFieldsFilterableShort = []
    const regionFields = []
    const regionFieldsFilterable = []
    const regions = new ArrayIndexed("id")
    let mapIsGeo = false

    if (this.mapsvg) {
      mapIsGeo = this.mapsvg.isGeo()
      const schemaObjects = this.mapsvg.objectsRepository.getSchema()
      if (schemaObjects) {
        schemaObjects.getFields().forEach(function (obj) {
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
          if (obj.type == "region" || obj.type == "select" || obj.type == "radio") {
            databaseFieldsFilterable.push("Object." + obj.name)
            databaseFieldsFilterableShort.push(obj.name)
          }
        })
      }

      const schemaRegions = this.mapsvg.regionsRepository.getSchema()

      if (schemaRegions) {
        schemaRegions.getFields().forEach(function (obj) {
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
              regionFields.push("Region.post.post_title")
            } else {
              regionFields.push("Region." + obj.name)
            }
          }
          if (obj.type == "status" || obj.type == "select" || obj.type == "radio") {
            regionFieldsFilterable.push("Region." + obj.name)
          }
        })
      }

      this.mapsvg.regions.forEach((region) => {
        regions.push({ id: region.id, title: region.title })
      })
    }

    return {
      databaseFields: databaseFields,
      databaseFieldsFilterable: databaseFieldsFilterable,
      databaseFieldsFilterableShort: databaseFieldsFilterableShort,
      regionFields: regionFields,
      regionFieldsFilterable: regionFieldsFilterable,
      regions: regions,
      mapIsGeo: mapIsGeo,
      mediaUploader: this.mediaUploader,
      filtersMode: this.filtersMode,
      showNames: this.showNames,
    }
  }
}
