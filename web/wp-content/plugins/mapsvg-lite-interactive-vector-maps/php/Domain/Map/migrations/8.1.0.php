<?php

/**
 * @var Map $map
 */
return function (&$map) {

    if (!isset($map["options"]) || !is_array($map["options"])) {
        return;
    }

    if (isset($map["options"]['events'])) {
        $map["options"]['events']['afterInit'] = $map["options"]['events']['afterLoad'] ?? "";
        $map["options"]['events']['beforeInit'] = $map["options"]['events']['beforeLoad'] ?? "";
        unset($map["options"]['events']['afterLoad']);
        unset($map["options"]['events']['beforeLoad']);

        if (isset($map["options"]['events']['afterLoad.regions'])) {
            $map["options"]['events']['afterLoadRegions'] = $map["options"]['events']['afterLoad.regions'];
            unset($map["options"]['events']['afterLoad.regions']);
        }

        if (isset($map["options"]['events']['afterLoad.objects'])) {
            $map["options"]['events']['afterLoadObjects'] = $map["options"]['events']['afterLoad.objects'];
            unset($map["options"]['events']['afterLoad.objects']);
        }
    }

    if (!isset($map["options"]['database']['schemas'])) {
        $regionsTableName = $map["options"]['database']['regionsTableName'] ?? "regions_" . $map["id"];
        $objectsTableName = $map["options"]['database']['objectsTableName'] ?? "objects_" . $map["id"];

        $map["options"]['database']['schemas'] = [
            'regions' => [
                'objectNameSingular' => 'region',
                'objectNamePlural' => 'regions',
                'name' => $regionsTableName,
                'apiEndpoints' => [
                    ['url' => 'regions/' . $regionsTableName, 'method' => 'GET', 'name' => 'index'],
                    ['url' => 'regions/' . $regionsTableName . '/[:id]', 'method' => 'GET', 'name' => 'show'],
                    ['url' => 'regions/' . $regionsTableName, 'method' => 'POST', 'name' => 'create'],
                    ['url' => 'regions/' . $regionsTableName . '/[:id]', 'method' => 'PUT', 'name' => 'update'],
                    ['url' => 'regions/' . $regionsTableName . '/[:id]', 'method' => 'DELETE', 'name' => 'delete'],
                    ['url' => 'regions/' . $regionsTableName, 'method' => 'DELETE', 'name' => 'clear'],
                ],
            ],
            'objects' => [
                'objectNameSingular' => 'object',
                'objectNamePlural' => 'objects',
                'name' => $objectsTableName,
                'apiEndpoints' => [
                    ['url' => 'objects/' . $objectsTableName, 'method' => 'GET', 'name' => 'index'],
                    ['url' => 'objects/' . $objectsTableName . '/[:id]', 'method' => 'GET', 'name' => 'show'],
                    ['url' => 'objects/' . $objectsTableName, 'method' => 'POST', 'name' => 'create'],
                    ['url' => 'objects/' . $objectsTableName . '/[:id]', 'method' => 'PUT', 'name' => 'update'],
                    ['url' => 'objects/' . $objectsTableName . '/[:id]', 'method' => 'DELETE', 'name' => 'delete'],
                    ['url' => 'objects/' . $objectsTableName, 'method' => 'DELETE', 'name' => 'clear'],
                ],
            ],
        ];
    }


    return $map;
};
