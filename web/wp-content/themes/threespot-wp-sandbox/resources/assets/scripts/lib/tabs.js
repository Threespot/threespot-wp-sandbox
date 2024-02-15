//------------------------------------------------------------------------
// Accessible tabs
//
// Adapted from script by Heydon Pickering
// https://inclusive-components.design/tabbed-interfaces/
// https://codepen.io/heydon/pen/veeaEa/
//------------------------------------------------------------------------
export default class Tabs {
  constructor(el) {
    this.el = el;

    // Get relevant elements and collections
    this.tablist = this.el.querySelector("[data-tablist]");
    this.tabs = this.tablist.querySelectorAll("a");
    this.panels = this.el.querySelectorAll("[data-tabpanel]");
    // Find any liks to tabbed content that live outside of the tab markup
    this.outsideTabLinks = document.querySelectorAll("[data-tablink]");

    // Add the tablist role to the first <ul> in the tabbed container
    this.tablist.setAttribute("role", "tablist");

    this.setupPanels();
    this.setupTabs();
    this.setupOutsideLinks();
    this.init();
  }

  // Initially activate the tabs
  init() {
    // Check if there is a hash in the URL that matches a tab
    let hash = window.location.hash;
    if (hash.length > 1) {
      // Use try/catch in case the hash is a malformed querySelector string
      try {
        let activeTab = this.tablist.querySelector(`a[href="${hash}"]`);
        let activePanel = this.el.querySelector(hash) || null;

        if (activeTab && activePanel) {
          activeTab.removeAttribute("tabindex");
          activeTab.setAttribute("aria-selected", "true");
          activePanel.hidden = false;
          return false;
        }
      } catch (e) {
        console.warn(`URL hash did not match a tab ID: ${hash}`);
      }
    }

    // Activate the first tab if no matching hash in the URL
    this.tabs[0].removeAttribute("tabindex");
    this.tabs[0].setAttribute("aria-selected", "true");
    this.panels[0].hidden = false;
  }

  // Add tab panel semantics and hide them all
  setupPanels() {
    this.panels.forEach((panel, index) => {
      panel.setAttribute("role", "tabpanel");
      panel.setAttribute("tabindex", "-1");
      let id = panel.getAttribute("id");
      panel.setAttribute("aria-labelledby", this.tabs[index].id);
      panel.hidden = true;
    });
  }

  // Add tab semantics and remove user focusability for each tab
  setupTabs() {
    this.tabs.forEach((tab, index) => {
      tab.setAttribute("role", "tab");
      tab.setAttribute("id", "tab" + (index + 1));
      tab.setAttribute("tabindex", "-1");
      tab.parentNode.setAttribute("role", "presentation");

      // Handle clicking of tabs for mouse users
      tab.addEventListener("click", e => {
        e.preventDefault();
        let currentTab = this.tablist.querySelector("[aria-selected]");
        if (e.currentTarget !== currentTab) {
          this.switchTab(currentTab, e.currentTarget);
        }
      });

      // Handle keydown events for keyboard users
      tab.addEventListener("keydown", e => {
        // Get the index of the current tab in the tabs node list
        let currentTabIndex = Array.prototype.indexOf.call(
          this.tabs,
          e.currentTarget
        );
        // Work out which key the user is pressing and
        // calculate the new tab’s index where appropriate
        let dir = null;
        switch (e.which) {
          case 37:
            dir = currentTabIndex - 1;
            break;
          case 39:
            dir = currentTabIndex + 1;
            break;
          case 40:
            dir = "down";
            break;
        }

        if (dir !== null) {
          e.preventDefault();
          // If the down key is pressed, move focus to the open panel,
          // otherwise switch to the adjacent tab
          if (dir === "down") {
            this.panels[index].focus();
          } else if (this.tabs[dir]) {
            this.switchTab(e.currentTarget, this.tabs[dir]);
          }
        }
      });
    });
  }

  // Add click handler to links that target one of the current tabs
  setupOutsideLinks() {
    this.outsideTabLinks.forEach(link => {
      if (link.hash) {
        let tabLink = this.tablist.querySelector(`[href="${link.hash}"]`);

        if (tabLink) {
          link.addEventListener("click", e => {
            e.preventDefault();
            tabLink.click();
            // Scroll to top of tabs
            window.scrollTo(0, this.tablist.offsetTop);
          });
        }
      }
    });
  }

  // The tab switching function
  switchTab(oldTab, newTab) {
    newTab.focus();
    // Make the active tab focusable by the user (Tab key)
    newTab.removeAttribute("tabindex");
    // Set the selected state
    newTab.setAttribute("aria-selected", "true");
    oldTab.removeAttribute("aria-selected");
    oldTab.setAttribute("tabindex", "-1");
    // Get the indices of the new and old tabs to find the correct
    // tab panels to show and hide
    let index = Array.prototype.indexOf.call(this.tabs, newTab);
    let oldIndex = Array.prototype.indexOf.call(this.tabs, oldTab);
    this.panels[oldIndex].hidden = true;
    this.panels[index].hidden = false;

    // Update hash so users can link to current tab
    // (only update if replaceState() is supported to avoid adding history entries)
    if (history.replaceState) {
      history.replaceState(null, null, newTab.hash);
    }
  }
}
