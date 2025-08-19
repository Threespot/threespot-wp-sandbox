import Sortable from "sortablejs"
import { SchemaField } from "../../../Infrastructure/Server/SchemaField"
import { FormBuilder } from "../../FormBuilder"
import { FormElement } from "../FormElement"
import "./styles.css"

const $ = jQuery

/**
 *
 */
export class ImagesFormElement extends FormElement {
  searchType: string
  images: { [key: string]: string }[]
  mediaUploader: any
  button_text: string
  sortable: Sortable

  constructor(options: SchemaField, formBuilder: FormBuilder, external: { [key: string]: any }) {
    super(options, formBuilder, external)

    this.searchType = options.searchType || "fulltext"
    this.mediaUploader = external.mediaUploader
    this.typeLabel = "Images"

    this.button_text = options.button_text || "Browse..."
    this.db_type = "text"
    this.label = options.label || "Images"
    this.name = options.name || "images"

    this.images = this.value || []
    this.value = JSON.stringify(this.value)
  }

  init() {
    super.init()
    this.redrawImages()
  }

  setDomElements() {
    super.setDomElements()
    // this.domElements.btnUpload = <HTMLElement>$(this.domElements.main).find('.mapsvg-upload-image')[0];
    // this.domElements.btnImageDelete = <HTMLElement>$(this.domElements.main).find('.mapsvg-upload-image')[0];
    // this.inputs.text = <HTMLInputElement>$(this.domElements.main).find('input[type="text"]')[0];
  }

  getData(): { name: string; value: any } {
    this.updateData()
    // if(this.images && this.images.length && this.images[0]!=null){
    //     var newList = [];
    //     $(this.domElements.main).find('.mapsvg-thumbnail-wrap').each(function(index, el){
    //         var imageData = $(el).data('image');
    //         newList.push(imageData);
    //     });
    //     this.images = newList;
    //     // data[control.name] = data[control.name].concat(control.images);
    // }
    return { name: this.name, value: this.images }
  }

  getSchema(): { [p: string]: any } {
    const schema = super.getSchema()
    schema.button_text = this.button_text
    return schema
  }

  updateData() {
    const newListOfImages = []

    $(this.domElements.main)
      .find(".mapsvg-thumbnail-wrap")
      .each(function (index, el) {
        const imageData = $(el).data("image")
        newListOfImages.push(imageData)
      })
    this.images = newListOfImages
    this.value = JSON.stringify(this.images)
    $(this.domElements.main).find("input").val(this.value)
  }

  setEventHandlers() {
    super.setEventHandlers()

    if (this.formBuilder.editMode) {
      return
    }

    const _this = this

    const imageDOM = $(this.domElements.main).find(".mapsvg-data-images")

    // this.external.mediaUploader.off("select");

    // When a file is selected, grab the URL and set it as the text field's value
    this.external.mediaUploader.on("select", () => {
      if (_this.formBuilder.mediaUploaderisOpenedFor !== _this) return false
      const attachments = _this.external.mediaUploader.state().get("selection").toJSON()
      attachments.forEach(function (img) {
        const image: any = { sizes: {} }
        for (const type in img.sizes) {
          image[type] = img.sizes[type].url.replace("http://", "//").replace("https://", "//")
          image.sizes[type] = {
            width: img.sizes[type].width,
            height: img.sizes[type].height,
          }
        }
        if (!image.thumbnail) {
          image.thumbnail = image.full
          image.sizes.thumbnail = {
            width: img.sizes.full.width,
            height: img.sizes.full.height,
          }
        }
        if (!image.medium) {
          image.medium = image.full
          image.sizes.medium = {
            width: img.sizes.full.width,
            height: img.sizes.full.height,
          }
        }

        // image.title = img.title;
        image.caption = img.caption
        image.description = img.description

        _this.images.push(image)
      })
      this.setValue(this.images)
      _this.redrawImages()
    })
    $(_this.domElements.main).on("click", ".mapsvg-upload-image", function (e) {
      e.preventDefault()
      // Open the uploader dialog
      _this.formBuilder.mediaUploaderisOpenedFor = _this
      _this.external.mediaUploader.open()
    })
    $(_this.domElements.main).on("click", ".mapsvg-image-delete", function (e) {
      e.preventDefault()
      $(this).closest(".mapsvg-thumbnail-wrap").remove()
      _this.updateData()
      // _this.images = [];
      // $(_this.domElements.main).find('img').each(function (i, image) {
      //     _this.images.push($(image).data('image'));
      // });
      // $(_this.domElements.main).find('input').val(JSON.stringify(_this.images));
    })

    _this.sortable = new Sortable(imageDOM[0], {
      animation: 150,
      onStart: function () {
        $(_this.domElements.main).addClass("sorting")
      },
      onEnd: function (evt) {
        _this.images = []
        $(_this.domElements.main)
          .find("img")
          .each(function (i, image) {
            _this.images.push($(image).data("image"))
          })
        this.value = JSON.stringify(_this.images)
        $(_this.domElements.main).find("input").val(this.value)
        $(_this.domElements.main).removeClass("sorting")
      },
    })
  }

  redrawImages() {
    const _this = this
    const imageDOM = $(this.domElements.main).find(".mapsvg-data-images")
    imageDOM.empty()
    this.images &&
      this.images.forEach(function (image) {
        const img = $('<img class="mapsvg-data-thumbnail" />')
          .attr("src", image.thumbnail)
          .data("image", image)
        const imgContainer = $('<div class="mapsvg-thumbnail-wrap"></div>').data("image", image)
        imgContainer.append(img)
        imgContainer.append('<i class="bi bi-x-lg  mapsvg-image-delete"></i>')
        imageDOM.append(imgContainer)
      })
    $(this.domElements.main).find("input").val(this.value)
  }

  destroy() {
    super.destroy()
    this.external.mediaUploader.off("select")
  }
}
