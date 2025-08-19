import { deepMerge, parseBoolean } from "@/Core/Utils"
import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { FormBuilder } from "../../FormBuilder.js"
import { FormElement } from "../FormElement.js"

const $ = jQuery

// TODO extend RegionsFormElement from the (not yet created) MultiselectFormElement

/**
 *
 */
export class RegionsFormElement extends FormElement {
  declare inputs: { select: HTMLSelectElement }
  regionsTableName: string

  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)
    this.searchable = parseBoolean(options.searchable)
    this.options = this.formBuilder.getRegionsList()
    this.label = options.label === undefined ? "Regions" : options.label
    this.name = "regions"
    this.db_type = "text"
    this.typeLabel = "Regions"
    if (typeof this.value === "undefined") {
      this.value = []
    }
    // TODO inject the table name as a dependency
    this.regionsTableName = this.formBuilder.mapsvg.regionsRepository.getSchema().name
  }

  setDomElements() {
    super.setDomElements()
    this.inputs.select = $(this.domElements.main).find("select")[0]
  }

  getData(): { name: string; value: any } {
    return { name: "regions", value: this.value }
  }

  getSchema(): { [p: string]: any } {
    const schema = super.getSchema()
    if (schema.multiselect) schema.db_type = "text"
    const opts = deepMerge({}, { options: this.options })
    schema.options = opts.options
    schema.optionsDict = {}
    schema.options.forEach(function (option) {
      schema.optionsDict[option.id] = option.title || option.id
    })

    return schema
  }

  getDataForTemplate(): { [p: string]: any } {
    const data = super.getDataForTemplate()
    data.regionsTableName = this.regionsTableName
    data.regionsFromCurrentTable = this.getRegionsForCurrentTable()
    return data
  }

  getRegionsForCurrentTable(): Array<{ id: string; title: string; tableName: string }> {
    return this.value
      ? this.value.filter((region) => region.tableName === this.regionsTableName)
      : []
  }

  setEventHandlers(): void {
    super.setEventHandlers()
    $(this.inputs.select).on("change", (e) => {
      const selectedOptions = Array.from(this.inputs.select.selectedOptions)
      const selectedValues = selectedOptions.map((option) => option.value)
      this.setValue(selectedValues, false)
      this.triggerChanged()
    })
  }

  destroy(): void {
    if ($().mselect2) {
      const sel = $(this.domElements.main).find(".mapsvg-select2")
      if (sel.length) {
        //@ts-ignore
        sel.mselect2("destroy")
      }
    }
    super.destroy()
  }

  setValue(regionIds: string[], updateInput = true) {
    const regionsFromOtherTables = this.value.filter(
      (region) => region.tableName !== this.regionsTableName,
    )
    const regions = []
    regionIds.forEach((regionId: string) => {
      const region = this.formBuilder.mapsvg.getRegion(regionId)
      regions.push({ id: region.id, title: region.title, tableName: this.regionsTableName })
    })
    this.value = regions.concat(regionsFromOtherTables)
    if (updateInput) {
      this.setInputValue(regionIds)
    }
  }

  // TODO add setInpuValue
}
