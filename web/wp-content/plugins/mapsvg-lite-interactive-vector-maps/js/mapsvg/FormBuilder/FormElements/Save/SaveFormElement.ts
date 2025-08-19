import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { FormBuilder } from "../../FormBuilder.js"
import { FormElement } from "../FormElement.js"

const $ = jQuery

/**
 *
 */
export class SaveFormElement extends FormElement {
  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)
    this.readonly = true
  }

  setDomElements() {
    super.setDomElements()
    this.inputs.btnSave = <HTMLButtonElement>$(this.domElements.main).find(".btn-save")[0]
    this.inputs.btnClose = <HTMLButtonElement>$(this.domElements.main).find(".mapsvg-close")[0]
  }

  setEventHandlers() {
    super.setEventHandlers()
    $(this.inputs.btnSave).on("click", (e) => {
      e.preventDefault()
      this.events.trigger("click.btn.save")
    })
    $(this.inputs.btnClose).on("click", (e) => {
      e.preventDefault()
      this.events.trigger("click.btn.close")
    })
  }
}
