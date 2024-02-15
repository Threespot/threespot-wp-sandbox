//------------------------------------------------------------------------
// Append icons to links and prevent them from wrapping
//------------------------------------------------------------------------
/* eslint-disable quotes */
import Unorphanize from '@threespot/unorphanize';

export default class Icon {
  constructor(el, opts) {
    const fileTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
    const icons = {
      arrow: {
        class: 'icon icon-arrow',
        viewBox: '0 0 14 12',
        width: 15,
        // height: 13, // optionally set the default height
        path: `<g fill="currentColor"><path fill="var(--icon-color)" d="M10 6.63H0V4.92h9.98L6.85 1.67 8.1.47l5.1 5.3-5.1 5.32-1.24-1.18L10 6.63z"/></g>`
        // customAttrs: '' // optional additional attrs (e.g. fill="none" stroke="#fff" stroke-width="1")
      },
      download: {
        class: 'icon icon-download',
        viewBox: '0 0 16 21',
        width: 15,
        path: `<g fill="currentColor"><path fill="var(--icon-color)" d="M14.54 17.77V21H1.62v-3.23h12.92zM9.69 0v9.72l4.02-4L15.99 8l-8 8L0 8 2.28 5.7 6.46 9.9V0H9.7z"/></g>`
      },
      external: {
        class: 'icon icon-external',
        viewBox: '0 0 13 12',
        width: 15,
        path: `<g fill="currentColor"><path fill="var(--icon-color)" d="M2.372792 0l.001 10h9.999v2h-12V0h2zm9.74264 0v7h-2l-.00064-3.657-4.327786 4.328573-1.414214-1.414214L8.628792 2h-3.51336V0h7z"/></g>`
      }
    };

    // Use Object.assign() to merge “opts” object with default values in this.options
    this.options = Object.assign(
      {},
      {
        name: el.getAttribute('data-icon'),// use “none” to only add external or download icons
        shouldSwap: el.hasAttribute('data-icon-swap'),// switch to external or download icon when applicable
        height: el.getAttribute('data-icon-height'),// optional height override
        width: el.getAttribute('data-icon-width'),// optional width override
      },
      opts
    );

    // Check if icon exists
    if (!(this.options.name in icons) && this.options.name != 'none') {
      console.warn(`Icon “${this.options.name}” was not found in link-icons.js`, el);
      return false;
    }

    // Automatically change the icon if it’s external or a download link
    // NOTE: This is project-specific, edit as needed.
    if (this.options.shouldSwap) {
      // If link is external, use external icon (excluding certain CTA links/buttons below)
      // External link test from https://gist.github.com/jlong/2428561
      var a = document.createElement('a');
      a.href = el.href;
      if (a.hostname !== window.location.hostname) {
        this.options.name = 'external';
      }

      // Check if link is a file download
      let fileExt = a.pathname.split('.').pop();

      if (fileTypes.indexOf(fileExt) > -1) {
        this.options.name = 'download';
      }
    }

    // Exit early if not adding an icon
    if (this.options.name == 'none') {
      return false;
    }

    // Create new object for this icon so we can change
    // the dimensions without affecting the defaults.
    const icon = {};

    // Copy values from original icons object
    Object.assign(icon, icons[this.options.name]);

    // Validate height and width values if present
    // Note: Use unary plus (+) operator to convert strings to numbers
    // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Arithmetic_Operators#Unary_plus_()
    if (this.options.height) {
      if (isNaN(+this.options.height)) {
        console.warn(
          `Can’t parse data-icon-height value of “${this.options.height}” on ${el}`
        );
        return false;
      } else {
        icon.height = +this.options.height;
      }
    }

    if (this.options.width) {
      if (isNaN(+this.options.width)) {
        console.warn(
          `Can parse data-icon-width value of “${this.options.width}” on ${el}`
        );
        return false;
      } else {
        icon.width = +this.options.width;
      }
    }

    // Make sure either the height or width has been defined
    if (!icon.height && !icon.width) {
      console.warn(`No height or width defined for icon “${this.options.name}”`, icon);
      return false;
    }

    // Calculate height or width if only one dimension was provided
    // Note: We can’t rely on CSS to resize SVGs in IE11-
    //       because IE doesn’t respect the viewBox ratio.
    let viewBoxArr = icon.viewBox.split(' ');

    // Validate viewBox value
    if (viewBoxArr.length !== 4) {
      console.warn(
        `Icon “${this.options.name}” has a malformed viewBox attribute: “${icon.viewBox}”`
      );
      return false;
    }

    // Calculate aspect ratio
    let aspectRatio = +viewBoxArr[2] / +viewBoxArr[3];

    // Calculate height if width was provided
    if (!icon.height && icon.width) {
      icon.height = this.roundSingleDecimal(icon.width / aspectRatio);
    }

    // Calculate width if height was provided
    if (!icon.width && icon.height) {
      icon.width = this.roundSingleDecimal(icon.height * aspectRatio);
    }

    // Insert the icon using Unorphanize to prevent wrapping
    if (this.options.name != 'none') {
      // TODO: Replace unorphnaize with simpler logic since this is only
      //       used for links that contain plain text.
      new Unorphanize(el, {
        inlineStyles: false,
        className: 'u-nowrap',
        append: this.buildSVG(icon)
      });
    }
  }

  // Use simple rounding function since we only need one decimal place.
  // This apprach has issues when rounding to more decimal places:
  // e.g. Math.round(1.005 * 100) / 100; // 1 instead of 1.01
  // http://www.jacklmoore.com/notes/rounding-in-javascript/
  //
  // Note: If more decimal places are desired, see this MDN page:
  // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/round#A_better_solution
  //
  // function round(number, precision) {
  //   var shift = function (number, exponent) {
  //     var numArray = ('' + number).split('e');
  //     return +(numArray[0] + 'e' + (numArray[1] ? (+numArray[1] + precision) : precision));
  //   };
  //   return shift(Math.round(shift(number, +exponent)), -exponent);
  // }
  roundSingleDecimal(number) {
    return Math.round(number * 10) / 10;
  }

  buildSVG(icon) {
    // Note: Don’t add line breaks or other whitespace as that will affect the icon position in the browser
    return `<svg class="${icon.class}" viewBox="${icon.viewBox}" width="${icon.width}" height="${icon.height}" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false" ${icon.customAttrs} xmlns:xlink="http://www.w3.org/1999/xlink">${icon.path}</svg>`;
  }
}
