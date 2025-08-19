import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { FormBuilder } from "../../FormBuilder"
import { FormElement } from "../FormElement"

const $ = jQuery

/**
 *
 */
export class ModalFormElement extends FormElement {
  showButtonText: boolean

  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)

    this.showButtonText = options.showButtonText
    this.typeLabel = "Modal"
  }

  getSchema(): { [p: string]: any } {
    const schema = super.getSchema()
    schema.showButtonText = this.showButtonText
    return schema
  }
}
