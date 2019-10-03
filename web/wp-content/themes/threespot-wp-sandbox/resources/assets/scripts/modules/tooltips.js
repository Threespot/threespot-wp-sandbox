// Tooltips
"use strict";

import Tooltip from "../lib/tooltips";

document.querySelectorAll("[data-tooltip][title]").forEach(function(el) {
  new Tooltip(el, {
    // min space between tooltip and edge of screen
    gutter: 10,
    // prepend screen reader text (optional)
    prepend: `<span class="u-screenreader">Definition: </span>`,
    // append a period if there isn't one for better screen reader experience
    append: el.title.trim().slice(-1) !== "." ? `<span class="u-screenreader">.</span>` : ""
  });
});
