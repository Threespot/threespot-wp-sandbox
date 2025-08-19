<?php

namespace MapSVG;

/**
 */
return function () {

    $repo = RepositoryFactory::get("map");
    $maps = $repo->find();

    if ($maps["items"]) {
        foreach ($maps["items"] as $map) {
            if ($map->options && isset($map->options["zoom"]) && isset($map->options["zoom"]["limit"]) && is_array($map->options["zoom"]["limit"])) {
                if ($map->options["zoom"]["limit"][0] < 0) {
                    $map->options["zoom"]["limit"][0] = 0;
                }
                if ($map->options["zoom"]["limit"][1] > 22) {
                    $map->options["zoom"]["limit"][1] = 22;
                }
            }
            $repo->update($map);
        }
    }
};
