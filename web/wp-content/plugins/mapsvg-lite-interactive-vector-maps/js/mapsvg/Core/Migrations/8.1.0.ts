export default function migration(options) {
  if (options.events) {
    options.events.afterInit = options.events.afterLoad || ""
    options.events.beforeInit = options.events.beforeLoad || ""
    if (options.events["beforeLoad.regions"]) {
      options.events.afterLoadRegions = options.events["afterLoad.regions"]
      delete options.events["afterLoad.regions"]
    }
    if (options.events["afterLoad.objects"]) {
      options.events.afterLoadObjects = options.events["afterLoad.objects"]
      delete options.events["afterLoad.objects"]
    }
  }
  return options
}
