<?php

/**
 * @var Map $map
 */
return function (&$map) {

    if (!isset($map["options"]) || !is_array($map["options"])) {
        return;
    }

    $events = isset($map["options"]) && isset($map["options"]['events']) ? $map["options"]['events'] : null;
    if ($events) {
        foreach ($events as $eventName => $eventFunc) {
            // 1. Remove .getData() from everywhere
            $eventFunc = str_replace('.getData()', '', $eventFunc);
            // 2. Replace mapsvg.database
            $eventFunc = str_replace('this.database', 'this.objectsRepository', $eventFunc);
            $eventFunc = str_replace('mapsvg.database', 'mapsvg.objectsRepository', $eventFunc);
            // 3. Replace mapsvg.regionsDatabase
            $eventFunc = str_replace('this.regionsDatabase', 'this.regionsRepository', $eventFunc);
            $eventFunc = str_replace('mapsvg.regionsDatabase', 'mapsvg.regionsRepository', $eventFunc);
            $events[$eventName] = $eventFunc;
            // 4. Replace events
            $eventFunc = str_replace('mapsvg.on(', 'mapsvg.events.on(', $eventFunc);
            $eventFunc = str_replace('objectsRepository.on(', 'objectsRepository.events.on(', $eventFunc);
            $eventFunc = str_replace('regionsRepository.on(', 'regionsRepository.events.on(', $eventFunc);
            $events[$eventName] = $eventFunc;
        }
        $map["options"]['events'] = $events;
    }


    if (isset($map["options"]['menu']) && (isset($map["options"]['menu']['position']) || isset($map["options"]['menu']['customContainer']))) {
        if (isset($map["options"]['menu']['customContainer'])) {
            $map["options"]['menu']['location'] = "custom";
        } else {
            $map["options"]['menu']['position'] = ($map["options"]['menu']['position'] === "left") ? "left" : "right";
            $map["options"]['menu']['location'] = ($map["options"]['menu']['position'] === "left") ? "leftSidebar" : "rightSidebar";

            if (!isset($map["options"]['containers']) || !isset($map["options"]['containers'][$map["options"]['menu']['location']])) {
                $map["options"]['containers'] = $map["options"]['containers'] ?? [];
                $map["options"]['containers'][$map["options"]['menu']['location']] = ['on' => false, 'width' => "200px"];
            }

            $map["options"]['containers'][$map["options"]['menu']['location']]['width'] = $map["options"]['menu']['width'];

            if (isset($map["options"]['menu']['on']) && (bool) $map["options"]['menu']['on']) {
                $map["options"]['containers'][$map["options"]['menu']['location']]['on'] = true;
            }
        }

        unset($map["options"]['menu']['position']);
        unset($map["options"]['menu']['width']);
        unset($map["options"]['menu']['customContainer']);
    }

    // Fix Details View options
    if (isset($map["options"]['detailsView']) && in_array($map["options"]['detailsView']['location'], ['mapContainer', 'near', 'top'])) {
        $map["options"]['detailsView']['location'] = "map";
    }

    // Transfer zoom options to controls options
    if (!isset($map["options"]['controls'])) {
        $map["options"]['controls'] = [];
        if (isset($map["options"]['zoom']) && is_array($map["options"]['zoom'])) {
            $map["options"]['controls']['zoom'] = isset($map["options"]['zoom']['on']) && (!isset($map["options"]['zoom']['buttons']) || $map["options"]['zoom']['buttons']['location'] !== "hide");
            $map["options"]['controls']['location'] = isset($map["options"]['zoom']['buttons']) && $map["options"]['zoom']['buttons']['location'] !== "hide" ? $map["options"]['zoom']['buttons']['location'] : "right";
        } else {
            $map["options"]['controls']['zoom'] = false;
            $map["options"]['controls']['location'] = "right";
        }
    }

    // Transfer colors options
    if (isset($map["options"]['colors']) && !isset($map["options"]['colors']['markers'])) {
        $map["options"]['colors']['markers'] = [
            'base' => ['opacity' => 100, 'saturation' => 100],
            'hovered' => ['opacity' => 100, 'saturation' => 100],
            'unhovered' => ['opacity' => 100, 'saturation' => 100],
            'active' => ['opacity' => 100, 'saturation' => 100],
            'inactive' => ['opacity' => 100, 'saturation' => 100],
        ];
    }

    if (isset($map["options"]['tooltipsMode'])) {
        $map["options"]['tooltips']['mode'] = $map["options"]['tooltipsMode'];
        unset($map["options"]['tooltipsMode']);
    }

    if (isset($map["options"]['popover'])) {
        $map["options"]['popovers'] = $map["options"]['popover'];
        unset($map["options"]['popover']);
    }


    return $map;
};
