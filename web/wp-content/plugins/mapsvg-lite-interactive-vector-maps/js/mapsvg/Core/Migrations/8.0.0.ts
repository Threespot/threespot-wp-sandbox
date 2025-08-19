export default function migration(options) {
  if (options.events) {
    options.events.afterLoad = `function(event) {
  const { map, data } = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events.afterLoad}).call(map, map)

}`

    options.events.beforeLoad = `function(event) {
  const { map, data } = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events.beforeLoad}).call(map, map)
}`
    if (options.events["regionsLoaded"]) {
      options.events["afterLoad.regions"] = `function(event) {
  const { map, data } = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["regionsLoaded"]}).call(map, map)
}`
    }

    options.events["afterLoad.objects"] = `function(event) {
  const { map, data } = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["databaseLoaded"]}).call(map, map)
}`

    options.events["databaseLoaded"] = null

    options.events["click.region"] = `function(event) {
  const { map, domEvent, data: { region }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["click.region"]}).call(region, domEvent, region, map)
}`

    options.events["mouseover.region"] = `function(event) {
  const { map, domEvent, data: { region }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["mouseover.region"]}).call(region, domEvent, region, map)
}`

    options.events["mouseout.region"] = `function(event) {
  const { map, domEvent, data: { region }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["mouseout.region"]}).call(region, domEvent, region, map)
}`

    options.events["click.marker"] = `function(event) {
  const { map, domEvent, data: { marker }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["click.marker"]}).call(marker, domEvent, marker, map)
}`

    options.events["mouseover.marker"] = `function(event) {
  const { map, domEvent, data: { marker }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["mouseover.marker"]}).call(marker, domEvent, marker, map)
}`

    options.events["mouseout.marker"] = `function(event) {
  const { map, domEvent, data: { marker }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["mouseout.marker"]}).call(marker, domEvent, marker, map)
}`

    options.events["click.directoryItem"] = `function(event) {
  const { map, domEvent, data: { region, object, directoryItem }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["click.directoryItem"]}).call(directoryItem, domEvent, region || object, map)
}`

    options.events["mouseover.directoryItem"] = `function(event) {
  const { map, domEvent, data: { region, object, directoryItem }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["mouseover.directoryItem"]}).call(directoryItem, domEvent, region || object, map)
}`

    options.events["mouseout.directoryItem"] = `function(event) {
  const { map, domEvent, data: { region, object, directoryItem }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["mouseout.directoryItem"]}).call(directoryItem, domEvent, region || object, map)
}`

    options.events["afterShow.popover"] = `function(event) {
  const { map, domEvent, data: { controller }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["shown.popover"]}).call(controller, map)
}`
    options.events["afterShow.detailsView"] = `function(event) {
  const { map, domEvent, data: { controller }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["shown.detailsView"]}).call(controller, map)
}`
    options.events["afterClose.popover"] = `function(event) {
  const { map, domEvent, data: { controller }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["closed.popover"]}).call(controller, map)
}`
    options.events["afterClose.detailsView"] = `function(event) {
  const { map, domEvent, data: { controller }} = event

  // This event handler was automatically modified to match the new event parameters format
  ;(${options.events["closed.detailsView"]}).call(controller, map)
}`
  }

  // new templates
  if (options.templates) {
    options.templates["directory"] = `{{#if categories}}
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
{{/if}}`

    options.templates["directoryItem"] =
      `<div id="mapsvg-directory-item-{{toSnakeCase id}}" class="mapsvg-directory-item" data-object-id="{{id}}">${options.templates["directoryItem"]}</div>`
    options.templates["categoryItem"] =
      `<div id="mapsvg-category-item-{{value}}" class="mapsvg-category-item" data-category-value="{{value}}">
  <span class="mapsvg-category-label">{{label}}</span>
  <span class="mapsvg-category-counter">{{counter}}</span>
  <span class="mapsvg-chevron"></span>
</div>
<div class="mapsvg-category-block" data-category-id="{{value}}">
  {{#each items}}
    {{>directoryItem}}
  {{/each}}  
</div>`
  }
  return options
}
