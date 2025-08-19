import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { FormBuilder } from "../../FormBuilder"
import { FormElement } from "../FormElement"

const $ = jQuery

/**
 *
 */
export class CheckboxesFormElement extends FormElement {
  checkboxLabel: string
  checkboxValue: string
  $checkboxes: any

  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)

    this.db_type = "text"
    this.typeLabel = "Checkboxes"
    this.checkboxLabel = options.checkboxLabel

    this.setOptions(options.options)
  }

  setDomElements() {
    super.setDomElements()
    this.$checkboxes = $(this.domElements.main).find('input[type="checkbox"]')
    this.setInputValue(this.value || [])
  }

  setEventHandlers() {
    super.setEventHandlers()
    this.$checkboxes.on("change", (e) => {
      const values = []
      $(this.domElements.main)
        .find("input:checked")
        .each((i, el) => {
          values.push(jQuery(el).attr("value"))
        })
      this.setValue(values, false)
      this.triggerChanged()
    })
  }

  setInputValue(values: string[] | null): void {
    if (values === null) {
      $(this.domElements.main).find(`input`).prop("checked", false)
    } else {
      values.forEach((value: string) => {
        $(this.domElements.main).find(`input[value="${value}"]`).prop("checked", true)
      })
    }
  }
}
