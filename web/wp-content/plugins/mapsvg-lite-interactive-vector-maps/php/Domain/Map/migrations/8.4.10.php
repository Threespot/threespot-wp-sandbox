<?php

/**
 * @var Map $map
 */
return function (&$map) {

    if (!isset($map["options"]) || !is_array($map["options"])) {
        return;
    }

    if (isset($map["options"]['regions'])) {
        foreach ($map["options"]['regions'] as $regionId => $regionData) {
            if (is_array($regionData) && !isset($regionData['style'])) {
                $newRegionData = ['style' => []];
                if (isset($regionData['fill'])) {
                    $newRegionData['style']['fill'] = $regionData['fill'];
                    unset($regionData['fill']);
                }
                $newRegionData = array_merge($newRegionData, $regionData);
                $map["options"]['regions'][$regionId] = $newRegionData;
            }
        }
    }

    return $map;
};
