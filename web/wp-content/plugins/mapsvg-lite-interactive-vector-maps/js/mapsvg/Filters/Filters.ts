/**
 * FiltersController class adds filters for the database.
 * @param options
 * @constructor
 */
import { ControllerOptions } from "@/Core/Controller.js"
import { BaseEventHandler } from "@/Core/Events.js"
import { DetailsController, DetailsViewOptions } from "../Details/Details.js"
import { FormBuilder } from "../FormBuilder/FormBuilder.js"
import { Query } from "../Infrastructure/Server/Query.js"
import { Schema } from "../Infrastructure/Server/Schema.js"
import "./filters.css"
const $ = jQuery

export enum FiltersEvent {
  CLICK_SHOW_FILTERS = "click.btnShowFilters",
  CLICK_SEARCH = "click.btnSearch",
}

interface FiltersOptions extends DetailsViewOptions {
  schema: Schema
  hideFilters: boolean
  query: Query
  modal?: boolean
  modalLocation: string
  hideOnMobile: boolean
  showButtonText: string
  clearButton: boolean
  clearButtonText: string
  searchButton: boolean
  searchButtonText: string
  events: ControllerOptions["events"] & {
    [K in FiltersEvent]?: BaseEventHandler
  }
}

export class FiltersController extends DetailsController {
  schema: Schema
  hideFilters: boolean
  query: Query
  modal: boolean
  modalLocation: string
  hideOnMobile: boolean
  hide: boolean
  showButtonText: string
  clearButton: boolean
  clearButtonText: string
  searchButton: boolean
  searchButtonText: string
  formBuilder: FormBuilder

  constructor(options: FiltersOptions) {
    super(options)

    this.name = "filters"
    this.classList = [
      "mapsvg-filters-wrap",
      "mapsvg-filters-container",
      "mapsvg-controller-no-padding",
    ]
    this.position = "relative"
    this.closable = false
    this.withToolbar = false

    this.showButtonText = options.showButtonText
    this.clearButton = options.clearButton
    this.clearButtonText = options.clearButtonText
    this.searchButton = options.searchButton
    this.searchButtonText = options.searchButtonText
    this.schema = options.schema
    this.hideFilters = options.hideFilters
    this.query = options.query
  }

  viewDidRedraw(): void {
    super.viewDidLoad()

    this.formBuilder && this.formBuilder.destroy()
    this.formBuilder = new FormBuilder({
      container: this.containers.contentView,
      filtersMode: true,
      schema: this.schema,
      modal: this.modal,
      filtersHide: this.hideFilters,
      showButtonText: this.showButtonText,
      clearButton: this.clearButton,
      clearButtonText: this.clearButtonText,
      searchButton: this.searchButton,
      searchButtonText: this.searchButtonText,
      editMode: false,
      mapsvg: this.map,
      data: { ...this.query.filters, search: this.query.search },
      admin: false,
      parent: this,
      events: {
        "change.formElement": (event) => {
          const { formElement, field, value } = event.data
          if (formElement.type === "search") {
            this.query.setSearch(value)
          } else {
            const filters: any = {}
            let _value = value
            if (field === "regions") {
              _value = {}
              _value.region_ids = value instanceof Array ? value : [value]
              _value.table_name = this.map.options.database.regionsTableName
              if (_value.region_ids.length === 0 || _value.region_ids[0] === "") {
                _value = null
              }
            }
            if (field === "distance" && (!_value.geoPoint.lat || !_value.geoPoint.lat)) {
              delete filters.distance
              this.query.setFilters({ distance: null })
            } else {
              filters[field] = _value
              this.query.setFilters(filters)
            }
          }
        },
        clear: (event) => {
          const { formElement, field, value } = event.data
          this.query.clearFilters()
        },
        afterLoad: (event) => {
          const { formElement, field, value } = event.data
          this.updateScroll()
        },
      },
    })
    this.formBuilder.init()
  }

  reset(): void {
    this.formBuilder && this.formBuilder.reset()
  }

  update(query: Query): void {
    const _query = Object.assign({}, query.filters)
    _query.search = query.search
    this.formBuilder && this.formBuilder.update(_query)
  }

  /**
   * Sets filters counter on the "Show filters" button when compact mode is enabled and filters are hidden
   */
  setFiltersCounter(): void {
    if (this.hideFilters) {
      // Don't include "search" filter into counter since it's always outside of the modal
      const filtersCounter = Object.keys(this.query.filters).length

      const filtersCounterString = filtersCounter === 0 ? "" : filtersCounter.toString()
      this.formBuilder &&
        this.formBuilder.showFiltersButton &&
        $(this.formBuilder.showFiltersButton.domElements.main)
          .find("button")
          .html(this.showButtonText + " <b>" + filtersCounterString + "</b>")
    }
  }

  setEventHandlers(): void {
    super.setEventHandlers()

    const _this = this

    $(this.containers.view).on("click", ".mapsvg-btn-show-filters", function () {
      _this.events.trigger(FiltersEvent.CLICK_SHOW_FILTERS)
    })
    $(this.containers.view).on("click", "#mapsvg-search-container button", function () {
      _this.events.trigger(FiltersEvent.CLICK_SEARCH)
    })
  }
}
