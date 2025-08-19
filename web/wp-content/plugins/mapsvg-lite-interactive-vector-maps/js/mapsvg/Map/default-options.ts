const defRegionTemplate =
  "<div>\n" +
  "  <p>This is the demo content of the <strong>Region %templateType%</strong>.</p>\n" +
  '  <p>How to edit it: if you are in mapsvg control panel now, click on the following link to open the template editor for this view: <a href="#" class="mapsvg-template-link" data-template="%templateTypeSnake%Region">Menu > Templates > Region %templateType%</a>.</p>\n' +
  '  <p>More information about templates: <a href="https://mapsvg.com/docs/map-editor/templates" target="_blank">mapsvg.com/docs/map-editor/templates</a></p>\n' +
  "</div>\n" +
  "<hr />\n\n" +
  "<!-- Region fields are available in this template -->\n" +
  "<h5>{{#if title}} {{title}} {{else}} {{id}} {{/if}}</h5>\n" +
  "<p>Status: {{status_text}}</p>\n\n" +
  "<!-- Show all linked Database Objects: -->\n" +
  "{{#each objects}}\n\n" +
  "  <!-- DB Object are available inside of this block -->\n\n" +
  "  <h5>{{title}}</h5>\n" +
  "  <!-- When you need to render a field as HTML, use 3 curly braces instead of 2:-->\n" +
  "  <p>{{{description}}}</p>\n" +
  "  <p><em>{{location.address.formatted}}</em></p>\n\n" +
  "  <!-- Show all images: -->\n" +
  "  {{#each images}}\n" +
  '    <!-- Image fields "thumbnail", "medium", "full" -->\n' +
  "    <!-- are available in this block                -->\n" +
  '    <img src="{{thumbnail}}" />\n' +
  "  {{/each}}\n\n" +
  "{{/each}}"

const defDBTemplate =
  "<div>\n" +
  "  <p>This is the demo content of the <strong>DB Object %templateType%</strong>.</p>\n" +
  '  <p>How to edit it: if you are in mapsvg control panel now, click on the following link to open the template editor for this view: <a href="#" class="mapsvg-template-link" data-template="%templateTypeSnake%">Menu > Templates > DB Object %templateType%</a>.</p>\n' +
  '  <p>More information about templates: <a href="https://mapsvg.com/docs/map-editor/templates" target="_blank">mapsvg.com/docs/map-editor/templates</a></p>\n' +
  "</div>\n" +
  "<hr />\n\n" +
  "<!-- DB Object fields are available in this template. -->\n" +
  "<h5>{{title}}</h5>\n" +
  "<!-- When you need to render a fields as HTML, use 3 curly braces instead of 2:-->\n" +
  "<p>{{{description}}}</p>\n" +
  "<p><em>{{location.address.formatted}}</em></p>\n\n" +
  "<!-- Show all images: -->\n" +
  "{{#each images}}\n" +
  '  <!-- Image fields "thumbnail", "medium", "full" -->\n' +
  "  <!-- are available in this block                -->\n" +
  '  <img src="{{thumbnail}}" />\n' +
  "{{/each}}\n\n" +
  "<!-- Show all linked Regions, comma-separated: -->\n" +
  "<p> Regions: \n" +
  "  {{#each regions}}\n" +
  "    <!-- Region fields are available in this block -->\n" +
  "    {{#if title}}\n" +
  "      {{title}}\n" +
  "    {{else}}\n" +
  "      {{id}}\n" +
  "    {{/if}}{{#unless @last}}, {{/unless}}\n" +
  "  {{/each}}\n" +
  "</p>"

/**
 * @ignore
 */
const DefaultOptions = {
  source: "",
  markerLastID: 0,
  regionLastID: 0,
  dataLastID: 1,
  disableAll: false,
  width: undefined,
  height: undefined,
  lockAspectRatio: false,
  padding: { top: 0, left: 0, right: 0, bottom: 0 },
  maxWidth: undefined,
  maxHeight: undefined,
  minWidth: undefined,
  minHeight: undefined,
  loadingText: "",
  //colors              : {base: "#E1F1F1", background: "#eeeeee", hover: "#548eac", selected: "#065A85", stroke: "#7eadc0"},
  colorsIgnore: false,
  colors: {
    baseDefault: "#000000",
    background: "#eeeeee",
    selected: 40,
    hover: 20,
    directory: "#fafafa",
    detailsView: "#ffffff",
    status: {},
    clusters: "",
    clustersBorders: "",
    clustersText: "",
    clustersHover: "",
    clustersHoverBorders: "",
    clustersHoverText: "",
    markers: {
      base: { opacity: 100, saturation: 100 },
      hovered: { opacity: 100, saturation: 100 },
      unhovered: { opacity: 40, saturation: 100 },
      active: { opacity: 100, saturation: 100 },
      inactive: { opacity: 40, saturation: 100 },
    },
  },
  regions: {},
  clustering: { on: false },
  viewBox: [],
  cursor: "default",
  manualRegions: false,
  onClick: null,
  mouseOver: null,
  mouseOut: null,
  menuOnClick: null,
  beforeLoad: null,
  afterLoad: null,
  zoom: {
    on: true,
    limit: [0, 22],
    delta: 2,
    buttons: { on: true, location: "right" },
    mousewheel: true,
    fingers: true,
    hideSvg: false,
    hideSvgZoomLevel: 7,
  },
  scroll: { on: true, limit: false, background: false, spacebar: false },
  responsive: true,
  tooltips: { on: false, position: "bottom-right", template: "", maxWidth: "", minWidth: 100 },
  popovers: {
    on: false,
    position: "top",
    template: "",
    centerOn: true,
    width: 300,
    maxWidth: 50,
    maxHeight: 50,
  },
  multiSelect: false,
  regionStatuses: {
    "1": { label: "Enabled", value: "1", color: "", disabled: false },
    "0": { label: "Disabled", value: "0", color: "", disabled: true },
  },
  middlewares: {
    mapLoad: `function(data, ctx){
  const { map } = ctx

  // Example: Modify map options dynamically before applying them.
  // You can conditionally change map settings based on factors like the current URL,
  // user roles, or other runtime conditions.

  // IMPORTANT: Consider checking if the map is being displayed in the WordPress admin area 
  // (by verifying map.inBackend). Typically, you don't want to run this middleware in the backend, 
  // because if you modify the map options here and then save the map, those modifications 
  // will be saved as default options in the database.

  // For example, if the map is not in the backend and the user is viewing a specific post, 
  // you might disable the zoom controls:
  // 
  // if (!map.inBackend && window.location.href.includes("/a-post-about-cats")) {
  //   data.controls.zoom = false;
  // }

  // Always return the modified or unmodified data from the middleware:
  return data
}`,
    render: `function(data, ctx){
  const { controller } = ctx;

  // This middleware function allows you to modify the data before it is sent to a specific controller.
  // Available controllers: details, popover, tooltip, directory, filters.
  // You can customize the data for different controllers, such as formatting numbers, strings, etc.

  // Example: If the controller is "details", format the "totalAmount" field from "1000000" to "1,000,000".
  // Uncomment and customize the following block to apply such transformations:
  //
  // if(controller.is("details")){
  //    return { ...data, totalAmount: data.totalAmount.toLocaleString('en-US') };  
  // }

  // Always return the data object after processing to ensure it continues through the middleware chain.
  return data;      
}`,
    request: `function(data, ctx){
  const { request, repository, map } = ctx;
  
  // This middleware function allows you to modify the request data before it is sent to the server.
  // Use this when you need to interact with a custom API that expects a different query format from MapSVG.
  
  // Example: If the request is for the "users" repository and the action is "index",
  // you can modify the request data by appending "_bar" to the "foo" property.
  //
  // if(repository.schema.objectNamePlural === "users" && request.action === "index") {
  //   data = {
  //     ...data,
  //     foo: data.foo + "_bar"
  //   };
  // }

  // Always return the data object after processing to ensure it continues through the middleware chain.
  return data;
}`,
    response: `function(data, ctx){
  const { request, response, repository, map } = ctx;
  
  // This middleware function allows you to modify the response data received from the server.
  // Use this when working with a custom API that returns data in a format different from what MapSVG expects.
  
  // Example: A response format that is compatible with MapSVG might look like this:
  // {
  //   users: {
  //     items: [
  //       {id: 1, name: "John"},
  //       {id: 2, name: "Anna"},
  //     ],
  //     hasMore: true
  //   }
  // }
  //
  // Suppose your actual API response has this structure:
  // {
  //   items: [
  //     {id: 1, name: "John"},
  //     {id: 2, name: "Anna"},
  //   ],
  //   page: 3,
  //   totalPages: 120
  // }
  //
  // The following code modifies the response for the "users" repository 
  // when fetching a list of models (the "index" action).
  // 
  // if(repository.schema.objectNamePlural === "users" && request.action === "index") {
  //   const dataParsed = typeof response.data === "string" ? JSON.parse(response.data) : data;
  //   const dataFormatted = {
  //     users: {
  //       items: dataParsed.items,
  //       hasMore: dataParsed.page < dataParsed.totalPages
  //     }
  //   };
  //   return dataFormatted;
  // } 
  
  // Always return the data object after processing to ensure it continues through the middleware chain.
  return data;    
}
`,
  },
  events: {
    afterInit: `function(event) {
  const { map } = event;

  // This event handler triggers after the map has fully initialized and all data has loaded.
  // It's a good place to execute any custom logic or manipulation that requires the map to be fully set up.

  // "map" gives you access to the MapSVG instance, allowing you to:
  // - Interact with the map's regions, markers, and other elements.
  // - Access or modify the map's settings, such as zoom level, center position, etc.
  // - Attach additional event listeners or perform post-initialization tasks.
}
`,
    beforeInit: `function(event) {
  const { map } = event;

  // This event handler triggers just before the map begins its initialization process.
  // It's a good place to set up any pre-initialization configurations or modifications.

  // "map" provides access to the MapSVG instance, allowing you to:
  // - Set or modify map settings before initialization starts (e.g., set default options).
  // - Add or alter data that will be used during initialization, such as regions or markers.
  // - Prepare any custom logic that needs to run early in the initialization phase.
}
`,
    zoom: `function(event) {
  const { map } = event;

  // This event handler triggers whenever the map is zoomed in or out.
  // It's useful for executing actions based on the zoom level of the map.

  // "map.zoomLevel" provides the current zoom level of the map.
  // You can use this to perform tasks like:
  // - Adjusting the visibility of certain elements based on zoom.
  // - Logging or displaying the current zoom level for debugging or user information.
  // - Dynamically loading additional data or layers depending on the zoom level.

  // Example usage:
  // console.log(map.zoomLevel);
}
`,
    afterLoadRegions: `function(event) {
  const { map } = event;

  // This event triggers after all regions from the regionsRepository have been fully loaded onto the map.
  // At this point:
  // - Regions are connected to their respective data objects.
  // - The directory (if present) is refreshed and populated with the updated data.

  // This is a good place to:
  // - Perform actions that depend on the regions being fully available on the map.
  // - Update or manipulate the directory based on the newly loaded data.
  // - Implement custom logic or effects that require all regions to be loaded.

  // Example:
  // console.log('Regions are fully loaded and connected to data:', map.regions);
}
`,
    afterLoadObjects: `function(event) {
  const { map } = event;

  // This event triggers after all objects from the objectsRepository have been fully loaded onto the map.
  // At this point:
  // - Objects are connected to their respective markers and regions.
  // - The directory (if present) is updated with the new data for these objects.

  // This is a good place to:
  // - Perform actions that depend on the objects being fully loaded and connected.
  // - Update or manipulate the map’s markers or regions based on the newly loaded objects.
  // - Implement custom logic or visual effects that require all objects to be loaded and displayed.

  // Example:
  // console.log('Objects are fully loaded and connected to markers and regions:', map.objects);
}
`,

    /**
     * Repository (Regions/Objects)
     */
    "beforeLoad.repository": `function(event) {
  const { map, data: { repository } } = event  

  // Perform actions or logging before the data is fetched from an API source into repository internal storage.

}`,
    "afterLoad.repository": `function(event) {
  const { map, data: { repository } } = event  

  // Perform actions or logging before the data is fetched from an API source into repository internal storage.

}`,
    /**
     * Region
     */
    "click.region": `function(event) {
  const { map, data: { region } } = event

  // Triggered when a region on the map is clicked.
  // You can access the clicked region's data using the 'region' property.
}`,
    "mouseover.region": `function(event) {
  const { map, data: { region } } = event

  // Triggered when the mouse pointer hovers over a region on the map.
  // You can access the hovered region's data using the 'region' property.
}`,
    "mouseout.region": `function(event) {
  const { map, data: { region } } = event

  // Triggered when the mouse pointer moves out of a region on the map.
  // You can access the region's data using the 'region' property.
}`,

    /**
     * Marker
     */
    "click.marker": `function(event) {
  const { map, data: { marker } } = event  
  
}`,
    "mouseover.marker": `function(event) {
  const { map, data: { marker } } = event  
  
}`,
    "mouseout.marker": `function(event) {
  const { map, data: { marker } } = event  
  
}`,
    /**
     * Directory
     */
    "click.directoryItem": `function(event) {
  const { map, data: { region, object, directoryItem } } = event      
  
}`,
    "mouseover.directoryItem": `function(event) {
  const { map, data: { region, object, directoryItem } } = event      
  
}`,
    "mouseout.directoryItem": `function(event) {
  const { map, data: { region, object, directoryItem } } = event        
  
}`,
    /**
     * Popover
     */
    "beforeRedraw.popover": `function(event) {
  const { map, data: { controller } } = event      
}`,
    "afterRedraw.popover": `function(event) {
  const { map, data: { controller } } = event      
}`,
    "beforeShow.popover": `function(event) {
  const { map, data: { controller } } = event      
}`,
    "afterShow.popover": `function(event) {
  const { map, data: { controller } } = event      
  
}`,
    "beforeClose.popover": `function(event) {
  const { map, data: { controller } } = event      
  
}`,
    "afterClose.popover": `function(event) {
  const { map, data: { controller } } = event      

}`,
    /**
     * Details
     */
    "beforeRedraw.detailsView": `function(event) {
  const { map, data: { controller } } = event      
}`,
    "afterRedraw.detailsView": `function(event) {
  const { map, data: { controller } } = event      
  
}`,
    "beforeClose.detailsView": `function(event) {
  const { map, data: { controller } } = event      
  
}`,
    "afterClose.detailsView": `function(event) {
  const { map, data: { controller } } = event      
  
}`,
    "beforeShow.detailsView": `function(event) {
  const { map, data: { controller } } = event          

}`,
    "afterShow.detailsView": `function(event) {
  const { map, data: { controller } } = event          

}`,
    /**
     * Tooltip
     */
    "beforeRedraw.tooltip": `function(event) {
  const { map, data: { controller } } = event      
}`,
    "afterRedraw.tooltip": `function(event) {
  const { map, data: { controller } } = event      
}`,
    "beforeClose.tooltip": `function(event) {
  const { map, data: { controller } } = event      
}`,
    "afterClose.tooltip": `function(event) {
  const { map, data: { controller } } = event      
}`,
    "beforeShow.tooltip": `function(event) {
  const { map, data: { controller } } = event          
}`,
    "afterShow.tooltip": `function(event) {
  const { map, data: { controller } } = event          
}`,
  },
  css:
    "#mapsvg-map-%id% .mapsvg-tooltip {\n\n}\n" +
    "#mapsvg-map-%id% .mapsvg-popover {\n\n}\n" +
    "#mapsvg-map-%id% .mapsvg-details-container {\n\n}\n" +
    "#mapsvg-map-%id% .mapsvg-directory-item {\n\n}\n" +
    "#mapsvg-map-%id% .mapsvg-region-label {\n" +
    "  /* background-color: rgba(255,255,255,.6); */\n" +
    "  font-size: 11px;\n" +
    "  padding: 3px 5px;\n" +
    "  border-radius: 4px;\n" +
    "}\n" +
    "#mapsvg-map-%id% .mapsvg-marker-label {\n" +
    "  padding: 3px 5px;\n" +
    "  /*\n" +
    "  border-radius: 4px;\n" +
    "  background-color: white;\n" +
    "  margin-top: -4px;\n" +
    "  */\n}\n" +
    "#mapsvg-map-%id% .mapsvg-filters-wrap {\n\n}\n" +
    "\n\n\n\n\n\n",
  templates: {
    list: `<div class="mapsvg-details-list">
            {{#each objects}}
          <div class="mapsvg-details-list-item">
                  <div class="mapsvg-details-list-item-image">
                    <img src="{{images.0.thumbnail}}" />
                  </div>
                  <div class="mapsvg-details-list-item-info">    	      
                    <h6>{{title}}</h6>
                    <p class="mapsvg-details-address">{{location.address.formatted}}</p>   
              <p class="mapsvg-details-category">{{category_text}}</p>                                   
                    <p>{{description}}</p>  
                <div class="mapsvg-details-link"><a href="{{link}}">{{link}}</a></div>              
                  </div>	            
              </div>
            {{/each}}
          </div>`,
    default: `<div class="mapsvg-details-flex">
        
        <div class="mapsvg-details-hero">
          <img src="{{images.0.full}}" class="mapsvg-details-hero-image"/>
        </div>
        <div class="mapsvg-details-info">
          <h4 class="mapsvg-details-header">{{#if title}} {{title}} {{else}} {{id}} {{/if}}</h4>
          {{description}}
        </div>
      </div>    
    `,
    post: `{{{post}}}`,
    title: `{{title}}`,
    imageTitle: `<div class="mapsvg-imageTitle-content">
  <div class="mapsvg-imageTitle-image">
    <img src="{{images.0.thumbnail}}" />
  </div>
  <div class="mapsvg-imageTitle-title">    	      
    {{title}}                                  
  </div>	            
</div>`,
    address: `{{#if location.address.formatted}}
        {{location.address.formatted}}
      {{else}}
        {{#if location.geoPoint.lat}}
          {{location.geoPoint.lat}}, {{location.geoPoint.lng}}
        {{/if}}
      {{/if}}`,
    popoverRegion: defRegionTemplate
      .replace(/%templateType%/g, "Popover")
      .replace(/%templateTypeSnake%/g, "popover"),
    popoverMarker: defDBTemplate
      .replace(/%templateType%/g, "Popover")
      .replace(/%templateTypeSnake%/g, "popover"),
    tooltipRegion: "{{id}} - {{title}}",
    tooltipMarker: "{{title}}",
    detailsView: defDBTemplate
      .replace(/%templateType%/g, "Details View")
      .replace(/%templateTypeSnake%/g, "detailsView"),
    detailsViewRegion: defRegionTemplate
      .replace(/%templateType%/g, "Details View")
      .replace(/%templateTypeSnake%/g, "detailsView"),
    labelMarker: `{{#if location.address.formatted}}
        {{location.address.formatted}}
      {{else}}
        {{#if location.geoPoint.lat}}
          {{location.geoPoint.lat}}, {{location.geoPoint.lng}}
        {{/if}}
      {{/if}}`,
    labelRegion: "{{title}}",
    labelLocation: "You are here!",
    directory: `{{#if categories}}
  {{#each categories}}
    {{>directoryCategoryItem}}
  {{/each}}
{{else}}  
  {{#each items}}
      {{>directoryItem}}      
  {{/each}}
  {{#unless items.length}}
    <div class="mapsvg-no-results">{{noResultsLabel}}</div>
  {{/unless}}
{{/if}}`,
    directoryItem: `<div id="mapsvg-directory-item-{{toSnakeCase id}}" class="mapsvg-directory-item" data-object-id="{{id}}">
  {{title}} 
</div>`,
    directoryCategoryItem: `<div id="mapsvg-category-item-{{value}}" class="mapsvg-category-item" data-category-value="{{value}}">
  <span class="mapsvg-category-label">{{label}}</span>
  <span class="mapsvg-category-counter">{{counter}}</span>
  <span class="mapsvg-chevron"></span>
</div>
<div class="mapsvg-category-block" data-category-id="{{value}}">
  {{#each items}}
    {{>directoryItem}}
  {{/each}}  
</div>    
    `,
  },
  choropleth: {
    on: false,
    source: "regions",
    sourceFieldSelect: {
      on: false,
      variants: [],
    },
    bubbleMode: false,
    bubbleSize: {
      min: 20,
      max: 40,
    },
    labels: { low: "low", high: "high" },
    colors: { lowRGB: null, highRGB: null, low: "#550000", high: "#ee0000", noData: "#333333" },
    min: 0,
    max: 0,
    coloring: {
      mode: "gradient",
      noData: {
        color: "#999999",
        description: "No data",
      },
      gradient: {
        colors: {
          lowRGB: null,
          highRGB: null,
          diffRGB: null,
          low: "#550000",
          high: "#ee0000",
        },
        labels: {
          low: "low",
          high: "high",
        },
        values: {
          min: null,
          max: null,
          maxAdjusted: null,
        },
      },
      palette: {
        outOfRange: {
          color: "#ececec",
          description: "Out of range",
        },
        colors: [
          {
            color: "#550000",
            valueFrom: 0,
            valueTo: 50,
            description: "",
          },
        ],
      },
      legend: {
        on: true,
        layout: "vertical",
        container: "bottom-left",
        title: "Choropleth map",
        text: "",
        description: "",
        width: "20%",
        height: "20%",
      },
    },
  },
  filters: {
    on: true,
    source: "database",
    location: "header",
    modalLocation: "map",
    width: "100%",
    hide: false,
    showButtonText: "Filters",
    clearButtonText: "Clear all",
    clearButton: false,
    searchButton: false,
    searchButtonText: "Search",
    padding: "",
  },
  menu: {
    on: false,
    hideOnMobile: true,
    location: "leftSidebar",
    locationMobile: "leftSidebar",
    search: false,
    containerId: "",
    searchPlaceholder: "Search...",
    searchFallback: false,
    source: "database",
    showFirst: "map",
    showMapOnClick: true,
    minHeight: "400",
    sortBy: "id",
    sortDirection: "desc",
    categories: {
      on: false,
      groupBy: "",
      hideEmpty: true,
      collapse: true,
      collapseOther: true,
    },
    clickActions: {
      region: "default",
      marker: "default",
      directoryItem: {
        triggerClick: true,
        showPopover: false,
        showDetails: true,
      },
    },
    detailsViewLocation: "overDirectory",
    noResultsText: "No results found",
    filterout: { field: "", cond: "=", val: "" },
  },
  database: {
    on: true,
    regionsTableName: "",
    objectsTableName: "",
    pagination: {
      on: true,
      perpage: 30,
      next: "Next",
      prev: "Prev.",
      showIn: "both",
    },
    loadOnStart: true,
    table: "",
    schemas: {
      regions: {
        objectNameSingular: "region",
        objectNamePlural: "regions",
        name: "",
        apiEndpoints: [
          { url: "regions/%name%", method: "GET", name: "index" },
          { url: "regions/%name%/[:id]", method: "GET", name: "show" },
          { url: "regions/%name%", method: "POST", name: "create" },
          { url: "regions/%name%/[:id]", method: "PUT", name: "update" },
          { url: "regions/%name%/[:id]", method: "DELETE", name: "delete" },
          { url: "regions/%name%", method: "DELETE", name: "clear" },
        ],
      },
      objects: {
        objectNameSingular: "object",
        objectNamePlural: "objects",
        name: "",
        apiEndpoints: [
          { url: "objects/%name%", method: "GET", name: "index" },
          { url: "objects/%name%/[:id]", method: "GET", name: "show" },
          { url: "objects/%name%", method: "POST", name: "create" },
          { url: "objects/%name%/[:id]", method: "PUT", name: "update" },
          { url: "objects/%name%/[:id]", method: "DELETE", name: "delete" },
          { url: "objects/%name%", method: "DELETE", name: "clear" },
        ],
      },
    },
  },
  actions: {
    map: {
      afterLoad: {
        selectRegion: true,
        selectMarker: true,
      },
    },
    region: {
      mouseover: {
        showTooltip: false,
        tooltipTemplate: "title",
      },
      click: {
        addIdToUrl: false,
        showDetails: true,
        showDetailsFor: "region",
        detailsViewTemplate: "default",
        
        filterDirectory: false,
        loadObjects: false,
        showPopover: false,
        showPopoverFor: "region",
        popoverTemplate: "default",
        goToLink: false,
        linkField: "Region.link",
      },
      touch: {
        showPopover: false,
      },
    },
    marker: {
      mouseover: {
        showTooltip: false,
        tooltipTemplate: "address",
      },
      click: {
        addIdToUrl: false,
        showDetails: true,
        detailsViewTemplate: "default",
        showPopover: false,
        popoverTemplate: "default",
        goToLink: false,
        linkField: "Object.link",
      },
      touch: {
        showPopover: false,
      },
    },
    directoryItem: {
      click: {
        showDetails: true,
        detailsViewTemplate: "default",
        showPopover: false,
        goToLink: false,
        selectRegion: true,
        fireRegionOnClick: true,
        linkField: "Object.link",
      },
      hover: {
        centerOnMarker: false,
      },
    },
  },
  detailsView: {
    location: "map",
    containerId: "",
    width: "100%",
    mobileFullscreen: true,
  },
  mobileView: {
    labelMap: "Map",
    labelList: "List",
    labelClose: "Close",
  },
  googleMaps: {
    on: false,
    apiKey: "",
    loaded: false,
    center: "auto", // or {lat: 12, lon: 13}
    type: "roadmap",
    minZoom: 1,
    style: "default",
    styleJSON: [],
    language: "en",
  },
  groups: [],
  floors: [],
  layersControl: {
    on: false,
    position: "top-left",
    label: "Show on map",
    expanded: true,
    maxHeight: "100%",
  },
  floorsControl: {
    on: false,
    position: "top-left",
    label: "Floors",
    expanded: false,
    maxHeight: "100%",
  },
  containers: {
    leftSidebar: { on: false, width: "250px" },
    rightSidebar: { on: false, width: "250px" },
    header: { on: true, height: "auto" },
    footer: { on: false, height: "auto" },
  },
  labelsMarkers: { on: false },
  labelsRegions: { on: false },
  svgFileVersion: 1,
  fitMarkers: false,
  fitMarkersOnStart: false,
  fitSingleMarkerZoom: 20,
  controls: {
    location: "right",
    zoom: true,
    zoomReset: false,
    userLocation: false,
    previousMap: false,
  },
  previousMapsIds: [],
}

export { DefaultOptions }
