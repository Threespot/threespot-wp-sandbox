import { ArrayIndexed } from "@/Core/ArrayIndexed"
import { Model } from "@/Model/Model"
import { Schema } from "./Schema"

export const SchemaModelSchema = new Schema({
  id: -1,
  type: "schema",
  name: "schema",
  title: "Schema",
  apiEndpoints: new ArrayIndexed("name", [
    {
      name: "index",
      url: "schemas",
      method: "GET",
    },
    {
      name: "show",
      url: "schemas/[:id]",
      method: "GET",
    },
    {
      name: "create",
      url: "schemas",
      method: "POST",
    },
    {
      name: "update",
      url: "schemas/[:id]",
      method: "PUT",
    },
    {
      name: "delete",
      url: "schemas/[:id]",
      method: "DELETE",
    },
    {
      name: "clear",
      url: "schemas",
      method: "DELETE",
    },
  ]),
  objectNameSingular: "schema",
  objectNamePlural: "schemas",
  strict: true,
  remote: false,
  fields: [
    {
      name: "id",
      type: "id",
    },
    {
      name: "type",
      type: "text",
    },
    {
      name: "title",
      type: "text",
    },
    {
      name: "fields",
      type: "text",
    },
    {
      name: "apiEndpoints",
      type: "text",
    },
    {
      name: "objectNameSingular",
      type: "text",
    },
    {
      name: "objectNamePlural",
      type: "text",
    },
    {
      name: "strict",
      type: "boolean",
    },
    {
      name: "remote",
      type: "boolean",
    },
  ],
})

/**
 * Schema Model class
 */
export class SchemaModel extends Model {}
