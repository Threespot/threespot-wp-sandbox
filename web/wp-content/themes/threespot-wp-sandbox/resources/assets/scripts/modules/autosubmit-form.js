//------------------------------------------------------------------------
// Autosubmit form when field is changed with a mouse (requires “what-input”)
//------------------------------------------------------------------------
"use strict";

document.querySelectorAll('form [data-autosubmit]').forEach(el => {
  el.addEventListener("change", () => {
    // Don’t autosubmit for non-mouse users
    if (window.document.documentElement.getAttribute("data-whatinput") === "mouse") {
      let formEl = el.closest("form");
      if (formEl) {
        formEl.submit();
      }
    }
  });
});
