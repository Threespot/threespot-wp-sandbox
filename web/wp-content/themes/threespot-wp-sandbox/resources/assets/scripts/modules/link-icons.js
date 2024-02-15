//------------------------------------------------------------------------
// Append icons to links and prevent them from wrapping
//------------------------------------------------------------------------
import Icon from '../lib/link-icons';

// Optional: Add “data-icon” attribute support
// document.querySelectorAll('[data-icon]').forEach((el) => {
//   new Icon(el);
// });

// Add external icons to richtext links
// Note: Buttons are handled separately
document.querySelectorAll('.u-richtext > *:not([id^="block"]) a:not(.wp-block-button__link)')
  .forEach((el) => {
    new Icon(el, {
      name: 'none',// no default icon, only apply external and download icons
      shouldSwap: true,
    });
  });
