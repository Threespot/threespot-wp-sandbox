/**
 * Abstract class. Creates a scrollable controller. Extended by {@link #mapsvgpopovercontroller|PopoverController} / {@link #mapsvgdetailscontroller|DetailsController} / {@link #mapsvgdirectorycontroller|DirectoryController}
 * @abstract
 * @constructor
 * @param {object} options - List of options
 */
import { MapSVGMap } from "../Map/Map"
import { BaseEventHandler, Events } from "./Events.js"
import { Middleware, MiddlewareList, MiddlewareType } from "./Middleware"
import { ResizeSensor } from "./ResizeSensor"
import { deepMerge, isPhone, isTablet, parseBoolean, useId } from "./Utils"
import "./controller.css"
const $ = jQuery

export enum ControllerEvent {
  RESIZE = "resize",
  BEFORE_SHOW = "beforeShow",
  AFTER_SHOW = "afterShow",
  BEFORE_CLOSE = "beforeClose",
  AFTER_CLOSE = "afterClose",
  BEFORE_REDRAW = "beforeRedraw",
  AFTER_REDRAW = "afterRedraw",
  BEFORE_LOAD = "beforeLoad",
  AFTER_LOAD = "afterLoad",
  AFTER_UNLOAD = "afterUnload",
}

export interface ControllerOptions {
  middleware?: Middleware[]
  classList?: string[]
  container: HTMLElement
  map: MapSVGMap
  parent?: ObjectWithEvents
  opened?: boolean
  closable?: boolean
  template?: string
  state?: Record<string, unknown>
  options?: unknown
  styles?: Partial<CSSStyleDeclaration>
  fullscreen?: {
    sm?: boolean
    md?: boolean
    lg?: boolean
  }
  autoresize?: boolean
  scrollable?: boolean
  withToolbar?: boolean
  noPadding?: boolean

  events?: {
    [K in ControllerEvent]?: BaseEventHandler<{ controller: BaseController }>
  } & {
    [key: string]: BaseEventHandler
  }
  position?: "absolute" | "relative" | "fixed"
}

export interface ObjectWithEvents {
  events: Events
}

export interface BaseController {
  uid: number
  classList: string[]
  destroyed: boolean
  opened: boolean
  loaded: boolean
  closable: boolean
  _canClose: boolean
  fullscreen: {
    sm: boolean
    md: boolean
    lg: boolean
  }
  mobileCloseBtn: HTMLElement
  styles: Partial<CSSStyleDeclaration>
  containers: {
    parent: HTMLElement
    main?: HTMLElement
    view?: HTMLElement
    toolbar?: HTMLElement
    contentWrap?: HTMLElement
    contentWrap2?: HTMLElement
    contentView?: HTMLElement
    sizer?: HTMLElement
  }
  map: MapSVGMap
  parent?: ObjectWithEvents
  name: string
  template?: string
  scrollable: boolean
  withToolbar: boolean
  autoresize: boolean
  templates: {
    toolbar: HandlebarsTemplateDelegate<any>
    main: HandlebarsTemplateDelegate<any>
  }
  state: { [key: string]: any }
  resizeSensor: ResizeSensor
  events: Events
  eventOptions: ControllerOptions["events"]
  middlewareOptions: Middleware[]
  options: unknown
  middlewares: MiddlewareList
  position?: "absolute" | "relative" | "fixed"
  noPadding?: boolean
}

export class Controller implements BaseController {
  uid: number
  destroyed: boolean
  classList: string[]
  opened: boolean
  loaded: boolean
  closable: boolean
  _canClose: boolean
  fullscreen: {
    sm: boolean
    md: boolean
    lg: boolean
  }
  mobileCloseBtn: HTMLElement
  styles: Partial<CSSStyleDeclaration>
  containers: {
    parent: HTMLElement
    main?: HTMLElement
    view?: HTMLElement
    toolbar?: HTMLElement
    contentWrap?: HTMLElement
    contentWrap2?: HTMLElement
    contentView?: HTMLElement
    sizer?: HTMLElement
  }
  map: MapSVGMap
  parent?: ObjectWithEvents
  name: string
  template: string
  scrollable: boolean
  withToolbar: boolean
  autoresize: boolean
  templates: {
    toolbar: HandlebarsTemplateDelegate<any>
    main: HandlebarsTemplateDelegate<any>
  }
  state: { [key: string]: any }
  resizeSensor: ResizeSensor
  events: Events
  eventOptions: ControllerOptions["events"]
  middlewareOptions: Middleware[]
  options: unknown
  middlewares: MiddlewareList
  position?: "absolute" | "relative" | "fixed"
  openedFullscreen: boolean = false
  noPadding?: boolean

  constructor(options: ControllerOptions) {
    this.uid = useId()
    this.containers = {
      parent: options.container,
    }
    this.parent = options.parent
    this.destroyed = false
    this.options = options.options
    this.position = options.position
    this.classList = options.classList || []
    this.classList.push("mapsvg-controller-container")

    this.opened = true
    this.loaded = false
    this.closable = options.closable ?? false
    this.canClose = true

    this.fullscreen = deepMerge({ sm: false, md: false, lg: false }, options.fullscreen)

    this.map = options.map
    this.template = options.template || ""
    this.scrollable = options.scrollable === undefined ? true : options.scrollable
    this.withToolbar = options.withToolbar === undefined ? true : options.withToolbar
    this.noPadding = options.noPadding ?? false
    this.autoresize = parseBoolean(options.autoresize)

    this.setState(options.state)

    this.eventOptions = options.events
    this.middlewareOptions = options.middleware

    if (options.styles) {
      this.styles = options.styles
    }

    this.middlewares = new MiddlewareList()
  }

  /**
   * This method fires when the view is fully loaded. Can be used to do any final actions.
   */
  viewDidLoad() {}

  /**
   * Fires when the view appears after being hidden.
   * Should be overriden by a child class.
   * @abstract
   */
  viewDidAppear() {}

  /**
   * This method fires when the view disappears.
   * Should be overriden by a child class.
   * @abstract
   */
  viewDidDisappear() {}

  viewWillRedraw() {}
  viewDidRedraw() {}

  /**
   * Updates the size of the scrollable container. Automatically fires when window size or content size changes.
   */
  updateScroll() {
    if (!this.scrollable) return
    const _this = this
    $(this.containers.contentWrap).nanoScroller({
      preventPageScrolling: true,
      // iOSNativeScrolling: true,
    })
    setTimeout(function () {
      $(_this.containers.contentWrap).nanoScroller({
        preventPageScrolling: true,
        // iOSNativeScrolling: true,
      })
    }, 300)
  }

  /**
   * Adjusts height of the container to fit content.
   */
  adjustHeight() {
    const _this = this
    $(_this.containers.main).height(
      $(_this.containers.main).find(".mapsvg-auto-height").outerHeight() +
        (_this.containers.toolbar ? $(_this.containers.toolbar).outerHeight() : 0),
    )
  }

  /**
   * Initialization
   */
  init() {
    if (this.destroyed) {
      return
    }
    if (this.position) {
      this.classList.push("mapsvg-" + this.position)
    }
    this.events = new Events({
      context: this,
      contextName: this.name,
      map: this.map,
      parent: this.parent && this.parent.events ? this.parent.events : undefined,
    })
    if (this.events) {
      for (const eventName in this.eventOptions) {
        if (typeof this.eventOptions[eventName] === "function") {
          this.events.on(eventName, this.eventOptions[eventName])
        }
      }
    }
    if (this.middlewareOptions) {
      this.middlewareOptions.forEach((mw) => this.middlewares.add(mw.name, mw.handler))
    }
    this.templates = {
      toolbar: Handlebars.compile(this.getToolbarTemplate()),
      main: Handlebars.compile(this.getMainTemplate()),
    }
    this.render()
  }

  /**
   * This method must be overriden by a child class and return an HTML code for the toolbar.
   */
  getToolbarTemplate(): string {
    if (this.closable && this.withToolbar)
      return '<div class="mapsvg-popover-close mapsvg-details-close"></div>'
    return ""
  }

  /**
   * Sets the template for the body
   */
  setMainTemplate(template: any) {
    return (this.templates.main = Handlebars.compile(template))
  }

  /**
   * This method must be overriden by a child class and  to return an HTML code for the main content
   */
  getMainTemplate() {
    return this.template || ""
  }

  /**
   * This method must be overriden by a child class and  to return an HTML code for the main content
   */
  getWrapperTemplate() {
    return "<div></div>"
  }

  setStyles(styles: Partial<CSSStyleDeclaration>): void {
    Object.assign(this.containers.main.style, styles)
  }

  /**
   * Renders the content.
   */
  render() {
    if (this.destroyed) {
      return
    }
    for (const [key, value] of Object.entries(this.fullscreen)) {
      if (value) {
        this.classList.push(`mapsvg-fullscreen-${key}`)
      }
    }
    // Move modal to
    if (
      (isPhone() && this.fullscreen.sm) ||
      (isTablet() && this.fullscreen.md) ||
      this.fullscreen.lg
    ) {
      this.containers.parent = document.body
      this.openedFullscreen = true
      document.body.classList.add("mapsvg-modal-fullscreen")
    }

    this.containers.main = $("<div />")[0]
    this.containers.main.classList.add(...this.classList)

    this.setStyles(this.styles)

    $(this.containers.main).toggleClass("mapsvg-invisible", !this.opened)

    this.containers.view = $("<div />")
      .attr("id", "mapsvg-controller-" + this.name)
      .addClass("mapsvg-controller-view")[0]

    // Wrap cointainer, includes scrollable container
    this.containers.contentWrap = $("<div />").addClass("mapsvg-controller-view-wrap")[0]
    this.containers.contentWrap2 = $("<div />")[0]

    // Scrollable container
    this.containers.sizer = $("<div />").addClass("mapsvg-auto-height")[0]
    this.containers.contentView = $("<div />").toggleClass(
      "mapsvg-controller-view-content",
      !this.noPadding,
    )[0]
    this.containers.sizer.appendChild(this.containers.contentView)

    if (this.scrollable) {
      $(this.containers.contentWrap).addClass("nano")
      $(this.containers.contentWrap2).addClass("nano-content")
    }
    this.containers.contentWrap.appendChild(this.containers.contentWrap2)
    this.containers.contentWrap2.appendChild(this.containers.sizer)

    // Add toolbar if it exists in template file
    if (this.withToolbar && this.templates.toolbar) {
      this.containers.toolbar = $("<div />").addClass("mapsvg-controller-view-toolbar")[0]
      this.containers.view.appendChild(this.containers.toolbar)
    }

    this.containers.view.append(this.containers.contentWrap)
    this.containers.main.append(this.containers.view)

    // Add view into container
    this.containers.parent.appendChild(this.containers.main)

    if (this.autoresize) {
      this.resizeSensor = new ResizeSensor(this.containers.sizer, () => {
        this.adjustHeight()
        this.updateScroll()
        this.events.trigger(ControllerEvent.RESIZE, { controller: this })
      })
    }

    if (this.closable && !this.mobileCloseBtn) {
      this.mobileCloseBtn = $(
        '<button class="mapsvg-mobile-modal-close">' +
          this.map.getData().options.mobileView.labelClose +
          "</button>",
      )[0]
      $(this.containers.view).append(this.mobileCloseBtn)
    }

    this.loaded = true
    this.viewDidLoad()
    this.events.trigger(ControllerEvent.AFTER_LOAD, { controller: this })

    setTimeout(() => {
      this.setEventHandlers()
      this.redraw()
    }, 1)
  }

  setState(state?: { [key: string]: any }, overwrite: boolean = false): void {
    if (overwrite) {
      this.state = state
    } else {
      this.state = { ...this.state, ...state }
    }

    if (this.loaded) {
      this.redraw()
    }
  }

  /**
   * Redraws the container.
   */
  redraw() {
    if (this.destroyed) {
      return
    }
    this.events.trigger(ControllerEvent.BEFORE_REDRAW, { controller: this })
    let formattedData = { ...this.state }
    formattedData = this.middlewares.run(MiddlewareType.RENDER, [
      formattedData,
      { controller: this },
    ])

    this.viewWillRedraw()

    try {
      $(this.containers.contentView).html(this.templates.main(formattedData))
    } catch (err) {
      console.error(err)
      $(this.containers.contentView).html("")
    }

    if (this.withToolbar && this.templates.toolbar)
      $(this.containers.toolbar).html(this.templates.toolbar(formattedData))

    this.resize()

    this.viewDidRedraw()
    this.events.trigger(ControllerEvent.AFTER_REDRAW, { controller: this })
  }

  resize(): void {
    this.updateTopShift()

    this.updateScroll()
    if (this.autoresize) {
      this.adjustHeight()
      this.resizeSensor.setScroll()
    }
  }

  /**
   * Updates top shift of the main container depending on toolbar height
   */
  updateTopShift() {
    const _this = this
    if (!this.withToolbar) return
    // bad, i know.
    $(_this.containers.contentWrap).css({
      top: $(_this.containers.toolbar).outerHeight(true) + "px",
    })
    setTimeout(function () {
      $(_this.containers.contentWrap).css({
        top: $(_this.containers.toolbar).outerHeight(true) + "px",
      })
    }, 100)
    setTimeout(function () {
      $(_this.containers.contentWrap).css({
        top: $(_this.containers.toolbar).outerHeight(true) + "px",
      })
    }, 200)
    setTimeout(function () {
      $(_this.containers.contentWrap).css({
        top: $(_this.containers.toolbar).outerHeight(true) + "px",
      })
      _this.updateScroll()
    }, 500)
  }

  /**
   * Sets event handlers
   */
  setEventHandlers() {
    const closeHandler = (e) => {
      e.preventDefault() // Prevent default browser behavior
      e.stopPropagation() // Stop event bubbling
      e.stopImmediatePropagation() // Stop other handlers on same element

      if (e.type === "click" || e.type === "touchend") {
        this.close()
      }
      return false
    }
    if (this.closable) {
      $(this.containers.toolbar).on(
        "click touchend touchstart mousedown",
        ".mapsvg-popover-close",
        (e) => {
          closeHandler(e)
        },
      )
      $(this.containers.view).on(
        "click touchend touchstart mousedown",
        ".mapsvg-mobile-modal-close",
        (e) => {
          closeHandler(e)
        },
      )

      $(window)
        .off("keydown.details.mapsvg-" + this.map.id)
        .on("keydown.details.mapsvg-" + this.map.id, (e) => {
          if (e.keyCode == 27) this.close()
        })
    }

    return
  }

  open(): void {
    if (!this.closable && this.destroyed) {
      return
    }
    this.events.trigger(ControllerEvent.BEFORE_SHOW, { controller: this })
    $(this.containers.main).toggleClass("mapsvg-invisible", false)
    this.opened = true
    this.resize()
    this.viewDidAppear()
    this.events.trigger(ControllerEvent.AFTER_SHOW, { controller: this })
  }

  /**
   * Closes the container
   *
   */
  close(): void {
    if (!this.closable || !this.canClose || !this.opened) {
      return
    }
    if (this.viewWillClose() === false) {
      return
    }
    if (this.openedFullscreen) {
      document.body.classList.remove("mapsvg-modal-fullscreen")
    }

    this.events.trigger(ControllerEvent.BEFORE_CLOSE, { controller: this })

    $(this.containers.main).toggleClass("mapsvg-invisible", true)
    this.opened = false

    this.events.trigger(ControllerEvent.AFTER_CLOSE, { controller: this })
  }

  /**
   * @ignore
   */
  set canClose(canClose: boolean) {
    this._canClose = canClose
  }

  /**
   * @ignore
   */
  get canClose(): boolean {
    return this._canClose
  }

  /**
   * Abstract method
   * @ignore
   */
  viewWillClose(): boolean {
    return true
  }

  /**
   * Abstract method
   * @ignore
   */
  viewWillGetDestroyed(): void {}

  /**
   * Destroys the controller.
   */
  destroy(): void {
    if (this.destroyed) {
      return
    }
    this.close()
    this.viewWillGetDestroyed()
    this.resizeSensor && this.resizeSensor.destroy()
    delete this.resizeSensor
    $(this.containers.view).empty().remove()
    $(this.containers.main).empty().remove()
    $(window).off("keydown.details.mapsvg-" + this.map.id)
    $("body").off("mouseup.popover.mapsvg touchend.popover.mapsvg")
    this.events.trigger(ControllerEvent.AFTER_UNLOAD, { controller: this })
    this.events = null
    this.destroyed = true
  }

  setOptions(options: Record<string, any>): void {}

  is(name: string) {
    if (name === "details" || name === "modal") {
      name = "detailsView"
    }
    return this.name === name
  }
}
