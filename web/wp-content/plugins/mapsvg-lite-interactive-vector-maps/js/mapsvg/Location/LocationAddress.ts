/**
 * LocationAddress stores address fields (usually received from Google Geocoding API service)
 */
export class LocationAddress {
  route?: string
  address_formatted?: string
  administrative_area_level_1?: string
  administrative_area_level_1_short?: string
  administrative_area_level_2?: string
  administrative_area_level_2_short?: string
  country?: string
  country_short?: string
  postal_code?: string
  state?: string
  state_short?: string
  zip?: string
  county?: string

  constructor(fields: object) {
    for (const i in fields) {
      this[i] = fields[i]
    }
    this.state = this._state
    this.state_short = this._state_short
    this.zip = this._zip
    this.county = this._county
  }

  getData(): { [key: string]: string } {
    const copy: { [key: string]: string } = {}

    ;[
      "route",
      "address_formatted",
      "administrative_area_level_1",
      "administrative_area_level_1_short",
      "administrative_area_level_2",
      "administrative_area_level_2_short",
      "country",
      "country_short",
      "postal_code",
    ].forEach((field) => {
      if (this[field]) {
        copy[field] = this[field]
      }
    })
    return copy
  }

  get _state() {
    return this.country_short === "US" ? this.administrative_area_level_1 : null
  }

  get _state_short() {
    return this.country_short === "US" ? this.administrative_area_level_1_short : null
  }

  get _county() {
    return this.country_short === "US" ? this.administrative_area_level_2 : null
  }

  get _zip() {
    return this.postal_code
  }
}
