import { MapOptions } from "@/Map/OptionsInterfaces/MapOptions"
import utils from "../Utils"

export default function migration(options: any): MapOptions {
  // Fix Directory options
  if (options.menu && (options.menu.position || options.menu.customContainer)) {
    if (options.menu.customContainer) {
      options.menu.location = "custom"
    } else {
      options.menu.position = options.menu.position === "left" ? "left" : "right"
      options.menu.location = options.menu.position === "left" ? "leftSidebar" : "rightSidebar"
      if (!options.containers || !options.containers[options.menu.location]) {
        options.containers = options.containers || {}
        options.containers[options.menu.location] = { on: false, width: "200px" }
      }
      options.containers[options.menu.location].width = options.menu.width
      if (utils.numbers.parseBoolean(options.menu.on)) {
        options.containers[options.menu.location].on = true
      }
    }
    delete options.menu.position
    delete options.menu.width
    delete options.menu.customContainer
  }
  // Fix Details View options
  if (
    options.detailsView &&
    (options.detailsView.location === "mapContainer" ||
      options.detailsView.location === "near" ||
      options.detailsView.location === "top")
  ) {
    options.detailsView.location = "map"
  }
  // Transfer zoom options to controls options
  if (!options.controls) {
    options.controls = {}
    options.controls.zoom =
      options.zoom &&
      options.zoom.on &&
      (!options.zoom.buttons || options.zoom.buttons.location !== "hide")
    options.controls.location =
      options.zoom && options.zoom.buttons && options.zoom.buttons.location !== "hide"
        ? options.zoom.buttons.location
        : "right"
  }
  // Transfer zoom options to controls options
  if (options.colors && !options.colors.markers) {
    options.colors.markers = {
      base: { opacity: 100, saturation: 100 },
      hovered: { opacity: 100, saturation: 100 },
      unhovered: { opacity: 100, saturation: 100 },
      active: { opacity: 100, saturation: 100 },
      inactive: { opacity: 100, saturation: 100 },
    }
  }
  if (options.tooltipsMode) {
    options.tooltips.mode = options.tooltipsMode
    delete options.tooltipsMode
  }
  if (options.popover) {
    options.popovers = options.popover
    delete options.popover
  }
  return options
}
