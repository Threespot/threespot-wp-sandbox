<?php

/**
 * @var Map $map
 */
return function (&$map) {

    if (!isset($map["options"]) || !is_array($map["options"])) {
        return;
    }


    if (!empty($map["options"]['events'])) {
        if (isset($map["options"]['events']['afterLoad'])) {
            $map["options"]['events']['afterLoad'] = 'function(event) {
    const { map, data } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['afterLoad'] . ').call(map, map);
}';
        }


        $map["options"]['events']['beforeLoad'] = 'function(event) {
    const { map, data } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['beforeLoad'] . ').call(map, map);
}';

        if (!empty($map["options"]['events']['regionsLoaded'])) {
            $map["options"]['events']['afterLoad.regions'] = 'function(event) {
    const { map, data } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['regionsLoaded'] . ').call(map, map);
}';
        }

        if (isset($map["options"]['events']['databaseLoaded'])) {
            $map["options"]['events']['afterLoad.objects'] = 'function(event) {
    const { map, data } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['databaseLoaded'] . ').call(map, map);
}';

            unset($map["options"]['events']['databaseLoaded']);
        }


        if (isset($map["options"]['events']['click.region'])) {
            $map["options"]['events']['click.region'] = 'function(event) {
    const { map, domEvent, data: { region } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['click.region'] . ').call(region, domEvent, region, map);
}';
        }


        $map["options"]['events']['mouseover.region'] = 'function(event) {
    const { map, domEvent, data: { region } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['mouseover.region'] . ').call(region, domEvent, region, map);
}';

        $map["options"]['events']['mouseout.region'] = 'function(event) {
    const { map, domEvent, data: { region } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['mouseout.region'] . ').call(region, domEvent, region, map);
}';

        $map["options"]['events']['click.marker'] = 'function(event) {
    const { map, domEvent, data: { marker } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['click.marker'] . ').call(marker, domEvent, marker, map);
}';

        $map["options"]['events']['mouseover.marker'] = 'function(event) {
    const { map, domEvent, data: { marker } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['mouseover.marker'] . ').call(marker, domEvent, marker, map);
}';

        $map["options"]['events']['mouseout.marker'] = 'function(event) {
    const { map, domEvent, data: { marker } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['mouseout.marker'] . ').call(marker, domEvent, marker, map);
}';

        $map["options"]['events']['click.directoryItem'] = 'function(event) {
    const { map, domEvent, data: { region, object, directoryItem } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['click.directoryItem'] . ').call(directoryItem, domEvent, region || object, map);
}';

        $map["options"]['events']['mouseover.directoryItem'] = 'function(event) {
    const { map, domEvent, data: { region, object, directoryItem } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['mouseover.directoryItem'] . ').call(directoryItem, domEvent, region || object, map);
}';

        $map["options"]['events']['mouseout.directoryItem'] = 'function(event) {
    const { map, domEvent, data: { region, object, directoryItem } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['mouseout.directoryItem'] . ').call(directoryItem, domEvent, region || object, map);
}';

        if (isset($map["options"]['events']['shown.popover'])) {
            $map["options"]['events']['afterShow.popover'] = 'function(event) {
    const { map, domEvent, data: { controller } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['shown.popover'] . ').call(controller, map);
}';
            unset($map["options"]['events']['shown.popover']);
        }


        if (isset($map["options"]['events']['shown.detailsView'])) {
            $map["options"]['events']['afterShow.detailsView'] = 'function(event) {
    const { map, domEvent, data: { controller } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['shown.detailsView'] . ').call(controller, map);
}';
            unset($map["options"]['events']['shown.detailsView']);
        }


        if (isset($map["options"]['events']['closed.popover'])) {
            $map["options"]['events']['afterClose.popover'] = 'function(event) {
    const { map, domEvent, data: { controller } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['closed.popover'] . ').call(controller, map);
}';
            unset($map["options"]['events']['closed.popover']);
        }


        if (isset($map["options"]['events']['closed.detailsView'])) {
            $map["options"]['events']['afterClose.detailsView'] = 'function(event) {
    const { map, domEvent, data: { controller } } = event;

    // This event handler was automatically modified to match the new event parameters format
    (' . $map["options"]['events']['closed.detailsView'] . ').call(controller, map);
}';
            unset($map["options"]['events']['closed.detailsView']);
        }
    }

    if (!empty($map["options"]['templates'])) {
        $map["options"]['templates']['directory'] = '{{#if categories}}
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
    {{/if}}';

        $map["options"]['templates']['directoryItem'] =
            '<div id="mapsvg-directory-item-{{toSnakeCase id}}" class="mapsvg-directory-item" data-object-id="{{id}}">' . $map["options"]['templates']['directoryItem'] . '</div>';

        $map["options"]['templates']['categoryItem'] =
            '<div id="mapsvg-category-item-{{value}}" class="mapsvg-category-item" data-category-value="{{value}}">
      <span class="mapsvg-category-label">{{label}}</span>
      <span class="mapsvg-category-counter">{{counter}}</span>
      <span class="mapsvg-chevron"></span>
    </div>
    <div class="mapsvg-category-block" data-category-id="{{value}}">
      {{#each items}}
        {{>directoryItem}}
      {{/each}}  
    </div>';
    }

    return $map;
};
