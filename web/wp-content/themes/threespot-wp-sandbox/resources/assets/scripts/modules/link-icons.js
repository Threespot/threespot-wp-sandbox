//------------------------------------------------------------------------
// Append icons to links and prevent them from wrapping
//
// FIXME: Demo script, don’t include in production unless actually needed
//------------------------------------------------------------------------
import Unorphanize from "@threespot/unorphanize";

// Use simple rounding function since we only need one decimal place.
// This apprach has issues when rounding to more decimal places:
// e.g. Math.round(1.005 * 100) / 100; // 1 instead of 1.01
// http://www.jacklmoore.com/notes/rounding-in-javascript/
const roundSingleDecimal = function(number) {
  return Math.round(number * 10) / 10;
};

// When more decimal places are desired, see this MDN page for a better function:
// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/round#A_better_solution
//
// function round(number, precision) {
//   var shift = function (number, exponent) {
//     var numArray = ("" + number).split("e");
//     return +(numArray[0] + "e" + (numArray[1] ? (+numArray[1] + precision) : precision));
//   };
//   return shift(Math.round(shift(number, +exponent)), -exponent);
// }

// FIXME: Replace example icons
const icons = {
  arrow: {
    class: "icon icon-arrow",
    viewBox: "0 0 118 91",
    width: 17,
    // height: 13, // optionally set the default height
    path: `<path d="M72.7 0l42 42 3.4 3.5-3.4 3.5-42 42-7.1-7.1L99 50.4H0v-9.9h99L65.6 7.1 72.7 0z"/>`
    // customAttrs: '' // optional additional attrs (e.g. fill="none" stroke="#fff" stroke-width="1")
  },
  download: {
    class: "icon icon-download",
    viewBox: "0 0 576 768",
    width: 12,
    path: `<path d="M256 0h64v530l169-169 46 46-224 224-23 22-23-22L41 407l46-46 169 169V0zM0 704h576v64H0v-64z"/>`
  },
  external: {
    class: "icon icon-external",
    viewBox: "0 0 576 512",
    width: 16,
    path: `<path d="M448 279v185c0 27-21 48-48 48H48c-27 0-48-21-48-48V112c0-27 21-48 48-48h248a24 24 0 0 1 17 7l16 16c15 15 4 41-17 41H64v320h320V295c0-6 3-12 7-17l16-16c15-15 41-4 41 17zM576 37c0-20-17-37-37-37H380c-15 0-28 13-28 28v18c0 16 13 28 29 28l67-2-249 247c-9 9-9 25 0 34l24 24c9 9 25 9 34 0l247-249-2 67c0 16 12 29 28 29h18c15 0 28-13 28-28V37z"/>`
  }
};

const buildSVG = function(icon) {
  return `
    <svg class="${icon.class}" viewBox="${icon.viewBox}" width="${icon.width}" height="${icon.height}" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false" ${icon.customAttrs}>
      ${icon.path}
    </svg>`;
};

const fileTypes = ["pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx"];

const nodes = document.querySelectorAll("[data-icon]");

nodes.forEach(function(el) {
  let iconName = el.getAttribute("data-icon");

  // Check if icon exists
  if (!(iconName in icons)) {
    console.warn(`Icon “${iconName}” was not found in link-icons.js`, el);
    return false;
  }

  // Automatically change the icon if it’s external or a download link
  // NOTE: This is project-specific, edit as needed.
  if (!el.classList.contains("FIXME-dont-change-icon")) {

    // If link is external, use external icon (excluding certain CTA links/buttons below)
    // External link test from https://gist.github.com/jlong/2428561
    var a = document.createElement("a");
    a.href = el.href;
    if (a.hostname !== window.location.hostname) {
      iconName = "external";
    }

    // Check if link is a file download
    let fileExt = a.pathname.split(".").pop();

    if (fileTypes.indexOf(fileExt) > -1) {
      iconName = "download";
    }
  }

  // Create new object for this icon so we can change
  // the dimensions without affecting the defaults.
  const icon = {};

  // Copy values from original icons object
  Object.assign(icon, icons[iconName]);

  // Check for custom size attributes
  let iconHeight = el.getAttribute("data-icon-height");
  let iconWidth = el.getAttribute("data-icon-width");

  // Validate height and width values if present
  // Note: Use unary plus (+) operator to convert strings to numbers
  // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Arithmetic_Operators#Unary_plus_()
  if (iconHeight) {
    if (isNaN(+iconHeight)) {
      console.warn(`Can’t parse data-icon-height value of “${iconHeight}” on ${el}`);
      return false;
    } else {
      icon.height = +iconHeight;
    }
  }

  if (iconWidth) {
    if (isNaN(+iconWidth)) {
      console.warn(`Can parse data-icon-width value of “${iconWidth}” on ${el}`);
      return false;
    } else {
      icon.width = +iconWidth;
    }
  }

  // Make sure either the height or width has been defined
  if (!icon.height && !icon.width) {
    console.warn(`No height or width defined for icon “${iconName}”`, icon);
    return false;
  }

  // Calculate height or width if only one dimension was provided
  // Note: We can’t rely on CSS to resize SVGs in IE11-
  //       because IE doesn’t respect the viewBox ratio.
  let viewBoxArr = icon.viewBox.split(" ");

  // Validate viewBox value
  if (viewBoxArr.length !== 4) {
    console.warn(`Icon “${iconName}” has a malformed viewBox attribute: “${icon.viewBox}”`);
    return false;
  }

  // Calculate aspect ratio
  let aspectRatio = +viewBoxArr[2] / +viewBoxArr[3];

  // Calculate height if width was provided
  if (!icon.height && icon.width) {
    icon.height = roundSingleDecimal(icon.width / aspectRatio);
  }

  // Calculate width if height was provided
  if (!icon.width && icon.height) {
    icon.width = roundSingleDecimal(icon.height * aspectRatio);
  }

  // Insert the icon using Unorphanize to prevent wrapping
  new Unorphanize(el, {
    inlineStyles: false,
    className: "u-nowrap",
    append: buildSVG(icon)
  });
});
