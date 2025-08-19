/**
 * MapSvg Builder javaScript
 * Version: 2.0.0
 * Author: Roman S. Stepanov
 * http://codecanyon.net/user/RomanCode/portfolio
 */

class ResizeSensor {
  constructor(element, callback) {
    var _this = this
    _this.element = element
    _this.callback = callback
    var style = getComputedStyle(element)
    var zIndex = parseInt(style.zIndex)
    if (isNaN(zIndex)) {
      zIndex = 0
    }
    zIndex--
    _this.expand = document.createElement("div")
    _this.expand.style.position = "absolute"
    _this.expand.style.left = "0px"
    _this.expand.style.top = "0px"
    _this.expand.style.right = "0px"
    _this.expand.style.bottom = "0px"
    _this.expand.style.overflow = "hidden"
    _this.expand.style.zIndex = zIndex.toString()
    _this.expand.style.visibility = "hidden"
    var expandChild = document.createElement("div")
    expandChild.style.position = "absolute"
    expandChild.style.left = "0px"
    expandChild.style.top = "0px"
    expandChild.style.width = "10000000px"
    expandChild.style.height = "10000000px"
    _this.expand.appendChild(expandChild)
    _this.shrink = document.createElement("div")
    _this.shrink.style.position = "absolute"
    _this.shrink.style.left = "0px"
    _this.shrink.style.top = "0px"
    _this.shrink.style.right = "0px"
    _this.shrink.style.bottom = "0px"
    _this.shrink.style.overflow = "hidden"
    _this.shrink.style.zIndex = zIndex.toString()
    _this.shrink.style.visibility = "hidden"
    var shrinkChild = document.createElement("div")
    shrinkChild.style.position = "absolute"
    shrinkChild.style.left = "0px"
    shrinkChild.style.top = "0px"
    shrinkChild.style.width = "200%"
    shrinkChild.style.height = "200%"
    _this.shrink.appendChild(shrinkChild)
    _this.element.appendChild(_this.expand)
    _this.element.appendChild(_this.shrink)
    var size = element.getBoundingClientRect()
    _this.currentWidth = size.width
    _this.currentHeight = size.height
    _this.setScroll()
    _this.expand.addEventListener("scroll", function () {
      _this.onScroll()
    })
    _this.shrink.addEventListener("scroll", function () {
      _this.onScroll()
    })
  }
  onScroll() {
    var _this = this
    var size = _this.element.getBoundingClientRect()
    var newWidth = size.width
    var newHeight = size.height
    if (newWidth != _this.currentWidth || newHeight != _this.currentHeight) {
      _this.currentWidth = newWidth
      _this.currentHeight = newHeight
      _this.callback()
    }
    this.setScroll()
  }
  setScroll() {
    this.expand.scrollLeft = 10000000
    this.expand.scrollTop = 10000000
    this.shrink.scrollLeft = 10000000
    this.shrink.scrollTop = 10000000
  }
  destroy() {
    this.expand.remove()
    this.shrink.remove()
  }
}

function loadDeps() {
  const files = [
    // {path:'css/mapsvg-admin.css'},
    { path: "js/vendor/papaparse/papaparse.min.js" },
    {
      path: "js/vendor/popper/popper.min.js",
      children: [
        {
          path: "js/vendor/bootstrap/bootstrap.min.js",
          children: [
            { path: "js/vendor/bootstrap-colorpicker/bootstrap-colorpicker.min.js" },
            {
              path: "js/vendor/bootstrap-datepicker/bootstrap-datepicker.min.js",
              children: [{ path: "js/vendor/bootstrap-datepicker/datepicker-locales/locales.js" }],
            },
          ],
        },
      ],
    },
    { path: "js/vendor/bootstrap-colorpicker/bootstrap-colorpicker.min.css" },
    { path: "js/vendor/bootstrap-datepicker/bootstrap-datepicker.min.css" },
    { path: "js/vendor/jquery-growl/jquery.growl.js" },
    { path: "js/vendor/jquery-growl/jquery.growl.css" },
    { path: "js/vendor/select2/select2.full.min.js" },
    { path: "js/vendor/select2/select2.min.css" },
    { path: "js/vendor/ion-rangeslider/ion.rangeSlider.min.js" },
    { path: "js/vendor/ion-rangeslider/ion.rangeSlider.css" },
    { path: "js/vendor/ion-rangeslider/ion.rangeSlider.skinNice.css" },
    {
      path: "js/vendor/codemirror/codemirror.js",
      children: [
        { path: "js/vendor/codemirror/codemirror.javascript.js" },
        { path: "js/vendor/codemirror/codemirror.xml.js" },
        { path: "js/vendor/codemirror/codemirror.css.js" },
        { path: "js/vendor/codemirror/codemirror.htmlmixed.js" },
        {
          path: "js/vendor/codemirror/codemirror.simple.js",
          children: [{ path: "js/vendor/codemirror/codemirror.handlebars.js" }],
        },
        { path: "js/vendor/codemirror/codemirror.multiplex.js" },
        { path: "js/vendor/codemirror/codemirror.show-hint.js" },
        { path: "js/vendor/codemirror/codemirror.anyword-hint.js" },
        { path: "js/vendor/codemirror/codemirror.show-hint.css" },
        { path: "js/vendor/codemirror/jshint.js" },
        { path: "js/vendor/codemirror/codemirror.lint.js" },
        { path: "js/vendor/codemirror/codemirror.javascript-lint.js" },
        { path: "js/vendor/codemirror/codemirror.lint.css" },
      ],
    },
    { path: "js/vendor/codemirror/codemirror.css" },
    { path: "js/vendor/sortable/sortable.min.js" },
    { path: "js/vendor/jscrollpane/jquery.jscrollpane.min.js" },
    { path: "js/vendor/jscrollpane/jquery.jscrollpane.css" },
    { path: "js/vendor/html2canvas/html2canvas.min.js" },
    { path: "js/mapsvg-admin/core/path-data-polyfill.js" },
    {
      path: "js/mapsvg-admin/core/controller.js",
      children: [
        { path: "js/mapsvg-admin/modules/settings/settings-controller.js" },
        { path: "js/mapsvg-admin/modules/database/database-controller.js" },
        { path: "js/mapsvg-admin/modules/regions/regions-controller.js" },
      ],
    },
  ]

  return window.mapsvg.utils.files.loadFiles(files)
}

;(function ($) {
  const MapSVG = window.MapSVG
  $.fn.inputToObject = function (formattedValue) {
    var obj = {}

    function add(obj, name, value) {
      //if(!addEmpty && !value)
      //    return false;
      if (name.length == 1) {
        obj[name[0]] = value
      } else {
        if (obj[name[0]] == null) obj[name[0]] = {}
        add(obj[name[0]], name.slice(1), value)
      }
    }

    if ($(this).attr("name") && !($(this).attr("type") == "radio" && !$(this).prop("checked"))) {
      add(obj, $(this).attr("name").replace(/]/g, "").split("["), formattedValue)
    }

    return obj
  }

  var WP = true // required for proper positioning of control panel in WordPress
  var editingMap
  var editingMark

  /**
   * @typedef {Object} MapOptions
   * @property {string} regionPrefix
   * @property {string} loadingText
   */

  /**
   * @typedef {Object} Map
   * @property {number} id
   * @property {string} svgFileLastChanged
   * @property {MapOptions} options
   */

  /**
   * @typedef {Object} User
   * @property {string} name
   * @property {string} email
   * @property {boolean} isAdmin
   */

  /**
   * @typedef {Object} MarkerImage
   * @property {string} relativeUrl
   * @property {string} url
   */

  /**
   * @typedef {Object} MapsvgBackendParams
   * @property {string} page
   * @property {Map} map
   * @property {User} user
   * @property {string} gitBranch
   * @property {Array<MarkerImage>} markerImages
   * @property {Array<string>} svgFiles
   * @property {number} fullTextMinWord
   * @property {Object} options
   * @property {Object} postTypes
   * @property {boolean} userIsAdmin
   * @property {boolean} accessGranted
   */

  /**
   * @typedef {Object} MapData
   * @property {MapsvgBackendParams|undefined} options
   * @property {boolean} gleapInitialized
   */

  /**
   * @typedef {Object} MapData
   * @property {MapsvgBackendParams|undefined} options
   * @property {boolean} gleapInitialized
   */

  /**
   * @type {MapData}
   */
  let _data = {}

  _data.gleapInitialized = false

  let _this = {}
  _data.optionsMode = {
    preview: {
      responsive: true,
      disableLinks: true,
    },
    editRegions: {
      responsive: true,
      disableLinks: true,
      zoom: { on: true, limit: [-1000, 1000] },
      scroll: { on: true },
      onClick: null,
      mouseOver: null,
      mouseOut: null,
      tooltips: {
        on: true,
      },
      templates: {
        tooltipRegion: "<b>{{id}}</b>{{#if title}}: {{title}} {{/if}}",
      },
      popovers: {
        on: false,
      },
      actions: {
        region: {
          click: {
            showDetails: false,
            filterDirectory: false,
            loadObjects: false,
            showPopover: false,
            goToLink: false,
          },
        },
        marker: {
          click: {
            showDetails: false,
            showPopover: false,
            goToLink: false,
          },
        },
      },
    },
    draw: {
      responsive: true,
      disableLinks: true,
      zoom: { on: true, limit: [-25, 25] },
      scroll: { on: true, spacebar: true },
      mouseOver: null,
      mouseOut: null,
      colorsIgnore: true,
      // colors: {
      //     base: ''
      // },
      tooltips: {
        on: false,
      },
      popovers: {
        on: false,
      },
      actions: {
        region: {
          click: {
            showDetails: false,
            filterDirectory: false,
            loadObjects: false,
            showPopover: false,
            goToLink: false,
          },
        },
        marker: {
          click: {
            showDetails: false,
            showPopover: false,
            goToLink: false,
          },
        },
      },
    },
    editData: {
      responsive: true,
      disableLinks: true,
      zoom: { on: true, limit: [-1000, 1000] },
      scroll: { on: true },
      actions: {
        region: {
          click: {
            showDetails: false,
            filterDirectory: true,
            loadObjects: false,
            showPopover: false,
            goToLink: false,
          },
        },
        marker: {
          click: {
            showDetails: false,
            showPopover: false,
            goToLink: false,
          },
        },
      },
      // onClick: function(){
      //     var region = this;
      //     $('#mapsvg-tabs-menu a[href="#tab_database"]').tab('show');
      //     var filter = {};
      //     if(region.mapsvg_type == 'region'){
      //         filter.region_id = region.id;
      //     }else if(region.mapsvg_type == 'marker'){
      //         filter.id = region.databaseObject.id;
      //
      //     }
      //     _data.controllers.database.setFilters(filter);
      // },
      // mouseOver: null,
      // mouseOut: null,
      tooltips: {
        on: true,
      },
      templates: {
        tooltipRegion: "Click to show linked DB Objects",
        tooltipMarker: "DB Object id: <b>{{id}}</b> (Click to edit)",
      },
      popovers: {
        on: false,
      },
    },
    editMarkers: {
      responsive: true,
      disableLinks: true,
      zoom: { on: true, limit: [-1000, 1000] },
      scroll: { on: true },
      onClick: null,
      mouseOver: null,
      mouseOut: null,
      tooltips: {
        on: true,
      },
      templates: {
        tooltipMarker: "DB Object id: <b>{{id}}</b> (Click to edit)",
      },
      popovers: {
        on: false,
      },
      actions: {
        region: {
          click: {
            showDetails: false,
            filterDirectory: true,
            loadObjects: false,
            showPopover: false,
            goToLink: false,
          },
        },
        marker: {
          click: {
            showDetails: false,
            showPopover: false,
            goToLink: false,
          },
        },
      },
    },
  }

  const methods = {
    getData: function () {
      return _data
    },
    getMapId: function () {
      return _data.options.map_id
    },
    selectCheckbox: function () {
      const c = $(this).attr("checked") ? true : false
      $(".region_select").removeAttr("checked")
      if (c) $(this).attr("checked", "true")
    },
    setMapTitles: function (map) {
      $("#map-page-title").html(map.options.title)
      $(".map-page-shortcode").text(`[mapsvg id="${map.id}"]`)
      $("button.mapsvg-copy-shortcode").attr("data-shortcode", `[mapsvg id="${map.id}"]`)
    },
    disableAll: function () {
      const c = $(this).attr("checked") ? true : false
      if (c) $(".region_disable").attr("checked", "true")
      else $(".region_disable").removeAttr("checked")
    },
    save: function (skipMessage) {
      var form = $(this)
      $("#mapsvg-save").buttonLoading(true)

      var mapsRepo = mapsvg.useRepository("maps")

      var mode = this.getData().mode
      this.setMode("preview")

      return mapsRepo
        .update(editingMap)
        .done(function (mapInstance) {
          var msg = "Settings saved"
          _this.setMapTitles(mapInstance)
          !skipMessage && $.growl.notice({ title: "", message: msg, duration: 700 })
        })
        .always(function () {
          $("#mapsvg-save").buttonLoading(false)
          _this.setMode(mode)
        })
        .fail(function (response, xhr, abc) {
          mapsvg.utils.http.handleFailedRequest(response)
        })
    },

    mapUndo: function (e) {
      e.preventDefault()

      var table_row = $(this).closest("tr")
      var id = table_row.attr("data-id")
      //    table_row.fadeOut();

      $(this).buttonLoading(true)

      const mapsRepo = mapsvg.useRepository("maps")
      const map = { id: id, status: 1 }

      mapsRepo.update(map).done(() => {
        $(this).buttonLoading(false)
        $(this).addClass("hidden")
        $(this).closest("tr").find(".mapsvg-span-deleted").addClass("hidden")
        $(this).closest("tr").find(".mapsvg-delete").removeClass("hidden")
      })
    },

    mapDelete: function (e) {
      e.preventDefault()

      var table_row = $(this).closest("tr")
      var id = table_row.attr("data-id")

      $(this).buttonLoading(true)

      var mapsRepo = mapsvg.useRepository("maps")

      mapsRepo.delete(id).done(() => {
        $(this).buttonLoading(false)
        $(this).addClass("hidden")
        $(this).closest("tr").find(".mapsvg-span-deleted").removeClass("hidden")
        $(this).closest("tr").find(".mapsvg-undo").removeClass("hidden")
      })
    },

    mapCopy: function (e) {
      e.preventDefault()

      var mapsRepo = mapsvg.useRepository("maps")

      var table_row = $(this).closest("tr")
      var id = table_row.attr("data-id")
      var map_title = table_row.attr("data-title")
      let new_title

      if (!(new_title = prompt("Enter new map title", map_title + " - copy"))) return false

      mapsRepo.copy(id, new_title).done(function (newMap) {
        var new_row = table_row.clone()

        var map_link = "?page=mapsvg-config&map_id=" + newMap.id
        new_row.attr("data-id", newMap.id).attr("data-title", newMap.title)
        new_row.find(".mapsvg-map-title a").attr("href", map_link).html(newMap.title)
        new_row.find(".mapsvg-action-buttons a.mapsvg-button-edit").attr("href", map_link)
        new_row
          .find(".mapsvg-shortcode")
          .html(
            '[mapsvg id="' +
              newMap.id +
              '"] <button data-shortcode=\'[mapsvg id="' +
              newMap.id +
              '"]\' class="toggle-tooltip mapsvg-copy-shortcode btn btn-xs btn-default" title="Copy to clipboard"><i class="bi bi-copy"></i></button>',
          )
        new_row.prependTo(table_row.closest("tbody"))
      })
    },
    mapUpgradeV2: function (e) {
      e.preventDefault()

      var button = $(e.target)
      button.buttonLoading(true)

      var mapsV2Repo = new mapsvg.mapsV2Repository()

      var table_row = $(this).closest("tr")
      var id = table_row.attr("data-id")

      mapsV2Repo.findById(id).done(function (map) {
        var mapsRepo = mapsvg.useRepository("maps")

        eval("map.options = " + map.options + ";")

        // Convert functions to strings
        ;["afterLoad", "beforeLoad", "onClick", "mouseOver", "mouseOut"].forEach((eventName) => {
          if (map.options[eventName]) {
            map.options[eventName] = map.options[eventName].toString()
          }
        })

        map.options.title = map.options.title + " - Upgrade from V2"
        map.title = map.options.title
        map.version = "2.4.1"

        delete map.id

        mapsRepo
          .createFromV2(map)
          .done(function (newMap) {
            var new_row = table_row.clone()
            var map_link = "?page=mapsvg-config&map_id=" + newMap.id
            new_row.attr("data-id", newMap.id).attr("data-title", newMap.title)
            new_row
              .find(".mapsvg-map-title")
              .html('<a href="' + map_link + '">' + newMap.title + "</a>")
            new_row.find(".mapsvg-action-buttons a.mapsvg-button-edit").attr("href", map_link)
            new_row
              .find(".mapsvg-shortcode")
              .html(
                '[mapsvg id="' +
                  newMap.id +
                  '"] <button data-shortcode=\'[mapsvg id="' +
                  newMap.id +
                  '"]\' class="toggle-tooltip mapsvg-copy-shortcode btn btn-xs btn-default" title="Copy to clipboard"><i class="bi bi-copy"></i></button>',
              )
            new_row.find(".alert").remove()
            new_row.find(".mapsvg-upgrade-v2").remove()
            new_row.hide()
            new_row.prependTo(table_row.closest("tbody")).fadeIn()
          })
          .always(function () {
            button.buttonLoading(false)
          })
      })
    },
    mapUpdate: function (e) {
      e.preventDefault()
      var btn = $(this)
      var table_row = $(this).closest("tr")
      var map_id = table_row.length ? table_row.attr("data-id") : editingMap.id

      var update_to = $(this).data("update-to")
      jQuery.get(ajaxurl, { action: "mapsvg_get", id: map_id }, function (data) {
        var disabledRegions = []
        let options = {}
        eval("options = " + data)
        if (options.regions) {
          for (var id in options.regions) {
            if (options.regions[id].disabled) disabledRegions.push(id)
          }
        }
        $.post(
          ajaxurl,
          {
            action: "mapsvg_update",
            id: map_id,
            update_to: update_to,
            disabledRegions: disabledRegions,
            _wpnonce: mapsvg.nonce,
            disabledColor:
              options.colors && options.colors.disabled !== undefined
                ? options.colors.disabled
                : "",
          },
          function () {
            btn.fadeOut()
            if (!table_row.length) window.location.reload()
          },
        ).fail(function () {
          $.growl.error({ title: "Server Error", message: "Can't update the map" })
        })
      })
    },
    markerEditHandler: function (updateGeoCoords) {
      editingMark = this.getOptions()
      // var markerForm = $('#table-markers').find('#mapsvg-marker-'+editingMark.id);
      // $('#mapsvg-tabs-menu a[href="#tab_markers"]').tab('show');
      if (this.hbData.isGeo && updateGeoCoords) {
        // if(markerForm.length)
        //     markerForm.find('.mapsvg-marker-geocoords a').html(this.geoCoords.join(','));
        if (editingMark.object) {
          var obj = editingMap.database.getLoadedObject(editingMark.dataId)
          editingMap.database.update(obj)
        }
        // $('.nano').nanoScroller({scrollTo: markerForm});
      } else {
        // if(!markerForm.length){
        //     editingMark.isSafari = hbData.isSafari;
        // _data.controllers.markers.addMarker(editingMark);
        // _this.updateScroll();
        // $('.nano').nanoScroller({scroll: 'top'});
        // }else{
        //     $('.nano').nanoScroller({scrollTo: markerForm});
        // }
      }
    },
    regionEditHandler: function () {
      var region = this
      var row = $("#mapsvg-region-" + region.id_no_spaces)
      $('#mapsvg-tabs-menu a[href="#tab_regions"]').tab("show")
      _data.controllers.regions.controllers.list.editRegion(region, true)
    },
    dataEditHandler: function () {
      var region = this
      $('#mapsvg-tabs-menu a[href="#tab_database"]').tab("show")
      var filter = {}
      if (region instanceof window.mapsvg.region) {
        filter.region_id = region.id
      } else if (region instanceof window.mapsvg.marker) {
        filter.id = region.object.id
      }
      _data.controllers.database.controllers.list.setFilters(filter)
    }, //.
    resizeDashboard: function () {
      // var w = _data.iframeWindow.width();
      var w = $("#wpbody-content").width()
      var top = $("#wpadminbar").height()
      var left = $(window).width() - w
      var h = $(window).height() - top
      $("#mapsvg-admin").css({ width: w, height: h, left: left, top: top })
      if (
        $("#mapsvg-sizer").outerWidth() > $("#mapsvg-container").outerWidth() ||
        $("#mapsvg-sizer").outerHeight() > $("#mapsvg-container").outerHeight() ||
        ($("#mapsvg-sizer").outerHeight() < $("#mapsvg-container").outerHeight() &&
          $("#mapsvg-sizer").outerWidth() < $("#mapsvg-container").outerWidth())
      ) {
        _this.resizeSVGCanvas()
      }
      // _this.updateScroll();
    },
    resizeSVGCanvas: function () {
      if (!editingMap) {
        return
      }

      var containerWidth = $("#mapsvg-container").width()
      var containerHeight = $("#mapsvg-container").height()

      var v = editingMap && editingMap.viewBox

      var s = Math.floor($(editingMap.containers.leftSidebar).outerWidth())
      var s2 = Math.floor($(editingMap.containers.rightSidebar).outerWidth())
      var h = $(editingMap.containers.header).is(":hidden")
        ? 0
        : Math.floor($(editingMap.containers.header).outerHeight(true))
      var f = 0 //Math.floor(msvg.$footer.outerHeight(true));

      var availWidth = containerWidth - (s + s2)
      var availHeight = containerHeight - h

      var mapRatio = v.width / v.height
      var containerRatio = availWidth / availHeight

      if (mapRatio < containerRatio) {
        var newWidth = mapRatio * availHeight
        var per = Math.round((newWidth * 100) / availWidth)
        // var totalWidth = containerWidth * (per / 100) + s + s2;
        var totalWidth = newWidth + s + s2
        $("#mapsvg-sizer").css({ width: totalWidth + "px" })
      } else {
        $("#mapsvg-sizer").css({ width: "auto" })
      }
    },
    setPreviousMode: function () {
      if (_data.previousMode) _this.setMode(_data.previousMode)
    },
    setMode: function (mode, dontSwitchTab) {
      if (_data.mode === mode) return

      if (_data.mode === "draw") {
        if (
          _data.controllers.draw.changed &&
          !confirm("Changes in SVG file will be lost. Continue?")
        ) {
          jQuery("#mapsvg-map-mode-2 label").toggleClass("active", false)
          jQuery("#mapsvg-map-mode-2 input").prop("checked", false)
          jQuery("#editSvgOption").toggleClass("active", true)
          jQuery("#editSvgOption")[0].previousSibling.checked = true
          return
        } else {
          _data.controllers.draw.revert()
        }
        _this.unloadController(_data.controllers.draw)
      }

      _data.previousMode = _data.mode
      _data.mode = mode
      // save settings from previous "dirty" state
      editingMap.restoreDeltaOptions()
      // msvg.update(msvg.optionsDelta);
      // // get current all saved settings
      // msvg.optionsDelta = {};
      var currentOptions = editingMap.getOptions()
      // remember all settings which are going to be changed in mode
      // into options delta
      $.each(_data.optionsMode[_data.mode], function (key, options) {
        editingMap.optionsDelta[key] =
          currentOptions[key] !== undefined ? currentOptions[key] : null
      })

      editingMap.update(_data.optionsMode[mode])
      var _mode = mode
      $("#mapsvg-map-mode").find("label").removeClass("active").find("input").prop("checked", false)
      var btn = $("#mapsvg-map-mode").find('label[data-mode="' + _mode + '"]')
      if (btn.length) {
        btn[0].previousSibling.checked = true
        btn.addClass("active")
      }

      $("body").off("click.switchTab")

      //editingMap.events.off("zoom")

      if (mode == "editRegions") {
        editingMap.setMarkersEditMode(false)
        editingMap.setRegionsEditMode(true)
        editingMap.setDataEditMode(false)
        _this.setDrawMode(false)
        $(editingMap.containers.map).addClass("mapsvg-edit-regions")
        // !dontSwitchTab && $('#mapsvg-tabs-menu a[href="#tab_regions"]').tab('show');
        $("body").on("click.switchTab", ".mapsvg-region", function () {
          $('#mapsvg-tabs-menu a[href="#tab_regions"]').tab("show")
        })
        $(editingMap.containers.map).removeClass("mapsvg-edit-objects")
      } else if (mode == "draw") {
        editingMap.setMarkersEditMode(false)
        editingMap.setRegionsEditMode(false)
        editingMap.setDataEditMode(false)
        $(editingMap.containers.map).removeClass("mapsvg-edit-regions")
        $(editingMap.containers.map).removeClass("mapsvg-edit-objects")
        _this.setDrawMode(true)
      } else if (mode == "editMarkers") {
        editingMap.setMarkersEditMode(true)
        editingMap.setRegionsEditMode(false)
        editingMap.setDataEditMode(false)
        _this.setDrawMode(false)
        $(editingMap.containers.map).removeClass("mapsvg-edit-regions")
        $(editingMap.containers.map).removeClass("mapsvg-edit-objects")
        // !dontSwitchTab && $('#mapsvg-tabs-menu a[href="#tab_markers"]').tab('show');
      } else if (mode == "editData") {
        editingMap.setMarkersEditMode(false)
        editingMap.setRegionsEditMode(false)
        editingMap.setDataEditMode(true)
        _this.setDrawMode(false)
        $(editingMap.containers.map).removeClass("mapsvg-edit-regions")
        $(editingMap.containers.map).addClass("mapsvg-edit-objects")
        $("body").on("click.switchTab", ".mapsvg-region", function () {
          $('#mapsvg-tabs-menu a[href="#tab_database"]').tab("show")
        })
        $("body").on("click.switchTab", ".mapsvg-marker", function () {
          $('#mapsvg-tabs-menu a[href="#tab_database"]').tab("show")
          var marker = editingMap.getMarker($(this).prop("id"))
          _data.controllers.database.controllers.list.editDataObject(marker.object.id, true)
        })
      } else {
        editingMap.setMarkersEditMode(false)
        editingMap.setRegionsEditMode(false)
        editingMap.setDataEditMode(false)
        // msvg.viewBoxReset(true);
        _this.setDrawMode(false)
        _this.resizeSVGCanvas()
        $(editingMap.containers.map).removeClass("mapsvg-edit-regions")
        $(editingMap.containers.map).removeClass("mapsvg-edit-objects")
      }
      $("#mapsvg-admin").attr("data-mode", mode)
      if (_data.previousMode == "draw") {
        _this.loadController("tab_settings", "settings")
      }
    },
    setDrawMode: async function (on) {
      var _this = this
      if (on) {
        _this.toggleContainers(false)
        editingMap.hideMarkersExceptOne()
        if (!_data.controllers.draw) {
          await window.mapsvg.utils.files.loadFiles([
            { path: "js/mapsvg-admin/modules/draw/draw-controller.js" },
            { path: "js/mapsvg-admin/modules/draw/draw-region-controller.js" },
          ])
          _data.controllers.draw = new MapSVGAdminDrawController(
            "mapsvg-container",
            _this,
            editingMap,
          )
          _data.controllers.draw.viewDidAppear()
        } else {
          _data.controllers.draw.show()
          _data.controllers.draw.viewDidAppear()
        }
        editingMap.adjustStrokes()
      } else {
        if (_data.previousMode !== "draw") {
          return
        }
        _this.toggleContainers()
        editingMap.showMarkers()
        _data.controllers.draw && _data.controllers.draw.close()
      }
    },
    enableMarkersMode: function (on) {
      var mode = $("#mapsvg-map-mode").find('[data-mode="editMarkers"]')
      if (on) {
        $("#mapsvg-map-mode").find("label").addClass("disabled")
        mode.removeClass("disabled").find("input")
      } else {
        // if(_data.mode == 'editMarkers')
        //     _this.setMode('preview');
        $("#mapsvg-map-mode").find("label").removeClass("disabled")
        mode.addClass("disabled").find("input")
      }
    },
    addHandlebarsMethods: function () {},
    getPostTypes: function () {
      return _data.options.postTypes
    },
    togglePanel: function (panelName, visibility) {
      if (!visibility) $("#mapsvg-panels").addClass("hide-" + panelName)
      else $("#mapsvg-panels").removeClass("hide-" + panelName)

      var btn = $("#mapsvg-panels-view-" + panelName)
      if (btn.hasClass("active") != visibility) {
        if (visibility) btn.addClass("active")
        else btn.removeClass("active")
        btn[0].previousSibling.checked = visibility
      }
    },
    rememberPanelsState: function () {
      _data.panelsState = {}
      _data.panelsState.left = !$("#mapsvg-panels").hasClass("hide-left")
      _data.panelsState.right = !$("#mapsvg-panels").hasClass("hide-right")
    },
    restorePanelsState: function () {
      for (var panelName in _data.panelsState) {
        _this.togglePanel(panelName, _data.panelsState[panelName])
      }
      _data.panelsState = {}
    },
    toggleContainers: function (on) {
      on = on === undefined ? !_this.containersVisible : on
      $(".mapsvg-top-container").toggleClass("mapsvg-hidden", !on)
      _this.containersVisible = on
    },

    setEventHandlersMainScreen: function () {
      var _this = this
      $('[rel="tooltip"]').on("click", function () {
        $(this).tooltip("hide")
      })

      const settingsModal = new bootstrap.Modal(document.getElementById("settingsModal"), {
        keyboard: true,
      })
      // $("body").on("click", ".form-check-label", (e) => {
      //   let input = $(e.target).parent().find("input")
      //   if (input.length) {
      //     $(input).prop("checked", !$(input).prop("checked"))
      //     $(input).trigger("change")
      //   }
      // })

      $("#mapsvg-btn-settings-modal").on("click", () => {
        settingsModal.show()
      })

      $("#mapsvg-admin").on("click", "#mapsvg-btn-phpinfo", function () {
        const server = new mapsvg.server(mapsvg.routes.api)

        server
          .get("info/php", {})
          .done(function (data) {
            const newWindow = window.open()
            newWindow.document.write(data)
            newWindow.document.close()
          })
          .fail(function (response) {
            if (response.responseText) {
              response = JSON.parse(response.responseText)
              if (response && response.data && response.data.error) {
                $.growl.error({ title: "", message: response.data.error })
              }
            }
          })
      })

      
      $("#mapsvg-alert-activate").on("click", ".close", function () {
        $("#mapsvg-alert-activate").hide()
      })

      $("#mapsvg-purchase-code-form").on("submit", function (e) {
        e.preventDetault()
      })

      $("#mapsvg-admin").on("click", "#mapsvg-btn-activate", function (e) {
        e.preventDefault()
        $(this).buttonLoading(true)
        var code = $('input[name="purchase_code"]').val()
        var server = new mapsvg.server(mapsvg.routes.api)
        server
          .put("purchasecode", { purchase_code: code })
          .done(function (data) {
            if (typeof data === "string") {
              data = JSON.parse(data)
            }
            $("#mapsvg-alert-activate").hide()
            alert(
              'MapSVG is activated. Now you can do plugin updates on the "WP Admin Menu > Plugins" page.',
            )
            $("#mapsvg-btn-activate").buttonLoading(false)
          })
          .fail(function (data) {
            if (data.responseJSON.error) {
              $.growl.error({ title: "", message: data.responseJSON.error })
            }
            $("#mapsvg-btn-activate").buttonLoading(false)
          })
          .always(function () {
            $("#mapsvg-btn-activate").buttonLoading(false)
          })
      })
      $("#mapsvg-table").on("click", ".mapsvg-copy-shortcode", function () {
        var str = $(this).data("shortcode")
        var el = document.createElement("textarea")
        el.value = str
        el.setAttribute("readonly", "")
        el.style.position = "absolute"
        el.style.left = "-9999px"
        document.body.appendChild(el)
        el.select()
        document.execCommand("copy")
        document.body.removeChild(el)
        $.growl.notice({
          title: "",
          message: "Shortcode copied to clipboard",
          duration: 700,
        })
      })
      $(".select-map-list")
        .mselect2()
        .on("select2:select", function () {
          var elem = $(this).find("option:selected")
          var mapsRepo = mapsvg.useRepository("maps")
          var options = {}
          options.source = elem.data("relative-url")

          mapsRepo.create({ options: options }).done(function (map) {
            if (map && map.id) {
              window.location.href = window.location.href + "&map_id=" + map.id
            }
          })
        })

      var files = []

      $("#svg_file_uploader").on("change", function (event) {
        $.each(event.target.files, function (index, file) {
          if (file.type.indexOf("svg") == -1) {
            alert("You can upload only SVG files")
            return false
          }
          var reader = new FileReader()
          reader.onload = function (event) {
            const object = {}
            object.filename = file.name
            object.data = event.target.result
            var data = $("<div>" + object.data + "</div>")
            var gm = data.find("#mapsvg-google-map-background")
            if (gm.length) {
              var remove = confirm("Remove Google Maps background image from SVG file?")
              if (remove) data.find("#mapsvg-google-map-background").remove()
            }
            object.data = data.html()
            object.data.replace("<!--?xml", "<?xml")
            object.data.replace('"no"?-->', '"no"?>')
            object.data.replace("mapsvg:geoviewbox", "mapsvg:geoViewBox")

            files.push(object)
            $("#svg_file_uploader_form").submit()
          }
          reader.readAsText(file)
        })
      })
      $("#svg_file_uploader_form").on("submit", function (form) {
        var btn = $(this).find(".btn")
        btn.buttonLoading(true)

        const formData = new FormData()

        _this.appendFilesToFormData(formData, files)

        var server = new mapsvg.server(mapsvg.routes.api)

        server
          .post("svgfile", formData)
          .done(function (data) {
            if (data.file.name) {
              $.growl.notice({
                delay: 7000,
                title: "",
                message:
                  "File uploaded:<br>" +
                  data.file.pathShort +
                  '<br /><br />Click on "New SVG file" and enter the file name to create a new map.',
              })
              var o = $("#mapsvg-svg-file-select").find(
                '[data-relative-url="' + data.file.relativeUrl + '"]',
              )
              if (!o.length)
                $("#mapsvg-svg-file-select").append(
                  '<option data-relative-url="' +
                    data.file.relativeUrl +
                    '">' +
                    data.file.pathShort +
                    "</option>",
                )
            }
          })
          .fail(function (response) {
            if (response.responseText) {
              response = JSON.parse(response.responseText)
              if (response && response.data && response.data.error) {
                $.growl.error({ title: "", message: response.data.error })
              }
            }
          })
          .always(function () {
            $("#svg_file_uploader").val("")
            btn.buttonLoading(false)
          })

        files = []
        form.preventDefault()
      })

      var imgfiles = []

      const mediaUploader = window.wp.media({
        title: "Choose images",
        button: {
          text: "Choose images",
        },
        multiple: false,
        library: {
          type: "image", // This restricts the selection to image files only
        },
      })

      $("#image_file_uploader").on("click", function (e) {
        e.preventDefault()
        mediaUploader.open()
      })

      mediaUploader.on("select", async () => {
        const attachments = mediaUploader.state().get("selection").toJSON()
        let image = attachments[0]

        try {
          const response = await fetch(image.sizes.full.url)
          if (!response.ok) {
            throw new Error("Network response was not ok")
          }
          const blob = await response.blob()

          var reader = new FileReader()
          reader.onload = function (event) {
            var pngBase64 = event.target.result
            var image = new Image()

            image.onload = function () {
              let object = {}
              var timestamp = new Date().valueOf()
              object.filename = image.filename + "_" + timestamp + ".svg"
              object.data =
                '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' +
                "\n" +
                "<svg " +
                'xmlns:mapsvg="http://mapsvg.com" ' +
                'xmlns:xlink="http://www.w3.org/1999/xlink" ' +
                'xmlns:dc="http://purl.org/dc/elements/1.1/" ' +
                'xmlns:cc="http://creativecommons.org/ns#" ' +
                'xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" ' +
                'xmlns:svg="http://www.w3.org/2000/svg" ' +
                'xmlns="http://www.w3.org/2000/svg" ' +
                'version="1.1" ' +
                'width="' +
                this.width +
                '" ' +
                'height="' +
                this.height +
                '"> ' +
                '<image id="mapsvg-image-background" class="mapsvg-image-background" xlink:href="' +
                pngBase64 +
                '"  x="0" y="0" height="' +
                this.height +
                '" width="' +
                this.width +
                '"></image>' +
                "</svg>"
              imgfiles.push(object)
              $("#image_file_uploader_form").submit()
            }
            image.src = pngBase64
          }
          reader.readAsDataURL(blob)
        } catch (error) {
          console.error("There has been a problem with your fetch operation:", error)
        }
      })

      $("#image_file_uploader_form").on("submit", function (form) {
        var btn = $(this).find(".btn")
        btn.buttonLoading(true)

        const formData = new FormData()

        _this.appendFilesToFormData(formData, imgfiles)

        const server = new mapsvg.server(mapsvg.routes.api)

        server
          .post("svgfile", formData)
          .done(function (data) {
            var mapsRepo = mapsvg.useRepository("maps")
            var options = {
              source: data.file.relativeUrl,
              title: "Image Map",
            }
            mapsRepo.create({ options: options }).done(function (map) {
              if (map && map.id) {
                window.location = window.location.href + "&map_id=" + map.id
              }
            })
          })
          .fail(function (response) {
            if (response.responseText) {
              response = JSON.parse(response.responseText)
              if (response && response.data && response.data.error) {
                $.growl.error({ title: "", message: response.data.error })
              }
            }
          })
          .always(function () {
            btn.buttonLoading(false)
          })

        imgfiles = []
        form.preventDefault()
      })

      $("#new-google-map").on("click", function (e) {
        // e.preventDefault();
        if (!_data.options.options.google_api_key) {
          settingsModal.show()
          return false
        }
        var mapsRepo = mapsvg.useRepository("maps")
        var options = {
          source: mapsvg.routes.maps + "geo-calibrated/empty.svg",
          title: "Google Map",
          viewBox: [-1766.3868973306835, 146.6496453220534, 23928.888888888887, 13460],
          googleMaps: {
            on: true,
            apiKey: _data.options.options.google_api_key,
            zoom: 1,
            center: { lat: 41.99585227532726, lng: 10.688006500000029 },
          },
        }
        mapsRepo.create({ options: options }).done(function (map) {
          if (map && map.id) {
            window.location = window.location.href + "&map_id=" + map.id
          }
        })
      })
      $("#download_gmap").on("click", function () {
        if (!_data.options.options.google_api_key) {
          settingsModal.show()
        } else {
          _this.showGoogleMapDownloader()
        }
      })

      $("#settingsModal").on("show.bs.modal", function () {
        var formSchema = new mapsvg.schema({
          fields: [
            {
              name: "google_api_key",
              label: "Google Maps API key",
              type: "text",
              help: "",
            },
            {
              name: "google_geocoding_api_key",
              label: "Google Geocoding API key",
              type: "text",
              help: "Google Geocoding API is used to convert address to lat/lng coordinates and back.",
            },
          ],
        })

        var form = new mapsvg.formBuilder({
          container: $("#settingsModal").find(".modal-body")[0],
          scrollable: false,
          showNames: false,
          schema: formSchema,
          // mapsvg: _this.mapsvg,
          // mediaUploader: mediaUploader,
          data: _data.options.options,
          admin: _this,
          events: {
            save: function (event) {
              const { data, formBuilder } = event.data
              jQuery.extend(_data.options.options, data)
              _this.updateOptions(data)
              settingsModal.hide()
            },
            close: function () {
              settingsModal.hide()
            },
          },
        })
        form.init()
      })

      $("#mapsvg-table-maps")
        .on("click", "a.mapsvg-delete", methods.mapDelete)
        .on("click", "a.mapsvg-undo", methods.mapUndo)
        .on("click", "a.mapsvg-copy", methods.mapCopy)
        .on("click", "a.mapsvg-upgrade-v2", methods.mapUpgradeV2)
    },
    updateOptions: function (options) {
      var server = new mapsvg.server(mapsvg.routes.api)
      server
        .post("options", { options: JSON.stringify(options) })
        .done(function (data) {
          $.growl.notice({ title: "", message: "Settings saved" })
        })
        .fail(function (data) {
          $.growl.error({ title: "", message: "Can't save settings" })
        })
    },
    setInputGrantAccessState: function () {
      const btn = $("#mapsvg-toggle-magic-link")
      const btnModal = $("#btnCustomizationModal")

      const accessGranted = _data.tokensRepo.getLoaded().length > 0

      if (accessGranted) {
        btnModal.removeClass("btn-outline-secondary")
        btnModal.addClass("btn-outline-danger")
        btnModal.html('<i class="bi bi-question-circle"></i> Support: access granted</button>')
        $("#mapsvg-revoke-access-block").show()
      } else {
        btnModal.removeClass("btn-outline-danger")
        btnModal.addClass("btn-outline-secondary")
        btnModal.html('<i class="bi bi-question-circle"></i> Support</button>')
      }
      if (!_data.options.user.isAdmin) {
        btn.attr("disabled", "disabled")
        $("#magic-link-disabled").show()
        $("#mapsvg-revoke-access-block").remove()
      }
    },
    copyInputToClipBoard: function () {
      $("#magic-link-input")[0].select()
      document.execCommand("copy")
      $.growl.notice({
        title: "",
        message: "Magic link has been updated and copied to clipboard",
        duration: 3000,
      })
    },
    // START gleap
    updateChatToken: async (apiKey) => {
      var server = new mapsvg.server(mapsvg.routes.api)
      const btn = $("#mapsvg-save-api-token")
      btn.buttonLoading(true)

      // Check if API key is correct
      if (apiKey.length) {
        const res = await fetch("https://mapsvg.com/dashboard/api/gleap/identity", {
          headers: {
            Authorization: `Bearer ${apiKey}`,
          },
        })

        if (!res.ok) {
          btn.buttonLoading(false)
          jQuery.growl.error({
            title: "",
            message: "Wrong API token",
            duration: 2000,
          })
          throw new Error("Wrong API token")
        }
      }

      server
        .post("options", { options: JSON.stringify({ apiKey }) })
        .done(async (result) => {
          _data.options.options.apiKey = apiKey
          btn.buttonLoading(false)

          if (_data.options.options.apiKey) {
            // If the token is valid, do req to mapsvg API and initialize Gleap
            await _this.identifyGleap(apiKey)
          } else {
            window.Gleap.close()
            window.Gleap.destroy()
            window.Gleap.showFeedbackButton(false)
            $("#gleap-unlogged").show()
          }
        })
        .fail(() => {
          btn.buttonLoading(false)
          window.Gleap.showFeedbackButton(false)
          $("#gleap-unlogged").show()
          $.growl.error({
            title: "",
            message: "Couldn't save the token",
            duration: 2000,
          })
        })
    },
    copyMagicLinkToClipBoard: function () {
      $("#magic-link-input")[0].select()
      document.execCommand("copy")
      $.growl.notice({
        title: "",
        message: "The link has been copied to clipboard",
        duration: 3000,
      })
    },
    // END
    setEventHandlers: function () {
      jQuery("#mapsvg-map-mode-2 input").on("change", (e) => {
        setTimeout(() => jQuery(e.target).blur(), 400)
      })

      $("body").on("click", ".toggle-tooltip", function () {
        $(this).tooltip("hide")
      })
      $("body").on("click", '[rel="tooltip"]', function () {
        $(this).tooltip("hide")
      })

      // $("body").on("click", ".form-check-label", (e) => {
      //   let input = $(e.target).parent().find("input")
      //   if (input.length) {
      //     $(input).prop("checked", !$(input).prop("checked"))
      //     $(input).trigger("change")
      //   }
      // })

      $("#mapsvg-admin").on("click", ".mapsvg-template-link", function () {
        var template = $(this).data("template")

        if (!_this.getData().controllers["templates"]) {
          $('#mapsvg-tabs-menu a[href="#tab_templates"]').tab("show")
          setTimeout(function () {
            $("#tab_templates").find("select").val(template).trigger("change")
          }, 500)
        } else {
          $('#mapsvg-tabs-menu a[href="#tab_templates"]').tab("show")
          $("#tab_templates").find("select").val(template).trigger("change")
        }
      })

      $("#mapsvg-admin").on("mousewheel", ".jspContainer", function (e) {
        e.preventDefault()
      })

      $(".mapsvg-copy-shortcode").on("click", function () {
        var str = $(this).data("shortcode")
        var el = document.createElement("textarea")
        el.value = str
        el.setAttribute("readonly", "")
        el.style.position = "absolute"
        el.style.left = "-9999px"
        document.body.appendChild(el)
        el.select()
        document.execCommand("copy")
        document.body.removeChild(el)
        $.growl.notice({
          title: "",
          message: "The shortcode has been copied to clipboard",
          duration: 3000,
        })
      })

      $(window).on("keydown.save.mapsvg", function (e) {
        if ((e.metaKey || e.ctrlKey) && e.keyCode == 83) {
          e.preventDefault()
          _data.mode != "draw" ? _this.save() : _data.controllers.draw.saveSvg()
        }
      })

      _data.view
        .on("click", "#mapsvg-save", function () {
          _this.save()
        })
        .on("change", "#mapsvg-map-mode :radio", function () {
          var mode = $("#mapsvg-map-mode :radio:checked").val()
          _this.setMode(mode)
        })
        .on("change", "#mapsvg-preview-containers-toggle :checkbox", function () {
          _this.toggleContainers($(this).is(":checked"))
        })
        .on("click", "button", function (e) {
          e.preventDefault()
        })
      _data.view.on("change", "#mapsvg-map-mode-2 :radio", function () {
        var mode = $("#mapsvg-map-mode-2 :radio:checked").val()
        _this.setMode(mode)
      })
      $("#mapsvg-view-buttons").on("change", '[type="checkbox"]', function () {
        var visible = $(this).prop("checked")
        var name = $(this).attr("name")
        _this.togglePanel(name, visible, true)
      })

      $("#mapsvg-tabs-menu").on("click", "a", function (e) {
        e.preventDefault()
        $(this).tab("show")
      })

      $("#mapsvg-tabs-menu").on("shown.bs.tab", "a", function (e) {
        $("#mapsvg-tabs-menu .menu-name").html($(this).text())
        var h = $(this).attr("href")
        _this.resizeDashboard()
        var controllerContainer = $(h)
        var containerId = h.replace("#", "")
        var controller = controllerContainer.attr("data-controller")
        _this.loadController(containerId, controller)
      })
    },
    addController: function (menuTitle, controllerName, menuPositionAfter) {
      if (!$("#" + controllerName).length) {
        var menu = $("#mapsvg-tabs-menu .dropdown-menu")
        var after = menu.find('a[href="#' + menuPositionAfter + '"]').parent()
        $('<li><a href="#' + controllerName + '">' + menuTitle + "</a></li>").insertAfter(after)
        $("#mapsvg-tabs").append(
          '<div class="tab-pane" id="' +
            controllerName +
            '" data-controller="' +
            controllerName +
            '"></div>',
        )
      }
    },
    loadController: async function (containerId, controllerName) {
      if (_data.currentController && _data.currentController == _data.controllers[controllerName])
        return

      if (!_data.controllers[controllerName]) {
        const partsOfName = controllerName.split("-")
        const mainName = partsOfName[0]
        await mapsvg.utils.files.loadFile({
          path:
            `js/mapsvg-admin/modules/${mainName}/${controllerName}-controller.js?v=` +
            mapsvg.version,
        })
        var capitalized = controllerName
          .split("-")
          .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
          .join("")
        _data.controllers[controllerName] = new window["MapSVGAdmin" + capitalized + "Controller"](
          containerId,
          _this,
          editingMap,
        )
      }
      _data.currentController && _data.currentController.viewDidDisappear()
      _data.currentController = _data.controllers[controllerName]
      _data.currentController.viewDidAppear()

      if (!$("#" + containerId).attr("data-controller")) {
        $("#" + containerId).attr("data-controller", controllerName)
      }
      if (!$("#" + containerId).hasClass("active")) {
        $('#mapsvg-tabs-menu a[href="#' + containerId + '"]').tab("show")
      }

      return _data.currentController
    },
    unloadController: function (controllerObjectOrName) {
      const name =
        typeof controllerObjectOrName != "string"
          ? controllerObjectOrName.name
          : controllerObjectOrName
      // if (typeof controllerObjectOrName != "string")
      // controllerObjectOrName = controllerObjectOrName.nameCamel();
      _data.controllers[name].destroy()
      _data.controllers[name] = null
      _data.currentController = null

      if (controllerObjectOrName == "draw") {
        _data.controllers["draw-region"] = null
      }
    },
    createControllerContainer: function (controllerName) {
      var containerId = "mapsvg-controller-" + controllerName
      if ($("#" + containerId).length === 0) {
        return $('<div id="' + containerId + '" class="full-flex" />')
      } else {
        return $("#" + containerId)
      }
    },
    goBackToController: function (fromController, toControllerName) {
      this.slideToController(fromController, toControllerName, "back")
    },
    goForwardToController: function (fromController, toControllerName) {
      this.slideToController(fromController, toControllerName, "forward")
    },
    slideToController: async function (fromController, toControllerName, direction) {
      var container
      if (!_data.controllers[toControllerName]) {
        container = this.createControllerContainer(toControllerName)
        if (direction == "back") {
          container.addClass("mapsvg-slide-back").insertBefore(fromController.container)
        } else {
          container.addClass("mapsvg-slide-forward").insertAfter(fromController.container)
        }
      } else {
        container = _data.controllers[toControllerName].container
      }
      await this.loadController(container.attr("id"), toControllerName)
      if (direction == "back") {
        fromController.slideForward()
        _data.controllers[toControllerName].slideForward()
        _data.controllers[toControllerName].history.forward = fromController
      } else {
        fromController.slideBack()
        _data.controllers[toControllerName].slideBack()
        _data.controllers[toControllerName].history.back = fromController
      }
    },
    // START gleap
    identifyGleap: async (token) => {
      const res = await fetch("https://mapsvg.com/dashboard/api/gleap/identity", {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })

      if (window.Gleap && window.Gleap.isUserIdentified()) {
        window.Gleap.clearIdentity()
      }

      if (res.ok) {
        const user = await res.json()

        if (user.userId && user.hash) {
          if (!_data.gleapInitialized) {
            _this.initGleap()
          }

          const { userId, hash } = user
          window.Gleap.identify(String(userId), {}, hash)
        } else {
          window.Gleap.close()
          window.Gleap.showFeedbackButton(false)
          $("#gleap-unlogged").show()
          jQuery.growl.error({
            title: "Error",
            message: "Incorrect response from API",
          })
        }
        return user
      } else {
        window.Gleap.close()
        window.Gleap.showFeedbackButton(false)
        $("#gleap-unlogged").show()
        jQuery.growl.error({
          title: "Error",
          message: "Incorrect API token",
        })
      }
    },
    initGleap: () => {
      try {
        !(function (Gleap, t, i) {
          if (!(Gleap = window.Gleap = window.Gleap || []).invoked) {
            for (
              window.GleapActions = [],
                Gleap.invoked = !0,
                Gleap.methods = [
                  "identify",
                  "setEnvironment",
                  "setTags",
                  "attachCustomData",
                  "setCustomData",
                  "removeCustomData",
                  "clearCustomData",
                  "registerCustomAction",
                  "trackEvent",
                  "log",
                  "preFillForm",
                  "showSurvey",
                  "sendSilentCrashReport",
                  "startFeedbackFlow",
                  "startBot",
                  "setAppBuildNumber",
                  "setAppVersionCode",
                  "setApiUrl",
                  "setFrameUrl",
                  "isOpened",
                  "open",
                  "close",
                  "on",
                  "setLanguage",
                  "setAiTools",
                  "setOfflineMode",
                  "startClassicForm",
                  "initialize",
                  "disableConsoleLogOverwrite",
                  "logEvent",
                  "hide",
                  "enableShortcuts",
                  "showFeedbackButton",
                  "destroy",
                  "getIdentity",
                  "isUserIdentified",
                  "clearIdentity",
                  "openConversations",
                  "openConversation",
                  "openHelpCenterCollection",
                  "openHelpCenterArticle",
                  "openHelpCenter",
                  "searchHelpCenter",
                  "openNewsArticle",
                  "openChecklists",
                  "startChecklist",
                  "openNews",
                  "openFeatureRequests",
                  "isLiveMode",
                ],
                Gleap.f = function (e) {
                  return function () {
                    var t = Array.prototype.slice.call(arguments)
                    window.GleapActions.push({ e: e, a: t })
                  }
                },
                t = 0;
              t < Gleap.methods.length;
              t++
            )
              Gleap[(i = Gleap.methods[t])] = Gleap.f(i)
            ;(Gleap.load = function () {
              var t = document.getElementsByTagName("head")[0],
                i = document.createElement("script")
              ;(i.type = "text/javascript"),
                (i.async = !0),
                (i.src = "https://sdk.gleap.io/latest/index.js"),
                t.appendChild(i)
            }),
              Gleap.load(),
              Gleap.initialize("i5Eg3fmVtu9sAzZu9Tgrs69lyxB5iWNC")

            const transactionTool = {
              // Name the tool. Only lowecase letters and - as well as _ are allowed.
              name: "send-money",
              // Describe the tool. This can also contain further instructions for the LLM.
              description: "Send money to a given contact.",
              // Let the LLM know what the tool is doing. This will allow Kai to update the customer accordingly.
              response:
                "The transfer got initiated but not completed yet. The user must confirm the transfer in the banking app.",
              // Set the execution type to auto or button.
              executionType: "button",
              // Specify the parameters (it's also possible to pass an empty array)
              parameters: [
                {
                  name: "amount",
                  description:
                    "The amount of money to send. Must be positive and provided by the user.",
                  type: "number",
                  required: true,
                },
                {
                  name: "contact",
                  description: "The contact to send money to.",
                  type: "string",
                  enum: ["Alice", "Bob"], // Optional
                  required: true,
                },
              ],
            }

            // Add all available tools to the array.
            const tools = [transactionTool]

            // Set the AI tools.
            Gleap.setAiTools(tools)

            window.Gleap.registerCustomAction((customAction) => {
              switch (customAction.name) {
                case "CREATE_TOKEN_FULL": {
                  _data.tokensRepo
                    .create({ accessRights: { wp: true, logs: true } })
                    .done((data) => {
                      const accessRights = data.accessRights
                      let magicLink
                      if (accessRights.wp) {
                        magicLink = window.mapsvg.routes.home + "/_mapsvg/login?key=" + data.token
                      } else if (accessRights.logs) {
                        magicLink = window.mapsvg.routes.home + "/_mapsvg/logs?key=" + data.token
                      } else {
                        $.growl.error({ title: "Error", message: "Invalid access rights." })
                        return
                      }
                      navigator.clipboard
                        .writeText(magicLink)
                        .then(() => {
                          $.growl.notice({
                            title: "",
                            message: "Magic link copied to clipboard",
                            duration: 700,
                          })
                        })
                        .catch((err) => {
                          console.error("Failed to copy text: ", err)
                        })
                    })
                  break
                }
                case "CREATE_TOKEN_LOGS": {
                  _data.tokensRepo.create({ accessRights: { logs: true } }).done((data) => {
                    const accessRights = data.accessRights
                    let magicLink
                    if (accessRights.wp) {
                      magicLink = window.mapsvg.routes.home + "/_mapsvg/login?key=" + data.token
                    } else if (accessRights.logs) {
                      magicLink = window.mapsvg.routes.home + "/_mapsvg/logs?key=" + data.token
                    } else {
                      $.growl.error({ title: "Error", message: "Invalid access rights." })
                      return
                    }
                    navigator.clipboard
                      .writeText(magicLink)
                      .then(() => {
                        $.growl.notice({
                          title: "",
                          message: "Magic link copied to clipboard",
                          duration: 700,
                        })
                      })
                      .catch((err) => {
                        console.error("Failed to copy text: ", err)
                      })
                  })
                  break
                }
              }
            })
            _data.gleapInitialized = true
          }
        })()
        window.Gleap.showFeedbackButton(true)
        $("#gleap-unlogged").hide()
      } catch (e) {
        console.error(e)
      }
    },
    loadTokens: () => {
      _data.tokensRepo.find().done((data) => {
        const tableHtml = `
            <table class="table">
              <thead>
                <tr>
                  <th>Token</th>
                  <th>Created</th>
                  <th>Last used</th>
                  <th>Access to</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                ${data
                  .map(
                    (token) => `
                  <tr>
                    <td>${token.tokenFirstFour}****</td>
                    <td>${new Date(token.createdAt).toLocaleDateString()}</td>
                    <td>${token.lastUsedAt ? new Date(token.lastUsedAt).toLocaleDateString() : "Never used"}</td>
                    <td>
                      ${Object.entries(token.accessRights)
                        .map(([key, value]) => {
                          if (value) {
                            return `<span class="badge bg-secondary">${key}</span>`
                          }
                          return ""
                        })
                        .join(" ")}
                    </td>
                    <td>
                      <button class="btn btn-xs btn-primary delete-token" data-token-id="${token.id}">Delete</button>
                    </td>
                  </tr>
                `,
                  )
                  .join("")}
              </tbody>
            </table>
          `

        $("#customizationModal #mapsvg-tokens").html(tableHtml)

        // Add event listener for delete buttons
        $("#customizationModal .delete-token").on("click", function () {
          const tokenId = $(this).data("token-id")
          _data.tokensRepo.delete(tokenId)
        })
      })
    },
    // END
    /**
     * Initialize the library with the given options.
     * @param {MapsvgBackendParams} options - The configuration options for initialization.
     */
    init: async function (options) {
      console.log("Init MapSVG backend")

      /**
       * Mapsvg backend parameters localized for use in JS.
       * @type {MapsvgBackendParams}
       */
      _data.options = options

      // Load dependencies
      try {
        await loadDeps()
        // All scripts have been loaded, you can run your code here.
      } catch (error) {
        // There was an error loading the scripts.
        console.error(error)
      }

      if (_data.options.options.chatConsentAccepted) {
        
        if (!_data.gleapInitialized) {
          _this.initGleap()
        }
        _this.initGleap()
        
      }

      // START support
      _this.initSupportModal()
      // END

      if (mapsvg.utils.env.isMac()) {
        $("body").addClass("mapsvg-os-mac")
      } else {
        $("body").addClass("mapsvg-os-other")
      }

      _data.controllers = {}
      _data.view = $("#mapsvg-admin")

      

      var onEditMapScreen = _data.options.map && _data.options.map.id ? true : false

      $("body").addClass("mapsvg-edit-screen")

      $(document).ready(function () {
        // Position control panel in WordPress
        if (WP && onEditMapScreen) {
          new ResizeSensor($("#adminmenuwrap")[0], function () {
            _this.resizeDashboard()
          })
          new ResizeSensor($("#wpwrap")[0], function () {
            _this.resizeDashboard()
          })
          new ResizeSensor($("#mapsvg")[0], function () {
            _this.resizeDashboard()
          })

          _this.resizeDashboard()
        }

        setTimeout(function () {
          _data.view.tooltip({
            selector: ".toggle-tooltip",
            trigger: "hover",
          })
        }, 1000)

        $("body").on("click", ".mapsvg-update", methods.mapUpdate)

        if (onEditMapScreen) {
          _this.addHandlebarsMethods()

          editingMap = new mapsvg.map(
            "mapsvg",
            {
              id: _data.options.map.id,
              svgFileLastChanged: _data.options.map.svgFileLastChanged,
              options: {
                loadingText: _data.options.map.options.loadingText,
                regionPrefix: _data.options.map.options.regionPrefix,
              },
            },
            {
              inBackend: true,
              prefetch: true,
            },
          )
          this.mapsvg = editingMap

          editingMap.events.on("afterInit", function (event) {
            const { map } = event
            _this.setMapTitles(map)

            if (!window.m) {
              window.m = editingMap
            }

            new ResizeSensor($(".mapsvg-header")[0], function () {
              setTimeout(function () {
                _this.resizeSVGCanvas()
              }, 1)
            })

            // TODO change this to onCLick events
            //msvg.setMarkerEditHandler(methods.markerEditHandler);
            editingMap.setRegionEditHandler(methods.regionEditHandler)

            const hbData = editingMap.getOptions(true)
            _this.hbData = hbData
            if (editingMap.presentAutoID) {
              $("#mapsvg-auto-id-warning").show()
            }

            _this.setMode("preview")

            hbData.isGeo = editingMap.mapIsGeo
            if (hbData.isGeo) {
              $("#mapsvg-admin").addClass("mapsvg-is-geo")
            }
            _data.options.markerImages = _data.options.markerImages || []
            if (!map.getOptions().defaultMarkerImage) {
              map.setDefaultMarkerImage(_data.options.markerImages[0].relativeUrl)
            }

            // Safary is laggy when there are many input fields in a form. We'll need
            // to wrap each input with <form /> tag
            hbData.isSafari =
              navigator.vendor &&
              navigator.vendor.indexOf("Apple") > -1 &&
              navigator.userAgent &&
              !navigator.userAgent.match("CriOS")

            // if (
            //   _data.options.map.options.extension &&
            //   $().mapSvg.extensions &&
            //   $().mapSvg.extensions[_data.options.map.options.extension]
            // ) {
            //   var ext = $().mapSvg.extensions[_data.options.map.options.extension]
            //   ext && ext.backend(msvg, _this)
            // }

            // Preload
            _data.controllers.settings = new MapSVGAdminSettingsController(
              "tab_settings",
              _this,
              editingMap,
            )
            _data.controllers.database = new MapSVGAdminDatabaseController(
              "database-controller",
              _this,
              editingMap,
            )
            _data.controllers.regions = new MapSVGAdminRegionsController(
              "tab_regions",
              _this,
              editingMap,
            )

            $(document).on("focus", ".select2-selection--single", function (e) {
              const select2_open = $(this).parent().parent().prev("select")
              select2_open.mselect2("open")
            })

            // Wrap input into form for Safari, otherwise form will be very slow
            if (hbData.isSafari) {
              _data.view.find('input[type="text"]').closest(".form-group").wrap("<form />")
            }

            _this.setEventHandlers()
            _this.resizeDashboard()
          })

          return _this
        } else {
          _this.setEventHandlersMainScreen()
        }

        var popoverTriggerList = [].slice.call(
document.querySelectorAll('[data-bs-toggle="popover"][data-premium="true"]'),
)
var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
return new bootstrap.Popover(popoverTriggerEl, {
// title: 'Your Popover Title',
content:
'<i class="bi bi-star-fill"></i> Premium feature <a class="btn btn-xs btn-outline-primary" target="_blank" href="https://mapsvg.com">Upgrade</a>',
html: true, // Enable HTML in popover content
trigger: "click", // or 'click' depending on how you want it triggered
})
})
        
      })
      return _this
    },

    initSupportModal: function () {
      $("#customizationModal").hide().appendTo("body")

      const customModal = new bootstrap.Modal(document.getElementById("customizationModal"), {
        keyboard: true,
      })
      $("#gleap-unlogged").appendTo("body")
      $("#gleap-unlogged").on("click", function () {
        customModal.show()
      })

      $("#mapsvg-chat-consent-form").on("submit", (e) => {
        e.preventDefault()

        if (!$('input[name="chatConsentAccepted"]').is(":checked")) {
          return $.growl.error({ title: "Error", message: "Please check the agreement checkbox." })
        }
        if (_data.gleapInitialized && _data.options.options.chatConsentAccepted) {
          return $.growl.error({ title: "Error", message: "Chat already enabled." })
        }

        var btn = $(this).find("button[type='submit']")
        btn.buttonLoading(true)

        var server = new mapsvg.server(mapsvg.routes.api)
        server
          .post("options", { options: JSON.stringify({ chatConsentAccepted: true }) })
          .done(async (result) => {
            btn.buttonLoading(false)
            if (!_data.gleapInitialized) {
              _this.initGleap()
              customModal.hide()
              window.Gleap.open()
            }
          })
          .fail(() => {
            btn.buttonLoading(false)
            window.Gleap.showFeedbackButton(false)
            $("#gleap-unlogged").show()
            $.growl.error({
              title: "",
              message: "Couldn't save the token",
              duration: 2000,
            })
          })
      })

      $("#customizationModal").on("show.bs.modal", () => {
        $("#mapsvg-new-token-message").hide()
        $("#apiTokenInput").val(_data.options.options.apiKey)
        $("#mapsvg-enabled-chat-section").toggle(!!_data.options.options.apiKey)
        $("#mapsvg-enable-chat-section").toggle(!_data.options.options.apiKey)
        $("input[name=debugMode]").prop("checked", _data.options.options.debugMode)
        $("#mapsvg-support-access-section").show()
        if (_data.options.options.apiKey) {
          const accessTokensTab = new bootstrap.Tab(document.getElementById("access-tokens-tab"))
          accessTokensTab.show()
        }
      })
      $("#mapsvg-create-token-form").on("submit", (e) => {
        e.preventDefault()
        const form = $(e.target)
        const accessWp = form.find('input[name="accessWp"]').is(":checked")
        const accessLogs = form.find('input[name="accessLogs"]').is(":checked")

        const accessRights = {}
        if (accessWp) accessRights.wp = true
        if (accessLogs) accessRights.logs = true

        if (Object.keys(accessRights).length === 0) {
          $.growl.error({ title: "Error", message: "Please choose at least one access option." })
          return
        }

        const token = {
          accessRights,
        }

        _data.tokensRepo.create(token).done((data) => {
          const accessRights = data.accessRights
          let magicLink
          if (accessRights.wp) {
            magicLink = window.mapsvg.routes.home + "/_mapsvg/login?key=" + data.token
          } else if (accessRights.logs) {
            magicLink = window.mapsvg.routes.home + "/_mapsvg/logs?key=" + data.token
          } else {
            $.growl.error({ title: "Error", message: "Invalid access rights." })
            return
          }
          $("#mapsvg-new-token-message input").val(magicLink)
          $("#mapsvg-new-token-message").show()
          $("#mapsvg-support-access-section").hide()

          // Select input content and copy to clipboard
          $("#mapsvg-new-token-message input")[0].select()
          document.execCommand("copy")
        })
      })

      

      $("body").on("click", ".mapsvg-copy-input", function () {
        const input = $(this).parent().find("input")
        if (input.length) {
          input[0].select()
          navigator.clipboard.writeText(input.val()).then(() => {
            $.growl.notice({
              title: "",
              message: "Copied to clipboard",
              duration: 1000,
            })
          })
        }
      })

      $("#btnCustomizationModal").on("click", () => {
        customModal.show()
      })
    },
    showGoogleMapDownloader: function () {
      if (!_this.googleMapsFullscreenWrapper) {
        _this.googleMapsFullscreenWrapper = $("#mapsvg-google-map-fullscreen-wrap")

        _this.googleMapsFullscreenWrapper
          .on("click", "#mapsvg-gm-download", function (e) {
            e.preventDefault()
            var link = $(this)
            var _w = window

            html2canvas(document.querySelector("#mapsvg-google-map-fullscreen"), {
              useCORS: true,
              allowTaint: true,

              ignoreElements: (node) => {
                return (
                  node.id === "mapsvg-google-map-fullscreen-controls" ||
                  node.classList.contains("gmnoprint")
                )
              },
            }).then((canvas) => {
              //document.body.appendChild(canvas)
              var server = new mapsvg.server(mapsvg.routes.api)

              var dataUrl = canvas.toDataURL("image/png")
              var bounds = _this.gm.getBounds().toJSON()
              bounds = [bounds.west, bounds.north, bounds.east, bounds.south]

              var width = canvas.width * 20
              var height = canvas.height * 20

              var file = { filename: "mapsvg.svg" }

              file.data =
                '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' +
                "\n" +
                "<svg " +
                'xmlns:mapsvg="http://mapsvg.com" ' +
                'xmlns:xlink="http://www.w3.org/1999/xlink" ' +
                'xmlns:dc="http://purl.org/dc/elements/1.1/" ' +
                'xmlns:cc="http://creativecommons.org/ns#" ' +
                'xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" ' +
                'xmlns:svg="http://www.w3.org/2000/svg" ' +
                'xmlns="http://www.w3.org/2000/svg" ' +
                'version="1.1" ' +
                'width="' +
                width +
                '" ' +
                'height="' +
                height +
                '" ' +
                'mapsvg:geoViewBox="' +
                bounds.join(" ") +
                '">' +
                '<image id="mapsvg-google-map-background" xlink:href="' +
                dataUrl +
                '"  x="0" y="0" height="' +
                height +
                '" width="' +
                width +
                '"></image>' +
                "</svg>"

              const formData = new FormData()
              var files = []
              files.push(file)

              _this.appendFilesToFormData(formData, files)

              server.post("svgfile", formData).done(function (data) {
                location.href = server.getUrl("svgfile/download")
              })
            })
          })
          .on("click", "#mapsvg-gm-close", function () {
            _this.googleMapsFullscreenWrapper.hide()
            $("body").css("overflow", "auto")
          })
      }

      _this.googleMapsFullscreenWrapper.show()
      $("body").css("overflow", "hidden")

      if (!_this.gmloaded) {
        var server = new mapsvg.server(mapsvg.routes.api)

        var locations = new Bloodhound({
          datumTokenizer: Bloodhound.tokenizers.obj.whitespace("formatted_address"),
          queryTokenizer: Bloodhound.tokenizers.whitespace,
          remote: {
            url: server.getUrl("geocoding") + "?address=%QUERY%",
            // url: 'https://maps.googleapis.com/maps/api/geocode/json?key='+window.MapSVG.options.google_api_key+'&address=%QUERY%&sensor=true',
            wildcard: "%QUERY%",
            transform: function (response) {
              if (response.error_message) {
                console.error(response.error_message)
              }
              return response.results
            },
            rateLimitWait: 500,
          },
        })
        var thContainer = _this.googleMapsFullscreenWrapper.find("#mapsvg-gm-address-search")

        var tH = thContainer.typeahead(null, {
          name: "mapsvg-addresses",
          display: "formatted_address",
          // source: locations,
          source: (query, sync, async) => {
            window.mapsvg.utils.funcs.geocode({ address: query }, async)
          },
          async: true,
          minLength: 2,
        })
        thContainer.on("typeahead:select", function (ev, item) {
          var b = item.geometry.bounds ? item.geometry.bounds : item.geometry.viewport
          var bounds = new google.maps.LatLngBounds(b.getSouthWest(), b.getNorthEast())
          _this.gm.fitBounds(bounds)
        })
        // $('#mapsvg-gm-address-search').on('focus', function(){
        //     $(this).select();
        // });

        _this.gmapikey = _data.options.options.google_api_key
        window.gm_authFailure = function () {
          alert("Google Maps API key is incorrect.")
        }
        _data.googleMapsScript = document.createElement("script")
        _data.googleMapsScript.onload = function () {
          // _data.googleMaps.loaded = true;
          // if(typeof callback == 'function')
          //     callback();
          _this.loadgm()
        }

        _data.googleMapsScript.src =
          "https://maps.googleapis.com/maps/api/js?key=" + _this.gmapikey + "&language=en" //+'&callback=initMap';

        document.head.appendChild(_data.googleMapsScript)
        _this.gmloaded = true
      } else {
        _this.loadgm()
      }

      // });
    },
    loadgm: function () {
      _this.gm = new google.maps.Map($("#mapsvg-google-map-fullscreen")[0], {
        zoom: 2,
        center: new google.maps.LatLng(-34.397, 150.644),
        mapTypeId: "roadmap",
        fullscreenControl: false,
        // keyboardShortcuts: true,
        mapTypeControl: true,
        scaleControl: true,
        scrollwheel: true,
        streetViewControl: false,
        zoomControl: true,
      })
    },
    /**
     * Append files to formData as blobs
     *
     * @param formData {FormData}
     * @param files {Array}
     * @returns FormData
     */
    appendFilesToFormData(formData, files) {
      files.forEach((file) => {
        const blob = new Blob([file.data], { type: "text/xml" })
        formData.append("file", blob, file.filename.replace(/\s/g, "_"))
      })

      return formData
    },
  }

  _this = methods
  $.fn.buttonLoading = function (status) {
    var $this = $(this)

    var _status = status !== false

    if (_status === true) {
      var loadingText = $(this).attr("data-loading-text")
      $this.data("original-text", $(this).html())
      //  $this.data("original-text", $(this).html());
      $this.text(loadingText)
      $this.attr("disabled", "disabled")
    } else {
      $this.html($this.data("original-text"))
      $this.removeAttr("disabled")
    }
    return $this
  }

  /** $.FN **/
  $.fn.mapsvgadmin = function (opts) {
    if (methods[opts]) {
      return methods[opts].apply(this, Array.prototype.slice.call(arguments, 1))
    } else if (typeof opts === "object") {
      return methods.init.apply(this, arguments)
    } else if (!opts) {
      return methods
    } else {
      $.error("Method " + methods[opts] + " does not exist on mapSvg plugin")
    }
  }
})(jQuery)

async function adminLoader() {
  if (window.mapsvgBackendParams) {
    window.mapsvgAdmin = await jQuery().mapsvgadmin("init", window.mapsvgBackendParams)
  }
}

if (window.mapsvg && window.mapsvg.initialized) {
  adminLoader()
} else {
  window.addEventListener("mapsvgClientInitialized", adminLoader)
}
