/**
 * Global MapSVG class. It contains all other MapSVG classes and some static methods.
 *
 * @module MapSVGClass
 *
 * @example
 * let mapsvg = mapsvg.get(0); // get first map instance
 * let mapsvg2 = mapsvg.get(1); // get second map instance
 * let mapsvg3 = mapsvg.getById(123); // get map by ID
 *
 * let mapsvg = new mapsvg.map("my-container",{
 *   source: "/path/to/map.svg"
 * });
 *
 * let marker = new mapsvg.marker({
 *   location: location,
 *   mapsvg: mapsvg
 * });
 *
 * if(mapsvg.utils.env.isPhone()){
 *  // do something special for mobile devices
 * }
 *
 *
 */
if (typeof $ === "undefined") {
  // @ts-ignore
  $ = jQuery
}

// import { FormBuilder } from "../FormBuilder/FormBuilder"
import type { MapSVGMap } from "../Map/Map"
import { ResizeSensor } from "./ResizeSensor"
import { deepMerge } from "./Utils"

export type MapsvgRoutes = {
  // WP ajaxurl
  ajaxurl?: string
  // Root folder of MapSVG plugin
  root: string
  // WP Rest API URL
  api: string
  // Stores info which templates are loaded
  templates: Record<string, boolean>
  // Path to maps folder, relative to the root
  maps: string
  // Path to uploads folder
  uploads: string
  // Path to home folder
  home: string
}

export interface MapsvgFrontendParams {
  routes: MapsvgRoutes
  nonce: string
  google_maps_api_key: string
}

interface MapsvgEnv {
  routes: MapsvgRoutes
  nonce: string
  google_maps_api_key: string
}

export interface MapSVGProps {
  initialized: boolean
  routes: MapsvgRoutes
  _nonce: string
  google_maps_api_key: string
  version: string
  markerImages: { url: string; file: string; folder: string; relativeUrl?: string }[]
  defaultMarkerImage: string
  // formBuilder: FormBuilder
  mediaUploader: any
  templatesLoaded: Record<string, boolean>
  instances: MapSVGMap[]
  mouse: { x: number; y: number }
  googleMapsApiLoaded: boolean
  distanceSearch: any

  googleMaps: {
    apiKey: string
    onLoadCallbacks: (() => void)[]
    apiIsLoading: boolean
    loaded: boolean
  }
}

export class Map {
  constructor() {}
}

export class Mapsvg implements MapSVGProps {
  initialized: boolean
  routes: MapsvgRoutes
  _nonce: string
  google_maps_api_key: string
  static initialized = false
  loaded: boolean = false
  version: string = "process.env.VERSION"
  markerImages: { url: string; file: string; folder: string; relativeUrl?: string }[]
  defaultMarkerImage: string
  // formBuilder: FormBuilder
  mediaUploader: any
  templatesLoaded: Record<string, boolean> = {}
  instances: MapSVGMap[]

  mouse: { x: number; y: number } = { x: 0, y: 0 }
  meta: Record<string, any> = {}
  ResizeSensor = ResizeSensor
  googleMapsApiLoaded: boolean = false
  distanceSearch: any
  googleMaps: {
    apiKey: string
    onLoadCallbacks: (() => void)[]
    apiIsLoading: boolean
    loaded: boolean
  }

  constructor() {
    this.instances = []

    const { wp, mapsvg_paths, ajaxurl, mapsvg_runtime_vars, google } = window

    if (typeof wp !== "undefined" && typeof wp.media !== "undefined") {
      this.mediaUploader = wp.media({
        title: "Choose images",
        button: {
          text: "Choose images",
        },
        multiple: true,
      })
    }

    this.mouse = { x: 0, y: 0 }
  }

  static createClient(frontEndOptions?: MapsvgFrontendParams) {
    const options = frontEndOptions ?? window.mapsvgFrontendParams
    if (!options) {
      throw new Error("MapSVG: can't initialize the core due to the missing front-end options.")
    }
    let mapsvgCore: Mapsvg
    if (!mapsvgCore) {
      mapsvgCore = new Mapsvg()
      mapsvgCore.init(options)
      window.MapSVG = mapsvgCore
    }
    return mapsvgCore
  }

  async init(options: MapsvgFrontendParams) {
    this.nonce = options.nonce
    this.routes = options.routes
    this.googleMaps = {
      apiKey: options.google_maps_api_key,
      onLoadCallbacks: [],
      apiIsLoading: false,
      loaded: false,
    }
    if (typeof window.ajaxurl !== "undefined") {
      this.routes.ajaxurl = window.ajaxurl
    }

    this.setEventHandlers()
    this.extendBuiltins()
    if (!window.mapsvg || window.mapsvg instanceof HTMLElement) {
      const load = async () => {
        const initGlobalsFile = await import("@/Core/InitGlobals")
        initGlobalsFile.initGlobals()
      }
      await load()
    }
    this.loadMaps()

    this.initialized = true
  }

  receiveMessageFromIframe(event) {
    const frames = document.getElementsByTagName("iframe")
    for (let i = 0; i < frames.length; i++) {
      if (frames[i].contentWindow === event.source) {
        jQuery(frames[i]).css({ height: event.data.height })
        break
      }
    }
  }

  loadMap(container: HTMLElement) {
    const {
      id: _id,
      svgFileLastChanged: _svgFileLastChanged,
      viewBox: _viewBox,
      loadDb,
      ...rest
    } = container.dataset

    const id = Number(_id)
    const svgFileLastChanged = Number(_svgFileLastChanged)
    if (typeof loadDb !== "undefined") {
      deepMerge(rest, { database: { loadOnStart: loadDb === "true" } })
    }

    setTimeout(() => {
      new window.mapsvg.map(container.id, { id, options: { id, ...rest }, svgFileLastChanged })
    }, 5)
  }

  lazyLoadMap(container: HTMLElement) {
    // IntersectionObserver configuration (adjusted for better visibility)
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            this.loadMap(entry.target as HTMLElement)
            observer.unobserve(entry.target) // Unobserve after first intersection
          }
        })
      },
      {
        root: null, // Observe relative to viewport
        threshold: 0, // Load when 50% or more of the element is visible
      },
    )

    observer.observe(container)
  }

  loadMaps() {
    this.unloadMaps()
    const containers = document.querySelectorAll<HTMLElement>('.mapsvg[data-autoload="true"]')
    containers.forEach((container) => {
      if (container.dataset.lazy === "true") {
        this.lazyLoadMap(container)
      } else {
        this.loadMap(container)
      }
    })
    this.loaded = true
  }
  unloadMaps() {
    const autoloadContainers = document.querySelectorAll('.mapsvg[data-autoload="true"]')
    autoloadContainers.forEach((container) => {
      const mapId = container.getAttribute("id")
      const mapInstance = this.getById(mapId)
      if (mapInstance) {
        mapInstance.destroy()
      }
    })
    this.loaded = false
  }

  setEventHandlers() {
    window.addEventListener("message", this.receiveMessageFromIframe, false)
  }

  addInstance(mapsvg) {
    this.instances.push(mapsvg)
  }

  get(index) {
    return this.instances[index]
  }

  getById(id) {
    const instance = this.instances.filter(function (i) {
      return i.id == id
    })
    if (instance.length > 0) {
      return instance[0]
    }
  }

  getByContainerId(id) {
    const instance = this.instances.filter(function (i) {
      return i.$map.attr("id") == id
    })
    if (instance.length > 0) {
      return instance[0]
    }
  }

  extend(sub, base) {
    sub.prototype = Object.create(base.prototype)
    sub.prototype.constructor = sub
  }

  set nonce(_nonce: string) {
    this._nonce = _nonce
  }
  get nonce(): string {
    return this._nonce
  }
  get googleMapsApiKey() {
    return this.googleMaps.apiKey
  }

  extendBuiltins() {
    if (!String.prototype.trim) {
      String.prototype.trim = function () {
        return this.replace(/^\s+|\s+$/g, "")
      }
    }

    // Create Element.remove() function if not exists
    if (!("remove" in Element.prototype)) {
      ;(Element.prototype as Element).remove = function () {
        if (this.parentNode) {
          this.parentNode.removeChild(this)
        }
      }
    }

    Math.hypot =
      Math.hypot ||
      function (...args: number[]) {
        let y = 0
        const length = args.length

        for (let i = 0; i < length; i++) {
          if (args[i] === Infinity || args[i] === -Infinity) {
            return Infinity
          }
          y += args[i] * args[i]
        }
        return Math.sqrt(y)
      }
    SVGElement.prototype.getTransformToElement =
      SVGElement.prototype.getTransformToElement ||
      function (toElement) {
        let value
        try {
          value = toElement.getScreenCTM().inverse().multiply(this.getScreenCTM())
        } catch (e) {
          return
        }
        return value
      }

    if (!Object.values) {
      Object.values = function (object) {
        return Object.keys(object).map(function (k) {
          return object[k]
        })
      }
    }
  }
}

/**
 * @ignore
 */
const mapsvgCore = Mapsvg.createClient()

export { mapsvgCore }
