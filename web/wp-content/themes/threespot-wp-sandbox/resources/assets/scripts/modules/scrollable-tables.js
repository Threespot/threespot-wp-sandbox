//------------------------------------------------------------------------
// Allow tables to scroll horizontally in narrow viewports
//
// TODO: Add more robust repsonsive table functionality a la https://inclusive-components.design/data-tables/
//------------------------------------------------------------------------
var tables = document.querySelectorAll(".u-richtext > table");

tables.forEach(function(el) {
  var wrapper = document.createElement("div");
  // Add “u-richtext” class since table styles are only applied to direct children (see _richtext.scss)
  wrapper.className = "u-richtext u-scrollX js-tablewrap";
  // Allow scrollbar when needed
  wrapper.style.overflow = "auto";
  // Set tabindex="0" for keyboard accessiblity
  // https://www.paciellogroup.com/blog/2016/02/short-note-on-improving-usability-of-scrollable-regions/
  wrapper.tabIndex = 0;
  el.parentNode.insertBefore(wrapper, el);
  wrapper.appendChild(el);
});
