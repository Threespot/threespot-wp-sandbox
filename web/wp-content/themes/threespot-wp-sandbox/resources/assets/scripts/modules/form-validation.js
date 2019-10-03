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

import ValidateForm from "../lib/form-validation";

document.querySelectorAll("form[data-validate]").forEach(el => new ValidateForm(el));
