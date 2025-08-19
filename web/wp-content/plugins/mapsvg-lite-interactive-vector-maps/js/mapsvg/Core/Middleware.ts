import { MapSVGMap } from "@/Map/Map"
import { MapOptions } from "@/Map/OptionsInterfaces/MapOptions"
import { BaseController } from "./Controller"
import { MapsvgRequest, MapsvgResponse, Repository } from "./Repository"

export enum MiddlewareType {
  REQUEST = "request",
  RESPONSE = "response",
  RENDER = "render",
  MAP_LOAD = "mapLoad",
}

export type MiddlewareCtx<T> = T

export type RequestMiddleware = (
  data: unknown,
  ctx: MiddlewareCtx<{ request: MapsvgRequest; repository: Repository; map: MapSVGMap }>,
) => unknown
export type ResponseMiddleware = (
  data: unknown,
  ctx: MiddlewareCtx<{
    request: MapsvgRequest
    response: MapsvgResponse
    repository: Repository
    map: MapSVGMap
  }>,
) => MapsvgResponse
export type RenderMiddleware = (
  data: Record<string, unknown>,
  ctx: MiddlewareCtx<{ controller: BaseController }>,
) => Record<string, unknown>
export type MapLoadMiddleware = (
  data: MapOptions,
  ctx: MiddlewareCtx<{ map: MapSVGMap }>,
) => MapOptions

export type MiddlewareHandler =
  | RequestMiddleware
  | ResponseMiddleware
  | RenderMiddleware
  | MapLoadMiddleware

export type MiddlewareHandlers = {
  request: RequestMiddleware
  response: ResponseMiddleware
  render: RenderMiddleware
  mapLoad: MapLoadMiddleware
}

export type MiddlewareOptions = {
  unique?: boolean
}

export interface Middleware {
  name: MiddlewareType
  handler: MiddlewareHandler
  options: MiddlewareOptions
}

export class MiddlewareList {
  middlewares: Middleware[]
  constructor() {
    this.middlewares = []
  }

  add<T extends MiddlewareType>(
    name: T,
    handler: MiddlewareHandlers[T],
    unique = false,
  ): Middleware[] {
    if (unique) {
      const middleware = this.middlewares.find(
        (middleware) => middleware.name === name && middleware.options.unique,
      )
      if (middleware) {
        this.off(name, middleware.handler)
      }
    }
    if (handler) {
      this.middlewares.push({
        name,
        handler,
        options: {
          unique: unique ?? false,
        },
      })
    }
    return this.middlewares
  }

  get(name: MiddlewareType.REQUEST): RequestMiddleware[]
  get(name: MiddlewareType.RESPONSE): ResponseMiddleware[]
  get(name: MiddlewareType.RENDER): RenderMiddleware[]
  get(name: MiddlewareType.MAP_LOAD): MapLoadMiddleware[]
  get(name: any): Middleware["handler"][] {
    return this.middlewares.filter((m) => m.name === name).map((m) => m.handler)
  }

  clear(): Middleware[] {
    this.middlewares.splice(0, this.middlewares.length - 1)
    return this.middlewares
  }

  off(eventName: string, handler?: MiddlewareHandler) {
    const events = this.middlewares.filter((event) => event.name === eventName)
    if (events.length) {
      events.forEach((event, index) => {
        if (typeof handler === "undefined" || event.handler === handler) {
          const indexOfEvent = this.middlewares.indexOf(event)
          this.middlewares.splice(indexOfEvent, 1)
        }
      })
    }
    return this
  }

  removeByType(type: MiddlewareType): Middleware[] {
    this.middlewares = this.middlewares.filter((middleware) => middleware.name !== type)
    return this.middlewares
  }

  run(type: MiddlewareType.REQUEST, params: Parameters<RequestMiddleware>): Record<string, unknown>
  run(
    type: MiddlewareType.RESPONSE,
    params: Parameters<ResponseMiddleware>,
  ): string | Record<string, unknown>
  run(type: MiddlewareType.RENDER, params: Parameters<RenderMiddleware>): Record<string, unknown>
  run(type: MiddlewareType.MAP_LOAD, params: Parameters<MapLoadMiddleware>): MapOptions
  run(
    type: MiddlewareType,
    params: Parameters<
      RequestMiddleware | ResponseMiddleware | RenderMiddleware | MapLoadMiddleware
    >,
  ): string | any | MapOptions {
    const middlewares = this.middlewares.filter((m) => m.name === type).map((m) => m.handler)
    let data: Record<string, any> = params[0]
    for (const middleware of middlewares) {
      let res: any
      try {
        res = middleware.apply(this, params)
      } catch (e) {
        console.error(`Error in middleware ${type}:`, e)
        res = params[0]
      }
      data = res
    }
    return data
  }
}
