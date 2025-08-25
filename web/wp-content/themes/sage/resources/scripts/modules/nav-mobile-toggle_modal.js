//------------------------------------------------------------------------
// Main nav toggle
//------------------------------------------------------------------------
'use strict';
import Modal from '../lib/modals';

export default class mobileNavToggle {
  constructor(selector) {
    this.el = document.querySelector(selector);

    if (!this.el) {
      console.warn(`Unable to initialize nav toggle, no matching element for “${selector}”`);
      return false;
    }

    this.resetEvent = new CustomEvent('nav-reset');

    // Mobile nav toggle setup, breakpoint must match $layout-nav-bp in _layout-vars.scss
    this.mediaQueryList = window.matchMedia('(min-width: 860px)');

    // Listen for breakpoint change
    this.mediaQueryList.addListener(evt => {
      // Emit reset event to close subnav menus
      document.documentElement.dispatchEvent(this.resetEvent);

      if (evt.matches) {
        this.destroy();
      } else {
        this.init();
      }
    });

    // Init on load
    if (!this.mediaQueryList.matches) {
      this.init();
    }
  }

  init() {
    this.navToggle = new Modal(this.el, {
      modalContentClass: 'Nav-wrap'
    });
  }

  destroy() {
    this.navToggle.destroy();
  }
}
