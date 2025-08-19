import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { FormBuilder } from "../../FormBuilder"
import { FormElement } from "../FormElement"

const $ = jQuery

/**
 *
 */
export class RadioFormElement extends FormElement {
  declare inputs: { radios: RadioNodeList }
  $radios: any

  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)
    this.setOptions(options.options)
  }

  setDomElements(): void {
    super.setDomElements()
    this.inputs.radios = this.formBuilder.getForm().elements[this.name]
    this.$radios = $(this.domElements.main).find('input[type="radio"]')
    this.typeLabel = "Radio"
  }

  setEventHandlers(): void {
    super.setEventHandlers()

    this.$radios.on("change", (e) => {
      this.setValue(e.target.value, false)
      this.triggerChanged()
    })
  }

  setInputValue(value: string | null): void {
    if (value === null) {
      this.$radios.prop("checked", false)
    } else {
      this.inputs.radios.value = value
    }
  }
}
