import { MapSVGMap } from "../Map/Map"

interface BaseEvent {
  clientX: number
  clientY: number
  target: EventTarget
  preventDefault(): void
  stopPropagation(): void
}

function isGenericEvent(event?: any): event is GenericEvent {
  return event && "clientX" in event && "clientY" in event
}

interface MouseEventLike extends BaseEvent {
  button: number
  buttons: number
  relatedTarget: EventTarget | null
  // ... other MouseEvent properties
}

interface TouchEventLike extends BaseEvent {
  touches: TouchList
  targetTouches: TouchList
  changedTouches: TouchList
  // ... other TouchEvent properties
}

type GenericEvent = MouseEventLike | TouchEventLike | any

export type EventData<T extends Record<string, unknown> = Record<string, unknown>> = T

export type EventWithData<T extends Record<string, unknown> = Record<string, unknown>> = {
  name: string
  domEvent?: MouseEvent
  map: MapSVGMap
  data: EventData<T>
}

export type BaseEventHandler<T extends Record<string, unknown> = Record<string, unknown>> = (
  event: EventWithData<T>,
) => void

type EventOptions = {
  repeat?: number
  unique?: boolean
}
interface EventParams {
  handler: BaseEventHandler
  name: string
  options?: EventOptions
}

enum EventBaseType {}

type EventHandlerMap<T extends EventBaseType> = {
  [key in keyof T as string]: BaseEventHandler
}

class EventListener implements EventParams {
  handler: BaseEventHandler
  options: EventOptions
  name: string
  constructor(options: EventParams) {
    this.handler = options.handler
    this.name = options.name
    this.options = options.options || {}
  }
}

export class Events {
  context: any
  contextName: string
  map?: MapSVGMap
  listeners: EventListener[]
  bubbleTo?: Events

  constructor({
    context,
    contextName,
    parent,
    map,
  }: {
    context: any
    contextName: string
    parent?: Events
    map?: MapSVGMap
  }) {
    this.listeners = new Array<EventListener>()
    this.context = context
    this.contextName = contextName
    this.bubbleTo = parent
    this.map = map
  }

  on(eventName: string, callback: BaseEventHandler, options?: EventOptions): this {
    if (options?.unique) {
      const listener = this.listeners.find(
        (listener) => listener.name === eventName && listener.options.unique,
      )
      if (listener) {
        this.off(eventName, listener.handler)
      }
    }
    const event = new EventListener({
      handler: callback,
      name: eventName,
      options,
    })
    this.listeners.push(event)
    return this
  }

  once(event: string, callback: BaseEventHandler): this {
    return this.on(event, callback, { repeat: 1 })
  }

  off(eventName: string, handler?: BaseEventHandler) {
    const events = this.listeners.filter((event) => event.name === eventName)
    if (events.length) {
      events.forEach((event, index) => {
        if (typeof handler === "undefined" || event.handler === handler) {
          const indexOfEvent = this.listeners.indexOf(event)
          this.listeners.splice(indexOfEvent, 1)
        }
      })
    }
    return this
  }

  trigger(eventName: string, e?: GenericEvent, data?: Record<string, unknown> | Array<unknown>)
  trigger(eventName: string, data?: Record<string, unknown> | Array<unknown>): void

  trigger(
    eventName: string,
    arg1?: GenericEvent | Record<string, unknown> | Array<unknown>,
    arg2?: Record<string, unknown> | Array<unknown>,
  ): this {
    const e = isGenericEvent(arg1) ? arg1 : undefined
    const data = (isGenericEvent(arg1) ? arg2 : arg1) ?? {}
    const events = this.listeners.filter((event) => event.name === eventName)
    const eventData = {
      domEvent: e,
      data,
      name: eventName,
      map: this.map,
    }
    if (events.length > 0) {
      const toRemove = []

      events.forEach((event, index) => {
        try {
          if ((event.name as string).indexOf("old") !== -1) {
            const args = Array.from(Object.values(data))
            event.handler && event.handler.apply(this.context, args || [this.context])
          } else {
            event.handler && event.handler.apply(this.context, [eventData])
          }
          if (event.options.repeat) {
            event.options.repeat--
          }
          if (event.options.repeat === 0) {
            toRemove.push(event)
          }
        } catch (err) {
          console.error(err)
        }
      })

      // Filter out the events that are no longer needed
      this.listeners = this.listeners.filter((event, index) => !toRemove.includes(event))
    }

    if (this.bubbleTo) {
      if (e) {
        this.bubbleTo.trigger(eventName + "." + this.contextName, e, data)
      } else {
        this.bubbleTo.trigger(eventName + "." + this.contextName, data)
      }
    } else {
      const event = new CustomEvent(eventName + ".mapsvg", { detail: eventData })
      window.dispatchEvent(event)
    }
    return this
  }
}
