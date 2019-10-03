// In order to improve perceived performance, place any small critical scripts
// here so they load sooner on slow connections (e.g. polyfills).
// DO NOT include any large libraries like Lodash, Vue, or D3.

// Modernizr
// FIXME: Generate a custom build based on what tests are needed, save to app/javascript/lib
// https://modernizr.com/download?setclasses
import "../lib/modernizr";

// Optional “clip-path” Modernizr tests for the following:
// - cssclippathcircle
// - cssclippathellipse
// - cssclippathinset
// - cssclippathpolygon
// - cssclippathsvg
//
// NOTE: You must enable the following Modernizr features:
// - addtest
// - domprefixes
// - prefixes
// - testprop
// - teststyles
//
// Note: Test may be added to Modernizr eventually
// https://github.com/Modernizr/Modernizr/issues/1969
//
// import "./lib/clip-path-detect";

// Font face observer to detect when font files have loaded.
// Can drop once font-display is supported:
// http://caniuse.com/#feat=css-font-rendering-controls
import "../modules/font-face";

// WARNING: If using SVG sprite, inline it to avoid CORS issues in Chrome!
// https://github.com/jonathantneal/svg4everybody/issues/16
// https://bugs.chromium.org/p/chromium/issues/detail?id=470601
// NOTE: If using external SVG sprite (even inline), use svg4everybody for IE 11- and Edge 12- support
// https://github.com/jonathantneal/svg4everybody
// import svg4everybody from "svg4everybody"
// svg4everybody();

// Allow tables to scroll horizontally in narrow viewports
import "../modules/scrollable-tables";
