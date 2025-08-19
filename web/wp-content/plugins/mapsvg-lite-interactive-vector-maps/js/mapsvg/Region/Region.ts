import { deepMerge, parseBoolean, ucfirst } from "@/Core/Utils"
import { RegionOptions, RegionStatusOptionsCollection } from "@/Map/OptionsInterfaces/MapOptions"
import { ArrayIndexed } from "../Core/ArrayIndexed"
import { SVGPoint } from "../Location/Location"
import { MapSVGMap, RegionStylesByStatus, RegionStylesByStatusOptions } from "../Map/Map"
import { ViewBox } from "../Map/ViewBox"
import { MapObject, MapObjectEvent, MapObjectType } from "../MapObject/MapObject"
import { Model } from "../Model/Model"
import "./region.css"
const $ = jQuery

enum RegionEventType {
  click,
  mouseover,
  mouseout,
}

export type RegionParams = {
  element: SVGElement
  mapsvg: MapSVGMap
  options: RegionOptions
  statusOptions: RegionStatusOptionsCollection
}

/**
 * Region class. Contains a reference to an SVG element.
 */
export class Region extends MapObject {
  type: MapObjectType
  name: string
  title?: string
  id_no_spaces: string
  disabled: boolean
  selected: boolean
  hovered: boolean
  status: number | string
  initialState: string
  elemOriginal: SVGGraphicsElement
  elem: SVGGraphicsElement
  style: Partial<CSSStyleDeclaration>
  styleSvg: Partial<CSSStyleDeclaration>
  choroplethValue: number
  customAttrs: Array<string>
  fill?: string
  center?: SVGPoint
  label: HTMLElement
  bubble: HTMLElement
  data?: Model | undefined
  bbox: ViewBox

  gaugeValue: number
  objects: ArrayIndexed<Model>
  options: Partial<RegionOptions>
  styleByState: RegionStylesByStatus
  statusOptions: RegionStatusOptionsCollection

  constructor(params: RegionParams) {
    super(params.element, params.mapsvg)
    this.options = params.options || {}
    this.statusOptions = params.statusOptions || {
      1: { disabled: false, label: "Enabled", color: "", value: "1" },
    }
    this.status = "undefined"
    this.name = "region"
    this.type = MapObjectType.REGION
    this.objects = new ArrayIndexed("id", [], { autoId: false, unique: true })
    this.id = this.element.getAttribute("id")
    this.title = this.element.getAttribute("title")
    // if (this.id && this.mapsvg.options.regionPrefix) {
    //   this.setId(this.id.replace(this.mapsvg.options.regionPrefix, ""))
    // }

    this.id_no_spaces = this.id.replace(/\s/g, "_")

    this.element.setAttribute("class", (this.element.className || "") + " mapsvg-region")

    this.setStyleInitial()

    this.disabled = this.getDisabledState()
    if (this.disabled) {
      this.attr("class", this.attr("class") + " mapsvg-disabled")
    }

    const { selected, ...restOptions } = this.options
    this.selected = selected
    this.bbox = this.getBBox()
    this.update(restOptions)
    this.saveState()
  }

  adjustStroke(scale: number): void {
    this.element.style.strokeWidth = this.style["stroke-width"] / scale + "px"
  }

  /**
   * Sets initial style of a Region, computed from SVG
   * @private
   */
  setStyleInitial(): void {
    this.style = { fill: this.getComputedStyle("fill") }
    this.style.stroke = this.getComputedStyle("stroke") || ""
    let w
    w = this.getComputedStyle("stroke-width")
    w = w ? w.replace("px", "") : "1"
    w = parseFloat(w)
    this.style["stroke-width"] = w
    this.styleSvg = { ...this.style }
    $(this.element).attr("data-stroke-width", w)
  }
  /**
   * Save state of a Region (all parameters)
   * @private
   */
  saveState(): void {
    this.initialState = JSON.stringify(this.getOptions())
  }
  /**
   * Returns SVG bounding box of the Region
   * @returns {[number,number,number,number]} - [x,y,width,height]
   */
  getBBox(): ViewBox {
    if (this.bbox && this.bbox.width > 0 && this.bbox.height > 0) {
      return this.bbox
    }
    // @ts-ignore
    const _bbox = this.element.getBBox()
    const bbox = new ViewBox(_bbox.x, _bbox.y, _bbox.width, _bbox.height)

    // @ts-ignore (TS doesn't recognize getTransformToElement() method from SVGGraphicsElement)
    const matrix = this.element.getTransformToElement(this.mapsvg.containers.svg)
    if (!matrix) {
      return _bbox
    }

    const x2 = bbox.x + bbox.width
    const y2 = bbox.y + bbox.height

    // transform a point using the transformed matrix
    let position = this.mapsvg.containers.svg.createSVGPoint()
    position.x = bbox.x
    position.y = bbox.y
    position = position.matrixTransform(matrix)
    bbox.x = position.x
    bbox.y = position.y
    // var position = this.mapsvg.containers.svg.createSVGPoint();
    position.x = x2
    position.y = y2
    position = position.matrixTransform(matrix)
    bbox.width = position.x - bbox.x
    bbox.height = position.y - bbox.y

    this.bbox = bbox
    return bbox
  }

  /**
   * Checks whether the Region was changed from the initial state
   * @returns {boolean}
   * @private
   */
  changed(): boolean {
    return JSON.stringify(this.getOptions()) != this.initialState
  }
  /**
   * Saves a copy of the Region SVG elem.
   * Used in Map Editor by "Edit SVG file" mode.
   * @private
   */
  edit(): void {
    this.elemOriginal = <SVGGraphicsElement>$(this.element).clone()[0]
  }
  /**
   * Deletes the copy of the Region SVG elem created by .edit() method.
   * Used in Map Editor by "Edit SVG file" mode.
   * @private
   */
  editCommit(): void {
    this.elemOriginal = null
  }
  /**
   * Restores SVG elem.
   * Used in Map Editor by "Edit SVG file" mode.
   * @private
   */
  editCancel(): void {
    this.mapsvg.containers.svg.appendChild(this.elemOriginal)
    this.element = this.elemOriginal
    this.elemOriginal = null
  }
  /**
   * Returns Region properties
   * @param {boolean} forTemplate - adds special properties for use in a template
   * @returns {object}
   */
  getOptions(forTemplate?: boolean): { [key: string]: any } {
    const options = {
      id: this.id,
      id_no_spaces: this.id_no_spaces,
      title: this.title,
      fill:
        this.mapsvg.options.regions[this.id] &&
        this.mapsvg.options.regions[this.id].style &&
        this.mapsvg.options.regions[this.id].style.fill,
      data: this.data,
      choroplethValue: this.choroplethValue,
      disabled: forTemplate ? this.disabled : undefined,
    }

    for (const key in options) {
      if (typeof options[key] === "undefined") {
        delete options[key]
      }
    }
    if (this.customAttrs) {
      const that = this
      this.customAttrs.forEach(function (attr) {
        options[attr] = that[attr]
      })
    }
    return options
  }
  /**
   * Returns an object with properties of the Region formatted for a template
   * @returns {object}
   */
  forTemplate(): any {
    const data = {
      id: this.id,
      title: this.title,
      ...this.data?.getData(),
      objects: this.objects.map((o) => o.getData()),
      data: this.data?.getData(),
      fill: this.style.fill ?? "",
    }

    return data
  }
  getData(): any {
    return this.forTemplate()
  }
  getModel(): Model {
    return this.data
  }
  /**
   * Updates the Region
   * @param {object} options
   *
   * @example
   * var region = mapsvg.getRegion("US-TX");
   * region.update({
   *   fill: "#FF3322"
   * });
   */
  update(options): void {
    for (const key in options) {
      // check if there's a setter for a property
      const setter = "set" + ucfirst(key)
      if (setter in this) this[setter](options[key])
      else {
        this[key] = options[key]
        this.customAttrs = this.customAttrs || []
        this.customAttrs.push(key)
      }
    }
  }
  /**
   * Sets Title of the Region
   * @param {string} title
   */
  setTitle(title?: string): void {
    if (title) {
      this.title = title
    }

    this.element.setAttribute("title", this.title)
    this.events.trigger(MapObjectEvent.UPDATE, { region: this, data: { title } })
  }

  /**
   * Sets CSS style of the Region
   * @param {object} style - CSS-format styles
   * @private
   */
  setStyle(style: any): void {
    deepMerge(this.style, style)
    // Apply the style to this.element
    for (const key in style) {
      this.element.style[key] = style[key]
    }
    this.events.trigger(MapObjectEvent.UPDATE, { region: this, data: { style } })
  }

  /**
   * @private
   */
  public setStyleByType(styleName?: "default" | "hover" | "selected"): void {
    if (!styleName) {
      styleName = this.selected ? "selected" : this.hovered ? "hover" : "default"
    }
    if (this.styleByState[this.status]) {
      this.setStyle(this.styleByState[this.status][styleName])
    }
  }

  /**
   * Sets the fill color for the Region
   * @param {string} fill - The fill color to set
   */
  setFill(fill: string): void {
    const currentStatus = this.status || "undefined"
    const newStyle = {
      [currentStatus]: {
        default: { fill },
      },
    }

    this.setStylesByState(newStyle)
    this.events.trigger(MapObjectEvent.UPDATE, { region: this, data: { fill } })
  }

  setStylesByState(styleByState: RegionStylesByStatusOptions, deepMergeFlag: boolean = true) {
    if (deepMergeFlag) {
      if (!this.styleByState) {
        this.styleByState = {}
      }
      deepMerge(this.styleByState, styleByState)
    }
    this.setStyleByType()
  }

  /**
   * Returns color of the Region for choropleth map
   * @returns {string} color
   */
  getChoroplethColor() {
    const o = this.mapsvg.options.choropleth
    let color = ""

    if (
      this.data &&
      (this.data[this.mapsvg.options.regionChoroplethField] ||
        this.data[this.mapsvg.options.regionChoroplethField] === 0)
    ) {
      const w =
        o.maxAdjusted === 0
          ? 0
          : (parseFloat(this.data[this.mapsvg.options.regionChoroplethField]) - o.min) /
            o.maxAdjusted

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
  getBubbleSize() {
    let bubbleSize

    if (this.data && this.data[this.mapsvg.options.choropleth.sourceField]) {
      const maxBubbleSize = Number(this.mapsvg.options.choropleth.bubbleSize.max),
        minBubbleSize = Number(this.mapsvg.options.choropleth.bubbleSize.min),
        maxSourceFieldvalue = this.mapsvg.options.choropleth.coloring.gradient.values.max,
        minSourceFieldvalue = this.mapsvg.options.choropleth.coloring.gradient.values.min,
        sourceFieldvalue = parseFloat(this.data[this.mapsvg.options.choropleth.sourceField])

      bubbleSize =
        ((sourceFieldvalue - minSourceFieldvalue) / (maxSourceFieldvalue - minSourceFieldvalue)) *
          (maxBubbleSize - minBubbleSize) +
        minBubbleSize
    } else {
      bubbleSize = false
    }

    return bubbleSize
  }

  /**
   * Disables the Region.
   * @param {boolean} on - true/false = disable/enable
   * @param {boolean} skipSetFill - If false, color of the Region will not be changed
   */
  setDisabled(on?: boolean, skipSetFill?: boolean) {
    on = on !== undefined ? parseBoolean(on) : this.getDisabledState() // get default disabled state if undefined
    const prevDisabled = this.disabled
    this.disabled = on
    this.attr("class", this.attr("class").replace("mapsvg-disabled", ""))
    if (on) {
      this.attr("class", this.attr("class") + " mapsvg-disabled")
    }
    if (this.disabled != prevDisabled) this.mapsvg.deselectRegion(this)
    !skipSetFill &&
      this.events.trigger(MapObjectEvent.UPDATE, { region: this, data: { disabled: on } })
  }
  /**
   * Sets status of the Region.
   * Takes the list of statuses from global MapSVG options.
   * @param {number} status
   */
  setStatus(status: string | number) {
    const statusOptions = this.statusOptions[status]
    const regionModel = this.data
    if (!statusOptions) {
      return false
    }

    this.status = status
    if (regionModel) {
      regionModel.update({ status })
    }
    this.setDisabled(statusOptions.disabled, true)
    this.setStyleByType()
    this.events.trigger(MapObjectEvent.UPDATE, { region: this, data: { status } })
  }
  /**
   * Selects the Region.
   */
  setSelected(selected: boolean) {
    if (selected) {
      this.mapsvg.selectRegion(this)
    } else {
      this.mapsvg.deselectRegion(this)
    }
    this.events.trigger(MapObjectEvent.UPDATE, { region: this, data: { selected } })
  }
  /**
   * Set Region choropleth value. Used to calculate color of the Region.
   * @param {number} val
   */
  setchoroplethValue(val: number) {
    if ($.isNumeric(val)) {
      if (typeof val === "string") {
        val = parseFloat(val)
      }
      this.choroplethValue = val
    } else {
      this.choroplethValue = undefined
    }
    this.events.trigger(MapObjectEvent.UPDATE, {
      region: this,
      data: { choroplethValue: this.choroplethValue },
    })
  }
  /**
   * Checks if Region should be disabled
   * @param {boolean} asDefault
   * @returns {boolean}
   */
  getDisabledState(asDefault?: boolean): boolean {
    const opts = this.mapsvg.options.regions[this.id]
    if (!asDefault && this.options && this.options.disabled !== undefined) {
      return this.options.disabled
    } else {
      return (
        this.mapsvg.options.disableAll ||
        this.style.fill === "none" ||
        this.id == "labels" ||
        this.id == "Labels"
      )
    }
  }
  /**
   * Highlight the Region.
   * Used on mouseover.
   * @param {boolean} force - If true, the region will be highlighted even if it is disabled
   */
  highlight() {
    this.hovered = true
    this.setStyleByType()
    $(this.element).addClass("mapsvg-region-hover")
    this.events.trigger(MapObjectEvent.UPDATE, { region: this, data: { hovered: true } })
  }
  /**
   * Unhighlight the Region.
   * Used on mouseout.
   */
  unhighlight() {
    this.hovered = false
    this.setStyleByType()
    $(this.element).removeClass("mapsvg-region-hover")
    this.events.trigger(MapObjectEvent.UPDATE, { region: this, data: { hovered: false } })
  }
  /**
   * Select the Region.
   */
  select() {
    this.selected = true
    this.setStyleByType()
    $(this.element).addClass("mapsvg-region-active")
    this.events.trigger(MapObjectEvent.UPDATE, { region: this, data: { selected: true } })
  }
  /**
   * Deselect the Region.
   */
  deselect() {
    this.selected = false
    this.setStyleByType()
    $(this.element).removeClass("mapsvg-region-active")
    this.events.trigger(MapObjectEvent.UPDATE, { region: this, data: { selected: false } })
  }

  /**
   * Adds custom data loaded from server
   * @param {object} data - Any set of `{key:value}` pairs
   */
  setData(data: Model) {
    this.data = data
    if (
      data &&
      typeof data.getData === "function" &&
      typeof data.getData()?.title !== "undefined"
    ) {
      this.setTitle(data.getData().title)
    }
    this.events.trigger(MapObjectEvent.UPDATE, { region: this, data: { data } })
  }

  /**
   * Draw a choropleth bubble for the region
   */
  drawBubble() {
    if (this.data) {
      const bubbleId = "mapsvg-bubble-" + this.id
      const bubbleValue = parseFloat(this.data[this.mapsvg.options.choropleth.sourceField])

      if (bubbleValue) {
        if (!this.center) {
          this.center = this.getCenterSVG()
        }

        const pos = this.mapsvg.converter.convertSVGToPixel(this.center)

        if (!this.bubble) {
          this.bubble = $(
            '<div id="' +
              bubbleId +
              '" class="mapsvg-bubble mapsvg-region-bubble">' +
              bubbleValue +
              "</div>",
          )[0]
          $(this.mapsvg.layers.bubbles).append(this.bubble)
        }

        const color = this.getChoroplethColor()
        const bubbleSize = Number(this.getBubbleSize())

        this.bubble.style.transform =
          "translate(-50%,-50%) translate(" + pos.x + "px," + pos.y + "px)"
        this.bubble.style.backgroundColor = color
        this.bubble.style.width = bubbleSize + "px"
        this.bubble.style.height = bubbleSize + "px"
        this.bubble.style.lineHeight = bubbleSize - 2 + "px"
      } else {
        delete this.bubble
      }
    }
  }

  /**
   * Adjust position of Region Label
   *
   */
  adjustLabelScreenPosition() {
    if (this.label) {
      if (!this.center) {
        this.center = this.getCenterSVG()
      }

      const labelSize = this.mapsvg.converter.convertSizePixelToSVG({
        width: this.label.offsetWidth,
        height: this.label.offsetHeight,
      })
      const pos = this.mapsvg.converter.convertSVGToPixel(this.center),
        x = pos.x - this.label.offsetWidth / 2,
        y = pos.y - this.label.offsetHeight / 2

      this.setLabelScreenPosition(x, y)
      const bbox = this.getBBox()
      this.label.style.opacity = labelSize.width > bbox.width ? "0" : "1"
    }
  }

  /**
   * Adjust position of Region bubble
   *
   */
  adjustBubbleScreenPosition() {
    if (this.bubble) {
      if (!this.center) {
        this.center = this.getCenterSVG()
      }

      const pos = this.mapsvg.converter.convertSVGToPixel(this.center),
        x = pos.x - this.bubble.offsetWidth / 2,
        y = pos.y - this.bubble.offsetHeight / 2

      this.setBubbleScreenPosition(x, y)
    }
  }

  /**
   * Set position of Region Label by given numbers
   *
   * @param {number} deltaX
   * @param {number} deltaY
   */
  moveLabelScreenPositionBy(deltaX, deltaY) {
    if (this.label) {
      const labelStyle = window.getComputedStyle(this.label),
        matrix = labelStyle.transform || labelStyle.webkitTransform,
        matrixValues = matrix.match(/matrix.*\((.+)\)/)
      if (matrixValues && matrixValues.length > 0) {
        const matrixParts = matrixValues[1].split(", ")
        const x = parseFloat(matrixParts[4]) - deltaX
        const y = parseFloat(matrixParts[5]) - deltaY
        this.setLabelScreenPosition(x, y)
      }
    }
  }

  /**
   * Set position of Region bubble by given numbers
   *
   * @param {number} deltaX
   * @param {number} deltaY
   */
  moveBubbleScreenPositionBy(deltaX, deltaY) {
    if (this.bubble) {
      const labelStyle = window.getComputedStyle(this.bubble),
        matrix = labelStyle.transform || labelStyle.webkitTransform,
        matrixValues = matrix.match(/matrix.*\((.+)\)/)[1].split(", "),
        x = parseFloat(matrixValues[4]) - deltaX,
        y = parseFloat(matrixValues[5]) - deltaY

      this.setBubbleScreenPosition(x, y)
    }
  }

  /**
   * Set position of Region Labels by given numbers
   *
   * @param {number} x
   * @param {number} y
   */
  setLabelScreenPosition(x, y) {
    if (this.label) {
      this.label.style.transform = "translate(" + x + "px," + y + "px)"
    }
  }

  /**
   * Set position of Region bubble by given numbers
   *
   * @param {number} x
   * @param {number} y
   */
  setBubbleScreenPosition(x, y) {
    if (this.bubble) {
      this.bubble.style.transform = "translate(" + x + "px," + y + "px)"
    }
  }
}
