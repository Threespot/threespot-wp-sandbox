//------------------------------------------------------------------------
// Filters toggle (only shown in mobile view)
//-----------------------------------------------------------------------

import ExpandToggle from '@threespot/expand-toggle';

export default class FiltersToggle {
  constructor(el) {
    this.el = el;
    // NOTE: Breakpoint must match $bp-row in _filters.scss
    this.mediaQueryList = window.matchMedia('(min-width: 768px)');

    // Listen for breakpoint change
    this.mediaQueryList.addListener(evt => {
      if (evt.matches) {
        this.destroyToggle();
      } else {
        this.initToggle();
      }
    });

    // Init on load
    if (!this.mediaQueryList.matches) {
      this.initToggle();
    }
  }

  initToggle() {
    this.toggleExpand = new ExpandToggle(this.el);
  }

  destroyToggle() {
    // Checks to see if this.toggleExpand exists
    if (typeof this.toggleExpand !== 'undefined') {
      this.toggleExpand.destroy();
    }
  }
}
