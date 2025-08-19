/**
 * Details View controller. Large scrollable window with content and "X" close button
 * that can be placed in the map container, header/footer/sidebar or in a custom DIV container outside of the map.
 * @param {object} options
 * @extends Controller
 * @constructor
 */
import { Controller, ControllerOptions } from "../Core/Controller"
import "./details.css"
const $ = jQuery

export interface DetailsViewOptions extends ControllerOptions {
  autoresize?: boolean
}

export class DetailsController extends Controller {
  constructor(options: DetailsViewOptions) {
    super(options)
    this.name = "detailsView"
    this.classList.push("mapsvg-details-container")
    this.closable = true
    this.autoresize = options.autoresize
    this.position = options.position || "absolute"
  }

  viewDidLoad(): void {}
}
