Handlebars.registerHelper("ifeq", function (v1, v2, options) {
  if (v1 == v2) {
    return options.fn(this)
  }
  return options.inverse(this)
})
Handlebars.registerHelper("ifselected", function (v1, hash, options) {
  for (const set of hash) {
    if (set.value === v1) {
      return options.fn(this)
    }
  }
  return options.inverse(this)
})
Handlebars.registerHelper("getLabel", function (options, fields, fieldName, _options) {
  return fields[fieldName + "_text"]
  // if(options && fields && fieldName && typeof fields[fieldName] !== "undefined"){
  //     let value = fields[fieldName];
  //     let object = options.get(value);
  //     if(typeof object !== "undefined"){
  //         return object.label;
  //     } else {
  //         return ""
  //     }
  // }else{
  //     return '';
  // }
})
Handlebars.registerHelper("getPostTitle", function (fields, fieldName, _options) {
  return typeof fields[fieldName] === "object" && fields[fieldName] && fields[fieldName].post_title
    ? fields[fieldName].post_title
    : ""
  // if(options && fields && fieldName && typeof fields[fieldName] !== "undefined"){
  //     let value = fields[fieldName];
  //     let object = options.get(value);
  //     if(typeof object !== "undefined"){
  //         return object.label;
  //     } else {
  //         return ""
  //     }
  // }else{
  //     return '';
  // }
})
Handlebars.registerHelper("getStatusText", function (options, region, status, _options) {
  return typeof options !== "undefined" &&
    typeof region[status] !== "undefined" &&
    typeof options.findById(region[status]) !== "undefined"
    ? options.findById(region[status]).label
    : "..."
})
Handlebars.registerHelper("round", function (x, options) {
  return Math.round(x)
})
Handlebars.registerHelper("ifinr", function (v1, v2, options) {
  for (const i in v2) {
    if (v2[i].id == v1) return options.fn(this)
  }
  return options.inverse(this)
})
Handlebars.registerHelper("ifselected", function (v1, v2, options) {
  for (const i in v2) {
    if (v2[i].value == v1) return options.fn(this)
  }
  return options.inverse(this)
})
Handlebars.registerHelper("ifin", function (v1, v2, options) {
  if (v2.indexOf(v1) != -1) {
    return options.fn(this)
  }
  return options.inverse(this)
})
Handlebars.registerHelper("ifnoteq", function (v1, v2, options) {
  if (v1 != v2) {
    return options.fn(this)
  }
  return options.inverse(this)
})
Handlebars.registerHelper("ifjson", function (v1, v2, options) {
  if (typeof v1[v2] == "object") {
    return options.fn(this)
  }
  return options.inverse(this)
})
Handlebars.registerHelper("ifnotjson", function (v1, v2, options) {
  if (typeof v1[v2] != "object") {
    return options.fn(this)
  }
  return options.inverse(this)
})
Handlebars.registerHelper("getRegions", function (v1, v2, options) {
  const regions = v1[v2]
  let str = ""
  if (regions && regions.length) {
    // regions.forEach(function(region){
    str +=
      '<label class="badge bg-secondary">' +
      (regions[0].title ? regions[0].title : regions[0].id) +
      "</label> "
    // });
    if (regions.length > 1)
      str += '<span class="mapsvg-data-image-counter">+' + (regions.length - 1) + "</span>"

    return new Handlebars.SafeString(str)
  }
})
Handlebars.registerHelper("getSelectedOptions", function (v1, v2, options) {
  const regions = v1[v2]
  let str = ""
  if (regions && regions.length) {
    const label = regions[0].label === undefined ? regions[0].value : regions[0].label
    str += '<label class="badge bg-secondary">' + label + "</label> "
    if (regions.length > 1)
      str += '<span class="mapsvg-data-image-counter">+' + (regions.length - 1) + "</span>"

    return new Handlebars.SafeString(str)
  }
})
Handlebars.registerHelper("getRegionIDs", function (v1, v2, options) {
  const regions = v1[v2]
  let str = ""
  if (regions && regions.length) {
    regions.forEach(function (region) {
      str +=
        '<label class="badge bg-secondary">' +
        (region.title ? region.title : region.id) +
        "</label> "
    })
    if (regions.length > 1)
      str += '<span class="mapsvg-data-image-counter">+' + (regions.length - 1) + "</span>"

    return new Handlebars.SafeString(str)
  }
})

Handlebars.registerHelper("getThumbs", function (v1, v2, options) {
  const images = v1[v2]
  let str = ""
  if (images && images.length) {
    // images.forEach(function(img){
    //     str += '<img src="'+img.thumbnail+'" class="mapsvg-data-thumbnail"/>';
    // });
    str += '<img src="' + images[0].thumbnail + '" class="mapsvg-data-thumbnail"/>'
    if (images.length > 1)
      str += '<span class="mapsvg-data-image-counter">+' + (images.length - 1) + "</span>"
    return new Handlebars.SafeString(str)
  }
})
Handlebars.registerHelper("getMarkerImage", function (v1, v2, options) {
  const location = v1[v2]

  if (!location || !location.markerImagePath) {
    return ""
  }
  return new Handlebars.SafeString(
    '<img src="' +
      location.markerImagePath +
      '" class="mapsvg-marker-image"/> ' +
      (location.address && location.address.formatted ? location.address.formatted : ""),
  )
})
Handlebars.registerHelper("not", function (v1, v2, options) {
  if (v1 != v2) {
    return options.fn(this)
  }
  return options.inverse(this)
})
Handlebars.registerHelper("if_starts", function (v1, v2, options) {
  if (v1 && v1.indexOf(v2) == 0) {
    return options.fn(this)
  }
  return options.inverse(this)
})
Handlebars.registerHelper("if_function", function (v1, options) {
  return typeof v1 == "function" ? options.fn(this) : options.inverse(this)
})
Handlebars.registerHelper("if_number", function (v1, options) {
  return jQuery.isNumeric(v1) ? options.fn(this) : options.inverse(this)
})
Handlebars.registerHelper("if_string", function (v1, options) {
  return typeof v1 == "string" && !jQuery.isNumeric(v1) ? options.fn(this) : options.inverse(this)
})
Handlebars.registerHelper("toString", function (object) {
  return object != undefined ? MapSVG.convertToText(object) : ""
})
Handlebars.registerHelper("jsonToString", function (object) {
  return object != undefined ? JSON.stringify(object) : ""
})
Handlebars.registerHelper("log", function (object) {
  console.log(object)
})

function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
  const R = 6371 // Radius of the earth in km
  const dLat = deg2rad(lat2 - lat1) // deg2rad below
  const dLon = deg2rad(lon2 - lon1)
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
  const d = R * c // Distance in km
  return d
}
function deg2rad(deg) {
  return deg * (Math.PI / 180)
}

Handlebars.registerHelper("distanceFrom", function (location) {
  if (window.mapsvg.distanceSearch) {
    let distance = getDistanceFromLatLonInKm(
      location.geoPoint.lat,
      location.geoPoint.lng,
      window.mapsvg.distanceSearch.geoPoint.lat,
      window.mapsvg.distanceSearch.geoPoint.lng,
    )
    if (window.mapsvg.distanceSearch.units === "mi") {
      distance = distance * 0.62137
    }
    return distance.toFixed(2) + " " + window.mapsvg.distanceSearch.units
  } else {
    return ""
  }
})
Handlebars.registerHelper("distanceTo", function (location) {
  if (window.mapsvg.distanceSearch) {
    let distance = getDistanceFromLatLonInKm(
      location.geoPoint.lat,
      location.geoPoint.lng,
      window.mapsvg.distanceSearch.geoPoint.lat,
      window.mapsvg.distanceSearch.geoPoint.lng,
    )
    if (window.mapsvg.distanceSearch.units === "mi") {
      distance = distance * 0.62137
    }
    return distance.toFixed(2) + " " + window.mapsvg.distanceSearch.units
  } else {
    return ""
  }
})
Handlebars.registerHelper("stripUnderscores", function (object) {
  return object != undefined ? object.replace(/_/g, " ") : ""
})
Handlebars.registerHelper("spacesToUnderscores", function (object) {
  return object != undefined ? object.replace(/ /g, "_") : ""
})
Handlebars.registerHelper("switch", function (value, options) {
  this._switch_value_ = value
  this._switch_break_ = false
  const html = options.fn(this)
  delete this._switch_break_
  delete this._switch_value_
  return html
})

Handlebars.registerHelper("case", function () {
  const args = Array.from(arguments)
  const options = args.pop()
  const caseValues = args

  if (
    this._switch_break_ ||
    (!this._open_break_ && caseValues.indexOf(this._switch_value_) === -1)
  ) {
    return ""
  } else {
    if (options.hash.break === "true" || options.hash.break === true) {
      this._switch_break_ = true
    } else {
      this._open_break_ = true
    }
    return options.fn(this)
  }
})

Handlebars.registerHelper("default", function (options) {
  if (!this._switch_break_) {
    return options.fn(this)
  }
})

Handlebars.registerHelper("numberFormat", function (value, options) {
  // Helper parameters
  const dl = options.hash["decimalLength"] || 2
  const ts = options.hash["thousandsSep"] || " "
  const ds = options.hash["decimalSep"] || "."

  // Parse to float
  const valueFormatted = parseFloat(value)

  // The regex
  const re = "\\d(?=(\\d{3})+" + (dl > 0 ? "\\D" : "$") + ")"

  // Formats the number with the decimals
  const num = valueFormatted.toFixed(Math.max(0, ~~dl))

  // Returns the formatted number
  return (ds ? num.replace(".", ds) : num).replace(new RegExp(re, "g"), "$&" + ts)
})

Handlebars.registerHelper("shortcode", function (shortcode) {
  for (const item in this) {
    shortcode = shortcode.replace(new RegExp("{{" + item + "}}", "g"), this[item])
  }

  const url = mapsvg.routes.home + "/mapsvg_sc?mapsvg_shortcode=" + encodeURI(shortcode)

  return new Handlebars.SafeString(
    '<iframe width="100%" class="mapsvg-iframe-shortcode"  src="' + url + '"></iframe>',
  )
})

Handlebars.registerHelper("shortcode_inline", function (shortcode) {
  for (const item in this) {
    shortcode = shortcode.replace(new RegExp("{{" + item + "}}", "g"), this[item])
  }

  if (typeof MapSVG.meta.shortcodeCounter === "undefined") {
    MapSVG.meta.shortcodeCounter = 0
  }

  const id = "mapsvg-inline-shortcode-" + ++MapSVG.meta.shortcodeCounter
  const shortcodeBlock = '<span class="mapsvg-inline-shortcode" id="' + id + '"></span>'
  const url = mapsvg.routes.home + "/mapsvg_sc"
  jQuery
    .get(mapsvg.routes.api + "shortcodes/" + shortcode, function (data) {
      jQuery("#" + id).replaceWith(data)
    })
    .done(() => {
      console.log("done")
    })
    .fail(() => {
      console.log("fail")
    })
    .always(() => {
      console.log("always")
    })

  return new Handlebars.SafeString(shortcodeBlock)
})

Handlebars.registerHelper("post", function (value) {
  if (this.post) {
    return new Handlebars.SafeString(
      '<iframe width="100%" class="mapsvg-iframe-post"  src="/mapsvg_sc?mapsvg_embed_post=' +
        encodeURI(this.post.id) +
        '"></iframe>',
    )
  } else {
    return ""
  }
})

Handlebars.registerHelper("premiumFeatureAlert", function (text, object) {
  let additionalText
  // Check if the first argument is the options object
  if (text && typeof text === "object" && text.name === "premiumFeatureAlert") {
    // If so, no arguments were passed
    additionalText = undefined
  } else {
    additionalText = text
  }

  var additionalTextDom = additionalText
    ? `: <span class="mapsvg-premium-feature-text">${additionalText}</span>`
    : ""

  return new Handlebars.SafeString(
    `<div class="alert alert-info mapsvg-premium-feature">
      <i class="bi bi-star-fill"></i> Premium feature${additionalTextDom} 
      <a class="btn btn-xs mx-1 btn-outline-primary" target="_blank" href="https://mapsvg.com/pricing">Upgrade</a>
    </div>`,
  )
})

Handlebars.registerHelper("math", function (lvalue, operator, rvalue) {
  lvalue = parseFloat(lvalue)
  rvalue = parseFloat(rvalue)
  return {
    "+": lvalue + rvalue,
    "-": lvalue - rvalue,
    "*": lvalue * rvalue,
    "/": lvalue / rvalue,
    "%": lvalue % rvalue,
  }[operator]
})

Handlebars.registerHelper("for", function (from, to, incr, block) {
  let accum = ""
  for (let i = from; i < to; i += incr) accum += block.fn(i)
  return accum
})

// Define the helper function
function toSnakeCase(str) {
  return typeof str === "string" ? str.replace(/\s+/g, "_") : str
}

// Register the helper with Handlebars
Handlebars.registerHelper("toSnakeCase", function (str) {
  return toSnakeCase(str)
})
