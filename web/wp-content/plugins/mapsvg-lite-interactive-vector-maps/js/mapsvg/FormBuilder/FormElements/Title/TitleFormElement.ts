import { parseBoolean } from "@/Core/Utils"
import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { FormBuilder } from "../../FormBuilder.js"
import { FormElement } from "../FormElement.js"

const $ = jQuery

/**
 *
 */
export class TitleFormElement extends FormElement {
  searchType: string
  searchFallback: boolean
  noResultsText: string
  width: string
  declare inputs: { text: HTMLInputElement }

  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)
    this.searchFallback = parseBoolean(options.searchFallback)
    this.width =
      this.formBuilder.filtersHide && !this.formBuilder.modal ? null : options.width || "100%"
    this.db_type = "varchar(255)"
    this.typeLabel = "Title"
  }

  setDomElements() {
    super.setDomElements()
    this.inputs.text = <HTMLInputElement>$(this.domElements.main).find('input[type="text"]')[0]
  }

  getSchema(): { [p: string]: any } {
    const schema = super.getSchema()
    schema.searchType = this.searchType
    return schema
  }

  setEventHandlers() {
    super.setEventHandlers()
    $(this.inputs.text).on("change keyup paste", (e) => {
      this.setValue(e.target.value, false)
      this.triggerChanged()
    })
  }

  setInputValue(value: string): void {
    this.inputs.text.value = value
  }
}
