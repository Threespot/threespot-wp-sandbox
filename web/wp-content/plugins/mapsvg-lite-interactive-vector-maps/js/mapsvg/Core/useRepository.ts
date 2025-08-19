import { Schema, SchemaOptions } from "@/Infrastructure/Server/Schema"
import { SchemaModelSchema } from "@/Infrastructure/Server/SchemaModel"
import { SchemaRepository } from "@/Infrastructure/Server/SchemaRepository"
import { MapSVGMap } from "@/Map/Map"
import { MapsRepository } from "@/Map/MapsRepository"
import { ArrayIndexed } from "./ArrayIndexed"
import { MiddlewareType } from "./Middleware"
import { Repository } from "./Repository"

function useRepository(name: "regions" | SchemaOptions, map: MapSVGMap): Repository
function useRepository(name: "objects" | SchemaOptions, map: MapSVGMap): Repository
function useRepository(name: "schemas" | SchemaOptions, map: MapSVGMap): SchemaRepository
function useRepository(name: "maps" | SchemaOptions, map: MapSVGMap): MapsRepository

function useRepository(
  name: string | SchemaOptions,
  map: MapSVGMap,
): Repository | SchemaRepository | MapsRepository {
  const repos = {
    maps: {
      class: MapsRepository,
      schema: {
        name: "maps",
        objectNameSingular: "map",
        objectNamePlural: "maps",
        apiEndpoints: [
          { url: "maps", method: "GET", name: "index" },
          { url: "maps/[:id]", method: "GET", name: "show" },
          { url: "maps", method: "POST", name: "create" },
          { url: "maps/createFromV2", method: "POST", name: "createFromV2" },
          { url: "maps/[:id]/copy", method: "POST", name: "copy" },
          { url: "maps/[:id]", method: "PUT", name: "update" },
          { url: "maps/[:id]", method: "DELETE", name: "delete" },
          { url: "maps", method: "DELETE", name: "clear" },
        ],
        fields: [
          {
            name: "id",
            label: "ID",
            type: "ID",
            db_type: "int(11)",
            visible: true,
            protected: true,
          },
          {
            name: "title",
            label: "Title",
            type: "text",
            db_type: "varchar(255)",
            visible: true,
          },
          {
            name: "options",
            label: "Options",
            type: "textarea",
            db_type: "longtext",
            visible: true,
          },
          {
            name: "svgFilePath",
            label: "SVG file path",
            type: "text",
            db_type: "varchar(512)",
            visible: true,
          },
          {
            name: "svgFileLastChanged",
            label: "SVG file last changed",
            type: "text",
            db_type: "int(11)",
            visible: true,
          },
          {
            name: "version",
            label: "Version",
            type: "text",
            db_type: "varchar(50)",
            visible: true,
          },
          {
            name: "status",
            label: "Status",
            type: "text",
            db_type: "tinyint",
            visible: false,
          },
          {
            name: "statusChangedAt",
            label: "Status Changed At",
            type: "text",
            db_type: "timestamp",
            visible: false,
          },
        ],
      },
    },
    regions: {
      class: Repository,
      schema: {
        objectNameSingular: "region",
        objectNamePlural: "regions",
        apiEndpoints: [
          { url: "regions/[:name]", method: "GET", name: "index" },
          { url: "regions/[:name]/[:id]", method: "GET", name: "show" },
          { url: "regions/[:name]", method: "POST", name: "create" },
          { url: "regions/[:name]/[:id]", method: "PUT", name: "update" },
          { url: "regions/[:name]/[:id]/import", method: "POST", name: "import" },
          { url: "regions/[:name]/[:id]", method: "DELETE", name: "delete" },
        ],
      },
    },
    objects: {
      class: Repository,
      schema: {
        objectNameSingular: "object",
        objectNamePlural: "objects",
        apiEndpoints: [
          { url: "objects/[:name]", method: "GET", name: "index" },
          { url: "objects/[:name]/[:id]", method: "GET", name: "show" },
          { url: "objects/[:name]", method: "POST", name: "create" },
          { url: "objects/[:name]/[:id]", method: "PUT", name: "update" },
          { url: "objects/[:name]/[:id]", method: "DELETE", name: "delete" },
          { url: "objects/[:name]/[:id]/import", method: "POST", name: "import" },
          { url: "objects/[:name]", method: "DELETE", name: "clear" },
        ],
      },
    },
    schemas: {
      class: SchemaRepository,
      schema: SchemaModelSchema,
    },
    logs: {
      class: Repository,
      schema: {
        objectNameSingular: "log",
        objectNamePlural: "logs",
      },
    },
    tokens: {
      class: Repository,
      schema: {
        name: "tokens",
        objectNameSingular: "token",
        objectNamePlural: "tokens",
        apiEndpoints: new ArrayIndexed("name", [
          {
            name: "index",
            url: "tokens",
            method: "GET",
          },
          {
            name: "create",
            url: "tokens",
            method: "POST",
          },
          {
            name: "delete",
            url: "tokens/[:id]",
            method: "DELETE",
          },
        ]),
        fields: [
          {
            name: "id",
            label: "ID",
            type: "id",
            db_type: "int(11)",
            protected: true,
            auto_increment: true,
          },
          {
            label: "Token",
            name: "token",
            type: "text",
            db_type: "varchar(255)",
          },
          {
            label: "Token first four",
            name: "tokenFirstFour",
            type: "text",
            db_type: "varchar(255)",
          },
          {
            label: "Hashed token",
            name: "hashedToken",
            type: "text",
            db_type: "varchar(255)",
          },
          {
            name: "createdAt",
            label: "Created at",
            type: "datetime",
            db_type: "datetime",
          },
          {
            name: "lastUsedAt",
            label: "Last used at",
            type: "datetime",
            db_type: "datetime",
          },
          {
            name: "accessRights",
            label: "Access rights",
            type: "json",
            db_type: "json",
          },
        ],
      },
    },
  }

  let schema: Schema
  let repo: Repository | SchemaRepository | MapsRepository

  if (typeof name === "string") {
    let type = name
    if (type.indexOf("regions") !== -1) {
      type = "regions"
    } else if (type.indexOf("objects") !== -1 || type.indexOf("database") !== -1) {
      type = "objects"
    }

    schema = new Schema(repos[type].schema)
    if (["objects", "regions"].includes(type)) {
      schema.apiEndpoints.forEach((endpoint) => {
        endpoint.url = endpoint.url.replace(/\[:name\]/, name)
      })
    }
    repo = new repos[type].class(schema, map)

    if ((type === "regions" || type === "objects") && map) {
      const middlewares = map.middlewares.middlewares.filter(
        (m) => m.name === MiddlewareType.REQUEST || m.name === MiddlewareType.RESPONSE,
      )
      middlewares.forEach((middleware) => {
        repo.middlewares.add(middleware.name, middleware.handler, middleware.options.unique)
      })
    }
  } else {
    schema = new Schema(name)
    repo = new Repository(schema, map)
    if ((schema.objectNamePlural === "regions" || schema.objectNamePlural === "objects") && map) {
      const middlewares = map.middlewares.middlewares.filter(
        (m) => m.name === MiddlewareType.REQUEST || m.name === MiddlewareType.RESPONSE,
      )
      middlewares.forEach((middleware) => {
        repo.middlewares.add(middleware.name, middleware.handler, middleware.options.unique)
      })
    }
  }

  repo.init()
  return repo
}

export { useRepository }
