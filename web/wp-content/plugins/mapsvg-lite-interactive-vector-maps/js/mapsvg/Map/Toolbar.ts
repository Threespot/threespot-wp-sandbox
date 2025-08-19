import { Controller, ControllerOptions } from "@/Core/Controller"
import { BaseEventHandler } from "@/Core/Events"

export enum ToolbarEvent {
  CLICK = "click",
  MOUSEOVER = "mouseover",
  MOUSEOUT = "mouseout",
}

interface ToolbarControllerOptions extends ControllerOptions {
  events: ControllerOptions["events"] & {
    [K in ToolbarEvent]?: BaseEventHandler<{
      buttonName: string
    }>
  }
}

const template = `
    <div class="mapsvg-mobile-buttons">    
      <div class="mapsvg-button mapsvg-button-menu" data-controller="menu"><i class="mapsvg-icon-menu"></i>
          {{labelList}}
      </div>
      <div class="mapsvg-button mapsvg-button-map" data-controller="map"><i class="mapsvg-icon-map"></i>
          {{labelMap}}
      </div>
    </div>`

export class ToolbarController extends Controller {
  buttons: { [key: string]: HTMLButtonElement }
  constructor(options: ToolbarControllerOptions) {
    options = {
      ...options,
      template,
      opened: true,
      closable: false,
      scrollable: false,
      fullscreen: { sm: false, md: false, lg: false },
      autoresize: false,
      withToolbar: false,
      noPadding: true,
    }
    super(options)
    this.name = "toolbar"
    this.classList.push("mapsvg-toolbar")
  }

  viewDidRedraw() {
    super.viewDidRedraw()
    this.buttons = {}
    this.buttons.menu = $(this.containers.view).find(".mapsvg-button-menu")[0] as HTMLButtonElement
    this.buttons.map = $(this.containers.view).find(".mapsvg-button-map")[0] as HTMLButtonElement
  }

  setEventHandlers() {
    super.setEventHandlers()
    $(this.containers.view).on("click.toolbar.mapsvg", ".mapsvg-button", (e) => {
      if (e.target.nodeName == "I") {
        return
      }

      const buttonName = e.target.getAttribute("data-controller")
      const button = this.buttons[buttonName]
      $(this.containers.view).find(".mapsvg-button").removeClass("active")
      $(button).addClass("active")

      this.events.trigger("click", e, {
        target: button,
        buttonName,
      })
    })
  }

  show(controllerName: string) {
    $(this.buttons[controllerName]).trigger("click")
  }
}
