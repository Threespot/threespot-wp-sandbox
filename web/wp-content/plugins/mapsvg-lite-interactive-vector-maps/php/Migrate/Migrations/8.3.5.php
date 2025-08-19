<?php

namespace MapSVG;

/**
 */
return function () {

    $db = Database::get();

    $addFieldsToSchema = function () use ($db) {
        $schemaTableName = $db->mapsvg_prefix . "schema";
        $schemaTableExists = $db->get_var('SHOW TABLES LIKE \'' . $schemaTableName . '\'');

        if ($schemaTableExists) {
            // Check if apiEndpoints column exists
            $columnExists = $db->get_var("SHOW COLUMNS FROM `$schemaTableName` LIKE 'objectNameSingular'");


            if (!$columnExists) {
                // Define the columns to add
                $migration_8_0_0 = require __DIR__ . DIRECTORY_SEPARATOR . '8.0.0.php';
                // Execute the 8.0.0 migration
                $migration_8_0_0();
            }
        }
    };

    $addFieldsToSchema();
};
