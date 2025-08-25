// SVG sprite plugin
// https://github.com/vbenjs/vite-plugin-svg-icons
import 'virtual:svg-icons-register';

// Emit “font-loaded” event so expandable components know to recalc their height
document.fonts.ready.then(function() {
  let fontEvent = new CustomEvent('fonts-loaded');
  document.documentElement.dispatchEvent(fontEvent);
});
