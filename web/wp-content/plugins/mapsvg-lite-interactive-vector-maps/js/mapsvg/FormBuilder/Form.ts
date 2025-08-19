/**
 * Class containing parameters of a Form. Used for filters on the front-end.
 * Create forms by using {@link FormBuilder}
 */
export class Form {
  title: string
  fields: any

  constructor(options) {
    this.title = options.title
    this.fields = options.fields
  }

  inputToObject(formattedValue) {
    const obj = {}

    function add(obj, name, value) {
      //if(!addEmpty && !value)
      //    return false;
      if (name.length == 1) {
        obj[name[0]] = value
      } else {
        if (obj[name[0]] == null) obj[name[0]] = {}
        add(obj[name[0]], name.slice(1), value)
      }
    }

    if ($(this).attr("name") && !($(this).attr("type") == "radio" && !$(this).prop("checked"))) {
      add(obj, $(this).attr("name").replace(/]/g, "").split("["), formattedValue)
    }

    return obj
  }
}
