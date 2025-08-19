import { GeoPoint } from "../Location/Location"

export type ViewBoxLiteral = {
  x: number
  y: number
  width: number
  height: number
}
export type ViewBoxArray =
  | [number, number, number, number]
  | [string, string, string, string]
  | number[]
  | string[]

/**
 * ViewBox class for managing the view box of the map.
 * The view box is a rectangular area of the SVG canvas that is visible to the user.
 * It is used to define the portion of the SVG canvas that is visible to the user.
 */
export class ViewBox {
  x: number = 0
  y: number = 0
  width: number = 0
  height: number = 0
  initialized: boolean = false

  /**
   * Constructs a new ViewBox object.
   * @param {(number | ViewBox | ViewBoxLiteral | ViewBoxArray | string)} [x] - The x-coordinate of the viewBox. Can be a number, a ViewBox object, a ViewBoxLiteral object, a ViewBoxArray, or a string in the format "x y width height".
   * @param {(number | string)} [y] - The y-coordinate of the viewBox. Can be a number or a string.
   * @param {(number | string)} [width] - The width of the viewBox. Can be a number or a string.
   * @param {(number | string)} [height] - The height of the viewBox. Can be a number or a string.
   */
  constructor(
    x?: number | ViewBox | ViewBoxLiteral | ViewBoxArray | string,
    y?: number | string,
    width?: number | string,
    height?: number | string,
  ) {
    if (typeof x !== "undefined") {
      this.update(x, y, width, height)
    }
  }

  /**
   * Updates the viewBox with new coordinates and dimensions.
   * @param {(ViewBoxLiteral | ViewBoxArray | string)} newViewBox - The new viewBox to update with. Can be an object with x, y, width, and height properties, an array of [x, y, width, height], or a string in the format "x y width height".
   */
  update(
    x: number | ViewBox | ViewBoxLiteral | ViewBoxArray | string,
    y?: number | string,
    width?: number | string,
    height?: number | string,
  ): void {
    if (typeof x === "object") {
      if (
        !Array.isArray(x) &&
        // x as Object {x, y, width, height}
        Object.prototype.hasOwnProperty.call(x, "x") &&
        Object.prototype.hasOwnProperty.call(x, "y") &&
        Object.prototype.hasOwnProperty.call(x, "width") &&
        Object.prototype.hasOwnProperty.call(x, "height")
      ) {
        this.x = typeof x.x === "string" ? parseFloat(x.x) : x.x
        this.y = typeof x.y === "string" ? parseFloat(x.y) : x.y
        this.width = typeof x.width === "string" ? parseFloat(x.width) : x.width
        this.height = typeof x.height === "string" ? parseFloat(x.height) : x.height
        this.initialized = true
      } else if (Array.isArray(x)) {
        if (x.length !== 4) {
          throw new Error("ViewBox Array must have 4 elements")
        }
        // x as Array [x, y, width, heigth]
        this.x = typeof x[0] === "string" ? parseFloat(x[0]) : x[0]
        this.y = typeof x[1] === "string" ? parseFloat(x[1]) : x[1]
        this.width = typeof x[2] === "string" ? parseFloat(x[2]) : x[2]
        this.height = typeof x[3] === "string" ? parseFloat(x[3]) : x[3]
        this.initialized = true
      }
    } else {
      if (typeof x === "string" && typeof y === "undefined") {
        ;[x, y, width, height] = x
          .trim()
          .split(" ")
          .map(function (v) {
            return parseFloat(v)
          })
      }

      // all params as strings
      this.x = typeof x === "string" ? parseFloat(x) : x
      this.y = typeof y === "string" ? parseFloat(y) : y
      this.width = typeof width === "string" ? parseFloat(width) : width
      this.height = typeof height === "string" ? parseFloat(height) : height
      this.initialized = true
    }
  }

  /**
   * Returns a string with viewBox numbers
   */
  toString(): string {
    return this.x + " " + this.y + " " + this.width + " " + this.height
  }

  /**
   * Returns an array containing viewBox numbers
   */
  toArray(): number[] {
    return [this.x, this.y, this.width, this.height]
  }

  clone(): ViewBox {
    return new ViewBox({ x: this.x, y: this.y, width: this.width, height: this.height })
  }

  /**
   * Check whether current viewBox fits inside of another viewBox
   */
  fitsInViewBox(viewBox: ViewBox, atLeastByOneDimension?: boolean): boolean {
    if (atLeastByOneDimension === true) {
      return viewBox.width >= this.width || viewBox.height >= this.height
    } else {
      return viewBox.width >= this.width && viewBox.height >= this.height
    }
  }

  /**
   * Adds padding to the viewBox
   */
  addPadding(padding: { top: number; left: number; right: number; bottom: number }): void {
    if (padding.top) {
      this.y -= padding.top
      this.height += padding.top
    }
    if (padding.right) {
      this.width += padding.right
    }
    if (padding.bottom) {
      this.height += padding.bottom
    }
    if (padding.left) {
      this.x -= padding.left
      this.width += padding.left
    }
  }
}

export class GeoViewBox {
  sw: GeoPoint
  ne: GeoPoint
  constructor(sw: GeoPoint, ne: GeoPoint) {
    this.sw = sw
    this.ne = ne
  }
}
