import { mapsvgCore } from "@/Core/Mapsvg"
import { parseBoolean } from "@/Core/Utils"
import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { Server } from "../../../Infrastructure/Server/Server"
import { PostInterface } from "../../../Post/PostInterface"
import { FormBuilder } from "../../FormBuilder"
import { FormElement } from "../FormElement"

const $ = jQuery

/**
 *
 */
export class PostFormElement extends FormElement {
  id: number
  post?: PostInterface
  declare inputs: { postSelect: HTMLSelectElement }
  add_fields: boolean
  post_type: boolean
  post_types: string[]

  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)

    if (this.formBuilder.admin) this.post_types = this.formBuilder.admin.getPostTypes()
    this.post_type = options.post_type || this.post_types[0]
    this.add_fields = parseBoolean(options.add_fields)
    this.db_type = "int(11)"
    this.name = "post"
    this.typeLabel = "WP Post"
    this.post = options.post
  }

  setDomElements() {
    super.setDomElements()
    this.inputs.postSelect = <HTMLSelectElement>(
      $(this.domElements.main).find(".mapsvg-find-post")[0]
    )
  }

  getSchema(): { [p: string]: any } {
    const schema = super.getSchema()
    schema.post_type = this.post_type
    schema.add_fields = this.add_fields
    return schema
  }

  destroy() {
    // @ts-ignore
    if ($().mselect2) {
      const sel = $(this.domElements.main).find(".mapsvg-select2")
      if (sel.length) {
        //@ts-ignore
        sel.mselect2("destroy")
      }
    }
  }

  getDataForTemplate(): { [p: string]: any } {
    const data = super.getDataForTemplate()

    if (this.formBuilder.admin) data.post_types = this.formBuilder.admin.getPostTypes()
    data.post_type = this.post_type
    data.post = this.post
    data.add_fields = this.add_fields || 0
    return data
  }

  setEventHandlers() {
    super.setEventHandlers()

    const server = new Server(mapsvgCore.routes.api)

    // @ts-ignore
    $(this.inputs.postSelect)
      .mselect2({
        placeholder: "Search post by title",
        allowClear: true,
        disabled: this.readonly,
        ajax: {
          url: server.getUrl("posts"),
          dataType: "json",
          delay: 250,
          data: (params) => {
            return {
              filters: { post_type: this.post_type },
              search: params.term, // search term
              page: params.page,
            }
          },
          processResults: (data, params) => {
            // parse the results into the format expected by Select2
            // since we are using custom formatting functions we do not need to
            // alter the remote JSON data, except to indicate that infinite
            // scrolling can be used
            params.page = params.page || 1

            return {
              results: data.posts ? data.posts : [],
              pagination: {
                more: false, //(params.page * 30) < data.total_count
              },
            }
          },
          cache: true,
        },
        escapeMarkup: (markup) => {
          return markup
        }, // let our custom formatter work
        minimumInputLength: 1,
        templateResult: this.formatRepo, // omitted for brevity, see the source of this page
        templateSelection: this.formatRepoSelection, // omitted for brevity, see the source of this page
      })
      .on("select2:select", (e) => {
        const post = e.params.data
        this.setValue(post)
        this.setInputValue(post)
        this.triggerChanged()
      })
      .on("change", (e) => {
        if (e.target.value === "") {
          $(this.domElements.main).find(".mapsvg-post-id").text("")
          $(this.domElements.main).find(".mapsvg-post-url").text("")
          this.setValue(null, false)
          this.triggerChanged()
        }
      })
  }

  formatRepo(repo): string {
    if (repo.loading) {
      return repo.text
    } else {
      return "<div class='select2-result-repository clearfix'>" + repo.post_title + "</div>"
    }
  }

  formatRepoSelection(repo): string {
    return repo?.post_title || repo.text
  }

  setValue(post: PostInterface, updateInput = true) {
    this.value = post
    if (updateInput) {
      this.setInputValue(post)
    }
  }

  setInputValue(post: PostInterface): void {
    $(this.domElements.main).find(".mapsvg-post-id").text(post.id)
    $(this.domElements.main).find(".mapsvg-post-url").text(post.url).attr("href", post.url)
  }
}
