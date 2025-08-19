// Type definitions for growl
// TypeScript Version: 3.9

/// <reference types="jquery" />

declare namespace JQueryGrowl {
  interface JQueryGrowlInterface {
    error(options: { title?: string; message: string; duration?: number })
    message(options: { title?: string; message: string; duration?: number })
  }
}

interface JQuery {
  popover(options?: any): any
  button(options?: any): any
  buttonLoading(options?: any): any
  tooltip(options?: any): any
  mselect2(options?: any): any
  colorpicker(options?: any): any
  nanoscroller(options?: any): any
  jscrollpane(options?: any): any
  bootstrapToggle(options?: any): any
  justifiedGallery(options?: any): any
  slick(options?: any): any
  growl: JQueryGrowl.JQueryGrowlInterface
  typeahead(
    methodOrOptions?: "destroy" | string | Bootstrap3Typeahead.Options,
    methodOrOptions2?: Bootstrap3Typeahead.Options | "",
  ): JQuery
}

interface JQueryStatic {
  growl: JQueryGrowl.JQueryGrowlInterface
}

// Typeahead
declare namespace Bootstrap3Typeahead {
  interface Options {
    name?: string
    display?: string
    limit?: number

    /**
     * The data source to query against
     */
    source?:
      | string[]
      | Record<string, unknown>[]
      | ((
          query?: string,
          process?: (callback: any) => string | string[] | Record<string, unknown>[],
        ) => void)

    /**
     * The max number of items to display in the dropdown
     */
    items?: number | "all"

    /**
     * The minimum character length needed before triggering autocomplete suggestions
     */
    minLength?: number

    /**
     * If hints should be shown as soon as the input gets focus
     */
    showHintOnFocus?: boolean | "all"

    /**
     * Number of pixels the scrollable parent container scrolled down
     */
    scrollHeight?: number | (() => number)

    /**
     * The method used to determine if a query matches an item
     */
    matcher?: (item: string) => boolean

    /**
     * Method used to sort autocomplete results
     */
    sorter?: (items: string[]) => string[]

    /**
     * The method used to return selected item
     */
    updater?: (item: string) => string

    /**
     * Method used to highlight autocomplete results
     */
    highlighter?: (item: string) => string

    /**
     * Method used to get textual representation of an item of the sources
     */
    displayText?: (item: string | { name: string }) => string

    /**
     * Allows you to dictate whether or not the first suggestion is selected automatically
     */
    autoSelect?: boolean

    /**
     * Call back function to execute after selected an item
     */
    afterSelect?: (this: Typeahead, item: string | Record<string, unknown>) => void

    /**
     * Adds a delay between lookups
     */
    delay?: number

    /**
     * Use this option to add the menu to another div
     */
    appendTo?: JQuery

    /**
     * Set to true if you want the menu to be the same size than the input it is attached to
     */
    fitToElement?: boolean

    /**
     * Adds an item to the end of the list
     */
    addItem?: Record<string, unknown>
  }

  interface Typeahead {
    $element: JQuery
    options: Options
  }
}
