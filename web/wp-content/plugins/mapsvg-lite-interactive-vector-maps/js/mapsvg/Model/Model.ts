import { mapsvgCore } from "@/Core/Mapsvg.js"
import { ucfirst } from "@/Core/Utils.js"
import { Schema } from "../Infrastructure/Server/Schema.js"
import { SchemaField } from "../Infrastructure/Server/SchemaField.js"
import { GeoPoint, Location, LocationOptionsInterface, SVGPoint } from "../Location/Location.js"
import { LocationAddress } from "../Location/LocationAddress.js"
import { Region } from "../Region/Region.js"

const handler = {
  set(target, property, value, receiver) {
    if (property in target) {
      // If the property exists directly on the target, set it
      target[property] = value
    } else {
      target.build({ [property]: value })
    }
    return true // Indicate success
  },
  get(target, property, receiver) {
    if (property in target._data) {
      return target._data[property]
    } else {
      if (typeof target[property] === "function") {
        return function (...args) {
          // Call the method on the parent instance
          return target[property](...args)
        }
      } else if (property in target) {
        return target[property]
      } else {
        return undefined
      }
    }
  },
}

export type RegionToModel = { id: string; title: string; tableName: string }

export class Model {
  schema: Schema
  dirtyFields: string[]
  initialLoad = true
  private _data: { location?: Location; regions?: RegionToModel[]; [key: string]: any } = {}

  constructor(params: any, schema: Schema) {
    if (schema) {
      this.setSchema(schema)
    }
    this.dirtyFields = []
    this.initialLoad = true
    this.build(params)
    this.initialLoad = false
    if (this._data.id) {
      this.clearDirtyFields()
    }

    return new Proxy(this, handler)
  }

  private setSchema(schema: Schema): void {
    this.schema = schema
  }
  // Catch-all getter
  get(key: string): any {
    if (this.schema.getField(key)) {
      return this._data[key]
    }
  }
  // Catch-all setter
  set(key: string, value: any): void {
    if (this.schema.getField(key)) {
      this.update({ [key]: value })
    }
  }

  update(params: any): void {
    const res = this.build(params)
    return res
  }

  build(params: any): void {
    for (const fieldName in params) {
      let field = this.schema.getField(fieldName)

      // If schema is strict and field was not found, skip it:
      if (!field) {
        if (this.schema.strict) {
          continue
        } else {
          field = new SchemaField({
            type: "text",
            name: fieldName,
            label: ucfirst(fieldName),
            db_type: "text",
          })
          this.schema.addField(field)
        }
      }

      if (!this.initialLoad) {
        this.dirtyFields.push(fieldName)
      }

      switch (field.type) {
        case "region":
          this._data.regions = params[fieldName]
          break
        case "location":
          if (
            params[fieldName] != null &&
            params[fieldName] != "" &&
            Object.keys(params[fieldName]).length !== 0
          ) {
            if (params[fieldName] instanceof Location) {
              this._data.location = params[fieldName]
            } else {
              const data: LocationOptionsInterface = {
                img: this.isMarkersByFieldEnabled() ? this.getMarkerImage() : params[fieldName].img,
                address: new LocationAddress(params[fieldName].address),
              }
              if (
                params[fieldName].geoPoint &&
                params[fieldName].geoPoint.lat &&
                params[fieldName].geoPoint.lng
              ) {
                data.geoPoint = new GeoPoint(params[fieldName].geoPoint)
              } else if (
                params[fieldName].svgPoint &&
                params[fieldName].svgPoint.x &&
                params[fieldName].svgPoint.y
              ) {
                data.svgPoint = new SVGPoint(params[fieldName].svgPoint)
              }
              if (this._data.location != null) {
                this._data.location.update(data)
              } else {
                this._data.location = new Location(data)
              }
            }
          } else {
            this._data.location = null
          }
          break
        case "datetime":
          if (params[fieldName] != null && params[fieldName] != "") {
            this._data[fieldName] = new Date(params[fieldName])
          } else {
            this._data[fieldName] = null
          }
          break
        case "json":
          this._data[fieldName] =
            typeof params[fieldName] === "string"
              ? JSON.parse(params[fieldName])
              : params[fieldName]
          break
        case "post":
        case "select":
        case "radio":
        default:
          this._data[fieldName] = params[fieldName]
          break
      }
    }
    const locationField = this.getLocationField()
    if (locationField && this.isMarkersByFieldEnabled() && this.isMarkerFieldChanged(params)) {
      this.reloadMarkerImage()
    }
  }

  isMarkerFieldChanged(params: { [key: string]: any }): boolean {
    return Object.keys(params).indexOf(this.getLocationField().markerField) !== -1
  }

  setLocationField(): void {}

  getLocationField(): SchemaField {
    return this.schema.getFieldByType("location")
  }

  reloadMarkerImage(): void {
    this._data.location && this._data.location.setImage(this.getMarkerImage())
  }

  getMarkerImage(): string {
    let fieldValue

    if (this.isMarkersByFieldEnabled()) {
      // @ts-ignore
      const locationField = this.getLocationField()
      fieldValue = this._data[locationField.markerField]
      if (!fieldValue) {
        return locationField.defaultMarkerPath || mapsvgCore.defaultMarkerImage
      } else {
        if (locationField.markerField === "regions") {
          fieldValue = fieldValue[0] && fieldValue[0].id
        } else if (typeof fieldValue === "object" && fieldValue.length) {
          fieldValue = fieldValue[0].value
        }
        // @ts-ignore
        return (
          locationField.markersByField[fieldValue] ||
          locationField.defaultMarkerPath ||
          mapsvgCore.defaultMarkerImage
        )
      }
    } else {
      return this._data.location.imagePath
    }
  }

  isMarkersByFieldEnabled(): boolean {
    const locationField = this.getLocationField()
    if (!locationField) {
      return false
    }
    // @ts-ignore
    if (
      locationField.markersByFieldEnabled &&
      locationField.markerField &&
      Object.values(locationField.markersByField).length > 0
    ) {
      return true
    } else {
      return false
    }
  }

  clone(): Model {
    const data = this.getData()
    return new Model(data, this.schema)
  }

  private getEnumLabel(field, params: any, fieldName: string): string {
    const value = field.options.get(params[fieldName])
    if (typeof value !== "undefined") {
      return value.label
    } else {
      return ""
    }
  }

  getDirtyFields(): { [key: string]: any } {
    const data: any = {}
    this.dirtyFields.forEach((field) => {
      data[field] = this._data[field]
    })
    data.id = this._data.id
    if (data.location != null && data.location instanceof Location) {
      data.location = data.location.getData()
    }
    if (this.schema.getFieldByType("region")) {
      data.regions = this._data.regions
    }
    return data
  }
  clearDirtyFields(): void {
    this.dirtyFields = []
  }

  getData(): { [key: string]: any } {
    const data = {}
    for (const name in this._data) {
      if (
        this._data[name] !== null &&
        this._data[name] !== undefined &&
        typeof this._data[name].getData === "function"
      ) {
        data[name] = this._data[name].getData()
      } else {
        data[name] = this._data[name]
      }
      const field = this.schema.getField(name)
      if (["select", "radio", "enum", "status"].includes(field.type)) {
        data[name + "_text"] = field.getEnumLabel(data[name])
      }
    }
    return data
  }
  getRegions(regionsTableName: string): Array<{ id: string; title: string }> {
    return this._data.regions
  }
  getRegionsForTable(regionsTableName: string): Array<{ id: string; title: string }> {
    return this._data.regions
      ? this._data.regions.filter(
          // !region.tableName is a fix for broken settings after v6 release
          (region) => !region.tableName || region.tableName === regionsTableName,
        )
      : []
  }

  linkToRegions(regions: Region[], tableName: string): void {
    if (regions.length === 0) {
      return
    }
    const regionsInObject = this.getRegionsForTable(tableName)
    const regionIds = regionsInObject.map((region) => region.id)

    if (regionIds.length > 0) {
      regions
        .filter((region) => regionIds.includes(region.id))
        .forEach((region) => {
          region.objects.clear()
          region.objects.push(this)
        })
    }
  }
}
