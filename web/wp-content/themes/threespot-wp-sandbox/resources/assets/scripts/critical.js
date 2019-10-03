//------------------------------------------------------------------------
// Critical JS to be inlined in the <head>
//
// ⚠️ NOTE: This file must be manually minified and copied to “critical-js.blade.php”
//------------------------------------------------------------------------
'use strict';

// Add user agent classes and check if web fonts have previously loaded
(function() {
  var ua = navigator.userAgent,
      d = document.documentElement,
      classes = d.className;

  // Replace 'no-js' class name with 'js'
  classes = classes.replace('no-js','js');

  // Detect iOS (needed to disable zoom on form elements)
  // http://stackoverflow.com/questions/9038625/detect-if-device-is-ios/9039885#9039885
  if ( /iPad|iPhone|iPod/.test(ua) && !window.MSStream ) {
    classes += ' ua-ios';

    // Add class for version of iOS
    var iosMatches = ua.match(/((\d+_?){2,3})\slike\sMac\sOS\sX/);
    if (iosMatches) {
      classes += ' ua-ios-' + iosMatches[1];// e.g. ua-ios-7_0_2
    }
  }

  // Detect Android (needed to disable print links on old devices)
  // http://www.ainixon.me/how-to-detect-android-version-using-js/
  if ( /Android/.test(ua) ) {
    var aosMatches = ua.match(/Android\s([0-9.]*)/);
    classes += aosMatches ? ' ua-aos ua-aos-' + aosMatches[1].replace(/\./g,'_') : ' ua-aos';
  }

  // Detect webOS (needed to disable optimizeLegibility)
  if ( /webOS|hpwOS/.test(ua) ) {
    classes += ' ua-webos';
  }

  // Check if “font-display” is supported, but only if fonts haven’t previously loaded
  if ( typeof sessionStorage !== "undefined" && !sessionStorage.fontsLoaded ) {
    // Use try/catch since IE 8- doesn’t support cssRules (but it does support sessionStorage)
    try {
      var isFontDisplaySupported = document.getElementById('cssTest').sheet.cssRules[0].cssText.indexOf("font-display") !== -1;

      if ( isFontDisplaySupported ) {
        sessionStorage.fontsLoaded = true;
      }
      else {
        // console.log('no font-display support');
      }
    } catch (e) {
      // IE 8 will fail
    }
  }

  // If no sessionStorage support (i.e. IE 7-, Opera Mini), add "fonts-loaded" class immediately (may cause FOUC)
  // http://caniuse.com/#feat=namevalue-storage
  // For modern browsers, if fonts have loaded before, assume they're cached and add class imediately
  if ( typeof sessionStorage == 'undefined' || !!sessionStorage.fontsLoaded ) {
    classes += ' fonts-loaded';
  }

  // Add classes
  d.className = classes;

  // Prevent errors from scripts attempting to use jQuery (e.g. legacy GTM scripts added by ALDF)
  // https://gist.github.com/tedw/97c0a847721baef835ee9c33ee2bc401
  window.jQuery = function() {
    console.warn("Something attempted to call jQuery but it’s not used on this site");
    return jQuery;// enables chaining
  }

  // List of most common jQuery methods (not exhaustive)
  var methods = ["on", "off", "click", "trigger", "attr", "data", "val", "css", "addClass", "removeClass", "toggleClass", "html", "text", "before", "after", "append", "prepend", "hide", "show", "toggle", "each"];

  for (var i=0, len=methods.length; i<len; i++) {
    window.jQuery[methods[i]] = function() {
      return false;
    }
  }

  // Create dummy Event function (required for Flickity)
  window.jQuery.Event = function() {
    return {};
  };

  window.$ = window.jQuery;
})();
