// Detect input method in order to hide outlines in an accessible manner
// https://github.com/ten1seven/what-input
// NOTE: Can drop once Safari supports :focus-visible to save 1.7kB (gzip)
//       https://caniuse.com/css-focus-visible
import 'what-input';

// Emit “font-loaded” event so expandable components know to recalc their height
document.fonts.ready.then(function() {
  let fontEvent = new CustomEvent('fonts-loaded');
  document.documentElement.dispatchEvent(fontEvent);
});

// Link icons
import './modules/link-icons';
