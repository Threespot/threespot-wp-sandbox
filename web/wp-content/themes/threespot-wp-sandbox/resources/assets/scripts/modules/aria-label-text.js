//------------------------------------------------------------------------
// Adds “aria-label” to elements with uppercase text to prevent screen readers
// from speaking each letter in certain edge cases (e.g. “NEW” as “n-e-w”).
//
// NOTE: “aria-label” only works on certain elements (not <p>, <span>, or <div>)
// https://developer.paciellogroup.com/blog/2017/07/short-note-on-aria-label-aria-labelledby-and-aria-describedby/
//
// Example:
// <button class="f-uppercase">new</button>
//
// Becomes:
// <button class="f-uppercase" aria-label="new">new</button>
//------------------------------------------------------------------------
"use strict";

const selector = ".f-uppercase";

const addAriaLabel = function(el) {
  const text = el.textContent;

  if (text.length && !el.hasAttribute("aria-label")) {
    el.setAttribute("aria-label", text.toLowerCase().trim());
  }
};

const els = document.querySelectorAll(selector);

els.forEach( el => addAriaLabel(el) );
