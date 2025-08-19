import { MapSVGMap } from "@/Map/Map"
import { FormBuilder } from "../FormBuilder/FormBuilder"
import { Query } from "../Infrastructure/Server/Query"
import { Schema } from "../Infrastructure/Server/Schema"
import { SchemaRepository } from "../Infrastructure/Server/SchemaRepository"
import { Server } from "../Infrastructure/Server/Server"
import { GeoPoint, Location, ScreenPoint } from "../Location/Location"
import { MapsRepository } from "../Map/MapsRepository"
import { MapsV2Repository } from "../Map/MapsV2Repository"
import { Marker } from "../Marker/Marker"
import { Model } from "../Model/Model"
import { Region } from "../Region/Region"
import { ArrayIndexed } from "./ArrayIndexed"
import { Mapsvg, mapsvgCore } from "./Mapsvg"
import { Repository } from "./Repository"
import utils from "./Utils"
import { useRepository } from "./useRepository"

/**
 * Client class, globally available as `mapsvg` or `window.mapsvg`.
 * It contains all of the classes and utilities.
 * Example usage:
 * ```js
 * // Init objects
 * const map = new mapsvg.map(...)
 * const location = new mapsvg.location(...)
 *
 * // Get map
 * const map = mapsvg.getById(12)
 *
 * // Use utilities
 * if(mapsvg.utils.env.isPhone()) {
 *   // do something for mobile devices
 * }
 *
 * const capitalizedString = map.utils.strings.ucfirst("orange") // > Orange
 * ```
 */
export class MapsvgClient extends Mapsvg {
  arrayIndexed: typeof ArrayIndexed = ArrayIndexed
  customObject = Model
  formBuilder = FormBuilder
  geoPoint = GeoPoint
  location = Location
  mapsRepository = MapsRepository
  mapsV2Repository = MapsV2Repository
  marker = Marker
  region = Region
  query = Query
  repository = Repository
  schema = Schema
  schemaRepository = SchemaRepository
  screenPoint = ScreenPoint
  server = Server
  svgPoint = SVGPoint
  map = MapSVGMap
  useRepository: typeof useRepository
  utils = utils
  initialized: boolean

  constructor() {
    super()
    this.instances = mapsvgCore.instances
    this.nonce = mapsvgCore.nonce
    this.mediaUploader = mapsvgCore.mediaUploader
    this.templatesLoaded = mapsvgCore.templatesLoaded
    this.routes = mapsvgCore.routes
    this.mouse = mapsvgCore.mouse
    this.useRepository = useRepository
  }
}

/**
 * @ignore
 */
export const initGlobals = () => {
  window.mapsvg = new MapsvgClient()
  window.MapSVG = Object.assign(
    Object.create(Object.getPrototypeOf(window.mapsvg)), // Create a new object with the same prototype as window.mapsvg
    window.mapsvg, // Copy own properties from window.mapsvg
  )

  // Now add other properties or methods
  Object.assign(window.MapSVG, {
    mouseCoords: utils.env.getMouseCoords,
    isPhone: utils.env.isPhone,
    ...mapsvgCore,
    ...utils.env,
    ...utils.files,
    ...utils.funcs,
    ...utils.http,
    ...utils.numbers,
    ...utils.strings,
  })
  window.mapsvg.initialized = true
  window.dispatchEvent(new Event("mapsvgClientInitialized"))
}
