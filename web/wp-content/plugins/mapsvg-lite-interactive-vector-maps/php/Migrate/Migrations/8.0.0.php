<?php

namespace MapSVG;

/**
 */
return function () {

    $db = Database::get();

    $addFieldsToSchema = function () use ($db) {
        $schemaTableName = $db->mapsvg_prefix . "schema";
        $schemaTableExists = $db->get_var("SHOW TABLES LIKE '{$schemaTableName}'");

        if ($schemaTableExists) {

            // Define the columns to add
            $columnsToAdd = [
                'objectNameSingular' => 'VARCHAR(255)',
                'objectNamePlural' => 'VARCHAR(255)',
            ];

            // Get the existing columns from the table
            $existingColumnsQuery = "SHOW COLUMNS FROM `{$schemaTableName}`";
            $existingColumnsResult = $db->get_results($existingColumnsQuery);

            // Fetch the existing columns
            $existingColumns = [];
            if ($existingColumnsResult) {
                foreach ($existingColumnsResult as $row) {
                    $existingColumns[] = $row->Field; // Assuming $row is an object
                }
            }

            // Prepare the ALTER TABLE statement
            $alterTableQuery = "ALTER TABLE $schemaTableName";

            // Add columns that don't exist
            $addColumns = [];
            foreach ($columnsToAdd as $column => $type) {
                if (!in_array($column, $existingColumns)) {
                    $addColumns[] = "ADD COLUMN $column $type";
                }
            }

            if (!empty($addColumns)) {
                $alterTableQuery .= ' ' . implode(', ', $addColumns);
                $db->query($alterTableQuery);
            }

            $schemaRepo = RepositoryFactory::get("schema");
            $schemas = $schemaRepo->find();
            foreach ($schemas["items"] as $schema) {
                $data = null;
                if (strpos($schema->name, "region") === 0) {
                    $schema->objectNameSingular = "region";
                    $schema->objectNamePlural = "regions";
                } elseif (strpos($schema->name, "post")) {
                    $schema->objectNameSingular = "post";
                    $schema->objectNamePlural = "posts";
                } else {
                    $schema->objectNameSingular = "object";
                    $schema->objectNamePlural = "objects";
                }
                $updateQuery = $db->prepare(
                    "UPDATE {$schemaTableName}
                    SET objectNameSingular = %s, objectNamePlural = %s 
                    WHERE id = %d",
                    [
                        $schema->objectNameSingular,
                        $schema->objectNamePlural,
                        $schema->id
                    ]
                );
                $db->query($updateQuery);
            }
        }
    };

    $addFieldsToSchema();
};
