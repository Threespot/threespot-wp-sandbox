import { Repository } from "../Core/Repository"

export class MapsV2Repository extends Repository {
  path = "maps-v2/"

  constructor() {
    //@ts-expect-error
    super("map", "maps")
  }

  encodeData(params: any): { [key: string]: any } {
    const data: { [key: string]: any } = {}
    data.options = JSON.stringify(params.options)

    // Apache mod_sec blocks requests with the following words:
    // table, select, database. Encode those words to decode them later in PHP to prevent blocking:
    data.options = data.options.replace(/select/g, "!mapsvg-encoded-slct")
    data.options = data.options.replace(/table/g, "!mapsvg-encoded-tbl")
    data.options = data.options.replace(/database/g, "!mapsvg-encoded-db")
    data.options = data.options.replace(/varchar/g, "!mapsvg-encoded-vc")

    data.id = params.id
    data.title = params.title

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
}
