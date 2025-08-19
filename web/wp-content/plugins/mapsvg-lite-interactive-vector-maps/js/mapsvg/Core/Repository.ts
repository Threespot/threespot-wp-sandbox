import { Query } from "../Infrastructure/Server/Query"
import {
  ApiEndpoint,
  ApiEndpointName,
  HTTPMethod,
  Schema,
  SchemaEventType,
} from "../Infrastructure/Server/Schema"
import { Server } from "../Infrastructure/Server/Server"
import { MapSVGMap } from "../Map/Map"
import { Model } from "../Model/Model"
import { ArrayIndexed } from "./ArrayIndexed"
import { Events } from "./Events"
import { mapsvgCore } from "./Mapsvg"
import { MiddlewareList, MiddlewareType } from "./Middleware"
import { addTrailingSlash, removeLeadingSlash } from "./Utils"
const $ = jQuery

export enum RepositoryEvent {
  AFTER_INIT = "afterInit",
  AFTER_LOAD = "afterLoad",
  FAILED_LOAD = "failedLoad",
  BEFORE_LOAD = "beforeLoad",
  BEFORE_FETCH = "beforeFetch",
  AFTER_FETCH = "afterFetch",
  CHANGE = "change",
  BEFORE_UPDATE = "beforeUpdate",
  AFTER_UPDATE = "afterUpdate",
  BEFORE_CREATE = "beforeCreate",
  AFTER_CREATE = "afterCreate",
  BEFORE_DELETE = "beforeDelete",
  AFTER_DELETE = "afterDelete",
  AFTER_UPDATE_SCHEMA = "afterUpdateSchema",
}

export type MapsvgRequest = {
  repository: RepositoryInterface<Model>
  action: string
  url: string
  method: HTTPMethod
  headers?: {
    Authorization: string
  }
  data?: Query | Record<string, any>
}
export type MapsvgResponse = {
  data: string | Record<string, any>
}
export type MapsvgCtx = {
  request: MapsvgRequest
  response: MapsvgResponse
  [key: string]: any
}
export interface RepositoryInterface<T extends Model = Model> {
  server: Server
  query: Query
  hasMore: boolean
  className: string

  path: string
  loaded: boolean
  schema?: Schema
  objects: ArrayIndexed<T>
  completeChunks: number

  middlewares: MiddlewareList
  events: Events

  noFiltersNoLoad: boolean

  classType: typeof Model

  setSchema(schema: Schema): void
  getSchema(): Schema

  getLoaded(): ArrayIndexed<T>
  getLoadedAsArray(): ArrayIndexed<T>
  create(object: any): JQueryDeferred<T>
  find(params?: Query): JQueryDeferred<any>
  findById(id: number | string): JQueryDeferred<T>
  getLoadedObject(id: number | string): T
  update(object: any): JQueryDeferred<T>
  delete(id: string | number): JQueryDeferred<void>

  loadDataFromResponse(data: string | Record<string, any>, ctx: MapsvgCtx): void
  reload(): void

  encodeData(params: any): { [key: string]: any }
  defaultResponseMiddleware(data: unknown, ctx: MapsvgCtx): Record<string, unknown>

  onFirstPage(): boolean
  onLastPage(): boolean
}

export class Repository<T extends Model = Model> implements RepositoryInterface<T> {
  classType: typeof Model
  name: string
  map?: MapSVGMap
  server: Server
  query: Query
  hasMore: boolean = false
  className: string = ""

  path: string
  loaded: boolean
  schema?: Schema
  objects: ArrayIndexed<T>
  completeChunks: number

  noFiltersNoLoad: boolean

  events: Events
  middlewares: MiddlewareList

  constructor(schema: Schema, map?: MapSVGMap) {
    this.classType = Model
    this.map = map
    this.server = new Server(mapsvgCore.routes.api)
    this.query = new Query()
    this.objects = new ArrayIndexed("id")
    this.completeChunks = 0
    this.setSchema(schema)
    this.middlewares = new MiddlewareList()
  }

  // TODO:
  // Proxy API to:
  // apiEndpoints: [
  //   { url: "objects/%name%", method: "GET", name: "index" },
  //   { url: "objects/%name%/[:id]", method: "GET", name: "show" },
  //   { url: "objects/%name%", method: "POST", name: "create" },
  //   { url: "objects/%name%/[:id]", method: "PUT", name: "update" },
  //   { url: "objects/%name%/[:id]", method: "DELETE", name: "delete" },
  //   { url: "objects/%name%", method: "DELETE", name: "clear" },
  // ],

  getApiEndpoint(name: string, model?: Partial<Model> | null): ApiEndpoint {
    const endpoint = this.schema.apiEndpoints.get(name)
    if (!endpoint) {
      return null
    }
    const url = endpoint.url.replace(/\[:(\w+)\]/g, (_, key) => {
      return model && model[key] !== undefined ? model[key] : `[:${key}]`
    })
    const base = addTrailingSlash(this.schema.apiBaseUrl || mapsvgCore.routes.api)
    return {
      url: base + removeLeadingSlash(url),
      method: endpoint.method,
      name: endpoint.name,
    }
  }

  init() {
    this.events = new Events({
      context: this,
      contextName: "repository",
      map: this.map,
      parent: this.map?.events,
    })
    this.events.trigger(RepositoryEvent.AFTER_INIT, { repository: this })
  }

  setNoFiltersNoLoad(value: boolean): void {
    this.noFiltersNoLoad = value
  }

  setSchema(schema: Schema | string): void {
    if (schema instanceof Schema) {
      this.schema = schema
      this.schema.events.on(SchemaEventType.UPDATE, () => this.find())
    } else {
      const load = async () => {
        const { useRepository } = await import("@/Core/useRepository")
        const schemaRepo = useRepository("schemas", this.map)

        schemaRepo.find({ filters: { name: schema } }).done((response) => {
          const schema = response.items[0]
          if (!schema) {
            console.error("Schema not found")
            return
          }
          this.setSchema(schema)
          this.find()
        })
      }
      load()
    }
  }
  getSchema(): Schema {
    return this.schema
  }

  loadDataFromResponse(data: string | Record<string, any>, ctx: MapsvgCtx): void {
    data = this.middlewares.run(MiddlewareType.RESPONSE, [
      data,
      { ...ctx, repository: this, map: this.map },
    ])

    const dataFormatted = this.defaultResponseMiddleware(data, ctx)
    this.objects.clear()

    const keyPlural = this.schema.objectNamePlural

    this.events.trigger(RepositoryEvent.BEFORE_LOAD, { data: dataFormatted, repository: this })

    if ("items" in dataFormatted && typeof dataFormatted.items === "object") {
      dataFormatted.items.forEach((obj) => {
        this.objects.push(obj)
      })
      this.hasMore = dataFormatted.hasMore
    } else {
      this.hasMore = false
    }

    this.loaded = true

    this.events.trigger(RepositoryEvent.AFTER_LOAD, { data, repository: this })
  }
  async reload(): Promise<JQueryDeferred<any>> {
    return await this.find()
  }

  create(object: Record<string, unknown>): JQueryDeferred<any> {
    const defer = jQuery.Deferred()
    defer.promise()

    const data = {}
    data[this.schema.objectNameSingular] = this.encodeData(object)

    const request = this.getRequest("create", null, data)

    this.events.trigger(RepositoryEvent.BEFORE_CREATE, {
      object: data[this.schema.objectNameSingular],
    })

    this.server
      .post(request.url, request.data)
      .done((_data: string) => {
        const response = this.getResponse(_data, request)
        const object = response.data[this.schema.objectNameSingular]
        if (object) {
          this.objects.push(object)
          this.events.trigger(RepositoryEvent.AFTER_CREATE, { object })
          defer.resolve(object)
        } else {
          defer.reject(response)
        }
      })
      .fail((response) => {
        defer.reject(response)
      })

    return defer
  }

  findById(id: number | string, nocache = false): JQueryDeferred<any> {
    const defer = jQuery.Deferred()
    defer.promise()

    const request = this.getRequest("show", { id })
    let object

    if (!nocache) {
      object = this.objects.findById(id.toString())
    }
    if (!nocache && object) {
      defer.resolve(object)
    } else {
      this.server
        .get(request.url, request.data)
        .done((_data: string) => {
          const eventData = { data: _data, repository: this }
          const response = this.getResponse(_data, request)
          this.events.trigger(RepositoryEvent.AFTER_LOAD, {
            data: response.data,
            repository: this,
          })
          const object = response.data[this.schema.objectNameSingular]
          if (object) {
            defer.resolve(object)
          } else {
            defer.reject(response)
          }
        })
        .fail((response) => {
          defer.reject(response)
        })
    }

    return defer
  }

  getRequest(
    action: ApiEndpointName,
    urlParams: undefined | null | Record<string, string | number>,
    data = {},
  ): MapsvgRequest {
    const apiEndpoint = this.getApiEndpoint(action, urlParams)
    if (!apiEndpoint) {
      throw new Error(
        `API endpoint ${apiEndpoint} does not exists for repository '${this.schema.objectNamePlural}'`,
      )
    }
    const request: MapsvgRequest = {
      repository: this,
      url: apiEndpoint.url,
      action: apiEndpoint.name,
      method: apiEndpoint.method,
      data,
    }

    request.data = this.middlewares.run(MiddlewareType.REQUEST, [
      request.data,
      { request, repository: this, map: this.map },
    ])
    return request
  }

  getResponse(_data: string, request): MapsvgResponse {
    const response: MapsvgResponse = {
      data: _data,
    }

    response.data = this.middlewares.run(MiddlewareType.RESPONSE, [
      response.data,
      { request, response, repository: this, map: this.map },
    ])

    response.data = this.defaultResponseMiddleware(response.data, { request, response })
    return response
  }

  find(query?: Query | { [key: string]: any }): JQueryDeferred<any> {
    const defer = jQuery.Deferred()
    defer.promise()

    if (typeof query !== "undefined") {
      this.query.update(query)
    }

    const request = this.getRequest("index", null, this.query)

    if (request.data !== this.query) {
      this.query.update(request.data)
    }

    const eventData = { query: this.query, repository: this }

    this.events.trigger(RepositoryEvent.BEFORE_LOAD, { query: this.query, repository: this })

    // Update query if reference was changed
    if (eventData.query !== this.query) {
      if (!(eventData.query instanceof Query)) {
        eventData.query = new Query(eventData.query)
      }
      this.query = eventData.query
    }

    if (this.noFiltersNoLoad && !this.query.hasFilters()) {
      this.objects.clear()
      this.events.trigger(RepositoryEvent.AFTER_LOAD, { data: {}, repository: this })
      defer.resolve(this.getLoaded())
      return defer
    }

    let schemaRequested = false
    if (this.schema.fields.length === 0) {
      this.query.update({ withSchema: true })
      schemaRequested = true
    }

    this.server
      .get(request.url, request.data)
      .done((serverResponse: string) => {
        if (schemaRequested) {
          this.query.update({ withSchema: false })
        }
        const response: MapsvgResponse = {
          data: serverResponse,
        }
        this.loadDataFromResponse(response.data, { request, response })
        defer.resolve(this.getLoaded())
      })
      .fail((response) => {
        defer.reject(response)
      })

    return defer
  }

  getLoaded(): ArrayIndexed<T> {
    return this.objects
  }
  getLoadedObject(id: number | string): T {
    return this.objects.findById(id.toString())
  }
  getLoadedAsArray(): ArrayIndexed<T> {
    return this.objects
  }

  update(object: any): JQueryDeferred<T> {
    const defer = jQuery.Deferred()
    defer.promise()

    const data = {}
    const objectUpdatedFields = "getDirtyFields" in object ? object.getDirtyFields() : object
    const objectEncoded = this.encodeData(objectUpdatedFields)
    data[this.schema.objectNameSingular] = objectEncoded

    const request = this.getRequest("update", { id: objectUpdatedFields.id }, data)

    this.events.trigger(RepositoryEvent.BEFORE_UPDATE, {
      object: data[this.schema.objectNameSingular],
    })

    this.server
      .put(request.url, request.data)
      .done((_data: string) => {
        if ("clearDirtyFields" in object) {
          object.clearDirtyFields()
        }

        const response = this.getResponse(_data, request)
        defer.resolve(object)
        this.events.trigger(RepositoryEvent.AFTER_UPDATE, {
          object,
          updatedFields: objectEncoded,
        })
      })
      .fail((response, stat) => {
        defer.reject(response, stat)
      })
    return defer
  }

  delete(id: number): JQueryDeferred<void> {
    const defer = jQuery.Deferred()
    defer.promise()

    const request = this.getRequest("delete", { id })

    this.events.trigger(RepositoryEvent.BEFORE_DELETE, {
      object: { id, ...this.objects.findById(id) },
    })

    this.server
      .delete(request.url)
      .done((data: string) => {
        const response = this.getResponse(data, request)
        this.objects.delete(id.toString())
        this.events.trigger(RepositoryEvent.AFTER_DELETE, { repository: this, object: { id } })
        defer.resolve()
      })
      .fail((data) => {
        defer.reject(data)
      })

    return defer
  }

  clear(): JQueryDeferred<void> {
    const defer = jQuery.Deferred()
    defer.promise()

    const request = this.getRequest("clear", null)

    this.server
      .delete(request.url)
      .done((data: string) => {
        this.objects.clear()
        const response = this.getResponse(data, request)
        this.events.trigger(RepositoryEvent.AFTER_LOAD, { repository: this, data: this.objects })
        defer.resolve()
      })
      .fail((response) => {
        defer.reject(response)
      })

    return defer
  }

  onFirstPage(): boolean {
    return this.query.page === 1
  }

  onLastPage(): boolean {
    return this.hasMore === false
  }

  encodeData(params: any): { [key: string]: any } {
    return params
  }

  defaultResponseMiddleware(data, ctx?): Record<string, any> {
    let dataRaw

    const dataJSON = data

    if (typeof dataJSON === "string") {
      dataRaw = JSON.parse(dataJSON)
    } else {
      dataRaw = { ...dataJSON }
    }

    if ("items" in dataRaw && "hasMore" in dataRaw) {
      const { items, schema, hasMore } = dataRaw
      if (schema) {
        this.schema.update(schema)
      }
      if (items && Array.isArray(items) && items.length > 0) {
        const testItem = items[0]
        if ("objectNamePlural" in testItem) {
          dataRaw.items = items.map((obj) => new Schema(obj))
        } else {
          dataRaw.items = items.map((obj) => new Model(obj, this.schema))
        }
      }
    } else {
      if ("schema" in dataRaw) {
        dataRaw.schema = new Schema(dataRaw.schema)
      }
      if (this.schema.objectNameSingular in dataRaw) {
        dataRaw[this.schema.objectNameSingular] = new Model(
          dataRaw[this.schema.objectNameSingular],
          this.schema,
        )
      }
    }

    return dataRaw
  }

  /**
   * Imports data from a CSV file.
   *
   */
  import(data: { [key: string]: any }, convertLatlngToAddress: boolean, map: MapSVGMap) {
    const _this = this

    const locationField = _this.schema.getFieldByType("location")
    let language = "en"
    if (locationField && locationField.language) {
      language = locationField.language
    }

    data = this.formatCSV(data, map)

    return this.importByChunks(data, language, convertLatlngToAddress).done(function () {
      _this.find()
    })
  }

  /**
   * Splits data to small chunks and sends every chunk separately to the server
   *
   * @param data - Data to import
   * @param language - Language for Geocoding conversions
   * @param convertLatlngToAddress - Whether lat/lng coordinates should be converted to addresses via Geocoding service
   */
  importByChunks(data: { [key: string]: any }, language: string, convertLatlngToAddress: boolean) {
    const _this = this

    let i, j, temparray
    const chunk = 50
    const chunks = []

    for (i = 0, j = data.length; i < j; i += chunk) {
      temparray = data.slice(i, i + chunk)
      chunks.push(temparray)
    }

    if (chunks.length > 0) {
      let delay = 0
      const delayPlus = chunks[0][0] && chunks[0][0].location ? 1000 : 0

      const defer = $.Deferred()
      defer.promise()

      _this.completeChunks = 0

      chunks.forEach(function (chunk) {
        delay += delayPlus
        setTimeout(function () {
          const data = {
            language: language,
            convertLatlngToAddress: convertLatlngToAddress,
          }

          data[_this.schema.objectNamePlural] = JSON.stringify(chunk)

          const objectNamePlural = _this.schema.type === "region" ? "regions" : "objects"
          _this.server
            .post(objectNamePlural + "/" + _this.schema.name + "/import", data)
            .done(function (_data) {
              _this.completeChunk(chunks, defer)
            })
            .fail((response) => {
              console.error(response)
            })
        }, delay)
      })
      return defer
    }
    return null
  }

  completeChunk(chunks, defer) {
    this.completeChunks++
    if (this.completeChunks === chunks.length) {
      defer.resolve()
    }
  }

  formatCSV(data: { [key: string]: any }, map: MapSVGMap) {
    const _this = this
    const newdata = []
    const latLngRegex =
      /^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?)[\s]?[,\s]?[\s]?[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/g

    const regionsTable = map.regionsRepository.getSchema().name

    data.forEach(function (object, index) {
      const newObject = {}
      for (const key in object) {
        const field = _this.schema.getField(key)
        if (field !== undefined) {
          switch (field.type) {
            case "region":
              newObject[key] = {}
              newObject[key] = object[key]
                .split(",")
                .map(function (regionId) {
                  return regionId.trim()
                })
                .filter(function (rId) {
                  return (
                    map.getRegion(rId) !== undefined ||
                    map.regions.find(function (item) {
                      return item.title === rId
                    }) !== undefined
                  )
                })
                .map(function (rId) {
                  let r = map.getRegion(rId)
                  if (typeof r === "undefined") {
                    r = map.regions.find(function (item) {
                      return item.title === rId
                    })
                  }
                  return { id: r.id, title: r.title, tableName: regionsTable }
                })
              break
            case "location":
              if (object[key].match(latLngRegex)) {
                let delimiter = ","
                if (object[key].indexOf(",") !== -1) {
                  delimiter = ","
                } else if (object[key].indexOf(" ") !== -1) {
                  delimiter = " "
                }
                const coords = object[key].split(delimiter).map(function (n) {
                  return parseFloat(n)
                })
                if (
                  coords.length == 2 &&
                  coords[0] > -90 &&
                  coords[0] < 90 &&
                  coords[1] > -180 &&
                  coords[1] < 180
                ) {
                  newObject[key] = {
                    geoPoint: { lat: coords[0], lng: coords[1] },
                  }
                } else {
                  newObject[key] = ""
                }
              } else if (object[key]) {
                newObject[key] = { address: object[key] }
              }

              if (typeof newObject[key] == "object") {
                newObject[key].img = map.options.defaultMarkerImage
              }

              break
            case "select": {
              const field = _this.schema.getField(key)
              if (field.multiselect) {
                const labels = _this.schema.getField(key).options.map(function (f) {
                  return f.label
                })
                newObject[key] = object[key]
                  .split(",")
                  .map(function (label) {
                    return label.trim()
                  })
                  .filter(function (label) {
                    return labels.indexOf(label) !== -1
                  })
                  .map(function (label) {
                    return _this.schema.getField(key).options.filter(function (option) {
                      return option.label == label
                    })[0]
                  })
                if (newObject[key].length === 0) {
                  const values = _this.schema.getField(key).options.map(function (f) {
                    return f.value + ""
                  })
                  newObject[key] = object[key]
                    .split(",")
                    .map(function (value) {
                      return value.trim()
                    })
                    .filter(function (value) {
                      return values.indexOf(value) !== -1
                    })
                    .map(function (value) {
                      return _this.schema.getField(key).options.filter(function (option) {
                        return option.value == value
                      })[0]
                    })
                }
              } else {
                newObject[key] = object[key]
              }
              break
            }
            case "radio":
            case "text":
            case "textarea":
            case "status":
            default:
              newObject[key] = object[key]
              break
          }
        }
      }
      data[index] = newObject
    })

    return data
  }
}
