import { mapsvgCore } from "@/Core/Mapsvg"
import { throttle, ucfirst } from "@/Core/Utils"
import { ArrayIndexed } from "../../Core/ArrayIndexed"
import { Events } from "../../Core/Events"
import { SchemaField, SchemaFieldProps } from "./SchemaField"
import { SchemaModel } from "./SchemaModel"

export type HTTPMethod = "POST" | "GET" | "DELETE" | "PUT"

export type ApiEndpoint = {
  method: HTTPMethod
  url: string
  name: ApiEndpointName
}

export type ApiEndpointName =
  | "index"
  | "show"
  | "get"
  | "update"
  | "create"
  | "delete"
  | "clear"
  | string

export type ApiEndpoints = ArrayIndexed<ApiEndpoint>

export type SchemaType = "object" | "post" | "api" | "schema" | "region" | "map"
export interface SchemaOptions {
  id?: number
  type?: SchemaType
  name?: string
  title?: string
  fields?: SchemaFieldProps[]
  apiEndpoints?: ApiEndpoints
  apiBaseUrl?: string
  authorization?: AuthorizationCredentials
  objectNameSingular: string
  objectNamePlural: string
  strict?: boolean
  remote?: boolean
}

export enum SchemaEventType {
  UPDATE = "update",
}

const defaultSchemaOptions: Partial<SchemaOptions> = {
  strict: true,
  type: "object",
}

function getDefaultApiEndpoints(type: SchemaType): ApiEndpoint[] {
  if (type === "post" || type === "object") {
    return [
      { url: "/objects/%name%", method: "GET", name: "index" },
      { url: "/objects/%name%/[:id]", method: "GET", name: "show" },
      { url: "/objects/%name%", method: "POST", name: "create" },
      { url: "/objects/%name%/[:id]", method: "PUT", name: "update" },
      { url: "/objects/%name%/[:id]", method: "DELETE", name: "delete" },
      { url: "/objects/%name%/[:id]/import", method: "POST", name: "import" },
      { url: "/objects/%name%", method: "DELETE", name: "clear" },
    ]
  } else if (type === "region") {
    return [
      { url: "/regions/%name%", method: "GET", name: "index" },
      { url: "/regions/%name%/[:id]", method: "GET", name: "show" },
      { url: "/regions/%name%", method: "POST", name: "create" },
      { url: "/regions/%name%/[:id]", method: "PUT", name: "update" },
      { url: "/regions/%name%/[:id]/import", method: "POST", name: "import" },
      { url: "/regions/%name%/[:id]", method: "DELETE", name: "delete" },
    ]
  }
  return []
}

export type AuthorizationCredentials = { type: "Bearer" | "Basic"; token: string }

/**
 * Schema class contains the list of fields with their options for MapSVG database or regions tables
 */
export class Schema {
  id: number
  type: SchemaType
  name: string
  title?: string
  fields?: ArrayIndexed<SchemaField>
  lastChangeTime: number
  events: Events
  apiEndpoints?: ApiEndpoints
  apiBaseUrl: string
  authorization?: AuthorizationCredentials
  objectNameSingular: string
  objectNamePlural: string
  strict: boolean
  remote: boolean
  model: SchemaModel

  constructor(options: SchemaOptions) {
    this.fields = new ArrayIndexed("name")
    this.apiEndpoints = new ArrayIndexed("name")
    const _options = { ...defaultSchemaOptions, ...options }

    const { name, type, ...rest } = _options
    this.setName(name)
    this.setType(type)

    this.build(rest)

    this.lastChangeTime = Date.now()
    this.events = new Events({
      context: this,
      contextName: "schema",
    })
  }

  setAuthorization(credentials: AuthorizationCredentials) {
    this.authorization = credentials
  }

  setApiEndpoints(endpoints: ApiEndpoint[]): ApiEndpoints {
    this.apiEndpoints.clear()
    if (endpoints) {
      this.apiEndpoints.push(...endpoints)
    }
    return this.apiEndpoints
  }

  setApiBaseUrl(url: string): void {
    if (url) {
      this.apiBaseUrl = url.replace(/\/+$/, "")
    }
  }

  setObjectNameSingular(nameSingular: string) {
    this.objectNameSingular = nameSingular
  }

  setObjectNamePlural(namePlural: string) {
    this.objectNamePlural = namePlural
  }

  build(options) {
    for (const paramName in options) {
      const setter = "set" + ucfirst(paramName)
      if (typeof options[paramName] !== "undefined" && typeof this[setter] == "function") {
        this[setter](options[paramName])
      }
    }
  }

  setType(val: SchemaType) {
    this.type = val

    const typesWithDefaultApi = ["region", "object", "post"]

    if (typesWithDefaultApi.includes(this.type)) {
      const endpoints = getDefaultApiEndpoints(this.type).map((endpoint) => ({
        ...endpoint,
        url: endpoint.url.replace("%name%", this.name),
      }))
      this.setApiEndpoints(endpoints)
      this.setApiBaseUrl(mapsvgCore.routes.api.replace(/\/+$/, ""))
    }
  }

  update(options) {
    this.build(options)
  }

  setRemote(value: boolean) {
    this.remote = value
  }

  setStrict(value: boolean) {
    this.strict = value
  }

  setId(id: number) {
    this.id = id
  }
  setTitle(title: string) {
    this.title = title
  }
  setName(name: string) {
    this.name = name
  }

  loaded() {
    return this.fields.length !== 0
  }

  setFields(fields: any[]) {
    if (fields) {
      this.fields.clear()

      fields.forEach((fieldParams) => {
        this.fields.push(new SchemaField(fieldParams))
      })
    }
  }

  addField(field: SchemaField | Record<string, unknown>) {
    const fieldFinal = field instanceof SchemaField ? field : new SchemaField(field)
    this.fields.push(fieldFinal)
    throttle(this.events.trigger, 500, this, [SchemaEventType.UPDATE, { schema: this }])
  }

  getFields(): ArrayIndexed<SchemaField> {
    return this.fields
  }

  getFieldNames(): Array<string> {
    return this.fields.map((f) => f.name)
  }
  getField(field) {
    return this.fields.findById(field)
  }
  getFieldByType(type) {
    let f = null
    this.fields.forEach(function (field) {
      if (field.type === type) f = field
    })
    return f
  }
  getColumns(filters) {
    filters = filters || {}

    const columns = this.fields

    const needfilters = Object.keys(filters).length !== 0
    let results = []

    if (needfilters) {
      let filterpass
      columns.forEach(function (obj) {
        filterpass = true
        for (const param in filters) {
          filterpass = obj[param] == filters[param]
        }
        filterpass && results.push(obj)
      })
    } else {
      results = columns
    }

    return results
  }

  getData() {
    const data = {
      id: this.id,
      title: this.title,
      name: this.name,
      fields: this.fields,
      type: this.type,
      authorization: this.authorization,
      apiEndpoints: this.apiEndpoints,
      apiBaseUrl: this.apiBaseUrl,
      objectNamePlural: this.objectNamePlural,
      objectNameSingular: this.objectNameSingular,
      remote: this.remote,
    }
    return data
  }
}
