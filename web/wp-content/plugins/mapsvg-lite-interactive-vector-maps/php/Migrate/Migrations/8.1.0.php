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

            // Define the columns to add
            $columnsToAdd = [
                'apiEndpoints' => 'TEXT',
                'apiBaseUrl' => 'VARCHAR(255)',
                'apiAuthorization' => 'VARCHAR(255)',
                'type' => 'VARCHAR(50)',
            ];

            // Define the columns to drop
            $columnsToDrop = [
                'apiEndpoint'
            ];

            // Get the existing columns from the table
            $existingColumnsQuery = "SHOW COLUMNS FROM `$schemaTableName`";
            $existingColumnsResult = $db->get_results($existingColumnsQuery);

            // Fetch the existing columns
            $existingColumns = [];
            if ($existingColumnsResult) {
                foreach ($existingColumnsResult as $row) {
                    $existingColumns[] = $row->Field; // Assuming $row is an object
                }
            }

            // Prepare the ALTER TABLE statement
            $alterTableQueryParts = [];

            // Add columns that don't exist
            foreach ($columnsToAdd as $column => $type) {
                if (!in_array($column, $existingColumns)) {
                    $alterTableQueryParts[] = "ADD COLUMN $column $type";
                }
            }

            // Drop columns that exist
            foreach ($columnsToDrop as $column) {
                if (in_array($column, $existingColumns)) {
                    $alterTableQueryParts[] = "DROP COLUMN $column";
                }
            }

            // Execute the ALTER TABLE statement if there are any changes
            if (!empty($alterTableQueryParts)) {
                $alterTableQuery = "ALTER TABLE $schemaTableName " . implode(', ', $alterTableQueryParts);
                $db->query($alterTableQuery);
            }
        }

        $query = <<<EOD
            UPDATE $schemaTableName
            SET apiEndpoints = CONCAT(
                '[',
                '{"url":"', objectNamePlural,'/',name,'","method":"GET","name":"index"},',
                '{"url":"', objectNamePlural,'/',name, '/[:id]","method":"GET","name":"show"},',
                '{"url":"', objectNamePlural,'/',name, '","method":"POST","name":"create"},',
                '{"url":"', objectNamePlural,'/',name, '/[:id]","method":"PUT","name":"update"},',
                '{"url":"', objectNamePlural,'/',name, '/[:id]","method":"DELETE","name":"delete"},',
                '{"url":"', objectNamePlural,'/',name, '","method":"DELETE","name":"clear"}',
                ']'
            ) WHERE apiEndpoints IS NULL;
            EOD;

        $db->query($query);

        $query2 = <<<EOD
        UPDATE $schemaTableName
        SET type = CASE
            WHEN name LIKE 'posts_%' THEN 'post'
            ELSE 'object'
        END;    
        EOD;
        $db->query($query2);
    };


    $addFieldsToSchema();
};
