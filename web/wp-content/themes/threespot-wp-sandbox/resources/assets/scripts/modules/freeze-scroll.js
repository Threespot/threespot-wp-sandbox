//------------------------------------------------------------------------
// FIXME: Demo script to test freeze-scroll, don’t include in production
//------------------------------------------------------------------------
import scroll from "@threespot/freeze-scroll";

var toggle = document.querySelectorAll('.scroll-toggle');
var isFrozen = false;

toggle.forEach(function(el) {
  el.addEventListener("click", function() {
    if (isFrozen) {
      console.log('scrolling enabled');
      scroll.unfreeze();
      isFrozen = false;
    }
    else {
      console.log('scrolling disabled');
      scroll.freeze();
      isFrozen = true;
    }
  });
});
