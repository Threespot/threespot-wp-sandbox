<?php

namespace MapSVG;

/**
 * Class that recursively updates a map, starting from the lowest version
 * and going up through all updates until the last version.
 */
class MapUpdater
{

	protected $db;


	public function __construct()
	{
		$this->db = Database::get();
	}

	/**
	 * Checks the map version and upgrades it if necessary
	 *
	 * @param object $map	 
	 * @param boolean $saveToDb
	 * @return Map | void
	 */
	function maybeUpdate(&$map, $saveToDb = false)
	{

		if ($map["version"] === MAPSVG_VERSION) {
			return $map;
		}



		// Backup the original state of the map
		$originalMap = $map;

		// Check if the current map version is outdated
		if (version_compare($map["version"], MAPSVG_VERSION, '<')) {
			// Get all migration files with version numbers higher than the current version
			$migrations = $this->getPendingMigrations($map["version"]);

			// Apply each migration to the map options
			foreach ($migrations as $migrationFile) {
				try {
					$this->applyMigration($map, $migrationFile);
				} catch (\Exception $e) {
					// An error occurred, rollback the map to its original state
					$map = $originalMap;
					// Optionally log the error or handle it as needed
					Logger::error("Migration failed: " . $e->getMessage());
					break;
				}
			}

			$map["version"] = MAPSVG_VERSION;
			$repo = RepositoryFactory::get("map");
			$repo->update($map);
		}

		// Return the updated map instance
		return $map;
	}

	private function getPendingMigrations($currentVersion)
	{
		$migrations = [];
		$migrationDir = __DIR__ . DIRECTORY_SEPARATOR . 'migrations';

		// Scan the /migrations directory for files
		foreach (glob($migrationDir . DIRECTORY_SEPARATOR . '*.php') as $file) {
			$version = basename($file, '.php');

			// Check if the file's version is higher than the current version
			if (version_compare($version, $currentVersion, '>')) {
				$migrations[$version] = $file;
			}
		}

		// Sort migrations by version
		uksort($migrations, 'version_compare');

		return $migrations;
	}

	private function applyMigration(&$map, $migrationFile)
	{
		$migration = require $migrationFile;

		if (is_callable($migration)) {
			if (!isset($map["options"]) || !is_array($map["options"])) {
				return;
			}
			$migration($map);
		}
	}
}
