//------------------------------------------------------------------------
// Basic form validation
//
// Usage Info:
// • Add “js-validate” to <form> or other wrapper
// • Add appropriate data-validate value to each input
//   - data-validate="email"
//   - data-validate="phone"
//   - data-validate="number"
//   - data-validate="zip"
//   - data-validate="notempty"
// • Add error message markup inside of <label>
//     <span class="is-hidden" data-validate="error" role="alert">Please enter a valid email address</span>
// • Add `data-validate="submit"` to submit button
//     <button type="submit" data-validate="submit">Submit</button>
//     To disable scrolling to the first error message, add the “data-no-jump” attribute
// • Required fields should use `aria-required="true"` when possible, but `required` and `data-validate-required` will also work
//  • For groups of checkboxes, wrap in <fieldset data-validate-required> and add appropriate `data-validate-group` value (see examples below)
//    - data-validate-group="min-1"
//    - data-validate-group="max-2"
//    - data-validate-group="notempty"
//  • For groups of radios, wrap in <fieldset role="radiogroup" aria-required="true"> and add appropriate `data-validate-group` value (see examples above)
//------------------------------------------------------------------------
"use strict";

export default class ValidateForm {
  constructor(el) {
    this.el = el;
    this.isAjaxSubmit = this.el.hasAttribute("data-form-ajax");

    // Add the “novalidate” attribute to form to disable default browser error messages if using “required” attribute
    // Note: `prop('novalidate', true)` doesn't work for some reason ¯\_(ツ)_/¯
    this.el.setAttribute("novalidate", "novalidate");

    // Find error messages
    this.errorMsgs = this.el.querySelectorAll("[data-validate='error']");

    // Submit button
    this.submitBtn = this.el.querySelector("[data-validate='submit']");

    if (!this.submitBtn) {
      console.warn(`Form is missing “button[data-validate="submit"]” element, skipping validation.`, this.el);
      return false;
    }

    this.shouldScroll = !this.submitBtn.hasAttribute("data-no-jump");
    this.submitText = this.submitBtn.textContent;

    // Elements that require validation, set to `aria-invalid='true'` on load
    this.validateEls = this.el.querySelectorAll("[data-validate]");

    // Exclude submit and error messages
    this.validateEls = [...this.validateEls].filter(
      el =>
        el.getAttribute("data-validate") !== "submit" &&
        el.getAttribute("data-validate") !== "error"
    );

    // Cache the associated elements refences to render error messages
    // this.storeElementReferences(this.$validateEls);

    // Groups of checkboxes/radios that require validation
    this.groups = this.el.querySelectorAll("[data-validate-group]");
    this.currentGroup = false;

    // Store previously focused checkbox/radio group, validate when focus changes
    this.prevGroup = false;

    // Validating every possible email is a fool’s errand and not necessary.
    // Instead, just check for at least one “@” and “.”
    // https://davidcel.is/posts/stop-validating-email-addresses-with-regex/
    this.emailRegex = /^.+@.+\..+$/;

    // If more validation is desired, we can check for only one “@”
    // Based on https://github.com/plataformatec/devise/blob/593ae41f9dac165a404b05cd3abd959245c64908/lib/devise.rb#L109-L113
    // this.emailRegex =  /^[^@\s]+@([^@\s]+\.)+[^@\s]+$/;

    // Numeric value test (allows single decimal)
    this.numericRegex = /^((\d+)|(\.\d+)|(\d+\.\d+))%?$/;

    // Phone number test (very loose, allows everything except line breaks)
    this.phoneRegex = /^.+$/;

    // Postal code RegEx, allows from 2–12 letters, numbers, spaces, or dashes
    this.zipRegex = /^[\w\d\- ]{2,12}$/;

    this.bindEvents();
  }

  bindEvents() {
    let self = this;

    // Validate on blur (i.e. when input loses focus)
    this.validateEls.forEach(el =>
      el.addEventListener("blur", evt => this.validate(evt))
    );

    // Selects, radios, and checkboxes require listening to the “change” event instead of “blur”
    // Validate on blur (i.e. when input loses focus)
    this.validateEls.forEach(el =>
      el.addEventListener("change", evt => this.validate(evt))
    );

    // Validate groups of selects, radios, or checkboxes
    let groupInputEls = document.querySelectorAll(
      "[data-validate-group] input"
    );

    groupInputEls.forEach(el =>
      el.addEventListener("change", evt => this.validateGroup(evt))
    );

    // Hide error messages while typing
    let validateTextInputs = this.validateEls.filter(
      el =>
        el.tagName.toLowerCase() === "input" ||
        el.tagName.toLowerCase() === "textarea"
    );

    validateTextInputs.forEach(el =>
      el.addEventListener("input", evt => this.hideErrorMsg(evt.target))
    );

    // Don’t allow non-numerical characters in number inputs
    let validateNumberInputs = this.validateEls.filter(
      el => el.getAttribute("data-validate") === "number"
    );

    validateNumberInputs.forEach(el =>
      el.addEventListener("keypress", evt => this.numberEvent(evt))
    );

    // Validate form on submit
    this.el.addEventListener("submit", evt => this.submitHandler(evt));

    // Using the back button in Firefox and Safari displays a cached page with the submit button still disabled, so we have to manually reenable it.
    // https://bugzilla.mozilla.org/show_bug.cgi?id=443289
    // http://stackoverflow.com/a/13123626/
    window.addEventListener("pageshow", function(evt) {
      if (evt.persisted) {
        self.resetSubmitButton();
      }
    });
  }

  isEmail(str) {
    return this.emailRegex.test(str);
  }

  isNumeric(str) {
    return this.numericRegex.test(str);
  }

  isPhone(str) {
    return this.phoneRegex.test(str);
  }

  isZip(str) {
    return this.zipRegex.test(str);
  }

  isEmpty(el) {
    // For checkboxes and radio buttons, use the “chacked” attribute
    if (
      el.getAttribute("type") === "checkbox" ||
      el.getAttribute("type") === "radio"
    ) {
      return !el.checked;
    } else {
      // For all other elements, use the value
      return !el.value;
    }
  }

  // We can’t apply the “required” attribute or “aria-required="true"” on checkbox inputs, or groups of them, so we have to use the  custom “data-validate-required” attribute
  // https://github.com/GoogleChrome/accessibility-developer-tools/issues/283
  isRequired(el) {
    return (
      el.getAttribute("aria-required") === "true" ||
      el.required ||
      el.hasAttribute("data-validate-required")
    );
  }

  // Add commas to dollar amounts
  // Source: http://stackoverflow.com/a/2901298
  formatDollars(val) {
    return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  // Strip out any non-digits except for decimal points
  getDecimal(val) {
    return val.replace(/[^0-9.]/g, '');
  }

  numberEvent(evt) {
    // Get typed character
    let key = String.fromCharCode(evt.which);
    const allowedChars = /[,.0-9%]/;

    // Don't allow non-number characters to be entered
    if (!allowedChars.test(key)) {
      evt.preventDefault();
    }
  }

  // Get label element whose “for” attribute matches current element’s “id” attribute
  getErrorMsgParent(el) {
    // If element has an ID, look for an associated label
    if (el.hasAttribute("id")) {
      return this.el.querySelector(`[for="${el.getAttribute("id")}"]`);
    } else {
      // Groups don’t have IDs and associated labels, so get error message parent
      let errorMsg = el.querySelector("[data-validate='error']");
      return errorMsg.length ? errorMsg.parentNode : false;
    }
  }

  // Get error message element associated with form element
  getErrorMsg(el) {
    return this.getErrorMsgParent(el).querySelector("[data-validate='error']");
  }

  // Show an element’s error message and update its “aria-invalid” state
  showErrorMsg(el) {
    let errorMsg = this.getErrorMsg(el);
    errorMsg.classList.remove("is-hidden");
    el.classList.add("is-invalid");
    el.setAttribute("aria-invalid", true);
  }

  // Hide an element’s error message and update its “aria-invalid” state
  hideErrorMsg(el) {
    let errorMsg = this.getErrorMsg(el);
    errorMsg.classList.add("is-hidden");
    el.classList.remove("is-invalid");
    el.setAttribute("aria-invalid", false);
  }

  // Show group error
  showGroupErrorMsg(el) {
    el.setAttribute("aria-invalid", true);

    let errorMsg = el.querySelector("[data-validate='error']");

    if (errorMsg) {
      errorMsg.classList.remove("is-hidden");
    }
  }

  // Clear group error
  hideGroupErrorMsg(el) {
    el.setAttribute("aria-invalid", false);

    let errorMsg = el.querySelector("[data-validate='error']");
    if (errorMsg.length) {
      errorMsg.classList.add("is-hidden");
    }
  }

  // Toggle error message
  toggleErrorMsg(isValid, el) {
    if (isValid) {
      this.hideErrorMsg(el);
    } else {
      this.showErrorMsg(el);
    }
  }

  getInvalidFields() {
    // Find all validated elements
    let invalidEls = [...this.validateEls, ...this.groups];
    // Filter only invalid ones
    invalidEls = [...invalidEls].filter(
      el => el.getAttribute("aria-invalid") === "true"
    );

    return invalidEls;
  }

  // Test if element is valid and toggle error msg
  validateEl(el) {
    var type = el.getAttribute("data-validate");
    var val = el.value;

    switch (type) {
      // Emails
      case "email":
        this.toggleErrorMsg(this.isEmail(val), el);
        break;

      // Numbers (allows single decimal and/or % sign)
      case "number":
        val = val.replace(/,/g, ""); // strip commas
        el.value = val;
        this.toggleErrorMsg(this.isNumeric(val), el);
        break;

      // Phone number (allows everything except line breaks)
      case "phone":
        this.toggleErrorMsg(this.isPhone(val), el);
        break;

      // ZIP code (from 2–12 letters, numbers, dashes, or spaces)
      case "zip":
        this.toggleErrorMsg(this.isZip(val), el);
        break;

      // Not empty
      case "notempty":
        this.toggleErrorMsg(!this.isEmpty(el), el);
        break;
    }
  }

  // Event handler to validate elements on blur or change
  validate(evt) {
    var targetEl = evt.target;

    // Exclude blank optional fields
    if (this.isRequired(targetEl) || !this.isEmpty(targetEl)) {
      this.validateEl(targetEl);
    }

    // Reset current group (the elements that trigger this function aren’t part of a group)
    this.currentGroup = false;
  }

  // Validate all single elements
  validateAll() {
    var self = this;

    this.validateEls.forEach(function(el) {
      // Exclude optional fields that are blank
      if (self.isRequired(el) || !self.isEmpty(el)) {
        self.validateEl(el);
      }
    });
  }

  // Validate group when focus leaves (can’t detect blur on fieldsets)
  validateGroupEl(el) {
    let condition = el.getAttribute("data-validate-group");
    const isExact = !isNaN(condition); // true if string can be converted to a number
    const checkedEls = el.querySelectorAll("input:checked");

    // Matches exact number of checked elements
    if (isExact && parseInt(condition, 10) === checkedEls.length) {
      this.hideGroupErrorMsg(el);
      return false;
    }

    // Has at least one checked element
    if (condition === "notempty" && checkedEls.length > 0) {
      this.hideGroupErrorMsg(el);
      return false;
    }

    // Min/max conditions
    const isMinMax =
      condition.indexOf("min-") === 0 || condition.indexOf("max-") === 0;

    if (isMinMax) {
      let conditionArr = condition.split("-");
      var type = conditionArr[0];
      var value = parseInt(conditionArr[1], 10);

      if (type === "min" && checkedEls.length >= value) {
        this.hideGroupErrorMsg(el);
        return false;
      } else if (type === "max" && checkedEls.length <= value) {
        this.hideGroupErrorMsg(el);
        return false;
      }
    }

    // Show error
    this.showGroupErrorMsg(el);
  }

  // Event handler to validate group on change
  validateGroup(evt) {
    var targetEl = evt.target;
    var currentGroup = targetEl.closest("[data-validate-group]");

    // Clear error on current group
    this.hideGroupErrorMsg(currentGroup);

    // If no previous group, set it to current group
    if (!this.prevGroup) {
      this.prevGroup = currentGroup;
    } else if (!!this.prevGroup && !currentGroup.isEqualNode(this.prevGroup)) {
      // If group has changed, validate the previous group
      this.validateGroupEl(this.prevGroup);

      // Update previous group
      this.prevGroup = currentGroup;
    }
  }

  // Validate all groups
  validateAllGroups() {
    var self = this;

    this.groups.forEach(function(el) {
      // Validate required groups and those with selected items
      // (non-required groups without any selected items will be ignored)
      if (self.isRequired(el) || el.checked) {
        self.validateGroupEl(el);
      }
    });
  }

  // Submit form data via XMLHttpRequest()
  // https://developer.mozilla.org/en-US/docs/Learn/HTML/Forms/Sending_forms_through_JavaScript
  sendData() {
    let self = this;

    const XHR = new XMLHttpRequest();
    // Use FormData interface (IE 10+)
    // https://developer.mozilla.org/en-US/docs/Web/API/FormData
    const FD = new FormData(this.el);

    // console.log([...FD]);

    // Define what happens on successful data submission
    XHR.addEventListener("load", function() {
      const successShow = self.el.getAttribute("data-success-show");
      const successHide = self.el.getAttribute("data-success-hide");

      // Hide elements on success
      if (successHide) {
        document
          .querySelectorAll(successHide)
          .forEach(el => (el.style.display = "none"));
      }

      // Show elements on success
      if (successShow) {
        document
          .querySelectorAll(successShow)
          .forEach(el => (el.style.display = "block"));
      }

      // TODO: Make form reset optional

      // Reset form fields
      let inputEls = self.el.querySelectorAll("input");
      let textareas = self.el.querySelectorAll("textarea");

      let allInputs = [...inputEls, ...textareas].filter(
        el =>
          el.getAttribute("type") !== "hidden" ||
          el.getAttribute("type") !== "submit"
      );

      // Reset form fields
      allInputs.forEach(function(el) {
        el.value = "";
        el.checked = false;
      });

      self.resetSubmitButton();
    });

    // Define what happens in case of error
    XHR.addEventListener("error", function() {
      const errorShow = self.el.getAttribute("data-error-show");

      // Show elements on success
      if (errorShow) {
        document
          .querySelectorAll(errorShow)
          .forEach(el => (el.style.display = "block"));
      }

      self.resetSubmitButton();

      console.error(
        "Unable to post form data to " + self.el.getAttribute("action")
      );
    });

    // Set up our request
    XHR.open("POST", this.el.getAttribute("action"));

    // Send our FormData object; HTTP headers are set automatically
    XHR.send(FD);
  }

  resetSubmitButton() {
    this.submitBtn.disabled = false;
    this.submitBtn.setAttribute("aria-busy", "false");
  }

  // Don't allow submit if validation errors are present
  submitHandler(evt) {
    // Validate all single elements
    this.validateAll();

    // Validate all groups
    this.validateAllGroups();

    // Check for invalid fields
    let invalidEls = this.getInvalidFields();

    // If form is invalid, scroll to first error message
    if (invalidEls.length) {
      // Prevent form submission
      evt.preventDefault();

      // Focus input
      invalidEls[0].focus();

      if (this.shouldScroll) {
        // Find associated label and scroll into view
        let label = document.querySelector(`[for="${invalidEls[0].id}"]`);

        if (label) {
          label.scrollIntoView();
        } else {
          // If can’t find label, scroll to input
          invalidEls[0].scrollIntoView();
        }
      }
    } else {
      // Disable submit button to prevent multiple submissions
      this.submitBtn.disabled = true;
      this.submitBtn.setAttribute("aria-busy", "true");

      // Check if form should submit via AJAX
      if (this.isAjaxSubmit) {
        evt.preventDefault();
        this.sendData();
      }

      // Optional: Trigger “form-valid” event for other modules
      // Backbone.trigger('form-valid', $(evt.target));
    }
  }
}
