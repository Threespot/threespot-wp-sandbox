import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { FormBuilder } from "../../FormBuilder.js"
import { FormElement } from "../FormElement"

const $ = jQuery

/**
 *
 */
export class CheckboxFormElement extends FormElement {
  checkboxLabel: string
  checkboxValue: string
  showAsSwitch: boolean
  declare inputs: { checkbox: HTMLInputElement; switch: HTMLInputElement }

  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)
    if (typeof options.showAsSwitch === "undefined") {
      this.showAsSwitch = true
    } else {
      this.showAsSwitch = options.showAsSwitch
    }
    this.db_type = "tinyint(1)"
    this.typeLabel = "Checkbox"
    this.checkboxLabel = options.checkboxLabel
    this.checkboxValue = options.checkboxValue
  }

  setDomElements() {
    super.setDomElements()
    this.inputs.checkbox = $(this.domElements.main).find("input")[0]
    this.inputs.switch = $(this.domElements.main).find("input")[0]
  }

  setEventHandlers() {
    super.setEventHandlers()
    $(this.inputs.checkbox).on("change", (e) => {
      this.setValue(e.target.checked, false)
      this.triggerChanged()
    })
  }

  setEditorEventHandlers() {
    super.setEditorEventHandlers()

    $(this.domElements.edit).on("change", "[name='showAsSwitch']", (e) => {
      if (this.showAsSwitch) {
        $(this.domElements.main)
          .find("[name='checkboxToSwitch']")
          .addClass("form-switch form-switch-md")
      }
      if (!this.showAsSwitch) {
        $(this.domElements.main)
          .find("[name='checkboxToSwitch']")
          .removeClass("form-switch form-switch-md")
      }
    })

    $(this.domElements.edit).on("keyup change paste", '[name="checkboxLabel"]', (e) => {
      $(this.domElements.main).find(".form-check-label").text(e.target.value)
    })
  }

  getSchema(): { [p: string]: any } {
    const schema = super.getSchema()
    if (this.checkboxLabel) {
      schema.checkboxLabel = this.checkboxLabel
    }
    if (this.checkboxValue) {
      schema.checkboxValue = this.checkboxValue
    }

    schema.showAsSwitch = this.showAsSwitch

    return schema
  }

  setInputValue(value: boolean): void {
    this.inputs.checkbox.checked = value === true
  }
}
