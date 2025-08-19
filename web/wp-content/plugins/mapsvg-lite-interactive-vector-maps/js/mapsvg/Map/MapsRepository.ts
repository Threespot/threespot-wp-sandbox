import { Schema } from "@/Infrastructure/Server/Schema"
import { Repository, RepositoryEvent } from "../Core/Repository"
import { MapSVGMap } from "./Map"

export class MapsRepository extends Repository {
  constructor(schema: Schema, map: MapSVGMap) {
    super(schema, map)
  }

  encodeData(params: any): { [key: string]: any } {
    const data: { [key: string]: any } = {}
    if (typeof params.options !== "undefined") {
      data.options = JSON.stringify(params.options)

      // Apache mod_sec blocks requests with the following words:
      // table, select, database. Encode those words to decode them later in PHP to prevent blocking:
      data.options = data.options.replace(/select/g, "!mapsvg-encoded-slct")
      data.options = data.options.replace(/table/g, "!mapsvg-encoded-tbl")
      data.options = data.options.replace(/database/g, "!mapsvg-encoded-db")
      data.options = data.options.replace(/varchar/g, "!mapsvg-encoded-vc")
      data.options = data.options.replace(/int\(11\)/g, "!mapsvg-encoded-int")
    }
    if (typeof params.title !== "undefined") {
      data.title = params.title
    }
    if (typeof params.id !== "undefined") {
      data.id = params.id
    }
    if (typeof params.status !== "undefined") {
      data.status = params.status
    }
    if (typeof params.version !== "undefined") {
      data.version = params.version
    }
    if (typeof params.svgFileLastChanged !== "undefined") {
      data.svgFileLastChanged = params.svgFileLastChanged
    }

    return data
  }

  defaultResponseMiddleware(dataJSON: string | { [key: string]: any }): { [key: string]: any } {
    let data

    if (typeof dataJSON === "string") {
      data = JSON.parse(dataJSON)
    } else {
      data = dataJSON
    }

    return data
  }

  copy(id: number, title: string): JQueryDeferred<any> {
    const defer = jQuery.Deferred()
    defer.promise()

    const data = { options: { title: title } }

    const request = this.getRequest("copy", { id }, data)

    this.server
      .post(request.url, this.encodeData(data))
      .done((response: string) => {
        const data = this.defaultResponseMiddleware(response)
        this.objects.clear()
        this.events.trigger(RepositoryEvent.AFTER_LOAD)
        this.events.trigger("cleared")
        defer.resolve(data.map)
      })
      .fail(() => {
        defer.reject()
      })

    return defer
  }

  createFromV2(object: any) {
    const defer = jQuery.Deferred()
    defer.promise()

    const data = {}
    data[this.schema.objectNameSingular] = this.encodeData(object)

    const request = this.getRequest("createFromV2", null, data)

    this.server
      .post(request.url, data)
      .done((response: any) => {
        const data = this.defaultResponseMiddleware(response)
        const object = data[this.schema.objectNameSingular]
        this.objects.push(object)
        defer.resolve(object)
        this.events.trigger(RepositoryEvent.AFTER_CREATE, this, [object])
      })
      .fail(() => {
        defer.reject()
      })

    return defer
  }
}
