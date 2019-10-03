//------------------------------------------------------------------------
// Modal windows
//
// - Progressively enhanced, works with pure CSS thanks to the `:target` pseudo selector
// - Supports multiple toggles and multiple close buttons
//
// References:
// - https://www.w3.org/TR/2017/NOTE-wai-aria-practices-1.1-20171214/examples/dialog-modal/dialog.html
// - https://www.smashingmagazine.com/2014/09/making-modal-windows-better-for-everyone/
// - https://www.smashingmagazine.com/2016/09/building-social-a-case-study-on-progressive-enhancement/
// - https://bitsofco.de/accessible-modal-dialog/
// - https://haltersweb.github.io/Accessibility/dialog.html
// - https://yoast.com/dev-blog/the-a11y-monthly-making-modals-accessible/
//
// Note: Avoid aria-modal="true" until support is beter
//       https://labs.levelaccess.com/index.php/ARIA_Dialog_Role_with_modal_true
//------------------------------------------------------------------------
"use strict";
import scroll from "@threespot/freeze-scroll";
import EventEmitter from "ev-emitter";

/**
 * Accessible modal window
 * @param {HTMLElement} el - Toggle button DOM node
 * @param {Object} opts - Options
 * @param {number} [opts.transitionSpeed="100"] - CSS transition speed, required to delay focus
 * @param {string} [opts.activeClasses=""] - Class(es) to apply when modal is open
 * @param {string} [opts.modalContentClass="Modal-content"] - Class of modal content wrapper
 * @param {function} [opts.onReady=""] - Ready callback function
 */
export default class Modal extends EventEmitter {
  constructor(el, opts) {
    // Have to call super() first before referencing “this” since we’re extending EventEmitter
    // https://stackoverflow.com/a/43591507/673457
    super();

    // Use Object.assign() to merge “opts” object with default values in this.options
    this.options = Object.assign(
      {},
      {
        transitionSpeed: 100, // CSS transition speed, required to delay focus
        activeClasses: "", // string, accepts multiple space-separated classes
        modalContentClass: "Modal-content", // string, optional
        onReady: null // ready callback function
      },
      opts
    );

    if (this.options.activeClasses.length) {
      // Check if active class string contains multiple classes
      if (this.options.activeClasses.indexOf(" ") > -1) {
        // Convert to array and remove any empty string values
        // caused by having multiple spaces in a row.
        this.options.activeClasses = this.options.activeClasses
          .split(" ")
          .filter(n => n.length);
      } else {
        // We still need to convert a single active class to an array
        // so we can use the spread syntax later in classList.add()
        this.options.activeClasses = [this.options.activeClasses];
      }
    }

    this.el = el;
    this.isOpen = false;
    this.hasToggles = false;
    this.contentEl = this.el.querySelector(
      "." + this.options.modalContentClass
    );
    this.closeEls = this.el.querySelectorAll("[data-modal-close]");

    // If modal has an ID, check for matching toggle elements with “data-modal” attribute
    if (this.el.id) {
      this.toggleEls = document.querySelectorAll(
        `[data-modal="${this.el.id}"]`
      );
      this.hasToggles = !!this.toggleEls.length;
    } else {
      // If modal doesn’t have an id, add a random one for “aria-controls”
      // https://gist.github.com/gordonbrander/2230317
      this.el.id = Math.random()
        .toString(36)
        .substr(2, 4);
    }

    // Store currently focused element when modal opens so we can restore focus when it closes
    this.prevFocusedEl = null;

    // Find focusable elements inside of modal window (used to prevent tabbing outside of modal)
    this.focusableEls = this.getFocusableEls();

    // Save first and last focusable elements
    if (this.focusableEls.length) {
      this.firstFocusableEl = this.focusableEls[0];
      this.lastFocusableEl = this.focusableEls[this.focusableEls.length - 1];
    }

    // Check for aria-label/aria-labelledby on modal (a11y best practice)
    if (
      !this.el.getAttribute("aria-label") &&
      !this.el.getAttribute("aria-labelledby")
    ) {
      console.warn(
        "A11y Issue: Modal window should have an “aria-label” or “aria-labelledby” attribute",
        this.el
      );
    }

    // Init modal window
    this.init();
  }

  init() {
    // Add aria attributes to modal window
    this.el.setAttribute("aria-hidden", "true");
    this.el.setAttribute("role", "dialog");

    // Add aria attributes to toggle buttons
    if (this.hasToggles) {
      this.toggleEls.forEach(toggleEl => {
        // Add “aria-controls” but be aware only JAWS supports it
        // https://inclusive-components.design/menus-menu-buttons/#ariacontrols
        toggleEl.setAttribute("aria-controls", this.el.id);
        toggleEl.setAttribute("aria-expanded", "false");
        toggleEl.setAttribute("role", "button");
      });
    }

    // Add aria attributes to close buttons
    if (this.closeEls.length) {
      this.closeEls.forEach(closeEl => {
        closeEl.setAttribute("role", "button");
      });
    }

    // Add event listeners
    this.bindEvents();

    // Check for ready callback
    if (typeof this.options.onReady === "function") {
      this.options.onReady();
    }

    // Check URL hash to determine if modal should start open
    if (
      this.el.id &&
      window.location.hash &&
      window.location.hash.substring(1) == this.el.id
    ) {
      this.open();
    }
  }

  destroy() {
    // Remove aria attributes on modal window
    this.el.removeAttribute("aria-hidden");
    this.el.removeAttribute("role");
    this.el.removeAttribute("tabindex");

    // Remove aria attributes on toggle buttons
    if (this.hasToggles) {
      this.toggleEls.forEach(toggleEl => {
        toggleEl.removeAttribute("aria-controls");
        toggleEl.removeAttribute("aria-expanded");
        toggleEl.removeAttribute("role");
      });
    }

    // Remove aria attributes on close buttons
    if (this.closeEls.length) {
      this.closeEls.forEach(closeEl => {
        closeEl.removeAttribute("aria-label");
        closeEl.removeAttribute("role");
      });
    }

    // Remove event listeners
    this.unbindEvents();

    // Trigger destroy event
    this.emitEvent("destroy");
  }

  // Find focusable elements inside of modal window (used to prevent tabbing outside of modal)
  // https://bitsofco.de/accessible-modal-dialog/
  getFocusableEls() {
    let focusableEls = this.el.querySelectorAll(
      'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex="0"]'
    );

    // Convert NodeList to Array
    return [...focusableEls];
  }

  // Get currently focused element
  // https://stackoverflow.com/a/40873560/673457
  // Could also use document.querySelector(":focus") but that’s likely less performant
  getFocusedEl() {
    if (
      document.hasFocus() &&
      document.activeElement !== document.body &&
      document.activeElement !== document.documentElement
    ) {
      return document.activeElement;
    }

    return null;
  }

  focusDelay(el) {
    var self = this;
    // Use setTimeout() to ensure element is focused
    // https://stackoverflow.com/questions/33955650/what-is-settimeout-doing-when-set-to-0-milliseconds/33955673
    // https://stackoverflow.com/questions/779379/why-is-settimeoutfn-0-sometimes-useful
    // https://blog.sessionstack.com/how-javascript-works-event-loop-and-the-rise-of-async-programming-5-ways-to-better-coding-with-2f077c4438b5
    window.setTimeout(() => el.focus(), this.options.transitionSpeed);
  }

  windowClickHandler(evt) {
    if (
      this.isOpen &&
      this.el.contains(evt.target) &&
      (this.contentEl && !this.contentEl.contains(evt.target))
    ) {
      this.close();
    }
  }

  keydownHandler(evt) {
    // Do nothing if modal is closed
    if (!this.isOpen) {
      return false;
    }

    // Close with escape key
    if (evt.which === 27) {
      this.close();
    }

    // Prevent tabbing outside of modal
    if (evt.which === 9) {
      // If no focusable items, close the modal
      if (!this.focusableEls.length) {
        this.close();
        return false;
      }

      // Find currently focused element
      let focusedEl = this.getFocusedEl();

      // If tabbing forward and the last item is focued, focus the first item
      if (!evt.shiftKey && focusedEl == this.lastFocusableEl) {
        // Prevent default since we're manually focusing the first element
        evt.preventDefault();
        this.firstFocusableEl.focus();
      } else if (
        evt.shiftKey &&
        (focusedEl == this.firstFocusableEl || focusedEl == this.contentEl)
      ) {
        // If tabbing backwards and the first item is focused, focus the last item
        evt.preventDefault();
        this.lastFocusableEl.focus();
      }
    }
  }

  bindEvents() {
    // Toggle buttons
    if (this.hasToggles) {
      // Note: Event callbacks need to be assigned to a var so they can be removed
      // https://stackoverflow.com/a/22870717/673457
      this.toggleClick = this.toggle.bind(this);

      this.toggleEls.forEach(toggleEl => {
        toggleEl.addEventListener("click", this.toggleClick);
      });
    }

    // Close buttons
    if (this.closeEls.length) {
      // Event callback
      this.closeClick = this.close.bind(this);

      this.closeEls.forEach(closeEl => {
        closeEl.addEventListener("click", this.closeClick);
      });
    }

    // Close if click outside of modal content
    this.windowClick = this.windowClickHandler.bind(this);
    window.addEventListener("click", this.windowClick);

    // Keyboard events
    this.keydown = this.keydownHandler.bind(this);
    window.addEventListener("keydown", this.keydown);
  }

  unbindEvents() {
    // Toggle buttons
    if (this.hasToggles) {
      this.toggleEls.forEach(toggleEl => {
        toggleEl.removeEventListener("click", this.toggleClick);
      });
    }

    // Close buttons
    if (this.closeEls.length) {
      this.closeEls.forEach(closeEl => {
        closeEl.removeEventListener("click", this.closeClick);
      });
    }

    // Window events
    window.removeEventListener("click", this.windowClick);
    window.removeEventListener("keydown", this.keydown);
  }

  // Expand expandable
  open() {
    var self = this;
    // Save currently focused element to focus on close
    this.prevFocusedEl = this.getFocusedEl();

    // Disable scrolling
    scroll.freeze();

    // Update modal aria attributes
    this.el.setAttribute("aria-hidden", "false");

    // Add custom classes
    if (this.options.activeClasses.length) {
      this.el.classList.add(...this.options.activeClasses);
    }

    // Update toggle aria attributes
    if (this.hasToggles) {
      this.toggleEls.forEach(toggleEl => {
        toggleEl.setAttribute("aria-expanded", "true");

        // Add custom classes
        if (this.options.activeClasses.length) {
          toggleEl.classList.add(...this.options.activeClasses);
        }
      });
    }

    // Focus modal on open
    if (this.contentEl) {
      this.contentEl.setAttribute("tabindex", "0");
      this.focusDelay(this.contentEl);
    } else {
      this.el.setAttribute("tabindex", "0");
      this.focusDelay(this.el);
    }

    // Update URL hash so users can link directly to the modal window content
    // Use history.replaceState() to prevent adding a new history entry
    // Note: If replaceState isn’t supported, modal-toggles.js won’t prevent the
    // default click event, causing the hash to update and creating a new history entry.
    if (history.replaceState) {
      history.replaceState(null, "", "#" + this.el.id);
    }

    // Update state
    this.isOpen = true;

    // Trigger open event
    this.emitEvent("open");
  }

  // Collapse expandable
  close(evt) {
    // Close buttons do not call toggle() so we must prevent default again here
    evg.preventDefault();

    // Clear hash using replaceState() to prevent adding a new history entry
    if (history.replaceState) {
      history.replaceState(null, "", window.location.pathname);
    }

    // Update modal aria attributes
    this.el.setAttribute("aria-hidden", "true");

    // Remove custom classes
    if (this.options.activeClasses.length) {
      this.el.classList.remove(...this.options.activeClasses);
    }

    // Update toggle aria attributes
    if (this.hasToggles) {
      this.toggleEls.forEach(toggleEl => {
        toggleEl.setAttribute("aria-expanded", "false");

        // Remove custom classes
        if (this.options.activeClasses.length) {
          toggleEl.classList.remove(...this.options.activeClasses);
        }
      });
    }

    // Enable scrolling
    scroll.unfreeze();

    // Shift focus to previously focused element
    if (this.prevFocusedEl) {
      this.focusDelay(this.prevFocusedEl);
    } else if (this.hasToggles) {
      // Focus the first toggle if nothing was previously focused
      this.focusDelay(this.toggleEls[0]);
    }

    // Update state
    this.isOpen = false;

    // Trigger close event
    this.emitEvent("close");
  }

  // Toggle expandable
  toggle(evt) {
    // Prevent default since we’re managing focus manually
    evt.preventDefault();

    if (this.isOpen) {
      this.close(evt);
    } else {
      this.open(evt);
    }
  }
}
