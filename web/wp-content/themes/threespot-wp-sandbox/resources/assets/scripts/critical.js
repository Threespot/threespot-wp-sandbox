//------------------------------------------------------------------------
// Critical JS
// ⚠️ NOTE: This must be manually minified and pasted in head.blade.php
//           Can minify with https://skalman.github.io/UglifyJS-online/
//------------------------------------------------------------------------
(function() {
  var ua = navigator.userAgent,
    d = document.documentElement,
    classes = d.className;

  // Replace 'no-js' class name with 'js'
  classes = classes.replace('no-js', 'js');

  // Detect iOS (needed to disable zoom on form elements)
  // http://stackoverflow.com/questions/9038625/detect-if-device-is-ios/9039885#9039885
  if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) {
    classes += ' ua-ios';
  }

  // Optional: Detect webOS (needed to disable optimizeLegibility)
  // if (/webOS|hpwOS/.test(ua)) {
  //   classes += ' ua-webos';
  // }

  // Apply classes to <html> element
  d.className = classes;
})();
