import { parseBoolean } from "@/Core/Utils"
import { FormElementOptions } from "@/FormBuilder/FormElements/FormElement"
import { ArrayIndexed } from "../../Core/ArrayIndexed"

// TODO finish the types
export type SchemaFieldType =
  | "id"
  | "text"
  | "textarea"
  | "radio"
  | "post"
  | "select"
  | "region"
  | "checkbox"
  | "checkboxes"
  | "image"
  | "marker"
  | "location"
  | "datetime"
  | "date"
  | "status"
  | "json"
  | "boolean"
  | "modal"
  | "save"
  | "empty"
  | "search"
  | "distance"

export type SchemaFieldDbType =
  | "int(11)"
  | "varchar(255)"
  | "datetime"
  | "text"
  | "boolean"
  | "float"
  | "double"
  | "json"

export interface SchemaFieldProps {
  type: SchemaFieldType
  label?: string
  name?: string
  help?: string
  options?: ArrayIndexed<{ [key: string]: any }>
  visible?: boolean
  hiddenOnTable?: boolean
  readonly?: boolean
  protected?: boolean
  renamable?: boolean
  searchable?: boolean
  value?: any
  placeholder?: string
  parameterName?: string
  parameterNameShort?: string
  db_type?: SchemaFieldDbType
  db_default?: number | boolean | string
  [key: string]: any
}

const defaultFieldProps = {
  type: "text",
  db_type: "varchar(255)",
  label: "",
  name: "",
  renamable: true,
  visible: true,
  hiddenOnTable: false,
  searchable: false,
  readonly: false,
  protected: false,
}

/**
 * A Schema field descriptor. Contains field name, label, type, etc.
 */
export class SchemaField implements SchemaFieldProps {
  type: SchemaFieldType
  label?: string
  name?: string
  help?: string
  options?: ArrayIndexed<FormElementOptions>
  visible?: boolean
  hiddenOnTable?: boolean
  readonly?: boolean
  protected?: boolean
  renamable?: boolean
  searchable?: boolean
  value?: any
  placeholder?: string
  parameterName?: string
  parameterNameShort?: string
  db_type?: SchemaFieldDbType
  db_default?: number | boolean | string;
  [key: string]: any

  constructor(field: { [key: string]: any }) {
    const booleans = ["visible", "searchable", "readonly", "protected", "renamable"]

    // Merge default properties with the passed field properties
    const mergedField = { ...defaultFieldProps, ...field }

    // Assign merged properties to the instance
    Object.assign(this, mergedField)

    if (this.type === "distance") {
      this.parameterName = "Object.distance"
      this.parameterNameShort = "distance"
    }

    // Set default values for booleans and parse them
    booleans.forEach((paramName) => {
      this[paramName] = parseBoolean(this[paramName])
    })

    // Ensure options is an instance of ArrayIndexed
    if (this.options && !(this.options instanceof ArrayIndexed)) {
      this.options = new ArrayIndexed("value", this.options)
    }
  }

  getEnumLabel(value: string | number): string | null {
    if (!this.options) {
      return null
    }
    const option = this.options.get(value)
    if (typeof option !== "undefined") {
      return option.label
    } else {
      return ""
    }
  }
}
