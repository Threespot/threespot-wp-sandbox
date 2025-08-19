import { MapOptions } from "@/Map/OptionsInterfaces/MapOptions"
import { compareVersions } from "../Utils"
import { migrations } from "./index"

const deepCopy = <T>(obj: T): T => JSON.parse(JSON.stringify(obj))

// Function to get the migration with the latest version number
const getLatestMigration = () => {
  if (migrations.length === 0) return null

  return migrations.reduce((latest, current) => {
    return compareVersions(current.version, latest.version) > 0 ? current : latest
  })
}

export const migrate = (fromVersion: string, options: MapOptions) => {
  const latestMigration = getLatestMigration()
  if (!latestMigration) {
    return
  }
  const toVersion = latestMigration.version
  const originalOptions = deepCopy(options)
  let modifiedOptions: MapOptions = originalOptions

  let currentVersion = fromVersion

  // Filter migrations that are needed
  const requiredMigrations = migrations.filter(
    (migration) => compareVersions(currentVersion, migration.version) === -1,
  )

  if (requiredMigrations.length > 0) {
    for (const migration of requiredMigrations) {
      try {
        // Apply the migration to the copy of original options
        modifiedOptions = migration.handler(modifiedOptions)
        currentVersion = migration.version
      } catch (e) {
        console.error(`Could not update options to version ${toVersion} due to error:`, e)
        // Return the original options if migration fails
        return {
          options: originalOptions,
          version: fromVersion,
          success: false,
        }
      }
    }
  }

  return {
    options: modifiedOptions,
    version: currentVersion,
    success: true,
  }
}
