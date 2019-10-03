var S3UploaderFieldView = Backbone.View.extend({

  currentFileURL: null, // A URL to the existing asset, provided by the server
  imagePreviewURL: null, // A URL to an image preview of the existing asset, provided by the server
  presignedPost: null, // Holds the Amazon S3 pre-signed post that we receive from Rails
  userFile: null, // Holds the captured user file
  userFileDisplayName: null, // Holds a calculated name for the captured file
  fileJQXHR: null, // Contains the jqXHR object when an upload is in progress

  // -------------------------------------------------------------------------
  // INITIALIZE
  // Find all of the elements and set up the UI
  // -------------------------------------------------------------------------

  initialize: function() {

    this.$touchRegion = this.$el.find(".s3-uploader-field-touch-region").first();

    this.$fileField = this.$el.find("[data-uploader=file-field]").first();
    this.$urlField = this.$el.find("[data-uploader=url-field]").first();

    this.$urlModeButton = this.$el.find("[data-uploader=url-mode]").first();
    this.$cancelButton = this.$el.find("[data-uploader=cancel]").first();
    this.$discardButton = this.$el.find("[data-uploader=discard]").first();
    this.$openCurrentLink = this.$el.find("[data-uploader=open-current]").first();
    this.$icon = this.$el.find("[data-uploader=icon]").first().hide();
    this.$emptyWell = this.$el.find("[data-uploader=empty-well]").first();
    this.$mainLabel = this.$el.find("[data-uploader=main-label]").first();
    this.$progressBar = this.$el.find("progress").first();

    this.imagePreviewURL = this.$el.data("uploader-preview-url");
    this.currentFileURL = this.$el.data("uploader-current-url");

    this.renderEmptyState();

  },

  // -------------------------------------------------------------------------
  // RENDERING/UI MODES
  // -------------------------------------------------------------------------

  // An untouched/empty version of the widget, shows existing file information
  // if that was available on page load

  renderEmptyState: function() {

    if (!!this.imagePreviewURL || !!this.currentFileURL) {
      this.$mainLabel.html("Drag and drop a replacement file here, <span>or select one</span>");
      if (!!this.currentFileURL) {
        this.$icon.removeClass().addClass("icon icon-file-o icon-exists").show();
        this.$emptyWell.hide();
      }
      if (!!this.imagePreviewURL) {
        this.$icon.hide();
        this.$emptyWell.show();
      }
    }
    else {
      this.$mainLabel.html("Drag and drop a file here, <span>or select one</span>");
      this.$icon.hide();
      this.$emptyWell.show();
    }

    this.$el.removeClass("-uploading -ready");
    this.$urlModeButton.show();
    this.$openCurrentLink.show();
    this.$discardButton.hide();
    this.$cancelButton.hide();
    this.$progressBar.hide();

  },

  // The widget flashes and shows a progress bar while uploading a file

  renderUploadState: function() {
    this.$el.addClass("-uploading");
    this.$emptyWell.hide();
    this.$icon.removeClass().addClass("icon icon-cloud-upload icon-pulse").show();
    this.$mainLabel.html("Uploading " + this.userFileDisplayName + "…");
    this.$urlModeButton.hide();
    this.$openCurrentLink.hide();
    this.$discardButton.hide();
    this.$cancelButton.show();
    this.$progressBar.show();
  },

  // The widget shows a confirmation that the file was uploaded and ready to save

  renderReadyState: function() {
    this.$emptyWell.hide();
    this.$openCurrentLink.hide();
    this.$urlModeButton.hide();
    this.$cancelButton.hide();
    this.$progressBar.hide();
    this.$icon.removeClass().addClass("icon icon-check-circle-o").show();
    this.$mainLabel.html("Ready to save " + this.userFileDisplayName + "…");
    this.$discardButton.show();
    this.$el.removeClass("-dragover").addClass("-ready");
  },

  // Passed an error message, displays a dismissible error message

  renderErrorState: function(errorMessage) {
    this.$emptyWell.hide();
    this.$openCurrentLink.hide();
    this.$urlModeButton.hide();
    this.$cancelButton.hide();
    this.$progressBar.hide();
    this.$discardButton.show();
    this.$icon.removeClass().addClass("icon icon-exclamation-circle icon-danger").show();
    this.$mainLabel.html(errorMessage + " Please report this issue.");
    this.$el.removeClass("-dragover").addClass("-error");
  },

  // The field glows when hovering over it

  dragHighlight: function(event) {
    event.preventDefault();
    this.$el.addClass("-dragover");
  },

  dragDim: function(event) {
    event.preventDefault();
    this.$el.removeClass("-dragover");
  },

  // Discard most of our UI and reveal the underlying URL field
  // so that the user can manually provide their own URL

  renderURLMode: function(event) {
    event.stopPropagation();
    event.preventDefault();
    this.$touchRegion.remove();
    this.$el.before(this.$urlField);
    this.$el.remove();
    this.$urlField.show().focus();
  },

  // Resize the progress bar to the given size, between 0-100

  resizeProgressBar: function(size) {
    this.$progressBar.attr("value", size);
    this.$progressBar.html(size + "%");
  },

  // -------------------------------------------------------------------------
  // FILE CAPTURING
  // We can get a file API object from the input[type=file] or a
  // HTML 5 `drop` event.
  // -------------------------------------------------------------------------

  // Capture an upload a file from an input[type=file]

  fileFromFileField: function(event) {
    event.preventDefault();
    event = event.originalEvent;
    if (!!event.target.files.length) {
      this.userFile = event.target.files[0]
      console.log("Caught user file", this.userFile);
      this.setUserFileDisplayName();
      this.presignAndUploadFile();
    }
    return false;
  },

  // Capture and upload a file from a drag/drop event

  fileFromDropEvent: function(event) {
    event.preventDefault();
    event = event.originalEvent;
    if (!!event.dataTransfer.files.length) {
      this.userFile = event.dataTransfer.files[0];
      console.log("Caught user file", this.userFile);
      this.setUserFileDisplayName();
      this.presignAndUploadFile();
    }
    return false;
  },

  // Determine a truncated filename from the captured file

  setUserFileDisplayName: function() {
    this.userFileDisplayName = this.userFile.name.substring(0,30).trim();
  },

  // Clear all of the internal data

  resetData: function() {
    this.$urlField.val("");
    this.$fileField.val("");
    this.presignedPost = null;
    this.userFile = null;
    this.userFileDisplayName = null;
    this.fileJQXHR = null;
  },

  // Discard the (finished) S3 upload and start over

  discardUploadedFile: function(event) {
    event.stopPropagation();
    event.preventDefault();
    this.resetData();
    this.renderEmptyState();
  },

  // Abort the jqXHR in progress and start over

  cancelFileUpload: function(event) {
    event.stopPropagation();
    event.preventDefault();
    this.fileJQXHR.abort();
    this.resetData();
    this.renderEmptyState();
  },

  // -------------------------------------------------------------------------
  // UPLOAD CHAIN
  //
  // 1. The Rails server has the AWS key pair
  // 2. Ask the Rails server to generate an AWS pre-signed bucket post.
  // 3. Upload a file, passing the pre-signed post fields. The file is sent
  //    directly to S3, and a URL to the temporary file is returned.
  // 4. Set the URL as the form field value, so that Paperclip processes that
  //    file on S3 when we save this form.
  // -------------------------------------------------------------------------

  // Get a presigned post for the captured file and upload it
  // Requires an existing `this.userFile`

  presignAndUploadFile: function() {

    var self = this;

    self.renderUploadState();

    // Get a pre-signed post from the Rails server

    self.fileJQXHR = $.ajax({

      type: "POST",
      url: "/admin/api/presigned_post",
      dataType: "json", // The server returns a JSON document

      data: {
        content_type: self.userFile.type, // Send the server the file mimetype for AWS signing
      },

      beforeSend: function(xhr) {
        xhr.setRequestHeader("X-CSRF-Token", RailsMeta.csrfToken);
      },

      error: function(xhr, status, error) {
        console.log("Could not get presigned S3 post", xhr, status, error, xhr["responseText"]);
        self.renderErrorState("Could not get a signature from Amazon S3.");
      },

      statusCode: {
        201: function(data, status, xhr) {
          self.presignedPost = data;
          console.log("Got presigned post from S3", self.presignedPost);
          self.uploadFile(); // Now upload it to S3
        }
      }

    });

  },

  // Upload the captured file
  // Requires an existing `this.presignedPost` and `this.userFile`

  uploadFile: function() {

    var self = this;

    var formData = new FormData();

    // Do not change the order of these fields
    // For some reason S3 wants `file` to be last and `key` to be early in the request
    formData.append("key", self.presignedPost["key"]);
    formData.append("acl", self.presignedPost["acl"]);
    formData.append("policy", self.presignedPost["policy"]);
    formData.append("x-amz-credential", self.presignedPost["x-amz-credential"]);
    formData.append("x-amz-signature", self.presignedPost["x-amz-signature"]);
    formData.append("x-amz-algorithm", self.presignedPost["x-amz-algorithm"]);
    formData.append("x-amz-date", self.presignedPost["x-amz-date"]);
    formData.append("success_action_status", self.presignedPost["success_action_status"]);
    formData.append("Content-Type", self.userFile.type)
    formData.append("file", self.userFile, self.userFile.name);

    self.fileJQXHR = $.ajax({

      type: "POST",
      url: "https://" + self.presignedPost["bucket_name"] + ".s3.amazonaws.com",
      processData: false, // Do not perform smart processing on the outgoing data
      contentType: false, // Do not permit jQuery to determine the content type of the outgoing data
      mimeType: "multipart/form-data", // S3 requires this form encoding format
      dataType: "xml", // S3 will sent back an XML document
      data: formData, // Submit our constructed form above

      // We need to build a custom XHR object with a progress event bound.
      // jQuery doesn't support this progress event natively
      xhr: function() {
        var xhr = new window.XMLHttpRequest();
        xhr.upload.addEventListener("progress", function(event) {
          if (event.lengthComputable) {
            self.resizeProgressBar((event.loaded / event.total) * 100);
          }
        }, false);
        return xhr;
      },

      error: function(xhr, status, error) {
        // The user aborted the request intentionally, so no error is shown visually
        if (status === "abort") {
          console.log("User aborted S3 upload");
        }
        // Something else bad happened
        else {
          console.log("Could not upload to Amazon S3", xhr, status, error, xhr["responseText"]);
          self.renderErrorState("Your file upload was refused by Amazon S3.");
        }
      },

      statusCode: {
        201: function(xmlDocument, status, xhr) {
          self.$urlField.val($(xmlDocument).find("Location").first().text());
          console.log("Successful upload to Amazon S3", self.$urlField.val());
          self.renderReadyState();
        }
      }

    });

  },

  // -------------------------------------------------------------------------
  // MISC EVENTS
  // -------------------------------------------------------------------------

  // Click the file input if we’re not currently uploading anything

  clickFileField: function(event) {
    event.stopPropagation();
    event.preventDefault();
    if (!this.fileJQXHR) {
      this.$fileField.click();
    }
  },

  // Don't propagate the passed event

  dontPropigate: function(event) {
    event.stopPropagation();
  },

  // -------------------------------------------------------------------------
  // EVENT MATRIX
  // -------------------------------------------------------------------------

  events: {
    "click .s3-uploader-field-touch-region": "clickFileField",
    "dragenter": "dragHighlight",
    "dragover": "dragHighlight",
    "dragexit": "dragDim",
    "dragleave": "dragDim",
    "drop": "fileFromDropEvent",
    "change input[type=file]": "fileFromFileField",
    "click [data-uploader=url-mode]": "renderURLMode",
    "click [data-uploader=cancel]": "cancelFileUpload",
    "click [data-uploader=discard]": "discardUploadedFile",
    "click [data-uploader=open-current]": "dontPropigate"
  }

});

FormOrchestrator.register(".s3-uploader-field", "S3UploaderFieldView");

$(".s3-uploader-field").each(function(index, $element) {
  new S3UploaderFieldView({el:$element});
});
