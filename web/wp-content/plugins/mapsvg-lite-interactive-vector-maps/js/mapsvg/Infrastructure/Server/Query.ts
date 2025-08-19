interface QueryInterface {
  sort?: Array<{ field: string; order: string }>
  sortBy?: string
  sortDir?: string
  page?: number
  lastpage?: boolean
  perpage?: number
  search?: string
  searchField?: string
  searchFallback?: boolean
  filters?: { regions?: { table_name: string; region_ids: string[] }; [key: string]: any }
  filterout?: { [key: string]: string | number }
  withSchema?: boolean
}

/**
 * Query class is used to construct a query that can be passed to the server to get objects or regions
 */
export class Query implements QueryInterface {
  sort?: Array<{ field: string; order: string }>
  sortBy?: string
  sortDir?: string
  page?: number
  lastpage?: boolean
  perpage?: number
  search?: string
  searchField?: string
  searchFallback?: boolean
  filters?: { regions?: { table_name: string; region_ids: string[] }; [key: string]: any }
  filterout?: { [key: string]: string | number }
  withSchema?: boolean

  constructor(options?: QueryInterface) {
    this.filters = {}
    this.filterout = {}
    this.page = 1
    this.setSearch("")
    if (options) {
      for (const i in options) {
        if (typeof options[i] !== "undefined") {
          this[i] = options[i]
        }
      }
    }
  }

  setFields(fields) {
    const _this = this
    for (const key in fields) {
      if (key == "filters") {
        _this.setFilters(fields[key])
      } else {
        _this[key] = fields[key]
      }
    }
  }

  update(query: QueryInterface) {
    for (const i in query) {
      if (typeof query[i] !== "undefined") {
        if (i === "filters") {
          this.setFilters(query[i])
        } else {
          this[i] = query[i]
        }
      }
    }
  }

  get() {
    return {
      search: this.search,
      searchField: this.searchField,
      searchFallback: this.searchFallback,
      filters: this.filters,
      filterout: this.filterout,
      page: this.page,
      sort: this.sort,
      perpage: this.perpage,
      lastpage: this.lastpage,
    }
  }

  clearFilters() {
    this.resetFilters()
  }
  setFilters(fields: { [key: string]: string | number }) {
    const _this = this
    for (const key in fields) {
      if (fields[key] === null || fields[key] === "" || fields[key] === undefined) {
        if (_this.filters[key]) {
          delete _this.filters[key]
        }
      } else {
        _this.filters[key] = fields[key]
      }
    }
  }
  setSearch(search: string) {
    this.search = search
  }
  setFilterOut(fields: { [key: string]: string | number } | null): void {
    if (fields === null) {
      delete this.filterout
    } else {
      this.filterout = fields
    }
  }
  resetFilters(fields?: { [key: string]: string | number }) {
    this.filters = {}
    this.setSearch("")
  }
  setFilterField(field: string, value: string | number) {
    this.filters[field] = value
  }
  hasFilters(): boolean {
    return Object.keys(this.filters).length > 0 || (this.search && this.search.length > 0)
  }
  removeFilter(fieldName: string): void {
    this.filters[fieldName] = null
    delete this.filters[fieldName]
  }
  requestSchema(requestSchema: boolean) {
    this.withSchema = requestSchema ? requestSchema : true
  }
}
