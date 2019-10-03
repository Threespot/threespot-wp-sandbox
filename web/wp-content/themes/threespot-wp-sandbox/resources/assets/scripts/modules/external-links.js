//------------------------------------------------------------------------
// Automatically add an icon to external links
//
// FIXME: Demo script, don’t include in production unless actually needed
//------------------------------------------------------------------------
// Import the ES5 code since Webpack isn’t setup to transpile external resources
import Unorphanize from "@threespot/unorphanize";

const externalIcon = '<svg class="external-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" preserveAspectRatio="xMidYMid meet" height="14" width="16"><path d="M448 279v185c0 27-21 48-48 48H48c-27 0-48-21-48-48V112c0-27 21-48 48-48h248a24 24 0 0 1 17 7l16 16c15 15 4 41-17 41H64v320h320V295c0-6 3-12 7-17l16-16c15-15 41-4 41 17zM576 37c0-20-17-37-37-37H380c-15 0-28 13-28 28v18c0 16 13 28 29 28l67-2-249 247c-9 9-9 25 0 34l24 24c9 9 25 9 34 0l247-249-2 67c0 16 12 29 28 29h18c15 0 28-13 28-28V37z"/></svg>';

// Find all non-relative links
var links = document.querySelectorAll('.u-richtext a[href^="http"]');

// Filter out buttons and links inside of divs (i.e. shortcodes)
if (links.length) {
  links = [...links].filter(function(el) {
    var isInsideDiv = el.parentNode.tagName.toLowerCase() === "div" || false;
    var isButton = el.classList.contains("btn");
    return !isInsideDiv && !isButton;
  });
}

links.forEach(function(el) {
  // Create a dummy anchor element to easily get the hostname of the href attribute
  // https://gist.github.com/jlong/2428561
  var a = document.createElement("a");
  a.href = el.href;

  // Compare the link hostname to the window hostname
  if (a.hostname !== window.location.hostname) {
    new Unorphanize(el, {
      inlineStyles: false,
      className: "u-nowrap is-external",
      append: externalIcon
    });
  }
});
