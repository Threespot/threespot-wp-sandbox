//------------------------------------------------------------------------
// Critical JS
// ⚠️ NOTE: This must be manually minified and pasted in head.blade.php
//           Can minify with https://skalman.github.io/UglifyJS-online/
//------------------------------------------------------------------------
(function() {
  var d = document.documentElement,
    classes = d.className;

  // Replace 'no-js' class name with 'js'
  classes = classes.replace('no-js', 'js');

  // Detect iOS (needed for “font: -apple-system-body”, see base.scss)
  // https://stackoverflow.com/a/9039885/673457
  var iosDevices = ['iPad Simulator', 'iPhone Simulator', 'iPod Simulator', 'iPad', 'iPhone', 'iPod'];
  var isIOS = iosDevices.includes(navigator.platform) || (navigator.userAgent.includes('Mac') && 'ontouchend' in document);

  if (isIOS) {
    classes += ' ua-ios';
  }

  // Detect Safari to disable <details> height transition
  // https://bugs.webkit.org/show_bug.cgi?id=295713
  const isMacSafari =
    /^((?!chrome|android|crios|fxios).)*safari/i.test(navigator.userAgent) &&
    navigator.platform.toUpperCase().includes('MAC');

  if (isMacSafari) {
    classes += ' ua-safari';
  }

  // Apply classes to <html> element
  d.className = classes;
})();
