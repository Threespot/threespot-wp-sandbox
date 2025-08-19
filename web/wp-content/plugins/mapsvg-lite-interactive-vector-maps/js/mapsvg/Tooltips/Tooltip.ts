import { Controller, ControllerOptions } from "../Core/Controller"
import { ScreenPoint } from "../Location/Location"
import "./tooltips.css"
const $ = jQuery

interface TooltipOptions extends ControllerOptions {
  positionToCursor?: string
}

/**
 * Creates a scrollable popover in a map container.
 * @extends Controller
 * @param options
 * @constructor
 */
export class Tooltip extends Controller {
  screenPoint: ScreenPoint
  posOriginal: { [key: string]: string }
  posShifted: { [key: string]: string }
  posShiftedPrev: { [key: string]: string }
  mirror: { [key: string]: number }
  positionToCursor: string

  constructor(options: TooltipOptions) {
    super(options)

    this.classList.push("mapsvg-tooltip")

    this.name = "tooltip"
    this.position = "absolute"
    this.opened = false
    this.closable = false
    this.withToolbar = false

    this.scrollable = false
    this.withToolbar = false
    this.autoresize = false
    this.screenPoint = new ScreenPoint(0, 0)

    this.positionToCursor = options.positionToCursor || ""

    this.posOriginal = {}
    this.posShifted = {}
    this.posShiftedPrev = {}
    this.mirror = {}
  }

  setSize(minWidth: number, maxWidth: number): void {}

  setPositionToCursor(position: string): void {
    const ex = position.split("-")
    if (ex[0].indexOf("top") != -1 || ex[0].indexOf("bottom") != -1) {
      this.posOriginal.topbottom = ex[0]
    }
    if (ex[0].indexOf("left") != -1 || ex[0].indexOf("right") != -1) {
      this.posOriginal.leftright = ex[0]
    }
    if (ex[1]) {
      this.posOriginal.leftright = ex[1]
    }

    this.containers.main.className = this.containers.main.className.replace(
      /(^|\s)mapsvg-tt-\S+/g,
      "",
    )

    this.containers.main.classList.add("mapsvg-tt-" + position)
  }

  /**
   * Sets a point where the popover should be shown
   */
  setScreenPoint(x: number, y: number): void {
    this.screenPoint.x = x
    this.screenPoint.y = y
  }

  /**
   * Final rendering steps of the popover
   * @private
   */
  viewDidLoad(): void {
    super.viewDidLoad.call(this)
    this.setPositionToCursor(this.positionToCursor)
  }

  /**
   * Set tooltip position by given coordinates
   */
  setScreenPosition(x: number, y: number): void {
    this.setScreenPoint(x, y)
    this.containers.main.style.transform = "translateX(-50%) translate(" + x + "px," + y + "px)"
  }

  /**
   * Sets event handlers for the popover
   */
  setEventHandlers(): void {
    const event = "mousemove.tooltip.mapsvg-" + this.uid
    $("body").off(event)
    $("body").on(event, (e) => this.move(e))
  }

  destroy() {
    super.destroy()
    $("body").off("mousemove.tooltip.mapsvg-" + this.uid)
  }

  move(e: JQuery.Event) {
    {
      if (this.destroyed) {
        return
      }
      this.containers.main.style.left =
        e.clientX + $(window).scrollLeft() - $(this.map.containers.map).offset().left + "px"
      this.containers.main.style.top =
        e.clientY + $(window).scrollTop() - $(this.map.containers.map).offset().top + "px"

      const m = new ScreenPoint(
        e.clientX + $(window).scrollLeft(),
        e.clientY + $(window).scrollTop(),
      )
      const _tbbox = this.containers.main.getBoundingClientRect()
      const _mbbox = this.map.containers.map.getBoundingClientRect()
      const tbbox = {
        top: _tbbox.top + $(window).scrollTop(),
        bottom: _tbbox.bottom + $(window).scrollTop(),
        left: _tbbox.left + $(window).scrollLeft(),
        right: _tbbox.right + $(window).scrollLeft(),
        width: _tbbox.width,
        height: _tbbox.height,
      }
      const mbbox = {
        top: _mbbox.top + $(window).scrollTop(),
        bottom: _mbbox.bottom + $(window).scrollTop(),
        left: _mbbox.left + $(window).scrollLeft(),
        right: _mbbox.right + $(window).scrollLeft(),
        width: _mbbox.width,
        height: _mbbox.height,
      }

      if (m.x > mbbox.right || m.y > mbbox.bottom || m.x < mbbox.left || m.y < mbbox.top) {
        return
      }

      if (this.mirror.top || this.mirror.bottom) {
        // may be cancel mirroring
        if (this.mirror.top && m.y > this.mirror.top) {
          this.mirror.top = 0
          delete this.posShifted.topbottom
        } else if (this.mirror.bottom && m.y < this.mirror.bottom) {
          this.mirror.bottom = 0
          delete this.posShifted.topbottom
        }
      } else {
        // may be need mirroring

        if (tbbox.bottom < mbbox.top + tbbox.height) {
          this.posShifted.topbottom = "bottom"
          this.mirror.top = m.y
        } else if (tbbox.top > mbbox.bottom - tbbox.height) {
          this.posShifted.topbottom = "top"
          this.mirror.bottom = m.y
        }
      }

      if (this.mirror.right || this.mirror.left) {
        // may be cancel mirroring

        if (this.mirror.left && m.x > this.mirror.left) {
          this.mirror.left = 0
          delete this.posShifted.leftright
        } else if (this.mirror.right && m.x < this.mirror.right) {
          this.mirror.right = 0
          delete this.posShifted.leftright
        }
      } else {
        // may be need mirroring
        if (tbbox.right < mbbox.left + tbbox.width) {
          this.posShifted.leftright = "right"
          this.mirror.left = m.x
        } else if (tbbox.left > mbbox.right - tbbox.width) {
          this.posShifted.leftright = "left"
          this.mirror.right = m.x
        }
      }

      let pos = $.extend({}, this.posOriginal, this.posShifted)
      const _pos = []
      pos.topbottom && _pos.push(pos.topbottom)
      pos.leftright && _pos.push(pos.leftright)
      // @ts-ignore
      pos = _pos.join("-")

      if (
        this.posShifted.topbottom != this.posOriginal.topbottom ||
        this.posShifted.leftright != this.posOriginal.leftright
      ) {
        this.containers.main.className = this.containers.main.className.replace(
          /(^|\s)mapsvg-tt-\S+/g,
          "",
        )
        this.containers.main.classList.add("mapsvg-tt-" + pos)
        this.posShiftedPrev = pos
      }
      // Need this to update tooltip position on initial load
      if (!this.opened) {
        this.open()
      }
    }
  }
}
