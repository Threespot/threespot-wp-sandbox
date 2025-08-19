import { mapsvgCore } from "@/Core/Mapsvg"
import { addTrailingSlash, removeLeadingSlash } from "@/Core/Utils"

const $ = jQuery

/**
 * Server class is used to query mapsvg backend services
 */
export class Server {
  apiUrl: string
  completeChunks: number
  putAvailable: boolean | null = null
  deleteAvailable: boolean | null = null

  constructor(apiUrl: string) {
    this.apiUrl = apiUrl
  }
  getUrl(path) {
    return path.indexOf("http:") === 0 || path.indexOf("https:") === 0
      ? path
      : addTrailingSlash(this.apiUrl) + removeLeadingSlash(path)
  }
  get(path: string, data?: any): JQueryPromise<any> {
    return $.ajax({
      url: this.getUrl(path),
      type: "GET",
      data: data,
      beforeSend: (xhr) => {
        this.addNonceHeader(xhr)
      },
    })
  }
  fetch(path: string, data?: any): Promise<any> {
    return fetch(path.indexOf("http:") === 0 ? path : this.apiUrl + path)
  }
  post(path: string, data?: any): JQueryPromise<any> {
    const ajaxParams = {
      url: this.getUrl(path),
      type: "POST",
      data: data,
      beforeSend: (xhr) => {
        this.addNonceHeader(xhr)
      },
    }

    if (data instanceof FormData) {
      ajaxParams["processData"] = false
      ajaxParams["contentType"] = false
    }

    if (ajaxParams["processData"] !== false) {
      this.processObjectData(data)
    }

    return $.ajax(ajaxParams)
  }
  put(path: string, data?: any): JQueryPromise<any> {
    return $.Deferred((deferred) => {
      this.ensureMethodAvailability("PUT").then((putAvailable) => {
        const ajaxParams: any = {
          url: this.getUrl(path),
          type: putAvailable ? "PUT" : "POST",
          data: data,
          beforeSend: (xhr) => {
            this.addNonceHeader(xhr)
            if (!putAvailable) {
              xhr.setRequestHeader("X-HTTP-Method-Override", "PUT")
            }
          },
        }

        if (data instanceof FormData) {
          ajaxParams.processData = false
          ajaxParams.contentType = false
        }

        if (ajaxParams["processData"] !== false) {
          this.processObjectData(data)
        }

        $.ajax(ajaxParams)
          .done((data) => deferred.resolve(data))
          .fail((jqXHR, textStatus, errorThrown) => deferred.reject(jqXHR, textStatus, errorThrown))
      })
    }).promise()
  }
  delete(path: string): JQueryPromise<any> {
    return $.Deferred((deferred) => {
      this.ensureMethodAvailability("DELETE").then((deleteAvailable) => {
        $.ajax({
          url: this.getUrl(path),
          type: deleteAvailable ? "DELETE" : "POST",
          beforeSend: (xhr) => {
            this.addNonceHeader(xhr)
            if (!deleteAvailable) {
              xhr.setRequestHeader("X-HTTP-Method-Override", "DELETE")
            }
          },
        })
          .done((data) => deferred.resolve(data))
          .fail((jqXHR, textStatus, errorThrown) => deferred.reject(jqXHR, textStatus, errorThrown))
      })
    }).promise()
  }
  ajax(
    path: string,
    data: { type: string; data: any; processData?: boolean; contentType?: boolean },
  ): JQueryPromise<any> {
    // @ts-ignore
    data.url = this.getUrl(path)
    // @ts-ignore
    data.beforeSend = (xhr) => {
      this.addNonceHeader(xhr)
    }
    // @ts-ignore
    return $.ajax(data)
  }

  private ensureMethodAvailability(method: "PUT" | "DELETE"): Promise<boolean> {
    if (method === "PUT" && this.putAvailable !== null) {
      return Promise.resolve(this.putAvailable)
    }
    if (method === "DELETE" && this.deleteAvailable !== null) {
      return Promise.resolve(this.deleteAvailable)
    }
    return this.checkMethodAvailability(method).then((available) => {
      if (method === "PUT") {
        this.putAvailable = available
      } else {
        this.deleteAvailable = available
      }
      return available
    })
  }

  async checkMethodAvailability(method: "PUT" | "DELETE"): Promise<boolean> {
    try {
      await $.ajax({
        url: this.getUrl("/method-check"),
        type: method,
        beforeSend: (xhr) => {
          this.addNonceHeader(xhr)
        },
      })
      return true
    } catch (error) {
      return false
    }
  }

  /**
   * jQuery strips out empty arrays, making it impossible (for example) to delete the last image in collection
   * Here we replace empty arrays with null values
   */
  processObjectData(data) {
    if (data instanceof Object) {
      Object.keys(data).forEach((key) => {
        if (Array.isArray(data[key]) && data[key].length === 0) {
          data[key] = null
        } else if (data[key] instanceof Object) {
          this.processObjectData(data[key])
        }
      })
    }
  }

  private addNonceHeader(xhr: JQueryXHR): void {
    if (mapsvgCore.nonce) {
      xhr.setRequestHeader("X-WP-Nonce", mapsvgCore.nonce)
    }
  }
}
