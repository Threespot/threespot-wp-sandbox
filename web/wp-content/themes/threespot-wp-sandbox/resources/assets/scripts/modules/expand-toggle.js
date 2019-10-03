import ExpandToggle from "@threespot/expand-toggle";

const expandToggles = document.querySelectorAll("[data-expands]");

expandToggles.forEach( function(el) {
  let toggle = new ExpandToggle(el, {
    onReady: function() {
      console.log('Expand toggle: ready');
    }
  });

  toggle.on('expand', function() {
    console.log('Expand toggle: expand');
  });

  toggle.on('collapse', function() {
    console.log('Expand toggle: collapse');
  });

  toggle.on('destroy', function() {
    console.log('Expand toggle: destroy');
  });

  // Listen for events that may affect the target element’s height
  document.documentElement.addEventListener("fonts-loaded", function() {
    toggle.updateExpandedHeight();
  });
});
