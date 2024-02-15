//------------------------------------------------------------------------
// Mobile nav toggle
//------------------------------------------------------------------------
// Note: For some reason, the compiled JS causes a Webpack error:
//   “Uncaught TypeError: Getter must be a function: d”
// so we’re using the uncompiled source code.
import ExpandToggle from '@threespot/expand-toggle/index';

export default class mobileNavToggle {
  constructor(selector) {
    this.el = document.querySelector(selector);

    if (!this.el) {
      console.warn(`Unable to initialize nav toggle, no matching element for “${selector}”`);
      return false;
    }

    this.resetEvent = new CustomEvent('nav-reset');// used to close subnav menus

    // Nav breakpoint should match $layout-nav-bp in _layout-vars.scss
    this.mediaQueryList = window.matchMedia('(min-width: 860px)');

    // Listen for breakpoint change
    this.mediaQueryList.addListener(evt => {
      // Emit “nav-reset” event to trigger subnav menus to collapse
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

  subnavToggleHandler() {
    this.navToggle.updateExpandedHeight();
  }

  init() {
    this.navToggle = new ExpandToggle(this.el);

    // Update height when submennu is toggled
    document.documentElement.addEventListener('subnav-toggle', this.subnavToggleHandler.bind(this));
  }

  destroy() {
    this.navToggle.destroy();
    window.removeEventListener('subnav-toggle', this.subnavToggleHandler);
  }
}
