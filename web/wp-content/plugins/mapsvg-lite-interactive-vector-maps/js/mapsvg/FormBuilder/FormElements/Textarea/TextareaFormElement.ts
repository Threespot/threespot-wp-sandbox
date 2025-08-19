// import CodeMirror from "codemirror"
import { parseBoolean } from "@/Core/Utils"
import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { FormBuilder } from "../../FormBuilder"
import { FormElement } from "../FormElement"

const $ = jQuery

/**
 *
 */
export class TextareaFormElement extends FormElement {
  searchType: string
  html: boolean
  htmlEditor: any
  autobr: boolean
  editor: CodeMirror.Editor
  format: "text" | "json" | "html"
  declare inputs: { textarea: HTMLTextAreaElement }

  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)
    this.searchType = options.searchType || "fulltext"
    this.searchable = parseBoolean(options.searchable)
    this.autobr = options.autobr
    this.html = options.html
    this.format = this.html ? "html" : options.format || "text"
    this.db_type = "text"
    this.typeLabel = "Long text"
  }

  setDomElements() {
    super.setDomElements()
    this.inputs.textarea = $(this.domElements.main).find("textarea")[0]

    if (this.format !== "text") {
      const formats = {
        html: { name: "handlebars", base: "text/html" },
        json: { name: "javascript", json: true },
      }
      this.editor = window.CodeMirror.fromTextArea(this.inputs.textarea, {
        mode: formats[this.format],
        //@ts-ignore
        matchBrackets: true,
        lineNumbers: true,
        lint: {
          esversion: 2023,
          asi: true, // Allow missing semicolons
        },
        extraKeys: {
          Tab: function (cm) {
            cm.replaceSelection("  ", "end")
          },
        },
        gutters: ["CodeMirror-lint-markers"],
      })
      if (this.formBuilder.admin) {
        this.editor.on("change", this.setTextareaValue)
      }
    }
  }

  setEventHandlers() {
    super.setEventHandlers()
    $(this.inputs.textarea).on("change keyup paste", (e) => {
      this.setValue(e.target.value, false)
      this.triggerChanged()
    })
  }

  getSchema(): { [p: string]: any } {
    const schema = super.getSchema()
    schema.autobr = this.autobr
    schema.html = this.html
    return schema
  }

  getDataForTemplate(): { [p: string]: any } {
    const data = super.getDataForTemplate()
    data.html = this.html
    return data
  }

  setTextareaValue(codemirror) {
    const handler = codemirror.getValue()
    const textarea = $(codemirror.getTextArea())
    textarea.val(handler).trigger("change")
  }

  destroy() {
    const cm = $(this.domElements.main).find(".CodeMirror")
    if (cm.length) {
      cm.empty().remove()
    }
    super.destroy()
  }
}
