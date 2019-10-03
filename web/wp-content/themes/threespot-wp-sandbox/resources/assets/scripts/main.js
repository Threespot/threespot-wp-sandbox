// console() polyfill for IE 9 (throws error when dev tools are closed)
import "console-polyfill";

// NodeList.prototype.forEach() polyfill
import 'mdn-polyfills/NodeList.prototype.forEach';

// Object.assign polyfill
import 'mdn-polyfills/Object.assign';

// Element.prototype.closest polyfill
import 'mdn-polyfills/Element.prototype.closest';

// Font face observer to detect when font files have loaded.
// Can drop once font-display is supported:
// http://caniuse.com/#feat=css-font-rendering-controls
import "./modules/font-face";

// Detect input method in order to hide outlines in an accessible manner
// https://github.com/ten1seven/what-input
import "what-input";

// Picture element polyfill
// Required for IE 11- and Android 4.4-
// http://caniuse.com/#feat=picture
import "picturefill";

// Modules
// import "./modules/aria-label-text";// OPTIONAL: Only required in certain edge cases
import "./modules/autosubmit-form";
import "./modules/expand-toggle";
import "./modules/fluid-iframes";
import "./modules/fluid-svg-polyfill";
import "./modules/form-validation";
import "./modules/mailto";
import "./modules/modals";
import "./modules/orphans";
import "./modules/tooltips";
// import "./modules/vimeo";// OPTIONAL: Tracks Vimeo video events in GTM
import "./modules/youtube";// OPTIONAL: Tracks Vimeo video events in GTM

// FIXME: These are demo scripts, don’t include in production
// import "../modules/external-links";
// import "../modules/freeze-scroll";
// import "../modules/link-icons";
