//------------------------------------------------------------------------
// Modal windows
//------------------------------------------------------------------------
import Modal from "../lib/modals";

document.querySelectorAll(".Modal").forEach(el => {
  var modal = new Modal(el, {
    onReady: function() {
      console.log('Modal: ready');
    }
  });

  modal.on("open", function() {
    console.log('Modal: open');
  });

  modal.on("close", function() {
    console.log('Modal: close');
  });

  modal.on("destroy", function() {
    console.log('Modal: destroy');
  });

});
