//------------------------------------------------------------------------
// Setup Font Face Observer to check when fonts have loaded
// https://fontfaceobserver.com
// https://www.filamentgroup.com/lab/font-events.html
// https://jonsuh.com/blog/font-loading-with-font-events/
// Note: Now that `font-display` is supported in some browsers, check for support first.
//       Once all modern browsers support it, consider loading fontfaceobserver asynchronously,
//       and possibly adding the Promise polyfill via Bable instead of bundling it in fontfaceobserver.
// http://caniuse.com/#feat=css-font-rendering-controls
// https://css-tricks.com/font-display-masses/#article-header-id-3
//------------------------------------------------------------------------
/* globals FontFaceObserver */ // JSHint global vars

// CustomEvent polyfill (IE 11- and aOS 4.3-)
// https://caniuse.com/#search=CustomEvent
import 'mdn-polyfills/CustomEvent';

// Font face observer script allows us to detect when web fonts have been downloaded
// NOTE: The main file in “fontfaceobserver” doesn’t include the Promise polyfill so we’re linking to the one that does
import FontFaceObserver from 'fontfaceobserver/fontfaceobserver';

// Only run if fonts have not been previously loaded
if ('sessionStorage' in window && !sessionStorage.fontsLoaded) {
  var htmlEl = document.documentElement;

  // Create new instance of FontFaceObserver for each font file
  var webFontName = new FontFaceObserver('webfontName', { weight: 400 });
  var webFontNameItalic = new FontFaceObserver('webfontName', {
    weight: 400,
    style: 'italic'
  });

  // Promise polyfill provided by fontfaceobserver.js
  Promise.all([webFontName.load(), webFontNameItalic.load()])
    .then(function() {
      // Add class
      htmlEl.className += ' fonts-loaded';

      // Set session var
      sessionStorage.fontsLoaded = "true";

      // Emit font-loaded event (used by expandable module to trigger height recalc)
      var triggerEvent = function() {
        var fontEvent = new CustomEvent('fonts-loaded');
        htmlEl.dispatchEvent(fontEvent);
      };

      // Use setTimeout to allow browser to paint first
      window.setTimeout(triggerEvent, 0);
    })
    .catch(function() {
      console.warn('Could not resolve Promise that webfonts loaded');
    });
}
