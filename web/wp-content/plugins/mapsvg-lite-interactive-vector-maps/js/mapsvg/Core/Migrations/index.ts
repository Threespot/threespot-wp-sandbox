import { MapOptions } from "@/Map/OptionsInterfaces/MapOptions"
import v8_0_0 from "./8.0.0"
import v8_1_0 from "./8.1.0"

const migrationsObject = {
  v8_0_0,
  v8_1_0,
}

const snakeToDot = (str: string) => str.replace("_", ".").replace("v", "")

const migrations: { version: string; handler: (options: MapOptions) => MapOptions }[] = []
Object.keys(migrationsObject).forEach((versionSnake) => {
  const versionNumberDot = snakeToDot(versionSnake)

  migrations.push({
    version: versionNumberDot,
    handler: migrationsObject[versionSnake],
  })
})

export { migrations }
