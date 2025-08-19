<?php

namespace MapSVG;

/**
 * Class that recursively updates mapsvg, starting from the lowest version
 * and going up through all updates until the last version.
 */
class Upgrade
{

    protected $db;


    public function __construct()
    {
        $this->db = Database::get();
    }

    /**
     * Checks the mapsvg version and upgrades it if necessary
     */
    function run()
    {

        $dbVersion = $this->isSettingsTableExists() ? Options::get('db_version') : '1.0.0';

        

        // Check if the current map version is outdated
        if (is_null($dbVersion) || version_compare($dbVersion, MAPSVG_VERSION, '<')) {
            // Get all migration files with version numbers higher than the current version
            $migrations = $this->getPendingMigrations($dbVersion);

            // Apply each migration to the map options
            foreach ($migrations as $migrationFile) {
                try {
                    $this->applyMigration($migrationFile);
                } catch (\Exception $e) {
                    // Optionally log the error or handle it as needed
                    Logger::error("Migration failed: " . $e->getMessage());
                    break;
                }
            }

            Options::set('db_version', MAPSVG_VERSION);
        }
    }

    /**
     * @return string|null
     */
    public function isSettingsTableExists()
    {
        $settings_table_exists = $this->db->get_var('SHOW TABLES LIKE \'' . $this->db->mapsvg_prefix . 'settings\'');
        return $settings_table_exists;
    }


    private function getPendingMigrations($currentVersion)
    {
        $migrations = [];
        $migrationDir = __DIR__ . DIRECTORY_SEPARATOR . 'Migrations';



        if (!is_dir($migrationDir)) {
            Logger::error("MapSVG: Migration directory does not exist: $migrationDir");
            return $migrations;
        }

        if (!is_readable($migrationDir)) {
            Logger::error("MapSVG: Migration directory is not readable: $migrationDir");
            return $migrations;
        }

        $globPattern = $migrationDir . DIRECTORY_SEPARATOR . '*.php';
        $files = glob($globPattern);

        if ($files === false) {
            Logger::error("MapSVG: glob() failed for pattern: $globPattern");
            return $migrations;
        }

        if (empty($files)) {
            Logger::error("MapSVG: No migration files found in: $migrationDir");
            return $migrations;
        }

        foreach ($files as $file) {
            $version = basename($file, '.php');

            if (is_null($currentVersion) || version_compare($version, $currentVersion, '>')) {
                $migrations[$version] = $file;
            }
        }


        // Sort migrations by version
        uksort($migrations, 'version_compare');

        return $migrations;
    }

    private function applyMigration($migrationFile)
    {
        $migration = require $migrationFile;

        if (is_callable($migration)) {
            $migration();
        } else {
            Logger::error("MapSVG: Migration file is not callable: $migrationFile");
        }
    }
}
