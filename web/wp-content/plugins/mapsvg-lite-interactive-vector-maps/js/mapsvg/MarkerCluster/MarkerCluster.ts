import { ScreenPoint, SVGPoint } from "../Location/Location"
import { MapSVGMap } from "../Map/Map"
import { ViewBox } from "../Map/ViewBox"
import { MapObject, MapObjectType } from "../MapObject/MapObject"
import { Marker } from "../Marker/Marker"
import "./markercluster.css"

const $ = jQuery

/**
 * MarkerCluster class. Groups markers into a clickable circle with a number indicating how many markers it contains.
 */
export class MarkerCluster extends MapObject {
  svgPoint: SVGPoint
  screenPoint: ScreenPoint
  min_x: number
  min_y: number
  max_x: number
  max_y: number
  cellX: number
  cellY: number
  markers: Array<Marker>
  cellSize: number
  width: number
  elem: HTMLElement
  visible: boolean

  constructor(
    options: {
      svgPoint: SVGPoint
      cellX: number
      cellY: number
      markers: Array<Marker>
      width?: number
      cellSize?: number
    },
    mapsvg: MapSVGMap,
  ) {
    super(null, mapsvg)

    this.type = MapObjectType.CLUSTER

    this.svgPoint = options.svgPoint
    this.cellX = options.cellX // SVG-x (not pixel-x)
    this.cellY = options.cellY // SVG-y (not pixel-y)
    this.markers = options.markers || []

    this.cellSize = 50
    this.width = 30

    const _this = this

    this.elem = $('<div class="mapsvg-marker-cluster">' + this.markers.length + "</div>")[0]
    $(this.elem).data("cluster", this)

    if (this.markers.length < 2) {
      $(this.elem).hide() // don't show cluster at the start
    }

    this.adjustScreenPosition()
  }

  /**
   * Adds marker to the cluster.
   * @param {Marker} marker
   */
  addMarker(marker: Marker) {
    this.markers.push(marker)
    if (this.markers.length > 1) {
      if (this.markers.length === 2) {
        // this.markers[0].clusterize();
        $(this.elem).show()
      }
      if (this.markers.length === 2) {
        const x = this.markers.map(function (m) {
          return m.svgPoint.x
        })
        this.min_x = Math.min.apply(null, x)
        this.max_x = Math.max.apply(null, x)

        const y = this.markers.map(function (m) {
          return m.svgPoint.y
        })
        this.min_y = Math.min.apply(null, y)
        this.max_y = Math.max.apply(null, y)

        this.svgPoint.x = this.min_x + (this.max_x - this.min_x) / 2
        this.svgPoint.y = this.min_y + (this.max_y - this.min_y) / 2
      }
      if (this.markers.length > 2) {
        if (marker.svgPoint.x < this.min_x) {
          this.min_x = marker.svgPoint.x
        } else if (marker.svgPoint.x > this.max_x) {
          this.max_x = marker.svgPoint.x
        }
        if (marker.svgPoint.y < this.min_y) {
          this.min_y = marker.svgPoint.y
        } else if (marker.svgPoint.x > this.max_x) {
          this.max_y = marker.svgPoint.y
        }
        this.svgPoint.x = this.min_x + (this.max_x - this.min_x) / 2
        this.svgPoint.y = this.min_y + (this.max_y - this.min_y) / 2
      }
      // marker.clusterize();
    } else {
      this.svgPoint.x = marker.svgPoint.x
      this.svgPoint.y = marker.svgPoint.y
    }

    $(this.elem).text(this.markers.length)
    this.adjustScreenPosition()
  }

  /**
   * Checks if provided marker should be added into this cluster.
   * @param {Marker} marker
   * @returns {boolean}
   */
  canTakeMarker(marker: Marker): boolean {
    const _this = this

    const screenPoint = _this.mapsvg.converter.convertSVGToPixel(marker.svgPoint)

    return (
      this.cellX === Math.ceil(screenPoint.x / this.cellSize) &&
      this.cellY === Math.ceil(screenPoint.y / this.cellSize)
    )
  }

  /**
   * Destroys the cluster
   */
  destroy() {
    // this.markers.forEach(function(marker){
    //     marker.unclusterize();
    // });
    this.markers = null
    $(this.elem).remove()
  }

  /**
   * Adjusts position of the cluster.
   * Called on zoom and map container resize.
   */
  adjustScreenPosition() {
    const pos = this.mapsvg.converter.convertSVGToPixel(this.svgPoint)

    pos.x -= this.width / 2
    pos.y -= this.width / 2

    this.setScreenPosition(pos.x, pos.y)
  }

  /**
   * Moves cluster by given numbers.
   *
   * @param {number} deltaX
   * @param {number} deltaY
   */
  moveSrceenPositionBy(deltaX, deltaY) {
    const oldPos = this.screenPoint,
      x = oldPos.x - deltaX,
      y = oldPos.y - deltaY

    this.setScreenPosition(x, y)
  }

  /**
   * Set cluster position.
   *
   * @param {number} x
   * @param {number} y
   */
  setScreenPosition(x, y) {
    if (this.screenPoint instanceof ScreenPoint) {
      this.screenPoint.x = x
      this.screenPoint.y = y
    } else {
      this.screenPoint = new ScreenPoint(x, y)
    }

    this.updateVisibility()

    if (this.visible === true) {
      this.elem.style.transform = "translate(" + x + "px," + y + "px)"
    }
  }

  /**
   * Check if the cluster is inside of the viewBox
   *
   * @return boolean
   */
  inViewBox() {
    const x = this.screenPoint.x,
      y = this.screenPoint.y,
      mapFullWidth = this.mapsvg.containers.map.offsetWidth,
      mapFullHeight = this.mapsvg.containers.map.offsetHeight

    return (
      x - this.width / 2 < mapFullWidth &&
      x + this.width / 2 > 0 &&
      y - this.width / 2 < mapFullHeight &&
      y + this.width / 2 > 0
    )
  }

  /**
   * Set visibility of the marker
   *
   */
  updateVisibility() {
    if (this.inViewBox() === true) {
      this.visible = true

      this.elem.classList.remove("mapsvg-out-of-sight")
    } else {
      this.visible = false

      this.elem.classList.add("mapsvg-out-of-sight")
    }

    return this.visible
  }

  /**
   * Get SVG bounding box of the MarkersCluster
   * @returns {*[]} - [x,y,width,height]
   */
  getBBox(): ViewBox {
    const bbox = {
      x: this.svgPoint.x,
      y: this.svgPoint.y,
      width: this.cellSize / this.mapsvg.getScale(),
      height: this.cellSize / this.mapsvg.getScale(),
    }

    return new ViewBox(bbox.x, bbox.y, bbox.width, bbox.height)
  }

  getData() {
    return this.markers.map((m) => m.object)
  }
}
