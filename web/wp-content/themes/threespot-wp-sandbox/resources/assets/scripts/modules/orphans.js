//------------------------------------------------------------------------
// Wrap words to prevent orphans
//
// Basic example:
//
//   <p data-orphans>Lorem ipsum dolor sit amet</p>
//
// Output:
//
//   <p>Lorem ipsum dolor <span class="u-nowrap">sit amet</span></p>
//
//------------------------------------------------------------------------
// Custom word count:
//
//   <p data-orphans="3">Lorem ipsum dolor sit amet</p>
//
// Output:
//
//   <p>Lorem ipsum <span class="u-nowrap">dolor sit amet</span></p>
//
//------------------------------------------------------------------------
// Requires the following CSS rule:
//
//   &-nowrap {
//     @include fs-min-width(320px) {
//       display: inline-block !important;
//       white-space: nowrap !important;
//     }
//   }
//
//------------------------------------------------------------------------
import Unorphanize from "@threespot/unorphanize";

const nodes = document.querySelectorAll("[data-orphans]");

nodes.forEach(function(el) {
  const options = {
    inlineStyles: false,
    className: "u-nowrap"
  };

  // Check for custom word count
  const wordCount = el.getAttribute("data-orphans");

  // Set custom word count if defined (defaults to 2)
  if (wordCount) {
    options.wordCount = wordCount;
  }

  new Unorphanize(el, options);
});
