import { deepMerge, parseBoolean } from "@/Core/Utils"
import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { FormBuilder } from "../../FormBuilder.js"
import { FormElement } from "../FormElement.js"

const $ = jQuery

// TODO extract "multiselect" class and make it inherited from SelectFormElement

/**
 *
 */
export class SelectFormElement extends FormElement {
  multiselect: boolean
  optionsGrouped: any
  declare inputs: { select: HTMLSelectElement }

  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)
    this.searchable = parseBoolean(options.searchable)
    this.multiselect = parseBoolean(options.multiselect)
    this.optionsGrouped = options.optionsGrouped
    this.db_type = this.multiselect ? "text" : "varchar(255)"
    this.typeLabel = "Select"

    this.setOptions(options.options)
  }

  setDomElements() {
    super.setDomElements()
    this.inputs.select = $(this.domElements.main).find("select")[0]
  }

  getSchema(): { [p: string]: any } {
    const schema = super.getSchema()
    schema.multiselect = parseBoolean(this.multiselect)
    if (schema.multiselect) schema.db_type = "text"
    schema.optionsGrouped = this.optionsGrouped
    const opts = deepMerge({}, { options: this.options })
    schema.options = opts.options || []
    schema.optionsDict = {}
    schema.options.forEach(function (option) {
      schema.optionsDict[option.value] = option.label
    })

    return schema
  }

  // getData(){
  // TODO check this and uncomment maybe
  // data[control.name] = data[control.name].map(function(value){
  //     return {value: value, label: control.optionsDict[value]};
  // });

  // TODO: check about multiselect
  // return {value: value, label: control.optionsDict[value]};
  // }

  setEventHandlers() {
    super.setEventHandlers()

    // TODO: check about multiselect
    $(this.inputs.select).on("change", (e) => {
      if (this.multiselect) {
        // Comment by Roman: AirBNB JS guide suggest using a spread operator:
        // [...this.inputs.select.selectedOptions]
        // instead of
        // Array.from(this.inputs.select.selectedOptions)
        // but Typescript compiler thinks it's incorrect and underlines that code
        // with a red line - so I'm using Array.from()
        const selectedOptions = Array.from(this.inputs.select.selectedOptions)
        const selectedValues = selectedOptions.map((option) => {
          return { label: option.label, value: option.value }
        })
        this.setValue(selectedValues, false)
        this.triggerChanged()
      } else {
        this.setValue(this.inputs.select.value, false)
        this.triggerChanged()
      }
    })
  }

  addSelect2() {
    if ($().mselect2) {
      const select2Options: { placeholder?: string; allowClear?: boolean } = {}
      if (this.formBuilder.filtersMode && this.type == "select") {
        select2Options.placeholder = this.placeholder
        if (!this.multiselect) {
          select2Options.allowClear = true
        }
      }
      $(this.domElements.main)
        .find("select")
        .css({ width: "100%", display: "block" })
        .mselect2(select2Options)
        .on("select2:focus", function () {
          $(this).mselect2("open")
        })
        .on("select2:unselecting", function (e) {
          $(this).data("state", "unselected")
        })
        .on("select2:open", function (e) {
          if ($(this).data("state") === "unselected") {
            $(this).removeData("state")

            const self = $(this)
            setTimeout(function () {
              self.mselect2("close")
            }, 1)
          }
        })
      $(this.domElements.main)
        .find(".select2-selection--multiple .select2-search__field")
        .css("width", "100%")
    }
  }

  setOptions(options?: any[]): any[] {
    if (options) {
      this.options = []
      this.optionsDict = {}
      options.forEach((value, key) => {
        this.options.push(value)
        if (this.optionsGrouped) {
          value.options.forEach((value2, key2) => {
            this.optionsDict[value2.value] = value2
          })
        } else {
          this.optionsDict[key] = value
        }
      })
      return this.options
    } else {
      return this.setOptions([
        { label: "Option one", name: "option_one", value: 1 },
        { label: "Option two", name: "option_two", value: 2 },
      ])
    }
  }

  setInputValue(value: string | Array<{ label: string; value: string | number }>): void {
    if (this.multiselect) {
      if (this.value) {
        $(this.inputs.select).val(this.value.map((el) => el.value))
      } else {
        $(this.inputs.select).val([])
      }
    } else {
      this.inputs.select.value = this.value
    }
    $(this.inputs.select).trigger("change.select2")
  }
}
