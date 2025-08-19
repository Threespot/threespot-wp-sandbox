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

            $schemaTableName = $db->mapsvg_prefix . "schema";

            $query2 = <<<EOD
            UPDATE $schemaTableName
            SET type = CASE
                WHEN name LIKE 'regions%' THEN 'region'
                WHEN name LIKE 'objects%' THEN 'object'
                WHEN name LIKE 'posts%' THEN 'post'
                ELSE 'object'
            END;    
            EOD;
            $db->query($query2);
        }
    };
    $addFieldsToSchema();
};
