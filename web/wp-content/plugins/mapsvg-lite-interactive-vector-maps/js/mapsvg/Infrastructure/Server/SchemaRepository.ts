import { MapSVGMap } from "@/Map/Map"
import { Repository, RepositoryEvent } from "../../Core/Repository"
import { Schema } from "./Schema"
import { SchemaModel } from "./SchemaModel"

/**
 * Repository used to manage schemas in the database.
 */
export class SchemaRepository extends Repository<SchemaModel> {
  constructor(schema: Schema, map?: MapSVGMap) {
    super(schema, map)
    this.className = "Schema"
  }

  update(schema: SchemaModel | Schema): JQueryDeferred<any> {
    const defer = jQuery.Deferred()
    defer.promise()

    const data = {}

    data[this.schema.objectNameSingular] = this.encodeData(schema)

    const req = this.getRequest("update", { id: schema["id"] }, data)

    this.server
      .put(req.url, req.data)
      .done((response: string) => {
        // const data = this.defaultResponseMiddleware({ data: response }, this)
        // this.objects.push(schema)
        defer.resolve(response)
        this.events.trigger(RepositoryEvent.AFTER_UPDATE, { schema: response })
        if (schema instanceof Schema) {
          schema.events.trigger("update")
        }
      })
      .fail(() => {
        defer.reject()
      })
    return defer
  }

  encodeData(schema: Schema | SchemaModel): { [key: string]: any } {
    const _schema = schema.getData()

    let fieldsJsonString = JSON.stringify(_schema)

    fieldsJsonString = fieldsJsonString.replace(/select/g, "!mapsvg-encoded-slct")
    fieldsJsonString = fieldsJsonString.replace(/table/g, "!mapsvg-encoded-tbl")
    fieldsJsonString = fieldsJsonString.replace(/database/g, "!mapsvg-encoded-db")
    fieldsJsonString = fieldsJsonString.replace(/varchar/g, "!mapsvg-encoded-vc")
    fieldsJsonString = fieldsJsonString.replace(/int\(11\)/g, "!mapsvg-encoded-int")

    const back = JSON.parse(fieldsJsonString)
    back.fields = JSON.stringify(_schema.fields)
    back.apiEndpoints = JSON.stringify(_schema.apiEndpoints)

    return back
  }
}
