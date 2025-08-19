import { Controller, ControllerOptions } from "../Core/Controller"
import { SVGPoint, ScreenPoint } from "../Location/Location"
import { Marker } from "../Marker/Marker"
import { Region } from "../Region/Region"
import "./popover.css"
const $ = jQuery

interface PopoverOptions extends ControllerOptions {
  mapObject: Marker | Region
  point?: SVGPoint
  yShift?: number
}

/**
 * Creates a scrollable popover in a map container.
 */
export class PopoverController extends Controller {
  point: SVGPoint
  screenPoint: ScreenPoint
  mapObject: Marker | Region
  yShift: number
  id: string

  constructor(options: PopoverOptions) {
    super(options)
    this.autoresize = true
    this.name = "popover"
    this.classList.push("mapsvg-popover")
    this.opened = false
    this.closable = true
    this.point = options.point
    this.yShift = options.yShift
    this.mapObject = options.mapObject
    this.id = this.mapObject.id + "_" + Math.random()
    $(this.containers.main).data("popover-id", this.id)
  }

  /**
   * Sets a point where the popover should be shown
   * @param {ScreenPoint} point - [x,y]
   */
  setPoint(point: SVGPoint): void {
    this.point = point
  }

  viewDidAppear(): void {
    this.adjustScreenPosition()
  }

  /**
   * Adjsuts position of the popver.
   *
   */
  adjustScreenPosition(): void {
    if (this.point) {
      const pos = this.map.converter.convertSVGToPixel(this.point)

      pos.y -= this.yShift
      pos.x = Math.round(pos.x)
      pos.y = Math.round(pos.y)

      this.setScreenPosition(pos.x, pos.y)
    }
  }

  /**
   * Moves popover by given numbers
   *
   * @param {number} deltaX
   * @param {number} deltaY
   */
  moveSrceenPositionBy(deltaX: number, deltaY: number): void {
    const oldPos = this.screenPoint,
      x = oldPos.x - deltaX,
      y = oldPos.y - deltaY

    this.setScreenPosition(x, y)
  }

  /**
   * Set popover position by given numbers
   *
   * @param {number} x
   * @param {number} y
   */
  setScreenPosition(x: number, y: number): void {
    this.screenPoint = new ScreenPoint(x, y)
    this.containers.main.style.transform = "translate(-50%) translate(" + x + "px," + y + "px)"
  }

  /**
   * Abstract method
   * @ignore
   */
  viewWillClose(): boolean {
    if ("type" in this.mapObject && this.mapObject.isRegion()) {
      this.map.deselectRegion(this.mapObject)
    }
    if ("type" in this.mapObject && this.mapObject.isMarker()) {
      this.map.deselectAllMarkers()
    }

    return true
  }
}
