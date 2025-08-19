/// <reference types="@types/google.maps" />
// import {} from "googlemaps";
import tinycolor from "tinycolor2"
import { ArrayIndexed } from "../Core/ArrayIndexed"
import { EventWithData, Events } from "../Core/Events"

import { mapsvgCore } from "@/Core/Mapsvg"
import {
  MiddlewareHandler,
  MiddlewareHandlers,
  MiddlewareList,
  MiddlewareType,
} from "@/Core/Middleware"
import utils, {
  compareVersions,
  debounce,
  deepMerge,
  env,
  fixColorHash,
  functionFromString,
  getMouseCoords,
  getNestedValue,
  isNumber,
  isPhone,
  isRemoteUrl,
  isTablet,
  sameDomain,
  splitObjectAndField,
  ucfirst,
} from "@/Core/Utils"
import { useRepository } from "@/Core/useRepository"
import { FormElementInterface } from "@/FormBuilder/FormElements"
import { SchemaEventType } from "@/Infrastructure/Server/Schema"
import { MapsvgRequest, Repository, RepositoryEvent } from "../Core/Repository"
import { ResizeSensor } from "../Core/ResizeSensor"
import { DetailsController } from "../Details/Details"

import { FiltersController } from "../Filters/Filters"
import { Query } from "../Infrastructure/Server/Query"
import { Schema } from "../Infrastructure/Server/Schema"
import { SchemaField } from "../Infrastructure/Server/SchemaField"
import { SchemaRepository } from "../Infrastructure/Server/SchemaRepository"
import {
  GeoPoint,
  GeoPointLiteral,
  Location,
  SVGPoint,
  SVGPointLiteral,
  ScreenPoint,
  isGeoPointLiteral,
  isSvgPointLiteral,
} from "../Location/Location"
import { MapObject, MapObjectEvent, MapObjectType } from "../MapObject/MapObject"
import { Marker } from "../Marker/Marker"
import { MarkerCluster } from "../MarkerCluster/MarkerCluster"
import { Model } from "../Model/Model"
import { PopoverController } from "../Popover/Popover"
import { Region } from "../Region/Region"
import { Tooltip } from "../Tooltips/Tooltip"
import { Converter } from "./Converter"
import {
  GalleryOptionsInterface,
  GroupOptionsInterface,
  LayersControlOptionsInterface,
  MapOptions,
  RegionOptionsCollection,
} from "./OptionsInterfaces/MapOptions"
import { ToolbarController } from "./Toolbar"
import { GeoViewBox, ViewBox } from "./ViewBox"
import { DefaultOptions } from "./default-options"
import "./map.css"

const $ = jQuery

enum MapEventType {
  AFTER_INIT = "afterInit",
  BEFORE_INIT = "beforeInit",
  ZOOM = "zoom",
  AFTER_CHANGE_BOUNDS = "afterChangeBounds",
  RESIZE = "resize",
}

export type RegionStylesByStatus = {
  [key: string]: { selected: Region["style"]; hover: Region["style"]; default: Region["style"] }
}

export type RegionStylesByStatusOptions = {
  [key: string]: { selected?: Region["style"]; hover?: Region["style"]; default?: Region["style"] }
}

export type ZoomLevel = {
  zoomLevel: number
  viewBox: ViewBox
  _scale: number
}

/**
 * MapSVGMap class. Creates a new map inside of the given HTML container, which is typically a DIV element.
 *
 * The class constructor is available in the global varialbe, as `mapsvg.map`.
 *
 * Example usage:
 *
 * ```js
 * const map = new mapsvg.map("my-container-id", {
 *   id: "my-map",
 *   options: {
 *     source: "/path/to/map.svg",
 *     zoom: {on: true},
 *     scroll: {on: true}
 *   }
 * });
 *
 * // Get map instance by container ID
 * const map = mapsvg.getById("my-map");
 *
 * // Get map instance by index (in the order of creation)
 * const map_1 = mapsvg.get(0) // first map
 * const map_2 = mapsvg.get(1) // second map
 * ```
 */
export class MapSVGMap {
  id: number
  version: string
  containerId: string
  markerOptions: Record<string, unknown> = { src: mapsvgCore.routes.root + "markers/pin1_red.png" }
  middlewares: MiddlewareList
  controllerMiddleWare: MiddlewareHandler
  private previousPaddingBottom: number = 0

  converter: Converter

  dirtyFields: string[] = []

  regions: ArrayIndexed<Region> = new ArrayIndexed("id")
  objects: ArrayIndexed<Model> = new ArrayIndexed("id")

  regionsRepository: Repository
  objectsRepository: Repository
  filtersRepository: Repository
  schemaRepository: SchemaRepository

  events: Events

  resizeSensor: ResizeSensor

  liveCss: HTMLStyleElement

  defaults: MapOptions
  options: MapOptions
  optionsDelta: { [key: string]: any }

  mapIsGeo: boolean
  geoCoordinates: boolean = false
  inBackend: boolean
  highlightedRegions: Array<Region> = []
  editRegions: { on: boolean } = { on: false }
  editMarkers: { on: boolean } = { on: false }
  clickAddsMarker: boolean
  editData: { on: boolean } = { on: false }
  disableLinks: boolean
  markerEditHandler: (location: Location) => void
  regionEditHandler: () => void
  objectClickedBeforeScroll: Region | Marker | MarkerCluster
  highlighted_marker: Marker

  googleMaps?: {
    loaded: boolean
    map: google.maps.Map
    zoomLimit?: boolean
    overlay?: any
    initialized: boolean
    maxZoomService?: google.maps.MaxZoomService
    currentMaxZoom?: number
  } = {
    loaded: false,
    initialized: false,
    map: null,
    zoomLimit: true,
    maxZoomService: null,
  }

  groups: ArrayIndexed<GroupOptionsInterface> = new ArrayIndexed("id", [], {
    autoId: true,
    unique: true,
  })

  galleries: ArrayIndexed<GalleryOptionsInterface>

  svgDefault: {
    viewBox?: ViewBox
    geoViewBox?: GeoViewBox
    width?: number
    height?: number
  } = {}

  /**
   *  Current viewBox
   */
  viewBox: ViewBox = new ViewBox()
  /**
   *  Initial viewBox
   **/
  _viewBox: ViewBox = new ViewBox()
  /**
   * Width/height ratio
   */
  whRatio: {
    original: number
    current: number
  }
  /**
   * Geo ViewBiox, containing to geo-points: SW(lat,lng) and NE(lat,lng)
   */
  geoViewBox: GeoViewBox = new GeoViewBox(new GeoPoint(0, 0), new GeoPoint(0, 0))

  mapLonDelta: number
  mapLatBottomDegree: number

  /**
   * The list of all zoom levels, with viewBoxes
   */
  zoomLevels: ArrayIndexed<ZoomLevel>
  /**
   * Flag defining whether the map is zooming at the moment
   */
  isZooming: boolean
  touchZoomStart: boolean
  scaleDistStart: number
  scrollStarted: boolean

  isScrolling: boolean = false
  zoomDelta: number = 0
  vbStart: boolean

  /**
   * Current map scale relative to original size
   */
  scale: number = 1
  superScale: number
  /**
   * Scroll coordinates
   */
  scroll: {
    tx?: number
    ty?: number
    vxi?: number
    vyi?: number // initial viewbox when scrollning started
    x?: number
    y?: number // mouse coordinates when scrolling started
    dx?: number
    dy?: number // mouse delta
    vx?: number
    vy?: number // new viewbox x/y
    gx?: number
    gy?: number // for google maps scroll
    touchScrollStart: number
  } = {
    tx: 0,
    ty: 0,
    vxi: 0,
    vyi: 0, // initial viewbox when scrollning started
    x: 0,
    y: 0, // mouse coordinates when scrolling started
    dx: 0,
    dy: 0, // mouse delta
    vx: 0,
    vy: 0, // new viewbox x/y
    gx: 0,
    gy: 0, // for google maps scroll
    touchScrollStart: 0,
  }

  /**
   * Flag defining whether map needs to be downscaled or upscaled to prevent blurring on iOS devices
   */
  iosDownscaleFactor: number

  /**
   * Map containers
   */
  containers: {
    /**
     * SVG image container
     */
    svg?: SVGSVGElement
    /**
     * Map container
     */
    map?: HTMLElement
    /**
     * Parent map container
     */
    mapContainer?: HTMLElement
    /**
     * Google Maps container
     */
    googleMaps?: HTMLElement
    /**
     * Scrolling container
     */
    scrollpane?: HTMLElement
    /**
     * Wrapper of scrolling container
     */
    scrollpaneWrap?: HTMLElement
    /**
     * Wrapper of map container
     */
    wrap?: HTMLElement
    /**
     * Main wrapper for all containers
     */
    wrapAll?: HTMLElement
    /**
     * Container for "Loading map..." message
     */
    loading?: HTMLElement
    /**
     * Directory container
     */
    directory?: HTMLElement
    /**
     * Filters container
     */
    filters?: HTMLElement
    /**
     * Details view container
     */
    detailsView?: HTMLElement
    /**
     * Popover container
     */
    popover?: HTMLElement
    /**
     * Tooltip container
     */
    tooltipRegion?: HTMLElement
    /**
     * Tooltip container
     */
    tooltipMarker?: HTMLElement
    /**
     * Tooltip container
     */
    tooltipCluster?: HTMLElement
    /**
     * Layers container
     */
    layers?: HTMLElement
    toolbar?: HTMLElement
    /**
     * Layers controls container
     */
    layersControl?: HTMLElement
    layersControlLabel?: HTMLElement
    layersControlList?: HTMLElement
    layersControlListNano?: HTMLElement
    pagerMap?: HTMLElement
    pagerDir?: HTMLElement
    leftSidebar?: HTMLElement
    rightSidebar?: HTMLElement
    header?: HTMLElement
    footer?: HTMLElement
    filterTags?: HTMLElement
    filtersModal?: HTMLElement
    clustersCss?: HTMLStyleElement
    clustersHoverCss?: HTMLStyleElement
    markersCss?: HTMLStyleElement
    controls?: HTMLElement
    zoomButtons?: HTMLElement
    legend?: {
      title: HTMLElement
      text: HTMLElement
      coloring: HTMLElement
      description: HTMLElement
      container: HTMLElement
    }
    choroplethSourceSelect?: {
      container: HTMLElement
      select: HTMLElement
      options: Array<HTMLElement>
    }
  } = {}
  containersCreated: boolean = false
  eventsBaseInitialized: boolean = false

  /**
   * Map controllers
   *
   * Example:
   * ```js
   * const { popover } = map.controllers
   * popover.show()
   * ```
   */
  controllers: {
    detailsView?: DetailsController
    popover?: PopoverController
    directory?: {[key: string]: any}
    
    tooltips?: {
      cluster?: Tooltip
      marker?: Tooltip
      region?: Tooltip
    }
    filters?: FiltersController
    toolbar?: ToolbarController
  } = {}

  /**
   * Handlebars templates (for Details View, Popover, etc.)
   */
  templates: {
    [key: string]: any // returned by Handlebars.compile()
  }
  /**
   * Array of Markers
   *
   * Example:
   * ```js
   * map.markers.forEach(marker => marker.hide())
   * ```
   */
  markers: ArrayIndexed<Marker> = new ArrayIndexed("id", [], { unique: true })
  /**
   * Array of Marker clusters
   */
  markersClusters: ArrayIndexed<MarkerCluster> = new ArrayIndexed("id")
  /**
   * Flag indicating first data load
   */
  firstDataLoad: boolean
  /**
   * Sets of pre-calculated clusters for every zoom level
   */
  clustersFromWorker: Array<MarkerCluster>
  /**
   * An array of selected reion IDs
   */
  selected_id: Array<string> = []
  /**
   * A reference ot currently selected marker
   */
  selected_marker: Marker
  /**
   * Clusterizer worker
   */
  clusterizerWorker: Worker
  /**
   * Reference to a Marker currently edited (dragged)
   */
  editingMarker: Marker

  /**
   * Initial zoom level
   */
  initialZoomLevel: number

  /**
   * Schema for the map filters (set of fields and their parameters)
   */
  filtersSchema: Schema

  layers: {
    [key: string]: HTMLElement
  } = {}
  controls: {
    zoom: HTMLElement
    userLocation: HTMLElement
    zoomReset: HTMLElement
    previousMap?: {[key: string]: any}
    
  }
  formBuilder: any

  tooltip: {
    posOriginal: { [key: string]: string }
    posShifted: { [key: string]: string }
    posShiftedPrev: { [key: string]: string }
    mirror: { [key: string]: number }
  }
  firefoxScroll: {
    insideIframe: boolean
    scrollX: number
    scrollY: number
  }
  canZoom: boolean
  eventsPreventList: { [key: string]: boolean } = {}
  googleMapsScript: HTMLScriptElement

  fitOnDataLoadDone: boolean
  fitDelta: number
  lastTimeFitWas: number
  userLocation: Location
  userLocationMarker: Marker
  setMarkersByField: boolean
  locationField: SchemaField
  popoverShowingFor: MapObject

  svgFileLastChanged: number

  changedFields: any
  changedSearch: any

  /* This property comes from v5 without any changes */
  $gauge: any

  /**
   * AfterLoad event can be blocked by:
   * 1. Loading Google API JS files
   * 2. Loading filters controller
   * When a blocker is introduced, it increases the number of afterLoadBlockers
   * when mapsvg.finalizeMapLoading is called, it checks if afterLoadBlockers = 0
   */
  afterLoadBlockers: number
  loaded: boolean
  svgFilePromise: Promise<void | Response>
  svgFileLoaded: boolean = false
  svgFileLoadedPromise: Promise<boolean>
  svgFileLoadedResolve: (value: boolean | PromiseLike<boolean>) => void
  mapDataPromise: Promise<void | Response>
  mapDataLoaded: boolean = false
  extras: {
    // Map options can be prefetched on start from an API endpoint
    prefetch?: boolean
    [key: string]: any
  }

  private debouncedShowTooltip: (object: Marker | Region | MarkerCluster) => void

  /**
   * Initializes a new instance of the Map class.
   *
   * @param containerId The ID of the HTML element that will contain the map.
   * @param mapParams An object containing map parameters such as ID, options, SVG file last changed timestamp, and version.
   * @param params Additional parameters for the map, including options for prefetching and backend mode.
   */
  constructor(
    containerId: string,
    mapParams: {
      id?: number
      options?: MapOptions
      svgFileLastChanged?: number | string
      version?: string
    },
    params: {
      // Map options can be prefetched on start from an API endpoint
      prefetch?: boolean
      inBackend?: boolean
      [key: string]: any
    } = {
      prefetch: true,
      inBackend: false,
    },
  ) {
    if (params) {
      this.extras = params
      this.inBackend = params.inBackend ?? false
    } else {
      this.extras = {}
    }
    if (!mapParams) {
      throw new Error(
        "To create a new map, you need to provide either SVG file URL or map ID and API url.",
      )
    }

    this.containerId = containerId
    this.id = mapParams.id
    this.svgFileLastChanged =
      typeof mapParams.svgFileLastChanged === "number"
        ? mapParams.svgFileLastChanged
        : Number(mapParams.svgFileLastChanged)
    this.events = new Events({
      context: this,
      contextName: "map",
      map: this,
    })
    this.options = <MapOptions>deepMerge({}, DefaultOptions, mapParams.options)

    this.svgFileLoadedPromise = new Promise<boolean>((resolve, reject) => {
      this.svgFileLoadedResolve = resolve
    })

    this.setLoadingText(mapParams.options ? mapParams.options.loadingText || "" : "")
    this.addLoadingMessage()
    this.showLoadingMessage()

    this.middlewares = new MiddlewareList()

    this.init()
  }

  loadSvgFile(svgFileUrl, svgFileLastChanged = 1): Promise<void | Response> {
    this.svgFilePromise = fetch(svgFileUrl + "?version=" + (svgFileLastChanged || ""))
      .then(async (response) => {
        if (!response.ok) {
          if (response.status === 404) {
            const msg =
              "MapSVG couldn't load SVG file. Please check if the file exists: " +
              this.options.source +
              ". If the file exists, please contact support (this message is showing up only in wp-admin area)."
            throw new Error(msg)
          } else if (response.status >= 500) {
            throw new Error(`MapSVG: SVG file loading error! status: ${response.status}`)
          } else {
            throw new Error(`MapSVG: SVG file loading error! status: ${response.status}`)
          }
        }
        return (await response.text()).trim()
      })
      .then(async (svgFileString) => {
        const parser = new DOMParser()
        const svgData = parser.parseFromString(svgFileString, "image/svg+xml")

        setTimeout(() => {
          this.renderSvgData(svgData)
          this.svgFileLoaded = true
          this.reloadRegions()

          // The initial map loading process is finished, remove the first afterLoad blocker
          this.decrementAfterloadBlockers()

          this.svgFileLoadedResolve(true)
        }, 500)
      })
      .catch((error) => {
        // Handle network or HTTP error
        if (this.inBackend) {
          $.growl.error({ message: error, duration: 60000 })
        } else {
          console.error("MapSVG couldn't load SVG file")
        }
      })
    return this.svgFilePromise
  }

  /**
   * Initialization. Map rendering happens here. Gets called on map creation.
   * @param {MapOptions} options - Map options
   * @param {HTMLElement} elem
   * @returns {*}
   * @private
   */
  init(): MapSVGMap {
    mapsvgCore.addInstance(this)

    // Set 3 blockers initially:
    // 1. SVG loading, initMap
    this.afterLoadBlockers = 2
    this.loaded = false

    // 2. Regions loading
    // 3. Objects loading
    this.afterLoadBlockers += this.extras.prefetch ? 2 : 0

    // this.setEvents(this.options.events)
    const { filters, ...restOptions } = this.options
    // this.update(restOptions)

    this.setEventHandlers()

    if (!this.id || this.extras.prefetch === false) {
      // Set beforeLoad event and then trigger it, before applying the options
      this.events.trigger(MapEventType.BEFORE_INIT, { options: this.options })

      this.createContainers()
      const svgFileUrl =
        isRemoteUrl(this.options.source) && !sameDomain(this.options.source, mapsvgCore.routes.home)
          ? mapsvgCore.routes.api + `maps/${this.id}/svg`
          : this.urlToRelativePath(this.options.source)
      this.loadSvgFile(svgFileUrl, this.svgFileLastChanged).then(async () => {
        await this.svgFileLoadedPromise
        this.loadMockRegions()
      })
      this.updateOutdatedOptions({
        version: this.version || "1.0.0",
        options: this.options,
      })
        .then(({ version, options }) => {
          this.version = version
          this.initMap(options)
        })
        .catch((error) => {
          console.error("Failed to update options:", error)
          // Handle the error, maybe with a fallback or notification
        })
    } else {
      const dataTypes = []
      const withData =
        this.options?.database?.loadOnStart === true || this.inBackend
          ? "objects,regions"
          : "regions"
      // If map options are not specified then fetch them from the server
      this.mapDataPromise = fetch(mapsvgCore.routes.api + `maps/${this.id}?withData=${withData}`)
        .then(async (response) => {
          if (!response.ok) {
            // Handle HTTP error
            throw new Error(`HTTP error! status: ${response.status}`)
          }
          return await response.json()
        })
        .then(async (res) => {
          const mapData = res.map
          this.version = mapData.version
          this.svgFileLastChanged = mapData.svgFileLastChanged

          deepMerge(this.options, mapData.options)

          // Convert options.regions to an object if it's an array
          if (Array.isArray(this.options.regions)) {
            const regionsObject = {} as RegionOptionsCollection
            this.options.regions.forEach((region, index) => {
              if (typeof index === "string") {
                regionsObject[index] = region
              }
            })
            this.options.regions = regionsObject
          }

          if (Array.isArray(this.options.regionStatuses)) {
            const regionStatusesObject = {} as Record<
              string,
              { label: string; value: string; disabled: boolean; color: string }
            >
            this.options.regionStatuses.forEach((status, index) => {
              regionStatusesObject[index] = status
            })
            this.options.regionStatuses = regionStatusesObject
          }

          // Load SVG File
          this.setRegionPrefix(this.options.regionPrefix || "")
          this.setLoadingText(this.options.loadingText || "")
          // Set beforeLoad event and then trigger it, before applying the options
          if (
            this.events &&
            this.options &&
            this.options.events &&
            this.options.events.beforeInit
          ) {
            this.setEvents({ beforeInit: this.options.events.beforeInit })
          }
          // Set beforeLoad event and then trigger it, before applying the options
          this.events.trigger(MapEventType.BEFORE_INIT, { options: this.options })
          this.createContainers()
          const svgFileUrl =
            isRemoteUrl(this.options.source) &&
            !sameDomain(this.options.source, mapsvgCore.routes.home)
              ? // proxy SVG file loading if it's on another domain
                mapsvgCore.routes.api + `maps/${this.id}/svg`
              : // otherwise load the file directly
                this.urlToRelativePath(this.options.source)
          this.loadSvgFile(svgFileUrl, this.svgFileLastChanged)

          // Need to await loading viewBox and geoViewBox from SVG file, to be able to add locations from repo.loadDataFromResponse()
          await this.initMap(this.options)

          // Load Regions
          if (!res.regions && this.id && this.options.database.regionsTableName) {
            this.regionsRepository.events.once(RepositoryEvent.AFTER_LOAD, () => {
              this.decrementAfterloadBlockers()
            })
          } else {
            if (res.regions) {
              this.regionsRepository.events.once(RepositoryEvent.AFTER_LOAD, () => {
                this.decrementAfterloadBlockers()
              })
              const request: MapsvgRequest = {
                repository: this.regionsRepository,
                url: this.regionsRepository.getApiEndpoint("index").url || "",
                action: "index",
                method: "GET",
              }
              const { schema: regSchema, ...regionsRest } = res.regions
              this.regionsRepository.schema.update(regSchema)
              this.regionsRepository.loadDataFromResponse(regionsRest, {
                request,
                response: { data: regionsRest },
              })
            } else {
              this.decrementAfterloadBlockers()
            }
          }

          // Load objects
          if (!res.objects && this.id && mapData.options.database.objectsTableName) {
            if (mapData.options.database.loadOnStart) {
              this.objectsRepository.events.once(RepositoryEvent.AFTER_LOAD, () => {
                this.decrementAfterloadBlockers()
              })
            } else {
              this.decrementAfterloadBlockers()
            }
          } else {
            if (res.objects && (this.inBackend || mapData.options.database.loadOnStart)) {
              this.objectsRepository.events.once(RepositoryEvent.AFTER_LOAD, () => {
                this.decrementAfterloadBlockers()
              })
              const request: MapsvgRequest = {
                repository: this.objectsRepository,
                url: this.objectsRepository.getApiEndpoint("index").url || "",
                action: "index",
                method: "GET",
              }
              const { schema: objectsSchema, ...objectsRest } = res.objects
              this.objectsRepository.schema.update(objectsSchema)
              if (this.objectsRepository.schema.type === "api") {
                this.objectsRepository.find()
              } else {
                this.objectsRepository.loadDataFromResponse(objectsRest, {
                  request,
                  response: { data: objectsRest },
                })
              }
            } else {
              this.decrementAfterloadBlockers()
            }
          }
        })
        .catch((error) => {
          // Handle network or HTTP error
          console.error("Fetch error:", error)
        })
    }

    // Initialize the debounced function
    this.debouncedShowTooltip = debounce((object: Marker | Region | MarkerCluster) => {
      this.showTooltip(object)
    }, 20)

    return this
  }

  async initMap(_options: MapOptions, data?: any) {
    const { data_regions, data_objects, events, middlewares, ...restOptions } = _options
    let options = restOptions

    if (events) {
      this.setEvents(events, true)
    }
    const { mapLoad, ...restMiddlewares } = middlewares
    if (mapLoad) {
      //@ts-expect-error
      this.setMiddlewares({ mapLoad }, true)
    }

    options = this.middlewares.run(MiddlewareType.MAP_LOAD, [options, { map: this }])

    this.regionsRepository = useRepository(options.database.schemas.regions, this)
    this.regionsRepository.init()
    // Turn off pagination for Regions:
    this.regionsRepository.query.update({ perpage: 0 })

    this.objectsRepository = useRepository(options.database.schemas.objects, this)
    this.objectsRepository.init()
    this.objectsRepository.query.update({
      perpage: options.database.pagination.on ? options.database.pagination.perpage : 0,
    })

    //@ts-expect-error
    this.setMiddlewares(restMiddlewares, true)
    this.setRepositoryEvents()

    this.mapDataLoaded = true

    await this.svgFileLoadedPromise

    options = <MapOptions>deepMerge({}, this.svgDefault, options)

    const { viewBox, width, height, responsive, ...restNewOptions } = options

    // Initial Size

    const size = {
      width: Number(width) || this.viewBox.width || this.svgDefault.width,
      height: Number(height) || this.viewBox.height || this.svgDefault.height,
      responsive,
    }
    this.setSvgTagViewBox(this.svgDefault.viewBox)
    this.setSize(size.width, size.height, size.responsive)

    const initialViewBox =
      viewBox && viewBox.length > 0 ? new ViewBox(viewBox) : this.svgDefault.viewBox
    this.setInitialViewBox(initialViewBox)

    this.setWhRatio(initialViewBox.width / initialViewBox.height)
    this.setViewBox(initialViewBox)
    this.initialZoomLevel = this.zoomLevel

    // Set converter
    this.converter = new Converter(this)
    if (this.geoViewBox) {
      this.converter.setGeoViewBox(this.geoViewBox)
    }

    // Apply map options
    this.update(restNewOptions)

    // 4. Google maps API blocker?
    if (this.options.googleMaps.on) {
      this.afterLoadBlockers++
    }

    // 5. Filters may block loading too because they load remote HTML templates
    if (this.filtersShouldBeShown()) {
      this.afterLoadBlockers++
    }

    

    if (
      mapsvgCore.googleMaps.apiKey &&
      (this.inBackend ||
        this.options.googleMaps.on ||
        (this.options.filters.on &&
          this.options.filtersSchema &&
          this.options.filtersSchema.find((f) => f.type === "distance")))
    ) {
      this.loadGoogleMapsAPI(
        () => {
          return 1
        },
        () => {
          return 1
        },
      )
    }

    if (this.options.colors && this.options.colors.background) {
      this.containers.map.style.background = this.options.colors.background
    }
    this.decrementAfterloadBlockers()
  }

  loadMockRegions() {
    const request: MapsvgRequest = {
      repository: this.regionsRepository,
      url: this.regionsRepository.getApiEndpoint("index").url || "",
      action: "index",
      method: "GET",
    }

    const data = {
      items: this.regions.map((region) => ({ id: region.id, title: region.title })),
      schema: {
        fields: [
          { type: "id", name: "id" },
          { type: "text", name: "title" },
        ],
      },
      hasMore: false,
      page: 1,
      perpage: 0,
    }

    this.regionsRepository.loadDataFromResponse(data, {
      request,
      response: { data },
    })
  }

  /**
   * Return relative path part of the URL
   * @param {string} url
   * @returns string
   * @private
   */
  urlToRelativePath(url: string): string {
    if (url.indexOf("//") === 0) url = url.replace(/^\/\/[^/]+/, "").replace("//", "/")
    else url = url.replace(/^.*:\/\/[^/]+/, "").replace("//", "/")
    return url
  }

  /**
   * Sets visiblity switches for groups of SVG objects
   * @private
   */
  setGroups(groups?: GroupOptionsInterface[]): void {
    if (groups) {
      this.groups.clear()
      // Empty the old array
      this.groups.push(...groups)
      this.options.groups = this.groups
    }

    this.groups.forEach((g: GroupOptionsInterface) => {
      g.objects &&
        g.objects.length &&
        g.objects.forEach((obj) => {
          const elems = this.containers.svg.querySelector("#" + obj.value)
          if (elems) {
            elems.classList.toggle("mapsvg-hidden", !g.visible)
          }
        })
    })
  }

  /**
   * Returns the list of options for visibility settings in admin.js
   */
  getGroupSelectOptions(): any[] {
    const _this = this
    let id
    const optionGroups = []
    const options = []
    const options2 = []

    $(_this.containers.svg)
      .find("g")
      .each(function (index) {
        const id = $(this)[0].getAttribute("id")
        if (id) {
          const title = $(this)[0].getAttribute("title")
          options.push({ label: title || id, value: id })
        }
      })
    optionGroups.push({ title: "SVG Layers / Groups", options: options })

    $(_this.containers.svg)
      .find("path,ellipse,circle,polyline,polygon,rectangle,img,text")
      .each(function (index) {
        const id = $(this)[0].getAttribute("id")
        if (id) {
          const title = $(this)[0].getAttribute("title")
          options2.push({ label: title || id, value: id })
        }
      })
    optionGroups.push({ title: "Other SVG objects", options: options2 })

    return optionGroups
  }

  /**
   * Sets visibility switches
   * @param {object} options - Options
   * @private
   */
  setLayersControl(options?: LayersControlOptionsInterface): void {
    const _this = this

    if (options) deepMerge(this.options.layersControl, options)

    if (this.options.layersControl.on) {
      if (!this.containers.layersControl) {
        this.containers.layersControl = document.createElement("div")
        this.containers.layersControl.classList.add("mapsvg-layers-control")

        this.containers.layersControlLabel = document.createElement("div")
        this.containers.layersControlLabel.classList.add("mapsvg-layers-label")
        this.containers.layersControl.appendChild(this.containers.layersControlLabel)

        const layersControlWrap = document.createElement("div")
        layersControlWrap.classList.add("mapsvg-layers-list-wrap")
        this.containers.layersControl.appendChild(layersControlWrap)

        this.containers.layersControlListNano = document.createElement("div")
        this.containers.layersControlListNano.classList.add("nano")
        layersControlWrap.appendChild(this.containers.layersControlListNano)

        this.containers.layersControlList = document.createElement("div")
        this.containers.layersControlList.classList.add("mapsvg-layers-list")
        this.containers.layersControlList.classList.add("nano-content")
        this.containers.layersControlListNano.appendChild(this.containers.layersControlList)

        this.containers.mapContainer.appendChild(this.containers.layersControl)
      }
      this.containers.layersControl.style.display = "block"
      this.containers.layersControlLabel.innerHTML = this.options.layersControl.label

      this.containers.layersControlLabel.style.display = "block"
      this.containers.layersControlList.innerHTML = ""
      while (this.containers.layersControlList.firstChild) {
        this.containers.layersControlList.removeChild(this.containers.layersControlList.firstChild)
      }
      this.containers.layersControl.classList.remove(
        "mapsvg-top-left",
        "mapsvg-top-right",
        "mapsvg-bottom-left",
        "mapsvg-bottom-right",
      )
      this.containers.layersControl.classList.add("mapsvg-" + this.options.layersControl.position)
      if (
        this.options.menu.on &&
        !this.options.menu.customContainer &&
        this.options.layersControl.position.indexOf("left") !== -1
      ) {
        this.containers.layersControl.style.left = this.options.menu.width
      }

      this.containers.layersControl.style.maxHeight = this.options.layersControl.maxHeight

      this.options.groups.forEach((g) => {
        const item = document.createElement("div")
        item.classList.add("mapsvg-layers-item")
        item.setAttribute("data-group-id", g.id)
        item.innerHTML =
          '<input type="checkbox" class="ios8-switch ios8-switch-sm" ' +
          (g.visible ? "checked" : "") +
          " /><label>" +
          g.title +
          "</label>"
        this.containers.layersControlList.appendChild(item)
      })

      // @ts-ignore
      $(this.containers.layersControlListNano).nanoScroller({
        preventPageScrolling: true,
        iOSNativeScrolling: true,
      })

      $(this.containers.layersControl).off()
      $(this.containers.layersControl).on("click", ".mapsvg-layers-item", function () {
        const id = $(this).data("group-id")
        const input = $(this).find("input")
        input.prop("checked", !input.prop("checked"))
        _this.groups.forEach(function (g) {
          if (g.id === id) g.visible = !g.visible
        })
        _this.setGroups()
      })
      $(this.containers.layersControlLabel).off()
      $(this.containers.layersControlLabel).on("click", () => {
        $(_this.containers.layersControl).toggleClass("closed")
      })

      $(this.containers.layersControl).toggleClass("closed", !this.options.layersControl.expanded)
    } else {
      if (this.containers.layersControl) {
        this.containers.layersControl.style.display = "none"
      }
    }
  }

  loadDataObjects(params?: any): JQueryDeferred<any> {
    return this.objectsRepository.find(params)
  }

  /**
   * Loads items from a source defined in settings  to the directory. The source can be "Regions" or "Database".
   */
  loadItemsToDirectory() {
    let items = []
    const _this = this

    if (!this.controllers.directory?.repository || !this.controllers.directory?.repository.loaded)
      return false

    // If "categories" option is enabled, then:
    if (
      this.options.menu.categories &&
      this.options.menu.categories.on &&
      this.options.menu.categories.groupBy
    ) {
      // Get category field to group objects by
      const categoryField = this.options.menu.categories.groupBy
      // Get the list of categories
      if (
        this.controllers.directory?.repository.getSchema().getField(categoryField) === undefined ||
        this.controllers.directory?.repository.getSchema().getField(categoryField).options ===
          undefined
      ) {
        return false
      }
      const categories = this.controllers.directory?.repository
        .getSchema()
        .getField(categoryField).options
      // Get the list of items for every category
      categories.forEach((category) => {
        let dbItems = this.controllers.directory?.repository
          .getLoaded()
          .map((item) => item.getData())
        dbItems = this.directoryFilterOut(dbItems)
        const itemArr: Array<any> = []
        dbItems.forEach((item) => {
          itemArr.push(item)
        })
        const catItems = itemArr.filter(function (object) {
          if (categoryField === "regions") {
            const objectRegions =
              typeof object[categoryField] !== "undefined" && object[categoryField].length
                ? object[categoryField]
                : []
            const objectRegionIDs = objectRegions.map(function (region) {
              return region.id
            })
            return objectRegionIDs.indexOf(category.id) !== -1
          } else {
            return object[categoryField] == category.value
          }
        })
        category.counter = catItems.length
        if (categoryField === "regions") {
          category.label = category.title
          category.value = category.id
        }

        catItems.sort(function (a, b) {
          const field = _this.options.menu.sortBy
          const direction = _this.options.menu.sortDirection === "asc" ? 1 : -1

          if (a[field] === b[field]) {
            return 0
          } else {
            return a[field] > b[field] ? direction : -direction
          }
        })

        items.push({ ...category, items: catItems })
      })
      // Filter out empty categories, if needed:
      if (this.options.menu.categories.hideEmpty) {
        items = items.filter(function (item) {
          return item.counter > 0
        })
      }

      this.controllers.directory?.setState({ categories: items, items: null })
    } else {
      // If categories are not enabled then just get the list of DB items:
      if (this.options.menu.source === "regions") {
        items = this.controllers.directory?.repository
          .getLoaded()
          .map((r) => {
            const data = r.getData()
            const mapRegion = this.getRegion(data.id)
            if (mapRegion) {
              data.objects = this.getRegion(data.id).objects
              return data
            } else {
              return null
            }
          })
          .filter((region) => region !== null)
        items = this.directoryFilterOut(items)
      } else {
        items = this.controllers.directory?.repository.getLoaded().map((r) => r.getData())
      }

      this.controllers.directory?.setState({ categories: null, items })
    }
  }

  /**
   * Filter out directory items
   */
  directoryFilterOut(items) {
    const _this = this

    if (this.controllers.directory?.repository.schema.objectNameSingular.indexOf("region") !== -1) {
      const f: { field: string; val: number | string } = {
        field: "",
        val: "",
      }
      if (this.options.menu.filterout.field) {
        f.field = this.options.menu.filterout.field
        f.val = this.options.menu.filterout.val
      }

      items = items.filter(function (item) {
        let ok = true
        const status = _this.options.regionStatuses
        if (status[item.status]) {
          ok = !status[item.status].disabled
        }

        if (ok && f.field) {
          ok = item[f.field] != f.val
        }
        return ok
      })
    }

    return items
  }

  loadDirectory(): void {
    // If "Load DB on start" is off then
    // don't load directory to avoid showing 'no records found' message
    if (
      !this.inBackend &&
      this.options.menu.source === "database" &&
      !this.objectsRepository.loaded
    ) {
      return
    }
    if (this.options.menu.on) {
      this.controllers.directory && this.loadItemsToDirectory()
    }
    this.setPagination()
  }

  /**
   * Adds pagination controls to the map and/or directory
   */
  setPagination() {
    this.containers.pagerMap && $(this.containers.pagerMap).empty().remove()
    this.containers.pagerDir && $(this.containers.pagerDir).empty().remove()

    const paginationMap = ["map", "both"].includes(this.options.database.pagination.showIn)
    const paginationDir = ["directory", "both"].includes(this.options.database.pagination.showIn)

    const paginationEnabledInGeneral =
      this.options.database.pagination.on && this.options.database.pagination.perpage !== 0

    const paginationInDirectoryEnabled =
      paginationDir &&
      paginationEnabledInGeneral &&
      this.controllers.directory &&
      this.options.menu.on

    const paginationForDirectory = paginationInDirectoryEnabled ? this.getPagination() : null
    if (this.controllers.directory) {
      this.controllers.directory?.setPagination(paginationForDirectory)
    }

    const paginationInMapEnabled = paginationMap && paginationEnabledInGeneral
    this.containers.pagerMap = paginationInMapEnabled ? this.getPagination() : null
    if (this.containers.pagerMap) {
      this.containers.map.appendChild(this.containers.pagerMap)
    }
  }
  /**
   * Generates HTML with pagination buttons and attaches callback event on button click
   * @param {function} callback - Callback function that should be called on click on a next/prev page button
   */
  getPagination(callback?: () => void): HTMLElement {
    const _this = this

    // pager && (pager.empty().remove());
    const pager = $(
      '<nav class="mapsvg-pagination"><ul class="pager"><!--<li class="mapsvg-first"><a href="#">First</a></li>--><li class="mapsvg-prev"><a href="#">&larr; ' +
        _this.options.database.pagination.prev +
        " " +
        _this.options.database.pagination.perpage +
        '</a></li><li class="mapsvg-next"><a href="#">' +
        _this.options.database.pagination.next +
        " " +
        _this.options.database.pagination.perpage +
        ' &rarr;</a></li><!--<li class="mapsvg-last"><a href="#">Last</a></li>--></ul></nav>',
    )

    if (!this.objectsRepository) {
      return pager[0]
    }

    if (this.objectsRepository.onFirstPage() && this.objectsRepository.onLastPage()) {
      pager.hide()
    } else {
      pager.find(".mapsvg-prev").removeClass("disabled")
      pager.find(".mapsvg-first").removeClass("disabled")
      pager.find(".mapsvg-last").removeClass("disabled")
      pager.find(".mapsvg-next").removeClass("disabled")

      this.objectsRepository.onLastPage() &&
        pager.find(".mapsvg-next").addClass("disabled") &&
        pager.find(".mapsvg-last").addClass("disabled")

      this.objectsRepository.onFirstPage() &&
        pager.find(".mapsvg-prev").addClass("disabled") &&
        pager.find(".mapsvg-first").addClass("disabled")
    }

    pager
      .on("click", ".mapsvg-next:not(.disabled)", (e) => {
        e.preventDefault()
        if (this.objectsRepository.onLastPage()) return
        const query = new Query({ page: this.objectsRepository.query.page + 1 })
        this.objectsRepository.find(query).done(function () {
          callback && callback()
        })
      })
      .on("click", ".mapsvg-prev:not(.disabled)", function (e) {
        e.preventDefault()
        if (_this.objectsRepository.onFirstPage()) return
        const query = new Query({ page: _this.objectsRepository.query.page - 1 })
        _this.objectsRepository.find(query).done(function () {
          callback && callback()
        })
      })
      .on("click", ".mapsvg-first:not(.disabled)", function (e) {
        e.preventDefault()
        if (_this.objectsRepository.onFirstPage()) return
        const query = new Query({ page: 1 })
        _this.objectsRepository.find(query).done(function () {
          callback && callback()
        })
      })
      .on("click", ".mapsvg-last:not(.disabled)", function (e) {
        e.preventDefault()
        if (_this.objectsRepository.onLastPage()) return
        const query = new Query({ lastpage: true })
        _this.objectsRepository.find(query).done(function () {
          callback && callback()
        })
      })

    return pager[0]
  }

  /**
   * Delete all markers
   */
  deleteMarkers() {
    while (this.markers.length) {
      this.markerDelete(this.markers[0])
    }
  }

  /**
   * Delete all clusters from the map
   */
  deleteClusters(): void {
    if (this.markersClusters) {
      this.markersClusters.forEach(function (markerCluster) {
        markerCluster.destroy()
      })
      this.markersClusters.clear()
    }
    this.clustersFromWorker = []
  }

  /**
   * Add locations by provided set of objects, containing location field with lat/lng coordinates
   * @private
   */
  addLocation(object: Model): void {
    let locationField = this.objectsRepository.getSchema().getFieldByType("location")
    if (!locationField) {
      return
    }
    locationField = locationField.name

    if (locationField) {
      if (this.firstDataLoad) {
        this.setMarkerImagesDependency()
      }

      const location: Location = object[locationField]

      if (location.img && (location.geoPoint || location.svgPoint)) {
        const marker = new Marker({
          location,
          object,
          mapsvg: this,
        })
        this.markerAdd(marker)
      }
    }
  }
  /**
   * Stores a set of markers/clusters for a certain zoom level for later use.
   * This method gets called by a worker thread that calculates markers/clusters for all zoom levels.
   * @param zoomLevel
   * @param clusters
   * @private
   * @ignore
   */
  addClustersFromWorker(clusters): void {
    this.clustersFromWorker = []

    for (const cell in clusters) {
      const markers = clusters[cell].markers.map((marker) => {
        const object = this.objectsRepository.objects.findById(marker.id)
        if (object && object["location"]) return object["location"].marker
      })
      this.clustersFromWorker.push(
        new MarkerCluster(
          {
            markers: markers,
            svgPoint: new SVGPoint(clusters[cell].x, clusters[cell].y),
            cellX: clusters[cell].cellX,
            cellY: clusters[cell].cellY,
          },
          this,
        ),
      )
    }

    this.clusterizeMarkers()
  }
  /**
   * Starts clusterizer worker in a separate thread
   * @private
   */
  async startClusterizer(): Promise<void | boolean> {
    this.clustersFromWorker = []

    if (!this.objectsRepository || this.objectsRepository.getLoaded().length === 0) {
      return
    }
    const locationField = this.objectsRepository.getSchema().getFieldByType("location")
    if (!locationField) {
      return false
    }

    if (!this.clusterizerWorker) {
      this.clusterizerWorker = new Worker(
        mapsvgCore.routes.root + "js/mapsvg/Core/ClusteringWorker.js",
      )
      // Receive messages from postMessage() calls in the Worker
      this.clusterizerWorker.onmessage = (evt) => {
        if (evt.data.clusters) {
          this.addClustersFromWorker(evt.data.clusters)
        }
      }
      this.events.on(MapEventType.ZOOM, () => {
        if (this.clusterizerWorker && this.options.clustering.on) {
          this.clusterizerWorker.postMessage({
            message: "zoom",
            zoomLevel: this.zoomLevel,
            viewBox: this.viewBox,
          })
        }
      })
      this.events.on(MapEventType.RESIZE, (event) => {
        if (this.clusterizerWorker && this.options.clustering.on) {
          this.clusterizerWorker.postMessage({
            message: "resize",
            viewBox: event.data.viewBox,
            mapWidth: event.data.mapWidth,
            mapHeight: event.data.mapHeight,
          })
        }
      })
    }

    // Pass data to the WebWorker
    const objectsData = []
    this.objectsRepository
      .getLoaded()
      .filter((object) => {
        return object["location"] && object["location"].marker
      })
      .forEach((object) => {
        const { x, y } = object["location"].marker.svgPoint
        objectsData.push({
          id: object.getData().id,
          x,
          y,
        })
      })
    this.clusterizerWorker.postMessage({
      objects: objectsData,
      cellSize: 50,
      mapWidth: this.containers.map.clientWidth,
      zoomLevel: this.zoomLevel,
      zoomDelta: this.zoomDelta,
      svgViewBox: this.svgDefault.viewBox,
      viewBox: this.viewBox,
    })
  }
  /**
   * Starts clustering markers on the map
   * @property {boolean} skipFitMarkers - Don't do "fit markers" action. Used to prevent fitting markers
   * on changinh zoom level.
   * @private
   */
  clusterizeMarkers(skipFitMarkers?: boolean): void {
    $(this.layers.markers)
      .children()
      .each((i, obj) => {
        $(obj).detach()
      })
    this.markers.clear()
    this.markersClusters.clear()

    $(this.containers.map).addClass("no-transitions-markers")

    if (this.clustersFromWorker.length > 0) {
      this.clustersFromWorker.forEach((cluster) => {
        // Don't clusterize on google maps with zoom level >= 17
        if (this.googleMaps.map && this.googleMaps.map.getZoom() >= 17) {
          this.markerAdd(cluster.markers[0])
        } else {
          if (cluster.markers.length > 1) {
            this.markersClusterAdd(cluster)
          } else {
            this.markerAdd(cluster.markers[0])
          }
        }
      })
    }

    if (this.editingMarker) {
      this.markerAdd(this.editingMarker)
    }

    if (!skipFitMarkers) {
      this.mayBeFitMarkers()
    }

    if (this.options.labelsMarkers.on) {
      this.setLabelsMarkers()
    }

    $(this.containers.map).removeClass("no-transitions-markers")
  }
  /**
   * Returns URL of the mapsvg.css file
   * @returns {string} URL
   * @private
   */
  getCssUrl(): string {
    return mapsvgCore.routes.root + "css/mapsvg.css"
  }
  /**
   * Checks if the map is geo-calibrated
   * @returns {boolean}
   */
  isGeo(): boolean {
    return this.mapIsGeo
  }

  /**
   * Returns map options.
   * @param {bool} forTemplate - If options should be formatted for use in a template
   * @param {bool} forWeb - If options should be formatted for use in the web
   * @returns {object}
   */
  getOptions(forTemplate: boolean, forWeb: boolean) {
    const options = <MapOptions>deepMerge({}, this.options)

    function clearEmpties(o) {
      for (const k in o) {
        if (!o[k] || typeof o[k] !== "object") {
          continue
        }

        clearEmpties(o[k])
        if (Object.keys(o[k]).length === 0) {
          delete o[k]
        }
      }
    }

    clearEmpties(options.regions)

    deepMerge(options, this.optionsDelta)

    options.viewBox = this._viewBox.toArray()
    if (this.filtersSchema) {
      options.filtersSchema = this.filtersSchema.getFields()
      if (options.filtersSchema.length > 0) {
        options.filtersSchema.forEach((field) => {
          if (field.type === "distance") {
            field.value = ""
          }
        })
      }
    }

    // @ts-ignore - old options field from MapSVG versions <5.x
    delete options.markers

    if (forTemplate) {
      // @ts-ignore - may this needs to be removed?
      options.svgFilename = options.source.split("/").pop()
      // @ts-ignore - may this needs to be removed?
      options.svgFiles = mapsvgCore.svgFiles
    }

    if (forWeb)
      $.each(options, (key, val) => {
        if (JSON.stringify(val) == JSON.stringify(this.defaults[key])) delete options[key]
      })

    return options
  }

  /**
   * Restore delta options, used by MapSVG-Admin
   * @private
   * @ignore
   */
  restoreDeltaOptions(): void {
    this.update(this.optionsDelta)
    this.optionsDelta = {}
  }

  setMiddlewares(
    functions: { [K in MiddlewareType]: string | MiddlewareHandlers[K] },
    unique = false,
  ): void {
    let compiledFunction

    for (const eventName in functions) {
      if (typeof functions[eventName] === "string") {
        compiledFunction =
          functions[eventName] != "" ? functionFromString(<string>functions[eventName]) : null
        if (
          !compiledFunction ||
          compiledFunction.error ||
          compiledFunction instanceof TypeError ||
          compiledFunction instanceof SyntaxError
        ) {
          continue
        }
      } else if (typeof functions[eventName] === "function") {
        compiledFunction = functions[eventName]
      }

      // Set new middleware
      this.setMiddleware(eventName as MiddlewareType, compiledFunction, unique)
    }

    deepMerge(this.options.middlewares, functions)
  }

  setMiddleware<T extends MiddlewareType>(name: T, handler: MiddlewareHandlers[T], unique = false) {
    this.middlewares.add(name, handler, unique)
    switch (name) {
      case MiddlewareType.REQUEST:
      case MiddlewareType.RESPONSE: {
        if (this.objectsRepository) {
          this.objectsRepository.middlewares.add(name, handler, unique)
        }
        if (this.regionsRepository) {
          this.regionsRepository.middlewares.add(name, handler, unique)
        }
        break
      }
      default:
        null
    }
  }

  /**
   * Sets event handlers
   * @param {object} functions Object containing the event handlers: `{eventName: callback, eventName2: callback}`
   */
  setEvents(functions: { [key: string]: string }, unique?: boolean) {
    // TODO add namespaces for events as done in jQuery

    let compiledFunction

    for (const eventName in functions) {
      if (typeof functions[eventName] === "string") {
        compiledFunction =
          functions[eventName] != "" ? functionFromString(<string>functions[eventName]) : null
        if (
          !compiledFunction ||
          compiledFunction.error ||
          compiledFunction instanceof TypeError ||
          compiledFunction instanceof SyntaxError
        ) {
          continue
        }
      } else if (typeof functions[eventName] === "function") {
        compiledFunction = functions[eventName]
      }

      // Set new event
      this.events.on(eventName, compiledFunction, { unique: unique ?? false })

      if (eventName.indexOf("directory") !== -1) {
        const event = eventName.split(".")[0]

        if (this.controllers && this.controllers.directory) {
          this.controllers.directory?.events.off(event)
          this.controllers.directory?.events.on(event, compiledFunction)
        }
      }
    }

    deepMerge(this.options.events, functions)
  }
  /**
   * Sets map actions that should be performed on Region click. Marker click, etc.
   * @param options
   *
   * @example
   * mapsvg.setActions({
   *   "click.region": {
   *     showPopover: true,
   *     showDetails: false
   *   }
   * })
   */
  setActions(options): void {
    deepMerge(this.options.actions, options)
  }
  /**
   * Sets Details View options
   * @param {object} options - Options
   */
  setDetailsView(options?: { [key: string]: any }) {
    options = options || this.options.detailsView || {}
    deepMerge(this.options.detailsView, options)
  }
  /**
   * Sets mobile view options
   * @param options
   */
  setMobileView(options): void {
    deepMerge(this.options.mobileView, options)
  }

  /**
   * Attaches DB Objects to Regions
   * @param object
   */
  attachDataToRegions(object?: Model) {
    const regions = object.getRegionsForTable(this.options.database.regionsTableName)
    const regionIds = regions.map((region) => region.id)

    this.regions
      .filter((region) => regionIds.includes(region.id))
      .forEach((region) => {
        region.objects.clear()
      })

    this.objectsRepository.getLoaded().forEach((obj, index) => {
      const regions = obj.getRegionsForTable(this.options.database.regionsTableName)
      if (regions && regions.length) {
        regions.forEach((region) => {
          const r = this.getRegion(region.id)
          if (r) r.objects.push(obj)
        })
      }
    })
  }

  /**
   * Sets templates body
   * @param {object} templates - Key:value pairs of template names and HTML content
   */
  setTemplates(templates): void {
    this.templates = this.templates || {}
    for (const name in templates) {
      if (name !== undefined) {
        this.options.templates[name] = templates[name]
        if (name == "directoryItem" || name == "directoryCategoryItem") {
          Handlebars.unregisterPartial(name)
          try {
            Handlebars.registerPartial(name, templates[name])
          } catch (error) {
            Handlebars.registerPartial(name, "")
            console.error(error)
          }
        } else {
          try {
            this.options.templates[name] = templates[name]
            this.templates[name] = Handlebars.compile(templates[name], { strict: false })
          } catch (err) {
            console.error(err)
            this.templates[name] = Handlebars.compile("", { strict: false })
          }
        }

        if (
          this.inBackend &&
          (name == "directory" || name == "directoryCategoryItem" || name == "directoryItem") &&
          this.controllers &&
          this.controllers.directory
        ) {
          this.controllers.directory.templates.main = this.templates.directory
          this.controllers.directory?.redraw()
        }
      }
    }
  }
  /**
   * Updates map settings.
   * @param {object} options - Map options
   *
   * @example
   * mapsvg.update({
   *   popovers: {on: true},
   *   colors: {
   *     background: "red"
   *   }
   * });
   */
  update(options: Omit<MapOptions, "id">): void {
    if (!options) {
      return
    }
    const { width, height, responsive, ...rest } = options

    for (const key in rest) {
      if (key == "regions") {
        // TODO options.regions for different regions tables (add regionTableName into options)
        for (const id in options.regions) {
          const region = this.getRegion(id)
          region && region.update(options.regions[id])
          this.reloadRegionStyles(region)
          if (region) {
            this.options.regions[id] = { ...this.options.regions[id], ...options.regions[id] }
          }
          if (typeof options.regions[id].disabled !== "undefined") {
            this.deselectRegion(region)
            this.options.regions[id] = this.options.regions[id] || {}
            this.options.regions[id].disabled = region.disabled
          }
        }
      } else {
        const setter = "set" + ucfirst(key)
        if (typeof this[setter] == "function") this[setter](options[key])
        else {
          this.options[key] = options[key]
        }
      }
    }

    if (width || height) {
      const newSize = { width, height, responsive }
      const size = {
        width:
          typeof width !== "undefined"
            ? Number(width)
            : (this.options.width ?? this.svgDefault.width),
        height:
          typeof height !== "undefined"
            ? Number(height)
            : (this.options.height ?? this.svgDefault.height),
        responsive: typeof responsive !== "undefined" ? !!responsive : this.options.responsive,
      }
      this.setSize(size.width, size.height, size.responsive)
    }
  }

  getDirtyFields(): { [key: string]: any } {
    return this.getData()
  }
  clearDirtyFields(): void {
    this.dirtyFields = []
  }

  /**
   * Sets map title
   * @param {string} title - Map title
   * @private
   */
  setTitle(title: string): void {
    title && (this.options.title = title)
  }
  /**
   * Adds MapSVG add-ons
   * @param extension
   * @private
   */
  setExtension(extension): void {
    if (extension) {
      this.options.extension = extension
    } else {
      delete this.options.extension
    }
  }
  /**
   * Disables/enables redirection by link on click on a region or marker
   * when "Go to link..." feature is enabled
   * Used to prevent redirection in the map editor.
   * @param {boolean} on - true (enable redirection) of false (disable)
   */
  setDisableLinks(on: boolean): void {
    on = utils.numbers.parseBoolean(on)
    if (on) {
      $(this.containers.map).on("click.a.mapsvg", "a", function (e) {
        e.preventDefault()
      })
    } else {
      $(this.containers.map).off("click.a.mapsvg")
    }
    this.disableLinks = on
  }
  /**
   * Updates the loading text message displayed on the map
   * @param {string} message - The text to display while the map is loading
   */
  setLoadingText(message: string): void {
    this.options.loadingText = message
    if (this.containers.loading) {
      $(this.containers.loading).find(".mapsvg-loading-text")[0].innerHTML =
        this.options.loadingText
    }
  }
  /**
   * Enables or disables the lock aspect ratio feature, primarily used in the Map Editor.
   * @param {boolean} onoff - A boolean indicating whether to enable or disable the feature.
   * @private
   */
  setLockAspectRatio(onoff: boolean): void {
    this.options.lockAspectRatio = utils.numbers.parseBoolean(onoff)
  }
  /**
   * Sets callback for marker click. Used in Map Editor to show an object editing window.
   * @private
   */
  setMarkerEditHandler(handler: (location: Location) => void): void {
    this.markerEditHandler = handler
  }
  /**
   * Sets the field that is used for choropleth map
   * @param {string} field - field name
   */
  setChoroplethSourceField(field): void {
    this.options.choropleth.sourceField = field
    this.redrawChoropleth()
  }
  /**
   * Sets callback function that is called on click on a Region.
   * Used in the Map Editor on click on a Region in the "Edit regions" map mode.
   * @param {Function} handler
   */
  setRegionEditHandler(handler: () => void): void {
    this.regionEditHandler = handler
  }
  /**
   * Disables all Regions if "true" is passed.
   * @param {boolean} on
   */
  setDisableAll(on: boolean) {
    on = utils.numbers.parseBoolean(on)
    deepMerge(this.options, { disableAll: on })
    $(this.containers.map).toggleClass("mapsvg-disabled-regions", on)
  }

  /**
   * Returns color of the Region for choropleth map
   * @returns {string} color
   */
  getChoroplethColor(region: Region) {
    const o = this.options.choropleth
    let color = ""

    if (
      region.data &&
      (region.data[this.options.regionChoroplethField] ||
        region.data[this.options.regionChoroplethField] === 0)
    ) {
      const w =
        o.maxAdjusted === 0
          ? 0
          : (parseFloat(region.data[this.options.regionChoroplethField]) - o.min) / o.maxAdjusted

      const c = {
        r: Math.round(o.colors.diffRGB.r * w + o.colors.lowRGB.r),
        g: Math.round(o.colors.diffRGB.g * w + o.colors.lowRGB.g),
        b: Math.round(o.colors.diffRGB.b * w + o.colors.lowRGB.b),
        a: (o.colors.diffRGB.a * w + o.colors.lowRGB.a).toFixed(2),
      }
      color = "rgba(" + c.r + "," + c.g + "," + c.b + "," + c.a + ")"
    } else {
      color = o.colors.noData
    }

    return color
  }

  /**
   * Returns size of the choropleth bubble
   */
  getBubbleSize(region: Region) {
    let bubbleSize

    if (region.data && region.data[this.options.choropleth.sourceField]) {
      const maxBubbleSize = Number(this.options.choropleth.bubbleSize.max),
        minBubbleSize = Number(this.options.choropleth.bubbleSize.min),
        maxSourceFieldvalue = this.options.choropleth.coloring.gradient.values.max,
        minSourceFieldvalue = this.options.choropleth.coloring.gradient.values.min,
        sourceFieldvalue = parseFloat(region.data[this.options.choropleth.sourceField])

      bubbleSize =
        ((sourceFieldvalue - minSourceFieldvalue) / (maxSourceFieldvalue - minSourceFieldvalue)) *
          (maxBubbleSize - minBubbleSize) +
        minBubbleSize
    } else {
      bubbleSize = false
    }

    return bubbleSize
  }

  getRegionStylesByState(region: Region): null | RegionStylesByStatus {
    if (!region) {
      return null
    }

    const statusStyles: RegionStylesByStatus = {}

    // Capture region in the outer scope
    const capturedRegion = region

    // Get all available statuses
    const statusField = this.regionsRepository.getSchema().getFieldByType("status") || {
      options: [],
    }

    const statuses = statusField ? statusField.options.map((option) => option.value) : []
    statuses.push("undefined")

    for (const status of statuses) {
      const styleDefault: Region["style"] = {}
      const styleSelected: Region["style"] = {}
      const styleHover: Region["style"] = {}

      const currentStatus = statusField.options.find((option) => option.value === status)

      // Use capturedRegion instead of region
      if (currentStatus?.color) {
        styleDefault.fill = currentStatus.color
      } else if (this.options.choropleth.on) {
        styleDefault.fill = this.getChoroplethColor(capturedRegion)
      } else if (
        this.options.regions[capturedRegion.id] &&
        this.options.regions[capturedRegion.id].style &&
        this.options.regions[capturedRegion.id].style.fill
      ) {
        styleDefault.fill = this.options.regions[capturedRegion.id].style.fill
      } else if (this.options.colors.base) {
        styleDefault.fill = this.options.colors.base
      } else if (capturedRegion.styleSvg.fill != "none") {
        styleDefault.fill = capturedRegion.styleSvg.fill ?? this.options.colors.baseDefault
      } else {
        styleDefault.fill = "none"
      }

      // Set selected and hover styles
      if (isNumber(this.options.colors.selected)) {
        styleSelected.fill = tinycolor(styleDefault.fill)
          .lighten(parseFloat("" + this.options.colors.selected))
          .toRgbString()
      } else {
        styleSelected.fill = this.options.colors.selected as string
      }

      if (isNumber(this.options.colors.hover)) {
        styleHover.fill = tinycolor(styleDefault.fill)
          .lighten(parseFloat("" + this.options.colors.hover))
          .toRgbString()
      } else {
        styleHover.fill = this.options.colors.hover as string
      }

      // Set stroke
      if (capturedRegion.style.stroke != "none" && this.options.colors.stroke != undefined) {
        styleDefault.stroke = this.options.colors.stroke
      } else {
        styleDefault.stroke = capturedRegion.style.stroke ?? ""
      }

      styleSelected.stroke = styleDefault.stroke
      styleHover.stroke = styleDefault.stroke

      statusStyles[status] = {
        selected: styleSelected,
        hover: styleHover,
        default: styleDefault,
      }
    }

    return statusStyles
  }

  setRegions(options: RegionOptionsCollection) {
    // Update or add new regions based on incoming options
    for (const [id, regionOptions] of Object.entries(options)) {
      if (Array.isArray(regionOptions.style)) {
        console.error("MapSVG: wrong format of region style options.")
        continue
      }
      if (this.options.regions[id]) {
        // Update existing region in options
        Object.assign(this.options.regions[id], regionOptions)
      } else {
        // Add new region to options
        this.options.regions[id] = regionOptions
      }

      // Update region object if it exists
      const region = this.getRegion(id)
      if (region) {
        region.update(this.options.regions[id])
      }
    }
  }
  /**
   * @private
   */
  setRegionStatuses(
    statuses: Record<string, { label: string; value: string; disabled: boolean; color: string }>,
  ): void {
    this.options.regionStatuses = statuses
    const colors = {}
    for (const key in statuses) {
      const s = statuses[key]
      colors[s.value] = s.color.length ? s.color : null
    }
    this.setColors({ status: colors })
  }
  /**
   * @deprecated
   * @private
   */
  setColorsIgnore(val): void {
    this.options.colorsIgnore = utils.numbers.parseBoolean(val)
    this.reloadAllRegionStyles()
  }
  /**
   * Sets color settings (background, regions, containers, etc.)
   * @param {object} colors
   *
   * @example
   * mapsvg.setColors({
   *   background: "#EEEEEE",
   *   hover: "10",
   *   selected: "20",
   *   leftSidebar: "#FF2233",
   *   detailsView: "#FFFFFF"
   * });
   */
  setColors(colors?: { [key: string]: string | number | Record<string, unknown> }): void {
    for (const i in colors) {
      if (i === "status") {
        for (const s in colors[i] as object) {
          fixColorHash(colors[i][s])
        }
      } else {
        if (typeof colors[i] == "string") {
          fixColorHash(colors[i] as string)
        }
      }
    }

    deepMerge(this.options, { colors: colors })

    if (colors && colors.status) this.options.colors.status = colors.status

    if (this.options.colors.markers) {
      for (const z in this.options.colors.markers) {
        for (const x in this.options.colors.markers[z]) {
          this.options.colors.markers[z][x] = parseInt(this.options.colors.markers[z][x])
        }
      }
    }

    if (this.options.colors.background)
      $(this.containers.map).css({ background: this.options.colors.background })
    if (this.options.colors.hover) {
      // Typescript compiler thinks isNaN can't accept strings
      // @ts-ignore
      this.options.colors.hover = !isNaN(this.options.colors.hover)
        ? parseInt(this.options.colors.hover + "")
        : this.options.colors.hover
    }
    if (this.options.colors.selected) {
      // Typescript compiler thinks isNaN can't accept strings
      // @ts-ignore
      this.options.colors.selected = !isNaN(this.options.colors.selected)
        ? parseInt(this.options.colors.selected + "")
        : this.options.colors.selected
    }

    $(this.containers.leftSidebar).css({
      "background-color": this.options.colors.leftSidebar,
    })
    $(this.containers.rightSidebar).css({
      "background-color": this.options.colors.rightSidebar,
    })
    $(this.containers.header).css({ "background-color": this.options.colors.header })
    $(this.containers.footer).css({ "background-color": this.options.colors.footer })

    if (this.controllers.detailsView && this.options.colors.detailsView !== undefined) {
      this.controllers.detailsView.setStyles({ backgroundColor: this.options.colors.detailsView })
    }
    if (this.controllers.directory && this.options.colors.directory !== undefined) {
      this.controllers.directory?.setStyles({ backgroundColor: this.options.colors.directory })
    }
    if (this.controllers.filters) {
      if (this.options.colors.directorySearch) {
        this.controllers.filters.setStyles({ backgroundColor: this.options.colors.directorySearch })
      } else {
        this.controllers.filters.setStyles({ backgroundColor: "" })
      }
    }

    if ($(this.containers.filtersModal) && this.options.colors.modalFilters !== undefined) {
      $(this.containers.filtersModal).css({
        "background-color": this.options.colors.modalFilters,
      })
    }

    if (!this.containers.clustersCss) {
      this.containers.clustersCss = <HTMLStyleElement>$("<style></style>").appendTo("body")[0]
    }
    let css = ""
    if (this.options.colors.clusters) {
      css += "background-color: " + this.options.colors.clusters + ";"
    }
    if (this.options.colors.clustersBorders) {
      css += "border-color: " + this.options.colors.clustersBorders + ";"
    }
    if (this.options.colors.clustersText) {
      css += "color: " + this.options.colors.clustersText + ";"
    }
    $(this.containers.clustersCss).html(".mapsvg-marker-cluster {" + css + "}")

    if (!this.containers.clustersHoverCss) {
      this.containers.clustersHoverCss = <HTMLStyleElement>$("<style></style>").appendTo("body")[0]
    }
    let cssHover = ""
    if (this.options.colors.clustersHover) {
      cssHover += "background-color: " + this.options.colors.clustersHover + ";"
    }
    if (this.options.colors.clustersHoverBorders) {
      cssHover += "border-color: " + this.options.colors.clustersHoverBorders + ";"
    }
    if (this.options.colors.clustersHoverText) {
      cssHover += "color: " + this.options.colors.clustersHoverText + ";"
    }
    $(this.containers.clustersHoverCss).html(".mapsvg-marker-cluster:hover {" + cssHover + "}")

    if (!this.containers.markersCss) {
      this.containers.markersCss = <HTMLStyleElement>$("<style></style>").appendTo("head")[0]
    }
    const markerCssText =
      ".mapsvg-with-marker-active .mapsvg-marker {\n" +
      "  opacity: " +
      this.options.colors.markers.inactive.opacity / 100 +
      ";\n" +
      "  -webkit-filter: grayscale(" +
      (100 - this.options.colors.markers.inactive.saturation) +
      "%);\n" +
      "  filter: grayscale(" +
      (100 - this.options.colors.markers.inactive.saturation) +
      "%);\n" +
      "}\n" +
      ".mapsvg-with-marker-active .mapsvg-marker-active {\n" +
      "  opacity: " +
      this.options.colors.markers.active.opacity / 100 +
      ";\n" +
      "  -webkit-filter: grayscale(" +
      (100 - this.options.colors.markers.active.saturation) +
      "%);\n" +
      "  filter: grayscale(" +
      (100 - this.options.colors.markers.active.saturation) +
      "%);\n" +
      "}\n" +
      ".mapsvg-with-marker-hover .mapsvg-marker {\n" +
      "  opacity: " +
      this.options.colors.markers.unhovered.opacity / 100 +
      ";\n" +
      "  -webkit-filter: grayscale(" +
      (100 - this.options.colors.markers.unhovered.saturation) +
      "%);\n" +
      "  filter: grayscale(" +
      (100 - this.options.colors.markers.unhovered.saturation) +
      "%);\n" +
      "}\n" +
      ".mapsvg-with-marker-hover .mapsvg-marker-hover {\n" +
      "  opacity: " +
      this.options.colors.markers.hovered.opacity / 100 +
      ";\n" +
      "  -webkit-filter: grayscale(" +
      (100 - this.options.colors.markers.hovered.saturation) +
      "%);\n" +
      "  filter: grayscale(" +
      (100 - this.options.colors.markers.hovered.saturation) +
      "%);\n" +
      "}\n"
    $(this.containers.markersCss).html(markerCssText)

    $.each(this.options.colors, (key, color) => {
      if (color === null) delete this.options.colors[key]
    })

    this.reloadAllRegionStyles()
  }
  /**
   * Sets tooltips options.
   * @param {object} options
   * @example
   * mapsvg.setTooltips({
   *   on: true,
   *   position: "bottom-left",
   *   maxWidth: "300"
   * })
   */
  setTooltips(options: { on: boolean }): void {
    if (options.on !== undefined) options.on = utils.numbers.parseBoolean(options.on)

    deepMerge(this.options, { tooltips: options })

    this.controllers.tooltips = {}
  }
  /**
   * Sets popover options.
   * @param {object} options
   * @example
   * mapsvg.setPopovers({
   *   on: true,
   *   width: 300, // pixels
   *   maxWidth: 70, // percents of map container
   *   maxHeight: 70, // percents of map container
   *   mobileFullscreen: true
   * });
   */
  setPopovers(options) {
    if (options.on !== undefined) options.on = utils.numbers.parseBoolean(options.on)

    deepMerge(this.options.popovers, options)
  }
  /**
   * Sets region prefix
   * @param {string} prefix
   * @private
   */
  setRegionPrefix(prefix) {
    this.options.regionPrefix = prefix
  }
  /**
   * Sets initial viewbox
   * @param {array} viewBox - [x,y,width,height]
   * @private
   */
  setInitialViewBox(viewBox: ViewBox) {
    this._viewBox.update(viewBox)
  }
  setWhRatio(ratio: number) {
    this.whRatio = {
      original: this.svgDefault.viewBox.width / this.svgDefault.viewBox.height,
      current: ratio,
    }
  }
  /**
   * Sets viewbox on map start and calculates initial width/height ratio
   * @private
   */
  setSvgTagViewBox(viewBox: ViewBox) {
    this.containers.svg.setAttribute("viewBox", viewBox.toString())

    // if ((env.getDevice().ios || env.getDevice().android) && this.svgDefault.viewBox.width > 1500) {
    //   this.iosDownscaleFactor = this.svgDefault.viewBox.width > 9999 ? 100 : 10
    //   this.containers.svg.style.width =
    //     (this.svgDefault.viewBox.width / this.iosDownscaleFactor).toString() + "px"
    // } else {
    //   this.containers.svg.style.width = this.svgDefault.viewBox.width + "px"
    // }

    this.vbStart = true
  }
  /**
   * Sets map viewbox
   * @param {ViewBox} viewBox
   * @param {boolean} adjustGoogleMap
   */
  setViewBox(viewBox?: ViewBox, adjustGoogleMap = true): boolean {
    if (!this.svgFileLoaded) {
      return
    }

    let isZooming = false

    if (viewBox) {
      isZooming = this.checkIfZoomIsNeeded(viewBox)
      this.viewBox.update(viewBox)
    }
    // Calculate new padding
    const newPaddingBottom = (this.viewBox.height * 100) / this.viewBox.width

    // Only update padding if the change is significant > 1
    if (Math.abs(newPaddingBottom - this.previousPaddingBottom) > 1) {
      $(this.containers.map).css({
        width: "100%",
        height: "0",
        "padding-bottom": newPaddingBottom + "%",
      })
      this.previousPaddingBottom = newPaddingBottom
    }

    this.whRatio.current = this.viewBox.width / this.viewBox.height

    this.scale = this.getScale()
    const dim: "width" | "height" =
      this.whRatio.current > this.whRatio.original ? "width" : "height"

    // Calculate superScale
    const currentAspectRatio = this.whRatio.current
    const originalAspectRatio = this.whRatio.original
    this.superScale = Math.max(
      currentAspectRatio / originalAspectRatio,
      originalAspectRatio / currentAspectRatio,
    )

    this.scroll.tx = Math.round((this.svgDefault.viewBox.x - this.viewBox.x) * this.scale)
    this.scroll.ty = Math.round((this.svgDefault.viewBox.y - this.viewBox.y) * this.scale)

    // Changing Google Map boundaries will call this method again with adjustGoogleMap=false
    if (this.googleMaps.map && adjustGoogleMap !== false) {
      // We don't know if there are more zoom levels for Google Map
      // So we just zoom-in and then check if zoom has changed.
      // If it's changed then we set the center point
      const googlePrevZoom = this.googleMaps.map.getZoom()

      if (isZooming) {
        this.googleFitCurrentGeoBounds()
      }
      if (googlePrevZoom !== this.googleMaps.map.getZoom()) {
        this.googleMaps.map.setCenter(this.getCenterGeoPoint())
      }
    } else {
      const scale = this.getRelativeScale()

      this.containers.scrollpane.style.transform = `translate(${this.scroll.tx}px, ${this.scroll.ty}px) scale(${scale})`

      this.events.trigger(MapEventType.AFTER_CHANGE_BOUNDS, {
        center: {
          geoPoint: this.getCenterGeoPoint(),
          svgPoint: this.getCenterSvgPoint(),
        },
        viewBox: this.viewBox,
        zoomLevel: this.zoomLevel,
        scale: this._scale,
      })
    }

    this.movingItemsAdjustScreenPosition()

    if (isZooming) {
      this.adjustStrokes()
      this.throttle(this.toggleSvgLayerOnZoom, 50, this)
      if (this.options.clustering.on) {
        // Google Maps is calling setViewBox 20-30 times per zoom cycle (400ms)
        // until the animation finishes. So we have to use .throttle to avoid calling
        // the expensive operation multiple times.
        this.throttle(this.clusterizeOnZoom, 400, this)
      } else {
        this.events.trigger(MapEventType.ZOOM)
      }
    }

    return true
  }

  /**
   * Returns lat,lng coordinates of center of the current view
   */
  getCenterGeoPoint(fromZeroLevel: boolean = false, viewBox?: ViewBox): GeoPoint | null {
    if (!this.isGeo()) {
      return null
    }
    if (!this.converter) {
      return new GeoPoint(0, 0)
    } else {
      return this.converter.convertSVGToGeo(this.getCenterSvgPoint(fromZeroLevel, viewBox))
    }
  }

  /**
   * Returns  SVG coordinates of the center of current map position
   */
  getCenterSvgPoint(fromZeroLevel: boolean = false, viewBox?: ViewBox): SVGPoint {
    if (fromZeroLevel) {
      return new SVGPoint(
        this.svgDefault.viewBox.x + this.svgDefault.viewBox.width / 2,
        this.svgDefault.viewBox.y + this.svgDefault.viewBox.height / 2,
      )
    } else if (viewBox) {
      return new SVGPoint(viewBox.x + viewBox.width / 2, viewBox.y + viewBox.height / 2)
    } else {
      return new SVGPoint(
        this.viewBox.x + this.viewBox.width / 2,
        this.viewBox.y + this.viewBox.height / 2,
      )
    }
  }

  getZoomRange(): { min: number; max: number } {
    const range = { min: 0, max: 0 }

    range.min = this.options.zoom.limit[0]
    range.max = this.options.zoom.limit[1]

    return range
  }
  /**
   * Turns on marker animations
   * @private
   */
  enableMovingElementsAnimation(): void {
    $(this.containers.map).removeClass(
      "no-transitions-markers no-transitions-labels no-transitions-bubbles",
    )
  }
  /**
   * Turns off marker animations
   * @private
   */
  disableMovingElementsAnimation(): void {
    $(this.containers.map).addClass(
      "no-transitions-markers no-transitions-labels no-transitions-bubbles",
    )
  }
  /**
   * Event handler that clusterizes markers on zoom
   * @private
   */

  clusterizeOnZoom(): void {
    this.events.trigger(MapEventType.ZOOM)
    this.clusterizeMarkers(true)
  }
  /**
   * Delays method execution.
   * For example, it can be used in search input fields to prevent sending
   * an ajax request on each key press.
   * @param {function} method
   * @param {number} delay
   * @param {object} scope
   * @param {array} params
   * @private
   */
  throttle(method: (params: any) => void, delay: number, scope: any, params?: any): void {
    // @ts-ignore
    clearTimeout(method._tId)
    // @ts-ignore
    method._tId = setTimeout(function () {
      method.apply(scope, params)
    }, delay)
  }
  googleFitCurrentGeoBounds(): void {
    const boundsCurrent = this.getGeoBounds()
    this.googleMaps.map.fitBounds(
      {
        south: boundsCurrent.sw.lat,
        west: boundsCurrent.sw.lng,
        north: boundsCurrent.ne.lat,
        east: boundsCurrent.ne.lng,
      },
      0,
    )
  }
  /**
   * Sets SVG viewBox by Google Maps bounds.
   * Used to overlay SVG map on Google Map.
   */
  setViewBoxByGoogleMapBounds(): void {
    const googleMapBounds = this.googleMaps.map.getBounds()
    if (!googleMapBounds) return
    const googleMapBoundsJSON = googleMapBounds.toJSON()

    if (googleMapBoundsJSON.west == -180 && googleMapBoundsJSON.east == 180) {
      const center = this.googleMaps.map.getCenter().toJSON()
    }
    const ne = new GeoPoint(
      googleMapBounds.getNorthEast().lat(),
      googleMapBounds.getNorthEast().lng(),
    )
    const sw = new GeoPoint(
      googleMapBounds.getSouthWest().lat(),
      googleMapBounds.getSouthWest().lng(),
    )

    const xyNE = this.converter.convertGeoToSVG(ne)
    const xySW = this.converter.convertGeoToSVG(sw)

    // Check if map is on border between 180/-180 longitude
    if (xyNE.x < xySW.y) {
      const mapPointsWidth = (this.svgDefault.viewBox.width / this.converter.mapLonDelta) * 360
      xySW.x = -(mapPointsWidth - xySW.y)
    }

    const width = xyNE.x - xySW.x
    const height = xySW.y - xyNE.y
    const viewBox = new ViewBox(xySW.x, xyNE.y, width, height)
    this.setViewBox(viewBox)
  }
  /**
   * Redraws the map.
   * Must be called when the map is shown after being hidden.
   */
  redraw(): void {
    this.disableAnimation()
    // if (utils.env.getBrowser().ie) {
    //   $(this.containers.svg).css({ height: this.svgDefault.viewBox.height })
    // }

    if (this.googleMaps.map) {
      // var center = this.googleMaps.map.getCenter();
      google.maps.event.trigger(this.googleMaps.map, "resize")
      // this.googleMaps.map.setCenter(center);
      // this.setViewBoxByGoogleMapBounds();
    } else {
      this.setViewBox(this.viewBox)
    }
    $(this.containers.popover) &&
      $(this.containers.popover).css({
        "max-height":
          (this.options.popovers.maxHeight * $(this.containers.wrap).outerHeight()) / 100 + "px",
      })
    if (this.controllers && this.controllers.directory) {
      this.controllers.directory?.updateTopShift()
      this.controllers.directory?.updateScroll()
    }
    this.movingItemsAdjustScreenPosition()
    this.adjustStrokes()
    if (this.options.choropleth.on && this.options.choropleth.bubbleMode) {
      this.redrawBubbles()
    }
    setTimeout(() => {
      this.enableAnimation()
    }, 400)
  }

  /**
   * Sets map container size.
   * Can accept both or just 1 parameter - width or height. The missing parameter will be calcualted.
   * @param {number} width
   * @param {number} height
   * @param {boolean} responsive
   * @returns {number[]} - [width, height]
   */
  setSize(width: number, height: number, responsive?: boolean): void {
    // Convert strings to numbers
    this.options.width = width
    this.options.height = height
    this.options.responsive =
      responsive != null && responsive != undefined
        ? utils.numbers.parseBoolean(responsive)
        : this.options.responsive

    // Calculate width and height
    if (!this.options.width && !this.options.height) {
      this.options.width = this.svgDefault.width
      this.options.height = this.svgDefault.height
    } else if (!this.options.width && this.options.height) {
      this.options.width = (this.options.height * this.svgDefault.width) / this.svgDefault.height
    } else if (this.options.width && !this.options.height) {
      this.options.height = (this.options.width * this.svgDefault.height) / this.svgDefault.width
    }

    this.scale = this.getScale()

    this.setResponsive(responsive)

    if (this.loaded) {
      this.redraw()
    }
  }
  /**
   * Sets map responsiveness on/off. When map is responsive, it takes the full width of the parent container.
   * @param {bool} on
   */
  setResponsive(on: boolean): void {
    on = on != undefined ? utils.numbers.parseBoolean(on) : this.options.responsive

    // if (on) {
    $(this.containers.wrap).css({
      width: "100%",
      height: "auto",
    })
    // } else {
    //   $(this.containers.wrap).css({
    //     width: this.options.width + "px",
    //     height: this.options.height + "px",
    //   })
    // }
    deepMerge(this.options, { responsive: on })
  }
  /**
   * Sets map scroll options.
   * @param {object} options - scroll options
   * @param {bool} skipEvents - used by Map Editor to prevent re-setting event handlers.
   * @example
   * mapsvg.setScroll({
   *   on: true,
   *   limit: false // limit scroll to map bounds
   * });
   */
  setScroll(options, skipEvents): void {
    options.on != undefined && (options.on = utils.numbers.parseBoolean(options.on))
    options.limit != undefined && (options.limit = utils.numbers.parseBoolean(options.limit))
    deepMerge(this.options, { scroll: options })
    !skipEvents && this.setEventHandlers()
  }
  /**
   * Sets map zoom options.
   * @param {object} options - zoom options
   * @example
   * mapsvg.setZoom({
   *   on: true,
   *   mousewheel: true,
   *   limit: [-5,10], // allow -5 zoom steps back and +20 zoom steps up from initial position.
   * });
   */
  setZoom(options): void {
    options = options || {}
    options.on != undefined && (options.on = utils.numbers.parseBoolean(options.on))
    options.fingers != undefined && (options.fingers = utils.numbers.parseBoolean(options.fingers))
    options.mousewheel != undefined &&
      (options.mousewheel = utils.numbers.parseBoolean(options.mousewheel))

    // delta = 1.2 changed to delta = 2 since introducing Google Maps + smooth zoom
    // options.delta = 2

    // options.delta && (options.delta = parseFloat(options.delta));

    if (options.limit) {
      if (typeof options.limit == "string") options.limit = options.limit.split(";")
      options.limit = [parseInt(options.limit[0]), parseInt(options.limit[1])]
    }

    deepMerge(this.options, { zoom: options })
    $(this.containers.scrollpaneWrap).off("wheel.mapsvg")

    if (this.options.zoom.mousewheel) {
      // var lastZoomTime = 0;
      // var zoomTimeDelta = 0;

      if (utils.env.getBrowser().firefox) {
        this.firefoxScroll = { insideIframe: false, scrollX: 0, scrollY: 0 }

        $(this.containers.scrollpaneWrap)
          .on("mouseenter", () => {
            this.firefoxScroll.insideIframe = true
            this.firefoxScroll.scrollX = window.scrollX
            this.firefoxScroll.scrollY = window.scrollY
          })
          .on("mouseleave", () => {
            this.firefoxScroll.insideIframe = false
          })

        $(document).scroll(() => {
          if (this.firefoxScroll.insideIframe)
            window.scrollTo(this.firefoxScroll.scrollX, this.firefoxScroll.scrollY)
        })
      }

      $(this.containers.scrollpaneWrap).on("wheel.mapsvg", (event: JQuery.MouseEventBase) => {
        event.preventDefault()
        this.mouseWheelZoom(event)
        // this.throttle(this.mouseWheelZoom, 500, this, [event]);
        return false
      })
      $(this.layers.markers).on("wheel.mapsvg", (event: JQuery.MouseEventBase) => {
        event.preventDefault()
        this.mouseWheelZoom(event)
        return false
      })
    }
    this.canZoom = true
  }

  mouseWheelZoom(event: JQuery.MouseEventBase<any, any, any, any>): void {
    event.preventDefault()
    // @ts-ignore - doesn't recognize .deltaY
    const d = Math.sign(-event.originalEvent.deltaY)
    const center = this.getSvgPointAtClick(event.originalEvent)
    d > 0 ? this.zoomIn(center) : this.zoomOut(center)
  }

  /**
   * Sets map control buttons.
   * @param {object} options - control button options
   * @example
   * mapsvg.setControls({
   *   location: 'right',
   *   zoom: true,
   *   zoomReset: true,
   *   userLocation: true,
   *   previousMap: true
   * });
   */
  setControls(options): void {
    options = options || {}
    deepMerge(this.options, { controls: options })
    this.options.controls.zoom = utils.numbers.parseBoolean(this.options.controls.zoom)
    this.options.controls.zoomReset = utils.numbers.parseBoolean(this.options.controls.zoomReset)
    this.options.controls.userLocation = utils.numbers.parseBoolean(
      this.options.controls.userLocation,
    )
    this.options.controls.previousMap = utils.numbers.parseBoolean(
      this.options.controls.previousMap,
    )

    const loc = this.options.controls.location || "right"

    if (!this.containers.controls) {
      const buttons = $("<div />").addClass("mapsvg-buttons")

      const zoomGroup = $("<div />").addClass("mapsvg-btn-group").appendTo(buttons)
      const zoomIn = $("<div />").addClass("mapsvg-btn-map mapsvg-in")

      zoomIn.on("touchend click", (e) => {
        if (e.cancelable) {
          e.preventDefault()
        }
        e.stopPropagation()
        this.zoomIn()
      })

      const zoomOut = $("<div />").addClass("mapsvg-btn-map mapsvg-out")
      zoomOut.on("touchend click", (e) => {
        if (e.cancelable) {
          e.preventDefault()
        }
        e.stopPropagation()
        this.zoomOut()
      })
      zoomGroup.append(zoomIn).append(zoomOut)

      const location = $("<div />").addClass("mapsvg-btn-map mapsvg-btn-location")
      location.on("touchend click", (e) => {
        if (e.cancelable) {
          e.preventDefault()
        }
        e.stopPropagation()
        this.showUserLocation((location) => {
          if (this.options.scroll.on) {
            this.centerOn(location.marker)
          }
        })
      })

      const userLocationIcon =
        '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 447.342 447.342" style="enable-background:new 0 0 447.342 447.342;" xml:space="preserve"><path d="M443.537,3.805c-3.84-3.84-9.686-4.893-14.625-2.613L7.553,195.239c-4.827,2.215-7.807,7.153-7.535,12.459 c0.254,5.305,3.727,9.908,8.762,11.63l129.476,44.289c21.349,7.314,38.125,24.089,45.438,45.438l44.321,129.509 c1.72,5.018,6.325,8.491,11.63,8.762c5.306,0.271,10.244-2.725,12.458-7.535L446.15,18.429 C448.428,13.491,447.377,7.644,443.537,3.805z"/></svg>'
      location.html(userLocationIcon)

      const locationGroup = $("<div />").addClass("mapsvg-btn-group").appendTo(buttons)
      locationGroup.append(location)

      const zoomResetIcon =
        '<svg height="14px" version="1.1" viewBox="0 0 14 14" width="14px" xmlns="http://www.w3.org/2000/svg" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" xmlns:xlink="http://www.w3.org/1999/xlink"><g fill="none" fill-rule="evenodd" id="Page-1" stroke="none" stroke-width="1"><g fill="#000000" transform="translate(-215.000000, -257.000000)"><g id="fullscreen" transform="translate(215.000000, 257.000000)"><path d="M2,9 L0,9 L0,14 L5,14 L5,12 L2,12 L2,9 L2,9 Z M0,5 L2,5 L2,2 L5,2 L5,0 L0,0 L0,5 L0,5 Z M12,12 L9,12 L9,14 L14,14 L14,9 L12,9 L12,12 L12,12 Z M9,0 L9,2 L12,2 L12,5 L14,5 L14,0 L9,0 L9,0 Z" /></g></g></g></svg>'
      const zoomResetButton = $("<div />")
        .html(zoomResetIcon)
        .addClass("mapsvg-btn-map mapsvg-btn-zoom-reset")
      zoomResetButton.on("touchend click", (e) => {
        if (e.cancelable) {
          e.preventDefault()
        }
        e.stopPropagation()
        this.viewBoxReset("custom")
      })
      const zoomResetGroup = $("<div />").addClass("mapsvg-btn-group").appendTo(buttons)
      zoomResetGroup.append(zoomResetButton)

      

      this.containers.controls = buttons[0]
      this.controls = {
        zoom: zoomGroup[0],
        userLocation: locationGroup[0],
        zoomReset: zoomResetGroup[0],
        
      }
      $(this.containers.map).append($(this.containers.controls))
    }

    $(this.controls.zoom).toggle(this.options.controls.zoom)
    $(this.controls.userLocation).toggle(this.options.controls.userLocation)
    $(this.controls.zoomReset).toggle(this.options.controls.zoomReset)
    

    $(this.containers.controls).removeClass("left")
    $(this.containers.controls).removeClass("right")
    if (loc === "right" || loc === "left") {
      $(this.containers.controls).addClass(loc)
    }
  }

  /**
   * Calculates ViewBox for a specific zoom level on demand
   * @param level - Target zoom level
   * @param baseViewBox - Optional base ViewBox to calculate from (defaults to svgDefault.viewBox)
   * @returns ViewBox for the specified zoom level
   */
  getZoomLevelViewBox(level: number): ViewBox {
    const viewBox = this.getAdjustedSvgDefaultViewBox()

    const delta = this.googleMaps.map ? 2 : this.options.zoom.delta

    // Calculate scale using the same logic as setZoomLevels
    const scale = level >= 0 ? Math.pow(delta, level) : 1 / Math.pow(delta, Math.abs(level))

    viewBox.update(0, 0, viewBox.width / scale, viewBox.height / scale)

    return viewBox
  }
  /**
   * Sets mouse pointer cursor style on hover on regions / markers.
   * @param {string} type - "pointer" or "default"
   */
  setCursor(type): void {
    type = type == "pointer" ? "pointer" : "default"
    this.options.cursor = type
    if (type == "pointer") $(this.containers.map).addClass("mapsvg-cursor-pointer")
    else $(this.containers.map).removeClass("mapsvg-cursor-pointer")
  }
  /**
   * Enables/disables "multiselect" option that allows to select multiple regions.
   * @param {boolean} on
   * @param {boolean} deselect - If true, deselects currently selected Regions
   */
  setMultiSelect(on: boolean, deselect?: boolean) {
    this.options.multiSelect = utils.numbers.parseBoolean(on)
    if (deselect !== false) this.deselectAllRegions()
  }
  /**
   * Sets choropleth map options
   * @param {object} options
   *
   * @example
   * mapsvg.setChoropleth({
   *   on: true,
   *   colors: {
   *     high: "#FF0000",
   *     low: "#00FF00"
   *   }
   *   labels: {
   *     high: "high",
   *     low: "low"
   *   }
   * });
   */
  _new_setChoropleth(options?: any) {
    options = options || this.options.choropleth
    options.on != undefined && (options.on = utils.numbers.parseBoolean(options.on))

    if (
      typeof options.coloring === "undefined" ||
      typeof options.coloring.palette === "undefined" ||
      typeof options.coloring.palette.colors === "undefined"
    ) {
      if (
        typeof options.sourceFieldSelect === "undefined" ||
        typeof options.sourceFieldSelect.variants === "undefined"
      ) {
        deepMerge(this.options.choropleth, options)
      } else {
        this.options.choropleth.sourceFieldSelect.variants = options.sourceFieldSelect.variants
      }
    } else {
      const paletteColorsIndexes = Object.keys(options.coloring.palette.colors)
      if (
        paletteColorsIndexes.length > 1 ||
        (paletteColorsIndexes[0] == "0" &&
          Object.keys(options.coloring.palette.colors[0]).length > 1)
      ) {
        this.options.choropleth.coloring.palette.colors = options.coloring.palette.colors
      } else {
        const paletteColorIndex = Object.keys(options.coloring.palette.colors)[0]
        deepMerge(
          this.options.choropleth.coloring.palette.colors[paletteColorIndex],
          options.coloring.palette.colors[paletteColorIndex],
        )
      }
    }

    this.updateChoroplethMinMax()

    if (
      this.options.choropleth.on &&
      this.options.choropleth.sourceFieldSelect.on &&
      this.options.choropleth.sourceFieldSelect.variants
    ) {
      if (!this.containers.choroplethSourceSelect) {
        const sourceSelectOptions = []
        this.options.choropleth.sourceFieldSelect.variants.forEach((variant) => {
          sourceSelectOptions.push(
            $(
              '<option value="' +
                variant +
                '" ' +
                (variant === this.options.choropleth.sourceField ? "selected" : "") +
                ">" +
                variant +
                "</option>",
            ),
          )
        })

        this.containers.choroplethSourceSelect = {
          container: $('<div class="mapsvg-choropleth-source-field"></div>')[0],
          select: $(
            '<select id="mapsvg-choropleth-source-field-select" class="mapsvg-select2"></select>',
          )[0],
          options: sourceSelectOptions,
        }

        $(this.containers.choroplethSourceSelect.select).append(
          this.containers.choroplethSourceSelect.options,
        )
        //$(this.containers.choroplethSourceSelect.container).append($(this.containers.choroplethSourceSelect.label));
        $(this.containers.choroplethSourceSelect.container).append(
          $(this.containers.choroplethSourceSelect.select),
        )

        $(this.containers.map).append($(this.containers.choroplethSourceSelect.container))

        $(this.containers.choroplethSourceSelect.select).mselect2()

        //select2 lib bug workaround. Should be on('change'...)
        $(this.containers.choroplethSourceSelect.select)
          .mselect2()
          .on("select2:select select2:unselecting", (e) => {
            if (e.cancelable) {
              e.preventDefault()
            }
            e.stopPropagation()
            this.setChoropleth({ sourceField: $(e.target).mselect2().val() })
          })
      } else {
        $(this.containers.choroplethSourceSelect.select).mselect2("destroy")
        $(this.containers.choroplethSourceSelect.select).find("option").remove()
        this.containers.choroplethSourceSelect.options = []
        this.options.choropleth.sourceFieldSelect.variants.forEach((variant) => {
          this.containers.choroplethSourceSelect.options.push(
            $(
              '<option value="' +
                variant +
                '" ' +
                (variant === this.options.choropleth.sourceField ? "selected" : "") +
                ">" +
                variant +
                "</option>",
            )[0],
          )
        })
        $(this.containers.choroplethSourceSelect.select).append(
          this.containers.choroplethSourceSelect.options,
        )
        $(this.containers.choroplethSourceSelect.select).mselect2({ width: "100%" })
      }
    }

    if (
      (!this.options.choropleth.on || !this.options.choropleth.sourceFieldSelect.on) &&
      this.containers.choroplethSourceSelect &&
      $(this.containers.choroplethSourceSelect.container).is(":visible")
    ) {
      $(this.containers.choroplethSourceSelect.container).hide()
    } else if (
      this.options.choropleth.on &&
      this.options.choropleth.sourceFieldSelect.on &&
      this.containers.choroplethSourceSelect &&
      !$(this.containers.choroplethSourceSelect.container).is(":visible")
    ) {
      $(this.containers.choroplethSourceSelect.container).show()
    }

    $(this.containers.map).find(".mapsvg-choropleth-legend").remove()

    if (this.options.choropleth.on && this.options.choropleth.coloring.legend.on) {
      const legend = this.options.choropleth.coloring.legend
      let coloring = ""

      if (this.options.choropleth.coloring.mode === "gradient") {
        //Gradient mode
        coloring +=
          '<div class="mapsvg-choropleth-legend-gradient-no-data">' +
          this.options.choropleth.coloring.noData.description +
          "</div>"

        let legendGradient = ""

        for (let chunkIdx = 1; chunkIdx < 5; chunkIdx++) {
          legendGradient +=
            '<div class="mapsvg-choropleth-legend-gradient-chunk">' +
            ((this.options.choropleth.coloring.gradient.values.maxAdjusted * chunkIdx) / 5 +
              this.options.choropleth.coloring.gradient.values.min) +
            "</div>"
        }

        coloring +=
          '<div class="mapsvg-choropleth-legend-gradient-colors">' + legendGradient + "</div>"
      } else {
        //Palette mode

        coloring +=
          '<div class="mapsvg-choropleth-legend-palette-color-wrap" data-idx="no-data">' +
          '<div class="mapsvg-choropleth-legend-palette-color"></div>' +
          '<div class="mapsvg-choropleth-legend-palette-color-description">' +
          this.options.choropleth.coloring.noData.description +
          "</div>" +
          "</div>"

        coloring +=
          '<div class="mapsvg-choropleth-legend-palette-color-wrap" data-idx="out-of-range">' +
          '<div class="mapsvg-choropleth-legend-palette-color"></div>' +
          '<div class="mapsvg-choropleth-legend-palette-color-description">' +
          this.options.choropleth.coloring.palette.outOfRange.description +
          "</div>" +
          "</div>"

        const paletteColors = this.options.choropleth.coloring.palette.colors

        paletteColors.forEach((paletteColor, idx) => {
          let paletteColorElementText = ""
          if (paletteColor.description) {
            paletteColorElementText = paletteColor.description
          } else {
            if (idx === 0 && !paletteColor.valueFrom && paletteColor.valueFrom !== 0) {
              if (legend.layout === "vertical") {
                paletteColorElementText = "Less then " + paletteColor.valueTo
              } else {
                paletteColorElementText = "< " + paletteColor.valueTo
              }
            } else if (idx === paletteColors.length - 1 && !paletteColor.valueTo) {
              if (legend.layout === "vertical") {
                paletteColorElementText = paletteColor.valueFrom + " or more"
              } else {
                paletteColorElementText = paletteColor.valueFrom + " <"
              }
            } else {
              paletteColorElementText = paletteColor.valueFrom + " &#8804; " + paletteColor.valueTo
            }
          }

          coloring +=
            '<div class="mapsvg-choropleth-legend-palette-color-wrap" data-idx="' +
            idx +
            '">' +
            '<div class="mapsvg-choropleth-legend-palette-color"></div>' +
            '<div class="mapsvg-choropleth-legend-palette-color-description">' +
            paletteColorElementText +
            "</div>" +
            "</div>"
        })
      }

      this.containers.legend = {
        title: $('<div class="mapsvg-choropleth-legend-title">' + legend.title + "</div>")[0],
        text: $('<div class="mapsvg-choropleth-legend-text">' + legend.text + "</div>")[0],
        coloring: $(
          '<div class="mapsvg-choropleth-legend-' +
            this.options.choropleth.coloring.mode +
            '">' +
            coloring +
            "</div>",
        )[0],
        description: $(
          '<div class="mapsvg-choropleth-legend-description">' + legend.description + "</div>",
        )[0],
        container: $("<div />").addClass(
          "mapsvg-choropleth-legend mapsvg-choropleth-legend-" +
            legend.layout +
            " mapsvg-choropleth-legend-container-" +
            legend.container,
        )[0],
      }

      $(this.containers.legend.container)
        .append(this.containers.legend.title)
        .append(this.containers.legend.text)
        .append(this.containers.legend.coloring)
        .append(this.containers.legend.description)
      $(this.containers.map).append(this.containers.legend.container)

      this.setChoroplethLegendCSS()

      this.regionsRepository.events.on(RepositoryEvent.AFTER_UPDATE, () => {
        this.redrawChoropleth()
      })

      this.objectsRepository.events.on(RepositoryEvent.AFTER_UPDATE, () => {
        this.redrawChoropleth()
      })
    }

    if (this.options.choropleth.on && this.options.choropleth.coloring.mode === "gradient") {
      const colors = this.options.choropleth.coloring.gradient.colors
      if (colors) {
        colors.lowRGB = tinycolor(colors.low).toRgb()
        colors.highRGB = tinycolor(colors.high).toRgb()
        colors.diffRGB = {
          r: colors.highRGB.r - colors.lowRGB.r,
          g: colors.highRGB.g - colors.lowRGB.g,
          b: colors.highRGB.b - colors.lowRGB.b,
          a: colors.highRGB.a - colors.lowRGB.a,
        }
        this.containers.legend && this.setChoroplethLegendCSS()
      }
    }

    this.redrawChoropleth()
  }
  /**
   * Redraws choropleth map colors
   * @private
   */
  redrawChoropleth() {
    this.updateChoroplethMinMax()
    this.redrawBubbles()
    this.reloadAllRegionStyles()
  }
  /**
   * Updates min/max values of choropleth fields
   * @private
   */
  updateChoroplethMinMax() {
    if (this.options.choropleth.on) {
      const gradient = this.options.choropleth.coloring.gradient,
        values = []

      gradient.values.min = 0
      gradient.values.max = null

      if (this.options.choropleth.source === "regions") {
        this.regionsRepository.getLoaded().forEach((region) => {
          const choropleth = region.getData()[this.options.choropleth.sourceField]
          if (choropleth) {
            values.push(choropleth)
          }
        })
      } else {
        this.objectsRepository.objects.forEach((object) => {
          const choropleth = object.getData()[this.options.choropleth.sourceField]
          choropleth != undefined && choropleth !== "" && values.push(choropleth)
        })
      }

      if (values.length > 0) {
        gradient.values.min = Math.min.apply(null, values)
        gradient.values.max = Math.max.apply(null, values)
        gradient.values.maxAdjusted = gradient.values.max - gradient.values.min
      }
    }
  }
  /**
   * Sets gradient for choropleth map
   * @private
   */
  setChoroplethLegendCSS(): void {
    const gradient = this.options.choropleth.coloring.gradient,
      legend = this.options.choropleth.coloring.legend,
      noData = this.options.choropleth.coloring.noData,
      outOfRange = this.options.choropleth.coloring.palette.outOfRange

    $(".mapsvg-choropleth-legend").css({
      width: legend.width,
      height: legend.height,
    })

    if (this.options.choropleth.coloring.mode === "gradient") {
      if (legend.layout === "horizontal") {
        // horizontal legend
        $(".mapsvg-choropleth-legend-horizontal .mapsvg-choropleth-legend-gradient-colors").css({
          background:
            "linear-gradient(to right," +
            gradient.colors.low +
            " 1%," +
            gradient.colors.high +
            " 100%)",
        })
      } else {
        // vertical legend
        $(".mapsvg-choropleth-legend-vertical .mapsvg-choropleth-legend-gradient-colors").css({
          background:
            "linear-gradient(to bottom," +
            gradient.colors.low +
            " 1%," +
            gradient.colors.high +
            " 100%)",
        })
      }

      $(".mapsvg-choropleth-legend-gradient-no-data").css({
        "background-color": noData.color,
      })
    } else {
      const paletteColors = this.options.choropleth.coloring.palette.colors

      paletteColors.forEach(function (paletteColor, idx) {
        $(
          '.mapsvg-choropleth-legend-palette-color-wrap[data-idx="' +
            idx +
            '"] .mapsvg-choropleth-legend-palette-color',
        ).css({
          "background-color": paletteColor.color,
        })
      })

      $(
        '.mapsvg-choropleth-legend-palette-color-wrap[data-idx="no-data"] .mapsvg-choropleth-legend-palette-color',
      ).css({
        "background-color": noData.color,
      })

      $(
        '.mapsvg-choropleth-legend-palette-color-wrap[data-idx="out-of-range"] .mapsvg-choropleth-legend-palette-color',
      ).css({
        "background-color": outOfRange.color,
      })
    }
  }
  /**
   * Sets custom map CSS.
   * CSS is added as <style>...</style> tag in the page <header>
   * @param {string} css
   * @private
   */
  setCss(css?: string): void {
    if (css && css.indexOf("%id%") !== -1) {
      css = css.replace(/%id%/g, "" + this.id)
    }
    this.options.css = css || this.options.css
    this.liveCss = this.liveCss || <HTMLStyleElement>$("<style></style>").appendTo("head")[0]
    $(this.liveCss).html(this.options.css)
  }
  /**
   * Sets the filters schema for the map.
   * The filters schema defines the available filters for the map.
   *
   * @param filtersSchema - An array of filter fields. Each field is an object with the following properties:
   * - `id` (string): The unique identifier of the filter field.
   * - `title` (string): The title of the filter field.
   * - `type` (string): The type of the filter field. Supported types are: "text", "select", "checkbox", "radio", "range".
   * - `options` (object): Additional options for the filter field. The options depend on the filter type.
   *
   * @returns {void}
   */
  setFiltersSchema(filtersSchema?: any[]): void {
    deepMerge(this.options, { filtersSchema: filtersSchema })
    if (!this.filtersSchema) {
      this.filtersSchema = new Schema({
        objectNamePlural: "filters",
        objectNameSingular: "filter",
        fields: this.options.filtersSchema,
      })
    }
    this.filtersSchema.setFields(filtersSchema)
    this.setFilters()
  }
  /**
   * Sets filter options
   * @param {object} options
   * @example
   * mapsvg.setFilters{{
   *   on: true,
   *   location: "leftSidebar"
   * }};
   */
  setFilters(options?: { [key: string]: any }): void {
    options = options || this.options.filters
    options.on != undefined && (options.on = utils.numbers.parseBoolean(options.on))
    options.hide != undefined && (options.hide = utils.numbers.parseBoolean(options.hide))
    deepMerge(this.options, { filters: options })

    // Filter schema not loaded yet, exit early
    if (typeof this.filtersSchema === "undefined") {
      return
    }

    const scrollable = false

    if (
      !mapsvgCore.googleMapsApiLoaded &&
      this.options.filters.on &&
      this.filtersSchema &&
      this.filtersSchema.getField("distance")
    ) {
      this.loadGoogleMapsAPI(
        () => {
          return 1
        },
        () => {
          return 1
        },
      )
    }

    if (
      ["leftSidebar", "rightSidebar", "header", "footer", "custom", "map"].indexOf(
        this.options.filters.location,
      ) === -1
    ) {
      this.options.filters.location = "leftSidebar"
    }

    if (this.options.filters.on) {
      if (this.formBuilder) {
        this.formBuilder.destroy()
      }

      if (!this.containers.filters) {
        this.containers.filters = $('<div class="mapsvg-filters-wrap"></div>')[0]
      } else {
        $(this.containers.filters).empty()
        $(this.containers.filters).show()
      }

      if ($(this.containers.filtersModal)) {
        $(this.containers.filtersModal).css({ width: this.options.filters.width })
      }

      let container: HTMLElement

      if (this.options.filters.location === "custom") {
        if ($("#" + this.options.filters.containerId).length) {
          container = $("#" + this.options.filters.containerId)[0]
        } else {
          this.decrementAfterloadBlockers()
          console.error(
            "MapSVG: filter container #" + this.options.filters.containerId + " does not exist",
          )
          return
        }
      } else {
        if (isPhone()) {
          container = this.containers.header
          this.setContainers({ header: { on: true } })
        } else {
          const location = isPhone() ? "header" : this.options.filters.location
          if (
            this.options.menu.on &&
            this.controllers.directory &&
            this.options.menu.location === this.options.filters.location
          ) {
            container = $(this.controllers.directory?.containers.view).find(
              ".mapsvg-directory-filter-wrap",
            )[0]
            if (!container) {
              return
            }
          } else {
            container = this.containers[location]
          }
        }
      }

      if (this.regionsRepository || this.objectsRepository) {
        this.loadFiltersController(container, false)
      }
      this.updateFiltersState()
    } else {
      this.controllers.filters && this.controllers.filters.destroy()
      this.controllers.filters = null
    }
    if (
      this.controllers.directory &&
      this.options.menu.location === this.options.filters.location
    ) {
      this.controllers.directory?.updateTopShift()
    }
  }
  /**
   * Updates filters in drop-downs. For example, when region is clicked and data is filtered -
   * it selects corresponding region in the drop-down filter
   * @private
   */
  updateFiltersState() {
    if (this.options.filters && this.options.filters.on && this.controllers.filters) {
      this.controllers.filters.update(this.filtersRepository.query)
      this.updateFilterTags()
    }
  }

  private setGlobalDistanceFilter(): void {
    if (
      this.objectsRepository &&
      this.objectsRepository.query.filters &&
      this.objectsRepository.query.filters.distance
    ) {
      const dist = this.objectsRepository.query.filters.distance
      mapsvgCore.distanceSearch = this.objectsRepository.query.filters.distance
    } else {
      mapsvgCore.distanceSearch = null
    }
  }

  private updateFilterTags() {
    $(this.containers.filterTags) && $(this.containers.filterTags).empty()

    // TODO get field difference

    for (const field_name in this.objectsRepository.query.filters) {
      let field_value = this.objectsRepository.query.filters[field_name]
      let _field_name = field_name
      const filterField = this.filtersSchema.getField(_field_name)

      if (field_name == "regions") {
        // check if there is such filter. If there is then change its value
        // if there isn't then add a tag with close button
        _field_name = ""
        field_value = field_value.region_ids.map((id) => this.getRegion(id).title)
      } else {
        _field_name = filterField && filterField.label
      }

      if (field_name !== "distance") {
        if (!this.containers.filterTags) {
          this.containers.filterTags = $('<div class="mapsvg-filter-tags"></div>')[0]
          // TODO If filtersmodeal on then dont append
          if ($(this.containers.filters)) {
            // $(this.containers.filters).append($(this.containers.filterTags));
          } else {
            if (this.options.menu.on && this.controllers.directory) {
              $(this.controllers.directory?.containers.toolbar).append(this.containers.filterTags)
              this.controllers.directory?.updateTopShift()
            } else {
              $(this.containers.map).append(this.containers.filterTags)
              if (this.options.zoom.buttons.on) {
                if (this.options.layersControl.on) {
                  if (this.options.layersControl.position == "top-left") {
                    $(this.containers.filterTags).css({
                      right: 0,
                      bottom: 0,
                    })
                  } else {
                    $(this.containers.filterTags).css({
                      bottom: 0,
                    })
                  }
                } else {
                  if (this.options.zoom.buttons.location == "left") {
                    $(this.containers.filterTags).css({
                      right: 0,
                    })
                  }
                }
              }
            }
          }

          $(this.containers.filterTags).on(
            "click",
            ".mapsvg-filter-delete",
            (e: JQuery.MouseEventBase) => {
              const filterField = $(e.target).data("filter")
              $(e.target).parent().remove()
              this.objectsRepository.query.removeFilter(filterField)
              this.deselectAllRegions()
              if (this.options.database.on) {
                this.loadDataObjects()
              }
            },
          )
        }
        $(this.containers.filterTags).append(
          '<div class="mapsvg-filter-tag">' +
            (_field_name ? _field_name + ": " : "") +
            field_value +
            ' <span class="mapsvg-filter-delete" data-filter="' +
            field_name +
            '">×</span></div>',
        )
      }
    }
  }

  createContainers() {
    this.containers = {
      ...this.containers,
      map: document.getElementById(this.containerId),
      scrollpane: $('<div class="mapsvg-scrollpane"></div>')[0],
      scrollpaneWrap: $('<div class="mapsvg-scrollpane-wrap"></div>')[0],
      layers: $('<div class="mapsvg-layers-wrap"></div>')[0],
    }

    this.containers.map.appendChild(this.containers.layers)
    this.containers.map.appendChild(this.containers.scrollpaneWrap)
    this.containers.scrollpaneWrap.appendChild(this.containers.scrollpane)

    this.containers.map.classList.add("mapsvg")

    this.containers.wrapAll = document.createElement("div")
    this.containers.wrapAll.classList.add("mapsvg-wrap-all")
    this.containers.wrapAll.id = "mapsvg-map-" + this.id
    this.containers.wrapAll.setAttribute("data-map-id", this.id ? this.id.toString() : "")

    this.containers.wrap = document.createElement("div")
    this.containers.wrap.classList.add("mapsvg-wrap")

    this.containers.mapContainer = document.createElement("div")
    this.containers.mapContainer.classList.add("mapsvg-map-container")

    this.containers.leftSidebar = document.createElement("div")
    this.containers.leftSidebar.className =
      "mapsvg-sidebar mapsvg-top-container mapsvg-sidebar-left"
    this.containers.rightSidebar = document.createElement("div")
    this.containers.rightSidebar.className =
      "mapsvg-sidebar mapsvg-top-container mapsvg-sidebar-right"

    this.containers.header = document.createElement("div")
    this.containers.header.className = "mapsvg-header mapsvg-top-container"
    this.containers.footer = document.createElement("div")
    this.containers.footer.className = "mapsvg-footer mapsvg-top-container"

    this.containers.wrapAll = $('<div class="mapsvg-wrap-all"></div>')
      .attr("id", "mapsvg-map-" + this.id)
      .attr("data-map-id", this.id)[0]
    this.containers.wrap = $('<div class="mapsvg-wrap"></div>')[0]
    this.containers.mapContainer = $('<div class="mapsvg-map-container"></div>')[0]
    this.containers.leftSidebar = $(
      '<div class="mapsvg-sidebar mapsvg-top-container mapsvg-sidebar-left"></div>',
    ).hide()[0]
    this.containers.rightSidebar = $(
      '<div class="mapsvg-sidebar mapsvg-top-container mapsvg-sidebar-right"></div>',
    ).hide()[0]
    this.containers.header = $('<div class="mapsvg-header mapsvg-top-container"></div>').hide()[0]
    this.containers.footer = $('<div class="mapsvg-footer mapsvg-top-container"></div>').hide()[0]

    // Assuming this.containers.wrapAll, this.containers.map, etc. are already references to DOM elements

    // Insert this.containers.wrapAll before this.containers.map
    this.containers.map.parentNode.insertBefore(this.containers.wrapAll, this.containers.map)

    // Append this.containers.header, this.containers.wrap, and this.containers.footer to this.containers.wrapAll
    this.containers.wrapAll.appendChild(this.containers.header)
    this.containers.wrapAll.appendChild(this.containers.wrap)
    this.containers.wrapAll.appendChild(this.containers.footer)

    // Append this.containers.map to this.containers.mapContainer
    this.containers.mapContainer.appendChild(this.containers.map)

    // Append this.containers.leftSidebar, this.containers.mapContainer, and this.containers.rightSidebar to this.containers.wrap
    this.containers.wrap.appendChild(this.containers.leftSidebar)
    this.containers.wrap.appendChild(this.containers.mapContainer)
    this.containers.wrap.appendChild(this.containers.rightSidebar)

    this.containersCreated = true

    if (!this.resizeSensor) {
      this.resizeSensor = new ResizeSensor(this.containers.map, () => {
        this.throttle(
          () => {
            this.redraw()
            this.events.trigger(MapEventType.RESIZE, {
              viewBox: this.viewBox,
              mapWidth: this.containers.map.clientWidth,
              mapHeight: this.containers.map.clientHeight,
            })
          },
          50,
          this,
        )
      })
    }

    // ADD MOBILE TOOLBAR
    this.containers.toolbar = $('<div class="mapsvg-map-toolbar"></div>')[0]
    this.containers.wrapAll.appendChild(this.containers.toolbar)

    this.disableAnimation()
    this.addLayer("markers")
    this.addLayer("labels")
    this.addLayer("bubbles")
    this.addLayer("popovers")
    this.addLayer("scrollpane")
  }

  /**
   * Sets container options: leftSidebar, rightSidebar, header, footer.
   * @param {object} options
   *
   * @example
   * mapsvg.setContainers({
   *   leftSidebar: {
   *     on: true,
   *     width: "300px"
   *   }
   * });
   */
  setContainers(options) {
    const _this = this

    options = options || _this.options.containers || {}

    for (const contName in options) {
      if (options[contName].on !== undefined) {
        options[contName].on = utils.numbers.parseBoolean(options[contName].on)
      }

      if (options[contName].width) {
        if (
          typeof options[contName].width != "string" ||
          (options[contName].width.indexOf("px") === -1 &&
            options[contName].width.indexOf("%") === -1 &&
            options[contName].width !== "auto")
        ) {
          options[contName].width = options[contName].width + "px"
        }
        $(_this.containers[contName]).css({ "flex-basis": options[contName].width })
      }
      if (options[contName].height) {
        if (
          typeof options[contName].height != "string" ||
          (options[contName].height.indexOf("px") === -1 &&
            options[contName].height.indexOf("%") === -1 &&
            options[contName].height !== "auto")
        ) {
          options[contName].height = options[contName].height + "px"
        }
        $(_this.containers[contName]).css({
          "flex-basis": options[contName].height,
          height: options[contName].height,
        })
      }

      deepMerge(_this.options, { containers: options })

      let on = _this.options.containers[contName].on

      if (
        isPhone() &&
        _this.options.menu.hideOnMobile &&
        (_this.options.menu.location === contName || _this.options.menu.location === "custom") &&
        ["leftSidebar", "rightSidebar"].indexOf(contName) !== -1
      ) {
        on = false
      } else if (
        isPhone() &&
        !_this.options.menu.hideOnMobile &&
        _this.options.menu.location === contName &&
        ["leftSidebar", "rightSidebar"].indexOf(contName) !== -1
      ) {
        $(_this.containers.wrapAll).addClass("mapsvg-directory-visible")
      }

      $(_this.containers[contName]).toggle(on)
    }

    _this.setDetailsView()
  }

  createToolbar() {
    if (this.controllers.toolbar) {
      this.controllers.toolbar.destroy()
    }
    this.controllers.toolbar = new ToolbarController({
      map: this,
      parent: this,
      container: this.containers.toolbar,
      state: {
        active: "map",
        ...this.options.mobileView,
      },
      events: {
        click: (event) => {
          const { buttonName } = event.data
          switch (buttonName) {
            case "map": {
              $(this.containers.mapContainer).show()
              $(this.controllers.directory.containers.parent).hide()
              if (isPhone()) {
                $(this.containers.wrap).css("height", "auto")
                this.controllers.directory.updateScroll()
              }
              this.redraw()
              break
            }
            case "menu": {
              $(this.containers.mapContainer).hide()
              $(this.controllers.directory.containers.parent).show()
              if (
                isPhone() &&
                $(this.controllers.directory.containers.main).height() <
                  parseInt(this.controllers.directory.options.minHeight)
              ) {
                $(this.containers.wrap).css(
                  "height",
                  parseInt(this.controllers.directory.options.minHeight) + "px",
                )
                this.controllers.directory.updateScroll()
              }
              break
            }
            default: {
              break
            }
          }

          this.controllers.directory.updateTopShift()
        },
      },
    })
    this.controllers.toolbar.init()
  }

  /**
   * Checks if container should be scrollable
   * @param {string} container - mapContainer / leftSidebar / rightSidebar / custom / header / footer
   * @returns {boolean}
   * @private
   */
  shouldBeScrollable(container) {
    const _this = this

    switch (container) {
      case "map":
      case "fullscreen":
      case "leftSidebar":
      case "rightSidebar":
        return true
        break
      case "custom":
        return false
        break
      case "header":
      case "footer":
        if (
          _this.options.containers[container].height &&
          _this.options.containers[container].height !== "auto" &&
          _this.options.containers[container].height !== "100%"
        ) {
          return true
        } else {
          return false
        }
        break
      default:
        return false
        break
    }
  }

  

  extractField(
    region: Region | null,
    object: Model | Region | null,
    path: string,
    callback: (value: string) => void,
  ) {
    const { object: objectName, field } = splitObjectAndField(path)
    let data: string
    if (!callback) {
      throw new Error("extractField: no callback provided")
    }
    if (objectName && field) {
      if (!["Region", "Object"].includes(objectName)) {
        throw new Error("extractField: Incorrect object type " + objectName)
      }
      const objectFinal = objectName == "Region" ? region.getData() : object.getData()
      data = getNestedValue(objectFinal, field)
      return callback(data)
    }
  }

  /**
   * Sets database options.
   * @param {object} options
   * @example
   * mapsvg.setDatabase({
   *   pagination: {on: true, perpage: 30}
   * });
   */
  setDatabase(options?) {
    options = options || this.options.database

    // if (!options.regionsTableName) {
    //   options.regionsTableName = "regions_" + this.id
    // }
    // if (!options.objectsTableName) {
    //   options.objectsTableName = "objects_" + this.id
    // }
    if (options.noFiltersNoLoad && this.objectsRepository) {
      this.objectsRepository.setNoFiltersNoLoad(true)
    }

    if (options.pagination) {
      if (options.pagination.on != undefined) {
        options.pagination.on = utils.numbers.parseBoolean(options.pagination.on)
      }
      if (options.pagination.perpage != undefined) {
        options.pagination.perpage = parseInt(options.pagination.perpage)
      }
    }
    const prevOptions = { ...this.options.database.pagination }
    deepMerge(this.options, { database: options })
    if (options.pagination) {
      if (
        (typeof options.pagination.on !== "undefined" &&
          prevOptions.on !== options.pagination.on) ||
        (typeof options.pagination.perpage !== "undefined" &&
          prevOptions.perpage !== options.pagination.perpage)
      ) {
        const query = new Query({
          perpage: this.options.database.pagination.on
            ? this.options.database.pagination.perpage
            : 0,
        })

        this.options.database.on && this.objectsRepository && this.objectsRepository.find(query)
      } else {
        this.setPagination()
      }
    }
  }
  updateGoogleMapsMaxZoom(geoPoint: GeoPoint): void {
    this.googleMaps.maxZoomService.getMaxZoomAtLatLng(
      geoPoint,
      (result: google.maps.MaxZoomResult) => {
        if (result.status === "OK") {
          this.googleMaps.currentMaxZoom = result.zoom
        }
      },
    )
  }
  /**
   * Sets Google Map options.
   * @param {object} options
   * @example
   * mapsvg.setGoogleMaps({
   *   on: true,
   *   type: "terrain"
   * });
   *
   * // Get Google Maps instance:
   * var gm = mapsvg.googleMaps.map;
   */
  setGoogleMaps(options?: any) {
    options = options || this.options.googleMaps
    options.on != undefined && (options.on = utils.numbers.parseBoolean(options.on))

    if (!this.googleMaps) {
      this.googleMaps = { loaded: false, initialized: false, map: null, overlay: null }
    }

    deepMerge(this.options, { googleMaps: options })

    if (this.options.googleMaps.on) {
      if (!mapsvgCore.googleMaps.apiKey) {
        console.error("Google Maps API key is required no enable Google Maps")
        return
      }
      $(this.containers.map).toggleClass("mapsvg-with-google-map", true)
      if (!mapsvgCore.googleMaps.loaded) {
        this.loadGoogleMapsAPI(
          () => {
            this.setGoogleMaps()
          },
          () => {
            this.setGoogleMaps({ on: false })
          },
        )
      } else {
        if (!this.googleMaps.map) {
          this.containers.googleMaps = $(
            '<div class="mapsvg-layer mapsvg-layer-gm" id="mapsvg-google-maps-' +
              this.id +
              '"></div>',
          ).prependTo(this.containers.map)[0]
          $(this.containers.googleMaps).css({
            position: "absolute",
            top: 0,
            left: 0,
            bottom: 0,
            right: 0,
            "z-index": "0",
            opacity: 1,
          })
          this.googleMaps.map = new google.maps.Map(this.containers.googleMaps, {
            mapTypeId: options.type,
            fullscreenControl: false,
            keyboardShortcuts: false,
            mapTypeControl: false,
            scaleControl: false,
            scrollwheel: false,
            streetViewControl: false,
            zoomControl: false,
            styles: options.styleJSON,
            tilt: 0,
          })
          let overlay

          const southWest = new google.maps.LatLng(this.geoViewBox.sw.lat, this.geoViewBox.sw.lng)
          const northEast = new google.maps.LatLng(this.geoViewBox.ne.lat, this.geoViewBox.ne.lng)
          const bounds = new google.maps.LatLngBounds(southWest, northEast)

          this.googleMaps.overlay = this.createGoogleMapOverlay(bounds, this.googleMaps.map)

          // if (!this.options.googleMaps.center || !this.options.googleMaps.zoom) {
          this.googleFitCurrentGeoBounds()
          // } else {
          //   this.googleMaps.map.setZoom(this.options.googleMaps.zoom)
          //   this.googleMaps.map.setCenter(this.options.googleMaps.center)
          // }

          this.googleMaps.maxZoomService = new google.maps.MaxZoomService()

          this.googleMaps.map.addListener("idle", () => {
            this.isZooming = false
            // this.syncZoomLevelWithGoogle()
          })
          google.maps.event.addListenerOnce(this.googleMaps.map, "idle", () => {
            setTimeout(() => {
              $(this.containers.map).addClass("mapsvg-fade-in")
              setTimeout(() => {
                this.googleMaps.initialized = true
                $(this.containers.map).removeClass("mapsvg-google-map-loading")
                $(this.containers.map).removeClass("mapsvg-fade-in")
                if (!this.options.googleMaps.center || !this.options.googleMaps.zoom) {
                  this.options.googleMaps.center = this.googleMaps.map.getCenter().toJSON()
                  this.options.googleMaps.zoom = this.googleMaps.map.getZoom()
                }
                this.updateGoogleMapsMaxZoom(this.options.googleMaps.center)
                this.setViewBoxByGoogleMapsOverlay()
                this.throttle(this.toggleSvgLayerOnZoom, 50, this)
                this.events.trigger("afterLoad.googleMaps")
                this.decrementAfterloadBlockers()
                this.finalizeMapLoading()
              }, 400)
            }, 1)
          })
          this.events.on("boundsChanged.googleMaps", ({ data }) => {
            this.setViewBoxByGoogleMapsOverlay()
          })
        } else {
          this.googleMaps.map.setZoom(this.zoomLevel)
          this.googleMaps.map.setCenter(this.getCenterGeoPoint())
          $(this.containers.googleMaps) && $(this.containers.googleMaps).css({ opacity: 1 })
          if (options.type) {
            this.googleMaps.map.setMapTypeId(options.type)
          }
        }
      }
    } else {
      $(this.containers.googleMaps) && $(this.containers.googleMaps).css({ opacity: 0 })
    }
    if (this.converter) {
      this.converter.setWorldShift(!!this.googleMaps.map)
    }
  }
  createGoogleMapOverlay(bounds, map) {
    class GoogleMapsOverlay extends google.maps.OverlayView {
      bounds_: google.maps.LatLngBounds
      map_: google.maps.Map
      prevCoords: {
        sw: { x: number; y: number }
        sw2: { x: number; y: number }
        ne: { x: number; y: number }
        ne2: { x: number; y: number }
      }
      element: HTMLElement
      mapsvg: MapSVGMap

      constructor(bounds, googleMapInstance, mapsvg_: MapSVGMap) {
        super()
        // Initialize all properties.
        this.bounds_ = bounds
        this.map_ = googleMapInstance
        this.mapsvg = mapsvg_
        this.setMap(googleMapInstance)
        this.prevCoords = {
          sw: { x: 0, y: 0 },
          sw2: { x: 0, y: 0 },
          ne: { x: 0, y: 0 },
          ne2: { x: 0, y: 0 },
        }
      }

      draw(): void {
        this.mapsvg.events.trigger("boundsChanged.googleMaps", {
          bounds: this.getPixelBounds(),
          map: this.map_,
        })
      }

      getScale(): null | number {
        const bounds = this.getPixelBounds()
        if (!bounds) return null
        return (bounds.ne.x - bounds.sw.x) / this.mapsvg.svgDefault.viewBox.width
      }

      onAdd(): void {
        this.element = document.createElement("div")
        this.element.style.borderStyle = "none"
        this.element.style.borderWidth = "0px"
        this.element.style.position = "absolute"
        // this.element.style.background = "rgba(255,0,0,.5)"

        // Add the element to the "overlayLayer" pane.
        const panes = this.getPanes()
        panes.overlayLayer.appendChild(this.element)
      }

      getPixelBounds(): { sw: google.maps.Point; ne: google.maps.Point } {
        const overlayProjection = this.getProjection()
        if (!overlayProjection) return

        const geoSW = this.bounds_.getSouthWest()
        const geoNE = this.bounds_.getNorthEast()

        const coords = {
          sw: overlayProjection.fromLatLngToDivPixel(geoSW),
          ne: overlayProjection.fromLatLngToDivPixel(geoNE),
          sw2: overlayProjection.fromLatLngToContainerPixel(geoSW),
          ne2: overlayProjection.fromLatLngToContainerPixel(geoNE),
        }

        // check if map on border between 180/-180 longitude
        // if(ne.x < sw.x){
        //     sw.x = sw.x - overlayProjection.getWorldWidth();
        // }
        // console.log('NOW sw:'+sw.x+' and ne:'+ne.x+'and W-width:'+overlayProjection.getWorldWidth());

        // if(ne2.x < sw2.x){
        //     sw2.x = sw2.x - overlayProjection.getWorldWidth();
        // }

        const ww = overlayProjection.getWorldWidth()

        if (this.prevCoords.sw) {
          if (coords.ne.x < coords.sw.x) {
            if (
              Math.abs(this.prevCoords.sw.x - coords.sw.x) >
              Math.abs(this.prevCoords.ne.x - coords.ne.x)
            ) {
              coords.sw.x = coords.sw.x - ww
            } else {
              coords.ne.x = coords.ne.x + ww
            }
            if (
              Math.abs(this.prevCoords.sw2.x - coords.sw2.x) >
              Math.abs(this.prevCoords.ne2.x - coords.ne2.x)
            ) {
              coords.sw2.x = coords.sw2.x - ww
            } else {
              coords.ne2.x = coords.ne2.x + ww
            }
          }
        }
        this.element.style.left = coords.sw.x + "px"
        this.element.style.top = coords.ne.y + "px"
        this.element.style.width = coords.ne.x - coords.sw.x + "px"
        this.element.style.height = coords.sw.y - coords.ne.y + "px"

        this.prevCoords = coords

        return { sw: coords.sw2, ne: coords.ne2 }
      }
    }
    return new GoogleMapsOverlay(bounds, map, this)
  }
  /**
   * Loads Google Maps API (js file)
   * @param {function} callback - called on file load
   * @param fail
   * @private
   */
  loadGoogleMapsAPI(callback: () => void, fail: () => void): void {
    if (!mapsvgCore.googleMaps.apiKey) {
      console.error("MapSVG: can't load Google API because no API key has been provided")
      return
    }
    if (window.google !== undefined && google.maps) {
      mapsvgCore.googleMaps.loaded = true
    }

    if (mapsvgCore.googleMaps.loaded) {
      if (typeof callback == "function") {
        callback()
      }
      return
    }
    $(this.containers.map).addClass("mapsvg-google-map-loading")
    mapsvgCore.googleMaps.onLoadCallbacks = mapsvgCore.googleMaps.onLoadCallbacks || []
    if (typeof callback == "function") {
      mapsvgCore.googleMaps.onLoadCallbacks.push(callback)
    }

    if (mapsvgCore.googleMaps.apiIsLoading) {
      return
    }
    mapsvgCore.googleMaps.apiIsLoading = true

    // @ts-ignore
    window.gm_authFailure = () => {
      console.error("MapSVG: Google maps API key is incorrect.")
    }
    this.googleMapsScript = document.createElement("script")
    this.googleMapsScript.async = true
    window.mapsvgInitGoogleMap = () => {
      mapsvgCore.googleMaps.loaded = true
      mapsvgCore.googleMaps.onLoadCallbacks.forEach((_callback) => {
        if (typeof callback == "function") _callback()
      })
    }
    const gmLibraries = []
    if (this.options.googleMaps.drawingTools) {
      gmLibraries.push("drawing")
    }
    if (this.options.googleMaps.geometry) {
      gmLibraries.push("geometry")
    }
    let libraries = ""
    if (gmLibraries.length > 0) {
      libraries = "&libraries=" + gmLibraries.join(",")
    }
    this.googleMapsScript.src =
      "https://maps.googleapis.com/maps/api/js?language=" +
      this.options.googleMaps.language +
      "&loading=async&callback=mapsvgInitGoogleMap" +
      "&key=" +
      mapsvgCore.googleMaps.apiKey +
      libraries

    try {
      document.head.appendChild(this.googleMapsScript)
    } catch (e) {
      console.error(e)
      this.decrementAfterloadBlockers()
      this.finalizeMapLoading()
    }
  }
  /**
   * Loads Details View for an object (Region or DB Object)
   * @param {Region|object} obj
   * @param {string} templateName
   *
   * @example
   * var region = mapsvg.getRegion("US-TX");
   * mapsvg.loadDetailsView(region);
   *
   * var object = mapsvg.database.getLoadedObject(12);
   * mapsvg.loadDetailsView(object);
   */
  showDetails(obj: Region | Model, templateName?: string): void {
    this.loadDetailsView(obj, templateName)
  }
  /**
   * @ignore
   */
  loadDetailsView(obj: Region | Model, templateName?: string): void {
    if (this.controllers.popover) this.controllers.popover.close()
    if (this.controllers.detailsView) this.controllers.detailsView.destroy()

    const cancelAutoresize =
      this.options.detailsView.location === "fullscreen" ||
      (isPhone() &&
        this.options.detailsView.mobileFullscreen &&
        this.options.detailsView.location !== "custom")

    if ($(this.containers.detailsView) && this.options.colors.detailsView !== undefined) {
      $(this.containers.detailsView).css({
        "background-color": this.options.colors.detailsView,
      })
    }

    let detContainer
    if (this.options.detailsView.location === "custom") {
      detContainer = $("#" + this.options.detailsView.containerId)[0]
    } else {
      detContainer = this.containers[this.options.detailsView.location]
    }

    const template = templateName
? this.options.templates[templateName]
: "type" in obj && obj.isRegion()
? this.options.templates.detailsViewRegion
: this.options.templates.detailsView
    

    this.controllers.detailsView = new DetailsController({
      map: this,
      parent: this,
      styles: {
        backgroundColor: this.options.colors.detailsView,
        margin: this.options.detailsView.margin,
        width: this.options.detailsView.width,
      },
      autoresize: cancelAutoresize ? false : this.options.detailsView.autoresize,
      container: detContainer,
      template,
      state: obj.getData(),
      scrollable: cancelAutoresize || this.shouldBeScrollable(this.options.detailsView.location), //['custom','header','footer'].indexOf(this.options.detailsView.location) === -1,
      withToolbar: !((isPhone() || isTablet()) && this.options.detailsView.mobileFullscreen),
      fullscreen: {
        sm:
          this.options.detailsView.mobileFullscreen ||
          this.options.detailsView.location === "fullscreen",
        md:
          this.options.detailsView.mobileFullscreen ||
          this.options.detailsView.location === "fullscreen",
        lg: this.options.detailsView.location === "fullscreen",
      },
      position: ["custom", "header", "footer"].includes(this.options.detailsView.location)
        ? "relative"
        : "absolute",
      events: {
        afterShow: (event) => {
          const { controller } = event.data
        },
        afterClose: (event) => {
          const { controller } = event.data
          this.deselectAllRegions()
          this.deselectAllMarkers()
          if (this.controllers && this.controllers.directory) {
            this.controllers.directory?.deselectItems()
          }
        },
      },
      middleware: this.middlewares.middlewares.filter((m) => m.name == MiddlewareType.RENDER),
    })
    this.controllers.detailsView.init()
    this.controllers.detailsView.open()
  }
  /**
   * Loads modal window with filters
   * @private
   */
  loadFiltersModal(): void {
    if (this.options.filters.modalLocation != "custom") {
      if (!this.containers.filtersModal) {
        this.containers.filtersModal = $(
          '<div class="mapsvg-details-container mapsvg-filters-wrap"></div>',
        )[0]
      }
      this.setColors()
      $(this.containers.filtersModal).css({ width: this.options.filters.width })
      if (isPhone()) {
        $("body").append($(this.containers.filtersModal))
        $(this.containers.filtersModal).css({ width: "" })
      } else {
        $(this.containers[this.options.filters.modalLocation]).append(
          $(this.containers.filtersModal),
        )
      }
    } else {
      this.containers.filtersModal = $("#" + this.options.filters.containerId)[0]
      $(this.containers.filtersModal).css({ width: "" })
    }

    this.loadFiltersController(this.containers.filtersModal, true)
  }
  /**
   * Loads filters controller into a provided container
   * @param {HTMLElement} container - Container, jQuery object
   * @param {boolean} modal - If filter should be in a modal window
   */
  loadFiltersController(container: HTMLElement, modal = false): void {
    if (!this.filtersShouldBeShown()) {
      this.controllers.filters?.destroy()
      return
    }

    let filtersInDirectory: boolean, filtersHide: boolean

    if (isPhone()) {
      filtersInDirectory = true
      filtersHide = this.options.filters.hideOnMobile
    } else {
      filtersInDirectory =
        this.options.menu.on &&
        this.controllers.directory &&
        this.options.menu.location === this.options.filters.location
      filtersHide = this.options.filters.hide
    }

    this.filtersRepository =
      this.options.filters.source === "regions" ? this.regionsRepository : this.objectsRepository

    if (this.options.filters.searchButton) {
      this.changedFields = null
      this.changedSearch = null
    } else {
      this.changedSearch = () => {
        this.filtersRepository.query.searchFallback =
          this.filtersSchema.getFieldByType("search").searchFallback
        this.throttle(this.filtersRepository.reload, 400, this.filtersRepository)
      }
      this.changedFields = () => {
        this.throttle(this.filtersRepository.reload, 400, this.filtersRepository)
      }
    }
    this.controllers.filters?.destroy()

    this.controllers.filters = new FiltersController({
      map: this,
      parent: this,
      middleware: this.middlewares.middlewares.filter((m) => m.name === MiddlewareType.RENDER),
      container,
      classList:
        this.options.filters.location === "custom" ? ["mapsvg-filter-container-custom"] : [],
      styles: {
        backgroundColor: this.options.colors.directorySearch,
        width: $(container).hasClass("mapsvg-map-container") ? this.options.filters.width : "100%",
        padding: this.options.filters.padding,
      },
      query: this.filtersRepository?.query,
      schema: this.filtersSchema,
      template: '<div class="mapsvg-filters-container"></div>',
      scrollable:
        modal ||
        (!filtersInDirectory &&
          ["leftSidebar", "rightSidebar"].indexOf(this.options.filters.location) !== -1),
      modal,
      withToolbar: isPhone() ? false : modal,
      modalLocation: this.options.filters.modalLocation,
      hideFilters: this.options.filters.hide,
      hideOnMobile: this.options.filters.hideOnMobile,
      showButtonText: this.options.filters.showButtonText,
      clearButton: this.options.filters.clearButton,
      clearButtonText: this.options.filters.clearButtonText,
      searchButton: this.options.filters.searchButton,
      searchButtonText: this.options.filters.searchButtonText,
      events: {
        afterRedraw: () => {
          filtersInDirectory && this.controllers.directory?.updateTopShift()
        },
        "clear.form": (event) => {
          this.deselectAllRegions()
          this.filtersRepository.find()
        },
        "change.formElement.form": (
          event: EventWithData<{ formElement: FormElementInterface }>,
        ) => {
          const { formElement } = event.data
          if (formElement.type === "search") {
            this.changedSearch && this.changedSearch()
          } else {
            this.changedFields && this.changedFields()
          }
        },
        "click.btnSearch": () => {
          this.filtersRepository.find()
        },
        "afterLoad.form": () => {
          // TODO add resize sensor to directory instead of this:
          this.controllers.directory && this.controllers.directory?.updateTopShift()
          this.decrementAfterloadBlockers()
        },
        "click.btnShowFilters": () => {
          this.loadFiltersModal()
        },
      },
    })
    this.controllers.filters.init()
  }

  private filtersShouldBeShown() {
    return (
      this.options.filters.on && this.options.filtersSchema && this.options.filtersSchema.length > 0
    )
  }

  /**
   * Event handler for text search input
   * @param {string} text
   * @param {boolean} fallback
   * @private
   */
  textSearch(text: string, fallback = false): void {
    const query = new Query({
      filters: { search: text },
      searchFallback: fallback,
    })

    this.filtersRepository.find(query)
  }
  /**
     * Finds a Region by ID
     * @param {string} id - Region ID
     * @returns {Region}

     * @example
     * var region = mapsvg.getRegion("US-TX");
     */
  getRegion(id: string): Region {
    return this.regions.findById(id)
  }
  /**
   * Returns all Regions
   * @returns {Region[]}
   */
  getRegions(): ArrayIndexed<Region> {
    return this.regions
  }
  /**
     * Finds a Marker by ID
     * @param {string} id - Marker ID
     * @returns {Marker}

     * @example
     * var marker = mapsvg.getMarker("marker_12");
     */
  getMarker(id: string): Marker {
    return this.markers.findById(id)
  }
  /**
   * Checks if IDs is unique
   * @param id
   * @returns {bool|string} - true if ID is available, {error: "error text"} if not.
   * @private
   */
  checkId(id: string): { canUse: boolean; error: string } {
    if (this.getRegion(id))
      return { canUse: false, error: "This ID is already being used by a Region" }
    else if (this.markers.findById(id))
      return { canUse: false, error: "This ID is already being used by another Marker" }
    else return { canUse: true, error: "" }
  }
  /**
   * Redraws colors of regions.
   * Used when Region statuses are loaded from the database or when choropleth map is enabled.
   */
  reloadAllRegionStyles(): void {
    this.regions.forEach(this.reloadRegionStyles)
  }

  reloadRegionStyles = (region: Region) => {
    const styles = this.getRegionStylesByState(region)
    if (styles) {
      region.setStylesByState(styles)
    }
  }

  /**
   * Redraws choropleth bubbles.
   */
  redrawBubbles(): void {
    $(this.containers.map).removeClass("bubbles-regions-on bubbles-database-on")

    if (this.options.choropleth.on && this.options.choropleth.bubbleMode) {
      $(this.containers.map).addClass("bubbles-" + this.options.choropleth.source + "-on")
    }

    this.regions.forEach(function (region) {
      region.drawBubble()
    })

    const markersBubbleMode =
      this.options.choropleth.on &&
      this.options.choropleth.source == "database" &&
      this.options.choropleth.bubbleMode
    this.markers.forEach(function (marker) {
      marker.setBubbleMode(markersBubbleMode)
    })
  }
  /**
   * Destroys the map and all related containers.
   * @returns {MapSVGMap}
   */
  destroy(): void {
    if (this.controllers && this.controllers.directory) {
      this.controllers.directory?.mobileButtons.remove()
    }
    $(this.containers.map)
      .empty()
      .insertBefore($(this.containers.wrapAll))
      // .attr("style", "")
      .attr("class", "mapsvg")

    this.controllers.popover && this.controllers.popover.close()

    if (this.controllers.detailsView) this.controllers.detailsView.destroy()

    $(this.containers.detailsView).remove()
    $(this.containers.popover).remove()
    $(this.containers.tooltipRegion).remove()
    $(this.containers.tooltipCluster).remove()
    $(this.containers.tooltipMarker).remove()

    $(this.containers.wrapAll).remove()
    const indexToRemove = mapsvgCore.instances.findIndex((item) => item.id === this.id)
    if (indexToRemove !== -1) {
      mapsvgCore.instances.splice(indexToRemove, 1) // Remove 1 element at the found index
    }
  }
  /**
   * @returns {MapSVGMap}
   */
  getData(): {
    id: number
    title: string
    options: MapOptions
    version: string
    svgFileLastChanged: number
  } {
    return {
      id: this.id,
      title: this.options.title,
      options: this.getOptions(false, false),
      version: this.version,
      svgFileLastChanged: this.svgFileLastChanged,
    }
  }
  /**
   * Checks if fitmarkers() action can be performed and does it
   * @private
   */
  mayBeFitMarkers(): void {
    if (!this.lastTimeFitWas) {
      this.lastTimeFitWas = Date.now() - 99999
    }
    this.fitDelta = Date.now() - this.lastTimeFitWas

    const { fitMarkersOnStart, fitMarkers } = this.options
    const needToFitMarkers =
      !this.fitOnDataLoadDone && (this.firstDataLoad ? fitMarkersOnStart : fitMarkers)

    if (this.fitDelta > 1000 && needToFitMarkers) {
      if (this.options.googleMaps.on && !this.googleMaps.map) {
        this.events.on("afterLoad.googleMaps", () => {
          this.fitMarkers()
        })
      } else {
        this.fitMarkers()
      }
      this.fitOnDataLoadDone = true
    }

    this.lastTimeFitWas = Date.now()
  }
  /**
   * Changes maps viewBox to fit loaded markers.
   */
  fitMarkers(): void {
    const dbObjects = this.objectsRepository.getLoaded()

    if (!dbObjects || dbObjects.length === 0) {
      return
    }

    if (this.googleMaps.map) {
      const lats = []
      const lngs = []

      if (dbObjects.length > 1) {
        dbObjects.forEach((object) => {
          if (object.getData().location && object.getData().location.geoPoint) {
            lats.push(object.getData().location.geoPoint.lat)
            lngs.push(object.getData().location.geoPoint.lng)
          }
        })

        // calc the min and max lng and lat
        const minlat = Math.min.apply(null, lats),
          maxlat = Math.max.apply(null, lats)
        const minlng = Math.min.apply(null, lngs),
          maxlng = Math.max.apply(null, lngs)

        const bbox = new google.maps.LatLngBounds(
          { lat: minlat, lng: minlng },
          { lat: maxlat, lng: maxlng },
        )
        this.googleMaps.map.fitBounds(bbox, 0)
      } else {
        if (
          dbObjects[0].getData().location &&
          dbObjects[0].getData().location.geoPoint.lat &&
          dbObjects[0].getData().location.geoPoint.lng
        ) {
          const coords = {
            lat: dbObjects[0].getData().location.geoPoint.lat,
            lng: dbObjects[0].getData().location.geoPoint.lng,
          }
          if (this.googleMaps.map) {
            this.googleMaps.map.setCenter(coords)
            this.googleMaps.map.setZoom(this.options.fitSingleMarkerZoom)
          }
        }
      }
    } else {
      if (this.options.clustering.on) {
        const arr = []
        this.markersClusters.forEach((c) => {
          arr.push(c)
        })
        this.markers.forEach((m) => {
          arr.push(m)
        })
        this.zoomTo(arr)
        return
      } else {
        this.zoomTo(this.markers)
        return
      }
    }
  }

  setFitSingleMarkerZoom(zoom) {
    this.options.fitSingleMarkerZoom = parseInt(zoom)
  }
  /**
   * Shows user current location
   * Works only under HTTPS connection
   * @param callback Callback to be called when user location is received
   * @returns {void}
   */
  showUserLocation(callback: (location: Location) => void): void {
    this.getUserLocation((geoPoint: GeoPoint) => {
      this.userLocation = null
      this.userLocation = new Location({
        geoPoint: geoPoint,
        img: mapsvgCore.routes.root + "/markers/user-location.svg",
      })
      this.userLocationMarker && this.userLocationMarker.delete()
      this.userLocationMarker = new Marker({
        location: this.userLocation,
        mapsvg: this,
        width: 15,
        height: 15,
      })
      $(this.userLocationMarker.element).addClass("mapsvg-user-location")
      this.userLocationMarker.centered = true
      this.getLayer("markers").append(this.userLocationMarker.element)
      this.userLocationMarker.adjustScreenPosition()
      callback && callback(this.userLocation)
    })
  }
  /**
   * Gets user's current location by using browser's HTML5 geolocation feature.
   * Works only under HTTPS connection!
   * @param callback Callback to be called when user location is received
   * @returns {boolean} - false if geolocation is unavailable, or true if it's available
   */
  getUserLocation(callback: (coordinates: GeoPoint) => void): boolean {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function (position) {
        const pos = new GeoPoint(position.coords.latitude, position.coords.longitude)
        callback && callback(pos)
      })
      return true
    } else {
      return false
    }
  }
  /**
   * Returns current SVG scale related to screen - map screen pixels to SVG points ratio.
   * Example: if SVG current viewBox width is 600 and the map is shown in a 300px container,
   * the map scale is 0.5 (300/600 = 0.5)
   * @returns {number} Map scale related to screen
   */
  getScale(): number {
    const scale2 = this.containers.map.clientWidth / this.viewBox.width
    return scale2 || 1
  }

  getAdjustedSvgDefaultViewBox(): ViewBox {
    return this.adjustViewBoxToSize(
      this.svgDefault.viewBox,
      this.options.width,
      this.options.height,
    )
  }

  /**
   * Calculates the relative scale of the map.
   * The relative scale is the ratio of the width of the adjusted SVG default viewBox to the width of the current viewBox.
   * @returns {number} The relative scale of the map.
   */
  getRelativeScale(): number {
    // const defaultViewBox = this.getAdjustedSvgDefaultViewBox()
    // return defaultViewBox.width / this.viewBox.width
    return this.svgDefault.viewBox.width / this.viewBox.width
  }

  /**
   * Get relative map scale from initial zoom level
   */
  get _scale(): number {
    return this.getRelativeScale()
  }

  /**
   * Current map zoom level
   */
  get zoomLevel(): number {
    if (this.googleMaps.map) {
      return this.googleMaps.map.getZoom()
    } else {
      const defaultViewBox = this.getAdjustedSvgDefaultViewBox()
      const _scale = defaultViewBox.width / this.viewBox.width
      return Math.round((Math.log(_scale) / Math.log(this.options.zoom.delta)) * 10) / 10
    }
  }

  /**
   * Returns current viewBox
   * @returns {ViewBox} ViewBox
   */
  getViewBox(): ViewBox {
    return this.viewBox
  }
  setRatio(ratio: number): void {
    const center = this.getCenterSvgPoint()
    let width: number, height: number

    const originalRatio = this.svgDefault.viewBox.width / this.svgDefault.viewBox.height
    if (ratio > originalRatio) {
      width = this.svgDefault.viewBox.height * ratio
      height = this.svgDefault.viewBox.height
    } else {
      width = this.svgDefault.viewBox.width
      height = this.svgDefault.viewBox.width / ratio
    }
    this.viewBoxSetBySize(width, height)
  }

  /**
   * Adjusts the ViewBox dimensions to match a new width/height ratio while maintaining the center point.
   * Used when the container size changes and the ViewBox needs to be adjusted accordingly.
   *
   * @param viewBox - The ViewBox to adjust
   * @param width - New container width
   * @param height - New container height
   *
   * Example:
   * If container changes from 800x600 to 800x400:
   * - New ratio will be 2:1 (wider)
   * - ViewBox width will increase to maintain ratio
   * - Center point stays the same
   */
  private adjustViewBoxToSize(viewBox: ViewBox, width: number, height: number): ViewBox {
    const viewBoxCopy = viewBox.clone()
    // Get current center point
    const centerX = viewBoxCopy.x + viewBoxCopy.width / 2
    const centerY = viewBoxCopy.y + viewBoxCopy.height / 2

    // Calculate new ratio
    const newRatio = width / height
    const currentRatio = viewBoxCopy.width / viewBoxCopy.height

    let newWidth: number
    let newHeight: number

    if (newRatio > currentRatio) {
      // Width needs to increase
      newHeight = viewBoxCopy.height
      newWidth = newHeight * newRatio
    } else {
      // Height needs to increase
      newWidth = viewBoxCopy.width
      newHeight = newWidth / newRatio
    }

    // Calculate new top-left corner to maintain center point
    const newX = centerX - newWidth / 2
    const newY = centerY - newHeight / 2

    // Update the passed viewBox
    viewBoxCopy.update({
      x: newX,
      y: newY,
      width: newWidth,
      height: newHeight,
    })

    return viewBoxCopy
  }

  /**
   * Adjusts the size of the map container and viewBox simultaneously.
   * This method is ideal for scenarios where both the map container and viewBox sizes need to be modified.
   * @param {number} width The desired width for the map container
   * @param {number} height The desired height for the map container
   * @returns {ViewBox} The updated viewBox object
   */
  viewBoxSetBySize(width: number, height: number): ViewBox {
    this.setSize(width, height)

    const viewBoxInitialAdjusted = this.adjustViewBoxToSize(this._viewBox, width, height)
    this.setInitialViewBox(viewBoxInitialAdjusted)

    const currentViewBox = this.adjustViewBoxToSize(this.viewBox, width, height)
    this.setViewBox(currentViewBox)

    this.setSize(width, height)

    return this.viewBox
  }
  /**
   * Returns viewBox for a provided width/height ratio
   * @param ratio
   * @returns {ViewBox} ViewBox
   * @private
   */
  viewBoxGetByRatio(ratio: number): ViewBox {
    const old_ratio = this.svgDefault.viewBox.width / this.svgDefault.viewBox.height

    const vb = this.svgDefault.viewBox.clone()

    if (ratio != old_ratio) {
      if (ratio > old_ratio) {
        vb.width = this.svgDefault.viewBox.height * ratio
        vb.x = this.svgDefault.viewBox.x - (vb.width - this.svgDefault.viewBox.width) / 2
      } else {
        vb.height = this.svgDefault.viewBox.width / ratio
        vb.y = this.svgDefault.viewBox.y - (vb.height - this.svgDefault.viewBox.height) / 2
      }
    }

    return vb
  }
  /**
   * Returns viewBox for a provided width/height
   * @param width
   * @param height
   * @returns {ViewBox} ViewBox
   * @private
   */
  viewBoxGetBySize(width: number, height: number): ViewBox {
    const new_ratio = width / height
    const old_ratio = this.svgDefault.viewBox.width / this.svgDefault.viewBox.height

    const vb = this.svgDefault.viewBox.clone()

    if (new_ratio != old_ratio) {
      if (new_ratio > old_ratio) {
        vb.width = this.svgDefault.viewBox.height * new_ratio
        vb.x = this.svgDefault.viewBox.x - (vb.width - this.svgDefault.viewBox.width) / 2
      } else {
        vb.height = this.svgDefault.viewBox.width / new_ratio
        vb.y = this.svgDefault.viewBox.y - (vb.height - this.svgDefault.viewBox.height) / 2
      }
    }

    return vb
  }
  /**
   * Resets the map zoom and scroll to the initial position.
   * @param {boolean} toInitial - Set to "true" to reset to the initial viewBox that was set by the user;
   * set to "false" to reset to the original SVG viewBox as defined in the SVG file.
   * @returns {ViewBox} The new ViewBox after the reset.
   */
  viewBoxReset(type: "custom" | "initial" | "svg" = "custom"): ViewBox {
    const viewBox = this.getViewBoxByState(type)
    if (this.googleMaps.map) {
      switch (type) {
        case "custom": {
          const bounds = this.getGeoBounds(this._viewBox)
          const latLngBounds = new google.maps.LatLngBounds(
            new google.maps.LatLng(bounds.sw),
            new google.maps.LatLng(bounds.ne),
          )
          this.googleMaps.map.fitBounds(latLngBounds, 0)
          break
        }
        case "initial": {
          const bounds = this.getGeoBounds(this.svgDefault.viewBox)
          const latLngBounds = new google.maps.LatLngBounds(
            new google.maps.LatLng(bounds.sw),
            new google.maps.LatLng(bounds.ne),
          )
          this.googleMaps.map.fitBounds(latLngBounds, 0)
          break
        }
        default:
          break
      }
      return viewBox
    } else {
      this.setViewBox(viewBox)
      return this.viewBox
    }
  }

  getViewBoxByState(type: "custom" | "initial" | "svg"): ViewBox {
    switch (type) {
      case "custom":
        return this._viewBox
      case "initial": {
        return this.getAdjustedSvgDefaultViewBox()
      }
      case "svg":
        return this.svgDefault.viewBox
      default:
        break
    }
  }

  /**
   * Returns geo-bounds of the map.
   * @returns {number[]} - [leftLon, topLat, rightLon, bottomLat]
   */
  getGeoViewBox(): number[] {
    const v = this.viewBox
    const p1 = new SVGPoint(v.x, v.y)
    const p2 = new SVGPoint(v.x + v.width, v.y)
    const p3 = new SVGPoint(v.x, v.y)
    const p4 = new SVGPoint(v.x, v.y + v.height)

    const leftLon = this.converter.convertSVGToGeo(p1).lng
    const rightLon = this.converter.convertSVGToGeo(p2).lng
    const topLat = this.converter.convertSVGToGeo(p3).lat
    const bottomLat = this.converter.convertSVGToGeo(p4).lat

    return [leftLon, topLat, rightLon, bottomLat]
  }

  /**
   * Retrieves the geographical boundaries of the map.
   * @returns {{ sw: GeoPoint; ne: GeoPoint }} - Returns an object with south-west and north-east geographical points.
   */
  getGeoBounds(viewBox?: ViewBox): { sw: GeoPoint; ne: GeoPoint } {
    const v = viewBox ?? this.viewBox
    const p1 = new SVGPoint(v.x, v.y)
    const p2 = new SVGPoint(v.x + v.width, v.y)
    const p3 = new SVGPoint(v.x, v.y)
    const p4 = new SVGPoint(v.x, v.y + v.height)

    const leftLon = this.converter.convertSVGToGeo(p1).lng
    const rightLon = this.converter.convertSVGToGeo(p2).lng
    const topLat = this.converter.convertSVGToGeo(p3).lat
    const bottomLat = this.converter.convertSVGToGeo(p4).lat

    return {
      sw: { lng: leftLon, lat: bottomLat },
      ne: { lng: rightLon, lat: topLat },
    }
  }

  /**
   * @ignore
   */
  private setStrokes() {
    $(this.containers.svg)
      .find("path, polygon, circle, ellipse, rect, line, polyline")
      .each((index, elem) => {
        const width = MapObject.getComputedStyle("stroke-width", elem)
        if (width) {
          $(elem).attr("data-stroke-width", width.replace("px", ""))
        }
      })
  }

  /**
   * Adjusts stroke thickness on zoom change
   * @ignore
   */
  private adjustStrokes(): void {
    $(this.containers.svg)
      .find("path, polygon, circle, ellipse, rect, line, polyline")
      .each((index, elem) => {
        const width = elem.getAttribute("data-stroke-width")
        if (width) {
          $(elem).css("stroke-width", Number(width) / this.scale)
        }
      })
  }
  /**
   * Zooms-in the map
   * @param {SVGPoint} center - if provided, zoom will shift the center point
   */
  zoomIn(center?: SVGPoint): void {
    if (this.requestZoom()) {
      this.zoom(this.zoomLevel + 1, center)
    }
  }
  /**
   * Zooms-out the map
   * @param {SVGPoint} center Center point (optional)
   */
  zoomOut(center?: SVGPoint): void {
    if (this.requestZoom()) {
      this.zoom(this.zoomLevel - 1, center)
    }
  }
  /**
   * Zooms to Region, Marker or array of Markers.
   * @param {String|Region|Marker|Marker[]|MarkersCluster[]} mapObjects - Region ID, Region, Marker, array of Markers, array of MarkerClusters
   * @param {number} zoomToLevel - Zoom level
   *
   * @example
   * var region = mapsvg.getRegion("US-TX");
   * mapsvg.zoomTo(region);
   */
  zoomTo(
    mapObjects: Region | Marker | MarkerCluster | Marker[] | Region[] | MarkerCluster[],
    zoomToLevel?: number,
  ): boolean {
    if (Array.isArray(mapObjects) || mapObjects.type === MapObjectType.REGION) {
      const convertedObjects = !Array.isArray(mapObjects) ? [mapObjects] : mapObjects
      if (!this.googleMaps?.map) {
        const bbox = this.getGroupBBox(convertedObjects)
        return this.fitViewBox(bbox, zoomToLevel)
      } else {
        const bounds = this.getGroupBounds(convertedObjects)
        const latLngBounds = new google.maps.LatLngBounds(
          new google.maps.LatLng(bounds.sw),
          new google.maps.LatLng(bounds.ne),
        )
        this.googleMaps.map.fitBounds(latLngBounds, 10)
      }
    } else {
      // Single Marker / cluster
      if (mapObjects.isMarker() || mapObjects.isCluster()) {
        return this.zoomToMarkerOrCluster(mapObjects, zoomToLevel)
      }
    }
  }

  /**
   * Zooms to a single marker or cluster
   * @private
   */
  zoomToMarkerOrCluster(mapObject: Marker | MarkerCluster, zoomToLevel?: number): boolean {
    if (this.requestZoom()) {
      const zoomLevel = zoomToLevel ?? 1
      const newVewBox = this.getZoomLevelViewBox(zoomLevel)
      const vbNew = new ViewBox(
        mapObject.svgPoint.x - newVewBox.width / 2,
        mapObject.svgPoint.y - newVewBox.height / 2,
        newVewBox.width,
        newVewBox.height,
      )
      this.setViewBox(vbNew)
      return true
    }

    return false
  }

  getGroupBounds(objects: (Region | Marker | MarkerCluster)[]): { sw: GeoPoint; ne: GeoPoint } {
    const lats: number[] = []
    const lngs: number[] = []

    objects.forEach((object) => {
      const bounds = object.getGeoBounds()
      lats.push(bounds.sw.lat, bounds.ne.lat)
      lngs.push(bounds.sw.lng, bounds.ne.lng)
    })

    if (lats.length === 0 || lngs.length === 0) {
      throw new Error("No valid geo points found in the provided objects.")
    }

    // Calculate the min and max latitude and longitude
    const minlat = Math.min(...lats)
    const maxlat = Math.max(...lats)
    const minlng = Math.min(...lngs)
    const maxlng = Math.max(...lngs)

    // Return the southwest and northeast points
    return {
      sw: new GeoPoint(minlat, minlng),
      ne: new GeoPoint(maxlat, maxlng),
    }
  }

  getGroupBBox(mapObjects: (Marker | Region | MarkerCluster)[]): ViewBox {
    let _bbox, bbox
    const xmax: number[] = []
    const ymax: number[] = []
    const xmin: number[] = []
    const ymin: number[] = []

    for (let i = 0; i < mapObjects.length; i++) {
      _bbox = mapObjects[i].getBBox()
      xmin.push(_bbox.x)
      ymin.push(_bbox.y)
      const _w = _bbox.x + _bbox.width
      const _h = _bbox.y + _bbox.height
      xmax.push(_w)
      ymax.push(_h)
    }
    const _xmin = Math.min(...xmin)
    const _ymin = Math.min(...ymin)
    const width = Math.max(...xmax) - _xmin
    const height = Math.max(...ymax) - _ymin

    let padding = 10
    const point1 = new ScreenPoint(padding, 0)
    const point2 = new ScreenPoint(0, 0)
    padding =
      this.converter.convertPixelToSVG(point1).x - this.converter.convertPixelToSVG(point2).x

    return new ViewBox(_xmin - padding, _ymin - padding, width + padding * 2, height + padding * 2)
  }

  getZoomLevelByViewBox(viewBox: ViewBox, forceZoomLevel?: number): ViewBox {
    if (forceZoomLevel !== undefined) {
      return this.getZoomLevelViewBox(forceZoomLevel)
    }

    // Get the ratio between current viewBox and the target viewBox
    const defaultViewBox = this.getAdjustedSvgDefaultViewBox()

    // Calculate scale for both width and height
    const scaleWidth = defaultViewBox.width / viewBox.width
    const scaleHeight = defaultViewBox.height / viewBox.height

    // Use the smaller scale (means more zooming out) to ensure both dimensions fit
    const scale = Math.min(scaleWidth, scaleHeight)
    const zoomLevel = Math.floor(Math.log(scale) / Math.log(this.options.zoom.delta))

    return this.getZoomLevelViewBox(zoomLevel)
  }

  checkIfZoomIsNeeded(viewBox: ViewBox): boolean {
    return viewBox.width !== this.viewBox.width || viewBox.height !== this.viewBox.height
  }

  requestZoom(): boolean {
    if (!this.canZoom) {
      return false
    } else {
      this.isZooming = true
      this.canZoom = false
      setTimeout(() => {
        this.isZooming = false
        this.canZoom = true
      }, 700)
      return true
    }
  }

  fitViewBox(viewBox: ViewBox, forceZoomLevel?: number | null): boolean {
    const viewBoxFit = this.getZoomLevelByViewBox(viewBox, forceZoomLevel)
    if (this.googleMaps.map) {
      const bounds = this.getGeoBounds(viewBox)
      const latLngBounds = new google.maps.LatLngBounds(
        new google.maps.LatLng(bounds.sw),
        new google.maps.LatLng(bounds.ne),
      )
      this.googleMaps.map.fitBounds(latLngBounds, 10)
      return true
    } else {
      // const zoomIsNeeded = this.checkIfZoomIsNeeded(viewBoxFit)
      // if (zoomIsNeeded) {
      //   if (!this.requestZoom()) {
      //     return false
      //   }
      // }
      const newViewBox = new ViewBox(
        viewBox.x - viewBoxFit.width / 2 + viewBox.width / 2,
        viewBox.y - viewBoxFit.height / 2 + viewBox.height / 2,
        viewBoxFit.width,
        viewBoxFit.height,
      )
      this.enableAnimation()
      this.setViewBox(newViewBox)

      return true
    }
  }

  /**
   * Zooms to a single region
   * @param region
   * @param zoomToLevel
   *
   * @private
   */
  zoomToRegion(region: Region, zoomToLevel?: number): boolean {
    this.fitViewBox(region.getBBox(), zoomToLevel)
    return true
  }

  setZoomLevel(zoomLevel: number): void {
    this.zoom(zoomLevel)
  }

  setCenter(point: SVGPoint): SVGPoint
  setCenter(point: GeoPoint): GeoPoint
  setCenter(point: SVGPoint | GeoPoint): SVGPoint | GeoPoint {
    let svgPoint: SVGPoint
    if (point instanceof GeoPoint) {
      if (!this.isGeo()) {
        throw new Error("MapSVG: can't center on a lat,lng point on a non-geo map.")
      }
      svgPoint = this.converter.convertGeoToSVG(point)
    } else {
      svgPoint = point
    }
    this.centerOn(svgPoint)
    return point
  }

  /**
   * Centers map on Region or Marker.
   * @param {Region|Marker} centerOnObject - Region or Marker
   * @param {number} yShift - Vertical shift from center. Used by showPopover method to fit popover in the map container
   */
  centerOn(
    centerOnObject?: Region | Marker | SVGPoint | SVGPointLiteral | GeoPoint | GeoPointLiteral,
    yShift?: number,
  ): ViewBox {
    yShift = yShift ? (yShift + 12) / this.getScale() : 0

    if (!centerOnObject) {
      centerOnObject = this.getCenterSvgPoint(true)
    }

    if (
      centerOnObject instanceof SVGPoint ||
      isSvgPointLiteral(centerOnObject) ||
      centerOnObject instanceof GeoPoint ||
      isGeoPointLiteral(centerOnObject)
    ) {
      const centerOnPoint = isSvgPointLiteral(centerOnObject)
        ? centerOnObject
        : this.converter.convertGeoToSVG(centerOnObject)
      const vb = this.viewBox
      const newViewBox = new ViewBox(
        centerOnPoint.x - vb.width / 2,
        centerOnPoint.y - vb.height / 2 - yShift,
        vb.width,
        vb.height,
      )
      this.setViewBox(newViewBox)
    } else if (this.googleMaps.map) {
      $(this.containers.map).addClass("scrolling")
      const latLng = centerOnObject.getCenterLatLng(yShift)
      this.googleMaps.map.panTo(latLng)
      setTimeout(() => {
        $(this.containers.map).removeClass("scrolling")
      }, 100)
    } else {
      const bbox = centerOnObject.getBBox()
      const vb = this.viewBox
      const newViewBox = new ViewBox(
        bbox.x - vb.width / 2 + bbox.width / 2,
        bbox.y - vb.height / 2 + bbox.height / 2 - yShift,
        vb.width,
        vb.height,
      )
      this.setViewBox(newViewBox)
      return newViewBox
    }
  }
  /**
   * Adjusts the map's zoom level
   * @param {number} zoomLevel - The target zoom level
   * @param {SVGPoint} [center] - Optional: The center point for zooming
   */
  zoom(zoomLevel: number, center?: SVGPoint): void {
    const d = zoomLevel > this.zoomLevel ? 1 : -1

    const zoomIn = d > 0
    const zoomOut = d < 0

    const zoomRange = this.getZoomRange()

    const isInZoomRange = this.zoomLevel >= zoomRange.min && this.zoomLevel <= zoomRange.max

    const goingOutOfZoomRange =
      isInZoomRange &&
      (zoomLevel > zoomRange.max ||
        (zoomLevel < zoomRange.min && zoomLevel < this.initialZoomLevel))

    const outOfRangeAndMovingAway =
      !isInZoomRange &&
      ((zoomIn && zoomLevel > zoomRange.max) || (zoomOut && zoomLevel < zoomRange.min))

    if (goingOutOfZoomRange || outOfRangeAndMovingAway) {
      return
    }

    let shift = []
    const newViewBox = this.googleMaps.map
      ? this.viewBox.clone()
      : this.getZoomLevelViewBox(zoomLevel)

    if (this.googleMaps.map) {
      const scale = Math.pow(2, zoomLevel - this.zoomLevel)
      newViewBox.width /= scale
      newViewBox.height /= scale
    }

    if (center) {
      const widthRatio = this.svgDefault.viewBox.width / newViewBox.width
      const heightRatio = this.svgDefault.viewBox.height / newViewBox.height
      const newScale = Math.min(widthRatio, heightRatio)
      const oldWidthRatio = this.svgDefault.viewBox.width / this.viewBox.width
      const oldHeightRatio = this.svgDefault.viewBox.height / this.viewBox.height
      const oldScale = Math.min(oldWidthRatio, oldHeightRatio)
      const zoomFactor = oldScale / newScale //zoomScaleDelta > 0 ? 1 / (newScale / oldScale) : newScale / oldScale

      newViewBox.x = this.viewBox.x + (center.x - this.viewBox.x) * (1 - zoomFactor)
      newViewBox.y = this.viewBox.y + (center.y - this.viewBox.y) * (1 - zoomFactor)
    } else {
      shift = [
        (this.viewBox.width - newViewBox.width) / 2,
        (this.viewBox.height - newViewBox.height) / 2,
      ]
      newViewBox.x = this.viewBox.x + shift[0]
      newViewBox.y = this.viewBox.y + shift[1]
    }
    this.shiftViewBoxToScrollBoundaries(newViewBox)

    if (this.googleMaps.map) {
      this.googleMaps.map.setZoom(zoomLevel)
      if (center) {
        this.googleMaps.map.setCenter(this.getCenterGeoPoint(false, newViewBox))
      }
    } else {
      this.setViewBox(newViewBox)
    }
  }

  private toggleSvgLayerOnZoom(): void {
    if (
      this.googleMaps.map &&
      this.options.zoom.hideSvg &&
      this.googleMaps.map.getZoom() >= this.options.zoom.hideSvgZoomLevel
    ) {
      if (!$(this.containers.svg).hasClass("mapsvg-invisible")) {
        $(this.containers.svg).animate({ opacity: 0 }, 400, "linear", () => {
          $(this.containers.svg).addClass("mapsvg-invisible")
        })
      }
    } else {
      if ($(this.containers.svg).hasClass("mapsvg-invisible")) {
        $(this.containers.svg).animate({ opacity: 1 }, 400, "linear", () => {
          $(this.containers.svg).removeClass("mapsvg-invisible")
        })
      }
    }
  }

  private shiftViewBoxToScrollBoundaries(newViewBox: ViewBox): ViewBox {
    if (this.options.scroll.limit) {
      if (newViewBox.x < this.svgDefault.viewBox.x) newViewBox.x = this.svgDefault.viewBox.x
      else if (
        newViewBox.x + newViewBox.width >
        this.svgDefault.viewBox.x + this.svgDefault.viewBox.width
      )
        newViewBox.x = this.svgDefault.viewBox.x + this.svgDefault.viewBox.width - newViewBox.width

      if (newViewBox.y < this.svgDefault.viewBox.y) newViewBox.y = this.svgDefault.viewBox.y
      else if (
        newViewBox.y + newViewBox.height >
        this.svgDefault.viewBox.y + this.svgDefault.viewBox.height
      )
        newViewBox.y =
          this.svgDefault.viewBox.y + this.svgDefault.viewBox.height - newViewBox.height
    }
    return newViewBox
  }

  /**
   * Deletes a marker
   * @param {Marker} marker
   */
  markerDelete(marker: Marker): void {
    if (this.editingMarker && this.editingMarker.id == marker.id) {
      this.unsetEditingMarker()
    }

    if (this.markers.findById(marker.id)) {
      this.markers.delete(marker.id)
    }
    marker.delete()
    marker = null

    if (this.markers.length === 0) this.options.markerLastID = 0
  }
  /**
   * Adds a marker cluster
   * @param {MarkerCluster} markersCluster
   */
  markersClusterAdd(markersCluster: MarkerCluster): void {
    this.layers.markers.append(markersCluster.elem)
    this.markersClusters.push(markersCluster)
    markersCluster.adjustScreenPosition()
  }
  /** Adds a marker to the map.
   * @param {Marker} marker
   *
   * @example
   * let location = new mapsvg.location({
   *   geoPoint: new mapsvg.geoPoint({lat: 55.12, lng: 46.19}),
   *   img: "/path/to/image.png"
   *  });
   *
   * let marker = new mapsvg.marker({
   *   location: location,
   *   mapsvg: mapInstance
   * });
   *
   * // The marker is created but still not added to the map. Let's add it:
   * mapInstance.markerAdd(marker);
   */
  markerAdd(marker): void {
    $(marker.element).hide()
    marker.adjustScreenPosition()
    // marker.setImage(this.getMarkerImage(marker.object, marker.location), true);
    this.layers.markers.append(marker.element)
    this.markers.push(marker)
    marker.mapped = true

    setTimeout(function () {
      $(marker.element).show()
    }, 100)
  }
  /**
   * Removes marker from the map
   * @param marker
   */
  markerRemove(marker: Marker): void {
    if (this.editingMarker && this.editingMarker.id == marker.id) {
      this.editingMarker = null
      delete this.editingMarker
    }

    if (this.markers.findById(marker.id)) {
      this.markers.findById(marker.id).delete()
      this.markers.delete(marker.id)
      marker = null
    }
    if (this.markers.length === 0) this.options.markerLastID = 0
  }
  /**
   * Generates new Marker ID
   * @ignore
   */
  markerId(): string {
    this.options.markerLastID = this.options.markerLastID + 1
    const id = "marker_" + this.options.markerLastID
    if (this.markers.findById(id)) return this.markerId()
    else return id
  }

  /**
   * Adjust positions of all movable objects (markers, clusters, popovers, labels)
   * @ignore
   */
  movingItemsAdjustScreenPosition() {
    this.markersAdjustScreenPosition()

    this.popoverAdjustScreenPosition()

    this.bubblesRegionsAdjustScreenPosition()

    this.labelsRegionsAdjustScreenPosition()
  }

  /**
   * Move all movable objects (markers, clusters, popovers, labels) by given values
   * @param {number} deltaX
   * @param {number} deltaY
   * @ignore
   */
  movingItemsMoveScreenPositionBy(deltaX, deltaY) {
    this.markersMoveScreenPositionBy(deltaX, deltaY)

    this.popoverMoveScreenPositionBy(deltaX, deltaY)

    this.bubblesRegionsMoveScreenPositionBy(deltaX, deltaY)

    this.labelsRegionsMoveScreenPositionBy(deltaX, deltaY)
  }

  /**
   * Adjusts position of Region Labels
   * @ignore
   */
  labelsRegionsAdjustScreenPosition() {
    if ($(this.containers.map).is(":visible")) {
      this.regions.forEach(function (region) {
        region.adjustLabelScreenPosition()
      })
    }
  }

  /**
   * Adjusts position of Regions bubbles
   * @ignore
   */
  bubblesRegionsAdjustScreenPosition() {
    if ($(this.containers.map).is(":visible")) {
      this.regions.forEach(function (region) {
        region.adjustBubbleScreenPosition()
      })
    }
  }

  /**
   * Move position of Region Labels by given numbers
   *
   * @param {number} deltaX
   * @param {number} deltaY
   */
  labelsRegionsMoveScreenPositionBy(deltaX: number, deltaY: number): void {
    if ($(this.containers.map).is(":visible")) {
      this.regions.forEach(function (region) {
        region.moveLabelScreenPositionBy(deltaX, deltaY)
      })
    }
  }

  /**
   * Move position of Regions bubbles by given numbers
   *
   * @param {number} deltaX
   * @param {number} deltaY
   */
  bubblesRegionsMoveScreenPositionBy(deltaX: number, deltaY: number): void {
    if ($(this.containers.map).is(":visible")) {
      this.regions.forEach((region) => {
        region.moveBubbleScreenPositionBy(deltaX, deltaY)
      })
    }
  }

  /**
   * Adjusts position of Markers and MarkerClusters.
   * This method is called on zoom or when map container is resized.
   */
  markersAdjustScreenPosition(): void {
    this.markers.forEach((marker: Marker) => {
      marker.adjustScreenPosition()
    })
    this.markersClusters.forEach((cluster: MarkerCluster) => {
      cluster.adjustScreenPosition()
    })
    if (this.userLocationMarker) {
      this.userLocationMarker.adjustScreenPosition()
    }
  }

  /**
   * Move Markers and clusters by given numbers.
   *
   * @param {number} deltaX
   * @param {number} deltaY
   */
  markersMoveScreenPositionBy(deltaX: number, deltaY: number): void {
    this.markers.forEach((marker) => {
      marker.moveSrceenPositionBy(deltaX, deltaY)
    })

    this.markersClusters.forEach((cluster: MarkerCluster) => {
      cluster.moveSrceenPositionBy(deltaX, deltaY)
    })

    if (this.userLocationMarker) {
      this.userLocationMarker.moveSrceenPositionBy(deltaX, deltaY)
    }
  }

  /**
   * Sets marker into "edit mode".
   * Used in Map Editor.
   * @param {Marker} marker
   */
  setEditingMarker(marker: Marker): void {
    this.editingMarker = marker
    if (!this.editingMarker.mapped) {
      this.markerAdd(this.editingMarker)
    }
  }
  /**
   * Disables marker "edit mode".
   * Used in Map Editor.
   * @private
   */
  unsetEditingMarker(): void {
    if (this.editingMarker && this.editingMarker.mapped) {
      this.markerRemove(this.editingMarker)
    }
    this.editingMarker = null
  }
  /**
   * Returns marker that is currently in the "edit mode"
   * Used in Map Editor.
   */
  getEditingMarker(): Marker {
    return this.editingMarker
  }
  isLinkClicked(e): boolean {
    return !!((e.target && $(e.target).is("a")) || $(e.target).closest("a").length)
  }
  // Check if clicked element is a link and handle the redirect
  handleLinkClick(e: JQuery.TriggeredEvent): void {
    if (this.isLinkClicked(e)) {
      const htmlLinkElement = $(e.target).is("a") ? e.target : $(e.target).closest("a")[0]
      const link = $(htmlLinkElement)
      const href = link.attr("href")

      // Check if it's an external link
      if (href && href.startsWith("http")) {
        e.preventDefault() // Prevent default if you want to handle the redirect

        // Check for target="_blank"
        if (link.attr("target") === "_blank") {
          window.open(href, "_blank")
        } else {
          window.location.href = href
        }
      }

      // Optional: handle internal links differently
      if (href && href.startsWith("#")) {
        e.preventDefault()
        // Handle internal navigation
        // ...
      }
    }
  }

  /**
   * Event handler - called when map scroll starts
   * @param e
   * @param {MapSVGMap} mapsvg
   * @returns {boolean}
   */
  scrollStart(e: JQuery.TouchEventBase, mapsvg: MapSVGMap): void {
    if ($(e.target).hasClass("mapsvg-btn-map") || $(e.target).closest(".mapsvg-choropleth").length)
      return

    if (this.editMarkers.on && $(e.target).hasClass("mapsvg-marker")) return

    e.preventDefault()

    $(this.containers.map).addClass("no-transitions")

    const ce =
      e.originalEvent && e.originalEvent.touches && e.originalEvent.touches[0]
        ? e.originalEvent.touches[0]
        : e

    this.scrollStarted = true

    this.scroll = {
      tx: this.scroll.tx || 0,
      ty: this.scroll.ty || 0,
      // initial viewbox when scrollning started
      vxi: this.viewBox.x,
      vyi: this.viewBox.y,
      // mouse coordinates when scrolling started
      x: ce.clientX,
      y: ce.clientY,
      // mouse delta
      dx: 0,
      dy: 0,
      // new viewbox x/y
      vx: 0,
      vy: 0,
      // for google maps scroll
      gx: ce.clientX,
      gy: ce.clientY,
      touchScrollStart: 0,
    }

    if (e.type.indexOf("mouse") === 0) {
      $(document).on("mousemove.scroll.mapsvg", (e: JQuery.TouchEventBase) => {
        this.scrollMove(e)
      })
      if (this.options.scroll.spacebar) {
        $(document).on("keyup.scroll.mapsvg", (e: JQuery.TouchEventBase) => {
          if (e.keyCode == 32) {
            this.scrollEnd(e, mapsvg)
          }
        })
      } else {
        $(document).on("mouseup.scroll.mapsvg", (e: JQuery.TouchEventBase) => {
          this.scrollEnd(e, mapsvg)
        })
      }
    }
  }
  /**
   * Event handler - called when map scroll moves
   * @param e
   * @private
   */
  scrollMove(e: JQuery.TouchEventBase): void {
    e.preventDefault()

    if (!this.isScrolling) {
      this.isScrolling = true
      // $(this.containers.map).css('pointer-events','none');
      $(this.containers.map).addClass("scrolling")
    }

    const ce =
      e.originalEvent && e.originalEvent.touches && e.originalEvent.touches[0]
        ? e.originalEvent.touches[0]
        : e

    this.panBy(this.scroll.gx - ce.clientX, this.scroll.gy - ce.clientY)

    this.scroll.gx = ce.clientX
    this.scroll.gy = ce.clientY

    // delta x/y
    this.scroll.dx = this.scroll.x - ce.clientX
    this.scroll.dy = this.scroll.y - ce.clientY

    // new viewBox x/y
    let vx = this.scroll.vxi + this.scroll.dx / this.scale
    let vy = this.scroll.vyi + this.scroll.dy / this.scale

    // Limit scroll to map boundaries
    if (this.options.scroll.limit) {
      if (vx < this.svgDefault.viewBox.x) vx = this.svgDefault.viewBox.x
      else if (this.viewBox.width + vx > this.svgDefault.viewBox.x + this.svgDefault.viewBox.width)
        vx = this.svgDefault.viewBox.x + this.svgDefault.viewBox.width - this.viewBox.width

      if (vy < this.svgDefault.viewBox.y) vy = this.svgDefault.viewBox.y
      else if (
        this.viewBox.height + vy >
        this.svgDefault.viewBox.y + this.svgDefault.viewBox.height
      )
        vy = this.svgDefault.viewBox.y + this.svgDefault.viewBox.height - this.viewBox.height
    }

    this.scroll.vx = vx
    this.scroll.vy = vy
  }
  /**
   * Event handler - called when map scroll ends
   * @param {JQuery.TriggeredEvent} e
   * @param {MapSVGMap} mapsvg
   * @param {boolean} noClick
   * @returns {boolean}
   * @private
   */
  scrollEnd(e: JQuery.TouchEventBase, mapsvg: MapSVGMap, noClick?: boolean): void {
    setTimeout(() => {
      this.enableAnimation()
      this.scrollStarted = false
      this.isScrolling = false
    }, 100)
    if (this.googleMaps.map && this.googleMaps.overlay) {
      this.googleMaps.overlay.draw()
    }

    $(this.containers.map).removeClass("scrolling")
    $(document).off("keyup.scroll.mapsvg")
    $(document).off("mousemove.scroll.mapsvg")
    $(document).off("mouseup.scroll.mapsvg")

    // call regionClickHandler if mouse did not move more than 5 pixels
    if (noClick !== true && Math.abs(this.scroll.dx) < 5 && Math.abs(this.scroll.dy) < 5) {
      // Usage in your click handler:
      if (this.isLinkClicked(e)) {
        this.handleLinkClick(e)
        return // Exit the handler after processing the link
      }

      if (this.editMarkers.on) this.clickAddsMarker && this.markerAddClickHandler(e)
      else if (this.objectClickedBeforeScroll)
        if (this.objectClickedBeforeScroll.isMarker()) {
          this.markerClickHandler(e, this.objectClickedBeforeScroll)
        } else if (this.objectClickedBeforeScroll.isRegion()) {
          this.regionClickHandler(e, this.objectClickedBeforeScroll)
        } else if (this.objectClickedBeforeScroll.isCluster()) {
          this.markerClusterClickHandler(e, this.objectClickedBeforeScroll)
        }
    }

    this.viewBox.x = this.scroll.vx || this.viewBox.x
    this.viewBox.y = this.scroll.vy || this.viewBox.y
  }

  setViewBoxByGoogleMapsOverlay(): void {
    if (!this.googleMaps.initialized) return

    const bounds = this.googleMaps.overlay.getPixelBounds()
    const scale = this.googleMaps.overlay.getScale()
    if (!scale || !bounds) return

    const viewBox = new ViewBox(
      this.svgDefault.viewBox.x - bounds.sw.x / scale,
      this.svgDefault.viewBox.y - bounds.ne.y / scale,
      this.containers.map.offsetWidth / scale,
      this.containers.map.offsetHeight / scale,
    )

    this.setViewBox(viewBox, false)
  }

  /**
   * Shift the map by x,y pixels
   * @param {number} x
   * @param {number} y
   */
  panBy(x: number, y: number): { x: boolean; y: boolean } {
    let tx = this.scroll.tx - x
    let ty = this.scroll.ty - y

    const scrolled = { x: true, y: true }

    if (this.options.scroll.limit) {
      const svg = $(this.containers.svg)[0].getBoundingClientRect()
      const bounds = $(this.containers.map)[0].getBoundingClientRect()
      if ((svg.left - x > bounds.left && x < 0) || (svg.right - x < bounds.right && x > 0)) {
        tx = this.scroll.tx
        scrolled.x = false
      }
      if ((svg.top - y > bounds.top && y < 0) || (svg.bottom - y < bounds.bottom && y > 0)) {
        ty = this.scroll.ty
        scrolled.y = false
      }
    }

    const widthRatio = this.svgDefault.viewBox.width / this.viewBox.width

    // this.containers.scrollpane.style.transform = "translate(" + tx + "px," + ty + "px)"
    this.containers.scrollpane.style.transform =
      "translate(" + tx + "px, " + ty + "px) " + "scale(" + widthRatio + ")"

    this.scroll.tx = tx
    this.scroll.ty = ty

    if (scrolled.x || scrolled.y) {
      const moveX = scrolled.x ? x : 0
      const moveY = scrolled.y ? y : 0

      this.movingItemsMoveScreenPositionBy(moveX, moveY)

      if (this.googleMaps.map) {
        let point = this.googleMaps.map.getCenter()

        const projection = this.googleMaps.overlay.getProjection()

        const pixelpoint = projection.fromLatLngToDivPixel(point)
        pixelpoint.x += moveX
        pixelpoint.y += moveY

        point = projection.fromDivPixelToLatLng(pixelpoint)

        this.googleMaps.map.setCenter(point)
      }
    }

    return scrolled
  }
  /**
   * Save the Region ID that was clicked before starting map scroll.
   * It is used to trigger .regionClickHandler() later if scroll was less than 5px
   */
  setObjectClickedBeforeScroll(object: Region | Marker | MarkerCluster): void {
    this.objectClickedBeforeScroll = object
  }
  /**
   * Event hanlder
   * @param _e
   * @param mapsvg
   * @private
   */
  touchStart(_e: JQuery.TouchEventBase, mapsvg: MapSVGMap): void {
    _e.preventDefault()

    // stop scroll and cancel click event
    if (this.scrollStarted) {
      this.scrollEnd(_e, mapsvg, true)
    }
    const e = _e.originalEvent

    if (this.options.zoom.fingers && e.touches && e.touches.length == 2) {
      // this.touchZoomStartViewBox = this.viewBox;
      // this.touchZoomStartScale =  this.scale;
      this.touchZoomStart = true
      this.scaleDistStart = Math.hypot(
        e.touches[0].pageX - e.touches[1].pageX,
        e.touches[0].pageY - e.touches[1].pageY,
      )
    } else if (e.touches && e.touches.length == 1) {
      this.scrollStart(_e, mapsvg)
    }

    $(document)
      .on("touchmove.scroll.mapsvg", (e: JQuery.TouchEventBase) => {
        e.preventDefault()
        this.touchMove(e, this)
      })
      .on("touchend.scroll.mapsvg", (e: JQuery.TouchEventBase) => {
        e.preventDefault()
        this.touchEnd(e, this)
      })
  }
  /**
   * Event hanlder
   * @param {JQuery.TriggeredEvent} _e
   * @param mapsvg
   * @private
   */
  touchMove(_e: JQuery.TouchEventBase, mapsvg: MapSVGMap): void {
    _e.preventDefault()
    const e = _e.originalEvent

    if (this.options.zoom.fingers && e.touches && e.touches.length == 2) {
      if (!env.getDevice().ios) {
        // e.scale is present on iOS devices only so we need to add "ts-ignore"
        // @ts-ignore
        e.scale =
          Math.hypot(
            e.touches[0].pageX - e.touches[1].pageX,
            e.touches[0].pageY - e.touches[1].pageY,
          ) / this.scaleDistStart
      }

      // e.scale is present on iOS devices only so we need to add "ts-ignore"
      // @ts-ignore
      if (e.scale != 1 && this.canZoom) {
        // @ts-ignore
        const d = e.scale > 1 ? 1 : -1

        const cx =
          e.touches[0].pageX >= e.touches[1].pageX
            ? e.touches[0].pageX -
              (e.touches[0].pageX - e.touches[1].pageX) / 2 -
              $(this.containers.map).offset().left
            : e.touches[1].pageX -
              (e.touches[1].pageX - e.touches[0].pageX) / 2 -
              $(this.containers.map).offset().left
        const cy =
          e.touches[0].pageY >= e.touches[1].pageY
            ? e.touches[0].pageY -
              (e.touches[0].pageY - e.touches[1].pageY) -
              $(this.containers.map).offset().top
            : e.touches[1].pageY -
              (e.touches[1].pageY - e.touches[0].pageY) -
              $(this.containers.map).offset().top

        const center = this.converter.convertPixelToSVG(new ScreenPoint(cx, cy))

        if (d > 0) this.zoomIn(center)
        else this.zoomOut(center)
      }
    } else if (e.touches && e.touches.length == 1) {
      this.scrollMove(_e)
    }
  }
  /**
   * Event hanlder
   * @param _e
   * @param mapsvg
   * @private
   */
  touchEnd(_e: JQuery.TouchEventBase, mapsvg: MapSVGMap): void {
    _e.preventDefault()
    const e = _e.originalEvent
    if (this.touchZoomStart) {
      this.touchZoomStart = false
    } else if (this.scrollStarted) {
      this.scrollEnd(_e, mapsvg)
    }

    $(document).off("touchmove.scroll.mapsvg")
    $(document).off("touchend.scroll.mapsvg")
  }
  /**
   * Returns array of IDs of selected Regions
   * @returns {String[]}
   */
  getSelected(): string[] {
    return this.selected_id
  }
  /**
   * Selects a Region
   * @param {Region|string} id - Region or ID
   * @param {bool} skipDirectorySelection
   * @returns {boolean}
   */
  selectRegion(id: string | Region, skipDirectorySelection?: boolean): void {
    let region: Region

    // this.hidePopover();
    if (typeof id == "string") {
      region = this.getRegion(id)
    } else {
      region = id
    }
    if (!region) return

    let ids: Array<string>

    if (this.options.multiSelect && !this.editRegions.on) {
      if (region.selected) {
        this.deselectRegion(region)
        if (!skipDirectorySelection && this.options.menu.on) {
          if (this.options.menu.source == "database") {
            if (region.objects && region.objects.length) {
              ids = region.objects.map((obj: Model) => {
                return obj.getData().id.toString()
              })
            }
          } else {
            ids = [region.id]
          }
          this.controllers.directory?.deselectItems()
        }

        return
      }
    } else if (this.selected_id.length > 0) {
      this.deselectAllRegions()
      if (!skipDirectorySelection && this.options.menu.on) {
        if (this.options.menu.source == "database") {
          if (region.objects && region.objects.length) {
            ids = region.objects.map((obj: Model) => {
              return obj.getData().id.toString()
            })
          }
        } else {
          ids = [region.id]
        }
        this.controllers.directory?.deselectItems()
      }
    }

    this.unhighlightRegions()

    // Remove the region from highlightedRegions array if present
    const index = this.highlightedRegions.indexOf(region)
    if (index > -1) {
      region.unhighlight()
      this.highlightedRegions.splice(index, 1)
    }
    this.selected_id.push(region.id)
    region.select()

    const skip = this.options.actions.region.click.filterDirectory

    if (
      !skip &&
      !skipDirectorySelection &&
      this.options.menu.on &&
      this.controllers &&
      this.controllers.directory
    ) {
      if (this.options.menu.source == "database") {
        if (region.objects && region.objects.length) {
          ids = region.objects.map((obj: Model) => {
            return obj.getData().id.toString()
          })
        } else {
          ids = [region.id]
        }
      } else {
        ids = [region.id]
      }
      this.controllers.directory?.selectItems(ids)
    }

    if (
      this.options.actions.region.click.addIdToUrl &&
      !this.options.actions.region.click.showAnotherMap
    ) {
      window.location.hash = "/m/r/" + region.id
      // history.replaceState(null, null, region.id);
      // history.replaceState("", document.title, window.location.pathname
      //     + window.location.search + '#m_'+region.id);
    }
  }
  /**
   * Deselects all Regions
   */
  deselectAllRegions(): void {
    $.each(this.selected_id, (index, id) => {
      this.deselectRegion(this.getRegion(id))
    })
  }
  /**
   * Deselects one Region
   * @param {Region} region
   */
  deselectRegion(region: Region): void {
    if (!region) region = this.getRegion(this.selected_id[0])
    if (region) {
      region.deselect()
      const i = $.inArray(region.id, this.selected_id)
      this.selected_id.splice(i, 1)
    }
    if (this.options.actions.region.click.addIdToUrl) {
      if (window.location.hash.indexOf(region.id) !== -1) {
        history.replaceState(null, null, " ")
      }
    }
  }
  /**
   * Highlights an array of Regions (used on mouseover)
   * @param {Region[]} regions
   */
  highlightRegions(regions: Region[]): void {
    regions.forEach((region) => {
      if (region && !region.selected && !region.disabled) {
        this.highlightedRegions.push(region)
        region.highlight()
      }
    })
  }
  /**
   * Unhighlights all Regions  (used on mouseout)
   */
  unhighlightRegions(): void {
    this.highlightedRegions.forEach((region: Region) => {
      if (region && !region.selected && !region.disabled) region.unhighlight()
    })
    this.highlightedRegions = []
  }
  /**
   * Selects a Marker
   * @param {Marker} marker
   */
  selectMarker(marker: Marker): void {
    if (!marker.isMarker()) return

    this.deselectAllMarkers()
    marker.select()
    this.selected_marker = marker

    $(this.layers.markers).addClass("mapsvg-with-marker-active")

    if (this.options.menu.on && this.options.menu.source == "database") {
      this.controllers.directory?.deselectItems()
      this.controllers.directory?.selectItems(marker.object.getData().id)
    }
  }

  /**
   * Deselects all Markers
   */
  deselectAllMarkers(): void {
    this.selected_marker && this.selected_marker.deselect()
    $(this.layers.markers).removeClass("mapsvg-with-marker-active")
  }

  /**
   * Deselects one Marker
   * @param {Marker} marker
   */
  deselectMarker(marker: Marker): void {
    if (marker) {
      marker.deselect()
    }
  }
  /**
   * Highlight marker
   * @param {Marker} marker
   */
  highlightMarker(marker: Marker): void {
    $(this.layers.markers).addClass("mapsvg-with-marker-hover")
    marker.highlight()
    this.highlighted_marker = marker
  }
  /**
   * Unhighlights all Regions  (used on mouseout)
   */
  unhighlightMarker(): void {
    $(this.layers.markers).removeClass("mapsvg-with-marker-hover")
    this.highlighted_marker && this.highlighted_marker.unhighlight()
  }
  /**
   * Converts mouse pointer coordinates to SVG coordinates
   * @param {object} e - Event object
   * @returns {Array} - [x,y] SVG coordinates
   */
  convertMouseToSVG(e: JQuery.TriggeredEvent): SVGPoint {
    const mc = getMouseCoords(e)
    const x = mc.x - $(this.containers.map).offset().left
    const y = mc.y - $(this.containers.map).offset().top
    const screenPoint = new ScreenPoint(x, y)
    return this.converter.convertPixelToSVG(screenPoint)
  }

  /**
   * Converts map geo-boundaries to viewBox
   * @param sw
   * @param ne
   * @returns {*[]}
   *
   * @private
   * @deprecated
   */
  /*
    convertGeoBoundsToViewBox (geoViewBox: GeoViewBox): ViewBox {
        //sw, ne

        var lat = parseFloat(coords[0]);
        var lon = parseFloat(coords[1]);
        var x = (lon - this.geoViewBox.leftLon) * (this.svgDefault.viewBox.width / this.converter.mapLonDelta);

        var lat = lat * 3.14159 / 180;
        // var worldMapWidth = ((this.svgDefault.width / this.converter.mapLonDelta) * 360) / (2 * 3.14159);
        var worldMapWidth = ((this.svgDefault.viewBox.width / this.converter.mapLonDelta) * 360) / (2 * 3.14159);
        var mapOffsetY = (worldMapWidth / 2 * Math.log((1 + Math.sin(this.mapLatBottomDegree)) / (1 - Math.sin(this.mapLatBottomDegree))));
        var y = this.svgDefault.viewBox.height - ((worldMapWidth / 2 * Math.log((1 + Math.sin(lat)) / (1 - Math.sin(lat)))) - mapOffsetY);

        x += this.svgDefault.viewBox.x;
        y += this.svgDefault.viewBox.y;

        return [x, y];
    }
    */

  /**
   * For choropleth map: returns color for given number value
   * @param {number} choroplethValue - Number value
   * @returns {{r: number, g: number, b: number, a: number}} - Color values, 0-255.
   */
  pickChoroplethColor(choroplethValue: number): { r: number; g: number; b: number; a: number } {
    const w =
      (choroplethValue - this.options.choropleth.coloring.gradient.values.min) /
      this.options.choropleth.coloring.gradient.values.maxAdjusted
    const rgba = {
      r: Math.round(
        this.options.choropleth.coloring.gradient.colors.diffRGB.r * w +
          this.options.choropleth.coloring.gradient.colors.lowRGB.r,
      ),
      g: Math.round(
        this.options.choropleth.coloring.gradient.colors.diffRGB.g * w +
          this.options.choropleth.coloring.gradient.colors.lowRGB.g,
      ),
      b: Math.round(
        this.options.choropleth.coloring.gradient.colors.diffRGB.b * w +
          this.options.choropleth.coloring.gradient.colors.lowRGB.b,
      ),
      a: Math.round(
        this.options.choropleth.coloring.gradient.colors.diffRGB.a * w +
          this.options.choropleth.coloring.gradient.colors.lowRGB.a,
      ),
    }
    return rgba
  }
  /**
   * Checks if Region should be disabled
   * @param {string} id - Region ID
   * @param {string} svgfill - Region color ("fill" attribute)
   * @returns {boolean} - "true" if Region should be disabled.
   */
  isRegionDisabled(id: string, svgfill: string): boolean {
    if (this.options.regions[id] && (this.options.regions[id].disabled || svgfill == "none")) {
      return true
    } else if (
      (this.options.regions[id] == undefined ||
        utils.numbers.parseBoolean(this.options.regions[id].disabled)) &&
      (this.options.disableAll || svgfill == "none" || id == "labels" || id == "Labels")
    ) {
      return true
    } else {
      return false
    }
  }

  

  

  markerClusterClickHandler(e: JQuery.TriggeredEvent, markerCluster: MarkerCluster): void {
    this.objectClickedBeforeScroll = null
    if (this.eventsPreventList["click"]) return
    this.zoomTo(markerCluster.markers)
    return
  }
  regionClickHandler(e: JQuery.TriggeredEvent, region: Region): void {
    this.objectClickedBeforeScroll = null
    if (this.eventsPreventList["click"]) return

    if (this.editRegions.on) {
      this.regionEditHandler.call(region)
      return
    }

    const actions = this.options.actions

    if (actions.region.click.zoom) {
      this.zoomTo(
        region,
        typeof actions.region.click.zoomToLevel !== "undefined"
          ? parseInt(actions.region.click.zoomToLevel)
          : undefined,
      )
    }

    if (actions.region.click.filterDirectory && this.options.database.on) {
      const query = new Query({
        filters: {
          regions: {
            table_name: this.regionsRepository.getSchema().name,
            region_ids: [region.id],
          },
        },
      })

      this.objectsRepository.query.resetFilters()
      this.setFilterOut()

      this.objectsRepository.find(query).done(() => {
        if (this.controllers.popover) {
          this.controllers.popover.setState(region.getData())
        }
        if (this.controllers.detailsView) {
          this.controllers.detailsView.setState(region.getData())
        }
      })
      this.updateFiltersState()
    }

    if (actions.region.click.showDetails) {
      const template = this.options.actions.region.click.detailsViewTemplate
      this.loadDetailsView(region, template)
    }

    if (actions.region.click.showPopover) {
      const template = this.options.actions.region.click.popoverTemplate
      const delay = actions.region.click.zoom ? 400 : 0
      setTimeout(() => {
        this.showPopover(region, template)
      }, delay)
    } else if (e && e.type.indexOf("touch") !== -1 && actions.region.touch.showPopover) {
      const template = this.options.actions.region.click.popoverTemplate
      const delay = actions.region.click.zoom ? 400 : 0
      setTimeout(() => {
        this.showPopover(region, template)
      }, 400)
    }

    

    

    this.selectRegion(region.id)
    this.events.trigger("click.region", e, { region })
  }
  markerClickHandler(e: JQuery.TriggeredEvent, marker: Marker): void {
    this.objectClickedBeforeScroll = null
    if (this.eventsPreventList["click"]) return

    const actions = this.options.actions

    this.selectMarker(marker)
    const passingObject = marker.object

    if (actions.marker.click.zoom) {
      this.zoomTo(
        marker,
        typeof actions.marker.click.zoomToLevel !== "undefined"
          ? parseInt(actions.marker.click.zoomToLevel)
          : undefined,
      )
    }

    if (actions.marker.click.filterDirectory && this.options.database.on) {
      const query = new Query({ filters: { id: marker.object.getData().id } })
      this.objectsRepository.find(query)
      this.updateFiltersState()
    }

    if (actions.marker.click.showDetails) {
      const template = this.options.actions.marker.click.detailsViewTemplate
      this.loadDetailsView(passingObject, template)
    }

    if (actions.marker.click.showPopover) {
      const template = this.options.actions.marker.click.popoverTemplate
      const delay = actions.marker.click.zoom && this.googleMaps.map ? 700 : 0
      setTimeout(() => {
        this.showPopover(passingObject, template)
      }, delay)
    } else if (e && e.type.indexOf("touch") !== -1 && actions.marker.touch.showPopover) {
      const template = this.options.actions.marker.click.popoverTemplate
      const delay = actions.marker.click.zoom && this.googleMaps.map ? 700 : 0
      setTimeout(() => {
        this.showPopover(passingObject, template)
      }, delay)
    }
    
    

    if (
      this.options.actions.marker.click.addIdToUrl &&
      !this.options.actions.marker.click.showAnotherMap
    ) {
      window.location.hash = "/m/db/" + marker.object.getData().id
    }
    this.events.trigger("click.marker", e, { marker })
  }
  /**
   * Checks if file exists by provided URL.
   * @param {string} url
   * @returns {boolean}
   *
   * @private
   */
  fileExists(url: string): boolean {
    if (url.substr(0, 4) == "data") return true
    const http = new XMLHttpRequest()
    http.open("HEAD", url, false)
    http.send()
    return http.status != 404
  }

  /**
   * Hides all markers
   * @private
   */
  hideMarkers(): void {
    this.markers.forEach(function (marker) {
      marker.hide()
    })
    $(this.containers.wrap).addClass("mapsvg-edit-marker-mode")
  }
  /**
   * Hides all markers except one.
   * Used in Map Editor.
   * @param {string} id - ID of the Marker
   * @private
   */
  hideMarkersExceptOne(id: string): void {
    this.markers.forEach(function (marker) {
      if (typeof id === "undefined" || (marker.object && id !== String(marker.id))) {
        marker.hide()
      }
    })

    $(this.containers.wrap).addClass("mapsvg-edit-marker-mode")
  }
  /**
   * Shows all markers after .hideMarkersExceptOne()
   * Used in Map Editor.
   * @private
   */
  showMarkers(): void {
    this.markers.forEach(function (m) {
      m.show()
    })
    this.deselectAllMarkers()
    // $(this.containers.wrap).removeClass('mapsvg-clusters-hidden');
    $(this.containers.wrap).removeClass("mapsvg-edit-marker-mode")
  }
  /**
   * Event handler that creates marker on click on the map.
   * Used in the Map Editor.
   * @param {object} e - Event object
   * @returns {boolean}
   */
  markerAddClickHandler(e: JQuery.TriggeredEvent): void {
    // Don't add marker if marker was clicked
    if ($(e.target).hasClass("mapsvg-marker")) return
    const svgPoint = this.getSvgPointAtClick(e)
    const geoPoint = this.isGeo() ? this.converter.convertSVGToGeo(svgPoint) : null

    if (!$.isNumeric(svgPoint.x) || !$.isNumeric(svgPoint.y)) return

    if (this.editingMarker) {
      if (geoPoint) {
        this.editingMarker.location.setGeoPoint(geoPoint)
      } else {
        this.editingMarker.location.setSvgPoint(svgPoint)
      }
      return
    }
    const location = new Location({
      svgPoint: svgPoint,
      geoPoint: geoPoint,
      img: this.options.defaultMarkerImage,
    })

    const marker = new Marker({
      location: location,
      mapsvg: this,
    })
    this.markerAdd(marker)

    this.markerEditHandler && this.markerEditHandler.call(location, location)
  }

  private getSvgPointAtClick(
    e: JQuery.TriggeredEvent | JQuery.MouseEventBase | MouseEvent,
  ): SVGPoint {
    const mc = getMouseCoords(e)
    const x = mc.x - $(this.containers.map).offset().left
    const y = mc.y - $(this.containers.map).offset().top
    const screenPoint = new ScreenPoint(x, y)
    return this.converter.convertPixelToSVG(screenPoint)
  }

  /**
   * Sets default marker image.
   * Used in Map Editor.
   * @param {string} src - Image URL
   * @private
   */
  setDefaultMarkerImage(src: string): void {
    this.options.defaultMarkerImage = src
  }

  /**
   * Checks and remebers if marker images should be set by a field value
   * @private
   */
  setMarkerImagesDependency(): void {
    this.locationField = this.objectsRepository.schema.getFieldByType("location")
    // @ts-ignore
    if (
      this.locationField &&
      this.locationField.markersByFieldEnabled &&
      this.locationField.markerField &&
      Object.values(this.locationField.markersByField).length > 0
    ) {
      this.setMarkersByField = true
    } else {
      this.setMarkersByField = false
    }
  }

  /**
   * Returns default marker image or marker image by field value if such option is enabled
   * @param {Number|String} fieldValue
   * @returns {String} URL of marker image
   * @private
   */
  getMarkerImage(fieldValueOrObject: any, location?: Location): string {
    let fieldValue

    if (this.setMarkersByField) {
      if (typeof fieldValueOrObject === "object") {
        // @ts-ignore
        fieldValue = fieldValueOrObject[this.locationField.markerField]
        if (this.locationField.markerField === "regions") {
          fieldValue = fieldValue[0] && fieldValue[0].id
        } else if (typeof fieldValue === "object" && fieldValue.length) {
          fieldValue = fieldValue[0].value
        }
      } else {
        fieldValue = fieldValueOrObject
      }
      // @ts-ignore
      if (this.locationField.markersByField[fieldValue]) {
        // @ts-ignore
        return this.locationField.markersByField[fieldValue]
      }
    }

    return location && location.imagePath
      ? location.imagePath
      : this.options.defaultMarkerImage
        ? this.options.defaultMarkerImage
        : mapsvgCore.routes.root + "markers/_pin_default.png"
  }

  /**
   * Sets on/off "edit markers" mode
   * @param {bool} on - on/off
   * @param {bool} clickAddsMarker - defines if click on the map should add a marker
   * @private
   */
  setMarkersEditMode(on: boolean, clickAddsMarker: boolean): void {
    this.editMarkers.on = utils.numbers.parseBoolean(on)
    this.clickAddsMarker = this.editMarkers.on
    this.setEventHandlers()
  }

  /**
   * Sets on/off "edit regions" mode
   * Used in Map Editor.
   * @param {bool} on - on/off
   *
   * @private
   */
  setRegionsEditMode(on: boolean): void {
    this.editRegions.on = utils.numbers.parseBoolean(on)
    this.deselectAllRegions()
    this.setEventHandlers()
  }

  /**
   * Enables edit mode (which means that the map is going to be shown in the Map Editor)
   * Used in the Map Editor.
   * @param {bool} on
   * @private
   */
  setEditMode(on: boolean): void {
    this.inBackend = on
  }

  /**
   * Enables "Edit objects" mode
   * Used in the Map Editor.
   * @param {bool} on
   * @private
   */
  setDataEditMode(on: boolean): void {
    this.editData.on = utils.numbers.parseBoolean(on)
    this.deselectAllRegions()
    this.setEventHandlers()
  }

  /**
   * Downloads SVG file.
   * @private
   */
  download() {
    window.location.href = window.location.origin + this.options.source
  }

  /**
   * Adjusts popover position. User on zoom and when map container is resized.
   *
   */
  popoverAdjustScreenPosition(): void {
    if (this.controllers.popover instanceof PopoverController) {
      this.controllers.popover.adjustScreenPosition()
    }
  }

  /**
   * Moves popover position. User on zoom and when map container is resized.
   *
   * @param {number} deltaX
   * @param {number} deltaY
   */
  popoverMoveScreenPositionBy(deltaX: number, deltaY: number): void {
    if (this.controllers.popover instanceof PopoverController) {
      this.controllers.popover.moveSrceenPositionBy(deltaX, deltaY)
    }
  }

  /**
   * Shows a popover for provided Region or DB Object.
   * @param {Region|object} object - Region or DB Object
   *
   * @example
   * var region = mapsvg.getRegion("US-TX");
   * mapsvg.showPopover(region);
   */
  showPopover(object: Region | Model, templateName?: string): void {
    const mapObject =
      "type" in object && object.isRegion()
        ? object
        : object["location"] && object["location"].marker
          ? object["location"].marker
          : null
    if (!mapObject) return

    let point
    if (mapObject.isMarker()) {
      point = mapObject.svgPoint
    } else {
      point = mapObject.getCenterSVG()
    }

    const template = templateName
? this.options.templates[templateName]
: "type" in object && object.isRegion()
? this.options.templates.popoverRegion
: this.options.templates.popoverMarker
    

    if (this.controllers.popover) this.controllers.popover.destroy()
    this.controllers.popover = new PopoverController({
      map: this,
      parent: this,
      container: this.layers.popovers,
      point: point,
      yShift:
        "type" in mapObject && mapObject.isMarker()
          ? mapObject.height
          : mapObject.isRegion() && mapObject.label
            ? mapObject.label.offsetHeight / 2
            : 0,
      template,
      state: object.getData(),
      mapObject: mapObject,
      scrollable: true,
      fullscreen: {
        sm: this.options.popovers.mobileFullscreen,
        md: this.options.popovers.mobileFullscreen,
        lg: false,
      },
      withToolbar: !(isPhone() && this.options.popovers.mobileFullscreen),
      styles: {
        width: this.options.popovers.width + "px",
        backgroundColor: this.options.colors.popover,
        maxWidth: this.options.popovers.maxWidth + "%",
        maxHeight:
          (parseInt(this.options.popovers.maxHeight + "") * $(this.layers.popovers).outerHeight()) /
            100 +
          "px",
      },
      middleware: this.middlewares.middlewares.filter((m) => m.name === MiddlewareType.RENDER),
      events: {
        afterShow: (event) => {
          const { controller } = event.data
          if (this.options.popovers.centerOn) {
            const shift = controller.containers.main.offsetHeight / 2
            if (
              this.options.popovers.centerOn &&
              !(isPhone() && this.options.popovers.mobileFullscreen)
            ) {
              this.centerOn(mapObject, shift)
            }
          }
          this.popoverShowingFor = mapObject
        },
        afterClose: (event) => {
          const { controller } = event.data
          this.options.popovers.resetViewboxOnClose && this.viewBoxReset("custom")
          this.popoverShowingFor = null
          if ("type" in mapObject && mapObject.isRegion()) {
            this.deselectRegion(mapObject)
          } else if ("type" in mapObject && mapObject.isMarker()) {
            this.deselectAllMarkers()
          }
          this.controllers &&
            this.controllers.directory &&
            this.controllers.directory?.deselectItems()
          $("body").off(`mouseup.popover.mapsvg-${this.id} touchend.popover.mapsvg-${this.id}`)
        },
        resize: (event) => {
          const { controller } = event.data
          if (this.options.popovers.centerOn) {
            const shift = controller.containers.main.offsetHeight / 2
            if (
              this.options.popovers.centerOn &&
              !(isPhone() && this.options.popovers.mobileFullscreen)
            ) {
              this.centerOn(mapObject, shift)
            }
          }
        },
      },
    })
    this.controllers.popover.init()
    this.controllers.popover.open()
    setTimeout(() => {
      $("body").on(`mouseup.popover.mapsvg-${this.id} touchend.popover.mapsvg-${this.id}`, (e) => {
        if (
          this.isScrolling ||
          $(e.target).closest(".mapsvg-directory").length ||
          $(e.target).closest(".mapsvg-popover").length ||
          $(e.target).hasClass("mapsvg-btn-map")
        )
          return
        this.controllers.popover.close()
      })
    }, 50)
  }

  /**
   * Hides the popover
   */
  hidePopover(): void {
    this.controllers.popover && this.controllers.popover.close()
  }

  /**
   * Event handler that catches clicks outside of the popover and closes the popover.
   * @param {object} e - Event object
   */
  popoverOffHandler(e: JQuery.TriggeredEvent): void {
    if (
      this.isScrolling ||
      $(e.target).closest(".mapsvg-popover").length ||
      $(e.target).hasClass("mapsvg-btn-map")
    )
      return
    // this.controllers.popover && this.controllers.popover.close()
  }

  /**
   * Mouseover event handler for Regions and Markers
   * @param {object} e - Event object
   * @param {Marker|Region} object
   * @private
   */
  mouseOverHandler(e: JQuery.TriggeredEvent, object: Marker | Region | MarkerCluster): void {
    if (this.eventsPreventList["mouseover"]) {
      return
    }

    if (object.isMarker()) {
      if (this.options.actions.marker.mouseover.showTooltip) {
        const template = this.options.actions.marker.mouseover.tooltipTemplate
        
        this.showTooltip(object, template)
      }
    }

    if (object.isRegion()) {
      if (this.options.actions.region.mouseover.showTooltip) {
        const template = this.options.actions.region.mouseover.tooltipTemplate
        
        this.showTooltip(object, template)
      }
    }

    let ids: any[]
    if (this.options.menu.on && this.controllers.directory) {
      if (this.options.menu.source == "database") {
        if (object.isRegion() && object.objects.length) {
          ids = object.objects.map((obj) => {
            return obj.getData().id
          })
        }
        if (object.isMarker()) {
          ids = object.object ? [object.object.getData().id] : []
        }
      } else {
        if (object.isRegion()) {
          ids = [object.id]
        }
        if (
          object.isMarker() &&
          object.object.getData().regions &&
          object.object.getData().regions.length
        ) {
          ids = object.object.getData().regions.map((obj) => {
            return obj.id
          })
        }
      }
      this.controllers.directory?.highlightItems(ids)
    }

    if (object.isRegion()) {
      if (!object.selected) object.highlight()
      this.events.trigger("mouseover.region", e, { region: object })
    } else {
      this.highlightMarker(<Marker>object)
      this.events.trigger("mouseover.marker", e, { marker: object })
    }
  }

  public showTooltip(object: Marker | Region | MarkerCluster, templateName?: string): void {
    let name, _object
    if (object.isRegion()) {
      name = "region"
      _object = object
    } else if (object.isMarker()) {
      name = "marker"
      _object = object.object
    } else {
      name = "cluster"
      _object = object
    }

    if (_object != null && this.popoverShowingFor !== object) {
      const camelName = `tooltip${ucfirst(name)}`
      if (this.controllers.tooltips[camelName]) {
        this.controllers.tooltips[camelName].destroy()
      }
      const template = templateName
        ? this.options.templates[templateName]
        : this.options.templates[camelName]
      const tooltip = new Tooltip({
        map: this,
        parent: this,
        middleware: this.middlewares.middlewares.filter((m) => m.name === MiddlewareType.RENDER),
        state: _object.getData(),
        template,
        container: this.containers.map,
        styles: {
          backgroundColor: this.options.colors.tooltip,
          minWidth: this.options.tooltips.minWidth ? this.options.tooltips.minWidth + "px" : "auto",
          maxWidth: this.options.tooltips.maxWidth ? this.options.tooltips.maxWidth + "px" : "auto",
        },
        positionToCursor: this.options.tooltips.position,
      })
      object.tooltip = tooltip
      tooltip.init()
      this.controllers.tooltips[camelName] = tooltip
    }
  }

  /**
   * Mouseout event handler for Regions and Markers
   * @param {object} e - Event object
   * @param {Marker|Region} object
   * @private
   */
  mouseOutHandler(e: JQuery.TriggeredEvent, object: Marker | Region | MarkerCluster): void {
    if (this.eventsPreventList["mouseout"]) {
      return
    }

    if (object.tooltip) {
      object.tooltip.destroy()
      object.tooltip = null
    }

    if (object.isRegion()) {
      object.unhighlight()
      this.events.trigger("mouseout.region", e, { region: object })
    } else {
      this.unhighlightMarker()
      this.events.trigger("mouseout.marker", e, { marker: object })
    }

    let ids: any[]
    if (this.options.menu.on && this.controllers.directory) {
      if (this.options.menu.source == "database") {
        if (object.isMarker()) {
          const marker = <Marker>object
          ids = marker.object ? [marker.object.getData().id] : []
        }
      }
      this.controllers.directory?.unhighlightItems()
    }
  }

  /**
   * Prevents provided event from being fired
   * @param {string} event - Event name
   * @private
   */
  eventsPrevent(event: string): void {
    this.eventsPreventList[event] = true
  }

  /**
   * Restores event disabled by .eventsPrevent()
   * @param {string} event - Event name
   * @private
   */
  eventsRestore(event: string): void {
    if (event) {
      this.eventsPreventList[event] = false
    } else {
      this.eventsPreventList = {}
    }
  }

  /**
   * Connect object to regions
   * @param {Model} object - Object to connect
   */
  linkObjectToRegions(object: Model): void {
    if (this.regions.length === 0) {
      return
    }
    const regionsInObject = object.getRegionsForTable(this.regionsRepository.schema?.name)
    const regionIds = regionsInObject.map((region) => region.id)

    if (regionIds.length > 0) {
      this.regions
        .filter((region) => regionIds.includes(region.id))
        .forEach((region) => {
          if (!region.objects.get(object.getData().id)) {
            region.objects.push(object)
          }
        })
    }
  }

  addObject(object: Model): void {
    this.addLocation(object)
  }

  objectsLoadedToMapHandler(): void {
    this.mayBeFitMarkers()

    this.fixMarkersWorldScreen()

    this.loadDirectory()
    if (this.options.labelsMarkers.on) {
      this.setLabelsMarkers()
    }
    // Check if there's a call to "{{objects}}" inside of the labelRegion template
    // and if it's found then reload Region Labels
    if (this.options.templates.labelRegion.indexOf("{{objects") !== -1) {
      this.setLabelsRegions()
    }
    if (
      this.options.filters.on &&
      this.options.filters.source === "database" &&
      this.controllers.filters &&
      this.controllers.filters.hideFilters
    ) {
      this.controllers.filters.setFiltersCounter()
    }
    this.hideLoadingMessage()

    this.updateFiltersState()
    if (this.options.choropleth?.on) {
      this.setChoropleth()
    }
    this.events.trigger("afterLoadObjects", {
      objects: this.objectsRepository.getLoaded(),
    })
  }

  setRepositoryEvents() {
    this.eventsBaseInitialized = true

    this.objectsRepository.events.on(RepositoryEvent.BEFORE_LOAD, () => {
      this.showLoadingMessage()
      if (typeof this.firstDataLoad === "undefined") {
        this.firstDataLoad = true
      } else if (this.firstDataLoad === true) {
        this.firstDataLoad = false
      }
    })
    this.objectsRepository.events.on(RepositoryEvent.FAILED_LOAD, () => {
      this.hideLoadingMessage()
      console.error("Could not load objects")
    })

    this.objectsRepository.events.on(
      RepositoryEvent.AFTER_LOAD,
      (event: EventWithData<{ repository: Repository }>) => {
        const { repository } = event.data
        this.fitOnDataLoadDone = false
        this.setGlobalDistanceFilter()
        this.deleteMarkers()
        this.deleteClusters()

        repository.objects
          .filter((object) => object.getData().regions && object.getData().regions.length > 0)
          .forEach((object) => this.linkObjectToRegions(object))

        repository.objects
          .filter((object) => object.getData().location)
          .forEach((object) => this.addObject(object))

        if (this.options.clustering.on) {
          this.startClusterizer()
        }
        this.objectsLoadedToMapHandler()
      },
    )

    this.objectsRepository.schema?.events.on(SchemaEventType.UPDATE, () => {
      this.objectsRepository.find()
    })
    this.objectsRepository.events.on(
      RepositoryEvent.AFTER_UPDATE,
      (event: EventWithData<{ object: Model; updatedFields: Record<string, unknown> }>) => {
        const { object, updatedFields } = event.data
        if (updatedFields.regions) {
          this.linkObjectToRegions(object)
        }
        this.objectsLoadedToMapHandler()
      },
    )
    this.objectsRepository.events.on(
      RepositoryEvent.AFTER_CREATE,
      (event: EventWithData<{ object: Model }>) => {
        const { object } = event.data
        this.linkObjectToRegions(object)
        this.objectsLoadedToMapHandler()
      },
    )
    this.objectsRepository.events.on(
      RepositoryEvent.AFTER_DELETE,
      (event: EventWithData<{ id: number }>) => {
        const { id } = event.data
        this.regions.forEach((region) => {
          const objectIndex = region.objects.findIndex((object) => object.getData().id === id)
          if (objectIndex !== -1) {
            region.objects.delete(objectIndex)
          }
        })
        this.objectsLoadedToMapHandler()
      },
    )

    this.regionsRepository.events.on(RepositoryEvent.BEFORE_LOAD, () => {
      this.showLoadingMessage()
    })
    this.regionsRepository.events.on(RepositoryEvent.FAILED_LOAD, () => {
      this.hideLoadingMessage()
      console.error("Could not load regions")
    })
    this.regionsRepository.events.on(RepositoryEvent.AFTER_LOAD, () => {
      this.hideLoadingMessage()
      this.reloadRegionsFull()
      this.events.trigger("afterLoadRegions", { regions: this.regions })
    })
    this.regionsRepository.events.on(RepositoryEvent.AFTER_UPDATE, () => {
      this.reloadRegionsFull()
    })
    this.regionsRepository.events.on(RepositoryEvent.AFTER_CREATE, () => {
      this.reloadRegionsFull()
    })
    this.regionsRepository.events.on(RepositoryEvent.AFTER_DELETE, () => {
      this.reloadRegionsFull()
    })
  }

  /**
   * Sets all event handlers
   * @private
   */
  setEventHandlers() {
    const _this = this

    $(_this.containers.map).off(".common.mapsvg")
    $(_this.containers.scrollpane).off(".common.mapsvg")
    $(document).off(".scroll.mapsvg")
    $(document).off(".scrollInit.mapsvg")

    if (_this.editMarkers.on) {
      $(_this.containers.map).on(
        "touchstart.common.mapsvg mousedown.common.mapsvg",
        ".mapsvg-marker",
        (e) => {
          e.originalEvent.preventDefault()
          const id = $(e.target.id).attr("id")
          const marker = this.markers.get(id)
          const startCoords = getMouseCoords(e)
          marker.drag(startCoords, _this.scale)

          /*
                marker.drag(startCoords, _this.scale, function() {
                    if (_this.mapIsGeo){
                        var svgPoint = new SVGPoint(this.x + this.width / 2, this.y + (this.height-1));
                        this.geoCoords = _this.converter.convertSVGToGeo(svgPoint);
                    }
                    //_this.markerEditHandler && _this.markerEditHandler.call(this,true);
                },function(){
                    //_this.markerEditHandler && _this.markerEditHandler.call(this);
                });

                 */
        },
      )
    }

    // REGIONS
    if (!_this.editMarkers.on) {
      $(_this.containers.map)
        .on("mouseover.common.mapsvg", ".mapsvg-region", function (e) {
          const id = $(this).attr("id")
          _this.mouseOverHandler.call(_this, e, _this.getRegion(id))
        })
        .on("mouseleave.common.mapsvg", ".mapsvg-region", function (e) {
          const id = $(this).attr("id")
          _this.mouseOutHandler.call(_this, e, _this.getRegion(id))
        })
    }
    if (!this.editRegions.on) {
      $(this.containers.map)
        .on("mouseover.common.mapsvg", ".mapsvg-marker", (e) => {
          const id = e.target.id
          this.mouseOverHandler.call(this, e, this.markers.get(id))
        })
        .on("mouseleave.common.mapsvg", ".mapsvg-marker", (e) => {
          const id = e.target.id
          this.mouseOutHandler.call(this, e, this.markers.get(id))
        })
    }

    if (_this.options.scroll.spacebar) {
      $(document).on("keydown.scroll.mapsvg", function (e) {
        if (
          !(
            document.activeElement.tagName === "INPUT" &&
            $(document.activeElement).attr("type") === "text"
          ) &&
          !_this.isScrolling &&
          e.keyCode == 32
        ) {
          e.preventDefault()
          $(_this.containers.map).addClass("mapsvg-scrollable")
          $(document)
            .on("mousemove.scrollInit.mapsvg", function (e: JQuery.TouchEventBase) {
              _this.isScrolling = true
              $(document).off("mousemove.scrollInit.mapsvg")
              _this.scrollStart(e, _this)
            })
            .on("keyup.scroll.mapsvg", function (e: JQuery.TouchEventBase) {
              if (e.keyCode == 32) {
                $(document).off("mousemove.scrollInit.mapsvg")
                $(_this.containers.map).removeClass("mapsvg-scrollable")
              }
            })
        }
      })
    } else if (!_this.options.scroll.on) {
      if (!_this.editMarkers.on) {
        $(_this.containers.map).on("touchstart.common.mapsvg", ".mapsvg-region", function (e) {
          _this.scroll.touchScrollStart = $(window).scrollTop()
        })
        $(_this.containers.map).on("touchstart.common.mapsvg", ".mapsvg-marker", function (e) {
          _this.scroll.touchScrollStart = $(window).scrollTop()
        })

        $(_this.containers.map).on(
          "touchstart.common.mapsvg mousedown.common.mapsvg",
          function (e: JQuery.TouchEventBase) {
            if (
              $(e.target).hasClass("mapsvg-popover") ||
              $(e.target).closest(".mapsvg-popover").length ||
              $(e.target).hasClass("mapsvg-details-container") ||
              $(e.target).closest(".mapsvg-details-container").length ||
              $(e.target).closest(".mapsvg-popover-container").length
            ) {
              // Prevent even dobule firing touchstart+mousedown on clicking popover close button
              if ($(e.target).hasClass("mapsvg-popover-close")) {
                if (e.type == "touchstart") {
                  if (e.cancelable) {
                    e.preventDefault()
                  }
                }
              }
              return
            }

            if (e.type == "touchstart") {
              if (e.cancelable) {
                e.preventDefault()
              }
            }

            if (
              _this.scroll.touchScrollStart ||
              _this.scroll.touchScrollStart !== $(window).scrollTop()
            ) {
              return
            }

            if (_this.isLinkClicked(e)) {
              _this.handleLinkClick(e)
              return true // Exit the handler after processing the link
            }

            // Handle region
            if (
              e.target &&
              $(e.target).attr("class") &&
              $(e.target).attr("class").indexOf("mapsvg-region") != -1
            ) {
              _this.regionClickHandler(e, _this.getRegion($(this).attr("id")))
            }

            if (
              e.target &&
              $(e.target).attr("class") &&
              $(e.target).attr("class").indexOf("mapsvg-marker") != -1 &&
              $(e.target).attr("class").indexOf("mapsvg-marker-cluster") === -1
            ) {
              const id = e.target.id
              _this.markerClickHandler.call(this, e, _this.markers.get(id))
            }

            if (
              e.target &&
              $(e.target).attr("class") &&
              $(e.target).attr("class").indexOf("mapsvg-marker-cluster") !== -1
            ) {
              const cluster = $(this).data("cluster")
              _this.zoomTo(cluster.markers)
            }
          },
        )

        $(_this.containers.map).on(
          "touchend.common.mapsvg mouseup.common.mapsvg",
          ".mapsvg-region",
          function (e) {
            if (e.cancelable) {
              e.preventDefault()
            }
            if (
              !_this.scroll.touchScrollStart ||
              _this.scroll.touchScrollStart === $(window).scrollTop()
            ) {
              _this.regionClickHandler(e, _this.getRegion($(this).attr("id")))
            }
          },
        )
        $(this.containers.map).on(
          "touchend.common.mapsvg mouseup.common.mapsvg",
          ".mapsvg-marker",
          (e) => {
            if (e.cancelable) {
              e.preventDefault()
            }
            if (
              !this.scroll.touchScrollStart ||
              this.scroll.touchScrollStart === $(window).scrollTop()
            ) {
              const id = e.target.id
              this.markerClickHandler.call(this, e, this.markers.get(id))
            }
          },
        )
        $(_this.containers.map).on(
          "touchend.common.mapsvg mouseup.common.mapsvg",
          ".mapsvg-marker-cluster",
          function (e) {
            if (e.cancelable) {
              e.preventDefault()
            }
            if (
              !_this.scroll.touchScrollStart ||
              _this.scroll.touchScrollStart == $(window).scrollTop()
            ) {
              const cluster = $(this).data("cluster")
              _this.zoomTo(cluster.markers)
            }
          },
        )
        // }
      } else {
        if (_this.clickAddsMarker)
          $(_this.containers.map).on("touchend.common.mapsvg mouseup.common.mapsvg", function (e) {
            // e.stopImmediatePropagation();
            if (e.cancelable) {
              e.preventDefault()
            }
            _this.markerAddClickHandler(e)
          })
      }
    } else {
      $(_this.containers.map).on(
        "touchstart.common.mapsvg mousedown.common.mapsvg",
        function (e: JQuery.TouchEventBase) {
          if (
            $(e.target).hasClass("mapsvg-popover") ||
            $(e.target).closest(".mapsvg-popover").length ||
            $(e.target).hasClass("mapsvg-details-container") ||
            $(e.target).closest(".mapsvg-details-container").length ||
            $(e.target).closest(".mapsvg-popover-container").length
          ) {
            // Prevent even dobule firing touchstart+mousedown on clicking popover close button
            if ($(e.target).hasClass("mapsvg-popover-close")) {
              if (e.type == "touchstart") {
                if (e.cancelable) {
                  e.preventDefault()
                }
              }
            }
            return
          }

          if (e.type == "touchstart") {
            if (e.cancelable) {
              e.preventDefault()
            }
          }

          let obj: any

          if (
            e.target &&
            $(e.target).attr("class") &&
            $(e.target).attr("class").indexOf("mapsvg-region") != -1
          ) {
            obj = _this.getRegion($(e.target).attr("id"))
            _this.setObjectClickedBeforeScroll(obj)
          } else if (
            e.target &&
            $(e.target).attr("class") &&
            $(e.target).attr("class").indexOf("mapsvg-marker") != -1 &&
            $(e.target).attr("class").indexOf("mapsvg-marker-cluster") === -1
          ) {
            if (_this.editMarkers.on) {
              return
            }
            const id = $(e.target).closest(".mapsvg-marker").attr("id") || e.target.id
            obj = _this.markers.get(id)
            _this.setObjectClickedBeforeScroll(obj)
          } else if (
            e.target &&
            $(e.target).attr("class") &&
            $(e.target).attr("class").indexOf("mapsvg-marker-cluster") != -1
          ) {
            if (_this.editMarkers.on) {
              return
            }
            obj = $(e.target).data("cluster")
            _this.setObjectClickedBeforeScroll(obj)
          }
          if (e.type == "mousedown") {
            _this.scrollStart(e, _this)
          } else {
            _this.touchStart(e, _this)
          }
        },
      )
    }

    //TODO Vyacheslav - переделать под новый choropleth
    /*
        $(_this.containers.map).on('mouseover.common.mapsvg', '.mapsvg-choropleth-gradient-wrap', function(e){
            let currentSegment = _this.options.choropleth.segments[$(e.target).parents('.mapsvg-choropleth-gradient-wrap').data('idx')];

            if (currentSegment.description){
                _this.showTooltip(currentSegment.description);
            }
        });
        */

    $(_this.containers.map).on(
      "mouseleave.common.mapsvg",
      ".mapsvg-choropleth-gradient-wrap",
      (e) => {
        this.controllers.tooltips.region.close()
      },
    )
  }

  setLabelsRegions(options?: any): void {
    options = options || this.options.labelsRegions
    options.on != undefined && (options.on = utils.numbers.parseBoolean(options.on))
    deepMerge(this.options, { labelsRegions: options })

    if (this.options.labelsRegions.on) {
      this.regions.forEach((region) => {
        if (!region.label) {
          region.label = document.createElement("div")
          region.label.className = "mapsvg-region-label"
          region.label.setAttribute("data-region-id", region.id)
          $(this.layers.labels).append(region.label)
        }
        try {
          $(region.label).html(this.templates.labelRegion(region.forTemplate()))
        } catch (err) {
          console.error('MapSVG: Error in the "Region Label" template')
        }
      })
      this.labelsRegionsAdjustScreenPosition()
    } else {
      this.regions.forEach((region) => {
        if (region.label) {
          $(region.label).remove()
          region.label = null
          delete region.label
        }
      })
    }
  }

  /**
   * Sets Marker labels
   * @param {object} options
   */
  setLabelsMarkers(options?: any): void {
    options = options || this.options.labelsMarkers
    options.on != undefined && (options.on = utils.numbers.parseBoolean(options.on))
    deepMerge(this.options, { labelsMarkers: options })

    if (this.options.labelsMarkers.on) {
      this.markers.forEach((marker: Marker) => {
        try {
          marker.setLabel(this.templates.labelMarker(marker.object.getData()))
        } catch (err) {
          console.error('MapSVG: Error in the "Marker Label" template')
        }
      })
    } else {
      this.markers.forEach((marker: Marker) => {
        marker.setLabel("")
      })
    }
  }

  /**
   * Adds a layer to the map that may contain some objects.
   * @private
   */
  addLayer(name: string): HTMLElement {
    this.layers[name] = $('<div class="mapsvg-layer mapsvg-layer-' + name + '"></div>')[0]
    this.containers.layers.appendChild(this.layers[name])
    return this.layers[name]
  }

  /**
   * Returns layer by provided name
   * @param name
   */
  getLayer(name: string): HTMLElement {
    return this.layers[name]
  }

  /**
   * Returns Database
   * @example
   * var objects = mapsvg.getDb().getLoaded();
   * var object  = mapsvg.getDb().getLoadedObject(12);
   *
   * // Filter DB by region "US-TX". This automatically triggers markers and directory reloading
   * mapsvg.getDatabase().getAll({filters: {regions: "US-TX"}});
   */
  getDb(): Repository {
    return this.objectsRepository
  }

  /**
   * Returns Regions database
   */
  getDbRegions(): Repository {
    return this.regionsRepository
  }

  /**
   * Adds an SVG object as Region to MapSVG
   * @param {object} svgObject - SVG Object
   * @returns {Region}
   * @private
   */
  regionAdd(
    svgObject:
      | SVGElement
      | SVGPathElement
      | SVGCircleElement
      | SVGEllipseElement
      | SVGRectElement
      | SVGPolygonElement
      | SVGPolylineElement,
  ) {
    const region = new Region({
      element: svgObject,
      mapsvg: this,
      options: this.options.regions[svgObject.id],
      statusOptions: this.options.regionStatuses,
    })
    this.reloadRegionStyles(region)
    region.events.on(
      MapObjectEvent.UPDATE,
      (event: EventWithData<{ region: Region; data: Partial<Region> }>) => {
        const { region, data } = event.data
        const paramsToCheck = ["choroplethValue"]
        if (paramsToCheck.some((param) => param in data)) {
          this.reloadRegionStyles(region)
        }
      },
    )
    this.regions.push(region)
    this.regions.sort(function (a, b) {
      return a.id == b.id ? 0 : +(a.id > b.id) || -1
    })
    return region
  }

  /**
   * Deletes a Region from the map
   * @param {string} id - Region ID
   * @private
   */
  regionDelete(id: string): void {
    if (this.regions.findById(id)) {
      this.regions.findById(id).elem.remove()
      this.regions.delete(id)
    } else if ($("#" + id).length) {
      $("#" + id).remove()
    }
  }
  /**
   * Reloads Regions from SVG file
   */
  reloadRegions(): void {
    // this.regions.forEach(function (r: Region) {
    //     $(r.element).css({ "stroke-width": r.style["stroke-width"] });
    // });
    this.regions.clear()
    $(this.containers.svg).find(".mapsvg-region").removeClass("mapsvg-region")
    $(this.containers.svg).find(".mapsvg-region-disabled").removeClass("mapsvg-region-disabled")
    $(this.containers.svg)
      .find("path, polygon, circle, ellipse, rect")
      .each((index: number, element: HTMLElement) => {
        // @ts-ignore
        const elem = <SVGElement>element
        if ($(elem).closest("defs").length) return
        if (elem.getAttribute("data-stroke-width")) {
          elem.style["stroke-width"] = elem.getAttribute("data-stroke-width")
        }
        if (elem.getAttribute("id")) {
          if (
            !this.options.regionPrefix ||
            (this.options.regionPrefix &&
              elem.getAttribute("id").indexOf(this.options.regionPrefix) === 0)
          ) {
            this.regionAdd(elem)
          }
        }
      })
  }
  /**
   * Reloads Regions data
   */
  reloadRegionsFull(): void {
    this.reloadAllRegionStyles()

    const statuses = this.regionsRepository.getSchema().getFieldByType("status")

    this.regions.forEach((region) => {
      const _region = <Model>this.regionsRepository.getLoaded().findById(region.id)

      if (_region) {
        region.setData(_region)
        if (
          statuses &&
          _region.getData().status !== undefined &&
          _region.getData().status !== null
        ) {
          region.setStatus(_region.getData().status)
        }
      } else {
        if (
          this.options.filters.filteredRegionsStatus ||
          this.options.filters.filteredRegionsStatus === 0 ||
          (this.options.menu.source === "regions" &&
            this.options.menu.filterout &&
            this.options.menu.filterout.field === "status" &&
            this.options.menu.filterout.val) ||
          this.options.menu.filterout.val === 0
        ) {
          const status =
            this.options.filters.filteredRegionsStatus ||
            this.options.filters.filteredRegionsStatus === 0
              ? this.options.filters.filteredRegionsStatus
              : this.options.menu.filterout.val
          region.setStatus(status)
        }
      }
    })

    this.loadDirectory()
    this.setChoropleth()
    this.setLayersControl()
    this.setGroups()
    if (this.options.labelsRegions.on) {
      this.setLabelsRegions()
    }
    if (
      this.options.filters.on &&
      this.options.filters.source === "regions" &&
      this.controllers.filters &&
      this.controllers.filters.hideFilters
    ) {
      this.controllers.filters.setFiltersCounter()
    }
  }

  /**
   * Fix "fit markers" view world screen offset
   * @private
   */
  fixMarkersWorldScreen(): void {
    if (this.googleMaps.map)
      setTimeout(() => {
        const markers = { left: 0, right: 0, leftOut: 0, rightOut: 0 }
        if (this.markers.length > 1) {
          this.markers.forEach((m: Marker) => {
            if (
              $(m.element).offset().left <
              $(this.containers.map).offset().left + this.containers.map.clientWidth / 2
            ) {
              markers.left++
              if ($(m.element).offset().left < $(this.containers.map).offset().left) {
                markers.leftOut++
              }
            } else {
              markers.right++
              if (
                $(m.element).offset().left >
                $(this.containers.map).offset().left + this.containers.map.clientWidth
              ) {
                markers.rightOut++
              }
            }
          })

          if (
            (markers.left === 0 && markers.rightOut) ||
            (markers.right === 0 && markers.leftOut)
          ) {
            const k = markers.left === 0 ? 1 : -1
            const ww =
              (this.svgDefault.viewBox.width / this.converter.mapLonDelta) * 360 * this.getScale()
            this.googleMaps.map.panBy(k * ww, 0)
          }
        }
      }, 600)
  }

  /**
   * Updates map options from old version of MapSVG
   * @param options
   * @private
   */
  async updateOutdatedOptions({
    version,
    options,
  }: {
    version: string
    options: MapOptions & Record<string, any>
  }): Promise<{ version: string; options: MapOptions & Record<string, any> }> {
    if (this.version && compareVersions(this.version, mapsvgCore.version) < 0) {
      const migrationClient = await import("@/Core/Migrations/migrate")

      const { options: modifiedOptions, success } = migrationClient.migrate(this.version, options)
      if (success) {
        return {
          version: mapsvgCore.version,
          options: modifiedOptions,
        }
      } else {
        throw Error("Could not update options")
      }
    } else {
      return {
        version,
        options,
      }
    }
  }

  getContainer(name: string): HTMLElement {
    return this.containers[name]
  }

  private renderSvgData(xmlData) {
    // Default width/height/viewBox from SVG

    // Clean memory
    const svgTag = $(xmlData).find("svg")

    this.containers.svg = svgTag[0]

    if (svgTag.attr("width") && svgTag.attr("height")) {
      this.svgDefault.width = parseFloat(svgTag.attr("width").replace(/px/g, ""))
      this.svgDefault.height = parseFloat(svgTag.attr("height").replace(/px/g, ""))
      this.svgDefault.viewBox = svgTag.attr("viewBox")
        ? new ViewBox(svgTag.attr("viewBox").split(" "))
        : new ViewBox(0, 0, this.svgDefault.width, this.svgDefault.height)
    } else if (svgTag.attr("viewBox")) {
      this.svgDefault.viewBox = new ViewBox(svgTag.attr("viewBox").split(" "))
      this.svgDefault.width = this.svgDefault.viewBox.width
      this.svgDefault.height = this.svgDefault.viewBox.height
    } else {
      const msg =
        "MapSVG couldn't load SVG file. Please check if the file exists: " +
        this.options.source +
        ". If the file exists, please contact support (this message is showing up only in wp-admin area)."
      if (this.inBackend) {
        $.growl.error({ message: msg, duration: 60000 })
      } else {
        console.error("MapSVG couldn't load SVG file")
      }
      return false
    }

    this.viewBox.update(this.svgDefault.viewBox)

    this.geoViewBox = this.getGeoViewBoxFromSvgTag(this.containers.svg)

    svgTag.attr("preserveAspectRatio", "xMidYMid meet")
    svgTag.removeAttr("width")
    svgTag.removeAttr("height")
    $(this.containers.scrollpane).append(svgTag)

    this.containers.svg.style.width = "auto"

    $(this.containers.svg)
      .find("path, polygon, circle, ellipse, rect, line, polyline")
      .each((index, elem) => {
        const width = MapObject.getComputedStyle("stroke-width", elem)
        if (width) {
          $(elem).attr("data-stroke-width", width.replace("px", ""))
        }
      })
  }

  private setFilterOut() {
    if (!this.objectsRepository) {
      return
    }
    if (this.options.menu.filterout.field) {
      const f = {}
      f[this.options.menu.filterout.field] = this.options.menu.filterout.val
      if (this.options.menu.source == "regions") {
        // this.regionsRepository.query.setFilterOut(f);
      } else {
        this.objectsRepository.query.setFilterOut(f)
      }
    } else {
      this.objectsRepository.query.setFilterOut(null)
    }
  }

  private getGeoViewBoxFromSvgTag(svgTag: SVGSVGElement): GeoViewBox | null {
    const geo = $(svgTag).attr("mapsvg:geoViewBox") || $(svgTag).attr("mapsvg:geoviewbox")
    if (geo) {
      const geoParts = geo.split(" ").map((p) => parseFloat(p))
      if (geoParts.length == 4) {
        this.mapIsGeo = true
        this.geoCoordinates = true

        const sw = new GeoPoint(geoParts[3], geoParts[0])
        const ne = new GeoPoint(geoParts[1], geoParts[2])
        return new GeoViewBox(sw, ne)
      }
    } else {
      return null
    }
  }

  private svgLoadingFailed(resp: JQueryXHR) {
    if (resp.status == 404) {
      let msg =
        "MapSVG: file not found - " +
        this.options.source +
        "\n\nIf you moved MapSVG from another server please read the following docs page: https://mapsvg.com/docs/installation/moving"
      if (this.inBackend) {
        msg += "\n\nIf you know the correct path for the SVG file, please write it and press OK:"
        const oldSvgPath = this.options.source
        const userSvgPath = prompt(msg, oldSvgPath)
        if (userSvgPath !== null) {
          $.ajax({ url: userSvgPath + "?v=" + this.svgFileLastChanged })
            .fail(function () {
              location.reload()
            })
            .done((xmlData) => {
              this.options.source = userSvgPath
              this.renderSvgData(xmlData)
              const mapsRepo = useRepository("maps", this)
              mapsRepo.update(this)
            })
        } else {
          location.reload()
        }
      } else {
        console.error(msg)
      }
    } else {
      const msg = "MapSVG: can't load SVG file for unknown reason. Please contact support."
      if (this.inBackend) {
        alert(msg)
      } else {
        console.error(msg)
      }
    }
  }

  private addLoadingMessage(): void {
    this.containers.loading = document.createElement("div")
    this.containers.loading.className = "mapsvg-loading"

    const loadingTextBlock = document.createElement("div")
    loadingTextBlock.className = "mapsvg-loading-text"
    loadingTextBlock.innerHTML = this.options.loadingText

    const spinner = document.createElement("div")
    spinner.className = "spinner-border spinner-border-sm"

    this.containers.loading.appendChild(spinner)
    this.containers.loading.appendChild(loadingTextBlock)

    document.getElementById(this.containerId).appendChild(this.containers.loading)
  }
  private hideLoadingMessage() {
    $(this.containers.loading).hide()
  }
  private showLoadingMessage() {
    $(this.containers.loading).show()
  }

  private disableAnimation() {
    this.containers.map.classList.add("no-transitions")
  }
  private enableAnimation() {
    this.containers.map.classList.remove("no-transitions")
  }

  private loadExtensions() {
    // Load extension (common things)
    // @ts-ignore
    if (
      this.options.extension &&
      // @ts-ignore
      $().mapSvg.extensions &&
      // @ts-ignore
      $().mapSvg.extensions[_this.options.extension]
    ) {
      // @ts-ignore
      const ext = $().mapSvg.extensions[_this.options.extension]
      ext && ext.common(this)
    }
  }

  /**
   * @ignore
   */
  decrementAfterloadBlockers(): void {
    if (this.afterLoadBlockers > 0) {
      this.afterLoadBlockers--
      if (this.afterLoadBlockers === 0) {
        this.finalizeMapLoading()
      }
    }
  }
  /**
   * Final stage of initialization.
   * Initialization is split into 2 steps because the 1st step contains async ajax request.
   * When the ajax requires is done, the final initialization step is called.
   * @private
   */
  finalizeMapLoading(): void {
    if (this.afterLoadBlockers > 0 || this.loaded) {
      return
    }

    this.selectObjectsByIdFromUrl()

    this.redraw()

    setTimeout(() => {
      // this.movingItemsAdjustScreenPosition()
      // this.adjustStrokes()
      if (isPhone() && this.options.menu.on && this.options.menu.hideOnMobile) {
        switch (this.options.menu.showFirst) {
          case "map":
            this.controllers.toolbar.show("map")
            break
          case "directory":
            this.controllers.toolbar.show("menu")
            break
          default:
            break
        }
      }
      setTimeout(() => {
        this.hideLoadingMessage()
        $(this.containers.loading).find(".mapsvg-loading-text").hide()
        // this.enableAnimation()
      }, 400)
    }, 100)

    this.loaded = true
    this.events.trigger(MapEventType.AFTER_INIT, { mapsvg: this })
  }

  private selectObjectsByIdFromUrl() {
    const match = RegExp("[?&]mapsvg_select=([^&]*)").exec(window.location.search)
    if (match) {
      const select = decodeURIComponent(match[1].replace(/\+/g, " "))
      this.selectRegion(select)
    }

    if (window.location.hash && window.location.hash.indexOf("#/m/") !== -1) {
      let query = window.location.hash.replace("#/m/", "")
      let regionId: string | undefined

      if (
        this.options.actions.map.afterLoad.selectRegion &&
        (query.indexOf("r/") === 0 || (query.indexOf("r/") === -1 && query.indexOf("db/") === -1))
      ) {
        // Handle Region ID in URL
        if (query.indexOf("r/") === 0) {
          query = query.replace("r/", "")
        }
        const region = this.getRegion(query)
        if (region) {
          this.regionClickHandler(null, region)
        }
      } else if (this.options.actions.map.afterLoad.selectMarker && query.indexOf("db/") === 0) {
        // Handle Object ID in URL
        query = query.replace("db/", "")
        const selectMarker = (object) => {
          const marker = this.markers.get(`marker_${object.getData().id}`)
          if (marker) {
            this.markerClickHandler.call(this, null, marker)
          }
        }
        const objectLoaded = this.objectsRepository
          .getLoaded()
          .find((object) => object.getData().id === query)
        if (objectLoaded) {
          selectMarker(objectLoaded)
        } else {
          this.objectsRepository.findById(query, false).done((object) => {
            this.addObject(object)
            this.objectsLoadedToMapHandler()
            selectMarker(object)
          })
        }
      }
    }
  }

  getConverter(): Converter {
    return this.converter
  }

  /**
   * Old choropleth methods
   */

  /**
   * Sets choropleth map options
   * @param {object} options
   *
   * @example
   * mapsvg.setGauge({
   *   on: true,
   *   colors: {
   *     high: "#FF0000",
   *     low: "#00FF00"
   *   }
   *   labels: {
   *     high: "high",
   *     low: "low"
   *   }
   * });
   */
  setChoropleth(options?: any) {
    const _this = this

    options = options || _this.options.choropleth
    options.on != undefined && (options.on = utils.numbers.parseBoolean(options.on))
    deepMerge(_this.options, { choropleth: options })

    let needsRedraw = false

    if (!_this.$gauge) {
      _this.$gauge = {}
      _this.$gauge.gradient = $("<td>&nbsp;</td>").addClass("mapsvg-gauge-gradient")
      _this.setGaugeGradientCSS()
      _this.$gauge.container = $("<div />").addClass("mapsvg-gauge").hide()
      _this.$gauge.table = $("<table />")
      const tr = $("<tr />")
      _this.$gauge.labelLow = $("<td>" + _this.options.choropleth.labels.low + "</td>")
      _this.$gauge.labelHigh = $("<td>" + _this.options.choropleth.labels.high + "</td>")
      tr.append(_this.$gauge.labelLow)
      tr.append(_this.$gauge.gradient)
      tr.append(_this.$gauge.labelHigh)
      _this.$gauge.table.append(tr)
      _this.$gauge.container.append(_this.$gauge.table)
      _this.$gauge.container.append(_this.$gauge.table)
      $(_this.containers.map).append(_this.$gauge.container)
    }

    if (!_this.options.choropleth.on && _this.$gauge.container.is(":visible")) {
      _this.$gauge.container.hide()
      needsRedraw = true
    } else if (_this.options.choropleth.on && !_this.$gauge.container.is(":visible")) {
      _this.$gauge.container.show()
      needsRedraw = true
      this.regionsRepository.events.on(RepositoryEvent.AFTER_UPDATE, this.redrawGauge)
      this.regionsRepository.events.on(RepositoryEvent.AFTER_LOAD, this.redrawGauge)
    }

    if (!this.options.choropleth.on) {
      this.regionsRepository.events.off(RepositoryEvent.AFTER_UPDATE, this.redrawGauge)
      this.regionsRepository.events.off(RepositoryEvent.AFTER_LOAD, this.redrawGauge)
    }

    if (options.colors) {
      _this.options.choropleth.colors.lowRGB = tinycolor(
        _this.options.choropleth.colors.low,
      ).toRgb()
      _this.options.choropleth.colors.highRGB = tinycolor(
        _this.options.choropleth.colors.high,
      ).toRgb()
      _this.options.choropleth.colors.diffRGB = {
        r: _this.options.choropleth.colors.highRGB.r - _this.options.choropleth.colors.lowRGB.r,
        g: _this.options.choropleth.colors.highRGB.g - _this.options.choropleth.colors.lowRGB.g,
        b: _this.options.choropleth.colors.highRGB.b - _this.options.choropleth.colors.lowRGB.b,
        a: _this.options.choropleth.colors.highRGB.a - _this.options.choropleth.colors.lowRGB.a,
      }
      needsRedraw = true
      _this.$gauge && _this.setGaugeGradientCSS()
    }

    if (options.labels) {
      _this.$gauge.labelLow.html(_this.options.choropleth.labels.low)
      _this.$gauge.labelHigh.html(_this.options.choropleth.labels.high)
    }

    needsRedraw && _this.redrawGauge()
  }

  /**
   * Redraws choropleth map colors
   * @private
   */
  redrawGauge = () => {
    const _this = this

    _this.updateGaugeMinMax()
    _this.reloadAllRegionStyles()
  }

  /**
   * Updates min/max values of Region choropleth fields
   * @private
   */
  updateGaugeMinMax() {
    const _this = this

    _this.options.choropleth.min = 0
    _this.options.choropleth.max = false
    const values = []
    _this.regions.forEach(function (r) {
      const gauge = r.data && r.data[_this.options.regionChoroplethField]
      gauge != undefined && values.push(gauge)
    })
    if (values.length > 0) {
      _this.options.choropleth.min = values.length == 1 ? 0 : Math.min.apply(null, values)
      _this.options.choropleth.max = Math.max.apply(null, values)
      _this.options.choropleth.maxAdjusted =
        _this.options.choropleth.max - _this.options.choropleth.min
    }
  }
  /**
   * Sets gradient for choropleth map
   * @private
   */
  setGaugeGradientCSS() {
    const _this = this

    _this.$gauge.gradient.css({
      background:
        "linear-gradient(to right," +
        _this.options.choropleth.colors.low +
        " 1%," +
        _this.options.choropleth.colors.high +
        " 100%)",
      filter:
        'progid:DXImageTransform.Microsoft.gradient( startColorstr="' +
        _this.options.choropleth.colors.low +
        '", endColorstr="' +
        _this.options.choropleth.colors.high +
        '",GradientType=1 )',
    })
  }
  setSvgFileLastChanged(val: number) {
    this.svgFileLastChanged = val
  }

  /**
   * @ignore
   * @deprecated
   */
  get $map() {
    return $(this.getContainer("map"))
  }

  /**
   * @ignore
   * @deprecated
   */
  get $svg() {
    return $(this.getContainer("svg"))
  }

  /**
   * @ignore
   * @deprecated
   */
  get $popover() {
    return $(this.getContainer("popover"))
  }

  /**
   * @ignore
   * @deprecated
   */
  get $details() {
    return $(this.getContainer("detailsView"))
  }

  /**
   * @ignore
   * @deprecated
   */
  get $directory() {
    return $(this.getContainer("directory"))
  }

  /**
   * @ignore
   * @deprecated
   */
  get database() {
    return this.objectsRepository
  }

  /**
   * @ignore
   * @deprecated
   */
  get regionsDatabase() {
    return this.regionsRepository
  }
}

// if (!window.mapsvg.map) {
//   window.mapsvg.map = MapSVGMap
// }
