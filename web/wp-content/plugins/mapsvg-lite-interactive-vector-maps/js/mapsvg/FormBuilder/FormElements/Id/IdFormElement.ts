import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { FormBuilder } from "../../FormBuilder"
import { FormElement } from "../FormElement"

/**
 *
 */
export class IdFormElement extends FormElement {
  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)
    this.typeLabel = "ID"
  }

  getData(): any {
    return { name: "id", value: this.value }
  }
}
