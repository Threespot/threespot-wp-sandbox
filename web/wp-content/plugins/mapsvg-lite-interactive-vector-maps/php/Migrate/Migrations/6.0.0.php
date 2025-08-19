<?php

namespace MapSVG;

/**
 */
return function () {

    $db = Database::get();


    $v6RenameFields = function () use ($db) {
        $db = Database::get();
        $schemasRepo = RepositoryFactory::get("schema");
        $schemas = $schemasRepo->find();

        $dbIdField = [
            "name" => "id",
            "label" => "ID",
            "type" => "id",
            "db_type" => "int(11)",
            "visible" => true,
            "protected" => true,
            "searchable" => false
        ];

        $regionIdField = [
            "name" => "id",
            "label" => "ID",
            "type" => "id",
            "db_type" => "varchar(255)",
            "visible" => true,
            "protected" => true,
            "searchable" => true
        ];

        $regionTitleField = [
            "name" => "title",
            "label" => "Title",
            "type" => "text",
            "db_type" => "varchar(255)",
            "visible" => true,
            "searchable" => true
        ];

        foreach ($schemas["items"] as $schema) {
            $tableExists = $db->get_var("SHOW TABLES LIKE '" . $db->mapsvg_prefix . esc_sql($schema->name) . "'");
            if (!$tableExists) {
                continue;
            }
            $postField = $schema->getField("post_id");
            if ($postField) {
                $schema->renameField('post_id', 'post');
                $db->query('ALTER TABLE ' . $db->mapsvg_prefix . esc_sql($schema->name) . ' CHANGE `post_id` `post` int(11)');
            }
            if (strpos($schema->name, "database_") === 0) {
                $idField = $schema->getField("id");
                if (!$idField) {
                    $schema->addField($dbIdField, true);
                }
            }
            if (strpos($schema->name, "regions_") === 0) {
                $titleField = $schema->getField("title");
                if (!$titleField) {
                    $schema->addField($regionTitleField, true);
                }
                $idField = $schema->getField("id");
                if (!$idField) {
                    $schema->addField($regionIdField, true);
                }
                $regionTitleColExists = $db->get_results('SHOW COLUMNS FROM `' . $db->mapsvg_prefix . esc_sql($schema->name) . '` LIKE \'region_title\';');
                if (!empty($regionTitleColExists)) {
                    $db->query('ALTER TABLE ' . $db->mapsvg_prefix . esc_sql($schema->name) . ' CHANGE `region_title` `title` varchar(255)');
                }
            }
            $schemasRepo->update($schema, true);
        }
    };

    $v6UpgradeSchemaTable = function () use ($db) {

        $charset = "default character set utf8 collate utf8_unicode_ci";
        $schemaTableName = $db->mapsvg_prefix . "schema";
        $schemaTableExists = $db->get_var('SHOW TABLES LIKE \'' . $schemaTableName . '\'');

        if (!$schemaTableExists) {
            $db->query("CREATE TABLE " . $schemaTableName . " (`id` int(11) AUTO_INCREMENT, `title` varchar(255) DEFAULT '', `name` varchar(255) DEFAULT '', `fields` longtext, PRIMARY KEY (id))" . esc_sql($charset));
            $oldSchemaTableName = $db->prefix . "mapsvg_schema";
            $oldSchemaTableExists = $db->get_var('SHOW TABLES LIKE \'' . $oldSchemaTableName . '\'');
            if ($oldSchemaTableExists) {
                $schemas = $db->get_results("SELECT * FROM " . $db->prefix . "mapsvg_schema");
                if (!empty($schemas)) {
                    foreach ($schemas as $schema) {
                        $name = explode("_", $schema->table_name);
                        if (count($name) > 2) {
                            $id = $name[count($name) - 1];
                            $name2 = $name[count($name) - 2];

                            $name = $name2 . "_" . $id;
                        }
                        $schema->name = $name;
                        unset($schema->table_name);
                        $db->insert($schemaTableName, (array)$schema);
                    }
                }
            }
        }
    };

    $v6AddMapsTable = function () use ($db) {
        $db = Database::get();
        $mapsTableName = esc_sql($db->mapsvg_prefix . "maps");
        $map_table_exists = $db->get_var('SHOW TABLES LIKE \'' . $mapsTableName . '\'');
        if (!$map_table_exists) {
            $charset_collate = "default character set utf8 collate utf8_unicode_ci";
            $db->query("CREATE TABLE " . $mapsTableName . " (`id` int(11) AUTO_INCREMENT, `title` varchar(255) DEFAULT '', `options` longtext DEFAULT '', `svgFilePath` varchar(500), `svgFileLastChanged` int(11) UNSIGNED, `version` varchar(20), `status` tinyint(1) UNSIGNED default 1,`statusChangedAt` timestamp, PRIMARY KEY (id))" . esc_sql($charset_collate));
            $db->query("INSERT INTO " . $mapsTableName . " (`id`, `title`, `options`) SELECT id, post_title, post_content from " . $db->prefix . "posts where post_type='mapsvg'");
        }


        $mapsRepo = RepositoryFactory::get("map");
        $maps = $mapsRepo->find();

        $v6FixMap = function ($map) use ($db) {

            if ($map->optionsBroken) {

                /**
                 * DO NOTHING AS THE ENDPOINT IS NOT WORKING AT THE MOMENT
                 */
                return;

                $post = get_post($map->id);
                // JSON may be broken. If that's the case, try to fix that
                $response = wp_remote_post(
                    'http://mapsvg.com:5050',
                    array(
                        'method' => 'POST',
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => array(),
                        'body' => array("options" => $post->post_content),
                        'cookies' => array()
                    )
                );

                if (is_wp_error($response)) {
                    $error_message = $response->get_error_message();
                    return;
                } else {
                    $data = json_decode($response['body'], true);
                    if (!$data) {
                        return;
                    } else {
                        $map->setOptions($data);
                    }
                }
            }

            $map->version = get_post_meta($map->id, 'mapsvg_version', true);
            if (!$map->version) {
                $map->version = '1.0.0';
            }

            if (!isset($map->options['database'])) {
                $map->options['database'] = [];
            }
            $map->options['database']['regionsTableName'] = 'regions_' . $map->id;
            $map->options['database']['objectsTableName'] = 'database_' . $map->id;
            if (isset($map->options['tooltips']) && $map->options['tooltips']['on'] === true) {
                if (!$map->options['actions']) {
                    $map->options['actions'] = [
                        'region' => ['mouseover' => []],
                        'marker' => ['mouseover' => []]
                    ];
                }
                $map->options['actions']['region']['mouseover']['showTooltip'] = true;
                $map->options['actions']['marker']['mouseover']['showTooltip'] = true;
            }

            $map->setOptions($map->options);

            $mapsRepo = RepositoryFactory::get("map");
            $mapsRepo->update($map);
        };

        foreach ($maps["items"] as $map) {
            $v6FixMap($map);
        }
    };

    $copyDbAndRegionsTables = function () use ($db) {
        $tables = $db->get_results('SHOW TABLES LIKE \'%mapsvg_database_%\'');
        if (!empty($tables)) foreach ($tables as $tableName) {
            $name = array_values(get_object_vars($tableName));
            $tableName = $name[0];
            $parts = explode("_", $tableName);
            $id = end($parts);
            $newTableNameSanitized = esc_sql(str_replace("mapsvg_", MAPSVG_PREFIX, $tableName));
            $tablesNew = $db->get_results('SHOW TABLES LIKE \'' . $newTableNameSanitized . '\'');
            // if (!empty($tablesNew) && isset($_GET['reload_db'])) {
            //     $db->query("DROP TABLE " . $newTableNameSanitized);
            //     $tablesNew = null;
            // }
            if (empty($tablesNew)) {
                $db->query("CREATE TABLE " . $newTableNameSanitized . " LIKE " . esc_sql($tableName));
                $db->query("INSERT INTO " . $newTableNameSanitized . " SELECT * FROM " . esc_sql($tableName));
                $db->query('update ' . $newTableNameSanitized . ' set regions = replace(regions,"}",",\"tableName\": \"regions_' . esc_sql($id) . '\"}")');
            }
        }
        $tables = $db->get_results('SHOW TABLES LIKE \'%mapsvg_regions_%\'');
        if (!empty($tables)) foreach ($tables as $tableName) {
            $name = array_values(get_object_vars($tableName));
            $tableName = $name[0];
            $newTableNameSanitized = esc_sql(str_replace("mapsvg_", MAPSVG_PREFIX, $tableName));
            $tablesNew = $db->get_results('SHOW TABLES LIKE \'' . $newTableNameSanitized . '\'');
            // if (!empty($tablesNew) && isset($_GET['reload_regions'])) {
            //     $db->query("DROP TABLE " . $newTableNameSanitized);
            //     $tablesNew = null;
            // }
            if (empty($tablesNew)) {
                $db->query("CREATE TABLE " . $newTableNameSanitized . " LIKE " . esc_sql($tableName));
                $db->query("INSERT INTO " . $newTableNameSanitized . " SELECT * FROM " . esc_sql($tableName));
            }
        }
    };

    $v6AddSettingsTable = function () use ($db) {
        $settings_table_exists = $db->get_var('SHOW TABLES LIKE \'' . $db->mapsvg_prefix . 'settings\'');
        if (!$settings_table_exists) {
            $charset = "default character set utf8 collate utf8_unicode_ci";
            $settingsTableNameSanitized = esc_sql($db->mapsvg_prefix . "settings");
            $db->query("CREATE TABLE `" . $settingsTableNameSanitized . "` (`key` varchar(100), `value` varchar(100), PRIMARY KEY (`key`)) " . esc_sql($charset));
            $oldOptions = array('mapsvg_purchase_code', 'mapsvg_google_api_key', 'mapsvg_google_geocoding_api_key');
            foreach ($oldOptions as $optionName) {
                $optionValue = get_option($optionName);
                if ($optionValue) {
                    $optionNameSanitized = esc_sql(str_replace('mapsvg_', '', $optionName));
                    $pairSanitized = array('key' => $optionNameSanitized, 'value' => esc_sql($optionValue));
                    $db->insert($settingsTableNameSanitized, $pairSanitized);
                }
            }
        }
    };

    $v6AddR2oTable = function () use ($db) {
        $r2o_table_exists = $db->get_var('SHOW TABLES LIKE \'' . $db->mapsvg_prefix . 'r2o\'');
        $copy_r2o_query = "INSERT INTO " . $db->mapsvg_prefix . "r2o (`objects_table`, `regions_table`, `region_id`, `object_id`) SELECT CONCAT('objects_',map_id), CONCAT('regions_',map_id), `region_id`, `object_id` from " . $db->prefix . "mapsvg_r2d";
        if (!$r2o_table_exists) {
            $charset = "default character set utf8 collate utf8_unicode_ci";
            $db->query("CREATE TABLE " . $db->mapsvg_prefix . "r2o (objects_table varchar(100), regions_table varchar(100), region_id varchar(100), object_id int(11), INDEX (objects_table, regions_table, region_id)) " . esc_sql($charset));
            $r2d_table_exists = $db->get_var('SHOW TABLES LIKE \'' . $db->prefix . 'mapsvg_r2o\'');
            if ($r2d_table_exists) {
                $db->query($copy_r2o_query);
            }
        } elseif (isset($_GET["reload_r2o"])) {
            $db->query($copy_r2o_query);
        }
    };

    $v6AddR2oTable();
    $v6AddSettingsTable();
    $v6UpgradeSchemaTable();
    $v6AddMapsTable();
    $copyDbAndRegionsTables();
    $v6RenameFields();
};
