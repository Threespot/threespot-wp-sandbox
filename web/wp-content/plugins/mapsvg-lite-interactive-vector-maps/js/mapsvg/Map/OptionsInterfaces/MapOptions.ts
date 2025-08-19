import { MiddlewareType } from "@/Core/Middleware"
import { SchemaOptions } from "@/Infrastructure/Server/Schema"
import { ArrayIndexed } from "../../Core/ArrayIndexed"
import { SchemaField } from "../../Infrastructure/Server/SchemaField"
import { GeoPoint } from "../../Location/Location"

// New interface for RegionStatusOptions
export interface RegionStatusOptions {
  label: string
  value: string
  disabled: boolean
  color: string
}

// New type for RegionStatusOptionsCollection
export type RegionStatusOptionsCollection = Record<string, RegionStatusOptions>

/**
 * MapOptions interface is used to control the correct types of map options, passed during the initialization of the map or updating it.
 */
export interface MapOptions {
  id: number
  title?: string
  backend?: boolean // passed from admin
  db_map_id?: number | string
  source?: string
  // Contents of an SVG file if it was preloaded
  svgData?: string
  regionPrefix?: string
  groups?: ArrayIndexed<GroupOptionsInterface>
  galleries?: ArrayIndexed<GalleryOptionsInterface>
  layersControl?: LayersControlOptionsInterface
  disableAll?: boolean
  responsive?: boolean
  loadingText?: string
  width?: number
  height?: number
  viewBox?: number[]
  lockAspectRatio?: boolean
  padding?: { top: number; left: number; right: number; bottom: number }
  cursor?: string
  multiSelect?: boolean
  colorsIgnore?: boolean
  inBackend?: boolean
  defaultMarkerImage?: string
  fitMarkers?: boolean
  fitMarkersOnStart?: boolean
  fitSingleMarkerZoom?: number
  menu?: DirectoryOptions
  colors?: ColorsOptions
  clustering?: { on: boolean }
  /**
   * Zoom
   */
  zoom?: ZoomOptions
  scroll?: ScrollOptions
  tooltips?: TooltipsOptions
  popovers?: PopoversOptions
  regionStatuses?: RegionStatusOptionsCollection
  events?: { [key: string]: string }
  middlewares?: { [K in MiddlewareType]: string }
  choropleth?: ChoroplethOptions
  regionChoroplethField?: string
  regions?: RegionOptionsCollection
  database?: DatabaseOptions
  labelsMarkers?: { on: boolean }
  labelsRegions?: { on: boolean }
  googleMaps?: {
    on: boolean
    apiKey: string
    center: GeoPoint
    zoom: number
    drawingTools?: boolean
    geometry?: boolean
    minZoom: number
    language: string
  }
  css?: string
  containers?: {
    leftSidebar: { on: boolean; width: string; height: string }
    rightSidebar: { on: boolean; width: string; height: string }
    header: { on: boolean; width: string; height: string }
    footer: { on: boolean; width: string; height: string }
  }
  filters?: FilterOptions
  filtersSchema?: Array<SchemaField>
  detailsView?: {
    location: "leftSidebar" | "rightSidebar" | "header" | "footer" | "custom" | "fullscreen" | "map"
    width: string
    mobileFullscreen: boolean
    containerId: string
    margin: string
    autoresize: boolean
  }
  mobileView?: {
    [key: string]: any
  }
  templates?: {
    directory: string
    directoryItem: string
    labelRegion: string
    popoverRegion: string
    popoverMarker: string
    tooltipRegion: string
    tooltipMarker: string
    detailsViewRegion: string
    detailsView: string
  }
  controls?: ControlsOptions
  actions?: {
    region: {
      click: { [key: string]: any }
      touch: { [key: string]: any }
      mouseover: { [key: string]: any; showTooltip: boolean }
      mouseout: { [key: string]: any }
    }
    marker: {
      touch: { [key: string]: any }
      click: { [key: string]: any }
      mouseover: { [key: string]: any; showTooltip: boolean }
      mouseout: { [key: string]: any }
    }
    map: {
      afterLoad: {
        selectRegion: boolean
        selectMarker: boolean
      }
    }
    directoryItem: {
      touch: { [key: string]: any }
      click: { [key: string]: any }
      hover: { [key: string]: any }
      mouseout: { [key: string]: any }
    }
  }
  svgFileVersion?: number
  data_regions?: any // passed from Admin
  data_objects?: any // passed from Admin
  extension?: any
  markerLastID?: number
  previousMapsIds?: Array<number | string>
}

export interface PaginationOptions {
  on: boolean
  perpage: number
  showIn: string
  prev: string
  next: string
}

export interface DatabaseOptions {
  pagination: PaginationOptions
  loadOnStart: boolean
  noFiltersNoLoad: boolean
  regionsTableName: string
  objectsTableName: string
  on: boolean
  schemas: {
    regions: SchemaOptions
    objects: SchemaOptions
  }
}

export interface MapRepositoryOptions {
  pagination: {
    on: boolean
    perpage: number
  }
  schema: SchemaOptions
}

export interface FilterOptions {
  on: boolean
  source: string
  containerId: string
  location: string
  width: string
  filteredRegionsStatus: number
  modalLocation: string
  hideOnMobile: boolean
  hide: boolean
  padding: string
  showButtonText: string
  clearButton: boolean
  clearButtonText: string
  searchButton: boolean
  searchButtonText: string
}

export interface GroupOptionsInterface {
  id: string
  title: string
  objects: Array<{ label: string; value: string }>
  visible: boolean
}
export interface GalleryOptionsInterface {
  id: string
  title: string
  objects: Array<{ label: string; value: string }>
  visible: boolean
}
export interface LayersControlOptionsInterface {
  on: boolean
  label: string
  maxHeight: string
  position: string
  expanded: boolean
}

export interface ControlsOptions {
  zoom: boolean
  zoomReset: boolean
  userLocation: boolean
  location: string
  previousMap: boolean
}

export interface DirectoryOptions {
  showFirst: string
  showMapOnClick: boolean
  noResultsText: string
  on: boolean
  customContainer: boolean
  width: string
  source: string
  hideOnMobile: boolean
  location: string
  containerId: string
  position: string
  filterout: {
    field: string
    val: number | string
  }
  categories: {
    on: boolean
    groupBy: string
    collapse: boolean
    collapseOther: boolean
    hideEmpty: boolean
  }
  sortBy: string
  sortDirection: string
  minHeight: string
}

export interface ColorsOptions {
  base?: string
  baseDefault?: string
  background?: string
  hover?: string | number
  selected?: string | number
  stroke?: string
  directory: string
  directorySearch: string
  detailsView: string
  popover: string
  tooltip: string
  status: any
  markers: any
  leftSidebar: string
  rightSidebar: string
  header: string
  footer: string
  modalFilters: string
  clusters: string
  clustersHover: string
  clustersText: string
  clustersBorders: string
  clustersHoverBorders: string
  clustersHoverText: string
}

/**
 * A set of options to control zoom
 */
export interface ZoomOptions {
  on: boolean
  limit: [number, number]
  delta: number
  buttons: {
    on: boolean
    location: string
  }
  mousewheel: boolean
  fingers: boolean
  hideSvg: boolean
  hideSvgZoomLevel: number
}

/**
 * A set of options to control scroll
 */
export interface ScrollOptions {
  on: boolean
  limit: boolean
  background: boolean
  spacebar: boolean
}

export interface TooltipsOptions {
  on: boolean
  position: string
  template: string
  maxWidth: number
  minWidth: number
}

export interface PopoversOptions {
  on: boolean
  position: string
  template: string
  centerOn: boolean
  width: string
  maxWidth: number
  maxHeight: number
  mobileFullscreen: boolean
  resetViewboxOnClose: boolean
}

export interface ChoroplethOptions {
  on?: boolean
  [key: string]: any
  source: string
  sourceField: string
  sourceFieldSelect: {
    on: boolean
    variants: Array<string>
  }
  bubbleMode: boolean
  bubbleSize: {
    min: number
    max: number
  }
  coloring: {
    mode: string
    noData: {
      color: "grey"
      description: "-"
    }
    gradient: {
      colors: {
        low: string
        high: string
        diffRGB: {
          r: number
          g: number
          b: number
          a: number
        }
        lowRGB: {
          r: number
          g: number
          b: number
          a: number
        }
        highRGB: {
          r: number
          g: number
          b: number
          a: number
        }
      }
      labels: {
        low: string
        high: string
      }
      values: {
        min: number
        max: number
        maxAdjusted: number
      }
    }
    palette: {
      colors: Array<{
        color: string
        valueFrom: number
        valueTo: number
        description: string
      }>
      outOfRange: {
        color: "grey"
        description: "-"
      }
    }
    legend: {
      on: boolean
      layout: string
      container: string
      title: string
      text: string
      description: string
      width: string
      height: string
    }
  }
}

export interface RegionOptionsCollection {
  [key: string]: RegionOptions
}

export type RegionOptions = {
  style?: Partial<CSSStyleDeclaration>
  selected?: boolean
  disabled?: boolean
}
