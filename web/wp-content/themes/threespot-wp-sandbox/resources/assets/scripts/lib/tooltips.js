//------------------------------------------------------------------------
// Tooltips
//
// Based on https://inclusive-components.design/tooltips-toggletips/
//
// Example:
// <span data-tooltip='{"content":"<p>Lorem ipsum dolor sit amet…</p><p><a href=\"http://www.google.com\">Learn more</a></p>"}'>
//------------------------------------------------------------------------
'use strict';
import debounce from "lodash/debounce";

/**
 * Wrap the last X words in an HTML tag to prevent them from wrapping (i.e. orphans)
 * @param {HTMLElement} el - Tooltip DOM node
 * @param {Object} opts - Options
 * @param {string} [opts.classes=""] - Class(es) to apply to tooltip
 * @param {number} [opts.gutter=0] - Gutter space around main content well
 */
export default class Tooltip {
  constructor(el, opts) {
    this.el = el;
    this.tooltipContent = this.el.getAttribute("title");

    if ( !this.tooltipContent ) {
      console.warn(`“${this.el.textContent.trim()}” tooltip has no “title” content`);
      return false;
    }

    // Use Object.assign() to merge “opts” object with default values in this.options
    this.options = Object.assign(
      {},
      {
        classes: "", // string, accepts multiple space-separated classes
        gutter: 0,// gutter space around main content well
        prepend: "",// HTML to prepend to tooltip
        append: ""// HTML to append to tooltip
      },
      opts
    );

    // Build tooltip, add attributes
    this.setup();

    // Event listeners
    this.events();

    // Determine tooltip position on load
    this.updatePosition();
  }

  setup() {
    this.isOpen = false;
    // Generate unique ID for each tooltip
    // https://gist.github.com/gordonbrander/2230317
    this.uniqueID = Math.random().toString(36).substr(2, 4);
    this.parentEl = this.el.parentNode;
    // this.mediaQueryList = window.matchMedia(`(min-width: ${this.options.breakpoint})`);

    // Add ARIA attributes
    this.el.setAttribute("aria-describedby", "tooltip-" + this.uniqueID);
    this.el.setAttribute("aria-expanded", "false");
    this.el.setAttribute("role", "button");
    this.el.classList.add("js-init");

    // Build tooltip
    this.tooltipEl = document.createElement('span');
    this.tooltipEl.setAttribute("aria-hidden", "true");
    this.tooltipEl.setAttribute("data-tooltip-menu","");// for styling purposes
    this.tooltipEl.setAttribute("id", `tooltip-${this.uniqueID}`);
    this.tooltipEl.setAttribute("role", "tooltip");

    if (this.options.classes) {
      this.tooltipEl.setAttribute("class", this.options.classes);
    }

    this.tooltipEl.innerHTML = this.options.prepend + this.tooltipContent + this.options.append;

    // We could could also do this, but I thinks it’s a little harder to read:
    // https://davidwalsh.name/convert-html-stings-dom-nodes
    // this.tooltipEl = new DOMParser().parseFromString(`
    //   <span id="tooltip-${this.uniqueID}" class="${this.options.classes}" aria-hidden="true" aria-expanded="false" role="alert"
    //     ${this.tooltipContent}
    //   </span>
    // `, 'text/html').body.firstChild;

    this.el.appendChild(this.tooltipEl);
  }

  events() {
    let self = this;

    // Toggle on click
    this.el.addEventListener("click", function(evt) {
      // Prevent default on clicks inside of tooltip menu
      if (self.tooltipEl.isSameNode(evt.target)) {
        evt.preventDefault();
      } else {
        self.toggle(evt);
      }
    });

    // Close tooltip if click off of it
    window.addEventListener("click", function(evt) {
      if (self.isOpen && !self.el.contains(evt.target)) {
        self.hideTooltip();
      }
    });

    // Update tooltip position once web fonts have loaded
    document.documentElement.addEventListener("fonts-loaded", function() {
      self.updatePosition();
    });

    // Hide on resize, recalc position
    window.addEventListener(
      "resize",
      debounce(function(event) {
        if ( self.isOpen ) {
          self.hideTooltip();
        }

        self.updatePosition();
      }, 150)
    );
  }

  updatePosition() {
    // Reset alignment classes/attributes, centers tooltip over toggle
    this.el.classList.remove("is-fullwidth");
    this.tooltipEl.setAttribute("data-align", "");

    // if ( !this.mediaQueryList.matches ) {
    //   return false;
    // }

    let bodyWidth = window.innerWidth - (this.options.gutter * 2);
    let bodyRightCutoff = window.innerWidth - this.options.gutter;

    let toggleBoundingRect = this.el.getBoundingClientRect();
    let toggleRightOffset = toggleBoundingRect.right;

    let tooltipBoundingRect = this.tooltipEl.getBoundingClientRect();
    let tooltipLeftOffset = tooltipBoundingRect.left;
    let tooltipRightOffset = tooltipBoundingRect.right;
    let tooltipWidth = tooltipBoundingRect.width;

    let cutoffRight = tooltipRightOffset > bodyRightCutoff;
    let cutoffLeft = tooltipLeftOffset < this.options.gutter;

    // If tooltip fits, do nothing to keep it centered
    if (!cutoffLeft && !cutoffRight) {
      return false;
    }

    // If right side is cutoff…
    if (tooltipRightOffset > bodyRightCutoff) {
      // …check if left side would fit before right aligning
      if (tooltipWidth <= toggleRightOffset - this.options.gutter) {
        this.tooltipEl.setAttribute("data-align", "right");
        return false;
      }
    }

    // If left side is cutoff…
    if (tooltipLeftOffset < this.options.gutter) {
      // …check if right side would fit before left aligning
      if (bodyWidth - tooltipLeftOffset <= tooltipWidth) {
        this.tooltipEl.setAttribute("data-align", "left");
        return false;
      }
    }

    // Tooltip can’t be aligned to toggle so make it full width
    this.el.classList.add("is-fullwidth");
    this.tooltipEl.setAttribute("data-align", "full");
  }

  showTooltip(evt) {
    this.isOpen = true;
    this.el.setAttribute("aria-expanded", "true");
    this.tooltipEl.setAttribute("aria-hidden", "false");
  }

  hideTooltip(evt) {
    this.isOpen = false;
    this.el.setAttribute("aria-expanded", "false");
    this.tooltipEl.setAttribute("aria-hidden", "true");
  }

  // Toggle expandable
  toggle(evt) {
    evt.preventDefault();

    if (this.isOpen) {
      this.hideTooltip();
    }
    else {
      this.showTooltip();
    }
  }
}
