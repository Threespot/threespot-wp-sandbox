;(function ($, window, MapSVG) {
  var MapSVGAdminGooglemapsController = function (container, admin, mapsvg) {
    this.name = "googlemaps"
    MapSVGAdminController.call(this, container, admin, mapsvg)
    this.styles = {
      default: {},
      silver: [
        {
          elementType: "geometry",
          stylers: [
            {
              color: "#f5f5f5",
            },
          ],
        },
        {
          elementType: "labels.icon",
          stylers: [
            {
              visibility: "off",
            },
          ],
        },
        {
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#616161",
            },
          ],
        },
        {
          elementType: "labels.text.stroke",
          stylers: [
            {
              color: "#f5f5f5",
            },
          ],
        },
        {
          featureType: "administrative.land_parcel",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#bdbdbd",
            },
          ],
        },
        {
          featureType: "poi",
          elementType: "geometry",
          stylers: [
            {
              color: "#eeeeee",
            },
          ],
        },
        {
          featureType: "poi",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#757575",
            },
          ],
        },
        {
          featureType: "poi.park",
          elementType: "geometry",
          stylers: [
            {
              color: "#e5e5e5",
            },
          ],
        },
        {
          featureType: "poi.park",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#9e9e9e",
            },
          ],
        },
        {
          featureType: "road",
          elementType: "geometry",
          stylers: [
            {
              color: "#ffffff",
            },
          ],
        },
        {
          featureType: "road.arterial",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#757575",
            },
          ],
        },
        {
          featureType: "road.highway",
          elementType: "geometry",
          stylers: [
            {
              color: "#dadada",
            },
          ],
        },
        {
          featureType: "road.highway",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#616161",
            },
          ],
        },
        {
          featureType: "road.local",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#9e9e9e",
            },
          ],
        },
        {
          featureType: "transit.line",
          elementType: "geometry",
          stylers: [
            {
              color: "#e5e5e5",
            },
          ],
        },
        {
          featureType: "transit.station",
          elementType: "geometry",
          stylers: [
            {
              color: "#eeeeee",
            },
          ],
        },
        {
          featureType: "water",
          elementType: "geometry",
          stylers: [
            {
              color: "#c9c9c9",
            },
          ],
        },
        {
          featureType: "water",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#9e9e9e",
            },
          ],
        },
      ],
      retro: [
        {
          elementType: "geometry",
          stylers: [
            {
              color: "#ebe3cd",
            },
          ],
        },
        {
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#523735",
            },
          ],
        },
        {
          elementType: "labels.text.stroke",
          stylers: [
            {
              color: "#f5f1e6",
            },
          ],
        },
        {
          featureType: "administrative",
          elementType: "geometry.stroke",
          stylers: [
            {
              color: "#c9b2a6",
            },
          ],
        },
        {
          featureType: "administrative.land_parcel",
          elementType: "geometry.stroke",
          stylers: [
            {
              color: "#dcd2be",
            },
          ],
        },
        {
          featureType: "administrative.land_parcel",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#ae9e90",
            },
          ],
        },
        {
          featureType: "landscape.natural",
          elementType: "geometry",
          stylers: [
            {
              color: "#dfd2ae",
            },
          ],
        },
        {
          featureType: "poi",
          elementType: "geometry",
          stylers: [
            {
              color: "#dfd2ae",
            },
          ],
        },
        {
          featureType: "poi",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#93817c",
            },
          ],
        },
        {
          featureType: "poi.park",
          elementType: "geometry.fill",
          stylers: [
            {
              color: "#a5b076",
            },
          ],
        },
        {
          featureType: "poi.park",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#447530",
            },
          ],
        },
        {
          featureType: "road",
          elementType: "geometry",
          stylers: [
            {
              color: "#f5f1e6",
            },
          ],
        },
        {
          featureType: "road.arterial",
          elementType: "geometry",
          stylers: [
            {
              color: "#fhzcf8",
            },
          ],
        },
        {
          featureType: "road.highway",
          elementType: "geometry",
          stylers: [
            {
              color: "#f8c967",
            },
          ],
        },
        {
          featureType: "road.highway",
          elementType: "geometry.stroke",
          stylers: [
            {
              color: "#e9bc62",
            },
          ],
        },
        {
          featureType: "road.highway.controlled_access",
          elementType: "geometry",
          stylers: [
            {
              color: "#e98d58",
            },
          ],
        },
        {
          featureType: "road.highway.controlled_access",
          elementType: "geometry.stroke",
          stylers: [
            {
              color: "#db8555",
            },
          ],
        },
        {
          featureType: "road.local",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#806b63",
            },
          ],
        },
        {
          featureType: "transit.line",
          elementType: "geometry",
          stylers: [
            {
              color: "#hzd2ae",
            },
          ],
        },
        {
          featureType: "transit.line",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#8f7d77",
            },
          ],
        },
        {
          featureType: "transit.line",
          elementType: "labels.text.stroke",
          stylers: [
            {
              color: "#ebe3cd",
            },
          ],
        },
        {
          featureType: "transit.station",
          elementType: "geometry",
          stylers: [
            {
              color: "#hzd2ae",
            },
          ],
        },
        {
          featureType: "water",
          elementType: "geometry.fill",
          stylers: [
            {
              color: "#b9d3c2",
            },
          ],
        },
        {
          featureType: "water",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#92998d",
            },
          ],
        },
      ],
      dark: [
        {
          elementType: "geometry",
          stylers: [
            {
              color: "#212121",
            },
          ],
        },
        {
          elementType: "labels.icon",
          stylers: [
            {
              visibility: "off",
            },
          ],
        },
        {
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#757575",
            },
          ],
        },
        {
          elementType: "labels.text.stroke",
          stylers: [
            {
              color: "#212121",
            },
          ],
        },
        {
          featureType: "administrative",
          elementType: "geometry",
          stylers: [
            {
              color: "#757575",
            },
          ],
        },
        {
          featureType: "administrative.country",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#9e9e9e",
            },
          ],
        },
        {
          featureType: "administrative.land_parcel",
          stylers: [
            {
              visibility: "off",
            },
          ],
        },
        {
          featureType: "administrative.locality",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#bdbdbd",
            },
          ],
        },
        {
          featureType: "poi",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#757575",
            },
          ],
        },
        {
          featureType: "poi.park",
          elementType: "geometry",
          stylers: [
            {
              color: "#181818",
            },
          ],
        },
        {
          featureType: "poi.park",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#616161",
            },
          ],
        },
        {
          featureType: "poi.park",
          elementType: "labels.text.stroke",
          stylers: [
            {
              color: "#1b1b1b",
            },
          ],
        },
        {
          featureType: "road",
          elementType: "geometry.fill",
          stylers: [
            {
              color: "#2c2c2c",
            },
          ],
        },
        {
          featureType: "road",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#8a8a8a",
            },
          ],
        },
        {
          featureType: "road.arterial",
          elementType: "geometry",
          stylers: [
            {
              color: "#373737",
            },
          ],
        },
        {
          featureType: "road.highway",
          elementType: "geometry",
          stylers: [
            {
              color: "#3c3c3c",
            },
          ],
        },
        {
          featureType: "road.highway.controlled_access",
          elementType: "geometry",
          stylers: [
            {
              color: "#4e4e4e",
            },
          ],
        },
        {
          featureType: "road.local",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#616161",
            },
          ],
        },
        {
          featureType: "transit",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#757575",
            },
          ],
        },
        {
          featureType: "water",
          elementType: "geometry",
          stylers: [
            {
              color: "#000000",
            },
          ],
        },
        {
          featureType: "water",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#3d3d3d",
            },
          ],
        },
      ],
      night: [
        {
          elementType: "geometry",
          stylers: [
            {
              color: "#242f3e",
            },
          ],
        },
        {
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#746855",
            },
          ],
        },
        {
          elementType: "labels.text.stroke",
          stylers: [
            {
              color: "#242f3e",
            },
          ],
        },
        {
          featureType: "administrative.locality",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#d59563",
            },
          ],
        },
        {
          featureType: "poi",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#d59563",
            },
          ],
        },
        {
          featureType: "poi.park",
          elementType: "geometry",
          stylers: [
            {
              color: "#263c3f",
            },
          ],
        },
        {
          featureType: "poi.park",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#6b9a76",
            },
          ],
        },
        {
          featureType: "road",
          elementType: "geometry",
          stylers: [
            {
              color: "#38414e",
            },
          ],
        },
        {
          featureType: "road",
          elementType: "geometry.stroke",
          stylers: [
            {
              color: "#212a37",
            },
          ],
        },
        {
          featureType: "road",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#9ca5b3",
            },
          ],
        },
        {
          featureType: "road.highway",
          elementType: "geometry",
          stylers: [
            {
              color: "#746855",
            },
          ],
        },
        {
          featureType: "road.highway",
          elementType: "geometry.stroke",
          stylers: [
            {
              color: "#1f2835",
            },
          ],
        },
        {
          featureType: "road.highway",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#f3d19c",
            },
          ],
        },
        {
          featureType: "transit",
          elementType: "geometry",
          stylers: [
            {
              color: "#2f3948",
            },
          ],
        },
        {
          featureType: "transit.station",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#d59563",
            },
          ],
        },
        {
          featureType: "water",
          elementType: "geometry",
          stylers: [
            {
              color: "#17263c",
            },
          ],
        },
        {
          featureType: "water",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#515c6d",
            },
          ],
        },
        {
          featureType: "water",
          elementType: "labels.text.stroke",
          stylers: [
            {
              color: "#17263c",
            },
          ],
        },
      ],
      blue: [
        {
          elementType: "geometry",
          stylers: [
            {
              color: "#1d2c4d",
            },
          ],
        },
        {
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#8ec3b9",
            },
          ],
        },
        {
          elementType: "labels.text.stroke",
          stylers: [
            {
              color: "#1a3646",
            },
          ],
        },
        {
          featureType: "administrative.country",
          elementType: "geometry.stroke",
          stylers: [
            {
              color: "#4b6878",
            },
          ],
        },
        {
          featureType: "administrative.land_parcel",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#64779e",
            },
          ],
        },
        {
          featureType: "administrative.province",
          elementType: "geometry.stroke",
          stylers: [
            {
              color: "#4b6878",
            },
          ],
        },
        {
          featureType: "landscape.man_made",
          elementType: "geometry.stroke",
          stylers: [
            {
              color: "#334e87",
            },
          ],
        },
        {
          featureType: "landscape.natural",
          elementType: "geometry",
          stylers: [
            {
              color: "#023e58",
            },
          ],
        },
        {
          featureType: "poi",
          elementType: "geometry",
          stylers: [
            {
              color: "#283d6a",
            },
          ],
        },
        {
          featureType: "poi",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#6f9ba5",
            },
          ],
        },
        {
          featureType: "poi",
          elementType: "labels.text.stroke",
          stylers: [
            {
              color: "#1d2c4d",
            },
          ],
        },
        {
          featureType: "poi.park",
          elementType: "geometry.fill",
          stylers: [
            {
              color: "#023e58",
            },
          ],
        },
        {
          featureType: "poi.park",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#3C7680",
            },
          ],
        },
        {
          featureType: "road",
          elementType: "geometry",
          stylers: [
            {
              color: "#304a7d",
            },
          ],
        },
        {
          featureType: "road",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#98a5be",
            },
          ],
        },
        {
          featureType: "road",
          elementType: "labels.text.stroke",
          stylers: [
            {
              color: "#1d2c4d",
            },
          ],
        },
        {
          featureType: "road.highway",
          elementType: "geometry",
          stylers: [
            {
              color: "#2c6675",
            },
          ],
        },
        {
          featureType: "road.highway",
          elementType: "geometry.stroke",
          stylers: [
            {
              color: "#255763",
            },
          ],
        },
        {
          featureType: "road.highway",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#b0d5ce",
            },
          ],
        },
        {
          featureType: "road.highway",
          elementType: "labels.text.stroke",
          stylers: [
            {
              color: "#023e58",
            },
          ],
        },
        {
          featureType: "transit",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#98a5be",
            },
          ],
        },
        {
          featureType: "transit",
          elementType: "labels.text.stroke",
          stylers: [
            {
              color: "#1d2c4d",
            },
          ],
        },
        {
          featureType: "transit.line",
          elementType: "geometry.fill",
          stylers: [
            {
              color: "#283d6a",
            },
          ],
        },
        {
          featureType: "transit.station",
          elementType: "geometry",
          stylers: [
            {
              color: "#3a4762",
            },
          ],
        },
        {
          featureType: "water",
          elementType: "geometry",
          stylers: [
            {
              color: "#0e1626",
            },
          ],
        },
        {
          featureType: "water",
          elementType: "labels.text.fill",
          stylers: [
            {
              color: "#4e6d70",
            },
          ],
        },
      ],
    }
    this.languages = [
      { value: "sq", label: "Albanian" },
      { value: "ar", label: "Arabic" },
      {
        value: "eu",
        label: "Basque",
      },
      { value: "be", label: "Belarusian" },
      { value: "bg", label: "Bulgarian" },
      {
        value: "my",
        label: "Burmese",
      },
      { value: "bn", label: "Bengali" },
      { value: "ca", label: "Catalan" },
      {
        value: "zh-cn",
        label: "Chinese (simplified)",
      },
      { value: "zh-tw", label: "Chinese (traditional)" },
      {
        value: "hr",
        label: "Croatian",
      },
      { value: "cs", label: "Czech" },
      { value: "da", label: "Danish" },
      {
        value: "nl",
        label: "Dutch",
      },
      { value: "en", label: "English" },
      {
        value: "en-au",
        label: "English (australian)",
      },
      { value: "en-gb", label: "English (great Britain)" },
      {
        value: "fa",
        label: "Farsi",
      },
      { value: "fi", label: "Finnish" },
      { value: "fil", label: "Filipino" },
      {
        value: "fr",
        label: "French",
      },
      { value: "gl", label: "Galician" },
      { value: "de", label: "German" },
      {
        value: "el",
        label: "Greek",
      },
      { value: "gu", label: "Gujarati" },
      { value: "iw", label: "Hebrew" },
      {
        value: "hi",
        label: "Hindi",
      },
      { value: "hu", label: "Hungarian" },
      { value: "id", label: "Indonesian" },
      {
        value: "it",
        label: "Italian",
      },
      { value: "ja", label: "Japanese" },
      { value: "kn", label: "Kannada" },
      {
        value: "kk",
        label: "Kazakh",
      },
      { value: "ko", label: "Korean" },
      { value: "ky", label: "Kyrgyz" },
      {
        value: "lt",
        label: "Lithuanian",
      },
      { value: "lv", label: "Latvian" },
      { value: "mk", label: "Macedonian" },
      {
        value: "ml",
        label: "Malayalam",
      },
      { value: "mr", label: "Marathi" },
      { value: "no", label: "Norwegian" },
      {
        value: "pl",
        label: "Polish",
      },
      { value: "pt", label: "Portuguese" },
      {
        value: "pt-br",
        label: "Portuguese (brazil)",
      },
      { value: "pt-pt", label: "Portuguese (portugal)" },
      {
        value: "pa",
        label: "Punjabi",
      },
      { value: "ro", label: "Romanian" },
      { value: "ru", label: "Russian" },
      {
        value: "sr",
        label: "Serbian",
      },
      { value: "sk", label: "Slovak" },
      { value: "sl", label: "Slovenian" },
      {
        value: "es",
        label: "Spanish",
      },
      { value: "sv", label: "Swedish" },
      { value: "tl", label: "Tagalog" },
      {
        value: "ta",
        label: "Tamil",
      },
      { value: "te", label: "Telugu" },
      { value: "th", label: "Thai" },
      {
        value: "tr",
        label: "Turkish",
      },
      { value: "uk", label: "Ukrainian" },
      { value: "uz", label: "Uzbek" },
      {
        value: "vi",
        label: "Vietnamese",
      },
    ]
  }
  window.MapSVGAdminGooglemapsController = MapSVGAdminGooglemapsController
  MapSVG.extend(MapSVGAdminGooglemapsController, window.MapSVGAdminController)

  MapSVGAdminGooglemapsController.prototype.viewLoaded = function () {
    if (!this.mapsvg.mapIsGeo) {
      this.view.find("#mapsvg-can-use-gmap").show()
      this.view.find("form").css({
        opacity: 0.4,
        "pointer-events": "none",
      })
    }
  }
  MapSVGAdminGooglemapsController.prototype.setEventHandlers = function () {
    var _this = this

    // MapSVG.GoogleMapBadApiKey = function () {
    //   alert(
    //     'Google maps API key is incorrect. Change API key, click "Save" and RELOAD the page to reload Google maps scripts with correct API key.',
    //   )
    //   _this.view.find("#googleMapsOn").prop("checked", false).trigger("change")
    // }

    const server = new mapsvg.server(mapsvg.routes.api)
    const geocodingApiUrl = server.getUrl("geocoding") + "?address=%QUERY%"

    var locations = new Bloodhound({
      datumTokenizer: Bloodhound.tokenizers.obj.whitespace("formatted_address"),
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      remote: {
        url: geocodingApiUrl,
        wildcard: "%QUERY%",
        transform: function (response) {
          if (response.error_message) {
            alert(response.error_message)
          }
          return response.results
        },
        rateLimitWait: 500,
      },
    })
    var thContainer = _this.view.find("#mapsvg-gm-address")
    var tH = thContainer.typeahead(null, {
      name: "mapsvg-addresses",
      display: "formatted_address",
      source: (query, sync, async) => {
        MapSVG.geocode({ address: query }, async)
      },
      async: true,
      minLength: 2,
    })
    thContainer.on("typeahead:select", function (ev, item) {
      if (_this.mapsvg.googleMaps && _this.mapsvg.googleMaps.map) {
        var b = item.geometry.bounds ? item.geometry.bounds : item.geometry.viewport
        var bounds = new google.maps.LatLngBounds(b.getSouthWest(), b.getNorthEast())
        _this.mapsvg.googleMaps.map.fitBounds(bounds)
      }
    })

    this.view.on("change", 'input[name="googleMaps[style]"]', function (e) {
      if (!_this.mapsvg.options.googleMaps.on) {
        return false
      }

      const style = $(this).val()

      if (style === "custom") {
        _this.view.find("#mapsvg-gm-custom-style-extra").show()
        _this.updateScroll()
        $("#mapsvg-gm-custom-style-textarea").trigger("change")
      } else {
        _this.view.find("#mapsvg-gm-custom-style-extra").hide()
        _this.updateScroll()
        _this.mapsvg.options.googleMaps.styleJSON = _this.styles[style]
        _this.mapsvg.googleMaps.map.setOptions({ styles: _this.styles[style] })
      }
    })
    this.view.on("change paste keyup", "#mapsvg-gm-custom-style-textarea", function (e) {
      try {
        var style = JSON.parse($(this).val())
        _this.mapsvg.options.googleMaps.styleJSON = style
        _this.mapsvg.googleMaps.map.setOptions({ styles: style })
        $("#mapsvg-gm-invalid-json").hide()
      } catch (err) {
        $("#mapsvg-gm-invalid-json").show()
      }
    })

    /*
        this.view.on('click','#mapsvg-google-download',function(e){
            // _this.mapsvg.initGoogleMaps.done(function(googleMaps){
                if(!_this.googleMapsFullscreenWrapper){
                    _this.googleMapsFullscreenWrapper = $(_this.templates.download()).prependTo('body');
                    // _this.googleMapsFullscreen = $('<div id="mapsvg-google-map-fullscreen"></div>').appendTo(_this.googleMapsFullscreenWrapper);
                    // _this.googleMapsFullscreenControls = $('<div id="mapsvg-google-map-fullscreen-controls" class="well">Zoom-in to desired area and click the button:<br /> <a class="btn btn-default" id="mapsvg-gm-download">Download SVG file</a></div>').appendTo(_this.googleMapsFullscreenWrapper);
                    _this.googleMapsFullscreenWrapper.on('click','#mapsvg-gm-download', function(e){
                        e.preventDefault();
                        var link = $(this);
                        var _w = window;

                        // blank space fix
                        var transform=$(".gm-style>div:first>div").css("transform")
                        var comp=transform.split(",") //split up the transform matrix
                        var mapleft=parseFloat(comp[4]) //get left value
                        var maptop=parseFloat(comp[5])  //get top value
                        $(".gm-style>div:first>div").css({ //get the map container. not sure if stable
                            "transform":"none",
                            "left":mapleft,
                            "top":maptop
                        });

                        html2canvas($('#mapsvg-google-map-fullscreen'), {
                            useCORS: true,
                            onrendered: function(canvas) {
                                var dataUrl = canvas.toDataURL("image/png");
                                var bounds = _this.gm.getBounds().toJSON();
                                bounds = [bounds.west, bounds.north, bounds.east, bounds.south];

                                $.post(ajaxurl, {action: 'mapsvg_download_svg', map_id: _this.mapsvg.id, png: dataUrl, bounds: bounds}).done(function(data){
                                    location.href=(data);
                                });
                                // blank space fix back
                                $(".gm-style>div:first>div").css({
                                    left:0,
                                    top:0,
                                    "transform":transform
                                })
                            }
                        });
                    }).on('click','#mapsvg-gm-close', function(){
                        _this.googleMapsFullscreenWrapper.hide();
                    });
                }else{
                    _this.googleMapsFullscreenWrapper.show();
                }

                _this.gm = new google.maps.Map($('#mapsvg-google-map-fullscreen')[0], {
                    zoom: 2,
                    center: new google.maps.LatLng(-34.397, 150.644),
                    mapTypeId: _this.mapsvg.options.googleMaps.type,
                    fullscreenControl: false,
                    // keyboardShortcuts: false,
                    // mapTypeControl: false,
                    // scaleControl: false,
                    // scrollwheel: false,
                    streetViewControl: false
                    // zoomControl: false
                });

            // });
        });
         */
  }

  MapSVGAdminGooglemapsController.prototype.getTemplateData = function () {
    const data = this.mapsvg.getOptions(true, null)
    data.languages = this.languages
    data.language = data.googleMaps.language
    return data
  }
})(jQuery, window, window.MapSVG)
